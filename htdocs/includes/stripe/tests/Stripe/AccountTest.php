<?php

namespace Stripe;

class AccountTest extends TestCase
{
    const TEST_RESOURCE_ID = 'acct_123';
    const TEST_EXTERNALACCOUNT_ID = 'ba_123';

    public function testIsListable()
    {
        $this->expectsRequest(
            'get',
            '/v1/accounts'
        );
        $resources = Account::all();
        $this->assertTrue(is_array($resources->data));
        $this->assertInstanceOf("Stripe\\Account", $resources->data[0]);
    }

    public function testIsRetrievable()
    {
        $this->expectsRequest(
            'get',
            '/v1/accounts/' . self::TEST_RESOURCE_ID
        );
        $resource = Account::retrieve(self::TEST_RESOURCE_ID);
        $this->assertInstanceOf("Stripe\\Account", $resource);
    }

    public function testIsRetrievableWithoutId()
    {
        $this->expectsRequest(
            'get',
            '/v1/account'
        );
        $resource = Account::retrieve();
        $this->assertInstanceOf("Stripe\\Account", $resource);
    }

    public function testIsCreatable()
    {
        $this->expectsRequest(
            'post',
            '/v1/accounts'
        );
        $resource = Account::create(["type" => "custom"]);
        $this->assertInstanceOf("Stripe\\Account", $resource);
    }

    public function testIsSaveable()
    {
        $resource = Account::retrieve(self::TEST_RESOURCE_ID);
        $resource->metadata["key"] = "value";
        $this->expectsRequest(
            'post',
            '/v1/accounts/' . $resource->id
        );
        $resource->save();
        $this->assertInstanceOf("Stripe\\Account", $resource);
    }

    public function testIsUpdatable()
    {
        $this->expectsRequest(
            'post',
            '/v1/accounts/' . self::TEST_RESOURCE_ID
        );
        $resource = Account::update(self::TEST_RESOURCE_ID, [
            "metadata" => ["key" => "value"],
        ]);
        $this->assertInstanceOf("Stripe\\Account", $resource);
    }

    public function testIsDeletable()
    {
        $resource = Account::retrieve(self::TEST_RESOURCE_ID);
        $this->expectsRequest(
            'delete',
            '/v1/accounts/' . $resource->id
        );
        $resource->delete();
        $this->assertInstanceOf("Stripe\\Account", $resource);
    }

    public function testIsRejectable()
    {
        $account = Account::retrieve(self::TEST_RESOURCE_ID);
        $this->expectsRequest(
            'post',
            '/v1/accounts/' . $account->id . '/reject'
        );
        $resource = $account->reject(["reason" => "fraud"]);
        $this->assertInstanceOf("Stripe\\Account", $resource);
        $this->assertSame($resource, $account);
    }

    public function testIsDeauthorizable()
    {
        $resource = Account::retrieve(self::TEST_RESOURCE_ID);
        $this->stubRequest(
            'post',
            '/oauth/deauthorize',
            [
                'client_id' => Stripe::getClientId(),
                'stripe_user_id' => $resource->id,
            ],
            null,
            false,
            [
                'stripe_user_id' => $resource->id,
            ],
            200,
            Stripe::$connectBase
        );
        $resource->deauthorize();
    }

    public function testCanCreateExternalAccount()
    {
        $this->expectsRequest(
            'post',
            '/v1/accounts/' . self::TEST_RESOURCE_ID . '/external_accounts'
        );
        $resource = Account::createExternalAccount(self::TEST_RESOURCE_ID, [
            "external_account" => "btok_123",
        ]);
        $this->assertInstanceOf("Stripe\\BankAccount", $resource);
    }

    public function testCanRetrieveExternalAccount()
    {
        $this->expectsRequest(
            'get',
            '/v1/accounts/' . self::TEST_RESOURCE_ID . '/external_accounts/' . self::TEST_EXTERNALACCOUNT_ID
        );
        $resource = Account::retrieveExternalAccount(self::TEST_RESOURCE_ID, self::TEST_EXTERNALACCOUNT_ID);
        $this->assertInstanceOf("Stripe\\BankAccount", $resource);
    }

    public function testCanUpdateExternalAccount()
    {
        $this->expectsRequest(
            'post',
            '/v1/accounts/' . self::TEST_RESOURCE_ID . '/external_accounts/' . self::TEST_EXTERNALACCOUNT_ID
        );
        $resource = Account::updateExternalAccount(self::TEST_RESOURCE_ID, self::TEST_EXTERNALACCOUNT_ID, [
            "name" => "name",
        ]);
        $this->assertInstanceOf("Stripe\\BankAccount", $resource);
    }

    public function testCanDeleteExternalAccount()
    {
        $this->expectsRequest(
            'delete',
            '/v1/accounts/' . self::TEST_RESOURCE_ID . '/external_accounts/' . self::TEST_EXTERNALACCOUNT_ID
        );
        $resource = Account::deleteExternalAccount(self::TEST_RESOURCE_ID, self::TEST_EXTERNALACCOUNT_ID);
        $this->assertInstanceOf("Stripe\\BankAccount", $resource);
    }

    public function testCanListExternalAccounts()
    {
        $this->expectsRequest(
            'get',
            '/v1/accounts/' . self::TEST_RESOURCE_ID . '/external_accounts'
        );
        $resources = Account::allExternalAccounts(self::TEST_RESOURCE_ID);
        $this->assertTrue(is_array($resources->data));
    }

