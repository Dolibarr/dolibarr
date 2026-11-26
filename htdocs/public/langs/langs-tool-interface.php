<?php
/*
 * Copyright (C) ---Replace with your own copyright and developer email---
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

//if (! defined('NOREQUIREDB'))              define('NOREQUIREDB', '1');				// Do not create database handler $db
if (! defined('NOREQUIREUSER'))            define('NOREQUIREUSER', '1');				// Do not load object $user
if (! defined('NOREQUIRESOC'))             define('NOREQUIRESOC', '1');				// Do not load object $mysoc
//if (! defined('NOREQUIRETRAN'))            define('NOREQUIRETRAN', '1');				// Do not load object $langs
//if (! defined('NOSCANGETFORINJECTION'))    define('NOSCANGETFORINJECTION', '1');		// Do not check injection attack on GET parameters
//if (! defined('NOSCANPOSTFORINJECTION'))   define('NOSCANPOSTFORINJECTION', '1');		// Do not check injection attack on POST parameters
if (! defined('NOTOKENRENEWAL'))           define('NOTOKENRENEWAL', '1');				// Do not roll the Anti CSRF token (used if MAIN_SECURITY_CSRF_WITH_TOKEN is on)
//if (! defined('NOSTYLECHECK'))             define('NOSTYLECHECK', '1');				// Do not check style html tag into posted data
if (! defined('NOREQUIREMENU'))            define('NOREQUIREMENU', '1');				// If there is no need to load and show top and left menu
//if (! defined('NOREQUIREHTML'))            define('NOREQUIREHTML', '1');				// If we don't need to load the html.form.class.php
//if (! defined('NOREQUIREAJAX'))            define('NOREQUIREAJAX', '1');       	  	// Do not load ajax.lib.php library
if (! defined("NOLOGIN"))                  define("NOLOGIN", '1');					// If this page is public (can be called outside logged session). This include the NOIPCHECK too.
//if (! defined('NOIPCHECK'))                define('NOIPCHECK', '1');					// Do not check IP defined into conf $dolibarr_main_restrict_ip
//if (! defined("MAIN_LANG_DEFAULT"))        define('MAIN_LANG_DEFAULT', 'auto');					// Force lang to a particular value
//if (! defined("MAIN_AUTHENTICATION_MODE")) define('MAIN_AUTHENTICATION_MODE', 'aloginmodule');	// Force authentication handler
//if (! defined('CSRFCHECK_WITH_TOKEN'))     define('CSRFCHECK_WITH_TOKEN', '1');		// Force use of CSRF protection with tokens even for GET
//if (! defined('NOBROWSERNOTIF'))     		 define('NOBROWSERNOTIF', '1');				// Disable browser notification


// Load Dolibarr environment
require '../../main.inc.php';

// Nothing is written into the session: release its lock at once, the JS langs tool calls this page in background
// while other requests of the same user may be running.
if (session_status() === PHP_SESSION_ACTIVE) {
	session_write_close();
}


/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */


/**
 * INTERFACE FOR JS CONTEXT LANG
 */

// The browser may keep the answer but must check it each time (ETag below): translations of a module change
// with its files, without any change of the Dolibarr version, and a max-age kept new keys hidden up to 24 h.
header('Cache-Control: no-cache, public');
header('Content-Type: application/json; charset=utf-8');


$local = GETPOST('local');
if (empty($local)) {
	$local = $langs->getDefaultLang();
}

$domain = GETPOST('domain');
if (empty($domain)) {
	echo json_encode(['error' => 'Missing domain']);
	exit;
}

if (!preg_match('/^[A-Za-z0-9_-]+(?:@[A-Za-z0-9_-]+)?$/', $domain)) {
	echo json_encode(['error' => 'Invalid domain']);
	exit;
}


if (!preg_match('/^[a-z]{1,2}_[A-Z]{1,2}(?:,[a-z]{1,2}_[A-Z]{1,2})*$/', $local)) {
	echo json_encode(['error' => 'Invalid langs codes']);
	exit;
}

