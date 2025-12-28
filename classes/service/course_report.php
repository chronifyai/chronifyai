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

use completion_info;
use context_course;
use moodle_exception;
use stdClass;

/**
 * Course report service for generating course analytics data
 *
 * @package    local_chronifyai
 * @copyright  2025 ChronifyAI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_report {
    /**
     * Generate a comprehensive course report.
     *
     * @param int $courseid Course ID
     * @param int $appcourseid APP course ID
     * @param int $backupid APP backup ID
     * @return array Report data
     * @throws moodle_exception
     */
    public function generate_report($courseid, $appcourseid, $backupid) {
        global $DB;

        // Validate course exists.
        $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);

        // Get course context.
        $context = context_course::instance($courseid);

        // Build a report structure.
        $report = [
            'course_id' => $appcourseid,
            'backup_id' => $backupid,
            'total_users' => 0,
            'in_progress_learners' => 0,
            'roles' => [],
            'modules' => [],
            'users' => [],
        ];

        // Get enrolled users with their roles.
        $enrolledusers = $this->get_enrolled_users($courseid, $context);
        $report['total_users'] = count($enrolledusers);

        // Process roles.
        $report['roles'] = $this->get_role_counts($enrolledusers);

        // Get course modules breakdown.
        $report['modules'] = $this->get_modules_breakdown($courseid);

        // Process user data with activities.
        $report['users'] = $this->get_users_data($courseid, $enrolledusers, $context);

        // Count in-progress learners.
        $report['in_progress_learners'] = $this->count_in_progress_learners($report['users']);

        return $report;
    }

    /**
     * Get enrolled users with their roles.
     *
     * @param int $courseid Course ID
     * @param context_course $context Course context
     * @return array Enrolled users data
     */
    private function get_enrolled_users($courseid, $context) {
        global $DB;

        // Get all required name fields for fullname() function.
        $namefields = \core_user\fields::get_name_fields();
        $userfields = 'u.id, u.email, u.timecreated as usercreated';
        foreach ($namefields as $field) {
            $userfields .= ', u.' . $field;
        }

        // Use Moodle's get_enrolled_users but we need enrollment details too.
        $enrolledusers = get_enrolled_users($context, '', 0, $userfields, null, 0, 0, true);

        // Get enrollment details for each user.
        foreach ($enrolledusers as $user) {
            // Get enrollment time and details.
            $enrollment = $DB->get_record_sql(
                "SELECT ue.timecreated as enrolled_at, ue.timestart, ue.timeend
                 FROM {user_enrolments} ue
                 JOIN {enrol} e ON e.id = ue.enrolid
                 WHERE ue.userid = :userid AND e.courseid = :courseid AND ue.status = 0
                 ORDER BY ue.timecreated ASC",
                ['userid' => $user->id, 'courseid' => $courseid]
            );

            if ($enrollment) {
                $user->enrolled_at = $enrollment->enrolled_at;
                $user->timestart = $enrollment->timestart;
                $user->timeend = $enrollment->timeend;
            } else {
                $user->enrolled_at = null;
                $user->timestart = null;
                $user->timeend = null;
            }

            // Get user's primary role in this context using Moodle function.
            $roles = get_user_roles($context, $user->id, true);
            if (!empty($roles)) {
                $primaryrole = reset($roles); // Get first (highest priority) role.
                $user->rolename = $primaryrole->shortname;
                $user->rolefullname = $primaryrole->name;
            } else {
                // Default to student if no role assigned.
                $user->rolename = 'student';
                $user->rolefullname = 'Student';
            }
        }

        return $enrolledusers;
    }

    /**
     * Get role counts from enrolled users.
     *
     * @param array $enrolledusers Enrolled users data
     * @return array Role counts
     */
    private function get_role_counts($enrolledusers) {
        $rolecounts = [];

        foreach ($enrolledusers as $user) {
            $rolename = $user->rolename ?: 'student'; // Default to student if no role assigned.

            // Normalize role names for consistent reporting.
            switch ($rolename) {
                case 'editingteacher':
                case 'teacher':
                    $normalizedrole = 'editing_teacher';
                    break;
                case 'student':
                    $normalizedrole = 'learners';
                    break;
                case 'manager':
                    $normalizedrole = 'manager';
                    break;
                case 'coursecreator':
                    $normalizedrole = 'course_creator';
                    break;
                default:
                    $normalizedrole = $rolename;
                    break;
            }

            if (!isset($rolecounts[$normalizedrole])) {
                $rolecounts[$normalizedrole] = 0;
            }
            $rolecounts[$normalizedrole]++;
        }

        return $rolecounts;
    }

    /**
     * Get course modules breakdown by type.
     *
     * @param int $courseid Course ID
     * @return array Modules breakdown
     */
    private function get_modules_breakdown($courseid) {
        global $DB;

        $sql = "SELECT m.name, COUNT(cm.id) as count
                FROM {course_modules} cm
                JOIN {modules} m ON m.id = cm.module
                WHERE cm.course = :courseid
                  AND cm.deletioninprogress = 0
                GROUP BY m.name
                ORDER BY count DESC";

        $modules = $DB->get_records_sql($sql, ['courseid' => $courseid]);

        $breakdown = [];
        foreach ($modules as $module) {
            $breakdown[$module->name] = (int)$module->count;
        }

        return $breakdown;
    }

    /**
     * Get detailed user data with activities.
     *
     * @param int $courseid Course ID
     * @param array $enrolledusers Enrolled users
     * @param context_course $context Course context
     * @return array Users data
     */
    private function get_users_data($courseid, $enrolledusers, $context) {
        global $CFG;
        require_once($CFG->libdir . '/completionlib.php');
        require_once($CFG->libdir . '/gradelib.php');

        $course = get_course($courseid);
        $completion = new completion_info($course);

        $users = [];

        foreach ($enrolledusers as $user) {
            // Get user completion data.
            $completiondata = $this->get_user_completion_data($courseid, $user->id, $completion);

            // Get user activity data with accurate completion tracking.
            $activities = $this->get_user_activities($courseid, $user->id, $completion);

            // Calculate progress using Moodle's native completion system.
            $totalprogress = $this->calculate_total_progress($courseid, $user->id, $completion, $activities);

            // Determine status.
            $status = $this->determine_user_status($completiondata, $activities, $completion, $user->id);

            // Get user login statistics.
            $loginstats = $this->get_user_login_stats($user->id, $courseid);

            $userdata = [
                'name' => fullname($user),
                'email' => $user->email,
                'enrolled_at' => $user->enrolled_at ? date('Y-m-d', $user->enrolled_at) : null,
                'started_at' => $completiondata['started_at'],
                'completed_at' => $completiondata['completed_at'],
                'status' => $status,
                'logins_per_week' => $loginstats['logins_per_week'],
                'time_per_week' => $loginstats['time_per_week'],
                'total_progress' => $totalprogress,
                'activities' => $activities,
            ];

            $users[] = $userdata;
        }

        return $users;
    }

    /**
     * Get user completion data.
     *
     * @param int $courseid Course ID
     * @param int $userid User ID
     * @param completion_info $completion Completion info object
     * @return array Completion data
     */
    private function get_user_completion_data($courseid, $userid, $completion = null) {
        global $DB;

        if (!$completion) {
            $course = get_course($courseid);
            $completion = new completion_info($course);
        }

        if (!$completion->is_enabled()) {
            return [
                'started_at' => null,
                'completed_at' => null,
            ];
        }

        // Get course completion data.
        $coursecompletion = $DB->get_record('course_completions', [
            'course' => $courseid,
            'userid' => $userid,
        ]);

        $starttime = null;
        $completetime = null;

        if ($coursecompletion) {
            if ($coursecompletion->timestarted) {
                $starttime = date('Y-m-d H:i:s', $coursecompletion->timestarted);
            }
            if ($coursecompletion->timecompleted) {
                $completetime = date('Y-m-d H:i:s', $coursecompletion->timecompleted);
            }
        } else {
            // If no completion record, check if user has any activity completions.
            $firstactivity = $DB->get_record_sql(
                "SELECT MIN(timemodified) as firsttime
                 FROM {course_modules_completion}
                 WHERE coursemoduleid IN (
                     SELECT id FROM {course_modules}
                     WHERE course = :courseid AND deletioninprogress = 0
                 ) AND userid = :userid AND timemodified > 0",
                ['courseid' => $courseid, 'userid' => $userid]
            );

            if ($firstactivity && $firstactivity->firsttime) {
                $starttime = date('Y-m-d H:i:s', $firstactivity->firsttime);
            } else {
                // Check for any course access.
                $firstaccess = $DB->get_record_sql(
                    "SELECT MIN(timecreated) as firsttime
                     FROM {logstore_standard_log}
                     WHERE userid = :userid AND courseid = :courseid
                       AND action IN ('viewed', 'submitted', 'attempted')",
                    ['userid' => $userid, 'courseid' => $courseid]
                );

                if ($firstaccess && $firstaccess->firsttime) {
                    $starttime = date('Y-m-d H:i:s', $firstaccess->firsttime);
                }
            }
        }

        return [
            'started_at' => $starttime,
            'completed_at' => $completetime,
        ];
    }

    /**
     * Get user activities with accurate completion status.
     *
     * @param int $courseid Course ID
     * @param int $userid User ID
     * @param completion_info $completion Completion info object
     * @return array Activities data
     */
    private function get_user_activities($courseid, $userid, $completion) {
        global $DB;

        $sql = "SELECT cm.id, cm.instance, cm.completion, cm.completiongradeitemnumber,
                       cm.completionview, cm.completionexpected, cm.completionpassgrade,
                       m.name as modtype,
                       COALESCE(cmc.completionstate, 0) as completionstate,
                       cmc.timemodified as completed_time
                FROM {course_modules} cm
                JOIN {modules} m ON m.id = cm.module
                LEFT JOIN {course_modules_completion} cmc ON cmc.coursemoduleid = cm.id
                      AND cmc.userid = :userid
                WHERE cm.course = :courseid
                  AND cm.deletioninprogress = 0
                  AND cm.visible = 1
                  AND cm.completion > 0
                ORDER BY cm.section, cm.id";

        $coursemodules = $DB->get_records_sql($sql, [
            'courseid' => $courseid,
            'userid' => $userid,
        ]);

        $activities = [];
        foreach ($coursemodules as $cm) {
            // Get activity name.
            $activityname = $this->get_activity_name($cm->modtype, $cm->instance);

            // Get accurate completion status and progress.
            $completiondata = $completion->get_data((object)['id' => $cm->id], false, $userid);

            $status = 'not started';
            $progress = 0;

            if (
                $completiondata->completionstate == COMPLETION_COMPLETE ||
                $completiondata->completionstate == COMPLETION_COMPLETE_PASS
            ) {
                $status = 'completed';
                $progress = 100;
            } else if ($completiondata->completionstate == COMPLETION_COMPLETE_FAIL) {
                $status = 'completed (failed)';
                $progress = 100;
            } else if ($completiondata->completionstate == COMPLETION_INCOMPLETE) {
                $status = 'in progress';

                // Calculate partial progress based on completion criteria.
                $progress = $this->calculate_activity_progress($courseid, $cm, $userid, $completiondata);
            } else if ($completiondata->viewed || $cm->completed_time) {
                $status = 'viewed';
                $progress = $cm->completionview ? 50 : 25; // If view is required, give more progress.
            }

            $activities[] = [
                'activity_name' => $activityname,
                'status' => $status,
                'progress' => $progress,
            ];
        }

        // If no completion-tracked activities, get all activities for reference.
        if (empty($activities)) {
            $sql = "SELECT cm.id, cm.instance, m.name as modtype
                    FROM {course_modules} cm
                    JOIN {modules} m ON m.id = cm.module
                    WHERE cm.course = :courseid
                      AND cm.deletioninprogress = 0
                      AND cm.visible = 1
                    ORDER BY cm.section, cm.id";

            $allmodules = $DB->get_records_sql($sql, ['courseid' => $courseid]);

            foreach ($allmodules as $cm) {
                $activityname = $this->get_activity_name($cm->modtype, $cm->instance);
                $activities[] = [
                    'activity_name' => $activityname,
                    'status' => 'not tracked',
                    'progress' => 0,
                ];
            }
        }

        return $activities;
    }

    /**
     * Calculate activity-specific progress based on completion criteria.
     *
     * @param int $courseid Course ID
     * @param stdClass $cm Course module object
     * @param int $userid User ID
     * @param stdClass $completiondata Completion data
     * @return int Progress percentage
     */
    private function calculate_activity_progress($courseid, $cm, $userid, $completiondata) {
        global $DB;

        $progress = 0;
        $criteriamet = 0;
        $totalcriteria = 0;

        // Check view requirement.
        if ($cm->completionview) {
            $totalcriteria++;
            if ($completiondata->viewed) {
                $criteriamet++;
            }
        }

        // Check grade requirement (using the correct field name).
        if ($cm->completiongradeitemnumber !== null || $cm->completionpassgrade) {
            $totalcriteria++;

            // Get grade for this activity.
            $grade = $DB->get_record_sql(
                "SELECT gg.finalgrade, gi.grademax, gi.grademin, gi.gradepass
                 FROM {grade_items} gi
                 LEFT JOIN {grade_grades} gg ON gg.itemid = gi.id AND gg.userid = :userid
                 WHERE gi.courseid = :courseid
                   AND gi.itemtype = 'mod'
                   AND gi.itemmodule = :modtype
                   AND gi.iteminstance = :instance",
                [
                    'userid' => $userid,
                    'courseid' => $courseid,
                    'modtype' => $cm->modtype,
                    'instance' => $cm->instance,
                ]
            );

            if ($grade && $grade->finalgrade !== null) {
                // If passing grade is required, check against pass grade.
                if ($cm->completionpassgrade && $grade->gradepass !== null) {
                    if ($grade->finalgrade >= $grade->gradepass) {
                        $criteriamet++;
                    }
                } else {
                    // Otherwise, just check if grade exists and is above minimum.
                    if ($grade->finalgrade >= $grade->grademin) {
                        $criteriamet++;
                    }
                }
            }
        }

        if ($totalcriteria > 0) {
            $progress = round(($criteriamet / $totalcriteria) * 100);
        } else {
            // If no specific criteria, use viewed status.
            $progress = $completiondata->viewed ? 50 : 10;
        }

        return $progress;
    }

    /**
     * Get activity name from module instance.
     *
     * @param string $modtype Module type
     * @param int $instance Instance ID
     * @return string Activity name
     */
    private function get_activity_name($modtype, $instance) {
        global $DB;

        $name = 'Unknown Activity';

        try {
            // Most modules have a 'name' field.
            $record = $DB->get_record($modtype, ['id' => $instance], 'name', IGNORE_MISSING);
            if ($record && !empty($record->name)) {
                $name = $record->name;
            }
        } catch (\Exception $e) {
            // Module table might not exist or have different structure.
            $name = ucfirst($modtype) . ' ' . $instance;
        }

        return $name;
    }

    /**
     * Calculate total progress for a user using Moodle's native completion system.
     *
     * @param int $courseid Course ID
     * @param int $userid User ID
     * @param completion_info $completion Completion info object
     * @param array $activities Activities data
     * @return int Total progress percentage
     */
    private function calculate_total_progress($courseid, $userid, $completion, $activities) {
        if (!$completion->is_enabled()) {
            // Fallback to activity-based calculation.
            if (empty($activities)) {
                return 0;
            }

            $totalprogress = 0;
            foreach ($activities as $activity) {
                $totalprogress += $activity['progress'];
            }

            return round($totalprogress / count($activities));
        }

        // Use Moodle's native progress calculation.
        $course = get_course($courseid);
        $progresspercentage = \core_completion\progress::get_course_progress_percentage($course, $userid);

        return $progresspercentage !== null ? round($progresspercentage) : 0;
    }

    /**
     * Determine user status based on completion and activities.
     *
     * @param array $completiondata Completion data
     * @param array $activities Activities data
     * @param completion_info $completion Completion info object
     * @param int $userid User ID
     * @return string Status
     */
    private function determine_user_status($completiondata, $activities, $completion, $userid) {
        // If the course is completed.
        if ($completiondata['completed_at']) {
            return 'completed';
        }

        // Check if a user has completed the course using Moodle's completion system.
        if ($completion->is_enabled()) {
            $iscomplete = $completion->is_course_complete($userid);
            if ($iscomplete) {
                return 'completed';
            }
        }

        // Check for any activity progress.
        $hasprogress = false;
        foreach ($activities as $activity) {
            if ($activity['progress'] > 0 || $activity['status'] !== 'not started') {
                $hasprogress = true;
                break;
            }
        }

        // If user has started activities or has a start time.
        if ($completiondata['started_at'] || $hasprogress) {
            return 'in-progress';
        }

        return 'not started';
    }

    /**
     * Get accurate user login statistics.
     *
     * @param int $userid User ID
     * @param int $courseid Course ID
     * @return array Login statistics
     */
    private function get_user_login_stats($userid, $courseid) {
        global $DB;

        // Get data for the last 4 weeks.
        $fourweeksago = time() - (4 * 7 * 24 * 60 * 60);

        // Use database-agnostic date conversion.
        // PostgreSQL: to_timestamp(), MySQL: FROM_UNIXTIME()
        $dbfamily = $DB->get_dbfamily();
        
        if ($dbfamily === 'postgres') {
            $dateconv = "DATE(to_timestamp(timecreated))";
        } else {
            // MySQL/MariaDB
            $dateconv = "DATE(FROM_UNIXTIME(timecreated))";
        }

        // Count unique days with course access.
        $uniquedays = $DB->count_records_sql(
            "SELECT COUNT(DISTINCT $dateconv) as days
             FROM {logstore_standard_log}
             WHERE userid = :userid
               AND courseid = :courseid
               AND timecreated > :timestart
               AND component IN ('core', 'mod_forum', 'mod_quiz', 'mod_assign', 'mod_page', 'mod_resource')",
            [
                'userid' => $userid,
                'courseid' => $courseid,
                'timestart' => $fourweeksago,
            ]
        );

        // Get actual session data.
        $sessions = $DB->get_records_sql(
            "SELECT $dateconv as day,
                    COUNT(*) as actions,
                    MAX(timecreated) - MIN(timecreated) as duration
             FROM {logstore_standard_log}
             WHERE userid = :userid
               AND courseid = :courseid
               AND timecreated > :timestart
             GROUP BY $dateconv
             ORDER BY day",
            [
                'userid' => $userid,
                'courseid' => $courseid,
                'timestart' => $fourweeksago,
            ]
        );

        $totaltime = 0;
        foreach ($sessions as $session) {
            // Estimate session time: minimum 2 minutes, maximum based on duration or actions.
            $sessiontime = max(120, min($session->duration, $session->actions * 30)); // 30 sec per action.
            $totaltime += $sessiontime;
        }

        // Convert to hours and calculate weekly averages.
        $totalhours = $totaltime / 3600;
        $loginsperweek = round($uniquedays / 4, 1);
        $timeperweek = round($totalhours / 4, 1);

        return [
            'logins_per_week' => $loginsperweek,
            'time_per_week' => $timeperweek,
        ];
    }

    /**
     * Count users with in-progress status.
     *
     * @param array $users Users data
     * @return int Count of in-progress learners
     */
    private function count_in_progress_learners($users) {
        $count = 0;
        foreach ($users as $user) {
            if ($user['status'] === 'in-progress') {
                $count++;
            }
        }
        return $count;
    }
}
