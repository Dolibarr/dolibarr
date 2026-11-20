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
 *      \file       test/phpunit/FichinterTest.php
 *		\ingroup    test
 *      \brief      PHPUnit test
 *		\remarks	To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db,$mysoc;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';
require_once dirname(__FILE__).'/../../htdocs/fichinter/class/fichinter.class.php';

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
class FichinterTest extends CommonClassTest
{
	/**
	 * Both technician signature layouts remain detectable after the customer's signature.
	 *
	 * @return void
	 */
	public function testTechnicianSignedPdfIsRecognized()
	{
		$intervention = new Fichinter($this->savdb);
		$intervention->last_main_doc = 'ficheinter/IN2601-0001/IN2601-0001_signed-20261120130000.pdf';
		$this->assertFalse($intervention->hasTechnicianSignedPdf());

		$intervention->last_main_doc = 'ficheinter/IN2601-0001/IN2601-0001_technician-signed-0123456789abcdef01234567.pdf';
		$this->assertTrue($intervention->hasTechnicianSignedPdf());

		$intervention->last_main_doc = 'ficheinter/IN2601-0001/IN2601-0001_technician-signed-0123456789abcdef01234567_signed-20261120130000.pdf';
		$this->assertTrue($intervention->hasTechnicianSignedPdf());
		$this->assertTrue($intervention->hasTechnicianSignedPdf(true));

		$intervention->last_main_doc = 'ficheinter/IN2601-0001/IN2601-0001_technician-signed-box-0123456789abcdef01234567_signed-20261120130000.pdf';
		$this->assertTrue($intervention->hasTechnicianSignedPdf());
		$this->assertFalse($intervention->hasTechnicianSignedPdf(true));
	}

	/**
	 * Signing adds the native signature label to the validated status.
	 *
	 * @return void
	 */
	public function testValidatedSignatureStatusLabels()
	{
		global $langs;

		$previousLangs = $langs;
		$langs = new Translate('', $this->savconf);
		$langs->setDefaultLang('fr_FR');

		try {
			$langs->load('main', 0, 0, '', 1);
			$intervention = new Fichinter($this->savdb);
			$intervention->status = Fichinter::STATUS_VALIDATED;
			$intervention->statut = Fichinter::STATUS_VALIDATED;
			$intervention->signed_status = Fichinter::$SIGNED_STATUSES['STATUS_SIGNED_SENDER'];
			$this->assertSame('Validé (Signé en interne)', $intervention->getLibStatut());

			$intervention->signed_status = Fichinter::$SIGNED_STATUSES['STATUS_SIGNED_ALL'];
			$this->assertSame('Validé (Signé en interne et par le tiers)', $intervention->getLibStatut());
		} finally {
			$langs = $previousLangs;
		}
	}

	/**
	 * testFichinterCreate
	 *
	 * @return	int
	 */
	public function testFichinterCreate()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$soc = new Societe($db);
		$soc->name = "FichinterTest Unittest";
		$socid = $soc->create($user);
		$this->assertLessThan($socid, 0, $soc->errorsToString());

		$localobject = new Fichinter($db);
		$localobject->initAsSpecimen();
		$localobject->socid = $socid;
		$result = $localobject->create($user);

		print __METHOD__." result=".$result."\n";
		$this->assertLessThan($result, 0, $localobject->errorsToString());

