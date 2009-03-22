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
 *	\file       htdocs/asterisk/wrapper.php
 *  \brief      File that is entry point to call Dolibarr WebServices
 *  \version    $Id$
 *	\remarks	To be used, an Asterisk user must be created by adding this
 * 				in /etc/asterisk/manager.conf 
 * 				[dolibarr]
 * 				secret = dolibarr
 * 				deny=0.0.0.0/0.0.0.0
 * 				permit=127.0.0.1/255.255.255.0
 * 				read = system,call,log,verbose,command,agent,user
 * 				write = system,call,log,verbose,command,agent,user
 */

// This is to make Dolibarr working with Plesk
set_include_path($_SERVER['DOCUMENT_ROOT'].'/htdocs');

require_once("../master.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/functions.lib.php");
require_once(DOL_DOCUMENT_ROOT."/lib/functions2.lib.php");


dol_syslog("Call Dolibarr Asterisk interface");

// TODO Enable and test if module Asterisk is enabled


$conf->global->ASTERISK_HOST="127.0.0.1";
$conf->global->ASTERISK_TYPE="SIP/";
$conf->global->ASTERISK_INDICATIF="0";
$conf->global->ASTERISK_PORT=5038;

$login = $_GET['login'];
$password = $_GET['password'];
$caller = $_GET['caller'];
$called = $_GET['called'];

# Adresse IP du serveur Asterisk
$strHost = $conf->global->ASTERISK_HOST;

#Context ( generalement from-internal )
$strContext = "from-internal";

#Spécifiez le type d'extension par laquelle vous poste est connecte.
#ex: SIP/, IAX2/, ZAP/, etc
$channel = $conf->global->ASTERISK_TYPE;


#Delai d'attente avant de raccrocher
$strWaitTime = "30";

#Priority
$strPriority = "1";

#Nomber of try
$strMaxRetry = "2";

#Indicatif de la ligne sortante
$prefix = $conf->global->ASTERISK_INDICATIF;

#Port
$port = $conf->global->ASTERISK_PORT;

?>
<html>
<head>
<title>Asterisk redirection ...</title>
</head>
<?php

$number=strtolower($called) ;
$pos=strpos ($number,"local");
if (! empty($number))
{
	if ($pos===false) :
	$errno=0 ;
	$errstr=0 ;
	$strCallerId = "Dolibarr <$number>" ;
	$oSocket = @fsockopen ($strHost, $port, $errno, $errstr, 10) ;
	if (!$oSocket)
	{
		echo '<body>'."\n";
		$txt="Failed to execute fsockopen($strHost, $port, \$errno, \$errstr, 10)<br>\n";
		echo $txt;
		dol_syslog($txt,LOG_ERR);
		$txt=$errstr." (".$errno.")<br>\n";
		echo $txt;
		dol_syslog($txt,LOG_ERR);
	} 
	else
	{
		echo '<body onload="javascript:history.go(-1);">'."\n";
		fputs($oSocket, "Action: login\r\n" ) ;
		fputs($oSocket, "Events: off\r\n" ) ;
		fputs($oSocket, "Username: $login\r\n" ) ;
		fputs($oSocket, "Secret: $password\r\n\r\n" ) ;
		fputs($oSocket, "Action: originate\r\n" ) ;
		fputs($oSocket, "Channel: ".$channel.$caller."\r\n" ) ;
		fputs($oSocket, "WaitTime: $strWaitTime\r\n" ) ;
		fputs($oSocket, "CallerId: $strCallerId\r\n" ) ;
		fputs($oSocket, "Exten: ".$prefix.$number."\r\n" ) ;
		fputs($oSocket, "Context: $strContext\r\n" ) ;
		fputs($oSocket, "Priority: $strPriority\r\n\r\n" ) ;
		fputs($oSocket, "Action: Logoff\r\n\r\n" ) ;
		sleep(2) ;
		fclose($oSocket) ;
	}
	endif ;
}
?>
</body>
</html>
