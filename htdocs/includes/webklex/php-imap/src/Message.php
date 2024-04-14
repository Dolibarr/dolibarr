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

use ReflectionClass;
use ReflectionException;
use Webklex\PHPIMAP\Exceptions\InvalidMessageDateException;
use Webklex\PHPIMAP\Exceptions\MaskNotFoundException;
use Webklex\PHPIMAP\Exceptions\MessageContentFetchingException;
use Webklex\PHPIMAP\Exceptions\MessageFlagException;
use Webklex\PHPIMAP\Exceptions\MessageHeaderFetchingException;
use Webklex\PHPIMAP\Exceptions\MethodNotFoundException;
use Webklex\PHPIMAP\Support\AttachmentCollection;
use Webklex\PHPIMAP\Support\FlagCollection;
use Webklex\PHPIMAP\Support\Masks\MessageMask;
use Illuminate\Support\Str;
use Webklex\PHPIMAP\Support\MessageCollection;
use Webklex\PHPIMAP\Traits\HasEvents;

/**
 * Class Message
 *
 * @package Webklex\PHPIMAP
 *
 * @property integer msglist
 * @property integer uid
 * @property integer msgn
 * @property Attribute subject
 * @property Attribute message_id
 * @property Attribute message_no
 * @property Attribute references
 * @property Attribute date
 * @property Attribute from
 * @property Attribute to
 * @property Attribute cc
 * @property Attribute bcc
 * @property Attribute reply_to
 * @property Attribute in_reply_to
 * @property Attribute sender
 *
 * @method integer getMsglist()
 * @method integer setMsglist($msglist)
 * @method integer getUid()
 * @method integer getMsgn()
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
     * @var Client
     */
    private $client = Client::class;

    /**
     * Default mask
     *
     * @var string $mask
     */
    protected $mask = MessageMask::class;

    /**
     * Used config
     *
     * @var array $config
     */
    protected $config = [];

    /**
     * Attribute holder
     *
     * @var Attribute[]|mixed[] $attributes
     */
    protected $attributes = [];

    /**
     * The message folder path
     *
     * @var string $folder_path
     */
    protected $folder_path;

    /**
     * Fetch body options
     *
     * @var integer
     */
    public $fetch_options = null;

    /**
     * @var integer
     */
    protected $sequence = IMAP::NIL;

    /**
     * Fetch body options
     *
     * @var bool
     */
    public $fetch_body = null;

    /**
     * Fetch flags options
     *
     * @var bool
     */
    public $fetch_flags = null;

    /**
     * @var Header $header
     */
    public $header = null;

    /**
     * Raw message body
     *
     * @var null|string $raw_body
     */
    public $raw_body = null;

    /**
     * Message structure
     *
     * @var Structure $structure
     */
    protected $structure = null;

    /**
     * Message body components
     *
     * @var array   $bodies
     */
    public $bodies = [];

    /** @var AttachmentCollection $attachments */
    public $attachments;

    /** @var FlagCollection $flags */
    public $flags;

    /**
     * A list of all available and supported flags
     *
     * @var array $available_flags
     */
    private $available_flags = null;

    /**
     * Message constructor.
     * @param integer $uid
     * @param integer|null $msglist
     * @param Client $client
     * @param integer|null $fetch_options
     * @param boolean $fetch_body
     * @param boolean $fetch_flags
     * @param integer $sequence
     *
     * @throws Exceptions\ConnectionFailedException
     * @throws InvalidMessageDateException
     * @throws Exceptions\RuntimeException
     * @throws MessageHeaderFetchingException
     * @throws MessageContentFetchingException
     * @throws Exceptions\EventNotFoundException
     * @throws MessageFlagException
     * @throws Exceptions\MessageNotFoundException
     */
    public function __construct($uid, $msglist, Client $client, $fetch_options = null, $fetch_body = false, $fetch_flags = false, $sequence = null) {
        $this->boot();

        $default_mask = $client->getDefaultMessageMask();
        if($default_mask != null) {
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
     * @throws Exceptions\ConnectionFailedException
     * @throws Exceptions\EventNotFoundException
     * @throws InvalidMessageDateException
     * @throws MessageContentFetchingException
     * @throws ReflectionException
     * @throws MessageFlagException
     * @throws Exceptions\RuntimeException
     * @throws Exceptions\MessageNotFoundException
     */
    public static function make($uid, $msglist, Client $client, $raw_header, $raw_body, $raw_flags, $fetch_options = null, $sequence = null){
        $reflection = new ReflectionClass(self::class);
        /** @var self $instance */
        $instance = $reflection->newInstanceWithoutConstructor();
        $instance->boot();

        $default_mask = $client->getDefaultMessageMask();
        if($default_mask != null) {
            $instance->setMask($default_mask);
        }
        $instance->setEvents([
            "message" => $client->getDefaultEvents("message"),
            "flag" => $client->getDefaultEvents("flag"),
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
     * Boot a new instance
     */
    public function boot(){
        $this->attributes = [];

        $this->config = ClientManager::get('options');
        $this->available_flags = ClientManager::get('flags');

        $this->attachments = AttachmentCollection::make([]);
        $this->flags = FlagCollection::make([]);
    }

    /**
     * Call dynamic attribute setter and getter methods
     * @param string $method
     * @param array $arguments
     *
     * @return mixed
     * @throws MethodNotFoundException
     */
    public function __call($method, $arguments) {
        if(strtolower(substr($method, 0, 3)) === 'get') {
            $name = Str::snake(substr($method, 3));
            return $this->get($name);
        }elseif (strtolower(substr($method, 0, 3)) === 'set') {
            $name = Str::snake(substr($method, 3));

            if(in_array($name, array_keys($this->attributes))) {
                return $this->__set($name, array_pop($arguments));
            }

        }

        throw new MethodNotFoundException("Method ".self::class.'::'.$method.'() is not supported');
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
     */
    public function __get($name) {
        return $this->get($name);
    }

    /**
     * Get an available message or message header attribute
     * @param $name
     *
     * @return Attribute|mixed|null
     */
    public function get($name) {
        if(isset($this->attributes[$name])) {
            return $this->attributes[$name];
        }

        return $this->header->get($name);
    }

    /**
     * Check if the Message has a text body
     *
     * @return bool
     */
    public function hasTextBody() {
        return isset($this->bodies['text']);
    }

    /**
     * Get the Message text body
     *
     * @return mixed
     */
    public function getTextBody() {
        if (!isset($this->bodies['text'])) {
            return null;
        }

        return $this->bodies['text'];
    }

    /**
     * Check if the Message has a html body
     *
     * @return bool
     */
    public function hasHTMLBody() {
        return isset($this->bodies['html']);
    }

    /**
     * Get the Message html body
     *
     * @return string|null
     */
    public function getHTMLBody() {
        if (!isset($this->bodies['html'])) {
            return null;
        }

        return $this->bodies['html'];
    }

    /**
     * Parse all defined headers
     *
     * @throws Exceptions\ConnectionFailedException
     * @throws Exceptions\RuntimeException
     * @throws InvalidMessageDateException
     * @throws MessageHeaderFetchingException
     */
    private function parseHeader() {
        $sequence_id = $this->getSequenceId();
        $headers = $this->client->getConnection()->headers([$sequence_id], "RFC822", $this->sequence === IMAP::ST_UID);
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
    public function parseRawHeader($raw_header){
        $this->header = new Header($raw_header);
    }

    /**
     * Parse additional raw flags
     * @param array $raw_flags
     */
    public function parseRawFlags($raw_flags) {
        $this->flags = FlagCollection::make([]);

        foreach($raw_flags as $flag) {
            if (strpos($flag, "\\") === 0){
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
     * @throws Exceptions\ConnectionFailedException
     * @throws MessageFlagException
     * @throws Exceptions\RuntimeException
     */
    private function parseFlags() {
        $this->client->openFolder($this->folder_path);
        $this->flags = FlagCollection::make([]);

        $sequence_id = $this->getSequenceId();
        try {
            $flags = $this->client->getConnection()->flags([$sequence_id], $this->sequence === IMAP::ST_UID);
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
     * @return $this
     * @throws Exceptions\ConnectionFailedException
     * @throws Exceptions\MessageContentFetchingException
     * @throws InvalidMessageDateException
     * @throws Exceptions\EventNotFoundException
     * @throws MessageFlagException
     * @throws Exceptions\RuntimeException
     */
    public function parseBody() {
        $this->client->openFolder($this->folder_path);

        $sequence_id = $this->getSequenceId();
        try {
            $contents = $this->client->getConnection()->content([$sequence_id], "RFC822", $this->sequence === IMAP::ST_UID);
        } catch (Exceptions\RuntimeException $e) {
            throw new MessageContentFetchingException("failed to fetch content", 0);
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
     * Handle auto "Seen" flag handling
     *
     * @throws Exceptions\ConnectionFailedException
     * @throws Exceptions\EventNotFoundException
     * @throws MessageFlagException
     * @throws Exceptions\RuntimeException
     */
    public function peek(){
        if ($this->fetch_options == IMAP::FT_PEEK) {
            if ($this->getFlags()->get("seen") == null) {
                $this->unsetFlag("Seen");
            }
        }elseif ($this->getFlags()->get("seen") != null) {
            $this->setFlag("Seen");
        }
    }

    /**
     * Parse a given message body
     * @param string $raw_body
     *
     * @return $this
     * @throws Exceptions\ConnectionFailedException
     * @throws InvalidMessageDateException
     * @throws MessageContentFetchingException
     * @throws Exceptions\RuntimeException
     */
    public function parseRawBody($raw_body) {
        $this->structure = new Structure($raw_body, $this->header);
        $this->fetchStructure($this->structure);

        return $this;
    }

    /**
     * Fetch the Message structure
     * @param Structure $structure
     *
     * @throws Exceptions\ConnectionFailedException
     * @throws Exceptions\RuntimeException
     */
    private function fetchStructure($structure) {
        $this->client->openFolder($this->folder_path);

        foreach ($structure->parts as $part) {
            $this->fetchPart($part);
        }
    }

    /**
     * Fetch a given part
     * @param Part $part
     */
    private function fetchPart(Part $part) {
        if ($part->isAttachment()) {
            $this->fetchAttachment($part);
        }else{
            $encoding = $this->getEncoding($part);

            $content = $this->decodeString($part->content, $part->encoding);

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
                $content = $this->convertEncoding($content, $encoding);
            }

            $subtype = strtolower($part->subtype);
            $subtype = $subtype == "plain" || $subtype == "" ? "text" : $subtype;

            if (isset($this->bodies[$subtype])) {
                $this->bodies[$subtype] .= "\n".$content;
            }else{
                $this->bodies[$subtype] = $content;
            }
        }
    }

    /**
     * Fetch the Message attachment
     * @param Part $part
     */
    protected function fetchAttachment($part) {
        $oAttachment = new Attachment($this, $part);

        if ($oAttachment->getName() !== null && $oAttachment->getSize() > 0) {
            if ($oAttachment->getId() !== null) {
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
     * @return $this
     */
    public function setFetchOption($option) {
        if (is_long($option) === true) {
            $this->fetch_options = $option;
        } elseif (is_null($option) === true) {
            $config = ClientManager::get('options.fetch', IMAP::FT_UID);
            $this->fetch_options = is_long($config) ? $config : 1;
        }

        return $this;
    }

    /**
     * Set the sequence type
     * @param int $sequence
     *
     * @return $this
     */
    public function setSequence($sequence) {
        if (is_long($sequence)) {
            $this->sequence = $sequence;
        } elseif (is_null($sequence)) {
            $config = ClientManager::get('options.sequence', IMAP::ST_MSGN);
            $this->sequence = is_long($config) ? $config : IMAP::ST_MSGN;
        }

        return $this;
    }

    /**
     * Fail proof setter for $fetch_body
     * @param $option
     *
     * @return $this
     */
    public function setFetchBodyOption($option) {
        if (is_bool($option)) {
            $this->fetch_body = $option;
        } elseif (is_null($option)) {
            $config = ClientManager::get('options.fetch_body', true);
            $this->fetch_body = is_bool($config) ? $config : true;
        }

        return $this;
    }

    /**
     * Fail proof setter for $fetch_flags
     * @param $option
     *
     * @return $this
     */
    public function setFetchFlagsOption($option) {
        if (is_bool($option)) {
            $this->fetch_flags = $option;
        } elseif (is_null($option)) {
            $config = ClientManager::get('options.fetch_flags', true);
            $this->fetch_flags = is_bool($config) ? $config : true;
        }

        return $this;
    }

    /**
     * Decode a given string
     * @param $string
     * @param $encoding
     *
     * @return string
     */
    public function decodeString($string, $encoding) {
        switch ($encoding) {
            case IMAP::MESSAGE_ENC_BINARY:
                if (extension_loaded('imap')) {
                    return base64_decode(\imap_binary($string));
                }
                return base64_decode($string);
            case IMAP::MESSAGE_ENC_BASE64:
                return base64_decode($string);
            case IMAP::MESSAGE_ENC_QUOTED_PRINTABLE:
                return quoted_printable_decode($string);
            case IMAP::MESSAGE_ENC_8BIT:
            case IMAP::MESSAGE_ENC_7BIT:
            case IMAP::MESSAGE_ENC_OTHER:
            default:
                return $string;
        }
    }

    /**
     * Convert the encoding
     * @param $str
     * @param string $from
     * @param string $to
     *
     * @return mixed|string
     */
    public function convertEncoding($str, $from = "ISO-8859-2", $to = "UTF-8") {

        $from = EncodingAliases::get($from);
        $to = EncodingAliases::get($to);

        if ($from === $to) {
            return $str;
        }

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
        if (strtolower($from) == 'us-ascii' && $to == 'UTF-8') {
            return $str;
        }

        if (function_exists('iconv') && $from != 'UTF-7' && $to != 'UTF-7') {
            return @iconv($from, $to.'//IGNORE', $str);
        } else {
            if (!$from) {
                return mb_convert_encoding($str, $to);
            }
            return mb_convert_encoding($str, $to, $from);
        }
    }

    /**
     * Get the encoding of a given abject
     * @param object|string $structure
     *
     * @return string
     */
    public function getEncoding($structure) {
        if (property_exists($structure, 'parameters')) {
            foreach ($structure->parameters as $parameter) {
                if (strtolower($parameter->attribute) == "charset") {
                    return EncodingAliases::get($parameter->value);
                }
            }
        }elseif (property_exists($structure, 'charset')){
            return EncodingAliases::get($structure->charset);
        }elseif (is_string($structure) === true){
            return mb_detect_encoding($structure);
        }

        return 'UTF-8';
    }

    /**
     * Get the messages folder
     *
     * @return mixed
     * @throws Exceptions\ConnectionFailedException
     * @throws Exceptions\FolderFetchingException
     * @throws Exceptions\RuntimeException
     */
    public function getFolder(){
        return $this->client->getFolderByPath($this->folder_path);
    }

    /**
     * Create a message thread based on the current message
     * @param Folder|null $sent_folder
     * @param MessageCollection|null $thread
     * @param Folder|null $folder
     *
     * @return MessageCollection|null
     * @throws Exceptions\ConnectionFailedException
     * @throws Exceptions\FolderFetchingException
     * @throws Exceptions\GetMessagesFailedException
     * @throws Exceptions\RuntimeException
     */
    public function thread($sent_folder = null, &$thread = null, $folder = null){
        $thread = $thread ? $thread : MessageCollection::make([]);
        $folder = $folder ? $folder :  $this->getFolder();
        $sent_folder = $sent_folder ? $sent_folder : $this->client->getFolderByPath(ClientManager::get("options.common_folders.sent", "INBOX/Sent"));

        /** @var Message $message */
        foreach($thread as $message) {
            if ($message->message_id->first() == $this->message_id->first()) {
                return $thread;
            }
        }
        $thread->push($this);

        $this->fetchThreadByInReplyTo($thread, $this->message_id, $folder, $folder, $sent_folder);
        $this->fetchThreadByInReplyTo($thread, $this->message_id, $sent_folder, $folder, $sent_folder);

        if (is_array($this->in_reply_to)) {
            foreach($this->in_reply_to as $in_reply_to) {
                $this->fetchThreadByMessageId($thread, $in_reply_to, $folder, $folder, $sent_folder);
                $this->fetchThreadByMessageId($thread, $in_reply_to, $sent_folder, $folder, $sent_folder);
            }
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
     * @throws Exceptions\ConnectionFailedException
     * @throws Exceptions\GetMessagesFailedException
     * @throws Exceptions\RuntimeException
     */
    protected function fetchThreadByInReplyTo(&$thread, $in_reply_to, $primary_folder, $secondary_folder, $sent_folder){
        $primary_folder->query()->inReplyTo($in_reply_to)
        ->setFetchBody($this->getFetchBodyOption())
        ->leaveUnread()->get()->each(function($message) use(&$thread, $secondary_folder, $sent_folder){
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
     * @throws Exceptions\ConnectionFailedException
     * @throws Exceptions\GetMessagesFailedException
     * @throws Exceptions\RuntimeException
     */
    protected function fetchThreadByMessageId(&$thread, $message_id, $primary_folder, $secondary_folder, $sent_folder){
        $primary_folder->query()->messageId($message_id)
        ->setFetchBody($this->getFetchBodyOption())
        ->leaveUnread()->get()->each(function($message) use(&$thread, $secondary_folder, $sent_folder){
            /** @var Message $message */
            $message->thread($sent_folder, $thread, $secondary_folder);
        });
    }

    /**
     * Copy the current Messages to a mailbox
     * @param string $folder_path
     * @param boolean $expunge
     *
     * @return null|Message
     * @throws Exceptions\ConnectionFailedException
     * @throws Exceptions\FolderFetchingException
     * @throws Exceptions\RuntimeException
     * @throws InvalidMessageDateException
     * @throws MessageContentFetchingException
     * @throws MessageHeaderFetchingException
     * @throws Exceptions\EventNotFoundException
     * @throws MessageFlagException
     * @throws Exceptions\MessageNotFoundException
     */
    public function copy($folder_path, $expunge = false) {
        $this->client->openFolder($folder_path);
        $status = $this->client->getConnection()->examineFolder($folder_path);

        if (isset($status["uidnext"])) {
            $next_uid = $status["uidnext"];

            /** @var Folder $folder */
            $folder = $this->client->getFolderByPath($folder_path);

            $this->client->openFolder($this->folder_path);
            if ($this->client->getConnection()->copyMessage($folder->path, $this->getSequenceId(), null, $this->sequence === IMAP::ST_UID) == true) {
                return $this->fetchNewMail($folder, $next_uid, "copied", $expunge);
            }
        }

        return null;
    }

    /**
     * Move the current Messages to a mailbox
     * @param string $folder_path
     * @param boolean $expunge
     *
     * @return Message|null
     * @throws Exceptions\ConnectionFailedException
     * @throws Exceptions\FolderFetchingException
     * @throws Exceptions\RuntimeException
     * @throws InvalidMessageDateException
     * @throws MessageContentFetchingException
     * @throws MessageHeaderFetchingException
     * @throws Exceptions\EventNotFoundException
     * @throws MessageFlagException
     * @throws Exceptions\MessageNotFoundException
     */
    public function move($folder_path, $expunge = false) {
        $this->client->openFolder($folder_path);
        $status = $this->client->getConnection()->examineFolder($folder_path);

        if (isset($status["uidnext"])) {
            $next_uid = $status["uidnext"];

            /** @var Folder $folder */
            $folder = $this->client->getFolderByPath($folder_path);

            $this->client->openFolder($this->folder_path);
            if ($this->client->getConnection()->moveMessage($folder->path, $this->getSequenceId(), null, $this->sequence === IMAP::ST_UID) == true) {
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
     * @return mixed
     * @throws Exceptions\ConnectionFailedException
     * @throws Exceptions\EventNotFoundException
     * @throws Exceptions\MessageNotFoundException
     * @throws Exceptions\RuntimeException
     * @throws InvalidMessageDateException
     * @throws MessageContentFetchingException
     * @throws MessageFlagException
     * @throws MessageHeaderFetchingException
     */
    protected function fetchNewMail($folder, $next_uid, $event, $expunge){
        if($expunge) $this->client->expunge();

        $this->client->openFolder($folder->path);

        if ($this->sequence === IMAP::ST_UID) {
            $sequence_id = $next_uid;
        }else{
            $sequence_id = $this->client->getConnection()->getMessageNumber($next_uid);
        }

        $message = $folder->query()->getMessage($sequence_id, null, $this->sequence);
        $event = $this->getEvent("message", $event);
        $event::dispatch($this, $message);

        return $message;
    }

    /**
     * Delete the current Message
     * @param bool $expunge
     *
     * @return bool
     * @throws Exceptions\ConnectionFailedException
     * @throws Exceptions\EventNotFoundException
     * @throws MessageFlagException
     * @throws Exceptions\RuntimeException
     */
    public function delete($expunge = true) {
        $status = $this->setFlag("Deleted");
        if($expunge) $this->client->expunge();

        $event = $this->getEvent("message", "deleted");
        $event::dispatch($this);

        return $status;
    }

    /**
     * Restore a deleted Message
     * @param boolean $expunge
     *
     * @return bool
     * @throws Exceptions\ConnectionFailedException
     * @throws Exceptions\EventNotFoundException
     * @throws MessageFlagException
     * @throws Exceptions\RuntimeException
     */
    public function restore($expunge = true) {
        $status = $this->unsetFlag("Deleted");
        if($expunge) $this->client->expunge();

        $event = $this->getEvent("message", "restored");
        $event::dispatch($this);

        return $status;
    }

    /**
     * Set a given flag
     * @param string|array $flag
     *
     * @return bool
     * @throws Exceptions\ConnectionFailedException
     * @throws MessageFlagException
     * @throws Exceptions\EventNotFoundException
     * @throws Exceptions\RuntimeException
     */
    public function setFlag($flag) {
        $this->client->openFolder($this->folder_path);
        $flag = "\\".trim(is_array($flag) ? implode(" \\", $flag) : $flag);
        $sequence_id = $this->getSequenceId();
        try {
            $status = $this->client->getConnection()->store([$flag], $sequence_id, $sequence_id, "+", true, $this->sequence === IMAP::ST_UID);
        } catch (Exceptions\RuntimeException $e) {
            throw new MessageFlagException("flag could not be set", 0, $e);
        }
        $this->parseFlags();

        $event = $this->getEvent("flag", "new");
        $event::dispatch($this, $flag);

        return $status;
    }

    /**
     * Unset a given flag
     * @param string|array $flag
     *
     * @return bool
     * @throws Exceptions\ConnectionFailedException
     * @throws Exceptions\EventNotFoundException
     * @throws MessageFlagException
     * @throws Exceptions\RuntimeException
     */
    public function unsetFlag($flag) {
        $this->client->openFolder($this->folder_path);

        $flag = "\\".trim(is_array($flag) ? implode(" \\", $flag) : $flag);
        $sequence_id = $this->getSequenceId();
        try {
            $status = $this->client->getConnection()->store([$flag], $sequence_id, $sequence_id, "-", true, $this->sequence === IMAP::ST_UID);
        } catch (Exceptions\RuntimeException $e) {
            throw new MessageFlagException("flag could not be removed", 0, $e);
        }
        $this->parseFlags();

        $event = $this->getEvent("flag", "deleted");
        $event::dispatch($this, $flag);

        return $status;
    }

    /**
     * Set a given flag
     * @param string|array $flag
     *
     * @return bool
     * @throws Exceptions\ConnectionFailedException
     * @throws MessageFlagException
     * @throws Exceptions\EventNotFoundException
     * @throws Exceptions\RuntimeException
     */
    public function addFlag($flag) {
        return $this->setFlag($flag);
    }

    /**
     * Unset a given flag
     * @param string|array $flag
     *
     * @return bool
     * @throws Exceptions\ConnectionFailedException
     * @throws Exceptions\EventNotFoundException
     * @throws MessageFlagException
     * @throws Exceptions\RuntimeException
     */
    public function removeFlag($flag) {
        return $this->unsetFlag($flag);
    }

    /**
     * Get all message attachments.
     *
     * @return AttachmentCollection
     */
    public function getAttachments() {
        return $this->attachments;
    }

    /**
     * Get all message attachments.
     *
     * @return AttachmentCollection
     */
    public function attachments(){
        return $this->getAttachments();
    }

    /**
     * Checks if there are any attachments present
     *
     * @return boolean
     */
    public function hasAttachments() {
        return $this->attachments->isEmpty() === false;
    }

    /**
     * Get the raw body
     *
     * @return string
     * @throws Exceptions\ConnectionFailedException
     * @throws Exceptions\RuntimeException
     */
    public function getRawBody() {
        if ($this->raw_body === null) {
            $this->client->openFolder($this->folder_path);

            $this->raw_body = $this->structure->raw;
        }

        return $this->raw_body;
    }

    /**
     * Get the message header
     *
     * @return Header
     */
    public function getHeader() {
        return $this->header;
    }

    /**
     * Get the current client
     *
     * @return Client
     */
    public function getClient() {
        return $this->client;
    }

    /**
     * Get the used fetch option
     *
     * @return integer
     */
    public function getFetchOptions() {
        return $this->fetch_options;
    }

    /**
     * Get the used fetch body option
     *
     * @return boolean
     */
    public function getFetchBodyOption() {
        return $this->fetch_body;
    }

    /**
     * Get the used fetch flags option
     *
     * @return boolean
     */
    public function getFetchFlagsOption() {
        return $this->fetch_flags;
    }

    /**
     * Get all available bodies
     *
     * @return array
     */
    public function getBodies() {
        return $this->bodies;
    }

    /**
     * Get all set flags
     *
     * @return FlagCollection
     */
    public function getFlags() {
        return $this->flags;
    }

    /**
     * Get all set flags
     *
     * @return FlagCollection
     */
    public function flags(){
        return $this->getFlags();
    }

    /**
     * Get the fetched structure
     *
     * @return Structure|null
     */
    public function getStructure(){
        return $this->structure;
    }

    /**
     * Check if a message matches an other by comparing basic attributes
     *
     * @param  null|Message $message
     * @return boolean
     */
    public function is(Message $message = null) {
        if (is_null($message)) {
            return false;
        }

        return $this->uid == $message->uid
            && $this->message_id->first() == $message->message_id->first()
            && $this->subject->first() == $message->subject->first()
            && $this->date->toDate()->eq($message->date);
    }

    /**
     * Get all message attributes
     *
     * @return array
     */
    public function getAttributes(){
        return array_merge($this->attributes, $this->header->getAttributes());
    }

    /**
     * Set the message mask
     * @param $mask
     *
     * @return $this
     */
    public function setMask($mask){
        if(class_exists($mask)){
            $this->mask = $mask;
        }

        return $this;
    }

    /**
     * Get the used message mask
     *
     * @return string
     */
    public function getMask(){
        return $this->mask;
    }

    /**
     * Get a masked instance by providing a mask name
     * @param string|null $mask
     *
     * @return mixed
     * @throws MaskNotFoundException
     */
    public function mask($mask = null){
        $mask = $mask !== null ? $mask : $this->mask;
        if(class_exists($mask)){
            return new $mask($this);
        }

        throw new MaskNotFoundException("Unknown mask provided: ".$mask);
    }

    /**
     * Get the message path aka folder path
     *
     * @return string
     */
    public function getFolderPath(){
        return $this->folder_path;
    }

    /**
     * Set the message path aka folder path
     * @param $folder_path
     *
     * @return $this
     */
    public function setFolderPath($folder_path){
        $this->folder_path = $folder_path;

        return $this;
    }

    /**
     * Set the config
     * @param $config
     *
     * @return $this
     */
    public function setConfig($config){
        $this->config = $config;

        return $this;
    }

    /**
     * Set the available flags
     * @param $available_flags
     *
     * @return $this
     */
    public function setAvailableFlags($available_flags){
        $this->available_flags = $available_flags;

        return $this;
    }

    /**
     * Set the attachment collection
     * @param $attachments
     *
     * @return $this
     */
    public function setAttachments($attachments){
        $this->attachments = $attachments;

        return $this;
    }

    /**
     * Set the flag collection
     * @param $flags
     *
     * @return $this
     */
    public function setFlags($flags){
        $this->flags = $flags;

        return $this;
    }

    /**
     * Set the client
     * @param $client
     *
     * @return $this
     * @throws Exceptions\RuntimeException
     * @throws Exceptions\ConnectionFailedException
     */
    public function setClient($client){
        $this->client = $client;
        $this->client->openFolder($this->folder_path);

        return $this;
    }

    /**
     * Set the message number
     * @param int $uid
     *
     * @return $this
     * @throws Exceptions\MessageNotFoundException
     * @throws Exceptions\ConnectionFailedException
     */
    public function setUid($uid){
        $this->uid = $uid;
        $this->msgn = $this->client->getConnection()->getMessageNumber($this->uid);
        $this->msglist = null;

        return $this;
    }

    /**
     * Set the message number
     * @param $msgn
     * @param int|null $msglist
     *
     * @return $this
     * @throws Exceptions\MessageNotFoundException
     * @throws Exceptions\ConnectionFailedException
     */
    public function setMsgn($msgn, $msglist = null){
        $this->msgn = $msgn;
        $this->msglist = $msglist;
        $this->uid = $this->client->getConnection()->getUid($this->msgn);

        return $this;
    }

    /**
     * Get the current sequence type
     *
     * @return int
     */
    public function getSequence(){
        return $this->sequence;
    }

    /**
     * Set the sequence type
     *
     * @return int
     */
    public function getSequenceId(){
        return $this->sequence === IMAP::ST_UID ? $this->uid : $this->msgn;
    }

    /**
     * Set the sequence id
     * @param $uid
     * @param int|null $msglist
     *
     * @throws Exceptions\ConnectionFailedException
     * @throws Exceptions\MessageNotFoundException
     */
    public function setSequenceId($uid, $msglist = null){
        if ($this->getSequence() === IMAP::ST_UID) {
            $this->setUid($uid);
            $this->setMsglist($msglist);
        }else{
            $this->setMsgn($uid, $msglist);
        }
    }
}
