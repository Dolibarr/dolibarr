<?php
/* Copyright (C) 2009-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
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
 *	\remarks	To be used, an Asterisk user must be created by adding this in /etc/asterisk/manager.conf
 * 				[dolibarr]
 * 				secret = dolibarr
 * 				deny=0.0.0.0/0.0.0.0
 * 				permit=127.0.0.1/255.255.255.0
 * 				read = system,call,log,verbose,command,agent,user
 * 				write = system,call,log,verbose,command,agent,user
 */

if (!defined('NOREQUIRESOC')) {
	define('NOREQUIRESOC', '1');
}
if (!defined('NOREQUIRETRAN')) {
	define('NOREQUIRETRAN', '1');
}
if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', '1');
}
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', '1');
}
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', '1');
}
if (!defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}

/**
 * Empty header
 *
 * @param 	string 			$head				Optional head lines
 * @param 	string 			$title				HTML title
 * @param	string			$help_url			Url links to help page
 * 		                            			Syntax is: For a wiki page: EN:EnglishPage|FR:FrenchPage|ES:SpanishPage|DE:GermanPage
 *                                  			For other external page: http://server/url
 * @param	string			$target				Target to use on links
 * @param 	int    			$disablejs			More content into html header
 * @param 	int    			$disablehead		More content into html header
 * @param 	array|string  	$arrayofjs			Array of complementary js files
 * @param 	array|string  	$arrayofcss			Array of complementary css files
 * @param	string			$morequerystring	Query string to add to the link "print" to get same parameters (use only if autodetect fails)
 * @param   string  		$morecssonbody      More CSS on body tag. For example 'classforhorizontalscrolloftabs'.
 * @param	string			$replacemainareaby	Replace call to main_area() by a print of this string
 * @param	int				$disablenofollow	Disable the "nofollow" on meta robot header
 * @param	int				$disablenoindex		Disable the "noindex" on meta robot header
 * @return	void
 */
function llxHeader($head = '', $title = '', $help_url = '', $target = '', $disablejs = 0, $disablehead = 0, $arrayofjs = '', $arrayofcss = '', $morequerystring = '', $morecssonbody = '', $replacemainareaby = '', $disablenofollow = 0, $disablenoindex = 0)
{
	print '<html>'."\n";
	print '<head>'."\n";
	print '<title>Asterisk redirection from Dolibarr...</title>'."\n";
	print '</head>'."\n";
}

/**
 * Empty footer
 *
 * @param	string	$comment    				A text to add as HTML comment into HTML generated page
 * @param	string	$zone						'private' (for private pages) or 'public' (for public pages)
 * @param	int		$disabledoutputofmessages	Clear all messages stored into session without displaying them
 * @return	void
 */
function llxFooter($comment = '', $zone = 'private', $disabledoutputofmessages = 0)
{
	print "\n".'</html>'."\n";
}

require_once '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';


// Security check
if (!isModEnabled('clicktodial')) {
	accessforbidden();
	exit;
}


// Define Asterisk setup
if (!getDolGlobalString('ASTERISK_HOST')) {
	$conf->global->ASTERISK_HOST = "127.0.0.1";
}
if (!getDolGlobalString('ASTERISK_TYPE')) {
	$conf->global->ASTERISK_TYPE = "SIP/";
}
if (!getDolGlobalString('ASTERISK_INDICATIF')) {
	$conf->global->ASTERISK_INDICATIF = "0";
}
if (!getDolGlobalString('ASTERISK_PORT')) {
	$conf->global->ASTERISK_PORT = 5038;
}
if (getDolGlobalString('ASTERISK_INDICATIF') == 'NONE') {
	$conf->global->ASTERISK_INDICATIF = '';
}
if (!getDolGlobalString('ASTERISK_CONTEXT')) {
	$conf->global->ASTERISK_CONTEXT = "from-internal";
}
if (!getDolGlobalString('ASTERISK_WAIT_TIME')) {
	$conf->global->ASTERISK_WAIT_TIME = "30";
}
if (!getDolGlobalString('ASTERISK_PRIORITY')) {
	$conf->global->ASTERISK_PRIORITY = "1";
}
if (!getDolGlobalString('ASTERISK_MAX_RETRY')) {
	$conf->global->ASTERISK_MAX_RETRY = "2";
}


$login = GETPOST('login', 'alphanohtml');
$password = GETPOST('password', 'none');
$caller = GETPOST('caller', 'alphanohtml');
$called = GETPOST('called', 'alphanohtml');

