<?php

namespace Sabre\CalDAV\Backend;

use Sabre\CalDAV\Xml\Notification\NotificationInterface;
use Sabre\DAV;

class MockSharing extends Mock implements NotificationSupport, SharingSupport {

    private $shares = [];
    private $notifications;

    function __construct(array $calendars = [], array $calendarData = [], array $notifications = []) {

        parent::__construct($calendars, $calendarData);
        $this->notifications = $notifications;

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

        $calendars = parent::getCalendarsForUser($principalUri);
        foreach ($calendars as $k => $calendar) {

            if (isset($calendar['share-access'])) {
                continue;
            }
            if (!empty($this->shares[$calendar['id']])) {
                $calendar['share-access'] = DAV\Sharing\Plugin::ACCESS_SHAREDOWNER;
            } else {
                $calendar['share-access'] = DAV\Sharing\Plugin::ACCESS_NOTSHARED;
            }
            $calendars[$k] = $calendar;

        }
        return $calendars;

    }

    /**
     * Returns a list of notifications for a given principal url.
     *
     * The returned array should only consist of implementations of
     * Sabre\CalDAV\Notifications\INotificationType.
     *
     * @param string $principalUri
     * @return array
     */
    function getNotificationsForPrincipal($principalUri) {

        if (isset($this->notifications[$principalUri])) {
            return $this->notifications[$principalUri];
        }
        return [];

    }

    /**
     * This deletes a specific notifcation.
     *
     * This may be called by a client once it deems a notification handled.
     *
     * @param string $principalUri
     * @param NotificationInterface $notification
     * @return void
     */
    function deleteNotification($principalUri, NotificationInterface $notification) {

        foreach ($this->notifications[$principalUri] as $key => $value) {
            if ($notification === $value) {
                unset($this->notifications[$principalUri][$key]);
            }
        }

    }

    /**
     * Updates the list of shares.
     *
     * @param mixed $calendarId
     * @param \Sabre\DAV\Xml\Element\Sharee[] $sharees
     * @return void
     */
    function updateInvites($calendarId, array $sharees) {

        if (!isset($this->shares[$calendarId])) {
            $this->shares[$calendarId] = [];
        }

        foreach ($sharees as $sharee) {

            $existingKey = null;
            foreach ($this->shares[$calendarId] as $k => $existingSharee) {
                if ($sharee->href === $existingSharee->href) {
                    $existingKey = $k;
                }
            }
            // Just making sure we're not affecting an existing copy.
            $sharee = clone $sharee;
            $sharee->inviteStatus = DAV\Sharing\Plugin::INVITE_NORESPONSE;

            if ($sharee->access === DAV\Sharing\Plugin::ACCESS_NOACCESS) {
                // It's a removal
                unset($this->shares[$calendarId][$existingKey]);
            } elseif ($existingKey) {
                // It's an update
                $this->shares[$calendarId][$existingKey] = $sharee;
            } else {
                // It's an addition
                $this->shares[$calendarId][] = $sharee;
            }
        }

        // Re-numbering keys
        $this->shares[$calendarId] = array_values($this->shares[$calendarId]);

    }

    /**
     * Returns the list of people whom this calendar is shared with.
     *
     * Every item in the returned list must be a Sharee object with at
     * least the following properties set:
     *   $href
     *   $shareAccess
     *   $inviteStatus
     *
     * and optionally:
     *   $properties
     *
     * @param mixed $calendarId
     * @return \Sabre\DAV\Xml\Element\Sharee[]
     */
    function getInvites($calendarId) {

        if (!isset($this->shares[$calendarId])) {
            return [];
        }

        return $this->shares[$calendarId];

    }

    /**
     * This method is called when a user replied to a request to share.
     *
     * @param string href The sharee who is replying (often a mailto: address)
     * @param int status One of the \Sabre\DAV\Sharing\Plugin::INVITE_* constants
     * @param string $calendarUri The url to the calendar thats being shared
     * @param string $inReplyTo The unique id this message is a response to
     * @param string $summary A description of the reply
     * @return void
     */
    function shareReply($href, $status, $calendarUri, $inReplyTo, $summary = null) {

        // This operation basically doesn't do anything yet
        if ($status === DAV\Sharing\Plugin::INVITE_ACCEPTED) {
            return 'calendars/blabla/calendar';
        }

    }

    /**
     * Publishes a calendar
     *
     * @param mixed $calendarId
     * @param bool $value
     * @return void
     */
    function setPublishStatus($calendarId, $value) {

        foreach ($this->calendars as $k => $cal) {
            if ($cal['id'] === $calendarId) {
                if (!$value) {
                    unset($cal['{http://calendarserver.org/ns/}publish-url']);
                } else {
                    $cal['{http://calendarserver.org/ns/}publish-url'] = 'http://example.org/public/ ' . $calendarId . '.ics';
                }
                return;
            }
        }

        throw new DAV\Exception('Calendar with id "' . $calendarId . '" not found');

    }

}
