## Kase
### A lightweight testing framework for PHP 5.6 and later

Kase is an attempt to create a testing framework for PHP with 2 general aims:

1. Be as lightweight as possible while remaining practical
2. Expose pluggable modules for customization to fit individual needs without materializing as a sea of abstractions and indirection for source-diving devs to wade through.

Kase takes design inspiration from the [Tape](https://github.com/substack/tape) Javascript testing framework:

- Tests defined as simple callbacks with **no shared state between test cases**
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
// Kase includes the Kanta assertion library, but feel free to use any exception-based library
use Kanta\Validation as v;

return runner( 'Demo Test Suite',

    test('Test 1 Description', function () {
         v\assert([
             'that' => 'te'.'st',
             'satisfies' => v\is('test'),
             'orFailBecause' => 'string concat failed to produce "test"'
         ]);
    }),

    skip('Test 2 Description', function () {
        // Test is marked as skipped, so no failure will be recorded even though the test fails explicitly
  	    v\fail('This test was failed explicitly');
    }),

    only('Test 3 Description', function () {
    	  // This will be the only test that runs in this suite as the use of 'only' isolates it
        v\assert([
           'that' => true,
           'satisfies' => v\is(true),
           'orFailBecause' => "true isn't true.......hmm......."
        ]);
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

## Assertions
Kase includes the [Kanta](https://github.com/pr0ggy/kanta) assertion library, but feel free to use
any exception-based validation/assertion library you like.

## Testing Kase
	./vendor/bin/phpunit

## License
**MIT**
