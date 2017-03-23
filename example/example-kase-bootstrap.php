<?php
/**
 * This file is an example of a Kase bootstrap file.  The bootstrap must define a function which returns
 * an iterable containing all the Kase test suite files.  An example of such a function is shown below.
 * Additional functions may be defined to tweak the behavior of Kase to suit individual needs, examples
 * of which are also shown below.  Note that the bootstrap is defined within the Kase namespace...this
 * is important as the runner will be looking for these functions within the Kase namespace.
 */

namespace Kase;

/**
 * REQUIRED FUNCTION: testSuitePathProvider
 *
 * This function must return an iterable containing all the Kase test suite files the user wishes to run.
 * In this example, the function is a generator which yields a test suite file path on each iteration.
 * The nette/finder package is used in this example, see: https://github.com/nette/finder
 */
function testSuitePathProvider()
{
    $testSuiteFilePattern = '*.test.php';
    $testSuiteDir = dirname(__FILE__);

    foreach (\Nette\Utils\Finder::findFiles($testSuiteFilePattern)->in($testSuiteDir) as $absTestSuiteFilePath => $fileInfo) {
        // $absTestSuiteFilePath is a string containing the absolute filename with path
        // $fileInfo is an instance of SplFileInfo
        yield $absTestSuiteFilePath;
    }
}

/**
 * OPTIONAL FUNCTION: overrideTestingResources
 *
 * This function, if defined, must return a dictionary with specific keys defined corresponding to
 * the testing resources the user wishes to customize/override.  Not all keys must be defined, only
 * those which are to be overridden.  The following keys may be defined to override the corresponding
 * resource:
 *
 *     validator: This is the validation object that will be passed into each test case definition and
 *                used to make assertions within the test case.  Kase ships with a TestValidator
 *                class which supports basic assertion methods and is also customizable by passing a
 *                dictionary of <custom_assertion_method_name> => <custom_assertion_method_callback>
 *                to the constructor.  If you wish to replace this validation class with a custom class,
 *                feel free; you write the test cases, so you decide how the validator will be used and
 *                can write your tests to suite any validator you choose.  The only requirement is that
 *                validation failures throw instances of Kase\ValidationFailureException.  An example of
 *                overriding the testing resources with a custom Kase\TestValidator instance can be found
 *                in the example implementation below.
 *
 *      reporter: This is the object which will handle reporting calls from the test runner.  It must
 *                be an object which implements the Kase\SuiteReporter interface.  An ad-hoc example
 *                of overriding the testing resources with a custom reporter instance can be found below.
 *
 * Any resource not overridden in the dictionary returned from this function will simply fall back
 * to a default implementation defined by the Kase test runner.
 *
 * @return array  a dictionary with keys defined corresponding to the testing resources the user wishes
 *                to customize/override
 */
function overrideTestingResources()
{
    $customValidationMethods = [
        'assertEvenInteger' =>
            function ($value, $message = 'Failed to assert that the given value was an even integer') {
                if (is_int($value) && ($value % 2) === 0) {
                    return;
                }

                throw new ValidationFailureException($message);
            }
    ];

    return [
        'validator' => new TestValidator($customValidationMethods),
        /*
        'reporter' => new \Acme\KaseTestReporter()
        */
    ];
}
