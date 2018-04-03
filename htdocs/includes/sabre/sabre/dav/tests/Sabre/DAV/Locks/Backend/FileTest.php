<?php

namespace Sabre\DAV\Locks\Backend;

require_once 'Sabre/TestUtil.php';

class FileTest extends AbstractTest {

    function getBackend() {

        \Sabre\TestUtil::clearTempDir();
        $backend = new File(SABRE_TEMPDIR . '/lockdb');
        return $backend;

    }


    function tearDown() {

        \Sabre\TestUtil::clearTempDir();

    }

}
