<?php

namespace Stripe;

class CouponTest extends TestCase
{
    const TEST_RESOURCE_ID = '25OFF';

    public function testIsListable()
    {
        $this->expectsRequest(
            'get',
            '/v1/coupons'
        );
        $resources = Coupon::all();
        $this->assertTrue(is_array($resources->data));
        $this->assertInstanceOf("Stripe\\Coupon", $resources->data[0]);
    }

    public function testIsRetrievable()
    {
        $this->expectsRequest(
            'get',
            '/v1/coupons/' . self::TEST_RESOURCE_ID
        );
        $resource = Coupon::retrieve(self::TEST_RESOURCE_ID);
        $this->assertInstanceOf("Stripe\\Coupon", $resource);
    }

    public function testIsCreatable()
    {
        $this->expectsRequest(
            'post',
            '/v1/coupons'
        );
        $resource = Coupon::create([
            "percent_off" => 25,
            "duration" => "repeating",
            "duration_in_months" => 3,
            "id" => self::TEST_RESOURCE_ID,
        ]);
        $this->assertInstanceOf("Stripe\\Coupon", $resource);
    }

    public function testIsSaveable()
    {
        $resource = Coupon::retrieve(self::TEST_RESOURCE_ID);
        $resource->metadata["key"] = "value";
        $this->expectsRequest(
            'post',
            '/v1/coupons/' . self::TEST_RESOURCE_ID
        );
        $resource->save();
        $this->assertInstanceOf("Stripe\\Coupon", $resource);
    }

    public function testIsUpdatable()
    {
        $this->expectsRequest(
            'post',
            '/v1/coupons/' . self::TEST_RESOURCE_ID
        );
        $resource = Coupon::update(self::TEST_RESOURCE_ID, [
            "metadata" => ["key" => "value"],
        ]);
        $this->assertInstanceOf("Stripe\\Coupon", $resource);
    }

    public function testIsDeletable()
    {
        $resource = Coupon::retrieve(self::TEST_RESOURCE_ID);
        $this->expectsRequest(
            'delete',
            '/v1/coupons/' . self::TEST_RESOURCE_ID
        );
        $resource->delete();
        $this->assertInstanceOf("Stripe\\Coupon", $resource);
    }
}
