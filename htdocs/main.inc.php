<?php
/* Copyright (C) 2002-2007  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2003       Xavier Dutoit           <doli@sydesy.com>
 * Copyright (C) 2004-2021  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2004       Sebastien Di Cintio     <sdicintio@ressource-toi.org>
 * Copyright (C) 2004       Benoit Mortier          <benoit.mortier@opensides.be>
 * Copyright (C) 2005-2021  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2011-2014  Philippe Grand          <philippe.grand@atoo-net.com>
 * Copyright (C) 2008       Matteli
 * Copyright (C) 2011-2016  Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2012       Christophe Battarel     <christophe.battarel@altairis.fr>
 * Copyright (C) 2014-2015  Marcos García           <marcosgdf@gmail.com>
 * Copyright (C) 2015       Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2020       Demarest Maxime         <maxime@indelog.fr>
 * Copyright (C) 2020       Charlene Benke          <charlie@patas-monkey.com>
 * Copyright (C) 2021-2024  Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2021       Alexandre Spangaro      <aspangaro@open-dsi.fr>
 * Copyright (C) 2023       Joachim Küter      		<git-jk@bloxera.com>
 * Copyright (C) 2023       Eric Seigne      		<eric.seigne@cap-rel.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 *	\file       htdocs/main.inc.php
 *	\ingroup	core
 *	\brief      File that defines environment for Dolibarr GUI pages only (file not required by scripts)
 */

//@ini_set('memory_limit', '128M');	// This may be useless if memory is hard limited by your PHP

// For optional tuning. Enabled if environment variable MAIN_SHOW_TUNING_INFO is defined.
$micro_start_time = 0;
if (!empty($_SERVER['MAIN_SHOW_TUNING_INFO'])) {
	list($usec, $sec) = explode(" ", microtime());
	$micro_start_time = ((float) $usec + (float) $sec);
	// Add Xdebug code coverage
	//define('XDEBUGCOVERAGE',1);
	if (defined('XDEBUGCOVERAGE')) {
		xdebug_start_code_coverage();
	}
}

/**
 * Return array of Emojis. We can't move this function inside a common lib because we need it for security before loading any file.
 *
 * @return 	array<string,array<string>>			Array of Emojis in hexadecimal
 * @see getArrayOfEmojiBis()
 */
function getArrayOfEmoji()
{
	$arrayofcommonemoji = array(
		'misc' => array('2600', '26FF'),		// Miscellaneous Symbols
		'ding' => array('2700', '27BF'),		// Dingbats
		'????' => array('9989', '9989'),		// Variation Selectors
		'vars' => array('FE00', 'FE0F'),		// Variation Selectors
		'pict' => array('1F300', '1F5FF'),		// Miscellaneous Symbols and Pictographs
		'emot' => array('1F600', '1F64F'),		// Emoticons
		'tran' => array('1F680', '1F6FF'),		// Transport and Map Symbols
		'flag' => array('1F1E0', '1F1FF'),		// Flags (note: may be 1F1E6 instead of 1F1E0)
		'supp' => array('1F900', '1F9FF'),		// Supplemental Symbols and Pictographs
	);

	return $arrayofcommonemoji;
}

/**
 * Return the real char for a numeric entities.
 * WARNING: This function is required by testSqlAndScriptInject() and the GETPOST 'restricthtml'. Regex calling must be similar.
 *
 * @param	array<int,string>	$matches			Array with a decimal numeric entity into key 0, value without the &# into the key 1
 * @return	string									New value
 */
function realCharForNumericEntities($matches)
{
	$newstringnumentity = preg_replace('/;$/', '', $matches[1]);
	//print  ' $newstringnumentity='.$newstringnumentity;

	if (preg_match('/^x/i', $newstringnumentity)) {		// if numeric is hexadecimal
		$newstringnumentity = hexdec(preg_replace('/^x/i', '', $newstringnumentity));
	} else {
		$newstringnumentity = (int) $newstringnumentity;
	}

	// The numeric values we don't want as entities because they encode ascii char, and why using html entities on ascii except for haking ?
	if (($newstringnumentity >= 65 && $newstringnumentity <= 90) || ($newstringnumentity >= 97 && $newstringnumentity <= 122)) {
		return chr((int) $newstringnumentity);
	}

	// The numeric values we want in UTF8 instead of entities because it is emoji
	$arrayofemojis = getArrayOfEmoji();
	foreach ($arrayofemojis as $valarray) {
		if ($newstringnumentity >= hexdec($valarray[0]) && $newstringnumentity <= hexdec($valarray[1])) {
			// This is a known emoji
			return html_entity_decode($matches[0], ENT_COMPAT | ENT_HTML5, 'UTF-8');
		}
	}

	return '&#'.$matches[1]; // Value will be unchanged because regex was /&#(  )/
}

/**
 * Security: WAF layer for SQL Injection and XSS Injection (scripts) protection (Filters on GET, POST, PHP_SELF).
 * Warning: Such a protection can't be enough. It is not reliable as it will always be possible to bypass this. Good protection can
 * only be guaranteed by escaping data during output.
 *
 * @param		string		$val		Brute value found into $_GET, $_POST or PHP_SELF
 * @param		string		$type		0=POST, 1=GET, 2=PHP_SELF, 3=GET without sql reserved keywords (the less tolerant test)
 * @return		int						>0 if there is an injection, 0 if none
 */
function testSqlAndScriptInject($val, $type)
{
	// Decode string first because a lot of things are obfuscated by encoding or multiple encoding.
	// So <svg o&#110;load='console.log(&quot;123&quot;)' become <svg onload='console.log(&quot;123&quot;)'
	// So "&colon;&apos;" become ":'" (due to ENT_HTML5)
	// So "&Tab;&NewLine;" become ""
	// So "&lpar;&rpar;" become "()"

	// Loop to decode until no more things to decode.
	//print "before decoding $val\n";
	do {
		$oldval = $val;
		$val = html_entity_decode($val, ENT_QUOTES | ENT_HTML5);	// Decode '&colon;', '&apos;', '&Tab;', '&NewLine', ...
		// Sometimes we have entities without the ; at end so html_entity_decode does not work but entities is still interpreted by browser.
		$val = preg_replace_callback(
			'/&#(x?[0-9][0-9a-f]+;?)/i',
			/**
			 * @param string[] $m
			 * @return string
			 */
			static function ($m) {
				// Decode '&#110;', ...
				return realCharForNumericEntities($m);
			},
			$val
		);

		// We clean html comments because some hacks try to obfuscate evil strings by inserting HTML comments. Example: on<!-- -->error=alert(1)
		$val = preg_replace('/<!--[^>]*-->/', '', $val);
		$val = preg_replace('/[\r\n\t]/', '', $val);
	} while ($oldval != $val);
	//print "type = ".$type." after decoding: ".$val."\n";

	$inj = 0;

	// We check string because some hacks try to obfuscate evil strings by inserting non printable chars. Example: 'java(ascci09)scr(ascii00)ipt' is processed like 'javascript' (whatever is place of evil ascii char)
	// We should use dol_string_nounprintableascii but function is not yet loaded/available
	// Example of valid UTF8 chars:
	// utf8 or utf8mb3: '\x09', '\x0A', '\x0D', '\x7E'
	// utf8 or utf8mb3: '\xE0\xA0\x80'
	// utf8mb4: 		'\xF0\x9D\x84\x9E'   (so this may be refused by the database insert if pagecode is utf8=utf8mb3)
	$newval = preg_replace('/[\x00-\x08\x0B-\x0C\x0E-\x1F\x7F]/u', '', $val); // /u operator makes UTF8 valid characters being ignored so are not included into the replace

	// Note that $newval may also be completely empty '' when non valid UTF8 are found.
	if ($newval != $val) {
		// If $val has changed after removing non valid UTF8 chars, it means we have an evil string.
		$inj += 1;
	}
	//print 'inj='.$inj.'-type='.$type.'-val='.$val.'-newval='.$newval."\n";

	// For SQL Injection (only GET are used to scan for such injection strings)
	if ($type == 1 || $type == 3) {
		// Note the \s+ is replaced into \s* because some spaces may have been modified in previous loop
		$inj += preg_match('/delete\s*from/i', $val);
		$inj += preg_match('/create\s*table/i', $val);
		$inj += preg_match('/insert\s*into/i', $val);
		$inj += preg_match('/select\s*from/i', $val);
		$inj += preg_match('/into\s*(outfile|dumpfile)/i', $val);
		$inj += preg_match('/user\s*\(/i', $val); // avoid to use function user() or mysql_user() that return current database login
		$inj += preg_match('/information_schema/i', $val); // avoid to use request that read information_schema database
		$inj += preg_match('/<svg/i', $val); // <svg can be allowed in POST
		$inj += preg_match('/update[^&=\w].*set.+=/i', $val);	// the [^&=\w] test is to avoid error when request is like action=update&...set... or &updatemodule=...set...
		$inj += preg_match('/union.+select/i', $val);
	}
	if ($type == 3) {
		// Note the \s+ is replaced into \s* because some spaces may have been modified in previous loop
		$inj += preg_match('/select|update|delete|truncate|replace|group\s*by|concat|count|from|union/i', $val);
	}
	if ($type != 2) {	// Not common key strings, so we can check them both on GET and POST
		$inj += preg_match('/updatexml\(/i', $val);
		$inj += preg_match('/(\.\.%2f)+/i', $val);
		$inj += preg_match('/\s@@/', $val);
	}
	// For XSS Injection done by closing textarea to execute content into a textarea field
	$inj += preg_match('/<\/textarea/i', $val);
	// For XSS Injection done by adding javascript with script
	// This is all cases a browser consider text is javascript:
	// When it found '<script', 'javascript:', '<style', 'onload\s=' on body tag, '="&' on a tag size with old browsers
	// All examples on page: http://ha.ckers.org/xss.html#XSScalc
	// More on https://www.owasp.org/index.php/XSS_Filter_Evasion_Cheat_Sheet
	$inj += preg_match('/<audio/i', $val);
	$inj += preg_match('/<embed/i', $val);
	$inj += preg_match('/<iframe/i', $val);
	$inj += preg_match('/<object/i', $val);
	$inj += preg_match('/<script/i', $val);
	$inj += preg_match('/Set\.constructor/i', $val); // ECMA script 6
	if (!defined('NOSTYLECHECK')) {
		$inj += preg_match('/<style/i', $val);
	}
	$inj += preg_match('/base\s+href/si', $val);
	$inj += preg_match('/=data:/si', $val);
	// List of dom events is on https://www.w3schools.com/jsref/dom_obj_event.asp and https://developer.mozilla.org/en-US/docs/Web/Events
	$inj += preg_match('/on(mouse|drag|key|load|touch|pointer|select|transition)[a-z]*\s*=/i', $val); // onmousexxx can be set on img or any html tag like <img title='...' onmouseover=alert(1)>
	$inj += preg_match('/on(abort|after|animation|auxclick|before|blur|cancel|canplay|canplaythrough|change|click|close|contextmenu|cuechange|copy|cut)[a-z]*\s*=/i', $val);
	$inj += preg_match('/on(dblclick|drop|durationchange|emptied|end|ended|error|focus|focusin|focusout|formdata|gotpointercapture|hashchange|input|invalid)[a-z]*\s*=/i', $val);
	$inj += preg_match('/on(lostpointercapture|offline|online|pagehide|pageshow)[a-z]*\s*=/i', $val);
	$inj += preg_match('/on(paste|pause|play|playing|progress|ratechange|reset|resize|scroll|search|seeked|seeking|show|stalled|start|submit|suspend)[a-z]*\s*=/i', $val);
	$inj += preg_match('/on(timeupdate|toggle|unload|volumechange|waiting|wheel)[a-z]*\s*=/i', $val);
	// More not into the previous list

	$inj += preg_match('/on(repeat|begin|finish|beforeinput)[a-z]*\s*=/i', $val);

	// We refuse html into html because some hacks try to obfuscate evil strings by inserting HTML into HTML. Example: <img on<a>error=alert(1) to bypass test on onerror
	$tmpval = preg_replace('/<[^<]+>/', '', $val);
	// List of dom events is on https://www.w3schools.com/jsref/dom_obj_event.asp and https://developer.mozilla.org/en-US/docs/Web/Events
	$inj += preg_match('/on(mouse|drag|key|load|touch|pointer|select|transition)[a-z]*\s*=/i', $tmpval); // onmousexxx can be set on img or any html tag like <img title='...' onmouseover=alert(1)>
	$inj += preg_match('/on(abort|after|animation|auxclick|before|blur|cancel|canplay|canplaythrough|change|click|close|contextmenu|cuechange|copy|cut)[a-z]*\s*=/i', $tmpval);
	$inj += preg_match('/on(dblclick|drop|durationchange|emptied|end|ended|error|focus|focusin|focusout|formdata|gotpointercapture|hashchange|input|invalid)[a-z]*\s*=/i', $tmpval);
	$inj += preg_match('/on(lostpointercapture|offline|online|pagehide|pageshow)[a-z]*\s*=/i', $tmpval);
	$inj += preg_match('/on(paste|pause|play|playing|progress|ratechange|reset|resize|scroll|search|seeked|seeking|show|stalled|start|submit|suspend)[a-z]*\s*=/i', $tmpval);
	$inj += preg_match('/on(timeupdate|toggle|unload|volumechange|waiting|wheel)[a-z]*\s*=/i', $tmpval);
	// More not into the previous list
	$inj += preg_match('/on(repeat|begin|finish|beforeinput)[a-z]*\s*=/i', $tmpval);

	//$inj += preg_match('/on[A-Z][a-z]+\*=/', $val);   // To lock event handlers onAbort(), ...
	$inj += preg_match('/&#58;|&#0000058|&#x3A/i', $val); // refused string ':' encoded (no reason to have it encoded) to lock 'javascript:...'
	$inj += preg_match('/j\s*a\s*v\s*a\s*s\s*c\s*r\s*i\s*p\s*t\s*:/i', $val);
	$inj += preg_match('/vbscript\s*:/i', $val);
	// For XSS Injection done by adding javascript closing html tags like with onmousemove, etc... (closing a src or href tag with not cleaned param)
	if ($type == 1 || $type == 3) {
		$val = str_replace('enclosure="', 'enclosure=X', $val); // We accept enclosure=" for the export/import module
		$inj += preg_match('/"/i', $val); // We refused " in GET parameters value.
	}
	if ($type == 2) {
		$inj += preg_match('/[:;"\'<>\?\(\){}\$%]/', $val); // PHP_SELF is a file system (or url path without parameters). It can contains spaces.
	}

	return $inj;
}

/**
 * Return true if security check on parameters are OK, false otherwise.
 *
 * @param		string|array<string,string>	$var		Variable name
 * @param		int<0,2>		$type		1=GET, 0=POST, 2=PHP_SELF
 * @param		int<0,1>		$stopcode	0=No stop code, 1=Stop code (default) if injection found
 * @return		boolean						True if there is no injection.
 */
function analyseVarsForSqlAndScriptsInjection(&$var, $type, $stopcode = 1)
{
	if (is_array($var)) {
		foreach ($var as $key => $value) {	// Warning, $key may also be used for attacks
			// Exclude check for some variable keys
			if ($type === 0 && defined('NOSCANPOSTFORINJECTION') && is_array(constant('NOSCANPOSTFORINJECTION')) && in_array($key, constant('NOSCANPOSTFORINJECTION'))) {
				continue;
			}

			if (analyseVarsForSqlAndScriptsInjection($key, $type, $stopcode) && analyseVarsForSqlAndScriptsInjection($value, $type, $stopcode)) {
				//$var[$key] = $value;	// This is useless
			} else {
				http_response_code(403);

				// Get remote IP: PS: We do not use getRemoteIP(), function is not yet loaded and we need a value that can't be spoofed
				$ip = (empty($_SERVER['REMOTE_ADDR']) ? 'unknown' : $_SERVER['REMOTE_ADDR']);

				if ($stopcode) {
					$errormessage = 'Access refused to '.htmlentities($ip, ENT_COMPAT, 'UTF-8').' by SQL or Script injection protection in main.inc.php:analyseVarsForSqlAndScriptsInjection type='.htmlentities((string) $type, ENT_COMPAT, 'UTF-8');
					//$errormessage .= ' paramkey='.htmlentities($key, ENT_COMPAT, 'UTF-8');	// Disabled to avoid text injection

					$errormessage2 = 'page='.htmlentities((empty($_SERVER["REQUEST_URI"]) ? '' : $_SERVER["REQUEST_URI"]), ENT_COMPAT, 'UTF-8');
					$errormessage2 .= ' paramtype='.htmlentities((string) $type, ENT_COMPAT, 'UTF-8');
					$errormessage2 .= ' paramkey='.htmlentities($key, ENT_COMPAT, 'UTF-8');
					$errormessage2 .= ' paramvalue='.htmlentities($value, ENT_COMPAT, 'UTF-8');

					print $errormessage;
					print "<br>\n";
					print 'Try to go back, fix data of your form and resubmit it. You can contact also your technical support.';

					print "\n".'<!--'."\n";
					print $errormessage2;
					print "\n".'-->';

					// Add entry into the PHP server error log
					if (function_exists('error_log')) {
						error_log($errormessage.' '.substr($errormessage2, 2000));
					}

					// Note: No addition into security audit table is done because we don't want to execute code in such a case.
					// Detection of too many such requests can be done with a fail2ban rule on 403 error code or into the PHP server error log.


					if (class_exists('PHPUnit\Framework\TestSuite')) {
						$message = $errormessage.' '.substr($errormessage2, 2000);
						throw new Exception("Security injection exception: $message");
					}
					exit;
				} else {
					return false;
				}
			}
		}
		return true;
	} else {
		return (testSqlAndScriptInject($var, $type) <= 0);
	}
}

// To disable the WAF for GET and POST and PHP_SELF, uncomment this
//define('NOSCANPHPSELFFORINJECTION', 1);
//define('NOSCANGETFORINJECTION', 1);
//define('NOSCANPOSTFORINJECTION', 1 or 2);

// Check consistency of NOREQUIREXXX DEFINES
if ((defined('NOREQUIREDB') || defined('NOREQUIRETRAN')) && !defined('NOREQUIREMENU')) {
	print 'If define NOREQUIREDB or NOREQUIRETRAN are set, you must also set NOREQUIREMENU or not set them.';
	exit;
}
if (defined('NOREQUIREUSER') && !defined('NOREQUIREMENU')) {
	print 'If define NOREQUIREUSER is set, you must also set NOREQUIREMENU or not set it.';
	exit;
}

// Sanity check on URL
if (!defined('NOSCANPHPSELFFORINJECTION') && !empty($_SERVER["PHP_SELF"])) {
	$morevaltochecklikepost = array($_SERVER["PHP_SELF"]);
	analyseVarsForSqlAndScriptsInjection($morevaltochecklikepost, 2);
}
// Sanity check on GET parameters
if (!defined('NOSCANGETFORINJECTION') && !empty($_SERVER["QUERY_STRING"])) {
	// Note: QUERY_STRING is url encoded, but $_GET and $_POST are already decoded
	// Because the analyseVarsForSqlAndScriptsInjection is designed for already url decoded value, we must decode QUERY_STRING
	// Another solution is to provide $_GET as parameter with analyseVarsForSqlAndScriptsInjection($_GET, 1);
	$morevaltochecklikeget = array(urldecode($_SERVER["QUERY_STRING"]));
	analyseVarsForSqlAndScriptsInjection($morevaltochecklikeget, 1);
}
// Sanity check on POST
if (!defined('NOSCANPOSTFORINJECTION') || is_array(constant('NOSCANPOSTFORINJECTION'))) {
	analyseVarsForSqlAndScriptsInjection($_POST, 0);
}

// This is to make Dolibarr working with Plesk
if (!empty($_SERVER['DOCUMENT_ROOT']) && substr($_SERVER['DOCUMENT_ROOT'], -6) !== 'htdocs') {
	set_include_path($_SERVER['DOCUMENT_ROOT'].'/htdocs');
}

// Include the conf.php and functions.lib.php and security.lib.php. This defined the constants like DOL_DOCUMENT_ROOT, DOL_DATA_ROOT, DOL_URL_ROOT...
require_once 'filefunc.inc.php';

// If there is a POST parameter to tell to save automatically some POST parameters into cookies, we do it.
// This is used for example by form of boxes to save personalization of some options.
// DOL_AUTOSET_COOKIE=cookiename:val1,val2 and  cookiename_val1=aaa cookiename_val2=bbb will set cookie_name with value json_encode(array('val1'=> , ))
if (GETPOST("DOL_AUTOSET_COOKIE")) {
	$tmpautoset = explode(':', GETPOST("DOL_AUTOSET_COOKIE"), 2);
	$tmplist = explode(',', $tmpautoset[1]);
	$cookiearrayvalue = array();
	foreach ($tmplist as $tmpkey) {
		$postkey = $tmpautoset[0].'_'.$tmpkey;
		//var_dump('tmpkey='.$tmpkey.' postkey='.$postkey.' value='.GETPOST($postkey);
		if (GETPOST($postkey)) {
			$cookiearrayvalue[$tmpkey] = GETPOST($postkey);
		}
	}
	$cookiename = $tmpautoset[0];
	$cookievalue = json_encode($cookiearrayvalue);
	//var_dump('setcookie cookiename='.$cookiename.' cookievalue='.$cookievalue);
	if (PHP_VERSION_ID < 70300) {
		setcookie($cookiename, empty($cookievalue) ? '' : $cookievalue, empty($cookievalue) ? 0 : (time() + (86400 * 354)), '/', '', ((empty($dolibarr_main_force_https) && isHTTPS() === false) ? false : true), true); // keep cookie 1 year and add tag httponly
	} else {
		// Only available for php >= 7.3
		$cookieparams = array(
			'expires' => empty($cookievalue) ? 0 : (time() + (86400 * 354)),
			'path' => '/',
			//'domain' => '.mywebsite.com', // the dot at the beginning allows compatibility with subdomains
			'secure' => ((empty($dolibarr_main_force_https) && isHTTPS() === false) ? false : true),
			'httponly' => true,
			'samesite' => 'Lax'	// None || Lax  || Strict
		);
		setcookie($cookiename, empty($cookievalue) ? '' : $cookievalue, $cookieparams);
	}
	if (empty($cookievalue)) {
		unset($_COOKIE[$cookiename]);
	}
}

// Set the handler of session
// if (ini_get('session.save_handler') == 'user')
if (!empty($php_session_save_handler) && $php_session_save_handler == 'db') {
	require_once 'core/lib/phpsessionin'.$php_session_save_handler.'.lib.php';
}

// Init session. Name of session is specific to Dolibarr instance.
// Must be done after the include of filefunc.inc.php so global variables of conf file are defined (like $dolibarr_main_instance_unique_id or $dolibarr_main_force_https).
// Note: the function dol_getprefix() is defined into functions.lib.php but may have been defined to return a different key to manage another area to protect.
$prefix = dol_getprefix('');
$sessionname = 'DOLSESSID_'.$prefix;
$sessiontimeout = 'DOLSESSTIMEOUT_'.$prefix;
if (!empty($_COOKIE[$sessiontimeout])) {
	ini_set('session.gc_maxlifetime', $_COOKIE[$sessiontimeout]);
}

// This create lock, released by session_write_close() or end of page.
// We need this lock as long as we read/write $_SESSION ['vars']. We can remove lock when finished.
if (!defined('NOSESSION')) {
	if (PHP_VERSION_ID < 70300) {
		session_set_cookie_params(0, '/', null, ((empty($dolibarr_main_force_https) && isHTTPS() === false) ? false : true), true); // Add tag secure and httponly on session cookie (same as setting session.cookie_httponly into php.ini). Must be called before the session_start.
	} else {
		// Only available for php >= 7.3
		$sessioncookieparams = array(
			'lifetime' => 0,
			'path' => '/',
			//'domain' => '.mywebsite.com', // the dot at the beginning allows compatibility with subdomains
			'secure' => ((empty($dolibarr_main_force_https) && isHTTPS() === false) ? false : true),
			'httponly' => true,
			'samesite' => 'Lax'	// None || Lax  || Strict
		);
		session_set_cookie_params($sessioncookieparams);
	}
	session_name($sessionname);
	dol_session_start();	// This call the open and read of session handler
	//exit;	// this exist generates a call to write and close
}


// Init the 6 global objects, this include will make the 'new Xxx()' and set properties for: $conf, $db, $langs, $user, $mysoc, $hookmanager
require_once 'master.inc.php';

// Uncomment this and set session.save_handler = user to use local session storing
// include DOL_DOCUMENT_ROOT.'/core/lib/phpsessionindb.inc.php

// If software has been locked. Only login $conf->global->MAIN_ONLY_LOGIN_ALLOWED is allowed.
if (getDolGlobalString('MAIN_ONLY_LOGIN_ALLOWED')) {
	$ok = 0;
	if ((!session_id() || !isset($_SESSION["dol_login"])) && !isset($_POST["username"]) && !empty($_SERVER["GATEWAY_INTERFACE"])) {
		$ok = 1; // We let working pages if not logged and inside a web browser (login form, to allow login by admin)
	} elseif (isset($_POST["username"]) && $_POST["username"] == $conf->global->MAIN_ONLY_LOGIN_ALLOWED) {
		$ok = 1; // We let working pages that is a login submission (login submit, to allow login by admin)
	} elseif (defined('NOREQUIREDB')) {
		$ok = 1; // We let working pages that don't need database access (xxx.css.php)
	} elseif (defined('EVEN_IF_ONLY_LOGIN_ALLOWED')) {
		$ok = 1; // We let working pages that ask to work even if only login enabled (logout.php)
	} elseif (session_id() && isset($_SESSION["dol_login"]) && $_SESSION["dol_login"] == $conf->global->MAIN_ONLY_LOGIN_ALLOWED) {
		$ok = 1; // We let working if user is allowed admin
	}
	if (!$ok) {
		if (session_id() && isset($_SESSION["dol_login"]) && $_SESSION["dol_login"] != $conf->global->MAIN_ONLY_LOGIN_ALLOWED) {
			print 'Sorry, your application is offline.'."\n";
			print 'You are logged with user "'.$_SESSION["dol_login"].'" and only administrator user "' . getDolGlobalString('MAIN_ONLY_LOGIN_ALLOWED').'" is allowed to connect for the moment.'."\n";
			$nexturl = DOL_URL_ROOT.'/user/logout.php?token='.newToken();
			print 'Please try later or <a href="'.$nexturl.'">click here to disconnect and change login user</a>...'."\n";
		} else {
			print 'Sorry, your application is offline. Only administrator user "' . getDolGlobalString('MAIN_ONLY_LOGIN_ALLOWED').'" is allowed to connect for the moment.'."\n";
			$nexturl = DOL_URL_ROOT.'/';
			print 'Please try later or <a href="'.$nexturl.'">click here to change login user</a>...'."\n";
		}
		exit;
	}
}


