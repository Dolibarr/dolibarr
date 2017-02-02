<?php
/* Copyright (C) 2005-2010 Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin       <regis.houssin@capnetworks.com>
 *
 * This file is an example to follow to add your own email selector inside
 * the Dolibarr email tool.
 * Follow instructions given in README file to know what to change to build
 * your own emailing list selector.
 * Code that need to be changed in this file are marked by "CHANGE THIS" tag.
 */

/**
 *	\file       htdocs/core/modules/mailings/framboise.modules.php
 *	\ingroup    mailing
 *	\brief      Example file to provide a list of recipients for mailing module
 */

include_once DOL_DOCUMENT_ROOT.'/core/modules/mailings/modules_mailings.php';


/**
 *	Class to manage a list of personalised recipients for mailing feature
 */
class mailing_framboise extends MailingTargets
{
	var $name='MembersCategories';
	// This label is used if no translation is found for key XXX neither MailingModuleDescXXX where XXX=name is found
	var $desc="Foundation members with emails (by categories)";
	// Set to 1 if selector is available for admin users only
	var $require_admin=0;

	var $require_module=array("adherent","categorie");
	var $picto='user';
	var $db;


	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	function __construct($db)
	{
		$this->db=$db;
	}


	/**
	 *  This is the main function that returns the array of emails.
	 *
	 *  @param	int		$mailing_id    	Id of mailing. No need to use it.
	 *  @param  array	$filtersarray   If you used the formFilter function. Empty otherwise.
	 *  @return int           			<0 if error, number of emails added if ok
	 */
	function add_to_target($mailing_id,$filtersarray=array())
	{
		global $conf, $langs;
		$langs->load("members");
		$langs->load("companies");

		$cibles = array();

		// Select the members from category
		$sql = "SELECT a.rowid as id, a.email as email, a.lastname, null as fk_contact, a.firstname,";
		$sql.= " a.datefin, a.civility as civility_id, a.login, a.societe,";	// Other fields
		if ($_POST['filter']) $sql.= " c.label";
		else $sql.=" null as label";
		$sql.= " FROM ".MAIN_DB_PREFIX."adherent as a";
		if ($_POST['filter'])
		{
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."categorie_member as cm ON cm.fk_member = a.rowid";
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."categorie as c ON c.rowid = cm.fk_categorie";
		}
		$sql.= " WHERE a.email != ''";
		$sql.= " AND a.entity = ".$conf->entity;
		if ($_POST['filter']) $sql.= " AND c.rowid='".$_POST['filter']."'";
		$sql.= " ORDER BY a.email";

		// Stocke destinataires dans cibles
		$result=$this->db->query($sql);
		if ($result)
		{
			$num = $this->db->num_rows($result);
			$i = 0;
			$j = 0;

			dol_syslog(get_class($this)."::add_to_target mailing ".$num." targets found");

			$old = '';
			while ($i < $num)
			{
				$obj = $this->db->fetch_object($result);
				if ($old <> $obj->email)
				{
					$cibles[$j] = array(
                    			'email' => $obj->email,
                    			'fk_contact' => $obj->fk_contact,
                    			'lastname' => $obj->lastname,
                    			'firstname' => $obj->firstname,
                    			'other' =>
                                ($langs->transnoentities("Login").'='.$obj->login).';'.
                                ($langs->transnoentities("UserTitle").'='.($obj->civility_id?$langs->transnoentities("Civility".$obj->civility_id):'')).';'.
                                ($langs->transnoentities("DateEnd").'='.dol_print_date($this->db->jdate($obj->datefin),'day')).';'.
                                ($langs->transnoentities("Company").'='.$obj->societe).';'.
								($obj->label?$langs->transnoentities("Category").'='.$obj->label:''),
                                'source_url' => $this->url($obj->id),
                                'source_id' => $obj->id,
                                'source_type' => 'member'
					);
					$old = $obj->email;
					$j++;
				}

				$i++;
			}
		}
		else
		{
			dol_syslog($this->db->error());
			$this->error=$this->db->error();
			return -1;
		}

		return parent::add_to_target($mailing_id, $cibles);
	}


    /**
	 *	On the main mailing area, there is a box with statistics.
	 *	If you want to add a line in this report you must provide an
	 *	array of SQL request that returns two field:
	 *	One called "label", One called "nb".
	 *
	 *	@return		array		Array with SQL requests
	 */
	function getSqlArrayForStats()
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
	 *  @param		string	$sql		Requete sql de comptage
	 *	@return		int					Nb of recipients
	 */
	function getNbOfRecipients($sql='')
	{
		global $conf;

		$sql = "SELECT count(distinct(a.email)) as nb";
		$sql.= " FROM ".MAIN_DB_PREFIX."adherent as a";
		$sql.= " WHERE a.email != ''";
		$sql.= " AND a.entity = ".$conf->entity;

		// La requete doit retourner un champ "nb" pour etre comprise
		// par parent::getNbOfRecipients
		return parent::getNbOfRecipients($sql);
	}

	/**
	 *  This is to add a form filter to provide variant of selector
	 *	If used, the HTML select must be called "filter"
	 *
	 *  @return     string      A html select zone
	 */
	function formFilter()
	{
		global $conf, $langs;

		$langs->load("companies");
        $langs->load("categories");

		$s='';
		$s.='<select name="filter" class="flat">';

		// Show categories
		$sql = "SELECT rowid, label, type, visible";
		$sql.= " FROM ".MAIN_DB_PREFIX."categorie";
		$sql.= " WHERE type = 3";	// We keep only categories for members
		// $sql.= " AND visible > 0";	// We ignore the property visible because member's categories does not use this property (only products categories use it).
		$sql.= " AND entity = ".$conf->entity;
		$sql.= " ORDER BY label";

		//print $sql;
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);

			$s.='<option value="0">&nbsp;</option>';
			if (! $num) $s.='<option value="0" disabled>'.$langs->trans("NoCategoriesDefined").'</option>';

			$i = 0;
			while ($i < $num)
			{
				$obj = $this->db->fetch_object($resql);

				$s.='<option value="'.$obj->rowid.'">'.dol_trunc($obj->label,38,'middle');
				$s.='</option>';
				$i++;
			}
		}
		else
		{
			dol_print_error($this->db);
		}

		$s.='</select>';
		return $s;

	}


	/**
	 *   Can include an URL link on each record provided by selector shown on target page.
	 *
	 *   @param		int			$id		Id of member
	 *   @return    string      		Url link
	 */
	function url($id)
	{
		return '<a href="'.DOL_URL_ROOT.'/adherents/card.php?rowid='.$id.'">'.img_object('',"user").'</a>';
	}

}

