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

use context_system;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use Exception;
use invalid_parameter_exception;
use moodle_exception;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

/**
 * Users external functions
 *
 * @package    local_chronifyai
 * @copyright  2025 SEBALE Innovations (http://sebale.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class users extends external_api {
    /**
     * Returns a description of method parameters for initiate_transcripts_export.
     *
     * @return external_function_parameters
     */
    public static function initiate_transcripts_export_parameters() {
        return new external_function_parameters([
            'externaluserid' => new external_value(
                PARAM_INT,
                'External User ID performing the export transcripts (for notifications)'
            ),
        ]);
    }

    /**
     * Initiate a transcript export process.
     *
     * This method validates the request, checks capabilities, and queues an adhoc task
     * to perform the actual transcript export to ChronifyAI.
     *
     * @param int $externaluserid External User ID performing the export transcripts.
     * @return array Response with status and message.
     * @throws invalid_parameter_exception
     * @throws moodle_exception
     */
    public static function initiate_transcripts_export($externaluserid) {
        global $USER;

        // Validate parameters.
        $params = self::validate_parameters(self::initiate_transcripts_export_parameters(), [
            'externaluserid' => $externaluserid,
        ]);

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
            // Check ChronifyAI service capability in the system context.
            require_capability('local/chronifyai:useservice', $systemcontext);
            // Check if user can view user details - required for accessing transcript data.
            // Using system context as we're exporting transcripts for multiple users.
            require_capability('moodle/user:viewdetails', $systemcontext);
            require_capability('moodle/grade:view', $systemcontext);
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => get_string('error:permission:nopermission', 'local_chronifyai'),
            ];
        }

        try {
            // Create and queue the adhoc task.
            $task = new \local_chronifyai\task\export_transcripts_task();
            $task->set_custom_data([
                'externaluserid' => $params['externaluserid'], // ChronifyAI app user ID.
            ]);

            // Set the user context for the task.
            $task->set_userid($USER->id);

            // Queue the task.
            \core\task\manager::queue_adhoc_task($task);

            // Return success response.
            return [
                'success' => true,
                'message' => get_string('transcripts:export:queued', 'local_chronifyai'),
            ];
        } catch (Exception $e) {
            // Log the error.
            debugging('Failed to queue transcripts export task: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => get_string('error:transcripts:export:failed', 'local_chronifyai'),
            ];
        }
    }

    /**
     * Returns description of the method result value for initiate_transcripts_export.
     *
     * @return external_single_structure
     */
    public static function initiate_transcripts_export_returns() {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether the export was successfully initiated'),
            'message' => new external_value(PARAM_TEXT, 'Status message'),
        ]);
    }
}
