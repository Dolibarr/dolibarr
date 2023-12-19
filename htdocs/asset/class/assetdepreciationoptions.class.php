<?php
/* Copyright (C) 2021  Open-Dsi  <support@open-dsi.fr>
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
 * \file        asset/class/assetdepreciationoptions.class.php
 * \ingroup     asset
 * \brief       This file is a class file for AssetDepreciationOptions
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';

/**
 * Class for AssetDepreciationOptions
 */
class AssetDepreciationOptions extends CommonObject
{
	/**
	 * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management.
	 */
	public $table_element = '';

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
	 *  'enabled_field' if the mode block or a field is enabled if another field equal a value (="mode_key:field_key:value")
	 *  'only_on_asset' is 1 if only a field on a asset
	 *
	 *  Note: To have value dynamic, you can set value to 0 in definition and edit the value on the fly into the constructor.
	 */

	/**
	 * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields = array();

	/**
	 * @var array  Array with all deprecation options info by mode.
	 *  Note : economic mode is mandatory and is the primary options
	 */
	public $deprecation_options_fields = array(
		'economic' => array(
			'label' => 'AssetDepreciationOptionEconomic',
			'table'	=> 'asset_depreciation_options_economic',
			'fields' => array(
				'depreciation_type' => array('type'=>'smallint', 'label'=>'AssetDepreciationOptionDepreciationType', 'enabled'=>'1', 'position'=>10, 'notnull'=>1, 'visible'=>1, 'default'=>'0', 'arrayofkeyval'=>array('0'=>'AssetDepreciationOptionDepreciationTypeLinear', '1'=>'AssetDepreciationOptionDepreciationTypeDegressive', '2'=>'AssetDepreciationOptionDepreciationTypeExceptional'), 'validate'=>'1',),
				'degressive_coefficient' => array('type'=>'double(24,8)', 'label'=>'AssetDepreciationOptionDegressiveRate', 'enabled'=>'1', 'position'=>20, 'notnull'=>1, 'visible'=>1, 'default'=>'0', 'isameasure'=>'1', 'validate'=>'1','enabled_field' => 'economic:depreciation_type:1'),
				'duration' => array('type'=>'integer', 'label'=>'AssetDepreciationOptionDuration', 'enabled'=>'1', 'position'=>30, 'notnull'=>1, 'visible'=>1, 'default'=>'0', 'isameasure'=>'1', 'validate'=>'1',),
				'duration_type' => array('type'=>'smallint', 'label'=>'AssetDepreciationOptionDurationType', 'enabled'=>'1', 'position'=>40, 'notnull'=>1, 'visible'=>1, 'default'=>'0', 'arrayofkeyval'=>array('0'=>'AssetDepreciationOptionDurationTypeAnnual', '1'=>'AssetDepreciationOptionDurationTypeMonthly'/*, '2'=>'AssetDepreciationOptionDurationTypeDaily'*/), 'validate'=>'1',),
				'rate' => array('type'=>'double(24,8)', 'label'=>'AssetDepreciationOptionRate', 'enabled'=>'1', 'position'=>50, 'visible'=>3, 'default'=>'0', 'isameasure'=>'1', 'validate'=>'1', 'computed' => '$object->asset_depreciation_options->getRate("economic")',),
				'accelerated_depreciation_option' => array('type'=>'boolean', 'label'=>'AssetDepreciationOptionAcceleratedDepreciation', 'enabled'=>'1', 'position'=>60, 'column_break' => true, 'notnull'=>0, 'default'=>'0', 'visible'=>1, 'validate'=>'1',),
				'amount_base_depreciation_ht' => array('type'=>'price', 'label'=>'AssetDepreciationOptionAmountBaseDepreciationHT', 'enabled'=>'isset($object)&&get_class($object)=="Asset"', 'only_on_asset'=>1, 'position'=>90, 'notnull'=>0, 'required'=>1, 'visible'=>1, 'default'=>'$object->reversal_amount_ht > 0 ? $object->reversal_amount_ht : $object->acquisition_value_ht', 'isameasure'=>'1', 'validate'=>'1',),
				'amount_base_deductible_ht' => array('type'=>'price', 'label'=>'AssetDepreciationOptionAmountBaseDeductibleHT', 'enabled'=>'isset($object)&&get_class($object)=="Asset"', 'only_on_asset'=>1, 'position'=>100, 'notnull'=>0, 'visible'=>1, 'default'=>'0', 'isameasure'=>'1', 'validate'=>'1',),
				'total_amount_last_depreciation_ht' => array('type'=>'price', 'label'=>'AssetDepreciationOptionTotalAmountLastDepreciationHT', 'enabled'=>'isset($object)&&get_class($object)=="Asset"', 'only_on_asset'=>1, 'position'=>110, 'noteditable'=> 1, 'notnull'=>0, 'visible'=>1, 'default'=>'0', 'isameasure'=>'1', 'validate'=>'1',),
			),
		),
		'accelerated_depreciation' => array(
			'label' => 'AssetDepreciationOptionAcceleratedDepreciation',
			'table'	=> 'asset_depreciation_options_fiscal',
			'enabled_field' => 'economic:accelerated_depreciation_option:1',
			'fields' => array(
				'depreciation_type' => array('type'=>'smallint', 'label'=>'AssetDepreciationOptionDepreciationType', 'enabled'=>'1', 'position'=>10, 'notnull'=>1, 'visible'=>1, 'default'=>'0', 'arrayofkeyval'=>array('0'=>'AssetDepreciationOptionDepreciationTypeLinear', '1'=>'AssetDepreciationOptionDepreciationTypeDegressive', '2'=>'AssetDepreciationOptionDepreciationTypeExceptional'), 'validate'=>'1',),
				'degressive_coefficient' => array('type'=>'double(24,8)', 'label'=>'AssetDepreciationOptionDegressiveRate', 'enabled'=>'1', 'position'=>20, 'notnull'=>1, 'visible'=>1, 'default'=>'0', 'isameasure'=>'1', 'validate'=>'1','enabled_field' => 'accelerated_depreciation:depreciation_type:1'),
				'duration' => array('type'=>'integer', 'label'=>'AssetDepreciationOptionDuration', 'enabled'=>'1', 'position'=>30, 'notnull'=>1, 'visible'=>1, 'default'=>'0', 'isameasure'=>'1', 'validate'=>'1',),
				'duration_type' => array('type'=>'smallint', 'label'=>'AssetDepreciationOptionDurationType', 'enabled'=>'1', 'position'=>40, 'notnull'=>1, 'visible'=>1, 'default'=>'0', 'arrayofkeyval'=>array('0'=>'AssetDepreciationOptionDurationTypeAnnual', '1'=>'AssetDepreciationOptionDurationTypeMonthly'/*, '2'=>'AssetDepreciationOptionDurationTypeDaily'*/), 'validate'=>'1',),
				'rate' => array('type'=>'double(24,8)', 'label'=>'AssetDepreciationOptionRate', 'enabled'=>'1', 'position'=>50, 'visible'=>3, 'default'=>'0', 'isameasure'=>'1', 'validate'=>'1', 'computed' => '$object->asset_depreciation_options->getRate("accelerated_depreciation")',),
				'amount_base_depreciation_ht' => array('type'=>'price', 'label'=>'AssetDepreciationOptionAmountBaseDepreciationHT', 'enabled'=>'isset($object)&&get_class($object)=="Asset"', 'only_on_asset'=>1, 'position'=>80, 'column_break' => true, 'notnull'=>0, 'required'=>1, 'visible'=>1, 'default'=>'$object->reversal_amount_ht > 0 ? $object->reversal_amount_ht : $object->acquisition_value_ht', 'isameasure'=>'1', 'validate'=>'1',),
				'amount_base_deductible_ht' => array('type'=>'price', 'label'=>'AssetDepreciationOptionAmountBaseDeductibleHT', 'enabled'=>'isset($object)&&get_class($object)=="Asset"', 'only_on_asset'=>1, 'position'=>90, 'notnull'=>0, 'visible'=>1, 'default'=>'0', 'isameasure'=>'1', 'validate'=>'1',),
				'total_amount_last_depreciation_ht' => array('type'=>'price', 'label'=>'AssetDepreciationOptionTotalAmountLastDepreciationHT', 'enabled'=>'isset($object)&&get_class($object)=="Asset"', 'only_on_asset'=>1, 'position'=>100, 'noteditable'=> 1, 'notnull'=>0, 'visible'=>1, 'default'=>'0', 'isameasure'=>'1', 'validate'=>'1',),
			),
		),
	);
	public $fk_asset;
	public $fk_asset_model;
	public $tms;
	public $fk_user_modif;

