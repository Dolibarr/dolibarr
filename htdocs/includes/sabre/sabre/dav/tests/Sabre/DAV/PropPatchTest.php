<?php

namespace Sabre\DAV;

class PropPatchTest extends \PHPUnit_Framework_TestCase {

    protected $propPatch;

    function setUp() {

        $this->propPatch = new PropPatch([
            '{DAV:}displayname' => 'foo',
        ]);
        $this->assertEquals(['{DAV:}displayname' => 'foo'], $this->propPatch->getMutations());

    }

    function testHandleSingleSuccess() {

        $hasRan = false;

        $this->propPatch->handle('{DAV:}displayname', function($value) use (&$hasRan) {
            $hasRan = true;
            $this->assertEquals('foo', $value);
            return true;
        });

        $this->assertTrue($this->propPatch->commit());
        $result = $this->propPatch->getResult();
        $this->assertEquals(['{DAV:}displayname' => 200], $result);

        $this->assertTrue($hasRan);

    }

    function testHandleSingleFail() {

        $hasRan = false;

        $this->propPatch->handle('{DAV:}displayname', function($value) use (&$hasRan) {
            $hasRan = true;
            $this->assertEquals('foo', $value);
            return false;
        });

        $this->assertFalse($this->propPatch->commit());
        $result = $this->propPatch->getResult();
        $this->assertEquals(['{DAV:}displayname' => 403], $result);

        $this->assertTrue($hasRan);

    }

    function testHandleSingleCustomResult() {

        $hasRan = false;

        $this->propPatch->handle('{DAV:}displayname', function($value) use (&$hasRan) {
            $hasRan = true;
            $this->assertEquals('foo', $value);
            return 201;
        });

        $this->assertTrue($this->propPatch->commit());
        $result = $this->propPatch->getResult();
        $this->assertEquals(['{DAV:}displayname' => 201], $result);

        $this->assertTrue($hasRan);

    }

    function testHandleSingleDeleteSuccess() {

        $hasRan = false;

        $this->propPatch = new PropPatch(['{DAV:}displayname' => null]);
        $this->propPatch->handle('{DAV:}displayname', function($value) use (&$hasRan) {
            $hasRan = true;
            $this->assertNull($value);
            return true;
        });

        $this->assertTrue($this->propPatch->commit());
        $result = $this->propPatch->getResult();
        $this->assertEquals(['{DAV:}displayname' => 204], $result);

        $this->assertTrue($hasRan);

    }


    function testHandleNothing() {

        $hasRan = false;

        $this->propPatch->handle('{DAV:}foobar', function($value) use (&$hasRan) {
            $hasRan = true;
        });

        $this->assertFalse($hasRan);

    }

    /**
     * @depends testHandleSingleSuccess
     */
    function testHandleRemaining() {

        $hasRan = false;

        $this->propPatch->handleRemaining(function($mutations) use (&$hasRan) {
            $hasRan = true;
            $this->assertEquals(['{DAV:}displayname' => 'foo'], $mutations);
            return true;
        });

        $this->assertTrue($this->propPatch->commit());
        $result = $this->propPatch->getResult();
        $this->assertEquals(['{DAV:}displayname' => 200], $result);

        $this->assertTrue($hasRan);

    }
    function testHandleRemainingNothingToDo() {

        $hasRan = false;

        $this->propPatch->handle('{DAV:}displayname', function() {});
        $this->propPatch->handleRemaining(function($mutations) use (&$hasRan) {
            $hasRan = true;
        });

        $this->assertFalse($hasRan);

    }

    function testSetResultCode() {

        $this->propPatch->setResultCode('{DAV:}displayname', 201);
        $this->assertTrue($this->propPatch->commit());
        $result = $this->propPatch->getResult();
        $this->assertEquals(['{DAV:}displayname' => 201], $result);

    }

    function testSetResultCodeFail() {

        $this->propPatch->setResultCode('{DAV:}displayname', 402);
        $this->assertFalse($this->propPatch->commit());
        $result = $this->propPatch->getResult();
        $this->assertEquals(['{DAV:}displayname' => 402], $result);

    }

    function testSetRemainingResultCode() {

        $this->propPatch->setRemainingResultCode(204);
        $this->assertTrue($this->propPatch->commit());
        $result = $this->propPatch->getResult();
        $this->assertEquals(['{DAV:}displayname' => 204], $result);

    }

    function testCommitNoHandler() {

        $this->assertFalse($this->propPatch->commit());
        $result = $this->propPatch->getResult();
        $this->assertEquals(['{DAV:}displayname' => 403], $result);

    }

