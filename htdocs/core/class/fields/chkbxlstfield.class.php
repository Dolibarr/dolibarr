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
 *    \file        htdocs/core/class/fields/chkbxlstfield.class.php
 *    \ingroup    core
 *    \brief      File of class to chkbxlst field(multiselect)
 */

require_once DOL_DOCUMENT_ROOT . '/core/class/fields/commonsellistfield.class.php';


/**
 *    Class to chkbxlst field (multiselect)
 */
class ChkbxlstField extends CommonSellistField
{
	/**
	 * @var array<int,mixed> 	List of value deemed as empty (null always deemed as empty)
	 */
	public $emptyValues = array(array(), '');


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
		global $conf;

		$moreCss = $this->getInputCss($fieldInfos, $moreCss);
		$moreAttrib = trim((string) $moreAttrib);
		if (empty($moreAttrib)) $moreAttrib = ' ' . $moreAttrib;
		$htmlName = $keyPrefix . $key . $keySuffix;
		$values = $this->isEmptyValue($fieldInfos, $value) ? array() : (is_string($value) ? explode(',', $value) : (is_array($value) ? $value: array($value)));

		$optionsList = array();
		$options = $this->getOptions($fieldInfos, $key);
		foreach ($options as $optionKey => $optionInfos) {
			$options[$optionKey] = $optionInfos['label'];
		}

		return self::$form->multiselectarray($htmlName, $optionsList, $values, 0, 0, $moreCss, 0, 0, $moreAttrib, '', '', (int) (!empty($conf->use_javascript_ajax) && !getDolGlobalString('MAIN_EXTRAFIELDS_DISABLE_SELECT2')));
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
		global $conf;

		$moreCss = $this->getInputCss($fieldInfos, $moreCss);
		$moreAttrib = trim((string) $moreAttrib);
		if (empty($moreAttrib)) $moreAttrib = ' ' . $moreAttrib;
		$htmlName = $keyPrefix . $key . $keySuffix;
		$values = $this->isEmptyValue($fieldInfos, $value) ? array() : (is_string($value) ? explode(',', $value) : (is_array($value) ? $value: array($value)));

		$options = $this->getOptions($fieldInfos, $key);

		return self::$form->multiselectarray($htmlName, $options, $values, 0, 0, $moreCss, 0, 0, $moreAttrib, '', '', (int) (!empty($conf->use_javascript_ajax) && !getDolGlobalString('MAIN_EXTRAFIELDS_DISABLE_SELECT2')));
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
		$values = $this->isEmptyValue($fieldInfos, $value) ? array() : (is_string($value) ? explode(',', $value) : (is_array($value) ? $value: array($value)));

		$out = '';
		if (!$this->isEmptyValue($fieldInfos, $values)) {
			$options = $this->getOptions($fieldInfos, $key, false, false, $values);
			$optionParams = $this->getOptionsParams($fieldInfos->options);
			$isCategory = $optionParams['tableName'] == 'categorie' && !empty($optionParams['categoryType']);

			$toPrint = array();
			foreach ($values as $val) {
				$valueToPrint = '';
				$colorToPrint = 'bbb';
				if (isset($options[$val])) {
					$valueToPrint = $options[$val]['label'];

					if ($isCategory) {
						require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
						$c = new Categorie($this->db);
						$c->fetch($val);
						$colorToPrint = $c->color ? $c->color : 'bbb';
						$valueToPrint = img_object('', 'category') . ' ' . $valueToPrint;
					}
				} else {
					$valueToPrint = $val;
				}
				$toPrint[] = '<li class="select2-search-choice-dolibarr noborderoncategories" style="background: #' . $colorToPrint . ';">' . $valueToPrint . '</li>';
			}
			if (!empty($toPrint)) {
				$out = '<div class="select2-container-multi-dolibarr" style="width: 90%;"><ul class="select2-choices-dolibarr">' . implode('', $toPrint) . '</ul></div>';
			}
		}

		return $out;
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
	public function getInputCss($fieldInfos, $moreCss = '', $defaultCss = '')
	{
		return parent::getInputCss($fieldInfos, $moreCss, $defaultCss ? $defaultCss : 'minwidth400');
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
		$values = $this->isEmptyValue($fieldInfos, $value) ? array() : (is_string($value) ? explode(',', $value) : (is_array($value) ? $value: array($value)));

		$result = parent::verifyFieldValue($fieldInfos, $key, $values);
		if ($result && !$this->isEmptyValue($fieldInfos, $values)) {
			$optionParams = $this->getOptionsParams($fieldInfos->options);
			if (!self::$validator->isInDb($values, $optionParams['tableName'], $optionParams['keyField'])) {
				return false;
			}

			$result = true;
		}

		return $result;
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
		$values = GETPOST($htmlName, 'array');

		return $this->verifyFieldValue($fieldInfos, $key, $values);
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
		$htmlName = $keyPrefix . $key . $keySuffix;

		if (GETPOSTISSET($htmlName)) {
			$values = GETPOST($htmlName, 'array');
			if (is_array($values)) $values = implode(',', $values);
		} else {
			$values = $defaultValue;
		}

		return $values;
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
		$htmlName = $keyPrefix . $key . $keySuffix;

		if (GETPOSTISSET($htmlName)) {
			$value = GETPOST($htmlName, 'array');
		} else {
			$value = $defaultValue;
		}

		return $value;
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
		if (!empty($value) && is_array($value)) {
			$alias = $fieldInfos->sqlAlias ?? 't.';
			$field = $this->db->sanitize($alias . ($fieldInfos->nameInTable ?? $key));

			$tmp = "'" . implode("','", array_map(array($this->db, 'escape'), $value)) . "'";
			return " AND " . $field . " IN (" . $this->db->sanitize($tmp, 1) . ")";
		}

		return '';
	}

	/**
	 * Get list of options
	 *
	 * @param   FieldInfos    										$fieldInfos     Array of properties for field to show
	 * @param	string												$key			Key of field
	 * @param	bool												$addEmptyValue	Add also empty value if needed
	 * @param 	bool												$reload			Force reload options
	 * @param	string|array<int,string>							$selectedValues	Only selected values
	 * @return  array<string,array{label:string,parent:string}>
	 */
	public function getOptions($fieldInfos, $key, $addEmptyValue = false, $reload = false, $selectedValues = array())
	{
		return parent::getOptions($fieldInfos, $key, $addEmptyValue, $reload, $selectedValues);
	}
}
