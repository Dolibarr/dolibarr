<?php
/* Copyright (C) 2015   	Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2018   	Pierre Chéné            <pierre.chene44@gmail.com>
 * Copyright (C) 2019   	Cedric Ancelin          <icedo.anc@gmail.com>
 * Copyright (C) 2020-2024  Frédéric France     	<frederic.france@free.fr>
 * Copyright (C) 2023       Alexandre Janniaux  	<alexandre.janniaux@gmail.com>
 * Copyright (C) 2024		MDW						<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024      Jon Bendtsen             <jon.bendtsen.github@jonb.dk>
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

/**
 * API class for thirdparties
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 *
 */
class Thirdparties extends DolibarrApi
{
	/**
	 *
	 * @var array   $FIELDS     Mandatory fields, checked when we create and update the object
	 */
	public static $FIELDS = array(
		'name'
	);

	/**
	 * @var Societe $company {@type Societe}
	 */
	public $company;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		global $db;
		$this->db = $db;

		require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
		require_once DOL_DOCUMENT_ROOT.'/societe/class/societeaccount.class.php';
		require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
		require_once DOL_DOCUMENT_ROOT.'/societe/class/companybankaccount.class.php';
		require_once DOL_DOCUMENT_ROOT.'/core/class/notify.class.php';

		$this->company = new Societe($this->db);

