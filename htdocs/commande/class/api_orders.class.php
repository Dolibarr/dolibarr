<?php
/* Copyright (C) 2015   Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2016	Laurent Destailleur		<eldy@users.sourceforge.net>
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

 require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';

/**
 * API class for orders
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class Orders extends DolibarrApi
{

    /**
     * @var array   $FIELDS     Mandatory fields, checked when create and update object
     */
    static $FIELDS = array(
        'socid'
    );

    /**
     * @var Commande $commande {@type Commande}
     */
    public $commande;

    /**
     * Constructor
     */
    function __construct()
    {
		global $db, $conf;
		$this->db = $db;
        $this->commande = new Commande($this->db);
    }

    /**
     * Get properties of a commande object
     *
     * Return an array with commande informations
     *
     * @param       int         $id         ID of order
     * @return 	array|mixed data without useless information
	 *
     * @throws 	RestException
     */
    function get($id)
    {
		if(! DolibarrApiAccess::$user->rights->commande->lire) {
			throw new RestException(401);
		}

        $result = $this->commande->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'Order not found');
        }

		if( ! DolibarrApi::_checkAccessToResource('commande',$this->commande->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

        $this->commande->fetchObjectLinked();
		return $this->_cleanObjectDatas($this->commande);
    }

    
   
    /**
     * List orders
     *
     * Get a list of orders
     *
     * @param string	       $sortfield	        Sort field
     * @param string	       $sortorder	        Sort order
     * @param int		       $limit		        Limit for list
     * @param int		       $page		        Page number
     * @param string   	       $thirdparty_ids	    Thirdparty ids to filter orders of. {@example '1' or '1,2,3'} {@pattern /^[0-9,]*$/i}
     * @param string           $sqlfilters          Other criteria to filter answers separated by a comma. Syntax example "(t.ref:like:'SO-%') and (t.date_creation:<:'20160101')"
     * @return  array                               Array of order objects
     *
	 * @throws RestException
     */
    function index($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $thirdparty_ids = '', $sqlfilters = '') {
        global $db, $conf;

        $obj_ret = array();
        
        // case of external user, $thirdparty_ids param is ignored and replaced by user's socid
        $socids = DolibarrApiAccess::$user->societe_id ? DolibarrApiAccess::$user->societe_id : $thirdparty_ids;

        // If the internal user must only see his customers, force searching by him
        $search_sale = 0;
        if (! DolibarrApiAccess::$user->rights->societe->client->voir && !$socids) $search_sale = DolibarrApiAccess::$user->id;

        $sql = "SELECT t.rowid";
        if ((!DolibarrApiAccess::$user->rights->societe->client->voir && !$socids) || $search_sale > 0) $sql .= ", sc.fk_soc, sc.fk_user"; // We need these fields in order to filter by sale (including the case where the user can only see his prospects)
        $sql.= " FROM ".MAIN_DB_PREFIX."commande as t";

        if ((!DolibarrApiAccess::$user->rights->societe->client->voir && !$socids) || $search_sale > 0) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc"; // We need this table joined to the select in order to filter by sale

        $sql.= ' WHERE t.entity IN ('.getEntity('commande', 1).')';
        if ((!DolibarrApiAccess::$user->rights->societe->client->voir && !$socids) || $search_sale > 0) $sql.= " AND t.fk_soc = sc.fk_soc";
        if ($socids) $sql.= " AND t.fk_soc IN (".$socids.")";
        if ($search_sale > 0) $sql.= " AND t.rowid = sc.fk_soc";		// Join for the needed table to filter by sale
        // Insert sale filter
        if ($search_sale > 0)
        {
            $sql .= " AND sc.fk_user = ".$search_sale;
        }
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

        dol_syslog("API Rest request");
        $result = $db->query($sql);

        if ($result)
        {
            $num = $db->num_rows($result);
            while ($i < min($num, ($limit <= 0 ? $num : $limit)))
            {
                $obj = $db->fetch_object($result);
                $commande_static = new Commande($db);
                if($commande_static->fetch($obj->rowid)) {
                    $obj_ret[] = $this->_cleanObjectDatas($commande_static);
                }
                $i++;
            }
        }
        else {
            throw new RestException(503, 'Error when retrieve commande list : '.$db->lasterror());
        }
        if( ! count($obj_ret)) {
            throw new RestException(404, 'No order found');
        }
		return $obj_ret;
    }

    /**
     * Create order object
     *
     * @param   array   $request_data   Request data
     * @return  int     ID of commande
     */
    function post($request_data = NULL)
    {
      if(! DolibarrApiAccess::$user->rights->commande->creer) {
			  throw new RestException(401, "Insuffisant rights");
		  }
        // Check mandatory fields
        $result = $this->_validate($request_data);

        foreach($request_data as $field => $value) {
            $this->commande->$field = $value;
        }
        /*if (isset($request_data["lines"])) {
          $lines = array();
          foreach ($request_data["lines"] as $line) {
            array_push($lines, (object) $line);
          }
          $this->commande->lines = $lines;
        }*/
        if ($this->commande->create(DolibarrApiAccess::$user) < 0) {
            throw new RestException(500, "Error creating order", array_merge(array($this->commande->error), $this->commande->errors));
        }

        return $this->commande->id;
    }

    /**
     * Get lines of an order
     *
     * @param int   $id             Id of order
     *
     * @url	GET {id}/lines
     *
     * @return int
     */
    function getLines($id) {
      if(! DolibarrApiAccess::$user->rights->commande->lire) {
		  	throw new RestException(401);
		  }

      $result = $this->commande->fetch($id);
      if( ! $result ) {
         throw new RestException(404, 'Order not found');
      }

		  if( ! DolibarrApi::_checkAccessToResource('commande',$this->commande->id)) {
			  throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
      }
      $this->commande->getLinesArray();
      $result = array();
      foreach ($this->commande->lines as $line) {
        array_push($result,$this->_cleanObjectDatas($line));
      }
      return $result;
    }

    /**
     * Add a line to given order
     *
     * @param int   $id             Id of commande to update
     * @param array $request_data   Orderline data
     *
     * @url	POST {id}/lines
     *
     * @return int
     */
    function postLine($id, $request_data = NULL) {
      if(! DolibarrApiAccess::$user->rights->commande->creer) {
		  	throw new RestException(401);
		  }

      $result = $this->commande->fetch($id);
      if( ! $result ) {
         throw new RestException(404, 'Order not found');
      }

		  if( ! DolibarrApi::_checkAccessToResource('commande',$this->commande->id)) {
			  throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
      }
			$request_data = (object) $request_data;
      $updateRes = $this->commande->addline(
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

    /**
     * Update a line to given order
     *
     * @param int   $id             Id of commande to update
     * @param int   $lineid         Id of line to update
     * @param array $request_data   Orderline data
     *
     * @url	PUT {id}/lines/{lineid}
     *
     * @return object
     */
    function putLine($id, $lineid, $request_data = NULL) {
      if(! DolibarrApiAccess::$user->rights->commande->creer) {
		  	throw new RestException(401);
		  }

      $result = $this->commande->fetch($id);
      if( ! $result ) {
         throw new RestException(404, 'Commande not found');
      }

		  if( ! DolibarrApi::_checkAccessToResource('commande',$this->commande->id)) {
			  throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
      }
			$request_data = (object) $request_data;
      $updateRes = $this->commande->updateline(
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

    /**
     * Delete a line to given order
     *
     *
     * @param int   $id             Id of commande to update
     * @param int   $lineid         Id of line to delete
     *
     * @url	DELETE {id}/lines/{lineid}
     *
     * @return int
     */
    function delLine($id, $lineid) {
      if(! DolibarrApiAccess::$user->rights->commande->creer) {
		  	throw new RestException(401);
		  }

      $result = $this->commande->fetch($id);
      if( ! $result ) {
         throw new RestException(404, 'Commande not found');
      }

		  if( ! DolibarrApi::_checkAccessToResource('commande',$this->commande->id)) {
			  throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
      }
			$request_data = (object) $request_data;
      $updateRes = $this->commande->deleteline(DolibarrApiAccess::$user,$lineid);
      if ($updateRes > 0) {
        return $this->get($id);
      }
      return false;
    }

    /**
     * Update order general fields (won't touch lines of order)
     *
     * @param int   $id             Id of commande to update
     * @param array $request_data   Datas
     *
     * @return int
     */
    function put($id, $request_data = NULL) {
      if(! DolibarrApiAccess::$user->rights->commande->creer) {
		  	throw new RestException(401);
		  }

        $result = $this->commande->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'Commande not found');
        }

		if( ! DolibarrApi::_checkAccessToResource('commande',$this->commande->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}
        foreach($request_data as $field => $value) {
            if ($field == 'id') continue;
            $this->commande->$field = $value;
        }

        if($this->commande->update($id, DolibarrApiAccess::$user, 1, '', '', 'update'))
            return $this->get($id);

        return false;
    }

    /**
     * Delete order
     *
     * @param   int     $id         Order ID
     *
     * @return  array
     */
    function delete($id)
    {
        if(! DolibarrApiAccess::$user->rights->commande->supprimer) {
			throw new RestException(401);
		}
        $result = $this->commande->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'Order not found');
        }

		if( ! DolibarrApi::_checkAccessToResource('commande',$this->commande->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

        if( ! $this->commande->delete(DolibarrApiAccess::$user)) {
            throw new RestException(500, 'Error when delete order : '.$this->commande->error);
        }

        return array(
            'success' => array(
                'code' => 200,
                'message' => 'Order deleted'
            )
        );

    }

    /**
     * Validate an order
     *
     * @param   int $id             Order ID
     * @param   int $idwarehouse    Warehouse ID
     * @param   int $notrigger      1=Does not execute triggers, 0= execute triggers
     *
     * @url POST    {id}/validate
     *
     * @return  array
     * FIXME An error 403 is returned if the request has an empty body.
     * Error message: "Forbidden: Content type `text/plain` is not supported."
     * Workaround: send this in the body
     * {
     *   "idwarehouse": 0,
     *   "notrigger": 0
     * }
     */
    function validate($id, $idwarehouse=0, $notrigger=0)
    {
        if(! DolibarrApiAccess::$user->rights->commande->creer) {
			throw new RestException(401);
		}
        $result = $this->commande->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'Order not found');
        }

		if( ! DolibarrApi::_checkAccessToResource('commande',$this->commande->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$result = $this->commande->valid(DolibarrApiAccess::$user, $idwarehouse, $notrigger);
		if ($result == 0) {
		    throw new RestException(500, 'Error nothing done. May be object is already validated');
		}
		if ($result < 0) {
		    throw new RestException(500, 'Error when validating Order: '.$this->commande->error);
		}

        return array(
            'success' => array(
                'code' => 200,
                'message' => 'Order validated'
            )
        );
    }

    /**
     * Validate fields before create or update object
     *
     * @param   array           $data   Array with data to verify
     * @return  array
     * @throws  RestException
     */
    function _validate($data)
    {
        $commande = array();
        foreach (Orders::$FIELDS as $field) {
            if (!isset($data[$field]))
                throw new RestException(400, "$field field missing");
            $commande[$field] = $data[$field];

        }
        return $commande;
    }
}
