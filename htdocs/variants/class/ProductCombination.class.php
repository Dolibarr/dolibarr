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
 * Class ProductCombination
 * Used to represent a product combination
 */
class ProductCombination
{
	/**
	 * Database handler
	 * @var DoliDB
	 */
	private $db;

	/**
	 * Rowid of combination
	 * @var int
	 */
	public $id;

	/**
	 * Rowid of parent product
	 * @var int
	 */
	public $fk_product_parent;

	/**
	 * Rowid of child product
	 * @var int
	 */
	public $fk_product_child;

	/**
	 * Price variation
	 * @var float
	 */
	public $variation_price;

	/**
	 * Is the price variation a relative variation?
	 * @var bool
	 */
	public $variation_price_percentage = false;

	/**
	 * Weight variation
	 * @var float
	 */
	public $variation_weight;

	/**
	 * Combination entity
	 * @var int
	 */
	public $entity;

	public function __construct(DoliDB $db)
	{
		global $conf;

		$this->db = $db;
		$this->entity = $conf->entity;
	}

	/**
	 * Retrieves a combination by its rowid
	 *
	 * @param int $rowid Row id
	 * @return int <0 KO, >0 OK
	 */
	public function fetch($rowid)
	{
		$sql = "SELECT rowid, fk_product_parent, fk_product_child, variation_price, variation_price_percentage, variation_weight FROM ".MAIN_DB_PREFIX."product_attribute_combination WHERE rowid = ".(int) $rowid." AND entity IN (".getEntity('product').")";

		$query = $this->db->query($sql);

		if (!$query) {
			return -1;
		}

		if (!$this->db->num_rows($query)) {
			return -1;
		}

		$result = $this->db->fetch_object($query);

		$this->id = $result->rowid;
		$this->fk_product_parent = $result->fk_product_parent;
		$this->fk_product_child = $result->fk_product_child;
		$this->variation_price = $result->variation_price;
		$this->variation_price_percentage = $result->variation_price_percentage;
		$this->variation_weight = $result->variation_weight;

		return 1;
	}

	/**
	 * Retrieves a product combination by a child product row id
	 *
	 * @param int $fk_child Product row id
	 * @return int <0 KO, >0 OK
	 */
	public function fetchByFkProductChild($fk_child)
	{
		$sql = "SELECT rowid, fk_product_parent, fk_product_child, variation_price, variation_price_percentage, variation_weight FROM ".MAIN_DB_PREFIX."product_attribute_combination WHERE fk_product_child = ".(int) $fk_child." AND entity IN (".getEntity('product').")";

		$query = $this->db->query($sql);

		if (!$query) {
			return -1;
		}

		if (!$this->db->num_rows($query)) {
			return -1;
		}

		$result = $this->db->fetch_object($query);

		$this->id = $result->rowid;
		$this->fk_product_parent = $result->fk_product_parent;
		$this->fk_product_child = $result->fk_product_child;
		$this->variation_price = $result->variation_price;
		$this->variation_price_percentage = $result->variation_price_percentage;
		$this->variation_weight = $result->variation_weight;

		return 1;
	}

	/**
	 * Retrieves all product combinations by the product parent row id
	 *
	 * @param int $fk_product_parent Rowid of parent product
	 * @return int|ProductCombination[] <0 KO
	 */
	public function fetchAllByFkProductParent($fk_product_parent)
	{
		$sql = "SELECT rowid, fk_product_parent, fk_product_child, variation_price, variation_price_percentage, variation_weight FROM ".MAIN_DB_PREFIX."product_attribute_combination WHERE fk_product_parent = ".(int) $fk_product_parent." AND entity IN (".getEntity('product').")";

		$query = $this->db->query($sql);

		if (!$query) {
			return -1;
		}

		$return = array();

		while ($result = $this->db->fetch_object($query)) {

			$tmp = new ProductCombination($this->db);
			$tmp->id = $result->rowid;
			$tmp->fk_product_parent = $result->fk_product_parent;
			$tmp->fk_product_child = $result->fk_product_child;
			$tmp->variation_price = $result->variation_price;
			$tmp->variation_price_percentage = $result->variation_price_percentage;
			$tmp->variation_weight = $result->variation_weight;

			$return[] = $tmp;
		}

		return $return;
	}

