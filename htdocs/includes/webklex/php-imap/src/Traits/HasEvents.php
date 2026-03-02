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
    protected array $events = [];

    /**
     * Set a specific event
     * @param string $section
     * @param string $event
     * @param mixed $class
     */
    public function setEvent(string $section, string $event, mixed $class): void {
        if (isset($this->events[$section])) {
            $this->events[$section][$event] = $class;
        }
    }

    /**
     * Set all events
     * @param array $events
     */
    public function setEvents(array $events): void {
        $this->events = $events;
    }

    /**
     * Get a specific event callback
     * @param string $section
     * @param string $event
     *
     * @return Event|string
     * @throws EventNotFoundException
     */
    public function getEvent(string $section, string $event): Event|string {
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
    public function getEvents(): array {
        return $this->events;
    }

    /**
     * Dispatch a specific event.
     * @throws EventNotFoundException
     */
    public function dispatch(string $section, string $event, mixed ...$args): void {
        $event = $this->getEvent($section, $event);
        $event::dispatch(...$args);
    }

}