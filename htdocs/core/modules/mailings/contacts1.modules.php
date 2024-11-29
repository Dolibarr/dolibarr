<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 *	\file       htdocs/core/modules/mailings/contacts1.modules.php
 *	\ingroup    mailing
 *	\brief      File of class to offer a selector of emailing targets with Rule 'Poire'.
 */

include_once DOL_DOCUMENT_ROOT.'/core/modules/mailings/modules_mailings.php';


/**
 *	Class to offer a selector of emailing targets from contacts
 */
class mailing_contacts1 extends MailingTargets
{
	public $name = 'ContactCompanies'; // Identifiant du module mailing
	// This label is used if no translation is found for key XXX neither MailingModuleDescXXX where XXX=name is found
	public $desc = 'Contacts of thirdparties (prospects, customers, suppliers...)';
	public $require_module = array("societe"); // Module mailing actif si modules require_module actifs
	public $require_admin = 0; // Module mailing actif pour user admin ou non

	/**
	 * @var string condition to enable module
	 */
	public $enabled = 'isModEnabled("societe")';

	/**
	 * @var string String with name of icon for myobject. Must be the part after the 'object_' into object_myobject.png
	 */
	public $picto = 'contact';


	/**
	 *  Constructor
	 *
	 *  @param      DoliDB      $db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}


	/**
	 *	On the main mailing area, there is a box with statistics.
	 *	If you want to add a line in this report you must provide an
	 *	array of SQL request that returns two field:
	 *	One called "label", One called "nb".
	 *
	 *	@return		string[]		Array with SQL requests
	 */
	public function getSqlArrayForStats()
	{
		global $langs;

		$langs->load("commercial");

		$statssql = array();
		$statssql[0] = "SELECT '".$this->db->escape($langs->trans("NbOfCompaniesContacts"))."' as label,";
		$statssql[0] .= " count(distinct(c.email)) as nb";
		$statssql[0] .= " FROM ".MAIN_DB_PREFIX."socpeople as c";
		$statssql[0] .= " WHERE c.entity IN (".getEntity('contact').")";
		$statssql[0] .= " AND c.email <> ''"; // Note that null != '' is false
		$statssql[0] .= " AND c.statut = 1";

		return $statssql;
	}


	/**
	 *	Return here number of distinct emails returned by your selector.
	 *	For example if this selector is used to extract 500 different
	 *	emails from a text file, this function must return 500.
	 *
	 *  @param		string		$sql		Requete sql de comptage
	 *  @return     int|string      		Nb of recipient, or <0 if error, or '' if NA
	 */
	public function getNbOfRecipients($sql = '')
	{
		global $conf;

		$sql = "SELECT count(distinct(c.email)) as nb";
		$sql .= " FROM ".MAIN_DB_PREFIX."socpeople as c";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = c.fk_soc";
		$sql .= " WHERE c.entity IN (".getEntity('contact').")";
		$sql .= " AND c.email <> ''"; // Note that null != '' is false
		if (empty($this->evenunsubscribe)) {
			$sql .= " AND NOT EXISTS (SELECT rowid FROM ".MAIN_DB_PREFIX."mailing_unsubscribe as mu WHERE mu.email = c.email and mu.entity = ".((int) $conf->entity).")";
		}
		// exclude unsubscribed users
		$sql .= " AND c.statut = 1";

		// The request must return a field called "nb" to be understandable by parent::getNbOfRecipients
		return parent::getNbOfRecipients($sql);
	}


