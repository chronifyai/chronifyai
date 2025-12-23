<?php
// This file is part of Moodle - http://moodle.org/
//
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

namespace local_chronifyai\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;

/**
 * Privacy provider for local_chronifyai.
 *
 * This plugin stores API credentials in site configuration and may share
 * course data with the ChronifyAI external service.
 *
 * @package    local_chronifyai
 * @copyright  2025 SEBALE Innovations (http://sebale.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider {
    /**
     * Get the list of metadata about data stored or transmitted by this plugin.
     *
     * @param collection $collection The collection to add metadata to
     * @return collection The updated collection
     */
    public static function get_metadata(collection $collection): collection {
        // Plugin configuration stored in {config_plugins} table.
        $collection->add_database_table(
            'config_plugins',
            [
                'plugin' => 'privacy:metadata:configplugins:plugin',
                'name' => 'privacy:metadata:configplugins:name',
                'value' => 'privacy:metadata:configplugins:value',
            ],
            'privacy:metadata:configplugins'
        );

        // Data shared with external ChronifyAI service.
        $collection->add_external_location_link(
            'chronifyai_service',
            [
                // Course Information.
                'courseid' => 'privacy:metadata:chronifyaiservice:courseid',
                'coursename' => 'privacy:metadata:chronifyaiservice:coursename',
                'courseshortname' => 'privacy:metadata:chronifyaiservice:courseshortname',
                'coursecategory' => 'privacy:metadata:chronifyaiservice:coursecategory',

                // User Information (when backups include users).
                'userid' => 'privacy:metadata:chronifyaiservice:userid',
                'username' => 'privacy:metadata:chronifyaiservice:username',
                'useremail' => 'privacy:metadata:chronifyaiservice:useremail',
                'userfirstname' => 'privacy:metadata:chronifyaiservice:userfirstname',
                'userlastname' => 'privacy:metadata:chronifyaiservice:userlastname',

                // Course Content (in backups).
                'activities' => 'privacy:metadata:chronifyaiservice:activities',
                'assignments' => 'privacy:metadata:chronifyaiservice:assignments',
                'quizzes' => 'privacy:metadata:chronifyaiservice:quizzes',
                'forums' => 'privacy:metadata:chronifyaiservice:forums',
                'files' => 'privacy:metadata:chronifyaiservice:files',

                // Student Data (when included in backups).
                'grades' => 'privacy:metadata:chronifyaiservice:grades',
                'submissions' => 'privacy:metadata:chronifyaiservice:submissions',
                'quizattempts' => 'privacy:metadata:chronifyaiservice:quizattempts',
                'forumposts' => 'privacy:metadata:chronifyaiservice:forumposts',
                'completiondata' => 'privacy:metadata:chronifyaiservice:completiondata',

                // Transcript Data.
                'transcriptgrades' => 'privacy:metadata:chronifyaiservice:transcriptgrades',
                'coursecompletions' => 'privacy:metadata:chronifyaiservice:coursecompletions',
                'certificatedata' => 'privacy:metadata:chronifyaiservice:certificatedata',
            ],
            'privacy:metadata:chronifyaiservice'
        );

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid The user to search
     * @return contextlist The contextlist containing the list of contexts
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        // This plugin does not store user-specific data in its own tables.
        return new contextlist();
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts to export
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        // This plugin does not store personal user data.
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context The context to delete data for
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        // This plugin does not store personal user data in Moodle.
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        // This plugin does not store personal user data in Moodle.
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param userlist $userlist The userlist containing the list of users
     */
    public static function get_users_in_context(userlist $userlist) {
        // This plugin does not store personal user data in Moodle.
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        // This plugin does not store personal user data in Moodle.
    }
}
