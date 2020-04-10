<?php

namespace Stripe;

/**
 * Class Person
 *
 * @package Stripe
 *
 * @property string $id
 * @property string $object
 * @property string $account
 * @property mixed $address
 * @property mixed $address_kana
 * @property mixed $address_kanji
 * @property int $created
 * @property bool $deleted
 * @property mixed $dob
 * @property string $email
 * @property string $first_name
 * @property string $first_name_kana
 * @property string $first_name_kanji
 * @property string $gender
 * @property bool $id_number_provided
 * @property string $last_name
 * @property string $last_name_kana
 * @property string $last_name_kanji
 * @property string $maiden_name
 * @property StripeObject $metadata
 * @property string $phone
 * @property mixed $relationship
 * @property mixed $requirements
 * @property bool $ssn_last_4_provided
 * @property mixed $verification
 */
class Person extends ApiResource
{
    const OBJECT_NAME = "person";

    use ApiOperations\Delete;
    use ApiOperations\Update;

    /**
     * Possible string representations of a person's gender.
     * @link https://stripe.com/docs/api/persons/object#person_object-gender
     */
    const GENDER_MALE   = 'male';
    const GENDER_FEMALE = 'female';

    /**
     * Possible string representations of a person's verification status.
     * @link https://stripe.com/docs/api/persons/object#person_object-verification-status
     */
    const VERIFICATION_STATUS_PENDING    = 'pending';
    const VERIFICATION_STATUS_UNVERIFIED = 'unverified';
    const VERIFICATION_STATUS_VERIFIED   = 'verified';

    /**
     * @return string The API URL for this Stripe account reversal.
     */
    public function instanceUrl()
    {
        $id = $this['id'];
        $account = $this['account'];
        if (!$id) {
            throw new Error\InvalidRequest(
                "Could not determine which URL to request: " .
                "class instance has invalid ID: $id",
                null
            );
        }
        $id = Util\Util::utf8($id);
        $account = Util\Util::utf8($account);

        $base = Account::classUrl();
        $accountExtn = urlencode($account);
        $extn = urlencode($id);
        return "$base/$accountExtn/persons/$extn";
    }

    /**
     * @param array|string $_id
     * @param array|string|null $_opts
     *
     * @throws \Stripe\Error\InvalidRequest
     */
    public static function retrieve($_id, $_opts = null)
    {
        $msg = "Persons cannot be accessed without an account ID. " .
               "Retrieve a Person using \$account->retrievePerson('person_id') instead.";
        throw new Error\InvalidRequest($msg, null);
    }

    /**
     * @param string $_id
     * @param array|null $_params
     * @param array|string|null $_options
     *
     * @throws \Stripe\Error\InvalidRequest
     */
    public static function update($_id, $_params = null, $_options = null)
    {
        $msg = "Persons cannot be accessed without an account ID. " .
               "Retrieve a Person using \$account->retrievePerson('person_id') instead.";
        throw new Error\InvalidRequest($msg, null);
    }
}
