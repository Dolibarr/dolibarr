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
use Webklex\PHPIMAP\Config;
use Webklex\PHPIMAP\Exceptions\AuthFailedException;
use Webklex\PHPIMAP\Exceptions\ImapBadRequestException;
use Webklex\PHPIMAP\Exceptions\MethodNotSupportedException;
use Webklex\PHPIMAP\Exceptions\RuntimeException;
use Webklex\PHPIMAP\IMAP;

/**
 * Class LegacyProtocol
 *
 * @package Webklex\PHPIMAP\Connection\Protocols
 */
class LegacyProtocol extends Protocol {

    protected string $protocol = "imap";
    protected string $host = "localhost";
    protected int $port = 993;

    /**
     * Imap constructor.
     * @param Config $config
     * @param bool $cert_validation set to false to skip SSL certificate validation
     * @param mixed $encryption Connection encryption method
     */
    public function __construct(Config $config, bool $cert_validation = true, mixed $encryption = false) {
        $this->config = $config;
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
     * @param int|null $port
     */
    public function connect(string $host, ?int $port = null): void {
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
     * @return Response
     */
    public function login(string $user, string $password): Response {
        return $this->response()->wrap(function($response) use ($user, $password) {
            /** @var Response $response */
            try {
                $this->stream = \imap_open(
                    $this->getAddress(),
                    $user,
                    $password,
                    0,
                    $attempts = 3,
                    $this->config->get('options.open')
                );
                $response->addCommand("imap_open");
            } catch (\ErrorException $e) {
                $errors = \imap_errors();
                $message = $e->getMessage() . '. ' . implode("; ", (is_array($errors) ? $errors : array()));
                throw new AuthFailedException($message);
            }

            if (!$this->stream) {
                $errors = \imap_errors();
                $message = implode("; ", (is_array($errors) ? $errors : array()));
                throw new AuthFailedException($message);
            }

            $errors = \imap_errors();
            $response->addCommand("imap_errors");
            if (is_array($errors)) {
                $status = $this->examineFolder();
                $response->stack($status);
                if ($status->data()['exists'] !== 0) {
                    $message = implode("; ", $errors);
                    throw new RuntimeException($message);
                }
            }

            if ($this->stream !== false) {
                return ["TAG" . $response->Noun() . " OK [] Logged in\r\n"];
            }

            $response->addError("failed to login");
            return [];
        });
    }

    /**
     * Authenticate your current session.
     * @param string $user username
     * @param string $token access token
     *
     * @return Response
     */
    public function authenticate(string $user, string $token): Response {
        return $this->login($user, $token);
    }

    /**
     * Get full address of mailbox.
     *
     * @return string
     */
    protected function getAddress(): string {
        $address = "{" . $this->host . ":" . $this->port . "/" . $this->protocol;
        if (!$this->cert_validation) {
            $address .= '/novalidate-cert';
        }
        if (in_array($this->encryption, ['tls', 'notls', 'ssl'])) {
            $address .= '/' . $this->encryption;
        } elseif ($this->encryption === "starttls") {
            $address .= '/tls';
        }

        $address .= '}';

        return $address;
    }

    /**
     * Logout of the current session
     *
     * @return Response
     */
    public function logout(): Response {
        return $this->response()->wrap(function($response) {
            /** @var Response $response */
            if ($this->stream) {
                $this->uid_cache = [];
                $response->addCommand("imap_close");
                if (\imap_close($this->stream, IMAP::CL_EXPUNGE)) {
                    $this->stream = false;
                    return [
                        0 => "BYE Logging out\r\n",
                        1 => "TAG" . $response->Noun() . " OK Logout completed (0.001 + 0.000 secs).\r\n",
                    ];
                }
                $this->stream = false;
            }
            return [];
        });
    }

    /**
     * Get an array of available capabilities
     *
     * @throws MethodNotSupportedException
     */
    public function getCapabilities(): Response {
        throw new MethodNotSupportedException();
    }

    /**
     * Change the current folder
     * @param string $folder change to this folder
     *
     * @return Response see examineOrselect()
     * @throws RuntimeException
     */
    public function selectFolder(string $folder = 'INBOX'): Response {
        $flags = IMAP::OP_READONLY;
        if (in_array($this->protocol, ["pop3", "nntp"])) {
            $flags = IMAP::NIL;
        }
        if ($this->stream === false) {
            throw new RuntimeException("failed to reopen stream.");
        }

        return $this->response("imap_reopen")->wrap(function($response) use ($folder, $flags) {
            /** @var Response $response */
            \imap_reopen($this->stream, $this->getAddress() . $folder, $flags, 3);
            $this->uid_cache = [];

            $status = $this->examineFolder($folder);
            $response->stack($status);

            return $status->data();
        });
    }

    /**
     * Examine a given folder
     * @param string $folder examine this folder
     *
     * @return Response
     * @throws RuntimeException
     */
    public function examineFolder(string $folder = 'INBOX'): Response {
        if (str_starts_with($folder, ".")) {
            throw new RuntimeException("Segmentation fault prevented. Folders starts with an illegal char '.'.");
        }
        return $this->response("imap_status")->wrap(function($response) use ($folder) {
            /** @var Response $response */
            $status = \imap_status($this->stream, $this->getAddress() . $folder, IMAP::SA_ALL);

            return $status ? [
                "flags"   => [],
                "exists"  => $status->messages,
                "recent"  => $status->recent,
                "unseen"  => $status->unseen,
                "uidnext" => $status->uidnext,
            ] : [];
        });
    }

    /**
     * Get the status of a given folder
     *
     * @return Response list of STATUS items
     * @throws MethodNotSupportedException
     */
    public function folderStatus(string $folder = 'INBOX', $arguments = ['MESSAGES', 'UNSEEN', 'RECENT', 'UIDNEXT', 'UIDVALIDITY']): Response {
        throw new MethodNotSupportedException();
    }

    /**
     * Fetch message content
     * @param int|array $uids
     * @param string $rfc
     * @param int|string $uid set to IMAP::ST_UID if you pass message unique identifiers instead of numbers.
     *
     * @return Response
     */
    public function content(int|array $uids, string $rfc = "RFC822", int|string $uid = IMAP::ST_UID): Response {
        return $this->response()->wrap(function($response) use ($uids, $uid) {
            /** @var Response $response */

            $result = [];
            $uids = is_array($uids) ? $uids : [$uids];
            foreach ($uids as $id) {
                $response->addCommand("imap_fetchbody");
                $result[$id] = \imap_fetchbody($this->stream, $id, "", $uid === IMAP::ST_UID ? IMAP::ST_UID : IMAP::NIL);
            }

            return $result;
        });
    }

    /**
     * Fetch message headers
     * @param int|array $uids
     * @param string $rfc
     * @param int|string $uid set to IMAP::ST_UID if you pass message unique identifiers instead of numbers.
     *
     * @return Response
     */
    public function headers(int|array $uids, string $rfc = "RFC822", int|string $uid = IMAP::ST_UID): Response {
        return $this->response()->wrap(function($response) use ($uids, $uid) {
            /** @var Response $response */

            $result = [];
            $uids = is_array($uids) ? $uids : [$uids];
            foreach ($uids as $id) {
                $response->addCommand("imap_fetchheader");
                $result[$id] = \imap_fetchheader($this->stream, $id, $uid ? IMAP::ST_UID : IMAP::NIL);
            }

            return $result;
        });
    }

    /**
     * Fetch message flags
     * @param int|array $uids
     * @param int|string $uid set to IMAP::ST_UID if you pass message unique identifiers instead of numbers.
     *
     * @return Response
     */
    public function flags(int|array $uids, int|string $uid = IMAP::ST_UID): Response {
        return $this->response()->wrap(function($response) use ($uids, $uid) {
            /** @var Response $response */

            $result = [];
            $uids = is_array($uids) ? $uids : [$uids];
            foreach ($uids as $id) {
                $response->addCommand("imap_fetch_overview");
                $raw_flags = \imap_fetch_overview($this->stream, $id, $uid ? IMAP::ST_UID : IMAP::NIL);
                $flags = [];
                if (is_array($raw_flags) && isset($raw_flags[0])) {
                    $raw_flags = (array)$raw_flags[0];
                    foreach ($raw_flags as $flag => $value) {
                        if ($value === 1 && in_array($flag, ["size", "uid", "msgno", "update"]) === false) {
                            $flags[] = "\\" . ucfirst($flag);
                        }
                    }
                }
                $result[$id] = $flags;
            }

            return $result;
        });
    }

    /**
     * Fetch message sizes
     * @param int|array $uids
     * @param int|string $uid set to IMAP::ST_UID if you pass message unique identifiers instead of numbers.
     *
     * @return Response
     */
    public function sizes(int|array $uids, int|string $uid = IMAP::ST_UID): Response {
        return $this->response()->wrap(function($response) use ($uids, $uid) {
            /** @var Response $response */
            $result = [];
            $uids = is_array($uids) ? $uids : [$uids];
            $uid_text = implode("','", $uids);
            $response->addCommand("imap_fetch_overview");
            if ($uid == IMAP::ST_UID) {
                $raw_overview = \imap_fetch_overview($this->stream, $uid_text, IMAP::FT_UID);
            } else {
                $raw_overview = \imap_fetch_overview($this->stream, $uid_text);
            }
            if ($raw_overview !== false) {
                foreach ($raw_overview as $overview_element) {
                    $overview_element = (array)$overview_element;
                    $result[$overview_element[$uid == IMAP::ST_UID ? 'uid' : 'msgno']] = $overview_element['size'];
                }
            }
            return $result;
        });
    }

    /**
     * Get uid for a given id
     * @param int|null $id message number
     *
     * @return Response message number for given message or all messages as array
     */
    public function getUid(?int $id = null): Response {
        return $this->response()->wrap(function($response) use ($id) {
            /** @var Response $response */
            if ($id === null) {
                if ($this->enable_uid_cache && $this->uid_cache) {
                    return $this->uid_cache;
                }

                $overview = $this->overview("1:*");
                $response->stack($overview);
                $uids = [];
                foreach ($overview->data() as $set) {
                    $uids[$set->msgno] = $set->uid;
                }

                $this->setUidCache($uids);
                return $uids;
            }

            $response->addCommand("imap_uid");
            $uid = \imap_uid($this->stream, $id);
            if ($uid) {
                return $uid;
            }

            return [];
        });
    }

    /**
     * Get the message number of a given uid
     * @param string $id uid
     *
     * @return Response message number
     */
    public function getMessageNumber(string $id): Response {
        return $this->response("imap_msgno")->wrap(function($response) use ($id) {
            /** @var Response $response */
            return \imap_msgno($this->stream, $id);
        });
    }

    /**
     * Get a message overview
     * @param string $sequence uid sequence
     * @param int|string $uid set to IMAP::ST_UID if you pass message unique identifiers instead of numbers.
     *
     * @return Response
     */
    public function overview(string $sequence, int|string $uid = IMAP::ST_UID): Response {
        return $this->response("imap_fetch_overview")->wrap(function($response) use ($sequence, $uid) {
            /** @var Response $response */
            return \imap_fetch_overview($this->stream, $sequence, $uid ? IMAP::ST_UID : IMAP::NIL) ?: [];
        });
    }

    /**
     * Get a list of available folders
     * @param string $reference mailbox reference for list
     * @param string $folder mailbox name match with wildcards
     *
     * @return Response folders that matched $folder as array(name => array('delimiter' => .., 'flags' => ..))
     */
    public function folders(string $reference = '', string $folder = '*'): Response {
        return $this->response("imap_getmailboxes")->wrap(function($response) use ($reference, $folder) {
            /** @var Response $response */
            $result = [];

            $items = \imap_getmailboxes($this->stream, $this->getAddress(), $reference . $folder);
            if (is_array($items)) {
                foreach ($items as $item) {
                    $name = $this->decodeFolderName($item->name);
                    $result[$name] = ['delimiter' => $item->delimiter, 'flags' => []];
                }
            } else {
                throw new RuntimeException(\imap_last_error());
            }

            return $result;
        });
    }

    /**
     * Manage flags
     * @param array|string $flags flags to set, add or remove - see $mode
     * @param int $from message for items or start message if $to !== null
     * @param int|null $to if null only one message ($from) is fetched, else it's the
     *                             last message, INF means last message available
     * @param string|null $mode '+' to add flags, '-' to remove flags, everything else sets the flags as given
     * @param bool $silent if false the return values are the new flags for the wanted messages
     * @param int|string $uid set to IMAP::ST_UID if you pass message unique identifiers instead of numbers.
     * @param string|null $item unused attribute
     *
     * @return Response new flags if $silent is false, else true or false depending on success
     */
    public function store(array|string $flags, int $from, ?int $to = null, ?string $mode = null, bool $silent = true, int|string $uid = IMAP::ST_UID, ?string $item = null): Response {
        $flag = trim(is_array($flags) ? implode(" ", $flags) : $flags);

        return $this->response()->wrap(function($response) use ($mode, $from, $flag, $uid, $silent) {
            /** @var Response $response */

            if ($mode == "+") {
                $response->addCommand("imap_setflag_full");
                $status = \imap_setflag_full($this->stream, $from, $flag, $uid ? IMAP::ST_UID : IMAP::NIL);
            } else {
                $response->addCommand("imap_clearflag_full");
                $status = \imap_clearflag_full($this->stream, $from, $flag, $uid ? IMAP::ST_UID : IMAP::NIL);
            }

            if ($silent === true) {
                if ($status) {
                    return [
                        "TAG" . $response->Noun() . " OK Store completed (0.001 + 0.000 secs).\r\n"
                    ];
                }
                return [];
            }

            return $this->flags($from);
        });
    }

    /**
     * Append a new message to given folder
     * @param string $folder name of target folder
     * @param string $message full message content
     * @param array|null $flags flags for new message
     * @param mixed $date date for new message
     *
     * @return Response
     */
    public function appendMessage(string $folder, string $message, ?array $flags = null, mixed $date = null): Response {
        return $this->response("imap_append")->wrap(function($response) use ($folder, $message, $flags, $date) {
            /** @var Response $response */
            if ($date != null) {
                if ($date instanceof \Carbon\Carbon) {
                    $date = $date->format('d-M-Y H:i:s O');
                }
                if (\imap_append($this->stream, $this->getAddress() . $folder, $message, $flags, $date)) {
                    return [
                        "OK Append completed (0.001 + 0.000 secs).\r\n"
                    ];
                }
            } else if (\imap_append($this->stream, $this->getAddress() . $folder, $message, $flags)) {
                return [
                    "OK Append completed (0.001 + 0.000 secs).\r\n"
                ];
            }
            return [];
        });
    }

    /**
     * Copy message set from current folder to other folder
     * @param string $folder destination folder
     * @param $from
     * @param int|null $to if null only one message ($from) is fetched, else it's the
     *                         last message, INF means last message available
     * @param int|string $uid set to IMAP::ST_UID if you pass message unique identifiers instead of numbers.
     *
     * @return Response
     */
    public function copyMessage(string $folder, $from, ?int $to = null, int|string $uid = IMAP::ST_UID): Response {
        return $this->response("imap_mail_copy")->wrap(function($response) use ($from, $folder, $uid) {
            /** @var Response $response */

            if (\imap_mail_copy($this->stream, $from, $this->getAddress() . $folder, $uid ? IMAP::ST_UID : IMAP::NIL)) {
                return [
                    "TAG" . $response->Noun() . " OK Copy completed (0.001 + 0.000 secs).\r\n"
                ];
            }
            throw new ImapBadRequestException("Invalid ID $from");
        });
    }

    /**
     * Copy multiple messages to the target folder
     * @param array $messages List of message identifiers
     * @param string $folder Destination folder
     * @param int|string $uid set to IMAP::ST_UID if you pass message unique identifiers instead of numbers.
     *
     * @return Response Tokens if operation successful, false if an error occurred
     */
    public function copyManyMessages(array $messages, string $folder, int|string $uid = IMAP::ST_UID): Response {
        return $this->response()->wrap(function($response) use ($messages, $folder, $uid) {
            /** @var Response $response */
            foreach ($messages as $msg) {
                $copy_response = $this->copyMessage($folder, $msg, null, $uid);
                $response->stack($copy_response);
                if (empty($copy_response->data())) {
                    return [
                        "TAG" . $response->Noun() . " BAD Copy failed (0.001 + 0.000 secs).\r\n",
                        "Invalid ID $msg\r\n"
                    ];
                }
            }
            return [
                "TAG" . $response->Noun() . " OK Copy completed (0.001 + 0.000 secs).\r\n"
            ];
        });
    }

    /**
     * Move a message set from current folder to another folder
     * @param string $folder destination folder
     * @param $from
     * @param int|null $to if null only one message ($from) is fetched, else it's the
     *                         last message, INF means last message available
     * @param int|string $uid set to IMAP::ST_UID if you pass message unique identifiers instead of numbers.
     *
     * @return Response success
     */
    public function moveMessage(string $folder, $from, ?int $to = null, int|string $uid = IMAP::ST_UID): Response {
        return $this->response("imap_mail_move")->wrap(function($response) use ($from, $folder, $uid) {
            if (\imap_mail_move($this->stream, $from, $this->getAddress() . $folder, $uid ? IMAP::ST_UID : IMAP::NIL)) {
                return [
                    "TAG" . $response->Noun() . " OK Move completed (0.001 + 0.000 secs).\r\n"
                ];
            }
            throw new ImapBadRequestException("Invalid ID $from");
        });
    }

    /**
     * Move multiple messages to the target folder
     * @param array $messages List of message identifiers
     * @param string $folder Destination folder
     * @param int|string $uid set to IMAP::ST_UID if you pass message unique identifiers instead of numbers.
     *
     * @return Response Tokens if operation successful, false if an error occurred
     * @throws ImapBadRequestException
     */
    public function moveManyMessages(array $messages, string $folder, int|string $uid = IMAP::ST_UID): Response {
        return $this->response()->wrap(function($response) use ($messages, $folder, $uid) {
            foreach ($messages as $msg) {
                $move_response = $this->moveMessage($folder, $msg, null, $uid);
                $response = $response->include($response);
                if (empty($move_response->data())) {
                    return [
                        "TAG" . $response->Noun() . " BAD Move failed (0.001 + 0.000 secs).\r\n",
                        "Invalid ID $msg\r\n"
                    ];
                }
            }
            return [
                "TAG" . $response->Noun() . " OK Move completed (0.001 + 0.000 secs).\r\n"
            ];
        });
    }

    /**
     * Exchange identification information
     * Ref.: https://datatracker.ietf.org/doc/html/rfc2971
     *
     * @param null $ids
     * @return Response
     *
     * @throws MethodNotSupportedException
     */
    public function ID($ids = null): Response {
        throw new MethodNotSupportedException();
    }

    /**
     * Create a new folder (and parent folders if needed)
     * @param string $folder folder name
     *
     * @return Response
     */
    public function createFolder(string $folder): Response {
        return $this->response("imap_createmailbox")->wrap(function($response) use ($folder) {
            return \imap_createmailbox($this->stream, $this->getAddress() . $folder) ? [
                0 => "TAG" . $response->Noun() . " OK Create completed (0.004 + 0.000 + 0.003 secs).\r\n",
            ] : [];
        });
    }

    /**
     * Rename an existing folder
     * @param string $old old name
     * @param string $new new name
     *
     * @return Response
     */
    public function renameFolder(string $old, string $new): Response {
        return $this->response("imap_renamemailbox")->wrap(function($response) use ($old, $new) {
            return \imap_renamemailbox($this->stream, $this->getAddress() . $old, $this->getAddress() . $new) ? [
                0 => "TAG" . $response->Noun() . " OK Move completed (0.004 + 0.000 + 0.003 secs).\r\n",
            ] : [];
        });
    }

    /**
     * Delete a folder
     * @param string $folder folder name
     *
     * @return Response
     */
    public function deleteFolder(string $folder): Response {
        return $this->response("imap_deletemailbox")->wrap(function($response) use ($folder) {
            return \imap_deletemailbox($this->stream, $this->getAddress() . $folder) ? [
                0 => "OK Delete completed (0.004 + 0.000 + 0.003 secs).\r\n",
            ] : [];
        });
    }

    /**
     * Subscribe to a folder
     * @param string $folder folder name
     *
     * @throws MethodNotSupportedException
     */
    public function subscribeFolder(string $folder): Response {
        throw new MethodNotSupportedException();
    }

    /**
     * Unsubscribe from a folder
     * @param string $folder folder name
     *
     * @throws MethodNotSupportedException
     */
    public function unsubscribeFolder(string $folder): Response {
        throw new MethodNotSupportedException();
    }

    /**
     * Apply session saved changes to the server
     *
     * @return Response
     */
    public function expunge(): Response {
        return $this->response("imap_expunge")->wrap(function($response) {
            return \imap_expunge($this->stream) ? [
                0 => "TAG" . $response->Noun() . " OK Expunge completed (0.001 + 0.000 secs).\r\n",
            ] : [];
        });
    }

    /**
     * Send noop command
     *
     * @throws MethodNotSupportedException
     */
    public function noop(): Response {
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
     * @param int|string $uid set to IMAP::ST_UID if you pass message unique identifiers instead of numbers.
     *
     * @return Response message ids
     */
    public function search(array $params, int|string $uid = IMAP::ST_UID): Response {
        return $this->response("imap_search")->wrap(function($response) use ($params, $uid) {
            $response->setCanBeEmpty(true);
            $result = \imap_search($this->stream, $params[0], $uid ? IMAP::ST_UID : IMAP::NIL);
            return $result ?: [];
        });
    }

    /**
     * Enable the debug mode
     */
    public function enableDebug() {
        $this->debug = true;
    }

    /**
     * Disable the debug mode
     */
    public function disableDebug() {
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
    protected function decodeFolderName($name): array|bool|string|null {
        preg_match('#\{(.*)}(.*)#', $name, $preg);
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
     * @return Response
     */
    public function getQuota($username): Response {
        return $this->response("imap_get_quota")->wrap(function($response) use ($username) {
            $result = \imap_get_quota($this->stream, 'user.' . $username);
            return $result ?: [];
        });
    }

    /**
     * Retrieve the quota settings per user
     * @param string $quota_root
     *
     * @return Response
     */
    public function getQuotaRoot(string $quota_root = 'INBOX'): Response {
        return $this->response("imap_get_quotaroot")->wrap(function($response) use ($quota_root) {
            $result = \imap_get_quotaroot($this->stream, $this->getAddress() . $quota_root);
            return $result ?: [];
        });
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

    /**
     * Create a new Response instance
     * @param string|null $command
     *
     * @return Response
     */
    protected function response(?string $command = ""): Response {
        return Response::make(0, $command == "" ? [] : [$command], [], $this->debug);
    }
}
