<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *  \file       	htdocs/compta/deplacement/fiche.php
 *  \brief      	Page to show a trip card
 */

require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/compta/deplacement/class/deplacement.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");
if ($conf->projet->enabled)
{
	require_once(DOL_DOCUMENT_ROOT."/lib/project.lib.php");
	require_once(DOL_DOCUMENT_ROOT."/projet/class/project.class.php");
}

$langs->load("trips");


// Security check
$id=isset($_GET["id"])?$_GET["id"]:$_POST["id"];
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'deplacement', $id,'');

$mesg = '';



/*
 * Actions
 */
if ($_POST["action"] == 'confirm_delete' && $_POST["confirm"] == "yes" && $user->rights->deplacement->supprimer)
{
	$deplacement = new Deplacement($db);
	$result=$deplacement->delete($_GET["id"]);
	if ($result >= 0)
	{
		Header("Location: index.php");
		exit;
	}
	else
	{
		$mesg=$deplacement->error;
	}
}

if ($_POST["action"] == 'add' && $user->rights->deplacement->creer)
{
	if (! $_POST["cancel"])
	{
		$error=0;

        $dated=dol_mktime(12, 0, 0,
		$_POST["remonth"],
		$_POST["reday"],
		$_POST["reyear"]);

        $deplacement = new Deplacement($db);
		$deplacement->date = $dated;
		$deplacement->km = $_POST["km"];
		$deplacement->type = $_POST["type"];
		$deplacement->socid = $_POST["socid"];
		$deplacement->fk_user = $_POST["fk_user"];
		$deplacement->note = $_POST["note"];
		$deplacement->note_public = $_POST["note_public"];

        if (! $deplacement->date)
        {
            $mesg=$langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Date"));
            $error++;
        }
		if ($deplacement->type == '-1') 	// Otherwise it is TF_LUNCH,...
		{
			$mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Type")).'</div>';
			$error++;
		}
		if (! ($deplacement->fk_user > 0))
		{
			$mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Person")).'</div>';
			$error++;
		}

		if (! $error)
		{
			$id = $deplacement->create($user);

			if ($id > 0)
			{
				Header("Location: fiche.php?id=".$id);
				exit;
			}
			else
			{
				$mesg=$deplacement->error;
				$_GET["action"]='create';
			}
		}
		else
		{
			$_GET["action"]='create';
		}
	}
	else
	{
		Header("Location: index.php");
		exit;
	}
}

if ($_POST["action"] == 'update' && $user->rights->deplacement->creer)
{
	if (empty($_POST["cancel"]))
	{
		$deplacement = new Deplacement($db);
		$result = $deplacement->fetch($_POST["id"]);

		$deplacement->date = dol_mktime(12, 0 , 0,
		$_POST["remonth"],
		$_POST["reday"],
		$_POST["reyear"]);
		$deplacement->km      = $_POST["km"];
		$deplacement->type    = $_POST["type"];
		$deplacement->fk_user = $_POST["fk_user"];
		$deplacement->socid   = $_POST["socid"];
		$result = $deplacement->update($user);

		if ($result > 0)
		{
			Header("Location: fiche.php?id=".$_POST["id"]);
			exit;
		}
		else
		{
			$mesg=$deplacement->error;
		}
	}
	else
	{
		Header("Location: ".$_SERVER["PHP_SELF"]."?id=".$_POST["id"]);
		exit;
	}
}

// Set into a project
if ($_POST['action'] == 'classin')
{
	$trip = new Deplacement($db);
	$trip->fetch($_GET['id']);
	$result=$trip->setProject($_POST['projectid']);
	if ($result < 0) dol_print_error($db,$trip->error);
}



/*
 * View
 */

llxHeader();

$html = new Form($db);

/*
 * Action create
 */
