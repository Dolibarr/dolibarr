<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@capnetworks.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 *	\file       htdocs/core/modules/mailings/contacts1.modules.php
 *	\ingroup    mailing
 *	\brief      File of class to offer a selector of emailing targets with Rule 'Poire'.
 */

include_once DOL_DOCUMENT_ROOT.'/core/modules/mailings/modules_mailings.php';


/**
 *	\class      mailing_contacts1
 *	\brief      Class to offer a selector of emailing targets with Rule 'Poire'.
 */
class mailing_contacts1 extends MailingTargets
{
	var $name='ContactCompanies';                     // Identifiant du module mailing
	// This label is used if no translation is found for key XXX neither MailingModuleDescXXX where XXX=name is found
	var $desc='Contacts of thirdparties (prospects, customers, suppliers...)';
	var $require_module=array("societe");               // Module mailing actif si modules require_module actifs
	var $require_admin=0;                               // Module mailing actif pour user admin ou non
	var $picto='contact';
	
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
	 *	On the main mailing area, there is a box with statistics.
	 *	If you want to add a line in this report you must provide an
	 *	array of SQL request that returns two field:
	 *	One called "label", One called "nb".
	 *
	 *	@return		string[]		Array with SQL requests
	 */
	function getSqlArrayForStats()
	{
		global $conf, $langs;

		$langs->load("commercial");

		$statssql=array();
		$statssql[0] = "SELECT '".$langs->trans("NbOfCompaniesContacts")."' as label,";
		$statssql[0].= " count(distinct(c.email)) as nb";
		$statssql[0].= " FROM ".MAIN_DB_PREFIX."socpeople as c";
		$statssql[0].= " WHERE c.entity IN (".getEntity('societe', 1).")";
		$statssql[0].= " AND c.email != ''";      // Note that null != '' is false
		$statssql[0].= " AND c.no_email = 0";
		$statssql[0].= " AND c.statut = 1";

		return $statssql;
	}


	/**
	 *	Return here number of distinct emails returned by your selector.
	 *	For example if this selector is used to extract 500 different
	 *	emails from a text file, this function must return 500.
	 *
	 *  @param		string	$sql		Requete sql de comptage
	 *	@return		int
	 */
	function getNbOfRecipients($sql='')
	{
		global $conf;

		$sql  = "SELECT count(distinct(c.email)) as nb";
		$sql.= " FROM ".MAIN_DB_PREFIX."socpeople as c";
    	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = c.fk_soc";
		$sql.= " WHERE c.entity IN (".getEntity('societe', 1).")";
		$sql.= " AND c.email != ''"; // Note that null != '' is false
		$sql.= " AND c.no_email = 0";
		$sql.= " AND c.statut = 1";

		// La requete doit retourner un champ "nb" pour etre comprise
		// par parent::getNbOfRecipients
		return parent::getNbOfRecipients($sql);
	}


	/**
	 *   Affiche formulaire de filtre qui apparait dans page de selection des destinataires de mailings
	 *
	 *   @return     string      Retourne zone select
	 */
	function formFilter()
	{
		global $langs;
		$langs->load("companies");
		$langs->load("commercial");
		$langs->load("suppliers");

		$s='';
		$s.='<select name="filter" class="flat">';
		// Add prospect of a particular level
		$sql = "SELECT code, label";
		$sql.= " FROM ".MAIN_DB_PREFIX."c_prospectlevel";
		$sql.= " WHERE active > 0";
		$sql.= " ORDER BY label";
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			if ($num) $s.='<option value="all">&nbsp;</option>';
			else $s.='<option value="all">'.$langs->trans("ContactsAllShort").'</option>';
            $s.='<option value="prospects">'.$langs->trans("ThirdPartyProspects").'</option>';

			$i = 0;
			while ($i < $num)
			{
				$obj = $this->db->fetch_object($resql);
				$level=$langs->trans($obj->code);
				if ($level == $obj->code) $level=$langs->trans($obj->label);
				$s.='<option value="prospectslevel'.$obj->code.'">'.$langs->trans("ThirdPartyProspects").' ('.$langs->trans("ProspectLevelShort").'='.$level.')</option>';
				$i++;
			}
		}
		$s.='<option value="customers">'.$langs->trans("ThirdPartyCustomers").'</option>';
		//$s.='<option value="customersidprof">'.$langs->trans("ThirdPartyCustomersWithIdProf12",$langs->trans("ProfId1"),$langs->trans("ProfId2")).'</option>';
		$s.='<option value="suppliers">'.$langs->trans("ThirdPartySuppliers").'</option>';
		$s.='</select>';
		return $s;
	}


	/**
	 *  Renvoie url lien vers fiche de la source du destinataire du mailing
	 *
     *  @param	int		$id		ID
	 *  @return string      	Url lien
	 */
	function url($id)
	{
		return '<a href="'.DOL_URL_ROOT.'/contact/card.php?id='.$id.'">'.img_object('',"contact").'</a>';
	}


	/**
	 *  Ajoute destinataires dans table des cibles
	 *
	 *  @param	int		$mailing_id    	Id of emailing
	 *  @param  array	$filtersarray   Requete sql de selection des destinataires
	 *  @return int           			<0 si erreur, nb ajout si ok
	 */
	function add_to_target($mailing_id,$filtersarray=array())
	{
		global $conf, $langs;

		$cibles = array();

		// List prospects levels
		$prospectlevel=array();
		$sql = "SELECT code, label";
		$sql.= " FROM ".MAIN_DB_PREFIX."c_prospectlevel";
		$sql.= " WHERE active > 0";
		$sql.= " ORDER BY label";
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num)
			{
				$obj = $this->db->fetch_object($resql);
				$prospectlevel[$obj->code]=$obj->label;
				$i++;
			}
		}
		else dol_print_error($this->db);

		// La requete doit retourner: id, email, fk_contact, name, firstname, other
		$sql = "SELECT c.rowid as id, c.email as email, c.rowid as fk_contact,";
		$sql.= " c.lastname, c.firstname, c.civility as civility_id,";
		$sql.= " s.nom as companyname";
		$sql.= " FROM ".MAIN_DB_PREFIX."socpeople as c";
    	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = c.fk_soc";
		$sql.= " WHERE c.entity IN (".getEntity('societe', 1).")";
		$sql.= " AND c.email <> ''";
		$sql.= " AND c.no_email = 0";
		$sql.= " AND c.statut = 1";
		$sql.= " AND c.email NOT IN (SELECT email FROM ".MAIN_DB_PREFIX."mailing_cibles WHERE fk_mailing=".$mailing_id.")";
		foreach($filtersarray as $key)
		{
			if ($key == 'prospects') $sql.= " AND s.client=2";
			//print "xx".$key;
			foreach($prospectlevel as $codelevel=>$valuelevel) if ($key == 'prospectslevel'.$codelevel) $sql.= " AND s.fk_prospectlevel='".$codelevel."'";
			if ($key == 'customers') $sql.= " AND s.client=1";
			if ($key == 'suppliers') $sql.= " AND s.fournisseur=1";
		}
		$sql.= " ORDER BY c.email";
		//print "x".$sql;

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
                                ($langs->transnoentities("ThirdParty").'='.$obj->companyname).';'.
                                ($langs->transnoentities("UserTitle").'='.($obj->civility_id?$langs->transnoentities("Civility".$obj->civility_id):'')),
                            'source_url' => $this->url($obj->id),
                            'source_id' => $obj->id,
                            'source_type' => 'contact'
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

}

