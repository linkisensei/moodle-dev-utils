<?php

require_once(__DIR__ . '/../vendor/autoload.php');
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$moodle_path = $_ENV['MOODLE_PATH'];

if(!is_readable("$moodle_path/config.php")){
    exit(1);
}

if (!defined('MOODLE_INTERNAL')) {
    define('MOODLE_INTERNAL', true);
}

// The PHPUnit version must be the same of your Moodle's installation
require_once("$moodle_path/lib/phpunit/bootstrap.php");