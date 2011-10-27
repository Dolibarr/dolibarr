<?php
/* Copyright (C) 2003		Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011	Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2011	Regis Houssin        <regis@dolibarr.fr>
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
 *  \file       	htdocs/compta/deplacement/fiche.php
 *  \brief      	Page to show a trip card
 */

require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/trip.lib.php");
require_once(DOL_DOCUMENT_ROOT."/compta/deplacement/class/deplacement.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");
if ($conf->projet->enabled)
{
	require_once(DOL_DOCUMENT_ROOT."/core/lib/project.lib.php");
	require_once(DOL_DOCUMENT_ROOT."/projet/class/project.class.php");
}

$langs->load("trips");


// Security check
$id = GETPOST('id');
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'deplacement', $id,'');

$action = GETPOST('action');
$confirm = GETPOST('confirm');

$mesg = '';

$object = new Deplacement($db);

/*
 * Actions
 */
if ($action == 'confirm_delete' && $confirm == "yes" && $user->rights->deplacement->supprimer)
{
	$result=$object->delete($id);
	if ($result >= 0)
	{
		Header("Location: index.php");
		exit;
	}
	else
	{
		$mesg=$object->error;
	}
}

if ($action == 'add' && $user->rights->deplacement->creer)
{
	if (! $_POST["cancel"])
	{
		$error=0;

		$object->date			= dol_mktime(12, 0, 0, $_POST["remonth"], $_POST["reday"], $_POST["reyear"]);
		$object->km				= $_POST["km"];
		$object->type			= $_POST["type"];
		$object->socid			= $_POST["socid"];
		$object->fk_user		= $_POST["fk_user"];
		$object->note_private	= $_POST["note_private"];
		$object->note_public	= $_POST["note_public"];

        if (! $object->date)
        {
            $mesg=$langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Date"));
            $error++;
        }
		if ($object->type == '-1') 	// Otherwise it is TF_LUNCH,...
		{
			$mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Type")).'</div>';
			$error++;
		}
		if (! ($object->fk_user > 0))
		{
			$mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Person")).'</div>';
			$error++;
		}

		if (! $error)
		{
			$id = $object->create($user);

			if ($id > 0)
			{
				Header("Location: " . $_SERVER["PHP_SELF"] . "?id=" . $id);
				exit;
			}
			else
			{
				$mesg=$object->error;
				$action='create';
			}
		}
		else
		{
			$action='create';
		}
	}
	else
	{
		Header("Location: index.php");
		exit;
	}
}

if ($action == 'update' && $user->rights->deplacement->creer)
{
	if (empty($_POST["cancel"]))
	{
		$result = $object->fetch($id);

		$object->date			= dol_mktime(12, 0 , 0, $_POST["remonth"], $_POST["reday"], $_POST["reyear"]);
		$object->km				= $_POST["km"];
		$object->type			= $_POST["type"];
		$object->fk_user		= $_POST["fk_user"];
		$object->socid			= $_POST["socid"];
		$object->note_private	= $_POST["note_private"];
		$object->note_public	= $_POST["note_public"];
		
		$result = $object->update($user);

		if ($result > 0)
		{
			Header("Location: " . $_SERVER["PHP_SELF"] . "?id=" . $id);
			exit;
		}
		else
		{
			$mesg=$object->error;
		}
	}
	else
	{
		Header("Location: " . $_SERVER["PHP_SELF"] . "?id=" . $id);
		exit;
	}
}

// Set into a project
if ($action == 'classin')
{
	$object->fetch($id);
	$result=$object->setProject($_POST['projectid']);
	if ($result < 0) dol_print_error($db, $object->error);
}



/*
 * View
 */

llxHeader();

$form = new Form($db);

/*
 * Action create
 */
