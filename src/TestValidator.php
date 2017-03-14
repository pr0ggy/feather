<?php

namespace Feather;

use BadMethodCallException;

/**
 * Validator instance that is passed into each test definition callback and used to make assertions
 * during execution of the test case
 *
 * @package Feather
 */
class TestValidator
{
    /**
     * dict, validation callback name => validation callback
     *
     * Given to the constructor at creation time to attach custom validator methods
     *
     * @var array
     */
    private $customValidators = []; // dict, validation callback name => validation callback

    public function __construct(array $customValidatorNameToCallbackMap = [])
    {
        $this->customValidators = $customValidatorNameToCallbackMap;
    }

    /**
     * Magic method used to access custom validation methods defined during construction
     *
     * @param  string $methodName the called method name
     * @param  array  $args       arguments passed during the invocation
     */
    public function __call($methodName, $args)
    {
        if (isset($this->customValidators[$methodName]) === false) {
            throw new BadMethodCallException("No validation found with the specified method: {$methodName}");
        }

        $boundValidatorMethod = $this->customValidators[$methodName]->bindTo($this);
        $boundValidatorMethod(...$args);
    }

    /**
     * Simple test readability function to explicitly indicate a test passes (though the method
     * itself does nothing)
     */
    public function pass()
    {
        return;
    }

    /**
     * Explicitly fails the test
     *
     * @param  string $message a message that should explain why the test failed
     * @throws \Feather\ValidationFailureException whenever the method is called
     */
    public function fail($message = 'Test explicitly failed (This message should ideally be more descriptive...)')
    {
        throw new ValidationFailureException($message);
    }

    /**
     * Asserts that a given value is truthy ($val == true)
     *
     * @param  mixed $value
     * @param  string $message a message that should explain what exactly failed if the value is not truthy
     * @throws \Feather\ValidationFailureException if the given value is not truthy
     */
    public function assert($value, $message = 'Failed to assert that the given value was true')
    {
        $this->assertEqual(true, $value, $message);
    }

    /**
     * Asserts that a given value equals an expected value using loose equality ($actual == expected)
     *
     * @param  mixed $expectedValue
     * @param  mixed $actualValue
     * @param  string $message       a message that should explain what exactly failed if the actual value
     *                               does not match the expected value
     * @throws \Feather\ValidationFailureException if the given actual value does not match the given
     *                                            expected value using loose equality
     */
    public function assertEqual($expectedValue, $actualValue, $message = 'Failed to assert that the given values were equal (==)')
    {
        if (is_object($expectedValue) && is_object($actualValue)) {
            if ($this->objectsAreEqual($expectedValue, $actualValue)) {
                return;
            }
        } elseif ($actualValue == $expectedValue) {
            return;
        }

        throw new ValidationFailureException($message, $expectedValue, $actualValue);
    }

    /**
     * Function that is necessary to check for non-referential (ie. value) equality between objects
     * and arrays because of how PHP compares objects and arrays when using the standard == operator
     *
     * @param  object|array  $objA
     * @param  object|array  $objB
     * @param  integer $depth recursive comparison depth limit
     * @return boolean true if the objects share value equality, false otherwise
     */
    protected function objectsAreEqual($objA, $objB, $depth = 30)
    {
        $aProps = (is_object($objA) ? get_object_vars($objA) : $objA);
        $bProps = (is_object($objB) ? get_object_vars($objB) : $objB);

        foreach ($aProps as $k => $v) {
            // check if key exists in b
            if (isset($bProps[$k]) === false) {
                return false;
            }

            if (is_object($v) || is_array($v)) {
                if ($depth === 0) {
                    throw new RuntimeException('Object equality test recursion overflow');
                }

                return $this->objectsAreEqual($v, $bProps[$k], --$depth);
            } elseif ($v !== $bProps[$k]) {
                return false;
            }
        }

        return true;
    }

    /**
     * Asserts that a given value equals an expected value using strict equality ($actual === expected)
     *
     * @param  mixed $expectedValue
     * @param  mixed $actualValue
     * @param  string $message       a message that should explain what exactly failed if the actual value
     *                               does not match the expected value
     * @throws \Feather\ValidationFailureException if the given actual value does not match the given
     *                                            expected value using strict equality
     */
    public function assertSame($expectedValue, $actualValue, $message = 'Failed to assert that the given values were equal (===)')
    {
        if ($actualValue === $expectedValue) {
            return;
        }

        throw new ValidationFailureException($message, $expectedValue, $actualValue);
    }
}
