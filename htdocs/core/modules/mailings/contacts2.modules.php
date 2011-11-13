<?php
/* Copyright (C) 2011 François Cerbelle <francois@cerbelle.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 *    	\file       htdocs/core/modules/mailings/contacts2.modules.php
 *    	\ingroup    mailing
 *    	\brief      Provides a list of recipients for mailing module
 */

include_once DOL_DOCUMENT_ROOT.'/core/modules/mailings/modules_mailings.php';


/**
 * 	    \class      mailing_contacts2
 * 		\brief      Class to manage a list of personalised recipients for mailing feature
 */
class mailing_contacts2 extends MailingTargets
{
    var $name='ContactsByFunction';
    // This label is used if no translation is found for key MailingModuleDescXXX where XXX=name is found
    var $desc='Add contacts by function';
    var $require_admin=0;

    var $require_module=array();
    var $picto='contact';
    var $db;


	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
    function mailing_contacts2($db)
    {
        $this->db=$db;
    }


    /**
     *      \brief      Renvoie url lien vers fiche de la source du destinataire du mailing
     *      \return     string      Url lien
     */
    function url($id)
    {
        return '<a href="'.DOL_URL_ROOT.'/contact/fiche.php?id='.$id.'">'.img_object('',"contact").'</a>';
    }

    /**
     *    This is the main function that returns the array of emails
     *    @param      mailing_id    Id of mailing. No need to use it.
     *    @param      filterarray   Function
     *    @return     int           <0 if error, number of emails added if ok
     */
    function add_to_target($mailing_id,$filtersarray=array())
    {
    	global $conf,$langs;

    	$target = array();

        // La requete doit retourner: id, email, fk_contact, name, firstname, other
        $sql = "SELECT sp.rowid as id, sp.email as email, sp.rowid as fk_contact,";
        $sql.= " sp.name as name, sp.firstname as firstname, sp.civilite,";
        $sql.= " s.nom as companyname";
    	$sql.= " FROM ".MAIN_DB_PREFIX."socpeople as sp,";
        $sql.= " ".MAIN_DB_PREFIX."societe as s";
        $sql.= " WHERE s.rowid = sp.fk_soc";
    	$sql.= " AND sp.email != ''";  // Note that null != '' is false
    	//$sql.= " AND sp.poste != ''";
    	$sql.= " AND sp.entity = ".$conf->entity;
    	if ($filtersarray[0]<>'all') $sql.= " AND sp.poste ='".$filtersarray[0]."'";
    	$sql.= " ORDER BY sp.name, sp.firstname";
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
                            'name' => $obj->name,
                            'firstname' => $obj->firstname,
                            'other' =>
                                ($langs->transnoentities("ThirdParty").'='.$obj->companyname).';'.
                                ($langs->transnoentities("Civility").'='.($obj->civilite?$langs->transnoentities("Civility".$obj->civilite):'')),
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
		global $conf;

		$statssql=array();
		/*for ($i=0; $i<5; $i++) {
            $statssql[$i] = "SELECT sp.poste as label";
            $statssql[$i].= ", count(distinct(sp.email)) as nb";
            $statssql[$i].= " FROM ".MAIN_DB_PREFIX."socpeople as sp,";
            $statssql[$i].= " ".MAIN_DB_PREFIX."societe as s";
            $statssql[$i].= " WHERE s.rowid = sp.fk_soc";
            $statssql[$i].= " AND sp.email != ''";  // Note that null != '' is false
            $statssql[$i].= " AND (sp.poste IS NOT NULL AND sp.poste != '')";
            $statssql[$i].= " AND sp.entity = ".$conf->entity;
            $statssql[$i].= " GROUP BY label";
            $statssql[$i].= " ORDER BY nb DESC";
            $statssql[$i].= " LIMIT $i,1";
		}*/

		return $statssql;
	}


    /**
     * 		Return here number of distinct emails returned by your selector.
     * 		@return		int
     */
    function getNbOfRecipients()
    {
    	global $conf;

        // We must report here number of contacts when absolutely no filter selected (so all contacts).
        // Number with a filter are show in the combo list for each filter.
        // If we want a filter "a position is defined", we must add it into formFilter
    	$sql = "SELECT count(distinct(sp.email)) as nb";
    	$sql.= " FROM ".MAIN_DB_PREFIX."socpeople as sp,";
        $sql.= " ".MAIN_DB_PREFIX."societe as s";
        $sql.= " WHERE s.rowid = sp.fk_soc";
        $sql.= " AND s.entity = ".$conf->entity;
        $sql.= " AND sp.entity = ".$conf->entity;
    	$sql.= " AND sp.email != ''";  // Note that null != '' is false
    	//$sql.= " AND sp.poste != ''";
    	// La requete doit retourner un champ "nb" pour etre comprise
    	// par parent::getNbOfRecipients
    	return parent::getNbOfRecipients($sql);
    }

    /**
     *      This is to add a form filter to provide variant of selector
     *		If used, the HTML select must be called "filter"
     *      @return     string      A html select zone
     */
    function formFilter()
    {
    	global $conf, $langs;

    	$langs->load("companies");

        $sql = "SELECT sp.poste, count(distinct(sp.email)) AS nb";
        $sql.= " FROM ".MAIN_DB_PREFIX."socpeople as sp,";
        $sql.= " ".MAIN_DB_PREFIX."societe as s";
        $sql.= " WHERE s.rowid = sp.fk_soc";
        $sql.= " AND sp.entity = ".$conf->entity;
        $sql.= " AND sp.email != ''";    // Note that null != '' is false
        $sql.= " AND (sp.poste IS NOT NULL AND sp.poste != '')";
        $sql.= " GROUP BY sp.poste";
        $sql.= " ORDER BY sp.poste";

        $resql = $this->db->query($sql);

        $s='';
        $s.='<select name="filter" class="flat">';
        $s.='<option value="all"></option>';
        if ($resql)
        {
            $num = $this->db->num_rows($resql);
            $i = 0;
            while ($i < $num)
            {
                $obj = $this->db->fetch_object($resql);
                $s.='<option value="'.$obj->poste.'">'.$obj->poste.' ('.$obj->nb.')</option>';
                $i++;
            }
        }
        $s.='</select>';
        return $s;
    }

}

?>