<?php
/* Copyright (C) 2005-2010 Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin       <regis.houssin@inodbox.com>
*
* This file is an example to follow to add your own email selector inside
* the Dolibarr email tool.
* Follow instructions given in README file to know what to change to build
* your own emailing list selector.
* Code that need to be changed in this file are marked by "CHANGE THIS" tag.
*/

/**
 *	\file       htdocs/preopportunity/core/modules/mailings/preopportunity.modules.php
 *	\ingroup    mailing
 *	\brief      Example file to provide a list of recipients for mailing module
 */

include_once DOL_DOCUMENT_ROOT.'/core/modules/mailings/modules_mailings.php';
dol_include_once('/preopportunity/class/preopportunity.class.php');


/**
 *	Class to manage a list of personalised recipients for mailing feature
 */
class mailing_preopportunity extends MailingTargets
{
	public $name = 'Preopportunity Mailings';
	// This label is used if no translation is found for key XXX neither MailingModuleDescXXX where XXX=name is found
	public $desc = "Pre-Opportunity";
	public $require_admin = 0;

	public $require_module = array("preopportunity"); // This module should not be displayed as Selector in mailling

	/**
	 * @var string String with name of icon for myobject. Must be the part after the 'object_' into object_myobject.png
	 */
	public $picto = 'fa-bullhorn';

	public $enabled = 'isModEnabled("preopportunity")';

    public $table_element = 'preopportunity_preopportunity';


	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
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
		$addDescription = '';

		$sql = "SELECT p.ref, p.entity, p.rowid as id, p.email as email, p.companyname as companyname, p.firstname as firstname, p.lastname as lastname, p.status as status, p.phone as phone, p.phonemobile as phonemobile,";
		$sql .= " p.fax as fax, p.address as address, p.zip as zip, p.town as town, p.source as source, ps.label as sourcename, p.description as description";
		$sql .= " FROM ".MAIN_DB_PREFIX."preopportunity_preopportunity as p";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_preopportunity_source as ps ON p.source = ps.rowid";
		$sql .= " WHERE p.email <> ''";
		$sql .= " AND p.entity IN (".getEntity('preopportunity').")";
		$sql .= " AND p.email NOT IN (SELECT email FROM ".MAIN_DB_PREFIX."mailing_cibles WHERE fk_mailing=".((int) $mailing_id).")";
		if (GETPOST('filter_status', 'int') > 0) {
			$sql .= " AND p.status = ".((int) GETPOST('filter_status', 'int'));
		}
		if (GETPOST('filter_source', 'int') > 0) {
			$sql .= " AND p.source = ".((int) GETPOST('filter_source', 'int'));
		}
		if (empty($this->evenunsubscribe)) {
			$sql .= " AND NOT EXISTS (SELECT rowid FROM ".MAIN_DB_PREFIX."mailing_unsubscribe as mu WHERE mu.email = p.email and mu.entity = ".((int) $conf->entity).")";
		}
		$sql .= " ORDER BY p.email";
		// echo $sql;exit;
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
					$otherTxt = ($obj->ref ? $langs->transnoentities("Ref").'='.$obj->ref : '');
					$otherTxt .= ($obj->sourcename ? ';source = '.$obj->sourcename : '');
					$otherTxt .= ($obj->companyname ? ';companyname = '.$obj->companyname : '');
					$otherTxt .= ($obj->phone ? ';phone = '.$obj->phone : '');
					$otherTxt .= ($obj->phonemobile ? ';phonemobile = '.$obj->phone : '');

					$cibles[$j] = array(
								'email' => $obj->email,
								'lastname' => $obj->lastname,
								'firstname' => $obj->firstname,
								'other' => $otherTxt,
								'source' => $obj->sourcename
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
	 *  @param		string			$sql 		Not use here
	 * 	@return     int|string      			Nb of recipient, or <0 if error, or '' if NA
	 */
	public function getNbOfRecipients($sql = '')
	{
		global $conf;

		$sql = "SELECT count(distinct(s.email)) as nb";
		$sql .= " FROM ".MAIN_DB_PREFIX.$this->table_element." as s";
		$sql .= " WHERE s.email != ''";
		$sql .= " AND s.entity IN (".getEntity('preopportunity').")";
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

		$langs->load("preopportunity");

		$s = '';

		$s .= ' <select id="filter_status_preopportunity" name="filter_status" class="flat">';
		$s .= '<option value="-1">'.$langs->trans("Status").'</option>';
		$s .= '<option value="0">'.$langs->trans("Open").'</option>';
		$s .= '<option value="1">'.$langs->trans("Closed").'</option>';
		$s .= '</select>';
		$s .= ajax_combobox("filter_status_preopportunity");


		$s .= ' <select id="filter_source_preopportunity" name="filter_source" class="flat">';
		$s .= '<option value="-1">'.$langs->trans("Source").'</option>';

        $sql = "SELECT t.code, t.label, t.active";
        $sql .= " FROM ";
        $sql .= " ".MAIN_DB_PREFIX."c_preopportunity_source as t";
        $sql .= " WHERE t.active = 1";
        $sql .= " ORDER BY t.position";
        $resql = $this->db->query($sql);
        if ($resql) {
			$num = $this->db->num_rows($resql);
			if ($num) {
				$i = 0;
				while ($i < $num) {
					$obj = $this->db->fetch_object($resql);
					if($obj->code > 0) {
						$s .= '<option value="'.$obj->code.'">'.$obj->label.'</option>';
					}
					$i++;
				}
			} else {
				$s .= '<option value="-1" disabled="disabled">'.$langs->trans("NoSourceFound").'</option>';
			}
		} else {
			dol_print_error($this->db);
		}
		$s .= '</select>';
		$s .= ajax_combobox("filter_source_preopportunity");

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
        return '';
	}
}
