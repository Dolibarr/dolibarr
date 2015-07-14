<?php
/* Copyright (C) 2003-2004	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2004		Sebastien Di Cintio		<sdicintio@ressource-toi.org>
 * Copyright (C) 2004		Benoit Mortier			<benoit.mortier@opensides.be>
 * Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@capnetworks.com>
 * Copyright (C) 2012		Juanjo Menent			<jmenent@2byte.es>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\defgroup   askpricesupplier     Module askpricesupplier
 *	\brief      Module to request supplier price proposals
 *	\file       htdocs/core/modules/modAskPriceSupplier.class.php
 *	\ingroup    askpricesupplier
 *	\brief      File to describe and activate module AskPriceSupplier
 */
include_once DOL_DOCUMENT_ROOT .'/core/modules/DolibarrModules.class.php';


/**
 *	Classe de description et activation du module AskPriceSupllier
 */
class modAskPriceSupplier extends DolibarrModules
{

	/**
	 *   Constructor. Define names, constants, directories, boxes, permissions
	 *
	 *   @param      DoliDB		$db      Database handler
	 */
	function __construct($db)
	{
		global $conf;

		$this->db = $db;
		$this->numero = 1120;

		$this->family = "products";
		$this->name = preg_replace('/^mod/i','',get_class($this));
		$this->description = "askpricesupplierDESC";

		$this->version = 'experimental';

		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->special = 0;
		$this->picto='askpricesupplier';

		$this->dirs = array();

		// Dependancies
		$this->depends = array('modFournisseur');
		$this->requiredby = array();
		$this->config_page_url = array("askpricesupplier.php");
		$this->langfiles = array("askpricesupplier");

		// Constants
		$this->const = array();
		$r=0;

		$this->const[$r][0] = "ASKPRICESUPPLIER_ADDON_PDF";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "aurore";
		$this->const[$r][3] = 'Name of submodule to generate PDF for supplier quotation request';
		$this->const[$r][4] = 0;
		$r++;

		$this->const[$r][0] = "ASKPRICESUPPLIER_ADDON";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "mod_askpricesupplier_marbre";
		$this->const[$r][3] = 'Name of submodule to number supplier quotation request';
		$this->const[$r][4] = 0;
		$r++;

		$this->const[$r][0] = "ASKPRICESUPPLIER_ADDON_PDF_ODT_PATH";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "DOL_DATA_ROOT/doctemplates/askpricesupplier";
		$this->const[$r][3] = "";
		$this->const[$r][4] = 0;

		// Boxes
		$this->boxes = array();

		// Permissions
		$this->rights = array();
		$this->rights_class = 'askpricesupplier';
		$r=0;

		$r++;
		$this->rights[$r][0] = $this->numero + $r; // id de la permission
		$this->rights[$r][1] = 'Read supplier proposals'; // libelle de la permission
		$this->rights[$r][3] = 1; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'lire';

		$r++;
		$this->rights[$r][0] = $this->numero + $r; // id de la permission
		$this->rights[$r][1] = 'Create/modify supplier proposals'; // libelle de la permission
		$this->rights[$r][3] = 1; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'creer';

		$r++;
		$this->rights[$r][0] = $this->numero + $r; // id de la permission
		$this->rights[$r][1] = 'Validate supplier proposals'; // libelle de la permission
		$this->rights[$r][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = '';
		$this->rights[$r][5] = 'validate';

		$r++;
		$this->rights[$r][0] = $this->numero + $r; // id de la permission
		$this->rights[$r][1] = 'Envoyer les demandes fournisseurs'; // libelle de la permission
		$this->rights[$r][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = '';
        $this->rights[$r][5] = 'send_advance';

		$r++;
		$this->rights[$r][0] = $this->numero + $r; // id de la permission
		$this->rights[$r][1] = 'Delete supplier proposals'; // libelle de la permission
		$this->rights[$r][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'supprimer';

		$r++;
		$this->rights[$r][0] = $this->numero + $r; // id de la permission
		$this->rights[$r][1] = 'Close supplier price requests'; // libelle de la permission
		$this->rights[$r][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'cloturer';

 		// Main menu entries
		$this->menu = array();			// List of menus to add
		$r=0;
		$this->menu[$r]=array(
			'fk_menu'=>'fk_mainmenu=commercial',		    // Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',			                // This is a Left menu entry
			'titre'=>'askpricesupplierMENU_LEFT_TITLE',
			'leftmenu'=>'askpricesuppliersubmenu',
			'url'=>'/comm/askpricesupplier/index.php',
			'langs'=>'askpricesupplier',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'enabled'=>'$conf->askpricesupplier->enabled',  // Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
			'perms'=>'$user->rights->askpricesupplier->lire',	// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
			'user'=>2, // 0=Menu for internal users, 1=external users, 2=both
            'position'=>30
		);
		$r++;

		$this->menu[$r]=array(
			'fk_menu'=>'fk_mainmenu=commercial,fk_leftmenu=askpricesuppliersubmenu',
			'type'=>'left',
			'titre'=>'askpricesupplierMENU_LEFT_TITLE_NEW',
			'url'=>'/comm/askpricesupplier/card.php?action=create',
			'langs'=>'askpricesupplier',
			'enabled'=>'$conf->askpricesupplier->enabled',
			'perms'=>'$user->rights->askpricesupplier->creer',
			'user'=>2,
            'position'=>31
		);
		$r++;

		$this->menu[$r]=array(
			'fk_menu'=>'fk_mainmenu=commercial,fk_leftmenu=askpricesuppliersubmenu',
			'type'=>'left',
			'titre'=>'askpricesupplierMENU_LEFT_TITLE_LIST',
			'url'=>'/comm/askpricesupplier/list.php',
			'langs'=>'askpricesupplier',
			'enabled'=>'$conf->askpricesupplier->enabled',
			'perms'=>'$user->rights->askpricesupplier->lire',
			'user'=>2,
            'position'=>32
		);
		$r++;
	}


	/**
	 *		Function called when module is enabled.
	 *		The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *		It also creates data directories
	 *
     *      @param      string	$options    Options when enabling module ('', 'noboxes')
	 *      @return     int             	1 if OK, 0 if KO
	 */
	function init($options='')
	{
		global $conf,$langs;

		// Remove permissions and default values
		$this->remove($options);

		//ODT template
		$src=DOL_DOCUMENT_ROOT.'/install/doctemplates/askpricesupplier/template_askpricesupplier.odt';
		$dirodt=DOL_DATA_ROOT.'/doctemplates/askpricesupplier';
		$dest=$dirodt.'/template_askpricesupplier.odt';

		if (file_exists($src) && ! file_exists($dest))
		{
			require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
			dol_mkdir($dirodt);
			$result=dol_copy($src,$dest,0,0);
			if ($result < 0)
			{
				$langs->load("errors");
				$this->error=$langs->trans('ErrorFailToCopyFile',$src,$dest);
				return 0;
			}
		}

		$sql = array(
				"DELETE FROM ".MAIN_DB_PREFIX."document_model WHERE nom = '".$this->const[0][2]."' AND entity = ".$conf->entity,
				"INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type, entity) VALUES('".$this->const[0][2]."','askpricesupplier',".$conf->entity.")",
		);

		return $this->_init($sql, $options);
	}
}