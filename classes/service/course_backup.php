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
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');

use backup;
use backup_controller;
use backup_plan_dbops;
use Exception;
use moodle_exception;

/**
 * Course Backup Service
 *
 * @package    local_chronifyai
 * @copyright  2025 SEBALE Innovations (http://sebale.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_backup {
    /**
     * Creates a course backup and returns the stored file for further processing.
     *
     * This method generates a course backup using the provided course ID and user data settings,
     * and returns the stored file without deleting it (caller is responsible for cleanup).
     *
     * @param int $courseid The course ID to back-up.
     * @param bool $userdata Whether to include user data in the backup.
     * @param int $userid User ID performing the backup (REQUIRED - no default to admin).
     * @return \stored_file The backup file
     * @throws moodle_exception If an error occurs during the backup process.
     */
    public function create_backup_for_upload($courseid, $userdata, $userid) {
        $bc = null;

        try {
            // Create a backup controller (can throw exceptions).
            $bc = $this->create_backup_controller($courseid, $userid);

            // Configure backup settings.
            $this->configure_backup_settings($bc, $userdata);

            // Execute the backup.
            $bc->execute_plan();
            $results = $bc->get_results();

            /** @var \stored_file $file */
            $file = $results['backup_destination'];
            if (!$file) {
                throw new moodle_exception('error:backup:failed', 'local_chronifyai', '', 'No backup file created');
            }

            return $file;
        } catch (Exception $e) {
            throw new moodle_exception('backuperror', 'error', '', $e->getMessage());
        } finally {
            // Always destroy the controller if it was created.
            if ($bc !== null) {
                $bc->destroy();
            }
        }
    }

    /**
     * Creates a backup controller for the given course.
     *
     * @param int $courseid The course ID to back-up.
     * @param int $userid User ID performing the backup (REQUIRED - no default to admin).
     * @return backup_controller The backup controller.
     * @throws moodle_exception If the backup controller creation fails.
     */
    private function create_backup_controller($courseid, $userid) {
        // Require explicit user ID - no defaulting to admin.
        if (empty($userid)) {
            throw new moodle_exception('error:user:idmissing', 'local_chronifyai', '', 'User ID is required for backup operations');
        }

        try {
            return new backup_controller(
                backup::TYPE_1COURSE,
                $courseid,
                backup::FORMAT_MOODLE,
                backup::INTERACTIVE_NO,
                backup::MODE_GENERAL,
                $userid
            );
        } catch (Exception $e) {
            throw new moodle_exception('error:backup:controllerfailed', 'local_chronifyai', '', $e->getMessage());
        }
    }

    /**
     * Configure backup settings.
     *
     * @param backup_controller $bc Backup controller.
     * @param bool $userdata Whether to include user data.
     */
    private function configure_backup_settings(backup_controller $bc, $userdata) {
        $plan = $bc->get_plan();

        $filename = backup_plan_dbops::get_default_backup_filename(
            $bc->get_format(),
            $bc->get_type(),
            $bc->get_id(),
            $userdata,
            false
        );

        // Core settings.
        $settings = [
            'filename' => $filename,
            'users' => (int) $userdata,
            'anonymize' => 0,
            'role_assignments' => (int) $userdata,
            'activities' => 1,
            'blocks' => 1,
            'files' => 1,
            'filters' => 1,
            'comments' => 1,
            'badges' => 1,
            'calendarevents' => 1,
            'userscompletion' => (int) $userdata,
            'logs' => 0,
            'grade_histories' => 0, // User history.
            'groups' => 1,
            'competencies' => 1,
            'customfield' => 1,
            'contentbankcontent' => 1,
            'xapistate' => 1,
            'legacyfiles' => 1,
        ];

        foreach ($settings as $name => $value) {
            if ($setting = $plan->get_setting($name)) {
                $setting->set_value($value);
            }
        }
    }
}