if ($action == 'create')
{
	print_fiche_titre($langs->trans("NewTrip"));

	dol_htmloutput_errors($mesg);

	$datec = dol_mktime(12, 0, 0, $_POST["remonth"], $_POST["reday"], $_POST["reyear"]);

	print '<form name="add" action="' . $_SERVER["PHP_SELF"] . '" method="POST">' . "\n";
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="add">';

	print '<table class="border" width="100%">';

	print "<tr>";
	print '<td width="25%" class="fieldrequired">'.$langs->trans("Type").'</td><td>';
	print $form->select_type_fees(GETPOST("type"),'type',1);
	print '</td></tr>';

	print "<tr>";
	print '<td class="fieldrequired">'.$langs->trans("Person").'</td><td>';
	print $form->select_users(GETPOST("fk_user"),'fk_user',1);
	print '</td></tr>';

	print "<tr>";
	print '<td class="fieldrequired">'.$langs->trans("Date").'</td><td>';
	print $form->select_date($datec?$datec:-1,'','','','','add',1,1);
	print '</td></tr>';

	// Km
    print '<tr><td class="fieldrequired">'.$langs->trans("FeesKilometersOrAmout").'</td><td><input name="km" size="10" value="' . GETPOST("km") . '"></td></tr>';

    // Company
	print "<tr>";
	print '<td>'.$langs->trans("CompanyVisited").'</td><td>';
	print $form->select_societes(GETPOST("socid"),'socid','',1);
	print '</td></tr>';

	// Public note
	print '<tr>';
	print '<td class="border" valign="top">'.$langs->trans('NotePublic').'</td>';
	print '<td valign="top" colspan="2">';
	print '<textarea name="note_public" wrap="soft" cols="70" rows="'.ROWS_3.'"></textarea>';
	print '</td></tr>';

	// Private note
	if (! $user->societe_id)
	{
	    print '<tr>';
	    print '<td class="border" valign="top">'.$langs->trans('NotePrivate').'</td>';
	    print '<td valign="top" colspan="2">';
	    print '<textarea name="note_private" wrap="soft" cols="70" rows="'.ROWS_3.'"></textarea>';
	    print '</td></tr>';
	}

    print '</table>';

    print '<br><center><input class="button" type="submit" value="'.$langs->trans("Save").'"> &nbsp; &nbsp; ';
	print '<input class="button" type="submit" name="cancel" value="'.$langs->trans("Cancel").'"></center';

	print '</form>';
}
else if ($id)
{
	$result = $object->fetch($id);
	if ($result > 0)
	{
		dol_htmloutput_mesg($mesg);

		$head = trip_prepare_head($object);

		dol_fiche_head($head, 'card', $langs->trans("TripCard"), 0, 'trip');

		if ($action == 'edit')
		{
			$soc = new Societe($db);
			if ($object->socid)
			{
				$soc->fetch($object->socid);
			}

			print '<form name="update" action="' . $_SERVER["PHP_SELF"] . '" method="POST">' . "\n";
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print '<input type="hidden" name="action" value="update">';
			print '<input type="hidden" name="id" value="'.$id.'">';

			print '<table class="border" width="100%">';

			// Ref
			print "<tr>";
			print '<td width="20%">'.$langs->trans("Ref").'</td><td>';
			print $object->ref;
			print '</td></tr>';

			// Type
			print "<tr>";
			print '<td class="fieldrequired">'.$langs->trans("Type").'</td><td>';
			print $form->select_type_fees($_POST["type"]?$_POST["type"]:$object->type,'type',0);
			print '</td></tr>';

			// Who
			print "<tr>";
			print '<td class="fieldrequired">'.$langs->trans("Person").'</td><td>';
			print $form->select_users($_POST["fk_user"]?$_POST["fk_user"]:$object->fk_user,'fk_user',0);
			print '</td></tr>';

			// Date
			print '<tr><td class="fieldrequired">'.$langs->trans("Date").'</td><td>';
			print $form->select_date($object->date,'','','','','update');
			print '</td></tr>';
			
			// Km
			print '<tr><td class="fieldrequired">'.$langs->trans("FeesKilometersOrAmout").'</td><td>';
			print '<input name="km" class="flat" size="10" value="'.$object->km.'">';
			print '</td></tr>';
			
			// Where
			print "<tr>";
			print '<td>'.$langs->trans("CompanyVisited").'</td><td>';
			print $form->select_societes($soc->id,'socid','',1);
			print '</td></tr>';
			
			// Public note
			print '<tr><td valign="top">'.$langs->trans("NotePublic").'</td>';
			print '<td valign="top" colspan="3">';
			print '<textarea name="note_public" cols="80" rows="8">'.$object->note_public."</textarea><br>";
			print "</td></tr>";
			
			// Private note
			if (! $user->societe_id)
			{
				print '<tr><td valign="top">'.$langs->trans("NotePrivate").'</td>';
				print '<td valign="top" colspan="3">';
				print '<textarea name="note_private" cols="80" rows="8">'.$object->note_private."</textarea><br>";
				print "</td></tr>";
			}
			
			print '</table>';
			
			print '<br><center><input type="submit" class="button" value="'.$langs->trans("Save").'"> &nbsp; ';
			print '<input type="submit" name="cancel" class="button" value="'.$langs->trans("Cancel").'">';
			print '</center>';
			
			print '</form>';
			
			print '</div>';
		}
		else
		{
			/*
			 * Confirmation de la suppression du deplacement
			 */
			if ($action == 'delete')
			{
				$ret=$form->form_confirm($_SERVER["PHP_SELF"]."?id=".$id,$langs->trans("DeleteTrip"),$langs->trans("ConfirmDeleteTrip"),"confirm_delete");
				if ($ret == 'html') print '<br>';
			}

			$soc = new Societe($db);
			if ($object->socid) $soc->fetch($object->socid);
			
			if (! empty($conf->global->MAIN_USE_JQUERY_JEDITABLE) && $user->rights->deplacement->creer)
			{
				include(DOL_DOCUMENT_ROOT.'/core/tpl/ajaxeditinplace.tpl.php');
			}

			print '<table class="border" width="100%">';

			// Ref
			print "<tr>";
			print '<td width="20%">'.$langs->trans("Ref").'</td><td>';
			print $form->showrefnav($object,'id','',1,'rowid','ref','');
			print '</td></tr>';
			
			// Type
			print '<tr><td>'.$langs->trans("Type").'</td><td>'.$langs->trans($object->type).'</td></tr>';

			// Who
			print '<tr><td>'.$langs->trans("Person").'</td><td>';
			$userfee=new User($db);
			$userfee->fetch($object->fk_user);
			print $userfee->getNomUrl(1);
			print '</td></tr>';

			// Date
			print '<tr><td>'.$langs->trans("Date").'</td><td>';
			print dol_print_date($object->date,'day');
			print '</td></tr>';

			// Km/Price
			print '<tr><td>'.$langs->trans("FeesKilometersOrAmout").'</td>';
			print '<td>'.$form->editInPlace($object->km, 'km', $object->element, 'text').'</td></tr>';
			
			// Where
			print '<tr><td>'.$langs->trans("CompanyVisited").'</td>';
			print '<td>';
			if ($soc->id) print $soc->getNomUrl(1);
			print '</td></tr>';
			
			// Project
			if ($conf->projet->enabled)
			{
				$langs->load('projects');
				print '<tr>';
				print '<td>';

				print '<table class="nobordernopadding" width="100%"><tr><td>';
				print $langs->trans('Project');
				print '</td>';
				if ($action != 'classify' && $user->rights->deplacement->creer)
				{
					print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=classify&amp;id='.$object->id.'">';
					print img_edit($langs->trans('SetProject'),1);
					print '</a></td>';
				}
				print '</tr></table>';
				print '</td><td colspan="3">';
				if ($action == 'classify')
				{
					$form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, $object->socid, $object->fk_project,'projectid');
				}
				else
				{
					$form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, $object->socid, $object->fk_project,'none');
				}
				print '</td>';
				print '</tr>';
			}

			// Statut
			print '<tr><td>'.$langs->trans("Status").'</td><td>'.$object->getLibStatut(4).'</td></tr>';
			
			// Public note
			print '<tr><td valign="top">'.$langs->trans("NotePublic").'</td>';
			print '<td valign="top" colspan="3">';
			print $form->editInPlace(($object->note_public ? dol_nl2br($object->note_public) : "&nbsp;"), 'note_public', $object->element);
			print "</td></tr>";
			
			// Private note
			if (! $user->societe_id)
			{
				print '<tr><td valign="top">'.$langs->trans("NotePrivate").'</td>';
				print '<td valign="top" colspan="3">';
				print $form->editInPlace(($object->note_private ? dol_nl2br($object->note_private) : "&nbsp;"), 'note', $object->element);
				print "</td></tr>";
			}

			print "</table>";

			print '</div>';
			
			/*
			 * Barre d'actions
			 */
			
			print '<div class="tabsAction">';
			
			if ($user->rights->deplacement->creer)
			{
				print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=edit&id='.$id.'">'.$langs->trans('Modify').'</a>';
			}
			else
			{
				print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotAllowed")).'">'.$langs->trans('Modify').'</a>';
			}
			if ($user->rights->deplacement->supprimer)
			{
				print '<a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?action=delete&id='.$id.'">'.$langs->trans('Delete').'</a>';
			}
			else
			{
				print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotAllowed")).'">'.$langs->trans('Delete').'</a>';
			}
			
			print '</div>';
		}
	}
	else
	{
		dol_print_error($db);
	}
}

$db->close();

llxFooter();
?>
