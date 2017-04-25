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
    public function execute_printsErrorMessageToOutput_whenNoBootstrapFound()
    {
        list($command, $commandTester) = $this->createCommandAndTester();
        $commandTester->execute([
            'command'  => $command->getName(),
            '--config' => 'test/fixtures/nonexistent-config.php'
        ]);

        $output = $commandTester->getDisplay();
        $this->assertContains('Error: Could not find specified Kase config file: test/fixtures/nonexistent-config.php', $output);
    }

    private function createCommandAndTester()
    {
        $application = new Application();
        $application->add(new RunKaseTestsCommand());

        $command = $application->find('run');
        return [$command, new CommandTester($command)];
    }

    /**
     * @test
     */
    public function execute_UsesTestSuitePathProviderDefinedInConfigToProvideTestSuites()
    {
        list($command, $commandTester) = $this->createCommandAndTester();

        $commandTester->execute([
            'command'  => $command->getName(),
            '--config' => 'tests/fixtures/testing-setup/kase-config.php'
        ]);

        $output = $commandTester->getDisplay();
        $this->assertContains('TEST FILE 1 INCLUDED', $output,
            'files not provided from suite path provider as expected');
        $this->assertContains('TEST FILE 2 INCLUDED', $output,
            'files not provided from suite path provider as expected');
    }

    /**
     * @test
     */
    public function execute_UsesReporterInstanceDefinedInConfig()
    {
        list($command, $commandTester) = $this->createCommandAndTester();

        $commandTester->execute([
            'command'  => $command->getName(),
            '--config' => 'tests/fixtures/testing-setup/kase-config.php'
        ]);

        $reporterInstance = Fakes\FakeReporter::instance();
        $this->assertTrue($reporterInstance->receivedInitFromRunner(),
            'reporter defined in config not used by runner as expected');
    }

    /**
     * @test
     */
    public function execute_UsesValidatorInstanceDefinedInConfig()
    {
        list($command, $commandTester) = $this->createCommandAndTester();

        $commandTester->execute([
            'command'  => $command->getName(),
            '--config' => 'tests/fixtures/testing-setup/kase-config.php'
        ]);

        $validatorInstance = Fakes\FakeValidator::instance();
        $this->assertTrue($validatorInstance->receivedPassInvocation(),
            'validator defined in config not used by runner as expected');
    }
}
