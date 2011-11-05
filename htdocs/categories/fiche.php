<?php
/* Copyright (C) 2005      Matthieu Valleton    <mv@seeschloss.org>
 * Copyright (C) 2006-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2011 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2007      Patrick Raguin	  	<patrick.raguin@gmail.com>
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
 *		\file       htdocs/categories/fiche.php
 *		\ingroup    category
 *		\brief      Page to create a new category
 */

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/categories/class/categorie.class.php");

$langs->load("categories");


// Security check
$socid=GETPOST('socid');
if (!$user->rights->categorie->lire) accessforbidden();

$action		= GETPOST('action');
$cancel		= GETPOST('cancel');
$origin		= GETPOST('origin');
$catorigin	= GETPOST('catorigin');
$nbcats 	= (GETPOST('choix') ? GETPOST('choix') : 1);
$type 		= GETPOST('type');
$urlfrom	= GETPOST("urlfrom");

if ($origin)
{
	if ($type == 0) $idProdOrigin 		= $origin;
	if ($type == 1) $idSupplierOrigin 	= $origin;
	if ($type == 2) $idCompanyOrigin 	= $origin;
	if ($type == 3) $idMemberOrigin 	= $origin;
}

if ($catorigin && $type == 0) $idCatOrigin = $catorigin;


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
		else
		{
			header("Location: ".DOL_URL_ROOT.'/categories/index.php?leftmenu=cat&type='.$type);
			exit;
		}
	}

	$object = new Categorie($db);

	$object->label			= $_POST["nom"];
	$object->description	= $_POST["description"];
	$object->socid			= ($_POST["socid"] ? $_POST["socid"] : 'null');
	$object->visible		= $_POST["visible"];
	$object->type			= $type;

	if($_POST['catMere'] != "-1") $object->id_mere = $_POST['catMere'];

	if (! $object->label)
	{
		$object->error = $langs->trans("ErrorFieldRequired",$langs->transnoentities("Ref"));
		$_GET["action"] = 'create';
	}

	// Create category in database
	if (! $object->error)
	{
		$result = $object->create();
		if ($result > 0)
		{
			$action = 'confirmed';
			$_POST["addcat"] = '';
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

		header("Location: ".DOL_URL_ROOT.'/categories/viewcat.php?id='.$result.'&type='.$type);
		exit;
	}
}


/*
 * View
 */

llxHeader("","",$langs->trans("Categories"));
$html = new Form($db);

if ($user->rights->categorie->creer)
{
	/*
	 * Fiche en mode creation
	 */
	if ($action == 'create' || $_POST["addcat"] == 'addcat')
	{
		print '<form action="'.$_SERVER['PHP_SELF'].'?type='.$type.'" method="POST">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="urlfrom" value="'.$urlfrom.'">';
		print '<input type="hidden" name="action" value="add">';
		print '<input type="hidden" name="addcat" value="addcat">';
		print '<input type="hidden" name="id" value="'.GETPOST('origin').'">';
		print '<input type="hidden" name="type" value="'.$type.'">';
		print '<input type="hidden" name="nom" value="'.dol_escape_htmltag($nom).'">';
		if ($origin) print '<input type="hidden" name="origin" value="'.$origin.'">';
		if ($catorigin)	print '<input type="hidden" name="catorigin" value="'.$catorigin.'">';

		print_fiche_titre($langs->trans("CreateCat"));

		dol_htmloutput_errors($object->error);

		print '<table width="100%" class="border">';

		// Ref
		print '<tr>';
		print '<td width="25%" class="fieldrequired">'.$langs->trans("Ref").'</td><td><input name="nom" size="25" value="'.$object->label.'">';
		print'</td></tr>';

		// Description
		print '<tr><td valign="top">'.$langs->trans("Description").'</td><td>';
		require_once(DOL_DOCUMENT_ROOT."/core/class/doleditor.class.php");
		$doleditor=new DolEditor('description',$object->description,'',200,'dolibarr_notes','',false,true,$conf->global->FCKEDITOR_ENABLE_PRODUCTDESC,ROWS_6,50);
		$doleditor->Create();
		print '</td></tr>';

		// Parent category
		print '<tr><td>'.$langs->trans("AddIn").'</td><td>';
		print $html->select_all_categories($type,$catorigin);
		print '</td></tr>';

		print '</table>';

		print '<center><br>';
		print '<input type="submit" class="button" value="'.$langs->trans("CreateThisCat").'" name="creation" />';
		print ' &nbsp; &nbsp; ';
		print '<input type="submit" class="button" value="'.$langs->trans("Cancel").'" name="cancel" />';
		print '</center>';

		print '</form>';
	}
}

$db->close();

llxFooter();
?>
