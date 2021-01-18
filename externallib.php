<?php

// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/*
 * BrookesEDGE web service - external library
 *
 * @package    local_brookes_edge_ws
 * @author     Peter Welham
 * @copyright  2020, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
 
require_once($CFG->libdir . "/externallib.php");
require_once($CFG->dirroot . "/course/lib.php");

class local_brookes_edge_ws_external extends external_api {

	public static function get_attributes_parameters() {
		return new external_function_parameters(array());
	}

	public static function get_attributes_returns() {
		return new external_multiple_structure(
			new external_single_structure(
				array(
					'attribute_code' => new external_value(PARAM_TEXT, 'EDGE attribute code'),
					'attribute_name' => new external_value(PARAM_TEXT, 'EDGE attribute name'),
					'attribute_entries_submitted' => new external_value(PARAM_INT, 'Attribute submitted entry count')
				)
			)
		);
	}

	public static function get_attributes() {
		global $USER, $DB;

		// Context validation
		$context = context_system::instance();
		self::validate_context($context);

        // Capability checking
		require_capability('moodle/blog:create', $context);

		// Get the tag ID of BrookesEDGE
		$criteria = "rawname = 'BrookesEDGE'";
	    if (!($tag = $DB->get_record_select('tag', $criteria, null, 'id'))) {
			throw new invalid_parameter_exception('BrookesEDGE tag not found');
		}
		
		// Store the ID of each related tag (EDGE attribute) in an array
		$criteria = "tagid = '" . $tag->id . "' AND itemtype = 'tag'";
		$db_ret = $DB->get_records_select('tag_instance', $criteria, null, 'itemid');
		$ids = array();
		foreach ($db_ret as $row) {
			$ids[] = $row->itemid;
		}
		
		// Get the details of the related tags (EDGE attributes) and store them in an array sorted by name
		$db_ret = $DB->get_records_list('tag', 'id', $ids, 'name', 'id, rawname');
		$attributes = array();
		foreach ($db_ret as $row) {
			$pos_open = strpos($row->rawname, ' (');
			if ($pos_open !== false) {
				$pos_close = strpos($row->rawname, ')', $pos_open + 2);
				if ($pos_close !== false) {
					$attribute_code = substr($row->rawname, ($pos_open + 2), ($pos_close - $pos_open - 2));
					$criteria = "author_id = '" . $USER->id . "' and attribute_code = '" . $attribute_code . "' and submitted = ";
					$attributes[] = array(
						'attribute_name' => substr($row->rawname, 0, $pos_open),
						'attribute_code' => $attribute_code,
						'attribute_entries_submitted' => $DB->count_records_select('local_brookes_edge_entries', $criteria . '1')
					);
				}
			}
		}

		return $attributes;
	}

	public static function get_attribute_parameters() {
		return new external_function_parameters(
			array(
				'attribute_name' => new external_value(PARAM_TEXT, 'Name of the required attribute'),
				'attribute_code' => new external_value(PARAM_TEXT, 'Code of the required attribute')
			)
		);
	}

	public static function get_attribute_returns() {
		return new external_single_structure(
			array(
				'attribute_name' => new external_value(PARAM_TEXT, 'Attribute name'),
				'attribute_description' => new external_value(PARAM_RAW, 'Attribute description'),
				'attribute_entries_submitted' => new external_value(PARAM_INT, 'Attribute submitted entry count'),
				'attribute_entries_not_submitted' => new external_value(PARAM_INT, 'Attribute not-submitted entry count')
			)
		);
	}

	public static function get_attribute($attribute_name, $attribute_code) {
		global $USER, $DB;

		// Context validation
		$context = context_system::instance();
		self::validate_context($context);

        // Capability checking
		require_capability('moodle/blog:create', $context);

		// Get the details of the attribute
		$criteria = "rawname = '" . $attribute_name . " (" . $attribute_code . ")'";
	    if (!($tag = $DB->get_record_select('tag', $criteria, null, 'id, rawname, description'))) {
			throw new invalid_parameter_exception('EDGE attribute tag not found');
		}
		
		$attribute = array();
		$pos_open = strpos($tag->rawname, ' (');
		if ($pos_open !== false) {
			$pos_close = strpos($tag->rawname, ')', $pos_open + 2);
			if ($pos_close !== false) {
				$criteria = "author_id = '" . $USER->id . "' and attribute_code = '" . $attribute_code . "' and submitted = ";
				$attribute = array(
					'attribute_name' => substr($tag->rawname, 0, $pos_open),
					'attribute_description' => $tag->description,
					'attribute_entries_submitted' => $DB->count_records_select('local_brookes_edge_entries', $criteria . '1'),
					'attribute_entries_not_submitted' => $DB->count_records_select('local_brookes_edge_entries', $criteria . '0')
				);
			}
		}
		$sql = 'SELECT id, attribute_code, title, submitted '
			. 'FROM {local_brookes_edge_entries} '
			. 'WHERE author_id = ? '
			. 'ORDER BY title';

		return $attribute;
	}
	
