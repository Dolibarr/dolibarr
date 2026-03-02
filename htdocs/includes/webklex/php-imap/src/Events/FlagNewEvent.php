<?php
/*
* File:     FlagNewEvent.php
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
 * Class FlagNewEvent
 *
 * @package Webklex\PHPIMAP\Events
 */
class FlagNewEvent extends Event {

    /** @var Message $message */
    public Message $message;

    /** @var string $flag */
    public string $flag;

    /**
     * Create a new event instance.
     * @var array $arguments
     *
     * @return void
     */
    public function __construct(array $arguments) {
        $this->message = $arguments[0];
        $this->flag = $arguments[1];
    }
}
