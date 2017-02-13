<?php
/* Copyright (C) 2011 François Cerbelle   <francois@cerbelle.net>
 * Copyright (C) 2013 Laurent Destailleur <eldy@users.sourceforge.net>
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
 *     	\file       htdocs/core/modules/mailings/contacts3.modules.php
 *     	\ingroup    mailing
 *     	\brief      Provides a list of recipients for mailing module
 */

include_once DOL_DOCUMENT_ROOT.'/core/modules/mailings/modules_mailings.php';


/**
 *	Class to manage a list of personalised recipients for mailing feature
 */
class mailing_contacts3 extends MailingTargets
{
	var $name='ContactsByCompanyCategory';
	// This label is used if no translation is found for key XXX neither MailingModuleDescXXX where XXX=name is found
	var $desc='Add contacts by company category';
    var $require_admin=0;

    var $require_module=array();
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
     *  Renvoie url lien vers fiche de la source du destinataire du mailing
     *
     *  @param	int		$id		ID
     *  @return	string      	Url lien
     */
    function url($id)
    {
        return '<a href="'.DOL_URL_ROOT.'/contact/card.php?id='.$id.'">'.img_object('',"contact").'</a>';
    }

    /**
     *    This is the main function that returns the array of emails
     *
     *    @param	int		$mailing_id    	Id of mailing. No need to use it.
     *    @param    array	$filtersarray   Category
     *    @return   int           			<0 if error, number of emails added if ok
     */
    function add_to_target($mailing_id,$filtersarray=array())
    {
    	global $conf,$langs;

    	$target = array();

        // La requete doit retourner: id, email, fk_contact, name, firstname, other
        $sql = "SELECT sp.rowid as id, sp.email as email, sp.rowid as fk_contact,";
        $sql.= " sp.lastname, sp.firstname, sp.civility as civility_id,";
        $sql.= " s.nom as companyname";
        $sql.= " FROM ".MAIN_DB_PREFIX."socpeople as sp";
    	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = sp.fk_soc";
        if ($filtersarray[0] <> 'all') $sql.= ", ".MAIN_DB_PREFIX."categorie as c";
    	if ($filtersarray[0] <> 'all') $sql.= ", ".MAIN_DB_PREFIX."categorie_societe as cs";
    	$sql.= " WHERE sp.email <> ''";     // Note that null != '' is false
    	$sql.= " AND sp.no_email = 0";
    	$sql.= " AND sp.statut = 1";
    	$sql.= " AND sp.entity IN (".getEntity('societe', 1).")";
    	$sql.= " AND sp.email NOT IN (SELECT email FROM ".MAIN_DB_PREFIX."mailing_cibles WHERE fk_mailing=".$mailing_id.")";
    	if ($filtersarray[0] <> 'all') $sql.= " AND cs.fk_categorie = c.rowid";
    	if ($filtersarray[0] <> 'all') $sql.= " AND cs.fk_soc = sp.fk_soc";
    	if ($filtersarray[0] <> 'all') $sql.= " AND c.label = '".$this->db->escape($filtersarray[0])."'";
    	$sql.= " ORDER BY sp.lastname, sp.firstname";

    	$resql = $this->db->query($sql);
    	if ($resql)
    	{
    		$num = $this->db->num_rows($resql);
    		$i = 0;

    		while ($i < $num)
    		{
    			$obj= $this->db->fetch_object($resql);
    			$target[] = array(
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
				$i++;
			}
		}

        return parent::add_to_target($mailing_id, $target);
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
		global $conf, $langs;

		$statssql=array();
		/*for ($i=0; $i<5; $i++) {
            $statssql[$i] = "SELECT c.label, count(sp.rowid) AS nb";
            $statssql[$i].= " FROM ".MAIN_DB_PREFIX."socpeople as sp,";
            $statssql[$i].= " ".MAIN_DB_PREFIX."societe as s,";
            $statssql[$i].= " ".MAIN_DB_PREFIX."categorie as c,";
            $statssql[$i].= " ".MAIN_DB_PREFIX."categorie_societe as cs";
            $statssql[$i].= " WHERE s.rowid = sp.fk_soc";
            $statssql[$i].= " AND sp.email != ''";    // Note that null != '' is false
            $statssql[$i].= " AND sp.entity IN (".getEntity('societe', 1).")";
            $statssql[$i].= " AND cs.fk_categorie = c.rowid";
            $statssql[$i].= " AND cs.fk_soc = sp.fk_soc";
            $statssql[$i].= " GROUP BY c.label";
            $statssql[$i].= " ORDER BY nb DESC";
            $statssql[$i].= " LIMIT $i,1";
		}*/

		return $statssql;
	}


    /**
     *		Return here number of distinct emails returned by your selector.
     *
     *		@param		string	$sql		Requete sql de comptage
     *		@return		int		Number of recipients
     */
    function getNbOfRecipients($sql='')
    {
    	global $conf;

    	// We must report here number of contacts when absolutely no filter selected (so all contacts).
    	// Number with a filter are show in the combo list for each filter.
        // If we want a filter "is inside at least one category", we must add it into formFilter
    	$sql = "SELECT count(distinct(c.email)) as nb";
        $sql.= " FROM ".MAIN_DB_PREFIX."socpeople as c";
    	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = c.fk_soc";
        $sql.= " WHERE c.entity IN (".getEntity('societe', 1).")";
        $sql.= " AND c.email != ''"; // Note that null != '' is false
        $sql.= " AND c.no_email = 0";
        $sql.= " AND c.statut = 1";
        /*
    	$sql = "SELECT count(distinct(sp.email)) as nb";
        $sql.= " FROM ".MAIN_DB_PREFIX."socpeople as sp,";
        $sql.= " ".MAIN_DB_PREFIX."societe as s,";
        $sql.= " ".MAIN_DB_PREFIX."categorie as c,";
        $sql.= " ".MAIN_DB_PREFIX."categorie_societe as cs";
        $sql.= " WHERE s.rowid = sp.fk_soc";
        $sql.= " AND sp.entity IN (".getEntity('societe', 1).")";
        $sql.= " AND sp.email != ''"; // Note that null != '' is false
        $sql.= " AND cs.fk_categorie = c.rowid";
        $sql.= " AND cs.fk_soc = sp.fk_soc";
        */
    	// La requete doit retourner un champ "nb" pour etre comprise
    	// par parent::getNbOfRecipients
    	return parent::getNbOfRecipients($sql);
    }

    /**
     *      This is to add a form filter to provide variant of selector
     *		If used, the HTML select must be called "filter".
     *
     *      @return     string      A html select zone
     */
    function formFilter()
    {
    	global $conf, $langs;

    	$langs->load("companies");

        $sql = "SELECT c.label, count(distinct(sp.email)) AS nb";
        $sql.= " FROM ".MAIN_DB_PREFIX."socpeople as sp,";
        $sql.= " ".MAIN_DB_PREFIX."categorie as c,";
        $sql.= " ".MAIN_DB_PREFIX."categorie_societe as cs";
        $sql.= " WHERE sp.email != ''";     // Note that null != '' is false
        $sql.= " AND sp.no_email = 0";
        $sql.= " AND sp.statut = 1";
        $sql.= " AND sp.entity IN (".getEntity('societe', 1).")";
        $sql.= " AND cs.fk_categorie = c.rowid";
        $sql.= " AND cs.fk_soc = sp.fk_soc";
        $sql.= " GROUP BY c.label";
        $sql.= " ORDER BY c.label";

        $resql = $this->db->query($sql);

        $s='';
        $s.='<select name="filter" class="flat">';
        $s.='<option value="all"></option>';
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

}

