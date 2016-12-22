<?php
/* Copyright (C) 2015       Frederic France     <frederic.france@free.fr>
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

// required Class
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';


// Define $urlwithroot
$urlwithouturlroot=preg_replace('/'.preg_quote(DOL_URL_ROOT,'/').'$/i','',trim($dolibarr_main_url_root));
$urlwithroot=$urlwithouturlroot.DOL_URL_ROOT;		// This is to use external domain name found into config file
//$urlwithroot=DOL_MAIN_URL_ROOT;					// This is to use same domain name than current


$langs->load("admin");
$langs->load("oauth");

// Security check
if (!$user->admin)
    accessforbidden();

$action = GETPOST('action', 'alpha');

// Supported OAUTH (a provider is supported when a file xxx_oauthcallback.php is available into htdocs/core/modules/oauth)
$supportedoauth2array=array('OAUTH_GOOGLE_NAME'=>'google');

// API access parameters OAUTH
$list = array (
            array(
                'OAUTH_AMAZON_NAME',
                'OAUTH_AMAZON_ID',
                'OAUTH_AMAZON_SECRET',
            ),
            array(
                'OAUTH_BITBUCKET_NAME',
                'OAUTH_BITBUCKET_ID',
                'OAUTH_BITBUCKET_SECRET',
            ),
            array(
                'OAUTH_BITLY_NAME',
                'OAUTH_BITLY_ID',
                'OAUTH_BITLY_SECRET',
            ),
            array(
                'OAUTH_BITRIX24_NAME',
                'OAUTH_BITRIX24_ID',
                'OAUTH_BITRIX24_SECRET',
            ),
            array(
                'OAUTH_BOX_NAME',
                'OAUTH_BOX_ID',
                'OAUTH_BOX_SECRET',
            ),
            array(
                'OAUTH_BUFFER_NAME',
                'OAUTH_BUFFER_ID',
                'OAUTH_BUFFER_SECRET',
            ),
            array(
                'OAUTH_DAILYMOTION_NAME',
                'OAUTH_DAILYMOTION_ID',
                'OAUTH_DAILYMOTION_SECRET',
            ),
            array(
                'OAUTH_DEVIANTART_NAME',
                'OAUTH_DEVIANTART_ID',
                'OAUTH_DEVIANTART_SECRET',
            ),
            array(
                'OAUTH_DROPBOX_NAME',
                'OAUTH_DROPBOX_ID',
                'OAUTH_DROPBOX_SECRET',
            ),
            array(
                'OAUTH_ETSY_NAME',
                'OAUTH_ETSY_ID',
                'OAUTH_ETSY_SECRET',
            ),
            array(
                'OAUTH_EVEONLINE_NAME',
                'OAUTH_EVEONLINE_ID',
                'OAUTH_EVEONLINE_SECRET',
            ),
            array(
                'OAUTH_FACEBOOK_NAME',
                'OAUTH_FACEBOOK_ID',
                'OAUTH_FACEBOOK_SECRET',
            ),
            array(
                'OAUTH_FITBIT_NAME',
                'OAUTH_FITBIT_ID',
                'OAUTH_FITBIT_SECRET',
            ),
            array(
                'OAUTH_FIVEHUNDREDPX_NAME',
                'OAUTH_FIVEHUNDREDPX_ID',
                'OAUTH_FIVEHUNDREDPX_SECRET',
            ),
            array(
                'OAUTH_FLICKR_NAME',
                'OAUTH_FLICKR_ID',
                'OAUTH_FLICKR_SECRET',
            ),
            array(
                'OAUTH_FOURSQUARE_NAME',
                'OAUTH_FOURSQUARE_ID',
                'OAUTH_FOURSQUARE_SECRET',
            ),
            array(
                'OAUTH_GITHUB_NAME',
                'OAUTH_GITHUB_ID',
                'OAUTH_GITHUB_SECRET',
            ),
            array(
                'OAUTH_GOOGLE_NAME',
                'OAUTH_GOOGLE_ID',
                'OAUTH_GOOGLE_SECRET',
            ),
            array(
                'OAUTH_HUBIC_NAME',
                'OAUTH_HUBIC_ID',
                'OAUTH_HUBIC_SECRET',
            ),
            array(
                'OAUTH_INSTAGRAM_NAME',
                'OAUTH_INSTAGRAM_ID',
                'OAUTH_INSTAGRAM_SECRET',
            ),
            array(
                'OAUTH_LINKEDIN_NAME',
                'OAUTH_LINKEDIN_ID',
                'OAUTH_LINKEDIN_SECRET',
            ),
            array(
                'OAUTH_MAILCHIMP_NAME',
                'OAUTH_MAILCHIMP_ID',
                'OAUTH_MAILCHIMP_SECRET',
            ),
            array(
                'OAUTH_MICROSOFT_NAME',
                'OAUTH_MICROSOFT_ID',
                'OAUTH_MICROSOFT_SECRET',
            ),
            array(
                'OAUTH_NEST_NAME',
                'OAUTH_NEST_ID',
                'OAUTH_NEST_SECRET',
            ),
            array(
                'OAUTH_NETATMO_NAME',
                'OAUTH_NETATMO_ID',
                'OAUTH_NETATMO_SECRET',
            ),
            array(
                'OAUTH_PARROTFLOWERPOWER_NAME',
                'OAUTH_PARROTFLOWERPOWER_ID',
                'OAUTH_PARROTFLOWERPOWER_SECRET',
            ),
            array(
                'OAUTH_PAYPAL_NAME',
                'OAUTH_PAYPAL_ID',
                'OAUTH_PAYPAL_SECRET',
            ),
            array(
                'OAUTH_POCKET_NAME',
                'OAUTH_POCKET_ID',
                'OAUTH_POCKET_SECRET',
            ),
            array(
                'OAUTH_QUICKBOOKS_NAME',
                'OAUTH_QUICKBOOKS_ID',
                'OAUTH_QUICKBOOKS_SECRET',
            ),
            array(
                'OAUTH_REDDIT_NAME',
                'OAUTH_REDDIT_ID',
                'OAUTH_REDDIT_SECRET',
            ),
            array(
                'OAUTH_REDMINE_NAME',
                'OAUTH_REDMINE_ID',
                'OAUTH_REDMINE_SECRET',
            ),
            array(
                'OAUTH_RUNKEEPER_NAME',
                'OAUTH_RUNKEEPER_ID',
                'OAUTH_RUNKEEPER_SECRET',
            ),
            array(
                'OAUTH_SCOOPIT_NAME',
                'OAUTH_SCOOPIT_ID',
                'OAUTH_SCOOPIT_SECRET',
            ),
            array(
                'OAUTH_SOUNDCLOUD_NAME',
                'OAUTH_SOUNDCLOUD_ID',
                'OAUTH_SOUNDCLOUD_SECRET',
            ),
            array(
                'OAUTH_SPOTIFY_NAME',
                'OAUTH_SPOTIFY_ID',
                'OAUTH_SPOTIFY_SECRET',
            ),
            array(
                'OAUTH_STRAVA_NAME',
                'OAUTH_STRAVA_ID',
                'OAUTH_STRAVA_SECRET',
            ),
            array(
                'OAUTH_TUMBLR_NAME',
                'OAUTH_TUMBLR_ID',
                'OAUTH_TUMBLR_SECRET',
            ),
            array(
                'OAUTH_TWITTER_NAME',
                'OAUTH_TWITTER_ID',
                'OAUTH_TWITTER_SECRET',
            ),
            array(
                'OAUTH_USTREAM_NAME',
                'OAUTH_USTREAM_ID',
                'OAUTH_USTREAM_SECRET',
            ),
            array(
                'OAUTH_VIMEO_NAME',
                'OAUTH_VIMEO_ID',
                'OAUTH_VIMEO_SECRET',
            ),
            array(
                'OAUTH_YAHOO_NAME',
                'OAUTH_YAHOO_ID',
                'OAUTH_YAHOO_SECRET',
            ),
            array(
                'OAUTH_YAMMER_NAME',
                'OAUTH_YAMMER_ID',
                'OAUTH_YAMMER_SECRET',
            ),
);


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

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans('ConfigOAuth'),$linkback,'title_setup');

print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="update">';

/*
 *  Parameters
 */
