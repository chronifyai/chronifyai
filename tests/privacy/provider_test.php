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
use core_privacy\local\metadata\collection;

/**
 * Privacy provider tests for local_chronifyai.
 *
 * @coversDefaultClass \local_chronifyai\privacy\provider
 */
final class provider_test extends provider_testcase {
    /**
     * Test that the plugin implements the metadata provider interface.
     *
     * @covers ::get_metadata
     */
    public function test_get_metadata(): void {
        $collection = new collection('local_chronifyai');
        $result = provider::get_metadata($collection);
        
        $this->assertInstanceOf(collection::class, $result);
        $this->assertNotEmpty($result->get_collection());
    }

    /**
     * Test that contexts for a user are returned.
     *
     * @covers ::get_contexts_for_userid
     */
    public function test_get_contexts_for_userid(): void {
        $this->resetAfterTest();
        
        $user = $this->getDataGenerator()->create_user();
        $contextlist = provider::get_contexts_for_userid($user->id);
        
        $this->assertInstanceOf(\core_privacy\local\request\contextlist::class, $contextlist);
    }
}
