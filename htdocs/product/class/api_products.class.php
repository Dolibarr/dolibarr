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
     * Return an array with product information.
     * TODO implement getting a product by ref or by $ref_ext
     *
     * @param 	int 	$id     			ID of product
     * @param	int		$includestockdata	Load also information about stock (slower)
     * @return 	array|mixed 				Data without useless information
	 *
     * @throws 401
     * @throws 403
     * @throws 404
     */
    function get($id, $includestockdata=0)
    {
        if(! DolibarrApiAccess::$user->rights->produit->lire) {
			throw new RestException(403);
		}

        $result = $this->product->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'Product not found');
        }

		if( ! DolibarrApi::_checkAccessToResource('product',$this->product->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		if ($includestockdata)
		{
        	$this->product->load_stock();
		}

        return $this->_cleanObjectDatas($this->product);
    }

    /**
     * List products
     *
     * Get a list of products
     *
     * @param string	$sortfield	Sort field
     * @param string	$sortorder	Sort order
     * @param int		$limit		Limit for list
     * @param int		$page		Page number
     * @param int		$mode		Use this param to filter list (0 for all, 1 for only product, 2 for only service)
     * @param int		$category	Use this param to filter list by category
     * @param string    $sqlfilters Other criteria to filter answers separated by a comma. Syntax example "(t.tobuy:=:0) and (t.tosell:=:1)"
     * @return array                Array of product objects
     */
    function index($sortfield = "t.ref", $sortorder = 'ASC', $limit = 100, $page = 0, $mode=0, $category=0, $sqlfilters = '') {
        global $db, $conf;

        $obj_ret = array();

        $socid = DolibarrApiAccess::$user->societe_id ? DolibarrApiAccess::$user->societe_id : '';

        $sql = "SELECT t.rowid, t.ref, t.ref_ext";
        $sql.= " FROM ".MAIN_DB_PREFIX."product as t";
        if ($category > 0)
        {
            $sql.= ", ".MAIN_DB_PREFIX."categorie_product as c";
        }
        $sql.= ' WHERE t.entity IN ('.getEntity('product').')';
        // Select products of given category
        if ($category > 0)
        {
            $sql.= " AND c.fk_categorie = ".$db->escape($category);
            $sql.= " AND c.fk_product = t.rowid ";
        }
        // Show products
        if ($mode == 1) $sql.= " AND t.fk_product_type = 0";
        // Show services
        if ($mode == 2) $sql.= " AND t.fk_product_type = 1";
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
            $min = min($num, ($limit <= 0 ? $num : $limit));
            while ($i < $min)
            {
                $obj = $db->fetch_object($result);
                $product_static = new Product($db);
                if($product_static->fetch($obj->rowid)) {
                    $obj_ret[] = $this->_cleanObjectDatas($product_static);
                }
                $i++;
            }
        }
        else {
            throw new RestException(503, 'Error when retrieve product list : '.$db->lasterror());
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
    function post($request_data = null)
    {
        if(! DolibarrApiAccess::$user->rights->produit->creer) {
			throw new RestException(401);
		}
        // Check mandatory fields
        $result = $this->_validate($request_data);

        foreach($request_data as $field => $value) {
            $this->product->$field = $value;
        }
        if ($this->product->create(DolibarrApiAccess::$user) < 0) {
            throw new RestException(500, "Error creating product", array_merge(array($this->product->error), $this->product->errors));
        }

        return $this->product->id;
    }

    /**
     * Update product.
     * Price will be updated by this API only if option is set on "One price per product". See other APIs for other price modes.
     *
     * @param int   $id             Id of product to update
     * @param array $request_data   Datas
     * @return int
     *
     * @throws RestException
     * @throws 401
     * @throws 404
     */
    function put($id, $request_data = null)
    {
    	global $conf;

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

		$oldproduct = dol_clone($this->product, 0);

        foreach($request_data as $field => $value) {
            if ($field == 'id') continue;
            $this->product->$field = $value;
        }

        $result = $this->product->update($id, DolibarrApiAccess::$user, 1, 'update');

        // If price mode is 1 price per product
        if ($result > 0 && ! empty($conf->global->PRODUCT_PRICE_UNIQ))
        {
        	// We update price only if it was changed
        	$pricemodified = false;
        	if ($this->product->price_base_type != $oldproduct->price_base_type) $pricemodified = true;
        	else
        	{
        		if ($this->product->tva_tx != $oldproduct->tva_tx) $pricemodified = true;
        		if ($this->product->tva_npr != $oldproduct->tva_npr) $pricemodified = true;
        		if ($this->product->default_vat_code != $oldproduct->default_vat_code) $pricemodified = true;

        		if ($this->product->price_base_type == 'TTC')
	        	{
	        		if ($this->product->price_ttc != $oldproduct->price_ttc) $pricemodified = true;
	        		if ($this->product->price_min_ttc != $oldproduct->price_min_ttc) $pricemodified = true;
	        	}
	        	else
	        	{
	        		if ($this->product->price != $oldproduct->price) $pricemodified = true;
	        		if ($this->product->price_min != $oldproduct->price_min) $pricemodified = true;
		      	}
        	}

        	if ($pricemodified)
        	{
        		$newvat = $this->product->tva_tx;
        		$newnpr = $this->product->tva_npr;
        		$newvatsrccode = $this->product->default_vat_code;

        		$newprice = $this->product->price;
        		$newpricemin = $this->product->price_min;
        		if ($this->product->price_base_type == 'TTC')
        		{
        			$newprice = $this->product->price_ttc;
        			$newpricemin = $this->product->price_min_ttc;
        		}

        		$result = $this->product->updatePrice($newprice, $this->product->price_base_type, DolibarrApiAccess::$user, $newvat, $newpricemin, 0, $newnpr, 0, 0, array(), $newvatsrccode);
        	}
        }

        if ($result <= 0)
        {
			throw new RestException(500, "Error updating product", array_merge(array($this->product->error), $this->product->errors));
		}

		return $this->get($id);
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

        return $this->product->delete(DolibarrApiAccess::$user);
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
	function getCategories($id, $sortfield = "s.rowid", $sortorder = 'ASC', $limit = 0, $page = 0)
	{
		if (! DolibarrApiAccess::$user->rights->categorie->lire) {
			throw new RestException(401);
		}

		$categories = new Categorie($this->db);

		$result = $categories->getListForItem($id, 'product', $sortfield, $sortorder, $limit, $page);

		if (empty($result)) {
			throw new RestException(404, 'No category found');
		}

		if ($result < 0) {
			throw new RestException(503, 'Error when retrieve category list : '.array_merge(array($categories->error), $categories->errors));  
		}

		return $result;
    }

    /**
     * Get prices per segment for a product
     *
     * @param int		$id         ID of product
     *
     * @return mixed
     *
     * @url GET {id}/selling_multiprices/per_segment
     */
    function getCustomerPricesPerSegment($id)
    {
    	global $conf;

    	if (! DolibarrApiAccess::$user->rights->produit->lire) {
    		throw new RestException(401);
    	}

    	if (empty($conf->global->PRODUIT_MULTIPRICES))
    	{
    		throw new RestException(400, 'API not available: this mode of pricing is not enabled by setup');
    	}

    	$result = $this->product->fetch($id);
    	if ( ! $result ) {
    		throw new RestException(404, 'Product not found');
    	}

    	if ($result < 0) {
    		throw new RestException(503, 'Error when retrieve prices list : '.array_merge(array($this->product->error), $this->product->errors));
    	}

    	return array(
    	'multiprices'=>$this->product->multiprices,
    	'multiprices_inc_tax'=>$this->product->multiprices_ttc,
    	'multiprices_min'=>$this->product->multiprices_min,
    	'multiprices_min_inc_tax'=>$this->product->multiprices_min_ttc,
    	'multiprices_vat'=>$this->product->multiprices_tva_tx,
    	'multiprices_base_type'=>$this->product->multiprices_base_type,
    	//'multiprices_default_vat_code'=>$this->product->multiprices_default_vat_code
    	);
    }

    /**
     * Get prices per customer for a product
     *
     * @param int		$id         ID of product
     *
     * @return mixed
     *
     * @url GET {id}/selling_multiprices/per_customer
     */
    function getCustomerPricesPerCustomer($id)
    {
    	global $conf;

    	if (! DolibarrApiAccess::$user->rights->produit->lire) {
    		throw new RestException(401);
    	}

    	if (empty($conf->global->PRODUIT_CUSTOMER_PRICES))
    	{
    		throw new RestException(400, 'API not available: this mode of pricing is not enabled by setup');
    	}

    	$result = $this->product->fetch($id);
    	if ( ! $result ) {
    		throw new RestException(404, 'Product not found');
    	}

    	if ($result < 0) {
    		throw new RestException(503, 'Error when retrieve prices list : '.array_merge(array($this->product->error), $this->product->errors));
    	}

    	throw new RestException(501, 'Feature not yet available');
    	//return $result;
    }

    /**
     * Get prices per quantity for a product
     *
     * @param int		$id         ID of product
     *
     * @return mixed
     *
     * @url GET {id}/selling_multiprices/per_quantity
     */
    function getCustomerPricesPerQuantity($id)
    {
    	global $conf;

    	if (! DolibarrApiAccess::$user->rights->produit->lire) {
    		throw new RestException(401);
    	}

    	if (empty($conf->global->PRODUIT_CUSTOMER_PRICES_BY_QTY))
    	{
    		throw new RestException(400, 'API not available: this mode of pricing is not enabled by setup');
    	}

    	$result = $this->product->fetch($id);
    	if ( ! $result ) {
    		throw new RestException(404, 'Product not found');
    	}

    	if ($result < 0) {
    		throw new RestException(503, 'Error when retrieve prices list : '.array_merge(array($this->product->error), $this->product->errors));
    	}

    	return array(
    		'prices_by_qty'=>$this->product->prices_by_qty[0],				// 1 if price by quantity was activated for the product
    		'prices_by_qty_list'=>$this->product->prices_by_qty_list[0]
    	);
    }


    /**
     * Clean sensible object datas
     *
     * @param   object  $object    Object to clean
     * @return    array    Array of cleaned object properties
     */
    function _cleanObjectDatas($object) {

        $object = parent::_cleanObjectDatas($object);

        unset($object->regeximgext);
        unset($object->price_by_qty);
        unset($object->prices_by_qty_id);
        unset($object->libelle);
        unset($object->product_id_already_linked);

        unset($object->name);
        unset($object->firstname);
        unset($object->lastname);
        unset($object->civility_id);

        unset($object->recuperableonly);

        return $object;
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
