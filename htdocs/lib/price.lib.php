<?php
/* Copyright (C) 2002-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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
		\file 		htdocs/lib/price.lib.php
		\brief 		Librairie contenant les fonctions pour calculer un prix.
		\author 	Rodolphe Quiedeville.
		\version 	$Id$
*/


/**
		\brief 	Permet de calculer les parts total HT, TVA et TTC d'une ligne de
				facture, propale, commande ou autre depuis:
				quantité, prix unitaire, remise_percent_ligne, txtva, remise_percent_global
		\param 	qty							Quantité
		\param 	pu							Prix unitaire (HT ou TTC selon price_base_type)
		\param 	remise_percent_ligne		Remise ligne
		\param 	txtva						Taux tva
		\param 	remise_percent_global		0
		\param	price_base_type 			HT=on calcule sur le HT, TTC=on calcule sur le TTC
		\return result[0,1,2,3,4,5]			(total_ht, total_tva, total_ttc, pu_ht, pu_tva, pu_ttc)
*/
function calcul_price_total($qty, $pu, $remise_percent_ligne, $txtva, $remise_percent_global=0, $price_base_type='HT')
{
	global $conf;
	
	$result=array();

	//dolibarr_syslog("price.lib::calcul_price_total $qty, $pu, $remise_percent_ligne, $txtva, $price_base_type");
	if ($price_base_type == 'HT')
	{
		// On travaille par defaut en partant du prix HT
		$tot_sans_remise = $pu * $qty;
		$tot_avec_remise_ligne = $tot_sans_remise       * ( 1 - ($remise_percent_ligne / 100));
		$tot_avec_remise       = $tot_avec_remise_ligne * ( 1 - ($remise_percent_global / 100));
		$result[0] = price2num($tot_avec_remise, 'MT');
		$result[2] = price2num($tot_avec_remise * ( 1 + ($txtva / 100)), 'MT');
		$result[1] = $result[2] - $result[0];
		$result[3] = price2num($pu, 'MU');
		$result[5] = price2num($pu * ( 1 + ($txtva / 100)), 'MU');
		$result[4] = $result[5] - $result[3];
	}
	else
	{
		// On cacule à l'envers en partant du prix TTC
		// Utilise pour les produits a prix TTC reglemente (livres, ...)

		$tot_sans_remise = $pu * $qty;
		$tot_avec_remise_ligne = $tot_sans_remise       * ( 1 - ($remise_percent_ligne / 100));
		$tot_avec_remise       = $tot_avec_remise_ligne * ( 1 - ($remise_percent_global / 100));

		$result[2] = price2num($tot_avec_remise, 'MT');
		$result[0] = price2num($tot_avec_remise / ( 1 + ($txtva / 100)), 'MT');
		$result[1] = $result[2] - $result[0];
		$result[5] = price2num($pu, 'MU');
		$result[3] = price2num($pu / ( 1 + ($txtva / 100)), 'MU');
		$result[4] = $result[5] - $result[3];
	}

	return $result;
}


/**
		\brief 		Permet de calculer un prix.
		\param 		products
		\param 		remise_percent
		\param 		remise_absolue
		\return 	result[0]	total_ht
		result[1]	total_tva
		result[2]	total_ttc
		result[5]	tableau des totaux par tva
		\deprecated
*/
function calcul_price($products, $remise_percent, $remise_absolue=0)
{
  $total_ht = 0;
  $amount_ht = 0;
  $total_tva = 0;
  $total_ttc = 0;
  $total_remise = 0;
  $result[5] = array();
  
  if ( sizeof( $products ) )
    {
      foreach ($products as $product)
	{
	  $prod_price = $product[0];	// Prix unitaire HT apres remise % de ligne
	  $prod_qty   = $product[1];
	  $prod_txtva = $product[2];
	  
	  // montant total HT de la ligne
	  $line_price_ht = $prod_qty * $prod_price;
	  
	  // incrémentation montant HT hors remise de l'ensemble
	  $amount_ht += $line_price_ht;
	  
	  // si une remise relative est consentie sur l'ensemble
	  if ($remise_percent > 0)
	    {
	      // calcul de la remise sur la ligne
	      $line_remise = ($line_price_ht * $remise_percent / 100);
	      // soustraction de cette remise au montant HT de la ligne
	      $line_price_ht -= $line_remise;
	      // incrémentation du montant total de remise sur l'ensemble
	      $total_remise += $line_remise;
	    }
	  // incrémentation du montant HT remisé de l'ensemble
	  $total_ht += $line_price_ht;
	  
	  // calcul de la TVA sur la ligne
	  $line_tva = ($line_price_ht * (abs($prod_txtva) / 100));
	  
	  // incrémentation du montant TTC de la valeur HT, on traite la TVA ensuite
	  $total_ttc  += $line_price_ht; 
	  
	  // traitement de la tva non perçue récupérable
	  if ( $prod_txtva >= 0 )
	    {
	      // ce n'est pas une TVA non perçue récupérable,
	      // donc on incrémente le total TTC de l'ensemble, de la valeur de TVA de la ligne
	      $total_ttc  += $line_tva; 
	    }
	  
	  // dans tous les cas, on incrémente le total de TVA
	  $total_tva += $line_tva;
	  
	  // on incrémente le tableau de différentiation des taux de TVA
	  // s'il faut rassembler les tva facturables ou non, du même taux
	  // dans un même ligne du tableau, remplacer la ligne suivante par :
	  //			$result[5][abs($prod_txtva)] += $line_tva;
	  $result[5][$prod_txtva] += $line_tva;
	  
	  $i++;
	}
    }
  
  /*
   * Si remise absolue, on la retire
   */
  $total_ht -= $remise_absolue;
  
  /*
   * 	Gestion des arrondis sur total des prix
   */
  $total_ht  = round($total_ht, 2);
  $total_tva = round($total_tva, 2);
  $total_ttc = $total_ht + $total_tva;
  
  
  // Renvoi réponse
  $result[0] = $total_ht;
  $result[1] = $total_tva;
  $result[2] = $total_ttc;
  
  $result[3] = $total_remise;	
  $result[4] = $amount_ht;
  
  return $result;
}

