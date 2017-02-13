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

 require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';

/**
 * API class for invoice object
 *
 * @smart-auto-routing false
 * @access protected 
 * @class  DolibarrApiAccess {@requires user,external}
 * @deprecated Use Invoices instead (defined in api_invoices.class.php)
 */
class InvoiceApi extends DolibarrApi
{
    /**
     *
     * @var array   $FIELDS     Mandatory fields, checked when create and update object 
     */
    static $FIELDS = array(
        'socid'
    );

    /**
     * @var Facture $invoice {@type Facture}
     */
    public $invoice;

    /**
     * Constructor <b>Warning: Deprecated</b>
     *
     * @url     GET invoice/
     * 
     */
    function __construct()
    {
		global $db, $conf;
		$this->db = $db;
        $this->invoice = new Facture($this->db);
    }

    /**
     * Get properties of a invoice object <b>Warning: Deprecated</b>
     *
     * Return an array with invoice informations
     * 
     * @param 	int 	$id ID of invoice
     * @return 	array|mixed data without useless information
     *
     * @url	GET invoice/{id}
     * @throws 	RestException
     */
    function get($id)
    {		
		if(! DolibarrApiAccess::$user->rights->facture->lire) {
			throw new RestException(401);
		}
			
        $result = $this->invoice->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'Facture not found');
        }
		
		if( ! DolibarrApi::_checkAccessToResource('facture',$this->invoice->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		return $this->_cleanObjectDatas($this->invoice);
    }

    /**
     * List invoices <b>Warning: Deprecated</b>
     * 
     * Get a list of invoices
     * 
     * @param string	$sortfield	Sort field
     * @param string	$sortorder	Sort order
     * @param int		$limit		Limit for list
     * @param int		$page		Page number
     * @param int       $socid      Filter list with thirdparty ID
     * @param string	$mode		Filter by invoice status : draft | unpaid | paid | cancelled
     *
     * @return array Array of invoice objects
     *
     * @url	GET invoice/list
     * @url	GET invoice/list/{mode}
     * @url GET thirdparty/{socid}/invoice/list
     * @url GET thirdparty/{socid}/invoice/list/{mode} 
     */
    function getList($sortfield = "s.rowid", $sortorder = 'ASC', $limit = 0, $page = 0, $socid=0, $mode='') {
        global $db, $conf;
        
        $obj_ret = array();
        
        $socid = DolibarrApiAccess::$user->societe_id ? DolibarrApiAccess::$user->societe_id : $socid;
            
        // If the internal user must only see his customers, force searching by him
        $search_sale = 0;
        if (! DolibarrApiAccess::$user->rights->societe->client->voir && !$socid) $search_sale = DolibarrApiAccess::$user->id;

        $sql = "SELECT s.rowid";
        if ((!DolibarrApiAccess::$user->rights->societe->client->voir && !$socid) || $search_sale > 0) $sql .= ", sc.fk_soc, sc.fk_user"; // We need these fields in order to filter by sale (including the case where the user can only see his prospects)
        $sql.= " FROM ".MAIN_DB_PREFIX."facture as s";
        
        if ((!DolibarrApiAccess::$user->rights->societe->client->voir && !$socid) || $search_sale > 0) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc"; // We need this table joined to the select in order to filter by sale

        $sql.= ' WHERE s.entity IN ('.getEntity('facture', 1).')';
        if ((!DolibarrApiAccess::$user->rights->societe->client->voir && !$socid) || $search_sale > 0) $sql.= " AND s.fk_soc = sc.fk_soc";
        if ($socid) $sql.= " AND s.fk_soc = ".$socid;
        if ($search_sale > 0) $sql.= " AND s.rowid = sc.fk_soc";		// Join for the needed table to filter by sale
        
        
		// Example of use $mode
        if ($mode == 'draft') $sql.= " AND s.fk_statut IN (0)";
        if ($mode == 'unpaid') $sql.= " AND s.fk_statut IN (1)";
        if ($mode == 'paid') $sql.= " AND s.fk_statut IN (2)";
        if ($mode == 'cancelled') $sql.= " AND s.fk_statut IN (3)";
        
        // Insert sale filter
        if ($search_sale > 0)
        {
            $sql .= " AND sc.fk_user = ".$search_sale;
        }
        
        $nbtotalofrecords = '';
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
            $i=0;
            $num = $db->num_rows($result);
            while ($i < min($num, ($limit <= 0 ? $num : $limit)))
            {
                $obj = $db->fetch_object($result);
                $invoice_static = new Facture($db);
                if($invoice_static->fetch($obj->rowid)) {
                    $obj_ret[] = $this->_cleanObjectDatas($invoice_static);
                }
                $i++;
            }
        }
        else {
            throw new RestException(503, 'Error when retrieve invoice list : '.$db->lasterror());
        }
        if( ! count($obj_ret)) {
            throw new RestException(404, 'No invoice found');
        }
		return $obj_ret;
    }
    
    /**
     * Create invoice object <b>Warning: Deprecated</b>
     * 
     * @param array $request_data   Request datas
     * @return int  ID of invoice
     *
     * @url	POST invoice/
     */
    function post($request_data = NULL)
    {
        if(! DolibarrApiAccess::$user->rights->facture->creer) {
			throw new RestException(401);
		}
        // Check mandatory fields
        $result = $this->_validate($request_data);
        
        foreach($request_data as $field => $value) {
            $this->invoice->$field = $value;
        }
        if(! array_keys($request_data,'date')) {
            $this->invoice->date = dol_now();
        }
        if( ! $this->invoice->create(DolibarrApiAccess::$user)) {
            throw new RestException(500);
        }
        return $this->invoice->id;
    }

    /**
     * Update invoice <b>Warning: Deprecated</b>
     *
     * @param int   $id             Id of invoice to update
     * @param array $request_data   Datas   
     * @return int 
     * 
     * @url	PUT invoice/{id}
     */
    function put($id, $request_data = NULL)
    {
        if(! DolibarrApiAccess::$user->rights->facture->creer) {
			throw new RestException(401);
		}
        
        $result = $this->invoice->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'Facture not found');
        }
		
		if( ! DolibarrApi::_checkAccessToResource('facture',$this->invoice->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

        foreach($request_data as $field => $value) {
            if ($field == 'id') continue;
            $this->invoice->$field = $value;
        }
        
        if($this->invoice->update($id, DolibarrApiAccess::$user))
            return $this->get ($id);
        
        return false;
    }
    
    /**
     * Delete invoice <b>Warning: Deprecated</b>
     *
     * @param int   $id Invoice ID
     * @return type
     * 
     * @url	DELETE invoice/{id} 
     */
    function delete($id)
    {
        if(! DolibarrApiAccess::$user->rights->facture->supprimer) {
			throw new RestException(401);
		}
        $result = $this->invoice->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'Facture not found');
        }
		
		if( ! DolibarrApi::_checkAccessToResource('facture',$this->facture->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}
        
        if( !$this->invoice->delete(DolibarrApiAccess::$user))
        {
            throw new RestException(500);
        }
        
         return array(
            'success' => array(
                'code' => 200,
                'message' => 'Facture deleted'
            )
        );
        
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
        $invoice = array();
        foreach (InvoiceApi::$FIELDS as $field) {
            if (!isset($data[$field]))
                throw new RestException(400, "$field field missing");
            $invoice[$field] = $data[$field];
        }
        return $invoice;
    }
}
