<?php
/*
* File: Issue393Test.php
* Category: -
* Author: M.Goldenbaum
* Created: 10.01.23 10:48
* Updated: -
*
* Description:
*  -
*/

namespace Tests\issues;

use Tests\live\LiveMailboxTestCase;
use Webklex\PHPIMAP\Exceptions\AuthFailedException;
use Webklex\PHPIMAP\Exceptions\ConnectionFailedException;
use Webklex\PHPIMAP\Exceptions\EventNotFoundException;
use Webklex\PHPIMAP\Exceptions\FolderFetchingException;
use Webklex\PHPIMAP\Exceptions\ImapBadRequestException;
use Webklex\PHPIMAP\Exceptions\ImapServerErrorException;
use Webklex\PHPIMAP\Exceptions\MaskNotFoundException;
use Webklex\PHPIMAP\Exceptions\ResponseException;
use Webklex\PHPIMAP\Exceptions\RuntimeException;
use Webklex\PHPIMAP\Folder;

class Issue393Test extends LiveMailboxTestCase {

    /**
     * Test issue #393 - "Empty response" when calling getFolders()
     * @return void
     * @throws AuthFailedException
     * @throws ConnectionFailedException
     * @throws EventNotFoundException
     * @throws FolderFetchingException
     * @throws ImapBadRequestException
     * @throws ImapServerErrorException
     * @throws ResponseException
     * @throws RuntimeException
     * @throws MaskNotFoundException
     */
    public function testIssue(): void {
        $client = $this->getClient();
        $client->connect();

        $delimiter = $this->getManager()->getConfig()->get("options.delimiter");
        $pattern = implode($delimiter, ['doesnt_exist', '%']);

        $folder = $client->getFolder('doesnt_exist');
        $this->deleteFolder($folder);

        $folders = $client->getFolders(true, $pattern, true);
        self::assertCount(0, $folders);

        try {
            $client->getFolders(true, $pattern, false);
            $this->fail('Expected FolderFetchingException::class exception not thrown');
        } catch (FolderFetchingException $e) {
            self::assertInstanceOf(FolderFetchingException::class, $e);
        }
    }
}