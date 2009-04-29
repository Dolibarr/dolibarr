<?php
/* Copyright (C) 2003-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2005-2009 Regis Houssin      	<regis@dolibarr.fr>
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

/**     \defgroup   commercial     Module commercial
 *      \brief      Module pour gerer les fonctions commerciales
 *		\version	$Id$
 */

/**
        \file       htdocs/includes/modules/modCommercial.class.php
        \ingroup    commercial
        \brief      Fichier de description et activation du module Commercial
*/

include_once(DOL_DOCUMENT_ROOT ."/includes/modules/DolibarrModules.class.php");


/** 	\class 		modCommercial
 *      \brief      Classe de description et activation du module Commercial
 */

class modCommercial extends DolibarrModules
{

   /**
    *   \brief      Constructeur. Definit les noms, constantes et boites
    *   \param      DB      Database handler
    */
    function modCommercial($DB)
    {
    	$this->db = $DB ;
      $this->numero = 2 ;
        
      $this->family = "crm";
      // Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
      $this->name = eregi_replace('^mod','',get_class($this));
      $this->description = "Gestion commercial";
      
      // Possible values for version are: 'development', 'experimental', 'dolibarr' or version
      $this->version = 'dolibarr';
      
      $this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
      $this->special = 0;
      $this->picto='commercial';
      
      // Data directories to create when module is enabled
      $this->dirs = array();
      $r=0;
      
      $this->dirs[$r][0] = "output";
      $this->dirs[$r][1] = "/comm";
      
      $r++;
      $this->dirs[$r][0] = "temp";
      $this->dirs[$r][1] = "/comm/temp";
        
        // Dependancies
        $this->depends = array("modSociete");
        $this->requiredby = array("modPropale","modContrat","modCommande","modFicheinter");
        
        // Constantes
        $this->const = array();
        
        // Boxes
        $this->boxes = array();
        
        // Permissions
        $this->rights = array();
        $this->rights_class = 'commercial';
        $r = 1;
        
        // 261 : Permission generale
        $this->rights[$r][0] = 261;
        $this->rights[$r][1] = 'Consulter menu commercial';
        $this->rights[$r][2] = 'r';
        $this->rights[$r][3] = 1;
        $this->rights[$r][4] = 'main';
        $this->rights[$r][5] = 'lire';
        $r++;
    }

    /**
     *  \brief      Fonction appelee lors de l'activation du module. Insere en base les constantes, boites, permissions du module.
     *              Definit egalement les repertoires de donnees a creer pour ce module.
     */
    function init()
    {
        // Permissions
        $this->remove();
        
        $sql = array();

        return $this->_init($sql);
    }
	
    /**
     *  \brief      Fonction appelee lors de la desactivation d'un module.
     *              Supprime de la base les constantes, boites et permissions du module.
     */
    function remove()
    {
        $sql = array();
        
        return $this->_remove($sql);
    }
}
?>
