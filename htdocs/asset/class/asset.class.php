<?php
/* Copyright (C) 2017  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2018  Alexandre Spangaro  <aspangaro@open-dsi.fr>
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
 * \file        asset/class/asset.class.php
 * \ingroup     asset
 * \brief       This file is a CRUD class file for Asset (Create/Read/Update/Delete)
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';

/**
 * Class for Asset
 */
class Asset extends CommonObject
{
	/**
	 * @var string ID of module.
	 */
	public $module = 'asset';

	/**
	 * @var string ID to identify managed object.
	 */
	public $element = 'asset';

	/**
	 * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management.
	 */
	public $table_element = 'asset';

	/**
	 * @var int  Does this object support multicompany module ?
	 * 0=No test on entity, 1=Test with field entity, 'field@table'=Test with link by field@table
	 */
	public $ismultientitymanaged = 1;

	/**
	 * @var int  Does object support extrafields ? 0=No, 1=Yes
	 */
	public $isextrafieldmanaged = 1;

	/**
	 * @var string String with name of icon for asset. Must be the part after the 'object_' into object_asset.png
	 */
	public $picto = 'asset';

	const STATUS_DRAFT = 0;
	const STATUS_VALIDATED = 1;
	const STATUS_CANCELED = 9;

	/**
	 *  'type' field format ('integer', 'integer:ObjectClass:PathToClass[:AddCreateButtonOrNot[:Filter[:Sortfield]]]', 'sellist:TableName:LabelFieldName[:KeyFieldName[:KeyFieldParent[:Filter[:Sortfield]]]]', 'varchar(x)', 'double(24,8)', 'real', 'price', 'text', 'text:none', 'html', 'date', 'datetime', 'timestamp', 'duration', 'mail', 'phone', 'url', 'password')
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
	 *  'isameasure' must be set to 1 or 2 if field can be used for measure. Field type must be summable like integer or double(24,8). Use 1 in most cases, or 2 if you don't want to see the column total into list (for example for percentage)
	 *  'css' and 'cssview' and 'csslist' is the CSS style to use on field. 'css' is used in creation and update. 'cssview' is used in view mode. 'csslist' is used for columns in lists. For example: 'css'=>'minwidth300 maxwidth500 widthcentpercentminusx', 'cssview'=>'wordbreak', 'csslist'=>'tdoverflowmax200'
	 *  'help' is a 'TranslationString' to use to show a tooltip on field. You can also use 'TranslationString:keyfortooltiponlick' for a tooltip on click.
	 *  'showoncombobox' if value of the field must be visible into the label of the combobox that list record
	 *  'disabled' is 1 if we want to have the field locked by a 'disabled' attribute. In most cases, this is never set into the definition of $fields into class, but is set dynamically by some part of code.
	 *  'arrayofkeyval' to set a list of values if type is a list of predefined values. For example: array("0"=>"Draft","1"=>"Active","-1"=>"Cancel"). Note that type can be 'integer' or 'varchar'
	 *  'autofocusoncreate' to have field having the focus on a create form. Only 1 field should have this property set to 1.
	 *  'comment' is not used. You can store here any text of your choice. It is not used by application.
	 *	'validate' is 1 if need to validate with $this->validateField()
	 *  'copytoclipboard' is 1 or 2 to allow to add a picto to copy value into clipboard (1=picto after label, 2=picto after value)
	 *
	 *  Note: To have value dynamic, you can set value to 0 in definition and edit the value on the fly into the constructor.
	 */

