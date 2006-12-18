<?php
/* Copyright (C) 2006 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
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
 */

/**
   \file       htdocs/includes/triggers/interface_user.class.php
   \ingroup    stock
   \brief      Fichier des actions du workflow de stock utilisateurs
   \remarks    Son propre fichier d'actions peut etre créé par recopie de celui-ci:
   - Le nom du fichier doit etre interface_xxx.class.php
   - Le fichier doit rester stocké dans includes/triggers
   - Le nom de la classe doit etre InterfaceXxx
*/


/**
   \class      InterfaceUser
   \brief      Classe des fonctions triggers des actions personalisées du workflow
*/

class InterfaceUser
{
  var $db;
  var $error;
  
  /**
   *   \brief      Constructeur.
   *   \param      DB      Handler d'accès base
   */
  function InterfaceUser($DB)
  {
    $this->db = $DB ;
    
    $this->name = "User";
    $this->family = "user";
    $this->description = "Les triggers de ce composant s'appliquent sur les utilisateurs gérant des stocks.";
    $this->revision = explode(' ','$Revision$');
    $this->version = $this->revision[1];
  }
  
  /**
   *   \brief      Renvoi nom du lot de triggers
   *   \return     string      Nom du lot de triggers
   */
  function getName()
  {
    return $this->name;
  }
  
  /**
   *   \brief      Renvoi descriptif du lot de triggers
   *   \return     string      Descriptif du lot de triggers
   */
  function getDesc()
  {
    return $this->description;
  }
  
  /**
   *   \brief      Renvoi version du lot de triggers
   *   \return     string      Version du lot de triggers
   */
  function getVersion()
  {
    global $langs;
    $langs->load("admin");
    
    if ($this->version == 'experimental') return $langs->trans("Experimental");
    elseif ($this->version == 'dolibarr') return DOL_VERSION;
    elseif ($this->version) return $this->version;
    else return $langs->trans("Unknown");
  }
  
  /**
   *      \brief      Fonction appelée lors du déclenchement d'un évènement Dolibarr.
   *                  D'autres fonctions run_trigger peuvent etre présentes dans includes/triggers
   *      \param      action      Code de l'evenement
   *      \param      object      Objet concern
   *      \param      user        Objet user
   *      \param      lang        Objet lang
   *      \param      conf        Objet conf
   *      \return     int         <0 si ko, 0 si aucune action faite, >0 si ok
   */
  function run_trigger($action,$object,$user,$langs,$conf)
  { 
    // Users
    if ($action == 'USER_CREATE')
      {
	dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);

	if ($conf->global->STOCK_USERSTOCK == 1 && $conf->global->STOCK_USERSTOCK_AUTOCREATE == 1)
	  {
	    require_once(DOL_DOCUMENT_ROOT."/product/stock/entrepot.class.php");
	    $entrepot = new Entrepot($this->db);
	    $entrepot->libelle = 'Stock Personnel '.$object->nom;
	    $entrepot->description = 'Cet entrepot représente le stock personnel de '.$object->prenom.' '.$object->nom;
	    $entrepot->statut = 1;
	    $entrepot->create($user);
	  }
	
      }
    return 0;
  }  
}
?>
