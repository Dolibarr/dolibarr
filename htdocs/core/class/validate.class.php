<?php
/* Copyright (C) 2021 John BOTELLA <john.botella@atm-consulting.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * any later version.
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
 *   	\file       htdocs/core/class/validate.class.php
 *      \ingroup    core
 *		\brief      File for Utils class
 */


/**
 *		Class toolbox to validate values
 */
class Validate
{

	/**
	 * @var DoliDb		Database handler (result of a new DoliDB)
	 */
	public $db;

	/**
	 * @var Translate $outputLang
	 */
	public $outputLang;

	/**
	 * @var string 		Error string
	 * @see             $errors
	 */
	public $error;


	/**
	 * Constructor
	 *
	 * @param DoliDB 		$db 			Database handler
	 * @param Translate   	$outputLang 	Output lang for error
	 */
	public function __construct($db, $outputLang = null)
	{
		global $langs;

		if (empty($outputLang)) {
			$this->outputLang = $langs;
		} else {
			$this->outputLang = $outputLang;
		}

		if (!is_object($this->outputLang) || !method_exists($this->outputLang, 'load')) {
			return false;
		}

		$this->outputLang->loadLangs(array('validate', 'errors'));

		$this->db = $db;
	}

	/**
	 * Use to clear errors msg or other ghost vars
	 * @return null
	 */
	protected function clear()
	{
		$this->error = '';
	}

	/**
	 * Use to clear errors msg or other ghost vars
	 *
	 * @param string $errMsg your error message
	 * @return null
	 */
	protected function setError($errMsg)
	{
		$this->error = $errMsg;
	}

