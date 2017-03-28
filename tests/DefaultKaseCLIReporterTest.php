<?php

namespace Kase\Test;

use PHPUnit\Framework\TestCase;
use Kase\DefaultKaseCLIReporter;
use Kase\ValidationFailureException;
use function Nark\occurredSequentially;

class DefaultKaseCLIReporterTest extends TestCase
{
    /**
     * @test
     */
    public function registerTestRunnerInitialization_printsKaseVersionToConsole()
    {
        $fakeOutput = $this->getOutputInterfaceSpy();
        $sut = new DefaultKaseCLIReporter($fakeOutput);

        $sut->registerTestRunnerInitialization();

        $outputSpy = $fakeOutput->reflector();
        $this->assertTrue(
            occurredSequentially(
                $outputSpy->writeln(''),
                $outputSpy->writeln('Kase '.VERSION),
                $outputSpy->writeln('')
            ),
            'reporter did not print the Kase version number to the output interface as expected'
        );
    }

    /**
     * @test
     */
    public function registerSuiteExecutionInitiation_reportsSuiteDescription()
    {
        $fakeOutput = $this->getOutputInterfaceSpy();
        $sut = new DefaultKaseCLIReporter($fakeOutput);
        $someTestSuiteName = 'Test Suite Alpha';

        $sut->registerSuiteExecutionInitiation($someTestSuiteName);

        $outputSpy = $fakeOutput->reflector();
        $this->assertTrue(
            occurredSequentially(
                $outputSpy->writeln('///////////////////////////////////////////'),
                $outputSpy->writeln('//'),
                $outputSpy->writeln("//  {$someTestSuiteName}"),
                $outputSpy->writeln('//')
            ),
            'reporter did not print the test suite header to the output interface as expected'
        );
    }

    private function getOutputInterfaceSpy()
    {
        return \Nark\createSpyInstanceOf('\Symfony\Component\Console\Output\OutputInterface');
    }

    /**
     * @test
     */
    public function registerPassedTest_reportsDescriptionOfPassingTest()
    {
        $fakeOutput = $this->getOutputInterfaceSpy();
        $sut = new DefaultKaseCLIReporter($fakeOutput);
        $someTestDescription = 'something happens';

        $sut->registerPassedTest($someTestDescription);

        $outputSpy = $fakeOutput->reflector();
        $this->assertEquals(
            1,
            count($outputSpy->writeln("<info>[PASS] {$someTestDescription}</info>")),
            'reporter did not print the passing test info to the output interface as expected'
        );
    }

    /**
     * @test
     */
    public function registerSkippedTest_reportsDescriptionOfSkippedTest()
    {
        $fakeOutput = $this->getOutputInterfaceSpy();
        $sut = new DefaultKaseCLIReporter($fakeOutput);
        $someTestDescription = 'something happens';

        $sut->registerSkippedTest($someTestDescription);

        $outputSpy = $fakeOutput->reflector();
        $this->assertEquals(
            1,
            count($outputSpy->writeln("<comment>[SKIP] {$someTestDescription}</comment>")),
            'reporter did not print the skipped test info to the output interface as expected'
        );
    }

    /**
     * @test
     */
    public function registerFailedTest_reportsTestFailureMessage()
    {
        $fakeOutput = $this->getOutputInterfaceSpy();
        $sut = new DefaultKaseCLIReporter($fakeOutput);
        $someTestDescription = 'some test description';
        $someValidationFailureMessage = 'this is why it failed';
        $someValidationException = new ValidationFailureException($someValidationFailureMessage);

        $sut->registerFailedTest($someTestDescription, $someValidationException);

        $outputSpy = $fakeOutput->reflector();
        $this->assertCount(
            1,
            $outputSpy->writeln("<error>[FAIL] {$someTestDescription}</error>"),
            'reporter did not print the failed test info to the output interface as expected'
        );
    }

    /**
     * @test
     */
    public function registerFailedTest_reportsTestFailureMessageAndValueDetails_whenExpectedAndActualValuesGiven()
    {
        $fakeOutput = $this->getOutputInterfaceSpy();
        $sut = new DefaultKaseCLIReporter($fakeOutput);
        $someTestDescription = 'something failed';
        $someValidationFailureMessage = 'this is why it failed';
        $someExpectedValue = [true];
        $someActualValue = [false];
        $someValidationException = new ValidationFailureException(
            $someValidationFailureMessage,
            $someExpectedValue,
            $someActualValue
        );

        $sut->registerFailedTest($someTestDescription, $someValidationException);

        $outputSpy = $fakeOutput->reflector();
        $this->assertCount(
            1,
            $outputSpy->writeln("<error>[FAIL] {$someTestDescription}</error>"),
            'reporter did not print the failed test info to the output interface as expected'
        );
    }

    /**
     * @test
     */
    public function registerUnexpectedException_reportsDetailsOnAnUnexpectedException()
    {
        $fakeOutput = $this->getOutputInterfaceSpy();
        $sut = new DefaultKaseCLIReporter($fakeOutput);
        $someUnexpectedException = new \Exception('something unexpected happened');

        $sut->registerUnexpectedException($someUnexpectedException);

        $outputSpy = $fakeOutput->reflector();
        $this->assertTrue(occurredSequentially(
            $outputSpy->writeln("<error>[FAIL] Unexpected {$someUnexpectedException}</error>")
        ), 'reporter did not print the unexpected exception message to the output interface as expected');
    }

