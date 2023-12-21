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
 *  Check the validity of a SIREN.
 *
 *  @param		string		$siren		SIREN to check
 *  @return		boolean					True if valid, False otherwise
 */
function isValidSiren($siren)
{
	$siren = trim($siren);
	$siren = preg_replace('/(\s)/', '', $siren);

	if (!is_numeric($siren) || dol_strlen($siren) != 9) {
		return false;
	}

	// we take each figure one by one and:
	// - if its index is odd then we double its value,
	// - if the latter is higher than 9 then we substract 9 from it,
	// - anf finally we add the result to the overall sum.
	$sum = 0;
	for ($index = 0; $index < 9; $index++) {
		$number = (int) $chaine[$index];
		if (($index % 2) != 0) {
			if (($number *= 2) > 9) {
				$number -= 9;
			}
		}
		$sum += $number;
	}

	// the siren is valid if the sum is a multiple of 10
	return (($sum % 10) == 0) ? true : false;
}