	public static function get_all_activities_parameters() {
		return new external_function_parameters(array());
	}
	
	public static function get_all_activities_returns() {
		return new external_multiple_structure(
			new external_single_structure(
				array (
					'activity_id' => new external_value(PARAM_INT, 'Activity ID'),
					'activity_name' => new external_value(PARAM_TEXT, 'Activity name'),
					'activity_shortname' => new external_value(PARAM_TEXT, 'Activity short name'),
					'activity_faculty' => new external_value(PARAM_TEXT, 'Activity faculty'),
					'activity_mnemonic' => new external_value(PARAM_TEXT, 'Activity mnemonic'),
					'activity_attributes' => new external_multiple_structure(new external_value(PARAM_TEXT, 'Activity attribute')),
					'activity_description' => new external_value(PARAM_RAW, 'Activity description')
				)
			)
		);
	}
		
	public static function get_all_activities() {
		global $DB;

		// Context validation
		$context = context_system::instance();
		self::validate_context($context);

        // Capability checking
		require_capability('moodle/blog:create', $context);

		// Select all activities
		$sql = 'SELECT c.id, c.fullname, c.shortname, c.summary, substr(c.idnumber, 6) AS codes '
			. 'FROM {course} c '
			. 'WHERE c.idnumber LIKE "EDGE~%~%~%" '
			. 'AND c.visible = 1';
		$activity_records = $DB->get_records_sql($sql);

		$activities = array();
		foreach ($activity_records as $activity) {
			$codes = explode('~', $activity->codes); // We know that there's at least three elements
			$faculty = array_shift($codes);
			$mnemonic = array_shift($codes);
			$activities[] = array(
				'activity_id' => $activity->id,
				'activity_name' => $activity->fullname,
				'activity_shortname' => $activity->shortname,
				'activity_faculty' => $faculty,
				'activity_mnemonic' => $mnemonic,
				'activity_attributes' => $codes,
				'activity_description' => $activity->summary
			);
		}

		return $activities;
	}

	public static function get_activities_parameters() {
		return new external_function_parameters(
			array(
				'attribute_code' => new external_value(PARAM_TEXT, 'Code of the required attribute')
			)
		);
	}

	public static function get_activities_returns() {
		return new external_multiple_structure(
			new external_single_structure(
				array(
					'activity_id' => new external_value(PARAM_INT, 'Activity ID'),
					'activity_name' => new external_value(PARAM_TEXT, 'Activity name'),
					'activity_joined' => new external_value(PARAM_TEXT, 'Activity joined flag')
				)
			)
		);
	}

	public static function get_activities($attribute_code = '') {
		global $DB, $USER;

		$params = self::validate_parameters(
			self::get_activities_parameters(), array(
				'attribute_code' => $attribute_code
			)
		);
		
		// Context validation
		$context = context_system::instance();
		self::validate_context($context);

        // Capability checking
		require_capability('moodle/blog:create', $context);

		$faculties = self::__get_faculties(); // User's course categories mapped to faculty abbreviations

		$sql = 'SELECT c.shortname AS name, c.id AS id, c.visible AS visible, c.summary AS description, substr(c.idnumber, 6) AS codes
			FROM {course} c
			WHERE c.idnumber LIKE "EDGE~%~%~%"
			ORDER BY c.shortname';
		$activity_records = $DB->get_records_sql($sql);

		$activities = array();
		foreach ($activity_records as $activity) {
			$codes = explode('~', $activity->codes); // We know that there's at least three elements
			$faculty = array_shift($codes);
			if (in_array($faculty, $faculties) || (substr($USER->username, 0, 1 ) === "p" && is_numeric(substr($USER->username, 1 )))) {
				$mnemonic = array_shift($codes);
				if (is_enrolled(context_course::instance($activity->id), $USER)) {
					$joined = '*';
				} else {
					$joined = '';
				}
				if (($attribute_code == '' || in_array($attribute_code, $codes)) && ($joined == '*' || $activity->visible == '1')) {
					$activities[] = array(
						'activity_id' => $activity->id,
						'activity_name' => $activity->name,
						'activity_joined' => $joined
					);
				}
			}
		}

		return $activities;
	}

