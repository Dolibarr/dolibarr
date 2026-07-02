<?php
/* Copyright (C) 2026	Open-Dsi	<support@open-dsi.fr>
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
 * \file       htdocs/core/lib/phone.lib.php
 * \ingroup    core
 * \brief      Helper functions for phone number formatting
 */

/**
 * Parse a stored phone number into country code and number parts
 *
 * Stored format is "+{code} {number}" (e.g. "+33 644986885").
 * If no code is found, returns empty code and the full string as number.
 *
 * @param	string	$phone		Stored phone number
 * @return	array{code:string,number:string}	Array with 'code' and 'number' keys
 */
function dol_parse_phone($phone)
{
	$phone = trim((string) $phone);
	if ($phone === '') {
		return array('code' => '', 'number' => '');
	}

	if (strpos($phone, '+') === 0) {
		$pos = strpos($phone, ' ');
		if ($pos !== false) {
			return array(
				'code' => substr($phone, 0, $pos),
				'number' => substr($phone, $pos + 1)
			);
		}
		// Phone starts with + but has no space: try to keep it as-is in code
		return array('code' => '', 'number' => $phone);
	}

	return array('code' => '', 'number' => $phone);
}

/**
 * Build a normalized phone string from code and number parts
 *
 * Strips formatting characters (spaces, dashes, dots, parentheses) from number.
 * Also strips the national trunk prefix for the country matching $code
 * (e.g. leading "0" for France, "8" for Russia, "06" for Hungary).
 * Returns "+{code} {number}" or just the number if code is empty.
 *
 * @param	DoliDB	$db			Database handler
 * @param	string	$code		Country calling code (e.g. "+33")
 * @param	string	$number		Phone number (may contain formatting)
 * @return	string				Normalized phone string
 */
function dol_build_phone($db, $code, $number)
{
	$number = preg_replace('/[\s\-\.\(\)]/', '', trim((string) $number));
	$code = trim((string) $code);

	if ($number === '') {
		return '';
	}

	if ($code !== '') {
		// Strip national trunk prefix (e.g. leading "0" for France)
		$sql = "SELECT trunk_prefix FROM ".$db->prefix()."c_country";
		$sql .= " WHERE phone_code = ".((int) ltrim($code, '+'));
		$sql .= " AND trunk_prefix IS NOT NULL AND trunk_prefix != ''";
		$sql .= " LIMIT 1";
		$resql = $db->query($sql);
		if ($resql) {
			$obj = $db->fetch_object($resql);
			if ($obj && isset($obj->trunk_prefix) && $obj->trunk_prefix !== '' && $obj->trunk_prefix !== null) {
				$prefix = $obj->trunk_prefix;
				if (strpos($number, $prefix) === 0) {
					$number = substr($number, strlen($prefix));
				}
			}
			$db->free($resql);
		}
	}

	if ($code !== '') {
		return $code.' '.$number;
	}

	return $number;
}

/**
 * Get the national trunk prefix for a phone code
 *
 * @param	DoliDB	$db			Database handler
 * @param	string	$phone_code	Phone code (e.g. "+33")
 * @return	string				Trunk prefix (e.g. "0") or empty string
 */
function dol_get_trunk_prefix($db, $phone_code)
{
	$phone_code = trim((string) $phone_code);
	if ($phone_code === '') {
		return '';
	}

	$sql = "SELECT trunk_prefix FROM ".$db->prefix()."c_country";
	$sql .= " WHERE phone_code = ".((int) ltrim($phone_code, '+'));
	$sql .= " AND trunk_prefix IS NOT NULL AND trunk_prefix != ''";
	$sql .= " LIMIT 1";
	$resql = $db->query($sql);
	if ($resql) {
		$obj = $db->fetch_object($resql);
		if ($obj && isset($obj->trunk_prefix) && $obj->trunk_prefix !== '' && $obj->trunk_prefix !== null) {
			$db->free($resql);
			return $obj->trunk_prefix;
		}
		$db->free($resql);
	}

	return '';
}

/**
 * Get the list of all distinct national trunk prefixes known in llx_c_country
 *
 * @param	DoliDB	$db		Database handler
 * @return	string[]		List of distinct non-empty trunk prefixes (e.g. array('0','1','8','06'))
 */
function dol_get_all_trunk_prefixes($db)
{
	static $cache = null;
	if ($cache !== null) {
		return $cache;
	}

	$cache = array();
	$sql = "SELECT DISTINCT trunk_prefix FROM ".$db->prefix()."c_country";
	$sql .= " WHERE trunk_prefix IS NOT NULL AND trunk_prefix != ''";
	$resql = $db->query($sql);
	if ($resql) {
		while ($obj = $db->fetch_object($resql)) {
			$cache[] = $obj->trunk_prefix;
		}
		$db->free($resql);
	}

	return $cache;
}

/**
 * Build a natural_search() SQL fragment for a phone/fax field, tolerant of the
 * international storage format "+{code} {number}" vs a national search input
 * (e.g. searching "0645821542" must still match stored "+33 645821542").
 *
 * @param	DoliDB			$db			Database handler
 * @param	string|string[]	$fields		Field(s) to search, same as natural_search()
 * @param	string			$value		Raw value entered by the user
 * @param	int				$nofirstand	1=Do not output the first 'AND'
 * @return	string			SQL to append to the query ('' if no value)
 */
function dol_natural_search_phone($db, $fields, $value, $nofirstand = 0)
{
	$value = trim((string) $value);
	if ($value === '') {
		return '';
	}

	$stripped = preg_replace('/[\s\-\.\(\)]/', '', $value);

	$candidates = array($stripped);
	if (strpos($stripped, '+') !== 0) {
		foreach (dol_get_all_trunk_prefixes($db) as $prefix) {
			if ($prefix !== '' && strpos($stripped, $prefix) === 0) {
				$candidates[] = substr($stripped, strlen($prefix));
			}
		}
	}

	$candidates = array_values(array_unique(array_filter($candidates, function ($c) {
		return $c !== '';
	})));
	if (empty($candidates)) {
		$candidates = array($value);
	}

	return natural_search($fields, implode('|', $candidates), 0, $nofirstand);
}

/**
 * Get the phone calling code for a country
 *
 * @param	DoliDB	$db				Database handler
 * @param	int		$country_id		Country rowid
 * @return	string					Phone code (e.g. "+33") or empty string
 */
function dol_get_phone_code_from_country($db, $country_id)
{
	$country_id = (int) $country_id;
	if ($country_id <= 0) {
		return '';
	}

	$sql = "SELECT phone_code FROM ".$db->prefix()."c_country WHERE rowid = ".((int) $country_id);
	$resql = $db->query($sql);
	if ($resql) {
		$obj = $db->fetch_object($resql);
		if ($obj && !empty($obj->phone_code)) {
			$db->free($resql);
			return '+'.$obj->phone_code;
		}
		$db->free($resql);
	}

	return '';
}
