<?php
/* Copyright (C) 2012 Nicolas Villa aka Boyquotes http://informetic.fr
 * Copyright (C) 2013 Florian Henry <florian.henry@opn-concept.pro>
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
 *	\file       core/lib/oauth.lib.php
 *	\brief      Function for module Oauth
 *	\ingroup    oauth
 */


// Supported OAUTH (a provider is supported when a file xxx_oauthcallback.php is available into htdocs/core/modules/oauth)
$supportedoauth2array=array(
    'OAUTH_GOOGLE_NAME'=>'google',
);
if ($conf->global->MAIN_FEATURES_LEVEL >= 2)
{
	$supportedoauth2array['OAUTH_STRIPE_TEST_NAME']='stripetest';
	$supportedoauth2array['OAUTH_STRIPE_LIVE_NAME']='stripelive';
}
$supportedoauth2array['OAUTH_GITHUB_NAME']='github';



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
        'OAUTH_GITHUB_DESC',
    ),
    array(
        'OAUTH_GOOGLE_NAME',
        'OAUTH_GOOGLE_ID',
        'OAUTH_GOOGLE_SECRET',
        'OAUTH_GOOGLE_DESC',
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
		'OAUTH_STRIPE_TEST_NAME',
		'OAUTH_STRIPE_TEST_ID',
		'STRIPE_TEST_SECRET_KEY',
	),
	array(
		'OAUTH_STRIPE_LIVE_NAME',
		'OAUTH_STRIPE_LIVE_ID',
		'STRIPE_LIVE_SECRET_KEY',
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



/**
 * Return array of tabs to used on pages to setup cron module.
 *
 * @return 	array				Array of tabs
 */
function oauthadmin_prepare_head()
{
    global $langs, $conf;
    $h = 0;
    $head = array();

    $head[$h][0] = dol_buildpath('/admin/oauth.php', 1);
    $head[$h][1] = $langs->trans("OAuthServices");
    $head[$h][2] = 'services';
    $h++;

    $head[$h][0] = dol_buildpath('/admin/oauthlogintokens.php', 1);
    $head[$h][1] = $langs->trans("TokenManager");
    $head[$h][2] = 'tokengeneration';
    $h++;

    complete_head_from_modules($conf, $langs, null, $head, $h, 'oauthadmin');

    complete_head_from_modules($conf, $langs, null, $head, $h, 'oauthadmin', 'remove');


    return $head;
}
