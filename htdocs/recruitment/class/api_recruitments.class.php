<?php
/* Copyright (C) 2022 Thibault FOUCART  <support@ptibogxiv.net>
 * Copyright (C) 2024-2025	MDW			<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2025       Frédéric France         <frederic.france@free.fr>
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
dol_include_once('/societe/class/societe.class.php');



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
class Recruitments extends DolibarrApi
{
	/**
	 * @var RecruitmentJobPosition {@type RecruitmentJobPosition}
	 */
	public $jobposition;
	/**
	 * @var RecruitmentCandidature {@type RecruitmentCandidature}
	 */
	public $candidature;


	/**
	 * Constructor
	 *
	 * @url     GET /
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
	 * Return an array with jobposition information
	 *
	 * @param	int			$id		ID of jobposition
	 * @return  Object				Object with cleaned properties
	 *
	 * @url	GET jobposition/{id}
	 *
	 * @throws RestException 401 Not allowed
	 * @throws RestException 404 Not found
	 */
	public function getJobPosition($id)
	{
		if (!DolibarrApiAccess::$user->hasRight('recruitment', 'recruitmentjobposition', 'read')) {
			throw new RestException(403);
		}

		$result = $this->jobposition->fetch($id);
		if (!$result) {
			throw new RestException(404, 'JobPosition not found');
		}

		if (!DolibarrApi::_checkAccessToResource('recruitment', $this->jobposition->id, 'recruitment_recruitmentjobposition')) {
			throw new RestException(403, 'Access to instance id='.$this->jobposition->id.' of object not allowed for login '.DolibarrApiAccess::$user->login);
		}

		// Check access to the thirdparty of the job position, if there is one
		$this->_checkAccessToThirdpartyOfJobPosition($this->jobposition->fk_soc);

		return $this->_cleanObjectDatas($this->jobposition);
	}

	/**
	 * Get properties of a candidature object
	 *
	 * Return an array with candidature information
	 *
	 * @param	int		$id		ID of candidature
	 * @return  Object          Object with cleaned properties
	 *
	 * @url	GET candidature/{id}
	 *
	 * @throws RestException 401 Not allowed
	 * @throws RestException 404 Not found
	 */
	public function getCandidature($id)
	{
		if (!DolibarrApiAccess::$user->hasRight('recruitment', 'recruitmentjobposition', 'read')) {
			throw new RestException(403);
		}

		$result = $this->candidature->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Candidature not found');
		}

		if (!DolibarrApi::_checkAccessToResource('recruitment', $this->candidature->id, 'recruitment_recruitmentcandidature')) {
			throw new RestException(403, 'Access to instance id='.$this->candidature->id.' of object not allowed for login '.DolibarrApiAccess::$user->login);
		}

		// Check access to the job position, and so to its thirdparty, the candidature is linked to
		$this->_checkAccessToJobPositionOfCandidature($this->candidature->fk_recruitmentjobposition);

		return $this->_cleanObjectDatas($this->candidature);
	}

	/**
	 * List jobpositions
	 *
	 * Get a list of jobpositions
	 *
	 * @param string		   $sortfield			Sort field
	 * @param string		   $sortorder			Sort order
	 * @param int			   $limit				Limit for list
	 * @param int			   $page				Page number
	 * @param string           $sqlfilters          Other criteria to filter answers separated by a comma. Syntax example "(t.ref:like:'SO-%') and (t.date_creation:>:'20160101')"
	 * @param string    $properties	Restrict the data returned to these properties. Ignored if empty. Comma separated list of properties names
	 * @param bool             $pagination_data     If this parameter is set to true the response will include pagination data. Default value is false. Page starts from 0*
	 * @return  array                               Array of order objects
	 * @phan-return array<string,mixed>
	 * @phpstan-return array<string,mixed>
	 *
	 * @throws RestException
	 *
	 * @url	GET /jobposition/
	 */
	public function indexJobPosition($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $sqlfilters = '', $properties = '', $pagination_data = false)
	{
		$obj_ret = array();
		$tmpobject = new RecruitmentJobPosition($this->db);

		if (!DolibarrApiAccess::$user->hasRight('recruitment', 'recruitmentjobposition', 'read')) {
			throw new RestException(403);
		}

		$socid = DolibarrApiAccess::$user->socid ?: 0;

		$restrictonsocid = 1; // RecruitmentJobPosition::$fields has a 'fk_soc' field

		// If the internal user must only see his customers, force searching by him
		$search_sale = 0;
		if ($restrictonsocid && !DolibarrApiAccess::$user->hasRight('societe', 'client', 'voir') && !$socid) {
			$search_sale = DolibarrApiAccess::$user->id;
		}

		$sql = "SELECT t.rowid";
		$sql .= " FROM ".MAIN_DB_PREFIX.$tmpobject->table_element." AS t";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX.$tmpobject->table_element."_extrafields AS ef ON (ef.fk_object = t.rowid)"; // Modification VMR Global Solutions to include extrafields as search parameters in the API GET call, so we will be able to filter on extrafields
		$sql .= " WHERE 1 = 1";
		if ($tmpobject->ismultientitymanaged) {
			$sql .= ' AND t.entity IN ('.getEntity($tmpobject->element).')';
		}
		if ($restrictonsocid && $socid) {
			$sql .= " AND t.fk_soc = ".((int) $socid);
		}
		// Search on sale representative
		if ($search_sale && $search_sale != '-1') {
			if ($search_sale == -2) {
				$sql .= " AND NOT EXISTS (SELECT sc.fk_soc FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc WHERE sc.fk_soc = t.fk_soc)";
			} elseif ($search_sale > 0) {
				// fk_soc is optional on RecruitmentJobPosition (a position isn't necessarily tied to a
				// customer's site), so a row with no linked thirdparty must still be visible to the sales
				// rep rather than being hidden by an EXISTS check that can never match a null fk_soc.
				$sql .= " AND (t.fk_soc IS NULL OR EXISTS (SELECT sc.fk_soc FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc WHERE sc.fk_soc = t.fk_soc AND sc.fk_user = ".((int) $search_sale)."))";
			}
		}
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
		$i = 0;
		if ($result) {
			$num = $this->db->num_rows($result);
			$min = min($num, ($limit <= 0 ? $num : $limit));
			while ($i < $min) {
				$obj = $this->db->fetch_object($result);
				$tmp_object = new RecruitmentJobPosition($this->db);
				if ($tmp_object->fetch($obj->rowid)) {
					$obj_ret[] = $this->_filterObjectProperties($this->_cleanObjectDatas($tmp_object), $properties);
				}
				$i++;
			}
		} else {
			throw new RestException(503, 'Error when retrieving jobposition list: '.$this->db->lasterror());
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
	 * List candatures
	 *
	 * Get a list of candidatures
	 *
	 * @param string		   $sortfield			Sort field
	 * @param string		   $sortorder			Sort order
	 * @param int			   $limit				Limit for list
	 * @param int			   $page				Page number
	 * @param string           $sqlfilters          Other criteria to filter answers separated by a comma. Syntax example "(t.ref:like:'SO-%') and (t.date_creation:>:'20160101')"
	 * @param string		   $properties			Restrict the data returned to these properties. Ignored if empty. Comma separated list of properties names
	 * @param bool             $pagination_data     If this parameter is set to true the response will include pagination data. Default value is false. Page starts from 0*
	 * @return  array                               Array of order objects
	 * @phan-return array<string,mixed>
	 * @phpstan-return array<string,mixed>
	 *
	 * @throws RestException
	 *
	 * @url	GET /candidature/
	 */
	public function indexCandidature($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $sqlfilters = '', $properties = '', $pagination_data = false)
	{
		global $db, $conf;

		$obj_ret = array();
		$tmpobject = new RecruitmentCandidature($this->db);

		if (!DolibarrApiAccess::$user->hasRight('recruitment', 'recruitmentjobposition', 'read')) {
			throw new RestException(403);
		}

		$socid = DolibarrApiAccess::$user->socid ?: 0;

		$restrictonsocid = 0; // Set to 1 if there is a field socid in table of object

		// If the internal user must only see his customers, force searching by him
		$search_sale = 0;
		if ($restrictonsocid && !DolibarrApiAccess::$user->hasRight('societe', 'client', 'voir') && !$socid) {
			$search_sale = DolibarrApiAccess::$user->id;
		}

		$sql = "SELECT t.rowid";
		$sql .= " FROM ".MAIN_DB_PREFIX.$tmpobject->table_element." AS t";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX.$tmpobject->table_element."_extrafields AS ef ON (ef.fk_object = t.rowid)"; // Modification VMR Global Solutions to include extrafields as search parameters in the API GET call, so we will be able to filter on extrafields
		$sql .= " WHERE 1 = 1";
		if ($tmpobject->ismultientitymanaged) {
			$sql .= ' AND t.entity IN ('.getEntity($tmpobject->element).')';
		}
		if ($restrictonsocid && $socid) {
			$sql .= " AND t.fk_soc = ".((int) $socid);
		}
		// Search on sale representative
		if ($search_sale && $search_sale != '-1') {
			if ($search_sale == -2) {
				$sql .= " AND NOT EXISTS (SELECT sc.fk_soc FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc WHERE sc.fk_soc = t.fk_soc)";
			} elseif ($search_sale > 0) {
				$sql .= " AND EXISTS (SELECT sc.fk_soc FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc WHERE sc.fk_soc = t.fk_soc AND sc.fk_user = ".((int) $search_sale).")";
			}
		}
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
		$i = 0;
		if ($result) {
			$num = $this->db->num_rows($result);
			$min = min($num, ($limit <= 0 ? $num : $limit));
			while ($i < $min) {
				$obj = $this->db->fetch_object($result);
				$tmp_object = new RecruitmentCandidature($this->db);
				if ($tmp_object->fetch($obj->rowid)) {
					$obj_ret[] = $this->_filterObjectProperties($this->_cleanObjectDatas($tmp_object), $properties);
				}
				$i++;
			}
		} else {
			throw new RestException(503, 'Error when retrieving candidature list: '.$this->db->lasterror());
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
	 * Create jobposition object
	 *
	 * @param array $request_data   Request data
	 * @phan-param ?array<string,mixed> $request_data
	 * @phpstan-param ?array<string,mixed> $request_data
	 * @return int  ID of jobposition
	 *
	 * @throws RestException
	 *
	 * @url	POST jobposition/
	 */
	public function postJobPosition($request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('recruitment', 'recruitmentjobposition', 'write')) {
			throw new RestException(403);
		}

		// Check mandatory fields
		$result = $this->_validate($request_data, $this->jobposition);

		foreach ($request_data as $field => $value) {
			if ($field === 'caller') {
				// Add a mention of caller so on trigger called after action, we can filter to avoid a loop if we try to sync back again with the caller
				$this->jobposition->context['caller'] = sanitizeVal($request_data['caller'], 'aZ09');
				continue;
			}

			$this->jobposition->$field = $this->_checkValForAPI($field, $value, $this->jobposition);
		}

		// Clean data
		// $this->jobposition->abc = sanitizeVal($this->jobposition->abc, 'alphanohtml');

		// Check access to the thirdparty the job position is located at, if there is one
		$this->_checkAccessToThirdpartyOfJobPosition($this->jobposition->fk_soc);

		if ($this->jobposition->create(DolibarrApiAccess::$user) < 0) {
			throw new RestException(500, "Error creating jobposition", array_merge(array($this->jobposition->error), $this->jobposition->errors));
		}
		return $this->jobposition->id;
	}

	/**
	 * Create candidature object
	 *
	 * @param array $request_data   Request data
	 * @phan-param ?array<string,string> $request_data
	 * @phpstan-param ?array<string,string> $request_data
	 * @return int  ID of candidature
	 *
	 * @throws RestException
	 *
	 * @url	POST candidature/
	 */
	public function postCandidature($request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('recruitment', 'recruitmentjobposition', 'write')) {
			throw new RestException(403);
		}

		// Check mandatory fields. Validate against the candidature model so the API
		// rejects with the actual missing candidature fields (e.g. email) instead of
		// the unrelated job position fields (see issue #38429).
		$result = $this->_validate($request_data, $this->candidature);

		foreach ($request_data as $field => $value) {
			if ($field === 'caller') {
				// Add a mention of caller so on trigger called after action, we can filter to avoid a loop if we try to sync back again with the caller
				$this->candidature->context['caller'] = sanitizeVal($request_data['caller'], 'aZ09');
				continue;
			}

			$this->candidature->$field = $this->_checkValForAPI($field, $value, $this->candidature);
		}

		// Clean data
		// $this->jobposition->abc = sanitizeVal($this->jobposition->abc, 'alphanohtml');

		// Check access to the job position, and so to its thirdparty, the candidature is linked to
		$this->_checkAccessToJobPositionOfCandidature($this->candidature->fk_recruitmentjobposition);

		if ($this->candidature->create(DolibarrApiAccess::$user) < 0) {
			throw new RestException(500, "Error creating candidature", array_merge(array($this->candidature->error), $this->candidature->errors));
		}
		return $this->candidature->id;
	}

	/**
	 * Update jobposition
	 *
	 * @param int   $id						Id of jobposition to update
	 * @param array $request_data			Data
	 * @phan-param ?array<string,mixed> $request_data
	 * @phpstan-param ?array<string,mixed> $request_data
	 * @return		Object					Object with cleaned properties
	 *
	 * @throws RestException
	 *
	 * @url	PUT jobposition/{id}
	 */
	public function putJobPosition($id, $request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('recruitment', 'recruitmentjobposition', 'write')) {
			throw new RestException(403);
		}

		$result = $this->jobposition->fetch($id);
		if (!$result) {
			throw new RestException(404, 'jobposition not found');
		}

		if (!DolibarrApi::_checkAccessToResource('recruitment', $this->jobposition->id, 'recruitment_recruitmentjobposition')) {
			throw new RestException(403, 'Access to instance id='.$this->jobposition->id.' of object not allowed for login '.DolibarrApiAccess::$user->login);
		}

		// Check access to the current thirdparty of the job position, if there is one
		$oldfk_soc = $this->jobposition->fk_soc;
		$this->_checkAccessToThirdpartyOfJobPosition($oldfk_soc);

		foreach ($request_data as $field => $value) {
			if ($field == 'id') {
				continue;
			}
			if ($field === 'caller') {
				// Add a mention of caller so on trigger called after action, we can filter to avoid a loop if we try to sync back again with the caller
				$this->jobposition->context['caller'] = sanitizeVal($request_data['caller'], 'aZ09');
				continue;
			}

			$this->jobposition->$field = $this->_checkValForAPI($field, $value, $this->jobposition);
		}

		// Check access to the new thirdparty when the thirdparty is changed
		if ($this->jobposition->fk_soc != $oldfk_soc) {
			$this->_checkAccessToThirdpartyOfJobPosition($this->jobposition->fk_soc);
		}

		// Clean data
		// $this->jobposition->abc = sanitizeVal($this->jobposition->abc, 'alphanohtml');

		if ($this->jobposition->update(DolibarrApiAccess::$user, 0) > 0) {
			return $this->getJobPosition($id);
		} else {
			throw new RestException(500, $this->jobposition->error);
		}
	}

	/**
	 * Update candidature
	 *
	 * @param	int		$id             Id of candidature to update
	 * @param	array	$request_data   Datas
	 * @phan-param ?array<string,mixed> $request_data
	 * @phpstan-param ?array<string,mixed> $request_data
	 * @return  Object					Object with cleaned properties
	 *
	 * @throws RestException
	 *
	 * @url	PUT candidature/{id}
	 */
	public function putCandidature($id, $request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('recruitment', 'recruitmentjobposition', 'write')) {
			throw new RestException(403);
		}

		$result = $this->candidature->fetch($id);
		if (!$result) {
			throw new RestException(404, 'candidature not found');
		}

		if (!DolibarrApi::_checkAccessToResource('recruitment', $this->candidature->id, 'recruitment_recruitmentcandidature')) {
			throw new RestException(403, 'Access to instance id='.$this->candidature->id.' of object not allowed for login '.DolibarrApiAccess::$user->login);
		}

		// Check access to the job position, and so to its thirdparty, the candidature is currently linked to
		$oldfk_recruitmentjobposition = $this->candidature->fk_recruitmentjobposition;
		$this->_checkAccessToJobPositionOfCandidature($oldfk_recruitmentjobposition);

		foreach ($request_data as $field => $value) {
			if ($field == 'id') {
				continue;
			}
			if ($field === 'caller') {
				// Add a mention of caller so on trigger called after action, we can filter to avoid a loop if we try to sync back again with the caller
				$this->candidature->context['caller'] = sanitizeVal($request_data['caller'], 'aZ09');
				continue;
			}

			$this->candidature->$field = $this->_checkValForAPI($field, $value, $this->candidature);
		}

		// Check access to the new job position when the job position is changed
		if ($this->candidature->fk_recruitmentjobposition != $oldfk_recruitmentjobposition) {
			$this->_checkAccessToJobPositionOfCandidature($this->candidature->fk_recruitmentjobposition);
		}

		// Clean data
		// $this->jobposition->abc = sanitizeVal($this->jobposition->abc, 'alphanohtml');

		if ($this->candidature->update(DolibarrApiAccess::$user, 0) > 0) {
			return $this->getCandidature($id);
		} else {
			throw new RestException(500, $this->candidature->error);
		}
	}


	/**
	 * Delete jobposition
	 *
	 * @param   int     $id   jobposition ID
	 * @return  array
	 * @phan-return array{success:array{code:int,message:string}}
	 * @phpstan-return array{success:array{code:int,message:string}}
	 *
	 * @throws RestException
	 *
	 * @url	DELETE jobposition/{id}
	 */
	public function deleteJobPosition($id)
	{
		if (!DolibarrApiAccess::$user->hasRight('recruitment', 'recruitmentjobposition', 'delete')) {
			throw new RestException(403);
		}
		$result = $this->jobposition->fetch($id);
		if (!$result) {
			throw new RestException(404, 'jobposition not found');
		}

		if (!DolibarrApi::_checkAccessToResource('recruitment', $this->jobposition->id, 'recruitment_recruitmentjobposition')) {
			throw new RestException(403, 'Access to instance id='.$this->jobposition->id.' of object not allowed for login '.DolibarrApiAccess::$user->login);
		}

		// Check access to the thirdparty of the job position, if there is one
		$this->_checkAccessToThirdpartyOfJobPosition($this->jobposition->fk_soc);

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
	 * @phan-return array{success:array{code:int,message:string}}
	 * @phpstan-return array{success:array{code:int,message:string}}
	 *
	 * @throws RestException
	 *
	 * @url	DELETE candidature/{id}
	 */
	public function deleteCandidature($id)
	{
		if (!DolibarrApiAccess::$user->hasRight('recruitment', 'recruitmentjobposition', 'delete')) {
			throw new RestException(403);
		}
		$result = $this->candidature->fetch($id);
		if (!$result) {
			throw new RestException(404, 'candidature not found');
		}

		if (!DolibarrApi::_checkAccessToResource('recruitment', $this->candidature->id, 'recruitment_recruitmentcandidature')) {
			throw new RestException(403, 'Access to instance id='.$this->candidature->id.' of object not allowed for login '.DolibarrApiAccess::$user->login);
		}

		// Check access to the job position, and so to its thirdparty, the candidature is linked to
		$this->_checkAccessToJobPositionOfCandidature($this->candidature->fk_recruitmentjobposition);

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
	 * @phpstan-template T
	 *
	 * @param   Object  $object     Object to clean
	 * @return  Object              Object with cleaned properties
	 * @phpstan-param T $object
	 * @phpstan-return T
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
	 * Check that the current API user has access to the thirdparty of a job position.
	 * External users are limited to their own thirdparty, sales representatives without
	 * the "societe > client > voir" right are limited to the thirdparties they are assigned to.
	 * A job position not linked to a thirdparty stays accessible to internal users.
	 *
	 * @param   int     $socid      Id of thirdparty of the job position (0 if not linked to a thirdparty)
	 * @return  void
	 *
	 * @throws RestException 403 Access to object not allowed
	 * @throws RestException 404 Thirdparty not found
	 */
	private function _checkAccessToThirdpartyOfJobPosition($socid)
	{
		if (empty($socid)) {
			// A job position is not necessarily located at a thirdparty: it stays visible to internal
			// users, but a record not linked to any thirdparty can not belong to an external user
			if (!empty(DolibarrApiAccess::$user->socid)) {
				throw new RestException(403, 'Access to object not linked to any thirdparty not allowed for external login '.DolibarrApiAccess::$user->login);
			}
			return;
		}

		$thirdpartytmp = new Societe($this->db);
		$thirdparty_result = $thirdpartytmp->fetch((int) $socid);
		if ($thirdparty_result < 1) {
			throw new RestException(404, 'Thirdparty with id='.((int) $socid).' not found or not allowed');
		}
		if (!DolibarrApi::_checkAccessToResource('societe', $thirdpartytmp->id)) {
			throw new RestException(403, 'Access to thirdparty id='.((int) $socid).' of object not allowed for login '.DolibarrApiAccess::$user->login);
		}
	}

	/**
	 * Check that the current API user has access to the job position of a candidature
	 * (the customer scope of a candidature is the one of its parent job position).
	 *
	 * @param   int     $jobpositionid     Id of the job position of the candidature (0 if not linked to a job position)
	 * @return  void
	 *
	 * @throws RestException 403 Access to object not allowed
	 */
	private function _checkAccessToJobPositionOfCandidature($jobpositionid)
	{
		if (((int) $jobpositionid) > 0) {
			$jobpositiontmp = new RecruitmentJobPosition($this->db);
			if ($jobpositiontmp->fetch((int) $jobpositionid) > 0) {
				$this->_checkAccessToThirdpartyOfJobPosition($jobpositiontmp->fk_soc);
				return;
			}
		}
		// Parent job position not found (can have been deleted): no link to any customer to
		// check for internal users, but a record without job position can not belong to an external user
		if (!empty(DolibarrApiAccess::$user->socid)) {
			throw new RestException(403, 'Access to object not linked to any job position not allowed for external login '.DolibarrApiAccess::$user->login);
		}
	}

	/**
	 * Validate fields before create or update object
	 *
	 * @param	?array<string,mixed>		$data   Array of data to validate
	 * @param	CommonObject				$object Object to validate against
	 * @return	array<string,mixed>
	 *
	 * @throws	RestException
	 */
	private function _validate($data, $object)
	{
		if ($data === null) {
			$data = array();
		}
		$result = array();
		foreach ($object->fields as $field => $propfield) {
			if (in_array($field, array('rowid', 'entity', 'date_creation', 'tms', 'fk_user_creat', 'ref')) || empty($propfield['notnull']) || $propfield['notnull'] != 1 || !empty($propfield['noteditable']) || isset($propfield['default'])) {
				continue; // Not a mandatory field or auto-generated field or has default value
			}
			if (!isset($data[$field])) {
				throw new RestException(400, "$field field missing");
			}
			$result[$field] = $data[$field];
		}
		return $result;
	}
}
