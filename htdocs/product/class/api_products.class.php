<?php
/* Copyright (C) 2015   Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2019   Cedric Ancelin          <icedo.anc@gmail.com>
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

require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT.'/variants/class/ProductAttribute.class.php';
require_once DOL_DOCUMENT_ROOT.'/variants/class/ProductAttributeValue.class.php';
require_once DOL_DOCUMENT_ROOT.'/variants/class/ProductCombination.class.php';
require_once DOL_DOCUMENT_ROOT.'/variants/class/ProductCombination2ValuePair.class.php';

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
     * @var ProductFournisseur $productsupplier {@type ProductFournisseur}
     */
    public $productsupplier;

    /**
     * Constructor
     */
    public function __construct()
    {
        global $db, $conf;
        $this->db = $db;
        $this->product = new Product($this->db);
        $this->productsupplier = new ProductFournisseur($this->db);
    }

    /**
     * Get properties of a product object by id
     *
     * Return an array with product information.
     *
     * @param  int    $id                  ID of product
     * @param  int    $includestockdata    Load also information about stock (slower)
     * @param  bool   $includesubproducts  Load information about subproducts
     * @return array|mixed                 Data without useless information
     *
     * @throws 401
     * @throws 403
     * @throws 404
     */
    public function get($id, $includestockdata = 0, $includesubproducts = false)
    {
        return $this->_fetch($id, '', '', '', $includestockdata, $includesubproducts);
    }

    /**
     * Get properties of a product object by ref
     *
     * Return an array with product information.
     *
     * @param  string $ref                Ref of element
     * @param  int    $includestockdata   Load also information about stock (slower)
     * @param  bool   $includesubproducts Load information about subproducts
     *
     * @return array|mixed                 Data without useless information
     *
     * @url GET ref/{ref}
     *
     * @throws 401
     * @throws 403
     * @throws 404
     */
    public function getByRef($ref, $includestockdata = 0, $includesubproducts = false)
    {
        return $this->_fetch('', $ref, '', '', $includestockdata, $includesubproducts);
    }

    /**
     * Get properties of a product object by ref_ext
     *
     * Return an array with product information.
     *
     * @param  string $ref_ext            Ref_ext of element
     * @param  int    $includestockdata   Load also information about stock (slower)
     * @param  bool   $includesubproducts Load information about subproducts
     *
     * @return array|mixed Data without useless information
     *
     * @url GET ref_ext/{ref_ext}
     *
     * @throws 401
     * @throws 403
     * @throws 404
     */
    public function getByRefExt($ref_ext, $includestockdata = 0, $includesubproducts = false)
    {
        return $this->_fetch('', '', $ref_ext, '', $includestockdata, $includesubproducts);
    }

    /**
     * Get properties of a product object by barcode
     *
     * Return an array with product information.
     *
     * @param  string $barcode            Barcode of element
     * @param  int    $includestockdata   Load also information about stock (slower)
     * @param  bool   $includesubproducts Load information about subproducts
     *
     * @return array|mixed Data without useless information
     *
     * @url GET barcode/{barcode}
     *
     * @throws 401
     * @throws 403
     * @throws 404
     */
    public function getByBarcode($barcode, $includestockdata = 0, $includesubproducts = false)
    {
        return $this->_fetch('', '', '', $barcode, $includestockdata, $includesubproducts);
    }

    /**
     * List products
     *
     * Get a list of products
     *
     * @param  string $sortfield  Sort field
     * @param  string $sortorder  Sort order
     * @param  int    $limit      Limit for list
     * @param  int    $page       Page number
     * @param  int    $mode       Use this param to filter list (0 for all, 1 for only product, 2 for only service)
     * @param  int    $category   Use this param to filter list by category
     * @param  string $sqlfilters Other criteria to filter answers separated by a comma. Syntax example "(t.tobuy:=:0) and (t.tosell:=:1)"
     * @return array                Array of product objects
     */
    public function index($sortfield = "t.ref", $sortorder = 'ASC', $limit = 100, $page = 0, $mode = 0, $category = 0, $sqlfilters = '')
    {
        global $db, $conf;

        $obj_ret = array();

        $socid = DolibarrApiAccess::$user->socid ? DolibarrApiAccess::$user->socid : '';

        $sql = "SELECT t.rowid, t.ref, t.ref_ext";
        $sql .= " FROM ".MAIN_DB_PREFIX."product as t";
        if ($category > 0) {
            $sql .= ", ".MAIN_DB_PREFIX."categorie_product as c";
        }
        $sql .= ' WHERE t.entity IN ('.getEntity('product').')';
        // Select products of given category
        if ($category > 0) {
            $sql .= " AND c.fk_categorie = ".$db->escape($category);
            $sql .= " AND c.fk_product = t.rowid ";
        }
        if ($mode == 1) {
            // Show only products
            $sql .= " AND t.fk_product_type = 0";
        } elseif ($mode == 2) {
            // Show only services
            $sql .= " AND t.fk_product_type = 1";
        }
        // Add sql filters
        if ($sqlfilters) {
            if (!DolibarrApi::_checkFilters($sqlfilters)) {
                throw new RestException(503, 'Error when validating parameter sqlfilters '.$sqlfilters);
            }
            $regexstring = '\(([^:\'\(\)]+:[^:\'\(\)]+:[^:\(\)]+)\)';
            $sql .= " AND (".preg_replace_callback('/'.$regexstring.'/', 'DolibarrApi::_forge_criteria_callback', $sqlfilters).")";
        }

        $sql .= $db->order($sortfield, $sortorder);
        if ($limit) {
            if ($page < 0) {
                $page = 0;
            }
            $offset = $limit * $page;

            $sql .= $db->plimit($limit + 1, $offset);
        }

        $result = $db->query($sql);
        if ($result) {
            $num = $db->num_rows($result);
            $min = min($num, ($limit <= 0 ? $num : $limit));
            $i = 0;
            while ($i < $min)
            {
                $obj = $db->fetch_object($result);
                $product_static = new Product($db);
                if ($product_static->fetch($obj->rowid)) {
                    $obj_ret[] = $this->_cleanObjectDatas($product_static);
                }
                $i++;
            }
        }
        else {
            throw new RestException(503, 'Error when retrieve product list : '.$db->lasterror());
        }
        if (!count($obj_ret)) {
            throw new RestException(404, 'No product found');
        }
        return $obj_ret;
    }

    /**
     * Create product object
     *
     * @param  array $request_data Request data
     * @return int     ID of product
     */
    public function post($request_data = null)
    {
        if (!DolibarrApiAccess::$user->rights->produit->creer) {
            throw new RestException(401);
        }
        // Check mandatory fields
        $result = $this->_validate($request_data);

        foreach ($request_data as $field => $value) {
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
     * @param  int   $id           Id of product to update
     * @param  array $request_data Datas
     * @return int
     *
     * @throws RestException
     * @throws 401
     * @throws 404
     */
    public function put($id, $request_data = null)
    {
        global $conf;

        if (!DolibarrApiAccess::$user->rights->produit->creer) {
            throw new RestException(401);
        }

        $result = $this->product->fetch($id);
        if (!$result) {
            throw new RestException(404, 'Product not found');
        }

        if (!DolibarrApi::_checkAccessToResource('product', $this->product->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $oldproduct = dol_clone($this->product, 0);

        foreach ($request_data as $field => $value) {
            if ($field == 'id') { continue;
            }
            $this->product->$field = $value;
        }

        $updatetype = false;
        if ($this->product->type != $oldproduct->type && ($this->product->isProduct() || $this->product->isService())) {
            $updatetype = true;
        }

        $result = $this->product->update($id, DolibarrApiAccess::$user, 1, 'update', $updatetype);

        // If price mode is 1 price per product
        if ($result > 0 && !empty($conf->global->PRODUCT_PRICE_UNIQ)) {
            // We update price only if it was changed
            $pricemodified = false;
            if ($this->product->price_base_type != $oldproduct->price_base_type) { $pricemodified = true;
            } else
            {
                if ($this->product->tva_tx != $oldproduct->tva_tx) { $pricemodified = true;
                }
                if ($this->product->tva_npr != $oldproduct->tva_npr) { $pricemodified = true;
                }
                if ($this->product->default_vat_code != $oldproduct->default_vat_code) { $pricemodified = true;
                }

                if ($this->product->price_base_type == 'TTC') {
                    if ($this->product->price_ttc != $oldproduct->price_ttc) { $pricemodified = true;
                    }
                    if ($this->product->price_min_ttc != $oldproduct->price_min_ttc) { $pricemodified = true;
                    }
                }
                else
                {
                    if ($this->product->price != $oldproduct->price) { $pricemodified = true;
                    }
                    if ($this->product->price_min != $oldproduct->price_min) { $pricemodified = true;
                    }
                }
            }

            if ($pricemodified) {
                $newvat = $this->product->tva_tx;
                $newnpr = $this->product->tva_npr;
                $newvatsrccode = $this->product->default_vat_code;

                $newprice = $this->product->price;
                $newpricemin = $this->product->price_min;
                if ($this->product->price_base_type == 'TTC') {
                    $newprice = $this->product->price_ttc;
                    $newpricemin = $this->product->price_min_ttc;
                }

                $result = $this->product->updatePrice($newprice, $this->product->price_base_type, DolibarrApiAccess::$user, $newvat, $newpricemin, 0, $newnpr, 0, 0, array(), $newvatsrccode);
            }
        }

        if ($result <= 0) {
            throw new RestException(500, "Error updating product", array_merge(array($this->product->error), $this->product->errors));
        }

        return $this->get($id);
    }

    /**
     * Delete product
     *
     * @param  int 		$id 		Product ID
     * @return array
     */
    public function delete($id)
    {
        if (!DolibarrApiAccess::$user->rights->produit->supprimer) {
            throw new RestException(401);
        }
        $result = $this->product->fetch($id);
        if (!$result) {
            throw new RestException(404, 'Product not found');
        }

        if (!DolibarrApi::_checkAccessToResource('product', $this->product->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        // The Product::delete() method uses the global variable $user.
        global $user;
        $user = DolibarrApiAccess::$user;

        return $this->product->delete(DolibarrApiAccess::$user);
    }

    /**
     * Get the list of subproducts of the product.
     *
     * @param  int $id      Id of parent product/service
     * @return array
     *
     * @throws RestException
     * @throws 401
     * @throws 404
     *
     * @url GET {id}/subproducts
     */
    public function getSubproducts($id)
    {
        if (!DolibarrApiAccess::$user->rights->produit->lire) {
            throw new RestException(401);
        }

        if (!DolibarrApi::_checkAccessToResource('product', $id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $childsArbo = $this->product->getChildsArbo($id, 1);

        $keys = ['rowid', 'qty', 'fk_product_type', 'label', 'incdec'];
        $childs = [];
        foreach ($childsArbo as $values) {
            $childs[] = array_combine($keys, $values);
        }

        return $childs;
    }

    /**
     * Add subproduct.
     *
     * Link a product/service to a parent product/service
     *
     * @param  int $id            Id of parent product/service
     * @param  int $subproduct_id Id of child product/service
     * @param  int $qty           Quantity
     * @param  int $incdec        1=Increase/decrease stock of child when parent stock increase/decrease
     * @return int
     *
     * @throws RestException
     * @throws 401
     * @throws 404
     *
     * @url POST {id}/subproducts/add
     */
    public function addSubproducts($id, $subproduct_id, $qty, $incdec = 1)
    {
        if (!DolibarrApiAccess::$user->rights->produit->creer) {
            throw new RestException(401);
        }

        if (!DolibarrApi::_checkAccessToResource('product', $id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $result = $this->product->add_sousproduit($id, $subproduct_id, $qty, $incdec);
        if ($result <= 0) {
            throw new RestException(500, "Error adding product child");
        }
        return $result;
    }

    /**
     * Remove subproduct.
     *
     *  Unlink a product/service from a parent product/service
     *
     * @param  int $id             Id of parent product/service
     * @param  int $subproduct_id  Id of child product/service
     * @return int
     *
     * @throws RestException
     * @throws 401
     * @throws 404
     *
     * @url DELETE {id}/subproducts/remove
     */
    public function delSubproducts($id, $subproduct_id)
    {
        if (!DolibarrApiAccess::$user->rights->produit->creer) {
            throw new RestException(401);
        }

        if (!DolibarrApi::_checkAccessToResource('product', $id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $result = $this->product->del_sousproduit($id, $subproduct_id);
        if ($result <= 0) {
            throw new RestException(500, "Error while removing product child");
        }
        return $result;
    }


    /**
     * Get categories for a product
     *
     * @param int    $id        ID of product
     * @param string $sortfield Sort field
     * @param string $sortorder Sort order
     * @param int    $limit     Limit for list
     * @param int    $page      Page number
     *
     * @return mixed
     *
     * @url GET {id}/categories
     */
    public function getCategories($id, $sortfield = "s.rowid", $sortorder = 'ASC', $limit = 0, $page = 0)
    {
        if (!DolibarrApiAccess::$user->rights->categorie->lire) {
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
     * @param int $id ID of product
     *
     * @return mixed
     *
     * @url GET {id}/selling_multiprices/per_segment
     */
    public function getCustomerPricesPerSegment($id)
    {
        global $conf;

        if (!DolibarrApiAccess::$user->rights->produit->lire) {
            throw new RestException(401);
        }

        if (empty($conf->global->PRODUIT_MULTIPRICES)) {
            throw new RestException(400, 'API not available: this mode of pricing is not enabled by setup');
        }

        $result = $this->product->fetch($id);
        if (!$result) {
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
     * @param int $id ID of product
     * @param string   	$thirdparty_id	  Thirdparty id to filter orders of (example '1') {@pattern /^[0-9,]*$/i}
     *
     * @return mixed
     *
     * @url GET {id}/selling_multiprices/per_customer
     */
    public function getCustomerPricesPerCustomer($id, $thirdparty_id = '')
    {
        global $conf;

        if (!DolibarrApiAccess::$user->rights->produit->lire) {
            throw new RestException(401);
        }

        if (empty($conf->global->PRODUIT_CUSTOMER_PRICES)) {
            throw new RestException(400, 'API not available: this mode of pricing is not enabled by setup');
        }

        $result = $this->product->fetch($id);
        if (!$result) {
            throw new RestException(404, 'Product not found');
        }

        if ($result > 0) {
			require_once DOL_DOCUMENT_ROOT.'/product/class/productcustomerprice.class.php';
			$prodcustprice = new Productcustomerprice($this->db);
			$filter = array();
			$filter['t.fk_product'] .= $id;
			if ($thirdparty_id) $filter['t.fk_soc'] .= $thirdparty_id;
			$result = $prodcustprice->fetch_all('', '', 0, 0, $filter);
        }

        if (empty($prodcustprice->lines)) {
            throw new RestException(404, 'Prices not found');
        }

        return $prodcustprice->lines;
    }

    /**
     * Get prices per quantity for a product
     *
     * @param int $id ID of product
     *
     * @return mixed
     *
     * @url GET {id}/selling_multiprices/per_quantity
     */
    public function getCustomerPricesPerQuantity($id)
    {
        global $conf;

        if (!DolibarrApiAccess::$user->rights->produit->lire) {
            throw new RestException(401);
        }

        if (empty($conf->global->PRODUIT_CUSTOMER_PRICES_BY_QTY)) {
            throw new RestException(400, 'API not available: this mode of pricing is not enabled by setup');
        }

        $result = $this->product->fetch($id);
        if (!$result) {
            throw new RestException(404, 'Product not found');
        }

        if ($result < 0) {
            throw new RestException(503, 'Error when retrieve prices list : '.array_merge(array($this->product->error), $this->product->errors));
        }

        return array(
        'prices_by_qty'=>$this->product->prices_by_qty[0], // 1 if price by quantity was activated for the product
        'prices_by_qty_list'=>$this->product->prices_by_qty_list[0]
        );
    }

    /**
     * Delete purchase price for a product
     *
     * @param  int $id Product ID
     * @param  int $priceid purchase price ID
     *
     * @url DELETE {id}/purchase_prices/{priceid}
     *
     * @return int
     *
     * @throws 401
     * @throws 404
     *
     */
    public function deletePurchasePrice($id, $priceid)
    {
        if (!DolibarrApiAccess::$user->rights->produit->supprimer) {
            throw new RestException(401);
        }
        $result = $this->product->fetch($id);
        if (!$result) {
            throw new RestException(404, 'Product not found');
        }

        if (!DolibarrApi::_checkAccessToResource('product', $this->product->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }
        $resultsupplier = 0;
        if ($result) {
            $this->productsupplier->fetch($id);
            $resultsupplier = $this->product->remove_product_fournisseur_price($priceid);
        }

        return $resultsupplier;
    }

    /**
     * Get a list of all purchase prices of products
     *
     * @param  string $sortfield  Sort field
     * @param  string $sortorder  Sort order
     * @param  int    $limit      Limit for list
     * @param  int    $page       Page number
     * @param  int    $mode       Use this param to filter list (0 for all, 1 for only product, 2 for only service)
     * @param  int    $category   Use this param to filter list by category of product
     * @param  int    $supplier   Use this param to filter list by supplier
     * @param  string $sqlfilters Other criteria to filter answers separated by a comma. Syntax example "(t.tobuy:=:0) and (t.tosell:=:1)"
     * @return array              Array of product objects
     *
     * @url GET purchase_prices
     */
    public function getSupplierProducts($sortfield = "t.ref", $sortorder = 'ASC', $limit = 100, $page = 0, $mode = 0, $category = 0, $supplier = 0, $sqlfilters = '')
    {
    	global $db, $conf;
    	$obj_ret = array();
    	$socid = DolibarrApiAccess::$user->socid ? DolibarrApiAccess::$user->socid : '';
    	$sql = "SELECT t.rowid, t.ref, t.ref_ext";
    	$sql .= " FROM ".MAIN_DB_PREFIX."product as t";
    	if ($category > 0) {
    		$sql .= ", ".MAIN_DB_PREFIX."categorie_product as c";
    	}
    	$sql .= ", ".MAIN_DB_PREFIX."product_fournisseur_price as s";

    	$sql .= ' WHERE t.entity IN ('.getEntity('product').')';

    	if ($supplier > 0) {
    		$sql .= " AND s.fk_soc = ".$db->escape($supplier);
    	}
    	$sql .= " AND s.fk_product = t.rowid";
    	// Select products of given category
    	if ($category > 0) {
    		$sql .= " AND c.fk_categorie = ".$db->escape($category);
    		$sql .= " AND c.fk_product = t.rowid";
    	}
    	if ($mode == 1) {
    		// Show only products
    		$sql .= " AND t.fk_product_type = 0";
    	} elseif ($mode == 2) {
    		// Show only services
    		$sql .= " AND t.fk_product_type = 1";
    	}
    	// Add sql filters
    	if ($sqlfilters) {
    		if (!DolibarrApi::_checkFilters($sqlfilters)) {
    			throw new RestException(503, 'Error when validating parameter sqlfilters '.$sqlfilters);
    		}
    		$regexstring = '\(([^:\'\(\)]+:[^:\'\(\)]+:[^:\(\)]+)\)';
    		$sql .= " AND (".preg_replace_callback('/'.$regexstring.'/', 'DolibarrApi::_forge_criteria_callback', $sqlfilters).")";
    	}
    	$sql .= $db->order($sortfield, $sortorder);
    	if ($limit) {
    		if ($page < 0) {
    			$page = 0;
    		}
    		$offset = $limit * $page;
    		$sql .= $db->plimit($limit + 1, $offset);
    	}
    	$result = $db->query($sql);
    	if ($result) {
    		$num = $db->num_rows($result);
    		$min = min($num, ($limit <= 0 ? $num : $limit));
    		$i = 0;
    		while ($i < $min)
    		{
    			$obj = $db->fetch_object($result);

    			$product_fourn = new ProductFournisseur($this->db);
    			$product_fourn_list = $product_fourn->list_product_fournisseur_price($obj->rowid, '', '', 0, 0);
    			foreach($product_fourn_list as $tmpobj) {
    				$this->_cleanObjectDatas($tmpobj);
    			}
    				//var_dump($product_fourn_list->db);exit;
    			$obj_ret[$obj->rowid] = $product_fourn_list;

    			$i++;
    		}
    	}
    	else {
    		throw new RestException(503, 'Error when retrieve product list : '.$db->lasterror());
    	}
    	if (!count($obj_ret)) {
    		throw new RestException(404, 'No product found');
    	}
    	return $obj_ret;
    }

    /**
     * Get purchase prices for a product
     *
     * Return an array with product information.
     * TODO implement getting a product by ref or by $ref_ext
     *
     * @param  int    $id               ID of product
     * @param  string $ref              Ref of element
     * @param  string $ref_ext          Ref ext of element
     * @param  string $barcode          Barcode of element
     * @return array|mixed                 Data without useless information
     *
     * @url GET {id}/purchase_prices
     *
     * @throws 401
     * @throws 403
     * @throws 404
     *
     */
    public function getPurchasePrices($id, $ref = '', $ref_ext = '', $barcode = '')
    {
        if (empty($id) && empty($ref) && empty($ref_ext) && empty($barcode)) {
            throw new RestException(400, 'bad value for parameter id, ref, ref_ext or barcode');
        }

        $id = (empty($id) ? 0 : $id);

        if (!DolibarrApiAccess::$user->rights->produit->lire) {
            throw new RestException(403);
        }

        $result = $this->product->fetch($id, $ref, $ref_ext, $barcode);
        if (!$result) {
            throw new RestException(404, 'Product not found');
        }

        if (!DolibarrApi::_checkAccessToResource('product', $this->product->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        if ($result) {
            $product_fourn = new ProductFournisseur($this->db);
            $product_fourn_list = $product_fourn->list_product_fournisseur_price($this->product->id, '', '', 0, 0);
        }

        return $this->_cleanObjectDatas($product_fourn_list);
    }

    /**
     * Get attributes.
     *
     * @return array
     *
     * @throws RestException
     *
     * @url GET attributes
     */
    public function getAttributes()
    {
        if (!DolibarrApiAccess::$user->rights->produit->lire) {
            throw new RestException(401);
        }

        $prodattr = new ProductAttribute($this->db);
        return $prodattr->fetchAll();
    }

    /**
     * Get attribute by ID.
     *
     * @param  int $id ID of Attribute
     * @return array
     *
     * @throws RestException
     * @throws 401
     * @throws 404
     *
     * @url GET attributes/{id}
     */
    public function getAttributeById($id)
    {
        if (!DolibarrApiAccess::$user->rights->produit->lire) {
            throw new RestException(401);
        }

        $prodattr = new ProductAttribute($this->db);
        $result = $prodattr->fetch((int) $id);

        if ($result < 0) {
            throw new RestException(404, "Attribute not found");
        }

        return $prodattr;
    }

    /**
     * Get attributes by ref.
     *
     * @param  string $ref Reference of Attribute
     * @return array
     *
     * @throws RestException
     * @throws 401
     *
     * @url GET attributes/ref/{ref}
     */
    public function getAttributesByRef($ref)
    {
        if (!DolibarrApiAccess::$user->rights->produit->lire) {
            throw new RestException(401);
        }

        $sql = "SELECT rowid, ref, label, rang FROM ".MAIN_DB_PREFIX."product_attribute WHERE ref LIKE '".trim($ref)."' AND entity IN (".getEntity('product').")";

        $query = $this->db->query($sql);

        if (!$this->db->num_rows($query)) {
            throw new RestException(404);
        }

        $result = $this->db->fetch_object($query);

        $attr = [];
        $attr['id'] = $result->rowid;
        $attr['ref'] = $result->ref;
        $attr['label'] = $result->label;
        $attr['rang'] = $result->rang;

        return $attr;
    }

    /**
     * Add attributes.
     *
     * @param  string $ref   Reference of Attribute
     * @param  string $label Label of Attribute
     * @return int
     *
     * @throws RestException
     * @throws 401
     *
     * @url POST attributes
     */
    public function addAttributes($ref, $label)
    {
        if (!DolibarrApiAccess::$user->rights->produit->creer) {
            throw new RestException(401);
        }

        $prodattr = new ProductAttribute($this->db);
        $prodattr->label = $label;
        $prodattr->ref = $ref;

        $resid = $prodattr->create(DolibarrApiAccess::$user);
        if ($resid <= 0) {
            throw new RestException(500, "Error creating new attribute");
        }
        return $resid;
    }

    /**
     * Update attributes by id.
     *
     * @param  int $id    ID of Attribute
     * @param  array $request_data Datas
     * @return array
     *
     * @throws RestException
     * @throws 401
     * @throws 404
     *
     * @url PUT attributes/{id}
     */
    public function putAttributes($id, $request_data = null)
    {
        if (!DolibarrApiAccess::$user->rights->produit->creer) {
            throw new RestException(401);
        }

        $prodattr = new ProductAttribute($this->db);

        $result = $prodattr->fetch((int) $id);
        if ($result == 0) {
            throw new RestException(404, 'Attribute not found');
        } elseif ($result < 0) {
            throw new RestException(500, "Error fetching attribute");
        }

        foreach ($request_data as $field => $value) {
            if ($field == 'rowid') { continue;
            }
            $prodattr->$field = $value;
        }

        if ($prodattr->update(DolibarrApiAccess::$user) > 0) {
            $result = $prodattr->fetch((int) $id);
            if ($result == 0) {
                throw new RestException(404, 'Attribute not found');
            } elseif ($result < 0) {
                throw new RestException(500, "Error fetching attribute");
            } else {
                return $prodattr;
            }
        }
        throw new RestException(500, "Error updating attribute");
    }

    /**
     * Delete attributes by id.
     *
     * @param  int $id 	ID of Attribute
     * @return int		Result of deletion
     *
     * @throws RestException
     * @throws 401
     *
     * @url DELETE attributes/{id}
     */
    public function deleteAttributes($id)
    {
        if (!DolibarrApiAccess::$user->rights->produit->supprimer) {
            throw new RestException(401);
        }

        $prodattr = new ProductAttribute($this->db);
        $prodattr->id = (int) $id;
        $result = $prodattr->delete(DolibarrApiAccess::$user);

        if ($result <= 0) {
        	throw new RestException(500, "Error deleting attribute");
        }

        return $result;
    }

    /**
     * Get attribute value by id.
     *
     * @param  int $id ID of Attribute value
     * @return array
     *
     * @throws RestException
     * @throws 401
     *
     * @url GET attributes/values/{id}
     */
    public function getAttributeValueById($id)
    {
        if (!DolibarrApiAccess::$user->rights->produit->lire) {
            throw new RestException(401);
        }

        $sql = "SELECT rowid, fk_product_attribute, ref, value FROM ".MAIN_DB_PREFIX."product_attribute_value WHERE rowid = ".(int) $id." AND entity IN (".getEntity('product').")";

        $query = $this->db->query($sql);

        if (!$query) {
            throw new RestException(401);
        }

        if (!$this->db->num_rows($query)) {
            throw new RestException(404, 'Attribute value not found');
        }

        $result = $this->db->fetch_object($query);

        $attrval = [];
        $attrval['id'] = $result->rowid;
        $attrval['fk_product_attribute'] = $result->fk_product_attribute;
        $attrval['ref'] = $result->ref;
        $attrval['value'] = $result->value;

        return $attrval;
    }

    /**
     * Get attribute value by ref.
     *
     * @param  int $id ID of Attribute value
     * @param  string $ref Ref of Attribute value
     * @return array
     *
     * @throws RestException
     * @throws 401
     *
     * @url GET attributes/{id}/values/ref/{ref}
     */
    public function getAttributeValueByRef($id, $ref)
    {
        if (!DolibarrApiAccess::$user->rights->produit->lire) {
            throw new RestException(401);
        }

        $sql = "SELECT rowid, fk_product_attribute, ref, value FROM ".MAIN_DB_PREFIX."product_attribute_value WHERE ref LIKE '".trim($ref)."' AND fk_product_attribute = ".(int) $id." AND entity IN (".getEntity('product').")";

        $query = $this->db->query($sql);

        if (!$query) {
            throw new RestException(401);
        }

        if (!$this->db->num_rows($query)) {
            throw new RestException(404, 'Attribute value not found');
        }

        $result = $this->db->fetch_object($query);

        $attrval = [];
        $attrval['id'] = $result->rowid;
        $attrval['fk_product_attribute'] = $result->fk_product_attribute;
        $attrval['ref'] = $result->ref;
        $attrval['value'] = $result->value;

        return $attrval;
    }

    /**
     * Delete attribute value by ref.
     *
     * @param  int $id ID of Attribute
     * @param  string $ref Ref of Attribute value
     * @return int
     *
     * @throws RestException
     * @throws 401
     *
     * @url DELETE attributes/{id}/values/ref/{ref}
     */
    public function deleteAttributeValueByRef($id, $ref)
    {
        if (!DolibarrApiAccess::$user->rights->produit->supprimer) {
            throw new RestException(401);
        }

        $sql = "DELETE FROM ".MAIN_DB_PREFIX."product_attribute_value WHERE ref LIKE '".trim($ref)."' AND fk_product_attribute = ".(int) $id;

        if ($this->db->query($sql)) {
            return 1;
        }

        throw new RestException(500, "Error deleting attribute value");
    }

    /**
     * Get all values for an attribute id.
     *
     * @param  int $id ID of an Attribute
     * @return array
     *
     * @throws RestException
     * @throws 401
     *
     * @url GET attributes/{id}/values
     */
    public function getAttributeValues($id)
    {
        if (!DolibarrApiAccess::$user->rights->produit->lire) {
            throw new RestException(401);
        }

        $objectval = new ProductAttributeValue($this->db);
        return $objectval->fetchAllByProductAttribute((int) $id);
    }

    /**
     * Get all values for an attribute ref.
     *
     * @param  string $ref Ref of an Attribute
     * @return array
     *
     * @throws RestException
     * @throws 401
     *
     * @url GET attributes/ref/{ref}/values
     */
    public function getAttributeValuesByRef($ref)
    {
        if (!DolibarrApiAccess::$user->rights->produit->lire) {
            throw new RestException(401);
        }

        $return = array();

        $sql = 'SELECT ';
        $sql .= 'v.fk_product_attribute, v.rowid, v.ref, v.value FROM '.MAIN_DB_PREFIX.'product_attribute_value v ';
        $sql .= "WHERE v.fk_product_attribute = ( SELECT rowid FROM ".MAIN_DB_PREFIX."product_attribute WHERE ref LIKE '".strtoupper(trim($ref))."' LIMIT 1)";

        $query = $this->db->query($sql);

        while ($result = $this->db->fetch_object($query)) {
            $tmp = new ProductAttributeValue($this->db);
            $tmp->fk_product_attribute = $result->fk_product_attribute;
            $tmp->id = $result->rowid;
            $tmp->ref = $result->ref;
            $tmp->value = $result->value;

            $return[] = $tmp;
        }

        return $return;
    }

    /**
     * Add attribute value.
     *
     * @param  int    $id    ID of Attribute
     * @param  string $ref   Reference of Attribute value
     * @param  string $value Value of Attribute value
     * @return int
     *
     * @throws RestException
     * @throws 401
     *
     * @url POST attributes/{id}/values
     */
    public function addAttributeValue($id, $ref, $value)
    {
        if (!DolibarrApiAccess::$user->rights->produit->creer) {
            throw new RestException(401);
        }

        if (empty($ref) || empty($value)) {
            throw new RestException(401);
        }

        $objectval = new ProductAttributeValue($this->db);
        $objectval->fk_product_attribute = $id;
        $objectval->ref = $ref;
        $objectval->value = $value;

        if ($objectval->create(DolibarrApiAccess::$user) > 0) {
            return $objectval->id;
        }
        throw new RestException(500, "Error creating new attribute value");
    }

    /**
     * Update attribute value.
     *
     * @param  int $id ID of Attribute
     * @param  array $request_data Datas
     * @return array
     *
     * @throws RestException
     * @throws 401
     *
     * @url PUT attributes/values/{id}
     */
    public function putAttributeValue($id, $request_data)
    {
        if (!DolibarrApiAccess::$user->rights->produit->creer) {
            throw new RestException(401);
        }

        $objectval = new ProductAttributeValue($this->db);
        $result = $objectval->fetch((int) $id);

        if ($result == 0) {
            throw new RestException(404, 'Attribute value not found');
        } elseif ($result < 0) {
            throw new RestException(500, "Error fetching attribute value");
        }

        foreach ($request_data as $field => $value) {
            if ($field == 'rowid') { continue;
            }
            $objectval->$field = $value;
        }

        if ($objectval->update(DolibarrApiAccess::$user) > 0) {
            $result = $objectval->fetch((int) $id);
            if ($result == 0) {
                throw new RestException(404, 'Attribute not found');
            } elseif ($result < 0) {
                throw new RestException(500, "Error fetching attribute");
            } else {
                return $objectval;
            }
        }
        throw new RestException(500, "Error updating attribute");
    }

    /**
     * Delete attribute value by id.
     *
     * @param  int $id ID of Attribute value
     * @return int
     *
     * @throws RestException
     * @throws 401
     *
     * @url DELETE attributes/values/{id}
     */
    public function deleteAttributeValueById($id)
    {
        if (!DolibarrApiAccess::$user->rights->produit->supprimer) {
            throw new RestException(401);
        }

        $objectval = new ProductAttributeValue($this->db);
        $objectval->id = (int) $id;

        if ($objectval->delete() > 0) {
            return 1;
        }
        throw new RestException(500, "Error deleting attribute value");
    }

    /**
     * Get product variants.
     *
     * @param  int $id ID of Product
     * @return array
     *
     * @throws RestException
     * @throws 401
     *
     * @url GET {id}/variants
     */
    public function getVariants($id)
    {
        if (!DolibarrApiAccess::$user->rights->produit->lire) {
            throw new RestException(401);
        }

        $prodcomb = new ProductCombination($this->db);
        $combinations = $prodcomb->fetchAllByFkProductParent((int) $id);

        foreach ($combinations as $key => $combination) {
            $prodc2vp = new ProductCombination2ValuePair($this->db);
            $combinations[$key]->attributes = $prodc2vp->fetchByFkCombination((int) $combination->id);
        }

        return $combinations;
    }

    /**
     * Get product variants by Product ref.
     *
     * @param  string $ref Ref of Product
     * @return array
     *
     * @throws RestException
     * @throws 401
     *
     * @url GET ref/{ref}/variants
     */
    public function getVariantsByProdRef($ref)
    {
        if (!DolibarrApiAccess::$user->rights->produit->lire) {
            throw new RestException(401);
        }

        $result = $this->product->fetch('', $ref);
        if (!$result) {
            throw new RestException(404, 'Product not found');
        }

        $prodcomb = new ProductCombination($this->db);
        $combinations = $prodcomb->fetchAllByFkProductParent((int) $this->product->id);

        foreach ($combinations as $key => $combination) {
            $prodc2vp = new ProductCombination2ValuePair($this->db);
            $combinations[$key]->attributes = $prodc2vp->fetchByFkCombination((int) $combination->id);
        }

        return $combinations;
    }

    /**
     * Add variant.
     *
     * "features" is a list of attributes pairs id_attribute=>id_value. Example: array(id_color=>id_Blue, id_size=>id_small, id_option=>id_val_a, ...)
     *
     * @param  int    $id                       ID of Product
     * @param  float  $weight_impact            Weight impact of variant
     * @param  float  $price_impact             Price impact of variant
     * @param  bool   $price_impact_is_percent  Price impact in percent (true or false)
     * @param  array  $features                 List of attributes pairs id_attribute->id_value. Example: array(id_color=>id_Blue, id_size=>id_small, id_option=>id_val_a, ...)
     * @return int
     *
     * @throws RestException
     * @throws 401
     * @throws 404
     *
     * @url POST {id}/variants
     */
    public function addVariant($id, $weight_impact, $price_impact, $price_impact_is_percent, $features)
    {
        if (!DolibarrApiAccess::$user->rights->produit->creer) {
            throw new RestException(401);
        }

        if (empty($id) || empty($features) || !is_array($features)) {
            throw new RestException(401);
        }

        $weight_impact = price2num($weight_impact);
        $price_impact = price2num($price_impact);

        $prodattr = new ProductAttribute($this->db);
        $prodattr_val = new ProductAttributeValue($this->db);
        foreach ($features as $id_attr => $id_value) {
            if ($prodattr->fetch((int) $id_attr) < 0) {
                throw new RestException(401);
            }
            if ($prodattr_val->fetch((int) $id_value) < 0) {
                throw new RestException(401);
            }
        }

        $result = $this->product->fetch((int) $id);
        if (!$result) {
            throw new RestException(404, 'Product not found');
        }

        $prodcomb = new ProductCombination($this->db);
        if (!$prodcomb->fetchByProductCombination2ValuePairs($id, $features))
        {
            $result = $prodcomb->createProductCombination(DolibarrApiAccess::$user, $this->product, $features, array(), $price_impact_is_percent, $price_impact, $weight_impact);
            if ($result > 0)
            {
                return $result;
            } else {
                throw new RestException(500, "Error creating new product variant");
            }
        } else {
            return $prodcomb->id;
        }
    }

    /**
     * Add variant by product ref.
     *
     * "features" is a list of attributes pairs id_attribute=>id_value. Example: array(id_color=>id_Blue, id_size=>id_small, id_option=>id_val_a, ...)
     *
     * @param  string $ref                      Ref of Product
     * @param  float  $weight_impact            Weight impact of variant
     * @param  float  $price_impact             Price impact of variant
     * @param  bool   $price_impact_is_percent  Price impact in percent (true or false)
     * @param  array  $features                 List of attributes pairs id_attribute->id_value. Example: array(id_color=>id_Blue, id_size=>id_small, id_option=>id_val_a, ...)
     * @return int
     *
     * @throws RestException
     * @throws 401
     * @throws 404
     *
     * @url POST ref/{ref}/variants
     */
    public function addVariantByProductRef($ref, $weight_impact, $price_impact, $price_impact_is_percent, $features)
    {
        if (!DolibarrApiAccess::$user->rights->produit->creer) {
            throw new RestException(401);
        }

        if (empty($ref) || empty($features) || !is_array($features)) {
            throw new RestException(401);
        }

        $weight_impact = price2num($weight_impact);
        $price_impact = price2num($price_impact);

        $prodattr = new ProductAttribute($this->db);
        $prodattr_val = new ProductAttributeValue($this->db);
        foreach ($features as $id_attr => $id_value) {
            if ($prodattr->fetch((int) $id_attr) < 0) {
                throw new RestException(404);
            }
            if ($prodattr_val->fetch((int) $id_value) < 0) {
                throw new RestException(404);
            }
        }

        $result = $this->product->fetch('', trim($ref));
        if (!$result) {
            throw new RestException(404, 'Product not found');
        }

        $prodcomb = new ProductCombination($this->db);
        if (!$prodcomb->fetchByProductCombination2ValuePairs($this->product->id, $features))
        {
            $result = $prodcomb->createProductCombination(DolibarrApiAccess::$user, $this->product, $features, array(), $price_impact_is_percent, $price_impact, $weight_impact);
            if ($result > 0)
            {
                return $result;
            } else {
                throw new RestException(500, "Error creating new product variant");
            }
        } else {
            return $prodcomb->id;
        }
    }

    /**
     * Put product variants.
     *
     * @param  int $id ID of Variant
     * @param  array $request_data Datas
     * @return int
     *
     * @throws RestException
     * @throws 401
     *
     * @url PUT variants/{id}
     */
    public function putVariant($id, $request_data = null)
    {
        if (!DolibarrApiAccess::$user->rights->produit->creer) {
            throw new RestException(401);
        }

        $prodcomb = new ProductCombination($this->db);
        $prodcomb->fetch((int) $id);

        foreach ($request_data as $field => $value) {
            if ($field == 'rowid') { continue;
            }
            $prodcomb->$field = $value;
        }

        $result = $prodcomb->update(DolibarrApiAccess::$user);
        if ($result > 0)
        {
            return 1;
        }
        throw new RestException(500, "Error editing variant");
    }

    /**
     * Delete product variants.
     *
     * @param  int $id 	ID of Variant
     * @return int		Result of deletion
     *
     * @throws RestException
     * @throws 401
     *
     * @url DELETE variants/{id}
     */
    public function deleteVariant($id)
    {
        if (!DolibarrApiAccess::$user->rights->produit->supprimer) {
            throw new RestException(401);
        }

        $prodcomb = new ProductCombination($this->db);
        $prodcomb->id = (int) $id;
        $result = $prodcomb->delete(DolibarrApiAccess::$user);
        if ($result <= 0)
        {
        	throw new RestException(500, "Error deleting variant");
        }
        return $result;
    }

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
    /**
     * Clean sensible object datas
     *
     * @param  object $object Object to clean
     * @return array    Array of cleaned object properties
     */
    protected function _cleanObjectDatas($object)
    {
        // phpcs:enable
        $object = parent::_cleanObjectDatas($object);

        unset($object->regeximgext);
        unset($object->price_by_qty);
        unset($object->prices_by_qty_id);
        unset($object->libelle);
        unset($object->product_id_already_linked);
        unset($object->reputations);

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
     * @param  array $data Datas to validate
     * @return array
     * @throws RestException
     */
    private function _validate($data)
    {
        $product = array();
        foreach (Products::$FIELDS as $field) {
            if (!isset($data[$field])) {
                throw new RestException(400, "$field field missing");
            }
            $product[$field] = $data[$field];
        }
        return $product;
    }

    /**
     * Get properties of a product object
     *
     * Return an array with product information.
     *
     * @param  int    $id                 ID of product
     * @param  string $ref                Ref of element
     * @param  string $ref_ext            Ref ext of element
     * @param  string $barcode            Barcode of element
     * @param  int    $includestockdata   Load also information about stock (slower)
     * @param  bool   $includesubproducts Load information about subproducts
     * @return array|mixed                Data without useless information
     *
     * @throws 401
     * @throws 403
     * @throws 404
     */
    private function _fetch($id, $ref = '', $ref_ext = '', $barcode = '', $includestockdata = 0, $includesubproducts = false)
    {
        if (empty($id) && empty($ref) && empty($ref_ext) && empty($barcode)) {
            throw new RestException(400, 'bad value for parameter id, ref, ref_ext or barcode');
        }

        $id = (empty($id) ? 0 : $id);

        if (!DolibarrApiAccess::$user->rights->produit->lire) {
            throw new RestException(403);
        }

        $result = $this->product->fetch($id, $ref, $ref_ext, $barcode);
        if (!$result) {
            throw new RestException(404, 'Product not found');
        }

        if (!DolibarrApi::_checkAccessToResource('product', $this->product->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        if ($includestockdata) {
        	$this->product->load_stock();

        	if (is_array($this->product->stock_warehouse)) {
        		foreach($this->product->stock_warehouse as $keytmp => $valtmp) {
        			if (is_array($this->product->stock_warehouse[$keytmp]->detail_batch)) {
        				foreach($this->product->stock_warehouse[$keytmp]->detail_batch as $keytmp2 => $valtmp2) {
        					unset($this->product->stock_warehouse[$keytmp]->detail_batch[$keytmp2]->db);
        				}
        			}
        		}
        	}
        }

        if ($includesubproducts) {
            $childsArbo = $this->product->getChildsArbo($id, 1);

            $keys = ['rowid', 'qty', 'fk_product_type', 'label', 'incdec'];
            $childs = [];
            foreach ($childsArbo as $values) {
                $childs[] = array_combine($keys, $values);
            }

            $this->product->sousprods = $childs;
        }

        return $this->_cleanObjectDatas($this->product);
    }
}
