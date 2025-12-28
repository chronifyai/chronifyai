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

namespace local_chronifyai\local\export;

use moodle_exception;

/**
 * NDJSON (Newline Delimited JSON) format exporter.
 *
 * Exports data in NDJSON format where each line is a valid, self-contained JSON object.
 * This format is ideal for streaming large datasets and is widely supported by data
 * processing pipelines.
 *
 * Format example:
 * {"id":1,"name":"John","grade":"A"}
 * {"id":2,"name":"Jane","grade":"B"}
 * {"id":3,"name":"Bob","grade":"A"}
 *
 * @package    local_chronifyai
 * @copyright  2025 ChronifyAI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ndjson implements exporter {
    /**
     * Export data to a file using streaming.
     *
     * Writes each record as a separate JSON line, which allows processing huge datasets
     * without loading everything into memory at once.
     *
     * @param array|\Iterator $data The data to export (array or iterator)
     * @param string $filepath Full path where the file should be saved
     * @return string The path to the created file
     * @throws moodle_exception If export fails
     */
    public function export_to_file($data, string $filepath): string {
        global $CFG;

        // Validate data structure.
        $this->validate_data($data);

        // Use Moodle's temp directory API.
        $tempdir = make_temp_directory('chronifyai');

        // If filepath is not in temp directory, ensure it's in a safe location.
        if (strpos($filepath, $CFG->tempdir) !== 0 && strpos($filepath, $CFG->dataroot) !== 0) {
            // Move to temp directory.
            $filepath = $tempdir . '/' . basename($filepath);
        }

        // Ensure directory exists using Moodle API.
        $directory = dirname($filepath);
        if (!is_dir($directory)) {
            if (!check_dir_exists($directory, true, true)) {
                throw new moodle_exception('error:export:cannotcreatedirectory', 'local_chronifyai', '', null, $directory);
            }
        }

        // Open file for writing.
        $handle = fopen($filepath, 'w');
        if ($handle === false) {
            throw new moodle_exception('error:export:cannotcreatefile', 'local_chronifyai', '', null, $filepath);
        }

        try {
            $recordcount = 0;

            // Write record line by line.
            foreach ($data as $record) {
                // Encode the record as JSON.
                $json = json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                if ($json === false) {
                    throw new moodle_exception(
                        'error:export:jsonencodefailed',
                        'local_chronifyai',
                        '',
                        null,
                        'Record #' . $recordcount . ': ' . json_last_error_msg()
                    );
                }

                // Write line to file.
                if (fwrite($handle, $json . "\n") === false) {
                    throw new moodle_exception('error:export:writefailed', 'local_chronifyai', '', null, $filepath);
                }

                $recordcount++;

                // Periodic memory cleanup for very large datasets.
                if ($recordcount % 1000 === 0) {
                    gc_collect_cycles();
                }
            }

            return $filepath;
        } finally {
            // Always close the file handle.
            fclose($handle);
        }
    }

    /**
     * Export data to a string.
     *
     * For small datasets only. Each record becomes one JSON line.
     *
     * @param array $data The data to export
     * @return string The exported data as NDJSON string
     * @throws moodle_exception If export fails
     */
    public function export_to_string(array $data): string {
        // Validate data structure.
        $this->validate_data($data);

        $lines = [];

        foreach ($data as $record) {
            $json = json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            if ($json === false) {
                throw new moodle_exception(
                    'error:export:jsonencodefailed',
                    'local_chronifyai',
                    '',
                    null,
                    json_last_error_msg()
                );
            }

            $lines[] = $json;
        }

        return implode("\n", $lines) . "\n";
    }

    /**
     * Get the file extension for NDJSON format.
     *
     * @return string File extension with dot
     */
    public function get_extension(): string {
        return '.ndjson';
    }

    /**
     * Get the MIME type for NDJSON format.
     *
     * @return string MIME type
     */
    public function get_mimetype(): string {
        return 'application/x-ndjson';
    }

    /**
     * Validate that the data structure is suitable for NDJSON export.
     *
     * NDJSON requires an iterable collection of records (objects/arrays).
     *
     * @param mixed $data The data to validate
     * @return bool True if data is valid
     * @throws moodle_exception If the data structure is invalid
     */
    public function validate_data($data): bool {
        if (!is_iterable($data)) {
            throw new moodle_exception(
                'error:export:invaliddata',
                'local_chronifyai',
                '',
                null,
                'Data must be iterable (array or Iterator)'
            );
        }

        // For arrays, check if empty (just a warning scenario, still valid).
        if (is_array($data) && empty($data)) {
            debugging('Exporting empty dataset', DEBUG_DEVELOPER);
        }

        return true;
    }
}
