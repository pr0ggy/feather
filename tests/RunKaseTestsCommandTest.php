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
    public function execute_usesTestingResourcesPassedDirectlyToCommand()
    {
        $kaseConfig = [
            'testSuitePathProvider' => [$this, 'fixtureTestPathProvider']
        ];

        $application = new Application();
        $application->add(new RunKaseTestsCommand($kaseConfig));

        $command = $application->find('run');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'command'  => $command->getName()
        ]);

        // if the fixture test files were included, this is proof the command used the config given
        // during construction as expected
        $output = $commandTester->getDisplay();
        $this->assertContains('TEST FILE 1 INCLUDED', $output,
            'files not provided from suite path provider as expected');
        $this->assertContains('TEST FILE 2 INCLUDED', $output,
            'files not provided from suite path provider as expected');
    }

    public function fixtureTestPathProvider()
    {
        $testSuiteDir = dirname(__FILE__).'/fixtures/testing-setup';
        yield "{$testSuiteDir}/tests/test-1.test.php";
        yield "{$testSuiteDir}/tests/test-2.test.php";
    }

    /**
     * @test
     */
    public function execute_readsConfigFilePathFromCommandArg_whenConfigNotExplicitlyPassedToCommand()
    {
        list($command, $commandTester) = $this->createCommandAndTester();

        $commandTester->execute([
            'command'  => $command->getName(),
            '--config' => 'tests/fixtures/testing-setup/kase-config.php'
        ]);

        // if the fixture test files were included, this is proof the command read in the fixture
        // config and used it as expected
        $output = $commandTester->getDisplay();
        $this->assertContains('TEST FILE 1 INCLUDED', $output,
            'files not provided from suite path provider as expected');
        $this->assertContains('TEST FILE 2 INCLUDED', $output,
            'files not provided from suite path provider as expected');
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
    public function execute_printsErrorMessageToOutput_whenNoBootstrapFoundAndNoConfigGiven()
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
    public function execute_displaysErrorMessage_whenNoTestSuitePathProviderDefinedInConfig()
    {
        $emptyKaseConfig = [];
        list($command, $commandTester) = $this->createCommandAndTester($emptyKaseConfig);

        $commandTester->execute([
            'command'  => $command->getName()
        ]);

        $output = $commandTester->getDisplay();
        $this->assertContains('Error: Required "testSuitePathProvider" callable not found in config', $output,
            'no error message sent to output as expected');
    }

    /**
     * @test
     */
    public function execute_displaysErrorMessage_whenTestSuitePathProviderDefinedInConfigNotCallable()
    {
        $kaseConfigWithBadPathProvider = [
            'testSuitePathProvider' => false
        ];
        list($command, $commandTester) = $this->createCommandAndTester($kaseConfigWithBadPathProvider);

        $commandTester->execute([
            'command'  => $command->getName()
        ]);

        $output = $commandTester->getDisplay();
        $this->assertContains('Error: Required "testSuitePathProvider" callable not found in config', $output,
            'no error message sent to output as expected');
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
            'command'  => $command->getName()
        ]);

        $validatorInstance = $kaseConfig['validator'];
        $this->assertEquals(2, $validatorInstance->callCountForMethod('pass'),
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
            'command'  => $command->getName()
        ]);

        $reporterInstance = $kaseConfig['reporter'];
        $this->assertEquals(1, $reporterInstance->callCountForMethod('registerTestRunnerInitialization'),
            'testing initialization not registered with reporter defined in config as expected when running command');
        $this->assertEquals(1, $reporterInstance->callCountForMethod('registerSuiteMetricsSummary'),
            'metrics summary not registered with reporter defined in config as expected when running command');
    }
}
