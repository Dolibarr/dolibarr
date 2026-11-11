<?php
/* Copyright (C) 2026		ATM Consulting		<contact@atm-consulting.fr>
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
 * \file       htdocs/core/triggers/interface_50_modVariants_VariantsImport.class.php
 * \ingroup    variants
 * \brief      Trigger file that runs the business logic of the variants on import
 */
require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';

/**
 * Class of triggered functions that reconcile imported variants with the business logic
 * of the screen.
 *
 * The import engine only runs raw SQL: it writes a row of llx_product_attribute_combination
 * without creating nor refreshing the child product. This trigger calls back
 * ProductCombination::createProductCombination(), the very method used by the screen
 * (variants/combinations.php) and by the REST API (api_products.class.php), so that an
 * imported variant is identical to a variant entered on screen.
 *
 * It never rolls back: it runs inside the transaction of the import and DoliDB::rollback()
 * only decrements the counter when the transaction is nested. Returning a negative value is
 * the only way to have imports/import.php roll the whole import back.
 *
 * Limitation: the screen refuses two combinations of the same parent carrying the same set of
 * attribute values, this trigger cannot. The value set of an imported variant is spread over
 * several lines of the file and the engine offers no end of import event in strict_line mode,
 * so a set read here is only a partial set and comparing it would reject legitimate variants
 * (a parent declined by colour alone and by colour plus size shares a partial set). Only the
 * weaker invariant that can be decided on a single line is enforced: a child product belongs
 * to exactly one combination.
 */
class InterfaceVariantsImport extends DolibarrTriggers
{
	/**
	 * Tables of the module that need a business reconciliation after an import
	 */
	const VARIANTS_TABLES = array(
		'product_attribute',
		'product_attribute_value',
		'product_attribute_combination',
		'product_attribute_combination2val',
		'product_attribute_combination_price_level',
	);

	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
		$this->errors = array();

