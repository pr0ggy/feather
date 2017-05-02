<?php

namespace Kase\Utils;

function pathFromKaseProjectRoot($path)
{
    $rootDir = __DIR__.'/../..';
    $path = trim($path, DIRECTORY_SEPARATOR);
    return realpath($rootDir)."/{$path}";
}
