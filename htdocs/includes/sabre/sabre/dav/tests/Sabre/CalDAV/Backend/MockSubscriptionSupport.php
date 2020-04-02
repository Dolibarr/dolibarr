<?php

namespace Sabre\CalDAV\Backend;

use Sabre\CalDAV;
use Sabre\DAV;

/**
 * This is a mock CalDAV backend that supports subscriptions.
 *
 * All data is retained in memory temporarily. It's primary purpose is
 * unit-tests.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class MockSubscriptionSupport extends Mock implements SubscriptionSupport {

    /**
     * Subscription list
     *
     * @var array
     */
    protected $subs = [];

    /**
     * Returns a list of subscriptions for a principal.
     *
     * Every subscription is an array with the following keys:
     *  * id, a unique id that will be used by other functions to modify the
     *    subscription. This can be the same as the uri or a database key.
     *  * uri. This is just the 'base uri' or 'filename' of the subscription.
     *  * principaluri. The owner of the subscription. Almost always the same as
     *    principalUri passed to this method.
     *  * source. Url to the actual feed
     *
     * Furthermore, all the subscription info must be returned too:
     *
     * 1. {DAV:}displayname
     * 2. {http://apple.com/ns/ical/}refreshrate
     * 3. {http://calendarserver.org/ns/}subscribed-strip-todos (omit if todos
     *    should not be stripped).
     * 4. {http://calendarserver.org/ns/}subscribed-strip-alarms (omit if alarms
     *    should not be stripped).
     * 5. {http://calendarserver.org/ns/}subscribed-strip-attachments (omit if
     *    attachments should not be stripped).
     * 7. {http://apple.com/ns/ical/}calendar-color
     * 8. {http://apple.com/ns/ical/}calendar-order
     *
     * @param string $principalUri
     * @return array
     */
    function getSubscriptionsForUser($principalUri) {

        if (isset($this->subs[$principalUri])) {
            return $this->subs[$principalUri];
        }
        return [];

    }

    /**
     * Creates a new subscription for a principal.
     *
     * If the creation was a success, an id must be returned that can be used to reference
     * this subscription in other methods, such as updateSubscription.
     *
     * @param string $principalUri
     * @param string $uri
     * @param array $properties
     * @return mixed
     */
    function createSubscription($principalUri, $uri, array $properties) {

        $properties['uri'] = $uri;
        $properties['principaluri'] = $principalUri;
        $properties['source'] = $properties['{http://calendarserver.org/ns/}source']->getHref();

        if (!isset($this->subs[$principalUri])) {
            $this->subs[$principalUri] = [];
        }

        $id = [$principalUri, count($this->subs[$principalUri]) + 1];

        $properties['id'] = $id;

        $this->subs[$principalUri][] = array_merge($properties, [
            'id' => $id,
        ]);

        return $id;

    }

    /**
     * Updates a subscription
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
     * @param mixed $subscriptionId
     * @param \Sabre\DAV\PropPatch $propPatch
     * @return void
     */
    function updateSubscription($subscriptionId, DAV\PropPatch $propPatch) {

        $found = null;
        foreach ($this->subs[$subscriptionId[0]] as &$sub) {

            if ($sub['id'][1] === $subscriptionId[1]) {
                $found = & $sub;
                break;
            }

        }

        if (!$found) return;

        $propPatch->handleRemaining(function($mutations) use (&$found) {
            foreach ($mutations as $k => $v) {
                $found[$k] = $v;
            }
            return true;
        });

    }

    /**
     * Deletes a subscription
     *
     * @param mixed $subscriptionId
     * @return void
     */
    function deleteSubscription($subscriptionId) {

        foreach ($this->subs[$subscriptionId[0]] as $index => $sub) {

            if ($sub['id'][1] === $subscriptionId[1]) {
                unset($this->subs[$subscriptionId[0]][$index]);
                return true;
            }

        }

        return false;

    }

}