dol_fiche_head(array(), '', '', 0, 'technic');


print $langs->trans("ListOfSupportedOauthProviders").'<br><br>';

print '<table class="noborder" width="100%">';

$var = true;

foreach ($list as $key)
{
    $supported=0;
    if (in_array($key[0], array_keys($supportedoauth2array))) $supported=1;
    if (! $supported) continue;     // show only supported
        
    print '<tr class="liste_titre">';
    // Api Name
    $label = $langs->trans($key[0]); 
    print '<td colspan="2">'.$label.'</td></tr>';

    if ($supported)
    {
        $redirect_uri=$urlwithroot.'/core/modules/oauth/'.$supportedoauth2array[$key[0]].'_oauthcallback.php';
        $var = !$var;
        print '<tr '.$bc[$var].' class="value">';
        print '<td>'.$langs->trans("UseTheFollowingUrlAsRedirectURI").'</td>';
        print '<td><input style="width: 80%" type"text" name="uri'.$key[0].'" value="'.$redirect_uri.'">';
        print '</td></tr>';
    }
    else
    {
        $var = !$var;
        print '<tr '.$bc[$var].' class="value">';
        print '<td>'.$langs->trans("UseTheFollowingUrlAsRedirectURI").'</td>';
        print '<td>'.$langs->trans("FeatureNotYetSupported").'</td>';
        print '</td></tr>';
    }
        
    // Api Id
    $var = !$var;
    print '<tr '.$bc[$var].' class="value">';
    print '<td><label for="'.$key[1].'">'.$langs->trans($key[1]).'</label></td>';
    print '<td><input type="text" size="100" id="'.$key[1].'" name="'.$key[1].'" value="'.$conf->global->{$key[1]}.'">';
    print '</td></tr>';

    // Api Secret
    $var = !$var;
    print '<tr '.$bc[$var].' class="value">';
    print '<td><label for="'.$key[2].'">'.$langs->trans($key[2]).'</label></td>';
    print '<td><input type="password" size="100" id="'.$key[2].'" name="'.$key[2].'" value="'.$conf->global->{$key[2]}.'">';
    print '</td></tr>';

}

print '</table>'."\n";

dol_fiche_end();

print '<div class="center"><input type="submit" class="button" value="'.$langs->trans('Modify').'" name="button"></div>';

print '</form>';


llxFooter();
$db->close();
