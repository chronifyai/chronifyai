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
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use local_chronifyai\local\api\api_helper;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

/**
 * Courses external functions
 *
 * @package    local_chronifyai
 * @copyright  2025 SEBALE Innovations (http://sebale.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class courses extends external_api {
    /**
     * Returns a description of method parameters for get_courses_list.
     *
     * @return external_function_parameters
     */
    public static function get_courses_list_parameters() {
        return new external_function_parameters([
            'page' => new external_value(PARAM_INT, 'Page number (starting from 0)', VALUE_DEFAULT, 0),
            'perpage' => new external_value(PARAM_INT, 'Number of courses per page', VALUE_DEFAULT, 10000),
            'search' => new external_value(PARAM_TEXT, 'Search term for course name', VALUE_DEFAULT, ''),
            'categoryid' => new external_value(PARAM_INT, 'Category ID to filter by', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Get a paginated list of courses.
     *
     * @param int $page Page number
     * @param int $perpage Number of courses per page
     * @param string $search Search term
     * @param int $categoryid Category ID to filter by
     * @return array
     */
    public static function get_courses_list($page = 0, $perpage = 10000, $search = '', $categoryid = 0) {
        global $DB;

        // Validate parameters.
        $params = self::validate_parameters(self::get_courses_list_parameters(), [
            'page' => $page,
            'perpage' => $perpage,
            'search' => $search,
            'categoryid' => $categoryid,
        ]);

        // Validate context and capabilities using helper.
        $context = context_system::instance();
        self::validate_context($context);

        // Use the custom ChronifyAI API capability along with basic course view capability.
        api_helper::validate_capabilities([
            'local/chronifyai:useservice',
            'moodle/course:view',
        ], $context);

        // Validate and sanitize pagination parameters using helper.
        [$page, $perpage] = api_helper::validate_pagination($params['page'], $params['perpage']);

        // Build SQL query.
        $sql = "SELECT c.id, c.fullname, c.shortname, c.startdate, c.enddate,
                       c.timecreated, c.timemodified, c.category,
                       cat.name as categoryname
                FROM {course} c
                LEFT JOIN {course_categories} cat ON cat.id = c.category
                WHERE c.id != 1"; // Exclude site course.

        $conditions = [];
        $sqlparams = [];

        // Add search condition.
        if (!empty($params['search'])) {
            $conditions[] = "(c.fullname LIKE :search OR c.shortname LIKE :search)";
            $sqlparams['search'] = '%' . $params['search'] . '%';
        }

        // Add category filter.
        if ($params['categoryid'] > 0) {
            $conditions[] = "c.category = :categoryid";
            $sqlparams['categoryid'] = $params['categoryid'];
        }

        if (!empty($conditions)) {
            $sql .= " AND " . implode(' AND ', $conditions);
        }

        $sql .= " ORDER BY c.timemodified DESC";

        // Count total records.
        $countsql = "SELECT COUNT(c.id) FROM {course} c WHERE c.id != 1";
        if (!empty($conditions)) {
            $countsql .= " AND " . implode(' AND ', $conditions);
        }
        $totalcount = $DB->count_records_sql($countsql, $sqlparams);

        // Get paginated results.
        $offset = $page * $perpage;
        $courses = $DB->get_records_sql($sql, $sqlparams, $offset, $perpage);

        // Process course data.
        $coursedata = self::process_course_data($courses);

        // Create pagination info using helper.
        $pagination = api_helper::create_pagination($page, $perpage, $totalcount);

        return [
            'data' => $coursedata,
            'pagination' => $pagination,
        ];
    }

    /**
     * Process course data and add additional information.
     *
     * @param array $courses Raw course data from database
     * @return array Processed course data
     */
    private static function process_course_data($courses) {
        global $DB;

        $coursedata = [];
        foreach ($courses as $course) {
            // Get student count.
            $usercount = $DB->count_records_sql(
                "SELECT COUNT(DISTINCT ue.userid)
                 FROM {user_enrolments} ue
                 JOIN {enrol} e ON e.id = ue.enrolid
                 WHERE e.courseid = :courseid AND ue.status = 0",
                ['courseid' => $course->id]
            );

            // Get activity count.
            $activitycount = $DB->count_records('course_modules', ['course' => $course->id]);

            // Get instructor name.
            $instructorname = self::get_course_instructor($course->id);

            $coursedata[] = [
                'id' => (int) $course->id,
                'course_name' => $course->fullname,
                'category' => $course->categoryname ?: 'Uncategorized',
                'start_date' => $course->startdate ? date('Y-m-d', $course->startdate) : null,
                'end_date' => $course->enddate ? date('Y-m-d', $course->enddate) : null,
                'instructor_name' => $instructorname,
                'users' => $usercount,
                'activities' => $activitycount,
            ];
        }

        return $coursedata;
    }

    /**
     * Get the instructor name for a course.
     *
     * @param int $courseid Course ID
     * @return string Instructor name
     */
    private static function get_course_instructor($courseid) {
        global $DB;

        // Get the first enrolled teacher or editing teacher in the course.
        $instructor = $DB->get_record_sql(
            "SELECT u.firstname, u.lastname
             FROM {user} u
             JOIN {role_assignments} ra ON ra.userid = u.id
             JOIN {context} ctx ON ctx.id = ra.contextid
             JOIN {role} r ON r.id = ra.roleid
             WHERE ctx.contextlevel = :contextlevel
             AND ctx.instanceid = :courseid
             AND r.shortname IN ('editingteacher', 'teacher')
             ORDER BY ra.timemodified ASC",
            [
                'contextlevel' => CONTEXT_COURSE,
                'courseid' => $courseid,
            ],
            0, // Limitfrom.
            1 // Limitnum - get only 1 record.
        );

        if ($instructor && $instructor->firstname && $instructor->lastname) {
            return trim($instructor->firstname . ' ' . $instructor->lastname);
        }

        return get_string('instructor:unknown', 'local_chronifyai');
    }

    /**
     * Returns description of method result value for get_courses_list.
     *
     * @return external_single_structure
     */
    public static function get_courses_list_returns() {
        return new external_single_structure([
            'data' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'Course ID'),
                    'course_name' => new external_value(PARAM_TEXT, 'Course name'),
                    'category' => new external_value(PARAM_TEXT, 'Course category'),
                    'start_date' => new external_value(PARAM_TEXT, 'Course start date (Y-m-d format)', VALUE_OPTIONAL),
                    'end_date' => new external_value(PARAM_TEXT, 'Course end date (Y-m-d format)', VALUE_OPTIONAL),
                    'instructor_name' => new external_value(PARAM_TEXT, 'Instructor name'),
                    'users' => new external_value(PARAM_INT, 'Number of users'),
                    'activities' => new external_value(PARAM_INT, 'Number of activities'),
                ])
            ),
            'pagination' => new external_single_structure([
                'page' => new external_value(PARAM_INT, 'Current page number'),
                'perpage' => new external_value(PARAM_INT, 'Number of items per page'),
                'total' => new external_value(PARAM_INT, 'Total number of courses'),
                'totalpages' => new external_value(PARAM_INT, 'Total number of pages'),
                'hasnext' => new external_value(PARAM_BOOL, 'Whether there is a next page'),
                'hasprev' => new external_value(PARAM_BOOL, 'Whether there is a previous page'),
            ]),
        ]);
    }

    /**
     * Returns a description of method parameters for check_course_exists.
     *
     * @return external_function_parameters
     */
    public static function check_course_exists_parameters() {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course ID to check'),
        ]);
    }

    /**
     * Check if a course exists in Moodle.
     *
     * @param int $courseid Course ID to check
     * @return array Response with existence status and basic course info if found
     */
    public static function check_course_exists($courseid) {
        global $DB;

        // Validate parameters.
        $params = self::validate_parameters(self::check_course_exists_parameters(), [
            'courseid' => $courseid,
        ]);

        // Validate context and capabilities.
        $context = context_system::instance();
        self::validate_context($context);

        // Use the custom ChronifyAI API capability along with basic course view capability.
        api_helper::validate_capabilities([
            'local/chronifyai:useservice',
            'moodle/course:view',
        ], $context);

        // Check for site course first.
        if ($params['courseid'] == SITEID) {
            return [
                'exists' => false,
                'courseid' => $params['courseid'],
                'message' => get_string('error:course:notsupported', 'local_chronifyai'),
            ];
        }

        // Check if a course exists.
        $exists = $DB->record_exists('course', ['id' => $params['courseid']]);

        return [
            'exists' => $exists,
            'courseid' => $params['courseid'],
            'message' => get_string($exists ? 'status:course:found' : 'status:course:notfound', 'local_chronifyai'),
        ];
    }

    /**
     * Returns a description of the method result value for check_course_exists.
     *
     * @return external_single_structure
     */
    public static function check_course_exists_returns() {
        return new external_single_structure([
            'exists' => new external_value(PARAM_BOOL, 'Whether the course exists'),
            'courseid' => new external_value(PARAM_INT, 'The course ID that was checked'),
            'message' => new external_value(PARAM_TEXT, 'Status message'),
        ]);
    }

    /**
     * Returns a description of method parameters for get_categories_list.
     *
     * @return external_function_parameters
     */
    public static function get_categories_list_parameters() {
        return new external_function_parameters([]);
    }

    /**
     * Get a list of categories where the user can create/restore courses.
     *
     * @return array
     */
    public static function get_categories_list() {
        global $DB;

        // Validate parameters.
        $params = self::validate_parameters(self::get_categories_list_parameters(), []);

        // Validate context and capabilities using helper.
        $context = context_system::instance();
        self::validate_context($context);

        // Use the custom ChronifyAI API capability.
        api_helper::validate_capabilities([
            'local/chronifyai:useservice',
        ], $context);

        // Get all categories ordered by path for proper hierarchy.
        $categories = $DB->get_records('course_categories', null, 'path ASC');

        $writablecategories = [];

        foreach ($categories as $category) {
            $categorycontext = \context_coursecat::instance($category->id);

            // Check if a user can create courses in this category.
            // We check for moodle/course:create capability which is required to create courses.
            if (has_capability('moodle/course:create', $categorycontext)) {
                $writablecategories[] = [
                    'id' => (int) $category->id,
                    'name' => format_string($category->name, true, ['context' => $categorycontext]),
                ];
            }
        }

        // Fallback: if no specific categories found, check for system-level course creation capability.
        // Users with system-level permission can create courses in any category.
        if (empty($writablecategories)) {
            // Check if user has capability at system level (can create courses anywhere).
            if (has_capability('moodle/course:create', $context)) {
                // Get all categories as fallback.
                foreach ($categories as $category) {
                    $writablecategories[] = [
                        'id' => (int) $category->id,
                        'name' => format_string($category->name, true, ['context' => $categorycontext]),
                    ];
                }
            }
        }

        return $writablecategories;
    }

    /**
     * Returns description of method result value for get_categories_list.
     *
     * @return external_multiple_structure
     */
    public static function get_categories_list_returns() {
        return new external_multiple_structure(
            new external_single_structure([
                'id' => new external_value(PARAM_INT, 'Category ID'),
                'name' => new external_value(PARAM_TEXT, 'Category name'),
            ])
        );
    }
}
