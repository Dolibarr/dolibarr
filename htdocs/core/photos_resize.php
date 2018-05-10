<?php
/* Copyright (C) 2010-2015	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2009		Meos
 * Copyright (C) 2012		Regis Houssin		<regis.houssin@capnetworks.com>
 * Copyright (C) 2016		Juanjo Menent		<jmenent@2byte.es>
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
 *       \file      htdocs/core/photos_resize.php
 *       \ingroup	core
 *       \brief     File of page to resize photos
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';

$langs->load("products");
$langs->load("other");

$id=GETPOST('id','int');
$action=GETPOST('action','alpha');
$modulepart=GETPOST('modulepart','alpha')?GETPOST('modulepart','alpha'):'produit|service';
$original_file = GETPOST("file");
$backtourl=GETPOST('backtourl');
$cancel=GETPOST('cancel','alpha');

// Security check
if (empty($modulepart)) accessforbidden('Bad value for modulepart');
$accessallowed=0;
if ($modulepart == 'produit' || $modulepart == 'product' || $modulepart == 'service' || $modulepart == 'produit|service')
{
	$result=restrictedArea($user,'produit|service',$id,'product&product');
	if ($modulepart=='produit|service' && (! $user->rights->produit->lire && ! $user->rights->service->lire)) accessforbidden();
	$accessallowed=1;
}
elseif ($modulepart == 'project')
{
    $result=restrictedArea($user,'projet',$id);
	if (! $user->rights->projet->lire) accessforbidden();
	$accessallowed=1;
}
elseif ($modulepart == 'expensereport')
{
	$result=restrictedArea($user,'expensereport',$id,'expensereport');
	if (! $user->rights->expensereport->lire) accessforbidden();
	$accessallowed=1;
}
elseif ($modulepart == 'holiday')
{
	$result=restrictedArea($user,'holiday',$id,'holiday');
	if (! $user->rights->holiday->read) accessforbidden();
	$accessallowed=1;
}
elseif ($modulepart == 'member')
{
	$result=restrictedArea($user, 'adherent', $id, '', '', 'fk_soc', 'rowid');
	if (! $user->rights->adherent->lire) accessforbidden();
	$accessallowed=1;
}
elseif ($modulepart == 'user')
{
	$result=restrictedArea($user,'user',$id,'user');
	if (! $user->rights->user->user->lire) accessforbidden();
	$accessallowed=1;
}
elseif ($modulepart == 'societe')
{
	$result=restrictedArea($user,'societe',$id,'societe');
	if (! $user->rights->societe->lire) accessforbidden();
	$accessallowed=1;
}
elseif ($modulepart == 'ticketsup')
{
	$result=restrictedArea($user,'ticketsup',$id,'ticketsup');
	if (! $user->rights->ticketsup->read) accessforbidden();
	$accessallowed=1;
}

// Security:
// Limit access if permissions are wrong
if (! $accessallowed)
{
	accessforbidden();
}

// Define dir according to modulepart
if ($modulepart == 'produit' || $modulepart == 'product' || $modulepart == 'service' || $modulepart == 'produit|service')
{
	require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
	$object = new Product($db);
	if ($id > 0)
	{
		$result = $object->fetch($id);
		if ($result <= 0) dol_print_error($db,'Failed to load object');
		$dir=$conf->product->multidir_output[$object->entity];	// By default
		if ($object->type == Product::TYPE_PRODUCT) $dir=$conf->product->multidir_output[$object->entity];
		if ($object->type == Product::TYPE_SERVICE) $dir=$conf->service->multidir_output[$object->entity];
	}
}
elseif ($modulepart == 'project')
{
    require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
    $object = new Project($db);
    if ($id > 0)
    {
        $result = $object->fetch($id);
        if ($result <= 0) dol_print_error($db,'Failed to load object');
        $dir=$conf->projet->dir_output;	// By default
    }
}
elseif ($modulepart == 'holiday')
{
	require_once DOL_DOCUMENT_ROOT.'/holiday/class/holiday.class.php';
	$object = new Holiday($db);
	if ($id > 0)
	{
		$result = $object->fetch($id);
		if ($result <= 0) dol_print_error($db,'Failed to load object');
		$dir=$conf->holiday->dir_output;	// By default
	}
}
elseif ($modulepart == 'member')
{
	require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
	$object = new Adherent($db);
	if ($id > 0)
	{
		$result = $object->fetch($id);
		if ($result <= 0) dol_print_error($db,'Failed to load object');
		$dir=$conf->adherent->dir_output;	// By default
	}
}
elseif ($modulepart == 'societe')
{
    require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
    $object = new Societe($db);
    if ($id > 0)
    {
        $result = $object->fetch($id);
        if ($result <= 0) dol_print_error($db,'Failed to load object');
        $dir=$conf->societe->dir_output;
    }
}
elseif ($modulepart == 'user')
{
    require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
    $object = new User($db);
    if ($id > 0)
    {
        $result = $object->fetch($id);
        if ($result <= 0) dol_print_error($db,'Failed to load object');
        $dir=$conf->user->dir_output;	// By default
    }
}
elseif ($modulepart == 'expensereport')
{
    require_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport.class.php';
    $object = new ExpenseReport($db);
    if ($id > 0)
    {
        $result = $object->fetch($id);
        if ($result <= 0) dol_print_error($db,'Failed to load object');
        $dir=$conf->expensereport->dir_output;	// By default
    }
}
elseif ($modulepart == 'ticketsup')
{
	require_once DOL_DOCUMENT_ROOT.'/ticketsup/class/ticketsup.class.php';
	$object = new Ticketsup($db);
	if ($id > 0)
	{
		$result = $object->fetch($id);
		if ($result <= 0) dol_print_error($db,'Failed to load object');
		$dir=$conf->ticketsup->dir_output;	// By default
	}
}
else {
	print 'Action crop for module part '.$modulepart.' is not supported yet.';
}

if (empty($backtourl))
{
    if (in_array($modulepart, array('product','produit','service','produit|service'))) $backtourl=DOL_URL_ROOT."/product/document.php?id=".$id.'&file='.urldecode($_POST["file"]);
    else if (in_array($modulepart, array('expensereport'))) $backtourl=DOL_URL_ROOT."/expensereport/document.php?id=".$id.'&file='.urldecode($_POST["file"]);
    else if (in_array($modulepart, array('holiday')))       $backtourl=DOL_URL_ROOT."/holiday/document.php?id=".$id.'&file='.urldecode($_POST["file"]);
    else if (in_array($modulepart, array('member')))        $backtourl=DOL_URL_ROOT."/adherents/document.php?id=".$id.'&file='.urldecode($_POST["file"]);
    else if (in_array($modulepart, array('project')))       $backtourl=DOL_URL_ROOT."/projet/document.php?id=".$id.'&file='.urldecode($_POST["file"]);
    else if (in_array($modulepart, array('societe')))       $backtourl=DOL_URL_ROOT."/societe/document.php?id=".$id.'&file='.urldecode($_POST["file"]);
    else if (in_array($modulepart, array('ticketsup')))     $backtourl=DOL_URL_ROOT."/ticketsup/document.php?id=".$id.'&file='.urldecode($_POST["file"]);
    else if (in_array($modulepart, array('user')))          $backtourl=DOL_URL_ROOT."/user/document.php?id=".$id.'&file='.urldecode($_POST["file"]);
}


/*
 * Actions
 */

