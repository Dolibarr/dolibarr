<?php

namespace Stripe;

class CountrySpecTest extends TestCase
{
    const TEST_RESOURCE_ID = 'US';

    public function testIsListable()
    {
        $this->expectsRequest(
            'get',
            '/v1/country_specs'
        );
        $resources = CountrySpec::all();
        $this->assertTrue(is_array($resources->data));
        $this->assertInstanceOf("Stripe\\CountrySpec", $resources->data[0]);
    }

    public function testIsRetrievable()
    {
        $this->expectsRequest(
            'get',
            '/v1/country_specs/' . self::TEST_RESOURCE_ID
        );
        $resource = CountrySpec::retrieve(self::TEST_RESOURCE_ID);
        $this->assertInstanceOf("Stripe\\CountrySpec", $resource);
    }
}
