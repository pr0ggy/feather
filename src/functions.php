<?php

namespace Kase;

use Equip\Structure\UnorderedList;
use Equip\Structure\Dictionary;
use Exception;
use RuntimeException;

///////////////////////////////////////////////////////////////////////////////////////////////////////
// --------------------------------------- PUBLIC INTERFACE ---------------------------------------- //
///////////////////////////////////////////////////////////////////////////////////////////////////////

/**
 * The core test suite runner generation method.  The returned callback handles execution of a given
 * list of tests comprising a suite.
 *
 * @param  string $suiteDescription the description of the suite defined by all the given tests
 * @param  array  $tests            a packed array of test instances passed to the function
 * @return callable  callback which handles execution of a given list of tests comprising a suite.
 */
function runner($suiteDescription, ...$suiteTests)
{
    return function ($testingResources) use ($suiteDescription, $suiteTests) {
        $testReporter = $testingResources['reporter'];
        $metricsLogger = $testingResources['metricsLogger'];

        // INITIALIZE SUITE METRICS DATA
        $suiteMetrics = [
            'suiteDescription' => $suiteDescription,
            'passedTestCount' => 0,
            'failedTests' => [],    // dict, <test description> => <validation exception>
            'skippedTests' => []    // list of skipped test descriptions
        ];

        // BEGIN TEST SUITE METRICS RECORDING
        $suiteMetrics['executionStartTime'] = microtime(true);
        $testReporter->registerSuiteExecutionInitiation($suiteDescription);

        // DETERMINE WHICH TESTS ACTUALLY NEED TO RUN
        $isTestIsolated = function ($test) {
            return ($test['runMode'] === TEST_MODE_ISOLATED);
        };
        $isolatedSuiteTests = array_filter($suiteTests, $isTestIsolated);
        if (count($isolatedSuiteTests) > 1) {
            throw new RuntimeException('Attempting to run multiple tests in isolation using the "only" function...only 1 allowed');
        } elseif (count($isolatedSuiteTests) === 1) {
            $testsToRun = $isolatedSuiteTests;
        } else {
            $testsToRun = $suiteTests;
        }

        // RUN THE TESTS
        foreach ($testsToRun as $test) {
            try {
                if ($test['runMode'] === TEST_MODE_SKIPPED) {
                    $suiteMetrics['skippedTests'][] = $test['description'];
                    $testReporter->registerSkippedTest($test['description']);
                    continue;
                }

                $testDefinition = $test['definition'];
                $testDefinition();
                ++$suiteMetrics['passedTestCount'];
                $testReporter->registerPassedTest($test['description']);
            } catch (Exception $exception) {
                $suiteMetrics['failedTests'][$test['description']] = $exception;
                $testReporter->registerFailedTest($test['description'], $exception);
            }
        }

        // END TEST SUITE METRICS RECORDING
        $suiteMetrics['executionEndTime'] = microtime(true);
        $testReporter->registerSuiteExecutionCompletion($suiteDescription, $suiteMetrics);
        $metricsLogger($suiteMetrics);
    };
}

/**
 * creates a test case that will be executed sequentially in test suite execution
 *
 * @param  string   $description    the description of the test case
 * @param  callable $testDefinition the function representing the actual test case to execute
 * @return \Equip\Structure\Dictionary  a dictionary representing the test
 */
function test($description, callable $testDefinition)
{
    return _createTest($description, $testDefinition, TEST_MODE_NORMAL);
}

/**
 * creates a test case that will be executed in isolation (only this test will be executed).
 * Note that only 1 call to this function can be used in any given test suite.
 *
 * @param  string   $description    the description of the test case
 * @param  callable $testDefinition the function representing the actual test case to execute
 * @return \Equip\Structure\Dictionary  a dictionary representing the test
 */
function only($description, callable $testDefinition)
{
    return _createTest($description, $testDefinition, TEST_MODE_ISOLATED);
}

/**
 * creates a test case that will be skipped in test suite execution
 *
 * @param  string   $description    the description of the test case
 * @param  callable $testDefinition the function representing the actual test case to execute
 * @return \Equip\Structure\Dictionary  a dictionary representing the test
 */
function skip($description, callable $testDefinition)
{
    return _createTest($description, $testDefinition, TEST_MODE_SKIPPED);
}


///////////////////////////////////////////////////////////////////////////////////////////////////////
// ---------------------------------------- OTHER FUNCTIONS ---------------------------------------- //
///////////////////////////////////////////////////////////////////////////////////////////////////////

/**
 * factory function for creating a dictionary representing a runnable test case
 *
 * @param  string   $testDescription string describing the test case
 * @param  callable $testDefinition  the actual callable test definition
 * @param  TEST_MODE_NORMAL|TEST_MODE_ISOLATED|TEST_MODE_SKIPPED  $runMode flag denoting the mode in
 *                                                                         which the test should run
 * @return Dictionary a dictionary representing a runnable test case
 */
function _createTest($testDescription, callable $testDefinition, $runMode)
{
    return new Dictionary([
        'description' => $testDescription,
        'definition' => $testDefinition,
        'runMode' => $runMode
    ]);
}
