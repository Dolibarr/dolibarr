<?php

namespace Sabre\CalDAV;

use Sabre\DAV;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\MkCol;
use Sabre\DAVACL;
use Sabre\HTTP\URLUtil;

/**
 * The CalendarHome represents a node that is usually in a users'
 * calendar-homeset.
 *
 * It contains all the users' calendars, and can optionally contain a
 * notifications collection, calendar subscriptions, a users' inbox, and a
 * users' outbox.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class CalendarHome implements DAV\IExtendedCollection, DAVACL\IACL {

    use DAVACL\ACLTrait;

    /**
     * CalDAV backend
     *
     * @var Backend\BackendInterface
     */
    protected $caldavBackend;

    /**
     * Principal information
     *
     * @var array
     */
    protected $principalInfo;

    /**
     * Constructor
     *
     * @param Backend\BackendInterface $caldavBackend
     * @param array $principalInfo
     */
    function __construct(Backend\BackendInterface $caldavBackend, $principalInfo) {

        $this->caldavBackend = $caldavBackend;
        $this->principalInfo = $principalInfo;

    }

    /**
     * Returns the name of this object
     *
     * @return string
     */
    function getName() {

        list(, $name) = URLUtil::splitPath($this->principalInfo['uri']);
        return $name;

    }

    /**
     * Updates the name of this object
     *
     * @param string $name
     * @return void
     */
    function setName($name) {

        throw new DAV\Exception\Forbidden();

    }

    /**
     * Deletes this object
     *
     * @return void
     */
    function delete() {

        throw new DAV\Exception\Forbidden();

    }

    /**
     * Returns the last modification date
     *
     * @return int
     */
    function getLastModified() {

        return null;

    }

    /**
     * Creates a new file under this object.
     *
     * This is currently not allowed
     *
     * @param string $filename
     * @param resource $data
     * @return void
     */
    function createFile($filename, $data = null) {

        throw new DAV\Exception\MethodNotAllowed('Creating new files in this collection is not supported');

    }

    /**
     * Creates a new directory under this object.
     *
     * This is currently not allowed.
     *
     * @param string $filename
     * @return void
     */
    function createDirectory($filename) {

        throw new DAV\Exception\MethodNotAllowed('Creating new collections in this collection is not supported');

    }

    /**
     * Returns a single calendar, by name
     *
     * @param string $name
     * @return Calendar
     */
    function getChild($name) {

        // Special nodes
        if ($name === 'inbox' && $this->caldavBackend instanceof Backend\SchedulingSupport) {
            return new Schedule\Inbox($this->caldavBackend, $this->principalInfo['uri']);
        }
        if ($name === 'outbox' && $this->caldavBackend instanceof Backend\SchedulingSupport) {
            return new Schedule\Outbox($this->principalInfo['uri']);
        }
        if ($name === 'notifications' && $this->caldavBackend instanceof Backend\NotificationSupport) {
            return new Notifications\Collection($this->caldavBackend, $this->principalInfo['uri']);
        }

        // Calendars
        foreach ($this->caldavBackend->getCalendarsForUser($this->principalInfo['uri']) as $calendar) {
            if ($calendar['uri'] === $name) {
                if ($this->caldavBackend instanceof Backend\SharingSupport) {
                    return new SharedCalendar($this->caldavBackend, $calendar);
                } else {
                    return new Calendar($this->caldavBackend, $calendar);
                }
            }
        }

        if ($this->caldavBackend instanceof Backend\SubscriptionSupport) {
            foreach ($this->caldavBackend->getSubscriptionsForUser($this->principalInfo['uri']) as $subscription) {
                if ($subscription['uri'] === $name) {
                    return new Subscriptions\Subscription($this->caldavBackend, $subscription);
                }
            }

        }

        throw new NotFound('Node with name \'' . $name . '\' could not be found');

    }

    /**
     * Checks if a calendar exists.
     *
     * @param string $name
     * @return bool
     */
    function childExists($name) {

        try {
            return !!$this->getChild($name);
        } catch (NotFound $e) {
            return false;
        }

    }

    /**
     * Returns a list of calendars
     *
     * @return array
     */
    function getChildren() {

        $calendars = $this->caldavBackend->getCalendarsForUser($this->principalInfo['uri']);
        $objs = [];
        foreach ($calendars as $calendar) {
            if ($this->caldavBackend instanceof Backend\SharingSupport) {
                $objs[] = new SharedCalendar($this->caldavBackend, $calendar);
            } else {
                $objs[] = new Calendar($this->caldavBackend, $calendar);
            }
        }

        if ($this->caldavBackend instanceof Backend\SchedulingSupport) {
            $objs[] = new Schedule\Inbox($this->caldavBackend, $this->principalInfo['uri']);
            $objs[] = new Schedule\Outbox($this->principalInfo['uri']);
        }

        // We're adding a notifications node, if it's supported by the backend.
        if ($this->caldavBackend instanceof Backend\NotificationSupport) {
            $objs[] = new Notifications\Collection($this->caldavBackend, $this->principalInfo['uri']);
        }

        // If the backend supports subscriptions, we'll add those as well,
        if ($this->caldavBackend instanceof Backend\SubscriptionSupport) {
            foreach ($this->caldavBackend->getSubscriptionsForUser($this->principalInfo['uri']) as $subscription) {
                $objs[] = new Subscriptions\Subscription($this->caldavBackend, $subscription);
            }
        }

        return $objs;

    }

    /**
     * Creates a new calendar or subscription.
     *
     * @param string $name
     * @param MkCol $mkCol
     * @throws DAV\Exception\InvalidResourceType
     * @return void
     */
    function createExtendedCollection($name, MkCol $mkCol) {

        $isCalendar = false;
        $isSubscription = false;
        foreach ($mkCol->getResourceType() as $rt) {
            switch ($rt) {
                case '{DAV:}collection' :
                case '{http://calendarserver.org/ns/}shared-owner' :
                    // ignore
                    break;
                case '{urn:ietf:params:xml:ns:caldav}calendar' :
                    $isCalendar = true;
                    break;
                case '{http://calendarserver.org/ns/}subscribed' :
                    $isSubscription = true;
                    break;
                default :
                    throw new DAV\Exception\InvalidResourceType('Unknown resourceType: ' . $rt);
            }
        }

        $properties = $mkCol->getRemainingValues();
        $mkCol->setRemainingResultCode(201);

        if ($isSubscription) {
            if (!$this->caldavBackend instanceof Backend\SubscriptionSupport) {
                throw new DAV\Exception\InvalidResourceType('This backend does not support subscriptions');
            }
            $this->caldavBackend->createSubscription($this->principalInfo['uri'], $name, $properties);

        } elseif ($isCalendar) {
            $this->caldavBackend->createCalendar($this->principalInfo['uri'], $name, $properties);

        } else {
            throw new DAV\Exception\InvalidResourceType('You can only create calendars and subscriptions in this collection');

        }

    }

    /**
     * Returns the owner of the calendar home.
     *
     * @return string
     */
    function getOwner() {

        return $this->principalInfo['uri'];

    }

    /**
     * Returns a list of ACE's for this node.
     *
     * Each ACE has the following properties:
     *   * 'privilege', a string such as {DAV:}read or {DAV:}write. These are
     *     currently the only supported privileges
     *   * 'principal', a url to the principal who owns the node
     *   * 'protected' (optional), indicating that this ACE is not allowed to
     *      be updated.
     *
     * @return array
     */
    function getACL() {

        return [
            [
                'privilege' => '{DAV:}read',
                'principal' => $this->principalInfo['uri'],
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}write',
                'principal' => $this->principalInfo['uri'],
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}read',
                'principal' => $this->principalInfo['uri'] . '/calendar-proxy-write',
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}write',
                'principal' => $this->principalInfo['uri'] . '/calendar-proxy-write',
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}read',
                'principal' => $this->principalInfo['uri'] . '/calendar-proxy-read',
                'protected' => true,
            ],

        ];

    }


    /**
     * This method is called when a user replied to a request to share.
     *
     * This method should return the url of the newly created calendar if the
     * share was accepted.
     *
     * @param string $href The sharee who is replying (often a mailto: address)
     * @param int    $status One of the SharingPlugin::STATUS_* constants
     * @param string $calendarUri The url to the calendar thats being shared
     * @param string $inReplyTo The unique id this message is a response to
     * @param string $summary A description of the reply
     * @return null|string
     */
    function shareReply($href, $status, $calendarUri, $inReplyTo, $summary = null) {

        if (!$this->caldavBackend instanceof Backend\SharingSupport) {
            throw new DAV\Exception\NotImplemented('Sharing support is not implemented by this backend.');
        }

        return $this->caldavBackend->shareReply($href, $status, $calendarUri, $inReplyTo, $summary);

    }

    /**
     * Searches through all of a users calendars and calendar objects to find
     * an object with a specific UID.
     *
     * This method should return the path to this object, relative to the
     * calendar home, so this path usually only contains two parts:
     *
     * calendarpath/objectpath.ics
     *
     * If the uid is not found, return null.
     *
     * This method should only consider * objects that the principal owns, so
     * any calendars owned by other principals that also appear in this
     * collection should be ignored.
     *
     * @param string $uid
     * @return string|null
     */
    function getCalendarObjectByUID($uid) {

        return $this->caldavBackend->getCalendarObjectByUID($this->principalInfo['uri'], $uid);

    }

}
