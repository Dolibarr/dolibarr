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

require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';


/**
 * API class for orders
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class Proposals extends DolibarrApi
{

	/**
	 * @var array   $FIELDS     Mandatory fields, checked when create and update object
	 */
	static $FIELDS = array(
		'socid'
	);

	/**
	 * @var Propal $propal {@type Propal}
	 */
	public $propal;

	/**
	 * Constructor
	 */
	function __construct()
	{
		global $db, $conf;
		$this->db = $db;
		$this->propal = new Propal($this->db);
	}

	/**
	 * Get properties of a commercial proposal object
	 *
	 * Return an array with commercial proposal informations
	 *
	 * @param       int         $id         ID of commercial proposal
	 * @return 	array|mixed data without useless information
	 *
	 * @throws 	RestException
	 */
	function get($id)
	{
		if(! DolibarrApiAccess::$user->rights->propal->lire) {
			throw new RestException(401);
		}

		$result = $this->propal->fetch($id);
		if( ! $result ) {
			throw new RestException(404, 'Commercial Proposal not found');
		}

		if( ! DolibarrApi::_checkAccessToResource('propal',$this->propal->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$this->propal->fetchObjectLinked();
		return $this->_cleanObjectDatas($this->propal);
	}

	/**
	 * List commercial proposals
	 *
	 * Get a list of commercial proposals
	 *
	 * @param string	$sortfield	        Sort field
	 * @param string	$sortorder	        Sort order
	 * @param int		$limit		        Limit for list
	 * @param int		$page		        Page number
	 * @param string   	$thirdparty_ids	    Thirdparty ids to filter commercial proposals. {@example '1' or '1,2,3'} {@pattern /^[0-9,]*$/i}
	 * @param string    $sqlfilters         Other criteria to filter answers separated by a comma. Syntax example "(t.ref:like:'SO-%') and (t.datec:<:'20160101')"
	 * @return  array                       Array of order objects
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
		$sql.= " FROM ".MAIN_DB_PREFIX."propal as t";

		if ((!DolibarrApiAccess::$user->rights->societe->client->voir && !$socids) || $search_sale > 0) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc"; // We need this table joined to the select in order to filter by sale

		$sql.= ' WHERE t.entity IN ('.getEntity('propal').')';
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
			$i = 0;
			while ($i < $min)
			{
				$obj = $db->fetch_object($result);
				$proposal_static = new Propal($db);
				if($proposal_static->fetch($obj->rowid)) {
					$obj_ret[] = $this->_cleanObjectDatas($proposal_static);
				}
				$i++;
			}
		}
		else {
			throw new RestException(503, 'Error when retrieve propal list : '.$db->lasterror());
		}
		if( ! count($obj_ret)) {
			throw new RestException(404, 'No proposal found');
		}
		return $obj_ret;
	}

	/**
	 * Create commercial proposal object
	 *
	 * @param   array   $request_data   Request data
	 * @return  int     ID of proposal
	 */
	function post($request_data = NULL)
	{
	  if(! DolibarrApiAccess::$user->rights->propal->creer) {
			  throw new RestException(401, "Insuffisant rights");
		  }
		// Check mandatory fields
		$result = $this->_validate($request_data);

		foreach($request_data as $field => $value) {
			$this->propal->$field = $value;
		}
		/*if (isset($request_data["lines"])) {
          $lines = array();
          foreach ($request_data["lines"] as $line) {
            array_push($lines, (object) $line);
          }
          $this->propal->lines = $lines;
        }*/
		if ($this->propal->create(DolibarrApiAccess::$user) < 0) {
			throw new RestException(500, "Error creating order", array_merge(array($this->propal->error), $this->propal->errors));
		}

		return $this->propal->id;
	}

	/**
	 * Get lines of a commercial proposal
	 *
	 * @param int   $id             Id of commercial proposal
	 *
	 * @url	GET {id}/lines
	 *
	 * @return int
	 */
	function getLines($id) {
	  if(! DolibarrApiAccess::$user->rights->propal->lire) {
		  	throw new RestException(401);
		  }

	  $result = $this->propal->fetch($id);
	  if( ! $result ) {
		 throw new RestException(404, 'Commercial Proposal not found');
	  }

		  if( ! DolibarrApi::_checkAccessToResource('propal',$this->propal->id)) {
			  throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
	  }
	  $this->propal->getLinesArray();
	  $result = array();
	  foreach ($this->propal->lines as $line) {
		array_push($result,$this->_cleanObjectDatas($line));
	  }
	  return $result;
	}

	/**
	 * Add a line to given commercial proposal
	 *
	 * @param int   $id             Id of commercial proposal to update
	 * @param array $request_data   Commercial proposal line data
	 *
	 * @url	POST {id}/lines
	 *
	 * @return int
	 */
	function postLine($id, $request_data = NULL)
	{
		if(! DolibarrApiAccess::$user->rights->propal->creer) {
		  	throw new RestException(401);
		}

		$result = $this->propal->fetch($id);
		if (! $result) {
		   throw new RestException(404, 'Commercial Proposal not found');
		}

		if (! DolibarrApi::_checkAccessToResource('propal',$this->propal->id))
		{
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$request_data = (object) $request_data;

      	$updateRes = $this->propal->addline(
                        $request_data->desc,
                        $request_data->subprice,
                        $request_data->qty,
                        $request_data->tva_tx,
                        $request_data->localtax1_tx,
                        $request_data->localtax2_tx,
                        $request_data->fk_product,
                        $request_data->remise_percent,
                        'HT',
                        0,
                        $request_data->info_bits,
                        $request_data->product_type,
                        $request_data->rang,
                        $request_data->special_code,
      					$request_data->fk_parent_line,
                        $request_data->fk_fournprice,
                        $request_data->pa_ht,
                        $request_data->label,
                        $request_data->date_start,
                        $request_data->date_end,
                        $request_data->array_options,
                        $request_data->fk_unit,
                        $request_data->origin,
                        $request_data->origin_id,
                        $request_data->multicurrency_subprice,
                        $request_data->fk_remise_except
      );

      if ($updateRes > 0) {
        return $updateRes;
	  }
		else {
			throw new RestException(400, $this->propal->error);
		}
	}

	/**
	 * Update a line of given commercial proposal
	 *
	 * @param int   $id             Id of commercial proposal to update
	 * @param int   $lineid         Id of line to update
	 * @param array $request_data   Commercial proposal line data
	 *
	 * @url	PUT {id}/lines/{lineid}
	 *
	 * @return object
	 */
	function putLine($id, $lineid, $request_data = NULL)
	{
		if(! DolibarrApiAccess::$user->rights->propal->creer) {
			throw new RestException(401);
		}

		$result = $this->propal->fetch($id);
		if($result <= 0) {
			throw new RestException(404, 'Proposal not found');
		}

		if( ! DolibarrApi::_checkAccessToResource('propal',$this->propal->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
	  	}

	  	$request_data = (object) $request_data;

	  	$propalline = new PropaleLigne($this->db);
	  	$result = $propalline->fetch($lineid);
	  	if ($result <= 0) {
	  		throw new RestException(404, 'Proposal line not found');
	  	}

	  	$updateRes = $this->propal->updateline(
						$lineid,
						isset($request_data->subprice)?$request_data->subprice:$propalline->subprice,
						isset($request_data->qty)?$request_data->qty:$propalline->qty,
						isset($request_data->remise_percent)?$request_data->remise_percent:$propalline->remise_percent,
						isset($request_data->tva_tx)?$request_data->tva_tx:$propalline->tva_tx,
						isset($request_data->localtax1_tx)?$request_data->localtax1_tx:$propalline->localtax1_tx,
						isset($request_data->localtax2_tx)?$request_data->localtax2_tx:$propalline->localtax2_tx,
						isset($request_data->desc)?$request_data->desc:$propalline->desc,
	  					'HT',
						isset($request_data->info_bits)?$request_data->info_bits:$propalline->info_bits,
						isset($request_data->special_code)?$request_data->special_code:$propalline->special_code,
						isset($request_data->fk_parent_line)?$request_data->fk_parent_line:$propalline->fk_parent_line,
	  					0,
	  					isset($request_data->fk_fournprice)?$request_data->fk_fournprice:$propalline->fk_fournprice,
	  					isset($request_data->pa_ht)?$request_data->pa_ht:$propalline->pa_ht,
	  					isset($request_data->label)?$request_data->label:$propalline->label,
					  	isset($request_data->product_type)?$request_data->product_type:$propalline->product_type,
	  					isset($request_data->date_start)?$request_data->date_start:$propalline->date_start,
						isset($request_data->date_end)?$request_data->date_end:$propalline->date_end,
						isset($request_data->array_options)?$request_data->array_options:$propalline->array_options,
						isset($request_data->fk_unit)?$request_data->fk_unit:$propalline->fk_unit,
	  					isset($request_data->multicurrency_subprice)?$request_data->multicurrency_subprice:$propalline->subprice
	  );

	  if ($updateRes > 0) {
		$result = $this->get($id);
		unset($result->line);
		return $this->_cleanObjectDatas($result);
	  }
	  return false;
	}

	/**
	 * Delete a line of given commercial proposal
	 *
	 *
	 * @param int   $id             Id of commercial proposal to update
	 * @param int   $lineid         Id of line to delete
	 *
	 * @url	DELETE {id}/lines/{lineid}
	 *
	 * @return int
     * @throws 401
     * @throws 404
	 */
	function deleteLine($id, $lineid) {
		if(! DolibarrApiAccess::$user->rights->propal->creer) {
		  	throw new RestException(401);
		}

		$result = $this->propal->fetch($id);
		if( ! $result ) {
			throw new RestException(404, 'Proposal not found');
		}

		if( ! DolibarrApi::_checkAccessToResource('propal',$this->propal->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		// TODO Check the lineid $lineid is a line of ojbect

		$updateRes = $this->propal->deleteline($lineid);
		if ($updateRes > 0) {
			return $this->get($id);
		}
		else
		{
			throw new RestException(405, $this->propal->error);
		}
	}

	/**
	 * Update commercial proposal general fields (won't touch lines of commercial proposal)
	 *
	 * @param int   $id             Id of commercial proposal to update
	 * @param array $request_data   Datas
	 *
	 * @return int
	 */
	function put($id, $request_data = NULL) {
	  if(! DolibarrApiAccess::$user->rights->propal->creer) {
		  	throw new RestException(401);
		  }

		$result = $this->propal->fetch($id);
		if( ! $result ) {
			throw new RestException(404, 'Proposal not found');
		}

		if( ! DolibarrApi::_checkAccessToResource('propal',$this->propal->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}
		foreach($request_data as $field => $value) {
			if ($field == 'id') continue;
			$this->propal->$field = $value;
		}

		// update end of validity date
		if (empty($this->propal->fin_validite) && !empty($this->propal->duree_validite) && !empty($this->propal->date_creation))
		{
			$this->propal->fin_validite = $this->propal->date_creation + ($this->propal->duree_validite * 24 * 3600);
		}
		if (!empty($this->propal->fin_validite))
		{
			if($this->propal->set_echeance(DolibarrApiAccess::$user, $this->propal->fin_validite)<0)
			{
				throw new RestException(500, $this->propal->error);
			}
		}

		if ($this->propal->update(DolibarrApiAccess::$user) > 0)
		{
			return $this->get($id);
		}
		else
		{
			throw new RestException(500, $this->propal->error);
		}
	}

	/**
	 * Delete commercial proposal
	 *
	 * @param   int     $id         Commercial proposal ID
	 *
	 * @return  array
	 */
	function delete($id)
	{
		if(! DolibarrApiAccess::$user->rights->propal->supprimer) {
			throw new RestException(401);
		}
		$result = $this->propal->fetch($id);
		if( ! $result ) {
			throw new RestException(404, 'Commercial Proposal not found');
		}

		if( ! DolibarrApi::_checkAccessToResource('propal',$this->propal->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		if( ! $this->propal->delete(DolibarrApiAccess::$user)) {
			throw new RestException(500, 'Error when delete Commercial Proposal : '.$this->propal->error);
		}

		return array(
			'success' => array(
				'code' => 200,
				'message' => 'Commercial Proposal deleted'
			)
		);

	}

	/**
	* Set a proposal to draft
	*
	* @param   int     $id             Order ID
	*
	* @url POST    {id}/settodraft
	*
	* @return  array
	*/
	function settodraft($id)
	{
		if(! DolibarrApiAccess::$user->rights->propal->creer) {
			throw new RestException(401);
		}
		$result = $this->propal->fetch($id);
		if( ! $result ) {
			throw new RestException(404, 'Proposal not found');
		}

		if( ! DolibarrApi::_checkAccessToResource('propal',$this->propal->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$result = $this->propal->set_draft(DolibarrApiAccess::$user);
		if ($result == 0) {
			throw new RestException(304, 'Nothing done. May be object is already draft');
		}
		if ($result < 0) {
			throw new RestException(500, 'Error : '.$this->propal->error);
		}

		$result = $this->propal->fetch($id);
		if( ! $result ) {
			throw new RestException(404, 'Proposal not found');
		}

		if( ! DolibarrApi::_checkAccessToResource('propal',$this->propal->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$this->propal->fetchObjectLinked();

		return $this->_cleanObjectDatas($this->propal);
	}


	/**
	 * Validate a commercial proposal
	 *
	 * If you get a bad value for param notrigger check that ou provide this in body
	 * {
	 * "notrigger": 0
	 * }
	 *
	 * @param   int     $id             Commercial proposal ID
	 * @param   int     $notrigger      1=Does not execute triggers, 0= execute triggers
	 *
	 * @url POST    {id}/validate
	 *
	 * @throws 304
     * @throws 401
     * @throws 404
     * @throws 500
     *
     * @return array
	 */
	function validate($id, $notrigger=0)
	{
		if(! DolibarrApiAccess::$user->rights->propal->creer) {
			throw new RestException(401);
		}
		$result = $this->propal->fetch($id);
		if( ! $result ) {
			throw new RestException(404, 'Commercial Proposal not found');
		}

		if( ! DolibarrApi::_checkAccessToResource('propal',$this->propal->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$result = $this->propal->valid(DolibarrApiAccess::$user, $notrigger);
		if ($result == 0) {
			throw new RestException(304, 'Error nothing done. May be object is already validated');
		}
		if ($result < 0) {
			throw new RestException(500, 'Error when validating Commercial Proposal: '.$this->propal->error);
		}

        $result = $this->propal->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'Commercial Proposal not found');
        }

        if( ! DolibarrApi::_checkAccessToResource('propal',$this->propal->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->propal->fetchObjectLinked();

        return $this->_cleanObjectDatas($this->propal);
	}

	/**
	 * Close (Accept or refuse) a quote / commercial proposal
	 *
	 * @param   int     $id             Commercial proposal ID
	 * @param   int	    $status			Must be 2 (accepted) or 3 (refused)				{@min 2}{@max 3}
	 * @param   string  $note_private   Add this mention at end of private note
	 * @param   int     $notrigger      Disabled triggers
	 *
	 * @url POST    {id}/close
	 *
	 * @return  array
	 */
	function close($id, $status, $note_private='', $notrigger=0)
	{
		if(! DolibarrApiAccess::$user->rights->propal->creer) {
			throw new RestException(401);
		}
		$result = $this->propal->fetch($id);
		if( ! $result ) {
			throw new RestException(404, 'Commercial Proposal not found');
		}

		if( ! DolibarrApi::_checkAccessToResource('propal',$this->propal->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$result = $this->propal->cloture(DolibarrApiAccess::$user, $status, $note_private, $notrigger);
		if ($result == 0) {
			throw new RestException(304, 'Error nothing done. May be object is already closed');
		}
		if ($result < 0) {
			throw new RestException(500, 'Error when closing Commercial Proposal: '.$this->propal->error);
		}

		$result = $this->propal->fetch($id);
		if( ! $result ) {
			throw new RestException(404, 'Proposal not found');
		}

		if( ! DolibarrApi::_checkAccessToResource('propal',$this->propal->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$this->propal->fetchObjectLinked();

		return $this->_cleanObjectDatas($this->propal);
	}

    /**
     * Set a commercial proposal billed. Could be also called setbilled
     *
     * @param   int     $id             Commercial proposal ID
     *
     * @url POST    {id}/setinvoiced
     *
     * @return  array
     */
    function setinvoiced($id)
    {
            if(! DolibarrApiAccess::$user->rights->propal->creer) {
                    throw new RestException(401);
            }
            $result = $this->propal->fetch($id);
            if( ! $result ) {
                    throw new RestException(404, 'Commercial Proposal not found');
            }

            if( ! DolibarrApi::_checkAccessToResource('propal',$this->propal->id)) {
                    throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
            }

            $result = $this->propal->classifyBilled(DolibarrApiAccess::$user );
            if ($result < 0) {
                    throw new RestException(500, 'Error : '.$this->propal->error);
            }

            $result = $this->propal->fetch($id);
            if( ! $result ) {
            	throw new RestException(404, 'Proposal not found');
            }

            if( ! DolibarrApi::_checkAccessToResource('propal',$this->propal->id)) {
            	throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
            }

            $this->propal->fetchObjectLinked();

            return $this->_cleanObjectDatas($this->propal);
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
		$propal = array();
		foreach (Proposals::$FIELDS as $field) {
			if (!isset($data[$field]))
				throw new RestException(400, "$field field missing");
			$propal[$field] = $data[$field];

		}
		return $propal;
	}


	/**
	 * Clean sensible object datas
	 *
	 * @param   object  $object    Object to clean
	 * @return    array    Array of cleaned object properties
	 */
	function _cleanObjectDatas($object) {

		$object = parent::_cleanObjectDatas($object);

        unset($object->note);
		unset($object->name);
		unset($object->lastname);
		unset($object->firstname);
		unset($object->civility_id);
		unset($object->address);

		return $object;
	}
}
