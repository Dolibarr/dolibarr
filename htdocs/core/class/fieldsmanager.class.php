<?php
/* Copyright (C) 2002-2003  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2002-2003  Jean-Louis Bergamo      <jlb@j1b.org>
 * Copyright (C) 2004       Sebastien Di Cintio     <sdicintio@ressource-toi.org>
 * Copyright (C) 2004       Benoit Mortier          <benoit.mortier@opensides.be>
 * Copyright (C) 2009-2012  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2009-2012  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2013       Florian Henry           <forian.henry@open-concept.pro>
 * Copyright (C) 2015       Charles-Fr BENKE        <charles.fr@benke.fr>
 * Copyright (C) 2016       Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2017       Nicolas ZABOURI         <info@inovea-conseil.com>
 * Copyright (C) 2018-2022  Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2022 		Antonin MARCHAL         <antonin@letempledujeu.fr>
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
 *    \file        htdocs/core/class/fieldsmanager.class.php
 *    \ingroup    core
 *    \brief      File of class to manage fields
 */

require_once DOL_DOCUMENT_ROOT . '/core/class/fieldinfos.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/fields/commonfield.class.php';


/**
 *    Class to manage fields
 */
class FieldsManager
{
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * @var string Error code (or message)
	 */
	public $error = '';

	/**
	 * @var string[] Array of Error code (or message)
	 */
	public $errors = array();

	/**
	 * @var array<string,string>	To store error results of ->validateField()
	 */
	public $validateFieldsErrors = array();

	/**
	 * @var string Path to fields classes
	 */
	public $fieldsPath = '/core/class/fields/';

	/**
	 * @var array<string,CommonField> Field classes cached
	 */
	public static $fieldClasses = array();

	/**
	 * @var array<string,array<string,array{object:array<string,FieldInfos>,extraField:array<string,FieldInfos>}>> Field infos cached (array<element,array<mode,{object:array<fieldKey,fieldInfos>,extraField:array<fieldKey,fieldInfos>}>>)
	 */
	public static $fieldInfos = array();

	/**
	 * @var array<string,bool|int<0,1>>|null	Array with boolean of status of groups
	 */
	public $expand_display = array();

	///**
	// * @var array<string,string>    Array of type to label
	// */
	//public static $type2label = array(
	//	'varchar' => 'String1Line',
	//	'text' => 'TextLongNLines',
	//	'html' => 'HtmlText',
	//	'int' => 'Int',
	//	'double' => 'Float',
	//	'date' => 'Date',
	//	'datetime' => 'DateAndTime',
	//	'duration' => 'Duration',
	//	//'datetimegmt'=>'DateAndTimeUTC',
	//	'boolean' => 'Boolean',
	//	'price' => 'ExtrafieldPrice',
	//	'pricecy' => 'ExtrafieldPriceWithCurrency',
	//	'phone' => 'ExtrafieldPhone',
	//	'email' => 'ExtrafieldMail',
	//	'url' => 'ExtrafieldUrl',
	//	'ip' => 'ExtrafieldIP',
	//	'icon' => 'Icon',
	//	'password' => 'ExtrafieldPassword',
	//	'radio' => 'ExtrafieldRadio',
	//	'select' => 'ExtrafieldSelect',
	//	'sellist' => 'ExtrafieldSelectList',
	//	'checkbox' => 'ExtrafieldCheckBox',
	//	'chkbxlst' => 'ExtrafieldCheckBoxFromList',
	//	'link' => 'ExtrafieldLink',
	//	'point' => 'ExtrafieldPointGeo',
	//	'multipts' => 'ExtrafieldMultiPointGeo',
	//	'linestrg' => 'ExtrafieldLinestringGeo',
	//	'polygon' => 'ExtrafieldPolygonGeo',
	//	'separate' => 'ExtrafieldSeparator',
	//	'stars' => 'ExtrafieldStars',
	//	//'real' => 'ExtrafieldReal',
	//);


	/**
	 *    Constructor
	 *
	 * @param DoliDB		$db 		Database handler
	 * @param Form|null		$form		Specific form handler
	 */
	public function __construct($db, $form = null)
	{
		$this->db = $db;
		$this->error = '';
		$this->errors = array();

		if (isset($form)) {
			CommonField::setForm($form);
		}
	}

	/**
	 * Get field handler for the provided type
	 *
	 * @param	string				$type		Field type
	 * @return	CommonField|null
	 */
	public function getFieldClass($type)
	{
		global $hookmanager, $langs;

		$type = trim($type);

		if (!isset(self::$fieldClasses[$type])) {
			$field = null;
			$parameters = array(
				'type' => $type,
				// @phan-suppress-next-line PhanPluginConstantVariableNull
				'field' => &$field,
			);

			$hookmanager->executeHooks('getFieldClass', $parameters, $this); // Note that $object may have been modified by hook
			// @phpstan-ignore-next-line @phan-suppress-next-line PhanPluginConstantVariableNull
			if (isset($field) && is_object($field)) {
				self::$fieldClasses[$type] = $field;
			} else {
				$filename = strtolower($type) . 'field.class.php';
				$classname = ucfirst($type) . 'Field';

				// Load class file
				dol_include_once(rtrim($this->fieldsPath, '/') . '/' . $filename);
				if (!class_exists($classname)) {
					@include_once DOL_DOCUMENT_ROOT . '/core/class/fields/' . $filename;
				}

				if (class_exists($classname)) {
					self::$fieldClasses[$type] = new $classname($this->db);
				} else {
					$langs->load("errors");
					$this->errors[] = $langs->trans('ErrorFieldClassNotFoundForClassName', $classname, $type);
					return null;
				}
			}
		}

		$field = self::$fieldClasses[$type];
		$field->clearErrors();

		return $field;
	}

	/**
	 * Get all fields handler available
	 *
	 * @return	array<string,CommonField>
	 */
	public function getAllFields()
	{
		// Todo to make
		return self::$fieldClasses;
	}

	/**
	 * clear errors
	 *
	 * @return	void
	 */
	public function clearErrors()
	{
		$this->error = '';
		$this->errors = array();
	}

	/**
	 * Method to output saved errors
	 *
	 * @param   string      $separator      Separator between each error
	 * @return	string		                String with errors
	 */
	public function errorsToString($separator = ', ')
	{
		return $this->error . (is_array($this->errors) ? (!empty($this->error) ? $separator : '') . implode($separator, $this->errors) : '');
	}

	/**
	 * clear validation message result for a field
	 *
	 * @param	string	$fieldKey	Key of attribute to clear
	 * @return	void
	 */
	public function clearFieldError($fieldKey)
	{
		$this->error = '';
		unset($this->validateFieldsErrors[$fieldKey]);
	}

	/**
	 * set validation error message a field
	 *
	 * @param	string	$fieldKey	Key of attribute
	 * @param	string	$msg		the field error message
	 * @return	void
	 */
	public function setFieldError($fieldKey, $msg = '')
	{
		global $langs;
		if (empty($msg)) {
			$msg = $langs->trans("UnknownError");
		}

		$this->error = $this->validateFieldsErrors[$fieldKey] = $msg;
	}

