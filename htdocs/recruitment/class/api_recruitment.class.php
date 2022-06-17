<?php
/* Copyright (C) 2022 Thibault FOUCART  <support@ptibogxiv.net>
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

dol_include_once('/recruitment/class/recruitmentjobposition.class.php');
dol_include_once('/recruitment/class/recruitmentcandidature.class.php');



/**
 * \file    recruitment/class/api_recruitment.class.php
 * \ingroup recruitment
 * \brief   File for API management of recruitment.
 */

/**
 * API class for recruitment
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class Recruitment extends DolibarrApi
{
	/**
	 * @var RecruitmentJobPosition $jobposition {@type RecruitmentJobPosition}
	 */
	public $jobposition;
	/**
	 * @var RecruitmentCandidature $candidature {@type RecruitmentCandidature}
	 */
	public $candidature;


	/**
	 * Constructor
	 *
	 * @url     GET /
	 *
	 */
	public function __construct()
	{
		global $db;
		$this->db = $db;
		$this->jobposition = new RecruitmentJobPosition($this->db);
		$this->candidature = new RecruitmentCandidature($this->db);
	}


	/**
	 * Get properties of a jobposition object
	 *
	 * Return an array with jobposition informations
	 *
	 * @param 	int 	$id ID of jobposition
	 * @return 	array|mixed data without useless information
	 *
	 * @url	GET jobposition/{id}
	 *
	 * @throws RestException 401 Not allowed
	 * @throws RestException 404 Not found
	 */
	public function getJobPosition($id)
	{
		if (!DolibarrApiAccess::$user->rights->recruitment->recruitmentjobposition->read) {
			throw new RestException(401);
		}

		$result = $this->jobposition->fetch($id);
		if (!$result) {
			throw new RestException(404, 'JobPosition not found');
		}

		if (!DolibarrApi::_checkAccessToResource('recruitment', $this->jobposition->id, 'recruitment_recruitmentjobposition')) {
			throw new RestException(401, 'Access to instance id='.$this->jobposition->id.' of object not allowed for login '.DolibarrApiAccess::$user->login);
		}

		return $this->_cleanObjectDatas($this->jobposition);
	}

	/**
	 * Get properties of a candidature object
	 *
	 * Return an array with candidature informations
	 *
	 * @param 	int 	$id ID of candidature
	 * @return 	array|mixed data without useless information
	 *
	 * @url	GET candidature/{id}
	 *
	 * @throws RestException 401 Not allowed
	 * @throws RestException 404 Not found
	 */
	public function getCandidature($id)
	{
		if (!DolibarrApiAccess::$user->rights->recruitment->recruitmentjobposition->read) {
			throw new RestException(401);
		}

		$result = $this->candidature->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Candidature not found');
		}

		if (!DolibarrApi::_checkAccessToResource('recruitment', $this->candidature->id, 'recruitment_recruitmentcandidature')) {
			throw new RestException(401, 'Access to instance id='.$this->candidature->id.' of object not allowed for login '.DolibarrApiAccess::$user->login);
		}

		return $this->_cleanObjectDatas($this->candidature);
	}


	/**
	 * List jobpositions
	 *
	 * Get a list of jobpositions
	 *
	 * @param string	       $sortfield	        Sort field
	 * @param string	       $sortorder	        Sort order
	 * @param int		       $limit		        Limit for list
	 * @param int		       $page		        Page number
	 * @param string           $sqlfilters          Other criteria to filter answers separated by a comma. Syntax example "(t.ref:like:'SO-%') and (t.date_creation:<:'20160101')"
	 * @return  array                               Array of order objects
	 *
	 * @throws RestException
	 *
	 * @url	GET /jobposition/
	 */
	public function indexJobPosition($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $sqlfilters = '')
	{
		global $db, $conf;

		$obj_ret = array();
		$tmpobject = new RecruitmentJobPosition($this->db);

		if (!DolibarrApiAccess::$user->rights->recruitment->recruitmentjobposition->read) {
			throw new RestException(401);
		}

		$socid = DolibarrApiAccess::$user->socid ? DolibarrApiAccess::$user->socid : '';

		$restrictonsocid = 0; // Set to 1 if there is a field socid in table of object

		// If the internal user must only see his customers, force searching by him
		$search_sale = 0;
		if ($restrictonsocid && !DolibarrApiAccess::$user->rights->societe->client->voir && !$socid) {
			$search_sale = DolibarrApiAccess::$user->id;
		}

		$sql = "SELECT t.rowid";
		if ($restrictonsocid && (!DolibarrApiAccess::$user->rights->societe->client->voir && !$socid) || $search_sale > 0) {
			$sql .= ", sc.fk_soc, sc.fk_user"; // We need these fields in order to filter by sale (including the case where the user can only see his prospects)
		}
		$sql .= " FROM ".MAIN_DB_PREFIX.$tmpobject->table_element." as t";

		if ($restrictonsocid && (!DolibarrApiAccess::$user->rights->societe->client->voir && !$socid) || $search_sale > 0) {
			$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc"; // We need this table joined to the select in order to filter by sale
		}
		$sql .= " WHERE 1 = 1";

		// Example of use $mode
		//if ($mode == 1) $sql.= " AND s.client IN (1, 3)";
		//if ($mode == 2) $sql.= " AND s.client IN (2, 3)";

		if ($tmpobject->ismultientitymanaged) {
			$sql .= ' AND t.entity IN ('.getEntity($tmpobject->element).')';
		}
		if ($restrictonsocid && (!DolibarrApiAccess::$user->rights->societe->client->voir && !$socid) || $search_sale > 0) {
			$sql .= " AND t.fk_soc = sc.fk_soc";
		}
		if ($restrictonsocid && $socid) {
			$sql .= " AND t.fk_soc = ".((int) $socid);
		}
		if ($restrictonsocid && $search_sale > 0) {
			$sql .= " AND t.rowid = sc.fk_soc"; // Join for the needed table to filter by sale
		}
		// Insert sale filter
		if ($restrictonsocid && $search_sale > 0) {
			$sql .= " AND sc.fk_user = ".((int) $search_sale);
		}
		if ($sqlfilters) {
			$errormessage = '';
			if (!DolibarrApi::_checkFilters($sqlfilters, $errormessage)) {
				throw new RestException(503, 'Error when validating parameter sqlfilters -> '.$errormessage);
			}
			$regexstring = '\(([^:\'\(\)]+:[^:\'\(\)]+:[^\(\)]+)\)';
			$sql .= " AND (".preg_replace_callback('/'.$regexstring.'/', 'DolibarrApi::_forge_criteria_callback', $sqlfilters).")";
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
		$i = 0;
		if ($result) {
			$num = $this->db->num_rows($result);
			while ($i < $num) {
				$obj = $this->db->fetch_object($result);
				$tmp_object = new RecruitmentJobPosition($this->db);
				if ($tmp_object->fetch($obj->rowid)) {
					$obj_ret[] = $this->_cleanObjectDatas($tmp_object);
				}
				$i++;
			}
		} else {
			throw new RestException(503, 'Error when retrieving jobposition list: '.$this->db->lasterror());
		}
		if (!count($obj_ret)) {
			throw new RestException(404, 'No jobposition found');
		}
		return $obj_ret;
	}

	/**
	 * List candatures
	 *
	 * Get a list of candidatures
	 *
	 * @param string	       $sortfield	        Sort field
	 * @param string	       $sortorder	        Sort order
	 * @param int		       $limit		        Limit for list
	 * @param int		       $page		        Page number
	 * @param string           $sqlfilters          Other criteria to filter answers separated by a comma. Syntax example "(t.ref:like:'SO-%') and (t.date_creation:<:'20160101')"
	 * @return  array                               Array of order objects
	 *
	 * @throws RestException
	 *
	 * @url	GET /candidature/
	 */
	public function indexCandidature($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $sqlfilters = '')
	{
		global $db, $conf;

		$obj_ret = array();
		$tmpobject = new RecruitmentCandidature($this->db);

		if (!DolibarrApiAccess::$user->rights->recruitment->recruitmentjobposition->read) {
			throw new RestException(401);
		}

		$socid = DolibarrApiAccess::$user->socid ? DolibarrApiAccess::$user->socid : '';

		$restrictonsocid = 0; // Set to 1 if there is a field socid in table of object

		// If the internal user must only see his customers, force searching by him
		$search_sale = 0;
		if ($restrictonsocid && !DolibarrApiAccess::$user->rights->societe->client->voir && !$socid) {
			$search_sale = DolibarrApiAccess::$user->id;
		}

		$sql = "SELECT t.rowid";
		if ($restrictonsocid && (!DolibarrApiAccess::$user->rights->societe->client->voir && !$socid) || $search_sale > 0) {
			$sql .= ", sc.fk_soc, sc.fk_user"; // We need these fields in order to filter by sale (including the case where the user can only see his prospects)
		}
		$sql .= " FROM ".MAIN_DB_PREFIX.$tmpobject->table_element." as t";

		if ($restrictonsocid && (!DolibarrApiAccess::$user->rights->societe->client->voir && !$socid) || $search_sale > 0) {
			$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc"; // We need this table joined to the select in order to filter by sale
		}
		$sql .= " WHERE 1 = 1";

		// Example of use $mode
		//if ($mode == 1) $sql.= " AND s.client IN (1, 3)";
		//if ($mode == 2) $sql.= " AND s.client IN (2, 3)";

		if ($tmpobject->ismultientitymanaged) {
			$sql .= ' AND t.entity IN ('.getEntity($tmpobject->element).')';
		}
		if ($restrictonsocid && (!DolibarrApiAccess::$user->rights->societe->client->voir && !$socid) || $search_sale > 0) {
			$sql .= " AND t.fk_soc = sc.fk_soc";
		}
		if ($restrictonsocid && $socid) {
			$sql .= " AND t.fk_soc = ".((int) $socid);
		}
		if ($restrictonsocid && $search_sale > 0) {
			$sql .= " AND t.rowid = sc.fk_soc"; // Join for the needed table to filter by sale
		}
		// Insert sale filter
		if ($restrictonsocid && $search_sale > 0) {
			$sql .= " AND sc.fk_user = ".((int) $search_sale);
		}
		if ($sqlfilters) {
			$errormessage = '';
			if (!DolibarrApi::_checkFilters($sqlfilters, $errormessage)) {
				throw new RestException(503, 'Error when validating parameter sqlfilters -> '.$errormessage);
			}
			$regexstring = '\(([^:\'\(\)]+:[^:\'\(\)]+:[^\(\)]+)\)';
			$sql .= " AND (".preg_replace_callback('/'.$regexstring.'/', 'DolibarrApi::_forge_criteria_callback', $sqlfilters).")";
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
		$i = 0;
		if ($result) {
			$num = $this->db->num_rows($result);
			while ($i < $num) {
				$obj = $this->db->fetch_object($result);
				$tmp_object = new RecruitmentCandidature($this->db);
				if ($tmp_object->fetch($obj->rowid)) {
					$obj_ret[] = $this->_cleanObjectDatas($tmp_object);
				}
				$i++;
			}
		} else {
			throw new RestException(503, 'Error when retrieving candidature list: '.$this->db->lasterror());
		}
		if (!count($obj_ret)) {
			throw new RestException(404, 'No candidature found');
		}
		return $obj_ret;
	}

	/**
	 * Create jobposition object
	 *
	 * @param array $request_data   Request datas
	 * @return int  ID of jobposition
	 *
	 * @throws RestException
	 *
	 * @url	POST jobposition/
	 */
	public function postJobPosition($request_data = null)
	{
		if (!DolibarrApiAccess::$user->rights->recruitment->recruitmentjobposition->write) {
			throw new RestException(401);
		}

		// Check mandatory fields
		$result = $this->_validate($request_data);

		foreach ($request_data as $field => $value) {
			$this->jobposition->$field = $this->_checkValForAPI($field, $value, $this->jobposition);
		}

		// Clean data
		// $this->jobposition->abc = sanitizeVal($this->jobposition->abc, 'alphanohtml');

		if ($this->jobposition->create(DolibarrApiAccess::$user)<0) {
			throw new RestException(500, "Error creating jobposition", array_merge(array($this->jobposition->error), $this->jobposition->errors));
		}
		return $this->jobposition->id;
	}

	/**
	* Create candidature object
	*
	* @param array $request_data   Request datas
	* @return int  ID of candidature
	*
	* @throws RestException
	*
	* @url	POST candidature/
	*/
	public function postCandidature($request_data = null)
	{
		if (!DolibarrApiAccess::$user->rights->recruitment->recruitmentjobposition->write) {
			throw new RestException(401);
		}

		// Check mandatory fields
		$result = $this->_validate($request_data);

		foreach ($request_data as $field => $value) {
			$this->jobposition->$field = $this->_checkValForAPI($field, $value, $this->jobposition);
		}

		// Clean data
		// $this->jobposition->abc = sanitizeVal($this->jobposition->abc, 'alphanohtml');

		if ($this->candidature->create(DolibarrApiAccess::$user)<0) {
			throw new RestException(500, "Error creating candidature", array_merge(array($this->candidature->error), $this->candidature->errors));
		}
		return $this->candidature->id;
	}

	/**
	 * Update jobposition
	 *
	 * @param int   $id             Id of jobposition to update
	 * @param array $request_data   Datas
	 * @return int
	 *
	 * @throws RestException
	 *
	 * @url	PUT jobposition/{id}
	 */
	public function putJobPosition($id, $request_data = null)
	{
		if (!DolibarrApiAccess::$user->rights->recruitment->recruitmentjobposition->write) {
			throw new RestException(401);
		}

		$result = $this->jobposition->fetch($id);
		if (!$result) {
			throw new RestException(404, 'jobposition not found');
		}

		if (!DolibarrApi::_checkAccessToResource('recruitment', $this->jobposition->id, 'recruitment_recruitmentjobposition')) {
			throw new RestException(401, 'Access to instance id='.$this->jobposition->id.' of object not allowed for login '.DolibarrApiAccess::$user->login);
		}

		foreach ($request_data as $field => $value) {
			if ($field == 'id') {
				continue;
			}
			$this->jobposition->$field = $this->_checkValForAPI($field, $value, $this->jobposition);
		}

		// Clean data
		// $this->jobposition->abc = sanitizeVal($this->jobposition->abc, 'alphanohtml');

		if ($this->jobposition->update(DolibarrApiAccess::$user, false) > 0) {
			return $this->get($id);
		} else {
			throw new RestException(500, $this->jobposition->error);
		}
	}

	/**
	 * Update candidature
	 *
	 * @param int   $id             Id of candidature to update
	 * @param array $request_data   Datas
	 * @return int
	 *
	 * @throws RestException
	 *
	 * @url	PUT candidature/{id}
	 */
	public function putCandidature($id, $request_data = null)
	{
		if (!DolibarrApiAccess::$user->rights->recruitment->recruitmentjobposition->write) {
			throw new RestException(401);
		}

		$result = $this->candidature->fetch($id);
		if (!$result) {
			throw new RestException(404, 'candidature not found');
		}

		if (!DolibarrApi::_checkAccessToResource('recruitment', $this->candidature->id, 'recruitment_recruitmentcandidature')) {
			throw new RestException(401, 'Access to instance id='.$this->candidature->id.' of object not allowed for login '.DolibarrApiAccess::$user->login);
		}

		foreach ($request_data as $field => $value) {
			if ($field == 'id') {
				continue;
			}
			$this->candidature->$field = $this->_checkValForAPI($field, $value, $this->candidature);
		}

		// Clean data
		// $this->jobposition->abc = sanitizeVal($this->jobposition->abc, 'alphanohtml');

		if ($this->candidature->update(DolibarrApiAccess::$user, false) > 0) {
			return $this->get($id);
		} else {
			throw new RestException(500, $this->candidature->error);
		}
	}


	/**
	 * Delete jobposition
	 *
	 * @param   int     $id   jobposition ID
	 * @return  array
	 *
	 * @throws RestException
	 *
	 * @url	DELETE jobposition/{id}
	 */
	public function deleteJobPosition($id)
	{
		if (!DolibarrApiAccess::$user->rights->recruitment->recruitmentjobposition->delete) {
			throw new RestException(401);
		}
		$result = $this->jobposition->fetch($id);
		if (!$result) {
			throw new RestException(404, 'jobposition not found');
		}

		if (!DolibarrApi::_checkAccessToResource('recruitment', $this->jobposition->id, 'recruitment_recruitmentjobposition')) {
			throw new RestException(401, 'Access to instance id='.$this->jobposition->id.' of object not allowed for login '.DolibarrApiAccess::$user->login);
		}

		if (!$this->jobposition->delete(DolibarrApiAccess::$user)) {
			throw new RestException(500, 'Error when deleting jobposition : '.$this->jobposition->error);
		}

		return array(
			'success' => array(
				'code' => 200,
				'message' => 'jobposition deleted'
			)
		);
	}

	/**
	 * Delete candidature
	 *
	 * @param   int     $id   candidature ID
	 * @return  array
	 *
	 * @throws RestException
	 *
	 * @url	DELETE candidature/{id}
	 */
	public function deleteCandidature($id)
	{
		if (!DolibarrApiAccess::$user->rights->recruitment->recruitmentjobposition->delete) {
			throw new RestException(401);
		}
		$result = $this->candidature->fetch($id);
		if (!$result) {
			throw new RestException(404, 'candidature not found');
		}

		if (!DolibarrApi::_checkAccessToResource('recruitment', $this->candidature->id, 'recruitment_recruitmentcandidature')) {
			throw new RestException(401, 'Access to instance id='.$this->candidature->id.' of object not allowed for login '.DolibarrApiAccess::$user->login);
		}

		if (!$this->candidature->delete(DolibarrApiAccess::$user)) {
			throw new RestException(500, 'Error when deleting candidature : '.$this->candidature->error);
		}

		return array(
			'success' => array(
				'code' => 200,
				'message' => 'candidature deleted'
			)
		);
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 * Clean sensible object datas
	 *
	 * @param   Object  $object     Object to clean
	 * @return  Object              Object with cleaned properties
	 */
	protected function _cleanObjectDatas($object)
	{
		// phpcs:enable
		$object = parent::_cleanObjectDatas($object);

		unset($object->rowid);
		unset($object->canvas);

		/*unset($object->name);
		unset($object->lastname);
		unset($object->firstname);
		unset($object->civility_id);
		unset($object->statut);
		unset($object->state);
		unset($object->state_id);
		unset($object->state_code);
		unset($object->region);
		unset($object->region_code);
		unset($object->country);
		unset($object->country_id);
		unset($object->country_code);
		unset($object->barcode_type);
		unset($object->barcode_type_code);
		unset($object->barcode_type_label);
		unset($object->barcode_type_coder);
		unset($object->total_ht);
		unset($object->total_tva);
		unset($object->total_localtax1);
		unset($object->total_localtax2);
		unset($object->total_ttc);
		unset($object->fk_account);
		unset($object->comments);
		unset($object->note);
		unset($object->mode_reglement_id);
		unset($object->cond_reglement_id);
		unset($object->cond_reglement);
		unset($object->shipping_method_id);
		unset($object->fk_incoterms);
		unset($object->label_incoterms);
		unset($object->location_incoterms);
		*/

		// If object has lines, remove $db property
		if (isset($object->lines) && is_array($object->lines) && count($object->lines) > 0) {
			$nboflines = count($object->lines);
			for ($i = 0; $i < $nboflines; $i++) {
				$this->_cleanObjectDatas($object->lines[$i]);

				unset($object->lines[$i]->lines);
				unset($object->lines[$i]->note);
			}
		}

		return $object;
	}

	/**
	 * Validate fields before create or update object
	 *
	 * @param	array		$data   Array of data to validate
	 * @return	array
	 *
	 * @throws	RestException
	 */
	private function _validate($data)
	{
		$jobposition = array();
		foreach ($this->jobposition->fields as $field => $propfield) {
			if (in_array($field, array('rowid', 'entity', 'date_creation', 'tms', 'fk_user_creat')) || $propfield['notnull'] != 1) {
				continue; // Not a mandatory field
			}
			if (!isset($data[$field])) {
				throw new RestException(400, "$field field missing");
			}
			$jobposition[$field] = $data[$field];
		}
		return $jobposition;
	}
}
