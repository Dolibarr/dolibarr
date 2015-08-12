<?php
/* Copyright (C) 2005		Matthieu Valleton	<mv@seeschloss.org>
 * Copyright (C) 2006-2015	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2014	Regis Houssin		<regis.houssin@capnetworks.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *		\file       htdocs/categories/card.php
 *		\ingroup    category
 *		\brief      Page to create a new category
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';

$langs->load("categories");

// Security check
$socid=GETPOST('socid','int');
if (!$user->rights->categorie->lire) accessforbidden();

$action		= GETPOST('action','alpha');
$cancel		= GETPOST('cancel','alpha');
$origin		= GETPOST('origin','alpha');
$catorigin	= GETPOST('catorigin','int');
$type 		= GETPOST('type','alpha');
$urlfrom	= GETPOST('urlfrom','alpha');

$socid=GETPOST('socid','int');
$label=GETPOST('label');
$description=GETPOST('description');
$visible=GETPOST('visible','int');
$parent=GETPOST('parent','int');

if ($origin)
{
	if ($type == Categorie::TYPE_PRODUCT)     $idProdOrigin     = $origin;
	if ($type == Categorie::TYPE_SUPPLIER)    $idSupplierOrigin = $origin;
	if ($type == Categorie::TYPE_CUSTOMER)    $idCompanyOrigin  = $origin;
	if ($type == Categorie::TYPE_MEMBER)      $idMemberOrigin   = $origin;
	if ($type == Categorie::TYPE_CONTACT)     $idContactOrigin  = $origin;
}

if ($catorigin && $type == Categorie::TYPE_PRODUCT) $idCatOrigin = $catorigin;

$object = new Categorie($db);

$extrafields = new ExtraFields($db);
$extralabels=$extrafields->fetch_name_optionals_label($object->table_element);

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
		else if ($idProdOrigin)
		{
			header("Location: ".DOL_URL_ROOT.'/categories/categorie.php?id='.$idProdOrigin.'&type='.$type);
			exit;
		}
		else if ($idCompanyOrigin)
		{
			header("Location: ".DOL_URL_ROOT.'/categories/categorie.php?socid='.$idCompanyOrigin.'&type='.$type);
			exit;
		}
		else if ($idSupplierOrigin)
		{
			header("Location: ".DOL_URL_ROOT.'/categories/categorie.php?socid='.$idSupplierOrigin.'&type='.$type);
			exit;
		}
		else if ($idMemberOrigin)
		{
			header("Location: ".DOL_URL_ROOT.'/categories/viewcat.php?id='.$idMemberOrigin.'&type='.$type);
			exit;
		}
		else if ($idCatOrigin)
		{
			header("Location: ".DOL_URL_ROOT.'/categories/viewcat.php?id='.$idCatOrigin.'&type='.$type);
			exit;
		}
		else if ($idContactOrigin)
		{
			header("Location: ".DOL_URL_ROOT.'/categories/viewcat.php?id='.$idContactOrigin.'&type='.$type);
			exit;
		}
		else
		{
			header("Location: ".DOL_URL_ROOT.'/categories/index.php?leftmenu=cat&type='.$type);
			exit;
		}
	}



	$object->label			= $label;
	$object->description	= dol_htmlcleanlastbr($description);
	$object->socid			= ($socid ? $socid : 'null');
	$object->visible		= $visible;
	$object->type			= $type;

	if ($parent != "-1") $object->fk_parent = $parent;

	$ret = $extrafields->setOptionalsFromPost($extralabels,$object);
	if ($ret < 0) $error++;

	if (! $object->label)
	{
		$error++;
		setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentities("Ref")), 'errors');
		$action = 'create';
	}

	// Create category in database
	if (! $error)
	{
		$result = $object->create($user);
		if ($result > 0)
		{
			$action = 'confirmed';
			$_POST["addcat"] = '';
		}
		else
		{
			setEventMessage($object->error,'errors');
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
		else if ($idProdOrigin)
		{
			header("Location: ".DOL_URL_ROOT.'/categories/categorie.php?id='.$idProdOrigin.'&mesg='.urlencode($langs->trans("CatCreated")));
			exit;
		}
		else if ($idCompanyOrigin)
		{
			header("Location: ".DOL_URL_ROOT.'/categories/categorie.php?socid='.$idCompanyOrigin.'&mesg='.urlencode($langs->trans("CatCreated")));
			exit;
		}
		else if ($idSupplierOrigin)
		{
			header("Location: ".DOL_URL_ROOT.'/categories/categorie.php?socid='.$idSupplierOrigin.'&mesg='.urlencode($langs->trans("CatCreated")));
			exit;
		}
		else if ($idMemberOrigin)
		{
			header("Location: ".DOL_URL_ROOT.'/categories/viewcat.php?id='.$idMemberOrigin.'&type='.$type);
			exit;
		}
		else if ($idCatOrigin)
		{
			header("Location: ".DOL_URL_ROOT.'/categories/viewcat.php?id='.$idCatOrigin.'&mesg='.urlencode($langs->trans("CatCreated")));
			exit;
		}
		else if ($idContactOrigin)
		{
			header("Location: ".DOL_URL_ROOT.'/categories/viewcat.php?id='.$idContactOrigin.'&mesg='.urlencode($langs->trans("CatCreated")));
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

llxHeader("","",$langs->trans("Categories"));

if ($user->rights->categorie->creer)
{
	/*
	 * Fiche en mode creation
	 */
	if ($action == 'create' || $_POST["addcat"] == 'addcat')
	{
		dol_set_focus('#label');

		print '<form action="'.$_SERVER['PHP_SELF'].'?type='.$type.'" method="POST">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="urlfrom" value="'.$urlfrom.'">';
		print '<input type="hidden" name="action" value="add">';
		print '<input type="hidden" name="addcat" value="addcat">';
		print '<input type="hidden" name="id" value="'.GETPOST('origin').'">';
		print '<input type="hidden" name="type" value="'.$type.'">';
		if ($origin) print '<input type="hidden" name="origin" value="'.$origin.'">';
		if ($catorigin)	print '<input type="hidden" name="catorigin" value="'.$catorigin.'">';

		print_fiche_titre($langs->trans("CreateCat"));

		dol_fiche_head('');

		print '<table width="100%" class="border">';

		// Ref
		print '<tr>';
		print '<td width="20%" class="fieldrequired">'.$langs->trans("Ref").'</td><td><input id="label" class="flat" name="label" size="25" value="'.$label.'">';
		print'</td></tr>';

		// Description
		print '<tr><td valign="top">'.$langs->trans("Description").'</td><td>';
		require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
		$doleditor=new DolEditor('description',$description,'',200,'dolibarr_notes','',false,true,$conf->global->FCKEDITOR_ENABLE_PRODUCTDESC,ROWS_6,50);
		$doleditor->Create();
		print '</td></tr>';

		// Parent category
		print '<tr><td>'.$langs->trans("AddIn").'</td><td>';
		print $form->select_all_categories($type, $catorigin);
		print '</td></tr>';

		$parameters=array();
		$reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook
		if (empty($reshook))
		{
			print $object->showOptionals($extrafields,'edit');
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


llxFooter();

$db->close();