// Activate end of page function
register_shutdown_function('dol_shutdown');

// Load debugbar
if (isModEnabled('debugbar') && !GETPOST('dol_use_jmobile') && empty($_SESSION['dol_use_jmobile'])) {
	global $debugbar;
	include_once DOL_DOCUMENT_ROOT.'/debugbar/class/DebugBar.php';
	$debugbar = new DolibarrDebugBar();
	$renderer = $debugbar->getJavascriptRenderer();
	if (!getDolGlobalString('MAIN_HTML_HEADER')) {
		$conf->global->MAIN_HTML_HEADER = '';
	}
	$conf->global->MAIN_HTML_HEADER .= $renderer->renderHead();

	$debugbar['time']->startMeasure('pageaftermaster', 'Page generation (after environment init)');
}

// Detection browser
if (isset($_SERVER["HTTP_USER_AGENT"])) {
	$tmp = getBrowserInfo($_SERVER["HTTP_USER_AGENT"]);
	$conf->browser->name = $tmp['browsername'];
	$conf->browser->os = $tmp['browseros'];
	$conf->browser->version = $tmp['browserversion'];
	$conf->browser->ua = $tmp['browserua'];
	$conf->browser->layout = $tmp['layout']; // 'classic', 'phone', 'tablet'
	//var_dump($conf->browser);

	if ($conf->browser->layout == 'phone') {
		$conf->dol_no_mouse_hover = 1;
	}
}

// If theme is forced
if (GETPOST('theme', 'aZ09')) {
	$conf->theme = GETPOST('theme', 'aZ09');
	$conf->css = "/theme/".$conf->theme."/style.css.php";
}

// Set global MAIN_OPTIMIZEFORTEXTBROWSER (must be before login part)
if (GETPOSTINT('textbrowser') || (!empty($conf->browser->name) && $conf->browser->name == 'lynxlinks')) {   // If we must enable text browser
	$conf->global->MAIN_OPTIMIZEFORTEXTBROWSER = 2;
}

// Force HTTPS if required ($conf->file->main_force_https is 0/1 or 'https dolibarr root url')
// $_SERVER["HTTPS"] is 'on' when link is https, otherwise $_SERVER["HTTPS"] is empty or 'off'
if (!empty($conf->file->main_force_https) && !isHTTPS() && !defined('NOHTTPSREDIRECT')) {
	$newurl = '';
	if (is_numeric($conf->file->main_force_https)) {
		if ($conf->file->main_force_https == '1' && !empty($_SERVER["SCRIPT_URI"])) {	// If SCRIPT_URI supported by server
			if (preg_match('/^http:/i', $_SERVER["SCRIPT_URI"]) && !preg_match('/^https:/i', $_SERVER["SCRIPT_URI"])) {	// If link is http
				$newurl = preg_replace('/^http:/i', 'https:', $_SERVER["SCRIPT_URI"]);
			}
		} else {
			// Check HTTPS environment variable (Apache/mod_ssl only)
			$newurl = preg_replace('/^http:/i', 'https:', DOL_MAIN_URL_ROOT).$_SERVER["REQUEST_URI"];
		}
	} else {
		// Check HTTPS environment variable (Apache/mod_ssl only)
		$newurl = $conf->file->main_force_https.$_SERVER["REQUEST_URI"];
	}
	// Start redirect
	if ($newurl) {
		header_remove(); // Clean header already set to be sure to remove any header like "Set-Cookie: DOLSESSID_..." from non HTTPS answers
		dol_syslog("main.inc: dolibarr_main_force_https is on, we make a redirect to ".$newurl);
		header("Location: ".$newurl);
		exit;
	} else {
		dol_syslog("main.inc: dolibarr_main_force_https is on but we failed to forge new https url so no redirect is done", LOG_WARNING);
	}
}

if (!defined('NOLOGIN') && !defined('NOIPCHECK') && !empty($dolibarr_main_restrict_ip)) {
	$listofip = explode(',', $dolibarr_main_restrict_ip);
	$found = false;
	foreach ($listofip as $ip) {
		$ip = trim($ip);
		if ($ip == $_SERVER['REMOTE_ADDR']) {
			$found = true;
			break;
		}
	}
	if (!$found) {
		print 'Access refused by IP protection. Your detected IP is '.$_SERVER['REMOTE_ADDR'];
		exit;
	}
}

// Loading of additional presentation includes
if (!defined('NOREQUIREHTML')) {
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php'; // Need 660ko memory (800ko in 2.2)
}
if (!defined('NOREQUIREAJAX')) {
	require_once DOL_DOCUMENT_ROOT.'/core/lib/ajax.lib.php'; // Need 22ko memory
}

// If install or upgrade process not done or not completely finished, we call the install page.
if (getDolGlobalString('MAIN_NOT_INSTALLED') || getDolGlobalString('MAIN_NOT_UPGRADED')) {
	dol_syslog("main.inc: A previous install or upgrade was not complete. Redirect to install page.", LOG_WARNING);
	header("Location: ".DOL_URL_ROOT."/install/index.php");
	exit;
}
// If an upgrade process is required, we call the install page.
$checkifupgraderequired = false;
if (getDolGlobalString('MAIN_VERSION_LAST_UPGRADE') && getDolGlobalString('MAIN_VERSION_LAST_UPGRADE') != DOL_VERSION) {
	$checkifupgraderequired = true;
}
if (!getDolGlobalString('MAIN_VERSION_LAST_UPGRADE') && getDolGlobalString('MAIN_VERSION_LAST_INSTALL') && getDolGlobalString('MAIN_VERSION_LAST_INSTALL') != DOL_VERSION) {
	$checkifupgraderequired = true;
}
if ($checkifupgraderequired) {
	$versiontocompare = getDolGlobalString('MAIN_VERSION_LAST_UPGRADE', getDolGlobalString('MAIN_VERSION_LAST_INSTALL'));
	require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
	$dolibarrversionlastupgrade = preg_split('/[.-]/', $versiontocompare);
	$dolibarrversionprogram = preg_split('/[.-]/', DOL_VERSION);
	$rescomp = versioncompare($dolibarrversionprogram, $dolibarrversionlastupgrade);
	if ($rescomp > 0) {   // Programs have a version higher than database.
		if (!getDolGlobalString('MAIN_NO_UPGRADE_REDIRECT_ON_LEVEL_3_CHANGE') || $rescomp < 3) {
			// We did not add "&& $rescomp < 3" because we want upgrade process for build upgrades
			dol_syslog("main.inc: database version ".$versiontocompare." is lower than programs version ".DOL_VERSION.". Redirect to install/upgrade page.", LOG_WARNING);
			if (php_sapi_name() === "cli") {
				print "main.inc: database version ".$versiontocompare." is lower than programs version ".DOL_VERSION.". Try to run upgrade process.\n";
			} else {
				header("Location: ".DOL_URL_ROOT."/install/index.php");
			}
			exit;
		}
	}
}

// Creation of a token against CSRF vulnerabilities
if (!defined('NOTOKENRENEWAL') && !defined('NOSESSION')) {
	// No token renewal on .css.php, .js.php and .json.php (even if the NOTOKENRENEWAL was not provided)
	if (!preg_match('/\.(css|js|json)\.php$/', $_SERVER["PHP_SELF"])) {
		// Rolling token at each call ($_SESSION['token'] contains token of previous page)
		if (isset($_SESSION['newtoken'])) {
			$_SESSION['token'] = $_SESSION['newtoken'];
		}

		if (!isset($_SESSION['newtoken']) || getDolGlobalInt('MAIN_SECURITY_CSRF_TOKEN_RENEWAL_ON_EACH_CALL')) {
			// Note: Using MAIN_SECURITY_CSRF_TOKEN_RENEWAL_ON_EACH_CALL is not recommended: if a user succeed in entering a data from
			// a public page with a link that make a token regeneration, it can make use of the backoffice no more possible !
			// Save in $_SESSION['newtoken'] what will be next token. Into forms, we will add param token = $_SESSION['newtoken']
			$token = dol_hash(uniqid((string) mt_rand(), false), 'md5'); // Generates a hash of a random number. We don't need a secured hash, just a changing random value.
			$_SESSION['newtoken'] = $token;
			dol_syslog("NEW TOKEN generated by : ".$_SERVER['PHP_SELF'], LOG_DEBUG);
		}
	}
}

//dol_syslog("CSRF info: ".defined('NOCSRFCHECK')." - ".$dolibarr_nocsrfcheck." - ".$conf->global->MAIN_SECURITY_CSRF_WITH_TOKEN." - ".$_SERVER['REQUEST_METHOD']." - ".GETPOST('token', 'alpha'));

