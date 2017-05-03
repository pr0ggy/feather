<?php

namespace Kase\Test\Validation;

use PHPUnit\Framework\TestCase;
use Kase\Validation\ValidationFailureException;

class ValidationFailureExceptionTest extends TestCase
{
    const SOME_FAILURE_MESSAGE = 'some validation failure message';
    const SOME_EXPECTED_VALUE = 'foo';
    const SOME_ACTUAL_VALUE = 'bar';

    /**
     * @test
     */
    public function getMessage_exposesValidationMessage()
    {
        $sut = new ValidationFailureException(self::SOME_FAILURE_MESSAGE, self::SOME_EXPECTED_VALUE, self::SOME_ACTUAL_VALUE);
        $this->assertEquals(self::SOME_FAILURE_MESSAGE, $sut->getMessage(),
            'validation exception did not expose validation message as expected');
    }

    /**
     * @test
     */
    public function getExpectedValue_exposesExpectedValue()
    {
        $sut = new ValidationFailureException(self::SOME_FAILURE_MESSAGE, self::SOME_EXPECTED_VALUE, self::SOME_ACTUAL_VALUE);
        $this->assertEquals(self::SOME_EXPECTED_VALUE, $sut->getExpectedValue(),
            'validation exception did not expose the expected value as expected');
    }

    /**
     * @test
     */
    public function getActualValue_exposesActualValue()
    {
        $sut = new ValidationFailureException(self::SOME_FAILURE_MESSAGE, self::SOME_EXPECTED_VALUE, self::SOME_ACTUAL_VALUE);
        $this->assertEquals(self::SOME_ACTUAL_VALUE, $sut->getActualValue(),
            'validation exception did not expose the actual value as expected');
    }
}
