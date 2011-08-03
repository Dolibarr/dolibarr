<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003-2005 Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2006-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * $Id: fiche.php,v 1.22 2011/08/03 00:45:42 eldy Exp $
 */

/**
 *	\file       htdocs/boutique/client/fiche.php
 *	\ingroup    boutique
 *	\brief      Page fiche client OSCommerce
 *	\version    $Revision: 1.22 $
 */

require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT.'/boutique/osc_master.inc.php');
include_once(DOL_DOCUMENT_ROOT.'/boutique/client/class/boutiqueclient.class.php');


/*
 * Actions
 */

if ($action == 'update' && !$cancel)
{
	$client = new BoutiqueClient($dbosc);
	$client->nom = $nom;
	$client->update($id, $user);
}



/*
 * View
 */

llxHeader();

if ($_GET['id'])
{
	$client = new BoutiqueClient($dbosc);
	$result = $client->fetch($_GET['id']);
	if ( $result )
	{
		print '<div class="titre">'.$langs->trans("CustomerCard").': '.$client->name.'</div><br>';

		print '<table border="1" width="100%" cellspacing="0" cellpadding="4">';
		print "<tr>";
		print '<td width="20%">Nom</td><td width="80%">'.$client->name.'</td></tr>';
		print "</table>";


		/*
		 * Commandes
		 */
		$sql = "SELECT o.orders_id, o.customers_id, date_purchased, t.value as total";
		$sql .= " FROM ".$conf->global->OSC_DB_NAME.".".$conf->global->OSC_DB_TABLE_PREFIX."orders as o, ".$conf->global->OSC_DB_NAME.".".$conf->global->OSC_DB_TABLE_PREFIX."orders_total as t";
		$sql .= " WHERE o.customers_id = " . $_GET['id'];
		$sql .= " AND o.orders_id = t.orders_id AND t.class = 'ot_total'";
		//echo $sql;
		$resql=$dbosc->query($sql);
		if ($resql)
		{
			$num = $dbosc->num_rows($resql);
			$i = 0;
			print '<table class="noborder" width="50%">';
			print "<tr class=\"liste_titre\"><td>Commandes</td>";
			print "</tr>\n";
			$var=True;
			while ($i < $num)
			{
				$objp = $dbosc->fetch_object($resql);
				$var=!$var;
				print "<tr $bc[$var]>";

				print '<td><a href="'.DOL_URL_ROOT.'/boutique/commande/fiche.php?id='.$objp->orders_id.'"><img src="/theme/'.$conf->theme.'/img/filenew.png" border="0" alt="Fiche">&nbsp;';

				print dol_print_date($dbosc->jdate($objp->date_purchased),'dayhour')."</a>\n";
				print $objp->total . "</a></TD>\n";
				print "</tr>\n";
				$i++;
			}
			print "</table>";
			$dbosc->free($resql);
		}
		else
		{
			print "<p>ERROR 1</p>\n";
			dol_print_error($dbosc);
		}

	}
	else
	{
		print "<p>ERROR 1</p>\n";
		dol_print_error($dbosc);
	}
}
else
{
	print "<p>ERROR 1</p>\n";
	print "Error";
}


/* ************************************************************************** */
/*                                                                            */
/* Barre d'action                                                             */
/*                                                                            */
/* ************************************************************************** */

// Pas d'action


$dbosc->close();

llxFooter('$Date: 2011/08/03 00:45:42 $ - $Revision: 1.22 $');
?>
