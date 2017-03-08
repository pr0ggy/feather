<?php

namespace Feather;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use function Nark\createSpyInstanceOf;

class RunFeatherTestsConsoleAppCommandTest extends TestCase
{
    /**
     * @test
     */
    public function execute_printsFeatherVersionToOutput()
    {
        list($command, $commandTester) = $this->createCommandAndTester();
        $commandTester->execute(array(
            'command'  => $command->getName(),
            '--test-folder' => 'test/fixtures/fake_test_files'
        ));
        $output = $commandTester->getDisplay();
        $this->assertContains('Feather '.FEATHER_VERSION, $output);
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
    public function execute_filtersTestFoldersAndFilesCorrectlyAccordingToSpecifiedPatterns()
    {
        list($command, $commandTester) = $this->createCommandAndTester();
        $commandTester->execute(array(
            'command'  => $command->getName(),
            '--test-folder' => 'test/fixtures/fake_test_files'
        ));
        $output = $commandTester->getDisplay();
        $this->assertContains('FAKE TEST FILE 1', $output);
        $this->assertContains('FAKE TEST FILE 2', $output);
        $this->assertContains('FAKE TEST FILE 3', $output);
        $this->assertContains('FAKE TEST FILE 4', $output);

        $commandTester->execute(array(
            'command'  => $command->getName(),
            '--test-folder' => 'test/fixtures',
        ));
        $output = $commandTester->getDisplay();
        $this->assertContains('FAKE TEST FILE 3', $output);
    }

    /**
     * @test
     */
    public function execute_printsWarningMessageToOutput_whenNoTestFilesFound()
    {
        list($command, $commandTester) = $this->createCommandAndTester();
        $commandTester->execute(array(
            'command'  => $command->getName(),
            '--test-folder' => 'test/fixtures/fake_test_files',
            '--test-file-pattern' => '/.+3\.nonexistent_test\.php/'
        ));
        $output = $commandTester->getDisplay();
        $this->assertContains('No test files found matching the pattern: "/.+3\.nonexistent_test\.php/"', $output);
    }

    /**
     * @test
     */
    public function execute_printsExecutedTestsSummary_whenTestFilesAreFound()
    {
        $fakeSuiteReporter = createSpyInstanceOf('\Feather\SuiteReporter');
        $fakeTestValidator = createSpyInstanceOf('\Feather\TestValidator');
        Context::unregisterSingletonInstance();
        Context::createAndRegisterSingletonWithConstructionArgs($fakeTestValidator, $fakeSuiteReporter);
        
        list($command, $commandTester) = $this->createCommandAndTester();
        $commandTester->execute(array(
            'command'  => $command->getName(),
            '--test-folder' => 'test/fixtures/fake_test_files'
        ));

        $feather = Context::getInstance();

        $suiteReporter = $fakeSuiteReporter->reflector();
        $this->assertEquals(1, count($suiteReporter->registerSuiteMetricsSummary($feather->executedSuiteMetrics)), 'a');
    }
}
