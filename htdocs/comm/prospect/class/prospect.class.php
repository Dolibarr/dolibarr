<?php
/* Copyright (C) 2004		Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006-2012	Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin        <regis.houssin@capnetworks.com>
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
 */

/**
 *   	\file       htdocs/comm/prospect/class/prospect.class.php
 *		\ingroup    societe
 *		\brief      Fichier de la classe des prospects
 */
include_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';


/**
 *      \class      Prospect
 *		\brief      Classe permettant la gestion des prospects
 */
class Prospect extends Societe
{
    var $db;


    /**
     *	Constructor
     *
     *	@param	DoliDB	$db		Databas handler
     */
    function __construct($db)
    {
        global $config;

        $this->db = $db;

        return 0;
    }


    /**
     *  Charge indicateurs this->nb de tableau de bord
     *
     *  @return     int         <0 if KO, >0 if OK
     */
    function load_state_board()
    {
        global $conf, $user;

        $this->nb=array("customers" => 0,"prospects" => 0);
        $clause = "WHERE";

        $sql = "SELECT count(s.rowid) as nb, s.client";
        $sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
        if (!$user->rights->societe->client->voir && !$user->societe_id)
        {
        	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON s.rowid = sc.fk_soc";
        	$sql.= " WHERE sc.fk_user = " .$user->id;
        	$clause = "AND";
        }
        $sql.= " ".$clause." s.client IN (1,2,3)";
        $sql.= " AND s.entity IN (".getEntity($this->element, 1).")";
        $sql.= " GROUP BY s.client";

        $resql=$this->db->query($sql);
        if ($resql)
        {
            while ($obj=$this->db->fetch_object($resql))
            {
                if ($obj->client == 1 || $obj->client == 3) $this->nb["customers"]+=$obj->nb;
                if ($obj->client == 2 || $obj->client == 3) $this->nb["prospects"]+=$obj->nb;
            }
            return 1;
        }
        else
        {
            dol_print_error($this->db);
            $this->error=$this->db->error();
            return -1;
        }
    }


	/**
	 *  Return status of prospect
	 *
	 *  @param	int		$mode       0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long
	 *  @return string        		Libelle
	 */
	function getLibProspStatut($mode=0)
	{
		return $this->LibProspStatut($this->stcomm_id,$mode);
	}

	/**
	 *  Return label of a given status
	 *
	 *  @param	int		$statut        	Id statut
	 *  @param  int		$mode          	0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto
	 *  @return string        			Libelle du statut
	 */
	function LibProspStatut($statut,$mode=0)
	{
		global $langs;
		$langs->load('customers');

		if ($mode == 2)
		{
			if ($statut == -1) return img_action($langs->trans("StatusProspect-1"),-1).' '.$langs->trans("StatusProspect-1");
			if ($statut ==  0) return img_action($langs->trans("StatusProspect0"), 0).' '.$langs->trans("StatusProspect0");
			if ($statut ==  1) return img_action($langs->trans("StatusProspect1"), 1).' '.$langs->trans("StatusProspect1");
			if ($statut ==  2) return img_action($langs->trans("StatusProspect2"), 2).' '.$langs->trans("StatusProspect2");
			if ($statut ==  3) return img_action($langs->trans("StatusProspect3"), 3).' '.$langs->trans("StatusProspect3");
		}
		if ($mode == 3)
		{
			if ($statut == -1) return img_action($langs->trans("StatusProspect-1"),-1);
			if ($statut ==  0) return img_action($langs->trans("StatusProspect0"), 0);
			if ($statut ==  1) return img_action($langs->trans("StatusProspect1"), 1);
			if ($statut ==  2) return img_action($langs->trans("StatusProspect2"), 2);
			if ($statut ==  3) return img_action($langs->trans("StatusProspect3"), 3);
		}
		if ($mode == 4)
		{
			if ($statut == -1) return img_action($langs->trans("StatusProspect-1"),-1).' '.$langs->trans("StatusProspect-1");
			if ($statut ==  0) return img_action($langs->trans("StatusProspect0"), 0).' '.$langs->trans("StatusProspect0");
			if ($statut ==  1) return img_action($langs->trans("StatusProspect1"), 1).' '.$langs->trans("StatusProspect1");
			if ($statut ==  2) return img_action($langs->trans("StatusProspect2"), 2).' '.$langs->trans("StatusProspect2");
			if ($statut ==  3) return img_action($langs->trans("StatusProspect3"), 3).' '.$langs->trans("StatusProspect3");
		}

		return "Error, mode/status not found";
	}

	/**
	 *	Renvoi le libelle du niveau
	 *
	 *  @return     string        Libelle
	 */
	function getLibProspLevel()
	{
		return $this->LibProspLevel($this->fk_prospectlevel);
	}

	/**
	 *  Renvoi le libelle du niveau
	 *
	 *  @param	int		$fk_prospectlevel   	Prospect level
	 *  @return string        					Libelle du niveau
	 */
	function LibProspLevel($fk_prospectlevel)
	{
		global $langs;

		$lib=$langs->trans("ProspectLevel".$fk_prospectlevel);
		// If lib not found in language file, we get label from cache/databse
		if ($lib == $langs->trans("ProspectLevel".$fk_prospectlevel))
		{
			$lib=$langs->getLabelFromKey($this->db,$fk_prospectlevel,'c_prospectlevel','code','label');
		}
		return $lib;
	}
}
?>
