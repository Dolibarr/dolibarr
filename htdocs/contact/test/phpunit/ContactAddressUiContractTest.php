<?php
/*
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

global $conf, $db, $langs, $user;

require_once dirname(__FILE__).'/../../../master.inc.php';

/**
 * Test server-rendered UI contracts for contact address mode.
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 */
class ContactAddressUiContractTest extends PHPUnit\Framework\TestCase
{
	/**
	 * Address fields must remain server-rendered for no-JS usage.
	 *
	 * @return void
	 */
	public function testContactCardDoesNotServerHideAddressFields(): void
	{
		$content = file_get_contents(DOL_DOCUMENT_ROOT.'/contact/card.php');

		$this->assertStringContainsString('contact-address-fields', $content);
		$this->assertStringNotContainsString('$addressdisplaystyle = ($showcustomaddressblock ? \'\' : \' style="display:none;"\');', $content);
		$this->assertStringContainsString('function updateContactAddressMode()', $content);
	}

	/**
	 * Canvas templates must keep address fields available without JavaScript.
	 *
	 * @return void
	 */
	public function testCanvasTemplatesDoNotServerHideAddressFields(): void
	{
		$create = file_get_contents(DOL_DOCUMENT_ROOT.'/contact/canvas/default/tpl/contactcard_create.tpl.php');
		$edit = file_get_contents(DOL_DOCUMENT_ROOT.'/contact/canvas/default/tpl/contactcard_edit.tpl.php');

		$this->assertStringContainsString('<tr class="contact-address-fields">', $create);
		$this->assertStringContainsString('<tr class="contact-address-fields">', $edit);
		$this->assertStringNotContainsString('style="display:none;"', $create);
		$this->assertStringNotContainsString('style="display:none;"', $edit);
	}

