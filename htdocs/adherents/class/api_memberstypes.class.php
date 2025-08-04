<?php
/* Copyright (C) 2017	Regis Houssin	<regis.houssin@inodbox.com>
 * Copyright (C) 2025	MDW				<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2025   Frédéric France	<frederic.france@free.fr>
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

require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent_type.class.php';

/**
 * API class for members types
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class MembersTypes extends DolibarrApi
{
	/**
	 * @var string[]	Mandatory fields, checked when create and update object
	 */
	public static $FIELDS = array(
		'label',
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
	 * Get properties of a member type object
	 *
	 * Return an array with member type information
	 *
	 * @param   int     $id				ID of member type
	 * @return  Object					Object with cleaned properties
	 *
	 * @throws  RestException
	 */
	public function get($id)
	{
		if (!DolibarrApiAccess::$user->hasRight('adherent', 'lire')) {
			throw new RestException(401);
		}

		$membertype = new AdherentType($this->db);
		$result = $membertype->fetch($id);
		if (!$result) {
			throw new RestException(404, 'member type not found');
		}

		if (!DolibarrApi::_checkAccessToResource('member', $membertype->id, 'adherent_type')) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		return $this->_cleanObjectDatas($membertype);
	}

	/**
	 * List members types
	 *
	 * Get a list of members types
	 *
	 * @param string    $sortfield  Sort field
	 * @param string    $sortorder  Sort order
	 * @param int       $limit      Limit for list
	 * @param int       $page       Page number
	 * @param string    $sqlfilters Other criteria to filter answers separated by a comma. Syntax example "(t.libelle:like:'SO-%') and (t.subscription:=:'1')"
	 * @param string    $properties	Restrict the data returned to these properties. Ignored if empty. Comma separated list of properties names
	 * @return array                Array of member type objects
	 * @phan-return AdherentType[]
	 * @phpstan-return AdherentType[]
	 *
	 * @throws RestException
	 */
	public function index($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $sqlfilters = '', $properties = '')
	{
		$obj_ret = array();

		if (!DolibarrApiAccess::$user->hasRight('adherent', 'lire')) {
			throw new RestException(401);
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

		return $obj_ret;
	}

	/**
	 * Create member type object
	 *
	 * @param array	$request_data   Request data
	 * @phan-param ?array<string,string>    $request_data
	 * @phpstan-param ?array<string,string> $request_data
	 * @return int  ID of member type
	 */
	public function post($request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('adherent', 'configurer')) {
			throw new RestException(401);
		}
		// Check mandatory fields. Throw exception on error.
		$this->_validate($request_data);

		$membertype = new AdherentType($this->db);
		foreach ($request_data as $field => $value) {
			if ($field === 'caller') {
				// Add a mention of caller so on trigger called after action, we can filter to avoid a loop if we try to sync back again with the caller
				$membertype->context['caller'] = $request_data['caller'];
				continue;
			}

			$membertype->$field = $value;
		}
		if ($membertype->create(DolibarrApiAccess::$user) < 0) {
			throw new RestException(500, 'Error creating member type', array_merge(array($membertype->error), $membertype->errors));
		}
		return $membertype->id;
	}

	/**
	 * Update member type
	 *
	 * @param int   $id             ID of member type to update
	 * @param array $request_data   Datas
	 * @phan-param ?array<string,string>    $request_data
	 * @phpstan-param ?array<string,string> $request_data
	 * @return Object
	 */
	public function put($id, $request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('adherent', 'configurer')) {
			throw new RestException(401);
		}

		$membertype = new AdherentType($this->db);
		$result = $membertype->fetch($id);
		if (!$result) {
			throw new RestException(404, 'member type not found');
		}

		if (!DolibarrApi::_checkAccessToResource('member', $membertype->id, 'adherent_type')) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		foreach ($request_data as $field => $value) {
			if ($field == 'id') {
				continue;
			}
			if ($field === 'caller') {
				// Add a mention of caller so on trigger called after action, we can filter to avoid a loop if we try to sync back again with the caller
				$membertype->context['caller'] = $request_data['caller'];
				continue;
			}
			if ($field == 'array_options' && is_array($value)) {
				foreach ($value as $index => $val) {
					$membertype->array_options[$index] = $this->_checkValForAPI($field, $val, $membertype);
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
	 */
	public function delete($id)
	{
		if (!DolibarrApiAccess::$user->hasRight('adherent', 'configurer')) {
			throw new RestException(401);
		}
		$membertype = new AdherentType($this->db);
		$result = $membertype->fetch($id);
		if (!$result) {
			throw new RestException(404, 'member type not found');
		}

		if (!DolibarrApi::_checkAccessToResource('member', $membertype->id, 'adherent_type')) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$res = $membertype->delete(DolibarrApiAccess::$user);
		if ($res < 0) {
			throw new RestException(500, "Can't delete, error occurs");
		} elseif ($res == 0) {
			throw new RestException(409, "Can't delete, that product is probably used");
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
	 * @param ?array<null|int|float|string> $data   Data to validate
	 * @return array<string,null|int|float|string>
	 *
	 * @throws RestException
	 */
	private function _validate($data)
	{
		$membertype = array();
		foreach (MembersTypes::$FIELDS as $field) {
			if (!isset($data[$field])) {
				throw new RestException(400, "$field field missing");
			}
			$membertype[$field] = $data[$field];
		}
		return $membertype;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 * Clean sensible object datas
	 *
	 * @param	Object  $object		Object to clean
	 * @return	Object				Object with cleaned properties
	 */
	protected function _cleanObjectDatas($object)
	{
		// phpcs:enable
		$object = parent::_cleanObjectDatas($object);

		unset($object->array_options);
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

		return $object;
	}
}
