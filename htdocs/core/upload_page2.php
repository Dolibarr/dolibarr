<?php
/* Copyright (C) 2005-2017  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2024-2025  Frédéric France			<frederic.france@free.fr>
 * Copyright (C) 2024		MDW						<mdeweerd@users.noreply.github.com>
 *
 * This file is a modified version of datepicker.php from phpBSM to fix some
 * bugs, to add new features and to dramatically increase speed.
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
 *       \file       htdocs/core/upload_page2.php
 *       \brief      Page to show a generic upload file feature
 */

require_once '../main.inc.php';
/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';


if (GETPOST('lang', 'aZ09')) {
	$langs->setDefaultLang(GETPOST('lang', 'aZ09')); // If language was forced on URL by the main.inc.php
}

$langs->loadLangs(array("main", "other"));

$action = GETPOST('action', 'aZ09');
$modulepart = GETPOST('modulepart', 'aZ09');

$upload_dir = $conf->admin->dir_temp.'/import';

// Delete the temporary files that are used when uploading files
//dol_delete_file($upload_dir.'/upload_page-by'.$user->id.'-*');

$file = GETPOST('file');

$reg = array();
if (preg_match('/^upload_page-by(\d+)-([a-z_]+)-(\d+)/', $file, $reg)) {
	$modulepart = $reg[2];

	if ($reg[1] != $user->id) {
		accessforbidden('User id for file to process does not match current user id');
	}
} else {
	accessforbidden('Bad value for file parameter');
}


/*
 * Actions
 */

//



/*
 * View
 */

//$form = new Form($db);

$title = $langs->trans("UploadFile");
$help_url = '';

$arrayofjs = array();
$arrayofcss = array();

llxHeader('', $title, $help_url, '', 0, 0, $arrayofjs, $arrayofcss, '', 'mod-upload page-card');
//top_htmlhead($head, $title, 0, 0, $arrayofjs, $arrayofcss);

print load_fiche_titre('', '', '', 0, '', '', '<h2>'.img_picto('', 'upload').' '.$title.'</h2>');

// Show all forms
print "\n";
print "<!-- Begin Form -->\n";
print '<form id="uploadform" enctype="multipart/form-data" method="POST" action="'.dolBuildUrl($_SERVER["PHP_SELF"]).'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="uploadfile">';
print '<input type="hidden" name="sendit" value="1">';
print '<input type="hidden" name="modulepart" id="modulepart" value="">';
print '<input type="hidden" name="overwritefile" value="1">';

print "<script>
$(document).ready(function() {

});
</script>";

print $file;


print '</form>';
print "\n<!-- End Form -->\n";



// End of page
llxFooter();
$db->close();
