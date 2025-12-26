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

namespace local_chronifyai\service;

use context_coursecat;
use context_course;
use context_system;
use Exception;
use local_chronifyai\constants;
use moodle_exception;

/**
 * Course Restore Validation Service
 *
 * Handles all validation logic for course restore operations.
 *
 * @package    local_chronifyai
 * @copyright  2025 SEBALE Innovations (http://sebale.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_validator {
    /**
     * Validates and parses restore options JSON.
     *
     * @param string $optionsjson JSON string with restore options
     * @return array Parsed and validated options
     * @throws moodle_exception If JSON is invalid or required fields are missing
     */
    public static function validate_options_json(string $optionsjson): array {
        // Parse JSON.
        $options = json_decode($optionsjson, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new moodle_exception('error:validation:invalidjson', 'local_chronifyai', '', json_last_error_msg());
        }

        // Ensure options is an array.
        if (!is_array($options)) {
            throw new moodle_exception('error:validation:jsonnotobject', 'local_chronifyai');
        }

        // Set default values for optional parameters.
        $options = array_merge([
            'categoryid' => null,
            'fullname' => '',
            'shortname' => '',
            'visible' => 1,
            'startdate' => null,
            'enddate' => null,
        ], $options);

        // Validate data types and constraints.
        self::validate_option_types($options);

        return $options;
    }

    /**
     * Validates data types and constraints for restore options.
     *
     * @param array $options Restore options array
     * @throws moodle_exception If validation fails
     */
    private static function validate_option_types(array $options): void {
        // Validate categoryid.
        if ($options['categoryid'] !== null && !is_int($options['categoryid'])) {
            throw new moodle_exception('error:validation:invalidcategoryid', 'local_chronifyai');
        }

        // Validate fullname.
        if (!is_string($options['fullname'])) {
            throw new moodle_exception('error:validation:invalidfullname', 'local_chronifyai');
        }

        if (strlen($options['fullname']) > constants::FULLNAME_MAXIMUM_LENGTH) {
            throw new moodle_exception('error:validation:fullnametoolong', 'local_chronifyai');
        }

        // Validate shortname.
        if (!is_string($options['shortname'])) {
            throw new moodle_exception('error:validation:invalidshortname', 'local_chronifyai');
        }

        if (strlen($options['shortname']) > constants::SHORTNAME_MAXIMUM_LENGTH) {
            throw new moodle_exception('error:validation:shortnametoolong', 'local_chronifyai');
        }

        // Validate visible.
        if (!in_array($options['visible'], [0, 1])) {
            throw new moodle_exception('error:validation:invalidvisible', 'local_chronifyai');
        }

        // Validate startdate.
        if ($options['startdate'] !== null && (!is_int($options['startdate']) || $options['startdate'] < 0)) {
            throw new moodle_exception('error:validation:invalidstartdate', 'local_chronifyai');
        }
    }

    /**
     * Validates requirements for creating a new course.
     *
     * @param array $options Restore options
     * @param context_system $systemcontext System context
     * @return array Validation result with success status and additional data
     */
    public static function validate_new_course_requirements(array $options, context_system $systemcontext): array {
        global $DB;

        // The categoryid is required for a new course.
        if (empty($options['categoryid'])) {
            return [
                'success' => false,
                'message' => get_string('error:validation:categoryidrequired', 'local_chronifyai'),
            ];
        }

        // Validate category exists.
        $category = $DB->get_record('course_categories', ['id' => $options['categoryid']]);
        if (!$category) {
            return [
                'success' => false,
                'message' => get_string('error:validation:categorynotfound', 'local_chronifyai'),
            ];
        }

        // Validate capabilities in category context.
        $categorycontext = context_coursecat::instance($options['categoryid']);

        try {
            // Check ChronifyAI service capability.
            require_capability('local/chronifyai:useservice', $systemcontext);

            // Check course creation capability in the category context.
            require_capability('moodle/course:create', $categorycontext);

            // Check if the user can manage course categories (needed for course creation).
            require_capability('moodle/category:manage', $categorycontext);
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => get_string('error:permission:nopermission', 'local_chronifyai'),
            ];
        }

        // If shortname is provided, validate uniqueness.
        if (!empty($options['shortname'])) {
            if ($DB->record_exists('course', ['shortname' => $options['shortname']])) {
                return [
                    'success' => false,
                    'message' => get_string('error:validation:shortnameexists', 'local_chronifyai'),
                ];
            }
        }

        return [
            'success' => true,
            'category' => $category,
        ];
    }

    /**
     * Validates requirements for restoring to an existing course.
     *
     * @param int $courseid Course ID
     * @param context_system $systemcontext System context
     * @return array Validation result with success status and additional data
     */
    public static function validate_existing_course_requirements(int $courseid, context_system $systemcontext): array {
        global $DB;

        // Validate course exists.
        $course = $DB->get_record('course', ['id' => $courseid]);
        if (!$course) {
            return [
                'success' => false,
                'message' => get_string('coursenotfound', 'local_chronifyai'),
            ];
        }

        // Site course is not supported.
        if ($courseid == SITEID) {
            return [
                'success' => false,
                'message' => get_string('error:course:notsupported', 'local_chronifyai'),
            ];
        }

        // Validate capabilities in course context.
        $coursecontext = context_course::instance($courseid);

        try {
            // Check ChronifyAI service capability.
            require_capability('local/chronifyai:useservice', $systemcontext);

            // Check restore capability in course context.
            require_capability('moodle/restore:restorecourse', $coursecontext);
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => get_string('error:permission:nopermission', 'local_chronifyai'),
            ];
        }

        return [
            'success' => true,
            'course' => $course,
        ];
    }

    /**
     * Validates options data structure and types.
     *
     * This performs comprehensive validation of all course options.
     *
     * @param array $options Restore options array
     * @param bool $isnewcourse Whether this is for new course creation
     * @throws moodle_exception If validation fails
     */
    public static function validate_options_data(array $options, bool $isnewcourse = true): void {
        // For new courses, a category is required.
        if ($isnewcourse && empty($options['categoryid'])) {
            throw new moodle_exception('error:validation:categoryidrequired', 'local_chronifyai');
        }

        // Validate categoryid type and value.
        if (
            !empty($options['categoryid'])
            && ((!is_int($options['categoryid']) && !is_numeric($options['categoryid'])) || (int) $options['categoryid'] <= 0)
        ) {
            throw new moodle_exception('error:validation:invalidcategoryid', 'local_chronifyai');
        }

        // Validate fullname.
        if (!empty($options['fullname'])) {
            if (!is_string($options['fullname'])) {
                throw new moodle_exception('error:validation:invalidfullname', 'local_chronifyai');
            }
            if (strlen($options['fullname']) > constants::FULLNAME_MAXIMUM_LENGTH) {
                throw new moodle_exception('error:validation:fullnametoolong', 'local_chronifyai');
            }
        }

        // Validate shortname.
        if (!empty($options['shortname'])) {
            if (!is_string($options['shortname'])) {
                throw new moodle_exception('error:validation:invalidshortname', 'local_chronifyai');
            }
            if (strlen($options['shortname']) > constants::SHORTNAME_MAXIMUM_LENGTH) {
                throw new moodle_exception('error:validation:shortnametoolong', 'local_chronifyai');
            }
        }

        // Validate visible.
        if (isset($options['visible']) && !in_array($options['visible'], [0, 1])) {
            throw new moodle_exception('error:validation:invalidvisible', 'local_chronifyai');
        }

        // Validate startdate.
        if (
            !empty($options['startdate'])
            && ((!is_int($options['startdate']) && !is_numeric($options['startdate'])) || (int) $options['startdate'] < 0)
        ) {
            throw new moodle_exception('error:validation:invalidstartdate', 'local_chronifyai');
        }

        // Validate enddate.
        if (
            !empty($options['enddate'])
            && ((!is_int($options['enddate']) && !is_numeric($options['enddate'])) || (int) $options['enddate'] < 0)
        ) {
            throw new moodle_exception('error:validation:invalidenddate', 'local_chronifyai');
        }

        // Validate date logic: enddate requires startdate.
        if (!empty($options['enddate']) && empty($options['startdate'])) {
            throw new moodle_exception('error:validation:enddaterequiresstartdate', 'local_chronifyai');
        }

        // Validate date logic: enddate must be after startdate.
        if (
            !empty($options['startdate'])
            && !empty($options['enddate']) && (int) $options['enddate'] <= (int) $options['startdate']
        ) {
            throw new moodle_exception('error:validation:endbeforestart', 'local_chronifyai');
        }
    }

    /**
     * Validates common restore inputs (backup file, user, optional course).
     *
     * @param string $backupfilepath Path to a backup file
     * @param int $userid User ID performing restore
     * @param int|null $courseid Optional course ID (for existing course restore)
     * @throws moodle_exception If validation fails
     */
    public static function validate_restore_inputs(string $backupfilepath, int $userid, ?int $courseid = null): void {
        self::validate_backup_file($backupfilepath);
        self::validate_user($userid);

        if ($courseid !== null) {
            self::validate_course_exists($courseid);
        }
    }

    /**
     * Validates a backup file exists and is readable.
     *
     * @param string $backupfilepath Path to a backup file
     * @throws moodle_exception If validation fails
     */
    private static function validate_backup_file(string $backupfilepath): void {
        if (!file_exists($backupfilepath)) {
            throw new moodle_exception('error:backup:filenotfound', 'local_chronifyai', '', $backupfilepath);
        }

        // Check file extension.
        $extension = pathinfo($backupfilepath, PATHINFO_EXTENSION);
        if (strtolower($extension) !== 'mbz') {
            throw new moodle_exception('error:backup:invalidfile', 'local_chronifyai', '', 'File must be a .mbz backup');
        }

        // Check if a backup file is readable and has a reasonable size.
        if (!is_readable($backupfilepath)) {
            throw new moodle_exception('error:backup:filenotreadable', 'local_chronifyai', '', $backupfilepath);
        }

        $filesize = filesize($backupfilepath);
        if ($filesize === false || $filesize < 100) {
            throw new moodle_exception('error:backup:invalidfile', 'local_chronifyai', '', 'Backup file is too small or corrupted');
        }
    }

    /**
     * Validates user ID exists.
     *
     * @param int $userid User ID
     * @throws moodle_exception If validation fails
     */
    private static function validate_user(int $userid): void {
        global $DB;

        if (empty($userid)) {
            throw new moodle_exception(
                'error:user:idmissing',
                'local_chronifyai',
                '',
                'User ID is required for restore operations'
            );
        }

        if (!$DB->record_exists('user', ['id' => $userid])) {
            throw new moodle_exception('error:user:notfound', 'local_chronifyai', '', $userid);
        }
    }

    /**
     * Validates course exists and is not the site course.
     *
     * @param int $courseid Course ID
     * @throws moodle_exception If validation fails
     */
    private static function validate_course_exists(int $courseid): void {
        global $DB;

        if (!$DB->record_exists('course', ['id' => $courseid])) {
            throw new moodle_exception('coursenotfound', 'local_chronifyai', '', $courseid);
        }

        if ($courseid == SITEID) {
            throw new moodle_exception('error:course:notsupported', 'local_chronifyai');
        }
    }
}
