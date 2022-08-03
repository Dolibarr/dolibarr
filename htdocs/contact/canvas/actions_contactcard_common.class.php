<?php
/* Copyright (C) 2010-2012 Regis Houssin  <regis.houssin@inodbox.com>
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
 *	\brief      Fichier de la classe Thirdparty contact card controller (common)
 */

/**
 *	\class      ActionsContactCardCommon
 *	\brief      Classe permettant la gestion des contacts par defaut
 */
abstract class ActionsContactCardCommon
{
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	public $dirmodule;
	public $targetmodule;
	public $canvas;
	public $card;

	//! Template container
	public $tpl = array();
	//! Object container
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
			if (! empty($id)) $this->object->fetch($id);
		}
		else
		{*/
			$object = new Contact($this->db);
		if (!empty($id)) {
			$object->fetch($id);
		}
			$this->object = $object;
		//}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Set content of ->tpl array, to use into template
	 *
	 *  @param	string		$action    Type of action
	 *  @param	int			$id			Id
	 *  @return	string					HTML output
	 */
	public function assign_values(&$action, $id)
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

		$this->tpl['error'] = $this->error;
		$this->tpl['errors'] = $this->errors;

		if ($action == 'create' || $action == 'edit') {
			if ($conf->use_javascript_ajax) {
				$this->tpl['ajax_selectcountry'] = "\n".'<script type="text/javascript">
				jQuery(document).ready(function () {
						jQuery("#selectcountry_id").change(function() {
							document.formsoc.action.value="'.$action.'";
							document.formsoc.canvas.value="'.$canvas.'";
							document.formsoc.submit();
						});
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

			// Predefined with third party
			if ((isset($objsoc->typent_code) && $objsoc->typent_code == 'TE_PRIVATE') || !empty($conf->global->CONTACT_USE_COMPANY_ADDRESS)) {
				if (dol_strlen(trim($this->object->address)) == 0) {
					$this->tpl['address'] = $objsoc->address;
				}
				if (dol_strlen(trim($this->object->zip)) == 0) {
					$this->object->zip = $objsoc->zip;
				}
				if (dol_strlen(trim($this->object->town)) == 0) {
					$this->object->town = $objsoc->town;
				}
				if (dol_strlen(trim($this->object->phone_pro)) == 0) {
					$this->object->phone_pro = $objsoc->phone;
				}
				if (dol_strlen(trim($this->object->fax)) == 0) {
					$this->object->fax = $objsoc->fax;
				}
				if (dol_strlen(trim($this->object->email)) == 0) {
					$this->object->email = $objsoc->email;
				}
			}

			// Zip
			$this->tpl['select_zip'] = $formcompany->select_ziptown($this->object->zip, 'zipcode', array('town', 'selectcountry_id', 'state_id'), 6);

			// Town
			$this->tpl['select_town'] = $formcompany->select_ziptown($this->object->town, 'town', array('zipcode', 'selectcountry_id', 'state_id'));

			if (dol_strlen(trim($this->object->country_id)) == 0) {
				$this->object->country_id = $objsoc->country_id;
			}

			// Country
			$this->tpl['select_country'] = $form->select_country($this->object->country_id, 'country_id');
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
			$selectarray = array('0'=>$langs->trans("ContactPublic"), '1'=>$langs->trans("ContactPrivate"));
			$this->tpl['select_visibility'] = $form->selectarray('priv', $selectarray, $this->object->priv, 0);
		}

		if ($action == 'view' || $action == 'edit' || $action == 'delete') {
			// Emailing
			if (!empty($conf->mailing->enabled)) {
				$langs->load("mails");
				$this->tpl['nb_emailing'] = $this->object->getNbOfEMailings();
			}

			// Linked element
			$this->tpl['contact_element'] = array();
			$i = 0;

			$this->object->load_ref_elements();

			if (!empty($conf->commande->enabled)) {
				$this->tpl['contact_element'][$i]['linked_element_label'] = $langs->trans("ContactForOrders");
				$this->tpl['contact_element'][$i]['linked_element_value'] = $this->object->ref_commande ? $this->object->ref_commande : $langs->trans("NoContactForAnyOrder");
				$i++;
			}
			if (!empty($conf->propal->enabled)) {
				$this->tpl['contact_element'][$i]['linked_element_label'] = $langs->trans("ContactForProposals");
				$this->tpl['contact_element'][$i]['linked_element_value'] = $this->object->ref_propal ? $this->object->ref_propal : $langs->trans("NoContactForAnyProposal");
				$i++;
			}
			if (!empty($conf->contrat->enabled)) {
				$this->tpl['contact_element'][$i]['linked_element_label'] = $langs->trans("ContactForContracts");
				$this->tpl['contact_element'][$i]['linked_element_value'] = $this->object->ref_contrat ? $this->object->ref_contrat : $langs->trans("NoContactForAnyContract");
				$i++;
			}
			if (isModEnabled('facture')) {
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

			$this->tpl['address'] = dol_nl2br($this->object->address);

			$this->tpl['zip'] = ($this->object->zip ? $this->object->zip.'&nbsp;' : '');

			$img = picto_from_langcode($this->object->country_code);
			$this->tpl['country'] = ($img ? $img.' ' : '').$this->object->country;

			$this->tpl['phone_pro'] = dol_print_phone($this->object->phone_pro, $this->object->country_code, 0, $this->object->id, 'AC_TEL');
			$this->tpl['phone_perso'] = dol_print_phone($this->object->phone_perso, $this->object->country_code, 0, $this->object->id, 'AC_TEL');
			$this->tpl['phone_mobile'] = dol_print_phone($this->object->phone_mobile, $this->object->country_code, 0, $this->object->id, 'AC_TEL');
			$this->tpl['fax'] = dol_print_phone($this->object->fax, $this->object->country_code, 0, $this->object->id, 'AC_FAX');
			$this->tpl['email'] = dol_print_email($this->object->email, 0, $this->object->id, 'AC_EMAIL');

			$this->tpl['visibility'] = $this->object->LibPubPriv($this->object->priv);

			$this->tpl['note'] = nl2br($this->object->note);
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
	 *  @return		string					HTML output
	 */
	private function assign_post()
	{
		// phpcs:enable
		global $langs, $mysoc;

		$this->object->socid = GETPOST("socid", 'int');
		$this->object->lastname			= GETPOST("name");
		$this->object->firstname		= GETPOST("firstname");
		$this->object->civility_id = GETPOST("civility_id");
		$this->object->poste			= GETPOST("poste");
		$this->object->address = GETPOST("address");
		$this->object->zip = GETPOST("zipcode");
		$this->object->town				= GETPOST("town");
		$this->object->country_id = GETPOST("country_id") ? GETPOST("country_id") : $mysoc->country_id;
		$this->object->state_id = GETPOST("state_id");
		$this->object->phone_pro = GETPOST("phone_pro");
		$this->object->phone_perso = GETPOST("phone_perso");
		$this->object->phone_mobile = GETPOST("phone_mobile");
		$this->object->fax = GETPOST("fax");
		$this->object->email			= GETPOST("email");
		$this->object->priv				= GETPOST("priv");
		$this->object->note				= GETPOST("note", "restricthtml");
		$this->object->canvas = GETPOST("canvas");

		// We set country_id, and country_code label of the chosen country
		if ($this->object->country_id) {
			$sql = "SELECT code, label FROM ".MAIN_DB_PREFIX."c_country WHERE rowid = ".((int) $this->object->country_id);
			$resql = $this->db->query($sql);
			if ($resql) {
				$obj = $this->db->fetch_object($resql);
			} else {
				dol_print_error($this->db);
			}
			$this->object->country_id = $langs->trans("Country".$obj->code) ? $langs->trans("Country".$obj->code) : $obj->label;
			$this->object->country_code = $obj->code;
			$this->object->country = $langs->trans("Country".$obj->code) ? $langs->trans("Country".$obj->code) : $obj->label;
		}
	}
}