	/**
	 *   Affiche formulaire de filtre qui apparait dans page de selection des destinataires de mailings
	 *
	 *   @return     string      Retourne zone select
	 */
	public function formFilter()
	{
		global $conf,$langs;

		// Load translation files required by the page
		$langs->loadLangs(array("commercial", "companies", "suppliers", "categories"));

		$s = '';

		// Add filter on job position
		$sql = "SELECT sp.poste, count(distinct(sp.email)) AS nb";
		$sql .= " FROM ".MAIN_DB_PREFIX."socpeople as sp";
		$sql .= " WHERE sp.entity IN (".getEntity('contact').")";
		$sql .= " AND sp.email <> ''";    // Note that null != '' is false
		$sql .= " AND sp.statut = 1";
		$sql .= " AND (sp.poste IS NOT NULL AND sp.poste <> '')";
		$sql .= " GROUP BY sp.poste";
		$sql .= " ORDER BY sp.poste";
		$resql = $this->db->query($sql);

		$s .= '<select id="filter_jobposition_contact" name="filter_jobposition" class="flat maxwidth200" placeholder="'.dol_escape_htmltag($langs->trans("PostOrFunction")).'">';
		$s .= '<option value="-1">'.$langs->trans("PostOrFunction").'</option>';
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			if ($num > 0) {
				while ($i < $num) {
					$obj = $this->db->fetch_object($resql);
					$s .= '<option value="'.dol_escape_htmltag($obj->poste).'">'.dol_escape_htmltag($obj->poste).' ('.$obj->nb.')</option>';
					$i++;
				}
			} else {
				$s .= '<option disabled="disabled" value="">'.$langs->trans("None").'</option>';
			}
		} else {
			dol_print_error($this->db);
		}
		$s .= '</select>';
		$s .= ajax_combobox("filter_jobposition_contact");
		$s .= ' ';

		// Filter on contact category
		$sql = "SELECT c.label, count(distinct(sp.email)) AS nb";
		$sql .= " FROM ";
		$sql .= " ".MAIN_DB_PREFIX."socpeople as sp,";
		$sql .= " ".MAIN_DB_PREFIX."categorie as c,";
		$sql .= " ".MAIN_DB_PREFIX."categorie_contact as cs";
		$sql .= " WHERE sp.entity IN (".getEntity('contact').")";
		$sql .= " AND sp.email <> ''";    // Note that null != '' is false
		$sql .= " AND sp.statut = 1";
		$sql .= " AND cs.fk_categorie = c.rowid";
		$sql .= " AND cs.fk_socpeople = sp.rowid";
		$sql .= " GROUP BY c.label";
		$sql .= " ORDER BY c.label";
		$resql = $this->db->query($sql);

		$s .= '<select id="filter_category_contact" name="filter_category" class="flat maxwidth200">';
		$s .= '<option value="-1">'.$langs->trans("ContactCategoriesShort").'</option>';
		if ($resql) {
			$num = $this->db->num_rows($resql);
			if ($num) {
				$i = 0;
				while ($i < $num) {
					$obj = $this->db->fetch_object($resql);
					$s .= '<option value="'.$obj->label.'">'.$obj->label.' ('.$obj->nb.')</option>';
					$i++;
				}
			} else {
				$s .= '<option value="-1" disabled="disabled">'.$langs->trans("NoContactWithCategoryFound").'</option>';
			}
		} else {
			dol_print_error($this->db);
		}
		$s .= '</select>';
		$s .= ajax_combobox("filter_category_contact");
		$s .= '<br>';

		// Add prospect of a particular level
		$s .= '<select id="filter_contact" name="filter" class="flat maxwidth200">';
		$sql = "SELECT code, label";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_prospectlevel";
		$sql .= " WHERE active > 0";
		$sql .= " ORDER BY label";
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			if ($num) {
				$s .= '<option value="-1">'.$langs->trans("NatureOfThirdParty").'</option>';
			} else {
				$s .= '<option value="-1">'.$langs->trans("ContactsAllShort").'</option>';
			}
			$s .= '<option value="prospects">'.$langs->trans("ThirdPartyProspects").'</option>';

