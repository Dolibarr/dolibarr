<?php
/*
* File:     Message.php
* Category: -
* Author:   M. Goldenbaum
* Created:  19.01.17 22:21
* Updated:  -
*
* Description:
*  -
*/

namespace Webklex\PHPIMAP;

use Exception;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionException;
use Webklex\PHPIMAP\Decoder\Decoder;
use Webklex\PHPIMAP\Decoder\DecoderInterface;
use Webklex\PHPIMAP\Exceptions\AuthFailedException;
use Webklex\PHPIMAP\Exceptions\ConnectionFailedException;
use Webklex\PHPIMAP\Exceptions\DecoderNotFoundException;
use Webklex\PHPIMAP\Exceptions\EventNotFoundException;
use Webklex\PHPIMAP\Exceptions\FolderFetchingException;
use Webklex\PHPIMAP\Exceptions\GetMessagesFailedException;
use Webklex\PHPIMAP\Exceptions\ImapBadRequestException;
use Webklex\PHPIMAP\Exceptions\ImapServerErrorException;
use Webklex\PHPIMAP\Exceptions\InvalidMessageDateException;
use Webklex\PHPIMAP\Exceptions\MaskNotFoundException;
use Webklex\PHPIMAP\Exceptions\MessageContentFetchingException;
use Webklex\PHPIMAP\Exceptions\MessageFlagException;
use Webklex\PHPIMAP\Exceptions\MessageHeaderFetchingException;
use Webklex\PHPIMAP\Exceptions\MessageNotFoundException;
use Webklex\PHPIMAP\Exceptions\MessageSizeFetchingException;
use Webklex\PHPIMAP\Exceptions\MethodNotFoundException;
use Webklex\PHPIMAP\Exceptions\ResponseException;
use Webklex\PHPIMAP\Exceptions\RuntimeException;
use Webklex\PHPIMAP\Support\AttachmentCollection;
use Webklex\PHPIMAP\Support\FlagCollection;
use Webklex\PHPIMAP\Support\Masks\MessageMask;
use Webklex\PHPIMAP\Support\MessageCollection;
use Webklex\PHPIMAP\Traits\HasEvents;

/**
 * Class Message
 *
 * @package Webklex\PHPIMAP
 *
 * @property integer $msglist
 * @property integer $uid
 * @property integer $msgn
 * @property integer $size
 * @property Attribute $subject
 * @property Attribute $message_id
 * @property Attribute $message_no
 * @property Attribute $references
 * @property Attribute $date
 * @property Attribute $from
 * @property Attribute $to
 * @property Attribute $cc
 * @property Attribute $bcc
 * @property Attribute $reply_to
 * @property Attribute $in_reply_to
 * @property Attribute $sender
 *
 * @method integer getMsglist()
 * @method integer setMsglist($msglist)
 * @method integer getUid()
 * @method integer getMsgn()
 * @method integer getSize()
 * @method Attribute getPriority()
 * @method Attribute getSubject()
 * @method Attribute getMessageId()
 * @method Attribute getMessageNo()
 * @method Attribute getReferences()
 * @method Attribute getDate()
 * @method Attribute getFrom()
 * @method Attribute getTo()
 * @method Attribute getCc()
 * @method Attribute getBcc()
 * @method Attribute getReplyTo()
 * @method Attribute getInReplyTo()
 * @method Attribute getSender()
 */
class Message {
    use HasEvents;

    /**
     * Client instance
     *
     * @var ?Client
     */
    private ?Client $client;

    /**
     * Default mask
     *
     * @var string $mask
     */
    protected string $mask = MessageMask::class;

    /**
     * Used options
     *
     * @var array $options
     */
    protected array $options = [];

    /**
     * All library configs
     *
     * @var Config $config
     */
    protected Config $config;

    /**
     * Decoder instance
     *
     * @var DecoderInterface $decoder
     */
    protected DecoderInterface $decoder;

    /**
     * Attribute holder
     *
     * @var Attribute[]|array $attributes
     */
    protected array $attributes = [];

    /**
     * The message folder path
     *
     * @var string $folder_path
     */
    protected string $folder_path;

    /**
     * Fetch body options
     *
     * @var ?integer
     */
    public ?int $fetch_options = null;

    /**
     * @var integer
     */
    protected int $sequence = IMAP::NIL;

    /**
     * Fetch body options
     *
     * @var bool
     */
    public bool $fetch_body = true;

    /**
     * Fetch flags options
     *
     * @var bool
     */
    public bool $fetch_flags = true;

    /**
     * @var ?Header $header
     */
    public ?Header $header = null;

    /**
     * Raw message body
     *
     * @var string $raw_body
     */
    protected string $raw_body = "";

    /**
     * Message structure
     *
     * @var ?Structure $structure
     */
    protected ?Structure $structure = null;

    /**
     * Message body components
     *
     * @var array $bodies
     */
    public array $bodies = [];

    /** @var AttachmentCollection $attachments */
    public AttachmentCollection $attachments;

    /** @var FlagCollection $flags */
    public FlagCollection $flags;

    /**
     * A list of all available and supported flags
     *
     * @var ?array $available_flags
     */
    private ?array $available_flags = null;

