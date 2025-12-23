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

use core\exception\coding_exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use local_chronifyai\constants;
use local_chronifyai\config;
use moodle_exception;
use Psr\Http\Message\ResponseInterface;
use stdClass;

/**
 * ChronifyAI API request service.
 *
 * Handles HTTP requests to the ChronifyAI API with automatic authentication,
 * retry logic, and proper error handling.
 *
 * @package   local_chronifyai
 * @copyright 2025 SEBALE Innovations (http://sebale.net)
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class request {
    /** @var int Maximum number of retry attempts for failed requests */
    private const MAX_RETRY_ATTEMPTS = 3;

    /** @var int Request timeout in seconds */
    private const REQUEST_TIMEOUT = 30;

    /** @var int Connection timeout in seconds */
    private const CONNECTION_TIMEOUT = 10;

    /** @var array Retry attempts tracking per request to prevent infinite loops */
    private static array $retrytracking = [];

    /**
     * Make an API GET request.
     *
     * @param string $endpoint Endpoint path
     * @param array $params Query parameters
     * @return stdClass Response data
     * @throws moodle_exception If request fails
     *
     * @example
     * ```php
     * $courses = request::get('courses', ['limit' => 10]);
     * ```
     */
    public static function get(string $endpoint, array $params = []): stdClass {
        return self::request('GET', $endpoint, $params);
    }

    /**
     * Make an API POST request.
     *
     * @param string $endpoint Endpoint path
     * @param array $data Request body data
     * @return stdClass Response data
     * @throws moodle_exception If request fails
     *
     * @example
     * ```php
     * $schedule = request::post('schedules', ['name' => 'Daily Backup']);
     * ```
     */
    public static function post(string $endpoint, array $data = [], array $attachments = []): stdClass {
        return self::request('POST', $endpoint, $data, attachments: $attachments);
    }

    /**
     * Make an API PUT request.
     *
     * @param string $endpoint Endpoint path
     * @param array $data Request body data
     * @return stdClass Response data
     * @throws moodle_exception If request fails
     */
    public static function put(string $endpoint, array $data = []): stdClass {
        return self::request('PUT', $endpoint, $data);
    }

    /**
     * Make an API DELETE request.
     *
     * @param string $endpoint Endpoint path
     * @param array $params Query parameters
     * @return stdClass Response data
     * @throws moodle_exception If request fails
     */
    public static function delete(string $endpoint, array $params = []): stdClass {
        return self::request('DELETE', $endpoint, $params);
    }
    /**
     * Make an API request with automatic authentication and retry handling.
     *
     * This method handles all the complexity of making authenticated requests,
     * including token retrieval, retry logic for auth failures, and proper
     * error handling.
     *
     * @param string $method HTTP method (GET, POST, PUT, DELETE)
     * @param string $endpoint Endpoint path
     * @param array $data Request data (query params for GET/DELETE, body for POST/PUT)
     * @param int $attempt Current attempt number (used internally for retry logic)
     * @param array $attachments File attachments for POST/PUT requests (optional)
     * @return stdClass Response data
     * @throws moodle_exception If request fails or plugin is disabled
     *
     * @example
     * ```php
     * $response = request::request('GET', 'courses/123', ['include' => 'activities']);
     * ```
     */
    public static function request(
        string $method,
        string $endpoint,
        array $data = [],
        int $attempt = 1,
        array $attachments = []
    ): stdClass {
        // Check if the plugin is enabled.
        if (!config::is_enabled()) {
            throw new moodle_exception('status:plugin:disabled', constants::PLUGIN_NAME);
        }

        // Validate method.
        $allowedmethods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'];
        if (!in_array(strtoupper($method), $allowedmethods)) {
            throw new coding_exception(
                'Method ' . $method . ' is not allowed.',
                'Allowed methods: ' . implode(', ', $allowedmethods) . '.'
            );
        }

        // Create a request tracking key to prevent infinite loops.
        $trackingkey = md5($method . $endpoint . serialize($data));

        // Reset tracking if this is the first attempt.
        if ($attempt === 1) {
            self::$retrytracking[$trackingkey] = ['attempts' => 0, 'last_attempt' => time()];
        }

        // Check if we've exceeded maximum attempts.
        if (self::$retrytracking[$trackingkey]['attempts'] >= self::MAX_RETRY_ATTEMPTS) {
            unset(self::$retrytracking[$trackingkey]);
            throw new moodle_exception('maxretriesexceeded', constants::PLUGIN_NAME);
        }

        self::$retrytracking[$trackingkey]['attempts']++;
        self::$retrytracking[$trackingkey]['last_attempt'] = time();

        try {
            // Get an authentication token.
            $token = auth::get_token();

            // Build full URL.
            $url = self::build_url($endpoint);

            // Create and configure the Guzzle client.
            $client = self::create_client();

            // Prepare request options.
            $options = self::prepare_request_options($method, $data, $token, attachments: $attachments);

            // Make the request.
            $response = $client->request($method, $url, $options);

            // Process response using the response handler.
            $result = self::process_response($response, $method);

            // Clear tracking on a successful response.
            unset(self::$retrytracking[$trackingkey]);

            // Check if the response indicates a retriable error.
            if (!$result->success && $result->is_retriable && $attempt < self::MAX_RETRY_ATTEMPTS) {
                $delay = response_handler::get_retry_delay($result->status_code, $attempt);
                sleep($delay);
                return self::request($method, $endpoint, $data, $attempt + 1);
            }

            // If not successful and not retriable, throw an error.
            if (!$result->success) {
                unset(self::$retrytracking[$trackingkey]);
                // Format error message properly for user display.
                $errormsg = !empty($result->error_message) ? $result->error_message : 'Unknown API error';
                throw new moodle_exception(
                    'error:api:communicationfailed',
                    constants::PLUGIN_NAME,
                    '',
                    $errormsg,
                    "Request to $endpoint failed with status {$result->http_code}"
                );
            }

            return $result;
        } catch (GuzzleException $e) {
            return self::handle_request_exception($e, $method, $endpoint, $data, $attempt, $trackingkey);
        }
    }

    /**
     * Upload a file using stream upload.
     *
     * @param string $endpoint Upload endpoint
     * @param string $filepath Path to the file to upload
     * @param string $contentType Content type for the upload
     * @param array $queryParams Additional query parameters
     * @return stdClass Response data
     * @throws moodle_exception If upload fails
     *
     * @example
     * ```php
     * $result = request::upload_file('backups/stream-upload',
     *                               '/path/to/backup.mbz',
     *                               'application/octet-stream',
     *                               ['type' => 'moodle']);
     * ```
     */
    public static function upload_file(
        string $endpoint,
        string $filepath,
        string $contenttype = 'application/octet-stream',
        array $queryparams = []
    ): stdClass {
        // Validate file exists and is readable.
        if (!file_exists($filepath) || !is_readable($filepath)) {
            throw new moodle_exception('error:file:notfound', constants::PLUGIN_NAME, '', null, $filepath);
        }

        $token = auth::get_token();
        $url = self::build_url($endpoint, $queryparams);
        $stream = null;

        try {
            $client = self::create_client();

            // Create a stream resource instead of reading into memory.
            $stream = fopen($filepath, 'r');
            if ($stream === false) {
                throw new moodle_exception('error:file:readfailed', constants::PLUGIN_NAME, '', null, $filepath);
            }

            // Get file size for Content-Length header.
            $filesize = filesize($filepath);
            if ($filesize === false) {
                throw new moodle_exception('error:file:sizefailed', constants::PLUGIN_NAME, '', null, $filepath);
            }

            $options = [
                RequestOptions::HEADERS => [
                    'Content-Type' => $contenttype,
                    'Content-Length' => $filesize,
                    'Authorization' => 'Bearer ' . $token,
                ],
                RequestOptions::BODY => $stream, // Pass the stream directly.
                RequestOptions::TIMEOUT => 300, // 5 minutes for file uploads
            ];

            $response = $client->post($url, $options);

            // Process response and handle any API errors.
            $result = self::process_response($response);
            if (!$result->success) {
                throw new moodle_exception(
                    'error:file:uploadfailed',
                    constants::PLUGIN_NAME,
                    '',
                    $result->error_message ?? 'Unknown error',
                    "File upload failed with HTTP code {$result->http_code}"
                );
            }
            return $result;
        } catch (GuzzleException $e) {
            throw new moodle_exception(
                'error:file:uploadfailed',
                constants::PLUGIN_NAME,
                '',
                null,
                $e->getMessage()
            );
        } finally {
            // Always try to close the stream in the finally block
            // Check if it's still a valid resource before closing.
            if ($stream !== null && is_resource($stream)) {
                fclose($stream);
            }
        }
    }

    /**
     * Download a file from the API using streaming for huge files.
     *
     * @param string $endpoint Download endpoint
     * @param string $savepath Path where the file should be saved
     * @param array $params Query parameters
     * @return bool True if download successful
     * @throws moodle_exception If download fails
     */
    public static function download_file(string $endpoint, string $savepath, array $params = []): bool {
        // Ensure directory exists.
        $directory = dirname($savepath);
        if (!is_dir($directory) && !mkdir($directory, 0755, true)) {
            throw new moodle_exception('error:dir:createfailed', constants::PLUGIN_NAME, '', null, $directory);
        }

        $tempfile = $savepath . '.tmp';

        try {
            $client = self::create_client();
            $token = auth::get_token();
            $url = self::build_url($endpoint, $params);

            $response = $client->get($url, [
                RequestOptions::HEADERS => [
                    'Accept' => 'application/octet-stream',
                    'Authorization' => 'Bearer ' . $token,
                ],
                RequestOptions::SINK => $tempfile, // Stream directly to a temp file.
                RequestOptions::TIMEOUT => 3600,
            ]);

            $statuscode = $response->getStatusCode();

            if ($statuscode === 200) {
                return rename($tempfile, $savepath);
            }

            // Clean up temp file and throw appropriate exception.
            self::cleanup_temp_file($tempfile);

            if ($statuscode === 404) {
                throw new moodle_exception('error:file:notfound', constants::PLUGIN_NAME, '', null, '(HTTP 404)');
            }

            throw new moodle_exception('error:file:downloadfailed', constants::PLUGIN_NAME, '', null, "Download failed (HTTP {$statuscode})");
        } catch (GuzzleException $e) {
            self::cleanup_temp_file($tempfile);
            throw new moodle_exception('error:file:downloadfailed', constants::PLUGIN_NAME, '', null, $e->getMessage());
        }
    }

    /**
     * Remove a temporary file from the filesystem.
     *
     * @param string $filepath The path to the temporary file
     * @return void
     */
    private static function cleanup_temp_file(string $filepath): void {
        if (file_exists($filepath)) {
            unlink($filepath); // Let it throw if it fails.
        }
    }

    /**
     * Build the full API URL for an endpoint.
     *
     * @param string $endpoint Endpoint path
     * @param array $queryParams Query parameters to append
     * @return string Full URL
     */
    private static function build_url(string $endpoint, array $queryparams = []): string {
        $baseurl = rtrim(config::get_api_base_url(), '/');
        $url = $baseurl . '/' . ltrim($endpoint, '/');

        if (!empty($queryparams)) {
            $url .= '?' . http_build_query($queryparams);
        }

        return $url;
    }

    /**
     * Create and configure a Guzzle HTTP client.
     *
     * Creates a standardized HTTP client with consistent timeouts, headers,
     * and error handling configuration for all ChronifyAI API interactions.
     *
     * @return Client Configured Guzzle client ready for API requests
     */
    public static function create_client(): Client {
        return new Client([
            RequestOptions::TIMEOUT => self::REQUEST_TIMEOUT,
            RequestOptions::CONNECT_TIMEOUT => self::CONNECTION_TIMEOUT,
            // Manual HTTP error handling allows for custom retry logic and better error messages.
            // Note: Moodle's adhoc task system provides automatic retry for failed tasks.
            RequestOptions::HTTP_ERRORS => false,
            RequestOptions::HEADERS => [
                'User-Agent' => self::get_user_agent(),
            ],
        ]);
    }

    /**
     * Prepare request options for Guzzle.
     *
     * @param string $method HTTP method
     * @param array $data Request data
     * @param string $token Authentication token
     * @param array $attachments File attachments (optional)
     * @return array Request options
     */
    private static function prepare_request_options(string $method, array $data, string $token, array $attachments = []): array {
        $options = [
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer ' . $token,
            ],
        ];

        // Handle attachments for POST/PUT requests.
        if (!empty($attachments) && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            // Use multipart form data when attachments are present.
            $multipart = [];

            // Add regular data fields.
            foreach ($data as $key => $value) {
                if (is_array($value)) {
                    // Handle nested arrays by JSON encoding.
                    $multipart[] = [
                        'name' => $key,
                        'contents' => json_encode($value),
                        'headers' => ['Content-Type' => 'application/json'],
                    ];
                } else {
                    $multipart[] = [
                        'name' => $key,
                        'contents' => (string)$value,
                    ];
                }
            }

            // Add file attachments.
            foreach ($attachments as $name => $attachment) {
                if (is_array($attachment)) {
                    // Advanced case: attachment is an array with options.
                    $filepath = $attachment['path'] ?? $attachment['filepath'] ?? '';
                    $contenttype = $attachment['content_type'] ?? $attachment['contenttype'] ?? 'application/json';
                    $filename = $attachment['filename'] ?? basename($filepath);
                } else {
                    throw new moodle_exception(
                        'invalidattachment',
                        constants::PLUGIN_NAME,
                        '',
                        null,
                        "Attachment '{$name}' must be an array with path/options"
                    );
                }

                // Validate file exists.
                if (!file_exists($filepath) || !is_readable($filepath)) {
                    throw new moodle_exception('error:file:notfound', constants::PLUGIN_NAME, '', null, $filepath);
                }

                // Create a file stream.
                $stream = fopen($filepath, 'r');
                if ($stream === false) {
                    throw new moodle_exception('error:file:readfailed', constants::PLUGIN_NAME, '', null, $filepath);
                }

                $multipart[] = [
                    'name' => $name,
                    'contents' => $stream,
                    'filename' => $filename,
                    'headers' => ['Content-Type' => $contenttype],
                ];
            }

            $options[RequestOptions::MULTIPART] = $multipart;
            // Don't set a Content-Type header for multipart - Guzzle will set it automatically with a boundary.
        } else {
            // Standard JSON request without attachments.
            $options[RequestOptions::HEADERS]['Content-Type'] = 'application/json';
            $options[RequestOptions::HEADERS]['Accept'] = 'application/json';

            // Add data based on a method.
            if (in_array($method, ['GET', 'DELETE'])) {
                if (!empty($data)) {
                    $options[RequestOptions::QUERY] = $data;
                }
            } else {
                $options[RequestOptions::JSON] = $data;
            }
        }

        return $options;
    }

    /**
     * Process successful response.
     *
     * @param ResponseInterface $response HTTP response object
     * @param string $method HTTP method used
     * @return stdClass Response data
     * @throws moodle_exception If response processing fails
     */
    private static function process_response(ResponseInterface $response, string $method = 'GET'): stdClass {
        return response_handler::process($response, $method);
    }

    /**
     * Handle exceptions from HTTP requests with retry logic.
     *
     * @param GuzzleException $exception The original exception
     * @param string $method HTTP method
     * @param string $endpoint Endpoint path
     * @param array $data Request data
     * @param int $attempt Current attempt number
     * @param string $tracking_key Request tracking key
     * @return stdClass Response data from retry
     * @throws moodle_exception If retry fails or max attempts exceeded
     */
    private static function handle_request_exception(
        GuzzleException $exception,
        string $method,
        string $endpoint,
        array $data,
        int $attempt,
        string $trackingkey,
    ): stdClass {
        $statuscode = null;
        $response = null;
        $shouldretry = false;
        $delay = 1;

        if (method_exists($exception, 'getResponse') && $exception->getResponse()) {
            $response = $exception->getResponse();
            $statuscode = $response->getStatusCode();
        }

        // Handle authentication errors with token refresh.
        if ($statuscode === 401) {
            if ($attempt < self::MAX_RETRY_ATTEMPTS) {
                try {
                    // Use a lock to prevent multiple token refreshes.
                    $lockkey = 'chronifyai_token_refresh';
                    $lock = \core\lock\lock_config::get_lock_factory('chronifyai');

                    if ($acquiredlock = $lock->get_lock($lockkey, 10)) {
                        try {
                            // Check if the token was already refreshed by another process.
                            $currenttokeninfo = auth::get_token_info();
                            $lastrefresh = self::$retrytracking[$trackingkey]['last_attempt'] ?? 0;

                            // Only refresh if the token wasn't refreshed recently.
                            if (!$currenttokeninfo || $currenttokeninfo['created_at'] <= $lastrefresh) {
                                auth::clear_token();
                                auth::get_token(true); // Force refresh.
                            }

                            $shouldretry = true;
                            $delay = 1; // Quick retry after token refresh.
                        } finally {
                            $acquiredlock->release();
                        }
                    }
                } catch (\Exception $retryexception) {
                    // Token refresh failed, don't retry.
                    unset(self::$retrytracking[$trackingkey]);
                    throw new moodle_exception(
                        'retryauthfailed',
                        constants::PLUGIN_NAME,
                        '',
                        null,
                        'Auth retry failed: ' . $retryexception->getMessage()
                    );
                }
            }
        } else if ($response && response_handler::is_retriable_error($statuscode)) {
            // Handle HTTP responses with retry logic.
            if ($attempt < self::MAX_RETRY_ATTEMPTS) {
                $shouldretry = true;
                $delay = response_handler::get_retry_delay($statuscode, $attempt);
            }
        } else if (self::is_connection_error($exception)) {
            // Handle connection/network errors.
            if ($attempt < self::MAX_RETRY_ATTEMPTS) {
                $shouldretry = true;
                $delay = min(30, pow(2, $attempt - 1)); // Exponential backoff with the max 30s.
            }
        } else if (response_handler::is_temporary_network_error($exception->getMessage())) {
            // Check for temporary network errors based on a message.
            if ($attempt < self::MAX_RETRY_ATTEMPTS) {
                $shouldretry = true;
                $delay = min(15, pow(2, $attempt - 1)); // Shorter backoff for network issues.
            }
        }

        // Retry if conditions are met.
        if ($shouldretry && $attempt < self::MAX_RETRY_ATTEMPTS) {
            sleep($delay);
            return self::request($method, $endpoint, $data, $attempt + 1);
        }

        // Clean up tracking and throw the final error.
        unset(self::$retrytracking[$trackingkey]);

        // For HTTP responses, use the response handler to get a proper error message.
        if ($response) {
            $processedresponse = response_handler::process($response, $method);
            throw new moodle_exception('error:api:communicationfailed', constants::PLUGIN_NAME, '', null, $processedresponse->error_message);
        }

        // For connection errors, format a clear message.
        $errormessage = self::format_connection_error($exception, $statuscode);
        throw new moodle_exception('error:api:communicationfailed', constants::PLUGIN_NAME, '', null, $errormessage);
    }

    /**
     * Check if an error is a connection error (not HTTP response).
     *
     * @param GuzzleException $exception The exception
     * @return bool True if the error is a connection error
     */
    private static function is_connection_error(GuzzleException $exception): bool {
        $connectionexceptiontypes = [
            \GuzzleHttp\Exception\ConnectException::class,
            \GuzzleHttp\Exception\RequestException::class,
        ];

        foreach ($connectionexceptiontypes as $exceptionclass) {
            if ($exception instanceof $exceptionclass) {
                // Additional check: make sure it's not an HTTP response.
                if (!method_exists($exception, 'getResponse') || !$exception->getResponse()) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Format a connection error message for better user understanding.
     *
     * @param GuzzleException $exception The exception
     * @param int|null $statuscode HTTP status code if available
     * @return string Formatted error message
     */
    private static function format_connection_error(GuzzleException $exception, ?int $statuscode): string {
        $message = $exception->getMessage();

        if (str_contains($message, 'cURL error')) {
            if (str_contains($message, 'Connection timed out')) {
                return 'Connection timed out while connecting to ChronifyAI API. Please check your internet connection and try again.';
            }
            if (str_contains($message, 'Could not resolve host')) {
                return 'Cannot reach ChronifyAI API server. Please check your DNS settings and internet connection.';
            }
            if (str_contains($message, 'Connection refused')) {
                return 'Connection refused by ChronifyAI API server. The service may be temporarily unavailable.';
            }
        }

        $errormessage = 'ChronifyAI API connection failed: ' . $message;
        if ($statuscode) {
            $errormessage .= ' (HTTP ' . $statuscode . ')';
        }

        return $errormessage;
    }


    /**
     * Get the User-Agent string for API requests.
     *
     * @return string User-Agent string
     */
    private static function get_user_agent(): string {
        global $CFG;
        $moodleversion = $CFG->version ?? 'unknown';
        $pluginversion = get_config('local_chronifyai', 'version') ?? 'unknown';

        return "Moodle/{$moodleversion} ChronifyAI/{$pluginversion}";
    }

    /**
     * Validate API response structure.
     *
     * @param stdClass $response The API response
     * @param array $requiredFields Required fields in the response
     * @return bool True if the response is valid
     * @throws moodle_exception If response validation fails
     */
    public static function validate_response(stdClass $response, array $requiredfields = []): bool {
        foreach ($requiredfields as $field) {
            if (!isset($response->$field)) {
                throw new moodle_exception(
                    'invalidresponse',
                    constants::PLUGIN_NAME,
                    '',
                    null,
                    "Missing required field: {$field}"
                );
            }
        }

        return true;
    }

    /**
     * Clear retry tracking for all requests.
     * Useful for cleanup or testing purposes.
     */
    public static function clear_retry_tracking(): void {
        self::$retrytracking = [];
    }

    /**
     * Get current retry statistics.
     * Useful for monitoring and debugging.
     *
     * @return array Current retry tracking data
     */
    public static function get_retry_stats(): array {
        return self::$retrytracking;
    }

    /**
     * Get API health status.
     *
     * Makes a lightweight request to check if the API is accessible.
     *
     * @return bool True if API is healthy, false otherwise
     */
    public static function check_api_health(): bool {
        try {
            // Use a lightweight endpoint for a health check.
            $response = self::get('health');
            return isset($response->status) && $response->status === 'ok';
        } catch (\Exception $e) {
            return false;
        }
    }
}
