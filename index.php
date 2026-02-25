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
 * ChronifyAI plugin installation/configuration wizard.
 *
 * @package    local_chronifyai
 * @copyright  2025 SEBALE Innovations (http://sebale.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/externallib.php');

use local_chronifyai\config;
use local_chronifyai\constants;
use local_chronifyai\form\wizard_step2_form;
use local_chronifyai\form\wizard_step3_form;
use local_chronifyai\output\layout;

// Setup admin external page - this integrates with Moodle's admin interface.
admin_externalpage_setup(constants::PLUGIN_NAME);

// Get the current step from the URL parameter.
$step = optional_param('step', 1, PARAM_INT);
$step = max(1, min(5, $step)); // Ensure the step is between 1-5.

// Set up the page.
$pageurl = new moodle_url('/local/chronifyai/index.php', ['step' => $step]);
$PAGE->set_url($pageurl);
$PAGE->set_title(get_string('wizard:common:title', 'local_chronifyai'));
$PAGE->set_heading(get_string('wizard:common:title', 'local_chronifyai'));

// Helper to get step URLs.
$stepurls = [
    'step1_url' => new moodle_url('/local/chronifyai/index.php', ['step' => 1]),
    'step2_url' => new moodle_url('/local/chronifyai/index.php', ['step' => 2]),
    'step3_url' => new moodle_url('/local/chronifyai/index.php', ['step' => 3]),
    'step4_url' => new moodle_url('/local/chronifyai/index.php', ['step' => 4]),
    'step5_url' => new moodle_url('/local/chronifyai/index.php', ['step' => 5]),
];

// Initialize form variable to avoid undefined variable error.
$form = null;

// Process form submissions based on the current step.
switch ($step) {
    case 2:
        require_once($CFG->dirroot . '/local/chronifyai/classes/form/wizard_step2_form.php');
        $form = new wizard_step2_form($pageurl);

        if ($form->is_cancelled()) {
            redirect($stepurls['step1_url']);
        } else if ($formdata = $form->get_data()) {
            // Save settings with audit logging.
            config::set('api_base_url', $formdata->api_base_url);
            config::set('client_id', $formdata->client_id);
            config::set('client_secret', $formdata->client_secret);

            // Determine the next step based on which button was clicked.
            if (isset($formdata->next)) {
                redirect(
                    $stepurls['step3_url'],
                    get_string('status:settings:saved', 'local_chronifyai'),
                    null,
                    \core\output\notification::NOTIFY_SUCCESS
                );
            } else {
                redirect(
                    $pageurl,
                    get_string('status:settings:saved', 'local_chronifyai'),
                    null,
                    \core\output\notification::NOTIFY_SUCCESS
                );
            }
        }
        break;

    case 3:
        require_once($CFG->dirroot . '/local/chronifyai/classes/form/wizard_step3_form.php');
        $form = new wizard_step3_form($pageurl);

        if ($form->is_cancelled()) {
            redirect($stepurls['step1_url']);
        } else if ($formdata = $form->get_data()) {
            // Save feature settings with audit logging.
            config::set('enabled', $formdata->enabled ? 1 : 0);

            // Determine the next step based on which button was clicked.
            if (isset($formdata->next)) {
                redirect(
                    $stepurls['step4_url'],
                    get_string('status:settings:saved', 'local_chronifyai'),
                    null,
                    \core\output\notification::NOTIFY_SUCCESS
                );
            } else {
                redirect(
                    $pageurl,
                    get_string('status:settings:saved', 'local_chronifyai'),
                    null,
                    \core\output\notification::NOTIFY_SUCCESS
                );
            }
        }
        break;
}

/**
 * Get or create a web service token for the ChronifyAI service.
 *
 * Looks up the ChronifyAI external service, ensures the current admin user
 * is an authorized user, and returns an existing or newly created token.
 *
 * @return array{token: string, error: string} Token string on success, error message on failure.
 */
function local_chronifyai_get_or_create_token(): array {
    global $DB, $USER;

    $result = ['token' => '', 'error' => ''];

    // Find the ChronifyAI external service.
    $service = $DB->get_record('external_services', ['shortname' => 'local_chronifyai_service']);
    if (!$service) {
        $result['error'] = get_string('wizard:step4:error:noservice', 'local_chronifyai');
        return $result;
    }

    // Check if a token already exists for this user and service.
    $existingtoken = $DB->get_record('external_tokens', [
        'userid' => $USER->id,
        'externalserviceid' => $service->id,
        'tokentype' => EXTERNAL_TOKEN_PERMANENT,
    ]);

    if ($existingtoken) {
        $result['token'] = $existingtoken->token;
        return $result;
    }

    // Add the user as an authorized user if not already.
    $authorizeduser = $DB->get_record('external_services_users', [
        'externalserviceid' => $service->id,
        'userid' => $USER->id,
    ]);

    if (!$authorizeduser) {
        $serviceuser = new stdClass();
        $serviceuser->externalserviceid = $service->id;
        $serviceuser->userid = $USER->id;
        $serviceuser->timecreated = time();
        $DB->insert_record('external_services_users', $serviceuser);
    }

    // Generate a new permanent token.
    $token = external_generate_token(
        EXTERNAL_TOKEN_PERMANENT,
        $service->id,
        $USER->id,
        context_system::instance()
    );

    $result['token'] = $token;
    return $result;
}

// Prepare step-specific content.
$content = '';

switch ($step) {
    case 1:
        $stepdata = [
            'step_title' => get_string('wizard:step1:title', 'local_chronifyai'),
            'step_description' => get_string('wizard:step1:description', 'local_chronifyai'),
            'next_url' => $stepurls['step2_url']->out(),
            'prereq_step1' => get_string('wizard:step1:prereq:step1', 'local_chronifyai'),
            'prereq_step2' => get_string('wizard:step1:prereq:step2', 'local_chronifyai'),
            'prereq_step3' => get_string('wizard:step1:prereq:step3', 'local_chronifyai'),
            'prereq_step4' => get_string('wizard:step1:prereq:step4', 'local_chronifyai'),
        ];
        $content = $OUTPUT->render_from_template('local_chronifyai/wizard_step1', $stepdata);
        break;

    case 2:
        $formhtml = $form->render();
        $stepdata = [
            'form' => $formhtml,
            'previous_url' => $stepurls['step1_url']->out(),
        ];
        $content = $OUTPUT->render_from_template('local_chronifyai/wizard_step2', $stepdata);
        break;

    case 3:
        $formhtml = $form->render();
        $stepdata = [
            'form' => $formhtml,
            'previous_url' => $stepurls['step2_url']->out(),
        ];
        $content = $OUTPUT->render_from_template('local_chronifyai/wizard_step3', $stepdata);
        break;

    case 4:
        // Auto-create token for the ChronifyAI service.
        $tokenresult = local_chronifyai_get_or_create_token();

        $stepdata = [
            'site_url' => $CFG->wwwroot,
            'token' => $tokenresult['token'],
            'has_error' => !empty($tokenresult['error']),
            'error_message' => $tokenresult['error'],
            'next_url' => $stepurls['step5_url']->out(),
            'previous_url' => $stepurls['step3_url']->out(),
            'instruction_step1' => get_string('wizard:step4:instruction:step1', 'local_chronifyai'),
            'instruction_step2' => get_string('wizard:step4:instruction:step2', 'local_chronifyai'),
            'instruction_step3' => get_string('wizard:step4:instruction:step3', 'local_chronifyai'),
            'instruction_step4' => get_string('wizard:step4:instruction:step4', 'local_chronifyai'),
        ];
        $content = $OUTPUT->render_from_template('local_chronifyai/wizard_step4', $stepdata);
        break;

    case 5:
        $stepdata = [
            'step_title' => get_string('wizard:step5:title', 'local_chronifyai'),
            'dashboard_url' => get_string('wizard:dashboard:url', 'local_chronifyai'),
        ];
        $content = $OUTPUT->render_from_template('local_chronifyai/wizard_step5', $stepdata);
        break;
}

// Get the renderer.
$renderer = $PAGE->get_renderer('local_chronifyai');

// Create a renderable settings layout and render it.
$layout = new layout($step, $stepurls, $content);

// Output the page.
echo $OUTPUT->header();
echo $renderer->render($layout);
echo $OUTPUT->footer();
