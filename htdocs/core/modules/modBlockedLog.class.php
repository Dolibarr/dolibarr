<?php
/* Copyright (C) 2017   Laurent Destailleur  <eldy@users.sourcefore.net>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * 	\defgroup   blockedlog   Module BlockedLog
 *  \brief      Add a log into a block chain for some actions.
 *  \file       htdocs/core/modules/modBlockedLog.class.php
 *  \ingroup    blockedlog
 *  \brief      Description and activation file for the module BlockedLog
 */
include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

/**
 *	Class to describe a BlockedLog module
 */
class modBlockedLog extends DolibarrModules
{
	/**
	 *   Constructor. Define names, constants, directories, boxes, permissions
	 *
	 *   @param      DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		global $langs, $conf, $mysoc;

		$this->db = $db;
		$this->numero = 3200;
		// Key text used to identify module (for permissions, menus, etc...)
		$this->rights_class = 'blockedlog';

		// Family can be 'crm','financial','hr','projects','products','ecm','technic','other'
		// It is used to group modules in module setup page
		$this->family = "base";
		// Module position in the family on 2 digits ('01', '10', '20', ...)
		$this->module_position = '76';
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		$this->description = "Enable a log on some business events into a non reversible log. This module may be mandatory for some countries.";
		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = 'dolibarr';
		// Key used in llx_const table to save module status enabled/disabled (where MYMODULE is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		// Name of image file used for this module.
		$this->picto = 'technic';

		// Data directories to create when module is enabled
		$this->dirs = array();

		// Config pages
		//-------------
		$this->config_page_url = array('blockedlog.php?withtab=1@blockedlog');

		// Dependancies
		//-------------
		$this->hidden = false; // A condition to disable module
		$this->depends = array('always'=>'modFacture'); // List of modules id that must be enabled if this module is enabled
		$this->requiredby = array(); // List of modules id to disable if this one is disabled
		$this->conflictwith = array(); // List of modules id this module is in conflict with
		$this->langfiles = array('blockedlog');

		$this->warnings_activation = array(); // Warning to show when we activate module. array('always'='text') or array('FR'='textfr','ES'='textes'...)
		$this->warnings_activation_ext = array(); // Warning to show when we activate an external module. array('always'='text') or array('FR'='textfr','ES'='textes'...)
		$this->warnings_unactivation = array('FR'=>'BlockedLogAreRequiredByYourCountryLegislation');

		// Currently, activation is not automatic because only companies (in France) making invoices to non business customers must
		// enable this module.
		/*if (!empty($conf->global->BLOCKEDLOG_DISABLE_NOT_ALLOWED_FOR_COUNTRY))
		{
			$tmp=explode(',', $conf->global->BLOCKEDLOG_DISABLE_NOT_ALLOWED_FOR_COUNTRY);
			$this->automatic_activation = array();
			foreach($tmp as $key)
			{
				$this->automatic_activation[$key]='BlockedLogActivatedBecauseRequiredByYourCountryLegislation';
			}
		}*/
		//var_dump($this->automatic_activation);

		$this->always_enabled = (!empty($conf->blockedlog->enabled)
			&& getDolGlobalString('BLOCKEDLOG_DISABLE_NOT_ALLOWED_FOR_COUNTRY')
			&& in_array((empty($mysoc->country_code) ? '' : $mysoc->country_code), explode(',', getDolGlobalString('BLOCKEDLOG_DISABLE_NOT_ALLOWED_FOR_COUNTRY')))
			&& $this->alreadyUsed());

		// Constants
		//-----------
		$this->const = array(
			1=>array('BLOCKEDLOG_DISABLE_NOT_ALLOWED_FOR_COUNTRY', 'chaine', 'FR', 'This is list of country code where the module may be mandatory', 0, 'current', 0)
		);

		// New pages on tabs
		// -----------------
		$this->tabs = array();

		// Boxes
		//------
		$this->boxes = array();

		// Permissions
		// -----------------
		$this->rights = array(); // Permission array used by this module

