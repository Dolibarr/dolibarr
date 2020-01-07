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

require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';

/**
 * API class for supplier invoices
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class SupplierInvoices extends DolibarrApi
{
    /**
     *
     * @var array   $FIELDS     Mandatory fields, checked when create and update object
     */
    static $FIELDS = array(
        'socid',
    );

    /**
     * @var FactureFournisseur $invoice {@type FactureFournisseur}
     */
    public $invoice;

    /**
     * Constructor
     */
    public function __construct()
    {
        global $db, $conf;
        $this->db = $db;
        $this->invoice = new FactureFournisseur($this->db);
    }

    /**
     * Get properties of a supplier invoice object
     *
     * Return an array with supplier invoice information
     *
     * @param 	int 	$id ID of supplier invoice
     * @return 	array|mixed data without useless information
     *
     * @throws 	RestException
     */
    public function get($id)
    {
        if(! DolibarrApiAccess::$user->rights->fournisseur->facture->lire) {
            throw new RestException(401);
        }

        $result = $this->invoice->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'Supplier invoice not found');
        }

        if( ! DolibarrApi::_checkAccessToResource('fournisseur', $this->invoice->id, 'facture_fourn', 'facture')) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->invoice->fetchObjectLinked();
        return $this->_cleanObjectDatas($this->invoice);
    }

    /**
     * List invoices
     *
     * Get a list of supplier invoices
     *
     * @param string	$sortfield	      Sort field
     * @param string	$sortorder	      Sort order
     * @param int		$limit		      Limit for list
     * @param int		$page		      Page number
     * @param string   	$thirdparty_ids	  Thirdparty ids to filter invoices of. {@example '1' or '1,2,3'} {@pattern /^[0-9,]*$/i}
     * @param string	$status		      Filter by invoice status : draft | unpaid | paid | cancelled
     * @param string    $sqlfilters       Other criteria to filter answers separated by a comma. Syntax example "(t.ref:like:'SO-%') and (t.datec:<:'20160101')"
     * @return array                      Array of invoice objects
     *
     * @throws RestException
     */
    public function index($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $thirdparty_ids = '', $status = '', $sqlfilters = '')
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
        $sql.= " FROM ".MAIN_DB_PREFIX."facture_fourn as t";

        if ((!DolibarrApiAccess::$user->rights->societe->client->voir && !$socid) || $search_sale > 0) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc"; // We need this table joined to the select in order to filter by sale

        $sql.= ' WHERE t.entity IN ('.getEntity('supplier_invoice').')';
        if ((!DolibarrApiAccess::$user->rights->societe->client->voir && !$socid) || $search_sale > 0) $sql.= " AND t.fk_soc = sc.fk_soc";
        if ($socids) $sql.= " AND t.fk_soc IN (".$socids.")";
        if ($search_sale > 0) $sql.= " AND t.rowid = sc.fk_soc";		// Join for the needed table to filter by sale

        // Filter by status
        if ($status == 'draft') {
            $sql.= " AND t.fk_statut IN (0)";
        }
        if ($status == 'unpaid') {
            $sql.= " AND t.fk_statut IN (1)";
        }
        if ($status == 'paid') {
            $sql.= " AND t.fk_statut IN (2)";
        }
        if ($status == 'cancelled') {
            $sql.= " AND t.fk_statut IN (3)";
        }
        // Insert sale filter
        if ($search_sale > 0) {
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
        if ($result) {
            $i = 0;
            $num = $db->num_rows($result);
            $min = min($num, ($limit <= 0 ? $num : $limit));
            while ($i < $min)
            {
                $obj = $db->fetch_object($result);
                $invoice_static = new FactureFournisseur($db);
                if($invoice_static->fetch($obj->rowid)) {
                    $obj_ret[] = $this->_cleanObjectDatas($invoice_static);
                }
                $i++;
            }
        }
        else {
            throw new RestException(503, 'Error when retrieve supplier invoice list : '.$db->lasterror());
        }
        if ( ! count($obj_ret)) {
            throw new RestException(404, 'No supplier invoice found');
        }
        return $obj_ret;
    }

    /**
     * Create supplier invoice object
     *
     * @param array $request_data   Request datas
     * @return int  ID of supplier invoice
     */
    public function post($request_data = null)
    {
        if(! DolibarrApiAccess::$user->rights->fournisseur->facture->creer) {
            throw new RestException(401, "Insuffisant rights");
        }
        // Check mandatory fields
        $result = $this->_validate($request_data);

        foreach($request_data as $field => $value) {
            $this->invoice->$field = $value;
        }
        if(! array_key_exists('date', $request_data)) {
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

        if ($this->invoice->create(DolibarrApiAccess::$user) < 0) {
            throw new RestException(500, "Error creating order", array_merge(array($this->invoice->error), $this->invoice->errors));
        }
        return $this->invoice->id;
    }

    /**
     * Update supplier invoice
     *
     * @param int   $id             Id of supplier invoice to update
     * @param array $request_data   Datas
     * @return int
     */
    public function put($id, $request_data = null)
    {
        if(! DolibarrApiAccess::$user->rights->fournisseur->facture->creer) {
            throw new RestException(401);
        }

        $result = $this->invoice->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'Supplier invoice not found');
        }

        if( ! DolibarrApi::_checkAccessToResource('fournisseur', $this->invoice->id, 'facture_fourn', 'facture')) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        foreach($request_data as $field => $value) {
            if ($field == 'id') continue;
            $this->invoice->$field = $value;
        }

        if($this->invoice->update($id, DolibarrApiAccess::$user))
            return $this->get($id);

        return false;
    }

    /**
     * Delete supplier invoice
     *
     * @param int   $id Supplier invoice ID
     * @return type
     */
    public function delete($id)
    {
        if(! DolibarrApiAccess::$user->rights->fournisseur->facture->supprimer) {
            throw new RestException(401);
        }
        $result = $this->invoice->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'Supplier invoice not found');
        }

        if( ! DolibarrApi::_checkAccessToResource('fournisseur', $this->invoice->id, 'facture_fourn', 'facture')) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        if( $this->invoice->delete(DolibarrApiAccess::$user) < 0)
        {
            throw new RestException(500);
        }

         return array(
            'success' => array(
                'code' => 200,
                'message' => 'Supplier invoice deleted'
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
    public function validate($id, $idwarehouse = 0, $notrigger = 0)
    {
        if(! DolibarrApiAccess::$user->rights->fournisseur->facture->creer) {
            throw new RestException(401);
        }
        $result = $this->invoice->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'Invoice not found');
        }

        if( ! DolibarrApi::_checkAccessToResource('fournisseur', $this->invoice->id, 'facture_fourn', 'facture')) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $result = $this->invoice->validate(DolibarrApiAccess::$user, '', $idwarehouse, $notrigger);
        if ($result == 0) {
            throw new RestException(304, 'Error nothing done. May be object is already validated');
        }
        if ($result < 0) {
            throw new RestException(500, 'Error when validating Invoice: '.$this->invoice->error);
        }

        return array(
            'success' => array(
                'code' => 200,
                'message' => 'Invoice validated (Ref='.$this->invoice->ref.')'
            )
        );
    }

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
    /**
     * Clean sensible object datas
     *
     * @param   Object  $object    Object to clean
     * @return  array              Array of cleaned object properties
     */
    protected function _cleanObjectDatas($object)
    {
        // phpcs:enable
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
    private function _validate($data)
    {
        $invoice = array();
        foreach (SupplierInvoices::$FIELDS as $field) {
            if (!isset($data[$field]))
                throw new RestException(400, "$field field missing");
            $invoice[$field] = $data[$field];
        }
        return $invoice;
    }
}
