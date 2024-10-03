<?php
/* Copyright (C) 2016		Marcos García			<marcosgdf@gmail.com>
 * Copyright (C) 2018		Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2022   	Open-Dsi				<support@open-dsi.fr>
 * Copyright (C) 2024		MDW						<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
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
 *	\file       htdocs/variants/class/ProductCombination.class.php
 *	\ingroup    variants
 *	\brief      File of the ProductCombination class
 */

/**
 * Class ProductCombination
 * Used to represent the relation between a product and one of its variants.
 *
 * Example: a product "shirt" has two variants "shirt XL white" and "shirt XL grey".
 * This is represented with two ProductCombination objects:
 * - One for "shirt XL white":
 *     * $object->fk_product_parent     ID of the Product object "shirt"
 *     * $object->fk_product_child      ID of the Product object "shirt XL white"
 * - Another for "shirt XL grey":
 *     * $object->fk_product_parent     ID of the Product object "shirt"
 *     * $object->fk_product_child      ID of the Product object "shirt XL grey"
 */
class ProductCombination
{
	/**
	 * Database handler
	 * @var DoliDB
	 */
	public $db;

	/**
	 * Rowid of this ProductCombination
	 * @var int
	 */
	public $id;

	/**
	 * Rowid of the parent Product
	 * @var int
	 */
	public $fk_product_parent;

	/**
	 * Rowid of the variant Product
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
	 * Can be an array if multiprice feature per level is enabled.
	 * @var bool|array
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

	/**
	 * Combination price level
	 * @var ProductCombinationLevel[]
	 */
	public $combination_price_levels;

	/**
	 * External ref
	 * @var string
	 */
	public $variation_ref_ext = '';

	/**
	 * Error message
	 * @var string
	 */
	public $error;

	/**
	 * Array of error messages
	 * @var string[]
	 */
	public $errors = array();

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
	 * Retrieves a ProductCombination by its rowid
	 *
	 * @param   int     	$rowid      ID of the ProductCombination
	 * @return  int<-1,1>               -1 if KO, 1 if OK
	 */
	public function fetch($rowid)
	{
		$sql = "SELECT rowid, fk_product_parent, fk_product_child, variation_price, variation_price_percentage, variation_weight, variation_ref_ext FROM ".MAIN_DB_PREFIX."product_attribute_combination WHERE rowid = ".((int) $rowid)." AND entity IN (".getEntity('product').")";

		$query = $this->db->query($sql);

		if (!$query) {
			return -1;
		}

		if (!$this->db->num_rows($query)) {
			return -1;
		}

		$obj = $this->db->fetch_object($query);

		$this->id = $obj->rowid;
		$this->fk_product_parent = $obj->fk_product_parent;
		$this->fk_product_child = $obj->fk_product_child;
		$this->variation_price = $obj->variation_price;
		$this->variation_price_percentage = $obj->variation_price_percentage;
		$this->variation_weight = $obj->variation_weight;
		$this->variation_ref_ext = $obj->variation_ref_ext;

		if (getDolGlobalString('PRODUIT_MULTIPRICES')) {
			$this->fetchCombinationPriceLevels();
		}

		return 1;
	}


	/**
	 * Retrieves combination price levels
	 *
	 * @param 	int 	$fk_price_level The price level to fetch, use 0 for all
	 * @param 	bool 	$useCache 		To use cache or not
	 * @return 	-1|1 					-1 if KO, 1 if OK
	 */
	public function fetchCombinationPriceLevels($fk_price_level = 0, $useCache = true)
	{
		global $conf;

		// Check cache
		if (!empty($this->combination_price_levels) && $useCache) {
			if ((!empty($fk_price_level) && isset($this->combination_price_levels[$fk_price_level])) || empty($fk_price_level)) {
				return 1;
			}
		}

		if (!is_array($this->combination_price_levels)
			|| empty($fk_price_level) // if fetch an unique level don't erase all already fetched
		) {
			$this->combination_price_levels = array();
		}

		$staticProductCombinationLevel = new ProductCombinationLevel($this->db);
		$combination_price_levels = $staticProductCombinationLevel->fetchAll($this->id, $fk_price_level);

		if (!is_array($combination_price_levels)) {
			return -1;
		}

		if (empty($combination_price_levels)) {
			/**
			 * for auto retrocompatibility with last behavior
			 */
			if ($fk_price_level > 0) {
				$combination_price_levels[$fk_price_level] = ProductCombinationLevel::createFromParent($this->db, $this, $fk_price_level);
			} else {
				$produit_multiprices_limit = getDolGlobalString('PRODUIT_MULTIPRICES_LIMIT');
				for ($i = 1; $i <= $produit_multiprices_limit; $i++) {
					$combination_price_levels[$i] = ProductCombinationLevel::createFromParent($this->db, $this, $i);
				}
			}
		}

		$this->combination_price_levels = $combination_price_levels;

		return 1;
	}

