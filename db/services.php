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
 * External services definitions for local_chronifyai.
 *
 * @package    local_chronifyai
 * @copyright  2025 ChronifyAI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    // INTERNAL FUNCTIONS.
    'local_chronifyai_verify_connection' => [
        'classname'       => \local_chronifyai\external\connection::class,
        'methodname'      => 'verify',
        'description'     => 'Verify connection to the ChronifyAI API',
        'type'            => 'write',
        'ajax'            => true,
        'readonlysession' => true,
    ],

    // IMMEDIATE DATA RETRIEVAL.
    'local_chronifyai_get_courses_list' => [
        'classname'       => \local_chronifyai\external\courses::class,
        'methodname'      => 'get_courses_list',
        'description'     => 'Get paginated list of courses',
        'type'            => 'read',
        'capabilities'    => 'local/chronifyai:useservice,moodle/course:view',
        'readonlysession' => true,
    ],
    'local_chronifyai_check_course_exists' => [
        'classname'       => \local_chronifyai\external\courses::class,
        'methodname'      => 'check_course_exists',
        'description'     => 'Check if a course exists in the LMS',
        'type'            => 'read',
        'capabilities'    => 'local/chronifyai:useservice,moodle/course:view',
        'readonlysession' => true,
    ],
    'local_chronifyai_get_categories_list' => [
        'classname'       => \local_chronifyai\external\courses::class,
        'methodname'      => 'get_categories_list',
        'description'     => 'Get list of categories where user can create/restore courses',
        'type'            => 'read',
        'capabilities'    => 'local/chronifyai:useservice,moodle/course:create',
        'readonlysession' => true,
    ],

    // DEFERRED OPERATIONS.
    'local_chronifyai_initiate_course_backup' => [
        'classname'    => \local_chronifyai\external\backups::class,
        'methodname'   => 'initiate_course_backup',
        'description'  => 'Start a course backup process',
        'type'         => 'write',
        'capabilities' => 'local/chronifyai:useservice,moodle/backup:backupcourse',
    ],
    'local_chronifyai_initiate_course_report' => [
        'classname'    => \local_chronifyai\external\reports::class,
        'methodname'   => 'initiate_course_report',
        'description'  => 'Start a course report generation process',
        'type'         => 'write',
        'capabilities' => 'local/chronifyai:useservice,moodle/course:view,moodle/course:viewparticipants',
    ],
    'local_chronifyai_initiate_course_restore' => [
        'classname'    => \local_chronifyai\external\backups::class,
        'methodname'   => 'initiate_course_restore',
        'description'  => 'Queues a task to download and restore a backup from ChronifyAI into an existing course.',
        'type'         => 'write',
        'capabilities' => 'local/chronifyai:useservice,moodle/restore:restorecourse,' .
                          'moodle/restore:restoreactivity,moodle/backup:configure',
    ],
    'local_chronifyai_initiate_transcripts_export' => [
        'classname'    => \local_chronifyai\external\users::class,
        'methodname'   => 'initiate_transcripts_export',
        'description'  => 'Queue a task to export user transcripts to ChronifyAI',
        'type'         => 'write',
        'capabilities' => 'local/chronifyai:useservice,moodle/user:viewprofile,moodle/grade:view',
    ],
];

$services = [
    'ChronifyAI Service' => [
        'functions' => [
            'local_chronifyai_get_courses_list',
            'local_chronifyai_check_course_exists',
            'local_chronifyai_get_categories_list',
            'local_chronifyai_initiate_course_backup',
            'local_chronifyai_initiate_course_report',
            'local_chronifyai_initiate_course_restore',
            'local_chronifyai_initiate_transcripts_export',
        ],
        'restrictedusers' => 1,
        'enabled' => 1,
        'shortname' => 'local_chronifyai_service',
        'downloadfiles' => 0,
        'uploadfiles' => 0,
    ],
];
