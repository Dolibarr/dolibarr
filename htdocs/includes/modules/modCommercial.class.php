<?php
/* Copyright (C) 2003-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 */

/**     \defgroup   commercial     Module commercial
        \brief      Module pour gerer les fonctions commerciales
		\version	$Id$
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
    *   \param      DB      handler d'acc�s base
    */
    function modCommercial($DB)
    {
        $this->db = $DB ;
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
        
        // D�pendances
        $this->depends = array("modSociete");
        $this->requiredby = array("modPropale","modContrat","modCommande","modFicheinter");
        
        // Constantes
        $this->const = array();
        
        // Boxes
        $this->boxes = array();
        $this->boxes[0][0] = "Derniers clients";
        $this->boxes[0][1] = "box_clients.php";
        
        $this->boxes[1][0] = "Derniers prospects enregistr�s";
        $this->boxes[1][1] = "box_prospect.php";
        
        // Permissions
        $this->rights = array();
        $this->rights_class = 'commercial';
        $r = 1;
        
        // 261 : Permission generale
        $this->rights[$r][0] = 261;
        $this->rights[$r][1] = 'Consulter informations commerciales';
        $this->rights[$r][2] = 'r';
        $this->rights[$r][3] = 1;
        $this->rights[$r][4] = 'main';
        $this->rights[$r][5] = 'lire';
        $r++;
        
        // 262 : Resteindre l'acces des commerciaux
        $this->rights[$r][0] = 262;
        $this->rights[$r][1] = 'Consulter tous les tiers par utilisateurs internes (sinon uniquement si contact commercial). Non effectif pour utilisateurs externes (tjs limités à eux-meme).';
        $this->rights[$r][2] = 'r';
        $this->rights[$r][3] = 1;
        $this->rights[$r][4] = 'client';
        $this->rights[$r][5] = 'voir';
        $r++;
    }

    /**
     *  \brief      Fonction appel�e lors de l'activation du module. Ins�re en base les constantes, boites, permissions du module.
     *              D�finit �galement les r�pertoires de donn�es � cr�er pour ce module.
     */
    function init()
    {
        // Permissions
        $this->remove();
        
        $sql = array();

        return $this->_init($sql);
    }
	
    /**
     *  \brief      Fonction appel�e lors de la d�sactivation d'un module.
     *              Supprime de la base les constantes, boites et permissions du module.
     */
    function remove()
    {
        $sql = array();
        
        return $this->_remove($sql);
    }
}
?>
