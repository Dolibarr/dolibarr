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
 *
 * $Id$
 * $Source$
 *
 */

/**
        \file       htdocs/cactioncomm.class.php
        \ingroup    commercial
        \brief      Fichier de la classe des types d'actions commerciales
        \version    $Revision$
*/


/**     \class      CActioncomm
	    \brief      Classe permettant la gestion des différents types d'actions commerciales
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
   *    \param      DB          Handler d'accès base de donnée
   */
  function CActionComm($DB)
    {
      $this->db = $DB;
    }

  /**
   *    \brief      Charge l'objet type d'action depuis la base
   *    \param      id          id ou code du type d'action à récupérer
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
	
		dolibarr_syslog("CActionComm::liste_array sql=".$sql);
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

  
	/*
	*    \brief      Renvoie le nom sous forme d'un libellé traduit d'un type d'action
	*    \param      id          id ou code du type d'action
	*    \return     string      libelle du type d'action
	*/
	function getNom()
	{
		global $langs;

		// Check if translation available
		$transcode=$langs->trans("Action".$this->code);
		if ($transcode != "Action".$this->code) return $transcode;

		// Check if available in cach
		if (isset($this->type_actions[$this->code]))
		{
			// Si valeur disponible en cache
			return $this->type_actions[$this->code];
		}

		// If no translation and no cache available
		$sql = 'SELECT id, code, libelle';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'c_actioncomm';
		if (is_numeric($id)) $sql.= " WHERE id=".$this->code;
		else $sql.= " WHERE code='".$this->code."'";

		dolibarr_syslog("CActionComm::getNom sql=".$sql);
		$result = $this->db->query($sql);
		if ($result)
		{
			if ($this->db->num_rows($result))
			{
				$obj = $this->db->fetch_object($result);

				$transcode=$langs->trans("Action".$obj->code);
				$libelle=($transcode!="Action".$obj->code?$transcode:$obj->libelle);

				$this->type_actions[$obj->code]=$libelle;		// Put in cache
				return $libelle;
			}
			$this->db->free($result);
		}
		else
		{
			dolibarr_print_error($db);
		}
	}

}    
?>
