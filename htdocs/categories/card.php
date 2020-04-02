<?php
/* Copyright (C) 2005		Matthieu Valleton	<mv@seeschloss.org>
 * Copyright (C) 2006-2017	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2014	Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2007		Patrick Raguin		<patrick.raguin@gmail.com>
 * Copyright (C) 2013		Florian Henry		<florian.henry@open-concept.pro>
 * Copyright (C) 2015       Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
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
 *		\file       htdocs/categories/card.php
 *		\ingroup    category
 *		\brief      Page to create a new category
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';

// Load translation files required by the page
$langs->load("categories");

// Security check
$socid = GETPOST('socid', 'int');
if (!$user->rights->categorie->lire) accessforbidden();

$action		= GETPOST('action', 'alpha');
$cancel		= GETPOST('cancel', 'alpha');
$origin		= GETPOST('origin', 'alpha');
$catorigin = GETPOST('catorigin', 'int');
$type = GETPOST('type', 'alpha');
$urlfrom	= GETPOST('urlfrom', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');

$socid = GETPOST('socid', 'int');
$label = GETPOST('label');
$description = GETPOST('description');
$color = GETPOST('color');
$visible = GETPOST('visible');
$parent = GETPOST('parent');

if ($origin)
{
	if ($type == Categorie::TYPE_PRODUCT)     $idProdOrigin     = $origin;
	if ($type == Categorie::TYPE_SUPPLIER)    $idSupplierOrigin = $origin;
	if ($type == Categorie::TYPE_CUSTOMER)    $idCompanyOrigin  = $origin;
	if ($type == Categorie::TYPE_MEMBER)      $idMemberOrigin   = $origin;
	if ($type == Categorie::TYPE_CONTACT)     $idContactOrigin  = $origin;
	if ($type == Categorie::TYPE_PROJECT)     $idProjectOrigin  = $origin;
}

if ($catorigin && $type == Categorie::TYPE_PRODUCT) $idCatOrigin = $catorigin;

$object = new Categorie($db);

$extrafields = new ExtraFields($db);
$extrafields->fetch_name_optionals_label($object->table_element);

// Initialize technical object to manage hooks. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('categorycard'));


/*
 *	Actions
 */

