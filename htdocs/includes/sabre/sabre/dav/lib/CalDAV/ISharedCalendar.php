<?php

namespace Sabre\CalDAV;

use Sabre\DAV\Sharing\ISharedNode;

/**
 * This interface represents a Calendar that is shared by a different user.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
interface ISharedCalendar extends ISharedNode {

    /**
     * Marks this calendar as published.
     *
     * Publishing a calendar should automatically create a read-only, public,
     * subscribable calendar.
     *
     * @param bool $value
     * @return void
     */
    function setPublishStatus($value);
}
