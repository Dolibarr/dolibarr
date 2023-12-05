<?php
/* Copyright (C) 2010 Laurent Destailleur         <eldy@users.sourceforge.net>
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
 *	\file			htdocs/core/lib/functions_ch.lib.php
 *	\brief			A set of swiss functions for Dolibarr
 *					This file contains rare functions.
 */


/**
 * Return if a BVRB number is valid or not (For switzerland)
 *
 * @param	string	$bvrb		BVRB number
 * @return 	boolean				True if OK, false if KO
 */
function dol_ch_controle_bvrb($bvrb)
{
	// Init array for control
	$tableau[0][0] = 0;
	$tableau[0][1] = 9;
	$tableau[0][2] = 4;
	$tableau[0][3] = 6;
	$tableau[0][4] = 8;
	$tableau[0][5] = 2;
	$tableau[0][6] = 7;
	$tableau[0][7] = 1;
	$tableau[0][8] = 3;
	$tableau[0][9] = 5;
	$tableau[0][10] = 0;

	$tableau[1][0] = 9;
	$tableau[1][1] = 4;
	$tableau[1][2] = 6;
	$tableau[1][3] = 8;
	$tableau[1][4] = 2;
	$tableau[1][5] = 7;
	$tableau[1][6] = 1;
	$tableau[1][7] = 3;
	$tableau[1][8] = 5;
	$tableau[1][9] = 0;
	$tableau[1][10] = 9;

	$tableau[2][0] = 4;
	$tableau[2][1] = 6;
	$tableau[2][2] = 8;
	$tableau[2][3] = 2;
	$tableau[2][4] = 7;
	$tableau[2][5] = 1;
	$tableau[2][6] = 3;
	$tableau[2][7] = 5;
	$tableau[2][8] = 0;
	$tableau[2][9] = 9;
	$tableau[2][10] = 8;

	$tableau[3][0] = 6;
	$tableau[3][1] = 8;
	$tableau[3][2] = 2;
	$tableau[3][3] = 7;
	$tableau[3][4] = 1;
	$tableau[3][5] = 3;
	$tableau[3][6] = 5;
	$tableau[3][7] = 0;
	$tableau[3][8] = 9;
	$tableau[3][9] = 4;
	$tableau[3][10] = 7;

	$tableau[4][0] = 8;
	$tableau[4][1] = 2;
	$tableau[4][2] = 7;
	$tableau[4][3] = 1;
	$tableau[4][4] = 3;
	$tableau[4][5] = 5;
	$tableau[4][6] = 0;
	$tableau[4][7] = 9;
	$tableau[4][8] = 4;
	$tableau[4][9] = 6;
	$tableau[4][10] = 6;

	$tableau[5][0] = 2;
	$tableau[5][1] = 7;
	$tableau[5][2] = 1;
	$tableau[5][3] = 3;
	$tableau[5][4] = 5;
	$tableau[5][5] = 0;
	$tableau[5][6] = 9;
	$tableau[5][7] = 4;
	$tableau[5][8] = 6;
	$tableau[5][9] = 8;
	$tableau[5][10] = 5;

	$tableau[6][0] = 7;
	$tableau[6][1] = 1;
	$tableau[6][2] = 3;
	$tableau[6][3] = 5;
	$tableau[6][4] = 0;
	$tableau[6][5] = 9;
	$tableau[6][6] = 4;
	$tableau[6][7] = 6;
	$tableau[6][8] = 8;
	$tableau[6][9] = 2;
	$tableau[6][10] = 4;

	$tableau[7][0] = 1;
	$tableau[7][1] = 3;
	$tableau[7][2] = 5;
	$tableau[7][3] = 0;
	$tableau[7][4] = 9;
	$tableau[7][5] = 4;
	$tableau[7][6] = 6;
	$tableau[7][7] = 8;
	$tableau[7][8] = 2;
	$tableau[7][9] = 7;
	$tableau[7][10] = 3;

	$tableau[8][0] = 3;
	$tableau[8][1] = 5;
	$tableau[8][2] = 0;
	$tableau[8][3] = 9;
	$tableau[8][4] = 4;
	$tableau[8][5] = 6;
	$tableau[8][6] = 8;
	$tableau[8][7] = 2;
	$tableau[8][8] = 7;
	$tableau[8][9] = 1;
	$tableau[8][10] = 2;

	$tableau[9][0] = 5;
	$tableau[9][1] = 0;
	$tableau[9][2] = 9;
	$tableau[9][3] = 4;
	$tableau[9][4] = 6;
	$tableau[9][5] = 8;
	$tableau[9][6] = 2;
	$tableau[9][7] = 7;
	$tableau[9][8] = 1;
	$tableau[9][9] = 3;
	$tableau[9][10] = 1;


	// Clean data
	$bv = str_replace(' ', '', $bvrb);

	// Make control
	$report = 0;
	while (dol_strlen($bv) > 1) {
		$match = substr($bv, 0, 1);
		$report = $tableau[$report][$match];
		$bv = substr($bv, 1);
	}
	$controle = $tableau[$report][10];

	return ($controle == $bv);
}
