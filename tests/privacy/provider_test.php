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
 * Privacy provider tests for local_chronifyai.
 *
 * @package    local_chronifyai
 * @copyright  2025 ChronifyAI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_chronifyai\privacy;

use core_privacy\tests\provider_testcase;

/**
 * Privacy provider tests for local_chronifyai.
 *
 * @coversDefaultClass \local_chronifyai\privacy\provider
 */
final class provider_test extends provider_testcase {
    /**
     * Test for provider::get_reason().
     *
     * @covers ::get_reason
     */
    public function test_get_reason(): void {
        $reason = provider::get_reason();
        $this->assertEquals('privacy:metadata', $reason);
    }
}
