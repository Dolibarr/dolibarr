<?php
/* Copyright (C) 2003-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2005-2010 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2010-2011 Herve Prot           <herve.prot@symeos.com>
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

/**     \defgroup   facture     Module invoices
 *      \brief      Module pour gerer les factures clients et/ou fournisseurs
 *		\version	$Id: modFacture.class.php,v 1.110 2010/11/03 17:06:19 hregis Exp $
 */


/**
 *      \file       htdocs/includes/modules/modFacture.class.php
 *		\ingroup    facture
 *		\brief      Fichier de la classe de description et activation du module Facture
 */
include_once(DOL_DOCUMENT_ROOT ."/includes/modules/DolibarrModules.class.php");


/**
 *      \class      modFacture
 *      \brief      Classe de description et activation du module Facture
 */
class modGenfact extends DolibarrModules
{

	/**
	 *   \brief      Constructeur. Definit les noms, constantes et boites
	 *   \param      DB      Database handler
	 */
	function modGenfact($DB)
	{
		global $conf;

		$this->db = $DB;
		$this->numero = 460;

		$this->family = "financial";
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i','',get_class($this));
		$this->description = "Generation automatique des factures";

		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = 'experimental';

		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->special = 0;
		$this->picto='bill';
                $this->moddir="genfact"; //directory for module in htdocs

		// Data directories to create when module is enabled
		$this->dirs = array("/genfact/temp");

		// Dependencies
		$this->depends = array("modFacture");
		$this->requiredby = array();
		$this->conflictwith = array();
		$this->langfiles = array();

		// Config pages
		$this->config_page_url = array();
                
                // Boites
                //-------
                $this->boxes = array();
        
                // Constants
                $this->const = array();

		$r=0;

		// Add here entries to declare new menus
		// Example to declare the Top Menu entry:
		$this->menu[$r]=array(	'fk_menu'=>0,			// Put 0 if this is a top menu
					'type'=>'top',			// This is a Top menu entry
					'titre'=>'Generation',
					'mainmenu'=>'genfact',
					'leftmenu'=>'1',		// Use 1 if you also want to add left menu entries using this descriptor.
					'url'=>'/genfact/main.php',
					'langs'=>'@genfact',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
					'position'=>100,
					'enabled'=>'1',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
					'perms'=>'$user->rights->genfact->write',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
					'target'=>'',
					'user'=>2);				// 0=Menu for internal users, 1=external users, 2=both
		$r++;
		//
		// Example to declare a Left Menu entry:
		 $this->menu[$r]=array(	'fk_menu'=>'r=0',		// Use r=value where r is index key used for the parent menu entry (higher parent must be a top menu entry)
					'type'=>'left',			// This is a Left menu entry
					'titre'=>'Generation des factures',
					'mainmenu'=>'genfact',
					'url'=>'/genfact/genfact.php?action=list',
					'langs'=>'@genfact',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
					'position'=>100,
					'enabled'=>'1',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
					'perms'=>'$user->rights->genfact->write',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
					'target'=>'',
					'user'=>2);				// 0=Menu for internal users, 1=external users, 2=both
		 $r++;

                // Example to declare a Left Menu entry:
                 $this->menu[$r]=array( 'fk_menu'=>'r=0',               // Use r=value where r is index key used for the parent menu entry (higher parent must be a top menu entry)
                                        'type'=>'left',                 // This is a Left menu entry
                                        'titre'=>'Validation et envoi des factures',
                                        'mainmenu'=>'genfact',
                                        'url'=>'/generation/sendfact.php?action=list',
                                        'langs'=>'genfact',       // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
                                        'position'=>100,
                                        'enabled'=>'1',                 // Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
                                        'perms'=>'$user->rights->genfact->write',                   // Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
                                        'target'=>'',
                                        'user'=>2);
		
                 $r++;

                // Example to declare a Left Menu entry:
                 $this->menu[$r]=array( 'fk_menu'=>'r=0',               // Use r=value where r is index key used for the parent menu entry (higher parent must be a top menu entry)
                                        'type'=>'left',                 // This is a Left menu entry
                                        'titre'=>'Generation des prélèvements',
                                        'mainmenu'=>'genfact',
                                        'url'=>'/genfact/genprev.php?action=list',
                                        'langs'=>'genfactn',       // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
                                        'position'=>100,
                                        'enabled'=>'1',                 // Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
                                        'perms'=>'$user->rights->genfact->write',                   // Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
                                        'target'=>'',
                                        'user'=>2);
                 
                 
                 // Permissions
        //------------
        $this->rights = array();
        $this->rights_class = 'genfact';
        $r=0;

        // $this->rights[$r][0]     Id permission (unique tous modules confondus)
        // $this->rights[$r][1]     Libelle par defaut si traduction de cle "PermissionXXX" non trouvee (XXX = Id permission)
        // $this->rights[$r][2]     Non utilise
        // $this->rights[$r][3]     1=Permis par defaut, 0=Non permis par defaut
        // $this->rights[$r][4]     Niveau 1 pour nommer permission dans code
        // $this->rights[$r][5]     Niveau 2 pour nommer permission dans code

        $this->rights[$r][0] = 461;
        $this->rights[$r][1] = 'Generation automatique factures';
        $this->rights[$r][2] = 'w';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'write';
                
	}


	/**
	 *  Fonction appelee lors de l'activation du module. Insere en base les constantes, boites, permissions du module.
	 *  Definit egalement les repertoires de donnees a creer pour ce module.
	 *	@param		options		Options when enabling module
	 */
	function init($options='')
	{
		global $conf;

		// Remove permissions and default values
		$this->remove($options);

		$sql = array(
			 
		);

		return $this->_init($sql,$options);
	}

	/**
	 *  Fonction appelee lors de la desactivation d'un module.
	 *  Supprime de la base les constantes, boites et permissions du module.
	 *	@param		options		Options when disabling module
	 */
	function remove($options='')
	{
		$sql = array();

		return $this->_remove($sql,$options);
	}
}
?>
