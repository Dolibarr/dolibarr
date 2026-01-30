<?php
/* Copyright (C) 2024 Anexum GmbH
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
 * \defgroup   indexadjustment     Module IndexAdjustment
 * \brief      Index Adjustment module for contract price adjustments
 *
 * \file       htdocs/custom/indexadjustment/core/modules/modIndexAdjustment.class.php
 * \ingroup    indexadjustment
 * \brief      Description and activation file for module IndexAdjustment
 */

include_once DOL_DOCUMENT_ROOT . '/core/modules/DolibarrModules.class.php';

/**
 * Description and activation class for module IndexAdjustment
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
		$this->numero = 510100;

		// Key text used to identify module (for permissions, menus, etc...)
		$this->rights_class = 'indexadjustment';

		// Family: 'crm','financial','hr','projects','products','ecm','technic','interface','other'
		$this->family = "financial";

		// Module position in the family
		$this->module_position = '90';

		// Module label (no space allowed)
		$this->name = preg_replace('/^mod/i', '', get_class($this));

		// Module description
		$this->description = "Transparent contract index adjustments based on Austrian VPI";
		$this->descriptionlong = "Module for managing batch contract price adjustments (Indexanpassungen) with full audit trail, preview mode, and rollback capability.";

		// Author
		$this->editor_name = 'Florian Hödl';
		$this->editor_url = '';

		// Version
		$this->version = '1.0.1';

		// Key used in llx_const table
		$this->const_name = 'MAIN_MODULE_' . strtoupper($this->name);

		// Module icon
		$this->picto = 'fa-percentage';

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
				// 'contractcard', // TODO: Implement hook to show adjustment history on contract card
			),
			'moduleforexternal' => 0,
		);

		// Data directories
		$this->dirs = array("/indexadjustment/temp");

		// Config page
		$this->config_page_url = array("setup.php@indexadjustment");

		// Dependencies
		$this->hidden = false;
		$this->depends = array('modContrat');
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
			1 => array('INDEXADJUSTMENT_DEFAULT_THRESHOLD', 'chaine', '0', 'Minimum percentage before adjustment', 1),
			2 => array('INDEXADJUSTMENT_ROUNDING_MODE', 'chaine', 'standard', 'Rounding mode: standard/up/down', 1),
			3 => array('INDEXADJUSTMENT_ROLLBACK_DAYS', 'chaine', '30', 'Days allowed for rollback', 1),
			4 => array('INDEXADJUSTMENT_VPI_BASE_YEAR', 'chaine', '2020', 'Default VPI base year', 1),
		);

		if (!isset($conf->indexadjustment) || !isset($conf->indexadjustment->enabled)) {
			$conf->indexadjustment = new stdClass();
			$conf->indexadjustment->enabled = 0;
		}

		// Tabs
		$this->tabs = array();

		// Dictionaries
		$this->dictionaries = array();

		// Boxes/Widgets
		$this->boxes = array();

		// Cronjobs
		$this->cronjobs = array();

		// Permissions
		$this->rights = array();
		$r = 0;

		// Read
		$this->rights[$r][0] = $this->numero . sprintf('%02d', (0 * 10) + 0 + 1);
		$this->rights[$r][1] = 'Read index adjustments';
		$this->rights[$r][4] = 'indexadjustment';
		$this->rights[$r][5] = 'read';
		$r++;

		// Write (Create/Update)
		$this->rights[$r][0] = $this->numero . sprintf('%02d', (0 * 10) + 1 + 1);
		$this->rights[$r][1] = 'Create/Update index adjustments';
		$this->rights[$r][4] = 'indexadjustment';
		$this->rights[$r][5] = 'write';
		$r++;

		// Execute
		$this->rights[$r][0] = $this->numero . sprintf('%02d', (0 * 10) + 2 + 1);
		$this->rights[$r][1] = 'Execute index adjustments';
		$this->rights[$r][4] = 'indexadjustment';
		$this->rights[$r][5] = 'execute';
		$r++;

		// Rollback
		$this->rights[$r][0] = $this->numero . sprintf('%02d', (0 * 10) + 3 + 1);
		$this->rights[$r][1] = 'Rollback index adjustments';
		$this->rights[$r][4] = 'indexadjustment';
		$this->rights[$r][5] = 'rollback';
		$r++;

		// Delete
		$this->rights[$r][0] = $this->numero . sprintf('%02d', (0 * 10) + 4 + 1);
		$this->rights[$r][1] = 'Delete index adjustments';
		$this->rights[$r][4] = 'indexadjustment';
		$this->rights[$r][5] = 'delete';
		$r++;

		// Menus
		$this->menu = array();
		$r = 0;

		// Left menu under Commercial → Contracts
		$this->menu[$r++] = array(
			'fk_menu' => 'fk_mainmenu=commercial,fk_leftmenu=contracts',
			'type' => 'left',
			'titre' => 'IndexAdjustmentMenu',
			'prefix' => img_picto('', $this->picto, 'class="paddingright pictofixedwidth valignmiddle"'),
			'mainmenu' => 'commercial',
			'leftmenu' => 'indexadjustment',
			'url' => '/indexadjustment/admin/wizard.php',
			'langs' => 'indexadjustment@indexadjustment',
			'position' => 1000 + $r,
			'enabled' => 'isModEnabled("indexadjustment")',
			'perms' => '$user->hasRight("indexadjustment", "indexadjustment", "read")',
			'target' => '',
			'user' => 2,
		);
	}

	/**
	 * Function called when module is enabled.
	 *
	 * @param string $options Options when enabling module ('', 'noboxes')
	 * @return int 1 if OK, 0 if KO
	 */
	public function init($options = '')
	{
		global $conf, $langs;

		// Load tables
		$result = $this->_load_tables('/indexadjustment/sql/');
		if ($result < 0) {
			return -1;
		}

		// Remove old entries then re-add
		$this->remove($options);

		$sql = array();

		// Add custom action type for index adjustments
		$sql[] = "INSERT INTO " . MAIN_DB_PREFIX . "c_actioncomm (id, code, type, libelle, module, active, position, picto) VALUES (100, 'AC_INDEXADJUST', 'systemauto', 'Index Adjustment', 'indexadjustment', 1, 100, 'fa-percent') ON DUPLICATE KEY UPDATE libelle = 'Index Adjustment', module = 'indexadjustment', active = 1, picto = 'fa-percent'";

		return $this->_init($sql, $options);
	}

	/**
	 * Function called when module is disabled.
	 *
	 * @param string $options Options when enabling module ('', 'noboxes')
	 * @return int 1 if OK, 0 if KO
	 */
	public function remove($options = '')
	{
		$sql = array();
		return $this->_remove($sql, $options);
	}
}