	public static function get_activity_parameters() {
		return new external_function_parameters(
			array(
				'activity_id' => new external_value(PARAM_INT, 'ID of the required activity')
			)
		);
	}

	public static function get_activity_returns() {
		return new external_single_structure(
			array(
				'activity_name' => new external_value(PARAM_TEXT, 'Activity name'),
				'activity_description' => new external_value(PARAM_RAW, 'Activity description'),
				'activity_joined' => new external_value(PARAM_TEXT, 'Activity joined')
			)
		);
	}

	public static function get_activity($activity_id) {
		global $DB, $USER;

		// Context validation
		$context = context_system::instance();
		self::validate_context($context);

        // Capability checking
		require_capability('moodle/blog:create', $context);

		// Get the details of the activity
		$criteria = "id = '" . $activity_id . "'";
	    if (!($course = $DB->get_record_select('course', $criteria, null, 'shortname, summary'))) {
			throw new invalid_parameter_exception('Activity not found');
		}
		
		if (is_enrolled(context_course::instance($activity_id), $USER)) {
			$joined = '*';
		} else {
			$joined = '';
		}
		
		$activity = array(
			'activity_name' => $course->shortname,
			'activity_description' => $course->summary,
			'activity_joined' => $joined
		);

		return $activity;
	}

	public static function get_activity_attributes_parameters() {
		return new external_function_parameters(
			array(
				'activity_id' => new external_value(PARAM_INT, 'ID of the required activity')
			)
		);
	}

	public static function get_activity_attributes_returns() {
		return new external_multiple_structure(
			new external_single_structure(
				array(
					'attribute_code' => new external_value(PARAM_TEXT, 'EDGE attribute code')
				)
			)
		);
	}

	public static function get_activity_attributes($activity_id) {
		global $DB;

		// Context validation
		$context = context_system::instance();
		self::validate_context($context);

        // Capability checking
		require_capability('moodle/blog:create', $context);

		// Get the activity
		$criteria = "id = '" . $activity_id . "'";
	    if (!($activity = $DB->get_record_select('course', $criteria, null, 'idnumber'))) {
			throw new invalid_parameter_exception('Activity not found');
		}
		
		$attributes = [];
		$codes = explode('~', $activity->idnumber); // We know that there should be at least four elements
		$prefix = array_shift($codes);
		$faculty = array_shift($codes);
		$mnemonic = array_shift($codes);
		foreach ($codes as $code) {
			$attributes[] = array(
				'attribute_code' => $code
			);
		}
		
		return $attributes;
	}

	public static function join_activity_parameters() {
		return new external_function_parameters(
			array(
				'activity_id' => new external_value(PARAM_INT, 'The course ID of the activity to join')
			)
		);
	}

	public static function join_activity_returns() {
		return null;
	}
	
 	public static function join_activity($activity_id) {
		
		$params = self::validate_parameters(
			self::join_activity_parameters(), array(
				'activity_id' => $activity_id
			)
		);

		// Check that the self enrolment plugin is installed
		$enrol = enrol_get_plugin('self');
		if (empty($enrol)) {
			throw new moodle_exception('canntenrol', 'enrol_self');
		}
		
		// Check a plugin instance exists (and is enabled) for this course
		$instance = null;
		$enrolinstances = enrol_get_instances($params['activity_id'], true);
		foreach ($enrolinstances as $courseenrolinstance) {
			if ($courseenrolinstance->enrol == 'self') {
				$instance = $courseenrolinstance;
				break;
			}
		}
		if (empty($instance)) {
			throw new moodle_exception('canntenrol', 'enrol_self');
		}
		
		// Check that they can actually enroll
		if (!$enrol->can_self_enrol($instance, true)) {
			throw new moodle_exception('canntenrol', 'enrol_self');
		}

		// OK, enroll
		$enrol->enrol_self($instance);

		return;
	}
	
