<?php
/* Copyright (C) 2010-2015	Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2009		Meos
 * Copyright (C) 2012		Regis Houssin				<regis.houssin@inodbox.com>
 * Copyright (C) 2016		Juanjo Menent				<jmenent@2byte.es>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024		Alexandre Spangaro			<alexandre@inovea-conseil.com>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *       \file      htdocs/core/photos_resize.php
 *       \ingroup	core
 *       \brief     File of page to resize photos
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';

// Load translation files required by the page
$langs->loadLangs(array("products", "other"));

$id = GETPOSTINT('id');
$action = GETPOST('action', 'aZ09');
$modulepart = GETPOST('modulepart', 'alpha') ? GETPOST('modulepart', 'alpha') : 'produit|service';
$original_file = GETPOST("file");
$backtourl = GETPOST('backtourl');
$cancel = GETPOST('cancel', 'alpha');

$file = GETPOST('file', 'alpha');
$num = GETPOST('num', 'alpha'); // Used for document on bank statement
$website = GETPOST('website', 'alpha');


// Security check
if (empty($modulepart)) {
	accessforbidden('Bad value for modulepart');
}
$accessallowed = 0;
if ($modulepart == 'produit' || $modulepart == 'product' || $modulepart == 'service' || $modulepart == 'produit|service') {
	$result = restrictedArea($user, 'produit|service', $id, 'product&product');
	if ($modulepart == 'produit|service' && (!$user->hasRight('produit', 'lire') && !$user->hasRight('service', 'lire'))) {
		accessforbidden();
	}
	$accessallowed = 1;
} elseif ($modulepart == 'project') {
	$result = restrictedArea($user, 'projet', $id);
	if (!$user->hasRight('projet', 'lire')) {
		accessforbidden();
	}
	$accessallowed = 1;
} elseif ($modulepart == 'bom') {
	$result = restrictedArea($user, $modulepart, $id, 'bom_bom');
	if (!$user->hasRight('bom', 'read')) {
		accessforbidden();
	}
	$accessallowed = 1;
} elseif ($modulepart == 'member') {
	$result = restrictedArea($user, 'adherent', $id, '', '', 'fk_soc', 'rowid');
	if (!$user->hasRight('adherent', 'lire')) {
		accessforbidden();
	}
	$accessallowed = 1;
} elseif ($modulepart == 'user') {
	$result = restrictedArea($user, $modulepart, $id, $modulepart, $modulepart);
	if (!$user->hasRight('user', 'user', 'lire')) {
		accessforbidden();
	}
	$accessallowed = 1;
} elseif ($modulepart == 'tax') {
	$result = restrictedArea($user, $modulepart, $id, 'chargesociales', 'charges');
	if (!$user->hasRight('tax', 'charges', 'lire')) {
		accessforbidden();
	}
	$accessallowed = 1;
} elseif ($modulepart == 'bank') {
	$result = restrictedArea($user, 'banque', $id, 'bank_account');
	if (!$user->hasRight('banque', 'lire')) {
		accessforbidden();
	}
	$accessallowed = 1;
} elseif ($modulepart == 'medias') {
	$permtoadd = ($user->hasRight('mailing', 'creer') || $user->hasRight('website', 'write'));
	if (!$permtoadd) {
		accessforbidden();
	}
	$accessallowed = 1;
} elseif ($modulepart == 'facture_fourn' || $modulepart == 'facture_fournisseur') {
	$result = restrictedArea($user, 'fournisseur', $id, 'facture_fourn', 'facture');
	if (!$user->hasRight('fournisseur', 'facture', 'lire')) {
		accessforbidden();
	}
	$accessallowed = 1;
} else {
	// ticket, holiday, expensereport, societe...
	$result = restrictedArea($user, $modulepart, $id, $modulepart);
	if (!$user->hasRight($modulepart, 'read') && !$user->hasRight($modulepart, 'lire')) {
		accessforbidden();
	}
	$accessallowed = 1;
}

// Security:
// Limit access if permissions are wrong
if (!$accessallowed) {
	accessforbidden();
}

// Define dir according to modulepart
$dir = '';
if ($modulepart == 'produit' || $modulepart == 'product' || $modulepart == 'service' || $modulepart == 'produit|service') {
	require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
	$object = new Product($db);
	if ($id > 0) {
		$result = $object->fetch($id);
		if ($result <= 0) {
			dol_print_error($db, 'Failed to load object');
		}
		$dir = $conf->product->multidir_output[$object->entity]; // By default
		if ($object->type == Product::TYPE_PRODUCT) {
			$dir = $conf->product->multidir_output[$object->entity];
		}
		if ($object->type == Product::TYPE_SERVICE) {
			$dir = $conf->service->multidir_output[$object->entity];
		}
	}
} elseif ($modulepart == 'project') {
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
	$object = new Project($db);
	if ($id > 0) {
		$result = $object->fetch($id);
		if ($result <= 0) {
			dol_print_error($db, 'Failed to load object');
		}
		$dir = $conf->project->multidir_output[$object->entity]; // By default
	}
} elseif ($modulepart == 'propal') {
	require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
	$object = new Propal($db);
	if ($id > 0) {
		$result = $object->fetch($id);
		if ($result <= 0) {
			dol_print_error($db, 'Failed to load object');
		}
		$dir = $conf->propal->multidir_output[$object->entity]; // By default
	}
} elseif ($modulepart == 'holiday') {
	require_once DOL_DOCUMENT_ROOT.'/holiday/class/holiday.class.php';
	$object = new Holiday($db);
	if ($id > 0) {
		$result = $object->fetch($id);
		if ($result <= 0) {
			dol_print_error($db, 'Failed to load object');
		}
		$dir = $conf->$modulepart->dir_output; // By default
	}
} elseif ($modulepart == 'member') {
	require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
	$object = new Adherent($db);
	if ($id > 0) {
		$result = $object->fetch($id);
		if ($result <= 0) {
			dol_print_error($db, 'Failed to load object');
		}
		$dir = $conf->adherent->dir_output; // By default
	}
} elseif ($modulepart == 'societe') {
	require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
	$object = new Societe($db);
	if ($id > 0) {
		$result = $object->fetch($id);
		if ($result <= 0) {
			dol_print_error($db, 'Failed to load object');
		}
		$dir = $conf->$modulepart->dir_output;
	}
} elseif ($modulepart == 'user') {
	require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
	$object = new User($db);
	if ($id > 0) {
		$result = $object->fetch($id);
		if ($result <= 0) {
			dol_print_error($db, 'Failed to load object');
		}
		$dir = $conf->$modulepart->dir_output; // By default
	}
} elseif ($modulepart == 'expensereport') {
	require_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport.class.php';
	$object = new ExpenseReport($db);
	if ($id > 0) {
		$result = $object->fetch($id);
		if ($result <= 0) {
			dol_print_error($db, 'Failed to load object');
		}
		$dir = $conf->expensereport->dir_output; // By default
	}
} elseif ($modulepart == 'tax') {
	require_once DOL_DOCUMENT_ROOT.'/compta/sociales/class/chargesociales.class.php';
	$object = new ChargeSociales($db);
	if ($id > 0) {
		$result = $object->fetch($id);
		if ($result <= 0) {
			dol_print_error($db, 'Failed to load object');
		}
		$dir = $conf->$modulepart->dir_output; // By default
	}
} elseif ($modulepart == 'ticket') {
	require_once DOL_DOCUMENT_ROOT.'/ticket/class/ticket.class.php';
	$object = new Ticket($db);
	if ($id > 0) {
		$result = $object->fetch($id);
		if ($result <= 0) {
			dol_print_error($db, 'Failed to load object');
		}
		$dir = $conf->$modulepart->dir_output; // By default
	}
} elseif ($modulepart == 'bom') {
	require_once DOL_DOCUMENT_ROOT.'/bom/class/bom.class.php';
	$object = new BOM($db);
	if ($id > 0) {
		$result = $object->fetch($id);
		if ($result <= 0) {
			dol_print_error($db, 'Failed to load object');
		}
		$dir = $conf->$modulepart->dir_output; // By default
	}
} elseif ($modulepart == 'mrp') {
	require_once DOL_DOCUMENT_ROOT.'/mrp/class/mo.class.php';
	$object = new Mo($db);
	if ($id > 0) {
		$result = $object->fetch($id);
		if ($result <= 0) {
			dol_print_error($db, 'Failed to load object');
		}
		$dir = $conf->$modulepart->dir_output; // By default
	}
} elseif ($modulepart == 'bank') {
	require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
	$object = new Account($db);
	if ($id > 0) {
		$result = $object->fetch($id);
		if ($result <= 0) {
			dol_print_error($db, 'Failed to load object');
		}
		$dir = $conf->bank->dir_output; // By default
	}
} elseif ($modulepart == 'facture') {
	require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
	$object = new Facture($db);
	if ($id > 0) {
		$result = $object->fetch($id);
		if ($result <= 0) {
			dol_print_error($db, 'Failed to load object');
		}
		$dir = $conf->$modulepart->dir_output; // By default
	}
} elseif ($modulepart == 'facture_fourn' || $modulepart == 'facture_fournisseur') {
	require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
	$object = new FactureFournisseur($db);
	if ($id > 0) {
		$result = $object->fetch($id);
		if ($result <= 0) {
			dol_print_error($db, 'Failed to load object');
		}
		$dir = $conf->fournisseur->dir_output.'/facture'; // By default
	}
} elseif ($modulepart == 'medias') {
	$dir = $dolibarr_main_data_root.'/'.$modulepart;
} else {
	print 'Bug: Action crop for modulepart = '.$modulepart.' is not supported yet by photos_resize.php.';
}

if (empty($backtourl)) {
	$regs = array();

	if (in_array($modulepart, array('product', 'produit', 'service', 'produit|service'))) {
		$backtourl = DOL_URL_ROOT."/product/document.php?id=".((int) $id).'&file='.urlencode($file);
	} elseif (in_array($modulepart, array('expensereport'))) {
		$backtourl = DOL_URL_ROOT."/expensereport/document.php?id=".((int) $id).'&file='.urlencode($file);
	} elseif (in_array($modulepart, array('holiday'))) {
		$backtourl = DOL_URL_ROOT."/holiday/document.php?id=".((int) $id).'&file='.urlencode($file);
	} elseif (in_array($modulepart, array('member'))) {
		$backtourl = DOL_URL_ROOT."/adherents/document.php?id=".((int) $id).'&file='.urlencode($file);
	} elseif (in_array($modulepart, array('project'))) {
		$backtourl = DOL_URL_ROOT."/projet/document.php?id=".((int) $id).'&file='.urlencode($file);
	} elseif (in_array($modulepart, array('propal'))) {
		$backtourl = DOL_URL_ROOT."/comm/propal/document.php?id=".((int) $id).'&file='.urlencode($file);
	} elseif (in_array($modulepart, array('societe'))) {
		$backtourl = DOL_URL_ROOT."/societe/document.php?id=".((int) $id).'&file='.urlencode($file);
	} elseif (in_array($modulepart, array('tax'))) {
		$backtourl = DOL_URL_ROOT."/compta/sociales/document.php?id=".((int) $id).'&file='.urlencode($file);
	} elseif (in_array($modulepart, array('ticket'))) {
		$backtourl = DOL_URL_ROOT."/ticket/document.php?id=".((int) $id).'&file='.urlencode($file);
	} elseif (in_array($modulepart, array('user'))) {
		$backtourl = DOL_URL_ROOT."/user/document.php?id=".((int) $id).'&file='.urlencode($file);
	} elseif (in_array($modulepart, array('facture'))) {
		$backtourl = DOL_URL_ROOT."/compta/facture/document.php?id=".((int) $id).'&file='.urlencode($file);
	} elseif (in_array($modulepart, array('facture_fourn', 'facture_fournisseur'))) {
		$backtourl = DOL_URL_ROOT."/fourn/facture/document.php?id=".((int) $id).'&file='.urlencode($file);
	} elseif (in_array($modulepart, array('bank')) && preg_match('/\/statement\/([^\/]+)\//', $file, $regs)) {
		$num = $regs[1];
		$backtourl = DOL_URL_ROOT."/compta/bank/account_statement_document.php?id=".((int) $id).'&num='.urlencode($num).'&file='.urlencode($file);
	} elseif (in_array($modulepart, array('bank'))) {
		$backtourl = DOL_URL_ROOT."/compta/bank/document.php?id=".((int) $id).'&file='.urlencode($file);
	} elseif (in_array($modulepart, array('mrp'))) {
		$backtourl = DOL_URL_ROOT."/mrp/mo_document.php?id=".((int) $id).'&file='.urlencode($file);
	} elseif (in_array($modulepart, array('medias'))) {
		$section_dir = dirname($file);
		if (!preg_match('/\/$/', $section_dir)) {
			$section_dir .= '/';
		}
		$backtourl = DOL_URL_ROOT.'/website/index.php?action=file_manager'.($website ? '&website='.urlencode($website) : '').'&section_dir='.urlencode($section_dir);
	} else {
		// Generic case that should work for everybody else
		$backtourl = DOL_URL_ROOT."/".$modulepart."/".$modulepart."_document.php?id=".((int) $id).'&file='.urlencode($file);
	}
}


/*
 * Actions
 */

