<?php
/* Copyright (C) 2024   	Florian Charlaix     <fcharlaix@easya.solutions>
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
 * API class for webhooks
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user}
 *
 */
class Webhook extends DolibarrApi
{
	/**
	 *
	 * @var array   $FIELDS     Mandatory fields, checked when we create and update the object
	 */
	public static $FIELDS = array(
		'url',
		'trigger_codes'
	);

	/**
	 * @var Target $target {@type Target}
	 */
	public $target;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		global $db;
		$this->db = $db;

		require_once DOL_DOCUMENT_ROOT.'/webhook/class/target.class.php';

		$this->target = new Target($this->db);
	}

	/**
	 * Get properties of a target object
	 *
	 * Return an array with target information
	 *
	 * @param	int		$id				Id of target to load
	 * @return  Object					Object with cleaned properties
	 *
	 * @throws	RestException
	 */
	public function get($id)
	{
		return $this->_fetch($id);
	}

	/**
	 * List targets
	 *
	 * Get a list of targets
	 *
	 * @param   string  $sortfield  Sort field
	 * @param   string  $sortorder  Sort order
	 * @param   int     $limit      Limit for list
	 * @param   int     $page       Page number
	 * @param   string  $sqlfilters Other criteria to filter answers separated by a comma. Syntax example "((t.nom:like:'TheCompany%') or (t.name_alias:like:'TheCompany%')) and (t.datec:<:'20160101')"
	 * @param   string  $properties	Restrict the data returned to these properties. Ignored if empty. Comma separated list of properties names
	 * @param bool $pagination_data If this parameter is set to true the response will include pagination data. Default value is false. Page starts from 0*
	 * @return  array               Array of target objects
	 */
	public function index($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $sqlfilters = '', $properties = '', $pagination_data = false)
	{
		$obj_ret = array();

		if (!DolibarrApiAccess::$user->hasRight('webhook', 'webhook_target', 'read')) {
			throw new RestException(403);
		}

		$sql = "SELECT t.rowid";
		$sql .= " FROM ".MAIN_DB_PREFIX."webhook_target as t";

		// Add sql filters
		if ($sqlfilters) {
			$errormessage = '';
			$sql .= forgeSQLFromUniversalSearchCriteria($sqlfilters, $errormessage);
			if ($errormessage) {
				throw new RestException(400, 'Error when validating parameter sqlfilters -> '.$errormessage);
			}
		}

		//this query will return total target with the filters given
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
				$target_static = new Target($this->db);
				if ($target_static->fetch($obj->rowid)) {
					$obj_ret[] = $this->_filterObjectProperties($this->_cleanObjectDatas($target_static), $properties);
				}
				$i++;
			}
		} else {
			throw new RestException(503, 'Error when retrieve targets : '.$this->db->lasterror());
		}
		if (!count($obj_ret)) {
			throw new RestException(404, 'Targets not found');
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
	 * Create target object
	 *
	 * @param array $request_data   Request datas
	 * @return int  ID of target
	 */
	public function post($request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('webhook', 'webhook_target', 'write')) {
			throw new RestException(403);
		}
		// Check mandatory fields
		$this->_validate($request_data);

		foreach ($request_data as $field => $value) {
			$this->target->$field = $this->_checkValForAPI($field, $value, $this->target);
		}

		if (!array_key_exists('status', $request_data)) {
			$this->target->status = 1;
		}

		if ($this->target->create(DolibarrApiAccess::$user) < 0) {
			throw new RestException(500, 'Error creating target', array_merge(array($this->target->error), $this->target->errors));
		}

		return $this->target->id;
	}

	/**
	 * Update target
	 *
	 * @param 	int   			$id             Id of target to update
	 * @param 	array 			$request_data   Datas
	 * @return 	Object|false					Updated object
	 *
	 * @throws RestException 401
	 * @throws RestException 404
	 * @throws RestException 500
	 */
	public function put($id, $request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('webhook', 'webhook_target', 'write')) {
			throw new RestException(403);
		}

		$result = $this->target->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Target not found');
		}

		foreach ($request_data as $field => $value) {
			if ($field == 'id') {
				continue;
			}
			$this->target->$field = $this->_checkValForAPI($field, $value, $this->target);
		}

		if ($this->target->update(DolibarrApiAccess::$user, 1) > 0) {
			return $this->get($id);
		} else {
			throw new RestException(500, $this->target->error);
		}
	}

	/**
	 * Delete target
	 *
	 * @param int $id   Target ID
	 * @return array
	 */
	public function delete($id)
	{
		if (!DolibarrApiAccess::$user->hasRight('webhook', 'webhook_target', 'delete')) {
			throw new RestException(403);
		}

		$result = $this->target->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Target not found');
		}

		$res = $this->target->delete(DolibarrApiAccess::$user);
		if ($res < 0) {
			throw new RestException(500, "Can't delete target, error occurs");
		}

		return array(
			'success' => array(
				'code' => 200,
				'message' => 'Target deleted'
			)
		);
	}

	/**
	 * Get the list of all available triggers
	 *
	 * @return array
	 *
	 * @url GET triggers
	 */
	public function listOfTriggers()
	{
		if (!DolibarrApiAccess::$user->hasRight('webhook', 'webhook_target', 'read')) {
			throw new RestException(403);
		}

		$triggers = array();

		$sql = "SELECT c.code, c.label FROM ".MAIN_DB_PREFIX."c_action_trigger as c ORDER BY c.rang ASC";
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$obj = $this->db->fetch_object($resql);
				$triggers[$obj->code] = $obj->label;
				$i++;
			}
		} else {
			throw new RestException(500, "Can't get list of triggers");
		}

		return $triggers;
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
		$target = array();
		foreach (self::$FIELDS as $field) {
			if (!isset($data[$field])) {
				throw new RestException(400, "$field field missing");
			}
			$target[$field] = $data[$field];
		}
		return $target;
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

		unset($object->rowid);
		unset($object->array_options);
		unset($object->array_languages);
		unset($object->contacts_ids);
		unset($object->linkedObjectsIds);
		unset($object->canvas);
		unset($object->fk_project);
		unset($object->contact_id);
		unset($object->user);
		unset($object->origin_type);
		unset($object->origin_id);
		unset($object->ref_ext);
		unset($object->statut);
		unset($object->country_id);
		unset($object->country_code);
		unset($object->state_id);
		unset($object->region_id);
		unset($object->barcode_type);
		unset($object->barcode_type_coder);
		unset($object->mode_reglement_id);
		unset($object->cond_reglement_id);
		unset($object->demand_reason_id);
		unset($object->transport_mode_id);
		unset($object->shipping_method_id);
		unset($object->shipping_method);
		unset($object->fk_multicurrency);
		unset($object->multicurrency_code);
		unset($object->multicurrency_tx);
		unset($object->multicurrency_total_ht);
		unset($object->multicurrency_total_tva);
		unset($object->multicurrency_total_ttc);
		unset($object->multicurrency_total_localtax1);
		unset($object->multicurrency_total_localtax2);
		unset($object->last_main_doc);
		unset($object->fk_account);
		unset($object->total_ht);
		unset($object->total_tva);
		unset($object->total_localtax1);
		unset($object->total_localtax2);
		unset($object->total_ttc);
		unset($object->lines);
		unset($object->actiontypecode);
		unset($object->name);
		unset($object->lastname);
		unset($object->firstname);
		unset($object->civility_id);
		unset($object->date_validation);
		unset($object->date_modification);
		unset($object->date_cloture);
		unset($object->user_author);
		unset($object->user_creation);
		unset($object->user_creation_id);
		unset($object->user_valid);
		unset($object->user_validation);
		unset($object->user_validation_id);
		unset($object->user_closing_id);
		unset($object->user_modification);
		unset($object->user_modification_id);
		unset($object->specimen);
		unset($object->extraparams);
		unset($object->product);
		unset($object->cond_reglement_supplier_id);
		unset($object->deposit_percent);
		unset($object->retained_warranty_fk_cond_reglement);
		unset($object->warehouse_id);
		unset($object->module);

		return $object;
	}

	/**
	 * Fetch properties of a target object.
	 *
	 * Return an array with target information
	 *
	 * @param    int	$rowid      Id of target party to load (Use 0 to get a specimen record, use null to use other search criteria)
	 * @param    string	$ref        Reference of target party, name (Warning, this can return several records)
	 * @return object cleaned target object
	 *
	 * @throws RestException
	 */
	private function _fetch($rowid, $ref = '')
	{
		if (!DolibarrApiAccess::$user->hasRight('webhook', 'webhook_target', 'read')) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login.'. No read permission on target.');
		}

		if ($rowid === 0) {
			$result = $this->target->initAsSpecimen();
		} else {
			$result = $this->target->fetch($rowid, $ref);
		}

		if (!$result) {
			throw new RestException(404, 'Target not found');
		}

		return $this->_cleanObjectDatas($this->target);
	}
}
