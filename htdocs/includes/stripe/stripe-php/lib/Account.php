<?php

namespace Stripe;

/**
 * Class Account
 *
 * @property string $id
 * @property string $object
 * @property mixed $business_profile
 * @property string $business_type
 * @property mixed $capabilities
 * @property bool $charges_enabled
 * @property mixed $company
 * @property string $country
 * @property int $created
 * @property string $default_currency
 * @property bool $details_submitted
 * @property string $email
 * @property Collection $external_accounts
 * @property mixed $individual
 * @property StripeObject $metadata
 * @property bool $payouts_enabled
 * @property mixed $requirements
 * @property mixed $settings
 * @property mixed $tos_acceptance
 * @property string $type
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

    /**
     * Possible string representations of an account's business type.
     * @link https://stripe.com/docs/api/accounts/object#account_object-business_type
     */
    const BUSINESS_TYPE_COMPANY    = 'company';
    const BUSINESS_TYPE_INDIVIDUAL = 'individual';

    /**
     * Possible string representations of an account's capabilities.
     * @link https://stripe.com/docs/api/accounts/object#account_object-capabilities
     */
    const CAPABILITY_CARD_PAYMENTS     = 'card_payments';
    const CAPABILITY_LEGACY_PAYMENTS   = 'legacy_payments';
    const CAPABILITY_PLATFORM_PAYMENTS = 'platform_payments';

    /**
     * Possible string representations of an account's capability status.
     * @link https://stripe.com/docs/api/accounts/object#account_object-capabilities
     */
    const CAPABILITY_STATUS_ACTIVE   = 'active';
    const CAPABILITY_STATUS_INACTIVE = 'inactive';
    const CAPABILITY_STATUS_PENDING  = 'pending';

    /**
     * Possible string representations of an account's type.
     * @link https://stripe.com/docs/api/accounts/object#account_object-type
     */
    const TYPE_CUSTOM   = 'custom';
    const TYPE_EXPRESS  = 'express';
    const TYPE_STANDARD = 'standard';

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

    const PATH_CAPABILITIES = '/capabilities';
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

    /*
     * Capabilities methods
     * We can not add the capabilities() method today as the Account object already has a
     * capabilities property which is a hash and not the sub-list of capabilities.
     */


    /**
     * @param string $id The ID of the account to which the capability belongs.
     * @param string $capabilityId The ID of the capability to retrieve.
     * @param array|null $params
     * @param array|string|null $opts
     *
     * @return Capability
     */
    public static function retrieveCapability($id, $capabilityId, $params = null, $opts = null)
    {
        return self::_retrieveNestedResource($id, static::PATH_CAPABILITIES, $capabilityId, $params, $opts);
    }

    /**
     * @param string $id The ID of the account to which the capability belongs.
     * @param string $capabilityId The ID of the capability to update.
     * @param array|null $params
     * @param array|string|null $opts
     *
     * @return Capability
     */
    public static function updateCapability($id, $capabilityId, $params = null, $opts = null)
    {
        return self::_updateNestedResource($id, static::PATH_CAPABILITIES, $capabilityId, $params, $opts);
    }

    /**
     * @param string $id The ID of the account on which to retrieve the capabilities.
     * @param array|null $params
     * @param array|string|null $opts
     *
     * @return Collection The list of capabilities.
     */
    public static function allCapabilities($id, $params = null, $opts = null)
    {
        return self::_allNestedResources($id, static::PATH_CAPABILITIES, $params, $opts);
    }

    /**
     * @param string $id The ID of the account on which to create the external account.
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
     * @param string $id The ID of the account to which the external account belongs.
     * @param string $externalAccountId The ID of the external account to retrieve.
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
     * @param string $id The ID of the account to which the external account belongs.
     * @param string $externalAccountId The ID of the external account to update.
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
     * @param string $id The ID of the account to which the external account belongs.
     * @param string $externalAccountId The ID of the external account to delete.
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
     * @param string $id The ID of the account on which to retrieve the external accounts.
     * @param array|null $params
     * @param array|string|null $opts
     *
     * @return Collection The list of external accounts (BankAccount or Card).
     */
    public static function allExternalAccounts($id, $params = null, $opts = null)
    {
        return self::_allNestedResources($id, static::PATH_EXTERNAL_ACCOUNTS, $params, $opts);
    }

    /**
     * @param string $id The ID of the account on which to create the login link.
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
     * @param string $id The ID of the account on which to create the person.
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
     * @param string $id The ID of the account to which the person belongs.
     * @param string $personId The ID of the person to retrieve.
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
     * @param string $id The ID of the account to which the person belongs.
     * @param string $personId The ID of the person to update.
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
     * @param string $id The ID of the account to which the person belongs.
     * @param string $personId The ID of the person to delete.
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
     * @param string $id The ID of the account on which to retrieve the persons.
     * @param array|null $params
     * @param array|string|null $opts
     *
     * @return Collection The list of persons.
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