    /**
     * Message constructor.
     * @param integer $uid
     * @param integer|null $msglist
     * @param Client $client
     * @param integer|null $fetch_options
     * @param boolean $fetch_body
     * @param boolean $fetch_flags
     * @param integer|null $sequence
     *
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
    public function __construct(int $uid, ?int $msglist, Client $client, ?int $fetch_options = null, bool $fetch_body = false, bool $fetch_flags = false, ?int $sequence = null) {
        $this->boot($client->getConfig());

        $default_mask = $client->getDefaultMessageMask();
        if ($default_mask != null) {
            $this->mask = $default_mask;
        }
        $this->events["message"] = $client->getDefaultEvents("message");
        $this->events["flag"] = $client->getDefaultEvents("flag");

        $this->folder_path = $client->getFolderPath();

        $this->setSequence($sequence);
        $this->setFetchOption($fetch_options);
        $this->setFetchBodyOption($fetch_body);
        $this->setFetchFlagsOption($fetch_flags);

        $this->client = $client;
        $this->client->openFolder($this->folder_path);

        $this->setSequenceId($uid, $msglist);

        if ($this->fetch_options == IMAP::FT_PEEK) {
            $this->parseFlags();
        }

        $this->parseHeader();

        if ($this->getFetchBodyOption() === true) {
            $this->parseBody();
        }

        if ($this->getFetchFlagsOption() === true && $this->fetch_options !== IMAP::FT_PEEK) {
            $this->parseFlags();
        }
    }

    /**
     * Create a new instance without fetching the message header and providing them raw instead
     * @param int $uid
     * @param int|null $msglist
     * @param Client $client
     * @param string $raw_header
     * @param string $raw_body
     * @param array $raw_flags
     * @param null $fetch_options
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
     * @throws ReflectionException
     * @throws RuntimeException
     * @throws ResponseException
     */
    public static function make(int $uid, ?int $msglist, Client $client, string $raw_header, string $raw_body, array $raw_flags, $fetch_options = null, $sequence = null): Message {
        $reflection = new ReflectionClass(self::class);
        /** @var Message $instance */
        $instance = $reflection->newInstanceWithoutConstructor();
        $instance->boot($client->getConfig());

        $default_mask = $client->getDefaultMessageMask();
        if ($default_mask != null) {
            $instance->setMask($default_mask);
        }
        $instance->setEvents([
                                 "message" => $client->getDefaultEvents("message"),
                                 "flag"    => $client->getDefaultEvents("flag"),
                             ]);
        $instance->setFolderPath($client->getFolderPath());
        $instance->setSequence($sequence);
        $instance->setFetchOption($fetch_options);

        $instance->setClient($client);
        $instance->setSequenceId($uid, $msglist);

        $instance->parseRawHeader($raw_header);
        $instance->parseRawFlags($raw_flags);
        $instance->parseRawBody($raw_body);
        $instance->peek();

        return $instance;
    }

    /**
     * Create a new message instance by reading and loading a file or remote location
     * @param string $filename
     * @param ?Config $config
     *
     * @return Message
     * @throws AuthFailedException
     * @throws ConnectionFailedException
     * @throws ImapBadRequestException
     * @throws ImapServerErrorException
     * @throws InvalidMessageDateException
     * @throws MaskNotFoundException
     * @throws MessageContentFetchingException
     * @throws ReflectionException
     * @throws ResponseException
     * @throws RuntimeException
     */
    public static function fromFile(string $filename, ?Config $config = null): Message {
        $blob = file_get_contents($filename);
        if ($blob === false) {
            throw new RuntimeException("Unable to read file");
        }
        return self::fromString($blob, $config);
    }

    /**
     * Create a new message instance by reading and loading a string
     * @param string $blob
     * @param ?Config $config
     *
     * @return Message
     * @throws AuthFailedException
     * @throws ConnectionFailedException
     * @throws ImapBadRequestException
     * @throws ImapServerErrorException
     * @throws InvalidMessageDateException
     * @throws MaskNotFoundException
     * @throws MessageContentFetchingException
     * @throws ReflectionException
     * @throws ResponseException
     * @throws RuntimeException
     */
    public static function fromString(string $blob, ?Config $config = null): Message {
        $reflection = new ReflectionClass(self::class);
        /** @var Message $instance */
        $instance = $reflection->newInstanceWithoutConstructor();
        $instance->boot($config);

        $default_mask  = $instance->getConfig()->getMask("message");
        if($default_mask != ""){
            $instance->setMask($default_mask);
        }else{
            throw new MaskNotFoundException("Unknown message mask provided");
        }

        if(!str_contains($blob, "\r\n")){
            $blob = str_replace("\n", "\r\n", $blob);
        }
        $raw_header = substr($blob, 0, strpos($blob, "\r\n\r\n"));
        $raw_body = substr($blob, strlen($raw_header)+4);

        $instance->parseRawHeader($raw_header);
        $instance->parseRawBody($raw_body);

        $instance->setUid(0);

        return $instance;
    }

    /**
     * Boot a new instance
     * @param ?Config $config
     * @throws DecoderNotFoundException
     */
    public function boot(?Config $config = null): void {
        $this->attributes = [];
        $this->client = null;
        $this->config = $config ?? Config::make();
        $this->decoder = $this->config->getDecoder("message");

        $this->options = $this->config->get('options');
        $this->available_flags = $this->config->get('flags');

        $this->attachments = AttachmentCollection::make();
        $this->flags = FlagCollection::make();
    }