	/**
	 * Server-rendered address values must be escaped before being printed as HTML.
	 *
	 * @return void
	 */
	public function testContactAddressOutputsAreEscaped(): void
	{
		$card = file_get_contents(DOL_DOCUMENT_ROOT.'/contact/card.php');
		$contactclass = file_get_contents(DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php');
		$canvasactions = file_get_contents(DOL_DOCUMENT_ROOT.'/contact/canvas/actions_contactcard_common.class.php');
		$create = file_get_contents(DOL_DOCUMENT_ROOT.'/contact/canvas/default/tpl/contactcard_create.tpl.php');
		$edit = file_get_contents(DOL_DOCUMENT_ROOT.'/contact/canvas/default/tpl/contactcard_edit.tpl.php');

		$this->assertStringContainsString('$effectivefulladdress = dol_escape_htmltag($effectiveaddressobject->getFullAddress', $card);
		$this->assertStringContainsString('dol_nl2br($effectivefulladdress)', $card);
		$this->assertStringContainsString('dol_escape_htmltag(GETPOSTISSET("address") ? GETPOST("address", \'alphanohtml\') : $object->address)', $card);
		$this->assertStringContainsString('dol_escape_htmltag(dol_format_address($effectiveaddressobject', $contactclass);
		$this->assertStringContainsString('dol_nl2br(dol_escape_htmltag((string) $effectiveaddressobject->address', $canvasactions);
		$this->assertStringContainsString("\$this->tpl['town'] = dol_escape_htmltag((string) \$effectiveaddressobject->town);", $canvasactions);
		$this->assertStringContainsString("\$this->tpl['departement'] = dol_escape_htmltag((string) \$effectiveaddressobject->state);", $canvasactions);
		$this->assertStringContainsString("dol_escape_htmltag(\$this->control->tpl['address'])", $create);
		$this->assertStringContainsString("dol_escape_htmltag(\$this->control->tpl['address'])", $edit);
	}

	/**
	 * Legacy thirdparty prefill must be removed only for postal fields.
	 *
	 * @return void
	 */
	public function testLegacyThirdpartyPrefillKeepsOnlyNonPostalFields(): void
	{
		$card = file_get_contents(DOL_DOCUMENT_ROOT.'/contact/card.php');
		$canvas = file_get_contents(DOL_DOCUMENT_ROOT.'/contact/canvas/actions_contactcard_common.class.php');

		$this->assertStringNotContainsString('$object->address = $objsoc->address', $card);
		$this->assertStringNotContainsString('$object->zip = $objsoc->zip', $card);
		$this->assertStringNotContainsString('$object->town = $objsoc->town', $card);
		$this->assertStringContainsString('$object->phone_pro = $objsoc->phone', $card);
		$this->assertStringContainsString('$object->fax = $objsoc->fax', $card);
		$this->assertStringContainsString('$object->email = $objsoc->email', $card);

		$this->assertStringNotContainsString("\$this->tpl['address'] = \$objsoc->address", $canvas);
		$this->assertStringNotContainsString('$this->object->zip = $objsoc->zip', $canvas);
		$this->assertStringNotContainsString('$this->object->town = $objsoc->town', $canvas);
		$this->assertStringContainsString('$this->object->phone_pro = $objsoc->phone', $canvas);
		$this->assertStringContainsString('$this->object->fax = $objsoc->fax', $canvas);
		$this->assertStringContainsString('$this->object->email = $objsoc->email', $canvas);
	}

	/**
	 * Legacy null records must keep heuristic resolution when rendering edit forms.
	 *
	 * @return void
	 */
	public function testEditFormsKeepLegacyResolutionForNullFlag(): void
	{
		$card = file_get_contents(DOL_DOCUMENT_ROOT.'/contact/card.php');
		$canvas = file_get_contents(DOL_DOCUMENT_ROOT.'/contact/canvas/actions_contactcard_common.class.php');

		$this->assertStringContainsString('$object->mustUseThirdpartyAddress() ? Contact::USE_THIRDPARTY_ADDRESS_YES : Contact::USE_THIRDPARTY_ADDRESS_NO', $card);
		$this->assertStringContainsString("\$action == 'create' && !isset(\$this->object->use_thirdparty_address)", $canvas);
		$this->assertStringContainsString('$this->object->mustUseThirdpartyAddress() ? Contact::USE_THIRDPARTY_ADDRESS_YES : Contact::USE_THIRDPARTY_ADDRESS_NO', $canvas);
	}

	/**
	 * Partial contact widgets must hydrate the address mode for getNomUrl() tooltips.
	 *
	 * @return void
	 */
	public function testPartialContactWidgetsHydrateAddressMode(): void
	{
		$box = file_get_contents(DOL_DOCUMENT_ROOT.'/core/boxes/box_contacts.php');
		$dashboard = file_get_contents(DOL_DOCUMENT_ROOT.'/societe/index.php');
		$list = file_get_contents(DOL_DOCUMENT_ROOT.'/contact/list.php');
		$companylib = file_get_contents(DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php');

		$this->assertStringContainsString('sp.use_thirdparty_address', $box);
		$this->assertStringContainsString('$contactstatic->socid = $objp->fk_soc;', $box);
		$this->assertStringContainsString('$contactstatic->country_id = $objp->country_id;', $box);
		$this->assertStringContainsString('$contactstatic->state_id = $objp->state_id;', $box);
		$this->assertStringContainsString('sp.use_thirdparty_address', $dashboard);
		$this->assertStringContainsString('$contact_static->fk_soc = $objp->rowid;', $dashboard);
		$this->assertStringContainsString('$contact_static->country_id = $objp->ccountry_id;', $dashboard);
		$this->assertStringContainsString('$contact_static->state_id = $objp->cstate_id;', $dashboard);
		$this->assertStringContainsString('$contactstatic->state_id = $obj->fk_departement;', $list);
		$this->assertStringContainsString('$contactstatic->country_id = $obj->country_id;', $companylib);
		$this->assertStringContainsString('$contactstatic->state_id = $obj->state_id;', $companylib);
	}
}
