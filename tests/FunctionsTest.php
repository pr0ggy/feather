<?php

namespace Kase\Test;

use PHPUnit\Framework\TestCase;
use Equip\Structure\Dictionary;
use function Nark\createSpyInstanceOf;
use function Nark\occurredChronologically;
use Exception;
use const Kase\TEST_MODE_NORMAL;
use const Kase\TEST_MODE_SKIPPED;
use const Kase\TEST_MODE_ISOLATED;
use function Kase\test;
use function Kase\skip;
use function Kase\only;
use function Kase\runner;
use function Kase\_createTest;

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
            _createTest($someTestDescription, $someTestDefinition, $someTestRunMode),
            'created test data structure was not as expected'
        );
    }

    /**
     * @test
     */
    public function createTest_createsTestDataStructureWithNormalRunMode()
    {
        $someTestDescription = 'Test A';
        $someTestDefinition = function ($t) { /*do nothing*/ };

        $expectedTestStructure =_createTest(
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

        $expectedTestStructure =_createTest(
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

        $expectedTestStructure =_createTest(
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
            'reporter'      => (isset($overrides['reporter']) ? $overrides['reporter'] : createSpyInstanceOf('\Kase\Reporting\Reporter')),
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
    public function runnerFunction_runsAllTests_whenNoIsolatedOrSkippedTests()
    {
        $executedTests = [];
        $suiteTests = [
            test('Test A', function () use (&$executedTests) { $executedTests[] = 'Test A'; }),
            test('Test B', function () use (&$executedTests) { $executedTests[] = 'Test B'; }),
            test('Test C', function () use (&$executedTests) { $executedTests[] = 'Test C'; })
        ];
        $expectedExecutedTests = ['Test A', 'Test B', 'Test C'];

        $sut = runner('some suite description', ...$suiteTests);
        $sut($this->createFakeTestingResources());

        $this->assertEquals($expectedExecutedTests, $executedTests,
            "failed to run the tests as expected");
    }

    /**
     * @test
     */
    public function runnerFunction_runsOnlyIsolatedTests_whenOneTestIsSpecifiedAsIsolated()
    {
        $executedTests = [];
        $suiteTests = [
            test('Test A', function () use (&$executedTests) { $executedTests[] = 'Test A'; }),
            only('Test B', function () use (&$executedTests) { $executedTests[] = 'Test B'; }),
            test('Test C', function () use (&$executedTests) { $executedTests[] = 'Test C'; }),
        ];
        $expectedExecutedTests = ['Test B'];

        $sut = runner('some suite description', ...$suiteTests);
        $sut($this->createFakeTestingResources());

        $this->assertEquals($expectedExecutedTests, $executedTests,
            "failed to run the tests as expected");
    }

    /**
     * @test
     */
    public function runnerFunction_doesNotRunSkippedTests()
    {
        $executedTests = [];
        $suiteTests = [
            test('Test A', function () use (&$executedTests) { $executedTests[] = 'Test A'; }),
            skip('Test B', function () use (&$executedTests) { $executedTests[] = 'Test B'; }),
            test('Test C', function () use (&$executedTests) { $executedTests[] = 'Test C'; })
        ];
        $expectedExecutedTests = ['Test A', 'Test C'];

        $sut = runner('some suite description', ...$suiteTests);
        $sut($this->createFakeTestingResources());

        $this->assertEquals($expectedExecutedTests, $executedTests,
            "failed to skip the expected test");
    }

    /**
     * @test
     */
    public function runnerFunction_registersTestResultsProperlyWithSuiteReporterInstanceFromContext()
    {
        $fakeTestingResources = $this->createFakeTestingResources();
        $fakeReporter = $fakeTestingResources['reporter'];
        $someTestValidationFailureException = new Exception('some test failure message');
        $suiteTests = [
            test('Successful Test', function () { /* no-op */ }),
            skip('Skipped Test', function () { /* no-op */ }),
            test('Failing Test', function () use ($someTestValidationFailureException) { throw $someTestValidationFailureException; })
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
    public function runnerFunction_registersAccurateSuiteMetricsPackageWithContextInstance()
    {
        $testCaseMetricsLog = [];
        $fakeTestingResources = $this->createFakeTestingResources([
            'metricsLogger' => function ($metricsToLog) use (&$testCaseMetricsLog) { $testCaseMetricsLog[] = $metricsToLog; }
        ]);


        // SOME TEST SUITE A
        $suiteADescription = 'Test Suite A';
        $someTestValidationFailureException = new Exception('some test failure message');
        $suiteATests = [
            test('Successful Test', function () { /* no-op */ }),
            skip('Skipped Test', function () { /* no-op */ }),
            test('Failing Test', function () use ($someTestValidationFailureException) { throw $someTestValidationFailureException; })
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
        $expectedSuiteAMetricsMatcher = $this->generateHamcrestKVMatcherFromDict($expectedRecordedSuiteAMetrics);
        $this->assertTrue($expectedSuiteAMetricsMatcher->matches($actualRecordedSuiteAMetrics),
            'recorded suite A metrics did not match the expected metrics');

        // SOME TEST SUITE B
        $suiteBDescription = 'Test Suite B';
        $suiteBTests = [
            test('Successful Test', function () { /* no-op */ }),
            skip('Skipped Test', function () { /* no-op */ }),
            skip('Skipped Test 2', function () { /* no-op */ })
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
        $expectedSuiteBMetricsMatcher = $this->generateHamcrestKVMatcherFromDict($expectedRecordedSuiteBMetrics);
        $this->assertTrue($expectedSuiteBMetricsMatcher->matches($actualRecordedSuiteBMetrics),
            'recorded suite B metrics did not match the expected metrics');
    }

    protected function generateHamcrestKVMatcherFromDict(array $dict)
    {
        $matchers = array_map(
            function ($k, $v) {
                return \Hamcrest\Matchers::hasKeyValuePair($k, $v);
            },
            array_keys($dict),
            array_values($dict)
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
            test('Successful Test', function () { /*no-op*/ }),
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
            test('Successful Test', function () { /*no-op*/ }),
            skip('Skipped Test', function () { /*no-op*/ }),
            skip('Skipped Test 2', function () { /*no-op*/ })

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
        $expectedSuiteAMetricsMatcher = $this->generateHamcrestKVMatcherFromDict($expectedRecordedSuiteAMetrics);
        $expectedSuiteBMetricsMatcher = $this->generateHamcrestKVMatcherFromDict($expectedRecordedSuiteBMetrics);
        $this->assertTrue(occurredChronologically(
            $reporterSpy->registerSuiteExecutionCompletion($suiteADescription, $expectedSuiteAMetricsMatcher),
            $reporterSpy->registerSuiteExecutionCompletion($suiteBDescription, $expectedSuiteBMetricsMatcher)
        ));
    }
}