	/**
	 * get field error message
	 *
	 * @param	string	$fieldKey	Key of attribute
	 * @return	string              Error message of validation ('' if no error)
	 */
	public function getFieldError($fieldKey)
	{
		if (!empty($this->validateFieldsErrors[$fieldKey])) {
			return $this->validateFieldsErrors[$fieldKey];
		}
		return '';
	}

	/**
	 * get field error icon
	 *
	 * @param  string  $fieldValidationErrorMsg	message to add in tooltip
	 * @return string							html output
	 */
	public function getFieldErrorIcon($fieldValidationErrorMsg)
	{
		$out = '';

		if (!empty($fieldValidationErrorMsg) && function_exists('getFieldErrorIcon')) {
			$out .= ' ' . getFieldErrorIcon($fieldValidationErrorMsg);
		}

		return $out;
	}

	/**
	 *	Get list of fields infos for the provided mode into X columns
	 *
	 * @param	CommonObject																				$object			Object handler
	 * @param	ExtraFields																					$extrafields	ExtraFields handler
	 * @param	string																						$mode			Get the fields infos for the provided mode ('create', 'edit', 'view', 'list')
	 * @param	int																							$nbColumn		Split fields infos into X columns
	 * @param	array<int,string>																			$breakKeys		Key used for break on each column (ex: array(1 => 'total_ht', ...))
	 * @param	array<string,mixed>																			$params			Other params
	 * @return	array{columns:array<int,array<string,FieldInfos>>,hiddenFields:array<string,FieldInfos>}					List of fields info by column and hidden
	 */
	public function getAllFieldsInfos(&$object, &$extrafields = null, $mode = 'view', $nbColumn = 2, $breakKeys = array(), $params = array())
	{
		global $hookmanager, $langs;

		// Get object fields
		$fields = $this->getAllObjectFieldsInfos($object, $mode, $params);

		// Old sort
		if (!getDolGlobalInt('MAIN_FIELDS_SORT_WITH_EXTRA_FIELDS')) {
			$fields = dol_sort_array($fields, 'position');
		}

		// Get extra fields
		$fields2 = $this->getAllExtraFieldsInfos($object, $extrafields, $mode, $params);
		$fields = array_merge($fields, $fields2);

		// New sort
		if (getDolGlobalInt('MAIN_FIELDS_SORT_WITH_EXTRA_FIELDS')) {
			$fields = dol_sort_array($fields, 'position');
		}

		// Split in columns
		$idxColumn = 1;
		$columns = array();
		$hiddenFields = array();
		$columns[$idxColumn] = array();
		$nbVisibleFields = 0;
		foreach ($fields as $field) {
			if ($field->visible) {
				$nbVisibleFields++;
			}
		}
		$nbFieldsByColumn = ceil($nbVisibleFields / $nbColumn);
		$breakKey = $breakKeys[$idxColumn] ?? '';
		$idxField = 0;
		foreach ($fields as $key => $field) {
			if ($idxColumn < $nbColumn && ((!empty($breakKey) && $key == $breakKey) || (empty($breakKey) && $idxField == $nbFieldsByColumn))) {
				$idxColumn++;
				$idxField = 0;
				$columns[$idxColumn] = array();
			}

			if ($field->visible) {
				if ($field->type != 'separate') {
					$idxField++;
				}

				// Add field into column
				$columns[$idxColumn][$key] = $field;
			} else {
				$hiddenFields[$key] = $field;
			}
		}

		// Add column not created
		for ($idxColumn = 1; $idxColumn <= $nbColumn; $idxColumn++) {
			if (!isset($columns[$idxColumn])) {
				$columns[$idxColumn] = array();
			}
		}

		$parameters = array(
			'object' => &$object,
			'extrafields' => &$extrafields,
			'mode' => $mode,
			'nbColumn' => $nbColumn,
			'breakKeys' => $breakKeys,
			'params' => $params,
			'columns' => &$columns,
			'hiddenFields' => &$hiddenFields,
		);

		$hookmanager->executeHooks('getFieldInfosFromObjectField', $parameters, $this); // Note that $object may have been modified by hook

		return array(
			'columns' => $columns,
			'hiddenFields' => $hiddenFields,
		);
	}

	/**
	 *	Get list of object fields infos
	 *
	 * @param	CommonObject				$object			Object handler
	 * @param	string						$mode			Get the fields infos for the provided mode ('create', 'edit', 'view', 'list')
	 * @param	array<string,mixed>			$params			Other params
	 * @return	array<string,FieldInfos>					List of fields infos
	 */
	public function getAllObjectFieldsInfos(&$object, $mode = 'view', $params = array())
	{
		global $hookmanager;

		// Get object fields
		$fields = array();
		// @phpstan-ignore-next-line
		if (isset($object->fields) && is_array($object->fields)) {
			$keyPrefix = getDolGlobalInt('MAIN_FIELDS_NEW_OBJECT_KEY_PREFIX') ? 'object_' : '';
			foreach ($object->fields as $key => $field) {
				$fieldInfos = $this->getFieldInfosFromObjectField($object, $key, $mode, $params);
				$fields[$keyPrefix . $key] = $fieldInfos;
			}
		}

		$parameters = array(
			'object' => &$object,
			'mode' => $mode,
			'params' => $params,
			'fields' => &$fields,
		);

		$hookmanager->executeHooks('getAllObjectFieldsInfos', $parameters, $this); // Note that $object may have been modified by hook

		return $fields;
	}

	/**
	 *	Get list of extra fields infos
	 *
	 * @param	CommonObject					$object			Object handler
	 * @param	ExtraFields						$extrafields	ExtraFields handler
	 * @param	string							$mode			Get the fields infos for the provided mode ('create', 'edit', 'view', 'list')
	 * @param	array<string,mixed>				$params			Other params
	 * @return  array<string,FieldInfos>                    	List of fields infos
	 */
	public function getAllExtraFieldsInfos(&$object, &$extrafields = null, $mode = 'view', $params = array())
	{
		global $hookmanager;

		// Get extra fields
		$fields = array();
		if (isset($extrafields->attributes[$object->table_element]) && is_array($extrafields->attributes[$object->table_element])) {
			if (isset($extrafields->attributes[$object->table_element]['label']) && is_array($extrafields->attributes[$object->table_element]['label'])) {
				$keyPrefix = 'options_';
				foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $label) {
					$fieldInfos = $this->getFieldInfosFromExtraField($object, $extrafields, $key, $mode, $params);
					$fields[$keyPrefix . $key] = $fieldInfos;
				}
			}
		}

		$parameters = array(
			'object' => &$object,
			'extrafields' => &$extrafields,
			'mode' => $mode,
			'params' => $params,
			'fields' => &$fields,
		);

		$hookmanager->executeHooks('getAllExtraFieldsInfos', $parameters, $this); // Note that $object may have been modified by hook

