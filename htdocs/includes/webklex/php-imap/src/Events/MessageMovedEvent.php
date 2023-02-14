<?php
/*
* File:     MessageMovedEvent.php
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
 * Class MessageMovedEvent
 *
 * @package Webklex\PHPIMAP\Events
 */
class MessageMovedEvent extends Event {

    /** @var Message $old_message */
    public $old_message;
    /** @var Message $new_message */
    public $new_message;

    /**
     * Create a new event instance.
     * @var Message[] $messages
     * @return void
     */
    public function __construct($messages) {
        $this->old_message = $messages[0];
        $this->new_message = $messages[1];
    }
}
