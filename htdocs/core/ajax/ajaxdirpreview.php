<?php
/* Copyright (C) 2004-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Simon Tosser         <simon@kornog-computing.com>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2010	   Pierre Morin         <pierre.morin@auguria.net>
 * Copyright (C) 2013      Marcos García        <marcosgdf@gmail.com>
 * Copyright (C) 2024      MDW                  <mdeweerd@users.noreply.github.com>
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
 *	\file       htdocs/core/ajax/ajaxdirpreview.php
 *  \brief      Service to return a HTML preview of a directory
 *  			Call of this service is made with URL:
 * 				ajaxdirpreview.php?mode=nojs&action=preview&module=ecm&section=0&file=xxx
 */

if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', 1); // Disables token renewal
}
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', '1');
}
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', '1');
}
if (!defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}

if (!isset($mode) || $mode != 'noajax') {    // For ajax call
	require_once '../../main.inc.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
	require_once DOL_DOCUMENT_ROOT.'/ecm/class/ecmdirectory.class.php';

	$action = GETPOST('action', 'aZ09');
	$file = urldecode(GETPOST('file', 'alpha'));
	$section = GETPOST("section", 'alpha');
	$module = GETPOST("module", 'alpha');
	$urlsource = GETPOST("urlsource", 'alpha');
	$search_doc_ref = GETPOST('search_doc_ref', 'alpha');

	$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
	$sortfield = GETPOST("sortfield", 'aZ09comma');
	$sortorder = GETPOST("sortorder", 'aZ09comma');
	$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
	if (empty($page) || $page == -1) {
		$page = 0;
	}     // If $page is not defined, or '' or -1
	$offset = $limit * $page;
	$pageprev = $page - 1;
	$pagenext = $page + 1;
	if (!$sortorder) {
		$sortorder = "ASC";
	}
	if (!$sortfield) {
		$sortfield = "name";
	}

	$rootdirfordoc = $conf->ecm->dir_output;

	$upload_dir = dirname(str_replace("../", "/", $rootdirfordoc.'/'.$file));

	$ecmdir = new EcmDirectory($db);
	if ($section > 0) {
		$result = $ecmdir->fetch($section);
		if (!($result > 0)) {
			//dol_print_error($db,$ecmdir->error);
			//exit;
		}
	}
} else {
	// For no ajax call
	$rootdirfordoc = $conf->ecm->dir_output;

	$ecmdir = new EcmDirectory($db);
	$relativepath = '';
	if ($section > 0) {
		$result = $ecmdir->fetch($section);
		if (!($result > 0)) {
			dol_print_error($db, $ecmdir->error);
			exit;
		}

		$relativepath = $ecmdir->getRelativePath(); // Example   'mydir/'
	} elseif (GETPOST('section_dir')) {
		$relativepath = GETPOST('section_dir');
	}
	//var_dump($section.'-'.GETPOST('section_dir').'-'.$relativepath);

	$upload_dir = $rootdirfordoc.'/'.$relativepath;
}

if (empty($url)) {	// autoset $url but it is better to have it defined before into filemanager.tpl.php (not possible when in auto tree)
	if (!empty($module) && $module == 'medias' && !GETPOST('website')) {
		$url = DOL_URL_ROOT.'/ecm/index_medias.php';
	} elseif (GETPOSTISSET('website')) {
		$url = DOL_URL_ROOT.'/website/index.php';
	} else {
		$url = DOL_URL_ROOT.'/ecm/index.php';
	}
}

// Load translation files required by the page
$langs->loadLangs(array("ecm", "companies", "other"));

if (empty($modulepart)) {
	$modulepart = $module;
}