	/**
	 * Retrieves combination price levels
	 *
	 * @param 	int 	$clean 		Levels of PRODUIT_MULTIPRICES_LIMIT
	 * @return 	int 				Return integer <0 KO, >0 OK
	 */
	public function saveCombinationPriceLevels($clean = 1)
	{
		global $conf;

		$error = 0;

		$staticProductCombinationLevel = new ProductCombinationLevel($this->db);

		// Delete all
		if (empty($this->combination_price_levels)) {
			return $staticProductCombinationLevel->deleteAllForCombination($this->id);
		}

		// Clean not needed price levels (level higher than number max defined into setup)
		if ($clean) {
			$res = $staticProductCombinationLevel->clean($this->id);
			if ($res < 0) {
				$this->errors[] = 'Fail to clean not needed price levels';
				return -1;
			}
		}

		foreach ($this->combination_price_levels as $fk_price_level => $combination_price_level) {
			$res = $combination_price_level->save();
			if ($res < 1) {
				$this->error = 'Error saving combination price level '.$fk_price_level.' : '.$combination_price_level->error;
				$this->errors[] = $this->error;
				$error++;
				break;
			}
		}

		if ($error) {
			return $error * -1;
		} else {
			return 1;
		}
	}

	/**
	 * Retrieves information of a variant product and ID of its parent product.
	 *
	 * @param 	int 	$productid 				Product ID of variant
	 * @param	int		$donotloadpricelevel	Avoid loading price impact for each level. If PRODUIT_MULTIPRICES is not set, this has no effect.
	 * @return 	int 							Return integer <0 if KO, 0 if product ID is not ID of a variant product (so parent not found), >0 if OK (ID of parent)
	 */
	public function fetchByFkProductChild($productid, $donotloadpricelevel = 0)
	{
		global $conf;

		$sql = "SELECT rowid, fk_product_parent, fk_product_child, variation_price, variation_price_percentage, variation_weight";
		$sql .= " FROM ".MAIN_DB_PREFIX."product_attribute_combination WHERE fk_product_child = ".((int) $productid)." AND entity IN (".getEntity('product').")";

		$query = $this->db->query($sql);

		if (!$query) {
			return -1;
		}

		if (!$this->db->num_rows($query)) {
			return 0;
		}

		$result = $this->db->fetch_object($query);

		$this->id = $result->rowid;
		$this->fk_product_parent = $result->fk_product_parent;
		$this->fk_product_child = $result->fk_product_child;
		$this->variation_price = $result->variation_price;
		$this->variation_price_percentage = $result->variation_price_percentage;
		$this->variation_weight = $result->variation_weight;

		if (empty($donotloadpricelevel) && getDolGlobalString('PRODUIT_MULTIPRICES')) {
			$this->fetchCombinationPriceLevels();
		}

		return (int) $this->fk_product_parent;
	}

	/**
	 * Retrieves all product combinations by the product parent row id
	 *
	 * @param	int							$fk_product_parent	Rowid of parent product
	 * @param	bool						$sort_by_ref		Sort result by product child reference
	 * @return	int|ProductCombination[]						Return integer <0 KO
	 */
	public function fetchAllByFkProductParent($fk_product_parent, $sort_by_ref = false)
	{
		global $conf;

		$sql = "SELECT pac.rowid, pac.fk_product_parent, pac.fk_product_child, pac.variation_price, pac.variation_price_percentage, pac.variation_ref_ext, pac.variation_weight";
		$sql .= " FROM ".MAIN_DB_PREFIX."product_attribute_combination AS pac";
		if ($sort_by_ref) {
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product AS p ON p.rowid = pac.fk_product_child";
		}
		$sql .= " WHERE pac.fk_product_parent = ".((int) $fk_product_parent)." AND pac.entity IN (".getEntity('product').")";
		if ($sort_by_ref) {
			$sql .= $this->db->order('p.ref', 'ASC');
		}

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
			$tmp->variation_ref_ext = $result->variation_ref_ext;

			if (getDolGlobalString('PRODUIT_MULTIPRICES')) {
				$tmp->fetchCombinationPriceLevels();
			}

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
		$sql = "SELECT count(rowid) as nb FROM ".MAIN_DB_PREFIX."product_attribute_combination WHERE fk_product_parent = ".((int) $fk_product_parent)." AND entity IN (".getEntity('product').")";

		$resql = $this->db->query($sql);
		if ($resql) {
			$obj = $this->db->fetch_object($resql);
			if ($obj) {
				$nb = $obj->nb;
			}
		}

		return $nb;
	}

