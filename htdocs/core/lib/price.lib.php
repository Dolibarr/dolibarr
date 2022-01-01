<?php
/* Copyright (C) 2002-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2010-2013 Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2012      Christophe Battarel  <christophe.battarel@altairis.fr>
 * Copyright (C) 2012      Cédric Salvador      <csalvador@gpcsolutions.fr>
 * Copyright (C) 2012-2014 Raphaël Doursenaud   <rdoursenaud@gpcsolutions.fr>
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
 *		\file 		htdocs/core/lib/price.lib.php
 *		\brief 		Library with functions to calculate prices
 */


/**
 *		Calculate totals (net, vat, ...) of a line.
 *		Value for localtaxX_type are	'0' : local tax not applied
 *										'1' : local tax apply on products and services without vat (localtax is calculated on amount without tax)
 *										'2' : local tax apply on products and services including vat (localtax is calculated on amount + tax)
 *										'3' : local tax apply on products without vat (localtax is calculated on amount without tax)
 *										'4' : local tax apply on products including vat (localtax is calculated on amount + tax)
 *										'5' : local tax apply on services without vat (localtax is calculated on amount without tax)
 *										'6' : local tax apply on services including vat (localtax is calculated on amount + tax)
 *
 *		@param	int		$qty						Quantity
 * 		@param 	float	$pu                         Unit price (HT or TTC selon price_base_type)
 *		@param 	float	$remise_percent_ligne       Discount for line
 *		@param 	float	$txtva                      0=do not apply VAT tax, VAT rate=apply (this is VAT rate only without text code, we don't need text code because we alreaydy have all tax info into $localtaxes_array)
 *		@param  float	$uselocaltax1_rate          0=do not use localtax1, >0=apply and get value from localtaxes_array (or database if empty), -1=autodetect according to seller if we must apply, get value from localtaxes_array (or database if empty). Try to always use -1.
 *		@param  float	$uselocaltax2_rate          0=do not use localtax2, >0=apply and get value from localtaxes_array (or database if empty), -1=autodetect according to seller if we must apply, get value from localtaxes_array (or database if empty). Try to always use -1.
 *		@param 	float	$remise_percent_global		0
 *		@param	string	$price_base_type 			HT=Unit price parameter is HT, TTC=Unit price parameter is TTC
 *		@param	int		$info_bits					Miscellaneous informations on line
 *		@param	int		$type						0/1=Product/service
 *		@param  Societe	$seller						Thirdparty seller (we need $seller->country_id property). Provided only if seller is the supplier, otherwise $seller will be $mysoc.
 *		@param  array	$localtaxes_array			Array with localtaxes info array('0'=>type1,'1'=>rate1,'2'=>type2,'3'=>rate2) (loaded by getLocalTaxesFromRate(vatrate, 0, ...) function).
 *		@param  integer	$progress                   Situation invoices progress (value from 0 to 100, 100 by default)
 *		@param  double	$multicurrency_tx           Currency rate (1 by default)
 * 		@param  double	$pu_devise					Amount in currency
 *      @param  string  $multicurrency_code			Value of the foreign currency if multicurrency is used ('EUR', 'USD', ...). It will be used for rounding according to currency.
 *		@return         array [
 *                       0=total_ht,
 *						 1=total_vat, (main vat only)
 *						 2=total_ttc, (total_ht + main vat + local taxes)
 *						 3=pu_ht,
 *						 4=pu_vat, (main vat only)
 *						 5=pu_ttc,
 *						 6=total_ht_without_discount,
 *						 7=total_vat_without_discount, (main vat only)
 *						 8=total_ttc_without_discount, (total_ht + main vat + local taxes)
 *						 9=total_tax1 for total_ht,
 *						10=total_tax2 for total_ht,
 *
 *						11=pu_tax1 for pu_ht, 							!! should not be used
 *						12=pu_tax2 for pu_ht, 							!! should not be used
 *						13=??                 							!! should not be used
 *						14=total_tax1 for total_ht_without_discount,	!! should not be used
 *						15=total_tax2 for total_ht_without_discount,	!! should not be used
 *
 * 						16=multicurrency_total_ht
 * 						17=multicurrency_total_tva
 * 						18=multicurrency_total_ttc
 * 						19=multicurrency_pu_ht
 * 						20=multicurrency_pu_vat
 * 						21=multicurrency_pu_ttc
 * 						22=multicurrency_total_ht_without_discount
 * 						23=multicurrency_total_vat_without_discount
 * 						24=multicurrency_total_ttc_without_discount
 * 						25=multicurrency_total_tax1 for total_ht
 *                      26=multicurrency_total_tax2 for total_ht
 */
