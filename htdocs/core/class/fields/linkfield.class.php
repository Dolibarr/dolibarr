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
 *    \file        htdocs/core/class/fields/linkfield.class.php
 *    \ingroup    core
 *    \brief      File of class to link field
 */

require_once DOL_DOCUMENT_ROOT . '/core/class/fields/commonfield.class.php';


/**
 *    Class to link field
 */
class LinkField extends CommonField
{
	/**
	 * @var array<int,mixed> 	List of value deemed as empty (null always deemed as empty)
	 */
	public $emptyValues = array('', '-1', '0', 0);


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

		$optionParams = $this->getOptionsParams($fieldInfos->options);

		if (version_compare(DOL_VERSION, '19.0.0') < 0) {
			// Example: 'ObjectName:classPath:1:(status:=:1)'
			$objectDesc = $optionParams['all'];
			if (strpos($objectDesc, '$ID$') !== false && !empty($fieldInfos->object->id)) {
				$objectDesc = str_replace('$ID$', (string) $fieldInfos->object->id, $objectDesc);
			}

			$out = self::$form->selectForForms($objectDesc, $htmlName, (int) $value, 0, '', '', $moreCss, $moreAttrib);
		} else {
			// Example: 'ObjectName:classPath'		To not propagate any filter (selectForForms do ajax call and propagating SQL filter is blocked by some WAF).
			// Also we should use the one into the definition in the ->fields of $elem if found.
			$objectDesc = $optionParams['objectClass'] . ':' . $optionParams['pathToClass'];

			// Example: 'actioncomm:options_fff'	To be used in priority to know object linked with all its definition (including filters)
			// The selectForForms is called with parameter $objectfield defined, so the app can retrieve the filter inside the ajax component instead of being provided as parameters. The
			// filter was used to pass SQL requests leading to serious SQL injection problem. This should not be possible. Also the call of the ajax was broken by some WAF.
			$objectField = isset($fieldInfos->object) ? $fieldInfos->object->element . (!empty($fieldInfos->object->module) ? '@' . $fieldInfos->object->module : '') . ':' . ($fieldInfos->fieldType == FieldInfos::FIELD_TYPE_EXTRA_FIELD ? 'options_' : '') . $fieldInfos->nameInClass : '';

			$out = self::$form->selectForForms($objectDesc, $htmlName, (int) $value, 0, '', '', $moreCss, $moreAttrib, 0, 0, '', $objectField);
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
		global $langs;

		$moreCss = $this->getInputCss($fieldInfos, $moreCss);
		$moreAttrib = trim((string) $moreAttrib);
		if (empty($moreAttrib)) $moreAttrib = ' ' . $moreAttrib;
		$placeHolder = $fieldInfos->inputPlaceholder;
		$autoFocus = $fieldInfos->inputAutofocus ? ' autofocus' : '';
		$htmlName = $keyPrefix . $key . $keySuffix;
		$showEmpty = $fieldInfos->required && !$this->isEmptyValue($fieldInfos, $fieldInfos->defaultValue) ? 0 : 1;

		$optionParams = $this->getOptionsParams($fieldInfos->options);

		// If we have to add a create button
		if ($optionParams['addCreateButton']) {
			if (!empty($fieldInfos->picto)) {
				$moreCss .= ' widthcentpercentminusxx';
			} else {
				$moreCss .= ' widthcentpercentminusx';
			}
		} elseif (!empty($fieldInfos->picto)) {
			$moreCss .= ' widthcentpercentminusx';
		}

		if (version_compare(DOL_VERSION, '19.0.0') < 0) {
			// Example: 'ObjectName:classPath:1:(status:=:1)'
			$objectDesc = $optionParams['all'];
			if (strpos($objectDesc, '$ID$') !== false && !empty($fieldInfos->object->id)) {
				$objectDesc = str_replace('$ID$', (string) $fieldInfos->object->id, $objectDesc);
			}

			$out = self::$form->selectForForms($objectDesc, $htmlName, (int) $value, $showEmpty, '', $placeHolder, $moreCss, $moreAttrib . $autoFocus, 0, $fieldInfos->inputDisabled ? 1 : 0);
		} else {
			// Example: 'ObjectName:classPath'		To not propagate any filter (selectForForms do ajax call and propagating SQL filter is blocked by some WAF).
			// Also we should use the one into the definition in the ->fields of $elem if found.
			$objectDesc = $optionParams['objectClass'] . ':' . $optionParams['pathToClass'];

			// Example: 'actioncomm:options_fff'	To be used in priority to know object linked with all its definition (including filters)
			// The selectForForms is called with parameter $objectfield defined, so the app can retrieve the filter inside the ajax component instead of being provided as parameters. The
			// filter was used to pass SQL requests leading to serious SQL injection problem. This should not be possible. Also the call of the ajax was broken by some WAF.
			$objectField = isset($fieldInfos->object) ? $fieldInfos->object->element . (!empty($fieldInfos->object->module) ? '@' . $fieldInfos->object->module : '') . ':' . ($fieldInfos->fieldType == FieldInfos::FIELD_TYPE_EXTRA_FIELD ? 'options_' : '') . $fieldInfos->nameInClass : '';

			$out = self::$form->selectForForms($objectDesc, $htmlName, (int) $value, $showEmpty, '', $placeHolder, $moreCss, $moreAttrib . $autoFocus, 0, $fieldInfos->inputDisabled ? 1 : 0, '', $objectField);
		}

		if ($optionParams['addCreateButton'] &&                                                                                              // If we have to add a create button
			(!GETPOSTISSET('backtopage') || strpos(GETPOST('backtopage'), $_SERVER['PHP_SELF']) === 0) &&        // To avoid to open several times the 'Plus' button (we accept only one level)
			!$fieldInfos->inputDisabled &&                                                                                            // To avoid to show the button if the field is protected by a "disabled".
			empty($fieldInfos->otherParams['nonewbutton'])                                                                            // manually disable new button
		) {
			$class = $optionParams['objectClass'];
			$classfile = $optionParams['pathToClass'];
			$classpath = dirname(dirname($classfile));
			if (file_exists(dol_buildpath($classpath . '/card.php'))) {
				$url_path = dol_buildpath($classpath . '/card.php', 1);
			} else {
				$url_path = dol_buildpath($classpath . '/' . strtolower($class) . '_card.php', 1);
			}
			$paramforthenewlink = '';
			$paramforthenewlink .= (GETPOSTISSET('action') ? '&action=' . GETPOST('action', 'aZ09') : '');
			$paramforthenewlink .= (GETPOSTISSET('id') ? '&id=' . GETPOSTINT('id') : '');
			$paramforthenewlink .= (GETPOSTISSET('origin') ? '&origin=' . GETPOST('origin', 'aZ09') : '');
			$paramforthenewlink .= (GETPOSTISSET('originid') ? '&originid=' . GETPOSTINT('originid') : '');
			$paramforthenewlink .= '&fk_' . strtolower($class) . '=--IDFORBACKTOPAGE--';
			// TODO Add JavaScript code to add input fields already filled into $paramforthenewlink so we won't loose them when going back to main page
			$out .= '<a class="butActionNew" title="' . $langs->trans("New") . '" href="' . $url_path . '?action=create&backtopage=' . urlencode($_SERVER['PHP_SELF'] . $paramforthenewlink) . '"><span class="fa fa-plus-circle valignmiddle"></span></a>';
		}

		return $out;
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
		if (!$this->isEmptyValue($fieldInfos, $value)) {
			$optionParams = $this->getOptionsParams($fieldInfos->options);

			$classpath = $optionParams['pathToClass'];
			if (!empty($classpath)) {
				$classname = $optionParams['objectClass'];

				$object = $this->getObject($classname, $classpath);
				if (isset($object)) {
					'@phan-var-force CommonObject $object';
					if ($object->element === 'product') {    // Special case for product because default valut of fetch are wrong
						'@phan-var-force Product $object';
						$result = $object->fetch((int) $value, '', '', '', 0, 1, 1);
					} else {
						$result = $object->fetch($value);
					}
					if ($result > 0) {
						$getNomUrlParam1 = $optionParams['getNomUrlParam1'];
						$getNomUrlParam2 = $optionParams['getNomUrlParam2'];

						if ($object->element === 'product') {
							'@phan-var-force Product $object';
							$get_name_url_param_arr = array($getNomUrlParam1, $getNomUrlParam2, 0, -1, 0, '', 0, ' - ');
							if (isset($fieldInfos->getNameUrlParams)) {
								$get_name_url_params = explode(':', $fieldInfos->getNameUrlParams);
								if (!empty($get_name_url_params)) {
									$param_num_max = count($get_name_url_param_arr) - 1;
									foreach ($get_name_url_params as $param_num => $param_value) {
										if ($param_num > $param_num_max) {
											break;
										}
										$get_name_url_param_arr[$param_num] = $param_value;
									}
								}
							}

							/**
							 * @var Product $object
							 */
							return self::$form->getNomUrl($object, (int) $get_name_url_param_arr[0], $get_name_url_param_arr[1], (int) $get_name_url_param_arr[2], (int) $get_name_url_param_arr[3], (int) $get_name_url_param_arr[4], $get_name_url_param_arr[5], (int) $get_name_url_param_arr[6], $get_name_url_param_arr[7]);
						} elseif (get_class($object) == 'Categorie') {
							// For category object, rendering must use the same method than the one deinfed into showCategories()
							$color = $object->color;
							$sfortag = '<span class="noborderoncategories"' . ($color ? ' style="background: #' . $color . ';"' : ' style="background: #bbb"') . '>';
							$sfortag .= self::$form->getNomUrl($object, (int) $getNomUrlParam1, $getNomUrlParam2);
							$sfortag .= '</span>';
							return $sfortag;
						} else {
							return self::$form->getNomUrl($object, (int) $getNomUrlParam1, $getNomUrlParam2);
						}
					}
				} else {
					dol_syslog('Error bad setup of field : ' . $key, LOG_WARNING);
					return 'Error bad setup of field';
				}
			} else {
				dol_syslog('Error bad setup of field : ' . $key, LOG_WARNING);
				return 'Error bad setup of field';
			}
		}

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
			$optionParams = $this->getOptionsParams($fieldInfos->options);
			$classname = $optionParams['objectClass'];
			$classpath = $optionParams['pathToClass'];
			$object = $this->getObject($classname, $classpath);
			if (isset($object) && method_exists($object, 'isExistingObject') && !self::$validator->isFetchable((int) $value, $classname, $classpath) // All class don't have isExistingObject function ...
				&& (version_compare(DOL_VERSION, '19.0.0') < 0 || !self::$validator->isFetchableElement((int) $value, $classname)) // from V19 of Dolibarr, In some cases link use element instead of class, example project_task
			) {
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

		if (GETPOSTISSET($htmlName)) {
			$value = GETPOSTINT($htmlName);
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
			$value = GETPOST($htmlName, 'alphanohtml');
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
		if (!$this->isEmptyValue($fieldInfos, $value)) {
			$alias = $fieldInfos->sqlAlias ?? 't.';

			return natural_search($alias . ($fieldInfos->nameInTable ?? $key), $value, 2);
		}

		return '';
	}

	/**
	 * Get all parameters in the options
	 *
	 * @param	array<string,mixed>		$options	Options of the field
	 * @return	array{all:string,objectClass:string,pathToClass:string,addCreateButton:bool,getNomUrlParam1:string,getNomUrlParam2:string,filter:string,sortField:string}
	 */
	public function getOptionsParams($options)
	{
		$options = is_array($options) ? $options : array();
		$paramList = array_keys($options);
		// Example: $paramList[0] = 'ObjectClass:PathToClass[:AddCreateButtonOrNot[:Filter[:Sortfield]]]'
		// Example: $paramList[0] = 'ObjectClass:PathToClass:#getnomurlparam1=-1#getnomurlparam2=customer'
		// Example: $paramList[0] = 'ObjectName:classPath' but can also be 'ObjectName:classPath:1:(status:=:1)'

		$all = (string) $paramList[0];
		$InfoFieldList = explode(":", $all);

		$objectClass = (string) ($InfoFieldList[0] ?? '');
		$pathToClass = (string) ($InfoFieldList[1] ?? '');
		$addCreateButton = !empty($InfoFieldList[2]) && is_numeric($InfoFieldList[2]);
		$getNomUrlParam1 = 3;
		$getNomUrlParam2 = '';
		if (preg_match('/#getnomurlparam1=([^#:]*)/', $all, $matches)) {
			$getNomUrlParam1 = $matches[1];
		}
		if (preg_match('/#getnomurlparam2=([^#:]*)/', $all, $matches)) {
			$getNomUrlParam2 = $matches[1];
		}
		$filter = (string) ($InfoFieldList[3] ?? '');
		$sortField = (string) ($InfoFieldList[4] ?? '');

		return array(
			'all' => $all,
			'objectClass' => $objectClass,
			'pathToClass' => $pathToClass,
			'addCreateButton' => $addCreateButton,
			'getNomUrlParam1' => $getNomUrlParam1,
			'getNomUrlParam2' => $getNomUrlParam2,
			'filter' => $filter,
			'sortField' => $sortField,
		);
	}

	/**
	 * Get object handler
	 *
	 * @param	string					$objectClass	Class name
	 * @param	string					$pathToClass	Path to the class
	 * @return	CommonObject|null
	 */
	public function getObject($objectClass, $pathToClass)
	{
		dol_include_once($pathToClass);
		if ($objectClass && !class_exists($objectClass)) {
			// from V19 of Dolibarr, In some cases link use element instead of class, example project_task
			// TODO use newObjectByElement() introduce in V20 by PR #30036 for better errors management
			$element_prop = getElementProperties($objectClass);
			if ($element_prop) {
				$objectClass = $element_prop['classname'];
			}
		}

		if ($objectClass && class_exists($objectClass)) {
			return new $objectClass($this->db);
		}

		return null;
	}
}