	/**
	 * @var array  Array with all deprecation options by mode.
	 */
	public $deprecation_options = array();

	/**
	 * Constructor
	 *
	 * @param DoliDb $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		global $langs;
		$this->db = $db;

		// Translate some data of arrayofkeyval
		if (is_object($langs)) {
			foreach ($this->deprecation_options_fields as $mode_key => $mode_info) {
				if (!empty($mode_info['fields']) && is_array($mode_info['fields'])) {
					foreach ($mode_info['fields'] as $field_key => $field_info) {
						if (!empty($field_info['arrayofkeyval']) && is_array($field_info['arrayofkeyval'])) {
							foreach ($field_info['arrayofkeyval'] as $key => $val) {
								$this->deprecation_options_fields[$mode_key]['fields'][$field_key]['arrayofkeyval'][$key] = $langs->trans($val);
							}
						}
					}
				}
			}
		}
	}

	/**
	 *  Set object infos for a mode
	 *
	 * @param	string		$mode			Depreciation mode (economic, accelerated_depreciation, ...)
	 * @param	int			$class_type		Type (0:asset, 1:asset model)
	 * @param	bool		$all_field		Get all fields
	 * @return	int							Return integer <0 if KO, >0 if OK
	 */
	public function setInfosForMode($mode, $class_type = 0, $all_field = false)
	{
		// Clean parameters
		$mode = strtolower(trim($mode));

		if (!empty($this->deprecation_options_fields[$mode])) {
			$this->table_element = $this->deprecation_options_fields[$mode]['table'];
			$this->fields = $this->deprecation_options_fields[$mode]['fields'];
			foreach ($this->fields as $field_key => $field_info) {
				if ((!empty($field_info['computed']) && !$all_field) || (!empty($field_info['only_on_asset']) && !empty($class_type))) {
					unset($this->fields[$field_key]);
					continue;
				}

				// Unset required option (notnull) if field disabled
				if (!empty($field_info['enabled_field'])) {
					$info = explode(':', $field_info['enabled_field']);
					if ($this->deprecation_options[$info[0]][$info[1]] != $info[2] && isset($this->fields[$field_key]['notnull'])) {
						unset($this->fields[$field_key]['notnull']);
					}
				}
				// Set value of the field in the object (for createCommon and setDeprecationOptionsFromPost functions)
				$this->{$field_key} = $this->deprecation_options[$mode][$field_key];
			}

			$this->fields['rowid'] = array('type'=>'integer', 'label'=>'TechnicalID', 'enabled'=>'1', 'position'=>1, 'notnull'=>1, 'visible'=>0, 'noteditable'=>'1', 'index'=>1, 'css'=>'left', 'comment'=>"Id");
			if (empty($class_type)) {
				$this->fields['fk_asset'] = array('type' => 'integer:Asset:asset/class/asset.class.php:1:status=1 AND entity IN (__SHARED_ENTITIES__)', 'label' => 'Asset', 'enabled' => '1', 'position' => 0, 'notnull' => 0, 'visible' => 0, 'index' => 1, 'validate' => '1',);
			} else {
				$this->fields['fk_asset_model'] = array('type' => 'integer:AssetModel:asset/class/assetmodel.class.php:1:status=1 AND entity IN (__SHARED_ENTITIES__)', 'label' => 'AssetModel', 'enabled' => '1', 'position' => 0, 'notnull' => 0, 'visible' => 0, 'index' => 1, 'validate' => '1',);
			}
			$this->fields['tms'] = array('type' => 'timestamp', 'label' => 'DateModification', 'enabled' => '1', 'position' => 501, 'notnull' => 0, 'visible' => 0,);
			$this->fields['fk_user_modif'] = array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserModif', 'enabled' => '1', 'position' => 511, 'notnull' => -1, 'visible' => 0,);
		}

		return 1;
	}

