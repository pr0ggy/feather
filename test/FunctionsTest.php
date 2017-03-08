<?php

namespace Feather;

use PHPUnit\Framework\TestCase;
use Equip\Structure\Dictionary;
use function Nark\createSpyInstanceOf;
use function Nark\occurredChronologically;

class FunctionsTest extends TestCase
{
    /**
     * @test
     */
    public function createTest_createsTestDataStructureAccordingToGivenArguments()
    {
        $someTestDescription = 'Test A';
        $someTestDefinition = function ($t) { /*do nothing*/ };
        $someTestRunMode = TEST_MODE_NORMAL;

        $expectedTestStructure = new Dictionary([
            'description' => $someTestDescription,
            'definition' => $someTestDefinition,
            'runMode' => $someTestRunMode
        ]);

        $this->assertEquals(
            $expectedTestStructure,
            createTest($someTestDescription, $someTestDefinition, $someTestRunMode),
            'created test data structure was not as expected'
        );
    }

    /**
     * @test
     */
    public function _test_createsTestDataStructureWithNormalRunMode()
    {
        $someTestDescription = 'Test A';
        $someTestDefinition = function ($t) { /*do nothing*/ };

        $expectedTestStructure = createTest(
            $someTestDescription,
            $someTestDefinition,
            TEST_MODE_NORMAL
        );

        $this->assertEquals(
            $expectedTestStructure,
            test($someTestDescription, $someTestDefinition),
            'created test data structure was not as expected'
        );
    }

    /**
     * @test
     */
    public function skip_createsTestDataStructureWithSkippedRunMode()
    {
        $someTestDescription = 'Test A';
        $someTestDefinition = function ($t) { /*do nothing*/ };

        $expectedTestStructure = createTest(
            $someTestDescription,
            $someTestDefinition,
            TEST_MODE_SKIPPED
        );

        $this->assertEquals(
            $expectedTestStructure,
            skip($someTestDescription, $someTestDefinition),
            'created test data structure was not as expected'
        );
    }

    /**
     * @test
     */
    public function only_createsTestDataStructureWithIsolatedRunMode()
    {
        $someTestDescription = 'Test A';
        $someTestDefinition = function ($t) { /*do nothing*/ };

        $expectedTestStructure = createTest(
            $someTestDescription,
            $someTestDefinition,
            TEST_MODE_ISOLATED
        );

        $this->assertEquals(
            $expectedTestStructure,
            only($someTestDescription, $someTestDefinition),
            'created test data structure was not as expected'
        );
    }

    /**
     * @test
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Attempting to run multiple tests in isolation using the "only" function...only 1 allowed
     */
    public function run_throwsRuntimeException_whenMultipleTestsGivenThatAreSpecifiedAsIsolated()
    {
        $fakeTestValidator = createSpyInstanceOf('\Feather\TestValidator');
        $fakeSuiteReporter = createSpyInstanceOf('\Feather\SuiteReporter');
        $suiteTests = [
            only('Test A', function ($t) { /*do nothing*/ }),
            only('Test B', function ($t) { /*do nothing*/ })
        ];

        Context::unregisterSingletonInstance();
        Context::createAndRegisterSingletonWithConstructionArgs($fakeTestValidator, $fakeSuiteReporter);
        run('some suite description', ...$suiteTests);
    }

    /**
     * @test
     */
    public function run_registersSuiteExecutionInitiationWithSuiteReporterFromContext()
    {
        $someSuiteDescription = 'Test Suite A';
        $fakeTestValidator = createSpyInstanceOf('\Feather\TestValidator');
        $fakeSuiteReporter = createSpyInstanceOf('\Feather\SuiteReporter');
        $suiteTests = [
            test('Test A', function ($t) { /*do nothing*/ }),
            test('Test B', function ($t) { /*do nothing*/ })
        ];

        Context::unregisterSingletonInstance();
        Context::createAndRegisterSingletonWithConstructionArgs($fakeTestValidator, $fakeSuiteReporter);
        run($someSuiteDescription, ...$suiteTests);

        $suiteReporter = $fakeSuiteReporter->reflector();
        $this->assertEquals(1, count($suiteReporter->registerSuiteExecutionInitiation($someSuiteDescription)),
            'failed to register suite initiation once with the SuiteReporter instance specified in the Context');
    }

