<?php
/* Copyright (C) 2001-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2007 Regis Houssin        <regis@dolibarr.fr>
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
 *       \file       htdocs/categories/photos.php
 *       \ingroup    category
 *       \brief      Gestion des photos d'une categorie
 *       \version    $Id$
 */

require("./pre.inc.php");

require_once(DOL_DOCUMENT_ROOT."/html.formfile.class.php");

$langs->load("category");
$langs->load("bills");

$mesg = '';
$type=$_REQUEST['type'];

// Security check
if (!$user->rights->categorie->lire) accessforbidden();

if ($_REQUEST['id'] == "")
{
	dol_print_error('','Missing parameter id');
	exit();
}


/*
 * Actions
 */

if ($_FILES['userfile']['size'] > 0 && $_POST["sendit"] && ! empty($conf->global->MAIN_UPLOAD_DOC))
{
	if ($_GET["id"])
	{
		$c = new Categorie($db);
		$result = $c->fetch($_GET["id"]);

		$result = $c->add_photo($conf->categorie->dir_output, $_FILES['userfile']);
	}
}


if ($_REQUEST["action"] == 'confirm_delete' && $_GET["file"] && $_REQUEST['confirm'] == 'yes' && $user->rights->categorie->creer)
{
	$c = new Categorie($db);
	$c->delete_photo($conf->categorie->dir_output."/".$_GET["file"]);
}

if ($_GET["action"] == 'addthumb' && $_GET["file"])
{
	$c = new Category($db);
	$c->add_thumb($conf->categorie->dir_output."/".$_GET["file"]);
}


/*
 * View
 */

llxHeader ("","",$langs->trans("Categories"));

$c = new Categorie($db);
$c->fetch($_REQUEST['id']);

$html = new Form($db);

