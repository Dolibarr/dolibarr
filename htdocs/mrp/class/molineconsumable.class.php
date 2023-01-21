<?php
/* Copyright (C) 2017  Laurent Destailleur 	<eldy@users.sourceforge.net>
 * Copyright (C) 2023  Christian Humpel		<christian.humpel@gmail.com>
 * Copyright (C) ---Put here your own copyright and developer email---
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
 * \file        class/molineconsumable.class.php
 * \ingroup     mrp
 * \brief       This file is a CRUD class file for MoLineConsumable (Create/Read/Update/Delete)
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobjectline.class.php';
require_once DOL_DOCUMENT_ROOT . '/mrp/class/mo.class.php';
//require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
//require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

/**
 * Class for MoLineConsumable
 */
class MoLineConsumable extends CommonObject
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'mo';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'mrp_mo';

	/**
	 * @var int  Does mo support multicompany module ? 0=No test on entity, 1=Test with field entity, 2=Test with link by societe
	 */
	public $ismultientitymanaged = 1;

	/**
	 * @var int  Does mo support extrafields ? 0=No, 1=Yes
	 */
	public $isextrafieldmanaged = 1;

	/**
	 * @var string String with name of icon for mo. Must be the part after the 'object_' into object_mo.png
	 */
	public $picto = 'mrp';

	// BEGIN MODULEBUILDER PROPERTIES
	/**
	 * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields = array();

	public $rowid;
	public $entity;
	public $ref;
	public $mrptype;
	public $label;
	public $qty;
	public $fk_warehouse;
	public $fk_soc;
	public $socid;

	/**
	 * @var string public note
	 */
	public $note_public;

	/**
	 * @var string private note
	 */
	public $note_private;

	/**
	 * @var integer|string date_creation
	 */
	public $date_creation;


	public $tms;
	public $fk_user_creat;
	public $fk_user_modif;
	public $import_key;
	public $status;
	public $fk_product;

	/**
	 * @var integer|string date_start_planned
	 */
	public $date_start_planned;

	/**
	 * @var integer|string date_end_planned
	 */
	public $date_end_planned;


	public $fk_bom;
	public $fk_project;

	/**
	 * @var integer id from the consumable product
	 */
	public $consumableproductrowid;

	/**
	 * @var string ref from the consumable product
	 */
	public $consumbaleproductref;

	/**
	 * @var double qty can consume in the MO
	 */
	public $qtytoconsum;

	/**
	 * @var double qty in the specific warehouse
	 */
	public $warehousereel;

	//  /**
	//   * @var integer id from the specific warehouse
	//   */
	//  public $warehouserowid;

	/**
	 * @var integer id from MoLine where affected
	 */
	public $molinerowid;
	// END MODULEBUILDER PROPERTIES

	// If this object has a subtable with lines
	// ...

	/**
	 * Constructor
	 *
	 * @param DoliDb $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		global $conf, $langs;

		$this->db = $db;

		// Get the $fields list of Mo class
		$tmpMo = new Mo($db);
		$this->fields = array_merge($tmpMo->fields);

		// Extends the $fields list
		$extendFields = array(
			'consumableproductrowid' => array('sqlSelect'=>'p.rowid AS consumableproductrowid', 'label'=>'ProductToConsumeTechnicalID', 'enabled'=>1, 'visible'=>-2, 'checked'=>0, 'position'=>1),
			'consumbaleproductref' => array('sqlSelect'=>'p.ref AS consumbaleproductref','label'=>'ProductToConsume', 'enabled'=>(!isModEnabled('product') ? 0 : 1), 'checked'=>1, 'position'=>35, 'visible'=>1),
			'qtytoconsum' => array('sqlSelect'=>'(l.qty - IFNULL(tChild.qty_consumed, 0)) AS qtytoconsum','label'=>'QtyToConsum', 'enabled'=>1, 'checked'=>1, 'position'=>42, 'type'=>'real', 'visible'=>1, 'css'=>'width75'),
			'warehousereel' => array('sqlSelect'=>'s.reel AS warehousereel','label'=>'QtyInWarehouse', 'enabled'=>1, 'checked'=>1, 'position'=>43, 'type'=>'real', 'visible'=>1, 'css'=>'width75'),
			'molinerowid' => array('sqlSelect'=>'l.rowid AS molinerowid','label'=>'molinerowid', 'enabled'=>1, 'checked'=>0, 'position'=>1, 'visible'=>-2),
		);
		$this->fields = array_merge($this->fields, $extendFields);

		if (empty($conf->global->MAIN_SHOW_TECHNICAL_ID) && isset($this->fields['rowid'])) {
			$this->fields['rowid']['visible'] = 0;
		}
		if (!isModEnabled('multicompany') && isset($this->fields['entity'])) {
			$this->fields['entity']['enabled'] = 0;
		}

		// Unset fields that are disabled
		foreach ($this->fields as $key => $val) {
			if (isset($val['enabled']) && empty($val['enabled'])) {
				unset($this->fields[$key]);
			}
		}

		// Translate some data of arrayofkeyval
		foreach ($this->fields as $key => $val) {
			if (!empty($val['arrayofkeyval']) && is_array($val['arrayofkeyval'])) {
				foreach ($val['arrayofkeyval'] as $key2 => $val2) {
					$this->fields[$key]['arrayofkeyval'][$key2] = $langs->trans($val2);
				}
			}
		}
	}

	/**
	 * Load list of objects in memory from the database.
	 *
	 * @param  string      $sortorder    Sort Order
	 * @param  string      $sortfield    Sort field
	 * @param  int         $limit        limit
	 * @param  int         $offset       Offset
	 * @param  array       $filter       Filter array. Example array('field'=>'valueforlike', 'customurl'=>...)
	 * @param  string      $filtermode   Filter mode (AND or OR)
	 * @return array|int                 int <0 if KO, array of pages if OK
	 */
	public function fetchAll($sortorder = '', $sortfield = '', $limit = 0, $offset = 0, array $filter = array(), $filtermode = 'AND')
	{
		global $conf;

		dol_syslog(__METHOD__, LOG_DEBUG);

		$records = array();

		// Build and execute select
		// --------------------------------------------------------------------
		$keys = array_keys($this->fields);
		$selects = array();
		foreach ($keys AS $key) {
			$select = '';
			If (!empty($arrayfields[$key]['sqlSelect'])) {
				$select = $arrayfields[$key]['sqlSelect'];
			} elseif (startsWith($key, 'ef.')) {
				continue;
			} else {
				$select = 't.'.$key;
			}
			$selects[] = $select;
		}

		$sql = 'SELECT t.rowid,';
		$sql .= implode(',', $selects);
		// Add fields from extrafields
		if (!empty($extrafields->attributes[$object->table_element]['label'])) {
			foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) {
				$sql .= ($extrafields->attributes[$object->table_element]['type'][$key] != 'separate' ? ", ef.".$key." as options_".$key : "");
			}
		}
		// Add fields from hooks
		$parameters = array();
		$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters, $object); // Note that $action and $object may have been modified by hook
		$sql .= preg_replace('/^,/', '', $hookmanager->resPrint);
		$sql = preg_replace('/,\s*$/', '', $sql);

		$sql .= " FROM ".MAIN_DB_PREFIX."product_stock as s";
		$sql .= " INNER JOIN ".MAIN_DB_PREFIX."product as p ON s.fk_product = p.rowid";
		$sql .= " INNER JOIN ".MAIN_DB_PREFIX."mrp_production as l ON p.rowid = l.fk_product";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX.$object->table_element." as t ON l.fk_mo = t.rowid";
		if (isset($extrafields->attributes[$object->table_element]['label']) && is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label'])) {
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX.$object->table_element."_extrafields as ef on (t.rowid = ef.fk_object)";
		}

		$sqlConsumed = "SELECT fk_mrp_production, SUM(qty) AS qty_consumed";
		$sqlConsumed .= " FROM ".MAIN_DB_PREFIX."mrp_production";
		$sqlConsumed .= " WHERE role = 'consumed'";
		$sqlConsumed .= " GROUP BY fk_mrp_production";

		$sql .= " LEFT JOIN (".$sqlConsumed.") as tChild ON l.rowid = tChild.fk_mrp_production";

		// Add table from hooks
		$parameters = array();
		$reshook = $hookmanager->executeHooks('printFieldListFrom', $parameters, $object); // Note that $action and $object may have been modified by hook
		$sql .= $hookmanager->resPrint;
		if ($object->ismultientitymanaged == 1) {
			$sql .= " WHERE t.entity IN (".getEntity($object->element).")";
		} else {
			$sql .= " WHERE 1 = 1";
		}
		// Manage filter
		$sqlwhere = array();
		if (count($filter) > 0) {
			foreach ($filter as $key => $value) {
				if ($key == 't.rowid') {
					$sqlwhere[] = $key." = ".((int) $value);
				} elseif (strpos($key, 'date') !== false) {
					$sqlwhere[] = $key." = '".$this->db->idate($value)."'";
				} elseif ($key == 'customsql') {
					$sqlwhere[] = $value;
				} else {
					$sqlwhere[] = $key." LIKE '%".$this->db->escape($value)."%'";
				}
			}
		}
		if (count($sqlwhere) > 0) {
			$sql .= ' AND ('.implode(' '.$this->db->escape($filtermode).' ', $sqlwhere).')';
		}

		if (!empty($sortfield)) {
			$sql .= $this->db->order($sortfield, $sortorder);
		}
		if (!empty($limit)) {
			$sql .= $this->db->plimit($limit, $offset);
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < min($limit, $num)) {
				$obj = $this->db->fetch_object($resql);

				$record = new self($this->db);
				$record->setVarsFromFetchObj($obj);

				$records[$record->id] = $record;

				$i++;
			}
			$this->db->free($resql);

			return $records;
		} else {
			$this->errors[] = 'Error '.$this->db->lasterror();
			dol_syslog(__METHOD__.' '.join(',', $this->errors), LOG_ERR);

			return -1;
		}
	}
}
