<?php

namespace Sabre\VObject\Component;

use DateTimeImmutable;
use DateTimeInterface;
use Sabre\VObject;
use Sabre\VObject\InvalidDataException;

/**
 * VAlarm component.
 *
 * This component contains some additional functionality specific for VALARMs.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class VAlarm extends VObject\Component {

    /**
     * Returns a DateTime object when this alarm is going to trigger.
     *
     * This ignores repeated alarm, only the first trigger is returned.
     *
     * @return DateTimeImmutable
     */
    function getEffectiveTriggerTime() {

        $trigger = $this->TRIGGER;
        if (!isset($trigger['VALUE']) || strtoupper($trigger['VALUE']) === 'DURATION') {
            $triggerDuration = VObject\DateTimeParser::parseDuration($this->TRIGGER);
            $related = (isset($trigger['RELATED']) && strtoupper($trigger['RELATED']) == 'END') ? 'END' : 'START';

            $parentComponent = $this->parent;
            if ($related === 'START') {

                if ($parentComponent->name === 'VTODO') {
                    $propName = 'DUE';
                } else {
                    $propName = 'DTSTART';
                }

                $effectiveTrigger = $parentComponent->$propName->getDateTime();
                $effectiveTrigger = $effectiveTrigger->add($triggerDuration);
            } else {
                if ($parentComponent->name === 'VTODO') {
                    $endProp = 'DUE';
                } elseif ($parentComponent->name === 'VEVENT') {
                    $endProp = 'DTEND';
                } else {
                    throw new InvalidDataException('time-range filters on VALARM components are only supported when they are a child of VTODO or VEVENT');
                }

                if (isset($parentComponent->$endProp)) {
                    $effectiveTrigger = $parentComponent->$endProp->getDateTime();
                    $effectiveTrigger = $effectiveTrigger->add($triggerDuration);
                } elseif (isset($parentComponent->DURATION)) {
                    $effectiveTrigger = $parentComponent->DTSTART->getDateTime();
                    $duration = VObject\DateTimeParser::parseDuration($parentComponent->DURATION);
                    $effectiveTrigger = $effectiveTrigger->add($duration);
                    $effectiveTrigger = $effectiveTrigger->add($triggerDuration);
                } else {
                    $effectiveTrigger = $parentComponent->DTSTART->getDateTime();
                    $effectiveTrigger = $effectiveTrigger->add($triggerDuration);
                }
            }
        } else {
            $effectiveTrigger = $trigger->getDateTime();
        }
        return $effectiveTrigger;

    }

    /**
     * Returns true or false depending on if the event falls in the specified
     * time-range. This is used for filtering purposes.
     *
     * The rules used to determine if an event falls within the specified
     * time-range is based on the CalDAV specification.
     *
     * @param DateTime $start
     * @param DateTime $end
     *
     * @return bool
     */
    function isInTimeRange(DateTimeInterface $start, DateTimeInterface $end) {

        $effectiveTrigger = $this->getEffectiveTriggerTime();

        if (isset($this->DURATION)) {
            $duration = VObject\DateTimeParser::parseDuration($this->DURATION);
            $repeat = (string)$this->REPEAT;
            if (!$repeat) {
                $repeat = 1;
            }

            $period = new \DatePeriod($effectiveTrigger, $duration, (int)$repeat);

            foreach ($period as $occurrence) {

                if ($start <= $occurrence && $end > $occurrence) {
                    return true;
                }
            }
            return false;
        } else {
            return ($start <= $effectiveTrigger && $end > $effectiveTrigger);
        }

    }

    /**
     * A simple list of validation rules.
     *
     * This is simply a list of properties, and how many times they either
     * must or must not appear.
     *
     * Possible values per property:
     *   * 0 - Must not appear.
     *   * 1 - Must appear exactly once.
     *   * + - Must appear at least once.
     *   * * - Can appear any number of times.
     *   * ? - May appear, but not more than once.
     *
     * @var array
     */
    function getValidationRules() {

        return [
            'ACTION'  => 1,
            'TRIGGER' => 1,

            'DURATION' => '?',
            'REPEAT'   => '?',

            'ATTACH' => '?',
        ];

    }

}
