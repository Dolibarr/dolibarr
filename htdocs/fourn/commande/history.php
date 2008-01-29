<?php
/* Copyright (C) 2003-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *
 * $Id$
 * $Source$
 */

/**
        \file       htdocs/fourn/commande/history.php
        \ingroup    commande
        \brief      Fiche commande
        \version    $Revision$
*/

require("./pre.inc.php");

$langs->load("orders");
$langs->load("suppliers");
$langs->load("companies");
$langs->load('stocks');


if (!$user->rights->fournisseur->commande->lire) accessforbidden();


/* *************************************************************************** */
/*                                                                             */
/* Mode vue                                                                    */
/*                                                                             */
/* *************************************************************************** */

if ($_GET["id"] > 0)
{
	$soc = new Societe($db);
	$commande = new CommandeFournisseur($db);

	if ( $commande->fetch($_GET["id"]) >= 0)
	{
		$soc->fetch($commande->socid);

		$author = new User($db);
		$author->id = $commande->user_author_id;
		$author->fetch();

		$addons[0][0] = DOL_URL_ROOT.'/fourn/fiche.php?socid='.$soc->id;
		$addons[0][1] = $soc->nom;

		llxHeader('',$langs->trans("History"),"CommandeFournisseur",$addons);

		$h = 0;
		$head[$h][0] = DOL_URL_ROOT.'/fourn/commande/fiche.php?id='.$commande->id;
		$head[$h][1] = $langs->trans("OrderCard");
		$h++;
		
		$head[$h][0] = DOL_URL_ROOT.'/fourn/commande/dispatch.php?id='.$commande->id;
	  $head[$h][1] = $langs->trans("OrderDispatch");
	  $h++;

		$head[$h][0] = DOL_URL_ROOT.'/fourn/commande/note.php?id='.$commande->id;
		$head[$h][1] = $langs->trans("Note");
		$h++;

		$head[$h][0] = DOL_URL_ROOT.'/fourn/commande/history.php?id='.$commande->id;
		$head[$h][1] = $langs->trans("OrderFollow");
		$a = $h;

		$h++;

		$title=$langs->trans("SupplierOrder");
		dolibarr_fiche_head($head, $a, $title);


		/*
		*   Commande
		*/

		print '<table class="border" width="100%">';

		// Ref
		print '<tr><td width="20%">'.$langs->trans("Ref").'</td>';
		print '<td colspan="3">'.$commande->ref.'</td>';
		print '</tr>';

		// Fournisseur
		print '<tr><td width="20%">'.$langs->trans("Supplier")."</td>";
		print '<td colspan="3">';
		print '<b><a href="'.DOL_URL_ROOT.'/fourn/fiche.php?socid='.$soc->id.'">'.img_object($langs->trans("ShowSupplier"),'company').' '.$soc->nom.'</a></b></td>';
		print '</tr>';

		print '<tr><td>'.$langs->trans("Status").'</td><td colspan="3">';
		print $commande->getLibStatut(4);
		print "</td></tr>";

		if ($commande->methode_commande_id > 0)
		{
			print '<tr><td>'.$langs->trans("Date").'</td>';
			print '<td colspan="2">'.dolibarr_print_date($commande->date_commande,"dayhourtext")."</td>\n";
			print '<td width="50%">&nbsp;';
			print "</td></tr>";
		}

		print "</table>\n";
		print "<br>";

		/*
		* Suivi historique
		* Date - Statut - Auteur
		*/
		print '<table class="noborder" width="100%">';

		print '<tr class="liste_titre"><td>'.$langs->trans("Date").'</td>';
		print '<td>'.$langs->trans("Status").'</td>';
		print '<td align="center">'.$langs->trans("Author").'</td>';
		print '</tr>';

		$sql = "SELECT l.fk_statut, ".$db->pdate("l.datelog") ."as dl, u.rowid, u.login, u.firstname, u.name";
		$sql .= " FROM ".MAIN_DB_PREFIX."commande_fournisseur_log as l ";
		$sql .= " , ".MAIN_DB_PREFIX."user as u ";
		$sql .= " WHERE l.fk_commande = ".$commande->id." AND u.rowid = l.fk_user";
		$sql .= " ORDER BY l.rowid DESC";

		$resql = $db->query($sql);
		if ($resql)
		{
			$num = $db->num_rows($resql);
			$i = 0;

			$var=True;
			while ($i < $num)
			{
				$var=!$var;

				$obj = $db->fetch_object($resql);
				print "<tr $bc[$var]>";

				print '<td width="20%">'.dolibarr_print_date($obj->dl,"dayhour")."</td>\n";

				// Statut
				print '<td width="100px" nowrap="1">'.$commande->LibStatut($obj->fk_statut,4)."</td>\n";

				// User
				print '<td align="center"><a href="'.DOL_URL_ROOT.'/user/fiche.php?id='.$obj->rowid.'">';
				print img_object($langs->trans("ShowUser"),'user').' '.$obj->login.'</a></td>';

				print '</tr>';

				$i++;
			}
			$db->free($resql);
		}
		else
		{
			dolibarr_print_error($db);
		}
		print "</table>";

		print '</div>';
	}
	else
	{
		/* Commande non trouvée */
		print "Commande inexistante ou accés refusé";
	}
}  

$db->close();

llxFooter('$Date$ - $Revision$');
?>
