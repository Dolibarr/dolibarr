<?php
/* Copyright (C) 2003-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
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
        \file       htdocs/fourn/commande/history.php
        \ingroup    commande
        \brief      Fiche commande
        \version    $Id$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/fourn.lib.php");

$langs->load("orders");
$langs->load("suppliers");
$langs->load("companies");
$langs->load('stocks');

// Security check
$id = isset($_GET["id"])?$_GET["id"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'commande_fournisseur', $id,'');


/*
 * View
 */

$html =	new	Form($db);

$now=gmmktime();

$ref= $_GET['ref'];

if ($id > 0 || ! empty($ref))
{
	$soc = new Societe($db);
	$commande = new CommandeFournisseur($db);

	$result=$commande->fetch($_GET["id"],$_GET['ref']);
	if ($result >= 0)
	{
		$soc->fetch($commande->socid);

		$author = new User($db);
		$author->id = $commande->user_author_id;
		$author->fetch();

		llxHeader('',$langs->trans("History"),"CommandeFournisseur");

		$head = ordersupplier_prepare_head($commande);

		$title=$langs->trans("SupplierOrder");
		dol_fiche_head($head, 'info', $title);


		/*
		*   Commande
		*/

		print '<table class="border" width="100%">';

		// Ref
		print '<tr><td width="20%">'.$langs->trans("Ref").'</td>';
		print '<td colspan="2">';
		print $html->showrefnav($commande,'ref','',1,'ref','ref');
		print '</td>';
		print '</tr>';

		// Fournisseur
		print '<tr><td>'.$langs->trans("Supplier")."</td>";
		print '<td colspan="2">'.$soc->getNomUrl(1,'supplier').'</td>';
		print '</tr>';

		// Statut
		print '<tr>';
		print '<td>'.$langs->trans("Status").'</td>';
		print '<td colspan="2">';
		print $commande->getLibStatut(4);
		print "</td></tr>";

		// Date
		if ($commande->methode_commande_id > 0)
		{
			print '<tr><td>'.$langs->trans("Date").'</td><td colspan="2">';
			if ($commande->date_commande)
			{
				print dol_print_date($commande->date_commande,"dayhourtext")."\n";
			}
			print "</td></tr>";

			if ($commande->methode_commande)
			{
				print '<tr><td>'.$langs->trans("Method").'</td><td colspan="2">'.$commande->methode_commande.'</td></tr>';
			}
		}

		// Auteur
		print '<tr><td>'.$langs->trans("AuthorRequest").'</td>';
		print '<td colspan="2">'.$author->getNomUrl(1).'</td>';
		print '</tr>';

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
		print '<td align="left">'.$langs->trans("Comment").'</td>';
		print '</tr>';

		$sql = "SELECT l.fk_statut, l.datelog as dl, l.comment, u.rowid, u.login, u.firstname, u.name";
		$sql.= " FROM ".MAIN_DB_PREFIX."commande_fournisseur_log as l";
		$sql.= " , ".MAIN_DB_PREFIX."user as u ";
		$sql.= " WHERE l.fk_commande = ".$commande->id;
		$sql.= " AND u.rowid = l.fk_user";
		$sql.= " ORDER BY l.rowid DESC";

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

				print '<td width="20%">'.dol_print_date($db->jdate($obj->dl),"dayhour")."</td>\n";

				// Statut
				print '<td width="100px" nowrap="1">'.$commande->LibStatut($obj->fk_statut,4)."</td>\n";

				// User
				print '<td align="center"><a href="'.DOL_URL_ROOT.'/user/fiche.php?id='.$obj->rowid.'">';
				print img_object($langs->trans("ShowUser"),'user').' '.$obj->login.'</a></td>';

				// Comment
				print '<td width="100px" nowrap="1" title="'.dol_escape_htmltag($obj->comment).'">'.dol_trunc($obj->comment,48)."</td>\n";

				print '</tr>';

				$i++;
			}
			$db->free($resql);
		}
		else
		{
			dol_print_error($db);
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
