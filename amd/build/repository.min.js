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
 * Repository module for ChronifyAI AJAX calls.
 *
 * @module     local_chronifyai/repository
 * @copyright  2025 SEBALE Innovations (http://sebale.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';

/**
 * Verify connection to the ChronifyAI API.
 *
 * @param {string} apiBaseUrl The API base URL
 * @param {string} clientId The client ID
 * @param {string} clientSecret The client secret
 * @returns {Promise} Promise resolved with the test result
 */
export const verifyConnection = (apiBaseUrl, clientId, clientSecret) => {
    const request = {
        methodname: 'local_chronifyai_verify_connection',
        args: {
            apibaseurl: apiBaseUrl,
            clientid: clientId,
            clientsecret: clientSecret,
        },
    };

    return Ajax.call([request])[0];
};