	/**
	 * Check for e-mail validity
	 *
	 * @param string $email e-mail address to validate
	 * @param int   $maxLength string max length
	 * @return boolean Validity is ok or not
	 */
	public function isEmail($email, $maxLength = false)
	{
		if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			$this->error = $this->outputLang->trans('RequireValidEmail');
			return false;
		}
		return true;
	}

	/**
	 * Check for price validity
	 *
	 * @param string $price Price to validate
	 * @return boolean Validity is ok or not
	 */
	public function isPrice($price)
	{
		if (!preg_match('/^[0-9]{1,10}(\.[0-9]{1,9})?$/ui', $price)) {
			$this->error = $this->outputLang->trans('RequireValidValue');
			return false;
		}
		return true;
	}

	/**
	 * Check for timestamp validity
	 *
	 * @param string|int $stamp timestamp to validate
	 * @return boolean Validity is ok or not
	 */
	public function isTimestamp($stamp)
	{
		if (!is_numeric($stamp) && (int) $stamp == $stamp) {
			$this->error = $this->outputLang->trans('RequireValidDate');
			return false;
		}
		return true;
	}

	/**
	 * Check for phone validity
	 *
	 * @param string $phone Phone string to validate
	 * @return boolean Validity is ok or not
	 */
	public function isPhone($phone)
	{
		if (!preg_match('/^[+0-9. ()-]*$/ui', $phone)) {
			$this->error = $this->outputLang->trans('RequireValidPhone');
			return false;
		}
		return true;
	}

	/**
	 * Check for string max length validity
	 *
	 * @param string $string to validate
	 * @param int  $length max length
	 * @return boolean Validity is ok or not
	 */
	public function isMaxLength($string, $length)
	{
		if (strlen($string) > $length) {
			$this->error = $this->outputLang->trans('RequireMaxLength', $length);
			return false;
		}
		return true;
	}

	/**
	 * Check for string not empty
	 *
	 * @param string $string to validate
	 * @return boolean Validity is ok or not
	 */
	public function isNotEmptyString($string)
	{
		if (!strlen($string)) {
			$this->error = $this->outputLang->trans('RequireANotEmptyValue');
			return false;
		}
		return true;
	}

	/**
	 * Check for string min length validity
	 *
	 * @param string $string to validate
	 * @param int  $length max length
	 * @return boolean Validity is ok or not
	 */
	public function isMinLength($string, $length)
	{
		if (!strlen($string) < $length) {
			$this->error = $this->outputLang->trans('RequireMinLength', $length);
			return false;
		}
		return true;
	}

	/**
	 * Check url validity
	 *
	 * @param string $url to validate
	 * @return boolean Validity is ok or not
	 */
	public function isUrl($url)
	{
		if (!filter_var($url, FILTER_VALIDATE_URL)) {
			$this->error = $this->outputLang->trans('RequireValidUrl');
			return false;
		}
		return true;
	}

	/**
	 * Check Duration validity
	 *
	 * @param mixed $duration to validate
	 * @return boolean Validity is ok or not
	 */
	public function isDuration($duration)
	{
		if (!is_int($duration) && $duration >= 0) {
			$this->error = $this->outputLang->trans('RequireValidDuration');
			return false;
		}
		return true;
	}

	/**
	 * Check numeric validity
	 *
	 * @param mixed $string to validate
	 * @return boolean Validity is ok or not
	 */
	public function isNumeric($string)
	{
		if (!is_numeric($string)) {
			$this->error = $this->outputLang->trans('RequireValidNumeric');
			return false;
		}
		return true;
	}

	/**
	 * Check for boolean validity
	 *
	 * @param boolean $bool Boolean to validate
	 * @return boolean Validity is ok or not
	 */
	public function isBool($bool)
	{
		if (!(is_null($bool) || is_bool($bool) || preg_match('/^[0|1]{1}$/ui', $bool))) {
			$this->error = $this->outputLang->trans('RequireValidBool');
			return false;
		}
		return true;
	}

	/**
	 * Check for all values in db
	 *
	 * @param array  $values Boolean to validate
	 * @param string $table  the db table name without $this->db->prefix()
	 * @param string $col    the target col
	 * @return boolean Validity is ok or not
	 * @throws Exception
	 */
	public function isInDb($values, $table, $col)
	{
		if (!is_array($values)) {
			$value_arr = array($values);
		} else {
			$value_arr = $values;
		}

		if (!count($value_arr)) {
			$this->error = $this->outputLang->trans('RequireValue');
			return false;
		}

		foreach ($value_arr as $val) {
			$sql = "SELECT ".$col." FROM ".$this->db->prefix().$table." WHERE ".$col." = '".$this->db->escape($val)."' LIMIT 1"; // more quick than count(*) to check existing of a row
			$resql = $this->db->query($sql);
			if ($resql) {
				$obj = $this->db->fetch_object($resql);
				if ($obj) {
					continue;
				}
			}
			// If something was wrong
			$this->error = $this->outputLang->trans('RequireValidExistingElement');
			return false;
		}

		return true;
	}

	/**
	 * Check for all values in db
	 *
	 * @param integer  $id of element
	 * @param string $classname the class name
	 * @param string $classpath the class path
	 * @return boolean Validity is ok or not
	 * @throws Exception
	 */
	public function isFetchable($id, $classname, $classpath)
	{
		if (!empty($classpath)) {
			if (dol_include_once($classpath)) {
				if ($classname && class_exists($classname)) {
					/** @var CommonObject $object */
					$object = new $classname($this->db);

					if (!is_callable(array($object, 'fetch')) || !is_callable(array($object, 'isExistingObject'))) {
						$this->error = $this->outputLang->trans('BadSetupOfFieldFetchNotCallable');
						return false;
					}

					if (!empty($object->table_element) && $object->isExistingObject($object->table_element, $id)) {
						return true;
					} else { $this->error = $this->outputLang->trans('RequireValidExistingElement'); }
				} else { $this->error = $this->outputLang->trans('BadSetupOfFieldClassNotFoundForValidation'); }
			} else { $this->error = $this->outputLang->trans('BadSetupOfFieldFileNotFound'); }
		} else { $this->error = $this->outputLang->trans('BadSetupOfField'); }
		return false;
	}
}
