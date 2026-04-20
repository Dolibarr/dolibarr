<?php
/* Copyright (C) 2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2023 Alexandre Janniaux   <alexandre.janniaux@gmail.com>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
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
			if ($obj->Field == 'code') {
				$savtype = $obj->Type;
				$savnull = $obj->Null;
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
}
