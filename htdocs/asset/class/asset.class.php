<?php
/* Copyright (C) 2017       Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2018-2024  Alexandre Spangaro      <alexandre@inovea-conseil.com>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2024       MDW                     <mdeweerd@users.noreply.github.com>
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
 * \file	asset/class/asset.class.php
 * \ingroup	asset
 * \brief	This file is a CRUD class file for Asset (Create/Read/Update/Delete)
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
	 * @var string ID to identify a managed object.
	 */
	public $element = 'asset';

	/**
	 * @var string Name of table without prefix where an object is stored. This is also the key used for extrafields management.
	 */
	public $table_element = 'asset';

	/**
	 * @var string String with name of icon for asset. Must be the part after the 'object_' into object_asset.png
	 */
	public $picto = 'asset';

	const STATUS_DRAFT = 0; 	// In progress
	const STATUS_DISPOSED = 9;	// Disposed

	/**
	 *  'type' field format ('integer', 'integer:ObjectClass:PathToClass[:AddCreateButtonOrNot[:Filter[:Sortfield]]]', 'sellist:TableName:LabelFieldName[:KeyFieldName[:KeyFieldParent[:Filter[:Sortfield]]]]', 'varchar(x)', 'double(24,8)', 'real', 'price', 'text', 'text:none', 'html', 'date', 'datetime', 'timestamp', 'duration', 'mail', 'phone', 'url', 'password')
	 *         Note: Filter can be a string like "(t.ref:like:'SO-%') or (t.date_creation:<:'20160101') or (t.nature:is:NULL)"
	 *  'label' the translation key.
	 *  'picto' is code of a picto to show before value in forms
	 *  'enabled' is a condition when the field must be managed (Example: 1 or 'getDolGlobalString("MY_SETUP_PARAM")'
	 *  'position' is the sort order of field.
	 *  'notnull' is set to 1 if not null in database. Set to -1 if we must set data to null if empty ('' or 0).
	 *  'visible' says if field is visible in list (Examples: 0=Not visible, 1=Visible on list and create/update/view forms, 2=Visible on list only, 3=Visible on create/update/view form only (not list), 4=Visible on list and update/view form only (not create). 5=Visible on list and view only (not create/not update). Using a negative value means field is not shown by default on list but can be selected for viewing)
	 *  'noteditable' says if field is not editable (1 or 0)
	 *  'default' is a default value for creation (can still be overwritten by the Setup of Default Values if field is editable in creation form). Note: If default is set to '(PROV)' and the field is 'ref', the default value will be set to '(PROVid)' where id is rowid when a new record is created.
	 *  'index' if we want an index in database.
	 *  'foreignkey'=>'tablename.field' if the field is a foreign key (it is recommended to name the field fk_...).
	 *  'searchall' is 1 if we want to search in this field when making a search from the quick search button.
	 *  'isameasure' must be set to 1 or 2 if field can be used for measure. Field type must be summable like integer or double(24,8). Use 1 in most cases, or 2 if you don't want to see the column total into a list (for example for percentage)
	 *  'css' and 'cssview' and 'csslist' is the CSS style to use on field. 'css' is used in creation and update. 'cssview' is used in view mode. 'csslist' is used for columns in lists. For example, 'css'=>'minwidth300 maxwidth500 widthcentpercentminusx', 'cssview'=>'wordbreak', 'csslist'=>'tdoverflowmax200'
	 *  'help' is a 'TranslationString' to use to show a tooltip on field. You can also use 'TranslationString:keyfortooltiponlick' for a tooltip on click.
	 *  'showoncombobox' if the value of the field must be visible into the label of the combobox that list record
	 *  'disabled' is 1 if we want to have the field locked by a 'disabled' attribute. In most cases, this is never set into the definition of $fields into class, but is set dynamically by some part of code.
	 *  'arrayofkeyval' to set a list of values if a type is a list of predefined values. For example, array("0"=>"Draft","1"=>"Active","-1"=>"Cancel"). Note that type can be 'integer' or 'varchar'
	 *  'autofocusoncreate' to have field having the focus on a creation form. Only 1 field should have this property set to 1.
	 *  'comment' is not used. You can store here any text of your choice. It is not used by application.
	 *	'validate' is 1 if you need to validate with $this->validateField()
	 *  'copytoclipboard' is 1 or 2 to allow adding a picto to copy value into clipboard (1=picto after label, 2=picto after value)
	 *
	 *  Note: To have value dynamic, you can set value to 0 in definition and edit the value on the fly into the constructor.
	 */

	/**
	 * @var array<string,array{type:string,label:string,enabled:int<0,2>|string,position:int,notnull?:int,visible:int,noteditable?:int,default?:string,index?:int,foreignkey?:string,searchall?:int,isameasure?:int,css?:string,csslist?:string,help?:string,showoncombobox?:int,disabled?:int,arrayofkeyval?:array<int,string>,comment?:string}>  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields = array(
		'rowid' => array('type' => 'integer', 'label' => 'TechnicalID', 'enabled' => 1, 'position' => 1, 'notnull' => 1, 'visible' => 0, 'noteditable' => 1, 'index' => 1, 'css' => 'left', 'comment' => "Id"),
		'ref' => array('type' => 'varchar(128)', 'label' => 'Ref', 'enabled' => 1, 'position' => 20, 'notnull' => 1, 'visible' => 1, 'noteditable' => 0, 'index' => 1, 'searchall' => 1, 'showoncombobox' => 1, 'validate' => 1, 'comment' => "Reference of object"),
		'label' => array('type' => 'varchar(255)', 'label' => 'Label', 'enabled' => 1, 'position' => 30, 'notnull' => 1, 'visible' => 1, 'searchall' => 1, 'css' => 'minwidth300', 'cssview' => 'wordbreak', 'showoncombobox' => '2', 'validate' => 1,),
		'fk_asset_model' => array('type' => 'integer:AssetModel:asset/class/assetmodel.class.php:1:((status:=:1) and (entity:IN:__SHARED_ENTITIES__))', 'label' => 'AssetModel', 'enabled' => 1, 'position' => 40, 'notnull' => 0, 'visible' => 1, 'index' => 1, 'validate' => 1,),
		'qty' => array('type' => 'real', 'label' => 'Qty', 'enabled' => 1, 'position' => 50, 'notnull' => 1, 'visible' => 0, 'default' => '1', 'isameasure' => 1, 'css' => 'maxwidth75imp', 'validate' => 1,),
		'acquisition_type' => array('type' => 'smallint', 'label' => 'AssetAcquisitionType', 'enabled' => 1, 'position' => 60, 'notnull' => 1, 'visible' => 1, 'arrayofkeyval' => array('0' => 'AssetAcquisitionTypeNew', '1' => 'AssetAcquisitionTypeOccasion'), 'validate' => 1,),
		'asset_type' => array('type' => 'smallint', 'label' => 'AssetType', 'enabled' => 1, 'position' => 70, 'notnull' => 1, 'visible' => 1, 'arrayofkeyval' => array('0' => 'AssetTypeIntangible', '1' => 'AssetTypeTangible', '2' => 'AssetTypeInProgress', '3' => 'AssetTypeFinancial'), 'validate' => 1,),
		'not_depreciated' => array('type' => 'boolean', 'label' => 'AssetNotDepreciated', 'enabled' => 1, 'position' => 80, 'notnull' => 0, 'default' => '0', 'visible' => 1, 'validate' => 1,),
		'date_acquisition' => array('type' => 'date', 'label' => 'AssetDateAcquisition', 'enabled' => 1, 'position' => 90, 'notnull' => 1, 'visible' => 1,),
		'date_start' => array('type' => 'date', 'label' => 'AssetDateStart', 'enabled' => 1, 'position' => 100, 'notnull' => 0, 'visible' => -1,),
		'acquisition_value_ht' => array('type' => 'price', 'label' => 'AssetAcquisitionValueHT', 'enabled' => 1, 'position' => 110, 'notnull' => 1, 'visible' => 1, 'isameasure' => 1, 'validate' => 1,),
		'recovered_vat' => array('type' => 'price', 'label' => 'AssetRecoveredVAT', 'enabled' => 1, 'position' => 120, 'notnull' => 0, 'visible' => 1, 'isameasure' => 1, 'validate' => 1,),
		'reversal_date' => array('type' => 'date', 'label' => 'AssetReversalDate', 'enabled' => 1, 'position' => 130, 'notnull' => 0, 'visible' => 1,),
		'reversal_amount_ht' => array('type' => 'price', 'label' => 'AssetReversalAmountHT', 'enabled' => 1, 'position' => 140, 'notnull' => 0, 'visible' => 1, 'isameasure' => 1, 'validate' => 1,),
		'disposal_date' => array('type' => 'date', 'label' => 'AssetDisposalDate', 'enabled' => 1, 'position' => 200, 'notnull' => 0, 'visible' => -2,),
		'disposal_amount_ht' => array('type' => 'price', 'label' => 'AssetDisposalAmount', 'enabled' => 1, 'position' => 210, 'notnull' => 0, 'visible' => -2, 'default' => '0', 'isameasure' => 1, 'validate' => 1,),
		'fk_disposal_type' => array('type' => 'sellist:c_asset_disposal_type:label:rowid::active=1', 'label' => 'AssetDisposalType', 'enabled' => 1, 'position' => 220, 'notnull' => 0, 'visible' => -2, 'index' => 1, 'validate' => 1,),
		'disposal_depreciated' => array('type' => 'boolean', 'label' => 'AssetDisposalDepreciated', 'enabled' => 1, 'position' => 230, 'notnull' => 0, 'default' => '0', 'visible' => -2, 'validate' => 1,),
		'disposal_subject_to_vat' => array('type' => 'boolean', 'label' => 'AssetDisposalSubjectToVat', 'enabled' => 1, 'position' => 240, 'notnull' => 0, 'default' => '0', 'visible' => -2, 'validate' => 1,),
		'note_public' => array('type' => 'html', 'label' => 'NotePublic', 'enabled' => 1, 'position' => 300, 'notnull' => 0, 'visible' => 0, 'validate' => 1,),
		'note_private' => array('type' => 'html', 'label' => 'NotePrivate', 'enabled' => 1, 'position' => 301, 'notnull' => 0, 'visible' => 0, 'validate' => 1,),
		'date_creation' => array('type' => 'datetime', 'label' => 'DateCreation', 'enabled' => 1, 'position' => 500, 'notnull' => 1, 'visible' => -2,),
		'tms' => array('type' => 'timestamp', 'label' => 'DateModification', 'enabled' => 1, 'position' => 501, 'notnull' => 0, 'visible' => -2,),
		'fk_user_creat' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserAuthor', 'enabled' => 1, 'position' => 510, 'notnull' => 1, 'visible' => -2, 'foreignkey' => 'user.rowid',),
		'fk_user_modif' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserModif', 'enabled' => 1, 'position' => 511, 'notnull' => -1, 'visible' => -2,),
		'last_main_doc' => array('type' => 'varchar(255)', 'label' => 'LastMainDoc', 'enabled' => 1, 'position' => 600, 'notnull' => 0, 'visible' => 0,),
		'import_key' => array('type' => 'varchar(14)', 'label' => 'ImportId', 'enabled' => 1, 'position' => 1000, 'notnull' => -1, 'visible' => -2,),
		'model_pdf' => array('type' => 'varchar(255)', 'label' => 'Model pdf', 'enabled' => 1, 'position' => 1010, 'notnull' => -1, 'visible' => 0,),
		'status' => array('type' => 'smallint', 'label' => 'Status', 'enabled' => 1, 'position' => 1000, 'notnull' => 1, 'default' => '0', 'visible' => 2, 'index' => 1, 'arrayofkeyval' => array('0' => 'Draft', '1' => 'Validated', '9' => 'Canceled'), 'validate' => 1,),
	);
	public $rowid;
	public $ref;
	public $label;
	public $fk_asset_model;
	public $reversal_amount_ht;
	public $acquisition_value_ht;
	public $recovered_vat;
	public $reversal_date;
	public $date_acquisition;
	public $date_start;
	public $qty;
	public $acquisition_type;
	public $asset_type;
	public $not_depreciated;
	public $disposal_date;
	public $disposal_amount_ht;
	public $fk_disposal_type;
	public $disposal_depreciated;
	public $disposal_subject_to_vat;
	public $supplier_invoice_id;
	public $note_public;
	public $note_private;
	public $fk_user_creat;
	public $fk_user_modif;
	public $last_main_doc;
	public $import_key;
	public $model_pdf;
	public $status;

	/**
	 * @var static object oldcopy
	 */
	public $oldcopy;


	/**
	 * @var AssetDepreciationOptions	Used for computed fields of depreciation options class.
	 */
	public $asset_depreciation_options;
	public $asset_accountancy_codes;
	/**
	 * @var array	List of depreciation lines for each mode (sort by depreciation date).
	 */
	public $depreciation_lines = array();

	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		global $conf, $langs;

		$this->db = $db;

		$this->ismultientitymanaged = 1;
		$this->isextrafieldmanaged = 1;

		if (!getDolGlobalString('MAIN_SHOW_TECHNICAL_ID') && isset($this->fields['rowid'])) {
			$this->fields['rowid']['visible'] = 0;
		}
		if (!isModEnabled('multicompany') && isset($this->fields['entity'])) {
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
	 * @param  int	$notrigger false=launch triggers after, true=disable triggers
	 * @return int             Return integer <0 if KO, Id of created object if OK
	 */
	public function create(User $user, $notrigger = 0)
	{
		if (!isset($this->date_start) || $this->date_start === "") {
			$this->date_start = $this->date_acquisition;
		}

		$this->db->begin();

		$result = $result_create = $this->createCommon($user, $notrigger);
		if ($result > 0 && $this->fk_asset_model > 0) {
			$result = $this->setDataFromAssetModel($user, $notrigger);
		}
		if ($result > 0) {
			if ($this->supplier_invoice_id > 0) {
				$this->add_object_linked('invoice_supplier', $this->supplier_invoice_id);
			}
		}

		if ($result < 0) {
			$this->db->rollback();
		} else {
			$this->db->commit();
		}

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

		//      $object = new self($this->db);
		//
		//      $this->db->begin();
		//
		//      // Load source object
		//      $result = $object->fetchCommon($fromid);
		//      if ($result > 0 && !empty($object->table_element_line)) {
		//          $object->fetchLines();
		//      }
		//
		//      // get lines so they will be clone
		//      //foreach($this->lines as $line)
		//      //  $line->fetch_optionals();
		//
		//      // Reset some properties
		//      unset($object->id);
		//      unset($object->fk_user_creat);
		//      unset($object->import_key);
		//
		//      // Clear fields
		//      if (property_exists($object, 'ref')) {
		//          $object->ref = empty($this->fields['ref']['default']) ? "Copy_Of_".$object->ref : $this->fields['ref']['default'];
		//      }
		//      if (property_exists($object, 'label')) {
		//          $object->label = empty($this->fields['label']['default']) ? $langs->trans("CopyOf")." ".$object->label : $this->fields['label']['default'];
		//      }
		//      if (property_exists($object, 'status')) {
		//          $object->status = self::STATUS_DRAFT;
		//      }
		//      if (property_exists($object, 'date_creation')) {
		//          $object->date_creation = dol_now();
		//      }
		//      if (property_exists($object, 'date_modification')) {
		//          $object->date_modification = null;
		//      }
		//      // ...
		//      // Clear extrafields that are unique
		//      if (is_array($object->array_options) && count($object->array_options) > 0) {
		//          $extrafields->fetch_name_optionals_label($this->table_element);
		//          foreach ($object->array_options as $key => $option) {
		//              $shortkey = preg_replace('/options_/', '', $key);
		//              if (!empty($extrafields->attributes[$this->table_element]['unique'][$shortkey])) {
		//                  //var_dump($key); var_dump($clonedObj->array_options[$key]); exit;
		//                  unset($object->array_options[$key]);
		//              }
		//          }
		//      }
		//
		//      // Create clone
		//      $object->context['createfromclone'] = 'createfromclone';
		//      $result = $object->createCommon($user);
		//      if ($result < 0) {
		//          $error++;
		//          $this->error = $object->error;
		//          $this->errors = $object->errors;
		//      }
		//
		//      if (!$error) {
		//          // copy internal contacts
		//          if ($this->copy_linked_contact($object, 'internal') < 0) {
		//              $error++;
		//          }
		//      }
		//
		//      if (!$error) {
		//          // copy external contacts if same company
		//          if (property_exists($this, 'fk_soc') && $this->fk_soc == $object->socid) {
		//              if ($this->copy_linked_contact($object, 'external') < 0) {
		//                  $error++;
		//              }
		//          }
		//      }
		//
		//      unset($object->context['createfromclone']);
		//
		//      // End
		//      if (!$error) {
		//          $this->db->commit();
		//          return $object;
		//      } else {
		//          $this->db->rollback();
		//          return -1;
		//      }
		return -1;
	}

	/**
	 * Load object in memory from the database
	 *
	 * @param int    $id   Id object
	 * @param string $ref  Ref
	 * @return int         Return integer <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id, $ref = null)
	{
		$result = $this->fetchCommon($id, $ref);
		if ($result > 0) {
			if (!empty($this->table_element_line)) {
				$this->fetchLines();
			}

			$res = $this->hasDepreciationLinesInBookkeeping();
			if ($res < 0) {
				return -1;
			} elseif ($res > 0) {
				$this->fields['date_acquisition']['noteditable'] = '1';
				$this->fields['date_start']['noteditable'] = '1';
				$this->fields['acquisition_value_ht']['noteditable'] = '1';
				$this->fields['recovered_vat']['noteditable'] = '1';
				$this->fields['reversal_date']['noteditable'] = '1';
				$this->fields['reversal_amount_ht']['noteditable'] = '1';
			}
		}
		return $result;
	}

	/**
	 * Load object lines in memory from the database
	 *
	 * @return int         Return integer <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetchLines()
	{
		$this->lines = array();

		return 1;
	}


	/**
	 * Load list of objects in memory from the database.
	 *
	 * @param  string      	$sortorder    	Sort Order
	 * @param  string      	$sortfield    	Sort field
	 * @param  int         	$limit        	limit
	 * @param  int         	$offset       	Offset
	 * @param  string		$filter       	Filter as an Universal Search string.
	 * 										Example: '((client:=:1) OR ((client:>=:2) AND (client:<=:3))) AND (client:!=:8) AND (nom:like:'a%')'
	 * @param  string      	$filtermode   	No more used
	 * @return array|int                 	int <0 if KO, array of pages if OK
	 */
	public function fetchAll($sortorder = '', $sortfield = '', $limit = 0, $offset = 0, $filter = '', $filtermode = 'AND')
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

		$records = array();

		$sql = "SELECT ";
		$sql .= $this->getFieldList('t');
		$sql .= " FROM ".MAIN_DB_PREFIX.$this->table_element." as t";
		if (isset($this->ismultientitymanaged) && $this->ismultientitymanaged == 1) {
			$sql .= " WHERE t.entity IN (".getEntity($this->element).")";
		} else {
			$sql .= " WHERE 1 = 1";
		}

		// Manage filter
		$errormessage = '';
		$sql .= forgeSQLFromUniversalSearchCriteria($filter, $errormessage);
		if ($errormessage) {
			$this->errors[] = $errormessage;
			dol_syslog(__METHOD__.' '.implode(',', $this->errors), LOG_ERR);

			return -1;
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
			dol_syslog(__METHOD__.' '.implode(',', $this->errors), LOG_ERR);

			return -1;
		}
	}

	/**
	 * Update object into database
	 *
	 * @param  User $user      User that modifies
	 * @param  int 	$notrigger 0=launch triggers after, 1=disable triggers
	 * @return int             Return integer <0 if KO, >0 if OK
	 */
	public function update(User $user, $notrigger = 0)
	{
		if (!isset($this->date_start) || $this->date_start === "") {
			$this->date_start = $this->date_acquisition;
		}

		$this->db->begin();

		$result = $this->updateCommon($user, $notrigger);
		if ($result > 0 && $this->fk_asset_model > 0 && $this->fk_asset_model != $this->oldcopy->fk_asset_model) {
			$result = $this->setDataFromAssetModel($user, $notrigger);
		}
		if ($result > 0 && (
			$this->date_start != $this->oldcopy->date_start ||
				$this->acquisition_value_ht != $this->oldcopy->acquisition_value_ht ||
				$this->reversal_date != $this->oldcopy->reversal_date ||
				$this->reversal_amount_ht != $this->oldcopy->reversal_amount_ht ||
				($this->fk_asset_model > 0 && $this->fk_asset_model != $this->oldcopy->fk_asset_model)
		)
		) {
			$result = $this->calculationDepreciation();
		}

		if ($result < 0) {
			$this->db->rollback();
		} else {
			$this->db->commit();
		}

		return $result;
	}

	/**
	 * Delete object in database
	 *
	 * @param User 	$user       User that deletes
	 * @param int	$notrigger  0=launch triggers after, 1=disable triggers
	 * @return int  			Return integer <0 if KO, >0 if OK
	 */
	public function delete(User $user, $notrigger = 0)
	{
		return $this->deleteCommon($user, $notrigger);
		//return $this->deleteCommon($user, $notrigger, 1);
	}

	/**
	 * Set asset model
	 *
	 * @param  User $user      	User that creates
	 * @param  int $notrigger 	0=launch triggers after, 1=disable triggers
	 * @return int             	Return integer <0 if KO, Id of created object if OK
	 */
	public function setDataFromAssetModel(User $user, $notrigger = 0)
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
	 * @return	int							Return integer <0 if KO, Id of created object if OK
	 */
	public function fetchDepreciationLines()
	{
		global $langs;
		$langs->load('assets');
		$this->depreciation_lines = array();

		// Clean parameters
		$this->id = $this->id > 0 ? $this->id : 0;

		// Check parameters
		$error = 0;
		if (empty($this->id)) {
			$this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Asset") . ' (' . $langs->transnoentitiesnoconv("TechnicalID") . ')');
			$error++;
		}
		if ($error) {
			return -1;
		}

		// Old request with 'WITH'
		/*
		$sql = "WITH in_accounting_bookkeeping(fk_docdet) AS (";
		$sql .= " SELECT DISTINCT fk_docdet";
		$sql .= " FROM " . MAIN_DB_PREFIX . "accounting_bookkeeping";
		$sql .= " WHERE doc_type = 'asset'";
		$sql .= ")";
		$sql .= "SELECT ad.rowid, ad.depreciation_mode, ad.ref, ad.depreciation_date, ad.depreciation_ht, ad.cumulative_depreciation_ht";
		$sql .= ", " . $this->db->ifsql('iab.fk_docdet IS NOT NULL', 1, 0) . " AS bookkeeping";
		$sql .= " FROM " . MAIN_DB_PREFIX . "asset_depreciation AS ad";
		$sql .= " LEFT JOIN in_accounting_bookkeeping as iab ON iab.fk_docdet = ad.rowid";
		$sql .= " WHERE ad.fk_asset = " . (int) $this->id;
		$sql .= " ORDER BY ad.depreciation_date ASC";
		*/

		$sql = "SELECT ad.rowid, ad.depreciation_mode, ad.ref, ad.depreciation_date, ad.depreciation_ht, ad.cumulative_depreciation_ht";
		$sql .= ", " . $this->db->ifsql('iab.fk_docdet IS NOT NULL', 1, 0) . " AS bookkeeping";
		$sql .= " FROM " . MAIN_DB_PREFIX . "asset_depreciation AS ad";
		$sql .= " LEFT JOIN (SELECT DISTINCT fk_docdet FROM " . MAIN_DB_PREFIX . "accounting_bookkeeping WHERE doc_type = 'asset') AS iab ON iab.fk_docdet = ad.rowid";
		$sql .= " WHERE ad.fk_asset = " . (int) $this->id;
		$sql .= " ORDER BY ad.depreciation_date ASC";

		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->errors[] = $langs->trans('AssetErrorFetchDepreciationLines') . ': ' . $this->db->lasterror();
			return -1;
		}

		while ($obj = $this->db->fetch_object($resql)) {
			if (!isset($this->depreciation_lines[$obj->depreciation_mode])) {
				$this->depreciation_lines[$obj->depreciation_mode] = array();
			}
			$this->depreciation_lines[$obj->depreciation_mode][] = array(
				'id' => $obj->rowid,
				'ref' => $obj->ref,
				'depreciation_date' => $this->db->jdate($obj->depreciation_date),
				'depreciation_ht' => $obj->depreciation_ht,
				'cumulative_depreciation_ht' => $obj->cumulative_depreciation_ht,
				'bookkeeping' => $obj->bookkeeping,
			);
		}

		return 1;
	}

	/**
	 * If has depreciation lines in bookkeeping
	 *
	 * @return	int			Return integer <0 if KO, 0 if NO, 1 if Yes
	 */
	public function hasDepreciationLinesInBookkeeping()
	{
		global $langs;
		$langs->load('assets');

		// Clean parameters
		$this->id = $this->id > 0 ? $this->id : 0;

		// Check parameters
		$error = 0;
		if (empty($this->id)) {
			$this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Asset") . ' (' . $langs->transnoentitiesnoconv("TechnicalID") . ')');
			$error++;
		}
		if ($error) {
			return -1;
		}

		// Old request with 'WITH'
		/*
		$sql = "WITH in_accounting_bookkeeping(fk_docdet) AS (";
		$sql .= " SELECT DISTINCT fk_docdet";
		$sql .= " FROM " . MAIN_DB_PREFIX . "accounting_bookkeeping";
		$sql .= " WHERE doc_type = 'asset'";
		$sql .= ")";
		$sql .= "SELECT COUNT(*) AS has_bookkeeping";
		$sql .= " FROM " . MAIN_DB_PREFIX . "asset_depreciation AS ad";
		$sql .= " LEFT JOIN in_accounting_bookkeeping as iab ON iab.fk_docdet = ad.rowid";
		$sql .= " WHERE ad.fk_asset = " . (int) $this->id;
		$sql .= " AND iab.fk_docdet IS NOT NULL";
		*/

		$sql = "SELECT COUNT(*) AS has_bookkeeping";
		$sql .= " FROM " . MAIN_DB_PREFIX . "asset_depreciation AS ad";
		$sql .= " LEFT JOIN (SELECT DISTINCT fk_docdet FROM " . MAIN_DB_PREFIX . "accounting_bookkeeping WHERE doc_type = 'asset') AS iab ON iab.fk_docdet = ad.rowid";
		$sql .= " WHERE ad.fk_asset = " . (int) $this->id;
		$sql .= " AND iab.fk_docdet IS NOT NULL";

		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->errors[] = $langs->trans('AssetErrorFetchDepreciationLines') . ': ' . $this->db->lasterror();
			return -1;
		}

		if ($obj = $this->db->fetch_object($resql)) {
			return $obj->has_bookkeeping > 0 ? 1 : 0;
		}

		return 0;
	}

	/**
	 * Add depreciation line for a mode
	 *
	 * @param	string		$mode							Depreciation mode (economic, accelerated_depreciation, ...)
	 * @param	string		$ref							Ref line
	 * @param	int			$depreciation_date				Depreciation date
	 * @param	double		$depreciation_ht				Depreciation amount HT
	 * @param	double		$cumulative_depreciation_ht		Depreciation cumulative amount HT
	 * @param	string		$accountancy_code_debit			Accountancy code Debit
	 * @param	string		$accountancy_code_credit		Accountancy code Credit
	 * @return	int											Return integer <0 if KO, Id of created line if OK
	 */
	public function addDepreciationLine($mode, $ref, $depreciation_date, $depreciation_ht, $cumulative_depreciation_ht, $accountancy_code_debit, $accountancy_code_credit)
	{
		global $langs;
		$langs->load('assets');

		// Clean parameters
		$this->id = $this->id > 0 ? $this->id : 0;
		$mode = strtolower(trim($mode));
		$ref = trim($ref);
		$accountancy_code_debit = trim($accountancy_code_debit);
		$accountancy_code_credit = trim($accountancy_code_credit);

		// Check parameters
		$error = 0;
		if (empty($this->id)) {
			$this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Asset") . ' (' . $langs->transnoentitiesnoconv("TechnicalID") . ')');
			$error++;
		}
		if ($error) {
			return -1;
		}

		$sql = "INSERT INTO " . MAIN_DB_PREFIX . "asset_depreciation(fk_asset, depreciation_mode, ref, depreciation_date, depreciation_ht, cumulative_depreciation_ht, accountancy_code_debit, accountancy_code_credit)";
		$sql .= " VALUES ( ";
		$sql .= " " . (int) $this->id;
		$sql .= ", '" . $this->db->escape($mode) . "'";
		$sql .= ", '" . $this->db->escape($ref) . "'";
		$sql .= ", '" . $this->db->idate($depreciation_date) . "'";
		$sql .= ", " . (float) $depreciation_ht;
		$sql .= ", " . (float) $cumulative_depreciation_ht;
		$sql .= ", '" . $this->db->escape($accountancy_code_debit) . "'";
		$sql .= ", '" . $this->db->escape($accountancy_code_credit) . "'";
		$sql .= ")";

		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->errors[] = $langs->trans('AssetErrorAddDepreciationLine') . ': ' . $this->db->lasterror();
			return -1;
		}

		return 1;
	}

	/**
	 * Calculation depreciation lines (reversal and future) for each mode
	 *
	 * @return	int							Return integer <0 if KO, Id of created object if OK
	 */
	public function calculationDepreciation()
	{
		global $conf, $langs;
		$langs->load('assets');

		// Clean parameters
		$this->id = $this->id > 0 ? $this->id : 0;

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

		// Get accountancy codes
		//---------------------------
		require_once DOL_DOCUMENT_ROOT . '/asset/class/assetaccountancycodes.class.php';
		$accountancy_codes = new AssetAccountancyCodes($this->db);
		$result = $accountancy_codes->fetchAccountancyCodes($this->id);
		if ($result < 0) {
			$this->error = $accountancy_codes->error;
			$this->errors = $accountancy_codes->errors;
			return -1;
		}

		$this->db->begin();

		// Delete old lines
		$modes = array();
		foreach ($options->deprecation_options as $mode_key => $fields) {
			$modes[$mode_key] = $this->db->escape($mode_key);
		}
		$sql = "DELETE FROM " . MAIN_DB_PREFIX . "asset_depreciation";
		$sql .= " WHERE fk_asset = " . (int) $this->id;
		$sql .= " AND depreciation_mode NOT IN ('" . $this->db->sanitize(implode("', '", $modes)) . "')";

		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->errors[] = $langs->trans('AssetErrorClearDepreciationLines') . ': ' . $this->db->lasterror();
			$error++;
		}

		if (!$error) {
			// Get fiscal period
			require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
			require_once DOL_DOCUMENT_ROOT . '/core/lib/accounting.lib.php';
			$dates = getCurrentPeriodOfFiscalYear($this->db, $conf, $this->date_start > $this->date_acquisition ? $this->date_start : $this->date_acquisition);
			$init_fiscal_period_start = $dates['date_start'];
			$init_fiscal_period_end = $dates['date_end'];
			if (empty($init_fiscal_period_start) || empty($init_fiscal_period_end)) {
				$pastmonthyear = $dates['pastmonthyear'];
				$pastmonth = $dates['pastmonth'];
				$init_fiscal_period_start = dol_get_first_day($pastmonthyear, $pastmonth, false);
				$init_fiscal_period_end = dol_get_last_day($pastmonthyear, $pastmonth, false);
			}

			foreach ($options->deprecation_options as $mode_key => $fields) {
				// Get last depreciation lines save in bookkeeping
				//-----------------------------------------------------

				// Old request with 'WITH'
				/*
				$sql = "WITH in_accounting_bookkeeping(fk_docdet) AS (";
				$sql .= " SELECT fk_docdet";
				$sql .= " FROM " . MAIN_DB_PREFIX . "accounting_bookkeeping";
				$sql .= " WHERE doc_type = 'asset'";
				$sql .= ")";
				$sql .= "SELECT ad.depreciation_date, ad.cumulative_depreciation_ht";
				$sql .= " FROM " . MAIN_DB_PREFIX . "asset_depreciation AS ad";
				$sql .= " LEFT JOIN in_accounting_bookkeeping as iab ON iab.fk_docdet = ad.rowid";
				$sql .= " WHERE ad.fk_asset = " . (int) $this->id;
				$sql .= " AND ad.depreciation_mode = '" . $this->db->escape($mode_key) . "'";
				$sql .= " AND iab.fk_docdet IS NOT NULL";
				$sql .= " ORDER BY ad.depreciation_date DESC";
				$sql .= " LIMIT 1";
				*/

				$sql = "SELECT ad.depreciation_date, ad.cumulative_depreciation_ht";
				$sql .= " FROM " . MAIN_DB_PREFIX . "asset_depreciation AS ad";
				$sql .= " LEFT JOIN (SELECT DISTINCT fk_docdet FROM " . MAIN_DB_PREFIX . "accounting_bookkeeping WHERE doc_type = 'asset') AS iab ON iab.fk_docdet = ad.rowid";
				$sql .= " WHERE ad.fk_asset = " . (int) $this->id;
				$sql .= " AND ad.depreciation_mode = '" . $this->db->escape($mode_key) . "'";
				$sql .= " AND iab.fk_docdet IS NOT NULL";
				$sql .= " ORDER BY ad.depreciation_date DESC";
				$sql .= " LIMIT 1";

				$resql = $this->db->query($sql);
				if (!$resql) {
					$this->errors[] = $langs->trans('AssetErrorFetchMaxDepreciationDateForMode', $mode_key) . ': ' . $this->db->lasterror();
					$error++;
					break;
				}
				$last_depreciation_date = '';
				$last_cumulative_depreciation_ht = $this->reversal_amount_ht;
				if ($obj = $this->db->fetch_object($resql)) {
					$last_depreciation_date = $this->db->jdate($obj->depreciation_date);
					$last_cumulative_depreciation_ht = $obj->cumulative_depreciation_ht;
				}

				// Set last cumulative depreciation
				$sql = "UPDATE " . MAIN_DB_PREFIX . $options->deprecation_options_fields[$mode_key]['table'];
				$sql .= " SET total_amount_last_depreciation_ht = " . (empty($last_cumulative_depreciation_ht) ? 0 : $last_cumulative_depreciation_ht);
				$sql .= " WHERE fk_asset = " . (int) $this->id;
				$resql = $this->db->query($sql);
				if (!$resql) {
					$this->errors[] = $langs->trans('AssetErrorSetLastCumulativeDepreciation') . ': ' . $this->db->lasterror();
					$error++;
					break;
				}

				// Delete old lines
				$sql = "DELETE " . MAIN_DB_PREFIX . "asset_depreciation FROM " . MAIN_DB_PREFIX . "asset_depreciation";
				$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "accounting_bookkeeping as ab ON ab.doc_type = 'asset' AND ab.fk_docdet = " . MAIN_DB_PREFIX . "asset_depreciation.rowid";
				$sql .= " WHERE " . MAIN_DB_PREFIX . "asset_depreciation.fk_asset = " . (int) $this->id;
				$sql .= " AND " . MAIN_DB_PREFIX . "asset_depreciation.depreciation_mode = '" . $this->db->escape($mode_key) . "'";
				$sql .= " AND ab.fk_docdet IS NULL";
				if ($last_depreciation_date !== "") {
					$sql .= " AND " . MAIN_DB_PREFIX . "asset_depreciation.ref != ''";
				}
				$resql = $this->db->query($sql);
				if (!$resql) {
					$this->errors[] = $langs->trans('AssetErrorClearDepreciationLines') . ': ' . $this->db->lasterror();
					$error++;
					break;
				}

				// Get depreciation period
				$depreciation_date_start = $this->date_start > $this->date_acquisition ? $this->date_start : $this->date_acquisition;
				$depreciation_date_end = dol_time_plus_duree($depreciation_date_start, $fields['duration'], $fields['duration_type'] == 1 ? 'm' : ($fields['duration_type'] == 2 ? 'd' : 'y'));
				$depreciation_amount = $fields['amount_base_depreciation_ht'];
				if ($fields['duration_type'] == 2) { // Daily
					$fiscal_period_start = $depreciation_date_start;
					$fiscal_period_end = $depreciation_date_start;
				} elseif ($fields['duration_type'] == 1) { // Monthly
					$date_temp = dol_getdate($depreciation_date_start);
					$fiscal_period_start = dol_get_first_day($date_temp['year'], $date_temp['mon'], false);
					$fiscal_period_end = dol_get_last_day($date_temp['year'], $date_temp['mon'], false);
				} else { // Annually
					$fiscal_period_start = $init_fiscal_period_start;
					$fiscal_period_end = $init_fiscal_period_end;
				}
				$cumulative_depreciation_ht = $last_cumulative_depreciation_ht;
				$depreciation_period_amount = $depreciation_amount - $this->reversal_amount_ht;
				$start_date = $depreciation_date_start;
				$disposal_date = isset($this->disposal_date) && $this->disposal_date !== "" ? $this->disposal_date : "";
				$finish_date = $disposal_date !== "" ? $disposal_date : $depreciation_date_end;
				$accountancy_code_depreciation_debit_key = $accountancy_codes->accountancy_codes_fields[$mode_key]['depreciation_debit'];
				$accountancy_code_depreciation_debit = $accountancy_codes->accountancy_codes[$mode_key][$accountancy_code_depreciation_debit_key];
				$accountancy_code_depreciation_credit_key = $accountancy_codes->accountancy_codes_fields[$mode_key]['depreciation_credit'];
				$accountancy_code_credit = $accountancy_codes->accountancy_codes[$mode_key][$accountancy_code_depreciation_credit_key];

				// Reversal depreciation line
				//-----------------------------------------------------
				if ($last_depreciation_date === "" && ($depreciation_date_start < $fiscal_period_start || is_numeric($this->reversal_date))) {
					if (is_numeric($this->reversal_date)) {
						if ($this->reversal_date < $fiscal_period_start) {
							$this->errors[] = $langs->trans('AssetErrorReversalDateNotGreaterThanCurrentBeginFiscalDateForMode', $mode_key);
							$error++;
							break;
						}

						if (empty($this->reversal_amount_ht)) {
							$this->errors[] = $langs->trans('AssetErrorReversalAmountNotProvidedForMode', $mode_key);
							$error++;
							break;
						}

						$start_date = $this->reversal_date;
						$result = $this->addDepreciationLine($mode_key, '', $start_date, $this->reversal_amount_ht, $this->reversal_amount_ht, $accountancy_code_depreciation_debit, $accountancy_code_credit);
						if ($result < 0) {
							$error++;
							break;
						}
					} else {
						$this->errors[] = $langs->trans('AssetErrorReversalDateNotProvidedForMode', $mode_key);
						$error++;
						break;
					}
				}

				// futures depreciation lines
				//-----------------------------------------------------
				$nb_days_in_year = getDolGlobalInt('ASSET_DEPRECIATION_DURATION_PER_YEAR', 360);
				$nb_days_in_month = getDolGlobalInt('ASSET_DEPRECIATION_DURATION_PER_MONTH', 30);
				$period_amount = (float) price2num($depreciation_period_amount / $fields['duration'], 'MT');
				$first_period_found = false;
				// TODO fix declaration of $begin_period
				$first_period_date = isset($begin_period) && $begin_period > $fiscal_period_start ? $begin_period : $fiscal_period_start;

				$ref_date_format = "%Y" . ($fields['duration_type'] == 1 || $fields['duration_type'] == 2 ? '-%m' : '') . ($fields['duration_type'] == 2 ? '-%d' : '');

				// Loop security
				$idx_loop = 0;
				$max_loop = $fields['duration'] + 2;
				do {
					// Loop security
					$idx_loop++;
					if ($idx_loop > $max_loop) {
						break;
					}

					if ($last_depreciation_date < $fiscal_period_end && ($first_period_date <= $start_date || $first_period_found)) {
						// Disposal not depreciated
						if ($fiscal_period_start <= $disposal_date && $disposal_date <= $fiscal_period_end && empty($this->disposal_depreciated)) {
							break;
						}

						$first_period_found = true;

						$period_begin = dol_print_date($fiscal_period_start, $ref_date_format);
						$period_end = dol_print_date($fiscal_period_end, $ref_date_format);
						$ref = $period_begin . ($period_begin != $period_end ? ' - ' . $period_end : '');
						if ($fiscal_period_start <= $disposal_date && $disposal_date <= $fiscal_period_end) {
							$ref .= ' - ' . $langs->transnoentitiesnoconv('AssetDisposal');
						}

						$begin_date = $fiscal_period_start < $start_date && $start_date <= $fiscal_period_end ? $start_date : $fiscal_period_start;
						$end_date = $fiscal_period_start < $finish_date && $finish_date <= $fiscal_period_end ? $finish_date : $fiscal_period_end;
						if ($fields['duration_type'] == 2) { // Daily
							$depreciation_ht = $period_amount;
						} elseif ($fields['duration_type'] == 1) { // Monthly
							$nb_days = min($nb_days_in_month, num_between_day($begin_date, $end_date, 1));
							if ($nb_days >= 28) {
								$date_temp = dol_getdate($begin_date);
								if ($date_temp['mon'] == 2) {
									$nb_days = 30;
								}
							}
							$depreciation_ht = (float) price2num($period_amount * $nb_days / $nb_days_in_month, 'MT');
						} else { // Annually
							$nb_days = min($nb_days_in_year, num_between_day($begin_date, $end_date, 1));
							$depreciation_ht = (float) price2num($period_amount * $nb_days / $nb_days_in_year, 'MT');
						}

						if ($fiscal_period_start <= $depreciation_date_end && $depreciation_date_end <= $fiscal_period_end) { // last period
							$depreciation_ht = (float) price2num($depreciation_amount - $cumulative_depreciation_ht, 'MT');
							$cumulative_depreciation_ht = $depreciation_amount;
						} else {
							$cumulative_depreciation_ht += $depreciation_ht;
						}

						$result = $this->addDepreciationLine($mode_key, $ref, $fiscal_period_end, $depreciation_ht, $cumulative_depreciation_ht, $accountancy_code_depreciation_debit, $accountancy_code_credit);
						if ($result < 0) {
							$error++;
							break;
						}
					}

					// Next fiscal period (+1 day/month/year)
					$fiscal_period_start = dol_time_plus_duree($fiscal_period_end, 1, 'd');
					if ($fields['duration_type'] == 2) { // Daily
						$fiscal_period_end = $fiscal_period_start;
					} elseif ($fields['duration_type'] == 1) { // Monthly
						$fiscal_period_end = dol_time_plus_duree(dol_time_plus_duree($fiscal_period_start, 1, 'm'), -1, 'd');
					} else { // Annually
						$fiscal_period_end = dol_time_plus_duree(dol_time_plus_duree($fiscal_period_start, 1, 'y'), -1, 'd');
					}
					$last_period_date = $disposal_date !== "" && $disposal_date < $depreciation_date_end ? $disposal_date : $depreciation_date_end;
				} while ($fiscal_period_start < $last_period_date);

				if ($error) {
					break;
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
	 * Set last cumulative depreciation for each mode
	 *
	 * @param	int		$asset_depreciation_id		Asset depreciation line ID
	 * @return	int									Return integer <0 if KO, >0 if OK
	 */
	public function setLastCumulativeDepreciation($asset_depreciation_id)
	{
		global $langs;
		$langs->load('assets');

		// Clean parameters
		$asset_depreciation_id = $asset_depreciation_id > 0 ? $asset_depreciation_id : 0;

		// Check parameters
		$error = 0;
		if (empty($asset_depreciation_id)) {
			$this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("AssetDepreciation") . ' (' . $langs->transnoentitiesnoconv("TechnicalID") . ')');
			$error++;
		}
		if ($error) {
			return -1;
		}

		$this->db->begin();

		require_once DOL_DOCUMENT_ROOT . '/asset/class/assetdepreciationoptions.class.php';
		$options = new AssetDepreciationOptions($this->db);

		// Get last depreciation lines save in bookkeeping
		//-----------------------------------------------------
		$sql = "SELECT fk_asset, depreciation_mode, cumulative_depreciation_ht";
		$sql .= " FROM " . MAIN_DB_PREFIX . "asset_depreciation";
		$sql .= " WHERE rowid = " . (int) $asset_depreciation_id;
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->errors[] = $langs->trans('AssetErrorFetchCumulativeDepreciation') . ': ' . $this->db->lasterror();
			$error++;
		} else {
			if ($obj = $this->db->fetch_object($resql)) {
				$mode_key = $obj->depreciation_mode;
				if (!empty($options->deprecation_options_fields[$mode_key])) {
					$sql = "UPDATE " . MAIN_DB_PREFIX . $options->deprecation_options_fields[$mode_key]['table'];
					$sql .= " SET total_amount_last_depreciation_ht = " . $obj->cumulative_depreciation_ht;
					$sql .= " WHERE fk_asset = " . (int) $obj->fk_asset;
					$resql = $this->db->query($sql);
					if (!$resql) {
						$this->errors[] = $langs->trans('AssetErrorSetLastCumulativeDepreciation') . ': ' . $this->db->lasterror();
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
	 *	Set dispose status
	 *
	 *	@param	User	$user						Object user that dispose
	 *	@param	int		$disposal_invoice_id		Disposal invoice ID
	 *  @param	int		$notrigger					1=Does not execute triggers, 0=Execute triggers
	 *	@return	int									Return integer <0 if KO, 0=Nothing done, >0 if OK
	 */
	public function dispose($user, $disposal_invoice_id, $notrigger = 0)
	{
		global $conf, $langs;

		// Protection
		if ($this->status != self::STATUS_DRAFT || $this->status == self::STATUS_DISPOSED) {
			return 0;
		}

		$this->db->begin();

		$required_fields = array('disposal_date', 'disposal_date', 'fk_disposal_type');
		foreach ($required_fields as $field) {
			$this->fields[$field]['notnull'] = 1;
		}
		$result = $this->update($user, 1);
		foreach ($required_fields as $field) {
			$this->fields[$field]['notnull'] = 0;
		}
		if ($result > 0) {
			if ($disposal_invoice_id > 0) {
				$this->add_object_linked('facture', $disposal_invoice_id);
			}
			$result = $this->setStatusCommon($user, self::STATUS_DISPOSED, $notrigger, 'ASSET_DISPOSED');
		}
		if ($result > 0) {
			$result = $this->calculationDepreciation();
		}

		if ($result < 0) {
			$this->db->rollback();
		} else {
			$this->db->commit();
		}

		// Define output language
		if ($result > 0 && !getDolGlobalString('MAIN_DISABLE_PDF_AUTOUPDATE')) {
			if (method_exists($this, 'generateDocument')) {
				global $hidedetails, $hidedesc, $hideref;
				$outputlangs = $langs;
				$newlang = '';
				if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
					$newlang = GETPOST('lang_id', 'aZ09');
				}
				if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang)) {
					$newlang = $this->thirdparty->default_lang;
				}
				if (!empty($newlang)) {
					$outputlangs = new Translate("", $conf);
					$outputlangs->setDefaultLang($newlang);
				}
				$model = $this->model_pdf;
				$ret = $this->fetch($this->id); // Reload to get new records

				$this->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref);
			}
		}

		return $result;
	}

	/**
	 *	Set back to validated status
	 *
	 *	@param	User	$user			Object user that modify
	 *  @param	int		$notrigger		1=Does not execute triggers, 0=Execute triggers
	 *	@return	int						Return integer <0 if KO, 0=Nothing done, >0 if OK
	 */
	public function reopen($user, $notrigger = 0)
	{
		global $conf, $langs;

		// Protection
		if ($this->status != self::STATUS_DISPOSED || $this->status == self::STATUS_DRAFT) {
			return 0;
		}


		$this->db->begin();

		$this->disposal_date = null;
		$this->disposal_amount_ht = null;
		$this->fk_disposal_type = null;
		$this->disposal_depreciated = null;
		$this->disposal_subject_to_vat = null;
		$result = $this->update($user, 1);
		if ($result > 0) {
			$this->deleteObjectLinked(null, 'facture');
			$result = $this->setStatusCommon($user, self::STATUS_DRAFT, $notrigger, 'ASSET_REOPEN');
		}
		if ($result > 0) {
			$result = $this->calculationDepreciation();
		}

		if ($result < 0) {
			$this->db->rollback();
		} else {
			$this->db->commit();
		}

		// Define output language
		if ($result > 0 && !getDolGlobalString('MAIN_DISABLE_PDF_AUTOUPDATE')) {
			if (method_exists($this, 'generateDocument')) {
				global $hidedetails, $hidedesc, $hideref;
				$outputlangs = $langs;
				$newlang = '';
				if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
					$newlang = GETPOST('lang_id', 'aZ09');
				}
				if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang)) {
					$newlang = $this->thirdparty->default_lang;
				}
				if (!empty($newlang)) {
					$outputlangs = new Translate("", $conf);
					$outputlangs->setDefaultLang($newlang);
				}
				$model = $this->model_pdf;
				$ret = $this->fetch($this->id); // Reload to get new records

				$this->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref);
			}
		}

		return $result;
	}

	/**
	 *  Return a link to the object card (with optionally the picto)
	 *
	 * @param	int     $withpicto                  Include picto in link (0=No picto, 1=Include picto into link, 2=Only picto)
	 * @param	string  $option                     On what the link point to ('nolink', ...)
	 * @param	int		$maxlen			          	Max length of name
	 * @param	int     $notooltip                  1=Disable tooltip
	 * @param	string  $morecss                    Add more css on link
	 * @param	int     $save_lastsearch_value      -1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 * @return	string                              String with URL
	 */
	public function getNomUrl($withpicto = 0, $option = '', $maxlen = 0, $notooltip = 0, $morecss = '', $save_lastsearch_value = -1)
	{
		global $db, $conf, $langs, $hookmanager;
		global $dolibarr_main_authentication, $dolibarr_main_demo;
		global $menumanager;

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
					if (!getDolGlobalString(strtoupper($module.'_'.$class).'_FORMATLISTPHOTOSASUSERS')) {
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
			$name = $this->ref;
			if ($option == 'label') {
				$name = $this->label;
			} elseif ($option == 'with_label') {
				$name .= ' - ' . $this->label;
			}
			$result .= dol_escape_htmltag($maxlen ? dol_trunc($name, $maxlen) : $name);
		}

		$result .= $linkend;
		//if ($withpicto != 2) $result.=(($addlabel && $this->label) ? $sep . dol_trunc($this->label, ($addlabel > 1 ? $addlabel : 0)) : '');

		global $action;
		$hookmanager->initHooks(array($this->element . 'dao'));
		$parameters = array('id' => $this->id, 'getnomurl' => &$result);
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
			//$langs->load("assets");
			$this->labelStatus[self::STATUS_DRAFT] = $langs->transnoentitiesnoconv('AssetInProgress');
			$this->labelStatus[self::STATUS_DISPOSED] = $langs->transnoentitiesnoconv('AssetDisposed');
			$this->labelStatusShort[self::STATUS_DRAFT] = $langs->transnoentitiesnoconv('AssetInProgress');
			$this->labelStatusShort[self::STATUS_DISPOSED] = $langs->transnoentitiesnoconv('AssetDisposed');
		}

		$statusType = 'status4';
		if ($status == self::STATUS_DISPOSED) {
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

				$this->user_creation_id = $obj->fk_user_creat;
				$this->user_modification_id = $obj->fk_user_modif;
				$this->date_creation     = $this->db->jdate($obj->datec);
				$this->date_modification = $this->db->jdate($obj->datem);
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
	 * @return int
	 */
	public function initAsSpecimen()
	{
		// Set here init that are not commonf fields
		// $this->property1 = ...
		// $this->property2 = ...

		return $this->initAsSpecimenCommon();
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
		$langs->load("assets");

		if (!getDolGlobalString('ASSET_ASSET_ADDON')) {
			$conf->global->ASSET_ASSET_ADDON = 'mod_asset_standard';
		}

		if (getDolGlobalString('ASSET_ASSET_ADDON')) {
			$mybool = false;

			$file = getDolGlobalString('ASSET_ASSET_ADDON') . ".php";
			$classname = getDolGlobalString('ASSET_ASSET_ADDON');

			// Include file with class
			$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
			foreach ($dirmodels as $reldir) {
				$dir = dol_buildpath($reldir."core/modules/asset/");

				// Load file with numbering class (if found)
				$mybool = ((bool) @include_once $dir.$file) || $mybool;
			}

			if ($mybool === false) {
				dol_print_error(null, "Failed to include file ".$file);
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
}
