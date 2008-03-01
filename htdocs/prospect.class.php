<?php
/* Copyright (C) 2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
    	\file       htdocs/prospect.class.php
		\ingroup    societe
		\brief      Fichier de la classe des prospects
		\version    $Id$
*/


/** 
        \class      Prospect
		\brief      Classe permettant la gestion des prospects
*/

include_once(DOL_DOCUMENT_ROOT."/societe.class.php");


class Prospect extends Societe
{
    var $db;

    
    /**
     *    \brief  Constructeur de la classe
     *    \param  DB     handler acc�s base de donn�es
     *    \param  id     id societe (0 par defaut)
     */
    function Prospect($DB, $id=0)
    {
        global $config;
    
        $this->db = $DB;
        $this->id = $id;
    
        return 0;
    }

    
    /**
     *      \brief      Charge indicateurs this->nb de tableau de bord
     *      \return     int         <0 si ko, >0 si ok
     */
    function load_state_board()
    {
        global $conf, $user;
        
        $this->nb=array("customers" => 0,"prospects" => 0);
        $clause = "WHERE";

        $sql = "SELECT count(s.rowid) as nb, s.client";
        $sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
        if (!$user->rights->commercial->client->voir && !$user->societe_id)
        {
        	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON s.rowid = sc.fk_soc";
        	$sql.= " WHERE sc.fk_user = " .$user->id;
        	$clause = "AND";
        }
        $sql.= " ".$clause." s.client in (1,2)";
        $sql.= " GROUP BY s.client";
        $resql=$this->db->query($sql);
        if ($resql)
        {
            while ($obj=$this->db->fetch_object($resql))
            {
                if ($obj->client == 1) $this->nb["customers"]=$obj->nb;
                if ($obj->client == 2) $this->nb["prospects"]=$obj->nb;
            }
            return 1;
        }
        else 
        {
            dolibarr_print_error($this->db);
            $this->error=$this->db->error();
            return -1;
        }
    }

    
	/**
	 *    \brief      Retourne le libell� du statut d'une facture (brouillon, valid�e, abandonn�e, pay�e)
	 *    \param      mode          0=libell� long, 1=libell� court, 2=Picto + Libell� court, 3=Picto, 4=Picto + Libell� long
	 *    \return     string        Libelle
	 */
	function getLibStatut($mode=0)
	{
		return $this->LibStatut($this->stcomm_id,$mode);
	}

	/**
	 *    	\brief      Renvoi le libelle d'un statut donne
	 *    	\param      statut        	Id statut
	 *    	\param      mode          	0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
	 *    	\return     string        	Libelle du statut
	 */
	function LibStatut($statut,$mode=0)
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
	 *	\brief      Renvoi le libelle du niveau
	 *  \return     string        Libelle
	 */
	function getLibLevel()
	{
		return $this->LibLevel($this->fk_prospectlevel);
	}
	
	/**
	 *    	\brief      Renvoi le libelle du niveau
	 *    	\param      fk_prospectlevel   	Prospect level
	 *    	\return     string        		Libelle du niveau
	 */
	function LibLevel($fk_prospectlevel)
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
