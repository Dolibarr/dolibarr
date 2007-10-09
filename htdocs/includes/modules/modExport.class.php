<?php
/* Copyright (C) 2005-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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

/**     \defgroup   export      Module export
        \brief      Module générique pour réaliser des exports de données en base
*/

/**
        \file       htdocs/includes/modules/modExport.class.php
        \ingroup    export
        \brief      Fichier de description et activation du module export
*/

include_once(DOL_DOCUMENT_ROOT ."/includes/modules/DolibarrModules.class.php");


/**     \class      modExport
		\brief      Classe de description et activation du module export
*/

class modExport extends DolibarrModules
{

   /**
    *   \brief      Constructeur. Definit les noms, constantes et boites
    *   \param      DB      handler d'accès base
    */
  function modExport($DB)
  {
    $this->db = $DB ;
    $this->id = 'export';   // Same value xxx than in file modXxx.class.php file
    $this->numero = 240;

    $this->family = "technic";
    $this->name = "Exports";
    $this->description = "Outils d'exports de données Dolibarr (via un assistant)";
    $this->version = 'dolibarr';                        // 'experimental' or 'dolibarr' or version
    $this->const_name = 'MAIN_MODULE_EXPORT';
    $this->special = 0;
    $this->picto='';

    // Dir
    $this->dirs = array();

    // Config pages
    $this->config_page_url = array();

    // Dépendances
    $this->depends = array();
    $this->requiredby = array();
    $this->phpmin = array(4,2,0);
    $this->phpmax = array();

    // Constantes
    $this->const = array();

    // Boxes
    $this->boxes = array();

    // Permissions
    $this->rights = array();
    $this->rights_class = 'export';
    
    $this->rights[1][0] = 1201;
    $this->rights[1][1] = 'Lire les exports';
    $this->rights[1][2] = 'r';
    $this->rights[1][3] = 1;
    $this->rights[1][4] = 'lire';

    $this->rights[2][0] = 1202;
    $this->rights[2][1] = 'Créer/modifier un export';
    $this->rights[2][2] = 'w';
    $this->rights[2][3] = 0;
    $this->rights[2][4] = 'creer';
    
  }

   /**
    *   \brief      Fonction appelée lors de l'activation du module. Insère en base les constantes, boites, permissions du module.
    *               Définit également les répertoires de données à créer pour ce module.
    */
  function init()
  {
    $sql = array();
		
    return $this->_init($sql);
  }

  /**
   *    \brief      Fonction appelée lors de la désactivation d'un module.
   *                Supprime de la base les constantes, boites et permissions du module.
   */
  function remove()
  {
    $sql = array();

    return $this->_remove($sql);
  }
}
?>
