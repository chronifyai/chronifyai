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

namespace local_chronifyai\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

use core\exception\moodle_exception;
use local_chronifyai\config;
use local_chronifyai\constants;

/**
 * Wizard step 2 form - API Settings configuration.
 *
 * @package    local_chronifyai
 * @copyright  2025 SEBALE Innovations (http://sebale.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class wizard_step2_form extends \moodleform {
    #[\Override]
    protected function definition(): void {
        $mform = $this->_form;

        // API base URL field.
        $mform->addElement('text', 'api_base_url', get_string('settings:api:apibaseurl', constants::PLUGIN_NAME));
        $mform->setType('api_base_url', PARAM_URL);
        $mform->addRule('api_base_url', get_string('required'), 'required', null, 'client');
        $mform->addHelpButton('api_base_url', 'settings:api:apibaseurl', constants::PLUGIN_NAME);
        $mform->setDefault('api_base_url', config::get('api_base_url', constants::DEFAULT_API_BASE_URL));

        // Client ID field.
        $mform->addElement('text', 'client_id', get_string('settings:authentication:clientid', constants::PLUGIN_NAME));
        $mform->setType('client_id', PARAM_TEXT);
        $mform->addRule('client_id', get_string('required'), 'required', null, 'client');
        $mform->addHelpButton('client_id', 'settings:authentication:clientid', constants::PLUGIN_NAME);
        $mform->setDefault('client_id', config::get_client_id() ?? '');

        // Client Secret field.
        $mform->addElement(
            'passwordunmask',
            'client_secret',
            get_string('settings:authentication:clientsecret', constants::PLUGIN_NAME)
        );
        $mform->setType('client_secret', PARAM_TEXT);
        $mform->addRule('client_secret', get_string('required'), 'required', null, 'client');
        $mform->addHelpButton('client_secret', 'settings:authentication:clientsecret', constants::PLUGIN_NAME);
        $mform->setDefault('client_secret', config::get_client_secret() ?? '');

        // Test connection block.
        $mform->addElement('html', $this->render_test_connection_block());

        // Action buttons.
        $buttonarray = [];
        $buttonarray[] = $mform->createElement('submit', 'save', get_string('ui:button:save', constants::PLUGIN_NAME));
        $buttonarray[] = $mform->createElement('submit', 'next', get_string('ui:button:saveandnext', constants::PLUGIN_NAME));
        $mform->addGroup($buttonarray, 'buttongroup', '', [' '], false);
    }

    #[\Override]
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);

        // Validate URL format.
        if (!empty($data['api_base_url'])) {
            if (!preg_match('|^https?://|', $data['api_base_url'])) {
                $errors['api_base_url'] = get_string('error:invalidurl', constants::PLUGIN_NAME);
            }
        }

        return $errors;
    }

    /**
     * Render test connection block from template.
     *
     * @return string Rendered HTML.
     * @throws moodle_exception
     */
    private function render_test_connection_block(): string {
        global $OUTPUT;
        return $OUTPUT->render_from_template('local_chronifyai/test_connection_block', []);
    }
}
