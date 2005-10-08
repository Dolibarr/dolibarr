<?php
/* Copyright (C) 2002-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 *
 * $Id$
 * $Source$
 *
 */

/*!	\file htdocs/lib/price.lib.php
  \brief librairie contenant les fonctions pour calculer un prix.
  \author Rodolphe Quiedeville.
		\version $Revision$
  
  Ensemble des fonctions permettant de calculer un prix.
*/


/*!
  \brief permet de calculer un prix.
  \param products
  \param remise_percent
  \return result
*/

function calcul_price($products, $remise_percent)
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
			$prod_price = $product[0];
			$prod_qty   = $product[1];
			$prod_txtva = $product[2];
		
			// montant total HT de la ligne
			$line_price_ht = $prod_qty * $prod_price;
		
			// incrémentation montant HT hors remise de l'ensemble
			$amount_ht += $line_price_ht;
		
			// si une remise est consentie sur l'ensemble
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
	* arrondis
	*/
	$total_ht  = round($total_ht, 2);
	$total_tva = round($total_tva, 2);
	
	/*
	*
	*/
	$result[0] = $total_ht;
	$result[1] = $total_tva;
	$result[2] = $total_ttc;
	$result[3] = $total_remise;
	$result[4] = $amount_ht;
	
	return $result;
}

