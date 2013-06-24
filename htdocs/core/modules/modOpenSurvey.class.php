<?php
/* Copyright (C) 2013 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * 		\defgroup   opensurvey     Module OpenSurvey
 *      \brief      Module to OpenSurvey integration.
 */

/**
 *      \file       htdocs/opensurvey/core/modules/modOpenSurvey.class.php
 *      \ingroup    opensurvey
 *      \brief      Description and activation file for module OpenSurvey
 */
include_once(DOL_DOCUMENT_ROOT ."/core/modules/DolibarrModules.class.php");


/**
 * Description and activation class for module opensurvey
 */
class modOpenSurvey extends DolibarrModules
{

	/**
	 *   Constructor. Define names, constants, directories, boxes, permissions
	 *
	 *   @param		DoliDB		$db		Database handler
	 */
	function __construct($db)
	{
		global $langs,$conf;

		$this->db = $db;

		// Id for module (must be unique).
		// Use here a free id (See in Home -> System information -> Dolibarr for list of used module id).
		$this->numero = 55000;
		// Key text used to identify module (for permission, menus, etc...)
		$this->rights_class = 'opensurvey';

		// Family can be 'crm','financial','hr','projects','product','technic','other'
		// It is used to group modules in module setup page
		$this->family = "projects";
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i','',get_class($this));
		// Module description used if translation string 'ModuleXXXDesc' not found (XXX is value MyModule)
		$this->description = "Module to integrate a survey (like Doodle, Studs, Rdvz, ...)";
		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = 'dolibarr';
		// Key used in llx_const table to save module status enabled/disabled (where MYMODULE is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		// Where to store the module in setup page (0=common,1=interface,2=others,3=very specific)
		$this->special = 0;
		// Name of image file used for this module.
		// If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
		// If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
		$this->picto='opensurvey.png@opensurvey';

		// Data directories to create when module is enabled
		$this->dirs = array();
		//$this->dirs[0] = DOL_DATA_ROOT.'/mymodule;
		//$this->dirs[1] = DOL_DATA_ROOT.'/mymodule/temp;

		// Config pages. Put here list of php page names stored in admin directory used to setup module
		$this->config_page_url = array("index.php@opensurvey");

		// Dependencies
		$this->depends = array();		// List of modules id that must be enabled if this module is enabled
		$this->requiredby = array();	// List of modules id to disable if this one is disabled
		$this->phpmin = array(4,1);					// Minimum version of PHP required by module
		$this->need_dolibarr_version = array(2,4);	// Minimum version of Dolibarr required by module

		// Constants
		$this->const = array();			// List of parameters

		// Dictionnaries
        $this->dictionnaries=array();

		// Boxes
		$this->boxes = array();			// List of boxes
		$r=0;

		// Add here list of php file(s) stored in includes/boxes that contains class to show a box.
		// Example:
		//$this->boxes[$r][1] = "myboxa.php";
		//$r++;
		//$this->boxes[$r][1] = "myboxb.php";
		//$r++;

		// Permissions
		$this->rights = array();		// Permission array used by this module
		$r=0;

		// Add here list of permission defined by an id, a label, a boolean and two constant strings.
		// Example:
		$this->rights[$r][0] = 55001; 				// Permission id (must not be already used)
		$this->rights[$r][1] = 'Read surveys';	// Permission label
		$this->rights[$r][2] = 'r'; 					// Permission by default for new user (0/1)
		$this->rights[$r][3] = 0; 					// Permission by default for new user (0/1)
		$this->rights[$r][4] = 'survey';				// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$this->rights[$r][5] = 'read';				// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$r++;

		// Add here list of permission defined by an id, a label, a boolean and two constant strings.
		// Example:
		$this->rights[$r][0] = 55002; 				// Permission id (must not be already used)
		$this->rights[$r][1] = 'Create/modify surveys';	// Permission label
		$this->rights[$r][2] = 'w'; 					// Permission by default for new user (0/1)
		$this->rights[$r][3] = 0; 					// Permission by default for new user (0/1)
		$this->rights[$r][4] = 'survey';				// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$this->rights[$r][5] = 'write';				// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$r++;


		// Main menu entries
		$this->menus = array();			// List of menus to add
		$r=0;

		$this->menu[$r]=array(	'fk_menu'=>0,		    						// Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
								'type'=>'top',
								'titre'=>'Surveys',
								'mainmenu'=>'opensurvey',
								'url'=>'/opensurvey/index.php',
								'langs'=>'opensurvey',
								'position'=>200,
                				'enabled'=>'$conf->opensurvey->enabled',         // Define condition to show or hide menu entry. Use '$conf->NewsSubmitter->enabled' if entry must be visible if module is enabled.
								'perms'=>'$user->rights->opensurvey->survey->read',
								'target'=>'',
								'user'=>0);
		$r++;

		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=opensurvey',		    // Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
								'type'=>'left',
								'titre'=>'Survey',
								'mainmenu'=>'opensurvey',
								'leftmenu'=>'opensurvey',
								'url'=>'/opensurvey/index.php?mainmenu=opensurvey&leftmenu=opensurvey',
								'langs'=>'opensurvey',
								'position'=>200,
                				'enabled'=>'$conf->opensurvey->enabled',         // Define condition to show or hide menu entry. Use '$conf->NewsSubmitter->enabled' if entry must be visible if module is enabled.
								'perms'=>'',
								'target'=>'',
								'user'=>0);
		$r++;

		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=opensurvey,fk_leftmenu=opensurvey',		    // Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
								'type'=>'left',
								'titre'=>'NewSurvey',
								'mainmenu'=>'opensurvey',
								'leftmenu'=>'opensurvey_new',
								'url'=>'/opensurvey/public/index.php?origin=dolibarr',
								'langs'=>'opensurvey',
								'position'=>210,
                				'enabled'=>'$conf->opensurvey->enabled',         // Define condition to show or hide menu entry. Use '$conf->NewsSubmitter->enabled' if entry must be visible if module is enabled.
								'perms'=>'',
								'target'=>'_blank',
								'user'=>0);
		$r++;

		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=opensurvey,fk_leftmenu=opensurvey',		    // Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
								'type'=>'left',
								'titre'=>'List',
								'mainmenu'=>'opensurvey',
								'leftmenu'=>'opensurvey_list',
								'url'=>'/opensurvey/list.php',
								'langs'=>'opensurvey',
								'position'=>220,
                				'enabled'=>'$conf->opensurvey->enabled',         // Define condition to show or hide menu entry. Use '$conf->NewsSubmitter->enabled' if entry must be visible if module is enabled.
								'perms'=>'',
								'target'=>'',
								'user'=>0);
		$r++;
	}

	/**
	 *	Function called when module is enabled.
	 *	The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *	It also creates data directories
	 *
     *  @param      string	$options    Options when enabling module ('', 'noboxes')
	 *  @return     int             	1 if OK, 0 if KO
	 */
	function init($options='')
	{
		// Permissions
		$this->remove($options);
		
		$sql = array();

		return $this->_init($sql,$options);
	}

	/**
	 *	Function called when module is disabled.
	 *  Remove from database constants, boxes and permissions from Dolibarr database.
	 *	Data directories are not deleted
	 *
     *  @param      string	$options    Options when enabling module ('', 'noboxes')
	 *  @return     int             	1 if OK, 0 if KO
	 */
	function remove($options='')
	{
		$sql = array();

		return $this->_remove($sql,$options);
	}
}

?>
