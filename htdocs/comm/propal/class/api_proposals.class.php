<?php
/* Copyright (C) 2015       Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2016       Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2020       Thibault FOUCART        <support@ptibogxiv.net>
 * Copyright (C) 2022       ATM Consulting          <contact@atm-consulting.fr>
 * Copyright (C) 2022       OpenDSI                 <support@open-dsi.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
	public static $FIELDS = array(
		'socid'
	);

	/**
	 * @var Propal $propal {@type Propal}
	 */
	public $propal;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		global $db;
		$this->db = $db;
		$this->propal = new Propal($this->db);
	}

	/**
	 * Get properties of a commercial proposal object
	 *
	 * Return an array with commercial proposal information
	 *
	 * @param   int         $id				ID of commercial proposal
	 * @param   int         $contact_list	0: Returned array of contacts/addresses contains all properties, 1: Return array contains just id
	 * @return  Object						Object with cleaned properties
	 *
	 * @throws	RestException
	 */
	public function get($id, $contact_list = 1)
	{
		return $this->_fetch($id, '', '', $contact_list);
	}

	/**
	 * Get properties of an proposal object by ref
	 *
	 * Return an array with proposal information
	 *
	 * @param       string		$ref			Ref of object
	 * @param       int         $contact_list	0: Returned array of contacts/addresses contains all properties, 1: Return array contains just id
	 * @return		Object						Object with cleaned properties
	 *
	 * @url GET    ref/{ref}
	 *
	 * @throws	RestException
	 */
	public function getByRef($ref, $contact_list = 1)
	{
		return $this->_fetch('', $ref, '', $contact_list);
	}

	/**
	 * Get properties of an proposal object by ref_ext
	 *
	 * Return an array with proposal information
	 *
	 * @param       string		$ref_ext		External reference of object
	 * @param       int         $contact_list	0: Returned array of contacts/addresses contains all properties, 1: Return array contains just id
	 * @return		Object						Object with cleaned properties
	 *
	 * @url GET    ref_ext/{ref_ext}
	 *
	 * @throws	RestException
	 */
	public function getByRefExt($ref_ext, $contact_list = 1)
	{
		return $this->_fetch('', '', $ref_ext, $contact_list);
	}

	/**
	 * Get properties of an proposal object
	 *
	 * Return an array with proposal information
	 *
	 * @param   int         $id             ID of order
	 * @param	string		$ref			Ref of object
	 * @param	string		$ref_ext		External reference of object
	 * @param   int         $contact_list	0: Returned array of contacts/addresses contains all properties, 1: Return array contains just id
	 * @return  Object						Object with cleaned properties
	 *
	 * @throws	RestException
	 */
	private function _fetch($id, $ref = '', $ref_ext = '', $contact_list = 1)
	{
		if (!DolibarrApiAccess::$user->hasRight('propal', 'lire')) {
			throw new RestException(403);
		}

		$result = $this->propal->fetch($id, $ref, $ref_ext);
		if (!$result) {
			throw new RestException(404, 'Commercial Proposal not found');
		}

		if (!DolibarrApi::_checkAccessToResource('propal', $this->propal->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		// Add external contacts ids.
		$tmparray = $this->propal->liste_contact(-1, 'external', $contact_list);
		if (is_array($tmparray)) {
			$this->propal->contacts_ids = $tmparray;
		}

		$this->propal->fetchObjectLinked();

		return $this->_cleanObjectDatas($this->propal);
	}

	/**
	 * List commercial proposals
	 *
	 * Get a list of commercial proposals
	 *
	 * @param string	$sortfield			Sort field
	 * @param string	$sortorder			Sort order
	 * @param int		$limit				Limit for list
	 * @param int		$page				Page number
	 * @param string	$thirdparty_ids		Thirdparty ids to filter commercial proposals (example '1' or '1,2,3') {@pattern /^[0-9,]*$/i}
	 * @param string    $sqlfilters         Other criteria to filter answers separated by a comma. Syntax example "(t.ref:like:'SO-%') and (t.datec:<:'2016-01-01')"
	 * @param string    $properties	        Restrict the data returned to these properties. Ignored if empty. Comma separated list of properties names
	 * @param bool      $pagination_data    If this parameter is set to true the response will include pagination data. Default value is false. Page starts from 0*
	 * @return  array                       Array of order objects
	 */
	public function index($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $thirdparty_ids = '', $sqlfilters = '', $properties = '', $pagination_data = false)
	{
		if (!DolibarrApiAccess::$user->hasRight('propal', 'lire')) {
			throw new RestException(403);
		}

		$obj_ret = array();

		// case of external user, $thirdparty_ids param is ignored and replaced by user's socid
		$socids = DolibarrApiAccess::$user->socid ? DolibarrApiAccess::$user->socid : $thirdparty_ids;

		// If the internal user must only see his customers, force searching by him
		$search_sale = 0;
		if (!DolibarrApiAccess::$user->hasRight('societe', 'client', 'voir') && !$socids) {
			$search_sale = DolibarrApiAccess::$user->id;
		}

		$sql = "SELECT t.rowid";
		$sql .= " FROM ".MAIN_DB_PREFIX."propal AS t";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."propal_extrafields AS ef ON (ef.fk_object = t.rowid)"; // Modification VMR Global Solutions to include extrafields as search parameters in the API GET call, so we will be able to filter on extrafields
		$sql .= ' WHERE t.entity IN ('.getEntity('propal').')';
		if ($socids) {
			$sql .= " AND t.fk_soc IN (".$this->db->sanitize($socids).")";
		}
		// Search on sale representative
		if ($search_sale && $search_sale != '-1') {
			if ($search_sale == -2) {
				$sql .= " AND NOT EXISTS (SELECT sc.fk_soc FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc WHERE sc.fk_soc = t.fk_soc)";
			} elseif ($search_sale > 0) {
				$sql .= " AND EXISTS (SELECT sc.fk_soc FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc WHERE sc.fk_soc = t.fk_soc AND sc.fk_user = ".((int) $search_sale).")";
			}
		}
		// Add sql filters
		if ($sqlfilters) {
			$errormessage = '';
			$sql .= forgeSQLFromUniversalSearchCriteria($sqlfilters, $errormessage);
			if ($errormessage) {
				throw new RestException(400, 'Error when validating parameter sqlfilters -> '.$errormessage);
			}
		}

		//this query will return total proposals with the filters given
		$sqlTotals = str_replace('SELECT t.rowid', 'SELECT count(t.rowid) as total', $sql);

		$sql .= $this->db->order($sortfield, $sortorder);
		if ($limit) {
			if ($page < 0) {
				$page = 0;
			}
			$offset = $limit * $page;

			$sql .= $this->db->plimit($limit + 1, $offset);
		}

		dol_syslog("API Rest request");
		$result = $this->db->query($sql);

		if ($result) {
			$num = $this->db->num_rows($result);
			$min = min($num, ($limit <= 0 ? $num : $limit));
			$i = 0;
			while ($i < $min) {
				$obj = $this->db->fetch_object($result);
				$proposal_static = new Propal($this->db);
				if ($proposal_static->fetch($obj->rowid)) {
					// Add external contacts ids
					$tmparray = $proposal_static->liste_contact(-1, 'external', 1);
					if (is_array($tmparray)) {
						$proposal_static->contacts_ids = $tmparray;
					}
					$obj_ret[] = $this->_filterObjectProperties($this->_cleanObjectDatas($proposal_static), $properties);
				}
				$i++;
			}
		} else {
			throw new RestException(503, 'Error when retrieve propal list : '.$this->db->lasterror());
		}

		//if $pagination_data is true the response will contain element data with all values and element pagination with pagination data(total,page,limit)
		if ($pagination_data) {
			$totalsResult = $this->db->query($sqlTotals);
			$total = $this->db->fetch_object($totalsResult)->total;

			$tmp = $obj_ret;
			$obj_ret = [];

			$obj_ret['data'] = $tmp;
			$obj_ret['pagination'] = [
				'total' => (int) $total,
				'page' => $page, //count starts from 0
				'page_count' => ceil((int) $total / $limit),
				'limit' => $limit
			];
		}

		return $obj_ret;
	}

	/**
	 * Create commercial proposal object
	 *
	 * @param   array   $request_data   Request data
	 * @return  int     ID of proposal
	 */
	public function post($request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('propal', 'creer')) {
			throw new RestException(403, "Insuffisant rights");
		}
		// Check mandatory fields
		$result = $this->_validate($request_data);

		foreach ($request_data as $field => $value) {
			if ($field === 'caller') {
				// Add a mention of caller so on trigger called after action, we can filter to avoid a loop if we try to sync back again with the caller
				$this->propal->context['caller'] = sanitizeVal($request_data['caller'], 'aZ09');
				continue;
			}

			$this->propal->$field = $this->_checkValForAPI($field, $value, $this->propal);
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

		return ((int) $this->propal->id);
	}

	/**
	 * Get lines of a commercial proposal
	 *
	 * @param int		$id				Id of commercial proposal
	 * @param string    $sqlfilters		Other criteria to filter answers separated by a comma. d is the alias for proposal lines table, p is the alias for product table. "Syntax example "(p.ref:like:'SO-%') AND (d.date_start:<:'20220101')"
	 *
	 * @url	GET {id}/lines
	 *
	 * @return array
	 */
	public function getLines($id, $sqlfilters = '')
	{
		if (!DolibarrApiAccess::$user->hasRight('propal', 'lire')) {
			throw new RestException(403);
		}

		$result = $this->propal->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Commercial Proposal not found');
		}

		if (!DolibarrApi::_checkAccessToResource('propal', $this->propal->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$sql = '';
		if (!empty($sqlfilters)) {
			$errormessage = '';
			$sql = forgeSQLFromUniversalSearchCriteria($sqlfilters, $errormessage);
			if ($errormessage) {
				throw new RestException(400, 'Error when validating parameter sqlfilters -> '.$errormessage);
			}
		}

		$this->propal->getLinesArray($sql);
		$result = array();
		foreach ($this->propal->lines as $line) {
			array_push($result, $this->_cleanObjectDatas($line));
		}
		return $result;
	}

	/**
	 * Add a line to given commercial proposal
	 *
	 * @param int   $id             Id of commercial proposal to update
	 * @param array $request_data   Commercial proposal line data
	 *
	 * @url	POST {id}/line
	 *
	 * @return int
	 */
	public function postLine($id, $request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('propal', 'creer')) {
			throw new RestException(403);
		}

		$result = $this->propal->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Commercial Proposal not found');
		}

		if (!DolibarrApi::_checkAccessToResource('propal', $this->propal->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$request_data = (object) $request_data;

		$request_data->desc = sanitizeVal($request_data->desc, 'restricthtml');
		$request_data->label = sanitizeVal($request_data->label);

		$updateRes = $this->propal->addline(
			$request_data->desc,
			$request_data->subprice,
			$request_data->qty,
			$request_data->tva_tx,
			$request_data->localtax1_tx,
			$request_data->localtax2_tx,
			$request_data->fk_product,
			$request_data->remise_percent,
			$request_data->price_base_type ? $request_data->price_base_type : 'HT',
			$request_data->subprice,
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
		} else {
			throw new RestException(400, $this->propal->error);
		}
	}

	/**
	 * Add lines to given commercial proposal
	 *
	 * @param int   $id             Id of commercial proposal to update
	 * @param array $request_data   Commercial proposal line data
	 *
	 * @url	POST {id}/lines
	 *
	 * @return int
	 */
	public function postLines($id, $request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('propal', 'creer')) {
			throw new RestException(403);
		}

		$result = $this->propal->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Commercial Proposal not found');
		}

		if (!DolibarrApi::_checkAccessToResource('propal', $this->propal->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$errors = [];
		$updateRes = 0;
		$this->db->begin();

		foreach ($request_data as $TData) {
			if (empty($TData[0])) {
				$TData = array($TData);
			}

			foreach ($TData as $lineData) {
				$line = (object) $lineData;

				$updateRes = $this->propal->addline(
					$line->desc,
					$line->subprice,
					$line->qty,
					$line->tva_tx,
					$line->localtax1_tx,
					$line->localtax2_tx,
					$line->fk_product,
					$line->remise_percent,
					'HT',
					0,
					$line->info_bits,
					$line->product_type,
					$line->rang,
					$line->special_code,
					$line->fk_parent_line,
					$line->fk_fournprice,
					$line->pa_ht,
					$line->label,
					$line->date_start,
					$line->date_end,
					$line->array_options,
					$line->fk_unit,
					$line->origin,
					$line->origin_id,
					$line->multicurrency_subprice,
					$line->fk_remise_except
				);

				if ($updateRes < 0) {
					$errors['lineLabel'] = $line->label;
					$errors['msg'] = $this->propal->errors;
				}
			}
		}
		if (empty($errors)) {
			$this->db->commit();
			return $updateRes;
		} else {
			$this->db->rollback();
			throw new RestException(400, implode(", ", $errors));
		}
	}

	/**
	 * Update a line of given commercial proposal
	 *
	 * @param	int				$id             Id of commercial proposal to update
	 * @param	int				$lineid         Id of line to update
	 * @param	array			$request_data   Commercial proposal line data
	 * @return  Object|false					Object with cleaned properties
	 *
	 * @url	PUT {id}/lines/{lineid}
	 */
	public function putLine($id, $lineid, $request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('propal', 'creer')) {
			throw new RestException(403);
		}

		$result = $this->propal->fetch($id);
		if ($result <= 0) {
			throw new RestException(404, 'Proposal not found');
		}

		if (!DolibarrApi::_checkAccessToResource('propal', $this->propal->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$request_data = (object) $request_data;

		if (isset($request_data->desc)) {
			$request_data->desc = sanitizeVal($request_data->desc, 'restricthtml');
		}
		if (isset($request_data->label)) {
			$request_data->label = sanitizeVal($request_data->label);
		}

		$propalline = new PropaleLigne($this->db);
		$result = $propalline->fetch($lineid);
		if ($result <= 0) {
			throw new RestException(404, 'Proposal line not found');
		}

		$updateRes = $this->propal->updateline(
			$lineid,
			isset($request_data->subprice) ? $request_data->subprice : $propalline->subprice,
			isset($request_data->qty) ? $request_data->qty : $propalline->qty,
			isset($request_data->remise_percent) ? $request_data->remise_percent : $propalline->remise_percent,
			isset($request_data->tva_tx) ? $request_data->tva_tx : $propalline->tva_tx,
			isset($request_data->localtax1_tx) ? $request_data->localtax1_tx : $propalline->localtax1_tx,
			isset($request_data->localtax2_tx) ? $request_data->localtax2_tx : $propalline->localtax2_tx,
			isset($request_data->desc) ? $request_data->desc : $propalline->desc,
			isset($request_data->price_base_type) ? $request_data->price_base_type : 'HT',
			isset($request_data->info_bits) ? $request_data->info_bits : $propalline->info_bits,
			isset($request_data->special_code) ? $request_data->special_code : $propalline->special_code,
			isset($request_data->fk_parent_line) ? $request_data->fk_parent_line : $propalline->fk_parent_line,
			0,
			isset($request_data->fk_fournprice) ? $request_data->fk_fournprice : $propalline->fk_fournprice,
			isset($request_data->pa_ht) ? $request_data->pa_ht : $propalline->pa_ht,
			isset($request_data->label) ? $request_data->label : $propalline->label,
			isset($request_data->product_type) ? $request_data->product_type : $propalline->product_type,
			isset($request_data->date_start) ? $request_data->date_start : $propalline->date_start,
			isset($request_data->date_end) ? $request_data->date_end : $propalline->date_end,
			isset($request_data->array_options) ? $request_data->array_options : $propalline->array_options,
			isset($request_data->fk_unit) ? $request_data->fk_unit : $propalline->fk_unit,
			isset($request_data->multicurrency_subprice) ? $request_data->multicurrency_subprice : $propalline->subprice,
			0,
			isset($request_data->rang) ? $request_data->rang : $propalline->rang
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
	 * @param	int				$id             Id of commercial proposal to update
	 * @param	int				$lineid         Id of line to delete
	 * @return  Object|false					Object with cleaned properties
	 *
	 * @url	DELETE {id}/lines/{lineid}
	 *
	 * @throws RestException 401
	 * @throws RestException 404
	 */
	public function deleteLine($id, $lineid)
	{
		if (!DolibarrApiAccess::$user->hasRight('propal', 'creer')) {
			throw new RestException(403);
		}

		$result = $this->propal->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Proposal not found');
		}

		if (!DolibarrApi::_checkAccessToResource('propal', $this->propal->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$updateRes = $this->propal->deleteLine($lineid, $id);
		if ($updateRes > 0) {
			return $this->get($id);
		} else {
			throw new RestException(405, $this->propal->error);
		}
	}

	/**
	 * Add a contact type of given commercial proposal
	 *
	 * @param int    $id             Id of commercial proposal to update
	 * @param int    $contactid      Id of external or internal contact to add
	 * @param string $type           Type of the external contact (BILLING, SHIPPING, CUSTOMER), internal contact (SALESREPFOLL)
	 * @param string $source         Source of the contact (internal, external)
	 * @return array
	 *
	 * @url	POST {id}/contact/{contactid}/{type}/{source}
	 *
	 * @throws RestException 401
	 * @throws RestException 404
	 */
	public function postContact($id, $contactid, $type, $source = 'external')
	{
		if (!DolibarrApiAccess::$user->hasRight('propal', 'creer')) {
			throw new RestException(403);
		}

		$result = $this->propal->fetch($id);

		if (!$result) {
			throw new RestException(404, 'Proposal not found');
		}

		if (!in_array($source, array('internal', 'external'), true)) {
			throw new RestException(500, 'Availables sources: internal OR external');
		}

		if ($source == 'external' && !in_array($type, array('BILLING', 'SHIPPING', 'CUSTOMER'), true)) {
			throw new RestException(500, 'Availables external types: BILLING, SHIPPING OR CUSTOMER');
		}

		if ($source == 'internal' && !in_array($type, array('SALESREPFOLL'), true)) {
			throw new RestException(500, 'Availables internal types: SALESREPFOLL');
		}

		if (!DolibarrApi::_checkAccessToResource('propal', $this->propal->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$result = $this->propal->add_contact($contactid, $type, $source);

		if (!$result) {
			throw new RestException(500, 'Error when added the contact');
		}

		return array(
			'success' => array(
				'code' => 200,
				'message' => 'Contact linked to the proposal'
			)
		);
	}

	/**
	 * Delete a contact type of given commercial proposal
	 *
	 * @param	int    $id				Id of commercial proposal to update
	 * @param	int    $contactid		Row key of the contact in the array contact_ids.
	 * @param	string $type			Type of the contact (BILLING, SHIPPING, CUSTOMER).
	 * @return Object					Object with cleaned properties
	 *
	 * @url	DELETE {id}/contact/{contactid}/{type}
	 *
	 * @throws RestException 401
	 * @throws RestException 404
	 * @throws RestException 500 System error
	 */
	public function deleteContact($id, $contactid, $type)
	{
		if (!DolibarrApiAccess::$user->hasRight('propal', 'creer')) {
			throw new RestException(403);
		}

		$result = $this->propal->fetch($id);

		if (!$result) {
			throw new RestException(404, 'Proposal not found');
		}

		if (!DolibarrApi::_checkAccessToResource('propal', $this->propal->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$contacts = $this->propal->liste_contact();

		foreach ($contacts as $contact) {
			if ($contact['id'] == $contactid && $contact['code'] == $type) {
				$result = $this->propal->delete_contact($contact['rowid']);

				if (!$result) {
					throw new RestException(500, 'Error when deleted the contact');
				}
			}
		}

		return $this->_cleanObjectDatas($this->propal);
	}

	/**
	 * Update commercial proposal general fields (won't touch lines of commercial proposal)
	 *
	 * @param	int		$id             Id of commercial proposal to update
	 * @param	array	$request_data   Datas
	 * @return	Object					Object with cleaned properties
	 */
	public function put($id, $request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('propal', 'creer')) {
			throw new RestException(403);
		}

		$result = $this->propal->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Proposal not found');
		}

		if (!DolibarrApi::_checkAccessToResource('propal', $this->propal->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}
		foreach ($request_data as $field => $value) {
			if ($field == 'id') {
				continue;
			}
			if ($field === 'caller') {
				// Add a mention of caller so on trigger called after action, we can filter to avoid a loop if we try to sync back again with the caller
				$this->propal->context['caller'] = sanitizeVal($request_data['caller'], 'aZ09');
				continue;
			}
			if ($field == 'array_options' && is_array($value)) {
				foreach ($value as $index => $val) {
					$this->propal->array_options[$index] = $this->_checkValForAPI($field, $val, $this->propal);
				}
				continue;
			}

			$this->propal->$field = $this->_checkValForAPI($field, $value, $this->propal);
		}

		// update end of validity date
		if (empty($this->propal->fin_validite) && !empty($this->propal->duree_validite) && !empty($this->propal->date_creation)) {
			$this->propal->fin_validite = $this->propal->date_creation + ($this->propal->duree_validite * 24 * 3600);
		}
		if (!empty($this->propal->fin_validite)) {
			if ($this->propal->set_echeance(DolibarrApiAccess::$user, $this->propal->fin_validite) < 0) {
				throw new RestException(500, $this->propal->error);
			}
		}

		if ($this->propal->update(DolibarrApiAccess::$user) > 0) {
			return $this->get($id);
		} else {
			throw new RestException(500, $this->propal->error);
		}
	}

	/**
	 * Delete commercial proposal
	 *
	 * @param   int     $id         Commercial proposal ID
	 * @return  array
	 */
	public function delete($id)
	{
		if (!DolibarrApiAccess::$user->hasRight('propal', 'supprimer')) {
			throw new RestException(403);
		}
		$result = $this->propal->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Commercial Proposal not found');
		}

		if (!DolibarrApi::_checkAccessToResource('propal', $this->propal->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		if (!$this->propal->delete(DolibarrApiAccess::$user)) {
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
	 * @return	Object					Object with cleaned properties
	 *
	 * @url POST    {id}/settodraft
	 */
	public function settodraft($id)
	{
		if (!DolibarrApiAccess::$user->hasRight('propal', 'creer')) {
			throw new RestException(403);
		}
		$result = $this->propal->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Proposal not found');
		}

		if (!DolibarrApi::_checkAccessToResource('propal', $this->propal->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$result = $this->propal->setDraft(DolibarrApiAccess::$user);
		if ($result == 0) {
			throw new RestException(304, 'Nothing done. May be object is already draft');
		}
		if ($result < 0) {
			throw new RestException(500, 'Error : '.$this->propal->error);
		}

		$result = $this->propal->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Proposal not found');
		}

		if (!DolibarrApi::_checkAccessToResource('propal', $this->propal->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
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
	 * @return	Object					Object with cleaned properties
	 *
	 * @url POST    {id}/validate
	 *
	 * @throws RestException 304
	 * @throws RestException 401
	 * @throws RestException 404
	 * @throws RestException 500 System error
	 */
	public function validate($id, $notrigger = 0)
	{
		if (!DolibarrApiAccess::$user->hasRight('propal', 'creer')) {
			throw new RestException(403);
		}
		$result = $this->propal->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Commercial Proposal not found');
		}

		if (!DolibarrApi::_checkAccessToResource('propal', $this->propal->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$result = $this->propal->valid(DolibarrApiAccess::$user, $notrigger);
		if ($result == 0) {
			throw new RestException(304, 'Error nothing done. May be object is already validated');
		}
		if ($result < 0) {
			throw new RestException(500, 'Error when validating Commercial Proposal: '.$this->propal->error);
		}

		$result = $this->propal->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Commercial Proposal not found');
		}

		if (!DolibarrApi::_checkAccessToResource('propal', $this->propal->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$this->propal->fetchObjectLinked();

		return $this->_cleanObjectDatas($this->propal);
	}

	/**
	 * Close (Accept or refuse) a quote / commercial proposal
	 *
	 * @param   int     $id             Commercial proposal ID
	 * @param   int		$status			Must be 2 (accepted) or 3 (refused)				{@min 2}{@max 3}
	 * @param   string  $note_private   Add this mention at end of private note
	 * @param   int     $notrigger      Disabled triggers
	 * @return	Object					Object with cleaned properties
	 *
	 * @url POST    {id}/close
	 */
	public function close($id, $status, $note_private = '', $notrigger = 0)
	{
		if (!DolibarrApiAccess::$user->hasRight('propal', 'creer')) {
			throw new RestException(403);
		}
		$result = $this->propal->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Commercial Proposal not found');
		}

		if (!DolibarrApi::_checkAccessToResource('propal', $this->propal->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$result = $this->propal->closeProposal(DolibarrApiAccess::$user, $status, $note_private, $notrigger);
		if ($result == 0) {
			throw new RestException(304, 'Error nothing done. May be object is already closed');
		}
		if ($result < 0) {
			throw new RestException(500, 'Error when closing Commercial Proposal: '.$this->propal->error);
		}

		$result = $this->propal->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Proposal not found');
		}

		if (!DolibarrApi::_checkAccessToResource('propal', $this->propal->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$this->propal->fetchObjectLinked();

		return $this->_cleanObjectDatas($this->propal);
	}

	/**
	 * Set a commercial proposal billed. Could be also called setbilled
	 *
	 * @param   int     $id             Commercial proposal ID
	 * @return	Object					Object with cleaned properties
	 *
	 * @url POST    {id}/setinvoiced
	 */
	public function setinvoiced($id)
	{
		if (!DolibarrApiAccess::$user->hasRight('propal', 'creer')) {
			throw new RestException(403);
		}
		$result = $this->propal->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Commercial Proposal not found');
		}

		if (!DolibarrApi::_checkAccessToResource('propal', $this->propal->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$result = $this->propal->classifyBilled(DolibarrApiAccess::$user);
		if ($result < 0) {
			throw new RestException(500, 'Error : '.$this->propal->error);
		}

		$result = $this->propal->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Proposal not found');
		}

		if (!DolibarrApi::_checkAccessToResource('propal', $this->propal->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$this->propal->fetchObjectLinked();

		return $this->_cleanObjectDatas($this->propal);
	}


	/**
	 * Validate fields before create or update object
	 *
	 * @param   array           $data   Array with data to verify
	 * @return  array
	 *
	 * @throws  RestException
	 */
	private function _validate($data)
	{
		$propal = array();
		foreach (Proposals::$FIELDS as $field) {
			if (!isset($data[$field])) {
				throw new RestException(400, "$field field missing");
			}
			$propal[$field] = $data[$field];
		}
		return $propal;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 * Clean sensible object datas
	 *
	 * @param   Object  $object     Object to clean
	 * @return  Object              Object with cleaned properties
	 */
	protected function _cleanObjectDatas($object)
	{
		// phpcs:enable
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
