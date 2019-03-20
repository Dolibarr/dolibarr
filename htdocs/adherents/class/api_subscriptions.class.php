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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
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
    static $FIELDS = array(
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
     * Return an array with subscription informations
     *
     * @param     int     $id ID of subscription
     * @return    array|mixed data without useless information
     *
     * @throws    RestException
     */
    public function get($id)
    {
        if(! DolibarrApiAccess::$user->rights->adherent->cotisation->lire) {
            throw new RestException(401);
        }

        $subscription = new Subscription($this->db);
        $result = $subscription->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'Subscription not found');
        }

        return $this->_cleanObjectDatas($subscription);
    }

    /**
     * List subscriptions
     *
     * Get a list of subscriptions
     *
     * @param string    $sortfield  Sort field
     * @param string    $sortorder  Sort order
     * @param int       $limit      Limit for list
     * @param int       $page       Page number
     * @param string    $sqlfilters Other criteria to filter answers separated by a comma. Syntax example "(t.ref:like:'SO-%') and (t.import_key:<:'20160101')"
     * @return array Array of subscription objects
     *
     * @throws RestException
     */
    public function index($sortfield = "dateadh", $sortorder = 'ASC', $limit = 100, $page = 0, $sqlfilters = '')
    {
        global $db, $conf;

        $obj_ret = array();

        if(! DolibarrApiAccess::$user->rights->adherent->cotisation->lire) {
            throw new RestException(401);
        }

        $sql = "SELECT rowid";
        $sql.= " FROM ".MAIN_DB_PREFIX."subscription as t";
        $sql.= ' WHERE 1 = 1';
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
        if ($limit)    {
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
            $i=0;
            $num = $db->num_rows($result);
            while ($i < min($limit, $num))
            {
                $obj = $db->fetch_object($result);
                $subscription = new Subscription($this->db);
                if($subscription->fetch($obj->rowid)) {
                    $obj_ret[] = $this->_cleanObjectDatas($subscription);
                }
                $i++;
            }
        }
        else {
            throw new RestException(503, 'Error when retrieve subscription list : '.$db->lasterror());
        }
        if( ! count($obj_ret)) {
            throw new RestException(404, 'No Subscription found');
        }

        return $obj_ret;
    }

    /**
     * Create subscription object
     *
     * @param array $request_data   Request data
     * @return int  ID of subscription
     */
    public function post($request_data = null)
    {
        if(! DolibarrApiAccess::$user->rights->adherent->cotisation->creer) {
            throw new RestException(401);
        }
        // Check mandatory fields
        $result = $this->_validate($request_data);

        $subscription = new Subscription($this->db);
        foreach($request_data as $field => $value) {
            $subscription->$field = $value;
        }
        if ($subscription->create(DolibarrApiAccess::$user) < 0) {
            throw new RestException(500, 'Error when creating subscription', array_merge(array($subscription->error), $subscription->errors));
        }
        return $subscription->id;
    }

    /**
     * Update subscription
     *
     * @param int   $id             ID of subscription to update
     * @param array $request_data   Datas
     * @return int
     */
    public function put($id, $request_data = null)
    {
        if(! DolibarrApiAccess::$user->rights->adherent->creer) {
            throw new RestException(401);
        }

        $subscription = new Subscription($this->db);
        $result = $subscription->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'Subscription not found');
        }

        foreach($request_data as $field => $value) {
            if ($field == 'id') continue;
            $subscription->$field = $value;
        }

        if ($subscription->update(DolibarrApiAccess::$user) > 0)
        {
            return $this->get($id);
        }
        else
        {
        	throw new RestException(500, $subscription->error);
        }
    }

    /**
     * Delete subscription
     *
     * @param int $id   ID of subscription to delete
     * @return array
     */
    public function delete($id)
    {
        // The right to delete a subscription comes with the right to create one.
        if(! DolibarrApiAccess::$user->rights->adherent->cotisation->creer) {
            throw new RestException(401);
        }
        $subscription = new Subscription($this->db);
        $result = $subscription->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'Subscription not found');
        }

        if (! $subscription->delete(DolibarrApiAccess::$user)) {
            throw new RestException(401, 'error when deleting subscription');
        }

        return array(
            'success' => array(
                'code' => 200,
                'message' => 'subscription deleted'
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
            if (!isset($data[$field]))
                throw new RestException(400, "$field field missing");
            $subscription[$field] = $data[$field];
        }
        return $subscription;
    }
}