if ($cancel) {
	if ($backtourl) {
		header("Location: ".$backtourl);
		exit;
	} else {
		dol_print_error(null, 'Cancel on photo_resize with a not supported value of modulepart='.$modulepart);
		exit;
	}
}

if ($action == 'confirm_resize' && GETPOSTISSET("file") && GETPOSTISSET("sizex") && GETPOSTISSET("sizey")) {
	if (empty($dir)) {
		dol_print_error(null, 'Bug: Value for $dir could not be defined.');
		exit;
	}

	$fullpath = $dir."/".$original_file;

	$result = dol_imageResizeOrCrop($fullpath, 0, GETPOSTINT('sizex'), GETPOSTINT('sizey'));

	if ($result == $fullpath) {
		// If image is related to a given object, we create also thumbs.
		if (is_object($object)) {
			$object->addThumbs($fullpath);
		}

		// Update/create database for file $fullpath
		$rel_filename = preg_replace('/^'.preg_quote(DOL_DATA_ROOT, '/').'/', '', $fullpath);
		$rel_filename = preg_replace('/^[\\/]/', '', $rel_filename);

		include_once DOL_DOCUMENT_ROOT.'/ecm/class/ecmfiles.class.php';
		$ecmfile = new EcmFiles($db);
		$result = $ecmfile->fetch(0, '', $rel_filename);
		if ($result > 0) {   // If found
			$filename = basename($rel_filename);
			$rel_dir = dirname($rel_filename);
			$rel_dir = preg_replace('/[\\/]$/', '', $rel_dir);
			$rel_dir = preg_replace('/^[\\/]/', '', $rel_dir);

			$ecmfile->label = md5_file(dol_osencode($fullpath));
			$result = $ecmfile->update($user);
		} elseif ($result == 0) {   // If not found
			$filename = basename($rel_filename);
			$rel_dir = dirname($rel_filename);
			$rel_dir = preg_replace('/[\\/]$/', '', $rel_dir);
			$rel_dir = preg_replace('/^[\\/]/', '', $rel_dir);

			$ecmfile->filepath = $rel_dir;
			$ecmfile->filename = $filename;
			$ecmfile->label = md5_file(dol_osencode($fullpath)); // $fullpath is a full path to file
			$ecmfile->fullpath_orig = $fullpath;
			$ecmfile->gen_or_uploaded = 'unknown';
			$ecmfile->description = ''; // indexed content
			$ecmfile->keywords = ''; // keyword content
			$result = $ecmfile->create($user);
			if ($result < 0) {
				setEventMessages($ecmfile->error, $ecmfile->errors, 'warnings');
			}
			$result = $ecmfile->create($user);
		}

		if ($backtourl) {
			header("Location: ".$backtourl);
			exit;
		} else {
			dol_print_error(null, 'confirm_resize on photo_resize without backtourl defined for modulepart='.$modulepart);
			exit;
		}
	} else {
		setEventMessages($result, null, 'errors');
		$action = '';
	}
}

