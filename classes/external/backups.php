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

namespace local_chronifyai\external;

use context_course;
use context_system;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use Exception;
use invalid_parameter_exception;
use local_chronifyai\service\restore_validator;
use local_chronifyai\task\backup_and_upload_task;
use moodle_exception;
use local_chronifyai\task\restore_course_task;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

/**
 * Backup external functions
 *
 * @package    local_chronifyai
 * @copyright  2025 SEBALE Innovations (http://sebale.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backups extends external_api {
    /**
     * Returns a description of method parameters for initiate_course_backup.
     *
     * @return external_function_parameters
     */
    public static function initiate_course_backup_parameters() {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course ID to backup'),
            'includeusers' => new external_value(PARAM_BOOL, 'Include user data in backup', VALUE_DEFAULT, true),
            'userid' => new external_value(PARAM_INT, 'External User ID performing the backup (optional, for notifications)', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Initiate a course backup process.
     *
     * This method validates the request, checks capabilities, validates the course exists,
     * and queues an adhoc task to perform the actual backup and upload.
     *
     * @param int $courseid Course ID to back-up
     * @param bool $includeusers Whether to include user data
     * @param int $userid External User ID performing the operation (0 => no notifications)
     * @return array Response with status and message
     * @throws invalid_parameter_exception
     * @throws moodle_exception
     */
    public static function initiate_course_backup(int $courseid, bool $includeusers = true, int $userid = 0) {
        global $DB, $USER;

        // Validate parameters.
        $params = self::validate_parameters(self::initiate_course_backup_parameters(), [
            'courseid' => $courseid,
            'includeusers' => $includeusers,
            'userid' => $userid,
        ]);

        // Validate system context for the API call.
        $systemcontext = context_system::instance();
        self::validate_context($systemcontext);

        // Check if the plugin is enabled.
        if (!get_config('local_chronifyai', 'enabled')) {
            return [
                'success' => false,
                'message' => get_string('status:plugin:disabled', 'local_chronifyai'),
                'taskid' => null,
            ];
        }

        // Validate course exists.
        $course = $DB->get_record('course', ['id' => $params['courseid']]);
        if (!$course) {
            return [
                'success' => false,
                'message' => get_string('coursenotfound', 'local_chronifyai'),
                'taskid' => null,
            ];
        }

        // Validate capabilities - both service capability and course backup capability.
        $coursecontext = context_course::instance($params['courseid']);

        try {
            // Check ChronifyAI service capability in system context.
            require_capability('local/chronifyai:useservice', $systemcontext);

            // Check backup capability in course context.
            require_capability('moodle/backup:backupcourse', $coursecontext);
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => get_string('error:permission:nopermission', 'local_chronifyai'),
                'taskid' => null,
            ];
        }

        try {
            // Create and queue the adhoc task.
            $task = new backup_and_upload_task();
            $task->set_custom_data([
                'courseid' => $params['courseid'],
                'includeusers' => $params['includeusers'],
                'externaluserid' => $params['userid'], // ChronifyAI app user ID.
                'coursename' => $course->fullname,
                'courseshortname' => $course->shortname,
            ]);

            // Set the user context for the task.
            $task->set_userid($USER->id);

            // Queue the task.
            $taskid = \core\task\manager::queue_adhoc_task($task);

            // Log the data transmission for audit purposes (GDPR/FERPA compliance).
            $logdata = sprintf(
                'Course backup initiated for course ID %d (%s). User data %s. ' .
                'Data will be transmitted to external ChronifyAI servers. Initiated by user ID %d.',
                $params['courseid'],
                $course->shortname,
                $params['includeusers'] ? 'INCLUDED' : 'EXCLUDED',
                $USER->id
            );
            
            // Use Moodle's standard logging for audit trail.
            $event = \core\event\course_backup_created::create([
                'objectid' => $params['courseid'],
                'context' => $coursecontext,
                'other' => [
                    'includeusers' => $params['includeusers'],
                    'destination' => 'ChronifyAI external service',
                    'taskid' => $taskid,
                ]
            ]);
            $event->trigger();

            // Return success response.
            return [
                'success' => true,
                'message' => get_string('status:backup:started', 'local_chronifyai'),
                'taskid' => $taskid, // This will be set after queuing.
            ];
        } catch (Exception $e) {
            // Log the error.
            debugging('Failed to queue backup task for course ' . $params['courseid'] . ': ' . $e->getMessage());

            return [
                'success' => false,
                'message' => get_string('error:backup:failed', 'local_chronifyai'),
                'taskid' => null,
            ];
        }
    }

    /**
     * Returns description of the method result value for initiate_course_backup.
     *
     * @return external_single_structure
     */
    public static function initiate_course_backup_returns() {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether the backup was successfully initiated'),
            'message' => new external_value(PARAM_TEXT, 'Status message'),
            'taskid' => new external_value(PARAM_INT, 'Adhoc task ID (if successful)', VALUE_OPTIONAL),
        ]);
    }

    /**
     * Describes the parameters for initiate_course_restore.
     *
     * @return external_function_parameters
     */
    public static function initiate_course_restore_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course ID to restore into (0 = create new course)'),
            'backupid' => new external_value(PARAM_INT, 'Backup ID on ChronifyAI to download and restore'),
            'options' => new external_value(PARAM_RAW, 'JSON string with restore options', VALUE_DEFAULT, '{}'),
            'userid' => new external_value(PARAM_INT, 'External User ID performing the restore (optional, for notifications)', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Queue an adhoc task to download a backup from ChronifyAI and restore it.
     *
     * @param int $courseid The course ID (0 = create new course, >0 = restore to existing)
     * @param int $backupid The ChronifyAI backup ID
     * @param string $options JSON string with restore options
     * @param int $userid External User ID performing the restore (0 => no notifications)
     * @return array
     * @throws moodle_exception
     */
    public static function initiate_course_restore($courseid, $backupid, $options = '{}', $userid = 0): array {
        global $USER;

        // Validate parameters.
        $params = self::validate_parameters(
            self::initiate_course_restore_parameters(),
            [
                'courseid' => $courseid,
                'backupid' => $backupid,
                'options' => $options,
                'userid' => $userid,
            ]
        );

        // Validate system context for the API call.
        $systemcontext = context_system::instance();
        self::validate_context($systemcontext);

        // Check if the plugin is enabled.
        if (!get_config('local_chronifyai', 'enabled')) {
            return [
                'success' => false,
                'message' => get_string('status:plugin:disabled', 'local_chronifyai'),
            ];
        }

        try {
            // Parse and validate JSON options using validator service.
            $validatedoptions = restore_validator::validate_options_json($params['options']);
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => get_string('error:validation:invalidrestoreoptions', 'local_chronifyai') . ': ' . $e->getMessage(),
            ];
        }

        // Determine restore type and validate accordingly.
        $isnewcourse = ($params['courseid'] === 0);

        try {
            if ($isnewcourse) {
                // Validate new course creation requirements.
                $validationresult = restore_validator::validate_new_course_requirements($validatedoptions, $systemcontext);
            } else {
                // Validate existing course restore requirements.
                $validationresult = restore_validator::validate_existing_course_requirements($params['courseid'], $systemcontext);
            }

            if (!$validationresult['success']) {
                return $validationresult;
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => get_string('error:permission:nopermission', 'local_chronifyai'),
            ];
        }

        // Log deprecation warning for userid parameter.
        if ($params['userid'] === 0) {
            debugging('userid parameter will become required in future versions', DEBUG_DEVELOPER);
        }

        try {
            // Create the adhoc task.
            $task = new restore_course_task();

            // Prepare task data.
            $taskdata = [
                'courseid' => $params['courseid'],
                'backupid' => $params['backupid'],
                'externaluserid' => $params['userid'], // ChronifyAI app user ID.
                'restoreoptions' => $validatedoptions, // Parsed and validated options.
                'isnewcourse' => $isnewcourse,
            ];

            // Add course info for existing course or target info for new course.
            if ($isnewcourse) {
                $taskdata['targetcategoryid'] = $validatedoptions['categoryid'];
                $taskdata['coursename'] = $validatedoptions['fullname'] ?: '';
                $taskdata['courseshortname'] = $validatedoptions['shortname'] ?: '';
            } else {
                $course = $validationresult['course'];
                $taskdata['coursename'] = $course->fullname;
                $taskdata['courseshortname'] = $course->shortname;
            }

            $task->set_custom_data((object) $taskdata);

            // Set the REAL Moodle user context for the task.
            $task->set_userid($USER->id);

            // Queue the adhoc task.
            \core\task\manager::queue_adhoc_task($task);

            // Return success response.
            return [
                'success' => true,
                'message' => get_string('status:restore:started', 'local_chronifyai'),
            ];
        } catch (Exception $e) {
            // Log the error.
            debugging('Failed to queue restore task: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => get_string('error:restore:failed', 'local_chronifyai'),
            ];
        }
    }

    /**
     * Describes the output of initiate_course_restore
     *
     * @return external_single_structure
     */
    public static function initiate_course_restore_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether the restore was successfully initiated'),
            'message' => new external_value(PARAM_TEXT, 'Status message'),
        ]);
    }
}
