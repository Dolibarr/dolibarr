<?php
/* Copyright (C) 2001-2007	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2019	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2017	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2010-2014	Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2011-2017	Philippe Grand			<philippe.grand@atoo-net.com>
 * Copyright (C) 2015		Alexandre Spangaro		<aspangaro@open-dsi.fr>
 * Copyright (C) 2017       Rui Strecht			    <rui.strecht@aliartalentos.com>
 * Copyright (C) 2020       Frédéric France         <frederic.france@netlogic.fr>
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
 *	\file       htdocs/admin/company_socialnetworks.php
 *	\ingroup    company
 *	\brief      Setup page to configure company social networks
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

$action = GETPOST('action', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'admincompany'; // To manage different context of search

// Load translation files required by the page
$langs->loadLangs(array('admin', 'companies'));

if (!$user->admin) {
	accessforbidden();
}
$listofnetworks = getArrayOfSocialNetworks();

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('adminsocialnetworkscompany', 'globaladmin'));

/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}
if (($action == 'update' && !GETPOST("cancel", 'alpha'))) {
	foreach ($listofnetworks as $key => $value) {
		if (!empty($value['active'])) {
			$networkconstname = 'MAIN_INFO_SOCIETE_'.strtoupper($key).'_URL';
			$networkconstid = 'MAIN_INFO_SOCIETE_'.strtoupper($key);
			if (GETPOSTISSET($key.'url') && GETPOST($key.'url', 'alpha') != '') {
				dolibarr_set_const($db, $networkconstname, GETPOST($key.'url', 'alpha'), 'chaine', 0, '', $conf->entity);
				dolibarr_set_const($db, $networkconstid, GETPOST($key, 'alpha'), 'chaine', 0, '', $conf->entity);
			} elseif (GETPOSTISSET($key) && GETPOST($key, 'alpha') != '') {
				if (!empty($listofnetworks[$key]['url'])) {
					$url = str_replace('{socialid}', GETPOST($key, 'alpha'), $listofnetworks[$key]['url']);
					dolibarr_set_const($db, $networkconstname, $url, 'chaine', 0, '', $conf->entity);
				}
				dolibarr_set_const($db, $networkconstid, GETPOST($key, 'alpha'), 'chaine', 0, '', $conf->entity);
			} else {
				dolibarr_del_const($db, $networkconstname, $conf->entity);
				dolibarr_del_const($db, $networkconstid, $conf->entity);
			}
		}
	}

	setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
}


/*
 * View
 */

$form = new Form($db);

$wikihelp = 'EN:First_setup|FR:Premiers_paramétrages|ES:Primeras_configuraciones';
llxHeader('', $langs->trans("Setup"), $wikihelp, '', 0, 0, '', '', '', 'mod-admin page-company_socialnetworks');

print load_fiche_titre($langs->trans("CompanyFoundation"), '', 'title_setup');

$head = company_admin_prepare_head();

print dol_get_fiche_head($head, 'socialnetworks', '', -1, '');

print '<span class="opacitymedium">'.$langs->trans("CompanyFundationDesc", $langs->transnoentities("Save"))."</span><br>\n";
print '<span class="opacitymedium">'.$langs->trans("MoreNetworksAvailableWithModule")."</span><br>\n";
print "<br>\n";


/**
 * Edit parameters
 */

print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="update">';


print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent editmode">';
print '<tr class="liste_titre">';
print '<td class="titlefieldcreate">'.$langs->trans("SocialNetworksInformation").'</td>';
print '<td>'.$langs->trans("SocialNetworkId").'</td>';
print '<td>'.$form->textwithpicto($langs->trans("Url"), $langs->trans("KeepEmptyToUseDefault")).'</td>';
print '<td></td>';
print "</tr>\n";

$listofnetworks = dol_sort_array($listofnetworks, 'label');
//var_dump($listofnetworks);
foreach ($listofnetworks as $key => $value) {
	if (!empty($value['active'])) {
		print '<tr class="oddeven">';
		print '<td><label for="'.$key.'url">'.$langs->trans(ucfirst($key)).'</label></td>';
		$networkconstname = 'MAIN_INFO_SOCIETE_'.strtoupper($key).'_URL';
		$networkconstid = 'MAIN_INFO_SOCIETE_'.strtoupper($key);
		print '<td class="nowraponall"><span class="paddingright fab '.($value['icon'] ? $value['icon'] : 'fa-link').'"></span>';
		print '<input name="'.$key.'" id="'.$key.'" class="minwidth300" value="'.dol_escape_htmltag(getDolGlobalString($networkconstid)).'">';
		print '</td><td>';
		print '<input name="'.$key.'url" id="'.$key.'url" class="minwidth300" value="'.dol_escape_htmltag(getDolGlobalString($networkconstname)).'">';
		print '</td>';
		print '<td class="nowraponall">'.dol_print_socialnetworks(dol_escape_htmltag(getDolGlobalString($networkconstid)), 0, 0, $key, $listofnetworks).'</td>';
		print '</tr>'."\n";
	}
}

print "</table>";
print '</div>';


print $form->buttonsSaveCancel("Save", '');

print '</form>';


// End of page
llxFooter();
$db->close();
