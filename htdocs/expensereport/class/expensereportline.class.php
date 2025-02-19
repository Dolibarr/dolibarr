<?php
/* Copyright (C) 2011 		Dimitri Mouillard   	<dmouillard@teclib.com>
 * Copyright (C) 2015 		Laurent Destailleur 	<eldy@users.sourceforge.net>
 * Copyright (C) 2015 		Alexandre Spangaro  	<aspangaro@open-dsi.fr>
 * Copyright (C) 2018       Nicolas ZABOURI         <info@inovea-conseil.com>
 * Copyright (c) 2018-2024  Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2016-2020 	Ferran Marcet       	<fmarcet@2byte.es>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
 * Copyright (C) 2024		William Mead			<william.mead@manchenumerique.fr>
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
 *       \file       htdocs/expensereport/class/expensereportline.class.php
 *       \ingroup    expensereport
 *       \brief      File to manage Expense Report lines
 */
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobjectline.class.php';
require_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport_rule.class.php';

/**
 * Class of expense report details lines
 */
class ExpenseReportLine extends CommonObjectLine
{
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'expensereport_det';

	/**
	 * @var string Error code (or message)
	 */
	public $error = '';

	/**
	 * @var int ID
	 */
	public $rowid;

	/**
	 * @var string
	 */
	public $comments;

	/**
	 * @var float Quantity
	 */
	public $qty;

	/**
	 * @var string|float|int
	 */
	public $value_unit;

	/**
	 * @var int|string
	 */
	public $date;

	/**
	 * @var int|string
	 */
	public $dates;

	/**
	 * @var int ID
	 */
	public $fk_c_type_fees;

	/**
	 * @var int ID
	 */
	public $fk_c_exp_tax_cat;

	/**
	 * @var int ID
	 */
	public $fk_projet;

	/**
	 * @var int ID
	 */
	public $fk_expensereport;

	/**
	 * @var string
	 */
	public $type_fees_code;

	/**
	 * @var string
	 */
	public $type_fees_libelle;

	/**
	 * @var string
	 */
	public $type_fees_accountancy_code;

	/**
	 * @var string
	 */
	public $projet_ref;

	/**
	 * @var string
	 */
	public $projet_title;

	/**
	 * @var int
	 */
	public $rang;

	/**
	 * @var int|string
	 */
	public $vatrate;

	/**
	 * @var string
	 */
	public $vat_src_code;

	/**
	 * @var float
	 */
	public $tva_tx;

	/**
	 * @var int|string
	 */
	public $localtax1_tx;

	/**
	 * @var int|string
	 */
	public $localtax2_tx;

	/**
	 * @var string
	 */
	public $localtax1_type;

	/**
	 * @var string
	 */
	public $localtax2_type;

	/**
	 * @var float
	 */
	public $total_ht;

	/**
	 * @var float
	 */
	public $total_tva;

	/**
	 * @var float
	 */
	public $total_ttc;

	/**
	 * @var float
	 */
	public $total_localtax1;

	/**
	 * @var float
	 */
	public $total_localtax2;

	// Multicurrency
	/**
	 * @var int Currency ID
	 */
	public $fk_multicurrency;

	/**
	 * @var string multicurrency code
	 */
	public $multicurrency_code;

	/**
	 * @var float
	 */
	public $multicurrency_tx;

	/**
	 * @var float
	 */
	public $multicurrency_total_ht;

	/**
	 * @var float
	 */
	public $multicurrency_total_tva;

	/**
	 * @var float
	 */
	public $multicurrency_total_ttc;

	/**
	 * @var int ID into llx_ecm_files table to link line to attached file
	 */
	public $fk_ecm_files;

	/**
	 * @var string
	 */
	public $rule_warning_message;


