<?php

use function Kase\runner;
use function Kase\test;
use function Kase\skip;
use function Kase\only;

return runner(
    'A_TEST_SUITE_NAME',

    test('A_TEST_DESCRIPTION', function ($t) {
        $t->fail();
    })
);
