<?php
/*
* File: HasEvents.php
* Category: -
* Author: M.Goldenbaum
* Created: 21.09.20 22:46
* Updated: -
*
* Description:
*  -
*/

namespace Webklex\PHPIMAP\Traits;


use Webklex\PHPIMAP\Events\Event;
use Webklex\PHPIMAP\Exceptions\EventNotFoundException;

/**
 * Trait HasEvents
 *
 * @package Webklex\PHPIMAP\Traits
 */
trait HasEvents {

    /**
     * Event holder
     *
     * @var array $events
     */
    protected $events = [];

    /**
     * Set a specific event
     * @param $section
     * @param $event
     * @param $class
     */
    public function setEvent($section, $event, $class) {
        if (isset($this->events[$section])) {
            $this->events[$section][$event] = $class;
        }
    }

    /**
     * Set all events
     * @param $events
     */
    public function setEvents($events) {
        $this->events = $events;
    }

    /**
     * Get a specific event callback
     * @param $section
     * @param $event
     *
     * @return Event
     * @throws EventNotFoundException
     */
    public function getEvent($section, $event) {
        if (isset($this->events[$section])) {
            return $this->events[$section][$event];
        }
        throw new EventNotFoundException();
    }

    /**
     * Get all events
     *
     * @return array
     */
    public function getEvents(){
        return $this->events;
    }

}