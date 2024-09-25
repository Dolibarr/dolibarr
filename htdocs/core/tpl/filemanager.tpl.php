<?php
/* Copyright (C) 2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024		Frédéric France			<frederic.france@free.fr>
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
 *
 * Output code for the filemanager
 * $module must be defined ('ecm', 'medias', ...)
 * $formalreadyopen can be set to 1 to avoid to open the <form> to submit files a second time
 */

// Protection to avoid direct call of template
if (empty($conf) || !is_object($conf)) {
	print "Error, template page filemanager.tpl.php can't be called as URL";
	exit;
}

?>

<!-- BEGIN PHP TEMPLATE core/tpl/filemanager.tpl.php -->
<!-- Doc of fileTree plugin at https://www.abeautifulsite.net/jquery-file-tree -->

<?php

require_once DOL_DOCUMENT_ROOT.'/ecm/class/ecmdirectory.class.php';

$langs->load("ecm");

if (empty($module)) {
	$module = 'ecm';
}

'@phan-var-force WebSite $website';

$permtoadd = 0;
$permtoupload = 0;
$showroot = 0;
if ($module == 'ecm') {
	$permtoadd = $user->hasRight("ecm", "setup");
	$permtoupload = $user->hasRight("ecm", "upload");
	$showroot = 0;
}
if ($module == 'medias') {
	$permtoadd = ($user->hasRight("mailing", "creer") || $user->hasRight("website", "write"));
	$permtoupload = ($user->hasRight("mailing", "creer") || $user->hasRight("website", "write"));
	$showroot = 1;
}

if (!isset($section)) {
	$section = 0;
}

// Confirm remove file (for non javascript users)
if (($action == 'delete' || $action == 'file_manager_delete') && empty($conf->use_javascript_ajax)) {
	// TODO Add website, pageid, filemanager if defined
	print $form->formconfirm($_SERVER["PHP_SELF"].'?section='.$section.'&urlfile='.urlencode(GETPOST("urlfile")), $langs->trans('DeleteFile'), $langs->trans('ConfirmDeleteFile'), 'confirm_deletefile', '', '', 1);
}

// Start container of all panels
?>
<!-- Begin div id="containerlayout" -->
<div id="containerlayout">
<div id="ecm-layout-north" class="toolbar largebutton">
<?php

// Start top panel, toolbar
print '<div class="inline-block toolbarbutton centpercent">';

// Toolbar
if ($permtoadd) {
	$websitekeyandpageid = (!empty($websitekey) ? '&website='.urlencode($websitekey) : '').(!empty($pageid) ? '&pageid='.urlencode((string) $pageid) : '');
	print '<a id="acreatedir" href="'.DOL_URL_ROOT.'/ecm/dir_add_card.php?action=create&module='.urlencode($module).$websitekeyandpageid.'&backtopage='.urlencode($_SERVER["PHP_SELF"].'?file_manager=1'.$websitekeyandpageid).'" class="inline-block valignmiddle toolbarbutton paddingtop" title="'.dol_escape_htmltag($langs->trans('ECMAddSection')).'">';
	print img_picto('', 'folder-plus', '', 0, 0, 0, '', 'size15x marginrightonly');
	print '</a>';
} else {
	print '<a id="acreatedir" href="#" class="inline-block valignmiddle toolbarbutton paddingtop" title="'.$langs->trans("NotAllowed").'">';
	print img_picto('', 'folder-plus', 'disabled', 0, 0, 0, '', 'size15x marginrightonly');
	print '</a>';
}
if ($module == 'ecm') {
	$tmpurl = ((!empty($conf->use_javascript_ajax) && !getDolGlobalString('MAIN_ECM_DISABLE_JS')) ? '#' : ($_SERVER["PHP_SELF"].'?action=refreshmanual'.($module ? '&amp;module='.$module : '').($section ? '&amp;section='.$section : '')));
	print '<a id="arefreshbutton" href="'.$tmpurl.'" class="inline-block valignmiddle toolbarbutton paddingtop" title="'.dol_escape_htmltag($langs->trans('ReSyncListOfDir')).'">';
	print img_picto('', 'refresh', 'id="refreshbutton"', 0, 0, 0, '', 'size15x marginrightonly');
	print '</a>';
}
if ($permtoadd && GETPOSTISSET('website')) {	// If on file manager to manage medias of a web site
	print '<a id="agenerateimgwebp" href="'.$_SERVER["PHP_SELF"].'?action=confirmconvertimgwebp&token='.newToken().'&website='.urlencode($website->ref).'" class="inline-block valignmiddle toolbarbutton paddingtop" title="'.dol_escape_htmltag($langs->trans("GenerateImgWebp")).'">';
	print img_picto('', 'images', '', 0, 0, 0, '', 'size15x flip marginrightonly');
	print '</a>';
} elseif ($permtoadd && $module == 'ecm') {	// If on file manager medias in ecm
	if (getDolGlobalInt('ECM_SHOW_GENERATE_WEBP_BUTTON')) {
		print '<a id="agenerateimgwebp" href="'.$_SERVER["PHP_SELF"].'?action=confirmconvertimgwebp&token='.newToken().'" class="inline-block valignmiddle toolbarbutton paddingtop" title="'.dol_escape_htmltag($langs->trans("GenerateImgWebp")).'">';
		print img_picto('', 'images', '', 0, 0, 0, '', 'size15x flip marginrightonly');
		print '</a>';
	}
}

