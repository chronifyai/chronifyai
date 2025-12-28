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

use Psr\Http\Message\ResponseInterface;
use stdClass;
use moodle_exception;

/**
 * HTTP response handler for ChronifyAI API responses.
 *
 * @package    local_chronifyai
 * @copyright  2025 ChronifyAI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class response_handler {
    /** @var array Success status codes for different operations */
    private const SUCCESS_STATUS_CODES = [
        'GET' => [200],
        'POST' => [200, 201, 202], // Include created and accepted.
        'PUT' => [200, 202, 204], // Include accepted and no content.
        'PATCH' => [200, 202, 204],
        'DELETE' => [200, 202, 204],
    ];

    /** @var array Client error status codes (4xx) */
    private const CLIENT_ERROR_CODES = [
        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        409 => 'Conflict',
        422 => 'Unprocessable Entity',
        429 => 'Too Many Requests',
    ];

    /** @var array Server error status codes (5xx) */
    private const SERVER_ERROR_CODES = [
        500 => 'Internal Server Error',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
    ];

    /**
     * Process HTTP response and return structured data.
     *
     * @param ResponseInterface $response HTTP response
     * @param string $method HTTP method used
     * @return stdClass Processed response data
     */
    public static function process(ResponseInterface $response, string $method = 'GET'): stdClass {
        $statuscode = $response->getStatusCode();
        $body = $response->getBody()->getContents();
        $contenttype = $response->getHeaderLine('Content-Type');

        // Check if the status code indicates success for this method.
        if (!self::is_success_status($statuscode, $method)) {
            // Create a structured error response instead of throwing immediately.
            $result = new stdClass();
            $result->status_code = $statuscode;
            $result->success = false;
            $result->error_message = self::format_error_message($statuscode, $body, $contenttype);
            $result->is_retriable = self::is_retriable_error($statuscode);
            return $result;
        }

        // Parse response body.
        $data = self::parse_response_body($body, $contenttype);

        // Create a standardized response object.
        $result = new stdClass();
        $result->status_code = $statuscode;
        $result->success = true;
        $result->data = $data;
        $result->is_retriable = false;

        // Add common response metadata.
        if (isset($data->id)) {
            $result->id = $data->id;
        }
        if (isset($data->message)) {
            $result->message = $data->message;
        }

        return $result;
    }

    /**
     * Check if status code indicates success for the given HTTP method.
     *
     * @param int $statuscode HTTP status code
     * @param string $method HTTP method
     * @return bool True if successful
     */
    private static function is_success_status(int $statuscode, string $method): bool {
        $method = strtoupper($method);
        $successcodes = self::SUCCESS_STATUS_CODES[$method] ?? self::SUCCESS_STATUS_CODES['GET'];

        return in_array($statuscode, $successcodes);
    }

    /**
     * Parse response body based on content type.
     *
     * @param string $body Response body
     * @param string $contenttype Content type header
     * @return stdClass|array|string Parsed data
     */
    private static function parse_response_body(string $body, string $contenttype) {
        // Handle empty responses (common for 204 No Content).
        if (empty($body)) {
            return new stdClass();
        }

        // Parse JSON responses.
        if (str_contains($contenttype, 'application/json')) {
            $decoded = json_decode($body);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        // Handle plain text or other content types.
        if (str_contains($contenttype, 'text/plain')) {
            return (object) ['message' => trim($body)];
        }

        // Default: attempt JSON decode, fallback to raw body.
        $decoded = json_decode($body);
        return (json_last_error() === JSON_ERROR_NONE) ? $decoded : (object) ['raw' => $body];
    }

    /**
     * Format error message from HTTP response.
     *
     * @param int $statuscode HTTP status code
     * @param string $body Response body
     * @param string $contenttype Content type
     * @return string Formatted error message
     */
    private static function format_error_message(int $statuscode, string $body, string $contenttype): string {
        // Get standard error message for status code.
        $standardmessage = self::get_standard_error_message($statuscode);

        // Try to extract error message from response body.
        $responsemessage = self::extract_error_from_body($body, $contenttype);

        // Combine messages for comprehensive error reporting.
        if ($responsemessage) {
            return "HTTP {$statuscode} ({$standardmessage}): {$responsemessage}";
        }

        return "HTTP {$statuscode}: {$standardmessage}";
    }

    /**
     * Get standard error message for HTTP status code.
     *
     * @param int $statuscode HTTP status code
     * @return string Standard error message
     */
    private static function get_standard_error_message(int $statuscode): string {
        // Check client errors first.
        if (isset(self::CLIENT_ERROR_CODES[$statuscode])) {
            return self::CLIENT_ERROR_CODES[$statuscode];
        }

        // Check server errors.
        if (isset(self::SERVER_ERROR_CODES[$statuscode])) {
            return self::SERVER_ERROR_CODES[$statuscode];
        }

        // Handle other status codes.
        if ($statuscode >= 400 && $statuscode < 500) {
            return 'Client Error';
        }
        if ($statuscode >= 500) {
            return 'Server Error';
        }

        return 'Unknown Error';
    }

    /**
     * Extract error message from response body.
     *
     * @param string $body Response body
     * @param string $contenttype Content type
     * @return string|null Error message if found
     */
    private static function extract_error_from_body(string $body, string $contenttype): ?string {
        if (empty($body)) {
            return null;
        }

        // Try to parse as JSON and extract error message.
        if (str_contains($contenttype, 'application/json')) {
            $data = json_decode($body, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
                // Common error message fields.
                $errorfields = ['error', 'message', 'detail', 'description'];
                foreach ($errorfields as $field) {
                    if (isset($data[$field]) && !empty($data[$field])) {
                        return is_string($data[$field]) ? $data[$field] : json_encode($data[$field]);
                    }
                }
            }
        }

        // For non-JSON or if no specific error field found, return truncated body.
        return strlen($body) > 200 ? substr($body, 0, 200) . '...' : $body;
    }

    /**
     * Determine if response indicates a retriable error.
     *
     * @param int $statuscode HTTP status code
     * @return bool True if the error is retriable
     */
    public static function is_retriable_error(int $statuscode): bool {
        // Server errors (5xx) - usually temporary.
        $servererrors = [500, 502, 503, 504, 521, 522, 523, 524];

        // Rate limiting and throttling.
        $throttlingerrors = [429];

        // Specific retriable client errors.
        $retriableclienterrors = [408, 409]; // Request timeout, conflict.

        return in_array($statuscode, array_merge($servererrors, $throttlingerrors, $retriableclienterrors));
    }

    /**
     * Get retry delay based on status code and attempt number.
     *
     * @param int $statuscode HTTP status code
     * @param int $attempt Current attempt number (1-based)
     * @return int Delay in seconds
     */
    public static function get_retry_delay(int $statuscode, int $attempt): int {
        // Rate limiting gets longer delays.
        if ($statuscode === 429) {
            return min(60, pow(2, $attempt) * 5); // 5, 10, 20, 40, 60 seconds max.
        }

        // Server errors get standard exponential backoff.
        if ($statuscode >= 500) {
            return min(30, pow(2, $attempt - 1)); // 1, 2, 4, 8, 16, 30 seconds max.
        }

        // Other retriable errors get shorter delays.
        return min(10, pow(2, $attempt - 1)); // 1, 2, 4, 8, 10 seconds max.
    }

    /**
     * Check if this is a temporary network error that should be retried.
     *
     * @param string $errormessage Error message from exception
     * @return bool True if this looks like a temporary network error
     */
    public static function is_temporary_network_error(string $errormessage): bool {
        $temporarypatterns = [
            'connection timed out',
            'connection reset',
            'connection refused',
            'network is unreachable',
            'temporary failure in name resolution',
            'ssl connection timeout',
            'couldn\'t connect to host',
            'operation timed out',
            'recv failure',
            'send failure',
        ];

        $lowercasemessage = strtolower($errormessage);
        foreach ($temporarypatterns as $pattern) {
            if (str_contains($lowercasemessage, $pattern)) {
                return true;
            }
        }

        return false;
    }
}
