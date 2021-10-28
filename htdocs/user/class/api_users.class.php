<?php
/* Copyright (C) 2015   Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2020   Thibault FOUCART     	<support@ptibogxiv.net>
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

require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/usergroup.class.php';


/**
 * API class for users
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class Users extends DolibarrApi
{
	/**
	 * @var array   $FIELDS     Mandatory fields, checked when create and update object
	 */
	static $FIELDS = array(
		'login',
	);

	/**
	 * @var User $user {@type User}
	 */
	public $useraccount;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		global $db, $conf;

		$this->db = $db;
		$this->useraccount = new User($this->db);
	}


	/**
	 * List Users
	 *
	 * Get a list of Users
	 *
	 * @param string	$sortfield	Sort field
	 * @param string	$sortorder	Sort order
	 * @param int		$limit		Limit for list
	 * @param int		$page		Page number
	 * @param string   	$user_ids   User ids filter field. Example: '1' or '1,2,3'          {@pattern /^[0-9,]*$/i}
	 * @param int       $category   Use this param to filter list by category
	 * @param string    $sqlfilters Other criteria to filter answers separated by a comma. Syntax example "(t.ref:like:'SO-%') and (t.date_creation:<:'20160101')"
	 * @return  array               Array of User objects
	 */
	public function index($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $user_ids = 0, $category = 0, $sqlfilters = '')
	{
		global $conf;

		if (empty(DolibarrApiAccess::$user->rights->user->user->lire) && empty(DolibarrApiAccess::$user->admin)) {
			throw new RestException(401, "You are not allowed to read list of users");
		}

		$obj_ret = array();

		// case of external user, $societe param is ignored and replaced by user's socid
		//$socid = DolibarrApiAccess::$user->socid ? DolibarrApiAccess::$user->socid : $societe;

		$sql = "SELECT t.rowid";
		$sql .= " FROM ".MAIN_DB_PREFIX."user as t";
		if ($category > 0) {
			$sql .= ", ".MAIN_DB_PREFIX."categorie_user as c";
		}
		$sql .= ' WHERE t.entity IN ('.getEntity('user').')';
		if ($user_ids) {
			$sql .= " AND t.rowid IN (".$this->db->sanitize($user_ids).")";
		}

		// Select products of given category
		if ($category > 0) {
			$sql .= " AND c.fk_categorie = ".((int) $category);
			$sql .= " AND c.fk_user = t.rowid";
		}

		// Add sql filters
		if ($sqlfilters) {
			if (!DolibarrApi::_checkFilters($sqlfilters)) {
				throw new RestException(503, 'Error when validating parameter sqlfilters '.$sqlfilters);
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

		if ($result) {
			$i = 0;
			$num = $this->db->num_rows($result);
			$min = min($num, ($limit <= 0 ? $num : $limit));
			while ($i < $min) {
				$obj = $this->db->fetch_object($result);
				$user_static = new User($this->db);
				if ($user_static->fetch($obj->rowid)) {
					$obj_ret[] = $this->_cleanObjectDatas($user_static);
				}
				$i++;
			}
		} else {
			throw new RestException(503, 'Error when retrieve User list : '.$this->db->lasterror());
		}
		if (!count($obj_ret)) {
			throw new RestException(404, 'No User found');
		}
		return $obj_ret;
	}

	/**
	 * Get properties of an user object
	 *
	 * @param 	int 	$id 					ID of user
	 * @param	int		$includepermissions		Set this to 1 to have the array of permissions loaded (not done by default for performance purpose)
	 * @return 	array|mixed 					data without useless information
	 *
	 * @throws RestException 401 Insufficient rights
	 * @throws RestException 404 User or group not found
	 */
	public function get($id, $includepermissions = 0)
	{
		if (empty(DolibarrApiAccess::$user->rights->user->user->lire) && empty(DolibarrApiAccess::$user->admin)) {
			throw new RestException(401, 'Not allowed');
		}

		if ($id == 0) {
			$result = $this->useraccount->initAsSpecimen();
		} else {
			$result = $this->useraccount->fetch($id);
		}
		if (!$result) {
			throw new RestException(404, 'User not found');
		}

		if ($id > 0 && !DolibarrApi::_checkAccessToResource('user', $this->useraccount->id, 'user')) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		if ($includepermissions) {
			$this->useraccount->getRights();
		}

		return $this->_cleanObjectDatas($this->useraccount);
	}

	/**
	 * Get properties of an user object by login
	 *
	 * @param 	string 	$login 					Login of user
	 * @param	int		$includepermissions		Set this to 1 to have the array of permissions loaded (not done by default for performance purpose)
	 * @return 	array|mixed 					Data without useless information
	 *
	 * @url GET login/{login}
	 *
	 * @throws RestException 401 Insufficient rights
	 * @throws RestException 404 User or group not found
	 */
	public function getByLogin($login, $includepermissions = 0)
	{
		if (empty(DolibarrApiAccess::$user->rights->user->user->lire) && empty(DolibarrApiAccess::$user->admin)) {
			throw new RestException(401, 'Not allowed');
		}

		$result = $this->useraccount->fetch('', $login);
		if (!$result) {
			throw new RestException(404, 'User not found');
		}

		if (!DolibarrApi::_checkAccessToResource('user', $this->useraccount->id, 'user')) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		if ($includepermissions) {
			$this->useraccount->getRights();
		}

		return $this->_cleanObjectDatas($this->useraccount);
	}

	/**
	 * Get properties of an user object by Email
	 *
	 * @param 	string 	$email 					Email of user
	 * @param	int		$includepermissions		Set this to 1 to have the array of permissions loaded (not done by default for performance purpose)
	 * @return 	array|mixed 					Data without useless information
	 *
	 * @url GET email/{email}
	 *
	 * @throws RestException 401     Insufficient rights
	 * @throws RestException 404     User or group not found
	 */
	public function getByEmail($email, $includepermissions = 0)
	{
		if (empty(DolibarrApiAccess::$user->rights->user->user->lire) && empty(DolibarrApiAccess::$user->admin)) {
			throw new RestException(401, 'Not allowed');
		}

		$result = $this->useraccount->fetch('', '', '', 0, -1, $email);
		if (!$result) {
			throw new RestException(404, 'User not found');
		}

		if (!DolibarrApi::_checkAccessToResource('user', $this->useraccount->id, 'user')) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		if ($includepermissions) {
			$this->useraccount->getRights();
		}

		return $this->_cleanObjectDatas($this->useraccount);
	}

	/**
	 * Get more properties of a user
	 *
	 * @url	GET /info
	 *
	 * @param	int			$includepermissions		Set this to 1 to have the array of permissions loaded (not done by default for performance purpose)
	 * @return  array|mixed 						Data without useless information
	 *
	 * @throws RestException 401     Insufficient rights
	 * @throws RestException 404     User or group not found
	 */
	public function getInfo($includepermissions = 0)
	{
		if (empty(DolibarrApiAccess::$user->rights->user->user->lire) && empty(DolibarrApiAccess::$user->admin)) {
			throw new RestException(401, 'Not allowed');
		}

		$apiUser = DolibarrApiAccess::$user;

		$result = $this->useraccount->fetch($apiUser->id);
		if (!$result) {
			throw new RestException(404, 'User not found');
		}

		if (!DolibarrApi::_checkAccessToResource('user', $this->useraccount->id, 'user')) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		if ($includepermissions) {
			$this->useraccount->getRights();
		}

		$usergroup = new UserGroup($this->db);
		$userGroupList = $usergroup->listGroupsForUser($apiUser->id, false);
		if (!is_array($userGroupList)) {
			throw new RestException(404, 'User group not found');
		}

		$this->useraccount->user_group_list = $this->_cleanUserGroupListDatas($userGroupList);

		return $this->_cleanObjectDatas($this->useraccount);
	}

	/**
	 * Create user account
	 *
	 * @param array $request_data New user data
	 * @return int
	 *
	 * @throws RestException 401 Not allowed
	 */
	public function post($request_data = null)
	{
		// Check user authorization
		if (empty(DolibarrApiAccess::$user->rights->user->creer) && empty(DolibarrApiAccess::$user->admin)) {
			throw new RestException(401, "User creation not allowed for login ".DolibarrApiAccess::$user->login);
		}

		// check mandatory fields
		/*if (!isset($request_data["login"]))
			throw new RestException(400, "login field missing");
		if (!isset($request_data["password"]))
			throw new RestException(400, "password field missing");
		if (!isset($request_data["lastname"]))
			 throw new RestException(400, "lastname field missing");*/

		//assign field values
		foreach ($request_data as $field => $value) {
			if (in_array($field, array('pass_crypted', 'pass_indatabase', 'pass_indatabase_crypted', 'pass_temp', 'api_key'))) {
				// This properties can't be set/modified with API
				throw new RestException(401, 'The property '.$field." can't be set/modified using the APIs");
			}
			/*if ($field == 'pass') {
				if (empty(DolibarrApiAccess::$user->rights->user->user->password)) {
					throw new RestException(401, 'You are not allowed to modify/set password of other users');
					continue;
				}
			}
			*/

			$this->useraccount->$field = $value;
		}

		if ($this->useraccount->create(DolibarrApiAccess::$user) < 0) {
			throw new RestException(500, 'Error creating', array_merge(array($this->useraccount->error), $this->useraccount->errors));
		}
		return $this->useraccount->id;
	}


	/**
	 * Update user account
	 *
	 * @param 	int   		$id             	Id of account to update
	 * @param	array 		$request_data   	Datas
	 * @return 	array|mixed						Record after update
	 *
	 * @throws RestException 401 Not allowed
	 * @throws RestException 404 Not found
	 * @throws RestException 500 System error
	 */
	public function put($id, $request_data = null)
	{
		// Check user authorization
		if (empty(DolibarrApiAccess::$user->rights->user->user->creer) && empty(DolibarrApiAccess::$user->admin)) {
			throw new RestException(401, "User update not allowed");
		}

		$result = $this->useraccount->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Account not found');
		}

		if (!DolibarrApi::_checkAccessToResource('user', $this->useraccount->id, 'user')) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		foreach ($request_data as $field => $value) {
			if ($field == 'id') {
				continue;
			}
			if (in_array($field, array('pass_crypted', 'pass_indatabase', 'pass_indatabase_crypted', 'pass_temp', 'api_key'))) {
				// This properties can't be set/modified with API
				throw new RestException(401, 'The property '.$field." can't be set/modified using the APIs");
			}
			if ($field == 'pass') {
				if ($this->useraccount->id != DolibarrApiAccess::$user->id && empty(DolibarrApiAccess::$user->rights->user->user->password)) {
					throw new RestException(401, 'You are not allowed to modify password of other users');
				}
				if ($this->useraccount->id == DolibarrApiAccess::$user->id && empty(DolibarrApiAccess::$user->rights->user->self->password)) {
					throw new RestException(401, 'You are not allowed to modify your own password');
				}
			}
			if (DolibarrApiAccess::$user->admin) {	// If user for API is admin
				if ($field == 'admin' && $value != $this->useraccount->admin && empty($value)) {
					throw new RestException(401, 'Reseting the admin status of a user is not possible using the API');
				}
			} else {
				if ($field == 'admin' && $value != $this->useraccount->admin) {
					throw new RestException(401, 'Only an admin user can modify the admin status of another user');
				}
			}
			if ($field == 'entity' && $value != $this->useraccount->entity) {
				throw new RestException(401, 'Changing entity of a user using the APIs is not possible');
			}
			// The status must be updated using setstatus() because it
			// is not handled by the update() method.
			if ($field == 'statut') {
				$result = $this->useraccount->setstatus($value);
				if ($result < 0) {
					throw new RestException(500, 'Error when updating status of user: '.$this->useraccount->error);
				}
			} else {
				$this->useraccount->$field = $value;
			}
		}

		// If there is no error, update() returns the number of affected
		// rows so if the update is a no op, the return value is zezo.
		if ($this->useraccount->update(DolibarrApiAccess::$user) >= 0) {
			return $this->get($id);
		} else {
			throw new RestException(500, $this->useraccount->error);
		}
	}


	/**
	 * List the groups of a user
	 *
	 * @param int $id     Id of user
	 * @return array      Array of group objects
	 *
	 * @throws RestException 403 Not allowed
	 * @throws RestException 404 Not found
	 *
	 * @url GET {id}/groups
	 */
	public function getGroups($id)
	{
		if (empty(DolibarrApiAccess::$user->rights->user->user->lire) && empty(DolibarrApiAccess::$user->admin)) {
			throw new RestException(403);
		}

		$obj_ret = array();

		$user = new User($this->db);
		$result = $user->fetch($id);
		if (!$result) {
			throw new RestException(404, 'user not found');
		}

		$usergroup = new UserGroup($this->db);
		$groups = $usergroup->listGroupsForUser($id, false);
		$obj_ret = array();
		foreach ($groups as $group) {
			$obj_ret[] = $this->_cleanObjectDatas($group);
		}
		return $obj_ret;
	}


	/**
	 * Add a user into a group
	 *
	 * @param   int     $id        User ID
	 * @param   int     $group     Group ID
	 * @param   int     $entity    Entity ID (valid only for superadmin in multicompany transverse mode)
	 * @return  int                1 if success
	 *
	 * @throws RestException 401 Not allowed
	 * @throws RestException 404 User not found
	 * @throws RestException 500 Error
	 *
	 * @url	GET {id}/setGroup/{group}
	 */
	public function setGroup($id, $group, $entity = 1)
	{
		global $conf;

		if (empty(DolibarrApiAccess::$user->rights->user->user->creer) && empty(DolibarrApiAccess::$user->admin)) {
			throw new RestException(401);
		}

		$result = $this->useraccount->fetch($id);
		if (!$result) {
			throw new RestException(404, 'User not found');
		}

		if (!DolibarrApi::_checkAccessToResource('user', $this->useraccount->id, 'user')) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		if (!empty($conf->multicompany->enabled) && !empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE) && !empty(DolibarrApiAccess::$user->admin) && empty(DolibarrApiAccess::$user->entity)) {
			$entity = (!empty($entity) ? $entity : $conf->entity);
		} else {
			// When using API, action is done on entity of logged user because a user of entity X with permission to create user should not be able to
			// hack the security by giving himself permissions on another entity.
			$entity = (DolibarrApiAccess::$user->entity > 0 ? DolibarrApiAccess::$user->entity : $conf->entity);
		}

		$result = $this->useraccount->SetInGroup($group, $entity);
		if (!($result > 0)) {
			throw new RestException(500, $this->useraccount->error);
		}

		return 1;
	}

	/**
	 * List Groups
	 *
	 * Return an array with a list of Groups
	 *
	 * @url	GET /groups
	 *
	 * @param string	$sortfield	Sort field
	 * @param string	$sortorder	Sort order
	 * @param int		$limit		Limit for list
	 * @param int		$page		Page number
	 * @param string   	$group_ids   Groups ids filter field. Example: '1' or '1,2,3'          {@pattern /^[0-9,]*$/i}
	 * @param string    $sqlfilters Other criteria to filter answers separated by a comma. Syntax example "(t.ref:like:'SO-%') and (t.date_creation:<:'20160101')"
	 * @return  array               Array of User objects
	 *
	 * @throws RestException 404 User not found
	 * @throws RestException 503 Error
	 */
	public function listGroups($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $group_ids = 0, $sqlfilters = '')
	{
		global $conf;

		$obj_ret = array();

		if ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty(DolibarrApiAccess::$user->rights->user->user->lire) && empty(DolibarrApiAccess::$user->admin)) ||
			!empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty(DolibarrApiAccess::$user->rights->user->group_advance->read) && empty(DolibarrApiAccess::$user->admin)) {
			throw new RestException(401, "You are not allowed to read groups");
		}

		// case of external user, $societe param is ignored and replaced by user's socid
		//$socid = DolibarrApiAccess::$user->socid ? DolibarrApiAccess::$user->socid : $societe;

		$sql = "SELECT t.rowid";
		$sql .= " FROM ".MAIN_DB_PREFIX."usergroup as t";
		$sql .= ' WHERE t.entity IN ('.getEntity('user').')';
		if ($group_ids) {
			$sql .= " AND t.rowid IN (".$this->db->sanitize($group_ids).")";
		}
		// Add sql filters
		if ($sqlfilters) {
			if (!DolibarrApi::_checkFilters($sqlfilters)) {
				throw new RestException(503, 'Error when validating parameter sqlfilters '.$sqlfilters);
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

		if ($result) {
			$i = 0;
			$num = $this->db->num_rows($result);
			$min = min($num, ($limit <= 0 ? $num : $limit));
			while ($i < $min) {
				$obj = $this->db->fetch_object($result);
				$group_static = new UserGroup($this->db);
				if ($group_static->fetch($obj->rowid)) {
					$obj_ret[] = $this->_cleanObjectDatas($group_static);
				}
				$i++;
			}
		} else {
			throw new RestException(503, 'Error when retrieve Group list : '.$this->db->lasterror());
		}
		if (!count($obj_ret)) {
			throw new RestException(404, 'No Group found');
		}
		return $obj_ret;
	}

	/**
	 * Get properties of an group object
	 *
	 * Return an array with group informations
	 *
	 * @url	GET /groups/{group}
	 *
	 * @param 	int 	$group ID of group
	 * @param int       $load_members     Load members list or not {@min 0} {@max 1}
	 * @return  array               Array of User objects
	 *
	 * @throws RestException 401 Not allowed
	 * @throws RestException 404 User not found
	 */
	public function infoGroups($group, $load_members = 0)
	{
		global $db, $conf;

		if ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty(DolibarrApiAccess::$user->rights->user->user->lire) && empty(DolibarrApiAccess::$user->admin)) ||
			!empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty(DolibarrApiAccess::$user->rights->user->group_advance->read) && empty(DolibarrApiAccess::$user->admin)) {
			throw new RestException(401, "You are not allowed to read groups");
		}

		$group_static = new UserGroup($this->db);
		$result = $group_static->fetch($group, '', $load_members);

		if (!$result) {
			throw new RestException(404, 'Group not found');
		}

		return $this->_cleanObjectDatas($group_static);
	}

	/**
	 * Delete account/user
	 *
	 * @param   int     $id Account ID
	 * @return  array
	 *
	 * @throws RestException 401 Not allowed
	 * @throws RestException 404 User not found
	 */
	public function delete($id)
	{
		if (empty(DolibarrApiAccess::$user->rights->user->user->supprimer) && empty(DolibarrApiAccess::$user->admin)) {
			throw new RestException(401, 'Not allowed');
		}
		$result = $this->useraccount->fetch($id);
		if (!$result) {
			throw new RestException(404, 'User not found');
		}

		if (!DolibarrApi::_checkAccessToResource('user', $this->useraccount->id, 'user')) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}
		$this->useraccount->oldcopy = clone $this->useraccount;
		return $this->useraccount->delete(DolibarrApiAccess::$user);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 * Clean sensible object datas
	 *
	 * @param   Object	$object    	Object to clean
	 * @return  Object    			Object with cleaned properties
	 */
	protected function _cleanObjectDatas($object)
	{
		// phpcs:enable
		global $conf;

		$object = parent::_cleanObjectDatas($object);

		unset($object->default_values);
		unset($object->lastsearch_values);
		unset($object->lastsearch_values_tmp);

		unset($object->total_ht);
		unset($object->total_tva);
		unset($object->total_localtax1);
		unset($object->total_localtax2);
		unset($object->total_ttc);

		unset($object->label_incoterms);
		unset($object->location_incoterms);

		unset($object->fk_delivery_address);
		unset($object->fk_incoterms);
		unset($object->all_permissions_are_loaded);
		unset($object->shipping_method_id);
		unset($object->nb_rights);
		unset($object->search_sid);
		unset($object->ldap_sid);
		unset($object->clicktodial_loaded);

		// List of properties never returned by API, whatever are permissions
		unset($object->pass);
		unset($object->pass_indatabase);
		unset($object->pass_indatabase_crypted);
		unset($object->pass_temp);
		unset($object->api_key);
		unset($object->clicktodial_password);
		unset($object->openid);

		unset($object->lines);
		unset($object->model_pdf);
		unset($object->skype);
		unset($object->twitter);
		unset($object->facebook);
		unset($object->linkedin);

		$canreadsalary = ((!empty($conf->salaries->enabled) && !empty(DolibarrApiAccess::$user->rights->salaries->read)) || (empty($conf->salaries->enabled)));

		if (!$canreadsalary) {
			unset($object->salary);
			unset($object->salaryextra);
			unset($object->thm);
			unset($object->tjm);
		}

		return $object;
	}

	/**
	 * Clean sensible user group list datas
	 *
	 * @param   array  $objectList   Array of object to clean
	 * @return  array                Array of cleaned object properties
	 */
	private function _cleanUserGroupListDatas($objectList)
	{
		$cleanObjectList = array();

		foreach ($objectList as $object) {
			$cleanObject = parent::_cleanObjectDatas($object);

			unset($cleanObject->default_values);
			unset($cleanObject->lastsearch_values);
			unset($cleanObject->lastsearch_values_tmp);

			unset($cleanObject->total_ht);
			unset($cleanObject->total_tva);
			unset($cleanObject->total_localtax1);
			unset($cleanObject->total_localtax2);
			unset($cleanObject->total_ttc);

			unset($cleanObject->libelle_incoterms);
			unset($cleanObject->location_incoterms);

			unset($cleanObject->fk_delivery_address);
			unset($cleanObject->fk_incoterms);
			unset($cleanObject->all_permissions_are_loaded);
			unset($cleanObject->shipping_method_id);
			unset($cleanObject->nb_rights);
			unset($cleanObject->search_sid);
			unset($cleanObject->ldap_sid);
			unset($cleanObject->clicktodial_loaded);

			unset($cleanObject->datec);
			unset($cleanObject->datem);
			unset($cleanObject->members);
			unset($cleanObject->note);
			unset($cleanObject->note_private);

			$cleanObjectList[] = $cleanObject;
		}

		return $cleanObjectList;
	}

	/**
	 * Validate fields before create or update object
	 *
	 * @param   array|null     $data   Data to validate
	 * @return  array
	 * @throws RestException
	 */
	private function _validate($data)
	{
		$account = array();
		foreach (Users::$FIELDS as $field) {
			if (!isset($data[$field])) {
				throw new RestException(400, "$field field missing");
			}
			$account[$field] = $data[$field];
		}
		return $account;
	}
}