// Add action
if ($action == 'add' && $user->rights->categorie->creer)
{
	// Action ajout d'une categorie
	if ($cancel)
	{
		if ($urlfrom)
		{
			header("Location: ".$urlfrom);
			exit;
		}
		elseif ($idProdOrigin)
		{
			header("Location: ".DOL_URL_ROOT.'/categories/viewcat.php?id='.$idProdOrigin.'&type='.$type);
			exit;
		}
		elseif ($idCompanyOrigin)
		{
			header("Location: ".DOL_URL_ROOT.'/categories/viewcat.php?id='.$idCompanyOrigin.'&type='.$type);
			exit;
		}
		elseif ($idSupplierOrigin)
		{
			header("Location: ".DOL_URL_ROOT.'/categories/viewcat.php?id='.$idSupplierOrigin.'&type='.$type);
			exit;
		}
		elseif ($idMemberOrigin)
		{
			header("Location: ".DOL_URL_ROOT.'/categories/viewcat.php?id='.$idMemberOrigin.'&type='.$type);
			exit;
		}
		elseif ($idContactOrigin)
		{
			header("Location: ".DOL_URL_ROOT.'/categories/viewcat.php?id='.$idContactOrigin.'&type='.$type);
			exit;
		}
		elseif ($idProjectOrigin)
		{
			header("Location: ".DOL_URL_ROOT.'/categories/viewcat.php?id='.$idProjectOrigin.'&type='.$type);
			exit;
		}
		else
		{
			header("Location: ".DOL_URL_ROOT.'/categories/index.php?leftmenu=cat&type='.$type);
			exit;
		}
	}



	$object->label			= $label;
	$object->color			= $color;
	$object->description = dol_htmlcleanlastbr($description);
	$object->socid			= ($socid ? $socid : 'null');
	$object->visible = $visible;
	$object->type = $type;

	if ($parent != "-1") $object->fk_parent = $parent;

	$ret = $extrafields->setOptionalsFromPost(null, $object);
	if ($ret < 0) $error++;

	if (!$object->label)
	{
		$error++;
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Ref")), null, 'errors');
		$action = 'create';
	}

	// Create category in database
	if (!$error)
	{
		$result = $object->create($user);
		if ($result > 0)
		{
			$action = 'confirmed';
			$_POST["addcat"] = '';
		}
		else
		{
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}
}

// Confirm action
if (($action == 'add' || $action == 'confirmed') && $user->rights->categorie->creer)
{
	// Action confirmation de creation categorie
	if ($action == 'confirmed')
	{
		if ($urlfrom)
		{
			header("Location: ".$urlfrom);
			exit;
		}
		elseif ($backtopage)
		{
			header("Location: ".$backtopage);
			exit;
		}
		elseif ($idProdOrigin)
		{
			header("Location: ".DOL_URL_ROOT.'/categories/viewcat.php?id='.$idProdOrigin.'&type='.$type.'&mesg='.urlencode($langs->trans("CatCreated")));
			exit;
		}
		elseif ($idCompanyOrigin)
		{
			header("Location: ".DOL_URL_ROOT.'/categories/viewcat.php?id='.$idCompanyOrigin.'&type='.$type.'&mesg='.urlencode($langs->trans("CatCreated")));
			exit;
		}
		elseif ($idSupplierOrigin)
		{
			header("Location: ".DOL_URL_ROOT.'/categories/viewcat.php?id='.$idSupplierOrigin.'&type='.$type.'&mesg='.urlencode($langs->trans("CatCreated")));
			exit;
		}
		elseif ($idMemberOrigin)
		{
			header("Location: ".DOL_URL_ROOT.'/categories/viewcat.php?id='.$idMemberOrigin.'&type='.$type.'&mesg='.urlencode($langs->trans("CatCreated")));
			exit;
		}
		elseif ($idContactOrigin)
		{
			header("Location: ".DOL_URL_ROOT.'/categories/viewcat.php?id='.$idContactOrigin.'&type='.$type.'&mesg='.urlencode($langs->trans("CatCreated")));
			exit;
		}
		elseif ($idProjectOrigin)
		{
			header("Location: ".DOL_URL_ROOT.'/categories/viewcat.php?id='.$idProjectOrigin.'&type='.$type.'&mesg='.urlencode($langs->trans("CatCreated")));
			exit;
		}

		header("Location: ".DOL_URL_ROOT.'/categories/viewcat.php?id='.$result.'&type='.$type);
		exit;
	}
}


/*
 * View
 */

$form = new Form($db);
$formother = new FormOther($db);

$helpurl = '';
llxHeader("", $langs->trans("Categories"), $helpurl);

if ($user->rights->categorie->creer)
{
	// Create or add
	if ($action == 'create' || $_POST["addcat"] == 'addcat')
	{
		dol_set_focus('#label');

		print '<form action="'.$_SERVER['PHP_SELF'].'?type='.$type.'" method="POST">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="urlfrom" value="'.$urlfrom.'">';
		print '<input type="hidden" name="action" value="add">';
		print '<input type="hidden" name="addcat" value="addcat">';
		print '<input type="hidden" name="id" value="'.GETPOST('origin', 'alpha').'">';
		print '<input type="hidden" name="type" value="'.$type.'">';
		print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
		if ($origin) print '<input type="hidden" name="origin" value="'.$origin.'">';
		if ($catorigin)	print '<input type="hidden" name="catorigin" value="'.$catorigin.'">';

		print load_fiche_titre($langs->trans("CreateCat"));

		dol_fiche_head('');

		print '<table width="100%" class="border">';

		// Ref
		print '<tr>';
		print '<td class="titlefieldcreate fieldrequired">'.$langs->trans("Ref").'</td><td><input id="label" class="minwidth100" name="label" value="'.$label.'">';
		print'</td></tr>';

		// Description
		print '<tr><td class="tdtop">'.$langs->trans("Description").'</td><td>';
		require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
		$doleditor = new DolEditor('description', $description, '', 200, 'dolibarr_notes', '', false, true, $conf->global->FCKEDITOR_ENABLE_PRODUCTDESC, ROWS_6, '90%');
		$doleditor->Create();
		print '</td></tr>';

		// Color
		print '<tr><td>'.$langs->trans("Color").'</td><td>';
		print $formother->selectColor($color, 'color');
		print '</td></tr>';

		// Parent category
		print '<tr><td>'.$langs->trans("AddIn").'</td><td>';
		print $form->select_all_categories($type, $catorigin, 'parent');
		print ajax_combobox('parent');
		print '</td></tr>';

		$parameters = array();
		$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
        print $hookmanager->resPrint;
		if (empty($reshook))
		{
			print $object->showOptionals($extrafields, 'edit', $parameters);
		}

		print '</table>';

		dol_fiche_end('');

		print '<div class="center">';
		print '<input type="submit" class="button" value="'.$langs->trans("CreateThisCat").'" name="creation" />';
		print '&nbsp; &nbsp; &nbsp;';
		print '<input type="submit" class="button" value="'.$langs->trans("Cancel").'" name="cancel" />';
		print '</div>';

		print '</form>';
	}
}

// End of page
llxFooter();
$db->close();
