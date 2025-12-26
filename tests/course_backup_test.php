<?php
namespace local_chronifyai\tests;

use advanced_testcase;
use local_chronifyai\service\course_backup;
use backup_controller;
use stored_file;
use backup_plan;
use backup_setting;
use moodle_exception;

/**
 * Unit tests for course backup service
 *
 * @package    local_chronifyai
 * @category   test
 * @copyright  2025 SEBALE Innovations (http://sebale.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \local_chronifyai\service\course_backup
 */
class course_backup_test extends advanced_testcase {
    /**
     * @var course_backup
     */
    protected $service;

    /**
     * Set up before each test
     */
    protected function setUp(): void {
        global $CFG;
        $this->resetAfterTest();
        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
        $this->service = new course_backup();
    }

    /**
     * Test successful course backup creation for upload
     * @covers ::create_backup_for_upload
     */
    public function test_create_backup_for_upload_success(): void {
        // Create a course and user
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();

        // Test actual backup creation (integration test style)
        // Skip mocking the private method - test the public interface instead
        $result = $this->service->create_backup_for_upload($course->id, false, $user->id);

        // Verify that a stored file is returned
        $this->assertInstanceOf(stored_file::class, $result);
        $this->assertGreaterThan(0, $result->get_filesize());
    }

    /**
     * Test backup creation with user data
     * @covers ::create_backup_for_upload
     * @dataProvider userdata_provider
     */
    public function test_create_backup_for_upload_userdata_settings(bool $userdata): void {
        // Create a course and user
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();

        // Test the backup creation
        $result = $this->service->create_backup_for_upload($course->id, $userdata, $user->id);

        // Verify file is returned
        $this->assertInstanceOf(stored_file::class, $result);
        $this->assertGreaterThan(0, $result->get_filesize());
    }

    /**
     * Test backup creation fails when no user ID provided
     * @covers ::create_backup_for_upload
     */
    public function test_create_backup_for_upload_missing_userid(): void {
        // Create a course
        $course = $this->getDataGenerator()->create_course();

        // Test that an exception is thrown when no user ID provided
        $this->expectException(moodle_exception::class);
        // Use expectExceptionMessage instead of expectExceptionStringContains
        $this->expectExceptionMessage('User ID is required');

        // Try to create backup without user ID (empty value)
        $this->service->create_backup_for_upload($course->id, true, null);
    }

    /**
     * Test backup creation fails when zero user ID provided
     * @covers ::create_backup_for_upload
     */
    public function test_create_backup_for_upload_zero_userid(): void {
        // Create a course
        $course = $this->getDataGenerator()->create_course();

        // Test that an exception is thrown when zero user ID provided
        $this->expectException(moodle_exception::class);
        // Use expectExceptionMessage instead of expectExceptionStringContains
        $this->expectExceptionMessage('User ID is required');

        // Try to create backup with zero user ID
        $this->service->create_backup_for_upload($course->id, true, 0);
    }

    /**
     * Test backup creation with invalid course ID
     * @covers ::create_backup_for_upload
     */
    public function test_create_backup_for_upload_invalid_course(): void {
        // Create a user
        $user = $this->getDataGenerator()->create_user();

        // Test that an exception is thrown with invalid course ID
        $this->expectException(\Exception::class);

        $this->service->create_backup_for_upload(999999, true, $user->id);
    }

    /**
     * Data provider for userdata settings test
     */
    public function userdata_provider(): array {
        return [
            'with user data' => [true],
            'without user data' => [false]
        ];
    }
}
