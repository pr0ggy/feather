<?php

namespace Acme;

use Feather\Context;
use Feather\TestValidator;
use Feather\ValidationFailureException;

//
// 1) register new validator object with custom validators if desired
//    if no custom validation functions are required, this isn't necessary
//
$feather = Context::getInstance();
$feather->TestValidator = new TestValidator([
    'assertInteger' =>
        function ($value, $message = 'Failed to assert that the given value was an integer') {
            if (is_int($value)) {
                return;
            }

            throw new ValidationFailureException($message);
        }
]);


//
// 2) register custom reporter if desired
//    leave this step out to use the default command-line reporter
//    custom reporters must implement Feather\SuiteReporter interface
//
/*
    $feather->suiteReporter = new AcmeSuiteReporter();
 */


//
// 3) require test files using any file inclusion method you like
//    the example below uses the Nette Finder package
//    @see https://github.com/nette/finder
//
$testFilePattern = '*.test.php';
$testDir = dirname(__FILE__).'/example';
foreach (\Nette\Utils\Finder::findFiles($testFilePattern)->in($testDir) as $absFilePath => $fileInfo) {
    // $absFilePath is a string containing absolute filename with path
    // $fileInfo is an instance of SplFileInfo
    require $absFilePath;
}
