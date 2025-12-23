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

namespace local_chronifyai;

/**
 * Plugin constants.
 *
 * @package   local_chronifyai
 * @copyright 2025 SEBALE Innovations (http://sebale.net)
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class constants {
    /** @var string The plugin name */
    public const PLUGIN_NAME = 'local_chronifyai';

    /** @var string The cache name for the token */
    public const CACHE_NAME = 'local_chronifyai';

    /** @var string The cache area for the token */
    public const CACHE_AREA = 'apitoken';

    /** @var string The cache key for the token */
    public const CACHE_KEY = 'api_token';

    /** @var int Default token lifetime in seconds */
    public const DEFAULT_TOKEN_LIFETIME = 900;

    /** @var string Default API base URL */
    public const DEFAULT_API_BASE_URL = 'https://app.chronifyai.com/api';

    /** @var int Course fullname maximum length */
    public const FULLNAME_MAXIMUM_LENGTH = 254;

    /** @var int Course shortname maximum length */
    public const SHORTNAME_MAXIMUM_LENGTH = 100;
}
