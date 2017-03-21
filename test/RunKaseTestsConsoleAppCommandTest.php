<?php

namespace Kase;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use function Nark\createSpyInstanceOf;

class RunKaseTestsConsoleAppCommandTest extends TestCase
{
    /**
     * @test
     */
    public function execute_printsKaseVersion()
    {
        list($command, $commandTester) = $this->createCommandAndTester();
        $commandTester->execute([
            'command'  => $command->getName(),
            '--bootstrap' => 'test/fixtures/fake-kase-bootstrap.php'
        ]);
        $output = $commandTester->getDisplay();
        $this->assertContains('Kase '.VERSION, $output);
    }

    /**
     * @test
     */
    public function execute_printsErrorMessageToOutput_whenNoBootstrapFound()
    {
        list($command, $commandTester) = $this->createCommandAndTester();
        $commandTester->execute([
            'command'  => $command->getName(),
            '--bootstrap' => 'test/fixtures/nonexistent-bootstrap.php'
        ]);
        $output = $commandTester->getDisplay();
        $this->assertContains('Error: Could not find specified Kase bootstrap file: test/fixtures/nonexistent-bootstrap.php', $output);
    }

    private function createCommandAndTester()
    {
        $application = new Application();
        $application->add(new RunKaseTestsConsoleAppCommand());

        $command = $application->find('run');
        return [$command, new CommandTester($command)];
    }

    /**
     * @test
     */
    public function execute_printsWarningMessageToOutput_whenNoTestFilesFound()
    {
        list($command, $commandTester) = $this->createCommandAndTester();
        $commandTester->execute([
            'command'  => $command->getName(),
            '--bootstrap' => 'test/fixtures/fake-kase-bootstrap.php'
        ]);

        // the fake bootstrap doesn't do anything, so no test will run
        $output = $commandTester->getDisplay();
        $this->assertContains('No test files found', $output);
    }
}
