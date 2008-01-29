<?php
/* Copyright (C) 2004-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
        \file       htdocs/fourn/commande/note.php
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



llxHeader('',$langs->trans("OrderCard"),"CommandeFournisseur");

$html = new Form($db);

/* *************************************************************************** */
/*                                                                             */
/* Mode vue et edition                                                         */
/*                                                                             */
/* *************************************************************************** */
  
if ($_GET["id"] > 0)
{
	$commande = new CommandeFournisseur($db);
	if ( $commande->fetch($_GET["id"]) >= 0)
	{
		$soc = new Societe($db);
		$soc->fetch($commande->socid);

		$author = new User($db);
		$author->id = $commande->user_author_id;
		$author->fetch();

		$h = 0;
		$head[$h][0] = DOL_URL_ROOT.'/fourn/commande/fiche.php?id='.$commande->id;
		$head[$h][1] = $langs->trans("OrderCard");
		$h++;
		
		$head[$h][0] = DOL_URL_ROOT.'/fourn/commande/dispatch.php?id='.$commande->id;
	  $head[$h][1] = $langs->trans("OrderDispatch");
	  $h++;

		$head[$h][0] = DOL_URL_ROOT.'/fourn/commande/note.php?id='.$commande->id;
		$head[$h][1] = $langs->trans("Note");
		$a = $h;
		$h++;

		$head[$h][0] = DOL_URL_ROOT.'/fourn/commande/history.php?id='.$commande->id;
		$head[$h][1] = $langs->trans("OrderFollow");
		$h++;

		$title=$langs->trans("SupplierOrder");
		dolibarr_fiche_head($head, $a, $title);


		/*
		*   Commande
		*/
		print '<form action="note.php?id='.$commande->id.'" method="post">';
		print '<input type="hidden" name="action" value="updatenote">';

		print '<table class="border" width="100%">';

		// Ref
		print '<tr><td width="20%">'.$langs->trans("Ref").'</td>';
		print '<td colspan="3">'.$commande->ref.'</td>';
		print '</tr>';

		// Fournisseur
		print '<tr><td width="20%">'.$langs->trans("Supplier").'</td>';
		print '<td colspan="3">';
		print '<a href="'.DOL_URL_ROOT.'/fourn/fiche.php?socid='.$soc->id.'">'.img_object($langs->trans("ShowSupplier"),'company').' '.$soc->nom.'</a></td>';
		print '</tr>';

		print '<tr>';
		print '<td>'.$langs->trans("Status").'</td>';
		print '<td colspan="3">';
		print $commande->getLibStatut(4);
		print "</td></tr>";

		if ($commande->methode_commande_id > 0)
		{
			print '<tr><td>'.$langs->trans("Date").'</td>';
			print '<td colspan="2">';

			if ($commande->date_commande)
			{
				print dolibarr_print_date($commande->date_commande,'dayhourtext')."\n";
			}

			print '&nbsp;</td><td width="50%">';
			if ($commande->methode_commande)
			{
				print "Méthode : " .$commande->methode_commande;
			}
			print "</td></tr>";
		}

		print '<tr><td valign="top">'.$langs->trans("NotePublic").'</td>';
		print '<td colspan="3">';
		if ($user->rights->fournisseur->commande->creer) print '<textarea cols="90" rows="'.ROWS_4.'" name="note_public">';
		print nl2br($commande->note_public);
		if ($user->rights->fournisseur->commande->creer) print '</textarea>';
		print '</td></tr>';

		if (! $user->societe_id)
		{
			print '<tr><td valign="top">'.$langs->trans("NotePrivate").'</td>';
			print '<td colspan="3">';
			if ($user->rights->fournisseur->commande->creer) print '<textarea cols="90" rows="'.ROWS_6.'" name="note">';
			print nl2br($commande->note);
			if ($user->rights->fournisseur->commande->creer) print '</textarea>';
			print '</td></tr>';
		}
		
		if ($user->rights->fournisseur->commande->creer)
		{
			print '<tr><td colspan="4" align="center"><input type="submit" class="button" value="'.$langs->trans("Save").'"></td></tr>';
		}

		print "</table></form>";
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
