<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace local_chronifyai\service;

defined('MOODLE_INTERNAL') || die;

global $CFG;
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
require_once($CFG->dirroot . '/course/lib.php');

use backup;
use restore_controller;
use Exception;
use moodle_exception;
use stdClass;

/**
 * Course Restore Service
 *
 * @package    local_chronifyai
 * @copyright  2025 SEBALE Innovations (http://sebale.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_restore {
    /**
     * Restores a course backup to an existing course, replacing all existing content.
     *
     * This method extracts the backup file and restores it to the specified course,
     * completely replacing the existing course content with the backup content.
     * Uses database transactions to ensure data integrity.
     *
     * @param string $backupfilepath Path to the backup file (.mbz).
     * @param int $courseid Existing course ID to restore into.
     * @param int $userid User ID performing the restore (REQUIRED - no default to admin).
     * @param array $overrideoptions Optional parameters to override backup settings (must be pre-validated and prepared)
     * @return void
     * @throws moodle_exception If an error occurs during the restore process.
     */
    public function restore_course_replace_existing($backupfilepath, $courseid, $userid, $overrideoptions = []) {
        global $CFG, $DB;

        // Validate inputs using centralized validator.
        restore_validator::validate_restore_inputs($backupfilepath, $userid, $courseid);

        // Turn off file logging to avoid file locking issues.
        $originallogginglevel = isset($CFG->backup_file_logger_level) ? $CFG->backup_file_logger_level : null;
        $CFG->backup_file_logger_level = backup::LOG_NONE;

        $backupid = null;
        $transaction = null;

        try {
            // Start database transaction to ensure data integrity during restore.
            $transaction = $DB->start_delegated_transaction();

            // Extract backup file and get backup ID.
            $backupid = $this->extract_backup_file($backupfilepath, $courseid, $userid);

            // Create restore controller for existing course (replacing content).
            $rc = $this->create_restore_controller($backupid, $courseid, $userid, backup::TARGET_CURRENT_DELETING);

            // Configure restore settings with same defaults as backup.
            $this->configure_restore_settings($rc);

            // Execute restore with proper error handling.
            $this->execute_restore($rc);

            // Apply parameter overrides after restore (data is already validated and prepared).
            if (!empty($overrideoptions)) {
                $this->apply_course_overrides($courseid, $overrideoptions);
            }

            // Commit transaction if everything succeeded.
            $transaction->allow_commit();
        } catch (Exception $e) {
            // Rollback transaction on any error.
            if ($transaction && !$transaction->is_disposed()) {
                $transaction->rollback($e);
            }
            throw new moodle_exception('error:restore:error', 'local_chronifyai', '', $e->getMessage());
        } finally {
            // Clean up temporary files.
            if ($backupid !== null) {
                $this->cleanup_backup_temp_files($backupid);
            }

            // Restore the original logging level.
            if ($originallogginglevel !== null) {
                $CFG->backup_file_logger_level = $originallogginglevel;
            } else {
                unset($CFG->backup_file_logger_level);
            }
        }
    }

    /**
     * Restores a course backup to a new course.
     *
     * This method creates a new course first with temporary names, restores the backup content,
     * then updates the names based on backup content and user preferences.
     *
     * @param string $backupfilepath Path to the backup file (.mbz).
     * @param array $courseoptions Course creation options (must be pre-validated and prepared)
     * @param int $userid User ID performing the restore (REQUIRED).
     * @return int The newly created course ID
     * @throws moodle_exception If an error occurs during the restore process.
     */
    public function restore_course_to_new_course($backupfilepath, $courseoptions, $userid): int {
        global $CFG;

        // Validate basic inputs using a centralized validator.
        restore_validator::validate_restore_inputs($backupfilepath, $userid);

        // Turn off file logging to avoid file-locking issues.
        $originallogginglevel = $CFG->backup_file_logger_level ?? null;
        $CFG->backup_file_logger_level = backup::LOG_NONE;

        $backupid = null;
        $newcourseid = null;

        try {
            // Create a course with guaranteed unique temporary names.
            $tempcourseoptions = restore_data_preparer::prepare_temp_course_options($courseoptions);
            $newcourseid = $this->create_new_course($tempcourseoptions); // No transaction - create_course doesn't respect them.

            // Extract backup and restore content.
            $backupid = $this->extract_backup_file($backupfilepath, $newcourseid, $userid);
            $rc = $this->create_restore_controller($backupid, $newcourseid, $userid, backup::TARGET_NEW_COURSE);
            $this->configure_restore_settings($rc);
            $this->execute_restore($rc);

            // Update course names - only if user didn't provide both names.
            if (empty($courseoptions['fullname']) || empty($courseoptions['shortname'])) {
                $this->update_course_names_from_backup($rc, $newcourseid, $courseoptions);
            }

            // Apply parameter overrides after restore (these override backup settings).
            $overrideoptions = restore_data_preparer::prepare_override_options($courseoptions);
            if (!empty($overrideoptions)) {
                $this->apply_course_overrides($newcourseid, $overrideoptions);
            }

            return $newcourseid;
        } catch (Exception $e) {
            // If we created a course but restore failed, try to delete it.
            if ($newcourseid !== null) {
                try {
                    delete_course($newcourseid, false);
                } catch (Exception $deleteerror) {
                    debugging('Failed to cleanup course after restore failure: ' . $deleteerror->getMessage(), DEBUG_DEVELOPER);
                }
            }

            throw new moodle_exception('error:restore:error', 'local_chronifyai', '', $e->getMessage());
        } finally {
            // Clean up temporary files.
            if ($backupid !== null) {
                $this->cleanup_backup_temp_files($backupid);
            }

            // Restore the original logging level.
            if ($originallogginglevel !== null) {
                $CFG->backup_file_logger_level = $originallogginglevel;
            } else {
                unset($CFG->backup_file_logger_level);
            }
        }
    }

    /**
     * Update course names using backup content and user preferences.
     *
     * Only called when user hasn't provided both fullname and shortname.
     *
     * @param restore_controller $rc Restore controller (to get backup info)
     * @param int $courseid Course ID to update
     * @param array $courseoptions Original user course options
     */
    private function update_course_names_from_backup($rc, $courseid, $courseoptions) {
        global $DB;

        try {
            $backupinfo = $rc->get_info();

            // Priority: user override → backup original → keep temp names.
            $finalfullname = $courseoptions['fullname']
                ?? $backupinfo->original_course_fullname
                ?? null;

            $finalshortname = $courseoptions['shortname']
                ?? $backupinfo->original_course_shortname
                ?? null;

            // Only update if we have meaningful names.
            if ($finalfullname || $finalshortname) {
                if (!$finalfullname) {
                    $finalfullname = get_course($courseid)->fullname;
                }
                if (!$finalshortname) {
                    $finalshortname = get_course($courseid)->shortname;
                }

                // Ensure uniqueness.
                [$uniquefull, $uniqueshort] = restore_data_preparer::ensure_unique_course_names(
                    $finalfullname,
                    $finalshortname
                );

                $DB->update_record('course', [
                    'id' => $courseid,
                    'fullname' => $uniquefull,
                    'shortname' => $uniqueshort,
                    'timemodified' => time(),
                ]);

                rebuild_course_cache($courseid, true);
                mtrace("Updated to meaningful names: '{$uniquefull}' / '{$uniqueshort}'");
            }
        } catch (Exception $e) {
            mtrace('Warning: Could not update course names - keeping temporary names');
            debugging($e->getMessage(), DEBUG_DEVELOPER);
        }
    }

    /**
     * Creates a new course with the specified options.
     *
     * Course options must be pre-validated and prepared before calling this method.
     *
     * @param array $options Course creation options (already validated and prepared)
     * @return int The new course ID
     * @throws moodle_exception If a course creation fails
     */
    private function create_new_course($options): int {
        try {
            // Convert options to a course data object.
            $coursedata = restore_data_preparer::prepare_course_data_object($options);

            // Create the course using Moodle's core function.
            $newcourse = create_course($coursedata);

            $this->log_course_creation($newcourse);

            return $newcourse->id;
        } catch (Exception $e) {
            throw new moodle_exception('error:course:creationfailed', 'local_chronifyai', '', $e->getMessage());
        }
    }

    /**
     * Logs course creation details.
     *
     * @param stdClass $course Created a course object
     */
    private function log_course_creation($course) {
        mtrace("Created new course with ID {$course->id}:");
        mtrace("  - Full name: {$course->fullname}");
        mtrace("  - Short name: {$course->shortname}");
        mtrace("  - Category ID: {$course->category}");
        mtrace("  - Visible: {$course->visible}");
    }

    /**
     * Applies course parameter overrides after restore completion.
     *
     * These parameters override anything that came from the backup.
     * Data must be pre-validated and prepared before calling this method.
     *
     * @param int $courseid Course ID to update
     * @param array $overrides Parameters to override (already validated and prepared)
     * @throws moodle_exception If update fails
     */
    private function apply_course_overrides($courseid, $overrides) {
        global $DB;

        if (empty($overrides)) {
            return;
        }

        $updatedata = new stdClass();
        $updatedata->id = $courseid;
        $updatedata->timemodified = time();
        $needsupdate = false;
        $appliedoverrides = [];

        // Apply fullname override.
        if (isset($overrides['fullname'])) {
            $updatedata->fullname = $overrides['fullname'];
            $needsupdate = true;
            $appliedoverrides[] = "fullname = '{$overrides['fullname']}'";
        }

        // Apply shortname override.
        if (isset($overrides['shortname'])) {
            $updatedata->shortname = $overrides['shortname'];
            $needsupdate = true;
            $appliedoverrides[] = "shortname = '{$overrides['shortname']}'";
        }

        // Apply visible override.
        if (isset($overrides['visible'])) {
            $updatedata->visible = $overrides['visible'];
            $needsupdate = true;
            $appliedoverrides[] = "visible = {$overrides['visible']}";
        }

        // Apply startdate override.
        if (isset($overrides['startdate'])) {
            $updatedata->startdate = $overrides['startdate'];
            $needsupdate = true;
            $appliedoverrides[] = "startdate = " . date('Y-m-d H:i:s', $overrides['startdate']);
        }

        // Apply enddate override if provided.
        if (isset($overrides['enddate'])) {
            $updatedata->enddate = $overrides['enddate'];
            $needsupdate = true;
            $appliedoverrides[] = "enddate = " . date('Y-m-d H:i:s', $overrides['enddate']);
        }

        if ($needsupdate) {
            try {
                if (!empty($appliedoverrides)) {
                    mtrace("Applying parameter overrides: " . implode(', ', $appliedoverrides));
                }

                // Update the course record.
                $DB->update_record('course', $updatedata);

                // Clear course cache to ensure changes are reflected immediately.
                $course = get_course($courseid);
                \course_modinfo::clear_instance_cache($course);
                rebuild_course_cache($courseid, true);

                mtrace("Course parameter overrides applied successfully");
            } catch (Exception $e) {
                throw new moodle_exception('error:course:overridefailed', 'local_chronifyai', '', $e->getMessage());
            }
        } else {
            mtrace("No parameter overrides to apply");
        }
    }

    /**
     * Extracts a backup file to a temporary directory.
     *
     * @param string $backupfilepath Path to the backup file (.mbz).
     * @param int $courseid Course ID for unique directory naming.
     * @param int $userid User ID for unique directory naming.
     * @return string The backup ID (temporary directory name).
     * @throws moodle_exception If file extraction fails.
     */
    private function extract_backup_file($backupfilepath, $courseid, $userid) {
        try {
            // Create a unique backup ID using actual courseid and userid for better uniqueness.
            $backupid = restore_controller::get_tempdir_name($courseid, $userid);

            // Create a temporary extraction directory.
            $extractpath = make_backup_temp_directory($backupid);

            // Extract a backup file using Moodle's file packer.
            $packer = get_file_packer('application/vnd.moodle.backup');
            if (!$packer->extract_to_pathname($backupfilepath, $extractpath)) {
                throw new moodle_exception(
                    'error:backup:extractionfailed',
                    'local_chronifyai',
                    '',
                    'Failed to extract backup file to: ' . $extractpath
                );
            }

            // Verify that essential backup files exist after extraction.
            $requiredfiles = ['moodle_backup.xml', 'course/course.xml'];
            foreach ($requiredfiles as $file) {
                if (!file_exists($extractpath . '/' . $file)) {
                    throw new moodle_exception(
                        'error:backup:invalidstructure',
                        'local_chronifyai',
                        '',
                        'Missing required backup file: ' . $file
                    );
                }
            }

            return $backupid;
        } catch (Exception $e) {
            throw new moodle_exception('error:backup:extractionfailed', 'local_chronifyai', '', $e->getMessage());
        }
    }

    /**
     * Creates a restore controller for the backup.
     *
     * @param string $backupid The backup ID (temporary directory name).
     * @param int $courseid The target course ID.
     * @param int $userid User ID performing the restore.
     * @param int $target Restore target (TARGET_CURRENT_DELETING or TARGET_NEW_COURSE).
     * @return restore_controller The restore controller.
     * @throws moodle_exception If the restore controller creation fails.
     */
    private function create_restore_controller($backupid, $courseid, $userid, $target) {
        try {
            return new restore_controller(
                $backupid,
                $courseid,
                backup::INTERACTIVE_NO,
                backup::MODE_GENERAL,
                $userid,
                $target
            );
        } catch (Exception $e) {
            throw new moodle_exception('error:restore:controllerfailed', 'local_chronifyai', '', $e->getMessage());
        }
    }

    /**
     * Configures restore settings with parameter override awareness.
     *
     * Uses the same settings structure as the course_backup service for consistency.
     * Ensures that course-level settings can be overridden post-restore.
     *
     * @param restore_controller $rc Restore controller.
     */
    private function configure_restore_settings(restore_controller $rc) {
        $plan = $rc->get_plan();

        // Enable overwriting ONLY for existing course restoration (TARGET_CURRENT_DELETING).
        // For new courses (TARGET_NEW_COURSE), this setting should not be set as it's locked by config.
        $target = $rc->get_target();
        if ($target == backup::TARGET_CURRENT_DELETING) {
            if ($setting = $plan->get_setting('overwrite_conf')) {
                $setting->set_value(true);
                mtrace("Set overwrite_conf = true (replacing existing course content)");
            }
        } else {
            mtrace("Skipping overwrite_conf setting (not applicable for new course creation)");
        }

        // Check if the backup contains user data using root_settings.
        $info = $rc->get_info();
        $includeuserdata = 0; // Default to not including user data.
        if (isset($info->root_settings['users']) && $info->root_settings['users'] == '1') {
            $includeuserdata = 1;
            mtrace("Backup contains user data - user-related settings enabled");
        } else {
            mtrace("Backup does not contain user data - user-related settings disabled");
        }

        // Configure restore settings based on backup content.
        // Settings automatically adapt based on whether backup includes user data.
        $settings = [
            'users' => $includeuserdata, // Only include users if the backup contains user data.
            'role_assignments' => $includeuserdata, // Only include role assignments if the backup contains user data.
            'activities' => 1, // Include activities.
            'blocks' => 1, // Include blocks.
            'filters' => 1, // Include filters.
            'comments' => $includeuserdata, // Only include comments if the backup contains user data.
            'badges' => 1, // Include badges.
            'calendarevents' => 1, // Include calendar events.
            'userscompletion' => $includeuserdata, // Only include user completion data if the backup contains user data.
            'logs' => 0, // Exclude logs.
            'grade_histories' => 0, // Exclude grade histories.
            'groups' => 1, // Include groups.
            'competencies' => 1, // Include competencies.
            'customfield' => 1, // Include custom fields.
            'contentbankcontent' => $includeuserdata, // Only include content bank content if the backup contains user data.
            'xapistate' => $includeuserdata, // Only include xAPI state if backup contains user data.
            'legacyfiles' => 1, // Include legacy files.
        ];

        // Apply settings.
        foreach ($settings as $name => $value) {
            if ($plan->setting_exists($name)) {
                $setting = $plan->get_setting($name);
                $setting->set_value($value);
                mtrace("Set restore setting {$name} = {$value}");
            } else {
                mtrace("Restore setting {$name} not found - skipping");
            }
        }

        mtrace("Restore settings configured - user parameters will override backup content");
    }

    /**
     * Executes the restore process with comprehensive error handling.
     *
     * @param restore_controller $rc Restore controller.
     * @throws moodle_exception If restore fails.
     */
    private function execute_restore(restore_controller $rc) {
        try {
            // Execute precheck validation.
            $precheckresult = $rc->execute_precheck();

            if (!$precheckresult) {
                // Get detailed precheck results for error reporting.
                $precheckresults = $rc->get_precheck_results();
                $errors = isset($precheckresults['errors']) ? $precheckresults['errors'] : [];
                $warnings = isset($precheckresults['warnings']) ? $precheckresults['warnings'] : [];

                // Log warnings but don't fail the restore.
                if (!empty($warnings)) {
                    mtrace('Restore warnings (ignored): ' . implode(', ', $warnings));
                }

                // Only fail if there are actual errors.
                if (!empty($errors)) {
                    $message = 'Errors: ' . implode(', ', $errors);
                    throw new moodle_exception('error:restore:precheckfailed', 'local_chronifyai', '', $message);
                }

                // If we only have warnings, continue with the restore.
                if (!empty($warnings)) {
                    mtrace('Continuing restore despite warnings...');
                }
            }

            // Execute the restore plan.
            $rc->execute_plan();
        } catch (Exception $e) {
            throw new moodle_exception('error:restore:executionfailed', 'local_chronifyai', '', '', $e->getMessage());
        } finally {
            // Always destroy the controller to clean up resources.
            $rc->destroy();
        }
    }

    /**
     * Cleans up temporary backup files and directories.
     *
     * @param string $backupid The backup ID (temporary directory name).
     */
    private function cleanup_backup_temp_files($backupid) {
        global $CFG;

        try {
            $tempdir = $CFG->tempdir . '/backup/' . $backupid;
            if (is_dir($tempdir)) {
                fulldelete($tempdir);
            }
        } catch (Exception $e) {
            // Log the cleanup failure but don't throw exception.
            debugging('Failed to cleanup backup temp files: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }
}
