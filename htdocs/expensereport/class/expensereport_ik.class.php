<?php
/* Copyright (C) 2017		ATM Consulting			<support@atm-consulting.fr>
 * Copyright (C) 2017		Pierre-Henry Favre		<phf@atm-consulting.fr>
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
	 * @var array
	 */
	public $fields = array(
		'rowid'=>array('type'=>'integer', 'index'=>true)
		,'fk_c_exp_tax_cat'=>array('type'=>'integer', 'index'=>true)
		,'fk_range'=>array('type'=>'integer', 'index'=>true)
		,'coef'=>array('type'=>'double')
		,'ikoffset'=>array('type'=>'double')
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
	 * @param  bool $notrigger false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, Id of created object if OK
	 */
	public function create(User $user, $notrigger = false)
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
	 * @return int         <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id, $ref = null)
	{
		$result = $this->fetchCommon($id, $ref);
		if ($result > 0 && !empty($this->table_element_line)) {
			$this->fetchLines();
		}
		return $result;
	}


	/**
	 * Update object into database
	 *
	 * @param  User $user      User that modifies
	 * @param  bool $notrigger false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, >0 if OK
	 */
	public function update(User $user, $notrigger = false)
	{
		return $this->updateCommon($user, $notrigger);
	}

	/**
	 * Delete object in database
	 *
	 * @param User $user       User that deletes
	 * @param bool $notrigger  false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, >0 if OK
	 */
	public function delete(User $user, $notrigger = false)
	{
		return $this->deleteCommon($user, $notrigger);
		//return $this->deleteCommon($user, $notrigger, 1);
	}


	/**
	 * Return expense categories in array
	 *
	 * @param	int		$mode	1=only active; 2=only inactive; other value return all
	 * @return	array of category
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
	 * Return an array of ranges for a user
	 *
	 * @param User  $userauthor         user author id
	 * @param int   $fk_c_exp_tax_cat   category
	 * @return boolean|array
	 */
	public function getRangeByUser(User $userauthor, int $fk_c_exp_tax_cat)
	{
		$default_range = (int) $userauthor->default_range; // if not defined, then 0
		$ranges = $this->getRangesByCategory($fk_c_exp_tax_cat);
		// prevent out of range -1 indice
		$indice = $default_range > 0 ? $default_range - 1 : 0;
		// substract 1 because array start from 0
		if (empty($ranges) || !isset($ranges[$indice])) {
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
	 * @return array
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
	 * @return array
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
