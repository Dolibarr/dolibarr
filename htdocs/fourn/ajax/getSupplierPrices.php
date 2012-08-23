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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

/**
 *	\file       /htdocs/fourn/ajax/getSupplierPrices.php
 *	\brief      File to return Ajax response on get supplier prices
 */

if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL','1'); // Disables token renewal
if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU','1');
if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML','1');
if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX','1');
if (! defined('NOREQUIRESOC'))   define('NOREQUIRESOC','1');
//if (! defined('NOREQUIRETRAN'))  define('NOREQUIRETRAN','1');

require '../../main.inc.php';

$idprod=GETPOST('idprod','int');

$prices = array();

$langs->load('stocks');

/*
 * View
*/

top_httphead();

//print '<!-- Ajax page called with url '.$_SERVER["PHP_SELF"].'?'.$_SERVER["QUERY_STRING"].' -->'."\n";

if (! empty($idprod))
{
	$sql = "SELECT p.rowid, p.label, p.ref, p.price, p.duration,";
	$sql.= " pfp.ref_fourn,";
	$sql.= " pfp.rowid as idprodfournprice, pfp.price as fprice, pfp.quantity, pfp.unitprice, pfp.charges, pfp.unitcharges,";
	$sql.= " s.nom";
	$sql.= " FROM ".MAIN_DB_PREFIX."product_fournisseur_price as pfp";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product as p ON p.rowid = pfp.fk_product";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = pfp.fk_soc";
	$sql.= " WHERE pfp.fk_product = ".$idprod;
	$sql.= " AND p.tobuy = 1";
	$sql.= " AND s.fournisseur = 1";
	$sql.= " ORDER BY s.nom, pfp.ref_fourn DESC";

	dol_syslog("Ajax::getSupplierPrices sql=".$sql, LOG_DEBUG);
	$result=$db->query($sql);

	if ($result)
	{
		$num = $db->num_rows($result);

		if ($num)
		{
			$i = 0;
			while ($i < $num)
			{
				$objp = $db->fetch_object($result);

				$label = $objp->nom.' - '.$objp->ref_fourn.' - ';

				if ($objp->quantity == 1)
				{
					$label.= price($objp->fprice);
					$label.= $langs->trans("Currency".$conf->monnaie)."/";
					$price = $objp->fprice;
				}

				$label.= $objp->quantity.' ';

				if ($objp->quantity == 1)
				{
					$label.= strtolower($langs->trans("Unit"));
				}
				else
				{
					$label.= strtolower($langs->trans("Units"));
				}
				if ($objp->quantity > 1)
				{
					$label.=" - ";
					$label.= price($objp->unitprice).$langs->trans("Currency".$conf->monnaie)."/".strtolower($langs->trans("Unit"));
					$price = $objp->unitprice;
				}
				if ($objp->unitcharges > 0 && ($conf->global->MARGIN_TYPE == "2")) {
					$label.=" + ";
					$label.= price($objp->unitcharges).$langs->trans("Currency".$conf->monnaie);
					$price += $objp->unitcharges;
				}
				if ($objp->duration) $label .= " - ".$objp->duration;

				$prices[] = array("id" => $objp->idprodfournprice, "price" => price($price,0,'',0), "label" => $label);
				$i++;
			}

			$db->free($result);
		}
	}

	echo json_encode($prices);
}

?>