	/**
	 * Constructor
	 *
	 * @param DoliDB    $db     Handlet database
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 * Fetch record for expense report detailed line
	 *
	 * @param   int     $rowid      Id of object to load
	 * @return  int                 Return integer <0 if KO, >0 if OK
	 */
	public function fetch($rowid)
	{
		$sql = 'SELECT fde.rowid, fde.fk_expensereport, fde.fk_c_type_fees, fde.fk_c_exp_tax_cat, fde.fk_projet as fk_project, fde.date,';
		$sql .= ' fde.tva_tx as vatrate, fde.vat_src_code, fde.comments, fde.qty, fde.value_unit, fde.total_ht, fde.total_tva, fde.total_ttc, fde.fk_ecm_files,';
		$sql .= ' fde.localtax1_tx, fde.localtax2_tx, fde.localtax1_type, fde.localtax2_type, fde.total_localtax1, fde.total_localtax2, fde.rule_warning_message,';
		$sql .= ' ctf.code as type_fees_code, ctf.label as type_fees_libelle,';
		$sql .= ' pjt.rowid as projet_id, pjt.title as projet_title, pjt.ref as projet_ref';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'expensereport_det as fde';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_type_fees as ctf ON fde.fk_c_type_fees=ctf.id'; // Sometimes type of expense report has been removed, so we use a left join here.
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'projet as pjt ON fde.fk_projet=pjt.rowid';
		$sql .= ' WHERE fde.rowid = '.((int) $rowid);

		$result = $this->db->query($sql);

		if ($result) {
			$objp = $this->db->fetch_object($result);

			$this->rowid = $objp->rowid;
			$this->id = $objp->rowid;
			//$this->ref = $objp->ref; Undefined property
			$this->fk_expensereport = $objp->fk_expensereport;
			$this->comments = $objp->comments;
			$this->qty = $objp->qty;
			$this->date = $objp->date;
			$this->dates = $this->db->jdate($objp->date);
			$this->value_unit = $objp->value_unit;
			$this->fk_c_type_fees = $objp->fk_c_type_fees;
			$this->fk_c_exp_tax_cat = $objp->fk_c_exp_tax_cat;
			$this->fk_projet = $objp->fk_project; // deprecated
			$this->fk_project = $objp->fk_project;
			$this->type_fees_code = $objp->type_fees_code;
			$this->type_fees_libelle = $objp->type_fees_libelle;
			$this->projet_ref = $objp->projet_ref;
			$this->projet_title = $objp->projet_title;

			$this->vatrate = $objp->vatrate;
			$this->vat_src_code = $objp->vat_src_code;
			$this->localtax1_tx = $objp->localtax1_tx;
			$this->localtax2_tx = $objp->localtax2_tx;
			$this->localtax1_type = $objp->localtax1_type;
			$this->localtax2_type = $objp->localtax2_type;

			$this->total_ht = $objp->total_ht;
			$this->total_tva = $objp->total_tva;
			$this->total_ttc = $objp->total_ttc;
			$this->total_localtax1 = $objp->total_localtax1;
			$this->total_localtax2 = $objp->total_localtax2;

			$this->fk_ecm_files = $objp->fk_ecm_files;

			$this->rule_warning_message = $objp->rule_warning_message;

			$this->db->free($result);

			return $this->id;
		} else {
			dol_print_error($this->db);
			return -1;
		}
	}

