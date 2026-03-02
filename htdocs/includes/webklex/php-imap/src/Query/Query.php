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
use Webklex\PHPIMAP\Exceptions\AuthFailedException;
use Webklex\PHPIMAP\Exceptions\ConnectionFailedException;
use Webklex\PHPIMAP\Exceptions\EventNotFoundException;
use Webklex\PHPIMAP\Exceptions\GetMessagesFailedException;
use Webklex\PHPIMAP\Exceptions\ImapBadRequestException;
use Webklex\PHPIMAP\Exceptions\ImapServerErrorException;
use Webklex\PHPIMAP\Exceptions\InvalidMessageDateException;
use Webklex\PHPIMAP\Exceptions\MessageContentFetchingException;
use Webklex\PHPIMAP\Exceptions\MessageFlagException;
use Webklex\PHPIMAP\Exceptions\MessageHeaderFetchingException;
use Webklex\PHPIMAP\Exceptions\MessageNotFoundException;
use Webklex\PHPIMAP\Exceptions\MessageSearchValidationException;
use Webklex\PHPIMAP\Exceptions\ResponseException;
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
    protected Collection $query;

    /** @var string $raw_query */
    protected string $raw_query;

    /** @var string[] $extensions */
    protected array $extensions;

    /** @var Client $client */
    protected Client $client;

    /** @var ?int $limit */
    protected ?int $limit = null;

    /** @var int $page */
    protected int $page = 1;

    /** @var ?int $fetch_options */
    protected ?int $fetch_options = null;

    /** @var boolean $fetch_body */
    protected bool $fetch_body = true;

    /** @var boolean $fetch_flags */
    protected bool $fetch_flags = true;

    /** @var int|string $sequence */
    protected mixed $sequence = IMAP::NIL;

    /** @var string $fetch_order */
    protected string $fetch_order;

    /** @var string $date_format */
    protected string $date_format;

    /** @var bool $soft_fail */
    protected bool $soft_fail = false;

    /** @var array $errors */
    protected array $errors = [];

    /**
     * Query constructor.
     * @param Client $client
     * @param string[] $extensions
     */
    public function __construct(Client $client, array $extensions = []) {
        $this->setClient($client);
        $config = $this->client->getConfig();

        $this->sequence = $config->get('options.sequence', IMAP::ST_MSGN);
        if ($config->get('options.fetch') === IMAP::FT_PEEK) $this->leaveUnread();

        if ($config->get('options.fetch_order') === 'desc') {
            $this->fetch_order = 'desc';
        } else {
            $this->fetch_order = 'asc';
        }

        $this->date_format = $config->get('date_format', 'd M y');
        $this->soft_fail = $config->get('options.soft_fail', false);

        $this->setExtensions($extensions);
        $this->query = new Collection();
        $this->boot();
    }

    /**
     * Instance boot method for additional functionality
     */
    protected function boot(): void {
    }

    /**
     * Parse a given value
     * @param mixed $value
     *
     * @return string
     */
    protected function parse_value(mixed $value): string {
        if ($value instanceof Carbon) {
            $value = $value->format($this->date_format);
        }

        return (string)$value;
    }

    /**
     * Check if a given date is a valid carbon object and if not try to convert it
     * @param mixed $date
     *
     * @return Carbon
     * @throws MessageSearchValidationException
     */
    protected function parse_date(mixed $date): Carbon {
        if ($date instanceof Carbon) return $date;

        try {
            $date = Carbon::parse($date);
        } catch (Exception) {
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
                    if (is_numeric($statement[1]) || (
                            ($statement[0] === 'SINCE' || $statement[0] === 'BEFORE') &&
                            $this->client->getConfig()->get('options.unescaped_search_dates', false)
                    )) {
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
     * @throws AuthFailedException
     * @throws ImapBadRequestException
     * @throws ImapServerErrorException
     * @throws ResponseException
     */
    public function search(): Collection {
        $this->generate_query();

        try {
            $available_messages = $this->client->getConnection()->search([$this->getRawQuery()], $this->sequence)->validatedData();
            return new Collection($available_messages);
        } catch (RuntimeException|ConnectionFailedException $e) {
            throw new GetMessagesFailedException("failed to fetch messages", 0, $e);
        }
    }

    /**
     * Count all available messages matching the current search criteria
     *
     * @return int
     * @throws AuthFailedException
     * @throws GetMessagesFailedException
     * @throws ImapBadRequestException
     * @throws ImapServerErrorException
     * @throws ResponseException
     */
    public function count(): int {
        return $this->search()->count();
    }

    /**
     * Fetch a given id collection
     * @param Collection $available_messages
     *
     * @return array
     * @throws AuthFailedException
     * @throws ConnectionFailedException
     * @throws ImapBadRequestException
     * @throws ImapServerErrorException
     * @throws RuntimeException
     * @throws ResponseException
     */
    protected function fetch(Collection $available_messages): array {
        if ($this->fetch_order === 'desc') {
            $available_messages = $available_messages->reverse();
        }

        $uids = $available_messages->forPage($this->page, $this->limit)->toArray();
        $extensions = $this->getExtensions();
        if (empty($extensions) === false && method_exists($this->client->getConnection(), "fetch")) {
            // this polymorphic call is fine - the method exists at this point
            $extensions = $this->client->getConnection()->fetch($extensions, $uids, null, $this->sequence)->validatedData();
        }
        $flags = $this->client->getConnection()->flags($uids, $this->sequence)->validatedData();
        $headers = $this->client->getConnection()->headers($uids, "RFC822", $this->sequence)->validatedData();

        $contents = [];
        if ($this->getFetchBody()) {
            $contents = $this->client->getConnection()->content($uids, $this->client->rfc, $this->sequence)->validatedData();
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
     * @throws AuthFailedException
     * @throws ConnectionFailedException
     * @throws EventNotFoundException
     * @throws GetMessagesFailedException
     * @throws ImapBadRequestException
     * @throws ImapServerErrorException
     * @throws ReflectionException
     * @throws ResponseException
     */
    protected function make(int $uid, int $msglist, string $header, string $content, array $flags): ?Message {
        try {
            return Message::make($uid, $msglist, $this->getClient(), $header, $content, $flags, $this->getFetchOptions(), $this->sequence);
        } catch (RuntimeException|MessageFlagException|InvalidMessageDateException|MessageContentFetchingException $e) {
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
        $key = match ($message_key) {
            'number' => $message->getMessageNo(),
            'list' => $msglist,
            'uid' => $message->getUid(),
            default => $message->getMessageId(),
        };
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
            return MessageCollection::make();
        } catch (Exception $e) {
            throw new GetMessagesFailedException($e->getMessage(), 0, $e);
        }
    }

    /**
     * Populate a given id collection and receive a fully fetched message collection
     * @param Collection $available_messages
     *
     * @return MessageCollection
     * @throws AuthFailedException
     * @throws ConnectionFailedException
     * @throws EventNotFoundException
     * @throws GetMessagesFailedException
     * @throws ImapBadRequestException
     * @throws ImapServerErrorException
     * @throws ReflectionException
     * @throws RuntimeException
     * @throws ResponseException
     */
    protected function populate(Collection $available_messages): MessageCollection {
        $messages = MessageCollection::make();
        $config = $this->client->getConfig();

        $messages->total($available_messages->count());

        $message_key = $config->get('options.message_key');

        $raw_messages = $this->fetch($available_messages);

        $msglist = 0;
        foreach ($raw_messages["headers"] as $uid => $header) {
            $content = $raw_messages["contents"][$uid] ?? "";
            $flag = $raw_messages["flags"][$uid] ?? [];
            $extensions = $raw_messages["extensions"][$uid] ?? [];

            $message = $this->make($uid, $msglist, $header, $content, $flag);
            foreach ($extensions as $key => $extension) {
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
     * @throws AuthFailedException
     * @throws GetMessagesFailedException
     * @throws ImapBadRequestException
     * @throws ImapServerErrorException
     * @throws ResponseException
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
     * @throws AuthFailedException
     * @throws ConnectionFailedException
     * @throws EventNotFoundException
     * @throws GetMessagesFailedException
     * @throws ImapBadRequestException
     * @throws ImapServerErrorException
     * @throws ReflectionException
     * @throws RuntimeException
     * @throws ResponseException
     */
    public function chunked(callable $callback, int $chunk_size = 10, int $start_chunk = 1): void {
        $start_chunk = max($start_chunk,1);
        $chunk_size = max($chunk_size,1);
        $skipped_messages_count = $chunk_size * ($start_chunk-1);

        $available_messages = $this->search();
        $available_messages_count = max($available_messages->count() - $skipped_messages_count,0);

        if ($available_messages_count > 0) {
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
     * @param null $page The current page you are on (e.g. 0, 1, 2, ...) use `null` to enable auto mode
     * @param string $page_name The page name / uri parameter used for the generated links and the auto mode
     *
     * @return LengthAwarePaginator
     * @throws AuthFailedException
     * @throws GetMessagesFailedException
     * @throws ImapBadRequestException
     * @throws ImapServerErrorException
     * @throws ResponseException
     */
    public function paginate(int $per_page = 5, $page = null, string $page_name = 'imap_page'): LengthAwarePaginator {
        if ($page === null && isset($_GET[$page_name]) && $_GET[$page_name] > 0) {
            $this->page = intval($_GET[$page_name]);
        } elseif ($page > 0) {
            $this->page = (int)$page;
        }

        $this->limit = $per_page;

        return $this->get()->paginate($per_page, $this->page, $page_name, true);
    }

    /**
     * Get a new Message instance
     * @param int $uid
     * @param null $msglist
     * @param null $sequence
     *
     * @return Message
     * @throws AuthFailedException
     * @throws ConnectionFailedException
     * @throws EventNotFoundException
     * @throws ImapBadRequestException
     * @throws ImapServerErrorException
     * @throws InvalidMessageDateException
     * @throws MessageContentFetchingException
     * @throws MessageFlagException
     * @throws MessageHeaderFetchingException
     * @throws RuntimeException
     * @throws ResponseException
     */
    public function getMessage(int $uid, $msglist = null, $sequence = null): Message {
        return new Message($uid, $msglist, $this->getClient(), $this->getFetchOptions(), $this->getFetchBody(), $this->getFetchFlags(), $sequence ?: $this->sequence);
    }

    /**
     * Get a message by its message number
     * @param $msgn
     * @param null $msglist
     *
     * @return Message
     * @throws AuthFailedException
     * @throws ConnectionFailedException
     * @throws EventNotFoundException
     * @throws ImapBadRequestException
     * @throws ImapServerErrorException
     * @throws InvalidMessageDateException
     * @throws MessageContentFetchingException
     * @throws MessageFlagException
     * @throws MessageHeaderFetchingException
     * @throws RuntimeException
     * @throws ResponseException
     */
    public function getMessageByMsgn($msgn, $msglist = null): Message {
        return $this->getMessage($msgn, $msglist, IMAP::ST_MSGN);
    }

    /**
     * Get a message by its uid
     * @param $uid
     *
     * @return Message
     * @throws AuthFailedException
     * @throws ConnectionFailedException
     * @throws EventNotFoundException
     * @throws ImapBadRequestException
     * @throws ImapServerErrorException
     * @throws InvalidMessageDateException
     * @throws MessageContentFetchingException
     * @throws MessageFlagException
     * @throws MessageHeaderFetchingException
     * @throws RuntimeException
     * @throws ResponseException
     */
    public function getMessageByUid($uid): Message {
        return $this->getMessage($uid, null, IMAP::ST_UID);
    }

    /**
     * Filter all available uids by a given closure and get a curated list of messages
     * @param callable $closure
     *
     * @return MessageCollection
     * @throws AuthFailedException
     * @throws ConnectionFailedException
     * @throws GetMessagesFailedException
     * @throws ImapBadRequestException
     * @throws ImapServerErrorException
     * @throws MessageNotFoundException
     * @throws RuntimeException
     * @throws ResponseException
     */
    public function filter(callable $closure): MessageCollection {
        $connection = $this->getClient()->getConnection();

        $uids = $connection->getUid()->validatedData();
        $available_messages = new Collection();
        if (is_array($uids)) {
            foreach ($uids as $id) {
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
     * @throws AuthFailedException
     * @throws ConnectionFailedException
     * @throws GetMessagesFailedException
     * @throws ImapBadRequestException
     * @throws ImapServerErrorException
     * @throws MessageNotFoundException
     * @throws RuntimeException
     * @throws ResponseException
     */
    public function getByUidGreaterOrEqual(int $uid): MessageCollection {
        return $this->filter(function($id) use ($uid) {
            return $id >= $uid;
        });
    }

    /**
     * Get all messages with an uid greater than a given UID
     * @param int $uid
     *
     * @return MessageCollection
     * @throws AuthFailedException
     * @throws ConnectionFailedException
     * @throws GetMessagesFailedException
     * @throws ImapBadRequestException
     * @throws ImapServerErrorException
     * @throws MessageNotFoundException
     * @throws RuntimeException
     * @throws ResponseException
     */
    public function getByUidGreater(int $uid): MessageCollection {
        return $this->filter(function($id) use ($uid) {
            return $id > $uid;
        });
    }

    /**
     * Get all messages with an uid lower than a given UID
     * @param int $uid
     *
     * @return MessageCollection
     * @throws AuthFailedException
     * @throws ConnectionFailedException
     * @throws GetMessagesFailedException
     * @throws ImapBadRequestException
     * @throws ImapServerErrorException
     * @throws MessageNotFoundException
     * @throws RuntimeException
     * @throws ResponseException
     */
    public function getByUidLower(int $uid): MessageCollection {
        return $this->filter(function($id) use ($uid) {
            return $id < $uid;
        });
    }

    /**
     * Get all messages with an uid lower or equal to a given UID
     * @param int $uid
     *
     * @return MessageCollection
     * @throws AuthFailedException
     * @throws ConnectionFailedException
     * @throws GetMessagesFailedException
     * @throws ImapBadRequestException
     * @throws ImapServerErrorException
     * @throws MessageNotFoundException
     * @throws RuntimeException
     * @throws ResponseException
     */
    public function getByUidLowerOrEqual(int $uid): MessageCollection {
        return $this->filter(function($id) use ($uid) {
            return $id <= $uid;
        });
    }

    /**
     * Get all messages with an uid greater than a given UID
     * @param int $uid
     *
     * @return MessageCollection
     * @throws AuthFailedException
     * @throws ConnectionFailedException
     * @throws GetMessagesFailedException
     * @throws ImapBadRequestException
     * @throws ImapServerErrorException
     * @throws MessageNotFoundException
     * @throws RuntimeException
     * @throws ResponseException
     */
    public function getByUidLowerThan(int $uid): MessageCollection {
        return $this->filter(function($id) use ($uid) {
            return $id < $uid;
        });
    }

    /**
     * Don't mark messages as read when fetching
     *
     * @return $this
     */
    public function leaveUnread(): static {
        $this->setFetchOptions(IMAP::FT_PEEK);

        return $this;
    }

    /**
     * Mark all messages as read when fetching
     *
     * @return $this
     */
    public function markAsRead(): static {
        $this->setFetchOptions(IMAP::FT_UID);

        return $this;
    }

    /**
     * Set the sequence type
     * @param int $sequence
     *
     * @return $this
     */
    public function setSequence(int $sequence): static {
        $this->sequence = $sequence;

        return $this;
    }

    /**
     * Get the sequence type
     *
     * @return int|string
     */
    public function getSequence(): int|string {
        return $this->sequence;
    }

    /**
     * @return Client
     * @throws AuthFailedException
     * @throws ConnectionFailedException
     * @throws ImapBadRequestException
     * @throws ImapServerErrorException
     * @throws RuntimeException
     * @throws ResponseException
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
    public function limit(int $limit, int $page = 1): static {
        if ($page >= 1) $this->page = $page;
        $this->limit = $limit;

        return $this;
    }

    /**
     * Get the current query collection
     *
     * @return Collection
     */
    public function getQuery(): Collection {
        return $this->query;
    }

    /**
     * Set all query parameters
     * @param array $query
     *
     * @return $this
     */
    public function setQuery(array $query): static {
        $this->query = new Collection($query);
        return $this;
    }

    /**
     * Get the raw query
     *
     * @return string
     */
    public function getRawQuery(): string {
        return $this->raw_query;
    }

    /**
     * Set the raw query
     * @param string $raw_query
     *
     * @return $this
     */
    public function setRawQuery(string $raw_query): static {
        $this->raw_query = $raw_query;
        return $this;
    }

    /**
     * Get all applied extensions
     *
     * @return string[]
     */
    public function getExtensions(): array {
        return $this->extensions;
    }

    /**
     * Set all extensions that should be used
     * @param string[] $extensions
     *
     * @return $this
     */
    public function setExtensions(array $extensions): static {
        $this->extensions = $extensions;
        if (count($this->extensions) > 0) {
            if (in_array("UID", $this->extensions) === false) {
                $this->extensions[] = "UID";
            }
        }
        return $this;
    }

    /**
     * Set the client instance
     * @param Client $client
     *
     * @return $this
     */
    public function setClient(Client $client): static {
        $this->client = $client;
        return $this;
    }

    /**
     * Get the set fetch limit
     *
     * @return ?int
     */
    public function getLimit(): ?int {
        return $this->limit;
    }

    /**
     * Set the fetch limit
     * @param int $limit
     *
     * @return $this
     */
    public function setLimit(int $limit): static {
        $this->limit = $limit <= 0 ? null : $limit;
        return $this;
    }

    /**
     * Get the set page
     *
     * @return int
     */
    public function getPage(): int {
        return $this->page;
    }

    /**
     * Set the page
     * @param int $page
     *
     * @return $this
     */
    public function setPage(int $page): static {
        $this->page = $page;
        return $this;
    }

    /**
     * Set the fetch option flag
     * @param int $fetch_options
     *
     * @return $this
     */
    public function setFetchOptions(int $fetch_options): static {
        $this->fetch_options = $fetch_options;
        return $this;
    }

    /**
     * Set the fetch option flag
     * @param int $fetch_options
     *
     * @return $this
     */
    public function fetchOptions(int $fetch_options): static {
        return $this->setFetchOptions($fetch_options);
    }

    /**
     * Get the fetch option flag
     *
     * @return ?int
     */
    public function getFetchOptions(): ?int {
        return $this->fetch_options;
    }

    /**
     * Get the fetch body flag
     *
     * @return boolean
     */
    public function getFetchBody(): bool {
        return $this->fetch_body;
    }

    /**
     * Set the fetch body flag
     * @param boolean $fetch_body
     *
     * @return $this
     */
    public function setFetchBody(bool $fetch_body): static {
        $this->fetch_body = $fetch_body;
        return $this;
    }

    /**
     * Set the fetch body flag
     * @param boolean $fetch_body
     *
     * @return $this
     */
    public function fetchBody(bool $fetch_body): static {
        return $this->setFetchBody($fetch_body);
    }

    /**
     * Get the fetch body flag
     *
     * @return bool
     */
    public function getFetchFlags(): bool {
        return $this->fetch_flags;
    }

    /**
     * Set the fetch flag
     * @param bool $fetch_flags
     *
     * @return $this
     */
    public function setFetchFlags(bool $fetch_flags): static {
        $this->fetch_flags = $fetch_flags;
        return $this;
    }

    /**
     * Set the fetch order
     * @param string $fetch_order
     *
     * @return $this
     */
    public function setFetchOrder(string $fetch_order): static {
        $fetch_order = strtolower($fetch_order);

        if (in_array($fetch_order, ['asc', 'desc'])) {
            $this->fetch_order = $fetch_order;
        }

        return $this;
    }

    /**
     * Set the fetch order
     * @param string $fetch_order
     *
     * @return $this
     */
    public function fetchOrder(string $fetch_order): static {
        return $this->setFetchOrder($fetch_order);
    }

    /**
     * Get the fetch order
     *
     * @return string
     */
    public function getFetchOrder(): string {
        return $this->fetch_order;
    }

    /**
     * Set the fetch order to ascending
     *
     * @return $this
     */
    public function setFetchOrderAsc(): static {
        return $this->setFetchOrder('asc');
    }

    /**
     * Set the fetch order to ascending
     *
     * @return $this
     */
    public function fetchOrderAsc(): static {
        return $this->setFetchOrderAsc();
    }

    /**
     * Set the fetch order to descending
     *
     * @return $this
     */
    public function setFetchOrderDesc(): static {
        return $this->setFetchOrder('desc');
    }

    /**
     * Set the fetch order to descending
     *
     * @return $this
     */
    public function fetchOrderDesc(): static {
        return $this->setFetchOrderDesc();
    }

    /**
     * Set soft fail mode
     * @var boolean $state
     *
     * @return $this
     */
    public function softFail(bool $state = true): static {
        return $this->setSoftFail($state);
    }

    /**
     * Set soft fail mode
     *
     * @var boolean $state
     * @return $this
     */
    public function setSoftFail(bool $state = true): static {
        $this->soft_fail = $state;

        return $this;
    }

    /**
     * Get soft fail mode
     *
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
    protected function handleException(int $uid): void {
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
    protected function setError(int $uid, Exception $error): void {
        $this->errors[$uid] = $error;
    }

    /**
     * Check if there are any errors / exceptions present
     * @var ?integer $uid
     *
     * @return boolean
     */
    public function hasErrors(?int $uid = null): bool {
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
     * @var integer $uid
     *
     * @return Exception|null
     */
    public function error(int $uid): ?Exception {
        return $this->getError($uid);
    }

    /**
     * Get a specific error / exception
     * @var integer $uid
     *
     * @return ?Exception
     */
    public function getError(int $uid): ?Exception {
        if ($this->hasError($uid)) {
            return $this->errors[$uid];
        }
        return null;
    }
}
