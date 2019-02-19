<?php

namespace Stripe;

/**
 * Class Account
 *
 * @property string $id
 * @property string $object
 * @property string $business_logo
 * @property string $business_name
 * @property string $business_primary_color
 * @property string $business_url
 * @property mixed $capabilities
 * @property bool $charges_enabled
 * @property string $country
 * @property int $created
 * @property bool $debit_negative_balances
 * @property mixed $decline_charge_on
 * @property string $default_currency
 * @property bool $details_submitted
 * @property string $display_name
 * @property string $email
 * @property Collection $external_accounts
 * @property mixed $legal_entity
 * @property StripeObject $metadata
 * @property mixed $payout_schedule
 * @property string $payout_statement_descriptor
 * @property bool $payouts_enabled
 * @property string $product_description
 * @property string $statement_descriptor
 * @property mixed $support_address
 * @property string $support_email
 * @property string $support_phone
 * @property string $support_url
 * @property string $timezone
 * @property mixed $tos_acceptance
 * @property string $type
 * @property mixed $verification
 *
 * @package Stripe
 */
class Account extends ApiResource
{

    const OBJECT_NAME = "account";

    use ApiOperations\All;
    use ApiOperations\Create;
    use ApiOperations\Delete;
    use ApiOperations\NestedResource;
    use ApiOperations\Retrieve {
        retrieve as protected _retrieve;
    }
    use ApiOperations\Update;

    public static function getSavedNestedResources()
    {
        static $savedNestedResources = null;
        if ($savedNestedResources === null) {
            $savedNestedResources = new Util\Set([
                'external_account',
                'bank_account',
            ]);
        }
        return $savedNestedResources;
    }

    const PATH_EXTERNAL_ACCOUNTS = '/external_accounts';
    const PATH_LOGIN_LINKS = '/login_links';
    const PATH_PERSONS = '/persons';

    public function instanceUrl()
    {
        if ($this['id'] === null) {
            return '/v1/account';
        } else {
            return parent::instanceUrl();
        }
    }

    /**
     * @param array|string|null $id The ID of the account to retrieve, or an
     *     options array containing an `id` key.
     * @param array|string|null $opts
     *
     * @return Account
     */
    public static function retrieve($id = null, $opts = null)
    {
        if (!$opts && is_string($id) && substr($id, 0, 3) === 'sk_') {
            $opts = $id;
            $id = null;
        }
        return self::_retrieve($id, $opts);
    }

    /**
     * @param array|null $params
     * @param array|string|null $opts
     *
     * @return Account The rejected account.
     */
    public function reject($params = null, $opts = null)
    {
        $url = $this->instanceUrl() . '/reject';
        list($response, $opts) = $this->_request('post', $url, $params, $opts);
        $this->refreshFrom($response, $opts);
        return $this;
    }

    /**
     * @param array|null $params
     * @param array|string|null $options
     *
     * @return Collection The list of persons.
     */
    public function persons($params = null, $options = null)
    {
        $url = $this->instanceUrl() . '/persons';
        list($response, $opts) = $this->_request('get', $url, $params, $options);
        $obj = Util\Util::convertToStripeObject($response, $opts);
        $obj->setLastResponse($response);
        return $obj;
    }

    /**
     * @param array|null $clientId
     * @param array|string|null $opts
     *
     * @return StripeObject Object containing the response from the API.
     */
    public function deauthorize($clientId = null, $opts = null)
    {
        $params = [
            'client_id' => $clientId,
            'stripe_user_id' => $this->id,
        ];
        return OAuth::deauthorize($params, $opts);
    }

    /**
     * @param string|null $id The ID of the account on which to create the external account.
     * @param array|null $params
     * @param array|string|null $opts
     *
     * @return BankAccount|Card
     */
    public static function createExternalAccount($id, $params = null, $opts = null)
    {
        return self::_createNestedResource($id, static::PATH_EXTERNAL_ACCOUNTS, $params, $opts);
    }

    /**
     * @param string|null $id The ID of the account to which the external account belongs.
     * @param array|null $externalAccountId The ID of the external account to retrieve.
     * @param array|null $params
     * @param array|string|null $opts
     *
     * @return BankAccount|Card
     */
    public static function retrieveExternalAccount($id, $externalAccountId, $params = null, $opts = null)
    {
        return self::_retrieveNestedResource($id, static::PATH_EXTERNAL_ACCOUNTS, $externalAccountId, $params, $opts);
    }

    /**
     * @param string|null $id The ID of the account to which the external account belongs.
     * @param array|null $externalAccountId The ID of the external account to update.
     * @param array|null $params
     * @param array|string|null $opts
     *
     * @return BankAccount|Card
     */
    public static function updateExternalAccount($id, $externalAccountId, $params = null, $opts = null)
    {
        return self::_updateNestedResource($id, static::PATH_EXTERNAL_ACCOUNTS, $externalAccountId, $params, $opts);
    }

