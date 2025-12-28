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
use local_chronifyai\local\api\api_helper;
use local_chronifyai\task\generate_and_upload_report_task;
use moodle_exception;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

/**
 * Reports external functions
 *
 * @package    local_chronifyai
 * @copyright  2025 ChronifyAI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class reports extends external_api {
    /**
     * Returns a description of method parameters for initiate_course_report.
     *
     * @return external_function_parameters
     */
    public static function initiate_course_report_parameters() {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'LMS Course ID to generate report for'),
            'appcourseid' => new external_value(PARAM_INT, 'APP course ID (returned back in response)'),
            'backupid' => new external_value(PARAM_RAW, 'APP backup ID (optional, returned back in response)', VALUE_DEFAULT, null),
        ]);
    }

    /**
     * Initiate a course report generation process.
     *
     * This method validates the request, checks capabilities, validates the course exists,
     * and queues an adhoc task to perform the actual report generation and upload.
     *
     * @param int $courseid LMS Course ID to generate report for
     * @param int $appcourseid APP course ID
     * @param int $backupid APP backup ID
     * @return array Response with status and message
     * @throws invalid_parameter_exception
     * @throws moodle_exception
     */
    public static function initiate_course_report($courseid, $appcourseid, $backupid = null) {
        global $DB, $USER;

        // Validate parameters.
        $params = self::validate_parameters(self::initiate_course_report_parameters(), [
            'courseid' => $courseid,
            'appcourseid' => $appcourseid,
            'backupid' => $backupid,
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
                'courseid' => $params['appcourseid'],
                'backupid' => $params['backupid'],
            ];
        }

        // Validate course exists.
        $course = $DB->get_record('course', ['id' => $params['courseid']]);
        if (!$course) {
            return [
                'success' => false,
                'message' => get_string('coursenotfound', 'local_chronifyai'),
                'taskid' => null,
                'courseid' => $params['appcourseid'],
                'backupid' => $params['backupid'],
            ];
        }

        // Site course is not supported.
        if ($params['courseid'] == 1) {
            return [
                'success' => false,
                'message' => get_string('error:course:notsupported', 'local_chronifyai'),
                'taskid' => null,
                'courseid' => $params['appcourseid'],
                'backupid' => $params['backupid'],
            ];
        }

        // Validate capabilities - both service capability and course view capability.
        $coursecontext = context_course::instance($params['courseid']);

        try {
            // Check ChronifyAI service capability in system context.
            require_capability('local/chronifyai:useservice', $systemcontext);

            // Check course view capability in course context.
            require_capability('moodle/course:view', $coursecontext);

            // Check if user can view participants (required for report generation).
            require_capability('moodle/course:viewparticipants', $coursecontext);
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => get_string('error:permission:nopermission', 'local_chronifyai'),
                'taskid' => null,
                'courseid' => $params['appcourseid'],
                'backupid' => $params['backupid'],
            ];
        }

        try {
            // Create and queue the adhoc task.
            $task = new generate_and_upload_report_task();
            $task->set_custom_data([
                'courseid' => $params['courseid'],
                'appcourseid' => $params['appcourseid'],
                'backupid' => $params['backupid'],
                'userid' => $USER->id,
                'coursename' => $course->fullname,
                'courseshortname' => $course->shortname,
            ]);

            // Set the user context for the task.
            $task->set_userid($USER->id);

            // Queue the task.
            $taskid = \core\task\manager::queue_adhoc_task($task);

            // Return success response.
            return [
                'success' => true,
                'message' => get_string('status:report:started', 'local_chronifyai'),
                'taskid' => $taskid, // This will be set after queuing.
                'courseid' => $params['appcourseid'],
                'backupid' => $params['backupid'],
            ];
        } catch (Exception $e) {
            // Log the error.
            debugging('Failed to queue report task for course ' . $params['courseid'] . ': ' . $e->getMessage());

            return [
                'success' => false,
                'message' => get_string('error:report:failed', 'local_chronifyai'),
                'taskid' => null,
                'courseid' => $params['appcourseid'],
                'backupid' => $params['backupid'],
            ];
        }
    }

    /**
     * Returns description of the method result value for initiate_course_report.
     *
     * @return external_single_structure
     */
    public static function initiate_course_report_returns() {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether the report was successfully initiated'),
            'message' => new external_value(PARAM_TEXT, 'Status message'),
            'taskid' => new external_value(PARAM_INT, 'Adhoc task ID (if successful)', VALUE_OPTIONAL),
            'courseid' => new external_value(PARAM_INT, 'APP course ID (returned back)'),
            'backupid' => new external_value(PARAM_RAW, 'APP backup ID (returned back)', VALUE_DEFAULT, null),
        ]);
    }
}