if ($cancel)
{
	if ($backtourl)
	{
		header("Location: ".$backtourl);
		exit;
	}
	else
	{
	    dol_print_error('', 'Cancel on photo_resize with a not supported value of modulepart='.$modulepart);
	    exit;
	}
}

if ($action == 'confirm_resize' && (isset($_POST["file"]) != "") && (isset($_POST["sizex"]) != "") && (isset($_POST["sizey"]) != ""))
{
	$fullpath=$dir."/".$original_file;
	$result=dol_imageResizeOrCrop($fullpath,0,$_POST['sizex'],$_POST['sizey']);

	if ($result == $fullpath)
	{
		$object->addThumbs($fullpath);

		// Update/create database for file $fullpath
		$rel_filename = preg_replace('/^'.preg_quote(DOL_DATA_ROOT,'/').'/', '', $fullpath);
		$rel_filename = preg_replace('/^[\\/]/','',$rel_filename);

		include_once DOL_DOCUMENT_ROOT.'/ecm/class/ecmfiles.class.php';
		$ecmfile=new EcmFiles($db);
		$result = $ecmfile->fetch(0, '', $rel_filename);
		if ($result > 0)   // If found
		{
		    $filename = basename($rel_filename);
		    $rel_dir = dirname($rel_filename);
		    $rel_dir = preg_replace('/[\\/]$/', '', $rel_dir);
		    $rel_dir = preg_replace('/^[\\/]/', '', $rel_dir);

		    $ecmfile->label = md5_file(dol_osencode($fullpath));
		    $result = $ecmfile->update($user);
		}
		elseif ($result == 0)   // If not found
		{
		    $filename = basename($rel_filename);
		    $rel_dir = dirname($rel_filename);
		    $rel_dir = preg_replace('/[\\/]$/', '', $rel_dir);
		    $rel_dir = preg_replace('/^[\\/]/', '', $rel_dir);

		    $ecmfile->filepath = $rel_dir;
		    $ecmfile->filename = $filename;
		    $ecmfile->label = md5_file(dol_osencode($fullpath));        // $fullpath is a full path to file
		    $ecmfile->fullpath_orig = $fullpath;
		    $ecmfile->gen_or_uploaded = 'unknown';
		    $ecmfile->description = '';    // indexed content
		    $ecmfile->keyword = '';        // keyword content
		    $result = $ecmfile->create($user);
		    if ($result < 0)
		    {
		        setEventMessages($ecmfile->error, $ecmfile->errors, 'warnings');
		    }
		    $result = $ecmfile->create($user);
		}

		if ($backtourl)
		{
			header("Location: ".$backtourl);
			exit;
		}
		else
		{
    	    dol_print_error('', 'confirm_resize on photo_resize without backtourl defined for modulepart='.$modulepart);
    	    exit;
		}
	}
	else
	{
		setEventMessages($result, null, 'errors');
		$_GET['file']=$_POST["file"];
		$action='';
	}
}

