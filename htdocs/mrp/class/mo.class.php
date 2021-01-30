<?php
/* Copyright (C) 2017  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2020  Lenin Rivas		   <lenin@leninrivas.com>
 * Copyright (C) ---Put here your own copyright and developer email---
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
 * \file        class/mo.class.php
 * \ingroup     mrp
 * \brief       This file is a CRUD class file for Mo (Create/Read/Update/Delete)
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
//require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
//require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

/**
 * Class for Mo
 */
class Mo extends CommonObject
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'mo';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'mrp_mo';

	/**
	 * @var int  Does mo support multicompany module ? 0=No test on entity, 1=Test with field entity, 2=Test with link by societe
	 */
	public $ismultientitymanaged = 0;

	/**
	 * @var int  Does mo support extrafields ? 0=No, 1=Yes
	 */
	public $isextrafieldmanaged = 1;

	/**
	 * @var string String with name of icon for mo. Must be the part after the 'object_' into object_mo.png
	 */
	public $picto = 'mrp';


	const STATUS_DRAFT = 0;
	const STATUS_VALIDATED = 1; // To produce
	const STATUS_INPROGRESS = 2;
	const STATUS_PRODUCED = 3;
	const STATUS_CANCELED = 9;



	/**
	 *  'type' if the field format ('integer', 'integer:ObjectClass:PathToClass[:AddCreateButtonOrNot[:Filter]]', 'varchar(x)', 'double(24,8)', 'real', 'price', 'text', 'html', 'date', 'datetime', 'timestamp', 'duration', 'mail', 'phone', 'url', 'password')
	 *         Note: Filter can be a string like "(t.ref:like:'SO-%') or (t.date_creation:<:'20160101') or (t.nature:is:NULL)"
	 *  'label' the translation key.
	 *  'enabled' is a condition when the field must be managed.
	 *  'visible' says if field is visible in list (Examples: 0=Not visible, 1=Visible on list and create/update/view forms, 2=Visible on list only, 3=Visible on create/update/view form only (not list), 4=Visible on list and update/view form only (not create). Using a negative value means field is not shown by default on list but can be selected for viewing)
	 *  'noteditable' says if field is not editable (1 or 0)
	 *  'notnull' is set to 1 if not null in database. Set to -1 if we must set data to null if empty ('' or 0).
	 *  'default' is a default value for creation (can still be replaced by the global setup of default values)
	 *  'index' if we want an index in database.
	 *  'foreignkey'=>'tablename.field' if the field is a foreign key (it is recommanded to name the field fk_...).
	 *  'position' is the sort order of field.
	 *  'searchall' is 1 if we want to search in this field when making a search from the quick search button.
	 *  'isameasure' must be set to 1 if you want to have a total on list for this field. Field type must be summable like integer or double(24,8).
	 *  'css' is the CSS style to use on field. For example: 'maxwidth200'
	 *  'help' is a string visible as a tooltip on field
	 *  'comment' is not used. You can store here any text of your choice. It is not used by application.
	 *  'showoncombobox' if value of the field must be visible into the label of the combobox that list record
	 *  'arraykeyval' to set list of value if type is a list of predefined values. For example: array("0"=>"Draft","1"=>"Active","-1"=>"Cancel")
	 */

	// BEGIN MODULEBUILDER PROPERTIES
	/**
	 * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields = array(
		'rowid' => array('type'=>'integer', 'label'=>'TechnicalID', 'enabled'=>1, 'visible'=>-1, 'position'=>1, 'notnull'=>1, 'index'=>1, 'comment'=>"Id",),
		'entity' => array('type'=>'integer', 'label'=>'Entity', 'enabled'=>1, 'visible'=>0, 'position'=>5, 'notnull'=>1, 'default'=>'1', 'index'=>1),
		'ref' => array('type'=>'varchar(128)', 'label'=>'Ref', 'enabled'=>1, 'visible'=>4, 'position'=>10, 'notnull'=>1, 'default'=>'(PROV)', 'index'=>1, 'searchall'=>1, 'comment'=>"Reference of object", 'showoncombobox'=>'1', 'noteditable'=>1),
		'fk_bom' => array('type'=>'integer:Bom:bom/class/bom.class.php:0:t.status=1', 'filter'=>'active=1', 'label'=>'BOM', 'enabled'=>1, 'visible'=>1, 'position'=>33, 'notnull'=>-1, 'index'=>1, 'comment'=>"Original BOM", 'css'=>'maxwidth300'),
		'fk_product' => array('type'=>'integer:Product:product/class/product.class.php:0', 'label'=>'Product', 'enabled'=>1, 'visible'=>1, 'position'=>35, 'notnull'=>1, 'index'=>1, 'comment'=>"Product to produce", 'css'=>'maxwidth300', 'picto'=>'product'),
		'qty' => array('type'=>'real', 'label'=>'QtyToProduce', 'enabled'=>1, 'visible'=>1, 'position'=>40, 'notnull'=>1, 'comment'=>"Qty to produce", 'css'=>'width75', 'default'=>1, 'isameasure'=>1),
		'label' => array('type'=>'varchar(255)', 'label'=>'Label', 'enabled'=>1, 'visible'=>1, 'position'=>42, 'notnull'=>-1, 'searchall'=>1, 'showoncombobox'=>'1',),
		'fk_soc' => array('type'=>'integer:Societe:societe/class/societe.class.php:1', 'label'=>'ThirdParty', 'picto'=>'company', 'enabled'=>1, 'visible'=>-1, 'position'=>50, 'notnull'=>-1, 'index'=>1, 'css'=>'maxwidth400'),
		'fk_project' => array('type'=>'integer:Project:projet/class/project.class.php:1:fk_statut=1', 'label'=>'Project', 'picto'=>'project', 'enabled'=>1, 'visible'=>-1, 'position'=>51, 'notnull'=>-1, 'index'=>1, 'css'=>'minwidth200 maxwidth400'),
		'fk_warehouse' => array('type'=>'integer:Entrepot:product/stock/class/entrepot.class.php:0', 'label'=>'WarehouseForProduction', 'picto'=>'stock', 'enabled'=>1, 'visible'=>1, 'position'=>52, 'css'=>'maxwidth400'),
		'note_public' => array('type'=>'html', 'label'=>'NotePublic', 'enabled'=>1, 'visible'=>0, 'position'=>61, 'notnull'=>-1,),
		'note_private' => array('type'=>'html', 'label'=>'NotePrivate', 'enabled'=>1, 'visible'=>0, 'position'=>62, 'notnull'=>-1,),
		'date_creation' => array('type'=>'datetime', 'label'=>'DateCreation', 'enabled'=>1, 'visible'=>-2, 'position'=>500, 'notnull'=>1,),
		'tms' => array('type'=>'timestamp', 'label'=>'DateModification', 'enabled'=>1, 'visible'=>-2, 'position'=>501, 'notnull'=>1,),
		'date_valid' => array('type'=>'datetime', 'label'=>'DateValidation', 'enabled'=>1, 'visible'=>-2, 'position'=>502,),
		'fk_user_creat' => array('type'=>'integer', 'label'=>'UserAuthor', 'enabled'=>1, 'visible'=>-2, 'position'=>510, 'notnull'=>1, 'foreignkey'=>'user.rowid',),
		'fk_user_modif' => array('type'=>'integer', 'label'=>'UserModif', 'enabled'=>1, 'visible'=>-2, 'position'=>511, 'notnull'=>-1,),
		'date_start_planned' => array('type'=>'datetime', 'label'=>'DateStartPlannedMo', 'enabled'=>1, 'visible'=>1, 'position'=>55, 'notnull'=>-1, 'index'=>1, 'help'=>'KeepEmptyForAsap'),
		'date_end_planned' => array('type'=>'datetime', 'label'=>'DateEndPlannedMo', 'enabled'=>1, 'visible'=>1, 'position'=>56, 'notnull'=>-1, 'index'=>1,),
		'import_key' => array('type'=>'varchar(14)', 'label'=>'ImportId', 'enabled'=>1, 'visible'=>-2, 'position'=>1000, 'notnull'=>-1,),
		'model_pdf' =>array('type'=>'varchar(255)', 'label'=>'Model pdf', 'enabled'=>1, 'visible'=>0, 'position'=>1010),
		'status' => array('type'=>'integer', 'label'=>'Status', 'enabled'=>1, 'visible'=>2, 'position'=>1000, 'default'=>0, 'notnull'=>1, 'index'=>1, 'arrayofkeyval'=>array('0'=>'Brouillon', '1'=>'Validated', '2'=>'InProgress', '3'=>'StatusMOProduced', '9'=>'Canceled')),
	);
	public $rowid;
	public $ref;
	public $entity;
	public $label;
	public $qty;
	public $fk_warehouse;
	public $fk_soc;

	/**
	 * @var string public note
	 */
	public $note_public;

	/**
	 * @var string private note
	 */
	public $note_private;

	/**
	 * @var integer|string date_creation
	 */
	public $date_creation;


	public $tms;
	public $fk_user_creat;
	public $fk_user_modif;
	public $import_key;
	public $status;
	public $fk_product;

	/**
	 * @var integer|string date_start_planned
	 */
	public $date_start_planned;

	/**
	 * @var integer|string date_end_planned
	 */
	public $date_end_planned;


	public $fk_bom;
	public $fk_project;
	// END MODULEBUILDER PROPERTIES


	// If this object has a subtable with lines

	/**
	 * @var string    Name of subtable line
	 */
	public $table_element_line = 'mo_production';

	/**
	 * @var string    Field with ID of parent key if this field has a parent
	 */
	public $fk_element = 'fk_mo';

	/**
	 * @var string    Name of subtable class that manage subtable lines
	 */
	public $class_element_line = 'MoLine';

	/**
	 * @var array	List of child tables. To test if we can delete object.
	 */
	protected $childtables = array();

	/**
	 * @var array	List of child tables. To know object to delete on cascade.
	 */
	protected $childtablesoncascade = array('mrp_production');

	/**
	 * @var MoLine[]     Array of subtable lines
	 */
	public $lines = array();



	/**
	 * Constructor
	 *
	 * @param DoliDb $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		global $conf, $langs;

		$this->db = $db;

		if (empty($conf->global->MAIN_SHOW_TECHNICAL_ID) && isset($this->fields['rowid'])) $this->fields['rowid']['visible'] = 0;
		if (empty($conf->multicompany->enabled) && isset($this->fields['entity'])) $this->fields['entity']['enabled'] = 0;

		// Unset fields that are disabled
		foreach ($this->fields as $key => $val)
		{
			if (isset($val['enabled']) && empty($val['enabled']))
			{
				unset($this->fields[$key]);
			}
		}

		// Translate some data of arrayofkeyval
		foreach ($this->fields as $key => $val)
		{
			if (!empty($val['arrayofkeyval']) && is_array($val['arrayofkeyval']))
			{
				foreach ($val['arrayofkeyval'] as $key2 => $val2)
				{
					$this->fields[$key]['arrayofkeyval'][$key2] = $langs->trans($val2);
				}
			}
		}
	}

	/**
	 * Create object into database
	 *
	 * @param  User $user      User that creates
	 * @param  bool $notrigger false=launch triggers after, true=disable triggers
	 * @return int             <=0 if KO, Id of created object if OK
	 */
	public function create(User $user, $notrigger = false)
	{
		global $conf;

		$error = 0;
		$idcreated = 0;

		$this->db->begin();

		if ($this->fk_product > 0) {
			include_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
			$tmpproduct = new Product($this->db);
			$tmpproduct->fetch($this->fk_product);
			if ($tmpproduct->hasFatherOrChild(1) > 0) {
				$this->error = 'ErrorAVirtualProductCantBeUsedIntoABomOrMo';
				$this->errors[] = $this->error;
				$this->db->rollback();
				return -1;
			}
		}

		// Check that product is not a kit/virtual product
		if (!$error) {
			$idcreated = $this->createCommon($user, $notrigger);
			if ($idcreated <= 0) {
				$error++;
			}
		}

		if (!$error) {
			$result = $this->updateProduction($user, $notrigger);
			if ($result <= 0) {
				$error++;
			}
		}

		if (!$error) {
			$this->db->commit();
		} else {
			$this->db->rollback();
		}

		return $idcreated;
	}

	/**
	 * Clone an object into another one
	 *
	 * @param  	User 	$user      	User that creates
	 * @param  	int 	$fromid     Id of object to clone
	 * @return 	mixed 				New object created, <0 if KO
	 */
	public function createFromClone(User $user, $fromid)
	{
		global $langs, $extrafields;
		$error = 0;

		dol_syslog(__METHOD__, LOG_DEBUG);

		$object = new self($this->db);

		$this->db->begin();

		// Load source object
		$result = $object->fetchCommon($fromid);
		if ($result > 0 && !empty($object->table_element_line)) $object->fetchLines();

		// get lines so they will be clone
		//foreach($this->lines as $line)
		//	$line->fetch_optionals();

		// Reset some properties
		unset($object->id);
		unset($object->fk_user_creat);
		unset($object->import_key);

		// Clear fields
		$object->ref = empty($this->fields['ref']['default']) ? "copy_of_".$object->ref : $this->fields['ref']['default'];
		$object->label = empty($this->fields['label']['default']) ? $langs->trans("CopyOf")." ".$object->label : $this->fields['label']['default'];
		$object->status = self::STATUS_DRAFT;
		// ...
		// Clear extrafields that are unique
		if (is_array($object->array_options) && count($object->array_options) > 0)
		{
			$extrafields->fetch_name_optionals_label($this->table_element);
			foreach ($object->array_options as $key => $option)
			{
				$shortkey = preg_replace('/options_/', '', $key);
				if (!empty($extrafields->attributes[$this->element]['unique'][$shortkey]))
				{
					//var_dump($key); var_dump($clonedObj->array_options[$key]); exit;
					unset($object->array_options[$key]);
				}
			}
		}

		// Create clone
		$object->context['createfromclone'] = 'createfromclone';
		$result = $object->createCommon($user);
		if ($result < 0) {
			$error++;
			$this->error = $object->error;
			$this->errors = $object->errors;
		}

		if (!$error)
		{
			// copy internal contacts
			if ($this->copy_linked_contact($object, 'internal') < 0)
			{
				$error++;
			}
		}

		if (!$error)
		{
			// copy external contacts if same company
			if (property_exists($this, 'socid') && $this->socid == $object->socid)
			{
				if ($this->copy_linked_contact($object, 'external') < 0)
					$error++;
			}
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
		if ($result > 0 && !empty($this->table_element_line)) $this->fetchLines();
		return $result;
	}

	/**
	 * Load object lines in memory from the database
	 *
	 * @return int         <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetchLines()
	{
		$this->lines = array();

		$result = $this->fetchLinesCommon();
		return $result;
	}


	/**
	 * Load list of objects in memory from the database.
	 *
	 * @param  string      $sortorder    Sort Order
	 * @param  string      $sortfield    Sort field
	 * @param  int         $limit        limit
	 * @param  int         $offset       Offset
	 * @param  array       $filter       Filter array. Example array('field'=>'valueforlike', 'customurl'=>...)
	 * @param  string      $filtermode   Filter mode (AND or OR)
	 * @return array|int                 int <0 if KO, array of pages if OK
	 */
	public function fetchAll($sortorder = '', $sortfield = '', $limit = 0, $offset = 0, array $filter = array(), $filtermode = 'AND')
	{
		global $conf;

		dol_syslog(__METHOD__, LOG_DEBUG);

		$records = array();

		$sql = 'SELECT ';
		$sql .= $this->getFieldList();
		$sql .= ' FROM '.MAIN_DB_PREFIX.$this->table_element.' as t';
		if (isset($this->ismultientitymanaged) && $this->ismultientitymanaged == 1) $sql .= ' WHERE t.entity IN ('.getEntity($this->table_element).')';
		else $sql .= ' WHERE 1 = 1';
		// Manage filter
		$sqlwhere = array();
		if (count($filter) > 0) {
			foreach ($filter as $key => $value) {
				if ($key == 't.rowid') {
					$sqlwhere[] = $key.'='.$value;
				} elseif (strpos($key, 'date') !== false) {
					$sqlwhere[] = $key.' = \''.$this->db->idate($value).'\'';
				} elseif ($key == 'customsql') {
					$sqlwhere[] = $value;
				} else {
					$sqlwhere[] = $key.' LIKE \'%'.$this->db->escape($value).'%\'';
				}
			}
		}
		if (count($sqlwhere) > 0) {
			$sql .= ' AND ('.implode(' '.$filtermode.' ', $sqlwhere).')';
		}

		if (!empty($sortfield)) {
			$sql .= $this->db->order($sortfield, $sortorder);
		}
		if (!empty($limit)) {
			$sql .= ' '.$this->db->plimit($limit, $offset);
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < min($limit, $num))
			{
				$obj = $this->db->fetch_object($resql);

				$record = new self($this->db);
				$record->setVarsFromFetchObj($obj);

				$records[$record->id] = $record;

				$i++;
			}
			$this->db->free($resql);

			return $records;
		} else {
			$this->errors[] = 'Error '.$this->db->lasterror();
			dol_syslog(__METHOD__.' '.join(',', $this->errors), LOG_ERR);

			return -1;
		}
	}

	/**
	 * Get list of lines linked to current line for a defined role.
	 *
	 * @param  	string 	$role      	Get lines linked to current line with the selected role ('consumed', 'produced', ...)
	 * @param	int		$lineid		Id of production line to filter childs
	 * @return 	array             	Array of lines
	 */
	public function fetchLinesLinked($role, $lineid = 0)
	{
		$resarray = array();
		$mostatic = new MoLine($this->db);

		$sql = 'SELECT ';
		$sql .= $mostatic->getFieldList();
		$sql .= ' FROM '.MAIN_DB_PREFIX.$mostatic->table_element.' as t';
		$sql .= " WHERE t.role = '".$this->db->escape($role)."'";
		if ($lineid > 0) $sql .= ' AND t.fk_mrp_production = '.$lineid;
		else $sql .= 'AND t.fk_mo = '.$this->id;

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);

			$i = 0;
			while ($i < $num) {
				$obj = $this->db->fetch_object($resql);
				if ($obj) {
					$resarray[] = array(
						'rowid'=> $obj->rowid,
						'date'=> $this->db->jdate($obj->date_creation),
						'qty' => $obj->qty,
						'role' => $obj->role,
						'fk_product' => $obj->fk_product,
						'fk_warehouse' => $obj->fk_warehouse,
						'batch' => $obj->batch,
						'fk_stock_movement' => $obj->fk_stock_movement
					);
				}

				$i++;
			}

			return $resarray;
		} else {
			$this->error = $this->db->lasterror();
			return array();
		}
	}


	/**
	 * Count number of movement with origin of MO
	 *
	 * @return 	int			Number of movements
	 */
	public function countMovements()
	{
		$result = 0;

		$sql = 'SELECT COUNT(rowid) as nb FROM '.MAIN_DB_PREFIX.'stock_mouvement as sm';
		$sql .= " WHERE sm.origintype = 'mo' and sm.fk_origin = ".$this->id;

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);

			$i = 0;
			while ($i < $num) {
				$obj = $this->db->fetch_object($resql);
				if ($obj) {
					$result = $obj->nb;
				}

				$i++;
			}
		} else {
			$this->error = $this->db->lasterror();
		}

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
		global $langs;

		$error = 0;

		$this->db->begin();

		$result = $this->updateCommon($user, $notrigger);
		if ($result <= 0) {
			$error++;
		}

		$result = $this->updateProduction($user, $notrigger);
		if ($result <= 0) {
			$error++;
		}

		if (!$error) {
			setEventMessages($langs->trans("RecordModifiedSuccessfully"), null, 'mesgs');
			$this->db->commit();
			return 1;
		} else {
			setEventMessages($this->error, $this->errors, 'errors');
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 * Erase and update the line to consume and to produce.
	 *
	 * @param  User $user      User that modifies
	 * @param  bool $notrigger false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, >0 if OK
	 */
	public function updateProduction(User $user, $notrigger = true)
	{
		$error = 0;

		if ($this->status != self::STATUS_DRAFT) {
			$this->error = 'BadStatus';
			return -1;
		}

		$this->db->begin();

		// Insert lines in mrp_production table from BOM data
		if (!$error)
		{
			// TODO Check that production has not started. If yes, we stop here.

			$sql = 'DELETE FROM '.MAIN_DB_PREFIX.'mrp_production WHERE fk_mo = '.$this->id;
			$this->db->query($sql);

			$moline = new MoLine($this->db);

			// Line to produce
			$moline->fk_mo = $this->id;
			$moline->qty = $this->qty;
			$moline->fk_product = $this->fk_product;
			$moline->role = 'toproduce';
			$moline->position = 1;

			$resultline = $moline->create($user, false); // Never use triggers here
			if ($resultline <= 0) {
				$error++;
				$this->error = $moline->error;
				$this->errors = $moline->errors;
				dol_print_error($this->db, $moline->error, $moline->errors);
			}

			if ($this->fk_bom > 0) {	// If a BOM is defined, we know what to consume.
				include_once DOL_DOCUMENT_ROOT.'/bom/class/bom.class.php';
				$bom = new Bom($this->db);
				$bom->fetch($this->fk_bom);
				if ($bom->id > 0)
				{
					// Lines to consume
					if (!$error) {
						foreach ($bom->lines as $line)
						{
							$moline = new MoLine($this->db);

							$moline->fk_mo = $this->id;
							if ($line->qty_frozen) {
								$moline->qty = $line->qty; // Qty to consume does not depends on quantity to produce
							} else {
								$moline->qty = price2num(($line->qty / $bom->qty) * $this->qty / $line->efficiency, 'MS'); // Calculate with Qty to produce and  more presition
							}
							if ($moline->qty <= 0) {
								$error++;
								$this->error = "BadValueForquantityToConsume";
								break;
							} else {
								$moline->fk_product = $line->fk_product;
								$moline->role = 'toconsume';
								$moline->position = $line->position;
								$moline->qty_frozen = $line->qty_frozen;
								$moline->disable_stock_change = $line->disable_stock_change;

								$resultline = $moline->create($user, false); // Never use triggers here
								if ($resultline <= 0) {
									$error++;
									$this->error = $moline->error;
									$this->errors = $moline->errors;
									dol_print_error($this->db, $moline->error, $moline->errors);
									break;
								}
							}
						}
					}
				}
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
		if ($this->status < 0)
		{
			$this->error = 'ErrorDeleteLineNotAllowedByObjectStatus';
			return -2;
		}

		return $this->deleteLineCommon($user, $idline, $notrigger);
	}


	/**
	 *  Returns the reference to the following non used MO depending on the active numbering module
	 *  defined into MRP_MO_ADDON
	 *
	 *  @param	Product		$prod 	Object product
	 *  @return string      		MO free reference
	 */
	public function getNextNumRef($prod)
	{
		global $langs, $conf;
		$langs->load("mrp");

		if (!empty($conf->global->MRP_MO_ADDON))
		{
			$mybool = false;

			$file = $conf->global->MRP_MO_ADDON.".php";
			$classname = $conf->global->MRP_MO_ADDON;

			// Include file with class
			$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
			foreach ($dirmodels as $reldir)
			{
				$dir = dol_buildpath($reldir."core/modules/mrp/");

				// Load file with numbering class (if found)
				$mybool |= @include_once $dir.$file;
			}

			if ($mybool === false)
			{
				dol_print_error('', "Failed to include file ".$file);
				return '';
			}

			$obj = new $classname();
			$numref = $obj->getNextValue($prod, $this);

			if ($numref != "")
			{
				return $numref;
			} else {
				$this->error = $obj->error;
				//dol_print_error($this->db,get_class($this)."::getNextNumRef ".$obj->error);
				return "";
			}
		} else {
			print $langs->trans("Error")." ".$langs->trans("Error_MRP_MO_ADDON_NotDefined");
			return "";
		}
	}

	/**
	 *	Validate Mo
	 *
	 *	@param		User	$user     		User making status change
	 *  @param		int		$notrigger		1=Does not execute triggers, 0= execute triggers
	 *	@return  	int						<=0 if OK, 0=Nothing done, >0 if KO
	 */
	public function validate($user, $notrigger = 0)
	{
		global $conf, $langs;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$error = 0;

		// Protection
		if ($this->status == self::STATUS_VALIDATED)
		{
			dol_syslog(get_class($this)."::validate action abandonned: already validated", LOG_WARNING);
			return 0;
		}

		/*if (! ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->mrp->create))
		 || (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->mrp->mrp_advance->validate))))
		 {
		 $this->error='NotEnoughPermissions';
		 dol_syslog(get_class($this)."::valid ".$this->error, LOG_ERR);
		 return -1;
		 }*/

		$now = dol_now();

		$this->db->begin();

		// Define new ref
		if (!$error && (preg_match('/^[\(]?PROV/i', $this->ref) || empty($this->ref))) // empty should not happened, but when it occurs, the test save life
		{
			$this->fetch_product();
			$num = $this->getNextNumRef($this->product);
		} else {
			$num = $this->ref;
		}
		$this->newref = $num;

		// Validate
		$sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element;
		$sql .= " SET ref = '".$this->db->escape($num)."',";
		$sql .= " status = ".self::STATUS_VALIDATED.",";
		$sql .= " date_valid='".$this->db->idate($now)."',";
		$sql .= " fk_user_valid = ".$user->id;
		$sql .= " WHERE rowid = ".$this->id;

		dol_syslog(get_class($this)."::validate()", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql)
		{
			dol_print_error($this->db);
			$this->error = $this->db->lasterror();
			$error++;
		}

		if (!$error && !$notrigger)
		{
			// Call trigger
			$result = $this->call_trigger('MRP_MO_VALIDATE', $user);
			if ($result < 0) $error++;
			// End call triggers
		}

		if (!$error)
		{
			$this->oldref = $this->ref;

			// Rename directory if dir was a temporary ref
			if (preg_match('/^[\(]?PROV/i', $this->ref))
			{
				// Now we rename also files into index
				$sql = 'UPDATE '.MAIN_DB_PREFIX."ecm_files set filename = CONCAT('".$this->db->escape($this->newref)."', SUBSTR(filename, ".(strlen($this->ref) + 1).")), filepath = 'mrp/".$this->db->escape($this->newref)."'";
				$sql .= " WHERE filename LIKE '".$this->db->escape($this->ref)."%' AND filepath = 'mrp/".$this->db->escape($this->ref)."' and entity = ".$conf->entity;
				$resql = $this->db->query($sql);
				if (!$resql) { $error++; $this->error = $this->db->lasterror(); }

				// We rename directory ($this->ref = old ref, $num = new ref) in order not to lose the attachments
				$oldref = dol_sanitizeFileName($this->ref);
				$newref = dol_sanitizeFileName($num);
				$dirsource = $conf->mrp->dir_output.'/'.$oldref;
				$dirdest = $conf->mrp->dir_output.'/'.$newref;
				if (!$error && file_exists($dirsource))
				{
					dol_syslog(get_class($this)."::validate() rename dir ".$dirsource." into ".$dirdest);

					if (@rename($dirsource, $dirdest))
					{
						dol_syslog("Rename ok");
						// Rename docs starting with $oldref with $newref
						$listoffiles = dol_dir_list($conf->mrp->dir_output.'/'.$newref, 'files', 1, '^'.preg_quote($oldref, '/'));
						foreach ($listoffiles as $fileentry)
						{
							$dirsource = $fileentry['name'];
							$dirdest = preg_replace('/^'.preg_quote($oldref, '/').'/', $newref, $dirsource);
							$dirsource = $fileentry['path'].'/'.$dirsource;
							$dirdest = $fileentry['path'].'/'.$dirdest;
							@rename($dirsource, $dirdest);
						}
					}
				}
			}
		}

		// Set new ref and current status
		if (!$error)
		{
			$this->ref = $num;
			$this->status = self::STATUS_VALIDATED;
		}

		if (!$error)
		{
			$this->db->commit();
			return 1;
		} else {
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *	Set draft status
	 *
	 *	@param	User	$user			Object user that modify
	 *  @param	int		$notrigger		1=Does not execute triggers, 0=Execute triggers
	 *	@return	int						<0 if KO, >0 if OK
	 */
	public function setDraft($user, $notrigger = 0)
	{
		// Protection
		if ($this->status <= self::STATUS_DRAFT)
		{
			return 0;
		}

		/*if (! ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->mymodule->write))
		 || (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->mymodule->mymodule_advance->validate))))
		 {
		 $this->error='Permission denied';
		 return -1;
		 }*/

		return $this->setStatusCommon($user, self::STATUS_DRAFT, $notrigger, 'MRP_MO_UNVALIDATE');
	}

	/**
	 *	Set cancel status
	 *
	 *	@param	User	$user			Object user that modify
	 *  @param	int		$notrigger		1=Does not execute triggers, 0=Execute triggers
	 *	@return	int						<0 if KO, 0=Nothing done, >0 if OK
	 */
	public function cancel($user, $notrigger = 0)
	{
		// Protection
		if ($this->status != self::STATUS_VALIDATED && $this->status != self::STATUS_INPROGRESS)
		{
			return 0;
		}

		/*if (! ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->mymodule->write))
		 || (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->mymodule->mymodule_advance->validate))))
		 {
		 $this->error='Permission denied';
		 return -1;
		 }*/

		return $this->setStatusCommon($user, self::STATUS_CANCELED, $notrigger, 'MRP_MO_CANCEL');
	}

	/**
	 *	Set back to validated status
	 *
	 *	@param	User	$user			Object user that modify
	 *  @param	int		$notrigger		1=Does not execute triggers, 0=Execute triggers
	 *	@return	int						<0 if KO, 0=Nothing done, >0 if OK
	 */
	public function reopen($user, $notrigger = 0)
	{
		// Protection
		if ($this->status != self::STATUS_PRODUCED && $this->status != self::STATUS_CANCELED)
		{
			return 0;
		}

		/*if (! ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->mymodule->write))
		 || (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->mymodule->mymodule_advance->validate))))
		 {
		 $this->error='Permission denied';
		 return -1;
		 }*/

		return $this->setStatusCommon($user, self::STATUS_VALIDATED, $notrigger, 'MRP_MO_REOPEN');
	}

	/**
	 *  Return a link to the object card (with optionaly the picto)
	 *
	 *  @param  int     $withpicto                  Include picto in link (0=No picto, 1=Include picto into link, 2=Only picto)
	 *  @param  string  $option                     On what the link point to ('nolink', '', 'production', ...)
	 *  @param  int     $notooltip                  1=Disable tooltip
	 *  @param  string  $morecss                    Add more css on link
	 *  @param  int     $save_lastsearch_value      -1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 *  @return	string                              String with URL
	 */
	public function getNomUrl($withpicto = 0, $option = '', $notooltip = 0, $morecss = '', $save_lastsearch_value = -1)
	{
		global $conf, $langs, $hookmanager;

		if (!empty($conf->dol_no_mouse_hover)) $notooltip = 1; // Force disable tooltips

		$result = '';

		$label = img_picto('', $this->picto).' <u class="paddingrightonly">'.$langs->trans("ManufacturingOrder").'</u>';
		if (isset($this->status)) {
			$label .= ' '.$this->getLibStatut(5);
		}
		$label .= '<br>';
		$label .= '<b>'.$langs->trans('Ref').':</b> '.$this->ref;

		$url = dol_buildpath('/mrp/mo_card.php', 1).'?id='.$this->id;
		if ($option == 'production') $url = dol_buildpath('/mrp/mo_production.php', 1).'?id='.$this->id;

		if ($option != 'nolink')
		{
			// Add param to save lastsearch_values or not
			$add_save_lastsearch_values = ($save_lastsearch_value == 1 ? 1 : 0);
			if ($save_lastsearch_value == -1 && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) $add_save_lastsearch_values = 1;
			if ($add_save_lastsearch_values) $url .= '&save_lastsearch_values=1';
		}

		$linkclose = '';
		if (empty($notooltip))
		{
			if (!empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER))
			{
				$label = $langs->trans("ShowMo");
				$linkclose .= ' alt="'.dol_escape_htmltag($label, 1).'"';
			}
			$linkclose .= ' title="'.dol_escape_htmltag($label, 1).'"';
			$linkclose .= ' class="classfortooltip'.($morecss ? ' '.$morecss : '').'"';
		} else $linkclose = ($morecss ? ' class="'.$morecss.'"' : '');

		$linkstart = '<a href="'.$url.'"';
		$linkstart .= $linkclose.'>';
		$linkend = '</a>';

		$result .= $linkstart;
		if ($withpicto) $result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
		if ($withpicto != 2) $result .= $this->ref;
		$result .= $linkend;
		//if ($withpicto != 2) $result.=(($addlabel && $this->label) ? $sep . dol_trunc($this->label, ($addlabel > 1 ? $addlabel : 0)) : '');

		global $action, $hookmanager;
		$hookmanager->initHooks(array('modao'));
		$parameters = array('id'=>$this->id, 'getnomurl'=>$result);
		$reshook = $hookmanager->executeHooks('getNomUrl', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
		if ($reshook > 0) $result = $hookmanager->resPrint;
		else $result .= $hookmanager->resPrint;

		return $result;
	}

	/**
	 *  Return label of the status
	 *
	 *  @param  int		$mode          0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
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
	 *  @param	int		$status        Id status
	 *  @param  int		$mode          0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *  @return string 			       Label of status
	 */
	public function LibStatut($status, $mode = 0)
	{
		// phpcs:enable
		if (empty($this->labelStatus))
		{
			global $langs;
			//$langs->load("mrp");
			$this->labelStatus[self::STATUS_DRAFT] = $langs->transnoentitiesnoconv('Draft');
			$this->labelStatus[self::STATUS_VALIDATED] = $langs->transnoentitiesnoconv('ValidatedToProduce');
			$this->labelStatus[self::STATUS_INPROGRESS] = $langs->transnoentitiesnoconv('InProgress');
			$this->labelStatus[self::STATUS_PRODUCED] = $langs->transnoentitiesnoconv('StatusMOProduced');
			$this->labelStatus[self::STATUS_CANCELED] = $langs->transnoentitiesnoconv('Canceled');

			$this->labelStatusShort[self::STATUS_DRAFT] = $langs->transnoentitiesnoconv('Draft');
			$this->labelStatusShort[self::STATUS_VALIDATED] = $langs->transnoentitiesnoconv('Validated');
			$this->labelStatusShort[self::STATUS_INPROGRESS] = $langs->transnoentitiesnoconv('InProgress');
			$this->labelStatusShort[self::STATUS_PRODUCED] = $langs->transnoentitiesnoconv('StatusMOProduced');
			$this->labelStatusShort[self::STATUS_CANCELED] = $langs->transnoentitiesnoconv('Canceled');
		}

		$statusType = 'status'.$status;
		if ($status == self::STATUS_VALIDATED) $statusType = 'status1';
		if ($status == self::STATUS_INPROGRESS) $statusType = 'status4';
		if ($status == self::STATUS_PRODUCED) $statusType = 'status6';
		if ($status == self::STATUS_CANCELED) $statusType = 'status9';

		return dolGetStatus($this->labelStatus[$status], $this->labelStatusShort[$status], '', $statusType, $mode);
	}

	/**
	 *	Load the info information in the object
	 *
	 *	@param  int		$id       Id of object
	 *	@return	void
	 */
	public function info($id)
	{
		$sql = 'SELECT rowid, date_creation as datec, tms as datem,';
		$sql .= ' fk_user_creat, fk_user_modif';
		$sql .= ' FROM '.MAIN_DB_PREFIX.$this->table_element.' as t';
		$sql .= ' WHERE t.rowid = '.$id;
		$result = $this->db->query($sql);
		if ($result)
		{
			if ($this->db->num_rows($result))
			{
				$obj = $this->db->fetch_object($result);
				$this->id = $obj->rowid;
				if ($obj->fk_user_author)
				{
					$cuser = new User($this->db);
					$cuser->fetch($obj->fk_user_author);
					$this->user_creation = $cuser;
				}

				if ($obj->fk_user_valid)
				{
					$vuser = new User($this->db);
					$vuser->fetch($obj->fk_user_valid);
					$this->user_validation = $vuser;
				}

				if ($obj->fk_user_cloture)
				{
					$cluser = new User($this->db);
					$cluser->fetch($obj->fk_user_cloture);
					$this->user_cloture = $cluser;
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
	}

	/**
	 * 	Create an array of lines
	 *
	 * 	@return array|int		array of lines if OK, <0 if KO
	 */
	public function getLinesArray()
	{
		$this->lines = array();

		$objectline = new MoLine($this->db);
		$result = $objectline->fetchAll('ASC', 'position', 0, 0, array('customsql'=>'fk_mo = '.$this->id));

		if (is_numeric($result))
		{
			$this->error = $this->error;
			$this->errors = $this->errors;
			return $result;
		} else {
			$this->lines = $result;
			return $this->lines;
		}
	}

	/**
	 *  Create a document onto disk according to template module.
	 *
	 *  @param	    string		$modele			Force template to use ('' to not force)
	 *  @param		Translate	$outputlangs	objet lang a utiliser pour traduction
	 *  @param      int			$hidedetails    Hide details of lines
	 *  @param      int			$hidedesc       Hide description
	 *  @param      int			$hideref        Hide ref
	 *  @param      null|array  $moreparams     Array to provide more information
	 *  @return     int         				0 if KO, 1 if OK
	 */
	public function generateDocument($modele, $outputlangs, $hidedetails = 0, $hidedesc = 0, $hideref = 0, $moreparams = null)
	{
		global $conf, $langs;

		$langs->load("mrp");

		if (!dol_strlen($modele)) {
			//$modele = 'standard';
			$modele = ''; // Remove this once a pdf_standard.php exists.

			if ($this->model_pdf) {
				$modele = $this->model_pdf;
			} elseif (!empty($conf->global->MO_ADDON_PDF)) {
				$modele = $conf->global->MO_ADDON_PDF;
			}
		}

		$modelpath = "core/modules/mrp/doc/";

		if (empty($modele)) return 1; // Remove this once a pdf_standard.php exists.

		return $this->commonGenerateDocument($modelpath, $modele, $outputlangs, $hidedetails, $hidedesc, $hideref, $moreparams);
	}

	/**
	 * Action executed by scheduler
	 * CAN BE A CRON TASK. In such a case, parameters come from the schedule job setup field 'Parameters'
	 * Use public function doScheduledJob($param1, $param2, ...) to get parameters
	 *
	 * @return	int			0 if OK, <>0 if KO (this function is used also by cron so only 0 is OK)
	 */
	public function doScheduledJob()
	{
		global $conf, $langs;

		//$conf->global->SYSLOG_FILE = 'DOL_DATA_ROOT/dolibarr_mydedicatedlofile.log';

		$error = 0;
		$this->output = '';
		$this->error = '';

		dol_syslog(__METHOD__, LOG_DEBUG);

		$now = dol_now();

		$this->db->begin();

		// ...

		$this->db->commit();

		return $error;
	}

	/**
	 * 	Return HTML table table of source object lines
	 *  TODO Move this and previous function into output html class file (htmlline.class.php).
	 *  If lines are into a template, title must also be into a template
	 *  But for the moment we don't know if it's possible, so we keep the method available on overloaded objects.
	 *
	 *	@param	string		$restrictlist		''=All lines, 'services'=Restrict to services only
	 *  @param  array       $selectedLines      Array of lines id for selected lines
	 *  @return	void
	 */
	public function printOriginLinesList($restrictlist = '', $selectedLines = array())
	{
		global $langs, $hookmanager, $conf, $form;

		print '<tr class="liste_titre">';
		print '<td>'.$langs->trans('Ref').'</td>';
		print '<td class="right">'.$langs->trans('Qty').' <span class="opacitymedium">('.$langs->trans("ForAQuantityOf", $this->bom->qty).')</span></td>';
		print '<td class="center">'.$langs->trans('QtyFrozen').'</td>';
		print '<td class="center">'.$langs->trans('DisableStockChange').'</td>';
		//print '<td class="right">'.$langs->trans('Efficiency').'</td>';
		//print '<td class="center">'.$form->showCheckAddButtons('checkforselect', 1).'</td>';
		print '<td class="center"></td>';
		print '</tr>';
		$i = 0;

		if (!empty($this->lines))
		{
			foreach ($this->lines as $line)
			{
				/*if (is_object($hookmanager) && (($line->product_type == 9 && !empty($line->special_code)) || !empty($line->fk_parent_line)))
				{
					if (empty($line->fk_parent_line))
					{
						$parameters = array('line'=>$line, 'i'=>$i);
						$action = '';
						$result = $hookmanager->executeHooks('printOriginObjectLine', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
					}
				}
				else
				{*/
					$this->printOriginLine($line, '', $restrictlist, '/core/tpl', $selectedLines);
				//}

				$i++;
			}
		}
	}


	/**
	 * 	Return HTML with a line of table array of source object lines
	 *  TODO Move this and previous function into output html class file (htmlline.class.php).
	 *  If lines are into a template, title must also be into a template
	 *  But for the moment we don't know if it's possible as we keep a method available on overloaded objects.
	 *
	 * 	@param	CommonObjectLine	$line				Line
	 * 	@param	string				$var				Var
	 *	@param	string				$restrictlist		''=All lines, 'services'=Restrict to services only (strike line if not)
	 *  @param	string				$defaulttpldir		Directory where to find the template
	 *  @param  array       		$selectedLines      Array of lines id for selected lines
	 * 	@return	void
	 */
	public function printOriginLine($line, $var, $restrictlist = '', $defaulttpldir = '/core/tpl', $selectedLines = array())
	{
		global $langs, $conf;

		$this->tpl['id'] = $line->id;

		$this->tpl['label'] = '';
		if (!empty($line->fk_product))
		{
			$productstatic = new Product($this->db);
			$productstatic->fetch($line->fk_product);
			$this->tpl['label'] .= $productstatic->getNomUrl(1);
			//$this->tpl['label'].= ' - '.$productstatic->label;
		} else {
			// If origin MRP line is not a product, but another MRP
			// TODO
		}

		$this->tpl['qty_bom'] = 1;
		if (is_object($this->bom) && $this->bom->qty > 1) {
			$this->tpl['qty_bom'] = $this->bom->qty;
		}

		$this->tpl['qty'] = $line->qty;
		$this->tpl['qty_frozen'] = $line->qty_frozen;
		$this->tpl['disable_stock_change'] = $line->disable_stock_change;
		$this->tpl['efficiency'] = $line->efficiency;

		$tpl = DOL_DOCUMENT_ROOT.'/mrp/tpl/originproductline.tpl.php';
		$res = include $tpl;
	}

	/**
	 * Function used to replace a thirdparty id with another one.
	 *
	 * @param DoliDB 	$db 			Database handler
	 * @param int 		$origin_id 		Old thirdparty id
	 * @param int 		$dest_id 		New thirdparty id
	 * @return bool
	 */
	public static function replaceThirdparty($db, $origin_id, $dest_id)
	{
		$tables = array('mrp_mo');

		return CommonObject::commonReplaceThirdparty($db, $origin_id, $dest_id, $tables);
	}
}

/**
 * Class MoLine. You can also remove this and generate a CRUD class for lines objects.
 */
class MoLine extends CommonObjectLine
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'mrp_production';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'mrp_production';

	/**
	 * @var int  Does myobject support multicompany module ? 0=No test on entity, 1=Test with field entity, 2=Test with link by societe
	 */
	public $ismultientitymanaged = 0;

	/**
	 * @var int  Does moline support extrafields ? 0=No, 1=Yes
	 */
	public $isextrafieldmanaged = 0;

	public $fields = array(
		'rowid' =>array('type'=>'integer', 'label'=>'ID', 'enabled'=>1, 'visible'=>-1, 'notnull'=>1, 'position'=>10),
		'fk_mo' =>array('type'=>'integer', 'label'=>'Mo', 'enabled'=>1, 'visible'=>-1, 'notnull'=>1, 'position'=>15),
		'position' =>array('type'=>'integer', 'label'=>'Position', 'enabled'=>1, 'visible'=>-1, 'notnull'=>1, 'position'=>20),
		'fk_product' =>array('type'=>'integer', 'label'=>'Product', 'enabled'=>1, 'visible'=>-1, 'notnull'=>1, 'position'=>25),
		'fk_warehouse' =>array('type'=>'integer', 'label'=>'Warehouse', 'enabled'=>1, 'visible'=>-1, 'position'=>30),
		'qty' =>array('type'=>'real', 'label'=>'Qty', 'enabled'=>1, 'visible'=>-1, 'notnull'=>1, 'position'=>35),
		'qty_frozen' => array('type'=>'smallint', 'label'=>'QuantityFrozen', 'enabled'=>1, 'visible'=>1, 'default'=>0, 'position'=>105, 'css'=>'maxwidth50imp', 'help'=>'QuantityConsumedInvariable'),
		'disable_stock_change' => array('type'=>'smallint', 'label'=>'DisableStockChange', 'enabled'=>1, 'visible'=>1, 'default'=>0, 'position'=>108, 'css'=>'maxwidth50imp', 'help'=>'DisableStockChangeHelp'),
		'batch' =>array('type'=>'varchar(30)', 'label'=>'Batch', 'enabled'=>1, 'visible'=>-1, 'position'=>140),
		'role' =>array('type'=>'varchar(10)', 'label'=>'Role', 'enabled'=>1, 'visible'=>-1, 'position'=>145),
		'fk_mrp_production' =>array('type'=>'integer', 'label'=>'Fk mrp production', 'enabled'=>1, 'visible'=>-1, 'position'=>150),
		'fk_stock_movement' =>array('type'=>'integer', 'label'=>'StockMovement', 'enabled'=>1, 'visible'=>-1, 'position'=>155),
		'date_creation' =>array('type'=>'datetime', 'label'=>'DateCreation', 'enabled'=>1, 'visible'=>-1, 'notnull'=>1, 'position'=>160),
		'tms' =>array('type'=>'timestamp', 'label'=>'Tms', 'enabled'=>1, 'visible'=>-1, 'notnull'=>1, 'position'=>165),
		'fk_user_creat' =>array('type'=>'integer', 'label'=>'UserCreation', 'enabled'=>1, 'visible'=>-1, 'notnull'=>1, 'position'=>170),
		'fk_user_modif' =>array('type'=>'integer', 'label'=>'UserModification', 'enabled'=>1, 'visible'=>-1, 'position'=>175),
		'import_key' =>array('type'=>'varchar(14)', 'label'=>'ImportId', 'enabled'=>1, 'visible'=>-1, 'position'=>180),
	);

	public $rowid;
	public $fk_mo;
	public $position;
	public $fk_product;
	public $fk_warehouse;
	public $qty;
	public $qty_frozen;
	public $disable_stock_change;
	public $batch;
	public $role;
	public $fk_mrp_production;
	public $fk_stock_movement;
	public $date_creation;
	public $tms;
	public $fk_user_creat;
	public $fk_user_modif;
	public $import_key;

	/**
	 * Constructor
	 *
	 * @param DoliDb $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		global $conf, $langs;

		$this->db = $db;

		if (empty($conf->global->MAIN_SHOW_TECHNICAL_ID) && isset($this->fields['rowid'])) $this->fields['rowid']['visible'] = 0;
		if (empty($conf->multicompany->enabled) && isset($this->fields['entity'])) $this->fields['entity']['enabled'] = 0;

		// Unset fields that are disabled
		foreach ($this->fields as $key => $val)
		{
			if (isset($val['enabled']) && empty($val['enabled']))
			{
				unset($this->fields[$key]);
			}
		}

		// Translate some data of arrayofkeyval
		if (is_object($langs))
		{
			foreach ($this->fields as $key => $val)
			{
				if (!empty($val['arrayofkeyval']) && is_array($val['arrayofkeyval']))
				{
					foreach ($val['arrayofkeyval'] as $key2 => $val2)
					{
						$this->fields[$key]['arrayofkeyval'][$key2] = $langs->trans($val2);
					}
				}
			}
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
		if (empty($this->qty)) {
			$this->error = 'BadValueForQty';
			return -1;
		}

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
		if ($result > 0 && !empty($this->table_element_line)) $this->fetchLines();
		return $result;
	}

	/**
	 * Load list of objects in memory from the database.
	 *
	 * @param  string      $sortorder    Sort Order
	 * @param  string      $sortfield    Sort field
	 * @param  int         $limit        limit
	 * @param  int         $offset       Offset
	 * @param  array       $filter       Filter array. Example array('field'=>'valueforlike', 'customurl'=>...)
	 * @param  string      $filtermode   Filter mode (AND or OR)
	 * @return array|int                 int <0 if KO, array of pages if OK
	 */
	public function fetchAll($sortorder = '', $sortfield = '', $limit = 0, $offset = 0, array $filter = array(), $filtermode = 'AND')
	{
		global $conf;

		dol_syslog(__METHOD__, LOG_DEBUG);

		$records = array();

		$sql = 'SELECT ';
		$sql .= $this->getFieldList();
		$sql .= ' FROM '.MAIN_DB_PREFIX.$this->table_element.' as t';
		if (isset($this->ismultientitymanaged) && $this->ismultientitymanaged == 1) $sql .= ' WHERE t.entity IN ('.getEntity($this->table_element).')';
		else $sql .= ' WHERE 1 = 1';
		// Manage filter
		$sqlwhere = array();
		if (count($filter) > 0) {
			foreach ($filter as $key => $value) {
				if ($key == 't.rowid') {
					$sqlwhere[] = $key.'='.$value;
				} elseif (strpos($key, 'date') !== false) {
					$sqlwhere[] = $key.' = \''.$this->db->idate($value).'\'';
				} elseif ($key == 'customsql') {
					$sqlwhere[] = $value;
				} else {
					$sqlwhere[] = $key.' LIKE \'%'.$this->db->escape($value).'%\'';
				}
			}
		}
		if (count($sqlwhere) > 0) {
			$sql .= ' AND ('.implode(' '.$filtermode.' ', $sqlwhere).')';
		}

		if (!empty($sortfield)) {
			$sql .= $this->db->order($sortfield, $sortorder);
		}
		if (!empty($limit)) {
			$sql .= ' '.$this->db->plimit($limit, $offset);
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < min($limit, $num))
			{
				$obj = $this->db->fetch_object($resql);

				$record = new self($this->db);
				$record->setVarsFromFetchObj($obj);

				$records[$record->id] = $record;

				$i++;
			}
			$this->db->free($resql);

			return $records;
		} else {
			$this->errors[] = 'Error '.$this->db->lasterror();
			dol_syslog(__METHOD__.' '.join(',', $this->errors), LOG_ERR);

			return -1;
		}
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
