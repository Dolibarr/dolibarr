<?php
/* Copyright (C) 2012      Christophe Battarel  <christophe.battarel@altairis.fr>
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
 *	\file       /htdocs/fourn/ajax/getSupplierPrices.php
 *	\brief      File to return an Ajax response to get a supplier prices
 */

if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL','1'); // Disables token renewal
if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU','1');
//if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML','1');
if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX','1');
if (! defined('NOREQUIRESOC'))   define('NOREQUIRESOC','1');
//if (! defined('NOREQUIRETRAN'))  define('NOREQUIRETRAN','1');

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';

$idprod=GETPOST('idprod','int');

$prices = array();

$langs->load('stocks');

/*
 * View
*/

top_httphead();

//print '<!-- Ajax page called with url '.$_SERVER["PHP_SELF"].'?'.$_SERVER["QUERY_STRING"].' -->'."\n";

if ($idprod > 0)
{
	$producttmp=new ProductFournisseur($db);
	$producttmp->fetch($idprod);

	$sql = "SELECT p.rowid, p.label, p.ref, p.price, p.duration,";
	$sql.= " pfp.ref_fourn,";
	$sql.= " pfp.rowid as idprodfournprice, pfp.price as fprice, pfp.remise_percent, pfp.quantity, pfp.unitprice, pfp.charges, pfp.unitcharges,";
	$sql.= " pfp.fk_supplier_price_expression, pfp.tva_tx, s.nom as name";
	$sql.= " FROM ".MAIN_DB_PREFIX."product_fournisseur_price as pfp";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product as p ON p.rowid = pfp.fk_product";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = pfp.fk_soc";
	$sql.= " WHERE pfp.fk_product = ".$idprod;
	$sql.= " AND p.tobuy = 1";
	$sql.= " AND s.fournisseur = 1";
	$sql.= " ORDER BY s.nom, pfp.ref_fourn DESC";

	dol_syslog("Ajax::getSupplierPrices", LOG_DEBUG);
	$result=$db->query($sql);

	if ($result)
	{
		$num = $db->num_rows($result);

		if ($num)
		{
            require_once DOL_DOCUMENT_ROOT.'/product/dynamic_price/class/price_parser.class.php';
			$i = 0;
			while ($i < $num)
			{
				$objp = $db->fetch_object($result);

                if (!empty($objp->fk_supplier_price_expression)) {
                    $priceparser = new PriceParser($db);
                    $price_result = $priceparser->parseProductSupplier($idprod, $objp->fk_supplier_price_expression, $objp->quantity, $objp->tva_tx);
                    if ($price_result >= 0) {
                        $objp->fprice = $price_result;
                        if ($objp->quantity >= 1)
                        {
                            $objp->unitprice = $objp->fprice / $objp->quantity;
                        }
                    }
                }

				$price = $objp->fprice * (1 - $objp->remise_percent / 100);
				$unitprice = $objp->unitprice * (1 - $objp->remise_percent / 100);

				$title = $objp->name.' - '.$objp->ref_fourn.' - ';

				if ($objp->quantity == 1)
				{
					$title.= price($price,0,$langs,0,0,-1,$conf->currency)."/";
				}
				$title.= $objp->quantity.' '.($objp->quantity == 1 ? $langs->trans("Unit") : $langs->trans("Units"));

				if ($objp->quantity > 1)
				{
					$title.=" - ";
					$title.= price($unitprice,0,$langs,0,0,-1,$conf->currency)."/".$langs->trans("Unit");

					$price = $unitprice;
				}
				if ($objp->unitcharges > 0 && ($conf->global->MARGIN_TYPE == "2"))
				{
					$title.=" + ";
					$title.= price($objp->unitcharges,0,$langs,0,0,-1,$conf->currency);
					$price += $objp->unitcharges;
				}
				if ($objp->duration) $label .= " - ".$objp->duration;

				$label = price($price,0,$langs,0,0,-1,$conf->currency)."/".$langs->trans("Unit");
				if ($objp->ref_fourn) $label.=' ('.$objp->ref_fourn.')';

				$prices[] = array("id" => $objp->idprodfournprice, "price" => price($price,0,'',0), "label" => $label, "title" => $title);
				$i++;
			}

			$db->free($result);
		}
	}

	// Add price for pmp
	$price=$producttmp->pmp;
	$prices[] = array("id" => 'pmpprice', "price" => $price, "label" => $langs->trans("PMPValueShort").': '.price($price,0,$langs,0,0,-1,$conf->currency), "title" => $langs->trans("PMPValueShort").': '.price($price,0,$langs,0,0,-1,$conf->currency));
}

echo json_encode($prices);

