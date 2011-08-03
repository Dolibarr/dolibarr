<?php
/* Copyright (C) 2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 *     \file       htdocs/admin/system/xcache.php
 *     \brief      Page administration XCache
 *     \version    $Id: xcache.php,v 1.2 2011/07/31 22:23:14 eldy Exp $
 */

require("../../main.inc.php");

$langs->load("admin");

if (!$user->admin)
  accessforbidden();

$action=GETPOST('action');


/*
 * View
 */

llxHeader();

print_fiche_titre("XCache",'','setup');

print "<br>\n";

//function_exists('apc_cache_info') || function_exists('eaccelerator_info') || function_exists('xcache_info'))
if (!function_exists('xcache_info'))
{
    print 'XCache seems to be not installed. Function xcache_info not found.';
	llxfooter('$Date: 2011/07/31 22:23:14 $ - $Revision: 1.2 $');
	exit;
}


print 'Opcode cache XCache is on<br>'."\n";

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

llxfooter('$Date: 2011/07/31 22:23:14 $ - $Revision: 1.2 $');
?>
