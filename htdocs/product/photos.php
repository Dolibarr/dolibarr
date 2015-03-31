<?php
/* Copyright (C) 2001-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2014 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
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
 *	\file       htdocs/product/photos.php
 *	\ingroup    product
 *	\brief      Onglet photos de la fiche produit
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';

$langs->load("products");
$langs->load("bills");

$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'alpha');
$confirm = GETPOST('confirm', 'alpha');

// Security check
$fieldvalue = (! empty($id) ? $id : (! empty($ref) ? $ref : ''));
$fieldtype = (! empty($ref) ? 'ref' : 'rowid');
if ($user->societe_id) $socid=$user->societe_id;
$result=restrictedArea($user,'produit|service',$fieldvalue,'product&product','','',$fieldtype);

$mesg = '';

$object = new Product($db);
if ($id > 0 || ! empty($ref))
{
	$result = $object->fetch($id, $ref);
	$dir = (! empty($conf->product->multidir_output[$object->entity])?$conf->product->multidir_output[$object->entity]:$conf->service->multidir_output[$object->entity]);
}


/*
 * Actions
 */

if (isset($_FILES['userfile']) && $_FILES['userfile']['size'] > 0 && GETPOST('sendit') && ! empty($conf->global->MAIN_UPLOAD_DOC))
{
	if ($object->id)
	{
		if (image_format_supported($_FILES['userfile']['name']) >= 1)
		{
			$result = $object->add_photo($dir, $_FILES['userfile']);
			if ($result > 0) setEventMessage($langs->trans("FileUploaded"));
			else setEventMessage($langs->trans("FileNotUploaded"), 'errors');
		}
		else
		{
			$langs->load("errors");
			setEventMessage($langs->trans("ErrorBadImageFormat"), 'errors');
		}
	}
}

if ($action == 'confirm_delete' && $_GET["file"] && $confirm == 'yes' && ($user->rights->produit->creer || $user->rights->service->creer))
{
	$object->delete_photo($dir."/".$_GET["file"]);
}

if ($action == 'addthumb' && $_GET["file"])
{
	$object->add_thumb($dir."/".$_GET["file"]);
}


/*
 *	View
 */

$form = new Form($db);

if ($object->id)
{
	llxHeader("","",$langs->trans("CardProduct".$object->type));

	/*
	 *  En mode visu
	*/
	$head=product_prepare_head($object, $user);
	$titre=$langs->trans("CardProduct".$object->type);
	$picto=($object->type== Product::TYPE_SERVICE?'service':'product');
	dol_fiche_head($head, 'photos', $titre, 0, $picto);

	/*
	 * Confirmation de la suppression de photo
	*/
	if ($action == 'delete')
	{
		print $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&file='.$_GET["file"], $langs->trans('DeletePicture'), $langs->trans('ConfirmDeletePicture'), 'confirm_delete', '', 0, 1);

	}

	print($mesg);

	print '<table class="border" width="100%">';

	// Reference
	print '<tr>';
	print '<td width="15%">'.$langs->trans("Ref").'</td><td colspan="2">';
	print $form->showrefnav($object,'ref','',1,'ref');
	print '</td>';
	print '</tr>';

	// Libelle
	print '<tr><td>'.$langs->trans("Label").'</td><td colspan="2">'.$object->libelle.'</td>';
	print '</tr>';

	// Status (to sell)
	print '<tr><td>'.$langs->trans("Status").' ('.$langs->trans("Sell").')</td><td>';
	print $object->getLibStatut(2,0);
	print '</td></tr>';

	// Status (to buy)
	print '<tr><td>'.$langs->trans("Status").' ('.$langs->trans("Buy").')</td><td>';
	print $object->getLibStatut(2,1);
	print '</td></tr>';

	print "</table>\n";

	print "</div>\n";


	$permtoedit=0;
	if ($user->rights->produit->creer && $object->type == Product::TYPE_PRODUCT) $permtoedit=1;
	if ($user->rights->service->creer && $object->type == Product::TYPE_SERVICE) $permtoedit=1;
	if (empty($conf->global->MAIN_UPLOAD_DOC)) $permtoedit=0;

	/* ************************************************************************** */
	/*                                                                            */
	/* Barre d'action                                                             */
	/*                                                                            */
	/* ************************************************************************** */
/*
	print "\n<div class=\"tabsAction\">\n";

	if ($action != 'ajout_photo' && ($user->rights->produit->creer || $user->rights->service->creer))
	{
		if (! empty($conf->global->MAIN_UPLOAD_DOC))
		{
			print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=ajout_photo&amp;id='.$object->id.'">';
			print $langs->trans("AddPhoto").'</a>';
		}
		else
		{
			print '<a class="butActionRefused" href="#">e';
			print $langs->trans("AddPhoto").'</a>';
		}
	}

	print "\n</div>\n";
*/
	/*
	 * Add a photo
	 */
	if ($permtoedit)
	{
		// Show upload form
		$formfile=new FormFile($db);
		$formfile->form_attach_new_file($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans("AddPhoto"), 1, 0, $permtoedit, 50, $object, '', false, '', 0); // ajax=false for the moment. true may not work.
	}
	//else print $langs->trans("NoPermissionToAddOrEditPhoto").'<br><br>';	// TODO Add this

	// Affiche photos
	if ($action != 'ajout_photo')
	{
		$nbphoto=0;
		$nbbyrow=5;

		$maxWidth = 160;
		$maxHeight = 120;

		print $object->show_photos($dir,1,1000,$nbbyrow,1,($permtoedit?1:0));

		if ($object->nbphoto < 1)
		{
			print '<table width="100%" valign="top" align="center" border="0" cellpadding="2" cellspacing="2">';
			print '<tr align=center valign=middle border=1><td class="photo">';
			print "<br>".$langs->trans("NoPhotoYet")."<br><br>";
			print '</td></tr>';
			print '</table>';
		}
	}
}
else
{
	print $langs->trans("ErrorUnknown");
}


llxFooter();
$db->close();