// Security check
if ($user->socid > 0) {
	$socid = $user->socid;
}
// On interdit les remontees de repertoire ainsi que les pipe dans les noms de fichiers.
if (preg_match('/\.\./', $upload_dir) || preg_match('/[<>|]/', $upload_dir)) {
	dol_syslog("Refused to deliver file ".$upload_dir);
	// Do no show plain path in shown error message
	dol_print_error(null, $langs->trans("ErrorFileNameInvalid", $upload_dir));
	exit;
}
// Check permissions
if ($modulepart == 'ecm') {
	if (!$user->hasRight('ecm', 'read')) {
		accessforbidden();
	}
} elseif ($modulepart == 'medias' || $modulepart == 'website') {
	// Always allowed
} else {
	accessforbidden();
}


/*
 * Action
 */

// None



/*
 * View
 */

if (!isset($mode) || $mode != 'noajax') {
	// Ajout directives pour resoudre bug IE
	header('Cache-Control: Public, must-revalidate');
	header('Pragma: public');

	top_httphead();
}

$type = 'directory';

// This test if file exists should be useless. We keep it to find bug more easily
if (!dol_is_dir($upload_dir)) {
	//dol_mkdir($upload_dir);
	/*$langs->load("install");
	dol_print_error(0,$langs->trans("ErrorDirDoesNotExists",$upload_dir));
	exit;*/
}

print '<!-- ajaxdirpreview type='.$type.' module='.$module.' modulepart='.$modulepart.'-->'."\n";
//print '<!-- Page called with mode='.dol_escape_htmltag(isset($mode)?$mode:'').' type='.dol_escape_htmltag($type).' module='.dol_escape_htmltag($module).' url='.dol_escape_htmltag($url).' '.dol_escape_htmltag($_SERVER["PHP_SELF"]).'?'.dol_escape_htmltag($_SERVER["QUERY_STRING"]).' -->'."\n";

$param = ($sortfield ? '&sortfield='.urlencode($sortfield) : '').($sortorder ? '&sortorder='.urlencode($sortorder) : '');
if (!empty($websitekey)) {
	$param .= '&website='.urlencode($websitekey);
}
if (!empty($pageid)) {
	$param .= '&pageid='.((int) $pageid);
}


