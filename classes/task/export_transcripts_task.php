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

namespace local_chronifyai\task;

use core\task\adhoc_task;
use Exception;
use local_chronifyai\api\client;
use local_chronifyai\constants;
use local_chronifyai\export\ndjson;
use local_chronifyai\service\notification;
use local_chronifyai\service\transcripts;
use moodle_exception;

/**
 * Adhoc task to export user transcripts to ChronifyAI.
 *
 * @package    local_chronifyai
 * @copyright  2025 SEBALE Innovations (http://sebale.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class export_transcripts_task extends adhoc_task {
    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     * @throws \coding_exception
     */
    #[\Override]
    public function get_name(): string {
        return get_string('task:transcripts:export', constants::PLUGIN_NAME);
    }

    /**
     * Execute the task.
     */
    public function execute() {
        // Get task data.
        $customdata = $this->get_custom_data();
        $externaluserid = $customdata->externaluserid ?? null;

        mtrace("Starting transcripts export for all valid users");

        $filepath = null;

        try {
            // Create a temporary directory for the export file.
            $tempdir = make_temp_directory('chronifyai/transcripts');
            $timestamp = time();
            $filename = "transcripts_export_{$timestamp}.ndjson";
            $filepath = $tempdir . '/' . $filename;

            mtrace("Generating transcript data...");

            // Create an NDJSON exporter instance.
            $exporter = new ndjson();

            // Generate transcript data using generator (memory efficient).
            $transcriptgenerator = transcripts::generate_all_transcripts();

            // Export to NDJSON file using streaming.
            $exportedfile = $exporter->export_to_file($transcriptgenerator, $filepath);

            // Check if a file was created and has content.
            if (!file_exists($exportedfile) || filesize($exportedfile) === 0) {
                throw new moodle_exception(
                    'error:transcripts:export:emptyfile',
                    constants::PLUGIN_NAME,
                    '',
                    null,
                    'No valid transcript data was generated'
                );
            }

            $filesize = filesize($exportedfile);
            $filesizemb = round($filesize / 1024 / 1024, 2);
            mtrace("Transcript file created: {$exportedfile} ({$filesizemb} MB)");

            // Upload the file to ChronifyAI using multipart form data.
            mtrace("Uploading transcript file to ChronifyAI...");
            $attachments = [
                'records' => [
                    'path' => $exportedfile,
                    'content_type' => 'application/x-ndjson',
                    'filename' => 'attachment_' . time() . '.ndjson',
                ],
            ];
            $response = client::create_transcripts($attachments);

            // Check for upload failure and throw exception to trigger Moodle's task retry mechanism.
            if (!$response->success) {
                throw new moodle_exception('error:api:communicationfailed', constants::PLUGIN_NAME, '', $response->error_message);
            }

            mtrace("Transcript file successfully uploaded to ChronifyAI");
            mtrace("Response: " . json_encode($response));

            // Send notification if external user ID provided.
            if ($externaluserid) {
                notification::send_transcripts_export_completed($externaluserid);
            }

            mtrace("Transcripts export completed successfully");
        } catch (Exception $e) {
            // Log the error.
            $error = "Transcripts export failed: " . $e->getMessage();
            mtrace($error);
            debugging($error, DEBUG_DEVELOPER);

            // Send failure notification if external user ID provided.
            if ($externaluserid) {
                notification::send_transcripts_export_failed($externaluserid, $e->getMessage());
            }

            // Re-throw the exception to mark the task as failed.
            throw $e;
        } finally {
            // Clean up temporary file.
            if ($filepath && file_exists($filepath)) {
                try {
                    unlink($filepath);
                    mtrace("Temporary file cleaned up: {$filepath}");
                } catch (Exception $e) {
                    mtrace("Warning: Could not delete temporary file: {$filepath}");
                }
            }
        }
    }

    /**
     * Limit concurrent transcripts tasks to prevent resource exhaustion.
     *
     * @return int
     */
    #[\Override]
    protected function get_default_concurrency_limit(): int {
        return 1;
    }
}
