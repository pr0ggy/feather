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

### Creating a New Kase Test Suite Boilerplate File
    ./vendor/bin/kase create-suite [-d|--test-dir <test directory, default: ./tests>]
                                   [--namespace <test file namespace>]
                                   <test file name, relative to test directory>

### Running Kase
	./vendor/bin/kase run [-c|--config <config file>]
                          [-d|--test-dir <test directory, default: ./tests>]
                          [-f|--file-pattern <test file pattern, default: '*.test.php'>]

### Example Config File
*Kase can utilize a user-defined config file to customize testing resources if desired.  An example config file can be found in the `example` folder with explanations of options.*

### Example Kase Test Suite
*Note that a more realistic example can be found in the `examples` folder*

```php
<?php

namespace Acme;

use function Kase\runner;
use function Kase\test;
use function Kase\skip;
use function Kase\only;

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
The main test runner generation function which takes a test suite name and 1 or more test cases to execute.  The test suite for the given tests is returned as a callable for execution, as in the example above.

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
The user can swap a new `Kase\TestValidator` instance (or any other class instance, for that matter) into the testing resources package to assert against it within test cases (see the example config file in the `example` folder of the repo for details on how this is accomplished).  The `Kase\TestValidator` constructor accepts a dictionary of custom assertion callbacks in the format:

	<validation method name> => <validation callback>

The given custom validation callbacks will be scope bound to the validator instance itself before execution, so you may access other validation methods within the custom callback using standard `$this->assert(...)` calls.  Note that it is only necessary to create a new `Kase\TestValidator` instance if you wish to use custom assertion methods...if not, a default instance will be created within the Kase runner that supports the [basic assertion methods](#basic_assertions) outlined above.

## Testing Kase
	./vendor/bin/phpunit

## License
**GPL-3**
