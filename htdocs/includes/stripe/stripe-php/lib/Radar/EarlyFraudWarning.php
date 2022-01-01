<?php

namespace Stripe\Radar;

/**
 * Class EarlyFraudWarning
 *
 * @property string $id
 * @property string $object
 * @property bool $actionable
 * @property string $charge
 * @property int $created
 * @property string $fraud_type
 * @property bool $livemode
 *
 * @package Stripe\Radar
 */
class EarlyFraudWarning extends \Stripe\ApiResource
{
    const OBJECT_NAME = "radar.early_fraud_warning";

    use \Stripe\ApiOperations\All;
    use \Stripe\ApiOperations\Retrieve;

    /**
     * Possible string representations of an early fraud warning's fraud type.
     * @link https://stripe.com/docs/api/early_fraud_warnings/object#early_fraud_warning_object-fraud_type
     */
    const FRAUD_TYPE_CARD_NEVER_RECEIVED         = 'card_never_received';
    const FRAUD_TYPE_FRAUDULENT_CARD_APPLICATION = 'fraudulent_card_application';
    const FRAUD_TYPE_MADE_WITH_COUNTERFEIT_CARD  = 'made_with_counterfeit_card';
    const FRAUD_TYPE_MADE_WITH_LOST_CARD         = 'made_with_lost_card';
    const FRAUD_TYPE_MADE_WITH_STOLEN_CARD       = 'made_with_stolen_card';
    const FRAUD_TYPE_MISC                        = 'misc';
    const FRAUD_TYPE_UNAUTHORIZED_USE_OF_CARD    = 'unauthorized_use_of_card';
}
