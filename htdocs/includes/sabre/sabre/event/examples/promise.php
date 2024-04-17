#!/usr/bin/env php
<?php declare (strict_types=1);

use Sabre\Event\Loop;
use Sabre\Event\Promise;
use function Sabre\Event\coroutine;

require __DIR__ . '/../vendor/autoload.php';

/**
 * This example shows demonstrates the Promise api.
 */


/* Creating a new promise */
$promise = new Promise();

/* After 2 seconds we fulfill it */
Loop\setTimeout(function() use ($promise) {

    echo "Step 1\n";
    $promise->fulfill("hello");

}, 2);


/* Callback chain */

$result = $promise
    ->then(function($value) {

        echo "Step 2\n";
        // Immediately returning a new value.
        return $value . " world";

    })
    ->then(function($value) {

        echo "Step 3\n";
        // This 'then' returns a new promise which we resolve later.
        $promise = new Promise();

        // Resolving after 2 seconds
        Loop\setTimeout(function() use ($promise, $value) {

            $promise->fulfill($value . ", how are ya?");

        }, 2);

        return $promise;
    })
    ->then(function($value) {

        echo "Step 4\n";
        // This is the final event handler.
        return $value . " you rock!";

    })
    // Making all async calls synchronous by waiting for the final result.
    ->wait();

echo $result, "\n";

/* Now an identical example, this time with coroutines. */

$result = coroutine(function() {

    $promise = new Promise();

    /* After 2 seconds we fulfill it */
    Loop\setTimeout(function() use ($promise) {

        echo "Step 1\n";
        $promise->fulfill("hello");

    }, 2);

    $value = (yield $promise);

    echo "Step 2\n";
    $value .= ' world';

    echo "Step 3\n";
    $promise = new Promise();
    Loop\setTimeout(function() use ($promise, $value) {

        $promise->fulfill($value . ", how are ya?");

    }, 2);

    $value = (yield $promise);

    echo "Step 4\n";

    // This is the final event handler.
    yield $value . " you rock!";

})->wait();

echo $result, "\n";