    /**
     * Call dynamic attribute setter and getter methods
     * @param string $method
     * @param array $arguments
     *
     * @return mixed
     * @throws AuthFailedException
     * @throws ConnectionFailedException
     * @throws ImapBadRequestException
     * @throws ImapServerErrorException
     * @throws MessageNotFoundException
     * @throws MethodNotFoundException
     * @throws MessageSizeFetchingException
     * @throws RuntimeException
     * @throws ResponseException
     */
    public function __call(string $method, array $arguments) {
        if (strtolower(substr($method, 0, 3)) === 'get') {
            $name = Str::snake(substr($method, 3));
            return $this->get($name);
        } elseif (strtolower(substr($method, 0, 3)) === 'set') {
            $name = Str::snake(substr($method, 3));

            if (in_array($name, array_keys($this->attributes))) {
                return $this->__set($name, array_pop($arguments));
            }

        }

        throw new MethodNotFoundException("Method " . self::class . '::' . $method . '() is not supported');
    }

    /**
     * Magic setter
     * @param $name
     * @param $value
     *
     * @return mixed
     */
    public function __set($name, $value) {
        $this->attributes[$name] = $value;

        return $this->attributes[$name];
    }

    /**
     * Magic getter
     * @param $name
     *
     * @return Attribute|mixed|null
     * @throws AuthFailedException
     * @throws ConnectionFailedException
     * @throws ImapBadRequestException
     * @throws ImapServerErrorException
     * @throws MessageNotFoundException
     * @throws MessageSizeFetchingException
     * @throws RuntimeException
     * @throws ResponseException
     */
    public function __get($name) {
        return $this->get($name);
    }

    /**
     * Get an available message or message header attribute
     * @param $name
     *
     * @return Attribute|mixed|null
     * @throws AuthFailedException
     * @throws ConnectionFailedException
     * @throws ImapBadRequestException
     * @throws ImapServerErrorException
     * @throws MessageNotFoundException
     * @throws RuntimeException
     * @throws ResponseException
     * @throws MessageSizeFetchingException
     */
    public function get($name): mixed {
        if (isset($this->attributes[$name]) && $this->attributes[$name] !== null) {
            return $this->attributes[$name];
        }

        switch ($name){
            case "uid":
                $this->attributes[$name] = $this->client->getConnection()->getUid($this->msgn)->validate()->integer();
                return $this->attributes[$name];
            case "msgn":
                $this->attributes[$name] = $this->client->getConnection()->getMessageNumber($this->uid)->validate()->integer();
                return $this->attributes[$name];
            case "size":
                if (!isset($this->attributes[$name])) {
                    $this->fetchSize();
                }
                return $this->attributes[$name];
        }

        return $this->header->get($name);
    }

    /**
     * Check if the Message has a text body
     *
     * @return bool
     */
    public function hasTextBody(): bool {
        return isset($this->bodies['text']) && $this->bodies['text'] !== "";
    }

    /**
     * Get the Message text body
     *
     * @return string
     */
    public function getTextBody(): string {
        if (!isset($this->bodies['text'])) {
            return "";
        }

        return $this->bodies['text'];
    }

    /**
     * Check if the Message has a html body
     *
     * @return bool
     */
    public function hasHTMLBody(): bool {
        return isset($this->bodies['html']) && $this->bodies['html'] !== "";
    }

    /**
     * Get the Message html body
     *
     * @return string
     */
    public function getHTMLBody(): string {
        if (!isset($this->bodies['html'])) {
            return "";
        }

        return $this->bodies['html'];
    }

    /**
     * Parse all defined headers
     *
     * @throws AuthFailedException
     * @throws ConnectionFailedException
     * @throws ImapBadRequestException
     * @throws ImapServerErrorException
     * @throws RuntimeException
     * @throws InvalidMessageDateException
     * @throws MessageHeaderFetchingException
     * @throws ResponseException
     */
    private function parseHeader(): void {
        $sequence_id = $this->getSequenceId();
        $headers = $this->client->getConnection()->headers([$sequence_id], "RFC822", $this->sequence)->setCanBeEmpty(true)->validatedData();
        if (!isset($headers[$sequence_id])) {
            throw new MessageHeaderFetchingException("no headers found", 0);
        }

        $this->parseRawHeader($headers[$sequence_id]);
    }

    /**
     * @param string $raw_header
     *
     * @throws InvalidMessageDateException
     */
    public function parseRawHeader(string $raw_header): void {
        $this->header = new Header($raw_header, $this->getConfig());
    }

    /**
     * Parse additional raw flags
     * @param array $raw_flags
     */
    public function parseRawFlags(array $raw_flags): void {
        $this->flags = FlagCollection::make();

        foreach ($raw_flags as $flag) {
            if (str_starts_with($flag, "\\")) {
                $flag = substr($flag, 1);
            }
            $flag_key = strtolower($flag);
            if ($this->available_flags === null || in_array($flag_key, $this->available_flags)) {
                $this->flags->put($flag_key, $flag);
            }
        }
    }

    /**
     * Parse additional flags
     *
     * @return void
     * @throws AuthFailedException
     * @throws ConnectionFailedException
     * @throws ImapBadRequestException
     * @throws ImapServerErrorException
     * @throws MessageFlagException
     * @throws RuntimeException
     * @throws ResponseException
     */
    private function parseFlags(): void {
        $this->client->openFolder($this->folder_path);
        $this->flags = FlagCollection::make();

        $sequence_id = $this->getSequenceId();
        try {
            $flags = $this->client->getConnection()->flags([$sequence_id], $this->sequence)->setCanBeEmpty(true)->validatedData();
        } catch (Exceptions\RuntimeException $e) {
            throw new MessageFlagException("flag could not be fetched", 0, $e);
        }

        if (isset($flags[$sequence_id])) {
            $this->parseRawFlags($flags[$sequence_id]);
        }
    }

