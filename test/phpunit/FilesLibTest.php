<?php
/* Copyright (C) 2010-2012	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2012		Regis Houssin		<regis.houssin@inodbox.com>
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
 *      \file       test/phpunit/FilesLibTest.php
 *		\ingroup    test
 *      \brief      PHPUnit test
 *		\remarks	To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
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
	function __construct()
	{
		parent::__construct();

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

    // tear down after class
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
     * testDolBasename
     *
     * @return	int
     */
    public function testDolBasename()
    {
    	global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

        $result=dol_basename('adir/afile');
    	print __METHOD__." result=".$result."\n";
		$this->assertEquals('afile',$result);

		$result=dol_basename('adir/afile/');
    	print __METHOD__." result=".$result."\n";
		$this->assertEquals('afile',$result);

		$result=dol_basename('adir/νεο');    // With cyrillic data. Here basename fails to return correct value
    	print __METHOD__." result=".$result."\n";
		$this->assertEquals('νεο',$result);

		$result=dol_basename('adir/νεο/');    // With cyrillic data. Here basename fails to return correct value
    	print __METHOD__." result=".$result."\n";
		$this->assertEquals('νεο',$result);
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
		$this->assertEquals(3,$result);

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


    /**
     * testDolDeleteDir
     *
     * @return	int
     */
    public function testDolDeleteDir()
    {
    	global $conf,$user,$langs,$db;
    	$conf=$this->savconf;
    	$user=$this->savuser;
    	$langs=$this->savlangs;
    	$db=$this->savdb;

    	$dirout=$conf->admin->dir_temp.'/test';
    	$dirout2=$conf->admin->dir_temp.'/test2';

    	$count=0;
    	$result=dol_delete_dir_recursive($dirout,$count);	// If it has no permission to delete, it will fails as if dir does not exists, so we can't test it
    	print __METHOD__." result=".$result."\n";
    	$this->assertGreaterThanOrEqual(0,$result);

    	$count=0;
    	$countdeleted=0;
    	$result=dol_delete_dir_recursive($dirout,$count,1,0,$countdeleted);	// If it has no permission to delete, it will fails as if dir does not exists, so we can't test it
    	print __METHOD__." result=".$result."\n";
    	$this->assertGreaterThanOrEqual(0,$result);
    	$this->assertGreaterThanOrEqual(0,$countdeleted);

    	dol_mkdir($dirout2);
    	$count=0;
    	$countdeleted=0;
    	$result=dol_delete_dir_recursive($dirout2,$count,1,0,$countdeleted);	// If it has no permission to delete, it will fails as if dir does not exists, so we can't test it
    	print __METHOD__." result=".$result."\n";
    	$this->assertGreaterThanOrEqual(1,$result);
    	$this->assertGreaterThanOrEqual(1,$countdeleted);
    }


    /**
     * testDolCopyMoveDelete
     *
     * @return	int
     *
     * @depends	testDolDeleteDir
     * The depends says test is run only if previous is ok
     */
    public function testDolCopyMoveDelete()
    {
        global $conf,$user,$langs,$db;
        $conf=$this->savconf;
        $user=$this->savuser;
        $langs=$this->savlangs;
        $db=$this->savdb;

        $file=dirname(__FILE__).'/Example_import_company_1.csv';

        $result=dol_copy($file, '/adir/that/does/not/exists/file.csv');
        print __METHOD__." result=".$result."\n";
        $this->assertLessThan(0,$result,'copy dir that does not exists');    // We should have error

        $result=dol_copy($file, $conf->admin->dir_temp.'/file.csv',0,1);
        print __METHOD__." result=".$result."\n";
        $this->assertGreaterThanOrEqual(1,$result, 'copy file ('.$file.') into a dir that exists ('.$conf->admin->dir_temp.'/file.csv'.')');    // Should be 1

        // Again to test with overwriting=0
        $result=dol_copy($file, $conf->admin->dir_temp.'/file.csv',0,0);
        print __METHOD__." result=".$result."\n";
        $this->assertEquals(0,$result, 'copy destination already exists, no overwrite');    // Should be 0

        // Again to test with overwriting=1
        $result=dol_copy($file, $conf->admin->dir_temp.'/file.csv',0,1);
        print __METHOD__." result=".$result."\n";
        $this->assertGreaterThanOrEqual(1,$result,'copy destination already exists, overwrite');    // Should be 1

        // To test a move that should work
        $result=dol_move($conf->admin->dir_temp.'/file.csv',$conf->admin->dir_temp.'/file2.csv',0,1);
        print __METHOD__." result=".$result."\n";
        $this->assertTrue($result,'move with default mask');

        // To test a move that should work with forced mask
        $result=dol_move($conf->admin->dir_temp.'/file2.csv',$conf->admin->dir_temp.'/file3.csv','0754',1); // file shoutld be rwxr-wr--
        print __METHOD__." result=".$result."\n";
        $this->assertTrue($result,'move with forced mask');

        // To test a delete that should success
        $result=dol_delete_file($conf->admin->dir_temp.'/file3.csv');
        print __METHOD__." result=".$result."\n";
        $this->assertTrue($result,'delete file');

        // Again to test there is error when deleting a non existing file with option disableglob
        $result=dol_delete_file($conf->admin->dir_temp.'/file3.csv',1,1);
        print __METHOD__." result=".$result."\n";
        $this->assertFalse($result,'delete file that does not exists with disableglo must return ko');

        // Again to test there is no error when deleting a non existing file without option disableglob
        $result=dol_delete_file($conf->admin->dir_temp.'/file3csv',0,1);
        print __METHOD__." result=".$result."\n";
        $this->assertTrue($result,'delete file that does not exists without disabling glob must return ok');

        // Test copy with special char / delete with blob
        $result=dol_copy($file, $conf->admin->dir_temp.'/file with [x] and é.csv',0,1);
        print __METHOD__." result=".$result."\n";
        $this->assertGreaterThanOrEqual(1,$result,'copy file with special chars, overwrite');    // Should be 1

        // Try to delete using a glob criteria
        $result=dol_delete_file($conf->admin->dir_temp.'/file with [x]*é.csv');
        print __METHOD__." result=".$result."\n";
        $this->assertTrue($result,'delete file using glob');
    }

    /**
     * testDolCompressUnCompress
     *
     * @return	string
     *
     * @depends	testDolCopyMoveDelete
     * The depends says test is run only if previous is ok
     */
    public function testDolCompressUnCompress()
    {
        global $conf,$user,$langs,$db;
        $conf=$this->savconf;
        $user=$this->savuser;
        $langs=$this->savlangs;
        $db=$this->savdb;

        $format='zip';
        $filein=dirname(__FILE__).'/Example_import_company_1.csv';
        $fileout=$conf->admin->dir_temp.'/test.'.$format;
        $dirout=$conf->admin->dir_temp.'/test';

        dol_delete_file($fileout);
        $count=0;
        dol_delete_dir_recursive($dirout,$count,1);

        $result=dol_compress_file($filein, $fileout, $format);
        print __METHOD__." result=".$result."\n";
        $this->assertGreaterThanOrEqual(1,$result);

        $result=dol_uncompress($fileout, $dirout);
        print __METHOD__." result=".join(',',$result)."\n";
        $this->assertEquals(0,count($result));
    }

    /**
     * testDolDirList
     *
     * @return	string
     *
     * @depends	testDolCompressUnCompress
     * The depends says test is run only if previous is ok
     */
    public function testDolDirList()
    {
        global $conf,$user,$langs,$db;

        // Scan dir to guaruante we on't have library jquery twice (we accept exception of duplicte into ckeditor because all dir is removed for debian package, so there is no duplicate).
        $founddirs=dol_dir_list(DOL_DOCUMENT_ROOT.'/includes/', 'files', 1, '^jquery\.js', array('ckeditor'));
        print __METHOD__." count(founddirs)=".count($founddirs)."\n";
        $this->assertEquals(1,count($founddirs));
    }


    /**
     * testDolCheckSecureAccessDocument
     *
     * @return void
     */
    public function testDolCheckSecureAccessDocument()
    {
        global $conf,$user,$langs,$db;
        $conf=$this->savconf;
        $user=$this->savuser;
        $langs=$this->savlangs;
        $db=$this->savdb;


        //$dummyuser=new User($db);
        //$result=restrictedArea($dummyuser,'societe');

        // We save user properties
        $savpermlire = $user->rights->facture->lire;
        $savpermcreer = $user->rights->facture->creer;


		// Check access to SPECIMEN
        $user->rights->facture->lire = 0;
        $user->rights->facture->creer = 0;
        $filename='SPECIMEN.pdf';             // Filename relative to module part
        $result=dol_check_secure_access_document('facture', $filename, 0, '', '', 'read');
        $this->assertEquals(1,$result['accessallowed']);


        // Check read permission
        $user->rights->facture->lire = 1;
        $user->rights->facture->creer = 1;
        $filename='FA010101/FA010101.pdf';    // Filename relative to module part
        $result=dol_check_secure_access_document('facture', $filename, 0, '', '', 'read');
        $this->assertEquals(1,$result['accessallowed']);

        $user->rights->facture->lire = 0;
        $user->rights->facture->creer = 0;
        $filename='FA010101/FA010101.pdf';    // Filename relative to module part
        $result=dol_check_secure_access_document('facture', $filename, 0, '', '', 'read');
        $this->assertEquals(0,$result['accessallowed']);

        // Check write permission
        $user->rights->facture->lire = 0;
        $user->rights->facture->creer = 0;
        $filename='FA010101/FA010101.pdf';    // Filename relative to module part
        $result=dol_check_secure_access_document('facture', $filename, 0, '', '', 'write');
        $this->assertEquals(0,$result['accessallowed']);

        $user->rights->facture->lire = 1;
        $user->rights->facture->creer = 1;
        $filename='FA010101/FA010101.pdf';    // Filename relative to module part
        $result=dol_check_secure_access_document('facture', $filename, 0, '', '', 'write');
        $this->assertEquals(1,$result['accessallowed']);

        $user->rights->facture->lire = 1;
        $user->rights->facture->creer = 0;
        $filename='FA010101/FA010101.pdf';    // Filename relative to module part
        $result=dol_check_secure_access_document('facture', $filename, 0, '', '', 'write');
        $this->assertEquals(0,$result['accessallowed']);


        // We restore user properties
        $user->rights->facture->lire = $savpermlire;
        $user->rights->facture->creer = $savpermcreer;
    }
}
