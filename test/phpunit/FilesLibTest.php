<?php
/* Copyright (C) 2010-2012	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2012		Regis Houssin		<regis@dolibarr.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 *      \file       test/phpunit/FilesLibTest.php
 *		\ingroup    test
 *      \brief      PHPUnit test
 *		\remarks	To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/files.lib.php';

if (empty($user->id))
{
	print "Load permissions for admin user nb 1\n";
	$user->fetch(1);
	$user->getrights();
}
$conf->global->MAIN_DISABLE_ALL_MAILS=1;


/**
 * Class for PHPUnit tests
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class FilesLibTest extends PHPUnit_Framework_TestCase
{
	protected $savconf;
	protected $savuser;
	protected $savlangs;
	protected $savdb;

	/**
	 * Constructor
	 * We save global variables into local variables
	 *
	 * @return FilesLibTest
	 */
	function FilesLibTest()
	{
		//$this->sharedFixture
		global $conf,$user,$langs,$db;
		$this->savconf=$conf;
		$this->savuser=$user;
		$this->savlangs=$langs;
		$this->savdb=$db;

		print __METHOD__." db->type=".$db->type." user->id=".$user->id;
		//print " - db ".$db->db;
		print "\n";
	}

	// Static methods
  	public static function setUpBeforeClass()
    {
    	global $conf,$user,$langs,$db;
		$db->begin();	// This is to have all actions inside a transaction even if test launched without suite.

    	print __METHOD__."\n";
    }
    public static function tearDownAfterClass()
    {
    	global $conf,$user,$langs,$db;
		$db->rollback();

		print __METHOD__."\n";
    }

	/**
	 * Init phpunit tests
	 *
	 * @return	void
	 */
    protected function setUp()
    {
    	global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		print __METHOD__."\n";
    }
	/**
	 * End phpunit tests
	 *
	 * @return	void
	 */
    protected function tearDown()
    {
    	print __METHOD__."\n";
    }

   /**
    * testDolCountNbOfLine
    *
    * @return	int
    */
    public function testDolCountNbOfLine()
    {
    	global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		$file=dirname(__FILE__).'/Example_import_company_1.csv';
		$result=dol_count_nb_of_line($file);
    	print __METHOD__." result=".$result."\n";
		$this->assertEquals(2,$result);

		return $result;
    }

   /**
    * testDolIsFileDir
    *
    * @return	int
    */
    public function testDolIsFileDir()
    {
    	global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		$file=dirname(__FILE__).'/Example_import_company_1.csv';

		$result=dol_is_file($file);
    	print __METHOD__." result=".$result."\n";
		$this->assertTrue($result);

		$result=dol_is_dir($file);
    	print __METHOD__." result=".$result."\n";
		$this->assertFalse($result);

		return $result;
    }

    /**
     * testDolOther
     *
     * @return boolean
    */
    public function testDolOther()
    {
        global $conf,$user,$langs,$db;
        $conf=$this->savconf;
        $user=$this->savuser;
        $langs=$this->savlangs;
        $db=$this->savdb;

        $url='http://www.dolibarr.org';
  		$result=dol_is_url($url);
        print __METHOD__." result=".$result."\n";
        $this->assertTrue($result);

        $url='https://www.dolibarr.org';
  		$result=dol_is_url($url);
        print __METHOD__." result=".$result."\n";
        $this->assertTrue($result);

        $url='file://www.dolibarr.org/download/file.zip';
        $result=dol_is_url($url);
        print __METHOD__." result=".$result."\n";
        $this->assertTrue($result);

        return $result;
    }

    /**
     * testDolCopyMove
     *
     * @return	int
     */
    public function testDolCopyMove()
    {
        global $conf,$user,$langs,$db;
        $conf=$this->savconf;
        $user=$this->savuser;
        $langs=$this->savlangs;
        $db=$this->savdb;

        $file=dirname(__FILE__).'/Example_import_company_1.csv';

        $result=dol_copy($file, '/adir/that/does/not/exists/file.csv');
        print __METHOD__." result=".$result."\n";
        $this->assertLessThan(0,$result);    // We should have error

        $result=dol_copy($file, $conf->admin->dir_temp.'/file.csv',0,1);
        print __METHOD__." result=".$result."\n";
        $this->assertGreaterThanOrEqual(1,$result);    // Should be 1

        // Again to test with overwriting=0
        $result=dol_copy($file, $conf->admin->dir_temp.'/file.csv',0,0);
        print __METHOD__." result=".$result."\n";
        $this->assertEquals(0,$result);    // Should be 0

        // Again to test with overwriting=1
        $result=dol_copy($file, $conf->admin->dir_temp.'/file.csv',0,1);
        print __METHOD__." result=".$result."\n";
        $this->assertGreaterThanOrEqual(1,$result);    // Should be 1

        // Again to test with overwriting=1
        $result=dol_move($conf->admin->dir_temp.'/file.csv',$conf->admin->dir_temp.'/file2.csv',0,1);
        print __METHOD__." result=".$result."\n";
        $this->assertTrue($result);

        $result=dol_delete_file($conf->admin->dir_temp.'/file2.csv');
        print __METHOD__." result=".$result."\n";
        $this->assertTrue($result);

        // Again to test no erreor when deleteing a non existing file
        $result=dol_delete_file($conf->admin->dir_temp.'/file2.csv');
        print __METHOD__." result=".$result."\n";
        $this->assertTrue($result);
    }
    
    /**
     * testDolMimeType
     *
     * @return	string
     */
    public function testDolMimeType()
    {
        global $conf,$user,$langs,$db;
        $conf=$this->savconf;
        $user=$this->savuser;
        $langs=$this->savlangs;
        $db=$this->savdb;

		// file.png
		$result=dol_mimetype('file.png','',0);
        $this->assertEquals('image/png',$result);
		$result=dol_mimetype('file.png','',1);
        $this->assertEquals('png',$result);
		$result=dol_mimetype('file.png','',2);
		$this->assertEquals('image.png',$result);
		$result=dol_mimetype('file.png','',3);
        $this->assertEquals('',$result);
		// file.odt
		$result=dol_mimetype('file.odt','',0);
        $this->assertEquals('application/vnd.oasis.opendocument.text',$result);
		$result=dol_mimetype('file.odt','',1);
        $this->assertEquals('vnd.oasis.opendocument.text',$result);
		$result=dol_mimetype('file.odt','',2);
		$this->assertEquals('ooffice.png',$result);
		$result=dol_mimetype('file.odt','',3);
        $this->assertEquals('',$result);
		// file.php
		$result=dol_mimetype('file.php','',0);
        $this->assertEquals('text/plain',$result);
		$result=dol_mimetype('file.php','',1);
        $this->assertEquals('plain',$result);
		$result=dol_mimetype('file.php','',2);
		$this->assertEquals('php.png',$result);
		$result=dol_mimetype('file.php','',3);
        $this->assertEquals('php',$result);        
		// file.php.noexe
		$result=dol_mimetype('file.php.noexe','',0);
        $this->assertEquals('text/plain',$result);
    }		
}
?>