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
require("../../main.inc.php");

$prices = array();

$langs->load('stocks');

$sql = "SELECT p.rowid, p.label, p.ref, p.price, p.duration,";
$sql.= " pfp.ref_fourn,";
$sql.= " pfp.rowid as idprodfournprice, pfp.price as fprice, pfp.quantity, pfp.unitprice, pfp.charges, pfp.unitcharges,";
$sql.= " s.nom";
$sql.= " FROM ".MAIN_DB_PREFIX."product_fournisseur_price as pfp";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product as p ON p.rowid = pfp.fk_product";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = pfp.fk_soc";
$sql.= " WHERE pfp.fk_product = ".$_REQUEST['idprod'];
$sql.= " AND p.tobuy = 1";
$sql.= " AND s.fournisseur = 1";
$sql.= " ORDER BY s.nom, pfp.ref_fourn DESC";
																																		
dol_syslog("Form::select_product_fourn_price sql=".$sql,LOG_DEBUG);
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

?>