// Dir scan
if ($type == 'directory') {
	$formfile = new FormFile($db);

	$maxlengthname = 40;
	$excludefiles = array('^SPECIMEN\.pdf$', '^\.', '(\.meta|_preview.*\.png)$', '^temp$', '^payments$', '^CVS$', '^thumbs$');
	$sorting = (strtolower($sortorder) == 'desc' ? SORT_DESC : SORT_ASC);

	// Right area. If module is defined here, we are in automatic ecm.
	$automodules = array(
		'company',
		'invoice',
		'invoice_supplier',
		'propal',
		'supplier_proposal',
		'order',
		'order_supplier',
		'contract',
		'product',
		'tax',
		'tax-vat',
		'salaries',
		'project',
		'project_task',
		'fichinter',
		'user',
		'expensereport',
		'holiday',
		'recruitment-recruitmentcandidature',
		'banque',
		'chequereceipt',
		'mrp-mo'
	);

	$parameters = array('modulepart' => $module);
	$reshook = $hookmanager->executeHooks('addSectionECMAuto', $parameters);
	if ($reshook > 0 && is_array($hookmanager->resArray) && count($hookmanager->resArray) > 0) {
		$automodules[] = $hookmanager->resArray['module'];
	}

	// TODO change for multicompany sharing
	if ($module == 'company') {
		$upload_dir = $conf->societe->dir_output;
		$excludefiles[] = '^contact$'; // The subdir 'contact' contains files of contacts.
	} elseif ($module == 'invoice') {
		$upload_dir = $conf->facture->dir_output;
	} elseif ($module == 'invoice_supplier') {
		$upload_dir = $conf->fournisseur->facture->dir_output;
	} elseif ($module == 'propal') {
		$upload_dir = $conf->propal->dir_output;
	} elseif ($module == 'supplier_proposal') {
		$upload_dir = $conf->supplier_proposal->dir_output;
	} elseif ($module == 'order') {
		$upload_dir = $conf->commande->dir_output;
	} elseif ($module == 'order_supplier') {
		$upload_dir = $conf->fournisseur->commande->dir_output;
	} elseif ($module == 'contract') {
		$upload_dir = $conf->contrat->dir_output;
	} elseif ($module == 'product') {
		$upload_dir = $conf->product->dir_output;
	} elseif ($module == 'tax') {
		$upload_dir = $conf->tax->dir_output;
		$excludefiles[] = '^vat$'; // The subdir 'vat' contains files of vats.
	} elseif ($module == 'tax-vat') {
		$upload_dir = $conf->tax->dir_output.'/vat';
	} elseif ($module == 'salaries') {
		$upload_dir = $conf->salaries->dir_output;
	} elseif ($module == 'project') {
		$upload_dir = $conf->project->dir_output;
	} elseif ($module == 'project_task') {
		$upload_dir = $conf->project->dir_output;
	} elseif ($module == 'fichinter') {
		$upload_dir = $conf->ficheinter->dir_output;
	} elseif ($module == 'user') {
		$upload_dir = $conf->user->dir_output;
	} elseif ($module == 'expensereport') {
		$upload_dir = $conf->expensereport->dir_output;
	} elseif ($module == 'holiday') {
		$upload_dir = $conf->holiday->dir_output;
	} elseif ($module == 'recruitment-recruitmentcandidature') {
		$upload_dir = $conf->recruitment->dir_output.'/recruitmentcandidature';
	} elseif ($module == 'banque') {
		$upload_dir = $conf->bank->dir_output;
	} elseif ($module == 'chequereceipt') {
		$upload_dir = $conf->bank->dir_output.'/checkdeposits';
	} elseif ($module == 'mrp-mo') {
		$upload_dir = $conf->mrp->dir_output;
	} else {
		$parameters = array('modulepart' => $module);
		$reshook = $hookmanager->executeHooks('addSectionECMAuto', $parameters);
		if ($reshook > 0 && is_array($hookmanager->resArray) && count($hookmanager->resArray) > 0) {
			$upload_dir = $hookmanager->resArray['directory'];
		}
	}

	// Automatic list
	if (in_array($module, $automodules)) {
		$param .= '&module='.$module;
		if (isset($search_doc_ref) && $search_doc_ref != '') {
			$param .= '&search_doc_ref='.urlencode($search_doc_ref);
		}

		$textifempty = ($section ? $langs->trans("NoFileFound") : ($showonrightsize == 'featurenotyetavailable' ? $langs->trans("FeatureNotYetAvailable") : $langs->trans("NoFileFound")));

		$filter = preg_quote($search_doc_ref, '/');
		$filearray = dol_dir_list($upload_dir, "files", 1, $filter, $excludefiles, $sortfield, $sorting, 1);

		$perm = $user->hasRight('ecm', 'upload');

		$formfile->list_of_autoecmfiles($upload_dir, $filearray, $module, $param, 1, '', $perm, 1, $textifempty, $maxlengthname, $url, 1);
	} else {
		// Manual list
		if ($module == 'medias') {
			/*
			   $_POST is array like
			  'token' => string '062380e11b7dcd009d07318b57b71750' (length=32)
			  'action' => string 'file_manager' (length=12)
			  'website' => string 'template' (length=8)
			  'pageid' => string '124' (length=3)
			  'section_dir' => string 'mydir/' (length=3)
			  'section_id' => string '0' (length=1)
			  'max_file_size' => string '2097152' (length=7)
			  'sendit' => string 'Envoyer fichier' (length=15)
			 */
			$relativepath = GETPOST('file', 'alpha') ? GETPOST('file', 'alpha') : GETPOST('section_dir', 'alpha');
			if ($relativepath && $relativepath != '/') {
				$relativepath .= '/';
			}
			$upload_dir = $dolibarr_main_data_root.'/'.$module.'/'.$relativepath;
			if (GETPOSTISSET('website') || GETPOSTISSET('file_manager')) {
				$param .= '&file_manager=1';
				if (!preg_match('/website=/', $param) && GETPOST('website', 'alpha')) {
					$param .= '&website='.urlencode(GETPOST('website', 'alpha'));
				}
				if (!preg_match('/pageid=/', $param)) {
					$param .= '&pageid='.GETPOSTINT('pageid');
				}
				//if (!preg_match('/backtopage=/',$param)) $param.='&backtopage='.urlencode($_SERVER["PHP_SELF"].'?file_manager=1&website='.$websitekey.'&pageid='.$pageid);
			}
		} else {
			$relativepath = $ecmdir->getRelativePath();
			$upload_dir = $conf->ecm->dir_output.'/'.$relativepath;
		}

		// If $section defined with value 0
		if (($section === '0' || empty($section)) && ($module != 'medias')) {
			$filearray = array();
		} else {
			$filearray = dol_dir_list($upload_dir, "files", 0, '', array('^\.', '(\.meta|_preview.*\.png)$', '^temp$', '^CVS$'), $sortfield, $sorting, 1);
		}

		if ($section) {
			$param .= '&section='.$section;
			if (isset($search_doc_ref) && $search_doc_ref != '') {
				$param .= '&search_doc_ref='.urlencode($search_doc_ref);
			}

			$textifempty = $langs->trans('NoFileFound');
		} elseif ($section === '0') {
			if ($module == 'ecm') {
				$textifempty = '<br><div class="center"><span class="warning">'.$langs->trans("DirNotSynchronizedSyncFirst").'</span></div><br>';
			} else {
				$textifempty = $langs->trans('NoFileFound');
			}
		} else {
			$textifempty = ($showonrightsize == 'featurenotyetavailable' ? $langs->trans("FeatureNotYetAvailable") : $langs->trans("ECMSelectASection"));
		}

		if ($module == 'medias') {
			$useinecm = 6;
			$modulepart = 'medias';
			$perm = ($user->hasRight("website", "write") || $user->hasRight("emailing", "creer"));
			$title = 'none';
		} elseif ($module == 'ecm') { // DMS/ECM -> manual structure
			if ($user->hasRight("ecm", "read")) {
				// Buttons: Preview
				$useinecm = 2;
			}

			if ($user->hasRight("ecm", "upload")) {
				// Buttons: Preview + Delete
				$useinecm = 4;
			}

			if ($user->hasRight("ecm", "setup")) {
				// Buttons: Preview + Delete + Edit
				$useinecm = 5;
			}

			$perm = $user->hasRight("ecm", "upload");
			$modulepart = 'ecm';
			$title = ''; // Use default
		} else {
			$useinecm = 5;
			$modulepart = 'ecm';
			$perm = $user->hasRight("ecm", "upload");
			$title = ''; // Use default
		}

		// When we show list of files for ECM files, $filearray contains file list, and directory is defined with modulepart + section into $param
		// When we show list of files for a directory, $filearray ciontains file list, and directory is defined with modulepart + $relativepath
		// var_dump("section=".$section." title=".$title." modulepart=".$modulepart." useinecm=".$useinecm." perm(permtoeditline)=".$perm." relativepath=".$relativepath." param=".$param." url=".$url);
		$formfile->list_of_documents($filearray, '', $modulepart, $param, 1, $relativepath, $perm, $useinecm, $textifempty, $maxlengthname, $title, $url, 0, $perm, '', $sortfield, $sortorder);
	}
}



