<?php
/* Copyright (C) 2009-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *     \file       htdocs/admin/system/xdebug.php
 *     \brief      Page administration XDebug
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
    llxFooter();
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
        echo "There is a Remote debug server at this address.<br>\n";
        echo "<br>\n";
        echo "To be sure this debugger accepts input from your PHP server, be sure to have\n";
        echo "your php.ini file with this :<br>\n";
        echo 'xdebug.remote_enable=on<br>
              xdebug.remote_handle=dbgp<br>
              xdebug.remote_host=localhost<br>
              xdebug.remote_port=9000<br>
              xdebug.profiler_enable=0<br>
              xdebug.profiler_enable_trigger=1<br>
              xdebug.show_local_vars=off<br>
              xdebug.profiler_output_dir=/tmp/xdebug<br>
              xdebug.profiler_append=0<br>
              <br>
              xdebug.trace_enable_trigger=1<br>
              xdebug.show_mem_delta=1<br>
              xdebug.trace_output_dir=/tmp/trace<br>
              xdebug.auto_trace=0<br>
	    '."\n";
        print "<br>\n";
        echo 'Then check in your debug server (Eclipse), you have setup:<br>
	         XDebug with same port than in php.ini<br>
	         Allow Remote debug=yes or prompt<br>'."\n";
        print "<br>\n";
        echo "Then, to run a debug session, add parameter XDEBUG_SESSION_START=aname on your URL. To stop, remove cookie XDEBUG_SESSION_START.\n";
    }
    else
    {
        print socket_strerror(socket_last_error());
        echo "Failed to connect to address=".$address." port=".$port."<br>\n";
        echo "There is no Remote debug server at this address.\n";
    }
    socket_close($socket);
}
else
{
    print "Can't test if PHPDebug is OK as PHP socket functions are not enabled.";
}


llxFooter();

$db->close();
?>