// Crop d'une image
if ($action == 'confirm_crop')
{
	$fullpath=$dir."/".$original_file;
	$result=dol_imageResizeOrCrop($fullpath,1,$_POST['w'],$_POST['h'],$_POST['x'],$_POST['y']);

	if ($result == $fullpath)
	{
		$object->addThumbs($fullpath);

		// Update/create database for file $fullpath
		$rel_filename = preg_replace('/^'.preg_quote(DOL_DATA_ROOT,'/').'/', '', $fullpath);
		$rel_filename = preg_replace('/^[\\/]/','',$rel_filename);

		include_once DOL_DOCUMENT_ROOT.'/ecm/class/ecmfiles.class.php';
		$ecmfile=new EcmFiles($db);
		$result = $ecmfile->fetch(0, '', $rel_filename);
		if ($result > 0)   // If found
		{
		    $filename = basename($rel_filename);
		    $rel_dir = dirname($rel_filename);
		    $rel_dir = preg_replace('/[\\/]$/', '', $rel_dir);
		    $rel_dir = preg_replace('/^[\\/]/', '', $rel_dir);

		    $ecmfile->label = md5_file(dol_osencode($fullpath));
		    $result = $ecmfile->update($user);
		}
		elseif ($result == 0)   // If not found
		{
		    $filename = basename($rel_filename);
		    $rel_dir = dirname($rel_filename);
		    $rel_dir = preg_replace('/[\\/]$/', '', $rel_dir);
		    $rel_dir = preg_replace('/^[\\/]/', '', $rel_dir);

		    $ecmfile->filepath = $rel_dir;
		    $ecmfile->filename = $filename;
		    $ecmfile->label = md5_file(dol_osencode($fullpath));        // $fullpath is a full path to file
		    $ecmfile->fullpath_orig = $fullpath;
		    $ecmfile->gen_or_uploaded = 'unknown';
		    $ecmfile->description = '';    // indexed content
		    $ecmfile->keyword = '';        // keyword content
		    $result = $ecmfile->create($user);
		    if ($result < 0)
		    {
		        setEventMessages($ecmfile->error, $ecmfile->errors, 'warnings');
		    }
		    $result = $ecmfile->create($user);
		}

		if ($backtourl)
		{
			header("Location: ".$backtourl);
			exit;
		}
		else
		{
    	    dol_print_error('', 'confirm_crop on photo_resize without backtourl defined for modulepart='.$modulepart);
    	    exit;
		}
	}
	else
	{
		setEventMessages($result, null, 'errors');
		$_GET['file']=$_POST["file"];
		$action='';
	}
}


/*
 * View
 */

llxHeader($head, $langs->trans("Image"), '', '', 0, 0, array('/includes/jquery/plugins/jcrop/js/jquery.Jcrop.min.js','/core/js/lib_photosresize.js'), array('/includes/jquery/plugins/jcrop/css/jquery.Jcrop.css'));


print load_fiche_titre($langs->trans("ImageEditor"));

$infoarray=dol_getImageSize($dir."/".GETPOST("file",'alpha'));
$height=$infoarray['height'];
$width=$infoarray['width'];
print $langs->trans("CurrentInformationOnImage").': ';
print $langs->trans("Width").': <strong>'.$width.'</strong> x '.$langs->trans("Height").': <strong>'.$height.'</strong><br>';

print '<br>'."\n";


/*
 * Resize image
 */

print '<!-- Form to resize -->'."\n";
print '<form name="redim_file" action="'.$_SERVER["PHP_SELF"].'?id='.$id.'" method="POST">';

print '<fieldset id="redim_file">';
print '<legend>'.$langs->trans("Resize").'</legend>';
print $langs->trans("ResizeDesc").'<br>';
print $langs->trans("NewLength").': <input name="sizex" type="number" class="flat maxwidth50"> px  &nbsp; '.$langs->trans("or").' &nbsp; ';
print $langs->trans("NewHeight").': <input name="sizey" type="number" class="flat maxwidth50"> px &nbsp; <br>';

