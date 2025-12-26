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

use coding_exception;
use core\task\adhoc_task;
use core\lock\lock_config;
use local_chronifyai\api\client;
use local_chronifyai\service\course_restore;
use local_chronifyai\service\notification;
use local_chronifyai\service\restore_data_preparer;
use moodle_exception;

/**
 * Adhoc task for restoring courses from ChronifyAI backups
 *
 * @package    local_chronifyai
 * @copyright  2025 SEBALE Innovations (http://sebale.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_course_task extends adhoc_task {
    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     * @throws coding_exception
     */
    public function get_name() {
        return get_string('task:restorecourse', 'local_chronifyai');
    }

    /**
     * Execute the task.
     *
     * This method downloads a course backup from ChronifyAI and restores it to either
     * an existing course or creates a new course based on the task configuration.
     */
    public function execute() {
        global $DB;

        // Get task data.
        $data = $this->get_custom_data();

        // Validate required fields exist.
        if (!isset($data->courseid)) {
            throw new \moodle_exception('error:restore:missingcourseid', 'local_chronifyai');
        }
        if (!isset($data->backupid)) {
            throw new \moodle_exception('error:restore:missingbackupid', 'local_chronifyai');
        }
        if (!isset($data->externaluserid)) {
            throw new \moodle_exception('error:restore:missingexternaluserid', 'local_chronifyai');
        }

        $courseid = $data->courseid;
        $backupid = $data->backupid;
        $externaluserid = (int) $data->externaluserid; // ChronifyAI app user ID - cast to int.
        $userid = $this->get_userid(); // Get the Moodle user ID who created this task.
        $isnewcourse = $data->isnewcourse ?? false;
        $rawrestoreoptions = (array) ($data->restoreoptions ?? []);

        // Extract course info for logging.
        $coursename = $data->coursename ?? '';
        $courseshortname = $data->courseshortname ?? '';

        // Log task start with details.
        if ($isnewcourse) {
            mtrace("Starting restore task to CREATE NEW COURSE");
            mtrace("Target category ID: {$data->targetcategoryid}");
        } else {
            mtrace("Starting restore task for EXISTING course ID: {$courseid}");
        }

        if ($coursename && $courseshortname) {
            mtrace("Course: {$coursename} ({$courseshortname})");
        }
        mtrace("Using backup ID: {$backupid} from ChronifyAI");

        // Determine lock key based on restore type.
        $lockkey = $isnewcourse
            ? 'course_restore_new_' . $data->targetcategoryid . '_' . $userid
            : 'course_restore_existing_' . $courseid;

        // Get a lock to prevent concurrent operations.
        $lockfactory = lock_config::get_lock_factory('course_restore_adhoc');
        $tempfilepath = null;
        $finalcourseid = null;

        try {
            // For existing course restore, validate course still exists.
            if (!$isnewcourse) {
                $course = $DB->get_record('course', ['id' => $courseid]);
                if (!$course) {
                    throw new moodle_exception('coursenotfound', 'local_chronifyai');
                }
                mtrace("Found target course: {$course->fullname}");
            }

            // Try to get a lock.
            if (!$lock = $lockfactory->get_lock($lockkey, 10)) {
                mtrace("Another restore operation is already running for this target");
                return;
            }
            mtrace('Obtained lock for restore operation');

            // Create a temp directory for downloaded backup.
            $tempdir = make_temp_directory('chronifyai');
            $tempfilepath = $tempdir . '/backup_' . $backupid . '.mbz';
            mtrace('Created temporary directory for backup file');

            // Download the backup file.
            mtrace('Downloading backup file from ChronifyAI...');
            $success = client::download_backup($backupid, $tempfilepath);
            if (!$success) {
                throw new moodle_exception('error:backup:downloadfailed', 'local_chronifyai', '', $backupid);
            }

            // Log download success and file details.
            mtrace('Backup file downloaded successfully');
            mtrace('File size: ' . display_size(filesize($tempfilepath)));

            // Prepare and validate options using data preparer.
            mtrace('Preparing and validating restore options...');
            $preparedoptions = restore_data_preparer::prepare_course_options($rawrestoreoptions, $isnewcourse);

            // Initialize restore service.
            mtrace('Initializing restore process...');
            $restoreservice = new course_restore();

            // Perform the restore based on type.
            if ($isnewcourse) {
                $finalcourseid = $restoreservice->restore_course_to_new_course($tempfilepath, $preparedoptions, $userid);
                mtrace("New course created with ID: {$finalcourseid}");
            } else {
                $finalcourseid = $courseid;
                $overrideoptions = restore_data_preparer::prepare_override_options($preparedoptions);
                $restoreservice->restore_course_replace_existing($tempfilepath, $courseid, $userid, $overrideoptions);
                mtrace("Existing course updated: ID {$finalcourseid}");
            }

            // Clean up temporary file.
            if (file_exists($tempfilepath)) {
                unlink($tempfilepath);
                mtrace('Temporary backup file cleaned up');
            }

            // Get final course details for notification.
            $finalcourse = $DB->get_record('course', ['id' => $finalcourseid]);
            $finalcoursename = $finalcourse ? $finalcourse->fullname : $coursename;

            // Send notification that course restore is finished.
            notification::send_course_restore_completed($finalcourseid, $externaluserid, $finalcoursename);

            mtrace("Restore completed successfully for course: {$finalcoursename}");
        } catch (\Exception $e) {
            // Log the error with context.
            $context = $isnewcourse ? 'new course creation' : "course {$courseid}";
            $error = "Restore task failed for {$context}: " . $e->getMessage();
            mtrace($error);
            debugging($error, DEBUG_DEVELOPER);

            // Clean up on error.
            if ($tempfilepath && file_exists($tempfilepath)) {
                unlink($tempfilepath);
                mtrace('Temporary file cleaned up after error');
            }

            // Send notification that course restore is failed.
            notification::send_course_restore_failed($externaluserid, $coursename);

            // Re-throw to mark a task as failed.
            throw $e;
        } finally {
            // Always release the lock if we obtained it.
            if (isset($lock)) {
                $lock->release();
                mtrace('Course restore lock released');
            }
        }
    }
}
