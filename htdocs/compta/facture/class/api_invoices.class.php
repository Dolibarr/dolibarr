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
 * API class for invoices
 *
 * @access protected 
 * @class  DolibarrApiAccess {@requires user,external}
 */
class Invoices extends DolibarrApi
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
     * Constructor
     */
    function __construct()
    {
		global $db, $conf;
		$this->db = $db;
        $this->invoice = new Facture($this->db);
    }

    /**
     * Get properties of a invoice object
     *
     * Return an array with invoice informations
     * 
     * @param 	int 	$id ID of invoice
     * @return 	array|mixed data without useless information
     *
     * @throws 	RestException
     */
    function get($id)
    {		
		if(! DolibarrApiAccess::$user->rights->facture->lire) {
			throw new RestException(401);
		}
			
        $result = $this->invoice->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'Invoice not found');
        }
		
		if( ! DolibarrApi::_checkAccessToResource('facture',$this->invoice->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		return $this->_cleanObjectDatas($this->invoice);
    }

    /**
     * List invoices
     * 
     * Get a list of invoices
     * 
     * @param string	$sortfield	Sort field
     * @param string	$sortorder	Sort order
     * @param int		$limit		Limit for list
     * @param int		$page		Page number
     * @param int       $socid      Filter list with thirdparty ID
     * @param string	$status		Filter by invoice status : draft | unpaid | paid | cancelled
     * @param string    $sqlfilters Other criteria to filter answers separated by a comma. Syntax example "(t.ref:like:'SO-%') and (t.date_creation:<:'20160101')"
     * @return array                Array of invoice objects
     *
	 * @throws RestException
     */
    function index($sortfield = "s.rowid", $sortorder = 'ASC', $limit = 0, $page = 0, $socid=0, $status='', $sqlfilters = '') {
        global $db, $conf;
        
        $obj_ret = array();
        
        $socid = DolibarrApiAccess::$user->societe_id ? DolibarrApiAccess::$user->societe_id : $socid;
            
        // If the internal user must only see his customers, force searching by him
        if (! DolibarrApiAccess::$user->rights->societe->client->voir && !$socid) $search_sale = DolibarrApiAccess::$user->id;

        $sql = "SELECT t.rowid";
        if ((!DolibarrApiAccess::$user->rights->societe->client->voir && !$socid) || $search_sale > 0) $sql .= ", sc.fk_soc, sc.fk_user"; // We need these fields in order to filter by sale (including the case where the user can only see his prospects)
        $sql.= " FROM ".MAIN_DB_PREFIX."facture as t";
        
        if ((!DolibarrApiAccess::$user->rights->societe->client->voir && !$socid) || $search_sale > 0) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc"; // We need this table joined to the select in order to filter by sale

        $sql.= ' WHERE t.entity IN ('.getEntity('facture', 1).')';
        if ((!DolibarrApiAccess::$user->rights->societe->client->voir && !$socid) || $search_sale > 0) $sql.= " AND t.fk_soc = sc.fk_soc";
        if ($socid) $sql.= " AND t.fk_soc = ".$socid;
        if ($search_sale > 0) $sql.= " AND t.rowid = sc.fk_soc";		// Join for the needed table to filter by sale
        
		// Filter by status
        if ($status == 'draft')     $sql.= " AND t.fk_statut IN (0)";
        if ($status == 'unpaid')    $sql.= " AND t.fk_statut IN (1)";
        if ($status == 'paid')      $sql.= " AND t.fk_statut IN (2)";
        if ($status == 'cancelled') $sql.= " AND t.fk_statut IN (3)";
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
            $num = $db->num_rows($result);
            while ($i < min($num, ($limit <= 0 ? $num : $limit)))
            {
                $obj = $db->fetch_object($result);
                $invoice_static = new Facture($db);
                if($invoice_static->fetch($obj->rowid)) {
                    $obj_ret[] = parent::_cleanObjectDatas($invoice_static);
                }
                $i++;
            }
        }
        else {
            throw new RestException(503, 'Error when retrieve invoice list');
        }
        if( ! count($obj_ret)) {
            throw new RestException(404, 'No invoice found');
        }
		return $obj_ret;
    }
    
    /**
     * Create invoice object
     * 
     * @param array $request_data   Request datas
     * @return int  ID of invoice
     */
    function post($request_data = NULL)
    {
        if(! DolibarrApiAccess::$user->rights->facture->creer) {
			throw new RestException(401, "Insuffisant rights");
		}
        // Check mandatory fields
        $result = $this->_validate($request_data);
        
        foreach($request_data as $field => $value) {
            $this->invoice->$field = $value;
        }
        if(! array_keys($request_data,'date')) {
            $this->invoice->date = dol_now();
        }
        /* We keep lines as an array
         if (isset($request_data["lines"])) {
            $lines = array();
            foreach ($request_data["lines"] as $line) {
                array_push($lines, (object) $line);
            }
            $this->invoice->lines = $lines;
        }*/
        
        if ($this->invoice->create(DolibarrApiAccess::$user) <= 0) {
            $errormsg = $this->invoice->error;
            throw new RestException(500, $errormsg ? $errormsg : "Error while creating order");
        }
        return $this->invoice->id;
    }

    /**
     * Update invoice
     *
     * @param int   $id             Id of invoice to update
     * @param array $request_data   Datas   
     * @return int 
     */
    function put($id, $request_data = NULL)
    {
        if(! DolibarrApiAccess::$user->rights->facture->creer) {
			throw new RestException(401);
		}
        
        $result = $this->invoice->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'Invoice not found');
        }
		
		if( ! DolibarrApi::_checkAccessToResource('facture',$this->invoice->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

        foreach($request_data as $field => $value) {
            $this->invoice->$field = $value;
        }
        
        if($this->invoice->update($id, DolibarrApiAccess::$user))
            return $this->get ($id);
        
        return false;
    }
    
    /**
     * Delete invoice
     *
     * @param int   $id Invoice ID
     * @return type
     */
    function delete($id)
    {
        if(! DolibarrApiAccess::$user->rights->facture->supprimer) {
			throw new RestException(401);
		}
        $result = $this->invoice->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'Invoice not found');
        }
		
		if( ! DolibarrApi::_checkAccessToResource('facture',$this->facture->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}
        
        if( $this->invoice->delete($id) < 0)
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
        foreach (Invoices::$FIELDS as $field) {
            if (!isset($data[$field]))
                throw new RestException(400, "$field field missing");
            $invoice[$field] = $data[$field];
        }
        return $invoice;
    }
}
