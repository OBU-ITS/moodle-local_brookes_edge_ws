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

/**
 * BrookesEDGE web service - service functions
 * @package   local_brookes_edge_ws
 * @author    Peter Welham
 * @copyright 2020, Oxford Brookes University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Define the web service functions to install.
$functions = array(
        'local_brookes_edge_ws_get_attributes' => array(
                'classname'   => 'local_brookes_edge_ws_external',
                'methodname'  => 'get_attributes',
                'classpath'   => 'local/brookes_edge_ws/externallib.php',
                'description' => 'Returns an array of EDGE attributes sorted by name (attribute_name, attribute_code, attribute_entries_submitted).',
                'type'        => 'read',
				'capabilities'=> 'moodle/blog:create'
        ),
        'local_brookes_edge_ws_get_attribute' => array(
                'classname'   => 'local_brookes_edge_ws_external',
                'methodname'  => 'get_attribute',
                'classpath'   => 'local/brookes_edge_ws/externallib.php',
                'description' => 'Returns EDGE attribute details (attribute_name, attribute_description, attribute_entries_submitted, attribute_entries_not_submitted).  The attribute_name and attribute_code are passed in as parameters.',
                'type'        => 'read',
				'capabilities'=> 'moodle/blog:create'
        ),
        'local_brookes_edge_ws_get_all_activities' => array(
                'classname'   => 'local_brookes_edge_ws_external',
                'methodname'  => 'get_all_activities',
                'classpath'   => 'local/brookes_edge_ws/externallib.php',
                'description' => 'Returns an array of all (visible) activities sorted by name (activity_id, activity_name, activity_shortname, activity_faculty, activity_mnemonic, activity_attributes, activity_description).',
                'type'        => 'read',
				'capabilities'=> 'moodle/blog:create'
        ),
        'local_brookes_edge_ws_get_activities' => array(
                'classname'   => 'local_brookes_edge_ws_external',
                'methodname'  => 'get_activities',
                'classpath'   => 'local/brookes_edge_ws/externallib.php',
                'description' => 'Returns an array of activities that this user can view for a given attribute (or all attributes if none given) sorted by name (activity_id, activity_name, activity_joined). Any required attribute_code is passed in as a parameter.',
                'type'        => 'read',
				'capabilities'=> 'moodle/blog:create'
        ),
        'local_brookes_edge_ws_get_activity' => array(
                'classname'   => 'local_brookes_edge_ws_external',
                'methodname'  => 'get_activity',
                'classpath'   => 'local/brookes_edge_ws/externallib.php',
                'description' => 'Returns activity details (activity name, activity_description, activity_joined).  The activity_id is passed in as a parameter.',
                'type'        => 'read',
				'capabilities'=> 'moodle/blog:create'
        ),
        'local_brookes_edge_ws_get_activity_attributes' => array(
                'classname'   => 'local_brookes_edge_ws_external',
                'methodname'  => 'get_activity_attributes',
                'classpath'   => 'local/brookes_edge_ws/externallib.php',
                'description' => 'Returns activity attributes (attribute_code).  The activity_id is passed in as a parameter.',
                'type'        => 'read',
				'capabilities'=> 'moodle/blog:create'
        ),
        'local_brookes_edge_ws_join_activity' => array(
                'classname'   => 'local_brookes_edge_ws_external',
                'methodname'  => 'join_activity',
                'classpath'   => 'local/brookes_edge_ws/externallib.php',
                'description' => 'Enrolls the user on an activity.  The activity_id is passed in as a parameter.',
                'type'        => 'read',
				'capabilities'=> 'moodle/blog:create'
        ),
        'local_brookes_edge_ws_leave_activity' => array(
                'classname'   => 'local_brookes_edge_ws_external',
                'methodname'  => 'leave_activity',
                'classpath'   => 'local/brookes_edge_ws/externallib.php',
                'description' => 'Unenrolls the user from an activity.  The activity_id is passed in as a parameter.',
                'type'        => 'read',
				'capabilities'=> 'moodle/blog:create'
        ),
        'local_brookes_edge_ws_get_entries' => array(
                'classname'   => 'local_brookes_edge_ws_external',
                'methodname'  => 'get_entries',
                'classpath'   => 'local/brookes_edge_ws/externallib.php',
                'description' => 'Returns an array of user\'s entries for the given attribute sorted by title (id, title, submitted). The attribute_code (if any) is passed in as a parameter.',
                'type'        => 'read',
				'capabilities'=> 'moodle/blog:create'
        ),
        'local_brookes_edge_ws_get_entry' => array(
                'classname'   => 'local_brookes_edge_ws_external',
                'methodname'  => 'get_entry',
                'classpath'   => 'local/brookes_edge_ws/externallib.php',
                'description' => 'Returns user\'s entry with the given id (activity_id, activity_name, attribute_code, title, situation, task, action, result, link, submitted). The id is passed in as parameter.',
                'type'        => 'read',
				'capabilities'=> 'moodle/blog:create'
        ),
        'local_brookes_edge_ws_save_entry' => array(
                'classname'   => 'local_brookes_edge_ws_external',
                'methodname'  => 'save_entry',
                'classpath'   => 'local/brookes_edge_ws/externallib.php',
                'description' => 'Adds or amends a users\'s entry. The entry_id (if any), title, attribute, situation, task, action, result and link are passed in as parameters.  The entry_id is returned.',
                'type'        => 'write',
				'capabilities'=> 'moodle/blog:create'
        ),
		'local_brookes_edge_ws_submit_entry' => array(
                'classname'   => 'local_brookes_edge_ws_external',
                'methodname'  => 'submit_entry',
                'classpath'   => 'local/brookes_edge_ws/externallib.php',
                'description' => 'Submits a users\'s entry. The entry_id is passed in as a parameter.',
                'type'        => 'write',
				'capabilities'=> 'moodle/blog:create'
        ),
		'local_brookes_edge_ws_delete_entry' => array(
                'classname'   => 'local_brookes_edge_ws_external',
                'methodname'  => 'delete_entry',
                'classpath'   => 'local/brookes_edge_ws/externallib.php',
                'description' => 'Deletes a users\'s entry. The entry_id is passed in as a parameter.',
                'type'        => 'write',
				'capabilities'=> 'moodle/blog:create'
		)
);

// Define the services to install as pre-build services.
$services = array(
	'BrookesEDGE web service' => array(
		'shortname' => 'brookes_edge_ws',
		'functions' => array(
			'local_brookes_edge_ws_get_attributes',
			'local_brookes_edge_ws_get_attribute',
			'local_brookes_edge_ws_get_all_activities',
			'local_brookes_edge_ws_get_activities',
			'local_brookes_edge_ws_get_activity',
			'local_brookes_edge_ws_get_activity_attributes',
			'local_brookes_edge_ws_join_activity',
			'local_brookes_edge_ws_leave_activity',
			'local_brookes_edge_ws_get_entries',
			'local_brookes_edge_ws_get_entry',
			'local_brookes_edge_ws_save_entry',
			'local_brookes_edge_ws_submit_entry',
			'local_brookes_edge_ws_delete_entry'
		),
		'restrictedusers' => 0,
		'enabled' => 1
	)
);