		$this->name = preg_replace('/^Interface/i', '', get_class($this));
		$this->family = "products";
		$this->description = "Triggers of this module run the business logic of the variants when they are imported.";
		$this->version = self::VERSIONS['prod'];
		$this->picto = 'product';
	}

	/**
	 * Function called when a Dolibarr business event is done.
	 *
	 * @param	string		$action		Event action code
	 * @param	CommonObject|stdClass		$object		Object, a business object prototype or a stdClass when it comes from the import engine
	 * @param	User		$user		Object user
	 * @param	Translate	$langs		Object langs
	 * @param	Conf		$conf		Object conf
	 * @return	int						Return integer <0 if KO, 0 if no trigger ran, >0 if OK
	 */
	public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
	{
		if (!isModEnabled('variants')) {
			return 0;
		}
		if (empty($object->context['import'])) {
			return 0;
		}

		$langs->load('products');

		if ($action == 'IMPORT_BULK_DONE') {
			return $this->refuseBulkModeOnVariants($object, $langs);
		}

		// The object provided by the import engine is only a prototype or a stdClass, carrying
		// nothing but id, rowid, import_key, context, element and table_element. Never assume a
		// hydrated business object, always refetch.
		$rowid = 0;
		if (!empty($object->id)) {
			$rowid = (int) $object->id;
		} elseif (!empty($object->rowid)) {
			$rowid = (int) $object->rowid;
		}
		if (empty($rowid)) {
			return 0;
		}

		switch ($action) {
			case 'PRODUCT_ATTRIBUTE_COMBINATION_CREATE':
			case 'PRODUCT_ATTRIBUTE_COMBINATION_MODIFY':
				dol_syslog(get_class($this)." for action '".$action."' launched by ".__FILE__.". id=".$rowid);
				return $this->reconcileCombination($rowid, $user, $langs);
			case 'PRODUCT_ATTRIBUTE_COMBINATION2VAL_CREATE':
			case 'PRODUCT_ATTRIBUTE_COMBINATION2VAL_MODIFY':
				dol_syslog(get_class($this)." for action '".$action."' launched by ".__FILE__.". id=".$rowid);
				return $this->reconcileValuePair($rowid, $user, $langs);
			case 'PRODUCT_ATTRIBUTE_COMBINATION_PRICE_LEVEL_CREATE':
			case 'PRODUCT_ATTRIBUTE_COMBINATION_PRICE_LEVEL_MODIFY':
				dol_syslog(get_class($this)." for action '".$action."' launched by ".__FILE__.". id=".$rowid);
				return $this->reconcilePriceLevel($rowid, $user, $langs);
		}

		return 0;
	}

	/**
	 * Refuse the fast_bulk mode on the tables of the module.
	 *
	 * In fast_bulk mode no per line trigger is emitted, so nothing would ever create the link
	 * with the child product nor propagate the prices: the import would leave combinations that
	 * are invisible on the product card.
	 *
	 * @param	CommonObject|stdClass		$object		Object carrying bulk_stats
	 * @param	Translate	$langs		Object langs
	 * @return	int						0 if not our business, -1 to abort the import
	 */
	private function refuseBulkModeOnVariants($object, Translate $langs)
	{
		$tables = array();
		if (!empty($object->bulk_stats['tables']) && is_array($object->bulk_stats['tables'])) {
			$tables = array_keys($object->bulk_stats['tables']);
		}

		if (!count(array_intersect($tables, self::VARIANTS_TABLES))) {
			return 0;
		}

		$this->errors[] = $langs->trans('ErrorVariantsImportNeedsStrictLineMode');
		dol_syslog(get_class($this)."::refuseBulkModeOnVariants variants tables imported in fast_bulk mode", LOG_ERR);

		return -1;
	}

	/**
	 * Count the combinations matching the given products, in the current entity.
	 * A parameter left to 0 is not part of the filter.
	 *
	 * @param	int		$fkproductparent		Parent product to match
	 * @param	int		$fkproductchild			Child product to match
	 * @param	int		$fkproductparentnot		Parent product to exclude
	 * @return	int								Number of rows, -1 on SQL error
	 */
	private function countCombinations($fkproductparent = 0, $fkproductchild = 0, $fkproductparentnot = 0)
	{
		$sql = "SELECT COUNT(rowid) as nb FROM ".MAIN_DB_PREFIX."product_attribute_combination";
		$sql .= " WHERE entity IN (".getEntity('product').")";
		if ($fkproductparent > 0) {
			$sql .= " AND fk_product_parent = ".((int) $fkproductparent);
		}
		if ($fkproductchild > 0) {
			$sql .= " AND fk_product_child = ".((int) $fkproductchild);
		}
		if ($fkproductparentnot > 0) {
			$sql .= " AND fk_product_parent <> ".((int) $fkproductparentnot);
		}

		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->errors[] = $this->db->lasterror();
			dol_syslog(get_class($this)."::countCombinations ".$this->db->lasterror(), LOG_ERR);
			return -1;
		}
		$obj = $this->db->fetch_object($resql);
		$this->db->free($resql);

		return empty($obj) ? 0 : (int) $obj->nb;
	}

	/**
	 * Check the consistency of an imported combination and propagate it to the child product.
	 *
	 * @param	int			$rowid	Row id of the combination
	 * @param	User		$user	Object user
	 * @param	Translate	$langs	Object langs
	 * @return	int					Return integer <0 if KO, >0 if OK
	 */
	private function reconcileCombination($rowid, User $user, Translate $langs)
	{
		require_once DOL_DOCUMENT_ROOT.'/variants/class/ProductCombination.class.php';
		require_once DOL_DOCUMENT_ROOT.'/variants/class/ProductCombination2ValuePair.class.php';
		require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

		$combination = new ProductCombination($this->db);
		if ($combination->fetch($rowid) <= 0) {
			$this->errors[] = $langs->trans('ErrorRecordNotFound').' (combination '.$rowid.')';
			dol_syslog(get_class($this)."::reconcileCombination combination ".$rowid." not found", LOG_ERR);
			return -1;
		}

		if ($combination->fk_product_parent == $combination->fk_product_child) {
			$this->errors[] = $langs->trans('ErrorVariantChildIsParent');
			dol_syslog(get_class($this)."::reconcileCombination product ".$combination->fk_product_child." is its own variant", LOG_ERR);
			return -1;
		}

		$parent = new Product($this->db);
		if ($parent->fetch($combination->fk_product_parent) <= 0) {
			$this->errors[] = $langs->trans('ErrorVariantParentNotFound');
			dol_syslog(get_class($this)."::reconcileCombination parent product ".$combination->fk_product_parent." not found", LOG_ERR);
			return -1;
		}

		$child = new Product($this->db);
		if ($child->fetch($combination->fk_product_child) <= 0) {
			// The import never creates a product: the child must exist beforehand.
			$this->errors[] = $langs->trans('ErrorVariantChildNotFound');
			dol_syslog(get_class($this)."::reconcileCombination child product ".$combination->fk_product_child." not found", LOG_ERR);
			return -1;
		}

		// createProductCombination() resolves the child product by its ref, restricted to the
		// entities visible from the current one, while Product::fetch() by id crosses every
		// entity (product.class.php filters on entity only in the ref branch). When the two
		// disagree, the creation path of createProductCombination() would be taken and a clone
		// of the parent product would be created: the import must never create a product.
		$childbyref = new Product($this->db);
		if ($childbyref->fetch(0, $child->ref) <= 0 || $childbyref->id != $child->id) {
			$this->errors[] = $langs->trans('ErrorVariantChildNotFound');
			dol_syslog(get_class($this)."::".__FUNCTION__." child ".$child->id." (".$child->ref.") is not resolvable by ref in the current entity", LOG_ERR);
			return -1;
		}

		// A parent must not be a variant itself, a child must not already be the variant of
		// another parent, and two combinations must not point to the same child product: the
		// variant resolution of the documents would become non deterministic. None of these
		// checks exists anywhere else in the application. They are written as counts on purpose:
		// fetchByFkProductChild() returns the first row of an unordered result, which is not
		// decidable when the row being imported is itself a candidate.
		$nbparentasvariant = $this->countCombinations(0, (int) $combination->fk_product_parent);
		if ($nbparentasvariant < 0) {
			return -1;
		}
		if ($nbparentasvariant > 0) {
			$this->errors[] = $langs->trans('ErrorVariantParentIsAVariant', $parent->ref);
			dol_syslog(get_class($this)."::reconcileCombination ".$parent->ref." is itself a variant of ".$nbparentasvariant." parent(s)", LOG_ERR);
			return -1;
		}

		$nbotherparent = $this->countCombinations(0, (int) $combination->fk_product_child, (int) $combination->fk_product_parent);
		if ($nbotherparent < 0) {
			return -1;
		}
		if ($nbotherparent > 0) {
			$this->errors[] = $langs->trans('ErrorVariantChildOfAnotherParent', $child->ref);
			dol_syslog(get_class($this)."::reconcileCombination ".$child->ref." is already a variant of ".$nbotherparent." other parent(s)", LOG_ERR);
			return -1;
		}

		// Symmetrical check of the one above: the child must not already be the parent of another
		// combination. Without it, importing the deepest link of a hierarchy first builds the very
		// two-level tree the previous check refuses, so the same file was accepted or rejected
		// depending on the order of its rows, and the accepted order could not be replayed.
		$nbchildasparent = $this->countCombinations((int) $combination->fk_product_child);
		if ($nbchildasparent < 0) {
			return -1;
		}
		if ($nbchildasparent > 0) {
			$this->errors[] = $langs->trans('ErrorVariantChildIsAParent', $child->ref);
			dol_syslog(get_class($this)."::reconcileCombination ".$child->ref." is already the parent of ".$nbchildasparent." combination(s)", LOG_ERR);
			return -1;
		}

		$nbsamechild = $this->countCombinations((int) $combination->fk_product_parent, (int) $combination->fk_product_child);
		if ($nbsamechild < 0) {
			return -1;
		}
		if ($nbsamechild > 1) {
			$this->errors[] = $langs->trans('ErrorVariantChildAlreadyCombined', $child->ref, $parent->ref);
			dol_syslog(get_class($this)."::reconcileCombination ".$nbsamechild." combinations of ".$parent->ref." point to ".$child->ref, LOG_ERR);
			return -1;
		}

		dol_syslog(get_class($this)."::reconcileCombination attach product ".$child->ref." as a variant of ".$parent->ref, LOG_NOTICE);

		// update() propagates weight, label and prices of every level to the child product,
		// through updateProperties(). Do not recompute anything here: a second pass would
		// duplicate the rows written into llx_product_price.
		if ($combination->update($user) < 0) {
			if (!empty($combination->error)) {
				$this->errors[] = $combination->error;
			}
			$this->errors = array_merge($this->errors, (array) $combination->errors);
			return -1;
		}

		return 1;
	}

	/**
	 * Reconcile a variant from one of its attribute/value links.
	 *
	 * The value set is only complete once the last line of the variant has been read, so this
	 * runs once per line and converges. Every pass is idempotent.
	 *
	 * @param	int			$rowid	Row id of the attribute/value link
	 * @param	User		$user	Object user
	 * @param	Translate	$langs	Object langs
	 * @return	int					Return integer <0 if KO, >0 if OK
	 */
	private function reconcileValuePair($rowid, User $user, Translate $langs)
	{
		require_once DOL_DOCUMENT_ROOT.'/variants/class/ProductCombination.class.php';
		require_once DOL_DOCUMENT_ROOT.'/variants/class/ProductCombination2ValuePair.class.php';
		require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

		$sql = "SELECT fk_prod_combination FROM ".MAIN_DB_PREFIX."product_attribute_combination2val";
		$sql .= " WHERE rowid = ".((int) $rowid);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->errors[] = $this->db->lasterror();
			dol_syslog(get_class($this)."::reconcileValuePair ".$this->db->lasterror(), LOG_ERR);
			return -1;
		}
		$obj = $this->db->fetch_object($resql);
		$this->db->free($resql);
		if (empty($obj)) {
			$this->errors[] = $langs->trans('ErrorRecordNotFound').' (combination2val '.$rowid.')';
			dol_syslog(get_class($this)."::reconcileValuePair combination2val ".$rowid." not found", LOG_ERR);
			return -1;
		}

		$combination = new ProductCombination($this->db);
		if ($combination->fetch($obj->fk_prod_combination) <= 0) {
			$this->errors[] = $langs->trans('ErrorRecordNotFound').' (combination '.$obj->fk_prod_combination.')';
			dol_syslog(get_class($this)."::reconcileValuePair combination ".$obj->fk_prod_combination." not found", LOG_ERR);
			return -1;
		}

		$parent = new Product($this->db);
		if ($parent->fetch($combination->fk_product_parent) <= 0) {
			$this->errors[] = $langs->trans('ErrorVariantParentNotFound');
			dol_syslog(get_class($this)."::reconcileValuePair parent product ".$combination->fk_product_parent." not found", LOG_ERR);
			return -1;
		}

		$child = new Product($this->db);
		if ($child->fetch($combination->fk_product_child) <= 0) {
			$this->errors[] = $langs->trans('ErrorVariantChildNotFound');
			dol_syslog(get_class($this)."::reconcileValuePair child product ".$combination->fk_product_child." not found", LOG_ERR);
			return -1;
		}

		// createProductCombination() resolves the child product by its ref, restricted to the
		// entities visible from the current one, while Product::fetch() by id crosses every
		// entity (product.class.php filters on entity only in the ref branch). When the two
		// disagree, the creation path of createProductCombination() would be taken and a clone
		// of the parent product would be created: the import must never create a product.
		$childbyref = new Product($this->db);
		if ($childbyref->fetch(0, $child->ref) <= 0 || $childbyref->id != $child->id) {
			$this->errors[] = $langs->trans('ErrorVariantChildNotFound');
			dol_syslog(get_class($this)."::".__FUNCTION__." child ".$child->id." (".$child->ref.") is not resolvable by ref in the current entity", LOG_ERR);
			return -1;
		}

		$features = array();
		$pair = new ProductCombination2ValuePair($this->db);
		$pairs = $pair->fetchByFkCombination($combination->id);
		if (!is_array($pairs)) {
			// The method returns -1 on a SQL error: iterating it would swallow the error and
			// report the line as imported.
			$this->errors[] = $pair->error;
			dol_syslog(get_class($this)."::reconcileValuePair ".$pair->error, LOG_ERR);
			return -1;
		}
		foreach ($pairs as $value) {
			$features[$value->fk_prod_attr] = $value->fk_prod_attr_val;
		}
		if (empty($features)) {
			return 0;
		}

		// Prices are passed as arrays so that createProductCombination() uses them as is instead
		// of summing the per value variations of the screen, which the file does not carry.
		$priceimpact = array();
		$priceimpactpercent = array();
		if (getDolGlobalString('PRODUIT_MULTIPRICES')) {
			$limit = getDolGlobalInt('PRODUIT_MULTIPRICES_LIMIT');
			for ($i = 1; $i <= $limit; $i++) {
				$level = isset($combination->combination_price_levels[$i]) ? $combination->combination_price_levels[$i] : null;
				$priceimpact[$i] = is_object($level) ? (float) $level->variation_price : (float) $combination->variation_price;
				$priceimpactpercent[$i] = is_object($level) ? (bool) $level->variation_price_percentage : (bool) $combination->variation_price_percentage;
			}
		} else {
			$priceimpact[1] = (float) $combination->variation_price;
			$priceimpactpercent[1] = (bool) $combination->variation_price_percentage;
		}

		// Categories are deliberately not cloned: the child product already exists, and
		// CommonObject::cloneCategories() does a blind INSERT ... SELECT that would violate the
		// primary key of llx_categorie_product and abort the whole import.
		$result = $combination->createProductCombination(
			$user,
			$parent,
			$features,
			array(),
			$priceimpactpercent,
			$priceimpact,
			(float) $combination->variation_weight,
			$child->ref,
			(string) $combination->variation_ref_ext,
			false,
			$combination
		);

		if ($result <= 0) {
			if (!empty($combination->error)) {
				$this->errors[] = $combination->error;
			}
			$this->errors = array_merge($this->errors, (array) $combination->errors);
			return -1;
		}

		return 1;
	}

	/**
	 * Propagate an imported price level to the child product.
	 *
	 * @param	int			$rowid	Row id of the price level
	 * @param	User		$user	Object user
	 * @param	Translate	$langs	Object langs
	 * @return	int					Return integer <0 if KO, >0 if OK
	 */
	private function reconcilePriceLevel($rowid, User $user, Translate $langs)
	{
		require_once DOL_DOCUMENT_ROOT.'/variants/class/ProductCombination.class.php';
		require_once DOL_DOCUMENT_ROOT.'/variants/class/ProductCombination2ValuePair.class.php';
		require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

		$sql = "SELECT fk_product_attribute_combination, fk_price_level FROM ".MAIN_DB_PREFIX."product_attribute_combination_price_level";
		$sql .= " WHERE rowid = ".((int) $rowid);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->errors[] = $this->db->lasterror();
			dol_syslog(get_class($this)."::reconcilePriceLevel ".$this->db->lasterror(), LOG_ERR);
			return -1;
		}
		$obj = $this->db->fetch_object($resql);
		$this->db->free($resql);
		if (empty($obj)) {
			$this->errors[] = $langs->trans('ErrorRecordNotFound').' (price level '.$rowid.')';
			dol_syslog(get_class($this)."::reconcilePriceLevel price level ".$rowid." not found", LOG_ERR);
			return -1;
		}

		// A level above the configured limit would be silently dropped later on by
		// ProductCombinationLevel::clean(). Refuse it now, with a message.
		$multipriceslimit = getDolGlobalInt('PRODUIT_MULTIPRICES_LIMIT');
		if ($multipriceslimit > 0 && (int) $obj->fk_price_level > $multipriceslimit) {
			$this->errors[] = $langs->trans('ErrorVariantPriceLevelOutOfRange', $obj->fk_price_level, $multipriceslimit);
			dol_syslog(get_class($this)."::reconcilePriceLevel price level ".$obj->fk_price_level." is above the limit of ".$multipriceslimit, LOG_ERR);
			return -1;
		}

		$combination = new ProductCombination($this->db);
		if ($combination->fetch($obj->fk_product_attribute_combination) <= 0) {
			$this->errors[] = $langs->trans('ErrorRecordNotFound').' (combination '.$obj->fk_product_attribute_combination.')';
			dol_syslog(get_class($this)."::reconcilePriceLevel combination ".$obj->fk_product_attribute_combination." not found", LOG_ERR);
			return -1;
		}

		$parent = new Product($this->db);
		if ($parent->fetch($combination->fk_product_parent) <= 0) {
			$this->errors[] = $langs->trans('ErrorVariantParentNotFound');
			dol_syslog(get_class($this)."::reconcilePriceLevel parent product ".$combination->fk_product_parent." not found", LOG_ERR);
			return -1;
		}

		// updateProperties() only, not update(): the price levels have just been written by the
		// engine and are the source of truth, they must not be saved again.
		if ($combination->updateProperties($parent, $user) < 0) {
			if (!empty($combination->error)) {
				$this->errors[] = $combination->error;
			}
			$this->errors = array_merge($this->errors, (array) $combination->errors);
			return -1;
		}

		return 1;
	}
}