	/**
	 * Insert a line of expense report
	 *
	 * @param   int     $notrigger      1=No trigger
	 * @param   bool    $fromaddline    false=keep default behavior, true=exclude the update_price() of parent object
	 * @return  int                     Return integer <0 if KO, >0 if OK
	 */
	public function insert($notrigger = 0, $fromaddline = false)
	{
		global $user;

		$error = 0;

		dol_syslog("ExpenseReportLine::Insert", LOG_DEBUG);

		// Clean parameters
		$this->comments = trim($this->comments);
		if (empty($this->value_unit)) {
			$this->value_unit = 0;
		}
		$this->qty = (float) price2num($this->qty);
		$this->vatrate = price2num($this->vatrate);
		if (empty($this->fk_c_exp_tax_cat)) {
			$this->fk_c_exp_tax_cat = 0;
		}

		$this->db->begin();

		$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'expensereport_det';
		$sql .= ' (fk_expensereport, fk_c_type_fees, fk_projet,';
		$sql .= ' tva_tx, vat_src_code,';
		$sql .= ' localtax1_tx, localtax2_tx, localtax1_type, localtax2_type,';
		$sql .= ' comments, qty, value_unit,';
		$sql .= ' total_ht, total_tva, total_ttc,';
		$sql .= ' total_localtax1, total_localtax2,';
		$sql .= ' date, rule_warning_message, fk_c_exp_tax_cat, fk_ecm_files)';
		$sql .= " VALUES (".$this->db->escape($this->fk_expensereport).",";
		$sql .= " ".((int) $this->fk_c_type_fees).",";
		$sql .= " ".((int) (!empty($this->fk_project) && $this->fk_project > 0) ? $this->fk_project : ((!empty($this->fk_projet) && $this->fk_projet > 0) ? $this->fk_projet : 'null')).",";
		$sql .= " ".((float) $this->vatrate).",";
		$sql .= " '".$this->db->escape(empty($this->vat_src_code) ? '' : $this->vat_src_code)."',";
		$sql .= " ".((float) price2num($this->localtax1_tx)).",";
		$sql .= " ".((float) price2num($this->localtax2_tx)).",";
		$sql .= " '".$this->db->escape($this->localtax1_type)."',";
		$sql .= " '".$this->db->escape($this->localtax2_type)."',";
		$sql .= " '".$this->db->escape($this->comments)."',";
		$sql .= " ".((float) $this->qty).",";
		$sql .= " ".((float) $this->value_unit).",";
		$sql .= " ".((float) price2num($this->total_ht)).",";
		$sql .= " ".((float) price2num($this->total_tva)).",";
		$sql .= " ".((float) price2num($this->total_ttc)).",";
		$sql .= " ".((float) price2num($this->total_localtax1)).",";
		$sql .= " ".((float) price2num($this->total_localtax2)).",";
		$sql .= " '".$this->db->idate($this->date)."',";
		$sql .= " ".(empty($this->rule_warning_message) ? 'null' : "'".$this->db->escape($this->rule_warning_message)."'").",";
		$sql .= " ".((int) $this->fk_c_exp_tax_cat).",";
		$sql .= " ".($this->fk_ecm_files > 0 ? ((int) $this->fk_ecm_files) : 'null');
		$sql .= ")";

		$resql = $this->db->query($sql);
		if ($resql) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.'expensereport_det');

			if (!$error && !$notrigger) {
				// Call triggers
				$result = $this->call_trigger('EXPENSE_REPORT_DET_CREATE', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}

			if (!$fromaddline) {
				$tmpparent = new ExpenseReport($this->db);
				$tmpparent->fetch($this->fk_expensereport);
				$result = $tmpparent->update_price(1);
				if ($result < 0) {
					$error++;
					$this->error = $tmpparent->error;
					$this->errors = $tmpparent->errors;
				}
			}
		} else {
			$error++;
		}

