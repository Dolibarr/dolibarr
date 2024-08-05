<?php
/* Copyright (C) 2003-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2008 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2011	   Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2016	   Francis Appels       <francis.appels@yahoo.com>
 * Copyright (C) 2019-2024  Frédéric France         <frederic.france@free.fr>
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
 *  \file       htdocs/product/stock/class/entrepot.class.php
 *  \ingroup    stock
 *  \brief      File for class to manage warehouses
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';


/**
 *  Class to manage warehouses
 */
class Entrepot extends CommonObject
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'stock';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'entrepot';

	/**
	 * @var string String with name of icon for myobject. Must be the part after the 'object_' into object_myobject.png
	 */
	public $picto = 'stock';

	/**
	 * @var string	Label
	 * @deprecated
	 * @see $label
	 */
	public $libelle;

	/**
	 * @var string  Label
	 */
	public $label;

	/**
	 * @var string description
	 */
	public $description;

	public $statut;

	/**
	 * @var string Place
	 */
	public $lieu;

	/**
	 * @var string Address
	 */
	public $address;

	/**
	 * @var string Zipcode
	 */
	public $zip;

	/**
	 * @var string Town
	 */
	public $town;

	/**
	 * @var string Phone
	 */
	public $phone;

	/**
	 * @var string Fax
	 */
	public $fax;

	/**
	 * @var int ID of parent
	 */
	public $fk_parent;

	/**
	 * @var int ID of project
	 */
	public $fk_project;

	/**
	 * @var	int	Warehouse usage ID
	 */
	public $warehouse_usage;

	/**
	 *  'type' field format:
	 *  	'integer', 'integer:ObjectClass:PathToClass[:AddCreateButtonOrNot[:Filter[:Sortfield]]]',
	 *  	'select' (list of values are in 'options'),
	 *  	'sellist:TableName:LabelFieldName[:KeyFieldName[:KeyFieldParent[:Filter[:CategoryIdType[:CategoryIdList[:SortField]]]]]]',
	 *  	'chkbxlst:...',
	 *  	'varchar(x)',
	 *  	'text', 'text:none', 'html',
	 *   	'double(24,8)', 'real', 'price', 'stock',
	 *  	'date', 'datetime', 'timestamp', 'duration',
	 *  	'boolean', 'checkbox', 'radio', 'array',
	 *  	'mail', 'phone', 'url', 'password', 'ip'
	 *		Note: Filter must be a Dolibarr Universal Filter syntax string. Example: "(t.ref:like:'SO-%') or (t.date_creation:<:'20160101') or (t.status:!=:0) or (t.nature:is:NULL)"
	 *  'label' the translation key.
	 *  'picto' is code of a picto to show before value in forms
	 *  'enabled' is a condition when the field must be managed (Example: 1 or 'getDolGlobalInt("MY_SETUP_PARAM")' or 'isModEnabled("multicurrency")' ...)
	 *  'position' is the sort order of field.
	 *  'notnull' is set to 1 if not null in database. Set to -1 if we must set data to null if empty ('' or 0).
	 *  'visible' says if field is visible in list (Examples: 0=Not visible, 1=Visible on list and create/update/view forms, 2=Visible on list only, 3=Visible on create/update/view form only (not list), 4=Visible on list and update/view form only (not create). 5=Visible on list and view only (not create/not update). Using a negative value means field is not shown by default on list but can be selected for viewing)
	 *  'noteditable' says if field is not editable (1 or 0)
	 *  'alwayseditable' says if field can be modified also when status is not draft ('1' or '0')
	 *  'default' is a default value for creation (can still be overwrote by the Setup of Default Values if field is editable in creation form). Note: If default is set to '(PROV)' and field is 'ref', the default value will be set to '(PROVid)' where id is rowid when a new record is created.
	 *  'index' if we want an index in database.
	 *  'foreignkey'=>'tablename.field' if the field is a foreign key (it is recommended to name the field fk_...).
	 *  'searchall' is 1 if we want to search in this field when making a search from the quick search button.
	 *  'isameasure' must be set to 1 or 2 if field can be used for measure. Field type must be summable like integer or double(24,8). Use 1 in most cases, or 2 if you don't want to see the column total into list (for example for percentage)
	 *  'css' and 'cssview' and 'csslist' is the CSS style to use on field. 'css' is used in creation and update. 'cssview' is used in view mode. 'csslist' is used for columns in lists. For example: 'css'=>'minwidth300 maxwidth500 widthcentpercentminusx', 'cssview'=>'wordbreak', 'csslist'=>'tdoverflowmax200'
	 *  'help' and 'helplist' is a 'TranslationString' to use to show a tooltip on field. You can also use 'TranslationString:keyfortooltiponlick' for a tooltip on click.
	 *  'showoncombobox' if value of the field must be visible into the label of the combobox that list record
	 *  'disabled' is 1 if we want to have the field locked by a 'disabled' attribute. In most cases, this is never set into the definition of $fields into class, but is set dynamically by some part of code.
	 *  'arrayofkeyval' to set a list of values if type is a list of predefined values. For example: array("0"=>"Draft","1"=>"Active","-1"=>"Cancel"). Note that type can be 'integer' or 'varchar'
	 *  'autofocusoncreate' to have field having the focus on a create form. Only 1 field should have this property set to 1.
	 *  'comment' is not used. You can store here any text of your choice. It is not used by application.
	 *	'validate' is 1 if you need to validate the field with $this->validateField(). Need MAIN_ACTIVATE_VALIDATION_RESULT.
	 *  'copytoclipboard' is 1 or 2 to allow to add a picto to copy value into clipboard (1=picto after label, 2=picto after value)
	 *
	 *  Note: To have value dynamic, you can set value to 0 in definition and edit the value on the fly into the constructor.
	 */

	// BEGIN MODULEBUILDER PROPERTIES
	/**
	 * @var array<string,array{type:string,label:string,enabled:int<0,2>|string,position:int,notnull?:int,visible:int,noteditable?:int,default?:string,index?:int,foreignkey?:string,searchall?:int,isameasure?:int,css?:string,csslist?:string,help?:string,showoncombobox?:int,disabled?:int,arrayofkeyval?:array<int,string>,comment?:string}>  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields = array(
		'rowid' => array('type' => 'integer', 'label' => 'ID', 'enabled' => 1, 'visible' => 0, 'notnull' => 1, 'position' => 10),
		'entity' => array('type' => 'integer', 'label' => 'Entity', 'enabled' => 1, 'visible' => 0, 'default' => '1', 'notnull' => 1, 'index' => 1, 'position' => 15),
		'ref' => array('type' => 'varchar(255)', 'label' => 'Ref', 'enabled' => 1, 'visible' => 1, 'showoncombobox' => 1, 'position' => 25, 'searchall' => 1),
		'description' => array('type' => 'text', 'label' => 'Description', 'enabled' => 1, 'visible' => -2, 'position' => 35, 'searchall' => 1),
		'lieu' => array('type' => 'varchar(64)', 'label' => 'LocationSummary', 'enabled' => 1, 'visible' => 1, 'position' => 40, 'showoncombobox' => 2, 'searchall' => 1),
		'fk_parent' => array('type' => 'integer:Entrepot:product/stock/class/entrepot.class.php:1:((statut:=:1) AND (entity:IN:__SHARED_ENTITIES__))', 'label' => 'ParentWarehouse', 'enabled' => 1, 'visible' => -2, 'position' => 41),
		'fk_project' => array('type' => 'integer:Project:projet/class/project.class.php:1:(fk_statut:=:1)', 'label' => 'Project', 'enabled' => '$conf->project->enabled', 'visible' => -1, 'position' => 42),
		'address' => array('type' => 'varchar(255)', 'label' => 'Address', 'enabled' => 1, 'visible' => -2, 'position' => 45, 'searchall' => 1),
		'zip' => array('type' => 'varchar(10)', 'label' => 'Zip', 'enabled' => 1, 'visible' => -2, 'position' => 50, 'searchall' => 1),
		'town' => array('type' => 'varchar(50)', 'label' => 'Town', 'enabled' => 1, 'visible' => -2, 'position' => 55, 'searchall' => 1),
		'fk_departement' => array('type' => 'integer', 'label' => 'State', 'enabled' => 1, 'visible' => 0, 'position' => 60),
		'fk_pays' => array('type' => 'integer:Ccountry:core/class/ccountry.class.php', 'label' => 'Country', 'enabled' => 1, 'visible' => -1, 'position' => 65),
		'phone' => array('type' => 'varchar(20)', 'label' => 'Phone', 'enabled' => 1, 'visible' => -2, 'position' => 70, 'searchall' => 1),
		'fax' => array('type' => 'varchar(20)', 'label' => 'Fax', 'enabled' => 1, 'visible' => -2, 'position' => 75, 'searchall' => 1),
		//'fk_user_author' =>array('type'=>'integer', 'label'=>'Fk user author', 'enabled'=>1, 'visible'=>-2, 'position'=>82),
		'datec' => array('type' => 'datetime', 'label' => 'DateCreation', 'enabled' => 1, 'visible' => -2, 'position' => 300),
		'tms' => array('type' => 'timestamp', 'label' => 'DateModification', 'enabled' => 1, 'visible' => -2, 'notnull' => 1, 'position' => 301),
		'warehouse_usage' => array('type' => 'integer', 'label' => 'WarehouseUsage', 'enabled' => 'getDolGlobalInt("STOCK_USE_WAREHOUSE_USAGE")', 'visible' => 1, 'position' => 400, 'default' => 1, 'arrayofkeyval' => array(1 => 'InternalWarehouse', 2 => 'ExternalWarehouse')),
		//'import_key' =>array('type'=>'varchar(14)', 'label'=>'ImportId', 'enabled'=>1, 'visible'=>-2, 'position'=>1000),
		//'model_pdf' =>array('type'=>'varchar(255)', 'label'=>'ModelPDF', 'enabled'=>1, 'visible'=>0, 'position'=>1010),
		'statut' => array('type' => 'tinyint(4)', 'label' => 'Status', 'enabled' => 1, 'visible' => 1, 'position' => 500, 'css' => 'minwidth50'),
	);
	// END MODULEBUILDER PROPERTIES


	/**
	 * Warehouse closed, inactive
	 */
	const STATUS_CLOSED = 0;

	/**
	 * Warehouse open and any operations are allowed (customer shipping, supplier dispatch, internal stock transfers/corrections).
	 */
	const STATUS_OPEN_ALL = 1;

	/**
	 * Warehouse open and only operations for stock transfers/corrections allowed (not for customer shipping and supplier dispatch).
	 * Used when ENTREPOT_EXTRA_STATUS is on;
	 */
	const STATUS_OPEN_INTERNAL = 2;


	/**
	 * Warehouse that must be include for stock calculation (default)
	 */
	const USAGE_INTERNAL = 1;

	/**
	 * Warehouse that must be excluded for stock calculation (scrapping stock, virtual warehouses, ...)
	 */
	const USAGE_EXTTERNAL = 2;



	/**
	 *  Constructor
	 *
	 *  @param      DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;

		$this->ismultientitymanaged = 1;

		$this->labelStatus[self::STATUS_CLOSED] = 'Closed2';
		if (getDolGlobalString('ENTREPOT_EXTRA_STATUS')) {
			$this->labelStatus[self::STATUS_OPEN_ALL] = 'OpenAnyMovement';
			$this->labelStatus[self::STATUS_OPEN_INTERNAL] = 'OpenInternal';
		} else {
			$this->labelStatus[self::STATUS_OPEN_ALL] = 'Opened';
		}
	}

	/**
	 *	Creation d'un entrepot en base
	 *
	 *	@param		User	$user		Object user that create the warehouse
	 *	@param		int		$notrigger	0=launch triggers after, 1=disable triggers
	 *	@return		int					Return >0 if OK, =<0 if KO
	 */
	public function create($user, $notrigger = 0)
	{
		global $conf;

		$error = 0;

		$this->label = trim($this->label);

		// Error if label not defined
		if ($this->label == '') {
			$this->error = "ErrorFieldRequired";
			return 0;
		}

		$now = dol_now();

		$this->db->begin();

		$sql = "INSERT INTO ".$this->db->prefix()."entrepot (ref, entity, datec, fk_user_author, fk_parent, fk_project)";
		$sql .= " VALUES ('".$this->db->escape($this->label)."', ".((int) $conf->entity).", '".$this->db->idate($now)."', ".((int) $user->id).", ".($this->fk_parent > 0 ? ((int) $this->fk_parent) : "NULL").", ".($this->fk_project > 0 ? ((int) $this->fk_project) : "NULL").")";

		dol_syslog(get_class($this)."::create", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {
			$id = $this->db->last_insert_id($this->db->prefix()."entrepot");
			if ($id > 0) {
				$this->id = $id;

				if (!$error) {
					$result = $this->update($id, $user);
					if ($result <= 0) {
						$error++;
					}
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
					$result = $this->call_trigger('WAREHOUSE_CREATE', $user);
					if ($result < 0) {
						$error++;
					}
					// End call triggers
				}

				if (!$error) {
					$this->db->commit();
					return $id;
				} else {
					dol_syslog(get_class($this)."::create return -3");
					$this->db->rollback();
					return -3;
				}
			} else {
				$this->error = "Failed to get insert id";
				dol_syslog(get_class($this)."::create return -2");
				return -2;
			}
		} else {
			$this->error = $this->db->error();
			dol_syslog(get_class($this)."::create Error ".$this->db->error());
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *	Update properties of a warehouse
	 *
	 *	@param		int		$id			id of warehouse to modify
	 *	@param		User	$user		User object
	 *	@param		int 	$notrigger	0=launch triggers after, 1=disable trigge
	 *	@return		int					Return >0 if OK, <0 if KO
	 */
	public function update($id, $user, $notrigger = 0)
	{
		$error = 0;

		if (empty($id)) {
			$id = $this->id;
		}

		// Check if new parent is already a child of current warehouse
		if (!empty($this->fk_parent)) {
			$TChildWarehouses = array($id);
			$TChildWarehouses = $this->get_children_warehouses($this->id, $TChildWarehouses);
			if (in_array($this->fk_parent, $TChildWarehouses)) {
				$this->error = 'ErrorCannotAddThisParentWarehouse';
				return -2;
			}
		}

		$this->label = trim($this->label);

		$this->description = trim($this->description);

		$this->lieu = trim($this->lieu);

		$this->address = trim($this->address);
		$this->zip = trim($this->zip);
		$this->town = trim($this->town);
		$this->country_id = ($this->country_id > 0 ? $this->country_id : 0);

		$sql = "UPDATE ".$this->db->prefix()."entrepot";
		$sql .= " SET ref = '".$this->db->escape($this->label)."'";
		$sql .= ", fk_parent = ".(($this->fk_parent > 0) ? $this->fk_parent : "NULL");
		$sql .= ", fk_project = ".(($this->fk_project > 0) ? $this->fk_project : "NULL");
		$sql .= ", description = '".$this->db->escape($this->description)."'";
		$sql .= ", statut = ".((int) $this->statut);
		$sql .= ", lieu = '".$this->db->escape($this->lieu)."'";
		$sql .= ", address = '".$this->db->escape($this->address)."'";
		$sql .= ", zip = '".$this->db->escape($this->zip)."'";
		$sql .= ", town = '".$this->db->escape($this->town)."'";
		$sql .= ", fk_pays = ".((int) $this->country_id);
		$sql .= ", phone = '".$this->db->escape($this->phone)."'";
		$sql .= ", fax = '".$this->db->escape($this->fax)."'";
		$sql .= " WHERE rowid = ".((int) $id);

		$this->db->begin();

		dol_syslog(get_class($this)."::update", LOG_DEBUG);
		$resql = $this->db->query($sql);

		if (!$resql) {
			$error++;
			$this->errors[] = "Error ".$this->db->lasterror();
		}

		if (!$error) {
			$result = $this->insertExtraFields();
			if ($result < 0) {
				$error++;
			}
		}

		if (!$error && !$notrigger) {
			// Call triggers
			$result = $this->call_trigger('WAREHOUSE_MODIFY', $user);
			if ($result < 0) {
				$error++;
			}
			// End call triggers
		}

		if (!$error) {
			$this->db->commit();
			return 1;
		} else {
			$this->db->rollback();
			$this->error = $this->db->lasterror();
			return -1;
		}
	}


	/**
	 *	Delete a warehouse
	 *
	 *	@param		User	$user		   Object user that made deletion
	 *  @param      int     $notrigger     1=No trigger
	 *	@return		int					   Return integer <0 if KO, >0 if OK
	 */
	public function delete($user, $notrigger = 0)
	{
		global $conf;

		$error = 0;

		dol_syslog(get_class($this)."::delete id=".$this->id, LOG_DEBUG);

		$this->db->begin();

		if (!$error && empty($notrigger)) {
			// Call trigger
			$result = $this->call_trigger('WAREHOUSE_DELETE', $user);
			if ($result < 0) {
				$error++;
			}
			// End call triggers
		}

		if (!$error) {
			$sql = "DELETE FROM ".$this->db->prefix()."product_batch";
			$sql .= " WHERE fk_product_stock IN (SELECT rowid FROM ".$this->db->prefix()."product_stock as ps WHERE ps.fk_entrepot = ".((int) $this->id).")";
			$result = $this->db->query($sql);
			if (!$result) {
				$error++;
				$this->errors[] = $this->db->lasterror();
			}
		}

		if (!$error) {
			$elements = array('stock_mouvement', 'product_stock');
			foreach ($elements as $table) {
				if (!$error) {
					$sql = "DELETE FROM ".$this->db->prefix().$table;
					$sql .= " WHERE fk_entrepot = ".((int) $this->id);

					$result = $this->db->query($sql);
					if (!$result) {
						$error++;
						$this->errors[] = $this->db->lasterror();
					}
				}
			}
		}

		// Removed extrafields
		if (!$error) {
			$result = $this->deleteExtraFields();
			if ($result < 0) {
				$error++;
				dol_syslog(get_class($this)."::delete Error ".$this->error, LOG_ERR);
			}
		}

		if (!$error) {
			$sql = "DELETE FROM ".$this->db->prefix()."entrepot";
			$sql .= " WHERE rowid = ".((int) $this->id);
			$resql1 = $this->db->query($sql);
			if (!$resql1) {
				$error++;
				$this->errors[] = $this->db->lasterror();
				dol_syslog(get_class($this)."::delete Error ".$this->db->lasterror(), LOG_ERR);
			}
		}

		if (!$error) {
			// Update denormalized fields because we change content of produt_stock. Warning: Do not use "SET p.stock", does not works with pgsql
			$sql = "UPDATE ".$this->db->prefix()."product as p SET stock = (SELECT SUM(ps.reel) FROM ".$this->db->prefix()."product_stock as ps WHERE ps.fk_product = p.rowid)";
			$resql2 = $this->db->query($sql);
			if (!$resql2) {
				$error++;
				$this->errors[] = $this->db->lasterror();
				dol_syslog(get_class($this)."::delete Error ".$this->db->lasterror(), LOG_ERR);
			}
		}

		if (!$error) {
			$this->db->commit();
			return 1;
		} else {
			$this->db->rollback();
			return -1;
		}
	}


	/**
	 *	Load warehouse data
	 *
	 *	@param		int		$id     Warehouse id
	 *	@param		string	$ref	Warehouse label
	 *	@return		int				>0 if OK, <0 if KO
	 */
	public function fetch($id, $ref = '')
	{
		global $conf;

		dol_syslog(get_class($this)."::fetch id=".$id." ref=".$ref);

		// Check parameters
		if (!$id && !$ref) {
			$this->error = 'ErrorWrongParameters';
			dol_syslog(get_class($this)."::fetch ".$this->error);
			return -1;
		}

		$sql  = "SELECT rowid, entity, fk_parent, fk_project, ref as label, description, statut, lieu, address, zip, town, fk_pays as country_id, phone, fax,";
		$sql .= " model_pdf, import_key";
		$sql .= " FROM ".$this->db->prefix()."entrepot";
		if ($id) {
			$sql .= " WHERE rowid = ".((int) $id);
		} else {
			$sql .= " WHERE entity IN (".getEntity('stock').")";
			if ($ref) {
				$sql .= " AND ref = '".$this->db->escape($ref)."'";
			}
		}

		$result = $this->db->query($sql);
		if ($result) {
			if ($this->db->num_rows($result) > 0) {
				$obj = $this->db->fetch_object($result);

				$this->id             = $obj->rowid;
				$this->entity         = $obj->entity;
				$this->fk_parent      = $obj->fk_parent;
				$this->fk_project     = $obj->fk_project;
				$this->ref            = $obj->label;
				$this->label          = $obj->label;
				$this->description    = $obj->description;
				$this->statut         = $obj->statut;
				$this->lieu           = $obj->lieu;
				$this->address        = $obj->address;
				$this->zip            = $obj->zip;
				$this->town           = $obj->town;
				$this->country_id     = $obj->country_id;
				$this->phone          = $obj->phone;
				$this->fax            = $obj->fax;

				$this->model_pdf      = $obj->model_pdf;
				$this->import_key     = $obj->import_key;

				// Retrieve all extrafield
				// fetch optionals attributes and labels
				$this->fetch_optionals();

				include_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
				$tmp = getCountry($this->country_id, 'all');
				$this->country = $tmp['label'];
				$this->country_code = $tmp['code'];

				return 1;
			} else {
				$this->error = "Record Not Found";
				return 0;
			}
		} else {
			$this->error = $this->db->error();
			return -1;
		}
	}


	/**
	 * 	Load warehouse info data
	 *
	 *  @param	int		$id      warehouse id
	 *  @return	void
	 */
	public function info($id)
	{
		$sql = "SELECT e.rowid, e.datec, e.tms as datem, e.fk_user_author";
		$sql .= " FROM ".$this->db->prefix()."entrepot as e";
		$sql .= " WHERE e.rowid = ".((int) $id);

		dol_syslog(get_class($this)."::info", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {
			if ($this->db->num_rows($result)) {
				$obj = $this->db->fetch_object($result);

				$this->id = $obj->rowid;

				$this->user_creation_id = $obj->fk_user_author;
				$this->date_creation     = $this->db->jdate($obj->datec);
				$this->date_modification = empty($obj->datem) ? '' : $this->db->jdate($obj->datem);
			}

			$this->db->free($result);
		} else {
			dol_print_error($this->db);
		}
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return list of all warehouses
	 *
	 *	@param	int		$status		Status
	 * 	@return array				Array list of warehouses
	 */
	public function list_array($status = 1)
	{
		// phpcs:enable
		$liste = array();

		$sql = "SELECT rowid, ref as label";
		$sql .= " FROM ".$this->db->prefix()."entrepot";
		$sql .= " WHERE entity IN (".getEntity('stock').")";
		$sql .= " AND statut = ".((int) $status);

		$result = $this->db->query($sql);
		$i = 0;
		$num = $this->db->num_rows($result);
		if ($result) {
			while ($i < $num) {
				$row = $this->db->fetch_row($result);
				$liste[$row[0]] = $row[1];
				$i++;
			}
			$this->db->free($result);
		}
		return $liste;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Return number of unique different product into a warehouse
	 *
	 * 	@return		array|int		Array('nb'=>Nb, 'value'=>Value)
	 */
	public function nb_different_products()
	{
		// phpcs:enable
		$ret = array();

		$sql = "SELECT count(distinct p.rowid) as nb";
		$sql .= " FROM ".$this->db->prefix()."product_stock as ps";
		$sql .= ", ".$this->db->prefix()."product as p";
		$sql .= " WHERE ps.fk_entrepot = ".((int) $this->id);
		$sql .= " AND ps.fk_product = p.rowid";

		//print $sql;
		$result = $this->db->query($sql);
		if ($result) {
			$obj = $this->db->fetch_object($result);
			$ret['nb'] = $obj->nb;
			$this->db->free($result);
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}

		return $ret;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Return stock and value of warehosue
	 *
	 * 	@return		array|int		Array('nb'=>Nb, 'value'=>Value)
	 */
	public function nb_products()
	{
		global $conf;
		// phpcs:enable
		$ret = array();

		//For MultiCompany PMP per entity
		$separatedPMP = false;
		if (getDolGlobalString('MULTICOMPANY_PRODUCT_SHARING_ENABLED') && getDolGlobalString('MULTICOMPANY_PMP_PER_ENTITY_ENABLED')) {
			$separatedPMP = true;
		}

		if ($separatedPMP) {
			$sql = "SELECT sum(ps.reel) as nb, sum(ps.reel * pa.pmp) as value";
		} else {
			$sql = "SELECT sum(ps.reel) as nb, sum(ps.reel * p.pmp) as value";
		}
		$sql .= " FROM ".$this->db->prefix()."product_stock as ps";
		$sql .= ", ".$this->db->prefix()."product as p";
		if ($separatedPMP) {
			$sql .= ", ".$this->db->prefix()."product_perentity as pa";
		}
		$sql .= " WHERE ps.fk_entrepot = ".((int) $this->id);
		if ($separatedPMP) {
			$sql .= " AND pa.fk_product = p.rowid AND pa.entity = ". (int) $conf->entity;
		}
		$sql .= " AND ps.fk_product = p.rowid";
		//print $sql;
		$result = $this->db->query($sql);
		if ($result) {
			$obj = $this->db->fetch_object($result);
			$ret['nb'] = $obj->nb;
			$ret['value'] = $obj->value;
			$this->db->free($result);
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}

		return $ret;
	}

	/**
	 *	Return label of status of object
	 *
	 *	@param      int		$mode       0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto
	 *	@return     string      		Label of status
	 */
	public function getLibStatut($mode = 0)
	{
		return $this->LibStatut($this->statut, $mode);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Return label of a given status
	 *
	 *	@param	int		$status     Id status
	 *	@param  int		$mode       0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto
	 *	@return string      		Label of status
	 */
	public function LibStatut($status, $mode = 0)
	{
		// phpcs:enable
		global $langs;

		$statusType = 'status5';
		if ($status > 0) {
			$statusType = 'status4';
		}

		$langs->load('stocks');
		$label = $langs->transnoentitiesnoconv($this->labelStatus[$status]);
		$labelshort = $langs->transnoentitiesnoconv($this->labelStatus[$status]);

		return dolGetStatus($label, $labelshort, '', $statusType, $mode);
	}

	/**
	 * getTooltipContentArray
	 *
	 * @param 	array 	$params 	Params to construct tooltip data
	 * @since 	v18
	 * @return 	array
	 */
	public function getTooltipContentArray($params)
	{
		global $conf, $langs, $user;

		$langs->load('stocks');

		$datas = [];

		$option = $params['option'] ?? '';
		$nofetch = !empty($params['nofetch']);

		if (getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER')) {
			return ['optimize' => $langs->trans("Warehouse")];
		}
		$datas['picto'] = img_picto('', $this->picto).' <u class="paddingrightonly">'.$langs->trans("Warehouse").'</u>';
		if (isset($this->statut)) {
			$datas['picto'] .= ' '.$this->getLibStatut(5);
		}
		$datas['ref'] = '<br><b>'.$langs->trans('Ref').':</b> '.(empty($this->ref) ? $this->label : $this->ref);
		if (!empty($this->lieu)) {
			$datas['locationsummary'] = '<br><b>'.$langs->trans('LocationSummary').':</b> '.$this->lieu;
		}
		// show categories for this record only in ajax to not overload lists
		if (!$nofetch && isModEnabled('category')) {
			require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
			$form = new Form($this->db);
			$datas['categories_warehouse'] = '<br>' . $form->showCategories($this->id, Categorie::TYPE_WAREHOUSE, 1, 1);
		}

		return $datas;
	}

	/**
	 *	Return clickable name (possibility with the pictogram)
	 *
	 *	@param		int		$withpicto				with pictogram
	 *	@param		string	$option					Where the link point to
	 *  @param      int     $showfullpath   		0=Show ref only. 1=Show full path instead of Ref (this->fk_parent must be defined)
	 *  @param	    int   	$notooltip				1=Disable tooltip
	 *  @param  	string  $morecss            	Add more css on link
	 *  @param  	int     $save_lastsearch_value  -1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 *	@return		string							String with URL
	 */
	public function getNomUrl($withpicto = 0, $option = '', $showfullpath = 0, $notooltip = 0, $morecss = '', $save_lastsearch_value = -1)
	{
		global $conf, $langs, $hookmanager;
		$langs->load("stocks");

		if (!empty($conf->dol_no_mouse_hover)) {
			$notooltip = 1; // Force disable tooltips
		}

		if (getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER') && $withpicto) {
			$withpicto = 0;
		}

		$result = '';
		$params = [
			'id' => $this->id,
			'objecttype' => $this->element,
			'option' => $option,
			'nofetch' => 1,
		];
		$classfortooltip = 'classfortooltip';
		$dataparams = '';
		if (getDolGlobalInt('MAIN_ENABLE_AJAX_TOOLTIP')) {
			$classfortooltip = 'classforajaxtooltip';
			$dataparams = ' data-params="'.dol_escape_htmltag(json_encode($params)).'"';
			$label = 'ToComplete';
		} else {
			$label = implode($this->getTooltipContentArray($params));
		}

		$url = DOL_URL_ROOT.'/product/stock/card.php?id='.$this->id;

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
				$label = $langs->trans("Warehouse");
				$linkclose .= ' alt="'.dol_escape_htmltag($label, 1).'"';
			}
			$linkclose .= ($label ? ' title="'.dol_escape_htmltag($label, 1).'"' : ' title="tocomplete"');
			$linkclose .= $dataparams.' class="'.$classfortooltip.'"';
		}

		$linkstart = '<a href="'.$url.'"';
		$linkstart .= $linkclose.'>';
		$linkend = '</a>';

		$result .= $linkstart;
		if ($withpicto) {
			$result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="'.(($withpicto != 2) ? 'paddingright ' : '').'"'), 0, 0, $notooltip ? 0 : 1);
		}
		if ($withpicto != 2) {
			$result .= (($showfullpath || getDolGlobalString('STOCK_ALWAYS_SHOW_FULL_ARBO')) ? $this->get_full_arbo() : $this->label);
		}
		$result .= $linkend;

		global $action;
		$hookmanager->initHooks(array('warehousedao'));
		$parameters = array('id' => $this->id, 'getnomurl' => &$result, 'withpicto' => $withpicto, 'option' => $option, 'showfullpath' => $showfullpath, 'notooltip' => $notooltip);
		$reshook = $hookmanager->executeHooks('getNomUrl', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
		if ($reshook > 0) {
			$result = $hookmanager->resPrint;
		} else {
			$result .= $hookmanager->resPrint;
		}

		return $result;
	}

	/**
	 *  Initialise an instance with random values.
	 *  Used to build previews or test instances.
	 *	id must be 0 if object instance is a specimen.
	 *
	 *  @return int
	 */
	public function initAsSpecimen()
	{
		global $user, $langs, $conf, $mysoc;

		$now = dol_now();

		// Initialize parameters
		$this->id = 0;
		$this->label = 'WAREHOUSE SPECIMEN';
		$this->description = 'WAREHOUSE SPECIMEN '.dol_print_date($now, 'dayhourlog');
		$this->statut = 1;
		$this->specimen = 1;

		$this->lieu = 'Location test';
		$this->address = '21 jump street';
		$this->zip = '99999';
		$this->town = 'MyTown';
		$this->country_id = 1;
		$this->country_code = 'FR';

		return 1;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Return full path to current warehouse
	 *
	 *	@return		string	String full path to current warehouse separated by " >> "
	 */
	public function get_full_arbo()
	{
		// phpcs:enable
		$TArbo = array($this->label);

		$protection = 100; // We limit depth of warehouses to 100

		$warehousetmp = new Entrepot($this->db);

		$parentid = $this->fk_parent; // If parent_id not defined on current object, we do not start consecutive searches of parents
		$i = 0;
		while ($parentid > 0 && $i < $protection) {
			$sql = "SELECT fk_parent FROM ".$this->db->prefix()."entrepot";
			$sql .= " WHERE rowid = ".((int) $parentid);

			$resql = $this->db->query($sql);
			if ($resql) {
				$objarbo = $this->db->fetch_object($resql);
				if ($objarbo) {
					$warehousetmp->fetch($parentid);
					$TArbo[] = $warehousetmp->label;
					$parentid = $objarbo->fk_parent;
				} else {
					break;
				}
			} else {
				dol_print_error($this->db);
			}

			$i++;
		}

		return implode(' >> ', array_reverse($TArbo));
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Return array of children warehouses ids from $id warehouse (recursive function)
	 *
	 * @param   int         $id					id parent warehouse
	 * @param   integer[]	$TChildWarehouses	array which will contain all children (param by reference)
	 * @return  integer[]   $TChildWarehouses	array which will contain all children
	 */
	public function get_children_warehouses($id, &$TChildWarehouses)
	{
		// phpcs:enable

		$sql = "SELECT rowid
				FROM ".$this->db->prefix()."entrepot
				WHERE fk_parent = ".((int) $id);

		$resql = $this->db->query($sql);
		if ($resql) {
			while ($res = $this->db->fetch_object($resql)) {
				$TChildWarehouses[] = $res->rowid;
				$this->get_children_warehouses($res->rowid, $TChildWarehouses);
			}
		}

		return $TChildWarehouses;
	}

	/**
	 *	Create object on disk
	 *
	 *	@param     string		$modele			force le modele a utiliser ('' to not force)
	 * 	@param     Translate	$outputlangs	Object langs to use for output
	 *  @param     int			$hidedetails    Hide details of lines
	 *  @param     int			$hidedesc       Hide description
	 *  @param     int			$hideref        Hide ref
	 *  @return    int             				0 if KO, 1 if OK
	 */
	public function generateDocument($modele, $outputlangs, $hidedetails = 0, $hidedesc = 0, $hideref = 0)
	{
		global $conf, $user, $langs;

		$langs->load("stocks");
		$outputlangs->load("products");

		if (!dol_strlen($modele)) {
			$modele = 'standard';

			if ($this->model_pdf) {
				$modele = $this->model_pdf;
			} elseif (getDolGlobalString('STOCK_ADDON_PDF')) {
				$modele = getDolGlobalString('STOCK_ADDON_PDF');
			}
		}

		$modelpath = "core/modules/stock/doc/";

		return $this->commonGenerateDocument($modelpath, $modele, $outputlangs, $hidedetails, $hidedesc, $hideref);
	}

	/**
	 * Sets object to supplied categories.
	 *
	 * Deletes object from existing categories not supplied.
	 * Adds it to non existing supplied categories.
	 * Existing categories are left untouch.
	 *
	 * @param 	int[]|int 	$categories 	Category or categories IDs
	 * @return 	int							Return integer <0 if KO, >0 if OK
	 */
	public function setCategories($categories)
	{
		require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
		return parent::setCategoriesCommon($categories, Categorie::TYPE_WAREHOUSE);
	}

	/**
	 *	Return clicable link of object (with eventually picto)
	 *
	 *	@param      string	    $option                 Where point the link (0=> main card, 1,2 => shipment, 'nolink'=>No link)
	 *  @param		array		$arraydata				Array of data
	 *  @return		string								HTML Code for Kanban thumb.
	 */
	public function getKanbanView($option = '', $arraydata = null)
	{
		$selected = (empty($arraydata['selected']) ? 0 : $arraydata['selected']);

		$return = '<div class="box-flex-item box-flex-grow-zero">';
		$return .= '<div class="info-box info-box-sm">';
		$return .= '<div class="info-box-icon bg-infobox-action" >';
		$return .= img_picto('', $this->picto);
		$return .= '</div>';
		$return .= '<div class="info-box-content" >';
		$return .= '<span class="info-box-ref inline-block tdoverflowmax150 valignmiddle">'.(method_exists($this, 'getNomUrl') ? $this->getNomUrl() : $this->ref).'</span>';
		if ($selected >= 0) {
			$return .= '<input id="cb'.$this->id.'" class="flat checkforselect fright" type="checkbox" name="toselect[]" value="'.$this->id.'"'.($selected ? ' checked="checked"' : '').'>';
		}
		if (property_exists($this, 'lieu') && (!empty($this->lieu))) {
			$return .= '<br><span class="info-box-label opacitymedium">'.$this->lieu.'</span>';
		}
		if (property_exists($this, 'sellvalue') && $this->sellvalue != 0) {
			$return .= '<br><span class="info-box-label amount">'.price($this->sellvalue).'</span>';
		}
		if (method_exists($this, 'getLibStatut')) {
			$return .= '<br><div class="info-box-status">'.$this->getLibStatut(3).'</div>';
		}
		$return .= '</div>';
		$return .= '</div>';
		$return .= '</div>';
		return $return;
	}
}
