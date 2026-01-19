<?php
/*
* File: LiveMailboxTestCase.php
* Category: -
* Author: M.Goldenbaum
* Created: 04.03.23 03:43
* Updated: -
*
* Description:
*  -
*/

namespace Tests\live;

use PHPUnit\Framework\TestCase;
use Webklex\PHPIMAP\Client;
use Webklex\PHPIMAP\ClientManager;
use Webklex\PHPIMAP\Exceptions\AuthFailedException;
use Webklex\PHPIMAP\Exceptions\ConnectionFailedException;
use Webklex\PHPIMAP\Exceptions\EventNotFoundException;
use Webklex\PHPIMAP\Exceptions\FolderFetchingException;
use Webklex\PHPIMAP\Exceptions\ImapBadRequestException;
use Webklex\PHPIMAP\Exceptions\ImapServerErrorException;
use Webklex\PHPIMAP\Exceptions\InvalidMessageDateException;
use Webklex\PHPIMAP\Exceptions\MaskNotFoundException;
use Webklex\PHPIMAP\Exceptions\MessageContentFetchingException;
use Webklex\PHPIMAP\Exceptions\MessageFlagException;
use Webklex\PHPIMAP\Exceptions\MessageHeaderFetchingException;
use Webklex\PHPIMAP\Exceptions\ResponseException;
use Webklex\PHPIMAP\Exceptions\RuntimeException;
use Webklex\PHPIMAP\Folder;
use Webklex\PHPIMAP\Message;

/**
 * Class LiveMailboxTestCase
 *
 * @package Tests
 */
abstract class LiveMailboxTestCase extends TestCase {

    /**
     * Special chars
     */
    const SPECIAL_CHARS = 'A_\\|!"£$%&()=?àèìòùÀÈÌÒÙ<>-@#[]_ß_б_π_€_✔_你_يد_Z_';

    /**
     * Client manager
     * @var ClientManager $manager
     */
    protected static ClientManager $manager;

    /**
     * Get the client manager
     *
     * @return ClientManager
     */
    final protected function getManager(): ClientManager {
        if (!isset(self::$manager)) {
            self::$manager = new ClientManager([
                'options' => [
                    "debug" => $_ENV["LIVE_MAILBOX_DEBUG"] ?? false,
                ],
                'accounts' => [
                    'default' => [
                        'host'          => getenv("LIVE_MAILBOX_HOST"),
                        'port'          => getenv("LIVE_MAILBOX_PORT"),
                        'encryption'    => getenv("LIVE_MAILBOX_ENCRYPTION"),
                        'validate_cert' => getenv("LIVE_MAILBOX_VALIDATE_CERT"),
                        'username'      => getenv("LIVE_MAILBOX_USERNAME"),
                        'password'      => getenv("LIVE_MAILBOX_PASSWORD"),
                        'protocol'      => 'imap', //might also use imap, [pop3 or nntp (untested)]
                    ],
                ],
            ]);
        }
        return self::$manager;
    }

    /**
     * Get the client
     *
     * @return Client
     * @throws MaskNotFoundException
     */
    final protected function getClient(): Client {
        if (!getenv("LIVE_MAILBOX") ?? false) {
            $this->markTestSkipped("This test requires a live mailbox. Please set the LIVE_MAILBOX environment variable to run this test.");
        }
        return $this->getManager()->account('default');
    }

    /**
     * Get special chars
     *
     * @return string
     */
    final protected function getSpecialChars(): string {
        return self::SPECIAL_CHARS;
    }

    /**
     * Get a folder
     * @param string $folder_path
     *
     * @return Folder
     * @throws AuthFailedException
     * @throws ConnectionFailedException
     * @throws ImapBadRequestException
     * @throws ImapServerErrorException
     * @throws MaskNotFoundException
     * @throws ResponseException
     * @throws RuntimeException
     * @throws FolderFetchingException
     */
    final protected function getFolder(string $folder_path = "INDEX"): Folder {
        $client = $this->getClient();
        self::assertInstanceOf(Client::class, $client->connect());

        $folder = $client->getFolderByPath($folder_path);
        self::assertInstanceOf(Folder::class, $folder);

        return $folder;
    }

    /**
     * Append a message to a folder
     * @param Folder $folder
     * @param string $message
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
     * @throws ResponseException
     * @throws RuntimeException
     */
    final protected function appendMessage(Folder $folder, string $message): Message {
        $status = $folder->select();
        if (!isset($status['uidnext'])) {
            $this->fail("No UIDNEXT returned");
        }

        $response = $folder->appendMessage($message);
        $valid_response = false;
        foreach ($response as $line) {
            if (str_starts_with($line, 'OK')) {
                $valid_response = true;
                break;
            }
        }
        if (!$valid_response) {
            $this->fail("Failed to append message: ".implode("\n", $response));
        }

        $message = $folder->messages()->getMessageByUid($status['uidnext']);
        self::assertInstanceOf(Message::class, $message);

        return $message;
    }

    /**
     * Append a message template to a folder
     * @param Folder $folder
     * @param string $template
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
     * @throws ResponseException
     * @throws RuntimeException
     */
    final protected function appendMessageTemplate(Folder $folder, string $template): Message {
        $content = file_get_contents(implode(DIRECTORY_SEPARATOR, [__DIR__, "..", "messages", $template]));
        return $this->appendMessage($folder, $content);
    }

    /**
     * Delete a folder if it is given
     * @param Folder|null $folder
     *
     * @return bool
     * @throws AuthFailedException
     * @throws ConnectionFailedException
     * @throws EventNotFoundException
     * @throws ImapBadRequestException
     * @throws ImapServerErrorException
     * @throws ResponseException
     * @throws RuntimeException
     */
    final protected function deleteFolder(?Folder $folder = null): bool {
        $response = $folder?->delete(false);
        if (is_array($response)) {
            $valid_response = false;
            foreach ($response as $line) {
                if (str_starts_with($line, 'OK')) {
                    $valid_response = true;
                    break;
                }
            }
            if (!$valid_response) {
                $this->fail("Failed to delete mailbox: ".implode("\n", $response));
            }
            return $valid_response;
        }
        return false;
    }
}