	/**
	 * Creates a product attribute combination
	 *
	 * @param	User	$user	Object user
	 * @return 	int				Return integer <0 if KO, >0 if OK
	 */
	public function create($user)
	{
		global $conf;

		/* $this->fk_product_child may be empty and will be filled later after subproduct has been created */

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."product_attribute_combination";
		$sql .= " (fk_product_parent, fk_product_child, variation_price, variation_price_percentage, variation_weight, variation_ref_ext, entity)";
		$sql .= " VALUES (".((int) $this->fk_product_parent).", ".((int) $this->fk_product_child).",";
		$sql .= (float) $this->variation_price.", ".(int) $this->variation_price_percentage.",";
		$sql .= (float) $this->variation_weight.", '".$this->db->escape($this->variation_ref_ext)."', ".(int) $this->entity.")";

		$resql = $this->db->query($sql);
		if ($resql) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.'product_attribute_combination');
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}

		if (getDolGlobalString('PRODUIT_MULTIPRICES')) {
			$res = $this->saveCombinationPriceLevels();
			if ($res < 0) {
				return -2;
			}
		}

		return 1;
	}

	/**
	 * Updates a product combination
	 *
	 * @param	User	$user		Object user
	 * @return 						int Return integer <0 KO, >0 OK
	 */
	public function update(User $user)
	{
		global $conf;

		$sql = "UPDATE ".MAIN_DB_PREFIX."product_attribute_combination";
		$sql .= " SET fk_product_parent = ".(int) $this->fk_product_parent.", fk_product_child = ".(int) $this->fk_product_child.",";
		$sql .= " variation_price = ".(float) $this->variation_price.", variation_price_percentage = ".(int) $this->variation_price_percentage.",";
		$sql .= " variation_ref_ext = '".$this->db->escape($this->variation_ref_ext)."',";
		$sql .= " variation_weight = ".(float) $this->variation_weight." WHERE rowid = ".((int) $this->id);

		$resql = $this->db->query($sql);
		if (!$resql) {
			return -1;
		}

		if (getDolGlobalString('PRODUIT_MULTIPRICES')) {
			$res = $this->saveCombinationPriceLevels();
			if ($res < 0) {
				return -2;
			}
		}

		$parent = new Product($this->db);
		$parent->fetch($this->fk_product_parent);

		$this->updateProperties($parent, $user);

		return 1;
	}

	/**
	 * Deletes a product combination
	 *
	 * @param 	User 	$user	Object user
	 * @return 	int 			Return integer <0 if KO, >0 if OK
	 */
	public function delete(User $user)
	{
		$this->db->begin();

		$comb2val = new ProductCombination2ValuePair($this->db);
		$comb2val->deleteByFkCombination($this->id);

		// remove combination price levels
		if (!$this->db->query("DELETE FROM ".MAIN_DB_PREFIX."product_attribute_combination_price_level WHERE fk_product_attribute_combination = ".(int) $this->id)) {
			$this->db->rollback();
			return -1;
		}

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
	 * @return int Return integer <0 KO >0 OK
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
	 * Updates the weight of the child product. The price must be updated using Product::updatePrices.
	 * This method is called by the update() of a product.
	 *
	 * @param	Product $parent 	Parent product
	 * @param	User	$user		Object user
	 * @return 	int 				>0 if OK, <0 if KO
	 */
	public function updateProperties(Product $parent, User $user)
	{
		global $conf;

		$this->db->begin();

		$child = new Product($this->db);
		$child->fetch($this->fk_product_child);

		$child->price_autogen = $parent->price_autogen;
		$child->weight = $parent->weight;
		// Only when Parent Status are updated
		if (is_object($parent->oldcopy) && !$parent->oldcopy->isEmpty() && ($parent->status != $parent->oldcopy->status)) {
			$child->status = $parent->status;
		}
		if (is_object($parent->oldcopy) && !$parent->oldcopy->isEmpty() && ($parent->status_buy != $parent->oldcopy->status_buy)) {
			$child->status_buy = $parent->status_buy;
		}

		if ($this->variation_weight) {	// If we must add a delta on weight
			$child->weight = ($child->weight ? $child->weight : 0) + $this->variation_weight;
		}
		$child->weight_units = $parent->weight_units;

		// Don't update the child label if the user has already modified it.
		if ($child->label == $parent->label) {
			// This will trigger only at variant creation time
			$varlabel               = $this->getCombinationLabel($this->fk_product_child);
			$child->label           = $parent->label.$varlabel;
		}


		if ($child->update($child->id, $user) > 0) {
			$new_vat = $parent->tva_tx;
			$new_npr = $parent->tva_npr;

			// MultiPrix
			if (getDolGlobalString('PRODUIT_MULTIPRICES')) {
				$produit_multiprices_limit = getDolGlobalString('PRODUIT_MULTIPRICES_LIMIT');
				for ($i = 1; $i <= $produit_multiprices_limit; $i++) {
					if ($parent->multiprices[$i] != '' || isset($this->combination_price_levels[$i]->variation_price)) {
						$new_type = empty($parent->multiprices_base_type[$i]) ? 'HT' : $parent->multiprices_base_type[$i];
						$new_min_price = $parent->multiprices_min[$i];
						$variation_price = (float) (!isset($this->combination_price_levels[$i]->variation_price) ? $this->variation_price : $this->combination_price_levels[$i]->variation_price);
						$variation_price_percentage = (float) (!isset($this->combination_price_levels[$i]->variation_price_percentage) ? $this->variation_price_percentage : $this->combination_price_levels[$i]->variation_price_percentage);

						if ($parent->prices_by_qty_list[$i]) {
							$new_psq = 1;
						} else {
							$new_psq = 0;
						}

						if ($new_type == 'TTC') {
							$new_price = $parent->multiprices_ttc[$i];
						} else {
							$new_price = $parent->multiprices[$i];
						}

						if ($variation_price_percentage) {
							if ($new_price != 0) {
								$new_price *= 1 + ($variation_price / 100);
							}
						} else {
							$new_price += $variation_price;
						}

						$ret = $child->updatePrice($new_price, $new_type, $user, $new_vat, $new_min_price, $i, $new_npr, $new_psq, 0, array(), $parent->default_vat_code);

						if ($ret < 0) {
							$this->db->rollback();
							$this->error = $child->error;
							$this->errors = $child->errors;
							return $ret;
						}
					}
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

				if ($this->variation_price_percentage) {
					if ($new_price != 0) {
						$new_price *= 1 + ($this->variation_price / 100);
					}
				} else {
					$new_price += $this->variation_price;
				}

				$ret = $child->updatePrice($new_price, $new_type, $user, $new_vat, $new_min_price, 1, $new_npr, $new_psq);

				if ($ret < 0) {
					$this->db->rollback();
					$this->error = $child->error;
					$this->errors = $child->errors;
					return $ret;
				}
			}

			$this->db->commit();

			return 1;
		}

		$this->db->rollback();
		$this->error = $child->error;
		$this->errors = $child->errors;
		return -1;
	}

	/**
	 * Retrieves the combination that matches the given features.
	 *
	 * @param 	int 						$prodid 	Id of parent product
	 * @param 	array<string,string> 		$features 	Format: [$attr] => $attr_val
	 * @return 	false|ProductCombination 				False if not found
	 */
	public function fetchByProductCombination2ValuePairs($prodid, array $features)
	{
		require_once DOL_DOCUMENT_ROOT.'/variants/class/ProductCombination2ValuePair.class.php';

		$actual_comp = array();

		$prodcomb2val = new ProductCombination2ValuePair($this->db);
		$prodcomb = new ProductCombination($this->db);

		$features = array_filter(
			$features,
			/**
			 * @param mixed $v Feature information of a product.
			 * @return bool
			 */
			static function ($v) {
				return !empty($v);
			}
		);

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
	 * Retrieves all unique attributes for a parent product
	 * (filtered on its 'to sell' variants)
	 *
	 * @param	int $productid			Parent Product rowid
	 * @return	array<object{id:int,ref:string,label:string,values:ProductAttributeValue[]}>		Array of attributes
	 */
	public function getUniqueAttributesAndValuesByFkProductParent($productid)
	{
		require_once DOL_DOCUMENT_ROOT.'/variants/class/ProductAttribute.class.php';
		require_once DOL_DOCUMENT_ROOT.'/variants/class/ProductAttributeValue.class.php';

		// Attributes
		// Select all unique attributes of the variants (which are to sell) of a given parent product.
		$sql = "SELECT DISTINCT c2v.fk_prod_attr, a.position";
		$sql .= " FROM ".MAIN_DB_PREFIX."product_attribute_combination2val c2v";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product_attribute_combination c";
		$sql .= "   ON c2v.fk_prod_combination = c.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product p";
		$sql .= "   ON p.rowid = c.fk_product_child";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product_attribute a";
		$sql .= "   ON a.rowid = fk_prod_attr";
		$sql .= " WHERE c.fk_product_parent = ".((int) $productid);
		$sql .= " AND p.tosell = 1";
		$sql .= $this->db->order('a.position', 'asc');

		$resql = $this->db->query($sql);

		// Values
		$variants = array();
		while ($obj = $this->db->fetch_object($resql)) {
			$attr = new ProductAttribute($this->db);
			$attr->fetch($obj->fk_prod_attr);

			$tmp = new stdClass();
			$tmp->id = $attr->id;
			$tmp->ref = $attr->ref;
			$tmp->label = $attr->label;
			$tmp->values = array();

			$attrval = new ProductAttributeValue($this->db);
			// fetch only the used values of this attribute
			foreach ($attrval->fetchAllByProductAttribute($attr->id, true) as $val) {
				'@phan-var-force ProductAttributeValue $val';
				$tmp->values[] = $val;
			}

			$variants[] = $tmp;
		}

		return $variants;
	}

	/**
	 * Creates a product combination. Check usages to find more about its use
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
	 * @param User                      $user                   User
	 * @param Product                   $product                Parent Product
	 * @param array<int,int>            $combinations           Attribute and value combinations.
	 * @param array<int,array<int,array{weight:string|float,price:string|float}>> $variations 	Price and weight variations (example: $variations[fk_product_attribute][fk_product_attribute_value]['weight'])
	 * @param bool|bool[]               $price_var_percent      Is the price variation value a relative variation (in %)? (it is an array if global constant "PRODUIT_MULTIPRICES" is on)
	 * @param false|float|float[]       $forced_pricevar        Value of the price variation if it is forced ; in currency or percent. (it is an array if global constant "PRODUIT_MULTIPRICES" is on)
	 * @param false|float               $forced_weightvar       Value of the weight variation if it is forced
	 * @param false|string              $forced_refvar          Value of the reference if it is forced
	 * @param string                    $ref_ext                External reference
	 * @return int<-1,1>                                        Return integer <0 KO, >0 OK
	 */
	public function createProductCombination(User $user, Product $product, array $combinations, array $variations, $price_var_percent = false, $forced_pricevar = false, $forced_weightvar = false, $forced_refvar = false, $ref_ext = '')
	{
		global $conf;

		require_once DOL_DOCUMENT_ROOT.'/variants/class/ProductAttribute.class.php';
		require_once DOL_DOCUMENT_ROOT.'/variants/class/ProductAttributeValue.class.php';

		$this->db->begin();

		$price_impact = array(1 => 0); // init level price impact

		$forced_refvar = trim((string) $forced_refvar);

		if (!empty($forced_refvar) && $forced_refvar != $product->ref) {
			$existingProduct = new Product($this->db);
			$result = $existingProduct->fetch(0, $forced_refvar);
			if ($result > 0) {
				$newproduct = $existingProduct;
			} else {
				$existingProduct = false;
				$newproduct = clone $product;
				$newproduct->ref = $forced_refvar;
			}
		} else {
			$forced_refvar = false;
			$existingProduct = false;
			$newproduct = clone $product;
		}

		//Final weight impact
		$weight_impact = (float) $forced_weightvar; // If false, return 0

		//Final price impact
		if (!is_array($forced_pricevar)) {
			$price_impact[1] = (float) $forced_pricevar; // If false, return 0
		} else {
			$price_impact = $forced_pricevar;
		}

		if (!array($price_var_percent)) {
			$price_var_percent[1] = (float) $price_var_percent;
		}

		$newcomb = new ProductCombination($this->db);
		$existingCombination = $newcomb->fetchByProductCombination2ValuePairs($product->id, $combinations);

		if ($existingCombination) {
			$newcomb = $existingCombination;
		} else {
			$newcomb->fk_product_parent = $product->id;

			// Create 1 entry into product_attribute_combination (1 entry for each combinations). This init also $newcomb->id
			$result = $newcomb->create($user);
			if ($result < 0) {
				$this->error = $newcomb->error;
				$this->errors = $newcomb->errors;
				$this->db->rollback();
				return -1;
			}
		}

		$prodattr = new ProductAttribute($this->db);
		$prodattrval = new ProductAttributeValue($this->db);

		// $combination contains list of attributes pairs key->value. Example: array('id Color'=>id Blue, 'id Size'=>id Small, 'id Option'=>id val a, ...)
		foreach ($combinations as $currcombattr => $currcombval) {
			//This was checked earlier, so no need to double check
			$prodattr->fetch($currcombattr);
			$prodattrval->fetch($currcombval);

			//If there is an existing combination, there is no need to duplicate the valuepair
			if (!$existingCombination) {
				$tmp = new ProductCombination2ValuePair($this->db);
				$tmp->fk_prod_attr = $currcombattr;
				$tmp->fk_prod_attr_val = $currcombval;
				$tmp->fk_prod_combination = $newcomb->id;

				if ($tmp->create($user) < 0) {		// Create 1 entry into product_attribute_combination2val
					$this->error = $tmp->error;
					$this->errors = $tmp->errors;
					$this->db->rollback();
					return -1;
				}
			}
			if ($forced_weightvar === false) {
				$weight_impact += (float) price2num($variations[$currcombattr][$currcombval]['weight']);
			}
			if ($forced_pricevar === false) {
				$price_impact[1] += (float) price2num($variations[$currcombattr][$currcombval]['price']);

				// Manage Price levels
				if (getDolGlobalString('PRODUIT_MULTIPRICES')) {
					$produit_multiprices_limit = getDolGlobalString('PRODUIT_MULTIPRICES_LIMIT');
					for ($i = 2; $i <= $produit_multiprices_limit; $i++) {
						$price_impact[$i] += (float) price2num($variations[$currcombattr][$currcombval]['price']);
					}
				}
			}

			if ($forced_refvar === false) {
				if (isset($conf->global->PRODUIT_ATTRIBUTES_SEPARATOR)) {
					$newproduct->ref .= getDolGlobalString('PRODUIT_ATTRIBUTES_SEPARATOR') . $prodattrval->ref;
				} else {
					$newproduct->ref .= '_'.$prodattrval->ref;
				}
			}

			//The first one should not contain a linebreak
			if ($newproduct->description) {
				$newproduct->description .= '<br>';
			}
			$newproduct->description .= '<strong>'.$prodattr->label.':</strong> '.$prodattrval->value;
		}

		$newcomb->variation_price_percentage = $price_var_percent[1];
		$newcomb->variation_price = $price_impact[1];
		$newcomb->variation_weight = $weight_impact;
		$newcomb->variation_ref_ext = $this->db->escape($ref_ext);

		// Init price level
		if (getDolGlobalString('PRODUIT_MULTIPRICES')) {
			$produit_multiprices_limit = getDolGlobalString('PRODUIT_MULTIPRICES_LIMIT');
			for ($i = 1; $i <= $produit_multiprices_limit; $i++) {
				$productCombinationLevel = new ProductCombinationLevel($this->db);
				$productCombinationLevel->fk_product_attribute_combination = $newcomb->id;
				$productCombinationLevel->fk_price_level = $i;
				$productCombinationLevel->variation_price = $price_impact[$i];

				if (is_array($price_var_percent)) {
					$productCombinationLevel->variation_price_percentage = (empty($price_var_percent[$i]) ? false : $price_var_percent[$i]);
				} else {
					$productCombinationLevel->variation_price_percentage = $price_var_percent;
				}

				$newcomb->combination_price_levels[$i] = $productCombinationLevel;
			}
		}
		//var_dump($newcomb->combination_price_levels);

		$newproduct->weight += $weight_impact;

		// Now create the product
		//print 'Create prod '.$newproduct->ref.'<br>'."\n";
		if ($existingProduct === false) {
			//To avoid wrong information in price history log
			$newproduct->price = 0;
			$newproduct->price_ttc = 0;
			$newproduct->price_min = 0;
			$newproduct->price_min_ttc = 0;

			// A new variant must use a new barcode (not same product)
			$newproduct->barcode = -1;
			$result = $newproduct->create($user);

			if ($result < 0) {
				//In case the error is not related with an already existing product
				if ($newproduct->error != 'ErrorProductAlreadyExists') {
					$this->error = $newproduct->error;
					$this->errors = $newproduct->errors;
					$this->db->rollback();
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
					$this->db->rollback();
					return -1;
				}
			}
		} else {
			$result = $newproduct->update($newproduct->id, $user);
			if ($result < 0) {
				$this->db->rollback();
				return -1;
			}
		}

		$newcomb->fk_product_child = $newproduct->id;

		if ($newcomb->update($user) < 0) {
			$this->error = $newcomb->error;
			$this->errors = $newcomb->errors;
			$this->db->rollback();
			return -1;
		}

		$this->db->commit();
		return $newproduct->id;
	}

	/**
	 * Copies all product combinations from the origin product to the destination product
	 *
	 * @param 	User 	$user	Object user
	 * @param   int     $origProductId  Origin product id
	 * @param   Product $destProduct    Destination product
	 * @return  int                     >0 OK <0 KO
	 */
	public function copyAll(User $user, $origProductId, Product $destProduct)
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
				$user,
				$destProduct,
				$variations,
				array(),
				$combination->variation_price_percentage,
				$combination->variation_price,
				$combination->variation_weight
			) < 0) {
				return -1;
			}
		}

		return 1;
	}

	/**
	 * Return label for combinations
	 * @param   int 	$prod_child		id of child
	 * @return  string					combination label
	 */
	public function getCombinationLabel($prod_child)
	{
		$label = '';
		$sql = 'SELECT pav.value AS label';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'product_attribute_combination pac';
		$sql .= ' INNER JOIN '.MAIN_DB_PREFIX.'product_attribute_combination2val pac2v ON pac2v.fk_prod_combination=pac.rowid';
		$sql .= ' INNER JOIN '.MAIN_DB_PREFIX.'product_attribute_value pav ON pav.rowid=pac2v.fk_prod_attr_val';
		$sql .= ' WHERE pac.fk_product_child='.((int) $prod_child);

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);

			$i = 0;

			while ($i < $num) {
				$obj = $this->db->fetch_object($resql);

				if ($obj->label) {
					$label .= ' '.$obj->label;
				}
				$i++;
			}
		}
		return $label;
	}
}



