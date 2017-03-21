<?php

namespace Kase;

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
     */
    public function runner_returnsACallableTestSuite()
    {
        $someFakeTests = [];
        $this->assertTrue(is_callable(runner('some test suite description', ...$someFakeTests)),
            'Kase\run failed to return a callable test suite');
    }

    /**
     * @test
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Attempting to run multiple tests in isolation using the "only" function...only 1 allowed
     */
    public function runnerFunction_throwsRuntimeException_whenMultipleTestsGivenThatAreSpecifiedAsIsolated()
    {
        $fakeTestingResources = $this->createFakeTestingResources();
        $suiteTests = [
            only('Test A', function ($t) { /*do nothing*/ }),
            only('Test B', function ($t) { /*do nothing*/ })
        ];
        $sut = runner('some suite description', ...$suiteTests);

        $sut($fakeTestingResources);
    }

    private function createFakeTestingResources($overrides = [])
    {
        return [
            'validator'     => (isset($overrides['validator']) ? $overrides['validator'] : createSpyInstanceOf('\Kase\TestValidator')),
            'reporter'      => (isset($overrides['reporter']) ? $overrides['reporter'] : createSpyInstanceOf('\Kase\Reporter')),
            'metricsLogger' => (isset($overrides['metricsLogger']) ? $overrides['metricsLogger'] : function($metrics) {/*no-op*/})
        ];
    }

    /**
     * @test
     */
    public function runnerFunction_registersSuiteExecutionInitiationWithSuiteReporterFromContext()
    {
        $fakeTestingResources = $this->createFakeTestingResources();
        $fakeReporter = $fakeTestingResources['reporter'];
        $someSuiteDescription = 'Test Suite A';
        $suiteTests = [
            test('Test A', function ($t) { /*do nothing*/ }),
            test('Test B', function ($t) { /*do nothing*/ })
        ];

        $sut = runner($someSuiteDescription, ...$suiteTests);
        $sut($fakeTestingResources);

        $reporterSpy = $fakeReporter->reflector();
        $this->assertEquals(1, count($reporterSpy->registerSuiteExecutionInitiation($someSuiteDescription)),
            'failed to register suite initiation once with the SuiteReporter instance specified in the Context');
    }

    /**
     * @test
     */
    public function runnerFunction_runsAllTestsUtilizingTestValidatorInstanceFromContext_whenNoIsolatedOrSkippedTests()
    {
        $fakeTestingResources = $this->createFakeTestingResources();
        $fakeValidator = $fakeTestingResources['validator'];
        $suiteTests = [
            test('Test A', function ($t) { $t->pass(); }),
            test('Test B', function ($t) { $t->pass(); }),
            test('Test C', function ($t) { $t->pass(); })
        ];

        $sut = runner('some suite description', ...$suiteTests);
        $sut($fakeTestingResources);

        $validatorSpy = $fakeValidator->reflector();
        $expectedValidations = count($suiteTests);
        $this->assertEquals($expectedValidations, count($validatorSpy->pass()),
            "failed to validate {$expectedValidations} times against the TestValidator instance specified in the Context");
    }

    /**
     * @test
     */
    public function runnerFunction_runsOnlyIsolatedTestsUtilizingTestValidatorInstanceFromContext_whenOneTestIsSpecifiedAsIsolated()
    {
        $fakeTestingResources = $this->createFakeTestingResources();
        $fakeValidator = $fakeTestingResources['validator'];
        $phpunit = $this;
        $failTheTest = function () use ($phpunit) { $phpunit->fail('Ran non-isolated test definition even though an isolated test was specified'); };
        $suiteTests = [
            test('Test A', function ($t) use ($failTheTest) { $failTheTest(); }),
            only('Test B', function ($t) { $t->pass(); }),
            test('Test C', function ($t) use ($failTheTest) { $failTheTest(); })
        ];

        $sut = runner('some suite description', ...$suiteTests);
        $sut($fakeTestingResources);

        $validatorSpy = $fakeValidator->reflector();
        $this->assertEquals(1, count($validatorSpy->pass()),
            'failed to validate once against the TestValidator instance specified in the Context');
    }

    /**
     * @test
     */
    public function runnerFunction_doesNotRunSkippedTests()
    {
        $fakeTestingResources = $this->createFakeTestingResources();
        $fakeValidator = $fakeTestingResources['validator'];
        $phpunit = $this;
        $failTheTest = function () use ($phpunit) { $phpunit->fail('Ran test definition even though that test was marked as skipped'); };
        $suiteTests = [
            test('Test A', function ($t) { $t->pass(); }),
            skip('Test B', function ($t) use ($failTheTest) { $failTheTest(); }),
            test('Test C', function ($t) { $t->pass(); })
        ];

        $sut = runner('some suite description', ...$suiteTests);
        $sut($fakeTestingResources);

        $nonSkippedTestCount = 2;
        $validatorSpy = $fakeValidator->reflector();
        $this->assertEquals($nonSkippedTestCount, count($validatorSpy->pass()),
            "failed to validate {$nonSkippedTestCount} times against the TestValidator instance specified in the Context");
    }

    /**
     * @test
     */
    public function runnerFunction_registersTestResultsProperlyWithSuiteReporterInstanceFromContext()
    {
        $someTestValidationFailureException = new ValidationFailureException('some validation failure message');
        $fakeTestingResources = $this->createFakeTestingResources([
            'validator' => createSpyInstanceOf('\Kase\TestValidator', [
                'fail' => \nark\throwsException($someTestValidationFailureException)
            ])
        ]);
        $fakeReporter = $fakeTestingResources['reporter'];
        $suiteTests = [
            test('Successful Test', function ($t) { $t->pass(); }),
            skip('Skipped Test', function ($t) { /* no-op */ }),
            test('Failing Test', function ($t) { $t->fail('some test failure message'); })
        ];

        $sut = runner('some suite description', ...$suiteTests);
        $sut($fakeTestingResources);

        $reporterSpy = $fakeReporter->reflector();
        $this->assertEquals(1, count($reporterSpy->registerPassedTest('Successful Test')),
            "failed to register passing test with the SuiteReporter instance specified in the Context");
        $this->assertEquals(1, count($reporterSpy->registerSkippedTest('Skipped Test')),
            "failed to register skipped test with the SuiteReporter instance specified in the Context");
        $this->assertEquals(1, count($reporterSpy->registerFailedTest('Failing Test', $someTestValidationFailureException)),
            "failed to register failed test with the SuiteReporter instance specified in the Context");
    }

    /**
     * @test
     */
    public function runnerFunction_registersAnyUnexpectedExceptionWithSuiteReporterInstanceFromContext()
    {
        $fakeTestingResources = $this->createFakeTestingResources();
        $fakeReporter = $fakeTestingResources['reporter'];
        $someUnexpectedException = new \RuntimeException('some runtime exception');
        $suiteTests = [
            test('Successful Test', function ($t) { $t->pass(); }),
            skip('Skipped Test', function ($t) { /* no-op */ }),
            test('Test Throwing Unexpected Exception', function ($t) use ($someUnexpectedException) { throw $someUnexpectedException; })
        ];

        $sut = runner('some suite description', ...$suiteTests);
        $sut($fakeTestingResources);

        $reporterSpy = $fakeReporter->reflector();
        $this->assertEquals(1, count($reporterSpy->registerUnexpectedException($someUnexpectedException)),
            "failed to register unexpected exception with the SuiteReporter instance specified in the Context");
    }

    /**
     * @test
     */
    public function runnerFunction_registersAccurateSuiteMetricsPackageWithContextInstance()
    {
        $someTestValidationFailureException = new ValidationFailureException('some validation failure message');
        $testCaseMetricsLog = [];
        $fakeTestingResources = $this->createFakeTestingResources([
            'validator' => createSpyInstanceOf('\Kase\TestValidator', [
                'fail' => \nark\throwsException($someTestValidationFailureException)
            ]),
            'metricsLogger' => function ($metricsToLog) use (&$testCaseMetricsLog) { $testCaseMetricsLog[] = $metricsToLog; }
        ]);


        // SOME TEST SUITE A
        $suiteADescription = 'Test Suite A';
        $suiteATests = [
            test('Successful Test', function ($t) { $t->pass(); }),
            skip('Skipped Test', function ($t) { /* no-op */ }),
            test('Failing Test', function ($t) { $t->fail('some test failure message'); })
        ];
        $sut = runner($suiteADescription, ...$suiteATests);
        $sut($fakeTestingResources);

        // ASSERT TEST SUITE A METRICS REGISTERED PROPERLY
        $this->assertCount(1, $testCaseMetricsLog, 'failed to register Suite A execution metrics with Kase context');
        $expectedRecordedSuiteAMetrics = [
            'suiteDescription' => $suiteADescription,
            'passedTestCount' => 1,
            'failedTests' => ['Failing Test' => $someTestValidationFailureException],
            'skippedTests' => ['Skipped Test']
        ];
        $actualRecordedSuiteAMetrics = $testCaseMetricsLog[0];
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
        $sut = runner($suiteBDescription, ...$suiteBTests);
        $sut($fakeTestingResources);

        // ASSERT TEST SUITE B METRICS REGISTERED PROPERLY
        $this->assertCount(2, $testCaseMetricsLog, 'failed to register Suite B execution metrics with Kase context');
        $expectedRecordedSuiteBMetrics = [
            'suiteDescription' => $suiteBDescription,
            'passedTestCount' => 1,
            'failedTests' => [],
            'skippedTests' => ['Skipped Test', 'Skipped Test 2']
        ];
        $actualRecordedSuiteBMetrics = $testCaseMetricsLog[1];
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
    public function runnerFunction_registersSuiteCompletionWithSuiteReporterInstanceFromContext()
    {
        $fakeTestingResources = $this->createFakeTestingResources();
        $fakeReporter = $fakeTestingResources['reporter'];

        // SOME TEST SUITE A
        $suiteADescription = 'Test Suite A';
        $suiteATests = [
            test('Successful Test', function ($t) { $t->pass(); }),
        ];
        $sut = runner($suiteADescription, ...$suiteATests);
        $sut($fakeTestingResources);
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
        $sut = runner($suiteBDescription, ...$suiteBTests);
        $sut($fakeTestingResources);
        $expectedRecordedSuiteBMetrics = [
            'suiteDescription' => $suiteBDescription,
            'passedTestCount' => 1,
            'failedTests' => [],
            'skippedTests' => ['Skipped Test', 'Skipped Test 2']
        ];

        $reporterSpy = $fakeReporter->reflector();
        $expectedSuiteAMetricsMatcher = $this->generateHamcrestKVMatcherFromMap($expectedRecordedSuiteAMetrics);
        $expectedSuiteBMetricsMatcher = $this->generateHamcrestKVMatcherFromMap($expectedRecordedSuiteBMetrics);
        $this->assertTrue(occurredChronologically(
            $reporterSpy->registerSuiteExecutionCompletion($suiteADescription, $expectedSuiteAMetricsMatcher),
            $reporterSpy->registerSuiteExecutionCompletion($suiteBDescription, $expectedSuiteBMetricsMatcher)
        ));
    }
}
