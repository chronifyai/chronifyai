<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace local_chronifyai\tests;

use advanced_testcase;
use local_chronifyai\task\restore_course_task;
use moodle_exception;
use stdClass;

/**
 * Unit tests for a restore course task
 *
 * @package    local_chronifyai
 * @category   test
 * @copyright  2025 SEBALE Innovations (http://sebale.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \local_chronifyai\task\restore_course_task
 */
final class restore_course_task_test extends advanced_testcase {
    /**
     * Set up before each test
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Test get_name returns correct string
     * @covers ::get_name
     */
    public function test_get_name(): void {
        $task = new restore_course_task();
        $name = $task->get_name();

        $this->assertNotEmpty($name);
        $this->assertIsString($name);
    }

    /**
     * Test execute fails when course not found
     * @covers ::execute
     */
    public function test_execute_fails_when_course_not_found(): void {
        $this->resetDebugging(); // Reset debugging to allow debugging() calls in this test

        $user = $this->getDataGenerator()->create_user();

        // Prepare task data with non-existent course ID.
        $data = new stdClass();
        $data->courseid = 99999; // Non-existent course.
        $data->backupid = 'test-backup-123';
        $data->externaluserid = 123; // FIX: Use integer instead of string.
        $data->isnewcourse = false;
        $data->restoreoptions = [];

        // Create task.
        $task = new restore_course_task();
        $task->set_custom_data($data);
        $task->set_userid($user->id);

        // Expect exception - debugging calls will happen but we've reset debugging
        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('coursenotfound');

        try {
            $task->execute();
        } finally {
            $this->resetDebugging(); // Clean up after test
        }
    }

    /**
     * Test task data structure for the existing course
     * @covers ::execute
     */
    public function test_task_data_structure_existing_course(): void {
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();

        $data = new stdClass();
        $data->courseid = $course->id;
        $data->backupid = 'backup-123';
        $data->externaluserid = 456; // Integer
        $data->isnewcourse = false;
        $data->coursename = 'Test';
        $data->courseshortname = 'TST';
        $data->restoreoptions = ['keep_roles' => true];

        $task = new restore_course_task();
        $task->set_custom_data($data);
        $task->set_userid($user->id);

        $retrieveddata = $task->get_custom_data();

        // Verify data structure.
        $this->assertIsObject($retrieveddata);
        $this->assertEquals($course->id, $retrieveddata->courseid);
        $this->assertEquals('backup-123', $retrieveddata->backupid);
    }

    /**
     * Test required fields are present
     * @covers ::execute
     */
    public function test_required_fields_validation(): void {
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();

        $data = new stdClass();
        $data->courseid = $course->id;
        $data->backupid = 'backup-123';
        $data->externaluserid = 456; // Integer
        $data->isnewcourse = false;

        $task = new restore_course_task();
        $task->set_custom_data($data);
        $task->set_userid($user->id);

        $retrieveddata = $task->get_custom_data();

        // FIX: Use property_exists() instead of assertObjectHasProperty()
        // assertObjectHasProperty() was deprecated in PHPUnit 9
        $this->assertTrue(property_exists($retrieveddata, 'courseid'), 'courseid property missing');
        $this->assertTrue(property_exists($retrieveddata, 'backupid'), 'backupid property missing');
        $this->assertTrue(property_exists($retrieveddata, 'externaluserid'), 'externaluserid property missing');
        $this->assertNotEmpty($task->get_userid());
    }

    /**
     * Test missing required field: courseid
     * @covers ::execute
     */
    public function test_missing_courseid(): void {
        $user = $this->getDataGenerator()->create_user();

        $data = new stdClass();
        // Missing courseid intentionally.
        $data->backupid = 'backup-123';
        $data->externaluserid = 456;
        $data->isnewcourse = false;

        $task = new restore_course_task();
        $task->set_custom_data($data);
        $task->set_userid($user->id);

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('Course ID is required');
        $task->execute();
    }

    /**
     * Test missing required field: backupid
     * @covers ::execute
     */
    public function test_missing_backupid(): void {
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();

        $data = new stdClass();
        $data->courseid = $course->id;
        // Missing backupid intentionally.
        $data->externaluserid = 456;
        $data->isnewcourse = false;

        $task = new restore_course_task();
        $task->set_custom_data($data);
        $task->set_userid($user->id);

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('Backup ID is required');
        $task->execute();
    }
}
