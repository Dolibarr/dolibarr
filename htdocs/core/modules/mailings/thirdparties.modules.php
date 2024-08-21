<?php
/* Copyright (C) 2018-2018 Andre Schild        <a.schild@aarboard.ch>
 * Copyright (C) 2005-2010 Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin       <regis.houssin@inodbox.com>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 *
 * This file is an example to follow to add your own email selector inside
 * the Dolibarr email tool.
 * Follow instructions given in README file to know what to change to build
 * your own emailing list selector.
 * Code that need to be changed in this file are marked by "CHANGE THIS" tag.
 */

/**
 *	\file       htdocs/core/modules/mailings/thirdparties.modules.php
 *	\ingroup    mailing
 *	\brief      Example file to provide a list of recipients for mailing module
 */

include_once DOL_DOCUMENT_ROOT.'/core/modules/mailings/modules_mailings.php';


/**
 *	Class to manage a list of personalised recipients for mailing feature
 */
class mailing_thirdparties extends MailingTargets
{
	public $name = 'ThirdPartiesByCategories';
	// This label is used if no translation is found for key XXX neither MailingModuleDescXXX where XXX=name is found
	public $desc = "Third parties (by categories)";
	public $require_admin = 0;

	public $require_module = array("societe"); // This module allows to select by categories must be also enabled if category module is not activated

	public $enabled = 'isModEnabled("societe")';

	/**
	 * @var string String with name of icon for myobject. Must be the part after the 'object_' into object_myobject.png
	 */
	public $picto = 'company';


	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		global $conf, $langs;
		$langs->load("companies");

