<?php
/* Copyright (C) 2007-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2014      Florian Henry   <florian.henry@open-concept.pro>
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

/**
 * \file htdocs/product/class/productcustomerprice.class.php
 * \ingroup produit
 * \brief File of class to manage predefined price products or services by customer
 */
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';

/**
 * File of class to manage predefined price products or services by customer
 */
class Productcustomerprice extends CommonObject
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'product_customer_price';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'product_customer_price';

	/**
	 * @var int Entity
	 */
	public $entity;

	public $datec = '';
	public $tms = '';

	/**
     * @var int ID
     */
	public $fk_product;

	/**
	 * @var int Thirdparty ID
	 */
    public $fk_soc;

	public $price;
	public $price_ttc;
	public $price_min;
	public $price_min_ttc;
	public $price_base_type;
	public $tva_tx;
	public $recuperableonly;
	public $localtax1_type;
	public $localtax1_tx;
	public $localtax2_type;
	public $localtax2_tx;

	/**
	 * @var int User ID
	 */
	public $fk_user;

	public $lines = array ();


	/**
	 * Constructor
	 *
	 * @param DoliDb $db handler
	 */
    public function __construct($db)
    {
        $this->db = $db;
    }

	/**
	 * Create object into database
	 *
	 * @param User $user that creates
	 * @param int $notrigger triggers after, 1=disable triggers
	 * @param int $forceupdateaffiliate update price on each soc child
	 * @return int <0 if KO, Id of created object if OK
	 */
    public function create($user, $notrigger = 0, $forceupdateaffiliate = 0)
    {

		global $conf, $langs;
		$error = 0;

		// Clean parameters

		if (isset($this->entity))
			$this->entity = trim($this->entity);
		if (isset($this->fk_product))
			$this->fk_product = trim($this->fk_product);
		if (isset($this->fk_soc))
			$this->fk_soc = trim($this->fk_soc);
		if (isset($this->price))
			$this->price = trim($this->price);
		if (isset($this->price_ttc))
			$this->price_ttc = trim($this->price_ttc);
		if (isset($this->price_min))
			$this->price_min = trim($this->price_min);
		if (isset($this->price_min_ttc))
			$this->price_min_ttc = trim($this->price_min_ttc);
		if (isset($this->price_base_type))
			$this->price_base_type = trim($this->price_base_type);
		if (isset($this->tva_tx))
			$this->tva_tx = trim($this->tva_tx);
		if (isset($this->recuperableonly))
			$this->recuperableonly = trim($this->recuperableonly);
		if (isset($this->localtax1_tx))
			$this->localtax1_tx = trim($this->localtax1_tx);
		if (isset($this->localtax2_tx))
			$this->localtax2_tx = trim($this->localtax2_tx);
		if (isset($this->fk_user))
			$this->fk_user = trim($this->fk_user);
		if (isset($this->import_key))
			$this->import_key = trim($this->import_key);

			// Check parameters
			// Put here code to add control on parameters values

		if ($this->price != '' || $this->price == 0) {
			if ($this->price_base_type == 'TTC') {
				$this->price_ttc = price2num($this->price, 'MU');
				$this->price = price2num($this->price) / (1 + ($this->tva_tx / 100));
				$this->price = price2num($this->price, 'MU');

				if ($this->price_min != '' || $this->price_min == 0) {
					$this->price_min_ttc = price2num($this->price_min, 'MU');
					$this->price_min = price2num($this->price_min) / (1 + ($this->tva_tx / 100));
					$this->price_min = price2num($this->price_min, 'MU');
				} else {
					$this->price_min = 0;
					$this->price_min_ttc = 0;
				}
			} else {
				$this->price = price2num($this->price, 'MU');
				$this->price_ttc = ($this->recuperableonly != 1) ? price2num($this->price) * (1 + ($this->tva_tx / 100)) : $this->price;
				$this->price_ttc = price2num($this->price_ttc, 'MU');

				if ($this->price_min != '' || $this->price_min == 0) {
					$this->price_min = price2num($this->price_min, 'MU');
					$this->price_min_ttc = price2num($this->price_min) * (1 + ($this->tva_tx / 100));
					$this->price_min_ttc = price2num($this->price_min_ttc, 'MU');
					// print 'X'.$newminprice.'-'.$price_min;
				} else {
					$this->price_min = 0;
					$this->price_min_ttc = 0;
				}
			}
		}

		// Insert request
		$sql = "INSERT INTO " . MAIN_DB_PREFIX . "product_customer_price(";
		$sql .= "entity,";
		$sql .= "datec,";
		$sql .= "fk_product,";
		$sql .= "fk_soc,";
		$sql .= "price,";
		$sql .= "price_ttc,";
		$sql .= "price_min,";
		$sql .= "price_min_ttc,";
		$sql .= "price_base_type,";
		$sql .= "default_vat_code,";
		$sql .= "tva_tx,";
		$sql .= "recuperableonly,";
		$sql .= "localtax1_type,";
		$sql .= "localtax1_tx,";
		$sql .= "localtax2_type,";
		$sql .= "localtax2_tx,";
		$sql .= "fk_user,";
		$sql .= "import_key";
		$sql .= ") VALUES (";
		$sql .= " " . $conf->entity . ",";
		$sql .= " '" . $this->db->idate(dol_now()) . "',";
		$sql .= " " . (! isset($this->fk_product) ? 'NULL' : "'" . $this->db->escape($this->fk_product) . "'") . ",";
		$sql .= " " . (! isset($this->fk_soc) ? 'NULL' : "'" . $this->db->escape($this->fk_soc) . "'") . ",";
		$sql .= " " . (empty($this->price) ? '0' : "'" . $this->db->escape($this->price) . "'") . ",";
		$sql .= " " . (empty($this->price_ttc) ? '0' : "'" . $this->db->escape($this->price_ttc) . "'") . ",";
		$sql .= " " . (empty($this->price_min) ? '0' : "'" . $this->db->escape($this->price_min) . "'") . ",";
		$sql .= " " . (empty($this->price_min_ttc) ? '0' : "'" . $this->db->escape($this->price_min_ttc) . "'") . ",";
		$sql .= " " . (! isset($this->price_base_type) ? 'NULL' : "'" . $this->db->escape($this->price_base_type) . "'") . ",";
		$sql .= " ".($this->default_vat_code ? "'".$this->db->escape($this->default_vat_code)."'" : "null").",";
		$sql .= " " . (! isset($this->tva_tx) ? 'NULL' : (empty($this->tva_tx)?0:$this->tva_tx)) . ",";
		$sql .= " " . (! isset($this->recuperableonly) ? 'NULL' : "'" . $this->db->escape($this->recuperableonly) . "'") . ",";
		$sql .= " " . (empty($this->localtax1_type) ? "'0'" : "'" . $this->db->escape($this->localtax1_type) . "'") . ",";
		$sql .= " " . (! isset($this->localtax1_tx) ? 'NULL' : (empty($this->localtax1_tx)?0:$this->localtax1_tx)) . ",";
		$sql .= " " . (empty($this->localtax2_type) ? "'0'" : "'" . $this->db->escape($this->localtax2_type) . "'") . ",";
		$sql .= " " . (! isset($this->localtax2_tx) ? 'NULL' : (empty($this->localtax2_tx)?0:$this->localtax2_tx)) . ",";
		$sql .= " " . $user->id . ",";
		$sql .= " " . (! isset($this->import_key) ? 'NULL' : "'" . $this->db->escape($this->import_key) . "'") . "";
		$sql .= ")";

		$this->db->begin();

		dol_syslog(get_class($this) . "::create", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (! $resql) {
			$error ++;
			$this->errors [] = "Error " . $this->db->lasterror();
		}

		if (! $error) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . "product_customer_price");

			if (! $notrigger) {
				// Uncomment this and change MYOBJECT to your own tag if you
				// want this action calls a trigger.

				// // Call triggers
				// include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
				// $interface=new Interfaces($this->db);
				// $result=$interface->run_triggers('MYOBJECT_CREATE',$this,$user,$langs,$conf);
				// if ($result < 0) { $error++; $this->errors=$interface->errors; }
				// // End call triggers
			}
		}

		if (! $error) {
			$result = $this->setPriceOnAffiliateThirdparty($user, $forceupdateaffiliate);
			if ($result < 0) {
				$error ++;
			}
		}

		// Commit or rollback
		if ($error) {
			foreach ($this->errors as $errmsg) {
				dol_syslog(get_class($this) . "::create " . $errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
			}
			$this->db->rollback();
			return - 1 * $error;
		} else {
			$this->db->commit();
			return $this->id;
		}
	}

	/**
	 * Load object in memory from the database
	 *
	 * @param int $id object
	 * @return int <0 if KO, >0 if OK
	 */
	public function fetch($id)
	{
		global $langs;

		$sql = "SELECT";
		$sql .= " t.rowid,";

		$sql .= " t.entity,";
		$sql .= " t.datec,";
		$sql .= " t.tms,";
		$sql .= " t.fk_product,";
		$sql .= " t.fk_soc,";
		$sql .= " t.price,";
		$sql .= " t.price_ttc,";
		$sql .= " t.price_min,";
		$sql .= " t.price_min_ttc,";
		$sql .= " t.price_base_type,";
		$sql .= " t.default_vat_code,";
		$sql .= " t.tva_tx,";
		$sql .= " t.recuperableonly,";
		$sql .= " t.localtax1_tx,";
		$sql .= " t.localtax2_tx,";
		$sql .= " t.fk_user,";
		$sql .= " t.import_key";

		$sql .= " FROM " . MAIN_DB_PREFIX . "product_customer_price as t";
		$sql .= " WHERE t.rowid = " . $id;

		dol_syslog(get_class($this) . "::fetch", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);

				$this->id = $obj->rowid;

				$this->entity = $obj->entity;
				$this->datec = $this->db->jdate($obj->datec);
				$this->tms = $this->db->jdate($obj->tms);
				$this->fk_product = $obj->fk_product;
				$this->fk_soc = $obj->fk_soc;
				$this->price = $obj->price;
				$this->price_ttc = $obj->price_ttc;
				$this->price_min = $obj->price_min;
				$this->price_min_ttc = $obj->price_min_ttc;
				$this->price_base_type = $obj->price_base_type;
				$this->default_vat_code = $obj->default_vat_code;
				$this->tva_tx = $obj->tva_tx;
				$this->recuperableonly = $obj->recuperableonly;
				$this->localtax1_tx = $obj->localtax1_tx;
				$this->localtax2_tx = $obj->localtax2_tx;
				$this->fk_user = $obj->fk_user;
				$this->import_key = $obj->import_key;
			}
			$this->db->free($resql);

			return 1;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			return - 1;
		}
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Load all customer prices in memory from database
	 *
	 * @param 	string 	$sortorder 	order
	 * @param 	string 	$sortfield 	field
	 * @param 	int 	$limit 		page
	 * @param 	int 	$offset 	offset
	 * @param 	array 	$filter 	Filter for select
	 * @return 	int 				<0 if KO, >0 if OK
	 */
	public function fetch_all($sortorder = '', $sortfield = '', $limit = 0, $offset = 0, $filter = array())
	{
        // phpcs:enable
		global $langs;

		if ( empty($sortfield)) $sortfield = "t.rowid";
		if ( empty($sortorder)) $sortorder = "DESC";

		$sql = "SELECT";
		$sql .= " t.rowid,";

		$sql .= " t.entity,";
		$sql .= " t.datec,";
		$sql .= " t.tms,";
		$sql .= " t.fk_product,";
		$sql .= " t.fk_soc,";
		$sql .= " t.price,";
		$sql .= " t.price_ttc,";
		$sql .= " t.price_min,";
		$sql .= " t.price_min_ttc,";
		$sql .= " t.price_base_type,";
		$sql .= " t.default_vat_code,";
		$sql .= " t.tva_tx,";
		$sql .= " t.recuperableonly,";
		$sql .= " t.localtax1_tx,";
		$sql .= " t.localtax2_tx,";
		$sql .= " t.localtax1_type,";
		$sql .= " t.localtax2_type,";
		$sql .= " t.fk_user,";
		$sql .= " t.import_key,";
		$sql .= " soc.nom as socname,";
		$sql .= " prod.ref as prodref";
		$sql .= " FROM " . MAIN_DB_PREFIX . "product_customer_price as t ";
		$sql .= " ," . MAIN_DB_PREFIX . "product as prod ";
		$sql .= " ," . MAIN_DB_PREFIX . "societe as soc ";
		$sql .= " WHERE soc.rowid=t.fk_soc ";
		$sql .= " AND prod.rowid=t.fk_product ";
		$sql .= " AND prod.entity IN (" . getEntity('product') . ")";
		$sql .= " AND t.entity IN (" . getEntity('productprice') . ")";

		// Manage filter
		if (count($filter) > 0) {
			foreach ($filter as $key => $value) {
				if (strpos($key, 'date')) 				// To allow $filter['YEAR(s.dated)']=>$year
				{
					$sql .= ' AND ' . $key . ' = \'' . $value . '\'';
				} elseif ($key == 'soc.nom') {
					$sql .= ' AND ' . $key . ' LIKE \'%' . $value . '%\'';
				} elseif ($key == 'prod.ref') {
					$sql .= ' AND ' . $key . ' LIKE \'%' . $value . '%\'';
				} else {
					$sql .= ' AND ' . $key . ' = ' . $value;
				}
			}
		}
		$sql.= $this->db->order($sortfield, $sortorder);
		if (! empty($limit)) $sql .= ' ' . $this->db->plimit($limit + 1, $offset);

		dol_syslog(get_class($this) . "::fetch_all", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {

			$this->lines = array ();
			$num = $this->db->num_rows($resql);

			while ( $obj = $this->db->fetch_object($resql) ) {

				$line = new PriceByCustomerLine();

				$line->id = $obj->rowid;

				$line->entity = $obj->entity;
				$line->datec = $this->db->jdate($obj->datec);
				$line->tms = $this->db->jdate($obj->tms);
				$line->fk_product = $obj->fk_product;
				$line->fk_soc = $obj->fk_soc;
				$line->price = $obj->price;
				$line->price_ttc = $obj->price_ttc;
				$line->price_min = $obj->price_min;
				$line->price_min_ttc = $obj->price_min_ttc;
				$line->price_base_type = $obj->price_base_type;
				$line->default_vat_code = $obj->default_vat_code;
				$line->tva_tx = $obj->tva_tx;
				$line->recuperableonly = $obj->recuperableonly;
				$line->localtax1_tx = $obj->localtax1_tx;
				$line->localtax2_tx = $obj->localtax2_tx;
				$line->localtax1_type = $obj->localtax1_type;
				$line->localtax2_type = $obj->localtax2_type;
				$line->fk_user = $obj->fk_user;
				$line->import_key = $obj->import_key;
				$line->socname = $obj->socname;
				$line->prodref = $obj->prodref;

				$this->lines [] = $line;
			}
			$this->db->free($resql);

			return $num;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			return - 1;
		}
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Load all objects in memory from database
	 *
	 * @param 	string 	$sortorder 	order
	 * @param 	string 	$sortfield 	field
	 * @param 	int 	$limit 		page
	 * @param 	int 	$offset 	offset
	 * @param 	array 	$filter 	Filter for sql request
	 * @return 	int 			<0 if KO, >0 if OK
	 */
	public function fetch_all_log($sortorder, $sortfield, $limit, $offset, $filter = array())
	{
        // phpcs:enable
		global $langs;

		if (! empty($sortfield)) $sortfield = "t.rowid";
		if (! empty($sortorder)) $sortorder = "DESC";

		$sql = "SELECT";
		$sql .= " t.rowid,";

		$sql .= " t.entity,";
		$sql .= " t.datec,";
		$sql .= " t.fk_product,";
		$sql .= " t.fk_soc,";
		$sql .= " t.price,";
		$sql .= " t.price_ttc,";
		$sql .= " t.price_min,";
		$sql .= " t.price_min_ttc,";
		$sql .= " t.price_base_type,";
		$sql .= " t.default_vat_code,";
		$sql .= " t.tva_tx,";
		$sql .= " t.recuperableonly,";
		$sql .= " t.localtax1_tx,";
		$sql .= " t.localtax2_tx,";
		$sql .= " t.fk_user,";
		$sql .= " t.import_key,";
		$sql .= " soc.nom as socname,";
		$sql .= " prod.ref as prodref";
		$sql .= " FROM " . MAIN_DB_PREFIX . "product_customer_price_log as t ";
		$sql .= " ," . MAIN_DB_PREFIX . "product as prod ";
		$sql .= " ," . MAIN_DB_PREFIX . "societe as soc ";
		$sql .= " WHERE soc.rowid=t.fk_soc ";
		$sql .= " AND prod.rowid=t.fk_product ";
		$sql .= " AND prod.entity IN (" . getEntity('product') . ")";
		$sql .= " AND t.entity IN (" . getEntity('productprice') . ")";

		// Manage filter
		if (count($filter) > 0) {
			foreach ($filter as $key => $value) {
				if (strpos($key, 'date')) 				// To allow $filter['YEAR(s.dated)']=>$year
				{
					$sql .= ' AND ' . $key . ' = \'' . $value . '\'';
				} elseif ($key == 'soc.nom') {
					$sql .= ' AND ' . $key . ' LIKE \'%' . $value . '%\'';
				} else {
					$sql .= ' AND ' . $key . ' = ' . $value;
				}
			}
		}

		$sql.= $this->db->order($sortfield, $sortorder);
		if (! empty($limit)) $sql .= ' ' . $this->db->plimit($limit + 1, $offset);

		dol_syslog(get_class($this) . "::fetch_all_log", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {

			$this->lines = array ();
			$num = $this->db->num_rows($resql);

			while ( $obj = $this->db->fetch_object($resql) ) {

				$line = new PriceByCustomerLine();

				$line->id = $obj->rowid;

				$line->entity = $obj->entity;
				$line->datec = $this->db->jdate($obj->datec);
				$line->tms = $this->db->jdate($obj->tms);
				$line->fk_product = $obj->fk_product;
				$line->fk_soc = $obj->fk_soc;
				$line->price = $obj->price;
				$line->price_ttc = $obj->price_ttc;
				$line->price_min = $obj->price_min;
				$line->price_min_ttc = $obj->price_min_ttc;
				$line->price_base_type = $obj->price_base_type;
				$line->default_vat_code = $obj->default_vat_code;
				$line->tva_tx = $obj->tva_tx;
				$line->recuperableonly = $obj->recuperableonly;
				$line->localtax1_tx = $obj->localtax1_tx;
				$line->localtax2_tx = $obj->localtax2_tx;
				$line->fk_user = $obj->fk_user;
				$line->import_key = $obj->import_key;
				$line->socname = $obj->socname;
				$line->prodref = $obj->prodref;

				$this->lines [] = $line;
			}
			$this->db->free($resql);

			return $num;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			return - 1;
		}
	}

	/**
	 * Update object into database
	 *
	 * @param User $user that modifies
	 * @param int $notrigger triggers after, 1=disable triggers
	 * @param int $forceupdateaffiliate update price on each soc child
	 * @return int <0 if KO, >0 if OK
	 */
    public function update($user = 0, $notrigger = 0, $forceupdateaffiliate = 0)
    {

		global $conf, $langs;
		$error = 0;

		// Clean parameters

		if (isset($this->entity))
			$this->entity = trim($this->entity);
		if (isset($this->fk_product))
			$this->fk_product = trim($this->fk_product);
		if (isset($this->fk_soc))
			$this->fk_soc = trim($this->fk_soc);
		if (isset($this->price))
			$this->price = trim($this->price);
		if (isset($this->price_ttc))
			$this->price_ttc = trim($this->price_ttc);
		if (isset($this->price_min))
			$this->price_min = trim($this->price_min);
		if (isset($this->price_min_ttc))
			$this->price_min_ttc = trim($this->price_min_ttc);
		if (isset($this->price_base_type))
			$this->price_base_type = trim($this->price_base_type);
		if (isset($this->tva_tx))
			$this->tva_tx = trim($this->tva_tx);
		if (isset($this->recuperableonly))
			$this->recuperableonly = trim($this->recuperableonly);
		if (isset($this->localtax1_tx))
			$this->localtax1_tx = trim($this->localtax1_tx);
		if (isset($this->localtax2_tx))
			$this->localtax2_tx = trim($this->localtax2_tx);
		if (isset($this->fk_user))
			$this->fk_user = trim($this->fk_user);
		if (isset($this->import_key))
			$this->import_key = trim($this->import_key);

			// Check parameters
			// Put here code to add a control on parameters values

		if ($this->price != '' || $this->price == 0) {
			if ($this->price_base_type == 'TTC') {
				$this->price_ttc = price2num($this->price, 'MU');
				$this->price = price2num($this->price) / (1 + ($this->tva_tx / 100));
				$this->price = price2num($this->price, 'MU');

				if ($this->price_min != '' || $this->price_min == 0) {
					$this->price_min_ttc = price2num($this->price_min, 'MU');
					$this->price_min = price2num($this->price_min) / (1 + ($this->tva_tx / 100));
					$this->price_min = price2num($this->price_min, 'MU');
				} else {
					$this->price_min = 0;
					$this->price_min_ttc = 0;
				}
			} else {
				$this->price = price2num($this->price, 'MU');
				$this->price_ttc = ($this->recuperableonly != 1) ? price2num($this->price) * (1 + ($this->tva_tx / 100)) : $this->price;
				$this->price_ttc = price2num($this->price_ttc, 'MU');

				if ($this->price_min != '' || $this->price_min == 0) {
					$this->price_min = price2num($this->price_min, 'MU');
					$this->price_min_ttc = price2num($this->price_min) * (1 + ($this->tva_tx / 100));
					$this->price_min_ttc = price2num($this->price_min_ttc, 'MU');
					// print 'X'.$newminprice.'-'.$price_min;
				} else {
					$this->price_min = 0;
					$this->price_min_ttc = 0;
				}
			}
		}

		// Do a copy of current record into log table
		// Insert request
		$sql = "INSERT INTO " . MAIN_DB_PREFIX . "product_customer_price_log(";

		$sql .= "entity,";
		$sql .= "datec,";
		$sql .= "fk_product,";
		$sql .= "fk_soc,";
		$sql .= "price,";
		$sql .= "price_ttc,";
		$sql .= "price_min,";
		$sql .= "price_min_ttc,";
		$sql .= "price_base_type,";
		$sql .= "default_vat_code,";
		$sql .= "tva_tx,";
		$sql .= "recuperableonly,";
		$sql .= "localtax1_tx,";
		$sql .= "localtax2_tx,";
		$sql .= "localtax1_type,";
		$sql .= "localtax2_type,";
		$sql .= "fk_user,";
		$sql .= "import_key";

		$sql .= ") 		";
		$sql .= "SELECT";

		$sql .= " t.entity,";
		$sql .= " t.datec,";
		$sql .= " t.fk_product,";
		$sql .= " t.fk_soc,";
		$sql .= " t.price,";
		$sql .= " t.price_ttc,";
		$sql .= " t.price_min,";
		$sql .= " t.price_min_ttc,";
		$sql .= " t.price_base_type,";
		$sql .= " t.default_vat_code,";
		$sql .= " t.tva_tx,";
		$sql .= " t.recuperableonly,";
		$sql .= " t.localtax1_tx,";
		$sql .= " t.localtax2_tx,";
		$sql .= " t.localtax1_type,";
		$sql .= " t.localtax2_type,";
		$sql .= " t.fk_user,";
		$sql .= " t.import_key";

		$sql .= " FROM " . MAIN_DB_PREFIX . "product_customer_price as t";
		$sql .= " WHERE t.rowid = " . $this->id;

		$this->db->begin();
		dol_syslog(get_class($this) . "::update", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (! $resql) {
			$error ++;
			$this->errors [] = "Error " . $this->db->lasterror();
		}

		// Update request
		$sql = "UPDATE " . MAIN_DB_PREFIX . "product_customer_price SET";

		$sql .= " entity=" . $conf->entity . ",";
		$sql .= " datec='" . $this->db->idate(dol_now()) . "',";
		$sql .= " tms=" . (dol_strlen($this->tms) != 0 ? "'" . $this->db->idate($this->tms) . "'" : 'null') . ",";
		$sql .= " fk_product=" . (isset($this->fk_product) ? $this->fk_product : "null") . ",";
		$sql .= " fk_soc=" . (isset($this->fk_soc) ? $this->fk_soc : "null") . ",";
		$sql .= " price=" . (isset($this->price) ? $this->price : "null") . ",";
		$sql .= " price_ttc=" . (isset($this->price_ttc) ? $this->price_ttc : "null") . ",";
		$sql .= " price_min=" . (isset($this->price_min) ? $this->price_min : "null") . ",";
		$sql .= " price_min_ttc=" . (isset($this->price_min_ttc) ? $this->price_min_ttc : "null") . ",";
		$sql .= " price_base_type=" . (isset($this->price_base_type) ? "'" . $this->db->escape($this->price_base_type) . "'" : "null") . ",";
		$sql .= " default_vat_code = ".($this->default_vat_code ? "'".$this->db->escape($this->default_vat_code)."'" : "null").",";
		$sql .= " tva_tx=" . (isset($this->tva_tx) ? (empty($this->tva_tx)?0:$this->tva_tx) : "null") . ",";
		$sql .= " recuperableonly=" . (isset($this->recuperableonly) ? $this->recuperableonly : "null") . ",";
		$sql .= " localtax1_tx=" . (isset($this->localtax1_tx) ? (empty($this->localtax1_tx)?0:$this->localtax1_tx) : "null") . ",";
		$sql .= " localtax2_tx=" . (isset($this->localtax2_tx) ? (empty($this->localtax2_tx)?0:$this->localtax2_tx) : "null") . ",";
		$sql .= " localtax1_type=" . (! empty($this->localtax1_type) ? "'".$this->db->escape($this->localtax1_type)."'": "'0'") . ",";
		$sql .= " localtax2_type=" . (! empty($this->localtax2_type) ? "'".$this->db->escape($this->localtax2_type)."'": "'0'") . ",";
		$sql .= " fk_user=" . $user->id . ",";
		$sql .= " import_key=" . (isset($this->import_key) ? "'" . $this->db->escape($this->import_key) . "'" : "null") . "";

		$sql .= " WHERE rowid=" . $this->id;

		dol_syslog(get_class($this) . "::update", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (! $resql) {
			$error ++;
			$this->errors [] = "Error " . $this->db->lasterror();
		}

		if (! $error) {
			if (! $notrigger) {
				// Call triggers
				include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
				$interface=new Interfaces($this->db);
				$result=$interface->run_triggers('PRODUCT_CUSTOMER_PRICE_UPDATE', $this, $user, $langs, $conf);
				if ($result < 0) { $error++; $this->errors=$interface->errors; }
				// End call triggers
			}
		}

		if (! $error) {
			$result = $this->setPriceOnAffiliateThirdparty($user, $forceupdateaffiliate);
			if ($result < 0) {
				$error ++;
			}
		}

		// Commit or rollback
		if ($error) {
			foreach ($this->errors as $errmsg) {
				dol_syslog(get_class($this) . "::update " . $errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
			}
			$this->db->rollback();
			return - 1 * $error;
		} else {
			$this->db->commit();
			return 1;
		}
    }

	/**
	 * Force update price on child price
	 *
	 * @param User $user that modifies
	 * @param int $forceupdateaffiliate update price on each soc child
	 * @return int <0 if KO, >0 if OK
	 */
    public function setPriceOnAffiliateThirdparty($user, $forceupdateaffiliate)
    {

		$error = 0;

		// Find all susidiaries
		$sql = "SELECT s.rowid";
		$sql .= " FROM " . MAIN_DB_PREFIX . "societe as s";
		$sql .= " WHERE s.parent = " . $this->fk_soc;
		$sql .= " AND s.entity IN (" . getEntity('societe') . ")";

		dol_syslog(get_class($this) . "::setPriceOnAffiliateThirdparty", LOG_DEBUG);
		$resql = $this->db->query($sql);

		if ($resql) {

			$this->lines = array ();
			$num = $this->db->num_rows($resql);

			while ( ($obj = $this->db->fetch_object($resql)) && (empty($error)) ) {

				// find if there is an existing line for the product and the subsidiaries
				$prodsocprice = new Productcustomerprice($this->db);

				$filter = array (
					't.fk_product' => $this->fk_product,'t.fk_soc' => $obj->rowid
				);

				$result = $prodsocprice->fetch_all('', '', 0, 0, $filter);
				if ($result < 0) {
					$error ++;
					$this->error = $prodsocprice->error;
				} else {

					// There is one line
					if (count($prodsocprice->lines) > 0) {
						// If force update => Update
						if (! empty($forceupdateaffiliate)) {

							$prodsocpriceupd = new Productcustomerprice($this->db);
							$prodsocpriceupd->fetch($prodsocprice->lines [0]->id);

							$prodsocpriceupd->price = $this->price;
							$prodsocpriceupd->price_min = $this->price_min;
							$prodsocpriceupd->price_base_type = $this->price_base_type;
							$prodsocpriceupd->tva_tx = $this->tva_tx;
							$prodsocpriceupd->recuperableonly = $this->recuperableonly;

							$resultupd = $prodsocpriceupd->update($user, 0, $forceupdateaffiliate);
							if ($result < 0) {
								$error ++;
								$this->error = $prodsocpriceupd->error;
							}
						}
					} else {
						// If line do not exits then create it
						$prodsocpricenew = new Productcustomerprice($this->db);
						$prodsocpricenew->fk_soc = $obj->rowid;
						$prodsocpricenew->fk_product = $this->fk_product;
						$prodsocpricenew->price = $this->price;
						$prodsocpricenew->price_min = $this->price_min;
						$prodsocpricenew->price_base_type = $this->price_base_type;
						$prodsocpricenew->tva_tx = $this->tva_tx;
						$prodsocpricenew->recuperableonly = $this->recuperableonly;

						$resultupd = $prodsocpricenew->create($user, 0, $forceupdateaffiliate);
						if ($result < 0) {
							$error ++;
							$this->error = $prodsocpriceupd->error;
						}
					}
				}
			}
			$this->db->free($resql);

			if (empty($error)) {
				return 1;
			} else {
				return - 1;
			}
		} else {
			$this->error = "Error " . $this->db->lasterror();
			return - 1;
		}
    }

	/**
	 * Delete object in database
	 *
	 * @param User $user that deletes
	 * @param int $notrigger triggers after, 1=disable triggers
	 * @return int <0 if KO, >0 if OK
	 */
    public function delete($user, $notrigger = 0)
    {

		global $conf, $langs;
		$error = 0;

		$this->db->begin();

		if (! $error) {
			if (! $notrigger) {
				// Uncomment this and change MYOBJECT to your own tag if you
				// want this action calls a trigger.

				// // Call triggers
				// include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
				// $interface=new Interfaces($this->db);
				// $result=$interface->run_triggers('MYOBJECT_DELETE',$this,$user,$langs,$conf);
				// if ($result < 0) { $error++; $this->errors=$interface->errors; }
				// // End call triggers
			}
		}

		if (! $error) {
			$sql = "DELETE FROM " . MAIN_DB_PREFIX . "product_customer_price";
			$sql .= " WHERE rowid=" . $this->id;

			dol_syslog(get_class($this) . "::delete", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (! $resql) {
				$error ++;
				$this->errors [] = "Error " . $this->db->lasterror();
			}
		}

		// Commit or rollback
		if ($error) {
			foreach ($this->errors as $errmsg) {
				dol_syslog(get_class($this) . "::delete " . $errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
			}
			$this->db->rollback();
			return - 1 * $error;
		} else {
			$this->db->commit();
			return 1;
		}
    }

	/**
	 * Load an object from its id and create a new one in database
	 *
	 * @param	User	$user		User making the clone
	 * @param   int     $fromid     ID of object to clone
	 * @return  int                 id of clone
	 */
    public function createFromClone(User $user, $fromid)
    {
		$error = 0;

		$object = new Productcustomerprice($this->db);

		$this->db->begin();

		// Load source object
		$object->fetch($fromid);
		$object->id = 0;
		$object->statut = 0;

		// Clear fields
		// ...

		// Create clone
		$object->context['createfromclone']='createfromclone';
		$result = $object->create($user);

		// Other options
		if ($result < 0) {
			$this->error = $object->error;
			$this->errors=array_merge($this->errors, $object->errors);
			$error++;
		}

		if (! $error) {

		}

		unset($object->context['createfromclone']);

		// End
		if (! $error) {
			$this->db->commit();
			return $object->id;
		} else {
			$this->db->rollback();
			return - 1;
		}
    }

	/**
	 * Initialise object with example values
	 * Id must be 0 if object instance is a specimen
	 *
	 * @return void
	 */
    public function initAsSpecimen()
    {

		$this->id = 0;

		$this->entity = '';
		$this->datec = '';
		$this->tms = '';
		$this->fk_product = '';
		$this->fk_soc = '';
		$this->price = '';
		$this->price_ttc = '';
		$this->price_min = '';
		$this->price_min_ttc = '';
		$this->price_base_type = '';
		$this->default_vat_code = '';
		$this->tva_tx = '';
		$this->recuperableonly = '';
		$this->localtax1_tx = '';
		$this->localtax2_tx = '';
		$this->fk_user = '';
		$this->import_key = '';
    }
}

/**
 * File of class to manage predefined price products or services by customer lines
 */
class PriceByCustomerLine
{
	/**
	 * @var int ID
	 */
	public $id;

	/**
	 * @var int Entity
	 */
	public $entity;

	public $datec = '';
	public $tms = '';

	/**
     * @var int ID
     */
	public $fk_product;

	/**
	 * @var int Thirdparty ID
	 */
    public $fk_soc;

	public $price;
	public $price_ttc;
	public $price_min;
	public $price_min_ttc;
	public $price_base_type;
	public $default_vat_code;
	public $tva_tx;
	public $recuperableonly;
	public $localtax1_tx;
	public $localtax2_tx;

    /**
     * @var int User ID
     */
    public $fk_user;

    public $import_key;
    public $socname;
    public $prodref;
}