    /**
     * @test
     */
    public function registerSuiteExecutionCompletion_reportsDetailsOnIndividualTestSuiteCompletion()
    {
        $fakeOutput = $this->getOutputInterfaceSpy();
        $sut = new DefaultKaseCLIReporter($fakeOutput);
        $someSuiteDescription = 'Some Test Suite';
        $someSuiteMetrics = [
            'executionStartTime' => 0,
            'executionEndTime' => 3.52,
            'passedTestCount' => 20,
            'failedTests' => [
                'failed test 1' => new \Exception()
            ],
            'skippedTests' => [
                'skipped test 1',
                'skipped test 2'
            ]
        ];

        $sut->registerSuiteExecutionCompletion($someSuiteDescription, $someSuiteMetrics);

        $expectedDurationInSeconds = '3.520';
        $expectedPassedTestCount = 20;
        $expectedFailedTestCount = 1;
        $expectedSkippedTestCount = 2;

        $outputSpy = $fakeOutput->reflector();
        $this->assertTrue(
            occurredSequentially(
                $outputSpy->writeln('//'),
                $outputSpy->writeln("// {$someSuiteDescription} tested in {$expectedDurationInSeconds} seconds"),
                $outputSpy->writeln("// <info>{$expectedPassedTestCount}</info> Passed, <error>{$expectedFailedTestCount}</error> Failed, <comment>{$expectedSkippedTestCount}</comment> Skipped"),
                $outputSpy->writeln('///////////////////////////////////////////'),
                $outputSpy->writeln(''),
                $outputSpy->writeln('')
            ),
            'reporter did not print the suite summary as expected'
        );
    }

    /**
     * @test
     */
    public function registerSuiteMetricsSummary_reportsWarning_whenNoTestsExecuted()
    {
        $fakeOutput = $this->getOutputInterfaceSpy();
        $sut = new DefaultKaseCLIReporter($fakeOutput);
        $emptySuiteMetricsList = [];

        $sut->registerSuiteMetricsSummary($emptySuiteMetricsList);

        $outputSpy = $fakeOutput->reflector();
        $this->assertCount(
            1,
            $outputSpy->writeln('No test files found'),
            'reporter did not send tests not found warning to output as expected'
        );
    }

    /**
     * @test
     */
    public function registerSuiteMetricsSummary_reportsDetailsOfAllExecutedTestSuites_whenAtLeast1TestExecuted()
    {
        $fakeOutput = $this->getOutputInterfaceSpy();
        $sut = new DefaultKaseCLIReporter($fakeOutput);
        $someValidationFailureMessage = 'something went wrong';
        $someValidationException = new ValidationFailureException($someValidationFailureMessage);

        $anotherValidationFailureMessage = 'something went wrong again';
        $someExpectedValue = [true];
        $someActualValue = [false];
        $anotherValidationException = new ValidationFailureException(
            $anotherValidationFailureMessage,
            $someExpectedValue,
            $someActualValue
        );

        $suiteMetricsList = [
            // some suite A
            [
                'suiteDescription' => 'Some Suite A',
                'executionStartTime' => 0,
                'executionEndTime' => 3.52,     // 3.52 second runtime
                'passedTestCount' => 20,
                'failedTests' => [
                    'failed test 1' => $someValidationException,
                    'failed test 2' => $anotherValidationException
                ],
                'skippedTests' => [
                    'skipped test 1',
                    'skipped test 2'
                ]
            ],

            // some suite B
            [
                'suiteDescription' => 'Some Suite B',
                'executionStartTime' => 3.55,
                'executionEndTime' => 5.05,     // 1.50 second runtime
                'passedTestCount' => 15,
                'failedTests' => [],
                'skippedTests' => [
                    'skipped test 3'
                ]
            ]
        ];

        $sut->registerSuiteMetricsSummary($suiteMetricsList);

        $expectedTotalDurationInSeconds = '5.020';
        $expectedPassedTestCount = 35;
        $expectedFailedTestCount = 2;
        $expectedSkippedTestCount = 3;

        $outputSpy = $fakeOutput->reflector();
        $this->assertTrue(
            occurredSequentially(
                $outputSpy->writeln('///////////////////////////////////////////'),
                $outputSpy->writeln('//'),
                $outputSpy->writeln('//  TESTING SUMMARY'),
                $outputSpy->writeln('//'),
                $outputSpy->writeln("// <info>{$expectedPassedTestCount}</info> Passed, <error>{$expectedFailedTestCount}</error> Failed, <comment>{$expectedSkippedTestCount}</comment> Skipped"),
                $outputSpy->writeln("// Completed in {$expectedTotalDurationInSeconds} seconds"),
                $outputSpy->writeln(''),
                $outputSpy->writeln('In Some Suite A:'),
                $outputSpy->writeln('<error>[FAIL] failed test 1</error>'),
                $outputSpy->write("<error>{$someValidationFailureMessage}</error>"),
                $outputSpy->writeln(''),
                $outputSpy->writeln(''),
                $outputSpy->writeln('<error>[FAIL] failed test 2</error>'),
                $outputSpy->write("<error>{$anotherValidationFailureMessage}</error>"),
                $outputSpy->writeln(
"<error>
--- Expected
+++ Actual
@@ @@
 Array (
-    0 => true
+    0 => false
 )
</error>")
            ),
            'reporter did not print the overall suite execution summary as expected'
        );
    }
}
