<?php
/* Copyright (C) 2007-2012  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2014       Juanjo Menent       <jmenent@2byte.es>
 * Copyright (C) 2015       Florian Henry       <florian.henry@open-concept.pro>
 * Copyright (C) 2015       Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2018-2024  Frédéric France     <frederic.france@free.fr>
 * Copyright (C) 2023	   	Gauthier VERDOL		<gauthier.verdol@atm-consulting.fr>
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
 * \file    product/stock/class/productlot.class.php
 * \ingroup stock
 * \brief   This is CRUD class file to manage table productlot (Create/Read/Update/Delete)
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
//require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
//require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

/**
 * Class with list of lots and properties
 */
class Productlot extends CommonObject
{
	/**
	 * @var string Id to identify managed objects
	 */
	public $element = 'productlot';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'product_lot';

	/**
	 * @var string String with name of icon for myobject. Must be the part after the 'object_' into object_myobject.png
	 */
	public $picto = 'lot';

	/**
	 * @var array{customers:int,nb:int,rows:int,qty:int} stats propales
	 */
	public $stats_propale;

	/**
	 * @var array{customers:int,nb:int,rows:int,qty:int} stats orders
	 */
	public $stats_commande;

	/**
	 * @var array{customers:int,nb:int,rows:int,qty:int} stats contracts
	 */
	public $stats_contrat;

	/**
	 * @var array{customers:int,nb:int,rows:int,qty:int} stats invoices
	 */
	public $stats_facture;

	/**
	 * @var array{suppliers:int,nb:int,rows:int,qty:int} stats supplier propales
	 */
	public $stats_commande_fournisseur;

	/**
	 * @var array{customers:int,nb:int,rows:int,qty:int} stats shipping
	 */
	public $stats_expedition;

	/**
	 * @var array{customers:int,nb:int,rows:int,qty:int} stats receptions
	 */
	public $stats_reception;

	/**
	 * @var array{customers:int,nb:int,rows:int,qty:int} stats supplier orders
	 */
	public $stats_supplier_order;

	/**
	 * @var array{customers_toconsume:int,nb_toconsume:int,qty_toconsume:int,customers_consumed:int,nb_consumed:int,qty_consumed:int,customers_toproduce:int,nb_toproduce:int,qty_toproduce:int,customers_produced:int,nb_produced:int,qty_produced:int} stats by role toconsume, consumed, toproduce, produced
	 */
	public $stats_mo;
	/**
	 * @var array{nb_toproduce:int,qty_toproduce:float,nb_toconsume:int,qty_toconsume:float}
	 */
	public $stats_bom;
	/**
	 * @var array{customers:int,nb:int,rows:int,qty:float}
	 */
	public $stats_mrptoconsume;
	/**
	 * @var array{customers:int,nb:int,rows:int,qty:float}
	 */
	public $stats_mrptoproduce;
	/**
	 * @var array{customers:int,nb:int,rows:int,qty:float}
	 */
	public $stats_facturerec;
	/**
	 * @var array{suppliers:int,nb:int,rows:int,qty:float}
	 */
	public $stats_facture_fournisseur;


	/**
	 *  'type' if the field format ('integer', 'integer:ObjectClass:PathToClass[:AddCreateButtonOrNot[:Filter]]', 'varchar(x)', 'double(24,8)', 'real', 'price', 'text', 'html', 'date', 'datetime', 'timestamp', 'duration', 'mail', 'phone', 'url', 'password')
	 *         Note: Filter can be a string like "(t.ref:like:'SO-%') or (t.date_creation:<:'20160101') or (t.nature:is:NULL)"
	 *  'label' the translation key.
	 *  'enabled' is a condition when the field must be managed (Example: 1 or 'getDolGlobalString("MY_SETUP_PARAM")'
	 *  'position' is the sort order of field.
	 *  'notnull' is set to 1 if not null in database. Set to -1 if we must set data to null if empty ('' or 0).
	 *  'visible' says if field is visible in list (Examples: 0=Not visible, 1=Visible on list and create/update/view forms, 2=Visible on list only, 3=Visible on create/update/view form only (not list), 4=Visible on list and update/view form only (not create). 5=Visible on list and view only (not create/not update). Using a negative value means field is not shown by default on list but can be selected for viewing)
	 *  'noteditable' says if field is not editable (1 or 0)
	 *  'default' is a default value for creation (can still be overwrote by the Setup of Default Values if field is editable in creation form). Note: If default is set to '(PROV)' and field is 'ref', the default value will be set to '(PROVid)' where id is rowid when a new record is created.
	 *  'index' if we want an index in database.
	 *  'foreignkey'=>'tablename.field' if the field is a foreign key (it is recommended to name the field fk_...).
	 *  'searchall' is 1 if we want to search in this field when making a search from the quick search button.
	 *  'isameasure' must be set to 1 if you want to have a total on list for this field. Field type must be summable like integer or double(24,8).
	 *  'css' is the CSS style to use on field. For example: 'maxwidth200'
	 *  'help' is a string visible as a tooltip on field
	 *  'showoncombobox' if value of the field must be visible into the label of the combobox that list record
	 *  'disabled' is 1 if we want to have the field locked by a 'disabled' attribute. In most cases, this is never set into the definition of $fields into class, but is set dynamically by some part of code.
	 *  'arrayofkeyval' to set list of value if type is a list of predefined values. For example: array("0"=>"Draft","1"=>"Active","-1"=>"Cancel")
	 *  'autofocusoncreate' to have field having the focus on a create form. Only 1 field should have this property set to 1.
	 *  'comment' is not used. You can store here any text of your choice. It is not used by application.
	 *
	 *  Note: To have value dynamic, you can set value to 0 in definition and edit the value on the fly into the constructor.
	 */

