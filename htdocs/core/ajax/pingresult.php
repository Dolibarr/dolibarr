<?php
/* Copyright (C) 2019		Laurent Destailleur	<eldy@users.sourceforge.net>
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
 *       \file       htdocs/core/ajax/pingresult.php
 *       \brief      File to save result of an anonymous ping into database (1 ping is done per installation)
 */

if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', '1'); // Disables token renewal
if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU', '1');
if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML', '1');
if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX', '1');
if (! defined('NOREQUIRESOC'))   define('NOREQUIRESOC', '1');
if (! defined('NOREQUIRETRAN'))  define('NOREQUIRETRAN', '1');

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

$action=GETPOST('action', 'alpha');
$hash_unique_id=GETPOST('hash_unique_id', 'alpha');
$hash_algo=GETPOST('hash_algo', 'alpha');


// Security check
if (! empty($user->socid))
	$socid = $user->socid;

$now = dol_now();


/*
 * View
 */

top_httphead();

print '<!-- Ajax page called with url '.dol_escape_htmltag($_SERVER["PHP_SELF"]).'?'.dol_escape_htmltag($_SERVER["QUERY_STRING"]).' -->'."\n";

// If ok
if ($action == 'firstpingok')
{
	// Note: pings are per installed instances / entity.
	// Once this constants are set, no more ping will be tried (except if we add parameter &forceping=1 on URL). So we can say this are 'first' ping.
	dolibarr_set_const($db, 'MAIN_FIRST_PING_OK_DATE', dol_print_date($now, 'dayhourlog', 'gmt'));
	dolibarr_set_const($db, 'MAIN_FIRST_PING_OK_ID', $hash_unique_id);

	print 'First ping OK saved for entity '.$conf->entity;
}
// If ko
elseif ($action == 'firstpingko')
{
	// Note: pings are by installation, done on entity 1.
	dolibarr_set_const($db, 'MAIN_LAST_PING_KO_DATE', dol_print_date($now, 'dayhourlog'), 'gmt');	// erase last value
	print 'First ping KO saved for entity '.$conf->entity;
}
else {
	print 'Error action='.$action.' not supported';
}