	/**
	 * Retrieves all product combinations by the product parent row id
	 *
	 * @param  int     $fk_product_parent  Id of parent product
	 * @return int                         Nb of record
	 */
	public function countNbOfCombinationForFkProductParent($fk_product_parent)
	{
	    $nb = 0;
	    $sql = "SELECT count(rowid) as nb FROM ".MAIN_DB_PREFIX."product_attribute_combination WHERE fk_product_parent = ".(int) $fk_product_parent." AND entity IN (".getEntity('product').")";

	    $resql = $this->db->query($sql);
	    if ($resql) {
	        $obj = $this->db->fetch_object($resql);
	        if ($obj) $nb = $obj->nb;
	    }

	    return $nb;
	}

	/**
	 * Creates a product attribute combination
	 *
	 * @param	User	$user	Object user
	 * @return 	int				<0 if KO, >0 if OK
	 */
	public function create($user)
	{
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."product_attribute_combination
		(fk_product_parent, fk_product_child, variation_price, variation_price_percentage, variation_weight, entity)
		VALUES (".(int) $this->fk_product_parent.", ".(int) $this->fk_product_child.",
		".(float) $this->variation_price.", ".(int) $this->variation_price_percentage.",
		".(float) $this->variation_weight.", ".(int) $this->entity.")";

		$query = $this->db->query($sql);

		if (!$query) {
			return -1;
		}

		$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.'product_attribute_combination');

