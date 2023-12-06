<?php
/*
* File: ImapProtocol.php
* Category: Protocol
* Author: M.Goldenbaum
* Created: 16.09.20 18:27
* Updated: -
*
* Description:
*  -
*/

namespace Webklex\PHPIMAP\Connection\Protocols;

use Exception;
use Webklex\PHPIMAP\Exceptions\AuthFailedException;
use Webklex\PHPIMAP\Exceptions\ConnectionFailedException;
use Webklex\PHPIMAP\Exceptions\InvalidMessageDateException;
use Webklex\PHPIMAP\Exceptions\MessageNotFoundException;
use Webklex\PHPIMAP\Exceptions\RuntimeException;
use Webklex\PHPIMAP\Header;

/**
 * Class ImapProtocol
 *
 * @package Webklex\PHPIMAP\Connection\Protocols
 */
class ImapProtocol extends Protocol {

    /**
     * Request noun
     * @var int
     */
    protected $noun = 0;

    /**
     * Imap constructor.
     * @param bool $cert_validation set to false to skip SSL certificate validation
     * @param mixed $encryption Connection encryption method
     */
    public function __construct($cert_validation = true, $encryption = false) {
        $this->setCertValidation($cert_validation);
        $this->encryption = $encryption;
    }

    /**
     * Public destructor
     */
    public function __destruct() {
        $this->logout();
    }

    /**
     * Open connection to IMAP server
     * @param string $host hostname or IP address of IMAP server
     * @param int|null $port of IMAP server, default is 143 and 993 for ssl
     *
     * @throws ConnectionFailedException
     */
    public function connect($host, $port = null) {
        $transport = 'tcp';
        $encryption = "";

        if ($this->encryption) {
            $encryption = strtolower($this->encryption);
            if ($encryption == "ssl") {
                $transport = 'ssl';
                $port = $port === null ? 993 : $port;
            }
        }
        $port = $port === null ? 143 : $port;
        try {
            $this->stream = $this->createStream($transport, $host, $port, $this->connection_timeout);
            if (!$this->assumedNextLine('* OK')) {
                throw new ConnectionFailedException('connection refused');
            }
            if ($encryption == "tls") {
                $this->enableTls();
            }
        } catch (Exception $e) {
            throw new ConnectionFailedException('connection failed', 0, $e);
        }
    }

    /**
     * Enable tls on the current connection
     *
     * @throws ConnectionFailedException
     * @throws RuntimeException
     */
    protected function enableTls(){
        $response = $this->requestAndResponse('STARTTLS');
        $result = $response && stream_socket_enable_crypto($this->stream, true, $this->getCryptoMethod());
        if (!$result) {
            throw new ConnectionFailedException('failed to enable TLS');
        }
    }

    /**
     * Get the next line from stream
     *
     * @return string next line
     * @throws RuntimeException
     */
    public function nextLine() {
        $line = fgets($this->stream);

        if ($line === false) {
            throw new RuntimeException('failed to read - connection closed?');
        }

        return $line;
    }

    /**
     * Get the next line and check if it starts with a given string
     * @param string $start
     *
     * @return bool
     * @throws RuntimeException
     */
    protected function assumedNextLine($start) {
        $line = $this->nextLine();
        return strpos($line, $start) === 0;
    }

    /**
     * Get the next line and split the tag
     * @param string $tag reference tag
     *
     * @return string next line
     * @throws RuntimeException
     */
    protected function nextTaggedLine(&$tag) {
        $line = $this->nextLine();
        list($tag, $line) = explode(' ', $line, 2);

        return $line;
    }

