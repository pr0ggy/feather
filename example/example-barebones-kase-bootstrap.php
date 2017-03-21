<?php
/**
 * This file is an example of a barebones Kase bootstrap file.  The bootstrap must define and
 * return a callable which takes a single argument--the testing resources passed in from the Kase
 * executable.  If no custom assertions are required and the standard console reporter will suffice,
 * then no adjustments to this resource package will be needed.  With no adjustments needed, the only
 * requirement of the bootstrap function is to find and include test suite files, executing each
 * returned test suite callback by passing in the testing resources package as an argument.  How
 * to find and loop over the test suite files is up to the end user; the example below uses the
 * Nette\Finder library.  To use the Nette\Finder library, see the following link for installation
 * instructions and documentation:
 *     https://github.com/nette/finder
 */

return function ($testingResources) {
    $testSuiteFilePattern = '*.test.php';
    $testSuiteDir = dirname(__FILE__);

    foreach (\Nette\Utils\Finder::findFiles($testSuiteFilePattern)->in($testSuiteDir) as $absTestSuiteFilePath => $fileInfo) {
        // $absTestSuiteFilePath is a string containing the absolute filename with path
        // $fileInfo is an instance of SplFileInfo
        $testSuiteRunner = require $absTestSuiteFilePath;
        $testSuiteRunner($testingResources);
    }
};
