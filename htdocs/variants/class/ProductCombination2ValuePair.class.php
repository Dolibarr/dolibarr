<?php
/* Copyright (C) 2016	Marcos García	<marcosgdf@gmail.com>
 * Copyright (C) 2022   Open-Dsi		<support@open-dsi.fr>
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
 * Class ProductCombination2ValuePair
 * Used to represent the relation between a product combination, a product attribute and a product attribute value
 */
class ProductCombination2ValuePair
{
	/**
	 * Database handler
	 * @var DoliDB
	 */
	private $db;

	/**
	 * Combination 2 value pair id
	 * @var int
	 */
	public $id;

	/**
	 * Product combination id
	 * @var int
	 */
	public $fk_prod_combination;

	/**
	 * Product attribute id
	 * @var int
	 */
	public $fk_prod_attr;

	/**
	 * Product attribute value id
	 * @var int
	 */
	public $fk_prod_attr_val;

	/**
	 * @var string error
	 */
	public $error;

	/**
	 * @var string[] array of errors
	 */
	public $errors = array();

	/**
	 * Constructor
	 *
	 * @param   DoliDB $db     Database handler
	 */
	public function __construct(DoliDB $db)
	{
		$this->db = $db;
	}

	/**
	 * Translates this class to a human-readable string
	 *
	 * @return string
	 */
	public function __toString()
	{
		require_once DOL_DOCUMENT_ROOT . '/variants/class/ProductAttributeValue.class.php';
		require_once DOL_DOCUMENT_ROOT . '/variants/class/ProductAttribute.class.php';

		$prodattr = new ProductAttribute($this->db);
		$prodattrval = new ProductAttributeValue($this->db);

		$prodattr->fetch($this->fk_prod_attr);
		$prodattrval->fetch($this->fk_prod_attr_val);

		return $prodattr->label . ': ' . $prodattrval->value;
	}

	/**
	 * Creates a product combination 2 value pair
	 *
	 * @param	User	$user		User that create
	 * @return 	int 				Return integer <0 KO, >0 OK
	 */
	public function create($user)
	{
		$sql = "INSERT INTO " . MAIN_DB_PREFIX . "product_attribute_combination2val
		(fk_prod_combination, fk_prod_attr, fk_prod_attr_val)
		VALUES(" . (int) $this->fk_prod_combination . ", " . (int) $this->fk_prod_attr . ", " . (int) $this->fk_prod_attr_val . ")";

		$query = $this->db->query($sql);

		if ($query) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . 'product_attribute_combination2val');

			return 1;
		}

		return -1;
	}

	/**
	 * Retrieves a product combination 2 value pair from its rowid
	 *
	 * @param int $fk_combination Fk combination to search
	 * @return int|ProductCombination2ValuePair[] -1 if KO
	 */
	public function fetchByFkCombination($fk_combination)
	{
		$sql = "SELECT
        c.rowid,
        c2v.fk_prod_attr_val,
        c2v.fk_prod_attr,
        c2v.fk_prod_combination
        FROM " . MAIN_DB_PREFIX . "product_attribute c LEFT JOIN " . MAIN_DB_PREFIX . "product_attribute_combination2val c2v ON c.rowid = c2v.fk_prod_attr
        WHERE c2v.fk_prod_combination = " . (int) $fk_combination;

		$sql .= $this->db->order('c.position', 'asc');

		$query = $this->db->query($sql);

		if (!$query) {
			return -1;
		}

		$return = array();

		while ($obj = $this->db->fetch_object($query)) {
			$tmp = new ProductCombination2ValuePair($this->db);
			$tmp->fk_prod_attr_val = $obj->fk_prod_attr_val;
			$tmp->fk_prod_attr = $obj->fk_prod_attr;
			$tmp->fk_prod_combination = $obj->fk_prod_combination;
			$tmp->id = $obj->rowid;

			$return[] = $tmp;
		}

		return $return;
	}

	/**
	 * Deletes a product combination 2 value pair
	 *
	 * @param int $fk_combination Rowid of the combination
	 * @return int >0 OK <0 KO
	 */
	public function deleteByFkCombination($fk_combination)
	{
		$sql = "DELETE FROM " . MAIN_DB_PREFIX . "product_attribute_combination2val WHERE fk_prod_combination = " . (int) $fk_combination;

		if ($this->db->query($sql)) {
			return 1;
		}

		return -1;
	}
}