    /**
     * Parse the Message body
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
     * @throws RuntimeException
     * @throws ResponseException
     */
    public function parseBody(): Message {
        $this->client->openFolder($this->folder_path);

        $sequence_id = $this->getSequenceId();
        try {
            $contents = $this->client->getConnection()->content([$sequence_id], $this->client->rfc, $this->sequence)->validatedData();
        } catch (Exceptions\RuntimeException $e) {
            throw new MessageContentFetchingException("failed to fetch content", 0, $e);
        }
        if (!isset($contents[$sequence_id])) {
            throw new MessageContentFetchingException("no content found", 0);
        }
        $content = $contents[$sequence_id];

        $body = $this->parseRawBody($content);
        $this->peek();

        return $body;
    }

    /**
     * Fetches the size for this message.
     *
     * @throws AuthFailedException
     * @throws ConnectionFailedException
     * @throws ImapBadRequestException
     * @throws ImapServerErrorException
     * @throws MessageSizeFetchingException
     * @throws ResponseException
     * @throws RuntimeException
     */
    private function fetchSize(): void {
        $sequence_id = $this->getSequenceId();
        $sizes = $this->client->getConnection()->sizes([$sequence_id], $this->sequence)->validatedData();
         if (!isset($sizes[$sequence_id])) {
            throw new MessageSizeFetchingException("sizes did not set an array entry for the supplied sequence_id", 0);
        }
        $this->attributes["size"] = $sizes[$sequence_id];
    }

    /**
     * Handle auto "Seen" flag handling
     *
     * @throws AuthFailedException
     * @throws ConnectionFailedException
     * @throws EventNotFoundException
     * @throws ImapBadRequestException
     * @throws ImapServerErrorException
     * @throws MessageFlagException
     * @throws RuntimeException
     * @throws ResponseException
     */
    public function peek(): void {
        if ($this->fetch_options == IMAP::FT_PEEK) {
            if ($this->getFlags()->get("seen") == null) {
                $this->unsetFlag("Seen");
            }
        } elseif ($this->getFlags()->get("seen") == null) {
            $this->setFlag("Seen");
        }
    }

    /**
     * Parse a given message body
     * @param string $raw_body
     *
     * @return Message
     * @throws AuthFailedException
     * @throws ConnectionFailedException
     * @throws ImapBadRequestException
     * @throws ImapServerErrorException
     * @throws InvalidMessageDateException
     * @throws MessageContentFetchingException
     * @throws RuntimeException
     * @throws ResponseException
     */
    public function parseRawBody(string $raw_body): Message {
        $this->structure = new Structure($raw_body, $this->header);
        $this->fetchStructure($this->structure);

        return $this;
    }

    /**
     * Fetch the Message structure
     * @param Structure $structure
     *
     * @throws AuthFailedException
     * @throws ConnectionFailedException
     * @throws ImapBadRequestException
     * @throws ImapServerErrorException
     * @throws RuntimeException
     * @throws ResponseException
     */
    private function fetchStructure(Structure $structure): void {
        $this->client?->openFolder($this->folder_path);

        foreach ($structure->parts as $part) {
            $this->fetchPart($part);
        }
    }

    /**
     * Fetch a given part
     * @param Part $part
     */
    private function fetchPart(Part $part): void {
        if ($part->isAttachment()) {
            $this->fetchAttachment($part);
        } else {
            $encoding = $this->decoder->getEncoding($part);

            $content = $this->decoder->decode($part->content, $part->encoding);

            // We don't need to do convertEncoding() if charset is ASCII (us-ascii):
            //     ASCII is a subset of UTF-8, so all ASCII files are already UTF-8 encoded
            //     https://stackoverflow.com/a/11303410
            //
            // us-ascii is the same as ASCII:
            //     ASCII is the traditional name for the encoding system; the Internet Assigned Numbers Authority (IANA)
            //     prefers the updated name US-ASCII, which clarifies that this system was developed in the US and
            //     based on the typographical symbols predominantly in use there.
            //     https://en.wikipedia.org/wiki/ASCII
            //
            // convertEncoding() function basically means convertToUtf8(), so when we convert ASCII string into UTF-8 it gets broken.
            if ($encoding != 'us-ascii') {
                $content = $this->decoder->convertEncoding($content, $encoding);
            }

            $this->addBody($part->subtype ?? '', $content);
        }
    }

    /**
     * Add a body to the message
     * @param string $subtype
     * @param string $content
     *
     * @return void
     */
    protected function addBody(string $subtype, string $content): void {
        $subtype = strtolower($subtype);
        $subtype = $subtype == "plain" || $subtype == "" ? "text" : $subtype;

        if (isset($this->bodies[$subtype]) && $this->bodies[$subtype] !== null && $this->bodies[$subtype] !== "") {
            if ($content !== "") {
                $this->bodies[$subtype] .= "\n".$content;
            }
        } else {
            $this->bodies[$subtype] = $content;
        }
    }

    /**
     * Fetch the Message attachment
     * @param Part $part
     */
    protected function fetchAttachment(Part $part): void {
        $oAttachment = new Attachment($this, $part);

        if ($oAttachment->getSize() > 0) {
            if ($oAttachment->getId() !== null && $this->attachments->offsetExists($oAttachment->getId())) {
                $this->attachments->put($oAttachment->getId(), $oAttachment);
            } else {
                $this->attachments->push($oAttachment);
            }
        }
    }

