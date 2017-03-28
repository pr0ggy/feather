<?php

namespace Kase;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use function Nark\createSpyInstanceOf;

class CreateKaseTestSuiteFileCommandTest extends TestCase
{
    const TEST_CREATION_DIR = __DIR__.'/fixtures';
    const EXISTING_TEST_NAME = 'fakeExistingTestFile';
    const CREATED_TEST_FILE_NAME = 'newTestFile';
    const CREATED_TEST_FILE_PATH = __DIR__.'/fixtures/newTestFile.php';
    const EXPECTED_BOILERPLATE_TEST_SUITE_WITHOUT_NAMESPACE_FILE_PATH = __DIR__.'/fixtures/expectedKaseTestSuiteBoilerplateFileWithoutNamespace.php';
    const EXPECTED_BOILERPLATE_TEST_SUITE_WITH_FOO_NAMESPACE_FILE_PATH = __DIR__.'/fixtures/expectedKaseTestSuiteBoilerplateFileWithFooNamespace.php';

    /**
     * @test
     * @expectedException Kase\NotFoundException
     */
    public function throwsNotFoundException_ifGivenTestDirectoryNotFound()
    {
        list($command, $commandTester) = $this->createCommandAndTester();
        $commandTester->execute([
            'command'  => $command->getName(),
            '--test-dir' => 'this-dir-doesnt-exist',
            'file-path' => 'someNewTest'
        ]);
    }

    private function createCommandAndTester()
    {
        $application = new Application();
        $application->add(new CreateKaseTestSuiteFileCommand());

        $command = $application->find('create-suite');
        return [$command, new CommandTester($command)];
    }

    /**
     * @test
     * @expectedException Kase\CollisionException
     */
    public function throwsCollisionException_ifGivenTestFilePathAlreadyExists()
    {
        list($command, $commandTester) = $this->createCommandAndTester();
        $commandTester->execute([
            'command'  => $command->getName(),
            '--test-dir' => self::TEST_CREATION_DIR,
            'file-path' => self::EXISTING_TEST_NAME
        ]);
    }

    /**
     * @test
     */
    public function createsANewTestFileWithGivenNameInGivenDirectory()
    {
        list($command, $commandTester) = $this->createCommandAndTester();
        $commandTester->execute([
            'command'  => $command->getName(),
            '--test-dir' => self::TEST_CREATION_DIR,
            'file-path' => self::CREATED_TEST_FILE_NAME
        ]);

        if (file_exists(self::CREATED_TEST_FILE_PATH)) {
            unlink(self::CREATED_TEST_FILE_PATH);
        } else {
            $this->fail('Failed to create test suite file');
        }
    }

    /**
     * @test
     */
    public function createdTestFileShouldContainExpectedBootstrapTestCode_whenNoNamespaceSpecified()
    {
        list($command, $commandTester) = $this->createCommandAndTester();
        $commandTester->execute([
            'command'  => $command->getName(),
            '--test-dir' => self::TEST_CREATION_DIR,
            'file-path' => self::CREATED_TEST_FILE_NAME
        ]);

        $createdFileContentsAsExpected = (file_get_contents(self::CREATED_TEST_FILE_PATH) === file_get_contents(self::EXPECTED_BOILERPLATE_TEST_SUITE_WITHOUT_NAMESPACE_FILE_PATH));
        if ($createdFileContentsAsExpected) {
            unlink(self::CREATED_TEST_FILE_PATH);
        } else {
            $this->fail("The created test suite file did not have the expected contents\nCompare ".self::EXPECTED_BOILERPLATE_TEST_SUITE_WITHOUT_NAMESPACE_FILE_PATH.' to '.self::CREATED_TEST_FILE_PATH);
        }
    }

    /**
     * @test
     */
    public function createdTestFileShouldContainExpectedBootstrapTestCode_whenNamespaceSpecified()
    {
        list($command, $commandTester) = $this->createCommandAndTester();
        $commandTester->execute([
            'command'  => $command->getName(),
            '--test-dir' => self::TEST_CREATION_DIR,
            '--namespace' => 'Foo',
            'file-path' => self::CREATED_TEST_FILE_NAME
        ]);

        $createdFileContentsAsExpected = (file_get_contents(self::CREATED_TEST_FILE_PATH) === file_get_contents(self::EXPECTED_BOILERPLATE_TEST_SUITE_WITH_FOO_NAMESPACE_FILE_PATH));
        if ($createdFileContentsAsExpected) {
            unlink(self::CREATED_TEST_FILE_PATH);
        } else {
            $this->fail("The created test suite file did not have the expected contents\nCompare ".self::EXPECTED_BOILERPLATE_TEST_SUITE_WITH_FOO_NAMESPACE_FILE_PATH.' to '.self::CREATED_TEST_FILE_PATH);
        }
    }
}
