<?php
/* Copyright (C) 2009-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	\file       htdocs/asterisk/wrapper.php
 *  \brief      File that is entry point to call an Asterisk server
 *	\remarks	To be used, an Asterisk user must be created by adding this
 * 				in /etc/asterisk/manager.conf
 * 				[dolibarr]
 * 				secret = dolibarr
 * 				deny=0.0.0.0/0.0.0.0
 * 				permit=127.0.0.1/255.255.255.0
 * 				read = system,call,log,verbose,command,agent,user
 * 				write = system,call,log,verbose,command,agent,user
 */

//if (! defined('NOREQUIREUSER')) define('NOREQUIREUSER','1');
//if (! defined('NOREQUIREDB'))   define('NOREQUIREDB','1');
if (! defined('NOREQUIRESOC'))    define('NOREQUIRESOC','1');
if (! defined('NOREQUIRETRAN'))   define('NOREQUIRETRAN','1');
if (! defined('NOCSRFCHECK'))     define('NOCSRFCHECK','1');
if (! defined('NOTOKENRENEWAL'))  define('NOTOKENRENEWAL','1');
if (! defined('NOREQUIREMENU'))   define('NOREQUIREMENU','1');
if (! defined('NOREQUIREHTML'))   define('NOREQUIREHTML','1');
if (! defined('NOREQUIREAJAX'))   define('NOREQUIREAJAX','1');

/**
 * Empty header
 *
 * @return	none
 */
function llxHeader()
{
    print '<html>'."\n";
    print '<head>'."\n";
    print '<title>Asterisk redirection from Dolibarr...</title>'."\n";
    print '</head>'."\n";
}
/**
 * Empty footer
 *
 * @return	none
 */
function llxFooter()
{
    print "\n".'</html>'."\n";
}

require_once("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/functions.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/functions2.lib.php");


// Security check
if (! $conf->clicktodial->enabled)
{
    accessforbidden();
    exit;
}


// Define Asterisk setup
if (! isset($conf->global->ASTERISK_HOST))      $conf->global->ASTERISK_HOST="127.0.0.1";
if (! isset($conf->global->ASTERISK_TYPE))      $conf->global->ASTERISK_TYPE="SIP/";
if (! isset($conf->global->ASTERISK_INDICATIF)) $conf->global->ASTERISK_INDICATIF="0";
if (! isset($conf->global->ASTERISK_PORT))      $conf->global->ASTERISK_PORT=5038;
if ($conf->global->ASTERISK_INDICATIF=='NONE')  $conf->global->ASTERISK_INDICATIF='';


$login = $_GET['login'];
$password = $_GET['password'];
$caller = $_GET['caller'];
$called = $_GET['called'];

// IP address of Asterisk server
$strHost = $conf->global->ASTERISK_HOST;
// Spécifiez le type d'extension par laquelle vous poste est connecte.
// ex: SIP/, IAX2/, ZAP/, etc
$channel = $conf->global->ASTERISK_TYPE;
// Indicatif de la ligne sortante
$prefix = $conf->global->ASTERISK_INDICATIF;
// Port
$port = $conf->global->ASTERISK_PORT;
// Context ( generalement from-internal )
$strContext = "from-internal";

// Delai d'attente avant de raccrocher
$strWaitTime = "30";
// Priority
$strPriority = "1";
// Nomber of try
$strMaxRetry = "2";


/*
 * View
 */

llxHeader();

$number=strtolower($called);
$pos=strpos($number,"local");
if (! empty($number))
{
    if ($pos===false)
    {
        $errno=0;
        $errstr=0;
        $strCallerId = "Dolibarr <".strtolower($caller).">";
        $oSocket = @fsockopen($strHost, $port, $errno, $errstr, 10);
        if (!$oSocket)
        {
            print '<body>'."\n";
            $txt="Failed to execute fsockopen($strHost, $port, \$errno, \$errstr, 10)<br>\n";
            print $txt;
            dol_syslog($txt,LOG_ERR);
            $txt=$errstr." (".$errno.")<br>\n";
            print $txt;
            dol_syslog($txt,LOG_ERR);
            print '</body>'."\n";
        }
        else
        {
            $txt="Call Asterisk dialer for caller: ".$caller.", called: ".$called." clicktodiallogin: ".$login;
            dol_syslog($txt);
            print '<body onload="javascript:history.go(-1);">'."\n";
            print '<!-- '.$txt.' -->';
            fputs($oSocket, "Action: login\r\n");
            fputs($oSocket, "Events: off\r\n");
            fputs($oSocket, "Username: $login\r\n");
            fputs($oSocket, "Secret: $password\r\n\r\n");
            fputs($oSocket, "Action: originate\r\n");
            fputs($oSocket, "Channel: ".$channel.$caller."\r\n");
            fputs($oSocket, "WaitTime: $strWaitTime\r\n");
            fputs($oSocket, "CallerId: $strCallerId\r\n");
            fputs($oSocket, "Exten: ".$prefix.$number."\r\n");
            fputs($oSocket, "Context: $strContext\r\n");
            fputs($oSocket, "Priority: $strPriority\r\n\r\n");
            fputs($oSocket, "Action: Logoff\r\n\r\n");
            sleep(2);
            fclose($oSocket);
            print '</body>'."\n";
        }
    }
}
else {
    print 'Bad parameters in URL. Must be '.$_SERVER['PHP_SELF'].'?caller=99999&called=99999&login=xxxxx&password=xxxxx';
}

llxFooter();