	/**
	 * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields=array(
		'rowid' => array('type'=>'integer', 'label'=>'TechnicalID', 'enabled'=>'1', 'position'=>1, 'notnull'=>1, 'visible'=>0, 'noteditable'=>'1', 'index'=>1, 'css'=>'left', 'comment'=>"Id"),
		'ref' => array('type'=>'varchar(128)', 'label'=>'Ref', 'enabled'=>'1', 'position'=>20, 'notnull'=>1, 'visible'=>1, 'noteditable'=>'0', 'default'=>'(PROV)', 'index'=>1, 'searchall'=>1, 'showoncombobox'=>'1', 'validate'=>'1', 'comment'=>"Reference of object"),
		'label' => array('type'=>'varchar(255)', 'label'=>'Label', 'enabled'=>'1', 'position'=>30, 'notnull'=>1, 'visible'=>1, 'searchall'=>1, 'css'=>'minwidth300', 'cssview'=>'wordbreak', 'showoncombobox'=>'2', 'validate'=>'1',),
		'fk_asset_model' => array('type'=>'integer:AssetModel:asset/class/assetmodel.class.php:1:status=1 AND entity IN (__SHARED_ENTITIES__)', 'label'=>'AssetModel', 'enabled'=>'1', 'position'=>40, 'notnull'=>1, 'visible'=>1, 'index'=>1, 'validate'=>'1',),
		'acquisition_value_ht' => array('type'=>'price', 'label'=>'AssetAcquisitionValueHT', 'enabled'=>'1', 'position'=>50, 'notnull'=>1, 'visible'=>1, 'default'=>'0', 'isameasure'=>'1', 'validate'=>'1',),
		'recovered_vat' => array('type'=>'price', 'label'=>'AssetRecoveredVAT', 'enabled'=>'1', 'position'=>60, 'notnull'=>0, 'visible'=>1, 'default'=>'0', 'isameasure'=>'1', 'validate'=>'1',),
		'date_acquisition' => array('type'=>'date', 'label'=>'AssetDateAcquisition', 'enabled'=>'1', 'position'=>70, 'notnull'=>1, 'visible'=>1,),
		'date_start' => array('type'=>'date', 'label'=>'AssetDateStart', 'enabled'=>'1', 'position'=>80, 'notnull'=>0, 'visible'=>-1,),
		'qty' => array('type'=>'real', 'label'=>'Qty', 'enabled'=>'1', 'position'=>90, 'notnull'=>1, 'visible'=>1, 'default'=>'1', 'isameasure'=>'1', 'css'=>'maxwidth75imp', 'validate'=>'1',),
		'acquisition_type' => array('type'=>'smallint', 'label'=>'AssetAcquisitionType', 'enabled'=>'1', 'position'=>100, 'notnull'=>1, 'visible'=>1, 'arrayofkeyval'=>array('0'=>'AssetAcquisitionTypeNew', '1'=>'AssetAcquisitionTypeOccasion'), 'validate'=>'1',),
		'asset_type' => array('type'=>'smallint', 'label'=>'AssetType', 'enabled'=>'1', 'position'=>110, 'notnull'=>1, 'visible'=>1, 'arrayofkeyval'=>array('0'=>'AssetTypeIntangible', '1'=>'AssetTypeTangible', '2'=>'AssetTypeInProgress', '3'=>'AssetTypeFinancial'), 'validate'=>'1',),
		'not_depreciated' => array('type'=>'boolean', 'label'=>'AssetNotDepreciated', 'enabled'=>'1', 'position'=>120, 'notnull'=>0, 'visible'=>1, 'validate'=>'1',),
		'note_public' => array('type'=>'html', 'label'=>'NotePublic', 'enabled'=>'1', 'position'=>300, 'notnull'=>0, 'visible'=>0, 'validate'=>'1',),
		'note_private' => array('type'=>'html', 'label'=>'NotePrivate', 'enabled'=>'1', 'position'=>301, 'notnull'=>0, 'visible'=>0, 'validate'=>'1',),
		'date_creation' => array('type'=>'datetime', 'label'=>'DateCreation', 'enabled'=>'1', 'position'=>500, 'notnull'=>1, 'visible'=>-2,),
		'tms' => array('type'=>'timestamp', 'label'=>'DateModification', 'enabled'=>'1', 'position'=>501, 'notnull'=>0, 'visible'=>-2,),
		'fk_user_creat' => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'UserAuthor', 'enabled'=>'1', 'position'=>510, 'notnull'=>1, 'visible'=>-2, 'foreignkey'=>'user.rowid',),
		'fk_user_modif' => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'UserModif', 'enabled'=>'1', 'position'=>511, 'notnull'=>-1, 'visible'=>-2,),
		'last_main_doc' => array('type'=>'varchar(255)', 'label'=>'LastMainDoc', 'enabled'=>'1', 'position'=>600, 'notnull'=>0, 'visible'=>0,),
		'import_key' => array('type'=>'varchar(14)', 'label'=>'ImportId', 'enabled'=>'1', 'position'=>1000, 'notnull'=>-1, 'visible'=>-2,),
		'model_pdf' => array('type'=>'varchar(255)', 'label'=>'Model pdf', 'enabled'=>'1', 'position'=>1010, 'notnull'=>-1, 'visible'=>0,),
		'status' => array('type'=>'smallint', 'label'=>'Status', 'enabled'=>'1', 'position'=>1000, 'notnull'=>1, 'default'=>'0', 'visible'=>2, 'index'=>1, 'arrayofkeyval'=>array('0'=>'Draft', '1'=>'Validated', '9'=>'Canceled'), 'validate'=>'1',),
	);
	public $rowid;
	public $ref;
	public $label;
	public $fk_asset_model;
	public $acquisition_value_ht;
	public $recovered_vat;
	public $date_acquisition;
	public $date_start;
	public $qty;
	public $acquisition_type;
	public $asset_type;
	public $not_depreciated;
	public $note_public;
	public $note_private;
	public $date_creation;
	public $tms;
	public $fk_user_creat;
	public $fk_user_modif;
	public $last_main_doc;
	public $import_key;
	public $model_pdf;
	public $status;

	// /**
	//  * @var string    Field with ID of parent key if this object has a parent
	//  */
	// public $fk_element = 'fk_asset';
	// /**
	//  * @var array	List of child tables. To test if we can delete object.
	//  */
	// protected $childtables = array();
	// /**
	//  * @var array    List of child tables. To know object to delete on cascade.
	//  *               If name matches '@ClassNAme:FilePathClass;ParentFkFieldName' it will
	//  *               call method deleteByParentField(parentId, ParentFkFieldName) to fetch and delete child object
	//  */
	// protected $childtablesoncascade = array('asset_assetdet');

