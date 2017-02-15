<?php
/* Copyright (C) 2015   Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2017   Ion Agorria             <ion@agorria.com>
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

 require_once DOL_DOCUMENT_ROOT.'/resource/class/dolresource.class.php';

/**
 * API class for resources
 *
 * @smart-auto-routing false
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class Dolresources extends DolibarrApi
{
    /**
     * @var array   $FIELDS     Mandatory fields, checked when create and update object 
     */
    static $FIELDS = array(
        'ref'
    );

    /**
     * @var Dolresource $resource {@type Dolresource}
     */
    public $resource;

    /**
     * Constructor
     *
     * @url     GET resources/
     * 
     */
    function __construct()
    {
        global $db, $conf;
        $this->db = $db;
        $this->resource = new Dolresource($this->db);
    }

    /**
     * Get properties of a resource object
     *
     * Return an array with resource informations
     *
     * @param 	int 	$id ID of resource
     * @return 	array|mixed data without useless information
     *
     * @url	GET resources/{id}
     * @throws 	RestException
     */
    function get($id)
    {
        if(! DolibarrApiAccess::$user->rights->resource->read) {
            throw new RestException(401);
        }
            
        $result = $this->resource->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'Resource not found');
        }
        
        if( ! DolibarrApi::_checkAccessToResource('resource',$this->resource->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        return $this->_cleanObjectDatas($this->resource);
    }

    /**
     * List resources
     * 
     * Get a list of resources
     *
     * @param string	$sortfield	Sort field
     * @param string	$sortorder	Sort order
     * @param int		$limit		Limit for list
     * @param int		$page		Page number
     * @param string    $sqlfilters Other criteria to filter answers separated by a comma. Syntax example "(t.ref:like:'SO-%') and (t.date_creation:<:'20160101') or (t.import_key:=:'20160101')"
     * @return array Array of resource objects
     *
     * @url	GET /resources/
     * @throws 	RestException
     */
    function index($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 0, $page = 0, $sqlfilters = '') {
        global $db;
        
        $obj_ret = array();
        
        if(! DolibarrApiAccess::$user->rights->resource->read) {
            throw new RestException(401);
        }
        
        $sql = "SELECT t.rowid";
        $sql.= " FROM ".MAIN_DB_PREFIX.$this->resource->table_element." as t";
        $sql.= ' WHERE t.entity IN ('.getEntity('resources', 1).')';
        // Add sql filters
        if ($sqlfilters)
        {
            if (! DolibarrApi::_checkFilters($sqlfilters))
            {
                throw new RestException(503, 'Error when validating parameter sqlfilters '.$sqlfilters);
            }
            $regexstring='\(([^:\'\(\)]+:[^:\'\(\)]+:[^:\(\)]+)\)';
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
            $i = 0;
            $num = $db->num_rows($result);
            while ($i < min($num, ($limit <= 0 ? $num : $limit)))
            {
                $obj = $db->fetch_object($result);
                $resource_static = new Dolresource($db);
                if($resource_static->fetch($obj->rowid)) {
                    $obj_ret[] = $this->_cleanObjectDatas($resource_static);
                }
                $i++;
            }
        }
        else {
            throw new RestException(503, 'Error when retrieve resource list');
        }
        if( ! count($obj_ret)) {
            throw new RestException(404, 'No resource found');
        }
        return $obj_ret;
    }
    
    /**
     * Create resource object
     *
     * @param array $request_data   Request datas
     * @return int  ID of resource
     *
     * @url	POST resources/
     * @throws 	RestException
     */
    function post($request_data = NULL)
    {
        if(! DolibarrApiAccess::$user->rights->resource->write) {
			throw new RestException(401);
		}
        // Check mandatory fields
        $result = $this->_validate($request_data);
        
        foreach($request_data as $field => $value) {
            $this->resource->$field = $value;
        }
        if( ! $this->resource->create(DolibarrApiAccess::$user)) {
            throw new RestException(500);
        }
        return $this->resource->id;
    }

    /**
     * Update resource
     *
     * @param int   $id             Id of resource to update
     * @param array $request_data   Datas   
     * @return int 
     *
     * @url	PUT resources/{id}
     * @throws 	RestException
     */
    function put($id, $request_data = NULL)
    {
        if(! DolibarrApiAccess::$user->rights->resource->write) {
			throw new RestException(401);
		}
        
        $result = $this->resource->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'Resource not found');
        }
		
		if( ! DolibarrApi::_checkAccessToResource('resource',$this->resource->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

        foreach($request_data as $field => $value) {
            $this->resource->$field = $value;
        }
        
        if($this->resource->update(DolibarrApiAccess::$user))
            return $this->get ($id);
        
        return false;
    }
    
    /**
     * Delete resource
     *
     * @param   int     $id   Resource ID
     * @return  array
     *
     * @url	DELETE resources/{id}
     * @throws 	RestException
     */
    function delete($id)
    {
        if(! DolibarrApiAccess::$user->rights->resource->delete) {
			throw new RestException(401);
		}
        $result = $this->resource->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'Resource not found');
        }
		
		if( ! DolibarrApi::_checkAccessToResource('resource',$this->resource->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}
        
        if( !$this->resource->delete($id))
        {
            throw new RestException(500);
        }
        
         return array(
            'success' => array(
                'code' => 200,
                'message' => 'resource deleted'
            )
        );
        
    }
    
    /**
     * Validate fields before create or update object
     * 
     * @param array $data   Data to validate
     * @return array
     * 
     * @throws RestException
     */
    function _validate($data)
    {
        $resource = array();
        foreach (DolResources::$FIELDS as $field) {
            if (!isset($data[$field]))
                throw new RestException(400, "$field field missing");
            $resource[$field] = $data[$field];
        }
        return $resource;
    }
    
    /**
     * Clean sensible object datas
     *
     * @param  Dolresource  $object    Object to clean
     * @return    array                Array of cleaned object properties
     */
    function _cleanObjectDatas($object) {
    
        $object = parent::_cleanObjectDatas($object);
    
        unset($object->fk_code_type_resource);
        unset($object->cache_code_type_resource);
        
        return $object;
    }
}