$locals = explode(',', $local);

/*
 * ETag built from what the translations come from, without reading the lang files: same tag as the copy of the
 * browser, answer 304 without body. A file read for nothing only costs an extra download, so the list of files
 * may be wider than what Translate::load() really reads, never narrower.
 */
$newdomain = $domain;
$modulename = '';
if (preg_match('/^([^@]+)@([^@]+)$/', $domain, $regs)) {
	$newdomain = $regs[1];
	$modulename = $regs[2];
}
$fingerprint = array(DOL_VERSION, $domain, $local, getDolGlobalString('MAIN_FORCELANGDIR'));
foreach ($locals as $langCode) {
	// Same fallback chain as Translate::load(): xx_YY, then xx_XX, then en_US
	$langarray = explode('_', $langCode);
	$parentlang = strtolower($langarray[0]).'_'.strtoupper($langarray[0]);
	if ($parentlang == 'el_EL') {
		$parentlang = 'el_GR';
	}
	if ($parentlang == 'ar_AR') {
		$parentlang = 'ar_SA';
	}
	$langdirs = array_unique(array($langCode, $parentlang, 'en_US'));

	$outputlangs = new Translate("", $conf);
	$outputlangs->setDefaultLang($langCode);
	foreach ($outputlangs->dir as $searchdir) {
		foreach ($langdirs as $langdir) {
			$file_lang = $searchdir.($modulename ? '/'.$modulename : '').'/langs/'.$langdir.'/'.$newdomain.'.lang';
			$stat = @stat(dol_osencode($file_lang));
			if ($stat) {
				$fingerprint[] = $file_lang.':'.$stat['mtime'].':'.$stat['size'];
			}
		}
	}
	$fingerprint[] = getDolGlobalString('MAIN_OVERWRITE_TRANS_'.$langCode);
}
// Translations overwritten in database (Setup > Translation), no date column so the rows are part of the tag
if (getDolGlobalString('MAIN_ENABLE_OVERWRITE_TRANSLATION')) {
	$sql = "SELECT lang, transkey, transvalue FROM ".$db->prefix()."overwrite_trans";
	$sql .= " WHERE entity IN (0, ".getEntity('overwrite_trans').")";
	$sql .= " ORDER BY rowid";
	$resql = $db->query($sql);
	if ($resql) {
		while ($obj = $db->fetch_object($resql)) {
			$fingerprint[] = $obj->lang.':'.$obj->transkey.':'.$obj->transvalue;
		}
		$db->free($resql);
	} else {
		$fingerprint[] = uniqid(); // No reliable tag: never answer 304
	}
}

$etag = '"'.md5(implode("\n", $fingerprint)).'"';
header('ETag: '.$etag);
// Apache mod_deflate may send it back as W/"...-gzip"
$sentTags = array();
foreach (explode(',', isset($_SERVER['HTTP_IF_NONE_MATCH']) ? (string) $_SERVER['HTTP_IF_NONE_MATCH'] : '') as $tag) {
	$sentTags[] = preg_replace('/-(gzip|br|deflate)"$/', '"', trim(str_replace('W/', '', $tag)));
}
if (in_array($etag, $sentTags, true)) {
	http_response_code(304);
	exit;
}

/*
Format for  JS:
{
  fr_FR : {KEY:"TEXT", ...},
  en_US : {KEY:"TEXT", ...}
}
*/

$json = new stdClass();

foreach ($locals as $langCode) {
	$json->$langCode = [];
	$outputlangs = new Translate("", $conf);
	$outputlangs->setDefaultLang($langCode);
	$outputlangs->load($domain);
	foreach ($outputlangs->tab_translate as $k => $v) {
		$json->$langCode[$k] = dolPrintHTML($v); // to escape js and other stuff
	}
}

print json_encode($json, JSON_PRETTY_PRINT);
