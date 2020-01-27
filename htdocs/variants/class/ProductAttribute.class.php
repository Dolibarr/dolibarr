<?php

/* Copyright (C) 2016	Marcos García	<marcosgdf@gmail.com>
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
 * Class ProductAttribute
 * Used to represent a product attribute
 */
class ProductAttribute
{
	/**
	 * Database handler
	 * @var DoliDB
	 */
	private $db;

	/**
	 * Id of the product attribute
	 * @var int
	 */
	public $id;

	/**
	 * Ref of the product attribute
	 * @var string
	 */
	public $ref;

	/**
	 * Label of the product attribute
	 * @var string
	 */
	public $label;

	/**
	 * Order of attribute.
	 * Lower ones will be shown first and higher ones last
	 * @var int
	 */
	public $rang;

    /**
     * Constructor
     *
     * @param   DoliDB $db     Database handler
     */
	public function __construct(DoliDB $db)
	{
		global $conf;

		$this->db = $db;
		$this->entity = $conf->entity;
	}

	/**
	 * Fetches the properties of a product attribute
	 *
	 * @param int $id Attribute id
	 * @return int <1 KO, >1 OK
	 */
	public function fetch($id)
	{
		if (!$id) {
			return -1;
		}

		$sql = "SELECT rowid, ref, label, rang FROM ".MAIN_DB_PREFIX."product_attribute WHERE rowid = ".(int) $id." AND entity IN (".getEntity('product').")";

		$query = $this->db->query($sql);

		if (!$this->db->num_rows($query)) {
			return -1;
		}

		$obj = $this->db->fetch_object($query);

		$this->id = $obj->rowid;
		$this->ref = $obj->ref;
		$this->label = $obj->label;
		$this->rang = $obj->rang;

		return 1;
	}

	/**
	 * Returns an array of all product variants
	 *
	 * @return ProductAttribute[]
	 */
	public function fetchAll()
	{
		$return = array();

		$sql = 'SELECT rowid, ref, label, rang FROM '.MAIN_DB_PREFIX."product_attribute WHERE entity IN (".getEntity('product').')';
		$sql .= $this->db->order('rang', 'asc');
		$query = $this->db->query($sql);
		if ($query)
		{
    		while ($result = $this->db->fetch_object($query)) {
    			$tmp = new ProductAttribute($this->db);
    			$tmp->id = $result->rowid;
    			$tmp->ref = $result->ref;
    			$tmp->label = $result->label;
    			$tmp->rang = $result->rang;

    			$return[] = $tmp;
    		}
		}
		else dol_print_error($this->db);

		return $return;
	}

	/**
	 * Creates a product attribute
	 *
	 * @param	User	$user	Object user that create
	 * @return 					int <0 KO, Id of new variant if OK
	 */
	public function create(User $user)
	{
		//Ref must be uppercase
		$this->ref = strtoupper($this->ref);

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."product_attribute (ref, label, entity, rang)
		VALUES ('".$this->db->escape($this->ref)."', '".$this->db->escape($this->label)."', ".(int) $this->entity.", ".(int) $this->rang.")";

		$query = $this->db->query($sql);
		if ($query)
		{
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.'product_attribute');

			return $this->id;
		}

