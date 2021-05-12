<?php

namespace Stripe;

/**
 * Class SubscriptionScheduleRevision
 *
 * @property string $id
 * @property string $object
 * @property int $created
 * @property mixed $invoice_settings
 * @property boolean $livemode
 * @property mixed $phases
 * @property string $previous_revision
 * @property string $renewal_behavior
 * @property mixed $renewal_interval
 * @property string $schedule
 *
 * @package Stripe
 */
class SubscriptionScheduleRevision extends ApiResource
{

    const OBJECT_NAME = "subscription_schedule_revision";

    use ApiOperations\All;
    use ApiOperations\Retrieve;

    /**
     * @return string The API URL for this Subscription Schedule Revision.
     */
    public function instanceUrl()
    {
        $id = $this['id'];
        $schedule = $this['schedule'];
        if (!$id) {
            throw new Error\InvalidRequest(
                "Could not determine which URL to request: " .
                "class instance has invalid ID: $id",
                null
            );
        }
        $id = Util\Util::utf8($id);
        $schedule = Util\Util::utf8($schedule);

        $base = SubscriptionSchedule::classUrl();
        $scheduleExtn = urlencode($schedule);
        $extn = urlencode($id);
        return "$base/$scheduleExtn/revisions/$extn";
    }

    /**
     * @param array|string $_id
     * @param array|string|null $_opts
     *
     * @throws \Stripe\Error\InvalidRequest
     */
    public static function retrieve($_id, $_opts = null)
    {
        $msg = "Subscription Schedule Revisions cannot be accessed without a Subscription Schedule ID. " .
               "Retrieve one using \$schedule->retrieveRevision('revision_id') instead.";
        throw new Error\InvalidRequest($msg, null);
    }

    /**
     * @param array|string $_id
     * @param array|string|null $_opts
     *
     * @throws \Stripe\Error\InvalidRequest
     */
    public static function all($params = null, $opts = null)
    {
        $msg = "Subscription Schedule Revisions cannot be listed without a Subscription Schedule ID. " .
               "List those using \$schedule->allRevisions('revision_id') instead.";
        throw new Error\InvalidRequest($msg, null);
    }
}