print "<script>
$('#acreatedir').on('click', function() {
	try{
		section_dir = $('.directory.expanded')[$('.directory.expanded').length-1].children[0].rel;
		section = $('.directory.expanded')[$('.directory.expanded').length-1].children[0].id.split('_')[2];
		catParent = ";
if ($module == 'ecm') {
	print "section;";
} else {
	print "section_dir.substring(0, section_dir.length - 1);";
}
print "
	} catch{
		section_dir = '/';
		section = 0;
		catParent = ";
if ($module == 'ecm') {
	print "section;";
} else {
	print "section_dir;";
}
print "
	}
	console.log('We click to create a new directory, we set current section_dir='+section_dir+' into href url of button acreatedir');
	$('#acreatedir').attr('href', $('#acreatedir').attr('href')+'%26section_dir%3D'+encodeURI(section_dir)+'%26section%3D'+encodeURI(section)+'&section_dir='+encodeURI(section_dir)+'&section='+encodeURI(section)+'&catParent='+encodeURI(catParent));
	console.log($('#acreatedir').attr('href'));
});
$('#agenerateimgwebp').on('click', function() {
	try{
		section_dir = $('.directory.expanded')[$('.directory.expanded').length-1].children[0].rel;
		section = $('.directory.expanded')[$('.directory.expanded').length-1].children[0].id.split('_')[2];
	} catch{
		section_dir = '/';
		section = 0;
	}
	console.log('We click to generate webp image, we set current section_dir='+section_dir+' into href url of button agenerateimgwebp');
	$('#agenerateimgwebp').attr('href', $('#agenerateimgwebp').attr('href')+'&section_dir='+encodeURI(section_dir)+'&section='+encodeURI(section));
	console.log($('#agenerateimgwebp').attr('href'));
});
</script>";

// Start "Add new file" area
$nameforformuserfile = 'formuserfileecm';

print '<div class="inline-block valignmiddle floatright">';

// Zone to attach a new file
if ((!empty($conf->use_javascript_ajax) && !getDolGlobalString('MAIN_ECM_DISABLE_JS')) || !empty($section)) {
	if ((empty($section) || $section == -1) && ($module != 'medias')) {
		?>
		<script>
		jQuery(document).ready(function() {
			jQuery('#<?php echo $nameforformuserfile ?>').hide();
		});
		</script>
		<?php
	}

	$sectiondir = GETPOST('file', 'alpha') ? GETPOST('file', 'alpha') : GETPOST('section_dir', 'alpha');

	print '<!-- Start form to attach new file in filemanager.tpl.php sectionid='.$section.' sectiondir='.$sectiondir.' -->'."\n";
	include_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
	$formfile = new FormFile($db);
	print $formfile->form_attach_new_file($_SERVER["PHP_SELF"], 'none', 0, ($section ? $section : -1), $permtoupload, 48, null, '', 0, '', 0, $nameforformuserfile, '', $sectiondir, empty($formalreadyopen) ? 0 : $formalreadyopen, 0, 0, 1);
} else {
	print '&nbsp;';
}

print '</div>';
// End "Add new file" area


print '</div>';
// End top panel, toolbar

?>
</div>
<div id="ecm-layout-west" class="inline-block">
<?php
// Start left area