    /**
     * @param string|null $id The ID of the account to which the external account belongs.
     * @param array|null $externalAccountId The ID of the external account to delete.
     * @param array|null $params
     * @param array|string|null $opts
     *
     * @return BankAccount|Card
     */
    public static function deleteExternalAccount($id, $externalAccountId, $params = null, $opts = null)
    {
        return self::_deleteNestedResource($id, static::PATH_EXTERNAL_ACCOUNTS, $externalAccountId, $params, $opts);
    }

    /**
     * @param string|null $id The ID of the account on which to retrieve the external accounts.
     * @param array|null $params
     * @param array|string|null $opts
     *
     * @return BankAccount|Card
     */
    public static function allExternalAccounts($id, $params = null, $opts = null)
    {
        return self::_allNestedResources($id, static::PATH_EXTERNAL_ACCOUNTS, $params, $opts);
    }

    /**
     * @param string|null $id The ID of the account on which to create the login link.
     * @param array|null $params
     * @param array|string|null $opts
     *
     * @return LoginLink
     */
    public static function createLoginLink($id, $params = null, $opts = null)
    {
        return self::_createNestedResource($id, static::PATH_LOGIN_LINKS, $params, $opts);
    }

    /**
     * @param string|null $id The ID of the account on which to create the person.
     * @param array|null $params
     * @param array|string|null $opts
     *
     * @return Person
     */
    public static function createPerson($id, $params = null, $opts = null)
    {
        return self::_createNestedResource($id, static::PATH_PERSONS, $params, $opts);
    }

    /**
     * @param string|null $id The ID of the account to which the person belongs.
     * @param array|null $personId The ID of the person to retrieve.
     * @param array|null $params
     * @param array|string|null $opts
     *
     * @return Person
     */
    public static function retrievePerson($id, $personId, $params = null, $opts = null)
    {
        return self::_retrieveNestedResource($id, static::PATH_PERSONS, $personId, $params, $opts);
    }

    /**
     * @param string|null $id The ID of the account to which the person belongs.
     * @param array|null $personId The ID of the person to update.
     * @param array|null $params
     * @param array|string|null $opts
     *
     * @return Person
     */
    public static function updatePerson($id, $personId, $params = null, $opts = null)
    {
        return self::_updateNestedResource($id, static::PATH_PERSONS, $personId, $params, $opts);
    }

    /**
     * @param string|null $id The ID of the account to which the person belongs.
     * @param array|null $personId The ID of the person to delete.
     * @param array|null $params
     * @param array|string|null $opts
     *
     * @return Person
     */
    public static function deletePerson($id, $personId, $params = null, $opts = null)
    {
        return self::_deleteNestedResource($id, static::PATH_PERSONS, $personId, $params, $opts);
    }

    /**
     * @param string|null $id The ID of the account on which to retrieve the persons.
     * @param array|null $params
     * @param array|string|null $opts
     *
     * @return Person
     */
    public static function allPersons($id, $params = null, $opts = null)
    {
        return self::_allNestedResources($id, static::PATH_PERSONS, $params, $opts);
    }

    public function serializeParameters($force = false)
    {
        $update = parent::serializeParameters($force);
        if (isset($this->_values['legal_entity'])) {
            $entity = $this['legal_entity'];
            if (isset($entity->_values['additional_owners'])) {
                $owners = $entity['additional_owners'];
                $entityUpdate = isset($update['legal_entity']) ? $update['legal_entity'] : [];
                $entityUpdate['additional_owners'] = $this->serializeAdditionalOwners($entity, $owners);
                $update['legal_entity'] = $entityUpdate;
            }
        }
        if (isset($this->_values['individual'])) {
            $individual = $this['individual'];
            if (($individual instanceof Person) && !isset($update['individual'])) {
                $update['individual'] = $individual->serializeParameters($force);
            }
        }
        return $update;
    }

    private function serializeAdditionalOwners($legalEntity, $additionalOwners)
    {
        if (isset($legalEntity->_originalValues['additional_owners'])) {
            $originalValue = $legalEntity->_originalValues['additional_owners'];
        } else {
            $originalValue = [];
        }
        if (($originalValue) && (count($originalValue) > count($additionalOwners))) {
            throw new \InvalidArgumentException(
                "You cannot delete an item from an array, you must instead set a new array"
            );
        }

        $updateArr = [];
        foreach ($additionalOwners as $i => $v) {
            $update = ($v instanceof StripeObject) ? $v->serializeParameters() : $v;

            if ($update !== []) {
                if (!$originalValue ||
                    !array_key_exists($i, $originalValue) ||
                    ($update != $legalEntity->serializeParamsValue($originalValue[$i], null, false, true))) {
                    $updateArr[$i] = $update;
                }
            }
        }
        return $updateArr;
    }
}
