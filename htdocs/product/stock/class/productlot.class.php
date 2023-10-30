<?php
/* Copyright (C) 2007-2012  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2014       Juanjo Menent       <jmenent@2byte.es>
 * Copyright (C) 2015       Florian Henry       <florian.henry@open-concept.pro>
 * Copyright (C) 2015       Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2018-2022  Frédéric France     <frederic.france@netlogic.fr>
 * Copyright (C) 2023	   	Gauthier VERDOL		<gauthier.verdol@atm-consulting.fr>
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

	public $stats_propale;
	public $stats_commande;
	public $stats_contrat;
	public $stats_facture;
	public $stats_commande_fournisseur;
	public $stats_expedition;
	public $stats_reception;
	public $stats_mo;
	public $stats_bom;
	public $stats_mrptoconsume;
	public $stats_mrptoproduce;
	public $stats_facturerec;
	public $stats_facture_fournisseur;


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
		'fk_product'    => array('type'=>'integer:Product:product/class/product.class.php', 'label'=>'Product', 'enabled'=>1, 'visible'=>1, 'position'=>5, 'notnull'=>1, 'index'=>1, 'searchall'=>1, 'picto' => 'product', 'css'=>'maxwidth500 widthcentpercentminusxx', 'csslist'=>'maxwidth150'),
		'batch'         => array('type'=>'varchar(30)', 'label'=>'Batch', 'enabled'=>1, 'visible'=>1, 'notnull'=>1, 'showoncombobox'=>1, 'index'=>1, 'position'=>10, 'comment'=>'Batch', 'searchall'=>1, 'picto'=>'lot'),
		'entity'        => array('type'=>'integer', 'label'=>'Entity', 'enabled'=>1, 'visible'=>0, 'default'=>1, 'notnull'=>1, 'index'=>1, 'position'=>20),
		'sellby'        => array('type'=>'date', 'label'=>'SellByDate', 'enabled'=>'empty($conf->global->PRODUCT_DISABLE_SELLBY)?1:0', 'visible'=>5, 'position'=>60),
		'eol_date'        => array('type'=>'date', 'label'=>'EndOfLife', 'enabled'=>'getDolGlobalInt("PRODUCT_LOT_ENABLE_QUALITY_CONTROL")?1:0', 'visible'=>'getDolGlobalInt("PRODUCT_LOT_ENABLE_QUALITY_CONTROL")?5:0', 'position'=>70),
		'manufacturing_date' => array('type'=>'date', 'label'=>'ManufacturingDate', 'enabled'=>'getDolGlobalInt("PRODUCT_LOT_ENABLE_TRACEABILITY")?1:0', 'visible'=>'getDolGlobalInt("PRODUCT_LOT_ENABLE_TRACEABILITY")?5:0', 'position'=>80),
		'scrapping_date'     => array('type'=>'date', 'label'=>'DestructionDate', 'enabled'=>'getDolGlobalInt("PRODUCT_LOT_ENABLE_TRACEABILITY")?1:0', 'visible'=>'getDolGlobalInt("PRODUCT_LOT_ENABLE_TRACEABILITY")?5:0', 'position'=>90),
		//'commissionning_date'        => array('type'=>'date', 'label'=>'FirstUseDate', 'enabled'=>'getDolGlobalInt("PRODUCT_LOT_ENABLE_TRACEABILITY", 0)', 'visible'=>5, 'position'=>100),
		'qc_frequency'        => array('type'=>'integer', 'label'=>'QCFrequency', 'enabled'=>'getDolGlobalInt("PRODUCT_LOT_ENABLE_QUALITY_CONTROL")?1:0', 'visible'=>'getDolGlobalInt("PRODUCT_LOT_ENABLE_QUALITY_CONTROL")?5:0', 'position'=>110),
		'lifetime'        => array('type'=>'integer', 'label'=>'Lifetime', 'enabled'=>'getDolGlobalInt("PRODUCT_LOT_ENABLE_QUALITY_CONTROL")?1:0', 'visible'=>'getDolGlobalInt("PRODUCT_LOT_ENABLE_QUALITY_CONTROL")?5:0', 'position'=>110),
		'eatby'         => array('type'=>'date', 'label'=>'EatByDate', 'enabled'=>'empty($conf->global->PRODUCT_DISABLE_EATBY)?1:0', 'visible'=>5, 'position'=>62),
		'model_pdf'		=> array('type' => 'varchar(255)', 'label' => 'Model pdf', 'enabled' => 1, 'visible' => 0, 'position' => 215),
		'last_main_doc' => array('type' => 'varchar(255)', 'label' => 'LastMainDoc', 'enabled' => 1, 'visible' => -2, 'position' => 310),
		'datec'         => array('type'=>'datetime', 'label'=>'DateCreation', 'enabled'=>1, 'visible'=>0, 'notnull'=>1, 'position'=>500),
		'tms'           => array('type'=>'timestamp', 'label'=>'DateModification', 'enabled'=>1, 'visible'=>-2, 'notnull'=>1, 'position'=>501),
		'fk_user_creat' => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'UserAuthor', 'enabled'=>1, 'visible'=>-2, 'notnull'=>1, 'position'=>510, 'foreignkey'=>'llx_user.rowid'),
		'fk_user_modif' => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'UserModif', 'enabled'=>1, 'visible'=>-2, 'notnull'=>-1, 'position'=>511),
		'import_key'    => array('type'=>'varchar(14)', 'label'=>'ImportId', 'enabled'=>1, 'visible'=>-2, 'notnull'=>-1, 'index'=>0, 'position'=>1000)
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
	public $eatby = '';
	public $sellby = '';
	public $eol_date = '';
	public $manufacturing_date = '';
	public $scrapping_date = '';
	//public $commissionning_date = '';
	public $qc_frequency = '';
	public $lifetime = '';
	public $datec = '';
	public $tms = '';

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
			dol_syslog(__METHOD__.' '.join(',', $this->errors), LOG_ERR);
			return -1;
		}
		// Put here code to add control on parameters values

		// Insert request
		$sql = 'INSERT INTO '.$this->db->prefix().$this->table_element.'(';
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
		$sql .= ' '.(!isset($this->entity) ? $conf->entity : $this->entity).',';
		$sql .= ' '.(!isset($this->fk_product) ? 'NULL' : $this->fk_product).',';
		$sql .= ' '.(!isset($this->batch) ? 'NULL' : "'".$this->db->escape($this->batch)."'").',';
		$sql .= ' '.(!isset($this->eatby) || dol_strlen($this->eatby) == 0 ? 'NULL' : "'".$this->db->idate($this->eatby)."'").',';
		$sql .= ' '.(!isset($this->sellby) || dol_strlen($this->sellby) == 0 ? 'NULL' : "'".$this->db->idate($this->sellby)."'").',';
		$sql .= ' '.(!isset($this->eol_date) || dol_strlen($this->eol_date) == 0 ? 'NULL' : "'".$this->db->idate($this->eol_date)."'").',';
		$sql .= ' '.(!isset($this->manufacturing_date) || dol_strlen($this->manufacturing_date) == 0 ? 'NULL' : "'".$this->db->idate($this->manufacturing_date)."'").',';
		$sql .= ' '.(!isset($this->scrapping_date) || dol_strlen($this->scrapping_date) == 0 ? 'NULL' : "'".$this->db->idate($this->scrapping_date)."'").',';
		//$sql .= ' '.(!isset($this->commissionning_date) || dol_strlen($this->commissionning_date) == 0 ? 'NULL' : "'".$this->db->idate($this->commissionning_date)."'").',';
		$sql .= ' '.(empty($this->qc_frequency) ? 'NULL' : $this->qc_frequency).',';
		$sql .= ' '.(empty($this->lifetime) ? 'NULL' : $this->lifetime).',';
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
			$this->id = $this->db->last_insert_id($this->db->prefix().$this->table_element);

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

		// $this->oldcopy should have been set by the caller of update (here properties were already modified)
		if (empty($this->oldcopy)) {
			$this->oldcopy = dol_clone($this);
		}

		// Update request
		$sql = 'UPDATE '.$this->db->prefix().$this->table_element.' SET';
		$sql .= ' entity = '.(isset($this->entity) ? $this->entity : "null").',';
		$sql .= ' fk_product = '.(isset($this->fk_product) ? $this->fk_product : "null").',';
		$sql .= ' batch = '.(isset($this->batch) ? "'".$this->db->escape($this->batch)."'" : "null").',';
		$sql .= ' eatby = '.(!isset($this->eatby) || dol_strlen($this->eatby) != 0 ? "'".$this->db->idate($this->eatby)."'" : 'null').',';
		$sql .= ' sellby = '.(!isset($this->sellby) || dol_strlen($this->sellby) != 0 ? "'".$this->db->idate($this->sellby)."'" : 'null').',';
		$sql .= ' eol_date = '.(!isset($this->eol_date) || dol_strlen($this->eol_date) != 0 ? "'".$this->db->idate($this->eol_date)."'" : 'null').',';
		$sql .= ' manufacturing_date = '.(!isset($this->manufacturing_date) || dol_strlen($this->manufacturing_date) != 0 ? "'".$this->db->idate($this->manufacturing_date)."'" : 'null').',';
		$sql .= ' scrapping_date = '.(!isset($this->scrapping_date) || dol_strlen($this->scrapping_date) != 0 ? "'".$this->db->idate($this->scrapping_date)."'" : 'null').',';
		//$sql .= ' commissionning_date = '.(!isset($this->first_use_date) || dol_strlen($this->first_use_date) != 0 ? "'".$this->db->idate($this->first_use_date)."'" : 'null').',';
		$sql .= ' qc_frequency = '.(!empty($this->qc_frequency) ? (int) $this->qc_frequency : 'null').',';
		$sql .= ' lifetime = '.(!empty($this->lifetime) ? (int) $this->lifetime : 'null').',';
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
				dol_syslog(__METHOD__.' '.join(',', $this->errors), LOG_ERR);
			}
		} else {
			$error++;
			$this->errors[] = 'Error '.$this->db->lasterror();
			dol_syslog(__METHOD__.' '.join(',', $this->errors), LOG_ERR);
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
				dol_syslog(__METHOD__.' '.join(',', $this->errors), LOG_ERR);
			}
		} else {
			$error++;
			$this->errors[] = 'Error '.$this->db->lasterror();
			dol_syslog(__METHOD__.' '.join(',', $this->errors), LOG_ERR);
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
	 *  Charge tableau des stats expedition pour le lot/numéro de série
	 *
	 * @param  int $socid Id societe
	 * @return int                     Array of stats in $this->stats_expedition, <0 if ko or >0 if ok
	 */
	public function loadStatsExpedition($socid = 0)
	{
		// phpcs:enable
		global $db, $conf, $user, $hookmanager, $action;

		$sql = "SELECT COUNT(DISTINCT exp.fk_soc) as nb_customers, COUNT(DISTINCT exp.rowid) as nb,";
		$sql .= " COUNT(ed.rowid) as nb_rows, SUM(edb.qty) as qty";
		$sql .= " FROM ".$this->db->prefix()."expeditiondet_batch as edb";
		$sql .= " INNER JOIN ".$this->db->prefix()."expeditiondet as ed ON (ed.rowid = edb.fk_expeditiondet)";
		$sql .= " INNER JOIN ".$this->db->prefix()."expedition as exp ON (exp.rowid = ed.fk_expedition)";
		//      $sql .= ", ".$this->db->prefix()."societe as s";
		if (empty($user->rights->societe->client->voir) && !$socid) {
			$sql .= ", ".$this->db->prefix()."societe_commerciaux as sc";
		}
		$sql .= " WHERE exp.entity IN (".getEntity('expedition').")";
		$sql .= " AND edb.batch = '".($this->db->escape($this->batch))."'";
		if (empty($user->rights->societe->client->voir) && !$socid) {
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
			//          if (!empty($conf->global->PRODUCT_STATS_WITH_PARENT_PROD_IF_INCDEC)) {
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
		// phpcs:enable
		global $db, $conf, $user, $hookmanager, $action;

		$sql = "SELECT COUNT(DISTINCT cf.fk_soc) as nb_customers, COUNT(DISTINCT cf.rowid) as nb,";
		$sql .= " COUNT(cfd.rowid) as nb_rows, SUM(cfdi.qty) as qty";
		$sql .= " FROM ".$this->db->prefix()."commande_fournisseur_dispatch as cfdi";
		$sql .= " INNER JOIN ".$this->db->prefix()."commande_fournisseurdet as cfd ON (cfd.rowid = cfdi.fk_commandefourndet)";
		$sql .= " INNER JOIN ".$this->db->prefix()."commande_fournisseur as cf ON (cf.rowid = cfd.fk_commande)";
		//      $sql .= ", ".$this->db->prefix()."societe as s";
		if (empty($user->rights->societe->client->voir) && !$socid) {
			$sql .= ", ".$this->db->prefix()."societe_commerciaux as sc";
		}
		$sql .= " WHERE cf.entity IN (".getEntity('expedition').")";
		$sql .= " AND cfdi.batch = '".($this->db->escape($this->batch))."'";
		if (empty($user->rights->societe->client->voir) && !$socid) {
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
			//          if (!empty($conf->global->PRODUCT_STATS_WITH_PARENT_PROD_IF_INCDEC)) {
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
		// phpcs:enable
		global $db, $conf, $user, $hookmanager, $action;

		$sql = "SELECT COUNT(DISTINCT recep.fk_soc) as nb_customers, COUNT(DISTINCT recep.rowid) as nb,";
		$sql .= " COUNT(cfdi.rowid) as nb_rows, SUM(cfdi.qty) as qty";
		$sql .= " FROM ".$this->db->prefix()."commande_fournisseur_dispatch as cfdi";
		$sql .= " INNER JOIN ".$this->db->prefix()."reception as recep ON (recep.rowid = cfdi.fk_reception)";
		//      $sql .= ", ".$this->db->prefix()."societe as s";
		if (empty($user->rights->societe->client->voir) && !$socid) {
			$sql .= ", ".$this->db->prefix()."societe_commerciaux as sc";
		}
		$sql .= " WHERE recep.entity IN (".getEntity('reception').")";
		$sql .= " AND cfdi.batch = '".($this->db->escape($this->batch))."'";
		if (empty($user->rights->societe->client->voir) && !$socid) {
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
			//          if (!empty($conf->global->PRODUCT_STATS_WITH_PARENT_PROD_IF_INCDEC)) {
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
		// phpcs:enable
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
			if (empty($user->rights->societe->client->voir) && !$socid) {
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
		global $langs;

		//$langs->load('stocks');

		return '';
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

		$langs->loadLangs(['stocks', 'productbatch']);

		$option = $params['option'] ?? '';

		$datas = [];
		$datas['picto'] = img_picto('', $this->picto).' <u class="paddingrightonly">'.$langs->trans("Batch").'</u>';
		//$datas['divopen'] = '<div width="100%">';
		$datas['batch'] = '<br><b>'.$langs->trans('Batch').':</b> '.$this->batch;
		if ($this->eatby && empty($conf->global->PRODUCT_DISABLE_EATBY)) {
			$datas['eatby'] = '<br><b>'.$langs->trans('EatByDate').':</b> '.dol_print_date($this->db->jdate($this->eatby), 'day');
		}
		if ($this->sellby && empty($conf->global->PRODUCT_DISABLE_SELLBY)) {
			$datas['sellby'] = '<br><b>'.$langs->trans('SellByDate').':</b> '.dol_print_date($this->db->jdate($this->sellby), 'day');
		}
		//$datas['divclose'] = '</div>';

		return $datas;
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
		global $langs, $conf, $hookmanager;

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
			if (!empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) {
				$label = $langs->trans("ShowMyObject");
				$linkclose .= ' alt="'.dol_escape_htmltag($label, 1).'"';
			}
			$linkclose .= ($label ? ' title="'.dol_escape_htmltag($label, 1).'"' :  ' title="tocomplete"');
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
	 * @return void
	 */
	public function initAsSpecimen()
	{
		global $conf;

		// Initialise parametres
		$this->id = 0;
		$this->ref = 'SPECIMEN';
		$this->specimen = 1;

		$this->entity = $conf->entity;
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
		global $conf, $user, $langs;

		$langs->loadLangs(array('stocks', 'productbatch', "products"));
		$outputlangs->loadLangs(array('stocks', 'productbatch', "products"));

		// Positionne le modele sur le nom du modele a utiliser
		if (!dol_strlen($modele)) {
			$modele = '';

			if (!empty($this->model_pdf)) {
				$modele = $this->model_pdf;
			} elseif (!empty($conf->global->PRODUCT_BATCH_ADDON_PDF)) {
				$modele = $conf->global->PRODUCT_BATCH_ADDON_PDF;
			}
		}

		$modelpath = "core/modules/product_batch/doc/";

		return $this->commonGenerateDocument($modelpath, $modele, $outputlangs, $hidedetails, $hidedesc, $hideref);
	}
}
