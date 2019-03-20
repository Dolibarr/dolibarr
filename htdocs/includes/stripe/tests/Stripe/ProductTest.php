<?php

namespace Stripe;

class ProductTest extends TestCase
{
    const TEST_RESOURCE_ID = 'prod_123';

    public function testIsListable()
    {
        $this->expectsRequest(
            'get',
            '/v1/products'
        );
        $resources = Product::all();
        $this->assertTrue(is_array($resources->data));
        $this->assertInstanceOf("Stripe\\Product", $resources->data[0]);
    }

    public function testIsRetrievable()
    {
        $this->expectsRequest(
            'get',
            '/v1/products/' . self::TEST_RESOURCE_ID
        );
        $resource = Product::retrieve(self::TEST_RESOURCE_ID);
        $this->assertInstanceOf("Stripe\\Product", $resource);
    }

    public function testIsCreatable()
    {
        $this->expectsRequest(
            'post',
            '/v1/products'
        );
        $resource = Product::create([
            'name' => 'name',
            'type' => 'good'
        ]);
        $this->assertInstanceOf("Stripe\\Product", $resource);
    }

    public function testIsSaveable()
    {
        $resource = Product::retrieve(self::TEST_RESOURCE_ID);
        $resource->metadata["key"] = "value";
        $this->expectsRequest(
            'post',
            '/v1/products/' . $resource->id
        );
        $resource->save();
        $this->assertInstanceOf("Stripe\\Product", $resource);
    }

    public function testIsUpdatable()
    {
        $this->expectsRequest(
            'post',
            '/v1/products/' . self::TEST_RESOURCE_ID
        );
        $resource = Product::update(self::TEST_RESOURCE_ID, [
            "metadata" => ["key" => "value"],
        ]);
        $this->assertInstanceOf("Stripe\\Product", $resource);
    }

    public function testIsDeletable()
    {
        $resource = Product::retrieve(self::TEST_RESOURCE_ID);
        $this->expectsRequest(
            'delete',
            '/v1/products/' . $resource->id
        );
        $resource->delete();
        $this->assertInstanceOf("Stripe\\Product", $resource);
    }
}
