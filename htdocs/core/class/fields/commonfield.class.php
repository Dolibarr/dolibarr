<?php
/* Copyright (C) 2025 		Open-Dsi         <support@open-dsi.fr>
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
 *    \file        htdocs/core/class/fields/commonfield.class.php
 *    \ingroup    core
 *    \brief      File of class to common field
 */

require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/validate.class.php';


/**
 *    Class to common field
 */
abstract class CommonField
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
	 * @var string 	Type
	 */
	public $type;

	/**
	 * @var string	Label
	 */
	public $label;

	/**
	 * @var Form|null 	Form handler.
	 */
	public static $form;

	/**
	 * @var Validate|null 	Validate handler.
	 */
	public static $validator;

	/**
	 * @var array<int,mixed> 	List of value deemed as empty (null always deemed as empty)
	 */
	public $emptyValues = array('', 0, array());


	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		global $form, $langs;

		$this->db = $db;
		$this->error = '';
		$this->errors = array();

		// Type and label
		$this->type = strtolower(substr(get_class($this), 0, -5));
		$this->label = 'FieldLabel' . ucfirst($this->type);

		if (!isset(self::$form)) {
			if (!is_object($form)) {
				$form = new Form($this->db);
			}
			self::setForm($form);
		}

		if (!isset(self::$validator)) {
			// Use Validate class to allow external Modules to use data validation part instead of concentrate all test here (factoring) or just for reuse
			$validator = new Validate($this->db, $langs);
			self::setValidator($validator);
		}
	}

	/**
	 * Set form used for print the field
	 *
	 * @param	Form	$form	Form handler
	 * @return	void
	 */
	public static function setForm(&$form)
	{
		self::$form = &$form;
	}

	/**
	 * Set validator used for check the field value
	 *
	 * @param	Validate	$validator	Validate handler
	 * @return	void
	 */
	public static function setValidator(&$validator)
	{
		self::$validator = &$validator;
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
	 * Check if the value is deemed as empty
	 *
	 * @param	FieldInfos			$fieldInfos		Properties of the field
	 * @param	mixed				$value			Value to check (for date type it must be in timestamp format, for amount or price it must be a php numeric value, for array type must be array)
	 * @param	array<int,mixed>	$emptyValues	List of value deemed as empty
	 * @return	bool
	 */
	public function isEmptyValue($fieldInfos, $value, $emptyValues = null)
	{
		if (!isset($value)) {
			return true;
		}

		if (!is_array($emptyValues)) {
			$emptyValues = !empty($fieldInfos->emptyValues) && is_array($fieldInfos->emptyValues) ? $fieldInfos->emptyValues : $this->emptyValues;
		}

		foreach ($emptyValues as $val) {
			if ($val === $value) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Return HTML string to put an input search field into a page
	 *
	 * @param   FieldInfos		$fieldInfos     Properties of the field
	 * @param   string          $key        	Key of field
	 * @param   mixed			$value      	Preselected value to show (for date type it must be in timestamp format, for amount or price it must be a php numeric value, for array type must be array)
	 * @param   string 			$keyPrefix  	Prefix string to add into name and id of field (can be used to avoid duplicate names)
	 * @param	string			$keySuffix		Suffix string to add into name and id of field (can be used to avoid duplicate names)
	 * @param	string			$moreCss		Value for css to define style/length of field.
	 * @param	string			$moreAttrib		To add more attributes on html input tag
	 * @return  string
	 */
	public function printInputSearchField($fieldInfos, $key, $value, $keyPrefix = '', $keySuffix = '', $moreCss = '', $moreAttrib = '')
	{
		return '';
	}

	/**
	 * Return HTML string to put an input field into a page
	 *
	 * @param	FieldInfos		$fieldInfos		Properties of the field
	 * @param   string         	$key       		Key of field
	 * @param   mixed			$value     		Preselected value to show (for date type it must be in timestamp format, for amount or price it must be a php numeric value, for array type must be array)
	 * @param   string 			$keyPrefix 		Prefix string to add into name and id of field (can be used to avoid duplicate names)
	 * @param	string			$keySuffix		Suffix string to add into name and id of field (can be used to avoid duplicate names)
	 * @param	string			$moreCss		Value for css to define style/length of field.
	 * @param	string			$moreAttrib		To add more attributes on html input tag
	 * @return  string
	 */
	public function printInputField($fieldInfos, $key, $value, $keyPrefix = '', $keySuffix = '', $moreCss = '', $moreAttrib = '')
	{
		return '';
	}

	/**
	 * Return HTML string to show a field into a page
	 *
	 * @param	FieldInfos		$fieldInfos		Properties of the field
	 * @param   string          $key       		Key of field
	 * @param   mixed			$value     		Preselected value to show (for date type it must be in timestamp format, for amount or price it must be a php numeric value, for array type must be array)
	 * @param   string 			$keyPrefix 		Prefix string to add into name and id of field (can be used to avoid duplicate names)
	 * @param	string			$keySuffix		Suffix string to add into name and id of field (can be used to avoid duplicate names)
	 * @param	string			$moreCss		Value for css to define style/length of field.
	 * @param	string			$moreAttrib		To add more attributes on html input tag
	 * @return  string
	 */
	public function printOutputField($fieldInfos, $key, $value, $keyPrefix = '', $keySuffix = '', $moreCss = '', $moreAttrib = '')
	{
		return '';
	}

	/**
	 * Get input CSS
	 *
	 * @param   FieldInfos		$fieldInfos     Properties of the field
	 * @param	string			$moreCss 		Value for css to define style/length of field.
	 * @param	string			$defaultCss		Default value for css to define style/length of field.
	 * @return  string
	 * @see self::printInputSearchField(), self::printInputField()
	 */
	public function getInputCss($fieldInfos, $moreCss = '', $defaultCss = 'minwidth400')
	{
		if (empty($moreCss)) {
			if (!empty($fieldInfos->css)) {
				$moreCss = $fieldInfos->css;
			} elseif (!empty($defaultCss)) {
				$moreCss = $defaultCss;
			}
		}
		$moreCss = trim((string) $moreCss);

		return empty($moreCss) ? '' : ' ' . $moreCss;
	}

	/**
	 * Verify if the field value is valid
	 *
	 * @param   FieldInfos		$fieldInfos		Properties of the field
	 * @param	string			$key			Key of field
	 * @param	mixed			$value     		Value to check (for date type it must be in timestamp format, for amount or price it must be a php numeric value, for array type must be array)
	 * @return  bool
	 * @see self::printInputField()
	 */
	public function verifyFieldValue($fieldInfos, $key, $value)
	{
		global $langs;

		$required = $fieldInfos->required;
		$minLength = $fieldInfos->minLength ?? 0;
		$maxLength = $fieldInfos->maxLength ?? 0;
		//$emptyValues = !empty($fieldInfos->emptyValues) ? $fieldInfos->emptyValues : $this->emptyValues;

		// Clear error
		self::$validator->error = '';

		// Todo move this in validate class ?
		// Required test and empty value
		if ($this->isEmptyValue($fieldInfos, $value)) {
			if ($required) {
				self::$validator->error = $langs->trans('RequireANotEmptyValue');
				return false;
			} else {
				// if no value sent and the field is not mandatory, no need to perform tests
				return true;
			}
		}

		// MIN Size test
		if (!empty($minLength) && is_string($value) && !self::$validator->isMinLength($value, $minLength)) {
			return false;
		}

		// MAX Size test
		if (!empty($maxLength) && is_string($value) && !self::$validator->isMaxLength($value, $maxLength)) {
			return false;
		}

		// Todo move this in validate class ?
		// MIN Value test
		if (isset($fieldInfos->minValue) && is_numeric($value) && ((double) $value) < $fieldInfos->minValue) {
			self::$validator->error = $langs->trans('RequireMinValue', $fieldInfos->minValue);
			return false;
		}

		// MAX Value test
		if (isset($fieldInfos->maxValue) && is_numeric($value) && ((double) $value) > $fieldInfos->maxValue) {
			self::$validator->error = $langs->trans('RequireMaxValue', $fieldInfos->maxValue);
			return false;
		}

		return true;
	}

	/**
	 * Verify if the field value from GET/POST is valid
	 *
	 * @param   FieldInfos			$fieldInfos		Properties of the field
	 * @param	string				$key        	Key of field
	 * @param	string				$keyPrefix		Prefix string to add into name and id of field (can be used to avoid duplicate names)
	 * @param	string				$keySuffix		Suffix string to add into name and id of field (can be used to avoid duplicate names)
	 * @return  bool
	 * @see self::printInputField()
	 */
	public function verifyPostFieldValue($fieldInfos, $key, $keyPrefix = '', $keySuffix = '')
	{
		$htmlName = $keyPrefix . $key . $keySuffix;

		return $this->verifyFieldValue($fieldInfos, $key, GETPOST($htmlName, 'restricthtml'));
	}

	/**
	 * Get field value from GET/POST
	 *
	 * @param   FieldInfos		$fieldInfos		Properties of the field
	 * @param   string      	$key        	Key of field
	 * @param   mixed  			$defaultValue   Preselected value to show (for date type it must be in timestamp format, for amount or price it must be a php numeric value, for array type must be array)
	 * @param	string			$keyPrefix		Prefix string to add into name and id of field (can be used to avoid duplicate names)
	 * @param	string			$keySuffix		Suffix string to add into name and id of field (can be used to avoid duplicate names)
	 * @return  mixed
	 * @see self::printInputField()
	 */
	public function getPostFieldValue($fieldInfos, $key, $defaultValue = null, $keyPrefix = '', $keySuffix = '')
	{
		return $defaultValue;
	}

	/**
	 * Get search field value from GET/POST
	 *
	 * @param   FieldInfos		$fieldInfos		Properties of the field
	 * @param   string          $key        	Key of field
	 * @param   mixed			$defaultValue   Preselected value to show (for date type it must be in timestamp format, for amount or price it must be a php numeric value, for array type must be array)
	 * @param	string			$keyPrefix		Prefix string to add into name and id of field (can be used to avoid duplicate names)
	 * @param	string			$keySuffix		Suffix string to add into name and id of field (can be used to avoid duplicate names)
	 * @return  mixed
	 * @see self::printInputSearchField()
	 */
	public function getPostSearchFieldValue($fieldInfos, $key, $defaultValue = null, $keyPrefix = '', $keySuffix = '')
	{
		return $defaultValue;
	}

	/**
	 * Get sql filter for search field
	 *
	 * @param   FieldInfos		$fieldInfos		Properties of the field
	 * @param   string          $key        	Key of field
	 * @param	mixed			$value			Preselected value to show (for date type it must be in timestamp format, for amount or price it must be a php numeric value, for array type must be array)
	 * @return  string
	 * @see self::printInputSearchField(), self::getPostSearchFieldValue()
	 */
	public function sqlFilterSearchField($fieldInfos, $key, $value)
	{
		return '';
	}
}
