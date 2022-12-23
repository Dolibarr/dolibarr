<?php
/* Copyright (C) 2004-2017  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2019-2022  Frédéric France         <frederic.france@netlogic.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    htdocs/admin/company_tos.php
 * \ingroup company
 * \brief   company tos setup page.
 */

// Load Dolibarr environment
require '../main.inc.php';

global $langs, $user;

// Libraries
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formadmin.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';

// Translations
$langs->load("admin");

// Access control
if (!$user->admin) {
	accessforbidden();
}

// Parameters
$action = GETPOST('action', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');

$formfile = new FormFile($db);
$formadmin = new FormAdmin($db);
$search_lang = GETPOST('search_lang', 'alpha');


/*
 * Actions
 */

include DOL_DOCUMENT_ROOT . '/core/actions_setmoduleoptions.inc.php';

if ($action == 'uploadtos') {
	if (!empty($_FILES['userfile'])) {
		$file_tmp = $_FILES['userfile']['tmp_name'][0];
		if (isset($_FILES['userfile']) && $_FILES['userfile']['error'][0] == 0) {
			if ($_FILES['userfile']['size'][0] <= (getDolGlobalInt('MAIN_UPLOAD_DOC') * 1024)) {
				$fileinfos = pathinfo($_FILES['userfile']['name'][0]);
				$extension_upload = $fileinfos['extension'];
				$extensions_allowed = array('pdf');
				if (in_array($extension_upload, $extensions_allowed)) {
					$upload_dir = $conf->mycompany->dir_output . '/'.getDolGlobalString('COMPANY_TOS_UPLOAD_DIR', 'tos').'/';
					if ($conf->global->MAIN_MULTILANGS && !empty($search_lang)) {
						$finaldirectory = $upload_dir . "/" . $search_lang . "/";
					} else {
						$finaldirectory = $upload_dir;
					}
					$res = dol_add_file_process($finaldirectory, 1, 0, 'userfile');
					if ($res) {
						$result = dolibarr_set_const($db, 'COMPANY_TOS_FILE' . ($search_lang ? '_' . $search_lang : ''), dol_sanitizeFileName(basename($_FILES['userfile']['name'][0])), 'chaine', 0, '', $conf->entity);
					} else {
						setEventMessage('TOSErrorUploadingFile', 'errors');
					}
				} else {
					setEventMessage('TOSErrorExtensionNotAllowedUploadingFile', 'errors');
				}
			} else {
				setEventMessage('TOSErrorTooBigUploadingFile', 'errors');
			}
		}
	}
}


/*
 * View
 */

$page_name = "CompanyTosSetup";
llxHeader('', $langs->trans($page_name));


// Subheader
$linkback = '<a href="' . ($backtopage ? $backtopage : DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1') . '">' . $langs->trans("BackToModuleList") . '</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, 'title_setup');

// Configuration header
$head = company_admin_prepare_head();
print dol_get_fiche_head($head, 'tosadmin', '', -1, "");

// Setup page goes here
print '<span class="opacitymedium">' . $langs->trans("TOSSetupPage") . '</span><br><br>';


$langs_available = $langs->get_available_languages(DOL_DOCUMENT_ROOT, 12);
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td class="titlefield" colspan="2">' . $langs->trans("TOSFiles") . '</td>';
print '</tr>';
print '</tr><td colspan="2">';
$option = $formadmin->select_language($search_lang, 'search_lang', 0, null, 1, 0, 0, 'maxwidth200');
$formfile->form_attach_new_file($_SERVER["PHP_SELF"] . '?action=uploadtos', '', 0, 0, 1, 50, '', $option, 1, '', 0, 'formuserfile', '', '', 0);
print '</td></tr>';
print '<tr>';
print '<td>' . $langs->trans('TOSFileNameByDefault') . ':</td><td> ' . getDolGlobalString('COMPANY_TOS_FILE') . '</td>';
print '</tr>';
foreach ($langs_available as $key => $value) {
	if (!empty(getDolGlobalString('COMPANY_TOS_FILE_' . $key))) {
		print '<tr>';
		print '<td>' . $langs->trans('TOSFileName') . ' ' . $value . ':</td><td> ' . getDolGlobalString('COMPANY_TOS_FILE_' . $key) . '</td>';
		print '</tr>';
	}
}
print "</table>";
print '<br>';

// Page end
print dol_get_fiche_end();

llxFooter();
$db->close();
