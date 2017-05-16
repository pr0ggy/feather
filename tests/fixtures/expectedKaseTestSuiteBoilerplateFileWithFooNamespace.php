<?php

namespace Foo;

use function Kase\runner;
use function Kase\test;
use function Kase\skip;
use function Kase\only;
// Kase includes the Kanta assertion library, but feel free to use any exception-based library
use Kanta\Validation as v;

return runner(
    'A_TEST_SUITE_NAME',

    test('A_TEST_DESCRIPTION', function ($t) {
        $t->fail();
    })
);
