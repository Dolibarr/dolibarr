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

 require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
 require_once DOL_DOCUMENT_ROOT.'/societe/class/client.class.php';

/**
 * API class for category object
 *
 * @smart-auto-routing false
 * @access protected 
 * @class  DolibarrApiAccess {@requires user,external}
 * 
 * @deprecated Use Categories instead (defined in api_categories.class.php)
 */
class CategoryApi extends DolibarrApi
{
    /**
     * @var array   $FIELDS     Mandatory fields, checked when create and update object 
     */
    static $FIELDS = array(
        'label',
        'type'
    );

    static $TYPES = array(
        0 => 'product',
        1 => 'supplier',
        2 => 'customer',
        3 => 'member',
        4 => 'contact',
        5 => 'account',
    );
    
    /**
     * @var Categorie $category {@type Categorie}
     */
    public $category;

    /**
     * Constructor <b>Warning: Deprecated</b>
     *
     * @url     GET category/
     * 
     */
    function __construct()
    {
		global $db, $conf;
		$this->db = $db;
        $this->category = new Categorie($this->db);
        
    }

    /**
     * Get properties of a category object <b>Warning: Deprecated</b>
     *
     * Return an array with category informations
     *
     * @param 	int 	$id ID of category
     * @return 	array|mixed data without useless information
	 * 
     * @url	GET category/{id}
     * @throws 	RestException
     */
    function get($id)
    {		
		if(! DolibarrApiAccess::$user->rights->categorie->lire) {
			throw new RestException(401);
		}
			
        $result = $this->category->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'category not found');
        }
		
		if( ! DolibarrApi::_checkAccessToResource('category',$this->category->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		return $this->_cleanObjectDatas($this->category);
    }

    /**
     * List categories <b>Warning: Deprecated</b>
     * 
     * Get a list of categories
     *
     * @param string	$type		Type of category ('member', 'customer', 'supplier', 'product', 'contact')
     * @param string	$sortfield	Sort field
     * @param string	$sortorder	Sort order
     * @param int		$limit		Limit for list
     * @param int		$page		Page number
     * @return array Array of category objects
     *
     * @url	GET /category/list
     */
    function getList($type='product', $sortfield = "s.rowid", $sortorder = 'ASC', $limit = 0, $page = 0) {
        global $db, $conf;
        
        $obj_ret = array();
        
         if(! DolibarrApiAccess::$user->rights->categorie->lire) {
			throw new RestException(401);
		}
        
        $sql = "SELECT s.rowid";
        $sql.= " FROM ".MAIN_DB_PREFIX."categorie as s";
        $sql.= ' WHERE s.entity IN ('.getEntity('category', 1).')';
        $sql.= ' AND s.type='.array_search($type,CategoryApi::$TYPES);

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
                $category_static = new Categorie($db);
                if($category_static->fetch($obj->rowid)) {
                    $obj_ret[] = $this->_cleanObjectDatas($category_static);
                }
                $i++;
            }
        }
        else {
            throw new RestException(503, 'Error when retrieve category list : '.$db->lasterror());
        }
        if( ! count($obj_ret)) {
            throw new RestException(404, 'No category found');
        }
		return $obj_ret;
    }
    /**
     * List categories of an entity <b>Warning: Deprecated</b>
     * 
     * Get a list of categories
     *
     * @param string	$type		Type of category ('member', 'customer', 'supplier', 'product', 'contact')
     * @param string	$sortfield	Sort field
     * @param string	$sortorder	Sort order
     * @param int		$limit		Limit for list
     * @param int		$page		Page number
     * @param int		$item		Id of the item to get categories for
     * @return array Array of category objects
     *
     * @url	GET /product/{item}/categories
     */
    function getListForItem($type='product', $sortfield = "s.rowid", $sortorder = 'ASC', $limit = 0, $page = 0, $item = 0) {
        global $db, $conf;
        
        $obj_ret = array();
        
         if(! DolibarrApiAccess::$user->rights->categorie->lire) {
			    throw new RestException(401);
         }
        //if ($type == "") {
          //$type="product";
        //}
        $sub_type = $type;
        $subcol_name = "fk_".$type;
        if ($type=="customer" || $type=="supplier") {
          $sub_type="societe";
          $subcol_name="fk_soc";
        }
        $sql = "SELECT s.rowid";
        $sql.= " FROM ".MAIN_DB_PREFIX."categorie as s";
        $sql.= " , ".MAIN_DB_PREFIX."categorie_".$sub_type." as sub ";
        $sql.= ' WHERE s.entity IN ('.getEntity('category', 1).')';
        $sql.= ' AND s.type='.array_search($type,CategoryApi::$TYPES);
        $sql.= ' AND s.rowid = sub.fk_categorie';
        $sql.= ' AND sub.'.$subcol_name.' = '.$item;

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
                $category_static = new Categorie($db);
                if($category_static->fetch($obj->rowid)) {
                    $obj_ret[] = $this->_cleanObjectDatas($category_static);
                }
                $i++;
            }
        }
        else {
            throw new RestException(503, 'Error when retrieve category list : '.$db->lasterror());
        }
        if( ! count($obj_ret)) {
            throw new RestException(404, 'No category found');
        }
		return $obj_ret;
    }
    
    /**
     * Get member categories list <b>Warning: Deprecated</b>
     * 
     * @param string	$sortfield	Sort field
     * @param string	$sortorder	Sort order
     * @param int		$limit		Limit for list
     * @param int		$page		Page number
     * @return mixed
     * 
     * @url GET /category/list/member
     */
    function getListCategoryMember($sortfield = "s.rowid", $sortorder = 'ASC', $limit = 0, $page = 0) {
        return $this->getList('member', $sortfield, $sortorder, $limit, $page);  
    }
    
    /**
     * Get customer categories list <b>Warning: Deprecated</b>
     * 
     * @param string	$sortfield	Sort field
     * @param string	$sortorder	Sort order
     * @param int		$limit		Limit for list
     * @param int		$page		Page number
     * 
     * @return mixed
     * 
     * @url GET /category/list/customer
     */
    function getListCategoryCustomer($sortfield = "s.rowid", $sortorder = 'ASC', $limit = 0, $page = 0) {
        return $this->getList('customer', $sortfield, $sortorder, $limit, $page);  
    }
    /**
     * Get categories for a customer <b>Warning: Deprecated</b>
     * 
     * @param int		$cusid  Customer id filter
     * @param string	$sortfield	Sort field
     * @param string	$sortorder	Sort order
     * @param int		$limit		Limit for list
     * @param int		$page		Page number
     * 
     * @return mixed
     * 
     * @url GET /customer/{cusid}/categories
     */
    function getListCustomerCategories($cusid, $sortfield = "s.rowid", $sortorder = 'ASC', $limit = 0, $page = 0) {
        return $this->getListForItem($sortfield, $sortorder, $limit, $page, 'customer', $cusid);  
    }

    /**
     * Add category to customer <b>Warning: Deprecated</b>
     * 
     * @param int		$cusid	Id of customer
     * @param int		$catid  Id of category
     * 
     * @return mixed
     * 
     * @url GET /customer/{cusid}/addCategory/{catid}
     */
    function addCustomerCategory($cusid,$catid) {
      if(! DolibarrApiAccess::$user->rights->societe->creer) {
			  throw new RestException(401);
      }
      $customer = new Client($this->db);
      $customer->fetch($cusid);
      if( ! $customer ) {
        throw new RestException(404, 'customer not found');
      }
      $result = $this->category->fetch($catid);
      if( ! $result ) {
        throw new RestException(404, 'category not found');
      }
      
      if( ! DolibarrApi::_checkAccessToResource('societe',$customer->id)) {
        throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
      }
      if( ! DolibarrApi::_checkAccessToResource('category',$this->category->id)) {
        throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
      }
      $this->category->add_type($customer,'customer');
      return $customer;
    }
    
    /**
     * Get supplier categories list <b>Warning: Deprecated</b>
     * 
     * @param string	$sortfield	Sort field
     * @param string	$sortorder	Sort order
     * @param int		$limit		Limit for list
     * @param int		$page		Page number
     * 
     * @return mixed
     * 
     * @url GET /category/list/supplier
     */
    function getListCategorySupplier($sortfield = "s.rowid", $sortorder = 'ASC', $limit = 0, $page = 0) {
        return $this->getList('supplier', $sortfield, $sortorder, $limit, $page);  
    }
    
    /**
     * Get product categories list <b>Warning: Deprecated</b>
     * 
     * @param string	$sortfield	Sort field
     * @param string	$sortorder	Sort order
     * @param int		$limit		Limit for list
     * @param int		$page		Page number
     * 
     * @return mixed
     * 
     * @url GET /category/list/product
     */
    function getListCategoryProduct($sortfield = "s.rowid", $sortorder = 'ASC', $limit = 0, $page = 0) {
        return $this->getList('product', $sortfield, $sortorder, $limit, $page);  
    }
    
    /**
     * Get contact categories list <b>Warning: Deprecated</b>
     * 
     * @param string	$sortfield	Sort field
     * @param string	$sortorder	Sort order
     * @param int		$limit		Limit for list
     * @param int		$page		Page number
     * @return mixed
     * 
     * @url GET /category/list/contact
     */
    function getListCategoryContact($sortfield = "s.rowid", $sortorder = 'ASC', $limit = 0, $page = 0) {
        return $this->getList('contact', $sortfield, $sortorder, $limit, $page);  
    }
    
    /**
     * Create category object <b>Warning: Deprecated</b>
     * 
     * @param array $request_data   Request data
     * @return int  ID of category
     *
     * @url	POST category/
     */
    function post($request_data = NULL)
    {
        if(! DolibarrApiAccess::$user->rights->categorie->creer) {
			throw new RestException(401);
		}
        // Check mandatory fields
        $result = $this->_validate($request_data);
        
        foreach($request_data as $field => $value) {
            $this->category->$field = $value;
        }
        if($this->category->create(DolibarrApiAccess::$user) < 0) {
            throw new RestException(500, 'Error when create category : '.$this->category->error);
        }
        return $this->category->id;
    }

    /**
     * Update category <b>Warning: Deprecated</b>
     * 
     * @param int   $id             Id of category to update
     * @param array $request_data   Datas   
     * @return int 
     *
     * @url	PUT category/{id}
     */
    function put($id, $request_data = NULL)
    {
        if(! DolibarrApiAccess::$user->rights->categorie->creer) {
			throw new RestException(401);
		}
        
        $result = $this->category->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'category not found');
        }
		
		if( ! DolibarrApi::_checkAccessToResource('category',$this->category->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

        foreach($request_data as $field => $value) {
            if ($field == 'id') continue;
            $this->category->$field = $value;
        }
        
        if($this->category->update(DolibarrApiAccess::$user))
            return $this->get ($id);
        
        return false;
    }
    
    /**
     * Delete category <b>Warning: Deprecated</b>
     *
     * @param int $id   Category ID
     * @return array
     * 
     * @url	DELETE category/{id}
     */
    function delete($id)
    {
        if(! DolibarrApiAccess::$user->rights->categorie->supprimer) {
			throw new RestException(401);
		}
        $result = $this->category->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'category not found');
        }
		
		if( ! DolibarrApi::_checkAccessToResource('category',$this->category->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}
        
        if (! $this->category->delete(DolibarrApiAccess::$user)) {
            throw new RestException(401,'error when delete category');
        }
        
        return array(
            'success' => array(
                'code' => 200,
                'message' => 'Category deleted'
            )
        );
    }
    
    /**
     * Validate fields before create or update object
     * 
     * @param array|null    $data   Data to validate
     * @return array
     * 
     * @throws RestException
     */
    function _validate($data)
    {
        $category = array();
        foreach (CategoryApi::$FIELDS as $field) {
            if (!isset($data[$field]))
                throw new RestException(400, "$field field missing");
            $category[$field] = $data[$field];
        }
        return $category;
    }
}
