<?php
/* Copyright (C) 2001-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2010 Regis Houssin        <regis@dolibarr.fr>
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
 *	\file       htdocs/product/photos.php
 *	\ingroup    product
 *	\brief      Onglet photos de la fiche produit
 *	\version    $Id$
 */

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/product.lib.php");
require_once(DOL_DOCUMENT_ROOT."/product.class.php");
require_once(DOL_DOCUMENT_ROOT."/html.formfile.class.php");

$langs->load("products");
$langs->load("bills");

// Security check
if (isset($_GET["id"]) || isset($_GET["ref"]))
{
	$id = isset($_GET["id"])?$_GET["id"]:(isset($_GET["ref"])?$_GET["ref"]:'');
}
$fieldid = isset($_GET["ref"])?'ref':'rowid';
if ($user->societe_id) $socid=$user->societe_id;
$result=restrictedArea($user,'produit|service',$id,'product','','',$fieldid);

$mesg = '';
$dir = (!empty($conf->produit->dir_output)?$conf->produit->dir_output:$conf->service->dir_output);

/*
 * Actions
 */

if ($_FILES['userfile']['size'] > 0 && $_POST["sendit"] && ! empty($conf->global->MAIN_UPLOAD_DOC))
{
	if ($_GET["id"])
	{
		$product = new Product($db);
		$result = $product->fetch($_GET["id"]);

		$result = $product->add_photo($dir, $_FILES['userfile']);
	}
}

if ($_REQUEST["action"] == 'confirm_delete' && $_GET["file"] && $_REQUEST['confirm'] == 'yes' && ($user->rights->produit->creer || $user->rights->service->creer))
{
	$product = new Product($db);
	$product->delete_photo($dir."/".$_GET["file"]);
}

if ($_GET["action"] == 'addthumb' && $_GET["file"])
{
	$product = new Product($db);
	$product->add_thumb($dir."/".$_GET["file"]);
}


/*
 *	View
 */

$html = new Form($db);

if ($_GET["id"] || $_GET["ref"])
{
	$product = new Product($db);
	
	if ($_GET["ref"]) $result = $product->fetch('',$_GET["ref"]);
	if ($_GET["id"]) $result = $product->fetch($_GET["id"]);

	llxHeader("","",$langs->trans("CardProduct".$product->type));

	if ($result)
	{
		/*
		 *  En mode visu
		 */
		$head=product_prepare_head($product, $user);
		$titre=$langs->trans("CardProduct".$product->type);
		$picto=($product->type==1?'service':'product');
		dol_fiche_head($head, 'photos', $titre, 0, $picto);

		/*
		 * Confirmation de la suppression de photo
		 */
		if ($_GET['action'] == 'delete')
		{
			$ret=$html->form_confirm($_SERVER["PHP_SELF"].'?id='.$product->id.'&file='.$_GET["file"], $langs->trans('DeletePicture'), $langs->trans('ConfirmDeletePicture'), 'confirm_delete', '', 0, 1);
			if ($ret == 'html') print '<br>';
		}

		print($mesg);

		print '<table class="border" width="100%">';

		// Reference
		print '<tr>';
		print '<td width="15%">'.$langs->trans("Ref").'</td><td colspan="2">';
		print $html->showrefnav($product,'ref','',1,'ref');
		print '</td>';
		print '</tr>';

		// Libelle
		print '<tr><td>'.$langs->trans("Label").'</td><td colspan="2">'.$product->libelle.'</td>';
		print '</tr>';

		// Prix
		print '<tr><td>'.$langs->trans("SellingPrice").'</td><td colspan="2">';
		if ($product->price_base_type == 'TTC')
		{
			print price($product->price_ttc).' '.$langs->trans($product->price_base_type);
		}
		else
		{
			print price($product->price).' '.$langs->trans($product->price_base_type);
		}
		print '</td></tr>';

		// Statut
		print '<tr><td>'.$langs->trans("Status").'</td><td colspan="2">';
		print $product->getLibStatut(2);
		print '</td></tr>';

		print "</table>\n";

		print "</div>\n";



		/* ************************************************************************** */
		/*                                                                            */
		/* Barre d'action                                                             */
		/*                                                                            */
		/* ************************************************************************** */

		print "\n<div class=\"tabsAction\">\n";

		if ($_GET["action"] != 'ajout_photo' && ($user->rights->produit->creer || $user->rights->service->creer))
		{
			if (! empty($conf->global->MAIN_UPLOAD_DOC))
			{
				print '<a class="butAction" href="'.DOL_URL_ROOT.'/product/photos.php?action=ajout_photo&amp;id='.$product->id.'">';
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
		 * Add a photo
		 */
		if ($_GET["action"] == 'ajout_photo' && ($user->rights->produit->creer || $user->rights->service->creer) && ! empty($conf->global->MAIN_UPLOAD_DOC))
		{
			// Affiche formulaire upload
			$formfile=new FormFile($db);
			$formfile->form_attach_new_file(DOL_URL_ROOT.'/product/photos.php?id='.$product->id,$langs->trans("AddPhoto"),1);
		}

		// Affiche photos
		if ($_GET["action"] != 'ajout_photo')
		{
			$nbphoto=0;
			$nbbyrow=5;

			$maxWidth = 160;
			$maxHeight = 120;

			$pdir = get_exdir($product->id,2) . $product->id ."/photos/";
			$dir = $dir . '/'. $pdir;

			print '<br>';
			print '<table width="100%" valign="top" align="center" border="0" cellpadding="2" cellspacing="2">';

			foreach ($product->liste_photos($dir) as $key => $obj)
			{
				$nbphoto++;

				//                if ($nbbyrow && $nbphoto == 1) print '<table width="100%" valign="top" align="center" border="0" cellpadding="2" cellspacing="2">';

				if ($nbbyrow && ($nbphoto % $nbbyrow == 1)) print '<tr align=center valign=middle border=1>';
				if ($nbbyrow) print '<td width="'.ceil(100/$nbbyrow).'%" class="photo">';

				print '<a href="'.DOL_URL_ROOT.'/viewimage.php?modulepart=product&file='.urlencode($pdir.$obj['photo']).'" alt="'.dol_escape_htmltag($langs->trans("OriginalSize")).'" target="_blank">';

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
				$product->get_image_size($dir.$filename);
				$imgWidth = ($product->imgWidth < $maxWidth) ? $product->imgWidth : $maxWidth;
				$imgHeight = ($product->imgHeight < $maxHeight) ? $product->imgHeight : $maxHeight;

				print '<img border="0" width="'.$imgWidth.'" height="'.$imgHeight.'" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=product&file='.urlencode($pdir.$filename).'">';

				print '</a>';
				print '<br>'.$viewfilename;
				print '<br>';

				// On propose la generation de la vignette si elle n'existe pas et si la taille est superieure aux limites
				if (!$obj['photo_vignette'] && preg_match('/(\.bmp|\.gif|\.jpg|\.jpeg|\.png)$/i',$obj['photo']) && ($product->imgWidth > $maxWidth || $product->imgHeight > $maxHeight))
				{
					print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$_GET["id"].'&amp;action=addthumb&amp;file='.urlencode($pdir.$viewfilename).'">'.img_refresh($langs->trans('GenerateThumb')).'&nbsp;&nbsp;</a>';
				}
				if ($user->rights->produit->creer || $user->rights->service->creer)
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
