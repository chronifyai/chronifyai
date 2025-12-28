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
use local_chronifyai\local\api\client;
use local_chronifyai\local\service\course_backup;
use local_chronifyai\local\service\notification;
use moodle_exception;

/**
 * Adhoc task for creating course backups and uploading them to ChronifyAI
 *
 * @package    local_chronifyai
 * @copyright  2025 SEBALE Innovations (http://sebale.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_and_upload_task extends adhoc_task {
    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('task:backupandupload', 'local_chronifyai');
    }

    /**
     * Execute the task.
     *
     * This method creates a course backup and uploads it to ChronifyAI.
     */
    public function execute() {
        global $DB;

        // Get task data.
        $data = $this->get_custom_data();
        $courseid = $data->courseid;
        $includeusers = $data->includeusers;
        $externaluserid = $data->externaluserid; // ChronifyAI app user ID.
        $userid = $this->get_userid(); // Get the Moodle user ID who created this task.
        $coursename = $data->coursename;
        $courseshortname = $data->courseshortname;

        // Log task start.
        mtrace("Starting backup and upload task for course ID: {$courseid} ({$coursename})");

        $tempfilepath = null;

        try {
            // Validate course still exists.
            $course = $DB->get_record('course', ['id' => $courseid]);
            if (!$course) {
                throw new moodle_exception('coursenotfound', 'local_chronifyai');
            }

            // Create backup service instance.
            $backupservice = new course_backup();

            // Create and execute backup, return stored file.
            $backupfile = $backupservice->create_backup_for_upload($courseid, $includeusers, $userid);

            // Copy backup to temp directory for upload.
            $tempfilepath = $this->copy_backup_to_temp($backupfile, $courseid, $courseshortname);

            mtrace("Backup file created: " . basename($tempfilepath));
            mtrace("File size: " . $this->format_file_size(filesize($tempfilepath)));

            // Upload to ChronifyAI.
            $this->upload_to_chronifyai($tempfilepath);

            // Clean up temp file.
            if (file_exists($tempfilepath)) {
                unlink($tempfilepath);
                mtrace('Temporary backup file cleaned up');
            }

            // Delete the stored backup file.
            $backupfile->delete();
            mtrace('Stored backup file cleaned up');

            // Send notification that course backup is finished.
            notification::send_course_backup_completed($externaluserid, $course->fullname);

            mtrace("Backup and upload task completed successfully for course: {$coursename}");
        } catch (Exception $e) {
            // Log the error.
            $error = "Backup and upload task failed for course ID {$courseid}: " . $e->getMessage();
            mtrace($error);
            debugging($error, DEBUG_DEVELOPER);

            // Clean up on error.
            if ($tempfilepath && file_exists($tempfilepath)) {
                unlink($tempfilepath);
                mtrace('Temporary file cleaned up after error');
            }

            // Re-throw the exception to mark the task as failed.
            throw $e;
        }
    }

    /**
     * Copy backup file to temporary directory for upload.
     *
     * @param \stored_file $backupfile The stored backup file
     * @param int $courseid Course ID
     * @param string $courseshortname Course short name
     * @return string Path to temporary file
     * @throws moodle_exception
     */
    private function copy_backup_to_temp($backupfile, $courseid, $courseshortname) {
        global $CFG;

        // Create a unique filename.
        $timestamp = date('Y-m-d_H-i-s');
        $safeshortname = preg_replace('/[^a-zA-Z0-9_-]/', '_', $courseshortname);
        $filename = "chronifyai_backup_{$courseid}_{$safeshortname}_{$timestamp}.mbz";

        // Use Moodle's temp directory.
        $tempdir = $CFG->tempdir . '/chronifyai';
        if (!is_dir($tempdir)) {
            if (!make_temp_directory('chronifyai')) {
                throw new moodle_exception('error:backup:cannotcreatetempdirectory', 'local_chronifyai');
            }
        }

        $tempfilepath = $tempdir . '/' . $filename;

        // Copy the file content to the temp directory.
        if (!$backupfile->copy_content_to($tempfilepath)) {
            throw new moodle_exception('error:backup:cannotcopyfile', 'local_chronifyai');
        }

        return $tempfilepath;
    }

    /**
     * Upload backup file to ChronifyAI.
     *
     * @param string $filepath Path to backup file
     * @throws moodle_exception
     */
    private function upload_to_chronifyai($filepath) {
        try {
            mtrace("Uploading backup to ChronifyAI...");

            // Use the existing client upload method.
            $response = client::upload_backup($filepath);

            mtrace("Backup successfully uploaded to ChronifyAI");
            mtrace("Response: " . json_encode($response));
        } catch (Exception $e) {
            // Log the original error message without adding prefixes.
            mtrace("Upload failed: " . $e->getMessage());
            debugging("Backup upload error: " . $e->getMessage(), DEBUG_DEVELOPER);

            // Throw clean error.
            throw new moodle_exception('error:backup:uploadfailed', 'local_chronifyai', '', null, $e->getMessage());
        }
    }

    /**
     * Format file size in human-readable format.
     *
     * @param int $size File size in bytes
     * @return string Formatted size
     */
    private function format_file_size($size) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $power = $size > 0 ? floor(log($size, 1024)) : 0;
        return number_format($size / pow(1024, $power), 2) . ' ' . $units[$power];
    }

    /**
     * Get the default concurrency limit for this task.
     *
     * @return int
     */
    protected function get_default_concurrency_limit(): int {
        // Limit concurrent backup tasks to prevent resource exhaustion.
        return 1;
    }
}