// Bottom of page
$useajax = 1;
if (!empty($conf->dol_use_jmobile)) {
	$useajax = 0;
}
if (empty($conf->use_javascript_ajax)) {
	$useajax = 0;
}
if (getDolGlobalString('MAIN_ECM_DISABLE_JS')) {
	$useajax = 0;
}

//$param.=($param?'?':'').(preg_replace('/^&/','',$param));

if ($useajax || $action == 'deletefile') {
	$urlfile = '';
	if ($action == 'deletefile') {
		$urlfile = GETPOST('urlfile', 'alpha');
	}

	if (empty($section_dir)) {
		$section_dir = GETPOST("file", "alpha");
	}
	$section_id = $section;

	require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';

	$form = new Form($db);
	$formquestion = array();
	$formquestion['urlfile'] = array('type' => 'hidden', 'value' => $urlfile, 'name' => 'urlfile'); // We must always put field, even if empty because it is filled by javascript later
	$formquestion['section'] = array('type' => 'hidden', 'value' => $section, 'name' => 'section'); // We must always put field, even if empty because it is filled by javascript later
	$formquestion['section_id'] = array('type' => 'hidden', 'value' => $section_id, 'name' => 'section_id'); // We must always put field, even if empty because it is filled by javascript later
	$formquestion['section_dir'] = array('type' => 'hidden', 'value' => $section_dir, 'name' => 'section_dir'); // We must always put field, even if empty because it is filled by javascript later
	$formquestion['sortfield'] = array('type' => 'hidden', 'value' => $sortfield, 'name' => 'sortfield'); // We must always put field, even if empty because it is filled by javascript later
	$formquestion['sortorder'] = array('type' => 'hidden', 'value' => $sortorder, 'name' => 'sortorder'); // We must always put field, even if empty because it is filled by javascript later
	if (!empty($action) && $action == 'file_manager') {
		$formquestion['file_manager'] = array('type' => 'hidden', 'value' => 1, 'name' => 'file_manager');
	}
	if (!empty($websitekey)) {
		$formquestion['website'] = array('type' => 'hidden', 'value' => $websitekey, 'name' => 'website');
	}
	if (!empty($pageid) && $pageid > 0) {
		$formquestion['pageid'] = array('type' => 'hidden', 'value' => $pageid, 'name' => 'pageid');
	}

	print $form->formconfirm($url, $langs->trans("DeleteFile"), $langs->trans("ConfirmDeleteFile"), 'confirm_deletefile', $formquestion, "no", ($useajax ? 'deletefile' : 0));
}