// Sanitize input data to avoid to use the wrapper to inject malicious paylod into asterisk
$login = preg_replace('/[\n\r]/', '', $login);
$password = preg_replace('/[\n\r]/', '', $password);
$caller = preg_replace('/[\n\r]/', '', $caller);
$called = preg_replace('/[\n\r]/', '', $called);

// IP address of Asterisk server
$strHost = getDolGlobalString('ASTERISK_HOST');

// Specify the type of extension through which your extension is connected.
// ex: SIP/, IAX2/, ZAP/, etc
$channel = getDolGlobalString('ASTERISK_TYPE');

// Outgoing call sign
$prefix = getDolGlobalString('ASTERISK_INDICATIF');

// Asterisk Port
$port = getDolGlobalString('ASTERISK_PORT');

// Context ( generalement from-internal )
$strContext = getDolGlobalString('ASTERISK_CONTEXT');

// Waiting time before hanging up
$strWaitTime = getDolGlobalString('ASTERISK_WAIT_TIME');

// Priority
$strPriority = getDolGlobalString('ASTERISK_PRIORITY');

// Number of call attempts
$strMaxRetry = getDolGlobalString('ASTERISK_MAX_RETRY');


/*
 * View
 */

llxHeader();

$sql = "SELECT s.nom as name FROM ".MAIN_DB_PREFIX."societe as s";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."socpeople as sp ON sp.fk_soc = s.rowid";
$sql .= " WHERE s.entity IN (".getEntity('societe').")";
$sql .= " AND (s.phone='".$db->escape($called)."'";
$sql .= " OR sp.phone='".$db->escape($called)."'";
$sql .= " OR sp.phone_perso='".$db->escape($called)."'";
$sql .= " OR sp.phone_mobile='".$db->escape($called)."')";
$sql .= $db->plimit(1);

dol_syslog('click to dial search information with phone '.$called, LOG_DEBUG);
$resql = $db->query($sql);
if ($resql) {
	$obj = $db->fetch_object($resql);
	if ($obj) {
		$found = $obj->name;
	} else {
		$found = 'Not found';
	}
	$db->free($resql);
} else {
	dol_print_error($db, 'Error');
	$found = 'Error';
}

$number = strtolower($called);
$pos = strpos($number, "local");
if (!empty($number)) {
	if ($pos === false) {
		$errno = 0;
		$errstr = 0;
		$strCallerId = "Dolibarr caller $found <".strtolower($number).">";
		$oSocket = @fsockopen($strHost, (int) $port, $errno, $errstr, 10);
		if (!$oSocket) {
			print '<body>'."\n";
			$txt = "Failed to execute fsockopen($strHost, $port, \$errno, \$errstr, 10)<br>\n";
			print $txt;
			dol_syslog($txt, LOG_ERR);
			$txt = $errstr." (".$errno.")<br>\n";
			print $txt;
			dol_syslog($txt, LOG_ERR);
			print '</body>'."\n";
		} else {
			$txt = "Call Asterisk dialer for caller: ".$caller.", called: ".$called." clicktodiallogin: ".$login;
			dol_syslog($txt);
			print '<body onload="history.go(-1);">'."\n";
			print '<!-- '.$txt.' -->';
			fwrite($oSocket, "Action: login\r\n");
			fwrite($oSocket, "Events: off\r\n");
			fwrite($oSocket, "Username: $login\r\n");
			fwrite($oSocket, "Secret: $password\r\n\r\n");
			fwrite($oSocket, "Action: originate\r\n");
			fwrite($oSocket, "Channel: ".$channel.$caller."\r\n");
			fwrite($oSocket, "WaitTime: $strWaitTime\r\n");
			fwrite($oSocket, "CallerId: $strCallerId\r\n");
			fwrite($oSocket, "Exten: ".$prefix.$number."\r\n");
			fwrite($oSocket, "Context: $strContext\r\n");
			fwrite($oSocket, "Priority: $strPriority\r\n\r\n");
			fwrite($oSocket, "Action: Logoff\r\n\r\n");
			sleep(2);
			fclose($oSocket);
			print '</body>'."\n";
		}
	}
} else {
	print 'Bad parameters in URL. Must be '.dol_escape_htmltag($_SERVER['PHP_SELF']).'?caller=99999&called=99999&login=xxxxx&password=xxxxx';
}

// End of page
llxFooter();
$db->close();
