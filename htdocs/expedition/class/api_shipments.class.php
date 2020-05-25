<?php
/* Copyright (C) 2015       Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2016       Laurent Destailleur     <eldy@users.sourceforge.net>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

 use Luracast\Restler\RestException;

 require_once DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php';

/**
 * API class for shipments
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class Shipments extends DolibarrApi
{

    /**
     * @var array   $FIELDS     Mandatory fields, checked when create and update object
     */
    static $FIELDS = array(
        'socid',
        'origin_id',
        'origin_type',
    );

    /**
     * @var Expedition $shipment {@type Expedition}
     */
    public $shipment;

    /**
     * Constructor
     */
    public function __construct()
    {
        global $db, $conf;
        $this->db = $db;
        $this->shipment = new Expedition($this->db);
    }

    /**
     * Get properties of a shipment object
     *
     * Return an array with shipment informations
     *
     * @param       int         $id         ID of shipment
     * @return 	array|mixed data without useless information
     *
     * @throws 	RestException
     */
    public function get($id)
    {
        if (!DolibarrApiAccess::$user->rights->expedition->lire) {
            throw new RestException(401);
        }

        $result = $this->shipment->fetch($id);
        if (!$result) {
            throw new RestException(404, 'Shipment not found');
        }

        if (!DolibarrApi::_checkAccessToResource('expedition', $this->shipment->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->shipment->fetchObjectLinked();
        return $this->_cleanObjectDatas($this->shipment);
    }



    /**
     * List shipments
     *
     * Get a list of shipments
     *
     * @param string	       $sortfield	        Sort field
     * @param string	       $sortorder	        Sort order
     * @param int		       $limit		        Limit for list
     * @param int		       $page		        Page number
     * @param string   	       $thirdparty_ids	    Thirdparty ids to filter shipments of (example '1' or '1,2,3') {@pattern /^[0-9,]*$/i}
     * @param string           $sqlfilters          Other criteria to filter answers separated by a comma. Syntax example "(t.ref:like:'SO-%') and (t.date_creation:<:'20160101')"
     * @return  array                               Array of shipment objects
     *
     * @throws RestException
     */
    public function index($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $thirdparty_ids = '', $sqlfilters = '')
    {
        global $db, $conf;

        $obj_ret = array();

        // case of external user, $thirdparty_ids param is ignored and replaced by user's socid
        $socids = DolibarrApiAccess::$user->socid ? DolibarrApiAccess::$user->socid : $thirdparty_ids;

        // If the internal user must only see his customers, force searching by him
        $search_sale = 0;
        if (!DolibarrApiAccess::$user->rights->societe->client->voir && !$socids) $search_sale = DolibarrApiAccess::$user->id;

        $sql = "SELECT t.rowid";
        if ((!DolibarrApiAccess::$user->rights->societe->client->voir && !$socids) || $search_sale > 0) $sql .= ", sc.fk_soc, sc.fk_user"; // We need these fields in order to filter by sale (including the case where the user can only see his prospects)
        $sql .= " FROM ".MAIN_DB_PREFIX."expedition as t";

        if ((!DolibarrApiAccess::$user->rights->societe->client->voir && !$socids) || $search_sale > 0) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc"; // We need this table joined to the select in order to filter by sale

        $sql .= ' WHERE t.entity IN ('.getEntity('expedition').')';
        if ((!DolibarrApiAccess::$user->rights->societe->client->voir && !$socids) || $search_sale > 0) $sql .= " AND t.fk_soc = sc.fk_soc";
        if ($socids) $sql .= " AND t.fk_soc IN (".$socids.")";
        if ($search_sale > 0) $sql .= " AND t.rowid = sc.fk_soc"; // Join for the needed table to filter by sale
        // Insert sale filter
        if ($search_sale > 0)
        {
            $sql .= " AND sc.fk_user = ".$search_sale;
        }
        // Add sql filters
        if ($sqlfilters)
        {
            if (!DolibarrApi::_checkFilters($sqlfilters))
            {
                throw new RestException(503, 'Error when validating parameter sqlfilters '.$sqlfilters);
            }
            $regexstring = '\(([^:\'\(\)]+:[^:\'\(\)]+:[^:\(\)]+)\)';
            $sql .= " AND (".preg_replace_callback('/'.$regexstring.'/', 'DolibarrApi::_forge_criteria_callback', $sqlfilters).")";
        }

        $sql .= $db->order($sortfield, $sortorder);
        if ($limit) {
            if ($page < 0)
            {
                $page = 0;
            }
            $offset = $limit * $page;

            $sql .= $db->plimit($limit + 1, $offset);
        }

        dol_syslog("API Rest request");
        $result = $db->query($sql);

        if ($result)
        {
            $num = $db->num_rows($result);
            $min = min($num, ($limit <= 0 ? $num : $limit));
            $i = 0;
            while ($i < $min)
            {
                $obj = $db->fetch_object($result);
                $shipment_static = new Expedition($db);
                if ($shipment_static->fetch($obj->rowid)) {
                    $obj_ret[] = $this->_cleanObjectDatas($shipment_static);
                }
                $i++;
            }
        }
        else {
            throw new RestException(503, 'Error when retrieve commande list : '.$db->lasterror());
        }
        if (!count($obj_ret)) {
            throw new RestException(404, 'No shipment found');
        }
        return $obj_ret;
    }

    /**
     * Create shipment object
     *
     * @param   array   $request_data   Request data
     * @return  int     ID of shipment
     */
    public function post($request_data = null)
    {
        if (!DolibarrApiAccess::$user->rights->expedition->creer) {
            throw new RestException(401, "Insuffisant rights");
        }
        // Check mandatory fields
        $result = $this->_validate($request_data);

        foreach ($request_data as $field => $value) {
            $this->shipment->$field = $value;
        }
        if (isset($request_data["lines"])) {
            $lines = array();
            foreach ($request_data["lines"] as $line) {
                array_push($lines, (object) $line);
            }
            $this->shipment->lines = $lines;
        }

        if ($this->shipment->create(DolibarrApiAccess::$user) < 0) {
            throw new RestException(500, "Error creating shipment", array_merge(array($this->shipment->error), $this->shipment->errors));
        }

        return $this->shipment->id;
    }

    // /**
    //  * Get lines of an shipment
    //  *
    //  * @param int   $id             Id of shipment
    //  *
    //  * @url	GET {id}/lines
    //  *
    //  * @return int
    //  */
    /*
    public function getLines($id)
    {
        if(! DolibarrApiAccess::$user->rights->expedition->lire) {
            throw new RestException(401);
        }

        $result = $this->shipment->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'Shipment not found');
        }

        if( ! DolibarrApi::_checkAccessToResource('expedition',$this->shipment->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }
        $this->shipment->getLinesArray();
        $result = array();
        foreach ($this->shipment->lines as $line) {
            array_push($result,$this->_cleanObjectDatas($line));
        }
        return $result;
    }
    */

    // /**
    //  * Add a line to given shipment
    //  *
    //  * @param int   $id             Id of shipment to update
    //  * @param array $request_data   ShipmentLine data
    //  *
    //  * @url	POST {id}/lines
    //  *
    //  * @return int
    //  */
    /*
    public function postLine($id, $request_data = null)
    {
        if(! DolibarrApiAccess::$user->rights->expedition->creer) {
            throw new RestException(401);
        }

        $result = $this->shipment->fetch($id);
        if ( ! $result ) {
            throw new RestException(404, 'Shipment not found');
        }

        if( ! DolibarrApi::_checkAccessToResource('expedition',$this->shipment->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }
        $request_data = (object) $request_data;
        $updateRes = $this->shipment->addline(
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
                        $request_data->origin,
                        $request_data->origin_id,
                        $request_data->multicurrency_subprice
        );

        if ($updateRes > 0) {
            return $updateRes;

        }
        return false;
    }*/

    // /**
    //  * Update a line to given shipment
    //  *
    //  * @param int   $id             Id of shipment to update
    //  * @param int   $lineid         Id of line to update
    //  * @param array $request_data   ShipmentLine data
    //  *
    //  * @url	PUT {id}/lines/{lineid}
    //  *
    //  * @return object
    //  */
    /*
    public function putLine($id, $lineid, $request_data = null)
    {
        if (! DolibarrApiAccess::$user->rights->expedition->creer) {
            throw new RestException(401);
        }

        $result = $this->shipment->fetch($id);
        if ( ! $result ) {
            throw new RestException(404, 'Shipment not found');
        }

        if( ! DolibarrApi::_checkAccessToResource('expedition',$this->shipment->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }
        $request_data = (object) $request_data;
        $updateRes = $this->shipment->updateline(
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
                        $request_data->fk_unit,
                        $request_data->multicurrency_subprice
        );

        if ($updateRes > 0) {
            $result = $this->get($id);
            unset($result->line);
            return $this->_cleanObjectDatas($result);
        }
        return false;
    }*/

    /**
     * Delete a line to given shipment
     *
     *
     * @param int   $id             Id of shipment to update
     * @param int   $lineid         Id of line to delete
     *
     * @url	DELETE {id}/lines/{lineid}
     *
     * @return int
     *
     * @throws RestException 401
     * @throws RestException 404
     */
    public function deleteLine($id, $lineid)
    {
        if (!DolibarrApiAccess::$user->rights->expedition->creer) {
            throw new RestException(401);
        }

        $result = $this->shipment->fetch($id);
        if (!$result) {
            throw new RestException(404, 'Shipment not found');
        }

        if (!DolibarrApi::_checkAccessToResource('expedition', $this->shipment->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        // TODO Check the lineid $lineid is a line of ojbect

        $request_data = (object) $request_data;
        $updateRes = $this->shipment->deleteline(DolibarrApiAccess::$user, $lineid);
        if ($updateRes > 0) {
            return $this->get($id);
        }
        else
        {
            throw new RestException(405, $this->shipment->error);
        }
    }

    /**
     * Update shipment general fields (won't touch lines of shipment)
     *
     * @param int   $id             Id of shipment to update
     * @param array $request_data   Datas
     *
     * @return int
     */
    public function put($id, $request_data = null)
    {
        if (!DolibarrApiAccess::$user->rights->expedition->creer) {
            throw new RestException(401);
        }

        $result = $this->shipment->fetch($id);
        if (!$result) {
            throw new RestException(404, 'Shipment not found');
        }

        if (!DolibarrApi::_checkAccessToResource('expedition', $this->shipment->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }
        foreach ($request_data as $field => $value) {
            if ($field == 'id') continue;
            $this->shipment->$field = $value;
        }

        if ($this->shipment->update(DolibarrApiAccess::$user) > 0)
        {
            return $this->get($id);
        }
        else
        {
            throw new RestException(500, $this->shipment->error);
        }
    }

    /**
     * Delete shipment
     *
     * @param   int     $id         Shipment ID
     *
     * @return  array
     */
    public function delete($id)
    {
    	if (!DolibarrApiAccess::$user->rights->expedition->supprimer) {
            throw new RestException(401);
        }
        $result = $this->shipment->fetch($id);
        if (!$result) {
            throw new RestException(404, 'Shipment not found');
        }

        if (!DolibarrApi::_checkAccessToResource('expedition', $this->shipment->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        if (!$this->shipment->delete(DolibarrApiAccess::$user)) {
            throw new RestException(500, 'Error when deleting shipment : '.$this->shipment->error);
        }

        return array(
            'success' => array(
                'code' => 200,
                'message' => 'Shipment deleted'
            )
        );
    }

    /**
     * Validate a shipment
     *
     * This may record stock movements if module stock is enabled and option to
     * decrease stock on shipment is on.
     *
     * @param   int $id             Shipment ID
     * @param   int $notrigger      1=Does not execute triggers, 0= execute triggers
     *
     * @url POST    {id}/validate
     *
     * @return  array
     * \todo An error 403 is returned if the request has an empty body.
     * Error message: "Forbidden: Content type `text/plain` is not supported."
     * Workaround: send this in the body
     * {
     *   "notrigger": 0
     * }
     */
    public function validate($id, $notrigger = 0)
    {
        if (!DolibarrApiAccess::$user->rights->expedition->creer) {
            throw new RestException(401);
        }
        $result = $this->shipment->fetch($id);
        if (!$result) {
            throw new RestException(404, 'Shipment not found');
        }

        if (!DolibarrApi::_checkAccessToResource('expedition', $this->shipment->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $result = $this->shipment->valid(DolibarrApiAccess::$user, $notrigger);
        if ($result == 0) {
            throw new RestException(304, 'Error nothing done. May be object is already validated');
        }
        if ($result < 0) {
            throw new RestException(500, 'Error when validating Shipment: '.$this->shipment->error);
        }
        $result = $this->shipment->fetch($id);
        if (!$result) {
            throw new RestException(404, 'Shipment not found');
        }

        if (!DolibarrApi::_checkAccessToResource('expedition', $this->shipment->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->shipment->fetchObjectLinked();
        return $this->_cleanObjectDatas($this->shipment);
    }


    // /**
    //  *  Classify the shipment as invoiced
    //  *
    //  * @param int   $id           Id of the shipment
    //  *
    //  * @url     POST {id}/setinvoiced
    //  *
    //  * @return int
    //  *
    //  * @throws RestException 400
    //  * @throws RestException 401
    //  * @throws RestException 404
    //  * @throws RestException 405
    //  */
     /*
    public function setinvoiced($id)
    {

        if(! DolibarrApiAccess::$user->rights->expedition->creer) {
                throw new RestException(401);
        }
        if(empty($id)) {
                throw new RestException(400, 'Shipment ID is mandatory');
        }
        $result = $this->shipment->fetch($id);
        if( ! $result ) {
                throw new RestException(404, 'Shipment not found');
        }

        $result = $this->shipment->classifyBilled(DolibarrApiAccess::$user);
        if( $result < 0) {
                throw new RestException(400, $this->shipment->error);
        }
        return $result;
    }
    */


    //  /**
    //  * Create a shipment using an existing order.
    //  *
    //  * @param int   $orderid       Id of the order
    //  *
    //  * @url     POST /createfromorder/{orderid}
    //  *
    //  * @return int
    //  * @throws RestException 400
    //  * @throws RestException 401
    //  * @throws RestException 404
    //  * @throws RestException 405
    //  */
    /*
    public function createShipmentFromOrder($orderid)
    {

        require_once DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php';

        if(! DolibarrApiAccess::$user->rights->expedition->lire) {
                throw new RestException(401);
        }
        if(! DolibarrApiAccess::$user->rights->expedition->creer) {
                throw new RestException(401);
        }
        if(empty($proposalid)) {
                throw new RestException(400, 'Order ID is mandatory');
        }

        $order = new Commande($this->db);
        $result = $order->fetch($proposalid);
        if( ! $result ) {
                throw new RestException(404, 'Order not found');
        }

        $result = $this->shipment->createFromOrder($order, DolibarrApiAccess::$user);
        if( $result < 0) {
                throw new RestException(405, $this->shipment->error);
        }
        $this->shipment->fetchObjectLinked();
        return $this->_cleanObjectDatas($this->shipment);
    }
    */

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
    /**
     * Clean sensible object datas
     *
     * @param   object  $object    Object to clean
     * @return    array    Array of cleaned object properties
     */
    protected function _cleanObjectDatas($object)
    {
        // phpcs:enable
        $object = parent::_cleanObjectDatas($object);

        unset($object->thirdparty); // id already returned

        unset($object->note);
        unset($object->address);
        unset($object->barcode_type);
        unset($object->barcode_type_code);
        unset($object->barcode_type_label);
        unset($object->barcode_type_coder);

        if (!empty($object->lines) && is_array($object->lines))
        {
            foreach ($object->lines as $line)
            {
                unset($line->tva_tx);
                unset($line->vat_src_code);
                unset($line->total_ht);
                unset($line->total_ttc);
                unset($line->total_tva);
                unset($line->total_localtax1);
                unset($line->total_localtax2);
                unset($line->remise_percent);
            }
        }

        return $object;
    }

    /**
     * Validate fields before create or update object
     *
     * @param   array           $data   Array with data to verify
     * @return  array
     * @throws  RestException
     */
    private function _validate($data)
    {
        $shipment = array();
        foreach (Shipments::$FIELDS as $field) {
            if (!isset($data[$field]))
                throw new RestException(400, "$field field missing");
            $shipment[$field] = $data[$field];
        }
        return $shipment;
    }
}
