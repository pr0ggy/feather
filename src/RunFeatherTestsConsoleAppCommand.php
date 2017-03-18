<?php

namespace Feather;

use Symfony\Component\Console\Command\Command;
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

        // ---------------------- print Feather version and include bootstrap ----------------------
        $bootstrapPath = $input->getOption('bootstrap');
        if ($bootstrapPath && file_exists($bootstrapPath)) {
            require $bootstrapPath;
        } else {
            $output->writeln("Error: Could not find specified Feather bootstrap file: {$bootstrapPath}\n\n");
            return;
        }


        if (count($feather->executedSuiteMetrics) === 0) {
            // ------------------------ print message if no files are found ------------------------
            $output->writeln('No test files found'.PHP_EOL);
        } else {
            // --------------------------- print test execution summary ----------------------------
            $feather->suiteReporter->registerSuiteMetricsSummary($feather->executedSuiteMetrics);
        }
    }
}
