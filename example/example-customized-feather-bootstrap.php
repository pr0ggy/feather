<?php
/**
 * This file is an example of a customized Feather bootstrap file.  The bootstrap must define and
 * return a callable which takes a single argument--the testing resources passed in from the Feather
 * executable.  The testing resources package is a simple dictionary with the following keys available
 * for customization:
 *
 *     validator: This is the validation object that will be passed into each test case definition and
 *                used to make assertions within the test case.  Feather ships with a TestValidator
 *                class which supports basic assertion methods and is also customizable by passing a
 *                dictionary of <custom_assertion_method_name> => <custom_assertion_method_callback>
 *                to the constructor.  If you wish to replace this validation class with a custom class,
 *                feel free; you write the test cases, so you decide how the validator will be used and
 *                can write your tests to suite any validator you choose.  An example of overriding the
 *                testing resources with a custom Feather\TestValidator instance can be found below.
 *
 *      reporter: This is the object which will handle reporting calls from the test runner.  It must
 *                be an object which implements the Feather\SuiteReporter interface.  An ad-hoc example
 *                of overriding the testing resources with a custom reporter instance can be found below.
 *
 * Aside from any desired customization of testing resources, the only requirement of the bootstrap
 * function is to find and include test suite files, executing each returned test suite callback by
 * passing in the testing resources package as an argument.  How to find and loop over the test suite
 * files is up to the end user; the example below uses the Nette\Finder library. To use the Nette\Finder
 * library, see the following link for installation instructions and documentation:
 *     https://github.com/nette/finder
 */

return function ($testingResources) {
    // --------- override validator resource to use custom Feather\TestValidator instance ----------
    $customValidationMethods = [
        'assertEvenInteger' =>
            function ($value, $message = 'Failed to assert that the given value was an even integer') {
                if (is_int($value) && ($value % 2) === 0) {
                    return;
                }

                throw new ValidationFailureException($message);
            }
    ];
    $testingResources['validator'] = new Feather\TestValidator($customValidationMethods);

    // -------------- override reporter resource to use some custom reporter instance --------------
    // $testingResources['reporter'] = new AcmeTestReporter();

    // --------------------------- find and run all desired test suites ----------------------------
    $testSuiteFilePattern = '*.test.php';
    $testSuiteDir = dirname(__FILE__);
    foreach (\Nette\Utils\Finder::findFiles($testSuiteFilePattern)->in($testSuiteDir) as $absTestSuiteFilePath => $fileInfo) {
        // $absTestSuiteFilePath is a string containing the absolute filename with path
        // $fileInfo is an instance of SplFileInfo
        $testSuiteRunner = require $absTestSuiteFilePath;
        $testSuiteRunner($testingResources);
    }
};
