<?php
/* Copyright (C) 2011-2012	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2011-2012	Regis Houssin		<regis@dolibarr.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 *	    \file       htdocs/core/lib/json.lib.php
 *		\brief      Functions to emulate json function for PHP < 5.3 compatibility
 * 		\ingroup	core
 */

if (! function_exists('json_encode'))
{
	/**
	 * Implement json_encode for PHP that does not support it
	 *
	 * @param	mixed	$elements		PHP Object to json encode
	 * @return 	string					Json encoded string
	 */
	function json_encode($elements)
	{
		return dol_json_encode($elements);
	}
}

/**
 * Implement json_encode for PHP that does not support it
 *
 * @param	mixed	$elements		PHP Object to json encode
 * @return 	string					Json encoded string
 */
function dol_json_encode($elements)
{
	$num = count($elements);

	// determine type
	if (is_numeric(key($elements)))
	{
		// indexed (list)
		$output = '[';
		for ($i = 0, $last = ($num - 1); isset($elements[$i]); ++$i)
		{
			if (is_array($elements[$i])) $output.= json_encode($elements[$i]);
			else $output .= _val($elements[$i]);
			if($i !== $last) $output.= ',';
		}
		$output.= ']';
	}
	else
	{
		// associative (object)
		$output = '{';
		$last = $num - 1;
		$i = 0;
		foreach($elements as $key => $value)
		{
			$output .= '"'.$key.'":';
			if (is_array($value)) $output.= json_encode($value);
			else $output .= _val($value);
			if ($i !== $last) $output.= ',';
			++$i;
		}
		$output.= '}';
	}

	// return
	return $output;
}

/**
 * Return text according to type
 *
 * @param 	mixed	$val	Value to show
 * @return	string			Formated value
 */
function _val($val)
{
	if (is_string($val))
	{
        // STRINGS ARE EXPECTED TO BE IN ASCII OR UTF-8 FORMAT
        $ascii = '';
        $strlen_var = strlen($val);

        /*
	     * Iterate over every character in the string,
	     * escaping with a slash or encoding to UTF-8 where necessary
	     */
	    for ($c = 0; $c < $strlen_var; ++$c) {

	        $ord_var_c = ord($val{$c});

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
	                $ascii .= '\\'.$val{$c};
	                break;

	            case (($ord_var_c >= 0x20) && ($ord_var_c <= 0x7F)):
	                // characters U-00000000 - U-0000007F (same as ASCII)
	                $ascii .= $val{$c};
	                break;

	            case (($ord_var_c & 0xE0) == 0xC0):
	                // characters U-00000080 - U-000007FF, mask 110XXXXX
	                // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
	                $char = pack('C*', $ord_var_c, ord($val{$c + 1}));
	                $c += 1;
	                $utf16 = utf82utf16($char);
	                $ascii .= sprintf('\u%04s', bin2hex($utf16));
	                break;

	            case (($ord_var_c & 0xF0) == 0xE0):
	                // characters U-00000800 - U-0000FFFF, mask 1110XXXX
	                // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
	                $char = pack('C*', $ord_var_c, ord($val{$c + 1}), ord($val{$c + 2}));
	                $c += 2;
	                $utf16 = utf82utf16($char);
	                $ascii .= sprintf('\u%04s', bin2hex($utf16));
	                break;

	            case (($ord_var_c & 0xF8) == 0xF0):
	                // characters U-00010000 - U-001FFFFF, mask 11110XXX
	                // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
	                $char = pack('C*', $ord_var_c, ord($val{$c + 1}), ord($val{$c + 2}), ord($val{$c + 3}));
	                $c += 3;
	                $utf16 = utf82utf16($char);
	                $ascii .= sprintf('\u%04s', bin2hex($utf16));
	                break;

	            case (($ord_var_c & 0xFC) == 0xF8):
	                // characters U-00200000 - U-03FFFFFF, mask 111110XX
	                // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
	                $char = pack('C*', $ord_var_c, ord($val{$c + 1}), ord($val{$c + 2}), ord($val{$c + 3}), ord($val{$c + 4}));
	                $c += 4;
	                $utf16 = utf82utf16($char);
	                $ascii .= sprintf('\u%04s', bin2hex($utf16));
	                break;

	            case (($ord_var_c & 0xFE) == 0xFC):
	                // characters U-04000000 - U-7FFFFFFF, mask 1111110X
	                // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
	                $char = pack('C*', $ord_var_c, ord($val{$c + 1}), ord($val{$c + 2}), ord($val{$c + 3}), ord($val{$c + 4}), ord($val{$c + 5}));
	                $c += 5;
	                $utf16 = utf82utf16($char);
	                $ascii .= sprintf('\u%04s', bin2hex($utf16));
	                break;
	        }
	    }

	    return '"'.$ascii.'"';
	}
	elseif (is_int($val)) return sprintf('%d', $val);
	elseif (is_float($val)) return sprintf('%F', $val);
	elseif (is_bool($val)) return ($val ? 'true' : 'false');
	else  return 'null';
}

