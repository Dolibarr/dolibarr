<?php
/* Copyright (C) 2003-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2006 Regis Houssin        <regis.houssin@cap-networks.com>
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

/**     \defgroup   avoir     Module avoir
        \brief      Module pour gérer les avoirs clients et/ou fournisseurs
*/


/**
        \file       htdocs/includes/modules/modFacture.class.php
		\ingroup    avoir
		\brief      Fichier de la classe de description et activation du module Avoir
*/

include_once "DolibarrModules.class.php";


/**
        \class      modAvoir
        \brief      Classe de description et activation du module Avoir
*/

class modAvoir extends DolibarrModules
{

   /**
    *   \brief      Constructeur. Definit les noms, constantes et boites
    *   \param      DB      handler d'accès base
    */
    function modAvoir($DB)
    {
        $this->db = $DB ;
        $this->id = 'avoir';   // Same value xxx than in file modXxx.class.php file
        $this->numero = 30 ;
    
        $this->family = "financial";
        $this->name = "Avoir";
        $this->description = "Gestion des avoirs clients";
    
        //$this->revision = explode(' ','$Revision$');
        //$this->version = $this->revision[1];
        $this->version = 'experimental';    // 'experimental' or 'dolibarr' or version
    
        $this->const_name = 'MAIN_MODULE_AVOIR';
        $this->special = 0;
        $this->picto='bill';
    
        // Dir
        $this->dirs = array();
    
        // Dépendances
        $this->depends = array("modSociete","modComptabilite");
        $this->requiredby = array();
    
        // Config pages
        //$this->config_page_url = "avoir.php";
    
        // Constantes
        $this->const = array();
/*    
        $this->const[0][0] = "FACTURE_ADDON_PDF";
        $this->const[0][1] = "chaine";
        $this->const[0][2] = "bulot";
    
        $this->const[1][0] = "FACTURE_ADDON";
        $this->const[1][1] = "chaine";
        $this->const[1][2] = "pluton";
*/
    
        // Boites
        $this->boxes = array();
/*    
        $this->boxes[0][0] = "Avoirs";
        $this->boxes[0][1] = "box_avoirs.php";
*/
    
        // Permissions
        $this->rights = array();
        $this->rights_class = 'avoir';
		    $r=0;
		    
        $r++;
        $this->rights[$r][0] = 401;
        $this->rights[$r][1] = 'Lire les avoirs';
        $this->rights[$r][2] = 'a';
        $this->rights[$r][3] = 1;
        $this->rights[$r][4] = 'lire';
    
        $r++;
        $this->rights[$r][0] = 402;
        $this->rights[$r][1] = 'Créer/modifier les avoirs';
        $this->rights[$r][2] = 'a';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'creer';
    
        $r++;
        $this->rights[$r][0] = 403;
        $this->rights[$r][1] = 'Valider les avoirs';
        $this->rights[$r][2] = 'a';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'valider';
    
        $r++;
        $this->rights[$r][0] = 404;
        $this->rights[$r][1] = 'Supprimer les avoirs';
        $this->rights[$r][2] = 'a';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'supprimer';

    
   /**
    *   \brief      Fonction appelée lors de l'activation du module. Insère en base les constantes, boites, permissions du module.
    *               Définit également les répertoires de données à créer pour ce module.
    */
    function init()
    {
        global $conf;
    
        // Permissions
        $this->remove();
    
        // Dir
        $this->dirs[0] = $conf->avoir->dir_output;
        $this->dirs[1] = $conf->avoir->dir_images;
    
        $sql = array();
/*        
        $sql = array(
		       "DELETE FROM ".MAIN_DB_PREFIX."avoir_model_pdf WHERE nom = '".$this->const[0][2]."'",
		       "INSERT INTO ".MAIN_DB_PREFIX."avoir_model_pdf (nom) VALUES('".$this->const[0][2]."');",
		       );
*/    
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
