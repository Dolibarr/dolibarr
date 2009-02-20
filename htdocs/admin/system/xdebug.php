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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
        \file       htdocs/admin/system/xdebug.php
		\brief      Page administration XDebug
		\version    $Id$
*/

require("./pre.inc.php");

$langs->load("admin");

if (!$user->admin)
  accessforbidden();


/*
* View
*/

llxHeader();

print_fiche_titre("XDebug",'','setup');

print "<br>\n";


if (!function_exists('xdebug_is_enabled'))
{
    print 'XDebug seems to be not installed. Function xdebug_is_enabled not found.';
	llxfooter('$Date$ - $Revision$');
	exit;
}


if (function_exists('socket_create'))
{
	$address = '127.0.0.1';
	$port = 9000;
	$sock = socket_create(AF_INET, SOCK_STREAM, 0);
	socket_bind($sock, $address, $port) or die('Unable to bind');
	socket_listen($sock);
	$client = socket_accept($sock);
	echo "connection established: $client";
	socket_close($client);
	socket_close($sock);
}
else
{
	print "Can't test if PHPDebug is OK as PHP socket functions are not enabled.";
}


llxfooter('$Date$ - $Revision$');
?>
