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
 * Hook callbacks for local_chronifyai.
 *
 * @package    local_chronifyai
 * @copyright  2025 ChronifyAI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_callbacks {
    /**
     * Display admin notification about external data transmission.
     *
     * @param \core\hook\output\before_footer_html_generation $hook
     */
    public static function before_footer(\core\hook\output\before_footer_html_generation $hook): void {
        global $PAGE;

        // Only show to site admins.
        if (!is_siteadmin()) {
            return;
        }

        // Only on relevant admin pages.
        $pagetype = $PAGE->pagetype;
        $pagepath = $PAGE->url->get_path();

        $isadminpage = (strpos($pagetype, 'admin-') === 0);
        $ispluginpage = (strpos($pagepath, '/local/chronifyai/') !== false);

        if (!$isadminpage && !$ispluginpage) {
            return;
        }

        // Check if plugin is enabled.
        if (!get_config('local_chronifyai', 'enabled')) {
            return;
        }

        // Build URL to privacy documentation.
        $privacyurl = new \moodle_url('/local/chronifyai/PRIVACY.md');

        // Display warning notification.
        \core\notification::warning(
            get_string('warning:externaldatatransmission', 'local_chronifyai', $privacyurl->out())
        );
    }
}
