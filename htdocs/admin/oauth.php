<?php
/* Copyright (C) 2015-2018  Frederic France     <frederic.france@netlogic.fr>
 * Copyright (C) 2016       Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

/**
 * \file        htdocs/admin/oauth.php
 * \ingroup     oauth
 * \brief       Setup page to configure oauth access api
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/oauth.lib.php';


// Define $urlwithroot
$urlwithouturlroot=preg_replace('/'.preg_quote(DOL_URL_ROOT,'/').'$/i','',trim($dolibarr_main_url_root));
$urlwithroot=$urlwithouturlroot.DOL_URL_ROOT;		// This is to use external domain name found into config file
//$urlwithroot=DOL_MAIN_URL_ROOT;					// This is to use same domain name than current

// Load translation files required by the page
$langs->loadLangs(array('admin', 'oauth'));

// Security check
if (!$user->admin)
    accessforbidden();

$action = GETPOST('action', 'alpha');


/*
 * Actions
 */

if ($action == 'update')
{
    $error = 0;

    foreach ($list as $constname) {
        $constvalue = GETPOST($constname[1], 'alpha');
        if (!dolibarr_set_const($db, $constname[1], $constvalue, 'chaine', 0, '', $conf->entity))
            $error++;
        $constvalue = GETPOST($constname[2], 'alpha');
        if (!dolibarr_set_const($db, $constname[2], $constvalue, 'chaine', 0, '', $conf->entity))
            $error++;
    }

    if (! $error)
    {
        setEventMessages($langs->trans("SetupSaved"), null);
    } else {
        setEventMessages($langs->trans("Error"), null, 'errors');
    }
}

/*
 * View
 */

llxHeader();

$form = new Form($db);

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans('ConfigOAuth'),$linkback,'title_setup');

print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="update">';

$head = oauthadmin_prepare_head();

dol_fiche_head($head, 'services', '', -1, 'technic');


print $langs->trans("ListOfSupportedOauthProviders").'<br><br>';

print '<table class="noborder" width="100%">';

$i=0;

// $list is defined into oauth.lib.php
foreach ($list as $key)
{
    $supported=0;
    if (in_array($key[0], array_keys($supportedoauth2array))) $supported=1;
    if (! $supported) continue;     // show only supported

    $i++;

    print '<tr class="liste_titre'.($i > 1 ?' liste_titre_add':'').'">';
    // Api Name
    $label = $langs->trans($key[0]);
    print '<td>'.$label.'</td>';
    print '<td>';
    if (! empty($key[3])) print $langs->trans($key[3]);
    print '</td>';
    print '</tr>';

    if ($supported)
    {
        $redirect_uri=$urlwithroot.'/core/modules/oauth/'.$supportedoauth2array[$key[0]].'_oauthcallback.php';
        print '<tr class="oddeven value">';
        print '<td>'.$langs->trans("UseTheFollowingUrlAsRedirectURI").'</td>';
        print '<td><input style="width: 80%" type"text" name="uri'.$key[0].'" value="'.$redirect_uri.'">';
        print '</td></tr>';
    }
    else
    {
        print '<tr class="oddeven value">';
        print '<td>'.$langs->trans("UseTheFollowingUrlAsRedirectURI").'</td>';
        print '<td>'.$langs->trans("FeatureNotYetSupported").'</td>';
        print '</td></tr>';
    }

    // Api Id
    print '<tr class="oddeven value">';
    print '<td><label for="'.$key[1].'">'.$langs->trans($key[1]).'</label></td>';
    print '<td><input type="text" size="100" id="'.$key[1].'" name="'.$key[1].'" value="'.$conf->global->{$key[1]}.'">';
    print '</td></tr>';

    // Api Secret
    print '<tr class="oddeven value">';
    print '<td><label for="'.$key[2].'">'.$langs->trans($key[2]).'</label></td>';
    print '<td><input type="password" size="100" id="'.$key[2].'" name="'.$key[2].'" value="'.$conf->global->{$key[2]}.'">';
    print '</td></tr>';
}

print '</table>'."\n";

dol_fiche_end();

print '<div class="center"><input type="submit" class="button" value="'.$langs->trans('Modify').'" name="button"></div>';

print '</form>';

// End of page
llxFooter();
$db->close();
