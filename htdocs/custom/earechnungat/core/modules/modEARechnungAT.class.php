<?php
/* Copyright (C) 2025 Florian Hoedl <florian@hoedl.co>
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
 * \defgroup   earechnungat     Module EARechnungAT
 * \brief      Einnahmen-Ausgaben-Rechnung for Austrian companies
 *
 * \file       htdocs/custom/earechnungat/core/modules/modEARechnungAT.class.php
 * \ingroup    earechnungat
 * \brief      Description and activation file for module EARechnungAT
 */

include_once DOL_DOCUMENT_ROOT . '/core/modules/DolibarrModules.class.php';

/**
 * Description and activation class for module EARechnungAT
 */
class modEARechnungAT extends DolibarrModules
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

		$this->numero = 510200;
		$this->rights_class = 'earechnungat';
		$this->family = "financial";
		$this->module_position = '91';
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		$this->description = "Einnahmen-Ausgaben-Rechnung for Austrian companies (Ist-Besteuerung)";
		$this->descriptionlong = "Generates income/expense reports (E/A-Rechnung) for Austrian companies under 700k EUR revenue, based on payment date (Zufluss-Abfluss-Prinzip).";
		$this->editor_name = 'Florian Hoedl';
		$this->editor_url = '';
		$this->version = '1.0.0';
		$this->const_name = 'MAIN_MODULE_' . strtoupper($this->name);
		$this->picto = 'fa-file-invoice';

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
			'hooks' => array(),
			'moduleforexternal' => 0,
		);

		$this->dirs = array();
		$this->config_page_url = array("setup.php@earechnungat");

		$this->hidden = false;
		$this->depends = array();
		$this->requiredby = array();
		$this->conflictwith = array();

		$this->langfiles = array("earechnungat@earechnungat");

		$this->phpmin = array(7, 4);
		$this->need_dolibarr_version = array(16, 0);

		$this->warnings_activation = array();
		$this->warnings_activation_ext = array();

		// Constants
		$this->const = array(
			1 => array('EARECHNUNGAT_TAX_MODE', 'chaine', 'payment', 'Tax calculation mode: payment (Ist) or invoice (Soll)', 1),
			2 => array('EARECHNUNGAT_FISCAL_YEAR_START', 'chaine', '01-01', 'Fiscal year start (MM-DD)', 1),
		);

		if (!isset($conf->earechnungat) || !isset($conf->earechnungat->enabled)) {
			$conf->earechnungat = new stdClass();
			$conf->earechnungat->enabled = 0;
		}

		$this->tabs = array();
		$this->dictionaries = array();
		$this->boxes = array();
		$this->cronjobs = array();

		// Permissions
		$this->rights = array();
		$r = 0;

		$this->rights[$r][0] = $this->numero . sprintf('%02d', 1);
		$this->rights[$r][1] = 'Read E/A reports';
		$this->rights[$r][4] = 'report';
		$this->rights[$r][5] = 'read';
		$r++;

		$this->rights[$r][0] = $this->numero . sprintf('%02d', 2);
		$this->rights[$r][1] = 'Export E/A reports';
		$this->rights[$r][4] = 'report';
		$this->rights[$r][5] = 'export';
		$r++;

		// Menus
		$this->menu = array();
		$r = 0;

		// Top menu under Accounting
		$this->menu[$r++] = array(
			'fk_menu' => 'fk_mainmenu=accountancy',
			'type' => 'left',
			'titre' => 'EARechnungATMenu',
			'prefix' => img_picto('', $this->picto, 'class="paddingright pictofixedwidth valignmiddle"'),
			'mainmenu' => 'accountancy',
			'leftmenu' => 'earechnungat',
			'url' => '/earechnungat/report.php',
			'langs' => 'earechnungat@earechnungat',
			'position' => 1000 + $r,
			'enabled' => 'isModEnabled("earechnungat")',
			'perms' => '$user->hasRight("earechnungat", "report", "read")',
			'target' => '',
			'user' => 2,
		);

		$this->menu[$r++] = array(
			'fk_menu' => 'fk_mainmenu=accountancy,fk_leftmenu=earechnungat',
			'type' => 'left',
			'titre' => 'EARechnungATReport',
			'mainmenu' => 'accountancy',
			'leftmenu' => 'earechnungat_report',
			'url' => '/earechnungat/report.php',
			'langs' => 'earechnungat@earechnungat',
			'position' => 1000 + $r,
			'enabled' => 'isModEnabled("earechnungat")',
			'perms' => '$user->hasRight("earechnungat", "report", "read")',
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
		$this->remove($options);
		$sql = array();
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
