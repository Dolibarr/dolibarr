<?php
/* Copyright (C) 2015   Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2024	Jose MARTINEZ			<jose.martinez@pichinov.com>
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

use Luracast\Restler\RestException;

require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/client.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';

require_once DOL_DOCUMENT_ROOT.'/adherents/class/api_members.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/api_products.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/api_contacts.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/api_thirdparties.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/api_projects.class.php';

/**
 * API class for categories
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class Categories extends DolibarrApi
{
	/**
	 * @var array   $FIELDS     Mandatory fields, checked when create and update object
	 */
	public static $FIELDS = array(
		'label',
		'type'
	);

	public static $TYPES = array(
		0  => 'product',
		1  => 'supplier',
		2  => 'customer',
		3  => 'member',
		4  => 'contact',
		5  => 'account',
		6  => 'project',
		7  => 'user',
		8  => 'bank_line',
		9  => 'warehouse',
		10 => 'actioncomm',
		11 => 'website_page',
		12 => 'ticket',
		13 => 'knowledgemanagement',
		16 => 'order'
	);

	/**
	 * @var Categorie $category {@type Categorie}
	 */
	public $category;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		global $db;

		$this->db = $db;
		$this->category = new Categorie($this->db);
	}

	/**
	 * Get properties of a category object
	 *
	 * Return an array with category information
	 *
	 * @param	int		$id ID of category
	 * @param	bool	$include_childs Include child categories list (true or false)
	 * @return	array|mixed data without useless information
	 *
	 * @throws	RestException
	 */
	public function get($id, $include_childs = false)
	{
		if (!DolibarrApiAccess::$user->hasRight('categorie', 'lire')) {
			throw new RestException(403);
		}

		$result = $this->category->fetch($id);
		if (!$result) {
			throw new RestException(404, 'category not found');
		}

		if (!DolibarrApi::_checkAccessToResource('categorie', $this->category->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		if ($include_childs) {
			$cats = $this->category->get_filles();
			if (!is_array($cats)) {
				throw new RestException(500, 'Error when fetching child categories', array_merge(array($this->category->error), $this->category->errors));
			}
			$this->category->childs = array();
			foreach ($cats as $cat) {
				$this->category->childs[] = $this->_cleanObjectDatas($cat);
			}
		}

		return $this->_cleanObjectDatas($this->category);
	}

	/**
	 * List categories
	 *
	 * Get a list of categories
	 *
	 * @param string	$sortfield	Sort field
	 * @param string	$sortorder	Sort order
	 * @param int		$limit		Limit for list
	 * @param int		$page		Page number
	 * @param string	$type		Type of category ('member', 'customer', 'supplier', 'product', 'contact', 'actioncomm')
	 * @param string    $sqlfilters Other criteria to filter answers separated by a comma. Syntax example "(t.ref:like:'SO-%') and (t.date_creation:<:'20160101')"
	 * @param string    $properties	Restrict the data returned to these properties. Ignored if empty. Comma separated list of properties names
	 * @return array                Array of category objects
	 *
	 * @throws RestException
	 */
	public function index($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $type = '', $sqlfilters = '', $properties = '')
	{
		$obj_ret = array();

		if (!DolibarrApiAccess::$user->hasRight('categorie', 'lire')) {
			throw new RestException(403);
		}

		$sql = "SELECT t.rowid";
		$sql .= " FROM ".MAIN_DB_PREFIX."categorie AS t LEFT JOIN ".MAIN_DB_PREFIX."categories_extrafields AS ef ON (ef.fk_object = t.rowid)"; // Modification VMR Global Solutions to include extrafields as search parameters in the API GET call, so we will be able to filter on extrafields
		$sql .= ' WHERE t.entity IN ('.getEntity('category').')';
		if (!empty($type)) {
			$sql .= ' AND t.type='.array_search($type, Categories::$TYPES);
		}
		// Add sql filters
		if ($sqlfilters) {
			$errormessage = '';
			$sql .= forgeSQLFromUniversalSearchCriteria($sqlfilters, $errormessage);
			if ($errormessage) {
				throw new RestException(400, 'Error when validating parameter sqlfilters -> '.$errormessage);
			}
		}

		$sql .= $this->db->order($sortfield, $sortorder);
		if ($limit) {
			if ($page < 0) {
				$page = 0;
			}
			$offset = $limit * $page;

			$sql .= $this->db->plimit($limit + 1, $offset);
		}

		$result = $this->db->query($sql);
		if ($result) {
			$i = 0;
			$num = $this->db->num_rows($result);
			$min = min($num, ($limit <= 0 ? $num : $limit));
			while ($i < $min) {
				$obj = $this->db->fetch_object($result);
				$category_static = new Categorie($this->db);
				if ($category_static->fetch($obj->rowid)) {
					$obj_ret[] = $this->_filterObjectProperties($this->_cleanObjectDatas($category_static), $properties);
				}
				$i++;
			}
		} else {
			throw new RestException(503, 'Error when retrieve category list : '.$this->db->lasterror());
		}

		return $obj_ret;
	}

	/**
	 * Create category object
	 *
	 * @param array $request_data   Request data
	 * @return int  ID of category
	 */
	public function post($request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('categorie', 'creer')) {
			throw new RestException(403);
		}

		// Check mandatory fields (throw an exception if wrong)
		$this->_validate($request_data);

		foreach ($request_data as $field => $value) {
			if ($field === 'caller') {
				// Add a mention of caller so on trigger called after action, we can filter to avoid a loop if we try to sync back again with the caller
				$this->category->context['caller'] = sanitizeVal($request_data['caller'], 'aZ09');
				continue;
			}

			$this->category->$field = $this->_checkValForAPI($field, $value, $this->category);
		}
		if ($this->category->create(DolibarrApiAccess::$user) < 0) {
			throw new RestException(500, 'Error when creating category', array_merge(array($this->category->error), $this->category->errors));
		}
		return $this->category->id;
	}

	/**
	 * Update category
	 *
	 * @param 	int   		$id             Id of category to update
	 * @param 	array 		$request_data   Datas
	 * @return 	Object						Updated object
	 */
	public function put($id, $request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('categorie', 'creer')) {
			throw new RestException(403);
		}

		$result = $this->category->fetch($id);
		if (!$result) {
			throw new RestException(404, 'category not found');
		}

		if (!DolibarrApi::_checkAccessToResource('categorie', $this->category->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		foreach ($request_data as $field => $value) {
			if ($field == 'id') {
				continue;
			}
			if ($field === 'caller') {
				// Add a mention of caller so on trigger called after action, we can filter to avoid a loop if we try to sync back again with the caller
				$this->category->context['caller'] = sanitizeVal($request_data['caller'], 'aZ09');
				continue;
			}

			$this->category->$field = $this->_checkValForAPI($field, $value, $this->category);
		}

		if ($this->category->update(DolibarrApiAccess::$user) > 0) {
			return $this->get($id);
		} else {
			throw new RestException(500, $this->category->error);
		}
	}

	/**
	 * Delete category
	 *
	 * @param int $id   Category ID
	 * @return array
	 */
	public function delete($id)
	{
		if (!DolibarrApiAccess::$user->hasRight('categorie', 'supprimer')) {
			throw new RestException(403);
		}
		$result = $this->category->fetch($id);
		if (!$result) {
			throw new RestException(404, 'category not found');
		}

		if (!DolibarrApi::_checkAccessToResource('categorie', $this->category->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		if (!$this->category->delete(DolibarrApiAccess::$user)) {
			throw new RestException(500, 'error when delete category');
		}

		return array(
			'success' => array(
				'code' => 200,
				'message' => 'Category deleted'
			)
		);
	}

	/**
	 * List categories of an object
	 *
	 * Get the list of categories linked to an object
	 *
	 * @param int       $id         Object ID
	 * @param string	$type		Type of category ('member', 'customer', 'supplier', 'product', 'contact', 'project', 'actioncomm')
	 * @param string	$sortfield	Sort field
	 * @param string	$sortorder	Sort order
	 * @param int		$limit		Limit for list
	 * @param int		$page		Page number
	 * @return array                Array of category objects
	 *
	 * @throws RestException
	 *
	 * @url GET /object/{type}/{id}
	 */
	public function getListForObject($id, $type, $sortfield = "s.rowid", $sortorder = 'ASC', $limit = 0, $page = 0)
	{
		if (!in_array($type, [
			Categorie::TYPE_PRODUCT,
			Categorie::TYPE_CONTACT,
			Categorie::TYPE_CUSTOMER,
			Categorie::TYPE_SUPPLIER,
			Categorie::TYPE_MEMBER,
			Categorie::TYPE_PROJECT,
			Categorie::TYPE_KNOWLEDGEMANAGEMENT,
			Categorie::TYPE_ACTIONCOMM
		])) {
			throw new RestException(403);
		}

		if ($type == Categorie::TYPE_PRODUCT && !DolibarrApiAccess::$user->hasRight('produit', 'lire') && !DolibarrApiAccess::$user->hasRight('service', 'lire')) {
			throw new RestException(403);
		} elseif ($type == Categorie::TYPE_CONTACT && !DolibarrApiAccess::$user->hasRight('contact', 'lire')) {
			throw new RestException(403);
		} elseif ($type == Categorie::TYPE_CUSTOMER && !DolibarrApiAccess::$user->hasRight('societe', 'lire')) {
			throw new RestException(403);
		} elseif ($type == Categorie::TYPE_SUPPLIER && !DolibarrApiAccess::$user->hasRight('fournisseur', 'lire')) {
			throw new RestException(403);
		} elseif ($type == Categorie::TYPE_MEMBER && !DolibarrApiAccess::$user->hasRight('adherent', 'lire')) {
			throw new RestException(403);
		} elseif ($type == Categorie::TYPE_PROJECT && !DolibarrApiAccess::$user->hasRight('projet', 'lire')) {
			throw new RestException(403);
		} elseif ($type == Categorie::TYPE_KNOWLEDGEMANAGEMENT && !DolibarrApiAccess::$user->hasRight('knowledgemanagement', 'knowledgerecord', 'read')) {
			throw new RestException(403);
		} elseif ($type == Categorie::TYPE_ACTIONCOMM && !DolibarrApiAccess::$user->hasRight('agenda', 'allactions', 'read')) {
			throw new RestException(403);
		}

		$categories = $this->category->getListForItem($id, $type, $sortfield, $sortorder, $limit, $page);

		if (!is_array($categories)) {
			throw new RestException(600, 'Error when fetching object categories', array_merge(array($this->category->error), $this->category->errors));
		}
		return $categories;
	}

	/**
	 * Link an object to a category by id
	 *
	 * @param int $id  ID of category
	 * @param string   $type Type of category ('member', 'customer', 'supplier', 'product', 'contact', 'actioncomm')
	 * @param int      $object_id ID of object
	 *
	 * @return array
	 * @throws RestException
	 *
	 * @url POST {id}/objects/{type}/{object_id}
	 */
	public function linkObjectById($id, $type, $object_id)
	{
		if (empty($type) || empty($object_id)) {
			throw new RestException(403);
		}

		if (!DolibarrApiAccess::$user->hasRight('categorie', 'lire')) {
			throw new RestException(403);
		}

		$result = $this->category->fetch($id);
		if (!$result) {
			throw new RestException(404, 'category not found');
		}

		if ($type === Categorie::TYPE_PRODUCT) {
			if (!DolibarrApiAccess::$user->hasRight('produit', 'creer') && !DolibarrApiAccess::$user->hasRight('service', 'creer')) {
				throw new RestException(403);
			}
			$object = new Product($this->db);
		} elseif ($type === Categorie::TYPE_CUSTOMER) {
			if (!DolibarrApiAccess::$user->hasRight('societe', 'creer')) {
				throw new RestException(403);
			}
			$object = new Societe($this->db);
		} elseif ($type === Categorie::TYPE_SUPPLIER) {
			if (!DolibarrApiAccess::$user->hasRight('societe', 'creer')) {
				throw new RestException(403);
			}
			$object = new Societe($this->db);
		} elseif ($type === Categorie::TYPE_CONTACT) {
			if (!DolibarrApiAccess::$user->hasRight('societe', 'contact', 'creer')) {
				throw new RestException(403);
			}
			$object = new Contact($this->db);
		} elseif ($type === Categorie::TYPE_MEMBER) {
			if (!DolibarrApiAccess::$user->hasRight('adherent', 'creer')) {
				throw new RestException(403);
			}
			$object = new Adherent($this->db);
		} elseif ($type === Categorie::TYPE_ACTIONCOMM) {
			if (!DolibarrApiAccess::$user->hasRight('agenda', 'allactions', 'read')) {
				throw new RestException(403);
			}
			$object = new ActionComm($this->db);
		} else {
			throw new RestException(400, "this type is not recognized yet.");
		}

		$result = $object->fetch($object_id);
		if ($result > 0) {
			$result = $this->category->add_type($object, $type);
			if ($result < 0) {
				if ($this->category->error != 'DB_ERROR_RECORD_ALREADY_EXISTS') {
					throw new RestException(500, 'Error when linking object', array_merge(array($this->category->error), $this->category->errors));
				}
			}
		} else {
			throw new RestException(500, 'Error when fetching object', array_merge(array($object->error), $object->errors));
		}

		return array(
			'success' => array(
				'code' => 200,
				'message' => 'Objects successfully linked to the category'
			)
		);
	}

	/**
	 * Link an object to a category by ref
	 *
	 * @param int $id  ID of category
	 * @param string   $type Type of category ('member', 'customer', 'supplier', 'product', 'contact')
	 * @param string   $object_ref Reference of object
	 *
	 * @return array
	 * @throws RestException
	 *
	 * @url POST {id}/objects/{type}/ref/{object_ref}
	 */
	public function linkObjectByRef($id, $type, $object_ref)
	{
		if (empty($type) || empty($object_ref)) {
			throw new RestException(403);
		}

		if (!DolibarrApiAccess::$user->hasRight('categorie', 'lire')) {
			throw new RestException(403);
		}

		$result = $this->category->fetch($id);
		if (!$result) {
			throw new RestException(404, 'category not found');
		}

		if ($type === Categorie::TYPE_PRODUCT) {
			if (!DolibarrApiAccess::$user->hasRight('produit', 'creer') && !DolibarrApiAccess::$user->hasRight('service', 'creer')) {
				throw new RestException(403);
			}
			$object = new Product($this->db);
		} elseif ($type === Categorie::TYPE_CUSTOMER) {
			if (!DolibarrApiAccess::$user->hasRight('societe', 'creer')) {
				throw new RestException(403);
			}
			$object = new Societe($this->db);
		} elseif ($type === Categorie::TYPE_SUPPLIER) {
			if (!DolibarrApiAccess::$user->hasRight('societe', 'creer')) {
				throw new RestException(403);
			}
			$object = new Societe($this->db);
		} elseif ($type === Categorie::TYPE_CONTACT) {
			if (!DolibarrApiAccess::$user->hasRight('societe', 'contact', 'creer')) {
				throw new RestException(403);
			}
			$object = new Contact($this->db);
		} elseif ($type === Categorie::TYPE_MEMBER) {
			if (!DolibarrApiAccess::$user->hasRight('adherent', 'creer')) {
				throw new RestException(403);
			}
			$object = new Adherent($this->db);
		} elseif ($type === Categorie::TYPE_ACTIONCOMM) {
			if (!DolibarrApiAccess::$user->hasRight('agenda', 'allactions', 'read')) {
				throw new RestException(403);
			}
			$object = new ActionComm($this->db);
		} else {
			throw new RestException(400, "this type is not recognized yet.");
		}

		$result = $object->fetch(0, $object_ref);
		if ($result > 0) {
			$result = $this->category->add_type($object, $type);
			if ($result < 0) {
				if ($this->category->error != 'DB_ERROR_RECORD_ALREADY_EXISTS') {
					throw new RestException(500, 'Error when linking object', array_merge(array($this->category->error), $this->category->errors));
				}
			}
		} else {
			throw new RestException(500, 'Error when fetching object', array_merge(array($object->error), $object->errors));
		}

		return array(
			'success' => array(
				'code' => 200,
				'message' => 'Objects successfully linked to the category'
			)
		);
	}

	/**
	 * Unlink an object from a category by id
	 *
	 * @param int      $id        ID of category
	 * @param string   $type      Type of category ('member', 'customer', 'supplier', 'product', 'contact', 'actioncomm')
	 * @param int      $object_id ID of the object
	 *
	 * @return array
	 * @throws RestException
	 *
	 * @url DELETE {id}/objects/{type}/{object_id}
	 */
	public function unlinkObjectById($id, $type, $object_id)
	{
		if (empty($type) || empty($object_id)) {
			throw new RestException(403);
		}

		if (!DolibarrApiAccess::$user->hasRight('categorie', 'lire')) {
			throw new RestException(403);
		}

		$result = $this->category->fetch($id);
		if (!$result) {
			throw new RestException(404, 'category not found');
		}

		if ($type === Categorie::TYPE_PRODUCT) {
			if (!DolibarrApiAccess::$user->hasRight('produit', 'creer') && !DolibarrApiAccess::$user->hasRight('service', 'creer')) {
				throw new RestException(403);
			}
			$object = new Product($this->db);
		} elseif ($type === Categorie::TYPE_CUSTOMER) {
			if (!DolibarrApiAccess::$user->hasRight('societe', 'creer')) {
				throw new RestException(403);
			}
			$object = new Societe($this->db);
		} elseif ($type === Categorie::TYPE_SUPPLIER) {
			if (!DolibarrApiAccess::$user->hasRight('societe', 'creer')) {
				throw new RestException(403);
			}
			$object = new Societe($this->db);
		} elseif ($type === Categorie::TYPE_CONTACT) {
			if (!DolibarrApiAccess::$user->hasRight('societe', 'contact', 'creer')) {
				throw new RestException(403);
			}
			$object = new Contact($this->db);
		} elseif ($type === Categorie::TYPE_MEMBER) {
			if (!DolibarrApiAccess::$user->hasRight('adherent', 'creer')) {
				throw new RestException(403);
			}
			$object = new Adherent($this->db);
		} elseif ($type === Categorie::TYPE_ACTIONCOMM) {
			if (!DolibarrApiAccess::$user->hasRight('agenda', 'allactions', 'read')) {
				throw new RestException(403);
			}
			$object = new ActionComm($this->db);
		} else {
			throw new RestException(400, "this type is not recognized yet.");
		}

		$result = $object->fetch((int) $object_id);
		if ($result > 0) {
			$result = $this->category->del_type($object, $type);
			if ($result < 0) {
				throw new RestException(500, 'Error when unlinking object', array_merge(array($this->category->error), $this->category->errors));
			}
		} else {
			throw new RestException(500, 'Error when fetching object', array_merge(array($object->error), $object->errors));
		}

		return array(
			'success' => array(
				'code' => 200,
				'message' => 'Objects successfully unlinked from the category'
			)
		);
	}

	/**
	 * Unlink an object from a category by ref
	 *
	 * @param int      $id         ID of category
	 * @param string   $type Type  of category ('member', 'customer', 'supplier', 'product', 'contact', 'actioncomm')
	 * @param string   $object_ref Reference of the object
	 *
	 * @return array
	 * @throws RestException
	 *
	 * @url DELETE {id}/objects/{type}/ref/{object_ref}
	 */
	public function unlinkObjectByRef($id, $type, $object_ref)
	{
		if (empty($type) || empty($object_ref)) {
			throw new RestException(403);
		}

		if (!DolibarrApiAccess::$user->hasRight('categorie', 'lire')) {
			throw new RestException(403);
		}

		$result = $this->category->fetch($id);
		if (!$result) {
			throw new RestException(404, 'category not found');
		}

		if ($type === Categorie::TYPE_PRODUCT) {
			if (!DolibarrApiAccess::$user->hasRight('produit', 'creer') && !DolibarrApiAccess::$user->hasRight('service', 'creer')) {
				throw new RestException(403);
			}
			$object = new Product($this->db);
		} elseif ($type === Categorie::TYPE_CUSTOMER) {
			if (!DolibarrApiAccess::$user->hasRight('societe', 'creer')) {
				throw new RestException(403);
			}
			$object = new Societe($this->db);
		} elseif ($type === Categorie::TYPE_SUPPLIER) {
			if (!DolibarrApiAccess::$user->hasRight('societe', 'creer')) {
				throw new RestException(403);
			}
			$object = new Societe($this->db);
		} elseif ($type === Categorie::TYPE_CONTACT) {
			if (!DolibarrApiAccess::$user->hasRight('societe', 'contact', 'creer')) {
				throw new RestException(403);
			}
			$object = new Contact($this->db);
		} elseif ($type === Categorie::TYPE_MEMBER) {
			if (!DolibarrApiAccess::$user->hasRight('adherent', 'creer')) {
				throw new RestException(403);
			}
			$object = new Adherent($this->db);
		} elseif ($type === Categorie::TYPE_ACTIONCOMM) {
			if (!DolibarrApiAccess::$user->hasRight('agenda', 'allactions', 'read')) {
				throw new RestException(403);
			}
			$object = new ActionComm($this->db);
		} else {
			throw new RestException(400, "this type is not recognized yet.");
		}

		$result = $object->fetch(0, (string) $object_ref);
		if ($result > 0) {
			$result = $this->category->del_type($object, $type);
			if ($result < 0) {
				throw new RestException(500, 'Error when unlinking object', array_merge(array($this->category->error), $this->category->errors));
			}
		} else {
			throw new RestException(500, 'Error when fetching object', array_merge(array($object->error), $object->errors));
		}

		return array(
			'success' => array(
				'code' => 200,
				'message' => 'Objects successfully unlinked from the category'
			)
		);
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 * Clean sensible object datas
	 *
	 * @param   Categorie  $object  Object to clean
	 * @return  Object     			Object with cleaned properties
	 */
	protected function _cleanObjectDatas($object)
	{
		// phpcs:enable
		$object = parent::_cleanObjectDatas($object);

		// Remove fields not relevant to categories
		unset($object->MAP_CAT_FK);
		unset($object->MAP_CAT_TABLE);
		unset($object->MAP_OBJ_CLASS);
		unset($object->MAP_OBJ_TABLE);
		unset($object->country);
		unset($object->country_id);
		unset($object->country_code);
		unset($object->total_ht);
		unset($object->total_ht);
		unset($object->total_localtax1);
		unset($object->total_localtax2);
		unset($object->total_ttc);
		unset($object->total_tva);
		unset($object->lines);
		unset($object->civility_id);
		unset($object->name);
		unset($object->lastname);
		unset($object->firstname);
		unset($object->shipping_method_id);
		unset($object->fk_delivery_address);
		unset($object->cond_reglement);
		unset($object->cond_reglement_id);
		unset($object->mode_reglement_id);
		unset($object->barcode_type_coder);
		unset($object->barcode_type_label);
		unset($object->barcode_type_code);
		unset($object->barcode_type);
		unset($object->canvas);
		unset($object->cats);
		unset($object->motherof);
		unset($object->context);
		unset($object->socid);
		unset($object->thirdparty);
		unset($object->contact);
		unset($object->contact_id);
		unset($object->user);
		unset($object->fk_account);
		unset($object->fk_project);
		unset($object->note);
		unset($object->statut);

		return $object;
	}

	/**
	 * Validate fields before create or update object
	 *
	 * @param array|null    $data   Data to validate
	 * @return array				Return array with validated mandatory fields and their value
	 *
	 * @throws RestException
	 */
	private function _validate($data)
	{
		$category = array();
		foreach (Categories::$FIELDS as $field) {
			if (!isset($data[$field])) {
				throw new RestException(400, "$field field missing");
			}
			$category[$field] = $data[$field];
		}
		return $category;
	}

	/**
	 * Get the list of objects in a category.
	 *
	 * @param int        $id         ID of category
	 * @param string     $type       Type of category ('member', 'customer', 'supplier', 'product', 'contact', 'project')
	 * @param int        $onlyids    Return only ids of objects (consume less memory)
	 *
	 * @return mixed
	 *
	 * @url GET {id}/objects
	 */
	public function getObjects($id, $type, $onlyids = 0)
	{
		dol_syslog("getObjects($id, $type, $onlyids)", LOG_DEBUG);

		if (!DolibarrApiAccess::$user->hasRight('categorie', 'lire')) {
			throw new RestException(403);
		}

		if (empty($type)) {
			throw new RestException(500, 'The "type" parameter is required.');
		}

		$result = $this->category->fetch($id);
		if (!$result) {
			throw new RestException(404, 'category not found');
		}

		if (!DolibarrApi::_checkAccessToResource('categorie', $this->category->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$result = $this->category->getObjectsInCateg($type, $onlyids);

		if ($result < 0) {
			throw new RestException(503, 'Error when retrieving objects list : '.$this->category->error);
		}

		$objects = $result;
		$cleaned_objects = array();
		$objects_api = null;
		if ($type == 'member') {
			$objects_api = new Members();
		} elseif ($type == 'customer' || $type == 'supplier') {
			$objects_api = new Thirdparties();
		} elseif ($type == 'product') {
			$objects_api = new Products();
		} elseif ($type == 'contact') {
			$objects_api = new Contacts();
		} elseif ($type == 'project') {
			$objects_api = new Projects();
		}

		if (is_object($objects_api)) {
			foreach ($objects as $obj) {
				$cleaned_objects[] = $objects_api->_cleanObjectDatas($obj);
			}
		}

		return $cleaned_objects;
	}
}