// Ask confirmation of deletion of directory
if ($action == 'delete_section') {
	print $form->formconfirm($_SERVER["PHP_SELF"].'?section='.$section, $langs->trans('DeleteSection'), $langs->trans('ConfirmDeleteSection', $ecmdir->label), 'confirm_deletesection', '', '', 1);
}
// End confirm

// Ask confirmation to build webp images
if ($action == 'confirmconvertimgwebp') {
	$langs->load("ecm");

	$section_dir = GETPOST('section_dir', 'alpha');
	$section = GETPOST('section', 'alpha');
	$file = GETPOST('filetoregenerate', 'alpha');
	$form = new Form($db);
	$formquestion = array();
	$formquestion['section_dir'] = array('type' => 'hidden', 'value' => $section_dir, 'name' => 'section_dir');
	$formquestion['section'] = array('type' => 'hidden', 'value' => $section, 'name' => 'section');
	$formquestion['filetoregenerate'] = array('type' => 'hidden', 'value' => $file, 'name' => 'filetoregenerate');
	if ($module == 'medias') {
		$formquestion['website'] = array('type' => 'hidden', 'value' => $website->ref, 'name' => 'website');
	}
	$param = '';
	if (!empty($sortfield)) {
		$param .= '&sortfield='.urlencode($sortfield);
	}
	if (!empty($sortorder)) {
		$param .= '&sortorder='.urlencode($sortorder);
	}
	print $form->formconfirm($_SERVER["PHP_SELF"].($param ? '?'.$param : ''), empty($file) ? $langs->trans('ConfirmImgWebpCreation') : $langs->trans('ConfirmChosenImgWebpCreation'), empty($file) ? $langs->trans('ConfirmGenerateImgWebp') : $langs->trans('ConfirmGenerateChosenImgWebp', basename($file)), 'convertimgwebp', $formquestion, "yes", 1);
	$action = 'file_manager';
}

// Duplicate images into .webp
if ($action == 'convertimgwebp' && $permtoadd) {
	$file = GETPOST('filetoregenerate', 'alpha');

	if ($module == 'medias') {
		$imagefolder = $conf->website->dir_output.'/'.$websitekey.'/medias/'.dol_sanitizePathName(GETPOST('section_dir', 'alpha'));
	} else {
		$imagefolder = $conf->ecm->dir_output.'/'.dol_sanitizePathName(GETPOST('section_dir', 'alpha'));
	}

	include_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';

	if (!empty($file)) {
		$filelist = array();
		$filelist[]["fullname"] = dol_osencode($imagefolder.'/'.$file); // get $imagefolder.'/'.$file infos
	} else {
		$regeximgext = getListOfPossibleImageExt();

		$filelist = dol_dir_list($imagefolder, "files", 0, $regeximgext);
	}

	$nbconverted = 0;

	foreach ($filelist as $filename) {
		$filepath = $filename['fullname'];
		if (!(substr_compare($filepath, 'webp', -strlen('webp')) === 0)) {
			if (!empty($file) || !dol_is_file($filepathnoext.'.webp')) { // If file does not exists yet
				if (image_format_supported($filepath) == 1) {
					$filepathnoext = preg_replace("/\.[a-z0-9]+$/i", "", $filepath);
					$result = dol_imageResizeOrCrop($filepath, 0, 0, 0, 0, 0, $filepathnoext.'.webp', 90);
					if (!dol_is_file($result)) {
						$error++;
						setEventMessages($result, null, 'errors');
					} else {
						$nbconverted++;
					}
				}
			}
		}
		if ($error) {
			break;
		}
	}
	if (!$error) {
		if (!empty($file)) {
			setEventMessages($langs->trans('SucessConvertChosenImgWebp'), null);
		} else {
			setEventMessages($langs->trans('SucessConvertImgWebp'), null);
		}
	}
	$action = 'file_manager';
}

