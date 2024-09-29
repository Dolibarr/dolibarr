<?php
/* Copyright (C) 2016	Xebax Christy	<xebax@wanadoo.fr>
 * Copyright (C) 2017	Regis Houssin	<regis.houssin@inodbox.com>
 * Copyright (C) 2020	Thibault FOUCART<support@ptibogxiv.net>
 * Copyright (C) 2020	Frédéric France	<frederic.france@netlogic.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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

require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/subscription.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent_type.class.php';


/**
 * API class for members
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class Members extends DolibarrApi
{
	/**
	 * @var string[]   $FIELDS     Mandatory fields, checked when create and update object
	 */
	public static $FIELDS = array(
		'morphy',
		'typeid'
	);

	/**
	 * Constructor
	 */
	public function __construct()
	{
		global $db;
		$this->db = $db;
	}

	/**
	 * Get properties of a member object
	 *
	 * Return an array with member information
	 *
	 * @param   int     $id				ID of member
	 * @return  Object					Object with cleaned properties
	 *
	 * @throws	RestException	403		Access denied
	 * @throws	RestException	404		Member not found
	 */
	public function get($id)
	{
		if (!DolibarrApiAccess::$user->hasRight('adherent', 'lire')) {
			throw new RestException(403);
		}

		$member = new Adherent($this->db);
		if ($id == 0) {
			$result = $member->initAsSpecimen();
		} else {
			$result = $member->fetch($id);
		}
		if (!$result) {
			throw new RestException(404, 'member not found');
		}

		if (!DolibarrApi::_checkAccessToResource('adherent', $member->id) && $id > 0) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		return $this->_cleanObjectDatas($member);
	}

	/**
	 * Get properties of a member object by linked thirdparty
	 *
	 * Return an array with member information
	 *
	 * @param     int     $thirdparty	ID of third party
	 *
	 * @return Object					Data without useless information
	 *
	 * @url GET thirdparty/{thirdparty}
	 *
	 * @throws	RestException	403		Access denied
	 * @throws	RestException	404		Member not found
	 */
	public function getByThirdparty($thirdparty)
	{
		if (!DolibarrApiAccess::$user->hasRight('adherent', 'lire')) {
			throw new RestException(403);
		}

		$member = new Adherent($this->db);
		$result = $member->fetch(0, '', $thirdparty);
		if (!$result) {
			throw new RestException(404, 'member not found');
		}

		if (!DolibarrApi::_checkAccessToResource('adherent', $member->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		return $this->_cleanObjectDatas($member);
	}

	/**
	 * Get properties of a member object by linked thirdparty email
	 *
	 * Return an array with member information
	 *
	 * @param  string $email            Email of third party
	 *
	 * @return Object					Data without useless information
	 *
	 * @url GET thirdparty/email/{email}
	 *
	 * @throws	RestException	403		Access denied
	 * @throws	RestException	404		Member or ThirdParty not found
	 */
	public function getByThirdpartyEmail($email)
	{
		if (!DolibarrApiAccess::$user->hasRight('adherent', 'lire')) {
			throw new RestException(403);
		}

		$thirdparty = new Societe($this->db);
		$result = $thirdparty->fetch(0, '', '', '', '', '', '', '', '', '', $email);
		if (!$result) {
			throw new RestException(404, 'thirdparty not found');
		}

		$member = new Adherent($this->db);
		$result = $member->fetch(0, '', $thirdparty->id);
		if (!$result) {
			throw new RestException(404, 'member not found');
		}

		if (!DolibarrApi::_checkAccessToResource('adherent', $member->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		return $this->_cleanObjectDatas($member);
	}

	/**
	 * Get properties of a member object by linked thirdparty barcode
	 *
	 * Return an array with member information
	 *
	 * @param  string $barcode			Barcode of third party
	 *
	 * @return Object					Data without useless information
	 *
	 * @url GET thirdparty/barcode/{barcode}
	 *
	 * @throws	RestException	403		Access denied
	 * @throws	RestException	404		Member or ThirdParty not found
	 */
	public function getByThirdpartyBarcode($barcode)
	{
		if (!DolibarrApiAccess::$user->hasRight('adherent', 'lire')) {
			throw new RestException(403);
		}

		$thirdparty = new Societe($this->db);
		$result = $thirdparty->fetch(0, '', '', $barcode);
		if (!$result) {
			throw new RestException(404, 'thirdparty not found');
		}

		$member = new Adherent($this->db);
		$result = $member->fetch(0, '', $thirdparty->id);
		if (!$result) {
			throw new RestException(404, 'member not found');
		}

		if (!DolibarrApi::_checkAccessToResource('adherent', $member->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		return $this->_cleanObjectDatas($member);
	}

	/**
	 * List members
	 *
	 * Get a list of members
	 *
	 * @param string    $sortfield  		Sort field
	 * @param string    $sortorder  		Sort order
	 * @param int       $limit      		Limit for list
	 * @param int       $page       		Page number
	 * @param string    $typeid     		ID of the type of member
	 * @param int		$category   		Use this param to filter list by category
	 * @param string    $sqlfilters 		Other criteria to filter answers separated by a comma.
	 *                              		Example: "(t.ref:like:'SO-%') and ((t.date_creation:<:'20160101') or (t.nature:is:NULL))"
	 * @param string	$properties			Restrict the data returned to these properties. Ignored if empty. Comma separated list of properties names
	 * @param bool      $pagination_data    If this parameter is set to true the response will include pagination data. Default value is false. Page starts from 0*
	 * @return array    					Array of member objects
	 * @phan-return array<array<string,null|int|float|string>>
	 * @phpstan-return array<array<string,null|int|float|string>>
	 *
	 * @throws	RestException	400		Error on SQL filters
	 * @throws	RestException	403		Access denied
	 * @throws	RestException	404		No Member found
	 * @throws	RestException	503		Error when retrieving Member list
	 */
	public function index($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $typeid = '', $category = 0, $sqlfilters = '', $properties = '', $pagination_data = false)
	{
		$obj_ret = array();

		if (!DolibarrApiAccess::$user->hasRight('adherent', 'lire')) {
			throw new RestException(403);
		}

		$sql = "SELECT t.rowid";
		$sql .= " FROM ".MAIN_DB_PREFIX."adherent AS t LEFT JOIN ".MAIN_DB_PREFIX."adherent_extrafields AS ef ON (ef.fk_object = t.rowid)"; // Modification VMR Global Solutions to include extrafields as search parameters in the API GET call
		if ($category > 0) {
			$sql .= ", ".MAIN_DB_PREFIX."categorie_member as c";
		}
		$sql .= ' WHERE t.entity IN ('.getEntity('adherent').')';
		if (!empty($typeid)) {
			$sql .= ' AND t.fk_adherent_type='.((int) $typeid);
		}
		// Select members of given category
		if ($category > 0) {
			$sql .= " AND c.fk_categorie = ".((int) $category);
			$sql .= " AND c.fk_member = t.rowid";
		}
		// Add sql filters
		if ($sqlfilters) {
			$errormessage = '';
			$sql .= forgeSQLFromUniversalSearchCriteria($sqlfilters, $errormessage);
			if ($errormessage) {
				throw new RestException(400, 'Error when validating parameter sqlfilters -> '.$errormessage);
			}
		}

		//this query will return total orders with the filters given
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
			$i = 0;
			$num = $this->db->num_rows($result);
			$min = min($num, ($limit <= 0 ? $num : $limit));
			while ($i < $min) {
				$obj = $this->db->fetch_object($result);
				$member = new Adherent($this->db);
				if ($member->fetch($obj->rowid)) {
					$obj_ret[] = $this->_filterObjectProperties($this->_cleanObjectDatas($member), $properties);
				}
				$i++;
			}
		} else {
			throw new RestException(503, 'Error when retrieve member list : '.$this->db->lasterror());
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
	 * Create member object
	 *
	 * @param array<string,string> $request_data   Request data
	 * @return int  ID of member
	 *
	 * @throws	RestException	403		Access denied
	 * @throws	RestException	500		Error when creating Member
	 */
	public function post($request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('adherent', 'creer')) {
			throw new RestException(403);
		}
		// Check mandatory fields
		$result = $this->_validate($request_data);

		$member = new Adherent($this->db);
		foreach ($request_data as $field => $value) {
			if ($field === 'caller') {
				// Add a mention of caller so on trigger called after action, we can filter to avoid a loop if we try to sync back again with the caller
				$member->context['caller'] = sanitizeVal($request_data['caller'], 'aZ09');
				continue;
			}

			$member->$field = $this->_checkValForAPI($field, $value, $member);
		}
		if ($member->create(DolibarrApiAccess::$user) < 0) {
			throw new RestException(500, 'Error creating member', array_merge(array($member->error), $member->errors));
		}
		return $member->id;
	}

	/**
	 * Update member
	 *
	 * @param 	int   		$id             ID of member to update
	 * @param 	array 		$request_data   Datas
	 * @phan-param ?array<string,string>	$request_data
	 * @phpstan-param ?array<string,string>	$request_data
	 * @return 	Object						Updated object
	 *
	 * @throws	RestException	403		Access denied
	 * @throws	RestException	404		Member not found
	 * @throws	RestException	500		Error when resiliating, validating, excluding, updating a Member
	 */
	public function put($id, $request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('adherent', 'creer')) {
			throw new RestException(403);
		}

		$member = new Adherent($this->db);
		$result = $member->fetch($id);
		if (!$result) {
			throw new RestException(404, 'member not found');
		}

		if (!DolibarrApi::_checkAccessToResource('member', $member->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		foreach ($request_data as $field => $value) {
			if ($field == 'id') {
				continue;
			}
			if ($field === 'caller') {
				// Add a mention of caller so on trigger called after action, we can filter to avoid a loop if we try to sync back again with the caller
				$member->context['caller'] = sanitizeVal($request_data['caller'], 'aZ09');
				continue;
			}
			if ($field == 'array_options' && is_array($value)) {
				foreach ($value as $index => $val) {
					$member->array_options[$index] = $val;
				}
				continue;
			}
			// Process the status separately because it must be updated using
			// the validate(), resiliate() and exclude() methods of the class Adherent.
			if ($field == 'statut') {
				if ($value == '0') {
					$result = $member->resiliate(DolibarrApiAccess::$user);
					if ($result < 0) {
						throw new RestException(500, 'Error when resiliating member: '.$member->error);
					}
				} elseif ($value == '1') {
					$result = $member->validate(DolibarrApiAccess::$user);
					if ($result < 0) {
						throw new RestException(500, 'Error when validating member: '.$member->error);
					}
				} elseif ($value == '-2') {
					$result = $member->exclude(DolibarrApiAccess::$user);
					if ($result < 0) {
						throw new RestException(500, 'Error when excluding member: '.$member->error);
					}
				}
			} else {
				$member->$field = $this->_checkValForAPI($field, $value, $member);
			}
		}

		// If there is no error, update() returns the number of affected rows
		// so if the update is a no op, the return value is zero.
		if ($member->update(DolibarrApiAccess::$user) >= 0) {
			return $this->get($id);
		} else {
			throw new RestException(500, 'Error when updating member: '.$member->error);
		}
	}

	/**
	 * Delete member
	 *
	 * @param int $id   member ID
	 * @return array
	 * @phan-return array<string,array{code:int,message:string}>
	 * @phpstan-return array<string,array{code:int,message:string}>
	 *
	 * @throws	RestException	403		Access denied
	 * @throws	RestException	404		Member not found
	 * @throws	RestException	500		Error when deleting a Member
	 */
	public function delete($id)
	{
		if (!DolibarrApiAccess::$user->hasRight('adherent', 'supprimer')) {
			throw new RestException(403);
		}
		$member = new Adherent($this->db);
		$result = $member->fetch($id);
		if (!$result) {
			throw new RestException(404, 'member not found');
		}

		if (!DolibarrApi::_checkAccessToResource('member', $member->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}


		$res = $member->delete(DolibarrApiAccess::$user);
		if ($res < 0) {
			throw new RestException(500, "Can't delete, error occurs");
		}

		return array(
			'success' => array(
				'code' => 200,
				'message' => 'Member deleted'
			)
		);
	}

	/**
	 * Validate fields before creating an object
	 *
	 * @param array<string,null|int|float|string>	$data   Data to validate
	 * @return array<string,null|int|float|string>			Return array with validated mandatory fields and their value
	 * @phan-return array<string,?int|?float|?string>			Return array with validated mandatory fields and their value
	 *
	 * @throws RestException
	 */
	private function _validate($data)
	{
		$member = array();

		$mandatoryfields = array(
			'morphy',
			'typeid'
		);
		foreach ($mandatoryfields as $field) {
			if (!isset($data[$field])) {
				throw new RestException(400, "$field field missing");
			}
			$member[$field] = $data[$field];
		}
		return $member;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 * Clean sensible object datas
	 *
	 * @param   Object  $object    	Object to clean
	 * @return  Object    			Object with cleaned properties
	 */
	protected function _cleanObjectDatas($object)
	{
		// phpcs:enable
		$object = parent::_cleanObjectDatas($object);

		// Remove the subscriptions because they are handled as a subresource.
		if ($object instanceof Adherent) {
			unset($object->subscriptions);
			unset($object->fk_incoterms);
			unset($object->label_incoterms);
			unset($object->location_incoterms);
			unset($object->fk_delivery_address);
			unset($object->shipping_method_id);

			unset($object->total_ht);
			unset($object->total_ttc);
			unset($object->total_tva);
			unset($object->total_localtax1);
			unset($object->total_localtax2);
		}

		if ($object instanceof AdherentType) {
			unset($object->linkedObjectsIds);
			unset($object->context);
			unset($object->canvas);
			unset($object->fk_project);
			unset($object->contact);
			unset($object->contact_id);
			unset($object->thirdparty);
			unset($object->user);
			unset($object->origin);
			unset($object->origin_id);
			unset($object->ref_ext);
			unset($object->country);
			unset($object->country_id);
			unset($object->country_code);
			unset($object->barcode_type);
			unset($object->barcode_type_code);
			unset($object->barcode_type_label);
			unset($object->barcode_type_coder);
			unset($object->mode_reglement_id);
			unset($object->cond_reglement_id);
			unset($object->cond_reglement);
			unset($object->fk_delivery_address);
			unset($object->shipping_method_id);
			unset($object->model_pdf);
			unset($object->fk_account);
			unset($object->note_public);
			unset($object->note_private);
			unset($object->fk_incoterms);
			unset($object->label_incoterms);
			unset($object->location_incoterms);
			unset($object->name);
			unset($object->lastname);
			unset($object->firstname);
			unset($object->civility_id);
			unset($object->total_ht);
			unset($object->total_tva);
			unset($object->total_localtax1);
			unset($object->total_localtax2);
			unset($object->total_ttc);
		}

		return $object;
	}

	/**
	 * List subscriptions of a member
	 *
	 * Get a list of subscriptions
	 *
	 * @param int $id ID of member
	 * @return array Array of subscription objects
	 * @phan-return Object[]
	 * @phpstan-return Object[]
	 *
	 * @url GET {id}/subscriptions
	 *
	 * @throws	RestException	403		Access denied
	 * @throws	RestException	404		Member not found
	 */
	public function getSubscriptions($id)
	{
		if (!DolibarrApiAccess::$user->hasRight('adherent', 'cotisation', 'lire')) {
			throw new RestException(403);
		}

		$member = new Adherent($this->db);
		$result = $member->fetch($id);
		if (!$result) {
			throw new RestException(404, 'member not found');
		}

		$obj_ret = array();
		foreach ($member->subscriptions as $subscription) {
			$obj_ret[] = $this->_cleanObjectDatas($subscription);
		}
		return $obj_ret;
	}

	/**
	 * Add a subscription for a member
	 *
	 * @param int		$id             ID of member
	 * @param string	$start_date     Start date {@from body} {@type timestamp}
	 * @param string	$end_date       End date {@from body} {@type timestamp}
	 * @param float		$amount         Amount (may be 0) {@from body}
	 * @param string	$label			Label {@from body}
	 * @return int  ID of subscription
	 *
	 * @url POST {id}/subscriptions
	 *
	 * @throws	RestException	403		Access denied
	 * @throws	RestException	404		Member not found
	 */
	public function createSubscription($id, $start_date, $end_date, $amount, $label = '')
	{
		if (!DolibarrApiAccess::$user->hasRight('adherent', 'cotisation', 'creer')) {
			throw new RestException(403);
		}

		$member = new Adherent($this->db);
		$result = $member->fetch($id);
		if (!$result) {
			throw new RestException(404, 'member not found');
		}

		return $member->subscription($start_date, $amount, 0, '', $label, '', '', '', $end_date);
	}

	/**
	 * Get categories for a member
	 *
	 * @param int		$id         ID of member
	 * @param string		$sortfield	Sort field
	 * @param string		$sortorder	Sort order
	 * @param int		$limit		Limit for list
	 * @param int		$page		Page number
	 *
	 * @return mixed
	 *
	 * @url GET {id}/categories
	 *
	 * @throws	RestException	403		Access denied
	 * @throws	RestException	404		Category not found
	 * @throws	RestException	503		Error when retrieving Category list
	 */
	public function getCategories($id, $sortfield = "s.rowid", $sortorder = 'ASC', $limit = 0, $page = 0)
	{
		if (!DolibarrApiAccess::$user->hasRight('categorie', 'lire')) {
			throw new RestException(403);
		}

		$categories = new Categorie($this->db);

		$result = $categories->getListForItem($id, 'member', $sortfield, $sortorder, $limit, $page);

		if ($result < 0) {
			throw new RestException(503, 'Error when retrieve category list : '.$categories->error);
		}

		return $result;
	}




	/**
	 * Get properties of a member type object
	 *
	 * Return an array with member type information
	 *
	 * @param   int     $id				ID of member type
	 * @return  Object					Object with cleaned properties
	 *
	 * @url GET /types/{id}
	 *
	 * @throws	RestException	403		Access denied
	 * @throws	RestException	404		No Member Type found
	 */
	public function getType($id)
	{
		if (!DolibarrApiAccess::$user->hasRight('adherent', 'lire')) {
			throw new RestException(403);
		}

		$membertype = new AdherentType($this->db);
		$result = $membertype->fetch($id);
		if (!$result) {
			throw new RestException(404, 'member type not found');
		}

		if (!DolibarrApi::_checkAccessToResource('member', $membertype->id, 'adherent_type')) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		return $this->_cleanObjectDatas($membertype);
	}

	/**
	 * List members types
	 *
	 * Get a list of members types
	 *
	 * @param string    $sortfield  		Sort field
	 * @param string    $sortorder  		Sort order
	 * @param int       $limit      		Limit for list
	 * @param int       $page       		Page number
	 * @param string    $sqlfilters 		Other criteria to filter answers separated by a comma. Syntax example "(t.libelle:like:'SO-%') and (t.subscription:=:'1')"
	 * @param string	$properties			Restrict the data returned to these properties. Ignored if empty. Comma separated list of properties names
	 * @param bool      $pagination_data    If this parameter is set to true the response will include pagination data. Default value is false. Page starts from 0*
	 * @return array                		Array of member type objects
	 * @phan-return array<array<string,null|int|float|string>>
	 * @phpstan-return array<array<string,null|int|float|string>>
	 *
	 * @url GET /types/
	 *
	 * @throws	RestException	403		Access denied
	 * @throws	RestException	404		No Member Type found
	 * @throws	RestException	503		Error when retrieving Member list
	 */
	public function indexType($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $sqlfilters = '', $properties = '', $pagination_data = false)
	{
		$obj_ret = array();

		if (!DolibarrApiAccess::$user->hasRight('adherent', 'lire')) {
			throw new RestException(403);
		}

		$sql = "SELECT t.rowid";
		$sql .= " FROM ".MAIN_DB_PREFIX."adherent_type AS t LEFT JOIN ".MAIN_DB_PREFIX."adherent_type_extrafields AS ef ON (ef.fk_object = t.rowid)"; // Modification VMR Global Solutions to include extrafields as search parameters in the API GET call, so we will be able to filter on extrafields
		$sql .= ' WHERE t.entity IN ('.getEntity('member_type').')';

		// Add sql filters
		if ($sqlfilters) {
			$errormessage = '';
			$sql .= forgeSQLFromUniversalSearchCriteria($sqlfilters, $errormessage);
			if ($errormessage) {
				throw new RestException(503, 'Error when validating parameter sqlfilters -> '.$errormessage);
			}
		}

		//this query will return total orders with the filters given
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
			$i = 0;
			$num = $this->db->num_rows($result);
			$min = min($num, ($limit <= 0 ? $num : $limit));
			while ($i < $min) {
				$obj = $this->db->fetch_object($result);
				$membertype = new AdherentType($this->db);
				if ($membertype->fetch($obj->rowid)) {
					$obj_ret[] = $this->_filterObjectProperties($this->_cleanObjectDatas($membertype), $properties);
				}
				$i++;
			}
		} else {
			throw new RestException(503, 'Error when retrieve member type list : '.$this->db->lasterror());
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
	 * Create member type object
	 *
	 * @param array $request_data   Request data
	 * @phan-param ?array<string,string>	$request_data
	 * @phpstan-param ?array<string,string>	$request_data
	 * @return int  ID of member type
	 *
	 * @url POST /types/
	 *
	 * @throws	RestException	403		Access denied
	 * @throws	RestException	500		Error when creating Member Type
	 */
	public function postType($request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('adherent', 'configurer')) {
			throw new RestException(403);
		}
		// Check mandatory fields
		$result = $this->_validateType($request_data);

		$membertype = new AdherentType($this->db);
		foreach ($request_data as $field => $value) {
			if ($field === 'caller') {
				// Add a mention of caller so on trigger called after action, we can filter to avoid a loop if we try to sync back again with the caller
				$membertype->context['caller'] = sanitizeVal($request_data['caller'], 'aZ09');
				continue;
			}

			$membertype->$field = $this->_checkValForAPI($field, $value, $membertype);
		}
		if ($membertype->create(DolibarrApiAccess::$user) < 0) {
			throw new RestException(500, 'Error creating member type', array_merge(array($membertype->error), $membertype->errors));
		}
		return $membertype->id;
	}

	/**
	 * Update member type
	 *
	 * @param 	int   		$id             ID of member type to update
	 * @param 	array 		$request_data   Datas
	 * @phan-param ?array<string,string>	$request_data
	 * @phpstan-param ?array<string,string>	$request_data
	 * @return 	Object						Updated object
	 *
	 * @url PUT /types/{id}
	 *
	 * @throws	RestException	403		Access denied
	 * @throws	RestException	404		No Member Type found
	 * @throws	RestException	500		Error when updating Member Type
	 */
	public function putType($id, $request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('adherent', 'configurer')) {
			throw new RestException(403);
		}

		$membertype = new AdherentType($this->db);
		$result = $membertype->fetch($id);
		if (!$result) {
			throw new RestException(404, 'member type not found');
		}

		if (!DolibarrApi::_checkAccessToResource('member', $membertype->id, 'adherent_type')) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		foreach ($request_data as $field => $value) {
			if ($field == 'id') {
				continue;
			}
			if ($field === 'caller') {
				// Add a mention of caller so on trigger called after action, we can filter to avoid a loop if we try to sync back again with the caller
				$membertype->context['caller'] = sanitizeVal($request_data['caller'], 'aZ09');
				continue;
			}
			if ($field == 'array_options' && is_array($value)) {
				foreach ($value as $index => $val) {
					$membertype->array_options[$index] = $val;
				}
				continue;
			}
			// Process the status separately because it must be updated using
			// the validate(), resiliate() and exclude() methods of the class AdherentType.
			$membertype->$field = $this->_checkValForAPI($field, $value, $membertype);
		}

		// If there is no error, update() returns the number of affected rows
		// so if the update is a no op, the return value is zero.
		if ($membertype->update(DolibarrApiAccess::$user) >= 0) {
			return $this->get($id);
		} else {
			throw new RestException(500, 'Error when updating member type: '.$membertype->error);
		}
	}

	/**
	 * Delete member type
	 *
	 * @param int $id   member type ID
	 * @return array
	 * @phan-return array<string,array{code:int,message:string}>
	 * @phpstan-return array<string,array{code:int,message:string}>
	 *
	 * @url DELETE /types/{id}
	 *
	 * @throws	RestException	403		Access denied
	 * @throws	RestException	404		No Member Type found
	 * @throws	RestException	500		Error when deleting Member Type
	 */
	public function deleteType($id)
	{
		if (!DolibarrApiAccess::$user->hasRight('adherent', 'configurer')) {
			throw new RestException(403);
		}
		$membertype = new AdherentType($this->db);
		$result = $membertype->fetch($id);
		if ($result < 1) {
			throw new RestException(404, 'member type not found');
		}

		if (!DolibarrApi::_checkAccessToResource('member', $membertype->id, 'adherent_type')) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$res = $membertype->delete(DolibarrApiAccess::$user);
		if ($res < 0) {
			throw new RestException(500, "Can't delete, error occurs");
		}

		return array(
			'success' => array(
				'code' => 200,
				'message' => 'Member type deleted'
			)
		);
	}

	/**
	 * Validate fields before creating an object
	 *
	 * @param ?array<string,null|int|float|string>	$data   Data to validate
	 * @return array<string,null|int|float|string>
	 *
	 * @throws RestException
	 */
	private function _validateType($data)
	{
		$membertype = array();

		$mandatoryfields = array('label');

		foreach ($mandatoryfields as $field) {
			if (!isset($data[$field])) {
				throw new RestException(400, "$field field missing");
			}
			$membertype[$field] = $data[$field];
		}
		return $membertype;
	}
}
