<?php
/* Copyright (C) 2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2023 Alexandre Janniaux   <alexandre.janniaux@gmail.com>
 * Copyright (C) 2024-2026  Frédéric France         <frederic.france@free.fr>
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
 *      \file       test/phpunit/DoliDBTest.php
 *		\ingroup    test
 *      \brief      PHPUnit test
 *		\remarks	To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/core/class/discount.class.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';

if (empty($user->id)) {
	print "Load permissions for admin user nb 1\n";
	$user->fetch(1);
	$user->loadRights();
}
$conf->global->MAIN_DISABLE_ALL_MAILS = 1;


/**
 * Class for PHPUnit tests
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class DoliDBTest extends CommonClassTest
{
	/**
	 * testDDLUpdateField
	 *
	 * @return	int
	 */
	public function testDDLCreateTable()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$namedic = MAIN_DB_PREFIX.'tmptesttabletoremove';

		$res = $db->DDLDropTable($namedic);

		$columns = array(
			'rowid' => array('type' => 'integer', 'AUTO_INCREMENT PRIMARY KEY'),
			'code' => array('type' => 'varchar', 'value' => 255, 'null'=>'NOT NULL'),
			'label' => array('type' => 'varchar', 'value' => 255, 'null'=>'NOT NULL'),
			'position' => array('type' => 'integer', 'null'=>'NULL'),
			'use_default' => array('type' => 'varchar', 'value' => 1, 'default'=>'1'),
			'active' => array('type' => 'integer')
		);
		$primaryKey = 'rowid';

		print __METHOD__.' db->type = '.$db->type."\n";

		$res = $db->DDLCreateTable($namedic, $columns, $primaryKey, "");

		$this->assertEquals(1, $res);
		print __METHOD__." result=".$res."\n";
	}

	/**
	 * testDDLUpdateField
	 *
	 * @return	int
	 */
	public function testDDLUpdateField()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		print __METHOD__.' db->type = '.$db->type."\n";

		$savtype = '';
		$savnull = '';
		$resql = $db->DDLDescTable($db->prefix().'c_paper_format', 'code');
		while ($obj = $db->fetch_object($resql)) {
			// MySQL SHOW COLUMNS returns "Field", PostgreSQL DDLDescTable returns "attname"
			$fieldname = $obj->Field ?? ($obj->attname ?? '');
			if ($fieldname == 'code') {
				$savtype = $obj->Type ?? '';
				$savnull = $obj->Null ?? '';
			}
		}

		// Set new field
		$field_desc = array('type' => 'varchar', 'value' => '17', 'null' => 'NOT NULL');

		$result = $db->DDLUpdateField($db->prefix().'c_paper_format', 'code', $field_desc);
		$this->assertEquals(1, $result);
		print __METHOD__." result=".$result."\n";

		// TODO Use $savtype and $savnull instead of hard coded
		$field_desc = array('type'=>'varchar', 'value'=>'16', 'null'=>'NOT NULL', 'default'=>'aaaabbbbccccdddd');

		$result = $db->DDLUpdateField($db->prefix().'c_paper_format', 'code', $field_desc);

		$this->assertEquals(1, $result);
		print __METHOD__." result=".$result."\n";

		return $result;
	}

	/**
	 * testPrepareExecute
	 *
	 * @return	void
	 */
	public function testPrepareExecute()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		print __METHOD__.' db->type = '.$db->type."\n";

		$table = MAIN_DB_PREFIX.'tmptestpreparedstmt';
		$db->query("DROP TABLE IF EXISTS ".$table);
		$res = $db->query("CREATE TABLE ".$table." (id integer, label varchar(50))");
		$this->assertNotFalse($res, 'Failed to create test table');

		// INSERT with bound parameters, including a value containing a single quote
		$ins = $db->prepare("INSERT INTO ".$table." (id, label) VALUES (?, ?)");
		$this->assertNotFalse($ins, 'prepare(INSERT) returned false: '.$db->lasterror());
		$this->assertNotFalse($db->execute($ins, array(1, 'alpha')), 'execute(INSERT 1): '.$db->lasterror());
		$r2 = $db->execute($ins, array(2, "o'brien"));
		$this->assertNotFalse($r2, 'execute(INSERT 2): '.$db->lasterror());
		$this->assertSame(1, $db->affected_rows($r2));
		$this->assertNotFalse($db->execute($ins, array(3, 'gamma')), 'execute(INSERT 3): '.$db->lasterror());

		// SELECT with a bound parameter -> resultset usable with the usual fetch helpers.
		// Also checks the "o'brien" value (single quote) round-tripped through the binding.
		$sel = $db->prepare("SELECT id, label FROM ".$table." WHERE id >= ? ORDER BY id");
		$this->assertNotFalse($sel, 'prepare(SELECT): '.$db->lasterror());
		$rs = $db->execute($sel, array(2));
		$this->assertNotFalse($rs, 'execute(SELECT): '.$db->lasterror());
		$this->assertSame(2, $db->num_rows($rs));
		$rows = array();
		while ($o = $db->fetch_object($rs)) {
			$rows[(int) $o->id] = $o->label;
		}
		$db->free($rs);
		$this->assertSame(array(2 => "o'brien", 3 => 'gamma'), $rows);

		// The same statement can be re-executed with different parameters
		$rs = $db->execute($sel, array(1));
		$this->assertSame(3, $db->num_rows($rs));
		$db->free($rs);

		// UPDATE with bound parameters -> affected rows
		$upd = $db->prepare("UPDATE ".$table." SET label = ? WHERE id < ?");
		$this->assertNotFalse($upd, 'prepare(UPDATE): '.$db->lasterror());
		$ru = $db->execute($upd, array('changed', 3));
		$this->assertNotFalse($ru, 'execute(UPDATE): '.$db->lasterror());
		$this->assertSame(2, $db->affected_rows($ru));

		$rs = $db->execute($sel, array(1));
		$rows = array();
		while ($o = $db->fetch_object($rs)) {
			$rows[(int) $o->id] = $o->label;
		}
		$db->free($rs);
		$this->assertSame(array(1 => 'changed', 2 => 'changed', 3 => 'gamma'), $rows);

		// A failing prepare() returns false, it does not raise
		$bad = $db->prepare("SELECT * FROM ".MAIN_DB_PREFIX."tmp_table_that_does_not_exist WHERE x = ?");
		$this->assertFalse($bad, 'prepare() on a bad query should return false');

		// A failing execute() returns false and the usual error helpers are populated
		$db->query("ALTER TABLE ".$table." ADD PRIMARY KEY (id)");
		$dup = $db->prepare("INSERT INTO ".$table." (id, label) VALUES (?, ?)");
		$this->assertNotFalse($db->execute($dup, array(9, 'x')), 'execute(INSERT 9): '.$db->lasterror());
		$this->assertFalse($db->execute($dup, array(9, 'x')), 'execute() with a duplicate key should return false');
		$this->assertSame('DB_ERROR_RECORD_ALREADY_EXISTS', $db->errno());
		$this->assertNotEmpty($db->lasterror());

		$db->query("DROP TABLE ".$table);

		print __METHOD__." OK\n";
	}
}