    function testHandlerNotCalled() {

        $hasRan = false;

        $this->propPatch->setResultCode('{DAV:}displayname', 402);
        $this->propPatch->handle('{DAV:}displayname', function($value) use (&$hasRan) {
            $hasRan = true;
        });

        $this->propPatch->commit();

        // The handler is not supposed to have ran
        $this->assertFalse($hasRan);

    }

    function testDependencyFail() {

        $propPatch = new PropPatch([
            '{DAV:}a' => 'foo',
            '{DAV:}b' => 'bar',
        ]);

        $calledA = false;
        $calledB = false;

        $propPatch->handle('{DAV:}a', function() use (&$calledA) {
            $calledA = true;
            return false;
        });
        $propPatch->handle('{DAV:}b', function() use (&$calledB) {
            $calledB = true;
            return false;
        });

        $result = $propPatch->commit();
        $this->assertTrue($calledA);
        $this->assertFalse($calledB);

        $this->assertFalse($result);

        $this->assertEquals([
            '{DAV:}a' => 403,
            '{DAV:}b' => 424,
        ], $propPatch->getResult());

    }

    /**
     * @expectedException \UnexpectedValueException
     */
    function testHandleSingleBrokenResult() {

        $propPatch = new PropPatch([
            '{DAV:}a' => 'foo',
        ]);

        $propPatch->handle('{DAV:}a', function() {
            return [];
        });
        $propPatch->commit();

    }

    function testHandleMultiValueSuccess() {

        $propPatch = new PropPatch([
            '{DAV:}a' => 'foo',
            '{DAV:}b' => 'bar',
            '{DAV:}c' => null,
        ]);

        $calledA = false;

        $propPatch->handle(['{DAV:}a', '{DAV:}b', '{DAV:}c'], function($properties) use (&$calledA) {
            $calledA = true;
            $this->assertEquals([
                '{DAV:}a' => 'foo',
                '{DAV:}b' => 'bar',
                '{DAV:}c' => null,
            ], $properties);
            return true;
        });
        $result = $propPatch->commit();
        $this->assertTrue($calledA);
        $this->assertTrue($result);

        $this->assertEquals([
            '{DAV:}a' => 200,
            '{DAV:}b' => 200,
            '{DAV:}c' => 204,
        ], $propPatch->getResult());

    }


    function testHandleMultiValueFail() {

        $propPatch = new PropPatch([
            '{DAV:}a' => 'foo',
            '{DAV:}b' => 'bar',
            '{DAV:}c' => null,
        ]);

        $calledA = false;

        $propPatch->handle(['{DAV:}a', '{DAV:}b', '{DAV:}c'], function($properties) use (&$calledA) {
            $calledA = true;
            $this->assertEquals([
                '{DAV:}a' => 'foo',
                '{DAV:}b' => 'bar',
                '{DAV:}c' => null,
            ], $properties);
            return false;
        });
        $result = $propPatch->commit();
        $this->assertTrue($calledA);
        $this->assertFalse($result);

        $this->assertEquals([
            '{DAV:}a' => 403,
            '{DAV:}b' => 403,
            '{DAV:}c' => 403,
        ], $propPatch->getResult());

    }

    function testHandleMultiValueCustomResult() {

        $propPatch = new PropPatch([
            '{DAV:}a' => 'foo',
            '{DAV:}b' => 'bar',
            '{DAV:}c' => null,
        ]);

        $calledA = false;

        $propPatch->handle(['{DAV:}a', '{DAV:}b', '{DAV:}c'], function($properties) use (&$calledA) {
            $calledA = true;
            $this->assertEquals([
                '{DAV:}a' => 'foo',
                '{DAV:}b' => 'bar',
                '{DAV:}c' => null,
            ], $properties);

            return [
                '{DAV:}a' => 201,
                '{DAV:}b' => 204,
            ];
        });
        $result = $propPatch->commit();
        $this->assertTrue($calledA);
        $this->assertFalse($result);

        $this->assertEquals([
            '{DAV:}a' => 201,
            '{DAV:}b' => 204,
            '{DAV:}c' => 500,
        ], $propPatch->getResult());

    }

    /**
     * @expectedException \UnexpectedValueException
     */
    function testHandleMultiValueBroken() {

        $propPatch = new PropPatch([
            '{DAV:}a' => 'foo',
            '{DAV:}b' => 'bar',
            '{DAV:}c' => null,
        ]);

        $propPatch->handle(['{DAV:}a', '{DAV:}b', '{DAV:}c'], function($properties) {
            return 'hi';
        });
        $propPatch->commit();

    }
}
