<?php
/* Copyright (C) 2011-2012	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2011-2012	Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
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
 * or see https://www.gnu.org/
 */

/**
 *	    \file       htdocs/core/lib/json.lib.php
 *		\brief      Functions to emulate json function when there were not activated
 * 		\ingroup	core
 */

if (!function_exists('json_encode') || defined('PHPUNIT_MODE')) {
	/**
	 * Implement json_encode for PHP that does not support it.
	 * Use json_encode and json_decode in your code !
	 * Note: We can found some special chars into a json string:
	 * Quotation mark (") = \", Backslash (\) = \\, Slash (/) =	\/, Backspace = \b, Form feed = \f, New line =\n, Carriage return =\r, Horizontal tab = \t
	 *
	 * @param	mixed	$elements		PHP Object to json encode
	 * @return 	string					Json encoded string
	 * @see json_encode()
	 * @deprecated Use native PHP json module
	 */
	function dol_json_encode($elements)
	{
		dol_syslog("For better performance, enable the native json in your PHP", LOG_WARNING);

		$num = 0;
		if (is_object($elements)) {	// Count number of properties for an object
			foreach ($elements as $key => $value) {
				$num++;
			}
		} else {
			if (is_countable($elements)) {
				$num = count($elements);
			}
		}

		// determine type
		if (is_numeric($elements)) {
			return $elements;
		} elseif (is_string($elements)) {
			return '"'.$elements.'"';
		}
		if (is_numeric(key($elements)) && key($elements) == 0) {
			// indexed (list)
			$keysofelements = array_keys($elements); // Elements array must have key that does not start with 0 and end with num-1, so we will use this later.
			$output = '[';
			for ($i = 0, $last = ($num - 1); $i < $num; $i++) {
				if (!isset($elements[$keysofelements[$i]])) {
					continue;
				}
				if (is_array($elements[$keysofelements[$i]]) || is_object($elements[$keysofelements[$i]])) {
					$output .= json_encode($elements[$keysofelements[$i]]);
				} else {
					$output .= _val($elements[$keysofelements[$i]]);
				}
				if ($i !== $last) {
					$output .= ',';
				}
			}
			$output .= ']';
		} else {
			// associative (object)
			$output = '{';
			$last = $num - 1;
			$i = 0;
			$tmpelements = array();
			if (is_array($elements)) {
				$tmpelements = $elements;
			}
			if (is_object($elements)) {
				$tmpelements = get_object_vars($elements);
			}
			foreach ($tmpelements as $key => $value) {
				$output .= '"'.$key.'":';
				if (is_array($value)) {
					$output .= json_encode($value);
				} else {
					$output .= _val($value);
				}
				if ($i !== $last) {
					$output .= ',';
				}
				++$i;
			}
			$output .= '}';
		}

		// return
		return $output;
	}

	/**
	 * Return text according to type
	 *
	 * @param 	mixed	$val	Value to show
	 * @return	string			Formatted value
	 */
	function _val($val)
	{
		if (is_string($val)) {
			// STRINGS ARE EXPECTED TO BE IN ASCII OR UTF-8 FORMAT
			$ascii = '';
			$strlen_var = strlen($val);

			/*
			 * Iterate over every character in the string,
			 * escaping with a slash or encoding to UTF-8 where necessary
			 */
			for ($c = 0; $c < $strlen_var; ++$c) {
				$ord_var_c = ord($val[$c]);

				switch (true) {
					case $ord_var_c == 0x08:
						$ascii .= '\b';
						break;
					case $ord_var_c == 0x09:
						$ascii .= '\t';
						break;
					case $ord_var_c == 0x0A:
						$ascii .= '\n';
						break;
					case $ord_var_c == 0x0C:
						$ascii .= '\f';
						break;
					case $ord_var_c == 0x0D:
						$ascii .= '\r';
						break;

					case $ord_var_c == 0x22:
					case $ord_var_c == 0x2F:
					case $ord_var_c == 0x5C:
						// double quote, slash, slosh
						$ascii .= '\\'.$val[$c];
						break;

					case (($ord_var_c >= 0x20) && ($ord_var_c <= 0x7F)):
						// characters U-00000000 - U-0000007F (same as ASCII)
						$ascii .= $val[$c];
						break;

					case (($ord_var_c & 0xE0) == 0xC0):
						// characters U-00000080 - U-000007FF, mask 110XXXXX
						// see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
						$char = pack('C*', $ord_var_c, ord($val[$c + 1]));
						$c += 1;
						$utf16 = utf82utf16($char);
						$ascii .= sprintf('\u%04s', bin2hex($utf16));
						break;

					case (($ord_var_c & 0xF0) == 0xE0):
						// characters U-00000800 - U-0000FFFF, mask 1110XXXX
						// see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
						$char = pack('C*', $ord_var_c, ord($val[$c + 1]), ord($val[$c + 2]));
						$c += 2;
						$utf16 = utf82utf16($char);
						$ascii .= sprintf('\u%04s', bin2hex($utf16));
						break;

					case (($ord_var_c & 0xF8) == 0xF0):
						// characters U-00010000 - U-001FFFFF, mask 11110XXX
						// see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
						$char = pack('C*', $ord_var_c, ord($val[$c + 1]), ord($val[$c + 2]), ord($val[$c + 3]));
						$c += 3;
						$utf16 = utf82utf16($char);
						$ascii .= sprintf('\u%04s', bin2hex($utf16));
						break;

					case (($ord_var_c & 0xFC) == 0xF8):
						// characters U-00200000 - U-03FFFFFF, mask 111110XX
						// see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
						$char = pack('C*', $ord_var_c, ord($val[$c + 1]), ord($val[$c + 2]), ord($val[$c + 3]), ord($val[$c + 4]));
						$c += 4;
						$utf16 = utf82utf16($char);
						$ascii .= sprintf('\u%04s', bin2hex($utf16));
						break;

					case (($ord_var_c & 0xFE) == 0xFC):
						// characters U-04000000 - U-7FFFFFFF, mask 1111110X
						// see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
						$char = pack('C*', $ord_var_c, ord($val[$c + 1]), ord($val[$c + 2]), ord($val[$c + 3]), ord($val[$c + 4]), ord($val[$c + 5]));
						$c += 5;
						$utf16 = utf82utf16($char);
						$ascii .= sprintf('\u%04s', bin2hex($utf16));
						break;
				}
			}

			return '"'.$ascii.'"';
		} elseif (is_int($val)) {
			return sprintf('%d', $val);
		} elseif (is_float($val)) {
			return sprintf('%F', $val);
		} elseif (is_bool($val)) {
			return ($val ? 'true' : 'false');
		} else {
			return 'null';
		}
	}


	/**
	 * Convert a string from one UTF-8 char to one UTF-16 char
	 *
	 * Normally should be handled by mb_convert_encoding, but
	 * provides a slower PHP-only method for installations
	 * that lack the multibyte string extension.
	 *
	 * @param    string  $utf8		UTF-8 character
	 * @return   string  			UTF-16 character
	 */
	function utf82utf16($utf8)
	{
		// oh please oh please oh please oh please oh please
		if (function_exists('mb_convert_encoding')) {
			return mb_convert_encoding($utf8, 'UTF-16', 'UTF-8');
		}

		switch (strlen($utf8)) {
			case 1:
				// this case should never be reached, because we are in ASCII range
				// see: http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
				return $utf8;

			case 2:
				// return a UTF-16 character from a 2-byte UTF-8 char
				// see: http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
				return chr(0x07 & (ord($utf8[0]) >> 2)).chr((0xC0 & (ord($utf8[0]) << 6)) | (0x3F & ord($utf8[1])));

			case 3:
				// return a UTF-16 character from a 3-byte UTF-8 char
				// see: http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
				return chr((0xF0 & (ord($utf8[0]) << 4)) | (0x0F & (ord($utf8[1]) >> 2))).chr((0xC0 & (ord($utf8[1]) << 6)) | (0x7F & ord($utf8[2])));
		}

		// ignoring UTF-32 for now, sorry
		return '';
	}
}

