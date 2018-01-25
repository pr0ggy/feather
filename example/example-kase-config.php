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
     * OPTIONAL KEY: reporter
     * TYPE: Kase\Reporter
     *
     * This is the object which will handle reporting calls from the test runner.  It must
     * be an object which implements the Kase\SuiteReporter interface.  An ad-hoc example
     * of overriding the testing resources with a custom reporter instance can be found below.
     */
    // 'reporter' => new \Acme\KaseTestReporter()

];
