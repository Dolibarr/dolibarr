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
 * 
 * API class for thirdparty object
 *
 * @smart-auto-routing false
 * @access protected 
 * @class  DolibarrApiAccess {@requires user,external}
 * 
 *
 */
class ThirdpartyApi extends DolibarrApi {
    
    static $FIELDS = array(
        'name',
        'email'
    );

    /**
     * @var Societe $company {@type Societe}
     */
    public $company;

    /**
     * Constructor
     *
     * @url	thirdparty/
     * 
     */
    function __construct()
    {
		global $db;
		$this->db = $db;
        $this->company = new Societe($this->db);
    }

    /**
     * Get properties of a thirdparty object
     *
     * Return an array with thirdparty informations
     *
     * @url	GET thirdparty/{id}
     * @param 	int 	$id ID of thirdparty
     * @return 	array|mixed data without useless information
	 * 
     * @throws 	RestException
     */
    function get($id)
    {
        $result = $this->company->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'Thirdparty not found');
        }
        
		return $this->cleanObjectDatas($this->company);
    }

    /**
     * Fetch a list of thirdparties
     *
     * @url	GET /thirdparties/list
     *
     * @return array Array of thirdparty objects
     */
    function getList() {

		$result = $this->company->fetch_all($id);
        if( ! $result ) {
            throw new RestException(404, 'Thirdparties not found');
        }

		return $this->cleanObjectDatas($this->company->lines);

    }
    /**
     * Create thirdparty object
     *
     * @url	POST thirdparty/
     * @param type $request_data
     * @return type
     */
    function post($request_data = NULL)
    {
        return $this->company->create($this->_validate($request_data));
    }

    /**
     * Update thirdparty
     *
     * @url	PUT thirdparty/{id}
     * @param type $id
     * @param type $request_data
     * @return type$this->company
     */
    function put($id, $request_data = NULL)
    {
        return $this->company->update($id, $this->_validate($request_data));
    }
    
    /**
     * Delete thirdparty
     *
     * @url	DELETE thirdparty/{id}
     * @param type $id
     * @return type
     */
    function delete($id)
    {
        return $this->company->delete($id);
    }
    
    /**
     * Validate fields before create or update object
     * @param type $data
     * @return array
     * @throws RestException
     */
    private function _validate($data)
    {
        $thirdparty = array();
        foreach (ThirdpartyApi::$FIELDS as $field) {
            if (!isset($data[$field]))
                throw new RestException(400, "$field field missing");
            $thirdparty[$field] = $data[$field];
        }
        return $thirdparty;
    }
}
