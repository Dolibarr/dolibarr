<?php
/* Copyright (C) 2003-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
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

/**     \defgroup   commercial     Module commercial
        \brief      Module pour gérer les fonctions commerciales
*/

/**
        \file       htdocs/includes/modules/modCommercial.class.php
        \ingroup    commercial
        \brief      Fichier de description et activation du module Commercial
*/

include_once(DOL_DOCUMENT_ROOT ."/includes/modules/DolibarrModules.class.php");


/** \class modCommercial
        \brief      Classe de description et activation du module Commercial
*/

class modCommercial extends DolibarrModules
{

   /**
    *   \brief      Constructeur. Definit les noms, constantes et boites
    *   \param      DB      handler d'accès base
    */
    function modCommercial($DB)
    {
        $this->db = $DB ;
        $this->id = 'commercial';   // Same value xxx than in file modXxx.class.php file
        $this->numero = 2 ;
        
        $this->family = "crm";
        $this->name = "Commercial";
        $this->description = "Gestion commercial";
        
        $this->revision = explode(' ','$Revision$');
        $this->version = $this->revision[1];
        
        $this->const_name = 'MAIN_MODULE_COMMERCIAL';
        $this->special = 0;
        $this->picto='commercial';
        
        // Dir
        $this->dirs = array();
        
        // Dépendances
        $this->depends = array("modSociete");
        $this->requiredby = array("modPropale","modContrat","modCommande",);
        
        // Constantes
        $this->const = array();
        
        // Boxes
        $this->boxes = array();
        $this->boxes[0][0] = "Derniers clients";
        $this->boxes[0][1] = "box_clients.php";
        
        $this->boxes[1][0] = "Derniers prospects enregistrés";
        $this->boxes[1][1] = "box_prospect.php";
        
        $this->boxes[2][0] = "Dernières actions";
        $this->boxes[2][1] = "box_actions.php";
        
        // Permissions
        $this->rights = array();
        $this->rights_class = 'commercial';
        $r = 1;
        
        // 261 : Permission générale
        $this->rights[$r][0] = 261;
        $this->rights[$r][1] = 'Consulter informations commerciales';
        $this->rights[$r][2] = 'r';
        $this->rights[$r][3] = 1;
        $this->rights[$r][4] = 'main';
        $this->rights[$r][5] = 'lire';
        $r++;
        
        // 262 : Resteindre l'accès des commerciaux
        $this->rights[$r][0] = 262;
        $this->rights[$r][1] = 'Consulter tous les clients (effectifs pour utilisateurs internes uniquement)';
        $this->rights[$r][2] = 'r';
        $this->rights[$r][3] = 1;
        $this->rights[$r][4] = 'client';
        $this->rights[$r][5] = 'voir';
        $r++;
    }

    /**
     *  \brief      Fonction appelée lors de l'activation du module. Insère en base les constantes, boites, permissions du module.
     *              Définit également les répertoires de données à créer pour ce module.
     */
    function init()
    {
        // Permissions
        $this->remove();
        
        $sql = array();

        return $this->_init($sql);
    }
	
    /**
     *  \brief      Fonction appelée lors de la désactivation d'un module.
     *              Supprime de la base les constantes, boites et permissions du module.
     */
    function remove()
    {
        $sql = array();
        
        return $this->_remove($sql);
    }
}
?>
