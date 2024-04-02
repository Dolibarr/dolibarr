<?php
/*
* File:     Query.php
* Category: -
* Author:   M. Goldenbaum
* Created:  21.07.18 18:54
* Updated:  -
*
* Description:
*  -
*/

namespace Webklex\PHPIMAP\Query;

use Carbon\Carbon;
use Exception;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use ReflectionException;
use Webklex\PHPIMAP\Client;
use Webklex\PHPIMAP\ClientManager;
use Webklex\PHPIMAP\Exceptions\ConnectionFailedException;
use Webklex\PHPIMAP\Exceptions\EventNotFoundException;
use Webklex\PHPIMAP\Exceptions\GetMessagesFailedException;
use Webklex\PHPIMAP\Exceptions\InvalidMessageDateException;
use Webklex\PHPIMAP\Exceptions\MessageContentFetchingException;
use Webklex\PHPIMAP\Exceptions\MessageFlagException;
use Webklex\PHPIMAP\Exceptions\MessageHeaderFetchingException;
use Webklex\PHPIMAP\Exceptions\MessageNotFoundException;
use Webklex\PHPIMAP\Exceptions\MessageSearchValidationException;
use Webklex\PHPIMAP\Exceptions\RuntimeException;
use Webklex\PHPIMAP\IMAP;
use Webklex\PHPIMAP\Message;
use Webklex\PHPIMAP\Support\MessageCollection;

/**
 * Class Query
 *
 * @package Webklex\PHPIMAP\Query
 */
class Query {

    /** @var Collection $query */
    protected $query;

    /** @var string $raw_query */
    protected $raw_query;

    /** @var string $charset */
    protected $charset;

    /** @var Client $client */
    protected $client;

    /** @var int $limit */
    protected $limit = null;

    /** @var int $page */
    protected $page = 1;

    /** @var int $fetch_options */
    protected $fetch_options = null;

    /** @var int $fetch_body */
    protected $fetch_body = true;

    /** @var int $fetch_flags */
    protected $fetch_flags = true;

    /** @var int $sequence */
    protected $sequence = IMAP::NIL;

    /** @var string $fetch_order */
    protected $fetch_order;

    /** @var string $date_format */
    protected $date_format;

    /** @var bool $soft_fail */
    protected $soft_fail = false;

    /** @var array $errors */
    protected $errors = [];

    /**
     * Query constructor.
     * @param Client $client
     * @param string $charset
     */
    public function __construct(Client $client, $charset = 'UTF-8') {
        $this->setClient($client);

        $this->sequence = ClientManager::get('options.sequence', IMAP::ST_MSGN);
        if (ClientManager::get('options.fetch') === IMAP::FT_PEEK) $this->leaveUnread();

        if (ClientManager::get('options.fetch_order') === 'desc') {
            $this->fetch_order = 'desc';
        } else {
            $this->fetch_order = 'asc';
        }

        $this->date_format = ClientManager::get('date_format', 'd M y');
        $this->soft_fail = ClientManager::get('options.soft_fail', false);

        $this->charset = $charset;
        $this->query = new Collection();
        $this->boot();
    }

    /**
     * Instance boot method for additional functionality
     */
    protected function boot() {
    }

    /**
     * Parse a given value
     * @param mixed $value
     *
     * @return string
     */
    protected function parse_value($value) {
        switch (true) {
            case $value instanceof Carbon:
                $value = $value->format($this->date_format);
                break;
        }

        return (string)$value;
    }

    /**
     * Check if a given date is a valid carbon object and if not try to convert it
     * @param string|Carbon $date
     *
     * @return Carbon
     * @throws MessageSearchValidationException
     */
    protected function parse_date($date) {
        if ($date instanceof Carbon) return $date;

        try {
            $date = Carbon::parse($date);
        } catch (Exception $e) {
            throw new MessageSearchValidationException();
        }

        return $date;
    }

