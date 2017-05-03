<?php

namespace Kase\Utils;

/**
 * @param  string $path  the relative path from the root
 * @return string  the absolute path representing the given relative path
 */
function pathFromKaseProjectRoot($path)
{
    $rootDir = __DIR__.'/../..';
    $path = trim($path, DIRECTORY_SEPARATOR);
    return realpath($rootDir)."/{$path}";
}
