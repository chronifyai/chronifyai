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

use context;
use required_capability_exception;

/**
 * API helper class for common operations.
 *
 * @package    local_chronifyai
 * @copyright  2025 ChronifyAI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class api_helper {
    /** @var int Maximum allowed items per page */
    const MAX_PER_PAGE = 1000;

    /** @var int Default items per page */
    const DEFAULT_PER_PAGE = 50;

    /**
     * Validate capabilities for the current user in the given context.
     *
     * @param array $capabilities Array of capability strings to check
     * @param context $context Context to check capabilities in
     * @throws required_capability_exception If user lacks required capabilities
     */
    public static function validate_capabilities(array $capabilities, context $context) {
        foreach ($capabilities as $capability) {
            require_capability($capability, $context);
        }
    }

    /**
     * Validate and sanitize pagination parameters.
     *
     * @param int $page Page number (0-based)
     * @param int $perpage Items per page
     * @return array [validated_page, validated_perpage]
     */
    public static function validate_pagination($page, $perpage) {
        // Validate page.
        if ($page < 0) {
            $page = 0;
        }

        // Validate perpage.
        if ($perpage <= 0) {
            $perpage = self::DEFAULT_PER_PAGE;
        } else if ($perpage > self::MAX_PER_PAGE) {
            $perpage = self::MAX_PER_PAGE;
        }

        return [$page, $perpage];
    }

    /**
     * Create pagination information for API responses.
     *
     * @param int $page Current page (0-based)
     * @param int $perpage Items per page
     * @param int $total Total number of items
     * @return array Pagination information
     */
    public static function create_pagination($page, $perpage, $total) {
        $totalpages = $perpage > 0 ? ceil($total / $perpage) : 0;

        return [
            'page' => (int) $page,
            'perpage' => (int) $perpage,
            'total' => (int) $total,
            'totalpages' => (int) $totalpages,
            'hasnext' => ($page + 1) < $totalpages,
            'hasprev' => $page > 0,
        ];
    }

    /**
     * Log API usage for debugging and monitoring.
     *
     * @param string $endpoint Endpoint name
     * @param array $params Parameters used
     * @param int|null $userid User ID making the request
     */
    public static function log_api_usage($endpoint, $params = [], $userid = null) {
        global $USER;

        if ($userid === null) {
            $userid = $USER->id;
        }

        // Use Moodle's standard logging.
        $logcontext = \context_system::instance();
        $event = \core\event\webservice_function_called::create([
            'context' => $logcontext,
            'userid' => $userid,
            'other' => [
                'function' => "local_chronifyai_{$endpoint}",
                'params' => json_encode($params),
            ],
        ]);
        $event->trigger();
    }

    /**
     * Standardized error response
     */
    public static function create_error_response(string $message, int $code = 400): array {
        return [
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ];
    }
}
