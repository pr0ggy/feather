<?php

namespace Kase\Validation;

/**
 * Default validator object that is passed into each test definition callback and used to make
 * assertions during execution of the test case
 *
 * @package Kase
 */
class ValidatorFactory
{
    /**
     * dictionary, <validation callback name> => <validation callback>
     *
     * @var array
     */
    private $customValidationMethods = []; // dict, validation callback name => validation callback

    public function __construct(array $customValidatorNameToCallbackMap = [])
    {
        $this->customValidationMethods = $customValidatorNameToCallbackMap;
    }

    /**
     * Returns a Validator instance to test against within the test case.
     *
     * @param  string $onFailureMessage  the message the failure should be loaded with
     * @return Validator  a validation failure object that is loaded with the given message,
     *                            as well as the custom validation functions passed to this instance.
     *                            Note that the failure is merely created and returned, not thrown...
     *                            that is handled within the test case itself.
     *
     * @see /kase/example/example-kase-test-suite.test.php
     */
    public function failBecause($onFailureMessage)
    {
        return new Validator($onFailureMessage, $this->customValidationMethods);
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
     * @throws \Kase\Validation\ValidationFailure whenever the method is called
     */
    public function fail($message = 'Test explicitly failed (This message should ideally be more descriptive...)')
    {
        throw new \Exception($message);
    }
}