    /**
     * Split a given line in values. A value is literal of any form or a list
     * @param string $line
     *
     * @return array
     * @throws RuntimeException
     */
    protected function decodeLine($line) {
        $tokens = [];
        $stack = [];

        //  replace any trailing <NL> including spaces with a single space
        $line = rtrim($line) . ' ';
        while (($pos = strpos($line, ' ')) !== false) {
            $token = substr($line, 0, $pos);
            if (!strlen($token)) {
                continue;
            }
            while ($token[0] == '(') {
                array_push($stack, $tokens);
                $tokens = [];
                $token = substr($token, 1);
            }
            if ($token[0] == '"') {
                if (preg_match('%^\(*"((.|\\\\|\\")*?)" *%', $line, $matches)) {
                    $tokens[] = $matches[1];
                    $line = substr($line, strlen($matches[0]));
                    continue;
                }
            }
            if ($token[0] == '{') {
                $endPos = strpos($token, '}');
                $chars = substr($token, 1, $endPos - 1);
                if (is_numeric($chars)) {
                    $token = '';
                    while (strlen($token) < $chars) {
                        $token .= $this->nextLine();
                    }
                    $line = '';
                    if (strlen($token) > $chars) {
                        $line = substr($token, $chars);
                        $token = substr($token, 0, $chars);
                    } else {
                        $line .= $this->nextLine();
                    }
                    $tokens[] = $token;
                    $line = trim($line) . ' ';
                    continue;
                }
            }
            if ($stack && $token[strlen($token) - 1] == ')') {
                // closing braces are not separated by spaces, so we need to count them
                $braces = strlen($token);
                $token = rtrim($token, ')');
                // only count braces if more than one
                $braces -= strlen($token) + 1;
                // only add if token had more than just closing braces
                if (rtrim($token) != '') {
                    $tokens[] = rtrim($token);
                }
                $token = $tokens;
                $tokens = array_pop($stack);
                // special handline if more than one closing brace
                while ($braces-- > 0) {
                    $tokens[] = $token;
                    $token = $tokens;
                    $tokens = array_pop($stack);
                }
            }
            $tokens[] = $token;
            $line = substr($line, $pos + 1);
        }

        // maybe the server forgot to send some closing braces
        while ($stack) {
            $child = $tokens;
            $tokens = array_pop($stack);
            $tokens[] = $child;
        }

        return $tokens;
    }

    /**
     * Read abd decode a response "line"
     * @param array|string $tokens to decode
     * @param string $wantedTag targeted tag
     * @param bool $dontParse if true only the unparsed line is returned in $tokens
     *
     * @return bool
     * @throws RuntimeException
     */
    public function readLine(&$tokens = [], $wantedTag = '*', $dontParse = false) {
        $line = $this->nextTaggedLine($tag); // get next tag
        if (!$dontParse) {
            $tokens = $this->decodeLine($line);
        } else {
            $tokens = $line;
        }
        if ($this->debug) echo "<< ".$line."\n";

        // if tag is wanted tag we might be at the end of a multiline response
        return $tag == $wantedTag;
    }

    /**
     * Read all lines of response until given tag is found
     * @param string $tag request tag
     * @param bool $dontParse if true every line is returned unparsed instead of the decoded tokens
     *
     * @return void|null|bool|array tokens if success, false if error, null if bad request
     * @throws RuntimeException
     */
    public function readResponse($tag, $dontParse = false) {
        $lines = [];
        $tokens = null; // define $tokens variable before first use
        do {
            $readAll = $this->readLine($tokens, $tag, $dontParse);
            $lines[] = $tokens;
        } while (!$readAll);

        if ($dontParse) {
            // First two chars are still needed for the response code
            $tokens = [substr($tokens, 0, 2)];
        }

        // last line has response code
        if ($tokens[0] == 'OK') {
            return $lines ? $lines : true;
        } elseif ($tokens[0] == 'NO') {
            return false;
        }

        return;
    }

    /**
     * Send a new request
     * @param string $command
     * @param array $tokens additional parameters to command, use escapeString() to prepare
     * @param string $tag provide a tag otherwise an autogenerated is returned
     *
     * @throws RuntimeException
     */
    public function sendRequest($command, $tokens = [], &$tag = null) {
        if (!$tag) {
            $this->noun++;
            $tag = 'TAG' . $this->noun;
        }

        $line = $tag . ' ' . $command;

        foreach ($tokens as $token) {
            if (is_array($token)) {
                if (fwrite($this->stream, $line . ' ' . $token[0] . "\r\n") === false) {
                    throw new RuntimeException('failed to write - connection closed?');
                }
                if (!$this->assumedNextLine('+ ')) {
                    throw new RuntimeException('failed to send literal string');
                }
                $line = $token[1];
            } else {
                $line .= ' ' . $token;
            }
        }
        if ($this->debug) echo ">> ".$line."\n";

        if (fwrite($this->stream, $line . "\r\n") === false) {
            throw new RuntimeException('failed to write - connection closed?');
        }
    }

