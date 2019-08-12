<?php
/* Copyright (C) 2003-2004	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2004		Sebastien Di Cintio		<sdicintio@ressource-toi.org>
 * Copyright (C) 2004		Benoit Mortier			<benoit.mortier@opensides.be>
 * Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@inodbox.com>
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
 *	\defgroup   supplier_proposal     Module supplier_proposal
 *	\brief      Module to request supplier price proposals
 *
 *	\file       htdocs/core/modules/modSupplierProposal.class.php
 *	\ingroup    supplier_proposal
 *	\brief      File to describe and activate module SupplierProposal
 */
include_once DOL_DOCUMENT_ROOT .'/core/modules/DolibarrModules.class.php';


/**
 *	Class to describe and enable module SupplierProposal
 */
class modSupplierProposal extends DolibarrModules
{

	/**
	 *   Constructor. Define names, constants, directories, boxes, permissions
	 *
	 *   @param      DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		global $conf;

		$this->db = $db;
		$this->numero = 1120;

		$this->family = "srm";
		$this->module_position = '35';
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		$this->description = "supplier_proposalDESC";

		$this->version = 'dolibarr';

		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->picto='supplier_proposal';

		// Data directories to create when module is enabled.
		$this->dirs = array();

		 // Config pages. Put here list of php page names stored in admin directory used to setup module.
        $this->config_page_url = array("supplier_proposal.php");

		// Dependencies
		$this->hidden = false;			// A condition to hide module
		$this->depends = array('modFournisseur');		// List of module class names as string that must be enabled if this module is enabled
		$this->requiredby = array();	// List of module ids to disable if this one is disabled
		$this->conflictwith = array();	// List of module class names as string this module is in conflict with
		$this->phpmin = array(5,4);		// Minimum version of PHP required by module
		$this->langfiles = array("supplier_proposal");

		// Constants
		$this->const = array();
		$r=0;

		$this->const[$r][0] = "SUPPLIER_PROPOSAL_ADDON_PDF";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "aurore";
		$this->const[$r][3] = 'Name of submodule to generate PDF for supplier quotation request';
		$this->const[$r][4] = 0;
		$r++;

		$this->const[$r][0] = "SUPPLIER_PROPOSAL_ADDON";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "mod_supplier_proposal_marbre";
		$this->const[$r][3] = 'Name of submodule to number supplier quotation request';
		$this->const[$r][4] = 0;
		$r++;

		$this->const[$r][0] = "SUPPLIER_PROPOSAL_ADDON_PDF_ODT_PATH";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "DOL_DATA_ROOT/doctemplates/supplier_proposal";
		$this->const[$r][3] = "";
		$this->const[$r][4] = 0;

		// Boxes
		$this->boxes = array();

		// Permissions
		$this->rights = array();
		$this->rights_class = 'supplier_proposal';
		$r=0;

		$r++;
		$this->rights[$r][0] = $this->numero + $r; // id de la permission
		$this->rights[$r][1] = 'Read supplier proposals'; // libelle de la permission
		$this->rights[$r][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'lire';

		$r++;
		$this->rights[$r][0] = $this->numero + $r; // id de la permission
		$this->rights[$r][1] = 'Create/modify supplier proposals'; // libelle de la permission
		$this->rights[$r][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'creer';

		$r++;
		$this->rights[$r][0] = $this->numero + $r; // id de la permission
		$this->rights[$r][1] = 'Validate supplier proposals'; // libelle de la permission
		$this->rights[$r][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'validate_advance';

		$r++;
		$this->rights[$r][0] = $this->numero + $r; // id de la permission
		$this->rights[$r][1] = 'Envoyer les demandes fournisseurs'; // libelle de la permission
		$this->rights[$r][3] = 0; // La permission est-elle une permission par defaut
        $this->rights[$r][4] = 'send_advance';

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
			'titre'=>'SupplierProposalsShort',
			'leftmenu'=>'supplier_proposalsubmenu',
			'url'=>'/supplier_proposal/index.php',
			'langs'=>'supplier_proposal',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'enabled'=>'$conf->supplier_proposal->enabled',  // Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
			'perms'=>'$user->rights->supplier_proposal->lire',	// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
			'user'=>2, // 0=Menu for internal users, 1=external users, 2=both
            'position'=>300
		);
		$r++;

		$this->menu[$r]=array(
			'fk_menu'=>'fk_mainmenu=commercial,fk_leftmenu=supplier_proposalsubmenu',
			'type'=>'left',
			'titre'=>'SupplierProposalNew',
			'url'=>'/supplier_proposal/card.php?action=create&amp;leftmenu=supplier_proposals',
			'langs'=>'supplier_proposal',
			'enabled'=>'$conf->supplier_proposal->enabled',
			'perms'=>'$user->rights->supplier_proposal->creer',
			'user'=>2,
            'position'=>301
		);
		$r++;

		$this->menu[$r]=array(
			'fk_menu'=>'fk_mainmenu=commercial,fk_leftmenu=supplier_proposalsubmenu',
			'type'=>'left',
			'titre'=>'List',
			'url'=>'/supplier_proposal/list.php?leftmenu=supplier_proposals',
			'langs'=>'supplier_proposal',
			'enabled'=>'$conf->supplier_proposal->enabled',
			'perms'=>'$user->rights->supplier_proposal->lire',
			'user'=>2,
            'position'=>302
		);
		$r++;

		$this->menu[$r]=array(
		    'fk_menu'=>'fk_mainmenu=commercial,fk_leftmenu=supplier_proposalsubmenu',
		    'type'=>'left',
		    'titre'=>'Statistics',
		    'url'=>'/comm/propal/stats/index.php?leftmenu=supplier_proposals&amp;mode=supplier',
		    'langs'=>'supplier_proposal',
		    'enabled'=>'$conf->supplier_proposal->enabled',
		    'perms'=>'$user->rights->supplier_proposal->lire',
		    'user'=>2,
		    'position'=>303
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
    public function init($options = '')
	{
		global $conf,$langs;

		// Remove permissions and default values
		$this->remove($options);

		//ODT template
		$src=DOL_DOCUMENT_ROOT.'/install/doctemplates/supplier_proposal/template_supplier_proposal.odt';
		$dirodt=DOL_DATA_ROOT.'/doctemplates/supplier_proposal';
		$dest=$dirodt.'/template_supplier_proposal.odt';

		if (file_exists($src) && ! file_exists($dest))
		{
			require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
			dol_mkdir($dirodt);
			$result=dol_copy($src, $dest, 0, 0);
			if ($result < 0)
			{
				$langs->load("errors");
				$this->error=$langs->trans('ErrorFailToCopyFile', $src, $dest);
				return 0;
			}
		}

		$sql = array(
				"DELETE FROM ".MAIN_DB_PREFIX."document_model WHERE nom = '".$this->db->escape($this->const[0][2])."' AND type = 'supplier_proposal' AND entity = ".$conf->entity,
				"INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type, entity) VALUES('".$this->db->escape($this->const[0][2])."','supplier_proposal',".$conf->entity.")",
		);

		return $this->_init($sql, $options);
	}



	/**
	 * Function called when module is disabled.
	 * Remove from database constants, boxes and permissions from Dolibarr database.
	 * Data directories are not deleted
	 *
	 * @param      string	$options    Options when enabling module ('', 'noboxes')
	 * @return     int             	1 if OK, 0 if KO
	 */
	public function remove($options = '')
	{
	    $sql = array(
	        "DELETE FROM ".MAIN_DB_PREFIX."rights_def WHERE module = 'askpricesupplier'"		// To delete/clean deprecated entries
	    );

	    return $this->_remove($sql, $options);
	}
}
