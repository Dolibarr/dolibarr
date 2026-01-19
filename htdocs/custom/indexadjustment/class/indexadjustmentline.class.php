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
 * \file       class/indexadjustmentline.class.php
 * \ingroup    indexadjustment
 * \brief      File for the IndexAdjustmentLine business object class
 */

require_once DOL_DOCUMENT_ROOT . '/core/class/commonobjectline.class.php';

/**
 * Class IndexAdjustmentLine
 *
 * Represents a single contract line adjustment.
 * Records before/after prices for audit trail.
 */
class IndexAdjustmentLine extends CommonObjectLine
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'indexadjustmentline';

	/**
	 * @var string Name of table without prefix
	 */
	public $table_element = 'indexadjustment_line';

	/**
	 * @var int ID
	 */
	public $id;

	/**
	 * @var int Parent adjustment ID
	 */
	public $fk_indexadjustment;

	/**
	 * @var int Contract ID
	 */
	public $fk_contrat;

	/**
	 * @var int Contract line ID
	 */
	public $fk_contratdet;

	/**
	 * @var string Product reference
	 */
	public $product_ref;

	/**
	 * @var string Product label
	 */
	public $product_label;

	/**
	 * @var float Unit price before adjustment
	 */
	public $subprice_before;

	/**
	 * @var float Quantity
	 */
	public $qty;

	/**
	 * @var float Total HT before adjustment
	 */
	public $total_ht_before;

	/**
	 * @var float Unit price after adjustment
	 */
	public $subprice_after;

	/**
	 * @var float Total HT after adjustment
	 */
	public $total_ht_after;

	/**
	 * @var float Price difference
	 */
	public $price_diff_ht;

	/**
	 * @var int Rollback flag (0=not rolled back, 1=rolled back)
	 */
	public $rollback_executed;

	/**
	 * @var int Rollback date timestamp
	 */
	public $rollback_date;

	/**
	 * @var int User who performed rollback
	 */
	public $fk_user_rollback;

	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 * Create object into database
	 *
	 * @param User $user      User that creates
	 * @param bool $notrigger false=launch triggers, true=disable triggers
	 * @return int            <0 if KO, Id of created object if OK
	 */
	public function create($user, $notrigger = false)
	{
		$sql = "INSERT INTO " . MAIN_DB_PREFIX . $this->table_element . " (";
		$sql .= "fk_indexadjustment,";
		$sql .= "fk_contrat,";
		$sql .= "fk_contratdet,";
		$sql .= "product_ref,";
		$sql .= "product_label,";
		$sql .= "subprice_before,";
		$sql .= "qty,";
		$sql .= "total_ht_before,";
		$sql .= "subprice_after,";
		$sql .= "total_ht_after,";
		$sql .= "price_diff_ht";
		$sql .= ") VALUES (";
		$sql .= (int)$this->fk_indexadjustment . ",";
		$sql .= (int)$this->fk_contrat . ",";
		$sql .= (int)$this->fk_contratdet . ",";
		$sql .= "'" . $this->db->escape($this->product_ref) . "',";
		$sql .= "'" . $this->db->escape($this->product_label) . "',";
		$sql .= (float)$this->subprice_before . ",";
		$sql .= (float)$this->qty . ",";
		$sql .= (float)$this->total_ht_before . ",";
		$sql .= (float)$this->subprice_after . ",";
		$sql .= (float)$this->total_ht_after . ",";
		$sql .= (float)$this->price_diff_ht;
		$sql .= ")";

		dol_syslog(get_class($this) . "::create", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . $this->table_element);
			return $this->id;
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}
	}

	/**
	 * Load object in memory from the database
	 *
	 * @param int $id   Id object
	 * @return int      <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id)
	{
		$sql = "SELECT rowid, fk_indexadjustment, fk_contrat, fk_contratdet,";
		$sql .= " product_ref, product_label, subprice_before, qty, total_ht_before,";
		$sql .= " subprice_after, total_ht_after, price_diff_ht,";
		$sql .= " rollback_executed, rollback_date, fk_user_rollback";
		$sql .= " FROM " . MAIN_DB_PREFIX . $this->table_element;
		$sql .= " WHERE rowid = " . (int)$id;

		$result = $this->db->query($sql);
		if ($result) {
			if ($this->db->num_rows($result)) {
				$obj = $this->db->fetch_object($result);

				$this->id = $obj->rowid;
				$this->fk_indexadjustment = $obj->fk_indexadjustment;
				$this->fk_contrat = $obj->fk_contrat;
				$this->fk_contratdet = $obj->fk_contratdet;
				$this->product_ref = $obj->product_ref;
				$this->product_label = $obj->product_label;
				$this->subprice_before = $obj->subprice_before;
				$this->qty = $obj->qty;
				$this->total_ht_before = $obj->total_ht_before;
				$this->subprice_after = $obj->subprice_after;
				$this->total_ht_after = $obj->total_ht_after;
				$this->price_diff_ht = $obj->price_diff_ht;
				$this->rollback_executed = $obj->rollback_executed;
				$this->rollback_date = $this->db->jdate($obj->rollback_date);
				$this->fk_user_rollback = $obj->fk_user_rollback;

				return 1;
			}
			return 0;
		}
		$this->error = $this->db->lasterror();
		return -1;
	}

	/**
	 * Update object into database
	 *
	 * @param User $user      User that modifies
	 * @param bool $notrigger false=launch triggers, true=disable triggers
	 * @return int            <0 if KO, >0 if OK
	 */
	public function update($user, $notrigger = false)
	{
		$sql = "UPDATE " . MAIN_DB_PREFIX . $this->table_element . " SET";
		$sql .= " subprice_after = " . (float)$this->subprice_after . ",";
		$sql .= " total_ht_after = " . (float)$this->total_ht_after . ",";
		$sql .= " price_diff_ht = " . (float)$this->price_diff_ht . ",";
		$sql .= " rollback_executed = " . (int)$this->rollback_executed . ",";
		$sql .= " rollback_date = " . ($this->rollback_date ? "'" . $this->db->idate($this->rollback_date) . "'" : "NULL") . ",";
		$sql .= " fk_user_rollback = " . ($this->fk_user_rollback ? (int)$this->fk_user_rollback : "NULL");
		$sql .= " WHERE rowid = " . (int)$this->id;

		dol_syslog(get_class($this) . "::update", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {
			return 1;
		}
		$this->error = $this->db->lasterror();
		return -1;
	}

	/**
	 * Delete object in database
	 *
	 * @param User $user      User that deletes
	 * @param bool $notrigger false=launch triggers, true=disable triggers
	 * @return int            <0 if KO, >0 if OK
	 */
	public function delete($user, $notrigger = false)
	{
		$sql = "DELETE FROM " . MAIN_DB_PREFIX . $this->table_element;
		$sql .= " WHERE rowid = " . (int)$this->id;

		$result = $this->db->query($sql);
		if ($result) {
			return 1;
		}
		$this->error = $this->db->lasterror();
		return -1;
	}

	/**
	 * Mark line as rolled back
	 *
	 * @param User $user User performing rollback
	 * @return int       <0 if KO, >0 if OK
	 */
	public function setRolledBack($user)
	{
		$this->rollback_executed = 1;
		$this->rollback_date = dol_now();
		$this->fk_user_rollback = $user->id;

		return $this->update($user);
	}
}