		$this->db = $db;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *    This is the main function that returns the array of emails
	 *
	 *    @param	int		$mailing_id    	Id of mailing. No need to use it.
	 *    @return   int 					Return integer <0 if error, number of emails added if ok
	 */
	public function add_to_target($mailing_id)
	{
		// phpcs:enable
		global $conf, $langs;

		$cibles = array();

		$addDescription = "";
		$addFilter = "";
		if (GETPOSTISSET("filter_client_thirdparties") && GETPOST("filter_client_thirdparties") != '-1') {
			$addFilter .= " AND s.client=".(GETPOSTINT("filter_client_thirdparties"));
			$addDescription = $langs->trans('ProspectCustomer')."=";
			if (GETPOST("filter_client_thirdparties") == 0) {
				$addDescription .= $langs->trans('NorProspectNorCustomer');
			} elseif (GETPOST("filter_client_thirdparties") == 1) {
				$addDescription .= $langs->trans('Customer');
			} elseif (GETPOST("filter_client_thirdparties") == 2) {
				$addDescription .= $langs->trans('Prospect');
			} elseif (GETPOST("filter_client_thirdparties") == 3) {
				$addDescription .= $langs->trans('ProspectCustomer');
			} else {
				$addDescription .= "Unknown status ".GETPOST("filter_client_thirdparties");
			}
		}
		if (GETPOSTISSET("filter_supplier_thirdparties") && GETPOST("filter_supplier_thirdparties") != '-1') {
			$addFilter .= " AND s.fournisseur = ".(GETPOSTINT("filter_supplier_thirdparties"));
			$addDescription = $langs->trans('Supplier')."=";
			if (GETPOST("filter_supplier_thirdparties") == 0) {
				$addDescription .= $langs->trans('No');
			} elseif (GETPOST("filter_supplier_thirdparties") == 1) {
				$addDescription .= $langs->trans('Yes');
			} else {
				$addDescription .= "Unknown status ".GETPOST("filter_supplier_thirdparties");
			}
		}
		if (GETPOSTISSET("filter_status")) {
			if (strlen($addDescription) > 0) {
				$addDescription .= ";";
			}
			$addDescription .= $langs->trans("Status")."=";
			if (GETPOST("filter_status") == '1') {
				$addFilter .= " AND s.status=1";
				$addDescription .= $langs->trans("Enabled");
			} elseif (GETPOST("filter_status") == '0') {
				$addFilter .= " AND s.status=0";
				$addDescription .= $langs->trans("Disabled");
			}
		}
		if (GETPOSTISSET("filter_status")) {
			if (strlen($addDescription) > 0) {
				$addDescription .= ";";
			}
			$addDescription .= $langs->trans("Status")."=";
			if (GETPOST("filter_status") == '1') {
				$addFilter .= " AND s.status=1";
				$addDescription .= $langs->trans("Enabled");
			} elseif (GETPOST("filter_status") == '0') {
				$addFilter .= " AND s.status=0";
				$addDescription .= $langs->trans("Disabled");
			}
		}
		if (GETPOST('default_lang', 'alpha') && GETPOST('default_lang', 'alpha') != '-1') {
			$addFilter .= " AND s.default_lang LIKE '".$this->db->escape(GETPOST('default_lang', 'alpha'))."%'";
			$addDescription = $langs->trans('DefaultLang')."=";
		}
		if (GETPOST('filter_lang_thirdparties', 'alpha') && GETPOST('filter_lang_thirdparties', 'alpha') != '-1') {
			$addFilter .= " AND s.default_lang LIKE '".$this->db->escape(GETPOST('filter_lang_thirdparties', 'alpha'))."%'";
			$addDescription = $langs->trans('DefaultLang')."=";
		}

		// Select the third parties from category
		if (!GETPOST('filter_thirdparties') || GETPOST('filter_thirdparties') == '-1') {
			$sql = "SELECT s.rowid as id, s.email as email, s.nom as name, null as fk_contact, null as firstname, null as label";
			$sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
			$sql .= " WHERE s.email <> ''";
			$sql .= " AND s.entity IN (".getEntity('societe').")";
			$sql .= " AND s.email NOT IN (SELECT email FROM ".MAIN_DB_PREFIX."mailing_cibles WHERE fk_mailing=".((int) $mailing_id).")";
			if (empty($this->evenunsubscribe)) {
				$sql .= " AND (SELECT count(*) FROM ".MAIN_DB_PREFIX."mailing_unsubscribe WHERE email = s.email) = 0";
			}
			$sql .= $addFilter;
		} else {
			$sql = "SELECT s.rowid as id, s.email as email, s.nom as name, null as fk_contact, null as firstname, c.label as label";
			$sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."categorie_societe as cs, ".MAIN_DB_PREFIX."categorie as c";
			$sql .= " WHERE s.email <> ''";
			$sql .= " AND s.entity IN (".getEntity('societe').")";
			$sql .= " AND s.email NOT IN (SELECT email FROM ".MAIN_DB_PREFIX."mailing_cibles WHERE fk_mailing=".((int) $mailing_id).")";
			$sql .= " AND cs.fk_soc = s.rowid";
			$sql .= " AND c.rowid = cs.fk_categorie";
			if (GETPOSTINT('filter_thirdparties') > 0) {
				$sql .= " AND c.rowid=".(GETPOSTINT('filter_thirdparties'));
			}
			if (empty($this->evenunsubscribe)) {
				$sql .= " AND (SELECT count(*) FROM ".MAIN_DB_PREFIX."mailing_unsubscribe WHERE email = s.email) = 0";
			}
			$sql .= $addFilter;
			$sql .= " UNION ";
			$sql .= "SELECT s.rowid as id, s.email as email, s.nom as name, null as fk_contact, null as firstname, c.label as label";
			$sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."categorie_fournisseur as cs, ".MAIN_DB_PREFIX."categorie as c";
			$sql .= " WHERE s.email <> ''";
			$sql .= " AND s.entity IN (".getEntity('societe').")";
			$sql .= " AND s.email NOT IN (SELECT email FROM ".MAIN_DB_PREFIX."mailing_cibles WHERE fk_mailing=".((int) $mailing_id).")";
			$sql .= " AND cs.fk_soc = s.rowid";
			$sql .= " AND c.rowid = cs.fk_categorie";
			if (GETPOSTINT('filter_thirdparties') > 0) {
				$sql .= " AND c.rowid=".(GETPOSTINT('filter_thirdparties'));
			}
			if (empty($this->evenunsubscribe)) {
				$sql .= " AND (SELECT count(*) FROM ".MAIN_DB_PREFIX."mailing_unsubscribe WHERE email = s.email) = 0";
			}
			$sql .= $addFilter;
		}
		$sql .= " ORDER BY email";

		//print $sql;exit;

		// Stock recipients emails into targets table
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
					$otherTxt = ($obj->label ? $langs->transnoentities("Category").'='.$obj->label : '');
					if (strlen($addDescription) > 0 && strlen($otherTxt) > 0) {
						$otherTxt .= ";";
					}
					$otherTxt .= $addDescription;
					$cibles[$j] = array(
								'email' => $obj->email,
								'fk_contact' => $obj->fk_contact,
								'lastname' => $obj->name, // For a thirdparty, we must use name
								'firstname' => '', // For a thirdparty, lastname is ''
								'other' => $otherTxt,
								'source_url' => $this->url($obj->id),
								'source_id' => $obj->id,
								'source_type' => 'thirdparty'
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
		// CHANGE THIS: Optional

		//var $statssql=array();
		//$this->statssql[0]="SELECT field1 as label, count(distinct(email)) as nb FROM mytable WHERE email IS NOT NULL";
		return array();
	}


	/**
	 *	Return here number of distinct emails returned by your selector.
	 *	For example if this selector is used to extract 500 different
	 *	emails from a text file, this function must return 500.
	 *
	 *  @param      string			$sql        Requete sql de comptage
	 *  @return     int|string      			Nb of recipient, or <0 if error, or '' if NA
	 */
	public function getNbOfRecipients($sql = '')
	{
		global $conf;

		$sql = "SELECT count(distinct(s.email)) as nb";
		$sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
		$sql .= " WHERE s.email <> ''";
		$sql .= " AND s.entity IN (".getEntity('societe').")";
		if (empty($this->evenunsubscribe)) {
			$sql .= " AND NOT EXISTS (SELECT rowid FROM ".MAIN_DB_PREFIX."mailing_unsubscribe as mu WHERE mu.email = s.email and mu.entity = ".((int) $conf->entity).")";
		}

		// La requete doit retourner un champ "nb" pour etre comprise par parent::getNbOfRecipients
		return parent::getNbOfRecipients($sql);
	}

	/**
	 *  This is to add a form filter to provide variant of selector
	 *	If used, the HTML select must be called "filter"
	 *
	 *  @return     string      A html select zone
	 */
	public function formFilter()
	{
		global $conf, $langs;

		$langs->load("companies");

		// filter
		$s = '<select id="filter_thirdparties" name="filter_thirdparties" class="flat maxwidth200">';

		// Show categories
		$sql = "SELECT rowid, label, type, visible";
		$sql .= " FROM ".MAIN_DB_PREFIX."categorie";
		$sql .= " WHERE type in (1,2)"; // We keep only categories for suppliers and customers/prospects
		// $sql.= " AND visible > 0";	// We ignore the property visible because third party's categories does not use this property (only products categories use it).
		$sql .= " AND entity = ".$conf->entity;
		$sql .= " ORDER BY label";

		//print $sql;
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);

			if (!isModEnabled("category")) {
				$num = 0; // Force empty list if category module is not enabled
			}

			if ($num) {
				$s .= '<option value="-1">'.$langs->trans("Categories").'</option>';
			} else {
				$s .= '<option value="0">'.$langs->trans("ContactsAllShort").'</option>';
			}

			$i = 0;
			while ($i < $num) {
				$obj = $this->db->fetch_object($resql);

				$type = '';
				if ($obj->type == 1) {
					$type = $langs->trans("Supplier");
				}
				if ($obj->type == 2) {
					$type = $langs->trans("Customer");
				}
				$labeltoshow = $obj->label;
				if ($type) {
					$labeltoshow .= ' <span class="opacitymedium">('.$type.')</span>';
				}
				$s .= '<option value="'.$obj->rowid.'" data-html="'.dol_escape_htmltag($labeltoshow).'">';
				$s .= $labeltoshow;
				$s .= '</option>';
				$i++;
			}
			$s .= ajax_combobox("filter_thirdparties");
		} else {
			dol_print_error($this->db);
		}

