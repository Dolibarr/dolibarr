<?php

namespace Sabre\VObject\Component;

use DateTimeInterface;
use Sabre\VObject;

/**
 * The VFreeBusy component.
 *
 * This component adds functionality to a component, specific for VFREEBUSY
 * components.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class VFreeBusy extends VObject\Component {

    /**
     * Checks based on the contained FREEBUSY information, if a timeslot is
     * available.
     *
     * @param DateTimeInterface $start
     * @param DateTimeInterface $end
     *
     * @return bool
     */
    function isFree(DateTimeInterface $start, DatetimeInterface $end) {

        foreach ($this->select('FREEBUSY') as $freebusy) {

            // We are only interested in FBTYPE=BUSY (the default),
            // FBTYPE=BUSY-TENTATIVE or FBTYPE=BUSY-UNAVAILABLE.
            if (isset($freebusy['FBTYPE']) && strtoupper(substr((string)$freebusy['FBTYPE'], 0, 4)) !== 'BUSY') {
                continue;
            }

            // The freebusy component can hold more than 1 value, separated by
            // commas.
            $periods = explode(',', (string)$freebusy);

            foreach ($periods as $period) {
                // Every period is formatted as [start]/[end]. The start is an
                // absolute UTC time, the end may be an absolute UTC time, or
                // duration (relative) value.
                list($busyStart, $busyEnd) = explode('/', $period);

                $busyStart = VObject\DateTimeParser::parse($busyStart);
                $busyEnd = VObject\DateTimeParser::parse($busyEnd);
                if ($busyEnd instanceof \DateInterval) {
                    $busyEnd = $busyStart->add($busyEnd);
                }

                if ($start < $busyEnd && $end > $busyStart) {
                    return false;
                }

            }

        }

        return true;

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
            'UID'     => 1,
            'DTSTAMP' => 1,

            'CONTACT'   => '?',
            'DTSTART'   => '?',
            'DTEND'     => '?',
            'ORGANIZER' => '?',
            'URL'       => '?',

            'ATTENDEE'       => '*',
            'COMMENT'        => '*',
            'FREEBUSY'       => '*',
            'REQUEST-STATUS' => '*',
        ];

    }

}
