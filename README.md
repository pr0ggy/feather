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
    ./vendor/bin/kase create-suite [-d|--test-dir <test directory, default: /PROJECT/ROOT/tests>]
                                   [--namespace <test file namespace>
                                   <test file name, relative to test directory>

### Running Kase
	./vendor/bin/kase run [-c|--config <config file>]
                          [-d|--test-dir <test directory, default: ./>]
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
    	$t->failBecause('string concat failed to produce "test"')
         ->ifNotEqual('test', 'te'.'st');
    }),

    skip('Test 2 Description', function ($t) {
    	// Test is marked as skipped, so no failure will be recorded even though the test fails explicitly
    	$t->fail('This test was failed explicitly');
    }),

    only('Test 3 Description', function ($t) {
    	// This will be the only test that runs in this suite as the use of 'only' isolates it
        $t->failBecause('failed to assert that true is true.......hmm.......')
    	   ->unless(true);
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
*The built-in ValidatorFactory and Validator instances utilized in each  test case definition by default supports the following validation methods out-of-the-box...see the example test suite *

```
ValidatorFactory::pass()
```
A created validator object throws exceptions to indicate errors found during execution of a test case definition.  A passed test is, by definition, one that doesn't result in a thrown exception.  So, this function doesn't actually do anything--it's a readability function.

---

```
ValidatorFactory::fail($message = 'Test explicitly failed (This message should ideally be more descriptive...)')
```
Explicitly fails the test case with the given message by throwing an exception immediately

---

```
ValidatorFactory::failBecause($onFailureMessage)
```
Creates and returns a new Validator object that will fail with the given message if any validation methods called on it fail

---

```
Validator::failUnless($valueThatPassesValidationIfTrue)
```
Asserts that the given value is truthy, or throws the Validator instance itself

---

```
Validator::failIf($valueThatPassesValidationIfFalse)
```
Asserts that the given value is not truthy, or throws the Validator instance itself

---

```
Validator::failIfNotEqual($expectedValue, $actualValue)
```
Asserts that the expected value matches the actual value using loose (==) equality, or throws the Validator instance itself

---

```
Validator::failIfNotSame($expectedValue, $actualValue)
```
Asserts that the expected value matches the actual value using strict (===) equality, or throws the Validator instance itself

## Custom Assertion Methods
The user can swap a custom validation instance into the testing resources package to assert against it within test cases (see the example config file in the `example` folder of the repo for details on how this is accomplished).  The `Kase\Validation\ValidatorFactory` constructor accepts a dictionary of custom assertion callbacks in the format:

	<validation method name> => <validation callback>

The given custom validation callbacks will be scope bound to a created Validator instance returned from the ValidatorFactory before execution, so you may access other validation methods within the custom callback.  Note that it is only necessary to create a new `Kase\Validation\ValidatorFactory` instance if you wish to use custom assertion methods...if not, a default instance will be created within the Kase runner that supports the [basic assertion methods](#basic_assertions) outlined above.

## Testing Kase
	./vendor/bin/phpunit

## License
**GPL-3**
