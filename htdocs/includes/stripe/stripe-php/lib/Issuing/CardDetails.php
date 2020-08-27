<?php

namespace Stripe\Issuing;

/**
 * Class CardDetails
 *
 * @property string $id
 * @property string $object
 * @property Card $card
 * @property string $cvc
 * @property int $exp_month
 * @property int $exp_year
 * @property string $number
 *
 * @package Stripe\Issuing
 */
class CardDetails extends \Stripe\ApiResource
{
    const OBJECT_NAME = "issuing.card_details";
}
