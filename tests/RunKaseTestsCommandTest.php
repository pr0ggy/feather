<?php

namespace Kase\Test;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use function Nark\createSpyInstanceOf;
use Kase\RunKaseTestsCommand;

class RunKaseTestsCommandTest extends TestCase
{
    /**
     * @test
     */
    public function execute_printsErrorMessageToOutput_whenConfigPathGivenButNotFound()
    {
        list($command, $commandTester) = $this->createCommandAndTester();
        $commandTester->execute([
            'command'  => $command->getName(),
            '--config' => 'tests/fixtures/nonexistent-config.php'
        ]);

        $output = $commandTester->getDisplay();
        $this->assertContains('Error: Could not find specified Kase config file: tests/fixtures/nonexistent-config.php', $output);
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
    public function execute_printsErrorMessageToOutput_whenConfigAtGivenPathDoesNotReturnKVMap()
    {
        list($command, $commandTester) = $this->createCommandAndTester();
        $commandTester->execute([
            'command'  => $command->getName(),
            '--config' => 'tests/fixtures/kase-config-not-returning-kv-map.php'
        ]);

        $output = $commandTester->getDisplay();
        $this->assertContains('Error: Specified config file does not return a key/value map', $output);
    }

    /**
     * @test
     */
    public function execute_usesResourcesDefinedInConfig_whenPathToValidConfigGiven()
    {
        list($command, $commandTester) = $this->createCommandAndTester();
        $commandTester->execute([
            'command'  => $command->getName(),
            '--config' => 'tests/fixtures/kase-config-using-method-recorders.php',
            '--test-dir' => __DIR__.'/fixtures/tests'
        ]);

        // Utils\MethodRecorderContainer is used within the config file specified above to define a
        // few fake resources.  See the config file specified above as well as the MethodRecorderContainer
        // class to understand what's happening here.  There may be a simpler way to implement/test
        // this but I'm not certain of it at the moment
        list($reporter, $validator) = Utils\MethodRecorderContainer::getLastNRecorders(2);
        $numberOfFixtureTestsFiles = 3; // Number of fake test files located in tests/fixtures/tests
        $this->assertEquals($numberOfFixtureTestsFiles, $validator->callCountForMethod('pass'),
            'validator defined in config not used by runner as expected');
        $this->assertEquals(1, $reporter->callCountForMethod('registerTestRunnerInitialization'),
            'testing initialization not registered with reporter defined in config as expected when running command');
        $this->assertEquals(1, $reporter->callCountForMethod('registerSuiteMetricsSummary'),
            'metrics summary not registered with reporter defined in config as expected when running command');
    }

    /**
     * @test
     */
    public function execute_includesBootstrapFile_ifBootstrapGivenInConfig()
    {
        list($command, $commandTester) = $this->createCommandAndTester();
        $commandTester->execute([
            'command'  => $command->getName(),
            '--config' => 'tests/fixtures/kase-config-defining-bootstrap.php',
            '--test-dir' => __DIR__.'/fixtures/tests'
        ]);

        $this->assertTrue(defined('Kase\Test\BOOTSTRAP_INCLUDED'),
            'bootstrap file defined in the test config was not included as expected');
    }

    /**
     * @test
     */
    public function execute_printsErrorMessageToOutput_ifBootstrapGivenInConfigButIsNotFound()
    {
        list($command, $commandTester) = $this->createCommandAndTester();
        $commandTester->execute([
            'command'  => $command->getName(),
            '--config' => 'tests/fixtures/kase-config-defining-missing-bootstrap.php',
            '--test-dir' => __DIR__.'/fixtures/tests'
        ]);

        $expectedBootstrapPath = realpath(__DIR__.'/fixtures/kase-bootstrap-that-does-not-exist.php');
        $output = $commandTester->getDisplay();
        $this->assertContains("Specified bootstrap could not be found: {$expectedBootstrapPath}", $output);
    }

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
}
