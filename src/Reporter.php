<?php

namespace Feather;

/**
 * Interface defining methods which are used by the test runner to report on testing events
 *
 * @package Feather
 */
interface Reporter
{
    /**
     * Called by the test runner just before running the tests within a suite
     *
     * @param  string $suiteDescription
     */
    public function registerSuiteExecutionInitiation($suiteDescription);

    /**
     * Called by the test runner to register a test that passed validation
     *
     * @param  string $testDescription
     */
    public function registerPassedTest($testDescription);

    /**
     * Called by the test runner to register a test that was skipped
     *
     * @param  string $testDescription
     */
    public function registerSkippedTest($testDescription);

    /**
     * Called by the test runner to register a test that failed validation
     *
     * @param  string                     $testDescription
     * @param  ValidationFailureException $exception the validation exception resulting in the failure
     */
    public function registerFailedTest($testDescription, ValidationFailureException $exception);

    /**
     * Called by the test runner to register an unexpected exception encountered during test execution
     *
     * @param  \Exception $exception
     */
    public function registerUnexpectedException(\Exception $exception);

    /**
     * Called by the test runner just after running all the tests within a suite
     *
     * @param  string $suiteDescription
     * @param  array  $suiteMetrics      metrics package for executed suite tests
     */
    public function registerSuiteExecutionCompletion($suiteDescription, array $suiteMetrics);

    /**
     * Called by the test runner just after running all tests from all suites
     *
     * @param  array  $suiteMetricsList  list of executed test metrics from each suite
     */
    public function registerSuiteMetricsSummary(array $suiteMetricsList);
}
