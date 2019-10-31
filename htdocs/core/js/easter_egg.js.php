<?php
/* Copyright (C) 2019  Pierre-Henry FAVRE <phf@atm-consulting.fr>
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
 * or see https://www.gnu.org/
 */

/**
 * \file		htdocs/core/js/easter_egg.js.php
 * \brief		File that include javascript code
 */

if (! defined('NOREQUIRESOC'))    define('NOREQUIRESOC', '1');
if (! defined('NOCSRFCHECK'))     define('NOCSRFCHECK', 1);
if (! defined('NOTOKENRENEWAL'))  define('NOTOKENRENEWAL', 1);
if (! defined('NOLOGIN'))         define('NOLOGIN', 1);
if (! defined('NOREQUIREMENU'))   define('NOREQUIREMENU', 1);
if (! defined('NOREQUIREHTML'))   define('NOREQUIREHTML', 1);
if (! defined('NOREQUIREAJAX'))   define('NOREQUIREAJAX', '1');

session_cache_limiter('public');

require_once '../../main.inc.php';

// Define javascript type
top_httphead('text/javascript; charset=UTF-8');
// Important: Following code is to avoid page request by browser and PHP CPU at each Dolibarr page access.
if (empty($dolibarr_nocache)) header('Cache-Control: max-age=10800, public, must-revalidate');
else header('Cache-Control: no-cache');


?>

$(document).ready(function() {
    var EASTER_EGG_PM_MAP = [38, 38, 40, 40, 37, 39, 37, 39, 66, 65];
    var EASTER_EGG_PM_MAP_KEYDOWN = [];
    $(document).on('keydown', function(event) {

        EASTER_EGG_PM_MAP_KEYDOWN.push(event.keyCode);
        EASTER_EGG_PM_MAP_KEYDOWN = EASTER_EGG_PM_MAP_KEYDOWN.slice(-10);

        if (EASTER_EGG_PM_MAP_KEYDOWN.length === 10 && JSON.stringify(EASTER_EGG_PM_MAP_KEYDOWN) === JSON.stringify(EASTER_EGG_PM_MAP)) {
            newpopup('/includes/easteregg/pm/index.html', 'EASTER EGG - PM');
        }

    });

    // Write code here to start another easter egg
});
