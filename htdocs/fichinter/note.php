<?php
/* Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
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
 \file       htdocs/fichinter/note.php
 \ingroup    fichinter
 \brief      Fiche d'information sur une fiche d'intervention
 \version    $Id$
 */

require('./pre.inc.php');
require_once(DOL_DOCUMENT_ROOT."/fichinter/fichinter.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/fichinter.lib.php");

$langs->load('companies');

$fichinterid = isset($_GET["id"])?$_GET["id"]:'';

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'ficheinter', $fichinterid, 'fichinter');


/******************************************************************************/
/*                     Actions                                                */
/******************************************************************************/

if ($_POST["action"] == 'update_public' && $user->rights->ficheinter->creer)
{
	$fichinter = new Fichinter($db);
	$fichinter->fetch($_GET['id']);

	$db->begin();

	$res=$fichinter->update_note_public($_POST["note_public"],$user);
	if ($res < 0)
	{
		$mesg='<div class="error">'.$fichinter->error.'</div>';
		$db->rollback();
	}
	else
	{
		$db->commit();
	}
}

if ($_POST['action'] == 'update' && $user->rights->ficheinter->creer)
{
	$fichinter = new Fichinter($db);
	$fichinter->fetch($_GET['id']);

	$db->begin();

	$res=$fichinter->update_note($_POST["note_private"],$user);
	if ($res < 0)
	{
		$mesg='<div class="error">'.$fichinter->error.'</div>';
		$db->rollback();
	}
	else
	{
		$db->commit();
	}
}



/******************************************************************************/
/* Affichage fiche                                                            */
/******************************************************************************/

llxHeader();

$html = new Form($db);

if ($_GET['id'])
{
	if ($mesg) print $mesg;

	$fichinter = new Fichinter($db);
	if ( $fichinter->fetch($_GET['id']) )
	{
		$societe = new Societe($db);
		if ( $societe->fetch($fichinter->socid) )
		{
			$head = fichinter_prepare_head($fichinter);
			dol_fiche_head($head, 'note', $langs->trans('InterventionCard'));

			print '<table class="border" width="100%">';

			print '<tr><td width="25%">'.$langs->trans('Ref').'</td><td colspan="3">'.$fichinter->ref.'</td></tr>';

			// Société
			print '<tr><td>'.$langs->trans('Company').'</td><td colspan="3">'.$societe->getNomUrl(1).'</td></tr>';

			// Note publique
			print '<tr><td valign="top">'.$langs->trans("NotePublic").' :</td>';
			print '<td valign="top" colspan="3">';
			if ($_GET["action"] == 'edit')
			{
				print '<form method="post" action="note.php?id='.$fichinter->id.'">';
				print '<input type="hidden" name="action" value="update_public">';
				print '<textarea name="note_public" cols="80" rows="8">'.$fichinter->note_public."</textarea><br>";
				print '<input type="submit" class="button" value="'.$langs->trans("Save").'">';
				print '</form>';
			}
			else
			{
				print ($fichinter->note_public?nl2br($fichinter->note_public):"&nbsp;");
			}
			print "</td></tr>";

			// Note privée
			if (! $user->societe_id)
			{
				print '<tr><td valign="top">'.$langs->trans("NotePrivate").' :</td>';
				print '<td valign="top" colspan="3">';
				if ($_GET["action"] == 'edit')
				{
					print '<form method="post" action="note.php?id='.$fichinter->id.'">';
					print '<input type="hidden" name="action" value="update">';
					print '<textarea name="note_private" cols="80" rows="8">'.$fichinter->note_private."</textarea><br>";
					print '<input type="submit" class="button" value="'.$langs->trans("Save").'">';
					print '</form>';
				}
				else
				{
					print ($fichinter->note_private?nl2br($fichinter->note_private):"&nbsp;");
				}
				print "</td></tr>";
			}
				
			print "</table>";

			print '</div>';

			/*
			 * Actions
			 */

			print '<div class="tabsAction">';
			if ($user->rights->ficheinter->creer && $_GET['action'] <> 'edit')
			{
				print '<a class="butAction" href="note.php?id='.$fichinter->id.'&amp;action=edit">'.$langs->trans('Modify').'</a>';
			}
			print '</div>';
		}
	}
}
$db->close();
llxFooter('$Date$ - $Revision: 1.15 ');
?>