// Crop d'une image
if ($action == 'confirm_crop') {
	if (empty($dir)) {
		print 'Bug: Value for $dir could not be defined.';
	}

	$fullpath = $dir."/".$original_file;

	$result = dol_imageResizeOrCrop($fullpath, 1, GETPOSTINT('w'), GETPOSTINT('h'), GETPOSTINT('x'), GETPOSTINT('y'));

	if ($result == $fullpath) {
		if (is_object($object)) {
			$object->addThumbs($fullpath);
		}

		// Update/create database for file $fullpath
		$rel_filename = preg_replace('/^'.preg_quote(DOL_DATA_ROOT, '/').'/', '', $fullpath);
		$rel_filename = preg_replace('/^[\\/]/', '', $rel_filename);

		include_once DOL_DOCUMENT_ROOT.'/ecm/class/ecmfiles.class.php';
		$ecmfile = new EcmFiles($db);
		$result = $ecmfile->fetch(0, '', $rel_filename);
		if ($result > 0) {   // If found
			$filename = basename($rel_filename);
			$rel_dir = dirname($rel_filename);
			$rel_dir = preg_replace('/[\\/]$/', '', $rel_dir);
			$rel_dir = preg_replace('/^[\\/]/', '', $rel_dir);

			$ecmfile->label = md5_file(dol_osencode($fullpath));
			$result = $ecmfile->update($user);
		} elseif ($result == 0) {   // If not found
			$filename = basename($rel_filename);
			$rel_dir = dirname($rel_filename);
			$rel_dir = preg_replace('/[\\/]$/', '', $rel_dir);
			$rel_dir = preg_replace('/^[\\/]/', '', $rel_dir);

			$ecmfile->filepath = $rel_dir;
			$ecmfile->filename = $filename;
			$ecmfile->label = md5_file(dol_osencode($fullpath)); // $fullpath is a full path to file
			$ecmfile->fullpath_orig = $fullpath;
			$ecmfile->gen_or_uploaded = 'unknown';
			$ecmfile->description = ''; // indexed content
			$ecmfile->keywords = ''; // keyword content
			$result = $ecmfile->create($user);
			if ($result < 0) {
				setEventMessages($ecmfile->error, $ecmfile->errors, 'warnings');
			}
		}

		if ($backtourl) {
			header("Location: ".$backtourl);
			exit;
		} else {
			dol_print_error(null, 'confirm_crop on photo_resize without backtourl defined for modulepart='.$modulepart);
			exit;
		}
	} else {
		setEventMessages($result, null, 'errors');
		$action = '';
	}
}