	/**
	 * @var AssetDepreciationOptions	Used for computed fields of depreciation options class.
	 */
	public $asset_depreciation_options;
	/**
	 * @var array	List of depreciation lines for each mode (sort by depreciation date).
	 */
	public $depreciation_lines = array();

	/**
	 * Constructor
	 *
	 * @param DoliDb $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		global $conf, $langs;

		$this->db = $db;

		if (empty($conf->global->MAIN_SHOW_TECHNICAL_ID) && isset($this->fields['rowid'])) {
			$this->fields['rowid']['visible'] = 0;
		}
		if (empty($conf->multicompany->enabled) && isset($this->fields['entity'])) {
			$this->fields['entity']['enabled'] = 0;
		}

		// Unset fields that are disabled
		foreach ($this->fields as $key => $val) {
			if (isset($val['enabled']) && empty($val['enabled'])) {
				unset($this->fields[$key]);
			}
		}

		// Translate some data of arrayofkeyval
		if (is_object($langs)) {
			foreach ($this->fields as $key => $val) {
				if (!empty($val['arrayofkeyval']) && is_array($val['arrayofkeyval'])) {
					foreach ($val['arrayofkeyval'] as $key2 => $val2) {
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
		if (!isset($this->date_start) || $this->date_start === "") $this->date_start = $this->date_acquisition;
		$result = $result_create = $this->createCommon($user, $notrigger);
		if ($result > 0) $result = $this->setDataFromAssetModel($user, $notrigger);

//		$result = $this->validate($user, $notrigger);

		return $result > 0 ? $result_create : $result;
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
		if ($result > 0 && !empty($object->table_element_line)) {
			$object->fetchLines();
		}

		// get lines so they will be clone
		//foreach($this->lines as $line)
		//	$line->fetch_optionals();

		// Reset some properties
		unset($object->id);
		unset($object->fk_user_creat);
		unset($object->import_key);

		// Clear fields
		if (property_exists($object, 'ref')) {
			$object->ref = empty($this->fields['ref']['default']) ? "Copy_Of_".$object->ref : $this->fields['ref']['default'];
		}
		if (property_exists($object, 'label')) {
			$object->label = empty($this->fields['label']['default']) ? $langs->trans("CopyOf")." ".$object->label : $this->fields['label']['default'];
		}
		if (property_exists($object, 'status')) {
			$object->status = self::STATUS_DRAFT;
		}
		if (property_exists($object, 'date_creation')) {
			$object->date_creation = dol_now();
		}
		if (property_exists($object, 'date_modification')) {
			$object->date_modification = null;
		}
		// ...
		// Clear extrafields that are unique
		if (is_array($object->array_options) && count($object->array_options) > 0) {
			$extrafields->fetch_name_optionals_label($this->table_element);
			foreach ($object->array_options as $key => $option) {
				$shortkey = preg_replace('/options_/', '', $key);
				if (!empty($extrafields->attributes[$this->table_element]['unique'][$shortkey])) {
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

		if (!$error) {
			// copy internal contacts
			if ($this->copy_linked_contact($object, 'internal') < 0) {
				$error++;
			}
		}

		if (!$error) {
			// copy external contacts if same company
			if (property_exists($this, 'fk_soc') && $this->fk_soc == $object->socid) {
				if ($this->copy_linked_contact($object, 'external') < 0) {
					$error++;
				}
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
		if ($result > 0 && !empty($this->table_element_line)) {
			$this->fetchLines();
		}
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

		return 1;
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

		$sql = "SELECT ";
		$sql .= $this->getFieldList('t');
		$sql .= " FROM ".MAIN_DB_PREFIX.$this->table_element." as t";
		if (isset($this->ismultientitymanaged) && $this->ismultientitymanaged == 1) {
			$sql .= " WHERE t.entity IN (".getEntity($this->table_element).")";
		} else {
			$sql .= " WHERE 1 = 1";
		}
		// Manage filter
		$sqlwhere = array();
		if (count($filter) > 0) {
			foreach ($filter as $key => $value) {
				if ($key == 't.rowid') {
					$sqlwhere[] = $key." = ".((int) $value);
				} elseif (in_array($this->fields[$key]['type'], array('date', 'datetime', 'timestamp'))) {
					$sqlwhere[] = $key." = '".$this->db->idate($value)."'";
				} elseif ($key == 'customsql') {
					$sqlwhere[] = $value;
				} elseif (strpos($value, '%') === false) {
					$sqlwhere[] = $key." IN (".$this->db->sanitize($this->db->escape($value)).")";
				} else {
					$sqlwhere[] = $key." LIKE '%".$this->db->escape($value)."%'";
				}
			}
		}
		if (count($sqlwhere) > 0) {
			$sql .= " AND (".implode(" ".$filtermode." ", $sqlwhere).")";
		}

		if (!empty($sortfield)) {
			$sql .= $this->db->order($sortfield, $sortorder);
		}
		if (!empty($limit)) {
			$sql .= $this->db->plimit($limit, $offset);
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < ($limit ? min($limit, $num) : $num)) {
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
		if (!isset($this->date_start) || $this->date_start === "") $this->date_start = $this->date_acquisition;
		$result = $this->updateCommon($user, $notrigger);
		if ($result > 0 && $this->fk_asset_model != $this->oldcopy->fk_asset_model) $result = $this->setDataFromAssetModel($user, $notrigger);

		return $result;
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
	 * Set asset model
	 *
	 * @param  User $user      User that creates
	 * @param  bool $notrigger false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, Id of created object if OK
	 */
	public function setDataFromAssetModel(User $user, $notrigger = false)
	{
		global $langs;
		$langs->load('assets');

		// Clean parameters
		$this->id = $this->id > 0 ? $this->id : 0;
		$this->fk_asset_model = $this->fk_asset_model > 0 ? $this->fk_asset_model : 0;

		// Check parameters
		$error = 0;
		if (empty($this->id)) {
			$this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Asset") . ' (' . $langs->transnoentitiesnoconv("TechnicalID") . ')');
			$error++;
		}
		if (empty($this->fk_asset_model)) {
			$this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("AssetModel") . ' (' . $langs->transnoentitiesnoconv("TechnicalID") . ')');
			$error++;
		}
		if ($error) {
			return -1;
		}

