<?php
/* Copyright (C) 2016-2018	Laurent Destailleur		<eldy@users.sourceforge.net>
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
 *	\file       htdocs/public/notice.php
 *	\brief      Dolibarr page to show a notice.
 *              Default notice is a message to say network connection is off.
 *              You can also call this page with URL:
 *                /public/notice.php?lang=xx_XX&transkey=translation_key  (key must be inside file main.lang, error.lang or other.lang)
 *                /public/notice.php?transphrase=url_encoded_sentence_to_show
 */

define('NOCSRFCHECK', 1);
define('NOLOGIN', 1);

require '../main.inc.php';


/**
 * View
 */

if (! GETPOST('transkey', 'alphanohtml') && ! GETPOST('transphrase', 'alphanohtml'))
{
    print 'Sorry, it seems your internet connexion is off.<br>';
    print 'You need to be connected to network to use this software.<br>';
}
else
{
    $langs->loadLangs(array("error", "other"));

    if (GETPOST('transphrase', 'alphanohtml')) print dol_escape_htmltag(GETPOST('transphrase', 'alphanohtml'));
    elseif (GETPOST('transkey', 'alphanohtml')) print dol_escape_htmltag($langs->trans(GETPOST('transkey', 'alphanohtml')));
}
