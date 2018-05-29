<?php
/* Copyright (C) 2017  Laurent Destailleur <eldy@users.sourceforge.net>
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
 * or see http://www.gnu.org/
 */

/**
 * \file		htdocs/core/js/lib_gravatar.js.php
 * \brief		File that include javascript functions (included if option use_javascript activated)
 * 				JQuery (providing object $) library must be loaded before this file.
 */

//if (! defined('NOREQUIREUSER')) define('NOREQUIREUSER','1');	// Not disabled cause need to load personalized language
//if (! defined('NOREQUIREDB'))   define('NOREQUIREDB','1');
if (! defined('NOREQUIRESOC'))    define('NOREQUIRESOC','1');
//if (! defined('NOREQUIRETRAN')) define('NOREQUIRETRAN','1');	// Not disabled cause need to do translations
if (! defined('NOCSRFCHECK'))     define('NOCSRFCHECK',1);
if (! defined('NOTOKENRENEWAL'))  define('NOTOKENRENEWAL',1);
if (! defined('NOLOGIN'))         define('NOLOGIN',1);
if (! defined('NOREQUIREMENU'))   define('NOREQUIREMENU',1);
if (! defined('NOREQUIREHTML'))   define('NOREQUIREHTML',1);
if (! defined('NOREQUIREAJAX'))   define('NOREQUIREAJAX','1');

session_cache_limiter(FALSE);

require_once '../../main.inc.php';

// Define javascript type
top_httphead('text/javascript; charset=UTF-8');
// Important: Following code is to avoid page request by browser and PHP CPU at each Dolibarr page access.
if (empty($dolibarr_nocache)) header('Cache-Control: max-age=3600, public, must-revalidate');
else header('Cache-Control: no-cache');

?>

function get_avatar_from_service(service, userid, size) {
    // this return the url that redirects to the according user image/avatar/profile picture
    // implemented services: google profiles, facebook, gravatar, twitter, tumblr, default fallback
    // for google   use get_avatar_from_service('google', profile-name or user-id , size-in-px )
    // for facebook use get_avatar_from_service('facebook', vanity url or user-id , size-in-px or size-as-word )
    // for gravatar use get_avatar_from_service('gravatar', md5 hash email@adress, size-in-px )
    // for twitter  use get_avatar_from_service('twitter', username, size-in-px or size-as-word )
    // for tumblr   use get_avatar_from_service('tumblr', blog-url, size-in-px )
    // everything else will go to the fallback
    // google and gravatar scale the avatar to any site, others will guided to the next best version
    var url = '';

    switch (service) {

    case "google":
        // see http://googlesystem.blogspot.com/2011/03/unedited-google-profile-pictures.html (couldn't find a better link)
        // available sizes: all, google rescales for you
        url = "http://profiles.google.com/s2/photos/profile/" + userid + "?sz=" + size;
        break;

    case "facebook":
        // see https://developers.facebook.com/docs/reference/api/
        // available sizes: square (50x50), small (50xH) , normal (100xH), large (200xH)
        var sizeparam = '';
        if (isNumberOrNot(size)) {
            if (size >= 200) {
                sizeparam = 'large'
            };
            if (size >= 100 && size < 200) {
                sizeparam = 'normal'
            };
            if (size >= 50 && size < 100) {
                sizeparam = 'small'
            };
            if (size < 50) {
                sizeparam = 'square'
            };
        } else {
            sizeparam = size;
        }
        url = "https://graph.facebook.com/" + userid + "/picture?type=" + sizeparam;
        break;

    case "gravatar":
        // see http://en.gravatar.com/site/implement/images/
        // available sizes: all, gravatar rescales for you
        url = "http://www.gravatar.com/avatar/" + userid + "?s=" + size
        break;

    case "twitter":
        // see https://dev.twitter.com/docs/api/1/get/users/profile_image/%3Ascreen_name
        // available sizes: bigger (73x73), normal (48x48), mini (24x24), no param will give you full size
        var sizeparam = '';
        if (isNumberOrNot(size)) {
            if (size >= 73) {
                sizeparam = 'bigger'
            };
            if (size >= 48 && size < 73) {
                sizeparam = 'normal'
            };
            if (size < 48) {
                sizeparam = 'mini'
            };
        } else {
            sizeparam = size;
        }

        url = "http://api.twitter.com/1/users/profile_image?screen_name=" + userid + "&size=" + sizeparam;
        break;

    case "tumblr":
        // see http://www.tumblr.com/docs/en/api/v2#blog-avatar
        //TODO do something smarter with the ranges
        // available sizes: 16, 24, 30, 40, 48, 64, 96, 128, 512
        var sizeparam = '';
        if (size >= 512) {
            sizeparam = 512
        };
        if (size >= 128 && size < 512) {
            sizeparam = 128
        };
        if (size >= 96 && size < 128) {
            sizeparam = 96
        };
        if (size >= 64 && size < 96) {
            sizeparam = 64
        };
        if (size >= 48 && size < 64) {
            sizeparam = 48
        };
        if (size >= 40 && size < 48) {
            sizeparam = 40
        };
        if (size >= 30 && size < 40) {
            sizeparam = 30
        };
        if (size >= 24 && size < 30) {
            sizeparam = 24
        };
        if (size < 24) {
            sizeparam = 16
        };

        url = "http://api.tumblr.com/v2/blog/" + userid + "/avatar/" + sizeparam;
        break;

    default:
        // http://www.iconfinder.com/icondetails/23741/128/avatar_devil_evil_green_monster_vampire_icon
        // find your own
        url = "http://i.imgur.com/RLiDK.png"; // 48x48
    }


    return url;
}


// helper methods

function isNumberOrNot(n) {
    // see http://stackoverflow.com/questions/18082/validate-numbers-in-javascript-isnumeric
    return !isNaN(parseFloat(n)) && isFinite(n);
}

// End of lib_gravatar.js.php
