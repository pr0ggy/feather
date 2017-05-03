<?php

namespace Kase\Test\Console;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Kase\Console\CreateKaseTestSuiteFileCommand;
use function Nark\createSpyInstanceOf;
use Kase\Utils;
use Kase\Test\Base;

class CreateKaseTestSuiteFileCommandTest extends Base\KaseCommandTestCase
{
    const TEST_SANDBOX_DIR = __DIR__.'/../test-creation-sandbox';
    const EXPECTED_BOILERPLATE_TEST_SUITE_WITHOUT_NAMESPACE = __DIR__.'/../fixtures/expectedKaseTestSuiteBoilerplateFileWithoutNamespace.php';
    const EXPECTED_BOILERPLATE_TEST_SUITE_WITH_FOO_NAMESPACE = __DIR__.'/../fixtures/expectedKaseTestSuiteBoilerplateFileWithFooNamespace.php';

    /**
     * @test
     * @expectedException Exceptions\IO\Filesystem\DirectoryNotFoundException
     */
    public function throwsException_ifGivenTestDirectoryNotFound()
    {
        $this->runCommandTest(
            $this->createCommandSUT(),
            [
                '--test-dir' => __DIR__.'/this-dir-doesnt-exist',
                'file-path' => 'someNewTest'
            ]
        );
    }

    protected function createCommandSUT(...$creationArgs)
    {
        return new CreateKaseTestSuiteFileCommand(Utils\pathFromKaseProjectRoot('/'));
    }

    /**
     * @test
     * @expectedException Exceptions\IO\Filesystem\FileAlreadyExistsException
     */
    public function throwsException_ifGivenTestFilePathAlreadyExists()
    {
        try {
            $someTestSuiteFileName = 'someTestSuite';
            $this->createTestFileInSandbox($someTestSuiteFileName);

            $this->runCommandTest(
                $this->createCommandSUT(),
                [
                    '--test-dir' => self::TEST_SANDBOX_DIR,
                    'file-path' => $someTestSuiteFileName
                ]
            );
        } finally {
            $this->cleanupTestSandbox();
        }
    }

    private function createTestFileInSandbox($fileName)
    {
        $fullFilePath = self::TEST_SANDBOX_DIR."/{$fileName}.php";
        file_put_contents($fullFilePath, '');
    }

    private function cleanupTestSandbox()
    {
        $files = glob(self::TEST_SANDBOX_DIR.'/*');
        foreach($files as $file){
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    /**
     * @test
     */
    public function createsANewTestFileWithGivenNameInGivenDirectory()
    {
        try {
            $someTestSuiteFileName = 'someTestSuite';
            $expectedFilePathCreated = self::TEST_SANDBOX_DIR."/{$someTestSuiteFileName}.php";

            $this->cleanupTestSandbox();
            $this->runCommandTest(
                $this->createCommandSUT(),
                [
                    '--test-dir' => self::TEST_SANDBOX_DIR,
                    'file-path' => $someTestSuiteFileName
                ]
            );

            if (file_exists($expectedFilePathCreated) === false) {
                $this->fail('Failed to create expected test suite file');
            }
        } finally {
            $this->cleanupTestSandbox();
        }
    }

    /**
     * @test
     */
    public function createdTestFileShouldContainExpectedBootstrapTestCode_whenNoNamespaceSpecified()
    {
        try {
            $someTestSuiteFileName = 'someTestSuite';
            $expectedFilePathCreated = self::TEST_SANDBOX_DIR."/{$someTestSuiteFileName}.php";

            $this->cleanupTestSandbox();
            $this->runCommandTest(
                $this->createCommandSUT(),
                [
                    '--test-dir' => self::TEST_SANDBOX_DIR,
                    'file-path' => $someTestSuiteFileName
                ]
            );

            $this->assertEquals(
                file_get_contents(self::EXPECTED_BOILERPLATE_TEST_SUITE_WITHOUT_NAMESPACE),
                file_get_contents($expectedFilePathCreated),
                'The created test suite file did not have the expected contents'
            );
        } finally {
            $this->cleanupTestSandbox();
        }
    }

    /**
     * @test
     */
    public function createdTestFileShouldContainExpectedBootstrapTestCode_whenNamespaceSpecified()
    {
        try {
            $someTestSuiteFileName = 'someTestSuite';
            $expectedFilePathCreated = self::TEST_SANDBOX_DIR."/{$someTestSuiteFileName}.php";

            $this->cleanupTestSandbox();
            $this->runCommandTest(
                $this->createCommandSUT(),
                [
                    '--test-dir' => self::TEST_SANDBOX_DIR,
                    '--namespace' => 'Foo',
                    'file-path' => $someTestSuiteFileName
                ]
            );

            $this->assertEquals(
                file_get_contents(self::EXPECTED_BOILERPLATE_TEST_SUITE_WITH_FOO_NAMESPACE),
                file_get_contents($expectedFilePathCreated),
                'The created test suite file did not have the expected contents'
            );
        } finally {
            $this->cleanupTestSandbox();
        }
    }
}
