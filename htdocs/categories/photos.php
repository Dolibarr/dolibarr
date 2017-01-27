<?php
/* Copyright (C) 2001-2007  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005       Eric Seigne             <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2012  Regis Houssin           <regis.houssin@capnetworks.com>
 * Copyright (C) 2014       Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2015       Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
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
 *       \file       htdocs/categories/photos.php
 *       \ingroup    category
 *       \brief      Gestion des photos d'une categorie
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/categories.lib.php';

$langs->load("categories");
$langs->load("bills");


$id=GETPOST('id','int');
$ref=GETPOST('ref');
$type=GETPOST('type');
$action=GETPOST('action');
$confirm=GETPOST('confirm');

if ($id == "")
{
    dol_print_error('','Missing parameter id');
    exit();
}

// Security check
$result = restrictedArea($user, 'categorie', $id, '&category');

$object = new Categorie($db);
if ($id > 0)
{
	$result = $object->fetch($id);

	$upload_dir = $conf->categorie->multidir_output[$object->entity];
}


/*
 * Actions
 */

if (isset($_FILES['userfile']) && $_FILES['userfile']['size'] > 0 && $_POST["sendit"] && ! empty($conf->global->MAIN_UPLOAD_DOC))
{
    if ($object->id) {
	    $object->add_photo($upload_dir, $_FILES['userfile']);
    }
}

if ($action == 'confirm_delete' && $_GET["file"] && $confirm == 'yes' && $user->rights->categorie->creer)
{
    $object->delete_photo($upload_dir."/".$_GET["file"]);
}

if ($action == 'addthumb' && $_GET["file"])
{
    $object->addThumbs($upload_dir."/".$_GET["file"]);
}


/*
 * View
 */

llxHeader("","",$langs->trans("Categories"));

$form = new Form($db);
$formother = new FormOther($db);

