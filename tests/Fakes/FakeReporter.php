<?php

namespace Kase\Test\Fakes;

class FakeReporter
{
    private static $instance;

    public static function instance()
    {
        if (isset(self::$instance) === false) {
            self::$instance = new static();
        }

        return self::$instance;
    }

    private $initRegistered = false;

    public function registerTestRunnerInitialization()
    {
        $this->initRegistered = true;
    }

    public function receivedInitFromRunner()
    {
        return $this->initRegistered;
    }

    public function registerSuiteMetricsSummary()
    {
        // no-op
    }
}
