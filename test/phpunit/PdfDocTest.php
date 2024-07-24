<?php
/* Copyright (C) 2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2023 Alexandre Janniaux   <alexandre.janniaux@gmail.com>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 * or see https://www.gnu.org/
 */

/**
 *      \file       test/phpunit/PdfDocTest.php
 *		\ingroup    test
 *      \brief      PHPUnit test
 *		\remarks	To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/compta/facture/class/facture.class.php';
require_once dirname(__FILE__).'/../../htdocs/product/class/product.class.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/pdf.lib.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/doc.lib.php';

if (empty($user->id)) {
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
class PdfDocTest extends PHPUnit\Framework\TestCase
{
	protected $savconf;
	protected $savuser;
	protected $savlangs;
	protected $savdb;

	/**
	 * Constructor
	 * We save global variables into local variables
	 *
	 * @param 	string	$name		Name
	 * @return PdfDocTest
	 */
	public function __construct($name = '')
	{
		parent::__construct($name);

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

	/**
	 * setUpBeforeClass
	 *
	 * @return void
	 */
	public static function setUpBeforeClass(): void
	{
		global $conf,$user,$langs,$db;
		$db->begin();	// This is to have all actions inside a transaction even if test launched without suite.

		print __METHOD__."\n";
	}

	/**
	 * tearDownAfterClass
	 *
	 * @return	void
	 */
	public static function tearDownAfterClass(): void
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
	protected function setUp(): void
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
	protected function tearDown(): void
	{
		print __METHOD__."\n";
	}

	/**
	 * testPdfDocGetLineDesc
	 *
	 * @return void
	 */
	public function testPdfDocGetLineDesc()
	{
		global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		$localproduct=new Product($db);
		$result = $localproduct->fetch(0, 'PINKDRESS');
		if ($result < 0) {
			print "\n".__METHOD__." Failed to make the fetch of product PINKDRESS. ".$localproduct->error;
			die(1);
		}
		$product_id = $localproduct->id;
		if ($product_id <= 0) {
			print "\n".__METHOD__." A product with ref PINKDRESS must exists into database. Create it manually before running the test";
			die(1);
		}

		$localobject=new Facture($db);
		$localobject->initAsSpecimen();
		$localobject->lines=array();
		$localobject->lines[0]=new FactureLigne($db);
		$localobject->lines[0]->fk_product=$product_id;
		$localobject->lines[0]->label='Label 1';
		$localobject->lines[0]->desc="This is a description with a é accent\n(Country of origin: France)";

		$result=pdf_getlinedesc($localobject, 0, $langs);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals("PINKDRESS - Label 1<br>This is a description with a &eacute; accent<br>(Country of origin: France)", $result);

		$result=doc_getlinedesc($localobject->lines[0], $langs);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals("PINKDRESS - Label 1\nThis is a description with a é accent\n(Country of origin: France)", $result);
	}

	/**
	* testPdfGetHeightForLogo
	*
	* @return void
	*/
	public function testPdfGetHeightForLogo()
	{
		$file=dirname(__FILE__).'/img250x50.jpg';
		$result=pdf_getHeightForLogo($file);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals($result, 20);
		$file=dirname(__FILE__).'/img250x20.png';
		$result=pdf_getHeightForLogo($file);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals($result, 10.4);
	}
}
