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

use local_chronifyai\config;
use local_chronifyai\constants;
use local_chronifyai\form\wizard_step2_form;
use local_chronifyai\form\wizard_step3_form;
use local_chronifyai\output\layout;

// Setup admin external page - this integrates with Moodle's admin interface.
admin_externalpage_setup(constants::PLUGIN_NAME);

// Get the current step from the URL parameter.
$step = optional_param('step', 1, PARAM_INT);
$step = max(1, min(4, $step)); // Ensure the step is between 1-4.

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
                redirect($stepurls['step3_url'], get_string('status:settings:saved', 'local_chronifyai'),
                    null, \core\output\notification::NOTIFY_SUCCESS);
            } else {
                redirect($pageurl, get_string('status:settings:saved', 'local_chronifyai'),
                    null, \core\output\notification::NOTIFY_SUCCESS);
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
                redirect($stepurls['step4_url'], get_string('status:settings:saved', 'local_chronifyai'),
                    null, \core\output\notification::NOTIFY_SUCCESS);
            } else {
                redirect($pageurl, get_string('status:settings:saved', 'local_chronifyai'),
                    null, \core\output\notification::NOTIFY_SUCCESS);
            }
        }
        break;
}

// Prepare step-specific content.
$content = '';

switch ($step) {
    case 1:
        $stepdata = [
            'step_title' => get_string('wizard:step1:title', 'local_chronifyai'),
            'step_description' => get_string('wizard:step1:description', 'local_chronifyai'),
            'next_url' => $stepurls['step2_url']->out(),
        ];
        $content = $OUTPUT->render_from_template('local_chronifyai/wizard_step1', $stepdata);
        break;

    case 2:
        $formhtml = $form->render();
        $stepdata = [
            'form' => $formhtml,
        ];
        $content = $OUTPUT->render_from_template('local_chronifyai/wizard_step2', $stepdata);
        break;

    case 3:
        $formhtml = $form->render();
        $stepdata = [
            'form' => $formhtml,
        ];
        $content = $OUTPUT->render_from_template('local_chronifyai/wizard_step3', $stepdata);
        break;

    case 4:
        $stepdata = [
            'step_title' => get_string('wizard:step4:title', 'local_chronifyai'),
            'dashboard_url' => get_string('wizard:dashboard:url', 'local_chronifyai'),
            'settings_url' => new moodle_url('/admin/settings.php', ['section' => 'local_chronifyai']),
        ];
        $content = $OUTPUT->render_from_template('local_chronifyai/wizard_step4', $stepdata);
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
