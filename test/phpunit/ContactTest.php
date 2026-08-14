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
 *      \file       test/phpunit/ContactTest.php
 *		\ingroup    test
 *      \brief      PHPUnit test
 *		\remarks	To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/contact/class/contact.class.php';
require_once dirname(__FILE__).'/../../htdocs/societe/class/societe.class.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';

$langs->load("dict");

if ($langs->defaultlang != 'en_US') {
	print "Error: Default language for company to run tests must be set to en_US or auto. Current is ".$langs->defaultlang."\n";
	exit(1);
}

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
class ContactTest extends CommonClassTest
{
	/**
	 * testContactCreate
	 *
	 * @return	int
	 */
	public function testContactCreate()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new Contact($db);
		$localobject->initAsSpecimen();
		$result = $localobject->create($user);

		print __METHOD__." result=".$result."\n";
		$this->assertLessThan($result, 0);

		return $result;
	}

	/**
	 * testContactFetch
	 *
	 * @param	int		$id		Id of contact
	 * @return	int
	 * @depends	testContactCreate
	 * The depends says test is run only if previous is ok
	 */
	public function testContactFetch($id)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new Contact($db);
		$result = $localobject->fetch($id);

		print __METHOD__." id=".$id." result=".$result."\n";
		$this->assertLessThan($result, 0);

		return $localobject;
	}

	/**
	 * testContactUpdate
	 *
	 * @param	Contact		$localobject	Contact
	 * @return	int
	 *
	 * @depends	testContactFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testContactUpdate($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject->oldcopy = clone $localobject;

		$localobject->note_private = 'New private note after update';
		$localobject->note_public = 'New public note after update';
		$localobject->lastname = 'New name';
		$localobject->firstname = 'New firstname';
		$localobject->address = 'New address';
		$localobject->zip = 'New zip';
		$localobject->town = 'New town';
		$localobject->country_id = 2;
		//$localobject->status=0;
		$localobject->phone_pro = 'New tel pro';
		$localobject->phone_perso = 'New tel perso';
		$localobject->phone_mobile = 'New tel mobile';
		$localobject->fax = 'New fax';
		$localobject->email = 'newemail@newemail.com';
		$localobject->socialnetworks['jabber'] = 'New im id';
		$localobject->default_lang = 'es_ES';

		$result = $localobject->update($localobject->id, $user);
		print __METHOD__." id=".$localobject->id." result=".$result."\n";
		$this->assertLessThan($result, 0, 'Contact::update error');

		$result = $localobject->update_note($localobject->note_private, '_private');
		print __METHOD__." id=".$localobject->id." result=".$result."\n";
		$this->assertLessThan($result, 0, 'Contact::update_note (private) error');

		$result = $localobject->update_note($localobject->note_public, '_public');
		print __METHOD__." id=".$localobject->id." result=".$result."\n";
		$this->assertLessThan($result, 0, 'Contact::update_note (public) error');

		$newobject = new Contact($db);
		$result = $newobject->fetch($localobject->id);
		print __METHOD__." id=".$localobject->id." result=".$result."\n";
		$this->assertLessThan($result, 0, 'Contact::fetch error');

		print __METHOD__." old=".$localobject->note_private." new=".$newobject->note_private."\n";
		$this->assertEquals($localobject->note_private, $newobject->note_private);
		//print __METHOD__." old=".$localobject->note_public." new=".$newobject->note_public."\n";
		//$this->assertEquals($localobject->note_public, $newobject->note_public);
		print __METHOD__." old=".$localobject->lastname." new=".$newobject->lastname."\n";
		$this->assertEquals($localobject->lastname, $newobject->lastname);
		print __METHOD__." old=".$localobject->firstname." new=".$newobject->firstname."\n";
		$this->assertEquals($localobject->firstname, $newobject->firstname);
		print __METHOD__." old=".$localobject->address." new=".$newobject->address."\n";
		$this->assertEquals($localobject->address, $newobject->address);
		print __METHOD__." old=".$localobject->zip." new=".$newobject->zip."\n";
		$this->assertEquals($localobject->zip, $newobject->zip);
		print __METHOD__." old=".$localobject->town." new=".$newobject->town."\n";
		$this->assertEquals($localobject->town, $newobject->town);
		print __METHOD__." old=".$localobject->country_id." new=".$newobject->country_id."\n";
		$this->assertEquals($localobject->country_id, $newobject->country_id);
		print __METHOD__." old=BE new=".$newobject->country_code."\n";
		$this->assertEquals('BE', $newobject->country_code);
		//print __METHOD__." old=".$localobject->status." new=".$newobject->status."\n";
		//$this->assertEquals($localobject->status, $newobject->status);
		print __METHOD__." old=".$localobject->phone_pro." new=".$newobject->phone_pro."\n";
		$this->assertEquals($localobject->phone_pro, $newobject->phone_pro);
		print __METHOD__." old=".$localobject->phone_pro." new=".$newobject->phone_pro."\n";
		$this->assertEquals($localobject->phone_perso, $newobject->phone_perso);
		print __METHOD__." old=".$localobject->phone_mobile." new=".$newobject->phone_mobile."\n";
		$this->assertEquals($localobject->phone_mobile, $newobject->phone_mobile);
		print __METHOD__." old=".$localobject->fax." new=".$newobject->fax."\n";
		$this->assertEquals($localobject->fax, $newobject->fax);
		print __METHOD__." old=".$localobject->email." new=".$newobject->email."\n";
		$this->assertEquals($localobject->email, $newobject->email);
		print __METHOD__." old=".$localobject->socialnetworks['jabber']." new=".$newobject->socialnetworks['jabber']."\n";
		$this->assertEquals($localobject->socialnetworks['jabber'], $newobject->socialnetworks['jabber']);
		print __METHOD__." old=".$localobject->default_lang." new=".$newobject->default_lang."\n";
		$this->assertEquals($localobject->default_lang, $newobject->default_lang);

		return $localobject;
	}

	/**
	 * testContactOther
	 *
	 * @param	Contact		$localobject		Contact
	 * @return	void
	 *
	 * @depends	testContactUpdate
	 * The depends says test is run only if previous is ok
	 */
	public function testContactOther($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		//$localobject->fetch($localobject->id);

		$result = $localobject->getNomUrl(1);
		print __METHOD__." id=".$localobject->id." result=".$result."\n";
		$this->assertNotEquals($result, '');

		$result = $localobject->getFullAddress(1);
		print __METHOD__." id=".$localobject->id." result=".$result."\n";
		$this->assertStringContainsString("New address\nNew zip New town\nBelgium", $result);

		$localobject->info($localobject->id);
		print __METHOD__." localobject->date_creation=".$localobject->date_creation."\n";
		$this->assertNotEquals($localobject->date_creation, '');

		return $localobject->id;
	}

	/**
	 * testContactDelete
	 *
	 * @param	int		$id		Id of contact
	 * @return	void
	 *
	 * @depends	testContactOther
	 * The depends says test is run only if previous is ok
	 */
	public function testContactDelete($id)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new Contact($db);
		$result = $localobject->fetch($id);

		$result = $localobject->delete($user);
		print __METHOD__." id=".$id." result=".$result."\n";
		$this->assertLessThan($result, 0);

		return $result;
	}

	/**
	 * testContactGetFullAddress
	 *
	 * @return	int		$id				Id of company
	 */
	public function testContactGetFullAddress()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobjectadd = new Contact($db);
		$localobjectadd->initAsSpecimen();

		// France
		unset($localobjectadd->country_code);
		$localobjectadd->country_id = 1;
		$localobjectadd->name = 'New name';
		$localobjectadd->address = 'New address';
		$localobjectadd->zip = 'New zip';
		$localobjectadd->town = 'New town';
		$result = $localobjectadd->getFullAddress(1);
		print __METHOD__." id=".$localobjectadd->id." result=".$result."\n";
		$this->assertStringContainsString("New address\nNew zip New town\nFrance", $result);

		// Belgium
		unset($localobjectadd->country_code);
		$localobjectadd->country_id = 2;
		$localobjectadd->name = 'New name';
		$localobjectadd->address = 'New address';
		$localobjectadd->zip = 'New zip';
		$localobjectadd->town = 'New town';
		$result = $localobjectadd->getFullAddress(1);
		print __METHOD__." id=".$localobjectadd->id." result=".$result."\n";
		$this->assertStringContainsString("New address\nNew zip New town\nBelgium", $result);

		// Switzerland
		unset($localobjectadd->country_code);
		$localobjectadd->country_id = 6;
		$localobjectadd->name = 'New name';
		$localobjectadd->address = 'New address';
		$localobjectadd->zip = 'New zip';
		$localobjectadd->town = 'New town';
		$result = $localobjectadd->getFullAddress(1);
		print __METHOD__." id=".$localobjectadd->id." result=".$result."\n";
		$this->assertStringContainsString("New address\nNew zip New town\nSwitzerland", $result);

		// USA
		unset($localobjectadd->country_code);
		$localobjectadd->country_id = 11;
		$localobjectadd->name = 'New name';
		$localobjectadd->address = 'New address';
		$localobjectadd->zip = 'New zip';
		$localobjectadd->town = 'New town';
		$result = $localobjectadd->getFullAddress(1);
		print __METHOD__." id=".$localobjectadd->id." result=".$result."\n";
		$this->assertStringContainsString("New address\nNew town, New zip\nUnited States", $result);

		return $localobjectadd->id;
	}

	/**
	 * Create a contact to be used as a fixture by the merge tests.
	 *
	 * @param	string				$lastname	Last name of the contact
	 * @param	array<string,mixed>	$moreprops	Additional properties to set before the creation
	 * @return	Contact							Created contact
	 */
	private function createContactForMerge($lastname, $moreprops = array())
	{
		global $user, $db;

		$contact = new Contact($db);
		$contact->lastname = $lastname;
		$contact->firstname = 'Phpunit';
		foreach ($moreprops as $key => $val) {
			$contact->$key = $val;
		}
		// The triggers are disabled on the creation of the fixtures only, to keep it independent from
		// the modules installed on the instance running the tests. mergeContact() itself does fire
		// CONTACT_MODIFY, as mergeCompany() fires COMPANY_MODIFY.
		// No cleanup is needed: CommonClassTest opens a transaction that is rolled back after the
		// class, and DoliDB::rollback() is nesting aware, so the commit of mergeContact() is included.
		$id = $contact->create($user, 1);
		$this->assertGreaterThan(0, $id, 'Failed to create the fixture contact: '.$contact->errorsToString());

		return $contact;
	}

	/**
	 * testContactMerge
	 *
	 * Check that two contacts can be merged: the empty fields of the target contact are filled from
	 * the merged one, the notes are concatenated and the merged contact is deleted.
	 *
	 * @return	void
	 */
	public function testContactMerge()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$dest = $this->createContactForMerge('MergeDest');
		$origin = $this->createContactForMerge('MergeOrigin', array(
			'email' => 'merge.origin@example.com',
			'phone_pro' => '0102030405',
			'note_public' => 'Note from the merged contact'
		));

		$result = $dest->mergeContact($origin->id);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals(0, $result, 'mergeContact failed: '.$dest->error);

		$check = new Contact($db);
		$check->fetch($dest->id);
		$this->assertEquals('merge.origin@example.com', $check->email, 'The empty email must be filled from the merged contact');
		$this->assertEquals('0102030405', $check->phone_pro, 'The empty phone must be filled from the merged contact');
		$this->assertStringContainsString('Note from the merged contact', (string) $check->note_public);

		$deleted = new Contact($db);
		$this->assertEquals(0, $deleted->fetch($origin->id), 'The merged contact must have been deleted');
	}

	/**
	 * testContactMergeKeepsInternalElementContact
	 *
	 * llx_element_contact.fk_socpeople references llx_socpeople when c_type_contact.source is
	 * 'external' but llx_user when it is 'internal'. Check that merging contacts never touches the
	 * internal (user) assignments, which would silently corrupt them.
	 *
	 * @return	void
	 */
	public function testContactMergeKeepsInternalElementContact()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$dest = $this->createContactForMerge('MergeDestInternal');
		$origin = $this->createContactForMerge('MergeOriginInternal');

		// An internal type of contact, ie one referencing llx_user and not llx_socpeople
		$sql = "SELECT rowid FROM ".$db->prefix()."c_type_contact WHERE source = 'internal' AND active = 1";
		$resql = $db->query($sql);
		$this->assertNotFalse($resql, 'Cannot read the types of contact');
		$objtype = $db->fetch_object($resql);
		$db->free($resql);
		$this->assertIsObject($objtype, 'No active internal type of contact found');

		// Simulate a user assigned to an element, storing a user id into fk_socpeople.
		// The id of the merged contact is used on purpose: it is the value a buggy UPDATE would move.
		$sql = "INSERT INTO ".$db->prefix()."element_contact(datecreate, statut, element_id, fk_c_type_contact, fk_socpeople)";
		$sql .= " VALUES ('".$db->idate(dol_now())."', 4, 999999, ".((int) $objtype->rowid).", ".((int) $origin->id).")";
		$this->assertNotFalse($db->query($sql), 'Cannot create the internal link fixture');

		$result = $dest->mergeContact($origin->id);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals(0, $result, 'mergeContact failed: '.$dest->error);

		$sql = "SELECT fk_socpeople FROM ".$db->prefix()."element_contact WHERE element_id = 999999";
		$sql .= " AND fk_c_type_contact = ".((int) $objtype->rowid);
		$resql = $db->query($sql);
		$obj = $db->fetch_object($resql);
		$db->free($resql);
		$this->assertIsObject($obj, 'The internal link must still exist');
		$this->assertEquals($origin->id, $obj->fk_socpeople, 'An internal link must NOT be moved by a contact merge');
	}

	/**
	 * testContactMergeDoesNotWipeRoles
	 *
	 * updateRoles(), called by update(), deletes then reinserts every societe_contacts row of the
	 * contact from $this->roles. Check that merging does not lose the roles of the target contact.
	 *
	 * @return	void
	 */
	public function testContactMergeDoesNotWipeRoles()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$company = new Societe($db);
		$company->name = 'PhpunitMergeRoles';
		$socid = $company->create($user, 1);
		$this->assertGreaterThan(0, $socid, 'Failed to create the fixture third party: '.$company->errorsToString());

		$dest = $this->createContactForMerge('MergeDestRoles', array('socid' => $socid));
		$origin = $this->createContactForMerge('MergeOriginRoles', array('socid' => $socid));

		$sql = "SELECT rowid FROM ".$db->prefix()."c_type_contact WHERE source = 'external' AND active = 1";
		$resql = $db->query($sql);
		$objtype = $db->fetch_object($resql);
		$db->free($resql);
		$this->assertIsObject($objtype, 'No active external type of contact found');

		$sql = "INSERT INTO ".$db->prefix()."societe_contacts(entity, date_creation, fk_soc, fk_c_type_contact, fk_socpeople)";
		$sql .= " VALUES (".((int) $conf->entity).", '".$db->idate(dol_now())."', ".((int) $socid).", ".((int) $objtype->rowid).", ".((int) $dest->id).")";
		$this->assertNotFalse($db->query($sql), 'Cannot create the role fixture');

		$result = $dest->mergeContact($origin->id);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals(0, $result, 'mergeContact failed: '.$dest->error);

		$sql = "SELECT COUNT(rowid) as nb FROM ".$db->prefix()."societe_contacts WHERE fk_socpeople = ".((int) $dest->id);
		$resql = $db->query($sql);
		$obj = $db->fetch_object($resql);
		$db->free($resql);
		$this->assertEquals(1, $obj->nb, 'The role of the target contact must be kept by the merge');
	}

	/**
	 * testContactMergeDeduplicatesElementContact
	 *
	 * llx_element_contact has a unique key on (element_id, fk_c_type_contact, fk_socpeople). Check
	 * that merging two contacts sharing the same role on the same object does not fail.
	 *
	 * @return	void
	 */
	public function testContactMergeDeduplicatesElementContact()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$dest = $this->createContactForMerge('MergeDestDedup');
		$origin = $this->createContactForMerge('MergeOriginDedup');

		$sql = "SELECT rowid FROM ".$db->prefix()."c_type_contact WHERE source = 'external' AND active = 1";
		$resql = $db->query($sql);
		$objtype = $db->fetch_object($resql);
		$db->free($resql);
		$this->assertIsObject($objtype, 'No active external type of contact found');

		foreach (array($dest->id, $origin->id) as $contactid) {
			$sql = "INSERT INTO ".$db->prefix()."element_contact(datecreate, statut, element_id, fk_c_type_contact, fk_socpeople)";
			$sql .= " VALUES ('".$db->idate(dol_now())."', 4, 999998, ".((int) $objtype->rowid).", ".((int) $contactid).")";
			$this->assertNotFalse($db->query($sql), 'Cannot create the duplicated link fixture');
		}

		$result = $dest->mergeContact($origin->id);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals(0, $result, 'mergeContact must succeed despite the duplicated link: '.$dest->error);

		$sql = "SELECT COUNT(rowid) as nb FROM ".$db->prefix()."element_contact WHERE element_id = 999998";
		$resql = $db->query($sql);
		$obj = $db->fetch_object($resql);
		$db->free($resql);
		$this->assertEquals(1, $obj->nb, 'Only one link must remain after the merge');
	}

	/**
	 * testContactMergeMovesActioncommResources
	 *
	 * The contacts assigned to an event are stored into llx_actioncomm_resources with an element_type
	 * of 'socpeople', llx_actioncomm.fk_contact being deprecated.
	 *
	 * @return	void
	 */
	public function testContactMergeMovesActioncommResources()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$dest = $this->createContactForMerge('MergeDestEvent');
		$origin = $this->createContactForMerge('MergeOriginEvent');

		$sql = "INSERT INTO ".$db->prefix()."actioncomm_resources(fk_actioncomm, element_type, fk_element, mandatory, answer_status, transparency)";
		$sql .= " VALUES (999997, 'socpeople', ".((int) $origin->id).", 0, 0, 0)";
		$this->assertNotFalse($db->query($sql), 'Cannot create the event resource fixture');

		$result = $dest->mergeContact($origin->id);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals(0, $result, 'mergeContact failed: '.$dest->error);

		$sql = "SELECT fk_element FROM ".$db->prefix()."actioncomm_resources WHERE fk_actioncomm = 999997";
		$sql .= " AND element_type = 'socpeople'";
		$resql = $db->query($sql);
		$obj = $db->fetch_object($resql);
		$db->free($resql);
		$this->assertIsObject($obj, 'The event assignment must still exist');
		$this->assertEquals($dest->id, $obj->fk_element, 'The event assignment must be moved to the target contact');
	}

	/**
	 * testContactMergeRemapsChildren
	 *
	 * llx_socpeople.fk_parent has neither a foreign key nor an index. Check that the children of the
	 * merged contact are moved, and that no dangling pointer is left when the target contact is a
	 * child of the merged one.
	 *
	 * @return	void
	 */
	public function testContactMergeRemapsChildren()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$dest = $this->createContactForMerge('MergeDestParent');
		$origin = $this->createContactForMerge('MergeOriginParent');
		$child = $this->createContactForMerge('MergeChild');

		// The child belongs to the merged contact, and the target contact is itself a child of it
		foreach (array($child->id, $dest->id) as $contactid) {
			$sql = "UPDATE ".$db->prefix()."socpeople SET fk_parent = ".((int) $origin->id)." WHERE rowid = ".((int) $contactid);
			$this->assertNotFalse($db->query($sql), 'Cannot create the hierarchy fixture');
		}

		$result = $dest->mergeContact($origin->id);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals(0, $result, 'mergeContact failed: '.$dest->error);

		$sql = "SELECT rowid, fk_parent FROM ".$db->prefix()."socpeople WHERE rowid IN (".((int) $child->id).", ".((int) $dest->id).")";
		$resql = $db->query($sql);
		$this->assertEquals(2, $db->num_rows($resql), 'Both the child and the target contact must still exist');
		while ($obj = $db->fetch_object($resql)) {
			if ($obj->rowid == $child->id) {
				$this->assertEquals($dest->id, $obj->fk_parent, 'The child must be moved to the target contact');
			} else {
				$this->assertNotEquals($origin->id, $obj->fk_parent, 'The target contact must not point to the deleted contact');
				$this->assertNotEquals($dest->id, $obj->fk_parent, 'The target contact must not be its own parent');
			}
		}
		$db->free($resql);
	}

	/**
	 * testContactMergeUnionOfCategories
	 *
	 * @return	void
	 */
	public function testContactMergeUnionOfCategories()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		require_once dirname(__FILE__).'/../../htdocs/categories/class/categorie.class.php';

		$dest = $this->createContactForMerge('MergeDestCateg');
		$origin = $this->createContactForMerge('MergeOriginCateg');

		$catids = array();
		foreach (array('PhpunitMergeCatA', 'PhpunitMergeCatB') as $label) {
			$categ = new Categorie($db);
			$categ->label = $label;
			$categ->type = Categorie::TYPE_CONTACT;
			$catid = $categ->create($user);
			$this->assertGreaterThan(0, $catid, 'Failed to create the fixture category: '.$categ->errorsToString());
			$catids[] = $catid;
		}

		$dest->setCategories(array($catids[0]));
		$origin->setCategories(array($catids[1]));

		$result = $dest->mergeContact($origin->id);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals(0, $result, 'mergeContact failed: '.$dest->error);

		$sql = "SELECT COUNT(fk_categorie) as nb FROM ".$db->prefix()."categorie_contact WHERE fk_socpeople = ".((int) $dest->id);
		$resql = $db->query($sql);
		$obj = $db->fetch_object($resql);
		$db->free($resql);
		$this->assertEquals(2, $obj->nb, 'The target contact must hold the union of both categories');
	}

	/**
	 * testContactMergeRejectsInvalidInput
	 *
	 * fetch() returns the id when found, 2 when several records were found, 0 when not found and -1
	 * on error, so the return value must be compared to the requested id.
	 *
	 * @return	void
	 */
	public function testContactMergeRejectsInvalidInput()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$dest = $this->createContactForMerge('MergeDestInvalid');

		$this->assertEquals(-1, $dest->mergeContact(0), 'Merging an empty id must be refused');
		$this->assertEquals(-1, $dest->mergeContact($dest->id), 'Merging a contact into itself must be refused');
		$this->assertEquals(-1, $dest->mergeContact(999996), 'Merging an unknown contact must be refused');

		$check = new Contact($db);
		$this->assertEquals($dest->id, $check->fetch($dest->id), 'The target contact must be untouched');
	}

	/**
	 * testContactMergeRefusesUnloadedTarget
	 *
	 * A contact that was not loaded has an id of 0, and update() would silently update no row while
	 * the satellite data would be moved to the contact id 0. This must be refused.
	 *
	 * @return	void
	 */
	public function testContactMergeRefusesUnloadedTarget()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$origin = $this->createContactForMerge('MergeOriginUnloaded');

		$notloaded = new Contact($db);
		$this->assertEquals(-1, $notloaded->mergeContact($origin->id), 'Merging into an unloaded contact must be refused');

		$check = new Contact($db);
		$this->assertEquals($origin->id, $check->fetch($origin->id), 'The contact to merge must still exist');
	}

	/**
	 * testContactMergeDeduplicatesPolymorphicRefs
	 *
	 * llx_links has a unique index on (objectid, objecttype, label), so moving the links of the merged
	 * contact must not violate it when both contacts share a link of the same label.
	 *
	 * @return	void
	 */
	public function testContactMergeDeduplicatesPolymorphicRefs()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$dest = $this->createContactForMerge('MergeDestLink');
		$origin = $this->createContactForMerge('MergeOriginLink');

		foreach (array($dest->id, $origin->id) as $contactid) {
			$sql = "INSERT INTO ".$db->prefix()."links(entity, datea, url, label, objecttype, objectid)";
			$sql .= " VALUES (".((int) $conf->entity).", '".$db->idate(dol_now())."', 'https://example.com',";
			$sql .= " 'PhpunitSameLabel', 'contact', ".((int) $contactid).")";
			$this->assertNotFalse($db->query($sql), 'Cannot create the link fixture');
		}

		$result = $dest->mergeContact($origin->id);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals(0, $result, 'mergeContact must succeed despite the duplicated link: '.$dest->error);

		$sql = "SELECT COUNT(rowid) as nb FROM ".$db->prefix()."links WHERE objecttype = 'contact'";
		$sql .= " AND objectid = ".((int) $dest->id)." AND label = 'PhpunitSameLabel'";
		$resql = $db->query($sql);
		$obj = $db->fetch_object($resql);
		$db->free($resql);
		$this->assertEquals(1, $obj->nb, 'Only one link must remain after the merge');
	}

	/**
	 * testContactMergeMovesPolymorphicSocpeopleRefs
	 *
	 * llx_ecm_files.src_object_type holds either the element name of the contact ('contact') or its
	 * table name ('socpeople') depending on the writer, so both flavours must be moved. A list of
	 * values must not be given to DoliDB::sanitize() as a whole: it removes the quotes it contains and
	 * would collapse the list into a single value matching nothing.
	 *
	 * @return	void
	 */
	public function testContactMergeMovesPolymorphicSocpeopleRefs()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$dest = $this->createContactForMerge('MergeDestEcm');
		$origin = $this->createContactForMerge('MergeOriginEcm');

		foreach (array('contact', 'socpeople') as $i => $objecttype) {
			$sql = "INSERT INTO ".$db->prefix()."ecm_files(entity, ref, label, filename, filepath, src_object_type, src_object_id, date_c)";
			$sql .= " VALUES (".((int) $conf->entity).", 'phpunitmerge".((int) $i).((int) $origin->id)."', 'phpunitmergelabel',";
			$sql .= " 'phpunitmerge".((int) $i).".txt', 'contact/".((int) $origin->id)."', '".$db->escape($objecttype)."',";
			$sql .= " ".((int) $origin->id).", '".$db->idate(dol_now())."')";
			$this->assertNotFalse($db->query($sql), 'Cannot create the indexed file fixture');
		}

		$result = $dest->mergeContact($origin->id);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals(0, $result, 'mergeContact failed: '.$dest->error);

		$sql = "SELECT COUNT(rowid) as nb FROM ".$db->prefix()."ecm_files WHERE label = 'phpunitmergelabel'";
		$sql .= " AND src_object_id = ".((int) $dest->id);
		$resql = $db->query($sql);
		$obj = $db->fetch_object($resql);
		$db->free($resql);
		$this->assertEquals(2, $obj->nb, 'Both flavours of src_object_type must be moved to the target contact');
	}

	/**
	 * testContactMergeMovesUserLink
	 *
	 * llx_user.fk_socpeople and llx_user_alert.fk_contact are moved by User::replaceContact().
	 *
	 * @return	void
	 */
	public function testContactMergeMovesUserLink()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$dest = $this->createContactForMerge('MergeDestUser');
		$origin = $this->createContactForMerge('MergeOriginUser');

		// The contact of a user account is on the merged contact only, the target one is free
		$sql = "UPDATE ".$db->prefix()."user SET fk_socpeople = ".((int) $origin->id)." WHERE rowid = ".((int) $user->id);
		$this->assertNotFalse($db->query($sql), 'Cannot create the user link fixture');
		$sql = "INSERT INTO ".$db->prefix()."user_alert(type, fk_user, fk_contact) VALUES (1, ".((int) $user->id).", ".((int) $origin->id).")";
		$this->assertNotFalse($db->query($sql), 'Cannot create the user alert fixture');

		$result = $dest->mergeContact($origin->id);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals(0, $result, 'mergeContact failed: '.$dest->error);

		$sql = "SELECT fk_socpeople FROM ".$db->prefix()."user WHERE rowid = ".((int) $user->id);
		$resql = $db->query($sql);
		$obj = $db->fetch_object($resql);
		$db->free($resql);
		$this->assertIsObject($obj, 'The user must still exist');
		$this->assertEquals($dest->id, $obj->fk_socpeople, 'The contact of the user account must be moved to the target contact');

		$sql = "SELECT COUNT(rowid) as nb FROM ".$db->prefix()."user_alert WHERE fk_contact = ".((int) $dest->id);
		$resql = $db->query($sql);
		$obj = $db->fetch_object($resql);
		$db->free($resql);
		$this->assertEquals(1, $obj->nb, 'The alert of the user must be moved to the target contact');
	}

	/**
	 * testContactMergeDeduplicatesSocieteContacts
	 *
	 * llx_societe_contacts has a unique key on (entity, fk_soc, fk_c_type_contact, fk_socpeople), so
	 * two contacts holding the same role on the same third party must not make the merge fail.
	 *
	 * @return	void
	 */
	public function testContactMergeDeduplicatesSocieteContacts()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$company = new Societe($db);
		$company->name = 'PhpunitMergeSameRole';
		$socid = $company->create($user, 1);
		$this->assertGreaterThan(0, $socid, 'Failed to create the fixture third party: '.$company->errorsToString());

		$dest = $this->createContactForMerge('MergeDestSameRole', array('socid' => $socid));
		$origin = $this->createContactForMerge('MergeOriginSameRole', array('socid' => $socid));

		$sql = "SELECT rowid FROM ".$db->prefix()."c_type_contact WHERE source = 'external' AND active = 1";
		$resql = $db->query($sql);
		$objtype = $db->fetch_object($resql);
		$db->free($resql);
		$this->assertIsObject($objtype, 'No active external type of contact found');

		foreach (array($dest->id, $origin->id) as $contactid) {
			$sql = "INSERT INTO ".$db->prefix()."societe_contacts(entity, date_creation, fk_soc, fk_c_type_contact, fk_socpeople)";
			$sql .= " VALUES (".((int) $conf->entity).", '".$db->idate(dol_now())."', ".((int) $socid).", ".((int) $objtype->rowid).", ".((int) $contactid).")";
			$this->assertNotFalse($db->query($sql), 'Cannot create the shared role fixture');
		}

		$result = $dest->mergeContact($origin->id);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals(0, $result, 'mergeContact must succeed despite the shared role: '.$dest->error);

		$sql = "SELECT COUNT(rowid) as nb FROM ".$db->prefix()."societe_contacts WHERE fk_soc = ".((int) $socid);
		$sql .= " AND fk_c_type_contact = ".((int) $objtype->rowid);
		$resql = $db->query($sql);
		$obj = $db->fetch_object($resql);
		$db->free($resql);
		$this->assertEquals(1, $obj->nb, 'Only one role must remain after the merge');
	}

	/**
	 * testContactMergeRemovesSelfLink
	 *
	 * If the two contacts were linked to each other, moving both ends of the link in llx_element_element
	 * leaves a link of the target contact to itself, which no unique key forbids.
	 *
	 * @return	void
	 */
	public function testContactMergeRemovesSelfLink()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$dest = $this->createContactForMerge('MergeDestSelfLink');
		$origin = $this->createContactForMerge('MergeOriginSelfLink');

		$sql = "INSERT INTO ".$db->prefix()."element_element(fk_source, sourcetype, fk_target, targettype)";
		$sql .= " VALUES (".((int) $origin->id).", 'contact', ".((int) $dest->id).", 'contact')";
		$this->assertNotFalse($db->query($sql), 'Cannot create the link fixture');

		$result = $dest->mergeContact($origin->id);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals(0, $result, 'mergeContact failed: '.$dest->error);

		$sql = "SELECT COUNT(rowid) as nb FROM ".$db->prefix()."element_element WHERE sourcetype = 'contact'";
		$sql .= " AND targettype = 'contact' AND fk_source = ".((int) $dest->id)." AND fk_target = ".((int) $dest->id);
		$resql = $db->query($sql);
		$obj = $db->fetch_object($resql);
		$db->free($resql);
		$this->assertEquals(0, $obj->nb, 'The target contact must not be linked to itself');
	}

	/**
	 * testContactMergeRefusesToHideDataIntoPrivate
	 *
	 * A private contact is visible to its creator only, administrators included, so absorbing a shared
	 * contact into a private one would hide its data from everybody and cannot be undone.
	 *
	 * @return	void
	 */
	public function testContactMergeRefusesToHideDataIntoPrivate()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$dest = $this->createContactForMerge('MergeDestPrivate', array('priv' => 1));
		$origin = $this->createContactForMerge('MergeOriginShared');

		$this->assertEquals(-1, $dest->mergeContact($origin->id), 'Merging a shared contact into a private one must be refused');

		$check = new Contact($db);
		$this->assertEquals($origin->id, $check->fetch($origin->id), 'The shared contact must still exist');
	}

	/**
	 * testContactMergeMovesFiles
	 *
	 * The documents are moved once the transaction is committed, because dol_move() is not
	 * transactional. Check that the tree is preserved, that a name collision renames the moved file
	 * instead of overwriting the one of the target contact, and that nothing is left behind.
	 *
	 * @return	void
	 */
	public function testContactMergeMovesFiles()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		if (empty($conf->societe->multidir_output[$conf->entity])) {
			$this->markTestSkipped('No output directory configured for the third parties');
		}
		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$dest = $this->createContactForMerge('MergeDestFiles');
		$origin = $this->createContactForMerge('MergeOriginFiles');

		$base = $conf->societe->multidir_output[$conf->entity].'/contact/';
		$srcdir = $base.$origin->id;
		$destdir = $base.$dest->id;

		// One file at the root, one in a subdirectory, and one colliding with a file of the target
		dol_mkdir($srcdir.'/sub');
		dol_mkdir($destdir);
		file_put_contents($srcdir.'/phpunitplain.txt', 'from the merged contact');
		file_put_contents($srcdir.'/sub/phpunitnested.txt', 'nested');
		file_put_contents($srcdir.'/phpunitclash.txt', 'version of the merged contact');
		file_put_contents($destdir.'/phpunitclash.txt', 'version of the target contact');

		$result = $dest->mergeContact($origin->id);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals(0, $result, 'mergeContact failed: '.$dest->error);
		$this->assertEmpty($dest->warnings, 'No file should have failed to move');

		$this->assertTrue(dol_is_file($destdir.'/phpunitplain.txt'), 'The file must be moved to the target contact');
		$this->assertTrue(dol_is_file($destdir.'/sub/phpunitnested.txt'), 'The subdirectories must be preserved');
		$this->assertEquals('version of the target contact', file_get_contents($destdir.'/phpunitclash.txt'), 'The file of the target contact must not be overwritten');
		$this->assertEquals('version of the merged contact', file_get_contents($destdir.'/phpunitclash-'.$origin->id.'.txt'), 'The colliding file must be renamed, not lost');
		$this->assertCount(0, dol_dir_list($srcdir, 'files', 1), 'No file must be left on the merged contact');

		// The class transaction rolls the database back but not the files
		dol_delete_dir_recursive($srcdir);
		dol_delete_dir_recursive($destdir);
	}

	/**
	 * testContactMergeThirdPartyOfTheTarget
	 *
	 * socid is among the fields filled when empty, so merging a contact of a third party into a
	 * contact that has none attaches the target to that third party, while a target that already has
	 * one keeps it. Both directions are checked because the second one is not reversible.
	 *
	 * @return	void
	 */
	public function testContactMergeThirdPartyOfTheTarget()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$socids = array();
		foreach (array('PhpunitMergeSocKept', 'PhpunitMergeSocGiven') as $name) {
			$company = new Societe($db);
			$company->name = $name;
			$socid = $company->create($user, 1);
			$this->assertGreaterThan(0, $socid, 'Failed to create the fixture third party: '.$company->errorsToString());
			$socids[] = $socid;
		}

		// A target that has no third party inherits the one of the merged contact
		$orphan = $this->createContactForMerge('MergeDestNoSoc');
		$attached = $this->createContactForMerge('MergeOriginSoc', array('socid' => $socids[1]));
		$this->assertEquals(0, $orphan->mergeContact($attached->id), 'mergeContact failed: '.$orphan->error);
		$check = new Contact($db);
		$check->fetch($orphan->id);
		$this->assertEquals($socids[1], $check->socid, 'A target without a third party must inherit the one of the merged contact');

		// A target that already has one keeps it, whatever the third party of the merged contact
		$kept = $this->createContactForMerge('MergeDestOwnSoc', array('socid' => $socids[0]));
		$other = $this->createContactForMerge('MergeOriginOtherSoc', array('socid' => $socids[1]));
		$this->assertEquals(0, $kept->mergeContact($other->id), 'mergeContact failed: '.$kept->error);
		$check = new Contact($db);
		$check->fetch($kept->id);
		$this->assertEquals($socids[0], $check->socid, 'The third party of the target contact must never be replaced');
	}
}
