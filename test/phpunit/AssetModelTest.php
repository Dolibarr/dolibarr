<?php
/* Copyright (C) 2007-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2022 Alice Adminson <aadminson@example.com>
 * Copyright (C) 2023      Alexandre Janniaux   <alexandre.janniaux@gmail.com>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    test/phpunit/AssetModelTest.php
 * \ingroup asset
 * \brief   PHPUnit test for AssetModel class.
 */

global $conf, $user, $langs, $db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver

//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/asset/class/assetmodel.class.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';

if (empty($user->id)) {
	print "Load permissions for admin user nb 1\n";
	$user->fetch(1);
	$user->loadRights();
}
$conf->global->MAIN_DISABLE_ALL_MAILS = 1;

$langs->load("main");


/**
 * Class AssetModel
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class AssetModelTest extends CommonClassTest
{
	/**
	 * testAssetModelCreate
	 *
	 * @return int
	 */
	public function testAssetModelCreate()
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new AssetModel($db);
		$localobject->initAsSpecimen();
		$result = $localobject->create($user);

		print __METHOD__." result=".$result."\n";
		$this->assertLessThan($result, 0);

		return $result;
	}

	/**
	 * testAssetModelFetch
	 *
	 * @param   int	$id Id of object
	 * @return  AssetModel
	 *
	 * @depends	testAssetModelCreate
	 * The depends says test is run only if previous is ok
	 */
	public function testAssetModelFetch($id)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new AssetModel($db);
		$result = $localobject->fetch($id);

		$this->assertLessThan($result, 0);
		print __METHOD__." id=".$id." result=".$result."\n";
		return $localobject;
	}

	/**
	 * testAssetModelUpdate
	 * @param  AssetModel $localobject AssetModel
	 * @return int
	 *
	 * @depends	testAssetModelFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testAssetModelUpdate($localobject)
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject->note_private = 'New note private after update';
		$result = $localobject->update($user);

		$this->assertLessThan($result, 0);
		print __METHOD__." id=".$localobject->id." result=".$result."\n";

		return $result;
	}

	/**
	 * testAssetModelDelete
	 *
	 * @param	int		$id		Id of object
	 * @return	int
	 *
	 * @depends	testAssetModelUpdate
	 * The depends says test is run only if previous is ok
	 */
	public function testAssetModelDelete($id)
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new AssetModel($db);
		$result = $localobject->fetch($id);
		$result = $localobject->delete($user);

		print __METHOD__." id=".$id." result=".$result."\n";
		$this->assertLessThan($result, 0);
		return $result;
	}
}
