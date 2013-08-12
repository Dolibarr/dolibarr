<?php
/* Copyright (C) 2002-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2010-2012 Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2012      Christophe Battarel  <christophe.battarel@altairis.fr>
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
 */

/**
 *		\file 		htdocs/core/lib/price.lib.php
 *		\brief 		Library with functions to calculate prices
 */


/**
 *		Calculate totals (net, vat, ...) of a line.
 *		Value for localtaxX_type are	'0' : local tax not applied
 *										'1' : local tax apply on products and services without vat (vat is not applied for local tax calculation)
 *										'2' : local tax apply on products and services before vat (vat is calculated on amount + localtax)
 *										'3' : local tax apply on products without vat (vat is not applied for local tax calculation)
 *										'4' : local tax apply on products before vat (vat is calculated on amount + localtax)
 *										'5' : local tax apply on services without vat (vat is not applied for local tax calculation)
 *										'6' : local tax apply on services before vat (vat is calculated on amount + localtax)
 *										'7' : local tax is a fix amount applied on global invoice
 *
 *		@param	int		$qty						Quantity
 * 		@param 	float	$pu                         Unit price (HT or TTC selon price_base_type)
 *		@param 	float	$remise_percent_ligne       Discount for line
 *		@param 	float	$txtva                      0=do not apply standard tax, Vat rate=apply
 *		@param  float	$uselocaltax1_rate          0=do not use this localtax, >0=apply, -1=autodetect according to seller
 *		@param  float	$uselocaltax2_rate          0=do not use this localtax, >0=apply, -1=autodetect according to seller
 *		@param 	float	$remise_percent_global		0
 *		@param	string	$price_base_type 			HT=on calcule sur le HT, TTC=on calcule sur le TTC
 *		@param	int		$info_bits					Miscellaneous informations on line
 *		@param	int		$type						0/1=Product/service
 *		@param  string	$seller						Thirdparty seller (we need $seller->country_code property). Provided only if seller is the supplier.
 *		@return result[ 0=total_ht,
 *						 1=total_vat,
 *						 2=total_ttc,
 *						 3=pu_ht,
 *						 4=pu_tva,
 *						 5=pu_ttc,
 *						 6=total_ht_without_discount,
 *						 7=total_vat_without_discount,
 *						 8=total_ttc_without_discount,
 *						 9=amount tax1 for total_ht,
 *						10=amount tax2 for total_ht,
 *						11=amount tax1 for pu_ht,
 *						12=amount tax2 for pu_ht,
 *						13=not used???,
 *						14=amount tax1 for total_ht_without_discount,
 *						15=amount tax1 for total_ht_without_discount]
 */