	/**
	 *  Fill deprecation_options property of object (using for data sent by forms)
	 *
	 * @param	int		$class_type		Type (0:asset, 1:asset model)
	 * @return	int						Return integer <0 if KO, >0 if OK
	 */
	public function setDeprecationOptionsFromPost($class_type = 0)
	{
		global $conf, $langs;

		$error = 0;

		$deprecation_options = array();
		foreach ($this->deprecation_options_fields as $mode_key => $mode_info) {
			$this->setInfosForMode($mode_key, $class_type);

			foreach ($mode_info['fields'] as $field_key => $field_info) {
				if (!empty($field_info['computed'])) {
					continue;
				}

				$html_name = $mode_key . '_' . $field_key;
				if ($field_info['type'] == 'duration') {
					if (GETPOST($html_name . 'hour') == '' && GETPOST($html_name . 'min') == '') {
						continue; // The field was not submited to be saved
					}
				} else {
					if (!GETPOSTISSET($html_name)) {
						continue; // The field was not submited to be saved
					}
				}
				// Ignore special fields
				if (in_array($field_key, array('rowid', 'entity', 'import_key'))) {
					continue;
				}
				if (in_array($field_key, array('date_creation', 'tms', 'fk_user_creat', 'fk_user_modif'))) {
					if (!in_array(abs($field_info['visible']), array(1, 3))) {
						continue; // Only 1 and 3 that are case to create
					}
				}

				// Set value to insert
				if (in_array($field_info['type'], array('text', 'html'))) {
					$value = GETPOST($html_name, 'restricthtml');
				} elseif ($field_info['type'] == 'date') {
					$value = dol_mktime(12, 0, 0, GETPOST($html_name . 'month', 'int'), GETPOST($html_name . 'day', 'int'), GETPOST($html_name . 'year', 'int')); // for date without hour, we use gmt
				} elseif ($field_info['type'] == 'datetime') {
					$value = dol_mktime(GETPOST($html_name . 'hour', 'int'), GETPOST($html_name . 'min', 'int'), GETPOST($html_name . 'sec', 'int'), GETPOST($html_name . 'month', 'int'), GETPOST($html_name . 'day', 'int'), GETPOST($html_name . 'year', 'int'), 'tzuserrel');
				} elseif ($field_info['type'] == 'duration') {
					$value = 60 * 60 * GETPOST($html_name . 'hour', 'int') + 60 * GETPOST($html_name . 'min', 'int');
				} elseif (preg_match('/^(integer|price|real|double)/', $field_info['type'])) {
					$value = price2num(GETPOST($html_name, 'alphanohtml')); // To fix decimal separator according to lang setup
				} elseif ($field_info['type'] == 'boolean') {
					$value = ((GETPOST($html_name) == '1' || GETPOST($html_name) == 'on') ? 1 : 0);
				} elseif ($field_info['type'] == 'reference') {
					// todo to check
					$tmparraykey = array(); //array_keys($object->param_list);
					$value = $tmparraykey[GETPOST($html_name)] . ',' . GETPOST($html_name . '2');
				} else {
					if ($field_key == 'lang') {
						$value = GETPOST($html_name, 'aZ09') ? GETPOST($html_name, 'aZ09') : "";
					} else {
						$value = GETPOST($html_name, 'alphanohtml');
					}
				}
				if (preg_match('/^integer:/i', $field_info['type']) && $value == '-1') {
					$value = ''; // This is an implicit foreign key field
				}
				if (!empty($field_info['foreignkey']) && $value == '-1') {
					$value = ''; // This is an explicit foreign key field
				}

				//var_dump($field_key.' '.$value.' '.$field_info['type']);
				$field_value = $value;
				if ($field_info['notnull'] > 0 && $field_value == '' && !is_null($field_info['default']) && $field_info['default'] == '(PROV)') {
					$field_value = '(PROV)';
				} elseif ((!empty($field_info['required']) || $field_info['notnull'] > 0) && $field_value == '' && !empty($field_info['default'])) {
					$field_value = dol_eval($field_info['default'], 1);
				}
				if ($field_info['notnull'] > 0 && $field_value == '' && is_null($field_info['default'])) {
					$error++;
					setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv($field_info['label'])), null, 'errors');
				}
				$deprecation_options[$mode_key][$field_key] = $field_value;

				// Validation of fields values
				if (getDolGlobalInt('MAIN_FEATURE_LEVEL') >= 1 || getDolGlobalString('MAIN_ACTIVATE_VALIDATION_RESULT')) {
					if (!$error && !empty($field_info['validate']) && is_callable(array($this, 'validateField'))) {
						if (!$this->validateField($mode_info['fields'], $field_key, $value)) {
							$error++;
						}
					}
				}
			}
		}
		// Unset not enabled modes
		foreach ($this->deprecation_options_fields as $mode_key => $mode_info) {
			if (!empty($mode_info['enabled_field'])) {
				$info = explode(':', $mode_info['enabled_field']);
				if ($deprecation_options[$info[0]][$info[1]] != $info[2]) {
					unset($deprecation_options[$info[0]][$info[1]]);
				}
			}
		}
		$this->deprecation_options = $deprecation_options;

		if ($error) {
			return -1;
		} else {
			return 1;
		}
	}

	/**
	 *  Load deprecation options of a asset or a asset model
	 *
	 * @param	int		$asset_id			Asset ID to set
	 * @param	int		$asset_model_id		Asset model ID to set
	 * @return	int							Return integer <0 if KO, >0 if OK
	 */
	public function fetchDeprecationOptions($asset_id = 0, $asset_model_id = 0)
	{
		global $langs, $hookmanager;
		dol_syslog(__METHOD__ . " asset_id=$asset_id, asset_model_id=$asset_model_id");

		$error = 0;
		$this->errors = array();
		$this->deprecation_options = array();

		// Clean parameters
		$asset_id = $asset_id > 0 ? $asset_id : 0;
		$asset_model_id = $asset_model_id > 0 ? $asset_model_id : 0;

		$hookmanager->initHooks(array('assetdepreciationoptionsdao'));
		$parameters = array('asset_id' => $asset_id, 'asset_model_id' => $asset_model_id);
		$reshook = $hookmanager->executeHooks('fetchDepreciationOptions', $parameters, $this); // Note that $action and $object may have been modified by some hooks
		if (!empty($reshook)) {
			return $reshook;
		}

		// Check parameters
		if (empty($asset_id) && empty($asset_model_id)) {
			$this->errors[] = $langs->trans('AssetErrorAssetOrAssetModelIDNotProvide');
			$error++;
		}
		if ($error) {
			dol_syslog(__METHOD__ . " Error check parameters: " . $this->errorsToString(), LOG_ERR);
			return -1;
		}

		$class_type = $asset_id > 0 ? 0 : 1;
		$deprecation_options = array();
		foreach ($this->deprecation_options_fields as $mode_key => $mode_info) {
			$this->setInfosForMode($mode_key, $class_type);

			$result = $this->fetchCommon(0, '', " AND " . ($asset_id > 0 ? " fk_asset = " . (int) $asset_id : " fk_asset_model = " . (int) $asset_model_id));
			if ($result < 0) {
				$this->errors = array_merge(array($langs->trans('AssetErrorFetchDepreciationOptionsForMode', $mode_key) . ':'), $this->errors);
				$error++;
			} elseif ($result > 0) {
				foreach ($this->fields as $field_key => $field_info) {
					if (in_array($field_key, array('rowid', 'fk_asset', 'fk_asset_model', 'tms', 'fk_user_modif'))) {
						continue;
					}
					$deprecation_options[$mode_key][$field_key] = $this->{$field_key};
				}
			}
		}
		// Unset not enabled modes
		foreach ($this->deprecation_options_fields as $mode_key => $mode_info) {
			if (!empty($mode_info['enabled_field'])) {
				$info = explode(':', $mode_info['enabled_field']);
				if ($deprecation_options[$info[0]][$info[1]] != $info[2]) {
					unset($deprecation_options[$info[0]][$info[1]]);
				}
			}
		}

		if ($error) {
			dol_syslog(__METHOD__ . " Error fetch accountancy codes: " . $this->errorsToString(), LOG_ERR);
			return -1;
		} else {
			$this->deprecation_options = $deprecation_options;
			return 1;
		}
	}

	/**
	 *  get general depreciation info for a mode (used in depreciation card)
	 *
	 * @param	string			$mode		Depreciation mode (economic, accelerated_depreciation, ...)
	 * @return	array|int					Return integer <0 if KO otherwise array with general depreciation info
	 */
	public function getGeneralDepreciationInfoForMode($mode)
	{
		global $hookmanager;
		dol_syslog(__METHOD__ . " mode=$mode");

		$this->errors = array();

		// Clean parameters
		$mode = strtolower(trim($mode));

		$hookmanager->initHooks(array('assetdepreciationoptionsdao'));
		$parameters = array('mode' => $mode);
		$reshook = $hookmanager->executeHooks('getGeneralDepreciationInfoForMode', $parameters, $this); // Note that $action and $object may have been modified by some hooks
		if ($reshook < 0) {
			return $reshook;
		} elseif ($reshook > 0) {
			return $hookmanager->resArray;
		}

		$duration_type_list = $this->deprecation_options_fields[$mode]['fields']['duration_type']['arrayofkeyval'];

		return array(
			'base_depreciation_ht' => $this->deprecation_options[$mode]['amount_base_depreciation_ht'],
			'duration' => $this->deprecation_options[$mode]['duration'],
			'duration_type' => $duration_type_list[$this->deprecation_options[$mode]['duration_type']],
			'rate' => $this->getRate($mode),
		);
	}

	/**
	 *	Update deprecation options of a asset or a asset model
	 *
	 * @param	User	$user				User making update
	 * @param	int		$asset_id			Asset ID to set
	 * @param	int		$asset_model_id		Asset model ID to set
	 * @param	int		$notrigger			1=disable trigger UPDATE (when called by create)
	 * @return	int							Return integer <0 if KO, >0 if OK
	 */
	public function updateDeprecationOptions($user, $asset_id = 0, $asset_model_id = 0, $notrigger = 0)
	{
		global $langs, $hookmanager;
		dol_syslog(__METHOD__ . " user_id=".$user->id.", asset_id=".$asset_id.", asset_model_id=".$asset_model_id.", notrigger=".$notrigger);

		$error = 0;
		$this->errors = array();

		// Clean parameters
		$asset_id = $asset_id > 0 ? $asset_id : 0;
		$asset_model_id = $asset_model_id > 0 ? $asset_model_id : 0;

		$hookmanager->initHooks(array('assetdepreciationoptionsdao'));
		$parameters = array('user' => $user, 'asset_id' => $asset_id, 'asset_model_id' => $asset_model_id);
		$reshook = $hookmanager->executeHooks('updateDepreciationOptions', $parameters, $this); // Note that $action and $object may have been modified by some hooks
		if (!empty($reshook)) {
			return $reshook;
		}

		// Check parameters
		if (empty($asset_id) && empty($asset_model_id)) {
			$this->errors[] = $langs->trans('AssetErrorAssetOrAssetModelIDNotProvide');
			$error++;
		}
		if ($error) {
			dol_syslog(__METHOD__ . " Error check parameters: " . $this->errorsToString(), LOG_ERR);
			return -1;
		}

		$this->db->begin();

		if ($asset_id > 0) {
			$this->fk_asset = $asset_id;
			$class_type = 0;
		} else {
			$this->fk_asset_model = $asset_model_id;
			$class_type = 1;
		}
		$this->tms = dol_now();
		$this->fk_user_modif = $user->id;

		foreach ($this->deprecation_options_fields as $mode_key => $mode_info) {
			// Delete old accountancy codes
			$sql = "DELETE FROM " . MAIN_DB_PREFIX . $mode_info['table'];
			$sql .= " WHERE " . ($asset_id > 0 ? " fk_asset = " . (int) $asset_id : " fk_asset_model = " . (int) $asset_model_id);
			$resql = $this->db->query($sql);
			if (!$resql) {
				$this->errors[] = $langs->trans('AssetErrorDeleteDepreciationOptionsForMode', $mode_key) . ': ' . $this->db->lasterror();
				$error++;
			}

			if (!$error && !empty($this->deprecation_options[$mode_key])) {
				if (!empty($mode_info['enabled_field'])) {
					$info = explode(':', $mode_info['enabled_field']);
					if ($this->deprecation_options[$info[0]][$info[1]] != $info[2]) {
						continue;
					}
				}

				$this->setInfosForMode($mode_key, $class_type);

				$result = $this->createCommon($user, 1);
				if ($result < 0) {
					$this->errors = array_merge(array($langs->trans('AssetErrorInsertDepreciationOptionsForMode', $mode_key) . ':'), $this->errors);
					$error++;
				}
			}
		}

		if (!$error && $this->fk_asset > 0) {
			// Calculation of depreciation lines (reversal and future)
			require_once DOL_DOCUMENT_ROOT . '/asset/class/asset.class.php';
			$asset = new Asset($this->db);
			$result = $asset->fetch($this->fk_asset);
			if ($result > 0) {
				$result = $asset->calculationDepreciation();
			}
			if ($result < 0) {
				$this->errors[] = $langs->trans('AssetErrorCalculationDepreciationLines');
				$this->errors[] = $asset->errorsToString();
				$error++;
			}
		}

		if (!$error && !$notrigger) {
			// Call trigger
			$result = $this->call_trigger('ASSET_DEPRECIATION_OPTIONS_MODIFY', $user);
			if ($result < 0) {
				$error++;
			}
			// End call triggers
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
	 *  Get rate
	 *
	 * @param	string			$mode		Depreciation mode (economic, accelerated_depreciation, ...)
	 * @return	string						Rate of the provided mode option
	 */
	public function getRate($mode)
	{
		$duration = $this->deprecation_options[$mode]["duration"] > 0 ? $this->deprecation_options[$mode]["duration"] : 0;
		$duration_type = $this->deprecation_options[$mode]["duration_type"] > 0 ? $this->deprecation_options[$mode]["duration_type"] : 0;

		return price(price2num($duration > 0 ? (100 * ($duration_type == 1 ? 12 : 1) / $duration) : 0, 2));
	}
}
