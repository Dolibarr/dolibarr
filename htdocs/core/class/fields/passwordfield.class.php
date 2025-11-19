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
 *    \file        htdocs/core/class/fields/passwordfield.class.php
 *    \ingroup    core
 *    \brief      File of class to password field
 */

require_once DOL_DOCUMENT_ROOT . '/core/class/fields/commonfield.class.php';


/**
 *    Class to password field
 */
class PasswordField extends CommonField
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

		return self::$form->inputType('text', $htmlName, (string) $value, $htmlName, $moreCss, $moreAttrib);
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
		$moreAttrib = trim((string) $moreAttrib);
		if (empty($moreAttrib)) $moreAttrib = ' ' . $moreAttrib;
		$autoFocus = $fieldInfos->inputAutofocus ? ' autofocus' : '';
		$htmlName = $keyPrefix . $key . $keySuffix;

		$out = '<input style="display:none" type="text" name="fakeusernameremembered">'; // Hidden field to reduce impact of evil Google Chrome autopopulate bug.
		if ($htmlName == 'pass_crypted') {
			$out .= self::$form->inputType('password', 'pass', '', 'pass', $moreCss, ' autocomplete="new-password"' . $moreAttrib . $autoFocus);
			$out .= self::$form->inputType('hidden', 'pass_crypted', (string) $value, 'pass_crypted', $moreCss, $moreAttrib);
		} else {
			$out .= self::$form->inputType('password', $htmlName, (string) $value, $htmlName, $moreCss, ' autocomplete="new-password"' . $moreAttrib . $autoFocus);
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
		global $langs;

		return !$this->isEmptyValue($fieldInfos, $value) ? '<span class="opacitymedium">' . $langs->trans("Encrypted") . '</span>' : '';
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
		return parent::getInputCss($fieldInfos, $moreCss, $defaultCss ? $defaultCss : 'maxwidth100');
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
		global $conf, $langs, $user;

		$result = parent::verifyFieldValue($fieldInfos, $key, $value);
		if ($result && !$this->isEmptyValue($fieldInfos, $value)) {
			// Todo do we use other method ?
			if (getDolGlobalString('USER_PASSWORD_GENERATED')) {
				// Add a check on rules for password syntax using the setup of the password generator
				$modGeneratePassClass = 'modGeneratePass' . ucfirst(getDolGlobalString('USER_PASSWORD_GENERATED'));

				include_once DOL_DOCUMENT_ROOT . '/core/modules/security/generate/' . $modGeneratePassClass . '.class.php';
				if (class_exists($modGeneratePassClass)) {
					$modGeneratePass = new $modGeneratePassClass($this->db, $conf, $langs, $user);
					'@phan-var-force ModeleGenPassword $modGeneratePass';

					// To check an input user password, we disable the cleaning on ambiguous characters (this is used only for auto-generated password)
					$modGeneratePass->WithoutAmbi = 0;

					// Call to validatePassword($password) to check pass match rules
					$testpassword = $modGeneratePass->validatePassword($value);
					if (!$testpassword) {
						self::$validator->error = $langs->trans('RequireValidValue');
						return false;
					}
				}
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
			$value = GETPOST($htmlName, 'password');
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
			$value = GETPOST($htmlName, 'alpha');
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

			// TODO rework search on crypt password
			return natural_search($alias . ($fieldInfos->nameInTable ?? $key), $value, 0);
		}

		return '';
	}
}