if ($_GET["id"] || $_GET["ref"])
{
	$c = new Categorie($db);

	if ($_GET["id"]) $result = $c->fetch($_GET["id"]);


	if ($result)
	{

		$h = 0;
		$head = array();

		$head[$h][0] = DOL_URL_ROOT.'/categories/viewcat.php?id='.$c->id.'&amp;type='.$type;
		$head[$h][1] = $langs->trans("Card");
		$head[$h][2] = 'card';
		$h++;

		$head[$h][0] = DOL_URL_ROOT.'/categories/photos.php?id='.$c->id.'&amp;type='.$type;
		$head[$h][1] = $langs->trans("Photos");
		$head[$h][2] = 'photos';
		$h++;

		$title=$langs->trans("ProductsCategoryShort");
		if ($type == 0) $title=$langs->trans("ProductsCategoryShort");
		if ($type == 1) $title=$langs->trans("SuppliersCategoryShort");
		if ($type == 2) $title=$langs->trans("CustomersCategoryShort");

		dol_fiche_head($head, 'photos', $title);

		/*
		 * Confirmation de la suppression de photo
		 */
		if ($_GET['action'] == 'delete')
		{
			$ret=$html->form_confirm($_SERVER["PHP_SELF"].'?id='.$c->id.'&file='.$_GET["file"], $langs->trans('DeletePicture'), $langs->trans('ConfirmDeletePicture'), 'confirm_delete', '', 0, 1);
			if ($ret == 'html') print '<br>';
		}

		print($mesg);

		print '<table class="border" width="100%">';

		// Path of category
		print '<tr><td width="20%" class="notopnoleft">';
		$ways = $c->print_all_ways ();
		print $langs->trans("Ref").'</td><td>';
		print '<a href="'.DOL_URL_ROOT.'/categories/index.php?leftmenu=cat&type='.$type.'">'.$langs->trans("Root").'</a> >> ';
		foreach ($ways as $way)
		{
			print $way."<br />\n";
		}
		print '</td></tr>';

		// Description
		print '<tr><td width="20%" class="notopnoleft">';
		print $langs->trans("Description").'</td><td>';
		print nl2br($c->description);
		print '</td></tr>';

		// Visibility
		if ($type == 0 && $conf->global->CATEGORY_ASSIGNED_TO_A_CUSTOMER)
		{
			if ($c->socid)
			{
				$soc = new Societe($db);
				$soc->fetch($c->socid);

				print '<tr><td width="20%" class="notopnoleft">';
				print $langs->trans("AssignedToTheCustomer").'</td><td>';
				print $soc->getNomUrl(1);
				print '</td></tr>';

				$catsMeres = $c->get_meres ();

				if ($catsMeres < 0)
				{
					dol_print_error();
				}
				else if (count($catsMeres) > 0)
				{
					print '<tr><td width="20%" class="notopnoleft">';
					print $langs->trans("CategoryContents").'</td><td>';
					print ($c->visible ? $langs->trans("Visible") : $langs->trans("Invisible"));
					print '</td></tr>';
				}
			}
			else
			{
				print '<tr><td width="20%" class="notopnoleft">';
				print $langs->trans("CategoryContents").'</td><td>';
				print ($c->visible ? $langs->trans("Visible") : $langs->trans("Invisible"));
				print '</td></tr>';
			}
		}
		else
		{
			print '<tr><td width="20%" class="notopnoleft">';
			print $langs->trans("CategoryContents").'</td><td>';
			print ($c->visible ? $langs->trans("Visible") : $langs->trans("Invisible"));
			print '</td></tr>';
		}

		print "</table>\n";

		print "</div>\n";



		/* ************************************************************************** */
		/*                                                                            */
		/* Barre d'action                                                             */
		/*                                                                            */
		/* ************************************************************************** */

		print "\n<div class=\"tabsAction\">\n";

		if ($_GET["action"] != 'ajout_photo' && $user->rights->produit->creer)
		{
			if (! empty($conf->global->MAIN_UPLOAD_DOC))
			{
				print '<a class="butAction" href="'.DOL_URL_ROOT.'/categories/photos.php?action=ajout_photo&amp;id='.$c->id.'">';
				print $langs->trans("AddPhoto").'</a>';
			}
			else
			{
				print '<a class="butActionRefused" href="#">e';
				print $langs->trans("AddPhoto").'</a>';
			}
		}

		print "\n</div>\n";

		/*
		 * Ajouter une photo
		 */
		if ($_GET["action"] == 'ajout_photo' && $user->rights->categorie->creer && ! empty($conf->global->MAIN_UPLOAD_DOC))
		{
			// Affiche formulaire upload
			$formfile=new FormFile($db);
			$formfile->form_attach_new_file(DOL_URL_ROOT.'/categories/photos.php?id='.$c->id,$langs->trans("AddPhoto"),1);
		}

		// Affiche photos
		// Affiche photos
		if ($_GET["action"] != 'ajout_photo')
		{
			$nbphoto=0;
			$nbbyrow=5;

			$maxWidth = 160;
			$maxHeight = 120;

			$pdir = get_exdir($c->id,2) . $c->id ."/photos/";
			$dir = $conf->categorie->dir_output.'/'.$pdir;

			print '<br>';
			print '<table width="100%" valign="top" align="center" border="0" cellpadding="2" cellspacing="2">';

			foreach ($c->liste_photos($dir) as $key => $obj)
			{
				$nbphoto++;


				if ($nbbyrow && ($nbphoto % $nbbyrow == 1)) print '<tr align=center valign=middle border=1>';
				if ($nbbyrow) print '<td width="'.ceil(100/$nbbyrow).'%" class="photo">';

				print '<a href="'.DOL_URL_ROOT.'/viewimage.php?modulepart=categorie&file='.urlencode($pdir.$obj['photo']).'" alt="Taille origine" target="_blank">';

				// Si fichier vignette disponible, on l'utilise, sinon on utilise photo origine
				if ($obj['photo_vignette'])
				{
					$filename='thumbs/'.$obj['photo_vignette'];
				}
				else
				{
					$filename=$obj['photo'];
				}

				// Nom affiche
				$viewfilename=$obj['photo'];

				// Taille de l'image
				$c->get_image_size($dir.$filename);
				$imgWidth = ($c->imgWidth < $maxWidth) ? $c->imgWidth : $maxWidth;
				$imgHeight = ($c->imgHeight < $maxHeight) ? $c->imgHeight : $maxHeight;

				print '<img border="0" width="'.$imgWidth.'" height="'.$imgHeight.'" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=categorie&file='.urlencode($pdir.$filename).'">';

				print '</a>';
				print '<br>'.$viewfilename;
				print '<br>';

				// On propose la generation de la vignette si elle n'existe pas et si la taille est superieure aux limites
				if (!$obj['photo_vignette'] && eregi('(\.bmp|\.gif|\.jpg|\.jpeg|\.png)$',$obj['photo']) && ($c->imgWidth > $maxWidth || $c->imgHeight > $maxHeight))
				{
					print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$_GET["id"].'&amp;action=addthumb&amp;file='.urlencode($pdir.$viewfilename).'">'.img_refresh($langs->trans('GenerateThumb')).'&nbsp;&nbsp;</a>';
				}
				if ($user->rights->categorie->creer)
				{
					print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$_GET["id"].'&amp;action=delete&amp;file='.urlencode($pdir.$viewfilename).'">';
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
}
else
{
	print $langs->trans("ErrorUnknown");
}



$db->close();

llxFooter('$Date$ - $Revision$');
?>
