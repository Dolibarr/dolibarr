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
	var $name='ContactsCategories';
	var $desc="Third parties (by categories)";
	var $require_admin=0;

	var $require_module=array("societe");	// This module allows to select by categories must be also enabled if category module is not activated
	var $picto='company';
	var $db;


	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	function __construct($db)
	{
		global $conf;

		$this->db=$db;
	}


	/**
	 *    This is the main function that returns the array of emails
	 *
	 *    @param	int		$mailing_id    	Id of mailing. No need to use it.
	 *    @param	array	$filtersarray   If you used the formFilter function. Empty otherwise.
	 *    @return   int 					<0 if error, number of emails added if ok
	 */
	function add_to_target($mailing_id,$filtersarray=array())
	{
		global $conf, $langs;

		$cibles = array();

		// Select the third parties from category
		if (empty($_POST['filter']))
		{
		    $sql = "SELECT s.rowid as id, s.email as email, s.nom as name, null as fk_contact, null as firstname, null as label";
		    $sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
		    $sql.= " WHERE s.email <> ''";
		    $sql.= " AND s.entity IN (".getEntity('societe', 1).")";
		    $sql.= " AND s.email NOT IN (SELECT email FROM ".MAIN_DB_PREFIX."mailing_cibles WHERE fk_mailing=".$mailing_id.")";
		}
		else
		{
		    $sql = "SELECT s.rowid as id, s.email as email, s.nom as name, null as fk_contact, null as firstname, c.label as label";
		    $sql.= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."categorie_societe as cs, ".MAIN_DB_PREFIX."categorie as c";
		    $sql.= " WHERE s.email <> ''";
		    $sql.= " AND s.entity IN (".getEntity('societe', 1).")";
		    $sql.= " AND s.email NOT IN (SELECT email FROM ".MAIN_DB_PREFIX."mailing_cibles WHERE fk_mailing=".$mailing_id.")";
		    $sql.= " AND cs.fk_societe = s.rowid";
		    $sql.= " AND c.rowid = cs.fk_categorie";
		    $sql.= " AND c.rowid='".$this->db->escape($_POST['filter'])."'";
		    $sql.= " UNION ";
		    $sql.= "SELECT s.rowid as id, s.email as email, s.nom as name, null as fk_contact, null as firstname, c.label as label";
		    $sql.= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."categorie_fournisseur as cs, ".MAIN_DB_PREFIX."categorie as c";
		    $sql.= " WHERE s.email <> ''";
		    $sql.= " AND s.entity IN (".getEntity('societe', 1).")";
		    $sql.= " AND s.email NOT IN (SELECT email FROM ".MAIN_DB_PREFIX."mailing_cibles WHERE fk_mailing=".$mailing_id.")";
		    $sql.= " AND cs.fk_societe = s.rowid";
		    $sql.= " AND c.rowid = cs.fk_categorie";
		    $sql.= " AND c.rowid='".$this->db->escape($_POST['filter'])."'";
		}
		$sql.= " ORDER BY email";

		// Stock recipients emails into targets table
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
                    			'other' => ($obj->label?$langs->transnoentities("Category").'='.$obj->label:''),
                                'source_url' => $this->url($obj->id),
                                'source_id' => $obj->id,
                                'source_type' => 'thirdparty'
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
	 *  @param      string	$sql        Requete sql de comptage
	 *	@return		int			Nb of recipients
	 */
	function getNbOfRecipients($sql='')
	{
		global $conf;

		$sql = "SELECT count(distinct(s.email)) as nb";
		$sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
		$sql.= " WHERE s.email != ''";
		$sql.= " AND s.entity IN (".getEntity('societe', 1).")";

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

		$s='';
		$s.='<select name="filter" class="flat">';

		// Show categories
		$sql = "SELECT rowid, label, type, visible";
		$sql.= " FROM ".MAIN_DB_PREFIX."categorie";
		$sql.= " WHERE type in (1,2)";	// We keep only categories for suppliers and customers/prospects
		// $sql.= " AND visible > 0";	// We ignore the property visible because third party's categories does not use this property (only products categories use it).
		$sql.= " AND entity = ".$conf->entity;
		$sql.= " ORDER BY label";

		//print $sql;
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);

			if (empty($conf->categorie->enabled)) $num=0;	// Force empty list if category module is not enabled

			if ($num) $s.='<option value="0">&nbsp;</option>';
			else $s.='<option value="0">'.$langs->trans("ContactsAllShort").'</option>';

			$i = 0;
			while ($i < $num)
			{
				$obj = $this->db->fetch_object($resql);

				$type='';
				if ($obj->type == 1) $type=$langs->trans("Supplier");
				if ($obj->type == 2) $type=$langs->trans("Customer");
				$s.='<option value="'.$obj->rowid.'">'.dol_trunc($obj->label,38,'middle');
				if ($type) $s.=' ('.$type.')';
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
	 *  Can include an URL link on each record provided by selector shown on target page.
	 *
     *  @param	int		$id		ID
	 *  @return string      	Url link
	 */
	function url($id)
	{
		//$companystatic=new Societe($this->db);
		//$companystatic->id=$id;
		//$companystatic->nom='';
		//return $companystatic->getNomUrl(1);	// Url too long
		return '<a href="'.DOL_URL_ROOT.'/societe/soc.php?socid='.$id.'">'.img_object('',"company").'</a>';
	}

}

?>
