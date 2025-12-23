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

use local_chronifyai\api\client;
use local_chronifyai\constants;

/**
 * ChronifyAI Notification Service
 *
 * Handles sending notifications to the external ChronifyAI application.
 *
 * @package    local_chronifyai
 * @copyright  2025 SEBALE Innovations (http://sebale.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class notification {
    /**
     * Send course restore completion notification to ChronifyAI.
     *
     * @param int $courseid Course ID that was restored
     * @param int $externaluserid ChronifyAI app user ID (not Moodle user ID)
     * @param string $coursename Full name of the course
     * @return bool True if notification sent successfully, false otherwise
     * @throws \coding_exception
     */
    public static function send_course_restore_completed(int $courseid, int $externaluserid, string $coursename): bool {
        $courseurl = self::get_course_url($courseid);
        $title = get_string('notification:course:restore:completed:title', constants::PLUGIN_NAME);
        $message = get_string(
            'notification:course:restore:completed:message',
            constants::PLUGIN_NAME,
            (object) [
                'coursename' => $coursename,
                'courseurl' => $courseurl,
            ]
        );

        return self::send_notification_safe($externaluserid, $title, $message, 'course restore');
    }

    /**
     * Send course backup completion notification to ChronifyAI.
     *
     * @param int $externaluserid ChronifyAI app user ID (not Moodle user ID)
     * @param string $coursename Full name of the course
     * @return bool True if notification sent successfully, false otherwise
     * @throws \coding_exception
     */
    public static function send_course_backup_completed(int $externaluserid, string $coursename): bool {
        $title = get_string('notification:course:backup:completed:title', constants::PLUGIN_NAME);
        $message = get_string(
            'notification:course:backup:completed:message',
            constants::PLUGIN_NAME,
            [
                'coursename' => $coursename,
            ]
        );

        return self::send_notification_safe($externaluserid, $title, $message, 'course backup');
    }

    /**
     * Send course restore error notification to ChronifyAI.
     *
     * @param int $externaluserid ChronifyAI app user ID (not Moodle user ID)
     * @param string|null $coursename Full name of the course (if available)
     * @return bool True if notification sent successfully, false otherwise
     * @throws \coding_exception
     */
    public static function send_course_restore_failed(int $externaluserid, ?string $coursename = null): bool {
        $title = get_string('notification:course:restore:failed:title', constants::PLUGIN_NAME);
        $message = self::build_course_restore_failure_message($coursename);

        return self::send_notification_safe($externaluserid, $title, $message, 'course restore error');
    }

    /**
     * Send a notification when the transcripts export is completed.
     *
     * @param int $externaluserid External user ID (ChronifyAI user)
     * @return bool True if notification sent successfully, false otherwise
     * @throws \coding_exception
     */
    public static function send_transcripts_export_completed(int $externaluserid): bool {
        $title = get_string('notification:transcripts:completed:title', constants::PLUGIN_NAME);
        $message = get_string('notification:transcripts:completed:message', constants::PLUGIN_NAME);

        return self::send_notification_safe($externaluserid, $title, $message, 'transcripts export');
    }

    /**
     * Send a notification when the transcripts export fails.
     *
     * @param int $externaluserid External user ID (ChronifyAI user)
     * @param string $errormessage Error details
     * @return bool True if notification sent successfully, false otherwise
     * @throws \coding_exception
     */
    public static function send_transcripts_export_failed(int $externaluserid, string $errormessage): bool {
        $title = get_string('notification:transcripts:failed:title', constants::PLUGIN_NAME);
        $message = get_string(
            'notification:transcripts:failed:message',
            constants::PLUGIN_NAME,
            $errormessage
        );

        return self::send_notification_safe($externaluserid, $title, $message, 'transcripts export error');
    }

    /**
     * Send a notification to ChronifyAI with error handling.
     *
     * @param int $externaluserid ChronifyAI app user ID (not Moodle user ID)
     * @param string $title Notification title
     * @param string $message Notification message
     * @param string $context Context for error messages (e.g., 'course restore')
     * @return bool True if notification sent successfully, false otherwise
     */
    private static function send_notification_safe(
        int $externaluserid,
        string $title,
        string $message,
        string $context = 'general'
    ): bool {
        // Skip notification if no external user ID provided.
        if (empty($externaluserid)) {
            mtrace("Skipping {$context} notification: No external user ID provided");
            return false;
        }

        try {
            client::send_notification($externaluserid, $title, $message);

            mtrace("Notification sent to external app: {$title}");
            return true;
        } catch (\Throwable $e) {
            // Do not fail the main process if notification fails.
            mtrace("Warning: Failed to send {$context} notification: " . $e->getMessage());
            debugging("{$context} notification error: " . $e->getMessage(), DEBUG_DEVELOPER);
            return false;
        }
    }

    /**
     * Get course URL for notifications.
     *
     * @param int $courseid Course ID
     * @return string Course URL
     */
    private static function get_course_url(int $courseid): string {
        $courseurl = new \moodle_url('/course/view.php', ['id' => $courseid]);
        return $courseurl->out(false);
    }

    /**
     * Build the message for a course restore failure.
     *
     * @param string|null $coursename Full name of the course (if available)
     * @return string
     * @throws \coding_exception
     */
    private static function build_course_restore_failure_message(?string $coursename = null): string {
        if (!empty($coursename)) {
            return get_string(
                'notification:course:restore:failed:message:withname',
                constants::PLUGIN_NAME,
                $coursename
            );
        }

        return get_string(
            'notification:course:restore:failed:message',
            constants::PLUGIN_NAME
        );
    }
}
