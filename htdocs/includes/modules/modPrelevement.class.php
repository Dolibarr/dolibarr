<?php
/* Copyright (C) 2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

/*!     \defgroup   prelevement     Module prelevement
        \brief      Module de gestion des prélèvements bancaires
*/

/*!
        \file       htdocs/includes/modules/modPrelevement.class.php
        \ingroup    prelevement
        \brief      Fichier de description et activation du module Prelevement
*/

include_once "DolibarrModules.class.php";

/*! \class modPrelevement
		\brief      Classe de description et activation du module Prelevement
*/

class modPrelevement extends DolibarrModules
{

  /** Initialisation de l'objet
   *
   *
   */

  function modPrelevement($DB)
  {
    $this->db = $DB ;
    $this->numero = 57 ;

    $this->family = "technic";
    $this->name = "Prelevement";
    $this->description = "Gestion des Prélèvements (experimental)";
    $this->const_name = "MAIN_MODULE_PRELEVEMENT";
    $this->const_config = MAIN_MODULE_PRELEVEMENT;

    $this->data_directory = DOL_DATA_ROOT . "/prelevement/bon/";

    // Dépendances
    $this->depends = array();
    $this->requiredby = array();

    $this->const = array();
    $this->boxes = array();
  }
  /** initialisation du module
   *
   *
   *
   */

  function init()
  {
    /*
     * Permissions
     */    
    $this->remove();
    $sql = array(
		 "INSERT INTO ".MAIN_DB_PREFIX."rights_def VALUES (150,'Tous les droits sur les prélèvements','prelevement','a',0);",
		 "INSERT INTO ".MAIN_DB_PREFIX."rights_def VALUES (151,'Consulter les prelevement','prélèvements','r',0);",
		 "INSERT INTO ".MAIN_DB_PREFIX."rights_def VALUES (152,'Configurer les prelevement','prélèvements','w',0);");
    
    /*
     * Documents
     *
     */
    if (defined("DOL_DATA_ROOT"))
	{
	  $dir[0] = DOL_DATA_ROOT . "/prelevement/" ;
	  $dir[1] = DOL_DATA_ROOT . "/prelevement/bon" ;
	  
	  for ($i = 0 ; $i < sizeof($dir) ; $i++)
	    {
	      if (is_dir($dir[$i]))
		{
		  dolibarr_syslog ("Le dossier '".$dir[$i]."' existe");
		}
	      else
		{
		  if (! @mkdir($dir[$i], 0755))
		    {
		      print "<tr><td>Impossible de créer : ".$dir[$i]."</td><td bgcolor=\"red\">Erreur</td></tr>";
		      dolibarr_syslog ("Impossible de créer '".$dir[$i]);
		      $error++;
		    }
		  else
		    {
		      dolibarr_syslog ("Le dossier '".$dir[$i]."' a ete créé");
		    }
		}
	    }
	}


    return $this->_init($sql);



  }
  /** suppression du module
   *
   *
   */
  function remove()
  {
    $sql = array("DELETE FROM ".MAIN_DB_PREFIX."rights_def WHERE module = 'prelevement';");

    return $this->_remove($sql);
  }
}
?>
