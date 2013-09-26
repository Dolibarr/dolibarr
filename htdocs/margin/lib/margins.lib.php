<?php
/* Copyright (C) 2012	Christophe Battarel	<christophe.battarel@altairis.fr>
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
	complete_head_from_modules($conf,$langs,'',$head,$h,'margesadmin');

	complete_head_from_modules($conf,$langs,'',$head,$h,'margesadmin','remove');

	return $head;
}

/**
 * Return array of tabs to used on pages for third parties cards.
 *
 * @return 	array				Array of tabs
 */
function marges_prepare_head()
{
	global $langs, $conf;
	$langs->load("marges@marges");

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT."/margin/productMargins.php";
	$head[$h][1] = $langs->trans("ProductMargins");
	$head[$h][2] = 'productMargins';
	$h++;

	$head[$h][0] = DOL_URL_ROOT."/margin/customerMargins.php";
	$head[$h][1] = $langs->trans("CustomerMargins");
	$head[$h][2] = 'customerMargins';
	$h++;

	$head[$h][0] = DOL_URL_ROOT."/margin/agentMargins.php";
	$head[$h][1] = $langs->trans("AgentMargins");
	$head[$h][2] = 'agentMargins';
	$h++;

	return $head;
}

/**
 * getMarginInfos
 *
 * @param 	float 	$pvht				Buying price with tax
 * @param 	float	$remise_percent		Discount percent
 * @param 	float	$tva_tx				Vat rate
 * @param 	float	$localtax1_tx		Vat rate special 1
 * @param 	float	$localtax2_tx		Vat rate special 2
 * @param 	int		$fk_pa				???
 * @param 	float	$paht				Buying price without tax
 * @return	array						Array of margin info
 */
function getMarginInfos($pvht, $remise_percent, $tva_tx, $localtax1_tx, $localtax2_tx, $fk_pa, $paht)
{
	global $db, $conf;

	$marge_tx_ret='';
	$marque_tx_ret='';

	if($fk_pa > 0) {
		require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';
		$product = new ProductFournisseur($db);
		if ($product->fetch_product_fournisseur_price($fk_pa)) {
			$paht_ret = $product->fourn_unitprice * (1 - $product->fourn_remise_percent / 100);
			if ($conf->global->MARGIN_TYPE == "2" && $product->fourn_unitcharges > 0)
				$paht_ret += $product->fourn_unitcharges;
		}
		else
			$paht_ret = $paht;
	}
	else
		$paht_ret	= $paht;

	require_once DOL_DOCUMENT_ROOT.'/core/lib/price.lib.php';
	// calcul pu_ht remisés
	$tabprice=calcul_price_total(1, $pvht, $remise_percent, $tva_tx, $localtax1_tx, $localtax2_tx, 0, 'HT', 0, 0);	// FIXME Parameter type is missing, i put 0 to avoid blocking error
	$pu_ht_remise = $tabprice[0];
	// calcul marge
	if ($pu_ht_remise < 0)
		$marge = -1 * (abs($pu_ht_remise) - $paht_ret);
	else
		$marge = $pu_ht_remise - $paht_ret;

	// calcul taux marge
	if ($paht_ret != 0)
		$marge_tx_ret = round((100 * $marge) / $paht_ret, 3);
	// calcul taux marque
	if ($pu_ht_remise != 0)
		$marque_tx_ret = round((100 * $marge) / $pu_ht_remise, 3);

	return array($paht_ret, $marge_tx_ret, $marque_tx_ret);
}
?>