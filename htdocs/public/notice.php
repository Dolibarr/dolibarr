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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/public/notice.php
 *	\brief      Dolibarr page to show a notice.
 *              Default notice is a message to say network connection is off.
 *              You can also call this page with URL:
 *                /public/notice.php?lang=xx_XX&transkey=translation_key  (key must be inside file main.lang, error.lang or other.lang)
 *                /public/notice.php?transphrase=url_encoded_sentence_to_show
 */

<<<<<<< HEAD
define('NOCSRFCHECK',1);
define('NOLOGIN',1);
=======
define('NOCSRFCHECK', 1);
define('NOLOGIN', 1);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

require '../main.inc.php';


/**
 * View
 */

<<<<<<< HEAD
if (! GETPOST('transkey','alphanohtml') && ! GETPOST('transphrase','alphanohtml'))
=======
if (! GETPOST('transkey', 'alphanohtml') && ! GETPOST('transphrase', 'alphanohtml'))
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
{
    print 'Sorry, it seems your internet connexion is off.<br>';
    print 'You need to be connected to network to use this software.<br>';
}
else
{
    $langs->load("error");
    $langs->load("other");

<<<<<<< HEAD
    if (GETPOST('transphrase','alphanohtml')) print GETPOST('transphrase','alphanohtml');
    if (GETPOST('transkey','alphanohtml')) print $langs->trans(GETPOST('transkey','alphanohtml'));
}

=======
    if (GETPOST('transphrase', 'alphanohtml')) print GETPOST('transphrase', 'alphanohtml');
    if (GETPOST('transkey', 'alphanohtml')) print $langs->trans(GETPOST('transkey', 'alphanohtml'));
}
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