if (!function_exists('json_encode')) {
	/**
	 * Implement json_encode for PHP that does not have module enabled.
	 *
	 * @param	mixed	$elements		PHP Object to json encode
	 * @return 	string					Json encoded string
	 * @phan-suppress PhanRedefineFunctionInternal
	 */
	function json_encode($elements)
	{
		return dol_json_encode($elements);
	}
}


if (!function_exists('json_decode') || defined('PHPUNIT_MODE')) {
	/**
	 * Implement json_decode for PHP that does not support it
	 * Use json_encode and json_decode in your code !
	 *
	 * @param	string	$json		Json encoded to PHP Object or Array
	 * @param	bool	$assoc		False return an object, true return an array. Try to always use it with true !
	 * @return 	mixed				Object or Array or false on error
	 * @see json_decode()
	 * @deprecated Use native PHP json module
	 */
	function dol_json_decode($json, $assoc = false)
	{
		dol_syslog("For better performance and security, enable the native json in your PHP", LOG_WARNING);

		$comment = false;

		$out = '';
		$strLength = strlen($json); // Must stay strlen and not dol_strlen because we want technical length, not visible length

		if (is_numeric($json)) {
			return $json;
		}

		for ($i = 0; $i < $strLength; $i++) {
			if (!$comment) {
				if ($i == 0 && !in_array($json[$i], array('{', '[', '"', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9'))) {
					// Not a json format
					return false;
				}
				if (($json[$i] == '{') || ($json[$i] == '[')) {
					$out .= 'array(';
				} elseif (($json[$i] == '}') || ($json[$i] == ']')) {
					$out .= ')';
				} elseif ($json[$i] == ':') {
					$out .= ' => ';
				} else {
					$out .= $json[$i];
				}
			} else {
				$out .= $json[$i];
			}
			// @phan-suppress-next-line PhanCompatibleNegativeStringOffset
			if ($i >= 1 && $json[$i] == '"' && $json[$i - 1] != "\\") {
				$comment = !$comment;
			}
		}

		$out = _unval($out);

		$array = array();

		// Return an array
		if ($out != '') {
			try {
				// @phan-suppress-next-line PhanPluginUnsafeEval
				eval('$array = '.$out.';');		// not secured but this is no mode used as php json lib is always expected to be loaded now.
			} catch (Exception $e) {
				$array = array();
			}
		}

		// Return an object
		if (!$assoc) {
			if (!empty($array)) {
				$object = false;
				if (count($array) > 0) {
					$object = (object) array();
				}
				foreach ($array as $key => $value) {
					if ($key) {
						$object->{$key} = $value;
					}
				}

				return $object;
			}

			return false;
		}

		return $array;
	}

	/**
	 * Return text according to type
	 *
	 * @param   string  $val    Value to decode
	 * @return  string          Formatted value
	 */
	function _unval($val)
	{
		$reg = array();
		while (preg_match('/\\\u([0-9A-F]{2})([0-9A-F]{2})/i', $val, $reg)) {
			// single, escaped unicode character
			$utf16 = chr(hexdec($reg[1])).chr(hexdec($reg[2]));
			$utf8  = utf162utf8($utf16);
			$val = preg_replace('/\\\u'.$reg[1].$reg[2].'/i', $utf8, $val);
		}
		return $val;
	}

	/**
	 * Convert a string from one UTF-16 char to one UTF-8 char
	 *
	 * Normally should be handled by mb_convert_encoding, but
	 * provides a slower PHP-only method for installations
	 * that lack the multibyte string extension.
	 *
	 * @param    string  $utf16		UTF-16 character
	 * @return   string  			UTF-8 character
	 */
	function utf162utf8($utf16)
	{
		// oh please oh please oh please oh please oh please
		if (function_exists('mb_convert_encoding')) {
			return mb_convert_encoding($utf16, 'UTF-8', 'UTF-16');
		}

		$bytes = (ord($utf16[0]) << 8) | ord($utf16[1]);

		switch (true) {
			case ((0x7F & $bytes) == $bytes):
				// this case should never be reached, because we are in ASCII range
				// see: http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
				return chr($bytes);

			case (0x07FF & $bytes) == $bytes:
				// return a 2-byte UTF-8 character
				// see: http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
				return chr(0xC0 | (($bytes >> 6) & 0x1F))
				. chr(0x80 | ($bytes & 0x3F));

			case (0xFFFF & $bytes) == $bytes:
				// return a 3-byte UTF-8 character
				// see: http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
				return chr(0xE0 | (($bytes >> 12) & 0x0F))
				. chr(0x80 | (($bytes >> 6) & 0x3F))
				. chr(0x80 | ($bytes & 0x3F));
		}

		// ignoring UTF-32 for now, sorry
		return '';
	}
}

if (!function_exists('json_decode')) {
	/**
	 * Implement json_decode for PHP that does not support it
	 *
	 * @param	string	$json		Json encoded to PHP Object or Array
	 * @param	bool	$assoc		False return an object, true return an array
	 * @return 	mixed				Object or Array
	 * @phan-suppress PhanRedefineFunctionInternal
	 */
	function json_decode($json, $assoc = false)
	{
		return dol_json_decode($json, $assoc);
	}
}