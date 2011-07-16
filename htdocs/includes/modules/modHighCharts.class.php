<?php
/* Copyright (C) 2003,2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *       \defgroup   member     Module foundation
 *       \brief      Module to manage members of a foundation
 */

/**
 *		\file       htdocs/includes/modules/modAdherent.class.php
 *      \ingroup    member
 *      \brief      File descriptor or module Member
 *		\version	$Id: modAdherent.class.php,v 1.76 2010/10/01 23:37:37 eldy Exp $
 */

include_once(DOL_DOCUMENT_ROOT ."/includes/modules/DolibarrModules.class.php");

/**
 *       \class      modAdherent
 *       \brief      Classe de description et activation du module Adherent
 */
class modHighCharts extends DolibarrModules
{

    /**
     *   \brief      Constructeur. Definit les noms, constantes et boites
     *   \param      DB      Database handler
     */
    function modHighCharts($DB)
    {
        $this->db = $DB;
        $this->numero = 440 ;

        $this->family = "technic";
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i','',get_class($this));
        $this->description = "Graphiques statistiques";
        $this->version = 'development';                        // 'experimental' or 'dolibarr' or version
        $this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
        $this->special = 0;
        $this->picto='stat';

        // Data directories to create when module is enabled
        $this->dirs = array("/highCharts/temp");

        // Config pages
        //-------------
        $this->config_page_url = array("highCharts.php@highCharts");

        // Dependances
        //------------
        $this->depends = array();
        $this->requiredby = array();
        $this->langfiles = array("highcharts","companies");

        // Constantes
        //-----------
        $this->const = array();
        $this->const[0]  = array("MAP_SYSTEM","texte","","openlayers");
        $this->const[1]  = array("GOOGLE_KEY","texte","","");
        

        // Boites
        //-------
        $this->boxes = array();


        // Menu
        //------------
	// None

        // Permissions
        //------------
        $this->rights = array();
        $this->rights_class = 'highcharts';
        $r=0;

        // $this->rights[$r][0]     Id permission (unique tous modules confondus)
        // $this->rights[$r][1]     Libelle par defaut si traduction de cle "PermissionXXX" non trouvee (XXX = Id permission)
        // $this->rights[$r][2]     Non utilise
        // $this->rights[$r][3]     1=Permis par defaut, 0=Non permis par defaut
        // $this->rights[$r][4]     Niveau 1 pour nommer permission dans code
        // $this->rights[$r][5]     Niveau 2 pour nommer permission dans code

        $this->rights[$r][0] = 441;
        $this->rights[$r][1] = 'Read graph';
        $this->rights[$r][2] = 'r';
        $this->rights[$r][3] = 1;
        $this->rights[$r][4] = 'read';

        $r++;
        $this->rights[$r][0] = 442;
        $this->rights[$r][1] = 'Print / export graph';
        $this->rights[$r][2] = 'w';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'export';

        $r++;
        $this->rights[$r][0] = 443;
        $this->rights[$r][1] = 'Read all';
        $this->rights[$r][2] = 'a';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'all';
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
