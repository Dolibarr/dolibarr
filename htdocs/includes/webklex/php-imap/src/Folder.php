<?php
/*
* File:     Folder.php
* Category: -
* Author:   M. Goldenbaum
* Created:  19.01.17 22:21
* Updated:  -
*
* Description:
*  -
*/

namespace Webklex\PHPIMAP;

use Carbon\Carbon;
use Webklex\PHPIMAP\Exceptions\ConnectionFailedException;
use Webklex\PHPIMAP\Query\WhereQuery;
use Webklex\PHPIMAP\Support\FolderCollection;
use Webklex\PHPIMAP\Traits\HasEvents;

/**
 * Class Folder
 *
 * @package Webklex\PHPIMAP
 */
class Folder {
    use HasEvents;

    /**
     * Client instance
     *
     * @var Client
     */
    protected $client;

    /**
     * Folder full path
     *
     * @var string
     */
    public $path;

    /**
     * Folder name
     *
     * @var string
     */
    public $name;

    /**
     * Folder fullname
     *
     * @var string
     */
    public $full_name;

    /**
     * Children folders
     *
     * @var FolderCollection|array
     */
    public $children = [];

    /**
     * Delimiter for folder
     *
     * @var string
     */
    public $delimiter;

    /**
     * Indicates if folder can't containg any "children".
     * CreateFolder won't work on this folder.
     *
     * @var boolean
     */
    public $no_inferiors;

    /**
     * Indicates if folder is only container, not a mailbox - you can't open it.
     *
     * @var boolean
     */
    public $no_select;

    /**
     * Indicates if folder is marked. This means that it may contain new messages since the last time it was checked.
     * Not provided by all IMAP servers.
     *
     * @var boolean
     */
    public $marked;

    /**
     * Indicates if folder containg any "children".
     * Not provided by all IMAP servers.
     *
     * @var boolean
     */
    public $has_children;

    /**
     * Indicates if folder refers to other.
     * Not provided by all IMAP servers.
     *
     * @var boolean
     */
    public $referral;

    /**
     * Folder constructor.
     * @param Client $client
     * @param string $folder_name
     * @param string $delimiter
     * @param string[] $attributes
     */
    public function __construct(Client $client, $folder_name, $delimiter, $attributes) {
        $this->client = $client;

        $this->events["message"] = $client->getDefaultEvents("message");
        $this->events["folder"] = $client->getDefaultEvents("folder");

        $this->setDelimiter($delimiter);
        $this->path      = $folder_name;
        $this->full_name  = $this->decodeName($folder_name);
        $this->name      = $this->getSimpleName($this->delimiter, $this->full_name);

        $this->parseAttributes($attributes);
    }

    /**
     * Get a new search query instance
     * @param string $charset
     *
     * @return WhereQuery
     * @throws Exceptions\ConnectionFailedException
     * @throws Exceptions\RuntimeException
     */
    public function query($charset = 'UTF-8'){
        $this->getClient()->checkConnection();
        $this->getClient()->openFolder($this->path);

        return new WhereQuery($this->getClient(), $charset);
    }

    /**
     * @inheritdoc self::query($charset = 'UTF-8')
     * @throws Exceptions\ConnectionFailedException
     * @throws Exceptions\RuntimeException
     */
    public function search($charset = 'UTF-8'){
        return $this->query($charset);
    }

    /**
     * @inheritdoc self::query($charset = 'UTF-8')
     * @throws Exceptions\ConnectionFailedException
     * @throws Exceptions\RuntimeException
     */
    public function messages($charset = 'UTF-8'){
        return $this->query($charset);
    }

    /**
     * Determine if folder has children.
     *
     * @return bool
     */
    public function hasChildren() {
        return $this->has_children;
    }

    /**
     * Set children.
     * @param FolderCollection|array $children
     *
     * @return self
     */
    public function setChildren($children = []) {
        $this->children = $children;

        return $this;
    }

    /**
     * Decode name.
     * It converts UTF7-IMAP encoding to UTF-8.
     * @param $name
     *
     * @return mixed|string
     */
    protected function decodeName($name) {
        return mb_convert_encoding($name, "UTF-8", "UTF7-IMAP");
    }

    /**
     * Get simple name (without parent folders).
     * @param $delimiter
     * @param $full_name
     *
     * @return mixed
     */
    protected function getSimpleName($delimiter, $full_name) {
        $arr = explode($delimiter, $full_name);

        return end($arr);
    }

    /**
     * Parse attributes and set it to object properties.
     * @param $attributes
     */
    protected function parseAttributes($attributes) {
        $this->no_inferiors = in_array('\NoInferiors', $attributes) ? true : false;
        $this->no_select    = in_array('\NoSelect', $attributes) ? true : false;
        $this->marked       = in_array('\Marked', $attributes) ? true : false;
        $this->referral     = in_array('\Referral', $attributes) ? true : false;
        $this->has_children = in_array('\HasChildren', $attributes) ? true : false;
    }

    /**
     * Move or rename the current folder
     * @param string $new_name
     * @param boolean $expunge
     *
     * @return bool
     * @throws ConnectionFailedException
     * @throws Exceptions\EventNotFoundException
     * @throws Exceptions\FolderFetchingException
     * @throws Exceptions\RuntimeException
     */
    public function move($new_name, $expunge = true) {
        $this->client->checkConnection();
        $status = $this->client->getConnection()->renameFolder($this->full_name, $new_name);
        if($expunge) $this->client->expunge();

        $folder = $this->client->getFolder($new_name);
        $event = $this->getEvent("folder", "moved");
        $event::dispatch($this, $folder);

        return $status;
    }