	public static function leave_activity_parameters() {
		return new external_function_parameters(
			array(
				'activity_id' => new external_value(PARAM_INT, 'The course ID of the activity to leave')
			)
		);
	}

	public static function leave_activity_returns() {
		return null;
	}
	
 	public static function leave_activity($activity_id) {
		global $USER;
		
		$params = self::validate_parameters(
			self::leave_activity_parameters(), array(
				'activity_id' => $activity_id
			)
		);

		// Check that the self enrolment plugin is installed
		$enrol = enrol_get_plugin('self');
		if (empty($enrol)) {
			throw new moodle_exception('canntenrol', 'enrol_self');
		}
		
		// Check a plugin instance exists (and is enabled) for this course
		$instance = null;
		$enrolinstances = enrol_get_instances($params['activity_id'], true);
		foreach ($enrolinstances as $courseenrolinstance) {
			if ($courseenrolinstance->enrol == 'self') {
				$instance = $courseenrolinstance;
				break;
			}
		}
		if (empty($instance)) {
			throw new moodle_exception('canntenrol', 'enrol_self');
		}
		
		// Check thay can actually unenroll themselves from this course
        if (!$enrol->allow_unenrol($instance) || !has_capability("enrol/self:unenrolself", context_course::instance($params['activity_id']))) {
			throw new moodle_exception('canntenrol', 'enrol_self');
		}

		// OK, unenroll
		$enrol->unenrol_user($instance, $USER->id);

		return;
 	}
	
	public static function get_entries_parameters() {
		return new external_function_parameters(
			array(
				'attribute_code' => new external_value(PARAM_TEXT, 'Code of the attribute (null for all) for which to return entries')
			)
		);
	}

	public static function get_entries_returns() {
		return new external_multiple_structure(
			new external_single_structure(
				array(
					'id' => new external_value(PARAM_INT, 'ID of entry'),
					'title' => new external_value(PARAM_TEXT, 'Title'),
					'submitted' => new external_value(PARAM_TEXT, 'Submitted?')
				)
			)
		);
	}

	public static function get_entries($attribute_code) {
		global $DB, $USER;

		$params = self::validate_parameters(
			self::get_entries_parameters(), array(
				'attribute_code' => $attribute_code
			)
		);
		
		// Context validation
		$context = context_system::instance();
		self::validate_context($context);

        // Capability checking
		require_capability('moodle/blog:create', $context);

		$sql = 'SELECT id, attribute_code, title, submitted '
			. 'FROM {local_brookes_edge_entries} '
			. 'WHERE author_id = ? '
			. 'ORDER BY title';
		
		$db_recs = $DB->get_records_sql($sql, array($USER->id));

		$entries = array();
		foreach ($db_recs as $entry) {
			if ($params['attribute_code'] == '' || $entry->attribute_code == $params['attribute_code']) {
				if ($entry->submitted == 1) {
					$submitted = '*';
				} else {
					$submitted = '';
				}
				$entries[] = array(
					'id' => $entry->id,
					'title' => $entry->title,
					'submitted' => $submitted
				);
			}
		}
		
		return $entries;
	}

	public static function get_entry_parameters() {
		return new external_function_parameters(
			array(
				'id' => new external_value(PARAM_INT, 'ID of the entry required')
			)
		);
	}

	public static function get_entry_returns() {
		return new external_single_structure(
			array(
				'activity_id' => new external_value(PARAM_INT, 'Moodle ID of the course/activity'),
				'activity_name' => new external_value(PARAM_TEXT, 'Activity name'),
				'attribute_code' => new external_value(PARAM_TEXT, 'Attribute code'),
				'title' => new external_value(PARAM_TEXT, 'Title'),
				'situation' => new external_value(PARAM_TEXT, 'Situation'),
				'task' => new external_value(PARAM_TEXT, 'Task'),
				'action' => new external_value(PARAM_TEXT, 'Action'),
				'result' => new external_value(PARAM_TEXT, 'Result'),
				'link' => new external_value(PARAM_TEXT, 'Link'),
				'submitted' => new external_value(PARAM_TEXT, 'Submitted?')
			)
		);
	}

