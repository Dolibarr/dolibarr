<?php
/* Copyright (C) 2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 */

/**
   \defgroup   editeur     Module editeur
   \brief      Module pour g�rer le suivi des editeurs
*/

/**
   \file       htdocs/includes/modules/modEditeur.class.php
   \ingroup    editeur
   \brief      Fichier de description et activation du module Editeur
*/

include_once(DOL_DOCUMENT_ROOT ."/includes/modules/DolibarrModules.class.php");


/**
   \class      modEditeur
   \brief      Classe de description et activation du module Editeur
*/

class modEditeur extends DolibarrModules
{
  
  /**
   *   \brief      Constructeur. Definit les noms, constantes et boites
   *   \param      DB      handler d'acc�s base
   */
  function modEditeur($DB)
  {
    $this->db = $DB ;
    $this->numero = 49 ;
    
    $this->family = "other";
    $this->name = "Editeur";
    $this->description = "Gestion des �diteurs";
    $this->revision = explode(' ','$Revision$');
    $this->version = $this->revision[1];
    $this->const_name = 'MAIN_MODULE_EDITEUR';
    $this->special = 3;
    //$this->picto='editeur';

    // Dir
    $this->dirs = array();

    // Config pages
    $this->config_page_url = array("editeur.php");

    // D�pendances
    $this->depends = array();
    $this->requiredby = array();
    $this->conflictwith = array();
    $this->needleftmenu = array('default.php');
    $this->needtotopmenu = array();
    $this->langfiles = array("orders","bills","companies");

    // Constantes

    $this->const = array();

    // Boites
    $this->boxes = array();

    // Documents
    $this->docs = array();
    $this->docs[0][0] = 1;
    $this->docs[0][1] = 'Courrier des droits';
    $this->docs[0][2] = 'docs/class/courrier-droit-editeur.class.php';
    $this->docs[0][3] = 'pdf_courrier_droit_editeur';

    // Permissions
    $this->rights = array();

  }


  /**
   *   \brief      Fonction appel�e lors de l'activation du module. Ins�re en base les constantes, boites, permissions du module.
   *               D�finit �galement les r�pertoires de donn�es � cr�er pour ce module.
   */
  function init()
  {
    global $conf;
    
    $sql = array();
    
    return $this->_init($sql);
  }
  
  
  /**
   *    \brief      Fonction appel�e lors de la d�sactivation d'un module.
   *                Supprime de la base les constantes, boites et permissions du module.
   */
  function remove()
  {
    $sql = array();
	
    return $this->_remove($sql);
  }
}
?>