print '<input type="hidden" name="file" value="'.dol_escape_htmltag(GETPOST('file')).'" />';
print '<input type="hidden" name="action" value="confirm_resize" />';
print '<input type="hidden" name="product" value="'.$id.'" />';
print '<input type="hidden" name="modulepart" value="'.dol_escape_htmltag($modulepart).'" />';
print '<input type="hidden" name="id" value="'.$id.'" />';
print '<br>';
print '<input class="button" id="submitresize" name="sendit" value="'.dol_escape_htmltag($langs->trans("Resize")).'" type="submit" />';
print '&nbsp;';
print '<input type="submit" id="cancelresize" name="cancel" class="button" value="'.dol_escape_htmltag($langs->trans("Cancel")).'" />';
print '</fieldset>'."\n";
print '</form>';
print '<br>'."\n";


/*
 * Crop image
 */

print '<br>'."\n";

if (! empty($conf->use_javascript_ajax))
{
	$infoarray=dol_getImageSize($dir."/".GETPOST("file"));
	$height=$infoarray['height'];
	$width=$infoarray['width'];
	$widthforcrop=$width; $refsizeforcrop='orig'; $ratioforcrop=1;
	// If image is too large, we use another scale.
	if (! empty($_SESSION['dol_screenwidth']) && ($widthforcrop > round($_SESSION['dol_screenwidth']/2)))
	{
		$widthforcrop=round($_SESSION['dol_screenwidth']/2);
		$refsizeforcrop='screenwidth';
		$ratioforcrop=1;
	}

	print '<!-- Form to crop -->'."\n";
	print '<fieldset id="redim_file">';
	print '<legend>'.$langs->trans("Recenter").'</legend>';
	print $langs->trans("DefineNewAreaToPick").'...<br>';
	print '<br><div class="center">';
	print '<div style="border: 1px solid #888888; width: '.$widthforcrop.'px;">';
	print '<img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart='.$modulepart.'&entity='.$object->entity.'&file='.urlencode($original_file).'" alt="" id="cropbox" width="'.$widthforcrop.'px"/>';
	print '</div>';
	print '</div><br>';
	print '<form action="'.$_SERVER["PHP_SELF"].'?id='.$id.'" method="POST">
	      <div class="jc_coords">
	         '.$langs->trans("NewSizeAfterCropping").':
	         <label>X1 <input type="number" class="flat maxwidth50" id="x" name="x" /></label>
	         <label>Y1 <input type="number" class="flat maxwidth50" id="y" name="y" /></label>
	         <label>X2 <input type="number" class="flat maxwidth50" id="x2" name="x2" /></label>
	         <label>Y2 <input type="number" class="flat maxwidth50" id="y2" name="y2" /></label>
	         <label>W  <input type="number" class="flat maxwidth50" id="w" name="w" /></label>
	         <label>H  <input type="number" class="flat maxwidth50" id="h" name="h" /></label>
	      </div>

	      <input type="hidden" id="file" name="file" value="'.dol_escape_htmltag($original_file).'" />
	      <input type="hidden" id="action" name="action" value="confirm_crop" />
	      <input type="hidden" id="product" name="product" value="'.dol_escape_htmltag($id).'" />
	      <input type="hidden" id="refsizeforcrop" name="refsizeforcrop" value="'.$refsizeforcrop.'" />
	      <input type="hidden" id="ratioforcrop" name="ratioforcrop" value="'.$ratioforcrop.'" />
          <input type="hidden" name="modulepart" value="'.dol_escape_htmltag($modulepart).'" />
	      <input type="hidden" name="id" value="'.dol_escape_htmltag($id).'" />
	      <br>
	      <input type="submit" id="submitcrop" name="submitcrop" class="button" value="'.dol_escape_htmltag($langs->trans("Recenter")).'" />
	      &nbsp;
	      <input type="submit" id="cancelcrop" name="cancel" class="button" value="'.dol_escape_htmltag($langs->trans("Cancel")).'" />
	   </form>'."\n";
	print '</fieldset>'."\n";
	print '<br>';
}

/* Check that mandatory fields are filled */
print '<script type="text/javascript" language="javascript">
jQuery(document).ready(function() {
	$("#submitcrop").click(function(e) {
        console.log("We click on submitcrop");
	    var idClicked = e.target.id;
	    if (parseInt(jQuery(\'#w\').val())) return true;
	    alert(\''.dol_escape_js($langs->trans("ErrorFieldRequired", $langs->trans("Dimension"))).'\');
	    return false;
	});
});
</script>';

llxFooter();
$db->close();
