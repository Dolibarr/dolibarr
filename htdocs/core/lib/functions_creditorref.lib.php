<?php
/* Copyright (C) 2026		Nick Fragoulis
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
 *	\file			htdocs/core/lib/functions_creditorref.lib.php
 *	\brief			ISO 11649 Structured Creditor Reference (RF) functions.
 *					Shared by all countries that use the RF standard
 *					(DE, GR, CH, NL, AT, FI, and any other SEPA country).
 *					Country-specific variants live in functions_<cc>.lib.php.
 */


/**
 * Convert an alphanumeric string to its all-digit form for ISO 7064 mod-97.
 *
 * Letters A-Z map to 10-35. Digits are kept. Anything else is dropped.
 *
 * @param	string	$str	Input string
 * @return	string			All-digit string suitable for dolCreditorRefMod97()
 */
function dolCreditorRefAlphaToDigits($str)
{
	$out = '';
	// Only A-Z and 0-9 take part in the computation, so drop anything else first.
	// The remaining string is pure ASCII, one byte per character.
	$str = preg_replace('/[^A-Z0-9]/', '', dol_strtoupper((string) $str));
	$len = dol_strlen($str);
	for ($i = 0; $i < $len; $i++) {
		$c = $str[$i];
		if (ctype_digit($c)) {
			$out .= $c;
		} else {
			$out .= (string) (ord($c) - ord('A') + 10);
		}
	}
	return $out;
}

/**
 * Calculate the two ISO 11649 (ISO 7064 mod-97-10) check digits.
 *
 * Leading zeros in the payload do not change the result, so a padded and
 * an unpadded payload give the same check digits.
 *
 * @param	string	$raw	Raw alphanumeric payload, no spaces
 * @return	string			Two-digit check string, '02' to '98'
 */
function dolCreditorRefCheckDigits($raw)
{
	$check = 98 - dolCreditorRefMod97(dolCreditorRefAlphaToDigits($raw.'RF00'));
	return ($check < 10) ? '0'.$check : (string) $check;
}

/**
 * Remainder of a long decimal string divided by 97.
 *
 * The numbers used by ISO 7064 are far larger than an integer can hold, so the
 * string is consumed in small pieces and only the remainder is carried over.
 * Pieces of 7 digits keep every intermediate value below 2^31, so this works on
 * a 32 bit build and needs no extension.
 *
 * @param	string	$digits		Decimal string, digits only
 * @return	int<0,96>			Remainder
 */
function dolCreditorRefMod97($digits)
{
	$rest = 0;
	$len = strlen($digits);
	for ($i = 0; $i < $len; $i += 7) {
		$rest = (int) ($rest.substr($digits, $i, 7)) % 97;
	}
	return $rest;
}

/**
 * Remove formatting spaces from a payment reference (machine form).
 *
 * @param	string	$ref	Reference, with or without spaces
 * @return	string			Reference without spaces
 */
function dolPayRefStrip($ref)
{
	return str_replace(' ', '', (string) $ref);
}

/**
 * Format a payment reference into groups of 4 characters (human form).
 *
 * @param	string	$ref	Reference without spaces
 * @return	string			Reference with a space every 4 characters
 */
function dolPayRefFormat($ref)
{
	return wordwrap((string) $ref, 4, ' ', true);
}

/**
 * Generate an ISO 11649 Structured Creditor Reference from a payload.
 *
 * Result is 'RF' + 2 check digits + payload, at most 25 characters.
 * The payload must be 1 to 21 alphanumeric characters after sanitisation.
 *
 * @param	string		$payload	Alphanumeric payload, max 21 chars after cleaning
 * @param	int<0,3>	$statut		Object status. Returns '' when lower than 1.
 * @return	string					Compact RF reference, or '' on error
 */
function dolCreditorRefGenerate($payload, $statut)
{
	if ($statut < 1) {
		return '';
	}

	$raw = dol_strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $payload));

	if ($raw === '' || dol_strlen($raw) > 21) {
		if (dol_strlen($raw) > 21) {
			dol_syslog('dolCreditorRefGenerate: payload "'.$raw.'" exceeds 21 chars', LOG_WARNING);
		}
		return '';
	}

	return 'RF'.dolCreditorRefCheckDigits($raw).$raw;
}

/**
 * Generate an ISO 11649 reference from an ordered list of parts.
 *
 * Parts are cleaned and concatenated in order. The combined payload is
 * truncated to 21 characters if needed.
 *
 * @param	string[]	$parts	Ordered parts to combine
 * @param	int<0,3>	$statut	Object status
 * @return	string				Compact RF reference, or '' on error
 */
function dolCreditorRefGenerateFromParts($parts, $statut)
{
	if ($statut < 1) {
		return '';
	}

	$combined = '';
	foreach ($parts as $part) {
		$combined .= preg_replace('/[^A-Za-z0-9]/', '', (string) $part);
	}
	$combined = dol_strtoupper($combined);

	if (dol_strlen($combined) > 21) {
		dol_syslog('dolCreditorRefGenerateFromParts: payload truncated to 21 chars', LOG_WARNING);
		$combined = dol_substr($combined, 0, 21);
	}

	return dolCreditorRefGenerate($combined, $statut);
}

/**
 * Check that a string is a valid ISO 11649 Structured Creditor Reference.
 *
 * @param	string	$ref	Reference to validate, with or without spaces
 * @return	bool			True when the format and check digits are correct
 */
function dolCreditorRefIsValid($ref)
{
	$ref = dolPayRefStrip($ref);

	if (dol_substr($ref, 0, 2) !== 'RF' || dol_strlen($ref) < 5 || dol_strlen($ref) > 25) {
		return false;
	}
	if (!ctype_alnum(dol_substr($ref, 4))) {
		return false;
	}

	return dol_substr($ref, 2, 2) === dolCreditorRefCheckDigits(dol_substr($ref, 4));
}

/**
 * Detect which structured payment reference scheme a stored value belongs to.
 *
 * Used by the SEPA XML builder and the QR code builders so no extra database
 * column is needed to remember how a reference was produced.
 *
 * @param	string	$ref	Stored payment reference
 * @return	string			'SCOR' for ISO 11649, 'BBA' for Belgian OGM-VCS,
 *							'FI' for a Finnish national reference, '' if unknown
 */
function dolPayRefDetectScheme($ref)
{
	$ref = trim((string) $ref);

	if ($ref === '') {
		return '';
	}
	if (strpos($ref, '+++') === 0) {
		return 'BBA';
	}
	if (dol_substr(dolPayRefStrip($ref), 0, 2) === 'RF') {
		return 'SCOR';
	}
	if (ctype_digit(dolPayRefStrip($ref))) {
		return 'FI';
	}

	return '';
}
