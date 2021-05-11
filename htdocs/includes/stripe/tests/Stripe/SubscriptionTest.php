<?php

namespace Stripe;

class SubscriptionTest extends TestCase
{
    const TEST_RESOURCE_ID = 'sub_123';

    public function testIsListable()
    {
        $this->expectsRequest(
            'get',
            '/v1/subscriptions'
        );
        $resources = Subscription::all();
        $this->assertTrue(is_array($resources->data));
        $this->assertInstanceOf("Stripe\\Subscription", $resources->data[0]);
    }

    public function testIsRetrievable()
    {
        $this->expectsRequest(
            'get',
            '/v1/subscriptions/' . self::TEST_RESOURCE_ID
        );
        $resource = Subscription::retrieve(self::TEST_RESOURCE_ID);
        $this->assertInstanceOf("Stripe\\Subscription", $resource);
    }

    public function testIsCreatable()
    {
        $this->expectsRequest(
            'post',
            '/v1/subscriptions'
        );
        $resource = Subscription::create([
            "customer" => "cus_123",
            "plan" => "plan"
        ]);
        $this->assertInstanceOf("Stripe\\Subscription", $resource);
    }

    public function testIsSaveable()
    {
        $resource = Subscription::retrieve(self::TEST_RESOURCE_ID);
        $resource->metadata["key"] = "value";
        $this->expectsRequest(
            'post',
            '/v1/subscriptions/' . $resource->id
        );
        $resource->save();
        $this->assertInstanceOf("Stripe\\Subscription", $resource);
    }

    public function testIsUpdatable()
    {
        $this->expectsRequest(
            'post',
            '/v1/subscriptions/' . self::TEST_RESOURCE_ID
        );
        $resource = Subscription::update(self::TEST_RESOURCE_ID, [
            "metadata" => ["key" => "value"],
        ]);
        $this->assertInstanceOf("Stripe\\Subscription", $resource);
    }

    public function testIsCancelable()
    {
        $resource = Subscription::retrieve(self::TEST_RESOURCE_ID);
        $this->expectsRequest(
            'delete',
            '/v1/subscriptions/' . $resource->id,
            [
                'at_period_end' => 'true',
            ]
        );
        $resource->cancel([
            'at_period_end' => true,
        ]);
        $this->assertInstanceOf("Stripe\\Subscription", $resource);
    }

    public function testCanDeleteDiscount()
    {
        $resource = Subscription::retrieve(self::TEST_RESOURCE_ID);
        $this->expectsRequest(
            'delete',
            '/v1/subscriptions/' . $resource->id . '/discount'
        );
        $resource->deleteDiscount();
        $this->assertInstanceOf("Stripe\\Subscription", $resource);
    }

    public function testSerializeParametersItems()
    {
        $obj = Util\Util::convertToStripeObject([
            'object' => 'subscription',
            'items' => Util\Util::convertToStripeObject([
                'object' => 'list',
                'data' => [],
            ], null),
        ], null);
        $obj->items = [
            ['id' => 'si_foo', 'deleted' => true],
            ['plan' => 'plan_bar'],
        ];
        $expected = [
            'items' => [
                0 => ['id' => 'si_foo', 'deleted' => true],
                1 => ['plan' => 'plan_bar'],
            ],
        ];
        $this->assertSame($expected, $obj->serializeParameters());
    }
}
