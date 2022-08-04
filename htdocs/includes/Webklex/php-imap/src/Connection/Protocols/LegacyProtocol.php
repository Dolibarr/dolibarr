<?php
/*
* File: LegacyProtocol.php
* Category: Protocol
* Author: M.Goldenbaum
* Created: 16.09.20 18:27
* Updated: -
*
* Description:
*  -
*/

namespace Webklex\PHPIMAP\Connection\Protocols;

use Webklex\PHPIMAP\ClientManager;
use Webklex\PHPIMAP\Exceptions\AuthFailedException;
use Webklex\PHPIMAP\Exceptions\MethodNotSupportedException;
use Webklex\PHPIMAP\Exceptions\RuntimeException;
use Webklex\PHPIMAP\IMAP;

/**
 * Class LegacyProtocol
 *
 * @package Webklex\PHPIMAP\Connection\Protocols
 */
class LegacyProtocol extends Protocol {

    protected $protocol = "imap";
    protected $host = null;
    protected $port = null;
    protected $encryption = null;

    /**
     * Imap constructor.
     * @param bool $cert_validation set to false to skip SSL certificate validation
     * @param mixed $encryption Connection encryption method
     */
    public function __construct(bool $cert_validation = true, $encryption = false) {
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
     * Save the information for a nw connection
     * @param string $host
     * @param null $port
     */
    public function connect(string $host, $port = null) {
        if ($this->encryption) {
            $encryption = strtolower($this->encryption);
            if ($encryption == "ssl") {
                $port = $port === null ? 993 : $port;
            }
        }
        $port = $port === null ? 143 : $port;
        $this->host = $host;
        $this->port = $port;
    }

    /**
     * Login to a new session.
     * @param string $user username
     * @param string $password password
     *
     * @return bool
     * @throws AuthFailedException
     * @throws RuntimeException
     */
    public function login(string $user, string $password): bool {
        try {
            $this->stream = \imap_open(
                $this->getAddress(),
                $user,
                $password,
                0,
                $attempts = 3,
                ClientManager::get('options.open')
            );
        } catch (\ErrorException $e) {
            $errors = \imap_errors();
            $message = $e->getMessage().'. '.implode("; ", (is_array($errors) ? $errors : array()));
            throw new AuthFailedException($message);
        }

        if(!$this->stream) {
            $errors = \imap_errors();
            $message = implode("; ", (is_array($errors) ? $errors : array()));
            throw new AuthFailedException($message);
        }

        $errors = \imap_errors();
        if(is_array($errors)) {
            $status = $this->examineFolder();
            if($status['exists'] !== 0) {
                $message = implode("; ", (is_array($errors) ? $errors : array()));
                throw new RuntimeException($message);
            }
        }

        return $this->stream !== false;
    }

    /**
     * Authenticate your current session.
     * @param string $user username
     * @param string $token access token
     *
     * @return bool|resource
     * @throws AuthFailedException|RuntimeException
     */
    public function authenticate(string $user, string $token): bool {
        return $this->login($user, $token);
    }

    /**
     * Get full address of mailbox.
     *
     * @return string
     */
    protected function getAddress(): string {
        $address = "{".$this->host.":".$this->port."/".$this->protocol;
        if (!$this->cert_validation) {
            $address .= '/novalidate-cert';
        }
        if (in_array($this->encryption,['tls', 'notls', 'ssl'])) {
            $address .= '/'.$this->encryption;
        } elseif ($this->encryption === "starttls") {
            $address .= '/tls';
        }

        $address .= '}';

        return $address;
    }

    /**
     * Logout of the current session
     *
     * @return bool success
     */
    public function logout(): bool {
        if ($this->stream) {
            $result = \imap_close($this->stream, IMAP::CL_EXPUNGE);
            $this->stream = false;
            $this->uid_cache = null;
            return $result;
        }
        return false;
    }

    /**
     * Check if the current session is connected
     *
     * @return bool
     */
    public function connected(): bool {
        return boolval($this->stream);
    }

    /**
     * Get an array of available capabilities
     *
     * @throws MethodNotSupportedException
     */
    public function getCapabilities(): array {
        throw new MethodNotSupportedException();
    }

    /**
     * Change the current folder
     * @param string $folder change to this folder
     *
     * @return bool|array see examineOrselect()
     * @throws RuntimeException
     */
    public function selectFolder(string $folder = 'INBOX') {
        \imap_reopen($this->stream, $folder, IMAP::OP_READONLY, 3);
        $this->uid_cache = null;
        return $this->examineFolder($folder);
    }

    /**
     * Examine a given folder
     * @param string $folder examine this folder
     *
     * @return bool|array
     * @throws RuntimeException
     */
    public function examineFolder(string $folder = 'INBOX') {
        if (strpos($folder, ".") === 0) {
            throw new RuntimeException("Segmentation fault prevented. Folders starts with an illegal char '.'.");
        }
        $folder = $this->getAddress().$folder;
        $status = \imap_status($this->stream, $folder, IMAP::SA_ALL);
        return [
            "flags" => [],
            "exists" => $status->messages,
            "recent" => $status->recent,
            "unseen" => $status->unseen,
            "uidnext" => $status->uidnext,
        ];
    }

    /**
     * Fetch message content
     * @param array|int $uids
     * @param string $rfc
     * @param int $uid set to IMAP::ST_UID if you pass message unique identifiers instead of numbers.
     *
     * @return array
     */
    public function content($uids, string $rfc = "RFC822", $uid = IMAP::ST_UID): array {
        $result = [];
        $uids = is_array($uids) ? $uids : [$uids];
        foreach ($uids as $id) {
            $result[$id] = \imap_fetchbody($this->stream, $id, "", $uid ? IMAP::ST_UID : IMAP::NIL);
        }
        return $result;
    }

    /**
     * Fetch message headers
     * @param array|int $uids
     * @param string $rfc
     * @param int $uid set to IMAP::ST_UID if you pass message unique identifiers instead of numbers.
     *
     * @return array
     */
    public function headers($uids, string $rfc = "RFC822", $uid = IMAP::ST_UID): array {
        $result = [];
        $uids = is_array($uids) ? $uids : [$uids];
        foreach ($uids as $id) {
            $result[$id] = \imap_fetchheader($this->stream, $id, $uid ? IMAP::ST_UID : IMAP::NIL);
        }
        return $result;
    }

    /**
     * Fetch message flags
     * @param array|int $uids
     * @param int $uid set to IMAP::ST_UID if you pass message unique identifiers instead of numbers.
     *
     * @return array
     */
    public function flags($uids, $uid = IMAP::ST_UID): array {
        $result = [];
        $uids = is_array($uids) ? $uids : [$uids];
        foreach ($uids as $id) {
            $raw_flags = \imap_fetch_overview($this->stream, $id, $uid ? IMAP::ST_UID : IMAP::NIL);
            $flags = [];
            if (is_array($raw_flags) && isset($raw_flags[0])) {
                $raw_flags = (array) $raw_flags[0];
                foreach($raw_flags as $flag => $value) {
                    if ($value === 1 && in_array($flag, ["size", "uid", "msgno", "update"]) === false){
                        $flags[] = "\\".ucfirst($flag);
                    }
                }
            }
            $result[$uid] = $flags;
        }

        return $result;
    }

    /**
     * Get uid for a given id
     * @param int|null $id message number
     *
     * @return array|string message number for given message or all messages as array
     */
    public function getUid($id = null) {
        if ($id === null) {
            if ($this->enable_uid_cache && $this->uid_cache) {
                return $this->uid_cache;
            }

            $overview = $this->overview("1:*");
            $uids = [];
            foreach($overview as $set){
                $uids[$set->msgno] = $set->uid;
            }

            $this->setUidCache($uids);
            return $uids;
        }

        return \imap_uid($this->stream, $id);
    }

    /**
     * Get a message number for a uid
     * @param string $id uid
     *
     * @return int message number
     */
    public function getMessageNumber(string $id): int {
        return \imap_msgno($this->stream, $id);
    }

    /**
     * Get a message overview
     * @param string $sequence uid sequence
     * @param int $uid set to IMAP::ST_UID if you pass message unique identifiers instead of numbers.
     *
     * @return array
     */
    public function overview(string $sequence, $uid = IMAP::ST_UID): array {
        return \imap_fetch_overview($this->stream, $sequence,$uid ? IMAP::ST_UID : IMAP::NIL);
    }

    /**
     * Get a list of available folders
     * @param string $reference mailbox reference for list
     * @param string $folder mailbox name match with wildcards
     *
     * @return array folders that matched $folder as array(name => array('delimiter' => .., 'flags' => ..))
     * @throws RuntimeException
     */
    public function folders(string $reference = '', string $folder = '*'): array {
        $result = [];

        $items = \imap_getmailboxes($this->stream, $this->getAddress(), $reference.$folder);
        if(is_array($items)){
            foreach ($items as $item) {
                $name = $this->decodeFolderName($item->name);
                $result[$name] = ['delimiter' => $item->delimiter, 'flags' => []];
            }
        }else{
            throw new RuntimeException(\imap_last_error());
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
     * @param int $uid set to IMAP::ST_UID if you pass message unique identifiers instead of numbers.
     * @param null $item unused attribute
     *
     * @return bool|array new flags if $silent is false, else true or false depending on success
     */
    public function store(array $flags, int $from, $to = null, $mode = null, bool $silent = true, $uid = IMAP::ST_UID, $item = null) {
        $flag = trim(is_array($flags) ? implode(" ", $flags) : $flags);

        if ($mode == "+"){
            $status = \imap_setflag_full($this->stream, $from, $flag, $uid ? IMAP::ST_UID : IMAP::NIL);
        }else{
            $status = \imap_clearflag_full($this->stream, $from, $flag, $uid ? IMAP::ST_UID : IMAP::NIL);
        }

        if ($silent === true) {
            return $status;
        }

        return $this->flags($from);
    }

    /**
     * Append a new message to given folder
     * @param string $folder name of target folder
     * @param string $message full message content
     * @param array|null $flags flags for new message
     * @param string $date date for new message
     *
     * @return bool success
     */
    public function appendMessage(string $folder, string $message, $flags = null, $date = null): bool {
        if ($date != null) {
            if ($date instanceof \Carbon\Carbon){
                $date = $date->format('d-M-Y H:i:s O');
            }
            return \imap_append($this->stream, $folder, $message, $flags, $date);
        }

        return \imap_append($this->stream, $folder, $message, $flags);
    }

    /**
     * Copy message set from current folder to other folder
     * @param string $folder destination folder
     * @param $from
     * @param int|null $to if null only one message ($from) is fetched, else it's the
     *                         last message, INF means last message available
     * @param int $uid set to IMAP::ST_UID if you pass message unique identifiers instead of numbers.
     *
     * @return bool success
     */
    public function copyMessage(string $folder, $from, $to = null, $uid = IMAP::ST_UID): bool {
        return \imap_mail_copy($this->stream, $from, $folder, $uid ? IMAP::ST_UID : IMAP::NIL);
    }

    /**
     * Copy multiple messages to the target folder
     * @param array $messages List of message identifiers
     * @param string $folder Destination folder
     * @param int $uid set to IMAP::ST_UID if you pass message unique identifiers instead of numbers.
     *
     * @return array|bool Tokens if operation successful, false if an error occurred
     */
    public function copyManyMessages(array $messages, string $folder, $uid = IMAP::ST_UID) {
        foreach($messages as $msg) {
            if ($this->copyMessage($folder, $msg, null, $uid) == false) {
                return false;
            }
        }

        return $messages;
    }

    /**
     * Move a message set from current folder to an other folder
     * @param string $folder destination folder
     * @param $from
     * @param int|null $to if null only one message ($from) is fetched, else it's the
     *                         last message, INF means last message available
     * @param int $uid set to IMAP::ST_UID if you pass message unique identifiers instead of numbers.
     *
     * @return bool success
     */
    public function moveMessage(string $folder, $from, $to = null, $uid = IMAP::ST_UID): bool {
        return \imap_mail_move($this->stream, $from, $folder, $uid ? IMAP::ST_UID : IMAP::NIL);
    }

    /**
     * Move multiple messages to the target folder
     * @param array $messages List of message identifiers
     * @param string $folder Destination folder
     * @param int $uid set to IMAP::ST_UID if you pass message unique identifiers instead of numbers.
     *
     * @return array|bool Tokens if operation successful, false if an error occurred
     */
    public function moveManyMessages(array $messages, string $folder, $uid = IMAP::ST_UID) {
        foreach($messages as $msg) {
            if ($this->moveMessage($folder, $msg, null, $uid) == false) {
                return false;
            }
        }

        return $messages;
    }

    /**
     * Exchange identification information
     * Ref.: https://datatracker.ietf.org/doc/html/rfc2971
     *
     * @param null $ids
     * @return array|bool|void|null
     *
     * @throws MethodNotSupportedException
     */
    public function ID($ids = null) {
        throw new MethodNotSupportedException();
    }

    /**
     * Create a new folder (and parent folders if needed)
     * @param string $folder folder name
     *
     * @return bool success
     */
    public function createFolder(string $folder): bool {
        return \imap_createmailbox($this->stream, $folder);
    }

    /**
     * Rename an existing folder
     * @param string $old old name
     * @param string $new new name
     *
     * @return bool success
     */
    public function renameFolder(string $old, string $new): bool {
        return \imap_renamemailbox($this->stream, $old, $new);
    }

    /**
     * Delete a folder
     * @param string $folder folder name
     *
     * @return bool success
     */
    public function deleteFolder(string $folder): bool {
        return \imap_deletemailbox($this->stream, $folder);
    }

    /**
     * Subscribe to a folder
     * @param string $folder folder name
     *
     * @throws MethodNotSupportedException
     */
    public function subscribeFolder(string $folder): bool {
        throw new MethodNotSupportedException();
    }

    /**
     * Unsubscribe from a folder
     * @param string $folder folder name
     *
     * @throws MethodNotSupportedException
     */
    public function unsubscribeFolder(string $folder): bool {
        throw new MethodNotSupportedException();
    }

    /**
     * Apply session saved changes to the server
     *
     * @return bool success
     */
    public function expunge(): bool {
        return \imap_expunge($this->stream);
    }

    /**
     * Send noop command
     *
     * @throws MethodNotSupportedException
     */
    public function noop(): bool {
        throw new MethodNotSupportedException();
    }

    /**
     * Send idle command
     *
     * @throws MethodNotSupportedException
     */
    public function idle() {
        throw new MethodNotSupportedException();
    }

    /**
     * Send done command
     *
     * @throws MethodNotSupportedException
     */
    public function done() {
        throw new MethodNotSupportedException();
    }

    /**
     * Search for matching messages
     * @param array $params
     * @param int $uid set to IMAP::ST_UID if you pass message unique identifiers instead of numbers.
     *
     * @return array message ids
     */
    public function search(array $params, $uid = IMAP::ST_UID): array {
        $result = \imap_search($this->stream, $params[0], $uid ? IMAP::ST_UID : IMAP::NIL);
        if ($result === false) {
            return [];
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

    /**
     * Decode name.
     * It converts UTF7-IMAP encoding to UTF-8.
     *
     * @param $name
     *
     * @return array|false|string|string[]|null
     */
    protected function decodeFolderName($name) {
        preg_match('#\{(.*)\}(.*)#', $name, $preg);
        return mb_convert_encoding($preg[2], "UTF-8", "UTF7-IMAP");
    }

    /**
     * @return string
     */
    public function getProtocol(): string {
        return $this->protocol;
    }

    /**
     * Retrieve the quota level settings, and usage statics per mailbox
     * @param $username
     *
     * @return array
     */
    public function getQuota($username): array {
        return \imap_get_quota($this->stream, 'user.'.$username);
    }

    /**
     * Retrieve the quota settings per user
     * @param string $quota_root
     *
     * @return array
     */
    public function getQuotaRoot(string $quota_root = 'INBOX'): array {
        return \imap_get_quotaroot($this->stream, $quota_root);
    }

    /**
     * @param string $protocol
     * @return LegacyProtocol
     */
    public function setProtocol(string $protocol): LegacyProtocol {
        if (($pos = strpos($protocol, "legacy")) > 0) {
            $protocol = substr($protocol, 0, ($pos + 2) * -1);
        }
        $this->protocol = $protocol;
        return $this;
    }
}