		if (getDolGlobalString('SOCIETE_EMAIL_MANDATORY')) {
			static::$FIELDS[] = 'email';
		}
	}

	/**
	 * Get properties of a thirdparty object
	 *
	 * Return an array with thirdparty information
	 *
	 * @param	int		$id				Id of third party to load
	 * @return  Object					Object with cleaned properties
	 *
	 * @throws	RestException
	 */
	public function get($id)
	{
		return $this->_fetch($id);
	}

	/**
	 * Get properties of a thirdparty object by email.
	 *
	 * Return an array with thirdparty information
	 *
	 * @param string    $email  Email of third party to load
	 * @return array|mixed Cleaned Societe object
	 *
	 * @url     GET email/{email}
	 *
	 * @throws RestException
	 */
	public function getByEmail($email)
	{
		return $this->_fetch('', '', '', '', '', '', '', '', '', '', $email);
	}

	/**
	 * Get properties of a thirdparty object by barcode.
	 *
	 * Return an array with thirdparty information
	 *
	 * @param string    $barcode  Barcode of third party to load
	 * @return array|mixed Cleaned Societe object
	 *
	 * @url     GET barcode/{barcode}
	 *
	 * @throws RestException
	 */
	public function getByBarcode($barcode)
	{
		return $this->_fetch('', '', '', $barcode);
	}

	/**
	 * List thirdparties
	 *
	 * Get a list of thirdparties
	 *
	 * @param   string  $sortfield  Sort field
	 * @param   string  $sortorder  Sort order
	 * @param   int     $limit      Limit for list
	 * @param   int     $page       Page number
	 * @param   int     $mode       Set to 1 to show only customers
	 *                              Set to 2 to show only prospects
	 *                              Set to 3 to show only those are not customer neither prospect
	 *								Set to 4 to show only suppliers
	 * @param	int		$category   Use this param to filter list by category
	 * @param   string  $sqlfilters Other criteria to filter answers separated by a comma. Syntax example "((t.nom:like:'TheCompany%') or (t.name_alias:like:'TheCompany%')) and (t.datec:<:'20160101')"
	 * @param   string  $properties	Restrict the data returned to these properties. Ignored if empty. Comma separated list of properties names
	 * @param bool $pagination_data If this parameter is set to true the response will include pagination data. Default value is false. Page starts from 0*
	 * @return  array               Array of thirdparty objects
	 */
	public function index($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $mode = 0, $category = 0, $sqlfilters = '', $properties = '', $pagination_data = false)
	{
		$obj_ret = array();

		if (!DolibarrApiAccess::$user->hasRight('societe', 'lire')) {
			throw new RestException(403);
		}

		// case of external user, we force socids
		$socids = DolibarrApiAccess::$user->socid ? (string) DolibarrApiAccess::$user->socid : '';

		// If the internal user must only see his customers, force searching by him
		$search_sale = 0;
		if (!DolibarrApiAccess::$user->hasRight('societe', 'client', 'voir') && !$socids) {
			$search_sale = DolibarrApiAccess::$user->id;
		}

		$sql = "SELECT t.rowid";
		$sql .= " FROM ".MAIN_DB_PREFIX."societe as t";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe_extrafields AS ef ON ef.fk_object = t.rowid";	// So we will be able to filter on extrafields
		if ($category > 0) {
			if ($mode != 4) {
				$sql .= ", ".MAIN_DB_PREFIX."categorie_societe as c";
			}
			if (!in_array($mode, array(1, 2, 3))) {
				$sql .= ", ".MAIN_DB_PREFIX."categorie_fournisseur as cc";
			}
		}
		$sql .= ", ".MAIN_DB_PREFIX."c_stcomm as st";
		$sql .= " WHERE t.entity IN (".getEntity('societe').")";
		$sql .= " AND t.fk_stcomm = st.id";
		if ($mode == 1) {
			$sql .= " AND t.client IN (1, 3)";
		} elseif ($mode == 2) {
			$sql .= " AND t.client IN (2, 3)";
		} elseif ($mode == 3) {
			$sql .= " AND t.client IN (0)";
		} elseif ($mode == 4) {
			$sql .= " AND t.fournisseur IN (1)";
		}
		// Select thirdparties of given category
		if ($category > 0) {
			if (!empty($mode) && $mode != 4) {
				$sql .= " AND c.fk_categorie = ".((int) $category)." AND c.fk_soc = t.rowid";
			} elseif (!empty($mode) && $mode == 4) {
				$sql .= " AND cc.fk_categorie = ".((int) $category)." AND cc.fk_soc = t.rowid";
			} else {
				$sql .= " AND ((c.fk_categorie = ".((int) $category)." AND c.fk_soc = t.rowid) OR (cc.fk_categorie = ".((int) $category)." AND cc.fk_soc = t.rowid))";
			}
		}
		if ($socids) {
			$sql .= " AND t.rowid IN (".$this->db->sanitize($socids).")";
		}
		// Search on sale representative
		if ($search_sale && $search_sale != '-1') {
			if ($search_sale == -2) {
				$sql .= " AND NOT EXISTS (SELECT sc.fk_soc FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc WHERE sc.fk_soc = t.rowid)";
			} elseif ($search_sale > 0) {
				$sql .= " AND EXISTS (SELECT sc.fk_soc FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc WHERE sc.fk_soc = t.rowid AND sc.fk_user = ".((int) $search_sale).")";
			}
		}
		// Add sql filters
		if ($sqlfilters) {
			$errormessage = '';
			$sql .= forgeSQLFromUniversalSearchCriteria($sqlfilters, $errormessage);
			if ($errormessage) {
				throw new RestException(400, 'Error when validating parameter sqlfilters -> '.$errormessage);
			}
		}

		//this query will return total thirdparties with the filters given
		$sqlTotals = str_replace('SELECT t.rowid', 'SELECT count(t.rowid) as total', $sql);

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
			$num = $this->db->num_rows($result);
			$min = min($num, ($limit <= 0 ? $num : $limit));
			$i = 0;
			while ($i < $min) {
				$obj = $this->db->fetch_object($result);
				$soc_static = new Societe($this->db);
				if ($soc_static->fetch($obj->rowid)) {
					if (isModEnabled('mailing')) {
						$soc_static->getNoEmail();
					}
					$obj_ret[] = $this->_filterObjectProperties($this->_cleanObjectDatas($soc_static), $properties);
				}
				$i++;
			}
		} else {
			throw new RestException(503, 'Error when retrieve thirdparties : '.$this->db->lasterror());
		}
		if (!count($obj_ret)) {
			throw new RestException(404, 'Thirdparties not found');
		}

		//if $pagination_data is true the response will contain element data with all values and element pagination with pagination data(total,page,limit)
		if ($pagination_data) {
			$totalsResult = $this->db->query($sqlTotals);
			$total = $this->db->fetch_object($totalsResult)->total;

			$tmp = $obj_ret;
			$obj_ret = [];

			$obj_ret['data'] = $tmp;
			$obj_ret['pagination'] = [
				'total' => (int) $total,
				'page' => $page, //count starts from 0
				'page_count' => ceil((int) $total / $limit),
				'limit' => $limit
			];
		}

		return $obj_ret;
	}

	/**
	 * Create thirdparty object
	 *
	 * @param array $request_data   Request datas
	 * @return int  ID of thirdparty
	 */
	public function post($request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('societe', 'creer')) {
			throw new RestException(403);
		}
		// Check mandatory fields
		$result = $this->_validate($request_data);

		foreach ($request_data as $field => $value) {
			if ($field === 'caller') {
				// Add a mention of caller so on trigger called after action, we can filter to avoid a loop if we try to sync back again with the caller
				$this->company->context['caller'] = sanitizeVal($request_data['caller'], 'aZ09');
				continue;
			}

			$this->company->$field = $this->_checkValForAPI($field, $value, $this->company);
		}

		if ($this->company->create(DolibarrApiAccess::$user) < 0) {
			throw new RestException(500, 'Error creating thirdparty', array_merge(array($this->company->error), $this->company->errors));
		}
		if (isModEnabled('mailing') && !empty($this->company->email) && isset($this->company->no_email)) {
			$this->company->setNoEmail($this->company->no_email);
		}

		return $this->company->id;
	}

	/**
	 * Update thirdparty
	 *
	 * @param 	int   			$id             Id of thirdparty to update
	 * @param 	array 			$request_data   Datas
	 * @return 	Object|false					Updated object
	 *
	 * @throws RestException 401
	 * @throws RestException 404
	 * @throws RestException 500
	 */
	public function put($id, $request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('societe', 'creer')) {
			throw new RestException(403);
		}

		$result = $this->company->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Thirdparty not found');
		}

		if (!DolibarrApi::_checkAccessToResource('societe', $this->company->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		foreach ($request_data as $field => $value) {
			if ($field == 'id') {
				continue;
			}
			if ($field === 'caller') {
				// Add a mention of caller so on trigger called after action, we can filter to avoid a loop if we try to sync back again with the caller
				$this->company->context['caller'] = sanitizeVal($request_data['caller'], 'aZ09');
				continue;
			}
			if ($field == 'array_options' && is_array($value)) {
				foreach ($value as $index => $val) {
					$this->company->array_options[$index] = $val;
				}
				continue;
			}
			$this->company->$field = $this->_checkValForAPI($field, $value, $this->company);
		}

		if (isModEnabled('mailing') && !empty($this->company->email) && isset($this->company->no_email)) {
			$this->company->setNoEmail($this->company->no_email);
		}

		if ($this->company->update($id, DolibarrApiAccess::$user, 1, '', '', 'update', 1) > 0) {
			return $this->get($id);
		} else {
			throw new RestException(500, $this->company->error);
		}
	}

	/**
	 * Merge a third party into another one.
	 *
	 * Merge content (properties, notes) and objects (like invoices, events, orders, proposals, ...) of a thirdparty into a target third party,
	 * then delete the merged third party.
	 * If a property has a defined value both in third party to delete and third party to keep, the value into the third party to
	 * delete will be ignored, the value of target thirdparty will remain, except for notes (content is concatenated).
	 *
	 * @param int   $id             ID of thirdparty to keep (the target third party)
	 * @param int   $idtodelete     ID of thirdparty to remove (the thirdparty to delete), once data has been merged into the target third party.
	 * @return Object				Return the resulted third party.
	 *
	 * @url PUT {id}/merge/{idtodelete}
	 */
	public function merge($id, $idtodelete)
	{
		if ($id == $idtodelete) {
			throw new RestException(400, 'Try to merge a thirdparty into itself');
		}

		if (!DolibarrApiAccess::$user->hasRight('societe', 'creer')) {
			throw new RestException(403);
		}

		$result = $this->company->fetch($id); // include the fetch of extra fields
		if (!$result) {
			throw new RestException(404, 'Thirdparty not found');
		}

		if (!DolibarrApi::_checkAccessToResource('societe', $this->company->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$companytoremove = new Societe($this->db);
		$result = $companytoremove->fetch($idtodelete); // include the fetch of extra fields
		if (!$result) {
			throw new RestException(404, 'Thirdparty not found');
		}

		if (!DolibarrApi::_checkAccessToResource('societe', $companytoremove->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$user = DolibarrApiAccess::$user;
		$result = $this->company->mergeCompany($companytoremove->id);
		if ($result < 0) {
			throw new RestException(500, 'Error failed to merged thirdparty '.$companytoremove->id.' into '.$id.'. Enable and read log file for more information.');
		}

		return $this->get($id);
	}

	/**
	 * Delete thirdparty
	 *
	 * @param int $id   Thirdparty ID
	 * @return array
	 */
	public function delete($id)
	{
		if (!DolibarrApiAccess::$user->hasRight('societe', 'supprimer')) {
			throw new RestException(403);
		}
		$result = $this->company->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Thirdparty not found');
		}
		if (!DolibarrApi::_checkAccessToResource('societe', $this->company->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}
		$this->company->oldcopy = clone $this->company;

		$res = $this->company->delete($id);
		if ($res < 0) {
			throw new RestException(500, "Can't delete, error occurs");
		} elseif ($res == 0) {
			throw new RestException(409, "Can't delete, that product is probably used");
		}

		return array(
			'success' => array(
				'code' => 200,
				'message' => 'Object deleted'
			)
		);
	}

	/**
	 * Set new price level for the given thirdparty
	 *
	 * @param	int		$id				ID of thirdparty
	 * @param	int		$priceLevel		Price level to apply to thirdparty
	 * @return	Object					Thirdparty data without useless information
	 *
	 * @url PUT {id}/setpricelevel/{priceLevel}
	 *
	 * @throws RestException 400 Price level out of bounds
	 * @throws RestException 401 Access not allowed for your login
	 * @throws RestException 404 Thirdparty not found
	 * @throws RestException 500 Error fetching/setting price level
	 * @throws RestException 501 Request needs modules "Thirdparties" and "Products" and setting Multiprices activated
	 */
	public function setThirdpartyPriceLevel($id, $priceLevel)
	{
		global $conf;

		if (!isModEnabled('societe')) {
			throw new RestException(501, 'Module "Thirdparties" needed for this request');
		}

		if (!isModEnabled("product")) {
			throw new RestException(501, 'Module "Products" needed for this request');
		}

		if (!getDolGlobalString('PRODUIT_MULTIPRICES')) {
			throw new RestException(501, 'Multiprices features activation needed for this request');
		}

		if ($priceLevel < 1 || $priceLevel > getDolGlobalString('PRODUIT_MULTIPRICES_LIMIT')) {
			throw new RestException(400, 'Price level must be between 1 and ' . getDolGlobalString('PRODUIT_MULTIPRICES_LIMIT'));
		}

		if (!DolibarrApiAccess::$user->hasRight('societe', 'creer')) {
			throw new RestException(403, 'Access to thirdparty '.$id.' not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$result = $this->company->fetch($id);
		if ($result < 0) {
			throw new RestException(404, 'Thirdparty '.$id.' not found');
		}

		if (empty($result)) {
			throw new RestException(500, 'Error fetching thirdparty '.$id, array_merge(array($this->company->error), $this->company->errors));
		}

		if (empty(DolibarrApi::_checkAccessToResource('societe', $this->company->id))) {
			throw new RestException(403, 'Access to thirdparty '.$id.' not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$result = $this->company->setPriceLevel($priceLevel, DolibarrApiAccess::$user);
		if ($result <= 0) {
			throw new RestException(500, 'Error setting new price level for thirdparty '.$id, array($this->company->db->lasterror()));
		}

		return $this->_cleanObjectDatas($this->company);
	}

	/**
	 * Add a customer representative to a thirdparty
	 *
	 * @param int		$id					Id of thirdparty
	 * @param int       $representative_id	Id of representative
	 * @return int							Return integer <=0 if KO, >0 if OK
	 *
	 * @url POST {id}/representative/{representative_id}
	 *
	 * @throws RestException 401 Access not allowed for your login
	 * @throws RestException 404 User or Thirdparty not found
	 */
	public function addRepresentative($id, $representative_id)
	{
		if (!DolibarrApiAccess::$user->hasRight('societe', 'creer')) {
			throw new RestException(403);
		}
		$result = $this->company->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Thirdparty not found');
		}
		$usertmp = new User($this->db);
		$result = $usertmp->fetch($representative_id);
		if (!$result) {
			throw new RestException(404, 'User not found');
		}
		if (!DolibarrApi::_checkAccessToResource('societe', $this->company->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}
		$result = $this->company->add_commercial(DolibarrApiAccess::$user, $representative_id);

		return $result;
	}

	/**
	 * Delete a customer representative to a thirdparty
	 *
	 * @param int		$id					Id of thirdparty
	 * @param int       $representative_id	Id of representative
	 * @return int							Return integer <=0 if KO, >0 if OK
	 *
	 * @url DELETE {id}/representative/{representative_id}
	 *
	 * @throws RestException 401 Access not allowed for your login
	 * @throws RestException 404 User or Thirdparty not found
	 */
	public function deleteRepresentative($id, $representative_id)
	{
		if (!DolibarrApiAccess::$user->hasRight('societe', 'supprimer')) {
			throw new RestException(403);
		}
		$result = $this->company->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Thirdparty not found');
		}
		$usertmp = new User($this->db);
		$result = $usertmp->fetch($representative_id);
		if (!$result) {
			throw new RestException(404, 'User not found');
		}
		if (!DolibarrApi::_checkAccessToResource('societe', $this->company->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}
		$result = $this->company->del_commercial(DolibarrApiAccess::$user, $representative_id);

		return $result;
	}

	/**
	 * Get customer categories for a thirdparty
	 *
	 * @param int		$id         ID of thirdparty
	 * @param string	$sortfield	Sort field
	 * @param string	$sortorder	Sort order
	 * @param int		$limit		Limit for list
	 * @param int		$page		Page number
	 * @return array|void
	 *
	 * @url GET {id}/categories
	 */
	public function getCategories($id, $sortfield = "s.rowid", $sortorder = 'ASC', $limit = 0, $page = 0)
	{
		if (!DolibarrApiAccess::$user->hasRight('categorie', 'lire')) {
			throw new RestException(403);
		}

		$result = $this->company->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Thirdparty not found');
		}

		$categories = new Categorie($this->db);

		$arrayofcateg = $categories->getListForItem($id, 'customer', $sortfield, $sortorder, $limit, $page);

		if (is_numeric($arrayofcateg) && $arrayofcateg < 0) {
			throw new RestException(503, 'Error when retrieve category list : '.$categories->error);
		}

		if (is_numeric($arrayofcateg) && $arrayofcateg >= 0) {	// To fix a return of 0 instead of empty array of method getListForItem
			return array();
		}

		return $arrayofcateg;
	}

	/**
	 * Add a customer category to a thirdparty
	 *
	 * @param int		$id				Id of thirdparty
	 * @param int       $category_id	Id of category
	 * @return Object|void
	 *
	 * @url PUT {id}/categories/{category_id}
	 */
	public function addCategory($id, $category_id)
	{
		if (!DolibarrApiAccess::$user->hasRight('societe', 'creer')) {
			throw new RestException(403);
		}

		$result = $this->company->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Thirdparty not found');
		}
		$category = new Categorie($this->db);
		$result = $category->fetch($category_id);
		if (!$result) {
			throw new RestException(404, 'category not found');
		}

		if (!DolibarrApi::_checkAccessToResource('societe', $this->company->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}
		if (!DolibarrApi::_checkAccessToResource('category', $category->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$category->add_type($this->company, 'customer');

		return $this->_cleanObjectDatas($this->company);
	}

	/**
	 * Remove the link between a customer category and the thirdparty
	 *
	 * @param int		$id				Id of thirdparty
	 * @param int		$category_id	Id of category
	 *
	 * @return Object|void
	 *
	 * @url DELETE {id}/categories/{category_id}
	 */
	public function deleteCategory($id, $category_id)
	{
		if (!DolibarrApiAccess::$user->hasRight('societe', 'creer')) {
			throw new RestException(403);
		}

		$result = $this->company->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Thirdparty not found');
		}
		$category = new Categorie($this->db);
		$result = $category->fetch($category_id);
		if (!$result) {
			throw new RestException(404, 'category not found');
		}

		if (!DolibarrApi::_checkAccessToResource('societe', $this->company->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}
		if (!DolibarrApi::_checkAccessToResource('category', $category->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$category->del_type($this->company, 'customer');

		return $this->_cleanObjectDatas($this->company);
	}

	/**
	 * Get supplier categories for a thirdparty
	 *
	 * @param int		$id         ID of thirdparty
	 * @param string	$sortfield	Sort field
	 * @param string	$sortorder	Sort order
	 * @param int		$limit		Limit for list
	 * @param int		$page		Page number
	 *
	 * @return mixed
	 *
	 * @url GET {id}/supplier_categories
	 */
	public function getSupplierCategories($id, $sortfield = "s.rowid", $sortorder = 'ASC', $limit = 0, $page = 0)
	{
		if (!DolibarrApiAccess::$user->hasRight('categorie', 'lire')) {
			throw new RestException(403);
		}

		$result = $this->company->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Thirdparty not found');
		}

		$categories = new Categorie($this->db);

		$result = $categories->getListForItem($id, 'supplier', $sortfield, $sortorder, $limit, $page);

		if (is_numeric($result) && $result < 0) {
			throw new RestException(503, 'Error when retrieve category list : '.$categories->error);
		}

		if (is_numeric($result) && $result == 0) {	// To fix a return of 0 instead of empty array of method getListForItem
			return array();
		}

		return $result;
	}

	/**
	 * Add a supplier category to a thirdparty
	 *
	 * @param int		$id				Id of thirdparty
	 * @param int       $category_id	Id of category
	 *
	 * @return mixed
	 *
	 * @url PUT {id}/supplier_categories/{category_id}
	 */
	public function addSupplierCategory($id, $category_id)
	{
		if (!DolibarrApiAccess::$user->hasRight('societe', 'creer')) {
			throw new RestException(403);
		}

		$result = $this->company->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Thirdparty not found');
		}
		$category = new Categorie($this->db);
		$result = $category->fetch($category_id);
		if (!$result) {
			throw new RestException(404, 'category not found');
		}

		if (!DolibarrApi::_checkAccessToResource('societe', $this->company->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}
		if (!DolibarrApi::_checkAccessToResource('category', $category->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$category->add_type($this->company, 'supplier');

		return $this->_cleanObjectDatas($this->company);
	}

	/**
	 * Remove the link between a category and the thirdparty
	 *
	 * @param int		$id				Id of thirdparty
	 * @param int		$category_id	Id of category
	 *
	 * @return mixed
	 *
	 * @url DELETE {id}/supplier_categories/{category_id}
	 */
	public function deleteSupplierCategory($id, $category_id)
	{
		if (!DolibarrApiAccess::$user->hasRight('societe', 'creer')) {
			throw new RestException(403);
		}

		$result = $this->company->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Thirdparty not found');
		}
		$category = new Categorie($this->db);
		$result = $category->fetch($category_id);
		if (!$result) {
			throw new RestException(404, 'category not found');
		}

		if (!DolibarrApi::_checkAccessToResource('societe', $this->company->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}
		if (!DolibarrApi::_checkAccessToResource('category', $category->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$category->del_type($this->company, 'supplier');

		return $this->_cleanObjectDatas($this->company);
	}


	/**
	 * Get outstanding proposals of thirdparty
	 *
	 * @param	int		$id			ID of the thirdparty
	 * @param	string	$mode		'customer' or 'supplier'
	 *
	 * @url     GET {id}/outstandingproposals
	 *
	 * @return array				List of outstandings proposals of thirdparty
	 *
	 * @throws RestException 400
	 * @throws RestException 401
	 * @throws RestException 404
	 */
	public function getOutStandingProposals($id, $mode = 'customer')
	{
		if (!DolibarrApiAccess::$user->hasRight('societe', 'lire')) {
			throw new RestException(403);
		}

		if (empty($id)) {
			throw new RestException(400, 'Thirdparty ID is mandatory');
		}

		if (!DolibarrApi::_checkAccessToResource('societe', $id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$result = $this->company->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Thirdparty not found');
		}

		$result = $this->company->getOutstandingProposals($mode);

		unset($result['total_ht']);
		unset($result['total_ttc']);

		return $result;
	}


	/**
	 * Get outstanding orders of thirdparty
	 *
	 * @param	int		$id			ID of the thirdparty
	 * @param	string	$mode		'customer' or 'supplier'
	 *
	 * @url     GET {id}/outstandingorders
	 *
	 * @return array				List of outstandings orders of thirdparty
	 *
	 * @throws RestException 400
	 * @throws RestException 401
	 * @throws RestException 404
	 */
	public function getOutStandingOrder($id, $mode = 'customer')
	{
		if (!DolibarrApiAccess::$user->hasRight('societe', 'lire')) {
			throw new RestException(403);
		}

		if (empty($id)) {
			throw new RestException(400, 'Thirdparty ID is mandatory');
		}

		if (!DolibarrApi::_checkAccessToResource('societe', $id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$result = $this->company->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Thirdparty not found');
		}

		$result = $this->company->getOutstandingOrders($mode);

		unset($result['total_ht']);
		unset($result['total_ttc']);

		return $result;
	}

	/**
	 * Get outstanding invoices of thirdparty
	 *
	 * @param	int		$id			ID of the thirdparty
	 * @param	string	$mode		'customer' or 'supplier'
	 *
	 * @url     GET {id}/outstandinginvoices
	 *
	 * @return array				List of outstandings invoices of thirdparty
	 *
	 * @throws RestException 400
	 * @throws RestException 401
	 * @throws RestException 404
	 */
	public function getOutStandingInvoices($id, $mode = 'customer')
	{
		if (!DolibarrApiAccess::$user->hasRight('societe', 'lire')) {
			throw new RestException(403);
		}

		if (empty($id)) {
			throw new RestException(400, 'Thirdparty ID is mandatory');
		}

		if (!DolibarrApi::_checkAccessToResource('societe', $id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$result = $this->company->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Thirdparty not found');
		}

		$result = $this->company->getOutstandingBills($mode);

		unset($result['total_ht']);
		unset($result['total_ttc']);

		return $result;
	}

	/**
	 * Get representatives of thirdparty
	 *
	 * @param	int		$id			ID of the thirdparty
	 * @param	int 	$mode		0=Array with properties, 1=Array of id.
	 *
	 * @url     GET {id}/representatives
	 *
	 * @return array				List of representatives of thirdparty
	 *
	 * @throws RestException 400
	 * @throws RestException 401
	 * @throws RestException 404
	 */
	public function getSalesRepresentatives($id, $mode = 0)
	{
		if (!DolibarrApiAccess::$user->hasRight('societe', 'lire')) {
			throw new RestException(403);
		}

		if (empty($id)) {
			throw new RestException(400, 'Thirdparty ID is mandatory');
		}

		if (!DolibarrApi::_checkAccessToResource('societe', $id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$result = $this->company->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Thirdparty not found');
		}

		$result = $this->company->getSalesRepresentatives(DolibarrApiAccess::$user, $mode);

		return $result;
	}

	/**
	 * Get fixed amount discount of a thirdparty (all sources: deposit, credit note, commercial offers...)
	 *
	 * @param	int		$id             ID of the thirdparty
	 * @param	string	$filter		Filter exceptional discount. "none" will return every discount, "available" returns unapplied discounts, "used" returns applied discounts   {@choice none,available,used}
	 * @param   string  $sortfield		Sort field
	 * @param   string  $sortorder		Sort order
	 *
	 * @url     GET {id}/fixedamountdiscounts
	 *
	 * @return array  List of fixed discount of thirdparty
	 *
	 * @throws RestException 400
	 * @throws RestException 401
	 * @throws RestException 404
	 * @throws RestException 503
	 */
	public function getFixedAmountDiscounts($id, $filter = "none", $sortfield = "f.type", $sortorder = 'ASC')
	{
		$obj_ret = array();

		if (!DolibarrApiAccess::$user->hasRight('societe', 'lire')) {
			throw new RestException(403);
		}

		if (empty($id)) {
			throw new RestException(400, 'Thirdparty ID is mandatory');
		}

		if (!DolibarrApi::_checkAccessToResource('societe', $id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$result = $this->company->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Thirdparty not found');
		}


		$sql = "SELECT f.ref, f.type as factype, re.fk_facture_source, re.rowid, re.amount_ht, re.amount_tva, re.amount_ttc, re.description, re.fk_facture, re.fk_facture_line";
		$sql .= " FROM ".MAIN_DB_PREFIX."societe_remise_except as re, ".MAIN_DB_PREFIX."facture as f";
		$sql .= " WHERE f.rowid = re.fk_facture_source AND re.fk_soc = ".((int) $id);
		if ($filter == "available") {
			$sql .= " AND re.fk_facture IS NULL AND re.fk_facture_line IS NULL";
		}
		if ($filter == "used") {
			$sql .= " AND (re.fk_facture IS NOT NULL OR re.fk_facture_line IS NOT NULL)";
		}

		$sql .= $this->db->order($sortfield, $sortorder);

		$result = $this->db->query($sql);
		if (!$result) {
			throw new RestException(503, $this->db->lasterror());
		} else {
			$num = $this->db->num_rows($result);
			while ($obj = $this->db->fetch_object($result)) {
				$obj_ret[] = $obj;
			}
		}

		return $obj_ret;
	}



	/**
	 * Return list of invoices qualified to be replaced by another invoice.
	 *
	 * @param int   $id             Id of thirdparty
	 *
	 * @url     GET {id}/getinvoicesqualifiedforreplacement
	 *
	 * @return array
	 * @throws RestException 400
	 * @throws RestException 401
	 * @throws RestException 404
	 * @throws RestException 405
	 */
	public function getInvoicesQualifiedForReplacement($id)
	{
		if (!DolibarrApiAccess::$user->hasRight('facture', 'lire')) {
			throw new RestException(403);
		}
		if (empty($id)) {
			throw new RestException(400, 'Thirdparty ID is mandatory');
		}

		if (!DolibarrApi::_checkAccessToResource('societe', $id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		/*$result = $this->thirdparty->fetch($id);
		 if( ! $result ) {
		 throw new RestException(404, 'Thirdparty not found');
		 }*/

		require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
		$invoice = new Facture($this->db);
		$result = $invoice->list_replacable_invoices($id);
		if ($result < 0) {
			throw new RestException(405, $invoice->error);
		}

		return $result;
	}

	/**
	 * Return list of invoices qualified to be corrected by a credit note.
	 * Invoices matching the following rules are returned
	 * (validated + payment on process) or classified (paid completely or paid partially) + not already replaced + not already a credit note
	 *
	 * @param int   $id             Id of thirdparty
	 *
	 * @url     GET {id}/getinvoicesqualifiedforcreditnote
	 *
	 * @return array
	 *
	 * @throws RestException 400
	 * @throws RestException 401
	 * @throws RestException 404
	 * @throws RestException 405
	 */
	public function getInvoicesQualifiedForCreditNote($id)
	{
		if (!DolibarrApiAccess::$user->hasRight('facture', 'lire')) {
			throw new RestException(403);
		}
		if (empty($id)) {
			throw new RestException(400, 'Thirdparty ID is mandatory');
		}

		if (!DolibarrApi::_checkAccessToResource('societe', $id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		/*$result = $this->thirdparty->fetch($id);
		 if( ! $result ) {
		 throw new RestException(404, 'Thirdparty not found');
		 }*/

		require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
		$invoice = new Facture($this->db);
		$result = $invoice->list_qualified_avoir_invoices($id);
		if ($result < 0) {
			throw new RestException(405, $invoice->error);
		}

		return $result;
	}

	/**
	 * Get CompanyNotification objects for thirdparty
	 *
	 * @param int $id ID of thirdparty
	 *
	 * @return array
	 *
	 * @url GET {id}/notifications
	 */
	public function getCompanyNotification($id)
	{
		if (empty($id)) {
			throw new RestException(400, 'Thirdparty ID is mandatory');
		}
		if (!DolibarrApiAccess::$user->hasRight('societe', 'lire')) {
			throw new RestException(403);
		}
		if (!DolibarrApi::_checkAccessToResource('societe', $id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		/**
		 * We select all the records that match the socid
		 */

		$sql = "SELECT rowid as id, fk_action, fk_action as event, fk_soc, fk_soc as socid, fk_contact, fk_contact as target, type, datec, tms";
		$sql .= " FROM ".MAIN_DB_PREFIX."notify_def";
		if ($id) {
			$sql .= " WHERE fk_soc  = ".((int) $id);
		}

		$result = $this->db->query($sql);
		if ($this->db->num_rows($result) == 0) {
			throw new RestException(404, 'Notification not found');
		}

		$i = 0;

		$notifications = array();

		if ($result) {
			$num = $this->db->num_rows($result);
			while ($i < $num) {
				$obj = $this->db->fetch_object($result);
				$notifications[] = $obj;
				$i++;
			}
		} else {
			throw new RestException(404, 'No notifications found');
		}

		$fields = array('id', 'socid', 'fk_soc', 'fk_action', 'event', 'fk_contact', 'target', 'datec', 'tms', 'type');

		$returnNotifications = array();

		foreach ($notifications as $notification) {
			$object = array();
			foreach ($notification as $key => $value) {
				if (in_array($key, $fields)) {
					$object[$key] = $value;
				}
			}
			$returnNotifications[] = $object;
		}

		return $returnNotifications;
	}

	/**
	 * Create CompanyNotification object for thirdparty
	 * @param int  $id ID of thirdparty
	 * @param array $request_data Request data
	 *
	 * @return array|mixed  Notification of thirdparty
	 *
	 * @url POST {id}/notifications
	 */
	public function createCompanyNotification($id, $request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('societe', 'creer')) {
			throw new RestException(403, "User has no right to update thirdparties");
		}
		if ($this->company->fetch($id) <= 0) {
			throw new RestException(404, 'Error creating Thirdparty Notification, Thirdparty doesn\'t exists');
		}
		$notification = new Notify($this->db);

		$notification->socid = $id;

		foreach ($request_data as $field => $value) {
			$notification->$field = $value;
		}

		if ($notification->create(DolibarrApiAccess::$user) < 0) {
			throw new RestException(500, 'Error creating Thirdparty Notification');
		}

		if ($notification->update(DolibarrApiAccess::$user) < 0) {
			throw new RestException(500, 'Error updating values');
		}

		return $this->_cleanObjectDatas($notification);
	}

	/**
	 * Create CompanyNotification object for thirdparty using action trigger code
	 * @param int  $id ID of thirdparty
	 * @param string  $code Action Trigger code
	 * @param array $request_data Request data
	 *
	 * @return array|mixed  Notification of thirdparty
	 *
	 * @url POST {id}/notificationsbycode/{code}
	 */
	public function createCompanyNotificationByCode($id, $code, $request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('societe', 'creer')) {
			throw new RestException(403, "User has no right to update thirdparties");
		}
		if ($this->company->fetch($id) <= 0) {
			throw new RestException(404, 'Error creating Thirdparty Notification, Thirdparty doesn\'t exists');
		}
		$notification = new Notify($this->db);
		$notification->socid = $id;

		$sql = "SELECT t.rowid as id FROM ".MAIN_DB_PREFIX."c_action_trigger as t";
		$sql .= " WHERE t.code = '".$this->db->escape($code)."'";

		$result = $this->db->query($sql);
		if ($this->db->num_rows($result) == 0) {
			throw new RestException(404, 'Action Trigger code not found');
		}

		$notification->event = $this->db->fetch_row($result)[0];
		foreach ($request_data as $field => $value) {
			$notification->$field = $value;
		}

		$event = $notification->event;
		$socid = $notification->socid;
		$contact_id = $notification->contact_id;

		$exists_sql = "SELECT rowid, fk_action as event, fk_soc as socid, fk_contact as contact_id, type, datec, tms as datem";
		$exists_sql .= " FROM ".MAIN_DB_PREFIX."notify_def";
		$exists_sql .= " WHERE fk_action = '".$this->db->escape($event)."'";
		$exists_sql .= " AND fk_soc = '".$this->db->escape($socid)."'";
		$exists_sql .= " AND fk_contact = '".$this->db->escape($contact_id)."'";

		$exists_result = $this->db->query($exists_sql);
		if ($this->db->num_rows($exists_sql) > 0) {
			throw new RestException(403, 'Notification already exists');
		}

		if ($notification->create(DolibarrApiAccess::$user) < 0) {
			throw new RestException(500, 'Error creating Thirdparty Notification, are request_data well formed?');
		}

		if ($notification->update(DolibarrApiAccess::$user) < 0) {
			throw new RestException(500, 'Error updating values');
		}

		return $this->_cleanObjectDatas($notification);
	}

	/**
	 * Delete a CompanyNotification attached to a thirdparty
	 *
	 * @param int $id ID of thirdparty
	 * @param int $notification_id ID of CompanyNotification
	 *
	 * @return int -1 if error 1 if correct deletion
	 *
	 * @url DELETE {id}/notifications/{notification_id}
	 */
	public function deleteCompanyNotification($id, $notification_id)
	{
		if (!DolibarrApiAccess::$user->hasRight('societe', 'creer')) {
			throw new RestException(403);
		}

		$notification = new Notify($this->db);

		$notification->fetch($notification_id);

		$socid = (int) $notification->socid;

		if ($socid == $id) {
			return $notification->delete(DolibarrApiAccess::$user);
		} else {
			throw new RestException(403, "Not allowed due to bad consistency of input data");
		}
	}

	/**
	 * Update CompanyNotification object for thirdparty
	 *
	 * @param int $id ID of thirdparty
	 * @param int  $notification_id ID of CompanyNotification
	 * @param array $request_data Request data
	 *
	 * @return array|mixed  Notification of thirdparty
	 *
	 * @url PUT {id}/notifications/{notification_id}
	 */
	public function updateCompanyNotification($id, $notification_id, $request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('societe', 'creer')) {
			throw new RestException(403, "User has no right to update thirdparties");
		}
		if ($this->company->fetch($id) <= 0) {
			throw new RestException(404, 'Error creating Company Notification, Company doesn\'t exists');
		}
		$notification = new Notify($this->db);

		// @phan-suppress-next-line PhanPluginSuspiciousParamPosition
		$notification->fetch($notification_id, $id);

		if ($notification->socid != $id) {
			throw new RestException(403, "Not allowed due to bad consistency of input data");
		}

		foreach ($request_data as $field => $value) {
			$notification->$field = $value;
		}

		if ($notification->update(DolibarrApiAccess::$user) < 0) {
			throw new RestException(500, 'Error updating values');
		}

		return $this->_cleanObjectDatas($notification);
	}

	/**
	 * Get CompanyBankAccount objects for thirdparty
	 *
	 * @param int $id ID of thirdparty
	 *
	 * @return array
	 *
	 * @url GET {id}/bankaccounts
	 */
	public function getCompanyBankAccount($id)
	{
		if (!DolibarrApiAccess::$user->hasRight('societe', 'lire')) {
			throw new RestException(403);
		}
		if (empty($id)) {
			throw new RestException(400, 'Thirdparty ID is mandatory');
		}

		if (!DolibarrApi::_checkAccessToResource('societe', $id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		/**
		 * We select all the records that match the socid
		 */

		$sql = "SELECT rowid, fk_soc, bank, number, code_banque, code_guichet, cle_rib, bic, iban_prefix as iban, domiciliation as address, proprio,";
		$sql .= " owner_address, default_rib, label, datec, tms as datem, rum, frstrecur";
		$sql .= " FROM ".MAIN_DB_PREFIX."societe_rib";
		if ($id) {
			$sql .= " WHERE fk_soc  = ".((int) $id);
		}

		$result = $this->db->query($sql);

		if ($this->db->num_rows($result) == 0) {
			throw new RestException(404, 'Account not found');
		}

		$i = 0;

		$accounts = array();

		if ($result) {
			$num = $this->db->num_rows($result);
			while ($i < $num) {
				$obj = $this->db->fetch_object($result);
				$account = new CompanyBankAccount($this->db);
				if ($account->fetch($obj->rowid)) {
					$accounts[] = $account;
				}
				$i++;
			}
		} else {
			throw new RestException(404, 'Account not found');
		}


		$fields = array('socid', 'default_rib', 'frstrecur', '1000110000001', 'datec', 'datem', 'label', 'bank', 'bic', 'iban', 'id', 'rum');

		$returnAccounts = array();

		foreach ($accounts as $account) {
			$object = array();
			foreach ($account as $key => $value) {
				if (in_array($key, $fields)) {
					$object[$key] = $value;
				}
			}
			$returnAccounts[] = $object;
		}

		return $returnAccounts;
	}

	/**
	 * Create CompanyBankAccount object for thirdparty
	 * @param int  $id ID of thirdparty
	 * @param array $request_data Request data
	 *
	 * @return array|mixed  BankAccount of thirdparty
	 *
	 * @url POST {id}/bankaccounts
	 */
	public function createCompanyBankAccount($id, $request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('societe', 'creer')) {
			throw new RestException(403);
		}
		if ($this->company->fetch($id) <= 0) {
			throw new RestException(404, 'Error creating Company Bank account, Company doesn\'t exists');
		}
		$account = new CompanyBankAccount($this->db);

		$account->socid = $id;

		foreach ($request_data as $field => $value) {
			if ($field === 'caller') {
				// Add a mention of caller so on trigger called after action, we can filter to avoid a loop if we try to sync back again with the caller
				$this->company->context['caller'] = sanitizeVal($request_data['caller'], 'aZ09');
				continue;
			}

			$account->$field = $this->_checkValForAPI('extrafields', $value, $account);
		}

		if ($account->create(DolibarrApiAccess::$user) < 0) {
			throw new RestException(500, 'Error creating Company Bank account');
		}

		if (empty($account->rum)) {
			require_once DOL_DOCUMENT_ROOT.'/compta/prelevement/class/bonprelevement.class.php';
			$prelevement = new BonPrelevement($this->db);
			$account->rum = $prelevement->buildRumNumber($this->company->code_client, $account->datec, $account->id);
			$account->date_rum = dol_now();
		}

		if ($account->update(DolibarrApiAccess::$user) < 0) {
			throw new RestException(500, 'Error updating values');
		}

		return $this->_cleanObjectDatas($account);
	}

	/**
	 * Update CompanyBankAccount object for thirdparty
	 *
	 * @param int $id ID of thirdparty
	 * @param int  $bankaccount_id ID of CompanyBankAccount
	 * @param array $request_data Request data
	 *
	 * @return array|mixed  BankAccount of thirdparty
	 *
	 * @url PUT {id}/bankaccounts/{bankaccount_id}
	 */
	public function updateCompanyBankAccount($id, $bankaccount_id, $request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('societe', 'creer')) {
			throw new RestException(403);
		}
		if ($this->company->fetch($id) <= 0) {
			throw new RestException(404, 'Error creating Company Bank account, Company doesn\'t exists');
		}
		$account = new CompanyBankAccount($this->db);

		// @phan-suppress-next-line PhanPluginSuspiciousParamPosition
		$account->fetch($bankaccount_id, '', $id, -1, '');

		if ($account->socid != $id) {
			throw new RestException(403);
		}


		foreach ($request_data as $field => $value) {
			if ($field === 'caller') {
				// Add a mention of caller so on trigger called after action, we can filter to avoid a loop if we try to sync back again with the caller
				$account->context['caller'] = sanitizeVal($request_data['caller'], 'aZ09');
				continue;
			}

			$account->$field = $this->_checkValForAPI($field, $value, $account);
		}

		if (empty($account->rum)) {
			require_once DOL_DOCUMENT_ROOT.'/compta/prelevement/class/bonprelevement.class.php';
			$prelevement = new BonPrelevement($this->db);
			$account->rum = $prelevement->buildRumNumber($this->company->code_client, $account->datec, $account->id);
			$account->date_rum = dol_now();
		}

		if ($account->update(DolibarrApiAccess::$user) < 0) {
			throw new RestException(500, 'Error updating values');
		}

		return $this->_cleanObjectDatas($account);
	}

	/**
	 * Delete a bank account attached to a thirdparty
	 *
	 * @param int $id ID of thirdparty
	 * @param int $bankaccount_id ID of CompanyBankAccount
	 *
	 * @return int -1 if error 1 if correct deletion
	 *
	 * @url DELETE {id}/bankaccounts/{bankaccount_id}
	 */
	public function deleteCompanyBankAccount($id, $bankaccount_id)
	{
		if (!DolibarrApiAccess::$user->hasRight('societe', 'creer')) {
			throw new RestException(403);
		}

		$account = new CompanyBankAccount($this->db);

		$account->fetch($bankaccount_id);

		$socid = (int) $account->socid;

		if ($socid == $id) {
			return $account->delete(DolibarrApiAccess::$user);
		} else {
			throw new RestException(403, "Not allowed due to bad consistency of input data");
		}
	}

	/**
	 * Generate a Document from a bank account record (like SEPA mandate)
	 *
	 * @param int		$id				Thirdparty id
	 * @param int		$companybankid	Companybank id
	 * @param string	$model			Model of document to generate
	 * @return array
	 *
	 * @url GET {id}/generateBankAccountDocument/{companybankid}/{model}
	 */
	public function generateBankAccountDocument($id, $companybankid = null, $model = 'sepamandate')
	{
		global $conf, $langs;

		$langs->loadLangs(array("main", "dict", "commercial", "products", "companies", "banks", "bills", "withdrawals"));

		if ($this->company->fetch($id) <= 0) {
			throw new RestException(404, 'Thirdparty not found');
		}

		if (!DolibarrApiAccess::$user->hasRight('societe', 'creer')) {
			throw new RestException(403);
		}

		$this->company->setDocModel(DolibarrApiAccess::$user, $model);

		$this->company->fk_bank = $this->company->fk_account;
		// $this->company->fk_account = $this->company->fk_account;

		$outputlangs = $langs;
		$newlang = '';

		//if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang) && GETPOST('lang_id', 'aZ09')) $newlang = GETPOST('lang_id', 'aZ09');
		if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang)) {
			if (isset($this->company->thirdparty->default_lang)) {
				$newlang = $this->company->thirdparty->default_lang; // for proposal, order, invoice, ...
			} elseif (isset($this->company->default_lang)) {
				$newlang = $this->company->default_lang; // for thirdparty
			}
		}
		if (!empty($newlang)) {
			$outputlangs = new Translate("", $conf);
			$outputlangs->setDefaultLang($newlang);
		}

		$sql = "SELECT rowid";
		$sql .= " FROM ".MAIN_DB_PREFIX."societe_rib";
		if ($id) {
			$sql .= " WHERE fk_soc = ".((int) $id);
		}
		if ($companybankid) {
			$sql .= " AND rowid = ".((int) $companybankid);
		}

		$i = 0;
		$accounts = array();

		$result = $this->db->query($sql);
		if ($result) {
			if ($this->db->num_rows($result) == 0) {
				throw new RestException(404, 'Bank account not found');
			}

			$num = $this->db->num_rows($result);
			while ($i < $num) {
				$obj = $this->db->fetch_object($result);

				$account = new CompanyBankAccount($this->db);
				if ($account->fetch($obj->rowid)) {
					$accounts[] = $account;
				}
				$i++;
			}
		} else {
			throw new RestException(500, 'Sql error '.$this->db->lasterror());
		}

		$moreparams = array(
			'use_companybankid' => $accounts[0]->id,
			'force_dir_output' => $conf->societe->multidir_output[$this->company->entity].'/'.dol_sanitizeFileName($this->company->id)
		);

		$result = $this->company->generateDocument($model, $outputlangs, 0, 0, 0, $moreparams);

		if ($result > 0) {
			return array("success" => $result);
		} else {
			throw new RestException(500, 'Error generating the document '.$this->company->error);
		}
	}

	/**
	 * Get a specific account attached to a thirdparty (by specifying the site key)
	 *
	 * @param int $id ID of thirdparty
	 * @param string $site Site key
	 *
	 * @return array|mixed
	 * @throws RestException 401 Unauthorized: User does not have permission to read thirdparties
	 * @throws RestException 404 Not Found: Specified thirdparty ID does not belongs to an existing thirdparty
	 *
	 * @url GET {id}/accounts/
	 */
	public function getSocieteAccounts($id, $site = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('societe', 'lire')) {
			throw new RestException(403);
		}

		if (!DolibarrApi::_checkAccessToResource('societe', $id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		/**
		 * We select all the records that match the socid
		 */
		$sql = "SELECT rowid, fk_soc, key_account, site, date_creation, tms FROM ".MAIN_DB_PREFIX."societe_account";
		$sql .= " WHERE fk_soc = ".((int) $id);
		if ($site) {
			$sql .= " AND site ='".$this->db->escape($site)."'";
		}

		$result = $this->db->query($sql);

		if ($result && $this->db->num_rows($result) == 0) {
			throw new RestException(404, 'This thirdparty does not have any account attached or does not exist.');
		}

		$i = 0;

		$accounts = array();

		$num = $this->db->num_rows($result);
		while ($i < $num) {
			$obj = $this->db->fetch_object($result);
			$account = new SocieteAccount($this->db);

			if ($account->fetch($obj->rowid)) {
				$accounts[] = $account;
			}
			$i++;
		}

		$fields = array('id', 'fk_soc', 'key_account', 'site', 'date_creation', 'tms');

		$returnAccounts = array();

		foreach ($accounts as $account) {
			$object = array();
			foreach ($account as $key => $value) {
				if (in_array($key, $fields)) {
					$object[$key] = $value;
				}
			}
			$returnAccounts[] = $object;
		}

		return $returnAccounts;
	}

	/**
	 * Create and attach a new account to an existing thirdparty
	 *
	 * Possible fields for request_data (request body) are specified in <code>llx_societe_account</code> table.<br>
	 * See <a href="https://wiki.dolibarr.org/index.php/Table_llx_societe_account">Table llx_societe_account</a> wiki page for more information<br><br>
	 * <u>Example body payload :</u> <pre>{"key_account": "cus_DAVkLSs1LYyYI", "site": "stripe"}</pre>
	 *
	 * @param int $id ID of thirdparty
	 * @param array $request_data Request data
	 *
	 * @return array|mixed
	 *
	 * @throws RestException 401 Unauthorized: User does not have permission to read thirdparties
	 * @throws RestException 409 Conflict: An Account already exists for this company and site.
	 * @throws RestException 422 Unprocessable Entity: You must pass the site attribute in your request data !
	 * @throws RestException 500 Internal Server Error: Error creating SocieteAccount account
	 *
	 * @url POST {id}/accounts
	 */
	public function createSocieteAccount($id, $request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('societe', 'creer')) {
			throw new RestException(403);
		}

		if (!isset($request_data['site'])) {
			throw new RestException(422, 'Unprocessable Entity: You must pass the site attribute in your request data !');
		}

		$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."societe_account WHERE fk_soc  = ".((int) $id)." AND site = '".$this->db->escape($request_data['site'])."'";
		$result = $this->db->query($sql);

		if ($result && $this->db->num_rows($result) == 0) {
			$account = new SocieteAccount($this->db);
			if (!isset($request_data['login'])) {
				$account->login = "";
			}
			$account->fk_soc = $id;

			foreach ($request_data as $field => $value) {
				if ($field === 'caller') {
					// Add a mention of caller so on trigger called after action, we can filter to avoid a loop if we try to sync back again with the caller
					$account->context['caller'] = sanitizeVal($request_data['caller'], 'aZ09');
					continue;
				}

				$account->$field = $this->_checkValForAPI($field, $value, $account);
			}

			if ($account->create(DolibarrApiAccess::$user) < 0) {
				throw new RestException(500, 'Error creating SocieteAccount entity. Ensure that the ID of thirdparty provided does exist!');
			}

			$this->_cleanObjectDatas($account);

			return $account;
		} else {
			throw new RestException(409, 'A SocieteAccount entity already exists for this company and site.');
		}
	}

	/**
	 * Create and attach a new (or replace an existing) specific site account to a thirdparty
	 *
	 * You <strong>MUST</strong> pass all values to keep (otherwise, they will be deleted) !<br>
	 * If you just need to update specific fields prefer <code>PATCH /thirdparties/{id}/accounts/{site}</code> endpoint.<br><br>
	 * When a <strong>SocieteAccount</strong> entity does not exist for the <code>id</code> and <code>site</code>
	 * supplied, a new one will be created. In that case <code>fk_soc</code> and <code>site</code> members form
	 * request body payload will be ignored and <code>id</code> and <code>site</code> query strings parameters
	 * will be used instead.
	 *
	 * @param int $id ID of thirdparty
	 * @param string $site Site key
	 * @param array $request_data Request data
	 *
	 * @return array|mixed
	 *
	 * @throws RestException 401 Unauthorized: User does not have permission to read thirdparties
	 * @throws RestException 422 Unprocessable Entity: You must pass the site attribute in your request data !
	 * @throws RestException 500 Internal Server Error: Error updating SocieteAccount entity
	 *
	 * @url PUT {id}/accounts/{site}
	 */
	public function putSocieteAccount($id, $site, $request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('societe', 'creer')) {
			throw new RestException(403);
		}

		$sql = "SELECT rowid, fk_user_creat, date_creation FROM ".MAIN_DB_PREFIX."societe_account WHERE fk_soc = $id AND site = '".$this->db->escape($site)."'";
		$result = $this->db->query($sql);

		// We do not found an existing SocieteAccount entity for this fk_soc and site ; we then create a new one.
		if ($result && $this->db->num_rows == 0) {
			if (!isset($request_data['key_account'])) {
				throw new RestException(422, 'Unprocessable Entity: You must pass the key_account attribute in your request data !');
			}
			$account = new SocieteAccount($this->db);
			if (!isset($request_data['login'])) {
				$account->login = "";
			}

			foreach ($request_data as $field => $value) {
				if ($field === 'caller') {
					// Add a mention of caller so on trigger called after action, we can filter to avoid a loop if we try to sync back again with the caller
					$account->context['caller'] = sanitizeVal($request_data['caller'], 'aZ09');
					continue;
				}

				$account->$field = $this->_checkValForAPI($field, $value, $account);
			}

			$account->fk_soc = $id;
			$account->site = $site;

			if ($account->create(DolibarrApiAccess::$user) < 0) {
				throw new RestException(500, 'Error creating SocieteAccount entity.');
			}
			// We found an existing SocieteAccount entity, we are replacing it
		} else {
			if (isset($request_data['site']) && $request_data['site'] !== $site) {
				$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."societe_account WHERE fk_soc  = ".((int) $id)." AND site = '".$this->db->escape($request_data['site'])."' ";
				$result = $this->db->query($sql);

				if ($result && $this->db->num_rows($result) !== 0) {
					throw new RestException(409, "You are trying to update this thirdparty Account for $site to ".$request_data['site']." but another Account already exists with this site key.");
				}
			}

			$obj = $this->db->fetch_object($result);

			$account = new SocieteAccount($this->db);
			$account->id = $obj->rowid;
			$account->fk_soc = $id;
			$account->site = $site;
			if (!isset($request_data['login'])) {
				$account->login = "";
			}
			$account->fk_user_creat = $obj->fk_user_creat;
			$account->date_creation = $obj->date_creation;

			foreach ($request_data as $field => $value) {
				if ($field === 'caller') {
					// Add a mention of caller so on trigger called after action, we can filter to avoid a loop if we try to sync back again with the caller
					$account->context['caller'] = sanitizeVal($request_data['caller'], 'aZ09');
					continue;
				}

				$account->$field = $this->_checkValForAPI($field, $value, $account);
			}

			if ($account->update(DolibarrApiAccess::$user) < 0) {
				throw new RestException(500, 'Error updating SocieteAccount entity.');
			}
		}

		$this->_cleanObjectDatas($account);

		return $account;
	}

	/**
	 * Update specified values of a specific account attached to a thirdparty
	 *
	 * @param int		$id				Id of thirdparty
	 * @param string	$site			Site key
	 * @param array		$request_data	Request data
	 *
	 * @return array|mixed
	 *
	 * @throws RestException 401 Unauthorized: User does not have permission to read thirdparties
	 * @throws RestException 404 Not Found: Specified thirdparty ID does not belongs to an existing thirdparty
	 * @throws RestException 409 Conflict: Another SocieteAccount entity already exists for this thirdparty with this site key.
	 * @throws RestException 500 Internal Server Error: Error updating SocieteAccount entity
	 *
	 * @url PATCH {id}/accounts/{site}
	 */
	public function patchSocieteAccount($id, $site, $request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('societe', 'creer')) {
			throw new RestException(403);
		}

		$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."societe_account WHERE fk_soc = ".((int) $id)." AND site = '".$this->db->escape($site)."'";
		$result = $this->db->query($sql);

		if ($result && $this->db->num_rows($result) == 0) {
			throw new RestException(404, "This thirdparty does not have $site account attached or does not exist.");
		} else {
			// If the user tries to edit the site member, we check first if
			if (isset($request_data['site']) && $request_data['site'] !== $site) {
				$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."societe_account WHERE fk_soc  = ".((int) $id)." AND site = '".$this->db->escape($request_data['site'])."' ";
				$result = $this->db->query($sql);

				if ($result && $this->db->num_rows($result) !== 0) {
					throw new RestException(409, "You are trying to update this thirdparty Account for ".$site." to ".$request_data['site']." but another Account already exists for this thirdparty with this site key.");
				}
			}

			$obj = $this->db->fetch_object($result);
			$account = new SocieteAccount($this->db);
			$account->fetch($obj->rowid);

			foreach ($request_data as $field => $value) {
				if ($field === 'caller') {
					// Add a mention of caller so on trigger called after action, we can filter to avoid a loop if we try to sync back again with the caller
					$account->context['caller'] = sanitizeVal($request_data['caller'], 'aZ09');
					continue;
				}

				$account->$field = $this->_checkValForAPI($field, $value, $account);
			}

			if ($account->update(DolibarrApiAccess::$user) < 0) {
				throw new RestException(500, 'Error updating SocieteAccount account');
			}

			$this->_cleanObjectDatas($account);

			return $account;
		}
	}

	/**
	 * Delete a specific site account attached to a thirdparty (by account id)
	 *
	 * @param int $id ID of thirdparty
	 * @param int $site Site key
	 *
	 * @return void
	 * @throws RestException 401 Unauthorized: User does not have permission to delete thirdparties accounts
	 * @throws RestException 404 Not Found: Specified thirdparty ID does not belongs to an existing thirdparty
	 * @throws RestException 500 Internal Server Error: Error deleting SocieteAccount entity
	 *
	 * @url DELETE {id}/accounts/{site}
	 */
	public function deleteSocieteAccount($id, $site)
	{
		if (!DolibarrApiAccess::$user->hasRight('societe', 'creer')) {
			throw new RestException(403);
		}

		$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."societe_account WHERE fk_soc  = $id AND site = '".$this->db->escape($site)."'";
		$result = $this->db->query($sql);

		if ($result && $this->db->num_rows($result) == 0) {
			throw new RestException(404);
		} else {
			$obj = $this->db->fetch_object($result);
			$account = new SocieteAccount($this->db);
			$account->fetch($obj->rowid);

			if ($account->delete(DolibarrApiAccess::$user) < 0) {
				throw new RestException(500, "Error while deleting $site account attached to this third party");
			}
		}
	}

	/**
	 * Delete all accounts attached to a thirdparty
	 *
	 * @param int $id ID of thirdparty
	 *
	 * @return void
	 * @throws RestException 401 Unauthorized: User does not have permission to delete thirdparties accounts
	 * @throws RestException 404 Not Found: Specified thirdparty ID does not belongs to an existing thirdparty
	 * @throws RestException 500 Internal Server Error: Error deleting SocieteAccount entity
	 *
	 * @url DELETE {id}/accounts
	 */
	public function deleteSocieteAccounts($id)
	{
		if (!DolibarrApiAccess::$user->hasRight('societe', 'creer')) {
			throw new RestException(403);
		}

		/**
		 * We select all the records that match the socid
		 */

		$sql = "SELECT rowid, fk_soc, key_account, site, date_creation, tms";
		$sql .= " FROM ".MAIN_DB_PREFIX."societe_account WHERE fk_soc = ".((int) $id);

		$result = $this->db->query($sql);

		if ($result && $this->db->num_rows($result) == 0) {
			throw new RestException(404, 'This third party does not have any account attached or does not exist.');
		} else {
			$i = 0;

			$num = $this->db->num_rows($result);
			while ($i < $num) {
				$obj = $this->db->fetch_object($result);
				$account = new SocieteAccount($this->db);
				$account->fetch($obj->rowid);

				if ($account->delete(DolibarrApiAccess::$user) < 0) {
					throw new RestException(500, 'Error while deleting account attached to this third party');
				}
				$i++;
			}
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 * Clean sensible object datas
	 *
	 * @param   Object  $object     Object to clean
	 * @return  Object				Object with cleaned properties
	 */
	protected function _cleanObjectDatas($object)
	{
		// phpcs:enable
		$object = parent::_cleanObjectDatas($object);

		unset($object->nom); // ->name already defined and nom deprecated
		unset($object->name_bis); // ->name_alias already defined
		unset($object->note); // ->note_private and note_public already defined
		unset($object->departement);
		unset($object->departement_code);
		unset($object->pays);
		unset($object->particulier);
		unset($object->prefix_comm);

		unset($object->siren);
		unset($object->siret);
		unset($object->ape);

		unset($object->commercial_id); // This property is used in create/update only. It does not exists in read mode because there is several sales representatives.

		unset($object->total_ht);
		unset($object->total_tva);
		unset($object->total_localtax1);
		unset($object->total_localtax2);
		unset($object->total_ttc);

		unset($object->lines);
		unset($object->thirdparty);

		unset($object->fk_delivery_address); // deprecated feature

		return $object;
	}

	/**
	 * Validate fields before create or update object
	 *
	 * @param array $data   Datas to validate
	 * @return array
	 *
	 * @throws RestException
	 */
	private function _validate($data)
	{
		$thirdparty = array();
		foreach (Thirdparties::$FIELDS as $field) {
			if (!isset($data[$field])) {
				throw new RestException(400, "$field field missing");
			}
			$thirdparty[$field] = $data[$field];
		}
		return $thirdparty;
	}

	/**
	 * Fetch properties of a thirdparty object.
	 *
	 * Return an array with thirdparty information
	 *
	 * @param    int	$rowid      Id of third party to load (Use 0 to get a specimen record, use null to use other search criteria)
	 * @param    string	$ref        Reference of third party, name (Warning, this can return several records)
	 * @param    string	$ref_ext    External reference of third party (Warning, this information is a free field not provided by Dolibarr)
	 * @param    string	$barcode    Barcode of third party to load
	 * @param    string	$idprof1		Prof id 1 of third party (Warning, this can return several records)
	 * @param    string	$idprof2		Prof id 2 of third party (Warning, this can return several records)
	 * @param    string	$idprof3		Prof id 3 of third party (Warning, this can return several records)
	 * @param    string	$idprof4		Prof id 4 of third party (Warning, this can return several records)
	 * @param    string	$idprof5		Prof id 5 of third party (Warning, this can return several records)
	 * @param    string	$idprof6		Prof id 6 of third party (Warning, this can return several records)
	 * @param    string	$email			Email of third party (Warning, this can return several records)
	 * @param    string	$ref_alias  Name_alias of third party (Warning, this can return several records)
	 * @return object cleaned Societe object
	 *
	 * @throws RestException
	 */
	private function _fetch($rowid, $ref = '', $ref_ext = '', $barcode = '', $idprof1 = '', $idprof2 = '', $idprof3 = '', $idprof4 = '', $idprof5 = '', $idprof6 = '', $email = '', $ref_alias = '')
	{
		global $conf;

		if (!DolibarrApiAccess::$user->hasRight('societe', 'lire')) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login.'. No read permission on thirdparties.');
		}

		if ($rowid === 0) {
			$result = $this->company->initAsSpecimen();
		} else {
			$result = $this->company->fetch($rowid, $ref, $ref_ext, $barcode, $idprof1, $idprof2, $idprof3, $idprof4, $idprof5, $idprof6, $email, $ref_alias);
		}
		if (!$result) {
			throw new RestException(404, 'Thirdparty not found');
		}

		if (!DolibarrApi::_checkAccessToResource('societe', $this->company->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login.' on this thirdparty');
		}
		if (isModEnabled('mailing')) {
			$this->company->getNoEmail();
		}

		if (getDolGlobalString('FACTURE_DEPOSITS_ARE_JUST_PAYMENTS')) {
			$filterabsolutediscount = "fk_facture_source IS NULL"; // If we want deposit to be subtracted to payments only and not to total of final invoice
			$filtercreditnote = "fk_facture_source IS NOT NULL"; // If we want deposit to be subtracted to payments only and not to total of final invoice
		} else {
			$filterabsolutediscount = "fk_facture_source IS NULL OR (description LIKE '(DEPOSIT)%' AND description NOT LIKE '(EXCESS RECEIVED)%')";
			$filtercreditnote = "fk_facture_source IS NOT NULL AND (description NOT LIKE '(DEPOSIT)%' OR description LIKE '(EXCESS RECEIVED)%')";
		}

		$absolute_discount = $this->company->getAvailableDiscounts('', $filterabsolutediscount);
		$absolute_creditnote = $this->company->getAvailableDiscounts('', $filtercreditnote);
		$this->company->absolute_discount = price2num($absolute_discount, 'MT');
		$this->company->absolute_creditnote = price2num($absolute_creditnote, 'MT');

		return $this->_cleanObjectDatas($this->company);
	}
}
