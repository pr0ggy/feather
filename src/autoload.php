<?php

$potentialAutoloaders = [
    __DIR__ . '/../../../autoload.php',  // if composer dependency
    __DIR__ . '/../vendor/autoload.php'  // if stand-alone package
];

foreach ($potentialAutoloaders as $autoloader) {
    if (is_file($autoloader)) {
        require $autoloader;
        return;
    }
}

fwrite(
    STDERR,
    'No vendor directory found'.PHP_EOL.
    'are you sure you installed dependencies via `composer install`?'.PHP_EOL
);

die(1);
