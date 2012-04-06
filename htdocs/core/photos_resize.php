<?php
/* Copyright (C) 2010-2011	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2009		Meos
 * Copyright (C) 2012		Regis Houssin		<regis@dolibarr.fr>
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
 *       \file      htdocs/core/photos_resize.php
 *       \ingroup	core
 *       \brief     File of page to resize photos
 */

//if (! defined('NOREQUIREUSER'))  define('NOREQUIREUSER','1');
//if (! defined('NOREQUIREDB'))    define('NOREQUIREDB','1');
//if (! defined('NOREQUIRESOC'))   define('NOREQUIRESOC','1');
//if (! defined('NOREQUIRETRAN'))  define('NOREQUIRETRAN','1');
//if (! defined('NOCSRFCHECK'))    define('NOCSRFCHECK','1');
//if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL','1');
//if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU','1'); // If there is no menu to show
//if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML','1'); // If we don't need to load the html.form.class.php
//if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX','1');
//if (! defined("NOLOGIN"))        define("NOLOGIN",'1');       // If this page is public (can be called outside logged session)

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/product.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/images.lib.php");
require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");

$langs->load("products");
$langs->load("other");

$id=GETPOST('id','int');
$action=GETPOST('action','alpha');
$modulepart=GETPOST('modulepart','alpha')?GETPOST('modulepart','alpha'):'produit|service';
$original_file = isset($_REQUEST["file"])?urldecode($_REQUEST["file"]):'';

// Security check
if (empty($modulepart)) accessforbidden('Bad value for modulepart');
$accessallowed=0;
if ($modulepart=='produit|service')
{
	$result=restrictedArea($user,'produit|service',$id,'product&product');
	if ($modulepart=='produit|service' && (! $user->rights->produit->lire && ! $user->rights->service->lire)) accessforbidden();
	$accessallowed=1;
}

// Security:
// Limit access if permissions are wrong
if (! $accessallowed)
{
	accessforbidden();
}

$object = new Product($db);
if ($id > 0)
{
	$result = $object->fetch($id);
	if ($result <= 0) dol_print_error($db,'Failed to load object');
	$dir=$conf->product->multidir_output[$object->entity];	// By default
	if ($object->type == 0) $dir=$conf->product->multidir_output[$object->entity];
	if ($object->type == 1) $dir=$conf->service->multidir_output[$object->entity];
}

/*
 * Actions
 */

if ($action == 'confirm_resize' && (isset($_POST["file"]) != "") && (isset($_POST["sizex"]) != "") && (isset($_POST["sizey"]) != ""))
{
	$fullpath=$dir."/".$original_file;
	$result=dol_imageResizeOrCrop($fullpath,0,$_POST['sizex'],$_POST['sizey']);

	if ($result == $fullpath)
	{
		header("Location: ".DOL_URL_ROOT."/product/photos.php?id=".$id.'&action=addthumb&file='.urldecode($_POST["file"]));
		exit;
	}
	else
	{
		$mesg=$result;
		$_GET['file']=$_POST["file"];
	}
}

// Crop d'une image
if ($action == 'confirm_crop')
{
	$fullpath=$dir."/".$original_file;
	$result=dol_imageResizeOrCrop($fullpath,1,$_POST['w'],$_POST['h'],$_POST['x'],$_POST['y']);

	if ($result == $fullpath)
	{
		header("Location: ".DOL_URL_ROOT."/product/photos.php?id=".$id.'&action=addthumb&file='.urldecode($_POST["file"]));
		exit;
	}
	else
	{
		$mesg=$result;
		$_GET['file']=$_POST["file"];
	}
}


/*
 * View
 */

llxHeader($head, $langs->trans("Image"), '', '', 0, 0, array('/includes/jquery/plugins/jcrop/js/jquery.Jcrop.min.js','/core/js/lib_photosresize.js'), array('/includes/jquery/plugins/jcrop/css/jquery.Jcrop.css'));


print_fiche_titre($langs->trans("ImageEditor"));

if ($mesg) print '<div class="error">'.$mesg.'</div>';

$infoarray=dol_getImageSize($dir."/".urldecode($_GET["file"]));
$height=$infoarray['height'];
$width=$infoarray['width'];
print $langs->trans("CurrentInformationOnImage").':';
print '<ul>
   <li>'.$langs->trans("Width").': '.$width.' px</li>
   <li>'.$langs->trans("Height").': '.$height.' px</li>
   </ul>';

print '<br>'."\n";

print '<!-- Form to resize -->'."\n";
print '<form name="redim_file" action="'.$_SERVER["PHP_SELF"].'?id='.$id.'" method="POST">';

print '<fieldset id="redim_file">';
print '<legend>'.$langs->trans("Resize").'</legend>';
print $langs->trans("ResizeDesc").'<br>';
print $langs->trans("NewLength").': <input class="flat" name="sizex" size="10" type="text" > px <br> ';
print $langs->trans("NewHeight").': <input class="flat" name="sizey" size="10" type="text" > px &nbsp; <br>';
print '<input type="hidden" name="file" value="'.$_GET['file'].'" />';
print '<input type="hidden" name="action" value="confirm_resize" />';
print '<input type="hidden" name="product" value="'.$id.'" />';
print '<input type="hidden" name="id" value="'.$id.'" />';
print '<br><input class="button" name="sendit" value="'.dol_escape_htmltag($langs->trans("Resize")).'" type="submit" />';
print '</fieldset>';
print '<br></form>';

/*
 * Recadrage d'une image
 */

print '<br>'."\n";

if (! empty($conf->use_javascript_ajax))
{

$infoarray=dol_getImageSize($dir."/".urldecode($_GET["file"]));
$height=$infoarray['height'];
$width=$infoarray['width'];

print '<!-- Form to crop -->'."\n";
print '<fieldset id="redim_file">';
print '<legend>'.$langs->trans("Recenter").'</legend>';
print $langs->trans("DefineNewAreaToPick").'...<br>';
print '<br><center>';
print '<div style="border: 1px solid #888888; width: '.$width.'px;"><img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=product&entity='.$object->entity.'&file='.$original_file.'" alt="" id="cropbox" /></div>';
print '</center><br>';
print '<form action="'.$_SERVER["PHP_SELF"].'?id='.$id.'" method="post" onsubmit="return checkCoords();">
      <div class="jc_coords">
         '.$langs->trans("NewSizeAfterCropping").':
         <label>X1 <input type="text" size="4" id="x" name="x" /></label>
         <label>Y1 <input type="text" size="4" id="y" name="y" /></label>
         <label>X2 <input type="text" size="4" id="x2" name="x2" /></label>
         <label>Y2 <input type="text" size="4" id="y2" name="y2" /></label>
         <label>W <input type="text" size="4" id="w" name="w" /></label>
         <label>H <input type="text" size="4" id="h" name="h" /></label>
      </div>

      <input type="hidden" id="file" name="file" value="'.urlencode($original_file).'" />
      <input type="hidden" id="action" name="action" value="confirm_crop" />
      <input type="hidden" id="product" name="product" value="'.$id.'" />
	  <input type="hidden" name="id" value="'.$id.'" />
      <br><input type="submit" class="button" value="'.dol_escape_htmltag($langs->trans("Recenter")).'" />
   </form>';
print '</fieldset>';

}


llxFooter();
$db->close();
?>