		return 1;
	}

	/**
	 * Updates a product combination
	 *
	 * @param	User	$user		Object user
	 * @return 						int <0 KO, >0 OK
	 */
	public function update(User $user)
	{
		$sql = "UPDATE ".MAIN_DB_PREFIX."product_attribute_combination
		SET fk_product_parent = ".(int) $this->fk_product_parent.", fk_product_child = ".(int) $this->fk_product_child.",
		variation_price = ".(float) $this->variation_price.", variation_price_percentage = ".(int) $this->variation_price_percentage.",
		variation_weight = ".(float) $this->variation_weight." WHERE rowid = ".(int) $this->id;

		$query = $this->db->query($sql);

		if (!$query) {
			return -1;
		}

		$parent = new Product($this->db);
		$parent->fetch($this->fk_product_parent);

		$this->updateProperties($parent);

		return 1;
	}

	/**
	 * Deletes a product combination
	 *
	 * @param 	User 	$user	Object user
	 * @return 	int 			<0 if KO, >0 if OK
	 */
	public function delete(User $user)
	{
		$this->db->begin();

		$comb2val = new ProductCombination2ValuePair($this->db);
		$comb2val->deleteByFkCombination($this->id);

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."product_attribute_combination WHERE rowid = ".(int) $this->id;

		if ($this->db->query($sql)) {
			$this->db->commit();
			return 1;
		}

		$this->db->rollback();
		return -1;
	}

	/**
	 * Deletes all product combinations of a parent product
	 *
	 * @param User		$user Object user
	 * @param int 		$fk_product_parent Rowid of parent product
	 * @return int <0 KO >0 OK
	 */
	public function deleteByFkProductParent($user, $fk_product_parent)
	{
		$this->db->begin();

		foreach ($this->fetchAllByFkProductParent($fk_product_parent) as $prodcomb) {

			$prodstatic = new Product($this->db);

			$res = $prodstatic->fetch($prodcomb->fk_product_child);

			if ($res > 0) {
				$res = $prodcomb->delete($user);
			}

			if ($res > 0 && !$prodstatic->isObjectUsed($prodstatic->id)) {
				$res = $prodstatic->delete($user);
			}

			if ($res < 0) {
				$this->db->rollback();
				return -1;
			}
		}

		$this->db->commit();
		return 1;
	}

	/**
	 * Updates the weight of the child product. The price must be updated using Product::updatePrices
	 *
	 * @param Product $parent Parent product
	 * @return int >0 OK <0 KO
	 */
	public function updateProperties(Product $parent)
	{
		global $user, $conf;

		$this->db->begin();

		$child = new Product($this->db);
		$child->fetch($this->fk_product_child);
		$child->price_autogen = $parent->price_autogen;
		$child->weight = $parent->weight + $this->variation_weight;
		$child->weight_units = $parent->weight_units;

		if ($child->update($child->id, $user) > 0) {

			$new_vat = $parent->tva_tx;
			$new_npr = $parent->tva_npr;

			// MultiPrix
			if (! empty($conf->global->PRODUIT_MULTIPRICES)) {
				$new_type = $parent->multiprices_base_type[1];
				$new_min_price = $parent->multiprices_min[1];
				$new_psq = $parent->multiprices_recuperableonly[1];

				if ($new_type == 'TTC') {
					$new_price = $parent->multiprices_ttc[1];
				} else {
					$new_price = $parent->multiprices[1];
				}
			} else {
				$new_type = $parent->price_base_type;
				$new_min_price = $parent->price_min;
				$new_psq = $parent->price_by_qty;

				if ($new_type == 'TTC') {
					$new_price = $parent->price_ttc;
				} else {
					$new_price = $parent->price;
				}
			}

			if ($this->variation_price_percentage) {
				$new_price *= 1 + ($this->variation_price/100);
			} else {
				$new_price += $this->variation_price;
			}

			$child->updatePrice($new_price, $new_type, $user, $new_vat, $new_min_price, 1, $new_npr, $new_psq);

			$this->db->commit();

			return 1;
		}

		$this->db->rollback();
		return -1;
	}

	/**
	 * Retrieves the combination that matches the given features.
	 *
	 * @param 	int 						$prodid 	Id of parent product
	 * @param 	array 						$features 	Format: [$attr] => $attr_val
	 * @return 	false|ProductCombination 				False if not found
	 */
	public function fetchByProductCombination2ValuePairs($prodid, array $features)
	{
		require_once DOL_DOCUMENT_ROOT.'/variants/class/ProductCombination2ValuePair.class.php';

		$actual_comp = array();

		$prodcomb2val = new ProductCombination2ValuePair($this->db);
		$prodcomb = new ProductCombination($this->db);

		foreach ($features as $attr => $attr_val) {
			$actual_comp[$attr] = $attr_val;
		}

		foreach ($prodcomb->fetchAllByFkProductParent($prodid) as $prc) {

			$values = array();

			foreach ($prodcomb2val->fetchByFkCombination($prc->id) as $value) {
				$values[$value->fk_prod_attr] = $value->fk_prod_attr_val;
			}

			$check1 = count(array_diff_assoc($values, $actual_comp));
			$check2 = count(array_diff_assoc($actual_comp, $values));

			if (!$check1 && !$check2) {
				return $prc;
			}
		}

		return false;
	}

	/**
	 * Retrieves all unique attributres for a parent product
	 *
	 * @param int $productid Product rowid
	 * @return ProductAttribute[]
	 */
	public function getUniqueAttributesAndValuesByFkProductParent($productid)
	{
		require_once DOL_DOCUMENT_ROOT.'/variants/class/ProductAttribute.class.php';
		require_once DOL_DOCUMENT_ROOT.'/variants/class/ProductAttributeValue.class.php';

		$variants = array();

		//Attributes
		$sql = "SELECT DISTINCT fk_prod_attr, a.rang
FROM ".MAIN_DB_PREFIX."product_attribute_combination2val c2v LEFT JOIN ".MAIN_DB_PREFIX."product_attribute_combination c
    ON c2v.fk_prod_combination = c.rowid
  LEFT JOIN ".MAIN_DB_PREFIX."product p ON p.rowid = c.fk_product_child
  LEFT JOIN ".MAIN_DB_PREFIX."product_attribute a ON a.rowid = fk_prod_attr
WHERE c.fk_product_parent = ".(int) $productid." AND p.tosell = 1";

		$sql .= $this->db->order('a.rang', 'asc');

		$query = $this->db->query($sql);

		//Values
		while ($result = $this->db->fetch_object($query)) {
			$attr = new ProductAttribute($this->db);
			$attr->fetch($result->fk_prod_attr);

			$tmp = new stdClass();
			$tmp->id = $attr->id;
			$tmp->ref = $attr->ref;
			$tmp->label = $attr->label;
			$tmp->values = array();

			$attrval = new ProductAttributeValue($this->db);
			foreach ($res = $attrval->fetchAllByProductAttribute($attr->id, true) as $val) {
				$tmp->values[] = $val;
			}

			$variants[] = $tmp;
		}

		return $variants;
	}

	/**
	 * Creates a product combination. Check usages to find more about its use
	 *
	 * Format of $combinations array:
	 * array(
	 * 	0 => array(
	 * 		attr => value,
	 * 		attr2 => value
	 * 		[...]
	 * 		),
	 * [...]
	 * )
	 *
	 * @param Product $product Parent product
	 * @param array $combinations Attribute and value combinations.
	 * @param array $variations Price and weight variations
	 * @param bool $price_var_percent Is the price variation a relative variation?
	 * @param bool|float $forced_pricevar If the price variation is forced
	 * @param bool|float $forced_weightvar If the weight variation is forced
	 * @return int <0 KO, >0 OK
	 */
	public function createProductCombination(Product $product, array $combinations, array $variations, $price_var_percent = false, $forced_pricevar = false, $forced_weightvar = false)
	{
		global $db, $user, $conf;

		require_once DOL_DOCUMENT_ROOT.'/variants/class/ProductAttribute.class.php';
		require_once DOL_DOCUMENT_ROOT.'/variants/class/ProductAttributeValue.class.php';

		$db->begin();

		$newproduct = clone $product;

		//Final weight impact
		$weight_impact = $forced_weightvar;

		if ($forced_weightvar === false) {
			$weight_impact = 0;
		}

		//Final price impact
		$price_impact = $forced_pricevar;

		if ($forced_pricevar === false) {
			$price_impact = 0;
		}

		$newcomb = new ProductCombination($db);
		$existingCombination = $newcomb->fetchByProductCombination2ValuePairs($product->id, $combinations);

		if ($existingCombination) {
			$newcomb = $existingCombination;
		} else {
			$newcomb->fk_product_parent = $product->id;

			if ($newcomb->create($user) < 0) {		// Create 1 entry into product_attribute_combination (1 entry for all combinations)
				$db->rollback();
				return -1;
			}
		}

		$prodattr = new ProductAttribute($db);
		$prodattrval = new ProductAttributeValue($db);

		// $combination contains list of attributes pairs key->value. Example: array('id Color'=>id Blue, 'id Size'=>id Small, 'id Option'=>id val a, ...)
		//var_dump($combinations);
		foreach ($combinations as $currcombattr => $currcombval) {

			//This was checked earlier, so no need to double check
			$prodattr->fetch($currcombattr);
			$prodattrval->fetch($currcombval);

			//If there is an existing combination, there is no need to duplicate the valuepair
			if (!$existingCombination) {
				$tmp = new ProductCombination2ValuePair($db);
				$tmp->fk_prod_attr = $currcombattr;
				$tmp->fk_prod_attr_val = $currcombval;
				$tmp->fk_prod_combination = $newcomb->id;

				if ($tmp->create($user) < 0) {		// Create 1 entry into product_attribute_combination2val
					$db->rollback();
					return -1;
				}
			}

			if ($forced_weightvar === false) {
				$weight_impact += (float) price2num($variations[$currcombattr][$currcombval]['weight']);
			}
			if ($forced_pricevar === false) {
				$price_impact += (float) price2num($variations[$currcombattr][$currcombval]['price']);
			}

			if (isset($conf->global->PRODUIT_ATTRIBUTES_SEPARATOR)) {
			  $newproduct->ref .= $conf->global->PRODUIT_ATTRIBUTES_SEPARATOR . $prodattrval->ref;
			} else {
			  $newproduct->ref .= '_'.$prodattrval->ref;
			}

			//The first one should not contain a linebreak
			if ($newproduct->description) {
				$newproduct->description .= '<br>';
			}
			$newproduct->description .= '<strong>'.$prodattr->label.':</strong> '.$prodattrval->value;
		}

		$newcomb->variation_price_percentage = $price_var_percent;
		$newcomb->variation_price = $price_impact;
		$newcomb->variation_weight = $weight_impact;

		$newproduct->weight += $weight_impact;

		//To avoid wrong information in price history log
		$newproduct->price = 0;
		$newproduct->price_ttc = 0;
		$newproduct->price_min = 0;
		$newproduct->price_min_ttc = 0;

		// A new variant must use a new barcode (not same product)
		$newproduct->barcode = -1;

		// Now create the product
		//print 'Create prod '.$newproduct->ref.'<br>'."\n";
		$newprodid = $newproduct->create($user);
		if ($newprodid < 0)
		{
			//In case the error is not related with an already existing product
			if ($newproduct->error != 'ErrorProductAlreadyExists') {
			    $this->error[] = $newproduct->error;
			    $this->errors = $newproduct->errors;
				$db->rollback();
				return -1;
			}

			/**
			 * If there is an existing combination, then we update the prices and weight
			 * Otherwise, we try adding a random number to the ref
			 */

			if ($newcomb->fk_product_child) {
				$res = $newproduct->fetch($existingCombination->fk_product_child);
			} else {
				$orig_prod_ref = $newproduct->ref;
				$i = 1;

				do {
					$newproduct->ref = $orig_prod_ref.$i;
					$res = $newproduct->create($user);

					if ($newproduct->error != 'ErrorProductAlreadyExists') {
					    $this->errors[] = $newproduct->error;
						break;
					}

					$i++;
				} while ($res < 0);
			}

			if ($res < 0) {
				$db->rollback();
				return -1;
			}

			$newproduct->weight += $weight_impact;
		}

		$newcomb->fk_product_child = $newproduct->id;

		if ($newcomb->update($user) < 0)
		{
			$db->rollback();
			return -1;
		}

		$db->commit();
		return 1;
	}

	/**
	 * Copies all product combinations from the origin product to the destination product
	 *
	 * @param int $origProductId Origin product id
	 * @param Product $destProduct Destination product
	 * @return int >0 OK <0 KO
	 */
	public function copyAll($origProductId, Product $destProduct)
	{
		require_once DOL_DOCUMENT_ROOT.'/variants/class/ProductCombination2ValuePair.class.php';

		//To prevent a loop
		if ($origProductId == $destProduct->id) {
			return -1;
		}

		$prodcomb2val = new ProductCombination2ValuePair($this->db);

		//Retrieve all product combinations
		$combinations = $this->fetchAllByFkProductParent($origProductId);

		foreach ($combinations as $combination) {

			$variations = array();

			foreach ($prodcomb2val->fetchByFkCombination($combination->id) as $tmp_pc2v) {
				$variations[$tmp_pc2v->fk_prod_attr] = $tmp_pc2v->fk_prod_attr_val;
			}

			if ($this->createProductCombination(
				$destProduct,
				$variations,
				array(),
				$combination->variation_price_percentage,
				$combination->variation_price,
				$combination->variation_weight
			) < 0)
			{
				return -1;
			}
		}

		return 1;
	}
}
