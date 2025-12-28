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
 * Connection test module for ChronifyAI configuration wizard.
 *
 * @module     local_chronifyai/connection_test
 * @copyright  2025 SEBALE Innovations (http://sebale.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Notification from 'core/notification';
import {getString} from 'core/str';
import {verifyConnection} from "./repository";

// Cache for loaded strings.
let strings = null;

/**
 * Load all required strings at once.
 *
 * @returns {Promise} Promise resolved with strings object
 */
const loadStrings = async() => {
    if (strings === null) {
        const stringKeys = [
            {key: 'connection:test:fieldsrequired', component: 'local_chronifyai'},
            {key: 'connection:test:testing', component: 'local_chronifyai'},
            {key: 'connection:test:inprogress', component: 'local_chronifyai'},
            {key: 'connection:test:unexpectederror', component: 'local_chronifyai'},
        ];

        const loadedStrings = await getString(stringKeys);

        strings = {
            fieldsRequired: loadedStrings[0],
            testing: loadedStrings[1],
            inProgress: loadedStrings[2],
            unexpectedError: loadedStrings[3],
        };
    }

    return strings;
};

/**
 * Initialize the connection test functionality.
 *
 * @param {string} buttonSelector The selector for the test connection button
 */
export const init = (buttonSelector = '#test-connection-btn') => {
    const testButton = document.querySelector(buttonSelector);

    if (!testButton) {
        return;
    }

    testButton.addEventListener('click', async(event) => {
        event.preventDefault();
        await handleTestConnection(testButton);
    });
};

/**
 * Handle the test connection button click.
 *
 * @param {HTMLElement} button The test connection button
 */
const handleTestConnection = async(button) => {
    // Load strings first.
    const strs = await loadStrings();

    // Get form values.
    const apiBaseUrl = document.querySelector('input[name="api_base_url"]')?.value || '';
    const clientId = document.querySelector('input[name="client_id"]')?.value || '';
    const clientSecret = document.querySelector('input[name="client_secret"]')?.value || '';

    // Get result container.
    const resultContainer = document.querySelector('#connection-result');

    if (!resultContainer) {
        return;
    }

    // Validate inputs.
    if (!apiBaseUrl || !clientId || !clientSecret) {
        showResult(resultContainer, false, strs.fieldsRequired);
        return;
    }

    // Show loading state.
    button.disabled = true;
    const originalText = button.textContent;
    button.textContent = strs.testing;

    // Show loading message.
    resultContainer.classList.remove('success', 'error');
    resultContainer.classList.add('loading');
    resultContainer.innerHTML = '<span class="icon">⏳</span> ' + escapeHtml(strs.inProgress);

    try {
        // Call the repository function.
        const result = await verifyConnection(apiBaseUrl, clientId, clientSecret);

        // Show result.
        showResult(resultContainer, result.success, result.message);
    } catch (error) {
        // Show error notification.
        showResult(resultContainer, false, strs.unexpectedError + ': ' + error.message);
        await Notification.exception(error);
    } finally {
        // Reset button state.
        button.disabled = false;
        button.textContent = originalText;
    }
};

/**
 * Display the connection test result.
 *
 * @param {HTMLElement} container The result container element
 * @param {boolean} success Whether the test was successful
 * @param {string} message The result message
 */
const showResult = (container, success, message) => {
    // Remove all state classes.
    container.classList.remove('success', 'error', 'loading');

    // Add appropriate state class.
    container.classList.add(success ? 'success' : 'error');

    // Set icon based on success/failure.
    const icon = success ? '✓' : '✗';

    // Update content.
    container.innerHTML = `<span class="icon">${icon}</span> ${escapeHtml(message)}`;
};

/**
 * Escape HTML to prevent XSS.
 *
 * @param {string} text The text to escape
 * @returns {string} The escaped text
 */
const escapeHtml = (text) => {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
};
