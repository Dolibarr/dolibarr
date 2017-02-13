<?php
/* Copyright (C) 2011-2015 Regis Houssin  <regis.houssin@capnetworks.com>
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
 *       \file       htdocs/core/ajax/security.php
 *       \brief      This ajax component is used to generated has keys for security purposes 
 *                   like key to use into URL to protect them. 
 */

if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL','1'); // Disables token renewal
if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU','1');
if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML','1');
if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX','1');
if (! defined('NOREQUIRESOC'))   define('NOREQUIRESOC','1');
if (! defined('NOREQUIRETRAN'))  define('NOREQUIRETRAN','1');
if (! defined('NOREQUIREHOOK'))  define('NOREQUIREHOOK','1');

require '../../main.inc.php';

/*
 * View
 */

//top_htmlhead("", "", 1);  // Replaced with top_httphead. An ajax page does not need html header.
top_httphead();

//print '<!-- Ajax page called with url '.$_SERVER["PHP_SELF"].'?'.$_SERVER["QUERY_STRING"].' -->'."\n";

// Registering the location of boxes
if (isset($_GET['action']) && ! empty($_GET['action']))
{
	if ($_GET['action'] == 'getrandompassword' && $user->admin)
	{
        require_once DOL_DOCUMENT_ROOT.'/core/lib/security2.lib.php';
	    $generic = $_GET['generic'] ? true : false;
		echo getRandomPassword($generic);
	}
}

