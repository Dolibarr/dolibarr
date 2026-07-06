<?php
/*
* File:     FolderMovedEvent.php
* Category: Event
* Author:   M. Goldenbaum
* Created:  25.11.20 22:21
* Updated:  -
*
* Description:
*  -
*/

namespace Webklex\PHPIMAP\Events;

use Webklex\PHPIMAP\Folder;

/**
 * Class FolderMovedEvent
 *
 * @package Webklex\PHPIMAP\Events
 */
class FolderMovedEvent extends Event {

    /** @var Folder $old_folder */
    public $old_folder;
    /** @var Folder $new_folder */
    public $new_folder;

    /**
     * Create a new event instance.
     * @var Folder[] $folders
     * @return void
     */
    public function __construct($folders) {
        $this->old_folder = $folders[0];
        $this->new_folder = $folders[1];
    }
}
