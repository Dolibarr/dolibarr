<?php
/* Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	    \file       htdocs/admin/website.php
 *		\ingroup    setup
 *		\brief      Page to administer web sites
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formadmin.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/website.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
require_once DOL_DOCUMENT_ROOT.'/website/class/website.class.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by the page
$langs->loadLangs(array('errors', 'admin', 'companies', 'website'));

$action = GETPOST('action', 'alpha') ? GETPOST('action', 'alpha') : 'view';
$confirm = GETPOST('confirm', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');

// Load variable for pagination
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

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('admin'));

$arrayofparameters = array('WEBSITE_USE_WEBSITE_ACCOUNTS'=>array('css'=>'minwidth200'));

$status = 1;
$rowid = GETPOST('rowid', 'alpha');

if (!$user->admin || !isModEnabled('website')) {
	accessforbidden();
}

// Set this to 1 to use the factory to manage constants. Warning, the generated module will be compatible with version v15+ only
/*
$useFormSetup = 1;

if (!class_exists('FormSetup')) {
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formsetup.class.php';
}
$formSetup = new FormSetup($db);

$item = $formSetup->newItem('NO_PARAM_JUST_TEXT');
*/


/*
 * Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_setmoduleoptions.inc.php';


/*
 * View
 */

$form = new Form($db);
$formadmin = new FormAdmin($db);

llxHeader('', $langs->trans("WebsiteSetup"), '', '', 0, 0, '', '', '', 'mod-admin page-website_options');

$titre = $langs->trans("WebsiteSetup");
$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php').'">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($titre, $linkback, 'title_setup');

// Onglets
$head = array();
$h = 0;

$head[$h][0] = DOL_URL_ROOT."/admin/website.php";
$head[$h][1] = $langs->trans("WebSites");
$head[$h][2] = 'website';
$h++;

$head[$h][0] = DOL_URL_ROOT."/admin/website_options.php";
$head[$h][1] = $langs->trans("Options");
$head[$h][2] = 'options';
$h++;

print dol_get_fiche_head($head, 'options', '', -1);



print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameter").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="100">'.$langs->trans("Value").'</td>'."\n";
print '</tr>';


// Mail required for users

print '<tr class="oddeven">';
print '<td>';
print $form->textwithpicto($langs->trans('WEBSITE_USE_WEBSITE_ACCOUNTS'), $langs->trans('WEBSITE_USE_WEBSITE_ACCOUNTSTooltip'));
print '</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
if (!empty($conf->use_javascript_ajax)) {
	print ajax_constantonoff('WEBSITE_USE_WEBSITE_ACCOUNTS');
} else {
	if (!getDolGlobalString('WEBSITE_USE_WEBSITE_ACCOUNTS')) {
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_WEBSITE_USE_WEBSITE_ACCOUNTS&token='.newToken().'">'.img_picto($langs->trans("Disabled"), 'off').'</a>';
	} else {
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=del_WEBSITE_USE_WEBSITE_ACCOUNTS&token='.newToken().'">'.img_picto($langs->trans("Enabled"), 'on').'</a>';
	}
}
print '</td></tr>';


print '</table>';

if (empty($conf->use_javascript_ajax)) {
	print '<div class="tabsAction">';
	print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=edit&token='.newToken().'">'.$langs->trans("Modify").'</a>';
	print '</div>';
}

print dol_get_fiche_end();

// End of page
llxFooter();
$db->close();
