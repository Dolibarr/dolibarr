<?php
/* Copyright (C) 2009-2014 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *     \file       htdocs/admin/system/xdebug.php
 *     \brief      Page administration XDebug
 */

require '../../main.inc.php';

$langs->load("admin");

if (!$user->admin)
accessforbidden();


/*
 * View
*/

llxHeader();

print load_fiche_titre("XDebug", '', 'title_setup');

print "<br>\n";


if (!function_exists('xdebug_is_enabled'))
{
	print 'XDebug seems to be not installed. Function xdebug_is_enabled not found.';
	llxFooter();
	exit;
}


if (function_exists('socket_create'))
{
	$address = ini_get('xdebug.remote_host') ?ini_get('xdebug.remote_host') : '127.0.0.1';
	$port = ini_get('xdebug.remote_port') ?ini_get('xdebug.remote_port') : 9000;

	print "<strong>Current xdebug setup:</strong><br>\n";
	print "* Remote debug setup:<br>\n";
	print 'xdebug.remote_enable = '.ini_get('xdebug.remote_enable')."<br>\n";
	print 'xdebug.remote_host = '.$address."<br>\n";
	print 'xdebug.remote_port = '.$port."<br>\n";
	print "* Profiler setup ";
	if (function_exists('xdebug_get_profiler_filename')) print xdebug_get_profiler_filename() ? "(currently on into file ".xdebug_get_profiler_filename().")" : "(currently off)";
	else print "(currenlty not available)";
	print ":<br>\n";
	print 'xdebug.profiler_enable = '.ini_get('xdebug.profiler_enable')."<br>\n";
	print 'xdebug.profiler_enable_trigger = '.ini_get('xdebug.profiler_enable_trigger')."<br>\n";
	print 'xdebug.profiler_output_dir = '.ini_get('xdebug.profiler_output_dir')."<br>\n";
	print 'xdebug.profiler_output_name = '.ini_get('xdebug.profiler_output_name')."<br>\n";
	print 'xdebug.profiler_append = '.ini_get('xdebug.profiler_append')."<br>\n";
	print "<br>\n";

	echo "To run a debug session, add parameter<br>";
	echo "* XDEBUG_SESSION_START=aname on your URL. To stop, remove cookie XDEBUG_SESSION_START.<br>\n";
	echo "To run a profiler session (when xdebug.profiler_enable_trigger=1), add parameter<br>\n";
	echo "* XDEBUG_PROFILE=aname on each URL.<br>";
	print "<br>";

	print "<strong>Test debugger server (Eclipse for example):</strong><br>\n";
	$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
	if (empty($socket)) die('Unable to prepare a socket');
	//socket_bind($sock, $address, $port) or die('Unable to bind on address='.$address.' port='.$port);
	//socket_listen($sock);
	//$client = socket_accept($sock);
	$client = socket_connect($socket, $address, $port);
	if ($client)
	{
		echo "Connection established: ".$client." - address=".$address." port=".$port."<br>\n";
		echo "There is a Remote debug server at this address.<br>\n";
		echo "<br>\n";
		echo "To be sure this debugger accepts input from your PHP server and xdebug, be sure to have\n";
		echo "your php.ini file with this :<br>\n";
		echo '<textarea cols="80" rows="16">'."xdebug.remote_enable=on
xdebug.remote_handle=dbgp
xdebug.remote_host=localhost
xdebug.remote_port=9000
xdebug.profiler_enable=0
xdebug.profiler_enable_trigger=1
xdebug.show_local_vars=off
xdebug.profiler_output_dir=/tmp/xdebug
xdebug.profiler_append=0
; for xdebug 2.2+
xdebug.trace_enable_trigger=1
xdebug.show_mem_delta=1
xdebug.trace_output_dir=/tmp/trace
xdebug.auto_trace=0
</textarea>\n";
		print "<br><br>\n";
		echo 'Then check in your debug server (Eclipse), you have setup:<br>
	         XDebug with same port than in php.ini<br>
	         Allow Remote debug=yes or prompt<br>'."\n";
		print "<br>\n";
	} else {
		print socket_strerror(socket_last_error());
		echo "Failed to connect to address=".$address." port=".$port."<br>\n";
		echo "There is no Remote debug server at this address.\n";
	}
	socket_close($socket);
} else {
	print "Can't test if PHPDebug is OK as PHP socket functions are not enabled.";
}


llxFooter();

$db->close();
