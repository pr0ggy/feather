<?php

namespace Kase\Test;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use function Nark\createSpyInstanceOf;
use Kase\RunKaseTestsCommand;
use Kase\Test\Utils\TestingException;

class RunKaseTestsCommandTest extends TestCase
{
    /**
     * @test
     */
    public function execute_usesGivenTestDirOptionToSearchForTestFiles()
    {
        list($command, $commandTester) = $this->createCommandAndTester();
        $commandTester->execute([
            'command'  => $command->getName(),
            '--test-dir' => __DIR__.'/fixtures/tests/Bar'
        ]);

        $output = $commandTester->getDisplay();
        $this->assertContains('BAR TEST FILE 1 INCLUDED', $output);
        $this->assertNotContains('FOO TEST FILE 1 INCLUDED', $output);
        $this->assertNotContains('FOO TEST FILE 2 INCLUDED', $output);
    }

    private function createCommandAndTester($kaseConfig = null)
    {
        $application = new Application();
        $application->add(new RunKaseTestsCommand($kaseConfig));

        $command = $application->find('run');
        return [$command, new CommandTester($command)];
    }

    /**
     * @test
     */
    public function execute_printsErrorMessageToOutput_whenSpecifiedTestDirNotFound()
    {
        $nonexistentDir = __DIR__.'/fixtures/tests/NonexistentDir';
        list($command, $commandTester) = $this->createCommandAndTester();
        $commandTester->execute([
            'command'  => $command->getName(),
            '--test-dir' => $nonexistentDir
        ]);

        $output = $commandTester->getDisplay();
        $this->assertContains("Error: Could not find specified specified test directory: {$nonexistentDir}", $output);
    }

    /**
     * @test
     */
    public function execute_usesGivenFilePatternOptionToSearchForTestFiles()
    {
        list($command, $commandTester) = $this->createCommandAndTester();
        $commandTester->execute([
            'command'  => $command->getName(),
            '--file-pattern' => '*-1.test.php',
            '--test-dir' => __DIR__.'/fixtures/tests'
        ]);

        $output = $commandTester->getDisplay();
        $this->assertContains('BAR TEST FILE 1 INCLUDED', $output);
        $this->assertContains('FOO TEST FILE 1 INCLUDED', $output);
        $this->assertNotContains('FOO TEST FILE 2 INCLUDED', $output);
    }

    /**
     * @test
     */
    public function execute_printsErrorMessageToOutput_whenConfigPathGivenButNotFound()
    {
        list($command, $commandTester) = $this->createCommandAndTester();
        $commandTester->execute([
            'command'  => $command->getName(),
            '--config' => 'test/fixtures/nonexistent-config.php'
        ]);

        $output = $commandTester->getDisplay();
        $this->assertContains('Error: Could not find specified Kase config file: test/fixtures/nonexistent-config.php', $output);
    }

    /**
     * @test
     */
    public function execute_printsErrorMessageToOutput_whenATestSuiteFileDoesNotReturnACallable()
    {
        $testFileFixtureDir = __DIR__.'/fixtures/tests';
        $emptyTestFileFixtureName = 'empty-test-file.php';
        $expectedAbsoluteFileFixturePath = realpath("{$testFileFixtureDir}/{$emptyTestFileFixtureName}");

        list($command, $commandTester) = $this->createCommandAndTester();
        $commandTester->execute([
            'command'  => $command->getName(),
            '--test-dir' => $testFileFixtureDir,
            '--file-pattern' => $emptyTestFileFixtureName
        ]);

        $output = $commandTester->getDisplay();
        $this->assertContains("Error: Suite file does not return a callable test suite: {$expectedAbsoluteFileFixturePath}", $output);
    }

    /**
     * @test
     */
    public function execute_usesValidatorInstanceDefinedInConfig()
    {
        $kaseConfig = [
            'testSuitePathProvider' => [$this, 'fixtureTestPathProvider'],
            'validator' => new Utils\MethodRecorder()
        ];
        list($command, $commandTester) = $this->createCommandAndTester($kaseConfig);

        $commandTester->execute([
            'command'  => $command->getName(),
            '--test-dir' => __DIR__.'/fixtures/tests'
        ]);

        $validatorInstance = $kaseConfig['validator'];
        $numberOfFixtureTestsFiles = 3; // Number of fake test files located in tests/fixtures/tests
        $this->assertEquals($numberOfFixtureTestsFiles, $validatorInstance->callCountForMethod('pass'),
            'validator defined in config not used by runner as expected');
    }

    /**
     * @test
     */
    public function execute_registersTestingInitializationAndMetricsSummaryWithReporterInstanceDefinedInConfig()
    {
        $kaseConfig = [
            'testSuitePathProvider' => [$this, 'fixtureTestPathProvider'],
            'reporter' => new Utils\MethodRecorder()
        ];
        list($command, $commandTester) = $this->createCommandAndTester($kaseConfig);

        $commandTester->execute([
            'command'  => $command->getName(),
            '--test-dir' => __DIR__.'/fixtures/tests'
        ]);

        $reporterInstance = $kaseConfig['reporter'];
        $this->assertEquals(1, $reporterInstance->callCountForMethod('registerTestRunnerInitialization'),
            'testing initialization not registered with reporter defined in config as expected when running command');
        $this->assertEquals(1, $reporterInstance->callCountForMethod('registerSuiteMetricsSummary'),
            'metrics summary not registered with reporter defined in config as expected when running command');
    }
}
