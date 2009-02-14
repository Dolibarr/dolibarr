<?php
/* Copyright (C) 2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	\file       htdocs/lib/sendings.lib.php
 *	\ingroup    expedition
 *	\brief      Library for expedition module
 *	\version    $Id$
 */


/**
 * List sendings and receive receipts
 *
 * @param unknown_type $option
 * @return unknown
 */
function show_list_sending_receive($origin='commande',$origin_id,$filter='')
{
	global $db, $conf, $langs, $bc;
	
	$sql = "SELECT obj.rowid, obj.fk_product, obj.description, obj.qty as qty_asked";
	$sql.= ", ed.qty as qty_shipped, ed.fk_expedition as expedition_id";
	$sql.= ", e.ref, ".$db->pdate("e.date_expedition")." as date_expedition";
    if ($conf->livraison_bon->enabled) $sql .= ", l.rowid as livraison_id, l.ref as livraison_ref, ".$db->pdate("l.date_livraison")." as date_delivery, ld.qty as qty_received";
	$sql.= " FROM (".MAIN_DB_PREFIX."expeditiondet as ed,";
	$sql.= " ".MAIN_DB_PREFIX.$origin."det as obj,";
	$sql.= " ".MAIN_DB_PREFIX."expedition as e)";
    if ($conf->livraison_bon->enabled) $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."livraison as l ON l.fk_expedition = e.rowid LEFT JOIN ".MAIN_DB_PREFIX."livraisondet as ld ON ld.fk_livraison = l.rowid  AND obj.rowid = ld.fk_origin_line";
	$sql.= " WHERE obj.fk_".$origin." = ".$origin_id;
	if ($filter) $sql.=$filter;
	$sql.= " AND obj.rowid = ed.fk_origin_line";
	$sql.= " AND ed.fk_expedition = e.rowid";
	$sql.= " ORDER BY obj.fk_product";

	dolibarr_syslog("show_list_sending_receive sql=".$sql, LOG_DEBUG);
	$resql = $db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);
		$i = 0;

		if ($num)
		{
			if ($somethingshown) print '<br>';

			if ($filter) print_titre($langs->trans("OtherSendingsForSameOrder"));
			else print_titre($langs->trans("SendingsAndReceivingForSameOrder")); 
			
			print '<table class="liste" width="100%">';
			print '<tr class="liste_titre">';
			print '<td align="left">'.$langs->trans("Description").'</td>';
			//print '<td align="left">'.$langs->trans("QtyOrdered").'</td>';
			print '<td align="left">'.$langs->trans("SendingSheet").'</td>';
			print '<td align="center">'.$langs->trans("QtyShipped").'</td>';
			print '<td align="center">'.$langs->trans("DateSending").'</td>';
			if ($conf->livraison_bon->enabled)
            {
                print '<td>'.$langs->trans("DeliveryOrder").'</td>';
                print '<td align="center">'.$langs->trans("QtyReceived").'</td>';
				print '<td align="center">'.$langs->trans("DeliveryDate").'</td>';
            }
			print "</tr>\n";

			$var=True;
			while ($i < $num)
			{
				$var=!$var;
				$objp = $db->fetch_object($resql);
				print "<tr $bc[$var]>";
				
				// Description
				if ($objp->fk_product > 0)
				{
					$product = new Product($db);
					$product->fetch($objp->fk_product);

					print '<td>';
					print '<a href="'.DOL_URL_ROOT.'/product/fiche.php?id='.$objp->fk_product.'">'.img_object($langs->trans("ShowProduct"),"product").' '.$product->ref.'</a> - '.dolibarr_trunc($product->libelle,20);
					if ($objp->description) print '<br>'.dol_htmlentitiesbr(dolibarr_trunc($objp->description,24));
					print '</td>';
				}
				else
				{
					print "<td>".dol_htmlentitiesbr(dolibarr_trunc($objp->description,24))."</td>\n";
				}

				//print '<td align="center">'.$objp->qty_asked.'</td>';
				
				// Sending id
				print '<td align="left" nowrap="nowrap"><a href="'.DOL_URL_ROOT.'/expedition/fiche.php?id='.$objp->expedition_id.'">'.img_object($langs->trans("ShowSending"),'sending').' '.$objp->ref.'<a></td>';

				print '<td align="center">'.$objp->qty_shipped.'</td>';
				
				print '<td align="center" nowrap="nowrap">'.dolibarr_print_date($objp->date_expedition,'dayhour').'</td>';
				if ($conf->livraison_bon->enabled)
				{
					if ($objp->livraison_id)
					{
						print '<td><a href="'.DOL_URL_ROOT.'/livraison/fiche.php?id='.$objp->livraison_id.'">'.img_object($langs->trans("ShowSending"),'sending').' '.$objp->livraison_ref.'<a></td>';
						print '<td align="center">'.$objp->qty_received.'</td>';
						print '<td>'.dolibarr_print_date($objp->date_delivery,'dayhour').'</td>';
					}
					else
					{
						print '<td>&nbsp;</td>';
						print '<td>&nbsp;</td>';
						print '<td>&nbsp;</td>';
					}
				}
				print '</tr>';
				$i++;
			}

			print '</table>';
		}
		$db->free($resql);
	}
	else
	{
		dolibarr_print_error($db);
	}

	return 1;
}

?>