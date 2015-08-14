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

 require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';

/**
 * API class for commande object
 *
 * @smart-auto-routing false
 * @access protected 
 * @class  DolibarrApiAccess {@requires user,external}
 * 
 * @category Api
 * @package  Api
 * 
 *
 */
class CommandeApi extends DolibarrApi
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
     *
     * @url     GET order/
     * 
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
     * @param		string		$ref		Ref of object
     * @param		string		$ref_ext		External reference of object
     * @param		string		$ref_int		Internal reference of other object
     * @return 	array|mixed data without useless information
	 *
     * @url	GET order/{id} 
     * @throws 	RestException
     */
    function get($id='',$ref='', $ref_ext='', $ref_int='')
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
     * @param int		$mode		Use this param to filter list
     * @param string	$sortfield	Sort field
     * @param string	$sortorder	Sort order
     * @param int		$limit		Limit for list
     * @param int		$page		Page number
     *
     * @url     GET     /order/list
     * @return  array   Array of order objects
     */
    function getList($mode=0, $sortfield = "s.rowid", $sortorder = 'ASC', $limit = 0, $page = 0) {
        global $db, $conf;
        
        $obj_ret = array();
        
        $socid = DolibarrApiAccess::$user->societe_id ? DolibarrApiAccess::$user->societe_id : '';
            
        // If the internal user must only see his customers, force searching by him
        if (! DolibarrApiAccess::$user->rights->societe->client->voir && !$socid) $search_sale = DolibarrApiAccess::$user->id;

        $sql = "SELECT s.rowid";
        if ((!DolibarrApiAccess::$user->rights->societe->client->voir && !$socid) || $search_sale > 0) $sql .= ", sc.fk_soc, sc.fk_user"; // We need these fields in order to filter by sale (including the case where the user can only see his prospects)
        $sql.= " FROM ".MAIN_DB_PREFIX."commande as s";
        
        if ((!DolibarrApiAccess::$user->rights->societe->client->voir && !$socid) || $search_sale > 0) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc"; // We need this table joined to the select in order to filter by sale
        
		// Example of use $mode
        //if ($mode == 1) $sql.= " AND s.client IN (1, 3)";
        //if ($mode == 2) $sql.= " AND s.client IN (2, 3)";

        $sql.= ' WHERE s.entity IN ('.getEntity('commande', 1).')';
        if ((!DolibarrApiAccess::$user->rights->societe->client->voir && !$socid) || $search_sale > 0) $sql.= " AND s.fk_soc = sc.fk_soc";
        if ($socid) $sql.= " AND s.fk_soc = ".$socid;
        if ($search_sale > 0) $sql.= " AND s.rowid = sc.fk_soc";		// Join for the needed table to filter by sale
        
        // Insert sale filter
        if ($search_sale > 0)
        {
            $sql .= " AND sc.fk_user = ".$search_sale;
        }
        
        $nbtotalofrecords = 0;
        if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
        {
            $result = $db->query($sql);
            $nbtotalofrecords = $db->num_rows($result);
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
                $commande_static = new Commande($db);
                if($commande_static->fetch($obj->rowid)) {
                    $obj_ret[] = parent::_cleanObjectDatas($commande_static);
                }
                $i++;
            }
        }
        else {
            throw new RestException(503, 'Error when retrieve commande list');
        }
        if( ! count($obj_ret)) {
            throw new RestException(404, 'No commande found');
        }
		return $obj_ret;
    }
    
    /**
     * Create order object
     *
     * @param   array   $request_data   Request datas
     * 
     * @url     POST    order/
     * 
     * @return  int     ID of commande
     */
    function post($request_data = NULL)
    {
        if(! DolibarrApiAccess::$user->rights->commande->creer) {
			throw new RestException(401);
		}
        // Check mandatory fields
        $result = $this->_validate($request_data);
        
        foreach($request_data as $field => $value) {
            $this->commande->$field = $value;
        }
        if(! $this->commande->create(DolibarrApiAccess::$user) ) {
            throw new RestException(401);
        }
        
        return $this->commande->ref;
    }

    /**
     * Update order
     *
     * @param int   $id             Id of commande to update
     * @param array $request_data   Datas   
     * 
     * @url	PUT order/{id}
     * 
     * @return int 
     */
    function put($id, $request_data = NULL)
    {
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
            $this->commande->$field = $value;
        }
        
        if($this->commande->update($id, DolibarrApiAccess::$user,1,'','','update'))
            return $this->get ($id);
        
        return false;
    }
    
    /**
     * Delete order
     *
     * @param   int     $id         Order ID
     * 
     * @url     DELETE  order/{id}
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
     * 
     * @url GET     order/{id}/validate
     * @url POST    order/{id}/validate
     *  
     * @return  array
     * 
     */
    function validOrder($id, $idwarehouse=0)
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
        
        if( ! $this->commande->valid(DolibarrApiAccess::$user, $idwarehouse)) {
            throw new RestException(500, 'Error when validate order');
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
        foreach (CommandeApi::$FIELDS as $field) {
            if (!isset($data[$field]))
                throw new RestException(400, "$field field missing");
            $commande[$field] = $data[$field];
        }
        return $commande;
    }
}
