<?php
/* Copyright (C) 2016	Xebax Christy	<xebax@wanadoo.fr>
 * Copyright (C) 2017	Regis Houssin	<regis.houssin@inodbox.com>
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

require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/subscription.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';

/**
 * API class for members
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class Members extends DolibarrApi
{
    /**
     * @var array   $FIELDS     Mandatory fields, checked when create and update object
     */
    static $FIELDS = array(
        'morphy',
        'typeid'
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
     * Get properties of a member object
     *
     * Return an array with member informations
     *
     * @param     int     $id ID of member
     * @return    array|mixed data without useless information
     *
     * @throws    RestException
     */
    public function get($id)
    {
        if(! DolibarrApiAccess::$user->rights->adherent->lire) {
            throw new RestException(401);
        }

        $member = new Adherent($this->db);
        $result = $member->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'member not found');
        }

        if( ! DolibarrApi::_checkAccessToResource('adherent', $member->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        return $this->_cleanObjectDatas($member);
    }

    /**
     * List members
     *
     * Get a list of members
     *
     * @param string    $sortfield  Sort field
     * @param string    $sortorder  Sort order
     * @param int       $limit      Limit for list
     * @param int       $page       Page number
     * @param string    $typeid     ID of the type of member
     * @param string    $sqlfilters Other criteria to filter answers separated by a comma.
     *                              Example: "(t.ref:like:'SO-%') and ((t.date_creation:<:'20160101') or (t.nature:is:NULL))"
     * @return array                Array of member objects
     *
     * @throws RestException
     */
    public function index($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $typeid = '', $sqlfilters = '')
    {
        global $db, $conf;

        $obj_ret = array();

        if(! DolibarrApiAccess::$user->rights->adherent->lire) {
            throw new RestException(401);
        }

        $sql = "SELECT t.rowid";
        $sql.= " FROM ".MAIN_DB_PREFIX."adherent as t";
        $sql.= ' WHERE t.entity IN ('.getEntity('adherent').')';
        if (!empty($typeid))
        {
            $sql.= ' AND t.fk_adherent_type='.$typeid;
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
            $min = min($num, ($limit <= 0 ? $num : $limit));
            while ($i < $min)
            {
            	$obj = $db->fetch_object($result);
                $member = new Adherent($this->db);
                if($member->fetch($obj->rowid)) {
                    $obj_ret[] = $this->_cleanObjectDatas($member);
                }
                $i++;
            }
        }
        else {
            throw new RestException(503, 'Error when retrieve member list : '.$db->lasterror());
        }
        if( ! count($obj_ret)) {
            throw new RestException(404, 'No member found');
        }

        return $obj_ret;
    }

    /**
     * Create member object
     *
     * @param array $request_data   Request data
     * @return int  ID of member
     */
    public function post($request_data = null)
    {
        if(! DolibarrApiAccess::$user->rights->adherent->creer) {
            throw new RestException(401);
        }
        // Check mandatory fields
        $result = $this->_validate($request_data);

        $member = new Adherent($this->db);
        foreach($request_data as $field => $value) {
            $member->$field = $value;
        }
        if ($member->create(DolibarrApiAccess::$user) < 0) {
            throw new RestException(500, 'Error creating member', array_merge(array($member->error), $member->errors));
        }
        return $member->id;
    }

    /**
     * Update member
     *
     * @param int   $id             ID of member to update
     * @param array $request_data   Datas
     * @return int
     */
    public function put($id, $request_data = null)
    {
        if(! DolibarrApiAccess::$user->rights->adherent->creer) {
            throw new RestException(401);
        }

        $member = new Adherent($this->db);
        $result = $member->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'member not found');
        }

        if( ! DolibarrApi::_checkAccessToResource('member', $member->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        foreach($request_data as $field => $value) {
            if ($field == 'id') continue;
            // Process the status separately because it must be updated using
            // the validate() and resiliate() methods of the class Adherent.
            if ($field == 'statut') {
                if ($value == '0') {
                    $result = $member->resiliate(DolibarrApiAccess::$user);
                    if ($result < 0) {
                        throw new RestException(500, 'Error when resiliating member: '.$member->error);
                    }
                } elseif ($value == '1') {
                    $result = $member->validate(DolibarrApiAccess::$user);
                    if ($result < 0) {
                        throw new RestException(500, 'Error when validating member: '.$member->error);
                    }
                }
            } else {
                $member->$field = $value;
            }
        }

        // If there is no error, update() returns the number of affected rows
        // so if the update is a no op, the return value is zero.
        if ($member->update(DolibarrApiAccess::$user) >= 0)
        {
            return $this->get($id);
        }
        else
        {
        	throw new RestException(500, $member->error);
        }
    }

    /**
     * Delete member
     *
     * @param int $id   member ID
     * @return array
     */
    public function delete($id)
    {
        if(! DolibarrApiAccess::$user->rights->adherent->supprimer) {
            throw new RestException(401);
        }
        $member = new Adherent($this->db);
        $result = $member->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'member not found');
        }

        if( ! DolibarrApi::_checkAccessToResource('member', $member->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        if (! $member->delete($member->id, DolibarrApiAccess::$user)) {
            throw new RestException(401, 'error when deleting member');
        }

        return array(
            'success' => array(
                'code' => 200,
                'message' => 'member deleted'
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
        $member = array();
        foreach (Members::$FIELDS as $field) {
            if (!isset($data[$field]))
                throw new RestException(400, "$field field missing");
            $member[$field] = $data[$field];
        }
        return $member;
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

        // Remove the subscriptions because they are handled as a subresource.
        unset($object->subscriptions);
        unset($object->fk_incoterms);
        unset($object->label_incoterms);
        unset($object->location_incoterms);
        unset($object->fk_delivery_address);
        unset($object->shipping_method_id);

        unset($object->total_ht);
        unset($object->total_ttc);
        unset($object->total_tva);
        unset($object->total_localtax1);
        unset($object->total_localtax2);

        return $object;
    }

    /**
     * List subscriptions of a member
     *
     * Get a list of subscriptions
     *
     * @param int $id ID of member
     * @return array Array of subscription objects
     *
     * @throws RestException
     *
     * @url GET {id}/subscriptions
     */
    public function getSubscriptions($id)
    {
        $obj_ret = array();

        if(! DolibarrApiAccess::$user->rights->adherent->cotisation->lire) {
            throw new RestException(401);
        }

        $member = new Adherent($this->db);
        $result = $member->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'member not found');
        }

        $obj_ret = array();
        foreach ($member->subscriptions as $subscription) {
            $obj_ret[] = $this->_cleanObjectDatas($subscription);
        }
        return $obj_ret;
    }

    /**
     * Add a subscription for a member
     *
     * @param int $id               ID of member
     * @param int $start_date       Start date {@from body} {@type timestamp}
     * @param int $end_date         End date {@from body} {@type timestamp}
     * @param float $amount         Amount (may be 0) {@from body}
     * @param string $label         Label {@from body}
     * @return int  ID of subscription
     *
     * @url POST {id}/subscriptions
     */
    public function createSubscription($id, $start_date, $end_date, $amount, $label = '')
    {
        if(! DolibarrApiAccess::$user->rights->adherent->cotisation->creer) {
            throw new RestException(401);
        }

        $member = new Adherent($this->db);
        $result = $member->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'member not found');
        }

        return $member->subscription($start_date, $amount, 0, '', $label, '', '', '', $end_date);
    }

    /**
     * Get categories for a member
     *
     * @param int		$id         ID of member
     * @param string		$sortfield	Sort field
     * @param string		$sortorder	Sort order
     * @param int		$limit		Limit for list
     * @param int		$page		Page number
     *
     * @return mixed
     *
     * @url GET {id}/categories
     */
	public function getCategories($id, $sortfield = "s.rowid", $sortorder = 'ASC', $limit = 0, $page = 0)
	{
		if (! DolibarrApiAccess::$user->rights->categorie->lire) {
			throw new RestException(401);
		}

		$categories = new Categorie($this->db);

		$result = $categories->getListForItem($id, 'member', $sortfield, $sortorder, $limit, $page);

		if (empty($result)) {
			throw new RestException(404, 'No category found');
		}

		if ($result < 0) {
			throw new RestException(503, 'Error when retrieve category list : '.$categories->error);
		}

		return $result;
	}
}