    /**
     * Send a request and get response at once
     * @param string $command
     * @param array $tokens parameters as in sendRequest()
     * @param bool $dontParse if true unparsed lines are returned instead of tokens
     *
     * @return void|null|bool|array response as in readResponse()
     * @throws RuntimeException
     */
    public function requestAndResponse($command, $tokens = [], $dontParse = false) {
        $this->sendRequest($command, $tokens, $tag);

        return $this->readResponse($tag, $dontParse);
    }

    /**
     * Escape one or more literals i.e. for sendRequest
     * @param string|array $string the literal/-s
     *
     * @return string|array escape literals, literals with newline ar returned
     *                      as array('{size}', 'string');
     */
    public function escapeString($string) {
        if (func_num_args() < 2) {
            if (strpos($string, "\n") !== false) {
                return ['{' . strlen($string) . '}', $string];
            } else {
                return '"' . str_replace(['\\', '"'], ['\\\\', '\\"'], $string) . '"';
            }
        }
        $result = [];
        foreach (func_get_args() as $string) {
            $result[] = $this->escapeString($string);
        }
        return $result;
    }

    /**
     * Escape a list with literals or lists
     * @param array $list list with literals or lists as PHP array
     *
     * @return string escaped list for imap
     */
    public function escapeList($list) {
        $result = [];
        foreach ($list as $v) {
            if (!is_array($v)) {
                $result[] = $v;
                continue;
            }
            $result[] = $this->escapeList($v);
        }
        return '(' . implode(' ', $result) . ')';
    }

    /**
     * Login to a new session.
     * @param string $user username
     * @param string $password password
     *
     * @return bool|mixed
     * @throws AuthFailedException
     */
    public function login($user, $password) {
        try {
            return $this->requestAndResponse('LOGIN', $this->escapeString($user, $password), true);
        } catch (RuntimeException $e) {
            throw new AuthFailedException("failed to authenticate", 0, $e);
        }
    }

    /**
     * Authenticate your current IMAP session.
     * @param string $user username
     * @param string $token access token
     *
     * @return bool
     * @throws AuthFailedException
     */
    public function authenticate($user, $token) {
        try {
            $authenticateParams = ['XOAUTH2', base64_encode("user=$user\1auth=Bearer $token\1\1")];
            $this->sendRequest('AUTHENTICATE', $authenticateParams);

            while (true) {
                $response = "";
                $is_plus = $this->readLine($response, '+', true);
                if ($is_plus) {
                    // try to log the challenge somewhere where it can be found
                    error_log("got an extra server challenge: $response");
                    // respond with an empty response.
                    $this->sendRequest('');
                } else {
                    if (preg_match('/^NO /i', $response) ||
                        preg_match('/^BAD /i', $response)) {
                        error_log("got failure response: $response");
                        return false;
                    } else if (preg_match("/^OK /i", $response)) {
                        return true;
                    }
                }
            }
        } catch (RuntimeException $e) {
            throw new AuthFailedException("failed to authenticate", 0, $e);
        }
        return false;
    }

    /**
     * Logout of imap server
     *
     * @return bool success
     */
    public function logout() {
        $result = false;
        if ($this->stream) {
            try {
                $result = $this->requestAndResponse('LOGOUT', [], true);
            } catch (Exception $e) {}
            fclose($this->stream);
            $this->stream = null;
        }
        return $result;
    }

    /**
     * Check if the current session is connected
     *
     * @return bool
     */
    public function connected(){
        return (boolean) $this->stream;
    }

    /**
     * Get an array of available capabilities
     *
     * @return array list of capabilities
     * @throws RuntimeException
     */
    public function getCapabilities() {
        $response = $this->requestAndResponse('CAPABILITY');

        if (!$response) return [];

        $capabilities = [];
        foreach ($response as $line) {
            $capabilities = array_merge($capabilities, $line);
        }
        return $capabilities;
    }

