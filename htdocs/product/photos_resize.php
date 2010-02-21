<?php
/* Copyright (C) 2001-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
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

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/product.lib.php");
require_once(DOL_DOCUMENT_ROOT."/lib/images.lib.php");
require_once(DOL_DOCUMENT_ROOT."/product.class.php");

$langs->load("products");

// Security check
if (isset($_GET["id"]))
{
	$id = isset($_GET["id"])?$_GET["id"]:'';
}
$result=restrictedArea($user,'produit|service',$id,'product','','',$fieldid);


$original_file = isset($_GET["file"])?urldecode($_GET["file"]):'';

$langs->load("products");

if (!$user->rights->produit->lire) accessforbidden();



/*
 * Actions
 */

if ($_POST["action"] == 'confirm_resize' && (isset($_POST["file"]) != "") && (isset($_POST["sizex"]) != "") &&(isset($_POST["sizey"]) != ""))
{
/*	$thumb = new Imagick($conf->produit->dir_output."/".$_POST["file"]);
	$height=$thumb->getImageHeight();	// dimensions de l'image actuelle
	$width=$thumb->getImageWidth();		// dimensions de l'image actuelle

	if($_POST["sizex"] != "")
	{
		if ($width > $_POST['sizex'])
		$thumb->scaleImage(intval($_POST['sizex']), 0);
	}
	if($_POST["sizey"] != "")
	{
		if ($height > $_POST['sizey'])
		$thumb->scaleImage(0, intval($_POST['sizey']));
	}
	$thumb->writeImage($conf->produit->dir_output."/".$_POST["file"]);
	$thumb->destroy();
*/
	header("Location: ".DOL_URL_ROOT."/product/photos.php?id=".$_POST["product"].'&action=addthumb&file='.urldecode($_POST["file"]));
	exit;
}

// Crop d'une image
if ($_POST["action"] == 'confirm_crop' && $_POST["file"])
{
/*	$thumb = new Imagick($conf->produit->dir_output."/".urldecode($_POST["file"]));
	$thumb->cropImage($_POST['w'], $_POST['h'], $_POST['x'], $_POST['y']);
	$thumb->writeImage($conf->produit->dir_output."/".urldecode($_POST["file"]));
	$thumb->destroy();
*/
	header("Location: ".DOL_URL_ROOT."/product/photos.php?id=".$_POST["product"].'&action=addthumb&file='.urldecode($_POST["file"]));
	exit;
}


/*
 * View
 */

llxHeader($head, $langs->trans("Resize"), '', '', 0, 0, array('includes/jcrop/js/jquery.min.js','includes/jcrop/js/jquery.Jcrop.min.js','lib/lib_photosresize.js'), array(DOL_URL_ROOT.'/includes/jcrop/css/jquery.Jcrop.css'));


print_fiche_titre($langs->trans("Current"));

$infoarray=dol_getImageSize($conf->produit->dir_output."/".urldecode($_GET["file"]));
$height=$infoarray['height'];
$width=$infoarray['width'];
print $langs->trans("Size").': </p>
   <ul>
   <li>'.$langs->trans("Length").': '.$width.' px</li>
   <li>'.$langs->trans("Width").': '.$height.' px</li>
   </ul>';

print '<br>';
print_fiche_titre($langs->trans("Resize"),'','');

print '<form name="redim_file" action="'.DOL_URL_ROOT.'/product/photos_resize.php?id='.$_GET['id'].'" method="post">';
print 'Entrer la nouvelle largeur <strong>OU</strong> la nouvelle hauteur. Le ratio est conservé lors du redimensionnement...<br>';
print 'Nouvelle largeur : <input class="flat" name="sizex" size="10" type="text" > px <br /> ';
print 'Nouvelle hauteur : <input class="flat" name="sizey" size="10" type="text" > px &nbsp; <br />';
print '<input type="hidden" name="file" value="'.$_GET['file'].'" />';
print '<input type="hidden" name="action" value="confirm_resize" />';
print '<input type="hidden" name="product" value="'.$_GET['id'].'" />';
print '<br><input class="button" name="sendit" value="Redimensionner" type="submit" />';
print '<br></form>';
print '<br>';

/*
 * Recadrage d'une image
 */

print '<br>';
print_fiche_titre($langs->trans("Recenter"),'','');

print 'Define new area to keep...<br>';
print '<img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=product&file='.$original_file.'" alt="Taille origine"  id="cropbox" />';
$infoarray=dol_getImageSize($conf->produit->dir_output."/".urldecode($_GET["file"]));
$height=$infoarray['height'];
$width=$infoarray['width'];
print '<form action="photos_resize.php" method="post" onsubmit="return checkCoords();">
      <div class="jc_coords">
         <div class="titre">Nouvelles dimensions après recadrage</div>
         <label>X1 <input type="text" size="4" id="x" name="x" /></label>
         <label>Y1 <input type="text" size="4" id="y" name="y" /></label>
         <label>X2 <input type="text" size="4" id="x2" name="x2" /></label>
         <label>Y2 <input type="text" size="4" id="y2" name="y2" /></label>
         <label>W <input type="text" size="4" id="w" name="w" /></label>
         <label>H <input type="text" size="4" id="h" name="h" /></label>
      </div>

      <input type="hidden" id="file" name="file" value="'.urlencode($original_file).'" />
      <input type="hidden" id="action" name="action" value="confirm_crop" />
      <input type="hidden" id="product" name="product" value="'.$_GET['id'].'" />
      <br><input type="submit" class="button" value="Recadrer" />
   </form>';

llxFooter('$Date$ - $Revision$');
?>