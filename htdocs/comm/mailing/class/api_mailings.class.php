<?php
/* Copyright (C) 2025		Cloned from htdocs/comm/propal/class/api_proposals.class.php then modified
 * Copyright (C) 2025		Jon Bendtsen <jon.bendtsen.github@jonb.dk>
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

require_once DOL_DOCUMENT_ROOT.'/comm/mailing/class/mailing.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/mailing/class/mailing_targets.class.php';

/**
 * API class for mass mailings
 *
 * @since	23.0.0	Initial implementation
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class Mailings extends DolibarrApi
{
	/**
	 * @var string[]       Mandatory fields, checked when create and update object
	 */
	public static $FIELDS = array(
		'title',
		'sujet',
		'body'
	);

	/**
	 * @var string[]       Mandatory fields, checked when create and update object
	 */
	public static $TARGETFIELDS = array(
		'fk_mailing',
		'email'
	);

	/**
	 * @var Mailing {@type Mailing}
	 */
	public $mailing;

	/**
	 * @var MailingTarget {@type MailingTarget}
	 */
	public $mailing_target;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		global $db;
		$this->db = $db;
		$this->mailing = new Mailing($this->db);
		$this->mailing_target = new MailingTarget($this->db);
	}

	/**
	 * Get a mass mailing
	 *
	 * Return an array with mass mailing information
	 *
	 * @since	23.0.0	Initial implementation
	 *
	 * @param   int         $id				ID of mass mailing
	 * @return  Object						Object with cleaned properties
	 *
	 * @throws	RestException
	 */
	public function get($id)
	{
		return $this->_fetch($id);
	}

	/**
	 * Get properties of an mailing object
	 *
	 * Return an array with mailing information
	 *
	 * @param   int         $id             ID of mailing object
	 * @return  Object						Object with cleaned properties
	 *
	 * @throws	RestException
	 */
	private function _fetch($id)
	{
		if (!DolibarrApiAccess::$user->hasRight('mailing', 'read')) {
			throw new RestException(403);
		}

		$result = $this->mailing->fetch($id);
		if ($result < 0) {
			throw new RestException(404, 'Mass mailing not found, id='.$id);
		}

		if (!DolibarrApi::_checkAccessToResource('mailing', $this->mailing->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$this->mailing->fetchObjectLinked();

		if (!DolibarrApi::_checkAccessToResource('project', ((int) $this->mailing->fk_project))) {
			throw new RestException(403, 'Access (project) not allowed for login '.DolibarrApiAccess::$user->login);
		}

		return $this->_cleanObjectDatas($this->mailing);
	}

	/**
	 * List mass mailings
	 *
	 * Get a list of mass mailings
	 *
	 * @since	23.0.0	Initial implementation
	 *
	 * @param string	$sortfield			Sort field
	 * @param string	$sortorder			Sort order
	 * @param int		$limit				Limit for list
	 * @param int		$page				Page number
	 * @param string    $fk_projects        Project ids to filter mass mailings (example '1' or '1,2,3') {@pattern /^[0-9,]*$/i}
	 * @param string    $sqlfilters         Other criteria to filter answers separated by a comma. Syntax example "(t.ref:like:'SO-%') and (t.datec:<:'2016-01-01')"
	 * @param string    $properties	        Restrict the data returned to these properties. Ignored if empty. Comma separated list of properties names
	 * @param bool      $pagination_data    If this parameter is set to true the response will include pagination data. Default value is false. Page starts from 0*
	 * @param int		$loadlinkedobjects	Load also linked object
	 * @return  array                       Array of order objects
	 * @phan-return Mailing[]|array{data:Mailing[],pagination:array{total:int,page:int,page_count:int,limit:int}}
	 * @phpstan-return Mailing[]|array{data:Mailing[],pagination:array{total:int,page:int,page_count:int,limit:int}}
	 *
	 * @throws RestException 400
	 * @throws RestException 403
	 */
	public function index($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $fk_projects = '', $sqlfilters = '', $properties = '', $pagination_data = false, $loadlinkedobjects = 0)
	{
		if (!DolibarrApiAccess::$user->hasRight('mailing', 'read')) {
			throw new RestException(403);
		}

		$arrayProjects = explode(",", $fk_projects);
		foreach ($arrayProjects as $project => $value) {
			if (!DolibarrApi::_checkAccessToResource('project', ((int) $value))) {
				throw new RestException(403, 'Access (project) not allowed for login '.DolibarrApiAccess::$user->login);
			}
		}

		$obj_ret = array();

		$sql = "SELECT t.rowid";
		$sql .= " FROM ".MAIN_DB_PREFIX."mailing AS t";
		$sql .= ' WHERE t.entity IN ('.getEntity('mailing').')';
		if ($fk_projects) {
			$sql .= " AND t.fk_project IN (".$this->db->sanitize($fk_projects).")";
		}		// Add sql filters
		if ($sqlfilters) {
			$errormessage = '';
			$sql .= forgeSQLFromUniversalSearchCriteria($sqlfilters, $errormessage);
			if ($errormessage) {
				throw new RestException(400, 'Error when validating parameter sqlfilters -> '.$errormessage);
			}
		}

		//this query will return total mass mailings with the filters given
		$sqlTotals = str_replace('SELECT t.rowid', 'SELECT count(t.rowid) as total', $sql);

		$sql .= $this->db->order($sortfield, $sortorder);
		if ($limit) {
			if ($page < 0) {
				$page = 0;
			}
			$offset = $limit * $page;

			$sql .= $this->db->plimit($limit + 1, $offset);
		}

		dol_syslog("API Rest request mass mailing");
		$result = $this->db->query($sql);

		if ($result) {
			$num = $this->db->num_rows($result);
			$min = min($num, ($limit <= 0 ? $num : $limit));
			$i = 0;
			while ($i < $min) {
				$obj = $this->db->fetch_object($result);
				$mailing_static = new Mailing($this->db);
				if ($mailing_static->fetch($obj->rowid) > 0) {
					if ($loadlinkedobjects) {
						// retrieve linked objects
						$mailing_static->fetchObjectLinked();
					}

					$obj_ret[] = $this->_filterObjectProperties($this->_cleanObjectDatas($mailing_static), $properties);
				}
				$i++;
			}
		} else {
			throw new RestException(503, 'Error when retrieve list of mass mailings : '.$this->db->lasterror());
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
	 * List mass mailing targets
	 *
	 * Get a list of mass mailing targets
	 *
	 * @since	23.0.0	Initial implementation
	 *
	 * @param int       $id                 Mass mailing ID
	 * @param string	$sortfield			Sort field
	 * @param string	$sortorder			Sort order
	 * @param int		$limit				Limit for list
	 * @param int		$page				Page number
	 * @param string    $sqlfilters         Other criteria to filter answers separated by a comma. Syntax example "(t.lastname:like:'John Doe') and (t.statut:=:3)"
	 * @param string    $properties	        Restrict the data returned to these properties. Ignored if empty. Comma separated list of properties names
	 * @param bool      $pagination_data    If this parameter is set to true the response will include pagination data. Default value is false. Page starts from 0*
	 * @return  array                       Array of order objects
	 * @phan-return Mailing[]|array{data:Mailing[],pagination:array{total:int,page:int,page_count:int,limit:int}}
	 * @phpstan-return Mailing[]|array{data:Mailing[],pagination:array{total:int,page:int,page_count:int,limit:int}}
	 *
	 * @url GET    {id}/targets
	 *
	 * @throws RestException 400
	 * @throws RestException 403
	 * @throws RestException 404
	 */
	public function indexTargets($id, $sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $sqlfilters = '', $properties = '', $pagination_data = false)
	{
		if (!DolibarrApiAccess::$user->hasRight('mailing', 'read')) {
			throw new RestException(403);
		}

		$fetchMailingResult = $this->mailing->fetch($id);
		if ($fetchMailingResult < 0) {
			throw new RestException(404, 'Mass mailing not found, id='.$id);
		}

		$fk_project = $this->mailing->fk_project;
		if (!DolibarrApi::_checkAccessToResource('project', ((int) $fk_project))) {
			throw new RestException(403, 'Access (project) not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$obj_ret = array();

		$sql = "SELECT t.rowid";
		$sql .= " FROM ".MAIN_DB_PREFIX."mailing_cibles AS t";
		$sql .= " WHERE t.fk_mailing = ".((int) $id);

		// Add sql filters
		if ($sqlfilters) {
			$errormessage = '';
			$sql .= forgeSQLFromUniversalSearchCriteria($sqlfilters, $errormessage);
			if ($errormessage) {
				throw new RestException(400, 'Error when validating parameter sqlfilters -> '.$errormessage);
			}
		}

		//this query will return total mass mailing targets with the filters given
		$sqlTotals = str_replace('SELECT t.rowid', 'SELECT count(t.rowid) as total', $sql);

		$sql .= $this->db->order($sortfield, $sortorder);
		if ($limit) {
			if ($page < 0) {
				$page = 0;
			}
			$offset = $limit * $page;

			$sql .= $this->db->plimit($limit + 1, $offset);
		}

		dol_syslog("API Rest request mass mailing target");
		$result = $this->db->query($sql);

		if ($result) {
			$num = $this->db->num_rows($result);
			$min = min($num, ($limit <= 0 ? $num : $limit));
			$i = 0;
			while ($i < $min) {
				$obj = $this->db->fetch_object($result);
				$mailing_target = new MailingTarget($this->db);
				if ($mailing_target->fetch($obj->rowid) > 0) {
					$obj_ret[] = $this->_filterObjectProperties($this->_cleanTargetDatas($mailing_target), $properties);
				}
				$i++;
			}
		} else {
			throw new RestException(503, 'Error when retrieve list of mass mailing targetss : '.$this->db->lasterror());
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
	 * Clone a mass mailing
	 *
	 * @since	23.0.0	Initial implementation
	 *
	 * @param  	int		$id     			Id of object to clone
	 * @param	int		$cloneContent		1=Clone content (default), 0=Forget content
	 * @param	int		$cloneRecipients	1=Clone recipients (default), 0=Forget recipients
	 * @param	int		$notrigger			1=Disable triggers, 0=Active triggers if any (default)
	 * @return 	Object 						Object with cleaned properties
	 *
	 * @throws RestException 403
	 * @throws RestException 404
	 * @throws RestException 500 System error
	 */
	public function clone($id, $cloneContent = 1, $cloneRecipients = 1, $notrigger = 0)
	{
		if (!DolibarrApiAccess::$user->hasRight('mailing', 'write')) {
			throw new RestException(403, "Insufficient rights");
		}
		$result = $this->mailing->fetch($id);
		if ($result < 0) {
			throw new RestException(404, 'Mass mailing to clone not found, id='.$id);
		}

		if (!DolibarrApi::_checkAccessToResource('project', ((int) $this->mailing->fk_project))) {
			throw new RestException(403, 'Access (project) not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$cloningResult = $this->mailing->createFromClone(DolibarrApiAccess::$user, ((int) $id), ((int) $cloneContent), ((int) $cloneRecipients), ((int) $notrigger));
		if ($cloningResult < 0) {
			throw new RestException(500, "Error cloning mass mailing", array_merge(array($this->mailing->error), $this->mailing->errors));
		}

		if ($cloningResult > 0) {
			return $this->get($cloningResult);
		} else {
			throw new RestException(500, $this->mailing->error);
		}
	}

	/**
	 * Create a mass mailing
	 *
	 * @since	23.0.0	Initial implementation
	 *
	 * @param   array   $request_data   Request data
	 * @phan-param ?array<string,string> $request_data
	 * @phpstan-param ?array<string,string> $request_data
	 * @return  int     ID of mass mailing
	 *
	 * @throws RestException 403
	 * @throws RestException 500 System error
	 */
	public function post($request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('mailing', 'write')) {
			throw new RestException(403, "Insufficiant rights");
		}
		// Check mandatory fields
		$result = $this->_validate($request_data);

		foreach ($request_data as $field => $value) {
			if ($field === 'caller') {
				// Add a mention of caller so on trigger called after action, we can filter to avoid a loop if we try to sync back again with the caller
				$this->mailing->context['caller'] = sanitizeVal($request_data['caller'], 'aZ09');
				continue;
			}
			if ($field === 'fk_project') {
				if (!DolibarrApi::_checkAccessToResource('project', ((int) $value))) {
					throw new RestException(403, 'Access (project) not allowed for login '.DolibarrApiAccess::$user->login);
				}
			}

			$this->mailing->$field = $this->_checkValForAPI($field, $value, $this->mailing);
		}

		if ($this->mailing->create(DolibarrApiAccess::$user) < 0) {
			throw new RestException(500, "Error creating mass mailing", array_merge(array($this->mailing->error), $this->mailing->errors));
		}

		return ((int) $this->mailing->id);
	}

	/**
	 * Update a mass mailing general fields (won't change lines of mass mailing)
	 *
	 * @since	23.0.0	Initial implementation
	 *
	 * @param	int		$id             Id of mass mailing to update
	 * @param	array	$request_data   Datas
	 * @phan-param ?array<string,string> $request_data
	 * @phpstan-param ?array<string,string> $request_data
	 * @return	Object					Object with cleaned properties
	 *
	 * @throws RestException 403
	 * @throws RestException 404
	 * @throws RestException 500 System error
	 */
	public function put($id, $request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('mailing', 'write')) {
			throw new RestException(403);
		}

		$result = $this->mailing->fetch($id);
		if ($result < 0) {
			throw new RestException(404, 'Mass mailing not found, id='.$id);
		}

		if (!DolibarrApi::_checkAccessToResource('project', ((int) $this->mailing->fk_project))) {
			throw new RestException(403, 'Access (project) not allowed for login '.DolibarrApiAccess::$user->login);
		}

		if (!DolibarrApi::_checkAccessToResource('mailing', $this->mailing->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}
		foreach ($request_data as $field => $value) {
			if ($field == 'id') {
				continue;
			}
			if ($field === 'caller') {
				// Add a mention of caller so on trigger called after action, we can filter to avoid a loop if we try to sync back again with the caller
				$this->mailing->context['caller'] = sanitizeVal($request_data['caller'], 'aZ09');
				continue;
			}
			if ($field == 'array_options' && is_array($value)) {
				foreach ($value as $index => $val) {
					$this->mailing->array_options[$index] = $this->_checkValForAPI($field, $val, $this->mailing);
				}
				continue;
			}

			$this->mailing->$field = $this->_checkValForAPI($field, $value, $this->mailing);
		}

		if ($this->mailing->update(DolibarrApiAccess::$user) > 0) {
			return $this->get($id);
		} else {
			throw new RestException(500, $this->mailing->error);
		}
	}

	/**
	 * Delete a mass mailing
	 *
	 * @since	23.0.0	Initial implementation
	 *
	 * @param   int     $id         Mass mailing ID
	 * @return  array
	 * @phan-return array{success:array{code:int,message:string}}
	 * @phpstan-return array{success:array{code:int,message:string}}
	 *
	 * @throws RestException 403
	 * @throws RestException 404
	 * @throws RestException 500 System error
	 */
	public function delete($id)
	{
		if (!DolibarrApiAccess::$user->hasRight('mailing', 'delete')) {
			throw new RestException(403);
		}
		$result = $this->mailing->fetch($id);
		if ($result < 0) {
			throw new RestException(404, 'Mass mailing not found, id='.$id);
		}

		if (!DolibarrApi::_checkAccessToResource('project', ((int) $this->mailing->fk_project))) {
			throw new RestException(403, 'Access (project) not allowed for login '.DolibarrApiAccess::$user->login);
		}

		if (!DolibarrApi::_checkAccessToResource('mailing', $this->mailing->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		if (!$this->mailing->delete(DolibarrApiAccess::$user)) {
			throw new RestException(500, 'Error when delete Mass mailing : '.$this->mailing->error);
		}

		return array(
			'success' => array(
				'code' => 200,
				'message' => 'Mass mailing with id='.$id.' deleted'
			)
		);
	}

	/**
	 * Update a mass mailing general fields (won't change lines of mass mailing)
	 *
	 * @since	23.0.0	Initial implementation
	 *
	 * @param	int		$id             Id of mass mailing with the targetid to update
	 * @param	int		$targetid       Id mass mailing target to update
	 * @param	array	$request_data   Datas
	 * @phan-param ?array<string,string> $request_data
	 * @phpstan-param ?array<string,string> $request_data
	 * @return	Object					Object with cleaned properties
	 *
	 * @url PUT    {id}/updateTarget/{targetid}
	 *
	 * @throws RestException 403
	 * @throws RestException 404
	 * @throws RestException 500 System error
	 */
	public function updateTarget($id, $targetid, $request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('mailing', 'write')) {
			throw new RestException(403);
		}

		$fetchMailingResult = $this->mailing->fetch($id);
		if ($fetchMailingResult < 0) {
			throw new RestException(404, 'Mass mailing not found, id='.$id);
		}
		$result = $this->mailing_target->fetch($targetid);
		if ($result < 0) {
			throw new RestException(404, 'Mass mailing target not found, id='.$targetid);
		}
		if ($id != $this->mailing_target->fk_mailing) {
			throw new RestException(404, 'Target id='.$targetid.' is does not belong to mailing id='.$id);
		}

		if (!DolibarrApi::_checkAccessToResource('project', ((int) $this->mailing->fk_project))) {
			throw new RestException(403, 'Access (project) not allowed for login '.DolibarrApiAccess::$user->login);
		}

		if (!DolibarrApi::_checkAccessToResource('mailing', $this->mailing->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}
		foreach ($request_data as $field => $value) {
			if ($field == 'id') {
				throw new RestException(400, 'Changing id field is forbidden');
			}
			if ($field == 'fk_mailing') {
				throw new RestException(400, 'Changing fk_mailing field is forbidden to protect inserting a wrong fk_mailing number. Use a POST to create a new mailing target with the correct mailing id, then an PUT to update the new target in the right mailing id, and finally a delete to remove the old target');
			}
			if ($field === 'caller') {
				// Add a mention of caller so on trigger called after action, we can filter to avoid a loop if we try to sync back again with the caller
				$this->mailing_target->context['caller'] = sanitizeVal($request_data['caller'], 'aZ09');
				continue;
			}

			$this->mailing_target->$field = $this->_checkValForAPI($field, $value, $this->mailing_target);
		}

		if ($this->mailing_target->update(DolibarrApiAccess::$user) > 0) {
			return $this->getTarget($id, $targetid);
		} else {
			throw new RestException(500, $this->mailing_target->error);
		}
	}

	/**
	 * Create a mass mailing
	 *
	 * @since	23.0.0	Initial implementation
	 *
	 * @param	int		$id             Id of mass mailing to create a target for
	 * @param   array   $request_data   Request data
	 * @phan-param ?array<string,string> $request_data
	 * @phpstan-param ?array<string,string> $request_data
	 * @return  int     ID of mass mailing
	 *
	 * @url POST    {id}/createTarget
	 *
	 * @throws RestException 400
	 * @throws RestException 403
	 * @throws RestException 404
	 * @throws RestException 500 System error
	 */
	public function postTarget($id, $request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('mailing', 'write')) {
			throw new RestException(403, "Insufficiant rights");
		}
		// Check mandatory fields
		$result = $this->_validateTarget($request_data);

		$fk_mailing_id = 0;

		foreach ($request_data as $field => $value) {
			if ($field === 'caller') {
				// Add a mention of caller so on trigger called after action, we can filter to avoid a loop if we try to sync back again with the caller
				$this->mailing_target->context['caller'] = sanitizeVal($request_data['caller'], 'aZ09');
				continue;
			}
			if ($field === 'fk_project') {
				if (!DolibarrApi::_checkAccessToResource('project', ((int) $value))) {
					throw new RestException(403, 'Access (project) not allowed for login '.DolibarrApiAccess::$user->login);
				}
			}

			if ($field == 'id') {
				throw new RestException(400, 'Creating with id field is forbidden');
			}
			if ($field == 'fk_mailing') {
				$fetchMailingResult = $this->mailing->fetch((int) $value);
				if ($fetchMailingResult < 0) {
					throw new RestException(404, 'Mass mailing not found, id='.((int) $value));
				}
				if (!DolibarrApi::_checkAccessToResource('project', ((int) $this->mailing->fk_project))) {
					throw new RestException(403, 'Access (project) not allowed for login '.DolibarrApiAccess::$user->login);
				}
				$fk_mailing_id = ((int) $value);
			}

			$this->mailing_target->$field = $this->_checkValForAPI($field, $value, $this->mailing_target);
		}

		if (0 == $fk_mailing_id) {
			throw new RestException(404, 'Mass mailing not found, id='.((int) $fk_mailing_id));
		}

		if ($this->mailing_target->create(DolibarrApiAccess::$user) < 0) {
			throw new RestException(500, "Error creating mass mailing target", array_merge(array($this->mailing->error), $this->mailing->errors));
		}

		return ((int) $this->mailing_target->id);
	}

	/**
	 * Get a target in a mass mailing
	 *
	 * Return an array with info about a mass mailing target
	 *
	 * @since	23.0.0	Initial implementation
	 *
	 * @param	int		$id             Id of mass mailing with the targetid to get
	 * @param	int		$targetid       Id mass mailing target to get
	 * @return  Object						Object with cleaned properties
	 *
	 * @url GET    {id}/getTarget/{targetid}
	 *
	 * @throws	RestException
	 */
	public function getTarget($id, $targetid)
	{
		return $this->_fetchTarget($id, $targetid);
	}

	/**
	 * Get properties of an mailing object
	 *
	 * Return an array with mailing information
	 *
	 * @param   int     $id             ID of mailing object
	 * @param	int		$targetid       Id mass mailing target
	 * @return  Object						Object with cleaned properties
	 *
	 * @throws RestException 403
	 * @throws RestException 404
	 */
	private function _fetchTarget($id, $targetid)
	{
		if (!DolibarrApiAccess::$user->hasRight('mailing', 'read')) {
			throw new RestException(403);
		}

		$fetchMailingResult = $this->mailing->fetch($id);
		if ($fetchMailingResult < 0) {
			throw new RestException(404, 'Mass mailing not found, id='.$id);
		}
		$result = $this->mailing_target->fetch($targetid);
		if ($result < 0) {
			throw new RestException(404, 'Mass mailing target not found, id='.$targetid);
		}
		if ($id != $this->mailing_target->fk_mailing) {
			throw new RestException(404, 'Target id='.$targetid.' is does not belong to mailing id='.$id);
		}

		if (!DolibarrApi::_checkAccessToResource('project', ((int) $this->mailing->fk_project))) {
			throw new RestException(403, 'Access (project) not allowed for login '.DolibarrApiAccess::$user->login);
		}

		if (!DolibarrApi::_checkAccessToResource('mailing', $this->mailing->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		return $this->_cleanTargetDatas($this->mailing_target);
	}

	/**
	 * Delete a mass mailing general fields (won't change lines of mass mailing)
	 *
	 * @since	23.0.0	Initial implementation
	 *
	 * @param	int		$id             Id of mass mailing with the targetid to delete
	 * @param	int		$targetid       Id mass mailing target to delete
	 * @return  array
	 * @phan-return array{success:array{code:int,message:string}}
	 * @phpstan-return array{success:array{code:int,message:string}}
	 *
	 * @url DELETE    {id}/deleteTarget/{targetid}
	 *
	 * @throws RestException 403
	 * @throws RestException 404
	 * @throws RestException 500 System error
	 */
	public function deleteTarget($id, $targetid)
	{
		if (!DolibarrApiAccess::$user->hasRight('mailing', 'delete')) {
			throw new RestException(403);
		}

		$fetchMailingResult = $this->mailing->fetch($id);
		if ($fetchMailingResult < 0) {
			throw new RestException(404, 'Mass mailing not found, id='.$id);
		}
		$result = $this->mailing_target->fetch($targetid);
		if ($result < 0) {
			throw new RestException(404, 'Mass mailing target not found, id='.$targetid);
		}
		if ($id != $this->mailing_target->fk_mailing) {
			throw new RestException(404, 'Target id='.$targetid.' is does not belong to mailing id='.$id);
		}
		if (!DolibarrApi::_checkAccessToResource('project', ((int) $this->mailing->fk_project))) {
			throw new RestException(403, 'Access (project) not allowed for login '.DolibarrApiAccess::$user->login);
		}
		if (!DolibarrApi::_checkAccessToResource('mailing', $this->mailing->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		if (!$this->mailing_target->delete(DolibarrApiAccess::$user)) {
			throw new RestException(500, 'Error when delete Mass mailing target: '.$this->mailing->error);
		}

		return array(
			'success' => array(
				'code' => 200,
				'message' => 'Deleting target id='.$targetid.' belonging to mailing id='.$id
			)
		);
	}

	/**
	 * Delete targets of a mass mailing
	 *
	 * @since	23.0.0	Initial implementation
	 *
	 * @param   int     $id         Mass mailing ID
	 * @return  array
	 * @phan-return array{success:array{code:int,message:string}}
	 * @phpstan-return array{success:array{code:int,message:string}}
	 *
	 * @url DELETE    {id}/deleteTargets
	 *
	 * @throws RestException 403
	 * @throws RestException 404
	 * @throws RestException 500 System error
	 */
	public function deleteTargets($id)
	{
		if (!DolibarrApiAccess::$user->hasRight('mailing', 'delete')) {
			throw new RestException(403);
		}
		$result = $this->mailing->fetch($id);
		if ($result < 0) {
			throw new RestException(404, 'Mass mailing not found, id='.$id);
		}

		if (!DolibarrApi::_checkAccessToResource('project', ((int) $this->mailing->fk_project))) {
			throw new RestException(403, 'Access (project) not allowed for login '.DolibarrApiAccess::$user->login);
		}

		if (!DolibarrApi::_checkAccessToResource('mailing', $this->mailing->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$count = $this->mailing->countNbOfTargets('all');
		if (!$this->mailing->delete_targets()) {
			throw new RestException(500, 'Error when delete targets of Mass mailing : '.$this->mailing->error);
		}

		return array(
			'success' => array(
				'code' => 200,
				'message' => 'Target recipients ('.$count.') of Mass mailing with id='.$id.' deleted'
			)
		);
	}

	/**
	 * reset target status of a mass mailing
	 *
	 * @since	23.0.0	Initial implementation
	 *
	 * @param   int     $id         Mass mailing ID
	 * @return  array
	 * @phan-return array{success:array{code:int,message:string}}
	 * @phpstan-return array{success:array{code:int,message:string}}
	 *
	 * @url PUT    {id}/resetTargetsStatus
	 *
	 * @throws RestException 403
	 * @throws RestException 404
	 * @throws RestException 500 System error
	 */
	public function resetTargetsStatus($id)
	{
		if (!DolibarrApiAccess::$user->hasRight('mailing', 'write')) {
			throw new RestException(403);
		}
		$result = $this->mailing->fetch($id);
		if ($result < 0) {
			throw new RestException(404, 'Mass mailing not found, id='.$id);
		}

		if (!DolibarrApi::_checkAccessToResource('project', ((int) $this->mailing->fk_project))) {
			throw new RestException(403, 'Access (project) not allowed for login '.DolibarrApiAccess::$user->login);
		}

		if (!DolibarrApi::_checkAccessToResource('mailing', $this->mailing->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$count = $this->mailing->countNbOfTargets('all');
		if (!$this->mailing->reset_targets_status(DolibarrApiAccess::$user)) {
			throw new RestException(500, 'Error when reset targets status of Mass mailing : '.$this->mailing->error);
		}

		return array(
			'success' => array(
				'code' => 200,
				'message' => 'Resetting status of '.$count.' target recipients in Mass mailing with id='.$id
			)
		);
	}

	/**
	 * Set a mass mailing to draft
	 *
	 * @since	23.0.0	Initial implementation
	 *
	 * @param   int     $id             Mass mailing ID
	 * @return	Object					Object with cleaned properties
	 *
	 * @url PUT    {id}/settodraft
	 *
	 * @throws RestException 304
	 * @throws RestException 403
	 * @throws RestException 404
	 * @throws RestException 500 System error
	 */
	public function settodraft($id)
	{
		if (!DolibarrApiAccess::$user->hasRight('mailing', 'write')) {
			throw new RestException(403);
		}
		$result = $this->mailing->fetch($id);
		if ($result < 0) {
			throw new RestException(404, 'Mass mailing not found, id='.$id);
		}

		if (!DolibarrApi::_checkAccessToResource('project', ((int) $this->mailing->fk_project))) {
			throw new RestException(403, 'Access (project) not allowed for login '.DolibarrApiAccess::$user->login);
		}

		if (!DolibarrApi::_checkAccessToResource('mailing', $this->mailing->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$result = $this->mailing->setDraft(DolibarrApiAccess::$user);
		if ($result == 0) {
			throw new RestException(304, 'Nothing done. May be object is already draft');
		}
		if ($result < 0) {
			throw new RestException(500, 'Error : '.$this->mailing->error);
		}

		$this->mailing->fetchObjectLinked();

		return $this->_cleanObjectDatas($this->mailing);
	}


	/**
	 * Validate a mass mailing
	 *
	 * If you get a bad value for param notrigger check that ou provide this in body
	 * {
	 * "notrigger": 0
	 * }
	 *
	 * @since	23.0.0	Initial implementation
	 *
	 * @param   int     $id             Mass mailing ID
	 * @return	Object					Object with cleaned properties
	 *
	 * @url PUT    {id}/validate
	 *
	 * @throws RestException 304
	 * @throws RestException 403
	 * @throws RestException 404
	 * @throws RestException 500 System error
	 */
	public function validate($id)
	{
		if (!DolibarrApiAccess::$user->hasRight('mailing', 'write')) {
			throw new RestException(403);
		}
		$result = $this->mailing->fetch($id);
		if ($result < 0) {
			throw new RestException(404, 'Mass mailing not found, id='.$id);
		}

		if (!DolibarrApi::_checkAccessToResource('project', ((int) $this->mailing->fk_project))) {
			throw new RestException(403, 'Access (project) not allowed for login '.DolibarrApiAccess::$user->login);
		}

		if (!DolibarrApi::_checkAccessToResource('mailing', $this->mailing->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$result = $this->mailing->valid(DolibarrApiAccess::$user);
		if ($result == 0) {
			throw new RestException(304, 'Error nothing done. May be object is already validated');
		}
		if ($result < 0) {
			throw new RestException(500, 'Error when validating Mass mailing: '.$this->mailing->error);
		}

		$this->mailing->fetchObjectLinked();

		return $this->_cleanObjectDatas($this->mailing);
	}

	/**
	 * Validate fields before create or update object
	 *
	 * @param ?array<string,string> $data   Array with data to verify
	 * @return array<string,string>
	 *
	 * @throws  RestException
	 */
	private function _validate($data)
	{
		if ($data === null) {
			$data = array();
		}
		$mailing = array();
		foreach (Mailings::$FIELDS as $field) {
			if (!isset($data[$field])) {
				throw new RestException(400, "$field field missing");
			}
			$mailing[$field] = $data[$field];
		}
		return $mailing;
	}

	/**
	 * Validate fields before create or update object
	 *
	 * @param ?array<string,string> $data   Array with data to verify
	 * @return array<string,string>
	 *
	 * @throws  RestException
	 */
	private function _validateTarget($data)
	{
		if ($data === null) {
			$data = array();
		}
		$mailing_target = array();
		foreach (Mailings::$TARGETFIELDS as $field) {
			if (!isset($data[$field])) {
				throw new RestException(400, "$field field missing");
			}
			$mailing_target[$field] = $data[$field];
		}
		return $mailing_target;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 * Clean sensible object (mailing target) datas
	 *
	 * @param   Object  $object     Object to clean
	 * @return  Object              Object with cleaned properties
	 */
	protected function _cleanTargetDatas($object)
	{
		// phpcs:enable
		$object = parent::_cleanObjectDatas($object);

		unset($object->TRIGGER_PREFIX);
		unset($object->actionmsg);
		unset($object->actionmsg2);
		unset($object->actiontypecode);
		unset($object->alreadypaid);
		unset($object->array_options);
		unset($object->array_languages);
		unset($object->barcode_type_code);
		unset($object->barcode_type_coder);
		unset($object->barcode_type_label);
		unset($object->barcode_type);
		unset($object->canvas);
		unset($object->civility_code);
		unset($object->civility_id);
		unset($object->comments);
		unset($object->cond_reglement_id);
		unset($object->cond_reglement_supplier_id);
		unset($object->contact_id);
		unset($object->contact);
		unset($object->contacts_ids_internal);
		unset($object->contacts_ids);
		unset($object->context);
		unset($object->country_code);
		unset($object->country_id);
		unset($object->country);
		unset($object->date_cloture);
		unset($object->date_creation);
		unset($object->date_validation);
		unset($object->db);
		unset($object->demand_reason_id);
		unset($object->deposit_percent);
		unset($object->element_for_permission);
		unset($object->element);
		unset($object->entity);
		unset($object->error);
		unset($object->errorhidden);
		unset($object->errors);
		unset($object->extraparams);
		unset($object->fields);
		unset($object->fk_account);
		unset($object->fk_bank);
		unset($object->fk_delivery_address);
		unset($object->fk_element);
		unset($object->fk_multicurrency);
		unset($object->fk_projet);
		unset($object->fk_project);
		unset($object->fk_user_creat);
		unset($object->fk_user_modif);
		unset($object->import_key);
		unset($object->isextrafieldmanaged);
		unset($object->ismultientitymanaged);
		unset($object->last_main_doc);
		unset($object->lines);
		unset($object->linked_objects);
		unset($object->linkedObjects);
		unset($object->linkedObjectsIds);
		unset($object->mode_reglement_id);
		unset($object->model_pdf);
		unset($object->module);
		unset($object->multicurrency_code);
		unset($object->multicurrency_total_ht);
		unset($object->multicurrency_total_localtax1);
		unset($object->multicurrency_total_localtax2);
		unset($object->multicurrency_total_ttc);
		unset($object->multicurrency_total_tva);
		unset($object->multicurrency_tx);
		unset($object->name);
		unset($object->nb);
		unset($object->nbphoto);
		unset($object->newref);
		unset($object->next_prev_filter);
		unset($object->note);
		unset($object->note_public);
		unset($object->note_private);
		unset($object->oldcopy);
		unset($object->oldref);
		unset($object->origin_id);
		unset($object->origin_object);
		unset($object->origin_type);
		unset($object->origin);
		unset($object->output);
		unset($object->product);
		unset($object->project);
		unset($object->ref_ext);
		unset($object->ref_next);
		unset($object->ref_previous);
		unset($object->ref);
		unset($object->region_code);
		unset($object->region_id);
		unset($object->region);
		unset($object->restrictiononfksoc);
		unset($object->retained_warranty_fk_cond_reglement);
		unset($object->sendtoid);
		unset($object->shipping_method_id);
		unset($object->shipping_method);
		unset($object->showphoto_on_popup);
		unset($object->specimen);
		unset($object->state_code);
		unset($object->state_id);
		unset($object->state);
		unset($object->table_element_line);
		unset($object->table_element);
		unset($object->thirdparty);
		unset($object->total_ht);
		unset($object->total_localtax1);
		unset($object->total_localtax2);
		unset($object->total_ttc);
		unset($object->total_tva);
		unset($object->totalpaid_multicurrency);
		unset($object->totalpaid);
		unset($object->tpl);
		unset($object->transport_mode_id);
		unset($object->user);
		unset($object->user_creation_id);
		unset($object->user_validation_id);
		unset($object->user_closing_id);
		unset($object->user_modification_id);
		unset($object->warehouse_id);

		return $object;
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

		unset($object->actionmsg);
		unset($object->actionmsg2);
		unset($object->actiontypecode);
		unset($object->alreadypaid);
		unset($object->barcode_type_code);
		unset($object->barcode_type_coder);
		unset($object->barcode_type_label);
		unset($object->barcode_type);
		unset($object->canvas);
		unset($object->civility_code);
		unset($object->civility_id);
		unset($object->comments);
		unset($object->cond_reglement_id);
		unset($object->cond_reglement_supplier_id);
		unset($object->contact_id);
		unset($object->contact);
		unset($object->contacts_ids_internal);
		unset($object->contacts_ids);
		unset($object->context);
		unset($object->country_code);
		unset($object->country_id);
		unset($object->country);
		unset($object->db);
		unset($object->demand_reason_id);
		unset($object->deposit_percent);
		unset($object->element_for_permission);
		unset($object->element);
		unset($object->error);
		unset($object->errorhidden);
		unset($object->errors);
		unset($object->fields);
		unset($object->firstname);
		unset($object->fk_account);
		unset($object->fk_bank);
		unset($object->fk_delivery_address);
		unset($object->fk_element);
		unset($object->fk_multicurrency);
		unset($object->fk_projet);
		unset($object->import_key);
		unset($object->isextrafieldmanaged);
		unset($object->ismultientitymanaged);
		unset($object->last_main_doc);
		unset($object->lastname);
		unset($object->lines);
		unset($object->linked_objects);
		unset($object->linkedObjects);
		unset($object->linkedObjectsIds);
		unset($object->mode_reglement_id);
		unset($object->model_pdf);
		unset($object->module);
		unset($object->multicurrency_code);
		unset($object->multicurrency_total_ht);
		unset($object->multicurrency_total_localtax1);
		unset($object->multicurrency_total_localtax2);
		unset($object->multicurrency_total_ttc);
		unset($object->multicurrency_total_tva);
		unset($object->multicurrency_tx);
		unset($object->name);
		unset($object->nb);
		unset($object->nbphoto);
		unset($object->newref);
		unset($object->next_prev_filter);
		unset($object->note);
		unset($object->oldcopy);
		unset($object->oldref);
		unset($object->origin_id);
		unset($object->origin_object);
		unset($object->origin_type);
		unset($object->origin);
		unset($object->output);
		unset($object->product);
		unset($object->project);
		unset($object->ref_ext);
		unset($object->ref_next);
		unset($object->ref_previous);
		unset($object->ref);
		unset($object->region_code);
		unset($object->region_id);
		unset($object->region);
		unset($object->restrictiononfksoc);
		unset($object->retained_warranty_fk_cond_reglement);
		unset($object->sendtoid);
		unset($object->shipping_method_id);
		unset($object->shipping_method);
		unset($object->showphoto_on_popup);
		unset($object->specimen);
		unset($object->state_code);
		unset($object->state_id);
		unset($object->state);
		unset($object->table_element_line);
		unset($object->table_element);
		unset($object->thirdparty);
		unset($object->total_ht);
		unset($object->total_localtax1);
		unset($object->total_localtax2);
		unset($object->total_ttc);
		unset($object->total_tva);
		unset($object->totalpaid_multicurrency);
		unset($object->totalpaid);
		unset($object->tpl);
		unset($object->transport_mode_id);
		unset($object->user);
		unset($object->warehouse_id);

		return $object;
	}
}
