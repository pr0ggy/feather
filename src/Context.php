<?php

namespace Feather;

/**
 * The main Feather context which stores pluggable modules used by the framework to run test suties,
 * as well as a running list of metrics from each executed test suite.
 *
 * @package Feather
 */
class Context
{
    use \Mockleton\MockableSingletonBehavior;

    /**
     * Validator instance which is passed into each test definition callback and used to make
     * assertions within the test case
     *
     * @var \Feather\TestValidator
     */
    public $testValidator;

    /**
     * SuiteReporter implementation used to report on events from the test runner
     *
     * Defaults to a \Feather\DefaultFeatherCLIReporter instance but can be altered by setting a new
     * SuiteReporter instance from within the Feather bootstrap file.
     *
     * @var \Feather\SuiteReporter
     */
    public $suiteReporter;

    /**
     * List of metrics dicts gathered from each executed test suite
     *
     * @var array
     */
    public $executedSuiteMetrics = [];

    protected function __construct(
        TestValidator $testValidator,
        SuiteReporter $suiteReporter
    ) {
        $this->testValidator = $testValidator;
        $this->suiteReporter = $suiteReporter;
    }
}
