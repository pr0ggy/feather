<?php

namespace Kase;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use function Nark\createSpyInstanceOf;

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
            '--bootstrap' => 'test/fixtures/nonexistent-bootstrap.php'
        ]);
        $output = $commandTester->getDisplay();
        $this->assertContains('Error: Could not find specified Kase bootstrap file: test/fixtures/nonexistent-bootstrap.php', $output);
    }

    private function createCommandAndTester()
    {
        $application = new Application();
        $application->add(new RunKaseTestsCommand());

        $command = $application->find('run');
        return [$command, new CommandTester($command)];
    }
}