    /**
     * Get the raw IMAP search query
     *
     * @return string
     */
    public function generate_query() {
        $query = '';
        $this->query->each(function($statement) use (&$query) {
            if (count($statement) == 1) {
                $query .= $statement[0];
            } else {
                if ($statement[1] === null) {
                    $query .= $statement[0];
                } else {
                    $query .= $statement[0] . ' "' . $statement[1] . '"';
                }
            }
            $query .= ' ';

        });

        $this->raw_query = trim($query);

        return $this->raw_query;
    }

    /**
     * Perform an imap search request
     *
     * @return Collection
     * @throws GetMessagesFailedException
     */
    protected function search() {
        $this->generate_query();

        try {
            $available_messages = $this->client->getConnection()->search([$this->getRawQuery()], $this->sequence == IMAP::ST_UID);
            return $available_messages !== false ? new Collection($available_messages) : new Collection();
        } catch (RuntimeException $e) {
            throw new GetMessagesFailedException("failed to fetch messages", 0, $e);
        } catch (ConnectionFailedException $e) {
            throw new GetMessagesFailedException("failed to fetch messages", 0, $e);
        }
    }

    /**
     * Count all available messages matching the current search criteria
     *
     * @return int
     * @throws GetMessagesFailedException
     */
    public function count() {
        return $this->search()->count();
    }

    /**
     * Fetch a given id collection
     * @param Collection $available_messages
     *
     * @return array
     * @throws ConnectionFailedException
     * @throws RuntimeException
     */
    protected function fetch($available_messages) {
        if ($this->fetch_order === 'desc') {
            $available_messages = $available_messages->reverse();
        }

        $uids = $available_messages->forPage($this->page, $this->limit)->toArray();
        $flags = $this->client->getConnection()->flags($uids, $this->sequence == IMAP::ST_UID);
        $headers = $this->client->getConnection()->headers($uids, "RFC822", $this->sequence == IMAP::ST_UID);

        $contents = [];
        if ($this->getFetchBody()) {
            $contents = $this->client->getConnection()->content($uids, "RFC822", $this->sequence == IMAP::ST_UID);
        }

        return [
            "uids"     => $uids,
            "flags"    => $flags,
            "headers"  => $headers,
            "contents" => $contents,
        ];
    }

    /**
     * Make a new message from given raw components
     * @param integer $uid
     * @param integer $msglist
     * @param string $header
     * @param string $content
     * @param array $flags
     *
     * @return Message|null
     * @throws ConnectionFailedException
     * @throws EventNotFoundException
     * @throws GetMessagesFailedException
     * @throws ReflectionException
     */
    protected function make($uid, $msglist, $header, $content, $flags){
        try {
            return Message::make($uid, $msglist, $this->getClient(), $header, $content, $flags, $this->getFetchOptions(), $this->sequence);
        }catch (MessageNotFoundException $e) {
            $this->setError($uid, $e);
        }catch (RuntimeException $e) {
            $this->setError($uid, $e);
        }catch (MessageFlagException $e) {
            $this->setError($uid, $e);
        }catch (InvalidMessageDateException $e) {
            $this->setError($uid, $e);
        }catch (MessageContentFetchingException $e) {
            $this->setError($uid, $e);
        }

        $this->handleException($uid);

        return null;
    }

    /**
     * Get the message key for a given message
     * @param string $message_key
     * @param integer $msglist
     * @param Message $message
     *
     * @return string
     */
    protected function getMessageKey($message_key, $msglist, $message){
        switch ($message_key) {
            case 'number':
                $key = $message->getMessageNo();
                break;
            case 'list':
                $key = $msglist;
                break;
            case 'uid':
                $key = $message->getUid();
                break;
            default:
                $key = $message->getMessageId();
                break;
        }
        return (string)$key;
    }

