<?php
/* Copyright (C) 2018-2018 Andre Schild        <a.schild@aarboard.ch>
 * Copyright (C) 2005-2010 Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin       <regis.houssin@inodbox.com>
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

	public $enabled = '$conf->societe->enabled';

	/**
	 * @var string String with name of icon for myobject. Must be the part after the 'object_' into object_myobject.png
	 */
	public $picto = 'company';

	/**
	 * @var DoliDB Database handler.
	 */
	public $db;


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
	 *    @return   int 					<0 if error, number of emails added if ok
	 */
	public function add_to_target($mailing_id)
	{
		// phpcs:enable
		global $conf, $langs;

		$cibles = array();

		$addDescription = "";
		// Select the third parties from category
		if (!GETPOST('filter')) {
			$sql = "SELECT s.rowid as id, s.email as email, s.nom as name, null as fk_contact, null as firstname, null as label";
			$sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
			$sql .= " WHERE s.email <> ''";
			$sql .= " AND s.entity IN (".getEntity('societe').")";
			$sql .= " AND s.email NOT IN (SELECT email FROM ".MAIN_DB_PREFIX."mailing_cibles WHERE fk_mailing=".((int) $mailing_id).")";
			if (GETPOST('default_lang', 'alpha')) {
				$sql .= " AND s.default_lang LIKE '".$this->db->escape(GETPOST('default_lang', 'alpha'))."%'";
			}
		} else {
			$addFilter = "";
			if (GETPOSTISSET("filter_client") && GETPOST("filter_client") <> '-1') {
				$addFilter .= " AND s.client=".((int) GETPOST("filter_client", 'int'));
				$addDescription = $langs->trans('ProspectCustomer')."=";
				if (GETPOST("filter_client") == 0) {
					$addDescription .= $langs->trans('NorProspectNorCustomer');
				} elseif (GETPOST("filter_client") == 1) {
					$addDescription .= $langs->trans('Customer');
				} elseif (GETPOST("filter_client") == 2) {
					$addDescription .= $langs->trans('Prospect');
				} elseif (GETPOST("filter_client") == 3) {
					$addDescription .= $langs->trans('ProspectCustomer');
				} else {
					$addDescription .= "Unknown status ".GETPOST("filter_client");
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
				} else {
					$addFilter .= " AND s.status=0";
					$addDescription .= $langs->trans("Disabled");
				}
			}
			if (GETPOST('default_lang', 'alpha')) {
				$addFilter .= " AND s.default_lang LIKE '".$this->db->escape(GETPOST('default_lang', 'alpha'))."%'";
				$addDescription = $langs->trans('DefaultLang')."=";
			}
			if (GETPOST('filter_lang_thirdparties', 'alpha')) {
				$addFilter .= " AND s.default_lang LIKE '".$this->db->escape(GETPOST('filter_lang_thirdparties', 'alpha'))."%'";
				$addDescription = $langs->trans('DefaultLang')."=";
			}

			$sql = "SELECT s.rowid as id, s.email as email, s.nom as name, null as fk_contact, null as firstname, c.label as label";
			$sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."categorie_societe as cs, ".MAIN_DB_PREFIX."categorie as c";
			$sql .= " WHERE s.email <> ''";
			$sql .= " AND s.entity IN (".getEntity('societe').")";
			$sql .= " AND s.email NOT IN (SELECT email FROM ".MAIN_DB_PREFIX."mailing_cibles WHERE fk_mailing=".((int) $mailing_id).")";
			$sql .= " AND cs.fk_soc = s.rowid";
			$sql .= " AND c.rowid = cs.fk_categorie";
			if (GETPOST('filter', 'int') > 0) {
				$sql .= " AND c.rowid=".((int) GETPOST('filter', 'int'));
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
			if (GETPOST('filter', 'int') > 0) {
				$sql .= " AND c.rowid=".((int) GETPOST('filter', 'int'));
			}
			$sql .= $addFilter;
		}
		$sql .= " ORDER BY email";

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
				if ($old <> $obj->email) {
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
	 *	@return		array		Array with SQL requests
	 */
	public function getSqlArrayForStats()
	{
		// CHANGE THIS: Optionnal

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

		$s = '<select id="filter_thirdparties" name="filter" class="flat">';

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

			if (empty($conf->categorie->enabled)) {
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
				$s .= '<option value="'.$obj->rowid.'">'.dol_trunc($obj->label, 38, 'middle');
				if ($type) {
					$s .= ' ('.$type.')';
				}
				$s .= '</option>';
				$i++;
			}
			$s .= ajax_combobox("filter_thirdparties");
		} else {
			dol_print_error($this->db);
		}

		$s .= '</select> ';

		$s .= '<select id="filter_client_thirdparties" name="filter_client_thirdparties" class="flat">';
		$s .= '<option value="-1">'.$langs->trans('ProspectCustomer').'</option>';
		if (empty($conf->global->SOCIETE_DISABLE_PROSPECTS)) {
			$s .= '<option value="2">'.$langs->trans('Prospect').'</option>';
		}
		if (empty($conf->global->SOCIETE_DISABLE_PROSPECTS) && empty($conf->global->SOCIETE_DISABLE_CUSTOMERS) && empty($conf->global->SOCIETE_DISABLE_PROSPECTSCUSTOMERS)) {
			$s .= '<option value="3">'.$langs->trans('ProspectCustomer').'</option>';
		}
		if (empty($conf->global->SOCIETE_DISABLE_CUSTOMERS)) {
			$s .= '<option value="1">'.$langs->trans('Customer').'</option>';
		}
		$s .= '<option value="0">'.$langs->trans('NorProspectNorCustomer').'</option>';

		$s .= '</select> ';
		$s .= ajax_combobox("filter_client_thirdparties");

		$s .= ' <select id="filter_status_thirdparties" name="filter_status" class="flat">';
		$s .= '<option value="-1">'.$langs->trans("Status").'</option>';
		$s .= '<option value="1">'.$langs->trans("Enabled").'</option>';
		$s .= '<option value="0">'.$langs->trans("Disabled").'</option>';
		$s .= '</select>';
		$s .= ajax_combobox("filter_status_thirdparties");

		// Choose language if multilangue active
		if (@$conf->global->MAIN_MULTILANGS==1) {
			require_once DOL_DOCUMENT_ROOT.'/core/class/html.formadmin.class.php';
			$formadmin = new FormAdmin($this->db);
			$s .= '<span class="opacitymedium">'.$langs->trans("DefaultLang").':</span> ';
			$s .= $formadmin->select_language($langs->getDefaultLang(1), 'filter_lang_thirdparties', 0, null, 1, 0, 0, '', 0, 0, 0, null, 1);
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
