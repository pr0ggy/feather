<?php

namespace Feather;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use function Nark\createSpyInstanceOf;

/**
 * @runTestsInSeparateProcesses
 */
class RunFeatherTestsConsoleAppCommandTest extends TestCase
{
    /**
     * @test
     */
    public function execute_printsErrorMessageToOutput_whenNoBootstrapFound()
    {
        list($command, $commandTester) = $this->createCommandAndTester();
        $commandTester->execute(array(
            'command'  => $command->getName(),
            '--bootstrap' => 'test/fixtures/nonexistent-bootstrap.php'
        ));
        $output = $commandTester->getDisplay();
        $this->assertContains('Error: Could not find specified Feather bootstrap file: test/fixtures/nonexistent-bootstrap.php', $output);
    }

    private function createCommandAndTester()
    {
        $application = new Application();
        $application->add(new RunFeatherTestsConsoleAppCommand());

        $command = $application->find('run');
        return [$command, new CommandTester($command)];
    }

    /**
     * @test
     */
    public function execute_printsWarningMessageToOutput_whenNoTestFilesFound()
    {
        list($command, $commandTester) = $this->createCommandAndTester();
        $commandTester->execute(array(
            'command'  => $command->getName(),
            '--bootstrap' => 'test/fixtures/fake-feather-bootstrap.php'
        ));

        // the fake bootstrap doesn't do anything, so no test will run
        $output = $commandTester->getDisplay();
        $this->assertContains('No test files found', $output);
    }

    /**
     * @test
     */
    public function execute_printsExecutedTestsSummary_whenTestFilesAreFoundAndSuitesExecuted()
    {
        $fakeSuiteReporter = createSpyInstanceOf('\Feather\SuiteReporter');
        $fakeTestValidator = createSpyInstanceOf('\Feather\TestValidator');
        Context::unregisterSingletonInstance();
        Context::createAndRegisterSingletonWithConstructionArgs($fakeTestValidator, $fakeSuiteReporter);
        // if suites have been executed, metrics summaries will exist, so lets fake a couple metric summaries
        $feather = Context::getInstance();
        $fakeSuiteMetrics = [ [/*fake suite metrics A*/], [/*fake suite metrics B*/] ];
        $feather->executedSuiteMetrics = $fakeSuiteMetrics;

        list($command, $commandTester) = $this->createCommandAndTester();
        $commandTester->execute(array(
            'command'  => $command->getName(),
            '--bootstrap' => 'test/fixtures/fake-feather-bootstrap.php'
        ));

        $suiteReporter = $fakeSuiteReporter->reflector();
        $this->assertEquals(1, count($suiteReporter->registerSuiteMetricsSummary($feather->executedSuiteMetrics)), 'a');
    }
}
