<?php
/* Copyright (C) 2009-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
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

// Load Dolibarr environment
require '../../main.inc.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

$langs->load("admin");

if (!$user->admin) {
	accessforbidden();
}

$action = GETPOST('action', 'aZ09');


/*
 * View
 */

llxHeader('', '', '', '', 0, 0, '', '', '', 'mod-admin page-system_xcache');

print load_fiche_titre("XCache", '', 'title_setup');

print "<br>\n";

if (!function_exists('xcache_info')) {
	print 'XCache seems to be not installed. Function xcache_info not found.';
	llxFooter();
	exit;
}


print 'Opcode cache XCache is on<br><br>'."\n\n";

print $langs->trans("Split").': '.ini_get('xcache.count').' &nbsp; &nbsp; &nbsp; '.$langs->trans("Recommended").': (cat /proc/cpuinfo | grep -c processor) + 1<br>'."\n";
print $langs->trans("Size").': '.ini_get('xcache.size').' &nbsp; &nbsp; &nbsp; '.$langs->trans("Recommended").': 16*Split<br>'."\n";

print $langs->trans("xcache.cacher").': '.yn(ini_get('xcache.cacher')).'<br>'."\n";
print $langs->trans("xcache.optimizer").': '.yn(ini_get('xcache.optimizer')).' (will be useful only with xcache v2)<br>'."\n";
print $langs->trans("xcache.stat").': '.yn(ini_get('xcache.stat')).'<br>'."\n";
print $langs->trans("xcache.coverager").': '.yn(ini_get('xcache.coverager')).'<br>'."\n";


// End of page
llxFooter();
$db->close();