		return -1;
	}

	/**
	 * Updates a product attribute
	 *
	 * @param	User	$user		Object user
	 * @return 	int 				<0 KO, >0 OK
	 */
	public function update(User $user)
	{
		//Ref must be uppercase
		$this->ref = trim(strtoupper($this->ref));
		$this->label = trim($this->label);

		$sql = "UPDATE ".MAIN_DB_PREFIX."product_attribute SET ref = '".$this->db->escape($this->ref)."', label = '".$this->db->escape($this->label)."', rang = ".(int) $this->rang." WHERE rowid = ".(int) $this->id;

		if ($this->db->query($sql)) {
			return 1;
		}

		return -1;
	}

	/**
	 * Deletes a product attribute
	 *
	 * @param	User	$user		Object user
	 * @return 	int 				<0 KO, >0 OK
	 */
	public function delete($user = null)
	{
		$sql = "DELETE FROM ".MAIN_DB_PREFIX."product_attribute WHERE rowid = ".(int) $this->id;

		if ($this->db->query($sql)) {
			return 1;
		}

		return -1;
	}

	/**
	 * Returns the number of values for this attribute
	 *
	 * @return int
	 */
	public function countChildValues()
	{
		$sql = "SELECT COUNT(*) count FROM ".MAIN_DB_PREFIX."product_attribute_value WHERE fk_product_attribute = ".(int) $this->id;

		$query = $this->db->query($sql);
		$result = $this->db->fetch_object($query);

		return $result->count;
	}

	/**
	 * Returns the number of products that are using this attribute
	 *
	 * @return int
	 */
	public function countChildProducts()
	{
		$sql = "SELECT COUNT(*) count FROM ".MAIN_DB_PREFIX."product_attribute_combination2val pac2v
		LEFT JOIN ".MAIN_DB_PREFIX."product_attribute_combination pac ON pac2v.fk_prod_combination = pac.rowid WHERE pac2v.fk_prod_attr = ".(int) $this->id." AND pac.entity IN (".getEntity('product').")";

		$query = $this->db->query($sql);

		$result = $this->db->fetch_object($query);

		return $result->count;
	}


	/**
	 * Reorders the order of the variants.
	 * This is an internal function used by moveLine function
	 *
	 * @return int <0 KO >0 OK
	 */
	protected function reorderLines()
	{
		global $user;

		$tmp_order = array();

		$sql = 'SELECT rowid FROM '.MAIN_DB_PREFIX.'product_attribute WHERE rang = 0';
		$sql .= $this->db->order('rang, rowid', 'asc');

		$query = $this->db->query($sql);

		if (!$query) {
			return -1;
		}

		while ($result = $this->db->fetch_object($query)) {
			$tmp_order[] = $result->rowid;
		}

		foreach ($tmp_order as $order => $rowid) {
			$tmp = new ProductAttribute($this->db);
			$tmp->fetch($rowid);
			$tmp->rang = $order+1;

			if ($tmp->update($user) < 0) {
				return -1;
			}
		}

		return 1;
	}

	/**
	 * Internal function to handle moveUp and moveDown functions
	 *
	 * @param string $type up/down
	 * @return int <0 KO >0 OK
	 */
	private function moveLine($type)
	{
		global $user;

		if ($this->reorderLines() < 0) {
			return -1;
		}

		$this->db->begin();

		if ($type == 'up') {
			$newrang = $this->rang - 1;
		} else {
			$newrang = $this->rang + 1;
		}

		$sql = 'UPDATE '.MAIN_DB_PREFIX.'product_attribute SET rang = '.$this->rang.' WHERE rang = '.$newrang;

		if (!$this->db->query($sql)) {
			$this->db->rollback();
			return -1;
		}

		$this->rang = $newrang;

		if ($this->update($user) < 0) {
			$this->db->rollback();
			return -1;
		}

		$this->db->commit();
		return 1;
	}

	/**
	 * Shows this attribute before others
	 *
	 * @return int <0 KO >0 OK
	 */
	public function moveUp()
	{
		return $this->moveLine('up');
	}

	/**
	 * Shows this attribute after others
	 *
	 * @return int <0 KO >0 OK
	 */
	public function moveDown()
	{
		return $this->moveLine('down');
	}

	/**
	 * Updates the order of all variants. Used by AJAX page for drag&drop
	 *
	 * @param DoliDB $db Database handler
	 * @param array $order Array with row id ordered in ascendent mode
	 * @return int <0 KO >0 OK
	 */
	public static function bulkUpdateOrder(DoliDB $db, array $order)
	{
		global $user;

		$tmp = new ProductAttribute($db);

		foreach ($order as $key => $attrid) {
			if ($tmp->fetch($attrid) < 0) {
				return -1;
			}

			$tmp->rang = $key;

			if ($tmp->update($user) < 0) {
				return -1;
			}
		}

		return 1;
	}
}
