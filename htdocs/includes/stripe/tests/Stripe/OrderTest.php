<?php

namespace Stripe;

class OrderTest extends TestCase
{
    const TEST_RESOURCE_ID = 'or_123';

    public function testIsListable()
    {
        $this->expectsRequest(
            'get',
            '/v1/orders'
        );
        $resources = Order::all();
        $this->assertTrue(is_array($resources->data));
        $this->assertInstanceOf("Stripe\\Order", $resources->data[0]);
    }

    public function testIsRetrievable()
    {
        $this->expectsRequest(
            'get',
            '/v1/orders/' . self::TEST_RESOURCE_ID
        );
        $resource = Order::retrieve(self::TEST_RESOURCE_ID);
        $this->assertInstanceOf("Stripe\\Order", $resource);
    }

    public function testIsCreatable()
    {
        $this->expectsRequest(
            'post',
            '/v1/orders'
        );
        $resource = Order::create([
            'currency' => 'usd'
        ]);
        $this->assertInstanceOf("Stripe\\Order", $resource);
    }

    public function testIsSaveable()
    {
        $resource = Order::retrieve(self::TEST_RESOURCE_ID);
        $resource->metadata["key"] = "value";
        $this->expectsRequest(
            'post',
            '/v1/orders/' . $resource->id
        );
        $resource->save();
        $this->assertInstanceOf("Stripe\\Order", $resource);
    }

    public function testIsUpdatable()
    {
        $this->expectsRequest(
            'post',
            '/v1/orders/' . self::TEST_RESOURCE_ID
        );
        $resource = Order::update(self::TEST_RESOURCE_ID, [
            "metadata" => ["key" => "value"],
        ]);
        $this->assertInstanceOf("Stripe\\Order", $resource);
    }

    public function testIsPayable()
    {
        $resource = Order::retrieve(self::TEST_RESOURCE_ID);
        $this->expectsRequest(
            'post',
            '/v1/orders/' . $resource->id . '/pay'
        );
        $resource->pay();
        $this->assertInstanceOf("Stripe\\Order", $resource);
    }

    public function testIsReturnable()
    {
        $order = Order::retrieve(self::TEST_RESOURCE_ID);
        $this->expectsRequest(
            'post',
            '/v1/orders/' . $order->id . '/returns'
        );
        $resource = $order->returnOrder();
        $this->assertInstanceOf("Stripe\\OrderReturn", $resource);
    }
}
