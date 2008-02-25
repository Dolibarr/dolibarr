<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
        \defgroup   contrat     Module contrat
        \brief      Module pour gerer la tenue de contrat de services
		\version	$Id$
*/

/**
        \file       htdocs/includes/modules/modContrat.class.php
        \ingroup    contrat
        \brief      Fichier de description et activation du module Contrat
*/

include_once(DOL_DOCUMENT_ROOT ."/includes/modules/DolibarrModules.class.php");


/**
        \class      modContrat
        \brief      Classe de description et activation du module Contrat
*/

class modContrat extends DolibarrModules
{

    /**
     *   \brief      Constructeur. Definit les noms, constantes et boites
     *   \param      DB      handler d'acc�s base
     */
    function modContrat($DB)
    {
        $this->db = $DB ;
        $this->numero = 54 ;

        $this->family = "crm";
        $this->name = "Contrats";
        $this->description = "Gestion des contrats de services";

        $this->revision = explode(' ','$Revision$');
        $this->version = $this->revision[1];

        $this->const_name = 'MAIN_MODULE_CONTRAT';
        $this->special = 0;
        $this->picto='contract';

        // Dir
        $this->dirs = array();

        // D�pendances
        $this->depends = array("modService");
        $this->requiredby = array();

        // Constantes
        $this->const = array();

        // Boites
        $this->boxes = array();

        // Permissions
        $this->rights = array();
        $this->rights_class = 'contrat';

        $this->rights[1][0] = 161;
        $this->rights[1][1] = 'Lire les contrats';
        $this->rights[1][2] = 'r';
        $this->rights[1][3] = 1;
        $this->rights[1][4] = 'lire';

        $this->rights[2][0] = 162;
        $this->rights[2][1] = 'Creer / modifier les contrats';
        $this->rights[2][2] = 'w';
        $this->rights[2][3] = 0;
        $this->rights[2][4] = 'creer';

        $this->rights[3][0] = 163;
        $this->rights[3][1] = 'Activer un service d\'un contrat';
        $this->rights[3][2] = 'w';
        $this->rights[3][3] = 0;
        $this->rights[3][4] = 'activer';

        $this->rights[4][0] = 164;
        $this->rights[4][1] = 'Desactiver un service d\'un contrat';
        $this->rights[4][2] = 'w';
        $this->rights[4][3] = 0;
        $this->rights[4][4] = 'desactiver';

        $this->rights[5][0] = 165;
        $this->rights[5][1] = 'Supprimer un contrat';
        $this->rights[5][2] = 'd';
        $this->rights[5][3] = 0;
        $this->rights[5][4] = 'supprimer';

    }


    /**
     *   \brief      Fonction appel�e lors de l'activation du module. Ins�re en base les constantes, boites, permissions du module.
     *               D�finit �galement les r�pertoires de donn�es � cr�er pour ce module.
     */
    function init()
    {
        global $conf;
        
        // Nettoyage avant activation
        $this->remove();

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