    /**
     * Get a message overview
     * @param string|null $sequence uid sequence
     *
     * @return array
     * @throws ConnectionFailedException
     * @throws Exceptions\InvalidMessageDateException
     * @throws Exceptions\MessageNotFoundException
     * @throws Exceptions\RuntimeException
     */
    public function overview($sequence = null){
        $this->client->openFolder($this->path);
        $sequence = $sequence === null ? "1:*" : $sequence;
        $uid = ClientManager::get('options.sequence', IMAP::ST_MSGN) == IMAP::ST_UID;
        return $this->client->getConnection()->overview($sequence, $uid);
    }

    /**
     * Append a string message to the current mailbox
     * @param string $message
     * @param string $options
     * @param string $internal_date
     *
     * @return bool
     * @throws Exceptions\ConnectionFailedException
     * @throws Exceptions\RuntimeException
     */
    public function appendMessage($message, $options = null, $internal_date = null) {
        /**
         * Check if $internal_date is parsed. If it is null it should not be set. Otherwise the message can't be stored.
         * If this parameter is set, it will set the INTERNALDATE on the appended message. The parameter should be a
         * date string that conforms to the rfc2060 specifications for a date_time value or be a Carbon object.
         */

        if ($internal_date != null) {
            if ($internal_date instanceof Carbon){
                $internal_date = $internal_date->format('d-M-Y H:i:s O');
            }
        }

        return $this->client->getConnection()->appendMessage($this->full_name, $message, $options, $internal_date);
    }

    /**
     * Rename the current folder
     * @param string $new_name
     * @param boolean $expunge
     *
     * @return bool
     * @throws ConnectionFailedException
     * @throws Exceptions\EventNotFoundException
     * @throws Exceptions\FolderFetchingException
     * @throws Exceptions\RuntimeException
     */
    public function rename($new_name, $expunge = true) {
        return $this->move($new_name, $expunge);
    }

    /**
     * Delete the current folder
     * @param boolean $expunge
     *
     * @return bool
     * @throws Exceptions\ConnectionFailedException
     * @throws Exceptions\RuntimeException
     * @throws Exceptions\EventNotFoundException
     */
    public function delete($expunge = true) {
        $status = $this->client->getConnection()->deleteFolder($this->path);
        if($expunge) $this->client->expunge();

        $event = $this->getEvent("folder", "deleted");
        $event::dispatch($this);

        return $status;
    }

    /**
     * Subscribe the current folder
     *
     * @return bool
     * @throws Exceptions\ConnectionFailedException
     * @throws Exceptions\RuntimeException
     */
    public function subscribe() {
        $this->client->openFolder($this->path);
        return $this->client->getConnection()->subscribeFolder($this->path);
    }

    /**
     * Unsubscribe the current folder
     *
     * @return bool
     * @throws Exceptions\ConnectionFailedException
     * @throws Exceptions\RuntimeException
     */
    public function unsubscribe() {
        $this->client->openFolder($this->path);
        return $this->client->getConnection()->unsubscribeFolder($this->path);
    }

    /**
     * Idle the current connection
     * @param callable $callback
     * @param integer $timeout max 1740 seconds - recommended by rfc2177 §3
     * @param boolean $auto_reconnect try to reconnect on connection close
     *
     * @throws ConnectionFailedException
     * @throws Exceptions\InvalidMessageDateException
     * @throws Exceptions\MessageContentFetchingException
     * @throws Exceptions\MessageHeaderFetchingException
     * @throws Exceptions\RuntimeException
     * @throws Exceptions\EventNotFoundException
     * @throws Exceptions\MessageFlagException
     * @throws Exceptions\MessageNotFoundException
     */
    public function idle(callable $callback, $timeout = 1200, $auto_reconnect = false) {
        $this->client->getConnection()->setConnectionTimeout($timeout);

        $this->client->reconnect();
        $this->client->openFolder($this->path, true);
        $connection = $this->client->getConnection();

        $sequence = ClientManager::get('options.sequence', IMAP::ST_MSGN);
        $connection->idle();

        while (true) {
            try {
                $line = $connection->nextLine();
                if (($pos = strpos($line, "EXISTS")) !== false) {
                    $msgn = (int) substr($line, 2, $pos -2);
                    $connection->done();

                    $this->client->openFolder($this->path, true);
                    $message = $this->query()->getMessageByMsgn($msgn);
                    $message->setSequence($sequence);
                    $callback($message);

                    $event = $this->getEvent("message", "new");
                    $event::dispatch($message);

                    $connection->idle();
                }
            }catch (Exceptions\RuntimeException $e) {
                if(strpos($e->getMessage(), "connection closed") === false) {
                    throw $e;
                }
                if ($auto_reconnect === true) {
                    $this->client->reconnect();
                    $this->client->openFolder($this->path, true);

                    $connection = $this->client->getConnection();
                    $connection->idle();
                }
            }
        }
    }

    /**
     * Get folder status information
     *
     * @return array|bool
     * @throws Exceptions\ConnectionFailedException
     * @throws Exceptions\RuntimeException
     */
    public function getStatus() {
        return $this->examine();
    }

    /**
     * Examine the current folder
     *
     * @return array
     * @throws Exceptions\ConnectionFailedException
     * @throws Exceptions\RuntimeException
     */
    public function examine() {
        return $this->client->getConnection()->examineFolder($this->path);
    }

    /**
     * Get the current Client instance
     *
     * @return Client
     */
    public function getClient() {
        return $this->client;
    }

    /**
     * Set the delimiter
     * @param $delimiter
     */
    public function setDelimiter($delimiter){
        if(in_array($delimiter, [null, '', ' ', false]) === true) {
            $delimiter = ClientManager::get('options.delimiter', '/');
        }

        $this->delimiter = $delimiter;
    }
}
