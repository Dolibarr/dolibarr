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

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

$action = GETPOST('action', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'admincompany'; // To manage different context of search

// Load translation files required by the page
$langs->loadLangs(array('admin', 'companies'));

if (!$user->admin) {
	accessforbidden();
}

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
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
	dolibarr_set_const($db, "MAIN_INFO_SOCIETE_FACEBOOK_URL", GETPOST("facebookurl", 'alpha'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_INFO_SOCIETE_TWITTER_URL", GETPOST("twitterurl", 'alpha'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_INFO_SOCIETE_LINKEDIN_URL", GETPOST("linkedinurl", 'alpha'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_INFO_SOCIETE_INSTAGRAM_URL", GETPOST("instagramurl", 'alpha'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_INFO_SOCIETE_YOUTUBE_URL", GETPOST("youtubeurl", 'alpha'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_INFO_SOCIETE_GITHUB_URL", GETPOST("githuburl", 'alpha'), 'chaine', 0, '', $conf->entity);
}


/*
 * View
 */

$wikihelp = 'EN:First_setup|FR:Premiers_paramétrages|ES:Primeras_configuraciones';
llxHeader('', $langs->trans("Setup"), $wikihelp);

print load_fiche_titre($langs->trans("CompanyFoundation"), '', 'title_setup');

$head = company_admin_prepare_head();

print dol_get_fiche_head($head, 'socialnetworks', $langs->trans("SocialNetworksInformation"), -1, 'company');

print '<span class="opacitymedium">'.$langs->trans("CompanyFundationDesc", $langs->transnoentities("Save"))."</span><br>\n";
print "<br>\n";


/**
 * Edit parameters
 */

print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="update">';

// Social networks
print '<br>';
print '<table class="noborder centpercent editmode">';
print '<tr class="liste_titre">';
print '<td class="titlefield">'.$langs->trans("SocialNetworksInformation").'</td><td>'.$langs->trans("Value").'</td>';
print "</tr>\n";

$listofnetworks = array(
	'facebook'=>'facebook',
	'twitter'=>'twitter',
	'linkedin'=>'linkedin',
	'instagram'=>'instagram',
	'youtube'=>'youtube',
	'github'=>'github'
);

foreach ($listofnetworks as $networkkey => $networkicon) {
	print '<tr class="oddeven"><td>';
	print '<label for="'.$networkkey.'url">'.$langs->trans("SocialNetworks".ucfirst($networkkey)."URL").'</label></td><td>';
	$networkconst = 'MAIN_INFO_SOCIETE_'.strtoupper($networkkey).'_URL';
	print '<span class="fa paddingright fa-'.($networkicon ? $networkicon : 'link').'"></span>';
	print '<input name="'.$networkkey.'url" id="'.$networkkey.'url" class="minwidth300" value="'.dol_escape_htmltag($conf->global->$networkconst).'"></td></tr>';
	print '</td></tr>'."\n";
}

print "</table>";

print '<br>';

print '<br><div class="center">';
print '<input type="submit" class="button button-save" name="save" value="'.$langs->trans("Save").'">';
print '</div>';

print '</form>';


// End of page
llxFooter();
$db->close();