// Check validity of token, only if option MAIN_SECURITY_CSRF_WITH_TOKEN enabled or if constant CSRFCHECK_WITH_TOKEN is set into page
if ((!defined('NOCSRFCHECK') && empty($dolibarr_nocsrfcheck) && getDolGlobalInt('MAIN_SECURITY_CSRF_WITH_TOKEN')) || defined('CSRFCHECK_WITH_TOKEN')) {
	// Array of action code where CSRFCHECK with token will be forced (so token must be provided on url request)
	$sensitiveget = false;
	if ((GETPOSTISSET('massaction') || GETPOST('action', 'aZ09')) && getDolGlobalInt('MAIN_SECURITY_CSRF_WITH_TOKEN') >= 3) {
		// All GET actions (except the listed exceptions that are usually post for pre-actions and not real action) and mass actions are processed as sensitive.
		if (GETPOSTISSET('massaction') || !in_array(GETPOST('action', 'aZ09'), array('create', 'createsite', 'createcard', 'edit', 'editvalidator', 'file_manager', 'presend', 'presend_addmessage', 'preview', 'specimen'))) {	// We exclude some action that are not sensitive so legitimate
			$sensitiveget = true;
		}
	} elseif (getDolGlobalInt('MAIN_SECURITY_CSRF_WITH_TOKEN') >= 2) {
		// Few GET actions coded with a &token into url are also processed as sensitive.
		$arrayofactiontoforcetokencheck = array(
			'activate',
			'doprev', 'donext', 'dvprev', 'dvnext',
			'freezone', 'install',
			'reopen'
		);
		if (in_array(GETPOST('action', 'aZ09'), $arrayofactiontoforcetokencheck)) {
			$sensitiveget = true;
		}
		// We also need a valid token for actions matching one of these values
		if (preg_match('/^(confirm_)?(add|classify|close|confirm|copy|del|disable|enable|remove|set|unset|update|save)/', GETPOST('action', 'aZ09'))) {
			$sensitiveget = true;
		}
	}

	// Check a token is provided for all cases that need a mandatory token
	// (all POST actions + all sensitive GET actions + all mass actions + all login/actions/logout on pages with CSRFCHECK_WITH_TOKEN set)
	if (
		(!empty($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST') ||
		$sensitiveget ||
		GETPOSTISSET('massaction') ||
		((GETPOSTISSET('actionlogin') || GETPOSTISSET('action')) && defined('CSRFCHECK_WITH_TOKEN'))
	) {
		// If token is not provided or empty, error (we are in case it is mandatory)
		if (!GETPOST('token', 'alpha') || GETPOST('token', 'alpha') == 'notrequired') {
			top_httphead();
			if (GETPOSTINT('uploadform')) {
				dol_syslog("--- Access to ".(empty($_SERVER["REQUEST_METHOD"]) ? '' : $_SERVER["REQUEST_METHOD"].' ').$_SERVER["PHP_SELF"]." refused. File size too large or not provided.");
				$langs->loadLangs(array("errors", "install"));
				print $langs->trans("ErrorFileSizeTooLarge").' ';
				print $langs->trans("ErrorGoBackAndCorrectParameters");
			} else {
				http_response_code(403);
				if (defined('CSRFCHECK_WITH_TOKEN')) {
					dol_syslog("--- Access to ".(empty($_SERVER["REQUEST_METHOD"]) ? '' : $_SERVER["REQUEST_METHOD"].' ').$_SERVER["PHP_SELF"]." refused by CSRF protection (CSRFCHECK_WITH_TOKEN protection) in main.inc.php. Token not provided.", LOG_WARNING);
					print "Access to a page that needs a token (constant CSRFCHECK_WITH_TOKEN is defined) is refused by CSRF protection in main.inc.php. Token not provided.\n";
				} else {
					dol_syslog("--- Access to ".(empty($_SERVER["REQUEST_METHOD"]) ? '' : $_SERVER["REQUEST_METHOD"].' ').$_SERVER["PHP_SELF"]." refused by CSRF protection (POST method or GET with a sensible value for 'action' parameter) in main.inc.php. Token not provided.", LOG_WARNING);
					print "Access to this page this way (POST method or GET with a sensible value for 'action' parameter) is refused by CSRF protection in main.inc.php. Token not provided.\n";
					print "If you access your server behind a proxy using url rewriting and the parameter is provided by caller, you might check that all HTTP header are propagated (or add the line \$dolibarr_nocsrfcheck=1 into your conf.php file or MAIN_SECURITY_CSRF_WITH_TOKEN to 0";
					if (getDolGlobalString('MAIN_SECURITY_CSRF_WITH_TOKEN')) {
						print " instead of " . getDolGlobalString('MAIN_SECURITY_CSRF_WITH_TOKEN');
					}
					print " into setup).\n";
				}
			}
			die;
		}
	}

	$sessiontokenforthisurl = (empty($_SESSION['token']) ? '' : $_SESSION['token']);
	// TODO Get the sessiontokenforthisurl into an array of session token (one array per base URL so we can use the CSRF per page and we keep ability for several tabs per url in a browser)
	if (GETPOSTISSET('token') && GETPOST('token') != 'notrequired' && GETPOST('token', 'alpha') != $sessiontokenforthisurl) {
		dol_syslog("--- Access to ".(empty($_SERVER["REQUEST_METHOD"]) ? '' : $_SERVER["REQUEST_METHOD"].' ').$_SERVER["PHP_SELF"]." refused by CSRF protection (invalid token), so we disable POST and some GET parameters - referrer=".(empty($_SERVER['HTTP_REFERER']) ? '' : $_SERVER['HTTP_REFERER']).", action=".GETPOST('action', 'aZ09').", _GET|POST['token']=".GETPOST('token', 'alpha'), LOG_WARNING);
		//dol_syslog("_SESSION['token']=".$sessiontokenforthisurl, LOG_DEBUG);
		// Do not output anything on standard output because this create problems when using the BACK button on browsers. So we just set a message into session.
		if (!defined('NOTOKENRENEWAL')) {
			// If the page is not a page that disable the token renewal, we report a warning message to explain token has epired.
			setEventMessages('SecurityTokenHasExpiredSoActionHasBeenCanceledPleaseRetry', null, 'warnings', '', 1);
		}
		$savid = null;
		if (isset($_POST['id'])) {
			$savid = ((int) $_POST['id']);
		}
		unset($_POST);
		unset($_GET['confirm']);
		unset($_GET['action']);
		unset($_GET['confirmmassaction']);
		unset($_GET['massaction']);
		unset($_GET['token']);			// TODO Make a redirect if we have a token in url to remove it ?
		if (isset($savid)) {
			$_POST['id'] = ((int) $savid);
		}
		// So rest of code can know something was wrong here
		$_GET['errorcode'] = 'InvalidToken';
	}

	// Note: There is another CSRF protection into the filefunc.inc.php
}

// Disable modules (this must be after session_start and after conf has been loaded)
if (GETPOSTISSET('disablemodules')) {
	$_SESSION["disablemodules"] = GETPOST('disablemodules', 'alpha');
}
if (!empty($_SESSION["disablemodules"])) {
	$modulepartkeys = array('css', 'js', 'tabs', 'triggers', 'login', 'substitutions', 'menus', 'theme', 'sms', 'tpl', 'barcode', 'models', 'societe', 'hooks', 'dir', 'syslog', 'tpllinkable', 'contactelement', 'moduleforexternal', 'websitetemplates');

	$disabled_modules = explode(',', $_SESSION["disablemodules"]);
	foreach ($disabled_modules as $module) {
		if ($module) {
			if (empty($conf->$module)) {
				$conf->$module = new stdClass(); // To avoid warnings
			}
			$conf->$module->enabled = false;
			foreach ($modulepartkeys as $modulepartkey) {
				unset($conf->modules_parts[$modulepartkey][$module]);
			}
			if ($module == 'fournisseur') {		// Special case
				$conf->supplier_order->enabled = 0;
				$conf->supplier_invoice->enabled = 0;
			}
		}
	}
}

// Set current modulepart
$modulepart = explode("/", $_SERVER["PHP_SELF"]);
if (is_array($modulepart) && count($modulepart) > 0) {
	foreach ($conf->modules as $module) {
		if (in_array($module, $modulepart)) {
			$modulepart = $module;
			break;
		}
	}
}
if (is_array($modulepart)) {
	$modulepart = '';
}


/*
 * Phase authentication / login
 */

$login = '';
$error = 0;
if (!defined('NOLOGIN')) {
	// $authmode lists the different method of identification to be tested in order of preference.
	// Example: 'http', 'dolibarr', 'ldap', 'http,forceuser', '...'

	if (defined('MAIN_AUTHENTICATION_MODE')) {
		$dolibarr_main_authentication = constant('MAIN_AUTHENTICATION_MODE');
	} else {
		// Authentication mode
		if (empty($dolibarr_main_authentication)) {
			$dolibarr_main_authentication = 'dolibarr';
		}
		// Authentication mode: forceuser
		if ($dolibarr_main_authentication == 'forceuser' && empty($dolibarr_auto_user)) {
			$dolibarr_auto_user = 'auto';
		}
	}
	// Set authmode
	$authmode = explode(',', $dolibarr_main_authentication);

	// No authentication mode
	if (!count($authmode)) {
		$langs->load('main');
		dol_print_error(null, $langs->trans("ErrorConfigParameterNotDefined", 'dolibarr_main_authentication'));
		exit;
	}

	// If login request was already post, we retrieve login from the session
	// Call module if not realized that his request.
	// At the end of this phase, the variable $login is defined.
	$resultFetchUser = '';
	$test = true;
	if (!isset($_SESSION["dol_login"])) {
		// It is not already authenticated and it requests the login / password
		include_once DOL_DOCUMENT_ROOT.'/core/lib/security2.lib.php';

		$dol_dst_observed = GETPOSTINT("dst_observed", 3);
		$dol_dst_first = GETPOSTINT("dst_first", 3);
		$dol_dst_second = GETPOSTINT("dst_second", 3);
		$dol_screenwidth = GETPOSTINT("screenwidth", 3);
		$dol_screenheight = GETPOSTINT("screenheight", 3);
		$dol_hide_topmenu = GETPOSTINT('dol_hide_topmenu', 3);
		$dol_hide_leftmenu = GETPOSTINT('dol_hide_leftmenu', 3);
		$dol_optimize_smallscreen = GETPOSTINT('dol_optimize_smallscreen', 3);
		$dol_no_mouse_hover = GETPOSTINT('dol_no_mouse_hover', 3);
		$dol_use_jmobile = GETPOSTINT('dol_use_jmobile', 3); // 0=default, 1=to say we use app from a webview app, 2=to say we use app from a webview app and keep ajax

		// If in demo mode, we check we go to home page through the public/demo/index.php page
		if (!empty($dolibarr_main_demo) && $_SERVER['PHP_SELF'] == DOL_URL_ROOT.'/index.php') {  // We ask index page
			if (empty($_SERVER['HTTP_REFERER']) || !preg_match('/public/', $_SERVER['HTTP_REFERER'])) {
				dol_syslog("Call index page from another url than demo page (call is done from page ".(empty($_SERVER['HTTP_REFERER']) ? '' : $_SERVER['HTTP_REFER']).")");
				$url = '';
				$url .= ($url ? '&' : '').($dol_hide_topmenu ? 'dol_hide_topmenu='.$dol_hide_topmenu : '');
				$url .= ($url ? '&' : '').($dol_hide_leftmenu ? 'dol_hide_leftmenu='.$dol_hide_leftmenu : '');
				$url .= ($url ? '&' : '').($dol_optimize_smallscreen ? 'dol_optimize_smallscreen='.$dol_optimize_smallscreen : '');
				$url .= ($url ? '&' : '').($dol_no_mouse_hover ? 'dol_no_mouse_hover='.$dol_no_mouse_hover : '');
				$url .= ($url ? '&' : '').($dol_use_jmobile ? 'dol_use_jmobile='.$dol_use_jmobile : '');
				$url = DOL_URL_ROOT.'/public/demo/index.php'.($url ? '?'.$url : '');
				header("Location: ".$url);
				exit;
			}
		}

		// Hooks for security access
		$action = '';
		$hookmanager->initHooks(array('login'));
		$parameters = array();
		$reshook = $hookmanager->executeHooks('beforeLoginAuthentication', $parameters, $user, $action); // Note that $action and $object may have been modified by some hooks
		if ($reshook < 0) {
			$test = false;
			$error++;
		}

		// Verification security graphic code
		if ($test && GETPOST("username", "alpha", 2) && getDolGlobalString('MAIN_SECURITY_ENABLECAPTCHA') && !isset($_SESSION['dol_bypass_antispam'])) {
			$sessionkey = 'dol_antispam_value';
			$ok = (array_key_exists($sessionkey, $_SESSION) === true && (strtolower($_SESSION[$sessionkey]) === strtolower(GETPOST('code', 'restricthtml'))));

			// Check code
			if (!$ok) {
				dol_syslog('Bad value for code, connection refused', LOG_NOTICE);
				// Load translation files required by page
				$langs->loadLangs(array('main', 'errors'));

				$_SESSION["dol_loginmesg"] = $langs->transnoentitiesnoconv("ErrorBadValueForCode");
				$test = false;

				// Call trigger for the "security events" log
				$user->context['audit'] = 'ErrorBadValueForCode - login='.GETPOST("username", "alpha", 2);

				// Call trigger
				$result = $user->call_trigger('USER_LOGIN_FAILED', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers

				// Hooks on failed login
				$action = '';
				$hookmanager->initHooks(array('login'));
				$parameters = array('dol_authmode' => $authmode, 'dol_loginmesg' => $_SESSION["dol_loginmesg"]);
				$reshook = $hookmanager->executeHooks('afterLoginFailed', $parameters, $user, $action); // Note that $action and $object may have been modified by some hooks
				if ($reshook < 0) {
					$error++;
				}

				// Note: exit is done later
			}
		}

		$allowedmethodtopostusername = 3;
		if (defined('MAIN_AUTHENTICATION_POST_METHOD')) {
			$allowedmethodtopostusername = constant('MAIN_AUTHENTICATION_POST_METHOD');	// Note a value of 2 is not compatible with some authentication methods that put username as GET parameter
		}
		// TODO Remove use of $_COOKIE['login_dolibarr'] ? Replace $usertotest = with $usertotest = GETPOST("username", "alpha", $allowedmethodtopostusername);
		$usertotest = (!empty($_COOKIE['login_dolibarr']) ? preg_replace('/[^a-zA-Z0-9_@\-\.]/', '', $_COOKIE['login_dolibarr']) : GETPOST("username", "alpha", $allowedmethodtopostusername));
		$passwordtotest = GETPOST('password', 'none', $allowedmethodtopostusername);
		$entitytotest = (GETPOSTINT('entity') ? GETPOSTINT('entity') : (!empty($conf->entity) ? $conf->entity : 1));

		// Define if we received the correct data to go into the test of the login with the checkLoginPassEntity().
		$goontestloop = false;
		if (isset($_SERVER["REMOTE_USER"]) && in_array('http', $authmode)) {	// For http basic login test
			$goontestloop = true;
		}
		if ($dolibarr_main_authentication == 'forceuser' && !empty($dolibarr_auto_user)) {	// For automatic login with a forced user
			$goontestloop = true;
		}
		if (GETPOST("username", "alpha", $allowedmethodtopostusername)) {	// For posting the login form
			$goontestloop = true;
		}
		if (GETPOST('openid_mode', 'alpha', 1)) {	// For openid_connect ?
			$goontestloop = true;
		}
		if (GETPOST('beforeoauthloginredirect') || GETPOST('afteroauthloginreturn')) {	// For oauth login
			$goontestloop = true;
		}
		if (!empty($_COOKIE['login_dolibarr'])) {	// TODO For ? Remove this ?
			$goontestloop = true;
		}

		if (!is_object($langs)) { // This can occurs when calling page with NOREQUIRETRAN defined, however we need langs for error messages.
			include_once DOL_DOCUMENT_ROOT.'/core/class/translate.class.php';
			$langs = new Translate("", $conf);
			$langcode = (GETPOST('lang', 'aZ09', 1) ? GETPOST('lang', 'aZ09', 1) : getDolGlobalString('MAIN_LANG_DEFAULT', 'auto'));
			if (defined('MAIN_LANG_DEFAULT')) {
				$langcode = constant('MAIN_LANG_DEFAULT');
			}
			$langs->setDefaultLang($langcode);
		}

		// Validation of login/pass/entity
		// If ok, the variable login will be returned
		// If error, we will put error message in session under the name dol_loginmesg
		if ($test && $goontestloop && (GETPOST('actionlogin', 'aZ09') == 'login' || $dolibarr_main_authentication != 'dolibarr')) {
			// Loop on each test mode defined into $authmode
			// $authmode is an array for example: array('0'=>'dolibarr', '1'=>'googleoauth');
			$oauthmodetotestarray = array('google');
			foreach ($oauthmodetotestarray as $oauthmodetotest) {
				if (in_array($oauthmodetotest.'oauth', $authmode)) {	// This is an authmode that is currently qualified. Do we have to remove it ?
					// If we click on the link to use OAuth authentication or if we goes after callback return, we do nothing
					if (GETPOST('beforeoauthloginredirect') == $oauthmodetotest || GETPOST('afteroauthloginreturn')) {
						// TODO Use: if (GETPOST('beforeoauthloginredirect') == $oauthmodetotest || GETPOST('afteroauthloginreturn') == $oauthmodetotest) {
						continue;
					}
					dol_syslog("User did not click on link for OAuth or is not on the OAuth return, so we disable check using ".$oauthmodetotest);
					foreach ($authmode as $tmpkey => $tmpval) {
						if ($tmpval == $oauthmodetotest.'oauth') {
							unset($authmode[$tmpkey]);
							break;
						}
					}
				}
			}

			// Check login for all qualified modes in array $authmode.
			$login = checkLoginPassEntity($usertotest, $passwordtotest, $entitytotest, $authmode);
			if ($login === '--bad-login-validity--') {
				$login = '';
			}

			$dol_authmode = '';

			if ($login) {
				$dol_authmode = $conf->authmode; // This properties is defined only when logged, to say what mode was successfully used
				$dol_tz = empty($_POST["tz"]) ? (empty($_SESSION["tz"]) ? '' : $_SESSION["tz"]) : $_POST["tz"];
				$dol_tz_string = empty($_POST["tz_string"]) ? (empty($_SESSION["tz_string"]) ? '' : $_SESSION["tz_string"]) : $_POST["tz_string"];
				$dol_tz_string = preg_replace('/\s*\(.+\)$/', '', $dol_tz_string);
				$dol_tz_string = preg_replace('/,/', '/', $dol_tz_string);
				$dol_tz_string = preg_replace('/\s/', '_', $dol_tz_string);
				$dol_dst = 0;
				// Keep $_POST here. Do not use GETPOSTISSET
				$dol_dst_first = empty($_POST["dst_first"]) ? (empty($_SESSION["dst_first"]) ? '' : $_SESSION["dst_first"]) : $_POST["dst_first"];
				$dol_dst_second = empty($_POST["dst_second"]) ? (empty($_SESSION["dst_second"]) ? '' : $_SESSION["dst_second"]) : $_POST["dst_second"];
				if ($dol_dst_first && $dol_dst_second) {
					include_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
					$datenow = dol_now();
					$datefirst = dol_stringtotime($dol_dst_first);
					$datesecond = dol_stringtotime($dol_dst_second);
					if ($datenow >= $datefirst && $datenow < $datesecond) {
						$dol_dst = 1;
					}
				}
				$dol_screenheight = empty($_POST["screenheight"]) ? (empty($_SESSION["dol_screenheight"]) ? '' : $_SESSION["dol_screenheight"]) : $_POST["screenheight"];
				$dol_screenwidth = empty($_POST["screenwidth"]) ? (empty($_SESSION["dol_screenwidth"]) ? '' : $_SESSION["dol_screenwidth"]) : $_POST["screenwidth"];
				//print $datefirst.'-'.$datesecond.'-'.$datenow.'-'.$dol_tz.'-'.$dol_tzstring.'-'.$dol_dst.'-'.sdol_screenheight.'-'.sdol_screenwidth; exit;
			}

			if (!$login) {
				dol_syslog('Bad password, connection refused (see a previous notice message for more info)', LOG_NOTICE);
				// Load translation files required by page
				$langs->loadLangs(array('main', 'errors'));

				// Bad password. No authmode has found a good password.
				// We set a generic message if not defined inside function checkLoginPassEntity or subfunctions
				if (empty($_SESSION["dol_loginmesg"])) {
					$_SESSION["dol_loginmesg"] = $langs->transnoentitiesnoconv("ErrorBadLoginPassword");
				}

				// Call trigger for the "security events" log
				$user->context['audit'] = $langs->trans("ErrorBadLoginPassword").' - login='.GETPOST("username", "alpha", 2);

				// Call trigger
				$result = $user->call_trigger('USER_LOGIN_FAILED', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers

				// Hooks on failed login
				$action = '';
				$hookmanager->initHooks(array('login'));
				$parameters = array('dol_authmode' => $dol_authmode, 'dol_loginmesg' => $_SESSION["dol_loginmesg"]);
				$reshook = $hookmanager->executeHooks('afterLoginFailed', $parameters, $user, $action); // Note that $action and $object may have been modified by some hooks
				if ($reshook < 0) {
					$error++;
				}

				// Note: exit is done in next chapter
			}
		}

		// End test login / passwords
		if (!$login || (in_array('ldap', $authmode) && empty($passwordtotest))) {	// With LDAP we refused empty password because some LDAP are "opened" for anonymous access so connection is a success.
			// No data to test login, so we show the login page.
			dol_syslog("--- Access to ".(empty($_SERVER["REQUEST_METHOD"]) ? '' : $_SERVER["REQUEST_METHOD"].' ').$_SERVER["PHP_SELF"]." - action=".GETPOST('action', 'aZ09')." - actionlogin=".GETPOST('actionlogin', 'aZ09')." - showing the login form and exit", LOG_NOTICE);
			if (defined('NOREDIRECTBYMAINTOLOGIN')) {
				// When used with NOREDIRECTBYMAINTOLOGIN set, the http header must already be set when including the main.
				// See example with selectsearchbox.php. This case is reserved for the selectesearchbox.php so we can
				// report a message to ask to login when search ajax component is used after a timeout.
				//top_httphead();
				return 'ERROR_NOT_LOGGED';
			} else {
				if (!empty($_SERVER["HTTP_USER_AGENT"]) && $_SERVER["HTTP_USER_AGENT"] == 'securitytest') {
					http_response_code(401); // It makes easier to understand if session was broken during security tests
				}
				dol_loginfunction($langs, $conf, (!empty($mysoc) ? $mysoc : ''));	// This include http headers
			}
			exit;
		}

		$resultFetchUser = $user->fetch('', $login, '', 1, ($entitytotest > 0 ? $entitytotest : -1)); // value for $login was retrieved previously when checking password.
		if ($resultFetchUser <= 0 || $user->isNotIntoValidityDateRange()) {
			dol_syslog('User not found or not valid, connection refused');
			session_destroy();
			session_set_cookie_params(0, '/', null, (empty($dolibarr_main_force_https) ? false : true), true); // Add tag secure and httponly on session cookie
			session_name($sessionname);
			dol_session_start();

			if ($resultFetchUser == 0) {
				// Load translation files required by page
				$langs->loadLangs(array('main', 'errors'));

				$_SESSION["dol_loginmesg"] = $langs->transnoentitiesnoconv("ErrorCantLoadUserFromDolibarrDatabase", $login);

				$user->context['audit'] = 'ErrorCantLoadUserFromDolibarrDatabase - login='.$login;
			} elseif ($resultFetchUser < 0) {
				$_SESSION["dol_loginmesg"] = $user->error;

				$user->context['audit'] = $user->error;
			} else {
				// Load translation files required by the page
				$langs->loadLangs(array('main', 'errors'));

				$_SESSION["dol_loginmesg"] = $langs->transnoentitiesnoconv("ErrorLoginDateValidity");

				$user->context['audit'] = $langs->trans("ErrorLoginDateValidity").' - login='.$login;
			}

			// Call trigger
			$result = $user->call_trigger('USER_LOGIN_FAILED', $user);
			if ($result < 0) {
				$error++;
			}
			// End call triggers


			// Hooks on failed login
			$action = '';
			$hookmanager->initHooks(array('login'));
			$parameters = array('dol_authmode' => $dol_authmode, 'dol_loginmesg' => $_SESSION["dol_loginmesg"]);
			$reshook = $hookmanager->executeHooks('afterLoginFailed', $parameters, $user, $action); // Note that $action and $object may have been modified by some hooks
			if ($reshook < 0) {
				$error++;
			}

			$paramsurl = array();
			if (GETPOSTINT('textbrowser')) {
				$paramsurl[] = 'textbrowser='.GETPOSTINT('textbrowser');
			}
			if (GETPOSTINT('nojs')) {
				$paramsurl[] = 'nojs='.GETPOSTINT('nojs');
			}
			if (GETPOST('lang', 'aZ09')) {
				$paramsurl[] = 'lang='.GETPOST('lang', 'aZ09');
			}
			header('Location: '.DOL_URL_ROOT.'/index.php'.(count($paramsurl) ? '?'.implode('&', $paramsurl) : ''));
			exit;
		} else {
			// User is loaded, we may need to change language for him according to its choice
			if (!empty($user->conf->MAIN_LANG_DEFAULT)) {
				$langs->setDefaultLang($user->conf->MAIN_LANG_DEFAULT);
			}
		}
	} else {
		// We are already into an authenticated session
		$login = $_SESSION["dol_login"];
		$entity = isset($_SESSION["dol_entity"]) ? $_SESSION["dol_entity"] : 0;
		dol_syslog("- This is an already logged session. _SESSION['dol_login']=".$login." _SESSION['dol_entity']=".$entity, LOG_DEBUG);

		$resultFetchUser = $user->fetch('', $login, '', 1, ($entity > 0 ? $entity : -1));

		//var_dump(dol_print_date($user->flagdelsessionsbefore, 'dayhour', 'gmt')." ".dol_print_date($_SESSION["dol_logindate"], 'dayhour', 'gmt'));

		if ($resultFetchUser <= 0
			|| ($user->flagdelsessionsbefore && !empty($_SESSION["dol_logindate"]) && $user->flagdelsessionsbefore > $_SESSION["dol_logindate"])
			|| ($user->status != $user::STATUS_ENABLED)
			|| ($user->isNotIntoValidityDateRange())) {
			if ($resultFetchUser <= 0) {
				// Account has been removed after login
				dol_syslog("Can't load user even if session logged. _SESSION['dol_login']=".$login, LOG_WARNING);
			} elseif ($user->flagdelsessionsbefore && !empty($_SESSION["dol_logindate"]) && $user->flagdelsessionsbefore > $_SESSION["dol_logindate"]) {
				// Session is no more valid
				dol_syslog("The user has a date for session invalidation = ".$user->flagdelsessionsbefore." and a session date = ".$_SESSION["dol_logindate"].". We must invalidate its sessions.");
			} elseif ($user->status != $user::STATUS_ENABLED) {
				// User is not enabled
				dol_syslog("The user login is disabled");
			} else {
				// User validity dates are no more valid
				dol_syslog("The user login has a validity between [".$user->datestartvalidity." and ".$user->dateendvalidity."], current date is ".dol_now());
			}
			session_destroy();
			session_set_cookie_params(0, '/', null, (empty($dolibarr_main_force_https) ? false : true), true); // Add tag secure and httponly on session cookie
			session_name($sessionname);
			dol_session_start();

			if ($resultFetchUser == 0) {
				$langs->loadLangs(array('main', 'errors'));

				$_SESSION["dol_loginmesg"] = $langs->transnoentitiesnoconv("ErrorCantLoadUserFromDolibarrDatabase", $login);

				$user->context['audit'] = 'ErrorCantLoadUserFromDolibarrDatabase - login='.$login;
			} elseif ($resultFetchUser < 0) {
				$_SESSION["dol_loginmesg"] = $user->error;

				$user->context['audit'] = $user->error;
			} else {
				$langs->loadLangs(array('main', 'errors'));

				$_SESSION["dol_loginmesg"] = $langs->transnoentitiesnoconv("ErrorSessionInvalidatedAfterPasswordChange");

				$user->context['audit'] = 'ErrorUserSessionWasInvalidated - login='.$login;
			}

			// Call trigger
			$result = $user->call_trigger('USER_LOGIN_FAILED', $user);
			if ($result < 0) {
				$error++;
			}
			// End call triggers

			// Hooks on failed login
			$action = '';
			$hookmanager->initHooks(array('login'));
			$parameters = array('dol_authmode' => (isset($dol_authmode) ? $dol_authmode : ''), 'dol_loginmesg' => $_SESSION["dol_loginmesg"]);
			$reshook = $hookmanager->executeHooks('afterLoginFailed', $parameters, $user, $action); // Note that $action and $object may have been modified by some hooks
			if ($reshook < 0) {
				$error++;
			}

			$paramsurl = array();
			if (GETPOSTINT('textbrowser')) {
				$paramsurl[] = 'textbrowser='.GETPOSTINT('textbrowser');
			}
			if (GETPOSTINT('nojs')) {
				$paramsurl[] = 'nojs='.GETPOSTINT('nojs');
			}
			if (GETPOST('lang', 'aZ09')) {
				$paramsurl[] = 'lang='.GETPOST('lang', 'aZ09');
			}

			header('Location: '.DOL_URL_ROOT.'/index.php'.(count($paramsurl) ? '?'.implode('&', $paramsurl) : ''));
			exit;
		} else {
			// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
			$hookmanager->initHooks(array('main'));

			// Code for search criteria persistence.
			if (!empty($_GET['save_lastsearch_values']) && !empty($_SERVER["HTTP_REFERER"])) {    // We must use $_GET here
				$relativepathstring = preg_replace('/\?.*$/', '', $_SERVER["HTTP_REFERER"]);
				$relativepathstring = preg_replace('/^https?:\/\/[^\/]*/', '', $relativepathstring); // Get full path except host server
				// Clean $relativepathstring
				if (constant('DOL_URL_ROOT')) {
					$relativepathstring = preg_replace('/^'.preg_quote(constant('DOL_URL_ROOT'), '/').'/', '', $relativepathstring);
				}
				$relativepathstring = preg_replace('/^\//', '', $relativepathstring);
				$relativepathstring = preg_replace('/^custom\//', '', $relativepathstring);
				//var_dump($relativepathstring);

				// We click on a link that leave a page we have to save search criteria, contextpage, limit and page and mode. We save them from tmp to no tmp
				if (!empty($_SESSION['lastsearch_values_tmp_'.$relativepathstring])) {
					$_SESSION['lastsearch_values_'.$relativepathstring] = $_SESSION['lastsearch_values_tmp_'.$relativepathstring];
					unset($_SESSION['lastsearch_values_tmp_'.$relativepathstring]);
				}
				if (!empty($_SESSION['lastsearch_contextpage_tmp_'.$relativepathstring])) {
					$_SESSION['lastsearch_contextpage_'.$relativepathstring] = $_SESSION['lastsearch_contextpage_tmp_'.$relativepathstring];
					unset($_SESSION['lastsearch_contextpage_tmp_'.$relativepathstring]);
				}
				if (!empty($_SESSION['lastsearch_limit_tmp_'.$relativepathstring]) && $_SESSION['lastsearch_limit_tmp_'.$relativepathstring] != $conf->liste_limit) {
					$_SESSION['lastsearch_limit_'.$relativepathstring] = $_SESSION['lastsearch_limit_tmp_'.$relativepathstring];
					unset($_SESSION['lastsearch_limit_tmp_'.$relativepathstring]);
				}
				if (!empty($_SESSION['lastsearch_page_tmp_'.$relativepathstring]) && $_SESSION['lastsearch_page_tmp_'.$relativepathstring] > 0) {
					$_SESSION['lastsearch_page_'.$relativepathstring] = $_SESSION['lastsearch_page_tmp_'.$relativepathstring];
					unset($_SESSION['lastsearch_page_tmp_'.$relativepathstring]);
				}
				if (!empty($_SESSION['lastsearch_mode_tmp_'.$relativepathstring])) {
					$_SESSION['lastsearch_mode_'.$relativepathstring] = $_SESSION['lastsearch_mode_tmp_'.$relativepathstring];
					unset($_SESSION['lastsearch_mode_tmp_'.$relativepathstring]);
				}
			}
			if (!empty($_GET['save_pageforbacktolist']) && !empty($_SERVER["HTTP_REFERER"])) {    // We must use $_GET here
				if (empty($_SESSION['pageforbacktolist'])) {
					$pageforbacktolistarray = array();
				} else {
					$pageforbacktolistarray = $_SESSION['pageforbacktolist'];
				}
				$tmparray = explode(':', $_GET['save_pageforbacktolist'], 2);
				if (!empty($tmparray[0]) && !empty($tmparray[1])) {
					$pageforbacktolistarray[$tmparray[0]] = $tmparray[1];
					$_SESSION['pageforbacktolist'] = $pageforbacktolistarray;
				}
			}

			$action = '';
			$parameters = array();
			$reshook = $hookmanager->executeHooks('updateSession', $parameters, $user, $action);
			if ($reshook < 0) {
				setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
			}
		}
	}

	// Is it a new session that has started ?
	// If we are here, this means authentication was successful.
	if (!isset($_SESSION["dol_login"])) {
		// New session for this login has started.
		$error = 0;

		// Store value into session (values always stored)
		$_SESSION["dol_login"] = $user->login;
		$_SESSION["dol_logindate"] = dol_now('gmt');
		$_SESSION["dol_authmode"] = isset($dol_authmode) ? $dol_authmode : '';
		$_SESSION["dol_tz"] = isset($dol_tz) ? $dol_tz : '';
		$_SESSION["dol_tz_string"] = isset($dol_tz_string) ? $dol_tz_string : '';
		$_SESSION["dol_dst"] = isset($dol_dst) ? $dol_dst : '';
		$_SESSION["dol_dst_observed"] = isset($dol_dst_observed) ? $dol_dst_observed : '';
		$_SESSION["dol_dst_first"] = isset($dol_dst_first) ? $dol_dst_first : '';
		$_SESSION["dol_dst_second"] = isset($dol_dst_second) ? $dol_dst_second : '';
		$_SESSION["dol_screenwidth"] = isset($dol_screenwidth) ? $dol_screenwidth : '';
		$_SESSION["dol_screenheight"] = isset($dol_screenheight) ? $dol_screenheight : '';
		$_SESSION["dol_company"] = getDolGlobalString("MAIN_INFO_SOCIETE_NOM");
		$_SESSION["dol_entity"] = $conf->entity;
		// Store value into session (values stored only if defined)
		if (!empty($dol_hide_topmenu)) {
			$_SESSION['dol_hide_topmenu'] = $dol_hide_topmenu;
		}
		if (!empty($dol_hide_leftmenu)) {
			$_SESSION['dol_hide_leftmenu'] = $dol_hide_leftmenu;
		}
		if (!empty($dol_optimize_smallscreen)) {
			$_SESSION['dol_optimize_smallscreen'] = $dol_optimize_smallscreen;
		}
		if (!empty($dol_no_mouse_hover)) {
			$_SESSION['dol_no_mouse_hover'] = $dol_no_mouse_hover;
		}
		if (!empty($dol_use_jmobile)) {
			$_SESSION['dol_use_jmobile'] = $dol_use_jmobile;
		}

		dol_syslog("This is a new started user session. _SESSION['dol_login']=".$_SESSION["dol_login"]." Session id=".session_id());

		$db->begin();

		$user->update_last_login_date();

		$loginfo = 'TZ='.$_SESSION["dol_tz"].';TZString='.$_SESSION["dol_tz_string"].';Screen='.$_SESSION["dol_screenwidth"].'x'.$_SESSION["dol_screenheight"];
		$loginfo .= ' - authmode='.$dol_authmode.' - entity='.$conf->entity;

		// Call triggers for the "security events" log
		$user->context['audit'] = $loginfo;
		$user->context['authentication_method'] = $dol_authmode;

		// Call trigger
		$result = $user->call_trigger('USER_LOGIN', $user);
		if ($result < 0) {
			$error++;
		}
		// End call triggers

		// Hooks on successful login
		$action = '';
		$hookmanager->initHooks(array('login'));
		$parameters = array('dol_authmode' => $dol_authmode, 'dol_loginfo' => $loginfo);
		$reshook = $hookmanager->executeHooks('afterLogin', $parameters, $user, $action); // Note that $action and $object may have been modified by some hooks
		if ($reshook < 0) {
			$error++;
		}

		if ($error) {
			$db->rollback();
			session_destroy();
			dol_print_error($db, 'Error in some triggers USER_LOGIN or in some hooks afterLogin');
			exit;
		} else {
			$db->commit();
		}

		// Change landing page if defined.
		$landingpage = (empty($user->conf->MAIN_LANDING_PAGE) ? (!getDolGlobalString('MAIN_LANDING_PAGE') ? '' : $conf->global->MAIN_LANDING_PAGE) : $user->conf->MAIN_LANDING_PAGE);
		if (!empty($landingpage)) {    // Example: /index.php
			$newpath = dol_buildpath($landingpage, 1);
			if ($_SERVER["PHP_SELF"] != $newpath) {   // not already on landing page (avoid infinite loop)
				header('Location: '.$newpath);
				exit;
			}
		}
	}


	// If user admin, we force the rights-based modules
	if ($user->admin) {
		$user->rights->user->user->lire = 1;
		$user->rights->user->user->creer = 1;
		$user->rights->user->user->password = 1;
		$user->rights->user->user->supprimer = 1;
		$user->rights->user->self->creer = 1;
		$user->rights->user->self->password = 1;

		//Required if advanced permissions are used with MAIN_USE_ADVANCED_PERMS
		if (getDolGlobalString('MAIN_USE_ADVANCED_PERMS')) {
			if (!$user->hasRight('user', 'user_advance')) {
				$user->rights->user->user_advance = new stdClass(); // To avoid warnings
			}
			if (!$user->hasRight('user', 'self_advance')) {
				$user->rights->user->self_advance = new stdClass(); // To avoid warnings
			}
			if (!$user->hasRight('user', 'group_advance')) {
				$user->rights->user->group_advance = new stdClass(); // To avoid warnings
			}

			$user->rights->user->user_advance->readperms = 1;
			$user->rights->user->user_advance->write = 1;
			$user->rights->user->self_advance->readperms = 1;
			$user->rights->user->self_advance->writeperms = 1;
			$user->rights->user->group_advance->read = 1;
			$user->rights->user->group_advance->readperms = 1;
			$user->rights->user->group_advance->write = 1;
			$user->rights->user->group_advance->delete = 1;
		}
	}

	/*
	 * Overwrite some configs globals (try to avoid this and have code to use instead $user->conf->xxx)
	 */

	// Set liste_limit
	if (isset($user->conf->MAIN_SIZE_LISTE_LIMIT)) {
		$conf->liste_limit = $user->conf->MAIN_SIZE_LISTE_LIMIT; // Can be 0
	}
	if (isset($user->conf->PRODUIT_LIMIT_SIZE)) {
		$conf->product->limit_size = $user->conf->PRODUIT_LIMIT_SIZE; // Can be 0
	}

	// Replace conf->css by personalized value if theme not forced
	if (!getDolGlobalString('MAIN_FORCETHEME') && !empty($user->conf->MAIN_THEME)) {
		$conf->theme = $user->conf->MAIN_THEME;
		$conf->css = "/theme/".$conf->theme."/style.css.php";
	}
} else {
	// We may have NOLOGIN set, but NOREQUIREUSER not
	if (!empty($user) && method_exists($user, 'loadDefaultValues') && !defined('NODEFAULTVALUES')) {
		$user->loadDefaultValues();		// Load default values for everybody (works even if $user->id = 0
	}
}


// Case forcing style from url
if (GETPOST('theme', 'aZ09')) {
	$conf->theme = GETPOST('theme', 'aZ09', 1);
	$conf->css = "/theme/".$conf->theme."/style.css.php";
}

// Set javascript option
if (GETPOSTINT('nojs')) {  // If javascript was not disabled on URL
	$conf->use_javascript_ajax = 0;
} else {
	if (!empty($user->conf->MAIN_DISABLE_JAVASCRIPT)) {
		$conf->use_javascript_ajax = !$user->conf->MAIN_DISABLE_JAVASCRIPT;
	}
}

// Set MAIN_OPTIMIZEFORTEXTBROWSER for user (must be after login part)
if (!getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER') && !empty($user->conf->MAIN_OPTIMIZEFORTEXTBROWSER)) {
	$conf->global->MAIN_OPTIMIZEFORTEXTBROWSER = $user->conf->MAIN_OPTIMIZEFORTEXTBROWSER;
	if (getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER') == 1) {
		$conf->global->THEME_TOPMENU_DISABLE_IMAGE = 1;
	}
}
//var_dump($conf->global->THEME_TOPMENU_DISABLE_IMAGE);
//var_dump($user->conf->THEME_TOPMENU_DISABLE_IMAGE);

// set MAIN_OPTIMIZEFORCOLORBLIND for user
$conf->global->MAIN_OPTIMIZEFORCOLORBLIND = empty($user->conf->MAIN_OPTIMIZEFORCOLORBLIND) ? '' : $user->conf->MAIN_OPTIMIZEFORCOLORBLIND;

// Set terminal output option according to conf->browser.
if (GETPOSTINT('dol_hide_leftmenu') || !empty($_SESSION['dol_hide_leftmenu'])) {
	$conf->dol_hide_leftmenu = 1;
}
if (GETPOSTINT('dol_hide_topmenu') || !empty($_SESSION['dol_hide_topmenu'])) {
	$conf->dol_hide_topmenu = 1;
}
if (GETPOSTINT('dol_optimize_smallscreen') || !empty($_SESSION['dol_optimize_smallscreen'])) {
	$conf->dol_optimize_smallscreen = 1;
}
if (GETPOSTINT('dol_no_mouse_hover') || !empty($_SESSION['dol_no_mouse_hover'])) {
	$conf->dol_no_mouse_hover = 1;
}
if (GETPOSTINT('dol_use_jmobile') || !empty($_SESSION['dol_use_jmobile'])) {
	$conf->dol_use_jmobile = 1;
}
// If not on Desktop
if (!empty($conf->browser->layout) && $conf->browser->layout != 'classic') {
	$conf->dol_no_mouse_hover = 1;
}

// If on smartphone or optimized for small screen
if ((!empty($conf->browser->layout) && $conf->browser->layout == 'phone')
			|| (!empty($_SESSION['dol_screenwidth']) && $_SESSION['dol_screenwidth'] < 400)
			|| (!empty($_SESSION['dol_screenheight']) && $_SESSION['dol_screenheight'] < 400
				|| getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER'))
) {
	$conf->dol_optimize_smallscreen = 1;

	if (getDolGlobalInt('PRODUIT_DESC_IN_FORM') == 1) {
		$conf->global->PRODUIT_DESC_IN_FORM_ACCORDING_TO_DEVICE = 0;
	}
}
// Replace themes bugged with jmobile with eldy
if (!empty($conf->dol_use_jmobile) && in_array($conf->theme, array('bureau2crea', 'cameleo', 'amarok'))) {
	$conf->theme = 'eldy';
	$conf->css = "/theme/".$conf->theme."/style.css.php";
}

if (!defined('NOREQUIRETRAN')) {
	if (!GETPOST('lang', 'aZ09')) {	// If language was not forced on URL
		// If user has chosen its own language
		if (!empty($user->conf->MAIN_LANG_DEFAULT)) {
			// If different than current language
			//print ">>>".$langs->getDefaultLang()."-".$user->conf->MAIN_LANG_DEFAULT;
			if ($langs->getDefaultLang() != $user->conf->MAIN_LANG_DEFAULT) {
				$langs->setDefaultLang($user->conf->MAIN_LANG_DEFAULT);
			}
		}
	}
}

if (!defined('NOLOGIN')) {
	// If the login is not recovered, it is identified with an account that does not exist.
	// Hacking attempt?
	if (!$user->login) {
		accessforbidden();
	}

	// Check if user is active
	if ($user->statut < 1) {
		// If not active, we refuse the user
		$langs->loadLangs(array("errors", "other"));
		dol_syslog("Authentication KO as login is disabled", LOG_NOTICE);
		accessforbidden("ErrorLoginDisabled");
	}

	// Load permissions
	$user->getrights();
}

dol_syslog("--- Access to ".(empty($_SERVER["REQUEST_METHOD"]) ? '' : $_SERVER["REQUEST_METHOD"].' ').$_SERVER["PHP_SELF"].' - action='.GETPOST('action', 'aZ09').', massaction='.GETPOST('massaction', 'aZ09').(defined('NOTOKENRENEWAL') ? ' NOTOKENRENEWAL='.constant('NOTOKENRENEWAL') : ''), LOG_NOTICE);
//Another call for easy debug
//dol_syslog("Access to ".$_SERVER["PHP_SELF"].' '.$_SERVER["HTTP_REFERER"].' GET='.join(',',array_keys($_GET)).'->'.join(',',$_GET).' POST:'.join(',',array_keys($_POST)).'->'.join(',',$_POST));

// Load main languages files
if (!defined('NOREQUIRETRAN')) {
	// Load translation files required by page
	$langs->loadLangs(array('main', 'dict'));
}

// Define some constants used for style of arrays
$bc = array(0 => 'class="impair"', 1 => 'class="pair"');
$bcdd = array(0 => 'class="drag drop oddeven"', 1 => 'class="drag drop oddeven"');
$bcnd = array(0 => 'class="nodrag nodrop nohover"', 1 => 'class="nodrag nodrop nohoverpair"'); // Used for tr to add new lines
$bctag = array(0 => 'class="impair tagtr"', 1 => 'class="pair tagtr"');

// Define messages variables
$mesg = '';
$warning = '';
$error = 0;
// deprecated, see setEventMessages() and dol_htmloutput_events()
$mesgs = array();
$warnings = array();
$errors = array();

// Constants used to defined number of lines in textarea
if (empty($conf->browser->firefox)) {
	define('ROWS_1', 1);
	define('ROWS_2', 2);
	define('ROWS_3', 3);
	define('ROWS_4', 4);
	define('ROWS_5', 5);
	define('ROWS_6', 6);
	define('ROWS_7', 7);
	define('ROWS_8', 8);
	define('ROWS_9', 9);
} else {
	define('ROWS_1', 0);
	define('ROWS_2', 1);
	define('ROWS_3', 2);
	define('ROWS_4', 3);
	define('ROWS_5', 4);
	define('ROWS_6', 5);
	define('ROWS_7', 6);
	define('ROWS_8', 7);
	define('ROWS_9', 8);
}

$heightforframes = 50;

// Init menu manager
if (!defined('NOREQUIREMENU')) {
	if (empty($user->socid)) {    // If internal user or not defined
		$conf->standard_menu = (!getDolGlobalString('MAIN_MENU_STANDARD_FORCED') ? (!getDolGlobalString('MAIN_MENU_STANDARD') ? 'eldy_menu.php' : $conf->global->MAIN_MENU_STANDARD) : $conf->global->MAIN_MENU_STANDARD_FORCED);
	} else {
		// If external user
		$conf->standard_menu = (!getDolGlobalString('MAIN_MENUFRONT_STANDARD_FORCED') ? (!getDolGlobalString('MAIN_MENUFRONT_STANDARD') ? 'eldy_menu.php' : $conf->global->MAIN_MENUFRONT_STANDARD) : $conf->global->MAIN_MENUFRONT_STANDARD_FORCED);
	}

	// Load the menu manager (only if not already done)
	$file_menu = $conf->standard_menu;
	if (GETPOST('menu', 'alpha')) {
		$file_menu = GETPOST('menu', 'alpha'); // example: menu=eldy_menu.php
	}
	if (!class_exists('MenuManager')) {
		$menufound = 0;
		$dirmenus = array_merge(array("/core/menus/"), (array) $conf->modules_parts['menus']);
		foreach ($dirmenus as $dirmenu) {
			$menufound = dol_include_once($dirmenu."standard/".$file_menu);
			if (class_exists('MenuManager')) {
				break;
			}
		}
		if (!class_exists('MenuManager')) {	// If failed to include, we try with standard eldy_menu.php
			dol_syslog("You define a menu manager '".$file_menu."' that can not be loaded.", LOG_WARNING);
			$file_menu = 'eldy_menu.php';
			include_once DOL_DOCUMENT_ROOT."/core/menus/standard/".$file_menu;
		}
	}
	$menumanager = new MenuManager($db, empty($user->socid) ? 0 : 1);
	$menumanager->loadMenu();
}

if (!empty(GETPOST('seteventmessages', 'alpha'))) {
	$message = GETPOST('seteventmessages', 'alpha');
	$messages  = explode(',', $message);
	foreach ($messages as $key => $msg) {
		$tmp = explode(':', $msg);
		setEventMessages($tmp[0], null, !empty($tmp[1]) ? $tmp[1] : 'mesgs');
	}
}

// Functions

if (!function_exists("llxHeader")) {
	/**
	 *	Show HTML header HTML + BODY + Top menu + left menu + DIV
	 *
	 * @param 	string 			$head				Optional head lines
	 * @param 	string 			$title				HTML title
	 * @param	string			$help_url			Url links to help page
	 * 		                            			Syntax is: For a wiki page: EN:EnglishPage|FR:FrenchPage|ES:SpanishPage|DE:GermanPage
	 *                                  			For other external page: http://server/url
	 * @param	string			$target				Target to use on links
	 * @param 	int<0,1>		$disablejs			More content into html header
	 * @param 	int<0,1>		$disablehead		More content into html header
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
		global $conf, $hookmanager;

		$parameters = array(
			'head' => & $head,
			'title' => & $title,
			'help_url' => & $help_url,
			'target' => & $target,
			'disablejs' => & $disablejs,
			'disablehead' => & $disablehead,
			'arrayofjs' => & $arrayofjs,
			'arrayofcss' => & $arrayofcss,
			'morequerystring' => & $morequerystring,
			'morecssonbody' => & $morecssonbody,
			'replacemainareaby' => & $replacemainareaby,
			'disablenofollow' => & $disablenofollow,
			'disablenoindex' => & $disablenoindex

		);
		$reshook = $hookmanager->executeHooks('llxHeader', $parameters);
		if ($reshook > 0) {
			print $hookmanager->resPrint;
			return;
		}

		// html header
		top_htmlhead($head, $title, $disablejs, $disablehead, $arrayofjs, $arrayofcss, 0, $disablenofollow, $disablenoindex);

		$tmpcsstouse = 'sidebar-collapse'.($morecssonbody ? ' '.$morecssonbody : '');
		// If theme MD and classic layer, we open the menulayer by default.
		if ($conf->theme == 'md' && !in_array($conf->browser->layout, array('phone', 'tablet')) && !getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER')) {
			global $mainmenu;
			if ($mainmenu != 'website') {
				$tmpcsstouse = $morecssonbody; // We do not use sidebar-collpase by default to have menuhider open by default.
			}
		}

		if (getDolGlobalString('MAIN_OPTIMIZEFORCOLORBLIND')) {
			$tmpcsstouse .= ' colorblind-'.strip_tags($conf->global->MAIN_OPTIMIZEFORCOLORBLIND);
		}

		print '<body id="mainbody" class="'.$tmpcsstouse.'">'."\n";

		// top menu and left menu area
		if ((empty($conf->dol_hide_topmenu) || GETPOSTINT('dol_invisible_topmenu')) && !GETPOSTINT('dol_openinpopup')) {
			top_menu($head, $title, $target, $disablejs, $disablehead, $arrayofjs, $arrayofcss, $morequerystring, $help_url);
		}

		if (empty($conf->dol_hide_leftmenu) && !GETPOST('dol_openinpopup', 'aZ09')) {
			left_menu(array(), $help_url, '', '', 1, $title, 1); // $menumanager is retrieved with a global $menumanager inside this function
		}

		// main area
		if ($replacemainareaby) {
			print $replacemainareaby;
			return;
		}
		main_area($title);
	}
}


/**
 *  Show HTTP header. Called by top_htmlhead().
 *
 *  @param  string  	$contenttype    Content type. For example, 'text/html'
 *  @param	int<0,1>	$forcenocache	Force disabling of cache for the page
 *  @return	void
 */
function top_httphead($contenttype = 'text/html', $forcenocache = 0)
{
	global $db, $conf, $hookmanager;

	if ($contenttype == 'text/html') {
		header("Content-Type: text/html; charset=".$conf->file->character_set_client);
	} else {
		header("Content-Type: ".$contenttype);
	}

	// Security options

	// X-Content-Type-Options
	header("X-Content-Type-Options: nosniff"); // With the nosniff option, if the server says the content is text/html, the browser will render it as text/html (note that most browsers now force this option to on)

	// X-Frame-Options
	if (!defined('XFRAMEOPTIONS_ALLOWALL')) {
		header("X-Frame-Options: SAMEORIGIN"); // By default, frames allowed only if on same domain (stop some XSS attacks)
	} else {
		header("X-Frame-Options: ALLOWALL");
	}

	if (getDolGlobalString('MAIN_SECURITY_FORCE_ACCESS_CONTROL_ALLOW_ORIGIN')) {
		$tmpurl = constant('DOL_MAIN_URL_ROOT');
		$tmpurl = preg_replace('/^(https?:\/\/[^\/]+)\/.*$/', '\1', $tmpurl);
		header('Access-Control-Allow-Origin: '.$tmpurl);
		header('Vary: Origin');
	}

	// X-XSS-Protection
	//header("X-XSS-Protection: 1");      		// XSS filtering protection of some browsers (note: use of Content-Security-Policy is more efficient). Disabled as deprecated.

	// Content-Security-Policy-Report-Only
	if (!defined('MAIN_SECURITY_FORCECSPRO')) {
		// If CSP not forced from the page

		// A default security policy that keep usage of js external component like ckeditor, stripe, google, working
		// For example: to restrict to only local resources, except for css (cloudflare+google), and js (transifex + google tags) and object/iframe (youtube)
		// default-src 'self'; style-src: https://cdnjs.cloudflare.com https://fonts.googleapis.com; script-src: https://cdn.transifex.com https://www.googletagmanager.com; object-src https://youtube.com; frame-src https://youtube.com; img-src: *;
		// For example, to restrict everything to itself except img that can be on other servers:
		// default-src 'self'; img-src *;
		// Pre-existing site that uses too much js code to fix but wants to ensure resources are loaded only over https and disable plugins:
		// default-src https: 'unsafe-inline' 'unsafe-eval'; object-src 'none'
		//
		// $contentsecuritypolicy = "frame-ancestors 'self'; img-src * data:; font-src *; default-src 'self' 'unsafe-inline' 'unsafe-eval' *.paypal.com *.stripe.com *.google.com *.googleapis.com *.google-analytics.com *.googletagmanager.com;";
		// $contentsecuritypolicy = "frame-ancestors 'self'; img-src * data:; font-src *; default-src *; script-src 'self' 'unsafe-inline' *.paypal.com *.stripe.com *.google.com *.googleapis.com *.google-analytics.com *.googletagmanager.com; style-src 'self' 'unsafe-inline'; connect-src 'self';";
		$contentsecuritypolicy = getDolGlobalString('MAIN_SECURITY_FORCECSPRO');

		if (!is_object($hookmanager)) {
			include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
			$hookmanager = new HookManager($db);
		}
		$hookmanager->initHooks(array("main"));

		$parameters = array('contentsecuritypolicy' => $contentsecuritypolicy, 'mode' => 'reportonly');
		$result = $hookmanager->executeHooks('setContentSecurityPolicy', $parameters); // Note that $action and $object may have been modified by some hooks
		if ($result > 0) {
			$contentsecuritypolicy = $hookmanager->resPrint; // Replace CSP
		} else {
			$contentsecuritypolicy .= $hookmanager->resPrint; // Concat CSP
		}

		if (!empty($contentsecuritypolicy)) {
			header("Content-Security-Policy-Report-Only: ".$contentsecuritypolicy);
		}
	} else {
		header("Content-Security-Policy: ".constant('MAIN_SECURITY_FORCECSPRO'));
	}

	// Content-Security-Policy
	if (!defined('MAIN_SECURITY_FORCECSP')) {
		// If CSP not forced from the page

		// A default security policy that keep usage of js external component like ckeditor, stripe, google, working
		// For example: to restrict to only local resources, except for css (cloudflare+google), and js (transifex + google tags) and object/iframe (youtube)
		// default-src 'self'; style-src: https://cdnjs.cloudflare.com https://fonts.googleapis.com; script-src: https://cdn.transifex.com https://www.googletagmanager.com; object-src https://youtube.com; frame-src https://youtube.com; img-src: *;
		// For example, to restrict everything to itself except img that can be on other servers:
		// default-src 'self'; img-src *;
		// Pre-existing site that uses too much js code to fix but wants to ensure resources are loaded only over https and disable plugins:
		// default-src https: 'unsafe-inline' 'unsafe-eval'; object-src 'none'
		//
		// $contentsecuritypolicy = "frame-ancestors 'self'; img-src * data:; font-src *; default-src 'self' 'unsafe-inline' 'unsafe-eval' *.paypal.com *.stripe.com *.google.com *.googleapis.com *.google-analytics.com *.googletagmanager.com;";
		// $contentsecuritypolicy = "frame-ancestors 'self'; img-src * data:; font-src *; default-src *; script-src 'self' 'unsafe-inline' *.paypal.com *.stripe.com *.google.com *.googleapis.com *.google-analytics.com *.googletagmanager.com; style-src 'self' 'unsafe-inline'; connect-src 'self';";
		$contentsecuritypolicy = getDolGlobalString('MAIN_SECURITY_FORCECSP');

		if (!is_object($hookmanager)) {
			include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
			$hookmanager = new HookManager($db);
		}
		$hookmanager->initHooks(array("main"));

		$parameters = array('contentsecuritypolicy' => $contentsecuritypolicy, 'mode' => 'active');
		$result = $hookmanager->executeHooks('setContentSecurityPolicy', $parameters); // Note that $action and $object may have been modified by some hooks
		if ($result > 0) {
			$contentsecuritypolicy = $hookmanager->resPrint; // Replace CSP
		} else {
			$contentsecuritypolicy .= $hookmanager->resPrint; // Concat CSP
		}

		if (!empty($contentsecuritypolicy)) {
			header("Content-Security-Policy: ".$contentsecuritypolicy);
		}
	} else {
		header("Content-Security-Policy: ".constant('MAIN_SECURITY_FORCECSP'));
	}

	// Referrer-Policy
	// Say if we must provide the referrer when we jump onto another web page.
	// Default browser are 'strict-origin-when-cross-origin' (only domain is sent on other domain switching), we want more so we use 'same-origin' so browser doesn't send any referrer at all when going into another web site domain.
	// Note that we do not use 'strict-origin' as this breaks feature to restore filters when clicking on "back to page" link on some cases.
	if (!defined('MAIN_SECURITY_FORCERP')) {
		$referrerpolicy = getDolGlobalString('MAIN_SECURITY_FORCERP', "same-origin");

		header("Referrer-Policy: ".$referrerpolicy);
	}

	if ($forcenocache) {
		header("Cache-Control: no-cache, no-store, must-revalidate, max-age=0");
	}

	// No need to add this token in header, we use instead the one into the forms.
	//header("anti-csrf-token: ".newToken());
}

/**
 * Output html header of a page. It calls also top_httphead()
 * This code is also duplicated into security2.lib.php::dol_loginfunction
 *
 * @param 	string		$head			 Optional head lines
 * @param 	string		$title			 HTML title
 * @param 	int<0,1>   	$disablejs		 Disable js output
 * @param 	int<0,1>   	$disablehead	 Disable head output
 * @param 	string[]	$arrayofjs		 Array of complementary js files
 * @param 	string[]	$arrayofcss		 Array of complementary css files
 * @param 	int<0,1>	$disableforlogin Do not load heavy js and css for login pages
 * @param   int<0,1>	$disablenofollow Disable nofollow tag for meta robots
 * @param   int<0,1>	$disablenoindex  Disable noindex tag for meta robots
 * @return	void
 */
function top_htmlhead($head, $title = '', $disablejs = 0, $disablehead = 0, $arrayofjs = array(), $arrayofcss = array(), $disableforlogin = 0, $disablenofollow = 0, $disablenoindex = 0)
{
	global $db, $conf, $langs, $user, $mysoc, $hookmanager;

	top_httphead();

	if (empty($conf->css)) {
		$conf->css = '/theme/eldy/style.css.php'; // If not defined, eldy by default
	}

	print '<!doctype html>'."\n";

	print '<html lang="'.substr($langs->defaultlang, 0, 2).'">'."\n";

	//print '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr">'."\n";
	if (empty($disablehead)) {
		if (!is_object($hookmanager)) {
			include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
			$hookmanager = new HookManager($db);
		}
		$hookmanager->initHooks(array("main"));

		$ext = 'layout='.(empty($conf->browser->layout) ? '' : $conf->browser->layout).'&amp;version='.urlencode(DOL_VERSION);

		print "<head>\n";

		if (GETPOST('dol_basehref', 'alpha')) {
			print '<base href="'.dol_escape_htmltag(GETPOST('dol_basehref', 'alpha')).'">'."\n";
		}

		// Displays meta
		print '<meta charset="utf-8">'."\n";
		print '<meta name="robots" content="'.($disablenoindex ? 'index' : 'noindex').($disablenofollow ? ',follow' : ',nofollow').'">'."\n"; // Do not index
		print '<meta name="viewport" content="width=device-width, initial-scale=1.0">'."\n"; // Scale for mobile device
		print '<meta name="author" content="Dolibarr Development Team">'."\n";
		print '<meta name="anti-csrf-newtoken" content="'.newToken().'">'."\n";
		print '<meta name="anti-csrf-currenttoken" content="'.currentToken().'">'."\n";
		if (getDolGlobalInt('MAIN_FEATURES_LEVEL')) {
			print '<meta name="MAIN_FEATURES_LEVEL" content="'.getDolGlobalInt('MAIN_FEATURES_LEVEL').'">'."\n";
		}
		// Favicon
		$favicon = DOL_URL_ROOT.'/theme/dolibarr_256x256_color.png';
		if (!empty($mysoc->logo_squarred_mini)) {
			$favicon = DOL_URL_ROOT.'/viewimage.php?cache=1&modulepart=mycompany&file='.urlencode('logos/thumbs/'.$mysoc->logo_squarred_mini);
		}
		if (getDolGlobalString('MAIN_FAVICON_URL')) {
			$favicon = getDolGlobalString('MAIN_FAVICON_URL');
		}
		if (empty($conf->dol_use_jmobile)) {
			print '<link rel="shortcut icon" type="image/x-icon" href="'.$favicon.'"/>'."\n"; // Not required into an Android webview
		}

		// Mobile appli like icon
		$manifest = DOL_URL_ROOT.'/theme/'.$conf->theme.'/manifest.json.php';
		$parameters = array('manifest' => $manifest);
		$resHook = $hookmanager->executeHooks('hookSetManifest', $parameters); // Note that $action and $object may have been modified by some hooks
		if ($resHook > 0) {
			$manifest = $hookmanager->resPrint; // Replace manifest.json
		} else {
			$manifest .= $hookmanager->resPrint; // Concat to actual manifest declaration
		}
		if (!empty($manifest)) {
			print '<link rel="manifest" href="'.$manifest.'" />'."\n";
		}

		if (getDolGlobalString('THEME_ELDY_TOPMENU_BACK1')) {
			// TODO: use auto theme color switch
			print '<meta name="theme-color" content="rgb(' . getDolGlobalString('THEME_ELDY_TOPMENU_BACK1').')">'."\n";
		}

		// Auto refresh page
		if (GETPOSTINT('autorefresh') > 0) {
			print '<meta http-equiv="refresh" content="'.GETPOSTINT('autorefresh').'">';
		}

		// Displays title
		$appli = constant('DOL_APPLICATION_TITLE');
		if (getDolGlobalString('MAIN_APPLICATION_TITLE')) {
			$appli = getDolGlobalString('MAIN_APPLICATION_TITLE');
		}

		print '<title>';
		$titletoshow = '';
		if ($title && preg_match('/showapp/', getDolGlobalString('MAIN_HTML_TITLE'))) {
			$titletoshow = dol_htmlentities($appli.' - '.$title);
		} elseif ($title) {
			$titletoshow = dol_htmlentities($title);
		} else {
			$titletoshow = dol_htmlentities($appli);
		}

		$parameters = array('title' => $titletoshow);
		$result = $hookmanager->executeHooks('setHtmlTitle', $parameters); // Note that $action and $object may have been modified by some hooks
		if ($result > 0) {
			$titletoshow = $hookmanager->resPrint; // Replace Title to show
		} else {
			$titletoshow .= $hookmanager->resPrint; // Concat to Title to show
		}

		print $titletoshow;
		print '</title>';

		print "\n";

		if (GETPOSTINT('version')) {
			$ext = 'version='.GETPOSTINT('version'); // useful to force no cache on css/js
		}
		// Refresh value of MAIN_IHM_PARAMS_REV before forging the parameter line.
		if (GETPOST('dol_resetcache')) {
			include_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
			dolibarr_set_const($db, "MAIN_IHM_PARAMS_REV", getDolGlobalInt('MAIN_IHM_PARAMS_REV') + 1, 'chaine', 0, '', $conf->entity);
		}

		$themeparam = '?lang='.$langs->defaultlang.'&amp;theme='.$conf->theme.(GETPOST('optioncss', 'aZ09') ? '&amp;optioncss='.GETPOST('optioncss', 'aZ09', 1) : '').(empty($user->id) ? '' : ('&amp;userid='.$user->id)).'&amp;entity='.$conf->entity;

		$themeparam .= ($ext ? '&amp;'.$ext : '').'&amp;revision='.getDolGlobalInt("MAIN_IHM_PARAMS_REV");
		if (GETPOSTISSET('dol_hide_topmenu')) {
			$themeparam .= '&amp;dol_hide_topmenu='.GETPOSTINT('dol_hide_topmenu');
		}
		if (GETPOSTISSET('dol_hide_leftmenu')) {
			$themeparam .= '&amp;dol_hide_leftmenu='.GETPOSTINT('dol_hide_leftmenu');
		}
		if (GETPOSTISSET('dol_optimize_smallscreen')) {
			$themeparam .= '&amp;dol_optimize_smallscreen='.GETPOSTINT('dol_optimize_smallscreen');
		}
		if (GETPOSTISSET('dol_no_mouse_hover')) {
			$themeparam .= '&amp;dol_no_mouse_hover='.GETPOSTINT('dol_no_mouse_hover');
		}
		if (GETPOSTISSET('dol_use_jmobile')) {
			$themeparam .= '&amp;dol_use_jmobile='.GETPOSTINT('dol_use_jmobile');
			$conf->dol_use_jmobile = GETPOSTINT('dol_use_jmobile');
		}
		if (GETPOSTISSET('THEME_DARKMODEENABLED')) {
			$themeparam .= '&amp;THEME_DARKMODEENABLED='.GETPOSTINT('THEME_DARKMODEENABLED');
		}
		if (GETPOSTISSET('THEME_SATURATE_RATIO')) {
			$themeparam .= '&amp;THEME_SATURATE_RATIO='.GETPOSTINT('THEME_SATURATE_RATIO');
		}

		if (getDolGlobalString('MAIN_ENABLE_FONT_ROBOTO')) {
			print '<link rel="preconnect" href="https://fonts.gstatic.com">'."\n";
			print '<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@200;300;400;500;600&display=swap" rel="stylesheet">'."\n";
		}

		if (!defined('DISABLE_JQUERY') && !$disablejs && $conf->use_javascript_ajax) {
			print '<!-- Includes CSS for JQuery (Ajax library) -->'."\n";
			$jquerytheme = 'base';
			if (getDolGlobalString('MAIN_USE_JQUERY_THEME')) {
				$jquerytheme = getDolGlobalString('MAIN_USE_JQUERY_THEME');
			}
			if (constant('JS_JQUERY_UI')) {
				print '<link rel="stylesheet" type="text/css" href="'.JS_JQUERY_UI.'css/'.$jquerytheme.'/jquery-ui.min.css'.($ext ? '?'.$ext : '').'">'."\n"; // Forced JQuery
			} else {
				print '<link rel="stylesheet" type="text/css" href="'.DOL_URL_ROOT.'/includes/jquery/css/'.$jquerytheme.'/jquery-ui.css'.($ext ? '?'.$ext : '').'">'."\n"; // JQuery
			}
			if (!defined('DISABLE_JQUERY_JNOTIFY')) {
				print '<link rel="stylesheet" type="text/css" href="'.DOL_URL_ROOT.'/includes/jquery/plugins/jnotify/jquery.jnotify-alt.min.css'.($ext ? '?'.$ext : '').'">'."\n"; // JNotify
			}
			if (!defined('DISABLE_SELECT2') && (getDolGlobalString('MAIN_USE_JQUERY_MULTISELECT') || defined('REQUIRE_JQUERY_MULTISELECT'))) {     // jQuery plugin "mutiselect", "multiple-select", "select2"...
				$tmpplugin = !getDolGlobalString('MAIN_USE_JQUERY_MULTISELECT') ? constant('REQUIRE_JQUERY_MULTISELECT') : $conf->global->MAIN_USE_JQUERY_MULTISELECT;
				print '<link rel="stylesheet" type="text/css" href="'.DOL_URL_ROOT.'/includes/jquery/plugins/'.$tmpplugin.'/dist/css/'.$tmpplugin.'.css'.($ext ? '?'.$ext : '').'">'."\n";
			}
		}

		if (!defined('DISABLE_FONT_AWSOME')) {
			print '<!-- Includes CSS for font awesome -->'."\n";
			$fontawesome_directory = getDolGlobalString('MAIN_FONTAWESOME_DIRECTORY', '/theme/common/fontawesome-5');
			print '<link rel="stylesheet" type="text/css" href="'.DOL_URL_ROOT.$fontawesome_directory.'/css/all.min.css'.($ext ? '?'.$ext : '').'">'."\n";
		}

		print '<!-- Includes CSS for Dolibarr theme -->'."\n";
		// Output style sheets (optioncss='print' or ''). Note: $conf->css looks like '/theme/eldy/style.css.php'
		$themepath = dol_buildpath($conf->css, 1);
		$themesubdir = '';
		if (!empty($conf->modules_parts['theme'])) {	// This slow down
			foreach ($conf->modules_parts['theme'] as $reldir) {
				if (file_exists(dol_buildpath($reldir.$conf->css, 0))) {
					$themepath = dol_buildpath($reldir.$conf->css, 1);
					$themesubdir = $reldir;
					break;
				}
			}
		}

		//print 'themepath='.$themepath.' themeparam='.$themeparam;exit;
		print '<link rel="stylesheet" type="text/css" href="'.$themepath.$themeparam.'">'."\n";
		if (getDolGlobalString('MAIN_FIX_FLASH_ON_CHROME')) {
			print '<!-- Includes CSS that does not exists as a workaround of flash bug of chrome -->'."\n".'<link rel="stylesheet" type="text/css" href="filethatdoesnotexiststosolvechromeflashbug">'."\n";
		}

		// LEAFLET AND GEOMAN
		if (getDolGlobalString('MAIN_USE_GEOPHP')) {
			print '<link rel="stylesheet" href="'.DOL_URL_ROOT.'/includes/leaflet/leaflet.css'.($ext ? '?'.$ext : '')."\">\n";
			print '<link rel="stylesheet" href="'.DOL_URL_ROOT.'/includes/leaflet/leaflet-geoman.css'.($ext ? '?'.$ext : '')."\">\n";
		}

		// CSS forced by modules (relative url starting with /)
		if (!empty($conf->modules_parts['css'])) {
			$arraycss = (array) $conf->modules_parts['css'];
			foreach ($arraycss as $modcss => $filescss) {
				$filescss = (array) $filescss; // To be sure filecss is an array
				foreach ($filescss as $cssfile) {
					if (empty($cssfile)) {
						dol_syslog("Warning: module ".$modcss." declared a css path file into its descriptor that is empty.", LOG_WARNING);
					}
					// cssfile is a relative path
					$urlforcss = dol_buildpath($cssfile, 1);
					if ($urlforcss && $urlforcss != '/') {
						print '<!-- Includes CSS added by module '.$modcss.' -->'."\n".'<link rel="stylesheet" type="text/css" href="'.$urlforcss;
						// We add params only if page is not static, because some web server setup does not return content type text/css if url has parameters, so browser cache is not used.
						if (!preg_match('/\.css$/i', $cssfile)) {
							print $themeparam;
						}
						print '">'."\n";
					} else {
						dol_syslog("Warning: module ".$modcss." declared a css path file for a file we can't find.", LOG_WARNING);
					}
				}
			}
		}
		// CSS forced by page in top_htmlhead call (relative url starting with /)
		if (is_array($arrayofcss)) {
			foreach ($arrayofcss as $cssfile) {
				if (preg_match('/^(http|\/\/)/i', $cssfile)) {
					$urltofile = $cssfile;
				} else {
					$urltofile = dol_buildpath($cssfile, 1);
				}
				print '<!-- Includes CSS added by page -->'."\n".'<link rel="stylesheet" type="text/css" title="default" href="'.$urltofile;
				// We add params only if page is not static, because some web server setup does not return content type text/css if url has parameters and browser cache is not used.
				if (!preg_match('/\.css$/i', $cssfile)) {
					print $themeparam;
				}
				print '">'."\n";
			}
		}

		// Custom CSS
		if (getDolGlobalString('MAIN_IHM_CUSTOM_CSS')) {
			// If a custom CSS was set, we add link to the custom css php file
			print '<link rel="stylesheet" type="text/css" href="'.DOL_URL_ROOT.'/theme/custom.css.php'.($ext ? '?'.$ext : '').'&amp;revision='.getDolGlobalInt("MAIN_IHM_PARAMS_REV").'">'."\n";
		}

		// Output standard javascript links
		if (!defined('DISABLE_JQUERY') && !$disablejs && !empty($conf->use_javascript_ajax)) {
			// JQuery. Must be before other includes
			print '<!-- Includes JS for JQuery -->'."\n";
			if (defined('JS_JQUERY') && constant('JS_JQUERY')) {
				print '<script nonce="'.getNonce().'" src="'.JS_JQUERY.'jquery.min.js'.($ext ? '?'.$ext : '').'"></script>'."\n";
			} else {
				print '<script nonce="'.getNonce().'" src="'.DOL_URL_ROOT.'/includes/jquery/js/jquery.min.js'.($ext ? '?'.$ext : '').'"></script>'."\n";
			}
			if (defined('JS_JQUERY_UI') && constant('JS_JQUERY_UI')) {
				print '<script nonce="'.getNonce().'" src="'.JS_JQUERY_UI.'jquery-ui.min.js'.($ext ? '?'.$ext : '').'"></script>'."\n";
			} else {
				print '<script nonce="'.getNonce().'" src="'.DOL_URL_ROOT.'/includes/jquery/js/jquery-ui.min.js'.($ext ? '?'.$ext : '').'"></script>'."\n";
			}
			// jQuery jnotify
			if (!getDolGlobalString('MAIN_DISABLE_JQUERY_JNOTIFY') && !defined('DISABLE_JQUERY_JNOTIFY')) {
				print '<script nonce="'.getNonce().'" src="'.DOL_URL_ROOT.'/includes/jquery/plugins/jnotify/jquery.jnotify.min.js'.($ext ? '?'.$ext : '').'"></script>'."\n";
			}
			// Table drag and drop lines
			if (empty($disableforlogin) && !defined('DISABLE_JQUERY_TABLEDND')) {
				print '<script nonce="'.getNonce().'" src="'.DOL_URL_ROOT.'/includes/jquery/plugins/tablednd/jquery.tablednd.min.js'.($ext ? '?'.$ext : '').'"></script>'."\n";
			}
			// Chart
			if (empty($disableforlogin) && (!getDolGlobalString('MAIN_JS_GRAPH') || getDolGlobalString('MAIN_JS_GRAPH') == 'chart') && !defined('DISABLE_JS_GRAPH')) {
				print '<script nonce="'.getNonce().'" src="'.DOL_URL_ROOT.'/includes/nnnick/chartjs/dist/chart.min.js'.($ext ? '?'.$ext : '').'"></script>'."\n";
			}

			// jQuery jeditable for Edit In Place features
			if (getDolGlobalString('MAIN_USE_JQUERY_JEDITABLE') && !defined('DISABLE_JQUERY_JEDITABLE')) {
				print '<!-- JS to manage editInPlace feature -->'."\n";
				print '<script nonce="'.getNonce().'" src="'.DOL_URL_ROOT.'/includes/jquery/plugins/jeditable/jquery.jeditable.js'.($ext ? '?'.$ext : '').'"></script>'."\n";
				print '<script nonce="'.getNonce().'" src="'.DOL_URL_ROOT.'/includes/jquery/plugins/jeditable/jquery.jeditable.ui-datepicker.js'.($ext ? '?'.$ext : '').'"></script>'."\n";
				print '<script nonce="'.getNonce().'" src="'.DOL_URL_ROOT.'/includes/jquery/plugins/jeditable/jquery.jeditable.ui-autocomplete.js'.($ext ? '?'.$ext : '').'"></script>'."\n";
				print '<script>'."\n";
				print 'var urlSaveInPlace = \''.DOL_URL_ROOT.'/core/ajax/saveinplace.php\';'."\n";
				print 'var urlLoadInPlace = \''.DOL_URL_ROOT.'/core/ajax/loadinplace.php\';'."\n";
				print 'var tooltipInPlace = \''.$langs->transnoentities('ClickToEdit').'\';'."\n"; // Added in title attribute of span
				print 'var placeholderInPlace = \'&nbsp;\';'."\n"; // If we put another string than $langs->trans("ClickToEdit") here, nothing is shown. If we put empty string, there is error, Why ?
				print 'var cancelInPlace = \''.$langs->trans("Cancel").'\';'."\n";
				print 'var submitInPlace = \''.$langs->trans('Ok').'\';'."\n";
				print 'var indicatorInPlace = \'<img src="'.DOL_URL_ROOT."/theme/".$conf->theme."/img/working.gif".'">\';'."\n";
				print 'var withInPlace = 300;'; // width in pixel for default string edit
				print '</script>'."\n";
				print '<script nonce="'.getNonce().'" src="'.DOL_URL_ROOT.'/core/js/editinplace.js'.($ext ? '?'.$ext : '').'"></script>'."\n";
				print '<script nonce="'.getNonce().'" src="'.DOL_URL_ROOT.'/includes/jquery/plugins/jeditable/jquery.jeditable.ckeditor.js'.($ext ? '?'.$ext : '').'"></script>'."\n";
			}
			// jQuery Timepicker
			if (getDolGlobalString('MAIN_USE_JQUERY_TIMEPICKER') || defined('REQUIRE_JQUERY_TIMEPICKER')) {
				print '<script nonce="'.getNonce().'" src="'.DOL_URL_ROOT.'/includes/jquery/plugins/timepicker/jquery-ui-timepicker-addon.js'.($ext ? '?'.$ext : '').'"></script>'."\n";
				print '<script nonce="'.getNonce().'" src="'.DOL_URL_ROOT.'/core/js/timepicker.js.php?lang='.$langs->defaultlang.($ext ? '&amp;'.$ext : '').'"></script>'."\n";
			}
			if (!defined('DISABLE_SELECT2') && (getDolGlobalString('MAIN_USE_JQUERY_MULTISELECT') || defined('REQUIRE_JQUERY_MULTISELECT'))) {
				// jQuery plugin "mutiselect", "multiple-select", "select2", ...
				$tmpplugin = !getDolGlobalString('MAIN_USE_JQUERY_MULTISELECT') ? constant('REQUIRE_JQUERY_MULTISELECT') : $conf->global->MAIN_USE_JQUERY_MULTISELECT;
				print '<script nonce="'.getNonce().'" src="'.DOL_URL_ROOT.'/includes/jquery/plugins/'.$tmpplugin.'/dist/js/'.$tmpplugin.'.full.min.js'.($ext ? '?'.$ext : '').'"></script>'."\n"; // We include full because we need the support of containerCssClass
			}
			if (!defined('DISABLE_MULTISELECT')) {     // jQuery plugin "mutiselect" to select with checkboxes. Can be removed once we have an enhanced search tool
				print '<script nonce="'.getNonce().'" src="'.DOL_URL_ROOT.'/includes/jquery/plugins/multiselect/jquery.multi-select.js'.($ext ? '?'.$ext : '').'"></script>'."\n";
			}
		}

		if (!$disablejs && !empty($conf->use_javascript_ajax)) {
			// CKEditor
			if (empty($disableforlogin) && (isModEnabled('fckeditor') && (!getDolGlobalString('FCKEDITOR_EDITORNAME') || getDolGlobalString('FCKEDITOR_EDITORNAME') == 'ckeditor') && !defined('DISABLE_CKEDITOR')) || defined('FORCE_CKEDITOR')) {
				print '<!-- Includes JS for CKEditor -->'."\n";
				$pathckeditor = DOL_URL_ROOT.'/includes/ckeditor/ckeditor/';
				$jsckeditor = 'ckeditor.js';
				if (constant('JS_CKEDITOR')) {
					// To use external ckeditor 4 js lib
					$pathckeditor = constant('JS_CKEDITOR');
				}
				print '<script nonce="'.getNonce().'">';
				print '/* enable ckeditor by main.inc.php */';
				print 'var CKEDITOR_BASEPATH = \''.dol_escape_js($pathckeditor).'\';'."\n";
				print 'var ckeditorConfig = \''.dol_escape_js(dol_buildpath($themesubdir.'/theme/'.$conf->theme.'/ckeditor/config.js'.($ext ? '?'.$ext : ''), 1)).'\';'."\n"; // $themesubdir='' in standard usage
				print 'var ckeditorFilebrowserBrowseUrl = \''.DOL_URL_ROOT.'/core/filemanagerdol/browser/default/browser.php?Connector='.DOL_URL_ROOT.'/core/filemanagerdol/connectors/php/connector.php\';'."\n";
				print 'var ckeditorFilebrowserImageBrowseUrl = \''.DOL_URL_ROOT.'/core/filemanagerdol/browser/default/browser.php?Type=Image&Connector='.DOL_URL_ROOT.'/core/filemanagerdol/connectors/php/connector.php\';'."\n";
				print '</script>'."\n";
				print '<script src="'.$pathckeditor.$jsckeditor.($ext ? '?'.$ext : '').'"></script>'."\n";
				print '<script>';
				if (GETPOST('mode', 'aZ09') == 'Full_inline') {
					print 'CKEDITOR.disableAutoInline = false;'."\n";
				} else {
					print 'CKEDITOR.disableAutoInline = true;'."\n";
				}
				print '</script>'."\n";
			}

			// Browser notifications (if NOREQUIREMENU is on, it is mostly a page for popup, so we do not enable notif too. We hide also for public pages).
			if (!defined('NOBROWSERNOTIF') && !defined('NOREQUIREMENU') && !defined('NOLOGIN')) {
				$enablebrowsernotif = false;
				if (isModEnabled('agenda') && getDolGlobalString('AGENDA_REMINDER_BROWSER')) {
					$enablebrowsernotif = true;
				}
				if ($conf->browser->layout == 'phone') {
					$enablebrowsernotif = false;
				}
				if ($enablebrowsernotif) {
					print '<!-- Includes JS of Dolibarr (browser layout = '.$conf->browser->layout.')-->'."\n";
					print '<script nonce="'.getNonce().'" src="'.DOL_URL_ROOT.'/core/js/lib_notification.js.php'.($ext ? '?'.$ext : '').'"></script>'."\n";
				}
			}

			// Global js function
			print '<!-- Includes JS of Dolibarr -->'."\n";
			print '<script nonce="'.getNonce().'" src="'.DOL_URL_ROOT.'/core/js/lib_head.js.php?lang='.$langs->defaultlang.($ext ? '&amp;'.$ext : '').'"></script>'."\n";

			// Leaflet TODO use dolibarr files
			if (getDolGlobalString('MAIN_USE_GEOPHP')) {
				print '<script nonce="'.getNonce().'" src="'.DOL_URL_ROOT.'/includes/leaflet/leaflet.js'.($ext ? '?'.$ext : '').'"></script>'."\n";
				print '<script nonce="'.getNonce().'" src="'.DOL_URL_ROOT.'/includes/leaflet/leaflet-geoman.min.js'.($ext ? '?'.$ext : '').'"></script>'."\n";
			}

			// JS forced by modules (relative url starting with /)
			if (!empty($conf->modules_parts['js'])) {		// $conf->modules_parts['js'] is array('module'=>array('file1','file2'))
				$arrayjs = (array) $conf->modules_parts['js'];
				foreach ($arrayjs as $modjs => $filesjs) {
					$filesjs = (array) $filesjs; // To be sure filejs is an array
					foreach ($filesjs as $jsfile) {
						// jsfile is a relative path
						$urlforjs = dol_buildpath($jsfile, 1);
						if ($urlforjs && $urlforjs != '/') {
							print '<!-- Include JS added by module '.$modjs.'-->'."\n";
							print '<script nonce="'.getNonce().'" src="'.$urlforjs.((strpos($jsfile, '?') === false) ? '?' : '&amp;').'lang='.$langs->defaultlang.'"></script>'."\n";
						} else {
							dol_syslog("Warning: module ".$modjs." declared a js path file for a file we can't find.", LOG_WARNING);
						}
					}
				}
			}
			// JS forced by page in top_htmlhead (relative url starting with /)
			if (is_array($arrayofjs)) {
				print '<!-- Includes JS added by page -->'."\n";
				foreach ($arrayofjs as $jsfile) {
					if (preg_match('/^(http|\/\/)/i', $jsfile)) {
						print '<script nonce="'.getNonce().'" src="'.$jsfile.((strpos($jsfile, '?') === false) ? '?' : '&amp;').'lang='.$langs->defaultlang.'"></script>'."\n";
					} else {
						print '<script nonce="'.getNonce().'" src="'.dol_buildpath($jsfile, 1).((strpos($jsfile, '?') === false) ? '?' : '&amp;').'lang='.$langs->defaultlang.'"></script>'."\n";
					}
				}
			}
		}

		//If you want to load custom javascript file from your selected theme directory
		if (getDolGlobalString('ALLOW_THEME_JS')) {
			$theme_js = dol_buildpath('/theme/'.$conf->theme.'/'.$conf->theme.'.js', 0);
			if (file_exists($theme_js)) {
				print '<script nonce="'.getNonce().'" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/'.$conf->theme.'.js'.($ext ? '?'.$ext : '').'"></script>'."\n";
			}
		}

		if (!empty($head)) {
			print $head."\n";
		}
		if (getDolGlobalString('MAIN_HTML_HEADER')) {
			print getDolGlobalString('MAIN_HTML_HEADER') . "\n";
		}

		$parameters = array();
		$result = $hookmanager->executeHooks('addHtmlHeader', $parameters); // Note that $action and $object may have been modified by some hooks
		print $hookmanager->resPrint; // Replace Title to show

		print "</head>\n\n";
	}

	$conf->headerdone = 1; // To tell header was output
}


/**
 *  Show an HTML header + a BODY + The top menu bar
 *
 *  @param      string			$head    			Lines in the HEAD
 *  @param      string			$title   			Title of web page
 *  @param      string			$target  			Target to use in menu links (Example: '' or '_top')
 *	@param		int<0,1>		$disablejs			Do not output links to js (Ex: qd fonction utilisee par sous formulaire Ajax)
 *	@param		int<0,1>		$disablehead		Do not output head section
 *	@param		string[]		$arrayofjs			Array of js files to add in header
 *	@param		string[]		$arrayofcss			Array of css files to add in header
 *  @param		string			$morequerystring	Query string to add to the link "print" to get same parameters (use only if autodetect fails)
 *  @param      string			$helppagename    	Name of wiki page for help ('' by default).
 * 				     		    		            Syntax is: For a wiki page: EN:EnglishPage|FR:FrenchPage|ES:SpanishPage|DE:GermanPage
 * 						                		    For other external page: http://server/url
 *  @return		void
 */
function top_menu($head, $title = '', $target = '', $disablejs = 0, $disablehead = 0, $arrayofjs = array(), $arrayofcss = array(), $morequerystring = '', $helppagename = '')
{
	global $user, $conf, $langs, $db, $form;
	global $dolibarr_main_authentication, $dolibarr_main_demo;
	global $hookmanager, $menumanager;

	$searchform = '';

	// Instantiate hooks for external modules
	$hookmanager->initHooks(array('toprightmenu'));

	$toprightmenu = '';

	// For backward compatibility with old modules
	if (empty($conf->headerdone)) {
		$disablenofollow = 0;
		top_htmlhead($head, $title, $disablejs, $disablehead, $arrayofjs, $arrayofcss, 0, $disablenofollow);
		print '<body id="mainbody">';
	}

	/*
	 * Top menu
	 */
	if ((empty($conf->dol_hide_topmenu) || GETPOSTINT('dol_invisible_topmenu')) && (!defined('NOREQUIREMENU') || !constant('NOREQUIREMENU'))) {
		if (!isset($form) || !is_object($form)) {
			include_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
			$form = new Form($db);
		}

		print "\n".'<!-- Start top horizontal -->'."\n";

		print '<header id="id-top" class="side-nav-vert'.(GETPOSTINT('dol_invisible_topmenu') ? ' hidden' : '').'">'; // dol_invisible_topmenu differs from dol_hide_topmenu: dol_invisible_topmenu means we output menu but we make it invisible.

		// Show menu entries
		print '<div id="tmenu_tooltip'.(!getDolGlobalString('MAIN_MENU_INVERT') ? '' : 'invert').'" class="tmenu">'."\n";
		$menumanager->atarget = $target;
		$menumanager->showmenu('top', array('searchform' => $searchform)); // This contains a \n
		print "</div>\n";

		// Define link to login card
		$appli = constant('DOL_APPLICATION_TITLE');
		if (getDolGlobalString('MAIN_APPLICATION_TITLE')) {
			$appli = getDolGlobalString('MAIN_APPLICATION_TITLE');
			if (preg_match('/\d\.\d/', $appli)) {
				if (!preg_match('/'.preg_quote(DOL_VERSION).'/', $appli)) {
					$appli .= " (".DOL_VERSION.")"; // If new title contains a version that is different than core
				}
			} else {
				$appli .= " ".DOL_VERSION;
			}
		} else {
			$appli .= " ".DOL_VERSION;
		}

		if (getDolGlobalInt('MAIN_FEATURES_LEVEL')) {
			$appli .= "<br>".$langs->trans("LevelOfFeature").': '.getDolGlobalInt('MAIN_FEATURES_LEVEL');
		}

		$logouttext = '';
		$logouthtmltext = '';
		if (!getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER')) {
			//$logouthtmltext=$appli.'<br>';
			$stringforfirstkey = $langs->trans("KeyboardShortcut");
			if ($conf->browser->name == 'chrome') {
				$stringforfirstkey .= ' ALT +';
			} elseif ($conf->browser->name == 'firefox') {
				$stringforfirstkey .= ' ALT + SHIFT +';
			} else {
				$stringforfirstkey .= ' CTL +';
			}
			if ($_SESSION["dol_authmode"] != 'forceuser' && $_SESSION["dol_authmode"] != 'http') {
				$logouthtmltext .= $langs->trans("Logout").'<br>';
				$logouttext .= '<a accesskey="l" href="'.DOL_URL_ROOT.'/user/logout.php?token='.newToken().'">';
				$logouttext .= img_picto($langs->trans('Logout').' ('.$stringforfirstkey.' l)', 'sign-out', '', false, 0, 0, '', 'atoplogin valignmiddle');
				$logouttext .= '</a>';
			} else {
				$logouthtmltext .= $langs->trans("NoLogoutProcessWithAuthMode", $_SESSION["dol_authmode"]);
				$logouttext .= img_picto($langs->trans('Logout').' ('.$stringforfirstkey.' l)', 'sign-out', '', false, 0, 0, '', 'atoplogin valignmiddle opacitymedium');
			}
		}

		print '<div class="login_block usedropdown">'."\n";

		$toprightmenu .= '<div class="login_block_other">';

		// Execute hook printTopRightMenu (hooks should output string like '<div class="login"><a href="">mylink</a></div>')
		$parameters = array();
		$result = $hookmanager->executeHooks('printTopRightMenu', $parameters); // Note that $action and $object may have been modified by some hooks
		if (is_numeric($result)) {
			if ($result == 0) {
				$toprightmenu .= $hookmanager->resPrint; // add
			} else {
				$toprightmenu = $hookmanager->resPrint; // replace
			}
		} else {
			$toprightmenu .= $result; // For backward compatibility
		}

		// Link to module builder
		if (isModEnabled('modulebuilder')) {
			$text = '<a href="'.DOL_URL_ROOT.'/modulebuilder/index.php?mainmenu=home&leftmenu=admintools" target="modulebuilder">';
			//$text.= img_picto(":".$langs->trans("ModuleBuilder"), 'printer_top.png', 'class="printer"');
			$text .= '<span class="fa fa-bug atoplogin valignmiddle"></span>';
			$text .= '</a>';
			// @phan-suppress-next-line PhanPluginSuspiciousParamPosition
			$toprightmenu .= $form->textwithtooltip('', $langs->trans("ModuleBuilder"), 2, 1, $text, 'login_block_elem', 2);
		}

		// Link to print main content area (optioncss=print)
		if (!getDolGlobalString('MAIN_PRINT_DISABLELINK') && !getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER')) {
			$qs = dol_escape_htmltag($_SERVER["QUERY_STRING"]);

			if (isset($_POST) && is_array($_POST)) {
				foreach ($_POST as $key => $value) {
					$key = preg_replace('/[^a-z0-9_\.\-\[\]]/i', '', $key);
					if (in_array($key, array('action', 'massaction', 'password'))) {
						continue;
					}
					if (!is_array($value)) {
						if ($value !== '') {
							$qs .= '&'.urlencode($key).'='.urlencode($value);
						}
					} else {
						foreach ($value as $value2) {
							if (($value2 !== '') && (!is_array($value2))) {
								$qs .= '&'.urlencode($key).'[]='.urlencode($value2);
							}
						}
					}
				}
			}
			$qs .= (($qs && $morequerystring) ? '&' : '').$morequerystring;
			$text = '<a href="'.dol_escape_htmltag($_SERVER["PHP_SELF"]).'?'.$qs.($qs ? '&' : '').'optioncss=print" target="_blank" rel="noopener noreferrer">';
			//$text.= img_picto(":".$langs->trans("PrintContentArea"), 'printer_top.png', 'class="printer"');
			$text .= '<span class="fa fa-print atoplogin valignmiddle"></span>';
			$text .= '</a>';
			// @phan-suppress-next-line PhanPluginSuspiciousParamPosition
			$toprightmenu .= $form->textwithtooltip('', $langs->trans("PrintContentArea"), 2, 1, $text, 'login_block_elem', 2);
		}

		// Link to Dolibarr wiki pages
		if (!getDolGlobalString('MAIN_HELP_DISABLELINK') && !getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER')) {
			$langs->load("help");

			$helpbaseurl = '';
			$helppage = '';
			$mode = '';
			$helppresent = '';

			if (empty($helppagename)) {
				$helppagename = 'EN:User_documentation|FR:Documentation_utilisateur|ES:Documentación_usuarios|DE:Benutzerdokumentation';
			} else {
				$helppresent = 'helppresent';
			}

			// Get helpbaseurl, helppage and mode from helppagename and langs
			$arrayres = getHelpParamFor($helppagename, $langs);
			$helpbaseurl = $arrayres['helpbaseurl'];
			$helppage = $arrayres['helppage'];
			$mode = $arrayres['mode'];

			// Link to help pages
			if ($helpbaseurl && $helppage) {
				$text = '';
				$title = $langs->trans($mode == 'wiki' ? 'GoToWikiHelpPage' : 'GoToHelpPage').', ';
				if ($mode == 'wiki') {
					$title .= '<br>'.img_picto('', 'globe', 'class="pictofixedwidth"').$langs->trans("PageWiki").' '.dol_escape_htmltag('"'.strtr($helppage, '_', ' ').'"');
					if ($helppresent) {
						$title .= ' <span class="opacitymedium">('.$langs->trans("DedicatedPageAvailable").')</span>';
					} else {
						$title .= ' <span class="opacitymedium">('.$langs->trans("HomePage").')</span>';
					}
				}
				$text .= '<a class="help" target="_blank" rel="noopener noreferrer" href="';
				if ($mode == 'wiki') {
					// @phan-suppress-next-line PhanPluginPrintfVariableFormatString
					$text .= sprintf($helpbaseurl, urlencode(html_entity_decode($helppage)));
				} else {
					// @phan-suppress-next-line PhanPluginPrintfVariableFormatString
					$text .= sprintf($helpbaseurl, $helppage);
				}
				$text .= '">';
				$text .= '<span class="fa fa-question-circle atoplogin valignmiddle'.($helppresent ? ' '.$helppresent : '').'"></span>';
				$text .= '<span class="fa fa-long-arrow-alt-up helppresentcircle'.($helppresent ? '' : ' unvisible').'"></span>';
				$text .= '</a>';
				// @phan-suppress-next-line PhanPluginSuspiciousParamPosition
				$toprightmenu .= $form->textwithtooltip('', $title, 2, 1, $text, 'login_block_elem', 2);
			}

			// Version
			if (getDolGlobalString('MAIN_SHOWDATABASENAMEINHELPPAGESLINK')) {
				$langs->load('admin');
				$appli .= '<br>'.$langs->trans("Database").': '.$db->database_name;
			}
		}

		if (!getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER')) {
			$text = '<span class="aversion"><span class="hideonsmartphone small">'.DOL_VERSION.'</span></span>';
			// @phan-suppress-next-line PhanPluginSuspiciousParamPosition
			$toprightmenu .= $form->textwithtooltip('', $appli, 2, 1, $text, 'login_block_elem', 2);
		}

		// Logout link
		$toprightmenu .= $form->textwithtooltip('', $logouthtmltext, 2, 1, $logouttext, 'login_block_elem logout-btn', 2);

		$toprightmenu .= '</div>'; // end div class="login_block_other"


		// Add login user link
		$toprightmenu .= '<div class="login_block_user">';

		// Login name with photo and tooltip
		$mode = -1;
		$toprightmenu .= '<div class="inline-block login_block_elem login_block_elem_name nowrap centpercent" style="padding: 0px;">';

		if (getDolGlobalString('MAIN_USE_TOP_MENU_SEARCH_DROPDOWN')) {
			// Add search dropdown
			$toprightmenu .= top_menu_search();
		}

		if (getDolGlobalString('MAIN_USE_TOP_MENU_QUICKADD_DROPDOWN')) {
			// Add search dropdown
			$toprightmenu .= top_menu_quickadd();
		}

		// Add bookmark dropdown
		$toprightmenu .= top_menu_bookmark();

		// Add user dropdown
		$toprightmenu .= top_menu_user();

		$toprightmenu .= '</div>';

		$toprightmenu .= '</div>'."\n";


		print $toprightmenu;

		print "</div>\n"; // end div class="login_block"

		print '</header>';
		//print '<header class="header2">&nbsp;</header>';

		print '<div style="clear: both;"></div>';
		print "<!-- End top horizontal menu -->\n\n";
	}

	if (empty($conf->dol_hide_leftmenu) && empty($conf->dol_use_jmobile)) {
		print '<!-- Begin div id-container --><div id="id-container" class="id-container">';
	}
}


/**
 * Build the tooltip on user login
 *
 * @param	int<0,1>	$hideloginname		Hide login name. Show only the image.
 * @param	string		$urllogout			URL for logout (Will use DOL_URL_ROOT.'/user/logout.php?token=...' if empty)
 * @return  string                  		HTML content
 */
function top_menu_user($hideloginname = 0, $urllogout = '')
{
	global $langs, $conf, $db, $hookmanager, $user, $mysoc;
	global $dolibarr_main_authentication, $dolibarr_main_demo;
	global $menumanager;

	$langs->load('companies');

	$userImage = $userDropDownImage = '';
	if (!empty($user->photo)) {
		$userImage          = Form::showphoto('userphoto', $user, 0, 0, 0, 'photouserphoto userphoto', 'small', 0, 1);
		$userDropDownImage  = Form::showphoto('userphoto', $user, 0, 0, 0, 'dropdown-user-image', 'small', 0, 1);
	} else {
		$nophoto = '/public/theme/common/user_anonymous.png';
		if ($user->gender == 'man') {
			$nophoto = '/public/theme/common/user_man.png';
		}
		if ($user->gender == 'woman') {
			$nophoto = '/public/theme/common/user_woman.png';
		}

		$userImage = '<img class="photo photouserphoto userphoto" alt="" src="'.DOL_URL_ROOT.$nophoto.'">';
		$userDropDownImage = '<img class="photo dropdown-user-image" alt="" src="'.DOL_URL_ROOT.$nophoto.'">';
	}

	$dropdownBody = '';
	$dropdownBody .= '<span id="topmenulogincompanyinfo-btn"><i class="fa fa-caret-right"></i> '.$langs->trans("ShowCompanyInfos").'</span>';
	$dropdownBody .= '<div id="topmenulogincompanyinfo" >';

	$dropdownBody .= '<br><b>'.$langs->trans("Company").'</b>: <span>'.dol_escape_htmltag($mysoc->name).'</span>';
	if ($langs->transcountry("ProfId1", $mysoc->country_code) != '-') {
		$dropdownBody .= '<br><b>'.$langs->transcountry("ProfId1", $mysoc->country_code).'</b>: <span>'.dol_print_profids(getDolGlobalString("MAIN_INFO_SIREN"), 1).'</span>';
	}
	if ($langs->transcountry("ProfId2", $mysoc->country_code) != '-') {
		$dropdownBody .= '<br><b>'.$langs->transcountry("ProfId2", $mysoc->country_code).'</b>: <span>'.dol_print_profids(getDolGlobalString("MAIN_INFO_SIRET"), 2).'</span>';
	}
	if ($langs->transcountry("ProfId3", $mysoc->country_code) != '-') {
		$dropdownBody .= '<br><b>'.$langs->transcountry("ProfId3", $mysoc->country_code).'</b>: <span>'.dol_print_profids(getDolGlobalString("MAIN_INFO_APE"), 3).'</span>';
	}
	if ($langs->transcountry("ProfId4", $mysoc->country_code) != '-') {
		$dropdownBody .= '<br><b>'.$langs->transcountry("ProfId4", $mysoc->country_code).'</b>: <span>'.dol_print_profids(getDolGlobalString("MAIN_INFO_RCS"), 4).'</span>';
	}
	if ($langs->transcountry("ProfId5", $mysoc->country_code) != '-') {
		$dropdownBody .= '<br><b>'.$langs->transcountry("ProfId5", $mysoc->country_code).'</b>: <span>'.dol_print_profids(getDolGlobalString("MAIN_INFO_PROFID5"), 5).'</span>';
	}
	if ($langs->transcountry("ProfId6", $mysoc->country_code) != '-') {
		$dropdownBody .= '<br><b>'.$langs->transcountry("ProfId6", $mysoc->country_code).'</b>: <span>'.dol_print_profids(getDolGlobalString("MAIN_INFO_PROFID6"), 6).'</span>';
	}
	$dropdownBody .= '<br><b>'.$langs->trans("VATIntraShort").'</b>: <span>'.dol_print_profids(getDolGlobalString("MAIN_INFO_TVAINTRA"), 'VAT').'</span>';
	$dropdownBody .= '<br><b>'.$langs->trans("Country").'</b>: <span>'.($mysoc->country_code ? $langs->trans("Country".$mysoc->country_code) : '').'</span>';
	if (isModEnabled('multicurrency')) {
		$dropdownBody .= '<br><b>'.$langs->trans("Currency").'</b>: <span>'.$conf->currency.'</span>';
	}
	$dropdownBody .= '</div>';

	$dropdownBody .= '<br>';
	$dropdownBody .= '<span id="topmenuloginmoreinfo-btn"><i class="fa fa-caret-right"></i> '.$langs->trans("ShowMoreInfos").'</span>';
	$dropdownBody .= '<div id="topmenuloginmoreinfo" >';

	// login infos
	if (!empty($user->admin)) {
		$dropdownBody .= '<br><b>'.$langs->trans("Administrator").'</b>: '.yn($user->admin);
	}
	if (!empty($user->socid)) {	// Add thirdparty for external users
		$thirdpartystatic = new Societe($db);
		$thirdpartystatic->fetch($user->socid);
		$companylink = ' '.$thirdpartystatic->getNomUrl(2); // picto only of company
		$company = ' ('.$langs->trans("Company").': '.$thirdpartystatic->name.')';
	}
	$type = ($user->socid ? $langs->trans("External").$company : $langs->trans("Internal"));
	$dropdownBody .= '<br><b>'.$langs->trans("Type").':</b> '.$type;
	$dropdownBody .= '<br><b>'.$langs->trans("Status").'</b>: '.$user->getLibStatut(0);
	$dropdownBody .= '<br>';

	$dropdownBody .= '<br><u>'.$langs->trans("Session").'</u>';
	$dropdownBody .= '<br><b>'.$langs->trans("IPAddress").'</b>: '.dol_escape_htmltag($_SERVER["REMOTE_ADDR"]);
	if (getDolGlobalString('MAIN_MODULE_MULTICOMPANY')) {
		$dropdownBody .= '<br><b>'.$langs->trans("ConnectedOnMultiCompany").':</b> '.$conf->entity.' (user entity '.$user->entity.')';
	}
	$dropdownBody .= '<br><b>'.$langs->trans("AuthenticationMode").':</b> '.$_SESSION["dol_authmode"].(empty($dolibarr_main_demo) ? '' : ' (demo)');
	$dropdownBody .= '<br><b>'.$langs->trans("ConnectedSince").':</b> '.dol_print_date($user->datelastlogin, "dayhour", 'tzuser');
	$dropdownBody .= '<br><b>'.$langs->trans("PreviousConnexion").':</b> '.dol_print_date($user->datepreviouslogin, "dayhour", 'tzuser');
	$dropdownBody .= '<br><b>'.$langs->trans("CurrentTheme").':</b> '.$conf->theme;
	$dropdownBody .= '<br><b>'.$langs->trans("CurrentMenuManager").':</b> '.(isset($menumanager) ? $menumanager->name : 'unknown');
	$langFlag = picto_from_langcode($langs->getDefaultLang());
	$dropdownBody .= '<br><b>'.$langs->trans("CurrentUserLanguage").':</b> '.($langFlag ? $langFlag.' ' : '').$langs->getDefaultLang();

	$tz = (int) $_SESSION['dol_tz'] + (int) $_SESSION['dol_dst'];
	$dropdownBody .= '<br><b>'.$langs->trans("ClientTZ").':</b> '.($tz ? ($tz >= 0 ? '+' : '').$tz : '');
	$dropdownBody .= ' ('.$_SESSION['dol_tz_string'].')';
	//$dropdownBody .= ' &nbsp; &nbsp; &nbsp; '.$langs->trans("DaylingSavingTime").': ';
	//if ($_SESSION['dol_dst'] > 0) $dropdownBody .= yn(1);
	//else $dropdownBody .= yn(0);

	$dropdownBody .= '<br><b>'.$langs->trans("Browser").':</b> '.$conf->browser->name.($conf->browser->version ? ' '.$conf->browser->version : '').' <small class="opacitymedium">('.dol_escape_htmltag($_SERVER['HTTP_USER_AGENT']).')</small>';
	$dropdownBody .= '<br><b>'.$langs->trans("Layout").':</b> '.$conf->browser->layout;
	$dropdownBody .= '<br><b>'.$langs->trans("Screen").':</b> '.$_SESSION['dol_screenwidth'].' x '.$_SESSION['dol_screenheight'];
	if ($conf->browser->layout == 'phone') {
		$dropdownBody .= '<br><b>'.$langs->trans("Phone").':</b> '.$langs->trans("Yes");
	}
	if (!empty($_SESSION["disablemodules"])) {
		$dropdownBody .= '<br><b>'.$langs->trans("DisabledModules").':</b> <br>'.implode(', ', explode(',', $_SESSION["disablemodules"]));
	}
	$dropdownBody .= '</div>';

	// Execute hook
	$parameters = array('user' => $user, 'langs' => $langs);
	$result = $hookmanager->executeHooks('printTopRightMenuLoginDropdownBody', $parameters); // Note that $action and $object may have been modified by some hooks
	if (is_numeric($result)) {
		if ($result == 0) {
			$dropdownBody .= $hookmanager->resPrint; // add
		} else {
			$dropdownBody = $hookmanager->resPrint; // replace
		}
	}

	if (empty($urllogout)) {
		$urllogout = DOL_URL_ROOT.'/user/logout.php?token='.newToken();
	}

	// accesskey is for Windows or Linux:  ALT + key for chrome, ALT + SHIFT + KEY for firefox
	// accesskey is for Mac:               CTRL + key for all browsers
	$stringforfirstkey = $langs->trans("KeyboardShortcut");
	if ($conf->browser->name == 'chrome') {
		$stringforfirstkey .= ' ALT +';
	} elseif ($conf->browser->name == 'firefox') {
		$stringforfirstkey .= ' ALT + SHIFT +';
	} else {
		$stringforfirstkey .= ' CTL +';
	}

	// Defined the links for bottom of card
	$profilLink = '<a accesskey="u" href="'.DOL_URL_ROOT.'/user/card.php?id='.$user->id.'" class="button-top-menu-dropdown" title="'.dol_escape_htmltag($langs->trans("YourUserFile").' ('.$stringforfirstkey.' u)').'"><i class="fa fa-user"></i>  '.$langs->trans("Card").'</a>';
	$urltovirtualcard = '/user/virtualcard.php?id='.((int) $user->id);
	$virtuelcardLink = dolButtonToOpenUrlInDialogPopup('publicvirtualcardmenu', $langs->transnoentitiesnoconv("PublicVirtualCardUrl").(is_object($user) ? ' - '.$user->getFullName($langs) : '').' ('.$stringforfirstkey.' v)', img_picto($langs->trans("PublicVirtualCardUrl").' ('.$stringforfirstkey.' v)', 'card', ''), $urltovirtualcard, '', 'button-top-menu-dropdown marginleftonly nohover', "closeTopMenuLoginDropdown()", '', 'v');
	$logoutLink = '<a accesskey="l" href="'.$urllogout.'" class="button-top-menu-dropdown" title="'.dol_escape_htmltag($langs->trans("Logout").' ('.$stringforfirstkey.' l)').'"><i class="fa fa-sign-out-alt padingright"></i><span class="hideonsmartphone">'.$langs->trans("Logout").'</span></a>';

	$profilName = $user->getFullName($langs).' ('.$user->login.')';
	if (!empty($user->admin)) {
		$profilName = '<i class="far fa-star classfortooltip" title="'.$langs->trans("Administrator").'" ></i> '.$profilName;
	}

	// Define version to show
	$appli = constant('DOL_APPLICATION_TITLE');
	if (getDolGlobalString('MAIN_APPLICATION_TITLE')) {
		$appli = getDolGlobalString('MAIN_APPLICATION_TITLE');
		if (preg_match('/\d\.\d/', $appli)) {
			if (!preg_match('/'.preg_quote(DOL_VERSION).'/', $appli)) {
				$appli .= " (".DOL_VERSION.")"; // If new title contains a version that is different than core
			}
		} else {
			$appli .= " ".DOL_VERSION;
		}
	} else {
		$appli .= " ".DOL_VERSION;
	}

	if (!getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER')) {
		$btnUser = '<!-- div for user link -->
	    <div id="topmenu-login-dropdown" class="userimg atoplogin dropdown user user-menu inline-block">
	        <a href="'.DOL_URL_ROOT.'/user/card.php?id='.$user->id.'" class="dropdown-toggle login-dropdown-a valignmiddle" data-toggle="dropdown">
	            '.$userImage.(empty($user->photo) ? '<!-- no photo so show also the login --><span class="hidden-xs maxwidth200 atoploginusername hideonsmartphone paddingleft valignmiddle small">'.dol_trunc($user->firstname ? $user->firstname : $user->login, 10).'</span>' : '').'
	        </a>
	        <div class="dropdown-menu">
	            <!-- User image -->
	            <div class="user-header">
	                '.$userDropDownImage.'
	                <p>
	                    '.$profilName.'<br>';
		if ($user->datelastlogin) {
			$title = $langs->trans("ConnectedSince").' : '.dol_print_date($user->datelastlogin, "dayhour", 'tzuser');
			if ($user->datepreviouslogin) {
				$title .= '<br>'.$langs->trans("PreviousConnexion").' : '.dol_print_date($user->datepreviouslogin, "dayhour", 'tzuser');
			}
		}
		$btnUser .= '<small class="classfortooltip" title="'.dol_escape_htmltag($title).'" ><i class="fa fa-user-clock"></i> '.dol_print_date($user->datelastlogin, "dayhour", 'tzuser').'</small><br>';
		if ($user->datepreviouslogin) {
			$btnUser .= '<small class="classfortooltip" title="'.dol_escape_htmltag($title).'" ><i class="fa fa-user-clock opacitymedium"></i> '.dol_print_date($user->datepreviouslogin, "dayhour", 'tzuser').'</small><br>';
		}

		//$btnUser .= '<small class="classfortooltip"><i class="fa fa-cog"></i> '.$langs->trans("Version").' '.$appli.'</small>';
		$btnUser .= '
	                </p>
	            </div>

	            <!-- Menu Body user-->
	            <div class="user-body">'.$dropdownBody.'</div>

	            <!-- Menu Footer-->
	            <div class="user-footer">
	                <div class="pull-left">
	                    '.$profilLink.'
	                </div>
	                <div class="pull-left">
	                    '.$virtuelcardLink.'
	                </div>
	                <div class="pull-right">
	                    '.$logoutLink.'
	                </div>
	                <div class="clearboth"></div>
	            </div>

	        </div>
	    </div>';
	} else {
		$btnUser = '<!-- div for user link text browser -->
	    <div id="topmenu-login-dropdown" class="userimg atoplogin dropdown user user-menu inline-block">
	    	<a href="'.DOL_URL_ROOT.'/user/card.php?id='.$user->id.'" class="valignmiddle" alt="'.$langs->trans("MyUserCard").'">
	    	'.$userImage.(empty($user->photo) ? '<span class="hidden-xs maxwidth200 atoploginusername hideonsmartphone paddingleft small valignmiddle">'.dol_trunc($user->firstname ? $user->firstname : $user->login, 10).'</span>' : '').'
	    	</a>
		</div>';
	}

	if (!defined('JS_JQUERY_DISABLE_DROPDOWN') && !empty($conf->use_javascript_ajax)) {    // This may be set by some pages that use different jquery version to avoid errors
		$btnUser .= '
        <!-- Code to show/hide the user drop-down -->
        <script>
		function closeTopMenuLoginDropdown() {
			//console.log("close login dropdown");	// This is call at each click on page, so we disable the log
			// Hide the menus.
            jQuery("#topmenu-login-dropdown").removeClass("open");
		}
        jQuery(document).ready(function() {
            jQuery(document).on("click", function(event) {
				// console.log("Click somewhere on screen");
                if (!$(event.target).closest("#topmenu-login-dropdown").length) {
					closeTopMenuLoginDropdown();
                }
            });
		';


		//if ($conf->theme != 'md') {
		$btnUser .= '
	            jQuery("#topmenu-login-dropdown .dropdown-toggle").on("click", function(event) {
					console.log("Click on #topmenu-login-dropdown .dropdown-toggle");
					event.preventDefault();
	                jQuery("#topmenu-login-dropdown").toggleClass("open");
	            });

	            jQuery("#topmenulogincompanyinfo-btn").on("click", function() {
					console.log("Click on #topmenulogincompanyinfo-btn");
	                jQuery("#topmenulogincompanyinfo").slideToggle();
	            });

	            jQuery("#topmenuloginmoreinfo-btn").on("click", function() {
					console.log("Click on #topmenuloginmoreinfo-btn");
	                jQuery("#topmenuloginmoreinfo").slideToggle();
	            });';
		//}

		$btnUser .= '
        });
        </script>
        ';
	}

	return $btnUser;
}

/**
 * Build the tooltip on top menu quick add
 *
 * @return  string                  HTML content
 */
function top_menu_quickadd()
{
	global $conf, $langs;

	// Button disabled on text browser
	if (getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER')) {
		return '';
	}

	$html = '';

	// accesskey is for Windows or Linux:  ALT + key for chrome, ALT + SHIFT + KEY for firefox
	// accesskey is for Mac:               CTRL + key for all browsers
	$stringforfirstkey = $langs->trans("KeyboardShortcut");
	if ($conf->browser->os === 'macintosh') {
		$stringforfirstkey .= ' CTL +';
	} else {
		if ($conf->browser->name == 'chrome') {
			$stringforfirstkey .= ' ALT +';
		} elseif ($conf->browser->name == 'firefox') {
			$stringforfirstkey .= ' ALT + SHIFT +';
		} else {
			$stringforfirstkey .= ' CTL +';
		}
	}

	if (!empty($conf->use_javascript_ajax)) {
		$html .= '<!-- div for quick add link -->
    <div id="topmenu-quickadd-dropdown" class="atoplogin dropdown inline-block">
        <a accesskey="a" class="dropdown-toggle login-dropdown-a nofocusvisible" data-toggle="dropdown" href="#" title="'.$langs->trans('QuickAdd').' ('.$stringforfirstkey.' a)"><i class="fa fa-plus-circle"></i></a>
        <div class="dropdown-menu">'.printDropdownQuickadd().'</div>
    </div>';
		if (!defined('JS_JQUERY_DISABLE_DROPDOWN')) {    // This may be set by some pages that use different jquery version to avoid errors
			$html .= '
        <!-- Code to show/hide the user drop-down for the quick add -->
        <script>
        jQuery(document).ready(function() {
            jQuery(document).on("click", function(event) {
                if (!$(event.target).closest("#topmenu-quickadd-dropdown").length) {
                    // Hide the menus.
                    $("#topmenu-quickadd-dropdown").removeClass("open");
                }
            });
            $("#topmenu-quickadd-dropdown .dropdown-toggle").on("click", function(event) {
				console.log("Click on #topmenu-quickadd-dropdown .dropdown-toggle");
                openQuickAddDropDown(event);
            });

            // Key map shortcut
            $(document).keydown(function(event){
				var ostype = \''.dol_escape_js($conf->browser->os).'\';
				if (ostype === "macintosh") {
					if ( event.which === 65 && event.ctrlKey ) {
						console.log(\'control + a : trigger open quick add dropdown\');
						openQuickAddDropDown(event);
					}
				} else {
					if ( event.which === 65 && event.ctrlKey && event.shiftKey ) {
						console.log(\'control + shift + a : trigger open quick add dropdown\');
						openQuickAddDropDown(event);
					}
				}
            });

            var openQuickAddDropDown = function(event) {
                event.preventDefault();
                $("#topmenu-quickadd-dropdown").toggleClass("open");
                //$("#top-quickadd-search-input").focus();
            }
        });
        </script>
        ';
		}
	}

	return $html;
}

/**
 * Generate list of quickadd items
 *
 * @return string HTML output
 */
function printDropdownQuickadd()
{
	global $user, $langs, $hookmanager;

	$items = array(
		'items' => array(
			array(
				"url" => "/adherents/card.php?action=create&amp;mainmenu=members",
				"title" => "MenuNewMember@members",
				"name" => "Adherent@members",
				"picto" => "object_member",
				"activation" => isModEnabled('member') && $user->hasRight("adherent", "write"), // vs hooking
				"position" => 5,
			),
			array(
				"url" => "/societe/card.php?action=create&amp;mainmenu=companies",
				"title" => "MenuNewThirdParty@companies",
				"name" => "ThirdParty@companies",
				"picto" => "object_company",
				"activation" => isModEnabled("societe") && $user->hasRight("societe", "write"), // vs hooking
				"position" => 10,
			),
			array(
				"url" => "/contact/card.php?action=create&amp;mainmenu=companies",
				"title" => "NewContactAddress@companies",
				"name" => "Contact@companies",
				"picto" => "object_contact",
				"activation" => isModEnabled("societe") && $user->hasRight("societe", "contact", "write"), // vs hooking
				"position" => 20,
			),
			array(
				"url" => "/comm/propal/card.php?action=create&amp;mainmenu=commercial",
				"title" => "NewPropal@propal",
				"name" => "Proposal@propal",
				"picto" => "object_propal",
				"activation" => isModEnabled("propal") && $user->hasRight("propal", "write"), // vs hooking
				"position" => 30,
			),

			array(
				"url" => "/commande/card.php?action=create&amp;mainmenu=commercial",
				"title" => "NewOrder@orders",
				"name" => "Order@orders",
				"picto" => "object_order",
				"activation" => isModEnabled('order') && $user->hasRight("commande", "write"), // vs hooking
				"position" => 40,
			),
			array(
				"url" => "/compta/facture/card.php?action=create&amp;mainmenu=billing",
				"title" => "NewBill@bills",
				"name" => "Bill@bills",
				"picto" => "object_bill",
				"activation" => isModEnabled('invoice') && $user->hasRight("facture", "write"), // vs hooking
				"position" => 50,
			),
			array(
				"url" => "/contrat/card.php?action=create&amp;mainmenu=commercial",
				"title" => "NewContractSubscription@contracts",
				"name" => "Contract@contracts",
				"picto" => "object_contract",
				"activation" => isModEnabled('contract') && $user->hasRight("contrat", "write"), // vs hooking
				"position" => 60,
			),
			array(
				"url" => "/supplier_proposal/card.php?action=create&amp;mainmenu=commercial",
				"title" => "SupplierProposalNew@supplier_proposal",
				"name" => "SupplierProposal@supplier_proposal",
				"picto" => "supplier_proposal",
				"activation" => isModEnabled('supplier_proposal') && $user->hasRight("supplier_invoice", "write"), // vs hooking
				"position" => 70,
			),
			array(
				"url" => "/fourn/commande/card.php?action=create&amp;mainmenu=commercial",
				"title" => "NewSupplierOrderShort@orders",
				"name" => "SupplierOrder@orders",
				"picto" => "supplier_order",
				"activation" => (isModEnabled("fournisseur") && !getDolGlobalString('MAIN_USE_NEW_SUPPLIERMOD') && $user->hasRight("fournisseur", "commande", "write")) || (isModEnabled("supplier_order") && $user->hasRight("supplier_invoice", "write")), // vs hooking
				"position" => 80,
			),
			array(
				"url" => "/fourn/facture/card.php?action=create&amp;mainmenu=billing",
				"title" => "NewBill@bills",
				"name" => "SupplierBill@bills",
				"picto" => "supplier_invoice",
				"activation" => (isModEnabled("fournisseur") && !getDolGlobalString('MAIN_USE_NEW_SUPPLIERMOD') && $user->hasRight("fournisseur", "facture", "write")) || (isModEnabled("supplier_invoice") && $user->hasRight("supplier_invoice", "write")), // vs hooking
				"position" => 90,
			),
			array(
				"url" => "/ticket/card.php?action=create&amp;mainmenu=ticket",
				"title" => "NewTicket@ticket",
				"name" => "Ticket@ticket",
				"picto" => "ticket",
				"activation" => isModEnabled('ticket') && $user->hasRight("ticket", "write"), // vs hooking
				"position" => 100,
			),
			array(
				"url" => "/fichinter/card.php?action=create&mainmenu=commercial",
				"title" => "NewIntervention@interventions",
				"name" => "Intervention@interventions",
				"picto" => "intervention",
				"activation" => isModEnabled('intervention') && $user->hasRight("ficheinter", "creer"), // vs hooking
				"position" => 110,
			),
			array(
				"url" => "/product/card.php?action=create&amp;type=0&amp;mainmenu=products",
				"title" => "NewProduct@products",
				"name" => "Product@products",
				"picto" => "object_product",
				"activation" => isModEnabled("product") && $user->hasRight("produit", "write"), // vs hooking
				"position" => 400,
			),
			array(
				"url" => "/product/card.php?action=create&amp;type=1&amp;mainmenu=products",
				"title" => "NewService@products",
				"name" => "Service@products",
				"picto" => "object_service",
				"activation" => isModEnabled("service") && $user->hasRight("service", "write"), // vs hooking
				"position" => 410,
			),
			array(
				"url" => "/user/card.php?action=create&amp;type=1&amp;mainmenu=home",
				"title" => "AddUser@users",
				"name" => "User@users",
				"picto" => "user",
				"activation" => $user->hasRight("user", "user", "write"), // vs hooking
				"position" => 500,
			),
		),
	);

	$dropDownQuickAddHtml = '';

	// Define $dropDownQuickAddHtml
	$dropDownQuickAddHtml .= '<div class="quickadd-body dropdown-body">';
	$dropDownQuickAddHtml .= '<div class="dropdown-quickadd-list">';

	// Allow the $items of the menu to be manipulated by modules
	$parameters = array();
	$hook_items = $items;
	$reshook = $hookmanager->executeHooks('menuDropdownQuickaddItems', $parameters, $hook_items); // Note that $action and $object may have been modified by some hooks
	if (is_numeric($reshook) && !empty($hookmanager->resArray) && is_array($hookmanager->resArray)) {
		if ($reshook == 0) {
			$items['items'] = array_merge($items['items'], $hookmanager->resArray); // add
		} else {
			$items = $hookmanager->resArray; // replace
		}

		// Sort menu items by 'position' value
		$position = array();
		foreach ($items['items'] as $key => $row) {
			$position[$key] = $row['position'];
		}
		$array1_sort_order = SORT_ASC;
		array_multisort($position, $array1_sort_order, $items['items']);
	}

	foreach ($items['items'] as $item) {
		if (!$item['activation']) {
			continue;
		}
		$langs->load(explode('@', $item['title'])[1]);
		$langs->load(explode('@', $item['name'])[1]);
		$dropDownQuickAddHtml .= '
			<a class="dropdown-item quickadd-item" href="'.DOL_URL_ROOT.$item['url'].'" title="'.$langs->trans(explode('@', $item['title'])[0]).'">
			'. img_picto('', $item['picto'], 'style="width:18px;"') . ' ' . $langs->trans(explode('@', $item['name'])[0]) . '</a>
		';
	}

	$dropDownQuickAddHtml .= '</div>';
	$dropDownQuickAddHtml .= '</div>';

	return $dropDownQuickAddHtml;
}

/**
 * Build the tooltip on top menu bookmark
 *
 * @return  string                  HTML content
 */
function top_menu_bookmark()
{
	global $langs, $conf, $db, $user;

	$html = '';

	// Define $bookmarks
	if (!isModEnabled('bookmark') || !$user->hasRight('bookmark', 'lire')) {
		return $html;
	}

	// accesskey is for Windows or Linux:  ALT + key for chrome, ALT + SHIFT + KEY for firefox
	// accesskey is for Mac:               CTRL + key for all browsers
	$stringforfirstkey = $langs->trans("KeyboardShortcut");
	if ($conf->browser->os === 'macintosh') {
		$stringforfirstkey .= ' CTL +';
	} else {
		if ($conf->browser->name == 'chrome') {
			$stringforfirstkey .= ' ALT +';
		} elseif ($conf->browser->name == 'firefox') {
			$stringforfirstkey .= ' ALT + SHIFT +';
		} else {
			$stringforfirstkey .= ' CTL +';
		}
	}

	if (!defined('JS_JQUERY_DISABLE_DROPDOWN') && !empty($conf->use_javascript_ajax)) {	    // This may be set by some pages that use different jquery version to avoid errors
		include_once DOL_DOCUMENT_ROOT.'/bookmarks/bookmarks.lib.php';
		$langs->load("bookmarks");

		if (getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER')) {
			$html .= '<div id="topmenu-bookmark-dropdown" class="dropdown inline-block">';
			$html .= printDropdownBookmarksList();
			$html .= '</div>';
		} else {
			$html .= '<!-- div for bookmark link -->
	        <div id="topmenu-bookmark-dropdown" class="dropdown inline-block">
	            <a accesskey="b" class="dropdown-toggle login-dropdown-a nofocusvisible" data-toggle="dropdown" href="#" title="'.$langs->trans('Bookmarks').' ('.$stringforfirstkey.' b)"><i class="fa fa-star"></i></a>
	            <div class="dropdown-menu">
	                '.printDropdownBookmarksList().'
	            </div>
	        </div>';

			$html .= '
	        <!-- Code to show/hide the bookmark drop-down -->
	        <script>
	        jQuery(document).ready(function() {
	            jQuery(document).on("click", function(event) {
	                if (!$(event.target).closest("#topmenu-bookmark-dropdown").length) {
						//console.log("close bookmark dropdown - we click outside");
	                    // Hide the menus.
	                    $("#topmenu-bookmark-dropdown").removeClass("open");
	                }
	            });

	            jQuery("#topmenu-bookmark-dropdown .dropdown-toggle").on("click", function(event) {
					console.log("Click on #topmenu-bookmark-dropdown .dropdown-toggle");
					openBookMarkDropDown(event);
	            });

	            // Key map shortcut
	            jQuery(document).keydown(function(event) {
					var ostype = \''.dol_escape_js($conf->browser->os).'\';
					if (ostype === "macintosh") {
						if ( event.which === 66 && event.ctrlKey ) {
							console.log("Click on control + b : trigger open bookmark dropdown");
							openBookMarkDropDown(event);
						}
					} else {
						if ( event.which === 66 && event.ctrlKey && event.shiftKey ) {
							console.log("Click on control + shift + b : trigger open bookmark dropdown");
							openBookMarkDropDown(event);
						}
					}
	            });

	            var openBookMarkDropDown = function(event) {
	                event.preventDefault();
	                jQuery("#topmenu-bookmark-dropdown").toggleClass("open");
	                jQuery("#top-bookmark-search-input").focus();
	            }

	        });
	        </script>
	        ';
		}
	}
	return $html;
}

/**
 * Build the tooltip on top menu tsearch
 *
 * @return  string                  HTML content
 */
function top_menu_search()
{
	global $langs, $conf, $db, $user, $hookmanager;

	$html = '';

	$usedbyinclude = 1;
	$arrayresult = array();
	include DOL_DOCUMENT_ROOT.'/core/ajax/selectsearchbox.php'; // This sets $arrayresult

	// accesskey is for Windows or Linux:  ALT + key for chrome, ALT + SHIFT + KEY for firefox
	// accesskey is for Mac:               CTRL + key for all browsers
	$stringforfirstkey = $langs->trans("KeyboardShortcut");
	if ($conf->browser->name == 'chrome') {
		$stringforfirstkey .= ' ALT +';
	} elseif ($conf->browser->name == 'firefox') {
		$stringforfirstkey .= ' ALT + SHIFT +';
	} else {
		$stringforfirstkey .= ' CTL +';
	}

	$searchInput = '<input type="search" name="search_all"'.($stringforfirstkey ? ' title="'.dol_escape_htmltag($stringforfirstkey.' s').'"' : '').' id="top-global-search-input" class="dropdown-search-input search_component_input" placeholder="'.$langs->trans('Search').'" autocomplete="off">';

	$defaultAction = '';
	$buttonList = '<div class="dropdown-global-search-button-list" >';
	// Menu with all searchable items
	foreach ($arrayresult as $keyItem => $item) {
		if (empty($defaultAction)) {
			$defaultAction = $item['url'];
		}
		$buttonList .= '<button class="dropdown-item global-search-item tdoverflowmax300" data-target="'.dol_escape_htmltag($item['url']).'" >';
		$buttonList .= $item['text'];
		$buttonList .= '</button>';
	}
	$buttonList .= '</div>';

	$dropDownHtml = '<form role="search" id="top-menu-action-search" name="actionsearch" method="GET" action="'.$defaultAction.'">';

	$dropDownHtml .= '
        <!-- search input -->
        <div class="dropdown-header search-dropdown-header">
            ' . $searchInput.'
        </div>
    ';

	$dropDownHtml .= '
        <!-- Menu Body search -->
        <div class="dropdown-body search-dropdown-body">
        '.$buttonList.'
        </div>
        ';

	$dropDownHtml .= '</form>';

	// accesskey is for Windows or Linux:  ALT + key for chrome, ALT + SHIFT + KEY for firefox
	// accesskey is for Mac:               CTRL + key for all browsers
	$stringforfirstkey = $langs->trans("KeyboardShortcut");
	if ($conf->browser->name == 'chrome') {
		$stringforfirstkey .= ' ALT +';
	} elseif ($conf->browser->name == 'firefox') {
		$stringforfirstkey .= ' ALT + SHIFT +';
	} else {
		$stringforfirstkey .= ' CTL +';
	}

	$html .= '<!-- div for Global Search -->
    <div id="topmenu-global-search-dropdown" class="atoplogin dropdown inline-block">
        <a accesskey="s" class="dropdown-toggle login-dropdown-a nofocusvisible" data-toggle="dropdown" href="#" title="'.$langs->trans('Search').' ('.$stringforfirstkey.' s)">
            <i class="fa fa-search" aria-hidden="true" ></i>
        </a>
        <div class="dropdown-menu dropdown-search">
            '.$dropDownHtml.'
        </div>
    </div>';

	$html .= '
    <!-- Code to show/hide the user drop-down -->
    <script>
    jQuery(document).ready(function() {

        // prevent submitting form on press ENTER
        jQuery("#top-global-search-input").keydown(function (e) {
            if (e.keyCode == 13 || e.keyCode == 40) {
                var inputs = $(this).parents("form").eq(0).find(":button");
                if (inputs[inputs.index(this) + 1] != null) {
                    inputs[inputs.index(this) + 1].focus();
					 if (e.keyCode == 13){
						 inputs[inputs.index(this) + 1].trigger("click");
					 }

                }
                e.preventDefault();
                return false;
            }
        });

        // arrow key nav
        jQuery(document).keydown(function(e) {
			// Get the focused element:
			var $focused = $(":focus");
			if($focused.length && $focused.hasClass("global-search-item")){

           		// UP - move to the previous line
				if (e.keyCode == 38) {
				    e.preventDefault();
					$focused.prev().focus();
				}

				// DOWN - move to the next line
				if (e.keyCode == 40) {
				    e.preventDefault();
					$focused.next().focus();
				}
			}
        });


        // submit form action
        jQuery(".dropdown-global-search-button-list .global-search-item").on("click", function(event) {
            jQuery("#top-menu-action-search").attr("action", $(this).data("target"));
            jQuery("#top-menu-action-search").submit();
        });

        // close drop down
        jQuery(document).on("click", function(event) {
			if (!$(event.target).closest("#topmenu-global-search-dropdown").length) {
				console.log("click close search - we click outside");
                // Hide the menus.
                jQuery("#topmenu-global-search-dropdown").removeClass("open");
            }
        });

        // Open drop down
        jQuery("#topmenu-global-search-dropdown .dropdown-toggle").on("click", function(event) {
			console.log("click on toggle #topmenu-global-search-dropdown .dropdown-toggle");
            openGlobalSearchDropDown();
        });

        // Key map shortcut
        jQuery(document).keydown(function(e){
              if ( e.which === 70 && e.ctrlKey && e.shiftKey ) {
                 console.log(\'control + shift + f : trigger open global-search dropdown\');
                 openGlobalSearchDropDown();
              }
              if ( e.which === 70 && e.alKey ) {
                 console.log(\'alt + f : trigger open global-search dropdown\');
                 openGlobalSearchDropDown();
              }
        });

        var openGlobalSearchDropDown = function() {
            jQuery("#topmenu-global-search-dropdown").toggleClass("open");
            jQuery("#top-global-search-input").focus();
        }

    });
    </script>
    ';

	return $html;
}

/**
 *  Show left menu bar
 *
 *  @param  string		$menu_array_before 	       	Table of menu entries to show before entries of menu handler. This param is deprecated and must be provided to ''.
 *  @param  string		$helppagename    	       	Name of wiki page for help ('' by default).
 *                                                  Syntax is: For a wiki page: EN:EnglishPage|FR:FrenchPage|ES:SpanishPage|DE:GermanPage
 *                                                  For other external page: http://server/url
 *  @param  string		$notused             		Deprecated. Used in past to add content into left menu. Hooks can be used now.
 *  @param  array		$menu_array_after           Table of menu entries to show after entries of menu handler
 *  @param  int			$leftmenuwithoutmainarea    Must be set to 1. 0 by default for backward compatibility with old modules.
 *  @param  string		$title                      Title of web page
 *  @param  int<0,1>	$acceptdelayedhtml          1 if caller request to have html delayed content not returned but saved into global $delayedhtmlcontent (so caller can show it at end of page to avoid flash FOUC effect)
 *  @return	void
 */
function left_menu($menu_array_before, $helppagename = '', $notused = '', $menu_array_after = array(), $leftmenuwithoutmainarea = 0, $title = '', $acceptdelayedhtml = 0)
{
	global $user, $conf, $langs, $db, $form;
	global $hookmanager, $menumanager;

	$searchform = '';

	if (!empty($menu_array_before)) {
		dol_syslog("Deprecated parameter menu_array_before was used when calling main::left_menu function. Menu entries of module should now be defined into module descriptor and not provided when calling left_menu.", LOG_WARNING);
	}

	if (empty($conf->dol_hide_leftmenu) && (!defined('NOREQUIREMENU') || !constant('NOREQUIREMENU'))) {
		// Instantiate hooks for external modules
		$hookmanager->initHooks(array('leftblock'));

		print "\n".'<!-- Begin side-nav id-left -->'."\n".'<div class="side-nav"><div id="id-left">'."\n";
		print "\n";

		if (!is_object($form)) {
			$form = new Form($db);
		}
		$selected = -1;
		if (!getDolGlobalString('MAIN_USE_TOP_MENU_SEARCH_DROPDOWN')) {
			// Select with select2 is awful on smartphone. TODO Is this still true with select2 v4 ?
			if ($conf->browser->layout == 'phone') {
				$conf->global->MAIN_USE_OLD_SEARCH_FORM = 1;
			}

			$usedbyinclude = 1;
			$arrayresult = array();
			include DOL_DOCUMENT_ROOT.'/core/ajax/selectsearchbox.php'; // This make initHooks('searchform') then set $arrayresult

			if ($conf->use_javascript_ajax && !getDolGlobalString('MAIN_USE_OLD_SEARCH_FORM')) {
				// accesskey is for Windows or Linux:  ALT + key for chrome, ALT + SHIFT + KEY for firefox
				// accesskey is for Mac:               CTRL + key for all browsers
				$stringforfirstkey = $langs->trans("KeyboardShortcut");
				if ($conf->browser->name == 'chrome') {
					$stringforfirstkey .= ' ALT +';
				} elseif ($conf->browser->name == 'firefox') {
					$stringforfirstkey .= ' ALT + SHIFT +';
				} else {
					$stringforfirstkey .= ' CTL +';
				}

				//$textsearch = $langs->trans("Search");
				$textsearch = '<span class="fa fa-search paddingright pictofixedwidth"></span>'.$langs->trans("Search");
				$searchform .= $form->selectArrayFilter('searchselectcombo', $arrayresult, $selected, 'accesskey="s"', 1, 0, (!getDolGlobalString('MAIN_SEARCHBOX_CONTENT_LOADED_BEFORE_KEY') ? 1 : 0), 'vmenusearchselectcombo', 1, $textsearch, 1, $stringforfirstkey.' s');
			} else {
				if (is_array($arrayresult)) {
					foreach ($arrayresult as $key => $val) {
						$searchform .= printSearchForm($val['url'], $val['url'], $val['label'], 'maxwidth125', 'search_all', (empty($val['shortcut']) ? '' : $val['shortcut']), 'searchleft'.$key, $val['img']);
					}
				}
			}

			// Execute hook printSearchForm
			$parameters = array('searchform' => $searchform);
			$reshook = $hookmanager->executeHooks('printSearchForm', $parameters); // Note that $action and $object may have been modified by some hooks
			if (empty($reshook)) {
				$searchform .= $hookmanager->resPrint;
			} else {
				$searchform = $hookmanager->resPrint;
			}

			// Force special value for $searchform for text browsers or very old search form
			if (getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER') || empty($conf->use_javascript_ajax)) {
				$urltosearch = DOL_URL_ROOT.'/core/search_page.php?showtitlebefore=1';
				$searchform = '<div class="blockvmenuimpair blockvmenusearchphone"><div id="divsearchforms1"><a href="'.$urltosearch.'" accesskey="s" alt="'.dol_escape_htmltag($langs->trans("ShowSearchFields")).'">'.$langs->trans("Search").'...</a></div></div>';
			} elseif ($conf->use_javascript_ajax && getDolGlobalString('MAIN_USE_OLD_SEARCH_FORM')) {
				$searchform = '<div class="blockvmenuimpair blockvmenusearchphone"><div id="divsearchforms1"><a href="#" alt="'.dol_escape_htmltag($langs->trans("ShowSearchFields")).'">'.$langs->trans("Search").'...</a></div><div id="divsearchforms2" style="display: none">'.$searchform.'</div>';
				$searchform .= '<script>
            	jQuery(document).ready(function () {
            		jQuery("#divsearchforms1").click(function(){
	                   jQuery("#divsearchforms2").toggle();
	               });
            	});
                </script>' . "\n";
				$searchform .= '</div>';
			}

			// Key map shortcut
			$searchform .= '<script>
				jQuery(document).keydown(function(e){
					if( e.which === 70 && e.ctrlKey && e.shiftKey ){
						console.log(\'control + shift + f : trigger open global-search dropdown\');
		                openGlobalSearchDropDown();
		            }
		            if( (e.which === 83 || e.which === 115) && e.altKey ){
		                console.log(\'alt + s : trigger open global-search dropdown\');
		                openGlobalSearchDropDown();
		            }
		        });

		        var openGlobalSearchDropDown = function() {
		            jQuery("#searchselectcombo").select2(\'open\');
		        }
			</script>';
		}

		// Left column
		print '<!-- Begin left menu -->'."\n";

		print '<div class="vmenu"'.(getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER') ? ' alt="Left menu"' : '').'>'."\n\n";

		// Show left menu with other forms
		$menumanager->menu_array = $menu_array_before;
		$menumanager->menu_array_after = $menu_array_after;
		$menumanager->showmenu('left', array('searchform' => $searchform)); // output menu_array and menu found in database

		// Dolibarr version + help + bug report link
		print "\n";
		print "<!-- Begin Help Block-->\n";
		print '<div id="blockvmenuhelp" class="blockvmenuhelp">'."\n";

		// Version
		if (getDolGlobalString('MAIN_SHOW_VERSION')) {    // Version is already on help picto and on login page.
			$doliurl = 'https://www.dolibarr.org';
			//local communities
			if (preg_match('/fr/i', $langs->defaultlang)) {
				$doliurl = 'https://www.dolibarr.fr';
			}
			if (preg_match('/es/i', $langs->defaultlang)) {
				$doliurl = 'https://www.dolibarr.es';
			}
			if (preg_match('/de/i', $langs->defaultlang)) {
				$doliurl = 'https://www.dolibarr.de';
			}
			if (preg_match('/it/i', $langs->defaultlang)) {
				$doliurl = 'https://www.dolibarr.it';
			}
			if (preg_match('/gr/i', $langs->defaultlang)) {
				$doliurl = 'https://www.dolibarr.gr';
			}

			$appli = constant('DOL_APPLICATION_TITLE');
			if (getDolGlobalString('MAIN_APPLICATION_TITLE')) {
				$appli = getDolGlobalString('MAIN_APPLICATION_TITLE');
				$doliurl = '';
				if (preg_match('/\d\.\d/', $appli)) {
					if (!preg_match('/'.preg_quote(DOL_VERSION).'/', $appli)) {
						$appli .= " (".DOL_VERSION.")"; // If new title contains a version that is different than core
					}
				} else {
					$appli .= " ".DOL_VERSION;
				}
			} else {
				$appli .= " ".DOL_VERSION;
			}
			print '<div id="blockvmenuhelpapp" class="blockvmenuhelp">';
			if ($doliurl) {
				print '<a class="help" target="_blank" rel="noopener noreferrer" href="'.$doliurl.'">';
			} else {
				print '<span class="help">';
			}
			print $appli;
			if ($doliurl) {
				print '</a>';
			} else {
				print '</span>';
			}
			print '</div>'."\n";
		}

		// Link to bugtrack
		if (getDolGlobalString('MAIN_BUGTRACK_ENABLELINK')) {
			require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

			if (getDolGlobalString('MAIN_BUGTRACK_ENABLELINK') == 'github') {
				$bugbaseurl = 'https://github.com/Dolibarr/dolibarr/issues/new?labels=Bug';
				$bugbaseurl .= '&title=';
				$bugbaseurl .= urlencode("Bug: ");
				$bugbaseurl .= '&body=';
				$bugbaseurl .= urlencode("# Instructions\n");
				$bugbaseurl .= urlencode("*This is a template to help you report good issues. You may use [Github Markdown](https://help.github.com/articles/getting-started-with-writing-and-formatting-on-github/) syntax to format your issue report.*\n");
				$bugbaseurl .= urlencode("*Please:*\n");
				$bugbaseurl .= urlencode("- *replace the bracket enclosed texts with meaningful information*\n");
				$bugbaseurl .= urlencode("- *remove any unused sub-section*\n");
				$bugbaseurl .= urlencode("\n");
				$bugbaseurl .= urlencode("\n");
				$bugbaseurl .= urlencode("# Bug\n");
				$bugbaseurl .= urlencode("[*Short description*]\n");
				$bugbaseurl .= urlencode("\n");
				$bugbaseurl .= urlencode("## Environment\n");
				$bugbaseurl .= urlencode("- **Version**: ".DOL_VERSION."\n");
				$bugbaseurl .= urlencode("- **OS**: ".php_uname('s')."\n");
				$bugbaseurl .= urlencode("- **Web server**: ".$_SERVER["SERVER_SOFTWARE"]."\n");
				$bugbaseurl .= urlencode("- **PHP**: ".php_sapi_name().' '.phpversion()."\n");
				$bugbaseurl .= urlencode("- **Database**: ".$db::LABEL.' '.$db->getVersion()."\n");
				$bugbaseurl .= urlencode("- **URL(s)**: ".$_SERVER["REQUEST_URI"]."\n");
				$bugbaseurl .= urlencode("\n");
				$bugbaseurl .= urlencode("## Expected and actual behavior\n");
				$bugbaseurl .= urlencode("[*Verbose description*]\n");
				$bugbaseurl .= urlencode("\n");
				$bugbaseurl .= urlencode("## Steps to reproduce the behavior\n");
				$bugbaseurl .= urlencode("[*Verbose description*]\n");
				$bugbaseurl .= urlencode("\n");
				$bugbaseurl .= urlencode("## [Attached files](https://help.github.com/articles/issue-attachments) (Screenshots, screencasts, dolibarr.log, debugging information…)\n");
				$bugbaseurl .= urlencode("[*Files*]\n");
				$bugbaseurl .= urlencode("\n");

				$bugbaseurl .= urlencode("\n");
				$bugbaseurl .= urlencode("## Report\n");
			} elseif (getDolGlobalString('MAIN_BUGTRACK_ENABLELINK')) {
				$bugbaseurl = getDolGlobalString('MAIN_BUGTRACK_ENABLELINK');
			} else {
				$bugbaseurl = "";
			}

			// Execute hook printBugtrackInfo
			$parameters = array('bugbaseurl' => $bugbaseurl);
			$reshook = $hookmanager->executeHooks('printBugtrackInfo', $parameters); // Note that $action and $object may have been modified by some hooks
			if (empty($reshook)) {
				$bugbaseurl .= $hookmanager->resPrint;
			} else {
				$bugbaseurl = $hookmanager->resPrint;
			}

			print '<div id="blockvmenuhelpbugreport" class="blockvmenuhelp">';
			print '<a class="help" target="_blank" rel="noopener noreferrer" href="'.$bugbaseurl.'"><i class="fas fa-bug"></i> '.$langs->trans("FindBug").'</a>';
			print '</div>';
		}

		print "</div>\n";
		print "<!-- End Help Block-->\n";
		print "\n";

		print "</div>\n";
		print "<!-- End left menu -->\n";
		print "\n";

		// Execute hook printLeftBlock
		$parameters = array();
		$reshook = $hookmanager->executeHooks('printLeftBlock', $parameters); // Note that $action and $object may have been modified by some hooks
		print $hookmanager->resPrint;

		print '</div></div> <!-- End side-nav id-left -->'; // End div id="side-nav" div id="id-left"
	}

	print "\n";
	print '<!-- Begin right area -->'."\n";

	if (empty($leftmenuwithoutmainarea)) {
		main_area($title);
	}
}


/**
 *  Begin main area
 *
 *  @param	string	$title		Title
 *  @return	void
 */
function main_area($title = '')
{
	global $conf, $langs, $hookmanager;

	if (empty($conf->dol_hide_leftmenu) && !GETPOST('dol_openinpopup')) {
		print '<div id="id-right">';
	}

	print "\n";

	print '<!-- Begin div class="fiche" -->'."\n".'<div class="fiche">'."\n";

	$hookmanager->initHooks(array('main'));
	$parameters = array();
	$reshook = $hookmanager->executeHooks('printMainArea', $parameters); // Note that $action and $object may have been modified by some hooks
	print $hookmanager->resPrint;

	if (getDolGlobalString('MAIN_ONLY_LOGIN_ALLOWED')) {
		print info_admin($langs->trans("WarningYouAreInMaintenanceMode", getDolGlobalString('MAIN_ONLY_LOGIN_ALLOWED')), 0, 0, 1, 'warning maintenancemode');
	}

	// Permit to add user company information on each printed document by setting SHOW_SOCINFO_ON_PRINT
	if (getDolGlobalString('SHOW_SOCINFO_ON_PRINT') && GETPOST('optioncss', 'aZ09') == 'print' && empty(GETPOST('disable_show_socinfo_on_print', 'aZ09'))) {
		$parameters = array();
		$reshook = $hookmanager->executeHooks('showSocinfoOnPrint', $parameters);
		if (empty($reshook)) {
			print '<!-- Begin show mysoc info header -->'."\n";
			print '<div id="mysoc-info-header">'."\n";
			print '<table class="centpercent div-table-responsive">'."\n";
			print '<tbody>';
			print '<tr><td rowspan="0" class="width20p">';
			if (getDolGlobalString('MAIN_SHOW_LOGO') && !getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER') && getDolGlobalString('MAIN_INFO_SOCIETE_LOGO')) {
				print '<img id="mysoc-info-header-logo" style="max-width:100%" alt="" src="'.DOL_URL_ROOT.'/viewimage.php?cache=1&modulepart=mycompany&file='.urlencode('logos/'.dol_escape_htmltag(getDolGlobalString('MAIN_INFO_SOCIETE_LOGO'))).'">';
			}
			print '</td><td  rowspan="0" class="width50p"></td></tr>'."\n";
			print '<tr><td class="titre bold">'.dol_escape_htmltag(getDolGlobalString('MAIN_INFO_SOCIETE_NOM')).'</td></tr>'."\n";
			print '<tr><td>'.dol_escape_htmltag(getDolGlobalString('MAIN_INFO_SOCIETE_ADDRESS')).'<br>'.dol_escape_htmltag(getDolGlobalString('MAIN_INFO_SOCIETE_ZIP')).' '.dol_escape_htmltag(getDolGlobalString('MAIN_INFO_SOCIETE_TOWN')).'</td></tr>'."\n";
			if (getDolGlobalString('MAIN_INFO_SOCIETE_TEL')) {
				print '<tr><td style="padding-left: 1em" class="small">'.$langs->trans("Phone").' : '.dol_escape_htmltag(getDolGlobalString('MAIN_INFO_SOCIETE_TEL')).'</td></tr>';
			}
			if (getDolGlobalString('MAIN_INFO_SOCIETE_MAIL')) {
				print '<tr><td style="padding-left: 1em" class="small">'.$langs->trans("Email").' : '.dol_escape_htmltag(getDolGlobalString('MAIN_INFO_SOCIETE_MAIL')).'</td></tr>';
			}
			if (getDolGlobalString('MAIN_INFO_SOCIETE_WEB')) {
				print '<tr><td style="padding-left: 1em" class="small">'.$langs->trans("Web").' : '.dol_escape_htmltag(getDolGlobalString('MAIN_INFO_SOCIETE_WEB')).'</td></tr>';
			}
			print '</tbody>';
			print '</table>'."\n";
			print '</div>'."\n";
			print '<!-- End show mysoc info header -->'."\n";
		}
	}
}


/**
 *  Return helpbaseurl, helppage and mode
 *
 *  @param	string		$helppagename		Page name ('EN:xxx,ES:eee,FR:fff,DE:ddd...' or 'http://localpage')
 *  @param  Translate	$langs				Language
 *  @return	array{helpbaseurl:string,helppage:string,mode:string}	Array of help urls
 */
function getHelpParamFor($helppagename, $langs)
{
	$helpbaseurl = '';
	$helppage = '';
	$mode = '';

	if (preg_match('/^http/i', $helppagename)) {
		// If complete URL
		$helpbaseurl = '%s';
		$helppage = $helppagename;
		$mode = 'local';
	} else {
		// If WIKI URL
		$reg = array();
		if (preg_match('/^es/i', $langs->defaultlang)) {
			$helpbaseurl = 'http://wiki.dolibarr.org/index.php/%s';
			if (preg_match('/ES:([^|]+)/i', $helppagename, $reg)) {
				$helppage = $reg[1];
			}
		}
		if (preg_match('/^fr/i', $langs->defaultlang)) {
			$helpbaseurl = 'http://wiki.dolibarr.org/index.php/%s';
			if (preg_match('/FR:([^|]+)/i', $helppagename, $reg)) {
				$helppage = $reg[1];
			}
		}
		if (preg_match('/^de/i', $langs->defaultlang)) {
			$helpbaseurl = 'http://wiki.dolibarr.org/index.php/%s';
			if (preg_match('/DE:([^|]+)/i', $helppagename, $reg)) {
				$helppage = $reg[1];
			}
		}
		if (empty($helppage)) {	// If help page not already found
			$helpbaseurl = 'http://wiki.dolibarr.org/index.php/%s';
			if (preg_match('/EN:([^|]+)/i', $helppagename, $reg)) {
				$helppage = $reg[1];
			}
		}
		$mode = 'wiki';
	}
	return array('helpbaseurl' => $helpbaseurl, 'helppage' => $helppage, 'mode' => $mode);
}


/**
 *  Show a search area.
 *  Used when the javascript quick search is not used.
 *
 *  @param  string	$urlaction          Url post
 *  @param  string	$urlobject          Url of the link under the search box
 *  @param  string	$title              Title search area
 *  @param  string	$htmlmorecss        Add more css
 *  @param  string	$htmlinputname      Field Name input form
 *  @param	string	$accesskey			Accesskey
 *  @param  string  $prefhtmlinputname  Complement for id to avoid multiple same id in the page
 *  @param	string	$img				Image to use
 *  @param	int		$showtitlebefore	Show title before input text instead of into placeholder. This can be set when output is dedicated for text browsers.
 *  @param	int		$autofocus			Set autofocus on field
 *  @return	string
 */
function printSearchForm($urlaction, $urlobject, $title, $htmlmorecss, $htmlinputname, $accesskey = '', $prefhtmlinputname = '', $img = '', $showtitlebefore = 0, $autofocus = 0)
{
	global $langs, $user;

	$ret = '';
	$ret .= '<form action="'.$urlaction.'" method="post" class="searchform nowraponall tagtr">';
	$ret .= '<input type="hidden" name="token" value="'.newToken().'">';
	$ret .= '<input type="hidden" name="savelogin" value="'.dol_escape_htmltag($user->login).'">';
	if ($showtitlebefore) {
		$ret .= '<div class="tagtd left">'.$title.'</div> ';
	}
	$ret .= '<div class="tagtd">';
	$ret .= img_picto('', $img, '', false, 0, 0, '', 'paddingright width20');
	$ret .= '<input type="text" class="flat '.$htmlmorecss.'"';
	$ret .= ' style="background-repeat: no-repeat; background-position: 3px;"';
	$ret .= ($accesskey ? ' accesskey="'.$accesskey.'"' : '');
	$ret .= ' placeholder="'.strip_tags($title).'"';
	$ret .= ($autofocus ? ' autofocus' : '');
	$ret .= ' name="'.$htmlinputname.'" id="'.$prefhtmlinputname.$htmlinputname.'" />';
	$ret .= '<button type="submit" class="button bordertransp" style="padding-top: 4px; padding-bottom: 4px; padding-left: 6px; padding-right: 6px">';
	$ret .= '<span class="fa fa-search"></span>';
	$ret .= '</button>';
	$ret .= '</div>';
	$ret .= "</form>\n";
	return $ret;
}


if (!function_exists("llxFooter")) {
	/**
	 * Show HTML footer
	 * Close div /DIV class=fiche + /DIV id-right + /DIV id-container + /BODY + /HTML.
	 * If global var $delayedhtmlcontent was filled, we output it just before closing the body.
	 *
	 * @param	string	$comment    				A text to add as HTML comment into HTML generated page
	 * @param	string	$zone						'private' (for private pages) or 'public' (for public pages)
	 * @param	int		$disabledoutputofmessages	Clear all messages stored into session without displaying them
	 * @return	void
	 */
	function llxFooter($comment = '', $zone = 'private', $disabledoutputofmessages = 0)
	{
		global $conf, $db, $langs, $user, $mysoc, $object, $hookmanager, $action;
		global $delayedhtmlcontent;
		global $contextpage, $page, $limit, $mode;
		global $dolibarr_distrib;

		$ext = 'layout='.urlencode($conf->browser->layout).'&version='.urlencode(DOL_VERSION);

		// Hook to add more things on all pages within fiche DIV
		$llxfooter = '';
		$parameters = array();
		$reshook = $hookmanager->executeHooks('llxFooter', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		if (empty($reshook)) {
			$llxfooter .= $hookmanager->resPrint;
		} elseif ($reshook > 0) {
			$llxfooter = $hookmanager->resPrint;
		}
		if ($llxfooter) {
			print $llxfooter;
		}

		// Global html output events ($mesgs, $errors, $warnings)
		dol_htmloutput_events($disabledoutputofmessages);

		// Code for search criteria persistence.
		// $user->lastsearch_values was set by the GETPOST when form field search_xxx exists
		if (is_object($user) && !empty($user->lastsearch_values_tmp) && is_array($user->lastsearch_values_tmp)) {
			// Clean and save data
			foreach ($user->lastsearch_values_tmp as $key => $val) {
				unset($_SESSION['lastsearch_values_tmp_'.$key]); // Clean array to rebuild it just after
				if (count($val) && empty($_POST['button_removefilter']) && empty($_POST['button_removefilter_x'])) {
					if (empty($val['sortfield'])) {
						unset($val['sortfield']);
					}
					if (empty($val['sortorder'])) {
						unset($val['sortorder']);
					}
					dol_syslog('Save lastsearch_values_tmp_'.$key.'='.json_encode($val, 0)." (systematic recording of last search criteria)");
					$_SESSION['lastsearch_values_tmp_'.$key] = json_encode($val);
					unset($_SESSION['lastsearch_values_'.$key]);
				}
			}
		}


		$relativepathstring = $_SERVER["PHP_SELF"];
		// Clean $relativepathstring
		if (constant('DOL_URL_ROOT')) {
			$relativepathstring = preg_replace('/^'.preg_quote(constant('DOL_URL_ROOT'), '/').'/', '', $relativepathstring);
		}
		$relativepathstring = preg_replace('/^\//', '', $relativepathstring);
		$relativepathstring = preg_replace('/^custom\//', '', $relativepathstring);
		if (preg_match('/list\.php$/', $relativepathstring)) {
			unset($_SESSION['lastsearch_contextpage_tmp_'.$relativepathstring]);
			unset($_SESSION['lastsearch_page_tmp_'.$relativepathstring]);
			unset($_SESSION['lastsearch_limit_tmp_'.$relativepathstring]);
			unset($_SESSION['lastsearch_mode_tmp_'.$relativepathstring]);

			if (!empty($contextpage)) {
				$_SESSION['lastsearch_contextpage_tmp_'.$relativepathstring] = $contextpage;
			}
			if (!empty($page) && $page > 0) {
				$_SESSION['lastsearch_page_tmp_'.$relativepathstring] = $page;
			}
			if (!empty($limit) && $limit != $conf->liste_limit) {
				$_SESSION['lastsearch_limit_tmp_'.$relativepathstring] = $limit;
			}
			if (!empty($mode)) {
				$_SESSION['lastsearch_mode_tmp_'.$relativepathstring] = $mode;
			}

			unset($_SESSION['lastsearch_contextpage_'.$relativepathstring]);
			unset($_SESSION['lastsearch_page_'.$relativepathstring]);
			unset($_SESSION['lastsearch_limit_'.$relativepathstring]);
			unset($_SESSION['lastsearch_mode_'.$relativepathstring]);
		}

		// Core error message
		if (getDolGlobalString('MAIN_CORE_ERROR')) {
			// Ajax version
			if ($conf->use_javascript_ajax) {
				$title = img_warning().' '.$langs->trans('CoreErrorTitle');
				print ajax_dialog($title, $langs->trans('CoreErrorMessage'));
			} else {
				// html version
				$msg = img_warning().' '.$langs->trans('CoreErrorMessage');
				print '<div class="error">'.$msg.'</div>';
			}

			//define("MAIN_CORE_ERROR",0);      // Constant was defined and we can't change value of a constant
		}

		print "\n\n";

		print '</div> <!-- End div class="fiche" -->'."\n"; // End div fiche

		if (empty($conf->dol_hide_leftmenu) && !GETPOST('dol_openinpopup')) {
			print '</div> <!-- End div id-right -->'."\n"; // End div id-right
		}

		if (empty($conf->dol_hide_leftmenu) && empty($conf->dol_use_jmobile)) {
			print '</div> <!-- End div id-container -->'."\n"; // End div container
		}

		print "\n";
		if ($comment) {
			print '<!-- '.$comment.' -->'."\n";
		}

		printCommonFooter($zone);

		if (!empty($delayedhtmlcontent)) {
			print $delayedhtmlcontent;
		}

		if (!empty($conf->use_javascript_ajax)) {
			print "\n".'<!-- Includes JS Footer of Dolibarr -->'."\n";
			print '<script src="'.DOL_URL_ROOT.'/core/js/lib_foot.js.php?lang='.$langs->defaultlang.($ext ? '&'.$ext : '').'"></script>'."\n";
		}

		// Wrapper to add log when clicking on download or preview
		if (isModEnabled('blockedlog') && is_object($object) && !empty($object->id) && $object->id > 0) {
			if (in_array($object->element, array('facture')) && $object->statut > 0) {       // Restrict for the moment to element 'facture'
				print "\n<!-- JS CODE TO ENABLE log when making a download or a preview of a document -->\n";
				?>
				<script>
				jQuery(document).ready(function () {
					$('a.documentpreview').click(function() {
						console.log("Call /blockedlog/ajax/block-add on a.documentpreview");
						$.post('<?php echo DOL_URL_ROOT."/blockedlog/ajax/block-add.php" ?>'
								, {
									id:<?php echo $object->id; ?>
									, element:'<?php echo dol_escape_js($object->element) ?>'
									, action:'DOC_PREVIEW'
									, token: '<?php echo currentToken(); ?>'
								}
						);
					});
					$('a.documentdownload').click(function() {
						console.log("Call /blockedlog/ajax/block-add a.documentdownload");
						$.post('<?php echo DOL_URL_ROOT."/blockedlog/ajax/block-add.php" ?>'
								, {
									id:<?php echo $object->id; ?>
									, element:'<?php echo dol_escape_js($object->element) ?>'
									, action:'DOC_DOWNLOAD'
									, token: '<?php echo currentToken(); ?>'
								}
						);
					});
				});
				</script>
				<?php
			}
		}

		// A div for the address popup
		print "\n<!-- A div to allow dialog popup by jQuery('#dialogforpopup').dialog() -->\n";
		print '<div id="dialogforpopup" style="display: none;"></div>'."\n";

		// Add code for the asynchronous anonymous first ping (for telemetry)
		// You can use &forceping=1 in parameters to force the ping if the ping was already sent.
		$forceping = GETPOST('forceping', 'alpha');
		if (($_SERVER["PHP_SELF"] == DOL_URL_ROOT.'/index.php') || $forceping) {
			//print '<!-- instance_unique_id='.$conf->file->instance_unique_id.' MAIN_FIRST_PING_OK_ID='.$conf->global->MAIN_FIRST_PING_OK_ID.' -->';
			$hash_unique_id = dol_hash('dolibarr'.$conf->file->instance_unique_id, 'sha256');	// Note: if the global salt changes, this hash changes too so ping may be counted twice. We don't mind. It is for statistics purpose only.

			if (!getDolGlobalString('MAIN_FIRST_PING_OK_DATE')
				|| (!empty($conf->file->instance_unique_id) && ($hash_unique_id != $conf->global->MAIN_FIRST_PING_OK_ID) && (getDolGlobalString('MAIN_FIRST_PING_OK_ID') != 'disabled'))
			|| $forceping) {
				// No ping done if we are into an alpha version
				if (strpos('alpha', DOL_VERSION) > 0 && !$forceping) {
					print "\n<!-- NO JS CODE TO ENABLE the anonymous Ping. It is an alpha version -->\n";
				} elseif (empty($_COOKIE['DOLINSTALLNOPING_'.$hash_unique_id]) || $forceping) {	// Cookie is set when we uncheck the checkbox in the installation wizard.
					// MAIN_LAST_PING_KO_DATE
					// Disable ping if MAIN_LAST_PING_KO_DATE is set and is recent (this month)
					if (getDolGlobalString('MAIN_LAST_PING_KO_DATE') && substr($conf->global->MAIN_LAST_PING_KO_DATE, 0, 6) == dol_print_date(dol_now(), '%Y%m') && !$forceping) {
						print "\n<!-- NO JS CODE TO ENABLE the anonymous Ping. An error already occurred this month, we will try later. -->\n";
					} else {
						include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

						print "\n".'<!-- Includes JS for Ping of Dolibarr forceping='.$forceping.' MAIN_FIRST_PING_OK_DATE='.getDolGlobalString("MAIN_FIRST_PING_OK_DATE").' MAIN_FIRST_PING_OK_ID='.getDolGlobalString("MAIN_FIRST_PING_OK_ID").' MAIN_LAST_PING_KO_DATE='.getDolGlobalString("MAIN_LAST_PING_KO_DATE").' -->'."\n";
						print "\n<!-- JS CODE TO ENABLE the anonymous Ping -->\n";
						$url_for_ping = getDolGlobalString('MAIN_URL_FOR_PING', "https://ping.dolibarr.org/");
						// Try to guess the distrib used
						$distrib = 'standard';
						if ($_SERVER["SERVER_ADMIN"] == 'doliwamp@localhost') {
							$distrib = 'doliwamp';
						}
						if (!empty($dolibarr_distrib)) {
							$distrib = $dolibarr_distrib;
						}
						?>
							<script>
							jQuery(document).ready(function (tmp) {
								console.log("Try Ping with hash_unique_id is dol_hash('dolibarr'+instance_unique_id, 'sha256')");
								$.ajax({
									  method: "POST",
									  url: "<?php echo $url_for_ping ?>",
									  timeout: 500,     // timeout milliseconds
									  cache: false,
									  data: {
										  hash_algo: 'dol_hash-sha256',
										  hash_unique_id: '<?php echo dol_escape_js($hash_unique_id); ?>',
										  action: 'dolibarrping',
										  version: '<?php echo (float) DOL_VERSION; ?>',
										  entity: '<?php echo (int) $conf->entity; ?>',
										  dbtype: '<?php echo dol_escape_js($db->type); ?>',
										  country_code: '<?php echo $mysoc->country_code ? dol_escape_js($mysoc->country_code) : 'unknown'; ?>',
										  php_version: '<?php echo dol_escape_js(phpversion()); ?>',
										  os_version: '<?php echo dol_escape_js(version_os('smr')); ?>',
										  db_version: '<?php echo dol_escape_js(version_db()); ?>',
										  distrib: '<?php echo $distrib ? dol_escape_js($distrib) : 'unknown'; ?>',
										  token: 'notrequired'
									  },
									  success: function (data, status, xhr) {   // success callback function (data contains body of response)
											console.log("Ping ok");
											$.ajax({
												method: 'GET',
												url: '<?php echo DOL_URL_ROOT.'/core/ajax/pingresult.php'; ?>',
												timeout: 500,     // timeout milliseconds
												cache: false,
												data: { hash_algo: 'dol_hash-sha256', hash_unique_id: '<?php echo dol_escape_js($hash_unique_id); ?>', action: 'firstpingok', token: '<?php echo currentToken(); ?>' },	// for update
											  });
									  },
									  error: function (data,status,xhr) {   // error callback function
											console.log("Ping ko: " + data);
											$.ajax({
												  method: 'GET',
												  url: '<?php echo DOL_URL_ROOT.'/core/ajax/pingresult.php'; ?>',
												  timeout: 500,     // timeout milliseconds
												  cache: false,
												  data: { hash_algo: 'dol_hash-sha256', hash_unique_id: '<?php echo dol_escape_js($hash_unique_id); ?>', action: 'firstpingko', token: '<?php echo currentToken(); ?>' },
												});
									  }
								});
							});
							</script>
						<?php
					}
				} else {
					$now = dol_now();
					print "\n<!-- NO JS CODE TO ENABLE the anonymous Ping. It was disabled -->\n";
					include_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
					dolibarr_set_const($db, 'MAIN_FIRST_PING_OK_DATE', dol_print_date($now, 'dayhourlog', 'gmt'), 'chaine', 0, '', $conf->entity);
					dolibarr_set_const($db, 'MAIN_FIRST_PING_OK_ID', 'disabled', 'chaine', 0, '', $conf->entity);
				}
			}
		}

		$parameters = array();
		$reshook = $hookmanager->executeHooks('beforeBodyClose', $parameters); // Note that $action and $object may have been modified by some hooks
		if ($reshook > 0) {
			print $hookmanager->resPrint;
		}

		print "</body>\n";
		print "</html>\n";
	}
}
