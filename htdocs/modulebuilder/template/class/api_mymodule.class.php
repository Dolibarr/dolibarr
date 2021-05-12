<?php
/* Copyright (C) 2015   Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) ---Put here your own copyright and developer email---
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

dol_include_once('/mymodule/class/myobject.class.php');



/**
 * \file    htdocs/modulebuilder/template/class/api_mymodule.class.php
 * \ingroup mymodule
 * \brief   File for API management of myobject.
 */

/**
 * API class for mymodule myobject
 *
<<<<<<< HEAD
 * @smart-auto-routing false
=======
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class MyModuleApi extends DolibarrApi
{
    /**
<<<<<<< HEAD
     * @var array   $FIELDS     Mandatory fields, checked when create and update object
     */
    static $FIELDS = array(
        'name'
    );


    /**
=======
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
     * @var MyObject $myobject {@type MyObject}
     */
    public $myobject;

    /**
     * Constructor
     *
     * @url     GET /
     *
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
        $this->myobject = new MyObject($this->db);
    }

    /**
     * Get properties of a myobject object
     *
     * Return an array with myobject informations
     *
     * @param 	int 	$id ID of myobject
     * @return 	array|mixed data without useless information
<<<<<<< HEAD
	 *
     * @url	GET myobjects/{id}
     * @throws 	RestException
     */
    function get($id)
    {
		if(! DolibarrApiAccess::$user->rights->myobject->read) {
			throw new RestException(401);
		}

        $result = $this->myobject->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'MyObject not found');
        }

		if( ! DolibarrApi::_checkAccessToResource('myobject',$this->myobject->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		return $this->_cleanObjectDatas($this->myobject);
=======
     *
     * @url	GET myobjects/{id}
     * @throws 	RestException
     */
    public function get($id)
    {
        if (! DolibarrApiAccess::$user->rights->mymodule->read) {
            throw new RestException(401);
        }

        $result = $this->myobject->fetch($id);
        if (! $result) {
            throw new RestException(404, 'MyObject not found');
        }

        if (! DolibarrApi::_checkAccessToResource('myobject', $this->myobject->id, 'mymodule_myobject')) {
            throw new RestException(401, 'Access to instance id='.$this->myobject->id.' of object not allowed for login '.DolibarrApiAccess::$user->login);
        }

        return $this->_cleanObjectDatas($this->myobject);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    }


    /**
     * List myobjects
     *
     * Get a list of myobjects
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
     * @url	GET /myobjects/
     */
<<<<<<< HEAD
    function index($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $sqlfilters = '') {
        global $db, $conf;

        $obj_ret = array();

        $socid = DolibarrApiAccess::$user->societe_id ? DolibarrApiAccess::$user->societe_id : '';

        $restictonsocid = 0;	// Set to 1 if there is a field socid in table of object

        // If the internal user must only see his customers, force searching by him
        if ($restictonsocid && ! DolibarrApiAccess::$user->rights->societe->client->voir && !$socid) $search_sale = DolibarrApiAccess::$user->id;

        $sql = "SELECT t.rowid";
        if ($restictonsocid && (!DolibarrApiAccess::$user->rights->societe->client->voir && !$socid) || $search_sale > 0) $sql .= ", sc.fk_soc, sc.fk_user"; // We need these fields in order to filter by sale (including the case where the user can only see his prospects)
        $sql.= " FROM ".MAIN_DB_PREFIX."myobject_mytable as t";

        if ($restictonsocid && (!DolibarrApiAccess::$user->rights->societe->client->voir && !$socid) || $search_sale > 0) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc"; // We need this table joined to the select in order to filter by sale
        $sql.= " WHERE 1 = 1";

		// Example of use $mode
        //if ($mode == 1) $sql.= " AND s.client IN (1, 3)";
        //if ($mode == 2) $sql.= " AND s.client IN (2, 3)";

        $tmpobject = new MyObject($db);
        if ($tmpobject->ismultientitymanaged) $sql.= ' AND t.entity IN ('.getEntity('myobject').')';
        if ($restictonsocid && (!DolibarrApiAccess::$user->rights->societe->client->voir && !$socid) || $search_sale > 0) $sql.= " AND t.fk_soc = sc.fk_soc";
        if ($restictonsocid && $socid) $sql.= " AND t.fk_soc = ".$socid;
        if ($restictonsocid && $search_sale > 0) $sql.= " AND t.rowid = sc.fk_soc";		// Join for the needed table to filter by sale
        // Insert sale filter
        if ($restictonsocid && $search_sale > 0)
        {
=======
    public function index($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $sqlfilters = '')
    {
        global $db, $conf;

        $obj_ret = array();
        $tmpobject = new MyObject($db);

        if(! DolibarrApiAccess::$user->rights->bbb->read) {
            throw new RestException(401);
        }

        $socid = DolibarrApiAccess::$user->societe_id ? DolibarrApiAccess::$user->societe_id : '';

        $restrictonsocid = 0;	// Set to 1 if there is a field socid in table of object

        // If the internal user must only see his customers, force searching by him
        $search_sale = 0;
        if ($restrictonsocid && ! DolibarrApiAccess::$user->rights->societe->client->voir && !$socid) $search_sale = DolibarrApiAccess::$user->id;

        $sql = "SELECT t.rowid";
        if ($restrictonsocid && (!DolibarrApiAccess::$user->rights->societe->client->voir && !$socid) || $search_sale > 0) $sql .= ", sc.fk_soc, sc.fk_user"; // We need these fields in order to filter by sale (including the case where the user can only see his prospects)
        $sql.= " FROM ".MAIN_DB_PREFIX.$tmpobject->table_element." as t";

        if ($restrictonsocid && (!DolibarrApiAccess::$user->rights->societe->client->voir && !$socid) || $search_sale > 0) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc"; // We need this table joined to the select in order to filter by sale
        $sql.= " WHERE 1 = 1";

        // Example of use $mode
        //if ($mode == 1) $sql.= " AND s.client IN (1, 3)";
        //if ($mode == 2) $sql.= " AND s.client IN (2, 3)";

        if ($tmpobject->ismultientitymanaged) $sql.= ' AND t.entity IN ('.getEntity('myobject').')';
        if ($restrictonsocid && (!DolibarrApiAccess::$user->rights->societe->client->voir && !$socid) || $search_sale > 0) $sql.= " AND t.fk_soc = sc.fk_soc";
        if ($restrictonsocid && $socid) $sql.= " AND t.fk_soc = ".$socid;
        if ($restrictonsocid && $search_sale > 0) $sql.= " AND t.rowid = sc.fk_soc";		// Join for the needed table to filter by sale
        // Insert sale filter
        if ($restrictonsocid && $search_sale > 0) {
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
            $sql .= " AND sc.fk_user = ".$search_sale;
        }
        if ($sqlfilters)
        {
<<<<<<< HEAD
            if (! DolibarrApi::_checkFilters($sqlfilters))
            {
                throw new RestException(503, 'Error when validating parameter sqlfilters '.$sqlfilters);
            }
	        $regexstring='\(([^:\'\(\)]+:[^:\'\(\)]+:[^:\(\)]+)\)';
=======
            if (! DolibarrApi::_checkFilters($sqlfilters)) {
                throw new RestException(503, 'Error when validating parameter sqlfilters '.$sqlfilters);
            }
            $regexstring='\(([^:\'\(\)]+:[^:\'\(\)]+:[^:\(\)]+)\)';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
            $sql.=" AND (".preg_replace_callback('/'.$regexstring.'/', 'DolibarrApi::_forge_criteria_callback', $sqlfilters).")";
        }

        $sql.= $db->order($sortfield, $sortorder);
        if ($limit)	{
<<<<<<< HEAD
            if ($page < 0)
            {
=======
            if ($page < 0) {
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
                $page = 0;
            }
            $offset = $limit * $page;

            $sql.= $db->plimit($limit + 1, $offset);
        }

        $result = $db->query($sql);
        if ($result)
        {
            $num = $db->num_rows($result);
            while ($i < $num)
            {
                $obj = $db->fetch_object($result);
                $myobject_static = new MyObject($db);
                if($myobject_static->fetch($obj->rowid)) {
                    $obj_ret[] = $this->_cleanObjectDatas($myobject_static);
                }
                $i++;
            }
        }
        else {
<<<<<<< HEAD
            throw new RestException(503, 'Error when retrieve myobject list');
=======
            throw new RestException(503, 'Error when retrieving myobject list: '.$db->lasterror());
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
        }
        if( ! count($obj_ret)) {
            throw new RestException(404, 'No myobject found');
        }
<<<<<<< HEAD
		return $obj_ret;
=======
        return $obj_ret;
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    }

    /**
     * Create myobject object
     *
     * @param array $request_data   Request datas
     * @return int  ID of myobject
     *
     * @url	POST myobjects/
     */
<<<<<<< HEAD
    function post($request_data = null)
    {
        if(! DolibarrApiAccess::$user->rights->myobject->create) {
			throw new RestException(401);
		}
=======
    public function post($request_data = null)
    {
        if(! DolibarrApiAccess::$user->rights->mymodule->write) {
            throw new RestException(401);
        }
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
        // Check mandatory fields
        $result = $this->_validate($request_data);

        foreach($request_data as $field => $value) {
            $this->myobject->$field = $value;
        }
        if( ! $this->myobject->create(DolibarrApiAccess::$user)) {
<<<<<<< HEAD
            throw new RestException(500);
=======
            throw new RestException(500, "Error creating MyObject", array_merge(array($this->myobject->error), $this->myobject->errors));
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
        }
        return $this->myobject->id;
    }

    /**
     * Update myobject
     *
     * @param int   $id             Id of myobject to update
     * @param array $request_data   Datas
     * @return int
     *
     * @url	PUT myobjects/{id}
     */
<<<<<<< HEAD
    function put($id, $request_data = null)
    {
        if(! DolibarrApiAccess::$user->rights->myobject->create) {
			throw new RestException(401);
		}
=======
    public function put($id, $request_data = null)
    {
        if(! DolibarrApiAccess::$user->rights->mymodule->write) {
            throw new RestException(401);
        }
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

        $result = $this->myobject->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'MyObject not found');
        }

<<<<<<< HEAD
		if( ! DolibarrApi::_checkAccessToResource('myobject',$this->myobject->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

        foreach($request_data as $field => $value) {
            $this->myobject->$field = $value;
        }

        if($this->myobject->update($id, DolibarrApiAccess::$user))
            return $this->get($id);

        return false;
=======
        if( ! DolibarrApi::_checkAccessToResource('myobject', $this->myobject->id, 'mymodule_myobject')) {
            throw new RestException(401, 'Access to instance id='.$this->myobject->id.' of object not allowed for login '.DolibarrApiAccess::$user->login);
        }

        foreach($request_data as $field => $value) {
            if ($field == 'id') continue;
            $this->myobject->$field = $value;
        }

        if ($this->myobject->update($id, DolibarrApiAccess::$user) > 0)
        {
            return $this->get($id);
        }
        else
        {
            throw new RestException(500, $this->myobject->error);
        }
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    }

    /**
     * Delete myobject
     *
     * @param   int     $id   MyObject ID
     * @return  array
     *
<<<<<<< HEAD
     * @url	DELETE myobject/{id}
     */
    function delete($id)
    {
    	if(! DolibarrApiAccess::$user->rights->myobject->delete) {
			throw new RestException(401);
		}
        $result = $this->myobject->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'MyObject not found');
        }

		if( ! DolibarrApi::_checkAccessToResource('myobject',$this->myobject->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		if( !$this->myobject->delete(DolibarrApiAccess::$user, 0))
        {
            throw new RestException(500);
=======
     * @url	DELETE myobjects/{id}
     */
    public function delete($id)
    {
        if (! DolibarrApiAccess::$user->rights->mymodule->delete) {
            throw new RestException(401);
        }
        $result = $this->myobject->fetch($id);
        if (! $result) {
            throw new RestException(404, 'MyObject not found');
        }

        if (! DolibarrApi::_checkAccessToResource('myobject', $this->myobject->id, 'mymodule_myobject')) {
            throw new RestException(401, 'Access to instance id='.$this->myobject->id.' of object not allowed for login '.DolibarrApiAccess::$user->login);
        }

        if (! $this->myobject->delete(DolibarrApiAccess::$user))
        {
            throw new RestException(500, 'Error when deleting MyObject : '.$this->myobject->error);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
        }

         return array(
            'success' => array(
                'code' => 200,
                'message' => 'MyObject deleted'
            )
        );
<<<<<<< HEAD

    }


=======
    }


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    /**
     * Clean sensible object datas
     *
     * @param   object  $object    Object to clean
     * @return    array    Array of cleaned object properties
     */
<<<<<<< HEAD
    function _cleanObjectDatas($object)
    {
    	$object = parent::_cleanObjectDatas($object);

    	/*unset($object->note);
    	unset($object->address);
    	unset($object->barcode_type);
    	unset($object->barcode_type_code);
    	unset($object->barcode_type_label);
    	unset($object->barcode_type_coder);*/

    	return $object;
=======
    protected function _cleanObjectDatas($object)
    {
        // phpcs:enable
        $object = parent::_cleanObjectDatas($object);

        /*unset($object->note);
        unset($object->address);
        unset($object->barcode_type);
        unset($object->barcode_type_code);
        unset($object->barcode_type_label);
        unset($object->barcode_type_coder);*/

        return $object;
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    }

    /**
     * Validate fields before create or update object
     *
<<<<<<< HEAD
     * @param array $data   Data to validate
     * @return array
     *
     * @throws RestException
     */
    function _validate($data)
    {
        $myobject = array();
        foreach (MyObjectApi::$FIELDS as $field) {
=======
     * @param	array		$data   Array of data to validate
     * @return	array
     *
     * @throws	RestException
     */
    private function _validate($data)
    {
        $myobject = array();
        foreach ($this->myobject->fields as $field => $propfield) {
            if (in_array($field, array('rowid', 'entity', 'date_creation', 'tms', 'fk_user_creat')) || $propfield['notnull'] != 1) continue;   // Not a mandatory field
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
            if (!isset($data[$field]))
                throw new RestException(400, "$field field missing");
            $myobject[$field] = $data[$field];
        }
        return $myobject;
    }
}
