<?php
/* Copyright (C) 2015       Jean-François Ferry         <jfefe@aternatik.fr>
 * Copyright (C) 2019       Frédéric France             <frederic.france@netlogic.fr>
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

//require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
//require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';


/**
 * API class for contacts
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class Contacts extends DolibarrApi
{
	/**
	 *
	 * @var array   $FIELDS     Mandatory fields, checked when create and update object
	 */
	public static $FIELDS = array(
		'lastname',
	);

	/**
	 * @var Contact $contact {@type Contact}
	 */
	public $contact;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		global $db, $conf;
		$this->db = $db;

		require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
		require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';

		$this->contact = new Contact($this->db);
	}

	/**
	 * Get properties of a contact object
	 *
	 * Return an array with contact informations
	 *
	 * @param 	int    $id                  ID of contact
	 * @param   int    $includecount        Count and return also number of elements the contact is used as a link for
	 * @param   int    $includeroles        Includes roles of the contact
	 * @return 	array|mixed data without useless information
	 *
	 * @throws 	RestException
	 */
	public function get($id, $includecount = 0, $includeroles = 0)
	{
		if (!DolibarrApiAccess::$user->rights->societe->contact->lire) {
			throw new RestException(401, 'No permission to read contacts');
		}

		if ($id === 0) {
			$result = $this->contact->initAsSpecimen();
		} else {
			$result = $this->contact->fetch($id);
		}

		if (!$result) {
			throw new RestException(404, 'Contact not found');
		}

		if (!DolibarrApi::_checkAccessToResource('contact', $this->contact->id, 'socpeople&societe')) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		if ($includecount) {
			$this->contact->load_ref_elements();
		}

		if ($includeroles) {
			$this->contact->fetchRoles();
		}

		return $this->_cleanObjectDatas($this->contact);
	}

	/**
	 * Get properties of a contact object by Email
	 *
	 * @param 	string 	$email 					Email of contact
	 * @param   int    $includecount        Count and return also number of elements the contact is used as a link for
	 * @param   int    $includeroles        Includes roles of the contact
	 * @return 	array|mixed data without useless information
	 *
	 * @url GET email/{email}
	 *
	 * @throws RestException 401     Insufficient rights
	 * @throws RestException 404     User or group not found
	 */
	public function getByEmail($email, $includecount = 0, $includeroles = 0)
	{
		if (!DolibarrApiAccess::$user->rights->societe->contact->lire) {
			throw new RestException(401, 'No permission to read contacts');
		}

		if (empty($email)) {
			$result = $this->contact->initAsSpecimen();
		} else {
			$result = $this->contact->fetch('', '', '', $email);
		}

		if (!$result) {
			throw new RestException(404, 'Contact not found');
		}

		if (!DolibarrApi::_checkAccessToResource('contact', $this->contact->id, 'socpeople&societe')) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		if ($includecount) {
			$this->contact->load_ref_elements();
		}

		if ($includeroles) {
			$this->contact->fetchRoles();
		}

		return $this->_cleanObjectDatas($this->contact);
	}

	/**
	 * List contacts
	 *
	 * Get a list of contacts
	 *
	 * @param string	$sortfield	        Sort field
	 * @param string	$sortorder	        Sort order
	 * @param int		$limit		        Limit for list
	 * @param int		$page		        Page number
	 * @param string   	$thirdparty_ids	    Thirdparty ids to filter contacts of (example '1' or '1,2,3') {@pattern /^[0-9,]*$/i}
	 * @param int    	$category   Use this param to filter list by category
	 * @param string    $sqlfilters         Other criteria to filter answers separated by a comma. Syntax example "(t.ref:like:'SO-%') and (t.date_creation:<:'20160101')"
	 * @param int       $includecount       Count and return also number of elements the contact is used as a link for
	 * @param int    	$includeroles        Includes roles of the contact
	 * @return array                        Array of contact objects
	 *
	 * @throws RestException
	 */
	public function index($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $thirdparty_ids = '', $category = 0, $sqlfilters = '', $includecount = 0, $includeroles = 0)
	{
		global $db, $conf;

		$obj_ret = array();

		if (!DolibarrApiAccess::$user->rights->societe->contact->lire) {
			throw new RestException(401, 'No permission to read contacts');
		}

		// case of external user, $thirdparty_ids param is ignored and replaced by user's socid
		$socids = DolibarrApiAccess::$user->socid ? DolibarrApiAccess::$user->socid : $thirdparty_ids;

		// If the internal user must only see his customers, force searching by him
		$search_sale = 0;
		if (!DolibarrApiAccess::$user->rights->societe->client->voir && !$socids) {
			$search_sale = DolibarrApiAccess::$user->id;
		}

		$sql = "SELECT t.rowid";
		$sql .= " FROM ".MAIN_DB_PREFIX."socpeople as t";
		if ($category > 0) {
			$sql .= ", ".MAIN_DB_PREFIX."categorie_contact as c";
		}
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."socpeople_extrafields as te ON te.fk_object = t.rowid";
		if ((!DolibarrApiAccess::$user->rights->societe->client->voir && !$socids) || $search_sale > 0) {
			// We need this table joined to the select in order to filter by sale
			$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		}
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON t.fk_soc = s.rowid";
		$sql .= ' WHERE t.entity IN ('.getEntity('socpeople').')';
		if ($socids) {
			$sql .= " AND t.fk_soc IN (".$this->db->sanitize($socids).")";
		}

		if ((!DolibarrApiAccess::$user->rights->societe->client->voir && !$socids) || $search_sale > 0) {
			$sql .= " AND t.fk_soc = sc.fk_soc";
		}
		if ($search_sale > 0) {
			$sql .= " AND s.rowid = sc.fk_soc"; // Join for the needed table to filter by sale
		}
		// Insert sale filter
		if ($search_sale > 0) {
			$sql .= " AND sc.fk_user = ".((int) $search_sale);
		}

		// Select contacts of given category
		if ($category > 0) {
			$sql .= " AND c.fk_categorie = ".((int) $category);
			$sql .= " AND c.fk_socpeople = t.rowid ";
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
			$num = $this->db->num_rows($result);
			$min = min($num, ($limit <= 0 ? $num : $limit));
			$i = 0;
			while ($i < $min) {
				$obj = $this->db->fetch_object($result);
				$contact_static = new Contact($this->db);
				if ($contact_static->fetch($obj->rowid)) {
					$contact_static->fetchRoles();
					if ($includecount) {
						$contact_static->load_ref_elements();
					}
					if ($includeroles) {
						$contact_static->fetchRoles();
					}

					$obj_ret[] = $this->_cleanObjectDatas($contact_static);
				}

				$i++;
			}
		} else {
			throw new RestException(503, 'Error when retrieve contacts : '.$sql);
		}
		if (!count($obj_ret)) {
			throw new RestException(404, 'Contacts not found');
		}
		return $obj_ret;
	}

	/**
	 * Create contact object
	 *
	 * @param   array   $request_data   Request datas
	 * @return  int     ID of contact
	 */
	public function post($request_data = null)
	{
		if (!DolibarrApiAccess::$user->rights->societe->contact->creer) {
			throw new RestException(401, 'No permission to create/update contacts');
		}
		// Check mandatory fields
		$result = $this->_validate($request_data);

		foreach ($request_data as $field => $value) {
			$this->contact->$field = $value;
		}
		if ($this->contact->create(DolibarrApiAccess::$user) < 0) {
			throw new RestException(500, "Error creating contact", array_merge(array($this->contact->error), $this->contact->errors));
		}
		return $this->contact->id;
	}

	/**
	 * Update contact
	 *
	 * @param int   $id             Id of contact to update
	 * @param array $request_data   Datas
	 * @return int
	 */
	public function put($id, $request_data = null)
	{
		if (!DolibarrApiAccess::$user->rights->societe->contact->creer) {
			throw new RestException(401, 'No permission to create/update contacts');
		}

		$result = $this->contact->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Contact not found');
		}

		if (!DolibarrApi::_checkAccessToResource('contact', $this->contact->id, 'socpeople&societe')) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		foreach ($request_data as $field => $value) {
			if ($field == 'id') {
				continue;
			}
			$this->contact->$field = $value;
		}

		if ($this->contact->update($id, DolibarrApiAccess::$user, 1, '', '', 'update')) {
			return $this->get($id);
		}

		return false;
	}

	/**
	 * Delete contact
	 *
	 * @param   int     $id Contact ID
	 * @return  integer
	 */
	public function delete($id)
	{
		if (!DolibarrApiAccess::$user->rights->societe->contact->supprimer) {
			throw new RestException(401, 'No permission to delete contacts');
		}
		$result = $this->contact->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Contact not found');
		}

		if (!DolibarrApi::_checkAccessToResource('contact', $this->contact->id, 'socpeople&societe')) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}
		$this->contact->oldcopy = clone $this->contact;
		return $this->contact->delete();
	}

	/**
	 * Create an user account object from contact (external user)
	 *
	 * @param   int   	$id   Id of contact
	 * @param   array   $request_data   Request datas
	 * @return  int     ID of user
	 *
	 * @url	POST {id}/createUser
	 */
	public function createUser($id, $request_data = null)
	{
		//if (!DolibarrApiAccess::$user->rights->user->user->creer) {
		//throw new RestException(401);
		//}

		if (!isset($request_data["login"])) {
			throw new RestException(400, "login field missing");
		}
		if (!isset($request_data["password"])) {
			throw new RestException(400, "password field missing");
		}

		if (!DolibarrApiAccess::$user->rights->societe->contact->lire) {
			throw new RestException(401, 'No permission to read contacts');
		}
		if (!DolibarrApiAccess::$user->rights->user->user->creer) {
			throw new RestException(401, 'No permission to create user');
		}

		$contact = new Contact($this->db);
		$contact->fetch($id);
		if ($contact->id <= 0) {
			throw new RestException(404, 'Contact not found');
		}

		if (!DolibarrApi::_checkAccessToResource('contact', $contact->id, 'socpeople&societe')) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		// Check mandatory fields
		$login = $request_data["login"];
		$password = $request_data["password"];
		$useraccount = new User($this->db);
		$result = $useraccount->create_from_contact($contact, $login, $password);
		if ($result <= 0) {
			throw new RestException(500, "User not created");
		}
		// password parameter not used in create_from_contact
		$useraccount->setPassword($useraccount, $password);

		return $result;
	}

	/**
	 * Get categories for a contact
	 *
	 * @param int		$id         ID of contact
	 * @param string	$sortfield	Sort field
	 * @param string	$sortorder	Sort order
	 * @param int		$limit		Limit for list
	 * @param int		$page		Page number
	 *
	 * @return mixed
	 *
	 * @url GET {id}/categories
	 */
	public function getCategories($id, $sortfield = "s.rowid", $sortorder = 'ASC', $limit = 0, $page = 0)
	{
		if (!DolibarrApiAccess::$user->rights->categorie->lire) {
			throw new RestException(401);
		}

		$categories = new Categorie($this->db);

		$result = $categories->getListForItem($id, 'contact', $sortfield, $sortorder, $limit, $page);

		if (empty($result)) {
			throw new RestException(404, 'No category found');
		}

		if ($result < 0) {
			throw new RestException(503, 'Error when retrieve category list : '.$categories->error);
		}

		return $result;
	}

	/**
	 * Add a category to a contact
	 *
	 * @url POST {id}/categories/{category_id}
	 *
	 * @param   int		$id             Id of contact
	 * @param   int     $category_id    Id of category
	 *
	 * @return  mixed
	 *
	 * @throws RestException 401 Insufficient rights
	 * @throws RestException 404 Category or contact not found
	 */
	public function addCategory($id, $category_id)
	{
		if (!DolibarrApiAccess::$user->rights->societe->contact->creer) {
			throw new RestException(401, 'Insufficient rights');
		}

		$result = $this->contact->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Contact not found');
		}
		$category = new Categorie($this->db);
		$result = $category->fetch($category_id);
		if (!$result) {
			throw new RestException(404, 'category not found');
		}

		if (!DolibarrApi::_checkAccessToResource('contact', $this->contact->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}
		if (!DolibarrApi::_checkAccessToResource('category', $category->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$category->add_type($this->contact, 'contact');

		return $this->_cleanObjectDatas($this->contact);
	}

	/**
	 * Remove the link between a category and a contact
	 *
	 * @url DELETE {id}/categories/{category_id}
	 *
	 * @param   int		$id				Id of contact
	 * @param   int		$category_id	Id of category
	 * @return  mixed
	 *
	 * @throws  RestException 401     Insufficient rights
	 * @throws  RestException 404     Category or contact not found
	 */
	public function deleteCategory($id, $category_id)
	{
		if (!DolibarrApiAccess::$user->rights->societe->contact->creer) {
			throw new RestException(401, 'Insufficient rights');
		}

		$result = $this->contact->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Contact not found');
		}
		$category = new Categorie($this->db);
		$result = $category->fetch($category_id);
		if (!$result) {
			throw new RestException(404, 'category not found');
		}

		if (!DolibarrApi::_checkAccessToResource('contact', $this->contact->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}
		if (!DolibarrApi::_checkAccessToResource('category', $category->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$category->del_type($this->contact, 'contact');

		return $this->_cleanObjectDatas($this->contact);
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

		unset($object->total_ht);
		unset($object->total_tva);
		unset($object->total_localtax1);
		unset($object->total_localtax2);
		unset($object->total_ttc);

		unset($object->note);
		unset($object->lines);
		unset($object->thirdparty);

		return $object;
	}

	/**
	 * Validate fields before create or update object
	 *
	 * @param   array|null     $data   Data to validate
	 * @return  array
	 * @throws  RestException
	 */
	private function _validate($data)
	{
		$contact = array();
		foreach (Contacts::$FIELDS as $field) {
			if (!isset($data[$field])) {
				throw new RestException(400, "$field field missing");
			}
			$contact[$field] = $data[$field];
		}

		return $contact;
	}
}
