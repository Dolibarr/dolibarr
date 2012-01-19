<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *  \file       htdocs/fourn/product/photos.php
 *  \ingroup    product
 *  \brief      Page de la fiche produit
 */

require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");


if (!$user->rights->produit->lire && !$user->rights->service->lire) accessforbidden();


/*
 *	View
 */

if ( $_POST["sendit"] && ! empty($conf->global->MAIN_UPLOAD_DOC))
{
	if ($_GET["id"])
	{
		$product = new Product($db);
		$result = $product->fetch($_GET["id"]);

		$product->add_photo($conf->product->dir_output, $_FILES['photofile']);
	}
}
/*
 *
 */
llxHeader("","",$langs->trans("CardProduct0"));

/*
 * Fiche produit
 */
if ($_GET["id"])
{
	$product = new Product($db);
	$result = $product->fetch($_GET["id"]);

	if ( $result )
	{
		/*
		 *  En mode visu
		 */

		$h=0;

		$head[$h][0] = DOL_URL_ROOT."/fourn/product/fiche.php?id=".$product->id;
		$head[$h][1] = $langs->trans("Card");
		$h++;


		if ($conf->stock->enabled)
		{
	  $head[$h][0] = DOL_URL_ROOT."/product/stock/product.php?id=".$product->id;
	  $head[$h][1] = $langs->trans("Stock");
	  $h++;
		}

		$head[$h][0] = DOL_URL_ROOT."/fourn/product/photos.php?id=".$product->id;
		$head[$h][1] = $langs->trans("Photos");
		$hselected = $h;
		$h++;

		//Affichage onglet Cat�gories
		if ($conf->categorie->enabled){
			$head[$h][0] = DOL_URL_ROOT."/fourn/product/categorie.php?id=".$product->id;
			$head[$h][1] = $langs->trans('Categories');
			$h++;
		}

		$head[$h][0] = DOL_URL_ROOT."/product/fiche.php?id=".$product->id;
		$head[$h][1] = $langs->trans("CommercialCard");
		$h++;

		dol_fiche_head($head, $hselected, $langs->trans("CardProduct".$product->type).' : '.$product->ref);

		print($mesg);
		print '<table class="border" width="100%">';
		print "<tr>";
		print '<td>'.$langs->trans("Ref").'</td><td>'.$product->ref.'</td>';
		print '<td colspan="2">';
		print $product->getLibStatut(2);
		print '</td></tr>';
		print '<tr><td>'.$langs->trans("Label").'</td><td>'.$product->libelle.'</td>';
		print '<td>'.$langs->trans("SellingPrice").'</td><td>'.price($product->price).'</td></tr>';
		print "</table><br>\n";

		/*
		 * Ajouter une photo
		 *
		 */
		if ($_GET["action"] == 'ajout_photo' && ($user->rights->produit->creer || $user->rights->service->creer) && ! empty($conf->global->MAIN_UPLOAD_DOC))
		{
			print_titre($langs->trans("AddPhoto"));

			print '<form name="userfile" action="photos.php?id='.$product->id.'" enctype="multipart/form-data" METHOD="POST">';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print '<input type="hidden" name="max_file_size" value="'.$conf->maxfilesize.'">';

			print '<table class="border" width="100%"><tr>';
			print '<td>'.$langs->trans("File").'</td>';
			print '<td><input type="file" name="photofile"></td></tr>';

			print '<tr><td colspan="4" align="center">';
			print '<input type="submit" name="sendit" value="'.$langs->trans("Save").'">&nbsp;';


			print '<input type="submit" name="cancel" value="'.$langs->trans("Cancel").'"></td></tr>';
			print '</table>';
			print '</form><br>';
		}


		// Affiche photos
		if ($_GET["action"] != 'ajout_photo')
		{
			$nbphoto=0;
			$nbbyrow=5;

			$pdir = get_exdir($product->id,2) . $product->id ."/photos/";
			$dir = $conf->product->dir_output . '/'. $pdir;

			print '<br><table width="100%" valign="top" align="center" border="0" cellpadding="2" cellspacing="2">';

			foreach ($product->liste_photos($dir) as $obj)
			{
				$nbphoto++;

				//                if ($nbbyrow && $nbphoto == 1) print '<table width="100%" valign="top" align="center" border="0" cellpadding="2" cellspacing="2">';

				if ($nbbyrow && ($nbphoto % $nbbyrow == 1)) print '<tr align=center valign=middle border=1>';
				if ($nbbyrow) print '<td width="'.ceil(100/$nbbyrow).'%" class="photo">';

				print '<a href="'.DOL_URL_ROOT.'/viewimage.php?modulepart=product&file='.urlencode($pdir.$obj['photo']).'" alt="Taille origine" target="_blank">';

				// Si fichier vignette disponible, on l'utilise, sinon on utilise photo origine
				if ($obj['photo_vignette']) $filename=$obj['photo_vignette'];
				else $filename=$obj['photo'];
				print '<img border="0" height="120" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=product&file='.urlencode($pdir.$filename).'">';

				print '</a>';
				print '<br>'.$langs->trans("File").': '.dol_trunc($filename,16);
				if ($user->rights->produit->creer || $user->rights->service->creer)
				{
					print '<br><a href="'.$_SERVER["PHP_SELF"].'?id='.$_GET["id"].'&amp;action=delete&amp;file='.urlencode($pdir.$filename).'">'.img_delete().'</a>';
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
				print '</td></tr></table>';
			}

			print '</table>';
		}


		print "</div>\n";

	}

	print "\n<div class=\"tabsAction\">\n";

	if ($_GET["action"] == '')
	{
		if (($user->rights->produit->creer || $user->rights->service->creer) && ! empty($conf->global->MAIN_UPLOAD_DOC))
		{
			print '<a class="butAction" href="photos.php?action=ajout_photo&amp;id='.$product->id.'">';
			print $langs->trans("AddPhoto").'</a>';
		}
	}

	print "\n</div>\n";

}
else
{
	print $langs->trans("ErrorUnknown");
}



$db->close();

llxFooter();
?>
