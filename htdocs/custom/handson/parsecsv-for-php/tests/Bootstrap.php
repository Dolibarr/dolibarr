<?php

chdir(__DIR__ . '/..');
if (!file_exists('vendor/autoload.php')) {
    `composer dump-autoload`;
}

require __DIR__ . '/../vendor/autoload.php';
