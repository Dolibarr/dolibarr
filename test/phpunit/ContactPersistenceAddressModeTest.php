<?php
/*
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

global $conf, $db, $langs, $user;

require_once dirname(__FILE__).'/CommonClassTest.class.php';
require_once dirname(__FILE__).'/../../htdocs/contact/class/contact.class.php';
require_once dirname(__FILE__).'/../../htdocs/societe/class/societe.class.php';

/**
 * Integration tests for persisted use_thirdparty_address values.
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 */
class ContactPersistenceAddressModeTest extends CommonClassTest
{
	/**
	 * Create a thirdparty fixture.
	 *
	 * @return Societe
	 */
	private function createThirdpartyFixture(): Societe
	{
		$suffix = dol_print_date(dol_now(), 'dayhourlogsmall').mt_rand(10, 99);
		$thirdparty = new Societe($this->savdb);
		$thirdparty->initAsSpecimen();
		$thirdparty->name = 'Address mode thirdparty '.$suffix;
		$thirdparty->client = 1;
		$thirdparty->fournisseur = 0;
		$thirdparty->code_client = 'CC-'.$suffix;
		$thirdparty->code_fournisseur = '';
		$thirdparty->email = 'address-mode-thirdparty-'.$suffix.'@example.test';
		$thirdparty->tva_intra = 'FR'.$suffix;
		$thirdparty->euid = 'FR-RCSXXXX-'.$suffix;
		$thirdparty->idprof1 = 'idprof1-'.$suffix;
		$thirdparty->idprof2 = 'idprof2-'.$suffix;
		$thirdparty->idprof3 = 'idprof3-'.$suffix;
		$thirdparty->idprof4 = 'idprof4-'.$suffix;
		$thirdparty->idprof5 = 'idprof5-'.$suffix;
		$thirdparty->idprof6 = 'idprof6-'.$suffix;
		$thirdparty->code_compta_client = '411'.$suffix;

		$result = $thirdparty->create($this->savuser);
		$this->assertGreaterThan(0, $result, 'Thirdparty creation failed: '.$thirdparty->error);

		return $thirdparty;
	}

	/**
	 * Build a contact fixture.
	 *
	 * @param int $socid Linked thirdparty id
	 * @return Contact
	 */
	private function buildContactFixture(int $socid = 0): Contact
	{
		$contact = new Contact($this->savdb);
		$contact->lastname = 'AddressMode';
		$contact->firstname = 'Contact'.mt_rand();
		$contact->socid = $socid;
		$contact->email = 'addressmode'.mt_rand().'@example.test';

		return $contact;
	}

	/**
	 * Refetch a contact from database.
	 *
	 * @param int $contactid Contact id
	 * @return Contact
	 */
	private function refetchContact(int $contactid): Contact
	{
		$contact = new Contact($this->savdb);
		$result = $contact->fetch($contactid);
		$this->assertGreaterThan(0, $result, 'Contact fetch failed: '.$contact->error);

		return $contact;
	}

	/**
	 * Create must default to thirdparty mode when a linked thirdparty exists.
	 *
	 * @return void
	 */
	public function testCreateDefaultsToThirdpartyAddressWhenSocidExists(): void
	{
		$thirdparty = $this->createThirdpartyFixture();
		$contact = $this->buildContactFixture((int) $thirdparty->id);

		$contactid = $contact->create($this->savuser, 1);
		$this->assertGreaterThan(0, $contactid, 'Contact creation failed: '.$contact->error);

		$refetched = $this->refetchContact((int) $contactid);
		$this->assertSame(Contact::USE_THIRDPARTY_ADDRESS_YES, (int) $refetched->use_thirdparty_address);
	}

	/**
	 * Create must default to contact mode when no linked thirdparty exists.
	 *
	 * @return void
	 */
	public function testCreateDefaultsToContactAddressWithoutSocid(): void
	{
		$contact = $this->buildContactFixture();

		$contactid = $contact->create($this->savuser, 1);
		$this->assertGreaterThan(0, $contactid, 'Contact creation failed: '.$contact->error);

		$refetched = $this->refetchContact((int) $contactid);
		$this->assertSame(Contact::USE_THIRDPARTY_ADDRESS_NO, (int) $refetched->use_thirdparty_address);
	}

	/**
	 * Create must preserve contact mode when own postal fields are provided.
	 *
	 * @return void
	 */
	public function testCreateKeepsContactAddressWhenOwnPostalFieldsAreProvided(): void
	{
		$thirdparty = $this->createThirdpartyFixture();
		$contact = $this->buildContactFixture((int) $thirdparty->id);
		$contact->address = '21 Jump Street';
		$contact->zip = '33000';
		$contact->town = 'Bordeaux';

		$contactid = $contact->create($this->savuser, 1);
		$this->assertGreaterThan(0, $contactid, 'Contact creation failed: '.$contact->error);

		$refetched = $this->refetchContact((int) $contactid);
		$this->assertSame(Contact::USE_THIRDPARTY_ADDRESS_NO, (int) $refetched->use_thirdparty_address);
	}

	/**
	 * Update must normalize a legacy null value to explicit thirdparty mode when own postal fields are empty.
	 *
	 * @return void
	 */
	public function testUpdateNormalizesLegacyNullToThirdpartyMode(): void
	{
		$thirdparty = $this->createThirdpartyFixture();
		$contact = $this->buildContactFixture((int) $thirdparty->id);
		$contactid = $contact->create($this->savuser, 1);
		$this->assertGreaterThan(0, $contactid, 'Contact creation failed: '.$contact->error);

		$sql = "UPDATE ".$this->savdb->prefix()."socpeople";
		$sql .= " SET use_thirdparty_address = NULL";
		$sql .= ", address = ''";
		$sql .= ", zip = ''";
		$sql .= ", town = ''";
		$sql .= ", fk_departement = NULL";
		$sql .= ", fk_pays = NULL";
		$sql .= " WHERE rowid = ".((int) $contactid);
		$result = $this->savdb->query($sql);
		$this->assertTrue((bool) $result, 'Legacy normalization setup failed: '.$this->savdb->lasterror());

		$contact = $this->refetchContact((int) $contactid);
		$result = $contact->update((int) $contactid, $this->savuser, 1);
		$this->assertGreaterThan(0, $result, 'Contact update failed: '.$contact->error);

		$refetched = $this->refetchContact((int) $contactid);
		$this->assertSame(Contact::USE_THIRDPARTY_ADDRESS_YES, (int) $refetched->use_thirdparty_address);
	}

	/**
	 * Update must force contact mode when no linked thirdparty exists.
	 *
	 * @return void
	 */
	public function testUpdateForcesContactModeWithoutSocid(): void
	{
		$contact = $this->buildContactFixture();
		$contactid = $contact->create($this->savuser, 1);
		$this->assertGreaterThan(0, $contactid, 'Contact creation failed: '.$contact->error);

		$contact = $this->refetchContact((int) $contactid);
		$contact->use_thirdparty_address = Contact::USE_THIRDPARTY_ADDRESS_YES;
		$result = $contact->update((int) $contactid, $this->savuser, 1);
		$this->assertGreaterThan(0, $result, 'Contact update failed: '.$contact->error);

		$refetched = $this->refetchContact((int) $contactid);
		$this->assertSame(Contact::USE_THIRDPARTY_ADDRESS_NO, (int) $refetched->use_thirdparty_address);
	}
}
