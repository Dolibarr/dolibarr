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
	 *    Constructor
	 *
	 * @param DoliDB $db Database handler
	 * @param Translate   $outputLang
	 */
	public function __construct($db,$outputLang = false)
	{
		global $langs;

		if ($outputLang) {
			$this->outputLang = $langs;
		} else {
			$this->outputLang = $outputLang;
		}

		$outputLang->load('validate');

		$this->db = $db;
	}

	/**
	 * Use to clear errors msg or other ghost vars
	 */
	protected function clear()
	{
		$this->error = '';
	}

	/**
	 * Use to clear errors msg or other ghost vars
	 */
	protected function setError($errMsg)
	{
		$this->error = '';
	}

	/**
	 * Check for e-mail validity
	 *
	 * @param string $email e-mail address to validate
	 * @param int   $maxLength
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
		if (!is_numeric($stamp) && (int)$stamp == $stamp) {
			$this->error = $this->outputLang->trans('RequireValideDate');
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
	 * @param int  $length max length
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

}