    /**
     * Populate a given id collection and receive a fully fetched message collection
     * @param Collection $available_messages
     *
     * @return MessageCollection
     * @throws ConnectionFailedException
     * @throws EventNotFoundException
     * @throws GetMessagesFailedException
     * @throws ReflectionException
     * @throws RuntimeException
     */
    protected function populate($available_messages) {
        $messages = MessageCollection::make([]);

        $messages->total($available_messages->count());

        $message_key = ClientManager::get('options.message_key');

        $raw_messages = $this->fetch($available_messages);

        $msglist = 0;
        foreach ($raw_messages["headers"] as $uid => $header) {
            $content = isset($raw_messages["contents"][$uid]) ? $raw_messages["contents"][$uid] : "";
            $flag = isset($raw_messages["flags"][$uid]) ? $raw_messages["flags"][$uid] : [];

            $message = $this->make($uid, $msglist, $header, $content, $flag);
            if ($message !== null) {
                $key = $this->getMessageKey($message_key, $msglist, $message);
                $messages->put("$key", $message);
            }
            $msglist++;
        }

        return $messages;
    }

    /**
     * Fetch the current query and return all found messages
     *
     * @return MessageCollection
     * @throws GetMessagesFailedException
     */
    public function get() {
        $available_messages = $this->search();

        try {
            if ($available_messages->count() > 0) {
                return $this->populate($available_messages);
            }
            return MessageCollection::make([]);
        } catch (Exception $e) {
            throw new GetMessagesFailedException($e->getMessage(), 0, $e);
        }
    }

    /**
     * Fetch the current query as chunked requests
     * @param callable $callback
     * @param int $chunk_size
     * @param int $start_chunk
     *
     * @throws ConnectionFailedException
     * @throws EventNotFoundException
     * @throws GetMessagesFailedException
     * @throws ReflectionException
     * @throws RuntimeException
     */
    public function chunked($callback, $chunk_size = 10, $start_chunk = 1) {
        $available_messages = $this->search();
        if (($available_messages_count = $available_messages->count()) > 0) {
            $old_limit = $this->limit;
            $old_page = $this->page;

            $this->limit = $chunk_size;
            $this->page = $start_chunk;
            do {
                $messages = $this->populate($available_messages);
                $callback($messages, $this->page);
                $this->page++;
            } while ($this->limit * $this->page <= $available_messages_count);
            $this->limit = $old_limit;
            $this->page = $old_page;
        }
    }

    /**
     * Paginate the current query
     * @param int $per_page Results you which to receive per page
     * @param int|null $page The current page you are on (e.g. 0, 1, 2, ...) use `null` to enable auto mode
     * @param string $page_name The page name / uri parameter used for the generated links and the auto mode
     *
     * @return LengthAwarePaginator
     * @throws GetMessagesFailedException
     */
    public function paginate($per_page = 5, $page = null, $page_name = 'imap_page') {
        if (
            $page === null
            && isset($_GET[$page_name])
            && $_GET[$page_name] > 0
        ) {
            $this->page = intval($_GET[$page_name]);
        } elseif ($page > 0) {
            $this->page = $page;
        }

        $this->limit = $per_page;

        return $this->get()->paginate($per_page, $this->page, $page_name, true);
    }

    /**
     * Get a new Message instance
     * @param int $uid
     * @param int|null $msglist
     * @param int|null $sequence
     *
     * @return Message
     * @throws ConnectionFailedException
     * @throws RuntimeException
     * @throws InvalidMessageDateException
     * @throws MessageContentFetchingException
     * @throws MessageHeaderFetchingException
     * @throws EventNotFoundException
     * @throws MessageFlagException
     * @throws MessageNotFoundException
     */
    public function getMessage($uid, $msglist = null, $sequence = null) {
        return new Message($uid, $msglist, $this->getClient(), $this->getFetchOptions(), $this->getFetchBody(), $this->getFetchFlags(), $sequence ? $sequence : $this->sequence);
    }

