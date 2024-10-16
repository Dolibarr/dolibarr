<?php
/* Copyright (C) 2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2017 ATM Consulting       <contact@atm-consulting.fr>
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
 *      \file       htdocs/blockedlog/ajax/check_signature.php
 *      \ingroup    blockedlog
 *      \brief      This page is not used yet.
 */


// This script is called with a POST method.
// Directory to scan (full path) is inside POST['dir'].

if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', 1); // Disables token renewal
}
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', '1');
}
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', '1');
}


// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/geturl.lib.php';
require_once DOL_DOCUMENT_ROOT.'/blockedlog/class/blockedlog.class.php';
require_once DOL_DOCUMENT_ROOT.'/blockedlog/class/authority.class.php';


if (!getDolGlobalString('BLOCKEDLOG_AUTHORITY_URL')) {
	exit('BLOCKEDLOG_AUTHORITY_URL not set');
}


/*
 * View
 */

top_httphead();

$auth = new BlockedLogAuthority($db);
$auth->syncSignatureWithAuthority();

$block_static = new BlockedLog($db);

$blocks = $block_static->getLog('just_certified', 0, 0, 'rowid', 'ASC');

$auth->signature = $block_static->getSignature();

if (is_array($blocks)) {
	foreach ($blocks as &$b) {
		$auth->blockchain .= $b->signature;
	}
}

$hash = $auth->getBlockchainHash();

// Call external authority
$url = getDolGlobalString('BLOCKEDLOG_AUTHORITY_URL') . '/blockedlog/ajax/authority.php?s='.urlencode($auth->signature).'&h='.urlencode($hash);

$resarray = getURLContent($url, 'GET', '', 1, array(), array(), 2);
$res = $resarray['content'];

//echo $url;
echo dol_escape_htmltag($res);
