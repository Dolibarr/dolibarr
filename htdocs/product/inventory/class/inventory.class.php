<?php
/* Copyright (C) 2007-2019  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2014-2016  Juanjo Menent       <jmenent@2byte.es>
 * Copyright (C) 2015       Florian Henry       <florian.henry@open-concept.pro>
 * Copyright (C) 2015       Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
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
 * \file        product/inventory/class/inventory.class.php
 * \ingroup     inventory
 * \brief       This file is a CRUD class file for Inventory (Create/Read/Update/Delete)
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobjectline.class.php';
//require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
//require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

/**
 * Class for Inventory
 */
class Inventory extends CommonObject
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'inventory';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'inventory';

	/**
	 * @var array  Does inventory support multicompany module ? 0=No test on entity, 1=Test with field entity, 2=Test with link by societe
	 */
	public $ismultientitymanaged = 1;

	/**
	 * @var int  Does object support extrafields ? 0=No, 1=Yes
	 */
	public $isextrafieldmanaged = 0;

	/**
	 * @var string String with name of icon for inventory
	 */
	public $picto = 'inventory';

	const STATUS_DRAFT = 0;			// Draft
	const STATUS_VALIDATED = 1;		// Inventory is in process
	const STATUS_RECORDED = 2;		// Inventory is finisged. Stock movement has been recorded.
	const STATUS_CANCELED = 9;		// Canceled

	/**
	 *  'type' field format ('integer', 'integer:ObjectClass:PathToClass[:AddCreateButtonOrNot[:Filter]]', 'sellist:TableName:LabelFieldName[:KeyFieldName[:KeyFieldParent[:Filter]]]', 'varchar(x)', 'double(24,8)', 'real', 'price', 'text', 'text:none', 'html', 'date', 'datetime', 'timestamp', 'duration', 'mail', 'phone', 'url', 'password')
	 *         Note: Filter can be a string like "(t.ref:like:'SO-%') or (t.date_creation:<:'20160101') or (t.nature:is:NULL)"
	 *  'label' the translation key.
	 *  'picto' is code of a picto to show before value in forms
	 *  'enabled' is a condition when the field must be managed (Example: 1 or '$conf->global->MY_SETUP_PARAM)
	 *  'position' is the sort order of field.
	 *  'notnull' is set to 1 if not null in database. Set to -1 if we must set data to null if empty ('' or 0).
	 *  'visible' says if field is visible in list (Examples: 0=Not visible, 1=Visible on list and create/update/view forms, 2=Visible on list only, 3=Visible on create/update/view form only (not list), 4=Visible on list and update/view form only (not create). 5=Visible on list and view only (not create/not update). Using a negative value means field is not shown by default on list but can be selected for viewing)
	 *  'noteditable' says if field is not editable (1 or 0)
	 *  'default' is a default value for creation (can still be overwrote by the Setup of Default Values if field is editable in creation form). Note: If default is set to '(PROV)' and field is 'ref', the default value will be set to '(PROVid)' where id is rowid when a new record is created.
	 *  'index' if we want an index in database.
	 *  'foreignkey'=>'tablename.field' if the field is a foreign key (it is recommanded to name the field fk_...).
	 *  'searchall' is 1 if we want to search in this field when making a search from the quick search button.
	 *  'isameasure' must be set to 1 if you want to have a total on list for this field. Field type must be summable like integer or double(24,8).
	 *  'css' and 'cssview' and 'csslist' is the CSS style to use on field. 'css' is used in creation and update. 'cssview' is used in view mode. 'csslist' is used for columns in lists. For example: 'maxwidth200', 'wordbreak', 'tdoverflowmax200', 'minwidth300 maxwidth500 widthcentpercentminusx'
	 *  'help' is a 'TranslationString' to use to show a tooltip on field. You can also use 'TranslationString:keyfortooltiponlick' for a tooltip on click.
	 *  'showoncombobox' if value of the field must be visible into the label of the combobox that list record
	 *  'disabled' is 1 if we want to have the field locked by a 'disabled' attribute. In most cases, this is never set into the definition of $fields into class, but is set dynamically by some part of code.
	 *  'arrayofkeyval' to set a list of values if type is a list of predefined values. For example: array("0"=>"Draft","1"=>"Active","-1"=>"Cancel"). Note that type can be 'integer' or 'varchar'
	 *  'autofocusoncreate' to have field having the focus on a create form. Only 1 field should have this property set to 1.
	 *  'comment' is not used. You can store here any text of your choice. It is not used by application.
	 *
	 *  Note: To have value dynamic, you can set value to 0 in definition and edit the value on the fly into the constructor.
	 */

	// BEGIN MODULEBUILDER PROPERTIES
	/**
	 * @var array  Array with all fields and their property
	 */
	public $fields = array(
		'rowid' => array('type'=>'integer', 'label'=>'TechnicalID', 'visible'=>-1, 'enabled'=>1, 'position'=>1, 'notnull'=>1, 'index'=>1, 'comment'=>'Id',),
		'ref' => array('type'=>'varchar(64)', 'label'=>'Ref', 'visible'=>1, 'enabled'=>1, 'position'=>10, 'notnull'=>1, 'index'=>1, 'searchall'=>1, 'comment'=>'Reference of object', 'css'=>'maxwidth200'),
		'entity'         => array('type'=>'integer', 'label'=>'Entity', 'visible'=>0, 'enabled'=>1, 'position'=>20, 'notnull'=>1, 'index'=>1,),
		'title'          => array('type'=>'varchar(255)', 'label'=>'Label', 'visible'=>1, 'enabled'=>1, 'position'=>25, 'css'=>'minwidth300', 'csslist'=>'tdoverflowmax200'),
		'fk_warehouse'   => array('type'=>'integer:Entrepot:product/stock/class/entrepot.class.php', 'label'=>'Warehouse', 'visible'=>1, 'enabled'=>1, 'position'=>30, 'index'=>1, 'help'=>'InventoryForASpecificWarehouse', 'picto'=>'stock', 'css'=>'minwidth300 maxwidth500 widthcentpercentminusx', 'csslist'=>'tdoverflowmax200'),
		'fk_product'     => array('type'=>'integer:Product:product/class/product.class.php', 'label'=>'Product', 'visible'=>1, 'enabled'=>1, 'position'=>32, 'index'=>1, 'help'=>'InventoryForASpecificProduct', 'picto'=>'product', 'css'=>'minwidth300 maxwidth500 widthcentpercentminusx', 'csslist'=>'tdoverflowmax200'),
		'date_inventory' => array('type'=>'date', 'label'=>'DateValue', 'visible'=>1, 'enabled'=>'$conf->global->STOCK_INVENTORY_ADD_A_VALUE_DATE', 'position'=>35),	// This date is not used so disabled by default.
		'date_creation' => array('type'=>'datetime', 'label'=>'DateCreation', 'enabled'=>1, 'visible'=>-2, 'notnull'=>1, 'position'=>500),
		'tms'           => array('type'=>'timestamp', 'label'=>'DateModification', 'enabled'=>1, 'visible'=>-2, 'notnull'=>1, 'position'=>501),
		'date_validation' => array('type'=>'datetime', 'label'=>'DateValidation', 'visible'=>-2, 'enabled'=>1, 'position'=>502),
		'fk_user_creat' => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'UserAuthor', 'enabled'=>1, 'visible'=>-2, 'notnull'=>1, 'position'=>510, 'foreignkey'=>'user.rowid', 'csslist'=>'tdoverflowmax200'),
		'fk_user_modif' => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'UserModif', 'enabled'=>1, 'visible'=>-2, 'notnull'=>-1, 'position'=>511, 'csslist'=>'tdoverflowmax200'),
		'fk_user_valid' => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'UserValidation', 'visible'=>-2, 'enabled'=>1, 'position'=>512, 'csslist'=>'tdoverflowmax200'),
		'import_key'    => array('type'=>'varchar(14)', 'label'=>'ImportId', 'enabled'=>1, 'visible'=>-2, 'notnull'=>-1, 'index'=>0, 'position'=>1000),

		'status' => array('type'=>'integer', 'label'=>'Status', 'visible'=>4, 'enabled'=>1, 'position'=>1000, 'notnull'=>1, 'default'=>0, 'index'=>1, 'arrayofkeyval'=>array(0=>'Draft', 1=>'Validated', 2=>'Closed', 9=>'Canceled'))
	);

	/**
	 * @var int ID
	 */
	public $rowid;

	/**
	 * @var string Ref
	 */
	public $ref;

	/**
	 * @var int Entity
	 */
	public $entity;

	/**
	 * @var int ID
	 */
	public $fk_warehouse;

	/**
	 * @var int ID
	 */
	public $fk_product;

	public $date_inventory;
	public $title;

	/**
	 * @var int Status
	 */
	public $status;

	/**
	 * @var integer|string date_creation
	 */
	public $date_creation;

	/**
	 * @var integer|string date_validation
	 */
	public $date_validation;


	public $tms;

	/**
	 * @var int ID
	 */
	public $fk_user_creat;

	/**
	 * @var int ID
	 */
	public $fk_user_modif;

	/**
	 * @var int ID
	 */
	public $fk_user_valid;

	/**
	 * @var string import key
	 */
	public $import_key;
	// END MODULEBUILDER PROPERTIES



	// If this object has a subtable with lines

	/**
	 * @var string    Name of subtable line
	 */
	public $table_element_line = 'inventorydet';

	/**
	 * @var string    Field with ID of parent key if this field has a parent
	 */
	public $fk_element = 'fk_inventory';

	/**
	 * @var string    Name of subtable class that manage subtable lines
	 */
	public $class_element_line = 'Inventoryline';

	/**
	 * @var array	List of child tables. To test if we can delete object.
	 */
	protected $childtables = array();
	/**
	 * @var array	List of child tables. To know object to delete on cascade.
	 */
	protected $childtablesoncascade = array('inventorydet');

	/**
	 * @var InventoryLine[]     Array of subtable lines
	 */
	public $lines = array();



	/**
	 * Constructor
	 *
	 * @param DoliDb $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		global $conf;

		$this->db = $db;

		if (empty($conf->global->MAIN_SHOW_TECHNICAL_ID)) {
			$this->fields['rowid']['visible'] = 0;
		}
		if (empty($conf->multicompany->enabled)) {
			$this->fields['entity']['enabled'] = 0;
		}
	}


	/**
	 * Create object into database
	 *
	 * @param  User $user      User that creates
	 * @param  bool $notrigger false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, Id of created object if OK
	 */
	public function create(User $user, $notrigger = false)
	{
		$result = $this->createCommon($user, $notrigger);

		return $result;
	}

	/**
	 * Validate inventory (start it)
	 *
	 * @param  User $user      User that creates
	 * @param  bool $notrigger false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, Id of created object if OK
	 */
	public function validate(User $user, $notrigger = false)
	{
		global $conf;
		$this->db->begin();

		$result = 0;

		if ($this->status == self::STATUS_DRAFT) {
			// Delete inventory
			$sql = 'DELETE FROM '.MAIN_DB_PREFIX.'inventorydet WHERE fk_inventory = '.((int) $this->id);
			$resql = $this->db->query($sql);
			if (!$resql) {
				$this->error = $this->db->lasterror();
				$this->db->rollback();
				return -1;
			}

			// Scan existing stock to prefill the inventory
			$sql = 'SELECT ps.rowid, ps.fk_entrepot as fk_warehouse, ps.fk_product, ps.reel,';
			$sql .= ' pb.batch, pb.qty';
			$sql .= ' FROM '.MAIN_DB_PREFIX.'product_stock as ps';
			$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'product_batch as pb ON pb.fk_product_stock = ps.rowid,';
			$sql .= ' '.MAIN_DB_PREFIX.'product as p, '.MAIN_DB_PREFIX.'entrepot as e';
			$sql .= ' WHERE p.entity IN ('.getEntity('product').')';
			$sql .= ' AND ps.fk_product = p.rowid AND ps.fk_entrepot = e.rowid';
			if (empty($conf->global->STOCK_SUPPORTS_SERVICES)) {
				$sql .= " AND p.fk_product_type = 0";
			}
			if ($this->fk_product > 0) {
				$sql .= ' AND ps.fk_product = '.((int) $this->fk_product);
			}
			if ($this->fk_warehouse > 0) {
				$sql .= ' AND ps.fk_entrepot = '.((int) $this->fk_warehouse);
			}

			$inventoryline = new InventoryLine($this->db);

			$resql = $this->db->query($sql);
			if ($resql) {
				$num = $this->db->num_rows($resql);

				$i = 0;
				while ($i < $num) {
					$obj = $this->db->fetch_object($resql);

					$inventoryline->fk_inventory = $this->id;
					$inventoryline->fk_warehouse = $obj->fk_warehouse;
					$inventoryline->fk_product = $obj->fk_product;
					$inventoryline->batch = $obj->batch;
					$inventoryline->datec = dol_now();

					if ($conf->productbatch->enabled) {
						$inventoryline->qty_stock = ($obj->batch ? $obj->qty : $obj->reel); // If there is batch detail, we take qty for batch, else global qty
					} else {
						$inventoryline->qty_stock = $obj->reel;
					}

					$resultline = $inventoryline->create($user);
					if ($resultline <= 0) {
						$this->error = $inventoryline->error;
						$this->errors = $inventoryline->errors;
						$result = -1;
						break;
					}

					$i++;
				}
			} else {
				$result = -1;
				$this->error = $this->db->lasterror();
			}
		}

		if ($result >= 0) {
			$result = $this->setStatut($this::STATUS_VALIDATED, null, '', 'INVENTORY_VALIDATED');
		}

		if ($result > 0) {
			$this->db->commit();
		} else {
			$this->db->rollback();
		}
		return $result;
	}

	/**
	 * Go back to draft
	 *
	 * @param  User $user      User that creates
	 * @param  bool $notrigger false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, Id of created object if OK
	 */
	public function setDraft(User $user, $notrigger = false)
	{
		$this->db->begin();

		// Delete inventory
		$sql = 'DELETE FROM '.MAIN_DB_PREFIX.'inventorydet WHERE fk_inventory = '.((int) $this->id);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = $this->db->lasterror();
			$this->db->rollback();
			return -1;
		}

		$result = $this->setStatut($this::STATUS_DRAFT, null, '', 'INVENTORY_DRAFT');

		if ($result > 0) {
			$this->db->commit();
		} else {
			$this->db->rollback();
		}
		return $result;
	}

	/**
	 * Set to inventory to status "Closed". It means all stock movements were recorded.
	 *
	 * @param  User $user      User that creates
	 * @param  bool $notrigger false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, Id of created object if OK
	 */
	public function setRecorded(User $user, $notrigger = false)
	{
		$this->db->begin();

		$result = $this->setStatut($this::STATUS_RECORDED, null, '', 'INVENTORY_RECORDED');

		if ($result > 0) {
			$this->db->commit();
		} else {
			$this->db->rollback();
			return -1;
		}
		return $result;
	}

	/**
	 * Set to Canceled
	 *
	 * @param  User $user      User that creates
	 * @param  bool $notrigger false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, Id of created object if OK
	 */
	public function setCanceled(User $user, $notrigger = false)
	{
		$this->db->begin();

		$result = $this->setStatut($this::STATUS_CANCELED, null, '', 'INVENTORY_CANCELED');

		if ($result > 0) {
			$this->db->commit();
		} else {
			$this->db->rollback();
			return -1;
		}
		return $result;
	}

	/**
	 * Clone and object into another one
	 *
	 * @param  	User 	$user      	User that creates
	 * @param  	int 	$fromid     Id of object to clone
	 * @return 	mixed 				New object created, <0 if KO
	 */
	public function createFromClone(User $user, $fromid)
	{
		global $hookmanager, $langs;
		$error = 0;

		dol_syslog(__METHOD__, LOG_DEBUG);

		$object = new self($this->db);

		$this->db->begin();

		// Load source object
		$object->fetchCommon($fromid);
		// Reset some properties
		unset($object->id);
		unset($object->fk_user_creat);
		unset($object->import_key);

		// Clear fields
		$object->ref = "copy_of_".$object->ref;
		$object->title = $langs->trans("CopyOf")." ".$object->title;
		// ...

		// Create clone
		$object->context['createfromclone'] = 'createfromclone';
		$result = $object->createCommon($user);
		if ($result < 0) {
			$error++;
			$this->error = $object->error;
			$this->errors = $object->errors;
		}

		unset($object->context['createfromclone']);

		// End
		if (!$error) {
			$this->db->commit();
			return $object;
		} else {
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 * Load object in memory from the database
	 *
	 * @param int    $id   Id object
	 * @param string $ref  Ref
	 * @return int         <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id, $ref = null)
	{
		$result = $this->fetchCommon($id, $ref);
		//if ($result > 0 && ! empty($this->table_element_line)) $this->fetchLines();
		return $result;
	}

	/**
	 * Load object lines in memory from the database
	 *
	 * @return int         <0 if KO, 0 if not found, >0 if OK
	 */
	/*public function fetchLines()
	 {
	 $this->lines=array();

	 // Load lines with object MyObjectLine

	 return count($this->lines)?1:0;
	 }*/

	/**
	 * Update object into database
	 *
	 * @param  User $user      User that modifies
	 * @param  bool $notrigger false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, >0 if OK
	 */
	public function update(User $user, $notrigger = false)
	{
		return $this->updateCommon($user, $notrigger);
	}

	/**
	 * Delete object in database
	 *
	 * @param User $user       User that deletes
	 * @param bool $notrigger  false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, >0 if OK
	 */
	public function delete(User $user, $notrigger = false)
	{
		return $this->deleteCommon($user, $notrigger);
	}

	/**
	 *  Delete a line of object in database
	 *
	 *	@param  User	$user       User that delete
	 *  @param	int		$idline		Id of line to delete
	 *  @param 	bool 	$notrigger  false=launch triggers after, true=disable triggers
	 *  @return int         		>0 if OK, <0 if KO
	 */
	public function deleteLine(User $user, $idline, $notrigger = false)
	{
		if ($this->status < 0) {
			$this->error = 'ErrorDeleteLineNotAllowedByObjectStatus';
			return -2;
		}

		return $this->deleteLineCommon($user, $idline, $notrigger);
	}

	/**
	 *  Return a link to the object card (with optionaly the picto)
	 *
	 *	@param	int		$withpicto					Include picto in link (0=No picto, 1=Include picto into link, 2=Only picto)
	 *	@param	string	$option						On what the link point to
	 *  @param	int  	$notooltip					1=Disable tooltip
	 *  @param  string  $morecss            		Add more css on link
	 *  @param  int     $save_lastsearch_value    	-1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 *	@return	string								String with URL
	 */
	public function getNomUrl($withpicto = 0, $option = '', $notooltip = 0, $morecss = '', $save_lastsearch_value = -1)
	{
		global $db, $conf, $langs;
		global $dolibarr_main_authentication, $dolibarr_main_demo;
		global $menumanager;

		if (!empty($conf->dol_no_mouse_hover)) {
			$notooltip = 1; // Force disable tooltips
		}

		$result = '';
		$companylink = '';

		$label = '<u>'.$langs->trans("Inventory").'</u>';
		$label .= '<br>';
		$label .= '<b>'.$langs->trans('Ref').':</b> '.$this->ref;

		$url = dol_buildpath('/product/inventory/card.php', 1).'?id='.$this->id;

		$linkclose = '';
		if (empty($notooltip)) {
			if (!empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) {
				$label = $langs->trans("ShowInventory");
				$linkclose .= ' alt="'.dol_escape_htmltag($label, 1).'"';
			}
			$linkclose .= ' title="'.dol_escape_htmltag($label, 1).'"';
			$linkclose .= ' class="classfortooltip'.($morecss ? ' '.$morecss : '').'"';
		} else {
			$linkclose = ($morecss ? ' class="'.$morecss.'"' : '');
		}

		$linkstart = '<a href="'.$url.'"';
		$linkstart .= $linkclose.'>';
		$linkend = '</a>';

		$result .= $linkstart;
		if ($withpicto) {
			$result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
		}
		if ($withpicto != 2) {
			$result .= $this->ref;
		}
		$result .= $linkend;
		//if ($withpicto != 2) $result.=(($addlabel && $this->label) ? $sep . dol_trunc($this->label, ($addlabel > 1 ? $addlabel : 0)) : '');

		return $result;
	}

	/**
	 *  Retourne le libelle du status d'un user (actif, inactif)
	 *
	 *  @param	int		$mode          0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
	 *  @return	string 			       Label of status
	 */
	public function getLibStatut($mode = 0)
	{
		return $this->LibStatut($this->status, $mode);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return the status
	 *
	 *  @param	int		$status        	Id status
	 *  @param  int		$mode          	0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 5=Long label + Picto, 6=Long label + Picto
	 *  @return string 			       	Label of status
	 */
	public static function LibStatut($status, $mode = 0)
	{
		// phpcs:enable
		global $langs;

		$labelStatus = array();
		$labelStatus[self::STATUS_DRAFT] = $langs->transnoentitiesnoconv('Draft');
		$labelStatus[self::STATUS_VALIDATED] = $langs->transnoentitiesnoconv('Validated').' ('.$langs->transnoentitiesnoconv('InventoryStartedShort').')';
		$labelStatus[self::STATUS_CANCELED] = $langs->transnoentitiesnoconv('Canceled');
		$labelStatus[self::STATUS_RECORDED] = $langs->transnoentitiesnoconv('Closed');
		$labelStatusShort[self::STATUS_DRAFT] = $langs->transnoentitiesnoconv('Draft');
		$labelStatusShort[self::STATUS_VALIDATED] = $langs->transnoentitiesnoconv('InventoryStartedShort');
		$labelStatusShort[self::STATUS_CANCELED] = $langs->transnoentitiesnoconv('Canceled');
		$labelStatusShort[self::STATUS_RECORDED] = $langs->transnoentitiesnoconv('Closed');

		$statusType = 'status'.$status;
		if ($status == self::STATUS_RECORDED) {
			$statusType = 'status6';
		}

		return dolGetStatus($labelStatus[$status], $labelStatusShort[$status], '', $statusType, $mode);
	}

	/**
	 *	Charge les informations d'ordre info dans l'objet commande
	 *
	 *	@param  int		$id       Id of order
	 *	@return	void
	 */
	public function info($id)
	{
		$sql = 'SELECT rowid, date_creation as datec, tms as datem, date_validation as datev,';
		$sql .= ' fk_user_creat, fk_user_modif, fk_user_valid';
		$sql .= ' FROM '.MAIN_DB_PREFIX.$this->table_element.' as t';
		$sql .= ' WHERE t.rowid = '.((int) $id);
		$result = $this->db->query($sql);
		if ($result) {
			if ($this->db->num_rows($result)) {
				$obj = $this->db->fetch_object($result);

				$this->id = $obj->rowid;

				if ($obj->fk_user_creat > 0) {
					$cuser = new User($this->db);
					$cuser->fetch($obj->fk_user_creat);
					$this->user_creation = $cuser;
				}

				if ($obj->fk_user_modif > 0) {
					$muser = new User($this->db);
					$muser->fetch($obj->fk_user_modif);
					$this->user_creation = $muser;
				}

				if ($obj->fk_user_valid > 0) {
					$vuser = new User($this->db);
					$vuser->fetch($obj->fk_user_valid);
					$this->user_validation = $vuser;
				}

				$this->date_creation     = $this->db->jdate($obj->datec);
				$this->date_modification = $this->db->jdate($obj->datem);
				$this->date_validation   = $this->db->jdate($obj->datev);
			}

			$this->db->free($result);
		} else {
			dol_print_error($this->db);
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
		$this->initAsSpecimenCommon();
		$this->title = '';
	}
}

/**
 * Class InventoryLine
 */
class InventoryLine extends CommonObjectLine
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'inventoryline';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'inventorydet';

	/**
	 * @var array  Does inventory support multicompany module ? 0=No test on entity, 1=Test with field entity, 2=Test with link by societe
	 */
	public $ismultientitymanaged = 0;

	/**
	 * @var int  Does object support extrafields ? 0=No, 1=Yes
	 */
	public $isextrafieldmanaged = 0;

	/**
	 * @var string String with name of icon for inventory
	 */
	public $picto = 'stock';

	/**
	 *  'type' if the field format.
	 *  'label' the translation key.
	 *  'enabled' is a condition when the field must be managed.
	 *  'visible' says if field is visible in list (Examples: 0=Not visible, 1=Visible on list and create/update/view forms, 2=Visible on list only. Using a negative value means field is not shown by default on list but can be selected for viewing)
	 *  'notnull' is set to 1 if not null in database. Set to -1 if we must set data to null if empty ('' or 0).
	 *  'index' if we want an index in database.
	 *  'foreignkey'=>'tablename.field' if the field is a foreign key (it is recommanded to name the field fk_...).
	 *  'position' is the sort order of field.
	 *  'searchall' is 1 if we want to search in this field when making a search from the quick search button.
	 *  'isameasure' must be set to 1 if you want to have a total on list for this field. Field type must be summable like integer or double(24,8).
	 *  'help' is a string visible as a tooltip on field
	 *  'comment' is not used. You can store here any text of your choice. It is not used by application.
	 *  'default' is a default value for creation (can still be replaced by the global setup of default values)
	 *  'showoncombobox' if field must be shown into the label of combobox
	 */

	// BEGIN MODULEBUILDER PROPERTIES
	/**
	 * @var array  Array with all fields and their property
	 */
	public $fields = array(
		'rowid' => array('type'=>'integer', 'label'=>'TechnicalID', 'visible'=>-1, 'enabled'=>1, 'position'=>1, 'notnull'=>1, 'index'=>1, 'comment'=>'Id',),
		'fk_inventory'  => array('type'=>'integer:Inventory:product/inventory/class/inventory.class.php', 'label'=>'Inventory', 'visible'=>1, 'enabled'=>1, 'position'=>30, 'index'=>1, 'help'=>'LinkToInventory'),
		'fk_warehouse'  => array('type'=>'integer:Entrepot:product/stock/class/entrepot.class.php', 'label'=>'Warehouse', 'visible'=>1, 'enabled'=>1, 'position'=>30, 'index'=>1, 'help'=>'LinkToThirparty'),
		'fk_product'    => array('type'=>'integer:Product:product/class/product.class.php', 'label'=>'Product', 'visible'=>1, 'enabled'=>1, 'position'=>32, 'index'=>1, 'help'=>'LinkToProduct'),
		'batch'         => array('type'=>'string', 'label'=>'Batch', 'visible'=>1, 'enabled'=>1, 'position'=>32, 'index'=>1, 'help'=>'LinkToProduct'),
		'datec'         => array('type'=>'datetime', 'label'=>'DateCreation', 'enabled'=>1, 'visible'=>-2, 'notnull'=>1, 'position'=>500),
		'tms'           => array('type'=>'timestamp', 'label'=>'DateModification', 'enabled'=>1, 'visible'=>-2, 'notnull'=>1, 'position'=>501),
		'qty_stock'     => array('type'=>'double', 'label'=>'QtyFound', 'visible'=>1, 'enabled'=>1, 'position'=>32, 'index'=>1, 'help'=>'Qty we found/want (to define during draft edition)'),
		'qty_view'      => array('type'=>'double', 'label'=>'QtyBefore', 'visible'=>1, 'enabled'=>1, 'position'=>33, 'index'=>1, 'help'=>'Qty before (filled once movements are validated)'),
		'qty_regulated' => array('type'=>'double', 'label'=>'QtyDelta', 'visible'=>1, 'enabled'=>1, 'position'=>34, 'index'=>1, 'help'=>'Qty aadded or removed (filled once movements are validated)'),
	);

	/**
	 * @var int ID
	 */
	public $rowid;

	public $fk_inventory;
	public $fk_warehouse;
	public $fk_product;
	public $batch;
	public $datec;
	public $tms;
	public $qty_stock;
	public $qty_view;
	public $qty_regulated;


	/**
	 * Create object in database
	 *
	 * @param User $user       User that creates
	 * @param bool $notrigger  false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, >0 if OK
	 */
	public function create(User $user, $notrigger = false)
	{
		return $this->createCommon($user, $notrigger);
	}

	/**
	 * Load object in memory from the database
	 *
	 * @param int    $id   Id object
	 * @param string $ref  Ref
	 * @return int         <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id, $ref = null)
	{
		$result = $this->fetchCommon($id, $ref);
		//if ($result > 0 && ! empty($this->table_element_line)) $this->fetchLines();
		return $result;
	}

	/**
	 * Update object into database
	 *
	 * @param  User $user      User that modifies
	 * @param  bool $notrigger false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, >0 if OK
	 */
	public function update(User $user, $notrigger = false)
	{
		return $this->updateCommon($user, $notrigger);
	}

	/**
	 * Delete object in database
	 *
	 * @param User $user       User that deletes
	 * @param bool $notrigger  false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, >0 if OK
	 */
	public function delete(User $user, $notrigger = false)
	{
		return $this->deleteCommon($user, $notrigger);
		//return $this->deleteCommon($user, $notrigger, 1);
	}
}
