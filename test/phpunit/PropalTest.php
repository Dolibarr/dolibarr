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
 *      \file       test/phpunit/PropalTest.php
 *		\ingroup    test
 *      \brief      PHPUnit test
 *		\remarks	To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/comm/propal/class/propal.class.php';
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
class PropalTest extends CommonClassTest
{
	/**
	 * testPropalCreate
	 *
	 * @return	void
	 */
	public function testPropalCreate()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new Propal($db);
		$param = array('tosell' => 1);
		$localobject->initAsSpecimen($param);
		$result = $localobject->create($user);

		$this->assertLessThan($result, 0);
		print __METHOD__." result=".$result."\n";
		return $result;
	}

	/**
	 * testPropalFetch
	 *
	 * @param	int		$id		Id of object
	 * @return	Propal
	 *
	 * @depends	testPropalCreate
	 * The depends says test is run only if previous is ok
	 */
	public function testPropalFetch($id)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new Propal($db);
		$result = $localobject->fetch($id);

		$this->assertLessThan($result, 0);
		print __METHOD__." id=".$id." result=".$result."\n";

		// Specimen lines are built from real products picked at random (see Propal::initAsSpecimen), so the
		// exact line count is not stable (a kit/BOM product can expand into extra lines) - only check totals coherence.
		$this->assertNotEmpty($localobject->lines);
		$this->assertLineTotalsMatchHeader($localobject, 'after fetch');

		return $localobject;
	}

	/**
	 * testPropalUpdate
	 *
	 * @param	Propal		$localobject	Proposal
	 * @return	Propal
	 *
	 * @depends	testPropalFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testPropalUpdate($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject->note_private = 'New note private after update';
		$result = $localobject->update($user);

		$this->assertLessThan($result, 0);
		print __METHOD__." id=".$localobject->id." result=".$result."\n";
		return $localobject;
	}

	/**
	 * testPropalAddLine
	 *
	 * @param	Propal	$localobject	Proposal
	 * @return	array{0:Propal,1:int}	Proposal and id of the line added
	 *
	 * @depends	testPropalUpdate
	 * The depends says test is run only if previous is ok
	 */
	public function testPropalAddLine($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject->fetch_thirdparty();
		$beforelinecount = count($localobject->lines);
		$beforetotalht = (float) $localobject->total_ht;

		$lineid = $localobject->addline('PHPUnit addline test', 100, 2, 20);	// 2 x 100 HT at 20% VAT = 200 HT / 40 VAT / 240 TTC

		print __METHOD__." id=".$localobject->id." lineid=".$lineid."\n";
		$this->assertGreaterThan(0, $lineid, $localobject->errorsToString());

		$localobject->fetch($localobject->id);
		$this->assertCount($beforelinecount + 1, $localobject->lines);
		$this->assertEqualsWithDelta($beforetotalht + 200, (float) $localobject->total_ht, 0.01, 'total_ht not updated after addline');
		$this->assertLineTotalsMatchHeader($localobject, 'after addline');

		return array($localobject, $lineid);
	}

	/**
	 * testPropalUpdateLine
	 *
	 * @param	array{0:Propal,1:int}	$params	Proposal and id of the line to update
	 * @return	array{0:Propal,1:int}			Proposal and id of the line updated
	 *
	 * @depends	testPropalAddLine
	 * The depends says test is run only if previous is ok
	 */
	public function testPropalUpdateLine($params)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		list($localobject, $lineid) = $params;
		$beforelinecount = count($localobject->lines);
		$beforetotalht = (float) $localobject->total_ht;

		$result = $localobject->updateline($lineid, 100, 3, 0, 20, 0, 0, 'PHPUnit addline test');	// qty 2 -> 3, so +100 HT / +20 VAT / +120 TTC

		print __METHOD__." id=".$localobject->id." lineid=".$lineid." result=".$result."\n";
		$this->assertGreaterThan(0, $result, $localobject->errorsToString());

		$localobject->fetch($localobject->id);
		$this->assertCount($beforelinecount, $localobject->lines);
		$this->assertEqualsWithDelta($beforetotalht + 100, (float) $localobject->total_ht, 0.01, 'total_ht not updated after updateline');
		$this->assertLineTotalsMatchHeader($localobject, 'after updateline');

		return array($localobject, $lineid);
	}

	/**
	 * testPropalDeleteLine
	 *
	 * @param	array{0:Propal,1:int}	$params	Proposal and id of the line to delete
	 * @return	Propal
	 *
	 * @depends	testPropalUpdateLine
	 * The depends says test is run only if previous is ok
	 */
	public function testPropalDeleteLine($params)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		list($localobject, $lineid) = $params;
		$beforelinecount = count($localobject->lines);
		$beforetotalht = (float) $localobject->total_ht;

		$result = $localobject->deleteLine($lineid);

		print __METHOD__." id=".$localobject->id." lineid=".$lineid." result=".$result."\n";
		$this->assertGreaterThan(0, $result, $localobject->errorsToString());

		$localobject->fetch($localobject->id);
		// Back to the original specimen lines, with the same totals
		$this->assertCount($beforelinecount - 1, $localobject->lines);
		$this->assertEqualsWithDelta($beforetotalht - 300, (float) $localobject->total_ht, 0.01, 'total_ht not updated after deleteLine');
		$this->assertLineTotalsMatchHeader($localobject, 'after deleteLine');

		return $localobject;
	}

	/**
	 * testPropalValid
	 *
	 * @param	Propal	$localobject	Proposal
	 * @return	Propal
	 *
	 * @depends	testPropalDeleteLine
	 * The depends says test is run only if previous is ok
	 */
	public function testPropalValid($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$result = $localobject->valid($user);

		print __METHOD__." id=".$localobject->id." result=".$result."\n";
		$this->assertLessThan($result, 0);

		// Test everything is still the same as a freshly built specimen with the same mutation applied
		// (catches unwanted field changes introduced by update()/valid())
		$this->assertMatchesFreshSpecimen(
			$localobject,
			function ($specimen) {
				$specimen->note_private = 'New note private after update';
			},
			array(
				'newref', 'oldcopy', 'oldref', 'id', 'entity', 'lines', 'line', 'client', 'thirdparty', 'brouillon', 'specimen',
				'fk_user_author', 'user_author_id', 'user_creation_id', 'user_modification_id', 'user_validation_id',
				'date', 'datec', 'datev', 'datep', 'date_creation', 'date_validation', 'date_lim_reglement', 'fin_validite', 'datem', 'date_modification',
				'ref', 'statut', 'status', 'socid', 'billed', 'fk_incoterms', 'actiontypecode', 'actionmsg2', 'actionmsg',
				'mode_reglement', 'cond_reglement', 'mode_reglement_code', 'cond_reglement_code', 'availability', 'availability_code', 'demand_reason', 'demand_reason_code',
				'cond_reglement_doc', 'modelpdf', 'total',
				// Totals are ignored here: specimen lines reference random real products, and a kit/BOM product can
				// expand into extra lines with a different amount - total correctness is checked by assertLineTotalsMatchHeader() instead.
				'total_ht', 'total_tva', 'total_ttc', 'total_localtax1', 'total_localtax2',
				'multicurrency_total_ht', 'multicurrency_total_tva', 'multicurrency_total_ttc', 'fk_multicurrency', 'multicurrency_code', 'multicurrency_tx',
				'trackid', 'user_creat', 'user_valid', 'note',
			)
		);

		return $localobject;
	}

	/**
	 * testPropalOther
	 *
	 * @param	Propal	$localobject	Proposal
	 * @return	int
	 *
	 * @depends testPropalValid
	 * The depends says test is run only if previous is ok
	 */
	public function testPropalOther($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject->info($localobject->id);
		print __METHOD__." localobject->date_creation=".$localobject->date_creation."\n";
		$this->assertNotEquals($localobject->date_creation, '');

		return $localobject->id;
	}

	/**
	 * testPropalDelete
	 *
	 * @param	int		$id		Id of proposal
	 * @return	void
	 *
	 * @depends	testPropalOther
	 * The depends says test is run only if previous is ok
	 */
	public function testPropalDelete($id)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new Propal($db);
		$result = $localobject->fetch($id);
		$result = $localobject->delete($user);

		print __METHOD__." id=".$id." result=".$result."\n";
		$this->assertLessThan($result, 0);
		return $result;
	}
}
