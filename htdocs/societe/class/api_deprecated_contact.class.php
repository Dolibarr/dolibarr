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

require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';

/**
 * API class for contact object
 *
 * @smart-auto-routing false
 * @access protected 
 * @class  DolibarrApiAccess {@requires user,external}
 * @deprecated Use Contacts instead (defined in api_contacts.class.php)
 */
class ContactApi extends DolibarrApi
{
	/**
	 *
	 * @var array   $FIELDS     Mandatory fields, checked when create and update object 
	 */
	static $FIELDS = array(
		'lastname'
	);

	/**
	 * @var Contact $contact {@type Contact}
	 */
	public $contact;

	/**
	 * Constructor <b>Warning: Deprecated</b>
	 *
	 * @url	contact/
	 * 
	 */
	function __construct() {
		global $db, $conf;
		$this->db = $db;
		$this->contact = new Contact($this->db);
	}

	/**
	 * Get properties of a contact object <b>Warning: Deprecated</b>
	 *
	 * Return an array with contact informations
	 *
	 * @param 	int 	$id ID of contact
	 * @return 	array|mixed data without useless information
	 * 
	 * @url	GET contact/{id}
	 * @throws 	RestException
	 */
	function get($id) {
		if (!DolibarrApiAccess::$user->rights->societe->contact->lire)
		{
			throw new RestException(401);
		}

		$result = $this->contact->fetch($id);
		if (!$result)
		{
			throw new RestException(404, 'Contact not found');
		}

		if (!DolibarrApi::_checkAccessToResource('contact', $this->contact->id, 'socpeople&societe'))
		{
			throw new RestException(401, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
		}

		return $this->_cleanObjectDatas($this->contact);
	}

	/**
	 * List contacts <b>Warning: Deprecated</b>
	 * 
	 * Get a list of contacts
	 * 
	 * @param int		$socid		ID of thirdparty to filter list
	 * @param string	$sortfield	Sort field
	 * @param string	$sortorder	Sort order
	 * @param int		$limit		Limit for list
	 * @param int		$page		Page number
	 * @return array Array of contact objects
	 *
	 * @url	GET /contact/list
	 * @url	GET /contact/list/{socid}
	 * @url	GET	/thirdparty/{socid}/contacts
	 * @url	GET	/customer/{socid}/contacts
     * 
	 * @throws RestException
	 */
	function getList($socid = 0, $sortfield = "c.rowid", $sortorder = 'ASC', $limit = 0, $page = 0) {
		global $db, $conf;

		$obj_ret = array();

		if (!$socid)
		{
			$socid = DolibarrApiAccess::$user->societe_id ? DolibarrApiAccess::$user->societe_id : '';
		}

		$search_sale = 0;
		// If the internal user must only see his customers, force searching by him
		if (!DolibarrApiAccess::$user->rights->societe->client->voir && !$socid)
			$search_sale = DolibarrApiAccess::$user->id;

		$sql = "SELECT c.rowid";
		$sql.= " FROM " . MAIN_DB_PREFIX . "socpeople as c";
		if ((!DolibarrApiAccess::$user->rights->societe->client->voir && !$socid) || $search_sale > 0) {
			// We need this table joined to the select in order to filter by sale
			$sql.= ", " . MAIN_DB_PREFIX . "societe_commerciaux as sc"; 
		}
		$sql.= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as s ON c.fk_soc = s.rowid";
		$sql.= ' WHERE  c.entity IN (' . getEntity('socpeople', 1) . ')';
		if ($socid)
			$sql.= " AND c.fk_soc = " . $socid;

		if ((!DolibarrApiAccess::$user->rights->societe->client->voir && !$socid) || $search_sale > 0)
			$sql.= " AND c.fk_soc = sc.fk_soc";
		if ($search_sale > 0)
			$sql.= " AND s.rowid = sc.fk_soc";  // Join for the needed table to filter by sale


			
		// Insert sale filter
		if ($search_sale > 0)
		{
			$sql .= " AND sc.fk_user = " . $search_sale;
		}

		$nbtotalofrecords = '';
		if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
		{
			$result = $db->query($sql);
			$nbtotalofrecords = $db->num_rows($result);
		}

		$sql.= $db->order($sortfield, $sortorder);

		if ($limit)
		{
			if ($page < 0)
			{
				$page = 0;
			}
			$offset = $limit * $page;

			$sql.= $db->plimit($limit + 1, $offset);
		}
		$result = $db->query($sql);
		if ($result)
		{
			$i = 0;
		    $num = $db->num_rows($result);
			while ($i < min($num, ($limit <= 0 ? $num : $limit)))
			{
				$obj = $db->fetch_object($result);
				$contact_static = new Contact($db);
				if ($contact_static->fetch($obj->rowid))
				{
					$obj_ret[] = $this->_cleanObjectDatas($contact_static);
				}
				$i++;
			}
		} 
		else {
			throw new RestException(503, 'Error when retreive contacts : ' . $sql);
		}
		if (!count($obj_ret))
		{
			throw new RestException(404, 'Contacts not found');
		}
		return $obj_ret;
	}

	/**
	 * Create contact object <b>Warning: Deprecated</b>
	 *
	 * @param   array   $request_data   Request datas
	 * @return  int     ID of contact
     * 
	 * @url	POST contact/
	 */
	function post($request_data = NULL) {
		if (!DolibarrApiAccess::$user->rights->societe->contact->creer)
		{
			throw new RestException(401);
		}
		// Check mandatory fields
		$result = $this->_validate($request_data);

		foreach ($request_data as $field => $value)
		{
			$this->contact->$field = $value;
		}
		return $this->contact->create(DolibarrApiAccess::$user);
	}

	/**
	 * Update contact <b>Warning: Deprecated</b>
	 *
	 * @param int   $id             Id of contact to update
	 * @param array $request_data   Datas   
	 * @return int 
     * 
	 * @url	PUT contact/{id}
	 */
	function put($id, $request_data = NULL) {
		if (!DolibarrApiAccess::$user->rights->societe->contact->creer)
		{
			throw new RestException(401);
		}

		$result = $this->contact->fetch($id);
		if (!$result)
		{
			throw new RestException(404, 'Contact not found');
		}

		if (!DolibarrApi::_checkAccessToResource('contact', $this->contact->id, 'socpeople&societe'))
		{
			throw new RestException(401, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
		}

		foreach ($request_data as $field => $value)
		{
            if ($field == 'id') continue;
		    $this->contact->$field = $value;
		}

		if ($this->contact->update($id, DolibarrApiAccess::$user, 1, '', '', 'update'))
			return $this->get($id);

		return false;
	}

	/**
	 * Delete contact <b>Warning: Deprecated</b>
	 *
	 * @param   int     $id Contact ID
	 * @return  integer
   * 
	 * @url	DELETE contact/{id}
	 */
	function delete($id) {
		if (!DolibarrApiAccess::$user->rights->contact->supprimer)
		{
			throw new RestException(401);
		}
		$result = $this->contact->fetch($id);
		if (!$result)
		{
			throw new RestException(404, 'Contact not found');
		}

		if (!DolibarrApi::_checkAccessToResource('contact', $this->contact->id, 'socpeople&societe'))
		{
			throw new RestException(401, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
		}

		return $this->contact->delete($id);
	}

	/**
	 * Validate fields before create or update object
     * 
	 * @param   array|null     $data   Data to validate
	 * @return  array
	 * @throws RestException
	 */
	function _validate($data) {
		$contact = array();
		foreach (ContactApi::$FIELDS as $field)
		{
			if (!isset($data[$field]))
				throw new RestException(400, "$field field missing");
			$contact[$field] = $data[$field];
		}
		return $contact;
	}
}
