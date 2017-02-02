<?php
/* Copyright (C) 2015   Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2016   Laurent Destailleur     <eldy@users.sourceforge.net>
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

 require_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport.class.php';

/**
 * API class for Expense Reports
 *
 * @access protected 
 * @class  DolibarrApiAccess {@requires user,external}
 */
class ExpenseReports extends DolibarrApi
{

    /**
     * @var array   $FIELDS     Mandatory fields, checked when create and update object 
     */
    static $FIELDS = array(
        'socid'
    );

    /**
     * @var ExpenseReport $expensereport {@type ExpenseReport}
     */
    public $expensereport;

    
    /**
     * Constructor
     */
    function __construct()
    {
		global $db, $conf;
		$this->db = $db;
        $this->expensereport = new ExpenseReport($this->db);
    }

    /**
     * Get properties of a Expense Report object
     *
     * Return an array with Expense Report informations
     * 
     * @param       int         $id         ID of Expense Report
     * @return 	    array|mixed             Data without useless information
	 *
     * @throws 	RestException
     */
    function get($id)
    {		
		if(! DolibarrApiAccess::$user->rights->expensereport->lire) {
			throw new RestException(401);
		}
			
        $result = $this->expensereport->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'Expense report not found');
        }
		
		if( ! DolibarrApi::_checkAccessToResource('expensereport',$this->expensereport->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}
        
        $this->expensereport->fetchObjectLinked();
		return $this->_cleanObjectDatas($this->expensereport);
    }

    /**
     * List Expense Reports
     * 
     * Get a list of Expense Reports
     * 
     * @param string	$sortfield	Sort field
     * @param string	$sortorder	Sort order
     * @param int		$limit		Limit for list
     * @param int		$page		Page number
     * @param string   	$user_ids   User ids filter field. Example: '1' or '1,2,3'          {@pattern /^[0-9,]*$/i}
     * @param string    $sqlfilters Other criteria to filter answers separated by a comma. Syntax example "(t.ref:like:'SO-%') and (t.date_creation:<:'20160101')"
     * @return  array               Array of Expense Report objects
     */
    function index($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 0, $page = 0, $user_ids = 0, $sqlfilters = '') {
        global $db, $conf;
        
        $obj_ret = array();

        // case of external user, $societe param is ignored and replaced by user's socid
        //$socid = DolibarrApiAccess::$user->societe_id ? DolibarrApiAccess::$user->societe_id : $societe;
        
        $sql = "SELECT t.rowid";
        $sql.= " FROM ".MAIN_DB_PREFIX."expensereport as t";
        $sql.= ' WHERE t.entity IN ('.getEntity('expensereport', 1).')';
        if ($user_ids) $sql.=" AND t.fk_user_author IN (".$user_ids.")";
        
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
            $num = $db->num_rows($result);
            while ($i < min($num, ($limit <= 0 ? $num : $limit)))
            {
                $obj = $db->fetch_object($result);
                $expensereport_static = new ExpenseReport($db);
                if($expensereport_static->fetch($obj->rowid)) {
                    $obj_ret[] = $this->_cleanObjectDatas($expensereport_static);
                }
                $i++;
            }
        }
        else {
            throw new RestException(503, 'Error when retrieve Expense Report list : '.$db->lasterror());
        }
        if( ! count($obj_ret)) {
            throw new RestException(404, 'No Expense Report found');
        }
		return $obj_ret;
    }

    /**
     * Create Expense Report object
     *
     * @param   array   $request_data   Request data
     * @return  int                     ID of Expense Report
     */
    function post($request_data = NULL)
    {
      if(! DolibarrApiAccess::$user->rights->expensereport->creer) {
			  throw new RestException(401, "Insuffisant rights");
		  }
        // Check mandatory fields
        $result = $this->_validate($request_data);

        foreach($request_data as $field => $value) {
            $this->expensereport->$field = $value;
        }
        /*if (isset($request_data["lines"])) {
          $lines = array();
          foreach ($request_data["lines"] as $line) {
            array_push($lines, (object) $line);
          }
          $this->expensereport->lines = $lines;
        }*/
        if ($this->expensereport->create(DolibarrApiAccess::$user) < 0) {
            throw new RestException(500, "Error creating expensereport", array_merge(array($this->expensereport->error), $this->expensereport->errors));
        }
        
        return $this->expensereport->id;
    }

    /**
     * Get lines of an Expense Report
     *
     * @param int   $id             Id of Expense Report
     * 
     * @url	GET {id}/lines
     * 
     * @return int 
     */
