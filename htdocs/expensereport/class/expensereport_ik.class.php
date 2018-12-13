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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/expensereport/class/expensereport_ik.class.php
 *	\ingroup    expenseik
 *	\brief      File of class to manage expense ik
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/coreobject.class.php';

/**
 *	Class to manage inventories
 */
class ExpenseReportIk extends CoreObject
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element='expenseik';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element='expensereport_ik';

	/**
	 * @var int Field with ID of parent key if this field has a parent
	 */
	public $fk_element='fk_expense_ik';

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
	protected $fields=array(
		'rowid'=>array('type'=>'integer','index'=>true)
		,'fk_c_exp_tax_cat'=>array('type'=>'integer','index'=>true)
	    ,'fk_range'=>array('type'=>'integer','index'=>true)
		,'coef'=>array('type'=>'double')
		,'ikoffset'=>array('type'=>'double')
	);

    /**
     *  Constructor
     *
     *  @param      DoliDB		$db      Database handler
     */
	public function __construct(DoliDB &$db)
	{
		global $conf;

        parent::__construct($db);
		parent::init();

		$this->errors = array();
	}


	/**
	 * Return expense categories in array
	 *
	 * @param	int		$mode	1=only active; 2=only inactive; other value return all
	 * @return	array of category
	 */
	public static function getTaxCategories($mode=1)
	{
		global $db;

		$categories = array();

		$sql = 'SELECT rowid, label, entity, active';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'c_exp_tax_cat';
		$sql.= ' WHERE entity IN ('. getEntity('c_exp_tax_cat').')';
		if ($mode == 1) $sql.= ' AND active = 1';
		elseif ($mode == 2) $sql.= 'AND active = 0';

		dol_syslog(get_called_class().'::getTaxCategories sql='.$sql, LOG_DEBUG);
		$resql = $db->query($sql);
		if ($resql)
		{
			while ($obj = $db->fetch_object($resql))
			{
				$categories[$obj->rowid] = $obj;
			}
		}
		else
		{
			dol_print_error($db);
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
    public static function getRangeByUser(User $userauthor, $fk_c_exp_tax_cat)
    {
		$default_range = (int) $userauthor->default_range; // if not defined, then 0
		$ranges = self::getRangesByCategory($fk_c_exp_tax_cat);

		// substract 1 because array start from 0
		if (empty($ranges) || !isset($ranges[$default_range-1])) return false;
		else return $ranges[$default_range-1];
	}

	/**
	 * Return an array of ranges for a category
	 *
	 * @param int	$fk_c_exp_tax_cat	category id
	 * @param int	$active				active
	 * @return array
	 */
	public static function getRangesByCategory($fk_c_exp_tax_cat, $active=1)
	{
		global $db;

		$ranges = array();

		$sql = 'SELECT r.rowid FROM '.MAIN_DB_PREFIX.'c_exp_tax_range r';
		if ($active) $sql.= ' INNER JOIN '.MAIN_DB_PREFIX.'c_exp_tax_cat c ON (r.fk_c_exp_tax_cat = c.rowid)';
		$sql.= ' WHERE r.fk_c_exp_tax_cat = '.$fk_c_exp_tax_cat;
		if ($active) $sql.= ' AND r.active = 1 AND c.active = 1';
		$sql.= ' ORDER BY r.range_ik';

		dol_syslog(get_called_class().'::getRangesByCategory sql='.$sql, LOG_DEBUG);
		$resql = $db->query($sql);
		if ($resql)
		{
			$num = $db->num_rows($resql);
			if ($num > 0)
			{
				while ($obj = $db->fetch_object($resql))
				{
					$object = new ExpenseReportIk($db);
					$object->fetch($obj->rowid);

					$ranges[] = $object;
				}
			}
		}
		else
		{
			dol_print_error($db);
		}

		return $ranges;
	}

	/**
	 * Return an array of ranges grouped by category
	 *
	 * @return array
	 */
	public static function getAllRanges()
	{
		global $db;

		$ranges = array();

		$sql = ' SELECT r.rowid, r.fk_c_exp_tax_cat, r.range_ik, c.label, i.rowid as fk_expense_ik, r.active as range_active, c.active as cat_active';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'c_exp_tax_range r';
		$sql.= ' INNER JOIN '.MAIN_DB_PREFIX.'c_exp_tax_cat c ON (r.fk_c_exp_tax_cat = c.rowid)';
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'expensereport_ik i ON (r.rowid = i.fk_range)';
		$sql.= ' WHERE r.entity IN (0, '. getEntity('').')';
		$sql.= ' ORDER BY r.fk_c_exp_tax_cat, r.range_ik';

		dol_syslog(get_called_class().'::getAllRanges sql='.$sql, LOG_DEBUG);
		$resql = $db->query($sql);
		if ($resql)
		{
			while ($obj = $db->fetch_object($resql))
			{
				$ik = new ExpenseReportIk($db);
				if ($obj->fk_expense_ik > 0) $ik->fetch($obj->fk_expense_ik);
				$obj->ik = $ik;

				if (!isset($ranges[$obj->fk_c_exp_tax_cat])) $ranges[$obj->fk_c_exp_tax_cat] = array('label' => $obj->label, 'active' => $obj->cat_active, 'ranges' => array());
				$ranges[$obj->fk_c_exp_tax_cat]['ranges'][] = $obj;
			}
		}
		else
		{
			dol_print_error($db);
		}

		return $ranges;
	}

	/**
	 * Return the max number of range by a category
	 *
	 * @param int $default_c_exp_tax_cat id
	 * @return int
	 */
	public static function getMaxRangeNumber($default_c_exp_tax_cat=0)
	{
		global $db,$conf;

		$sql = 'SELECT MAX(counted) as nbRange FROM (';
		$sql.= ' SELECT COUNT(*) as counted';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'c_exp_tax_range r';
		$sql.= ' WHERE r.entity IN (0, '.$conf->entity.')';
		if ($default_c_exp_tax_cat > 0) $sql .= ' AND r.fk_c_exp_tax_cat = '.$default_c_exp_tax_cat;
		$sql.= ' GROUP BY r.fk_c_exp_tax_cat';
		$sql .= ') as counts';

		dol_syslog(get_called_class().'::getMaxRangeNumber sql='.$sql, LOG_DEBUG);
		$resql = $db->query($sql);
		if ($resql)
		{
			$obj = $db->fetch_object($resql);
			return $obj->nbRange;
		}
		else
		{
			dol_print_error($db);
		}

		return 0;
	}
}
