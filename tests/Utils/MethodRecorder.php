<?php

namespace Kase\Test\Utils;

class MethodRecorder
{
    private $methodCallCounts = [];

    public function __call($methodName, $args)
    {
        if (isset($this->methodCallCounts[$methodName]) === false) {
            $this->methodCallCounts[$methodName] = 0;
        }

        ++$this->methodCallCounts[$methodName];
    }

    public function callCountForMethod($methodName)
    {
        return isset($this->methodCallCounts[$methodName])
            ? $this->methodCallCounts[$methodName]
            : 0;
    }
}