		$r = 1;
		$this->rights[$r][0] = $this->numero + $r; // Permission id (must not be already used)
		$this->rights[$r][1] = 'Read archived events and fingerprints'; // Permission label
		$this->rights[$r][3] = 0; // Permission by default for new user (0/1)
		$this->rights[$r][4] = 'read'; // In php code, permission will be checked by test if ($user->rights->mymodule->level1->level2)
		$this->rights[$r][5] = '';

		// Main menu entries
		// -----------------
		$r = 0;
		$this->menu[$r] = array(
			'fk_menu'=>'fk_mainmenu=tools', // Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'mainmenu'=>'tools',
			'leftmenu'=>'blockedlogbrowser',
			'type'=>'left', // This is a Left menu entry
			'titre'=>'BrowseBlockedLog',
			'prefix' => img_picto('', $this->picto, 'class="paddingright pictofixedwidth"'),
			'url'=>'/blockedlog/admin/blockedlog_list.php?mainmenu=tools&leftmenu=blockedlogbrowser',
			'langs'=>'blockedlog', // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>200,
			'enabled'=>'$conf->blockedlog->enabled', // Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
			'perms'=>'$user->rights->blockedlog->read', // Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>2, // 0=Menu for internal users, 1=external users, 2=both
		);
		$r++;
	}


	/**
	 * Check if module was already used before unactivation linked to warnings_unactivation property
	 *
	 * @return	boolean		True if already used, otherwise False
	 */
	public function alreadyUsed()
	{
		require_once DOL_DOCUMENT_ROOT.'/blockedlog/class/blockedlog.class.php';
		$b = new BlockedLog($this->db);
		return $b->alreadyUsed(1);
	}


	/**
	 *      Function called when module is enabled.
	 *      The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *      It also creates data directories.
	 *
	 *      @param      string	$options    Options when enabling module ('', 'noboxes')
	 *      @return     int             	1 if OK, 0 if KO
	 */
	public function init($options = '')
	{
		global $conf, $user;

		$sql = array();

		// If already used, we add an entry to show we enable module
		require_once DOL_DOCUMENT_ROOT . '/blockedlog/class/blockedlog.class.php';

		$object = new stdClass();
		$object->id = 1;
		$object->element = 'module';
		$object->ref = 'systemevent';
		$object->entity = $conf->entity;
		$object->date = dol_now();

		$b = new BlockedLog($this->db);
		$result = $b->setObjectData($object, 'MODULE_SET', 0);
		if ($result < 0) {
			$this->error = $b->error;
			$this->errors = $b->errors;
			return 0;
		}

		$res = $b->create($user);
		if ($res <= 0) {
			$this->error = $b->error;
			$this->errors = $b->errors;
			return $res;
		}

		return $this->_init($sql, $options);
	}

	/**
	 * Function called when module is disabled.
	 * The remove function removes tabs, constants, boxes, permissions and menus from Dolibarr database.
	 * Data directories are not deleted
	 *
	 * @param      string	$options    Options when enabling module ('', 'noboxes')
	 * @return     int             		1 if OK, 0 if KO
	 */
	public function remove($options = '')
	{
		global $conf, $user;

		$sql = array();

		// If already used, we add an entry to show we enable module
		require_once DOL_DOCUMENT_ROOT.'/blockedlog/class/blockedlog.class.php';

		$object = new stdClass();
		$object->id = 1;
		$object->element = 'module';
		$object->ref = 'systemevent';
		$object->entity = $conf->entity;
		$object->date = dol_now();

		$b = new BlockedLog($this->db);
		$result = $b->setObjectData($object, 'MODULE_RESET', 0);
		if ($result < 0) {
			$this->error = $b->error;
			$this->errors = $b->errors;
			return 0;
		}

		if ($b->alreadyUsed(1)) {
			$res = $b->create($user, '0000000000'); // If already used for something else than SET or UNSET, we log with error
		} else {
			$res = $b->create($user);
		}
		if ($res <= 0) {
			$this->error = $b->error;
			$this->errors = $b->errors;
			return $res;
		}

		return $this->_remove($sql, $options);
	}
}
