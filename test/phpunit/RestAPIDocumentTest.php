<?php
/* Copyright (C) 2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 *      \file       test/phpunit/RestAPIDocumentTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test
 *      \remarks    To run this script as CLI:  phpunit filename.php.
 */
global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/date.lib.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/geturl.lib.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/files.lib.php';

if (empty($user->id)) {
    echo "Load permissions for admin user nb 1\n";
    $user->fetch(1);
    $user->getrights();
}
$conf->global->MAIN_DISABLE_ALL_MAILS = 1;
$conf->global->MAIN_UMASK = '0666';

/**
 * Class for PHPUnit tests.
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class RestAPIDocumentTest extends PHPUnit_Framework_TestCase
{
    protected $savconf;
    protected $savuser;
    protected $savlangs;
    protected $savdb;
    protected $api_url;
    protected $api_key;

    /**
     * Constructor
     * We save global variables into local variables.
     *
     * @return DateLibTest
     */
    public function __construct()
    {
    	parent::__construct();

    	//$this->sharedFixture
        global $conf,$user,$langs,$db;
        $this->savconf = $conf;
        $this->savuser = $user;
        $this->savlangs = $langs;
        $this->savdb = $db;

        echo __METHOD__.' db->type='.$db->type.' user->id='.$user->id;
        //print " - db ".$db->db;
        echo "\n";
    }

    // Static methods
    public static function setUpBeforeClass()
    {
        global $conf,$user,$langs,$db;
        $db->begin(); // This is to have all actions inside a transaction even if test launched without suite.

        echo __METHOD__."\n";
    }

    // tear down after class
    public static function tearDownAfterClass()
    {
        global $conf,$user,$langs,$db;
        $db->rollback();

        echo __METHOD__."\n";
    }

    /**
     * Init phpunit tests.
     * @return void
     */
    protected function setUp()
    {
        global $conf,$user,$langs,$db;
        $conf = $this->savconf;
        $user = $this->savuser;
        $langs = $this->savlangs;
        $db = $this->savdb;

        $this->api_url = DOL_MAIN_URL_ROOT.'/api/index.php';

        $login = 'admin';
        $password = 'admin';
        $url = $this->api_url.'/login?login='.$login.'&password='.$password;
        // Call the API login method to save api_key for this test class
        $result = getURLContent($url, 'GET', '', 1, array());
        echo __METHOD__.' result = '.var_export($result, true)."\n";
        echo __METHOD__.' curl_error_no: '.$result['curl_error_no']."\n";
        $this->assertEquals($result['curl_error_no'], '');
        $object = json_decode($result['content'], true);
        $this->assertNotNull($object, 'Parsing of json result must no be null');
        $this->assertEquals('200', $object['success']['code']);

        $this->api_key = $object['success']['token'];
        echo __METHOD__." api_key: $this->api_key \n";

        echo __METHOD__."\n";
    }

    /**
     * End phpunit tests.
     * @return void
     */
    protected function tearDown()
    {
        echo __METHOD__."\n";
    }

    /**
     * testPushDocument.
     *
     * @return int
     */
    public function testPushDocument()
    {
        global $conf,$user,$langs,$db;

        $url = $this->api_url.'/documents/?api_key='.$this->api_key;

        echo __METHOD__.' Request POST url='.$url."\n";


        // Send to non existant directory

        dol_delete_dir_recursive(DOL_DATA_ROOT.'/medias/tmpphpunit');

        //$data = '{ "filename": "mynewfile.txt", "modulepart": "medias", "ref": "", "subdir": "mysubdir1/mysubdir2", "filecontent": "content text", "fileencoding": "" }';
        $data = array(
            'filename'=>"mynewfile.txt",
            'modulepart'=>"medias",
            'ref'=>"",
            'subdir'=>"tmpphpunit/tmpphpunit2",
            'filecontent'=>"content text",
            'fileencoding'=>""
        );

        $result = getURLContent($url, 'POST', $data, 1);
        echo __METHOD__.' Result for sending document: '.var_export($result, true)."\n";
        echo __METHOD__.' curl_error_no: '.$result['curl_error_no']."\n";
        $object = json_decode($result['content'], true);
        $this->assertNotNull($object, 'Parsing of json result must no be null');
        $this->assertEquals('401', $object['error']['code']);


        // Send to existant directory

        dol_mkdir(DOL_DATA_ROOT.'/medias/tmpphpunit/tmpphpunit2');

        $data = array(
            'filename'=>"mynewfile.txt",
            'modulepart'=>"medias",
            'ref'=>"",
            'subdir'=>"tmpphpunit/tmpphpunit2",
            'filecontent'=>"content text",
            'fileencoding'=>""
        );

        $result2 = getURLContent($url, 'POST', $data, 1);
        echo __METHOD__.' Result for sending document: '.var_export($result2, true)."\n";
        echo __METHOD__.' curl_error_no: '.$result2['curl_error_no']."\n";
        $object2 = json_decode($result2['content'], true);
        $this->assertNotNull($object2, 'Parsing of json result must no be null');
        $this->assertEquals($result2['curl_error_no'], '');
        $this->assertEquals($result2['content'], 'true');

        dol_delete_dir_recursive(DOL_DATA_ROOT.'/medias/tmpphpunit');
    }
}
