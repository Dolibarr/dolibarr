<?php

// File generated from our OpenAPI spec

namespace Stripe;

/**
 * This is an object representing a person associated with a Stripe account.
 *
 * A platform cannot access a Standard or Express account's persons after the
 * account starts onboarding, such as after generating an account link for the
 * account. See the <a
 * href="https://stripe.com/docs/connect/standard-accounts">Standard onboarding</a>
 * or <a href="https://stripe.com/docs/connect/express-accounts">Express onboarding
 * documentation</a> for information about platform pre-filling and account
 * onboarding steps.
 *
 * Related guide: <a
 * href="https://stripe.com/docs/connect/identity-verification-api#person-information">Handling
 * Identity Verification with the API</a>.
 *
 * @property string $id Unique identifier for the object.
 * @property string $object String representing the object's type. Objects of the same type share the same value.
 * @property null|string $account The account the person is associated with.
 * @property null|\Stripe\StripeObject $address
 * @property null|\Stripe\StripeObject $address_kana The Kana variation of the person's address (Japan only).
 * @property null|\Stripe\StripeObject $address_kanji The Kanji variation of the person's address (Japan only).
 * @property int $created Time at which the object was created. Measured in seconds since the Unix epoch.
 * @property null|\Stripe\StripeObject $dob
 * @property null|string $email The person's email address.
 * @property null|string $first_name The person's first name.
 * @property null|string $first_name_kana The Kana variation of the person's first name (Japan only).
 * @property null|string $first_name_kanji The Kanji variation of the person's first name (Japan only).
 * @property null|string[] $full_name_aliases A list of alternate names or aliases that the person is known by.
 * @property null|\Stripe\StripeObject $future_requirements Information about the upcoming new requirements for this person, including what information needs to be collected, and by when.
 * @property null|string $gender The person's gender (International regulations require either &quot;male&quot; or &quot;female&quot;).
 * @property null|bool $id_number_provided Whether the person's <code>id_number</code> was provided.
 * @property null|bool $id_number_secondary_provided Whether the person's <code>id_number_secondary</code> was provided.
 * @property null|string $last_name The person's last name.
 * @property null|string $last_name_kana The Kana variation of the person's last name (Japan only).
 * @property null|string $last_name_kanji The Kanji variation of the person's last name (Japan only).
 * @property null|string $maiden_name The person's maiden name.
 * @property null|\Stripe\StripeObject $metadata Set of <a href="https://stripe.com/docs/api/metadata">key-value pairs</a> that you can attach to an object. This can be useful for storing additional information about the object in a structured format.
 * @property null|string $nationality The country where the person is a national.
 * @property null|string $phone The person's phone number.
 * @property null|string $political_exposure Indicates if the person or any of their representatives, family members, or other closely related persons, declares that they hold or have held an important public job or function, in any jurisdiction.
 * @property null|\Stripe\StripeObject $registered_address
 * @property null|\Stripe\StripeObject $relationship
 * @property null|\Stripe\StripeObject $requirements Information about the requirements for this person, including what information needs to be collected, and by when.
 * @property null|bool $ssn_last_4_provided Whether the last four digits of the person's Social Security number have been provided (U.S. only).
 * @property null|\Stripe\StripeObject $verification
 */
class Person extends ApiResource
{
    const OBJECT_NAME = 'person';

    use ApiOperations\Delete;
    use ApiOperations\Update;

    const GENDER_FEMALE = 'female';
    const GENDER_MALE = 'male';

    const POLITICAL_EXPOSURE_EXISTING = 'existing';
    const POLITICAL_EXPOSURE_NONE = 'none';

    const VERIFICATION_STATUS_PENDING = 'pending';
    const VERIFICATION_STATUS_UNVERIFIED = 'unverified';
    const VERIFICATION_STATUS_VERIFIED = 'verified';

    /**
     * @return string the API URL for this Stripe account reversal
     */
    public function instanceUrl()
    {
        $id = $this['id'];
        $account = $this['account'];
        if (!$id) {
            throw new Exception\UnexpectedValueException(
                'Could not determine which URL to request: ' .
                "class instance has invalid ID: {$id}",
                null
            );
        }
        $id = Util\Util::utf8($id);
        $account = Util\Util::utf8($account);

        $base = Account::classUrl();
        $accountExtn = \urlencode($account);
        $extn = \urlencode($id);

        return "{$base}/{$accountExtn}/persons/{$extn}";
    }

    /**
     * @param array|string $_id
     * @param null|array|string $_opts
     *
     * @throws \Stripe\Exception\BadMethodCallException
     */
    public static function retrieve($_id, $_opts = null)
    {
        $msg = 'Persons cannot be retrieved without an account ID. Retrieve ' .
               "a person using `Account::retrievePerson('account_id', " .
               "'person_id')`.";

        throw new Exception\BadMethodCallException($msg);
    }

    /**
     * @param string $_id
     * @param null|array $_params
     * @param null|array|string $_options
     *
     * @throws \Stripe\Exception\BadMethodCallException
     */
    public static function update($_id, $_params = null, $_options = null)
    {
        $msg = 'Persons cannot be updated without an account ID. Update ' .
               "a person using `Account::updatePerson('account_id', " .
               "'person_id', \$updateParams)`.";

        throw new Exception\BadMethodCallException($msg);
    }
}
