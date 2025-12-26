<?php
defined('MOODLE_INTERNAL') || die();

$plugin->component = 'local_chronifyai';
$plugin->version = 2025012702;  // Increment for PHPUnit fixes round 2
$plugin->requires = 2022041900;
$plugin->supported = [400, 403];
$plugin->maturity = MATURITY_STABLE;
$plugin->release = 'v1.2.2';  // All PHPUnit tests now pass
