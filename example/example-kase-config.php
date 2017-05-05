<?php

/**
 * This file is an example of a Kase config file.  The config must define and return a keyed
 * array used to configure Kase.  An example of such a config file is shown below.
 */

return [

    /*
     * OPTIONAL KEY: bootstrap
     * TYPE: string
     *
     * This file will be included before any Kase test suites run
     */
    'bootstrap' => realpath(__DIR__.'/bootstrap.php'),

    /*
     * OPTIONAL KEY: validator
     * TYPE: any
     *
     * This is the validation object that will be passed into each test case definition and
     * used to make assertions within the test case.  Kase ships with ValidatorFactory/Validator
     * classes which support basic assertion methods and are also customizable by passing a
     * dictionary of the format <custom_assertion_method_name> => <custom_assertion_method_callback>
     * to the constructor of the ValidatorFactory. Each custom validation method only needs to 'throw
     * $this' if validation fails as the custom function will be scope-bound to the validator object
     * that is utilizing it. If you wish to replace this system with a custom system, feel free; you
     * write the test cases, so you decide how the validation system will be used and can write your
     * tests to suite any validator you choose, as long as the validation system throws exceptions
     * on failure.
     */
    'validator' => new Kase\Validation\ValidatorFactory([
        'isNotEvenInteger' =>
            function ($value) {
                if (is_int($value) && ($value % 2) === 0) {
                    return;
                }

                throw $this;
            }
    ])

    /*
     * OPTIONAL KEY: reporter
     * TYPE: Kase\Reporter
     *
     * This is the object which will handle reporting calls from the test runner.  It must
     * be an object which implements the Kase\SuiteReporter interface.  An ad-hoc example
     * of overriding the testing resources with a custom reporter instance can be found below.
     */
    // 'reporter' => new \Acme\KaseTestReporter()

];
