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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Class ProductAttributeValue
 * Used to represent a product attribute value
 */
class ProductAttributeValue
{
	/**
	 * Database handler
	 * @var DoliDB
	 */
	private $db;

	/**
	 * Attribute value id
	 * @var int
	 */
	public $id;

	/**
	 * Product attribute id
	 * @var int
	 */
	public $fk_product_attribute;

	/**
	 * Attribute value ref
	 * @var string
	 */
	public $ref;

	/**
	 * Attribute value value
	 * @var string
	 */
	public $value;

	public function __construct(DoliDB $db)
	{
		global $conf;

		$this->db = $db;
		$this->entity = $conf->entity;
	}

	/**
	 * Gets a product attribute value
	 *
	 * @param int $valueid Product attribute value id
	 * @return int <0 KO, >0 OK
	 */
	public function fetch($valueid)
	{
		$sql = "SELECT rowid, fk_product_attribute, ref, value FROM ".MAIN_DB_PREFIX."product_attribute_value WHERE rowid = ".(int) $valueid." AND entity IN (".getEntity('product').")";

		$query = $this->db->query($sql);

		if (!$query) {
			return -1;
		}

		if (!$this->db->num_rows($query)) {
			return -1;
		}

		$result = $this->db->fetch_object($query);

		$this->id = $result->rowid;
		$this->fk_product_attribute = $result->fk_product_attribute;
		$this->ref = $result->ref;
		$this->value = $result->value;

		return 1;
	}

	/**
	 * Returns all product attribute values of a product attribute
	 *
	 * @param int $prodattr_id Product attribute id
	 * @param bool $only_used Fetch only used attribute values
	 * @return ProductAttributeValue[]
	 */
	public function fetchAllByProductAttribute($prodattr_id, $only_used = false)
	{
		$return = array();

		$sql = 'SELECT ';

		if ($only_used) {
			$sql .= 'DISTINCT ';
		}

		$sql .= 'v.fk_product_attribute, v.rowid, v.ref, v.value FROM '.MAIN_DB_PREFIX.'product_attribute_value v ';

		if ($only_used) {
			$sql .= 'LEFT JOIN '.MAIN_DB_PREFIX.'product_attribute_combination2val c2v ON c2v.fk_prod_attr_val = v.rowid ';
			$sql .= 'LEFT JOIN '.MAIN_DB_PREFIX.'product_attribute_combination c ON c.rowid = c2v.fk_prod_combination ';
			$sql .= 'LEFT JOIN '.MAIN_DB_PREFIX.'product p ON p.rowid = c.fk_product_child ';
		}

		$sql .= 'WHERE v.fk_product_attribute = '.(int) $prodattr_id;

		if ($only_used) {
			$sql .= ' AND c2v.rowid IS NOT NULL AND p.tosell = 1';
		}

		$query = $this->db->query($sql);

		while ($result = $this->db->fetch_object($query)) {

			$tmp = new ProductAttributeValue($this->db);
			$tmp->fk_product_attribute = $result->fk_product_attribute;
			$tmp->id = $result->rowid;
			$tmp->ref = $result->ref;
			$tmp->value = $result->value;

			$return[] = $tmp;
		}

		return $return;
	}

	/**
	 * Creates a value for a product attribute
	 *
	 * @param	User	$user		Object user
	 * @return 	int 				<0 KO >0 OK
	 */
	public function create(User $user)
	{
		if (!$this->fk_product_attribute) {
			return -1;
		}

		// Ref must be uppercase
		$this->ref = strtoupper($this->ref);

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."product_attribute_value (fk_product_attribute, ref, value, entity)
		VALUES ('".(int) $this->fk_product_attribute."', '".$this->db->escape($this->ref)."',
		'".$this->db->escape($this->value)."', ".(int) $this->entity.")";

		$query = $this->db->query($sql);

		if ($query) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.'product_attribute_value');
			return 1;
		}

		return -1;
	}

	/**
	 * Updates a product attribute value
	 *
	 * @param	User	$user	Object user
	 * @return 	int				<0 if KO, >0 if OK
	 */
	public function update(User $user)
	{
		//Ref must be uppercase
		$this->ref = trim(strtoupper($this->ref));
		$this->value = trim($this->value);

		$sql = "UPDATE ".MAIN_DB_PREFIX."product_attribute_value
		SET fk_product_attribute = '".(int) $this->fk_product_attribute."', ref = '".$this->db->escape($this->ref)."',
		value = '".$this->db->escape($this->value)."' WHERE rowid = ".(int) $this->id;

		if ($this->db->query($sql)) {
			return 1;
		}

		return -1;
	}

	/**
	 * Deletes a product attribute value
	 *
	 * @return int <0 KO, >0 OK
	 */
	public function delete()
	{
		$sql = "DELETE FROM ".MAIN_DB_PREFIX."product_attribute_value WHERE rowid = ".(int) $this->id;

		if ($this->db->query($sql)) {
			return 1;
		}

		return -1;
	}

	/**
	 * Deletes all product attribute values by a product attribute id
	 *
	 * @param int $fk_attribute Product attribute id
	 * @return int <0 KO, >0 OK
	 */
	public function deleteByFkAttribute($fk_attribute)
	{
		$sql = "DELETE FROM ".MAIN_DB_PREFIX."product_attribute_value WHERE fk_product_attribute = ".(int) $fk_attribute;

		if ($this->db->query($sql)) {
			return 1;
		}

		return -1;
	}
}