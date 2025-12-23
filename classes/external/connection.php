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

/**
 * External API for testing ChronifyAI API connection.
 *
 * @package    local_chronifyai
 * @copyright  2025 SEBALE Innovations (http://sebale.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_chronifyai\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use local_chronifyai\api\auth;
use local_chronifyai\api\client;

/**
 * External API for testing ChronifyAI API connection.
 */
class connection extends external_api {
    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function verify_parameters(): external_function_parameters {
        return new external_function_parameters([
            'apibaseurl' => new external_value(PARAM_URL, 'API base URL'),
            'clientid' => new external_value(PARAM_TEXT, 'Client ID'),
            'clientsecret' => new external_value(PARAM_TEXT, 'Client secret'),
        ]);
    }

    /**
     * Verify connection to ChronifyAI API with provided credentials.
     *
     * This method validates that the supplied API credentials are correct
     * by attempting authentication and confirming API access works.
     * It provides actionable error messages to guide users on fixing issues.
     *
     * @param string $apibaseurl The API base URL
     * @param string $clientid The client ID
     * @param string $clientsecret The client secret
     * @return array Result array with success status and message
     */
    public static function verify(string $apibaseurl, string $clientid, string $clientsecret): array {
        // Release session lock immediately - we make external API calls and don't need session data.
        // This prevents blocking other requests during potentially slow operations.
        \core\session\manager::write_close();

        // Validate parameters.
        $params = self::validate_parameters(self::verify_parameters(), [
            'apibaseurl' => $apibaseurl,
            'clientid' => $clientid,
            'clientsecret' => $clientsecret,
        ]);

        // Check permissions.
        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('moodle/site:config', $context);

        // Validate inputs.
        if (empty($params['apibaseurl']) || empty($params['clientid']) || empty($params['clientsecret'])) {
            return [
                'success' => false,
                'message' => get_string('connection:test:allfieldsrequired', 'local_chronifyai'),
            ];
        }

        // Note: URL format validation is handled by PARAM_URL parameter type.

        try {
            // Temporarily override configuration values for testing.
            $originalconfig = [
                'api_base_url' => get_config('local_chronifyai', 'api_base_url'),
                'client_id' => get_config('local_chronifyai', 'client_id'),
                'client_secret' => get_config('local_chronifyai', 'client_secret'),
            ];

            // Set test configuration.
            set_config('api_base_url', $params['apibaseurl'], 'local_chronifyai');
            set_config('client_id', $params['clientid'], 'local_chronifyai');
            set_config('client_secret', $params['clientsecret'], 'local_chronifyai');

            try {
                // Test authentication by requesting a token.
                $token = auth::get_token(true); // Force fresh token request.

                if (empty($token)) {
                    throw new moodle_exception('error:auth:emptytokenresponse', 'local_chronifyai');
                }

                // Verify token works by making a simple API call.
                $courses = client::get_courses(['limit' => 1]);

                return [
                    'success' => true,
                    'message' => get_string('connection:test:success', 'local_chronifyai'),
                ];
            } catch (\Exception $e) {
                // Parse error message for more user-friendly feedback.
                if ($e instanceof \moodle_exception) {
                    switch ($e->errorcode) {
                        case 'error:api:invalidconfig':
                            $errormessage = get_string('connection:test:configerror', 'local_chronifyai');
                            break;
                        case 'error:auth:failed':
                            $errormessage = get_string('connection:test:authfailed', 'local_chronifyai');
                            break;
                        case 'error:auth:approvalrequired':
                            $errormessage = get_string('connection:test:approvalrequired', 'local_chronifyai');
                            break;
                        case 'sso_request_failed':
                        case 'error:api:communicationfailed':
                            $errormessage = get_string('connection:test:networkerror', 'local_chronifyai');
                            break;
                        default:
                            $errormessage = $e->getMessage();
                            break;
                    }
                } else {
                    // For non-moodle exceptions, use the message directly.
                    $errormessage = $e->getMessage();
                }

                return [
                    'success' => false,
                    'message' => $errormessage,
                ];
            }
        } finally {
            // Restore original configuration values.
            if (isset($originalconfig)) {
                foreach ($originalconfig as $key => $value) {
                    if ($value !== false) {
                        set_config($key, $value, 'local_chronifyai');
                    } else {
                        unset_config($key, 'local_chronifyai');
                    }
                }
            }
        }
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function verify_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether the connection was successful'),
            'message' => new external_value(PARAM_TEXT, 'Result message'),
        ]);
    }
}