if ($object->id)
{
	if ($type == Categorie::TYPE_PRODUCT)       $title=$langs->trans("ProductsCategoryShort");
	elseif ($type == Categorie::TYPE_SUPPLIER)  $title=$langs->trans("SuppliersCategoryShort");
	elseif ($type == Categorie::TYPE_CUSTOMER)  $title=$langs->trans("CustomersCategoryShort");
	elseif ($type == Categorie::TYPE_MEMBER)    $title=$langs->trans("MembersCategoryShort");
	elseif ($type == Categorie::TYPE_CONTACT)   $title=$langs->trans("ContactCategoriesShort");
	elseif ($type == Categorie::TYPE_ACCOUNT)   $title=$langs->trans("AccountsCategoriesShort");
	elseif ($type == Categorie::TYPE_PROJECT)   $title=$langs->trans("ProjectsCategoriesShort");
    else                                        $title=$langs->trans("Category");

	$head = categories_prepare_head($object,$type);


	dol_fiche_head($head, 'photos', $title, 0, 'category');
	
	$linkback = '<a href="'.DOL_URL_ROOT.'/categories/index.php?leftmenu=cat&type='.$type.'">'.$langs->trans("BackToList").'</a>';
	
	$object->ref = $object->label;
	$morehtmlref='<br><div class="refidno"><a href="'.DOL_URL_ROOT.'/categories/index.php?leftmenu=cat&type='.$type.'">'.$langs->trans("Root").'</a> >> ';
	$ways = $object->print_all_ways(" &gt;&gt; ", '', 1);
	foreach ($ways as $way)
	{
	    $morehtmlref.=$way."<br>\n";
	}
	$morehtmlref.='</div>';
	
	dol_banner_tab($object, 'ref', $linkback, ($user->societe_id?0:1), 'ref', 'ref', $morehtmlref, '', 0, '', '', 1);
	
	/*
	 * Confirmation de la suppression de photo
	*/
	if ($action == 'delete')
	{
	    print $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&type='.$type.'&file='.$_GET["file"], $langs->trans('DeletePicture'), $langs->trans('ConfirmDeletePicture'), 'confirm_delete', '', 0, 1);
	}

	print '<br>';
	
	print '<div class="underbanner clearboth"></div>';
	print '<table class="border" width="100%">';

	// Description
	print '<tr><td class="titlefield notopnoleft">';
	print $langs->trans("Description").'</td><td>';
	print dol_htmlentitiesbr($object->description);
	print '</td></tr>';

	// Color
	print '<tr><td class="notopnoleft">';
	print $langs->trans("Color").'</td><td>';
	print $formother->showColor($object->color);
	print '</td></tr>';

	print "</table>\n";

	print dol_fiche_end();



	/* ************************************************************************** */
	/*                                                                            */
	/* Barre d'action                                                             */
	/*                                                                            */
	/* ************************************************************************** */

	print '<div class="tabsAction">'."\n";

	if ($action != 'ajout_photo' && $user->rights->categorie->creer)
	{
		if (! empty($conf->global->MAIN_UPLOAD_DOC))
		{
			print '<a class="butAction hideonsmartphone" href="'.$_SERVER['PHP_SELF'].'?action=ajout_photo&amp;id='.$object->id.'&amp;type='.$type.'">';
			print $langs->trans("AddPhoto").'</a>';
		}
		else
		{
			print '<a class="butActionRefused hideonsmartphone" href="#">';
			print $langs->trans("AddPhoto").'</a>';
		}
	}

	print '</div>'."\n";

	/*
	 * Ajouter une photo
	*/
	if ($action == 'ajout_photo' && $user->rights->categorie->creer && ! empty($conf->global->MAIN_UPLOAD_DOC))
	{
		// Affiche formulaire upload
		$formfile=new FormFile($db);
		$formfile->form_attach_new_file($_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;type='.$type, $langs->trans("AddPhoto"), 1, '', $user->rights->categorie->creer, 50, $object, '', false, '', 0);
	}

	// Affiche photos
	if ($action != 'ajout_photo')
	{
		$nbphoto=0;
		$nbbyrow=5;

		$maxWidth = 160;
		$maxHeight = 120;

		$pdir = get_exdir($object->id,2,0,0,$object,'category') . $object->id ."/photos/";
		$dir = $upload_dir.'/'.$pdir;

		print '<br>';
		print '<table width="100%" valign="top" align="center" border="0" cellpadding="2" cellspacing="2">';

		foreach ($object->liste_photos($dir) as $key => $obj)
		{
			$nbphoto++;


			if ($nbbyrow && ($nbphoto % $nbbyrow == 1)) print '<tr align=center valign=middle border=1>';
			if ($nbbyrow) print '<td width="'.ceil(100/$nbbyrow).'%" class="photo">';

			print '<a href="'.DOL_URL_ROOT.'/viewimage.php?modulepart=category&entity='.$object->entity.'&file='.urlencode($pdir.$obj['photo']).'" alt="Taille origine" target="_blank">';

			// Si fichier vignette disponible, on l'utilise, sinon on utilise photo origine
			if ($obj['photo_vignette'])
			{
				$filename=$obj['photo_vignette'];
			}
			else
			{
				$filename=$obj['photo'];
			}

			// Nom affiche
			$viewfilename=$obj['photo'];

			// Taille de l'image
			$object->get_image_size($dir.$filename);
			$imgWidth = ($object->imgWidth < $maxWidth) ? $object->imgWidth : $maxWidth;
			$imgHeight = ($object->imgHeight < $maxHeight) ? $object->imgHeight : $maxHeight;

			print '<img border="0" width="'.$imgWidth.'" height="'.$imgHeight.'" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=category&entity='.$object->entity.'&file='.urlencode($pdir.$filename).'">';

			print '</a>';
			print '<br>'.$viewfilename;
			print '<br>';

			// On propose la generation de la vignette si elle n'existe pas et si la taille est superieure aux limites
			if (!$obj['photo_vignette'] && preg_match('/(\.bmp|\.gif|\.jpg|\.jpeg|\.png)$/i',$obj['photo']) && ($object->imgWidth > $maxWidth || $object->imgHeight > $maxHeight))
			{
				print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=addthumb&amp;type='.$type.'&amp;file='.urlencode($pdir.$viewfilename).'">'.img_picto($langs->trans('GenerateThumb'),'refresh').'&nbsp;&nbsp;</a>';
			}
			if ($user->rights->categorie->creer)
			{
				print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=delete&amp;type='.$type.'&amp;file='.urlencode($pdir.$viewfilename).'">';
				print img_delete().'</a>';
			}
			if ($nbbyrow) print '</td>';
			if ($nbbyrow && ($nbphoto % $nbbyrow == 0)) print '</tr>';
		}

		// Ferme tableau
		while ($nbphoto % $nbbyrow)
		{
			print '<td width="'.ceil(100/$nbbyrow).'%">&nbsp;</td>';
			$nbphoto++;
		}

		if ($nbphoto < 1)
		{
			print '<tr align=center valign=middle border=1><td class="photo">';
			print "<br>".$langs->trans("NoPhotoYet")."<br><br>";
			print '</td></tr>';
		}

		print '</table>';
	}
}
else
{
    print $langs->trans("ErrorUnknown");
}


llxFooter();
$db->close();