    /**
     * Fail proof setter for $fetch_option
     * @param $option
     *
     * @return Message
     */
    public function setFetchOption($option): Message {
        if (is_long($option) === true) {
            $this->fetch_options = $option;
        } elseif (is_null($option) === true) {
            $config = $this->config->get('options.fetch', IMAP::FT_UID);
            $this->fetch_options = is_long($config) ? $config : 1;
        }

        return $this;
    }

    /**
     * Set the sequence type
     * @param int|null $sequence
     *
     * @return Message
     */
    public function setSequence(?int $sequence): Message {
        if (is_long($sequence)) {
            $this->sequence = $sequence;
        } elseif (is_null($sequence)) {
            $config = $this->config->get('options.sequence', IMAP::ST_MSGN);
            $this->sequence = is_long($config) ? $config : IMAP::ST_MSGN;
        }

        return $this;
    }

    /**
     * Fail proof setter for $fetch_body
     * @param $option
     *
     * @return Message
     */
    public function setFetchBodyOption($option): Message {
        if (is_bool($option)) {
            $this->fetch_body = $option;
        } elseif (is_null($option)) {
            $config = $this->config->get('options.fetch_body', true);
            $this->fetch_body = is_bool($config) ? $config : true;
        }

        return $this;
    }

    /**
     * Fail proof setter for $fetch_flags
     * @param $option
     *
     * @return Message
     */
    public function setFetchFlagsOption($option): Message {
        if (is_bool($option)) {
            $this->fetch_flags = $option;
        } elseif (is_null($option)) {
            $config = $this->config->get('options.fetch_flags', true);
            $this->fetch_flags = is_bool($config) ? $config : true;
        }

        return $this;
    }

    /**
     * Get the messages folder
     *
     * @return ?Folder
     * @throws AuthFailedException
     * @throws ConnectionFailedException
     * @throws FolderFetchingException
     * @throws ImapBadRequestException
     * @throws ImapServerErrorException
     * @throws RuntimeException
     * @throws ResponseException
     */
    public function getFolder(): ?Folder {
        return $this->client->getFolderByPath($this->folder_path);
    }

    /**
     * Create a message thread based on the current message
     * @param Folder|null $sent_folder
     * @param MessageCollection|null $thread
     * @param Folder|null $folder
     *
     * @return MessageCollection
     * @throws AuthFailedException
     * @throws ConnectionFailedException
     * @throws FolderFetchingException
     * @throws GetMessagesFailedException
     * @throws ImapBadRequestException
     * @throws ImapServerErrorException
     * @throws RuntimeException
     * @throws ResponseException
     */
    public function thread(?Folder $sent_folder = null, ?MessageCollection &$thread = null, ?Folder $folder = null): MessageCollection {
        $thread = $thread ?: MessageCollection::make();
        $folder = $folder ?: $this->getFolder();
        $sent_folder = $sent_folder ?: $this->client->getFolderByPath($this->config->get("options.common_folders.sent", "INBOX/Sent"));

        /** @var Message $message */
        foreach ($thread as $message) {
            if ($message->message_id->first() == $this->message_id->first()) {
                return $thread;
            }
        }
        $thread->push($this);

        $this->fetchThreadByInReplyTo($thread, $this->message_id, $folder, $folder, $sent_folder);
        $this->fetchThreadByInReplyTo($thread, $this->message_id, $sent_folder, $folder, $sent_folder);

        foreach ($this->in_reply_to->all() as $in_reply_to) {
            $this->fetchThreadByMessageId($thread, $in_reply_to, $folder, $folder, $sent_folder);
            $this->fetchThreadByMessageId($thread, $in_reply_to, $sent_folder, $folder, $sent_folder);
        }

        return $thread;
    }

    /**
     * Fetch a partial thread by message id
     * @param MessageCollection $thread
     * @param string $in_reply_to
     * @param Folder $primary_folder
     * @param Folder $secondary_folder
     * @param Folder $sent_folder
     *
     * @throws AuthFailedException
     * @throws ConnectionFailedException
     * @throws FolderFetchingException
     * @throws GetMessagesFailedException
     * @throws ImapBadRequestException
     * @throws ImapServerErrorException
     * @throws RuntimeException
     * @throws ResponseException
     */
    protected function fetchThreadByInReplyTo(MessageCollection &$thread, string $in_reply_to, Folder $primary_folder, Folder $secondary_folder, Folder $sent_folder): void {
        $primary_folder->query()->inReplyTo($in_reply_to)
            ->setFetchBody($this->getFetchBodyOption())
            ->leaveUnread()->get()->each(function($message) use (&$thread, $secondary_folder, $sent_folder) {
                /** @var Message $message */
                $message->thread($sent_folder, $thread, $secondary_folder);
            });
    }

    /**
     * Fetch a partial thread by message id
     * @param MessageCollection $thread
     * @param string $message_id
     * @param Folder $primary_folder
     * @param Folder $secondary_folder
     * @param Folder $sent_folder
     *
     * @throws AuthFailedException
     * @throws ConnectionFailedException
     * @throws GetMessagesFailedException
     * @throws FolderFetchingException
     * @throws ImapBadRequestException
     * @throws ImapServerErrorException
     * @throws RuntimeException
     * @throws ResponseException
     */
    protected function fetchThreadByMessageId(MessageCollection &$thread, string $message_id, Folder $primary_folder, Folder $secondary_folder, Folder $sent_folder): void {
        $primary_folder->query()->messageId($message_id)
            ->setFetchBody($this->getFetchBodyOption())
            ->leaveUnread()->get()->each(function($message) use (&$thread, $secondary_folder, $sent_folder) {
                /** @var Message $message */
                $message->thread($sent_folder, $thread, $secondary_folder);
            });
    }

