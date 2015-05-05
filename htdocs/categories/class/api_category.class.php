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

/**
 * API class for category object
 *
 * @smart-auto-routing false
 * @access protected 
 * @class  DolibarrApiAccess {@requires user,external}
 * 
 *
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
    );
    
    /**
     * @var Categorie $category {@type Categorie}
     */
    public $category;

    /**
     * Constructor
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
     * Get properties of a category object
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
     * List categories
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
        $sql.= ' WHERE s.entity IN ('.getEntity('categorie', 1).')';
        $sql.= ' AND s.type='.array_search($type,CategoryApi::$TYPES);

        $nbtotalofrecords = 0;
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
            $num = $db->num_rows($result);
            while ($i < $num)
            {
                $obj = $db->fetch_object($result);
                $category_static = new Categorie($db);
                if($category_static->fetch($obj->rowid)) {
                    $obj_ret[] = parent::_cleanObjectDatas($category_static);
                }
                $i++;
            }
        }
        else {
            throw new RestException(503, 'Error when retrieve category list : '.$category_static->error);
        }
        if( ! count($obj_ret)) {
            throw new RestException(404, 'No category found');
        }
		return $obj_ret;
    }
    
    /**
     * Get member categories list
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
     * Get customer categories list
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
     * Get supplier categories list
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
     * Get product categories list
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
     * Get contact categories list
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
     * Create category object
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
            throw new RestException(503, 'Error when create category : '.$this->category->error);
        }
        return $this->category->id;
    }

    /**
     * Update category
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
            $this->category->$field = $value;
        }
        
        if($this->category->update(DolibarrApiAccess::$user))
            return $this->get ($id);
        
        return false;
    }
    
    /**
     * Delete category
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
     * @param array $data   Data to validate
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
