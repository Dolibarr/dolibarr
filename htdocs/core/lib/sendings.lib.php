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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/core/lib/sendings.lib.php
 *	\ingroup    expedition
 *	\brief      Library for expedition module
 */
require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
require_once(DOL_DOCUMENT_ROOT."/expedition/class/expedition.class.php");


function shipping_prepare_head($object)
{
	global $langs, $conf, $user;

	$langs->load("sendings");
	$langs->load("deliveries");

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT."/expedition/fiche.php?id=".$object->id;
	$head[$h][1] = $langs->trans("SendingCard");
	$head[$h][2] = 'shipping';
	$h++;

	if ($conf->livraison_bon->enabled && $user->rights->expedition->livraison->lire && ! empty($object->linkedObjectsIds['delivery'][0]))
	{
		$head[$h][0] = DOL_URL_ROOT."/livraison/fiche.php?id=".$object->linkedObjectsIds['delivery'][0];
		$head[$h][1] = $langs->trans("DeliveryCard");
		$head[$h][2] = 'delivery';
		$h++;
	}

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    // $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
    // $this->tabs = array('entity:-tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to remove a tab
    complete_head_from_modules($conf,$langs,$object,$head,$h,'delivery');

    return $head;
}


function delivery_prepare_head($object)
{
	global $langs, $conf, $user;

	$langs->load("sendings");
	$langs->load("deliveries");

	$h = 0;
	$head = array();

	if ($conf->expedition_bon->enabled && $user->rights->expedition->lire)
	{
		$head[$h][0] = DOL_URL_ROOT."/expedition/fiche.php?id=".$object->origin_id;
		$head[$h][1] = $langs->trans("SendingCard");
		$head[$h][2] = 'shipping';
		$h++;
	}

	$head[$h][0] = DOL_URL_ROOT."/livraison/fiche.php?id=".$object->id;
	$head[$h][1] = $langs->trans("DeliveryCard");
	$head[$h][2] = 'delivery';
	$h++;

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    // $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
    // $this->tabs = array('entity:-tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to remove a tab
    complete_head_from_modules($conf,$langs,$object,$head,$h,'delivery');

    return $head;
}

/**
 * List sendings and receive receipts
 *
 * @param   string		$origin			Origin
 * @param	int			$origin_id		Origin id
 * @param	string		$filter			Filter
 * @return
 */
function show_list_sending_receive($origin='commande',$origin_id,$filter='')
{
	global $db, $conf, $langs, $bc;
	global $html;

	$product_static=new Product($db);
	$expedition=new Expedition($db);

	$sql = "SELECT obj.rowid, obj.fk_product, obj.description, obj.product_type as fk_product_type, obj.qty as qty_asked";
	$sql.= ", ed.qty as qty_shipped, ed.fk_expedition as expedition_id, ed.fk_origin_line";
	$sql.= ", e.rowid as sendingid, e.ref as exp_ref, e.date_creation, e.date_delivery, e.date_expedition,";
	//if ($conf->livraison_bon->enabled) $sql .= " l.rowid as livraison_id, l.ref as livraison_ref, l.date_delivery, ld.qty as qty_received,";
	$sql.= ' p.label as product, p.ref, p.fk_product_type, p.rowid as prodid,';
	$sql.= ' p.description as product_desc';
	$sql.= " FROM ".MAIN_DB_PREFIX."expeditiondet as ed";
	$sql.= ", ".MAIN_DB_PREFIX."expedition as e";
	$sql.= ", ".MAIN_DB_PREFIX.$origin."det as obj";
    //if ($conf->livraison_bon->enabled) $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."livraison as l ON l.fk_expedition = e.rowid LEFT JOIN ".MAIN_DB_PREFIX."livraisondet as ld ON ld.fk_livraison = l.rowid  AND obj.rowid = ld.fk_origin_line";
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
			//print '<td align="left">'.$langs->trans("QtyOrdered").'</td>';
			print '<td align="left">'.$langs->trans("SendingSheet").'</td>';
			print '<td align="left">'.$langs->trans("Description").'</td>';
			print '<td align="center">'.$langs->trans("DateCreation").'</td>';
			print '<td align="center">'.$langs->trans("DateDeliveryPlanned").'</td>';
			print '<td align="center">'.$langs->trans("QtyShipped").'</td>';
			if ($conf->livraison_bon->enabled)
            {
                print '<td>'.$langs->trans("DeliveryOrder").'</td>';
                //print '<td align="center">'.$langs->trans("QtyReceived").'</td>';
				print '<td align="right">'.$langs->trans("DeliveryDate").'</td>';
            }
			print "</tr>\n";

			$var=True;
			while ($i < $num)
			{
				$var=!$var;
				$objp = $db->fetch_object($resql);
				print "<tr $bc[$var]>";

				// Sending id
				print '<td align="left" nowrap="nowrap"><a href="'.DOL_URL_ROOT.'/expedition/fiche.php?id='.$objp->expedition_id.'">'.img_object($langs->trans("ShowSending"),'sending').' '.$objp->exp_ref.'<a></td>';

				// Description
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

				// Date creation
				print '<td align="center" nowrap="nowrap">'.dol_print_date($db->jdate($objp->date_creation),'day').'</td>';

				// Date shipping creation
				print '<td align="center" nowrap="nowrap">'.dol_print_date($db->jdate($objp->date_delivery),'day').'</td>';

				// Qty shipped
				print '<td align="center">'.$objp->qty_shipped.'</td>';

				// Informations on receipt
				if ($conf->livraison_bon->enabled)
				{
					include_once(DOL_DOCUMENT_ROOT.'/livraison/class/livraison.class.php');
					$expedition->id=$objp->sendingid;
					$expedition->fetchObjectLinked($expedition->id,$expedition->element);
					//var_dump($expedition->linkedObjects);
					$receiving=$expedition->linkedObjects['delivery'][0];

					if (! empty($receiving))
					{
						// $expedition->fk_origin_line = id of det line of order
						// $receiving->fk_origin_line = id of det line of order
						// $receiving->origin may be 'shipping'
						// $receiving->origin_id may be id of shipping

						// Ref
						print '<td>';
						print $receiving->getNomUrl($db);
						//print '<a href="'.DOL_URL_ROOT.'/livraison/fiche.php?id='.$livraison_id.'">'.img_object($langs->trans("ShowReceiving"),'sending').' '.$objp->livraison_ref.'<a>';
						print '</td>';
						// Qty received
						//print '<td align="center">';
						// TODO No solution for the moment to link a line det of receipt with a line det of shipping,
						// so no way to know the qty received for this line of shipping.
						//print $langs->trans("FeatureNotYetAvailable");
						//print '</td>';
						// Date shipping real
						print '<td align="right">';
						print dol_print_date($receiving->date_delivery,'day');
						print '</td>';
					}
					else
					{
						//print '<td>&nbsp;</td>';
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