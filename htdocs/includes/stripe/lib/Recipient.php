<?php

namespace Stripe;

/**
 * Class Recipient
 *
 * @package Stripe
 */
class Recipient extends ApiResource
{
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
