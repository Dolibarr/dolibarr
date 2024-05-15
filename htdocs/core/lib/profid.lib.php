<?php
/* Copyright (C) 2006-2011	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2022		Frédéric France			<frederic.france@netlogic.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 *  \file		htdocs/core/lib/profid.lib.php
 *  \brief		Set of functions for professional identifiers
 */


/**
 *  Check if a string passes the Luhn algorithm test.
 *  @param		string|int		$str		string to check
 *  @return		bool						True if the string passes the Luhn algorithm check, False otherwise
 *  @since		Dolibarr V20
 */
function isValidLuhn($str)
{
	$str = (string) $str;
	$len = dol_strlen($str);
	$parity = $len % 2;
	$sum = 0;
	for ($i = $len - 1; $i >= 0; $i--) {
		$d = (int) $str[$i];
		if ($i % 2 == $parity) {
			if (($d *= 2) > 9) {
				$d -= 9;
			}
		}
		$sum += $d;
	}
	return $sum % 10 == 0;
}


/**
 *  Check the syntax validity of a SIREN.
 *
 *  @param		string		$siren		SIREN to check
 *  @return		boolean					True if valid, False otherwise
 *  @since		Dolibarr V20
 */
function isValidSiren($siren)
{
	$siren = trim($siren);
	$siren = preg_replace('/(\s)/', '', $siren);

	if (!is_numeric($siren) || dol_strlen($siren) != 9) {
		return false;
	}

	return isValidLuhn($siren);
}


/**
 *  Check the syntax validity of a SIRET.
 *
 *  @param		string		$siret		SIRET to check
 *  @return		boolean					True if valid, False otherwise
 *  @since		Dolibarr V20
 */
function isValidSiret($siret)
{
	$siret = trim($siret);
	$siret = preg_replace('/(\s)/', '', $siret);

	if (!is_numeric($siret) || dol_strlen($siret) != 14) {
		return false;
	}

	if (isValidLuhn($siret)) {
		return true;
	} elseif ((substr($siret, 0, 9) == "356000000") && (array_sum(str_split($siret)) % 5 == 0)) {
		/**
		 *  Specific case of "La Poste" businesses (SIRET such as "356 000 000 XXXXX"),
		 *  for which the rule becomes: the sum of the 14 digits must be a multiple of 5.
		 *  See https://fr.wikipedia.org/wiki/SIRET for details.
		 */
		return true;
	} else {
		return false;
	}
}


/**
 *  Check the syntax validity of a Portuguese (PT) Tax Identification Number (TIN).
 *  (NIF = Número de Identificação Fiscal)
 *
 *  @param		string		$str		NIF to check
 *  @return		boolean					True if valid, False otherwise
 *  @since		Dolibarr V20
 */
function isValidTinForPT($str)
{
	$str = trim($str);
	$str = preg_replace('/(\s)/', '', $str);

	if (preg_match('/(^[0-9]{9}$)/', $str)) {
		return true;
	} else {
		return false;
	}
}


/**
 *  Check the syntax validity of an Algerian (DZ) Tax Identification Number (TIN).
 *  (NIF = Numéro d'Identification Fiscale)
 *
 *  @param		string		$str		TIN to check
 *  @return		boolean					True if valid, False otherwise
 *  @since		Dolibarr V20
 */
function isValidTinForDZ($str)
{
	$str = trim($str);
	$str = preg_replace('/(\s)/', '', $str);

	if (preg_match('/(^[0-9]{15}$)/', $str)) {
		return true;
	} else {
		return false;
	}
}


/**
 *  Check the syntax validity of a Belgium (BE) Tax Identification Number (TIN).
 *  (NN = Numéro National)
 *
 *  @param		string		$str		NN to check
 *  @return		boolean					True if valid, False otherwise
 *  @since		Dolibarr V20
 */
function isValidTinForBE($str)
{
	// https://economie.fgov.be/fr/themes/entreprises/banque-carrefour-des/actualites/structure-du-numero
	$str = trim($str);
	$str = preg_replace('/(\s)/', '', $str);

	if (preg_match('/(^[0-1]{1}[0-9]{3}\.[0-9]{3}\.[0-9]{3}$)/', $str)) {
		return true;
	} else {
		return false;
	}
}


/**
 *  Check the syntax validity of a Spanish (ES) Tax Identification Number (TIN), where:
 *  - NIF = Número de Identificación Fiscal (used for residents only before 2008. Used for both residents and companies since 2008.)
 *  - CIF = Código de Identificación Fiscal (used for companies only before 2008. Replaced by NIF since 2008.)
 *  - NIE = Número de Identidad de Extranjero
 *
 *  @param		string		$str		TIN to check
 *  @return		int<-4,3>				1 if NIF ok, 2 if CIF ok, 3 if NIE ok, -1 if NIF bad, -2 if CIF bad, -3 if NIE bad, -4 if unexpected bad
 *  @since		Dolibarr V20
 */
function isValidTinForES($str)
{
	$str = trim($str);
	$str = preg_replace('/(\s)/', '', $str);
	$str = strtoupper($str);

	//Check format
	if (!preg_match('/((^[A-Z]{1}[0-9]{7}[A-Z0-9]{1}$|^[T]{1}[A-Z0-9]{8}$)|^[0-9]{8}[A-Z]{1}$)/', $str)) {
		return 0;
	}

	$num = array();
	for ($i = 0; $i < 9; $i++) {
		$num[$i] = substr($str, $i, 1);
	}

	//Check NIF
	if (preg_match('/(^[0-9]{8}[A-Z]{1}$)/', $str)) {
		if ($num[8] == substr('TRWAGMYFPDXBNJZSQVHLCKE', (int) substr($str, 0, 8) % 23, 1)) {
			return 1;
		} else {
			return -1;
		}
	}

	//algorithm checking type code CIF
	$sum = (int) $num[2] + (int) $num[4] + (int) $num[6];
	for ($i = 1; $i < 8; $i += 2) {
		$sum += intval(substr((string) (2 * (int) $num[$i]), 0, 1)) + intval(substr((string) (2 * (int) $num[$i]), 1, 1));
	}
	$n = 10 - (int) substr((string) $sum, strlen((string) $sum) - 1, 1);

	//Check special NIF
	if (preg_match('/^[KLM]{1}/', $str)) {
		if ($num[8] == chr(64 + $n) || $num[8] == substr('TRWAGMYFPDXBNJZSQVHLCKE', (int) substr($str, 1, 8) % 23, 1)) {
			return 1;
		} else {
			return -1;
		}
	}

	//Check CIF
	if (preg_match('/^[ABCDEFGHJNPQRSUVW]{1}/', $str)) {
		if ($num[8] == chr(64 + $n) || $num[8] == substr((string) $n, strlen((string) $n) - 1, 1)) {
			return 2;
		} else {
			return -2;
		}
	}

	//Check NIE T
	if (preg_match('/^[T]{1}/', $str)) {
		if ($num[8] == preg_match('/^[T]{1}[A-Z0-9]{8}$/', $str)) {
			return 3;
		} else {
			return -3;
		}
	}

	//Check NIE XYZ
	if (preg_match('/^[XYZ]{1}/', $str)) {
		if ($num[8] == substr('TRWAGMYFPDXBNJZSQVHLCKE', (int) substr(str_replace(array('X', 'Y', 'Z'), array('0', '1', '2'), $str), 0, 8) % 23, 1)) {
			return 3;
		} else {
			return -3;
		}
	}

	//Can not be verified
	return -4;
}
