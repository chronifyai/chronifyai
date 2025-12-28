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

namespace local_chronifyai\local\api;

use moodle_exception;

/**
 * API client for ChronifyAI.
 *
 * @package   local_chronifyai
 * @copyright 2025 SEBALE Innovations (http://sebale.net)
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class client {
    /**
     * Get a list of all courses.
     *
     * @param array $params Optional query parameters
     * @return \stdClass Response data
     * @throws moodle_exception If request fails
     */
    public static function get_courses(array $params = []) {
        return request::get(endpoints::COURSES_LIST, $params);
    }

    /**
     * Get details for a specific course.
     *
     * @param int $courseid The course ID
     * @param array $params Optional query parameters
     * @return \stdClass Response data
     * @throws moodle_exception If request fails
     */
    public static function get_course_detail($courseid, array $params = []) {
        $endpoint = endpoints::format(
            endpoints::COURSE_DETAIL,
            ['id' => $courseid]
        );
        return request::get($endpoint, $params);
    }

    /**
     * Upload a course backup file.
     *
     * @param string $filepath The path to the backup file
     * @param string $backuptype The type of backup (e.g., 'Moodle')
     * @return \stdClass Response data
     * @throws moodle_exception If request fails
     */
    public static function upload_backup($filepath, $backuptype = 'Moodle') {
        return request::upload_file(
            endpoints::BACKUP_UPLOAD,
            $filepath,
            'application/octet-stream',
            ['type' => $backuptype]
        );
    }

    /**
     * Create a course report by uploading JSON data to ChronifyAI.
     *
     * @param array $reportdata The report data to send
     * @param array $attachments File attachments for the report
     * @return \stdClass Response data
     * @throws moodle_exception If request fails
     */
    public static function create_report($reportdata, $attachments) {
        return request::post(endpoints::REPORT_CREATE, data: $reportdata, attachments: $attachments);
    }

    /**
     * Download a backup file as an octet-stream using its unique identifier.
     *
     * @param int $backupid The unique identifier of the backup to download
     * @param string $savepath Path where the backup file should be saved
     * @return bool True if download successful
     * @throws moodle_exception If the download request fails
     */
    public static function download_backup(int $backupid, string $savepath) {
        $endpoint = endpoints::format(
            endpoints::BACKUP_DOWNLOAD,
            ['id' => $backupid]
        );
        return request::download_file($endpoint, $savepath);
    }

    /**
     * Send notification to the App.
     *
     * @param int $userid The user ID
     * @param string $title The notification title
     * @param string $message The notification message
     * @return \stdClass Response data
     * @throws moodle_exception If request fails
     */
    public static function send_notification(int $userid, $title, $message) {
        $data = [
            'title' => $title,
            'user_id' => $userid,
            'message' => $message,
        ];

        return request::post(endpoints::NOTIFICATION_SEND, $data);
    }

    /**
     * Create user transcripts by uploading NDJSON data as a file to the ChronifyAI.
     *
     * @param array $attachments
     * @return \stdClass Response data
     * @throws moodle_exception If the file upload fails
     */
    public static function create_transcripts(array $attachments) {
        return request::post(endpoints::TRANSCRIPTS_CREATE, attachments: $attachments);
    }
}
