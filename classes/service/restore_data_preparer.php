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

use moodle_exception;
use stdClass;

/**
 * Course Restore Data Preparation Service
 *
 * Handles data cleaning, sanitization, and preparation for course restore operations.
 * This class transforms validated data into a format ready for use by the service layer.
 *
 * @package    local_chronifyai
 * @copyright  2025 SEBALE Innovations (http://sebale.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_data_preparer {
    /**
     * Prepares and validates course options from API input.
     *
     * This method:
     * 1. Validates the data structure and types
     * 2. Cleans and sanitizes string values
     * 3. Typecasts numeric values
     * 4. Sets defaults for optional fields
     * 5. Validates business rules (e.g., date ranges, uniqueness)
     *
     * @param array $options Raw course options from API
     * @param bool $isnewcourse Whether this is for a new course creation
     * @return array Prepared and validated course options
     * @throws moodle_exception If validation or preparation fails
     */
    public static function prepare_course_options(array $options, bool $isnewcourse = true): array {
        // First, validate using the validator.
        restore_validator::validate_options_data($options, $isnewcourse);

        // Now prepare the data.
        $prepared = [];

        // Typecast categoryid.
        if (isset($options['categoryid'])) {
            $prepared['categoryid'] = (int) $options['categoryid'];
        }

        // Clean and prepare fullname.
        if (isset($options['fullname'])) {
            $prepared['fullname'] = !empty($options['fullname'])
                ? clean_param($options['fullname'], PARAM_TEXT)
                : null;
        } else {
            $prepared['fullname'] = null;
        }

        // Clean and prepare shortname.
        if (isset($options['shortname'])) {
            $prepared['shortname'] = !empty($options['shortname'])
                ? clean_param($options['shortname'], PARAM_TEXT)
                : null;
        } else {
            $prepared['shortname'] = null;
        }

        // Typecast visible (with default).
        $prepared['visible'] = (int) ($options['visible'] ?? 1);

        // Typecast startdate if provided.
        if (!empty($options['startdate'])) {
            $prepared['startdate'] = (int) $options['startdate'];
        }

        // Typecast enddate if provided.
        if (!empty($options['enddate'])) {
            $prepared['enddate'] = (int) $options['enddate'];
        }

        return $prepared;
    }

    /**
     * Prepares a course data object for course creation.
     *
     * Converts array options into a stdClass ready for create_course().
     *
     * @param array $options Prepared course options
     * @return stdClass Course data object
     */
    public static function prepare_course_data_object(array $options): stdClass {
        $coursedata = new stdClass();

        // Required fields.
        $coursedata->category = $options['categoryid'];
        $coursedata->fullname = $options['fullname'];
        $coursedata->shortname = $options['shortname'];
        $coursedata->visible = $options['visible'];

        // Optional fields.
        if (!empty($options['startdate'])) {
            $coursedata->startdate = $options['startdate'];
        }

        if (!empty($options['enddate'])) {
            $coursedata->enddate = $options['enddate'];
        }

        // Defaults for fields that will be overridden by backup.
        $coursedata->format = 'topics';
        $coursedata->numsections = 5;
        $coursedata->summary = '';
        $coursedata->summaryformat = FORMAT_HTML;

        return $coursedata;
    }

    /**
     * Prepares temporary course options with guaranteed unique names.
     *
     * Creates temporary placeholder names that will be replaced after restore.
     *
     * @param array $options Prepared course options
     * @return array Course options with temporary unique names
     */
    public static function prepare_temp_course_options(array $options): array {
        $timestamp = time();
        $tempfullname = 'Restoring Course ' . $timestamp;
        $tempshortname = 'restoring_' . $timestamp;

        // Ensure uniqueness.
        [$uniquefull, $uniqueshort] = self::ensure_unique_course_names($tempfullname, $tempshortname);

        $tempoptions = [
            'categoryid' => $options['categoryid'],
            'fullname' => $uniquefull,
            'shortname' => $uniqueshort,
            'visible' => $options['visible'],
        ];

        // Preserve dates if provided.
        if (!empty($options['startdate'])) {
            $tempoptions['startdate'] = $options['startdate'];
        }

        if (!empty($options['enddate'])) {
            $tempoptions['enddate'] = $options['enddate'];
        }

        return $tempoptions;
    }

    /**
     * Prepares override options for applying after restore.
     *
     * Extracts only the parameters that should override backup content.
     *
     * @param array $options Prepared course options
     * @return array Override options (only non-null values)
     */
    public static function prepare_override_options(array $options): array {
        $overrides = [];

        // These fields should always override backup content if specified.
        if (isset($options['visible'])) {
            $overrides['visible'] = $options['visible'];
        }

        if (!empty($options['startdate'])) {
            $overrides['startdate'] = $options['startdate'];
        }

        if (!empty($options['enddate'])) {
            $overrides['enddate'] = $options['enddate'];
        }

        // For new courses, preserve user-specified names.
        if (!empty($options['fullname'])) {
            $overrides['fullname'] = $options['fullname'];
        }

        if (!empty($options['shortname'])) {
            $overrides['shortname'] = $options['shortname'];
        }

        return $overrides;
    }

    /**
     * Ensures course names are unique.
     *
     * Uses Moodle's built-in uniqueness check and returns unique names.
     *
     * @param string $fullname Desired full name
     * @param string $shortname Desired short name
     * @return array Array with [unique_fullname, unique_shortname]
     */
    public static function ensure_unique_course_names(string $fullname, string $shortname): array {
        return \restore_dbops::calculate_course_names(0, $fullname, $shortname);
    }
}
