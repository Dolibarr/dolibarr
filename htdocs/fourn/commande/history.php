<?php
/* Copyright (C) 2003-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
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
 *       \file       htdocs/fourn/commande/history.php
 *       \ingroup    commande
 *       \brief      Fiche commande
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/fourn.lib.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';

$langs->load("orders");
$langs->load("suppliers");
$langs->load("companies");
$langs->load('stocks');

$id=GETPOST('id','int');
$ref=GETPOST('ref','alpha');

// Security check
$socid='';
if (! empty($user->societe_id)) $socid=$user->societe_id;
$result = restrictedArea($user, 'fournisseur', $id, '', 'commande');


/*
 * View
 */

$form =	new	Form($db);

$now=dol_now();

if ($id > 0 || ! empty($ref))
{
	$soc = new Societe($db);
	$commande = new CommandeFournisseur($db);

	$result=$commande->fetch($id,$ref);
	if ($result >= 0)
	{
		$soc->fetch($commande->socid);

		$author = new User($db);
		$author->fetch($commande->user_author_id);

		llxHeader('',$langs->trans("History"),"CommandeFournisseur");

		$head = ordersupplier_prepare_head($commande);

		$title=$langs->trans("SupplierOrder");
		dol_fiche_head($head, 'info', $title, 0, 'order');


		/*
		*   Commande
		*/

		print '<table class="border" width="100%">';

		$linkback = '<a href="'.DOL_URL_ROOT.'/fourn/commande/list.php'.(! empty($socid)?'?socid='.$socid:'').'">'.$langs->trans("BackToList").'</a>';

		// Ref
		print '<tr><td width="20%">'.$langs->trans("Ref").'</td>';
		print '<td colspan="2">';
		print $form->showrefnav($commande, 'ref', $linkback, 1, 'ref', 'ref');
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
                print '<tr><td>'.$langs->trans("Method").'</td><td colspan="2">'.$commande->getInputMethod().'</td></tr>';
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

		print '<tr class="liste_titre"><td class="liste_titre">'.$langs->trans("Date").'</td>';
		print '<td class="liste_titre">'.$langs->trans("Status").'</td>';
		print '<td class="liste_titre" align="center">'.$langs->trans("Author").'</td>';
		print '<td class="liste_titre" align="left">'.$langs->trans("Comment").'</td>';
		print '</tr>';

		$sql = "SELECT l.fk_statut, l.datelog as dl, l.comment, u.rowid, u.login, u.firstname, u.lastname";
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
				print "<tr ".$bc[$var].">";

				print '<td width="20%">'.dol_print_date($db->jdate($obj->dl),"dayhour")."</td>\n";

				// Statut
				print '<td class="nowrap">'.$commande->LibStatut($obj->fk_statut,4)."</td>\n";

				// User
				print '<td align="center"><a href="'.DOL_URL_ROOT.'/user/card.php?id='.$obj->rowid.'">';
				print img_object($langs->trans("ShowUser"),'user').' '.$obj->login.'</a></td>';

				// Comment
				print '<td class="nowrap" title="'.dol_escape_htmltag($obj->comment).'">'.dol_trunc($obj->comment,48)."</td>\n";

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
		/* Commande non trouvee */
		print "Commande inexistante ou acces refuse";
	}
}


llxFooter();
$db->close();