/*
 * View
 */

$head = '';
$title = $langs->trans("ImageEditor");
$morejs = array('/includes/jquery/plugins/jcrop/js/jquery.Jcrop.min.js', '/core/js/lib_photosresize.js');
$morecss = array('/includes/jquery/plugins/jcrop/css/jquery.Jcrop.css');

llxHeader($head, $title, '', '', 0, 0, $morejs, $morecss);


print load_fiche_titre($title);

$infoarray = dol_getImageSize($dir."/".GETPOST("file", 'alpha'));
$height = $infoarray['height'];
$width = $infoarray['width'];
print '<span class="opacitymedium hideonsmartphone">'.$langs->trans("CurrentInformationOnImage").': </span>';
print '<span class="opacitymedium">';
print $langs->trans("Width").': <strong>'.$width.'</strong> x '.$langs->trans("Height").': <strong>'.$height.'</strong>';
print '</span><br>';

print '<br>'."\n";


/*
 * Resize image
 */

print '<!-- Form to resize -->'."\n";
print '<form name="redim_file" action="'.$_SERVER["PHP_SELF"].'?id='.((int) $id).($num ? '&num='.urlencode($num) : '').'" method="POST">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="backtourl" value="'.$backtourl.'">';

print '<fieldset id="redim_file">';
print '<legend>'.$langs->trans("Resize").'</legend>';
print $langs->trans("ResizeDesc").'<br>';
print $langs->trans("NewLength").': <input name="sizex" type="number" class="flat maxwidth50 right"> px  &nbsp; <span class="opacitymedium">'.$langs->trans("or").'</span> &nbsp; ';
print $langs->trans("NewHeight").': <input name="sizey" type="number" class="flat maxwidth50 right"> px &nbsp; <br>';

