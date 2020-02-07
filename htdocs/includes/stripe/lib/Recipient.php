<?php

namespace Stripe;

/**
 * Class Recipient
 *
 * @package Stripe
 *
 * @property string $id
 * @property string $object
 * @property mixed $active_account
 * @property Collection $cards
 * @property int $created
 * @property string $default_card
 * @property string $description
 * @property string $email
 * @property bool $livemode
 * @property StripeObject $metadata
 * @property string $migrated_to
 * @property string $name
 * @property string $rolled_back_from
 * @property string $type
 */
class Recipient extends ApiResource
{

    const OBJECT_NAME = "recipient";

    use ApiOperations\All;
    use ApiOperations\Create;
    use ApiOperations\Delete;
    use ApiOperations\Retrieve;
    use ApiOperations\Update;

    /**
     * @param array|null $params
     *
     * @return Collection of the Recipient's Transfers
     */
    public function transfers($params = null)
    {
        $params = $params ?: [];
        $params['recipient'] = $this->id;
        $transfers = Transfer::all($params, $this->_opts);
        return $transfers;
    }
}
