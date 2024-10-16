<?php
/* Copyright (C) 2007-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2014      Florian Henry   <florian.henry@open-concept.pro>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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

/**
 * \file htdocs/product/class/productcustomerprice.class.php
 * \ingroup produit
 * \brief File of class to manage predefined price products or services by customer
 */
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobjectline.class.php';

/**
 * File of class to manage predefined price products or services by customer
 */
class ProductCustomerPrice extends CommonObject
{
	/**
	 * @var array<string,array{type:string,label:string,enabled:int<0,2>|string,position:int,notnull?:int,visible:int<-2,5>|string,noteditable?:int<0,1>,default?:string,index?:int,foreignkey?:string,searchall?:int<0,1>,isameasure?:int<0,1>,css?:string,csslist?:string,help?:string,showoncombobox?:int<0,2>,disabled?:int<0,1>,arrayofkeyval?:array<int|string,string>,comment?:string,validate?:int<0,1>}>  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields = array(
		'ref' => array('type' => 'varchar(128)', 'label' => 'Ref', 'enabled' => 1, 'visible' => 4, 'position' => 10, 'notnull' => 1, 'default' => '(PROV)', 'index' => 1, 'searchall' => 1, 'comment' => "Reference of object", 'showoncombobox' => 1, 'noteditable' => 1),
		'fk_product' => array('type' => 'integer:Product:product/class/product.class.php:0', 'label' => 'Product', 'enabled' => '$conf->product->enabled', 'visible' => 1, 'position' => 35, 'notnull' => 1, 'index' => 1, 'comment' => "Product to produce", 'css' => 'maxwidth300', 'csslist' => 'tdoverflowmax100', 'picto' => 'product'),
		'ref_customer' => array('type' => 'varchar(128)', 'label' => 'RefCustomer', 'enabled' => 1, 'visible' => 4, 'position' => 10, 'notnull' => 1,),
		'datec' => array('type' => 'datetime', 'label' => 'AppliedPricesFrom', 'enabled' => 1, 'visible' => 1, 'position' => 500, 'notnull' => 1,),
		'price_base_type' => array('type' => 'varchar(255)', 'label' => 'PriceBase', 'enabled' => 1, 'visible' => 1, 'position' => 11, 'notnull' => -1, 'comment' => 'Price Base Type'),
		'tva_tx' => array('type' => 'decimal(20,6)', 'label' => 'VAT', 'enabled' => 1, 'visible' => 1, 'position' => 12, 'notnull' => -1, 'comment' => 'TVA Tax Rate'),
		'price' => array('type' => 'decimal(20,6)', 'label' => 'HT', 'enabled' => 1, 'visible' => 1, 'position' => 8, 'notnull' => -1, 'comment' => 'Price HT'),
		'price_ttc' => array('type' => 'decimal(20,6)', 'label' => 'TTC', 'enabled' => 1, 'visible' => 1, 'position' => 8, 'notnull' => -1, 'comment' => 'Price TTC'),
		'price_min' => array('type' => 'decimal(20,6)', 'label' => 'MinPriceHT', 'enabled' => 1, 'visible' => 1, 'position' => 9, 'notnull' => -1, 'comment' => 'Minimum Price'),
		'price_min_ttc' => array('type' => 'decimal(20,6)', 'label' => 'MinPriceTTC', 'enabled' => 1, 'visible' => 1, 'position' => 10, 'notnull' => -1, 'comment' => 'Minimum Price TTC'),
		'price_label' => array('type' => 'varchar(255)', 'label' => 'PriceLabel', 'enabled' => 1, 'visible' => 1, 'position' => 20, 'notnull' => -1, 'comment' => 'Price Label'),
		'fk_user' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserModif', 'enabled' => 1, 'visible' => 1, 'position' => 510, 'notnull' => 1, 'foreignkey' => 'user.rowid', 'csslist' => 'tdoverflowmax100'),
	);

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

	/**
	 * @var int ID
	 */
	public $fk_product;

	/**
	 * @var int Thirdparty ID
	 */
	public $fk_soc;

	/**
	 * @var string Customer reference
	 */
	public $ref_customer;