    /**
     * Copy the current Messages to a mailbox
     * @param string $folder_path
     * @param boolean $expunge
     * @param bool $utf7
     *
     * @return null|Message
     * @throws AuthFailedException
     * @throws ConnectionFailedException
     * @throws EventNotFoundException
     * @throws FolderFetchingException
     * @throws ImapBadRequestException
     * @throws ImapServerErrorException
     * @throws InvalidMessageDateException
     * @throws MessageContentFetchingException
     * @throws MessageFlagException
     * @throws MessageHeaderFetchingException
     * @throws MessageNotFoundException
     * @throws RuntimeException
     * @throws ResponseException
     */
    public function copy(string $folder_path, bool $expunge = false, bool $utf7 = false): ?Message {
        $this->client->openFolder($folder_path);
        $status = $this->client->getConnection()->examineFolder($folder_path)->validatedData();

        if (isset($status["uidnext"])) {
            $next_uid = $status["uidnext"];
            if ((int)$next_uid <= 0) {
                return null;
            }

            /** @var Folder $folder */
            $folder = $this->client->getFolderByPath($folder_path, $utf7);

            $this->client->openFolder($this->folder_path);
            if ($this->client->getConnection()->copyMessage($folder->path, $this->getSequenceId(), null, $this->sequence)->validatedData()) {
                return $this->fetchNewMail($folder, $next_uid, "copied", $expunge);
            }
        }

        return null;
    }

    /**
     * Move the current Messages to a mailbox
     * @param string $folder_path
     * @param boolean $expunge
     * @param bool $utf7
     *
     * @return Message|null
     * @throws AuthFailedException
     * @throws ConnectionFailedException
     * @throws EventNotFoundException
     * @throws FolderFetchingException
     * @throws ImapBadRequestException
     * @throws ImapServerErrorException
     * @throws InvalidMessageDateException
     * @throws MessageContentFetchingException
     * @throws MessageFlagException
     * @throws MessageHeaderFetchingException
     * @throws MessageNotFoundException
     * @throws RuntimeException
     * @throws ResponseException
     */
    public function move(string $folder_path, bool $expunge = false, bool $utf7 = false): ?Message {
        $this->client->openFolder($folder_path);
        $status = $this->client->getConnection()->examineFolder($folder_path)->validatedData();

        if (isset($status["uidnext"])) {
            $next_uid = $status["uidnext"];
            if ((int)$next_uid <= 0) {
                return null;
            }

            /** @var Folder $folder */
            $folder = $this->client->getFolderByPath($folder_path, $utf7);

            $this->client->openFolder($this->folder_path);
            if ($this->client->getConnection()->moveMessage($folder->path, $this->getSequenceId(), null, $this->sequence)->validatedData()) {
                return $this->fetchNewMail($folder, $next_uid, "moved", $expunge);
            }
        }

        return null;
    }

    /**
     * Fetch a new message and fire a given event
     * @param Folder $folder
     * @param int $next_uid
     * @param string $event
     * @param boolean $expunge
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
     * @throws MessageNotFoundException
     * @throws RuntimeException
     * @throws ResponseException
     */
    protected function fetchNewMail(Folder $folder, int $next_uid, string $event, bool $expunge): Message {
        if ($expunge) $this->client->expunge();

        $this->client->openFolder($folder->path);

        if ($this->sequence === IMAP::ST_UID) {
            $sequence_id = $next_uid;
        } else {
            $sequence_id = $this->client->getConnection()->getMessageNumber($next_uid)->validatedData();
        }

        $message = $folder->query()->getMessage($sequence_id, null, $this->sequence);
        $this->dispatch("message", $event, $this, $message);

        return $message;
    }

    /**
     * Delete the current Message
     * @param bool $expunge
     * @param string|null $trash_path
     * @param boolean $force_move
     *
     * @return bool
     * @throws AuthFailedException
     * @throws ConnectionFailedException
     * @throws EventNotFoundException
     * @throws FolderFetchingException
     * @throws ImapBadRequestException
     * @throws ImapServerErrorException
     * @throws InvalidMessageDateException
     * @throws MessageContentFetchingException
     * @throws MessageFlagException
     * @throws MessageHeaderFetchingException
     * @throws MessageNotFoundException
     * @throws RuntimeException
     * @throws ResponseException
     */
    public function delete(bool $expunge = true, ?string $trash_path = null, bool $force_move = false): bool {
        $status = $this->setFlag("Deleted");
        if ($force_move) {
            $trash_path = $trash_path === null ? $this->config["common_folders"]["trash"] : $trash_path;
            $this->move($trash_path);
        }
        if ($expunge) $this->client->expunge();

        $this->dispatch("message", "deleted", $this);

        return $status;
    }

