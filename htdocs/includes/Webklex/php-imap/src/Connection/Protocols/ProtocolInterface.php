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

use ErrorException;
use Webklex\PHPIMAP\Client;
use Webklex\PHPIMAP\Exceptions\AuthFailedException;
use Webklex\PHPIMAP\Exceptions\ConnectionFailedException;
use Webklex\PHPIMAP\Exceptions\InvalidMessageDateException;
use Webklex\PHPIMAP\Exceptions\MessageNotFoundException;
use Webklex\PHPIMAP\Exceptions\RuntimeException;
use Webklex\PHPIMAP\IMAP;

/**
 * Interface ProtocolInterface
 *
 * @package Webklex\PHPIMAP\Connection\Protocols
 */
interface ProtocolInterface {

    /**
     * Public destructor
     */
    public function __destruct();

    /**
     * Open a new connection / session
     * @param string $host hostname or IP address of IMAP server
     * @param int|null $port of service server
     *
     * @throws ErrorException
     * @throws ConnectionFailedException
     * @throws RuntimeException
     */
    public function connect(string $host, $port = null);

    /**
     * Login to a new session.
     *
     * @param string $user username
     * @param string $password password
     * @return bool success
     * @throws AuthFailedException
     */
    public function login(string $user, string $password): bool;

    /**
     * Authenticate your current session.
     * @param string $user username
     * @param string $token access token
     *
     * @return bool|mixed
     * @throws AuthFailedException
     */
    public function authenticate(string $user, string $token);

    /**
     * Logout of the current server session
     *
     * @return bool success
     */
    public function logout(): bool;

    /**
     * Check if the current session is connected
     *
     * @return bool
     */
    public function connected(): bool;

    /**
     * Get an array of available capabilities
     *
     * @return array list of capabilities
     * @throws RuntimeException
     */
    public function getCapabilities(): array;

    /**
     * Change the current folder
     *
     * @param string $folder change to this folder
     * @return bool|array see examineOrselect()
     * @throws RuntimeException
     */
    public function selectFolder(string $folder = 'INBOX');

    /**
     * Examine a given folder
     *
     * @param string $folder
     * @return bool|array
     * @throws RuntimeException
     */
    public function examineFolder(string $folder = 'INBOX');

    /**
     * Fetch message headers
     * @param array|int $uids
     * @param string $rfc
     * @param int|string $uid set to IMAP::ST_UID or any string representing the UID - set to IMAP::ST_MSGN to use
     * message numbers instead.
     *
     * @return array
     * @throws RuntimeException
     */
    public function content($uids, string $rfc = "RFC822", $uid = IMAP::ST_UID): array;

    /**
     * Fetch message headers
     * @param array|int $uids
     * @param string $rfc
     * @param int|string $uid set to IMAP::ST_UID or any string representing the UID - set to IMAP::ST_MSGN to use
     * message numbers instead.
     *
     * @return array
     * @throws RuntimeException
     */
    public function headers($uids, string $rfc = "RFC822", $uid = IMAP::ST_UID): array;

    /**
     * Fetch message flags
     * @param array|int $uids
     * @param int|string $uid set to IMAP::ST_UID or any string representing the UID - set to IMAP::ST_MSGN to use
     * message numbers instead.
     *
     * @return array
     * @throws RuntimeException
     */
    public function flags($uids, $uid = IMAP::ST_UID): array;

    /**
     * Get uid for a given id
     * @param int|null $id message number
     *
     * @return array|string message number for given message or all messages as array
     * @throws MessageNotFoundException
     */
    public function getUid($id = null);

    /**
     * Get a message number for a uid
     * @param string $id uid
     *
     * @return int message number
     * @throws MessageNotFoundException
     */
    public function getMessageNumber(string $id): int;

    /**
     * Get a list of available folders
     * @param string $reference mailbox reference for list
     * @param string $folder mailbox / folder name match with wildcards
     *
     * @return array mailboxes that matched $folder as array(globalName => array('delim' => .., 'flags' => ..))
     * @throws RuntimeException
     */
    public function folders(string $reference = '', string $folder = '*'): array;

    /**
     * Set message flags
     * @param array $flags flags to set, add or remove
     * @param int $from message for items or start message if $to !== null
     * @param int|null $to if null only one message ($from) is fetched, else it's the
     *                             last message, INF means last message available
     * @param string|null $mode '+' to add flags, '-' to remove flags, everything else sets the flags as given
     * @param bool $silent if false the return values are the new flags for the wanted messages
     * @param int|string $uid set to IMAP::ST_UID or any string representing the UID - set to IMAP::ST_MSGN to use
     * message numbers instead.
     * @param null|string $item command used to store a flag
     *
     * @return bool|array new flags if $silent is false, else true or false depending on success
     * @throws RuntimeException
     */
    public function store(array $flags, int $from, $to = null, $mode = null, bool $silent = true, $uid = IMAP::ST_UID, $item = null);

    /**
     * Append a new message to given folder
     * @param string $folder name of target folder
     * @param string $message full message content
     * @param array|null $flags flags for new message
     * @param string|null $date date for new message
     *
     * @return bool success
     * @throws RuntimeException
     */
    public function appendMessage(string $folder, string $message, $flags = null, $date = null): bool;