			$i = 0;
			while ($i < $num) {
				$obj = $this->db->fetch_object($resql);
				$level = $langs->trans($obj->code);
				if ($level == $obj->code) {
					$level = $langs->trans($obj->label);
				}
				$labeltoshow = $langs->trans("ThirdPartyProspects").' <span class="opacitymedium">('.$langs->trans("ProspectLevelShort").'='.$level.')</span>';
				$s .= '<option value="prospectslevel'.$obj->code.'" data-html="'.dol_escape_htmltag($labeltoshow).'">'.$labeltoshow.'</option>';
				$i++;
			}
		} else {
			dol_print_error($this->db);
		}
		$s .= '<option value="customers">'.$langs->trans("ThirdPartyCustomers").'</option>';
		//$s.='<option value="customersidprof">'.$langs->trans("ThirdPartyCustomersWithIdProf12",$langs->trans("ProfId1"),$langs->trans("ProfId2")).'</option>';
		$s .= '<option value="suppliers">'.$langs->trans("ThirdPartySuppliers").'</option>';
		$s .= '</select>';
		$s .= ajax_combobox("filter_contact");

		$s .= ' ';

		// Filter on thirdparty category
		$sql = "SELECT c.label, count(distinct(sp.email)) AS nb";
		$sql .= " FROM ";
		$sql .= " ".MAIN_DB_PREFIX."socpeople as sp,";
		$sql .= " ".MAIN_DB_PREFIX."categorie as c,";
		$sql .= " ".MAIN_DB_PREFIX."categorie_societe as cs";
		$sql .= " WHERE sp.entity IN (".getEntity('contact').")";
		$sql .= " AND sp.email <> ''";    // Note that null != '' is false
		$sql .= " AND sp.statut = 1";
		$sql .= " AND cs.fk_categorie = c.rowid";
		$sql .= " AND cs.fk_soc = sp.fk_soc";
		$sql .= " GROUP BY c.label";
		$sql .= " ORDER BY c.label";
		$resql = $this->db->query($sql);

		$s .= '<select id="filter_category_customer_contact" name="filter_category_customer" class="flat maxwidth200">';
		$s .= '<option value="-1">'.$langs->trans("CustomersProspectsCategoriesShort").'</option>';
		if ($resql) {
			$num = $this->db->num_rows($resql);
			if ($num) {
				$i = 0;
				while ($i < $num) {
					$obj = $this->db->fetch_object($resql);
					$s .= '<option value="'.$obj->label.'">'.$obj->label.' ('.$obj->nb.')</option>';
					$i++;
				}
			} else {
				$s .= '<option value="-1" disabled="disabled">'.$langs->trans("NoContactLinkedToThirdpartieWithCategoryFound").'</option>';
			}
		} else {
			dol_print_error($this->db);
		}
		$s .= '</select>';
		$s .= ajax_combobox("filter_category_customer_contact");

		$s .= ' ';

		// Filter on thirdparty category
		$sql = "SELECT c.label, count(distinct(sp.email)) AS nb";
		$sql .= " FROM ";
		$sql .= " ".MAIN_DB_PREFIX."socpeople as sp,";
		$sql .= " ".MAIN_DB_PREFIX."categorie as c,";
		$sql .= " ".MAIN_DB_PREFIX."categorie_fournisseur as cs";
		$sql .= " WHERE sp.entity IN (".getEntity('contact').")";
		$sql .= " AND sp.email <> ''";    // Note that null != '' is false
		$sql .= " AND sp.statut = 1";
		$sql .= " AND cs.fk_categorie = c.rowid";
		$sql .= " AND cs.fk_soc = sp.fk_soc";
		$sql .= " GROUP BY c.label";
		$sql .= " ORDER BY c.label";
		$resql = $this->db->query($sql);

		$s .= '<select id="filter_category_supplier_contact" name="filter_category_supplier" class="flat maxwidth200">';
		$s .= '<option value="-1">'.$langs->trans("SuppliersCategoriesShort").'</option>';
		if ($resql) {
			$num = $this->db->num_rows($resql);
			if ($num) {
				$i = 0;
				while ($i < $num) {
					$obj = $this->db->fetch_object($resql);
					$s .= '<option value="'.$obj->label.'">'.$obj->label.' ('.$obj->nb.')</option>';
					$i++;
				}
			} else {
				$s .= '<option value="-1" disabled="disabled">'.$langs->trans("NoContactLinkedToThirdpartieWithCategoryFound").'</option>';
			}
		} else {
			dol_print_error($this->db);
		}
		$s .= '</select>';

		$s .= ajax_combobox("filter_category_supplier_contact");

		// Choose language
		if (getDolGlobalInt('MAIN_MULTILANGS')) {
			require_once DOL_DOCUMENT_ROOT.'/core/class/html.formadmin.class.php';
			$formadmin = new FormAdmin($this->db);
			$s .= img_picto($langs->trans("DefaultLang"), 'language', 'class="pictofixedwidth"');
			$s .= $formadmin->select_language(GETPOST('filter_lang', 'aZ09'), 'filter_lang', 0, null, $langs->trans("DefaultLang"), 0, 0, '', 0, 0, 0, null, 1);
		}

		return $s;
	}


	/**
	 *  Provide the URL to the car of the source information of the recipient for the mailing
	 *
	 *  @param	int		$id		ID
	 *  @return string      	URL link
	 */
	public function url($id)
	{
		return '<a href="'.DOL_URL_ROOT.'/contact/card.php?id='.$id.'">'.img_object('', "contact").'</a>';
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Add some recipients into target table
	 *
	 *  @param  int		$mailing_id    	Id of emailing
	 *  @return int           			Return integer <0 si erreur, nb ajout si ok
	 */
	public function add_to_target($mailing_id)
	{
		// phpcs:enable
		global $conf, $langs;

		$filter = GETPOST('filter', 'alpha');
		$filter_jobposition = GETPOST('filter_jobposition', 'alpha');
		$filter_category = GETPOST('filter_category', 'alpha');
		$filter_category_customer = GETPOST('filter_category_customer', 'alpha');
		$filter_category_supplier = GETPOST('filter_category_supplier', 'alpha');
		$filter_lang = GETPOST('filter_lang', 'alpha');

		$cibles = array();

		// List prospects levels
		$prospectlevel = array();
		$sql = "SELECT code, label";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_prospectlevel";
		$sql .= " WHERE active > 0";
		$sql .= " ORDER BY label";
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$obj = $this->db->fetch_object($resql);
				$prospectlevel[$obj->code] = $obj->label;
				$i++;
			}
		} else {
			dol_print_error($this->db);
		}

		// Request must return: id, email, fk_contact, lastname, firstname, other
		$sql = "SELECT sp.rowid as id, sp.email as email, sp.rowid as fk_contact, sp.lastname, sp.firstname, sp.civility as civility_id, sp.poste as jobposition,";
		$sql .= " s.nom as companyname";
		$sql .= " FROM ".MAIN_DB_PREFIX."socpeople as sp";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = sp.fk_soc";
		if ($filter_category != 'all' && $filter_category != '-1') {
			$sql .= ", ".MAIN_DB_PREFIX."categorie as c";
			$sql .= ", ".MAIN_DB_PREFIX."categorie_contact as cs";
		}
		if ($filter_category_customer != 'all' && $filter_category_customer != '-1') {
			$sql .= ", ".MAIN_DB_PREFIX."categorie as c2";
			$sql .= ", ".MAIN_DB_PREFIX."categorie_societe as c2s";
		}
		if ($filter_category_supplier != 'all' && $filter_category_supplier != '-1') {
			$sql .= ", ".MAIN_DB_PREFIX."categorie as c3";
			$sql .= ", ".MAIN_DB_PREFIX."categorie_fournisseur as c3s";
		}
		$sql .= " WHERE sp.entity IN (".getEntity('contact').")";
		$sql .= " AND sp.email <> ''";

		if (empty($this->evenunsubscribe)) {
			$sql .= " AND NOT EXISTS (SELECT rowid FROM ".MAIN_DB_PREFIX."mailing_unsubscribe as mu WHERE mu.email = sp.email and mu.entity = ".((int) $conf->entity).")";
		}
		// Exclude unsubscribed email addresses
		$sql .= " AND sp.statut = 1";
		$sql .= " AND sp.email NOT IN (SELECT email FROM ".MAIN_DB_PREFIX."mailing_cibles WHERE fk_mailing=".((int) $mailing_id).")";

		// Filter on category
		if ($filter_category != 'all' && $filter_category != '-1') {
			$sql .= " AND cs.fk_categorie = c.rowid AND cs.fk_socpeople = sp.rowid";
			$sql .= " AND c.label = '".$this->db->escape($filter_category)."'";
		}
		if ($filter_category_customer != 'all' && $filter_category_customer != '-1') {
			$sql .= " AND c2s.fk_categorie = c2.rowid AND c2s.fk_soc = sp.fk_soc";
			$sql .= " AND c2.label = '".$this->db->escape($filter_category_customer)."'";
		}
		if ($filter_category_supplier != 'all' && $filter_category_supplier != '-1') {
			$sql .= " AND c3s.fk_categorie = c3.rowid AND c3s.fk_soc = sp.fk_soc";
			$sql .= " AND c3.label = '".$this->db->escape($filter_category_supplier)."'";
		}

		// Filter on language
		if (!empty($filter_lang) && $filter_lang != '-1') {
			$sql .= " AND sp.default_lang LIKE '".$this->db->escape($filter_lang)."%'";
		}

		// Filter on nature
		$key = $filter;

		//print "xx".$key;
		if ($key == 'prospects') {
			$sql .= " AND s.client = 2";
		}
		foreach ($prospectlevel as $codelevel => $valuelevel) {
			if ($key == 'prospectslevel'.$codelevel) {
				$sql .= " AND s.fk_prospectlevel = '".$this->db->escape($codelevel)."'";
			}
		}
		if ($key == 'customers') {
			$sql .= " AND s.client = 1";
		}
		if ($key == 'suppliers') {
			$sql .= " AND s.fournisseur = 1";
		}

		// Filter on job position
		$key = $filter_jobposition;
		if (!empty($key) && $key != 'all'  && $key != '-1') {
			$sql .= " AND sp.poste = '".$this->db->escape($key)."'";
		}

		$sql .= " ORDER BY sp.email";
		//print "wwwwwwx".$sql;

		// Store recipients in target tables
		$result = $this->db->query($sql);
		if ($result) {
			$num = $this->db->num_rows($result);
			$i = 0;
			$j = 0;

			dol_syslog(get_class($this)."::add_to_target mailing ".$num." targets found");

			$old = '';
			while ($i < $num) {
				$obj = $this->db->fetch_object($result);
				if ($old != $obj->email) {
					$cibles[$j] = array(
						'email' => $obj->email,
						'fk_contact' => (int) $obj->fk_contact,
						'lastname' => $obj->lastname,
						'firstname' => $obj->firstname,
						'other' =>
							($langs->transnoentities("ThirdParty").'='.$obj->companyname).';'.
							($langs->transnoentities("UserTitle").'='.($obj->civility_id ? $langs->transnoentities("Civility".$obj->civility_id) : '')).';'.
							($langs->transnoentities("PostOrFunction").'='.$obj->jobposition),
						'source_url' => $this->url($obj->id),
						'source_id' => (int) $obj->id,
						'source_type' => 'contact'
					);
					$old = $obj->email;
					$j++;
				}

				$i++;
			}
		} else {
			dol_syslog($this->db->error());
			$this->error = $this->db->error();
			return -1;
		}

		return parent::addTargetsToDatabase($mailing_id, $cibles);
	}
}
