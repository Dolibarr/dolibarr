<?php
/* Copyright (C) 2015   Jean-François Ferry     <jfefe@aternatik.fr>
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

 require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';

/**
 * API class for commande object
 *
 * @smart-auto-routing false
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 *
 * @category Api
 * @package  Api
 *
 * @deprecated Use Orders instead (defined in api_orders.class.php)
 */
class CommandeApi extends DolibarrApi
{

    /**
     * @var array   $FIELDS     Mandatory fields, checked when create and update object
     */
    static $FIELDS = array(
        'socid'
    );

    /**
     * @var Commande $commande {@type Commande}
     */
    public $commande;

    /**
     * Constructor <b>Warning: Deprecated</b>
     *
     * @url     GET order/
     *
     */
    function __construct()
    {
		global $db, $conf;
		$this->db = $db;
        $this->commande = new Commande($this->db);
    }

    /**
     * Get properties of a commande object <b>Warning: Deprecated</b>
     *
     * Return an array with commande informations
     *
     * @param       int         $id         ID of order
     * @param		string		$ref		Ref of object
     * @param		string		$ref_ext		External reference of object
     * @param		string		$ref_int		Internal reference of other object
     * @return 	array|mixed data without useless information
	 *
     * @url	GET order/{id}
     * @throws 	RestException
     */
    function get($id='',$ref='', $ref_ext='', $ref_int='')
    {
		if(! DolibarrApiAccess::$user->rights->commande->lire) {
			throw new RestException(401);
		}

        $result = $this->commande->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'Order not found');
        }

		if( ! DolibarrApi::_checkAccessToResource('commande',$this->commande->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

        $this->commande->fetchObjectLinked();
		return $this->_cleanObjectDatas($this->commande);
    }

    /**
     * List orders <b>Warning: Deprecated</b>
     *
     * Get a list of orders
     *
     * @param string	$sortfield	Sort field
     * @param string	$sortorder	Sort order
     * @param int		$limit		Limit for list
     * @param int		$page		Page number
     * @param int		$mode		Use this param to filter list
     * @param string	$societe	Thirdparty filter field
     *
     * @url     GET     /order/list
     * @return  array   Array of order objects
     */
    function getList($sortfield = "s.rowid", $sortorder = 'ASC', $limit = 0, $page = 0, $mode=0, $societe = 0) {
        global $db, $conf;

        $obj_ret = array();
        // case of external user, $societe param is ignored and replaced by user's socid
        $socid = DolibarrApiAccess::$user->societe_id ? DolibarrApiAccess::$user->societe_id : $societe;

        // If the internal user must only see his customers, force searching by him
        $search_sale = 0;
        if (! DolibarrApiAccess::$user->rights->societe->client->voir && !$socid) $search_sale = DolibarrApiAccess::$user->id;

        $sql = "SELECT s.rowid";
        if ((!DolibarrApiAccess::$user->rights->societe->client->voir && !$socid) || $search_sale > 0) $sql .= ", sc.fk_soc, sc.fk_user"; // We need these fields in order to filter by sale (including the case where the user can only see his prospects)
        $sql.= " FROM ".MAIN_DB_PREFIX."commande as s";

        if ((!DolibarrApiAccess::$user->rights->societe->client->voir && !$socid) || $search_sale > 0) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc"; // We need this table joined to the select in order to filter by sale

		// Example of use $mode
        //if ($mode == 1) $sql.= " AND s.client IN (1, 3)";
        //if ($mode == 2) $sql.= " AND s.client IN (2, 3)";

        $sql.= ' WHERE s.entity IN ('.getEntity('commande', 1).')';
        if ((!DolibarrApiAccess::$user->rights->societe->client->voir && !$socid) || $search_sale > 0) $sql.= " AND s.fk_soc = sc.fk_soc";
        if ($socid) $sql.= " AND s.fk_soc = ".$socid;
        if ($search_sale > 0) $sql.= " AND s.rowid = sc.fk_soc";		// Join for the needed table to filter by sale

        // Insert sale filter
        if ($search_sale > 0)
        {
            $sql .= " AND sc.fk_user = ".$search_sale;
        }

        $nbtotalofrecords = '';
        if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
        {
            $result = $db->query($sql);
            $nbtotalofrecords = $db->num_rows($result);
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

        $result = $db->query($sql);

        if ($result)
        {
        	$i=0;
            $num = $db->num_rows($result);
            while ($i < min($num, ($limit <= 0 ? $num : $limit)))
            {
                $obj = $db->fetch_object($result);
                $commande_static = new Commande($db);
                if($commande_static->fetch($obj->rowid)) {
                    $obj_ret[] = $this->_cleanObjectDatas($commande_static);
                }
                $i++;
            }
        }
        else {
            throw new RestException(503, 'Error when retrieve commande list : '.$db->lasterror());
        }
        if( ! count($obj_ret)) {
            throw new RestException(404, 'No commande found');
        }
		return $obj_ret;
    }

    /**
     * List orders for specific thirdparty <b>Warning: Deprecated</b>
     *
     * Get a list of orders
     *
     * @param int	$socid Id of customer
     *
     * @url     GET     /customer/{socid}/order/list
     * @url     GET     /thirdparty/{socid}/order/list
     * @return  array   Array of order objects
     */
    function getListForSoc($socid = 0) {
      return $this->getList(0,"s.rowid","ASC",0,0,$socid);
    }


    /**
     * Create order object <b>Warning: Deprecated</b>
     *
     * @param   array   $request_data   Request datas
     *
     * @url     POST    order/
     *
     * @return  int     ID of commande
     */
    function post($request_data = NULL)
    {
      if(! DolibarrApiAccess::$user->rights->commande->creer) {
			  throw new RestException(401, "Insuffisant rights");
		  }
        // Check mandatory fields
        $result = $this->_validate($request_data);

        foreach($request_data as $field => $value) {
            $this->commande->$field = $value;
        }
        if (isset($request_data["lines"])) {
          $lines = array();
          foreach ($request_data["lines"] as $line) {
            array_push($lines, (object) $line);
          }
          $this->commande->lines = $lines;
        }
        if(! $this->commande->create(DolibarrApiAccess::$user) ) {
            throw new RestException(500, "Error while creating order");
        }

        return $this->commande->id;
    }
    /**
     * Get lines of an order <b>Warning: Deprecated</b>
     *
     *
     * @param int   $id             Id of order
     *
     * @url	GET order/{id}/line/list
     *
     * @return int
     */
    function getLines($id) {
      if(! DolibarrApiAccess::$user->rights->commande->lire) {
		  	throw new RestException(401);
		  }

      $result = $this->commande->fetch($id);
      if( ! $result ) {
         throw new RestException(404, 'Commande not found');
      }

		  if( ! DolibarrApi::_checkAccessToResource('commande',$this->commande->id)) {
			  throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
      }
      $this->commande->getLinesArray();
      $result = array();
      foreach ($this->commande->lines as $line) {
        array_push($result,$this->_cleanObjectDatas($line));
      }
      return $result;
    }
    /**
     * Add a line to given order <b>Warning: Deprecated</b>
     *
     *
     * @param int   $id             Id of commande to update
     * @param array $request_data   Orderline data
     *
     * @url	POST order/{id}/line
     *
     * @return int
     */
    function postLine($id, $request_data = NULL) {
      if(! DolibarrApiAccess::$user->rights->commande->creer) {
		  	throw new RestException(401);
		  }

      $result = $this->commande->fetch($id);
      if( ! $result ) {
         throw new RestException(404, 'Commande not found');
      }

		  if( ! DolibarrApi::_checkAccessToResource('commande',$this->commande->id)) {
			  throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
      }
			$request_data = (object) $request_data;
      $updateRes = $this->commande->addline(
                        $request_data->desc,
                        $request_data->subprice,
                        $request_data->qty,
                        $request_data->tva_tx,
                        $request_data->localtax1_tx,
                        $request_data->localtax2_tx,
                        $request_data->fk_product,
                        $request_data->remise_percent,
                        $request_data->info_bits,
                        $request_data->fk_remise_except,
                        'HT',
                        0,
                        $request_data->date_start,
                        $request_data->date_end,
                        $request_data->product_type,
                        $request_data->rang,
                        $request_data->special_code,
                        $fk_parent_line,
                        $request_data->fk_fournprice,
                        $request_data->pa_ht,
                        $request_data->label,
                        $request_data->array_options,
                        $request_data->fk_unit,
                        $this->element,
                        $request_data->id
      );

      if ($updateRes > 0) {
        return $this->get($id)->line->rowid;

      }
      return false;
    }
    /**
     * Update a line to given order <b>Warning: Deprecated</b>
     *
     *
     * @param int   $id             Id of commande to update
     * @param int   $lineid         Id of line to update
     * @param array $request_data   Orderline data
     *
     * @url	PUT order/{id}/line/{lineid}
     *
     * @return object
     */
    function putLine($id, $lineid, $request_data = NULL) {
      if(! DolibarrApiAccess::$user->rights->commande->creer) {
		  	throw new RestException(401);
		  }

      $result = $this->commande->fetch($id);
      if( ! $result ) {
         throw new RestException(404, 'Commande not found');
      }

		  if( ! DolibarrApi::_checkAccessToResource('commande',$this->commande->id)) {
			  throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
      }
			$request_data = (object) $request_data;
      $updateRes = $this->commande->updateline(
                        $lineid,
                        $request_data->desc,
                        $request_data->subprice,
                        $request_data->qty,
                        $request_data->remise_percent,
                        $request_data->tva_tx,
                        $request_data->localtax1_tx,
                        $request_data->localtax2_tx,
                        'HT',
                        $request_data->info_bits,
                        $request_data->date_start,
                        $request_data->date_end,
                        $request_data->product_type,
                        $request_data->fk_parent_line,
                        0,
                        $request_data->fk_fournprice,
                        $request_data->pa_ht,
                        $request_data->label,
                        $request_data->special_code,
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
     * Delete a line to given order <b>Warning: Deprecated</b>
     *
     *
     * @param int   $id             Id of commande to update
     * @param int   $lineid         Id of line to delete
     *
     * @url	DELETE order/{id}/line/{lineid}
     *
     * @return int
     */
    function delLine($id, $lineid) {
      if(! DolibarrApiAccess::$user->rights->commande->creer) {
		  	throw new RestException(401);
		  }

      $result = $this->commande->fetch($id);
      if( ! $result ) {
         throw new RestException(404, 'Commande not found');
      }

		  if( ! DolibarrApi::_checkAccessToResource('commande',$this->commande->id)) {
			  throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
      }
			$request_data = (object) $request_data;
      $updateRes = $this->commande->deleteline(DolibarrApiAccess::$user,$lineid);
      if ($updateRes == 1) {
        return $this->get($id);
      }
      return false;
    }

    /**
     * Update order general fields (won't touch lines of order) <b>Warning: Deprecated</b>
     *
     * @param int   $id             Id of commande to update
     * @param array $request_data   Datas
     *
     * @url	PUT order/{id}
     *
     * @return int
     */
    function put($id, $request_data = NULL) {
      if(! DolibarrApiAccess::$user->rights->commande->creer) {
		  	throw new RestException(401);
		  }

        $result = $this->commande->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'Commande not found');
        }

		if( ! DolibarrApi::_checkAccessToResource('commande',$this->commande->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}
        foreach($request_data as $field => $value) {
            if ($field == 'id') continue;
            $this->commande->$field = $value;
        }

        if($this->commande->update($id, DolibarrApiAccess::$user,1,'','','update'))
            return $this->get($id);

        return false;
    }

    /**
     * Delete order <b>Warning: Deprecated</b>
     *
     * @param   int     $id         Order ID
     *
     * @url     DELETE  order/{id}
     *
     * @return  array
     */
    function delete($id)
    {
        if(! DolibarrApiAccess::$user->rights->commande->supprimer) {
			throw new RestException(401);
		}
        $result = $this->commande->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'Order not found');
        }

		if( ! DolibarrApi::_checkAccessToResource('commande',$this->commande->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

        if( ! $this->commande->delete(DolibarrApiAccess::$user)) {
            throw new RestException(500, 'Error when delete order : '.$this->commande->error);
        }

        return array(
            'success' => array(
                'code' => 200,
                'message' => 'Order deleted'
            )
        );

    }

    /**
     * Validate an order <b>Warning: Deprecated</b>
     *
     * @param   int $id             Order ID
     * @param   int $idwarehouse    Warehouse ID
     *
     * @url GET     order/{id}/validate
     * @url POST    order/{id}/validate
     *
     * @return  array
     *
     */
    function validOrder($id, $idwarehouse=0)
    {
        if(! DolibarrApiAccess::$user->rights->commande->creer) {
			throw new RestException(401);
		}
        $result = $this->commande->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'Order not found');
        }

		if( ! DolibarrApi::_checkAccessToResource('commande',$this->commande->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

        if( ! $this->commande->valid(DolibarrApiAccess::$user, $idwarehouse)) {
            throw new RestException(500, 'Error when validate order');
        }

        return array(
            'success' => array(
                'code' => 200,
                'message' => 'Order validated'
            )
        );
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
        $commande = array();
        foreach (CommandeApi::$FIELDS as $field) {
            if (!isset($data[$field]))
                throw new RestException(400, "$field field missing");
            $commande[$field] = $data[$field];

        }
        return $commande;
    }
}