if ($useajax) {
	print '<!-- ajaxdirpreview.php: js to manage preview of doc -->'."\n";
	print '<script nonce="'.getNonce().'" type="text/javascript">';

	// Enable jquery handlers on new generated HTML objects (same code than into lib_footer.js.php)
	// Because the content is reloaded by ajax call, we must also reenable some jquery hooks
	// Wrapper to manage document_preview
	if ($conf->browser->layout != 'phone') {
		print "\n/* JS CODE TO ENABLE document_preview */\n";
		print '
                jQuery(document).ready(function () {
			        jQuery(".documentpreview").click(function () {
            		    console.log("We click on preview for element with href="+$(this).attr(\'href\')+" mime="+$(this).attr(\'mime\'));
            		    document_preview($(this).attr(\'href\'), $(this).attr(\'mime\'), \''.dol_escape_js($langs->transnoentities("Preview")).'\');
                		return false;
        			});
        		});
           ' . "\n";
	}

	// Enable jquery handlers button to delete files
	print 'jQuery(document).ready(function() {'."\n";
	print '  jQuery(".deletefilelink").click(function(e) { '."\n";
	print '    console.log("We click on button with class deletefilelink, param='.$param.', we set urlfile to "+jQuery(this).attr("rel"));'."\n";
	print '    jQuery("#urlfile").val(jQuery(this).attr("rel"));'."\n";
	//print '    jQuery("#section_dir").val(\'aaa\');'."\n";
	print '    jQuery("#dialog-confirm-deletefile").dialog("open");'."\n";
	print '    return false;'."\n";
	print '  });'."\n";
	print '});'."\n";
	print '</script>'."\n";
}

// Close db if mode is not noajax
if ((!isset($mode) || $mode != 'noajax') && is_object($db)) {
	$db->close();
}
