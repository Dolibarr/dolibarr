<?php
/* Copyright (C) 2015		Jean-François Ferry		<jfefe@aternatik.fr>
 * Copyright (C) 2024		Frédéric France			<frederic.france@free.fr>
 * Copyright (C) 2025		Charlene Benke			<charlene@patas-monkey.com>
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

dol_include_once('/compta/paiement/class/paiement.class.php');



/**
 * \file    compta/paiement/class/api_paiement.class.php
 * \ingroup paiement
 * \brief   File for API management of paiement.
 */

/**
 * API class for paiement
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class Paiements extends DolibarrApi
{
	/**
	 * @var Paiement {@type Paiement}
	 */
	public $paiement;

	/**
	 * Constructor
	 *
	 * @url     GET /
	 */
	public function __construct()
	{
		global $db;
		$this->db = $db;
		$this->paiement = new Paiement($this->db);
	}

	/* BEGIN MODULEBUILDER API PAIEMENT */

	/**
	 * Get properties of a paiement object
	 *
	 * Return an array with paiement information
	 *
	 * @param	int		$id				ID of paiement
	 * @return  Object					Object with cleaned properties
	 * @phan-return	Paiement			Object with cleaned properties
	 * @phpstan-return	Paiement			Object with cleaned properties
	 *
	 * @phan-return  Paiement
	 *
	 *
	 * @throws RestException 403 Not allowed
	 * @throws RestException 404 Not found
	 */
	public function get($id)
	{
		if (!DolibarrApiAccess::$user->hasRight('facture', 'lire')) {
			throw new RestException(403);
		}

		$result = $this->paiement->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Paiement not found');
		}

		return $this->_cleanObjectDatas($this->paiement);
	}


	/**
	 * List paiements
	 *
	 * Get a list of paiements
	 *
	 * @param string		   $sortfield			Sort field
	 * @param string		   $sortorder			Sort order
	 * @param int			   $limit				Limit for list
	 * @param int			   $page				Page number
	 * @param string           $sqlfilters          Other criteria to filter answers separated by a comma. Syntax example "(t.ref:like:'SO-%') and (t.date_creation:<:'20160101')"
	 * @param string		   $properties			Restrict the data returned to these properties. Ignored if empty. Comma separated list of properties names
	 * @return  array                               Array of paiements objects
	 * @phan-return array<int,Paiement>
	 * @phpstan-return array<int,Paiement>
	 *
	 * @throws RestException 403 Not allowed
	 * @throws RestException 503 System error
	 *
	*/
	public function index($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $sqlfilters = '', $properties = '')
	{
		$obj_ret = array();
		$tmpobject = new Paiement($this->db);

		if (!DolibarrApiAccess::$user->hasRight('facture', 'lire')) {
			throw new RestException(403);
		}

		$sql = "SELECT t.rowid";
		$sql .= " FROM ".$this->db->prefix().$tmpobject->table_element." AS t";
		$sql .= " WHERE 1 = 1";
		if ($tmpobject->ismultientitymanaged) {
			$sql .= ' AND t.entity IN ('.getEntity($tmpobject->element).')';
		}
		if ($sqlfilters) {
			$errormessage = '';
			$sql .= forgeSQLFromUniversalSearchCriteria($sqlfilters, $errormessage);
			if ($errormessage) {
				throw new RestException(400, 'Error when validating parameter sqlfilters -> '.$errormessage);
			}
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
		$i = 0;
		if ($result) {
			$num = $this->db->num_rows($result);
			$min = min($num, ($limit <= 0 ? $num : $limit));
			while ($i < $min) {
				$obj = $this->db->fetch_object($result);
				$tmp_object = new Paiement($this->db);
				if ($tmp_object->fetch($obj->rowid)) {
					$obj_ret[] = $this->_filterObjectProperties($this->_cleanObjectDatas($tmp_object), $properties);
				}
				$i++;
			}
		} else {
			throw new RestException(503, 'Error when retrieving Paiement list: '.$this->db->lasterror());
		}

		return $obj_ret;
	}


	/**
	 * Update paiement
	 *
	 * @param 	int   		$id             Id of paiement to update
	 * @param 	array 		$request_data   Data
	 * @phan-param ?array<string,mixed>	$request_data
	 * @phpstan-param ?array<string,mixed>	$request_data
	 * @return 	Object						Object after update
	 * @phan-return Paiement
	 * @phpstan-return Paiement
	 *
	 * @throws RestException 403 Not allowed
	 * @throws RestException 404 Not found
	 * @throws RestException 500 System error
	 *
	 */
	public function put($id, $request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('facture', 'creer')) {
			throw new RestException(403);
		}

		$result = $this->paiement->fetch($id);
		if (!$result) {
			throw new RestException(404, 'paiement not found');
		}

		foreach ($request_data as $field => $value) {
			if ($field == 'id') {
				continue;
			}
			if ($field === 'caller') {
				// Add a mention of caller so on trigger called after action, we can filter to avoid a loop if we try to sync back again with the caller
				$this->paiement->context['caller'] = sanitizeVal($request_data['caller'], 'aZ09');
				continue;
			}

			$this->paiement->$field = $this->_checkValForAPI($field, $value, $this->paiement);
		}

		// Clean data
		// $this->paiement->abc = sanitizeVal($this->paiement->abc, 'alphanohtml');

		if ($this->paiement->update(DolibarrApiAccess::$user, 0) > 0) {
			return $this->get($id);
		} else {
			throw new RestException(500, $this->paiement->error);
		}
	}

	/**
	 * Delete paiement
	 *
	 * @param   int     $id   Paiement ID
	 * @return  array
	 * @phan-return array<string,array{code:int,message:string}>
	 * @phpstan-return array<string,array{code:int,message:string}>
	 *
	 * @throws RestException 403 Not allowed
	 * @throws RestException 404 Not found
	 * @throws RestException 409 Nothing to do
	 * @throws RestException 500 System error
	 *
	 */
	public function delete($id)
	{
		if (!DolibarrApiAccess::$user->hasRight('facture', 'supprimer')) {
			throw new RestException(403);
		}

		$result = $this->paiement->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Paiement not found');
		}

		if ($this->paiement->delete(DolibarrApiAccess::$user) == 0) {
			throw new RestException(409, 'Error when deleting Paiement : '.$this->paiement->error);
		} elseif ($this->paiement->delete(DolibarrApiAccess::$user) < 0) {
			throw new RestException(500, 'Error when deleting Paiement : '.$this->paiement->error);
		}

		return array(
			'success' => array(
				'code' => 200,
				'message' => 'Paiement deleted'
			)
		);
	}


	/* END MODULEBUILDER API PAIEMENT */



	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 * Clean sensitive object data fields
	 * @phpstan-template T of Object
	 *
	 * @param   Object  $object     Object to clean
	 * @return  Object              Object with cleaned properties
	 *
	 * @phpstan-param T $object
	 * @phpstan-return T
	 */
	protected function _cleanObjectDatas($object)
	{
		// phpcs:enable
		$object = parent::_cleanObjectDatas($object);

		unset($object->rowid);
		unset($object->canvas);

		return $object;
	}
}
