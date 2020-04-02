<?php

namespace Sabre\CalDAV\Backend;

use Sabre\CalDAV;
use Sabre\DAV;

class Mock extends AbstractBackend {

    protected $calendarData;
    protected $calendars;

    function __construct(array $calendars = [], array $calendarData = []) {

        foreach ($calendars as &$calendar) {
            if (!isset($calendar['id'])) {
                $calendar['id'] = DAV\UUIDUtil::getUUID();
            }
        }

        $this->calendars = $calendars;
        $this->calendarData = $calendarData;

    }

    /**
     * Returns a list of calendars for a principal.
     *
     * Every project is an array with the following keys:
     *  * id, a unique id that will be used by other functions to modify the
     *    calendar. This can be the same as the uri or a database key.
     *  * uri, which the basename of the uri with which the calendar is
     *    accessed.
     *  * principalUri. The owner of the calendar. Almost always the same as
     *    principalUri passed to this method.
     *
     * Furthermore it can contain webdav properties in clark notation. A very
     * common one is '{DAV:}displayname'.
     *
     * @param string $principalUri
     * @return array
     */
    function getCalendarsForUser($principalUri) {

        $r = [];
        foreach ($this->calendars as $row) {
            if ($row['principaluri'] == $principalUri) {
                $r[] = $row;
            }
        }

        return $r;

    }

    /**
     * Creates a new calendar for a principal.
     *
     * If the creation was a success, an id must be returned that can be used to reference
     * this calendar in other methods, such as updateCalendar.
     *
     * This function must return a server-wide unique id that can be used
     * later to reference the calendar.
     *
     * @param string $principalUri
     * @param string $calendarUri
     * @param array $properties
     * @return string|int
     */
    function createCalendar($principalUri, $calendarUri, array $properties) {

        $id = DAV\UUIDUtil::getUUID();
        $this->calendars[] = array_merge([
            'id'                                                                 => $id,
            'principaluri'                                                       => $principalUri,
            'uri'                                                                => $calendarUri,
            '{' . CalDAV\Plugin::NS_CALDAV . '}supported-calendar-component-set' => new CalDAV\Xml\Property\SupportedCalendarComponentSet(['VEVENT', 'VTODO']),
        ], $properties);

        return $id;

    }

    /**
     * Updates properties for a calendar.
     *
     * The list of mutations is stored in a Sabre\DAV\PropPatch object.
     * To do the actual updates, you must tell this object which properties
     * you're going to process with the handle() method.
     *
     * Calling the handle method is like telling the PropPatch object "I
     * promise I can handle updating this property".
     *
     * Read the PropPatch documentation for more info and examples.
     *
     * @param mixed $calendarId
     * @param \Sabre\DAV\PropPatch $propPatch
     * @return void
     */
    function updateCalendar($calendarId, \Sabre\DAV\PropPatch $propPatch) {

        $propPatch->handleRemaining(function($props) use ($calendarId) {

            foreach ($this->calendars as $k => $calendar) {

                if ($calendar['id'] === $calendarId) {
                    foreach ($props as $propName => $propValue) {
                        if (is_null($propValue)) {
                            unset($this->calendars[$k][$propName]);
                        } else {
                            $this->calendars[$k][$propName] = $propValue;
                        }
                    }
                    return true;

                }

            }

        });

    }

    /**
     * Delete a calendar and all it's objects
     *
     * @param string $calendarId
     * @return void
     */
    function deleteCalendar($calendarId) {

        foreach ($this->calendars as $k => $calendar) {
            if ($calendar['id'] === $calendarId) {
                unset($this->calendars[$k]);
            }
        }

    }

    /**
     * Returns all calendar objects within a calendar object.
     *
     * Every item contains an array with the following keys:
     *   * id - unique identifier which will be used for subsequent updates
     *   * calendardata - The iCalendar-compatible calendar data
     *   * uri - a unique key which will be used to construct the uri. This can be any arbitrary string.
     *   * lastmodified - a timestamp of the last modification time
     *   * etag - An arbitrary string, surrounded by double-quotes. (e.g.:
     *   '  "abcdef"')
     *   * calendarid - The calendarid as it was passed to this function.
     *
     * Note that the etag is optional, but it's highly encouraged to return for
     * speed reasons.
     *
     * The calendardata is also optional. If it's not returned
     * 'getCalendarObject' will be called later, which *is* expected to return
     * calendardata.
     *
     * @param string $calendarId
     * @return array
     */
    function getCalendarObjects($calendarId) {

        if (!isset($this->calendarData[$calendarId]))
            return [];

        $objects = $this->calendarData[$calendarId];

        foreach ($objects as $uri => &$object) {
            $object['calendarid'] = $calendarId;
            $object['uri'] = $uri;
            $object['lastmodified'] = null;
        }
        return $objects;

    }

    /**
     * Returns information from a single calendar object, based on it's object
     * uri.
     *
     * The object uri is only the basename, or filename and not a full path.
     *
     * The returned array must have the same keys as getCalendarObjects. The
     * 'calendardata' object is required here though, while it's not required
     * for getCalendarObjects.
     *
     * This method must return null if the object did not exist.
     *
     * @param mixed $calendarId
     * @param string $objectUri
     * @return array|null
     */
    function getCalendarObject($calendarId, $objectUri) {

        if (!isset($this->calendarData[$calendarId][$objectUri])) {
            return null;
        }
        $object = $this->calendarData[$calendarId][$objectUri];
        $object['calendarid'] = $calendarId;
        $object['uri'] = $objectUri;
        $object['lastmodified'] = null;
        return $object;

    }

    /**
     * Creates a new calendar object.
     *
     * @param string $calendarId
     * @param string $objectUri
     * @param string $calendarData
     * @return void
     */
    function createCalendarObject($calendarId, $objectUri, $calendarData) {

        $this->calendarData[$calendarId][$objectUri] = [
            'calendardata' => $calendarData,
            'calendarid'   => $calendarId,
            'uri'          => $objectUri,
        ];
        return '"' . md5($calendarData) . '"';

    }

    /**
     * Updates an existing calendarobject, based on it's uri.
     *
     * @param string $calendarId
     * @param string $objectUri
     * @param string $calendarData
     * @return void
     */
    function updateCalendarObject($calendarId, $objectUri, $calendarData) {

        $this->calendarData[$calendarId][$objectUri] = [
            'calendardata' => $calendarData,
            'calendarid'   => $calendarId,
            'uri'          => $objectUri,
        ];
        return '"' . md5($calendarData) . '"';

    }

    /**
     * Deletes an existing calendar object.
     *
     * @param string $calendarId
     * @param string $objectUri
     * @return void
     */
    function deleteCalendarObject($calendarId, $objectUri) {

        unset($this->calendarData[$calendarId][$objectUri]);

    }

}
