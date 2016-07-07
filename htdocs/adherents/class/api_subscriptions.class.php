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

require_once DOL_DOCUMENT_ROOT.'/adherents/class/cotisation.class.php';

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
        'amount'
    );

    /**
     * Constructor
     */
    function __construct()
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
    function get($id)
    {
        if(! DolibarrApiAccess::$user->rights->adherent->cotisation->lire) {
            throw new RestException(401);
        }

        $subscription = new Cotisation($this->db);
        $result = $subscription->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'subscription not found');
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
     * @return array Array of subscription objects
     *
     * @throws RestException
     */
    function index($sortfield = "dateadh", $sortorder = 'ASC', $limit = 0, $page = 0) {
        global $db, $conf;

        $obj_ret = array();

        if(! DolibarrApiAccess::$user->rights->adherent->cotisation->lire) {
            throw new RestException(401);
        }

        $sql = "SELECT rowid";
        $sql.= " FROM ".MAIN_DB_PREFIX."cotisation";

        $nbtotalofrecords = 0;
        if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
        {
            $result = $db->query($sql);
            $nbtotalofrecords = $db->num_rows($result);
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
            while ($i < $num)
            {
                $obj = $db->fetch_object($result);
                $subscription = new Cotisation($this->db);
                if($subscription->fetch($obj->rowid)) {
                    $obj_ret[] = $this->_cleanObjectDatas($subscription);
                }
                $i++;
            }
        }
        else {
            throw new RestException(503, 'Error when retrieve subscription list : '.$subscription->error);
        }
        if( ! count($obj_ret)) {
            throw new RestException(404, 'No subscription found');
        }

        return $obj_ret;
    }

    /**
     * Create subscription object
     *
     * @param array $request_data   Request data
     * @return int  ID of subscription
     */
    function post($request_data = null)
    {
        if(! DolibarrApiAccess::$user->rights->adherent->cotisation->creer) {
            throw new RestException(401);
        }
        // Check mandatory fields
        $result = $this->_validate($request_data);

        $subscription = new Cotisation($this->db);
        foreach($request_data as $field => $value) {
            $subscription->$field = $value;
        }
        if($subscription->create(DolibarrApiAccess::$user) < 0) {
            throw new RestException(503, 'Error when create subscription : '.$subscription->error);
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
    function patch($id, $request_data = null)
    {
        if(! DolibarrApiAccess::$user->rights->adherent->creer) {
            throw new RestException(401);
        }

        $subscription = new Cotisation($this->db);
        $result = $subscription->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'subscription not found');
        }

        foreach($request_data as $field => $value) {
            $subscription->$field = $value;
        }

        if($subscription->update(DolibarrApiAccess::$user) > 0)
            return $this->get($id);

        return false;
    }

    /**
     * Delete subscription
     *
     * @param int $id   ID of subscription to delete
     * @return array
     */
    function delete($id)
    {
        // The right to delete a subscription comes with the right to create one.
        if(! DolibarrApiAccess::$user->rights->adherent->cotisation->creer) {
            throw new RestException(401);
        }
        $subscription = new Cotisation($this->db);
        $result = $subscription->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'subscription not found');
        }

        if (! $subscription->delete(DolibarrApiAccess::$user)) {
            throw new RestException(401,'error when deleting subscription');
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
     * @param array $data   Data to validate
     * @return array
     *
     * @throws RestException
     */
    function _validate($data)
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
