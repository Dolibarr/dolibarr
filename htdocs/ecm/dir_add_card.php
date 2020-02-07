<?php
/* Copyright (C) 2008-2017	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2008-2012	Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2015-2016	Alexandre Spangaro	<aspangaro@open-dsi.fr>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	\file		htdocs/ecm/dir_add_card.php
 *	\ingroup	ecm
 *	\brief		Main page for ECM section area
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/ecm/class/htmlecm.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/ecm/class/ecmdirectory.class.php';

// Load translation files required by the page
$langs->loadLangs(array("ecm","companies","other","users","orders","propal","bills","contracts","categories"));

// Get parameters
$socid      = GETPOST('socid', 'int');
$action     = GETPOST('action', 'alpha');
$cancel     = GETPOST('cancel', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');
$confirm    = GETPOST('confirm', 'alpha');

$module  = GETPOST('module', 'alpha');
$website = GETPOST('website', 'alpha');
$pageid  = GETPOST('pageid', 'int');
if (empty($module)) $module='ecm';

// Security check
if ($user->socid > 0)
{
    $action = '';
    $socid = $user->socid;
}

$section=$urlsection=GETPOST('section', 'alpha');
if (empty($urlsection)) $urlsection='misc';

if ($module == 'ecm')
{
	$upload_dir = $conf->ecm->dir_output.'/'.$urlsection;
}
else	// For example $module == 'medias'
{
	$upload_dir = $conf->medias->multidir_output[$conf->entity];
}

$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$page = GETPOST("page", 'int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
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
		dol_print_error($db, $ecmdir->error);
		exit;
	}
}

// Permissions
$permtoadd = 0;
$permtoupload = 0;
if ($module == 'ecm')
{
	$permtoadd = $user->rights->ecm->setup;
	$permtoupload = $user->rights->ecm->upload;
}
if ($module == 'medias')
{
	$permtoadd = ($user->rights->mailing->creer || $user->rights->website->write);
	$permtoupload = ($user->rights->mailing->creer || $user->rights->website->write);
}

if (! $permtoadd) accessforbidden();



/*
 * Actions
 */

// Action ajout d'un produit ou service
if ($action == 'add' && $permtoadd)
{
	if ($cancel)
	{
		if (! empty($backtopage))
		{
			header("Location: ".$backtopage);
			exit;
		}
		else
		{
			header("Location: ".DOL_URL_ROOT.'/ecm/index.php?action=file_manager'.($module?'&module='.$module:''));
			exit;
		}
	}

	$ref = trim(GETPOST("ref", 'alpha'));
	$label = trim(GETPOST("label", 'alpha'));
	$desc = trim(GETPOST("desc", 'alpha'));
	$catParent = GETPOST("catParent", 'alpha');	// Can be an int (with ECM) or a string (with generic filemanager)
	if ($catParent == '-1') $catParent=0;

	$error=0;

	if (empty($label))
	{
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Label")), null, 'errors');
		$action = 'create';
		$error++;
	}

	if (! $error)
	{
		if ($module == 'ecm')
		{
			$ecmdir->ref            = $ref;
			$ecmdir->label          = $label;
			$ecmdir->description    = $desc;
			$ecmdir->fk_parent      = (int) $catParent;

			$id = $ecmdir->create($user);
			if ($id <= 0)
			{
				$error++;
				$langs->load("errors");
				setEventMessages($ecmdir->error, $ecmdir->errors, 'errors');
				$action = 'create';
			}
		}
		else	// For example $module == 'medias'
		{
			$dirfornewdir = '';
			if ($module == 'medias')
			{
				$dirfornewdir = $conf->medias->multidir_output[$conf->entity];
			}
			if (empty($dirfornewdir))
			{
				$error++;
				dol_print_error('', 'Bad value for module. Not supported.');
			}

			if (! $error)
			{
				$fullpathofdir = $dirfornewdir.'/'.($catParent? $catParent.'/' : '').$label;
				$result = dol_mkdir($fullpathofdir, DOL_DATA_ROOT);
				if ($result < 0)
				{
					setEventMessages($langs->trans('ErrorFailToCreateDir', $label), null, 'errors');
					$error++;
				}
				else
				{
					setEventMessages($langs->trans("ECMSectionWasCreated", $label), null, 'mesgs');
				}
			}
		}
	}

	if (! $error)
	{
		if (! empty($backtopage))
		{
			header("Location: ".$backtopage);
			exit;
		}
		else
		{
			header("Location: ".DOL_URL_ROOT.'/ecm/index.php?action=file_manager');
			exit;
		}
	}
}

// Deleting file
elseif ($action == 'confirm_deletesection' && $confirm == 'yes')
{
	$result=$ecmdir->delete($user);
	setEventMessages($langs->trans("ECMSectionWasRemoved", $ecmdir->label), null, 'mesgs');
}




/*
 * View
 */

llxHeader('', $langs->trans("ECMNewSection"));

$form=new Form($db);
$formecm=new FormEcm($db);

if ($action == 'create')
{
	//***********************
	// Create
	//***********************
	print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="add">';
	print '<input type="hidden" name="module" value="'.dol_escape_htmltag($module).'">';
	print '<input type="hidden" name="backtopage" value="'.dol_escape_htmltag($backtopage).'">';
	if ($website) print '<input type="hidden" name="website" value="'.dol_escape_htmltag($website).'">';
	if ($pageid)  print '<input type="hidden" name="pageid" value="'.dol_escape_htmltag($pageid).'">';

	$title=$langs->trans("ECMNewSection");
	print load_fiche_titre($title);

	dol_fiche_head();

	print '<table class="border centpercent">';

	// Label
	print '<tr><td class="titlefieldcreate fieldrequired">'.$langs->trans("Label").'</td><td><input name="label" class="minwidth100" maxlength="32" value="'.$ecmdir->label.'"></td></tr>'."\n";

	print '<tr><td>'.$langs->trans("AddIn").'</td><td>';
	print $formecm->selectAllSections((GETPOST("catParent", 'alpha') ? GETPOST("catParent", 'alpha') : $ecmdir->fk_parent), 'catParent', $module);
	print '</td></tr>'."\n";

	// Description
	if ($module == 'ecm')
	{
		print '<tr><td class="tdtop">'.$langs->trans("Description").'</td><td>';
		print '<textarea name="desc" rows="4" class="quatrevingtpercent">';
		print $ecmdir->description;
		print '</textarea>';
		print '</td></tr>'."\n";
	}

	print '</table>';

	dol_fiche_end();

	print '<div class="center">';
	print '<input type="submit" class="button" name="create" value="'.$langs->trans("Create").'">';
	print ' &nbsp; &nbsp; ';
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
	print '<a href="'.DOL_URL_ROOT.'/ecm/dir_add_card.php">'.$langs->trans("ECMRoot").'</a>';
	//print ' -> <b>'.$ecmdir->getNomUrl(1).'</b><br>';
	print "<br><br>";
    */

	// Confirmation de la suppression d'une ligne categorie
	if ($action == 'delete_section')
	{
		print $form->formconfirm($_SERVER["PHP_SELF"].'?section='.$section, $langs->trans('DeleteSection'), $langs->trans('ConfirmDeleteSection', $ecmdir->label), 'confirm_deletesection');
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
		print '<a class="butActionRefused classfortooltip" href="#" title="'.$langs->trans("NotAllowed").'">'.$langs->trans('Delete').'</a>';
	}
	print '</div>';
}


// End of page
llxFooter();
$db->close();
