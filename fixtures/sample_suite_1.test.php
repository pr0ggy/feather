<?php

namespace Feather;

run( 'Test Suite 1',

    test('Test pass validation method', function ($t) {
        $t->pass();
    }),

    test('Test fail validation method', function ($t) {
        $t->fail('UH OH...SOMETHING HAPPENED');
    }),

    skip('Test suite test skip method', function ($t) {
        $t->fail('This shouldt fail, even if failed explicitly');
    }),

    test('Test assert validation method passes', function ($t) {
        $t->assert(true, 'Failed to assert that true is true...');
    }),

    test('Test assert validation method fails', function ($t) {
        $t->assert(false, 'Failed to assert that false is true');
    }),

    test('Test assertEqual validation method passes', function ($t) {
        $t->assertEqual(false, false, 'Failed to assert that false is false');
        $t->assertEqual(1, 1, 'Failed to assert that 1 is 1');
        $t->assertEqual(null, null, 'Failed to assert that null is null');
        $t->assertEqual([], [], 'Failed to assert that [] matches []');
    }),

    test('Test assertEqual validation method fails', function ($t) {
        $obj1 = new \stdClass(); $obj1->foo = 1;
        $obj2 = new \stdClass(); $obj2->foo = 2;
        $t->assertEqual($obj1, $obj2, 'Failed to assert that objects are equal');
    })
);
