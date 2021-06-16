<?php
/* Copyright (C) 2015   Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2019 Maxime Kohlhaas <maxime@atm-consulting.fr>
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

require_once DOL_DOCUMENT_ROOT.'/mrp/class/mo.class.php';


/**
 * \file    mrp/class/api_mo.class.php
 * \ingroup mrp
 * \brief   File for API management of MO.
 */

/**
 * API class for MO
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class Mos extends DolibarrApi
{
	/**
	 * @var Mo $mo {@type Mo}
	 */
	public $mo;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		global $db, $conf;
		$this->db = $db;
		$this->mo = new Mo($this->db);
	}

	/**
	 * Get properties of a MO object
	 *
	 * Return an array with MO informations
	 *
	 * @param 	int 	$id ID of MO
	 * @return 	array|mixed data without useless information
	 *
	 * @url	GET {id}
	 * @throws 	RestException
	 */
	public function get($id)
	{
		if (!DolibarrApiAccess::$user->rights->mrp->read) {
			throw new RestException(401);
		}

		$result = $this->mo->fetch($id);
		if (!$result) {
			throw new RestException(404, 'MO not found');
		}

		if (!DolibarrApi::_checkAccessToResource('mrp', $this->mo->id, 'mrp_mo')) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		return $this->_cleanObjectDatas($this->mo);
	}


	/**
	 * List Mos
	 *
	 * Get a list of MOs
	 *
	 * @param string	       $sortfield	        Sort field
	 * @param string	       $sortorder	        Sort order
	 * @param int		       $limit		        Limit for list
	 * @param int		       $page		        Page number
	 * @param string           $sqlfilters          Other criteria to filter answers separated by a comma. Syntax example "(t.ref:like:'SO-%') and (t.date_creation:<:'20160101')"
	 * @return  array                               Array of order objects
	 *
	 * @throws RestException
	 */
	public function index($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $sqlfilters = '')
	{
		global $db, $conf;

		if (!DolibarrApiAccess::$user->rights->mrp->read) {
			throw new RestException(401);
		}

		$obj_ret = array();
		$tmpobject = new Mo($this->db);

		$socid = DolibarrApiAccess::$user->socid ? DolibarrApiAccess::$user->socid : '';

		$restrictonsocid = 0; // Set to 1 if there is a field socid in table of object

		// If the internal user must only see his customers, force searching by him
		$search_sale = 0;
		if ($restrictonsocid && !DolibarrApiAccess::$user->rights->societe->client->voir && !$socid) {
			$search_sale = DolibarrApiAccess::$user->id;
		}

		$sql = "SELECT t.rowid";
		if ($restrictonsocid && (!DolibarrApiAccess::$user->rights->societe->client->voir && !$socid) || $search_sale > 0) {
			$sql .= ", sc.fk_soc, sc.fk_user"; // We need these fields in order to filter by sale (including the case where the user can only see his prospects)
		}
		$sql .= " FROM ".MAIN_DB_PREFIX.$tmpobject->table_element." as t";

		if ($restrictonsocid && (!DolibarrApiAccess::$user->rights->societe->client->voir && !$socid) || $search_sale > 0) {
			$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc"; // We need this table joined to the select in order to filter by sale
		}
		$sql .= " WHERE 1 = 1";

		// Example of use $mode
		//if ($mode == 1) $sql.= " AND s.client IN (1, 3)";
		//if ($mode == 2) $sql.= " AND s.client IN (2, 3)";

		if ($tmpobject->ismultientitymanaged) {
			$sql .= ' AND t.entity IN ('.getEntity($tmpobject->element).')';
		}
		if ($restrictonsocid && (!DolibarrApiAccess::$user->rights->societe->client->voir && !$socid) || $search_sale > 0) {
			$sql .= " AND t.fk_soc = sc.fk_soc";
		}
		if ($restrictonsocid && $socid) {
			$sql .= " AND t.fk_soc = ".((int) $socid);
		}
		if ($restrictonsocid && $search_sale > 0) {
			$sql .= " AND t.rowid = sc.fk_soc"; // Join for the needed table to filter by sale
		}
		// Insert sale filter
		if ($restrictonsocid && $search_sale > 0) {
			$sql .= " AND sc.fk_user = ".((int) $search_sale);
		}
		if ($sqlfilters) {
			if (!DolibarrApi::_checkFilters($sqlfilters)) {
				throw new RestException(503, 'Error when validating parameter sqlfilters '.$sqlfilters);
			}
			$regexstring = '\(([^:\'\(\)]+:[^:\'\(\)]+:[^\(\)]+)\)';
			$sql .= " AND (".preg_replace_callback('/'.$regexstring.'/', 'DolibarrApi::_forge_criteria_callback', $sqlfilters).")";
		}

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
			$i = 0;
			while ($i < $num) {
				$obj = $this->db->fetch_object($result);
				$tmp_object = new Mo($this->db);
				if ($tmp_object->fetch($obj->rowid)) {
					$obj_ret[] = $this->_cleanObjectDatas($tmp_object);
				}
				$i++;
			}
		} else {
			throw new RestException(503, 'Error when retrieve MO list');
		}
		if (!count($obj_ret)) {
			throw new RestException(404, 'No MO found');
		}
		return $obj_ret;
	}

	/**
	 * Create MO object
	 *
	 * @param array $request_data   Request datas
	 * @return int  ID of MO
	 */
	public function post($request_data = null)
	{
		if (!DolibarrApiAccess::$user->rights->mrp->write) {
			throw new RestException(401);
		}
		// Check mandatory fields
		$result = $this->_validate($request_data);

		foreach ($request_data as $field => $value) {
			$this->mo->$field = $value;
		}
		if (!$this->mo->create(DolibarrApiAccess::$user)) {
			throw new RestException(500, "Error creating MO", array_merge(array($this->mo->error), $this->mo->errors));
		}
		return $this->mo->id;
	}

	/**
	 * Update MO
	 *
	 * @param int   $id             Id of MO to update
	 * @param array $request_data   Datas
	 *
	 * @return int
	 */
	public function put($id, $request_data = null)
	{
		if (!DolibarrApiAccess::$user->rights->mrp->write) {
			throw new RestException(401);
		}

		$result = $this->mo->fetch($id);
		if (!$result) {
			throw new RestException(404, 'MO not found');
		}

		if (!DolibarrApi::_checkAccessToResource('mrp', $this->mo->id, 'mrp_mo')) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		foreach ($request_data as $field => $value) {
			if ($field == 'id') {
				continue;
			}
			$this->mo->$field = $value;
		}

		if ($this->mo->update(DolibarrApiAccess::$user) > 0) {
			return $this->get($id);
		} else {
			throw new RestException(500, $this->mo->error);
		}
	}

	/**
	 * Delete MO
	 *
	 * @param   int     $id   MO ID
	 * @return  array
	 */
	public function delete($id)
	{
		if (!DolibarrApiAccess::$user->rights->mrp->delete) {
			throw new RestException(401);
		}
		$result = $this->mo->fetch($id);
		if (!$result) {
			throw new RestException(404, 'MO not found');
		}

		if (!DolibarrApi::_checkAccessToResource('mrp', $this->mo->id, 'mrp_mo')) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		if (!$this->mo->delete(DolibarrApiAccess::$user)) {
			throw new RestException(500, 'Error when deleting MO : '.$this->mo->error);
		}

		return array(
			'success' => array(
				'code' => 200,
				'message' => 'MO deleted'
			)
		);
	}


	/**
	 * Produce and consume
	 *
	 * Example:
	 * {
	 *   "inventorylabel": "Produce and consume using API",
	 *   "inventorycode": "PRODUCEAPI-YY-MM-DD",
	 *   "autoclose": 1,
	 *   "arraytoconsume": [],
	 *   "arraytoproduce": []
	 * }
	 *
	 * @param int       $id        		ID of state
	 * @param array 	$request_data   Request datas
	 *
	 * @url     POST {id}/produceandconsume
	 *
	 * @return int  ID of MO
	 */
	public function produceAndConsume($id, $request_data = null)
	{
		global $langs;

		$error = 0;

		if (!DolibarrApiAccess::$user->rights->mrp->write) {
			throw new RestException(401, 'Not enough permission');
		}
		$result = $this->mo->fetch($id);
		if (!$result) {
			throw new RestException(404, 'MO not found');
		}

		if ($this->mo->status != Mo::STATUS_VALIDATED && $this->mo->status != Mo::STATUS_INPROGRESS) {
			throw new RestException(401, 'Error bad status of MO');
		}

		$labelmovement = '';
		$codemovement = '';
		$autoclose = 1;
		$arraytoconsume = array();
		$arraytoproduce = array();

		foreach ($request_data as $field => $value) {
			if ($field == 'inventorylabel') {
				$labelmovement = $value;
			}
			if ($field == 'inventorycode') {
				$codemovement = $value;
			}
			if ($field == 'autoclose') {
				$autoclose = $value;
			}
			if ($field == 'arraytoconsume') {
				$arraytoconsume = $value;
			}
			if ($field == 'arraytoproduce') {
				$arraytoproduce = $value;
			}
		}

		if (empty($labelmovement)) {
			throw new RestException(500, "Field inventorylabel not prodivded");
		}
		if (empty($codemovement)) {
			throw new RestException(500, "Field inventorycode not prodivded");
		}

		// Code for consume and produce...
		require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
		require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';
		dol_include_once('/mrp/lib/mrp_mo.lib.php');

		$stockmove = new MouvementStock($this->db);

		if (!empty($arraytoconsume) && !empty($arraytoproduce)) {
			$pos = 0;
			$arrayofarrayname = array("arraytoconsume","arraytoproduce");
			foreach ($arrayofarrayname as $arrayname) {
				foreach ($$arrayname as $value) {
					$tmpproduct = new Product($this->db);
					if (empty($value["objectid"])) {
						throw new RestException(500, "Field objectid required in ".$arrayname);
					}
					$tmpproduct->fetch($value["qty"]);
					if (empty($value["qty"])) {
						throw new RestException(500, "Field qty required in ".$arrayname);
					}
					if ($value["qty"]!=0) {
						$qtytoprocess = $value["qty"];
						if (isset($value["fk_warehouse"])) {	// If there is a warehouse to set
							if (!($value["fk_warehouse"] > 0)) {	// If there is no warehouse set.
								throw new RestException(500, "Field fk_warehouse must be > 0 in ".$arrayname);
								$error++;
							}
							if ($tmpproduct->status_batch) {
								throw new RestException(500, "Product ".$tmpproduct->ref."must be in batch");
								$error++;
							}
						}
						$idstockmove = 0;
						if (!$error && $value["fk_warehouse"] > 0) {
							// Record stock movement
							$id_product_batch = 0;
							$stockmove->origin = $this->mo;
							if ($qtytoprocess >= 0) {
								$moline = new MoLine($this->db);
								$moline->fk_mo = $this->mo->id;
								$moline->position = $pos;
								$moline->fk_product = $value["objectid"];
								$moline->fk_warehouse = $value["fk_warehouse"];
								$moline->qty = $qtytoprocess;
								$moline->batch = $tmpproduct->status_batch;
								$moline->role = 'toproduce';
								$moline->fk_mrp_production = "";
								$moline->fk_stock_movement = $idstockmove;
								$moline->fk_user_creat = DolibarrApiAccess::$user->id;

								$resultmoline = $moline->create(DolibarrApiAccess::$user);
								if ($resultmoline <= 0) {
									$error++;
									throw new RestException(500, $moline->error);
								}
								$idstockmove = $stockmove->livraison(DolibarrApiAccess::$user, $value["objectid"], $value["fk_warehouse"], $qtytoprocess, 0, $labelmovement, dol_now(), '', '', $tmpproduct->status_batch, $id_product_batch, $codemovement);
							} else {
								$moline = new MoLine($this->db);
								$moline->fk_mo = $this->mo->id;
								$moline->position = $pos;
								$moline->fk_product = $value["objectid"];
								$moline->fk_warehouse = $value["fk_warehouse"];
								$moline->qty = $qtytoprocess;
								$moline->batch = $tmpproduct->status_batch;
								$moline->role = 'toconsume';
								$moline->fk_mrp_production = "";
								$moline->fk_stock_movement = $idstockmove;
								$moline->fk_user_creat = DolibarrApiAccess::$user->id;

								$resultmoline = $moline->create(DolibarrApiAccess::$user);
								if ($resultmoline <= 0) {
									$error++;
									throw new RestException(500, $moline->error);
								}
								$idstockmove = $stockmove->reception(DolibarrApiAccess::$user, $value["objectid"], $value["fk_warehouse"], $qtytoprocess, 0, $labelmovement, dol_now(), '', '', $tmpproduct->status_batch, $id_product_batch, $codemovement);
							}
							if ($idstockmove < 0) {
								$error++;
								throw new RestException(500, $stockmove->error);
							}
						}
						if (!$error) {
							// Record consumption
							$moline = new MoLine($this->db);
							$moline->fk_mo = $this->mo->id;
							$moline->position = $pos;
							$moline->fk_product = $value["objectid"];
							$moline->fk_warehouse = $value["fk_warehouse"];
							$moline->qty = $qtytoprocess;
							$moline->batch = $tmpproduct->status_batch;
							if ($arrayname == "arraytoconsume") {
								$moline->role = 'consumed';
							} else {
								$moline->role = 'produced';
							}
							$moline->fk_mrp_production = "";
							$moline->fk_stock_movement = $idstockmove;
							$moline->fk_user_creat = DolibarrApiAccess::$user->id;

							$resultmoline = $moline->create(DolibarrApiAccess::$user);
							if ($resultmoline <= 0) {
								$error++;
								throw new RestException(500, $moline->error);
							}

							$pos++;
						}
					}
				}
			}
			if (!$error) {
				$consumptioncomplete = true;
				$productioncomplete = true;

				if ($autoclose <= 0) {
					$consumptioncomplete = false;
					$productioncomplete = false;
				}
			}
		} else {
			$pos = 0;
			foreach ($this->mo->lines as $line) {
				if ($line->role == 'toconsume') {
					$tmpproduct = new Product($this->db);
					$tmpproduct->fetch($line->fk_product);
					if ($line->qty != 0) {
						$qtytoprocess = $line->qty;
						if (isset($line->fk_warehouse)) {	// If there is a warehouse to set
							if (!($line->fk_warehouse > 0)) {	// If there is no warehouse set.
								$langs->load("errors");
								throw new RestException(500, $langs->trans("ErrorFieldRequiredForProduct", $langs->transnoentitiesnoconv("Warehouse"), $tmpproduct->ref));
								$error++;
							}
							if ($tmpproduct->status_batch) {
								$langs->load("errors");
								throw new RestException(500, $langs->trans("ErrorFieldRequiredForProduct", $langs->transnoentitiesnoconv("Batch"), $tmpproduct->ref));
								$error++;
							}
						}
						$idstockmove = 0;
						if (!$error && $line->fk_warehouse > 0) {
							// Record stock movement
							$id_product_batch = 0;
							$stockmove->origin = $this->mo;
							if ($qtytoprocess >= 0) {
								$idstockmove = $stockmove->livraison(DolibarrApiAccess::$user, $line->fk_product, $line->fk_warehouse, $qtytoprocess, 0, $labelmovement, dol_now(), '', '', $tmpproduct->status_batch, $id_product_batch, $codemovement);
							} else {
								$idstockmove = $stockmove->reception(DolibarrApiAccess::$user, $line->fk_product, $line->fk_warehouse, $qtytoprocess, 0, $labelmovement, dol_now(), '', '', $tmpproduct->status_batch, $id_product_batch, $codemovement);
							}
							if ($idstockmove < 0) {
								$error++;
								throw new RestException(500, $stockmove->error);
							}
						}
						if (!$error) {
							// Record consumption
							$moline = new MoLine($this->db);
							$moline->fk_mo = $this->mo->id;
							$moline->position = $pos;
							$moline->fk_product = $line->fk_product;
							$moline->fk_warehouse = $line->fk_warehouse;
							$moline->qty = $qtytoprocess;
							$moline->batch = $tmpproduct->status_batch;
							$moline->role = 'consumed';
							$moline->fk_mrp_production = $line->id;
							$moline->fk_stock_movement = $idstockmove;
							$moline->fk_user_creat = DolibarrApiAccess::$user->id;

							$resultmoline = $moline->create(DolibarrApiAccess::$user);
							if ($resultmoline <= 0) {
								$error++;
								throw new RestException(500, $moline->error);
							}

							$pos++;
						}
					}
				}
			}
			$pos = 0;
			foreach ($this->mo->lines as $line) {
				if ($line->role == 'toproduce') {
					$tmpproduct = new Product($this->db);
					$tmpproduct->fetch($line->fk_product);
					if ($line->qty != 0) {
						$qtytoprocess = $line->qty;
						if (isset($line->fk_warehouse)) {	// If there is a warehouse to set
							if (!($line->fk_warehouse > 0)) {	// If there is no warehouse set.
								$langs->load("errors");
								throw new RestException(500, $langs->trans("ErrorFieldRequiredForProduct", $langs->transnoentitiesnoconv("Warehouse"), $tmpproduct->ref));
								$error++;
							}
							if ($tmpproduct->status_batch) {
								$langs->load("errors");
								throw new RestException(500, $langs->trans("ErrorFieldRequiredForProduct", $langs->transnoentitiesnoconv("Batch"), $tmpproduct->ref));
								$error++;
							}
						}
						$idstockmove = 0;
						if (!$error && $line->fk_warehouse > 0) {
							// Record stock movement
							$id_product_batch = 0;
							$stockmove->origin = $this->mo;
							if ($qtytoprocess >= 0) {
								$idstockmove = $stockmove->livraison(DolibarrApiAccess::$user, $line->fk_product, $line->fk_warehouse, $qtytoprocess, 0, $labelmovement, dol_now(), '', '', $tmpproduct->status_batch, $id_product_batch, $codemovement);
							} else {
								$idstockmove = $stockmove->reception(DolibarrApiAccess::$user, $line->fk_product, $line->fk_warehouse, $qtytoprocess, 0, $labelmovement, dol_now(), '', '', $tmpproduct->status_batch, $id_product_batch, $codemovement);
							}
							if ($idstockmove < 0) {
								$error++;
								throw new RestException(500, $stockmove->error);
							}
						}
						if (!$error) {
							// Record consumption
							$moline = new MoLine($this->db);
							$moline->fk_mo = $this->mo->id;
							$moline->position = $pos;
							$moline->fk_product = $line->fk_product;
							$moline->fk_warehouse = $line->fk_warehouse;
							$moline->qty = $qtytoprocess;
							$moline->batch = $tmpproduct->status_batch;
							$moline->role = 'produced';
							$moline->fk_mrp_production = $line->id;
							$moline->fk_stock_movement = $idstockmove;
							$moline->fk_user_creat = DolibarrApiAccess::$user->id;

							$resultmoline = $moline->create(DolibarrApiAccess::$user);
							if ($resultmoline <= 0) {
								$error++;
								throw new RestException(500, $moline->error);
							}

							$pos++;
						}
					}
				}
			}

			if (!$error) {
				$consumptioncomplete = true;
				$productioncomplete = true;

				if ($autoclose > 0) {
					foreach ($this->mo->lines as $line) {
						if ($line->role == 'toconsume') {
							$arrayoflines = $this->mo->fetchLinesLinked('consumed', $line->id);
							$alreadyconsumed = 0;
							foreach ($arrayoflines as $line2) {
								$alreadyconsumed += $line2['qty'];
							}

							if ($alreadyconsumed < $line->qty) {
								$consumptioncomplete = false;
							}
						}
						if ($line->role == 'toproduce') {
							$arrayoflines = $this->mo->fetchLinesLinked('produced', $line->id);
							$alreadyproduced = 0;
							foreach ($arrayoflines as $line2) {
								$alreadyproduced += $line2['qty'];
							}

							if ($alreadyproduced < $line->qty) {
								$productioncomplete = false;
							}
						}
					}
				} else {
					$consumptioncomplete = false;
					$productioncomplete = false;
				}
			}
		}
		// Update status of MO
		dol_syslog("consumptioncomplete = ".$consumptioncomplete." productioncomplete = ".$productioncomplete);
		//var_dump("consumptioncomplete = ".$consumptioncomplete." productioncomplete = ".$productioncomplete);
		if ($consumptioncomplete && $productioncomplete) {
			$result = $this->mo->setStatut(self::STATUS_PRODUCED, 0, '', 'MRP_MO_PRODUCED');
		} else {
			$result = $this->mo->setStatut(self::STATUS_INPROGRESS, 0, '', 'MRP_MO_PRODUCED');
		}
		if ($result <= 0) {
			throw new RestException(500, $this->mo->error);
		}

		return $this->mo->id;
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

		unset($object->rowid);
		unset($object->canvas);

		unset($object->name);
		unset($object->lastname);
		unset($object->firstname);
		unset($object->civility_id);
		unset($object->statut);
		unset($object->state);
		unset($object->state_id);
		unset($object->state_code);
		unset($object->region);
		unset($object->region_code);
		unset($object->country);
		unset($object->country_id);
		unset($object->country_code);
		unset($object->barcode_type);
		unset($object->barcode_type_code);
		unset($object->barcode_type_label);
		unset($object->barcode_type_coder);
		unset($object->total_ht);
		unset($object->total_tva);
		unset($object->total_localtax1);
		unset($object->total_localtax2);
		unset($object->total_ttc);
		unset($object->fk_account);
		unset($object->comments);
		unset($object->note);
		unset($object->mode_reglement_id);
		unset($object->cond_reglement_id);
		unset($object->cond_reglement);
		unset($object->shipping_method_id);
		unset($object->fk_incoterms);
		unset($object->label_incoterms);
		unset($object->location_incoterms);

		// If object has lines, remove $db property
		if (isset($object->lines) && is_array($object->lines) && count($object->lines) > 0) {
			$nboflines = count($object->lines);
			for ($i = 0; $i < $nboflines; $i++) {
				$this->_cleanObjectDatas($object->lines[$i]);

				unset($object->lines[$i]->lines);
				unset($object->lines[$i]->note);
			}
		}

		return $object;
	}

	/**
	 * Validate fields before create or update object
	 *
	 * @param	array		$data   Array of data to validate
	 * @return	array
	 *
	 * @throws	RestException
	 */
	private function _validate($data)
	{
		$myobject = array();
		foreach ($this->mo->fields as $field => $propfield) {
			if (in_array($field, array('rowid', 'entity', 'date_creation', 'tms', 'fk_user_creat')) || $propfield['notnull'] != 1) {
				continue; // Not a mandatory field
			}
			if (!isset($data[$field])) {
				throw new RestException(400, "$field field missing");
			}
				$myobject[$field] = $data[$field];
		}
		return $myobject;
	}
}