/*    
    function getLines($id) {
      if(! DolibarrApiAccess::$user->rights->expensereport->lire) {
		  	throw new RestException(401);
		  }
        
      $result = $this->expensereport->fetch($id);
      if( ! $result ) {
         throw new RestException(404, 'expensereport not found');
      }
		
		  if( ! DolibarrApi::_checkAccessToResource('expensereport',$this->expensereport->id)) {
			  throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
      }
      $this->expensereport->getLinesArray();
      $result = array();
      foreach ($this->expensereport->lines as $line) {
        array_push($result,$this->_cleanObjectDatas($line));
      }
      return $result;
    }
*/
    
    /**
     * Add a line to given Expense Report
     *
     * @param int   $id             Id of Expense Report to update
     * @param array $request_data   Expense Report data   
     * 
     * @url	POST {id}/lines
     * 
     * @return int 
     */
/*    
    function postLine($id, $request_data = NULL) {
      if(! DolibarrApiAccess::$user->rights->expensereport->creer) {
		  	throw new RestException(401);
		  }
        
      $result = $this->expensereport->fetch($id);
      if( ! $result ) {
         throw new RestException(404, 'expensereport not found');
      }
		
		  if( ! DolibarrApi::_checkAccessToResource('expensereport',$this->expensereport->id)) {
			  throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
      }
			$request_data = (object) $request_data;
      $updateRes = $this->expensereport->addline(
                        $request_data->desc,
                        $request_data->subprice,
                        $request_data->qty,
                        $request_data->tva_tx,
                        $request_data->localtax1_tx,
                        $request_data->localtax2_tx,
                        $request_data->fk_product,
                        $request_data->remise_percent,
                        $request_data->info_bits,
                        $request_data->fk_remise_except,
                        'HT',
                        0,
                        $request_data->date_start,
                        $request_data->date_end,
                        $request_data->product_type,
                        $request_data->rang,
                        $request_data->special_code,
                        $fk_parent_line,
                        $request_data->fk_fournprice,
                        $request_data->pa_ht,
                        $request_data->label,
                        $request_data->array_options,
                        $request_data->fk_unit,
                        $this->element,
                        $request_data->id
      );

      if ($updateRes > 0) {
        return $this->get($id)->line->rowid;

      }
      return false;
    }
*/
    
    /**
     * Update a line to given Expense Report
     *
     * @param int   $id             Id of Expense Report to update
     * @param int   $lineid         Id of line to update
     * @param array $request_data   Expense Report data   
     * 
     * @url	PUT {id}/lines/{lineid}
     * 
     * @return object 
     */
    /*
    function putLine($id, $lineid, $request_data = NULL) {
      if(! DolibarrApiAccess::$user->rights->expensereport->creer) {
		  	throw new RestException(401);
		  }
        
      $result = $this->expensereport->fetch($id);
      if( ! $result ) {
         throw new RestException(404, 'expensereport not found');
      }
		
		  if( ! DolibarrApi::_checkAccessToResource('expensereport',$this->expensereport->id)) {
			  throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
      }
			$request_data = (object) $request_data;
      $updateRes = $this->expensereport->updateline(
                        $lineid,
                        $request_data->desc,
                        $request_data->subprice,
                        $request_data->qty,
                        $request_data->remise_percent,
                        $request_data->tva_tx,
                        $request_data->localtax1_tx,
                        $request_data->localtax2_tx,
                        'HT',
                        $request_data->info_bits,
                        $request_data->date_start,
                        $request_data->date_end,
                        $request_data->product_type,
                        $request_data->fk_parent_line,
                        0,
                        $request_data->fk_fournprice,
                        $request_data->pa_ht,
                        $request_data->label,
                        $request_data->special_code,
                        $request_data->array_options,
                        $request_data->fk_unit
      );

      if ($updateRes > 0) {
        $result = $this->get($id);
        unset($result->line);
        return $this->_cleanObjectDatas($result);
      }
      return false;
    }
      */

    /**
     * Delete a line of given Expense Report
     *
     * @param int   $id             Id of Expense Report to update
     * @param int   $lineid         Id of line to delete
     * 
     * @url	DELETE {id}/lines/{lineid}
     * 
     * @return int 
     */
    /*
    function delLine($id, $lineid) {
      if(! DolibarrApiAccess::$user->rights->expensereport->creer) {
		  	throw new RestException(401);
		  }
        
      $result = $this->expensereport->fetch($id);
      if( ! $result ) {
         throw new RestException(404, 'expensereport not found');
      }
		
		  if( ! DolibarrApi::_checkAccessToResource('expensereport',$this->expensereport->id)) {
			  throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
      }
			$request_data = (object) $request_data;
      $updateRes = $this->expensereport->deleteline($lineid);
      if ($updateRes == 1) {
        return $this->get($id);
      }
      return false;
    }
    */
    
    /**
     * Update Expense Report general fields (won't touch lines of expensereport)
     *
     * @param int   $id             Id of Expense Report to update
     * @param array $request_data   Datas   
     * 
     * @return int 
     */
    /*
    function put($id, $request_data = NULL) {
      if(! DolibarrApiAccess::$user->rights->expensereport->creer) {
		  	throw new RestException(401);
		  }
        
        $result = $this->expensereport->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'expensereport not found');
        }
		
		if( ! DolibarrApi::_checkAccessToResource('expensereport',$this->expensereport->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}
        foreach($request_data as $field => $value) {
            if ($field == 'id') continue;
            $this->expensereport->$field = $value;
        }
        
        if($this->expensereport->update($id, DolibarrApiAccess::$user,1,'','','update'))
            return $this->get($id);
        
        return false;
    }
    */
        
    /**
     * Delete Expense Report
     *
     * @param   int     $id         Expense Report ID
     * 
     * @return  array
     */
    function delete($id)
    {
        if(! DolibarrApiAccess::$user->rights->expensereport->supprimer) {
			throw new RestException(401);
		}
        $result = $this->expensereport->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'Expense Report not found');
        }
		
		if( ! DolibarrApi::_checkAccessToResource('expensereport',$this->expensereport->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}
        
        if( ! $this->expensereport->delete(DolibarrApiAccess::$user)) {
            throw new RestException(500, 'Error when delete Expense Report : '.$this->expensereport->error);
        }
        
        return array(
            'success' => array(
                'code' => 200,
                'message' => 'Expense Report deleted'
            )
        );
        
    }
    
    /**
     * Validate an Expense Report
     * 
     * @param   int $id             Expense Report ID
     * 
     * @url POST    {id}/validate
     *  
     * @return  array
     * FIXME An error 403 is returned if the request has an empty body.
     * Error message: "Forbidden: Content type `text/plain` is not supported."
     * Workaround: send this in the body
     * {
     *   "idwarehouse": 0
     * }
     */
    /*
    function validate($id, $idwarehouse=0)
    {
        if(! DolibarrApiAccess::$user->rights->expensereport->creer) {
			throw new RestException(401);
		}
        $result = $this->expensereport->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'expensereport not found');
        }
		
		if( ! DolibarrApi::_checkAccessToResource('expensereport',$this->expensereport->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}
        
        if( ! $this->expensereport->valid(DolibarrApiAccess::$user, $idwarehouse)) {
            throw new RestException(500, 'Error when validate expensereport');
        }
        
        return array(
            'success' => array(
                'code' => 200,
                'message' => 'expensereport validated'
            )
        );
    }*/
    
    /**
     * Validate fields before create or update object
     * 
     * @param   array           $data   Array with data to verify
     * @return  array           
     * @throws  RestException
     */
    function _validate($data)
    {
        $expensereport = array();
        foreach (ExpenseReports::$FIELDS as $field) {
            if (!isset($data[$field]))
                throw new RestException(400, "$field field missing");
            $expensereport[$field] = $data[$field];
            
        }
        return $expensereport;
    }
}