    /**
     * Examine and select have the same response.
     * @param string $command can be 'EXAMINE' or 'SELECT'
     * @param string $folder target folder
     *
     * @return bool|array
     * @throws RuntimeException
     */
    public function examineOrSelect($command = 'EXAMINE', $folder = 'INBOX') {
        $this->sendRequest($command, [$this->escapeString($folder)], $tag);

        $result = [];
        $tokens = null; // define $tokens variable before first use
        while (!$this->readLine($tokens, $tag)) {
            if ($tokens[0] == 'FLAGS') {
                array_shift($tokens);
                $result['flags'] = $tokens;
                continue;
            }
            switch ($tokens[1]) {
                case 'EXISTS':
                case 'RECENT':
                    $result[strtolower($tokens[1])] = $tokens[0];
                    break;
                case '[UIDVALIDITY':
                    $result['uidvalidity'] = (int)$tokens[2];
                    break;
                case '[UIDNEXT':
                    $result['uidnext'] = (int)$tokens[2];
                    break;
                default:
                    // ignore
                    break;
            }
        }

        if ($tokens[0] != 'OK') {
            return false;
        }
        return $result;
    }

    /**
     * Change the current folder
     * @param string $folder change to this folder
     *
     * @return bool|array see examineOrselect()
     * @throws RuntimeException
     */
    public function selectFolder($folder = 'INBOX') {
        return $this->examineOrSelect('SELECT', $folder);
    }

    /**
     * Examine a given folder
     * @param string $folder examine this folder
     *
     * @return bool|array see examineOrselect()
     * @throws RuntimeException
     */
    public function examineFolder($folder = 'INBOX') {
        return $this->examineOrSelect('EXAMINE', $folder);
    }

    /**
     * Fetch one or more items of one or more messages
     * @param string|array $items items to fetch [RFC822.HEADER, FLAGS, RFC822.TEXT, etc]
     * @param int|array $from message for items or start message if $to !== null
     * @param int|null $to if null only one message ($from) is fetched, else it's the
     *                             last message, INF means last message available
     * @param bool $uid set to true if passing a unique id
     *
     * @return string|array if only one item of one message is fetched it's returned as string
     *                      if items of one message are fetched it's returned as (name => value)
     *                      if one items of messages are fetched it's returned as (msgno => value)
     *                      if items of messages are fetched it's returned as (msgno => (name => value))
     * @throws RuntimeException
     */
    protected function fetch($items, $from, $to = null, $uid = false) {
        if (is_array($from)) {
            $set = implode(',', $from);
        } elseif ($to === null) {
            $set = (int)$from;
        } elseif ($to === INF) {
            $set = (int)$from . ':*';
        } else {
            $set = (int)$from . ':' . (int)$to;
        }

        $items = (array)$items;
        $itemList = $this->escapeList($items);

        $this->sendRequest(($uid ? 'UID ' : '') . 'FETCH', [$set, $itemList], $tag);

        $result = [];
        $tokens = null; // define $tokens variable before first use
        while (!$this->readLine($tokens, $tag)) {
            // ignore other responses
            if ($tokens[1] != 'FETCH') {
                continue;
            }

            // find array key of UID value; try the last elements, or search for it
            if ($uid) {
                $count = count($tokens[2]);
                if ($tokens[2][$count - 2] == 'UID') {
                    $uidKey = $count - 1;
                } else if ($tokens[2][0] == 'UID') {
                    $uidKey = 1;
                } else {
                    $uidKey = array_search('UID', $tokens[2]) + 1;
                }
            }

            // ignore other messages
            if ($to === null && !is_array($from) && ($uid ? $tokens[2][$uidKey] != $from : $tokens[0] != $from)) {
                continue;
            }

            // if we only want one item we return that one directly
            if (count($items) == 1) {
                if ($tokens[2][0] == $items[0]) {
                    $data = $tokens[2][1];
                } elseif ($uid && $tokens[2][2] == $items[0]) {
                    $data = $tokens[2][3];
                } else {
                    // maybe the server send an other field we didn't wanted
                    $count = count($tokens[2]);
                    // we start with 2, because 0 was already checked
                    for ($i = 2; $i < $count; $i += 2) {
                        if ($tokens[2][$i] != $items[0]) {
                            continue;
                        }
                        $data = $tokens[2][$i + 1];
                        break;
                    }
                }
            } else {
                $data = [];
                while (key($tokens[2]) !== null) {
                    $data[current($tokens[2])] = next($tokens[2]);
                    next($tokens[2]);
                }
            }

            // if we want only one message we can ignore everything else and just return
            if ($to === null && !is_array($from) && ($uid ? $tokens[2][$uidKey] == $from : $tokens[0] == $from)) {
                // we still need to read all lines
                while (!$this->readLine($tokens, $tag))

                    return $data;
            }
            if ($uid) {
                $result[$tokens[2][$uidKey]] = $data;
            }else{
                $result[$tokens[0]] = $data;
            }
        }

        if ($to === null && !is_array($from)) {
            throw new RuntimeException('the single id was not found in response');
        }

        return $result;
    }

