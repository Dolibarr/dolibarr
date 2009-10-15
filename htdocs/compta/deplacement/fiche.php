<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *  \file       	htdocs/compta/deplacement/fiche.php
 *  \brief      	Page fiche d'un deplacement
 *  \version		$Id$
 */
require("./pre.inc.php");

$langs->load("trips");

// If socid provided by ajax company selector
if (! empty($_REQUEST['socid_id']))
{
	$_GET['socid'] = $_GET['socid_id'];
	$_POST['socid'] = $_POST['socid_id'];
	$_REQUEST['socid'] = $_REQUEST['socid_id'];
}

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
		$deplacement = new Deplacement($db);

		$deplacement->date = dol_mktime(12, 0, 0,
		$_POST["remonth"],
		$_POST["reday"],
		$_POST["reyear"]);

		$deplacement->km = $_POST["km"];
		$deplacement->type = $_POST["type"];
		$deplacement->socid = $_POST["socid"];
		$deplacement->fk_user = $_POST["fk_user"];

		$id = $deplacement->create($user);

		if ($id > 0)
		{
			Header ( "Location: fiche.php?id=".$id);
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
		Header ( "Location: index.php");
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

	if ($mesg) print $mesg."<br>";

	print "<form name='add' action=\"fiche.php\" method=\"post\">\n";
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="add">';

	print '<table class="border" width="100%">';

	print "<tr>";
	print '<td width="25%">'.$langs->trans("CompanyVisited").'</td><td>';
	print $html->select_societes($_GET["socid"],'socid','',1);
	print '</td></tr>';

	print "<tr>";
	print '<td>'.$langs->trans("Type").'</td><td>';
	print $html->select_type_fees($_GET["type"],'type',1);
	print '</td></tr>';

	print "<tr>";
	print '<td>'.$langs->trans("Person").'</td><td>';
	print $html->select_users($_GET["fk_user"],'fk_user',1);
	print '</td></tr>';

	print "<tr>";
	print '<td>'.$langs->trans("Date").'</td><td>';
	print $html->select_date('','','','','','add');
	print '</td></tr>';

	print '<tr><td>'.$langs->trans("FeesKilometersOrAmout").'</td><td><input name="km" size="10" value=""></td></tr>';
	print '<tr><td colspan="2" align="center"><input class="button" type="submit" value="'.$langs->trans("Save").'">&nbsp;';
	print '<input class="button" type="submit" name="cancel" value="'.$langs->trans("Cancel").'"></td></tr>';
	print '</table>';
	print '</form>';
}
else
{
	if ($id)
	{
		$deplacement = new Deplacement($db);
		$result = $deplacement->fetch($id);
		if ($result)
		{

			if ($mesg) print $mesg."<br>";

			if ($_GET["action"] == 'edit')
			{
				$soc = new Societe($db);
				$soc->fetch($deplacement->socid);

				$h=0;

				$head[$h][0] = DOL_URL_ROOT."/compta/deplacement/fiche.php?id=$deplacement->id";
				$head[$h][1] = $langs->trans("Card");

				dol_fiche_head($head, $hselected, $langs->trans("TripCard"), 0, 'trip');

				print "<form name='update' action=\"fiche.php\" method=\"post\">\n";
				print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
				print '<input type="hidden" name="action" value="update">';
				print '<input type="hidden" name="id" value="'.$id.'">';

				print '<table class="border" width="100%">';

				print "<tr>";
				print '<td width="20%">'.$langs->trans("Ref").'</td><td>';
				print $deplacement->ref;
				print '</td></tr>';

				print "<tr>";
				print '<td>'.$langs->trans("Type").'</td><td>';
				print $html->select_type_fees($deplacement->type,'type',1);
				print '</td></tr>';

				print "<tr>";
				print '<td>'.$langs->trans("CompanyVisited").'</td><td>';
				print $html->select_societes($soc->id,'socid','',1);
				print '</td></tr>';

				print "<tr>";
				print '<td>'.$langs->trans("Person").'</td><td>';
				print $html->select_users($deplacement->fk_user,'fk_user',1);
				print '</td></tr>';

				print '<tr><td>'.$langs->trans("Date").'</td><td>';
				print $html->select_date($deplacement->date,'','','','','update');
				print '</td></tr>';
				print '<tr><td>'.$langs->trans("FeesKilometersOrAmout").'</td><td><input name="km" class="flat" size="10" value="'.$deplacement->km.'"></td></tr>';

				print '<tr><td align="center" colspan="2"><input type="submit" class="button" value="'.$langs->trans("Save").'"> &nbsp; ';
				print '<input type="submit" name="cancel" class="button" value="'.$langs->trans("Cancel").'"></td></tr>';
				print '</table>';
				print '</form>';

				print '</div>';
			}
			else
			{
				$h=0;

				$head[$h][0] = DOL_URL_ROOT."/compta/deplacement/fiche.php?id=$deplacement->id";
				$head[$h][1] = $langs->trans("Card");

				dol_fiche_head($head, $hselected, $langs->trans("TripCard"), 0, 'trip');

				/*
				 * Confirmation de la suppression du d�placement
				 */
				if ($_GET["action"] == 'delete')
				{
					$ret=$html->form_confirm("fiche.php?id=".$id,$langs->trans("DeleteTrip"),$langs->trans("ConfirmDeleteTrip"),"confirm_delete");
					if ($ret == 'html') print '<br>';
				}

				$soc = new Societe($db);
				if ($deplacement->socid) $soc->fetch($deplacement->socid);

				print '<table class="border" width="100%">';

				print "<tr>";
				print '<td width="20%">'.$langs->trans("Ref").'</td><td>';
				print $deplacement->ref;
				print '</td></tr>';

				print '<tr><td>'.$langs->trans("Type").'</td><td>'.$langs->trans($deplacement->type).'</td></tr>';

				print '<tr><td width="20%">'.$langs->trans("CompanyVisited").'</td>';
				print '<td>';
				if ($soc->id) print $soc->getNomUrl(1);
				print '</td></tr>';

				print '<tr><td>'.$langs->trans("Person").'</td><td>';
				$userfee=new User($db,$deplacement->fk_user);
				$userfee->fetch();
				print $userfee->getNomUrl(1);
				print '</td></tr>';

				print '<tr><td>'.$langs->trans("Date").'</td><td>';
				print dol_print_date($deplacement->date);
				print '</td></tr>';

				print '<tr><td>'.$langs->trans("FeesKilometersOrAmout").'</td><td>'.$deplacement->km.'</td></tr>';

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

llxFooter('$Date$ - $Revision$');
?>
