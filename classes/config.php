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

namespace local_chronifyai;

/**
 * ChronifyAI Configuration Class.
 *
 * @package   local_chronifyai
 * @copyright 2025 SEBALE Innovations (http://sebale.net)
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class config {
    /**
     * Get a configuration value for the plugin.
     *
     * Retrieves a configuration value from Moodle's plugin configuration system.
     * Returns the default value if the configuration key doesn't exist or is false.
     *
     * @param string $name The configuration key name
     * @param mixed $default The default value to return if config key is not found
     * @return mixed The configuration value or default value
     *
     */
    public static function get(string $name, mixed $default = null): mixed {
        $value = get_config(constants::PLUGIN_NAME, $name);
        return $value !== false ? $value : $default;
    }

    /**
     * Set a configuration value for the plugin with audit logging.
     *
     * Saves a configuration value to Moodle's plugin configuration system
     * and logs the change to the config log for audit trail purposes.
     *
     * @param string $name The configuration key name
     * @param mixed $value The value to save
     * @return bool True if the configuration was saved successfully
     *
     */
    public static function set(string $name, mixed $value): bool {
        // Get the old value before changing it.
        $oldvalue = get_config(constants::PLUGIN_NAME, $name);

        // Handle null/false values properly.
        if ($oldvalue === false) {
            $oldvalue = null;
        }

        // Save the new value.
        $result = set_config($name, $value, constants::PLUGIN_NAME);

        // Log the change for audit trail.
        if ($result) {
            add_to_config_log($name, $oldvalue, $value, constants::PLUGIN_NAME);
        }

        return $result;
    }

    /**
     * Get the ChronifyAI API base URL.
     *
     * Returns the configured API base URL or the default URL if not configured.
     * The URL is used as the base for all API endpoints.
     *
     * @return string The API base URL (without trailing slash)
     *
     */
    public static function get_api_base_url(): string {
        return (string) self::get('api_base_url', constants::DEFAULT_API_BASE_URL);
    }

    /**
     * Get the OAuth client ID for API authentication.
     *
     * Returns the configured client ID used for OAuth authentication with the ChronifyAI API.
     * This value is required for API access and should be kept secure.
     *
     * @return string|null The client ID or null if not configured
     *
     */
    public static function get_client_id(): ?string {
        $value = self::get('client_id');
        return $value !== null ? (string) $value : null;
    }

    /**
     * Get the OAuth client secret for API authentication.
     *
     * Returns the configured client secret used for OAuth authentication with
     * the ChronifyAI API. This value is required for API access and should be
     * kept secure.
     *
     * @return string|null The client secret or null if not configured
     *
     */
    public static function get_client_secret(): ?string {
        $value = self::get('client_secret');
        return $value !== null ? (string) $value : null;
    }

    /**
     * Get the email address to use for API authentication.
     *
     * Returns the configured email address for API authentication. If not
     * specifically configured, falls back to the current user's email address.
     * This email is used in the SSO authentication process.
     *
     * @return string The email address to use for authentication
     *
     */
    public static function get_email(): string {
        global $USER;
        return (string) self::get('api_email', $USER->email ?? '');
    }

    /**
     * Check if the ChronifyAI plugin is enabled.
     *
     * Returns whether the plugin functionality is enabled. When disabled,
     * the plugin should not perform any API operations or show active features.
     *
     * @return bool True if the plugin is enabled, false otherwise
     *
     */
    public static function is_enabled(): bool {
        return (bool) self::get('enabled', false);
    }

    /**
     * Check if plugin has minimum required configuration.
     *
     * Validates that all required configuration values are present and valid
     * for the plugin to function properly.
     *
     * @return bool True if all required config is present, false otherwise
     *
     */
    public static function has_valid_config(): bool {
        // Check if the plugin is enabled.
        if (!self::is_enabled()) {
            return false;
        }

        // Check required configuration values.
        $clientid = self::get_client_id();
        $clientsecret = self::get_client_secret();
        $baseurl = self::get_api_base_url();

        return !empty($clientid) &&
               !empty($clientsecret) &&
               !empty($baseurl) &&
               filter_var($baseurl, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Get configuration validation errors.
     *
     * Returns an array of configuration validation errors that prevent
     * the plugin from functioning correctly.
     *
     * @return array<string> Array of error messages, empty if config is valid
     *
     */
    public static function get_config_errors(): array {
        $errors = [];

        if (!self::is_enabled()) {
            $errors[] = get_string('status:plugin:disabled', 'local_chronifyai');
            return $errors; // No point checking another config if disabled.
        }

        $clientid = self::get_client_id();
        if (empty($clientid)) {
            $errors[] = get_string('error:auth:clientidmissing', 'local_chronifyai');
        }

        $clientsecret = self::get_client_secret();
        if (empty($clientsecret)) {
            $errors[] = get_string('error:auth:clientsecretmissing', 'local_chronifyai');
        }

        $baseurl = self::get_api_base_url();
        if (empty($baseurl)) {
            $errors[] = get_string('error_missing_api_url', 'local_chronifyai');
        } else if (filter_var($baseurl, FILTER_VALIDATE_URL) === false) {
            $errors[] = get_string('error_invalid_api_url', 'local_chronifyai');
        }

        // Validate email configuration (may use current user's email as fallback).
        $email = self::get_email();
        if (empty($email)) {
            $errors[] = get_string('error_missing_email', 'local_chronifyai');
        } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = get_string('error_invalid_email', 'local_chronifyai');
        }

        return $errors;
    }
}
