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
 * Uses icons for visual feedback and backend messages for localized text.
 *
 * @module     local_chronifyai/connection_test
 * @copyright  2025 SEBALE Innovations (http://sebale.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Notification from 'core/notification';
import {verifyConnection} from "./repository";

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
    // Get form values.
    const apiBaseUrl = document.querySelector('input[name="api_base_url"]')?.value || '';
    const clientId = document.querySelector('input[name="client_id"]')?.value || '';
    const clientSecret = document.querySelector('input[name="client_secret"]')?.value || '';

    // Get result container.
    const resultContainer = document.querySelector('#connection-result');

    if (!resultContainer) {
        return;
    }

    // Validate inputs - show warning icon if empty.
    if (!apiBaseUrl || !clientId || !clientSecret) {
        resultContainer.classList.remove('success', 'error', 'loading');
        resultContainer.classList.add('error');
        resultContainer.innerHTML = '<span class="icon" role="img" aria-label="Warning">⚠️</span>';
        return;
    }

    // Show loading state.
    button.disabled = true;

    // Show loading icon.
    resultContainer.classList.remove('success', 'error');
    resultContainer.classList.add('loading');
    resultContainer.innerHTML = '<span class="icon" role="img" aria-label="Loading">⏳</span>';

    try {
        // Call the repository function.
        const result = await verifyConnection(apiBaseUrl, clientId, clientSecret);

        // Show result with backend message (properly localized).
        showResult(resultContainer, result.success, result.message);
    } catch (error) {
        // Show error icon.
        resultContainer.classList.remove('loading', 'success');
        resultContainer.classList.add('error');
        resultContainer.innerHTML = '<span class="icon" role="img" aria-label="Error">✗</span>';

        // Show error notification with details.
        await Notification.exception(error);
    } finally {
        // Reset button state.
        button.disabled = false;
    }
};

/**
 * Display the connection test result.
 *
 * @param {HTMLElement} container The result container element
 * @param {boolean} success Whether the test was successful
 * @param {string} message The result message (from backend, localized)
 */
const showResult = (container, success, message) => {
    // Remove all state classes.
    container.classList.remove('success', 'error', 'loading');

    // Add appropriate state class.
    container.classList.add(success ? 'success' : 'error');

    // Set icon based on success/failure.
    const icon = success ? '✓' : '✗';
    const ariaLabel = success ? 'Success' : 'Error';

    // Update content with icon + backend message.
    container.innerHTML = `<span class="icon" role="img" aria-label="${ariaLabel}">${icon}</span> ${escapeHtml(message)}`;
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
