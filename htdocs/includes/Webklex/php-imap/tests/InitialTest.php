<?php
/*
* File: InitialTest.php
* Category: test
* Author: M.Goldenbaum
* Created: 02.01.21 04:42
* Updated: -
*
* Description:
*  -
*/

use PHPUnit\Framework\TestCase;
use \Webklex\PHPIMAP\ClientManager;

class InitialTest extends TestCase {
    protected $cm;

    public function setUp() {
        $this->cm = new ClientManager();
    }

    public function testConfigDefaultAccount() {
        $this->assertEquals("default", ClientManager::get("default"));
    }
}