<?php

return [

    'testSuitePathProvider' => function () {
        $testSuiteDir = dirname(__FILE__);
        yield "{$testSuiteDir}/tests/test-1.test.php";
        yield "{$testSuiteDir}/tests/test-2.test.php";
    },

    'reporter' => new Kase\Test\Utils\MethodRecorder(),

    'validator' => new Kase\Test\Utils\MethodRecorder()

];
