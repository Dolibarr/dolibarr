<?php
/*
* File: custom_message_mask.php
* Category: Example
* Author: M.Goldenbaum
* Created: 14.03.19 18:47
* Updated: -
*
* Description:
*  -
*/

class CustomMessageMask extends \Webklex\PHPIMAP\Support\Masks\MessageMask {

    /**
     * New custom method which can be called through a mask
     * @return string
     */
    public function token(){
        return implode('-', [$this->message_id, $this->uid, $this->message_no]);
    }

    /**
     * Get number of message attachments
     * @return integer
     */
    public function getAttachmentCount() {
        return $this->getAttachments()->count();
    }

}

/** @var \Webklex\PHPIMAP\Client $client */
$cm = new \Webklex\PHPIMAP\ClientManager('path/to/config/imap.php');
$client = $cm->account('default');
$client->connect();

/** @var \Webklex\PHPIMAP\Folder $folder */
$folder = $client->getFolder('INBOX');

/** @var \Webklex\PHPIMAP\Message $message */
$message = $folder->query()->limit(1)->get()->first();

/** @var CustomMessageMask $masked_message */
$masked_message = $message->mask(CustomMessageMask::class);

echo 'Token for uid ['.$masked_message->uid.']: '.$masked_message->token().' @atms:'.$masked_message->getAttachmentCount();

$masked_message->setFlag('seen');

