<?php
/* Copyright (C) 2003-2007	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004		Sebastien Di Cintio		<sdicintio@ressource-toi.org>
 * Copyright (C) 2004		Benoit Mortier			<benoit.mortier@opensides.be>
 * Copyright (C) 2004		Eric Seigne				<eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2013	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2024	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2014		Raphaël Doursenaud		<rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2018		Josep Lluís Amador		<joseplluis@lliuretic.cat>
 * Copyright (C) 2019-2024	Frédéric France			<frederic.france@free.fr>
 * Copyright (C) 2024		MDW						<mdeweerd@users.noreply.github.com>
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
 * \file           htdocs/core/modules/DolibarrModules.class.php
 * \brief          File of parent class of module descriptor class files
 */


/**
 * Class DolibarrModules
 *
 * Parent class for module descriptor class files
 */
class DolibarrModules // Can not be abstract, because we need to instantiate it into unActivateModule to be able to disable a module whose files were removed.
{
	/**
	 * @var DoliDB	Database handler
	 */
	public $db;

	/**
	 * @var int 	Module unique ID
	 * @see https://wiki.dolibarr.org/index.php/List_of_modules_id
	 */
	public $numero;

	/**
	 * @var string 	Publisher name
	 */
	public $editor_name;

	/**
	 * @var string 	URL of module at publisher site
	 */
	public $editor_url;

	/**
	 * @var string 	URL of logo of the publisher. Must be image filename into the module/img directory followed with @modulename. Example: 'myimage.png@mymodule'.
	 */
	public $editor_squarred_logo;

	/**
	 * @var	string	Family
	 * @see $familyinfo
	 *
	 * Native values: 'crm', 'financial', 'hr', 'projects', 'products', 'ecm', 'technic', 'other'.
	 * Use familyinfo to declare a custom value.
	 */
	public $family;

	/**
	 * @var array<string,array{position:string,label:string}> Custom family information
	 * @see $family
	 *
	 * e.g.:
	 * array(
	 *     'myownfamily' => array(
	 *         'position' => '001',
	 *         'label' => $langs->trans("MyOwnFamily")
	 *     )
	 * );
	 */
	public $familyinfo;

	/**
	 * @var string	Module position on 2 digits
	 */
	public $module_position = '50';

	/**
	 * @var string 	Module name
	 *
	 * Only used if Module[ID]Name translation string is not found.
	 *
	 * You can use the following code to automatically derive it from your module's class name:
	 * preg_replace('/^mod/i', '', get_class($this))
	 */
	public $name;

	/**
	 * @var string[] Paths to create when module is activated
	 *
	 * e.g.: array('/mymodule/temp')
	 */
	public $dirs = array();

	/**
	 * @var array Module boxes
	 */
	public $boxes = array();

	/**
	 * @var array Module constants
	 */
	public $const = array();

	/**
	 * @var array Module cron jobs entries
	 */
	public $cronjobs = array();

	/**
	 * @var array 	Module access rights
	 */
	public $rights;

	/**
	 * @var int		1=Admin is always granted of permission of modules (even when module is disabled)
	 */
	public $rights_admin_allowed;

	/**
	 * @var string 	Module access rights family
	 */
	public $rights_class;

	const KEY_ID = 0;
	const KEY_LABEL = 1;
	const KEY_TYPE = 2;	// deprecated
	const KEY_DEFAULT = 3;
	const KEY_FIRST_LEVEL = 4;
	const KEY_SECOND_LEVEL = 5;
	const KEY_MODULE = 6;
	const KEY_ENABLED = 7;

	/**
	 * @var array|int 	Module menu entries (1 means the menu entries are not declared into module descriptor but are hardcoded into menu manager)
	 */
	public $menu = array();

	/**
	 * @var array{triggers?:int<0,1>,login?:int<0,1>,substitutions?:int<0,1>,menus?:int<0,1>,theme?:int<0,1>,tpl?:int<0,1>,barcode?:int<0,1>,models?:int<0,1>,printing?:int<0,1>,css?:string[],js?:string[],hooks?:array{data?:string[],entity?:string},moduleforexternal?:int<0,1>,websitetemplates?:int<0,1>,contactelement?:int<0,1>} Module parts
	 *  array(
	 *      // Set this to 1 if module has its own trigger directory (/mymodule/core/triggers)
	 *      'triggers' => 0,
	 *      // Set this to 1 if module has its own login method directory (/mymodule/core/login)
	 *      'login' => 0,
	 *      // Set this to 1 if module has its own substitution function file (/mymodule/core/substitutions)
	 *      'substitutions' => 0,
	 *      // Set this to 1 if module has its own menus handler directory (/mymodule/core/menus)
	 *      'menus' => 0,
	 *      // Set this to 1 if module has its own theme directory (/mymodule/theme)
	 *      'theme' => 0,
	 *      // Set this to 1 if module overwrite template dir (/mymodule/core/tpl)
	 *      'tpl' => 0,
	 *      // Set this to 1 if module has its own barcode directory (/mymodule/core/modules/barcode)
	 *      'barcode' => 0,
	 *      // Set this to 1 if module has its own models directory (/mymodule/core/modules/xxx)
	 *      'models' => 0,
	 *      // Set this to relative path of css file if module has its own css file
	 *      'css' => '/mymodule/css/mymodule.css.php',
	 *      // Set this to relative path of js file if module must load a js on all pages
	 *      'js' => '/mymodule/js/mymodule.js',
	 *      // Set here all hooks context managed by module
	 *      'hooks' => array('hookcontext1','hookcontext2')
	 *  )
	 */
	public $module_parts = array();

	/**
	 * @var string Error message
	 */
	public $error;

	/**
	 * @var string[] Array of Errors messages
	 */
	public $errors;

	/**
	 * @var string 	Module version ('x.y.z' or a reserved keyword)
	 * @see http://semver.org
	 *
	 * The following keywords that can also be used are:
	 * 'development'
	 * 'experimental'
	 * 'dolibarr': only for core modules that share its version
	 * 'dolibarr_deprecated': only for deprecated core modules
	 */
	public $version;

	/**
	 * Module last version
	 * @var string $lastVersion
	 */
	public $lastVersion = '';

	/**
	 * true indicate this module need update
	 * @var bool $needUpdate
	 */
	public $needUpdate = false;

	/**
	 * @var string Module description (short text)
	 *
	 * Only used if Module[ID]Desc translation string is not found.
	 */
	public $description;

	/**
	 * @var   string Module description (long text)
	 * @since 4.0.0
	 *
	 * HTML content supported.
	 */
	public $descriptionlong;

	/**
	 * @var array dictionaries description
	 */
	public $dictionaries = array();

	/**
	 * @var array tabs description
	 */
	public $tabs;

	// For exports

	/**
	 * @var string Module export code
	 */
	public $export_code;

	/**
	 * @var string[] Module export label
	 */
	public $export_label;

	public $export_icon;

	/**
	 * @var array export enabled
	 */
	public $export_enabled;
	public $export_permission;
	public $export_fields_array;
	public $export_TypeFields_array; // Array of key=>type where type can be 'Numeric', 'Date', 'Text', 'Boolean', 'Status', 'List:xxx:fieldlabel:rowid'
	public $export_entities_array;
	public $export_aggregate_array;
	public $export_examplevalues_array;
	public $export_help_array;
	public $export_special_array; // special or computed field
	public $export_dependencies_array;
	public $export_sql_start;
	public $export_sql_end;
	public $export_sql_order;


	// For import

	/**
	 * @var string Module import code
	 */
	public $import_code;

	/**
	 * @var string[] Module import label
	 */
	public $import_label;

	public $import_icon;
	public $import_entities_array;
	public $import_tables_array;
	public $import_tables_creator_array;
	public $import_fields_array;
	public $import_fieldshidden_array;
	public $import_convertvalue_array;
	public $import_regex_array;
	public $import_examplevalues_array;
	public $import_updatekeys_array;
	public $import_run_sql_after_array;
	public $import_TypeFields_array;
	public $import_help_array;

	/**
	 * @var string Module constant name
	 */
	public $const_name;

	/**
	 * @var bool Module can't be disabled
	 */
	public $always_enabled;

	/**
	 * @var bool Module is disabled
	 */
	public $disabled;

	/**
	 * @var int Module is enabled globally (Multicompany support)
	 */
	public $core_enabled;

	/**
	 * @var string Name of image file used for this module
	 *
	 * If file is in theme/yourtheme/img directory under name object_pictoname.png use 'pictoname'
	 * If file is in module/img directory under name object_pictoname.png use 'pictoname@module'
	 */
	public $picto;

	/**
	 * @var string[]|string 	List of config pages (Old modules uses a string. New one must use an array)
	 *
	 * Name of php pages stored into module/admin directory, used to setup module.
	 * e.g.: array("setup.php@mymodule")
	 */
	public $config_page_url;


	/**
	 * @var array 	List of module class names that must be enabled if this module is enabled. e.g.: array('modAnotherModule', 'FR'=>'modYetAnotherModule')
	 * 				Another example : array('always'=>array("modBanque", "modFacture", "modProduct", "modCategorie"), 'FR'=>array('modBlockedLog'));
	 * @see $requiredby
	 */
	public $depends;

	/**
	 * @var string[] List of module class names to disable if the module is disabled.
	 * @see $depends
	 */
	public $requiredby;

	/**
	 * @var string[] List of module class names as string this module is in conflict with.
	 * @see $depends
	 */
	public $conflictwith;

	/**
	 * @var string[] Module language files
	 */
	public $langfiles;

	/**
	 * @var array<string,string> Array of warnings to show when we activate the module
	 *
	 * array('always'='text') or array('FR'='text')
	 */
	public $warnings_activation;

	/**
	 * @var array<string,string> Array of warnings to show when we activate an external module
	 *
	 * array('always'='text') or array('FR'='text')
	 */
	public $warnings_activation_ext;

	/**
	 * @var array<string,string> Array of warnings to show when we disable the module
	 *
	 * array('always'='text') or array('FR'='text')
	 */
	public $warnings_unactivation;

	/**
	 * @var array Minimum version of PHP required by module.
	 * e.g.: PHP ≥ 7.0 = array(7, 0)
	 */
	public $phpmin;

	public $phpmax;

	/**
	 * @var array Minimum version of Dolibarr required by module.
	 * e.g.: Dolibarr ≥ 3.6 = array(3, 6)
	 */
	public $need_dolibarr_version;

	public $need_javascript_ajax;

	public $enabled_bydefault;

	/**
	 * @var bool|int Whether to hide the module.
	 */
	public $hidden = false;

	/**
	 * @var string url to check for module update
	 */
	public $url_last_version;


