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


/**
 * API class for skeleton object
 *
 * @smart-auto-routing false
 * @access protected 
 * @class  DolibarrApiAccess {@requires user,external}
 * 
 *
 */
class SkeletonApi extends DolibarrApi
{
    /**
     * @var array   $FIELDS     Mandatory fields, checked when create and update object 
     */
    static $FIELDS = array(
        'name'
    );

    /**
     * @var Skeleton $skeleton {@type Skeleton}
     */
    public $skeleton;

    /**
     * Constructor
     *
     * @url     GET skeleton/
     * 
     */
    function __construct()
    {
		global $db, $conf;
		$this->db = $db;
        $this->skeleton = new Skeleton($this->db);
    }

    /**
     * Get properties of a skeleton object
     *
     * Return an array with skeleton informations
     *
     * @param 	int 	$id ID of skeleton
     * @return 	array|mixed data without useless information
	 * 
     * @url	GET skeleton/{id}
     * @throws 	RestException
     */
    function get($id)
    {		
		if(! DolibarrApiAccess::$user->rights->skeleton->read) {
			throw new RestException(401);
		}
			
        $result = $this->skeleton->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'Skeleton not found');
        }
		
		if( ! DolibarrApi::_checkAccessToResource('skeleton',$this->skeleton->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		return $this->_cleanObjectDatas($this->skeleton);
    }

    /**
     * List skeletons
     * 
     * Get a list of skeletons
     * 
     * @param int		$mode		Use this param to filter list
     * @param string	$sortfield	Sort field
     * @param string	$sortorder	Sort order
     * @param int		$limit		Limit for list
     * @param int		$page		Page number
     * @param string    $sqlfilters Other criteria to filter answers separated by a comma. Syntax example "(t.ref:like:'SO-%') and (t.date_creation:<:'20160101') or (t.import_key:=:'20160101')"
     * @return array Array of skeleton objects
     *
     * @url	GET /skeletons/
     */
    function index($mode, $sortfield = "t.rowid", $sortorder = 'ASC', $limit = 0, $page = 0, $sqlfilters = '') {
        global $db, $conf;
        
        $obj_ret = array();
        
        $socid = DolibarrApiAccess::$user->societe_id ? DolibarrApiAccess::$user->societe_id : '';
            
        // If the internal user must only see his customers, force searching by him
        if (! DolibarrApiAccess::$user->rights->societe->client->voir && !$socid) $search_sale = DolibarrApiAccess::$user->id;

        $sql = "SELECT s.rowid";
        if ((!DolibarrApiAccess::$user->rights->societe->client->voir && !$socid) || $search_sale > 0) $sql .= ", sc.fk_soc, sc.fk_user"; // We need these fields in order to filter by sale (including the case where the user can only see his prospects)
        $sql.= " FROM ".MAIN_DB_PREFIX."skeleton as s";
        
        if ((!DolibarrApiAccess::$user->rights->societe->client->voir && !$socid) || $search_sale > 0) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc"; // We need this table joined to the select in order to filter by sale
        $sql.= ", ".MAIN_DB_PREFIX."c_stcomm as st";
        $sql.= " WHERE s.fk_stcomm = st.id";
        
		// Example of use $mode
        //if ($mode == 1) $sql.= " AND s.client IN (1, 3)";
        //if ($mode == 2) $sql.= " AND s.client IN (2, 3)";

        $sql.= ' AND s.entity IN ('.getEntity('skeleton', 1).')';
        if ((!DolibarrApiAccess::$user->rights->societe->client->voir && !$socid) || $search_sale > 0) $sql.= " AND s.fk_soc = sc.fk_soc";
        if ($socid) $sql.= " AND s.fk_soc = ".$socid;
        if ($search_sale > 0) $sql.= " AND s.rowid = sc.fk_soc";		// Join for the needed table to filter by sale
        // Insert sale filter
        if ($search_sale > 0)
        {
            $sql .= " AND sc.fk_user = ".$search_sale;
        }
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
            $num = $db->num_rows($result);
            while ($i < $num)
            {
                $obj = $db->fetch_object($result);
                $skeleton_static = new Skeleton($db);
                if($skeleton_static->fetch($obj->rowid)) {
                    $obj_ret[] = parent::_cleanObjectDatas($skeleton_static);
                }
                $i++;
            }
        }
        else {
            throw new RestException(503, 'Error when retrieve skeleton list');
        }
        if( ! count($obj_ret)) {
            throw new RestException(404, 'No skeleton found');
        }
		return $obj_ret;
    }
    
    /**
     * Create skeleton object
     *
     * @param array $request_data   Request datas
     * @return int  ID of skeleton
     * 
     * @url	POST skeleton/
     */
    function post($request_data = NULL)
    {
        if(! DolibarrApiAccess::$user->rights->skeleton->create) {
			throw new RestException(401);
		}
        // Check mandatory fields
        $result = $this->_validate($request_data);
        
        foreach($request_data as $field => $value) {
            $this->skeleton->$field = $value;
        }
        if( ! $this->skeleton->create(DolibarrApiAccess::$user)) {
            throw new RestException(500);
        }
        return $this->skeleton->id;
    }

    /**
     * Update skeleton
     *
     * @param int   $id             Id of skeleton to update
     * @param array $request_data   Datas   
     * @return int 
     * 
     * @url	PUT skeleton/{id}
     */
    function put($id, $request_data = NULL)
    {
        if(! DolibarrApiAccess::$user->rights->skeleton->create) {
			throw new RestException(401);
		}
        
        $result = $this->skeleton->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'Skeleton not found');
        }
		
		if( ! DolibarrApi::_checkAccessToResource('skeleton',$this->skeleton->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

        foreach($request_data as $field => $value) {
            $this->skeleton->$field = $value;
        }
        
        if($this->skeleton->update($id, DolibarrApiAccess::$user))
            return $this->get ($id);
        
        return false;
    }
    
    /**
     * Delete skeleton
     *
     * @param   int     $id   Skeleton ID
     * @return  array
     * 
     * @url	DELETE skeleton/{id}
     */
    function delete($id)
    {
        if(! DolibarrApiAccess::$user->rights->skeleton->supprimer) {
			throw new RestException(401);
		}
        $result = $this->skeleton->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'Skeleton not found');
        }
		
		if( ! DolibarrApi::_checkAccessToResource('skeleton',$this->skeleton->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}
        
        if( !$this->skeleton->delete($id))
        {
            throw new RestException(500);
        }
        
         return array(
            'success' => array(
                'code' => 200,
                'message' => 'Skeleton deleted'
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
        $skeleton = array();
        foreach (SkeletonApi::$FIELDS as $field) {
            if (!isset($data[$field]))
                throw new RestException(400, "$field field missing");
            $skeleton[$field] = $data[$field];
        }
        return $skeleton;
    }
}
