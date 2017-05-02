<?php

use Kase\Test\TestUtils\MethodRecorderContainer;

return [
    'reporter' => MethodRecorderContainer::newInstance(),
    'validator' => MethodRecorderContainer::newInstance()
];
