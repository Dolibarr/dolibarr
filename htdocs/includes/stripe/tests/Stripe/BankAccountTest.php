<?php

namespace Stripe;

class BankAccountTest extends TestCase
{
    const TEST_RESOURCE_ID = 'ba_123';

    // Because of the wildcard nature of sources, stripe-mock cannot currently
    // reliably return sources of a given type, so we create a fixture manually
    public function createFixture($params = [])
    {
        if (empty($params)) {
            $params['customer'] = 'cus_123';
        }
        $base = [
            'id' => self::TEST_RESOURCE_ID,
            'object' => 'bank_account',
            'metadata' => [],
        ];
        return BankAccount::constructFrom(
            array_merge($params, $base),
            new Util\RequestOptions()
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

    public function testHasCorrectUrlForAccount()
    {
        $resource = $this->createFixture(['account' => 'acct_123']);
        $this->assertSame(
            "/v1/accounts/acct_123/external_accounts/" . self::TEST_RESOURCE_ID,
            $resource->instanceUrl()
        );
    }

    /**
     * @expectedException \Stripe\Error\InvalidRequest
     */
    public function testIsNotDirectlyRetrievable()
    {
        BankAccount::retrieve(self::TEST_RESOURCE_ID);
    }

    public function testIsSaveable()
    {
        $resource = $this->createFixture();
        $resource->metadata["key"] = "value";
        $this->expectsRequest(
            'post',
            '/v1/customers/cus_123/sources/' . self::TEST_RESOURCE_ID
        );
        $resource->save();
        $this->assertSame("Stripe\\BankAccount", get_class($resource));
    }

    /**
     * @expectedException \Stripe\Error\InvalidRequest
     */
    public function testIsNotDirectlyUpdatable()
    {
        BankAccount::update(self::TEST_RESOURCE_ID, [
            "metadata" => ["key" => "value"],
        ]);
    }

    public function testIsDeletable()
    {
        $resource = $this->createFixture();
        $this->expectsRequest(
            'delete',
            '/v1/customers/cus_123/sources/' . self::TEST_RESOURCE_ID
        );
        $resource->delete();
        $this->assertSame("Stripe\\BankAccount", get_class($resource));
    }

    public function testIsVerifiable()
    {
        $resource = $this->createFixture();
        $this->expectsRequest(
            'post',
            '/v1/customers/cus_123/sources/' . self::TEST_RESOURCE_ID . "/verify",
            [
                "amounts" => [1, 2]
            ]
        );
        $resource->verify(["amounts" => [1, 2]]);
        $this->assertInstanceOf("Stripe\\BankAccount", $resource);
    }
}
