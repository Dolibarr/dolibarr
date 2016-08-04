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
 
 require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
 require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';

/**
 * API class for products
 *
 * @access protected 
 * @class  DolibarrApiAccess {@requires user,external}
 */
class Products extends DolibarrApi
{
    /**
     * @var array   $FIELDS     Mandatory fields, checked when create and update object 
     */
    static $FIELDS = array(
        'ref',
        'label' 
    );

    /**
     * @var Product $product {@type Product}
     */
    public $product;

    /**
     * Constructor
     */
    function __construct()
    {
		global $db, $conf;
		$this->db = $db;
        $this->product = new Product($this->db);
    }

    /**
     * Get properties of a product object
     * 
     * Return an array with product informations
     *
     * @param 	int 	$id     ID of product
     * @return 	array|mixed data without useless information
	 * 
     * @throws 	RestException
     * TODO implement getting a product by ref or by $ref_ext
     */
    function get($id)
    {		
        if(! DolibarrApiAccess::$user->rights->produit->lire) {
			throw new RestException(401);
		}
			
        $result = $this->product->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'Product not found');
        }
		
		if( ! DolibarrApi::_checkAccessToResource('product',$this->product->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}
        
        $this->product->load_stock();

        return $this->_cleanObjectDatas($this->product);
    }

    /**
     * List products
     * 
     * Get a list of products
     * 
     * @param int		$mode		Use this param to filter list (0 for all, 1 for only product, 2 for only service)
     * @param int		$category	Use this param to filter list by category
     * @param mixed     $to_sell    Filter products to sell (1) or not to sell (0)  
     * @param mixed     $to_buy     Filter products to buy (1) or not to buy (0)  
     * @param string	$sortfield	Sort field
     * @param string	$sortorder	Sort order
     * @param int		$limit		Limit for list
     * @param int		$page		Page number
     *
     * @return array Array of product objects
     */
    function index($mode=0, $category=0, $to_sell='', $to_buy='', $sortfield = "p.ref", $sortorder = 'ASC', $limit = 0, $page = 0) {
        global $db, $conf;
        
        $obj_ret = array();
        
        $socid = DolibarrApiAccess::$user->societe_id ? DolibarrApiAccess::$user->societe_id : '';

        $sql = "SELECT rowid, ref, ref_ext";
        $sql.= " FROM ".MAIN_DB_PREFIX."product as p";
        if ($category > 0)
        {
            $sql.= ", ".MAIN_DB_PREFIX."categorie_product as c";
        }
        $sql.= ' WHERE p.entity IN ('.getEntity('product', 1).')';

        // Select products of given category
        if ($category > 0)
        {
            $sql.= " AND c.fk_categorie = ".$db->escape($category);
            $sql.= " AND c.fk_product = p.rowid ";
        }

        // Show products
        if ($mode == 1) $sql.= " AND p.fk_product_type = 0";
        // Show services
        if ($mode == 2) $sql.= " AND p.fk_product_type = 1";
        // Show product on sell
        if ($to_sell !== '') $sql.= " AND p.tosell = ".$db->escape($to_sell);
        // Show product on buy
        if ($to_buy !== '') $sql.= " AND p.tobuy = ".$db->escape($to_buy);

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
                $product_static = new Product($db);
                if($product_static->fetch($obj->rowid)) {
                    $obj_ret[] = parent::_cleanObjectDatas($product_static);
                }
                $i++;
            }
        }
        else {
            throw new RestException(503, 'Error when retrieve product list');
        }
        if( ! count($obj_ret)) {
            throw new RestException(404, 'No product found');
        }
        return $obj_ret;
    }
    
    /**
     * Create product object
     * 
     * @param   array   $request_data   Request data
     * @return  int     ID of product
     */
    function post($request_data = NULL)
    {
        if(! DolibarrApiAccess::$user->rights->produit->creer) {
			throw new RestException(401);
		}
        // Check mandatory fields
        $result = $this->_validate($request_data);
        
        foreach($request_data as $field => $value) {
            $this->product->$field = $value;
        }
        $result = $this->product->create(DolibarrApiAccess::$user);
        if($result < 0) {
            throw new RestException(503,'Error when creating product : '.$this->product->error);
        }
        
        return $this->product->id;
    }

    /**
     * Update product
     * 
     * @param int   $id             Id of product to update
     * @param array $request_data   Datas   
     * @return int 
     */
    function put($id, $request_data = NULL)
    {
        if(! DolibarrApiAccess::$user->rights->produit->creer) {
			throw new RestException(401);
		}
        
        $result = $this->product->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'Product not found');
        }
		
		if( ! DolibarrApi::_checkAccessToResource('product',$this->product->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

        foreach($request_data as $field => $value) {
            $this->product->$field = $value;
        }
        
        if($this->product->update($id, DolibarrApiAccess::$user,1,'update'))
            return $this->get ($id);
        
        return false;
    }
    
    /**
     * Delete product
     * 
     * @param   int     $id   Product ID
     * @return  array
     */
    function delete($id)
    {
        if(! DolibarrApiAccess::$user->rights->produit->supprimer) {
			throw new RestException(401);
		}
        $result = $this->product->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'Product not found');
        }
		
		if( ! DolibarrApi::_checkAccessToResource('product',$this->product->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}
        
        // The Product::delete() method uses the global variable $user.
        global $user;
        $user = DolibarrApiAccess::$user;

        return $this->product->delete($id);
    }
    
    /**
     * Get categories for a product
     *
     * @param int		$id         ID of product
     * @param string	$sortfield	Sort field
     * @param string	$sortorder	Sort order
     * @param int		$limit		Limit for list
     * @param int		$page		Page number
     *
     * @return mixed
     *
     * @url GET {id}/categories
     */
    function getCategories($id, $sortfield = "s.rowid", $sortorder = 'ASC', $limit = 0, $page = 0) {
        $categories = new Categories();
        return $categories->getListForItem('product', $sortfield, $sortorder, $limit, $page, $id);
    }

    /**
     * Validate fields before create or update object
     * 
     * @param array $data   Datas to validate
     * @return array
     * @throws RestException
     */
    function _validate($data)
    {
        $product = array();
        foreach (Products::$FIELDS as $field) {
            if (!isset($data[$field]))
                throw new RestException(400, "$field field missing");
            $product[$field] = $data[$field];
        }
        return $product;
    }
}
