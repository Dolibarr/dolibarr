<?php
/* Copyright (C) 2008-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2008-2010 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
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
 * You can call this page with param module=medias to get a filemanager for medias.
 */

/**
 *	\file       htdocs/ecm/index_medias.php
 *	\ingroup    ecm
 *	\brief      Main page for ECM section of public media directories area
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/ecm.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/treeview.lib.php';
require_once DOL_DOCUMENT_ROOT.'/ecm/class/ecmdirectory.class.php';

// Load translation files required by the page
$langs->loadLangs(array('ecm', 'companies', 'other', 'users', 'orders', 'propal', 'bills', 'contracts'));

// Get parameters
$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');

$socid = GETPOSTINT('socid');
$file_manager = GETPOST('file_manager', 'alpha');
$section = GETPOSTINT('section') ? GETPOSTINT('section') : GETPOSTINT('section_id');
if (!$section) {
	$section = 0;
}
$section_dir = GETPOST('section_dir', 'alpha');
$overwritefile = GETPOSTINT('overwritefile');

if (empty($action) && $file_manager) {
	$action = 'file_manager';
}
$pageid  = GETPOSTINT('pageid');

$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
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


$ecmdir = new EcmDirectory($db);
if ($section > 0) {
	$result = $ecmdir->fetch($section);
	if (!($result > 0)) {
		dol_print_error($db, $ecmdir->error);
		exit;
	}
}

$form = new Form($db);
$ecmdirstatic = new EcmDirectory($db);
$userstatic = new User($db);

$error = 0;

// Security check
if ($user->socid) {
	$socid = $user->socid;
}
$result = restrictedArea($user, 'ecm', 0);

$permissiontouploadfile = ($user->hasRight('ecm', 'setup') || $user->hasRight('mailing', 'creer') || $user->hasRight('website', 'write'));
$diroutput = $conf->medias->multidir_output[$conf->entity];

$relativepath = $section_dir;
$upload_dir = preg_replace('/\/$/', '', $diroutput).'/'.preg_replace('/^\//', '', $relativepath);

$websitekey = '';

$permissiontoadd = $permissiontouploadfile;	// Used by the include of actions_addupdatedelete.inc.php and actions_linkedfiles

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('ecmmediascard', 'globalcard'));

/*
 *	Actions
 */

$savbacktopage = $backtopage;
$backtopage = $_SERVER["PHP_SELF"].'?file_manager=1&website='.urlencode((string) ($websitekey)).'&pageid='.urlencode((string) ($pageid)).(GETPOST('section_dir', 'alpha') ? '&section_dir='.urlencode((string) (GETPOST('section_dir', 'alpha'))) : ''); // used after a confirm_deletefile into actions_linkedfiles.inc.php
if ($sortfield) {
	$backtopage .= '&sortfield='.urlencode($sortfield);
}
if ($sortorder) {
	$backtopage .= '&sortorder='.urlencode($sortorder);
}
include DOL_DOCUMENT_ROOT.'/core/actions_linkedfiles.inc.php';	// This manage 'sendit', 'confirm_deletefile', 'renamefile' action when submitting new file.

$backtopage = $savbacktopage;

if ($action == 'renamefile') {	// Must be after include DOL_DOCUMENT_ROOT.'/core/actions_linkedfiles.inc.php'; If action were renamefile, we set it to 'file_manager'
	$action = 'file_manager';
}


// Add directory
if ($action == 'add' && $permissiontouploadfile) {
	$ecmdir->ref                = 'NOTUSEDYET';
	$ecmdir->label              = GETPOST("label");
	$ecmdir->description        = GETPOST("desc");

	$id = $ecmdir->create($user);
	if ($id > 0) {
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	} else {
		setEventMessages('Error '.$langs->trans($ecmdir->error), null, 'errors');
		$action = "create";
	}

	clearstatcache();
}

// Remove directory
if ($action == 'confirm_deletesection' && GETPOST('confirm', 'alpha') == 'yes') {
	$result = $ecmdir->delete($user);
	setEventMessages($langs->trans("ECMSectionWasRemoved", $ecmdir->label), null, 'mesgs');

	clearstatcache();
}

