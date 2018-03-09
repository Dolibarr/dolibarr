<?php

namespace Stripe;

class BitcoinReceiverTest extends TestCase
{
    const TEST_RESOURCE_ID = 'btcrcv_123';

    // Because of the wildcard nature of sources, stripe-mock cannot currently
    // reliably return sources of a given type, so we create a fixture manually
    public function createFixture($params = [])
    {
        $base = [
            'id' => self::TEST_RESOURCE_ID,
            'object' => 'bitcoin_receiver',
            'metadata' => [],
        ];
        return BitcoinReceiver::constructFrom(
            array_merge($params, $base),
            new Util\RequestOptions()
        );
    }

    public function testHasCorrectStandaloneUrl()
    {
        $resource = $this->createFixture();
        $this->assertSame(
            "/v1/bitcoin/receivers/" . self::TEST_RESOURCE_ID,
            $resource->instanceUrl()
        );
    }

    public function testHasCorrectUrlForCustomer()
    {
        $resource = $this->createFixture(['customer' => 'cus_123']);
        $this->assertSame(
            "/v1/customers/cus_123/sources/" . self::TEST_RESOURCE_ID,
            $resource->instanceUrl()
        );
    }

    public function testIsListable()
    {
        $this->expectsRequest(
            'get',
            '/v1/bitcoin/receivers'
        );
        $resources = BitcoinReceiver::all();
        $this->assertTrue(is_array($resources->data));
        $this->assertSame("Stripe\\BitcoinReceiver", get_class($resources->data[0]));
    }

    public function testIsRetrievable()
    {
        $this->expectsRequest(
            'get',
            '/v1/bitcoin/receivers/' . self::TEST_RESOURCE_ID
        );
        $resource = BitcoinReceiver::retrieve(self::TEST_RESOURCE_ID);
        $this->assertSame("Stripe\\BitcoinReceiver", get_class($resource));
    }
}
