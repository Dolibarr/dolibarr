<?php
/* Copyright (C) 2025 Florian Hödl <florian@hoedl.co>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \defgroup   indexadjustment     Module IndexAdjustment
 * \brief      Index Adjustment module descriptor.
 *
 * \file       htdocs/indexadjustment/core/modules/modIndexAdjustment.class.php
 * \ingroup    indexadjustment
 * \brief      Description and activation file for module IndexAdjustment
 */
include_once DOL_DOCUMENT_ROOT . '/core/modules/DolibarrModules.class.php';

/**
 * Description and activation class for module IndexAdjustment
 *
 * Handles transparent contract index adjustments based on Austrian VPI (Verbraucherpreisindex).
 * Allows batch price adjustments for contract lines with full audit trail.
 */
class modIndexAdjustment extends DolibarrModules
{
	/**
	 * Constructor. Define names, constants, directories, boxes, permissions
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		global $langs, $conf;
		$this->db = $db;

		// Module ID (must be unique)
		// See https://wiki.dolibarr.org/index.php/List_of_modules_id
		$this->numero = 510100;

		// Key text used to identify module (for permissions, menus, etc...)
		$this->rights_class = 'indexadjustment';

		// Family: 'crm','financial','hr','projects','products','ecm','technic','interface','other'
		$this->family = "financial";

		// Module position in the family
		$this->module_position = '50';

		// Module label (no space allowed)
		$this->name = preg_replace('/^mod/i', '', get_class($this));

		// Module description
		$this->description = "Transparent contract index adjustments based on Austrian VPI (Verbraucherpreisindex)";
		$this->descriptionlong = "Allows batch price adjustments for contract service lines with full audit trail. "
			. "Supports VPI-based calculations, threshold filtering, rollback capability, and detailed event documentation.";

		// Author
		$this->editor_name = 'Anexum GmbH';
		$this->editor_url = 'https://www.anexum.at';

		// Version
		$this->version = '1.0.0';

		// Key used in llx_const table
		$this->const_name = 'MAIN_MODULE_' . strtoupper($this->name);

		// Module icon
		$this->picto = 'fa-percent';

		// Module parts
		$this->module_parts = array(
			'triggers' => 0,
			'login' => 0,
			'substitutions' => 0,
			'menus' => 0,
			'tpl' => 0,
			'barcode' => 0,
			'models' => 0,
			'printing' => 0,
			'theme' => 0,
			'css' => array(),
			'js' => array(),
			'hooks' => array(
				'data' => array(
					'contractcard',
				),
				'entity' => '0',
			),
			'moduleforexternal' => 0,
		);

		// Data directories to create
		$this->dirs = array("/indexadjustment/temp");

		// Config pages
		$this->config_page_url = array("setup.php@indexadjustment");

		// Dependencies
		$this->hidden = false;
		$this->depends = array('modContrat'); // Requires contract module
		$this->requiredby = array();
		$this->conflictwith = array();

		// Language file
		$this->langfiles = array("indexadjustment@indexadjustment");

		// Prerequisites
		$this->phpmin = array(7, 4);
		$this->need_dolibarr_version = array(16, 0);

		// Messages at activation
		$this->warnings_activation = array();
		$this->warnings_activation_ext = array();

		// Constants
		$this->const = array(
			1 => array('INDEXADJUSTMENT_DEFAULT_THRESHOLD', 'chaine', '0', 'Minimum % before adjustment', 1, 'current', 0),
			2 => array('INDEXADJUSTMENT_ROUNDING_MODE', 'chaine', 'standard', 'Rounding mode: standard, up, down', 1, 'current', 0),
			3 => array('INDEXADJUSTMENT_ROLLBACK_DAYS', 'chaine', '30', 'Days allowed for rollback', 1, 'current', 0),
			4 => array('INDEXADJUSTMENT_VPI_BASE_YEAR', 'chaine', '2020', 'Default VPI base year', 1, 'current', 0),
		);

		if (!isset($conf->indexadjustment) || !isset($conf->indexadjustment->enabled)) {
			$conf->indexadjustment = new stdClass();
			$conf->indexadjustment->enabled = 0;
		}

		// Tabs
		$this->tabs = array(
			// Add tab to contract card showing adjustment history
			'contract:+indexadjustment:IndexAdjustmentHistory:indexadjustment@indexadjustment:$user->hasRight("indexadjustment", "indexadjustment", "read"):/indexadjustment/contract_history.php?id=__ID__',
		);

		// Dictionaries
		$this->dictionaries = array();

		// Boxes/Widgets
		$this->boxes = array();

		// Cronjobs
		$this->cronjobs = array();

		// Permissions
		$this->rights = array();
		$r = 0;

		// Read permission
		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1);
		$this->rights[$r][1] = 'Read index adjustments';
		$this->rights[$r][4] = 'indexadjustment';
		$this->rights[$r][5] = 'read';
		$r++;

		// Write permission
		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1);
		$this->rights[$r][1] = 'Create/Update index adjustments';
		$this->rights[$r][4] = 'indexadjustment';
		$this->rights[$r][5] = 'write';
		$r++;

		// Execute permission
		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1);
		$this->rights[$r][1] = 'Execute index adjustments';
		$this->rights[$r][4] = 'indexadjustment';
		$this->rights[$r][5] = 'execute';
		$r++;

		// Rollback permission
		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1);
		$this->rights[$r][1] = 'Rollback index adjustments';
		$this->rights[$r][4] = 'indexadjustment';
		$this->rights[$r][5] = 'rollback';
		$r++;

		// Delete permission
		$this->rights[$r][0] = $this->numero . sprintf("%02d", $r + 1);
		$this->rights[$r][1] = 'Delete index adjustments';
		$this->rights[$r][4] = 'indexadjustment';
		$this->rights[$r][5] = 'delete';
		$r++;

		// Main menu entries
		$this->menu = array();
		$r = 0;

		// Top menu under Contracts
		$this->menu[$r++] = array(
			'fk_menu' => 'fk_mainmenu=commercial,fk_leftmenu=contracts',
			'type' => 'left',
			'titre' => 'IndexAdjustments',
			'prefix' => img_picto('', 'fa-percent', 'class="paddingright pictofixedwidth"'),
			'mainmenu' => 'commercial',
			'leftmenu' => 'indexadjustment',
			'url' => '/indexadjustment/list.php',
			'langs' => 'indexadjustment@indexadjustment',
			'position' => 1000 + $r,
			'enabled' => '$conf->indexadjustment->enabled && $conf->contrat->enabled',
			'perms' => '$user->hasRight("indexadjustment", "indexadjustment", "read")',
			'target' => '',
			'user' => 0,
		);

		// Submenu: List
		$this->menu[$r++] = array(
			'fk_menu' => 'fk_mainmenu=commercial,fk_leftmenu=indexadjustment',
			'type' => 'left',
			'titre' => 'List',
			'mainmenu' => 'commercial',
			'leftmenu' => 'indexadjustment_list',
			'url' => '/indexadjustment/list.php',
			'langs' => 'indexadjustment@indexadjustment',
			'position' => 1000 + $r,
			'enabled' => '$conf->indexadjustment->enabled',
			'perms' => '$user->hasRight("indexadjustment", "indexadjustment", "read")',
			'target' => '',
			'user' => 0,
		);

		// Submenu: New adjustment (wizard)
		$this->menu[$r++] = array(
			'fk_menu' => 'fk_mainmenu=commercial,fk_leftmenu=indexadjustment',
			'type' => 'left',
			'titre' => 'NewIndexAdjustment',
			'mainmenu' => 'commercial',
			'leftmenu' => 'indexadjustment_new',
			'url' => '/indexadjustment/wizard.php',
			'langs' => 'indexadjustment@indexadjustment',
			'position' => 1000 + $r,
			'enabled' => '$conf->indexadjustment->enabled',
			'perms' => '$user->hasRight("indexadjustment", "indexadjustment", "write")',
			'target' => '',
			'user' => 0,
		);
	}

	/**
	 * Function called when module is enabled.
	 * The init function adds constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 * It also creates data directories.
	 *
	 * @param string $options Options when enabling module ('', 'noboxes')
	 * @return int             1 if OK, 0 if KO
	 */
	public function init($options = '')
	{
		global $conf, $langs;

		$result = $this->_load_tables('/indexadjustment/sql/');
		if ($result < 0) {
			return -1;
		}

		// Create extrafields if needed
		// (Currently no extrafields defined for this module)

		return $this->_init(array(), $options);
	}

	/**
	 * Function called when module is disabled.
	 * The remove function removes constants, boxes, permissions and menus from Dolibarr database.
	 * Data directories are not deleted.
	 *
	 * @param string $options Options when disabling module ('', 'noboxes')
	 * @return int             1 if OK, 0 if KO
	 */
	public function remove($options = '')
	{
		$sql = array();

		return $this->_remove($sql, $options);
	}
}
