<?php
/* Copyright (C) 2011-2012	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2011-2012	Regis Houssin		<regis.houssin@capnetworks.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 *	    \file       htdocs/core/lib/json.lib.php
 *		\brief      Functions to emulate json function for PHP < 5.3 compatibility
 * 		\ingroup	core
 */

/**
 * Implement json_encode for PHP that does not support it
 *
 * @param	mixed	$elements		PHP Object to json encode
 * @return 	string					Json encoded string
 * @deprecated Use json_encode native function
 */
function dol_json_encode($elements)
{
	return json_encode($elements);
}

/**
 * Implement json_decode for PHP that does not support it
 *
 * @param	string	$json		Json encoded to PHP Object or Array
 * @param	bool	$assoc		False return an object, true return an array. Try to always use it with true !
 * @return 	mixed				Object or Array
 * @deprecated Use json_decode native function
 */
function dol_json_decode($json, $assoc=false)
{
	return json_decode($json, $assoc);
}

/**
 * Convert a string from one UTF-16 char to one UTF-8 char
 *
 * Normally should be handled by mb_convert_encoding, but
 * provides a slower PHP-only method for installations
 * that lack the multibye string extension.
 *
 * @param    string  $utf16		UTF-16 character
 * @return   string  			UTF-8 character
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
 * Convert a string from one UTF-8 char to one UTF-16 char
 *
 * Normally should be handled by mb_convert_encoding, but
 * provides a slower PHP-only method for installations
 * that lack the multibye string extension.
 *
 * @param    string  $utf8		UTF-8 character
 * @return   string  			UTF-16 character
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
