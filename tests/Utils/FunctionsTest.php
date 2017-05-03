<?php

namespace Kase\Test\Utils;

use PHPUnit\Framework\TestCase;
use Kase\Utils;

class FunctionsTest extends TestCase
{
    /**
     * @test
     */
    public function pathFromKaseProjectRoot_returnsTheAbsolutePathOfAGivenPathRelativeToKaseProjectRoot()
    {
        $expectedPath = realpath(__DIR__.'/..');

        $this->assertEquals($expectedPath, Utils\pathFromKaseProjectRoot('/tests'),
            'failed to generate and return the expected absolute path');
    }
}
