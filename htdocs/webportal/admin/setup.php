<?php
/* Copyright (C) 2023-2024 	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2023-2024	Lionel Vessiller		<lvessiller@easya.solutions>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
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
 * \file    htdocs/webportal/admin/setup.php
 * \ingroup webportal
 * \brief   WebPortal setup page.
 */

// Load Dolibarr environment
require_once "../../main.inc.php";
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once DOL_DOCUMENT_ROOT . "/webportal/lib/webportal.lib.php";

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Translations
$langs->loadLangs(array("admin", "webportal", "website"));

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('webportalsetup', 'globalsetup'));

// Parameters
$action = GETPOST('action', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');
$modulepart = GETPOST('modulepart', 'aZ09');    // Used by actions_setmoduleoptions.inc.php

$value = GETPOST('value', 'alpha');
$label = GETPOST('label', 'alpha');
$scandir = GETPOST('scan_dir', 'alpha');
$type = 'webportal';

$error = 0;
$setupnotempty = 0;

// Access control
if (!$user->admin) {
	accessforbidden();
}

// Set this to 1 to use the factory to manage constants. Warning, the generated module will be compatible with version v15+ only
$useFormSetup = 1;

if (!class_exists('FormSetup')) {
	require_once DOL_DOCUMENT_ROOT . '/core/class/html.formsetup.class.php';
}
$formSetup = new FormSetup($db);


// Add logged user
//$formSetup->newItem('WEBPORTAL_USER_LOGGED2')->setAsSelectUser();
// only enabled users
$userList = $formSetup->form->select_dolusers(getDolGlobalInt('WEBPORTAL_USER_LOGGED'), 'WEBPORTAL_USER_LOGGED', 1, null, 0, '', '', '0', 0, 0, '', 0, '', '', 1, 2);

$item = $formSetup->newItem('WEBPORTAL_USER_LOGGED');
$item->setAsSelect($userList);
$item->picto = 'user';
$item->helpText = $langs->transnoentities('WebPortalUserLoggedHelp');
// TODO Add a property mandatory to set style to "fieldrequired" and to add a check in submit


// root url

// @var	FormSetupItem	$item
$item = $formSetup->newItem('WEBPORTAL_ROOT_URL')->setAsString();
$item->nameText = $langs->transnoentities('UrlPublicInterfaceLabelAdmin');
$item->fieldAttr = array('placeholder' => 'https://');
$item->helpText = $langs->transnoentities('UrlPublicInterfaceHelpAdmin');
require_once __DIR__ . '/../class/context.class.php';
//$context = Context::getInstance();
//$item->fieldOutputOverride = '<a target="_blank" href="'.Context::getRootConfigUrl().'" >'.img_picto('', 'globe', 'class="pictofixedwidth"').Context::getRootConfigUrl().'</a>';


$formSetup->newItem('WEBPORTAL_TITLE')->defaultFieldValue = getDolGlobalString('MAIN_INFO_SOCIETE_NOM');


// Enable access for the proposals
if (isModEnabled('propal')) {
	$formSetup->newItem('WEBPORTAL_PROPAL_LIST_ACCESS')->setAsYesNo();
}

// Enable access for the orders
if (isModEnabled('order')) {
	$formSetup->newItem('WEBPORTAL_ORDER_LIST_ACCESS')->setAsYesNo();
}

// Enable access for the invoices
if (isModEnabled('invoice')) {
	$formSetup->newItem('WEBPORTAL_INVOICE_LIST_ACCESS')->setAsYesNo();
}

// Enable access for the partnership record
if (isModEnabled('partnership')) {
	$access_list = array(
		'hidden' => $langs->trans('WebPortalAccessHidden'),
		'visible' => $langs->trans('WebPortalAccessVisible'),
	);
	$item = $formSetup->newItem('WEBPORTAL_PARTNERSHIP_CARD_ACCESS');
	$item->setAsSelect($access_list);
	$item->helpText = $langs->transnoentities('WebPortalPartnerShipCardAccessHelp');
}

// Enable access for the membership record
if (isModEnabled('member')) {
	$access_list = array(
		'hidden' => $langs->trans('WebPortalAccessHidden'),
		'visible' => $langs->trans('WebPortalAccessVisible'),
		'edit' => $langs->trans('WebPortalAccessEdit'),
	);
	$item = $formSetup->newItem('WEBPORTAL_MEMBER_CARD_ACCESS');
	$item->setAsSelect($access_list);
	$item->helpText = $langs->transnoentities('WebPortalMemberCardAccessHelp');
}


$setupnotempty += count($formSetup->items);

$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);

$moduledir = 'webportal';
$myTmpObjects = array();
$myTmpObjects['webportal'] = array('label' => 'WebPortal', 'includerefgeneration' => 0, 'includedocgeneration' => 0, 'class' => 'WebPortal');

$tmpobjectkey = GETPOST('object', 'aZ09');
if ($tmpobjectkey && !array_key_exists($tmpobjectkey, $myTmpObjects)) {
	accessforbidden('Bad value for object. Hack attempt ?');
}


/*
 * Actions
 */

include DOL_DOCUMENT_ROOT . '/core/actions_setmoduleoptions.inc.php';

// Force always edit mode
if (empty($action) || $action == 'update') {
	$action = 'edit';
}


/*
 * View
 */

$form = new Form($db);

$help_url = '';
$title = "WebPortalSetup";

llxHeader('', $langs->trans($title), $help_url);

// Subheader
$linkback = '<a href="' . ($backtopage ? $backtopage : DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1') . '">' . $langs->trans("BackToModuleList") . '</a>';

print load_fiche_titre($langs->trans($title), $linkback, 'title_setup');

// Configuration header
$head = webportalAdminPrepareHead();
print dol_get_fiche_head($head, 'settings', $langs->trans($title), -1, "webportal");

print '<br>';

// URL For webportal
print img_picto('', 'globe').' <span class="opacitymedium">'.$langs->trans('WebPortalURL').'</span><br>';
if (isModEnabled('multicompany')) {
	$entity_qr = '?entity='.((int) $conf->entity);
} else {
	$entity_qr = '';
}

// Define $urlwithroot
$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
//$urlwithroot=DOL_MAIN_URL_ROOT;					// This is to use same domain name than current

print '<div class="urllink">';
print '<input type="text" id="publicurlmember" class="quatrevingtpercentminusx" value="'.$urlwithroot.'/public/webportal/index.php'.$entity_qr.'">';
print '<a target="_blank" rel="noopener noreferrer" href="'.$urlwithroot.'/public/webportal/index.php'.$entity_qr.'">'.img_picto('', 'globe', 'class="paddingleft"').'</a>';
print '</div>';
print ajax_autoselect('publicurlmember');
//print '<a target="_blank" href="'.Context::getRootConfigUrl().'" >'.img_picto('', 'globe', 'class="pictofixedwidth"').Context::getRootConfigUrl().'</a>';

// Setup page goes here
print info_admin($langs->trans("UserAccountForWebPortalAreInThirdPartyTabHelp"));

print '<br><br>';

if ($action == 'edit') {
	print $formSetup->generateOutput(true);
	print '<br>';
} elseif (!empty($formSetup->items)) {
	print $formSetup->generateOutput();
	print '<div class="tabsAction">';
	print '<a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?action=edit&token=' . newToken() . '">' . $langs->trans("Modify") . '</a>';
	print '</div>';
} else {
	print '<br>' . $langs->trans("NothingToSetup");
}

// Page end
print dol_get_fiche_end();

llxFooter();
$db->close();