/**
 * Class ProductCombinationLevel
 * Used to represent a product combination Level
 */
class ProductCombinationLevel
{
	/**
	 * Database handler
	 * @var DoliDB
	 */
	public $db;

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'product_attribute_combination_price_level';

	/**
	 * Rowid of combination
	 * @var int
	 */
	public $id;

	/**
	 * Rowid of parent product combination
	 * @var int
	 */
	public $fk_product_attribute_combination;

	/**
	 * Combination price level
	 * @var int
	 */
	public $fk_price_level;

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
	 * @param DoliDB $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		$this->db = $db;
	}

	/**
	 * Retrieves a combination level by its rowid
	 *
	 * @param int $rowid Row id
	 * @return int Return integer <0 KO, >0 OK
	 */
	public function fetch($rowid)
	{
		$sql = "SELECT rowid, fk_product_attribute_combination, fk_price_level, variation_price, variation_price_percentage";
		$sql .= " FROM ".MAIN_DB_PREFIX.$this->table_element;
		$sql .= " WHERE rowid = ".(int) $rowid;

		$resql = $this->db->query($sql);
		if ($resql) {
			$obj = $this->db->fetch_object($resql);
			if ($obj) {
				return $this->fetchFormObj($obj);
			}
		}

		return -1;
	}


	/**
	 * Retrieves combination price levels
	 *
	 * @param 	int 	$fk_product_attribute_combination		Id of product combination
	 * @param 	int 	$fk_price_level 						The price level to fetch, use 0 for all
	 * @return  mixed											self[] | -1 on KO
	 */
	public function fetchAll($fk_product_attribute_combination, $fk_price_level = 0)
	{
		$result = array();

		$sql = "SELECT rowid, fk_product_attribute_combination, fk_price_level, variation_price, variation_price_percentage";
		$sql .= " FROM ".MAIN_DB_PREFIX.$this->table_element;
		$sql .= " WHERE fk_product_attribute_combination = ".intval($fk_product_attribute_combination);
		if (!empty($fk_price_level)) {
			$sql .= ' AND fk_price_level = '.intval($fk_price_level);
		}

		$res = $this->db->query($sql);
		if ($res) {
			if ($this->db->num_rows($res) > 0) {
				while ($obj = $this->db->fetch_object($res)) {
					$productCombinationLevel = new ProductCombinationLevel($this->db);
					$productCombinationLevel->fetchFormObj($obj);
					$result[$obj->fk_price_level] = $productCombinationLevel;
				}
			}
		} else {
			return -1;
		}

		return $result;
	}

	/**
	 * Assign vars form an stdclass like sql obj
	 *
	 * @param 	Object 	$obj		Object resultset
	 * @return 	int 				Return integer <0 KO, >0 OK
	 */
	public function fetchFormObj($obj)
	{
		if (!$obj) {
			return -1;
		}

		$this->id = $obj->rowid;
		$this->fk_product_attribute_combination = (int) $obj->fk_product_attribute_combination;
		$this->fk_price_level = intval($obj->fk_price_level);
		$this->variation_price = (float) $obj->variation_price;
		$this->variation_price_percentage = (bool) $obj->variation_price_percentage;

		return 1;
	}


	/**
	 * Save a price impact of a product combination for a price level
	 *
	 * @return int 		Return integer <0 KO, >0 OK
	 */
	public function save()
	{
		if (($this->id > 0 && empty($this->fk_product_attribute_combination)) || empty($this->fk_price_level)) {
			return -1;
		}

		// Check if level exist in DB before add
		if ($this->fk_product_attribute_combination > 0 && empty($this->id)) {
			$sql = "SELECT rowid id";
			$sql .= " FROM ".MAIN_DB_PREFIX.$this->table_element;
			$sql .= " WHERE fk_product_attribute_combination = ".(int) $this->fk_product_attribute_combination;
			$sql .= ' AND fk_price_level = '.((int) $this->fk_price_level);

			$resql = $this->db->query($sql);
			if ($resql) {
				$obj = $this->db->fetch_object($resql);
				if ($obj) {
					$this->id = $obj->id;
				}
			}
		}

		// Update
		if (!empty($this->id)) {
			$sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element;
			$sql .= ' SET variation_price = '.(float) $this->variation_price.' , variation_price_percentage = '.intval($this->variation_price_percentage);
			$sql .= ' WHERE rowid = '.((int) $this->id);

			$res = $this->db->query($sql);
			if ($res > 0) {
				return $this->id;
			} else {
				$this->error = $this->db->error();
				$this->errors[] = $this->error;
				return -1;
			}
		} else {
			// Add
			$sql = "INSERT INTO ".MAIN_DB_PREFIX.$this->table_element." (";
			$sql .= "fk_product_attribute_combination, fk_price_level, variation_price, variation_price_percentage";
			$sql .= ") VALUES (";
			$sql .= (int) $this->fk_product_attribute_combination;
			$sql .= ", ".intval($this->fk_price_level);
			$sql .= ", ".(float) $this->variation_price;
			$sql .= ", ".intval($this->variation_price_percentage);
			$sql .= ")";

			$res = $this->db->query($sql);
			if ($res) {
				$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.$this->table_element);
			} else {
				$this->error = $this->db->error();
				$this->errors[] = $this->error;
				return -1;
			}
		}

		return $this->id;
	}


	/**
	 * delete
	 *
	 * @return int Return integer <0 KO, >0 OK
	 */
	public function delete()
	{
		$sql = "DELETE FROM ".MAIN_DB_PREFIX.$this->table_element." WHERE rowid = ".(int) $this->id;
		$res = $this->db->query($sql);

		return $res ? 1 : -1;
	}


	/**
	 * delete all for a combination
	 *
	 * @param 	int		$fk_product_attribute_combination	Id of combination
	 * @return 	int 										Return integer <0 KO, >0 OK
	 */
	public function deleteAllForCombination($fk_product_attribute_combination)
	{
		$sql = "DELETE FROM ".MAIN_DB_PREFIX.$this->table_element." WHERE fk_product_attribute_combination = ".(int) $fk_product_attribute_combination;
		$res = $this->db->query($sql);

		return $res ? 1 : -1;
	}


	/**
	 * Clean not needed price levels for a combination
	 *
	 * @param 	int		$fk_product_attribute_combination	Id of combination
	 * @return 	int 										Return integer <0 KO, >0 OK
	 */
	public function clean($fk_product_attribute_combination)
	{
		global $conf;

		$sql = "DELETE FROM ".MAIN_DB_PREFIX.$this->table_element;
		$sql .= " WHERE fk_product_attribute_combination = ".(int) $fk_product_attribute_combination;
		$sql .= " AND fk_price_level > ".(int) getDolGlobalString('PRODUIT_MULTIPRICES_LIMIT');
		$res = $this->db->query($sql);

		return $res ? 1 : -1;
	}


	/**
	 * Create new Product Combination Price level from Parent
	 *
	 * @param DoliDB 				$db						Database handler
	 * @param ProductCombination 	$productCombination		Product combination
	 * @param int					$fkPriceLevel			Price level
	 * @return ProductCombinationLevel
	 */
	public static function createFromParent(DoliDB $db, ProductCombination $productCombination, $fkPriceLevel)
	{
		$productCombinationLevel = new self($db);
		$productCombinationLevel->fk_price_level = $fkPriceLevel;
		$productCombinationLevel->fk_product_attribute_combination = $productCombination->id;
		$productCombinationLevel->variation_price = $productCombination->variation_price;
		$productCombinationLevel->variation_price_percentage = (bool) $productCombination->variation_price_percentage;

		return $productCombinationLevel;
	}
}