function calcul_price_total($qty, $pu, $remise_percent_ligne, $txtva, $uselocaltax1_rate, $uselocaltax2_rate, $remise_percent_global, $price_base_type, $info_bits, $type, $seller = '', $localtaxes_array = '', $progress = 100, $multicurrency_tx = 1, $pu_devise = 0, $multicurrency_code = '')
{
	global $conf, $mysoc, $db;

	$result = array();

	// Clean parameters
	if (empty($info_bits)) $info_bits = 0;
	if (empty($txtva)) $txtva = 0;
	if (empty($seller) || !is_object($seller))
	{
		dol_syslog("Price.lib::calcul_price_total Warning: function is called with parameter seller that is missing", LOG_WARNING);
		if (!is_object($mysoc))	// mysoc may be not defined (during migration process)
		{
			$mysoc = new Societe($db);
			$mysoc->setMysoc($conf);
		}
		$seller = $mysoc; // If sell is done to a customer, $seller is not provided, we use $mysoc
		//var_dump($seller->country_id);exit;
	}
	if (empty($localtaxes_array) || !is_array($localtaxes_array)) {
		dol_syslog("Price.lib::calcul_price_total Warning: function is called with parameter localtaxes_array that is missing", LOG_WARNING);
	}
	if (!is_numeric($txtva)) {
		dol_syslog("Price.lib::calcul_price_total Warning: function was called with a parameter vat rate that is not a real numeric value. There is surely a bug.", LOG_ERR);
	} elseif ($txtva >= 1000) {
		dol_syslog("Price.lib::calcul_price_total Warning: function was called with a bad value for vat rate (should be often < 100, always < 1000). There is surely a bug.", LOG_ERR);
	}
	// Too verbose. Enable for debug only
	//dol_syslog("Price.lib::calcul_price_total qty=".$qty." pu=".$pu." remiserpercent_ligne=".$remise_percent_ligne." txtva=".$txtva." uselocaltax1_rate=".$uselocaltax1_rate." uselocaltax2_rate=".$uselocaltax2_rate.' remise_percent_global='.$remise_percent_global.' price_base_type='.$ice_base_type.' type='.$type.' progress='.$progress);

	$countryid = $seller->country_id;

	if (is_numeric($uselocaltax1_rate)) $uselocaltax1_rate = (float) $uselocaltax1_rate;
	if (is_numeric($uselocaltax2_rate)) $uselocaltax2_rate = (float) $uselocaltax2_rate;

	if ($uselocaltax1_rate < 0) $uselocaltax1_rate = $seller->localtax1_assuj;
	if ($uselocaltax2_rate < 0) $uselocaltax2_rate = $seller->localtax2_assuj;

	//var_dump($uselocaltax1_rate.' - '.$uselocaltax2_rate);
	dol_syslog('Price.lib::calcul_price_total qty='.$qty.' pu='.$pu.' remise_percent_ligne='.$remise_percent_ligne.' txtva='.$txtva.' uselocaltax1_rate='.$uselocaltax1_rate.' uselocaltax2_rate='.$uselocaltax2_rate.' remise_percent_global='.$remise_percent_global.' price_base_type='.$price_base_type.' type='.$type.' progress='.$progress);

    // Now we search localtaxes information ourself (rates and types).
	$localtax1_type = 0;
	$localtax2_type = 0;

	if (is_array($localtaxes_array))
	{
		$localtax1_type = $localtaxes_array[0];
		$localtax1_rate = $localtaxes_array[1];
		$localtax2_type = $localtaxes_array[2];
		$localtax2_rate = $localtaxes_array[3];
	}
	else	// deprecated method. values and type for localtaxes must be provided by caller and loaded with getLocalTaxesFromRate using the full vat rate (including text code)
	{
	    dol_syslog("Price.lib::calcul_price_total search vat information using old deprecated method", LOG_WARNING);

	    $sql = "SELECT taux, localtax1, localtax2, localtax1_type, localtax2_type";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_tva as cv";
		$sql .= " WHERE cv.taux = ".$txtva;
		$sql .= " AND cv.fk_pays = ".$countryid;
		$resql = $db->query($sql);
		if ($resql)
		{
			$obj = $db->fetch_object($resql);
			if ($obj)
			{
				$localtax1_rate = $obj->localtax1;
				$localtax2_rate = $obj->localtax2;
				$localtax1_type = $obj->localtax1_type;
				$localtax2_type = $obj->localtax2_type;
				//var_dump($localtax1_rate.' '.$localtax2_rate.' '.$localtax1_type.' '.$localtax2_type);exit;
			}
		}
		else dol_print_error($db);
	}

	// pu calculation from pu_devise if pu empty
	if (empty($pu) && !empty($pu_devise)) {
		if (!empty($multicurrency_tx)) $pu = $pu_devise / $multicurrency_tx;
		else
		{
			dol_syslog('Price.lib::calcul_price_total function called with bad parameters combination (multicurrency_tx empty when pu_devise not) ', LOG_ERR);
			return array();
		}
	}
	if ($pu === '') $pu = 0;
	// pu_devise calculation from pu
	if (empty($pu_devise) && !empty($multicurrency_tx)) {
		if (is_numeric($pu) && is_numeric($multicurrency_tx)) $pu_devise = $pu * $multicurrency_tx;
		else
		{
			dol_syslog('Price.lib::calcul_price_total function called with bad parameters combination (pu or multicurrency_tx are not numeric)', LOG_ERR);
			return array();
		}
	}

	// initialize total (may be HT or TTC depending on price_base_type)
	$tot_sans_remise = $pu * $qty * $progress / 100;
	$tot_avec_remise_ligne = $tot_sans_remise * (1 - ($remise_percent_ligne / 100));
	$tot_avec_remise       = $tot_avec_remise_ligne * (1 - ($remise_percent_global / 100));

	// initialize result array
	for ($i = 0; $i <= 15; $i++) $result[$i] = 0;

	// if there's some localtax including vat, we calculate localtaxes (we will add later)

    // if input unit price is 'HT', we need to have the totals with main VAT for a correct calculation
    if ($price_base_type != 'TTC')
    {
    	$tot_sans_remise_wt = price2num($tot_sans_remise * (1 + ($txtva / 100)), 'MU');
    	$tot_avec_remise_wt = price2num($tot_avec_remise * (1 + ($txtva / 100)), 'MU');
    	$pu_wt = price2num($pu * (1 + ($txtva / 100)), 'MU');
    }
    else
    {
    	$tot_sans_remise_wt = $tot_sans_remise;
    	$tot_avec_remise_wt = $tot_avec_remise;
    	$pu_wt = $pu;
    }

	//print 'rr'.$price_base_type.'-'.$txtva.'-'.$tot_sans_remise_wt."-".$pu_wt."-".$uselocaltax1_rate."-".$localtax1_rate."-".$localtax1_type."\n";

    $localtaxes = array(0, 0, 0);
    $apply_tax = false;
    switch ($localtax1_type) {
        case '2':     // localtax on product or service
            $apply_tax = true;
            break;
        case '4':     // localtax on product
            if ($type == 0) $apply_tax = true;
            break;
        case '6':     // localtax on service
            if ($type == 1) $apply_tax = true;
            break;
    }

    if ($uselocaltax1_rate && $apply_tax) {
  		$result[14] = price2num(($tot_sans_remise_wt * (1 + ($localtax1_rate / 100))) - $tot_sans_remise_wt, 'MT');
  		$localtaxes[0] += $result[14];

  		$result[9] = price2num(($tot_avec_remise_wt * (1 + ($localtax1_rate / 100))) - $tot_avec_remise_wt, 'MT');
  		$localtaxes[1] += $result[9];

  		$result[11] = price2num(($pu_wt * (1 + ($localtax1_rate / 100))) - $pu_wt, 'MU');
  		$localtaxes[2] += $result[11];
    }

    $apply_tax = false;
    switch ($localtax2_type) {
        case '2':     // localtax on product or service
            $apply_tax = true;
            break;
        case '4':     // localtax on product
            if ($type == 0) $apply_tax = true;
            break;
        case '6':     // localtax on service
            if ($type == 1) $apply_tax = true;
            break;
    }
    if ($uselocaltax2_rate && $apply_tax) {
  		$result[15] = price2num(($tot_sans_remise_wt * (1 + ($localtax2_rate / 100))) - $tot_sans_remise_wt, 'MT');
  		$localtaxes[0] += $result[15];

  		$result[10] = price2num(($tot_avec_remise_wt * (1 + ($localtax2_rate / 100))) - $tot_avec_remise_wt, 'MT');
  		$localtaxes[1] += $result[10];

  		$result[12] = price2num(($pu_wt * (1 + ($localtax2_rate / 100))) - $pu_wt, 'MU');
  		$localtaxes[2] += $result[12];
    }

	//dol_syslog("price.lib::calcul_price_total $qty, $pu, $remise_percent_ligne, $txtva, $price_base_type $info_bits");
	if ($price_base_type == 'HT')
	{
		// We work to define prices using the price without tax
		$result[6] = price2num($tot_sans_remise, 'MT');
		$result[8] = price2num($tot_sans_remise * (1 + ((($info_bits & 1) ? 0 : $txtva) / 100)) + $localtaxes[0], 'MT'); // Selon TVA NPR ou non
		$result8bis = price2num($tot_sans_remise * (1 + ($txtva / 100)) + $localtaxes[0], 'MT'); // Si TVA consideree normale (non NPR)
		$result[7] = price2num($result8bis - ($result[6] + $localtaxes[0]), 'MT');

		$result[0] = price2num($tot_avec_remise, 'MT');
		$result[2] = price2num($tot_avec_remise * (1 + ((($info_bits & 1) ? 0 : $txtva) / 100)) + $localtaxes[1], 'MT'); // Selon TVA NPR ou non
		$result2bis = price2num($tot_avec_remise * (1 + ($txtva / 100)) + $localtaxes[1], 'MT'); // Si TVA consideree normale (non NPR)
		$result[1] = price2num($result2bis - ($result[0] + $localtaxes[1]), 'MT'); // Total VAT = TTC - (HT + localtax)

		$result[3] = price2num($pu, 'MU');
		$result[5] = price2num($pu * (1 + ((($info_bits & 1) ? 0 : $txtva) / 100)) + $localtaxes[2], 'MU'); // Selon TVA NPR ou non
		$result5bis = price2num($pu * (1 + ($txtva / 100)) + $localtaxes[2], 'MU'); // Si TVA consideree normale (non NPR)
		$result[4] = price2num($result5bis - ($result[3] + $localtaxes[2]), 'MU');
	}
	else
	{
		// We work to define prices using the price with tax
		$result[8] = price2num($tot_sans_remise + $localtaxes[0], 'MT');
		$result[6] = price2num($tot_sans_remise / (1 + ((($info_bits & 1) ? 0 : $txtva) / 100)), 'MT'); // Selon TVA NPR ou non
		$result6bis = price2num($tot_sans_remise / (1 + ($txtva / 100)), 'MT'); // Si TVA consideree normale (non NPR)
		$result[7] = price2num($result[8] - ($result6bis + $localtaxes[0]), 'MT');

		$result[2] = price2num($tot_avec_remise + $localtaxes[1], 'MT');
		$result[0] = price2num($tot_avec_remise / (1 + ((($info_bits & 1) ? 0 : $txtva) / 100)), 'MT'); // Selon TVA NPR ou non
		$result0bis = price2num($tot_avec_remise / (1 + ($txtva / 100)), 'MT'); // Si TVA consideree normale (non NPR)
		$result[1] = price2num($result[2] - ($result0bis + $localtaxes[1]), 'MT'); // Total VAT = TTC - (HT + localtax)

		$result[5] = price2num($pu + $localtaxes[2], 'MU');
		$result[3] = price2num($pu / (1 + ((($info_bits & 1) ? 0 : $txtva) / 100)), 'MU'); // Selon TVA NPR ou non
		$result3bis = price2num($pu / (1 + ($txtva / 100)), 'MU'); // Si TVA consideree normale (non NPR)
		$result[4] = price2num($result[5] - ($result3bis + $localtaxes[2]), 'MU');
	}

	// if there's some localtax without vat, we calculate localtaxes (we will add them at end)

    //If input unit price is 'TTC', we need to have the totals without main VAT for a correct calculation
    if ($price_base_type == 'TTC')
    {
    	$tot_sans_remise = price2num($tot_sans_remise / (1 + ($txtva / 100)), 'MU');
    	$tot_avec_remise = price2num($tot_avec_remise / (1 + ($txtva / 100)), 'MU');
    	$pu = price2num($pu / (1 + ($txtva / 100)), 'MU');
    }

	$apply_tax = false;
    switch ($localtax1_type) {
        case '1':     // localtax on product or service
            $apply_tax = true;
            break;
        case '3':     // localtax on product
            if ($type == 0) $apply_tax = true;
            break;
        case '5':     // localtax on service
            if ($type == 1) $apply_tax = true;
            break;
    }
    if ($uselocaltax1_rate && $apply_tax) {
  		$result[14] = price2num(($tot_sans_remise * (1 + ($localtax1_rate / 100))) - $tot_sans_remise, 'MT'); // amount tax1 for total_ht_without_discount
  		$result[8] += $result[14]; // total_ttc_without_discount + tax1

  		$result[9] = price2num(($tot_avec_remise * (1 + ($localtax1_rate / 100))) - $tot_avec_remise, 'MT'); // amount tax1 for total_ht
  		$result[2] += $result[9]; // total_ttc + tax1

  		$result[11] = price2num(($pu * (1 + ($localtax1_rate / 100))) - $pu, 'MU'); // amount tax1 for pu_ht
  		$result[5] += $result[11]; // pu_ht + tax1
    }

    $apply_tax = false;
    switch ($localtax2_type) {
        case '1':     // localtax on product or service
            $apply_tax = true;
            break;
        case '3':     // localtax on product
            if ($type == 0) $apply_tax = true;
            break;
        case '5':     // localtax on service
            if ($type == 1) $apply_tax = true;
            break;
    }
    if ($uselocaltax2_rate && $apply_tax) {
  		$result[15] = price2num(($tot_sans_remise * (1 + ($localtax2_rate / 100))) - $tot_sans_remise, 'MT'); // amount tax2 for total_ht_without_discount
  		$result[8] += $result[15]; // total_ttc_without_discount + tax2

  		$result[10] = price2num(($tot_avec_remise * (1 + ($localtax2_rate / 100))) - $tot_avec_remise, 'MT'); // amount tax2 for total_ht
  		$result[2] += $result[10]; // total_ttc + tax2

  		$result[12] = price2num(($pu * (1 + ($localtax2_rate / 100))) - $pu, 'MU'); // amount tax2 for pu_ht
  		$result[5] += $result[12]; // pu_ht + tax2
    }

	// If rounding is not using base 10 (rare)
	if (!empty($conf->global->MAIN_ROUNDING_RULE_TOT))
	{
		if ($price_base_type == 'HT')
		{
			$result[0] = round($result[0] / $conf->global->MAIN_ROUNDING_RULE_TOT, 0) * $conf->global->MAIN_ROUNDING_RULE_TOT;
			$result[1] = round($result[1] / $conf->global->MAIN_ROUNDING_RULE_TOT, 0) * $conf->global->MAIN_ROUNDING_RULE_TOT;
			$result[9] = round($result[9] / $conf->global->MAIN_ROUNDING_RULE_TOT, 0) * $conf->global->MAIN_ROUNDING_RULE_TOT;
			$result[10] = round($result[10] / $conf->global->MAIN_ROUNDING_RULE_TOT, 0) * $conf->global->MAIN_ROUNDING_RULE_TOT;
			$result[2] = price2num($result[0] + $result[1] + $result[9] + $result[10], 'MT');
		}
		else
		{
			$result[1] = round($result[1] / $conf->global->MAIN_ROUNDING_RULE_TOT, 0) * $conf->global->MAIN_ROUNDING_RULE_TOT;
			$result[2] = round($result[2] / $conf->global->MAIN_ROUNDING_RULE_TOT, 0) * $conf->global->MAIN_ROUNDING_RULE_TOT;
			$result[9] = round($result[9] / $conf->global->MAIN_ROUNDING_RULE_TOT, 0) * $conf->global->MAIN_ROUNDING_RULE_TOT;
			$result[10] = round($result[10] / $conf->global->MAIN_ROUNDING_RULE_TOT, 0) * $conf->global->MAIN_ROUNDING_RULE_TOT;
			$result[0] = price2num($result[2] - $result[1] - $result[9] - $result[10], 'MT');
		}
	}

	// Multicurrency
	if ($multicurrency_tx != 1)
	{
		if ($multicurrency_code) {
			$savMAIN_MAX_DECIMALS_UNIT = $conf->global->MAIN_MAX_DECIMALS_UNIT;
			$savMAIN_MAX_DECIMALS_TOT = $conf->global->MAIN_MAX_DECIMALS_TOT;
			$savMAIN_ROUNDING_RULE_TOT = $conf->global->MAIN_ROUNDING_RULE_TOT;

			// Set parameter for currency accurency according to the value of $multicurrency_code (this is because a foreign currency may have different rounding rules)
			$keyforforeignMAIN_MAX_DECIMALS_UNIT = 'MAIN_MAX_DECIMALS_UNIT_'.$multicurrency_code;
			$keyforforeignMAIN_MAX_DECIMALS_TOT = 'MAIN_MAX_DECIMALS_TOT_'.$multicurrency_code;
			$keyforforeignMAIN_ROUNDING_RULE_TOT = 'MAIN_ROUNDING_RULE_TOT_'.$multicurrency_code;
			if (!empty($conf->global->$keyforforeignMAIN_ROUNDING_RULE_TOT)) {
				$conf->global->MAIN_MAX_DECIMALS_UNIT = $conf->global->$keyforforeignMAIN_MAX_DECIMALS_UNIT;
				$conf->global->MAIN_MAX_DECIMALS_TOT = $conf->global->$keyforforeignMAIN_MAX_DECIMALS_TOT;
				$conf->global->MAIN_ROUNDING_RULE_TOT = $conf->global->$keyforforeignMAIN_ROUNDING_RULE_TOT;
			}
		}

		// Recal function using the multicurrency price as reference price. We must set param $multicurrency_tx to 1 to avoid infinite loop.
		$newresult = calcul_price_total($qty, $pu_devise, $remise_percent_ligne, $txtva, $uselocaltax1_rate, $uselocaltax2_rate, $remise_percent_global, $price_base_type, $info_bits, $type, $seller, $localtaxes_array, $progress, 1, 0, '');

		if ($multicurrency_code) {
			// Restore setup of currency accurency
			$conf->global->MAIN_MAX_DECIMALS_UNIT = $savMAIN_MAX_DECIMALS_UNIT;
			$conf->global->MAIN_MAX_DECIMALS_TOT = $savMAIN_MAX_DECIMALS_TOT;
			$conf->global->MAIN_ROUNDING_RULE_TOT = $savMAIN_ROUNDING_RULE_TOT;
		}

		$result[16] = $newresult[0];
		$result[17] = $newresult[1];
		$result[18] = $newresult[2];
		$result[19] = $newresult[3];
		$result[20] = $newresult[4];
		$result[21] = $newresult[5];
		$result[22] = $newresult[6];
		$result[23] = $newresult[7];
		$result[24] = $newresult[8];
		$result[25] = $newresult[9];
		$result[26] = $newresult[10];
	}
	else
	{
		$result[16] = $result[0];
		$result[17] = $result[1];
		$result[18] = $result[2];
		$result[19] = $result[3];
		$result[20] = $result[4];
		$result[21] = $result[5];
		$result[22] = $result[6];
		$result[23] = $result[7];
		$result[24] = $result[8];
		$result[25] = $result[9];
		$result[26] = $result[10];
	}

	//var_dump($result);
	// initialize result array
	//for ($i=0; $i <= 18; $i++) $result[$i] = (float) $result[$i];

	dol_syslog('Price.lib::calcul_price_total MAIN_ROUNDING_RULE_TOT='.$conf->global->MAIN_ROUNDING_RULE_TOT.' pu='.$pu.' qty='.$qty.' price_base_type='.$price_base_type.' total_ht='.$result[0].'-total_vat='.$result[1].'-total_ttc='.$result[2]);

	return $result;
}
