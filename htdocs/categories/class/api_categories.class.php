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

<<<<<<< HEAD
 use Luracast\Restler\RestException;

 require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
 require_once DOL_DOCUMENT_ROOT.'/societe/class/client.class.php';
=======
use Luracast\Restler\RestException;

require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/client.class.php';

require_once DOL_DOCUMENT_ROOT.'/adherents/class/api_members.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/api_products.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/api_contacts.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/api_thirdparties.class.php';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

/**
 * API class for categories
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class Categories extends DolibarrApi
{
    /**
     * @var array   $FIELDS     Mandatory fields, checked when create and update object
     */
    static $FIELDS = array(
        'label',
        'type'
    );

    static $TYPES = array(
        0 => 'product',
        1 => 'supplier',
        2 => 'customer',
        3 => 'member',
        4 => 'contact',
        5 => 'account',
    );

    /**
     * @var Categorie $category {@type Categorie}
     */
    public $category;

    /**
     * Constructor
     */
<<<<<<< HEAD
    function __construct()
    {
		global $db, $conf;
		$this->db = $db;
=======
    public function __construct()
    {
        global $db, $conf;
        $this->db = $db;
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
        $this->category = new Categorie($this->db);
    }

    /**
     * Get properties of a category object
     *
     * Return an array with category informations
     *
     * @param 	int 	$id ID of category
     * @return 	array|mixed data without useless information
<<<<<<< HEAD
	 *
     * @throws 	RestException
     */
    function get($id)
    {
		if(! DolibarrApiAccess::$user->rights->categorie->lire) {
			throw new RestException(401);
		}

        $result = $this->category->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'category not found');
        }

		if( ! DolibarrApi::_checkAccessToResource('category',$this->category->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		return $this->_cleanObjectDatas($this->category);
=======
     *
     * @throws 	RestException
     */
    public function get($id)
    {
        if (! DolibarrApiAccess::$user->rights->categorie->lire) {
            throw new RestException(401);
        }

        $result = $this->category->fetch($id);
        if ( ! $result ) {
            throw new RestException(404, 'category not found');
        }

        if ( ! DolibarrApi::_checkAccessToResource('categorie', $this->category->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        return $this->_cleanObjectDatas($this->category);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    }

    /**
     * List categories
     *
     * Get a list of categories
     *
     * @param string	$sortfield	Sort field
     * @param string	$sortorder	Sort order
     * @param int		$limit		Limit for list
     * @param int		$page		Page number
     * @param string	$type		Type of category ('member', 'customer', 'supplier', 'product', 'contact')
     * @param string    $sqlfilters Other criteria to filter answers separated by a comma. Syntax example "(t.ref:like:'SO-%') and (t.date_creation:<:'20160101')"
     * @return array                Array of category objects
     *
<<<<<<< HEAD
	 * @throws RestException
     */
    function index($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $type = '', $sqlfilters = '') {
=======
     * @throws RestException
     */
    public function index($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $type = '', $sqlfilters = '')
    {
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
        global $db, $conf;

        $obj_ret = array();

<<<<<<< HEAD
         if(! DolibarrApiAccess::$user->rights->categorie->lire) {
			throw new RestException(401);
		}
=======
        if(! DolibarrApiAccess::$user->rights->categorie->lire) {
            throw new RestException(401);
        }
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

        $sql = "SELECT t.rowid";
        $sql.= " FROM ".MAIN_DB_PREFIX."categorie as t";
        $sql.= ' WHERE t.entity IN ('.getEntity('category').')';
        if (!empty($type))
        {
<<<<<<< HEAD
            $sql.= ' AND t.type='.array_search($type,Categories::$TYPES);
=======
            $sql.= ' AND t.type='.array_search($type, Categories::$TYPES);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
        }
        // Add sql filters
        if ($sqlfilters)
        {
            if (! DolibarrApi::_checkFilters($sqlfilters))
            {
                throw new RestException(503, 'Error when validating parameter sqlfilters '.$sqlfilters);
            }
<<<<<<< HEAD
	        $regexstring='\(([^:\'\(\)]+:[^:\'\(\)]+:[^:\(\)]+)\)';
=======
            $regexstring='\(([^:\'\(\)]+:[^:\'\(\)]+:[^:\(\)]+)\)';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
            $sql.=" AND (".preg_replace_callback('/'.$regexstring.'/', 'DolibarrApi::_forge_criteria_callback', $sqlfilters).")";
        }

        $sql.= $db->order($sortfield, $sortorder);
        if ($limit)	{
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
<<<<<<< HEAD
        	$i=0;
=======
            $i=0;
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
            $num = $db->num_rows($result);
            $min = min($num, ($limit <= 0 ? $num : $limit));
            while ($i < $min)
            {
                $obj = $db->fetch_object($result);
                $category_static = new Categorie($db);
                if($category_static->fetch($obj->rowid)) {
                    $obj_ret[] = $this->_cleanObjectDatas($category_static);
                }
                $i++;
            }
        }
        else {
            throw new RestException(503, 'Error when retrieve category list : '.$db->lasterror());
        }
        if( ! count($obj_ret)) {
            throw new RestException(404, 'No category found');
        }
<<<<<<< HEAD
		return $obj_ret;
=======
        return $obj_ret;
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    }

    /**
     * Create category object
     *
     * @param array $request_data   Request data
     * @return int  ID of category
     */
<<<<<<< HEAD
    function post($request_data = null)
    {
        if(! DolibarrApiAccess::$user->rights->categorie->creer) {
			throw new RestException(401);
		}
=======
    public function post($request_data = null)
    {
        if(! DolibarrApiAccess::$user->rights->categorie->creer) {
            throw new RestException(401);
        }
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

        // Check mandatory fields
        $result = $this->_validate($request_data);

        foreach($request_data as $field => $value) {
            $this->category->$field = $value;
        }
        if ($this->category->create(DolibarrApiAccess::$user) < 0) {
            throw new RestException(500, 'Error when creating category', array_merge(array($this->category->error), $this->category->errors));
        }
        return $this->category->id;
    }

    /**
     * Update category
     *
     * @param int   $id             Id of category to update
     * @param array $request_data   Datas
     * @return int
     */
<<<<<<< HEAD
    function put($id, $request_data = null)
    {
        if(! DolibarrApiAccess::$user->rights->categorie->creer) {
			throw new RestException(401);
		}
=======
    public function put($id, $request_data = null)
    {
        if(! DolibarrApiAccess::$user->rights->categorie->creer) {
            throw new RestException(401);
        }
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

        $result = $this->category->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'category not found');
        }

<<<<<<< HEAD
		if( ! DolibarrApi::_checkAccessToResource('category',$this->category->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}
=======
        if ( ! DolibarrApi::_checkAccessToResource('category', $this->category->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

        foreach($request_data as $field => $value) {
            if ($field == 'id') continue;
            $this->category->$field = $value;
        }

        if ($this->category->update(DolibarrApiAccess::$user) > 0)
        {
<<<<<<< HEAD
            return $this->get ($id);
        }
        else
        {
        	throw new RestException(500, $this->category->error);
=======
            return $this->get($id);
        }
        else
        {
            throw new RestException(500, $this->category->error);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
        }
    }

    /**
     * Delete category
     *
     * @param int $id   Category ID
     * @return array
     */
<<<<<<< HEAD
    function delete($id)
    {
        if(! DolibarrApiAccess::$user->rights->categorie->supprimer) {
			throw new RestException(401);
		}
=======
    public function delete($id)
    {
        if(! DolibarrApiAccess::$user->rights->categorie->supprimer) {
            throw new RestException(401);
        }
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
        $result = $this->category->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'category not found');
        }

<<<<<<< HEAD
		if( ! DolibarrApi::_checkAccessToResource('category',$this->category->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

        if (! $this->category->delete(DolibarrApiAccess::$user)) {
            throw new RestException(401,'error when delete category');
=======
        if ( ! DolibarrApi::_checkAccessToResource('category', $this->category->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        if (! $this->category->delete(DolibarrApiAccess::$user)) {
            throw new RestException(401, 'error when delete category');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
        }

        return array(
            'success' => array(
                'code' => 200,
                'message' => 'Category deleted'
            )
        );
    }


<<<<<<< HEAD
=======
    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    /**
     * Clean sensible object datas
     *
     * @param   Categorie  $object    Object to clean
     * @return    array    Array of cleaned object properties
     */
<<<<<<< HEAD
    function _cleanObjectDatas($object) {

=======
    protected function _cleanObjectDatas($object)
    {
        // phpcs:enable
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
        $object = parent::_cleanObjectDatas($object);

        // Remove fields not relevent to categories
        unset($object->country);
        unset($object->country_id);
        unset($object->country_code);
        unset($object->total_ht);
        unset($object->total_ht);
        unset($object->total_localtax1);
        unset($object->total_localtax2);
        unset($object->total_ttc);
        unset($object->total_tva);
        unset($object->lines);
        unset($object->fk_incoterms);
<<<<<<< HEAD
        unset($object->libelle_incoterms);
=======
        unset($object->label_incoterms);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
        unset($object->location_incoterms);
        unset($object->civility_id);
        unset($object->name);
        unset($object->lastname);
        unset($object->firstname);
        unset($object->shipping_method_id);
        unset($object->fk_delivery_address);
        unset($object->cond_reglement);
        unset($object->cond_reglement_id);
        unset($object->mode_reglement_id);
        unset($object->barcode_type_coder);
        unset($object->barcode_type_label);
        unset($object->barcode_type_code);
        unset($object->barcode_type);
        unset($object->canvas);
        unset($object->cats);
        unset($object->motherof);
        unset($object->context);
        unset($object->socid);
        unset($object->thirdparty);
        unset($object->contact);
        unset($object->contact_id);
        unset($object->user);
        unset($object->fk_account);
        unset($object->fk_project);
        unset($object->note);
        unset($object->statut);

        return $object;
    }

    /**
     * Validate fields before create or update object
     *
     * @param array|null    $data    Data to validate
     * @return array
     *
     * @throws RestException
     */
<<<<<<< HEAD
    function _validate($data)
=======
    private function _validate($data)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    {
        $category = array();
        foreach (Categories::$FIELDS as $field) {
            if (!isset($data[$field]))
                throw new RestException(400, "$field field missing");
            $category[$field] = $data[$field];
        }
        return $category;
    }
<<<<<<< HEAD
=======

    /**
     * Get the list of objects in a category.
     *
     * @param int        $id         ID of category
     * @param string     $type       Type of category ('member', 'customer', 'supplier', 'product', 'contact')
     * @param int        $onlyids    Return only ids of objects (consume less memory)
     *
     * @return mixed
     *
     * @url GET {id}/objects
     */
    public function getObjects($id, $type, $onlyids = 0)
    {
		dol_syslog("getObjects($id, $type, $onlyids)", LOG_DEBUG);

		if (! DolibarrApiAccess::$user->rights->categorie->lire) {
			throw new RestException(401);
		}

        if (empty($type))
        {
			throw new RestException(500, 'The "type" parameter is required.');
        }

        $result = $this->category->fetch($id);
        if (! $result) {
            throw new RestException(404, 'category not found');
        }

		if (! DolibarrApi::_checkAccessToResource('category', $this->category->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$result = $this->category->getObjectsInCateg($type, $onlyids);

		if ($result < 0) {
			throw new RestException(503, 'Error when retrieving objects list : '.$this->category->error);
		}

		$objects = $result;
        $cleaned_objects = array();
        if ($type == 'member') {
			$objects_api = new Members();
		} elseif ($type == 'customer' || $type == 'supplier') {
			$objects_api = new Thirdparties();
		} elseif ($type == 'product') {
			$objects_api = new Products();
		} elseif ($type == 'contact') {
			$objects_api = new Contacts();
		}
		if (is_object($objects_api))
		{
    		foreach ($objects as $obj) {
    			$cleaned_objects[] = $objects_api->_cleanObjectDatas($obj);
    		}
		}

		return $cleaned_objects;
    }
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
}