if (! function_exists('json_decode'))
{
	/**
	 * Implement json_decode for PHP that does not support it
	 *
	 * @param	string	$json		Json encoded to PHP Object or Array
	 * @param	bool	$assoc		False return an object, true return an array
	 * @return 	mixed				Object or Array
	 */
	function json_decode($json, $assoc=false)
	{
		return dol_json_decode($json, $assoc);
	}
}

/**
 * Implement json_decode for PHP that does not support it
 *
 * @param	string	$json		Json encoded to PHP Object or Array
 * @param	bool	$assoc		False return an object, true return an array. Try to always use it with true !
 * @return 	mixed				Object or Array
 */
function dol_json_decode($json, $assoc=false)
{
	$comment = false;

    $out='';
	$strLength = strlen($json);    // Must stay strlen and not dol_strlen because we want technical length, not visible length
	for ($i=0; $i<$strLength; $i++)
	{
		if (! $comment)
		{
			if (($json[$i] == '{') || ($json[$i] == '[')) $out.= 'array(';
			else if (($json[$i] == '}') || ($json[$i] == ']')) $out.= ')';
			else if ($json[$i] == ':') $out.= ' => ';
			else $out.=$json[$i];
		}
		else $out.= $json[$i];
		if ($json[$i] == '"' && $json[($i-1)]!="\\") $comment = !$comment;
	}

	$out=_unval($out);

	// Return an array
	if ($out != '') eval('$array = '.$out.';');
	else $array=array();

	// Return an object
	if (! $assoc)
	{
		if (! empty($array))
		{
			$object = false;

			foreach ($array as $key => $value)
			{
				$object->{$key} = $value;
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
 * @param 	mixed	$val	Value to decode
 * @return	string			Formated value
 */
function _unval($val)
{
	while (preg_match('/\\\u([0-9A-F]{2})([0-9A-F]{2})/i', $val, $reg))
	{
	    // single, escaped unicode character
	    $utf16 = chr(hexdec($reg[1])) . chr(hexdec($reg[2]));
	    $utf8 .= utf162utf8($utf16);
	    $val=preg_replace('/\\\u'.$reg[1].$reg[2].'/i',$utf8,$val);
	}
	return $val;
}

/**
 * convert a string from one UTF-16 char to one UTF-8 char
 *
 * Normally should be handled by mb_convert_encoding, but
 * provides a slower PHP-only method for installations
 * that lack the multibye string extension.
 *
 * @param    string  $utf16  UTF-16 character
 * @return   string  UTF-8 character
 */
function utf162utf8($utf16)
{
	// oh please oh please oh please oh please oh please
	if(function_exists('mb_convert_encoding')) {
	    return mb_convert_encoding($utf16, 'UTF-8', 'UTF-16');
	}

	$bytes = (ord($utf16{0}) << 8) | ord($utf16{1});

	switch(true) {
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

/**
 * convert a string from one UTF-8 char to one UTF-16 char
 *
 * Normally should be handled by mb_convert_encoding, but
 * provides a slower PHP-only method for installations
 * that lack the multibye string extension.
 *
 * @param    string  $utf8   UTF-8 character
 * @return   string  UTF-16 character
 */
function utf82utf16($utf8)
{
	// oh please oh please oh please oh please oh please
	if(function_exists('mb_convert_encoding')) {
	return mb_convert_encoding($utf8, 'UTF-16', 'UTF-8');
	}

	switch(strlen($utf8)) {
	case 1:
	        // this case should never be reached, because we are in ASCII range
	// see: http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
	return $utf8;

	case 2:
	// return a UTF-16 character from a 2-byte UTF-8 char
	// see: http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
	return chr(0x07 & (ord($utf8{0}) >> 2)) . chr((0xC0 & (ord($utf8{0}) << 6)) | (0x3F & ord($utf8{1})));

	case 3:
	// return a UTF-16 character from a 3-byte UTF-8 char
	// see: http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
	return chr((0xF0 & (ord($utf8{0}) << 4)) | (0x0F & (ord($utf8{1}) >> 2))) . chr((0xC0 & (ord($utf8{1}) << 6)) | (0x7F & ord($utf8{2})));
	}

	// ignoring UTF-32 for now, sorry
	return '';
}
