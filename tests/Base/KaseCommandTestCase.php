<?php

namespace Kase\Test\Base;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Kase\Utils;

abstract class KaseCommandTestCase extends TestCase
{
    protected function runCommandTest(Command $sut, array $executionOptions = [])
    {
        $application = new Application();
        $application->add($sut);

        $tester = new CommandTester($sut);
        $tester->execute($executionOptions + ['command'  => $sut->getName()]);

        return $tester;
    }

    abstract protected function createCommandSUT(...$creationArgs);
}
