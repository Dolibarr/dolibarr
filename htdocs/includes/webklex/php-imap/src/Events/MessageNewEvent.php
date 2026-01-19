<?php
/*
* File:     MessageNewEvent.php
* Category: Event
* Author:   M. Goldenbaum
* Created:  25.11.20 22:21
* Updated:  -
*
* Description:
*  -
*/

namespace Webklex\PHPIMAP\Events;

use Webklex\PHPIMAP\Message;

/**
 * Class MessageNewEvent
 *
 * @package Webklex\PHPIMAP\Events
 */
class MessageNewEvent extends Event {

    /** @var Message $message */
    public Message $message;

    /**
     * Create a new event instance.
     * @var Message[] $messages
     *
     * @return void
     */
    public function __construct(array $messages) {
        $this->message = $messages[0];
    }
}
