<?php

namespace Stripe;

class BalanceTest extends TestCase
{
    public function testIsRetrievable()
    {
        $this->expectsRequest(
            'get',
            '/v1/balance'
        );
        $resource = Balance::retrieve();
        $this->assertInstanceOf("Stripe\\Balance", $resource);
    }
}
