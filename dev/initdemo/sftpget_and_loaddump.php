#!/usr/bin/env php
<?php
/* Copyright (C) 2012 Laurent Destailleur	<eldy@users.sourceforge.net>
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
 * or see http://www.gnu.org/
 *
 * Get a distant dump file and load it into a mysql database
 */

$sapi_type = php_sapi_name();
$script_file = basename(__FILE__);
$path=dirname(__FILE__).'/';

// Test if batch mode
if (substr($sapi_type, 0, 3) == 'cgi') {
	echo "Error: You are using PHP for CGI. To execute ".$script_file." from command line, you must use PHP for CLI mode.\n";
	exit;
}

// Global variables
$error=0;

$sourceserver=isset($argv[1])?$argv[1]:'';		// user@server:/src/file
$password=isset($argv[2])?$argv[2]:'';
$dataserver=isset($argv[3])?$argv[3]:'';
$database=isset($argv[4])?$argv[4]:'';
$loginbase=isset($argv[5])?$argv[5]:'';
$passwordbase=isset($argv[6])?$argv[6]:'';

// Include Dolibarr environment
$res=0;
if (! $res && file_exists($path."../../master.inc.php")) $res=@include($path."../../master.inc.php");
if (! $res && file_exists($path."../../htdocs/master.inc.php")) $res=@include($path."../../htdocs/master.inc.php");
if (! $res && file_exists("../master.inc.php")) $res=@include("../master.inc.php");
if (! $res && file_exists("../../master.inc.php")) $res=@include("../../master.inc.php");
if (! $res && file_exists("../../../master.inc.php")) $res=@include("../../../master.inc.php");
if (! $res && preg_match('/\/nltechno([^\/]*)\//',$_SERVER["PHP_SELF"],$reg)) $res=@include($path."../../../dolibarr".$reg[1]."/htdocs/master.inc.php"); // Used on dev env only
if (! $res && preg_match('/\/nltechno([^\/]*)\//',$_SERVER["PHP_SELF"],$reg)) $res=@include("../../../dolibarr".$reg[1]."/htdocs/master.inc.php"); // Used on dev env only
if (! $res) die ("Failed to include master.inc.php file\n");
include_once(DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php');


/*
 *	Main
 */

$login='';
$server='';
if (preg_match('/^(.*)@(.*):(.*)$/',$sourceserver,$reg))
{
	$login=$reg[1];
	$server=$reg[2];
	$sourcefile=$reg[3];
	$targetfile=basename($sourcefile);
}
if (empty($sourceserver) || empty($server) || empty($login) || empty($sourcefile) || empty($password) || empty($database) || empty($loginbase) || empty($passwordbase))
{
	print "Usage: $script_file login@server:/src/file.(sql|gz|bz2) passssh databaseserver databasename loginbase passbase\n";
	print "Return code: 0 if success, <>0 if error\n";
	print "Warning, this script may take a long time.\n";
	exit(-1);
}


$targetdir='/tmp';
print "Get dump file from server ".$server.", path ".$sourcefile.", connect with login ".$login." loaded into localhost\n";

$sftpconnectstring=$sourceserver;
print 'SFTP connect string : '.$sftpconnectstring."\n";
//print 'SFTP password '.$password."\n";


// SFTP connect
if (! function_exists("ssh2_connect")) {
	dol_print_error('','ssh2_connect function does not exists'); exit(1);
}

$connection = ssh2_connect($server, 22);
if ($connection)
{
	if (! @ssh2_auth_password($connection, $login, $password))
	{
		dol_syslog("Could not authenticate with username ".$login." . and password ".preg_replace('/./', '*', $password),LOG_ERR);
		exit(-5);
	}
	else
	{
		//$stream = ssh2_exec($connection, '/usr/bin/php -i');
		/*
		print "Generate dump ".$filesys1.'.bz2'."\n";
			$stream = ssh2_exec($connection, "mysqldump -u debian-sys-maint -p4k9Blxl2snq4FHXY -h 127.0.0.1 --single-transaction -K --tables -c -e --hex-blob --default-character-set=utf8 saasplex | bzip2 -1 > ".$filesys1.'.bz2');
			stream_set_blocking($stream, true);
			// The command may not finish properly if the stream is not read to end
			$output = stream_get_contents($stream);
		*/

		$sftp = ssh2_sftp($connection);

		print 'Get file '.$sourcefile.' into '.$targetdir.$targetfile."\n";
		ssh2_scp_recv($connection, $sourcefile, $targetdir.$targetfile);

		$fullcommand="cat ".$targetdir.$targetfile." | mysql -h".$databaseserver." -u".$loginbase." -p".$passwordbase." -D ".$database;
		if (preg_match('/\.bz2$/',$targetfile))
		{
			$fullcommand="bzip2 -c -d ".$targetdir.$targetfile." | mysql -h".$databaseserver." -u".$loginbase." -p".$passwordbase." -D ".$database;
		}
		if (preg_match('/\.gz$/',$targetfile))
		{
			$fullcommand="gzip -d ".$targetdir.$targetfile." | mysql -h".$databaseserver." -u".$loginbase." -p".$passwordbase." -D ".$database;
		}
		print "Load dump with ".$fullcommand."\n";
		$output=array();
		$return_var=0;
		print strftime("%Y%m%d-%H%M%S").' '.$fullcommand."\n";
		exec($fullcommand, $output, $return_var);
		foreach($output as $line) print $line."\n";

		//ssh2_sftp_unlink($sftp, $fileinstalllock);
		//print $output;
	}
}
else
{
	print 'Failed to connect to ssh2 to '.$server;
	exit(-6);
}


exit(0);
