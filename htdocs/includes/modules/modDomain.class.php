<?php
/* Copyright (C) 2007      Laurent Destailleur  <eldy@users.sourceforge.net>
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
        \defgroup   domain     Module domain
        \brief      Module pour gérer une base de noms de domaines
*/

/**
        \file       htdocs/includes/modules/modDomain.class.php
        \ingroup    adherent
        \brief      Fichier de description et activation du module domain
*/

include_once(DOL_DOCUMENT_ROOT ."/includes/modules/DolibarrModules.class.php");


/**
        \class      modAdherent
        \brief      Classe de description et activation du module Adherent
*/

class modDomain extends DolibarrModules
{

    /**
     *   \brief      Constructeur. Definit les noms, constantes et boites
     *   \param      DB      handler d'accès base
     */
    function modDomain($DB)
    {
        $this->db = $DB;
        $this->id = 'domain';   // Same value xxx than in file modXxx.class.php file
        $this->numero = 1300 ;
    
        $this->family = "other";
        $this->name = "Domains";
        $this->description = "Gestion d'une base de noms de domaines";
        $this->version = 'development';			// 'development' or 'experimental' or 'dolibarr' or version
        $this->const_name = 'MAIN_MODULE_DOMAIN';
        $this->special = 2;
        $this->picto='user';
    
        // Dir
        //----
        $this->dirs = array();
    
        // Config pages
        //-------------
        $this->config_page_url = array();
    
        // Dépendances
        //------------
        $this->depends = array();
        $this->requiredby = array();
        $this->langfiles = array("domains");
    
        // Constantes
        //-----------
        $this->const = array();
    
        // Boites
        //-------
        $this->boxes = array();
    
        // Permissions
        //------------
        $this->rights = array();
        $this->rights_class = 'domain';
        $r=0;

	    $r++;
	    $this->rights[$r][0] = 1301;
	    $this->rights[$r][1] = 'Read domain names';
	    $this->rights[$r][2] = 'r';
	    $this->rights[$r][3] = 1;    
	    $this->rights[$r][4] = 'read';
        
	    $r++;
	    $this->rights[$r][0] = 1302;
	    $this->rights[$r][1] = 'Create/modify domain names';
	    $this->rights[$r][2] = 'w';
	    $this->rights[$r][3] = 0;    
	    $this->rights[$r][4] = 'create';
        
	    $r++;
	    $this->rights[$r][0] = 1303;
	    $this->rights[$r][1] = 'Delete domain names';
	    $this->rights[$r][2] = 'd';
	    $this->rights[$r][3] = 0;    
	    $this->rights[$r][4] = 'delete';
        
        // Exports
        //--------
        $r=0;

        // $this->export_code[$r]          Code unique identifiant l'export (tous modules confondus)
        // $this->export_label[$r]         Libellé par défaut si traduction de clé "ExportXXX" non trouvée (XXX = Code)
        // $this->export_fields_sql[$r]    Liste des champs exportables en codif sql
        // $this->export_fields_name[$r]   Liste des champs exportables en codif traduction
        // $this->export_sql[$r]           Requete sql qui offre les données à l'export
        // $this->export_permission[$r]    Liste des codes permissions requis pour faire l'export
    }

    
    /**
     *   \brief      Fonction appelée lors de l'activation du module. Insère en base les constantes, boites, permissions du module.
     *               Définit également les répertoires de données à créer pour ce module.
     */
    function init()
    {
        global $conf;
        
        // Permissions
        $this->remove();
        
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
