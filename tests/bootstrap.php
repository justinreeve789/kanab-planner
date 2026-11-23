<?php

/*
 * PHPUnit bootstrap.
 *
 * /storage is git-ignored in this repo, so a fresh clone has no storage/framework/* directories and Laravel
 * fails to boot (views/cache/sessions can't be written). Create the minimum layout before autoloading.
 */

$base = dirname(__DIR__);

foreach ([
    'storage/app/public',
    'storage/framework/cache/data',
    'storage/framework/sessions',
    'storage/framework/testing',
    'storage/framework/views',
    'storage/logs',
    'storage/temp',
    'bootstrap/cache',
] as $dir) {
    if (!is_dir($base . '/' . $dir)) {
        mkdir($base . '/' . $dir, 0775, true);
    }
}

require $base . '/vendor/autoload.php';