		$s .= '</select> ';

		// filter_client_thirdparties
		$s .= '<select id="filter_client_thirdparties" name="filter_client_thirdparties" class="flat minwidth100">';
		$s .= '<option value="-1">'.$langs->trans('ProspectCustomer').'</option>';
		if (!getDolGlobalString('SOCIETE_DISABLE_PROSPECTS')) {
			$s .= '<option value="2">'.$langs->trans('Prospect').'</option>';
		}
		if (!getDolGlobalString('SOCIETE_DISABLE_PROSPECTS') && !getDolGlobalString('SOCIETE_DISABLE_CUSTOMERS') && !getDolGlobalString('SOCIETE_DISABLE_PROSPECTSCUSTOMERS')) {
			$s .= '<option value="3">'.$langs->trans('ProspectCustomer').'</option>';
		}
		if (!getDolGlobalString('SOCIETE_DISABLE_CUSTOMERS')) {
			$s .= '<option value="1">'.$langs->trans('Customer').'</option>';
		}
		$s .= '<option value="0">'.$langs->trans('NorProspectNorCustomer').'</option>';

		$s .= '</select> ';
		$s .= ajax_combobox("filter_client_thirdparties");

		// filter_supplier_thirdparties
		$s .= ' <select id="filter_supplier_thirdparties" name="filter_supplier_thirdparties" class="flat minwidth100">';
		$s .= '<option value="-1">'.$langs->trans("Supplier").'</option>';
		$s .= '<option value="1">'.$langs->trans("Yes").'</option>';
		$s .= '<option value="0">'.$langs->trans("No").'</option>';
		$s .= '</select>';
		$s .= ajax_combobox("filter_supplier_thirdparties");

