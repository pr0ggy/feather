## Kase
### A lightweight testing framework for PHP 5.6 and later

Kase is an attempt to create a testing framework for PHP with 2 general aims:

1. Be as lightweight as possible while remaining practical
2. Expose pluggable modules for customization to fit individual needs without materializing as a sea of abstractions and indirection for source-diving devs to wade through.

Kase takes design inspiration from the [Tape](https://github.com/substack/tape) Javascript testing framework:

- Tests defined as simple callbacks with **no shared state between test cases**
- A basic, no-frills (but extendable) validator object passed to each test case for making assertions
- Test suite and test case descriptions given as explicit strings rather than relying on test case class/function naming conventions

### Installation
	composer require --dev pr0ggy/kase

### Running Kase
	./vendor/bin/kase run -b [bootstrap file, default: ./kase-bootstrap.php]

### <a name="example_bootstrap"></a>Example Kase Bootstrap File
*Kase relies on a user-defined bootstrap file to customize testing resources as needed and include test files.  Check the `example` folder for a couple different example bootstrap files.*

```php
<?php
/**
 * This file is an example of a customized Kase bootstrap file.  The bootstrap must define and
 * return a callable which takes a single argument--the testing resources passed in from the Kase
 * executable.  The testing resources package is a simple dictionary with the following keys available
 * for customization:
 *
 *     validator: This is the validation object that will be passed into each test case definition and
 *                used to make assertions within the test case.  Kase ships with a TestValidator
 *                class which supports basic assertion methods and is also customizable by passing a
 *                dictionary of <custom_assertion_method_name> => <custom_assertion_method_callback>
 *                to the constructor.  If you wish to replace this validation class with a custom class,
 *                feel free; you write the test cases, so you decide how the validator will be used and
 *                can write your tests to suite any validator you choose.  An example of overriding the
 *                testing resources with a custom Kase\TestValidator instance can be found below.
 *
 *      reporter: This is the object which will handle reporting calls from the test runner.  It must
 *                be an object which implements the Kase\SuiteReporter interface.  An ad-hoc example
 *                of overriding the testing resources with a custom reporter instance can be found below.
 *
 * Aside from any desired customization of testing resources, the only requirement of the bootstrap
 * function is to find and include test suite files, executing each returned test suite callback by
 * passing in the testing resources package as an argument.  How to find and loop over the test suite
 * files is up to the end user; the example below uses the Nette\Finder library. To use the Nette\Finder
 * library, see the following link for installation instructions and documentation:
 *     https://github.com/nette/finder
 */

return function ($testingResources) {
    // --------- override validator resource to use custom Kase\TestValidator instance ----------
    $customValidationMethods = [
        'assertEvenInteger' =>
            function ($value, $message = 'Failed to assert that the given value was an even integer') {
                if (is_int($value) && ($value % 2) === 0) {
                    return;
                }

                throw new ValidationFailureException($message);
            }
    ];
    $testingResources['validator'] = new Kase\TestValidator($customValidationMethods);

    // -------------- override reporter resource to use some custom reporter instance --------------
    // $testingResources['reporter'] = new AcmeTestReporter();

    // --------------------------- find and run all desired test suites ----------------------------
    $testSuiteFilePattern = '*.test.php';
    $testSuiteDir = dirname(__FILE__);
    foreach (\Nette\Utils\Finder::findFiles($testSuiteFilePattern)->in($testSuiteDir) as $absTestSuiteFilePath => $fileInfo) {
        // $absTestSuiteFilePath is a string containing the absolute filename with path
        // $fileInfo is an instance of SplFileInfo
        $testSuiteRunner = require $absTestSuiteFilePath;
        $testSuiteRunner($testingResources);
    }
};
```

### Example Kase Test Suite
*Note that a more realistic example can be found in the source in the `examples` folder*

```php
<?php

namespace Acme;

use Kase\runner;
use Kase\test;
use Kase\skip;
use Kase\only;

return runner( 'Demo Test Suite',

    test('Test 1 Description', function ($t) {
    	$t->assertEqual('test', 'te'.'st',
    		'string concat failed to produce "test"');
    }),

    skip('Test 2 Description', function ($t) {
    	// Test is marked as skipped, so no failure will be recorded even though the test fails explicitly
    	$t->fail('This test was failed explicitly');
    }),

    only('Test 3 Description', function ($t) {
    	// This will be the only test that runs in this suite as the use of 'only' isolates it
    	$t->assert(true, 'failed to assert that true is true.......hmm.......');
    })

);
```

## Test Suite / Test Case API

```php
function Kase\runner($suiteDescription, ...$suiteTests)
```
The main test runner generation function which takes a test suite name and 1 or more test cases to execute.  The test suite for the given tests is returned as a callable for execution within the Kase bootstrap file, as in the example above.

---

```php
function Kase\test($description, callable $testDefinition)
```
Creates a test case with a given description and executable definition which will run in sequence.  Test definitions are given as callbacks with a single parameter: a validator object to use for assertions within the test case.

---

```php
function Kase\skip($description, callable $testDefinition)
```
Creates a standard test case which will be skipped when the suite is executed.

---

```php
function Kase\only($description, callable $testDefinition)
```
Creates a test case which will run in isolation (ie. all other test cases will be skipped).  Only one test per suite may be run in isolation at a given time or an error will be thrown.

## <a name="basic_assertions"></a>Assertion API
*The built-in validation class passed to each  test case definition supports the following validation methods out-of-the-box:*

```
TestValidator::pass()
```
The validator object throws exceptions to indicate errors found during execution of a test case definition.  This function doesn't actually do anything--it's a readability function.

---

```
TestValidator::fail($message = 'Test explicitly failed (This message should ideally be more descriptive...)')
```
Explicitly fails the test case with the given message

---

```
TestValidator::assert($value, $message = 'Failed to assert that the given value was true')
```
Asserts that the given value is truthy, or fails the test case with the given message

---

```
TestValidator::assertEqual($expectedValue, $actualValue, $message = 'Failed to assert that the given values were equal (==)')
```
Asserts that the expected value matches the actual value using loose (==) equality, or fails the test case with the given message

---

```
TestValidator::assertSame($expectedValue, $actualValue, $message = 'Failed to assert that the given values were equal (===)')
```
Asserts that the expected value matches the actual value using strict (===) equality, or fails the test case with the given message

## Custom Assertion Methods
As shown in [the example bootstrap file](#example_bootstrap) above, you can swap a new `Kase\TestValidator` instance (or any other class instance, for that matter) into the testing resources package to assert against it within test cases.  The `Kase\TestValidator` constructor accepts a dictionary of custom assertion callbacks in the format:

	<validation method name> => <validation callback>

Note that it is only necessary to create a new `Kase\TestValidator` instance if you wish to use custom assertion methods--a default instance is already created within the Kase context that supports the [basic assertion methods](#basic_assertions) outlined above.

## Testing Kase
	./vendor/bin/phpunit

## License
**GPL-3**