    /**
     * @test
     */
    public function run_runsAllTestsUtilizingTestValidatorInstanceFromContext_whenNoIsolatedOrSkippedTests()
    {
        $fakeTestValidator = createSpyInstanceOf('\Feather\TestValidator');
        $fakeSuiteReporter = createSpyInstanceOf('\Feather\SuiteReporter');
        $suiteTests = [
            test('Test A', function ($t) { $t->pass(); }),
            test('Test B', function ($t) { $t->pass(); }),
            test('Test C', function ($t) { $t->pass(); })
        ];

        Context::unregisterSingletonInstance();
        Context::createAndRegisterSingletonWithConstructionArgs($fakeTestValidator, $fakeSuiteReporter);
        run('some suite description', ...$suiteTests);

        $testValidator = $fakeTestValidator->reflector();
        $expectedValidations = count($suiteTests);
        $this->assertEquals($expectedValidations, count($testValidator->pass()),
            "failed to validate {$expectedValidations} times against the TestValidator instance specified in the Context");
    }

    /**
     * @test
     */
    public function run_runsOnlyIsolatedTestsUtilizingTestValidatorInstanceFromContext_whenOneTestIsSpecifiedAsIsolated()
    {
        $fakeTestValidator = createSpyInstanceOf('\Feather\TestValidator');
        $fakeSuiteReporter = createSpyInstanceOf('\Feather\SuiteReporter');
        $phpunit = $this;
        $failTheTest = function () use ($phpunit) { $phpunit->fail('Ran non-isolated test definition even though an isolated test was specified'); };
        $suiteTests = [
            test('Test A', function ($t) use ($failTheTest) { $failTheTest(); }),
            only('Test B', function ($t) { $t->pass(); }),
            test('Test C', function ($t) use ($failTheTest) { $failTheTest(); })
        ];

        Context::unregisterSingletonInstance();
        Context::createAndRegisterSingletonWithConstructionArgs($fakeTestValidator, $fakeSuiteReporter);
        run('some suite description', ...$suiteTests);

        $testValidator = $fakeTestValidator->reflector();
        $this->assertEquals(1, count($testValidator->pass()),
            'failed to validate once against the TestValidator instance specified in the Context');
    }

    /**
     * @test
     */
    public function run_doesNotRunSkippedTests()
    {
        $fakeTestValidator = createSpyInstanceOf('\Feather\TestValidator');
        $fakeSuiteReporter = createSpyInstanceOf('\Feather\SuiteReporter');
        $phpunit = $this;
        $failTheTest = function () use ($phpunit) { $phpunit->fail('Ran test definition even though that test was marked as skipped'); };
        $suiteTests = [
            test('Test A', function ($t) { $t->pass(); }),
            skip('Test B', function ($t) use ($failTheTest) { $failTheTest(); }),
            test('Test C', function ($t) { $t->pass(); })
        ];

        Context::unregisterSingletonInstance();
        Context::createAndRegisterSingletonWithConstructionArgs($fakeTestValidator, $fakeSuiteReporter);
        run('some suite description', ...$suiteTests);

        $nonSkippedTestCount = 2;
        $testValidator = $fakeTestValidator->reflector();
        $this->assertEquals($nonSkippedTestCount, count($testValidator->pass()),
            "failed to validate {$nonSkippedTestCount} times against the TestValidator instance specified in the Context");
    }

