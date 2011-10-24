<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *      \file       htdocs/fourn/facture/note.php
 *      \ingroup    facture
 *      \brief      Fiche de notes sur une facture fournisseur
 */

require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT.'/core/lib/fourn.lib.php');
require_once(DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.class.php');
require_once(DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php');

$langs->load('bills');
$langs->load("companies");

$facid = isset($_GET["facid"])?$_GET["facid"]:'';
$action=empty($_GET['action']) ? (empty($_POST['action']) ? '' : $_POST['action']) : $_GET['action'];

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'fournisseur', $facid, 'facture_fourn', 'facture');

$fac = new FactureFournisseur($db);
$fac->fetch($_GET["facid"]);


/******************************************************************************/
/*                     Actions                                                */
/******************************************************************************/

if ($_POST["action"] == 'update_public' && $user->rights->facture->creer)
{
	$db->begin();

	$res=$fac->update_note_public($_POST["note_public"],$user);
	if ($res < 0)
	{
		$mesg='<div class="error">'.$fac->error.'</div>';
		$db->rollback();
	}
	else
	{
		$db->commit();
	}
}

if ($_POST["action"] == 'update' && $user->rights->fournisseur->facture->creer)
{
	$db->begin();

	$res=$fac->update_note($_POST["note"],$user);
	if ($res < 0)
	{
		$mesg='<div class="error">'.$fac->error.'</div>';
		$db->rollback();
	}
	else
	{
		$db->commit();
	}
}



/*
 * View
 */

$html = new Form($db);

llxHeader();

if ($_GET["facid"])
{
    $fac->fetch_thirdparty();

	$head = facturefourn_prepare_head($fac);
	$titre=$langs->trans('SupplierInvoice');
	dol_fiche_head($head, 'note', $titre, 0, 'bill');


    print '<table class="border" width="100%">';

	// Ref
	print '<tr><td width="20%" nowrap="nowrap">'.$langs->trans("Ref").'</td><td colspan="3">';
	print $html->showrefnav($fac,'facid','',1,'rowid','ref',$morehtmlref);
	print '</td>';
	print "</tr>\n";

	// Ref supplier
	print '<tr><td nowrap="nowrap">'.$langs->trans("RefSupplier").'</td><td colspan="3">'.$fac->ref_supplier.'</td>';
	print "</tr>\n";

    // Company
    print '<tr><td>'.$langs->trans('Supplier').'</td><td colspan="3">'.$fac->thirdparty->getNomUrl(1).'</td></tr>';

	// Note publique
    print '<tr><td valign="top">'.$langs->trans("NotePublic").' :</td>';
	print '<td valign="top" colspan="3">';
    if ($_GET["action"] == 'edit')
    {
        print '<form method="post" action="note.php?facid='.$fac->id.'">';
        print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
        print '<input type="hidden" name="action" value="update_public">';
        print '<textarea name="note_public" cols="80" rows="8">'.$fac->note_public."</textarea><br>";
        print '<input type="submit" class="button" value="'.$langs->trans("Save").'">';
        print '</form>';
    }
    else
    {
	    print ($fac->note_public?nl2br($fac->note_public):"&nbsp;");
    }
	print "</td></tr>";

	// Note private
	if (! $user->societe_id)
	{
	    print '<tr><td valign="top">'.$langs->trans("NotePrivate").' :</td>';
		print '<td valign="top" colspan="3">';
	    if ($_GET["action"] == 'edit')
	    {
	        print '<form method="post" action="note.php?facid='.$fac->id.'">';
	        print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	        print '<input type="hidden" name="action" value="update">';
	        print '<textarea name="note" cols="80" rows="8">'.$fac->note."</textarea><br>";
	        print '<input type="submit" class="button" value="'.$langs->trans("Save").'">';
	        print '</form>';
	    }
		else
		{
		    print ($fac->note?nl2br($fac->note):"&nbsp;");
		}
		print "</td></tr>";
	}

    print "</table>";

    dol_fiche_end();

    /*
    * Actions
    */
    print '<div class="tabsAction">';

    if ($user->rights->fournisseur->facture->creer && $_GET["action"] <> 'edit')
    {
        print "<a class=\"butAction\" href=\"note.php?facid=$fac->id&amp;action=edit\">".$langs->trans('Modify')."</a>";
    }

    print "</div>";


}

$db->close();

llxFooter();
?>
