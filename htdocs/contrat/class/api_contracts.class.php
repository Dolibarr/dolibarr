<?php
/* Copyright (C) 2015   Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2016	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2018       Frédéric France         <frederic.france@netlogic.fr>
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

 require_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';

/**
 * API class for contracts
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class Contracts extends DolibarrApi
{

    /**
     * @var array   $FIELDS     Mandatory fields, checked when create and update object
     */
    static $FIELDS = array(
        'socid',
        'date_contrat',
        'commercial_signature_id',
        'commercial_suivi_id'
    );

    /**
     * @var Contrat $contract {@type Contrat}
     */
    public $contract;

    /**
     * Constructor
     */
    public function __construct()
    {
        global $db, $conf;
        $this->db = $db;
        $this->contract = new Contrat($this->db);
    }

    /**
     * Get properties of a contract object
     *
     * Return an array with contract informations
     *
     * @param       int         $id         ID of contract
     * @return 	array|mixed data without useless information
     *
     * @throws 	RestException
     */
    public function get($id)
    {
        if(! DolibarrApiAccess::$user->rights->contrat->lire) {
            throw new RestException(401);
        }

        $result = $this->contract->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'Contract not found');
        }

        if( ! DolibarrApi::_checkAccessToResource('contrat', $this->contract->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->contract->fetchObjectLinked();
        return $this->_cleanObjectDatas($this->contract);
    }



    /**
     * List contracts
     *
     * Get a list of contracts
     *
     * @param string	       $sortfield	        Sort field
     * @param string	       $sortorder	        Sort order
     * @param int		       $limit		        Limit for list
     * @param int		       $page		        Page number
     * @param string   	       $thirdparty_ids	    Thirdparty ids to filter contracts of. {@example '1' or '1,2,3'} {@pattern /^[0-9,]*$/i}
     * @param string           $sqlfilters          Other criteria to filter answers separated by a comma. Syntax example "(t.ref:like:'SO-%') and (t.date_creation:<:'20160101')"
     * @return  array                               Array of contract objects
     *
     * @throws RestException
     */
    public function index($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $thirdparty_ids = '', $sqlfilters = '')
    {
        global $db, $conf;

        $obj_ret = array();

        // case of external user, $thirdparty_ids param is ignored and replaced by user's socid
        $socids = DolibarrApiAccess::$user->societe_id ? DolibarrApiAccess::$user->societe_id : $thirdparty_ids;

        // If the internal user must only see his customers, force searching by him
        $search_sale = 0;
        if (! DolibarrApiAccess::$user->rights->societe->client->voir && !$socids) $search_sale = DolibarrApiAccess::$user->id;

        $sql = "SELECT t.rowid";
        if ((!DolibarrApiAccess::$user->rights->societe->client->voir && !$socids) || $search_sale > 0) $sql .= ", sc.fk_soc, sc.fk_user"; // We need these fields in order to filter by sale (including the case where the user can only see his prospects)
        $sql.= " FROM ".MAIN_DB_PREFIX."contrat as t";

        if ((!DolibarrApiAccess::$user->rights->societe->client->voir && !$socids) || $search_sale > 0) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc"; // We need this table joined to the select in order to filter by sale

        $sql.= ' WHERE t.entity IN ('.getEntity('contrat').')';
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
            $min = min($num, ($limit <= 0 ? $num : $limit));
            $i=0;
            while ($i < $min)
            {
                $obj = $db->fetch_object($result);
                $contrat_static = new Contrat($db);
                if($contrat_static->fetch($obj->rowid)) {
                    $obj_ret[] = $this->_cleanObjectDatas($contrat_static);
                }
                $i++;
            }
        }
        else {
            throw new RestException(503, 'Error when retrieve contrat list : '.$db->lasterror());
        }
        if( ! count($obj_ret)) {
            throw new RestException(404, 'No contract found');
        }
        return $obj_ret;
    }

    /**
     * Create contract object
     *
     * @param   array   $request_data   Request data
     * @return  int     ID of contrat
     */
    public function post($request_data = null)
    {
        if(! DolibarrApiAccess::$user->rights->contrat->creer) {
            throw new RestException(401, "Insuffisant rights");
        }
        // Check mandatory fields
        $result = $this->_validate($request_data);

        foreach($request_data as $field => $value) {
            $this->contract->$field = $value;
        }
        /*if (isset($request_data["lines"])) {
          $lines = array();
          foreach ($request_data["lines"] as $line) {
            array_push($lines, (object) $line);
          }
          $this->contract->lines = $lines;
        }*/
        if ($this->contract->create(DolibarrApiAccess::$user) < 0) {
            throw new RestException(500, "Error creating contract", array_merge(array($this->contract->error), $this->contract->errors));
        }

        return $this->contract->id;
    }

    /**
     * Get lines of a contract
     *
     * @param int   $id             Id of contract
     *
     * @url	GET {id}/lines
     *
     * @return array
     */
    public function getLines($id)
    {
        if (! DolibarrApiAccess::$user->rights->contrat->lire) {
            throw new RestException(401);
        }

        $result = $this->contract->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'Contract not found');
        }

        if( ! DolibarrApi::_checkAccessToResource('contrat', $this->contract->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }
        $this->contract->getLinesArray();
        $result = array();
        foreach ($this->contract->lines as $line) {
            array_push($result, $this->_cleanObjectDatas($line));
        }
        return $result;
    }

    /**
     * Add a line to given contract
     *
     * @param int   $id             Id of contrat to update
     * @param array $request_data   Contractline data
     *
     * @url	POST {id}/lines
     *
     * @return int|bool
     */
    public function postLine($id, $request_data = null)
    {
        if(! DolibarrApiAccess::$user->rights->contrat->creer) {
            throw new RestException(401);
        }

        $result = $this->contract->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'Contract not found');
        }

        if( ! DolibarrApi::_checkAccessToResource('contrat', $this->contract->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }
        $request_data = (object) $request_data;
        $updateRes = $this->contract->addline(
            $request_data->desc,
            $request_data->subprice,
            $request_data->qty,
            $request_data->tva_tx,
            $request_data->localtax1_tx,
            $request_data->localtax2_tx,
            $request_data->fk_product,
            $request_data->remise_percent,
            $request_data->date_start,  // date_start = date planned start, date ouverture = date_start_real
            $request_data->date_end,    // date_end = date planned end, date_cloture = date_end_real
            $request_data->HT,
            $request_data->subprice_excl_tax,
            $request_data->info_bits,
            $request_data->fk_fournprice,
            $request_data->pa_ht,
            $request_data->array_options,
            $request_data->fk_unit,
            $request_data->rang
        );

        if ($updateRes > 0) {
            return $updateRes;
        }
        return false;
    }

    /**
     * Update a line to given contract
     *
     * @param int   $id             Id of contrat to update
     * @param int   $lineid         Id of line to update
     * @param array $request_data   Contractline data
     *
     * @url	PUT {id}/lines/{lineid}
     *
     * @return array|bool
     */
    public function putLine($id, $lineid, $request_data = null)
    {
        if(! DolibarrApiAccess::$user->rights->contrat->creer) {
            throw new RestException(401);
        }

        $result = $this->contract->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'Contrat not found');
        }

        if( ! DolibarrApi::_checkAccessToResource('contrat', $this->contract->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $request_data = (object) $request_data;

        $updateRes = $this->contract->updateline(
            $lineid,
            $request_data->desc,
            $request_data->subprice,
            $request_data->qty,
            $request_data->remise_percent,
            $request_data->date_ouveture_prevue,
            $request_data->date_fin_validite,
            $request_data->tva_tx,
            $request_data->localtax1_tx,
            $request_data->localtax2_tx,
            $request_data->date_ouverture,
            $request_data->date_cloture,
            'HT',
            $request_data->info_bits,
            $request_data->fk_fourn_price,
            $request_data->pa_ht,
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
     * Activate a service line of a given contract
     *
     * @param int   	$id             Id of contract to activate
     * @param int   	$lineid         Id of line to activate
     * @param string  	$datestart		{@from body}  Date start        {@type timestamp}
     * @param string    $dateend		{@from body}  Date end          {@type timestamp}
     * @param string    $comment  		{@from body}  Comment
     *
     * @url	PUT {id}/lines/{lineid}/activate
     *
     * @return array|bool
     */
    public function activateLine($id, $lineid, $datestart, $dateend = null, $comment = null)
    {
        if(! DolibarrApiAccess::$user->rights->contrat->creer) {
            throw new RestException(401);
        }

        $result = $this->contract->fetch($id);
        if (! $result) {
            throw new RestException(404, 'Contrat not found');
        }

        if( ! DolibarrApi::_checkAccessToResource('contrat', $this->contract->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $updateRes = $this->contract->active_line(DolibarrApiAccess::$user, $lineid, $datestart, $dateend, $comment);

        if ($updateRes > 0) {
            $result = $this->get($id);
            unset($result->line);
            return $this->_cleanObjectDatas($result);
        }

        return false;
    }

    /**
     * Unactivate a service line of a given contract
     *
     * @param int   	$id             Id of contract to activate
     * @param int   	$lineid         Id of line to activate
     * @param string  	$datestart		{@from body}  Date start        {@type timestamp}
     * @param string    $comment  		{@from body}  Comment
     *
     * @url	PUT {id}/lines/{lineid}/unactivate
     *
     * @return array|bool
     */
    public function unactivateLine($id, $lineid, $datestart, $comment = null)
    {
        if (! DolibarrApiAccess::$user->rights->contrat->creer) {
            throw new RestException(401);
        }

        $result = $this->contract->fetch($id);
        if (! $result) {
            throw new RestException(404, 'Contrat not found');
        }

        if( ! DolibarrApi::_checkAccessToResource('contrat', $this->contract->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $request_data = (object) $request_data;

        $updateRes = $this->contract->close_line(DolibarrApiAccess::$user, $lineid, $datestart, $comment);

        if ($updateRes > 0) {
            $result = $this->get($id);
            unset($result->line);
            return $this->_cleanObjectDatas($result);
        }

        return false;
    }

    /**
     * Delete a line to given contract
     *
     *
     * @param int   $id             Id of contract to update
     * @param int   $lineid         Id of line to delete
     *
     * @url	DELETE {id}/lines/{lineid}
     *
     * @return int
     * @throws 401
     * @throws 404
     */
    public function deleteLine($id, $lineid)
    {
        if (! DolibarrApiAccess::$user->rights->contrat->creer) {
            throw new RestException(401);
        }

        $result = $this->contract->fetch($id);
        if (! $result) {
            throw new RestException(404, 'Contrat not found');
        }

        if (! DolibarrApi::_checkAccessToResource('contrat', $this->contract->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        // TODO Check the lineid $lineid is a line of object

        $updateRes = $this->contract->deleteline($lineid, DolibarrApiAccess::$user);
        if ($updateRes > 0) {
            return $this->get($id);
        }
        else
        {
              throw new RestException(405, $this->contract->error);
        }
    }

    /**
     * Update contract general fields (won't touch lines of contract)
     *
     * @param int   $id             Id of contract to update
     * @param array $request_data   Datas
     *
     * @return int
     */
    public function put($id, $request_data = null)
    {
        if (! DolibarrApiAccess::$user->rights->contrat->creer) {
            throw new RestException(401);
        }

        $result = $this->contract->fetch($id);
        if (! $result) {
            throw new RestException(404, 'Contrat not found');
        }

        if (! DolibarrApi::_checkAccessToResource('contrat', $this->contract->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }
        foreach($request_data as $field => $value) {
            if ($field == 'id') continue;
            $this->contract->$field = $value;
        }

        if ($this->contract->update(DolibarrApiAccess::$user) > 0)
        {
            return $this->get($id);
        }
        else
        {
            throw new RestException(500, $this->contract->error);
        }
    }

    /**
     * Delete contract
     *
     * @param   int     $id         Contract ID
     *
     * @return  array
     */
    public function delete($id)
    {
        if (! DolibarrApiAccess::$user->rights->contrat->supprimer) {
            throw new RestException(401);
        }
        $result = $this->contract->fetch($id);
        if (! $result) {
            throw new RestException(404, 'Contract not found');
        }

        if (! DolibarrApi::_checkAccessToResource('contrat', $this->contract->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        if (! $this->contract->delete(DolibarrApiAccess::$user)) {
            throw new RestException(500, 'Error when delete contract : '.$this->contract->error);
        }

        return array(
            'success' => array(
                'code' => 200,
                'message' => 'Contract deleted'
            )
        );
    }

    /**
     * Validate a contract
     *
     * @param   int $id             Contract ID
     * @param   int $notrigger      1=Does not execute triggers, 0= execute triggers
     *
     * @url POST    {id}/validate
     *
     * @return  array
     * FIXME An error 403 is returned if the request has an empty body.
     * Error message: "Forbidden: Content type `text/plain` is not supported."
     * Workaround: send this in the body
     * {
     *   "notrigger": 0
     * }
     */
    public function validate($id, $notrigger = 0)
    {
        if (! DolibarrApiAccess::$user->rights->contrat->creer) {
            throw new RestException(401);
        }
        $result = $this->contract->fetch($id);
        if (! $result) {
            throw new RestException(404, 'Contract not found');
        }

        if (! DolibarrApi::_checkAccessToResource('contrat', $this->contract->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $result = $this->contract->validate(DolibarrApiAccess::$user, '', $notrigger);
        if ($result == 0) {
            throw new RestException(304, 'Error nothing done. May be object is already validated');
        }
        if ($result < 0) {
            throw new RestException(500, 'Error when validating Contract: '.$this->contract->error);
        }

        return array(
            'success' => array(
                'code' => 200,
                'message' => 'Contract validated (Ref='.$this->contract->ref.')'
            )
        );
    }

    /**
     * Close all services of a contract
     *
     * @param   int $id             Contract ID
     * @param   int $notrigger      1=Does not execute triggers, 0= execute triggers
     *
     * @url POST    {id}/close
     *
     * @return  array
     * FIXME An error 403 is returned if the request has an empty body.
     * Error message: "Forbidden: Content type `text/plain` is not supported."
     * Workaround: send this in the body
     * {
     *   "notrigger": 0
     * }
     */
    public function close($id, $notrigger = 0)
    {
        if (! DolibarrApiAccess::$user->rights->contrat->creer) {
            throw new RestException(401);
        }
        $result = $this->contract->fetch($id);
        if (! $result) {
            throw new RestException(404, 'Contract not found');
        }

        if (! DolibarrApi::_checkAccessToResource('contrat', $this->contract->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $result = $this->contract->closeAll(DolibarrApiAccess::$user, $notrigger);
        if ($result == 0) {
            throw new RestException(304, 'Error nothing done. May be object is already close');
        }
        if ($result < 0) {
            throw new RestException(500, 'Error when closing Contract: '.$this->contract->error);
        }

        return array(
            'success' => array(
                'code' => 200,
                'message' => 'Contract closed (Ref='.$this->contract->ref.'). All services were closed.'
            )
        );
    }



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

        unset($object->address);

        unset($object->date_ouverture_prevue);
        unset($object->date_ouverture);
        unset($object->date_fin_validite);
        unset($object->date_cloture);
        unset($object->date_debut_prevue);
        unset($object->date_debut_reel);
        unset($object->date_fin_prevue);
        unset($object->date_fin_reel);
        unset($object->civility_id);

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
        $contrat = array();
        foreach (Contracts::$FIELDS as $field) {
            if (!isset($data[$field]))
                throw new RestException(400, "$field field missing");
            $contrat[$field] = $data[$field];
        }
        return $contrat;
    }
}
