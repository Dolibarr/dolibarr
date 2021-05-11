<?php

namespace Stripe;

class InvoiceTest extends TestCase
{
    const TEST_RESOURCE_ID = 'in_123';

    public function testIsListable()
    {
        $this->expectsRequest(
            'get',
            '/v1/invoices'
        );
        $resources = Invoice::all();
        $this->assertTrue(is_array($resources->data));
        $this->assertInstanceOf("Stripe\\Invoice", $resources->data[0]);
    }

    public function testIsRetrievable()
    {
        $this->expectsRequest(
            'get',
            '/v1/invoices/' . self::TEST_RESOURCE_ID
        );
        $resource = Invoice::retrieve(self::TEST_RESOURCE_ID);
        $this->assertInstanceOf("Stripe\\Invoice", $resource);
    }

    public function testIsCreatable()
    {
        $this->expectsRequest(
            'post',
            '/v1/invoices'
        );
        $resource = Invoice::create([
            "customer" => "cus_123"
        ]);
        $this->assertInstanceOf("Stripe\\Invoice", $resource);
    }

    public function testIsSaveable()
    {
        $resource = Invoice::retrieve(self::TEST_RESOURCE_ID);
        $resource->metadata["key"] = "value";
        $this->expectsRequest(
            'post',
            '/v1/invoices/' . $resource->id
        );
        $resource->save();
        $this->assertInstanceOf("Stripe\\Invoice", $resource);
    }

    public function testIsUpdatable()
    {
        $this->expectsRequest(
            'post',
            '/v1/invoices/' . self::TEST_RESOURCE_ID
        );
        $resource = Invoice::update(self::TEST_RESOURCE_ID, [
            "metadata" => ["key" => "value"],
        ]);
        $this->assertInstanceOf("Stripe\\Invoice", $resource);
    }

    public function testCanRetrieveUpcoming()
    {
        $this->expectsRequest(
            'get',
            '/v1/invoices/upcoming'
        );
        $resource = Invoice::upcoming(["customer" => "cus_123"]);
        $this->assertInstanceOf("Stripe\\Invoice", $resource);
    }

    public function testIsPayable()
    {
        $invoice = Invoice::retrieve(self::TEST_RESOURCE_ID);
        $this->expectsRequest(
            'post',
            '/v1/invoices/' . $invoice->id . '/pay'
        );
        $resource = $invoice->pay();
        $this->assertInstanceOf("Stripe\\Invoice", $resource);
        $this->assertSame($resource, $invoice);
    }
}
