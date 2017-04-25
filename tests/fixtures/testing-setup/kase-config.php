<?php

return [

    'testSuitePathProvider' => function () {
        $testSuiteFilePattern = '*.test.php';
        $testSuiteDir = dirname(__FILE__);

        yield "{$testSuiteDir}/tests/test-1.test.php";
        yield "{$testSuiteDir}/tests/test-2.test.php";
    },

    'reporter' => Kase\Test\Fakes\FakeReporter::instance(),

    'validator' => Kase\Test\Fakes\FakeValidator::instance()

];