		if (!$error) {
			$this->db->commit();
			return $this->id;
		} else {
			$this->error = $this->db->lasterror();
			dol_syslog("ExpenseReportLine::insert Error ".$this->error, LOG_ERR);
			$this->db->rollback();
			return -2;
		}
	}

	/**
	 * Function to get total amount in expense reports for a same rule
	 *
	 * @param  ExpenseReportRule $rule		object rule to check
	 * @param  int				 $fk_user	user author id
	 * @param  string			 $mode		day|EX_DAY / month|EX_MON / year|EX_YEA to get amount
	 * @return float                        Amount
	 */
	public function getExpAmount(ExpenseReportRule $rule, $fk_user, $mode = 'day')
	{
		$amount = 0;

		$sql = 'SELECT SUM(d.total_ttc) as total_amount';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'expensereport_det d';
		$sql .= ' INNER JOIN '.MAIN_DB_PREFIX.'expensereport e ON (d.fk_expensereport = e.rowid)';
		$sql .= ' WHERE e.fk_user_author = '.((int) $fk_user);
		if (!empty($this->id)) {
			$sql .= ' AND d.rowid <> '.((int) $this->id);
		}
		$sql .= ' AND d.fk_c_type_fees = '.((int) $rule->fk_c_type_fees);
		if ($mode == 'day' || $mode == 'EX_DAY') {
			$sql .= " AND d.date = '".dol_print_date($this->date, '%Y-%m-%d')."'";
		} elseif ($mode == 'mon' || $mode == 'EX_MON') {
			$sql .= " AND DATE_FORMAT(d.date, '%Y-%m') = '".dol_print_date($this->date, '%Y-%m')."'"; // @todo DATE_FORMAT is forbidden
		} elseif ($mode == 'year' || $mode == 'EX_YEA') {
			$sql .= " AND DATE_FORMAT(d.date, '%Y') = '".dol_print_date($this->date, '%Y')."'"; 	// @todo DATE_FORMAT is forbidden
		}

		dol_syslog('ExpenseReportLine::getExpAmount');

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			if ($num > 0) {
				$obj = $this->db->fetch_object($resql);
				$amount = (float) $obj->total_amount;
			}
		} else {
			dol_print_error($this->db);
		}

		return $amount + $this->total_ttc;
	}

	/**
	 * Update line
	 *
	 * @param   User    $user      User
	 * @param   int     $notrigger 1=No trigger
	 * @return  int                Return integer <0 if KO, >0 if OK
	 */
	public function update(User $user, $notrigger = 0)
	{
		global $langs;

		$error = 0;

		// Clean parameters
		$this->comments = trim($this->comments);
		$this->vatrate = price2num($this->vatrate);
		$this->value_unit = price2num($this->value_unit);
		if (empty($this->fk_c_exp_tax_cat)) {
			$this->fk_c_exp_tax_cat = 0;
		}

		$this->db->begin();

		// Update line in database
		$sql = "UPDATE ".MAIN_DB_PREFIX."expensereport_det SET";
		$sql .= " comments='".$this->db->escape($this->comments)."'";
		$sql .= ", value_unit = ".((float) $this->value_unit);
		$sql .= ", qty=".((float) $this->qty);
		$sql .= ", date='".$this->db->idate($this->date)."'";
		$sql .= ", total_ht=".((float) price2num($this->total_ht, 'MT'));
		$sql .= ", total_tva=".((float) price2num($this->total_tva, 'MT'));
		$sql .= ", total_ttc=".((float) price2num($this->total_ttc, 'MT'));
		$sql .= ", total_localtax1=".((float) price2num($this->total_localtax1, 'MT'));
		$sql .= ", total_localtax2=".((float) price2num($this->total_localtax2, 'MT'));
		$sql .= ", tva_tx=".((float) $this->vatrate);
		$sql .= ", vat_src_code='".$this->db->escape($this->vat_src_code)."'";
		$sql .= ", localtax1_tx=".((float) $this->localtax1_tx);
		$sql .= ", localtax2_tx=".((float) $this->localtax2_tx);
		$sql .= ", localtax1_type='".$this->db->escape($this->localtax1_type)."'";
		$sql .= ", localtax2_type='".$this->db->escape($this->localtax2_type)."'";
		$sql .= ", rule_warning_message='".$this->db->escape($this->rule_warning_message)."'";
		$sql .= ", fk_c_exp_tax_cat=".$this->db->escape($this->fk_c_exp_tax_cat);
		$sql .= ", fk_ecm_files=".($this->fk_ecm_files > 0 ? ((int) $this->fk_ecm_files) : 'null');
		if ($this->fk_c_type_fees) {
			$sql .= ", fk_c_type_fees = ".((int) $this->fk_c_type_fees);
		} else {
			$sql .= ", fk_c_type_fees=null";
		}
		if ($this->fk_project > 0) {
			$sql .= ", fk_projet=".((int) $this->fk_project);
		} else {
			$sql .= ", fk_projet=null";
		}
		$sql .= " WHERE rowid = ".((int) ($this->rowid ? $this->rowid : $this->id));

		dol_syslog("ExpenseReportLine::update");

		$resql = $this->db->query($sql);
		if ($resql) {
			$tmpparent = new ExpenseReport($this->db);
			$result = $tmpparent->fetch($this->fk_expensereport);
			if ($result > 0) {
				$result = $tmpparent->update_price(1);
				if ($result < 0) {
					$error++;
					$this->error = $tmpparent->error;
					$this->errors = $tmpparent->errors;
				}
			} else {
				$error++;
				$this->error = $tmpparent->error;
				$this->errors = $tmpparent->errors;
			}
		} else {
			$error++;
			dol_print_error($this->db);
		}

		if (!$error && !$notrigger) {
			// Call triggers
			$result = $this->call_trigger('EXPENSE_REPORT_DET_MODIFY', $user);
			if ($result < 0) {
				$error++;
			}
			// End call triggers
		}

		if (!$error) {
			$this->db->commit();
			return 1;
		} else {
			$this->error = $this->db->lasterror();
			dol_syslog("ExpenseReportLine::update Error ".$this->error, LOG_ERR);
			$this->db->rollback();
			return -2;
		}
	}
}
