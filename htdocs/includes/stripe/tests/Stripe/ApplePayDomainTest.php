<?php

namespace Stripe;

class ApplePayDomainTest extends TestCase
{
    const TEST_RESOURCE_ID = 'apwc_123';

    public function testIsListable()
    {
        $this->expectsRequest(
            'get',
            '/v1/apple_pay/domains'
        );
        $resources = ApplePayDomain::all();
        $this->assertTrue(is_array($resources->data));
        $this->assertInstanceOf("Stripe\\ApplePayDomain", $resources->data[0]);
    }

    public function testIsRetrievable()
    {
        $this->expectsRequest(
            'get',
            '/v1/apple_pay/domains/' . self::TEST_RESOURCE_ID
        );
        $resource = ApplePayDomain::retrieve(self::TEST_RESOURCE_ID);
        $this->assertInstanceOf("Stripe\\ApplePayDomain", $resource);
    }

    public function testIsCreatable()
    {
        $this->expectsRequest(
            'post',
            '/v1/apple_pay/domains'
        );
        $resource = ApplePayDomain::create([
            "domain_name" => "domain",
        ]);
        $this->assertInstanceOf("Stripe\\ApplePayDomain", $resource);
    }

    public function testIsDeletable()
    {
        $resource = ApplePayDomain::retrieve(self::TEST_RESOURCE_ID);
        $this->expectsRequest(
            'delete',
            '/v1/apple_pay/domains/' . $resource->id
        );
        $resource->delete();
        $this->assertInstanceOf("Stripe\\ApplePayDomain", $resource);
    }
}
