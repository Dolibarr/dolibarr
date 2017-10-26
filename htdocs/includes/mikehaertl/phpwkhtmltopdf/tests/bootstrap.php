<?php
// Some travis environments use phpunit > 6
$newClass = '\PHPUnit\Framework\TestCase';
$oldClass = '\PHPUnit_Framework_TestCase';
if (!class_exists($newClass) && class_exists($oldClass)) {
    class_alias($oldClass, $newClass);
}

require __DIR__ . '/../vendor/autoload.php';