	public static function get_entry($id) {
		global $DB, $USER;

		$params = self::validate_parameters(
			self::get_entry_parameters(), array(
				'id' => $id
			)
		);
		
		if ($params['id'] < 1) {
			throw new invalid_parameter_exception('id must be a positive integer');
		}

		// Context validation
		$context = context_system::instance();
		self::validate_context($context);

        // Capability checking
		require_capability('moodle/blog:create', $context);

		// Get the given entry
		$rec = $DB->get_record('local_brookes_edge_entries', array('id' => $params['id']), '*', MUST_EXIST);

		// Get the details of the activity
		$criteria = "id = '" . $rec->activity_id . "'";
	    if (!($course = $DB->get_record_select('course', $criteria, null, 'shortname'))) {
			throw new invalid_parameter_exception('Activity not found');
		}

		if ($rec->submitted == 1) {
			$submitted = '*';
		} else {
			$submitted = '';
		}
		
		$entry = array(
			'activity_id' => $rec->activity_id,
			'activity_name' => $course->shortname,
			'attribute_code' => $rec->attribute_code,
			'title' => $rec->title,
			'situation' => $rec->situation,
			'task' => $rec->task,
			'action' => $rec->action,
			'result' => $rec->result,
			'link' => $rec->link,
			'submitted' => $submitted
		);
			
		return $entry;
	}
	
	public static function save_entry_parameters() {
		return new external_function_parameters(
			array(
				'id' => new external_value(PARAM_INT, 'ID of entry to amend (if any)'),
				'activity_id' => new external_value(PARAM_INT, 'ID of activity'),
				'attribute_code' => new external_value(PARAM_TEXT, 'Attribute code'),
				'title' => new external_value(PARAM_TEXT, 'Title'),
				'situation' => new external_value(PARAM_TEXT, 'Situation'),
				'task' => new external_value(PARAM_TEXT, 'Task'),
				'action' => new external_value(PARAM_TEXT, 'Action'),
				'result' => new external_value(PARAM_TEXT, 'Result'),
				'link' => new external_value(PARAM_TEXT, 'Link')
			)
		);
	}

	public static function save_entry_returns() {
		return new external_single_structure(
			array(
				'id' => new external_value(PARAM_INT, 'ID of added or amended entry')
			)
		);
	}

	public static function save_entry($id, $activity_id, $attribute_code, $title, $situation, $task, $action, $result, $link) {
		global $DB, $USER;

		// Parameter validation
		$params = self::validate_parameters(
			self::save_entry_parameters(), array(
				'id' => $id,
				'activity_id' => $activity_id,
				'attribute_code' => $attribute_code,
				'title' => $title,
				'situation' => $situation,
				'task' => $task,
				'action' => $action,
				'result' => $result,
				'link' => $link
			)
		);

		if ($params['id'] < 0) {
			throw new invalid_parameter_exception('id must not be negative');
		}

		if ($params['activity_id'] < 1) {
			throw new invalid_parameter_exception('activity_id must be a positive integer');
		}

		if (strlen($params['attribute_code']) < 1) {
			throw new invalid_parameter_exception('attribute_code must be a non-empty string');
		}

		if (strlen($params['title']) < 1) {
			throw new invalid_parameter_exception('title must be a non-empty string');
		}

		if (strlen($params['situation']) < 1) {
			throw new invalid_parameter_exception('situation must be a non-empty string');
		}
		
		if (strlen($params['task']) < 1) {
			throw new invalid_parameter_exception('task must be a non-empty string');
		}

		if (strlen($params['action']) < 1) {
			throw new invalid_parameter_exception('action must be a non-empty string');
		}

		if (strlen($params['result']) < 1) {
			throw new invalid_parameter_exception('result must be a non-empty string');
		}

		// Context validation
		$context = context_system::instance();
		self::validate_context($context);

        // Capability checking
		require_capability('moodle/blog:create', $context);

		// Get the given entry (if any)
		if ($params['id'] != 0) {
			$rec = $DB->get_record('local_brookes_edge_entries', array('id' => $params['id'], 'author_id' => $USER->id), '*', MUST_EXIST);
		} else {
			$rec = new stdClass();
			$rec->id = 0;
			$rec->author_id = $USER->id;
			$rec->submitted = 0;
		}

		$rec->activity_id = $params['activity_id'];
		$rec->attribute_code = $params['attribute_code'];
		$rec->title = $params['title'];
		$rec->situation = $params['situation'];
		$rec->task = $params['task'];
		$rec->action = $params['action'];
		$rec->result = $params['result'];
		$rec->link = $params['link'];

		// Store the update time
		$date = new DateTime();
		$rec->update_time = $date->getTimestamp();

		if ($rec->id != 0) {
			$rec_id = $rec->id;
			$DB->update_record('local_brookes_edge_entries', $rec);
		} else {		
			$rec_id = $DB->insert_record('local_brookes_edge_entries', $rec);
		}

		return array('id' => $rec_id);
	}

