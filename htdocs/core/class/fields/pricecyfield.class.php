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
 *    \file        htdocs/core/class/fields/pricecyfield.class.php
 *    \ingroup    core
 *    \brief      File of class to pricecy field (price with currency)
 */

require_once DOL_DOCUMENT_ROOT . '/core/class/fields/commonfield.class.php';


/**
 *    Class to pricecy field (price with currency)
 */
class PricecyField extends CommonField
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
		$moreCss = $this->getInputCss($fieldInfos, $moreCss);
		$htmlName = $keyPrefix . $key . $keySuffix;
		$tmp = $this->getPriceAndCurrencyFromValue($fieldInfos, $value);
		$value = $tmp['price'];
		$currency = $tmp['currency'];

		$tmp = $this->getPriceAndCurrencyAliasAndField($fieldInfos, $key);
		$aliasPrice = $tmp['aliasPrice'];
		$fieldPrice = $tmp['fieldPrice'];
		$aliasCurrency = $tmp['aliasCurrency'];
		$fieldCurrency = $tmp['fieldCurrency'];

		$out = self::$form->inputType('text', $htmlName, (string) $value, $htmlName, $moreCss, $moreAttrib);
		if (!empty($fieldCurrency)) {
			$out .= self::$form->selectCurrency($currency, $htmlName . 'currency_id');
		}

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
		$moreCss = $this->getInputCss($fieldInfos, $moreCss);
		$autoFocus = $fieldInfos->inputAutofocus ? ' autofocus' : '';
		$htmlName = $keyPrefix . $key . $keySuffix;
		$tmp = $this->getPriceAndCurrencyFromValue($fieldInfos, $value);
		$value = $tmp['price'];
		$currency = $tmp['currency'];
		$value = !$this->isEmptyValue($fieldInfos, $value) ? price($value) : '';

		$out = self::$form->inputType('text', $htmlName, (string) $value, $htmlName, $moreCss, $moreAttrib . $autoFocus);
		$out .= self::$form->selectCurrency($currency, $htmlName . 'currency_id');

		return '<span class="form-select-price-currency-container">'.$out.'</span>';
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
		global $langs;
		$tmp = $this->getPriceAndCurrencyFromValue($fieldInfos, $value);
		$value = $tmp['price'];
		$currency = $tmp['currency'];

		return !$this->isEmptyValue($fieldInfos, $value) ? price($value, 0, $langs, 0, getDolGlobalInt('MAIN_MAX_DECIMALS_TOT'), -1, $currency) : '';
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
		return parent::getInputCss($fieldInfos, $moreCss, $defaultCss ? $defaultCss : 'maxwidth75');
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
			$tmp = $this->getPriceAndCurrencyFromValue($fieldInfos, $value);
			$value = $tmp['price'];
			$currency = $tmp['currency'];
			if (!self::$validator->isNumeric($value)) {
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
		$value = GETPOST($htmlName, 'restricthtml') . ':' . GETPOST($htmlName . "currency_id", 'restricthtml');
		$value = str_replace(',', '.', $value);

		return $this->verifyFieldValue($fieldInfos, $key, $value);
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
			$value = price2num(GETPOST($htmlName, 'alphanohtml')) . ':' . GETPOST($htmlName . "currency_id", 'alpha');
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

		if (GETPOSTISSET($htmlName)) {
			$value = array(
				'value' => GETPOST($htmlName, 'alphanohtml'),
				'currency' => GETPOST($htmlName . "currency_id", 'alpha'),
			);
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
		$filterValue = $value['value'] ?? '';
		$filterCurrency = $value['currency'] ?? '';
		if ($filterCurrency == '-1') $filterCurrency = '';

		$tmp = $this->getPriceAndCurrencyAliasAndField($fieldInfos, $key);
		$aliasPrice = $tmp['aliasPrice'];
		$fieldPrice = $tmp['fieldPrice'];
		$aliasCurrency = $tmp['aliasCurrency'];
		$fieldCurrency = $tmp['fieldCurrency'];

		if (!empty($filterValue)) {
			return natural_search($aliasPrice . $fieldPrice, $filterValue, 1);
		}
		if (!empty($filterCurrency) && !empty($fieldCurrency)) {
			return natural_search($aliasCurrency . $fieldCurrency, $filterCurrency, 0);
		}

		return '';
	}

	/**
	 * Get price and currency from value
	 *
	 * @param	FieldInfos								$fieldInfos		Properties of the field
	 * @param	string									$value			Value in memory is a php string like '0.01:EUR'
	 * @return	array{price:double,currency:string}
	 */
	public function getPriceAndCurrencyFromValue($fieldInfos, $value)
	{
		global $conf;

		if ($this->isEmptyValue($fieldInfos, $value)) {
			$price = '';
			$currency = $conf->currency;
		} else {
			// $value in memory is a php string like '10.01:USD'
			$tmp = explode(':', $value);
			$price = $this->isEmptyValue($fieldInfos, $tmp[0] ?? '') ? '' : $tmp[0];
			$currency = !empty($tmp[1]) ? $tmp[1] : $conf->currency;
		}

		return array(
			'price' => (double) $price,
			'currency' => $currency
		);
	}

	/**
	 * Get alias and field name in table for price and currency
	 *
	 * @param	FieldInfos								$fieldInfos		Properties of the field
	 * @param	string									$key			Key of field
	 * @return	array{aliasPrice:string,aliasCurrency:string,fieldPrice:string,fieldCurrency:string}
	 */
	public function getPriceAndCurrencyAliasAndField($fieldInfos, $key)
	{
		$alias = $fieldInfos->sqlAlias ?? 't.';
		$tmp = explode(':', $alias);
		$aliasPrice = $tmp[0] ?? '';
		$aliasCurrency = $tmp[1] ?? '';

		$field = $fieldInfos->nameInTable ?? $key;
		$tmp = explode(':', $field);
		$fieldPrice = $tmp[0] ?? '';
		$fieldCurrency = $tmp[1] ?? '';

		return array(
			'aliasPrice' => trim($aliasPrice),
			'aliasCurrency' => trim($aliasCurrency),
			'fieldPrice' => trim($fieldPrice),
			'fieldCurrency' => trim($fieldCurrency),
		);
	}
}
