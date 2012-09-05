<?php
/* Copyright (C) 2002-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2010      Juanjo Menent        <jmenent@2byte.es>
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
 */

/**
 *		\file 		htdocs/core/lib/price.lib.php
 *		\brief 		Librairie contenant les fonctions pour calculer un prix.
 */


/**
 *		Calculate totals (net, vat, ...) of a line.
 *
 *		@param	int		$qty						Quantity
 * 		@param 	float	$pu							Unit price (HT or TTC selon price_base_type)
 *		@param 	float	$remise_percent_ligne		Discount for line
 *		@param 	float	$txtva						Vat rate
 *		@param  float	$txlocaltax1				Localtax1 rate (used for some countries only, like spain)
 *		@param  float	$txlocaltax2				Localtax2 rate (used for some countries only, like spain)
 *		@param 	float	$remise_percent_global		0
 *		@param	string	$price_base_type 			HT=on calcule sur le HT, TTC=on calcule sur le TTC
 *		@param	int		$info_bits					Miscellanous informations on line
 *		@return result[0,1,2,3,4,5,6,7,8]	(total_ht, total_vat, total_ttc, pu_ht, pu_tva, pu_ttc, total_ht_without_discount, total_vat_without_discount, total_ttc_without_discount)
 */
function calcul_price_total($qty, $pu, $remise_percent_ligne, $txtva, $txlocaltax1=0, $txlocaltax2=0, $remise_percent_global=0, $price_base_type='HT', $info_bits=0)
{
	global $conf,$mysoc;

	$result=array();

	// We work to define prices using the price without tax
	$tot_sans_remise = $pu * $qty;
	$tot_avec_remise_ligne = $tot_sans_remise       * (1 - ($remise_percent_ligne / 100));
	$tot_avec_remise       = $tot_avec_remise_ligne * (1 - ($remise_percent_global / 100));

	//dol_syslog("price.lib::calcul_price_total $qty, $pu, $remise_percent_ligne, $txtva, $price_base_type $info_bits");
	if ($price_base_type == 'HT')
	{
		$result[6] = price2num($tot_sans_remise, 'MT');
		$result[8] = price2num($tot_sans_remise * (1 + ( (($info_bits & 1)?0:$txtva) / 100)), 'MT');	// Selon TVA NPR ou non
		$result8bis= price2num($tot_sans_remise * (1 + ( $txtva / 100)), 'MT');	// Si TVA consideree normale (non NPR)
		$result[7] = price2num($result8bis - $result[6], 'MT');

		$result[0] = price2num($tot_avec_remise, 'MT');
		$result[2] = price2num($tot_avec_remise * (1 + ( (($info_bits & 1)?0:$txtva) / 100)), 'MT');	// Selon TVA NPR ou non
		$result2bis= price2num($tot_avec_remise * (1 + ( $txtva / 100)), 'MT');	// Si TVA consideree normale (non NPR)

		$result[1] = price2num($result2bis - $result[0], 'MT');	// Total VAT = TTC - HT

		$result[3] = price2num($pu, 'MU');
		$result[5] = price2num($pu * (1 + ((($info_bits & 1)?0:$txtva) / 100)), 'MU');	// Selon TVA NPR ou non
		$result5bis= price2num($pu * (1 + ($txtva / 100)), 'MU');	// Si TVA consideree normale (non NPR)
		$result[4] = price2num($result5bis - $result[3], 'MU');
	}
	else
	{
		$result[8] = price2num($tot_sans_remise, 'MT');
		$result[6] = price2num($tot_sans_remise / (1 + ((($info_bits & 1)?0:$txtva) / 100)), 'MT');	// Selon TVA NPR ou non
		$result6bis= price2num($tot_sans_remise / (1 + ($txtva / 100)), 'MT');	// Si TVA consideree normale (non NPR)
		$result[7] = price2num($result[8] - $result6bis, 'MT');

		$result[2] = price2num($tot_avec_remise, 'MT');
		$result[0] = price2num($tot_avec_remise / (1 + ((($info_bits & 1)?0:$txtva) / 100)), 'MT');	// Selon TVA NPR ou non
		$result0bis= price2num($tot_avec_remise / (1 + ($txtva / 100)), 'MT');	// Si TVA consideree normale (non NPR)

		$result[1] = price2num($result[2] - $result0bis, 'MT');	// Total VAT = TTC - HT

		$result[5] = price2num($pu, 'MU');
		$result[3] = price2num($pu / (1 + ((($info_bits & 1)?0:$txtva) / 100)), 'MU');	// Selon TVA NPR ou non
		$result3bis= price2num($pu / (1 + ($txtva / 100)), 'MU');	// Si TVA consideree normale (non NPR)
		$result[4] = price2num($result[5] - $result3bis, 'MU');
	}

	// Local taxes 1. Local tax1 is a second tax that is added after standard one)
	if ($txlocaltax1 > 0)
	{
		$result[14] = price2num(($result[6] * ( 1 + ( $txlocaltax1 / 100))) - $result[6], 'MT');	// amount tax1 for total_ht_without_discount
		$result[9] = price2num(($result[0] * ( 1 + ( $txlocaltax1 / 100))) - $result[0], 'MT');		// amount tax1 for total_ht
		$result[11] = price2num(($result[3] * ( 1 + ( $txlocaltax1 / 100))) - $pu, 'MU');			// amount tax1 for pu_ht

		$result[8]  = price2num($result[8] + $result[14], 'MT');									// total_ttc_without_discount + tax1
		$result[2]  = price2num($result[2] + $result[9], 'MT');										// total_ttc + tax1
		$result[5]  = price2num($result[5] + $result[11], 'MU');									// pu_ht + tax1
	}
	else
	{
		$result[14] = 0;
		$result[9]  = 0;
		$result[11] = 0;
	}

	// Local taxes 2. Local tax2 is a second tax that is substracted after standard one)
	// Example: Country = Spain, localtax2 is IRPF
	if ($txlocaltax2 > 0)
	{
		$result[15] = price2num(($result[6] * ( 1 + ( $txlocaltax2 / 100))) - $result[6], 'MT');	// amount tax2 for total_ht_without_discount
		$result[10] = price2num(($result[0] * ( 1 + ( $txlocaltax2 / 100))) - $result[0], 'MT');	// amount tax2 for total_ht
		$result[12] = price2num(($result[3] * ( 1 + ( $txlocaltax2 / 100))) - $pu, 'MU');			// amount tax2 for pu_ht

		$result[8] = price2num($result[8] - $result[15], 'MT');										// total_ttc_without_discount + tax2
		$result[2] = price2num($result[2] - $result[10], 'MT');										// total_ttc + tax2
		$result[5] = price2num($result[5] - $result[12], 'MU');										// pu_ttc + tax2
	}
	else
	{
		$result[15] = 0;
		$result[10] = 0;
		$result[12] = 0;
	}

	// If rounding is not using base 10 (rare)
	if (! empty($conf->global->MAIN_ROUNDING_RULE_TOT))
	{
		if ($price_base_type == 'HT')
		{
			$result[0]=round($result[0]/$conf->global->MAIN_ROUNDING_RULE_TOT, 0)*$conf->global->MAIN_ROUNDING_RULE_TOT;
			$result[1]=round($result[1]/$conf->global->MAIN_ROUNDING_RULE_TOT, 0)*$conf->global->MAIN_ROUNDING_RULE_TOT;
			$result[2]=price2num($result[0]+$result[1], 'MT');
			$result[9]=round($result[9]/$conf->global->MAIN_ROUNDING_RULE_TOT, 0)*$conf->global->MAIN_ROUNDING_RULE_TOT;
			$result[10]=round($result[10]/$conf->global->MAIN_ROUNDING_RULE_TOT, 0)*$conf->global->MAIN_ROUNDING_RULE_TOT;
		}
		else
		{
			$result[1]=round($result[1]/$conf->global->MAIN_ROUNDING_RULE_TOT, 0)*$conf->global->MAIN_ROUNDING_RULE_TOT;
			$result[2]=round($result[2]/$conf->global->MAIN_ROUNDING_RULE_TOT, 0)*$conf->global->MAIN_ROUNDING_RULE_TOT;
			$result[0]=price2num($result[2]-$result[0], 'MT');
			$result[9]=round($result[9]/$conf->global->MAIN_ROUNDING_RULE_TOT, 0)*$conf->global->MAIN_ROUNDING_RULE_TOT;
			$result[10]=round($result[10]/$conf->global->MAIN_ROUNDING_RULE_TOT, 0)*$conf->global->MAIN_ROUNDING_RULE_TOT;
		}
	}

	//print "Price.lib::calcul_price_total ".$result[0]."-".$result[1]."-".$result[2];

	return $result;
}

