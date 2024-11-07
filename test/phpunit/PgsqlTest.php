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
require_once dirname(__FILE__).'/CommonClassTest.class.php';

$langs->load("dict");

if (empty($user->id)) {
	print "Load permissions for admin user nb 1\n";
	$user->fetch(1);
	$user->getrights();
}

$conf->global->MAIN_DISABLE_ALL_MAILS = 1;


/**
 * Class for PHPUnit tests
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class PgsqlTest extends CommonClassTest
{
	/**
	 * testConvertSQLFromMysql
	 *
	 * @return	int
	 */
	public function testConvertSQLFromMysql()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		// Create a dummy db handler for pgsql
		$tmpdb = new DoliDBPgsql('pqsql', 'host', 'user', 'pass');

		/*
		$sql = "CREATE SEQUENCE __DATABASE__.llx_c_paiement_id_seq OWNED BY llx_c_paiement.id;";
		$result=$tmpdb->convertSQLFromMysql($sql);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals($result, "CREATE SEQUENCE __DATABASE__.llx_c_paiement_id_seq OWNED BY llx_c_paiement.id;");
		*/

		$sql = "ALTER TABLE llx_bank_account MODIFY COLUMN state_id integer USING state_id::integer;";
		$result = $tmpdb->convertSQLFromMysql($sql);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals($result, "-- ALTER TABLE llx_bank_account MODIFY COLUMN state_id integer USING state_id::integer; replaced by --\nALTER TABLE llx_bank_account ALTER COLUMN state_id TYPE integer USING state_id::integer;");

		$sql = "ALTER TABLE llx_table RENAME TO llx_table_new;";
		$result = $tmpdb->convertSQLFromMysql($sql);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals($result, "ALTER TABLE llx_table RENAME TO llx_table_new;");

		$sql = "ALTER TABLE llx_table ADD COLUMN newcol varchar(60) NOT NULL DEFAULT '0' AFTER existingcol;";
		$result = $tmpdb->convertSQLFromMysql($sql);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals($result, "ALTER TABLE llx_table ADD COLUMN newcol varchar(60) NOT NULL DEFAULT '0';");

		$sql = "ALTER TABLE llx_table CHANGE COLUMN oldname newname varchar(60);";
		$result = $tmpdb->convertSQLFromMysql($sql);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals($result, "-- ALTER TABLE llx_table CHANGE COLUMN oldname newname varchar(60); replaced by --\nALTER TABLE llx_table RENAME COLUMN oldname TO newname");

		$sql = "ALTER TABLE llx_table DROP COLUMN oldname;";
		$result = $tmpdb->convertSQLFromMysql($sql);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals($result, $sql);

		$sql = "ALTER TABLE llx_table MODIFY name varchar(60);";
		$result = $tmpdb->convertSQLFromMysql($sql);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals($result, "-- ALTER TABLE llx_table MODIFY name varchar(60); replaced by --\nALTER TABLE llx_table ALTER COLUMN name TYPE varchar(60);");

		// Create a constraint
		$sql = 'ALTER TABLE llx_tablechild ADD CONSTRAINT fk_tablechild_fk_fieldparent FOREIGN KEY (fk_fieldparent) REFERENCES llx_tableparent (rowid)';
		$result = $tmpdb->convertSQLFromMysql($sql);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals($result, $sql.' DEFERRABLE INITIALLY IMMEDIATE;');

		// Test GROUP_CONCAT (without SEPARATOR)
		$sql = "SELECT a.b, GROUP_CONCAT(a.c) FROM table GROUP BY a.b";
		$result = $tmpdb->convertSQLFromMysql($sql);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals($result, "SELECT a.b, STRING_AGG(a.c, ',') FROM table GROUP BY a.b", 'Test GROUP_CONCAT (without SEPARATOR)');

		// Test GROUP_CONCAT (with SEPARATOR)
		$sql = "SELECT a.b, GROUP_CONCAT(a.c SEPARATOR ',') FROM table GROUP BY a.b";
		$result = $tmpdb->convertSQLFromMysql($sql);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals($result, "SELECT a.b, STRING_AGG(a.c, ',') FROM table GROUP BY a.b", 'Test GROUP_CONCAT (with SEPARATOR)');

		return $result;
	}
}