function calcul_price_total($qty, $pu, $remise_percent_ligne, $txtva, $uselocaltax1_rate, $uselocaltax2_rate, $remise_percent_global, $price_base_type, $info_bits, $type, $seller = '')
{
	global $conf,$mysoc,$db;

	$result=array();

	// Clean parameters
	if (empty($txtva)) $txtva=0;
	
	if (empty($seller) || ! is_object($seller))
	{
		if (! is_object($mysoc))	// mysoc may be not defined (during migration process)
		{
			$mysoc=new Societe($db);
			$mysoc->setMysoc($conf);
		}
		$seller=$mysoc;	// If sell is done to a customer, $seller is not provided, we use $mysoc
		//var_dump($seller->country_id);exit;
	}
	// Too verbose. Enable for debug only
	//dol_syslog("calcul_price_total qty=".$qty." pu=".$pu." remiserpercent_ligne=".$remise_percent_ligne." txtva=".$txtva." uselocaltax1_rate=".$uselocaltax1_rate." uselocaltax2_rate=".$uselocaltax2_rate);
	
	$countryid=$seller->country_id;
	if ($uselocaltax1_rate < 0) $uselocaltax1_rate=$seller->localtax1_assuj;
	if ($uselocaltax2_rate < 0) $uselocaltax2_rate=$seller->localtax2_assuj;

	// Now we search localtaxes information ourself (rates and types).
	$localtax1_type=0;
	$localtax2_type=0;
	$sql = "SELECT taux, localtax1, localtax2, localtax1_type, localtax2_type";
	$sql.= " FROM ".MAIN_DB_PREFIX."c_tva as cv";
	$sql.= " WHERE cv.taux = ".$txtva;
	$sql.= " AND cv.fk_pays = ".$countryid;
	dol_syslog("calcul_price_total search vat information sql=".$sql);
	$resql = $db->query($sql);
	if ($resql)
	{
		$obj = $db->fetch_object($resql);
		if ($obj)
		{
			$localtax1_rate=$obj->localtax1;
			$localtax2_rate=$obj->localtax2;
			$localtax1_type=$obj->localtax1_type;
			$localtax2_type=$obj->localtax2_type;
			//var_dump($localtax1_rate.' '.$localtax2_rate.' '.$localtax1_type.' '.$localtax2_type);exit;
		}
	}
	else dol_print_error($db);

	// initialize total (may be HT or TTC depending on price_base_type)
	$tot_sans_remise = $pu * $qty;
	$tot_avec_remise_ligne = $tot_sans_remise       * (1 - ($remise_percent_ligne / 100));
	$tot_avec_remise       = $tot_avec_remise_ligne * (1 - ($remise_percent_global / 100));

	// initialize result
	for ($i=0; $i <= 15; $i++) $result[$i] = 0;

	// if there's some localtax including vat, we calculate localtaxes (we will add later)
	$localtaxes = array(0,0,0);
    $apply_tax = false;
  	switch($localtax1_type) {
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
  		$result[14] = price2num(($tot_sans_remise * (1 + ( $localtax1_rate / 100))) - $tot_sans_remise, 'MT');
  		$localtaxes[0] += $result[14];

  		$result[9] = price2num(($tot_avec_remise * (1 + ( $localtax1_rate / 100))) - $tot_avec_remise, 'MT');
  		$localtaxes[1] += $result[9];

  		$result[11] = price2num(($pu * (1 + ( $localtax1_rate / 100))) - $pu, 'MU');
  		$localtaxes[2] += $result[11];
    }

    $apply_tax = false;
  	switch($localtax2_type) {
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
  		$result[15] = price2num(($tot_sans_remise * (1 + ( $localtax2_rate / 100))) - $tot_sans_remise, 'MT');
  		$localtaxes[0] += $result[15];

  		$result[10] = price2num(($tot_avec_remise * (1 + ( $localtax2_rate / 100))) - $tot_avec_remise, 'MT');
  		$localtaxes[1] += $result[10];

  		$result[12] = price2num(($pu * (1 + ( $localtax2_rate / 100))) - $pu, 'MU');
  		$localtaxes[2] += $result[12];
    }

	//dol_syslog("price.lib::calcul_price_total $qty, $pu, $remise_percent_ligne, $txtva, $price_base_type $info_bits");
	if ($price_base_type == 'HT')
	{
		// We work to define prices using the price without tax
		$result[6] = price2num($tot_sans_remise, 'MT');
		$result[8] = price2num(($tot_sans_remise + $localtaxes[0]) * (1 + ( (($info_bits & 1)?0:$txtva) / 100)), 'MT');	// Selon TVA NPR ou non
		$result8bis= price2num(($tot_sans_remise + $localtaxes[0]) * (1 + ( $txtva / 100)), 'MT');	// Si TVA consideree normale (non NPR)
		$result[7] = price2num($result8bis - ($result[6] + $localtaxes[0]), 'MT');

		$result[0] = price2num($tot_avec_remise, 'MT');
		$result[2] = price2num(($tot_avec_remise + $localtaxes[1])  * (1 + ( (($info_bits & 1)?0:$txtva) / 100)), 'MT');	// Selon TVA NPR ou non
		$result2bis= price2num(($tot_avec_remise + $localtaxes[1])  * (1 + ( $txtva / 100)), 'MT');	// Si TVA consideree normale (non NPR)
		$result[1] = price2num($result2bis - ($result[0] + $localtaxes[1]), 'MT');	// Total VAT = TTC - (HT + localtax)

		$result[3] = price2num($pu, 'MU');
		$result[5] = price2num(($pu + $localtaxes[2]) * (1 + ((($info_bits & 1)?0:$txtva) / 100)), 'MU');	// Selon TVA NPR ou non
		$result5bis= price2num(($pu + $localtaxes[2]) * (1 + ($txtva / 100)), 'MU');	// Si TVA consideree normale (non NPR)
		$result[4] = price2num($result5bis - ($result[3] + $localtaxes[2]), 'MU');
	}
	else
	{
		// We work to define prices using the price with tax
		$result[8] = price2num($tot_sans_remise + $localtaxes[0], 'MT');
		$result[6] = price2num(($tot_sans_remise + $localtaxes[0]) / (1 + ((($info_bits & 1)?0:$txtva) / 100)), 'MT');	// Selon TVA NPR ou non
		$result6bis= price2num(($tot_sans_remise + $localtaxes[0]) / (1 + ($txtva / 100)), 'MT');	// Si TVA consideree normale (non NPR)
		$result[7] = price2num($result[8] - ($result6bis + $localtaxes[0]), 'MT');

		$result[2] = price2num($tot_avec_remise + $localtaxes[1], 'MT');
		$result[0] = price2num(($tot_avec_remise + $localtaxes[1]) / (1 + ((($info_bits & 1)?0:$txtva) / 100)), 'MT');	// Selon TVA NPR ou non
		$result0bis= price2num(($tot_avec_remise + $localtaxes[1]) / (1 + ($txtva / 100)), 'MT');	// Si TVA consideree normale (non NPR)
		$result[1] = price2num($result[2] - ($result0bis + $localtaxes[1]), 'MT');	// Total VAT = TTC - HT

		$result[5] = price2num(($pu + $localtaxes[2]), 'MU');
		$result[3] = price2num(($pu + $localtaxes[2]) / (1 + ((($info_bits & 1)?0:$txtva) / 100)), 'MU');	// Selon TVA NPR ou non
		$result3bis= price2num(($pu + $localtaxes[2]) / (1 + ($txtva / 100)), 'MU');	// Si TVA consideree normale (non NPR)
		$result[4] = price2num($result[5] - ($result3bis + $localtaxes[2]), 'MU');
	}

	// if there's some localtax without vat, we calculate localtaxes (we will add them at end)

    //If price is 'TTC' we need to have the totals without VAT for a correct calculation
    if ($price_base_type=='TTC')
    {
    	$tot_sans_remise= price2num($tot_sans_remise / (1 + ($txtva / 100)),'MU');
    	$tot_avec_remise= price2num($tot_avec_remise / (1 + ($txtva / 100)),'MU');
    	$pu = price2num($pu / (1 + ($txtva / 100)),'MU');
    }

	$apply_tax = false;
    switch($localtax1_type) {
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

  		$result[14] = price2num(($tot_sans_remise * (1 + ( $localtax1_rate / 100))) - $tot_sans_remise, 'MT');	// amount tax1 for total_ht_without_discount
  		$result[8] += $result[14];																				// total_ttc_without_discount + tax1

  		$result[9] = price2num(($tot_avec_remise * (1 + ( $localtax1_rate / 100))) - $tot_avec_remise, 'MT');	// amount tax1 for total_ht
  		$result[2] += $result[9];																				// total_ttc + tax1

  		$result[11] = price2num(($pu * (1 + ( $localtax1_rate / 100))) - $pu, 'MU');							// amount tax1 for pu_ht
  		$result[5] += $result[11];																				// pu_ht + tax1
    }

    $apply_tax = false;
  	switch($localtax2_type) {
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
  		$result[15] = price2num(($tot_sans_remise * (1 + ( $localtax2_rate / 100))) - $tot_sans_remise, 'MT');	// amount tax2 for total_ht_without_discount
  		$result[8] += $result[15];																				// total_ttc_without_discount + tax2

  		$result[10] = price2num(($tot_avec_remise * (1 + ( $localtax2_rate / 100))) - $tot_avec_remise, 'MT');	// amount tax2 for total_ht
  		$result[2] += $result[10];																				// total_ttc + tax2

  		$result[12] = price2num(($pu * (1 + ( $localtax2_rate / 100))) - $pu, 'MU');							// amount tax2 for pu_ht
  		$result[5] += $result[12];																				// pu_ht + tax2
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