    /**
     * Fetch message headers
     * @param array|int $uids
     * @param string $rfc
     * @param bool $uid set to true if passing a unique id
     *
     * @return array
     * @throws RuntimeException
     */
    public function content($uids, $rfc = "RFC822", $uid = false) {
        return $this->fetch(["$rfc.TEXT"], $uids, null, $uid);
    }

    /**
     * Fetch message headers
     * @param array|int $uids
     * @param string $rfc
     * @param bool $uid set to true if passing a unique id
     *
     * @return array
     * @throws RuntimeException
     */
    public function headers($uids, $rfc = "RFC822", $uid = false){
        return $this->fetch(["$rfc.HEADER"], $uids, null, $uid);
    }

    /**
     * Fetch message flags
     * @param array|int $uids
     * @param bool $uid set to true if passing a unique id
     *
     * @return array
     * @throws RuntimeException
     */
    public function flags($uids, $uid = false){
        return $this->fetch(["FLAGS"], $uids, null, $uid);
    }

    /**
     * Get uid for a given id
     * @param int|null $id message number
     *
     * @return array|string message number for given message or all messages as array
     * @throws MessageNotFoundException
     */
    public function getUid($id = null) {
        try {
            $uids = $this->fetch('UID', 1, INF);
            if ($id == null) {
                return $uids;
            }

            foreach ($uids as $k => $v) {
                if ($k == $id) {
                    return $v;
                }
            }
        } catch (RuntimeException $e) {}

        throw new MessageNotFoundException('unique id not found');
    }

    /**
     * Get a message number for a uid
     * @param string $id uid
     *
     * @return int message number
     * @throws MessageNotFoundException
     */
    public function getMessageNumber($id) {
        $ids = $this->getUid();
        foreach ($ids as $k => $v) {
            if ($v == $id) {
                return $k;
            }
        }

        throw new MessageNotFoundException('message number not found');
    }

    /**
     * Get a list of available folders
     * @param string $reference mailbox reference for list
     * @param string $folder mailbox name match with wildcards
     *
     * @return array folders that matched $folder as array(name => array('delimiter' => .., 'flags' => ..))
     * @throws RuntimeException
     */
    public function folders($reference = '', $folder = '*') {
        $result = [];
        $list = $this->requestAndResponse('LIST', $this->escapeString($reference, $folder));
        if (!$list || $list === true) {
            return $result;
        }

        foreach ($list as $item) {
            if (count($item) != 4 || $item[0] != 'LIST') {
                continue;
            }
            $result[$item[3]] = ['delimiter' => $item[2], 'flags' => $item[1]];
        }

        return $result;
    }

    /**
     * Manage flags
     * @param array $flags flags to set, add or remove - see $mode
     * @param int $from message for items or start message if $to !== null
     * @param int|null $to if null only one message ($from) is fetched, else it's the
     *                             last message, INF means last message available
     * @param string|null $mode '+' to add flags, '-' to remove flags, everything else sets the flags as given
     * @param bool $silent if false the return values are the new flags for the wanted messages
     * @param bool $uid set to true if passing a unique id
     *
     * @return bool|array new flags if $silent is false, else true or false depending on success
     * @throws RuntimeException
     */
    public function store(array $flags, $from, $to = null, $mode = null, $silent = true, $uid = false) {
        $item = 'FLAGS';
        if ($mode == '+' || $mode == '-') {
            $item = $mode . $item;
        }
        if ($silent) {
            $item .= '.SILENT';
        }

        $flags = $this->escapeList($flags);
        $set = (int)$from;
        if ($to !== null) {
            $set .= ':' . ($to == INF ? '*' : (int)$to);
        }

        $command = ($uid ? "UID " : "")."STORE";
        $result = $this->requestAndResponse($command, [$set, $item, $flags], $silent);

        if ($silent) {
            return (bool)$result;
        }

        $tokens = $result;
        $result = [];
        foreach ($tokens as $token) {
            if ($token[1] != 'FETCH' || $token[2][0] != 'FLAGS') {
                continue;
            }
            $result[$token[0]] = $token[2][1];
        }

        return $result;
    }

