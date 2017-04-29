<?php

namespace Kase;

// TEST RUN MODES
const TEST_MODE_NORMAL = 1;     // test will run in sequence as normal
const TEST_MODE_ISOLATED = 2;   // test will run in isolation (no other tests in the suite will run)
const TEST_MODE_SKIPPED = 3;    // test will be skipped

// ERROR/LOGIC HANDLING CONSTANTS
const NOT_FOUND = 0;

// CALCULATE AND CREATE PROJECT ROOT PATH CONSTANT
$potentialComposerJSONPaths = [
    __DIR__.'/../../../..',  // if composer dependency
    __DIR__.'/..'            // if stand-alone package
];
foreach ($potentialComposerJSONPaths as $composerJSONPath) {
    if (is_file("{$composerJSONPath}/composer.json")) {
        define('Kase\CLIENT_PROJECT_ROOT', realpath($composerJSONPath));
        break;
    }
}

// OTHER CONSTS
define('Kase\VERSION', (json_decode(file_get_contents(__DIR__.'/../composer.json'), true))['version']);