print '<input type="hidden" name="file" value="'.dol_escape_htmltag($file).'" />';
print '<input type="hidden" name="action" value="confirm_resize" />';
print '<input type="hidden" name="product" value="'.$id.'" />';
print '<input type="hidden" name="modulepart" value="'.dol_escape_htmltag($modulepart).'" />';
print '<input type="hidden" name="id" value="'.$id.'" />';
print '<br>';
print '<input class="button" id="submitresize" name="sendit" value="'.dol_escape_htmltag($langs->trans("Resize")).'" type="submit" />';
print '&nbsp;';
print '<input type="submit" id="cancelresize" name="cancel" class="button button-cancel" value="'.dol_escape_htmltag($langs->trans("Cancel")).'" />';
print '</fieldset>'."\n";
print '</form>';

print '<br>'."\n";


/*
 * Crop image
 */

print '<br>'."\n";

if (!empty($conf->use_javascript_ajax)) {
	$infoarray = dol_getImageSize($dir."/".GETPOST("file"));
	$height = $infoarray['height'];
	$width = $infoarray['width'];
	$widthforcrop = $width;
	$refsizeforcrop = 'orig';
	$ratioforcrop = 1;

	// If image is too large, we use another scale.
	if (!empty($_SESSION['dol_screenwidth'])) {
		$widthforcroporigin = $widthforcrop;
		while ($widthforcrop > round($_SESSION['dol_screenwidth'] / 1.5)) {
			//var_dump($widthforcrop.' '.round($_SESSION['dol_screenwidth'] / 1.5));
			$ratioforcrop = 2 * $ratioforcrop;
			$widthforcrop = floor($widthforcroporigin / $ratioforcrop);
			$refsizeforcrop = 'screenwidth';
		}
	}

	print '<!-- Form to crop -->'."\n";
	print '<fieldset id="redim_file">';
	print '<legend>'.$langs->trans("Crop").'</legend>';
	print $langs->trans("DefineNewAreaToPick").'...<br>';
	print '<br><div class="center">';

	if (empty($conf->dol_no_mouse_hover)) {
		print '<div style="border: 1px solid #888888; width: '.$widthforcrop.'px;">';
		print '<img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart='.urlencode($modulepart).'&entity='.((int) $object->entity).'&file='.urlencode($original_file).'" alt="" id="cropbox" width="'.$widthforcrop.'px"/>';
		print '</div>';
		print '</div><br>';

		print '<form action="'.$_SERVER["PHP_SELF"].'?id='.((int) $id).($num ? '&num='.urlencode($num) : '').'" method="POST">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="backtourl" value="'.$backtourl.'">';
		print '
		      <div class="jc_coords">
		         '.$langs->trans("NewSizeAfterCropping").':
		         &nbsp; <label>X1=<input type="number" class="flat maxwidth50" id="x" name="x" /></label>
		         &nbsp; <label>Y1=<input type="number" class="flat maxwidth50" id="y" name="y" /></label>
		         &nbsp; <label>X2=<input type="number" class="flat maxwidth50" id="x2" name="x2" /></label>
		         &nbsp; <label>Y2=<input type="number" class="flat maxwidth50" id="y2" name="y2" /></label>
		         &nbsp; <label>W=<input type="number" class="flat maxwidth50" id="w" name="w" /></label>
		         &nbsp; <label>H=<input type="number" class="flat maxwidth50" id="h" name="h" /></label>
		      </div>

		      <input type="hidden" id="file" name="file" value="'.dol_escape_htmltag($original_file).'" />
		      <input type="hidden" id="action" name="action" value="confirm_crop" />
		      <input type="hidden" id="product" name="product" value="'.dol_escape_htmltag($id).'" />
		      <input type="hidden" id="dol_screenwidth" name="dol_screenwidth" value="'.($_SESSION['dol_screenwidth'] ?? 'null').'" />
		      <input type="hidden" id="refsizeforcrop" name="refsizeforcrop" value="'.$refsizeforcrop.'" />
		      <input type="hidden" id="ratioforcrop" name="ratioforcrop" value="'.$ratioforcrop.'" /><!-- value in field used by js/lib/lib_photoresize.js -->
		      <input type="hidden" id="imagewidth" name="imagewidth" value="'.$width.'" /><!-- value in field used by js/lib/lib_photoresize.js -->
		      <input type="hidden" id="imageheight" name="imageheight" value="'.$height.'" /><!-- value in field used by js/lib/lib_photoresize.js -->
	          <input type="hidden" name="modulepart" value="'.dol_escape_htmltag($modulepart).'" />
		      <input type="hidden" name="id" value="'.dol_escape_htmltag($id).'" />
		      <br>
		      <input type="submit" id="submitcrop" name="submitcrop" class="button" value="'.dol_escape_htmltag($langs->trans("Crop")).'" />
		      &nbsp;
		      <input type="submit" id="cancelcrop" name="cancel" class="button button-cancel" value="'.dol_escape_htmltag($langs->trans("Cancel")).'" />
		   </form>'."\n";
	} else {
		$langs->load("other");
		print '<div class="opacitymedium">'.$langs->trans("FeatureNotAvailableOnDevicesWithoutMouse").'</div>';
	}
	print '</fieldset>'."\n";
	print '<br>';
}

/* Check that mandatory fields are filled */
print '<script nonce="'.getNonce().'" type="text/javascript">
jQuery(document).ready(function() {
	$("#submitcrop").click(function(e) {
        console.log("We click on submitcrop");
	    var idClicked = e.target.id;
	    if (parseInt(jQuery(\'#w\').val())) return true;
	    alert(\''.dol_escape_js($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Size"))).'\');
	    return false;
	});
});
</script>';

llxFooter();
$db->close();
