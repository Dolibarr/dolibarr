<?php
/* Copyright (C) 2010-2012 Regis Houssin  <regis.houssin@inodbox.com>
 * Copyright (C) 2024-2025	MDW				<mdeweerd@users.noreply.github.com>
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
 */

/**
 *	\file       htdocs/contact/canvas/actions_contactcard_common.class.php
 *	\ingroup    thirdparty
 *	\brief      File for the class Thirdparty contact card controller (common)
 */

/**
 *	\class      ActionsContactCardCommon
 *	\brief      Common Abstract Class for contact managmeent
 */
abstract class ActionsContactCardCommon
{
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * @var string
	 */
	public $dirmodule;
	/**
	 * @var string
	 */
	public $targetmodule;
	/**
	 * @var string
	 */
	public $canvas;
	/**
	 * @var string
	 */
	public $card;

	/**
	 * @var array<string,mixed> Template container
	 */
	public $tpl = array();
	//!
	/**
	 * @var Contact Object container
	 */
	public $object;

	/**
	 * @var string Error code (or message)
	 */
	public $error = '';


	/**
	 * @var string[] Error codes (or messages)
	 */
	public $errors = array();


	/**
	 *  Get object
	 *
	 *  @param	int		$id		Object id
	 *  @return	object			Object loaded
	 */
	public function getObject($id)
	{
		/*$ret = $this->getInstanceDao();

		if (is_object($this->object) && method_exists($this->object,'fetch'))
		{
			if (!empty($id)) $this->object->fetch($id);
		}
		else
		{*/
		$object = new Contact($this->db);
		if (!empty($id)) {
			$object->fetch($id);
		}

		$this->object = $object;

		return $object;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Set content of ->tpl array, to use into template
	 *
	 *  @param	string		$action		Type of action
	 *  @param	int			$id			Id
	 * 	@param	string		$ref		Object ref (if id not provided) / Unused here
	 *  @return	void
	 */
	public function assign_values(&$action, $id, $ref = '')
	{
		// phpcs:enable
		global $conf, $langs, $user, $canvas;
		global $form, $formcompany, $objsoc;

		if ($action == 'add' || $action == 'update') {
			$this->assign_post();
		}

		foreach ($this->object as $key => $value) {
			$this->tpl[$key] = $value;
		}
		$this->tpl['name'] = $this->object->lastname;

		$this->tpl['error'] = $this->error;
		$this->tpl['errors'] = $this->errors;

		if ($action == 'create' || $action == 'edit') {
			if (is_object($objsoc) && $objsoc->id > 0) {
				$this->object->socid = (int) $objsoc->id;
			}
			if (GETPOSTISSET('use_different_address_than_thirdparty')) {
				$this->object->use_thirdparty_address = $this->resolveUseThirdpartyAddressFromRequest((int) $this->object->socid);
			} elseif ($action == 'create' && !isset($this->object->use_thirdparty_address)) {
				$this->object->use_thirdparty_address = ($this->object->socid > 0 ? Contact::USE_THIRDPARTY_ADDRESS_YES : Contact::USE_THIRDPARTY_ADDRESS_NO);
			}
			$this->tpl['show_custom_address_block'] = ($this->object->socid <= 0 || !$this->object->mustUseThirdpartyAddress());
			$this->tpl['use_thirdparty_address'] = ($this->object->mustUseThirdpartyAddress() ? Contact::USE_THIRDPARTY_ADDRESS_YES : Contact::USE_THIRDPARTY_ADDRESS_NO);

			if ($conf->use_javascript_ajax) {
				$this->tpl['ajax_selectcountry'] = "\n".'<script type="text/javascript">
				jQuery(document).ready(function () {
						jQuery("#selectcountry_id").change(function() {
							document.formsoc.action.value="'.$action.'";
							document.formsoc.canvas.value="'.$canvas.'";
							document.formsoc.submit();
						});

						function contactHasOwnPostalValues() {
							return jQuery.trim(jQuery("#address").val()).length > 0
								|| jQuery.trim(jQuery("#zipcode").val()).length > 0
								|| jQuery.trim(jQuery("#town").val()).length > 0
								|| parseInt(jQuery("#state_id").val(), 10) > 0
								|| parseInt(jQuery("#selectcountry_id").val(), 10) > 0;
						}

						function updateContactAddressMode(fromSocChange) {
							var currentSocId = parseInt(jQuery("#socid").val(), 10) || 0;
							// If postal fields were already typed before selecting a thirdparty,
							// keep that intent instead of silently switching to the thirdparty address.
							if (fromSocChange && currentSocId > 0 && contactHasOwnPostalValues()) {
								jQuery("#use_different_address_than_thirdparty").prop("checked", true);
							}

							var useDifferent = jQuery("#use_different_address_than_thirdparty").is(":checked");
							var useThirdpartyAddress = (currentSocId > 0 && !useDifferent);
							jQuery("#use_thirdparty_address").val(useThirdpartyAddress ? "1" : "0");
							jQuery(".contact-address-fields").toggle(!useThirdpartyAddress);
						}

						jQuery("#use_different_address_than_thirdparty").change(function() { updateContactAddressMode(false); });
						jQuery("#socid").change(function() { updateContactAddressMode(true); });
						updateContactAddressMode(false);
					})
				</script>'."\n";
			}

			if (is_object($objsoc) && $objsoc->id > 0) {
				$this->tpl['company'] = $objsoc->getNomUrl(1);
				$this->tpl['company_id'] = $objsoc->id;
			} else {
				$this->tpl['company'] = $form->select_company($this->object->socid, 'socid', '', 1);
			}

			// Civility
			$this->tpl['select_civility'] = $formcompany->select_civility($this->object->civility_id);

			// Keep legacy thirdparty defaults for non-postal contact fields only.
			if ((isset($objsoc->typent_code) && $objsoc->typent_code == 'TE_PRIVATE') || getDolGlobalString('CONTACT_USE_COMPANY_ADDRESS')) {
				if (dol_strlen(trim($this->object->phone_pro)) == 0) {
					$this->object->phone_pro = (string) $objsoc->phone;
				}
				if (dol_strlen(trim($this->object->fax)) == 0) {
					$this->object->fax = (string) $objsoc->fax;
				}
				if (dol_strlen(trim((string) $this->object->email)) == 0) {
					$this->object->email = (string) $objsoc->email;
				}
				$this->tpl['phone_pro'] = $this->object->phone_pro;
				$this->tpl['fax'] = $this->object->fax;
				$this->tpl['email'] = $this->object->email;
			}

			// Zip
			$this->tpl['select_zip'] = $formcompany->select_ziptown((string) $this->object->zip, 'zipcode', array('town', 'selectcountry_id', 'state_id'), 6);

			// Town
			$this->tpl['select_town'] = $formcompany->select_ziptown((string) $this->object->town, 'town', array('zipcode', 'selectcountry_id', 'state_id'));

			if (dol_strlen(trim((string) $this->object->country_id)) == 0) {
				$this->object->country_id = $objsoc->country_id;
			}

			// Country
			$this->tpl['select_country'] = $form->select_country((string) $this->object->country_id, 'country_id');
			$countrynotdefined = $langs->trans("ErrorSetACountryFirst").' ('.$langs->trans("SeeAbove").')';

			if ($user->admin) {
				$this->tpl['info_admin'] = info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
			}

			// State
			if ($this->object->country_id) {
				$this->tpl['select_state'] = $formcompany->select_state($this->object->state_id, $this->object->country_code);
			} else {
				$this->tpl['select_state'] = $countrynotdefined;
			}

			// Public or private
			$selectarray = array('0' => $langs->trans("ContactPublic"), '1' => $langs->trans("ContactPrivate"));
			$this->tpl['select_visibility'] = $form->selectarray('priv', $selectarray, $this->object->priv, 0);
		}

		if ($action == 'view' || $action == 'edit' || $action == 'delete') {
			// Emailing
			if (isModEnabled('mailing')) {
				$langs->load("mails");
				$this->tpl['nb_emailing'] = $this->object->getNbOfEMailings();
			}

			// Linked element
			$this->tpl['contact_element'] = array();
			$i = 0;

			$this->object->load_ref_elements();

			if (isModEnabled('order')) {
				$this->tpl['contact_element'][$i]['linked_element_label'] = $langs->trans("ContactForOrders");
				$this->tpl['contact_element'][$i]['linked_element_value'] = $this->object->ref_commande ? $this->object->ref_commande : $langs->trans("NoContactForAnyOrder");
				$i++;
			}
			if (isModEnabled("propal")) {
				$this->tpl['contact_element'][$i]['linked_element_label'] = $langs->trans("ContactForProposals");
				$this->tpl['contact_element'][$i]['linked_element_value'] = $this->object->ref_propal ? $this->object->ref_propal : $langs->trans("NoContactForAnyProposal");
				$i++;
			}
			if (isModEnabled('contract')) {
				$this->tpl['contact_element'][$i]['linked_element_label'] = $langs->trans("ContactForContracts");
				$this->tpl['contact_element'][$i]['linked_element_value'] = $this->object->ref_contrat ? $this->object->ref_contrat : $langs->trans("NoContactForAnyContract");
				$i++;
			}
			if (isModEnabled('invoice')) {
				$this->tpl['contact_element'][$i]['linked_element_label'] = $langs->trans("ContactForInvoices");
				$this->tpl['contact_element'][$i]['linked_element_value'] = $this->object->ref_facturation ? $this->object->ref_facturation : $langs->trans("NoContactForAnyInvoice");
				$i++;
			}

			// Dolibarr user
			if ($this->object->user_id) {
				$dolibarr_user = new User($this->db);
				$result = $dolibarr_user->fetch($this->object->user_id);
				$this->tpl['dolibarr_user'] = $dolibarr_user->getLoginUrl(1);
			} else {
				$this->tpl['dolibarr_user'] = $langs->trans("NoDolibarrAccess");
			}
		}

		if ($action == 'view' || $action == 'delete') {
			$this->tpl['showrefnav'] = $form->showrefnav($this->object, 'id');

			if ($this->object->socid > 0) {
				$objsoc = new Societe($this->db);

				$objsoc->fetch($this->object->socid);
				$this->tpl['company'] = $objsoc->getNomUrl(1);
			} else {
				$this->tpl['company'] = $langs->trans("ContactNotLinkedToCompany");
			}

			$this->tpl['civility'] = $this->object->getCivilityLabel();
			$effectiveaddressfields = $this->object->getEffectiveAddressFields();

			$this->tpl['address'] = dol_nl2br(dol_escape_htmltag($effectiveaddressfields['address'], 0, 1));

			$this->tpl['zip'] = (!empty($effectiveaddressfields['zip']) ? dol_escape_htmltag($effectiveaddressfields['zip']).'&nbsp;' : '');
			$this->tpl['town'] = dol_escape_htmltag($effectiveaddressfields['town']);
			$this->tpl['departement'] = dol_escape_htmltag($effectiveaddressfields['state']);

			$img = picto_from_langcode($effectiveaddressfields['country_code']);
			$this->tpl['country'] = ($img ? $img.' ' : '').dol_escape_htmltag($effectiveaddressfields['country']);

			$this->tpl['phone_pro'] = dol_print_phone($this->object->phone_pro, $this->object->country_code, 0, $this->object->id, 'AC_TEL');
			$this->tpl['phone_perso'] = dol_print_phone($this->object->phone_perso, $this->object->country_code, 0, $this->object->id, 'AC_TEL');
			$this->tpl['phone_mobile'] = dol_print_phone($this->object->phone_mobile, $this->object->country_code, 0, $this->object->id, 'AC_TEL');
			$this->tpl['fax'] = dol_print_phone($this->object->fax, $this->object->country_code, 0, $this->object->id, 'AC_FAX');
			$this->tpl['email'] = dol_print_email((string) $this->object->email, 0, $this->object->id, 1);

			$this->tpl['visibility'] = $this->object->LibPubPriv($this->object->priv);

			$this->tpl['note'] = $this->object->note_private;
		}

		if ($action == 'create_user') {
			// Full firstname and lastname separated with a dot : firstname.lastname
			include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
			require_once DOL_DOCUMENT_ROOT.'/core/lib/security2.lib.php';
			$login = dol_buildlogin($this->object->lastname, $this->object->firstname);

			$generated_password = getRandomPassword(false);
			$password = $generated_password;

			// Create a form array
			$formquestion = array(
			array('label' => $langs->trans("LoginToCreate"), 'type' => 'text', 'name' => 'login', 'value' => $login),
			array('label' => $langs->trans("Password"), 'type' => 'text', 'name' => 'password', 'value' => $password));

			$this->tpl['action_create_user'] = $form->formconfirm($_SERVER["PHP_SELF"]."?id=".$this->object->id, $langs->trans("CreateDolibarrLogin"), $langs->trans("ConfirmCreateContact"), "confirm_create_user", $formquestion, 'no');
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Assign POST values into object
	 *
	 *  @return		void
	 */
	private function assign_post()
	{
		// phpcs:enable
		global $langs, $mysoc;

		$this->object->socid = GETPOSTINT("socid");
		$this->object->lastname			= (GETPOSTISSET('lastname') ? GETPOST("lastname", 'alphanohtml') : GETPOST("name", 'alphanohtml'));
		$this->object->firstname		= GETPOST("firstname", 'alphanohtml');
		$this->object->civility_id = GETPOST("civility_id", 'aZ09');
		$this->object->poste			= GETPOST("poste", 'alphanohtml');
		$this->object->address = GETPOST("address", 'alphanohtml');
		$this->object->zip = GETPOST("zipcode", 'alphanohtml');
		$this->object->town				= GETPOST("town", 'alphanohtml');
		$this->object->country_id = GETPOST('country_id', 'int') ? GETPOSTINT('country_id') : $mysoc->country_id;
		$this->object->state_id = GETPOSTINT("state_id");
		$this->object->use_thirdparty_address = $this->resolveUseThirdpartyAddressFromRequest((int) $this->object->socid);
		$this->object->phone_pro = GETPOST("phone_pro", 'alphanohtml');
		$this->object->phone_perso = GETPOST("phone_perso", 'alphanohtml');
		$this->object->phone_mobile = GETPOST("phone_mobile", 'alphanohtml');
		$this->object->fax = GETPOST("fax", 'alphanohtml');
		$this->object->email			= GETPOST("email", 'email');
		$this->object->priv				= GETPOSTINT("priv");
		$this->object->note				= GETPOST("note", "restricthtml");
		$this->object->canvas = GETPOST("canvas", 'alphanohtml');

		// We set country_id, and country_code label of the chosen country
		if ($this->object->country_id) {
			$sql = "SELECT code, label FROM ".MAIN_DB_PREFIX."c_country WHERE rowid = ".((int) $this->object->country_id);
			$resql = $this->db->query($sql);
			$obj = null;
			if ($resql) {
				$obj = $this->db->fetch_object($resql);
				$this->db->free($resql);
			} else {
				dol_print_error($this->db);
			}
			if ($obj !== null) {
				$this->object->country_id = (int) GETPOSTINT('country_id');
				$this->object->country_code = $obj->code;
				$this->object->country = $langs->trans("Country".$obj->code) ? $langs->trans("Country".$obj->code) : $obj->label;
			}
		}
	}

	/**
	 * Resolve the persisted address mode from current contact card payload.
	 *
	 * @param   int $socid Linked thirdparty id
	 * @return  int
	 */
	private function resolveUseThirdpartyAddressFromRequest(int $socid): int
	{
		if ($socid <= 0) {
			return Contact::USE_THIRDPARTY_ADDRESS_NO;
		}

		// Keep explicit user intent first, then preserve manually entered postal fields,
		// and only fallback to the thirdparty address when nothing else was requested.
		if (GETPOST('use_different_address_than_thirdparty', 'int')) {
			return Contact::USE_THIRDPARTY_ADDRESS_NO;
		}

		if (GETPOSTINT('use_thirdparty_address') === Contact::USE_THIRDPARTY_ADDRESS_YES) {
			return Contact::USE_THIRDPARTY_ADDRESS_YES;
		}

		if (dol_strlen(trim((string) GETPOST('address', 'alphanohtml'))) > 0
			|| dol_strlen(trim((string) GETPOST('zipcode', 'alphanohtml'))) > 0
			|| dol_strlen(trim((string) GETPOST('town', 'alphanohtml'))) > 0
			|| GETPOSTINT('state_id') > 0
			|| GETPOSTINT('country_id') > 0) {
			return Contact::USE_THIRDPARTY_ADDRESS_NO;
		}

		return Contact::USE_THIRDPARTY_ADDRESS_YES;
	}
}
