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

namespace local_chronifyai\local\service;

use moodle_exception;
use stdClass;

/**
 * Service for collecting and exporting user transcripts to ChronifyAI.
 *
 * @package    local_chronifyai
 * @copyright  2025 SEBALE Innovations (http://sebale.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class transcripts {
    /**
     * Get all valid users for transcript export.
     *
     * Returns users who are:
     * - Not deleted
     * - Not suspended
     * - Have at least one active course enrollment
     *
     * @return \Generator Generator yielding user IDs
     * @throws \dml_exception
     */
    public static function get_valid_users_generator(): \Generator {
        global $DB;

        $sql = "SELECT DISTINCT u.id
                  FROM {user} u
                  JOIN {user_enrolments} ue ON ue.userid = u.id
                  JOIN {enrol} e ON e.id = ue.enrolid
                  JOIN {course} c ON c.id = e.courseid
                 WHERE u.deleted = 0
                   AND u.suspended = 0
                   AND ue.status = :uestatus
                   AND e.status = :estatus
                   AND c.id != :siteid
              ORDER BY u.id ASC";

        $params = [
            'uestatus' => ENROL_USER_ACTIVE,
            'estatus' => ENROL_INSTANCE_ENABLED,
            'siteid' => SITEID,
        ];

        $recordset = $DB->get_recordset_sql($sql, $params);

        try {
            foreach ($recordset as $record) {
                yield (int)$record->id;
            }
        } finally {
            $recordset->close();
        }
    }

    /**
     * Generate transcript data for all valid users.
     *
     * Uses a generator to process users one at a time without loading
     * all data into memory.
     *
     * @return \Generator Generator yielding transcript data arrays
     * @throws \dml_exception
     */
    public static function generate_all_transcripts(): \Generator {
        $usercount = 0;
        $errorcount = 0;

        foreach (self::get_valid_users_generator() as $userid) {
            try {
                $transcript = self::collect_user_transcript($userid);

                // Only yield transcripts that have course data.
                if (!empty($transcript['courses'])) {
                    $usercount++;
                    yield $transcript;

                    // Periodic memory cleanup.
                    if ($usercount % 100 === 0) {
                        mtrace("Processed {$usercount} users...");
                        gc_collect_cycles();
                    }
                }
            } catch (\Exception $e) {
                $errorcount++;
                mtrace("Error collecting transcript for user {$userid}: " . $e->getMessage());
                debugging("Transcript collection error for user {$userid}: " . $e->getMessage(), DEBUG_DEVELOPER);

                // Continue processing other users.
                continue;
            }
        }

        mtrace("Transcript generation completed. Processed: {$usercount}, Errors: {$errorcount}");
    }

    /**
     * Collect transcript data for a user.
     *
     * Retrieves all enrolled courses with grades, completion info, and activity data.
     *
     * @param int $userid The Moodle user ID.
     * @return array Transcript data formatted for ChronifyAI API.
     * @throws moodle_exception
     */
    public static function collect_user_transcript(int $userid): array {
        global $DB;

        // Retrieve user information for email and name.
        $user = $DB->get_record('user', ['id' => $userid]);
        if (!$user) {
            throw new moodle_exception('error:user:notfound', 'local_chronifyai', '', $userid);
        }

        // Get all courses where the user is enrolled.
        $courses = self::get_user_enrolled_courses($userid);

        // Build transcript data.
        $transcriptdata = [
            'student_id' => $userid,
            'email' => $user->email,
            'name' => fullname($user),
            'courses' => [],
        ];

        // Collect course information for each enrollment.
        foreach ($courses as $course) {
            $coursedata = self::collect_course_data($userid, $course);
            if ($coursedata) {
                $transcriptdata['courses'][] = $coursedata;
            }
        }

        return $transcriptdata;
    }

    /**
     * Get all courses where the user is enrolled.
     *
     * @param int $userid The Moodle user ID.
     * @return array Array of the course objects.
     * @throws \dml_exception
     */
    private static function get_user_enrolled_courses(int $userid): array {
        global $DB;

        $sql = "SELECT DISTINCT c.id, c.idnumber, c.fullname, c.shortname, c.timemodified
                  FROM {course} c
                  JOIN {enrol} e ON e.courseid = c.id
                  JOIN {user_enrolments} ue ON ue.enrolid = e.id
                 WHERE ue.userid = :userid
                   AND c.id != :siteid
                   AND ue.status = :uestatus
                   AND e.status = :estatus
              ORDER BY c.timemodified DESC";

        $params = [
            'userid' => $userid,
            'siteid' => SITEID,
            'uestatus' => ENROL_USER_ACTIVE,
            'estatus' => ENROL_INSTANCE_ENABLED,
        ];

        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Collect detailed course data for a user.
     *
     * @param int $userid The Moodle user ID.
     * @param stdClass $course The course object.
     * @return array|null Course data array or null if no grades.
     * @throws \dml_exception
     */
    private static function collect_course_data($userid, $course): ?array {
        // Get course context.
        $context = \context_course::instance($course->id);

        // Get grade information.
        $gradedata = self::get_course_grades($userid, $course->id);

        // Get course completion info.
        $completiondata = self::get_course_completion($userid, $course->id);

        // Get activity statistics.
        $activitystats = self::get_activity_statistics($userid, $course->id);

        // Get instructor information.
        $instructorname = self::get_course_instructor($context);

        // Get course term/period.
        $term = self::get_course_term($course);

        // Build course data array.
        $coursedata = [
            'code' => $course->idnumber ?: $course->shortname,
            'title' => $course->fullname,
            'instructor' => $instructorname,
            'term' => $term,
            'grade' => $gradedata['letter'] ?? 'N/A',
            'completion' => $completiondata['percentage'] ?? 0,
            'time_in_lms' => $activitystats['time_in_hours'] ?? 0,
            'assignment_timeliness' => $activitystats['assignment_timeliness'] ?? null,
            'quiz_avg' => $activitystats['quiz_average'] ?? null,
            'status' => $completiondata['status'] ?? 'In Progress',
            'forum_posts' => $activitystats['forum_posts'] ?? null,
        ];

        return $coursedata;
    }

    /**
     * Get course grades for a user.
     *
     * @param int $userid The Moodle user ID.
     * @param int $courseid The course ID.
     * @return array|null Grade data with letter grade or null.
     * @throws \dml_exception
     */
    private static function get_course_grades(int $userid, int $courseid): ?array {
        global $DB;

        // Get the course grade item.
        $gradeitem = $DB->get_record('grade_items', [
            'courseid' => $courseid,
            'itemtype' => 'course',
        ]);

        if (!$gradeitem) {
            return null;
        }

        // Get the user's grade.
        $grade = $DB->get_record('grade_grades', [
            'itemid' => $gradeitem->id,
            'userid' => $userid,
        ]);

        if (!$grade || $grade->finalgrade === null) {
            return null;
        }

        // Convert numeric grade to letter grade.
        $letterggrade = self::convert_to_letter_grade($grade->finalgrade, $gradeitem->grademax);

        return [
            'numeric' => round($grade->finalgrade, 2),
            'letter' => $letterggrade,
        ];
    }

    /**
     * Get course completion status for a user.
     *
     * @param int $userid The Moodle user ID.
     * @param int $courseid The course ID.
     * @return array Completion status and percentage.
     * @throws \dml_exception
     */
    private static function get_course_completion(int $userid, int $courseid): array {
        global $DB;

        // Check if completion tracking is enabled for the course.
        $course = $DB->get_record('course', ['id' => $courseid], 'id, enablecompletion');
        if (!$course || !$course->enablecompletion) {
            // Completion tracking is disabled for this course.
            return [
                'status' => 'Not Available',
                'percentage' => 0,
            ];
        }

        // Check if the course has completion tracking record.
        $completion = $DB->get_record('course_completions', [
            'course' => $courseid,
            'userid' => $userid,
        ]);

        if (!$completion) {
            // No completion record exists - user hasn't started or tracking wasn't enabled when enrolled.
            return [
                'status' => 'Not Started',
                'percentage' => 0,
            ];
        }

        // Determine status.
        if ($completion->timecompleted) {
            $status = 'Completed';
        } else if ($completion->timestarted) {
            $status = 'In Progress';
        } else {
            $status = 'Not Started';
        }

        // Calculate completion percentage.
        $percentage = self::calculate_completion_percentage($userid, $courseid);

        return [
            'status' => $status,
            'percentage' => $percentage,
        ];
    }

    /**
     * Calculate course completion percentage for a user.
     *
     * @param int $userid The Moodle user ID.
     * @param int $courseid The course ID.
     * @return int Completion percentage (0-100).
     * @throws \dml_exception
     */
    private static function calculate_completion_percentage(int $userid, int $courseid): int {
        global $DB;

        // Get completion criteria for the course.
        $criteria = $DB->get_records('course_completion_criteria', ['course' => $courseid]);

        if (empty($criteria) || count($criteria) === 0) {
            return 0;
        }

        $completed = 0;
        foreach ($criteria as $criterion) {
            $completionstatus = $DB->get_record('course_completion_crit_compl', [
                'userid' => $userid,
                'criteriaid' => $criterion->id,
            ]);
            if ($completionstatus && $completionstatus->timecompleted) {
                $completed++;
            }
        }

        // Calculate percentage with validation.
        $totalcriteria = count($criteria);
        if ($totalcriteria === 0) {
            return 0;
        }
        $percentage = ($completed / $totalcriteria) * 100;
        return (int) min(100, max(0, round($percentage)));
    }

    /**
     * Get activity statistics for a user in a course.
     *
     * @param int $userid The Moodle user ID.
     * @param int $courseid The course ID.
     * @return array Activity statistics.
     * @throws \dml_exception
     */
    private static function get_activity_statistics(int $userid, int $courseid): array {
        // Get time in LMS (if log data available).
        $stats['time_in_hours'] = self::get_time_in_lms($userid, $courseid);

        // Get assignment timeliness percentage.
        $stats['assignment_timeliness'] = self::get_assignment_timeliness($userid, $courseid);

        // Get quiz average.
        $stats['quiz_average'] = self::get_quiz_average($userid, $courseid);

        // Get forum posts count.
        $stats['forum_posts'] = self::get_forum_posts_count($userid, $courseid);

        return $stats;
    }

    /**
     * Get time spent in LMS for a user in a course (in hours).
     *
     * @param int $userid The Moodle user ID.
     * @param int $courseid The course ID.
     * @return float|null Time in hours or null.
     * @throws \dml_exception
     */
    private static function get_time_in_lms(int $userid, int $courseid): ?float {
        global $DB;

        // Performance safeguard: Limit to recent activity (last 2 years) to prevent logstore table hangs.
        $twoyearsago = time() - (2 * 365 * 24 * 60 * 60);

        // Check if database supports window functions (MySQL 8.0+, MariaDB 10.2+).
        // We'll use a try-catch approach to gracefully fall back if window functions aren't available.
        try {
            // Modern approach with window functions (MySQL 8.0+, MariaDB 10.2+).
            $sql = "SELECT COALESCE(SUM(timedeltas.duration), 0) as total_duration
                      FROM (
                        SELECT userid, courseid,
                               COALESCE(LEAD(timecreated) OVER (PARTITION BY userid, courseid ORDER BY timecreated)
                                       - timecreated, 600) as duration
                          FROM {logstore_standard_log}
                         WHERE userid = :userid
                           AND courseid = :courseid
                           AND timecreated >= :timefilter
                         ORDER BY timecreated
                      ) as timedeltas";

            $params = [
                'userid' => $userid,
                'courseid' => $courseid,
                'timefilter' => $twoyearsago,
            ];

            // Use set_limit_params for the outer query.
            $result = $DB->get_record_sql($sql, $params, IGNORE_MULTIPLE);

            if ($result && $result->total_duration > 0) {
                return round($result->total_duration / 3600, 2); // Convert to hours.
            }
        } catch (\dml_exception $e) {
            // Window functions not supported - fall back to simplified calculation.
            // This provides approximate time based on log entry count rather than precise session tracking.
            $sql = "SELECT COUNT(*) as logcount
                      FROM {logstore_standard_log}
                     WHERE userid = :userid
                       AND courseid = :courseid
                       AND timecreated >= :timefilter";

            $params = [
                'userid' => $userid,
                'courseid' => $courseid,
                'timefilter' => $twoyearsago,
            ];

            $result = $DB->get_record_sql($sql, $params);

            if ($result && $result->logcount > 0) {
                // Rough estimate: assume 2 minutes average per log entry.
                return round(($result->logcount * 2) / 60, 2); // Convert to hours.
            }
        }

        return null;
    }

    /**
     * Get assignment submission timeliness percentage.
     *
     * @param int $userid The Moodle user ID.
     * @param int $courseid The course ID.
     * @return int|null Percentage of on-time submissions.
     * @throws \dml_exception
     */
    private static function get_assignment_timeliness($userid, $courseid): ?int {
        global $DB;

        // Get all assignment activities in course.
        $assignments = $DB->get_records('assign', ['course' => $courseid]);

        if (empty($assignments)) {
            return null;
        }

        $totalontime = 0;
        $totalsubmissions = 0;

        foreach ($assignments as $assignment) {
            $submission = $DB->get_record('assign_submission', [
                'assignment' => $assignment->id,
                'userid' => $userid,
            ]);

            if ($submission && $submission->timecreated) {
                $totalsubmissions++;
                if ($submission->timecreated <= $assignment->duedate || $assignment->duedate == 0) {
                    $totalontime++;
                }
            }
        }

        if ($totalsubmissions === 0) {
            return null;
        }

        $percentage = ($totalontime / $totalsubmissions) * 100;
        return (int) min(100, max(0, round($percentage)));
    }

    /**
     * Get an average quiz score for a user in a course.
     *
     * @param int $userid The Moodle user ID.
     * @param int $courseid The course ID.
     * @return int|null Average quiz score (0-100).
     * @throws \dml_exception
     */
    private static function get_quiz_average(int $userid, int $courseid): ?int {
        global $DB;

        // Get quiz module type.
        $quizzes = $DB->get_records('quiz', ['course' => $courseid]);

        if (empty($quizzes)) {
            return null;
        }

        $totalpercentage = 0;
        $quizcount = 0;

        foreach ($quizzes as $quiz) {
            // Skip quizzes with no grade configuration.
            if (empty($quiz->sumgrades) || $quiz->sumgrades <= 0) {
                continue;
            }

            $attempt = $DB->get_records('quiz_attempts', [
                'quiz' => $quiz->id,
                'userid' => $userid,
                'state' => 'finished',
            ], 'timemodified DESC', '*', 0, 1); // Get latest attempt.

            if (!empty($attempt)) {
                $attempt = reset($attempt);
                // Calculate percentage.
                $percentage = ($attempt->sumgrades / $quiz->sumgrades) * 100;
                $totalpercentage += $percentage;
                $quizcount++;
            }
        }

        if ($quizcount === 0) {
            return null;
        }

        $averagepercentage = $totalpercentage / $quizcount;
        return (int) min(100, max(0, round($averagepercentage)));
    }

    /**
     * Get forum posts to count for a user in a course.
     *
     * @param int $userid The Moodle user ID.
     * @param int $courseid The course ID.
     * @return int|null Number of forum posts.
     * @throws \dml_exception
     */
    private static function get_forum_posts_count(int $userid, int $courseid): ?int {
        global $DB;

        // Get forum instances in course.
        $forums = $DB->get_records('forum', ['course' => $courseid]);

        if (empty($forums)) {
            return null;
        }

        $totalpostcount = 0;

        foreach ($forums as $forum) {
            // Get discussions.
            $discussions = $DB->get_records('forum_discussions', ['forum' => $forum->id]);

            foreach ($discussions as $discussion) {
                // Count posts by user.
                $postcount = $DB->count_records('forum_posts', [
                    'userid' => $userid,
                    'discussion' => $discussion->id,
                ]);
                $totalpostcount += $postcount;
            }
        }

        return $totalpostcount > 0 ? $totalpostcount : null;
    }

    /**
     * Convert numeric grade to letter grade.
     *
     * @param float $grade The numeric grade.
     * @param float $grademax The maximum grade value.
     * @return string Letter grade.
     */
    private static function convert_to_letter_grade($grade, $grademax): string {
        if (empty($grademax) || $grademax <= 0) {
            return 'N/A';
        }

        $percentage = ($grade / $grademax) * 100;

        if ($percentage >= 90) {
            return 'A';
        } else if ($percentage >= 80) {
            return 'B';
        } else if ($percentage >= 70) {
            return 'C';
        } else if ($percentage >= 60) {
            return 'D';
        } else {
            return 'F';
        }
    }

    /**
     * Get the course instructor name.
     *
     * @param \context $context Course context.
     * @return string Instructor name or "N/A".
     */
    private static function get_course_instructor($context): string {
        // Get users with a teacher role in the course.
        $userfieldsapi = \core_user\fields::for_name();
        $namefields = $userfieldsapi->get_sql('u', false, '', '', true)->selects;
        $teachers = get_role_users(3, $context, false, 'u.id, u.firstname, u.lastname' . $namefields, 'u.lastname ASC');

        if (!empty($teachers)) {
            $teacher = reset($teachers);
            return fullname($teacher);
        }

        return 'N/A';
    }

    /**
     * Get course term/academic period.
     *
     * @param stdClass $course The course object
     * @return string Academic term string
     */
    private static function get_course_term(stdClass $course): string {
        // Use the course start date to determine term if available.
        $month = !isset($course->startdate) || $course->startdate <= 0 ? 0 : (int) date('m', $course->startdate);
        $year = (int) date('Y', $course->startdate ?? time());

        // Determine term based on month.
        if ($month >= 1 && $month <= 5) {
            return "Spring {$year}";
        } else if ($month >= 6 && $month <= 8) {
            return "Summer {$year}";
        } else if ($month >= 9 && $month <= 12) {
            return "Fall {$year}";
        }
        return 'Current Term';
    }
}
