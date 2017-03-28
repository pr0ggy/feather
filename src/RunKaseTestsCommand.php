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
    /**
     * Defines the command name and available options
     */
    protected function configure()
    {
        $this
            ->setName('run')
            ->setDescription('Kase testing framework CLI runner')
            ->addOption(
                'bootstrap',
                'b',
                InputOption::VALUE_REQUIRED,
                'The bootstrap file to be included before Kase runs',
                getcwd().DIRECTORY_SEPARATOR.'kase-bootstrap.php' // default value
            );
    }

    /**
     * @param  InputInterface  $input  the input interface to use when executing the command
     * @param  OutputInterface $output the output interface to use when executing the command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // INCLUDE KASE BOOTSTRAP
        $bootstrapPath = $input->getOption('bootstrap');
        if ($bootstrapPath && file_exists($bootstrapPath)) {
            require $bootstrapPath;
        } else {
            $output->writeln("Error: Could not find specified Kase bootstrap file: {$bootstrapPath}\n\n");
            return;
        }

        // VERIFY REQUIRED USER-DEFINED FUNCTIONS ARE DEFINED IN BOOTSTRAP FILE
        if (function_exists('Kase\testSuitePathProvider') === false) {
            $output->writeln('Error: Required "Kase\testSuitePathProvider" function not found in bootstrap');
            return;
        }

        // SET UP TESTING RESOURCES
        $metricsLog = [];
        $defaultTestingResources = [
            'validator'     => new TestValidator(),
            'reporter'      => new DefaultKaseCLIReporter($output),
            'metricsLogger' => function ($metricsToRecord) use (&$metricsLog) {
                $metricsLog[] = $metricsToRecord;
            }
        ];
        $userDefinedResourceOverrides = (function_exists('Kase\overrideTestingResources') ? overrideTestingResources() : []);

        $testingResources = ($userDefinedResourceOverrides + $defaultTestingResources);

        // SEND RUNNER INITIALIZATION EVENT TO REPORTER
        $testingResources['reporter']->registerTestRunnerInitialization();

        // RUN TESTS
        foreach (testSuitePathProvider() as $testSuiteFilePath) {
            $suiteRunner = require $testSuiteFilePath;
            $suiteRunner($testingResources);
        }

        // REPORT RESULTS
        $testingResources['reporter']->registerSuiteMetricsSummary($metricsLog);
    }
}
