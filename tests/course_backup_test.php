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

        // Mock the backup file
        $mockfile = $this->createMock(stored_file::class);
        $mockfile->expects($this->never())
            ->method('delete'); // Should not be deleted in this method

        $mocksetting = $this->createMock(backup_setting::class);
        $mocksetting->expects($this->atLeastOnce())
            ->method('set_value');

        $mockplan = $this->createMock(backup_plan::class);
        $mockplan->expects($this->atLeastOnce())
            ->method('get_setting')
            ->willReturn($mocksetting);

        $mockcontroller = $this->createMock(backup_controller::class);
        $mockcontroller->expects($this->once())
            ->method('get_plan')
            ->willReturn($mockplan);
        $mockcontroller->expects($this->once())
            ->method('execute_plan');
        $mockcontroller->expects($this->once())
            ->method('get_results')
            ->willReturn(['backup_destination' => $mockfile]);
        $mockcontroller->expects($this->once())
            ->method('destroy');

        // Create a partial mock of the service to avoid actual backup controller creation
        $servicemock = $this->getMockBuilder(course_backup::class)
            ->onlyMethods(['create_backup_controller'])
            ->getMock();
        $servicemock->expects($this->once())
            ->method('create_backup_controller')
            ->with($course->id, $user->id)
            ->willReturn($mockcontroller);

        // Test the backup creation
        $result = $servicemock->create_backup_for_upload($course->id, true, $user->id);

        // Verify that the stored file is returned
        $this->assertSame($mockfile, $result);
    }

    /**
     * Test backup creation with error
     * @covers ::create_backup_for_upload
     */
    public function test_create_backup_for_upload_error(): void {
        // Create a course and user
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();

        // Mock the backup controller to throw an exception
        $mockcontroller = $this->createMock(backup_controller::class);
        $mockcontroller->expects($this->once())
            ->method('get_plan')
            ->willThrowException(new \Exception('Backup failed'));
        $mockcontroller->expects($this->once())
            ->method('destroy');

        // Create a partial mock of the service
        $servicemock = $this->getMockBuilder(course_backup::class)
            ->onlyMethods(['create_backup_controller'])
            ->getMock();
        $servicemock->expects($this->once())
            ->method('create_backup_controller')
            ->willReturn($mockcontroller);

        // Test that an exception is thrown
        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('Backup failed');

        $servicemock->create_backup_for_upload($course->id, true, $user->id);
    }

    /**
     * Test backup creation with different user data settings
     * @covers ::create_backup_for_upload
     * @dataProvider userdata_provider
     */
    public function test_create_backup_for_upload_userdata_settings(bool $userdata): void {
        // Create a course and user
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();

        // Create mock objects
        $mockfile = $this->createMock(stored_file::class);
        $mocksetting = $this->createMock(backup_setting::class);
        $mockplan = $this->createMock(backup_plan::class);
        $mockcontroller = $this->createMock(backup_controller::class);

        // Set up expectations for settings - the key ones to check
        $mocksetting->expects($this->atLeastOnce())
            ->method('set_value')
            ->withConsecutive(
                [$this->anything()], // filename
                [(int)$userdata],    // users
                [0],                 // anonymize
                [(int)$userdata]     // role_assignments
            );

        $mockplan->method('get_setting')->willReturn($mocksetting);
        $mockcontroller->method('get_plan')->willReturn($mockplan);
        $mockcontroller->method('get_results')->willReturn(['backup_destination' => $mockfile]);

        // Create service mock
        $servicemock = $this->getMockBuilder(course_backup::class)
            ->onlyMethods(['create_backup_controller'])
            ->getMock();
        $servicemock->method('create_backup_controller')->willReturn($mockcontroller);

        // Test the backup creation
        $result = $servicemock->create_backup_for_upload($course->id, $userdata, $user->id);

        // Verify file is returned
        $this->assertSame($mockfile, $result);
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
        $this->expectExceptionStringContains('User ID is required for backup operations');

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
        $this->expectExceptionStringContains('User ID is required for backup operations');

        // Try to create backup with zero user ID
        $this->service->create_backup_for_upload($course->id, true, 0);
    }

    /**
     * Test backup creation fails when backup file is not created
     * @covers ::create_backup_for_upload
     */
    public function test_create_backup_for_upload_no_file_created(): void {
        // Create a course and user
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();

        // Mock objects that return no backup file
        $mocksetting = $this->createMock(backup_setting::class);
        $mockplan = $this->createMock(backup_plan::class);
        $mockcontroller = $this->createMock(backup_controller::class);

        $mockplan->method('get_setting')->willReturn($mocksetting);
        $mockcontroller->method('get_plan')->willReturn($mockplan);
        $mockcontroller->method('get_results')->willReturn(['backup_destination' => null]); // No file created

        // Create service mock
        $servicemock = $this->getMockBuilder(course_backup::class)
            ->onlyMethods(['create_backup_controller'])
            ->getMock();
        $servicemock->method('create_backup_controller')->willReturn($mockcontroller);

        // Test that an exception is thrown when no backup file is created
        $this->expectException(moodle_exception::class);
        $this->expectExceptionStringContains('No backup file created');

        $servicemock->create_backup_for_upload($course->id, true, $user->id);
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