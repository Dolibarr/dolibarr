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
 *	Class to offer a selector of emailing targets from contacts
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
		$statssql[0].= " WHERE c.entity IN (".getEntity('societe').")";
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
		$sql.= " WHERE c.entity IN (".getEntity('societe').")";
		$sql.= " AND c.email != ''"; // Note that null != '' is false
		$sql.= " AND c.no_email = 0";
		$sql.= " AND c.statut = 1";

		// The request must return a field called "nb" to be understandable by parent::getNbOfRecipients
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
		$langs->load("categories");

		$s='';

		// Add filter on job position
		$sql = "SELECT sp.poste, count(distinct(sp.email)) AS nb";
		$sql.= " FROM ".MAIN_DB_PREFIX."socpeople as sp";
		$sql.= " WHERE sp.entity IN (".getEntity('societe').")";
		/*$sql.= " AND sp.email != ''";    // Note that null != '' is false
		 $sql.= " AND sp.no_email = 0";
		 $sql.= " AND sp.statut = 1";*/
		$sql.= " AND (sp.poste IS NOT NULL AND sp.poste != '')";
		$sql.= " GROUP BY sp.poste";
		$sql.= " ORDER BY sp.poste";
		$resql = $this->db->query($sql);

		$s.=$langs->trans("PostOrFunction").': ';
		$s.='<select name="filter_jobposition" class="flat">';
		$s.='<option value="all">&nbsp;</option>';
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num)
			{
				$obj = $this->db->fetch_object($resql);
				$s.='<option value="'.dol_escape_htmltag($obj->poste).'">'.dol_escape_htmltag($obj->poste).' ('.$obj->nb.')</option>';
				$i++;
			}
		}
		else dol_print_error($this->db);
		$s.='</select>';

		$s.=' ';

		// Filter on contact category
		$s .= $langs->trans("ContactCategoriesShort").': ';
		$sql = "SELECT c.label, count(distinct(sp.email)) AS nb";
		$sql.= " FROM ";
		$sql.= " ".MAIN_DB_PREFIX."socpeople as sp,";
		$sql.= " ".MAIN_DB_PREFIX."categorie as c,";
		$sql.= " ".MAIN_DB_PREFIX."categorie_contact as cs";
		$sql.= " WHERE sp.statut = 1";     // Note that null != '' is false
		//$sql.= " AND sp.no_email = 0";
		//$sql.= " AND sp.email != ''";
		//$sql.= " AND sp.entity IN (".getEntity('societe').")";
		$sql.= " AND cs.fk_categorie = c.rowid";
		$sql.= " AND cs.fk_socpeople = sp.rowid";
		$sql.= " GROUP BY c.label";
		$sql.= " ORDER BY c.label";
		$resql = $this->db->query($sql);

		$s.='<select name="filter_category" class="flat">';
		$s.='<option value="all">&nbsp;</option>';
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			if ($num)
			{
				$i = 0;
				while ($i < $num)
				{
					$obj = $this->db->fetch_object($resql);
					$s.='<option value="'.$obj->label.'">'.$obj->label.' ('.$obj->nb.')</option>';
					$i++;
				}
			}
			else
			{
				$s.='<option value="-1" disabled="disabled">'.$langs->trans("NoContactWithCategoryFound").'</option>';
			}
		}
		else dol_print_error($this->db);
		$s.='</select>';

		$s.='<br>';

		// Add prospect of a particular level
		$s.=$langs->trans("NatureOfThirdParty").': ';
		$s.='<select name="filter" class="flat">';
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
		else dol_print_error($this->db);
		$s.='<option value="customers">'.$langs->trans("ThirdPartyCustomers").'</option>';
		//$s.='<option value="customersidprof">'.$langs->trans("ThirdPartyCustomersWithIdProf12",$langs->trans("ProfId1"),$langs->trans("ProfId2")).'</option>';
		$s.='<option value="suppliers">'.$langs->trans("ThirdPartySuppliers").'</option>';
		$s.='</select>';

		$s.= ' ';

		// Filter on thirdparty category
		$s .= $langs->trans("CustomersProspectsCategoriesShort").': ';
		$sql = "SELECT c.label, count(distinct(sp.email)) AS nb";
		$sql.= " FROM ";
		$sql.= " ".MAIN_DB_PREFIX."socpeople as sp,";
		$sql.= " ".MAIN_DB_PREFIX."categorie as c,";
		$sql.= " ".MAIN_DB_PREFIX."categorie_societe as cs";
		$sql.= " WHERE sp.statut = 1";     // Note that null != '' is false
		//$sql.= " AND sp.no_email = 0";
		//$sql.= " AND sp.email != ''";
		//$sql.= " AND sp.entity IN (".getEntity('societe').")";
		$sql.= " AND cs.fk_categorie = c.rowid";
		$sql.= " AND cs.fk_soc = sp.fk_soc";
		$sql.= " GROUP BY c.label";
		$sql.= " ORDER BY c.label";
		$resql = $this->db->query($sql);

		$s.='<select name="filter_category_customer" class="flat">';
		$s.='<option value="all">&nbsp;</option>';
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			if ($num)
			{
				$i = 0;
				while ($i < $num)
				{
					$obj = $this->db->fetch_object($resql);
					$s.='<option value="'.$obj->label.'">'.$obj->label.' ('.$obj->nb.')</option>';
					$i++;
				}
			}
			else
			{
				$s.='<option value="-1" disabled="disabled">'.$langs->trans("NoContactLinkedToThirdpartieWithCategoryFound").'</option>';
			}
		}
		else dol_print_error($this->db);
		$s.='</select>';

		$s.= ' ';

		// Filter on thirdparty category
		$s .= $langs->trans("SuppliersCategoriesShort").': ';
		$sql = "SELECT c.label, count(distinct(sp.email)) AS nb";
		$sql.= " FROM ";
		$sql.= " ".MAIN_DB_PREFIX."socpeople as sp,";
		$sql.= " ".MAIN_DB_PREFIX."categorie as c,";
		$sql.= " ".MAIN_DB_PREFIX."categorie_fournisseur as cs";
		$sql.= " WHERE sp.statut = 1";     // Note that null != '' is false
		//$sql.= " AND sp.no_email = 0";
		//$sql.= " AND sp.email != ''";
		//$sql.= " AND sp.entity IN (".getEntity('societe').")";
		$sql.= " AND cs.fk_categorie = c.rowid";
		$sql.= " AND cs.fk_soc = sp.fk_soc";
		$sql.= " GROUP BY c.label";
		$sql.= " ORDER BY c.label";
		$resql = $this->db->query($sql);

		$s.='<select name="filter_category_supplier" class="flat">';
		$s.='<option value="all">&nbsp;</option>';
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			if ($num)
			{
				$i = 0;
				while ($i < $num)
				{
					$obj = $this->db->fetch_object($resql);
					$s.='<option value="'.$obj->label.'">'.$obj->label.' ('.$obj->nb.')</option>';
					$i++;
				}
			}
			else
			{
				$s.='<option value="-1" disabled="disabled">'.$langs->trans("NoContactLinkedToThirdpartieWithCategoryFound").'</option>';
			}
		}
		else dol_print_error($this->db);
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
	 *  @param  array	$filtersarray   Optional filter data (deprecated)
	 *  @return int           			<0 si erreur, nb ajout si ok
	 */
	function add_to_target($mailing_id,$filtersarray=array())
	{
		global $conf, $langs;

		$filter = GETPOST('filter','alpha');
		$filter_jobposition = GETPOST('filter_jobposition','alpha');
		$filter_category = GETPOST('filter_category','alpha');
		$filter_category_customer = GETPOST('filter_category_customer','alpha');
		$filter_category_supplier = GETPOST('filter_category_supplier','alpha');

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

		// Request must return: id, email, fk_contact, lastname, firstname, other
		$sql = "SELECT sp.rowid as id, sp.email as email, sp.rowid as fk_contact, sp.lastname, sp.firstname, sp.civility as civility_id, sp.poste as jobposition,";
		$sql.= " s.nom as companyname";
		$sql.= " FROM ".MAIN_DB_PREFIX."socpeople as sp";
    	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = sp.fk_soc";
    	if ($filter_category <> 'all') $sql.= ", ".MAIN_DB_PREFIX."categorie as c";
    	if ($filter_category <> 'all') $sql.= ", ".MAIN_DB_PREFIX."categorie_contact as cs";
    	if ($filter_category_customer <> 'all') $sql.= ", ".MAIN_DB_PREFIX."categorie as c2";
    	if ($filter_category_customer <> 'all') $sql.= ", ".MAIN_DB_PREFIX."categorie_societe as c2s";
    	if ($filter_category_supplier <> 'all') $sql.= ", ".MAIN_DB_PREFIX."categorie as c3";
    	if ($filter_category_supplier <> 'all') $sql.= ", ".MAIN_DB_PREFIX."categorie_fournisseur as c3s";
    	$sql.= " WHERE sp.entity IN (".getEntity('societe').")";
		$sql.= " AND sp.email <> ''";
		$sql.= " AND sp.no_email = 0";
		$sql.= " AND sp.statut = 1";
		$sql.= " AND sp.email NOT IN (SELECT email FROM ".MAIN_DB_PREFIX."mailing_cibles WHERE fk_mailing=".$mailing_id.")";
		// Filter on category
		if ($filter_category <> 'all') $sql.= " AND cs.fk_categorie = c.rowid AND cs.fk_socpeople = sp.rowid";
    	if ($filter_category <> 'all') $sql.= " AND c.label = '".$this->db->escape($filter_category)."'";
		if ($filter_category_customer <> 'all') $sql.= " AND c2s.fk_categorie = c2.rowid AND c2s.fk_soc = sp.fk_soc";
    	if ($filter_category_customer <> 'all') $sql.= " AND c2.label = '".$this->db->escape($filter_category_customer)."'";
		if ($filter_category_supplier <> 'all') $sql.= " AND c3s.fk_categorie = c3.rowid AND c3s.fk_soc = sp.fk_soc";
    	if ($filter_category_supplier <> 'all') $sql.= " AND c3.label = '".$this->db->escape($filter_category_supplier)."'";
    	// Filter on nature
		$key = $filter;
		{
			//print "xx".$key;
			if ($key == 'prospects') $sql.= " AND s.client=2";
			foreach($prospectlevel as $codelevel=>$valuelevel) if ($key == 'prospectslevel'.$codelevel) $sql.= " AND s.fk_prospectlevel='".$codelevel."'";
			if ($key == 'customers') $sql.= " AND s.client=1";
			if ($key == 'suppliers') $sql.= " AND s.fournisseur=1";
		}
		// Filter on job position
		$key = $filter_jobposition;
		if (! empty($key) && $key != 'all') $sql.= " AND sp.poste ='".$this->db->escape($key)."'";
		$sql.= " ORDER BY sp.email";
		//print "wwwwwwx".$sql;

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
                                ($langs->transnoentities("UserTitle").'='.($obj->civility_id?$langs->transnoentities("Civility".$obj->civility_id):'')).';'.
                                ($langs->transnoentities("JobPosition").'='.$obj->jobposition),
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

