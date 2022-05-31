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
 *	\file       htdocs/core/modules/mailings/partnership.modules.php
 *	\ingroup    mailing
 *	\brief      Example file to provide a list of recipients for mailing module
 */

include_once DOL_DOCUMENT_ROOT.'/core/modules/mailings/modules_mailings.php';


/**
 *	Class to manage a list of personalised recipients for mailing feature
 */
class mailing_partnership extends MailingTargets
{
	public $name = 'PartnershipThirdartiesOrMembers';
	// This label is used if no translation is found for key XXX neither MailingModuleDescXXX where XXX=name is found
	public $desc = "Thirdparties or members included into a partnership program";
	public $require_admin = 0;

	public $require_module = array(); // This module allows to select by categories must be also enabled if category module is not activated

	/**
	 * @var string String with name of icon for myobject. Must be the part after the 'object_' into object_myobject.png
	 */
	public $picto = 'partnership';

	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	public $enabled = '$conf->partnership->enabled';


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
		$addDescription = '';

		$sql = "SELECT s.rowid as id, s.email as email, s.nom as name, null as fk_contact, null as firstname, pt.label as label, 'thirdparty' as source";
		$sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."partnership as p, ".MAIN_DB_PREFIX."c_partnership_type as pt";
		$sql .= " WHERE s.email <> ''";
		$sql .= " AND s.entity IN (".getEntity('societe').")";
		$sql .= " AND s.email NOT IN (SELECT email FROM ".MAIN_DB_PREFIX."mailing_cibles WHERE fk_mailing=".((int) $mailing_id).")";
		$sql .= " AND p.fk_soc = s.rowid";
		$sql .= " AND pt.rowid = p.fk_type";
		if (GETPOST('filter', 'int') > 0) {
			$sql .= " AND pt.rowid=".((int) GETPOST('filter', 'int'));
		}

		$sql .= " UNION ";

		$sql .= "SELECT s.rowid as id, s.email as email, s.lastname as name, null as fk_contact, s.firstname as firstname, pt.label as label, 'member' as source";
		$sql .= " FROM ".MAIN_DB_PREFIX."adherent as s, ".MAIN_DB_PREFIX."partnership as p, ".MAIN_DB_PREFIX."c_partnership_type as pt";
		$sql .= " WHERE s.email <> ''";
		$sql .= " AND s.entity IN (".getEntity('member').")";
		$sql .= " AND s.email NOT IN (SELECT email FROM ".MAIN_DB_PREFIX."mailing_cibles WHERE fk_mailing=".((int) $mailing_id).")";
		$sql .= " AND p.fk_member = s.rowid";
		$sql .= " AND pt.rowid = p.fk_type";
		if (GETPOST('filter', 'int') > 0) {
			$sql .= " AND pt.rowid=".((int) GETPOST('filter', 'int'));
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
					$otherTxt = ($obj->label ? $langs->transnoentities("PartnershipType").'='.$obj->label : '');
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
								'source_url' => $this->url($obj->id, $obj->source),
								'source_id' => $obj->id,
								'source_type' => $obj->source
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
		$sql .= " FROM ".MAIN_DB_PREFIX."partnership as p, ".MAIN_DB_PREFIX."societe as s";
		$sql .= " WHERE s.rowid = p.fk_soc AND s.email <> ''";
		$sql .= " AND s.entity IN (".getEntity('societe').")";

		$sql .= " UNION ";

		$sql .= "SELECT count(distinct(s.email)) as nb";
		$sql .= " FROM ".MAIN_DB_PREFIX."partnership as p, ".MAIN_DB_PREFIX."adherent as s";
		$sql .= " WHERE s.rowid = p.fk_member AND s.email <> ''";
		$sql .= " AND s.entity IN (".getEntity('member').")";

		//print $sql;

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

		$s = '<select id="filter_partnership" name="filter" class="flat">';

		// Show categories
		$sql = "SELECT rowid, label, code, active";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_partnership_type";
		$sql .= " WHERE active = 1";
		$sql .= " AND entity = ".$conf->entity;
		$sql .= " ORDER BY label";

		//print $sql;
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);

			if (empty($conf->partnership->enabled)) {
				$num = 0; // Force empty list if category module is not enabled
			}

			if ($num) {
				$s .= '<option value="-1">'.$langs->trans("PartnershipType").'</option>';
			}

			$i = 0;
			while ($i < $num) {
				$obj = $this->db->fetch_object($resql);

				$s .= '<option value="'.$obj->rowid.'">'.dol_escape_htmltag($obj->label);
				$s .= '</option>';
				$i++;
			}
			$s .= ajax_combobox("filter_partnership");
		} else {
			dol_print_error($this->db);
		}

		$s .= '</select> ';

		return $s;
	}


	/**
	 *  Can include an URL link on each record provided by selector shown on target page.
	 *
	 *  @param	int		$id			ID
	 *  @param	string	$sourcetype	Source type
	 *  @return string      		Url link
	 */
	public function url($id, $sourcetype = 'thirdparty')
	{
		if ($sourcetype == 'thirparty') {
			return '<a href="'.DOL_URL_ROOT.'/societe/card.php?socid='.((int) $id).'">'.img_object('', "societe").'</a>';
		}
		if ($sourcetype == 'member') {
			return '<a href="'.DOL_URL_ROOT.'/adherent/card.php?id='.((int) $id).'">'.img_object('', "member").'</a>';
		}

		return '';
	}
}