if (empty($action) || $action == 'editfile' || $action == 'file_manager' || preg_match('/refresh/i', $action) || $action == 'delete') {
	$langs->load("ecm");

	print '<table class="liste centpercent">'."\n";

	print '<!-- Title for manual directories -->'."\n";
	print '<tr class="liste_titre">'."\n";
	print '<th class="liste_titre left">';
	print '<span style="padding-left: 5px; padding-right: 5px;">'.$langs->trans("ECMSections").'</span>';
	print '</th></tr>';

	$showonrightsize = '';

	// Manual section
	$htmltooltip = $langs->trans("ECMAreaDesc2a");
	$htmltooltip .= '<br>'.$langs->trans("ECMAreaDesc2b");

	if (!empty($conf->use_javascript_ajax) && !getDolGlobalString('MAIN_ECM_DISABLE_JS')) {
		// Show the link to "Root"
		if ($showroot) {
			print '<tr class="oddeven nohover"><td><div style="padding-left: 5px; padding-right: 5px;"><a href="'.$_SERVER["PHP_SELF"].'?file_manager=1'.(!empty($websitekey) ? '&website='.urlencode($websitekey) : '').'&pageid='.urlencode((string) $pageid).'">';
			if ($module == 'medias') {
				print $langs->trans("RootOfMedias");
			} else {
				print $langs->trans("Root");
			}
			print '</a></div></td></tr>';
		}

		print '<tr class="oddeven nohover"><td>';

		// Show filemanager tree (will be filled by a call of ajax /ecm/tpl/enablefiletreeajax.tpl.php, later, that executes ajaxdirtree.php)
		print '<div id="filetree" class="ecmfiletree"></div>';

		if ($action == 'deletefile') {
			print $form->formconfirm('eeeee', $langs->trans('DeleteFile'), $langs->trans('ConfirmDeleteFile'), 'confirm_deletefile', '', '', 'deletefile');
		}

		print '</td></tr>';
	} else {
		// Show filtree when ajax is disabled (rare)
		print '<tr><td style="padding-left: 20px">';

		$_POST['modulepart'] = $module;
		$_POST['openeddir'] = GETPOST('openeddir');
		$_POST['dir'] = empty($_POST['dir']) ? '/' : GETPOST('dir');

		// Show filemanager tree (will be filled by direct include of ajaxdirtree.php in mode noajax, this will return all dir - all levels - to show)
		print '<div id="filetree" class="ecmfiletree">';

		// Variables that may be defined:
		// $_GET['modulepart'], $_GET['openeddir'], $_GET['sortfield'], $_GET['sortorder']
		// $_POST['dir']
		$mode = 'noajax';
		if (empty($url)) {
			$url = DOL_URL_ROOT.'/ecm/index.php';
		}
		include DOL_DOCUMENT_ROOT.'/core/ajax/ajaxdirtree.php';

		print '</div>';
		print '</td></tr>';
	}


	print "</table>";
}


// End left panel
?>
</div>
<div id="ecm-layout-center" class="inline-block">
<div class="pane-in ecm-in-layout-center">
<div id="ecmfileview" class="ecmfileview">
<?php
// Start right panel - List of content of a directory

$mode = 'noajax';
if (empty($url)) {	// autoset $url but it is better to have it defined before (for example by ecm/index.php, ecm/index_medias.php, website/index.php)
	if (!empty($module) && $module == 'medias' && !GETPOST('website')) {
		$url = DOL_URL_ROOT.'/ecm/index_medias.php';
	} elseif (GETPOSTISSET('website')) {
		$url = DOL_URL_ROOT.'/website/index.php';
	} else {
		$url = DOL_URL_ROOT.'/ecm/index.php';
	}
}
include DOL_DOCUMENT_ROOT.'/core/ajax/ajaxdirpreview.php'; // Show content of a directory on right side


// End right panel
?>
</div>
</div>

</div>
</div> <!-- End div id="containerlayout" -->
<?php


if (!empty($conf->use_javascript_ajax) && !getDolGlobalString('MAIN_ECM_DISABLE_JS')) { // Show filtree when ajax is enabled
	//var_dump($modulepart);
	// Variables that may be defined:
	// $_GET['modulepart'], $_GET['openeddir'], $_GET['sortfield'], $_GET['sortorder']
	// $_POST['dir']
	// $_POST['section_dir'], $_POST['section_id'], $_POST['token'], $_POST['max_file_size'], $_POST['sendit']
	if (GETPOST('section_dir', 'alpha')) {
		$preopened = GETPOST('section_dir', 'alpha');
	}

	include DOL_DOCUMENT_ROOT.'/ecm/tpl/enablefiletreeajax.tpl.php';
}

?>
<!-- END PHP TEMPLATE core/tpl/filemanager.tpl.php -->
