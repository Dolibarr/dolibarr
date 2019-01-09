<?php

namespace Stripe;

class TokenTest extends TestCase
{
    const TEST_RESOURCE_ID = 'tok_123';

    public function testIsRetrievable()
    {
        $this->expectsRequest(
            'get',
            '/v1/tokens/' . self::TEST_RESOURCE_ID
        );
        $resource = Token::retrieve(self::TEST_RESOURCE_ID);
        $this->assertInstanceOf("Stripe\\Token", $resource);
    }

    public function testIsCreatable()
    {
        $this->expectsRequest(
            'post',
            '/v1/tokens'
        );
        $resource = Token::create(["card" => "tok_visa"]);
        $this->assertInstanceOf("Stripe\\Token", $resource);
    }
}
