<?php

namespace Stripe;

class DisputeTest extends TestCase
{
    const TEST_RESOURCE_ID = 'dp_123';

    public function testIsListable()
    {
        $this->expectsRequest(
            'get',
            '/v1/disputes'
        );
        $resources = Dispute::all();
        $this->assertTrue(is_array($resources->data));
        $this->assertInstanceOf("Stripe\\Dispute", $resources->data[0]);
    }

    public function testIsRetrievable()
    {
        $this->expectsRequest(
            'get',
            '/v1/disputes/' . self::TEST_RESOURCE_ID
        );
        $resource = Dispute::retrieve(self::TEST_RESOURCE_ID);
        $this->assertInstanceOf("Stripe\\Dispute", $resource);
    }

    public function testIsSaveable()
    {
        $resource = Dispute::retrieve(self::TEST_RESOURCE_ID);
        $resource->metadata["key"] = "value";
        $this->expectsRequest(
            'post',
            '/v1/disputes/' . $resource->id
        );
        $resource->save();
        $this->assertInstanceOf("Stripe\\Dispute", $resource);
    }

    public function testIsUpdatable()
    {
        $this->expectsRequest(
            'post',
            '/v1/disputes/' . self::TEST_RESOURCE_ID
        );
        $resource = Dispute::update(self::TEST_RESOURCE_ID, [
            "metadata" => ["key" => "value"],
        ]);
        $this->assertInstanceOf("Stripe\\Dispute", $resource);
    }

    public function testIsClosable()
    {
        $dispute = Dispute::retrieve(self::TEST_RESOURCE_ID);
        $this->expectsRequest(
            'post',
            '/v1/disputes/' . $dispute->id . '/close'
        );
        $resource = $dispute->close();
        $this->assertInstanceOf("Stripe\\Dispute", $resource);
        $this->assertSame($resource, $dispute);
    }
}
