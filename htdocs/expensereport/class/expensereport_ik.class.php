<?php
/* Copyright (C) 2017		ATM Consulting			<support@atm-consulting.fr>
 * Copyright (C) 2017		Pierre-Henry Favre		<phf@atm-consulting.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
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
 *	\file       htdocs/expensereport/class/expensereport_ik.class.php
 *	\ingroup    expenseik
 *	\brief      File of class to manage expense ik
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';

/**
 *	Class to manage inventories
 */
class ExpenseReportIk extends CommonObject
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'expenseik';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'expensereport_ik';

	/**
	 * @var string Field with ID of parent key if this field has a parent
	 */
	public $fk_element = 'fk_expense_ik';

	/**
	 * c_exp_tax_cat Id
	 * @var int
	 */
	public $fk_c_exp_tax_cat;

	/**
	 * c_exp_tax_range id
	 * @var int
	 */
	public $fk_range;

	/**
	 * Coef
	 * @var double
	 */
	public $coef;

	/**
	 * Offset
	 * @var double
	 */
	public $ikoffset;


	/**
	 * Attribute object linked with database
	 * @var array<string,array{type:string,label:string,enabled:int<0,2>|string,position:int,notnull?:int,visible:int<-5,5>|string,alwayseditable?:int<0,1>,noteditable?:int<0,1>,default?:string,index?:int,foreignkey?:string,searchall?:int<0,1>,isameasure?:int<0,1>,css?:string,csslist?:string,help?:string,showoncombobox?:int<0,4>,disabled?:int<0,1>,arrayofkeyval?:array<int|string,string>,autofocusoncreate?:int<0,1>,comment?:string,copytoclipboard?:int<1,2>,validate?:int<0,1>,showonheader?:int<0,1>}>  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields = array(
		'rowid' => array('type' => 'integer', 'label' => 'ID', 'enabled' => 1, 'index' => 1, 'visible' => -1, 'position' => 10),
		'fk_c_exp_tax_cat' => array('type' => 'integer', 'label' => 'Tax cat id', 'enabled' => 1, 'index' => 1, 'visible' => -1, 'position' => 20),
		'fk_range' => array('type' => 'integer', 'label' => 'Tax range id', 'enabled' => 1, 'index' => 1, 'visible' => -1, 'position' => 30),
		'coef' => array('type' => 'double', 'label' => 'Coef', 'enabled' => 1, 'visible' => -1, 'position' => 40),
		'ikoffset' => array('type' => 'double', 'label' => 'Offset', 'enabled' => 1, 'visible' => -1, 'position' => 50),
	);


	/**
	 *  Constructor
	 *
	 *  @param      DoliDB		$db      Database handler
	 */
	public function __construct(DoliDB $db)
	{
		$this->db = $db;
	}


	/**
	 * Create object into database
	 *
	 * @param  User $user      User that creates
	 * @param  int 	$notrigger 0=launch triggers after, 1=disable triggers
	 * @return int             Return integer <0 if KO, Id of created object if OK
	 */
	public function create(User $user, $notrigger = 0)
	{
		$resultcreate = $this->createCommon($user, $notrigger);

		//$resultvalidate = $this->validate($user, $notrigger);

		return $resultcreate;
	}


	/**
	 * Load object in memory from the database
	 *
	 * @param int    $id   Id object
	 * @param string $ref  Ref
	 * @return int         Return integer <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id, $ref = null)
	{
		return $this->fetchCommon($id, $ref);
	}



	/**
	 * Update object into database
	 *
	 * @param  User $user      User that modifies
	 * @param  int 	$notrigger 0=launch triggers after, 1=disable triggers
	 * @return int             Return integer <0 if KO, >0 if OK
	 */
	public function update(User $user, $notrigger = 0)
	{
		return $this->updateCommon($user, $notrigger);
	}

	/**
	 * Delete object in database
	 *
	 * @param User $user       	User that deletes
	 * @param int 	$notrigger  0=launch triggers after, 1=disable triggers
	 * @return int             	Return integer <0 if KO, >0 if OK
	 */
	public function delete(User $user, $notrigger = 0)
	{
		return $this->deleteCommon($user, $notrigger);
		//return $this->deleteCommon($user, $notrigger, 1);
	}


	/**
	 * Return expense categories in array
	 *
	 * @param	int		$mode	1=only active; 2=only inactive; other value return all
	 * @return	array<int,object> of category
	 */
	public function getTaxCategories($mode = 1)
	{
		$categories = array();

		$sql = 'SELECT rowid, label, entity, active';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'c_exp_tax_cat';
		$sql .= ' WHERE entity IN (0, '.getEntity($this->element).')';
		if ($mode == 1) {
			$sql .= ' AND active = 1';
		} elseif ($mode == 2) {
			$sql .= 'AND active = 0';
		}

		dol_syslog(get_called_class().'::getTaxCategories', LOG_DEBUG);

		$resql = $this->db->query($sql);
		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				$categories[$obj->rowid] = $obj;
			}
		} else {
			dol_print_error($this->db);
		}

		return $categories;
	}

	/**
	 * Return a range for a user
	 *
	 * @param User  $userauthor         user author id
	 * @param int   $fk_c_exp_tax_cat   category
	 * @return false|ExpenseReportIk
	 */
	public function getRangeByUser(User $userauthor, int $fk_c_exp_tax_cat)
	{
		$default_range = (int) $userauthor->default_range; // if not defined, then 0
		$ranges = $this->getRangesByCategory($fk_c_exp_tax_cat);
		// prevent out of range -1 indice
		$indice = $default_range  - 1;
		// subtract 1 because array start from 0
		if (empty($ranges) || $indice < 0 || !isset($ranges[$indice])) {
			return false;
		} else {
			return $ranges[$indice];
		}
	}

	/**
	 * Return an array of ranges for a category
	 *
	 * @param int	$fk_c_exp_tax_cat	category id
	 * @param int	$active				active
	 * @return ExpenseReportIk[]
	 */
	public function getRangesByCategory(int $fk_c_exp_tax_cat, $active = 1)
	{
		$ranges = array();

		dol_syslog(get_called_class().'::getRangesByCategory for fk_c_exp_tax_cat='.$fk_c_exp_tax_cat, LOG_DEBUG);

		$sql = 'SELECT r.rowid FROM '.MAIN_DB_PREFIX.'c_exp_tax_range r';
		if ($active) {
			$sql .= ' INNER JOIN '.MAIN_DB_PREFIX.'c_exp_tax_cat c ON (r.fk_c_exp_tax_cat = c.rowid)';
		}
		$sql .= ' WHERE r.fk_c_exp_tax_cat = '.((int) $fk_c_exp_tax_cat);
		$sql .= " AND r.entity IN(0, ".getEntity($this->element).")";
		if ($active) {
			$sql .= ' AND r.active = 1 AND c.active = 1';
		}
		$sql .= ' ORDER BY r.range_ik';

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			if ($num > 0) {
				while ($obj = $this->db->fetch_object($resql)) {
					$object = new ExpenseReportIk($this->db);
					$object->fetch($obj->rowid);

					$ranges[] = $object;
				}
			}
		} else {
			dol_print_error($this->db);
		}

		return $ranges;
	}

	/**
	 * Return an array of ranges grouped by category
	 *
	 * @return array<int,array{ranges:array<Object>,label:string,active:int<0,1>}>
	 */
	public function getAllRanges()
	{
		$ranges = array();

		$sql = ' SELECT r.rowid, r.fk_c_exp_tax_cat, r.range_ik, c.label, i.rowid as fk_expense_ik, r.active as range_active, c.active as cat_active';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'c_exp_tax_range r';
		$sql .= ' INNER JOIN '.MAIN_DB_PREFIX.'c_exp_tax_cat c ON (r.fk_c_exp_tax_cat = c.rowid)';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'expensereport_ik i ON (r.rowid = i.fk_range)';
		$sql .= ' WHERE r.entity IN (0, '.getEntity($this->element).')';
		$sql .= ' ORDER BY r.fk_c_exp_tax_cat, r.range_ik';

		dol_syslog(get_called_class().'::getAllRanges', LOG_DEBUG);

		$resql = $this->db->query($sql);
		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				$ik = new ExpenseReportIk($this->db);
				if ($obj->fk_expense_ik > 0) {
					$ik->fetch($obj->fk_expense_ik);
				}

				// TODO Set a $tmparray = new stdObj(); and use it to fill $ranges array
				$obj->ik = $ik;

				if (!isset($ranges[$obj->fk_c_exp_tax_cat])) {
					$ranges[$obj->fk_c_exp_tax_cat] = array('label' => $obj->label, 'active' => $obj->cat_active, 'ranges' => array());
				}
				$ranges[$obj->fk_c_exp_tax_cat]['ranges'][] = $obj;
			}
		} else {
			dol_print_error($this->db);
		}

		return $ranges;
	}

	/**
	 * Return the max number of range by a category
	 *
	 * @param 	int 	$default_c_exp_tax_cat id	Default c_exp_tax_cat
	 * @return 	int									Max nb
	 */
	public function getMaxRangeNumber($default_c_exp_tax_cat = 0)
	{
		$sql = 'SELECT MAX(counted) as nbRange FROM (';
		$sql .= ' SELECT COUNT(*) as counted';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'c_exp_tax_range r';
		$sql .= ' WHERE r.entity IN (0, '.getEntity($this->element).')';
		if ($default_c_exp_tax_cat > 0) {
			$sql .= ' AND r.fk_c_exp_tax_cat = '.((int) $default_c_exp_tax_cat);
		}
		$sql .= ' GROUP BY r.fk_c_exp_tax_cat';
		$sql .= ') as counts';

		dol_syslog(get_called_class().'::getMaxRangeNumber', LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$obj = $this->db->fetch_object($resql);
			return $obj->nbRange;
		} else {
			dol_print_error($this->db);
		}

		return 0;
	}
}
