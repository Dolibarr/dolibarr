<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003-2005 Eric Seigne <eric.seigne@ryxeo.com>
 * Copyright (C) 2004-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *
 */

/**
 *  \file       htdocs/boutique/commande/fiche.php
 *  \ingroup    boutique
 *  \brief      Page fiche commande OSCommerce
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/boutique/osc_master.inc.php';
include_once DOL_DOCUMENT_ROOT.'/boutique/commande/class/boutiquecommande.class.php';

$langs->load("products");

$id=GETPOST('id', 'int');

/*
 *	View
 */

llxHeader();


if ($id > 0)
{
	$commande = new BoutiqueCommande($db);
	$result = $commande->fetch($id);
	if ($result)
	{
		print '<div class="titre">'.$langs->trans("OrderCard").': '.$commande->id.'</div><br>';

		print '<table border="1" width="100%" cellspacing="0" cellpadding="4">';
		print '<tr><td width="20%">Date</td><td width="80%" colspan="2">'.$commande->date.'</td></tr>';
		print '<td width="20%">Client</td><td width="80%" colspan="2"><a href="'.DOL_URL_ROOT.'/boutique/client/fiche.php?id='.$commande->client_id.'">'.$commande->client_name.'</a></td></tr>';

		print '<td width="20%">Paiement</td><td width="80%" colspan="2">'.$commande->payment_method.'</td></tr>';

		print "<tr><td>".$langs->trans("Address")."</td><td>".$langs->trans("Delivery")."</td><td>".$langs->trans("Invoice")."</td></tr>";

		print "<td>&nbsp;</td><td>".$commande->delivery_adr->name."<br>".$commande->delivery_adr->street."<br>".$commande->delivery_adr->zip."<br>".$commande->delivery_adr->city."<br>".$commande->delivery_adr->country."</td>";
		print "<td>".$commande->billing_adr->name."<br>".$commande->billing_adr->street."<br>".$commande->billing_adr->zip."<br>".$commande->billing_adr->city."<br>".$commande->billing_adr->country."</td>";
		print "</tr>";

		print "</table>";

		print "<br>";

		/*
		 * Produits
		 *
		 */
		$sql = "SELECT orders_id, products_id, products_model, products_name, products_price, final_price, products_quantity";
		$sql .= " FROM ".$conf->global->OSC_DB_NAME.".".$conf->global->OSC_DB_TABLE_PREFIX."orders_products";
		$sql .= " WHERE orders_id = " . $commande->id;
		//$commande->id;
		//	echo $sql;
		$resql=$dbosc->query($sql);
		if ($resql)
		{
			$num = $dbosc->num_rows($resql);
			$i = 0;
			print '<table class="noborder" width="100%">';
			print '<tr class="liste_titre"><td align="left" width="40%">'.$langs->trans("Products").'</td>';
			print '<td align="center">'.$langs->trans("Number").'</td><td align="right">'.$langs->trans("Price").'</td><td align="right">Prix final</td>';
			print "</tr>\n";
			$var=True;
			while ($i < $num)
			{
				$objp = $dbosc->fetch_object($resql);
				$var=!$var;
				print "<tr $bc[$var]>";
				print '<td align="left" width="40%">';
				print '<a href="fiche.php?id='.$objp->products_id.'"><img src="/theme/'.$conf->theme.'/img/filenew.png" border="0" width="16" height="16" alt="Fiche livre"></a>';

				print '<a href="fiche.php?id='.$objp->products_id.'">'.$objp->products_name.'</a>';
				print "</td>";

				print '<td align="center"><a href="fiche.php?id='.$objp->rowid."\">$objp->products_quantity</a></TD>\n";
				print "<td align=\"right\"><a href=\"fiche.php?id=$objp->rowid\">".price($objp->products_price)."</a></TD>\n";
				print "<td align=\"right\"><a href=\"fiche.php?id=$objp->rowid\">".price($objp->final_price)."</a></TD>\n";

				print "</tr>\n";
				$i++;
			}
			print "</table>";
			$dbosc->free();
		}
		else
		{
			print $dbosc->error();
		}

		/*
		 *
		 *
		 */
		print "<br>";

		print '<table border="1" width="100%" cellspacing="0" cellpadding="4">';
		print "<tr>";
		print '<td width="20%">Frais d\'expeditions</td><td width="80%">'.price($commande->total_ot_shipping).' EUR</td></tr>';
		print '<td width="20%">'.$langs->trans("Lastname").'</td><td width="80%">'.price($commande->total_ot_total).' EUR</td></tr>';
		print "</table>";



	}
	else
	{
		print "Fetch failed";
	}
}
else
{
	print "Error";
}


/* ************************************************************************** */
/*                                                                            */
/* Barre d'action                                                             */
/*                                                                            */
/* ************************************************************************** */

print '<br><table width="100%" border="1" cellspacing="0" cellpadding="3">';
print '<td width="20%" align="center">-</td>';
print '<td width="20%" align="center">-</td>';
print '<td width="20%" align="center">-</td>';
print '<td width="20%" align="center">-</td>';
print '<td width="20%" align="center">-</td>';
print '</table><br>';



$dbosc->close();

llxFooter();
?>
