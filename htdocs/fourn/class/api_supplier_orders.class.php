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

require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';

/**
 * API class for supplier orders
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class SupplierOrders extends DolibarrApi
{
    /**
     *
     * @var array   $FIELDS     Mandatory fields, checked when create and update object
     */
    static $FIELDS = array(
        'socid'
    );

    /**
     * @var CommandeFournisseur $order {@type CommandeFournisseur}
     */
    public $order;

    /**
     * Constructor
     */
    function __construct()
    {
        global $db, $conf;
        $this->db = $db;
        $this->order = new CommandeFournisseur($this->db);
    }

    /**
     * Get properties of a supplier order object
     *
     * Return an array with supplier order information
     *
     * @param 	int 	$id ID of supplier order
     * @return 	array|mixed data without useless information
     *
     * @throws 	RestException
     */
    function get($id)
    {
		if(! DolibarrApiAccess::$user->rights->fournisseur->commande->lire) {
			throw new RestException(401);
		}

        $result = $this->order->fetch($id);
        if ( ! $result ) {
            throw new RestException(404, 'Supplier order not found');
        }

		if ( ! DolibarrApi::_checkAccessToResource('fournisseur',$this->order->id,'','commande')) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$this->order->fetchObjectLinked();
		return $this->_cleanObjectDatas($this->order);
    }

    /**
     * List orders
     *
     * Get a list of supplier orders
     *
     * @param string	$sortfield	      Sort field
     * @param string	$sortorder	      Sort order
     * @param int		$limit		      Limit for list
     * @param int		$page		      Page number
     * @param string   	$thirdparty_ids	  Thirdparty ids to filter orders of. {@example '1' or '1,2,3'} {@pattern /^[0-9,]*$/i}
     * @param string	$status		      Filter by order status : draft | validated | approved | running | received_start | received_end | cancelled | refused
     * @param string    $sqlfilters       Other criteria to filter answers separated by a comma. Syntax example "(t.ref:like:'SO-%') and (t.datec:<:'20160101')"
     * @return array                      Array of order objects
     *
	 * @throws RestException
     */
    function index($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $thirdparty_ids='', $status='', $sqlfilters = '')
    {
        global $db, $conf;

        $obj_ret = array();

        // case of external user, $thirdparty_ids param is ignored and replaced by user's socid
        $socids = DolibarrApiAccess::$user->societe_id ? DolibarrApiAccess::$user->societe_id : $thirdparty_ids;

        // If the internal user must only see his customers, force searching by him
        $search_sale = 0;
        if (! DolibarrApiAccess::$user->rights->societe->client->voir && !$socid) $search_sale = DolibarrApiAccess::$user->id;

        $sql = "SELECT t.rowid";
        if ((!DolibarrApiAccess::$user->rights->societe->client->voir && !$socid) || $search_sale > 0) $sql .= ", sc.fk_soc, sc.fk_user"; // We need these fields in order to filter by sale (including the case where the user can only see his prospects)
        $sql.= " FROM ".MAIN_DB_PREFIX."commande_fournisseur as t";

        if ((!DolibarrApiAccess::$user->rights->societe->client->voir && !$socid) || $search_sale > 0) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc"; // We need this table joined to the select in order to filter by sale

        $sql.= ' WHERE t.entity IN ('.getEntity('supplier_order').')';
        if ((!DolibarrApiAccess::$user->rights->societe->client->voir && !$socid) || $search_sale > 0) $sql.= " AND t.fk_soc = sc.fk_soc";
        if ($socids) $sql.= " AND t.fk_soc IN (".$socids.")";
        if ($search_sale > 0) $sql.= " AND t.rowid = sc.fk_soc";		// Join for the needed table to filter by sale

		// Filter by status
        if ($status == 'draft')     $sql.= " AND t.fk_statut IN (0)";
        if ($status == 'validated') $sql.= " AND t.fk_statut IN (1)";
        if ($status == 'approved')  $sql.= " AND t.fk_statut IN (2)";
        if ($status == 'running')   $sql.= " AND t.fk_statut IN (3)";
        if ($status == 'received_start') $sql.= " AND t.fk_statut IN (4)";
        if ($status == 'received_end')   $sql.= " AND t.fk_statut IN (5)";
        if ($status == 'cancelled') $sql.= " AND t.fk_statut IN (6,7)";
        if ($status == 'refused')   $sql.= " AND t.fk_statut IN (9)";
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

        $result = $db->query($sql);
        if ($result)
        {
            $i = 0;
            $num = $db->num_rows($result);
            $min = min($num, ($limit <= 0 ? $num : $limit));
            while ($i < $min)
            {
                $obj = $db->fetch_object($result);
                $order_static = new CommandeFournisseur($db);
                if($order_static->fetch($obj->rowid)) {
                    $obj_ret[] = $this->_cleanObjectDatas($order_static);
                }
                $i++;
            }
        }
        else {
            throw new RestException(503, 'Error when retrieve supplier order list : '.$db->lasterror());
        }
        if( ! count($obj_ret)) {
            throw new RestException(404, 'No supplier order found');
        }
		return $obj_ret;
    }

    /**
     * Create supplier order object
     *
     * @param array $request_data   Request datas
     * @return int  ID of supplier order
     */
    function post($request_data = null)
    {
        if(! DolibarrApiAccess::$user->rights->fournisseur->commande->creer) {
			throw new RestException(401, "Insuffisant rights");
		}
        // Check mandatory fields
        $result = $this->_validate($request_data);

        foreach($request_data as $field => $value) {
            $this->order->$field = $value;
        }
        if(! array_keys($request_data,'date')) {
            $this->order->date = dol_now();
        }
        /* We keep lines as an array
         if (isset($request_data["lines"])) {
            $lines = array();
            foreach ($request_data["lines"] as $line) {
                array_push($lines, (object) $line);
            }
            $this->order->lines = $lines;
        }*/

        if ($this->order->create(DolibarrApiAccess::$user) < 0) {
            throw new RestException(500, "Error creating order", array_merge(array($this->order->error), $this->order->errors));
        }
        return $this->order->id;
    }

    /**
     * Update supplier order
     *
     * @param int   $id             Id of supplier order to update
     * @param array $request_data   Datas
     * @return int
     */
    function put($id, $request_data = null)
    {
        if(! DolibarrApiAccess::$user->rights->fournisseur->commande->creer) {
			throw new RestException(401);
		}

        $result = $this->order->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'Supplier order not found');
        }

		if( ! DolibarrApi::_checkAccessToResource('fournisseur',$this->order->id,'','commande')) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

        foreach($request_data as $field => $value) {
            if ($field == 'id') continue;
            $this->order->$field = $value;
        }

        if($this->order->update($id, DolibarrApiAccess::$user))
            return $this->get ($id);

        return false;
    }

    /**
     * Delete supplier order
     *
     * @param int   $id Supplier order ID
     * @return type
     */
    function delete($id)
    {
        if(! DolibarrApiAccess::$user->rights->fournisseur->commande->supprimer) {
			throw new RestException(401);
		}
        $result = $this->order->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'Supplier order not found');
        }

		if( ! DolibarrApi::_checkAccessToResource('fournisseur',$this->order->id,'','commande')) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

        if( $this->order->delete(DolibarrApiAccess::$user) < 0)
        {
            throw new RestException(500);
        }

         return array(
            'success' => array(
                'code' => 200,
                'message' => 'Supplier order deleted'
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
    	if(! DolibarrApiAccess::$user->rights->fournisseur->commande->creer) {
    		throw new RestException(401);
    	}
    	$result = $this->order->fetch($id);
    	if( ! $result ) {
    		throw new RestException(404, 'Order not found');
    	}

    	if( ! DolibarrApi::_checkAccessToResource('fournisseur',$this->order->id,'','commande')) {
    		throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
    	}

    	$result = $this->order->valid(DolibarrApiAccess::$user, $idwarehouse, $notrigger);
    	if ($result == 0) {
    		throw new RestException(304, 'Error nothing done. May be object is already validated');
    	}
    	if ($result < 0) {
    		throw new RestException(500, 'Error when validating Order: '.$this->order->error);
    	}

    	return array(
	    	'success' => array(
		    	'code' => 200,
		    	'message' => 'Order validated (Ref='.$this->order->ref.')'
	    	)
    	);
    }

    /**
     * Clean sensible object datas
     *
     * @param   Object  $object    Object to clean
     * @return  array              Array of cleaned object properties
     */
    function _cleanObjectDatas($object)
    {

        $object = parent::_cleanObjectDatas($object);

        unset($object->rowid);
        unset($object->barcode_type);
        unset($object->barcode_type_code);
        unset($object->barcode_type_label);
        unset($object->barcode_type_coder);

        return $object;
    }

    /**
     * Validate fields before create or update object
     *
     * @param array $data   Datas to validate
     * @return array
     *
     * @throws RestException
     */
    function _validate($data)
    {
        $order = array();
        foreach (SupplierOrders::$FIELDS as $field) {
            if (!isset($data[$field]))
                throw new RestException(400, "$field field missing");
            $order[$field] = $data[$field];
        }
        return $order;
    }
}
