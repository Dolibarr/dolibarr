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
 *    \file        htdocs/core/class/fields/datetimefield.class.php
 *    \ingroup    core
 *    \brief      File of class to datetime field
 */

require_once DOL_DOCUMENT_ROOT . '/core/class/fields/commonfield.class.php';


/**
 *    Class to datetime field
 */
class DatetimeField extends CommonField
{
	/**
	 * @var array<int,mixed> 	List of value deemed as empty (null always deemed as empty)
	 */
	public $emptyValues = array('');

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
		global $langs;

		// TODO Must also support $moreCss ?

		$htmlName = $keyPrefix . $key . $keySuffix;
		$prefill = array(
			'start' => $value['start'] ?? '',
			'end' => $value['end'] ?? '',
		);

		// Search filter on a date field shows two inputs to select a date range
		$out = '<div ' . $moreAttrib . '><div class="nowrap">';
		$out .= self::$form->selectDate($prefill['start'], $htmlName . '_start', 1, 1, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans("From"), 'tzuserrel');
		$out .= '</div><div class="nowrap">';
		$out .= self::$form->selectDate($prefill['end'], $htmlName . '_end', 1, 1, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans("to"), 'tzuserrel');
		$out .= '</div></div>';

		return $out;
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
		$required = $fieldInfos->required ? 1 : 0;
		$htmlName = $keyPrefix . $key . $keySuffix;

		// Do not show current date when field not required (see selectDate() method)
		if (!$required && $this->isEmptyValue($fieldInfos, $value)) {
			$value = '-1';
		}

		// TODO Must also support $moreAttrib and $moreCss ?
		return self::$form->selectDate($value, $htmlName, 1, 1, $required, '', 1, 1, 0, 1, '', '', '', 1, '', '', 'tzuserrel');
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
		return !$this->isEmptyValue($fieldInfos, $value) ? dol_print_date($value, 'dayhour', 'tzuserrel') : '';
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
		return parent::getInputCss($fieldInfos, $moreCss, $defaultCss ? $defaultCss : 'minwidth200imp');
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
		$result = parent::verifyFieldValue($fieldInfos, $key, $value);
		if ($result && !$this->isEmptyValue($fieldInfos, $value)) {
			if (!self::$validator->isTimestamp($value)) {
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
		return parent::verifyPostFieldValue($fieldInfos, $key, $keyPrefix, $keySuffix);
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

		if (GETPOSTISSET($htmlName . 'hour') || GETPOSTISSET($htmlName . 'min') || GETPOSTISSET($htmlName . 'sec') || GETPOSTISSET($htmlName . 'month') || GETPOSTISSET($htmlName . 'day') || GETPOSTISSET($htmlName . 'year')) {
			$value = dol_mktime(GETPOSTINT($htmlName . 'hour'), GETPOSTINT($htmlName . 'min'), GETPOSTINT($htmlName . 'sec'), GETPOSTINT($htmlName . 'month'), GETPOSTINT($htmlName . 'day'), GETPOSTINT($htmlName . 'year'), 'tzuserrel'); // for date without hour, we use gmt
		} else {
			$value = $defaultValue;
		}

		return $value;
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

		if (GETPOSTISSET($htmlName . '_startmonth') || GETPOSTISSET($htmlName . '_startday') || GETPOSTISSET($htmlName . '_startyear')) {
			$start = dol_mktime(0, 0, 0, GETPOSTINT($htmlName . '_startmonth'), GETPOSTINT($htmlName . '_startday'), GETPOSTINT($htmlName . '_startyear'), 'tzuserrel');
		} else {
			$start = is_array($defaultValue) && isset($defaultValue['start']) ? $defaultValue['start'] : '';
		}

		if (GETPOSTISSET($htmlName . '_endmonth') || GETPOSTISSET($htmlName . '_endday') || GETPOSTISSET($htmlName . '_endyear')) {
			$end = dol_mktime(23, 59, 59, GETPOSTINT($htmlName . '_endmonth'), GETPOSTINT($htmlName . '_endday'), GETPOSTINT($htmlName . '_endyear'), 'tzuserrel');
		} else {
			$end = is_array($defaultValue) && isset($defaultValue['end']) ? $defaultValue['end'] : '';
		}

		return array(
			'start' => $start,
			'end' => $end,
		);
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
		if (!$this->isEmptyValue($fieldInfos, $value)) {
			$alias = $fieldInfos->sqlAlias ?? 't.';
			$field = $this->db->sanitize($alias . ($fieldInfos->nameInTable ?? $key));
			$sql = '';

			if (is_array($value)) {
				$hasStart = !is_null($value['start']) && $value['start'] !== '';
				$hasEnd = !is_null($value['start']) && $value['start'] !== '';
				if ($hasStart && $hasEnd) {
					$sql = " AND (" . $field . " BETWEEN '" . $this->db->idate($value['start']) . "' AND '" . $this->db->idate($value['end']) . "')";
				} elseif ($hasStart) {
					$sql = " AND " . $field . " >= '" . $this->db->idate($value['start']) . "'";
				} elseif ($hasEnd) {
					$sql = " AND " . $field . " <= '" . $this->db->idate($value['end']) . "'";
				}
			} elseif (is_numeric($value)) {
				$sql = " AND " . $field . " = '" . $this->db->idate($value) . "'";
			}

			return $sql;
		}

		return '';
	}
}
