<?php

date_default_timezone_set('UTC');

$try = [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../../../autoload.php',
];

foreach ($try as $path) {
    if (file_exists($path)) {
        $autoLoader = include $path;
        break;
    }
}

$autoLoader->addPsr4('Sabre\\VObject\\', __DIR__ . '/VObject');

if (!defined('SABRE_TEMPDIR')) {
  define('SABRE_TEMPDIR', __DIR__ . '/temp/');
}

if (!file_exists(SABRE_TEMPDIR)) {
  mkdir(SABRE_TEMPDIR);
}
