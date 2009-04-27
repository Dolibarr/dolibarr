<?php
/* Copyright (C) 2004-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin         <regis@dolibarr.fr>
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
        \file       htdocs/fourn/commande/note.php
        \ingroup    commande
        \brief      Fiche note commande
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
 * Actions
 */

if ($_POST["action"] == 'updatenote' && $user->rights->fournisseur->commande->creer)
{
	$commande = new CommandeFournisseur($db);
	$commande->fetch($_GET["id"]);

	$result = $commande->UpdateNote($user, $_POST["note"], $_POST["note_public"]);
	if ($result >= 0)
	{
		Header("Location: note.php?id=".$_GET["id"]);
		exit;
	}
}


/*
 * View
 */

llxHeader('',$langs->trans("OrderCard"),"CommandeFournisseur");

$html = new Form($db);

/* *************************************************************************** */
/*                                                                             */
/* Mode vue et edition                                                         */
/*                                                                             */
/* *************************************************************************** */

$now=gmmktime();

$id = $_GET['id'];
$ref= $_GET['ref'];
if ($id > 0 || ! empty($ref))
{
	$commande = new CommandeFournisseur($db);
	$result=$commande->fetch($_GET["id"],$_GET['ref']);
	if ($result >= 0)
	{
		$soc = new Societe($db);
		$soc->fetch($commande->socid);

		$author = new User($db);
		$author->id = $commande->user_author_id;
		$author->fetch();

		$head = ordersupplier_prepare_head($commande);

		$title=$langs->trans("SupplierOrder");
		dol_fiche_head($head, 'note', $title);


		/*
		*   Commande
		*/
		print '<form action="note.php?id='.$commande->id.'" method="post">';
		print '<input type="hidden" name="action" value="updatenote">';

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

		print '<tr><td valign="top">'.$langs->trans("NotePublic").'</td>';
		print '<td colspan="2">';
		if ($user->rights->fournisseur->commande->creer) print '<textarea cols="90" rows="'.ROWS_4.'" name="note_public">';
		print $commande->note_public;
		if ($user->rights->fournisseur->commande->creer) print '</textarea>';
		print '</td></tr>';

		if (! $user->societe_id)
		{
			print '<tr><td valign="top">'.$langs->trans("NotePrivate").'</td>';
			print '<td colspan="2">';
			if ($user->rights->fournisseur->commande->creer) print '<textarea cols="90" rows="'.ROWS_6.'" name="note">';
			print $commande->note;
			if ($user->rights->fournisseur->commande->creer) print '</textarea>';
			print '</td></tr>';
		}

		if ($user->rights->fournisseur->commande->creer)
		{
			print '<tr><td colspan="3" align="center"><input type="submit" class="button" value="'.$langs->trans("Save").'"></td></tr>';
		}

		print "</table></form>";

		print "</div>\n";
	}
	else
	{
		/* Commande non trouvée */
		print "Commande inexistante";
	}
}


$db->close();

llxFooter('$Date$ - $Revision$');
?>
