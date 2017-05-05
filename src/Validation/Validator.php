<?php

namespace Kase\Validation;

/**
 * Exception subclass instantiated by a \Kase\Validation\ValidatorFactory instance and tested for
 * within a test case definition using the methods contained herein, or the custom methods passed
 * in from the ValidatorFactory instance.
 *
 * @package Kase
 */
class Validator extends \Exception
{
    /**
     * flag denoting if this particular failure case was actually tested against, or merely created
     *
     * @var boolean
     * @see __destruct method below
     */
    private $hasBeenAccountedFor = false;

    /**
     * custom validation callback dictionary in the format <callbackName> => <callback>
     * @var array
     */
    private $customValidators = [];

    /**
     * @param string $message          the message explaining exactly why the failure was thrown
     * @param array  $customValidators custom validation callback dictionary
     */
    public function __construct($message = 'Validation Failure!', $customValidators = [])
    {
        parent::__construct($message);
        $this->customValidators = $customValidators;
    }

    /**
     * Magic method used to access custom validation methods defined during construction, as well as
     * tweak
     *
     * @param  string $methodName the called method name
     * @param  array  $args       arguments passed during the invocation
     * @throws \BadMethodCallException if no methods can be found matching the given method name
     */
    public function __call($methodName, $args)
    {
        $methodNamewithThrow = 'fail'.ucfirst($methodName);

        switch (true) {
            case method_exists($this, $methodNamewithThrow):
                $callableMethod = [$this, $methodNamewithThrow];
                break;

            case isset($this->customValidators[$methodName]):
                $callableMethod = $this->customValidators[$methodName]->bindTo($this);
                break;

            case isset($this->customValidators[$methodNamewithThrow]):
                $callableMethod = $this->customValidators[$methodNamewithThrow]->bindTo($this);
                break;

            default:
                throw new \BadMethodCallException("Validation method not found: {$methodName}");
        }

        $this->markAccountedFor();
        return $callableMethod(...$args);
    }

    /**
     * Validator objects are created using the ValidatorFactory::failBecause(...) method, which will
     * create a Validator instance that knows the reason why it failed if it failed via a message,
     * but it still needs to actually be validated using one of the methods below.  However, if a
     * method below is not called, we need a way to throw an error to the user indicating that a
     * validator was created for a specific failure condition, but never actually tested for.  The
     * $hasBeenAccountedFor flag handles this for us.  A newly-created Validator instance will have
     * a $hasBeenAccountedFor value of false.  Each method below (as well as the __call() magic
     * method) must call this method to mark the validator as accounted for, or an error will be
     * thrown from the destructor of the Validator instance.
     */
    public function markAccountedFor()
    {
        $this->hasBeenAccountedFor = true;
    }

    /**
     * Returns whether or not this Validator instance has actually been tested for a failure condition
     *
     * @return boolean
     * @see markAccountedFor() method
     * @see __destruct() method
     */
    public function hasBeenAccountedFor()
    {
        return $this->hasBeenAccountedFor;
    }

    /**
     * Asserts that a given value is true using loose (==) equality
     *
     * @param  mixed $value
     * @throws \Kase\Validation\Validator if the given value is not truthy
     */
    public function failUnless($valueThatPassesValidationIfTrue)
    {
        $this->failIfNotEqual(true, $valueThatPassesValidationIfTrue);
    }

    /**
     * Asserts that a given value is false using loose (==) equality
     *
     * @param  mixed $valueThatPassesValidationIfFalse
     * @throws \Kase\Validation\Validator if the given value is truthy
     */
    public function failIf($valueThatPassesValidationIfFalse)
    {
        $this->failIfNotEqual(false, $valueThatPassesValidationIfFalse);
    }

    /**
     * @param  mixed $expectedValue
     * @param  mixed $actualValue
     * @throws \Kase\Validation\ValidationFailure if the given actual value does not match the given
     *                                            expected value using loose equality
     */
    public function failIfNotEqual($expectedValue, $actualValue)
    {
        $this->markAccountedFor();
        if (is_object($expectedValue) && is_object($actualValue)) {
            if ($this->objectsAreEqual($expectedValue, $actualValue)) {
                return;
            }
        } elseif ($actualValue == $expectedValue) {
            return;
        }

        $this->data = [
            'expectedValue' => $expectedValue,
            'actualValue' => $actualValue
        ];

        throw $this;
    }

    /**
     * Function that is necessary to check for non-referential (ie. value) equality between objects
     * and arrays because of how PHP compares objects and arrays when using the standard == operator
     *
     * @param  object|array  $objA
     * @param  object|array  $objB
     * @param  integer $depth recursive comparison depth limit
     * @return boolean true if the objects share value equality, false otherwise
     * @throws RuntimeException if the given recursive depth is exceeded
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
                    throw new \RuntimeException('Object equality test recursion overflow');
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
     * @throws \Kase\Validation\ValidationFailure if the given actual value does not match the given
     *                                            expected value using strict equality
     */
    public function failIfNotSame($expectedValue, $actualValue)
    {
        $this->markAccountedFor();
        if ($actualValue === $expectedValue) {
            return;
        }

        $this->data = [
            'expectedValue' => $expectedValue,
            'actualValue' => $actualValue
        ];

        throw $this;
    }

    /**
     * Hook into the destructor as a last-second check to ensure this ValidationFailure instance is
     * actually tested for using one of the methods above.  If not, set the message of this instance
     * to one which explains that the instance was never tested for and throw.
     */
    public function __destruct()
    {
        if ($this->hasBeenAccountedFor()) {
            return;
        }

        $this->message = self::getUnaccountedForErrorMessageForValidator($this);
        throw $this;
    }

    public static function getUnaccountedForErrorMessageForValidator(Validator $validator)
    {
        return '
Error: Validation failure condition was created but never tested for!'.PHP_EOL.'
Condition: '.$validator->message.PHP_EOL.PHP_EOL.$validator->getTraceAsString();
    }
}