	public static function submit_entry_parameters() {
		return new external_function_parameters(
			array(
				'id' => new external_value(PARAM_INT, 'ID of entry to submit')
			)
		);
	}

	public static function submit_entry_returns() {
		return new external_single_structure(
			array(
				'message' => new external_value(PARAM_TEXT, 'Submission message')
			)
		);
	}

	public static function submit_entry($id) {
		global $DB, $USER;

		// Parameter validation
		$params = self::validate_parameters(
				self::delete_entry_parameters(), array(
					'id' => $id
				)
		);

		if ($params['id'] < 1) {
			throw new invalid_parameter_exception('id must be a positive integer');
		}

		// Context validation
		$context = context_system::instance();
		self::validate_context($context);

        // Capability checking
		require_capability('moodle/blog:create', $context);

		// Get the given entry
		$rec = $DB->get_record('local_brookes_edge_entries', array('id' => $params['id'], 'author_id' => $USER->id), '*', MUST_EXIST);
		
		// Check the word count
		$word_count = str_word_count($rec->situation) + str_word_count($rec->task) + str_word_count($rec->action) + str_word_count($rec->result);
		$minimum_words = get_config('local_brookes_edge', 'minimum_words');
		if ($word_count < $minimum_words) {
			return array('message' => get_string('submission_failure', 'local_brookes_edge_ws', array('word_count' => $word_count, 'minimum_words' => $minimum_words)));
		}

		$rec->submitted = 1;

		// Store the update time
		$date = new DateTime();
		$rec->update_time = $date->getTimestamp();

		$DB->update_record('local_brookes_edge_entries', $rec);
		
		// Check what they have, save any new award and determine a relevant message
		$edge = $DB->record_exists('local_brookes_edge_awards', array('recipient_id' => $USER->id)); // Already have the award?
		$count = self::__count_submissions();
		if ($count['entries'] == 1) {
			$entries = get_string('entry', 'local_brookes_edge');
		} else {
			$entries = get_string('entries', 'local_brookes_edge', $count['entries']);
		}
		if ($count['attributes'] == 1) {
			$attributes = get_string('attribute', 'local_brookes_edge');
		} else {
			$attributes = get_string('attributes', 'local_brookes_edge', $count['attributes']);
		}
		$minimum_entries = get_config('local_brookes_edge', 'minimum_entries');
		$minimum_attributes = get_config('local_brookes_edge', 'minimum_attributes');
		if (!$edge && ($count['entries'] >= $minimum_entries) && ($count['attributes'] >= $minimum_attributes)) { // AWARD!!!
			$rec = new stdClass();
			$rec->id = 0;
			$rec->recipient_id = $USER->id;
			$rec->award_time = $date->getTimestamp();
			$rec_id = $DB->insert_record('local_brookes_edge_awards', $rec);
			$message = get_string('submission_edge', 'local_brookes_edge_ws', array('entries' => $entries, 'attributes' => $attributes));
			$admin = get_complete_user_data('username', 'brookes_edge');
			$admin->customheaders = array ( // Add email headers to help prevent auto-responders
				'Precedence: Bulk',
				'X-Auto-Response-Suppress: All',
				'Auto-Submitted: auto-generated'
			);
			$user = get_complete_user_data('id', $USER->id);
			$salutation = get_string('salutation', 'local_brookes_edge') . ' ' . $user->firstname;
			$html = '<p>' . $salutation . '</p><p>' . $message . '</p><p>' . get_string('certificate_message', 'local_brookes_edge_ws')
				. '</p><p>' . get_string('closing', 'local_brookes_edge') . '</p><p>' . $admin->firstname . ' ' . $admin->lastname . '</p>';
			email_to_user($user, $admin, get_string('title', 'local_brookes_edge'), html_to_text($html), $html);
			$html = get_string('notification', 'local_brookes_edge_ws', array('firstname' => $user->firstname, 'lastname' => $user->lastname, 'username' => $user->username));
			email_to_user($admin, $admin, get_string('title', 'local_brookes_edge'), html_to_text($html), $html);
		} else if ($count['entries'] > 1) {
			$message = get_string('submission_general', 'local_brookes_edge_ws', array('entries' => $entries, 'attributes' => $attributes));
		} else { 
			$message = get_string('submission_first', 'local_brookes_edge_ws');
		}

		return array('message' => $message);
	}

