<?php
/* Copyright (C) 2006 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * or see http://www.gnu.org/
 *
 * $Id$
 * $Source$
 */

/**
	    \file       htdocs/lib/product.lib.php
		\brief      Ensemble de fonctions de base pour le module produit et service
		\version    $Revision$

		Ensemble de fonctions de base de dolibarr sous forme d'include
*/

function product_prepare_head($product)
{
	global $langs, $conf;
	$h = 0;
	$head = array();
	
	$head[$h][0] = DOL_URL_ROOT."/product/fiche.php?id=".$product->id;
	$head[$h][1] = $langs->trans("Card");
	$head[$h][2] = 'card';
	$h++;
	
	$head[$h][0] = DOL_URL_ROOT."/product/price.php?id=".$product->id;
	$head[$h][1] = $langs->trans("Price");
	$head[$h][2] = 'price';
	$h++;
	
	//affichage onglet catégorie
	if ($conf->categorie->enabled)
	{
		$head[$h][0] = DOL_URL_ROOT."/product/categorie.php?id=".$product->id;
		$head[$h][1] = $langs->trans('Categories');
		$head[$h][2] = 'category';
		$h++;
	}
	
	if($product->type == 0)
	{
		if ($user->rights->barcode->lire)
		{
			if ($conf->barcode->enabled)
			{
				$head[$h][0] = DOL_URL_ROOT."/product/barcode.php?id=".$product->id;
				$head[$h][1] = $langs->trans("BarCode");
				$head[$h][2] = 'barcode';
				$h++;
			}
		}
	}
	
	// Multilangs
	// TODO Ecran a virer et à remplacer par 
	if($conf->global->MAIN_MULTILANGS)
	{
		$head[$h][0] = DOL_URL_ROOT."/product/traduction.php?id=".$product->id;
		$head[$h][1] = $langs->trans("Translation");
		$head[$h][2] = 'translation';
		$h++;
	}
	
	if ($conf->fournisseur->enabled)
	{
		$head[$h][0] = DOL_URL_ROOT."/product/fournisseurs.php?id=".$product->id;
		$head[$h][1] = $langs->trans("Suppliers");
		$head[$h][2] = 'suppliers';
		$h++;
	}
	
	$head[$h][0] = DOL_URL_ROOT."/product/stats/fiche.php?id=".$product->id;
	$head[$h][1] = $langs->trans('Statistics');
	$head[$h][2] = 'stats';
	$h++;
	
	// sousproduits
	if($conf->global->PRODUIT_SOUSPRODUITS == 1)
	{
		$head[$h][0] = DOL_URL_ROOT."/product/sousproduits/fiche.php?id=".$product->id;
		$head[$h][1] = $langs->trans('AssociatedProducts');
		$head[$h][2] = 'subproduct';
		$h++;
	}
	
	
	$head[$h][0] = DOL_URL_ROOT."/product/stats/facture.php?id=".$product->id;
	$head[$h][1] = $langs->trans('Referers');
	$head[$h][2] = 'referers';
	$h++;
	
	$head[$h][0] = DOL_URL_ROOT."/product/photos.php?id=".$product->id;
	$head[$h][1] = $langs->trans("Photos");
	$head[$h][2] = 'photos';
	$h++;
	
	$head[$h][0] = DOL_URL_ROOT.'/product/document.php?id='.$product->id;
	$head[$h][1] = $langs->trans('Documents');
	$head[$h][2] = 'document';
	$h++;

	if($product->type == 0)	// Si produit stockable
	{
		if ($conf->stock->enabled)
		{
			$head[$h][0] = DOL_URL_ROOT."/product/stock/product.php?id=".$product->id;
			$head[$h][1] = $langs->trans("Stock");
			$head[$h][2] = 'stock';
			$h++;
		}
	}
	
	return $head;
}

?>