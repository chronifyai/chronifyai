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

use local_chronifyai\config;

/**
 * Wizard step 3 form - Features configuration.
 *
 * @package    local_chronifyai
 * @copyright  2025 SEBALE Innovations (http://sebale.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class wizard_step3_form extends \moodleform {
    #[\Override]
    protected function definition(): void {
        $mform = $this->_form;

        // Privacy Warning - Prominent notice about data transmission.
        $privacywarning = html_writer::div(
            html_writer::tag('strong', get_string('privacy:setup:warning', 'local_chronifyai')) .
            get_string('privacy:setup:requirements', 'local_chronifyai'),
            'alert alert-warning'
        );
        $mform->addElement('html', $privacywarning);

        // Enable ChronifyAI features checkbox.
        $mform->addElement('checkbox', 'enabled', get_string('settings:features:enabled', 'local_chronifyai'));
        $mform->setType('enabled', PARAM_BOOL);
        $mform->addHelpButton('enabled', 'settings:features:enabled', 'local_chronifyai');
        $mform->setDefault('enabled', config::is_enabled());

        // Data transmission acknowledgment checkbox (required when enabling).
        $mform->addElement(
            'advcheckbox',
            'data_transmission_acknowledged',
            get_string('acknowledge_data_transmission', 'local_chronifyai'),
            get_string('acknowledge_data_transmission_desc', 'local_chronifyai')
        );
        $mform->setType('data_transmission_acknowledged', PARAM_BOOL);

        // Make acknowledgment required only when enabling the plugin.
        $mform->disabledIf('data_transmission_acknowledged', 'enabled');

        // Action buttons.
        $buttonarray = [];
        $buttonarray[] = $mform->createElement('submit', 'save', get_string('ui:button:save', 'local_chronifyai'));
        $buttonarray[] = $mform->createElement('submit', 'next', get_string('ui:button:saveandcomplete', 'local_chronifyai'));
        $mform->addGroup($buttonarray, 'buttongroup', '', [' '], false);
    }

    #[\Override]
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);

        // If enabling the plugin, require acknowledgment.
        if (!empty($data['enabled']) && empty($data['data_transmission_acknowledged'])) {
            $errors['data_transmission_acknowledged'] = get_string('error:must_acknowledge', 'local_chronifyai');
        }

        return $errors;
    }
}
