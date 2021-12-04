<?php
/* Copyright (C) 2010-2012	Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2011-2012	Laurent Destailleur	<eldy@users.sourceforge.net>
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
 *	\file       htdocs/societe/canvas/actions_card_common.class.php
 *	\ingroup    thirdparty
 *	\brief      Fichier de la classe Thirdparty card controller (common)
 */

/**
 *	Classe permettant la gestion des tiers par defaut
 */
abstract class ActionsCardCommon
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
	 *  Get object from id or ref and save it into this->object
	 *
	 *  @param		int		$id			Object id
	 *  @param		string	$ref		Object ref
	 *  @return		object				Object loaded
	 */
	protected function getObject($id, $ref = '')
	{
		//$ret = $this->getInstanceDao();

		$object = new Societe($this->db);
		if (!empty($id) || !empty($ref)) {
			$object->fetch($id, $ref);
		}
		$this->object = $object;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *    Assign custom values for canvas (for example into this->tpl to be used by templates)
	 *
	 *    @param	string	$action    Type of action
	 *    @param	integer	$id			Id of object
	 *    @param	string	$ref		Ref of object
	 *    @return	void
	 */
	public function assign_values(&$action, $id = 0, $ref = '')
	{
		// phpcs:enable
		global $conf, $langs, $db, $user, $mysoc, $canvas;
		global $form, $formadmin, $formcompany;

		if ($action == 'add' || $action == 'update') {
			$this->assign_post($action);
		}

		if ($_GET["type"] == 'f') {
			$this->object->fournisseur = 1;
		}
		if ($_GET["type"] == 'c') {
			$this->object->client = 1;
		}
		if ($_GET["type"] == 'p') {
			$this->object->client = 2;
		}
		if ($_GET["type"] == 'cp') {
			$this->object->client = 3;
		}
		if ($_REQUEST["private"] == 1) {
			$this->object->particulier = 1;
		}

		foreach ($this->object as $key => $value) {
			$this->tpl[$key] = $value;
		}

		$this->tpl['error'] = get_htmloutput_errors($this->object->error, $this->object->errors);
		if (is_array($GLOBALS['errors'])) {
			$this->tpl['error'] = get_htmloutput_mesg('', $GLOBALS['errors'], 'error');
		}

		if ($action == 'create') {
			if ($conf->use_javascript_ajax) {
				$this->tpl['ajax_selecttype'] = "\n".'<script type="text/javascript">
				$(document).ready(function () {
		              $("#radiocompany").click(function() {
                            document.formsoc.action.value="create";
                            document.formsoc.canvas.value="company";
                            document.formsoc.private.value=0;
                            document.formsoc.submit();
		              });
		               $("#radioprivate").click(function() {
                            document.formsoc.action.value="create";
                            document.formsoc.canvas.value="individual";
                            document.formsoc.private.value=1;
                            document.formsoc.submit();
                      });
		          });
                </script>'."\n";
			}
		}

		if ($action == 'create' || $action == 'edit') {
			if ($conf->use_javascript_ajax) {
				$this->tpl['ajax_selectcountry'] = "\n".'<script type="text/javascript">
				$(document).ready(function () {
						$("#selectcountry_id").change(function() {
							document.formsoc.action.value="'.$action.'";
							document.formsoc.canvas.value="'.$canvas.'";
							document.formsoc.submit();
						});
					})
				</script>'."\n";
			}

			// Load object modCodeClient
			$module = (!empty($conf->global->SOCIETE_CODECLIENT_ADDON) ? $conf->global->SOCIETE_CODECLIENT_ADDON : 'mod_codeclient_leopard');
			if (substr($module, 0, 15) == 'mod_codeclient_' && substr($module, -3) == 'php') {
				$module = substr($module, 0, dol_strlen($module) - 4);
			}
			$dirsociete = array_merge(array('/core/modules/societe/'), $conf->modules_parts['societe']);
			foreach ($dirsociete as $dirroot) {
				$res = dol_include_once($dirroot.$module.'.php');
				if ($res) {
					break;
				}
			}
			$modCodeClient = new $module($db);
			$this->tpl['auto_customercode'] = $modCodeClient->code_auto;
			// We verified if the tag prefix is used
			if ($modCodeClient->code_auto) {
				$this->tpl['prefix_customercode'] = $modCodeClient->verif_prefixIsUsed();
			}

			// TODO create a function
			$this->tpl['select_customertype'] = Form::selectarray('client', array(
				0 => $langs->trans('NorProspectNorCustomer'),
				1 => $langs->trans('Customer'),
				2 => $langs->trans('Prospect'),
				3 => $langs->trans('ProspectCustomer')
			), $this->object->client);

			// Customer
			$this->tpl['customercode'] = $this->object->code_client;
			if ((!$this->object->code_client || $this->object->code_client == -1) && $modCodeClient->code_auto) {
				$this->tpl['customercode'] = $modCodeClient->getNextValue($this->object, 0);
			}
			$this->tpl['ismodifiable_customercode'] = $this->object->codeclient_modifiable();
			$s = $modCodeClient->getToolTip($langs, $this->object, 0);
			$this->tpl['help_customercode'] = $form->textwithpicto('', $s, 1);

			if ((!empty($conf->fournisseur->enabled) && empty($conf->global->MAIN_USE_NEW_SUPPLIERMOD)) || !empty($conf->supplier_order->enabled) || !empty($conf->supplier_invoice->enabled)) {
				$this->tpl['supplier_enabled'] = 1;

				// Load object modCodeFournisseur
				$module = $conf->global->SOCIETE_CODECLIENT_ADDON;
				if (substr($module, 0, 15) == 'mod_codeclient_' && substr($module, -3) == 'php') {
					$module = substr($module, 0, dol_strlen($module) - 4);
				}
				$dirsociete = array_merge(array('/core/modules/societe/'), $conf->modules_parts['societe']);
				foreach ($dirsociete as $dirroot) {
					$res = dol_include_once($dirroot.$module.'.php');
					if ($res) {
						break;
					}
				}
				$modCodeFournisseur = new $module;
				$this->tpl['auto_suppliercode'] = $modCodeFournisseur->code_auto;
				// We verified if the tag prefix is used
				if ($modCodeFournisseur->code_auto) {
					$this->tpl['prefix_suppliercode'] = $modCodeFournisseur->verif_prefixIsUsed();
				}

				// Supplier
				$this->tpl['yn_supplier'] = $form->selectyesno("fournisseur", $this->object->fournisseur, 1);
				$this->tpl['suppliercode'] = $this->object->code_fournisseur;
				if ((!$this->object->code_fournisseur || $this->object->code_fournisseur == -1) && $modCodeFournisseur->code_auto) {
					$this->tpl['suppliercode'] = $modCodeFournisseur->getNextValue($this->object, 1);
				}
				$this->tpl['ismodifiable_suppliercode'] = $this->object->codefournisseur_modifiable();
				$s = $modCodeFournisseur->getToolTip($langs, $this->object, 1);
				$this->tpl['help_suppliercode'] = $form->textwithpicto('', $s, 1);

				$this->object->LoadSupplierCateg();
				$this->tpl['suppliercategory'] = $this->object->SupplierCategories;
			}

			// Zip
			$this->tpl['select_zip'] = $formcompany->select_ziptown($this->object->zip, 'zipcode', array('town', 'selectcountry_id', 'state_id'), 6);

			// Town
			$this->tpl['select_town'] = $formcompany->select_ziptown($this->object->town, 'town', array('zipcode', 'selectcountry_id', 'state_id'));

			// Country
			$this->object->country_id = ($this->object->country_id ? $this->object->country_id : $mysoc->country_id);
			$this->object->country_code = ($this->object->country_code ? $this->object->country_code : $mysoc->country_code);
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

			// Language
			if (!empty($conf->global->MAIN_MULTILANGS)) {
				$this->tpl['select_lang'] = $formadmin->select_language(($this->object->default_lang ? $this->object->default_lang : $conf->global->MAIN_LANG_DEFAULT), 'default_lang', 0, 0, 1);
			}

			// VAT
			$this->tpl['yn_assujtva'] = $form->selectyesno('assujtva_value', $this->tpl['tva_assuj'], 1); // Assujeti par defaut en creation

			// Select users
			$this->tpl['select_users'] = $form->select_dolusers($this->object->commercial_id, 'commercial_id', 1, '', 0, '', '', 0, 0, 0, '', 0, '', 'maxwidth300');

			// Local Tax
			// TODO mettre dans une classe propre au pays
			if ($mysoc->country_code == 'ES') {
				$this->tpl['localtax'] = '';

				if ($mysoc->localtax1_assuj == "1" && $mysoc->localtax2_assuj == "1") {
					$this->tpl['localtax'] .= '<tr><td>'.$langs->trans("LocalTax1IsUsedES").'</td><td>';
					$this->tpl['localtax'] .= $form->selectyesno('localtax1assuj_value', $this->object->localtax1_assuj, 1);
					$this->tpl['localtax'] .= '</td><td>'.$langs->trans("LocalTax2IsUsedES").'</td><td>';
					$this->tpl['localtax'] .= $form->selectyesno('localtax2assuj_value', $this->object->localtax1_assuj, 1);
					$this->tpl['localtax'] .= '</td></tr>';
				} elseif ($mysoc->localtax1_assuj == "1") {
					$this->tpl['localtax'] .= '<tr><td>'.$langs->trans("LocalTax1IsUsedES").'</td><td colspan="3">';
					$this->tpl['localtax'] .= $form->selectyesno('localtax1assuj_value', $this->object->localtax1_assuj, 1);
					$this->tpl['localtax'] .= '</td><tr>';
				} elseif ($mysoc->localtax2_assuj == "1") {
					$this->tpl['localtax'] .= '<tr><td>'.$langs->trans("LocalTax2IsUsedES").'</td><td colspan="3">';
					$this->tpl['localtax'] .= $form->selectyesno('localtax2assuj_value', $this->object->localtax1_assuj, 1);
					$this->tpl['localtax'] .= '</td><tr>';
				}
			}
		} else {
			$head = societe_prepare_head($this->object);

			$this->tpl['showhead'] = dol_get_fiche_head($head, 'card', '', 0, 'company');
			$this->tpl['showend'] = dol_get_fiche_end();

			$this->tpl['showrefnav'] = $form->showrefnav($this->object, 'socid', '', ($user->socid ? 0 : 1), 'rowid', 'nom');

			$this->tpl['checkcustomercode'] = $this->object->check_codeclient();
			$this->tpl['checksuppliercode'] = $this->object->check_codefournisseur();
			$this->tpl['address'] = dol_nl2br($this->object->address);

			$img = picto_from_langcode($this->object->country_code);
			if ($this->object->isInEEC()) {
				$this->tpl['country'] = $form->textwithpicto(($img ? $img.' ' : '').$this->object->country, $langs->trans("CountryIsInEEC"), 1, 0);
			}
			$this->tpl['country'] = ($img ? $img.' ' : '').$this->object->country;

			$this->tpl['phone'] 	= dol_print_phone($this->object->phone, $this->object->country_code, 0, $this->object->id, 'AC_TEL');
			$this->tpl['fax'] 		= dol_print_phone($this->object->fax, $this->object->country_code, 0, $this->object->id, 'AC_FAX');
			$this->tpl['email'] 	= dol_print_email($this->object->email, 0, $this->object->id, 'AC_EMAIL');
			$this->tpl['url'] 		= dol_print_url($this->object->url);

			$this->tpl['tva_assuj'] = yn($this->object->tva_assuj);

			// Third party type
			$arr = $formcompany->typent_array(1);
			$this->tpl['typent'] = $arr[$this->object->typent_code];

			if (!empty($conf->global->MAIN_MULTILANGS)) {
				require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
				//$s=picto_from_langcode($this->default_lang);
				//print ($s?$s.' ':'');
				$langs->load("languages");
				$this->tpl['default_lang'] = ($this->default_lang ? $langs->trans('Language_'.$this->object->default_lang) : '');
			}

			$this->tpl['image_edit'] = img_edit();

			$this->tpl['display_rib'] = $this->object->display_rib();

			// Sales representatives
			$this->tpl['sales_representatives'] = '';
			$listsalesrepresentatives = $this->object->getSalesRepresentatives($user);
			$nbofsalesrepresentative = count($listsalesrepresentatives);
			if ($nbofsalesrepresentative > 3) {   // We print only number
				$this->tpl['sales_representatives'] .= $nbofsalesrepresentative;
			} elseif ($nbofsalesrepresentative > 0) {
				$userstatic = new User($this->db);
				$i = 0;
				foreach ($listsalesrepresentatives as $val) {
					$userstatic->id = $val['id'];
					$userstatic->lastname = $val['name'];
					$userstatic->firstname = $val['firstname'];
					$this->tpl['sales_representatives'] .= $userstatic->getNomUrl(1);
					$i++;
					if ($i < $nbofsalesrepresentative) {
						$this->tpl['sales_representatives'] .= ', ';
					}
				}
			} else {
				$this->tpl['sales_representatives'] .= $langs->trans("NoSalesRepresentativeAffected");
			}

			// Linked member
			if (!empty($conf->adherent->enabled)) {
				$langs->load("members");
				$adh = new Adherent($this->db);
				$result = $adh->fetch('', '', $this->object->id);
				if ($result > 0) {
					$adh->ref = $adh->getFullName($langs);
					$this->tpl['linked_member'] = $adh->getNomUrl(1);
				} else {
					$this->tpl['linked_member'] = $langs->trans("ThirdpartyNotLinkedToMember");
				}
			}

			// Local Tax
			// TODO mettre dans une classe propre au pays
			if ($mysoc->country_code == 'ES') {
				$this->tpl['localtax'] = '';

				if ($mysoc->localtax1_assuj == "1" && $mysoc->localtax2_assuj == "1") {
					$this->tpl['localtax'] .= '<tr><td>'.$langs->trans("LocalTax1IsUsedES").'</td>';
					$this->tpl['localtax'] .= '<td>'.yn($this->object->localtax1_assuj).'</td>';
					$this->tpl['localtax'] .= '<td>'.$langs->trans("LocalTax2IsUsedES").'</td>';
					$this->tpl['localtax'] .= '<td>'.yn($this->object->localtax2_assuj).'</td></tr>';
				} elseif ($mysoc->localtax1_assuj == "1") {
					$this->tpl['localtax'] .= '<tr><td>'.$langs->trans("LocalTax1IsUsedES").'</td>';
					$this->tpl['localtax'] .= '<td colspan="3">'.yn($this->object->localtax1_assuj).'</td></tr>';
				} elseif ($mysoc->localtax2_assuj == "1") {
					$this->tpl['localtax'] .= '<tr><td>'.$langs->trans("LocalTax2IsUsedES").'</td>';
					$this->tpl['localtax'] .= '<td colspan="3">'.yn($this->object->localtax2_assuj).'</td></tr>';
				}
			}
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Assign POST values into object
	 *
	 *	@param		string		$action		Action string
	 *  @return		string					HTML output
	 */
	private function assign_post($action)
	{
		// phpcs:enable
		global $langs, $mysoc;

		$this->object->id = GETPOST("socid");
		$this->object->name = GETPOST("nom");
		$this->object->prefix_comm			= GETPOST("prefix_comm");
		$this->object->client = GETPOST("client");
		$this->object->code_client			= GETPOST("code_client");
		$this->object->fournisseur			= GETPOST("fournisseur");
		$this->object->code_fournisseur = GETPOST("code_fournisseur");
		$this->object->address = GETPOST("adresse");
		$this->object->zip = GETPOST("zipcode");
		$this->object->town					= GETPOST("town");
		$this->object->country_id = GETPOST("country_id") ? GETPOST("country_id") : $mysoc->country_id;
		$this->object->state_id = GETPOST("state_id");
		$this->object->phone				= GETPOST("tel");
		$this->object->fax					= GETPOST("fax");
		$this->object->email				= GETPOST("email", 'alphawithlgt');
		$this->object->url					= GETPOST("url");
		$this->object->capital				= GETPOST("capital");
		$this->object->idprof1				= GETPOST("idprof1");
		$this->object->idprof2				= GETPOST("idprof2");
		$this->object->idprof3				= GETPOST("idprof3");
		$this->object->idprof4				= GETPOST("idprof4");
		$this->object->typent_id = GETPOST("typent_id");
		$this->object->effectif_id = GETPOST("effectif_id");
		$this->object->barcode				= GETPOST("barcode");
		$this->object->forme_juridique_code = GETPOST("forme_juridique_code");
		$this->object->default_lang			= GETPOST("default_lang");
		$this->object->commercial_id		= GETPOST("commercial_id");

		$this->object->tva_assuj = GETPOST("assujtva_value") ? GETPOST("assujtva_value") : 1;
		$this->object->tva_intra = GETPOST("tva_intra");

		//Local Taxes
		$this->object->localtax1_assuj		= GETPOST("localtax1assuj_value");
		$this->object->localtax2_assuj		= GETPOST("localtax2assuj_value");

		// We set country_id, and country_code label of the chosen country
		if ($this->object->country_id) {
			$tmparray = getCountry($this->object->country_id, 'all', $this->db, $langs, 0);
			$this->object->country_code = $tmparray['code'];
			$this->object->country_label = $tmparray['label'];
		}
	}
}
