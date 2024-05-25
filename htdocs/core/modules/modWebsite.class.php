<?php
/* Copyright (C) 2015      Laurent Destailleur <eldy@users.sourceforge.net>
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
 * 	\defgroup   website     Module website
 *  \brief      website module descriptor.
 *  \file       htdocs/core/modules/modWebsite.class.php
 *  \ingroup    websites
 *  \brief      Description and activation file for the module Website
 */
include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';


/**
 *	Class to describe Websites module
 */
class modWebsite extends DolibarrModules
{
	/**
	 *   Constructor. Define names, constants, directories, boxes, permissions
	 *
	 *   @param      DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
		$this->numero = 10000;

		// Family can be 'crm','financial','hr','projects','products','ecm','technic','other'
		// It is used to group modules in module setup page
		$this->family = "portal";
		$this->module_position = '50';
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		$this->description = "Enable to build and serve public web sites with CMS features";
		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = 'dolibarr';
		// Key used in llx_const table to save module status enabled/disabled (where MYMODULE is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		// Name of image file used for this module.
		$this->picto = 'website';

		// Data directories to create when module is enabled
		$this->dirs = array("/website/temp");

		// Config pages
		$this->config_page_url = array('website.php');

		// Dependencies
		$this->hidden = getDolGlobalInt('MODULE_WEBSITE_DISABLED'); // A condition to disable module
		$this->depends = array('modFckeditor'); // List of modules id that must be enabled if this module is enabled
		$this->requiredby = array(); // List of modules id to disable if this one is disabled
		$this->conflictwith = array(); // List of modules id this module is in conflict with
		$this->phpmin = array(7, 0); // Minimum version of PHP required by module
		$this->langfiles = array("website");

		// Constants
		$this->const = array();

		// New pages on tabs
		//$this->tabs[] = array();  					// To add a new tab identified by code tabname1

		// Boxes
		$this->boxes = array();

		// Permissions
		$this->rights = array(); // Permission array used by this module
		$this->rights_class = 'website';
		$r = 0;

		$this->rights[$r][0] = 10001;
		$this->rights[$r][1] = 'Read website content';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'read';
		$r++;

		$this->rights[$r][0] = 10002;
		$this->rights[$r][1] = 'Create/modify website content (html and javascript content)';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'write';
		$r++;

		$this->rights[$r][0] = 10003;
		$this->rights[$r][1] = 'Create/modify website content (dynamic php code). Dangerous, must be reserved to restricted developers.';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'writephp';
		$r++;

		$this->rights[$r][0] = 10005;
		$this->rights[$r][1] = 'Delete website content';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'delete';
		$r++;

		$this->rights[$r][0] = 10008;
		$this->rights[$r][1] = 'Export website content';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'export';
		$r++;

		// Main menu entries
		$r = 0;
		$this->menu[$r] = array('fk_menu'=>'0', // Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
								'type'=>'top', // This is a Left menu entry
								'titre'=>'WebSites',
								'prefix' => img_picto('', $this->picto, 'class="pictofixedwidth em092"'),
								'mainmenu'=>'website',
								'url'=>'/website/index.php',
								'langs'=>'website', // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
								'position'=>100,
								'enabled'=>'$conf->website->enabled', // Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
								'perms'=>'$user->rights->website->read', // Use 'perms'=>'$user->hasRight("mymodule","level1","level2")' if you want your menu with a permission rules
								'target'=>'',
								'user'=>2); // 0=Menu for internal users, 1=external users, 2=both
		$r++;

		// Exports
		$r = 1;

		$this->export_code[$r] = $this->rights_class.'_'.$r;
		$this->export_label[$r] = 'MyWebsitePages'; // Translation key (used only if key ExportDataset_xxx_z not found)
		$this->export_permission[$r] = array(array("website", "export"));
		$this->export_icon[$r] = 'globe';
		$keyforclass = 'WebsitePage';
		$keyforclassfile = '/website/class/websitepage.class.php';
		$keyforelement = 'Website';
		include DOL_DOCUMENT_ROOT.'/core/commonfieldsinexport.inc.php';
		//$keyforselect='myobject'; $keyforelement='myobject'; $keyforaliasextra='extra';
		//include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';
		$this->export_sql_start[$r] = 'SELECT DISTINCT ';
		$this->export_sql_end[$r]  = ' FROM '.MAIN_DB_PREFIX.'website_page as t, '.MAIN_DB_PREFIX.'website as p';
		$this->export_sql_end[$r] .= ' WHERE t.fk_website = p.rowid';
		$this->export_sql_end[$r] .= ' AND p.entity IN ('.getEntity('website').')';
		$r++;
	}


	/**
	 *  Function called when module is enabled.
	 *  The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *  It also creates data directories
	 *
	 *  @param      string	$options    Options when enabling module ('', 'noboxes')
	 *  @return     int                 1 if OK, 0 if KO
	 */
	public function init($options = '')
	{
		global $conf, $langs;

		$error = 0;

		$result = $this->_load_tables('/install/mysql/', 'website');
		if ($result < 0) {
			return -1; // Do not activate module if error 'not allowed' returned when loading module SQL queries (the _load_table run sql with run_sql with the error allowed parameter set to 'default')
		}

		// Remove permissions and default values
		$this->remove($options);

		// Copy flags and octicons directory
		$dirarray = array('common/flags'=>'flags', 'common/octicons/build/svg'=>'octicons');
		foreach ($dirarray as $dirfrom => $dirtarget) {
			$src = DOL_DOCUMENT_ROOT.'/theme/'.$dirfrom;
			$dest = DOL_DATA_ROOT.'/medias/image/'.$dirtarget;

			if (is_dir($src)) {
				require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
				dol_mkdir($dest);
				$result = dolCopyDir($src, $dest, 0, 0);
				if ($result < 0) {
					$langs->load("errors");
					$this->error = $langs->trans('ErrorFailToCopyDir', $src, $dest);
					$this->errors[] = $langs->trans('ErrorFailToCopyDir', $src, $dest);
					$error++;
				}
			}
		}

		if ($error) {
			return 0;
		}

		// Website templates
		$srcroot = DOL_DOCUMENT_ROOT.'/install/doctemplates/websites';
		$destroot = DOL_DATA_ROOT.'/doctemplates/websites';

		dol_mkdir($destroot);

		// Copy templates in zip format (old)
		$docs = dol_dir_list($srcroot, 'files', 0, 'website_.*(\.zip|\.jpg)$');
		foreach ($docs as $cursorfile) {
			$src = $srcroot.'/'.$cursorfile['name'];
			$dest = $destroot.'/'.$cursorfile['name'];

			$result = dol_copy($src, $dest, 0, 1); // For full zip templates, we overwrite old existing files
			if ($result < 0) {
				$langs->load("errors");
				$this->error = $langs->trans('ErrorFailToCopyFile', $src, $dest);
				$this->errors[] = $langs->trans('ErrorFailToCopyFile', $src, $dest);
				$error++;
			}
		}

		// Copy templates in dir format (recommended) into zip file
		$docs = dol_dir_list($srcroot, 'directories', 0, 'website_.*$');
		foreach ($docs as $cursorfile) {
			$src = $srcroot.'/'.$cursorfile['name'];
			$dest = $destroot.'/'.$cursorfile['name'];

			// Compress it
			global $errormsg;
			$errormsg = '';
			$result = dol_compress_dir($src, $dest.'.zip', 'zip');
			if ($result < 0) {
				$error++;
				$this->error = ($errormsg ? $errormsg : $langs->trans('ErrorFailToCreateZip', $dest));
				$this->errors[] = ($errormsg ? $errormsg : $langs->trans('ErrorFailToCreateZip', $dest));
			}
		}

		if ($error) {
			return 0;
		}

		$sql = array();

		return $this->_init($sql, $options);
	}
}
