<?php
/* Copyright (C) 2006-2011	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2022		Frédéric France			<frederic.france@netlogic.fr>
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
 *  Check if a string passes the Luhn agorithm test.
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
	for ($i = $len-1; $i >= 0; $i--) {
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
	} elseif ( (substr($siret, 0, 9) == "356000000") && (array_sum(str_split($siret)) %5 == 0) ) {
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