		$this->db->begin();

		// Get depreciation options
		//---------------------------
		require_once DOL_DOCUMENT_ROOT . '/asset/class/assetdepreciationoptions.class.php';
		$options_model = new AssetDepreciationOptions($this->db);
		$result = $options_model->fetchDeprecationOptions(0, $this->fk_asset_model);
		if ($result < 0) {
			$this->error = $options_model->error;
			$this->errors = $options_model->errors;
			$error++;
		} elseif ($result > 0) {
			$options = new AssetDepreciationOptions($this->db);
			$result = $options->fetchDeprecationOptions($this->id);
			if ($result < 0) {
				$this->error = $options->error;
				$this->errors = $options->errors;
				$error++;
			}

			if (!$error) {
				foreach ($options_model->deprecation_options as $mode_key => $fields) {
					foreach ($fields as $field_key => $value) {
						$options->deprecation_options[$mode_key][$field_key] = $value;
					}
				}

				$result = $options->updateDeprecationOptions($user, $this->id, 0, $notrigger);
				if ($result < 0) {
					$this->error = $options->error;
					$this->errors = $options->errors;
					$error++;
				}
			}
		}

		// Get accountancy codes
		//---------------------------
		if (!$error) {
			require_once DOL_DOCUMENT_ROOT . '/asset/class/assetaccountancycodes.class.php';
			$accountancy_codes_model = new AssetAccountancyCodes($this->db);
			$result = $accountancy_codes_model->fetchAccountancyCodes(0, $this->fk_asset_model);
			if ($result < 0) {
				$this->error = $accountancy_codes_model->error;
				$this->errors = $accountancy_codes_model->errors;
				$error++;
			} elseif ($result > 0) {
				$accountancy_codes = new AssetAccountancyCodes($this->db);
				$result = $accountancy_codes->fetchAccountancyCodes($this->id);
				if ($result < 0) {
					$this->error = $accountancy_codes->error;
					$this->errors = $accountancy_codes->errors;
					$error++;
				}

				if (!$error) {
					foreach ($accountancy_codes_model->accountancy_codes as $mode_key => $fields) {
						foreach ($fields as $field_key => $value) {
							$accountancy_codes->accountancy_codes[$mode_key][$field_key] = $value;
						}
					}

					$result = $accountancy_codes->updateAccountancyCodes($user, $this->id, 0, $notrigger);
					if ($result < 0) {
						$this->error = $accountancy_codes->error;
						$this->errors = $accountancy_codes->errors;
						$error++;
					}
				}
			}
		}

