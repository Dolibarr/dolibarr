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
 *	\file       htdocs/core/modules/mailings/eventorganization.modules.php
 *	\ingroup    mailing
 *	\brief      Example file to provide a list of recipients for mailing module
 */


// Load Dolibarr Environment
include_once DOL_DOCUMENT_ROOT.'/core/modules/mailings/modules_mailings.php';


/**
 *	Class to manage a list of personalised recipients for mailing feature
 */
class mailing_eventorganization extends MailingTargets
{
	// This label is used if no translation is found for key XXX neither MailingModuleDescXXX where XXX=name is found
	public $name = 'AttendeesOfOrganizedEvent';
	public $desc = "Attendees of an organized event";

	public $require_admin = 0;

	public $require_module = array(); // This module allows to select by categories must be also enabled if category module is not activated

	/**
	 * @var string String with name of icon for myobject. Must be the part after the 'object_' into object_myobject.png
	 */
	public $picto = 'conferenceorbooth';

	public $enabled = 'isModEnabled("eventorganization")';


	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		global $conf, $langs;
		$langs->load('companies');

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

		$sql = "SELECT p.ref, p.entity, e.rowid as id, e.fk_project, e.email as email, e.email_company as company_name, e.firstname as firstname, e.lastname as lastname,";
		$sql .= " 'eventorganizationattendee' as source";
		$sql .= " FROM ".MAIN_DB_PREFIX."eventorganization_conferenceorboothattendee as e,";
		$sql .= " ".MAIN_DB_PREFIX."projet as p";
		$sql .= " WHERE e.email <> ''";
		$sql .= " AND e.fk_project = p.rowid";
		$sql .= " AND p.entity IN (".getEntity('project').")";
		$sql .= " AND e.email NOT IN (SELECT email FROM ".MAIN_DB_PREFIX."mailing_cibles WHERE fk_mailing=".((int) $mailing_id).")";
		if (GETPOST('filter_eventorganization', 'int') > 0) {
			$sql .= " AND e.fk_project = ".((int) GETPOST('filter_eventorganization', 'int'));
		}
		if (empty($this->evenunsubscribe)) {
			$sql .= " AND NOT EXISTS (SELECT rowid FROM ".MAIN_DB_PREFIX."mailing_unsubscribe as mu WHERE mu.email = e.email and mu.entity = ".((int) $conf->entity).")";
		}
		$sql .= " ORDER BY e.email";

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
					$otherTxt = ($obj->ref ? $langs->transnoentities("Project").'='.$obj->ref : '');
					if (strlen($addDescription) > 0 && strlen($otherTxt) > 0) {
						$otherTxt .= ";";
					}
					$otherTxt .= $addDescription;
					$cibles[$j] = array(
								'email' => $obj->email,
								'fk_project' => $obj->fk_project,
								'lastname' => $obj->lastname,
								'firstname' => $obj->firstname,
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

		$sql = "SELECT COUNT(DISTINCT(e.email)) as nb";
		$sql .= " FROM ".MAIN_DB_PREFIX."eventorganization_conferenceorboothattendee as e, ";
		$sql .= " ".MAIN_DB_PREFIX."projet as p";
		$sql .= " WHERE e.email <> ''";
		$sql .= " AND e.fk_project = p.rowid";
		$sql .= " AND p.entity IN (".getEntity('project').")";
		if (empty($this->evenunsubscribe)) {
			$sql .= " AND NOT EXISTS (SELECT rowid FROM ".MAIN_DB_PREFIX."mailing_unsubscribe as mu WHERE mu.email = e.email and mu.entity = ".((int) $conf->entity).")";
		}

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

		include_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
		$formproject = new FormProjets($this->db);

		$s = img_picto($langs->trans("OrganizedEvent"), 'project', 'class="pictofixedwidth"');
		$s .= $formproject->select_projects(-1, 0, "filter_eventorganization", 0, 0, $langs->trans("OrganizedEvent"), 1, 0, 0, 0, '', 1, 0, '', '', 'usage_organize_event=1');

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
		if ($sourcetype == 'project') {
			return '<a href="'.DOL_URL_ROOT.'/eventorganization/conferenceorboothattendee_card.php?id='.((int) $id).'">'.img_object('', "eventorganization").'</a>';
		}

		return '';
	}
}
