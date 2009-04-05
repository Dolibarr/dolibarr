<?php
/* Copyright (C) 2008-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
require_once(DOL_DOCUMENT_ROOT."/product.class.php");


/**
 * List sendings and receive receipts
 *
 * @param unknown_type $option
 * @return unknown
 */
function show_list_sending_receive($origin='commande',$origin_id,$filter='')
{
	global $db, $conf, $langs, $bc;
	global $html;

	$product_static=new Product($db);

	$sql = "SELECT obj.rowid, obj.fk_product, obj.description, obj.product_type as fk_product_type, obj.qty as qty_asked";
	$sql.= ", ed.qty as qty_shipped, ed.fk_expedition as expedition_id";
	$sql.= ", e.ref as exp_ref, ".$db->pdate("e.date_expedition")." as date_expedition,";
	if ($conf->livraison_bon->enabled) $sql .= " l.rowid as livraison_id, l.ref as livraison_ref, ".$db->pdate("l.date_livraison")." as date_delivery, ld.qty as qty_received,";
	$sql.= ' p.label as product, p.ref, p.fk_product_type, p.rowid as prodid,';
	$sql.= ' p.description as product_desc';
	$sql.= " FROM (".MAIN_DB_PREFIX."expeditiondet as ed,";
	$sql.= " ".MAIN_DB_PREFIX.$origin."det as obj,";
	$sql.= " ".MAIN_DB_PREFIX."expedition as e)";
    if ($conf->livraison_bon->enabled) $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."livraison as l ON l.fk_expedition = e.rowid LEFT JOIN ".MAIN_DB_PREFIX."livraisondet as ld ON ld.fk_livraison = l.rowid  AND obj.rowid = ld.fk_origin_line";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product as p ON obj.fk_product = p.rowid";
    $sql.= " WHERE obj.fk_".$origin." = ".$origin_id;
	if ($filter) $sql.=$filter;
	$sql.= " AND obj.rowid = ed.fk_origin_line";
	$sql.= " AND ed.fk_expedition = e.rowid";
	$sql.= " ORDER BY obj.fk_product";

	dol_syslog("show_list_sending_receive sql=".$sql, LOG_DEBUG);
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

				if ($objp->fk_product > 0)
				{
					print '<td>';

					// Show product and description
					$product_static->type=$objp->fk_product_type;
					$product_static->id=$objp->fk_product;
					$product_static->ref=$objp->ref;
					$product_static->libelle=$objp->product;
					$text=$product_static->getNomUrl(1);
					$text.= ' - '.$objp->product;
					$description=($conf->global->PRODUIT_DESC_IN_FORM?'':dol_htmlentitiesbr($objp->description));
					print $html->textwithtooltip($text,$description,3,'','',$i);

					// Show range
					print_date_range($objp->date_start,$objp->date_end);

					// Add description in form
					if ($conf->global->PRODUIT_DESC_IN_FORM)
					{
						print ($objp->description && $objp->description!=$objp->product)?'<br>'.dol_htmlentitiesbr($objp->description):'';
					}

					print '</td>';
				}
				else
				{
					print "<td>";
					if ($type==1) $text = img_object($langs->trans('Service'),'service');
					else $text = img_object($langs->trans('Product'),'product');
					print $text.' '.nl2br($objp->description);

					// Show range
					print_date_range($objp->date_start,$objp->date_end);
					print "</td>\n";
				}

				//print '<td align="center">'.$objp->qty_asked.'</td>';

				// Sending id
				print '<td align="left" nowrap="nowrap"><a href="'.DOL_URL_ROOT.'/expedition/fiche.php?id='.$objp->expedition_id.'">'.img_object($langs->trans("ShowSending"),'sending').' '.$objp->exp_ref.'<a></td>';

				print '<td align="center">'.$objp->qty_shipped.'</td>';

				print '<td align="center" nowrap="nowrap">'.dol_print_date($objp->date_expedition,'dayhour').'</td>';
				if ($conf->livraison_bon->enabled)
				{
					if ($objp->livraison_id)
					{
						print '<td><a href="'.DOL_URL_ROOT.'/livraison/fiche.php?id='.$objp->livraison_id.'">'.img_object($langs->trans("ShowSending"),'sending').' '.$objp->livraison_ref.'<a></td>';
						print '<td align="center">'.$objp->qty_received.'</td>';
						print '<td>'.dol_print_date($objp->date_delivery,'dayhour').'</td>';
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
		dol_print_error($db);
	}

	return 1;
}

?>