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

use cache;
use cache_session;
use moodle_exception;
use stdClass;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use local_chronifyai\config;
use local_chronifyai\constants;

/**
 * Authentication and token management for ChronifyAI API.
 *
 * This class handles all authentication-related operations including
 * - SSO authentication with ChronifyAI API
 * - Token management and caching
 * - Token validation and refresh
 *
 * @package   local_chronifyai
 * @copyright 2025 SEBALE Innovations (http://sebale.net)
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class auth {
    /** @var int Default token lifetime buffer in seconds (subtract from actual lifetime) */
    private const TOKEN_LIFETIME_BUFFER = 60;

    /** @var int Maximum token lifetime in seconds (24 hours) */
    private const MAX_TOKEN_LIFETIME = 86400;

    /** @var int Minimum token lifetime in seconds (5 minutes) */
    private const MIN_TOKEN_LIFETIME = 300;

    /**
     * Get a valid authentication token.
     *
     * Attempts to retrieve a cached token first. If no valid cached token exists,
     * request a new token via SSO authentication.
     *
     * @param bool $forcerefresh Force a new token request even if a cached token exists
     * @return string Valid authentication token
     * @throws moodle_exception If authentication fails or configuration is invalid
     *
     */
    public static function get_token(bool $forcerefresh = false): string {
        // Use locking to prevent concurrent token refresh issues.
        $lockkey = 'chronifyai_token_get';
        $lock = \core\lock\lock_config::get_lock_factory('chronifyai');

        if ($acquiredlock = $lock->get_lock($lockkey, 10)) {
            try {
                if (!$forcerefresh) {
                    $token = self::get_cached_token();
                    if ($token !== null) {
                        return $token;
                    }
                }

                // Request a new token via SSO.
                $tokendata = self::request_sso_token();

                // Validate token before caching.
                if (empty($tokendata->token)) {
                    throw new moodle_exception(
                        'error:auth:emptytokenresponse',
                        'local_chronifyai',
                        '',
                        null,
                        'Token response does not contain valid token.'
                    );
                }

                // Cache the new token.
                self::cache_token($tokendata);

                return $tokendata->token;
            } finally {
                $acquiredlock->release();
            }
        } else {
            throw new moodle_exception(
                'error:auth:tokenlockfailed',
                'local_chronifyai',
                '',
                null,
                'Could not acquire lock for token operations.'
            );
        }
    }


    /**
     * Request a new authentication token via SSO.
     *
     * Performs SSO authentication with the ChronifyAI API using client credentials
     * and HMAC signature verification.
     *
     * @return stdClass Token response object containing token and metadata
     * @throws moodle_exception If SSO authentication fails
     *
     */
    public static function request_sso_token(): stdClass {
        global $CFG;

        // Validate configuration.
        if (!config::has_valid_config()) {
            $errors = config::get_config_errors();
            throw new moodle_exception(
                'error:api:invalidconfig',
                'local_chronifyai',
                '',
                null,
                implode(', ', $errors)
            );
        }

        $endpoint = rtrim(config::get_api_base_url(), '/') . '/' . endpoints::AUTH;
        $timestamp = time();

        // Prepare SSO payload.
        $payload = [
            'email' => config::get_email(),
            'timestamp' => $timestamp,
            'client_id' => config::get_client_id(),
            'site_url' => $CFG->wwwroot,
        ];

        // Generate HMAC signature.
        $signaturedata = $payload['email'] . $payload['timestamp'] . $payload['site_url'];
        $payload['signature'] = hash_hmac('sha256', $signaturedata, config::get_client_secret());

        $maxattempts = 3;
        $attempt = 1;

        while ($attempt <= $maxattempts) {
            try {
                // Create a Guzzle client.
                $client = request::create_client();

                // Make SSO request.
                $response = $client->post($endpoint, [
                    RequestOptions::JSON => $payload,
                    RequestOptions::HEADERS => [
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                    ],
                ]);

                $httpcode = $response->getStatusCode();
                $responsebody = $response->getBody()->getContents();

                // Handle successful response.
                if ($httpcode === 200) {
                    $result = json_decode($responsebody);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        throw new moodle_exception(
                            'error:validation:invalidjson',
                            'local_chronifyai',
                            '',
                            json_last_error_msg()
                        );
                    }

                    if (empty($result->token)) {
                        throw new moodle_exception(
                            'error:auth:emptytokenresponse',
                            'local_chronifyai',
                            '',
                            null,
                            'No token in SSO response.'
                        );
                    }

                    return $result;
                }

                // Handle 403 - check if it's an approval-required case.
                if ($httpcode === 403) {
                    $errordata = json_decode($responsebody, true);
                    if (!empty($errordata['need_approve'])) {
                        $debuginfo = 'Approval required from ChronifyAI admin panel.';

                        // Add API response for developers.
                        if (!empty($errordata['error'])) {
                            $debuginfo .= ' API message: ' . $errordata['error'];
                        }

                        throw new moodle_exception(
                            'error:auth:approvalrequired',
                            'local_chronifyai',
                            '',
                            null,
                            $debuginfo . " (HTTP $httpcode)"
                        );
                    }
                }

                // Handle retriable HTTP errors (429 Too Many Requests, 503 Service Unavailable).
                // We don't retry on auth failures (401, 403) as these require user intervention.
                if (response_handler::is_retriable_error($httpcode) && $attempt < $maxattempts) {
                    $delay = response_handler::get_retry_delay($httpcode, $attempt);
                    sleep($delay);
                    $attempt++;
                    continue;
                }

                // Handle non-retriable errors.
                $errormessage = 'Authentication failed.';
                $errordata = json_decode($responsebody, true);
                if (isset($errordata['error'])) {
                    $errormessage .= ' API message: ' . $errordata['error'];
                }

                throw new moodle_exception(
                    'error:auth:failed',
                    'local_chronifyai',
                    '',
                    null,
                    $errormessage . " (HTTP $httpcode)"
                );
            } catch (GuzzleException $e) {
                // Check if this is a temporary network error that can be retried.
                if (response_handler::is_temporary_network_error($e->getMessage()) && $attempt < $maxattempts) {
                    $delay = min(15, pow(2, $attempt - 1));
                    sleep($delay);
                    $attempt++;
                    continue;
                }

                throw new moodle_exception(
                    'sso_request_failed',
                    'local_chronifyai',
                    '',
                    null,
                    'Network error during SSO authentication: ' . $e->getMessage()
                );
            }
        }

        // This point is reached if max retries exceeded without a successful response.
        throw new moodle_exception(
            'sso_max_retries',
            'local_chronifyai',
            '',
            null,
            'Maximum retry attempts exceeded for SSO authentication.'
        );
    }

    /**
     * Validate an authentication token.
     *
     * Checks if a token is valid by making a test API call. This can be used
     * to verify token validity before making important API calls.
     *
     * Note: This method is currently not used internally but is provided as a
     * public API for plugin extensions or future enhancements.
     *
     * @param string $token The token to validate
     * @return bool True if the token is valid, false otherwise
     *
     */
    public static function validate_token(string $token): bool {
        if (empty($token)) {
            return false;
        }

        try {
            $endpoint = rtrim(config::get_api_base_url(), '/') . '/courses/count';

            $client = request::create_client();

            $response = $client->get($endpoint, [
                RequestOptions::HEADERS => [
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer ' . $token,
                ],
            ]);

            return $response->getStatusCode() === 200;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get the cache instance.
     *
     * @return cache_session The cache instance
     */
    private static function get_cache(): cache_session {
        return cache::make(constants::CACHE_NAME, constants::CACHE_AREA);
    }

    /**
     * Get a cached authentication token if valid.
     *
     * Retrieves a token from the cache and validates its expiration time.
     * Returns null if no valid cached token exists.
     *
     * @return string|null Cached token or null if not available/expired
     */
    private static function get_cached_token(): ?string {
        $cache = self::get_cache();

        $cacheddata = $cache->get(constants::CACHE_KEY);
        if ($cacheddata === false) {
            return null;
        }

        // Check if the token is still valid.
        if (isset($cacheddata->expires_at) && $cacheddata->expires_at < time()) {
            $cache->delete(constants::CACHE_KEY);
            return null;
        }

        return $cacheddata->token ?? null;
    }

    /**
     * Cache an authentication token.
     *
     * Stores the token in a cache with the appropriate expiration time.
     * Uses token lifetime from response or default value.
     *
     * @param stdClass $tokendata Token response object from SSO
     */
    private static function cache_token(stdClass $tokendata): void {
        $cache = self::get_cache();

        // Calculate expiration time.
        $lifetime = $tokendata->expires_in ?? constants::DEFAULT_TOKEN_LIFETIME;

        // Apply safety buffer and bounds checking.
        $lifetime = max(self::MIN_TOKEN_LIFETIME, min(self::MAX_TOKEN_LIFETIME, $lifetime));
        $expiresat = time() + $lifetime - self::TOKEN_LIFETIME_BUFFER;

        $cachedata = (object)[
            'token' => $tokendata->token,
            'expires_at' => $expiresat,
            'created_at' => time(),
        ];

        $cache->set(constants::CACHE_KEY, $cachedata);
    }

    /**
     * Clear cached authentication token.
     *
     * Removes the token from the cache, forcing a new authentication on the next request.
     * Useful when a token becomes invalid or for security purposes.
     *
     * @return bool True if a token was cleared, false if no token was cached
     *
     */
    public static function clear_token(): bool {
        $cache = self::get_cache();
        return $cache->delete(constants::CACHE_KEY);
    }

    /**
     * Get token information without exposing the actual token.
     *
     * Returns metadata about the current token (expiration, creation time, etc.)
     * without exposing the sensitive token value.
     *
     * @return array|null Token metadata or null if no token cached
     *
     */
    public static function get_token_info(): ?array {
        $cache = self::get_cache();

        $cacheddata = $cache->get(constants::CACHE_KEY);
        if ($cacheddata === false) {
            return null;
        }

        // Return metadata without exposing the token.
        return [
            'has_token' => !empty($cacheddata->token),
            'expires_at' => $cacheddata->expires_at ?? null,
            'created_at' => $cacheddata->created_at ?? null,
            'is_expired' => isset($cacheddata->expires_at) && $cacheddata->expires_at < time(),
            'time_to_expiry' => isset($cacheddata->expires_at) ? max(0, $cacheddata->expires_at - time()) : null,
        ];
    }

    /**
     * Check if authentication is properly configured.
     *
     * Validates that all required authentication configuration is present
     * and appears valid without making any API calls.
     *
     * @return bool True if auth configuration appears valid, false otherwise
     *
     */
    public static function is_configured(): bool {
        return config::has_valid_config();
    }

    /**
     * Refresh authentication token.
     *
     * Forces a new token request and clears any cached token.
     * Useful when the current token becomes invalid or for security purposes.
     *
     * @return string New authentication token
     * @throws moodle_exception If token refresh fails
     *
     */
    public static function refresh_token(): string {
        self::clear_token();
        return self::get_token(true);
    }
}