    /**
     * @test
     */
    public function run_registersTestResultsProperlyWithSuiteReporterInstanceFromContext()
    {
        $someTestValidationFailureException = new ValidationFailureException('some validation failure message');
        $fakeTestValidator = createSpyInstanceOf('\Feather\TestValidator', [
            'fail' => \nark\throwsException($someTestValidationFailureException)
        ]);
        $fakeSuiteReporter = createSpyInstanceOf('\Feather\SuiteReporter');
        $suiteTests = [
            test('Successful Test', function ($t) { $t->pass(); }),
            skip('Skipped Test', function ($t) { /* no-op */ }),
            test('Failing Test', function ($t) { $t->fail('some test failure message'); })
        ];

        Context::unregisterSingletonInstance();
        Context::createAndRegisterSingletonWithConstructionArgs($fakeTestValidator, $fakeSuiteReporter);
        run('some suite description', ...$suiteTests);

        $suiteReporter = $fakeSuiteReporter->reflector();
        $this->assertEquals(1, count($suiteReporter->registerPassedTest('Successful Test')),
            "failed to register passing test with the SuiteReporter instance specified in the Context");
        $this->assertEquals(1, count($suiteReporter->registerSkippedTest('Skipped Test')),
            "failed to register skipped test with the SuiteReporter instance specified in the Context");
        $this->assertEquals(1, count($suiteReporter->registerFailedTest('Failing Test', $someTestValidationFailureException)),
            "failed to register failed test with the SuiteReporter instance specified in the Context");
    }

    /**
     * @test
     */
    public function run_registersAnyUnexpectedExceptionWithSuiteReporterInstanceFromContext()
    {
        $fakeTestValidator = createSpyInstanceOf('\Feather\TestValidator');
        $fakeSuiteReporter = createSpyInstanceOf('\Feather\SuiteReporter');
        $someUnexpectedException = new \RuntimeException('some runtime exception');
        $suiteTests = [
            test('Successful Test', function ($t) { $t->pass(); }),
            skip('Skipped Test', function ($t) { /* no-op */ }),
            test('Test Throwing Unexpected Exception', function ($t) use ($someUnexpectedException) { throw $someUnexpectedException; })
        ];

        Context::unregisterSingletonInstance();
        Context::createAndRegisterSingletonWithConstructionArgs($fakeTestValidator, $fakeSuiteReporter);
        run('some suite description', ...$suiteTests);

        $suiteReporter = $fakeSuiteReporter->reflector();
        $this->assertEquals(1, count($suiteReporter->registerUnexpectedException($someUnexpectedException)),
            "failed to register unexpected exception with the SuiteReporter instance specified in the Context");
    }

    /**
     * @test
     */
    public function run_registersAccurateSuiteMetricsPackageWithContextInstance()
    {
        $someTestValidationFailureException = new ValidationFailureException('some validation failure message');
        $fakeTestValidator = createSpyInstanceOf('\Feather\TestValidator', [
            'fail' => \nark\throwsException($someTestValidationFailureException)
        ]);
        $fakeSuiteReporter = createSpyInstanceOf('\Feather\SuiteReporter');
        Context::unregisterSingletonInstance();
        Context::createAndRegisterSingletonWithConstructionArgs($fakeTestValidator, $fakeSuiteReporter);
        $featherContext = Context::getInstance();

        // SOME TEST SUITE A
        $suiteADescription = 'Test Suite A';
        $suiteATests = [
            test('Successful Test', function ($t) { $t->pass(); }),
            skip('Skipped Test', function ($t) { /* no-op */ }),
            test('Failing Test', function ($t) { $t->fail('some test failure message'); })
        ];
        run($suiteADescription, ...$suiteATests);

        // ASSERT TEST SUITE A METRICS REGISTERED PROPERLY
        $this->assertCount(1, $featherContext->executedSuiteMetrics, 'failed to register Suite A execution metrics with Feather context');
        $expectedRecordedSuiteAMetrics = [
            'suiteDescription' => $suiteADescription,
            'passedTestCount' => 1,
            'failedTests' => ['Failing Test' => $someTestValidationFailureException],
            'skippedTests' => ['Skipped Test']
        ];
        $actualRecordedSuiteAMetrics = $featherContext->executedSuiteMetrics[0];
        $expectedSuiteAMetricsMatcher = $this->generateHamcrestKVMatcherFromMap($expectedRecordedSuiteAMetrics);
        $this->assertTrue($expectedSuiteAMetricsMatcher->matches($actualRecordedSuiteAMetrics),
            'recorded suite A metrics did not match the expected metrics');

        // SOME TEST SUITE B
        $suiteBDescription = 'Test Suite B';
        $suiteBTests = [
            test('Successful Test', function ($t) { $t->pass(); }),
            skip('Skipped Test', function ($t) { /* no-op */ }),
            skip('Skipped Test 2', function ($t) { /* no-op */ })
        ];
        run($suiteBDescription, ...$suiteBTests);

        // ASSERT TEST SUITE B METRICS REGISTERED PROPERLY
        $this->assertCount(2, $featherContext->executedSuiteMetrics, 'failed to register Suite B execution metrics with Feather context');
        $expectedRecordedSuiteBMetrics = [
            'suiteDescription' => $suiteBDescription,
            'passedTestCount' => 1,
            'failedTests' => [],
            'skippedTests' => ['Skipped Test', 'Skipped Test 2']
        ];
        $actualRecordedSuiteBMetrics = $featherContext->executedSuiteMetrics[1];
        $expectedSuiteBMetricsMatcher = $this->generateHamcrestKVMatcherFromMap($expectedRecordedSuiteBMetrics);
        $this->assertTrue($expectedSuiteBMetricsMatcher->matches($actualRecordedSuiteBMetrics),
            'recorded suite B metrics did not match the expected metrics');
    }

