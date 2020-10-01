<?php
/* Copyright (C) 2009-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *     \file       htdocs/admin/system/xcache.php
 *     \brief      Page administration XCache
 */

require '../../main.inc.php';

$langs->load("admin");

if (!$user->admin) accessforbidden();

$action = GETPOST('action', 'aZ09');


/*
 * View
 */

llxHeader();

print load_fiche_titre("XCache", '', 'title_setup');

print "<br>\n";

if (!function_exists('xcache_info'))
{
	print 'XCache seems to be not installed. Function xcache_info not found.';
	llxFooter();
	exit;
}


print 'Opcode cache XCache is on<br><br>'."\n\n";

print $langs->trans("Split").': '.ini_get('xcache.count').' &nbsp; &nbsp; &nbsp; '.$langs->trans("Recommanded").': (cat /proc/cpuinfo | grep -c processor) + 1<br>'."\n";
print $langs->trans("Size").': '.ini_get('xcache.size').' &nbsp; &nbsp; &nbsp; '.$langs->trans("Recommanded").': 16*Split<br>'."\n";

print $langs->trans("xcache.cacher").': '.yn(ini_get('xcache.cacher')).'<br>'."\n";
print $langs->trans("xcache.optimizer").': '.yn(ini_get('xcache.optimizer')).' (will be usefull only with xcache v2)<br>'."\n";
print $langs->trans("xcache.stat").': '.yn(ini_get('xcache.stat')).'<br>'."\n";
print $langs->trans("xcache.coverager").': '.yn(ini_get('xcache.coverager')).'<br>'."\n";

//print xcache_get();
/*
$cacheinfos = array();
for ($i = 0; $i < 10; $i ++)
{
    $data = xcache_info(XC_TYPE_PHP, $i);
    $data['cacheid'] = $i;
    $cacheinfos[] = $data;
}

var_dump($cacheinfos);

if ($action == 'clear')
{
    xcache_clear_cache();
}
*/

// End of page
llxFooter();
$db->close();
