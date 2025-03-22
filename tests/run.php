<?php

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$moodle_path = $_ENV['MOODLE_PATH'] ?? false;

if (!$moodle_path || !is_dir($moodle_path)) {
    exit("Error: MOODLE_PATH is not configured or invalid in your .env file\n");
}

$phpunit_bin = realpath("$moodle_path/vendor/bin/phpunit");

if (!file_exists($phpunit_bin)) {
    exit("Error: PHPUnit executable not found at path: $phpunit_bin\n");
}

// Your project's phpunit.xml file
$phpunit_xml = realpath(__DIR__ . '/../phpunit.xml');

if (!$phpunit_xml) {
    exit("Error: phpunit.xml file not found in the project's root.\n");
}

// Command execution
$command = escapeshellcmd("$phpunit_bin -c \"$phpunit_xml\" " . __DIR__);

passthru($command, $return_code);

exit($return_code);