	/**
	 * @var array<string,array{type:string,label:string,enabled:int<0,2>|string,position:int,notnull?:int,visible:int<-5,5>|string,alwayseditable?:int<0,1>,noteditable?:int<0,1>,default?:string,index?:int,foreignkey?:string,searchall?:int<0,1>,isameasure?:int<0,1>,css?:string,csslist?:string,help?:string,showoncombobox?:int<0,4>,disabled?:int<0,1>,arrayofkeyval?:array<int|string,string>,autofocusoncreate?:int<0,1>,comment?:string,copytoclipboard?:int<1,2>,validate?:int<0,1>,showonheader?:int<0,1>}>  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields = array(
		'rowid'         => array('type' => 'integer', 'label' => 'TechnicalID', 'enabled' => 1, 'visible' => -2, 'noteditable' => 1, 'notnull' => 1, 'index' => 1, 'position' => 1, 'comment' => 'Id', 'css' => 'left'),
		'fk_product'    => array('type' => 'integer:Product:product/class/product.class.php', 'label' => 'Product', 'enabled' => 1, 'visible' => 1, 'position' => 5, 'notnull' => 1, 'index' => 1, 'searchall' => 1, 'picto' => 'product', 'css' => 'maxwidth500 widthcentpercentminusxx', 'csslist' => 'maxwidth150'),
		'batch'         => array('type' => 'varchar(30)', 'label' => 'Batch', 'enabled' => 1, 'visible' => 1, 'notnull' => 1, 'showoncombobox' => 1, 'index' => 1, 'position' => 10, 'comment' => 'Batch', 'searchall' => 1, 'picto' => 'lot', 'validate' => 1),
		'entity'        => array('type' => 'integer', 'label' => 'Entity', 'enabled' => 1, 'visible' => 0, 'default' => '1', 'notnull' => 1, 'index' => 1, 'position' => 20),
		'sellby'        => array('type' => 'date', 'label' => 'SellByDate', 'enabled' => 'empty($conf->global->PRODUCT_DISABLE_SELLBY)?1:0', 'visible' => 1, 'notnull' => 0, 'position' => 60),
		'eatby'         => array('type' => 'date', 'label' => 'EatByDate', 'enabled' => 'empty($conf->global->PRODUCT_DISABLE_EATBY)?1:0', 'visible' => 1, 'notnull' => 0, 'position' => 62),
		'eol_date'        => array('type' => 'date', 'label' => 'EndOfLife', 'enabled' => 'getDolGlobalInt("PRODUCT_LOT_ENABLE_QUALITY_CONTROL")?1:0', 'visible' => 'getDolGlobalInt("PRODUCT_LOT_ENABLE_QUALITY_CONTROL")?5:0', 'position' => 70),
		'manufacturing_date' => array('type' => 'date', 'label' => 'ManufacturingDate', 'enabled' => 'getDolGlobalInt("PRODUCT_LOT_ENABLE_TRACEABILITY")?1:0', 'visible' => 'getDolGlobalInt("PRODUCT_LOT_ENABLE_TRACEABILITY")?5:0', 'position' => 80),
		'scrapping_date'     => array('type' => 'date', 'label' => 'DestructionDate', 'enabled' => 'getDolGlobalInt("PRODUCT_LOT_ENABLE_TRACEABILITY")?1:0', 'visible' => 'getDolGlobalInt("PRODUCT_LOT_ENABLE_TRACEABILITY")?5:0', 'position' => 90),
		//'commissionning_date'        => array('type'=>'date', 'label'=>'FirstUseDate', 'enabled'=>'getDolGlobalInt("PRODUCT_LOT_ENABLE_TRACEABILITY", 0)', 'visible'=>5, 'position'=>100),
		'qc_frequency'        => array('type' => 'integer', 'label' => 'QCFrequency', 'enabled' => 'getDolGlobalInt("PRODUCT_LOT_ENABLE_QUALITY_CONTROL")?1:0', 'visible' => 'getDolGlobalInt("PRODUCT_LOT_ENABLE_QUALITY_CONTROL")?5:0', 'position' => 110),
		'lifetime'        => array('type' => 'integer', 'label' => 'Lifetime', 'enabled' => 'getDolGlobalInt("PRODUCT_LOT_ENABLE_QUALITY_CONTROL")?1:0', 'visible' => 'getDolGlobalInt("PRODUCT_LOT_ENABLE_QUALITY_CONTROL")?5:0', 'position' => 110),
		'model_pdf'		=> array('type' => 'varchar(255)', 'label' => 'Model pdf', 'enabled' => 1, 'visible' => 0, 'position' => 215),
		'last_main_doc' => array('type' => 'varchar(255)', 'label' => 'LastMainDoc', 'enabled' => 1, 'visible' => -2, 'position' => 310),
		'datec'         => array('type' => 'datetime', 'label' => 'DateCreation', 'enabled' => 1, 'visible' => 0, 'notnull' => 1, 'position' => 500),
		'tms'           => array('type' => 'timestamp', 'label' => 'DateModification', 'enabled' => 1, 'visible' => -2, 'notnull' => 1, 'position' => 501),
		'fk_user_creat' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserAuthor', 'enabled' => 1, 'visible' => -2, 'notnull' => 1, 'position' => 510, 'foreignkey' => 'llx_user.rowid'),
		'fk_user_modif' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserModif', 'enabled' => 1, 'visible' => -2, 'notnull' => -1, 'position' => 511),
		'import_key'    => array('type' => 'varchar(14)', 'label' => 'ImportId', 'enabled' => 1, 'visible' => -2, 'notnull' => -1, 'index' => 0, 'position' => 1000)
	);

	/**
	 * @var int Entity
	 */
	public $entity;

	/**
	 * @var int Product ID
	 */
	public $fk_product;

	/**
	 * @var string batch ref
	 */
	public $batch;

	/**
	 * @var int|string eatby
	 */
	public $eatby = '';

	/**
	 * @var int|string sellby
	 */
	public $sellby = '';

	/**
	 * @var int|'' eol_date
	 */
	public $eol_date = '';

	/**
	 * @var int|'' manufacturing_date
	 */
	public $manufacturing_date = '';

	/**
	 * @var int|'' scrapping_date
	 */
	public $scrapping_date = '';
	//public $commissionning_date = '';
	/**
	 * @var int|''
	 */
	public $qc_frequency = '';
	/**
	 * @var int|''
	 */
	public $lifetime = '';
	/**
	 * @var int|''
	 */
	public $datec = '';

	/**
	 * @var int user ID
	 */
	public $fk_user_creat;

	/**
	 * @var int user ID
	 */
	public $fk_user_modif;

	/**
	 * @var string import key
	 */
	public $import_key;


	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		$this->db = $db;

