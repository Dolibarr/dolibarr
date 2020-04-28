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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

use Luracast\Restler\RestException;

require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/client.class.php';

require_once DOL_DOCUMENT_ROOT.'/adherents/class/api_members.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/api_products.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/api_contacts.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/api_thirdparties.class.php';

/**
 * API class for categories
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class Categories extends DolibarrApi
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
     * Constructor
     */
    public function __construct()
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
     * @param 	bool 	$include_childs Include child categories list (true or false)
     * @return 	array|mixed data without useless information
     *
     * @throws 	RestException
     */
    public function get($id, $include_childs = false)
    {
        if (!DolibarrApiAccess::$user->rights->categorie->lire) {
            throw new RestException(401);
        }

        $result = $this->category->fetch($id);
        if (!$result) {
            throw new RestException(404, 'category not found');
        }

        if (!DolibarrApi::_checkAccessToResource('categorie', $this->category->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        if ($include_childs) {
            $cats = $this->category->get_filles();
            if (!is_array($cats)) {
                throw new RestException(500, 'Error when fetching child categories', array_merge(array($this->category->error), $this->category->errors));
            }
            $this->category->childs = [];
            foreach ($cats as $cat) {
                $this->category->childs[] = $this->_cleanObjectDatas($cat);
            }
        }

        return $this->_cleanObjectDatas($this->category);
    }

    /**
     * List categories
     *
     * Get a list of categories
     *
     * @param string	$sortfield	Sort field
     * @param string	$sortorder	Sort order
     * @param int		$limit		Limit for list
     * @param int		$page		Page number
     * @param string	$type		Type of category ('member', 'customer', 'supplier', 'product', 'contact')
     * @param string    $sqlfilters Other criteria to filter answers separated by a comma. Syntax example "(t.ref:like:'SO-%') and (t.date_creation:<:'20160101')"
     * @return array                Array of category objects
     *
     * @throws RestException
     */
    public function index($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $type = '', $sqlfilters = '')
    {
        global $db, $conf;

        $obj_ret = array();

        if (!DolibarrApiAccess::$user->rights->categorie->lire) {
            throw new RestException(401);
        }

        $sql = "SELECT t.rowid";
        $sql .= " FROM ".MAIN_DB_PREFIX."categorie as t";
        $sql .= ' WHERE t.entity IN ('.getEntity('category').')';
        if (!empty($type))
        {
            $sql .= ' AND t.type='.array_search($type, Categories::$TYPES);
        }
        // Add sql filters
        if ($sqlfilters)
        {
            if (!DolibarrApi::_checkFilters($sqlfilters))
            {
                throw new RestException(503, 'Error when validating parameter sqlfilters '.$sqlfilters);
            }
            $regexstring = '\(([^:\'\(\)]+:[^:\'\(\)]+:[^:\(\)]+)\)';
            $sql .= " AND (".preg_replace_callback('/'.$regexstring.'/', 'DolibarrApi::_forge_criteria_callback', $sqlfilters).")";
        }

        $sql .= $db->order($sortfield, $sortorder);
        if ($limit) {
            if ($page < 0)
            {
                $page = 0;
            }
            $offset = $limit * $page;

            $sql .= $db->plimit($limit + 1, $offset);
        }

        $result = $db->query($sql);
        if ($result)
        {
            $i = 0;
            $num = $db->num_rows($result);
            $min = min($num, ($limit <= 0 ? $num : $limit));
            while ($i < $min)
            {
                $obj = $db->fetch_object($result);
                $category_static = new Categorie($db);
                if ($category_static->fetch($obj->rowid)) {
                    $obj_ret[] = $this->_cleanObjectDatas($category_static);
                }
                $i++;
            }
        }
        else {
            throw new RestException(503, 'Error when retrieve category list : '.$db->lasterror());
        }
        if (!count($obj_ret)) {
            throw new RestException(404, 'No category found');
        }
        return $obj_ret;
    }

    /**
     * Create category object
     *
     * @param array $request_data   Request data
     * @return int  ID of category
     */
    public function post($request_data = null)
    {
        if (!DolibarrApiAccess::$user->rights->categorie->creer) {
            throw new RestException(401);
        }

        // Check mandatory fields
        $result = $this->_validate($request_data);

        foreach ($request_data as $field => $value) {
            $this->category->$field = $value;
        }
        if ($this->category->create(DolibarrApiAccess::$user) < 0) {
            throw new RestException(500, 'Error when creating category', array_merge(array($this->category->error), $this->category->errors));
        }
        return $this->category->id;
    }

    /**
     * Update category
     *
     * @param int   $id             Id of category to update
     * @param array $request_data   Datas
     * @return int
     */
    public function put($id, $request_data = null)
    {
        if (!DolibarrApiAccess::$user->rights->categorie->creer) {
            throw new RestException(401);
        }

        $result = $this->category->fetch($id);
        if (!$result) {
            throw new RestException(404, 'category not found');
        }

        if (!DolibarrApi::_checkAccessToResource('categorie', $this->category->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        foreach ($request_data as $field => $value) {
            if ($field == 'id') continue;
            $this->category->$field = $value;
        }

        if ($this->category->update(DolibarrApiAccess::$user) > 0)
        {
            return $this->get($id);
        }
        else
        {
            throw new RestException(500, $this->category->error);
        }
    }

    /**
     * Delete category
     *
     * @param int $id   Category ID
     * @return array
     */
    public function delete($id)
    {
        if (!DolibarrApiAccess::$user->rights->categorie->supprimer) {
            throw new RestException(401);
        }
        $result = $this->category->fetch($id);
        if (!$result) {
            throw new RestException(404, 'category not found');
        }

        if (!DolibarrApi::_checkAccessToResource('categorie', $this->category->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        if (!$this->category->delete(DolibarrApiAccess::$user)) {
            throw new RestException(401, 'error when delete category');
        }

        return array(
            'success' => array(
                'code' => 200,
                'message' => 'Category deleted'
            )
        );
    }

    /**
     * List categories of an object
     *
     * Get the list of categories linked to an object
     *
     * @param int       $id         Object ID
     * @param string	$type		Type of category ('member', 'customer', 'supplier', 'product', 'contact')
     * @param string	$sortfield	Sort field
     * @param string	$sortorder	Sort order
     * @param int		$limit		Limit for list
     * @param int		$page		Page number
     * @return array                Array of category objects
     *
     * @throws RestException
     *
     * @url GET /object/{type}/{id}
     */
    public function getListForObject($id, $type, $sortfield = "s.rowid", $sortorder = 'ASC', $limit = 0, $page = 0)
    {
        if (!in_array($type, [
            Categorie::TYPE_PRODUCT,
            Categorie::TYPE_CONTACT,
            Categorie::TYPE_CUSTOMER,
            Categorie::TYPE_SUPPLIER,
            Categorie::TYPE_MEMBER
        ])) {
            throw new RestException(401);
        }

        if ($type == Categorie::TYPE_PRODUCT && !(DolibarrApiAccess::$user->rights->produit->lire || DolibarrApiAccess::$user->rights->service->lire)) {
            throw new RestException(401);
        } elseif ($type == Categorie::TYPE_CONTACT && !DolibarrApiAccess::$user->rights->contact->lire) {
            throw new RestException(401);
        } elseif ($type == Categorie::TYPE_CUSTOMER && !DolibarrApiAccess::$user->rights->societe->lire) {
            throw new RestException(401);
        } elseif ($type == Categorie::TYPE_SUPPLIER && !DolibarrApiAccess::$user->rights->fournisseur->lire) {
            throw new RestException(401);
        } elseif ($type == Categorie::TYPE_MEMBER && !DolibarrApiAccess::$user->rights->adherent->lire) {
            throw new RestException(401);
        }

        $categories = $this->category->getListForItem($id, $type, $sortfield, $sortorder, $limit, $page);

        if (!is_array($categories)) {
            if ($categories == 0) {
                throw new RestException(404, 'No category found for this object');
            }
            throw new RestException(500, 'Error when fetching object categories', array_merge(array($this->category->error), $this->category->errors));
        }
        return $categories;
    }

    /**
     * Link an object to a category by id
     *
     * @param int $id  ID of category
     * @param string   $type Type of category ('member', 'customer', 'supplier', 'product', 'contact')
     * @param int      $object_id ID of object
     *
     * @return array
     * @throws RestException
     *
     * @url POST {id}/objects/{type}/{object_id}
     */
    public function linkObjectById($id, $type, $object_id)
    {
        if (empty($type) || empty($object_id)) {
            throw new RestException(401);
        }

        if (!DolibarrApiAccess::$user->rights->categorie->lire) {
            throw new RestException(401);
        }

        $result = $this->category->fetch($id);
        if (!$result) {
            throw new RestException(404, 'category not found');
        }

        if ($type === Categorie::TYPE_PRODUCT) {
            if (!(DolibarrApiAccess::$user->rights->produit->creer || DolibarrApiAccess::$user->rights->service->creer)) {
                throw new RestException(401);
            }
            $object = new Product($this->db);
        } elseif ($type === Categorie::TYPE_CUSTOMER) {
            if (!DolibarrApiAccess::$user->rights->societe->creer) {
                throw new RestException(401);
            }
            $object = new Societe($this->db);
        } elseif ($type === Categorie::TYPE_SUPPLIER) {
            if (!DolibarrApiAccess::$user->rights->societe->creer) {
                throw new RestException(401);
            }
            $object = new Societe($this->db);
        } elseif ($type === Categorie::TYPE_CONTACT) {
            if (!DolibarrApiAccess::$user->rights->societe->contact->creer) {
                throw new RestException(401);
            }
            $object = new Contact($this->db);
        } elseif ($type === Categorie::TYPE_MEMBER) {
            if (!DolibarrApiAccess::$user->rights->adherent->creer) {
                throw new RestException(401);
            }
            $object = new Adherent($this->db);
        } else {
            throw new RestException(401, "this type is not recognized yet.");
        }

        if (!empty($object)) {
            $result = $object->fetch($object_id);
            if ($result > 0) {
                $result = $this->category->add_type($object, $type);
                if ($result < 0) {
                    if ($this->category->error != 'DB_ERROR_RECORD_ALREADY_EXISTS') {
                        throw new RestException(500, 'Error when linking object', array_merge(array($this->category->error), $this->category->errors));
                    }
                }
            } else {
                throw new RestException(500, 'Error when fetching object', array_merge(array($object->error), $object->errors));
            }

            return array(
                'success' => array(
                    'code' => 200,
                    'message' => 'Objects succefully linked to the category'
                )
            );
        }

        throw new RestException(401);
    }

    /**
     * Link an object to a category by ref
     *
     * @param int $id  ID of category
     * @param string   $type Type of category ('member', 'customer', 'supplier', 'product', 'contact')
     * @param string   $object_ref Reference of object
     *
     * @return array
     * @throws RestException
     *
     * @url POST {id}/objects/{type}/ref/{object_ref}
     */
    public function linkObjectByRef($id, $type, $object_ref)
    {
        if (empty($type) || empty($object_ref)) {
            throw new RestException(401);
        }

        if (!DolibarrApiAccess::$user->rights->categorie->lire) {
            throw new RestException(401);
        }

        $result = $this->category->fetch($id);
        if (!$result) {
            throw new RestException(404, 'category not found');
        }

        if ($type === Categorie::TYPE_PRODUCT) {
            if (!(DolibarrApiAccess::$user->rights->produit->creer || DolibarrApiAccess::$user->rights->service->creer)) {
                throw new RestException(401);
            }
            $object = new Product($this->db);
        } elseif ($type === Categorie::TYPE_CUSTOMER) {
            if (!DolibarrApiAccess::$user->rights->societe->creer) {
                throw new RestException(401);
            }
            $object = new Societe($this->db);
        } elseif ($type === Categorie::TYPE_SUPPLIER) {
            if (!DolibarrApiAccess::$user->rights->societe->creer) {
                throw new RestException(401);
            }
            $object = new Societe($this->db);
        } elseif ($type === Categorie::TYPE_CONTACT) {
            if (!DolibarrApiAccess::$user->rights->societe->contact->creer) {
                throw new RestException(401);
            }
            $object = new Contact($this->db);
        } elseif ($type === Categorie::TYPE_MEMBER) {
            if (!DolibarrApiAccess::$user->rights->adherent->creer) {
                throw new RestException(401);
            }
            $object = new Adherent($this->db);
        } else {
            throw new RestException(401, "this type is not recognized yet.");
        }

        if (!empty($object)) {
            $result = $object->fetch('', $object_ref);
            if ($result > 0) {
                $result = $this->category->add_type($object, $type);
                if ($result < 0) {
                    if ($this->category->error != 'DB_ERROR_RECORD_ALREADY_EXISTS') {
                        throw new RestException(500, 'Error when linking object', array_merge(array($this->category->error), $this->category->errors));
                    }
                }
            } else {
                throw new RestException(500, 'Error when fetching object', array_merge(array($object->error), $object->errors));
            }

            return array(
                'success' => array(
                    'code' => 200,
                    'message' => 'Objects succefully linked to the category'
                )
            );
        }

        throw new RestException(401);
    }

    /**
     * Unlink an object from a category by id
     *
     * @param int      $id        ID of category
     * @param string   $type      Type of category ('member', 'customer', 'supplier', 'product', 'contact')
     * @param int      $object_id ID of the object
     *
     * @return array
     * @throws RestException
     *
     * @url DELETE {id}/objects/{type}/{object_id}
     */
    public function unlinkObjectById($id, $type, $object_id)
    {
        if (empty($type) || empty($object_id)) {
            throw new RestException(401);
        }

        if (!DolibarrApiAccess::$user->rights->categorie->lire) {
            throw new RestException(401);
        }

        $result = $this->category->fetch($id);
        if (!$result) {
            throw new RestException(404, 'category not found');
        }

        if ($type === Categorie::TYPE_PRODUCT) {
            if (!(DolibarrApiAccess::$user->rights->produit->creer || DolibarrApiAccess::$user->rights->service->creer)) {
                throw new RestException(401);
            }
            $object = new Product($this->db);
        } elseif ($type === Categorie::TYPE_CUSTOMER) {
            if (!DolibarrApiAccess::$user->rights->societe->creer) {
                throw new RestException(401);
            }
            $object = new Societe($this->db);
        } elseif ($type === Categorie::TYPE_SUPPLIER) {
            if (!DolibarrApiAccess::$user->rights->societe->creer) {
                throw new RestException(401);
            }
            $object = new Societe($this->db);
        } elseif ($type === Categorie::TYPE_CONTACT) {
            if (!DolibarrApiAccess::$user->rights->societe->contact->creer) {
                throw new RestException(401);
            }
            $object = new Contact($this->db);
        } elseif ($type === Categorie::TYPE_MEMBER) {
            if (!DolibarrApiAccess::$user->rights->adherent->creer) {
                throw new RestException(401);
            }
            $object = new Adherent($this->db);
        } else {
            throw new RestException(401, "this type is not recognized yet.");
        }

        if (!empty($object)) {
            $result = $object->fetch((int) $object_id);
            if ($result > 0) {
                $result = $this->category->del_type($object, $type);
                if ($result < 0) {
                    throw new RestException(500, 'Error when unlinking object', array_merge(array($this->category->error), $this->category->errors));
                }
            } else {
                throw new RestException(500, 'Error when fetching object', array_merge(array($object->error), $object->errors));
            }

            return array(
                'success' => array(
                    'code' => 200,
                    'message' => 'Objects succefully unlinked from the category'
                )
            );
        }

        throw new RestException(401);
    }

    /**
     * Unlink an object from a category by ref
     *
     * @param int      $id         ID of category
     * @param string   $type Type  of category ('member', 'customer', 'supplier', 'product', 'contact')
     * @param string   $object_ref Reference of the object
     *
     * @return array
     * @throws RestException
     *
     * @url DELETE {id}/objects/{type}/ref/{object_ref}
     */
    public function unlinkObjectByRef($id, $type, $object_ref)
    {
        if (empty($type) || empty($object_ref)) {
            throw new RestException(401);
        }

        if (!DolibarrApiAccess::$user->rights->categorie->lire) {
            throw new RestException(401);
        }

        $result = $this->category->fetch($id);
        if (!$result) {
            throw new RestException(404, 'category not found');
        }

        if ($type === Categorie::TYPE_PRODUCT) {
            if (!(DolibarrApiAccess::$user->rights->produit->creer || DolibarrApiAccess::$user->rights->service->creer)) {
                throw new RestException(401);
            }
            $object = new Product($this->db);
        } elseif ($type === Categorie::TYPE_CUSTOMER) {
            if (!DolibarrApiAccess::$user->rights->societe->creer) {
                throw new RestException(401);
            }
            $object = new Societe($this->db);
        } elseif ($type === Categorie::TYPE_SUPPLIER) {
            if (!DolibarrApiAccess::$user->rights->societe->creer) {
                throw new RestException(401);
            }
            $object = new Societe($this->db);
        } elseif ($type === Categorie::TYPE_CONTACT) {
            if (!DolibarrApiAccess::$user->rights->societe->contact->creer) {
                throw new RestException(401);
            }
            $object = new Contact($this->db);
        } elseif ($type === Categorie::TYPE_MEMBER) {
            if (!DolibarrApiAccess::$user->rights->adherent->creer) {
                throw new RestException(401);
            }
            $object = new Adherent($this->db);
        } else {
            throw new RestException(401, "this type is not recognized yet.");
        }

        if (!empty($object)) {
            $result = $object->fetch('', (string) $object_ref);
            if ($result > 0) {
                $result = $this->category->del_type($object, $type);
                if ($result < 0) {
                    throw new RestException(500, 'Error when unlinking object', array_merge(array($this->category->error), $this->category->errors));
                }
            } else {
                throw new RestException(500, 'Error when fetching object', array_merge(array($object->error), $object->errors));
            }

            return array(
                'success' => array(
                    'code' => 200,
                    'message' => 'Objects succefully unlinked from the category'
                )
            );
        }

        throw new RestException(401);
    }


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
    /**
     * Clean sensible object datas
     *
     * @param   Categorie  $object    Object to clean
     * @return    array    Array of cleaned object properties
     */
    protected function _cleanObjectDatas($object)
    {
        // phpcs:enable
        $object = parent::_cleanObjectDatas($object);

        // Remove fields not relevent to categories
        unset($object->country);
        unset($object->country_id);
        unset($object->country_code);
        unset($object->total_ht);
        unset($object->total_ht);
        unset($object->total_localtax1);
        unset($object->total_localtax2);
        unset($object->total_ttc);
        unset($object->total_tva);
        unset($object->lines);
        unset($object->fk_incoterms);
        unset($object->label_incoterms);
        unset($object->location_incoterms);
        unset($object->civility_id);
        unset($object->name);
        unset($object->lastname);
        unset($object->firstname);
        unset($object->shipping_method_id);
        unset($object->fk_delivery_address);
        unset($object->cond_reglement);
        unset($object->cond_reglement_id);
        unset($object->mode_reglement_id);
        unset($object->barcode_type_coder);
        unset($object->barcode_type_label);
        unset($object->barcode_type_code);
        unset($object->barcode_type);
        unset($object->canvas);
        unset($object->cats);
        unset($object->motherof);
        unset($object->context);
        unset($object->socid);
        unset($object->thirdparty);
        unset($object->contact);
        unset($object->contact_id);
        unset($object->user);
        unset($object->fk_account);
        unset($object->fk_project);
        unset($object->note);
        unset($object->statut);

        return $object;
    }

    /**
     * Validate fields before create or update object
     *
     * @param array|null    $data    Data to validate
     * @return array
     *
     * @throws RestException
     */
    private function _validate($data)
    {
        $category = array();
        foreach (Categories::$FIELDS as $field) {
            if (!isset($data[$field]))
                throw new RestException(400, "$field field missing");
            $category[$field] = $data[$field];
        }
        return $category;
    }

    /**
     * Get the list of objects in a category.
     *
     * @param int        $id         ID of category
     * @param string     $type       Type of category ('member', 'customer', 'supplier', 'product', 'contact')
     * @param int        $onlyids    Return only ids of objects (consume less memory)
     *
     * @return mixed
     *
     * @url GET {id}/objects
     */
    public function getObjects($id, $type, $onlyids = 0)
    {
		dol_syslog("getObjects($id, $type, $onlyids)", LOG_DEBUG);

		if (!DolibarrApiAccess::$user->rights->categorie->lire) {
			throw new RestException(401);
		}

        if (empty($type))
        {
			throw new RestException(500, 'The "type" parameter is required.');
        }

        $result = $this->category->fetch($id);
        if (!$result) {
            throw new RestException(404, 'category not found');
        }

		if (!DolibarrApi::_checkAccessToResource('categorie', $this->category->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$result = $this->category->getObjectsInCateg($type, $onlyids);

		if ($result < 0) {
			throw new RestException(503, 'Error when retrieving objects list : '.$this->category->error);
		}

		$objects = $result;
        $cleaned_objects = array();
        if ($type == 'member') {
			$objects_api = new Members();
		} elseif ($type == 'customer' || $type == 'supplier') {
			$objects_api = new Thirdparties();
		} elseif ($type == 'product') {
			$objects_api = new Products();
		} elseif ($type == 'contact') {
			$objects_api = new Contacts();
		}
		if (is_object($objects_api))
		{
    		foreach ($objects as $obj) {
    			$cleaned_objects[] = $objects_api->_cleanObjectDatas($obj);
    		}
		}

		return $cleaned_objects;
    }
}
