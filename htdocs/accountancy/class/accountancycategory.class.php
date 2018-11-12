<?php
/* Copyright (C) 2016		Jamal Elbaz			<jamelbaz@gmail.pro>
 * Copyright (C) 2016-2017	Alexandre Spangaro	<aspangaro@zendsi.com>
 * Copyright (C) 2018       Frédéric France     <frederic.france@netlogic.fr>
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
 * \file	htdocs/accountancy/class/accountancycategory.class.php
 * \ingroup Advanced accountancy
 * \brief	File of class to manage categories of an accounting category_type
 */

// Class
require_once DOL_DOCUMENT_ROOT . '/core/lib/accounting.lib.php';

/**
 * Class to manage categories of an accounting account
 */
class AccountancyCategory
{
	private $db;
	public $error;
	public $errors = array ();
	public $element = 'accounting_category';
	public $table_element = 'c_accounting_category';
	public $id;
	public $lines_cptbk;
	public $lines_display;
	public $sdc;

	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db) {
		$this->db = $db;
	}

	/**
	 * Function to select all accounting accounts from an accounting category
	 *
	 * @param int $id Id
	 *
	 * @return int <0 if KO, 0 if not found, >0 if OK
	 */
	public function display($id) {
		$sql = "SELECT t.rowid, t.account_number, t.label";
		$sql .= " FROM " . MAIN_DB_PREFIX . "accounting_account as t";
		$sql .= " WHERE t.fk_accounting_category = " . $id;

		$this->lines_display = array ();

		dol_syslog(__METHOD__ . " sql=" . $sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			if ($num) {
				while ( $obj = $this->db->fetch_object($resql) ) {
					$this->lines_display[] = $obj;
				}
			}
			return $num;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			$this->errors[] = $this->error;
			dol_syslog(__METHOD__ . " " . implode(',' . $this->errors), LOG_ERR);

			return - 1;
		}
	}

	/**
	 * Function to select accounting category of an accounting account present in chart of accounts
	 *
	 * @param int $id Id category
	 *
	 * @return int <0 if KO, 0 if not found, >0 if OK
	 */
	public function getCptBK($id) {
		global $conf;

		$sql = "SELECT t.numero_compte, t.label_operation, t.doc_ref";
		$sql .= " FROM " . MAIN_DB_PREFIX . "accounting_bookkeeping as t";
		$sql .= " WHERE t.numero_compte NOT IN (";
		$sql .= " SELECT t.account_number";
		$sql .= " FROM " . MAIN_DB_PREFIX . "accounting_account as t";
		$sql .= " WHERE t.fk_accounting_category = " . $id . ")";
		$sql .= " AND t.numero_compte IN (";
		$sql .= " SELECT DISTINCT aa.account_number";
		$sql .= " FROM " . MAIN_DB_PREFIX . "accounting_account as aa";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "accounting_system as asy ON aa.fk_pcg_version = asy.pcg_version";
		$sql .= " AND asy.rowid = " . $conf->global->CHARTOFACCOUNTS;
		$sql .= " AND aa.active = 1)";
		$sql .= " GROUP BY t.numero_compte, t.label_operation, t.doc_ref";
		$sql .= " ORDER BY t.numero_compte";

		$this->lines_CptBk = array ();

		dol_syslog(__METHOD__, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			if ($num) {
				while ( $obj = $this->db->fetch_object($resql) ) {
					$this->lines_cptbk[] = $obj;
				}
			}

			return $num;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			$this->errors[] = $this->error;
			dol_syslog(__METHOD__ . " " . implode(',' . $this->errors), LOG_ERR);

			return - 1;
		}
	}

	/**
	 * Function to select accounting category of an accounting account present in chart of accounts
	 *
	 * @param int $id Id category
	 *
	 * @return int <0 if KO, 0 if not found, >0 if OK
	 */
	public function getAccountsWithNoCategory($id) {
	    global $conf;

	    $sql = "SELECT aa.account_number as numero_compte, aa.label as label_compte";
	    $sql .= " FROM " . MAIN_DB_PREFIX . "accounting_account as aa";
	    $sql .= " INNER JOIN " . MAIN_DB_PREFIX . "accounting_system as asy ON aa.fk_pcg_version = asy.pcg_version";
	    $sql .= " WHERE (aa.fk_accounting_category != ".$id." OR aa.fk_accounting_category IS NULL)";
	    $sql .= " AND asy.rowid = " . $conf->global->CHARTOFACCOUNTS;
	    $sql .= " AND aa.active = 1";
	    $sql .= " GROUP BY aa.account_number, aa.label";
	    $sql .= " ORDER BY aa.account_number, aa.label";

	    $this->lines_CptBk = array ();

	    dol_syslog(__METHOD__, LOG_DEBUG);
	    $resql = $this->db->query($sql);
	    if ($resql) {
	        $num = $this->db->num_rows($resql);
	        if ($num) {
	            while ( $obj = $this->db->fetch_object($resql) ) {
	                $this->lines_cptbk[] = $obj;
	            }
	        }

	        return $num;
	    } else {
	        $this->error = "Error " . $this->db->lasterror();
	        $this->errors[] = $this->error;
	        dol_syslog(__METHOD__ . " " . implode(',' . $this->errors), LOG_ERR);

	        return - 1;
	    }
	}

	/**
	 * Function to add an accounting account in an accounting category
	 *
	 * @param int $id_cat Id category
	 * @param array $cpts list of accounts array
	 *
	 * @return int <0 if KO, >0 if OK
	 */
	public function updateAccAcc($id_cat, $cpts = array()) {
		global $conf;
		$error = 0;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/accounting.lib.php';

		$sql = "SELECT aa.rowid,aa.account_number ";
		$sql .= " FROM " . MAIN_DB_PREFIX . "accounting_account as aa";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "accounting_system as asy ON aa.fk_pcg_version = asy.pcg_version";
		$sql .= " AND asy.rowid = " . $conf->global->CHARTOFACCOUNTS;
		$sql .= " AND aa.active = 1";

		$this->db->begin();

		dol_syslog(__METHOD__, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (! $resql) {
			$error ++;
			$this->errors[] = "Error " . $this->db->lasterror();
			$this->db->rollback();
			return -1;
		}

		while ( $obj = $this->db->fetch_object($resql))
		{
			if (array_key_exists(length_accountg($obj->account_number), $cpts))
			{
				$sql = "UPDATE " . MAIN_DB_PREFIX . "accounting_account";
				$sql .= " SET fk_accounting_category=" . $id_cat;
				$sql .= " WHERE rowid=".$obj->rowid;
				dol_syslog(__METHOD__, LOG_DEBUG);
				$resqlupdate = $this->db->query($sql);
				if (! $resqlupdate) {
					$error ++;
					$this->errors[] = "Error " . $this->db->lasterror();
				}
			}
		}

		// Commit or rollback
		if ($error) {
			foreach ( $this->errors as $errmsg ) {
				dol_syslog(__METHOD__ . " " . $errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
			}
			$this->db->rollback();

			return - 1 * $error;
		} else {
			$this->db->commit();

			return 1;
		}
	}

	/**
	 * Function to delete an accounting account from an accounting category
	 *
	 * @param int $cpt_id Id of accounting account
	 *
	 * @return int <0 if KO, >0 if OK
	 */
	public function deleteCptCat($cpt_id) {
		$error = 0;

		$sql = "UPDATE " . MAIN_DB_PREFIX . "accounting_account as aa";
		$sql .= " SET fk_accounting_category= 0";
		$sql .= " WHERE aa.rowid= " . $cpt_id;
		$this->db->begin();

		dol_syslog(__METHOD__ . " sql=" . $sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (! $resql) {
			$error ++;
			$this->errors[] = "Error " . $this->db->lasterror();
		}

		// Commit or rollback
		if ($error) {
			foreach ( $this->errors as $errmsg ) {
				dol_syslog(__METHOD__ . " " . $errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
			}
			$this->db->rollback();

			return - 1 * $error;
		} else {
			$this->db->commit();

			return 1;
		}
	}

	/**
	 * Function to know all category from accounting account
	 *
	 * @return array       Result in table
	 */
	public function getCatsCpts()
	{
		global $mysoc;

		$sql = "";

		if (empty($mysoc->country_id)) {
			dol_print_error('', 'Call to select_accounting_account with mysoc country not yet defined');
			exit();
		}

		$sql = "SELECT t.rowid, t.account_number, t.label as account_label, cat.code, cat.position, cat.label as name_cat, cat.sens ";
		$sql .= " FROM " . MAIN_DB_PREFIX . "accounting_account as t, " . MAIN_DB_PREFIX . "c_accounting_category as cat";
		$sql .= " WHERE t.fk_accounting_category IN ( SELECT c.rowid ";
		$sql .= " FROM " . MAIN_DB_PREFIX . "c_accounting_category as c";
		$sql .= " WHERE c.active = 1";
		$sql .= " AND (c.fk_country = ".$mysoc->country_id." OR c.fk_country = 0)";
		$sql .= " AND cat.rowid = t.fk_accounting_category";
		$sql .= " ORDER BY cat.position ASC";

		$resql = $this->db->query($sql);
		if ($resql) {
			$i = 0;
			$obj = '';
			$num = $this->db->num_rows($resql);
			$data = array ();
			if ($num) {
				while ( $obj = $this->db->fetch_object($resql) ) {
					$name_cat = $obj->name_cat;
					$data[$name_cat][$i] = array (
							'id' => $obj->rowid,
							'code' => $obj->code,
							'position' => $obj->position,
							'account_number' => $obj->account_number,
							'account_label' => $obj->account_label,
							'sens' => $obj->sens
					);
					$i ++;
				}
			}
			return $data;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(__METHOD__ . " " . $this->error, LOG_ERR);

			return -1;
		}
	}

	/**
	 * Function to show result of an accounting account from the ledger with a direction and a period
	 *
	 * @param int 		$cpt 				Id accounting account
	 * @param string 	$month 				Specifig month - Can be empty
	 * @param string 	$date_start			Date start
	 * @param string 	$date_end			Date end
	 * @param int 		$sens 				Sens of the account:  0: credit - debit, 1: debit - credit
	 * @param string	$thirdparty_code	Thirdparty code
	 * @return integer 						Result in table
	 */
	public function getResult($cpt, $month, $date_start, $date_end, $sens, $thirdparty_code='nofilter')
	{
		$sql = "SELECT SUM(t.debit) as debit, SUM(t.credit) as credit";
		$sql .= " FROM " . MAIN_DB_PREFIX . "accounting_bookkeeping as t";
		$sql .= " WHERE t.numero_compte = '" . $cpt."'";
		if (! empty($date_start) && ! empty($date_end))
			$sql.= " AND t.doc_date >= '".$this->db->idate($date_start)."' AND t.doc_date <= '".$this->db->idate($date_end)."'";
		if (! empty($month)) {
			$sql .= " AND MONTH(t.doc_date) = " . $month;
		}
		if ($thirdparty_code != 'nofilter')
		{
			$sql .= " AND thirdparty_code = '".$this->db->escape($thirdparty_code)."'";
		}

		dol_syslog(__METHOD__ . " sql=" . $sql, LOG_DEBUG);
		$resql = $this->db->query($sql);

		if ($resql) {
			$num = $this->db->num_rows($resql);
			$this->sdc = 0;
			if ($num) {
				$obj = $this->db->fetch_object($resql);
				if ($sens == 1) {
					$this->sdc = $obj->debit - $obj->credit;
				} else {
					$this->sdc = $obj->credit - $obj->debit;
				}
			}
			return $num;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(__METHOD__ . " " . $this->error, LOG_ERR);

			return - 1;
		}
	}

	/**
	 * Return list of personalized groups that are active
	 *
	 * @param	int			$categorytype		-1=All, 0=Only non computed groups, 1=Only computed groups
	 * @return	array|int						Array of groups or -1 if error
	 */
	public function getCats($categorytype=-1)
	{
		global $db, $langs, $user, $mysoc;

		if (empty($mysoc->country_id)) {
			dol_print_error('', 'Call to select_accounting_account with mysoc country not yet defined');
			exit();
		}

		$sql = "SELECT c.rowid, c.code, c.label, c.formula, c.position, c.category_type";
		$sql .= " FROM " . MAIN_DB_PREFIX . "c_accounting_category as c";
		$sql .= " WHERE c.active = 1 ";
		if ($categorytype >= 0) $sql.=" AND c.category_type = 1";
		$sql .= " AND (c.fk_country = ".$mysoc->country_id." OR c.fk_country = 0)";
		$sql .= " ORDER BY c.position ASC";

		$resql = $this->db->query($sql);
		if ($resql) {
			$i = 0;
			$obj = '';
			$num = $this->db->num_rows($resql);
			$data = array ();
			if ($num) {
				while ( $i < $num ) {
					$obj = $this->db->fetch_object($resql);

					$data[] = array (
							'rowid' => $obj->rowid,
							'code' => $obj->code,
							'label' => $obj->label,
							'formula' => $obj->formula,
							'position' => $obj->position,
							'category_type' => $obj->category_type
					);
					$i++;
				}
			}
			return $data;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			$this->errors[] = $this->error;
			dol_syslog(__METHOD__ . " " . implode(',', $this->errors), LOG_ERR);

			return - 1;
		}
	}


	// calcule

	/* I try to replace this with dol_eval()

	const PATTERN = '/(?:\-?\d+(?:\.?\d+)?[\+\-\*\/])+\-?\d+(?:\.?\d+)?/';

	const PARENTHESIS_DEPTH = 10;

	public function calculate($input)
	{
		global $langs;

		if(strpos($input, '+') != null || strpos($input, '-') != null || strpos($input, '/') != null || strpos($input, '*') != null){
			//  Remove white spaces and invalid math chars
			$input = str_replace($langs->trans("ThousandSeparator"), '', $input);
			$input = str_replace(',', '.', $input);
			$input = preg_replace('[^0-9\.\+\-\*\/\(\)]', '', $input);

			//  Calculate each of the parenthesis from the top
			$i = 0;
			while(strpos($input, '(') || strpos($input, ')')){
				$input = preg_replace_callback('/\(([^\(\)]+)\)/', 'self::callback', $input);

				$i++;
				if($i > self::PARENTHESIS_DEPTH){
					break;
				}
			}

			//  Calculate the result
			if(preg_match(self::PATTERN, $input, $match)){
				return $this->compute($match[0]);
			}

			return 0;
		}

		return $input;
	}

	private function compute($input){
		$compute = create_function('', 'return '.$input.';');

		return 0 + $compute();
	}

	private function callback($input){
		if(is_numeric($input[1])){
			return $input[1];
		}
		elseif(preg_match(self::PATTERN, $input[1], $match)){
			return $this->compute($match[0]);
		}

		return 0;
	}
	*/


	/**
	 * Get all accounting account of a group.
	 * You must choose between first parameter (personalized group) or the second (free criteria filter)
	 *
	 * @param 	int 		$cat_id 				Id if personalized accounting group/category
	 * @param 	string 		$predefinedgroupwhere 	Sql criteria filter to select accounting accounts
	 * @return 	array|int							Array of accounting accounts or -1 if error
	 */
	public function getCptsCat($cat_id, $predefinedgroupwhere='')
	{
		global $mysoc;
		$sql = '';

		if (empty($mysoc->country_id) && empty($mysoc->country_code)) {
			dol_print_error('', 'Call to select_accounting_account with mysoc country not yet defined');
			exit();
		}

		if (! empty($cat_id))
		{
			$sql = "SELECT t.rowid, t.account_number, t.label as account_label";
			$sql .= " FROM " . MAIN_DB_PREFIX . "accounting_account as t";
			$sql .= " WHERE t.fk_accounting_category = ".$cat_id;
			$sql .= " ORDER BY t.account_number";
		}
		else
		{
			$sql = "SELECT t.rowid, t.account_number, t.label as account_label";
			$sql .= " FROM " . MAIN_DB_PREFIX . "accounting_account as t";
			$sql .= " WHERE ".$predefinedgroupwhere;
			$sql .= " ORDER BY t.account_number";
		}
		//echo $sql;

		$resql = $this->db->query($sql);
		if ($resql) {
			$i = 0;
			$obj = '';
			$num = $this->db->num_rows($resql);
			$data = array();
			if ($num) {
				while ($obj = $this->db->fetch_object($resql))
				{
					$name_cat = $obj->name_cat;
					$data[] = array (
							'id' => $obj->rowid,
							'account_number' => $obj->account_number,
							'account_label' => $obj->account_label,
					);
					$i ++;
				}
			}
			return $data;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(__METHOD__ . " " . $this->error, LOG_ERR);

			return -1;
		}
	}

}
