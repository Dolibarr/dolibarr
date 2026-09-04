<?php
/* Copyright (C) 2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2023 Alexandre Janniaux   <alexandre.janniaux@gmail.com>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2026       Nathan Pixodeo          <nathan@pixodeo.net>
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
 *      \file       test/phpunit/CompanyBankAccount.php
 *		\ingroup    test
 *      \brief      PHPUnit test
 *		\remarks	To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/societe/class/companybankaccount.class.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/files.lib.php';
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
class CompanyBankAccountTest extends CommonClassTest
{
	/**
	 * testCompanyBankAccountCreate
	 *
	 * @return	int
	 */
	public function testCompanyBankAccountCreate()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$soc = new Societe($db);
		$soc->name = "CompanyBankAccountTest Unittest";
		$socid = $soc->create($user);
		$this->assertLessThan($socid, 0, $soc->errorsToString());

		$localobject = new CompanyBankAccount($db);
		$localobject->initAsSpecimen();
		$localobject->socid = $socid;
		$result = $localobject->create($user);

		print __METHOD__." result=".$result." id=".$localobject->id."\n";
		$this->assertLessThan($result, 0, $localobject->errorsToString());
		return $localobject->id;
	}

	/**
	 * testCompanyBankAccountFetch
	 *
	 * @param	int		$id			Id of bank account
	 * @return	CompanyBankAccount  Bank account object
	 *
	 * @depends	testCompanyBankAccountCreate
	 * The depends says test is run only if previous is ok
	 */
	public function testCompanyBankAccountFetch($id)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new CompanyBankAccount($db);
		$result = $localobject->fetch($id);
		print __METHOD__." id=".$id." result=".$result."\n";
		$this->assertLessThan($result, 0);
		return $localobject;
	}

	/**
	 * Test that SEPA mandate generation honors the output directory passed through the object context.
	 *
	 * @param	CompanyBankAccount	$localobject	Bank account object
	 * @return	CompanyBankAccount				Bank account object
	 *
	 * @depends	testCompanyBankAccountFetch
	 */
	public function testSepaMandateForcedOutputDirectory($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$soc = new Societe($db);
		$result = $soc->fetch($localobject->socid);
		$this->assertGreaterThan(0, $result, $soc->errorsToString());

		$outputdir = $conf->societe->multidir_output[$soc->entity ?? $conf->entity].'/'.dol_sanitizeFileName((string) $soc->id);
		$moreparams = array(
			'use_companybankid' => $localobject->id,
			'force_dir_output' => $outputdir,
		);
		$langs->load('withdrawals');
		$objectref = dol_sanitizeFileName($localobject->ref);
		$expectedfile = $outputdir.'/'.$langs->transnoentitiesnoconv('SepaMandateShort').' '.$objectref.'-'.dol_sanitizeFileName($localobject->rum).'.pdf';
		if (file_exists($expectedfile)) {
			dol_delete_file($expectedfile);
		}

		try {
			$result = $soc->generateDocument('sepamandate', $langs, 0, 0, 0, $moreparams);
			$this->assertGreaterThan(0, $result, $soc->errorsToString());
			$this->assertFileExists($expectedfile);
		} finally {
			if (file_exists($expectedfile)) {
				dol_delete_file($expectedfile);
			}
		}

		return $localobject;
	}

	/**
	 * testCompanyBankAccountSetAsDefault
	 *
	 * @param   CompanyBankAccount  $localobject    Bank account
	 * @return  int
	 *
	 * @depends testCompanyBankAccountFetch
	 */
	public function testCompanyBankAccountSetAsDefault($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$result = $localobject->setAsDefault($localobject->id);
		print __METHOD__." id=".$localobject->id." result=".$result."\n";
		$this->assertLessThan($result, 0);
		return $localobject;
	}

	/**
	 * testCompanyBankAccountUpdate
	 *
	 * @param	CompanyBankAccount	$localobject	Bank account object
	 * @return	int
	 *
	 * @depends	testCompanyBankAccountFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testCompanyBankAccountUpdate($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject->owner = 'New owner';
		$result = $localobject->update($user);

		print __METHOD__." id=".$localobject->id." result=".$result."\n";
		$this->assertLessThan($result, 0);
		return $localobject;
	}

	/**
	 * testCompanyBankAccountOther
	 *
	 * @param	CompanyBankAccount	$localobject	Bank account
	 * @return	int
	 *
	 * @depends testCompanyBankAccountFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testCompanyBankAccountOther($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject->owner = 'New owner';
		$result = $localobject->update($user);

		print __METHOD__." id=".$localobject->id." result=".$result."\n";
		$this->assertLessThan($result, 0);
		return $localobject->id;
	}
}