    protected function generateHamcrestKVMatcherFromMap(array $map)
    {
        $matchers = array_map(
            function ($k, $v) {
                return \Hamcrest\Matchers::hasKeyValuePair($k, $v);
            },
            array_keys($map),
            array_values($map)
        );

        return \Hamcrest\Matchers::allOf(...$matchers);
    }

    /**
     * @test
     */
    public function run_registersSuiteCompletionWithSuiteReporterInstanceFromContext()
    {
        $fakeTestValidator = createSpyInstanceOf('\Feather\TestValidator');
        $fakeSuiteReporter = createSpyInstanceOf('\Feather\SuiteReporter');
        Context::unregisterSingletonInstance();
        Context::createAndRegisterSingletonWithConstructionArgs($fakeTestValidator, $fakeSuiteReporter);

        // SOME TEST SUITE A
        $suiteADescription = 'Test Suite A';
        $suiteATests = [
            test('Successful Test', function ($t) { $t->pass(); }),
        ];
        run($suiteADescription, ...$suiteATests);
        $expectedRecordedSuiteAMetrics = [
            'suiteDescription' => $suiteADescription,
            'passedTestCount' => 1,
            'failedTests' => [],
            'skippedTests' => []
        ];

        // SOME TEST SUITE B
        $suiteBDescription = 'Test Suite B';
        $suiteBTests = [
            test('Successful Test', function ($t) { $t->pass(); }),
            skip('Skipped Test', function ($t) { /*no-op*/ }),
            skip('Skipped Test 2', function ($t) { /*no-op*/ })

        ];
        run($suiteBDescription, ...$suiteBTests);
        $expectedRecordedSuiteBMetrics = [
            'suiteDescription' => $suiteBDescription,
            'passedTestCount' => 1,
            'failedTests' => [],
            'skippedTests' => ['Skipped Test', 'Skipped Test 2']
        ];

        $suiteReporter = $fakeSuiteReporter->reflector();
        $expectedSuiteAMetricsMatcher = $this->generateHamcrestKVMatcherFromMap($expectedRecordedSuiteAMetrics);
        $expectedSuiteBMetricsMatcher = $this->generateHamcrestKVMatcherFromMap($expectedRecordedSuiteBMetrics);
        $this->assertTrue(occurredChronologically(
            $suiteReporter->registerSuiteExecutionCompletion($suiteADescription, $expectedSuiteAMetricsMatcher),
            $suiteReporter->registerSuiteExecutionCompletion($suiteBDescription, $expectedSuiteBMetricsMatcher)
        ));
    }
}
