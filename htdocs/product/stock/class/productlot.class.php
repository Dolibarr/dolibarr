<?php
/* Copyright (C) 2007-2012  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2014       Juanjo Menent       <jmenent@2byte.es>
 * Copyright (C) 2015       Florian Henry       <florian.henry@open-concept.pro>
 * Copyright (C) 2015       Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2018       Frédéric France     <frederic.france@netlogic.fr>
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
	 * 0=No test on entity, 1=Test with field entity, 2=Test with link by societe
	 * @var int
	 */
	public $ismultientitymanaged = 1;


	/**
	 *  'type' if the field format ('integer', 'integer:ObjectClass:PathToClass[:AddCreateButtonOrNot[:Filter]]', 'varchar(x)', 'double(24,8)', 'real', 'price', 'text', 'html', 'date', 'datetime', 'timestamp', 'duration', 'mail', 'phone', 'url', 'password')
	 *         Note: Filter can be a string like "(t.ref:like:'SO-%') or (t.date_creation:<:'20160101') or (t.nature:is:NULL)"
	 *  'label' the translation key.
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
	 * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields = array(
		'rowid'         => array('type'=>'integer', 'label'=>'TechnicalID', 'enabled'=>1, 'visible'=>-2, 'noteditable'=>1, 'notnull'=> 1, 'index'=>1, 'position'=>1, 'comment'=>'Id', 'css'=>'left'),
		'fk_product'    => array('type'=>'integer:Product:product/class/product.class.php', 'label'=>'Product', 'enabled'=>1, 'visible'=>1, 'position'=>5, 'notnull'=>1, 'index'=>1, 'searchall'=>1),
		'batch'         => array('type'=>'varchar(30)', 'label'=>'Batch', 'enabled'=>1, 'visible'=>1, 'notnull'=>0, 'showoncombobox'=>1, 'index'=>1, 'position'=>10, 'comment'=>'Batch', 'searchall'=>1),
		'entity'        => array('type'=>'integer', 'label'=>'Entity', 'enabled'=>1, 'visible'=>0, 'default'=>1, 'notnull'=>1, 'index'=>1, 'position'=>20),
		'sellby'        => array('type'=>'date', 'label'=>'SellByDate', 'enabled'=>'empty($conf->global->PRODUCT_DISABLE_SELLBY)?1:0', 'visible'=>5, 'position'=>60),
		'eol_date'        => array('type'=>'date', 'label'=>'EndOfLife', 'enabled'=>'empty($conf->global->PRODUCT_ENABLE_TRACEABILITY)?0:1', 'visible'=>5, 'position'=>70),
		'manufacturing_date' => array('type'=>'date', 'label'=>'ManufacturingDate', 'enabled'=>'empty($conf->global->PRODUCT_ENABLE_TRACEABILITY)?0:1', 'visible'=>5, 'position'=>80),
		'scrapping_date'     => array('type'=>'date', 'label'=>'DestructionDate', 'enabled'=>'empty($conf->global->PRODUCT_ENABLE_TRACEABILITY)?0:1', 'visible'=>5, 'position'=>90),
		//'commissionning_date'        => array('type'=>'date', 'label'=>'FirstUseDate', 'enabled'=>'empty($conf->global->PRODUCT_ENABLE_TRACEABILITY)?0:1', 'visible'=>5, 'position'=>100),
		//'qc_frequency'        => array('type'=>'varchar(6)', 'label'=>'QCFrequency', 'enabled'=>'empty($conf->global->PRODUCT_ENABLE_QUALITYCONTROL)?1:0', 'visible'=>5, 'position'=>110),
		'eatby'         => array('type'=>'date', 'label'=>'EatByDate', 'enabled'=>'empty($conf->global->PRODUCT_DISABLE_EATBY)?1:0', 'visible'=>5, 'position'=>62),
		'datec'         => array('type'=>'datetime', 'label'=>'DateCreation', 'enabled'=>1, 'visible'=>1, 'notnull'=>1, 'position'=>500),
		'tms'           => array('type'=>'timestamp', 'label'=>'DateModification', 'enabled'=>1, 'visible'=>-2, 'notnull'=>1, 'position'=>501),
		'fk_user_creat' => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'UserAuthor', 'enabled'=>1, 'visible'=>-2, 'notnull'=>1, 'position'=>510, 'foreignkey'=>'llx_user.rowid'),
		'fk_user_modif' => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'UserModif', 'enabled'=>1, 'visible'=>-2, 'notnull'=>-1, 'position'=>511),
		'import_key'    => array('type'=>'varchar(14)', 'label'=>'ImportId', 'enabled'=>1, 'visible'=>-2, 'notnull'=>-1, 'index'=>0, 'position'=>1000),
	);

	/**
	 * @var int Entity
	 */
	public $entity;

	/**
	 * @var int ID
	 */
	public $fk_product;

	public $batch;
	public $eatby = '';
	public $sellby = '';
	public $eol_date = '';
	public $manufacturing_date = '';
	public $scrapping_date = '';
	//public $commissionning_date = '';
	//public $qc_frequency = '';
	public $datec = '';
	public $tms = '';

	/**
	 * @var int ID
	 */
	public $fk_user_creat;

	/**
	 * @var int ID
	 */
	public $fk_user_modif;

	public $import_key;


	/**
	 * Constructor
	 *
	 * @param DoliDb $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		$this->db = $db;
	}

	/**
	 * Create object into database
	 *
	 * @param  User $user      User that creates
	 * @param  bool $notrigger false=launch triggers after, true=disable triggers
	 *
	 * @return int <0 if KO, Id of created object if OK
	 */
	public function create(User $user, $notrigger = false)
	{
		global $conf;
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
		// Put here code to add control on parameters values

		// Insert request
		$sql = 'INSERT INTO '.MAIN_DB_PREFIX.$this->table_element.'(';
		$sql .= 'entity,';
		$sql .= 'fk_product,';
		$sql .= 'batch,';
		$sql .= 'eatby,';
		$sql .= 'sellby,';
		$sql .= 'eol_date,';
		$sql .= 'manufacturing_date,';
		$sql .= 'scrapping_date,';
		//$sql .= 'commissionning_date,';
		//$sql .= 'qc_frequency,';
		$sql .= 'datec,';
		$sql .= 'fk_user_creat,';
		$sql .= 'fk_user_modif,';
		$sql .= 'import_key';
		$sql .= ') VALUES (';
		$sql .= ' '.(!isset($this->entity) ? $conf->entity : $this->entity).',';
		$sql .= ' '.(!isset($this->fk_product) ? 'NULL' : $this->fk_product).',';
		$sql .= ' '.(!isset($this->batch) ? 'NULL' : "'".$this->db->escape($this->batch)."'").',';
		$sql .= ' '.(!isset($this->eatby) || dol_strlen($this->eatby) == 0 ? 'NULL' : "'".$this->db->idate($this->eatby)."'").',';
		$sql .= ' '.(!isset($this->sellby) || dol_strlen($this->sellby) == 0 ? 'NULL' : "'".$this->db->idate($this->sellby)."'").',';
		$sql .= ' '.(!isset($this->eol_date) || dol_strlen($this->eol_date) == 0 ? 'NULL' : "'".$this->db->idate($this->eol_date)."'").',';
		$sql .= ' '.(!isset($this->manufacturing_date) || dol_strlen($this->manufacturing_date) == 0 ? 'NULL' : "'".$this->db->idate($this->manufacturing_date)."'").',';
		$sql .= ' '.(!isset($this->scrapping_date) || dol_strlen($this->scrapping_date) == 0 ? 'NULL' : "'".$this->db->idate($this->scrapping_date)."'").',';
		//$sql .= ' '.(!isset($this->commissionning_date) || dol_strlen($this->commissionning_date) == 0 ? 'NULL' : "'".$this->db->idate($this->commissionning_date)."'").',';
		//$sql .= ' '.(!isset($this->qc_frequency) ? 'NULL' : $this->qc_frequency).',';
		$sql .= ' '."'".$this->db->idate(dol_now())."'".',';
		$sql .= ' '.(!isset($this->fk_user_creat) ? 'NULL' : $this->fk_user_creat).',';
		$sql .= ' '.(!isset($this->fk_user_modif) ? 'NULL' : $this->fk_user_modif).',';
		$sql .= ' '.(!isset($this->import_key) ? 'NULL' : $this->import_key);
		$sql .= ')';

		$this->db->begin();

		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++;
			$this->errors[] = 'Error '.$this->db->lasterror();
			dol_syslog(__METHOD__.' '.join(',', $this->errors), LOG_ERR);
		}

		if (!$error) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.$this->table_element);

			// Actions on extra fields
			if (!$error) {
				$result = $this->insertExtraFields();
				if ($result < 0) {
					$error++;
				}
			}

			if (!$error && !$notrigger) {
				// Uncomment this and change MYOBJECT to your own tag if you
				// want this action to call a trigger.

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

			return -1 * $error;
		} else {
			$this->db->commit();

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
	 * @return int <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id = 0, $product_id = 0, $batch = '')
	{
		global $conf;
		dol_syslog(__METHOD__, LOG_DEBUG);

		$sql = 'SELECT';
		$sql .= ' t.rowid,';
		$sql .= " t.entity,";
		$sql .= " t.fk_product,";
		$sql .= " t.batch,";
		$sql .= " t.eatby,";
		$sql .= " t.sellby,";
		$sql .= " t.eol_date,";
		$sql .= " t.manufacturing_date,";
		$sql .= " t.scrapping_date,";
		//$sql .= " t.commissionning_date,";
		//$sql .= " t.qc_frequency,";
		$sql .= " t.datec,";
		$sql .= " t.tms,";
		$sql .= " t.fk_user_creat,";
		$sql .= " t.fk_user_modif,";
		$sql .= " t.import_key";
		$sql .= ' FROM '.MAIN_DB_PREFIX.$this->table_element.' as t';
		if ($product_id > 0 && $batch != '') {
			$sql .= " WHERE t.batch = '".$this->db->escape($batch)."' AND t.fk_product = ".((int) $product_id);
		} else {
			$sql .= ' WHERE t.rowid = '.((int) $id);
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
				//$this->qc_frequency = $obj->qc_frequency;

				$this->datec = $this->db->jdate($obj->datec);
				$this->tms = $this->db->jdate($obj->tms);
				$this->fk_user_creat = $obj->fk_user_creat;
				$this->fk_user_modif = $obj->fk_user_modif;
				$this->import_key = $obj->import_key;

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
			dol_syslog(__METHOD__.' '.join(',', $this->errors), LOG_ERR);

			return -1;
		}
	}

	/**
	 * Update object into database
	 *
	 * @param  User $user      User that modifies
	 * @param  bool $notrigger false=launch triggers after, true=disable triggers
	 *
	 * @return int <0 if KO, >0 if OK
	 */
	public function update(User $user, $notrigger = false)
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

		if (empty($this->oldcopy)) {
			$org = new self($this->db);
			$org->fetch($this->id);
			$this->oldcopy = $org;
		}

		// Update request
		$sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element.' SET';
		$sql .= ' entity = '.(isset($this->entity) ? $this->entity : "null").',';
		$sql .= ' fk_product = '.(isset($this->fk_product) ? $this->fk_product : "null").',';
		$sql .= ' batch = '.(isset($this->batch) ? "'".$this->db->escape($this->batch)."'" : "null").',';
		$sql .= ' eatby = '.(!isset($this->eatby) || dol_strlen($this->eatby) != 0 ? "'".$this->db->idate($this->eatby)."'" : 'null').',';
		$sql .= ' sellby = '.(!isset($this->sellby) || dol_strlen($this->sellby) != 0 ? "'".$this->db->idate($this->sellby)."'" : 'null').',';
		$sql .= ' eol_date = '.(!isset($this->eol_date) || dol_strlen($this->eol_date) != 0 ? "'".$this->db->idate($this->eol_date)."'" : 'null').',';
		$sql .= ' manufacturing_date = '.(!isset($this->manufacturing_date) || dol_strlen($this->manufacturing_date) != 0 ? "'".$this->db->idate($this->manufacturing_date)."'" : 'null').',';
		$sql .= ' scrapping_date = '.(!isset($this->scrapping_date) || dol_strlen($this->scrapping_date) != 0 ? "'".$this->db->idate($this->scrapping_date)."'" : 'null').',';
		//$sql .= ' commissionning_date = '.(!isset($this->first_use_date) || dol_strlen($this->first_use_date) != 0 ? "'".$this->db->idate($this->first_use_date)."'" : 'null').',';
		//$sql .= ' qc_frequency = '.(!isset($this->qc_frequency) || dol_strlen($this->qc_frequency) != 0 ? "'".$this->db->escape($this->qc_frequency)."'" : 'null').',';
		$sql .= ' datec = '.(!isset($this->datec) || dol_strlen($this->datec) != 0 ? "'".$this->db->idate($this->datec)."'" : 'null').',';
		$sql .= ' tms = '.(dol_strlen($this->tms) != 0 ? "'".$this->db->idate($this->tms)."'" : "'".$this->db->idate(dol_now())."'").',';
		$sql .= ' fk_user_creat = '.(isset($this->fk_user_creat) ? $this->fk_user_creat : "null").',';
		$sql .= ' fk_user_modif = '.(isset($this->fk_user_modif) ? $this->fk_user_modif : "null").',';
		$sql .= ' import_key = '.(isset($this->import_key) ? $this->import_key : "null");
		$sql .= ' WHERE rowid='.((int) $this->id);

		$this->db->begin();

		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++;
			$this->errors[] = 'Error '.$this->db->lasterror();
			dol_syslog(__METHOD__.' '.join(',', $this->errors), LOG_ERR);
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

			return -1 * $error;
		} else {
			$this->db->commit();

			return 1;
		}
	}

	/**
	 * Delete object in database
	 *
	 * @param User $user      User that deletes
	 * @param bool $notrigger false=launch triggers after, true=disable triggers
	 *
	 * @return int <0 if KO, >0 if OK
	 */
	public function delete(User $user, $notrigger = false)
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

		$error = 0;

		$this->db->begin();

		//if (!$error) {
			//if (!$notrigger) {
				// Uncomment this and change MYOBJECT to your own tag if you
				// want this action calls a trigger.

				//// Call triggers
				//$result=$this->call_trigger('MYOBJECT_DELETE',$user);
				//if ($result < 0) { $error++; //Do also what you must do to rollback action if trigger fail}
				//// End call triggers
			//}
		//}

		if (!$error) {
			$sql = 'DELETE FROM '.MAIN_DB_PREFIX.$this->table_element;
			$sql .= ' WHERE rowid='.((int) $this->id);

			$resql = $this->db->query($sql);
			if (!$resql) {
				$error++;
				$this->errors[] = 'Error '.$this->db->lasterror();
				dol_syslog(__METHOD__.' '.join(',', $this->errors), LOG_ERR);
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
			dol_syslog(__METHOD__.' '.join(',', $this->errors), LOG_ERR);
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
		global $langs;

		//$langs->load('stocks');

		return '';
	}


	/**
	 *  Return a link to the a lot card (with optionaly the picto)
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
		global $langs, $conf, $db;
		global $dolibarr_main_authentication, $dolibarr_main_demo;
		global $menumanager;

		$result = '';

		$label = img_picto('', $this->picto).' <u>'.$langs->trans("Batch").'</u>';
		$label .= '<div width="100%">';
		$label .= '<b>'.$langs->trans('Batch').':</b> '.$this->batch;
		if ($this->eatby && empty($conf->global->PRODUCT_DISABLE_EATBY)) {
			$label .= '<br><b>'.$langs->trans('EatByDate').':</b> '.dol_print_date($this->eatby, 'day');
		}
		if ($this->sellby && empty($conf->global->PRODUCT_DISABLE_SELLBY)) {
			$label .= '<br><b>'.$langs->trans('SellByDate').':</b> '.dol_print_date($this->sellby, 'day');
		}

		$url = DOL_URL_ROOT.'/product/stock/productlot_card.php?id='.$this->id;

		if ($option != 'nolink') {
			// Add param to save lastsearch_values or not
			$add_save_lastsearch_values = ($save_lastsearch_value == 1 ? 1 : 0);
			if ($save_lastsearch_value == -1 && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) {
				$add_save_lastsearch_values = 1;
			}
			if ($add_save_lastsearch_values) {
				$url .= '&save_lastsearch_values=1';
			}
		}

		$linkclose = '';
		if (empty($notooltip)) {
			if (!empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) {
				$label = $langs->trans("ShowMyObject");
				$linkclose .= ' alt="'.dol_escape_htmltag($label, 1).'"';
			}
			$linkclose .= ' title="'.dol_escape_htmltag($label, 1).'"';
			$linkclose .= ' class="classfortooltip'.($morecss ? ' '.$morecss : '').'"';
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
			$result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
		}
		if ($withpicto != 2) {
			$result .= $this->batch;
		}
		$result .= $linkend;

		return $result;
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

		$this->entity = null;
		$this->fk_product = null;
		$this->batch = '';
		$this->eatby = '';
		$this->sellby = '';
		$this->datec = '';
		$this->tms = '';
		$this->fk_user_creat = null;
		$this->fk_user_modif = null;
		$this->import_key = '';
	}
}
