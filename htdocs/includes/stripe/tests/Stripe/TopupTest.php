<?php

namespace Stripe;

class TopupTest extends TestCase
{
    const TEST_RESOURCE_ID = 'tu_123';

    public function testIsListable()
    {
        $this->expectsRequest(
            'get',
            '/v1/topups'
        );
        $resources = Topup::all();
        $this->assertTrue(is_array($resources->data));
        $this->assertInstanceOf("Stripe\\Topup", $resources->data[0]);
    }

    public function testIsRetrievable()
    {
        $this->expectsRequest(
            'get',
            '/v1/topups/' . self::TEST_RESOURCE_ID
        );
        $resource = Topup::retrieve(self::TEST_RESOURCE_ID);
        $this->assertInstanceOf("Stripe\\Topup", $resource);
    }

    public function testIsCreatable()
    {
        $this->expectsRequest(
            'post',
            '/v1/topups'
        );
        $resource = Topup::create([
            "amount" => 100,
            "currency" => "usd",
            "source" => "tok_123",
            "description" => "description",
            "statement_descriptor" => "statement descriptor"
        ]);
        $this->assertInstanceOf("Stripe\\Topup", $resource);
    }

    public function testIsSaveable()
    {
        $resource = Topup::retrieve(self::TEST_RESOURCE_ID);
        $resource->metadata["key"] = "value";
        $this->expectsRequest(
            'post',
            '/v1/topups/' . $resource->id
        );
        $resource->save();
        $this->assertInstanceOf("Stripe\\Topup", $resource);
    }

    public function testIsUpdatable()
    {
        $this->expectsRequest(
            'post',
            '/v1/topups/' . self::TEST_RESOURCE_ID
        );
        $resource = Topup::update(self::TEST_RESOURCE_ID, [
            "metadata" => ["key" => "value"],
        ]);
        $this->assertInstanceOf("Stripe\\Topup", $resource);
    }
}
