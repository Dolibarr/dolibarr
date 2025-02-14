<?php
/* Copyright (C) 2015		Jean-François Ferry		<jfefe@aternatik.fr>
 * Copyright (C) 2019		Cedric Ancelin			<icedo.anc@gmail.com>
 * Copyright (C) 2024		Christian Humpel		<christian.humpel@gmail.com>
 * Copyright (C) 2024		MDW						<mdeweerd@users.noreply.github.com>
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

require_once DOL_DOCUMENT_ROOT.'/workstation/class/workstation.class.php';

/**
 * \file    htdocs/workstation/class/api_workstations.class.php
 * \ingroup workstation
 * \brief   File for API management of Workstations.
 */


/**
 * API class for workstations
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class Workstations extends DolibarrApi
{
	/**
	 * @var Workstation $workstation {@type Workstation}
	 */
	public $workstation;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		global $db, $conf;

		$this->db = $db;
		$this->workstation = new Workstation($this->db);
	}

	/**
	 * Get properties of a workstation object by id
	 *
	 * Return an array with workstation information.
	 *
	 * @param  int    $id                  ID of workstation
	 * @return array|mixed                 Data without useless information
	 *
	 * @url    GET {id}
	 *
	 * @throws RestException 401
	 * @throws RestException 403
	 * @throws RestException 404
	 */
	public function get($id)
	{
		return $this->_fetch($id);
	}

	/**
	 * Get properties of a workstation object by ref
	 *
	 * Return an array with workstation information.
	 *
	 * @param  string $ref                Ref of element
	 *
	 * @return array|mixed                 Data without useless information
	 *
	 * @url GET ref/{ref}
	 *
	 * @throws RestException 401
	 * @throws RestException 403
	 * @throws RestException 404
	 */
	public function getByRef($ref)
	{
		return $this->_fetch(0, $ref);
	}

	/**
	 * List workstations
	 *
	 * Get a list of workstations
	 *
	 * @param  string $sortfield			Sort field
	 * @param  string $sortorder			Sort order
	 * @param  int    $limit				Limit for list
	 * @param  int    $page					Page number
	 * @param  string $sqlfilters			Other criteria to filter answers separated by a comma. Syntax example "(t.tobuy:=:0) and (t.tosell:=:1)"
	 * @param string  $properties			Restrict the data returned to these properties. Ignored if empty. Comma separated list of properties names
	 * @return array						Array of workstation objects
	 */
	public function index($sortfield = "t.ref", $sortorder = 'ASC', $limit = 100, $page = 0, $sqlfilters = '', $properties = '')
	{
		global $db, $conf;

		if (!DolibarrApiAccess::$user->rights->workstation->workstation->read) {
			throw new RestException(403);
		}

		$obj_ret = array();

		$socid = DolibarrApiAccess::$user->socid ? DolibarrApiAccess::$user->socid : '';

		$sql = "SELECT t.rowid, t.ref";
		$sql .= " FROM ".$this->db->prefix()."workstation_workstation as t";

		// Add sql filters
		if ($sqlfilters) {
			$errormessage = '';
			$sql .= forgeSQLFromUniversalSearchCriteria($sqlfilters, $errormessage);
			if ($errormessage) {
				throw new RestException(400, 'Error when validating parameter sqlfilters -> '.$errormessage);
			}
		}

		// this query will return total products with the filters given
		$sqlTotals =  str_replace('SELECT t.rowid, t.ref', 'SELECT count(t.rowid) as total', $sql);

		$sql .= $this->db->order($sortfield, $sortorder);
		if ($limit) {
			if ($page < 0) {
				$page = 0;
			}
			$offset = $limit * $page;

			$sql .= $this->db->plimit($limit + 1, $offset);
		}

		$result = $this->db->query($sql);
		if ($result) {
			$num = $this->db->num_rows($result);
			$min = min($num, ($limit <= 0 ? $num : $limit));
			$i = 0;
			while ($i < $min) {
				$obj = $this->db->fetch_object($result);
				$workstation_static = new Workstation($this->db);
				if ($workstation_static->fetch($obj->rowid)) {
					$obj_ret[] = $this->_filterObjectProperties($this->_cleanObjectDatas($workstation_static), $properties);
				}
				$i++;
			}
		} else {
			throw new RestException(503, 'Error when retrieve workstation list : '.$this->db->lasterror());
		}

		//if $pagination_data is true the response will contain element data with all values and element pagination with pagination data(total,page,limit)
		if ($page > 0) {
			$totalsResult = $this->db->query($sqlTotals);
			$total = $this->db->fetch_object($totalsResult)->total;

			$tmp = $obj_ret;
			$obj_ret = array();

			$obj_ret['data'] = $tmp;
			$obj_ret['pagination'] = array(
				'total' => (int) $total,
				'page' => $page, //count starts from 0
				'page_count' => ceil((int) $total / $limit),
				'limit' => $limit
			);
		}

		return $obj_ret;
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

		unset($object->statut);

		unset($object->regeximgext);
		unset($object->price_by_qty);
		unset($object->prices_by_qty_id);
		unset($object->libelle);
		unset($object->product_id_already_linked);
		unset($object->reputations);
		unset($object->db);
		unset($object->name);
		unset($object->firstname);
		unset($object->lastname);
		unset($object->civility_id);
		unset($object->contact);
		unset($object->contact_id);
		unset($object->contacts_ids);
		unset($object->thirdparty);
		unset($object->user);
		unset($object->origin);
		unset($object->origin_id);
		unset($object->fourn_pu);
		unset($object->fourn_price_base_type);
		unset($object->fourn_socid);
		unset($object->ref_fourn);
		unset($object->ref_supplier);
		unset($object->product_fourn_id);
		unset($object->fk_project);

		unset($object->linked_objects);
		unset($object->linkedObjectsIds);
		unset($object->oldref);
		unset($object->actionmsg);
		unset($object->actionmsg2);
		unset($object->canvas);
		unset($object->origin_object);
		unset($object->expedition);
		unset($object->livraison);
		unset($object->commandeFournisseur);
		unset($object->country_id);
		unset($object->country_code);
		unset($object->state_id);
		unset($object->region_id);
		unset($object->barcode_type);
		unset($object->barcode_type_coder);
		unset($object->shipping_method);
		unset($object->fk_multicurrency);
		unset($object->multicurrency_code);
		unset($object->multicurrency_tx);
		unset($object->multicurrency_total_ht);
		unset($object->multicurrency_total_tva);
		unset($object->multicurrency_total_ttc);
		unset($object->multicurrency_total_localtax1);
		unset($object->multicurrency_total_localtax2);
		unset($object->last_main_doc);
		unset($object->total_ht);
		unset($object->total_tva);
		unset($object->total_localtax1);
		unset($object->total_localtax2);
		unset($object->total_ttc);
		unset($object->totalpaid);
		unset($object->labelStatus);
		unset($object->labelStatusShort);
		unset($object->tpl);
		unset($object->showphoto_on_popup);
		unset($object->nb);
		unset($object->output);
		unset($object->extraparams);
		unset($object->product);
		unset($object->cond_reglement_supplier_id);
		unset($object->deposit_percent);
		unset($object->retained_warranty_fk_cond_reglement);
		unset($object->warehouse_id);
		unset($object->rowid);

		unset($object->mode_reglement_id);
		unset($object->cond_reglement_id);
		unset($object->demand_reason_id);
		unset($object->transport_mode_id);
		unset($object->cond_reglement);
		unset($object->shipping_method_id);
		unset($object->model_pdf);
		unset($object->note);

		unset($object->nbphoto);
		unset($object->recuperableonly);
		unset($object->multiprices_recuperableonly);
		unset($object->tva_npr);
		unset($object->lines);
		unset($object->fk_bank);
		unset($object->fk_account);

		unset($object->supplierprices);

		unset($object->stock_reel);
		unset($object->stock_theorique);
		unset($object->stock_warehouse);

		return $object;
	}

	/**
	 * Get properties of 1 workstation object.
	 * Return an array with workstation information.
	 *
	 * @param  int    $id						ID of product
	 * @param  string $ref						Ref of element
	 * @return array|mixed						Data without useless information
	 *
	 * @throws RestException 401
	 * @throws RestException 403
	 * @throws RestException 404
	 */
	private function _fetch($id, $ref = '')
	{
		if (empty($id) && empty($ref)) {
			throw new RestException(400, 'bad value for parameter id or ref');
		}

		$id = (empty($id) ? 0 : $id);

		if (!DolibarrApiAccess::$user->rights->workstation->workstation->read) {
			throw new RestException(403);
		}

		$result = $this->workstation->fetch($id, $ref);
		if (!$result) {
			throw new RestException(404, 'Workstation not found');
		}

		if (!DolibarrApi::_checkAccessToResource('workstation', $this->workstation->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		return $this->_cleanObjectDatas($this->workstation);
	}
}