    /**
     * Restore a deleted Message
     * @param boolean $expunge
     *
     * @return bool
     * @throws AuthFailedException
     * @throws ConnectionFailedException
     * @throws EventNotFoundException
     * @throws ImapBadRequestException
     * @throws ImapServerErrorException
     * @throws MessageFlagException
     * @throws RuntimeException
     * @throws ResponseException
     */
    public function restore(bool $expunge = true): bool {
        $status = $this->unsetFlag("Deleted");
        if ($expunge) $this->client->expunge();

        $this->dispatch("message", "restored", $this);

        return $status;
    }

    /**
     * Set a given flag
     * @param array|string $flag
     *
     * @return bool
     * @throws AuthFailedException
     * @throws ConnectionFailedException
     * @throws EventNotFoundException
     * @throws ImapBadRequestException
     * @throws ImapServerErrorException
     * @throws MessageFlagException
     * @throws RuntimeException
     * @throws ResponseException
     */
    public function setFlag(array|string $flag): bool {
        $this->client->openFolder($this->folder_path);
        $flag = "\\" . trim(is_array($flag) ? implode(" \\", $flag) : $flag);
        $sequence_id = $this->getSequenceId();
        try {
            $status = $this->client->getConnection()->store([$flag], $sequence_id, $sequence_id, "+", true, $this->sequence)->validatedData();
        } catch (Exceptions\RuntimeException $e) {
            throw new MessageFlagException("flag could not be set", 0, $e);
        }
        $this->parseFlags();

        $this->dispatch("flag", "new", $this, $flag);

        return (bool)$status;
    }

    /**
     * Unset a given flag
     * @param array|string $flag
     *
     * @return bool
     * @throws AuthFailedException
     * @throws ConnectionFailedException
     * @throws EventNotFoundException
     * @throws ImapBadRequestException
     * @throws ImapServerErrorException
     * @throws MessageFlagException
     * @throws RuntimeException
     * @throws ResponseException
     */
    public function unsetFlag(array|string $flag): bool {
        $this->client->openFolder($this->folder_path);

        $flag = "\\" . trim(is_array($flag) ? implode(" \\", $flag) : $flag);
        $sequence_id = $this->getSequenceId();
        try {
            $status = $this->client->getConnection()->store([$flag], $sequence_id, $sequence_id, "-", true, $this->sequence)->validatedData();
        } catch (Exceptions\RuntimeException $e) {
            throw new MessageFlagException("flag could not be removed", 0, $e);
        }
        $this->parseFlags();

        $this->dispatch("flag", "deleted", $this, $flag);

        return (bool)$status;
    }

    /**
     * Set a given flag
     * @param array|string $flag
     *
     * @return bool
     * @throws AuthFailedException
     * @throws ConnectionFailedException
     * @throws EventNotFoundException
     * @throws ImapBadRequestException
     * @throws ImapServerErrorException
     * @throws MessageFlagException
     * @throws RuntimeException
     * @throws ResponseException
     */
    public function addFlag(array|string $flag): bool {
        return $this->setFlag($flag);
    }

    /**
     * Unset a given flag
     * @param array|string $flag
     *
     * @return bool
     * @throws AuthFailedException
     * @throws ConnectionFailedException
     * @throws EventNotFoundException
     * @throws ImapBadRequestException
     * @throws ImapServerErrorException
     * @throws MessageFlagException
     * @throws RuntimeException
     * @throws ResponseException
     */
    public function removeFlag(array|string $flag): bool {
        return $this->unsetFlag($flag);
    }

    /**
     * Get all message attachments.
     *
     * @return AttachmentCollection
     */
    public function getAttachments(): AttachmentCollection {
        return $this->attachments;
    }

    /**
     * Get all message attachments.
     *
     * @return AttachmentCollection
     */
    public function attachments(): AttachmentCollection {
        return $this->getAttachments();
    }

    /**
     * Checks if there are any attachments present
     *
     * @return boolean
     */
    public function hasAttachments(): bool {
        return $this->attachments->isEmpty() === false;
    }

    /**
     * Get the raw body
     *
     * @return string
     */
    public function getRawBody(): string {
        if ($this->raw_body === "") {
            $this->raw_body = $this->structure->raw;
        }

        return $this->raw_body;
    }

    /**
     * Get the message header
     *
     * @return ?Header
     */
    public function getHeader(): ?Header {
        return $this->header;
    }

    /**
     * Get the current client
     *
     * @return ?Client
     */
    public function getClient(): ?Client {
        return $this->client;
    }

    /**
     * Get the used fetch option
     *
     * @return ?integer
     */
    public function getFetchOptions(): ?int {
        return $this->fetch_options;
    }

    /**
     * Get the used fetch body option
     *
     * @return boolean
     */
    public function getFetchBodyOption(): bool {
        return $this->fetch_body;
    }

    /**
     * Get the used fetch flags option
     *
     * @return boolean
     */
    public function getFetchFlagsOption(): bool {
        return $this->fetch_flags;
    }

    /**
     * Get all available bodies
     *
     * @return array
     */
    public function getBodies(): array {
        return $this->bodies;
    }

    /**
     * Get all set flags
     *
     * @return FlagCollection
     */
    public function getFlags(): FlagCollection {
        return $this->flags;
    }

    /**
     * Get all set flags
     *
     * @return FlagCollection
     */
    public function flags(): FlagCollection {
        return $this->getFlags();
    }

    /**
     * Check if a flag is set
     *
     * @param string $flag
     * @return boolean
     */
    public function hasFlag(string $flag): bool {
        $flag = str_replace("\\", "", strtolower($flag));
        return $this->getFlags()->has($flag);
    }

