<?php

namespace Kase\Test\Fakes;

class FakeValidator
{
    private static $instance;

    public static function instance()
    {
        if (isset(self::$instance) === false) {
            self::$instance = new static();
        }

        return self::$instance;
    }

    private $passInvoked = false;

    public function pass()
    {
        $this->passInvoked = true;
    }

    public function receivedPassInvocation()
    {
        return $this->passInvoked;
    }
}
