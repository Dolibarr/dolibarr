<?php
/* Copyright (C) 2002-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
        \file       htdocs/cactioncomm.class.php
        \ingroup    commercial
        \brief      Fichier de la classe des types d'actions commerciales
        \version    $Id$
*/


/**     \class      CActioncomm
	    \brief      Classe permettant la gestion des diff�rents types d'actions commerciales
*/

class CActionComm {
  var $db;

  var $id;

  var $code;
  var $type;
  var $libelle;
  var $active;

  var $error;
  
  var $type_actions=array();
  

  /**
   *    \brief      Constructeur
   *    \param      DB          Handler d'acc�s base de donn�e
   */
  function CActionComm($DB)
    {
      $this->db = $DB;
    }

  /**
   *    \brief      Charge l'objet type d'action depuis la base
   *    \param      id          id ou code du type d'action � r�cup�rer
   *    \return     int         1=ok, 0=aucune action, -1=erreur
   */
	function fetch($id)
    {
        
        $sql = "SELECT id, code, type, libelle, active";
        $sql.= " FROM ".MAIN_DB_PREFIX."c_actioncomm";
		if (is_numeric($id)) $sql.= " WHERE id=".$id;
		else $sql.= " WHERE code='".$id."'";
        
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);
        
                $this->id      = $obj->id;
                $this->code    = $obj->code;
                $this->type    = $obj->type;
                $this->libelle = $obj->libelle;
                $this->active  = $obj->active;
        
                return 1;
            }
            else
            {
                return 0;
            }

            $this->db->free($resql);
        }
        else
        {
            $this->error=$this->db->error();
            return -1;
        }
    }

	/*
	*    \brief      Renvoi la liste des types d'actions existant
	*    \param      active      1 ou 0 pour un filtre sur l'etat actif ou non ('' par defaut = pas de filtre)
	*    \return     array       Tableau des types d'actions actifs si ok, <0 si erreur
	*/
	function liste_array($active='',$idorcode='id')
	{
		global $langs,$conf;
		$langs->load("commercial");
	
		$repid = array();
		$repcode = array();
		
		$sql = "SELECT id, code, libelle, module";
		$sql.= " FROM ".MAIN_DB_PREFIX."c_actioncomm";
		if ($active != '')
		{
			$sql.=" WHERE active=".$active;
		}
	
		dol_syslog("CActionComm::liste_array sql=".$sql);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$nump = $this->db->num_rows($resql);
			if ($nump)
			{
				$i = 0;
				while ($i < $nump)
				{
					$obj = $this->db->fetch_object($resql);
					$qualified=1;
					if ($obj->module)
					{
						if ($obj->module == 'invoice' && ! $conf->facture->enabled)	 $qualified=0;
						if ($obj->module == 'order'   && ! $conf->commande->enabled) $qualified=0;
						if ($obj->module == 'propal'  && ! $conf->propal->enabled)	 $qualified=0;
					}
					if ($qualified)
					{
						$transcode=$langs->trans("Action".$obj->code);
						$repid[$obj->id] = ($transcode!="Action".$obj->code?$transcode:$obj->libelle);
						$repcode[$obj->code] = ($transcode!="Action".$obj->code?$transcode:$obj->libelle);
					}
					$i++;
				}
			}
			if ($idorcode == 'id') $this->liste_array=$repid;
			if ($idorcode == 'code') $this->liste_array=$repcode;
			return $this->liste_array;
		}
		else
		{
			return -1;
		}
	}

  
	/**
	*   \brief      Renvoie le nom sous forme d'un libelle traduit d'un type d'action
	*	\param		withpicto		0=Pas de picto, 1=Inclut le picto dans le lien, 2=Picto seul
	*	\param		option			Sur quoi pointe le lien
	*   \return     string      	Libelle du type d'action
	*/
	function getNomUrl($withpicto=0)
	{
		global $langs;

		// Check if translation available
		$transcode=$langs->trans("Action".$this->code);
		if ($transcode != "Action".$this->code) return $transcode;
	}

}    
?>
