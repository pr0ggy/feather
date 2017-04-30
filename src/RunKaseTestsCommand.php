<?php

namespace Kase;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use RuntimeException;

/**
 * Symfony component console command which defines the main Kase 'run' command usable from the
 * command line
 *
 * @package Kase
 */
class RunKaseTestsCommand extends Command
{
    private $config;

    public function __construct(array $config = null)
    {
        $this->config = $config;
        parent::__construct();
    }

    /**
     * Defines the command name and available options
     */
    protected function configure()
    {
        $this
            ->setName('run')
            ->setDescription('Kase testing framework CLI runner')
            ->addOption(
                'config',
                'c',
                InputOption::VALUE_REQUIRED,
                'The config file used to set up Kase before running tests'
            )
            ->addOption(
                'test-dir',
                'd',
                InputOption::VALUE_REQUIRED,
                'The directory where test suite files are located',
                getcwd() // default value
            )
            ->addOption(
                'file-pattern',
                'f',
                InputOption::VALUE_REQUIRED,
                'The glob pattern matching test suite files',
                '*.test.php' // default value
            );
    }

    /**
     * @param  InputInterface  $input  the input interface to use when executing the command
     * @param  OutputInterface $output the output interface to use when executing the command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // ----- READ CONFIG FROM FILE IF REQUIRED AND SPECIFIED -----------------------------------
        if (isset($this->config) === false) {
            $configPath = $input->getOption('config');
            if ($configPath) {
                if (file_exists($configPath)) {
                    $this->config = require $configPath;
                } else {
                    $output->writeln("Error: Could not find specified Kase config file: {$configPath}\n\n");
                    return;
                }
            }
        }

        // ----- SET UP TESTING RESOURCES ----------------------------------------------------------
        $metricsLog = [];
        $testingResources = [
            'validator'     => (isset($this->config['validator']) ? $this->config['validator'] : new TestValidator()),
            'reporter'      => (isset($this->config['reporter']) ? $this->config['reporter'] : new DefaultKaseCLIReporter($output)),
            'metricsLogger' => function ($metricsToRecord) use (&$metricsLog) {
                $metricsLog[] = $metricsToRecord;
            },
            'console'      => $output // normally shouldn't be used in testing, mostly for unit testing of Kase
        ];

        // ----- SEND RUNNER INITIALIZATION EVENT TO REPORTER --------------------------------------
        $testingResources['reporter']->registerTestRunnerInitialization();

        // ----- RUN TESTS -------------------------------------------------------------------------
        $testSuiteFilePattern = $input->getOption('file-pattern');
        $testSuiteDir = $input->getOption('test-dir');
        if (file_exists($testSuiteDir) === false) {
            $output->writeln("Error: Could not find specified specified test directory: {$testSuiteDir}\n\n");
            return;
        }

        $testSuites = [];
        // FIRST, VERIFY ALL TEST SUITES ARE 'RUNNABLE'
        foreach (\Nette\Utils\Finder::findFiles($testSuiteFilePattern)->from($testSuiteDir) as $absTestSuiteFilePath => $fileInfo) {
            // $absTestSuiteFilePath is a string containing the absolute filename with path
            // $fileInfo is an instance of SplFileInfo
            $suiteRunner = require $absTestSuiteFilePath;
            if (is_callable($suiteRunner) === false) {
                $output->writeln("Error: Suite file does not return a callable test suite: {$absTestSuiteFilePath}\n\n");
                return;
            }
            $testSuites[] = $suiteRunner;
        }

        // ALL TESTS ARE RUNNABLE...RUN 'EM
        foreach ($testSuites as $suiteRunner) {
            $suiteRunner($testingResources);
        }

        // ----- REPORT TESTING RESULTS ------------------------------------------------------------
        $testingResources['reporter']->registerSuiteMetricsSummary($metricsLog);
    }
}
