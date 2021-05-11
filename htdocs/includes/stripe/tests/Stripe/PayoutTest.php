<?php

namespace Stripe;

class PayoutTest extends TestCase
{
    const TEST_RESOURCE_ID = 'po_123';

    public function testIsListable()
    {
        $this->expectsRequest(
            'get',
            '/v1/payouts'
        );
        $resources = Payout::all();
        $this->assertTrue(is_array($resources->data));
        $this->assertInstanceOf("Stripe\\Payout", $resources->data[0]);
    }

    public function testIsRetrievable()
    {
        $this->expectsRequest(
            'get',
            '/v1/payouts/' . self::TEST_RESOURCE_ID
        );
        $resource = Payout::retrieve(self::TEST_RESOURCE_ID);
        $this->assertInstanceOf("Stripe\\Payout", $resource);
    }

    public function testIsCreatable()
    {
        $this->expectsRequest(
            'post',
            '/v1/payouts'
        );
        $resource = Payout::create([
            "amount" => 100,
            "currency" => "usd"
        ]);
        $this->assertInstanceOf("Stripe\\Payout", $resource);
    }

    public function testIsSaveable()
    {
        $resource = Payout::retrieve(self::TEST_RESOURCE_ID);
        $resource->metadata["key"] = "value";
        $this->expectsRequest(
            'post',
            '/v1/payouts/' . $resource->id
        );
        $resource->save();
        $this->assertInstanceOf("Stripe\\Payout", $resource);
    }

    public function testIsUpdatable()
    {
        $this->expectsRequest(
            'post',
            '/v1/payouts/' . self::TEST_RESOURCE_ID
        );
        $resource = Payout::update(self::TEST_RESOURCE_ID, [
            "metadata" => ["key" => "value"],
        ]);
        $this->assertInstanceOf("Stripe\\Payout", $resource);
    }

    public function testIsCancelable()
    {
        $resource = Payout::retrieve(self::TEST_RESOURCE_ID);
        $this->expectsRequest(
            'post',
            '/v1/payouts/' . $resource->id . '/cancel'
        );
        $resource->cancel();
        $this->assertInstanceOf("Stripe\\Payout", $resource);
    }
}
