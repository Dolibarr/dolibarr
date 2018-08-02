<?php

namespace Sabre\DAV\Locks;

use Sabre\HTTP\Request;

class Plugin2Test extends \Sabre\DAVServerTest {

    public $setupLocks = true;

    function setUpTree() {

        $this->tree = new \Sabre\DAV\FS\Directory(SABRE_TEMPDIR);

    }

    function tearDown() {

        \Sabre\TestUtil::clearTempDir();

    }

    /**
     * This test first creates a file with LOCK and then deletes it.
     *
     * After deleting the file, the lock should no longer be in the lock
     * backend.
     *
     * Reported in ticket #487
     */
    function testUnlockAfterDelete() {

        $body = '<?xml version="1.0"?>
<D:lockinfo xmlns:D="DAV:">
    <D:lockscope><D:exclusive/></D:lockscope>
    <D:locktype><D:write/></D:locktype>
</D:lockinfo>';

        $request = new Request(
            'LOCK',
            '/file.txt',
            [],
            $body
        );
        $response = $this->request($request);
        $this->assertEquals(201, $response->getStatus(), $response->getBodyAsString());

        $this->assertEquals(
            1,
            count($this->locksBackend->getLocks('file.txt', true))
        );

        $request = new Request(
            'DELETE',
            '/file.txt',
            [
                'If' => '(' . $response->getHeader('Lock-Token') . ')',
            ]
        );
        $response = $this->request($request);
        $this->assertEquals(204, $response->getStatus(), $response->getBodyAsString());

        $this->assertEquals(
            0,
            count($this->locksBackend->getLocks('file.txt', true))
        );
    }

}