    /**
     * Append a new message to given folder
     * @param string $folder name of target folder
     * @param string $message full message content
     * @param array $flags flags for new message
     * @param string $date date for new message
     *
     * @return bool success
     * @throws RuntimeException
     */
    public function appendMessage($folder, $message, $flags = null, $date = null) {
        $tokens = [];
        $tokens[] = $this->escapeString($folder);
        if ($flags !== null) {
            $tokens[] = $this->escapeList($flags);
        }
        if ($date !== null) {
            $tokens[] = $this->escapeString($date);
        }
        $tokens[] = $this->escapeString($message);

        return $this->requestAndResponse('APPEND', $tokens, true);
    }

    /**
     * Copy a message set from current folder to an other folder
     * @param string $folder destination folder
     * @param $from
     * @param int|null $to if null only one message ($from) is fetched, else it's the
     *                         last message, INF means last message available
     * @param bool $uid set to true if passing a unique id
     *
     * @return bool success
     * @throws RuntimeException
     */
    public function copyMessage($folder, $from, $to = null, $uid = false) {
        $set = (int)$from;
        if ($to !== null) {
            $set .= ':' . ($to == INF ? '*' : (int)$to);
        }
        $command = ($uid ? "UID " : "")."COPY";

        return $this->requestAndResponse($command, [$set, $this->escapeString($folder)], true);
    }

    /**
     * Copy multiple messages to the target folder
     *
     * @param array<string> $messages List of message identifiers
     * @param string $folder Destination folder
     * @param bool $uid Set to true if you pass message unique identifiers instead of numbers
     * @return array|bool Tokens if operation successful, false if an error occurred
     *
     * @throws RuntimeException
     */
    public function copyManyMessages($messages, $folder, $uid = false) {
        $command = $uid ? 'UID COPY' : 'COPY';

        $set = implode(',', $messages);
        $tokens = [$set, $this->escapeString($folder)];

        return $this->requestAndResponse($command, $tokens, true);
    }

    /**
     * Move a message set from current folder to an other folder
     * @param string $folder destination folder
     * @param $from
     * @param int|null $to if null only one message ($from) is fetched, else it's the
     *                         last message, INF means last message available
     * @param bool $uid set to true if passing a unique id
     *
     * @return bool success
     * @throws RuntimeException
     */
    public function moveMessage($folder, $from, $to = null, $uid = false) {
        $set = (int)$from;
        if ($to !== null) {
            $set .= ':' . ($to == INF ? '*' : (int)$to);
        }
        $command = ($uid ? "UID " : "")."MOVE";

        return $this->requestAndResponse($command, [$set, $this->escapeString($folder)], true);
    }

    /**
     * Move multiple messages to the target folder
     *
     * @param array<string> $messages List of message identifiers
     * @param string $folder Destination folder
     * @param bool $uid Set to true if you pass message unique identifiers instead of numbers
     * @return array|bool Tokens if operation successful, false if an error occurred
     *
     * @throws RuntimeException
     */
    public function moveManyMessages($messages, $folder, $uid = false) {
        $command = $uid ? 'UID MOVE' : 'MOVE';

        $set = implode(',', $messages);
        $tokens = [$set, $this->escapeString($folder)];

        return $this->requestAndResponse($command, $tokens, true);
    }

    /**
     * Create a new folder (and parent folders if needed)
     * @param string $folder folder name
     *
     * @return bool success
     * @throws RuntimeException
     */
    public function createFolder($folder) {
        return $this->requestAndResponse('CREATE', [$this->escapeString($folder)], true);
    }

