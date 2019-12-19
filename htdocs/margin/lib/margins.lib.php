<?php
/* Copyright (C) 2012      Christophe Battarel <christophe.battarel@altairis.fr>
 * Copyright (C) 2014-2015 Marcos García       <marcosgdf@gmail.com>
 * Copyright (C) 2016	   Florian Henry       <florian.henry@open-concept.pro>
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
 *	\file			/htdocs/margin/lib/margins.lib.php
 *  \ingroup		margin
 *  \brief			Library for common margin functions
 */

/**
 *  Define head array for tabs of marges tools setup pages
 *
 *  @return			Array of head
 */
function marges_admin_prepare_head()
{
	global $langs, $conf;

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT."/margin/admin/margin.php";
	$head[$h][1] = $langs->trans("Parameters");
	$head[$h][2] = 'parameters';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
    // $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
    // $this->tabs = array('entity:-tabname);   												to remove a tab
	complete_head_from_modules($conf, $langs, '', $head, $h, 'margesadmin');

	complete_head_from_modules($conf, $langs, '', $head, $h, 'margesadmin', 'remove');

	return $head;
}

/**
 * Return array of tabs to used on pages for third parties cards.
 *
 * @return 	array				Array of tabs
 */
function marges_prepare_head()
{
	global $langs, $conf, $user;
	$langs->load("margins");

	$h = 0;
	$head = array();

	if ($user->rights->produit->lire) {
		$head[$h][0] = DOL_URL_ROOT."/margin/productMargins.php";
		$head[$h][1] = $langs->trans("ProductMargins");
		$head[$h][2] = 'productMargins';
		$h++;
	}

	if ($user->rights->societe->lire) {
		$head[$h][0] = DOL_URL_ROOT."/margin/customerMargins.php";
		$head[$h][1] = $langs->trans("CustomerMargins");
		$head[$h][2] = 'customerMargins';
		$h++;
	}

	if ($user->rights->margins->read->all) {
		$title = 'UserMargins';
	} else {
		$title = 'SalesRepresentativeMargins';
	}

	$head[$h][0] = DOL_URL_ROOT."/margin/agentMargins.php";
	$head[$h][1] = $langs->trans($title);
	$head[$h][2] = 'agentMargins';


	if ($user->rights->margins->creer) {
		$h++;
		$head[$h][0] = DOL_URL_ROOT."/margin/checkMargins.php";
		$head[$h][1] = $langs->trans('CheckMargins');
		$head[$h][2] = 'checkMargins';
	}

	complete_head_from_modules($conf, $langs, null, $head, $h, 'margins', 'remove');
	complete_head_from_modules($conf, $langs, null, $head, $h, 'margins');

	return $head;
}

/**
 * Return an array with margins information of a line
 *
 * @param 	float 	$pvht				Selling price without tax
 * @param 	float	$remise_percent		Discount percent on line
 * @param 	float	$tva_tx				Vat rate (not used)
 * @param 	float	$localtax1_tx		Vat rate special 1 (not used)
 * @param 	float	$localtax2_tx		Vat rate special 2 (not used)
 * @param 	int		$fk_pa				Id of buying price (prefer set this to 0 and provide $paht instead. With id, buying price may have change)
 * @param 	float	$paht				Buying price without tax
 * @return	array						Array of margin info (buying price, marge rate, marque rate)
 */
function getMarginInfos($pvht, $remise_percent, $tva_tx, $localtax1_tx, $localtax2_tx, $fk_pa, $paht)
{
	global $db, $conf;

	$marge_tx_ret='';
	$marque_tx_ret='';

	if ($fk_pa > 0 && empty($paht)) {
		require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';
		$product = new ProductFournisseur($db);
		if ($product->fetch_product_fournisseur_price($fk_pa))
		{
			$paht_ret = $product->fourn_unitprice * (1 - $product->fourn_remise_percent / 100);
		}
		else
		{
			$paht_ret = $paht;
		}
	}
	else
	{
		$paht_ret = $paht;
	}

	// Calculate selling unit price including line discount
	// We don't use calculate_price, because this function is dedicated to calculation of total with accuracy of total. We need an accuracy of a unit price.
	// Also we must not apply rounding on non decimal rule defined by option MAIN_ROUNDING_RULE_TOT
	$pu_ht_remise = $pvht * (1 - ($remise_percent / 100));
	$pu_ht_remise = price2num($pu_ht_remise, 'MU');

	// calcul marge
	if ($pu_ht_remise < 0)
		$marge = -1 * (abs($pu_ht_remise) - $paht_ret);
	else
		$marge = $pu_ht_remise - $paht_ret;

	// calcul taux marge
	if ($paht_ret != 0)
		$marge_tx_ret = (100 * $marge) / $paht_ret;
	// calcul taux marque
	if ($pu_ht_remise != 0)
		$marque_tx_ret = (100 * $marge) / $pu_ht_remise;

	return array($paht_ret, $marge_tx_ret, $marque_tx_ret);
}