		return $result;
	}

	/**
	 * testFichinterFetch
	 *
	 * @param	int		$id		Id of intervention
	 * @return	int
	 *
	 * @depends	testFichinterCreate
	 * The depends says test is run only if previous is ok
	 */
	public function testFichinterFetch($id)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new Fichinter($db);
		$result = $localobject->fetch($id);

		print __METHOD__." id=".$id." result=".$result."\n";
		$this->assertLessThan($result, 0, $localobject->errorsToString());

		return $localobject;
	}

	/**
	 * testFichinterUpdate
	 *
	 * Check that a intervention object can be updated.
	 *
	 * @param 	Object		$localobject 	An existing intervention object to update.
	 * @return 	Fichinter					Intervention object with data fetched and description changed
	 *
	 * @depends testFichinterFetch
	 */
	public function testFichinterUpdate($localobject)
	{
		global $user;

		$localobject->description = "foobar";

		$result = $localobject->update($user);
		print __METHOD__." id=".$localobject->id." result=".$result."\n";
		$this->assertLessThan($result, 0, $localobject->errorsToString());

		return $localobject;
	}

	/**
	 * testFichinterValid
	 *
	 * @param	Fichinter		$localobject		Intervention
	 * @return	int
	 *
	 * @depends	testFichinterFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testFichinterValid($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$result = $localobject->setValid($user);
		print __METHOD__." id=".$localobject->id." result=".$result."\n";

		$this->assertLessThan($result, 0, $localobject->errorsToString());
		return $localobject;
	}

	/**
	 * testFichinterValid
	 *
	 * @param	Fichinter	$localobject	Object intervention
	 * @return	int
	 *
	 * @depends testFichinterValid
	 * The depends says test is run only if previous is ok
	 */
	public function testFichinterOther($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		/*$result=$localobject->setstatus(0);
		print __METHOD__." id=".$localobject->id." result=".$result."\n";
		$this->assertLessThan($result, 0);
		*/

		$localobject->info($localobject->id);
		print __METHOD__." localobject->date_creation=".$localobject->date_creation."\n";
		$this->assertNotEquals($localobject->date_creation, '', $localobject->errorsToString());

		return $localobject->id;
	}

	/**
	 * testFichinterDelete
	 *
	 * @param	int		$id		Id of intervention
	 * @return	int
	 *
	 * @depends	testFichinterOther
	 * The depends says test is run only if previous is ok
	 */
	public function testFichinterDelete($id)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new Fichinter($db);
		$result = $localobject->fetch($id);
		$result = $localobject->delete($user);

		print __METHOD__." id=".$id." result=".$result."\n";
		$this->assertLessThan($result, 0, $localobject->errorsToString());
		return $result;
	}

	/**
	 * testFichinterSetValidSetCloseStatusGuards
	 *
	 * setValid() and setClose() must do nothing, and return 0, when the intervention
	 * does not have a status allowed for the action.
	 *
	 * @return	void
	 */
	public function testFichinterSetValidSetCloseStatusGuards()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$soc = new Societe($db);
		$soc->name = "FichinterTest Unittest status guards";
		$socid = $soc->create($user);
		$this->assertGreaterThan(0, $socid, $soc->errorsToString());

		$localobject = new Fichinter($db);
		$localobject->initAsSpecimen();
		$localobject->socid = $socid;
		$id = $localobject->create($user);
		$this->assertGreaterThan(0, $id, $localobject->errorsToString());

		$check = new Fichinter($db);

		// A draft cannot be closed
		$localobject->fetch($id);
		$result = $localobject->setClose($user);
		$this->assertEquals(0, $result, 'Closing a draft intervention must do nothing');
		$this->assertEquals(Fichinter::STATUS_DRAFT, $localobject->status, 'In memory status must stay draft');
		$check->fetch($id);
		$this->assertEquals(Fichinter::STATUS_DRAFT, $check->status, 'Status in database must stay draft');

		// Draft -> validated -> closed still works
		$result = $localobject->setValid($user);
		$this->assertGreaterThan(0, $result, $localobject->errorsToString());
		$result = $localobject->setClose($user);
		$this->assertGreaterThan(0, $result, $localobject->errorsToString());
		$check->fetch($id);
		$this->assertEquals(Fichinter::STATUS_CLOSED, $check->status, 'Intervention must be closed');
		$closedref = $check->ref;

		// A closed intervention cannot be validated
		$localobject->fetch($id);
		$result = $localobject->setValid($user);
		$this->assertEquals(0, $result, 'Validating a closed intervention must do nothing');
		$this->assertEquals(Fichinter::STATUS_CLOSED, $localobject->status, 'In memory status must stay closed');
		$this->assertEquals($closedref, $localobject->ref, 'In memory ref must not change');
		$check->fetch($id);
		$this->assertEquals(Fichinter::STATUS_CLOSED, $check->status, 'Status in database must stay closed');
		$this->assertEquals($closedref, $check->ref, 'Ref in database must not change');

		// Closing again does nothing
		$result = $localobject->setClose($user);
		$this->assertEquals(0, $result, 'Closing a closed intervention must do nothing');

		$localobject->delete($user);
	}

	/**
	 * testFichinterSetDraftStatusGuard
	 *
	 * setDraft() must only put back to draft a validated intervention, and must do nothing,
	 * and return 0, for any other status.
	 *
	 * @return	void
	 */
	public function testFichinterSetDraftStatusGuard()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$soc = new Societe($db);
		$soc->name = "FichinterTest Unittest set draft guard";
		$socid = $soc->create($user);
		$this->assertGreaterThan(0, $socid, $soc->errorsToString());

		$localobject = new Fichinter($db);
		$localobject->initAsSpecimen();
		$localobject->socid = $socid;
		$id = $localobject->create($user);
		$this->assertGreaterThan(0, $id, $localobject->errorsToString());

		$check = new Fichinter($db);

		// A draft is already draft
		$localobject->fetch($id);
		$result = $localobject->setDraft($user);
		$this->assertEquals(0, $result, 'Setting a draft intervention to draft must do nothing');
		$this->assertEquals(Fichinter::STATUS_DRAFT, $localobject->status, 'In memory status must stay draft');

		// Validated -> draft works
		$result = $localobject->setValid($user);
		$this->assertGreaterThan(0, $result, $localobject->errorsToString());
		$result = $localobject->setDraft($user);
		$this->assertGreaterThan(0, $result, $localobject->errorsToString());
		$this->assertEquals(Fichinter::STATUS_DRAFT, $localobject->status, 'In memory status must be draft');
		$check->fetch($id);
		$this->assertEquals(Fichinter::STATUS_DRAFT, $check->status, 'Status in database must be draft');

		// A closed intervention cannot be put back to draft
		$result = $localobject->setValid($user);
		$this->assertGreaterThan(0, $result, $localobject->errorsToString());
		$result = $localobject->setClose($user);
		$this->assertGreaterThan(0, $result, $localobject->errorsToString());
		$localobject->fetch($id);
		$this->assertEquals(Fichinter::STATUS_CLOSED, $localobject->status, 'Intervention must be closed');
		$result = $localobject->setDraft($user);
		$this->assertEquals(0, $result, 'Setting a closed intervention to draft must do nothing');
		$this->assertEquals(Fichinter::STATUS_CLOSED, $localobject->status, 'In memory status must stay closed');
		$check->fetch($id);
		$this->assertEquals(Fichinter::STATUS_CLOSED, $check->status, 'Status in database must stay closed');

		$localobject->delete($user);
	}

	/**
	 * testFichinterUpdateExtrafieldError
	 *
	 * update() must return an error when the extra fields can not be saved.
	 *
	 * @return	void
	 */
	public function testFichinterUpdateExtrafieldError()
	{
		global $conf,$user,$langs,$db,$extrafields;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';

		$soc = new Societe($db);
		$soc->name = "FichinterTest Unittest update extrafield error";
		$socid = $soc->create($user);
		$this->assertGreaterThan(0, $socid, $soc->errorsToString());

		$localobject = new Fichinter($db);
		$localobject->initAsSpecimen();
		$localobject->socid = $socid;
		$id = $localobject->create($user);
		$this->assertGreaterThan(0, $id, $localobject->errorsToString());
		$localobject->fetch($id);

		// Declare, in memory only, an integer extra field so insertExtraFields() refuses a value that is not a number
		$savextrafields = $extrafields;
		$extrafields = new ExtraFields($db);
		$extrafields->attributes[$localobject->table_element] = array(
			'loaded' => 1,
			'label' => array('unittestint' => 'Unit test int'),
			'type' => array('unittestint' => 'int'),
			'param' => array('unittestint' => ''),
			'required' => array('unittestint' => 0),
			'unique' => array('unittestint' => 0),
			'computed' => array('unittestint' => ''),
			'emptyonclone' => array('unittestint' => 0),
		);

		try {
			$localobject->array_options = array('options_unittestint' => 'not a number');
			$result = $localobject->update($user);
		} finally {
			$extrafields = $savextrafields;
		}

		$this->assertLessThan(0, $result, 'Update must fail when an extra field has a wrong value');
		$this->assertNotEmpty($localobject->errors, 'The extra field error must be returned');

		$localobject->array_options = array();
		$localobject->delete($user);
	}
}