	public $price;
	public $price_ttc;
	public $price_min;
	public $price_min_ttc;
	public $price_base_type;
	public $default_vat_code;
	public $tva_tx;
	public $recuperableonly;
	public $localtax1_type;
	public $localtax1_tx;
	public $localtax2_type;
	public $localtax2_tx;
	public $price_label;

	/**
	 * @var int User ID
	 */
	public $fk_user;

	/**
	 * @var PriceByCustomerLine[]
	 */
	public $lines = array();


	/**
	 * Constructor
	 *
	 * @param DoliDB $db handler
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
	 * @return int Return integer <0 if KO, Id of created object if OK
	 */
	public function create($user, $notrigger = 0, $forceupdateaffiliate = 0)
	{
		global $conf, $langs;
		$error = 0;

		// Clean parameters

		if (isset($this->entity)) {
			$this->entity = (int) $this->entity;
		}
		if (isset($this->fk_product)) {
			$this->fk_product = (int) $this->fk_product;
		}
		if (isset($this->fk_soc)) {
			$this->fk_soc = (int) $this->fk_soc;
		}
		if (isset($this->ref_customer)) {
			$this->ref_customer = trim($this->ref_customer);
		}
		if (isset($this->price)) {
			$this->price = trim($this->price);
		}
		if (isset($this->price_ttc)) {
			$this->price_ttc = trim($this->price_ttc);
		}
		if (isset($this->price_min)) {
			$this->price_min = trim($this->price_min);
		}
		if (isset($this->price_min_ttc)) {
			$this->price_min_ttc = trim($this->price_min_ttc);
		}
		if (isset($this->price_base_type)) {
			$this->price_base_type = trim($this->price_base_type);
		}
		if (isset($this->tva_tx)) {
			$this->tva_tx = (float) $this->tva_tx;
		}
		if (isset($this->recuperableonly)) {
			$this->recuperableonly = trim($this->recuperableonly);
		}
		if (isset($this->localtax1_tx)) {
			$this->localtax1_tx = trim($this->localtax1_tx);
		}
		if (isset($this->localtax2_tx)) {
			$this->localtax2_tx = trim($this->localtax2_tx);
		}
		if (isset($this->fk_user)) {
			$this->fk_user = (int) $this->fk_user;
		}
		if (isset($this->price_label)) {
			$this->price_label = trim($this->price_label);
		}
		if (isset($this->import_key)) {
			$this->import_key = trim($this->import_key);
		}

		// Check parameters
		// Put here code to add control on parameters values

		if ($this->price != '' || $this->price == 0) {
			if ($this->price_base_type == 'TTC') {
				$this->price_ttc = price2num($this->price, 'MU');
				$this->price = (float) price2num($this->price) / (1 + ($this->tva_tx / 100));
				$this->price = price2num($this->price, 'MU');

				if ($this->price_min != '' || $this->price_min == 0) {
					$this->price_min_ttc = price2num($this->price_min, 'MU');
					$this->price_min = (float) price2num($this->price_min) / (1 + ($this->tva_tx / 100));
					$this->price_min = price2num($this->price_min, 'MU');
				} else {
					$this->price_min = 0;
					$this->price_min_ttc = 0;
				}
			} else {
				$this->price = price2num($this->price, 'MU');
				$this->price_ttc = ($this->recuperableonly != 1) ? (float) price2num($this->price) * (1 + ($this->tva_tx / 100)) : $this->price;
				$this->price_ttc = price2num($this->price_ttc, 'MU');

				if ($this->price_min != '' || $this->price_min == 0) {
					$this->price_min = price2num($this->price_min, 'MU');
					$this->price_min_ttc = (float) price2num($this->price_min) * (1 + ($this->tva_tx / 100));
					$this->price_min_ttc = price2num($this->price_min_ttc, 'MU');
					// print 'X'.$newminprice.'-'.$price_min;
				} else {
					$this->price_min = 0;
					$this->price_min_ttc = 0;
				}
			}
		}

		// Insert request
		$sql = "INSERT INTO ".$this->db->prefix()."product_customer_price(";
		$sql .= "entity,";
		$sql .= "datec,";
		$sql .= "fk_product,";
		$sql .= "fk_soc,";
		$sql .= 'ref_customer,';
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
		$sql .= "price_label,";
		$sql .= "import_key";
		$sql .= ") VALUES (";
		$sql .= " ".((int) $conf->entity).",";
		$sql .= " '".$this->db->idate(dol_now())."',";
		$sql .= " ".(!isset($this->fk_product) ? 'NULL' : "'".$this->db->escape($this->fk_product)."'").",";
		$sql .= " ".(!isset($this->fk_soc) ? 'NULL' : "'".$this->db->escape($this->fk_soc)."'").",";
		$sql .= " ".(!isset($this->ref_customer) ? 'NULL' : "'".$this->db->escape($this->ref_customer)."'").",";
		$sql .= " ".(empty($this->price) ? '0' : "'".$this->db->escape($this->price)."'").",";
		$sql .= " ".(empty($this->price_ttc) ? '0' : "'".$this->db->escape($this->price_ttc)."'").",";
		$sql .= " ".(empty($this->price_min) ? '0' : "'".$this->db->escape($this->price_min)."'").",";
		$sql .= " ".(empty($this->price_min_ttc) ? '0' : "'".$this->db->escape($this->price_min_ttc)."'").",";
		$sql .= " ".(!isset($this->price_base_type) ? 'NULL' : "'".$this->db->escape($this->price_base_type)."'").",";
		$sql .= " ".($this->default_vat_code ? "'".$this->db->escape($this->default_vat_code)."'" : "null").",";
		$sql .= " ".(!isset($this->tva_tx) ? 'NULL' : (empty($this->tva_tx) ? 0 : $this->tva_tx)).",";
		$sql .= " ".(!isset($this->recuperableonly) ? 'NULL' : "'".$this->db->escape($this->recuperableonly)."'").",";
		$sql .= " ".(empty($this->localtax1_type) ? "'0'" : "'".$this->db->escape($this->localtax1_type)."'").",";
		$sql .= " ".(!isset($this->localtax1_tx) ? 'NULL' : (empty($this->localtax1_tx) ? 0 : $this->localtax1_tx)).",";
		$sql .= " ".(empty($this->localtax2_type) ? "'0'" : "'".$this->db->escape($this->localtax2_type)."'").",";
		$sql .= " ".(!isset($this->localtax2_tx) ? 'NULL' : (empty($this->localtax2_tx) ? 0 : $this->localtax2_tx)).",";
		$sql .= " ".((int) $user->id).",";
		$sql .=  " ".(!isset($this->price_label) ? 'NULL' : "'".$this->db->escape($this->price_label)."'").",";
		$sql .= " ".(!isset($this->import_key) ? 'NULL' : "'".$this->db->escape($this->import_key)."'");
		$sql .= ")";

		$this->db->begin();

		dol_syslog(get_class($this)."::create", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++;
			$this->errors [] = "Error ".$this->db->lasterror();
		}

		if (!$error) {
			$this->id = $this->db->last_insert_id($this->db->prefix()."product_customer_price");

			if (!$notrigger) {
				$result = $this->call_trigger('PRODUCT_CUSTOMER_PRICE_CREATE', $user);
				if ($result < 0) {
					$error++;
				}
			}
		}

		if (!$error) {
			$result = $this->setPriceOnAffiliateThirdparty($user, $forceupdateaffiliate);
			if ($result < 0) {
				$error++;
			}
		}

		// Commit or rollback
		if ($error) {
			foreach ($this->errors as $errmsg) {
				dol_syslog(get_class($this)."::create ".$errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
			}
			$this->db->rollback();
			return -1 * $error;
		} else {
			$this->db->commit();
			return $this->id;
		}
	}

	/**
	 * Load object in memory from the database
	 *
	 * @param 	int 	$id 	ID of customer price
	 * @return 	int 			Return integer <0 if KO, 0 if not found, >0 if OK
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
		$sql .= " t.ref_customer,";
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
		$sql .= " t.price_label,";
		$sql .= " t.import_key";
		$sql .= " FROM ".$this->db->prefix()."product_customer_price as t";
		$sql .= " WHERE t.rowid = ".((int) $id);

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
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
				$this->ref_customer = $obj->ref_customer;
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
				$this->price_label = $obj->price_label;
				$this->import_key = $obj->import_key;

				$this->db->free($resql);

				return 1;
			} else {
				$this->db->free($resql);

				return 0;
			}
		} else {
			$this->error = "Error ".$this->db->lasterror();
			return -1;
		}
	}

	/**
	 * Load all customer prices in memory from database
	 *
	 * @param 	string 			$sortorder 	Sort order
	 * @param 	string 			$sortfield 	Sort field
	 * @param 	int 			$limit 		Limit page
	 * @param 	int 			$offset 	offset
	 * @param 	string|array 	$filter 	Filter USF.
	 * @return 	int 						Return integer <0 if KO, >0 if OK
	 * @since dolibarr v17
	 */
	public function fetchAll($sortorder = '', $sortfield = '', $limit = 0, $offset = 0, $filter = '')
	{
		if (empty($sortfield)) {
			$sortfield = "t.rowid";
		}
		if (empty($sortorder)) {
			$sortorder = "DESC";
		}

		$sql = "SELECT";
		$sql .= " t.rowid,";
		$sql .= " t.entity,";
		$sql .= " t.datec,";
		$sql .= " t.tms,";
		$sql .= " t.fk_product,";
		$sql .= " t.fk_soc,";
		$sql .= " t.ref_customer,";
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
		$sql .= " t.price_label,";
		$sql .= " t.import_key,";
		$sql .= " soc.nom as socname,";
		$sql .= " prod.ref as prodref";
		$sql .= " FROM ".$this->db->prefix()."product_customer_price as t,";
		$sql .= " ".$this->db->prefix()."product as prod,";
		$sql .= " ".$this->db->prefix()."societe as soc";
		$sql .= " WHERE soc.rowid=t.fk_soc ";
		$sql .= " AND prod.rowid=t.fk_product ";
		$sql .= " AND prod.entity IN (".getEntity('product').")";
		$sql .= " AND t.entity IN (".getEntity('productprice').")";

		// Manage filter
		if (is_array($filter)) {
			if (count($filter) > 0) {
				foreach ($filter as $key => $value) {
					if (strpos($key, 'date')) {				// To allow $filter['YEAR(s.dated)']=>$year
						$sql .= " AND ".$this->db->sanitize($key)." = '".$this->db->escape($value)."'";
					} elseif ($key == 'soc.nom') {
						$sql .= " AND ".$this->db->sanitize($key)." LIKE '%".$this->db->escape($this->db->escapeforlike($value))."%'";
					} elseif ($key == 'prod.ref' || $key == 'prod.label') {
						$sql .= " AND ".$this->db->sanitize($key)." LIKE '%".$this->db->escape($this->db->escapeforlike($value))."%'";
					} elseif ($key == 't.price' || $key == 't.price_ttc') {
						$sql .= " AND ".$this->db->sanitize($key)." = ".((float) price2num($value));
					} else {
						$sql .= " AND ".$this->db->sanitize($key)." = ".((int) $value);
					}
				}
			}

			$filter = '';
		}

		// Manage filter
		$errormessage = '';
		$sql .= forgeSQLFromUniversalSearchCriteria($filter, $errormessage);
		if ($errormessage) {
			$this->errors[] = $errormessage;
			dol_syslog(__METHOD__.' '.implode(',', $this->errors), LOG_ERR);
			return -1;
		}

		$sql .= $this->db->order($sortfield, $sortorder);
		if (!empty($limit)) {
			$sql .= $this->db->plimit($limit + 1, $offset);
		}

		dol_syslog(get_class($this)."::fetchAll", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$this->lines = array();
			$num = $this->db->num_rows($resql);

			while ($obj = $this->db->fetch_object($resql)) {
				$line = new PriceByCustomerLine($this->db);

				$line->id = $obj->rowid;

				$line->entity = $obj->entity;
				$line->datec = $this->db->jdate($obj->datec);
				$line->tms = $this->db->jdate($obj->tms);
				$line->fk_product = $obj->fk_product;
				$line->fk_soc = $obj->fk_soc;
				$line->ref_customer = $obj->ref_customer;
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
				$line->price_label = $obj->price_label;
				$line->import_key = $obj->import_key;
				$line->socname = $obj->socname;
				$line->prodref = $obj->prodref;

				$this->lines[] = $line;
			}
			$this->db->free($resql);

			return $num;
		} else {
			$this->error = "Error ".$this->db->lasterror();
			return -1;
		}
	}

	/**
	 * Load all objects in memory from database
	 *
	 * @param 	string 	$sortorder 	order
	 * @param 	string 	$sortfield 	field
	 * @param 	int 	$limit 		page
	 * @param 	int 	$offset 	offset
	 * @param 	array 	$filter 	Filter for sql request
	 * @return 	int 			Return integer <0 if KO, >0 if OK
	 */
	public function fetchAllLog($sortorder, $sortfield, $limit, $offset, $filter = array())
	{
		if (!empty($sortfield)) {
			$sortfield = "t.rowid";
		}
		if (!empty($sortorder)) {
			$sortorder = "DESC";
		}

		$sql = "SELECT";
		$sql .= " t.rowid,";
		$sql .= " t.entity,";
		$sql .= " t.datec,";
		$sql .= " t.fk_product,";
		$sql .= " t.fk_soc,";
		$sql .= " t.ref_customer,";
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
		$sql .= " t.price_label,";
		$sql .= " t.import_key,";
		$sql .= " soc.nom as socname,";
		$sql .= " prod.ref as prodref";
		$sql .= " FROM ".$this->db->prefix()."product_customer_price_log as t";
		$sql .= " ,".$this->db->prefix()."product as prod";
		$sql .= " ,".$this->db->prefix()."societe as soc";
		$sql .= " WHERE soc.rowid=t.fk_soc";
		$sql .= " AND prod.rowid=t.fk_product ";
		$sql .= " AND prod.entity IN (".getEntity('product').")";
		$sql .= " AND t.entity IN (".getEntity('productprice').")";
		// Manage filter
		if (count($filter) > 0) {
			foreach ($filter as $key => $value) {
				if (strpos($key, 'date')) { 				// To allow $filter['YEAR(s.dated)']=>$year
					$sql .= " AND ".$key." = '".$this->db->escape($value)."'";
				} elseif ($key == 'soc.nom') {
					$sql .= " AND ".$key." LIKE '%".$this->db->escape($value)."%'";
				} else {
					$sql .= " AND ".$key." = ".((int) $value);
				}
			}
		}
		$sql .= $this->db->order($sortfield, $sortorder);
		if (!empty($limit)) {
			$sql .= $this->db->plimit($limit + 1, $offset);
		}

		dol_syslog(get_class($this)."::fetchAllLog", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$this->lines = array();
			$num = $this->db->num_rows($resql);

			while ($obj = $this->db->fetch_object($resql)) {
				$line = new PriceByCustomerLine($this->db);

				$line->id = $obj->rowid;

				$line->entity = $obj->entity;
				$line->datec = $this->db->jdate($obj->datec);
				$line->tms = $this->db->jdate($obj->tms);
				$line->fk_product = $obj->fk_product;
				$line->fk_soc = $obj->fk_soc;
				$line->ref_customer = $obj->ref_customer;
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
				$line->price_label = $obj->price_label;
				$line->import_key = $obj->import_key;
				$line->socname = $obj->socname;
				$line->prodref = $obj->prodref;

				$this->lines [] = $line;
			}
			$this->db->free($resql);

			return $num;
		} else {
			$this->error = "Error ".$this->db->lasterror();
			return -1;
		}
	}

	/**
	 * Update object into database
	 *
	 * @param User $user that modifies
	 * @param int $notrigger triggers after, 1=disable triggers
	 * @param int $forceupdateaffiliate update price on each soc child
	 * @return int Return integer <0 if KO, >0 if OK
	 */
	public function update(User $user, $notrigger = 0, $forceupdateaffiliate = 0)
	{
		global $conf, $langs;
		$error = 0;

		// Clean parameters

		if (isset($this->entity)) {
			$this->entity = (int) $this->entity;
		}
		if (isset($this->fk_product)) {
			$this->fk_product = (int) $this->fk_product;
		}
		if (isset($this->fk_soc)) {
			$this->fk_soc = (int) $this->fk_soc;
		}
		if (isset($this->ref_customer)) {
			$this->ref_customer = trim($this->ref_customer);
		}
		if (isset($this->price)) {
			$this->price = trim($this->price);
		}
		if (isset($this->price_ttc)) {
			$this->price_ttc = trim($this->price_ttc);
		}
		if (isset($this->price_min)) {
			$this->price_min = trim($this->price_min);
		}
		if (isset($this->price_min_ttc)) {
			$this->price_min_ttc = trim($this->price_min_ttc);
		}
		if (isset($this->price_base_type)) {
			$this->price_base_type = trim($this->price_base_type);
		}
		if (isset($this->tva_tx)) {
			$this->tva_tx = (float) $this->tva_tx;
		}
		if (isset($this->recuperableonly)) {
			$this->recuperableonly = trim($this->recuperableonly);
		}
		if (isset($this->localtax1_tx)) {
			$this->localtax1_tx = trim($this->localtax1_tx);
		}
		if (isset($this->localtax2_tx)) {
			$this->localtax2_tx = trim($this->localtax2_tx);
		}
		if (isset($this->fk_user)) {
			$this->fk_user = (int) $this->fk_user;
		}
		if (isset($this->price_label)) {
			$this->price_label = trim($this->price_label);
		}
		if (isset($this->import_key)) {
			$this->import_key = trim($this->import_key);
		}

		// Check parameters
		// Put here code to add a control on parameters values

		if ($this->price != '' || $this->price == 0) {
			if ($this->price_base_type == 'TTC') {
				$this->price_ttc = price2num($this->price, 'MU');
				$this->price = (float) price2num($this->price) / (1 + ($this->tva_tx / 100));
				$this->price = price2num($this->price, 'MU');

				if ($this->price_min != '' || $this->price_min == 0) {
					$this->price_min_ttc = price2num($this->price_min, 'MU');
					$this->price_min = (float) price2num($this->price_min) / (1 + ($this->tva_tx / 100));
					$this->price_min = price2num($this->price_min, 'MU');
				} else {
					$this->price_min = 0;
					$this->price_min_ttc = 0;
				}
			} else {
				$this->price = price2num($this->price, 'MU');
				$this->price_ttc = ($this->recuperableonly != 1) ? (float) price2num($this->price) * (1 + ($this->tva_tx / 100)) : $this->price;
				$this->price_ttc = price2num($this->price_ttc, 'MU');

				if ($this->price_min != '' || $this->price_min == 0) {
					$this->price_min = price2num($this->price_min, 'MU');
					$this->price_min_ttc = (float) price2num($this->price_min) * (1 + ($this->tva_tx / 100));
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
		$sql = "INSERT INTO ".$this->db->prefix()."product_customer_price_log(";

		$sql .= "entity,";
		$sql .= "datec,";
		$sql .= "fk_product,";
		$sql .= "fk_soc,";
		$sql .= "ref_customer,";
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
		$sql .= "price_label,";
		$sql .= "import_key";

		$sql .= ") 		";
		$sql .= "SELECT";

		$sql .= " t.entity,";
		$sql .= " t.datec,";
		$sql .= " t.fk_product,";
		$sql .= " t.fk_soc,";
		$sql .= " t.ref_customer,";
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
		$sql .= " t.price_label,";
		$sql .= " t.import_key";

		$sql .= " FROM ".$this->db->prefix()."product_customer_price as t";
		$sql .= " WHERE t.rowid = ".((int) $this->id);

		$this->db->begin();
		dol_syslog(get_class($this)."::update", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++;
			$this->errors [] = "Error ".$this->db->lasterror();
		}

		// Update request
		$sql = "UPDATE ".$this->db->prefix()."product_customer_price SET";

		$sql .= " entity=".$conf->entity.",";
		$sql .= " datec='".$this->db->idate(dol_now())."',";
		$sql .= " tms=".(dol_strlen((string) $this->tms) != 0 ? "'".$this->db->idate($this->tms)."'" : 'null').",";
		$sql .= " fk_product=".(isset($this->fk_product) ? $this->fk_product : "null").",";
		$sql .= " fk_soc=".(isset($this->fk_soc) ? $this->fk_soc : "null").",";
		$sql .= " ref_customer=".(isset($this->ref_customer) ? "'".$this->db->escape($this->ref_customer)."'" : "null").",";
		$sql .= " price=".(isset($this->price) ? $this->price : "null").",";
		$sql .= " price_ttc=".(isset($this->price_ttc) ? $this->price_ttc : "null").",";
		$sql .= " price_min=".(isset($this->price_min) ? $this->price_min : "null").",";
		$sql .= " price_min_ttc=".(isset($this->price_min_ttc) ? $this->price_min_ttc : "null").",";
		$sql .= " price_base_type=".(isset($this->price_base_type) ? "'".$this->db->escape($this->price_base_type)."'" : "null").",";
		$sql .= " default_vat_code = ".($this->default_vat_code ? "'".$this->db->escape($this->default_vat_code)."'" : "null").",";
		$sql .= " tva_tx=".(isset($this->tva_tx) ? (empty($this->tva_tx) ? 0 : $this->tva_tx) : "null").",";
		$sql .= " recuperableonly=".(isset($this->recuperableonly) ? $this->recuperableonly : "null").",";
		$sql .= " localtax1_tx=".(isset($this->localtax1_tx) ? (empty($this->localtax1_tx) ? 0 : $this->localtax1_tx) : "null").",";
		$sql .= " localtax2_tx=".(isset($this->localtax2_tx) ? (empty($this->localtax2_tx) ? 0 : $this->localtax2_tx) : "null").",";
		$sql .= " localtax1_type=".(!empty($this->localtax1_type) ? "'".$this->db->escape($this->localtax1_type)."'" : "'0'").",";
		$sql .= " localtax2_type=".(!empty($this->localtax2_type) ? "'".$this->db->escape($this->localtax2_type)."'" : "'0'").",";
		$sql .= " fk_user=".$user->id.",";
		$sql .= " price_label=".(isset($this->price_label) ? "'".$this->db->escape($this->price_label)."'" : "null").",";
		$sql .= " import_key=".(isset($this->import_key) ? "'".$this->db->escape($this->import_key)."'" : "null");

		$sql .= " WHERE rowid=".((int) $this->id);

		dol_syslog(get_class($this)."::update", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++;
			$this->errors [] = "Error ".$this->db->lasterror();
		}

		if (!$error && !$notrigger) {
			// Call trigger
			$result = $this->call_trigger('PRODUCT_CUSTOMER_PRICE_MODIFY', $user);
			if ($result < 0) {
				$error++;
			}
			// End call triggers
		}

		if (!$error) {
			$result = $this->setPriceOnAffiliateThirdparty($user, $forceupdateaffiliate);
			if ($result < 0) {
				$error++;
			}
		}

		// Commit or rollback
		if ($error) {
			foreach ($this->errors as $errmsg) {
				dol_syslog(get_class($this)."::update ".$errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
			}
			$this->db->rollback();
			return -1 * $error;
		} else {
			$this->db->commit();
			return 1;
		}
	}

	/**
	 * Force update price on child companies so child company has same prices than parent.
	 *
	 * @param 	User $user 					User that modifies
	 * @param 	int $forceupdateaffiliate 	update price on each soc child
	 * @return 	int 						Return integer <0 if KO, 0 = action disabled, >0 if OK
	 */
	public function setPriceOnAffiliateThirdparty($user, $forceupdateaffiliate)
	{
		global $conf;

		if (getDolGlobalString('PRODUCT_DISABLE_PROPAGATE_CUSTOMER_PRICES_ON_CHILD_COMPANIES')) {
			return 0;
		}

		$error = 0;

		// Find all susidiaries
		$sql = "SELECT s.rowid";
		$sql .= " FROM ".$this->db->prefix()."societe as s";
		$sql .= " WHERE s.parent = ".((int) $this->fk_soc);
		$sql .= " AND s.entity IN (".getEntity('societe').")";

		dol_syslog(get_class($this)."::setPriceOnAffiliateThirdparty", LOG_DEBUG);
		$resql = $this->db->query($sql);

		if ($resql) {
			$this->lines = array();
			$num = $this->db->num_rows($resql);

			while (($obj = $this->db->fetch_object($resql)) && (empty($error))) {
				// find if there is an existing line for the product and the subsidiaries
				$prodsocprice = new ProductCustomerPrice($this->db);

				$filter = array(
					't.fk_product' => $this->fk_product, 't.fk_soc' => $obj->rowid
				);

				$result = $prodsocprice->fetchAll('', '', 0, 0, $filter);
				if ($result < 0) {
					$error++;
					$this->error = $prodsocprice->error;
				} else {
					// There is one line
					if (count($prodsocprice->lines) > 0) {
						// If force update => Update
						if (!empty($forceupdateaffiliate)) {
							$prodsocpriceupd = new ProductCustomerPrice($this->db);
							$prodsocpriceupd->fetch($prodsocprice->lines [0]->id);

							$prodsocpriceupd->price = $this->price;
							$prodsocpriceupd->price_min = $this->price_min;
							$prodsocpriceupd->price_base_type = $this->price_base_type;
							$prodsocpriceupd->tva_tx = $this->tva_tx;
							$prodsocpriceupd->recuperableonly = $this->recuperableonly;
							$prodsocpriceupd->price_label = $this->price_label;

							$resultupd = $prodsocpriceupd->update($user, 0, $forceupdateaffiliate);
							if ($resultupd < 0) {
								$error++;
								$this->error = $prodsocpriceupd->error;
							}
						}
					} else {
						// If line do not exits then create it
						$prodsocpricenew = new ProductCustomerPrice($this->db);
						$prodsocpricenew->fk_soc = $obj->rowid;
						$prodsocpricenew->ref_customer = $obj->ref_customer;
						$prodsocpricenew->fk_product = $this->fk_product;
						$prodsocpricenew->price = $this->price;
						$prodsocpricenew->price_min = $this->price_min;
						$prodsocpricenew->price_base_type = $this->price_base_type;
						$prodsocpricenew->tva_tx = $this->tva_tx;
						$prodsocpricenew->recuperableonly = $this->recuperableonly;
						$prodsocpricenew->price_label = $this->price_label;

						$resultupd = $prodsocpricenew->create($user, 0, $forceupdateaffiliate);
						if ($resultupd < 0) {
							$error++;
							$this->error = $prodsocpricenew->error;
						}
					}
				}
			}
			$this->db->free($resql);

			if (empty($error)) {
				return 1;
			} else {
				return -1;
			}
		} else {
			$this->error = "Error ".$this->db->lasterror();
			return -1;
		}
	}

	/**
	 * Delete object in database
	 *
	 * @param User $user that deletes
	 * @param int $notrigger triggers after, 1=disable triggers
	 * @return int Return integer <0 if KO, >0 if OK
	 */
	public function delete($user, $notrigger = 0)
	{
		global $conf, $langs;
		$error = 0;

		$this->db->begin();

		if (!$notrigger) {
			$result = $this->call_trigger('PRODUCT_CUSTOMER_PRICE_DELETE', $user);
			if ($result < 0) {
				$error++;
			}
		}

		if (!$error) {
			$sql = "DELETE FROM ".$this->db->prefix()."product_customer_price";
			$sql .= " WHERE rowid=".((int) $this->id);

			dol_syslog(get_class($this)."::delete", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				$error++;
				$this->errors [] = "Error ".$this->db->lasterror();
			}
		}

		// Commit or rollback
		if ($error) {
			foreach ($this->errors as $errmsg) {
				dol_syslog(get_class($this)."::delete ".$errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
			}
			$this->db->rollback();
			return -1 * $error;
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

		$object = new ProductCustomerPrice($this->db);

		$this->db->begin();

		// Load source object
		$object->fetch($fromid);
		$object->id = 0;
		$object->statut = 0;

		// Clear fields
		// ...

		// Create clone
		$object->context['createfromclone'] = 'createfromclone';
		$result = $object->create($user);

		// Other options
		if ($result < 0) {
			$this->error = $object->error;
			$this->errors = array_merge($this->errors, $object->errors);
			$error++;
		}

		if (!$error) {
		}

		unset($object->context['createfromclone']);

		// End
		if (!$error) {
			$this->db->commit();
			return $object->id;
		} else {
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 * Initialise object with example values
	 * Id must be 0 if object instance is a specimen
	 *
	 * @return int
	 */
	public function initAsSpecimen()
	{
		$this->id = 0;

		$this->entity = 0;
		$this->datec = '';
		$this->tms = dol_now();
		$this->fk_product = 0;
		$this->fk_soc = 0;
		$this->ref_customer = '';
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
		$this->fk_user = 0;
		$this->price_label = '';
		$this->import_key = '';

		return 1;
	}
}

/**
 * File of class to manage predefined price products or services by customer lines
 */
class PriceByCustomerLine extends CommonObjectLine
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

	/**
	 * @var int ID
	 */
	public $fk_product;

	/**
	 * @var string Customer reference
	 */
	public $ref_customer;

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
	public $price_label;

	public $import_key;
	public $socname;
	public $prodref;
}
