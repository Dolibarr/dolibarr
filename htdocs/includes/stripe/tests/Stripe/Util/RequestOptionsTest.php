<?php

namespace Stripe;

class RequestOptionsTest extends TestCase
{
    public function testStringAPIKey()
    {
        $opts = Util\RequestOptions::parse("foo");
        $this->assertSame("foo", $opts->apiKey);
        $this->assertSame([], $opts->headers);
    }

    public function testNull()
    {
        $opts = Util\RequestOptions::parse(null);
        $this->assertSame(null, $opts->apiKey);
        $this->assertSame([], $opts->headers);
    }

    public function testEmptyArray()
    {
        $opts = Util\RequestOptions::parse([]);
        $this->assertSame(null, $opts->apiKey);
        $this->assertSame([], $opts->headers);
    }

    public function testAPIKeyArray()
    {
        $opts = Util\RequestOptions::parse(
            [
                'api_key' => 'foo',
            ]
        );
        $this->assertSame('foo', $opts->apiKey);
        $this->assertSame([], $opts->headers);
    }

    public function testIdempotentKeyArray()
    {
        $opts = Util\RequestOptions::parse(
            [
                'idempotency_key' => 'foo',
            ]
        );
        $this->assertSame(null, $opts->apiKey);
        $this->assertSame(['Idempotency-Key' => 'foo'], $opts->headers);
    }

    public function testKeyArray()
    {
        $opts = Util\RequestOptions::parse(
            [
                'idempotency_key' => 'foo',
                'api_key' => 'foo'
            ]
        );
        $this->assertSame('foo', $opts->apiKey);
        $this->assertSame(['Idempotency-Key' => 'foo'], $opts->headers);
    }

    /**
     * @expectedException Stripe\Error\Api
     */
    public function testWrongType()
    {
        $opts = Util\RequestOptions::parse(5);
    }

    public function testDiscardNonPersistentHeaders()
    {
        $opts = Util\RequestOptions::parse(
            [
                'stripe_account' => 'foo',
                'idempotency_key' => 'foo',
            ]
        );
        $opts->discardNonPersistentHeaders();
        $this->assertSame(['Stripe-Account' => 'foo'], $opts->headers);
    }
}