    /**
     * Get a message by its message number
     * @param $msgn
     * @param int|null $msglist
     *
     * @return Message
     * @throws ConnectionFailedException
     * @throws InvalidMessageDateException
     * @throws MessageContentFetchingException
     * @throws MessageHeaderFetchingException
     * @throws RuntimeException
     * @throws EventNotFoundException
     * @throws MessageFlagException
     * @throws MessageNotFoundException
     */
    public function getMessageByMsgn($msgn, $msglist = null) {
        return $this->getMessage($msgn, $msglist, IMAP::ST_MSGN);
    }

    /**
     * Get a message by its uid
     * @param $uid
     *
     * @return Message
     * @throws ConnectionFailedException
     * @throws InvalidMessageDateException
     * @throws MessageContentFetchingException
     * @throws MessageHeaderFetchingException
     * @throws RuntimeException
     * @throws EventNotFoundException
     * @throws MessageFlagException
     * @throws MessageNotFoundException
     */
    public function getMessageByUid($uid) {
        return $this->getMessage($uid, null, IMAP::ST_UID);
    }

    /**
     * Don't mark messages as read when fetching
     *
     * @return $this
     */
    public function leaveUnread() {
        $this->setFetchOptions(IMAP::FT_PEEK);

        return $this;
    }

    /**
     * Mark all messages as read when fetching
     *
     * @return $this
     */
    public function markAsRead() {
        $this->setFetchOptions(IMAP::FT_UID);

        return $this;
    }

    /**
     * Set the sequence type
     * @param int $sequence
     *
     * @return $this
     */
    public function setSequence($sequence) {
        $this->sequence = $sequence != IMAP::ST_MSGN ? IMAP::ST_UID : $sequence;

        return $this;
    }

    /**
     * @return Client
     * @throws ConnectionFailedException
     */
    public function getClient() {
        $this->client->checkConnection();
        return $this->client;
    }

    /**
     * Set the limit and page for the current query
     * @param int $limit
     * @param int $page
     *
     * @return $this
     */
    public function limit($limit, $page = 1) {
        if ($page >= 1) $this->page = $page;
        $this->limit = $limit;

        return $this;
    }

    /**
     * @return Collection
     */
    public function getQuery() {
        return $this->query;
    }

    /**
     * @param array $query
     * @return Query
     */
    public function setQuery($query) {
        $this->query = new Collection($query);
        return $this;
    }

    /**
     * @return string
     */
    public function getRawQuery() {
        return $this->raw_query;
    }

    /**
     * @param string $raw_query
     * @return Query
     */
    public function setRawQuery($raw_query) {
        $this->raw_query = $raw_query;
        return $this;
    }

    /**
     * @return string
     */
    public function getCharset() {
        return $this->charset;
    }

    /**
     * @param string $charset
     * @return Query
     */
    public function setCharset($charset) {
        $this->charset = $charset;
        return $this;
    }

    /**
     * @param Client $client
     * @return Query
     */
    public function setClient(Client $client) {
        $this->client = $client;
        return $this;
    }

    /**
     * @return int
     */
    public function getLimit() {
        return $this->limit;
    }

    /**
     * @param int $limit
     * @return Query
     */
    public function setLimit($limit) {
        $this->limit = $limit <= 0 ? null : $limit;
        return $this;
    }

    /**
     * @return int
     */
    public function getPage() {
        return $this->page;
    }

    /**
     * @param int $page
     * @return Query
     */
    public function setPage($page) {
        $this->page = $page;
        return $this;
    }

    /**
     * @param boolean $fetch_options
     * @return Query
     */
    public function setFetchOptions($fetch_options) {
        $this->fetch_options = $fetch_options;
        return $this;
    }

    /**
     * @param boolean $fetch_options
     * @return Query
     */
    public function fetchOptions($fetch_options) {
        return $this->setFetchOptions($fetch_options);
    }

    /**
     * @return int
     */
    public function getFetchOptions() {
        return $this->fetch_options;
    }

