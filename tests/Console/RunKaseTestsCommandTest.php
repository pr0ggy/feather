<?php

namespace Kase\Test\Console;

use PHPUnit\Framework\TestCase;
use function Nark\createSpyInstanceOf;
use Kase\Console\RunKaseTestsCommand;
use Kase\Utils;
use Kase\Test\TestUtils;
use Kase\Test\Base;

class RunKaseTestsCommandTest extends Base\KaseCommandTestCase
{
    /**
     * @test
     */
    public function execute_printsErrorMessageToOutput_whenConfigPathGivenButNotFound()
    {
        $missingConfigPath = Utils\pathFromKaseProjectRoot('/tests/fixtures/nonexistent-config.php');

        $tester = $this->runCommandTest(
            $this->createCommandSUT(),
            ['--config' => $missingConfigPath]
        );

        $this->assertContains(
            "Error: Could not find specified Kase config file: {$missingConfigPath}",
            $tester->getDisplay(),
            'Missing config file error not shown as expected'
        );
    }

    protected function createCommandSUT(...$creationArgs)
    {
        return new RunKaseTestsCommand();
    }

    /**
     * @test
     */
    public function execute_printsErrorMessageToOutput_whenConfigAtGivenPathDoesNotReturnKVDict()
    {
        $tester = $this->runCommandTest(
            $this->createCommandSUT(),
            [
                '--config' => Utils\pathFromKaseProjectRoot('tests/fixtures/kase-config-not-returning-kv-dict.php')
            ]
        );

        $this->assertContains(
            'Error: Specified config file does not return a key/value dictionary',
            $tester->getDisplay(),
            'No error shown as expected'
        );
    }

    /**
     * @test
     */
    public function execute_usesResourcesDefinedInConfig_whenPathToValidConfigGiven()
    {
        $tester = $this->runCommandTest(
            $this->createCommandSUT(),
            [
                '--config' => Utils\pathFromKaseProjectRoot('/tests/fixtures/kase-config-using-method-recorders.php'),
                '--test-dir' => Utils\pathFromKaseProjectRoot('/tests/fixtures/tests')
            ]
        );

        // TestUtils\MethodRecorderContainer is used within the config file specified above to define a
        // few fake resources.  See the config file specified above as well as the MethodRecorderContainer
        // class to understand what's happening here.  There may be a simpler way to implement/test
        // this but I'm not certain of it at the moment
        list($reporter, $validator) = TestUtils\MethodRecorderContainer::getLastNRecorders(2);
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
        $tester = $this->runCommandTest(
            $this->createCommandSUT(),
            [
                '--config' => Utils\pathFromKaseProjectRoot('/tests/fixtures/kase-config-defining-bootstrap.php'),
                '--test-dir' => Utils\pathFromKaseProjectRoot('/tests/fixtures/tests')
            ]
        );

        $this->assertTrue(defined('Kase\Test\BOOTSTRAP_INCLUDED'),
            'bootstrap file defined in the test config was not included as expected');
    }

    /**
     * @test
     */
    public function execute_printsErrorMessageToOutput_ifBootstrapGivenInConfigButIsNotFound()
    {
        $tester = $this->runCommandTest(
            $this->createCommandSUT(),
            [
                '--config' => __DIR__.'/../fixtures/kase-config-defining-missing-bootstrap.php',
                '--test-dir' => Utils\pathFromKaseProjectRoot('/tests/fixtures/tests')
            ]
        );

        $bootstrapPathDefinedInConfig = Utils\pathFromKaseProjectRoot('/tests/fixtures').'/kase-bootstrap-that-does-not-exist.php';
        $this->assertContains(
            "Specified bootstrap could not be found: {$bootstrapPathDefinedInConfig}",
            $tester->getDisplay()
        );
    }

    /**
     * @test
     */
    public function execute_usesGivenTestDirOptionToSearchForTestFiles()
    {
        $tester = $this->runCommandTest(
            $this->createCommandSUT(),
            [
                '--test-dir' => Utils\pathFromKaseProjectRoot('/tests/fixtures/tests/Bar')
            ]
        );

        $output = $tester->getDisplay();
        $this->assertContains('BAR TEST FILE 1 INCLUDED', $output);
        $this->assertNotContains('FOO TEST FILE 1 INCLUDED', $output);
        $this->assertNotContains('FOO TEST FILE 2 INCLUDED', $output);
    }

    /**
     * @test
     */
    public function execute_printsErrorMessageToOutput_whenSpecifiedTestDirNotFound()
    {
        $someNonexistentDir = __DIR__.'/../fixtures/tests/NonexistentDir';
        $tester = $this->runCommandTest(
            $this->createCommandSUT(),
            [
                '--test-dir' => $someNonexistentDir
            ]
        );

        $this->assertContains(
            "Error: Could not find specified specified test directory: {$someNonexistentDir}",
            $tester->getDisplay()
        );
    }

    /**
     * @test
     */
    public function execute_usesGivenFilePatternOptionToSearchForTestFiles()
    {
        $tester = $this->runCommandTest(
            $this->createCommandSUT(),
            [
                '--file-pattern' => '*-1.test.php',
                '--test-dir' => __DIR__.'/../fixtures/tests'
            ]
        );

        $output = $tester->getDisplay();
        $this->assertContains('BAR TEST FILE 1 INCLUDED', $output);
        $this->assertContains('FOO TEST FILE 1 INCLUDED', $output);
        $this->assertNotContains('FOO TEST FILE 2 INCLUDED', $output);
    }

    /**
     * @test
     */
    public function execute_printsErrorMessageToOutput_whenATestSuiteFileDoesNotReturnACallable()
    {
        $testFileFixtureDir = Utils\pathFromKaseProjectRoot('/tests/fixtures/tests');
        $emptyTestFileFixtureName = 'empty-test-file.php';
        $expectedAbsoluteFileFixturePath = "{$testFileFixtureDir}/{$emptyTestFileFixtureName}";

        $tester = $this->runCommandTest(
            $this->createCommandSUT(),
            [
                '--test-dir' => $testFileFixtureDir,
                '--file-pattern' => $emptyTestFileFixtureName
            ]
        );

        $this->assertContains(
            "Error: Suite file does not return a callable test suite: {$expectedAbsoluteFileFixturePath}",
            $tester->getDisplay()
        );
    }
}
