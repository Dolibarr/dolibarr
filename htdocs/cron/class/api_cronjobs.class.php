<?php
/* Copyright (C) 2026		Frédéric France			<frederic.france@free.fr>
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

require_once DOL_DOCUMENT_ROOT.'/cron/class/cronjob.class.php';

/**
 * API class for cron jobs
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class Cronjobs extends DolibarrApi
{
	/**
	 * @var string[]       Mandatory fields, checked when create and update object
	 */
	public static $FIELDS = array(
		'jobtype',
		'label',
	);

	/**
	 * @var Cronjob {@type Cronjob}
	 */
	public $cronjob;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		global $db;
		$this->db = $db;
		$this->cronjob = new Cronjob($this->db);
	}

	/**
	 * Get properties of a cron job object
	 *
	 * Return an array with cron job information
	 *
	 * @param	int		$id		ID of cron job
	 * @return	Object			Object with cleaned properties
	 *
	 * @throws	RestException
	 */
	public function get($id)
	{
		if (!DolibarrApiAccess::$user->hasRight('cron', 'read')) {
			throw new RestException(403);
		}

		$result = $this->cronjob->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Cron job not found');
		}

		if (!DolibarrApi::_checkAccessToResource('cronjob', $this->cronjob->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		return $this->_cleanObjectDatas($this->cronjob);
	}

	/**
	 * List cron jobs
	 *
	 * Get a list of cron jobs
	 *
	 * @param	string	$sortfield		Sort field
	 * @param	string	$sortorder		Sort order
	 * @param	int		$limit			Limit for list
	 * @param	int		$page			Page number
	 * @param	int		$status			Filter on status (-1=no filter, 0=disabled, 1=enabled, 2=archived)
	 * @param	string	$sqlfilters		Other criteria to filter answers separated by a comma. Syntax example "(t.label:like:'%foo%')"
	 * @param	string	$properties		Restrict the data returned to these properties. Ignored if empty. Comma separated list of properties names
	 * @param	bool	$pagination_data	If this parameter is set to true the response will include pagination data. Default value is false. Page starts from 0
	 * @return	array					Array of cron job objects
	 * @phan-return Cronjob[]|array{data:Cronjob[],pagination:array{total:int,page:int,page_count:int,limit:int}}
	 * @phpstan-return Cronjob[]|array{data:Cronjob[],pagination:array{total:int,page:int,page_count:int,limit:int}}
	 *
	 * @throws RestException
	 */
	public function index($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $status = -1, $sqlfilters = '', $properties = '', $pagination_data = false)
	{
		if (!DolibarrApiAccess::$user->hasRight('cron', 'read')) {
			throw new RestException(403);
		}

		$obj_ret = array();

		if ($page < 0) {
			$page = 0;
		}
		$offset = $limit * $page;

		$result = $this->cronjob->fetchAll($sortorder, $sortfield, $limit, $offset, $status, $sqlfilters);
		if ($result < 0) {
			throw new RestException(503, 'Error when retrieving cron job list: '.$this->cronjob->error);
		}

		foreach ($this->cronjob->lines as $cronjob_static) {
			$obj_ret[] = $this->_filterObjectProperties($this->_cleanObjectDatas($cronjob_static), $properties);
		}

		if ($pagination_data) {
			$tmp = $obj_ret;
			$obj_ret = [];

			$obj_ret['data'] = $tmp;
			$obj_ret['pagination'] = [
				'total' => count($tmp),
				'page' => $page,
				'page_count' => ceil(count($tmp) / $limit),
				'limit' => $limit
			];
		}

		return $obj_ret;
	}

	/**
	 * Create cron job object
	 *
	 * @param	array	$request_data	Request data
	 * @phan-param ?array<string,string> $request_data
	 * @phpstan-param ?array<string,string> $request_data
	 * @return	int		ID of cron job
	 *
	 * @throws RestException
	 */
	public function post($request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('cron', 'write') || !DolibarrApiAccess::$user->admin) {
			throw new RestException(403, "Insufficient rights - creating a cron job requires an admin user");
		}

		$result = $this->_validate($request_data);

		foreach ($request_data as $field => $value) {
			$this->cronjob->$field = $this->_checkValForAPI($field, $value, $this->cronjob);
		}

		if ($this->cronjob->create(DolibarrApiAccess::$user) < 0) {
			throw new RestException(500, "Error creating cron job", array_merge(array($this->cronjob->error), $this->cronjob->errors));
		}

		return $this->cronjob->id;
	}

	/**
	 * Update cron job fields
	 *
	 * @param	int		$id				Id of cron job to update
	 * @param	array	$request_data	Data
	 * @phan-param ?array<string,string> $request_data
	 * @phpstan-param ?array<string,string> $request_data
	 * @return	Object					Updated object
	 *
	 * @throws RestException
	 */
	public function put($id, $request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('cron', 'write') || !DolibarrApiAccess::$user->admin) {
			throw new RestException(403, "Insufficient rights - updating a cron job requires an admin user");
		}

		$result = $this->cronjob->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Cron job not found');
		}

		if (!DolibarrApi::_checkAccessToResource('cronjob', $this->cronjob->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		foreach ($request_data as $field => $value) {
			if ($field == 'id') {
				continue;
			}
			$this->cronjob->$field = $this->_checkValForAPI($field, $value, $this->cronjob);
		}

		if ($this->cronjob->update(DolibarrApiAccess::$user) > 0) {
			return $this->get($id);
		} else {
			throw new RestException(500, $this->cronjob->error);
		}
	}

	/**
	 * Delete cron job
	 *
	 * @param	int		$id		Cron job ID
	 * @return	array
	 * @phan-return array{success:array{code:int,message:string}}
	 * @phpstan-return array{success:array{code:int,message:string}}
	 *
	 * @throws RestException
	 */
	public function delete($id)
	{
		if (!DolibarrApiAccess::$user->hasRight('cron', 'delete')) {
			throw new RestException(403);
		}

		$result = $this->cronjob->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Cron job not found');
		}

		if (!DolibarrApi::_checkAccessToResource('cronjob', $this->cronjob->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		if (!$this->cronjob->delete(DolibarrApiAccess::$user)) {
			throw new RestException(500, 'Error when deleting cron job: '.$this->cronjob->error);
		}

		return array(
			'success' => array(
				'code' => 200,
				'message' => 'Cron job deleted'
			)
		);
	}

	/**
	 * Run a cron job now
	 *
	 * Runs the job synchronously, in the current API request (there is no asynchronous job
	 * queue: this call blocks until the job finishes, exactly like the "Execute" button on the
	 * cron job card). The job's own success/failure is reported in the returned object
	 * (lastresult / lastoutput / datelastresult), it does not by itself make this call fail.
	 *
	 * @param	int		$id				Cron job ID
	 * @param	string	$securitykey	Security key, mandatory only if the CRON_KEY global constant is set	{@from query}
	 *
	 * @url	POST	{id}/run
	 *
	 * @return	Object
	 *
	 * @throws RestException
	 */
	public function run($id, $securitykey = '')
	{
		if (!DolibarrApiAccess::$user->hasRight('cron', 'write') || !DolibarrApiAccess::$user->admin) {
			throw new RestException(403, "Insufficient rights - running a cron job requires an admin user");
		}

		if (getDolGlobalString('CRON_KEY') && getDolGlobalString('CRON_KEY') != $securitykey) {
			throw new RestException(403, 'Security key is wrong');
		}

		$result = $this->cronjob->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Cron job not found');
		}

		if (!DolibarrApi::_checkAccessToResource('cronjob', $this->cronjob->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$now = dol_now();

		$result = $this->cronjob->run_jobs(DolibarrApiAccess::$user->login);
		if ($result < 0) {
			throw new RestException(500, 'Error when running cron job: '.$this->cronjob->error);
		}

		$result = $this->cronjob->reprogram_jobs(DolibarrApiAccess::$user->login, $now);
		if ($result <= 0) {
			throw new RestException(500, 'Job ran but failed to reprogram next run: '.$this->cronjob->error);
		}

		return $this->_cleanObjectDatas($this->cronjob);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 * Clean sensible object datas
	 * @phpstan-template T
	 *
	 * @param	Object	$object		Object to clean
	 * @return	Object				Object with cleaned properties
	 * @phpstan-param T $object
	 * @phpstan-return T
	 */
	protected function _cleanObjectDatas($object)
	{
		// phpcs:enable
		$object = parent::_cleanObjectDatas($object);

		unset($object->md5params);

		return $object;
	}

	/**
	 * Validate fields before create or update object
	 *
	 * @param ?array<string,string> $data   Array with data to verify
	 * @return array<string,string>
	 * @throws  RestException
	 */
	private function _validate($data)
	{
		if ($data === null) {
			$data = array();
		}
		$cronjob = array();
		foreach (Cronjobs::$FIELDS as $field) {
			if (!isset($data[$field])) {
				throw new RestException(400, $field." field missing");
			}
			$cronjob[$field] = $data[$field];
		}
		return $cronjob;
	}
}