    public function testCanCreateLoginLink()
    {
        $this->expectsRequest(
            'post',
            '/v1/accounts/' . self::TEST_RESOURCE_ID . '/login_links'
        );
        $resource = Account::createLoginLink(self::TEST_RESOURCE_ID);
        $this->assertInstanceOf("Stripe\\LoginLink", $resource);
    }

    public function testSerializeNewAdditionalOwners()
    {
        $obj = Util\Util::convertToStripeObject([
            'object' => 'account',
            'legal_entity' => StripeObject::constructFrom([]),
        ], null);
        $obj->legal_entity->additional_owners = [
            ['first_name' => 'Joe'],
            ['first_name' => 'Jane'],
        ];

        $expected = [
            'legal_entity' => [
                'additional_owners' => [
                    0 => ['first_name' => 'Joe'],
                    1 => ['first_name' => 'Jane'],
                ],
            ],
        ];
        $this->assertSame($expected, $obj->serializeParameters());
    }

    public function testSerializePartiallyChangedAdditionalOwners()
    {
        $obj = Util\Util::convertToStripeObject([
            'object' => 'account',
            'legal_entity' => [
                'additional_owners' => [
                    StripeObject::constructFrom(['first_name' => 'Joe']),
                    StripeObject::constructFrom(['first_name' => 'Jane']),
                ],
            ],
        ], null);
        $obj->legal_entity->additional_owners[1]->first_name = 'Stripe';

        $expected = [
            'legal_entity' => [
                'additional_owners' => [
                    1 => ['first_name' => 'Stripe'],
                ],
            ],
        ];
        $this->assertSame($expected, $obj->serializeParameters());
    }

    public function testSerializeUnchangedAdditionalOwners()
    {
        $obj = Util\Util::convertToStripeObject([
            'object' => 'account',
            'legal_entity' => [
                'additional_owners' => [
                    StripeObject::constructFrom(['first_name' => 'Joe']),
                    StripeObject::constructFrom(['first_name' => 'Jane']),
                ],
            ],
        ], null);

        $expected = [
            'legal_entity' => [
                'additional_owners' => [],
            ],
        ];
        $this->assertSame($expected, $obj->serializeParameters());
    }

    public function testSerializeUnsetAdditionalOwners()
    {
        $obj = Util\Util::convertToStripeObject([
            'object' => 'account',
            'legal_entity' => [
                'additional_owners' => [
                    StripeObject::constructFrom(['first_name' => 'Joe']),
                    StripeObject::constructFrom(['first_name' => 'Jane']),
                ],
            ],
        ], null);
        $obj->legal_entity->additional_owners = null;

        // Note that the empty string that we send for this one has a special
        // meaning for the server, which interprets it as an array unset.
        $expected = [
            'legal_entity' => [
                'additional_owners' => '',
            ],
        ];
        $this->assertSame($expected, $obj->serializeParameters());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testSerializeAdditionalOwnersDeletedItem()
    {
        $obj = Util\Util::convertToStripeObject([
            'object' => 'account',
            'legal_entity' => [
                'additional_owners' => [
                    StripeObject::constructFrom(['first_name' => 'Joe']),
                    StripeObject::constructFrom(['first_name' => 'Jane']),
                ],
            ],
        ], null);
        unset($obj->legal_entity->additional_owners[0]);

        $obj->serializeParameters();
    }

    public function testSerializeExternalAccountString()
    {
        $obj = Util\Util::convertToStripeObject([
            'object' => 'account',
        ], null);
        $obj->external_account = 'btok_123';

        $expected = [
            'external_account' => 'btok_123',
        ];
        $this->assertSame($expected, $obj->serializeParameters());
    }

    public function testSerializeExternalAccountHash()
    {
        $obj = Util\Util::convertToStripeObject([
            'object' => 'account',
        ], null);
        $obj->external_account = [
            'object' => 'bank_account',
            'routing_number' => '110000000',
            'account_number' => '000123456789',
            'country' => 'US',
            'currency' => 'usd',
        ];

        $expected = [
            'external_account' => [
                'object' => 'bank_account',
                'routing_number' => '110000000',
                'account_number' => '000123456789',
                'country' => 'US',
                'currency' => 'usd',
            ],
        ];
        $this->assertSame($expected, $obj->serializeParameters());
    }

    public function testSerializeBankAccountString()
    {
        $obj = Util\Util::convertToStripeObject([
            'object' => 'account',
        ], null);
        $obj->bank_account = 'btok_123';

        $expected = [
            'bank_account' => 'btok_123',
        ];
        $this->assertSame($expected, $obj->serializeParameters());
    }

    public function testSerializeBankAccountHash()
    {
        $obj = Util\Util::convertToStripeObject([
            'object' => 'account',
        ], null);
        $obj->bank_account = [
            'object' => 'bank_account',
            'routing_number' => '110000000',
            'account_number' => '000123456789',
            'country' => 'US',
            'currency' => 'usd',
        ];

        $expected = [
            'bank_account' => [
                'object' => 'bank_account',
                'routing_number' => '110000000',
                'account_number' => '000123456789',
                'country' => 'US',
                'currency' => 'usd',
            ],
        ];
        $this->assertSame($expected, $obj->serializeParameters());
    }
}
