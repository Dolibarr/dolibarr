<?php
/* Copyright (C) 2012      Christophe Battarel  <christophe.battarel@altairis.fr>
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
 */

/**
 *	\file			/marges/lib/marges.lib.php
 *  \ingroup		marges
 *  \brief			Library for common marges functions
 *  \version		$Id:$
 */

/**
 *  Define head array for tabs of marges tools setup pages
 *  @return			Array of head
 */
function marges_admin_prepare_head()
{
	global $langs, $conf;
	
	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/marges/admin/marges.php",1);
	$head[$h][1] = $langs->trans("Parameters");
	$head[$h][2] = 'parameters';
	$h++;
    
    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    // $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
    // $this->tabs = array('entity:-tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to remove a tab
    complete_head_from_modules($conf,$langs,$object,$head,$h,'margesadmin');

    return $head;
}

function marges_prepare_head($user)
{
	global $langs, $conf;
	$langs->load("marges@marges");

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT_ALT."/marges/productMargins.php";
	$head[$h][1] = $langs->trans("ProductMargins");
	$head[$h][2] = 'productMargins';
	$h++;

	$head[$h][0] = DOL_URL_ROOT_ALT."/marges/customerMargins.php";
	$head[$h][1] = $langs->trans("CustomerMargins");
	$head[$h][2] = 'customerMargins';
	$h++;

	$head[$h][0] = DOL_URL_ROOT_ALT."/marges/agentMargins.php";
	$head[$h][1] = $langs->trans("AgentMargins");
	$head[$h][2] = 'agentMargins';
	$h++;

	return $head;
}

function getMarginInfos($pvht, $remise_percent, $tva_tx, $localtax1_tx, $localtax2_tx, $fk_pa, $paht) {
  global $db, $conf;
  
  if($fk_pa > 0) {
	  require_once DOL_DOCUMENT_ROOT."/fourn/class/fournisseur.product.class.php";
    $product = new ProductFournisseur($db);
	  if ($product->fetch_product_fournisseur_price($fk_pa)) {
	    $paht_ret = $product->fourn_unitprice;
      if ($conf->global->MARGIN_TYPE == "2" && $product->fourn_unitcharges > 0)
      	$paht_ret += $product->fourn_unitcharges;
	  }
	  else
	    $paht_ret = $paht;
	}
	else
	  $paht_ret	= $paht;

	require_once(DOL_DOCUMENT_ROOT.'/lib/price.lib.php');
  // calcul pu_ht remisés
  $tabprice=calcul_price_total(1, $pvht, $remise_percent, $tva_tx, $localtax1_tx, $localtax2_tx, 0, 'HT', $objp->info_bits);
  $pu_ht_remise = $tabprice[0];
  // calcul taux marge
	if ($paht_ret != 0)
    $marge_tx_ret = round((100 * ($pu_ht_remise - $paht_ret)) / $paht_ret, 3);
  // calcul taux marque
  if ($pu_ht_remise != 0)
    $marque_tx_ret = round((100 * ($pu_ht_remise - $paht_ret)) / $pu_ht_remise, 3);
  
  return array($paht_ret, $marge_tx_ret, $marque_tx_ret);
}
?>