	public static function delete_entry_parameters() {
		return new external_function_parameters(
			array(
				'id' => new external_value(PARAM_INT, 'ID of entry to delete')
			)
		);
	}

	public static function delete_entry_returns() {
		return null;
	}

	public static function delete_entry($id) {
		global $DB, $USER;

		// Parameter validation
		$params = self::validate_parameters(
			self::delete_entry_parameters(), array(
				'id' => $id
			)
		);

		if ($params['id'] < 1) {
			throw new invalid_parameter_exception('id must be a positive integer');
		}

		// Context validation
		$context = context_system::instance();
		self::validate_context($context);

        // Capability checking
		require_capability('moodle/blog:create', $context);

		return;
	}

	private static function __get_faculties() { 
		global $DB, $USER;

		$role = $DB->get_record('role', array('shortname' => 'student'), 'id', MUST_EXIST);
		
		$sql = 'SELECT DISTINCT cc.name'
			. ' FROM {user_enrolments} ue'
			. ' JOIN {enrol} e ON e.id = ue.enrolid'
			. ' JOIN {context} ct ON ct.instanceid = e.courseid'
			. ' JOIN {role_assignments} ra ON ra.contextid = ct.id'
			. ' JOIN {course} c ON c.id = e.courseid'
			. ' JOIN {course_categories} cc ON cc.id = c.category'
			. ' WHERE ue.userid = ?'
				. ' AND e.enrol = "database"'
				. ' AND ct.contextlevel = 50'
				. ' AND ra.userid = ue.userid'
				. ' AND ra.roleid = ?'
				. ' AND c.idnumber LIKE "%#%"';
		$categories = $DB->get_records_sql($sql, array($USER->id, $role->id));
		
		$faculties = array();
		foreach ($categories as $category) {
			switch ($category->name) {
				case 'BU':
					$faculties[] = 'BUS';
					break;
				case 'HL':
					$faculties[] = 'HLS';
					break;
				case 'HS':
					$faculties[] = 'HSS';
					break;
				case 'TD':
					$faculties[] = 'TDE';
					break;
				case 'BH':
					$faculties[] = 'BUS';
					$faculties[] = 'HSS';
					break;
				case 'BL':
					$faculties[] = 'BUS';
					$faculties[] = 'HLS';
					break;
				case 'BT':
					$faculties[] = 'BUS';
					$faculties[] = 'TDE';
					break;
				case 'HH':
					$faculties[] = 'HLS';
					$faculties[] = 'HSS';
					break;
				case 'HT':
					$faculties[] = 'HSS';
					$faculties[] = 'TDE';
					break;
				case 'LT':
					$faculties[] = 'HLS';
					$faculties[] = 'TDE';
					break;
			}
		}

		if (!empty($faculties)) {
			$faculties[] = 'UNI';
		}
		
		return $faculties;
	}

	private static function __count_submissions() { 
		global $DB, $USER;

		$sql = 'SELECT id, attribute_code '
			. 'FROM {local_brookes_edge_entries} '
			. 'WHERE author_id = ? '
			. '  AND submitted = 1 '
			. 'ORDER BY title';
		
		$submissions = $DB->get_records_sql($sql, array($USER->id));

		$entries = array();
		$attributes = array();
		foreach ($submissions as $submission) {
			$entries[] = array('id' => $submission->id);
			if (!in_array($submission->attribute_code, $attributes)) {
				$attributes[] = $submission->attribute_code;
			}
		}
		
		return array('entries' => count($entries), 'attributes' => count($attributes));
	}
}
