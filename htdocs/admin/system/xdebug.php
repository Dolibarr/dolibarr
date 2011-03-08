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
 *     \file       htdocs/admin/system/xdebug.php
 *     \brief      Page administration XDebug
 *     \version    $Id$
 */

require("../../main.inc.php");

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
    $address = empty($conf->global->XDEBUG_SERVER)?'127.0.0.1':$conf->global->XDEBUG_SERVER;
    $port = empty($conf->global->XDEBUG_PORT)?9000:$conf->global->XDEBUG_PORT;

    print 'XDEBUG_SERVER: '.$address."<br>\n";
    print 'XDEBUG_PORT: '.$port."<br>\n";
    print "<br>\n";
    $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
	if (empty($socket)) die('Unable to preapre a socket');
	//socket_bind($sock, $address, $port) or die('Unable to bind on address='.$address.' port='.$port);
	//socket_listen($sock);
	//$client = socket_accept($sock);
	$client=socket_connect($socket, $address, $port);
	if ($client)
	{
	   echo "Connection established: ".$client." - address=".$address." port=".$port."<br>\n";
	   echo "There is a Remote debug server at this address.\n";
	}
	else
	{
	    print socket_strerror(socket_last_error());
        echo "Failed to connect to address=".$address." port=".$port."<br>\n";
        echo "There is no Remote debug server at this address.\n";
	}
	socket_close($client);
	socket_close($sock);
}
else
{
	print "Can't test if PHPDebug is OK as PHP socket functions are not enabled.";
}


llxfooter('$Date$ - $Revision$');
?>
