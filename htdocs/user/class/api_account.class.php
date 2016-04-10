<?php
/* Copyright (C) 2015   Jean-François Ferry     <jfefe@aternatik.fr>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

use Luracast\Restler\RestException;

//require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';

/**
 * API class for user object
 *
 * @smart-auto-routing false
 * @access protected 
 * @class  DolibarrApiAccess {@requires user,external}
 * 
 */
class AccountApi extends DolibarrApi
{
	/**
	 *
	 * @var array   $FIELDS     Mandatory fields, checked when create and update object 
	 */
	static $FIELDS = array(
		'login'
	);

	/**
	 * @var User $account {@type User}
	 */
	public $account;

	/**
	 * Constructor
	 *
	 * @url	account/
	 * 
	 */
	function __construct() {
		global $db, $conf;
		$this->db = $db;
		$this->account = new User($this->db);
	}

	/**
	 * Get properties of an account object
	 *
	 * Return an array with account informations
	 *
	 * @param 	int 	$id ID of account
	 * @return 	array|mixed data without useless information
	 * 
	 * @url	GET account/{user}
	 * @throws 	RestException
	 */
	function get($id) {
		//if (!DolibarrApiAccess::$user->rights->user->lire)
		//{
			//throw new RestException(401);
		//}

		$result = $this->account->fetch($id);
		if (!$result)
		{
			throw new RestException(404, 'User not found');
		}

		if (!DolibarrApi::_checkAccessToResource('user', $this->account->id, 'user'))
		{
			throw new RestException(401, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
		}

		return $this->_cleanObjectDatas($this->account);
	}

	/**
	 * Create account object from contact
	 *
	 * @param   int   $contactid   Id of contact
	 * @param   array   $request_data   Request datas
	 * @return  int     ID of account
     * 
	 * @url	POST /contact/{contactid}/createAccount
	 */
	function createFromContact($contactid, $request_data = NULL) {
		//if (!DolibarrApiAccess::$user->rights->user->creer)
		//{
			//throw new RestException(401);
    //}
    //
    if (!isset($request_data["login"]))
				throw new RestException(400, "login field missing");
    if (!isset($request_data["password"]))
				throw new RestException(400, "password field missing");
    if (!DolibarrApiAccess::$user->rights->societe->contact->lire) {
      throw new RestException(401);
    }
		$contact = new Contact($this->db);
    $contact->fetch($contactid);
    if ($contact->id <= 0) {
      throw new RestException(404, 'Contact not found');
    }

    if (!DolibarrApi::_checkAccessToResource('contact', $contact->id, 'socpeople&societe')) {
      throw new RestException(401, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
    }
    // Check mandatory fields
    $login = $request_data["login"];
    $password = $request_data["password"];
    $result = $this->account->create_from_contact($contact,$login,$password);
    if ($result <= 0) {
      throw new RestException(500, "User not created");
    }
    // password parameter not used in create_from_contact
    $this->account->setPassword($this->account,$password);
    return $result;
	}

	/**
	 * Update account
	 *
	 * @param int   $id             Id of account to update
	 * @param array $request_data   Datas   
	 * @return int 
     * 
	 * @url	PUT account/{id}
	 */
	function put($id, $request_data = NULL) {
		//if (!DolibarrApiAccess::$user->rights->user->creer)
		//{//
			//throw new RestException(401);
		//}

		$result = $this->account->fetch($id);
		if (!$result)
		{
			throw new RestException(404, 'Account not found');
		}

		if (!DolibarrApi::_checkAccessToResource('user', $this->account->id, 'user'))
		{
			throw new RestException(401, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
		}

		foreach ($request_data as $field => $value)
		{
			$this->account->$field = $value;
		}

		if ($this->account->update($id, DolibarrApiAccess::$user, 1, '', '', 'update'))
			return $this->get($id);

		return false;
	}

	/**
	 * Delete account
	 *
	 * @param   int     $id Account ID
	 * @return  array
     * 
	 * @url	DELETE account/{id}
	 */
	function delete($id) {
		//if (!DolibarrApiAccess::$user->rights->user->supprimer)
		//{
			//throw new RestException(401);
		//}
		$result = $this->account->fetch($id);
		if (!$result)
		{
			throw new RestException(404, 'User not found');
		}

		if (!DolibarrApi::_checkAccessToResource('user', $this->account->id, 'user'))
		{
			throw new RestException(401, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
		}

		return $this->account->delete($id);
	}

	/**
	 * Validate fields before create or update object
     * 
	 * @param   array $data Data to validate
	 * @return  array
	 * @throws RestException
	 */
	function _validate($data) {
		$account = array();
		foreach (UserApi::$FIELDS as $field)
		{
			if (!isset($data[$field]))
				throw new RestException(400, "$field field missing");
			$account[$field] = $data[$field];
		}
		return $account;
	}
}
