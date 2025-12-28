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
 * Interface for data exporters.
 *
 * Defines the contract for exporting data in various formats.
 * All exporters must implement streaming write capabilities for handling large datasets.
 *
 * @package    local_chronifyai
 * @copyright  2025 ChronifyAI
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface exporter {
    /**
     * Export data to a file using streaming.
     *
     * This method should write data line-by-line or chunk-by-chunk to handle large datasets
     * without loading everything into memory.
     *
     * @param array|\Iterator $data The data to export (array or iterator for streaming)
     * @param string $filepath Full path where the file should be saved
     * @return string The path to the created file
     * @throws moodle_exception If export fails
     */
    public function export_to_file($data, string $filepath): string;

    /**
     * Export data to a string.
     *
     * Use this for small datasets only. For large datasets, use export_to_file() instead.
     *
     * @param array $data The data to export
     * @return string The exported data as string
     * @throws moodle_exception If export fails
     */
    public function export_to_string(array $data): string;

    /**
     * Get the file extension for this format (including the dot).
     *
     * @return string File extension (e.g., '.json', '.ndjson')
     */
    public function get_extension(): string;

    /**
     * Get the MIME type for this format.
     *
     * @return string MIME type (e.g., 'application/json', 'application/x-ndjson')
     */
    public function get_mimetype(): string;

    /**
     * Validate that the data structure is suitable for this export format.
     *
     * @param mixed $data The data to validate
     * @return bool True if data is valid for this format
     * @throws moodle_exception If the data structure is invalid
     */
    public function validate_data($data): bool;
}
