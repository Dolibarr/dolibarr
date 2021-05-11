<?php

namespace Stripe;

class EphemeralKeyTest extends TestCase
{
    public function testIsCreatable()
    {
        $this->expectsRequest(
            'post',
            '/v1/ephemeral_keys',
            null,
            ["Stripe-Version: 2017-05-25"]
        );
        $resource = EphemeralKey::create([
            "customer" => "cus_123",
        ], ["stripe_version" => "2017-05-25"]);
        $this->assertInstanceOf("Stripe\\EphemeralKey", $resource);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testIsNotCreatableWithoutAnExplicitApiVersion()
    {
        $resource = EphemeralKey::create([
            "customer" => "cus_123",
        ]);
    }

    public function testIsDeletable()
    {
        $key = EphemeralKey::create([
            "customer" => "cus_123",
        ], ["stripe_version" => "2017-05-25"]);
        $this->expectsRequest(
            'delete',
            '/v1/ephemeral_keys/' . $key->id
        );
        $resource = $key->delete();
        $this->assertInstanceOf("Stripe\\EphemeralKey", $resource);
    }
}
