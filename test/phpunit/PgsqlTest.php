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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 * or see https://www.gnu.org/
 */

/**
 *      \file       test/phpunit/PgsqlTest.php
 *		\ingroup    test
 *      \brief      PHPUnit test
 *		\remarks	To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/core/db/pgsql.class.php';
$langs->load("dict");

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
class PgsqlTest extends PHPUnit\Framework\TestCase
{
	protected $savconf;
	protected $savuser;
	protected $savlangs;
	protected $savdb;

	/**
	 * Constructor
	 * We save global variables into local variables
	 *
	 * @return ContactTest
	 */
	public function __construct()
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

    /**
     * setUpBeforeClass
     *
     * @return void
     */
    public static function setUpBeforeClass()
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
     * testConvertSQLFromMysql
     *
     * @return	int
     */
    public function testConvertSQLFromMysql()
    {
    	global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

        $sql="ALTER TABLE llx_table RENAME TO llx_table_new;";
        $result=DoliDBPgsql::convertSQLFromMysql($sql);
        print __METHOD__." result=".$result."\n";
        $this->assertEquals($result, "ALTER TABLE llx_table RENAME TO llx_table_new;");

        $sql="ALTER TABLE llx_table ADD COLUMN newcol varchar(60) NOT NULL DEFAULT '0' AFTER existingcol;";
        $result=DoliDBPgsql::convertSQLFromMysql($sql);
        print __METHOD__." result=".$result."\n";
        $this->assertEquals($result, "ALTER TABLE llx_table ADD COLUMN newcol varchar(60) NOT NULL DEFAULT '0';");

        $sql="ALTER TABLE llx_table CHANGE COLUMN oldname newname varchar(60);";
        $result=DoliDBPgsql::convertSQLFromMysql($sql);
        print __METHOD__." result=".$result."\n";
        $this->assertEquals($result, "-- ALTER TABLE llx_table CHANGE COLUMN oldname newname varchar(60); replaced by --\nALTER TABLE llx_table RENAME COLUMN oldname TO newname");

        $sql="ALTER TABLE llx_table DROP COLUMN oldname;";
        $result=DoliDBPgsql::convertSQLFromMysql($sql);
        print __METHOD__." result=".$result."\n";
        $this->assertEquals($result, $sql);

        $sql="ALTER TABLE llx_table MODIFY name varchar(60);";
        $result=DoliDBPgsql::convertSQLFromMysql($sql);
        print __METHOD__." result=".$result."\n";
        $this->assertEquals($result, "-- ALTER TABLE llx_table MODIFY name varchar(60); replaced by --\nALTER TABLE llx_table ALTER COLUMN name TYPE varchar(60);");

        // Create a constraint
		$sql='ALTER TABLE llx_tablechild ADD CONSTRAINT fk_tablechild_fk_fieldparent FOREIGN KEY (fk_fieldparent) REFERENCES llx_tableparent (rowid)';
		$result=DoliDBPgsql::convertSQLFromMysql($sql);
        print __METHOD__." result=".$result."\n";
    	$this->assertEquals($result, $sql.' DEFERRABLE INITIALLY IMMEDIATE;');

        // Test GROUP_CONCAT (without SEPARATOR)
		$sql="SELECT a.b, GROUP_CONCAT(a.c) FROM table GROUP BY a.b";
		$result=DoliDBPgsql::convertSQLFromMysql($sql);
        print __METHOD__." result=".$result."\n";
    	$this->assertEquals($result, "SELECT a.b, STRING_AGG(a.c, ',') FROM table GROUP BY a.b", 'Test GROUP_CONCAT (without SEPARATOR)');

        // Test GROUP_CONCAT (with SEPARATOR)
		$sql="SELECT a.b, GROUP_CONCAT(a.c SEPARATOR ',') FROM table GROUP BY a.b";
		$result=DoliDBPgsql::convertSQLFromMysql($sql);
        print __METHOD__." result=".$result."\n";
    	$this->assertEquals($result, "SELECT a.b, STRING_AGG(a.c, ',') FROM table GROUP BY a.b", 'Test GROUP_CONCAT (with SEPARATOR)');

    	return $result;
    }
}
