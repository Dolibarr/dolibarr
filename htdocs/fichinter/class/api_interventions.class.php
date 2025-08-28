<?php
/* Copyright (C) 2015	Jean-François Ferry		<jfefe@aternatik.fr>
 * Copyright (C) 2016	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2025	William Mead			<william@m34d.com>
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
 *       \file       htdocs/fichinter/class/api_interventions.class.php
 *       \ingroup    fichinter
 *       \brief      File of API to manage intervention
 */
use Luracast\Restler\RestException;

require_once DOL_DOCUMENT_ROOT.'/fichinter/class/fichinter.class.php';


/**
 * API class for Interventions
 *
 * @since	7.0.0	Initial implementation
 *
 * @access	protected
 * @class	DolibarrApiAccess {@requires user,external}
 */
class Interventions extends DolibarrApi
{
	/**
	 * @var string[]	Mandatory fields, checked when create and update object
	 */
	public static $FIELDS = array(
		'socid',
		'fk_project',
		'description',
	);

	/**
	 * @var string[]	Mandatory fields, checked when create and update object
	 */
	public static $FIELDSLINE = array(
		'description',
		'date',
		'duration',
	);

	/**
	 * @var Fichinter {@type fichinter}
	 */
	public $fichinter;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		global $db;
		$this->db = $db;
		$this->fichinter = new Fichinter($this->db);
	}

	/**
	 * Get an intervention
	 * Return an array with intervention information
	 *
	 * @since	7.0.0	Initial implementation
	 *
	 * @param	int			$id				ID of intervention
	 * @param	string		$ref			Ref of object
	 * @param	string		$ref_ext		External reference of object
	 * @param   int         $contact_list	0: Returned array of contacts/addresses contains all properties, 1: Return array contains just id, -1: Do not return contacts/adddesses
	 * @return	Object						Cleaned intervention object
	 *
	 * @throws		RestException
	 */
	public function get($id, $ref = '', $ref_ext = '', $contact_list = 1)
	{
		if (!DolibarrApiAccess::$user->hasRight('ficheinter', 'lire')) {
			throw new RestException(403);
		}

		$result = $this->fichinter->fetch($id, $ref, $ref_ext);
		if (!$result) {
			throw new RestException(404, 'Intervention not found');
		}

		if (!DolibarrApi::_checkAccessToResource('fichinter', $this->fichinter->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		if ($contact_list > -1) {
			// Add external contacts ids
			$tmparray = $this->fichinter->liste_contact(-1, 'external', $contact_list);
			if (is_array($tmparray)) {
				$this->fichinter->contacts_ids = $tmparray;
			}
			$tmparray = $this->fichinter->liste_contact(-1, 'internal', $contact_list);
			if (is_array($tmparray)) {
				$this->fichinter->contacts_ids_internal = $tmparray;
			}
		}

		$this->fichinter->fetchObjectLinked();

		return $this->_cleanObjectDatas($this->fichinter);
	}

	/**
	 * List interventions
	 *
	 * Get a list of interventions
	 *
	 * @since	7.0.0	Initial implementation
	 *
	 * @param	string	$sortfield				Sort field
	 * @param	string	$sortorder				Sort order
	 * @param	int		$limit					Limit for list
	 * @param	int		$page					Page number
	 * @param	string	$thirdparty_ids			Thirdparty ids to filter orders of (example '1' or '1,2,3') {@pattern /^[0-9,]*$/i}
	 * @param	string	$sqlfilters				Other criteria to filter answers separated by a comma. Syntax example "(t.ref:like:'SO-%') and (t.date_creation:<:'20160101')"
	 * @param	string	$properties				Restrict the data returned to these properties. Ignored if empty. Comma separated list of property names
	 * @param	string	$contact_type			Type of contacts: thirdparty, internal or external
	 * @param	bool	$pagination_data		If this parameter is set to true the response will include pagination data. Default value is false. Page starts from 0*
	 * @return	array							Array of order objects
	 * @phan-return array<object>
	 * @phpstan-return array<object>
	 *
	 * @throws RestException
	 */
	public function index($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $thirdparty_ids = '', $sqlfilters = '', $properties = '', $contact_type = '', $pagination_data = false)
	{
		if (!DolibarrApiAccess::$user->hasRight('ficheinter', 'lire')) {
			throw new RestException(403);
		}

		$obj_ret = array();

		// case of external user, $thirdparty_ids param is ignored and replaced by user's socid
		$socids = DolibarrApiAccess::$user->socid ?: $thirdparty_ids;

		// If the internal user must only see his customers, force searching by him
		$search_sale = 0;
		if (!DolibarrApiAccess::$user->hasRight('societe', 'client', 'voir') && !$socids) {
			$search_sale = DolibarrApiAccess::$user->id;
		}

		$sql = "SELECT t.rowid";
		$sql .= " FROM ".MAIN_DB_PREFIX."fichinter AS t LEFT JOIN ".MAIN_DB_PREFIX."fichinter_extrafields AS ef ON (ef.fk_object = t.rowid)"; // Modification VMR Global Solutions to include extrafields as search parameters in the API GET call, so we will be able to filter on extrafields
		$sql .= ' WHERE t.entity IN ('.getEntity('intervention').')';
		if ($socids) {
			$sql .= " AND t.fk_soc IN (".$this->db->sanitize($socids).")";
		}
		// Search on sale representative
		if ($search_sale && $search_sale != '-1') {
			if ($search_sale == -2) {
				$sql .= " AND NOT EXISTS (SELECT sc.fk_soc FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc WHERE sc.fk_soc = t.fk_soc)";
			} elseif ($search_sale > 0) {
				$sql .= " AND EXISTS (SELECT sc.fk_soc FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc WHERE sc.fk_soc = t.fk_soc AND sc.fk_user = ".((int) $search_sale).")";
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

		//this query will return total interventions with the filters given
		$sqlTotals = str_replace('SELECT t.rowid', 'SELECT count(t.rowid) as total', $sql);

		$sql .= $this->db->order($sortfield, $sortorder);
		if ($limit) {
			if ($page < 0) {
				$page = 0;
			}
			$offset = $limit * $page;

			$sql .= $this->db->plimit($limit + 1, $offset);
		}

		dol_syslog("API Rest request");
		$result = $this->db->query($sql);

		if ($result) {
			$num = $this->db->num_rows($result);
			$min = min($num, ($limit <= 0 ? $num : $limit));
			$i = 0;
			while ($i < $min) {
				$obj = $this->db->fetch_object($result);
				$fichinter_static = new Fichinter($this->db);
				if ($fichinter_static->fetch($obj->rowid)) {
					if ($contact_type) {
						$fichinter_static->contacts_ids = $fichinter_static->liste_contact(-1, $contact_type, 1);
					}
					$obj_ret[] = $this->_filterObjectProperties($this->_cleanObjectDatas($fichinter_static), $properties);
				}
				$i++;
			}
		} else {
			throw new RestException(503, 'Error when retrieve intervention list : '.$this->db->lasterror());
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
	 * Create an intervention
	 *
	 * @since	7.0.0	Initial implementation
	 *
	 * @param			array	$request_data	Request data
	 * @phan-param		?array<string,string>	$request_data
	 * @phpstan-param	?array<string,string>	$request_data
	 * @return			int						ID of created intervention
	 *
	 * @throws RestException
	 */
	public function post($request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('ficheinter', 'creer')) {
			throw new RestException(403, "Insuffisant rights");
		}
		// Check mandatory fields
		$result = $this->_validate($request_data);
		foreach ($request_data as $field => $value) {
			if ($field === 'caller') {
				// Add a mention of caller so on trigger called after action, we can filter to avoid a loop if we try to sync back again with the caller
				$this->fichinter->context['caller'] = sanitizeVal($request_data['caller'], 'aZ09');
				continue;
			}

			$this->fichinter->$field = $this->_checkValForAPI($field, $value, $this->fichinter);
		}

		if ($this->fichinter->create(DolibarrApiAccess::$user) < 0) {
			throw new RestException(500, "Error creating intervention", array_merge(array($this->fichinter->error), $this->fichinter->errors));
		}

		return $this->fichinter->id;
	}

	/**
	 * Update intervention general fields (won't touch lines of fichinter)
	 *
	 * @since	22.0.0	Initial implementation
	 *
	 * @param			int		$id				ID of fichinter to update
	 * @param			array	$request_data	Request data
	 * @phan-param		?array<string,string>	$request_data
	 * @phpstan-param	?array<string,string>	$request_data
	 * @return			Object					Updated object
	 *
	 * @throws RestException
	 */
	public function put($id, $request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('ficheinter', 'creer')) {
			throw new RestException(403);
		}

		$result = $this->fichinter->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Fichinter not found');
		}

		if (!DolibarrApi::_checkAccessToResource('fichinter', $this->fichinter->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}
		foreach ($request_data as $field => $value) {
			if ($field == 'id') {
				continue;
			}
			if ($field === 'caller') {
				// Add a mention of caller so on trigger called after action, we can filter to avoid a loop if we try to sync back again with the caller
				$this->fichinter->context['caller'] = sanitizeVal($request_data['caller'], 'aZ09');
				continue;
			}
			if ($field == 'array_options' && is_array($value)) {
				foreach ($value as $index => $val) {
					$this->fichinter->array_options[$index] = $this->_checkValForAPI($field, $val, $this->fichinter);
				}
				continue;
			}

			$this->fichinter->$field = $this->_checkValForAPI($field, $value, $this->fichinter);
		}

		if ($this->fichinter->update(DolibarrApiAccess::$user) > 0) {
			return $this->get($id);
		} else {
			throw new RestException(500, $this->fichinter->error);
		}
	}

	/**
	 * Get lines of intervention
	 *
	 * @param int   $id             Id of intervention
	 *
	 * @url	GET {id}/lines
	 *
	 * @return int
	 */
	/* TODO
	public function getLines($id)
	{
		if(! DolibarrApiAccess::$user->hasRight('ficheinter', 'lire')) {
			throw new RestException(403);
		}

		$result = $this->fichinter->fetch($id);
		if( ! $result ) {
			throw new RestException(404, 'Intervention not found');
		}

		if( ! DolibarrApi::_checkAccessToResource('fichinter',$this->fichinter->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}
		$this->fichinter->getLinesArray();
		$result = array();
		foreach ($this->fichinter->lines as $line) {
			array_push($result,$this->_cleanObjectDatas($line));
		}
		return $result;
	}
	*/

	/**
	 * Add a line to an intervention
	 *
	 * @since	7.0.0	Initial implementation
	 *
	 * @param			int		$id				ID of intervention to update
	 * @param			array	$request_data	Request data
	 * @phan-param		?array<string,string>	$request_data
	 * @phpstan-param	?array<string,string>	$request_data
	 *
	 * @url		POST	{id}/lines
	 *
	 * @return	int		0 if ok, <0 if ko
	 *
	 * @throws RestException
	 */
	public function postLine($id, $request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('ficheinter', 'creer')) {
			throw new RestException(403, "Insuffisant rights");
		}
		// Check mandatory fields
		$result = $this->_validateLine($request_data);

		foreach ($request_data as $field => $value) {
			if ($field === 'caller') {
				// Add a mention of caller so on trigger called after action, we can filter to avoid a loop if we try to sync back again with the caller
				$this->fichinter->context['caller'] = sanitizeVal($request_data['caller'], 'aZ09');
				continue;
			}

			$this->fichinter->$field = $this->_checkValForAPI($field, $value, $this->fichinter);
		}

		if (!$result) {
			throw new RestException(404, 'Intervention not found');
		}

		if (!DolibarrApi::_checkAccessToResource('fichinter', $this->fichinter->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$updateRes = $this->fichinter->addLine(
			DolibarrApiAccess::$user,
			$id,
			$this->fichinter->description,
			$this->fichinter->date,
			$this->fichinter->duration
		);

		if ($updateRes > 0) {
			return $updateRes;
		} else {
			throw new RestException(400, $this->fichinter->error);
		}
	}

	/**
	 * Delete an intervention
	 *
	 * @since	8.0.0	Initial implementation
	 *
	 * @param	int		$id		Intervention ID
	 * @return	array
	 * @phan-return array<string,array{code:int,message:string}>
	 * @phpstan-return array<string,array{code:int,message:string}>
	 *
	 * @throws RestException
	 */
	public function delete($id)
	{
		if (!DolibarrApiAccess::$user->hasRight('ficheinter', 'supprimer')) {
			throw new RestException(403);
		}
		$result = $this->fichinter->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Intervention not found');
		}

		if (!DolibarrApi::_checkAccessToResource('fichinter', $this->fichinter->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		if (!$this->fichinter->delete(DolibarrApiAccess::$user)) {
			throw new RestException(500, 'Error when delete intervention : '.$this->fichinter->error);
		}

		return array(
			'success' => array(
				'code' => 200,
				'message' => 'Intervention deleted'
			)
		);
	}

	/**
	 * Reopen an intervention
	 *
	 * @since	22.0.0	Initial implementation
	 *
	 * @param	int		$id		Intervention ID
	 *
	 * @url		POST	{id}/reopen
	 *
	 * @return	Object
	 *
	 * @throws	RestException
	 */
	public function reopen($id)
	{
		if (!DolibarrApiAccess::$user->hasRight('ficheinter', 'creer')) {
			throw new RestException(403, "Insuffisant rights");
		}
		$result = $this->fichinter->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Intervention not found');
		}

		if (!DolibarrApi::_checkAccessToResource('fichinter', $this->fichinter->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}
		$result = $this->fichinter->setDraft(DolibarrApiAccess::$user);
		if ($result == 0) {
			throw new RestException(304, 'Error nothing done. May be object is already set as draft');
		}
		if ($result < 0) {
			throw new RestException(500, 'Error when closing Intervention: '.$this->fichinter->error);
		}
		$this->fichinter->fetchObjectLinked();
		return $this->_cleanObjectDatas($this->fichinter);
	}

	/**
	 * Validate an intervention
	 *
	 * If you get a bad value for param notrigger check, provide this in body
	 * {
	 *   "notrigger": 0
	 * }
	 *
	 * @since	7.0.0	Initial implementation
	 *
	 * @param	int		$id				Intervention ID
	 * @param	int		$notrigger		1=Does not execute triggers, 0= execute triggers
	 *
	 * @url		POST	{id}/validate
	 *
	 * @return	Object
	 *
	 * @throws RestException
	 */
	public function validate($id, $notrigger = 0)
	{
		if (!DolibarrApiAccess::$user->hasRight('ficheinter', 'creer')) {
			throw new RestException(403, "Insuffisant rights");
		}
		$result = $this->fichinter->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Intervention not found');
		}

		if (!DolibarrApi::_checkAccessToResource('fichinter', $this->fichinter->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$result = $this->fichinter->setValid(DolibarrApiAccess::$user, $notrigger);
		if ($result == 0) {
			throw new RestException(304, 'Error nothing done. May be object is already validated');
		}
		if ($result < 0) {
			throw new RestException(500, 'Error when validating Intervention: '.$this->fichinter->error);
		}

		$this->fichinter->fetchObjectLinked();

		return $this->_cleanObjectDatas($this->fichinter);
	}

	/**
	 * Close an intervention
	 *
	 * @since	7.0.0	Initial implementation
	 *
	 * @param	int		$id		Intervention ID
	 *
	 * @url		POST	{id}/close
	 *
	 * @return	Object
	 *
	 * @throws RestException
	 */
	public function closeFichinter($id)
	{
		if (!DolibarrApiAccess::$user->hasRight('ficheinter', 'creer')) {
			throw new RestException(403, "Insuffisant rights");
		}
		$result = $this->fichinter->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Intervention not found');
		}

		if (!DolibarrApi::_checkAccessToResource('fichinter', $this->fichinter->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$result = $this->fichinter->setStatut(3);

		if ($result == 0) {
			throw new RestException(304, 'Error nothing done. May be object is already closed');
		}
		if ($result < 0) {
			throw new RestException(500, 'Error when closing Intervention: '.$this->fichinter->error);
		}

		$this->fichinter->fetchObjectLinked();

		return $this->_cleanObjectDatas($this->fichinter);
	}

	/**
	 * Validate fields before create or update object
	 *
	 * @param ?array<null|int|float|string> $data   Data to validate
	 * @return array<string,null|int|float|string>
	 *
	 * @throws RestException
	 */
	private function _validate($data)
	{
		$fichinter = array();
		foreach (Interventions::$FIELDS as $field) {
			if (!isset($data[$field])) {
				throw new RestException(400, "$field field missing");
			}
			$fichinter[$field] = $data[$field];
		}
		return $fichinter;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 * Clean sensible object data
	 *
	 * @param	Object	$object		Object to clean
	 * @return	Object				Object with cleaned properties
	 */
	protected function _cleanObjectDatas($object)
	{
		// phpcs:enable
		$object = parent::_cleanObjectDatas($object);

		unset($object->labelStatus);
		unset($object->labelStatusShort);

		return $object;
	}

	/**
	 * Validate fields before create or update object
	 *
	 * @param ?array<string,null|int|float|string>   $data   Data to validate
	 * @return array<string,null|int|float|string>          Return array with validated mandatory fields and their value
	 *
	 * @throws RestException
	 */
	private function _validateLine($data)
	{
		if ($data === null) {
			$data = array();
		}
		$fichinter = array();
		foreach (Interventions::$FIELDSLINE as $field) {
			if (!isset($data[$field])) {
				throw new RestException(400, "$field field missing");
			}
			$fichinter[$field] = $data[$field];
		}
		return $fichinter;
	}
}