		$this->ismultientitymanaged = 1;
	}

	/**
	 * Check sell or eat by date is mandatory
	 *
	 * @param	string 		$onlyFieldName		[=''] check all fields by default or only one field name ("sellby", "eatby")
	 * @return 	int			Return integer <0 if KO, 0 nothing done, >0 if OK
	 */
	public function checkSellOrEatByMandatory($onlyFieldName = '')
	{
		if (getDolGlobalString('PRODUCT_DISABLE_SELLBY') && getDolGlobalString('PRODUCT_DISABLE_EATBY')) {
			return 0;
		}

		$errorMsgArr = array();
		if ($this->fk_product > 0) {
			$res = $this->fetch_product();
			$product = $this->product;
			if ($res <= 0) {
				$errorMsgArr[] = $product->errorsToString();
			}

			if (empty($errorMsgArr)) {
				$errorMsgArr = self::checkSellOrEatByMandatoryFromProductAndDates($product, $this->sellby, $this->eatby, $onlyFieldName, true);
			}
		}

		if (!empty($errorMsgArr)) {
			$this->errors = array_merge($this->errors, $errorMsgArr);
			return -1;
		} else {
			return 1;
		}
	}

	/**
	 * Check sell or eat by date is mandatory from product id and sell-by and eat-by dates
	 *
	 * @param 	int 		$productId			Product id
	 * @param	int			$sellBy				Sell by date
	 * @param 	int			$eatBy				Eat by date
	 * @param	string 		$onlyFieldName		[=''] check all fields by default or only one field name ("sellby", "eatby")
	 * @return 	string[]|null	Array of errors or null if nothing done
	 */
	public static function checkSellOrEatByMandatoryFromProductIdAndDates($productId, $sellBy, $eatBy, $onlyFieldName = '')
	{
		global $db;

		if (getDolGlobalString('PRODUCT_DISABLE_SELLBY') && getDolGlobalString('PRODUCT_DISABLE_EATBY')) {
			return null;
		}

		$errorMsgArr = array();
		if ($productId > 0) {
			$product = new Product($db);
			$res = $product->fetch($productId);
			if ($res <= 0) {
				$errorMsgArr[] = $product->errorsToString();
			}

			if (empty($errorMsgArr)) {
				$errorMsgArr = self::checkSellOrEatByMandatoryFromProductAndDates($product, $sellBy, $eatBy, $onlyFieldName, true);
			}
		}

		return $errorMsgArr;
	}

	/**
	 * Check sell or eat by date is mandatory from product and sell-by and eat-by dates
	 *
	 * @param 	Product 	$product			Product object
	 * @param	int			$sellBy				Sell by date
	 * @param 	int			$eatBy				Eat by date
	 * @param	string 		$onlyFieldName		[=''] check all fields by default or only one field name ("sellby", "eatby")
	 * @param	bool		$alreadyCheckConf	[=false] conf hasn't been already checked by default or true not to check conf
	 * @return 	string[]|null	Array of errors or null if nothing done
	 */
	public static function checkSellOrEatByMandatoryFromProductAndDates($product, $sellBy, $eatBy, $onlyFieldName = '', $alreadyCheckConf = false)
	{
		global $langs;

		if ($alreadyCheckConf === false && getDolGlobalString('PRODUCT_DISABLE_SELLBY') && getDolGlobalString('PRODUCT_DISABLE_EATBY')) {
			return null;
		}

		$errorMsgArr = array();
		$checkSellByMandatory = false;
		$checkEatByMandatory = false;

		$sellOrEatByMandatoryId = $product->sell_or_eat_by_mandatory;
		if (!getDolGlobalString('PRODUCT_DISABLE_SELLBY') && $sellOrEatByMandatoryId == Product::SELL_OR_EAT_BY_MANDATORY_ID_SELL_BY && ($onlyFieldName == '' || $onlyFieldName == 'sellby')) {
			$checkSellByMandatory = true;
		} elseif (!getDolGlobalString('PRODUCT_DISABLE_EATBY') && $sellOrEatByMandatoryId == Product::SELL_OR_EAT_BY_MANDATORY_ID_EAT_BY && ($onlyFieldName == '' || $onlyFieldName == 'eatby')) {
			$checkEatByMandatory = true;
		} elseif ($sellOrEatByMandatoryId == Product::SELL_OR_EAT_BY_MANDATORY_ID_SELL_AND_EAT) {
			if (!getDolGlobalString('PRODUCT_DISABLE_SELLBY') && ($onlyFieldName == '' || $onlyFieldName == 'sellby')) {
				$checkSellByMandatory = true;
			}
			if (!getDolGlobalString('PRODUCT_DISABLE_EATBY') && ($onlyFieldName == '' || $onlyFieldName == 'eatby')) {
				$checkEatByMandatory = true;
			}
		}

		if ($checkSellByMandatory) {
			if (!isset($sellBy) || dol_strlen((string) $sellBy) == 0) {
				// error : sell by is mandatory
				$errorMsgArr[] = $langs->trans('ErrorFieldRequired', $langs->transnoentities('SellByDate'));
			}
		}
		if ($checkEatByMandatory) {
			if (!isset($eatBy) || dol_strlen((string) $eatBy) == 0) {
				// error : eat by is mandatory
				$errorMsgArr[] = $langs->trans('ErrorFieldRequired', $langs->transnoentities('EatByDate'));
			}
		}

		return $errorMsgArr;
	}


	/**
	 * Create object into database
	 *
	 * @param  User $user      	User that creates
	 * @param  int 	$notrigger 	0=launch triggers after, 1=disable triggers
	 * @return int 				Return integer <0 if KO, Id of created object if OK
	 */
	public function create(User $user, $notrigger = 0)
	{
		global $conf, $langs;

		dol_syslog(__METHOD__, LOG_DEBUG);

		$error = 0;

		// Clean parameters

		if (isset($this->entity)) {
			$this->entity = (int) $this->entity;
		}
		if (isset($this->fk_product)) {
			$this->fk_product = (int) $this->fk_product;
		}
		if (isset($this->batch)) {
			$this->batch = trim($this->batch);
		}
		if (isset($this->fk_user_creat)) {
			$this->fk_user_creat = (int) $this->fk_user_creat;
		}
		if (isset($this->fk_user_modif)) {
			$this->fk_user_modif = (int) $this->fk_user_modif;
		}
		if (isset($this->import_key)) {
			$this->import_key = trim($this->import_key);
		}

		// Check parameters
		if ($this->batch === '') {
			$this->errors[] = $langs->trans("ErrorBadValueForBatch");
			dol_syslog(__METHOD__.' '.implode(',', $this->errors), LOG_ERR);
			return -1;
		}
		// Put here code to add control on parameters values
		$res = $this->checkSellOrEatByMandatory();
		if ($res < 0) {
			$error++;
		}

		if (!$error) {
			// Insert request
			$sql = 'INSERT INTO ' . $this->db->prefix() . $this->table_element . '(';
			$sql .= 'entity,';
			$sql .= 'fk_product,';
			$sql .= 'batch,';
			$sql .= 'eatby,';
			$sql .= 'sellby,';
			$sql .= 'eol_date,';
			$sql .= 'manufacturing_date,';
			$sql .= 'scrapping_date,';
			//$sql .= 'commissionning_date,';
			$sql .= 'qc_frequency,';
			$sql .= 'lifetime,';
			$sql .= 'datec,';
			$sql .= 'fk_user_creat,';
			$sql .= 'fk_user_modif,';
			$sql .= 'import_key';
			$sql .= ') VALUES (';
			$sql .= ' ' . (!isset($this->entity) ? $conf->entity : $this->entity) . ',';
			$sql .= ' ' . (!isset($this->fk_product) ? 'NULL' : $this->fk_product) . ',';
			$sql .= ' ' . (!isset($this->batch) ? 'NULL' : "'" . $this->db->escape($this->batch) . "'") . ',';
			$sql .= ' ' . (!isset($this->eatby) || dol_strlen($this->eatby) == 0 ? 'NULL' : "'" . $this->db->idate($this->eatby) . "'") . ',';
			$sql .= ' ' . (!isset($this->sellby) || dol_strlen($this->sellby) == 0 ? 'NULL' : "'" . $this->db->idate($this->sellby) . "'") . ',';
			$sql .= ' ' . (!isset($this->eol_date) || dol_strlen($this->eol_date) == 0 ? 'NULL' : "'" . $this->db->idate($this->eol_date) . "'") . ',';
			$sql .= ' ' . (!isset($this->manufacturing_date) || dol_strlen($this->manufacturing_date) == 0 ? 'NULL' : "'" . $this->db->idate($this->manufacturing_date) . "'") . ',';
			$sql .= ' ' . (!isset($this->scrapping_date) || dol_strlen($this->scrapping_date) == 0 ? 'NULL' : "'" . $this->db->idate($this->scrapping_date) . "'") . ',';
			//$sql .= ' '.(!isset($this->commissionning_date) || dol_strlen($this->commissionning_date) == 0 ? 'NULL' : "'".$this->db->idate($this->commissionning_date)."'").',';
			$sql .= ' '.(empty($this->qc_frequency) ? 'NULL' : $this->qc_frequency).',';
			$sql .= ' '.(empty($this->lifetime) ? 'NULL' : $this->lifetime).',';
			$sql .= ' ' . "'" . $this->db->idate(dol_now()) . "'" . ',';
			$sql .= ' ' . (!isset($this->fk_user_creat) ? 'NULL' : $this->fk_user_creat) . ',';
			$sql .= ' ' . (!isset($this->fk_user_modif) ? 'NULL' : $this->fk_user_modif) . ',';
			$sql .= ' ' . (!isset($this->import_key) ? 'NULL' : $this->import_key);
			$sql .= ')';

			$this->db->begin();

			$resql = $this->db->query($sql);
			if (!$resql) {
				$error++;
				$this->errors[] = 'Error ' . $this->db->lasterror();
			}

			if (!$error) {
				$this->id = $this->db->last_insert_id($this->db->prefix() . $this->table_element);

				// Actions on extra fields
				if (!$error) {
					$result = $this->insertExtraFields();
					if ($result < 0) {
						$error++;
					}
				}

				if (!$error && !$notrigger) {
					// Call triggers
					$result = $this->call_trigger('PRODUCTLOT_CREATE', $user);
					if ($result < 0) {
						$error++;
					}
					// End call triggers
				}
			}

			// Commit or rollback
			if ($error) {
				$this->db->rollback();
			} else {
				$this->db->commit();
			}
		}

		if ($error) {
			dol_syslog(__METHOD__ . ' ' . implode(',', $this->errors), LOG_ERR);
			return -1 * $error;
		} else {
			return $this->id;
		}
	}

	/**
	 * Load object in memory from the database
	 *
	 * @param int    $id  			Id of lot/batch
	 * @param int    $product_id  	Id of product, batch number parameter required
	 * @param string $batch 		batch number
	 *
	 * @return int Return integer <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id = 0, $product_id = 0, $batch = '')
	{
		global $conf;
		dol_syslog(__METHOD__, LOG_DEBUG);

		$sql = "SELECT";
		$sql .= " t.rowid,";
		$sql .= " t.entity,";
		$sql .= " t.fk_product,";
		$sql .= " t.batch,";
		$sql .= " t.eatby,";
		$sql .= " t.sellby,";
		$sql .= " t.eol_date,";
		$sql .= " t.manufacturing_date,";
		$sql .= " t.scrapping_date,";
		//$sql .= " t.commissionning_date,";
		$sql .= " t.qc_frequency,";
		$sql .= " t.lifetime,";
		$sql .= " t.model_pdf,";
		$sql .= " t.last_main_doc,";
		$sql .= " t.datec,";
		$sql .= " t.tms,";
		$sql .= " t.fk_user_creat,";
		$sql .= " t.fk_user_modif,";
		$sql .= " t.import_key,";
		$sql .= " t.note_public,";
		$sql .= " t.note_private";
		$sql .= " FROM ".$this->db->prefix().$this->table_element." as t";
		if ($product_id > 0 && $batch != '') {
			$sql .= " WHERE t.batch = '".$this->db->escape($batch)."' AND t.fk_product = ".((int) $product_id);
		} else {
			$sql .= " WHERE t.rowid = ".((int) $id);
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			$numrows = $this->db->num_rows($resql);
			if ($numrows) {
				$obj = $this->db->fetch_object($resql);

				$this->id = $obj->rowid;
				$this->ref = $obj->rowid;
				//$this->ref = $obj->fk_product.'_'.$obj->batch;

				$this->batch = $obj->batch;
				$this->entity = (!empty($obj->entity) ? $obj->entity : $conf->entity); // Prevent "null" entity
				$this->fk_product = $obj->fk_product;
				$this->eatby = $this->db->jdate($obj->eatby);
				$this->sellby = $this->db->jdate($obj->sellby);
				$this->eol_date = $this->db->jdate($obj->eol_date);
				$this->manufacturing_date = $this->db->jdate($obj->manufacturing_date);
				$this->scrapping_date = $this->db->jdate($obj->scrapping_date);
				//$this->commissionning_date = $this->db->jdate($obj->commissionning_date);
				$this->qc_frequency = $obj->qc_frequency;
				$this->lifetime = $obj->lifetime;
				$this->model_pdf = $obj->model_pdf;
				$this->last_main_doc = $obj->last_main_doc;

				$this->datec = $this->db->jdate($obj->datec);
				$this->tms = $this->db->jdate($obj->tms);
				$this->fk_user_creat = $obj->fk_user_creat;
				$this->fk_user_modif = $obj->fk_user_modif;
				$this->import_key = $obj->import_key;
				$this->note_public = $obj->note_public;
				$this->note_private = $obj->note_private;

				// Retrieve all extrafield
				// fetch optionals attributes and labels
				$this->fetch_optionals();
			}
			$this->db->free($resql);

			if ($numrows) {
				return 1;
			} else {
				return 0;
			}
		} else {
			$this->errors[] = 'Error '.$this->db->lasterror();
			dol_syslog(__METHOD__.' '.implode(',', $this->errors), LOG_ERR);

			return -1;
		}
	}

	/**
	 * Update object into database
	 *
	 * @param  User $user      	User that modifies
	 * @param  int 	$notrigger 	0=launch triggers after, 1=disable triggers
	 * @return int 				Return integer <0 if KO, >0 if OK
	 */
	public function update(User $user, $notrigger = 0)
	{
		$error = 0;

		dol_syslog(__METHOD__, LOG_DEBUG);

		// Clean parameters

		if (isset($this->entity)) {
			$this->entity = (int) $this->entity;
		}
		if (isset($this->fk_product)) {
			$this->fk_product = (int) $this->fk_product;
		}
		if (isset($this->batch)) {
			$this->batch = trim($this->batch);
		}
		if (isset($this->fk_user_creat)) {
			$this->fk_user_creat = (int) $this->fk_user_creat;
		}
		if (isset($this->fk_user_modif)) {
			$this->fk_user_modif = (int) $this->fk_user_modif;
		}
		if (isset($this->import_key)) {
			$this->import_key = trim($this->import_key);
		}

		// Check parameters
		// Put here code to add a control on parameters values
		$res = $this->checkSellOrEatByMandatory();
		if ($res < 0) {
			$error++;
		}

		// $this->oldcopy should have been set by the caller of update (here properties were already modified)
		if (empty($this->oldcopy)) {
			$this->oldcopy = dol_clone($this, 2);
		}

		if (!$error) {
			// Update request
			$sql = 'UPDATE ' . $this->db->prefix() . $this->table_element . ' SET';
			$sql .= ' entity = ' . (isset($this->entity) ? $this->entity : "null") . ',';
			$sql .= ' fk_product = ' . (isset($this->fk_product) ? $this->fk_product : "null") . ',';
			$sql .= ' batch = ' . (isset($this->batch) ? "'" . $this->db->escape($this->batch) . "'" : "null") . ',';
			$sql .= ' eatby = ' . (!isset($this->eatby) || dol_strlen($this->eatby) != 0 ? "'" . $this->db->idate($this->eatby) . "'" : 'null') . ',';
			$sql .= ' sellby = ' . (!isset($this->sellby) || dol_strlen($this->sellby) != 0 ? "'" . $this->db->idate($this->sellby) . "'" : 'null') . ',';
			$sql .= ' eol_date = ' . (!isset($this->eol_date) || dol_strlen($this->eol_date) != 0 ? "'" . $this->db->idate($this->eol_date) . "'" : 'null') . ',';
			$sql .= ' manufacturing_date = ' . (!isset($this->manufacturing_date) || dol_strlen($this->manufacturing_date) != 0 ? "'" . $this->db->idate($this->manufacturing_date) . "'" : 'null') . ',';
			$sql .= ' scrapping_date = ' . (!isset($this->scrapping_date) || dol_strlen($this->scrapping_date) != 0 ? "'" . $this->db->idate($this->scrapping_date) . "'" : 'null') . ',';
			//$sql .= ' commissionning_date = '.(!isset($this->first_use_date) || dol_strlen($this->first_use_date) != 0 ? "'".$this->db->idate($this->first_use_date)."'" : 'null').',';
			$sql .= ' qc_frequency = '.(!empty($this->qc_frequency) ? (int) $this->qc_frequency : 'null').',';
			$sql .= ' lifetime = '.(!empty($this->lifetime) ? (int) $this->lifetime : 'null').',';
			$sql .= ' datec = ' . (dol_strlen((string) $this->datec) != 0 ? "'" . $this->db->idate($this->datec) . "'" : 'null') . ',';
			$sql .= ' tms = ' . (dol_strlen((string) $this->tms) != 0 ? "'" . $this->db->idate($this->tms) . "'" : "'" . $this->db->idate(dol_now()) . "'") . ',';
			$sql .= ' fk_user_creat = ' . (isset($this->fk_user_creat) ? $this->fk_user_creat : "null") . ',';
			$sql .= ' fk_user_modif = ' . (isset($this->fk_user_modif) ? $this->fk_user_modif : "null") . ',';
			$sql .= ' import_key = ' . (isset($this->import_key) ? $this->import_key : "null");
			$sql .= ' WHERE rowid=' . ((int) $this->id);

			$this->db->begin();

			$resql = $this->db->query($sql);
			if (!$resql) {
				$error++;
				$this->errors[] = 'Error ' . $this->db->lasterror();
			}

			// Actions on extra fields
			if (!$error) {
				$result = $this->insertExtraFields();
				if ($result < 0) {
					$error++;
				}
			}

			if (!$error && !$notrigger) {
				// Call triggers
				$result = $this->call_trigger('PRODUCTLOT_MODIFY', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}

			// Commit or rollback
			if ($error) {
				$this->db->rollback();
			} else {
				$this->db->commit();
			}
		}

		if ($error) {
			dol_syslog(__METHOD__ . ' ' . implode(',', $this->errors), LOG_ERR);
			return -1 * $error;
		} else {
			return 1;
		}
	}

	/**
	 * Delete object in database
	 *
	 * @param User 	$user      	User that deletes
	 * @param int 	$notrigger 	0=launch triggers after, 1=disable triggers
	 * @return int 				Return integer <0 if KO, >0 if OK
	 */
	public function delete(User $user, $notrigger = 0)
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

		$error = 0;

		$this->db->begin();

		// Check there is no stock for this lot
		$sql = "SELECT pb.rowid FROM ".$this->db->prefix()."product_batch as pb, ".$this->db->prefix()."product_stock as ps";
		$sql .= " WHERE pb.fk_product_stock = ps.rowid AND pb.batch = '".$this->db->escape($this->batch)."'";
		$sql .= " AND ps.fk_product = ".((int) $this->fk_product);
		$sql .= $this->db->plimit(1);

		$resql = $this->db->query($sql);
		if ($resql) {
			$obj = $this->db->fetch_object($resql);
			if ($obj) {
				$error++;
				$this->errors[] = 'Error Lot is used in stock (ID = '.$obj->rowid.'). Deletion not possible.';
				dol_syslog(__METHOD__.' '.implode(',', $this->errors), LOG_ERR);
			}
		} else {
			$error++;
			$this->errors[] = 'Error '.$this->db->lasterror();
			dol_syslog(__METHOD__.' '.implode(',', $this->errors), LOG_ERR);
		}

		// Check there is no movement for this lot
		$sql = "SELECT sm.rowid FROM ".$this->db->prefix()."stock_mouvement as sm";
		$sql .= " WHERE sm.batch = '".$this->db->escape($this->batch)."'";
		$sql .= " AND sm.fk_product = ".((int) $this->fk_product);
		$sql .= $this->db->plimit(1);

		$resql = $this->db->query($sql);
		if ($resql) {
			$obj = $this->db->fetch_object($resql);
			if ($obj) {
				$error++;
				$this->errors[] = 'Error Lot was used in a stock movement (ID '.$obj->rowid.'). Deletion not possible.';
				dol_syslog(__METHOD__.' '.implode(',', $this->errors), LOG_ERR);
			}
		} else {
			$error++;
			$this->errors[] = 'Error '.$this->db->lasterror();
			dol_syslog(__METHOD__.' '.implode(',', $this->errors), LOG_ERR);
		}

		// TODO
		//if (!$error) {
		//if (!$notrigger) {
		// Uncomment this and change PRODUCTLOT to your own tag if you
		// want this action calls a trigger.

		//// Call triggers
		//$result=$this->call_trigger('PRODUCTLOT_DELETE',$user);
		//if ($result < 0) { $error++; //Do also what you must do to rollback action if trigger fail}
		//// End call triggers
		//}
		//}

		if (!$error) {
			$sql = 'DELETE FROM '.$this->db->prefix().$this->table_element;
			$sql .= ' WHERE rowid='.((int) $this->id);

			$resql = $this->db->query($sql);
			if (!$resql) {
				$error++;
				$this->errors[] = 'Error '.$this->db->lasterror();
				dol_syslog(__METHOD__.' '.implode(',', $this->errors), LOG_ERR);
			}
		}

		// Commit or rollback
		if ($error) {
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
	 * @param   int     $fromid     Id of object to clone
	 * @return  int                 New id of clone
	 */
	public function createFromClone(User $user, $fromid)
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

		$error = 0;
		$object = new Productlot($this->db);

		$this->db->begin();

		// Load source object
		$object->fetch($fromid);
		// Reset object
		$object->id = 0;

		// Clear fields
		// ...

		// Create clone
		$object->context['createfromclone'] = 'createfromclone';
		$result = $object->create($user);

		// Other options
		if ($result < 0) {
			$error++;
			$this->errors = $object->errors;
			dol_syslog(__METHOD__.' '.implode(',', $this->errors), LOG_ERR);
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
	 *  Charge tableau des stats expedition pour le lot/numéro de série
	 *
	 * @param  int $socid Id societe
	 * @return int                     Array of stats in $this->stats_expedition, <0 if ko or >0 if ok
	 */
	public function loadStatsExpedition($socid = 0)
	{
		global $user, $hookmanager, $action;

		$sql = "SELECT COUNT(DISTINCT exp.fk_soc) as nb_customers, COUNT(DISTINCT exp.rowid) as nb,";
		$sql .= " COUNT(ed.rowid) as nb_rows, SUM(edb.qty) as qty";
		$sql .= " FROM ".$this->db->prefix()."expeditiondet_batch as edb";
		$sql .= " INNER JOIN ".$this->db->prefix()."expeditiondet as ed ON (ed.rowid = edb.fk_expeditiondet)";
		$sql .= " INNER JOIN ".$this->db->prefix()."expedition as exp ON (exp.rowid = ed.fk_expedition)";
		//      $sql .= ", ".$this->db->prefix()."societe as s";
		if (!$user->hasRight('societe', 'client', 'voir')) {
			$sql .= ", ".$this->db->prefix()."societe_commerciaux as sc";
		}
		$sql .= " WHERE exp.entity IN (".getEntity('expedition').")";
		$sql .= " AND edb.batch = '".($this->db->escape($this->batch))."'";
		if (!$user->hasRight('societe', 'client', 'voir')) {
			$sql .= " AND exp.fk_soc = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
		}
		//$sql.= " AND exp.fk_statut != 0";
		if ($socid > 0) {
			$sql .= " AND exp.fk_soc = ".((int) $socid);
		}

		$result = $this->db->query($sql);
		if ($result) {
			$obj = $this->db->fetch_object($result);
			$this->stats_expedition['customers'] = $obj->nb_customers;
			$this->stats_expedition['nb'] = $obj->nb;
			$this->stats_expedition['rows'] = $obj->nb_rows;
			$this->stats_expedition['qty'] = $obj->qty ? $obj->qty : 0;


			// Virtual products can't be used with kits (see langs with key ErrorNoteAlsoThatSubProductCantBeFollowedByLot)

			// if it's a virtual product, maybe it is in invoice by extension
			//          if (getDolGlobalString('PRODUCT_STATS_WITH_PARENT_PROD_IF_INCDEC')) {
			//              $TFather = $this->getFather();
			//              if (is_array($TFather) && !empty($TFather)) {
			//                  foreach ($TFather as &$fatherData) {
			//                      $pFather = new Product($this->db);
			//                      $pFather->id = $fatherData['id'];
			//                      $qtyCoef = $fatherData['qty'];
			//
			//                      if ($fatherData['incdec']) {
			//                          $pFather->loadStatsExpedition($socid);
			//
			//                          $this->stats_expedition['customers'] += $pFather->stats_expedition['customers'];
			//                          $this->stats_expedition['nb'] += $pFather->stats_expedition['nb'];
			//                          $this->stats_expedition['rows'] += $pFather->stats_expedition['rows'];
			//                          $this->stats_expedition['qty'] += $pFather->stats_expedition['qty'] * $qtyCoef;
			//                      }
			//                  }
			//              }
			//          }

			$parameters = array('socid' => $socid);
			$reshook = $hookmanager->executeHooks('loadStatsLotExpedition', $parameters, $this, $action);
			if ($reshook > 0) {
				$this->stats_expedition = $hookmanager->resArray['stats_expedition'];
			}

			return 1;
		} else {
			$this->error = $this->db->error();
			return -1;
		}
	}

	/**
	 *  Charge tableau des stats commande fournisseur pour le lot/numéro de série
	 *
	 * @param  int $socid Id societe
	 * @return int                     Array of stats in $this->stats_expedition, <0 if ko or >0 if ok
	 */
	public function loadStatsSupplierOrder($socid = 0)
	{
		global $user, $hookmanager, $action;

		$sql = "SELECT COUNT(DISTINCT cf.fk_soc) as nb_customers, COUNT(DISTINCT cf.rowid) as nb,";
		$sql .= " COUNT(cfd.rowid) as nb_rows, SUM(cfdi.qty) as qty";
		$sql .= " FROM ".$this->db->prefix()."receptiondet_batch as cfdi";
		$sql .= " INNER JOIN ".$this->db->prefix()."commande_fournisseurdet as cfd ON (cfd.rowid = cfdi.fk_elementdet)";
		$sql .= " INNER JOIN ".$this->db->prefix()."commande_fournisseur as cf ON (cf.rowid = cfd.fk_commande)";
		//      $sql .= ", ".$this->db->prefix()."societe as s";
		if (!$user->hasRight('societe', 'client', 'voir')) {
			$sql .= ", ".$this->db->prefix()."societe_commerciaux as sc";
		}
		$sql .= " WHERE cf.entity IN (".getEntity('expedition').")";
		$sql .= " AND cfdi.batch = '".($this->db->escape($this->batch))."'";
		if (!$user->hasRight('societe', 'client', 'voir')) {
			$sql .= " AND cf.fk_soc = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
		}
		//$sql.= " AND cf.fk_statut != 0";
		if ($socid > 0) {
			$sql .= " AND cf.fk_soc = ".((int) $socid);
		}

		$result = $this->db->query($sql);
		if ($result) {
			$obj = $this->db->fetch_object($result);
			$this->stats_supplier_order['customers'] = $obj->nb_customers;
			$this->stats_supplier_order['nb'] = $obj->nb;
			$this->stats_supplier_order['rows'] = $obj->nb_rows;
			$this->stats_supplier_order['qty'] = $obj->qty ? $obj->qty : 0;


			// Virtual products can't be used with kits (see langs with key ErrorNoteAlsoThatSubProductCantBeFollowedByLot)

			// if it's a virtual product, maybe it is in invoice by extension
			//          if (getDolGlobalString('PRODUCT_STATS_WITH_PARENT_PROD_IF_INCDEC')) {
			//              $TFather = $this->getFather();
			//              if (is_array($TFather) && !empty($TFather)) {
			//                  foreach ($TFather as &$fatherData) {
			//                      $pFather = new Product($this->db);
			//                      $pFather->id = $fatherData['id'];
			//                      $qtyCoef = $fatherData['qty'];
			//
			//                      if ($fatherData['incdec']) {
			//                          $pFather->stats_supplier_order($socid);
			//
			//                          $this->stats_supplier_order['customers'] += $pFather->stats_supplier_order['customers'];
			//                          $this->stats_supplier_order['nb'] += $pFather->stats_supplier_order['nb'];
			//                          $this->stats_supplier_order['rows'] += $pFather->stats_supplier_order['rows'];
			//                          $this->stats_supplier_order['qty'] += $pFather->stats_supplier_order['qty'] * $qtyCoef;
			//                      }
			//                  }
			//              }
			//          }

			$parameters = array('socid' => $socid);
			$reshook = $hookmanager->executeHooks('loadStatsLotSupplierOrder', $parameters, $this, $action);
			if ($reshook > 0) {
				$this->stats_supplier_order = $hookmanager->resArray['stats_supplier_order'];
			}

			return 1;
		} else {
			$this->error = $this->db->error();
			return -1;
		}
	}

	/**
	 *  Charge tableau des stats expedition pour le lot/numéro de série
	 *
	 * @param  int $socid Id societe
	 * @return int                     Array of stats in $this->stats_expedition, <0 if ko or >0 if ok
	 */
	public function loadStatsReception($socid = 0)
	{
		global $user, $hookmanager, $action;

		$sql = "SELECT COUNT(DISTINCT recep.fk_soc) as nb_customers, COUNT(DISTINCT recep.rowid) as nb,";
		$sql .= " COUNT(cfdi.rowid) as nb_rows, SUM(cfdi.qty) as qty";
		$sql .= " FROM ".$this->db->prefix()."receptiondet_batch as cfdi";
		$sql .= " INNER JOIN ".$this->db->prefix()."reception as recep ON (recep.rowid = cfdi.fk_reception)";
		//      $sql .= ", ".$this->db->prefix()."societe as s";
		if (!$user->hasRight('societe', 'client', 'voir')) {
			$sql .= ", ".$this->db->prefix()."societe_commerciaux as sc";
		}
		$sql .= " WHERE recep.entity IN (".getEntity('reception').")";
		$sql .= " AND cfdi.batch = '".($this->db->escape($this->batch))."'";
		if (!$user->hasRight('societe', 'client', 'voir')) {
			$sql .= " AND recep.fk_soc = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
		}
		//$sql.= " AND exp.fk_statut != 0";
		if ($socid > 0) {
			$sql .= " AND recep.fk_soc = ".((int) $socid);
		}

		$result = $this->db->query($sql);
		if ($result) {
			$obj = $this->db->fetch_object($result);
			$this->stats_reception['customers'] = $obj->nb_customers;
			$this->stats_reception['nb'] = $obj->nb;
			$this->stats_reception['rows'] = $obj->nb_rows;
			$this->stats_reception['qty'] = $obj->qty ? $obj->qty : 0;


			// Virtual products can't be used with kits (see langs with key ErrorNoteAlsoThatSubProductCantBeFollowedByLot)

			// if it's a virtual product, maybe it is in invoice by extension
			//          if (getDolGlobalString('PRODUCT_STATS_WITH_PARENT_PROD_IF_INCDEC')) {
			//              $TFather = $this->getFather();
			//              if (is_array($TFather) && !empty($TFather)) {
			//                  foreach ($TFather as &$fatherData) {
			//                      $pFather = new Product($this->db);
			//                      $pFather->id = $fatherData['id'];
			//                      $qtyCoef = $fatherData['qty'];
			//
			//                      if ($fatherData['incdec']) {
			//                          $pFather->loadStatsReception($socid);
			//
			//                          $this->stats_expedition['customers'] += $pFather->stats_expedition['customers'];
			//                          $this->stats_expedition['nb'] += $pFather->stats_expedition['nb'];
			//                          $this->stats_expedition['rows'] += $pFather->stats_expedition['rows'];
			//                          $this->stats_expedition['qty'] += $pFather->stats_expedition['qty'] * $qtyCoef;
			//                      }
			//                  }
			//              }
			//          }

			$parameters = array('socid' => $socid);
			$reshook = $hookmanager->executeHooks('loadStatsLotReception', $parameters, $this, $action);
			if ($reshook > 0) {
				$this->stats_expedition = $hookmanager->resArray['stats_expedition'];
			}

			return 1;
		} else {
			$this->error = $this->db->error();
			return -1;
		}
	}

	/**
	 *  Charge tableau des stats expedition pour le lot/numéro de série
	 *
	 * @param  int $socid Id societe
	 * @return int                     Array of stats in $this->stats_expedition, <0 if ko or >0 if ok
	 */
	public function loadStatsMo($socid = 0)
	{
		global $user, $hookmanager, $action;

		$error = 0;

		foreach (array('toconsume', 'consumed', 'toproduce', 'produced') as $role) {
			$this->stats_mo['customers_'.$role] = 0;
			$this->stats_mo['nb_'.$role] = 0;
			$this->stats_mo['qty_'.$role] = 0;

			$sql = "SELECT COUNT(DISTINCT c.fk_soc) as nb_customers, COUNT(DISTINCT c.rowid) as nb,";
			$sql .= " SUM(mp.qty) as qty";
			$sql .= " FROM ".$this->db->prefix()."mrp_mo as c";
			$sql .= " INNER JOIN ".$this->db->prefix()."mrp_production as mp ON mp.fk_mo=c.rowid";
			if (!$user->hasRight('societe', 'client', 'voir')) {
				$sql .= "INNER JOIN ".$this->db->prefix()."societe_commerciaux as sc ON sc.fk_soc=c.fk_soc AND sc.fk_user = ".((int) $user->id);
			}
			$sql .= " WHERE ";
			$sql .= " c.entity IN (".getEntity('mo').")";

			$sql .= " AND mp.batch = '".($this->db->escape($this->batch))."'";
			$sql .= " AND mp.role ='".$this->db->escape($role)."'";
			if ($socid > 0) {
				$sql .= " AND c.fk_soc = ".((int) $socid);
			}

			$result = $this->db->query($sql);
			if ($result) {
				$obj = $this->db->fetch_object($result);
				$this->stats_mo['customers_'.$role] = $obj->nb_customers ? $obj->nb_customers : 0;
				$this->stats_mo['nb_'.$role] = $obj->nb ? $obj->nb : 0;
				$this->stats_mo['qty_'.$role] = $obj->qty ? price2num($obj->qty, 'MS') : 0;		// qty may be a float due to the SUM()
			} else {
				$this->error = $this->db->error();
				$error++;
			}
		}

		if (!empty($error)) {
			return -1;
		}

		$parameters = array('socid' => $socid);
		$reshook = $hookmanager->executeHooks('loadStatsCustomerMO', $parameters, $this, $action);
		if ($reshook > 0) {
			$this->stats_mo = $hookmanager->resArray['stats_mo'];
		}

		return 1;
	}


	/**
	 *	Return label of status of object
	 *
	 *	@param      int		$mode       0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto
	 *	@return     string      		Label of status
	 */
	public function getLibStatut($mode = 0)
	{
		return $this->LibStatut(0, $mode);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Return label of a given status
	 *
	 *	@param	int		$status     Status
	 *	@param  int		$mode       0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto
	 *	@return string      		Label of status
	 */
	public function LibStatut($status, $mode = 0)
	{
		// phpcs:enable
		//global $langs;

		//$langs->load('stocks');

		return '';
	}


	/**
	 * getTooltipContentArray
	 * @param array<string,mixed> $params params to construct tooltip data
	 * @since v18
	 * @return array{picto?:string,ref?:string,refsupplier?:string,label?:string,date?:string,date_echeance?:string,amountht?:string,total_ht?:string,totaltva?:string,amountlt1?:string,amountlt2?:string,amountrevenustamp?:string,totalttc?:string}|array{optimize:string}
	 */
	public function getTooltipContentArray($params)
	{
		global $langs;

		$langs->loadLangs(['stocks', 'productbatch']);

		//$option = $params['option'] ?? '';

		$datas = [];
		$datas['picto'] = img_picto('', $this->picto).' <u class="paddingrightonly">'.$langs->trans("Batch").'</u>';
		//$datas['divopen'] = '<div width="100%">';
		$datas['batch'] = '<br><b>'.$langs->trans('Batch').':</b> '.$this->batch;
		if (isDolTms($this->eatby) && !getDolGlobalString('PRODUCT_DISABLE_EATBY')) {
			$datas['eatby'] = '<br><b>'.$langs->trans('EatByDate').':</b> '.dol_print_date($this->eatby, 'day');
		}
		if (isDolTms($this->sellby) && !getDolGlobalString('PRODUCT_DISABLE_SELLBY')) {
			$datas['sellby'] = '<br><b>'.$langs->trans('SellByDate').':</b> '.dol_print_date($this->sellby, 'day');
		}
		//$datas['divclose'] = '</div>';

		return $datas;
	}

	/**
	 *  Return a link to the a lot card (with optionally the picto)
	 * 	Use this->id,this->lastname, this->firstname
	 *
	 *	@param	int		$withpicto				Include picto in link (0=No picto, 1=Include picto into link, 2=Only picto)
	 *	@param	string	$option					On what the link point to
	 *  @param	integer	$notooltip				1=Disable tooltip
	 *  @param	int		$maxlen					Max length of visible user name
	 *  @param  string  $morecss            	Add more css on link
	 *  @param  int     $save_lastsearch_value	-1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 *	@return	string							String with URL
	 */
	public function getNomUrl($withpicto = 0, $option = '', $notooltip = 0, $maxlen = 24, $morecss = '', $save_lastsearch_value = -1)
	{
		global $langs, $hookmanager;

		$result = '';
		$params = [
			'id' => $this->id,
			'objecttype' => $this->element,
			'option' => $option,
		];
		$classfortooltip = 'classfortooltip';
		$dataparams = '';
		if (getDolGlobalInt('MAIN_ENABLE_AJAX_TOOLTIP')) {
			$classfortooltip = 'classforajaxtooltip';
			$dataparams = ' data-params="'.dol_escape_htmltag(json_encode($params)).'"';
			$label = '';
		} else {
			$label = implode($this->getTooltipContentArray($params));
		}

		$url = DOL_URL_ROOT.'/product/stock/productlot_card.php?id='.$this->id;

		if ($option != 'nolink') {
			// Add param to save lastsearch_values or not
			$add_save_lastsearch_values = ($save_lastsearch_value == 1 ? 1 : 0);
			if ($save_lastsearch_value == -1 && isset($_SERVER["PHP_SELF"]) && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) {
				$add_save_lastsearch_values = 1;
			}
			if ($add_save_lastsearch_values) {
				$url .= '&save_lastsearch_values=1';
			}
		}

		$linkclose = '';
		if (empty($notooltip)) {
			if (getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER')) {
				$label = $langs->trans("ShowMyObject");
				$linkclose .= ' alt="'.dolPrintHTMLForAttribute($label).'"';
			}
			$linkclose .= ($label ? ' title="'.dolPrintHTMLForAttribute($label).'"' : ' title="tocomplete"');
			$linkclose .= $dataparams.' class="'.$classfortooltip.($morecss ? ' '.$morecss : '').'"';
		} else {
			$linkclose = ($morecss ? ' class="'.$morecss.'"' : '');
		}

		if ($option == 'nolink') {
			$linkstart = '<span';
		} else {
			$linkstart = '<a href="'.$url.'"';
		}
		$linkstart .= $linkclose.'>';
		if ($option == 'nolink') {
			$linkend = '</span>';
		} else {
			$linkend = '</a>';
		}

		$result .= $linkstart;
		if ($withpicto) {
			$result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="'.(($withpicto != 2) ? 'paddingright ' : '').'"'), 0, 0, $notooltip ? 0 : 1);
		}
		if ($withpicto != 2) {
			$result .= $this->batch;
		}
		$result .= $linkend;

		global $action;
		$hookmanager->initHooks(array('productlotdao'));
		$parameters = array('id' => $this->id, 'getnomurl' => $result);
		$reshook = $hookmanager->executeHooks('getNomUrl', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
		if ($reshook > 0) {
			$result = $hookmanager->resPrint;
		} else {
			$result .= $hookmanager->resPrint;
		}

		return $result;
	}


	/**
	 * Initialise object with example values
	 * Id must be 0 if object instance is a specimen
	 *
	 * @return int
	 */
	public function initAsSpecimen()
	{
		global $conf;

		$now = dol_now();

		// Initialise parameters
		$this->id = 0;
		$this->ref = 'SPECIMEN';
		$this->specimen = 1;

		$this->entity = $conf->entity;
		$this->fk_product = 0;
		$this->batch = 'ABCD123456';
		$this->eatby = $now - 100000;
		$this->sellby = $now - 100000;
		$this->datec = $now - 3600;
		$this->tms = $now;
		$this->fk_user_creat = 0;
		$this->fk_user_modif = 0;
		$this->import_key = '123456';

		return 1;
	}

	/**
	 *  Create a document onto disk according to template module.
	 *
	 * @param  string    $modele      Force model to use ('' to not force)
	 * @param  Translate $outputlangs Object langs to use for output
	 * @param  int       $hidedetails Hide details of lines
	 * @param  int       $hidedesc    Hide description
	 * @param  int       $hideref     Hide ref
	 * @return int                         0 if KO, 1 if OK
	 */
	public function generateDocument($modele, $outputlangs, $hidedetails = 0, $hidedesc = 0, $hideref = 0)
	{
		global $langs;

		$langs->loadLangs(array('stocks', 'productbatch', "products"));
		$outputlangs->loadLangs(array('stocks', 'productbatch', "products"));

		// Positionne le modele sur le nom du modele a utiliser
		if (!dol_strlen($modele)) {
			$modele = '';

			if (!empty($this->model_pdf)) {
				$modele = $this->model_pdf;
			} elseif (getDolGlobalString('PRODUCT_BATCH_ADDON_PDF')) {
				$modele = getDolGlobalString('PRODUCT_BATCH_ADDON_PDF');
			}
		}

		$modelpath = "core/modules/product_batch/doc/";

		return $this->commonGenerateDocument($modelpath, $modele, $outputlangs, $hidedetails, $hidedesc, $hideref);
	}

	/**
	 * Return validation test result for a field
	 *
	 * @param array<string,array{type:string,label:string,enabled:int<0,2>|string,position:int,notnull?:int,visible:int<-2,5>|string,alwayseditable?:int<0,1>,noteditable?:int<0,1>,default?:string,index?:int,foreignkey?:string,searchall?:int<0,1>,isameasure?:int<0,1>,css?:string,csslist?:string,help?:string,showoncombobox?:int<0,2>,disabled?:int<0,1>,arrayofkeyval?:array<int,string>,autofocusoncreate?:int<0,1>,comment?:string,copytoclipboard?:int<1,2>,validate?:int<0,1>}>	$fields	Array of properties of field to show
	 * @param  string  $fieldKey            Key of attribute
	 * @param  string  $fieldValue          value of attribute
	 * @return bool 						Return false if fail, true on success, set $this->error for error message
	 */
	public function validateField($fields, $fieldKey, $fieldValue)
	{
		// Add your own validation rules here.
		if ($fieldKey == 'batch') {
			if (preg_match('/\s/', $fieldValue)) {
				$this->error = 'ErrorABatchShouldNotContainsSpaces';
				return false;
			}
		}

		return parent::validateField($fields, $fieldKey, $fieldValue);
	}
}
