<?php

namespace Kase\Test\TestUtils;

class MethodRecorderContainer
{
    private static $instances = [];

    public static function newInstance()
    {
        $newRecorder = new MethodRecorder;
        self::$instances[] = $newRecorder;
        return $newRecorder;
    }

    public static function getLastNRecorders($n)
    {
        return array_slice(self::$instances, (-1*$n));
    }
}
