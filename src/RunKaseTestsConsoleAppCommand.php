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
class RunKaseTestsConsoleAppCommand extends Command
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
        // ----------------------- initialize the Kase testing resources ------------------------
        $metricsLog = [];
        $testingResources = [
            'validator'     => new TestValidator(),
            'reporter'      => new DefaultKaseCLIReporter($output),
            'metricsLogger' => function ($metricsToRecord) use (&$metricsLog) {
                $metricsLog[] = $metricsToRecord;
            }
        ];

        // ------------------------------------- print Kase version -------------------------------------
        $output->writeln(PHP_EOL.'Kase '.VERSION.PHP_EOL);

        // --------------------------------- run Kase bootstrap ---------------------------------
        $bootstrapPath = $input->getOption('bootstrap');
        if ($bootstrapPath && file_exists($bootstrapPath)) {
            $bootstrapper = require $bootstrapPath;
            $bootstrapper($testingResources);
        } else {
            $output->writeln("Error: Could not find specified Kase bootstrap file: {$bootstrapPath}\n\n");
            return;
        }

        // ------------------------------------ report results -------------------------------------
        if (count($metricsLog) === 0) {
            $output->writeln('No test files found'.PHP_EOL);
        } else {
            $testingResources['reporter']->registerSuiteMetricsSummary($metricsLog);
        }
    }
}
