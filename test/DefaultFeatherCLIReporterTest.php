<?php

namespace Feather;

use PHPUnit\Framework\TestCase;
use function Nark\occurredSequentially;

class DefaultFeatherCLIReporterTest extends TestCase
{
    /**
     * @test
     */
    public function registerSuiteExecutionInitiation_reportsSuiteDescription()
    {
        $outputSpy = $this->getOutputInterfaceSpy();
        $sut = new DefaultFeatherCLIReporter($outputSpy);
        $someTestSuiteName = 'Test Suite Alpha';

        $sut->registerSuiteExecutionInitiation($someTestSuiteName);

        $output = $outputSpy->reflector();
        $this->assertTrue(occurredSequentially(
            $output->writeln('///////////////////////////////////////////'),
            $output->writeln('//'),
            $output->writeln("//  {$someTestSuiteName}"),
            $output->writeln('//')
        ), 'reporter did not print the test suite header to the output interface as expected');
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
        $outputSpy = $this->getOutputInterfaceSpy();
        $sut = new DefaultFeatherCLIReporter($outputSpy);
        $someTestDescription = 'something happens';

        $sut->registerPassedTest($someTestDescription);

        $output = $outputSpy->reflector();
        $this->assertEquals(1, count($output->writeln("<info>[PASS] {$someTestDescription}</info>")),
            'reporter did not print the passing test info to the output interface as expected');
    }

    /**
     * @test
     */
    public function registerSkippedTest_reportsDescriptionOfSkippedTest()
    {
        $outputSpy = $this->getOutputInterfaceSpy();
        $sut = new DefaultFeatherCLIReporter($outputSpy);
        $someTestDescription = 'something happens';

        $sut->registerSkippedTest($someTestDescription);

        $output = $outputSpy->reflector();
        $this->assertEquals(1, count($output->writeln("<comment>[SKIP] {$someTestDescription}</comment>")),
            'reporter did not print the skipped test info to the output interface as expected');
    }

    /**
     * @test
     */
    public function registerFailedTest_reportsTestFailureMessage()
    {
        $outputSpy = $this->getOutputInterfaceSpy();
        $sut = new DefaultFeatherCLIReporter($outputSpy);
        $someTestDescription = 'some test description';
        $someValidationFailureMessage = 'this is why it failed';
        $someValidationException = new ValidationFailureException($someValidationFailureMessage);

        $sut->registerFailedTest($someTestDescription, $someValidationException);

        $output = $outputSpy->reflector();
        $this->assertCount(1, $output->writeln("<error>[FAIL] {$someTestDescription}</error>"),
            'reporter did not print the failed test info to the output interface as expected');
    }

//     /**
//      * @test
//      */
//     public function registerFailedTest_reportsTestFailureMessageAndValueDetails_whenExpectedAndActualValuesGiven()
//     {
//         $outputSpy = $this->getOutputInterfaceSpy();
//         $sut = new DefaultFeatherCLIReporter($outputSpy);
//         $someTestDescription = 'something failed';
//         $someValidationFailureMessage = 'this is why it failed';
//         $someExpectedValue = [true];
//         $someActualValue = [false];
//         $someValidationException = new ValidationFailureException(
//             $someValidationFailureMessage,
//             $someExpectedValue,
//             $someActualValue
//         );

//         $sut->registerFailedTest($someTestDescription, $someValidationException);

//         $output = $outputSpy->reflector();
//         $this->assertTrue(occurredSequentially(
//             $output->writeln(""),
//             $output->writeln("<error>[FAIL] {$someTestDescription}</error>"),
//             $output->write("<error>{$someValidationFailureMessage}</error>"),
//             $output->writeln("<error>
// --- Expected
// +++ Actual
// @@ @@
//  Array (
// -    0 => true
// +    0 => false
//  )
// </error>"
//             )
//         ), 'reporter did not print the failed test info to the output interface as expected');
//     }

    /**
     * @test
     */
    public function registerUnexpectedException_reportsDetailsOnAnUnexpectedException()
    {
        $outputSpy = $this->getOutputInterfaceSpy();
        $sut = new DefaultFeatherCLIReporter($outputSpy);
        $someUnexpectedException = new \Exception('something unexpected happened');

        $sut->registerUnexpectedException($someUnexpectedException);

        $output = $outputSpy->reflector();
        $this->assertTrue(occurredSequentially(
            $output->writeln("<error>[FAIL] Unexpected {$someUnexpectedException}</error>")
        ), 'reporter did not print the unexpected exception message to the output interface as expected');
    }

    /**
     * @test
     */
    public function registerSuiteExecutionCompletion_reportsDetailsOnIndividualTestSuiteCompletion()
    {
        $outputSpy = $this->getOutputInterfaceSpy();
        $sut = new DefaultFeatherCLIReporter($outputSpy);
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

        $output = $outputSpy->reflector();
        $this->assertTrue(occurredSequentially(
            $output->writeln('//'),
            $output->writeln("// {$someSuiteDescription} tested in {$expectedDurationInSeconds} seconds"),
            $output->writeln("// <info>{$expectedPassedTestCount}</info> Passed, <error>{$expectedFailedTestCount}</error> Failed, <comment>{$expectedSkippedTestCount}</comment> Skipped"),
            $output->writeln('///////////////////////////////////////////'),
            $output->writeln(''),
            $output->writeln('')
        ), 'reporter did not print the suite summary as expected');
    }

    /**
     * @test
     */
    public function registerSuiteMetricsSummary_reportsNothing_whenNoTestsExecuted()
    {
        $outputSpy = $this->getOutputInterfaceSpy();
        $sut = new DefaultFeatherCLIReporter($outputSpy);
        $emptySuiteMetricsList = [];

        $sut->registerSuiteMetricsSummary($emptySuiteMetricsList);

        $output = $outputSpy->reflector();
        $this->assertEquals(count($output->writeln), 0,
            'reporter sent output to console when no output was expected');
    }

    /**
     * @test
     */
    public function registerSuiteMetricsSummary_reportsDetailsOfAllExecutedTestSuites_whenAtLeast1TestExecuted()
    {
        $outputSpy = $this->getOutputInterfaceSpy();
        $sut = new DefaultFeatherCLIReporter($outputSpy);
        $someValidationFailureMessage = 'something went wrong';
        $someValidationException = new ValidationFailureException($someValidationFailureMessage);
        $anotherValidationFailureMessage = 'something went wrong again';
        $anotherValidationException = new ValidationFailureException($anotherValidationFailureMessage);
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

        $output = $outputSpy->reflector();
        $this->assertTrue(occurredSequentially(
            $output->writeln('///////////////////////////////////////////'),
            $output->writeln('//'),
            $output->writeln('//  TESTING SUMMARY'),
            $output->writeln('//'),
            $output->writeln("// <info>{$expectedPassedTestCount}</info> Passed, <error>{$expectedFailedTestCount}</error> Failed, <comment>{$expectedSkippedTestCount}</comment> Skipped"),
            $output->writeln("// Completed in {$expectedTotalDurationInSeconds} seconds"),
            $output->writeln(''),
            $output->writeln('In Some Suite A:'),
            $output->writeln('<error>[FAIL] failed test 1</error>'),
            $output->write("<error>{$someValidationFailureMessage}</error>"),
            $output->writeln(''),
            $output->writeln(''),
            $output->writeln('<error>[FAIL] failed test 2</error>'),
            $output->write("<error>{$anotherValidationFailureMessage}</error>")
        ), 'reporter did not print the overall suite execution summary as expected');
    }
}
