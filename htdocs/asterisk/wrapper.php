<?php
/* Copyright (C) 2009-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
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

if (! defined('NOREQUIRESOC'))    define('NOREQUIRESOC', '1');
if (! defined('NOREQUIRETRAN'))   define('NOREQUIRETRAN', '1');
if (! defined('NOCSRFCHECK'))     define('NOCSRFCHECK', '1');
if (! defined('NOTOKENRENEWAL'))  define('NOTOKENRENEWAL', '1');
if (! defined('NOREQUIREMENU'))   define('NOREQUIREMENU', '1');
if (! defined('NOREQUIREHTML'))   define('NOREQUIREHTML', '1');
if (! defined('NOREQUIREAJAX'))   define('NOREQUIREAJAX', '1');

/**
 * Empty header
 *
 * @ignore
 * @return	void
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
 * @ignore
 * @return	void
 */
function llxFooter()
{
    print "\n".'</html>'."\n";
}

require_once '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';


// Security check
if (empty($conf->clicktodial->enabled))
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
if (! isset($conf->global->ASTERISK_CONTEXT))   $conf->global->ASTERISK_CONTEXT="from-internal";
if (! isset($conf->global->ASTERISK_WAIT_TIME)) $conf->global->ASTERISK_WAIT_TIME="30";
if (! isset($conf->global->ASTERISK_PRIORITY))  $conf->global->ASTERISK_PRIORITY="1";
if (! isset($conf->global->ASTERISK_MAX_RETRY)) $conf->global->ASTERISK_MAX_RETRY="2";


$login = GETPOST('login');
$password = GETPOST('password');
$caller = GETPOST('caller');
$called = GETPOST('called');

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
$strContext = $conf->global->ASTERISK_CONTEXT;
// Delai d'attente avant de raccrocher
$strWaitTime = $conf->global->ASTERISK_WAIT_TIME;
// Priority
$strPriority = $conf->global->ASTERISK_PRIORITY;
// Nomber of try
$strMaxRetry = $conf->global->ASTERISK_MAX_RETRY;


/*
 * View
 */

llxHeader();

$sql = "SELECT s.nom as name FROM ".MAIN_DB_PREFIX."societe as s";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."socpeople as sp ON sp.fk_soc = s.rowid";
$sql.= " WHERE s.entity IN (".getEntity('societe').")";
$sql.= " AND (s.phone='".$db->escape($called)."'";
$sql.= " OR sp.phone='".$db->escape($called)."'";
$sql.= " OR sp.phone_perso='".$db->escape($called)."'";
$sql.= " OR sp.phone_mobile='".$db->escape($called)."')";
$sql.= $db->plimit(1);

dol_syslog('click to dial search information with phone '.$called, LOG_DEBUG);
$resql = $db->query($sql);
if ($resql)
{
	$obj = $db->fetch_object($resql);
	if ($obj)
	{
		$found = $obj->name;
	} else {
		$found = $notfound;
	}
	$db->free($resql);
}
else
{
	dol_print_error($db, 'Error');
	$found = 'Error';
}

$number=strtolower($called);
$pos=strpos($number, "local");
if (! empty($number))
{
    if ($pos===false)
    {
        $errno=0;
        $errstr=0;
        $strCallerId = "Dolibarr call $found <".strtolower($number).">";
        $oSocket = @fsockopen($strHost, $port, $errno, $errstr, 10);
        if (!$oSocket)
        {
            print '<body>'."\n";
            $txt="Failed to execute fsockopen($strHost, $port, \$errno, \$errstr, 10)<br>\n";
            print $txt;
            dol_syslog($txt, LOG_ERR);
            $txt=$errstr." (".$errno.")<br>\n";
            print $txt;
            dol_syslog($txt, LOG_ERR);
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

// End of page
llxFooter();
$db->close();
