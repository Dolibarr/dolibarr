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
use Webklex\PHPIMAP\Exceptions\AuthFailedException;
use Webklex\PHPIMAP\Exceptions\ConnectionFailedException;
use Webklex\PHPIMAP\Exceptions\ImapBadRequestException;
use Webklex\PHPIMAP\Exceptions\ImapServerErrorException;
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
     *
     * @throws ImapBadRequestException
     * @throws ImapServerErrorException
     * @throws RuntimeException
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
    public function connect(string $host, ?int $port = null);

    /**
     * Login to a new session.
     *
     * @param string $user username
     * @param string $password password
     *
     * @return Response
     *
     * @throws AuthFailedException
     * @throws ImapBadRequestException
     * @throws ImapServerErrorException
     */
    public function login(string $user, string $password): Response;

    /**
     * Authenticate your current session.
     * @param string $user username
     * @param string $token access token
     *
     * @return Response
     * @throws AuthFailedException
     */
    public function authenticate(string $user, string $token): Response;

    /**
     * Logout of the current server session
     *
     * @return Response
     *
     * @throws ImapBadRequestException
     * @throws ImapServerErrorException
     * @throws RuntimeException
     */
    public function logout(): Response;

    /**
     * Check if the current session is connected
     *
     * @return bool
     */
    public function connected(): bool;

    /**
     * Get an array of available capabilities
     *
     * @return Response containing a list of capabilities
     * @throws RuntimeException
     */
    public function getCapabilities(): Response;

    /**
     * Change the current folder
     * @param string $folder change to this folder
     *
     * @return Response see examineOrSelect()
     * @throws RuntimeException
     */
    public function selectFolder(string $folder = 'INBOX'): Response;

    /**
     * Examine a given folder
     * @param string $folder
     *
     * @return Response
     * @throws RuntimeException
     */
    public function examineFolder(string $folder = 'INBOX'): Response;

    /**
     * Get the status of a given folder
     *
     * @return Response list of STATUS items
     *
     * @throws ImapBadRequestException
     * @throws ImapServerErrorException
     * @throws RuntimeException
     */
    public function folderStatus(string $folder = 'INBOX', $arguments = ['MESSAGES', 'UNSEEN', 'RECENT', 'UIDNEXT', 'UIDVALIDITY']): Response;

    /**
     * Fetch message contents
     * @param int|array $uids
     * @param string $rfc
     * @param int|string $uid set to IMAP::ST_UID or any string representing the UID - set to IMAP::ST_MSGN to use
     * message numbers instead.
     *
     * @return Response
     * @throws RuntimeException
     */
    public function content(int|array $uids, string $rfc = "RFC822", int|string $uid = IMAP::ST_UID): Response;

    /**
     * Fetch message headers
     * @param int|array $uids
     * @param string $rfc
     * @param int|string $uid set to IMAP::ST_UID or any string representing the UID - set to IMAP::ST_MSGN to use
     * message numbers instead.
     *
     * @return Response
     * @throws RuntimeException
     */
    public function headers(int|array $uids, string $rfc = "RFC822", int|string $uid = IMAP::ST_UID): Response;

    /**
     * Fetch message flags
     * @param int|array $uids
     * @param int|string $uid set to IMAP::ST_UID or any string representing the UID - set to IMAP::ST_MSGN to use
     * message numbers instead.
     *
     * @return Response
     * @throws RuntimeException
     */
    public function flags(int|array $uids, int|string $uid = IMAP::ST_UID): Response;
    
    /**
     * Fetch message sizes
     * @param int|array $uids
     * @param int|string $uid set to IMAP::ST_UID or any string representing the UID - set to IMAP::ST_MSGN to use
     * message numbers instead.
     *
     * @return Response
     * @throws RuntimeException
     */
    public function sizes(int|array $uids, int|string $uid = IMAP::ST_UID): Response;

    /**
     * Get uid for a given id
     * @param int|null $id message number
     *
     * @return Response containing a message number for given message or all messages as array
     * @throws MessageNotFoundException
     */
    public function getUid(?int $id = null): Response;

    /**
     * Get a message number for a uid
     * @param string $id uid
     *
     * @return Response containing the message number
     * @throws MessageNotFoundException
     */
    public function getMessageNumber(string $id): Response;

    /**
     * Get a list of available folders
     * @param string $reference mailbox reference for list
     * @param string $folder mailbox / folder name match with wildcards
     *
     * @return Response containing mailboxes that matched $folder as array(globalName => array('delim' => .., 'flags' => ..))
     * @throws RuntimeException
     */
    public function folders(string $reference = '', string $folder = '*'): Response;

    /**
     * Set message flags
     * @param array|string $flags flags to set, add or remove
     * @param int $from message for items or start message if $to !== null
     * @param int|null $to if null only one message ($from) is fetched, else it's the
     *                             last message, INF means last message available
     * @param string|null $mode '+' to add flags, '-' to remove flags, everything else sets the flags as given
     * @param bool $silent if false the return values are the new flags for the wanted messages
     * @param int|string $uid set to IMAP::ST_UID or any string representing the UID - set to IMAP::ST_MSGN to use
     * message numbers instead.
     * @param string|null $item command used to store a flag
     *
     * @return Response containing the new flags if $silent is false, else true or false depending on success
     * @throws RuntimeException
     */
    public function store(array|string $flags, int $from, ?int $to = null, ?string $mode = null, bool $silent = true, int|string $uid = IMAP::ST_UID, ?string $item = null): Response;

    /**
     * Append a new message to given folder
     * @param string $folder name of target folder
     * @param string $message full message content
     * @param array|null $flags flags for new message
     * @param string|null $date date for new message
     *
     * @return Response
     * @throws RuntimeException
     */
    public function appendMessage(string $folder, string $message, ?array $flags = null, ?string $date = null): Response;

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
     * @return Response
     * @throws RuntimeException
     */
    public function copyMessage(string $folder, $from, ?int $to = null, int|string $uid = IMAP::ST_UID): Response;

    /**
     * Copy multiple messages to the target folder
     * @param array<string> $messages List of message identifiers
     * @param string $folder Destination folder
     * @param int|string $uid set to IMAP::ST_UID or any string representing the UID - set to IMAP::ST_MSGN to use
     * message numbers instead.
     *
     * @return Response Tokens if operation successful, false if an error occurred
     * @throws RuntimeException
     */
    public function copyManyMessages(array $messages, string $folder, int|string $uid = IMAP::ST_UID): Response;

    /**
     * Move a message set from current folder to another folder
     * @param string $folder destination folder
     * @param $from
     * @param int|null $to if null only one message ($from) is fetched, else it's the
     *                         last message, INF means last message available
     * @param int|string $uid set to IMAP::ST_UID or any string representing the UID - set to IMAP::ST_MSGN to use
     * message numbers instead.
     *
     * @return Response
     */
    public function moveMessage(string $folder, $from, ?int $to = null, int|string $uid = IMAP::ST_UID): Response;

    /**
     * Move multiple messages to the target folder
     *
     * @param array<string> $messages List of message identifiers
     * @param string $folder Destination folder
     * @param int|string $uid set to IMAP::ST_UID or any string representing the UID - set to IMAP::ST_MSGN to use
     * message numbers instead.
     *
     * @return Response Tokens if operation successful, false if an error occurred
     * @throws RuntimeException
     */
    public function moveManyMessages(array $messages, string $folder, int|string $uid = IMAP::ST_UID): Response;

    /**
     * Exchange identification information
     * Ref.: https://datatracker.ietf.org/doc/html/rfc2971
     *
     * @param null $ids
     * @return Response
     *
     * @throws RuntimeException
     */
    public function ID($ids = null): Response;

    /**
     * Create a new folder
     *
     * @param string $folder folder name
     * @return Response
     * @throws RuntimeException
     */
    public function createFolder(string $folder): Response;

    /**
     * Rename an existing folder
     *
     * @param string $old old name
     * @param string $new new name
     * @return Response
     * @throws RuntimeException
     */
    public function renameFolder(string $old, string $new): Response;

    /**
     * Delete a folder
     *
     * @param string $folder folder name
     * @return Response
     *
     * @throws ImapBadRequestException
     * @throws ImapServerErrorException
     * @throws RuntimeException
     */
    public function deleteFolder(string $folder): Response;

    /**
     * Subscribe to a folder
     *
     * @param string $folder folder name
     * @return Response
     *
     * @throws ImapBadRequestException
     * @throws ImapServerErrorException
     * @throws RuntimeException
     */
    public function subscribeFolder(string $folder): Response;

    /**
     * Unsubscribe from a folder
     *
     * @param string $folder folder name
     * @return Response
     *
     * @throws ImapBadRequestException
     * @throws ImapServerErrorException
     * @throws RuntimeException
     */
    public function unsubscribeFolder(string $folder): Response;

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
     * @return Response
     *
     * @throws ImapBadRequestException
     * @throws ImapServerErrorException
     * @throws RuntimeException
     */
    public function expunge(): Response;

    /**
     * Retrieve the quota level settings, and usage statics per mailbox
     * @param $username
     *
     * @return Response
     * @throws RuntimeException
     */
    public function getQuota($username): Response;

    /**
     * Retrieve the quota settings per user
     *
     * @param string $quota_root
     *
     * @return Response
     * @throws ConnectionFailedException
     */
    public function getQuotaRoot(string $quota_root = 'INBOX'): Response;

    /**
     * Send noop command
     *
     * @return Response
     *
     * @throws ImapBadRequestException
     * @throws ImapServerErrorException
     * @throws RuntimeException
     */
    public function noop(): Response;

    /**
     * Do a search request
     *
     * @param array $params
     * @param int|string $uid set to IMAP::ST_UID or any string representing the UID - set to IMAP::ST_MSGN to use
     * message numbers instead.
     *
     * @return Response containing the message ids
     * @throws RuntimeException
     */
    public function search(array $params, int|string $uid = IMAP::ST_UID): Response;

    /**
     * Get a message overview
     * @param string $sequence uid sequence
     * @param int|string $uid set to IMAP::ST_UID or any string representing the UID - set to IMAP::ST_MSGN to use
     * message numbers instead.
     *
     * @return Response
     * @throws RuntimeException
     * @throws MessageNotFoundException
     * @throws InvalidMessageDateException
     */
    public function overview(string $sequence, int|string $uid = IMAP::ST_UID): Response;

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
    public function setUidCache(?array $uids);
}