		if ($error) {
			$this->db->rollback();
			return -1;
		} else {
			$this->db->commit();
			return 1;
		}
	}

	/**
	 * Fetch depreciation lines for each mode in $this->depreciation_lines (sort by depreciation date)
	 *
	 * @param	int		$begin_period		Begin period filter
	 * @param	int		$end_period			End period filter
	 * @param	bool	$only_save			Return only saved depreciation lines
	 * @param	bool	$only_new			Return only new depreciation lines
	 * @return	int							<0 if KO, Id of created object if OK
	 */
	public function fetchDepreciationLines($begin_period = null, $end_period = null, $only_save = false, $only_new = false)
	{
		global $langs;
		$langs->load('assets');
		$this->depreciation_lines = array();

		// Clean parameters
		$this->id = $this->id > 0 ? $this->id : 0;
		if ($only_save && $only_new) $only_new = false;

		// Check parameters
		$error = 0;
		if (empty($this->id)) {
			$this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Asset") . ' (' . $langs->transnoentitiesnoconv("TechnicalID") . ')');
			$error++;
		}
		if ($error) {
			return -1;
		}

		// Get depreciation options
		//---------------------------
		require_once DOL_DOCUMENT_ROOT . '/asset/class/assetdepreciationoptions.class.php';
		$options = new AssetDepreciationOptions($this->db);
		$result = $options->fetchDeprecationOptions($this->id);
		if ($result < 0) {
			$this->error = $options->error;
			$this->errors = $options->errors;
			return -1;
		}

		$depreciation_lines = array();
		foreach ($options->deprecation_options as $mode_key => $fields) {
			$lines = array();

			// Get saved lines for period provided
			if (!$only_new) {
				$sql = "SELECT rowid, ref, depreciation_date, depreciation_ht, cumulative_depreciation_ht";
				$sql .= " FROM " . MAIN_DB_PREFIX . "asset_depreciation";
				$sql .= " WHERE fk_asset = " . $this->id;
				$sql .= " AND depreciation_mode = '" . $this->db->escape($mode_key) . "'";
				if (isset($begin_period)) $sql .= " AND depreciation_date >= '" . $this->db->idate($begin_period) . "'";
				if (isset($end_period)) $sql .= " AND depreciation_date <= '" . $this->db->idate($end_period) . "'";
				$sql .= " ORDER BY depreciation_date ASC";

				$resql = $this->db->query($sql);
				if (!$resql) {
					$this->errors[] = $langs->trans('AssetErrorFetchDepreciationLinesForMode', $mode_key) . ': ' . $this->db->lasterror();
					return -1;
				}
				while ($obj = $this->db->fetch_object($resql)) {
					$depreciation_date = $this->db->jdate($obj->depreciation_date);
					$lines[$depreciation_date] = array(
						'id' => $obj->rowid,
						'type' => 1,
						'ref' => $obj->ref,
						'depreciation_date' => $depreciation_date,
						'depreciation_ht' => $obj->depreciation_ht,
						'cumulative_depreciation_ht' => $obj->cumulative_depreciation_ht,
					);
				}
			}

			if (!$only_save) {
				// Get fiscal period
				require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
				require_once DOL_DOCUMENT_ROOT . '/core/lib/accounting.lib.php';
				$dates = getDefaultDatesForTransfer();
				$fiscal_period_start = $dates['date_start'];
				$fiscal_period_end = $dates['date_end'];
				if (empty($fiscal_period_start) || empty($fiscal_period_end)) {
					$pastmonthyear = $dates['pastmonthyear'];
					$pastmonth = $dates['pastmonth'];
					$fiscal_period_start = dol_get_first_day($pastmonthyear, $pastmonth, false);
					$fiscal_period_end = dol_get_last_day($pastmonthyear, $pastmonth, false);
				}

				// Get last depreciation date saved
				$sql = "SELECT depreciation_date, cumulative_depreciation_ht";
				$sql .= " FROM " . MAIN_DB_PREFIX . "asset_depreciation";
				$sql .= " WHERE fk_asset = " . $this->id;
				$sql .= " AND depreciation_mode = '" . $this->db->escape($mode_key) . "'";
				$sql .= " ORDER BY depreciation_date DESC";
				$sql .= " LIMIT 1";
				$resql = $this->db->query($sql);
				if (!$resql) {
					$this->errors[] = $langs->trans('AssetErrorFetchMaxDepreciationDateForMode', $mode_key) . ': ' . $this->db->lasterror();
					return -1;
				}
				$depreciation_date = '';
				$cumulative_depreciation_ht = '';
				if ($obj = $this->db->fetch_object($resql)) {
					$depreciation_date = $this->db->jdate($obj->depreciation_date);
					$cumulative_depreciation_ht = $obj->cumulative_depreciation_ht;
				}

				// Get depreciation period
				$depreciation_date_start = $this->date_start > $this->date_acquisition ? $this->date_start : $this->date_acquisition;
				$depreciation_date_end = dol_time_plus_duree($depreciation_date_start, $fields['duration'], $fields['duration_type'] == 1 ? 'm' : ($fields['duration_type'] == 2 ? 'd' : 'y'));
				$depreciation_amount = $fields['total_amount_last_depreciation_ht'] > 0 ? $fields['total_amount_last_depreciation_ht'] : $fields['amount_base_depreciation_ht'];
				$depreciation_period_amount = $depreciation_amount;
				$start_date = $depreciation_date_start;
				$finish_date = $depreciation_date_end;

				if ($depreciation_date_start < $fiscal_period_start) {
					if ($depreciation_date === "" && is_numeric($fields['depreciation_reversal_date'])) {
						if ($fields['depreciation_reversal_date'] < $fiscal_period_start) {
							$this->errors[] = $langs->trans('AssetErrorReversalDateNotGreaterThanCurrentBeginFiscalDateForMode', $mode_key);
							return -1;
						}

						if (empty($fields['depreciation_reversal_amount_ht'])) {
							$this->errors[] = $langs->trans('AssetErrorReversalAmountNotProvidedForMode', $mode_key);
							return -1;
						}

						$start_date = $fields['depreciation_reversal_date'];
						$cumulative_depreciation_ht = $fields['depreciation_reversal_amount_ht'];
						$depreciation_period_amount = $depreciation_amount - $cumulative_depreciation_ht;
						$lines[$start_date] = array(
							'id' => 0,
							'type' => 0,
							'ref' => $langs->trans('AssetDepreciationReversal'),
							'depreciation_date' => $start_date,
							'depreciation_ht' => $cumulative_depreciation_ht,
							'cumulative_depreciation_ht' => $cumulative_depreciation_ht,
						);
					} else {
						$this->errors[] = $langs->trans('AssetErrorReversalDateNotProvidedForMode', $mode_key);
						return -1;
					}
				}

				$nb_days_in_year = 360;
				$period_amount = (double)price2num($depreciation_period_amount * ($fields['duration_type'] == 1 ? 12 : ($fields['duration_type'] == 2 ? $nb_days_in_year : 1)) / $fields['duration'], 'MT');

				$first_period_found = false;
				$first_period_date = isset($begin_period) && $begin_period > $fiscal_period_start ? $begin_period : $fiscal_period_start;
				// Loop security
				$idx_loop = 0;
				$max_loop = $fields['duration'] + 2;
				do {
					// Loop security
					$idx_loop++;
					if ($idx_loop > $max_loop) break;

					if ($first_period_date <= $start_date || $first_period_found) {
						$first_period_found = true;

						$period_begin = dol_print_date($fiscal_period_start, '%Y');
						$period_end = dol_print_date($fiscal_period_end, '%Y');
						$ref = $period_begin . ($period_begin != $period_end ? ' - ' . $period_end : '');

						$begin_date = $fiscal_period_start < $start_date && $start_date <= $fiscal_period_end ? $start_date : $fiscal_period_start;
						$end_date = $fiscal_period_start < $finish_date && $finish_date <= $fiscal_period_end ? $finish_date : $fiscal_period_end;
						$nb_days = min($nb_days_in_year, ($end_date - $begin_date) / 86400); // 86400s = 1d
						$depreciation_ht = (double)price2num($period_amount * $nb_days / $nb_days_in_year, 'MT');
						if ($fiscal_period_start <= $depreciation_date_end && $depreciation_date_end <= $fiscal_period_end) { // last period
							$depreciation_ht = (double)price2num($depreciation_amount - $cumulative_depreciation_ht, 'MT');
							$cumulative_depreciation_ht = $depreciation_amount;
						} else {
							$cumulative_depreciation_ht += $depreciation_ht;
						}

						$lines[$fiscal_period_end] = array(
							'id' => 0,
							'type' => 2,
							'ref' => $ref,
							'depreciation_date' => $fiscal_period_end,
							'depreciation_ht' => $depreciation_ht,
							'cumulative_depreciation_ht' => $cumulative_depreciation_ht,
						);
					}

					// Next fiscal period (+1 year)
					$fiscal_period_start = dol_time_plus_duree($fiscal_period_end, 1, 'd');
					$fiscal_period_end = dol_time_plus_duree(dol_time_plus_duree($fiscal_period_start, 1, 'y'), -1, 'd');
					$last_period_date = isset($end_period) && $end_period < $depreciation_date_end ? $end_period : $depreciation_date_end;
				} while ($fiscal_period_start < $last_period_date);

				ksort($lines, SORT_NUMERIC);
				$depreciation_lines[$mode_key] = $lines;
			}
		}

		$this->depreciation_lines = $depreciation_lines;
		return 1;
	}

	/**
	 *	Validate object
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
		if ($this->status == self::STATUS_VALIDATED) {
			dol_syslog(get_class($this)."::validate action abandonned: already validated", LOG_WARNING);
			return 0;
		}

		$now = dol_now();

		$this->db->begin();

		// Define new ref
		if (!$error && (preg_match('/^[\(]?PROV/i', $this->ref) || empty($this->ref))) { // empty should not happened, but when it occurs, the test save life
			$num = $this->getNextNumRef();
		} else {
			$num = $this->ref;
		}
		$this->newref = $num;

		if (!empty($num)) {
			// Validate
			$sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element;
			$sql .= " SET ref = '".$this->db->escape($num)."',";
			$sql .= " status = ".self::STATUS_VALIDATED;
			if (!empty($this->fields['date_validation'])) {
				$sql .= ", date_validation = '".$this->db->idate($now)."'";
			}
			if (!empty($this->fields['fk_user_valid'])) {
				$sql .= ", fk_user_valid = ".((int) $user->id);
			}
			$sql .= " WHERE rowid = ".((int) $this->id);

			dol_syslog(get_class($this)."::validate()", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				dol_print_error($this->db);
				$this->error = $this->db->lasterror();
				$error++;
			}

			if (!$error && !$notrigger) {
				// Call trigger
				$result = $this->call_trigger('ASSET_VALIDATE', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}
		}

		if (!$error) {
			$this->oldref = $this->ref;

			// Rename directory if dir was a temporary ref
			if (preg_match('/^[\(]?PROV/i', $this->ref)) {
				// Now we rename also files into index
				$sql = 'UPDATE '.MAIN_DB_PREFIX."ecm_files set filename = CONCAT('".$this->db->escape($this->newref)."', SUBSTR(filename, ".(strlen($this->ref) + 1).")), filepath = 'asset/".$this->db->escape($this->newref)."'";
				$sql .= " WHERE filename LIKE '".$this->db->escape($this->ref)."%' AND filepath = 'asset/".$this->db->escape($this->ref)."' and entity = ".$conf->entity;
				$resql = $this->db->query($sql);
				if (!$resql) {
					$error++; $this->error = $this->db->lasterror();
				}

				// We rename directory ($this->ref = old ref, $num = new ref) in order not to lose the attachments
				$oldref = dol_sanitizeFileName($this->ref);
				$newref = dol_sanitizeFileName($num);
				$dirsource = $conf->asset->dir_output.'/asset/'.$oldref;
				$dirdest = $conf->asset->dir_output.'/asset/'.$newref;
				if (!$error && file_exists($dirsource)) {
					dol_syslog(get_class($this)."::validate() rename dir ".$dirsource." into ".$dirdest);

					if (@rename($dirsource, $dirdest)) {
						dol_syslog("Rename ok");
						// Rename docs starting with $oldref with $newref
						$listoffiles = dol_dir_list($conf->asset->dir_output.'/asset/'.$newref, 'files', 1, '^'.preg_quote($oldref, '/'));
						foreach ($listoffiles as $fileentry) {
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
		if (!$error) {
			$this->ref = $num;
			$this->status = self::STATUS_VALIDATED;
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
	 *	Set draft status
	 *
	 *	@param	User	$user			Object user that modify
	 *  @param	int		$notrigger		1=Does not execute triggers, 0=Execute triggers
	 *	@return	int						<0 if KO, >0 if OK
	 */
	public function setDraft($user, $notrigger = 0)
	{
		// Protection
		if ($this->status <= self::STATUS_DRAFT) {
			return 0;
		}

		/*if (! ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->asset->write))
		 || (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->asset->asset_advance->validate))))
		 {
		 $this->error='Permission denied';
		 return -1;
		 }*/

		return $this->setStatusCommon($user, self::STATUS_DRAFT, $notrigger, 'ASSET_UNVALIDATE');
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
		if ($this->status != self::STATUS_VALIDATED) {
			return 0;
		}

		/*if (! ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->asset->write))
		 || (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->asset->asset_advance->validate))))
		 {
		 $this->error='Permission denied';
		 return -1;
		 }*/

		return $this->setStatusCommon($user, self::STATUS_CANCELED, $notrigger, 'ASSET_CANCEL');
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
		if ($this->status != self::STATUS_CANCELED) {
			return 0;
		}

		/*if (! ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->asset->write))
		 || (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->asset->asset_advance->validate))))
		 {
		 $this->error='Permission denied';
		 return -1;
		 }*/

		return $this->setStatusCommon($user, self::STATUS_VALIDATED, $notrigger, 'ASSET_REOPEN');
	}

	/**
	 *  Return a link to the object card (with optionaly the picto)
	 *
	 *  @param  int     $withpicto                  Include picto in link (0=No picto, 1=Include picto into link, 2=Only picto)
	 *  @param  string  $option                     On what the link point to ('nolink', ...)
	 *  @param  int     $notooltip                  1=Disable tooltip
	 *  @param  string  $morecss                    Add more css on link
	 *  @param  int     $save_lastsearch_value      -1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 *  @return	string                              String with URL
	 */
	public function getNomUrl($withpicto = 0, $option = '', $notooltip = 0, $morecss = '', $save_lastsearch_value = -1)
	{
		global $conf, $langs, $hookmanager;

		if (!empty($conf->dol_no_mouse_hover)) {
			$notooltip = 1; // Force disable tooltips
		}

		$result = '';

		$label = img_picto('', $this->picto).' <u>'.$langs->trans("Asset").'</u>';
		if (isset($this->status)) {
			$label .= ' '.$this->getLibStatut(5);
		}
		$label .= '<br>';
		$label .= '<b>'.$langs->trans('Ref').':</b> '.$this->ref;

		$url = dol_buildpath('/asset/card.php', 1).'?id='.$this->id;

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
				$label = $langs->trans("ShowAsset");
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

		if (empty($this->showphoto_on_popup)) {
			if ($withpicto) {
				$result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
			}
		} else {
			if ($withpicto) {
				require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

				list($class, $module) = explode('@', $this->picto);
				$upload_dir = $conf->$module->multidir_output[$conf->entity]."/$class/".dol_sanitizeFileName($this->ref);
				$filearray = dol_dir_list($upload_dir, "files");
				$filename = $filearray[0]['name'];
				if (!empty($filename)) {
					$pospoint = strpos($filearray[0]['name'], '.');

					$pathtophoto = $class.'/'.$this->ref.'/thumbs/'.substr($filename, 0, $pospoint).'_mini'.substr($filename, $pospoint);
					if (empty($conf->global->{strtoupper($module.'_'.$class).'_FORMATLISTPHOTOSASUSERS'})) {
						$result .= '<div class="floatleft inline-block valignmiddle divphotoref"><div class="photoref"><img class="photo'.$module.'" alt="No photo" border="0" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart='.$module.'&entity='.$conf->entity.'&file='.urlencode($pathtophoto).'"></div></div>';
					} else {
						$result .= '<div class="floatleft inline-block valignmiddle divphotoref"><img class="photouserphoto userphoto" alt="No photo" border="0" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart='.$module.'&entity='.$conf->entity.'&file='.urlencode($pathtophoto).'"></div>';
					}

					$result .= '</div>';
				} else {
					$result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
				}
			}
		}

		if ($withpicto != 2) {
			$result .= $this->ref;
		}

		$result .= $linkend;
		//if ($withpicto != 2) $result.=(($addlabel && $this->label) ? $sep . dol_trunc($this->label, ($addlabel > 1 ? $addlabel : 0)) : '');

		global $action, $hookmanager;
		$hookmanager->initHooks(array('assetdao'));
		$parameters = array('id'=>$this->id, 'getnomurl'=>$result);
		$reshook = $hookmanager->executeHooks('getNomUrl', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
		if ($reshook > 0) {
			$result = $hookmanager->resPrint;
		} else {
			$result .= $hookmanager->resPrint;
		}

		return $result;
	}

	/**
	 *  Return the label of the status
	 *
	 *  @param  int		$mode          0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *  @return	string 			       Label of status
	 */
	public function getLabelStatus($mode = 0)
	{
		return $this->LibStatut($this->status, $mode);
	}

	/**
	 *  Return the label of the status
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
		if (empty($this->labelStatus) || empty($this->labelStatusShort)) {
			global $langs;
			//$langs->load("asset@asset");
			$this->labelStatus[self::STATUS_DRAFT] = $langs->transnoentitiesnoconv('Draft');
			$this->labelStatus[self::STATUS_VALIDATED] = $langs->transnoentitiesnoconv('Validated');
			$this->labelStatus[self::STATUS_CANCELED] = $langs->transnoentitiesnoconv('Canceled');
			$this->labelStatusShort[self::STATUS_DRAFT] = $langs->transnoentitiesnoconv('Draft');
			$this->labelStatusShort[self::STATUS_VALIDATED] = $langs->transnoentitiesnoconv('Validated');
			$this->labelStatusShort[self::STATUS_CANCELED] = $langs->transnoentitiesnoconv('Canceled');
		}

		$statusType = 'status'.$status;
		//if ($status == self::STATUS_VALIDATED) $statusType = 'status1';
		if ($status == self::STATUS_CANCELED) {
			$statusType = 'status6';
		}

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
		$sql = "SELECT rowid, date_creation as datec, tms as datem,";
		$sql .= " fk_user_creat, fk_user_modif";
		$sql .= " FROM ".MAIN_DB_PREFIX.$this->table_element." as t";
		$sql .= " WHERE t.rowid = ".((int) $id);

		$result = $this->db->query($sql);
		if ($result) {
			if ($this->db->num_rows($result)) {
				$obj = $this->db->fetch_object($result);
				$this->id = $obj->rowid;
				if ($obj->fk_user_author) {
					$cuser = new User($this->db);
					$cuser->fetch($obj->fk_user_author);
					$this->user_creation = $cuser;
				}

				if ($obj->fk_user_valid) {
					$vuser = new User($this->db);
					$vuser->fetch($obj->fk_user_valid);
					$this->user_validation = $vuser;
				}

				if ($obj->fk_user_cloture) {
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
		// Set here init that are not commonf fields
		// $this->property1 = ...
		// $this->property2 = ...

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

		return $this->lines;
	}

	/**
	 *  Returns the reference to the following non used object depending on the active numbering module.
	 *
	 *  @return string      		Object free reference
	 */
	public function getNextNumRef()
	{
		global $langs, $conf;
		$langs->load("asset@asset");

		if (empty($conf->global->ASSET_ASSET_ADDON)) {
			$conf->global->ASSET_ASSET_ADDON = 'mod_asset_standard';
		}

		if (!empty($conf->global->ASSET_ASSET_ADDON)) {
			$mybool = false;

			$file = $conf->global->ASSET_ASSET_ADDON.".php";
			$classname = $conf->global->ASSET_ASSET_ADDON;

			// Include file with class
			$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
			foreach ($dirmodels as $reldir) {
				$dir = dol_buildpath($reldir."core/modules/asset/");

				// Load file with numbering class (if found)
				$mybool |= @include_once $dir.$file;
			}

			if ($mybool === false) {
				dol_print_error('', "Failed to include file ".$file);
				return '';
			}

			if (class_exists($classname)) {
				$obj = new $classname();
				$numref = $obj->getNextValue($this);

				if ($numref != '' && $numref != '-1') {
					return $numref;
				} else {
					$this->error = $obj->error;
					//dol_print_error($this->db,get_class($this)."::getNextNumRef ".$obj->error);
					return "";
				}
			} else {
				print $langs->trans("Error")." ".$langs->trans("ClassNotFound").' '.$classname;
				return "";
			}
		} else {
			print $langs->trans("ErrorNumberingModuleNotSetup", $this->element);
			return "";
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

		$result = 0;
		$includedocgeneration = 1;

		$langs->load("asset@asset");

		if (!dol_strlen($modele)) {
			$modele = 'standard_asset';

			if (!empty($this->model_pdf)) {
				$modele = $this->model_pdf;
			} elseif (!empty($conf->global->ASSET_ADDON_PDF)) {
				$modele = $conf->global->ASSET_ADDON_PDF;
			}
		}

		$modelpath = "core/modules/asset/doc/";

		if ($includedocgeneration && !empty($modele)) {
			$result = $this->commonGenerateDocument($modelpath, $modele, $outputlangs, $hidedetails, $hidedesc, $hideref, $moreparams);
		}

		return $result;
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
}
