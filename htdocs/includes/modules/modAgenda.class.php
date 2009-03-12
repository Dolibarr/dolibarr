<?php
/* Copyright (C) 2003,2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
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

/**
        \defgroup   agenda     Module agenda
        \brief      Module pour gerer l'agenda et actions
		\brief		$Id$
*/

/**
        \file       htdocs/includes/modules/modAgenda.class.php
        \ingroup    agenda
        \brief      Fichier de description et activation du module agenda
*/

include_once(DOL_DOCUMENT_ROOT ."/includes/modules/DolibarrModules.class.php");

/**
        \class      modAdherent
        \brief      Classe de description et activation du module Adherent
*/

class modAgenda extends DolibarrModules
{

    /**
     *   \brief      Constructeur. Definit les noms, constantes et boites
     *   \param      DB      handler d'acces base
     */
    function modAgenda($DB)
    {
        $this->db = $DB;
        $this->numero = 2400;

        $this->family = "projects";
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = eregi_replace('^mod','',get_class($this));
        $this->description = "Gestion de l'agenda et des actions";
        $this->version = 'dolibarr';                        // 'experimental' or 'dolibarr' or version
		// Key used in llx_const table to save module status enabled/disabled (where MYMODULE is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
        $this->special = 0;
        $this->picto='calendar';

        // Dir
        //----
        $this->dirs = array();
		//$this->dirs[0] = DOL_DATA_ROOT.'/mymodule;
        //$this->dirs[1] = DOL_DATA_ROOT.'/mymodule/temp;

        // Config pages
        //-------------
        $this->config_page_url = array("agenda.php");

        // Dependancies
        //-------------
        $this->depends = array();
        $this->requiredby = array();
        $this->langfiles = array("companies");

        // Constantes
        //-----------
        $this->const = array();

		// New pages on tabs
		// -----------------
        $this->tabs = array();

		// Boxes
        //------
        $this->boxes = array();
        $this->boxes[0][1] = "box_actions.php";

        // Permissions
        //------------
        $this->rights = array();
        $this->rights_class = 'agenda';
        $r=0;

        // $this->rights[$r][0]     Id permission (unique tous modules confondus)
        // $this->rights[$r][1]     Libelle par defaut si traduction de cle "PermissionXXX" non trouvee (XXX = Id permission)
        // $this->rights[$r][2]     Non utilise
        // $this->rights[$r][3]     1=Permis par defaut, 0=Non permis par defaut
        // $this->rights[$r][4]     Niveau 1 pour nommer permission dans code
        // $this->rights[$r][5]     Niveau 2 pour nommer permission dans code
		// $r++;

        $this->rights[$r][0] = 2401;
        $this->rights[$r][1] = 'Read actions/tasks linked to his account';
        $this->rights[$r][2] = 'r';
        $this->rights[$r][3] = 1;
        $this->rights[$r][4] = 'myactions';
        $this->rights[$r][5] = 'read';
        $r++;

        $this->rights[$r][0] = 2402;
        $this->rights[$r][1] = 'Create/modify/delete actions/tasks linked to his account';
        $this->rights[$r][2] = 'w';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'myactions';
        $this->rights[$r][5] = 'create';
        $r++;

        $this->rights[$r][0] = 2403;
        $this->rights[$r][1] = 'Read actions/tasks of others';
        $this->rights[$r][2] = 'r';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'allactions';
        $this->rights[$r][5] = 'read';
        $r++;

        $this->rights[$r][0] = 2405;
        $this->rights[$r][1] = 'Create/modify/delete actions/tasks of others';
        $this->rights[$r][2] = 'w';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'allactions';
        $this->rights[$r][5] = 'create';
        $r++;

        // Menus
		//------
		$r=0;

		$this->menu[$r]=array('fk_menu'=>0,'type'=>'top','titre'=>'Agenda','mainmenu'=>'agenda','leftmenu'=>'0','url'=>'/comm/action/index.php','langs'=>'commercial','position'=>100,'perms'=>'$user->rights->agenda->myactions->read','target'=>'','user'=>0);
		$r++;

		// Exports
        //--------
        $r=0;

        // $this->export_code[$r]          Code unique identifiant l'export (tous modules confondus)
        // $this->export_label[$r]         Libelle par defaut si traduction de cle "ExportXXX" non trouvee (XXX = Code)
        // $this->export_permission[$r]    Liste des codes permissions requis pour faire l'export
        // $this->export_fields_sql[$r]    Liste des champs exportables en codif sql
        // $this->export_fields_name[$r]   Liste des champs exportables en codif traduction
        // $this->export_sql[$r]           Requete sql qui offre les donnees a l'export
    }


    /**
     *   \brief      Fonction appelee lors de l'activation du module. Insere en base les constantes, boites, permissions du module.
     *               Definit egalement les repertoires de donnees a creer pour ce module.
     */
    function init()
    {
        $sql = array();

        return $this->_init($sql);
    }

    /**
     *    \brief      Fonction appelee lors de la desactivation d'un module.
     *                Supprime de la base les constantes, boites et permissions du module.
     */
    function remove()
    {
		$sql = array();

		return $this->_remove($sql);
    }

}
?>
