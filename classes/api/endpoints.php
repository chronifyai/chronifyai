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

namespace local_chronifyai\api;

/**
 * ChronifyAI API endpoints.
 *
 * @package    local_chronifyai
 * @copyright  2025 ChronifyAI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class endpoints {
    /** @var string Authentication endpoint */
    public const AUTH = 'sso-login';

    /** @var string Courses list endpoint */
    public const COURSES_LIST = 'courses';

    /** @var string Course detail endpoint */
    public const COURSE_DETAIL = 'courses/{id}';

    /** @var string Course backup upload endpoint */
    public const BACKUP_UPLOAD = 'backups/stream-upload';

    /** @var string Course backup download endpoint */
    public const BACKUP_DOWNLOAD = 'backups/get/{id}';

    /** @var string Create a new report along with users and their activity data */
    public const REPORT_CREATE = 'reports/create';

    /** @var string Send notification endpoint */
    public const NOTIFICATION_SEND = 'notification/send';

    /** @var string Transcripts create endpoint */
    public const TRANSCRIPTS_CREATE = 'transcripts/import';

    /**
     * Format endpoint with parameters.
     *
     * @param string $endpoint Endpoint template
     * @param array $params Parameters to replace
     * @return string Formatted endpoint
     *
     * @example
     * ```php
     * $endpoint = endpoints::format(endpoints::COURSE_DETAIL, ['id' => 123]);
     * // Returns: 'courses/123'
     * ```
     */
    public static function format($endpoint, array $params = []) {
        $path = $endpoint;

        // Replace each parameter in the path.
        foreach ($params as $key => $value) {
            $path = str_replace('{' . $key . '}', urlencode($value), $path);
        }

        return $path;
    }
}
