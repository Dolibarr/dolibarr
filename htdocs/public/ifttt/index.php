<?php
/* Copyright (C) 2008-2010 Laurent Destailleur <eldy@users.sourceforge.net>
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
 * 	\file       htdocs/public/ifttt/index.php
 * 	\ingroup    ifttt
 * 	\brief      Page to IFTTT endpoint agenda
 * 				http://127.0.0.1/dolibarr/public/ifttt/index.php?securekey=...
 */

if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', '1');
if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU', '1'); // If there is no menu to show
if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML', '1'); // If we don't need to load the html.form.class.php
if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX', '1');
if (! defined('NOLOGIN'))        define("NOLOGIN", 1);		// This means this output page does not require to be logged.
if (! defined('NOCSRFCHECK'))    define("NOCSRFCHECK", 1);	// We accept to go on this page from external web site.

// This is a wrapper, so header is empty

/**
 * Header function
 *
 * @return	void
 */
function llxHeaderIFTTT()
{
    print '<html><title>IFTTT API</title><body>';
}
/**
 * Footer function
 *
 * @return	void
 */
function llxFooterIFTTT()
{
    print '</body></html>';
}


require '../../main.inc.php';

// Security check
if (empty($conf->ifttt->enabled)) accessforbidden('', 0, 0, 1);

// Check config
if (empty($conf->global->IFTTT_DOLIBARR_ENDPOINT_SECUREKEY))
{
	$user->getrights();

	llxHeaderIFTTT();
	print '<div class="error">Module Agenda was not configured properly.</div>';
	llxFooterIFTTT();
	exit;
}

// Check exportkey
if (empty($_GET["securekey"]) || $conf->global->IFTTT_DOLIBARR_ENDPOINT_SECUREKEY != $_GET["securekey"])
{
	$user->getrights();

	llxHeaderIFTTT();
	print '<div class="error">Bad value for securekey.</div>';
	llxFooterIFTTT();
	exit;
}


// Initialize technical object to manage hooks. Note that conf->hooks_modules contains array of hooks
$hookmanager->initHooks(array('iftttapi'));


llxHeaderIFTTT();
print '<div class="error">TODO</div>';
llxFooterIFTTT();
