<?php

namespace Stripe;

/**
 * Class InvoiceItem
 *
 * @property string $id
 * @property string $object
 * @property int $amount
 * @property string $currency
 * @property string $customer
 * @property int $date
 * @property string $description
 * @property bool $discountable
 * @property string $invoice
 * @property bool $livemode
 * @property StripeObject $metadata
 * @property mixed $period
 * @property Plan $plan
 * @property bool $proration
 * @property int $quantity
 * @property string $subscription
 * @property string $subscription_item
<<<<<<< HEAD
=======
 * @property array $tax_rates
 * @property int $unit_amount
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
 *
 * @package Stripe
 */
class InvoiceItem extends ApiResource
{
<<<<<<< HEAD
=======

    const OBJECT_NAME = "invoiceitem";

>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    use ApiOperations\All;
    use ApiOperations\Create;
    use ApiOperations\Delete;
    use ApiOperations\Retrieve;
    use ApiOperations\Update;
}
