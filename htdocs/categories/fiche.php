<?php
/* Copyright (C) 2005      Matthieu Valleton    <mv@seeschloss.org>
 * Copyright (C) 2006-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010 Regis Houssin        <regis@dolibarr.fr>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *		\file       htdocs/categories/fiche.php
 *		\ingroup    category
 *		\brief      Page to create a new category
 *		\version	$Id$
 */

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/categories/class/categorie.class.php");

$langs->load("categories");


// Security check
$socid=GETPOST('socid');
if (!$user->rights->categorie->lire) accessforbidden();

if (GETPOST('choix'))
{
	$nbcats = GETPOST('choix');
}
else
{ // par default, une nouvelle categorie sera dans une seule categorie mere
	$nbcats = 1;
}

if (GETPOST('origin'))
{
	if ($_GET['type'] == 0) $idProdOrigin = GETPOST('origin');
	if ($_GET['type'] == 1) $idSupplierOrigin = GETPOST('origin');
	if ($_GET['type'] == 2) $idCompanyOrigin = GETPOST('origin');
	if ($_GET['type'] == 3) $idMemberOrigin = GETPOST('origin');
}

if (GETPOST('catorigin'))
{
	if ($_GET['type'] == 0) $idCatOrigin = GETPOST('catorigin');
}
$urlfrom=GETPOST("urlfrom");


/*
 *	Actions
 */

// Add action
if ($_POST["action"] == 'add' && $user->rights->categorie->creer)
{
	// Action ajout d'une categorie
	if ($_POST["cancel"])
	{
		if ($urlfrom)
		{
			header("Location: ".$urlfrom);
			exit;
		}
		else if ($idProdOrigin)
		{
			header("Location: ".DOL_URL_ROOT.'/categories/categorie.php?id='.$idProdOrigin.'&type='.$_GET["type"]);
			exit;
		}
		else if ($idCompanyOrigin)
		{
			header("Location: ".DOL_URL_ROOT.'/categories/categorie.php?socid='.$idCompanyOrigin.'&type='.$_GET["type"]);
			exit;
		}
		else if ($idSupplierOrigin)
		{
			header("Location: ".DOL_URL_ROOT.'/categories/categorie.php?socid='.$idSupplierOrigin.'&type='.$_GET["type"]);
			exit;
		}
		else if ($idMemberOrigin)
		{
			header("Location: ".DOL_URL_ROOT.'/categories/viewcat.php?id='.$idMemberOrigin.'&type='.$_GET["type"]);
			exit;
		}
		else if ($idCatOrigin)
		{
			header("Location: ".DOL_URL_ROOT.'/categories/viewcat.php?id='.$idCatOrigin.'&type='.$_GET["type"]);
			exit;
		}
		else
		{
			header("Location: ".DOL_URL_ROOT.'/categories/index.php?leftmenu=cat&type='.$_GET["type"]);
			exit;
		}
	}

	$categorie = new Categorie($db);

	$categorie->label          = $_POST["nom"];
	$categorie->description    = $_POST["description"];
	$categorie->socid          = ($_POST["socid"] ? $_POST["socid"] : 'null');
	$categorie->visible        = $_POST["visible"];
	$categorie->type		   = $_POST["type"];

	if($_POST['catMere'] != "-1")
	$categorie->id_mere = $_POST['catMere'];

	if (! $categorie->label)
	{
		$categorie->error = $langs->trans("ErrorFieldRequired",$langs->transnoentities("Ref"));
		$_GET["action"] = 'create';
	}

	// Create category in database
	if (! $categorie->error)
	{
		$result = $categorie->create();
		if ($result > 0)
		{
			$_GET["action"] = 'confirmed';
			$_POST["addcat"] = '';
		}
	}
}

// Confirm action
if ($_POST["action"] == 'add' && $user->rights->categorie->creer)
{
	// Action confirmation de creation categorie
	if ($_GET["action"] == 'confirmed')
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
			header("Location: ".DOL_URL_ROOT.'/categories/viewcat.php?id='.$idMemberOrigin.'&type='.$_GET["type"]);
			exit;
		}
		else if ($idCatOrigin)
		{
			header("Location: ".DOL_URL_ROOT.'/categories/viewcat.php?id='.$idCatOrigin.'&mesg='.urlencode($langs->trans("CatCreated")));
			exit;
		}

		header("Location: ".DOL_URL_ROOT.'/categories/viewcat.php?id='.$result.'&type='.$_POST["type"]);
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
	if ($_GET["action"] == 'create' || $_POST["addcat"] == 'addcat')
	{
		print '<form action="'.$_SERVER['PHP_SELF'].'?type='.$_GET['type'].'" method="post">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="urlfrom" value="'.$urlfrom.'">';
		print '<input type="hidden" name="action" value="add">';
		print '<input type="hidden" name="addcat" value="addcat">';
		print '<input type="hidden" name="id" value="'.GETPOST('origin').'">';
		print '<input type="hidden" name="type" value="'.$_GET['type'].'">';
		if (GETPOST('origin'))
		{
			print '<input type="hidden" name="origin" value="'.GETPOST('origin').'">';
		}
		if (GETPOST('catorigin'))
		{
			print '<input type="hidden" name="catorigin" value="'.GETPOST('catorigin').'">';
		}
		print '<input type="hidden" name="nom" value="'.dol_escape_htmltag($nom).'">';

		print_fiche_titre($langs->trans("CreateCat"));

		if ($categorie->error)
		{
			print '<div class="error">';
			print $categorie->error;
			print '</div>';
		}

		print '<table width="100%" class="border">';

		// Ref
		print '<tr>';
		print '<td width="25%" class="fieldrequired">'.$langs->trans("Ref").'</td><td><input name="nom" size="25" value="'.$categorie->label.'">';
		print'</td></tr>';

		// Description
		print '<tr><td valign="top">'.$langs->trans("Description").'</td><td>';
		require_once(DOL_DOCUMENT_ROOT."/lib/doleditor.class.php");
		$doleditor=new DolEditor('description',$categorie->description,200,'dolibarr_notes','',false,true,$conf->fckeditor->enabled && $conf->global->FCKEDITOR_ENABLE_PRODUCTDESC,ROWS_6,50);
		$doleditor->Create();
		print '</td></tr>';

		// Parent category
		print '<tr><td>'.$langs->trans ("AddIn").'</td><td>';
		print $html->select_all_categories($_GET['type'],GETPOST('catorigin'));
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

llxFooter('$Date$ - $Revision$');
?>