    /**
     * Copy message set from current folder to other folder
     *
     * @param string $folder destination folder
     * @param $from
     * @param int|null $to if null only one message ($from) is fetched, else it's the
     *                         last message, INF means last message available
     * @param int|string $uid set to IMAP::ST_UID or any string representing the UID - set to IMAP::ST_MSGN to use
     * message numbers instead.
     *
     * @return bool success
     * @throws RuntimeException
     */
    public function copyMessage(string $folder, $from, $to = null, $uid = IMAP::ST_UID): bool;

    /**
     * Copy multiple messages to the target folder
     * @param array<string> $messages List of message identifiers
     * @param string $folder Destination folder
     * @param int|string $uid set to IMAP::ST_UID or any string representing the UID - set to IMAP::ST_MSGN to use
     * message numbers instead.
     *
     * @return array|bool Tokens if operation successful, false if an error occurred
     * @throws RuntimeException
     */
    public function copyManyMessages(array $messages, string $folder, $uid = IMAP::ST_UID);

    /**
     * Move a message set from current folder to an other folder
     * @param string $folder destination folder
     * @param $from
     * @param int|null $to if null only one message ($from) is fetched, else it's the
     *                         last message, INF means last message available
     * @param int|string $uid set to IMAP::ST_UID or any string representing the UID - set to IMAP::ST_MSGN to use
     * message numbers instead.
     *
     * @return bool success
     */
    public function moveMessage(string $folder, $from, $to = null, $uid = IMAP::ST_UID): bool;

    /**
     * Move multiple messages to the target folder
     *
     * @param array<string> $messages List of message identifiers
     * @param string $folder Destination folder
     * @param int|string $uid set to IMAP::ST_UID or any string representing the UID - set to IMAP::ST_MSGN to use
     * message numbers instead.
     *
     * @return array|bool Tokens if operation successful, false if an error occurred
     * @throws RuntimeException
     */
    public function moveManyMessages(array $messages, string $folder, $uid = IMAP::ST_UID);

    /**
     * Exchange identification information
     * Ref.: https://datatracker.ietf.org/doc/html/rfc2971
     *
     * @param null $ids
     * @return array|bool|void|null
     *
     * @throws RuntimeException
     */
    public function ID($ids = null);

    /**
     * Create a new folder
     *
     * @param string $folder folder name
     * @return bool success
     * @throws RuntimeException
     */
    public function createFolder(string $folder): bool;

    /**
     * Rename an existing folder
     *
     * @param string $old old name
     * @param string $new new name
     * @return bool success
     * @throws RuntimeException
     */
    public function renameFolder(string $old, string $new): bool;

    /**
     * Delete a folder
     *
     * @param string $folder folder name
     * @return bool success
     * @throws RuntimeException
     */
    public function deleteFolder(string $folder): bool;

    /**
     * Subscribe to a folder
     *
     * @param string $folder folder name
     * @return bool success
     * @throws RuntimeException
     */
    public function subscribeFolder(string $folder): bool;

    /**
     * Unsubscribe from a folder
     * @param string $folder folder name
     *
     * @return bool success
     * @throws RuntimeException
     */
    public function unsubscribeFolder(string $folder): bool;

    /**
     * Send idle command
     *
     * @throws RuntimeException
     */
    public function idle();

    /**
     * Send done command
     * @throws RuntimeException
     */
    public function done();

    /**
     * Apply session saved changes to the server
     *
     * @return bool success
     * @throws RuntimeException
     */
    public function expunge(): bool;

    /**
     * Retrieve the quota level settings, and usage statics per mailbox
     * @param $username
     *
     * @return array
     * @throws RuntimeException
     */
    public function getQuota($username): array;

    /**
     * Retrieve the quota settings per user
     *
     * @param string $quota_root
     *
     * @return array
     * @throws ConnectionFailedException
     */
    public function getQuotaRoot(string $quota_root = 'INBOX'): array;

    /**
     * Send noop command
     *
     * @return bool success
     * @throws RuntimeException
     */
    public function noop(): bool;

    /**
     * Do a search request
     *
     * @param array $params
     * @param int|string $uid set to IMAP::ST_UID or any string representing the UID - set to IMAP::ST_MSGN to use
     * message numbers instead.
     *
     * @return array message ids
     * @throws RuntimeException
     */
    public function search(array $params, $uid = IMAP::ST_UID): array;

    /**
     * Get a message overview
     * @param string $sequence uid sequence
     * @param int|string $uid set to IMAP::ST_UID or any string representing the UID - set to IMAP::ST_MSGN to use
     * message numbers instead.
     *
     * @return array
     * @throws RuntimeException
     * @throws MessageNotFoundException
     * @throws InvalidMessageDateException
     */
    public function overview(string $sequence, $uid = IMAP::ST_UID): array;

    /**
     * Enable the debug mode
     */
    public function enableDebug();

    /**
     * Disable the debug mode
     */
    public function disableDebug();

    /**
     * Enable uid caching
     */
    public function enableUidCache();

    /**
     * Disable uid caching
     */
    public function disableUidCache();

    /**
     * Set the uid cache of current active folder
     *
     * @param array|null $uids
     */
    public function setUidCache($uids);
}
