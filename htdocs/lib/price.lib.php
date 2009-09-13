<?php
/* Copyright (C) 2002-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *		\file 		htdocs/lib/price.lib.php
 *		\brief 		Librairie contenant les fonctions pour calculer un prix.
 *		\version 	$Id$
 */


/**
 *		\brief 	Permet de calculer les parts total HT, TVA et TTC d'une ligne de
 *				facture, propale, commande ou autre depuis:
 *				quantity, unit price, remise_percent_ligne, txtva, remise_percent_global, price_base_type, info_bits
 *		\param 	qty							Quantity
 *		\param 	pu							Prix unitaire (HT ou TTC selon price_base_type)
 *		\param 	remise_percent_ligne		Remise ligne
 *		\param 	txtva						Taux tva
 *		\param 	remise_percent_global		0
 *		\param	price_base_type 			HT=on calcule sur le HT, TTC=on calcule sur le TTC
 *		\param	info_bits					Miscellanous informations on line
 *		\return result[0,1,2,3,4,5,6,7,8]	(total_ht, total_vat, total_ttc, pu_ht, pu_tva, pu_ttc, total_ht_without_discount, total_vat_without_discount, total_ttc_without_discount)
 */
function calcul_price_total($qty, $pu, $remise_percent_ligne, $txtva, $remise_percent_global=0, $price_base_type='HT', $info_bits=0)
{
	global $conf;

	$result=array();

	//dol_syslog("price.lib::calcul_price_total $qty, $pu, $remise_percent_ligne, $txtva, $price_base_type $info_bits");
	if ($price_base_type == 'HT')
	{
		// On travaille par defaut en partant du prix HT
		$tot_sans_remise = $pu * $qty;
		$tot_avec_remise_ligne = $tot_sans_remise       * ( 1 - ($remise_percent_ligne / 100));
		$tot_avec_remise       = $tot_avec_remise_ligne * ( 1 - ($remise_percent_global / 100));

		$result[6] = price2num($tot_sans_remise, 'MT');
		$result[8] = price2num($tot_sans_remise * ( 1 + ( (($info_bits & 1)?0:$txtva) / 100)), 'MT');	// Selon TVA NPR ou non
		$result8bis= price2num($tot_sans_remise * ( 1 + ( $txtva / 100)), 'MT');	// Si TVA consideree normale (non NPR)
		$result[7] = $result8bis - $result[6];

		$result[0] = price2num($tot_avec_remise, 'MT');
		$result[2] = price2num($tot_avec_remise * ( 1 + ( (($info_bits & 1)?0:$txtva) / 100)), 'MT');	// Selon TVA NPR ou non
		$result2bis= price2num($tot_avec_remise * ( 1 + ( $txtva / 100)), 'MT');	// Si TVA consideree normale (non NPR)
		$result[1] = $result2bis - $result[0];

		$result[3] = price2num($pu, 'MU');
		$result[5] = price2num($pu * ( 1 + ((($info_bits & 1)?0:$txtva) / 100)), 'MU');	// Selon TVA NPR ou non
		$result5bis= price2num($pu * ( 1 + ($txtva / 100)), 'MU');	// Si TVA consideree normale (non NPR)
		$result[4] = $result5bis - $result[3];
	}
	else
	{
		// On cacule a l'envers en partant du prix TTC
		// Utilise pour les produits a prix TTC reglemente (livres, ...)
		$tot_sans_remise = $pu * $qty;
		$tot_avec_remise_ligne = $tot_sans_remise       * ( 1 - ($remise_percent_ligne / 100));
		$tot_avec_remise       = $tot_avec_remise_ligne * ( 1 - ($remise_percent_global / 100));

		$result[8] = price2num($tot_sans_remise, 'MT');
		$result[6] = price2num($tot_sans_remise / ( 1 + ((($info_bits & 1)?0:$txtva) / 100)), 'MT');	// Selon TVA NPR ou non
		$result6bis= price2num($tot_sans_remise / ( 1 + ($txtva / 100)), 'MT');	// Si TVA consideree normale (non NPR)
		$result[7] = $result[8] - $result6bis;

		$result[2] = price2num($tot_avec_remise, 'MT');
		$result[0] = price2num($tot_avec_remise / ( 1 + ((($info_bits & 1)?0:$txtva) / 100)), 'MT');	// Selon TVA NPR ou non
		$result0bis= price2num($tot_avec_remise / ( 1 + ($txtva / 100)), 'MT');	// Si TVA consideree normale (non NPR)
		$result[1] = $result[2] - $result0bis;

		$result[5] = price2num($pu, 'MU');
		$result[3] = price2num($pu / ( 1 + ((($info_bits & 1)?0:$txtva) / 100)), 'MU');	// Selon TVA NPR ou non
		$result3bis= price2num($pu / ( 1 + ($txtva / 100)), 'MU');	// Si TVA consideree normale (non NPR)
		$result[4] = $result[5] - $result3bis;
	}

	//print "Price.lib::calcul_price_total ".$result[0]."-".$result[1]."-".$result[2];

	return $result;
}