// Refresh directory view
// This refresh list of dirs, not list of files (for performance reason). List of files is refresh only if dir was not synchronized.
// To refresh content of dir with cache, just open the dir in edit mode.
if ($action == 'refreshmanual') {
	$ecmdirtmp = new EcmDirectory($db);

	// This part of code is same than into file ecm/ajax/ecmdatabase.php TODO Remove duplicate
	clearstatcache();

	$diroutputslash = str_replace('\\', '/', $conf->ecm->dir_output);
	$diroutputslash .= '/';

	// Scan directory tree on disk
	$disktree = dol_dir_list($conf->ecm->dir_output, 'directories', 1, '', '^temp$', '', 0, 0);

	// Scan directory tree in database
	$sqltree = $ecmdirstatic->get_full_arbo(0);

	$adirwascreated = 0;

	// Now we compare both trees to complete missing trees into database
	//var_dump($disktree);
	//var_dump($sqltree);
	foreach ($disktree as $dirdesc) {    // Loop on tree onto disk
		$dirisindatabase = 0;
		foreach ($sqltree as $dirsqldesc) {
			if ($conf->ecm->dir_output.'/'.$dirsqldesc['fullrelativename'] == $dirdesc['fullname']) {
				$dirisindatabase = 1;
				break;
			}
		}

		if (!$dirisindatabase) {
			$txt = "Directory found on disk ".$dirdesc['fullname'].", not found into database so we add it";
			dol_syslog($txt);
			//print $txt."<br>\n";

			// We must first find the fk_parent of directory to create $dirdesc['fullname']
			$fk_parent = -1;
			$relativepathmissing = str_replace($diroutputslash, '', $dirdesc['fullname']);
			$relativepathtosearchparent = $relativepathmissing;
			//dol_syslog("Try to find parent id for directory ".$relativepathtosearchparent);
			if (preg_match('/\//', $relativepathtosearchparent)) {
				//while (preg_match('/\//',$relativepathtosearchparent))
				$relativepathtosearchparent = preg_replace('/\/[^\/]*$/', '', $relativepathtosearchparent);
				$txt = "Is relative parent path ".$relativepathtosearchparent." for ".$relativepathmissing." found in sql tree ?";
				dol_syslog($txt);
				//print $txt." -> ";
				$parentdirisindatabase = 0;
				foreach ($sqltree as $dirsqldesc) {
					if ($dirsqldesc['fullrelativename'] == $relativepathtosearchparent) {
						$parentdirisindatabase = $dirsqldesc['id'];
						break;
					}
				}
				if ($parentdirisindatabase > 0) {
					dol_syslog("Yes with id ".$parentdirisindatabase);
					//print "Yes with id ".$parentdirisindatabase."<br>\n";
					$fk_parent = $parentdirisindatabase;
					//break;  // We found parent, we can stop the while loop
				} else {
					dol_syslog("No");
					//print "No<br>\n";
				}
			} else {
				dol_syslog("Parent is root");
				$fk_parent = 0; // Parent is root
			}

			if ($fk_parent >= 0) {
				$ecmdirtmp->ref                = 'NOTUSEDYET';
				$ecmdirtmp->label              = dol_basename($dirdesc['fullname']);
				$ecmdirtmp->description        = '';
				$ecmdirtmp->fk_parent          = $fk_parent;

				$txt = "We create directory ".$ecmdirtmp->label." with parent ".$fk_parent;
				dol_syslog($txt);
				//print $ecmdirtmp->cachenbofdoc."<br>\n";exit;
				$id = $ecmdirtmp->create($user);
				if ($id > 0) {
					$newdirsql = array('id' => $id,
									 'id_mere' => $ecmdirtmp->fk_parent,
									 'label' => $ecmdirtmp->label,
									 'description' => $ecmdirtmp->description,
									 'fullrelativename' => $relativepathmissing);
					$sqltree[] = $newdirsql; // We complete fulltree for following loops
					//var_dump($sqltree);
					$adirwascreated = 1;
				} else {
					dol_syslog("Failed to create directory ".$ecmdirtmp->label, LOG_ERR);
				}
			} else {
				$txt = "Parent of ".$dirdesc['fullname']." not found";
				dol_syslog($txt);
				//print $txt."<br>\n";
			}
		}
	}

	// Loop now on each sql tree to check if dir exists
	foreach ($sqltree as $dirdesc) {    // Loop on each sqltree to check dir is on disk
		$dirtotest = $conf->ecm->dir_output.'/'.$dirdesc['fullrelativename'];
		if (!dol_is_dir($dirtotest)) {
			$ecmdirtmp->id = $dirdesc['id'];
			$ecmdirtmp->delete($user, 'databaseonly');
			//exit;
		}
	}

	$sql = "UPDATE ".MAIN_DB_PREFIX."ecm_directories set cachenbofdoc = -1 WHERE cachenbofdoc < 0"; // If pb into cache counting, we set to value -1 = "unknown"
	dol_syslog("sql = ".$sql);
	$db->query($sql);

	// If a directory was added, the fulltree array is not correctly completed and sorted, so we clean
	// it to be sure that fulltree array is not used without reloading it.
	if ($adirwascreated) {
		$sqltree = null;
	}
}



/*
 *	View
 */

// Define height of file area (depends on $_SESSION["dol_screenheight"])
//print $_SESSION["dol_screenheight"];
$maxheightwin = (isset($_SESSION["dol_screenheight"]) && $_SESSION["dol_screenheight"] > 466) ? ($_SESSION["dol_screenheight"] - 136) : 660; // Also into index_auto.php file

$moreheadcss = '';
$moreheadjs = '';

//$morejs=array();
$morejs = array('includes/jquery/plugins/blockUI/jquery.blockUI.js', 'core/js/blockUI.js'); // Used by ecm/tpl/enabledfiletreeajax.tpl.pgp
if (!getDolGlobalString('MAIN_ECM_DISABLE_JS')) {
	$morejs[] = "includes/jquery/plugins/jqueryFileTree/jqueryFileTree.js";
}

$moreheadjs .= '<script type="text/javascript">'."\n";
$moreheadjs .= 'var indicatorBlockUI = \''.DOL_URL_ROOT."/theme/".$conf->theme."/img/working.gif".'\';'."\n";
$moreheadjs .= '</script>'."\n";

llxHeader($moreheadcss.$moreheadjs, $langs->trans("ECMArea"), '', '', 0, 0, $morejs, '', 0, 'mod-ecm page-index_medias');

$head = ecm_prepare_dasboard_head(null);
print dol_get_fiche_head($head, 'index_medias', '', -1, '');


// Add filemanager component
$module = 'medias';
if (empty($url)) {
	$url = DOL_URL_ROOT.'/ecm/index_medias.php'; // Must be an url without param
}
include DOL_DOCUMENT_ROOT.'/core/tpl/filemanager.tpl.php';

// End of page
print dol_get_fiche_end();

llxFooter();

$db->close();
