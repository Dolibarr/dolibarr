<?PHP
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

/*!	\file price.lib.php
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
  $tva = array();
  $total_tva = 0;
  $total_remise = 0;

  $num = sizeof($products);
  $i = 0;

  while ($i < $num)	  
    {
      $prod_price = $products[$i][0];
      $prod_qty   = $products[$i][1];
      $prod_txtva = $products[$i][2];

      $lprice = $prod_qty * $prod_price;

      $amount_ht = $amount_ht + $lprice;

      if ($remise_percent > 0)
	{
	  $lremise = ($lprice * $remise_percent / 100);
	  $lprice = $lprice - $lremise;
	  $total_remise = $total_remise + $lremise;
	}
      
      $total_ht = $total_ht + $lprice;

      $ligne_tva = ($lprice * ($prod_txtva / 100));

      $tva[$prod_txtva] = $tva[$prod_txtva] + $ligne_tva;
      $i++;
    }

  /*
   * Sommes et arrondis
   */
  $j=0;
  $result[5] = array();

  foreach ($tva as $key => $value)
    {
      $tva[$key] = round($tva[$key], 2);
      $total_tva = $total_tva + $tva[$key];
      $result[5][$key] = $tva[$key];
      $j++;
    }
  
  $total_ht  = round($total_ht, 2);
  $total_tva = round($total_tva, 2);
  
  $total_ttc = $total_ht + $total_tva;
  
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
