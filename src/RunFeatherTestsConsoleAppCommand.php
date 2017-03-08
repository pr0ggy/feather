<?php

namespace Feather;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use RuntimeException;

/**
 * Symfony component console command which defines the main Feather 'run' command usable from the
 * command line
 *
 * @package Feather
 */
class RunFeatherTestsConsoleAppCommand extends Command
{
    /**
     * Defines the command name and available options
     */
    protected function configure()
    {
        $this
            ->setName('run')
            ->setDescription('Feather testing framework CLI runner')
            ->addOption(
                'bootstrap',
                'b',
                InputOption::VALUE_REQUIRED,
                'The bootstrap file to be included before Feather runs',
                getcwd().DIRECTORY_SEPARATOR.'feather-bootstrap.php' // default value
            )
            ->addOption(
                'test-folder',
                'f',
                InputOption::VALUE_REQUIRED,
                'The folder to traverse recursively for test files',
                getcwd().DIRECTORY_SEPARATOR.'test' // default value
            )
            ->addOption(
                'test-file-pattern',
                'p',
                InputOption::VALUE_REQUIRED,
                'The regex pattern used to search for test files',
                '/.+\.test\.php/i' // default value
            );
    }

    /**
     * @param  InputInterface  $input  the input interface to use when executing the command
     * @param  OutputInterface $output the output interface to use when executing the command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // ------------ initialize the Feather context (can be customized in bootstrap) ------------
        try {
            Context::createAndRegisterSingletonWithConstructionArgs(
                new TestValidator(),
                new DefaultFeatherCLIReporter($output)
            );
        } catch (RuntimeException $singletonAlreadyRegistered) {
            // do nothing
        }

        $feather = Context::getInstance();

        // ----------------------------------- include bootstrap -----------------------------------
        $bootstrapPath = $input->getOption('bootstrap');
        if ($bootstrapPath && file_exists($bootstrapPath)) {
            require $bootstrapPath;
        }

        // -------------------------- print Feather version and run tests --------------------------
        $output->writeln(PHP_EOL.'Feather '.FEATHER_VERSION.PHP_EOL);
        $processedTestFiles = $this->processTestFiles(
            $input->getOption('test-folder'),
            $input->getOption('test-file-pattern'),
            $output
        );


        if (count($processedTestFiles) === 0) {
            // ------------------------ print message if no files are found ------------------------
            $output->writeln(
                "No test files found matching the pattern: \"{$input->getOption('test-file-pattern')}\" in folder \"{$input->getOption('test-folder')}\"".
                PHP_EOL
            );
        } else {
            // --------------------------- print test execution summary ----------------------------
            $feather->suiteReporter->registerSuiteMetricsSummary($feather->executedSuiteMetrics);
        }
    }

    /**
     * Does a recursive search within the given folder name for all files matching the given pattern.
     * The OutputInterface parameter is pulled into the function scope for availability within the
     * scope of the test file being included.  This really shouldn't be needed for standard use, only
     * as a convenience for testing Feather itself.
     *
     * @param  string          $folder      the root folder from which to do a recursive search
     * @param  regex           $filePattern the regex pattern to match files against for inclusion
     * @param  OutputInterface $output      the output interface that will be made available within
     *                                      the scope of each included test file (used only for testing)
     * @return array an array of file names that were processed
     */
    protected function processTestFiles($folder, $filePattern, OutputInterface $output)
    {
        $testFilesProcessed = [];

        foreach ($this->rsearch($folder, $filePattern) as $filePathList) {
            foreach ($filePathList as $path) {
                if (is_dir($path)) {
                    continue;
                }

                require $path;
                $testFilesProcessed[] = $path;
            }
        }

        return $testFilesProcessed;
    }

    /**
     * Generator method which executes a recursive search starting at the given root folder for all
     * files contained within that match a given file pattern. Yields a file list on each call.
     *
     * @param  string $folder      the root folder from which to do a recursive search
     * @param  regex  $filePattern the regex pattern to match files against for inclusion
     */
    protected function rsearch($folder, $filePattern)
    {
        $fileSearch = new \RegexIterator(
            new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($folder)
            ),
            $filePattern,
            \RegexIterator::GET_MATCH
        );

        foreach ($fileSearch as $fileList) {
            yield $fileList;
        }
    }
}
