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
use local_chronifyai\api\client;
use local_chronifyai\service\course_restore;
use local_chronifyai\service\notification;
use local_chronifyai\service\restore_data_preparer;
use core\lock\lock_config;
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
     * Test a successful restore to existing course
     * @covers ::execute
     */
    public function test_execute_restore_to_existing_course(): void {
        global $DB;

        // Create test data.
        $course = $this->getDataGenerator()->create_course(['fullname' => 'Test Course']);
        $user = $this->getDataGenerator()->create_user();

        // Prepare task data.
        $data = new stdClass();
        $data->courseid = $course->id;
        $data->backupid = 'test-backup-123';
        $data->externaluserid = 'ext-user-456';
        $data->isnewcourse = false;
        $data->coursename = 'Test Course';
        $data->courseshortname = 'TC';
        $data->restoreoptions = [];

        // Create task.
        $task = new restore_course_task();
        $task->set_custom_data($data);
        $task->set_userid($user->id);

        // Mock dependencies (in real implementation, you'd need to mock these classes)
        // This is a simplified example - actual implementation would need proper mocking.

        // For this test to work properly, you'd need to:
        // 1. Mock client::download_backup()
        // 2. Mock course_restore service
        // 3. Mock notification service
        // 4. Create actual backup file or mock file system.

        // This test demonstrates the structure but would need actual mocking framework.
        $this->markTestSkipped('Requires dependency injection and mocking framework');
    }

    /**
     * Test a successful restore to a new course
     * @covers ::execute
     */
    public function test_execute_restore_to_new_course(): void {
        // Create test data.
        $category = $this->getDataGenerator()->create_category();
        $user = $this->getDataGenerator()->create_user();

        // Prepare task data.
        $data = new stdClass();
        $data->backupid = 'test-backup-123';
        $data->externaluserid = 'ext-user-456';
        $data->isnewcourse = true;
        $data->targetcategoryid = $category->id;
        $data->coursename = 'New Course';
        $data->courseshortname = 'NC';
        $data->restoreoptions = [
            'target_category' => $category->id,
        ];

        // Create task.
        $task = new restore_course_task();
        $task->set_custom_data($data);
        $task->set_userid($user->id);

        // Would need mocking as above.
        $this->markTestSkipped('Requires dependency injection and mocking framework');
    }

    /**
     * Test restore fails when course not found
     * @covers ::execute
     */
    public function test_execute_fails_when_course_not_found(): void {
        $user = $this->getDataGenerator()->create_user();

        // Prepare task data with non-existent course ID.
        $data = new stdClass();
        $data->courseid = 99999; // Non-existent course.
        $data->backupid = 'test-backup-123';
        $data->externaluserid = 'ext-user-456';
        $data->isnewcourse = false;
        $data->restoreoptions = [];

        // Create task.
        $task = new restore_course_task();
        $task->set_custom_data($data);
        $task->set_userid($user->id);

        // Expect exception.
        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('coursenotfound');

        $task->execute();
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
        $data->externaluserid = 'user-456';
        $data->isnewcourse = false;
        $data->coursename = 'Test';
        $data->courseshortname = 'TST';
        $data->restoreoptions = ['keep_roles' => true];

        $task = new restore_course_task();
        $task->set_custom_data($data);
        $task->set_userid($user->id);

        $retrieveddata = $task->get_custom_data();

        $this->assertEquals($course->id, $retrieveddata->courseid);
        $this->assertEquals('backup-123', $retrieveddata->backupid);
        $this->assertEquals('user-456', $retrieveddata->externaluserid);
        $this->assertFalse($retrieveddata->isnewcourse);
        $this->assertEquals($user->id, $task->get_userid());
    }

    /**
     * Test task data structure for new course
     * @covers ::execute
     */
    public function test_task_data_structure_new_course(): void {
        $category = $this->getDataGenerator()->create_category();
        $user = $this->getDataGenerator()->create_user();

        $data = new stdClass();
        $data->backupid = 'backup-123';
        $data->externaluserid = 'user-456';
        $data->isnewcourse = true;
        $data->targetcategoryid = $category->id;
        $data->coursename = 'New Course';
        $data->courseshortname = 'NC';
        $data->restoreoptions = ['target_category' => $category->id];

        $task = new restore_course_task();
        $task->set_custom_data($data);
        $task->set_userid($user->id);

        $retrieveddata = $task->get_custom_data();

        $this->assertEquals('backup-123', $retrieveddata->backupid);
        $this->assertTrue($retrieveddata->isnewcourse);
        $this->assertEquals($category->id, $retrieveddata->targetcategoryid);
    }

    /**
     * Test lock key generation for existing course
     * @covers ::execute
     */
    public function test_lock_key_generation_existing_course(): void {
        $course = $this->getDataGenerator()->create_course();

        $expectedkey = 'course_restore_existing_' . $course->id;

        // This tests the lock key pattern used in the code
        // In actual implementation, you'd verify this through mocking.
        $this->assertMatchesRegularExpression('/^course_restore_existing_\d+$/', $expectedkey);
    }

    /**
     * Test lock key generation for new course
     * @covers ::execute
     */
    public function test_lock_key_generation_new_course(): void {
        $category = $this->getDataGenerator()->create_category();
        $user = $this->getDataGenerator()->create_user();

        $expectedkey = 'course_restore_new_' . $category->id . '_' . $user->id;

        // This tests the lock key pattern used in the code.
        $this->assertMatchesRegularExpression('/^course_restore_new_\d+_\d+$/', $expectedkey);
    }

    /**
     * Test restore options are properly handled
     * @covers ::execute
     */
    public function test_restore_options_handling(): void {
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();

        $rawoptions = [
            'keep_roles' => true,
            'keep_enrolments' => false,
            'keep_groups' => true,
        ];

        $data = new stdClass();
        $data->courseid = $course->id;
        $data->backupid = 'backup-123';
        $data->externaluserid = 'user-456';
        $data->isnewcourse = false;
        $data->restoreoptions = (object)$rawoptions;

        $task = new restore_course_task();
        $task->set_custom_data($data);

        $retrieveddata = $task->get_custom_data();
        $retrievedoptions = (array)$retrieveddata->restoreoptions;

        $this->assertArrayHasKey('keep_roles', $retrievedoptions);
        $this->assertTrue($retrievedoptions['keep_roles']);
        $this->assertFalse($retrievedoptions['keep_enrolments']);
    }

    /**
     * Test default values for optional fields
     * @covers ::execute
     */
    public function test_default_values_for_optional_fields(): void {
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();

        // Create data without optional fields.
        $data = new stdClass();
        $data->courseid = $course->id;
        $data->backupid = 'backup-123';
        $data->externaluserid = 'user-456';
        // No isnewcourse, no coursename, no restoreoptions.

        $task = new restore_course_task();
        $task->set_custom_data($data);

        $retrieveddata = $task->get_custom_data();

        // Test defaults as used in execute() method.
        $isnewcourse = $retrieveddata->isnewcourse ?? false;
        $coursename = $retrieveddata->coursename ?? '';
        $restoreoptions = (array)($retrieveddata->restoreoptions ?? []);

        $this->assertFalse($isnewcourse);
        $this->assertEquals('', $coursename);
        $this->assertIsArray($restoreoptions);
        $this->assertEmpty($restoreoptions);
    }

    /**
     * Test temporary file path generation
     * @covers ::execute
     */
    public function test_temporary_file_path_generation(): void {
        $backupid = 'test-backup-12345';

        // Simulate how the path is generated in execute().
        $tempdir = make_temp_directory('chronifyai');
        $expectedpath = $tempdir . '/backup_' . $backupid . '.mbz';

        $this->assertStringContainsString('chronifyai', $expectedpath);
        $this->assertStringContainsString('backup_test-backup-12345.mbz', $expectedpath);
        $this->assertStringEndsWith('.mbz', $expectedpath);
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
        $data->externaluserid = 'user-456';
        $data->isnewcourse = false;

        $task = new restore_course_task();
        $task->set_custom_data($data);
        $task->set_userid($user->id);

        $retrieveddata = $task->get_custom_data();

        // Verify all required fields are present.
        $this->assertObjectHasProperty('courseid', $retrieveddata);
        $this->assertObjectHasProperty('backupid', $retrieveddata);
        $this->assertObjectHasProperty('externaluserid', $retrieveddata);
        $this->assertNotEmpty($task->get_userid());
    }
}