if ($_GET["action"] == 'create')
{
	print_fiche_titre($langs->trans("NewTrip"));

	dol_htmloutput_errors($mesg);

	$datec = dol_mktime(12, 0, 0,
	$_POST["remonth"],
	$_POST["reday"],
	$_POST["reyear"]);

	print "<form name='add' action=\"fiche.php\" method=\"post\">\n";
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="add">';

	print '<table class="border" width="100%">';

	print "<tr>";
	print '<td width="25%" class="fieldrequired">'.$langs->trans("Type").'</td><td>';
	print $html->select_type_fees($_POST["type"]?$_POST["type"]:$_GET["type"],'type',1);
	print '</td></tr>';

	print "<tr>";
	print '<td class="fieldrequired">'.$langs->trans("Person").'</td><td>';
	print $html->select_users($_POST["fk_user"]?$_POST["fk_user"]:$_GET["fk_user"],'fk_user',1);
	print '</td></tr>';

	print "<tr>";
	print '<td class="fieldrequired">'.$langs->trans("Date").'</td><td>';
	print $html->select_date($datec?$datec:-1,'','','','','add',1,1);
	print '</td></tr>';

	// Km
    print '<tr><td class="fieldrequired">'.$langs->trans("FeesKilometersOrAmout").'</td><td><input name="km" size="10" value="'.($_POST["km"]?$_POST["km"]:'').'"></td></tr>';

    // Company
	print "<tr>";
	print '<td>'.$langs->trans("CompanyVisited").'</td><td>';
	print $html->select_societes($_POST["socid"]?$_POST["socid"]:$_GET["socid"],'socid','',1);
	print '</td></tr>';

	// Public note
	print '<tr>';
	print '<td class="border" valign="top">'.$langs->trans('NotePublic').'</td>';
	print '<td valign="top" colspan="2">';
	print '<textarea name="note_public" wrap="soft" cols="70" rows="'.ROWS_3.'">';
	if (is_object($objectsrc))    // Take value from source object
	{
	    print $objectsrc->note_public;
	}
	print '</textarea></td></tr>';

	// Private note
	if (! $user->societe_id)
	{
	    print '<tr>';
	    print '<td class="border" valign="top">'.$langs->trans('NotePrivate').'</td>';
	    print '<td valign="top" colspan="2">';
	    print '<textarea name="note" wrap="soft" cols="70" rows="'.ROWS_3.'">';
	    if (is_object($objectsrc))    // Take value from source object
	    {
	        print $objectsrc->note;
	    }
	    print '</textarea></td></tr>';
	}

    print '</table>';

    print '<br><center><input class="button" type="submit" value="'.$langs->trans("Save").'"> &nbsp; &nbsp; ';
	print '<input class="button" type="submit" name="cancel" value="'.$langs->trans("Cancel").'"></center';

	print '</form>';
}
else
{
	if ($id)
	{
		$deplacement = new Deplacement($db);
		$result = $deplacement->fetch($id);
		if ($result > 0)
		{
			if ($mesg) print $mesg."<br>";

			$h=0;

			$head[$h][0] = DOL_URL_ROOT."/compta/deplacement/fiche.php?id=$deplacement->id";
			$head[$h][1] = $langs->trans("Card");
			$head[$h][2] = 'card';
			$h++;

			$head[$h][0] = DOL_URL_ROOT."/compta/deplacement/note.php?id=$deplacement->id";
			$head[$h][1] = $langs->trans("Note");
			$head[$h][2] = 'note';
			$h++;

			dol_fiche_head($head, 'card', $langs->trans("TripCard"), 0, 'trip');

			if ($_GET["action"] == 'edit')
			{
				$soc = new Societe($db);
				if ($deplacement->socid)
				{
					$soc->fetch($deplacement->socid);
				}

				print "<form name='update' action=\"fiche.php\" method=\"post\">\n";
				print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
				print '<input type="hidden" name="action" value="update">';
				print '<input type="hidden" name="id" value="'.$id.'">';

				print '<table class="border" width="100%">';

				// Ref
				print "<tr>";
				print '<td width="20%">'.$langs->trans("Ref").'</td><td>';
				print $deplacement->ref;
				print '</td></tr>';

				// Type
				print "<tr>";
				print '<td class="fieldrequired">'.$langs->trans("Type").'</td><td>';
				print $html->select_type_fees($_POST["type"]?$_POST["type"]:$deplacement->type,'type',0);
				print '</td></tr>';

				// Who
				print "<tr>";
				print '<td class="fieldrequired">'.$langs->trans("Person").'</td><td>';
				print $html->select_users($_POST["fk_user"]?$_POST["fk_user"]:$deplacement->fk_user,'fk_user',0);
				print '</td></tr>';

				// Date
				print '<tr><td class="fieldrequired">'.$langs->trans("Date").'</td><td>';
				print $html->select_date($deplacement->date,'','','','','update');
				print '</td></tr>';

                // Km
                print '<tr><td class="fieldrequired">'.$langs->trans("FeesKilometersOrAmout").'</td><td><input name="km" class="flat" size="10" value="'.$deplacement->km.'"></td></tr>';

				// Where
                print "<tr>";
                print '<td>'.$langs->trans("CompanyVisited").'</td><td>';
                print $html->select_societes($soc->id,'socid','',1);
                print '</td></tr>';

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
				if ($_GET["action"] == 'delete')
				{
					$ret=$html->form_confirm("fiche.php?id=".$id,$langs->trans("DeleteTrip"),$langs->trans("ConfirmDeleteTrip"),"confirm_delete");
					if ($ret == 'html') print '<br>';
				}

				$soc = new Societe($db);
				if ($deplacement->socid) $soc->fetch($deplacement->socid);

				print '<table class="border" width="100%">';

				// Ref
				print "<tr>";
				print '<td width="20%">'.$langs->trans("Ref").'</td><td>';
                print $html->showrefnav($deplacement,'id','',1,'rowid','ref','');
				print '</td></tr>';

				// Type
				print '<tr><td>'.$langs->trans("Type").'</td><td>'.$langs->trans($deplacement->type).'</td></tr>';

				// Who
				print '<tr><td>'.$langs->trans("Person").'</td><td>';
				$userfee=new User($db);
				$userfee->fetch($deplacement->fk_user);
				print $userfee->getNomUrl(1);
				print '</td></tr>';

				// Date
				print '<tr><td>'.$langs->trans("Date").'</td><td>';
				print dol_print_date($deplacement->date,'day');
				print '</td></tr>';

				// Km/Price
				print '<tr><td>'.$langs->trans("FeesKilometersOrAmout").'</td><td>'.$deplacement->km.'</td></tr>';

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
					if ($_GET['action'] != 'classify')
					{
						print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=classify&amp;id='.$deplacement->id.'">';
						print img_edit($langs->trans('SetProject'),1);
						print '</a></td>';
					}
					print '</tr></table>';
					print '</td><td colspan="3">';
					if ($_GET['action'] == 'classify')
					{
						$html->form_project($_SERVER['PHP_SELF'].'?id='.$deplacement->id, $deplacement->socid, $deplacement->fk_project,'projectid');
					}
					else
					{
						$html->form_project($_SERVER['PHP_SELF'].'?id='.$deplacement->id, $deplacement->socid, $deplacement->fk_project,'none');
					}
					print '</td>';
					print '</tr>';
				}

				// Statut
				print '<tr><td>'.$langs->trans("Status").'</td><td>'.$deplacement->getLibStatut(4).'</td></tr>';

				print "</table>";

				print '</div>';
			}

		}
		else
		{
			dol_print_error($db);
		}
	}
}


/*
 * Barre d'actions
 *
 */

print '<div class="tabsAction">';

if ($_GET["action"] != 'create' && $_GET["action"] != 'edit')
{
	if ($user->rights->deplacement->creer)
	{
		print '<a class="butAction" href="fiche.php?action=edit&id='.$id.'">'.$langs->trans('Modify').'</a>';
	}
	else
	{
		print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotAllowed")).'">'.$langs->trans('Modify').'</a>';
	}
	if ($user->rights->deplacement->supprimer)
	{
		print '<a class="butActionDelete" href="fiche.php?action=delete&id='.$id.'">'.$langs->trans('Delete').'</a>';
	}
	else
	{
		print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotAllowed")).'">'.$langs->trans('Delete').'</a>';
	}
}

print '</div>';

$db->close();

llxFooter();
?>