    /**
     * Rename an existing folder
     * @param string $old old name
     * @param string $new new name
     *
     * @return bool success
     * @throws RuntimeException
     */
    public function renameFolder($old, $new) {
        return $this->requestAndResponse('RENAME', $this->escapeString($old, $new), true);
    }

    /**
     * Delete a folder
     * @param string $folder folder name
     *
     * @return bool success
     * @throws RuntimeException
     */
    public function deleteFolder($folder) {
        return $this->requestAndResponse('DELETE', [$this->escapeString($folder)], true);
    }

    /**
     * Subscribe to a folder
     * @param string $folder folder name
     *
     * @return bool success
     * @throws RuntimeException
     */
    public function subscribeFolder($folder) {
        return $this->requestAndResponse('SUBSCRIBE', [$this->escapeString($folder)], true);
    }

    /**
     * Unsubscribe from a folder
     * @param string $folder folder name
     *
     * @return bool success
     * @throws RuntimeException
     */
    public function unsubscribeFolder($folder) {
        return $this->requestAndResponse('UNSUBSCRIBE', [$this->escapeString($folder)], true);
    }

    /**
     * Apply session saved changes to the server
     *
     * @return bool success
     * @throws RuntimeException
     */
    public function expunge() {
        return $this->requestAndResponse('EXPUNGE');
    }

    /**
     * Send noop command
     *
     * @return bool success
     * @throws RuntimeException
     */
    public function noop() {
        return $this->requestAndResponse('NOOP');
    }

    /**
     * Retrieve the quota level settings, and usage statics per mailbox
     * @param $username
     *
     * @return array
     * @throws RuntimeException
     */
    public function getQuota($username) {
        return $this->requestAndResponse("GETQUOTA", ['"#user/'.$username.'"']);
    }

    /**
     * Retrieve the quota settings per user
     * @param string $quota_root
     *
     * @return array
     * @throws RuntimeException
     */
    public function getQuotaRoot($quota_root = 'INBOX') {
        return $this->requestAndResponse("QUOTA", [$quota_root]);
    }

    /**
     * Send idle command
     *
     * @throws RuntimeException
     */
    public function idle() {
        $this->sendRequest("IDLE");
        if (!$this->assumedNextLine('+ ')) {
            throw new RuntimeException('idle failed');
        }
    }

    /**
     * Send done command
     * @throws RuntimeException
     */
    public function done() {
        if (fwrite($this->stream, "DONE\r\n") === false) {
            throw new RuntimeException('failed to write - connection closed?');
        }
        return true;
    }

    /**
     * Search for matching messages
     * @param array $params
     * @param bool $uid set to true if passing a unique id
     *
     * @return array message ids
     * @throws RuntimeException
     */
    public function search(array $params, $uid = false) {
        $token = $uid == true ? "UID SEARCH" : "SEARCH";
        $response = $this->requestAndResponse($token, $params);
        if (!$response) {
            return $response;
        }

        foreach ($response as $ids) {
            if ($ids[0] == 'SEARCH') {
                array_shift($ids);
                return $ids;
            }
        }
        return [];
    }

    /**
     * Get a message overview
     * @param string $sequence
     * @param bool $uid set to true if passing a unique id
     *
     * @return array
     * @throws RuntimeException
     * @throws MessageNotFoundException
     * @throws InvalidMessageDateException
     */
    public function overview($sequence, $uid = false) {
        $result = [];
        list($from, $to) = explode(":", $sequence);

        $uids = $this->getUid();
        $ids = [];
        foreach ($uids as $msgn => $v) {
            $id = $uid ? $v : $msgn;
            if ( ($to >= $id && $from <= $id) || ($to === "*" && $from <= $id) ){
                $ids[] = $id;
            }
        }
        $headers = $this->headers($ids, "RFC822", $uid);
        foreach ($headers as $id => $raw_header) {
            $result[$id] = (new Header($raw_header, false))->getAttributes();
        }
        return $result;
    }

    /**
     * Enable the debug mode
     */
    public function enableDebug(){
        $this->debug = true;
    }

    /**
     * Disable the debug mode
     */
    public function disableDebug(){
        $this->debug = false;
    }
}
