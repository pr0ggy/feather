<?php

namespace Kase;

// TEST RUN MODES
const TEST_MODE_NORMAL = 1;     // test will run in sequence as normal
const TEST_MODE_ISOLATED = 2;   // test will run in isolation (no other tests in the suite will run)
const TEST_MODE_SKIPPED = 3;    // test will be skipped

// ERROR/LOGIC HANDLING CONSTANTS
const NOT_FOUND = 0;

// DETERMINE PATH TO COMPOSER AUTOLOADER
$SRC_DIR = __DIR__;
$potentialAutoloaderPaths = [
    "{$SRC_DIR}/../../../autoload.php", // should be the correct path when running Kase as a
                                        // dep within another project

    "{$SRC_DIR}/../vendor/autoload.php" // should be correct path when doing local dev on Kase
];
$autoloaderPath = NOT_FOUND;

foreach ($potentialAutoloaderPaths as $potentialAutoloaderPath) {
    if (file_exists($potentialAutoloaderPath)) {
        $autoloaderPath = realpath($potentialAutoloaderPath);
        break;
    }
}

define('Kase\COMPOSER_AUTOLOADER', $autoloaderPath);

// OTHER CONSTS
define('Kase\PROJECT_ROOT_DIR', (COMPOSER_AUTOLOADER === NOT_FOUND ? NOT_FOUND : dirname(dirname(COMPOSER_AUTOLOADER))));
define('Kase\VERSION', (json_decode(file_get_contents("{$SRC_DIR}/../composer.json"), true))['version']);


