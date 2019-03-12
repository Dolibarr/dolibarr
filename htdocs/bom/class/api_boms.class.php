<?php
/* Copyright (C) 2015   Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2019 Maxime Kohlhaas <maxime@atm-consulting.fr>
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

require_once DOL_DOCUMENT_ROOT.'/bom/class/bom.class.php';


/**
 * \file    bom/class/api_boms.class.php
 * \ingroup bom
 * \brief   File for API management of bom.
 */

/**
 * API class for bom bom
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class BOMs extends DolibarrApi
{
    /**
     * @var array   $FIELDS     Mandatory fields, checked when create and update object
     */
    static $FIELDS = array(
        'label'
    );


    /**
     * @var BOM $bom {@type BOM}
     */
    public $bom;

    /**
     * Constructor
     */
    public function __construct()
    {
		global $db, $conf;
		$this->db = $db;
        $this->bom = new BOM($this->db);
    }

    /**
     * Get properties of a bom object
     *
     * Return an array with bom informations
     *
     * @param 	int 	$id ID of bom
     * @return 	array|mixed data without useless information
	 *
     * @url	GET {id}
     * @throws 	RestException
     */
    public function get($id)
    {
		if(! DolibarrApiAccess::$user->rights->bom->read) {
			throw new RestException(401);
		}

        $result = $this->bom->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'BOM not found');
        }

		if( ! DolibarrApi::_checkAccessToResource('bom', $this->bom->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		return $this->_cleanObjectDatas($this->bom);
    }


    /**
     * List boms
     *
     * Get a list of boms
     *
     * @param string	       $sortfield	        Sort field
     * @param string	       $sortorder	        Sort order
     * @param int		       $limit		        Limit for list
     * @param int		       $page		        Page number
     * @param string           $sqlfilters          Other criteria to filter answers separated by a comma. Syntax example "(t.ref:like:'SO-%') and (t.date_creation:<:'20160101')"
     * @return  array                               Array of order objects
     *
     * @throws RestException
     */
    public function index($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $sqlfilters = '')
    {
        global $db, $conf;

        $obj_ret = array();

        $socid = DolibarrApiAccess::$user->societe_id ? DolibarrApiAccess::$user->societe_id : '';

        $restictonsocid = 0;	// Set to 1 if there is a field socid in table of object

        // If the internal user must only see his customers, force searching by him
        $search_sale = 0;
        if ($restictonsocid && ! DolibarrApiAccess::$user->rights->societe->client->voir && !$socid) $search_sale = DolibarrApiAccess::$user->id;

        $sql = "SELECT t.rowid";
        if ($restictonsocid && (!DolibarrApiAccess::$user->rights->societe->client->voir && !$socid) || $search_sale > 0) $sql .= ", sc.fk_soc, sc.fk_user"; // We need these fields in order to filter by sale (including the case where the user can only see his prospects)
        $sql.= " FROM ".MAIN_DB_PREFIX."bom_mytable as t";

        if ($restictonsocid && (!DolibarrApiAccess::$user->rights->societe->client->voir && !$socid) || $search_sale > 0) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc"; // We need this table joined to the select in order to filter by sale
        $sql.= " WHERE 1 = 1";

        // Example of use $mode
        //if ($mode == 1) $sql.= " AND s.client IN (1, 3)";
        //if ($mode == 2) $sql.= " AND s.client IN (2, 3)";

        $tmpobject = new BOM($db);
        if ($tmpobject->ismultientitymanaged) $sql.= ' AND t.entity IN ('.getEntity('bom').')';
        if ($restictonsocid && (!DolibarrApiAccess::$user->rights->societe->client->voir && !$socid) || $search_sale > 0) $sql.= " AND t.fk_soc = sc.fk_soc";
        if ($restictonsocid && $socid) $sql.= " AND t.fk_soc = ".$socid;
        if ($restictonsocid && $search_sale > 0) $sql.= " AND t.rowid = sc.fk_soc";		// Join for the needed table to filter by sale
        // Insert sale filter
        if ($restictonsocid && $search_sale > 0)
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
            $i = 0;
            while ($i < $num)
            {
                $obj = $db->fetch_object($result);
                $bom_static = new BOM($db);
                if ($bom_static->fetch($obj->rowid)) {
                    $obj_ret[] = $this->_cleanObjectDatas($bom_static);
                }
                $i++;
            }
        }
        else {
            throw new RestException(503, 'Error when retrieve bom list');
        }
        if( ! count($obj_ret)) {
            throw new RestException(404, 'No bom found');
        }
		return $obj_ret;
    }

    /**
     * Create bom object
     *
     * @param array $request_data   Request datas
     * @return int  ID of bom
     */
    public function post($request_data = null)
    {
        if(! DolibarrApiAccess::$user->rights->bom->create) {
            throw new RestException(401);
        }
        // Check mandatory fields
        $result = $this->_validate($request_data);

        foreach($request_data as $field => $value) {
            $this->bom->$field = $value;
        }
        if( ! $this->bom->create(DolibarrApiAccess::$user)) {
            throw new RestException(500, "Error creating BOM", array_merge(array($this->bom->error), $this->bom->errors));
        }
        return $this->bom->id;
    }

    /**
     * Update bom
     *
     * @param int   $id             Id of bom to update
     * @param array $request_data   Datas
     *
     * @return int
     */
    public function put($id, $request_data = null)
    {
        if(! DolibarrApiAccess::$user->rights->bom->create) {
            throw new RestException(401);
        }

        $result = $this->bom->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'BOM not found');
        }

		if( ! DolibarrApi::_checkAccessToResource('bom', $this->bom->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

        foreach($request_data as $field => $value) {
            if ($field == 'id') continue;
            $this->bom->$field = $value;
        }

        if($this->bom->update($id, DolibarrApiAccess::$user) > 0)
        {
            return $this->get($id);
        }
        else
        {
            throw new RestException(500, $this->commande->error);
        }
    }

    /**
     * Delete bom
     *
     * @param   int     $id   BOM ID
     * @return  array
     */
    public function delete($id)
    {
    	if (! DolibarrApiAccess::$user->rights->bom->delete) {
			throw new RestException(401);
		}
        $result = $this->bom->fetch($id);
        if (! $result) {
            throw new RestException(404, 'BOM not found');
        }

        if (! DolibarrApi::_checkAccessToResource('bom', $this->bom->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

		if (! $this->bom->delete(DolibarrApiAccess::$user))
        {
            throw new RestException(500, 'Error when deleting BOM : '.$this->bom->error);
        }

         return array(
            'success' => array(
                'code' => 200,
                'message' => 'BOM deleted'
            )
        );
    }


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
    /**
     * Clean sensible object datas
     *
     * @param   object  $object     Object to clean
     * @return  array               Array of cleaned object properties
     */
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
    }

    /**
     * Validate fields before create or update object
     *
     * @param	array		$data   Array of data to validate
     * @return	array
     *
     * @throws	RestException
     */
    private function _validate($data)
    {
        $bom = array();
        foreach (BOMs::$FIELDS as $field) {
            if (!isset($data[$field]))
                throw new RestException(400, "$field field missing");
            $bom[$field] = $data[$field];
        }
        return $bom;
    }
}
