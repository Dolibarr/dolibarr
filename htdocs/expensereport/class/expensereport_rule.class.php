<?php
/* Copyright (C) 2017		ATM Consulting				<support@atm-consulting.fr>
 * Copyright (C) 2017		Pierre-Henry Favre			<phf@atm-consulting.fr>
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
class ExpenseReportRule extends CommonObject
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'expenserule';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'expensereport_rules';

	/**
	 * @var string Fieldname with ID of parent key if this field has a parent
	 */
	public $fk_element = 'fk_expense_rule';

	/**
	 * date start
	 * @var int|string
	 */
	public $dates;

	/**
	 * date end
	 * @var int|string
	 */
	public $datee;

	/**
	 * amount
	 * @var double
	 */
	public $amount;

	/**
	 * restrective
	 * @var int
	 */
	public $restrictive;

	/**
	 * rule for user
	 * @var int
	 */
	public $fk_user;

	/**
	 * rule for group
	 * @var int
	 */
	public $fk_usergroup;

	/**
	 * c_type_fees id
	 * @var int
	 */
	public $fk_c_type_fees;

	/**
	 * code type of expense report
	 * @var string
	 */
	public $code_expense_rules_type;

	/**
	 * rule for all
	 * @var int
	 */
	public $is_for_all;

	/**
	 * entity
	 * @var int
	 */
	public $entity;



	/**
	 * Attribute object linked with database
	 * @var array<string,array{type:string,label:string,enabled:int<0,2>|string,position:int,notnull?:int,visible:int<0,1>,noteditable?:int<0,1>,default?:string,index?:int,foreignkey?:string,searchall?:int<0,1>,isameasure?:int<0,1>,css?:string,csslist?:string,help?:string,showoncombobox?:int<0,1>,disabled?:int<0,1>,arrayofkeyval?:array<int,string>,comment?:string,validate?:int<0,1>}>  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields = array(
		'rowid' => array('type' => 'integer', 'index' => 1, 'label' => 'ID', 'enabled' => 1, 'visible' => -1, 'position' => 10),
		'dates' => array('type' => 'date', 'label' => 'Dates', 'enabled' => 1, 'visible' => -1, 'position' => 20),
		'datee' => array('type' => 'date', 'label' => 'Datee', 'enabled' => 1, 'visible' => -1, 'position' => 30),
		'amount' => array('type' => 'double', 'label' => 'Amount', 'enabled' => 1, 'visible' => -1, 'position' => 40),
		'restrictive' => array('type' => 'integer', 'label' => 'Restrictive', 'enabled' => 1, 'visible' => -1, 'position' => 50),
		'fk_user' => array('type' => 'integer', 'label' => 'User', 'enabled' => 1, 'visible' => -1, 'position' => 60),
		'fk_usergroup' => array('type' => 'integer', 'label' => 'Usergroup', 'enabled' => 1, 'visible' => -1, 'position' => 70),
		'fk_c_type_fees' => array('type' => 'integer', 'label' => 'Type fees', 'enabled' => 1, 'visible' => -1, 'position' => 80),
		'code_expense_rules_type' => array('type' => 'string', 'label' => 'Expense rule code', 'enabled' => 1, 'visible' => -1, 'position' => 90),
		'is_for_all' => array('type' => 'integer', 'label' => 'IsForAll', 'enabled' => 1, 'visible' => -1, 'position' => 100),
		'entity' => array('type' => 'integer', 'label' => 'Entity', 'enabled' => 1, 'visible' => -2, 'position' => 110),
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
	 * @param User 	$user       User that deletes
	 * @param int 	$notrigger  0=launch triggers after, 1=disable triggers
	 * @return int             	Return integer <0 if KO, >0 if OK
	 */
	public function delete(User $user, $notrigger = 0)
	{
		return $this->deleteCommon($user, $notrigger);
		//return $this->deleteCommon($user, $notrigger, 1);
	}


	/**
	 * Return all rules or filtered by something
	 *
	 * @param int	     $fk_c_type_fees	type of expense
	 * @param int|string $date			    date of expense
	 * @param int        $fk_user		    user of expense
	 * @return array                        Array with ExpenseReportRule
	 */
	public function getAllRule($fk_c_type_fees = 0, $date = '', $fk_user = 0)
	{
		$rules = array();

		$sql = 'SELECT er.rowid';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'expensereport_rules er';
		$sql .= ' WHERE er.entity IN (0,'.getEntity($this->element).')';
		if (!empty($fk_c_type_fees)) {
			$sql .= ' AND er.fk_c_type_fees IN (-1, '.((int) $fk_c_type_fees).')';
		}
		if (!empty($date)) {
			$sql .= " AND er.dates <= '".$this->db->idate($date)."'";
			$sql .= " AND er.datee >= '".$this->db->idate($date)."'";
		}
		if ($fk_user > 0) {
			$sql .= ' AND (er.is_for_all = 1';
			$sql .= ' OR er.fk_user = '.((int) $fk_user);
			$sql .= ' OR er.fk_usergroup IN (SELECT ugu.fk_usergroup FROM '.MAIN_DB_PREFIX.'usergroup_user ugu WHERE ugu.fk_user = '.((int) $fk_user).') )';
		}
		$sql .= ' ORDER BY er.is_for_all, er.fk_usergroup, er.fk_user';

		dol_syslog("ExpenseReportRule::getAllRule");

		$resql = $this->db->query($sql);
		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				$rule = new ExpenseReportRule($this->db);
				if ($rule->fetch($obj->rowid) > 0) {
					$rules[$rule->id] = $rule;
				} else {
					dol_print_error($this->db);
				}
			}
		} else {
			dol_print_error($this->db);
		}

		return $rules;
	}

	/**
	 * Return the label of group for the current object
	 *
	 * @return string
	 */
	public function getGroupLabel()
	{
		include_once DOL_DOCUMENT_ROOT.'/user/class/usergroup.class.php';

		if ($this->fk_usergroup > 0) {
			$group = new UserGroup($this->db);
			if ($group->fetch($this->fk_usergroup) > 0) {
				return $group->name;
			} else {
				$this->error = $group->error;
				$this->errors[] = $this->error;
			}
		}

		return '';
	}

	/**
	 * Return the name of user for the current object
	 *
	 * @return string
	 */
	public function getUserName()
	{
		include_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';

		if ($this->fk_user > 0) {
			$u = new User($this->db);
			if ($u->fetch($this->fk_user) > 0) {
				return dolGetFirstLastname($u->firstname, $u->lastname);
			} else {
				$this->error = $u->error;
				$this->errors[] = $this->error;
			}
		}

		return '';
	}
}
