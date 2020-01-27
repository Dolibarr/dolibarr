<?php

namespace Sabre\HTTP;

class ResponseDecoratorTest extends \PHPUnit_Framework_TestCase {

    protected $inner;
    protected $outer;

    function setUp() {

        $this->inner = new Response();
        $this->outer = new ResponseDecorator($this->inner);

    }

    function testStatus() {

        $this->outer->setStatus(201);
        $this->assertEquals(201, $this->inner->getStatus());
        $this->assertEquals(201, $this->outer->getStatus());
        $this->assertEquals('Created', $this->inner->getStatusText());
        $this->assertEquals('Created', $this->outer->getStatusText());

    }

    function testToString() {

        $this->inner->setStatus(201);
        $this->inner->setBody('foo');
        $this->inner->setHeader('foo', 'bar');

        $this->assertEquals((string)$this->inner, (string)$this->outer);

    }

}