    /**
     * Get the fetched structure
     *
     * @return Structure|null
     */
    public function getStructure(): ?Structure {
        return $this->structure;
    }

    /**
     * Check if a message matches another by comparing basic attributes
     *
     * @param null|Message $message
     * @return boolean
     */
    public function is(?Message $message = null): bool {
        if (is_null($message)) {
            return false;
        }

        return $this->uid == $message->uid
            && $this->message_id->first() == $message->message_id->first()
            && $this->subject->first() == $message->subject->first()
            && $this->date->toDate()->eq($message->date->toDate());
    }

    /**
     * Get all message attributes
     *
     * @return array
     */
    public function getAttributes(): array {
        return array_merge($this->attributes, $this->header->getAttributes());
    }

    /**
     * Set the message mask
     * @param $mask
     *
     * @return Message
     */
    public function setMask($mask): Message {
        if (class_exists($mask)) {
            $this->mask = $mask;
        }

        return $this;
    }

    /**
     * Get the used message mask
     *
     * @return string
     */
    public function getMask(): string {
        return $this->mask;
    }

    /**
     * Get a masked instance by providing a mask name
     * @param mixed|null $mask
     *
     * @return mixed
     * @throws MaskNotFoundException
     */
    public function mask(mixed $mask = null): mixed {
        $mask = $mask !== null ? $mask : $this->mask;
        if (class_exists($mask)) {
            return new $mask($this);
        }

        throw new MaskNotFoundException("Unknown mask provided: " . $mask);
    }

    /**
     * Get the message path aka folder path
     *
     * @return string
     */
    public function getFolderPath(): string {
        return $this->folder_path;
    }

    /**
     * Set the message path aka folder path
     * @param $folder_path
     *
     * @return Message
     */
    public function setFolderPath($folder_path): Message {
        $this->folder_path = $folder_path;

        return $this;
    }

    /**
     * Set the config
     * @param Config $config
     *
     * @return Message
     */
    public function setConfig(Config $config): Message {
        $this->config = $config;

        return $this;
    }

    /**
     * Get the config
     *
     * @return Config
     */
    public function getConfig(): Config {
        return $this->config;
    }

    /**
     * Set the options
     * @param array $options
     *
     * @return Message
     */
    public function setOptions(array $options): Message {
        $this->options = $options;

        return $this;
    }

    /**
     * Get the options
     *
     * @return array
     */
    public function getOptions(): array {
        return $this->options;
    }

    /**
     * Set the available flags
     * @param $available_flags
     *
     * @return Message
     */
    public function setAvailableFlags($available_flags): Message {
        $this->available_flags = $available_flags;

        return $this;
    }

    /**
     * Get the available flags
     *
     * @return array
     */
    public function getAvailableFlags(): array {
        return $this->available_flags;
    }

    /**
     * Set the attachment collection
     * @param $attachments
     *
     * @return Message
     */
    public function setAttachments($attachments): Message {
        $this->attachments = $attachments;

        return $this;
    }

    /**
     * Set the flag collection
     * @param $flags
     *
     * @return Message
     */
    public function setFlags($flags): Message {
        $this->flags = $flags;

        return $this;
    }

    /**
     * Set the client
     * @param $client
     *
     * @return Message
     * @throws AuthFailedException
     * @throws ConnectionFailedException
     * @throws ImapBadRequestException
     * @throws ImapServerErrorException
     * @throws RuntimeException
     * @throws ResponseException
     */
    public function setClient($client): Message {
        $this->client = $client;
        $this->client?->openFolder($this->folder_path);

        return $this;
    }

    /**
     * Set the message number
     * @param int $uid
     *
     * @return Message
     */
    public function setUid(int $uid): Message {
        $this->uid = $uid;
        $this->msgn = null;
        $this->msglist = null;

        return $this;
    }

    /**
     * Set the message number
     * @param int $msgn
     * @param int|null $msglist
     *
     * @return Message
     */
    public function setMsgn(int $msgn, ?int $msglist = null): Message {
        $this->msgn = $msgn;
        $this->msglist = $msglist;
        $this->uid = null;

        return $this;
    }

    /**
     * Get the current sequence type
     *
     * @return int
     */
    public function getSequence(): int {
        return $this->sequence;
    }

    /**
     * Get the current sequence id (either a UID or a message number!)
     *
     * @return int
     */
    public function getSequenceId(): int {
        return $this->sequence === IMAP::ST_UID ? $this->uid : $this->msgn;
    }

    /**
     * Set the sequence id
     * @param $uid
     * @param int|null $msglist
     */
    public function setSequenceId($uid, ?int $msglist = null): void {
        if ($this->getSequence() === IMAP::ST_UID) {
            $this->setUid($uid);
            $this->setMsglist($msglist);
        } else {
            $this->setMsgn($uid, $msglist);
        }
    }

    /**
     * Get the decoder instance
     *
     * @return DecoderInterface
     */
    public function getDecoder(): DecoderInterface {
        return $this->decoder;
    }

    /**
     * Set the decoder instance
     * @param DecoderInterface $decoder
     *
     * @return $this
     */
    public function setDecoder(DecoderInterface $decoder): static {
        $this->decoder = $decoder;
        return $this;
    }

    /**
     * Safe the entire message in a file
     * @param $filename
     *
     * @return bool|int
     */
    public function save($filename): bool|int {
        return file_put_contents($filename, $this->header->raw."\r\n\r\n".$this->structure->raw);
    }
}
