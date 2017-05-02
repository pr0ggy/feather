<?php

namespace Kase\Validation;

/**
 * Exception sublcass thrown by a \Kase\TestValidator instance in the event that validation fails
 *
 * @package Kase
 */
class ValidationFailureException extends \Exception
{
    /**
     * @var mixed
     */
    private $expectedValue;

    /**
     * @var mixed
     */
    private $actualValue;

    public function __construct($message, $expectedValue = null, $actualValue = null, $code = 0, \Exception $previous = null)
    {
        $this->expectedValue = $expectedValue;
        $this->actualValue = $actualValue;
        parent::__construct($message, $code, $previous);
    }

    public function getExpectedValue()
    {
        return $this->expectedValue;
    }

    public function getActualValue()
    {
        return $this->actualValue;
    }
}
