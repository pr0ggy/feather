<?php

namespace Kase\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Exceptions\IO\Filesystem;

/**
 * Symfony component console command which will create a new Kase test suite file at a path relative
 * to a root test directory
 *
 * @package Kase
 */
class CreateKaseTestSuiteFileCommand extends Command
{
    private $activeProjectRoot;

    public function __construct($activeProjectRoot)
    {
        $this->activeProjectRoot = $activeProjectRoot;
        parent::__construct();
    }

    /**
     * Defines the command name and available options
     */
    protected function configure()
    {
        $this
            ->setName('create-suite')
            ->setDescription('Creates a new test suite file.')
            ->setHelp('This command allows you to create a new boilerplate test suite file at the given path')
            ->addOption(
                'test-dir',
                '-d',
                InputOption::VALUE_REQUIRED,
                'The directory where tests are located',
                "{$this->activeProjectRoot}/tests"
            )
            ->addOption(
                'namespace',
                null,
                InputOption::VALUE_REQUIRED,
                'The namespace in which to define the test file'
            )
            ->addArgument(
                'file-path',
                InputArgument::REQUIRED,
                'The file to create, relative to the defined test directory.'
            )
        ;
    }

    /**
     * @param  InputInterface  $input  the input interface to use when executing the command
     * @param  OutputInterface $output the output interface to use when executing the command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $testDirectory = $input->getOption('test-dir');
        if (is_dir($testDirectory) === false) {
            throw new Filesystem\DirectoryNotFoundException("Test directory not found: {$testDirectory}");
        }

        $fileToCreate = $testDirectory.'/'.$input->getArgument('file-path').'.php';
        if (file_exists($fileToCreate)) {
            throw new Filesystem\FileAlreadyExistsException('File already exists: '.realpath($fileToCreate));
        }

        $testNamespace = $input->getOption('namespace');
        $namespaceLines = ($testNamespace ? "\nnamespace {$testNamespace};\n" : '');

        $boilerplateTestContents = <<<EOD
<?php
{$namespaceLines}
use function Kase\\runner;
use function Kase\\test;
use function Kase\\skip;
use function Kase\\only;
// Kase includes the Kanta assertion library, but feel free to use any exception-based library
use Kanta\Validation as v;

return runner(
    'A_TEST_SUITE_NAME',

    test('A_TEST_DESCRIPTION', function (\$t) {
        \$t->fail();
    })
);

EOD;

        file_put_contents($fileToCreate, $boilerplateTestContents);
        $output->writeln('Test file created successfully: '.realpath($fileToCreate));
    }
}