		// filter_status_thirdparties
		$s .= ' <select id="filter_status_thirdparties" name="filter_status" class="flat">';
		$s .= '<option value="-1">'.$langs->trans("Status").'</option>';
		$s .= '<option value="1">'.$langs->trans("Enabled").'</option>';
		$s .= '<option value="0">'.$langs->trans("Disabled").'</option>';
		$s .= '</select>';
		$s .= ajax_combobox("filter_status_thirdparties");

		// filter_lang_thirdparties
		if (getDolGlobalInt('MAIN_MULTILANGS')) {
			// Choose language
			require_once DOL_DOCUMENT_ROOT.'/core/class/html.formadmin.class.php';
			$formadmin = new FormAdmin($this->db);
			$s .= img_picto($langs->trans("DefaultLang"), 'language', 'class="pictofixedwidth"');
			//$s .= '<span class="opacitymedium">'.$langs->trans("DefaultLang").':</span> ';
			$s .= $formadmin->select_language(GETPOST('filter_lang_thirdparties', 'aZ09'), 'filter_lang_thirdparties', 0, null, $langs->trans("DefaultLang"), 0, 0, '', 0, 0, 0, null, 1);
		}

		return $s;
	}


	/**
	 *  Can include an URL link on each record provided by selector shown on target page.
	 *
	 *  @param	int		$id		ID
	 *  @return string      	Url link
	 */
	public function url($id)
	{
		return '<a href="'.DOL_URL_ROOT.'/societe/card.php?socid='.$id.'">'.img_object('', "company").'</a>';
	}
}
