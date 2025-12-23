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

namespace local_chronifyai\task;

use core\task\adhoc_task;
use Exception;
use local_chronifyai\api\client;
use local_chronifyai\api\endpoints;
use local_chronifyai\service\course_report;
use moodle_exception;

/**
 * Adhoc task for generating course reports and uploading them to ChronifyAI
 *
 * @package    local_chronifyai
 * @copyright  2025 ChronifyAI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class generate_and_upload_report_task extends adhoc_task {
    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('task:generateanduploadreport', 'local_chronifyai');
    }

    /**
     * Execute the task.
     *
     * This method generates a course report and uploads it to ChronifyAI.
     */
    public function execute() {
        global $DB;

        // Get task data.
        $data = $this->get_custom_data();
        $courseid = $data->courseid;
        $appcourseid = $data->appcourseid;
        $backupid = $data->backupid;
        $userid = $data->userid;
        $coursename = $data->coursename;
        $courseshortname = $data->courseshortname;

        // Log task start.
        mtrace("Starting report generation task for course ID: {$courseid} ({$coursename})");

        $tempfilepath = null;

        try {
            // Validate course still exists.
            $course = $DB->get_record('course', ['id' => $courseid]);
            if (!$course) {
                throw new moodle_exception('coursenotfound', 'local_chronifyai');
            }

            // Create report service instance.
            $reportservice = new course_report();

            // Generate course report data.
            $reportdata = $reportservice->generate_report($courseid, $appcourseid, $backupid);

            mtrace("Report data generated successfully");

            // Save report to temp file.
            $tempfilepath = $this->save_report_to_temp($reportdata, $courseid, $courseshortname);

            mtrace("Report file created: " . basename($tempfilepath));
            mtrace("File size: " . $this->format_file_size(filesize($tempfilepath)));

            // Set report data for upload.
            $reportdata = [
                'course_id' => $appcourseid,
                'backup_id' => $backupid,
            ];

            // Upload to ChronifyAI.
            $this->upload_to_chronifyai($tempfilepath, reportdata: $reportdata);

            // Clean up temp file.
            if (file_exists($tempfilepath)) {
                unlink($tempfilepath);
                mtrace("Temporary report file cleaned up");
            }

            mtrace("Report generation and upload task completed successfully for course: {$coursename}");
        } catch (Exception $e) {
            // Log the error.
            $error = "Report generation task failed for course ID {$courseid}: " . $e->getMessage();
            mtrace($error);
            debugging($error, DEBUG_DEVELOPER);

            // Clean up on error.
            if ($tempfilepath && file_exists($tempfilepath)) {
                unlink($tempfilepath);
                mtrace("Temporary file cleaned up after error");
            }

            // Re-throw the exception to mark the task as failed.
            throw $e;
        }
    }

    /**
     * Save report data to temporary file.
     *
     * @param array $reportdata The report data
     * @param int $courseid Course ID
     * @param string $courseshortname Course short name
     * @return string Path to temporary file
     * @throws moodle_exception
     */
    private function save_report_to_temp($reportdata, $courseid, $courseshortname) {
        global $CFG;

        // Create a unique filename.
        $timestamp = date('Y-m-d_H-i-s');
        $safeshortname = preg_replace('/[^a-zA-Z0-9_-]/', '_', $courseshortname);
        $filename = "chronifyai_report_{$courseid}_{$safeshortname}_{$timestamp}.json";

        // Use Moodle's temp directory.
        $tempdir = $CFG->tempdir . '/chronifyai';
        if (!is_dir($tempdir)) {
            if (!make_temp_directory('chronifyai')) {
                throw new moodle_exception('error:backup:cannotcreatetempdirectory', 'local_chronifyai');
            }
        }

        $tempfilepath = $tempdir . '/' . $filename;

        // Save JSON data to file.
        $jsondata = json_encode($reportdata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if (file_put_contents($tempfilepath, $jsondata) === false) {
            throw new moodle_exception('error:report:cannotcreatefile', 'local_chronifyai');
        }

        return $tempfilepath;
    }

    /**
     * Upload report to ChronifyAI.
     *
     * @param string $filepath Path to report file
     * @param array $reportdata The report data (for JSON payload)
     * @throws moodle_exception
     */
    private function upload_to_chronifyai($filepath, $reportdata) {
        try {
            mtrace("Uploading report to ChronifyAI...");

            // Upload a report file using multipart form data instead of JSON payload.
            $attachments = [
                'report_file' => [
                    'path' => $filepath,
                    'content_type' => 'application/json',
                    'filename' => 'attachment_' . time() . '.json',
                ],
            ];
            $response = client::create_report(reportdata: $reportdata, attachments: $attachments);

            // The response handler already validated the HTTP status code.
            // If we get here, the request was successful (200, 201, 202, etc.).
            if ($response->success) {
                mtrace("Report successfully uploaded to ChronifyAI");

                // Log response details.
                $statusmsg = "HTTP {$response->status_code}";
                if (isset($response->message)) {
                    $statusmsg .= ": {$response->message}";
                }
                if (isset($response->id)) {
                    $statusmsg .= " (Report ID: {$response->id})";
                }

                mtrace($statusmsg);
                mtrace("Full response: " . json_encode($response->data));
            } else {
                // Handle unexpected response format (defensive programming).
                // The response_handler should ensure proper format, but we check for safety.
                throw new moodle_exception('error:report:uploadfailed', 'local_chronifyai', '', null, 'Unexpected response format');
            }
        } catch (moodle_exception $e) {
            // Re-throw moodle_exceptions without wrapping them.
            throw $e;
        } catch (Exception $e) {
            // Only catch and wrap unexpected exceptions.
            $error = 'Unexpected error during report upload: ' . $e->getMessage();
            mtrace($error);
            debugging($error, DEBUG_DEVELOPER);
            throw new moodle_exception('error:report:uploadfailed', 'local_chronifyai', '', null, $error);
        }
    }

    /**
     * Format file size in human readable format.
     *
     * @param int $size Size in bytes
     * @return string Formatted size
     */
    private function format_file_size($size) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitindex = 0;

        while ($size >= 1024 && $unitindex < count($units) - 1) {
            $size /= 1024;
            $unitindex++;
        }

        return round($size, 2) . ' ' . $units[$unitindex];
    }
}