	/**
	 * Constructor. Define names, constants, directories, boxes, permissions
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}
	// We should but can't set this as abstract because this will make dolibarr hang
	// after migration due to old module not implementing. We must wait PHP is able to make
	// a try catch on Fatal error to manage this correctly.
	// We need constructor into function unActivateModule into admin.lib.php


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 * Enables a module.
	 * Inserts all information into database.
	 *
	 * @param array  $array_sql 	SQL requests to be executed when enabling module
	 * @param string $options   	String with options when disabling module:
	 *                          	- 'noboxes' = Do all actions but do not insert boxes
	 *                          	- 'newboxdefonly' = Do all actions but for boxes, insert def of boxes only and not boxes activation
	 * @return int                  1 if OK, 0 if KO
	 */
	protected function _init($array_sql, $options = '')
	{
		// phpcs:enable
		global $conf;
		$err = 0;

		$this->db->begin();

		// Insert activation module constant
		if (!$err) {
			$err += $this->_active();
		}

		// Insert new pages for tabs (into llx_const)
		if (!$err) {
			$err += $this->insert_tabs();
		}

		// Insert activation of module's parts. Copy website templates into doctemplates.
		if (!$err) {
			$err += $this->insert_module_parts();
		}

		// Insert constant defined by modules (into llx_const)
		if (!$err && !preg_match('/newboxdefonly/', $options)) {
			$err += $this->insert_const(); // Test on newboxdefonly to avoid to erase value during upgrade
		}

		// Insert boxes def (into llx_boxes_def) and boxes setup (into llx_boxes)
		if (!$err && !preg_match('/noboxes/', $options)) {
			$err += $this->insert_boxes($options);
		}

		// Insert cron job entries (entry in llx_cronjobs)
		if (!$err) {
			$err += $this->insert_cronjobs();
		}

		// Insert permission definitions of module into llx_rights_def. If user is admin, grant this permission to user.
		if (!$err) {
			$err += $this->insert_permissions(1, null, 1);
		}

		// Insert specific menus entries into database
		if (!$err) {
			$err += $this->insert_menus();
		}

		// Create module's directories
		if (!$err) {
			$err += $this->create_dirs();
		}

		// Execute addons requests
		$num = count($array_sql);
		for ($i = 0; $i < $num; $i++) {
			if (!$err) {
				$val = $array_sql[$i];
				$sql = $val;
				$ignoreerror = 0;
				if (is_array($val)) {
					$sql = $val['sql'];
					$ignoreerror = $val['ignoreerror'];
				}
				// Add current entity id
				$sql = str_replace('__ENTITY__', (string) $conf->entity, $sql);

				dol_syslog(get_class($this)."::_init ignoreerror=".$ignoreerror, LOG_DEBUG);
				$result = $this->db->query($sql, $ignoreerror);
				if (!$result) {
					if (!$ignoreerror) {
						$this->error = $this->db->lasterror();
						$err++;
					} else {
						dol_syslog(get_class($this)."::_init Warning ".$this->db->lasterror(), LOG_WARNING);
					}
				}
			}
		}

		// Return code
		if (!$err) {
			$this->db->commit();

			$moduleNameInConf = strtolower(preg_replace('/^MAIN_MODULE_/', '', $this->const_name));
			// two exceptions to handle
			if ($moduleNameInConf === 'propale') {
				$moduleNameInConf = 'propal';
			} elseif ($moduleNameInConf === 'supplierproposal') {
				$moduleNameInConf = 'supplier_proposal';
			}

			$conf->modules[$moduleNameInConf] = $moduleNameInConf; // Add this module in list of enabled modules so isModEnabled() will work (conf->module->enabled must no more be used)

			return 1;
		} else {
			$this->db->rollback();
			return 0;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 * Disable function. Deletes the module constants and boxes from the database.
	 *
	 * @param string[] $array_sql SQL requests to be executed when module is disabled
	 * @param string   $options   Options when disabling module:
	 *
	 * @return int                     1 if OK, 0 if KO
	 */
	protected function _remove($array_sql, $options = '')
	{
		global $conf;
		// phpcs:enable
		$err = 0;

		$this->db->begin();

		// Remove activation module line (constant MAIN_MODULE_MYMODULE in llx_const)
		if (!$err) {
			$err += $this->_unactive();
		}

		// Remove activation of module's new tabs (MAIN_MODULE_MYMODULE_TABS_XXX in llx_const)
		if (!$err) {
			$err += $this->delete_tabs();
		}

		// Remove activation of module's parts (MAIN_MODULE_MYMODULE_XXX in llx_const)
		if (!$err) {
			$err += $this->delete_module_parts();
		}

		// Remove constants defined by modules
		if (!$err) {
			$err += $this->delete_const();
		}

		// Remove list of module's available boxes (entry in llx_boxes)
		if (!$err && !preg_match('/(newboxdefonly|noboxes)/', $options)) {
			$err += $this->delete_boxes(); // We don't have to delete if option ask to keep boxes safe or ask to add new box def only
		}

		// Remove list of module's cron job entries (entry in llx_cronjobs)
		if (!$err) {
			$err += $this->delete_cronjobs();
		}

		// Remove module's permissions from list of available permissions (entries in llx_rights_def)
		if (!$err) {
			$err += $this->delete_permissions();
		}

		// Remove module's menus (entries in llx_menu)
		if (!$err) {
			$err += $this->delete_menus();
		}

		// Remove module's directories
		if (!$err) {
			$err += $this->delete_dirs();
		}

		// Run complementary sql requests
		$num = count((array) $array_sql);
		for ($i = 0; $i < $num; $i++) {
			if (!$err) {
				dol_syslog(get_class($this)."::_remove", LOG_DEBUG);
				$result = $this->db->query($array_sql[$i]);
				if (!$result) {
					$this->error = $this->db->error();
					$err++;
				}
			}
		}

		// Return code
		if (!$err) {
			$this->db->commit();

			// Disable modules
			$moduleNameInConf = strtolower(preg_replace('/^MAIN_MODULE_/', '', $this->const_name));
			// two exceptions to handle
			if ($moduleNameInConf === 'propale') {
				$moduleNameInConf = 'propal';
			} elseif ($moduleNameInConf === 'supplierproposal') {
				$moduleNameInConf = 'supplier_proposal';
			}

			unset($conf->modules[$moduleNameInConf]);	// Add this module in list of enabled modules so isModEnabled() will work (conf->module->enabled must no more be used)

			return 1;
		} else {
			$this->db->rollback();
			return 0;
		}
	}


	/**
	 * Gives the translated module name if translation exists in admin.lang or into language files of module.
	 * Otherwise return the module key name.
	 *
	 * @return string  Translated module name
	 */
	public function getName()
	{
		global $langs;
		$langs->load("admin");

		if ($langs->transnoentitiesnoconv("Module".$this->numero."Name") != "Module".$this->numero."Name") {
			// If module name translation exists
			return $langs->transnoentitiesnoconv("Module".$this->numero."Name");
		} else {
			// If module name translation using it's unique id does not exist, we try to use its name to find translation
			if (is_array($this->langfiles)) {
				foreach ($this->langfiles as $val) {
					if ($val) {
						$langs->load($val);
					}
				}
			}

			if ($langs->trans("Module".$this->name."Name") != "Module".$this->name."Name") {
				// If module name translation exists
				return $langs->transnoentitiesnoconv("Module".$this->name."Name");
			}

			// Last chance with simple label
			return $langs->transnoentitiesnoconv($this->name);
		}
	}


	/**
	 * Gives the translated module description if translation exists in admin.lang or the default module description
	 *
	 * @return string  Translated module description
	 */
	public function getDesc()
	{
		global $langs;
		$langs->load("admin");

		if ($langs->transnoentitiesnoconv("Module".$this->numero."Desc") != "Module".$this->numero."Desc") {
			// If module description translation exists
			return $langs->transnoentitiesnoconv("Module".$this->numero."Desc");
		} else {
			// If module description translation does not exist using its unique id, we can use its name to find translation
			if (is_array($this->langfiles)) {
				foreach ($this->langfiles as $val) {
					if ($val) {
						$langs->load($val);
					}
				}
			}

			if ($langs->transnoentitiesnoconv("Module".$this->name."Desc") != "Module".$this->name."Desc") {
				// If module name translation exists
				return $langs->trans("Module".$this->name."Desc");
			}

			// Last chance with simple label
			return $langs->trans($this->description);
		}
	}

	/**
	 * Gives the long description of a module. First check README-la_LA.md then README.md
	 * If no markdown files found, it returns translated value of the key ->descriptionlong.
	 *
	 * @return string     Long description of a module from README.md of from property.
	 */
	public function getDescLong()
	{
		global $langs;
		$langs->load("admin");

		include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
		include_once DOL_DOCUMENT_ROOT.'/core/lib/geturl.lib.php';

		$content = '';
		$pathoffile = $this->getDescLongReadmeFound();

		if ($pathoffile) {     // Mostly for external modules
			$content = file_get_contents($pathoffile, false, null, 0, 1024 * 1024);	// Max size loaded 1Mb

			if ((float) DOL_VERSION >= 6.0) {
				@include_once DOL_DOCUMENT_ROOT.'/core/lib/parsemd.lib.php';

				$content = dolMd2Html(
					$content,
					'parsedown',
					array(
						'doc/' => dol_buildpath(strtolower($this->name).'/doc/', 1),
						'img/' => dol_buildpath(strtolower($this->name).'/img/', 1),
						'images/' => dol_buildpath(strtolower($this->name).'/images/', 1),
					)
				);

				$content = preg_replace('/<a href="/', '<a target="_blank" rel="noopener noreferrer" href="', $content);
			} else {
				$content = nl2br($content);
			}
		} else {
			// Mostly for internal modules
			if (!empty($this->descriptionlong)) {
				if (is_array($this->langfiles)) {
					foreach ($this->langfiles as $val) {
						if ($val) {
							$langs->load($val);
						}
					}
				}

				$content = $langs->transnoentitiesnoconv($this->descriptionlong);
			}
		}

		return $content;
	}

	/**
	 * Return path of file if a README file was found.
	 *
	 * @return string      Path of file if a README file was found.
	 */
	public function getDescLongReadmeFound()
	{
		global $langs;

		$filefound = false;

		// Define path to file README.md.
		// First check README-la_LA.md then README-la.md then README.md
		$pathoffile = dol_buildpath(strtolower($this->name).'/README-'.$langs->defaultlang.'.md', 0);
		if (dol_is_file($pathoffile)) {
			$filefound = true;
		}
		if (!$filefound) {
			$tmp = explode('_', $langs->defaultlang);
			$pathoffile = dol_buildpath(strtolower($this->name).'/README-'.$tmp[0].'.md', 0);
			if (dol_is_file($pathoffile)) {
				$filefound = true;
			}
		}
		if (!$filefound) {
			$pathoffile = dol_buildpath(strtolower($this->name).'/README.md', 0);
			if (dol_is_file($pathoffile)) {
				$filefound = true;
			}
		}

		return ($filefound ? $pathoffile : '');
	}


	/**
	 * Gives the changelog. First check ChangeLog-la_LA.md then ChangeLog.md
	 *
	 * @return string  Content of ChangeLog
	 */
	public function getChangeLog()
	{
		global $langs;
		$langs->load("admin");

		include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
		include_once DOL_DOCUMENT_ROOT.'/core/lib/geturl.lib.php';

		$filefound = false;

		// Define path to file README.md.
		// First check ChangeLog-la_LA.md then ChangeLog.md
		$pathoffile = dol_buildpath(strtolower($this->name).'/ChangeLog-'.$langs->defaultlang.'.md', 0);
		if (dol_is_file($pathoffile)) {
			$filefound = true;
		}
		if (!$filefound) {
			$pathoffile = dol_buildpath(strtolower($this->name).'/ChangeLog.md', 0);
			if (dol_is_file($pathoffile)) {
				$filefound = true;
			}
		}

		if ($filefound) {     // Mostly for external modules
			$content = file_get_contents($pathoffile);

			if ((float) DOL_VERSION >= 6.0) {
				@include_once DOL_DOCUMENT_ROOT.'/core/lib/parsemd.lib.php';

				$content = dolMd2Html($content, 'parsedown', array('doc/' => dol_buildpath(strtolower($this->name).'/doc/', 1)));
			} else {
				$content = nl2br($content);
			}
		} else {
			$content = '';
		}

		return $content;
	}

	/**
	 * Gives the publisher name
	 *
	 * @return string  Publisher name
	 */
	public function getPublisher()
	{
		return $this->editor_name;
	}

	/**
	 * Gives the publisher url
	 *
	 * @return string  Publisher url
	 */
	public function getPublisherUrl()
	{
		return $this->editor_url;
	}

	/**
	 * Gives module version (translated if param $translated is on)
	 * For 'experimental' modules, gives 'experimental' translation
	 * For 'dolibarr' modules, gives Dolibarr version
	 *
	 * @param  int 		$translated 		1=Special version keys are translated, 0=Special version keys are not translated
	 * @return string               		Module version
	 */
	public function getVersion($translated = 1)
	{
		global $langs;
		$langs->load("admin");

		$ret = '';

		$newversion = preg_replace('/_deprecated/', '', $this->version);
		if ($newversion == 'experimental') {
			$ret = ($translated ? $langs->transnoentitiesnoconv("VersionExperimental") : $newversion);
		} elseif ($newversion == 'development') {
			$ret = ($translated ? $langs->transnoentitiesnoconv("VersionDevelopment") : $newversion);
		} elseif ($newversion == 'dolibarr') {
			$ret = DOL_VERSION;
		} elseif ($newversion) {
			$ret = $newversion;
		} else {
			$ret = ($translated ? $langs->transnoentitiesnoconv("VersionUnknown") : 'unknown');
		}

		if (preg_match('/_deprecated/', $this->version)) {
			$ret .= ($translated ? ' ('.$langs->transnoentitiesnoconv("Deprecated").')' : $this->version);
		}
		return $ret;
	}

	/**
	 * Gives the module position
	 *
	 * @return string	Module position (an external module should never return a value lower than 100000. 1-100000 are reserved for core)
	 */
	public function getModulePosition()
	{
		if (in_array($this->version, array('dolibarr', 'dolibarr_deprecated', 'experimental', 'development'))) {	// core module
			return $this->module_position;
		} else {																			// external module
			if ($this->module_position >= 100000) {
				return $this->module_position;
			} else {
				$position = intval($this->module_position) + 100000;
				return strval($position);
			}
		}
	}

	/**
	 * Tells if module is core or external.
	 * Version = 'dolibarr', 'dolibarr_deprecated', 'experimental' and 'development' means core modules
	 *
	 * @return string  'core', 'external' or 'unknown'
	 */
	public function isCoreOrExternalModule()
	{
		if ($this->version == 'dolibarr' || $this->version == 'dolibarr_deprecated') {
			return 'core';
		}
		if (!empty($this->version) && !in_array($this->version, array('experimental', 'development'))) {
			return 'external';
		}
		if (!empty($this->editor_name) || !empty($this->editor_url)) {
			return 'external';
		}
		if ($this->numero >= 100000) {
			return 'external';
		}
		return 'unknown';
	}


	/**
	 * Gives module related language files list
	 *
	 * @return string[]    Language files list
	 */
	public function getLangFilesArray()
	{
		return $this->langfiles;
	}

	/**
	 * Gives translated label of an export dataset
	 *
	 * @param int $r Dataset index
	 *
	 * @return string       Translated databaset label
	 */
	public function getExportDatasetLabel($r)
	{
		global $langs;

		$langstring = "ExportDataset_".$this->export_code[$r];
		if ($langs->trans($langstring) == $langstring) {
			// Translation not found
			return $langs->trans($this->export_label[$r]);
		} else {
			// Translation found
			return $langs->trans($langstring);
		}
	}


	/**
	 * Gives translated label of an import dataset
	 *
	 * @param int $r Dataset index
	 *
	 * @return string      Translated dataset label
	 */
	public function getImportDatasetLabel($r)
	{
		global $langs;

		$langstring = "ImportDataset_".$this->import_code[$r];
		//print "x".$langstring;
		if ($langs->trans($langstring) == $langstring) {
			// Translation not found
			return $langs->transnoentitiesnoconv($this->import_label[$r]);
		} else {
			// Translation found
			return $langs->transnoentitiesnoconv($langstring);
		}
	}


	/**
	 * Gives the last date of activation
	 *
	 * @return 	int|string       	Date of last activation or '' if module was never activated
	 */
	public function getLastActivationDate()
	{
		global $conf;

		$err = 0;

		$sql = "SELECT tms FROM ".MAIN_DB_PREFIX."const";
		$sql .= " WHERE ".$this->db->decrypt('name')." = '".$this->db->escape($this->const_name)."'";
		$sql .= " AND entity IN (0, ".((int) $conf->entity).")";

		dol_syslog(get_class($this)."::getLastActiveDate", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$err++;
		} else {
			$obj = $this->db->fetch_object($resql);
			if ($obj) {
				return $this->db->jdate($obj->tms);
			}
		}

		return '';
	}


	/**
	 * Gives the last author of activation
	 *
	 * @return array       Array array('authorid'=>Id of last activation user, 'lastactivationdate'=>Date of last activation)
	 */
	public function getLastActivationInfo()
	{
		global $conf;

		$err = 0;

		$sql = "SELECT tms, note FROM ".MAIN_DB_PREFIX."const";
		$sql .= " WHERE ".$this->db->decrypt('name')." = '".$this->db->escape($this->const_name)."'";
		$sql .= " AND entity IN (0, ".$conf->entity.")";

		dol_syslog(get_class($this)."::getLastActiveDate", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$err++;
		} else {
			$obj = $this->db->fetch_object($resql);
			if ($obj) {
				$tmp = array();
				if ($obj->note) {
					$tmp = json_decode($obj->note, true);
				}
				return array(
					'authorid' => empty($tmp['authorid']) ? '' : $tmp['authorid'],
					'ip' => empty($tmp['ip']) ? '' : $tmp['ip'],
					'lastactivationdate' => $this->db->jdate($obj->tms),
					'lastactivationversion' => (!empty($tmp['lastactivationversion']) ? $tmp['lastactivationversion'] : 'unknown'),
				);
			}
		}

		return array();
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 * Insert constants for module activation
	 *
	 * @return int Error count (0 if OK)
	 */
	protected function _active()
	{
		// phpcs:enable
		global $conf, $user;

		$err = 0;

		// Common module
		$entity = ((!empty($this->always_enabled) || !empty($this->core_enabled)) ? 0 : $conf->entity);

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."const";
		$sql .= " WHERE ".$this->db->decrypt('name')." = '".$this->db->escape($this->const_name)."'";
		$sql .= " AND entity IN (0, ".$entity.")";

		dol_syslog(get_class($this)."::_active delete activation constant", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$err++;
		}

		$note = json_encode(
			array(
				'authorid' => (is_object($user) ? $user->id : 0),
				'ip' => (empty($_SERVER['REMOTE_ADDR']) ? '' : $_SERVER['REMOTE_ADDR']),
				'lastactivationversion' => $this->version,
			)
		);

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."const (name, value, visible, entity, note) VALUES";
		$sql .= " (".$this->db->encrypt($this->const_name);
		$sql .= ", ".$this->db->encrypt('1');
		$sql .= ", 0, ".((int) $entity);
		$sql .= ", '".$this->db->escape($note)."')";

		dol_syslog(get_class($this)."::_active insert activation constant", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$err++;
		}

		return $err;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 * Module deactivation
	 *
	 * @return int Error count (0 if OK)
	 */
	protected function _unactive()
	{
		// phpcs:enable
		global $conf;

		$err = 0;

		// Common module
		$entity = ((!empty($this->always_enabled) || !empty($this->core_enabled)) ? 0 : $conf->entity);

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."const";
		$sql .= " WHERE ".$this->db->decrypt('name')." = '".$this->db->escape($this->const_name)."'";
		$sql .= " AND entity IN (0, ".$entity.")";

		dol_syslog(get_class($this)."::_unactive", LOG_DEBUG);
		$this->db->query($sql);

		return $err;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps,PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 * Create tables and keys required by module:
	 * - Files table.sql or table-module.sql with create table instructions
	 * - Then table.key.sql or table-module.key.sql with create keys instructions
	 * - Then data_xxx.sql (usually provided by external modules only)
	 * - Then update_xxx.sql (usually provided by external modules only)
	 * Files must be stored in subdirectory 'tables' or 'data' into directory $reldir (Example: '/install/mysql/' or '/module/sql/')
	 * This function may also be called by :
	 * - _load_tables('/install/mysql/', 'modulename') into the this->init() of core module descriptors.
	 * - _load_tables('/mymodule/sql/') into the this->init() of external module descriptors.
	 *
	 * @param  	string 	$reldir 			Relative directory where to scan files. Example: '/install/mysql/' or '/module/sql/'
	 * @param	string	$onlywithsuffix		Only with the defined suffix
	 * @return 	int<0,1>             			Return integer <=0 if KO, >0 if OK
	 */
	protected function _load_tables($reldir, $onlywithsuffix = '')
	{
		// phpcs:enable
		global $conf;

		$error = 0;
		$dirfound = 0;
		$ok = 1;

		if (empty($reldir)) {
			return 1;
		}

		include_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

		foreach ($conf->file->dol_document_root as $dirroot) {
			if ($ok == 1) {
				$dirsql = $dirroot.$reldir;
				$ok = 0;

				// We will loop on xxx/, xxx/tables/, xxx/data/
				$listofsubdir = array('', 'tables/', 'data/');
				if ($this->db->type == 'pgsql') {
					$listofsubdir[] = '../pgsql/functions/';
				}

				foreach ($listofsubdir as $subdir) {
					$dir = $dirsql.$subdir;

					$handle = @opendir($dir); // Dir may not exists
					if (is_resource($handle)) {
						$dirfound++;

						// Run llx_mytable.sql files, then llx_mytable_*.sql
						$files = array();
						while (($file = readdir($handle)) !== false) {
							$files[] = $file;
						}
						sort($files);
						foreach ($files as $file) {
							if ($onlywithsuffix) {
								if (!preg_match('/\-'.preg_quote($onlywithsuffix, '/').'\./i', $file)) {
									//print 'File '.$file.' does not match suffix '.$onlywithsuffix.' so it is discarded<br>'."\n";
									continue;
								} else {
									//print 'File '.$file.' match suffix '.$onlywithsuffix.' so we keep it<br>'."\n";
								}
							}
							if (preg_match('/\.sql$/i', $file) && !preg_match('/\.key\.sql$/i', $file) && substr($file, 0, 4) == 'llx_') {
								$result = run_sql($dir.$file, !getDolGlobalString('MAIN_DISPLAY_SQL_INSTALL_LOG') ? 1 : 0, '', 1);
								if ($result <= 0) {
									$error++;
								}
							}
						}

						rewinddir($handle);

						// Run llx_mytable.key.sql files (Must be done after llx_mytable.sql) then then llx_mytable_*.key.sql
						$files = array();
						while (($file = readdir($handle)) !== false) {
							$files[] = $file;
						}
						sort($files);
						foreach ($files as $file) {
							if ($onlywithsuffix) {
								if (!preg_match('/\-'.preg_quote($onlywithsuffix, '/').'\./i', $file)) {
									//print 'File '.$file.' does not match suffix '.$onlywithsuffix.' so it is discarded<br>'."\n";
									continue;
								} else {
									//print 'File '.$file.' match suffix '.$onlywithsuffix.' so we keep it<br>'."\n";
								}
							}
							if (preg_match('/\.key\.sql$/i', $file) && substr($file, 0, 4) == 'llx_') {
								$result = run_sql($dir.$file, !getDolGlobalString('MAIN_DISPLAY_SQL_INSTALL_LOG') ? 1 : 0, '', 1);
								if ($result <= 0) {
									$error++;
								}
							}
						}

						rewinddir($handle);

						// Run functions-xxx.sql files (Must be done after llx_mytable.key.sql)
						$files = array();
						while (($file = readdir($handle)) !== false) {
							$files[] = $file;
						}
						sort($files);
						foreach ($files as $file) {
							if ($onlywithsuffix) {
								if (!preg_match('/\-'.preg_quote($onlywithsuffix, '/').'\./i', $file)) {
									//print 'File '.$file.' does not match suffix '.$onlywithsuffix.' so it is discarded<br>'."\n";
									continue;
								} else {
									//print 'File '.$file.' match suffix '.$onlywithsuffix.' so we keep it<br>'."\n";
								}
							}
							if (preg_match('/\.sql$/i', $file) && !preg_match('/\.key\.sql$/i', $file) && substr($file, 0, 9) == 'functions') {
								$result = run_sql($dir.$file, !getDolGlobalString('MAIN_DISPLAY_SQL_INSTALL_LOG') ? 1 : 0, '', 1);
								if ($result <= 0) {
									$error++;
								}
							}
						}

						rewinddir($handle);

						// Run data_xxx.sql files (Must be done after llx_mytable.key.sql)
						$files = array();
						while (($file = readdir($handle)) !== false) {
							$files[] = $file;
						}
						sort($files);
						foreach ($files as $file) {
							if ($onlywithsuffix) {
								if (!preg_match('/\-'.preg_quote($onlywithsuffix, '/').'\./i', $file)) {
									//print 'File '.$file.' does not match suffix '.$onlywithsuffix.' so it is discarded<br>'."\n";
									continue;
								} else {
									//print 'File '.$file.' match suffix '.$onlywithsuffix.' so we keep it<br>'."\n";
								}
							}
							if (preg_match('/\.sql$/i', $file) && !preg_match('/\.key\.sql$/i', $file) && substr($file, 0, 4) == 'data') {
								$result = run_sql($dir.$file, !getDolGlobalString('MAIN_DISPLAY_SQL_INSTALL_LOG') ? 1 : 0, '', 1);
								if ($result <= 0) {
									$error++;
								}
							}
						}

						rewinddir($handle);

						// Run update_xxx.sql files
						$files = array();
						while (($file = readdir($handle)) !== false) {
							$files[] = $file;
						}
						sort($files);
						foreach ($files as $file) {
							if ($onlywithsuffix) {
								if (!preg_match('/\-'.preg_quote($onlywithsuffix, '/').'\./i', $file)) {
									//print 'File '.$file.' does not match suffix '.$onlywithsuffix.' so it is discarded<br>'."\n";
									continue;
								} else {
									//print 'File '.$file.' match suffix '.$onlywithsuffix.' so we keep it<br>'."\n";
								}
							}
							if (preg_match('/\.sql$/i', $file) && !preg_match('/\.key\.sql$/i', $file) && substr($file, 0, 6) == 'update') {
								$result = run_sql($dir.$file, !getDolGlobalString('MAIN_DISPLAY_SQL_INSTALL_LOG') ? 1 : 0, '', 1);
								if ($result <= 0) {
									$error++;
								}
							}
						}

						closedir($handle);
					}
				}

				if ($error == 0) {
					$ok = 1;
				}
			}
		}

		if (!$dirfound) {
			dol_syslog("A module wants to load sql files from ".$reldir." but this directory was not found.", LOG_WARNING);
		}
		return $ok;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Adds boxes
	 *
	 * @param string $option Options when disabling module ('newboxdefonly'=insert only boxes definition)
	 *
	 * @return int             Error count (0 if OK)
	 */
	public function insert_boxes($option = '')
	{
		// phpcs:enable
		include_once DOL_DOCUMENT_ROOT.'/core/class/infobox.class.php';

		global $conf;

		$err = 0;

		if (is_array($this->boxes)) {
			dol_syslog(get_class($this)."::insert_boxes", LOG_DEBUG);

			$pos_name = InfoBox::getListOfPagesForBoxes();

			foreach ($this->boxes as $key => $value) {
				$file  = isset($this->boxes[$key]['file']) ? $this->boxes[$key]['file'] : '';
				$note  = isset($this->boxes[$key]['note']) ? $this->boxes[$key]['note'] : '';
				$enabledbydefaulton = isset($this->boxes[$key]['enabledbydefaulton']) ? $this->boxes[$key]['enabledbydefaulton'] : 'Home';

				if (empty($file)) {
					$file  = isset($this->boxes[$key][1]) ? $this->boxes[$key][1] : ''; // For backward compatibility
				}
				if (empty($note)) {
					$note  = isset($this->boxes[$key][2]) ? $this->boxes[$key][2] : ''; // For backward compatibility
				}

				// Search if boxes def already present
				$sql = "SELECT count(*) as nb FROM ".MAIN_DB_PREFIX."boxes_def";
				$sql .= " WHERE file = '".$this->db->escape($file)."'";
				$sql .= " AND entity = ".$conf->entity;
				if ($note) {
					$sql .= " AND note ='".$this->db->escape($note)."'";
				}

				$result = $this->db->query($sql);
				if ($result) {
					$obj = $this->db->fetch_object($result);
					if ($obj->nb == 0) {
						$this->db->begin();

						if (!$err) {
							$sql = "INSERT INTO ".MAIN_DB_PREFIX."boxes_def (file, entity, note)";
							$sql .= " VALUES ('".$this->db->escape($file)."', ";
							$sql .= $conf->entity.", ";
							$sql .= $note ? "'".$this->db->escape($note)."'" : "null";
							$sql .= ")";

							dol_syslog(get_class($this)."::insert_boxes", LOG_DEBUG);
							$resql = $this->db->query($sql);
							if (!$resql) {
								$err++;
							}
						}
						if (!$err && !preg_match('/newboxdefonly/', $option)) {
							$lastid = $this->db->last_insert_id(MAIN_DB_PREFIX."boxes_def", "rowid");

							foreach ($pos_name as $key2 => $val2) {
								//print 'key2='.$key2.'-val2='.$val2."<br>\n";
								if ($enabledbydefaulton && $val2 != $enabledbydefaulton) {
									continue; // Not enabled by default onto this page.
								}

								$sql = "INSERT INTO ".MAIN_DB_PREFIX."boxes (box_id, position, box_order, fk_user, entity)";
								$sql .= " VALUES (".((int) $lastid).", ".((int) $key2).", '0', 0, ".((int) $conf->entity).")";

								dol_syslog(get_class($this)."::insert_boxes onto page ".$key2."=".$val2, LOG_DEBUG);
								$resql = $this->db->query($sql);
								if (!$resql) {
									$err++;
								}
							}
						}

						if (!$err) {
							$this->db->commit();
						} else {
							$this->error = $this->db->lasterror();
							$this->db->rollback();
						}
					}
					// else box already registered into database
				} else {
					$this->error = $this->db->lasterror();
					$err++;
				}
			}
		}

		return $err;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Removes boxes
	 *
	 * @return int Error count (0 if OK)
	 */
	public function delete_boxes()
	{
		// phpcs:enable
		global $conf;

		$err = 0;

		if (is_array($this->boxes)) {
			foreach ($this->boxes as $key => $value) {
				//$titre = $this->boxes[$key][0];
				if (empty($this->boxes[$key]['file'])) {
					$file = isset($this->boxes[$key][1]) ? $this->boxes[$key][1] : ''; // For backward compatibility
				} else {
					$file = $this->boxes[$key]['file'];
				}

				//$note  = $this->boxes[$key][2];

				// TODO If the box is also included by another module and the other module is still on, we should not remove it.
				// For the moment, we manage this with hard coded exception
				//print "Remove box ".$file.'<br>';
				if ($file == 'box_graph_product_distribution.php') {
					if (isModEnabled("product") || isModEnabled("service")) {
						dol_syslog("We discard deleting module ".$file." because another module still active requires it.");
						continue;
					}
				}

				if ($this->db->type == 'sqlite3') {
					// sqlite doesn't support "USING" syntax.
					// TODO: remove this dependency.
					$sql = "DELETE FROM ".MAIN_DB_PREFIX."boxes ";
					$sql .= "WHERE ".MAIN_DB_PREFIX."boxes.box_id IN (";
					$sql .= "SELECT ".MAIN_DB_PREFIX."boxes_def.rowid ";
					$sql .= "FROM ".MAIN_DB_PREFIX."boxes_def ";
					$sql .= "WHERE ".MAIN_DB_PREFIX."boxes_def.file = '".$this->db->escape($file)."') ";
					$sql .= "AND ".MAIN_DB_PREFIX."boxes.entity = ".$conf->entity;
				} else {
					$sql = "DELETE FROM ".MAIN_DB_PREFIX."boxes";
					$sql .= " USING ".MAIN_DB_PREFIX."boxes, ".MAIN_DB_PREFIX."boxes_def";
					$sql .= " WHERE ".MAIN_DB_PREFIX."boxes.box_id = ".MAIN_DB_PREFIX."boxes_def.rowid";
					$sql .= " AND ".MAIN_DB_PREFIX."boxes_def.file = '".$this->db->escape($file)."'";
					$sql .= " AND ".MAIN_DB_PREFIX."boxes.entity = ".$conf->entity;
				}

				dol_syslog(get_class($this)."::delete_boxes", LOG_DEBUG);
				$resql = $this->db->query($sql);
				if (!$resql) {
					$this->error = $this->db->lasterror();
					$err++;
				}

				$sql = "DELETE FROM ".MAIN_DB_PREFIX."boxes_def";
				$sql .= " WHERE file = '".$this->db->escape($file)."'";
				$sql .= " AND entity = ".$conf->entity;		// Do not use getEntity here, we want to delete only in current company

				dol_syslog(get_class($this)."::delete_boxes", LOG_DEBUG);
				$resql = $this->db->query($sql);
				if (!$resql) {
					$this->error = $this->db->lasterror();
					$err++;
				}
			}
		}

		return $err;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Adds cronjobs
	 *
	 * @return int             Error count (0 if OK)
	 */
	public function insert_cronjobs()
	{
        // phpcs:enable
		include_once DOL_DOCUMENT_ROOT . '/core/class/infobox.class.php';
		include_once DOL_DOCUMENT_ROOT . '/cron/class/cronjob.class.php';

		global $conf, $user;

		$err = 0;

		if (is_array($this->cronjobs)) {
			dol_syslog(get_class($this) . "::insert_cronjobs", LOG_DEBUG);

			foreach ($this->cronjobs as $key => $value) {
				$now = dol_now();

				$entity = isset($value['entity']) ? $value['entity'] : $conf->entity;
				$label = isset($value['label']) ? $value['label'] : '';
				$jobtype = isset($value['jobtype']) ? $value['jobtype'] : '';
				$classesname = isset($value['class']) ? $value['class'] : '';
				$objectname = isset($value['objectname']) ? $value['objectname'] : '';
				$methodename = isset($value['method']) ? $value['method'] : '';
				$command = isset($value['command']) ? $value['command'] : '';
				$params = isset($value['parameters']) ? $value['parameters'] : '';
				$md5params = isset($value['md5params']) ? $value['md5params'] : '';
				$comment = isset($value['comment']) ? $value['comment'] : '';
				$frequency = isset($value['frequency']) ? $value['frequency'] : '';
				$unitfrequency = isset($value['unitfrequency']) ? $value['unitfrequency'] : '';
				$priority = isset($value['priority']) ? $value['priority'] : '';
				$datestart = isset($value['datestart']) ? $value['datestart'] : '';
				$dateend = isset($value['dateend']) ? $value['dateend'] : '';
				$datenextrun = isset($value['datenextrun']) ? $value['datenextrun'] : $now;
				$status = isset($value['status']) ? $value['status'] : '';
				$maxrun = isset($value['maxrun']) ? $value['maxrun'] : 0;
				$libname = isset($value['libname']) ? $value['libname'] : '';
				$test = isset($value['test']) ? $value['test'] : ''; // Line must be enabled or not (so visible or not)

				// Search if cron entry already present
				$sql = "SELECT count(*) as nb FROM " . MAIN_DB_PREFIX . "cronjob";
				//$sql .= " WHERE module_name = '" . $this->db->escape(empty($this->rights_class) ? strtolower($this->name) : $this->rights_class) . "'";
				$sql .= " WHERE label = '".$this->db->escape($label)."'";
				if ($classesname) {
					$sql .= " AND classesname = '" . $this->db->escape($classesname) . "'";
				}
				if ($objectname) {
					$sql .= " AND objectname = '" . $this->db->escape($objectname) . "'";
				}
				if ($methodename) {
					$sql .= " AND methodename = '" . $this->db->escape($methodename) . "'";
				}
				if ($command) {
					$sql .= " AND command = '" . $this->db->escape($command) . "'";
				}
				if ($params) {
					$sql .= " AND params = '" . $this->db->escape($params) . "'";
				}
				$sql .= " AND entity = " . ((int) $entity); // Must be exact entity

				$result = $this->db->query($sql);
				if (!$result) {
					$this->error = $this->db->lasterror();
					$err++;
					continue;
					// else box already registered into database
				}

				$obj = $this->db->fetch_object($result);
				if ($obj->nb > 0) {
					continue;
				}

				$cronjob = new Cronjob($this->db);

				$cronjob->entity = $entity;
				$cronjob->label = $label;
				$cronjob->jobtype = $jobtype;
				$cronjob->classesname = $classesname;
				$cronjob->objectname = $objectname;
				$cronjob->methodename = $methodename;
				$cronjob->command = $command;
				$cronjob->params = $params;
				$cronjob->md5params = $md5params;
				$cronjob->comment = $comment;
				$cronjob->frequency = $frequency;
				$cronjob->unitfrequency = $unitfrequency;
				$cronjob->priority = $priority;
				$cronjob->datestart = $datestart;
				$cronjob->dateend = $dateend;
				$cronjob->datenextrun = $datenextrun;
				$cronjob->maxrun = $maxrun;
				$cronjob->status = $status;
				$cronjob->test = $test;
				$cronjob->libname = $libname;
				$cronjob->module_name = empty($this->rights_class) ? strtolower($this->name) : $this->rights_class;

				$retCreate = $cronjob->create($user);

				if ($retCreate < 0) {
					$this->error = implode("\n", array_merge([$cronjob->error], $cronjob->errors));
					return -1;
				}
			}
		}

		return $err;
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Removes boxes
	 *
	 * @return int Error count (0 if OK)
	 */
	public function delete_cronjobs()
	{
		// phpcs:enable
		global $conf;

		$err = 0;

		if (is_array($this->cronjobs)) {
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."cronjob";
			$sql .= " WHERE module_name = '".$this->db->escape(empty($this->rights_class) ? strtolower($this->name) : $this->rights_class)."'";
			$sql .= " AND entity = ".$conf->entity;
			$sql .= " AND test = '1'"; // We delete on lines that are not set with a complete test that is '$conf->module->enabled' so when module is disabled, the cron is also removed.
			// For crons declared with a '$conf->module->enabled', there is no need to delete the line, so we don't loose setup if we reenable module.

			dol_syslog(get_class($this)."::delete_cronjobs", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				$this->error = $this->db->lasterror();
				$err++;
			}
		}

		return $err;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Removes tabs
	 *
	 * @return int Error count (0 if OK)
	 */
	public function delete_tabs()
	{
		// phpcs:enable
		global $conf;

		$err = 0;

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."const";
		$sql .= " WHERE ".$this->db->decrypt('name')." like '".$this->db->escape($this->const_name)."_TABS_%'";
		$sql .= " AND entity = ".$conf->entity;

		dol_syslog(get_class($this)."::delete_tabs", LOG_DEBUG);
		if (!$this->db->query($sql)) {
			$this->error = $this->db->lasterror();
			$err++;
		}

		return $err;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Adds tabs
	 *
	 * @return int  Error count (0 if ok)
	 */
	public function insert_tabs()
	{
		// phpcs:enable
		global $conf;

		$err = 0;

		if (!empty($this->tabs)) {
			dol_syslog(get_class($this)."::insert_tabs", LOG_DEBUG);

			$i = 0;
			foreach ($this->tabs as $key => $value) {
				if (is_array($value) && count($value) == 0) {
					continue; // Discard empty arrays
				}

				$entity = $conf->entity;
				$newvalue = $value;

				if (is_array($value)) {
					$newvalue = $value['data'];
					if (isset($value['entity'])) {
						$entity = $value['entity'];
					}
				}

				if ($newvalue) {
					$sql = "INSERT INTO ".MAIN_DB_PREFIX."const (";
					$sql .= "name";
					$sql .= ", type";
					$sql .= ", value";
					$sql .= ", note";
					$sql .= ", visible";
					$sql .= ", entity";
					$sql .= ")";
					$sql .= " VALUES (";
					$sql .= $this->db->encrypt($this->const_name."_TABS_".$i);
					$sql .= ", 'chaine'";
					$sql .= ", ".$this->db->encrypt($newvalue);
					$sql .= ", null";
					$sql .= ", '0'";
					$sql .= ", ".((int) $entity);
					$sql .= ")";

					$resql = $this->db->query($sql);
					if (!$resql) {
						dol_syslog($this->db->lasterror(), LOG_ERR);
						if ($this->db->lasterrno() != 'DB_ERROR_RECORD_ALREADY_EXISTS') {
							$this->error = $this->db->lasterror();
							$this->errors[] = $this->db->lasterror();
							$err++;
							break;
						}
					}
				}
				$i++;
			}
		}
		return $err;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Adds constants
	 *
	 * @return int Error count (0 if OK)
	 */
	public function insert_const()
	{
		// phpcs:enable
		global $conf;

		$err = 0;

		if (empty($this->const)) {
			return 0;
		}

		dol_syslog(__METHOD__, LOG_DEBUG);

		foreach ($this->const as $key => $value) {
			$name      = $this->const[$key][0];
			$type      = $this->const[$key][1];
			$val       = $this->const[$key][2];
			$note      = isset($this->const[$key][3]) ? $this->const[$key][3] : '';
			$visible   = isset($this->const[$key][4]) ? $this->const[$key][4] : 0;
			$entity    = (!empty($this->const[$key][5]) && $this->const[$key][5] != 'current') ? 0 : $conf->entity;

			// Clean
			if (empty($visible)) {
				$visible = '0';
			}
			if (empty($val) && $val != '0') {
				$val = '';
			}

			$sql = "SELECT count(*) as nb";
			$sql .= " FROM ".MAIN_DB_PREFIX."const";
			$sql .= " WHERE ".$this->db->decrypt('name')." = '".$this->db->escape($name)."'";
			$sql .= " AND entity = ".((int) $entity);

			$result = $this->db->query($sql);
			if ($result) {
				$row = $this->db->fetch_row($result);

				if ($row[0] == 0) {   // If not found
					$sql = "INSERT INTO ".MAIN_DB_PREFIX."const (name,type,value,note,visible,entity)";
					$sql .= " VALUES (";
					$sql .= $this->db->encrypt($name);
					$sql .= ",'".$this->db->escape($type)."'";
					$sql .= ",".(($val != '') ? $this->db->encrypt($val) : "''");
					$sql .= ",".($note ? "'".$this->db->escape($note)."'" : "null");
					$sql .= ",'".$this->db->escape($visible)."'";
					$sql .= ",".$entity;
					$sql .= ")";

					if (!$this->db->query($sql)) {
						$err++;
					}
				} else {
					dol_syslog(__METHOD__." constant '".$name."' already exists", LOG_DEBUG);
				}
			} else {
				$err++;
			}
		}

		return $err;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Removes constants tagged 'deleteonunactive'
	 *
	 * @return int Return integer <0 if KO, 0 if OK
	 */
	public function delete_const()
	{
		// phpcs:enable
		global $conf;

		$err = 0;

		if (empty($this->const)) {
			return 0;
		}

		foreach ($this->const as $key => $value) {
			$name = $this->const[$key][0];
			$deleteonunactive = (!empty($this->const[$key][6])) ? 1 : 0;

			if ($deleteonunactive) {
				$sql = "DELETE FROM ".MAIN_DB_PREFIX."const";
				$sql .= " WHERE ".$this->db->decrypt('name')." = '".$this->db->escape($name)."'";
				$sql .= " AND entity in (0, ".$conf->entity.")";
				dol_syslog(get_class($this)."::delete_const", LOG_DEBUG);
				if (!$this->db->query($sql)) {
					$this->error = $this->db->lasterror();
					$err++;
				}
			}
		}

		return $err;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Adds access rights
	 *
	 * @param  int $reinitadminperms 	If 1, we also grant them to all admin users
	 * @param  int $force_entity     	Force current entity
	 * @param  int $notrigger        	1=Does not execute triggers, 0= execute triggers
	 * @return int                     	Error count (0 if OK)
	 */
	public function insert_permissions($reinitadminperms = 0, $force_entity = null, $notrigger = 0)
	{
		// phpcs:enable
		global $conf, $user;

		$err = 0;
		$entity = (!empty($force_entity) ? $force_entity : $conf->entity);

		dol_syslog(get_class($this)."::insert_permissions", LOG_DEBUG);

		// Test if module is activated
		$sql_del = "SELECT ".$this->db->decrypt('value')." as value";
		$sql_del .= " FROM ".MAIN_DB_PREFIX."const";
		$sql_del .= " WHERE ".$this->db->decrypt('name')." = '".$this->db->escape($this->const_name)."'";
		$sql_del .= " AND entity IN (0,".((int) $entity).")";

		$resql = $this->db->query($sql_del);

		if ($resql) {
			$obj = $this->db->fetch_object($resql);

			if ($obj !== null && !empty($obj->value) && !empty($this->rights)) {
				include_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';

				// TODO rights parameters with integer indexes are deprecated
				// $this->rights[$key][0] = $this->rights[$key][self::KEY_ID]
				// $this->rights[$key][1] = $this->rights[$key][self::KEY_LABEL]
				// $this->rights[$key][3] = $this->rights[$key][self::KEY_DEFAULT]
				// $this->rights[$key][4] = $this->rights[$key][self::KEY_FIRST_LEVEL]
				// $this->rights[$key][5] = $this->rights[$key][self::KEY_SECOND_LEVEL]

				// new parameters
				// $this->rights[$key][self::KEY_MODULE]	// possibility to define user right for an another module (default: current module name)
				// $this->rights[$key][self::KEY_ENABLED]	// condition to show or hide a user right (default: 1) (eg isModEnabled('anothermodule'))

				// If the module is active
				foreach ($this->rights as $key => $value) {
					$r_id = $this->rights[$key][self::KEY_ID];	// permission id in llx_rights_def (not unique because primary key is couple id-entity)
					$r_label = $this->rights[$key][self::KEY_LABEL];
					$r_type	= $this->rights[$key][self::KEY_TYPE] ?? 'w';	// TODO deprecated
					$r_default = $this->rights[$key][self::KEY_DEFAULT] ?? 0;
					$r_perms = $this->rights[$key][self::KEY_FIRST_LEVEL] ?? '';
					$r_subperms = $this->rights[$key][self::KEY_SECOND_LEVEL] ?? '';

					// KEY_FIRST_LEVEL (perms) must not be empty
					if (empty($r_perms)) {
						continue;
					}

					// name of module (default: current module name)
					$r_module = (empty($this->rights_class) ? strtolower($this->name) : $this->rights_class);

					// name of the module from which the right comes (default: empty means same module the permission is for)
					$r_module_origin = '';

					if (isset($this->rights[$key][self::KEY_MODULE])) {
						// name of the module to which the right must be applied
						$r_module = $this->rights[$key][self::KEY_MODULE];
						// name of the module from which the right comes
						$r_module_origin = (empty($this->rights_class) ? strtolower($this->name) : $this->rights_class);
					}

					// condition to show or hide a user right (default: 1) (eg isModEnabled('anothermodule') or ($conf->global->MAIN_FEATURES_LEVEL > 0) or etc..)
					$r_enabled	= $this->rights[$key][self::KEY_ENABLED] ?? '1';

					// Search if perm already present
					$sql = "SELECT count(*) as nb FROM ".MAIN_DB_PREFIX."rights_def";
					$sql .= " WHERE entity = ".((int) $entity);
					$sql .= " AND id = ".((int) $r_id);

					$resqlselect = $this->db->query($sql);
					if ($resqlselect) {
						$objcount = $this->db->fetch_object($resqlselect);
						if ($objcount && $objcount->nb == 0) {
							$sql = "INSERT INTO ".MAIN_DB_PREFIX."rights_def (";
							$sql .= "id";
							$sql .= ", entity";
							$sql .= ", libelle";
							$sql .= ", module";
							$sql .= ", module_origin";
							$sql .= ", type";	// TODO deprecated
							$sql .= ", bydefault";
							$sql .= ", perms";
							$sql .= ", subperms";
							$sql .= ", enabled";
							$sql .= ") VALUES (";
							$sql .= ((int) $r_id);
							$sql .= ", ".((int) $entity);
							$sql .= ", '".$this->db->escape($r_label)."'";
							$sql .= ", '".$this->db->escape($r_module)."'";
							$sql .= ", '".$this->db->escape($r_module_origin)."'";
							$sql .= ", '".$this->db->escape($r_type)."'";	// TODO deprecated
							$sql .= ", ".((int) $r_default);
							$sql .= ", '".$this->db->escape($r_perms)."'";
							$sql .= ", '".$this->db->escape($r_subperms)."'";
							$sql .= ", '".$this->db->escape($r_enabled)."'";
							$sql.= ")";

							$resqlinsert = $this->db->query($sql, 1);

							if (!$resqlinsert) {
								if ($this->db->errno() != "DB_ERROR_RECORD_ALREADY_EXISTS") {
									$this->error = $this->db->lasterror();
									$err++;
									break;
								} else {
									dol_syslog(get_class($this)."::insert_permissions record already exists", LOG_INFO);
								}
							}

							$this->db->free($resqlinsert);
						}

						$this->db->free($resqlselect);
					}

					// If we want to init permissions on admin users
					if (!empty($reinitadminperms)) {
						$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."user WHERE admin = 1";
						dol_syslog(get_class($this)."::insert_permissions Search all admin users", LOG_DEBUG);

						$resqlseladmin = $this->db->query($sql, 1);

						if ($resqlseladmin) {
							$num = $this->db->num_rows($resqlseladmin);
							$i = 0;
							while ($i < $num) {
								$obj2 = $this->db->fetch_object($resqlseladmin);
								dol_syslog(get_class($this)."::insert_permissions Add permission id ".$r_id." to user id=".$obj2->rowid);

								$tmpuser = new User($this->db);
								$result = $tmpuser->fetch($obj2->rowid);
								if ($result > 0) {
									$tmpuser->addrights($r_id, '', '', 0, 1);
								} else {
									dol_syslog(get_class($this)."::insert_permissions Failed to add the permission to user because fetch return an error", LOG_ERR);
								}
								$i++;
							}
						} else {
							dol_print_error($this->db);
						}
					}
				}

				if (!empty($reinitadminperms) && !empty($user->admin)) {  // Reload permission for current user if defined
					// We reload permissions
					$user->clearrights();
					$user->loadRights();
				}
			}
			$this->db->free($resql);
		} else {
			$this->error = $this->db->lasterror();
			$err++;
		}

		return $err;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Removes access rights
	 *
	 * @return int                     Error count (0 if OK)
	 */
	public function delete_permissions()
	{
		// phpcs:enable
		global $conf;

		$err = 0;

		$module = empty($this->rights_class) ? strtolower($this->name) : $this->rights_class;

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."rights_def";
		$sql .= " WHERE (module = '".$this->db->escape($module)."' OR module_origin = '".$this->db->escape($module)."')";

		// Delete all entities if core module
		if (empty($this->core_enabled)) {
			$sql .= " AND entity = ".((int) $conf->entity);
		}

		dol_syslog(get_class($this)."::delete_permissions", LOG_DEBUG);
		if (!$this->db->query($sql)) {
			$this->error = $this->db->lasterror();
			$err++;
		}

		return $err;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Adds menu entries
	 *
	 * @return int     Error count (0 if OK)
	 */
	public function insert_menus()
	{
		// phpcs:enable
		global $conf, $user;

		if (!is_array($this->menu) || empty($this->menu)) {
			return 0;
		}

		include_once DOL_DOCUMENT_ROOT.'/core/class/menubase.class.php';

		dol_syslog(get_class($this)."::insert_menus", LOG_DEBUG);

		$err = 0;

		// Common module
		$entity = ((!empty($this->always_enabled) || !empty($this->core_enabled)) ? 0 : $conf->entity);

		$this->db->begin();

		foreach ($this->menu as $key => $value) {
			$menu = new Menubase($this->db);
			$menu->menu_handler = 'all';

			//$menu->module=strtolower($this->name);    TODO When right_class will be same than module name
			$menu->module = (empty($this->rights_class) ? strtolower($this->name) : $this->rights_class);

			if (!$this->menu[$key]['fk_menu']) {
				$menu->fk_menu = 0;
			} else {
				$foundparent = 0;
				$fk_parent = $this->menu[$key]['fk_menu'];
				$reg = array();
				if (preg_match('/^r=/', $fk_parent)) {    // old deprecated method
					$fk_parent = str_replace('r=', '', $fk_parent);
					if (isset($this->menu[$fk_parent]['rowid'])) {
						$menu->fk_menu = $this->menu[$fk_parent]['rowid'];
						$foundparent = 1;
					}
				} elseif (preg_match('/^fk_mainmenu=([a-zA-Z0-9_]+),fk_leftmenu=([a-zA-Z0-9_]+)$/', $fk_parent, $reg)) {
					$menu->fk_menu = -1;
					$menu->fk_mainmenu = $reg[1];
					$menu->fk_leftmenu = $reg[2];
					$foundparent = 1;
				} elseif (preg_match('/^fk_mainmenu=([a-zA-Z0-9_]+)$/', $fk_parent, $reg)) {
					$menu->fk_menu = -1;
					$menu->fk_mainmenu = $reg[1];
					$menu->fk_leftmenu = '';
					$foundparent = 1;
				}
				if (!$foundparent) {
					$this->error = "ErrorBadDefinitionOfMenuArrayInModuleDescriptor";
					dol_syslog(get_class($this)."::insert_menus ".$this->error." ".$this->menu[$key]['fk_menu'], LOG_ERR);
					$err++;
				}
			}
			$menu->type = $this->menu[$key]['type'];
			$menu->mainmenu = isset($this->menu[$key]['mainmenu']) ? $this->menu[$key]['mainmenu'] : (isset($menu->fk_mainmenu) ? $menu->fk_mainmenu : '');
			$menu->leftmenu = isset($this->menu[$key]['leftmenu']) ? $this->menu[$key]['leftmenu'] : '';
			$menu->title = $this->menu[$key]['titre'];
			$menu->prefix = isset($this->menu[$key]['prefix']) ? $this->menu[$key]['prefix'] : '';
			$menu->url = $this->menu[$key]['url'];
			$menu->langs = isset($this->menu[$key]['langs']) ? $this->menu[$key]['langs'] : '';
			$menu->position = $this->menu[$key]['position'];
			$menu->perms = $this->menu[$key]['perms'];
			$menu->target = isset($this->menu[$key]['target']) ? $this->menu[$key]['target'] : '';
			$menu->user = $this->menu[$key]['user'];
			$menu->enabled = isset($this->menu[$key]['enabled']) ? $this->menu[$key]['enabled'] : 0;
			$menu->position = $this->menu[$key]['position'];
			$menu->entity = $entity;

			if (!$err) {
				$result = $menu->create($user); // Save menu entry into table llx_menu
				if ($result > 0) {
					$this->menu[$key]['rowid'] = $result;
				} else {
					$this->error = $menu->error;
					dol_syslog(get_class($this).'::insert_menus result='.$result." ".$this->error, LOG_ERR);
					$err++;
					break;
				}
			}
		}

		if (!$err) {
			$this->db->commit();
		} else {
			dol_syslog(get_class($this)."::insert_menus ".$this->error, LOG_ERR);
			$this->db->rollback();
		}

		return $err;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Removes menu entries
	 *
	 * @return int Error count (0 if OK)
	 */
	public function delete_menus()
	{
		// phpcs:enable
		global $conf;

		$err = 0;

		//$module=strtolower($this->name);        TODO When right_class will be same than module name
		$module = empty($this->rights_class) ? strtolower($this->name) : $this->rights_class;

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."menu";
		$sql .= " WHERE module = '".$this->db->escape($module)."'";
		$sql .= " AND menu_handler = 'all'";	// We delete only lines that were added manually or by the module activation. We keep entry added by menuhandler like 'auguria'
		$sql .= " AND entity IN (0, ".$conf->entity.")";

		dol_syslog(get_class($this)."::delete_menus", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = $this->db->lasterror();
			$err++;
		}

		return $err;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Creates directories
	 *
	 * @return int Error count (0 if OK)
	 */
	public function create_dirs()
	{
		// phpcs:enable
		global $langs, $conf;

		$err = 0;
		$name = '';

		if (isset($this->dirs) && is_array($this->dirs)) {
			foreach ($this->dirs as $key => $value) {
				$addtodatabase = 0;

				if (!is_array($value)) {
					$dir = $value; // Default simple mode
				} else {
					$constname = $this->const_name."_DIR_";
					$dir       = $this->dirs[$key][1];
					$addtodatabase = empty($this->dirs[$key][2]) ? '' : $this->dirs[$key][2]; // Create constante in llx_const
					$subname   = empty($this->dirs[$key][3]) ? '' : strtoupper($this->dirs[$key][3]); // Add submodule name (ex: $conf->module->submodule->dir_output)
					$forcename = empty($this->dirs[$key][4]) ? '' : strtoupper($this->dirs[$key][4]); // Change the module name if different

					if (!empty($forcename)) {
						$constname = 'MAIN_MODULE_'.$forcename."_DIR_";
					}
					if (!empty($subname)) {
						$constname = $constname.$subname."_";
					}

					$name = $constname.strtoupper($this->dirs[$key][0]);
				}

				// Define directory full path ($dir must start with "/")
				if (!getDolGlobalString('MAIN_MODULE_MULTICOMPANY') || $conf->entity == 1) {
					$fulldir = DOL_DATA_ROOT.$dir;
				} else {
					$fulldir = DOL_DATA_ROOT."/".$conf->entity.$dir;
				}
				// Create dir if it does not exists
				if (!empty($fulldir) && !file_exists($fulldir)) {
					if (dol_mkdir($fulldir, DOL_DATA_ROOT) < 0) {
						$this->error = $langs->trans("ErrorCanNotCreateDir", $fulldir);
						dol_syslog(get_class($this)."::_init ".$this->error, LOG_ERR);
						$err++;
					}
				}

				// Define the constant in database if requested (not the default mode)
				if (!empty($addtodatabase) && !empty($name)) {
					$result = $this->insert_dirs($name, $dir);
					if ($result) {
						$err++;
					}
				}
			}
		}

		return $err;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Adds directories definitions
	 *
	 * @param string $name Name
	 * @param string $dir  Directory
	 *
	 * @return int             Error count (0 if OK)
	 */
	public function insert_dirs($name, $dir)
	{
		// phpcs:enable
		global $conf;

		$err = 0;

		$sql = "SELECT count(*)";
		$sql .= " FROM ".MAIN_DB_PREFIX."const";
		$sql .= " WHERE ".$this->db->decrypt('name')." = '".$this->db->escape($name)."'";
		$sql .= " AND entity = ".$conf->entity;

		dol_syslog(get_class($this)."::insert_dirs", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {
			$row = $this->db->fetch_row($result);

			if ($row[0] == 0) {
				$sql = "INSERT INTO ".MAIN_DB_PREFIX."const (name, type, value, note, visible, entity)";
				$sql .= " VALUES (".$this->db->encrypt($name).", 'chaine', ".$this->db->encrypt($dir).", '".$this->db->escape("Directory for module ".$this->name)."', '0', ".((int) $conf->entity).")";

				dol_syslog(get_class($this)."::insert_dirs", LOG_DEBUG);
				$this->db->query($sql);
			}
		} else {
			$this->error = $this->db->lasterror();
			$err++;
		}

		return $err;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Removes directories
	 *
	 * @return int Error count (0 if OK)
	 */
	public function delete_dirs()
	{
		// phpcs:enable
		global $conf;

		$err = 0;

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."const";
		$sql .= " WHERE ".$this->db->decrypt('name')." LIKE '".$this->db->escape($this->const_name)."_DIR_%'";
		$sql .= " AND entity = ".$conf->entity;

		dol_syslog(get_class($this)."::delete_dirs", LOG_DEBUG);
		if (!$this->db->query($sql)) {
			$this->error = $this->db->lasterror();
			$err++;
		}

		return $err;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Save configuration for generic features.
	 * This also generate website templates if the module provide some.
	 *
	 * @return int Error count (0 if OK)
	 */
	public function insert_module_parts()
	{
		// phpcs:enable
		global $conf, $langs;

		$error = 0;

		if (is_array($this->module_parts)) {
			if (empty($this->module_parts['icon']) && !empty($this->picto) && preg_match('/^fa\-/', $this->picto)) {
				$this->module_parts['icon'] = $this->picto;
			}

			foreach ($this->module_parts as $key => $value) {
				if (is_array($value) && count($value) == 0) {
					continue; // Discard empty arrays
				}

				// If module brings website templates, we must generate the zip like we do whenenabling the website module
				if ($key == 'websitetemplates' && $value == 1) {
					$srcroot = dol_buildpath('/'.strtolower($this->name).'/doctemplates/websites');

					// Copy templates in dir format (recommended) into zip file
					$docs = dol_dir_list($srcroot, 'directories', 0, 'website_.*$');
					foreach ($docs as $cursorfile) {
						$src = $srcroot.'/'.$cursorfile['name'];
						$dest = DOL_DATA_ROOT.'/doctemplates/websites/'.$cursorfile['name'];

						dol_delete_file($dest.'.zip');

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

					// Copy also the preview website_xxx.jpg file
					$docs = dol_dir_list($srcroot, 'files', 0, 'website_.*\.jpg$');
					foreach ($docs as $cursorfile) {
						$src = $srcroot.'/'.$cursorfile['name'];
						$dest = DOL_DATA_ROOT.'/doctemplates/websites/'.$cursorfile['name'];

						dol_copy($src, $dest);
					}
				}

				$entity = $conf->entity; // Reset the current entity
				$newvalue = $value;

				// Serialize array parameters
				if (is_array($value)) {
					// Can defined other parameters
					// Example when $key='hooks', then $value is an array('data'=>array('hookcontext1','hookcontext2'), 'entity'=>X)
					if (isset($value['data']) && is_array($value['data'])) {
						$newvalue = json_encode($value['data']);
						if (isset($value['entity'])) {
							$entity = $value['entity'];
						}
					} elseif (isset($value['data']) && !is_array($value['data'])) {
						$newvalue = $value['data'];
						if (isset($value['entity'])) {
							$entity = $value['entity'];
						}
					} else { // when hook is declared with syntax 'hook'=>array('hookcontext1','hookcontext2',...)
						$newvalue = json_encode($value);
					}
				}

				if (!empty($newvalue)) {
					$sql = "INSERT INTO ".MAIN_DB_PREFIX."const (";
					$sql .= "name";
					$sql .= ", type";
					$sql .= ", value";
					$sql .= ", note";
					$sql .= ", visible";
					$sql .= ", entity";
					$sql .= ")";
					$sql .= " VALUES (";
					$sql .= " ".$this->db->encrypt($this->const_name."_".strtoupper($key), 1);
					$sql .= ", 'chaine'";
					$sql .= ", ".$this->db->encrypt($newvalue, 1);
					$sql .= ", null";
					$sql .= ", '0'";
					$sql .= ", ".((int) $entity);
					$sql .= ")";

					dol_syslog(get_class($this)."::insert_module_parts for key=".$this->const_name."_".strtoupper($key), LOG_DEBUG);

					$resql = $this->db->query($sql, 1);
					if (!$resql) {
						if ($this->db->lasterrno() != 'DB_ERROR_RECORD_ALREADY_EXISTS') {
							$error++;
							$this->error = $this->db->lasterror();
						} else {
							dol_syslog(get_class($this)."::insert_module_parts for ".$this->const_name."_".strtoupper($key)." Record already exists.", LOG_WARNING);
						}
					}
				}
			}
		}
		return $error;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Removes generic parts
	 *
	 * @return int Error count (0 if OK)
	 */
	public function delete_module_parts()
	{
		// phpcs:enable
		global $conf;

		$err = 0;

		if (is_array($this->module_parts)) {
			dol_syslog(get_class($this)."::delete_module_parts", LOG_DEBUG);

			if (empty($this->module_parts['icon']) && !empty($this->picto) && preg_match('/^fa\-/', $this->picto)) {
				$this->module_parts['icon'] = $this->picto;
			}

			foreach ($this->module_parts as $key => $value) {
				// If entity is defined
				if (is_array($value) && isset($value['entity'])) {
					$entity = $value['entity'];
				} else {
					$entity = $conf->entity;
				}

				$sql = "DELETE FROM ".MAIN_DB_PREFIX."const";
				$sql .= " WHERE ".$this->db->decrypt('name')." LIKE '".$this->db->escape($this->const_name)."_".strtoupper($key)."'";
				$sql .= " AND entity = ".((int) $entity);

				if (!$this->db->query($sql)) {
					$this->error = $this->db->lasterror();
					$err++;
				}
			}
		}
		return $err;
	}

	/**
	 * Function called when module is enabled.
	 * The init function adds tabs, constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 * It also creates data directories
	 *
	 * @param  string $options Options when enabling module ('', 'newboxdefonly', 'noboxes', 'menuonly')
	 *                         'noboxes' = Do not insert boxes 'newboxdefonly' = For boxes, insert def of boxes only and not boxes activation
	 * @return int                1 if OK, 0 if KO
	 */
	public function init($options = '')
	{
		return $this->_init(array(), $options);
	}

	/**
	 * Function called when module is disabled.
	 * The remove function removes tabs, constants, boxes, permissions and menus from Dolibarr database.
	 * Data directories are not deleted
	 *
	 * @param  string $options Options when enabling module ('', 'noboxes')
	 * @return int                     1 if OK, 0 if KO
	 */
	public function remove($options = '')
	{
		return $this->_remove(array(), $options);
	}


	/**
	 * Return Kanban view of a module
	 *
	 * @param	string	$codeenabledisable		HTML code for button to enable/disable module
	 * @param	string	$codetoconfig			HTML code to go to config page
	 * @return 	string							HTML code of Kanban view
	 */
	public function getKanbanView($codeenabledisable = '', $codetoconfig = '')
	{
		global $langs;

		// Define imginfo
		$imginfo = "info";
		if ($this->isCoreOrExternalModule() == 'external') {
			$imginfo = "info_black";
		}

		$const_name = 'MAIN_MODULE_'.strtoupper(preg_replace('/^mod/i', '', get_class($this)));

		$version = $this->getVersion(0);
		$versiontrans = '';
		if (preg_match('/development/i', $version)) {
			$versiontrans .= 'warning';
		}
		if (preg_match('/experimental/i', $version)) {
			$versiontrans .= 'warning';
		}
		if (preg_match('/deprecated/i', $version)) {
			$versiontrans .= 'warning';
		}

		$return = '
    	<div class="box-flex-item info-box-module'
			.(getDolGlobalString($const_name) ? '' : ' --disabled')
			.($this->isCoreOrExternalModule() == 'external' ? ' --external' : '')
			.($this->needUpdate ? ' --need-update' : '')
			.'">
	    <div class="info-box info-box-sm info-box-module">
	    <div class="info-box-icon'.(!getDolGlobalString($const_name) ? '' : ' info-box-icon-module-enabled'.($versiontrans ? ' info-box-icon-module-warning' : '')).'">';

		$alttext = '';
		//if (is_array($objMod->need_dolibarr_version)) $alttext.=($alttext?' - ':'').'Dolibarr >= '.join('.',$objMod->need_dolibarr_version);
		//if (is_array($objMod->phpmin)) $alttext.=($alttext?' - ':'').'PHP >= '.join('.',$objMod->phpmin);
		if (!empty($this->picto)) {
			if (preg_match('/^\//i', $this->picto)) {
				$return .= img_picto($alttext, $this->picto, 'class="inline-block valignmiddle"', 1);
			} else {
				$return .= img_object($alttext, $this->picto, 'class="inline-block valignmiddle"');
			}
		} else {
			$return .= img_object($alttext, 'generic', 'class="inline-block valignmiddle"');
		}

		if ($this->isCoreOrExternalModule() == 'external' || preg_match('/development|experimental|deprecated/i', $version)) {
			$versionTitle =  $langs->trans("Version").' '.$this->getVersion(1);
			if ($this->needUpdate) {
				$versionTitle .= '<br>'.$langs->trans('ModuleUpdateAvailable').' : '.$this->lastVersion;
			}

			$return .=  '<span class="info-box-icon-version'.($versiontrans ? ' '.$versiontrans : '').' classfortooltip" title="'.dol_escape_js($versionTitle).'" >';
			$return .=  $this->getVersion(1);
			$return .=  '</span>';
		}

		$return .=  '</div>
	    <div class="info-box-content info-box-text-module'.(!getDolGlobalString($const_name) ? '' : ' info-box-module-enabled'.($versiontrans ? ' info-box-content-warning' : '')).'">
	    <span class="info-box-title">'.$this->getName().'</span>
	    <span class="info-box-desc twolinesmax opacitymedium" title="'.dol_escape_htmltag($this->getDesc()).'">'.nl2br($this->getDesc()).'</span>';

		$return .=  '<div class="valignmiddle inline-block info-box-more">';
		//if ($versiontrans) print img_warning($langs->trans("Version").' '.$this->getVersion(1)).' ';
		$return .=  '<a class="valignmiddle inline-block" href="javascript:document_preview(\''.DOL_URL_ROOT.'/admin/modulehelp.php?id='.((int) $this->numero).'\',\'text/html\',\''.dol_escape_js($langs->trans("Module")).'\')">'.img_picto(($this->isCoreOrExternalModule() == 'external' ? $langs->trans("ExternalModule").' - ' : '').$langs->trans("ClickToShowDescription"), $imginfo).'</a>';
		$return .=  '</div><br>';

		$return .=  '<div class="valignmiddle inline-block info-box-actions">';
		$return .=  '<div class="valignmiddle inline-block info-box-setup">';
		$return .=  $codetoconfig;
		$return .=  '</div>';
		$return .=  '<div class="valignmiddle inline-block marginleftonly marginrightonly">';
		$return .=  $codeenabledisable;
		$return .=  '</div>';
		$return .=  '</div>';

		$return .=  '
	    </div><!-- /.info-box-content -->
	    </div><!-- /.info-box -->
	    </div>';

		return $return;
	}

	/**
	 * Check for module update.
	 * Get URL content of $this->url_last_version and set $this->lastVersion and$this->needUpdate
	 * TODO Store result in DB.
	 * TODO Add a cron task to monitor for updates.
	 *
	 * @return int Return integer <0 if Error, 0 == no update needed,  >0 if need update
	 */
	public function checkForUpdate()
	{
		require_once DOL_DOCUMENT_ROOT.'/core/lib/geturl.lib.php';
		if (!empty($this->url_last_version)) {
			$lastVersion = getURLContent($this->url_last_version, 'GET', '', 1, array(), array('http', 'https'), 0);	// Accept http or https links on external remote server only
			if (isset($lastVersion['content']) && strlen($lastVersion['content']) < 30) {
				// Security warning :  be careful with remote data content, the module editor could be hacked (or evil) so limit to a-z A-Z 0-9 _ . -
				$this->lastVersion = preg_replace("/[^a-zA-Z0-9_\.\-]+/", "", $lastVersion['content']);
				if (version_compare($this->lastVersion, $this->version) > 0) {
					$this->needUpdate = true;
					return 1;
				} else {
					$this->needUpdate = false;
					return 0;
				}
			} else {
				return -1;
			}
		}
		return 0;
	}

	/**
	 * Helper method to declare dictionaries one at a time (rather than declaring dictionaries property by property).
	 *
	 * @param array $dictionaryArray Array describing one dictionary. Keys are:
	 *                               'name',        table name (without prefix)
	 *                               'lib',         dictionary label
	 *                               'sql',         query for select
	 *                               'sqlsort',     sort order
	 *                               'field',       comma-separated list of fields to select
	 *                               'fieldvalue',  list of columns used for editing existing rows
	 *                               'fieldinsert', list of columns used for inserting new rows
	 *                               'rowid',       name of the technical ID (primary key) column, usually 'rowid'
	 *                               'cond',        condition for the dictionary to be shown / active
	 *                               'help',        optional array of translation keys by column for tooltips
	 *                               'fieldcheck'   (appears to be unused)
	 * @param string $langs Optional translation file to include (appears to be unused)
	 * @return void
	 */
	protected function declareNewDictionary($dictionaryArray, $langs = '')
	{
		$fields = array('name', 'lib', 'sql', 'sqlsort', 'field', 'fieldvalue', 'fieldinsert', 'rowid', 'cond', 'help', 'fieldcheck');

		foreach ($fields as $field) {
			if (isset($dictionaryArray[$field])) {
				$this->dictionaries['tab'.$field][] = $dictionaryArray[$field];
			}
		}
		if ($langs && !in_array($langs, $this->dictionaries[$langs])) {
			$this->dictionaries['langs'][] = $langs;
		}
	}
}
