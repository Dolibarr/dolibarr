<?php
/* Copyright (C) 2008-2012	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2008-2012	Regis Houssin		<regis.houssin@capnetworks.com>
 * Copyright (C) 2015-2016	Alexandre Spangaro	<aspangaro.dolibarr@gmail.com>
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
 *	\file		htdocs/ecm/docdir.php
 *	\ingroup	ecm
 *	\brief		Main page for ECM section area
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/ecm/class/htmlecm.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/ecm/class/ecmdirectory.class.php';

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

if (! $user->rights->ecm->setup) accessforbidden();

// Get parameters
$socid = GETPOST('socid','int');
$action=GETPOST('action','alpha');
$confirm=GETPOST('confirm','alpha');

// Security check
if ($user->societe_id > 0)
{
    $action = '';
    $socid = $user->societe_id;
}

$section=$urlsection=GETPOST('section');
if (empty($urlsection)) $urlsection='misc';
$upload_dir = $conf->ecm->dir_output.'/'.$urlsection;

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
if (! empty($section))
{
	$result=$ecmdir->fetch($section);
	if (! $result > 0)
	{
		dol_print_error($db,$ecmdir->error);
		exit;
	}
}


/*
 * Actions
 */

// Action ajout d'un produit ou service
if ($action == 'add' && $user->rights->ecm->setup)
{
	if (! empty($_POST["cancel"]))
	{
		header("Location: ".DOL_URL_ROOT.'/ecm/index.php?action=file_manager');
		exit;
	}
	$ecmdir->ref                = trim($_POST["ref"]);
	$ecmdir->label              = trim($_POST["label"]);
	$ecmdir->description        = trim($_POST["desc"]);
	$ecmdir->fk_parent          = $_POST["catParent"];

	$ok=true;

	if (! $ecmdir->label)
	{
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Label")), null, 'errors');
		$action = 'create';
		$ok=false;
	}

	if ($ok)
	{
		$id = $ecmdir->create($user);

		if ($id > 0)
		{
			header("Location: ".DOL_URL_ROOT.'/ecm/index.php?action=file_manager');
			exit;
		}
		else
		{
			$langs->load("errors");
			setEventMessages($langs->trans($ecmdir->error), $ecmdir->errors, 'errors');
			$action = 'create';
		}
	}
}

// Deleting file
else if ($action == 'confirm_deletesection' && $confirm == 'yes')
{
	$result=$ecmdir->delete($user);
	setEventMessages($langs->trans("ECMSectionWasRemoved", $ecmdir->label), null, 'mesgs');
}




/*
 * View
 */

llxHeader();

$form=new Form($db);
$formecm=new FormEcm($db);

if ($action == 'create')
{
	//***********************
	// Create
	//***********************
	print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="add">';

	$title=$langs->trans("ECMNewSection");
	print load_fiche_titre($title);
	
	dol_fiche_head();

	print '<table class="border" width="100%">';

	// Label
	print '<tr><td class="titlefieldcreate fieldrequired">'.$langs->trans("Label").'</td><td><input name="label" size="40" maxlength="32" value="'.$ecmdir->label.'"></td></tr>'."\n";

	print '<tr><td>'.$langs->trans("AddIn").'</td><td>';
	print $formecm->select_all_sections(! empty($_GET["catParent"])?$_GET["catParent"]:$ecmdir->fk_parent,'catParent');
	print '</td></tr>'."\n";

	// Description
	print '<tr><td class="tdtop">'.$langs->trans("Description").'</td><td>';
	print '<textarea name="desc" rows="4" cols="90">';
	print $ecmdir->description;
	print '</textarea>';
	print '</td></tr>'."\n";

	print '</table>';

	dol_fiche_end();

	print '<div class="center">';
	print '<input type="submit" class="button" name="create" value="'.$langs->trans("Create").'">';
	print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
	print '</div>';
	print '</form>';
}


if (empty($action) || $action == 'delete_section')
{
	//***********************
	// List
	//***********************
	print load_fiche_titre($langs->trans("ECMSectionOfDocuments"));
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
	if ($action == 'delete_section')
	{
		print $form->formconfirm($_SERVER["PHP_SELF"].'?section='.$section, $langs->trans('DeleteSection'), $langs->trans('ConfirmDeleteSection',$ecmdir->label), 'confirm_deletesection');
		
	}

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
llxFooter();
$db->close();
