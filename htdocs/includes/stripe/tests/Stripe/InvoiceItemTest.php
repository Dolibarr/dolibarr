<?php

namespace Stripe;

class InvoiceItemTest extends TestCase
{
    const TEST_RESOURCE_ID = 'ii_123';

    public function testIsListable()
    {
        $this->expectsRequest(
            'get',
            '/v1/invoiceitems'
        );
        $resources = InvoiceItem::all();
        $this->assertTrue(is_array($resources->data));
        $this->assertInstanceOf("Stripe\\InvoiceItem", $resources->data[0]);
    }

    public function testIsRetrievable()
    {
        $this->expectsRequest(
            'get',
            '/v1/invoiceitems/' . self::TEST_RESOURCE_ID
        );
        $resource = InvoiceItem::retrieve(self::TEST_RESOURCE_ID);
        $this->assertInstanceOf("Stripe\\InvoiceItem", $resource);
    }

    public function testIsCreatable()
    {
        $this->expectsRequest(
            'post',
            '/v1/invoiceitems'
        );
        $resource = InvoiceItem::create([
            "amount" => 100,
            "currency" => "usd",
            "customer" => "cus_123"
        ]);
        $this->assertInstanceOf("Stripe\\InvoiceItem", $resource);
    }

    public function testIsSaveable()
    {
        $resource = InvoiceItem::retrieve(self::TEST_RESOURCE_ID);
        $resource->metadata["key"] = "value";
        $this->expectsRequest(
            'post',
            '/v1/invoiceitems/' . $resource->id
        );
        $resource->save();
        $this->assertInstanceOf("Stripe\\InvoiceItem", $resource);
    }

    public function testIsUpdatable()
    {
        $this->expectsRequest(
            'post',
            '/v1/invoiceitems/' . self::TEST_RESOURCE_ID
        );
        $resource = InvoiceItem::update(self::TEST_RESOURCE_ID, [
            "metadata" => ["key" => "value"],
        ]);
        $this->assertInstanceOf("Stripe\\InvoiceItem", $resource);
    }

    public function testIsDeletable()
    {
        $invoiceItem = InvoiceItem::retrieve(self::TEST_RESOURCE_ID);
        $this->expectsRequest(
            'delete',
            '/v1/invoiceitems/' . $invoiceItem->id
        );
        $resource = $invoiceItem->delete();
        $this->assertInstanceOf("Stripe\\InvoiceItem", $resource);
    }
}
