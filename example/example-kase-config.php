<?php

/**
 * This file is an example of a Kase config file.  The config must define and return a keyed
 * array used to configure Kase.  An example of such a config file is shown below.
 */

return [

    /*
     * REQUIRED KEY: testSuitePathProvider
     * TYPE: callable
     *
     * Must return an iterable containing all the Kase test suite files the user wishes to run.
     * In this example, the function is a generator which yields a test suite file path on each iteration.
     * The nette/finder package is used in this example, see: https://github.com/nette/finder
     */
    'testSuitePathProvider' => function () {
        $testSuiteFilePattern = '*.test.php';
        $testSuiteDir = dirname(__FILE__);

        foreach (\Nette\Utils\Finder::findFiles($testSuiteFilePattern)->from($testSuiteDir) as $absTestSuiteFilePath => $fileInfo) {
            // $absTestSuiteFilePath is a string containing the absolute filename with path
            // $fileInfo is an instance of SplFileInfo
            yield $absTestSuiteFilePath;
        }
    },

    /*
     * OPTIONAL KEY: validator
     * TYPE: any
     *
     * This is the validation object that will be passed into each test case definition and
     * used to make assertions within the test case.  Kase ships with a TestValidator
     * class which supports basic assertion methods and is also customizable by passing a
     * dictionary of <custom_assertion_method_name> => <custom_assertion_method_callback>
     * to the constructor.  If you wish to replace this validation class with a custom class,
     * feel free; you write the test cases, so you decide how the validator will be used and
     * can write your tests to suite any validator you choose.  The only requirement is that
     * validation failures throw instances of Kase\ValidationFailureException.  An example of
     * overriding the testing resources with a Kase\TestValidator instance loaded with custom
     * validation methods can be found in the example below.
     */
    'validator' => new Kase\TestValidator([
        'assertEvenInteger' =>
            function ($value, $message = 'Failed to assert that the given value was an even integer') {
                if (is_int($value) && ($value % 2) === 0) {
                    return;
                }

                throw new ValidationFailureException($message);
            }
    ])

    /*
     * OPTIONAL KEY: reporter
     * TYPE: Kase\Reporter
     *
     * This is the object which will handle reporting calls from the test runner.  It must
     * be an object which implements the Kase\SuiteReporter interface.  An ad-hoc example
     * of overriding the testing resources with a custom reporter instance can be found below.
     */
    // 'reporter' => new \Acme\KaseTestReporter()

];
