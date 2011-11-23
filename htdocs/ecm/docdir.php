<?php
/* Copyright (C) 2008-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2008      Regis Houssin        <regis@dolibarr.fr>
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
 *	\file      	htdocs/ecm/docdir.php
 *	\ingroup   	ecm
 *	\brief     	Main page for ECM section area
 *	\author		Laurent Destailleur
 */

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");
require_once(DOL_DOCUMENT_ROOT."/ecm/class/htmlecm.form.class.php");
require_once(DOL_DOCUMENT_ROOT."/ecm/class/ecmdirectory.class.php");

// Load traductions files
$langs->load("ecm");
$langs->load("companies");
$langs->load("other");
$langs->load("users");
$langs->load("orders");
$langs->load("propal");
$langs->load("bills");
$langs->load("contracts");
$langs->load("categories");

// Load permissions
$user->getrights('ecm');

if (!$user->rights->ecm->setup) accessforbidden();

// Get parameters
$socid = isset($_GET["socid"])?$_GET["socid"]:'';

$section=$_GET["section"];
if (! $section) $section='misc';
$upload_dir = $conf->ecm->dir_output.'/'.$section;

$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) { $page = 0; }
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="label";

$ecmdir = new EcmDirectory($db);
if (! empty($_GET["section"]))
{
	$result=$ecmdir->fetch($_GET["section"]);
	if (! $result > 0)
	{
		dol_print_error($db,$ecmdir->error);
		exit;
	}
}


/*******************************************************************
 * ACTIONS
 *
 * Put here all code to do according to value of "action" parameter
 ********************************************************************/

// Action ajout d'un produit ou service
if ($_POST["action"] == 'add' && $user->rights->ecm->setup)
{
	if (! empty($_POST["cancel"]))
	{
		Header("Location: ".DOL_URL_ROOT.'/ecm/index.php?action=file_manager');
		exit;
	}
	$ecmdir->ref                = trim($_POST["ref"]);
	$ecmdir->label              = trim($_POST["label"]);
	$ecmdir->description        = trim($_POST["desc"]);
	$ecmdir->fk_parent          = $_POST["catParent"];

	$ok=true;

	if (! $ecmdir->label)
	{
		$mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentities("Label")).'</div>';
		$_GET["action"] = "create";
		$ok=false;
	}

	if ($ok)
	{
		$id = $ecmdir->create($user);

		if ($id > 0)
		{
			Header("Location: ".DOL_URL_ROOT.'/ecm/index.php?action=file_manager');
			exit;
		}
		else
		{
			$langs->load("errors");
			$mesg='<div class="error">'.$langs->trans($ecmdir->error).'</div>';
			$_GET["action"] = "create";
		}
	}
}

// Suppression fichier
if ($_POST['action'] == 'confirm_deletesection' && $_POST['confirm'] == 'yes')
{
	$result=$ecmdir->delete($user);
	$mesg = '<div class="ok">'.$langs->trans("ECMSectionWasRemoved", $ecmdir->label).'</div>';
}




/*******************************************************************
 * PAGE
 *
 * Put here all code to do according to value of "action" parameter
 ********************************************************************/

llxHeader();

$form=new Form($db);
$formecm=new FormEcm($db);

if ($_GET["action"] == 'create')
{
	//***********************
	// Create
	//***********************
	print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="add">';

	$title=$langs->trans("ECMNewSection");
	print_fiche_titre($title);
	if ($mesg) { print $mesg."<br>"; }

	print '<table class="border" width="100%">';

	// Label
	print '<tr><td class="fieldrequired">'.$langs->trans("Label").'</td><td><input name="label" size="40" maxlength="32" value="'.$ecmdir->label.'"></td></tr>'."\n";

	print '<tr><td>'.$langs->trans ("AddIn").'</td><td>';
	print $formecm->select_all_sections(! empty($_GET["catParent"])?$_GET["catParent"]:$ecmdir->fk_parent,'catParent');
	print '</td></tr>'."\n";

	// Description
	print '<tr><td valign="top">'.$langs->trans("Description").'</td><td>';
	print '<textarea name="desc" rows="4" cols="90">';
	print $ecmdir->description;
	print '</textarea>';
	print '</td></tr>'."\n";

	print '</td></tr>'."\n";

	print '</table><br>';

	print '<center>';
	print '<input type="submit" class="button" name="create" value="'.$langs->trans("Create").'">';
	print ' &nbsp; &nbsp; ';
	print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
	print '</center>';
	print '</form>';
}


if (! $_GET["action"] || $_GET["action"] == 'delete_section')
{
	//***********************
	// List
	//***********************
	print_fiche_titre($langs->trans("ECMSectionOfDocuments"));
	print '<br>';

/*
	$ecmdir->ref=$ecmdir->label;
	print $langs->trans("ECMSection").': ';
	print img_picto('','object_dir').' ';
	print '<a href="'.DOL_URL_ROOT.'/ecm/docdir.php">'.$langs->trans("ECMRoot").'</a>';
	//print ' -> <b>'.$ecmdir->getNomUrl(1).'</b><br>';
	print "<br><br>";
*/

	// Confirmation de la suppression d'une ligne categorie
	if ($_GET['action'] == 'delete_section')
	{
		$ret=$form->form_confirm($_SERVER["PHP_SELF"].'?section='.$_GET["section"], $langs->trans('DeleteSection'), $langs->trans('ConfirmDeleteSection',$ecmdir->label), 'confirm_deletesection');
		if ($ret == 'html') print '<br>';
	}

	if ($mesg) { print $mesg."<br>"; }


	// Construit fiche  rubrique


	// Actions buttons
	print '<div class="tabsAction">';
	if ($user->rights->ecm->setup)
	{
		print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=delete_section">'.$langs->trans('Delete').'</a>';
	}
	else
	{
		print '<a class="butActionRefused" href="#" title="'.$langs->trans("NotAllowed").'">'.$langs->trans('Delete').'</a>';
	}
	print '</div>';
}


// End of page
$db->close();

llxFooter();
?>
