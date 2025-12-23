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

namespace local_chronifyai\output;

use renderable;
use renderer_base;
use templatable;

/**
 * Renderable wizard layout class.
 *
 * @package    local_chronifyai
 * @copyright  2025 SEBALE Innovations (http://sebale.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class layout implements renderable, templatable {
    /**
     * Current step number.
     *
     * @var int
     */
    private $currentstep;

    /**
     * Array of step URLs.
     *
     * @var array
     */
    private $stepurls;

    /**
     * Main content HTML.
     *
     * @var string
     */
    private $content;

    /**
     * Constructor.
     *
     * @param int $currentstep Current step number (1-4).
     * @param array $stepurls Array with step1_url, step2_url, step3_url, step4_url.
     * @param string $content Main content HTML.
     */
    public function __construct($currentstep, $stepurls, $content) {
        $this->currentstep = $currentstep;
        $this->stepurls = $stepurls;
        $this->content = $content;
    }

    /**
     * Export data for rendering.
     *
     * @param renderer_base $output Renderer base.
     * @return array Array of template data.
     */
    public function export_for_template(renderer_base $output) {
        $steps = [];

        for ($i = 1; $i <= 4; $i++) {
            $stepurl = $this->stepurls['step' . $i . '_url'];
            $steps[] = [
                'number' => $i,
                'url' => $stepurl->out(),
                'is_current' => ($i === $this->currentstep),
                'is_completed' => ($i < $this->currentstep),
                'show_check' => ($i < $this->currentstep),
                'show_line_after' => ($i < 4),
            ];
        }

        $navigationdata = ['steps' => $steps];
        $navigation = $output->render_from_template('local_chronifyai/wizard_navigation', $navigationdata);

        return [
            'navigation' => $navigation,
            'content' => $this->content,
            'backgroundimage' => $output->image_url('wizard-background', 'local_chronifyai')->out(),
        ];
    }
}
