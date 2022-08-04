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

    /** @var string[] $extensions */
    protected $extensions;

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

    /** @var int|string $sequence */
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
     * @param string[] $extensions
     */
    public function __construct(Client $client, array $extensions = []) {
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

        $this->setExtensions($extensions);
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
    protected function parse_value($value): string {
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
    protected function parse_date($date): Carbon {
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
    public function generate_query(): string {
        $query = '';
        $this->query->each(function($statement) use (&$query) {
            if (count($statement) == 1) {
                $query .= $statement[0];
            } else {
                if ($statement[1] === null) {
                    $query .= $statement[0];
                } else {
                    if (is_numeric($statement[1])) {
                        $query .= $statement[0] . ' ' . $statement[1];
                    } else {
                        $query .= $statement[0] . ' "' . $statement[1] . '"';
                    }
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
    protected function search(): Collection {
        $this->generate_query();

        try {
            $available_messages = $this->client->getConnection()->search([$this->getRawQuery()], $this->sequence);
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
    public function count(): int {
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
    protected function fetch(Collection $available_messages): array {
        if ($this->fetch_order === 'desc') {
            $available_messages = $available_messages->reverse();
        }

        $uids = $available_messages->forPage($this->page, $this->limit)->toArray();
        $extensions = [];
        if (empty($this->getExtensions()) === false) {
            $extensions = $this->client->getConnection()->fetch($this->getExtensions(), $uids, null, $this->sequence);
        }
        $flags = $this->client->getConnection()->flags($uids, $this->sequence);
        $headers = $this->client->getConnection()->headers($uids, "RFC822", $this->sequence);

        $contents = [];
        if ($this->getFetchBody()) {
            $contents = $this->client->getConnection()->content($uids, "RFC822", $this->sequence);
        }

        return [
            "uids"       => $uids,
            "flags"      => $flags,
            "headers"    => $headers,
            "contents"   => $contents,
            "extensions" => $extensions,
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
    protected function make(int $uid, int $msglist, string $header, string $content, array $flags) {
        try {
            return Message::make($uid, $msglist, $this->getClient(), $header, $content, $flags, $this->getFetchOptions(), $this->sequence);
        } catch (MessageNotFoundException $e) {
            $this->setError($uid, $e);
        } catch (RuntimeException $e) {
            $this->setError($uid, $e);
        } catch (MessageFlagException $e) {
            $this->setError($uid, $e);
        } catch (InvalidMessageDateException $e) {
            $this->setError($uid, $e);
        } catch (MessageContentFetchingException $e) {
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
    protected function getMessageKey(string $message_key, int $msglist, Message $message): string {
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
     * Curates a given collection aof messages
     * @param Collection $available_messages
     *
     * @return MessageCollection
     * @throws GetMessagesFailedException
     */
    public function curate_messages(Collection $available_messages): MessageCollection {
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
    protected function populate(Collection $available_messages): MessageCollection {
        $messages = MessageCollection::make([]);

        $messages->total($available_messages->count());

        $message_key = ClientManager::get('options.message_key');

        $raw_messages = $this->fetch($available_messages);

        $msglist = 0;
        foreach ($raw_messages["headers"] as $uid => $header) {
            $content = $raw_messages["contents"][$uid] ?? "";
            $flag = $raw_messages["flags"][$uid] ?? [];
            $extensions = $raw_messages["extensions"][$uid] ?? [];

            $message = $this->make($uid, $msglist, $header, $content, $flag);
            foreach($extensions as $key => $extension) {
                $message->getHeader()->set($key, $extension);
            }
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
    public function get(): MessageCollection {
        return $this->curate_messages($this->search());
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
    public function chunked(callable $callback, int $chunk_size = 10, int $start_chunk = 1) {
        $available_messages = $this->search();
        if (($available_messages_count = $available_messages->count()) > 0) {
            $old_limit = $this->limit;
            $old_page = $this->page;

            $this->limit = $chunk_size;
            $this->page = $start_chunk;
            $handled_messages_count = 0;
            do {
                $messages = $this->populate($available_messages);
                $handled_messages_count += $messages->count();
                $callback($messages, $this->page);
                $this->page++;
            } while ($handled_messages_count < $available_messages_count);
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
    public function paginate(int $per_page = 5, $page = null, string $page_name = 'imap_page'): LengthAwarePaginator {
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
     * @param int|string|null $sequence
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
    public function getMessage(int $uid, $msglist = null, $sequence = null): Message {
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
    public function getMessageByMsgn($msgn, $msglist = null): Message {
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
    public function getMessageByUid($uid): Message {
        return $this->getMessage($uid, null, IMAP::ST_UID);
    }

    /**
     * Filter all available uids by a given closure and get a curated list of messages
     * @param callable $closure
     *
     * @return MessageCollection
     * @throws ConnectionFailedException
     * @throws GetMessagesFailedException
     * @throws MessageNotFoundException
     */
    public function filter(callable $closure): MessageCollection {
        $connection = $this->getClient()->getConnection();

        $uids = $connection->getUid();
        $available_messages = new Collection();
        if (is_array($uids)) {
            foreach ($uids as $id){
                if ($closure($id)) {
                    $available_messages->push($id);
                }
            }
        }

        return $this->curate_messages($available_messages);
    }

    /**
     * Get all messages with an uid greater or equal to a given UID
     * @param int $uid
     *
     * @return MessageCollection
     * @throws ConnectionFailedException
     * @throws GetMessagesFailedException
     * @throws MessageNotFoundException
     */
    public function getByUidGreaterOrEqual(int $uid): MessageCollection {
        return $this->filter(function($id) use($uid){
            return $id >= $uid;
        });
    }

    /**
     * Get all messages with an uid greater than a given UID
     * @param int $uid
     *
     * @return MessageCollection
     * @throws ConnectionFailedException
     * @throws GetMessagesFailedException
     * @throws MessageNotFoundException
     */
    public function getByUidGreater(int $uid): MessageCollection {
        return $this->filter(function($id) use($uid){
            return $id > $uid;
        });
    }

    /**
     * Get all messages with an uid lower than a given UID
     * @param int $uid
     *
     * @return MessageCollection
     * @throws ConnectionFailedException
     * @throws GetMessagesFailedException
     * @throws MessageNotFoundException
     */
    public function getByUidLower(int $uid): MessageCollection {
        return $this->filter(function($id) use($uid){
            return $id < $uid;
        });
    }

    /**
     * Get all messages with an uid lower or equal to a given UID
     * @param int $uid
     *
     * @return MessageCollection
     * @throws ConnectionFailedException
     * @throws GetMessagesFailedException
     * @throws MessageNotFoundException
     */
    public function getByUidLowerOrEqual(int $uid): MessageCollection {
        return $this->filter(function($id) use($uid){
            return $id <= $uid;
        });
    }

    /**
     * Get all messages with an uid greater than a given UID
     * @param int $uid
     *
     * @return MessageCollection
     * @throws ConnectionFailedException
     * @throws GetMessagesFailedException
     * @throws MessageNotFoundException
     */
    public function getByUidLowerThan(int $uid): MessageCollection {
        return $this->filter(function($id) use($uid){
            return $id < $uid;
        });
    }

    /**
     * Don't mark messages as read when fetching
     *
     * @return $this
     */
    public function leaveUnread(): Query {
        $this->setFetchOptions(IMAP::FT_PEEK);

        return $this;
    }

    /**
     * Mark all messages as read when fetching
     *
     * @return $this
     */
    public function markAsRead(): Query {
        $this->setFetchOptions(IMAP::FT_UID);

        return $this;
    }

    /**
     * Set the sequence type
     * @param int $sequence
     *
     * @return $this
     */
    public function setSequence(int $sequence): Query {
        $this->sequence = $sequence;

        return $this;
    }

    /**
     * Get the sequence type
     *
     * @return int|string
     */
    public function getSequence() {
        return $this->sequence;
    }

    /**
     * @return Client
     * @throws ConnectionFailedException
     */
    public function getClient(): Client {
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
    public function limit(int $limit, int $page = 1): Query {
        if ($page >= 1) $this->page = $page;
        $this->limit = $limit;

        return $this;
    }

    /**
     * @return Collection
     */
    public function getQuery(): Collection {
        return $this->query;
    }

    /**
     * @param array $query
     * @return Query
     */
    public function setQuery(array $query): Query {
        $this->query = new Collection($query);
        return $this;
    }

    /**
     * @return string
     */
    public function getRawQuery(): string {
        return $this->raw_query;
    }

    /**
     * @param string $raw_query
     * @return Query
     */
    public function setRawQuery(string $raw_query): Query {
        $this->raw_query = $raw_query;
        return $this;
    }

    /**
     * @return string[]
     */
    public function getExtensions(): array {
        return $this->extensions;
    }

    /**
     * @param string[] $extensions
     * @return Query
     */
    public function setExtensions(array $extensions): Query {
        $this->extensions = $extensions;
        if (count($this->extensions) > 0) {
            if (in_array("UID", $this->extensions) === false) {
                $this->extensions[] = "UID";
            }
        }
        return $this;
    }

    /**
     * @param Client $client
     * @return Query
     */
    public function setClient(Client $client): Query {
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
    public function setLimit(int $limit): Query {
        $this->limit = $limit <= 0 ? null : $limit;
        return $this;
    }

    /**
     * @return int
     */
    public function getPage(): int {
        return $this->page;
    }

    /**
     * @param int $page
     * @return Query
     */
    public function setPage(int $page): Query {
        $this->page = $page;
        return $this;
    }

    /**
     * @param int $fetch_options
     * @return Query
     */
    public function setFetchOptions(int $fetch_options): Query {
        $this->fetch_options = $fetch_options;
        return $this;
    }

    /**
     * @param int $fetch_options
     * @return Query
     */
    public function fetchOptions(int $fetch_options): Query {
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
    public function setFetchBody(bool $fetch_body): Query {
        $this->fetch_body = $fetch_body;
        return $this;
    }

    /**
     * @param boolean $fetch_body
     * @return Query
     */
    public function fetchBody(bool $fetch_body): Query {
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
    public function setFetchFlags(int $fetch_flags): Query {
        $this->fetch_flags = $fetch_flags;
        return $this;
    }

    /**
     * @param string $fetch_order
     * @return Query
     */
    public function setFetchOrder(string $fetch_order): Query {
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
    public function fetchOrder(string $fetch_order): Query {
        return $this->setFetchOrder($fetch_order);
    }

    /**
     * @return string
     */
    public function getFetchOrder(): string {
        return $this->fetch_order;
    }

    /**
     * @return Query
     */
    public function setFetchOrderAsc(): Query {
        return $this->setFetchOrder('asc');
    }

    /**
     * @return Query
     */
    public function fetchOrderAsc(): Query {
        return $this->setFetchOrderAsc();
    }

    /**
     * @return Query
     */
    public function setFetchOrderDesc(): Query {
        return $this->setFetchOrder('desc');
    }

    /**
     * @return Query
     */
    public function fetchOrderDesc(): Query {
        return $this->setFetchOrderDesc();
    }

    /**
     * @return Query
     * @var boolean $state
     *
     */
    public function softFail(bool $state = true): Query {
        return $this->setSoftFail($state);
    }

    /**
     * @return Query
     * @var boolean $state
     *
     */
    public function setSoftFail(bool $state = true): Query {
        $this->soft_fail = $state;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getSoftFail(): bool {
        return $this->soft_fail;
    }

    /**
     * Handle the exception for a given uid
     * @param integer $uid
     *
     * @throws GetMessagesFailedException
     */
    protected function handleException(int $uid) {
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
    protected function setError(int $uid, Exception $error) {
        $this->errors[$uid] = $error;
    }

    /**
     * Check if there are any errors / exceptions present
     * @return boolean
     * @var integer|null $uid
     *
     */
    public function hasErrors($uid = null): bool {
        if ($uid !== null) {
            return $this->hasError($uid);
        }
        return count($this->errors) > 0;
    }

    /**
     * Check if there is an error / exception present
     * @return boolean
     * @var integer $uid
     *
     */
    public function hasError(int $uid): bool {
        return isset($this->errors[$uid]);
    }

    /**
     * Get all available errors / exceptions
     *
     * @return array
     */
    public function errors(): array {
        return $this->getErrors();
    }

    /**
     * Get all available errors / exceptions
     *
     * @return array
     */
    public function getErrors(): array {
        return $this->errors;
    }

    /**
     * Get a specific error / exception
     * @return Exception|null
     * @var integer $uid
     *
     */
    public function error(int $uid) {
        return $this->getError($uid);
    }

    /**
     * Get a specific error / exception
     * @return Exception|null
     * @var integer $uid
     *
     */
    public function getError(int $uid) {
        if ($this->hasError($uid)) {
            return $this->errors[$uid];
        }
        return null;
    }
}