		return $fields;
	}

	/**
	 *	Get list of fields infos for the provided mode into X columns
	 *
	 * @param	string					$key			Field key (begin by object_ for object or options_ for extrafields)
	 * @param	CommonObject			$object			Object handler
	 * @param	ExtraFields				$extrafields	ExtraFields handler
	 * @param	string					$mode			Get the fields infos for the provided mode ('create', 'edit', 'view', 'list')
	 * @param	array<string,mixed>		$params			Other params
	 * @return	FieldInfos|null							Get field info or null if not found
	 */
	public function getFieldsInfos($key, &$object, &$extrafields = null, $mode = 'view', $params = array())
	{
		global $langs;

		$fieldInfos = null;

		$patternObjectPrefix = getDolGlobalInt('MAIN_FIELDS_NEW_OBJECT_KEY_PREFIX') ? 'object_' : '';
		if (preg_match('/^options_(.*)/i', $key, $matches)) {
			$fieldKey = $matches[1];
			$fieldInfos = $this->getFieldInfosFromExtraField($object, $extrafields, $fieldKey, $mode, $params);
		} elseif (preg_match('/^' . $patternObjectPrefix . '(.*)/i', $key, $matches)) {
			$fieldKey = $matches[2];
			$fieldInfos = $this->getFieldInfosFromObjectField($object, $fieldKey, $mode, $params);
		}

		return $fieldInfos;
	}

	/**
	 * Get field infos from object field infos
	 *
	 * @param	CommonObject			$object		Object handler
	 * @param	string					$key		Field key
	 * @param	string					$mode		Get the fields infos for the provided mode ('create', 'edit', 'view', 'list')
	 * @param	array<string,mixed>		$params		Other params
	 * @return	FieldInfos|null						Properties of the field or null if field not found
	 */
	public function getFieldInfosFromObjectField(&$object, $key, $mode = 'view', $params = array())
	{
		global $hookmanager;

		if (!isset($object->fields[$key])) {
			return null;
		}

		if (isset(self::$fieldInfos[$object->element][$mode]['object'][$key])) {
			return self::$fieldInfos[$object->element][$mode]['object'][$key];
		}

		$attributes = $object->fields[$key];

		$fieldInfos = new FieldInfos();
		$fieldInfos->fieldType = FieldInfos::FIELD_TYPE_OBJECT;
		$fieldInfos->originType = $attributes['type'] ?? '';
		$fieldInfos->size = $attributes['length'] ?? '';
		$fieldInfos->label = $attributes['label'] ?? '';
		$fieldInfos->langFile = $attributes['langfile'] ?? '';
		$fieldInfos->sqlAlias = $attributes['alias'] ?? null;
		$fieldInfos->picto = $attributes['picto'] ?? '';
		$fieldInfos->position = $attributes['position'] ?? 0;
		$fieldInfos->required = ($attributes['notnull'] ?? 0) > 0;
		$fieldInfos->alwaysEditable = !empty($attributes['alwayseditable']);
		$fieldInfos->defaultValue = $attributes['default'] ?? '';
		$fieldInfos->css = $attributes['css'] ?? '';
		$fieldInfos->viewCss = $attributes['cssview'] ?? '';
		$fieldInfos->listCss = $attributes['csslist'] ?? '';
		$fieldInfos->inputPlaceholder = $attributes['placeholder'] ?? '';
		$fieldInfos->help = $attributes['help'] ?? '';
		$fieldInfos->listHelp = $attributes['helplist'] ?? '';
		$fieldInfos->showOnComboBox = !empty($attributes['showoncombobox']);
		$fieldInfos->inputDisabled = !empty($attributes['disabled']);
		$fieldInfos->inputAutofocus = !empty($attributes['autofocusoncreate']) && $mode == 'create';
		$fieldInfos->comment = $attributes['comment'] ?? '';
		$fieldInfos->listTotalizable = !empty($attributes['isameasure']) && $attributes['isameasure'] == 1;
		$fieldInfos->validateField = !empty($attributes['validate']);
		$fieldInfos->copyToClipboard = $attributes['copytoclipboard'] ?? 0;
		$fieldInfos->tdCss = $attributes['tdcss'] ?? '';
		$fieldInfos->multiInput = !empty($attributes['multiinput']);
		$fieldInfos->nameInClass = $attributes['nameinclass'] ?? $key;
		$fieldInfos->nameInTable = $attributes['nameintable'] ?? $key;
		$fieldInfos->getNameUrlParams = $attributes['get_name_url_params'] ?? null;
		$fieldInfos->showOnHeader = !empty($attributes['showonheader']);

		// TODO set nameinclass = "id" in fields "rowid"
		if ($fieldInfos->nameInClass == 'rowid') {
			$fieldInfos->nameInClass = 'id';
		}

		$enabled = $attributes['enabled'] ?? '1';
		$visibility = $attributes['visible'] ?? '1';
		$perms = empty($attributes['noteditable']) ? '1' : '0';

		$this->setCommonFieldInfos($fieldInfos, $object, $extrafields, $key, $mode, $enabled, $visibility, $perms, $params);

		// Special case that force options and type ($type can be integer, varchar, ...)
		if (!empty($attributes['arrayofkeyval']) && is_array($attributes['arrayofkeyval'])) {
			$fieldInfos->options = $attributes['arrayofkeyval'];
			// Special case that prevent to force $type to have multiple input @phan-suppress-next-line PhanTypeMismatchProperty
			if (!$fieldInfos->multiInput) {
				$fieldInfos->type = (($fieldInfos->type == 'checkbox') ? $fieldInfos->type : 'select');
			}
		}

		$parameters = array(
			'object' => &$object,
			'key' => $key,
			'mode' => $mode,
			'fieldInfos' => &$fieldInfos,
		);

		$hookmanager->executeHooks('getFieldInfosFromObjectField', $parameters, $this); // Note that $object may have been modified by hook

		self::$fieldInfos[$object->element][$mode]['object'][$key] = $fieldInfos;
		return $fieldInfos;
	}

	/**
	 * Get field infos from extra field infos
	 *
	 * @param	CommonObject			$object			Object handler
	 * @param	ExtraFields				$extrafields	Extrafields handler
	 * @param	string					$key			Field key
	 * @param	string					$mode			Get the fields infos for the provided mode ('create', 'edit', 'view', 'list')
	 * @param	array<string,mixed>		$params			Other params
	 * @return	FieldInfos|null							Properties of the field or null if not found
	 */
	public function getFieldInfosFromExtraField(&$object, &$extrafields, $key, $mode = 'view', $params = array())
	{
		global $hookmanager;

		if (!isset($extrafields->attributes[$object->table_element]['label'][$key])) {
			return null;
		}

		if (isset(self::$fieldInfos[$object->element][$mode]['extraField'][$key])) {
			return self::$fieldInfos[$object->element][$mode]['extraField'][$key];
		}

		$attributes = $extrafields->attributes[$object->table_element];

		$fieldInfos = new FieldInfos();
		$fieldInfos->fieldType = FieldInfos::FIELD_TYPE_EXTRA_FIELD;
		$fieldInfos->originType = $attributes['type'][$key] ?? '';
		$fieldInfos->label = $attributes['label'][$key] ?? '';
		$fieldInfos->position = $attributes['pos'][$key] ?? 0;
		$fieldInfos->required = !empty($attributes['required'][$key]);
		$fieldInfos->defaultValue = $attributes['default'][$key] ?? '';
		$fieldInfos->css = $attributes['css'][$key] ?? '';
		$fieldInfos->help = $attributes['help'][$key] ?? '';
		$fieldInfos->size = $attributes['size'][$key] ?? '';
		$fieldInfos->computed = $attributes['computed'][$key] ?? '';
		$fieldInfos->unique = !empty($attributes['unique'][$key]);
		$fieldInfos->alwaysEditable = !empty($attributes['alwayseditable'][$key]);
		$fieldInfos->emptyOnClone = !empty($attributes['emptyonclone'][$key]);
		$fieldInfos->langFile = $attributes['langfile'][$key] ?? '';
		$fieldInfos->printable = !empty($attributes['printable'][$key]);
		$fieldInfos->aiPrompt = $attributes['aiprompt'][$key] ?? '';
		$fieldInfos->viewCss = $attributes['cssview'][$key] ?? '';
		$fieldInfos->listCss = $attributes['csslist'][$key] ?? '';
		$fieldInfos->listTotalizable = !empty($attributes['totalizable'][$key]);
		$fieldInfos->options = array_diff_assoc($attributes['param'][$key]['options'] ?? array(), array('' => null)); // For remove case when not defined
		$fieldInfos->nameInClass = $key;
		$fieldInfos->nameInTable = $key;

		$enabled = $attributes['enabled'][$key] ?? '1';
		$visibility = $attributes['list'][$key] ?? '1';
		$perms = $attributes['perms'][$key] ?? null;

		$this->setCommonFieldInfos($fieldInfos, $object, $extrafields, $key, $mode, $enabled, $visibility, $perms, $params);

		$parameters = array(
			'object' => &$object,
			'extraFields' => &$extrafields,
			'key' => $key,
			'mode' => $mode,
			'fieldInfos' => &$fieldInfos,
		);

		$hookmanager->executeHooks('getFieldInfosFromExtraField', $parameters, $this); // Note that $object may have been modified by hook

		self::$fieldInfos[$object->element][$mode]['extraField'][$key] = $fieldInfos;
		return $fieldInfos;
	}

	/**
	 * Set common field infos
	 *
	 * @param	FieldInfos				$fieldInfos		Field infos to set with common infos
	 * @param	CommonObject			$object			Object handler
	 * @param	ExtraFields				$extrafields	Extrafields handler
	 * @param	string					$key			Field key
	 * @param	string					$mode			Get the fields infos for the provided mode ('create', 'edit', 'view', 'list')
	 * @param 	string					$enabled		Condition when the field must be managed (Example: 1 or 'getDolGlobalInt("MY_SETUP_PARAM")' or 'isModEnabled("multicurrency")' ...)
	 * @param 	string					$visibility		Condition when the field must be visible (Examples: 0=Not visible, 1=Visible on list and create/update/view forms, 2=Visible on list only, 3=Visible on create/update/view form only (not list), 4=Visible on list and update/view form (not create). 5=Visible on list and view form (not create/not update). 6=visible on list and update/view form (not create). Using a negative value means field is not shown by default on list but can be selected for viewing)
	 * @param 	string					$perms			Condition when the field must be editable
	 * @param	array<string,mixed>		$params			Other params
	 * @return	void
	 */
	public function setCommonFieldInfos(&$fieldInfos, &$object, &$extrafields, $key, $mode = 'view', $enabled = '1', $visibility = '', $perms = null, $params = array())
	{
		global $user;

		$fieldInfos->object = &$object;
		$fieldInfos->mode = preg_replace('/[^a-z0-9_]/i', '', $mode);
		$fieldInfos->type = $fieldInfos->originType;
		$fieldInfos->key = $key;
		$fieldInfos->otherParams = $params;

		if (preg_match('/^(integer|link):(.*):(.*):(.*):(.*)/i', $fieldInfos->originType, $reg)) {
			$fieldInfos->options = array($reg[2] . ':' . $reg[3] . ':' . $reg[4] . ':' . $reg[5] => 'N');
			$fieldInfos->type = 'link';
		} elseif (preg_match('/^(integer|link):(.*):(.*):(.*)/i', $fieldInfos->originType, $reg)) {
			$fieldInfos->options = array($reg[2] . ':' . $reg[3] . ':' . $reg[4] => 'N');
			$fieldInfos->type = 'link';
		} elseif (preg_match('/^(integer|link):(.*):(.*)/i', $fieldInfos->originType, $reg)) {
			$fieldInfos->options = array($reg[2] . ':' . $reg[3] . ($reg[1] == 'User' ? ':#getnomurlparam1=-1' : '') => 'N');
			$fieldInfos->type = 'link';
		} elseif (preg_match('/^(sellist):(.*):(.*):(.*):(.*)/i', $fieldInfos->originType, $reg)) {
			$fieldInfos->options = array($reg[2] . ':' . $reg[3] . ':' . $reg[4] . ':' . $reg[5] => 'N');
			$fieldInfos->type = 'sellist';
		} elseif (preg_match('/^(sellist):(.*):(.*):(.*)/i', $fieldInfos->originType, $reg)) {
			$fieldInfos->options = array($reg[2] . ':' . $reg[3] . ':' . $reg[4] => 'N');
			$fieldInfos->type = 'sellist';
		} elseif (preg_match('/^(sellist):(.*):(.*)/i', $fieldInfos->originType, $reg)) {
			$fieldInfos->options = array($reg[2] . ':' . $reg[3] => 'N');
			$fieldInfos->type = 'sellist';
		} elseif (preg_match('/^chkbxlst:(.*)/i', $fieldInfos->originType, $reg)) {
			$fieldInfos->options = array($reg[1] => 'N');
			$fieldInfos->type = 'chkbxlst';
		} elseif (preg_match('/varchar\((\d+)\)/', $fieldInfos->originType, $reg)) {
			$fieldInfos->options = array();
			$fieldInfos->type = 'varchar';
			$fieldInfos->size = $reg[1];
			$fieldInfos->maxLength = (int) $reg[1];
		} elseif (preg_match('/varchar/', $fieldInfos->originType)) {
			$fieldInfos->options = array();
			$fieldInfos->type = 'varchar';
		} elseif (preg_match('/stars\((\d+)\)/', $fieldInfos->originType, $reg)) {
			$fieldInfos->options = array();
			$fieldInfos->type = 'stars';
			$fieldInfos->size = $reg[1];
		} elseif (preg_match('/integer/', $fieldInfos->originType)) {
			$fieldInfos->type = 'int';
		} elseif ($fieldInfos->originType == 'mail') {
			$fieldInfos->type = 'email';
		} elseif (preg_match('/^(text):(.*)/i', $fieldInfos->originType, $reg)) {
			$fieldInfos->type = 'text';
			$fieldInfos->getPostCheck = $reg[2];
		} elseif (preg_match('/^(html):(.*)/i', $fieldInfos->originType, $reg)) {
			$fieldInfos->type = 'html';
			$fieldInfos->getPostCheck = $reg[2];
		} elseif (preg_match('/^double\(([0-9]+,[0-9]+)\)/', $fieldInfos->originType, $reg)) {
			$fieldInfos->type = 'double';
			$fieldInfos->size = $reg[1];
		}

		// Set visibility
		$visibility = (int) dol_eval((string) $visibility, 1, 1, '2');
		$absVisibility = abs($visibility);
		$enabled = (int) dol_eval((string) $enabled, 1, 1, '2');
		$fieldInfos->visible = true;
		if (empty($visibility) ||
			empty($enabled) ||
			($mode == 'create' && !in_array($absVisibility, array(1, 3, 6))) ||
			($mode == 'edit' && !in_array($absVisibility, array(1, 3, 4))) ||
			($mode == 'view' && (!in_array($absVisibility, array(1, 3, 4, 5)) || $fieldInfos->showOnHeader)) ||
			($mode == 'list' && $absVisibility == 3)
		) {
			$fieldInfos->visible = false;
		}

		// Set edit perms
		if (isset($perms)) {
			$perms = (int) dol_eval((string) $perms, 1, 1, '2');
		} else {
			//TODO Improve element and rights detection
			$mappingKeyForPerm = array(
				'fichinter' => 'ficheinter',
				'product' => 'produit',
				'project' => 'projet',
				'order_supplier' => 'supplier_order',
				'invoice_supplier' => 'supplier_invoice',
				'shipping' => 'expedition',
				'productlot' => 'stock',
				'facturerec' => 'facture',
				'mo' => 'mrp',
				'salary' => 'salaries',
				'member' => 'adherent',
			);
			$keyForPerm = $mappingKeyForPerm[$object->element] ?? $object->element;

			$perms = false;
			if (isset($user->rights->$keyForPerm)) {
				$perms = $user->hasRight($keyForPerm, 'creer') || $user->hasRight($keyForPerm, 'create') || $user->hasRight($keyForPerm, 'write');
			}
			if ($object->element == 'order_supplier' && !getDolGlobalString('MAIN_USE_NEW_SUPPLIERMOD')) {
				$perms = $user->hasRight('fournisseur', 'commande', 'creer');
			} elseif ($object->element == 'invoice_supplier' && !getDolGlobalString('MAIN_USE_NEW_SUPPLIERMOD')) {
				$perms = $user->hasRight('fournisseur', 'facture', 'creer');
			} elseif ($object->element == 'delivery') {
				$perms = $user->hasRight('expedition', 'delivery', 'creer');
			} elseif ($object->element == 'contact') {
				$perms = $user->hasRight('societe', 'contact', 'creer');
			}
		}
		// Manage always editable of extra field
		$isDraft = ((isset($object->statut) && $object->statut == 0) || (isset($object->status) && $object->status == 0));
		if ($mode == 'view' && !$isDraft && !$fieldInfos->alwaysEditable) {
			$perms = false;
		}
		// Case visible only in view so not editable
		if ($mode == 'view' && $absVisibility == 5) {
			$perms = false;
		}
		// Case field computed
		if (!empty($fieldInfos->computed)) {
			$perms = false;
		}
		$fieldInfos->editable = !empty($perms);

		// Set list info 'checked'
		$fieldInfos->listChecked = $mode == 'list' && $visibility > 0;
	}

	/**
	 * Set all values of the object (with extra field) from POST
	 *
	 * @param	CommonObject			$object			Object handler
	 * @param	ExtraFields				$extrafields	Extrafields handler
	 * @param	string					$keyPrefix		Prefix string to add into name and id of field (can be used to avoid duplicate names)
	 * @param	string					$keySuffix		Suffix string to add into name and id of field (can be used to avoid duplicate names)
	 * @param	string					$mode			Get the fields infos for the provided mode ('create', 'edit', 'view', 'list')
	 * @param	array<string,mixed>		$params			Other params
	 * @return	int										Result <0 if KO, >0 if OK
	 */
	public function setFieldValuesFromPost(&$object, &$extrafields, $keyPrefix = '', $keySuffix = '', $mode = 'view', $params = array())
	{
		$result = $this->setObjectFieldValuesFromPost($object, $keyPrefix, $keySuffix, $mode, $params);
		$result2 = $this->setExtraFieldValuesFromPost($object, $extrafields, $keyPrefix, $keySuffix, $mode, $params);

		return $result > 0 && $result2 > 0 ? 1 : -1;
	}

	/**
	 * Set all object values of the object from POST
	 *
	 * @param	CommonObject			$object			Object handler
	 * @param	string					$keyPrefix		Prefix string to add into name and id of field (can be used to avoid duplicate names)
	 * @param	string					$keySuffix		Suffix string to add into name and id of field (can be used to avoid duplicate names)
	 * @param	string					$mode			Get the fields infos for the provided mode ('create', 'edit', 'view', 'list')
	 * @param	array<string,mixed>		$params			Other params
	 * @return  int                                     Result <0 if KO, >0 if OK
	 */
	public function setObjectFieldValuesFromPost(&$object, $keyPrefix = '', $keySuffix = '', $mode = 'view', $params = array())
	{
		$fields = $this->getAllObjectFieldsInfos($object, $mode, $params);

		$error = 0;
		foreach ($fields as $fieldKey => $fieldInfos) {
			$check = true;
			$key = $fieldInfos->nameInClass ?? $fieldInfos->key;
			if ($fieldInfos->visible) {
				$check = $this->verifyPostFieldValue($fieldInfos, $fieldKey, $keyPrefix, $keySuffix);
			}
			if ($check) {
				$object->$key = $this->getPostFieldValue($fieldInfos, $fieldKey, $object->$key, $keyPrefix, $keySuffix);
			}
			if (!$fieldInfos->visible) {
				$check = $this->verifyFieldValue($fieldInfos, $fieldKey, $object->$key);
			}
			if (!$check) {
				$error++;
			}
		}

		return $error ? -1 : 1;
	}

	/**
	 * Set all extra field values of the object from POST
	 *
	 * @param	CommonObject			$object			Object handler
	 * @param	ExtraFields				$extrafields	Extrafields handler
	 * @param	string					$keyPrefix		Prefix string to add into name and id of field (can be used to avoid duplicate names)
	 * @param	string					$keySuffix		Suffix string to add into name and id of field (can be used to avoid duplicate names)
	 * @param	string					$mode			Get the fields infos for the provided mode ('create', 'edit', 'view', 'list')
	 * @param	array<string,mixed>		$params			Other params
	 * @return  int                                     Result <0 if KO, >0 if OK
	 */
	public function setExtraFieldValuesFromPost(&$object, &$extrafields, $keyPrefix = '', $keySuffix = '', $mode = 'view', $params = array())
	{
		$fields = $this->getAllExtraFieldsInfos($object, $extrafields, $mode, $params);

		$error = 0;
		foreach ($fields as $fieldKey => $fieldInfos) {
			$check = true;
			$key = 'options_' . ($fieldInfos->nameInClass ?? $fieldInfos->key);
			if ($fieldInfos->visible) {
				$check = $this->verifyPostFieldValue($fieldInfos, $fieldKey, $keyPrefix, $keySuffix);
			}
			if ($check) {
				$object->array_options[$key] = $this->getPostFieldValue($fieldInfos, $fieldKey, $object->array_options[$key], $keyPrefix, $keySuffix);
			}
			if (!$fieldInfos->visible) {
				$check = $this->verifyFieldValue($fieldInfos, $fieldKey, $object->array_options[$key]);
			}
			if (!$check) {
				$error++;
			}
		}

		return $error ? -1 : 1;
	}

	/**
	 * Verify if the field value is valid
	 *
	 * @param   FieldInfos		$fieldInfos     Properties of the field
	 * @param	string			$key			Key of attribute
	 * @param	string			$keyPrefix		Prefix string to add into name and id of field (can be used to avoid duplicate names)
	 * @param	string			$keySuffix		Suffix string to add into name and id of field (can be used to avoid duplicate names)
	 * @return  bool
	 */
	public function verifyPostFieldValue($fieldInfos, $key, $keyPrefix = '', $keySuffix = '')
	{
		global $hookmanager;

		$result = true;
		if (getDolGlobalInt('MAIN_FEATURES_LEVEL') >= 1 || getDolGlobalString('MAIN_ACTIVATE_VALIDATION_RESULT')) {
			$parameters = array(
				'fieldInfos' => &$fieldInfos,
				'key' => $key,
				'keyPrefix' => $keyPrefix,
				'keySuffix' => $keySuffix,
			);

			$reshook = $hookmanager->executeHooks('verifyPostFieldValue', $parameters, $this); // Note that $object may have been modified by hook
			if ($reshook > 0) {
				return (bool) $hookmanager->resPrint;
			}

			$this->clearErrors();
			$field = $this->getFieldClass($fieldInfos->type);
			if (isset($field)) {
				$this->clearFieldError($key);
				$result = $field->verifyPostFieldValue($fieldInfos, $key, $keyPrefix, $keySuffix);
				if (!$result) {
					$this->setFieldError($key, CommonField::$validator->error);
				}
			}
		}

		return $result;
	}

	/**
	 * Verify if the field value is valid
	 *
	 * @param   FieldInfos		$fieldInfos		Properties of the field
	 * @param	string			$key			Key of field
	 * @param	mixed			$value     		Value to check (for date type it must be in timestamp format, for amount or price it must be a php numeric value, for array type must be array)
	 * @return  bool
	 */
	public function verifyFieldValue($fieldInfos, $key, $value)
	{
		global $hookmanager;

		$result = true;
		if (getDolGlobalInt('MAIN_FEATURES_LEVEL') >= 1 || getDolGlobalString('MAIN_ACTIVATE_VALIDATION_RESULT')) {
			$parameters = array(
				'fieldInfos' => &$fieldInfos,
				'key' => $key,
				'value' => $value,
			);

			$reshook = $hookmanager->executeHooks('verifyFieldValue', $parameters, $this); // Note that $object may have been modified by hook
			if ($reshook > 0) {
				return (bool) $hookmanager->resPrint;
			}

			$this->clearErrors();
			$field = $this->getFieldClass($fieldInfos->type);
			if (isset($field)) {
				$this->clearFieldError($key);
				$result = $field->verifyFieldValue($fieldInfos, $key, $value);
				if (!$result) {
					$this->setFieldError($key, CommonField::$validator->error);
				}
			}
		}

		return $result;
	}

	/**
	 * Get field value from GET/POST
	 *
	 * @param   FieldInfos		$fieldInfos     Properties of the field
	 * @param   string          $key        	Key of field
	 * @param   mixed			$defaultValue   Preselected value to show (for date type it must be in timestamp format, for amount or price it must be a php numeric value, for array type must be array)
	 * @param	string			$keyPrefix		Prefix string to add into name and id of field (can be used to avoid duplicate names)
	 * @param	string			$keySuffix		Suffix string to add into name and id of field (can be used to avoid duplicate names)
	 * @return  mixed
	 */
	public function getPostFieldValue($fieldInfos, $key, $defaultValue = null, $keyPrefix = '', $keySuffix = '')
	{
		global $hookmanager;

		$value = '';
		$parameters = array(
			'fieldInfos' => &$fieldInfos,
			'key' => $key,
			'value' => &$value,
			'keyPrefix' => $keyPrefix,
			'keySuffix' => $keySuffix,
		);

		$reshook = $hookmanager->executeHooks('getPostFieldValue', $parameters, $this); // Note that $object may have been modified by hook
		if ($reshook > 0) {
			return $value;
		}

		$this->clearErrors();
		$field = $this->getFieldClass($fieldInfos->type);
		if (isset($field)) {
			$value = $field->getPostFieldValue($fieldInfos, $key, $defaultValue, $keyPrefix, $keySuffix);
		} else {
			$value = $defaultValue;
		}

		return $value;
	}

	/**
	 * Get search field value from GET/POST
	 *
	 * @param   FieldInfos		$fieldInfos     Properties of the field
	 * @param   string      	$key        	Key of field
	 * @param   mixed			$defaultValue   Preselected value to show (for date type it must be in timestamp format, for amount or price it must be a php numeric value, for array type must be array)
	 * @param	string			$keyPrefix		Prefix string to add into name and id of field (can be used to avoid duplicate names)
	 * @param	string			$keySuffix		Suffix string to add into name and id of field (can be used to avoid duplicate names)
	 * @return  mixed
	 */
	public function getPostSearchFieldValue($fieldInfos, $key, $defaultValue = null, $keyPrefix = '', $keySuffix = '')
	{
		global $hookmanager;

		$value = '';
		$parameters = array(
			'fieldInfos' => &$fieldInfos,
			'key' => $key,
			'value' => &$value,
			'keyPrefix' => $keyPrefix,
			'keySuffix' => $keySuffix,
		);

		$reshook = $hookmanager->executeHooks('getPostSearchFieldValue', $parameters, $this); // Note that $object may have been modified by hook
		if ($reshook > 0) {
			return $value;
		}

		$this->clearErrors();
		$field = $this->getFieldClass($fieldInfos->type);
		if (isset($field)) {
			$value = $field->getPostSearchFieldValue($fieldInfos, $key, $defaultValue, $keyPrefix, $keySuffix);
		} else {
			$value = $defaultValue;
		}

		return $value;
	}

	/**
	 * Return HTML string to put an input search field into a page
	 *
	 * @param	FieldInfos		$fieldInfos     Properties of the field
	 * @param	string 			$key			Key of attribute
	 * @param	mixed		 	$value			Preselected value to show (for date type it must be in timestamp format, for amount or price it must be a php numeric value, for array type must be array)
	 * @param	string			$keyPrefix		Prefix string to add into name and id of field (can be used to avoid duplicate names)
	 * @param	string			$keySuffix		Suffix string to add into name and id of field (can be used to avoid duplicate names)
	 * @param	string			$moreCss		Value for css to define style/length of field.
	 * @param	string			$moreAttrib		To add more attributes on html input tag
	 * @param	int<0,1> 		$noNewButton	Force to not show the new button on field that are links to object
	 * @return	string
	 */
	public function printInputSearchField($fieldInfos, $key, $value, $keyPrefix = '', $keySuffix = '', $moreCss = '', $moreAttrib = '', $noNewButton = 0)
	{
		global $hookmanager;

		$overwrite_before = '';
		$overwrite_content = '';
		$overwrite_after = '';

		$parameters = array(
			'fieldInfos' => &$fieldInfos,
			'key' => $key,
			'value' => &$value,
			'keyPrefix' => $keyPrefix,
			'keySuffix' => $keySuffix,
			'moreCss' => $moreCss,
			'moreAttrib' => $moreAttrib,
			'noNewButton' => $noNewButton,
			'overwrite_before' => &$overwrite_before,
			'overwrite_content' => &$overwrite_content,
			'overwrite_after' => &$overwrite_after,
		);

		$hookmanager->executeHooks('printInputSearchField', $parameters, $this); // Note that $this may have been modified by hook

		if (!empty($fieldInfos->computed)) {
			return '';
		}

		$out = $overwrite_before;
		if (empty($overwrite_content)) {
			$this->clearErrors();
			$field = $this->getFieldClass($fieldInfos->type);
			if (isset($field)) {
				$moreCss = $field->getInputCss($fieldInfos, $moreCss);

				$out .= $field->printInputSearchField($fieldInfos, $key, $value, $keyPrefix, $keySuffix, $moreCss, $moreAttrib);
			} else {
				$out .= $this->errorsToString();
			}
		} else {
			$out .= $overwrite_content;
		}
		$out .= $overwrite_after;

		return $out;
	}

	/**
	 * Return HTML string to put an input field into a page
	 *
	 * @param	FieldInfos		$fieldInfos     Properties of the field
	 * @param	string 			$key			Key of attribute
	 * @param	mixed		 	$value			Preselected value to show (for date type it must be in timestamp format, for amount or price it must be a php numeric value, for array type must be array)
	 * @param	string			$keyPrefix		Prefix string to add into name and id of field (can be used to avoid duplicate names)
	 * @param	string			$keySuffix		Suffix string to add into name and id of field (can be used to avoid duplicate names)
	 * @param	string			$moreCss		Value for css to define style/length of field.
	 * @param	string			$moreAttrib		To add more attributes on html input tag
	 * @param	int<0,1> 		$noNewButton	Force to not show the new button on field that are links to object
	 * @return	string
	 */
	public function printInputField($fieldInfos, $key, $value, $keyPrefix = '', $keySuffix = '', $moreCss = '', $moreAttrib = '', $noNewButton = 0)
	{
		global $hookmanager, $langs;

		$overwrite_before = '';
		$overwrite_content = '';
		$overwrite_after = '';

		$parameters = array(
			'fieldInfos' => &$fieldInfos,
			'key' => $key,
			'value' => &$value,
			'keyPrefix' => $keyPrefix,
			'keySuffix' => $keySuffix,
			'moreCss' => $moreCss,
			'moreAttrib' => $moreAttrib,
			'noNewButton' => $noNewButton,
			'overwrite_before' => &$overwrite_before,
			'overwrite_content' => &$overwrite_content,
			'overwrite_after' => &$overwrite_after,
		);

		$hookmanager->executeHooks('printInputField', $parameters, $this); // Note that $this may have been modified by hook

		if (!empty($fieldInfos->computed)) {
			return '<span class="opacitymedium">' . $langs->trans("AutomaticallyCalculated") . '</span>';
		}

		if (!$fieldInfos->editable) {
			return $this->printOutputField($fieldInfos, $key, $value, $keyPrefix, $keySuffix, $moreCss, $moreAttrib);
		}

		// Get validation error
		$fieldValidationErrorMsg = $this->getFieldError($key);

		$out = $overwrite_before;
		if (empty($overwrite_content)) {
			$this->clearErrors();
			$field = $this->getFieldClass($fieldInfos->type);
			if (isset($field)) {
				$moreCss = $field->getInputCss($fieldInfos, $moreCss);

				// Add validation state class
				if (!empty($fieldValidationErrorMsg)) {
					$moreCss .= ' --error'; // the -- is use as class state in css :  .--error can't be be defined alone it must be define with another class like .my-class.--error or input.--error
				} else {
					$moreCss .= ' --success'; // the -- is use as class state in css :  .--success can't be be defined alone it must be define with another class like .my-class.--success or input.--success
				}

				$out .= $field->printInputField($fieldInfos, $key, $value, $keyPrefix, $keySuffix, $moreCss, $moreAttrib);
			} else {
				$out .= $this->errorsToString();
			}
		} else {
			$out .= $overwrite_content;
		}
		if (empty($overwrite_after)) {
			// Display error message for field
			$out .= $this->getFieldErrorIcon($fieldValidationErrorMsg);
		} else {
			$out .= $overwrite_after;
		}

		return $out;
	}

	/**
	 * Return HTML string to show a field into a page
	 *
	 * @param	FieldInfos		$fieldInfos     Properties of the field
	 * @param	string			$key			Key of attribute
	 * @param	mixed			$value			Preselected value to show (for date type it must be in timestamp format, for amount or price it must be a php numeric value, for array type must be array)
	 * @param	string			$keyPrefix		Prefix string to add into name and id of field (can be used to avoid duplicate names)
	 * @param	string			$keySuffix		Suffix string to add into name and id of field (can be used to avoid duplicate names)
	 * @param	string			$moreCss		Value for css to define style/length of field.
	 * @param	string			$moreAttrib		To add more attributes on html input tag
	 * @return	string
	 */
	public function printOutputField($fieldInfos, $key, $value, $keyPrefix = '', $keySuffix = '', $moreCss = '', $moreAttrib = '')
	{
		global $hookmanager;

		$overwrite_before = '';
		$overwrite_content = '';
		$overwrite_after = '';

		$parameters = array(
			'fieldInfos' => &$fieldInfos,
			'key' => $key,
			'value' => &$value,
			'keyPrefix' => $keyPrefix,
			'keySuffix' => $keySuffix,
			'moreCss' => $moreCss,
			'moreAttrib' => $moreAttrib,
			'overwrite_before' => &$overwrite_before,
			'overwrite_content' => &$overwrite_content,
			'overwrite_after' => &$overwrite_after,
		);

		$hookmanager->executeHooks('printOutputField', $parameters, $this); // Note that $object may have been modified by hook

		$out = $overwrite_before;
		if (empty($overwrite_content)) {
			$this->clearErrors();
			$field = $this->getFieldClass($fieldInfos->type);
			if (isset($field)) {
				$moreCss = $field->getInputCss($fieldInfos, $moreCss);

				$out .= $field->printOutputField($fieldInfos, $key, $value, $keyPrefix, $keySuffix, $moreCss, $moreAttrib);
			} else {
				$out .= $this->errorsToString();
			}
		} else {
			$out .= $overwrite_content;
		}
		$out .= $overwrite_after;

		return $out;
	}

	/**
	 * Return HTML string to print separator field
	 *
	 * @param   string	$key            Key of attribute
	 * @param	object	$object			Object
	 * @param	int		$colspan		Value of colspan to use (it must include the first column with title)
	 * @param	string	$display_type	"card" for form display, "line" for document line display
	 * @param 	string  $mode           Show output ('view') or input ('create' or 'edit') for field
	 * @return 	string					HTML code with line for separator
	 */
	public function printSeparator($key, &$object, $colspan = 2, $display_type = 'card', $mode = 'view')
	{
		global $conf, $langs;

		// TODO to adapt for field and not extra fields only
		$out = '';

		/*$tagtype = 'tr';
		$tagtype_dyn = 'td';

		if ($display_type == 'line') {
			$tagtype = 'div';
			$tagtype_dyn = 'span';
			$colspan = 0;
		}

		$extrafield_param = $this->attributes[$object->table_element]['param'][$key];
		$extrafield_param_list = array();
		if (!empty($extrafield_param) && is_array($extrafield_param)) {
			$extrafield_param_list = array_keys($extrafield_param['options']);
		}

		// Set $extrafield_collapse_display_value (do we have to collapse/expand the group after the separator)
		$extrafield_collapse_display_value = -1;
		$expand_display = false;
		if (is_array($extrafield_param_list) && count($extrafield_param_list) > 0) {
			$extrafield_collapse_display_value = intval($extrafield_param_list[0]);
			$expand_display = ((isset($_COOKIE['DOLUSER_COLLAPSE_' . $object->table_element . '_extrafields_' . $key]) || GETPOSTINT('ignorecollapsesetup')) ? (!empty($_COOKIE['DOLUSER_COLLAPSE_' . $object->table_element . '_extrafields_' . $key])) : !($extrafield_collapse_display_value == 2));
		}
		$disabledcookiewrite = 0;
		if ($mode == 'create') {
			// On create mode, force separator group to not be collapsible
			$extrafield_collapse_display_value = 1;
			$expand_display = true;    // We force group to be shown expanded
			$disabledcookiewrite = 1; // We keep status of group unchanged into the cookie
		}

		$out = '<' . $tagtype . ' id="trextrafieldseparator' . $key . (!empty($object->id) ? '_' . $object->id : '') . '" class="trextrafieldseparator trextrafieldseparator' . $key . (!empty($object->id) ? '_' . $object->id : '') . '">';
		$out .= '<' . $tagtype_dyn . ' ' . (!empty($colspan) ? 'colspan="' . $colspan . '"' : '') . '>';
		// Some js code will be injected here to manage the collapsing of fields
		// Output the picto
		$out .= '<span class="' . ($extrafield_collapse_display_value ? 'cursorpointer ' : '') . ($extrafield_collapse_display_value == 0 ? 'fas fa-square opacitymedium' : 'far fa-' . (($expand_display ? 'minus' : 'plus') . '-square')) . '"></span>';
		$out .= '&nbsp;';
		$out .= '<strong>';
		$out .= $langs->trans($this->attributes[$object->table_element]['label'][$key]);
		$out .= '</strong>';
		$out .= '</' . $tagtype_dyn . '>';
		$out .= '</' . $tagtype . '>';

		$collapse_group = $key . (!empty($object->id) ? '_' . $object->id : '');
		//$extrafields_collapse_num = $this->attributes[$object->table_element]['pos'][$key].(!empty($object->id)?'_'.$object->id:'');

		if ($extrafield_collapse_display_value == 1 || $extrafield_collapse_display_value == 2) {
			// Set the collapse_display status to cookie in priority or if ignorecollapsesetup is 1, if cookie and ignorecollapsesetup not defined, use the setup.
			$this->expand_display[$collapse_group] = $expand_display;

			if (!empty($conf->use_javascript_ajax)) {
				$out .= '<!-- Add js script to manage the collapse/uncollapse of extrafields separators ' . $key . ' -->' . "\n";
				$out .= '<script nonce="' . getNonce() . '" type="text/javascript">' . "\n";
				$out .= 'jQuery(document).ready(function(){' . "\n";
				if (empty($disabledcookiewrite)) {
					if (!$expand_display) {
						$out .= '   console.log("Inject js for the collapsing of extrafield ' . $key . ' - hide");' . "\n";
						$out .= '   jQuery(".trextrafields_collapse' . $collapse_group . '").hide();' . "\n";
					} else {
						$out .= '   console.log("Inject js for collapsing of extrafield ' . $key . ' - keep visible and set cookie");' . "\n";
						$out .= '   document.cookie = "DOLUSER_COLLAPSE_' . $object->table_element . '_extrafields_' . $key . '=1; path=' . $_SERVER["PHP_SELF"] . '"' . "\n";
					}
				}
				$out .= '   jQuery("#trextrafieldseparator' . $key . (!empty($object->id) ? '_' . $object->id : '') . '").click(function(){' . "\n";
				$out .= '       console.log("We click on collapse/uncollapse to hide/show .trextrafields_collapse' . $collapse_group . '");' . "\n";
				$out .= '       jQuery(".trextrafields_collapse' . $collapse_group . '").toggle(100, function(){' . "\n";
				$out .= '           if (jQuery(".trextrafields_collapse' . $collapse_group . '").is(":hidden")) {' . "\n";
				$out .= '               jQuery("#trextrafieldseparator' . $key . (!empty($object->id) ? '_' . $object->id : '') . ' ' . $tagtype_dyn . ' span").addClass("fa-plus-square").removeClass("fa-minus-square");' . "\n";
				$out .= '               document.cookie = "DOLUSER_COLLAPSE_' . $object->table_element . '_extrafields_' . $key . '=0; path=' . $_SERVER["PHP_SELF"] . '"' . "\n";
				$out .= '           } else {' . "\n";
				$out .= '               jQuery("#trextrafieldseparator' . $key . (!empty($object->id) ? '_' . $object->id : '') . ' ' . $tagtype_dyn . ' span").addClass("fa-minus-square").removeClass("fa-plus-square");' . "\n";
				$out .= '               document.cookie = "DOLUSER_COLLAPSE_' . $object->table_element . '_extrafields_' . $key . '=1; path=' . $_SERVER["PHP_SELF"] . '"' . "\n";
				$out .= '           }' . "\n";
				$out .= '       });' . "\n";
				$out .= '   });' . "\n";
				$out .= '});' . "\n";
				$out .= '</script>' . "\n";
			}
		} else {
			$this->expand_display[$collapse_group] = 1;
		}*/

		return $out;
	}
}
