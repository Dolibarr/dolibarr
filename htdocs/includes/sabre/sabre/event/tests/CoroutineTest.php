<?php

namespace Sabre\Event;

class CoroutineTest extends \PHPUnit_Framework_TestCase {

    /**
     * @expectedException \InvalidArgumentException
     */
    function testNonGenerator() {

        coroutine(function() {});

    }

    function testBasicCoroutine() {

        $start = 0;

        coroutine(function() use (&$start) {

            $start += 1;
            yield;

        });

        $this->assertEquals(1, $start);

    }

    function testFulfilledPromise() {

        $start = 0;
        $promise = new Promise(function($fulfill, $reject) {
            $fulfill(2);
        });

        coroutine(function() use (&$start, $promise) {

            $start += 1;
            $start += (yield $promise);

        });

        Loop\run();
        $this->assertEquals(3, $start);

    }

    function testRejectedPromise() {

        $start = 0;
        $promise = new Promise(function($fulfill, $reject) {
            $reject(2);
        });

        coroutine(function() use (&$start, $promise) {

            $start += 1;
            try {
                $start += (yield $promise);
                // This line is unreachable, but it's our control
                $start += 4;
            } catch (\Exception $e) {
                $start += $e->getMessage();
            }

        });

        Loop\run();
        $this->assertEquals(3, $start);

    }

    function testRejectedPromiseException() {

        $start = 0;
        $promise = new Promise(function($fulfill, $reject) {
            $reject(new \LogicException('2'));
        });

        coroutine(function() use (&$start, $promise) {

            $start += 1;
            try {
                $start += (yield $promise);
                // This line is unreachable, but it's our control
                $start += 4;
            } catch (\LogicException $e) {
                $start += $e->getMessage();
            }

        });

        Loop\run();
        $this->assertEquals(3, $start);

    }

    function testRejectedPromiseArray() {

        $start = 0;
        $promise = new Promise(function($fulfill, $reject) {
            $reject([]);
        });

        coroutine(function() use (&$start, $promise) {

            $start += 1;
            try {
                $start += (yield $promise);
                // This line is unreachable, but it's our control
                $start += 4;
            } catch (\Exception $e) {
                $this->assertTrue(strpos($e->getMessage(), 'Promise was rejected with') === 0);
                $start += 2;
            }

        })->wait();

        $this->assertEquals(3, $start);

    }

    function testFulfilledPromiseAsync() {

        $start = 0;
        $promise = new Promise();
        coroutine(function() use (&$start, $promise) {

            $start += 1;
            $start += (yield $promise);

        });
        Loop\run();

        $this->assertEquals(1, $start);

        $promise->fulfill(2);
        Loop\run();

        $this->assertEquals(3, $start);

    }

    function testRejectedPromiseAsync() {

        $start = 0;
        $promise = new Promise();
        coroutine(function() use (&$start, $promise) {

            $start += 1;
            try {
                $start += (yield $promise);
                // This line is unreachable, but it's our control
                $start += 4;
            } catch (\Exception $e) {
                $start += $e->getMessage();
            }

        });

        $this->assertEquals(1, $start);

        $promise->reject(new \Exception(2));
        Loop\run();

        $this->assertEquals(3, $start);

    }

    function testCoroutineException() {

        $start = 0;
        coroutine(function() use (&$start) {

            $start += 1;
            $start += (yield 2);

            throw new \Exception('4');

        })->error(function($e) use (&$start) {

            $start += $e->getMessage();

        });
        Loop\run();

        $this->assertEquals(7, $start);

    }

    function testDeepException() {

        $start = 0;
        $promise = new Promise();
        coroutine(function() use (&$start, $promise) {

            $start += 1;
            $start += (yield $promise);

        })->error(function($e) use (&$start) {

            $start += $e->getMessage();

        });

        $this->assertEquals(1, $start);

        $promise->reject(new \Exception(2));
        Loop\run();

        $this->assertEquals(3, $start);

    }

    function testResolveToLastYield() {

        $ok = false;
        coroutine(function() {

            yield 1;
            yield 2;
            $hello = 'hi';

        })->then(function($value) use (&$ok) {
            $this->assertEquals(2, $value);
            $ok = true;
        })->error(function($reason) {
            $this->fail($reason);
        });
        Loop\run();

        $this->assertTrue($ok);

    }

    function testResolveToLastYieldPromise() {

        $ok = false;

        $promise = new Promise();

        coroutine(function() use ($promise) {

            yield 'fail';
            yield $promise;
            $hello = 'hi';

        })->then(function($value) use (&$ok) {
            $ok = $value;
            $this->fail($reason);
        });

        $promise->fulfill('omg it worked');
        Loop\run();

        $this->assertEquals('omg it worked', $ok);

    }

}