    /**
     * @return boolean
     */
    public function getFetchBody() {
        return $this->fetch_body;
    }

    /**
     * @param boolean $fetch_body
     * @return Query
     */
    public function setFetchBody($fetch_body) {
        $this->fetch_body = $fetch_body;
        return $this;
    }

    /**
     * @param boolean $fetch_body
     * @return Query
     */
    public function fetchBody($fetch_body) {
        return $this->setFetchBody($fetch_body);
    }

    /**
     * @return int
     */
    public function getFetchFlags() {
        return $this->fetch_flags;
    }

    /**
     * @param int $fetch_flags
     * @return Query
     */
    public function setFetchFlags($fetch_flags) {
        $this->fetch_flags = $fetch_flags;
        return $this;
    }

    /**
     * @param string $fetch_order
     * @return Query
     */
    public function setFetchOrder($fetch_order) {
        $fetch_order = strtolower($fetch_order);

        if (in_array($fetch_order, ['asc', 'desc'])) {
            $this->fetch_order = $fetch_order;
        }

        return $this;
    }

    /**
     * @param string $fetch_order
     * @return Query
     */
    public function fetchOrder($fetch_order) {
        return $this->setFetchOrder($fetch_order);
    }

    /**
     * @return string
     */
    public function getFetchOrder() {
        return $this->fetch_order;
    }

    /**
     * @return Query
     */
    public function setFetchOrderAsc() {
        return $this->setFetchOrder('asc');
    }

    /**
     * @return Query
     */
    public function fetchOrderAsc() {
        return $this->setFetchOrderAsc();
    }

    /**
     * @return Query
     */
    public function setFetchOrderDesc() {
        return $this->setFetchOrder('desc');
    }

    /**
     * @return Query
     */
    public function fetchOrderDesc() {
        return $this->setFetchOrderDesc();
    }

    /**
     * @var boolean $state
     *
     * @return Query
     */
    public function softFail($state = true) {
        return $this->setSoftFail($state);
    }

    /**
     * @var boolean $state
     *
     * @return Query
     */
    public function setSoftFail($state = true) {
        $this->soft_fail = $state;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getSoftFail() {
        return $this->soft_fail;
    }

    /**
     * Handle the exception for a given uid
     * @param integer $uid
     *
     * @throws GetMessagesFailedException
     */
    protected function handleException($uid) {
        if ($this->soft_fail === false && $this->hasError($uid)) {
            $error = $this->getError($uid);
            throw new GetMessagesFailedException($error->getMessage(), 0, $error);
        }
    }

    /**
     * Add a new error to the error holder
     * @param integer $uid
     * @param Exception $error
     */
    protected function setError($uid, $error) {
        $this->errors[$uid] = $error;
    }

    /**
     * Check if there are any errors / exceptions present
     * @var integer|null $uid
     *
     * @return boolean
     */
    public function hasErrors($uid = null){
        if ($uid !== null) {
            return $this->hasError($uid);
        }
        return count($this->errors) > 0;
    }

    /**
     * Check if there is an error / exception present
     * @var integer $uid
     *
     * @return boolean
     */
    public function hasError($uid){
        return isset($this->errors[$uid]);
    }

    /**
     * Get all available errors / exceptions
     *
     * @return array
     */
    public function errors(){
        return $this->getErrors();
    }

    /**
     * Get all available errors / exceptions
     *
     * @return array
     */
    public function getErrors(){
        return $this->errors;
    }

    /**
     * Get a specific error / exception
     * @var integer $uid
     *
     * @return Exception|null
     */
    public function error($uid){
        return $this->getError($uid);
    }

    /**
     * Get a specific error / exception
     * @var integer $uid
     *
     * @return Exception|null
     */
    public function getError($uid){
        if ($this->hasError($uid)) {
            return $this->errors[$uid];
        }
        return null;
    }
}
