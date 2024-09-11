<?php
/* Copyright (C) 2016   Xebax Christy           <xebax@wanadoo.fr>
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

require_once DOL_DOCUMENT_ROOT.'/adherents/class/subscription.class.php';

/**
 * API class for subscriptions
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class Subscriptions extends DolibarrApi
{
	/**
	 * @var array   $FIELDS     Mandatory fields, checked when create and update object
	 */
	public static $FIELDS = array(
		'fk_adherent',
		'dateh',
		'datef',
		'amount',
	);

	/**
	 * Constructor
	 */
	public function __construct()
	{
		global $db, $conf;
		$this->db = $db;
	}

	/**
	 * Get properties of a subscription object
	 *
	 * Return an array with subscription information
	 *
	 * @param   int     $id				ID of subscription
	 * @return  Object					Object with cleaned properties
	 *
	 * @throws	RestException	403		Access denied
	 * @throws	RestException	404		No Subscription found
	 */
	public function get($id)
	{
		if (!DolibarrApiAccess::$user->hasRight('adherent', 'cotisation', 'lire')) {
			throw new RestException(403);
		}

		$subscription = new Subscription($this->db);
		$result = $subscription->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Subscription not found');
		}

		return $this->_cleanObjectDatas($subscription);
	}

	/**
	 * List subscriptions
	 *
	 * Get a list of subscriptions
	 *
	 * @param string    $sortfield  		Sort field
	 * @param string    $sortorder  		Sort order
	 * @param int       $limit     			Limit for list
	 * @param int       $page       		Page number
	 * @param string    $sqlfilters 		Other criteria to filter answers separated by a comma. Syntax example "(t.ref:like:'SO-%') and (t.import_key:<:'20160101')"
	 * @param string 	$properties 		Restrict the data returned to these properties. Ignored if empty. Comma separated list of properties names
	 * @param bool      $pagination_data    If this parameter is set to true the response will include pagination data. Default value is false. Page starts from 0*
	 * @return array 						Array of subscription objects
	 *
	 * @throws	RestException	403		Access denied
	 * @throws	RestException	404		No Subscription found
	 * @throws	RestException	503		Error when retrieving Subscription list
	 */
	public function index($sortfield = "dateadh", $sortorder = 'ASC', $limit = 100, $page = 0, $sqlfilters = '', $properties = '', $pagination_data = false)
	{
		global $conf;

		$obj_ret = array();

		if (!DolibarrApiAccess::$user->hasRight('adherent', 'cotisation', 'lire')) {
			throw new RestException(403);
		}

		$sql = "SELECT rowid";
		$sql .= " FROM ".MAIN_DB_PREFIX."subscription as t";
		$sql .= ' WHERE 1 = 1';
		// Add sql filters
		if ($sqlfilters) {
			$errormessage = '';
			$sql .= forgeSQLFromUniversalSearchCriteria($sqlfilters, $errormessage);
			if ($errormessage) {
				throw new RestException(503, 'Error when validating parameter sqlfilters -> '.$errormessage);
			}
		}

		//this query will return total orders with the filters given
		$sqlTotals = str_replace('SELECT rowid', 'SELECT count(rowid) as total', $sql);

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
			$i = 0;
			$num = $this->db->num_rows($result);
			while ($i < min($limit, $num)) {
				$obj = $this->db->fetch_object($result);
				$subscription = new Subscription($this->db);
				if ($subscription->fetch($obj->rowid)) {
					$obj_ret[] = $this->_filterObjectProperties($this->_cleanObjectDatas($subscription), $properties);
				}
				$i++;
			}
		} else {
			throw new RestException(503, 'Error when retrieve subscription list : '.$this->db->lasterror());
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
	 * Create subscription object
	 *
	 * @param array $request_data   Request data
	 * @return int  ID of subscription
	 *
	 * @throws	RestException	403		Access denied
	 * @throws	RestException	500		Error when creating Subscription
	 */
	public function post($request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('adherent', 'cotisation', 'creer')) {
			throw new RestException(403);
		}
		// Check mandatory fields
		$result = $this->_validate($request_data);

		$subscription = new Subscription($this->db);
		foreach ($request_data as $field => $value) {
			if ($field === 'caller') {
				// Add a mention of caller so on trigger called after action, we can filter to avoid a loop if we try to sync back again with the caller
				$subscription->context['caller'] = sanitizeVal($request_data['caller'], 'aZ09');
				continue;
			}

			$subscription->$field = $this->_checkValForAPI($field, $value, $subscription);
		}
		if ($subscription->create(DolibarrApiAccess::$user) < 0) {
			throw new RestException(500, 'Error when creating subscription', array_merge(array($subscription->error), $subscription->errors));
		}
		return $subscription->id;
	}

	/**
	 * Update subscription
	 *
	 * @param 	int   		$id             ID of subscription to update
	 * @param 	array 		$request_data   Datas
	 * @return 	Object						Updated object
	 *
	 * @throws	RestException	403		Access denied
	 * @throws	RestException	404		No Subscription found
	 * @throws	RestException	500		Error when updating Subscription
	 */
	public function put($id, $request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('adherent', 'creer')) {
			throw new RestException(403);
		}

		$subscription = new Subscription($this->db);
		$result = $subscription->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Subscription not found');
		}

		foreach ($request_data as $field => $value) {
			if ($field == 'id') {
				continue;
			}
			if ($field === 'caller') {
				// Add a mention of caller so on trigger called after action, we can filter to avoid a loop if we try to sync back again with the caller
				$subscription->context['caller'] = sanitizeVal($request_data['caller'], 'aZ09');
				continue;
			}

			$subscription->$field = $this->_checkValForAPI($field, $value, $subscription);
		}

		if ($subscription->update(DolibarrApiAccess::$user) > 0) {
			return $this->get($id);
		} else {
			throw new RestException(500, 'Error when updating contribution: '.$subscription->error);
		}
	}

	/**
	 * Delete subscription
	 *
	 * @param int $id   ID of subscription to delete
	 * @return array
	 *
	 * @throws	RestException	403		Access denied
	 * @throws	RestException	404		No Subscription found
	 * @throws	RestException	409		No Subscription deleted
	 * @throws	RestException	500		Error when deleting Subscription
	 */
	public function delete($id)
	{
		// The right to delete a subscription comes with the right to create one.
		if (!DolibarrApiAccess::$user->hasRight('adherent', 'cotisation', 'creer')) {
			throw new RestException(403);
		}
		$subscription = new Subscription($this->db);
		$result = $subscription->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Subscription not found');
		}

		$res = $subscription->delete(DolibarrApiAccess::$user);
		if ($res < 0) {
			throw new RestException(500, "Can't delete, error occurs");
		} elseif ($res == 0) {
			throw new RestException(409, "No subscription whas deleted");
		}

		return array(
			'success' => array(
				'code' => 200,
				'message' => 'Subscription deleted'
			)
		);
	}

	/**
	 * Validate fields before creating an object
	 *
	 * @param array|null    $data   Data to validate
	 * @return array
	 *
	 * @throws RestException
	 */
	private function _validate($data)
	{
		$subscription = array();
		foreach (Subscriptions::$FIELDS as $field) {
			if (!isset($data[$field])) {
				throw new RestException(400, "$field field missing");
			}
			$subscription[$field] = $data[$field];
		}
		return $subscription;
	}
}
