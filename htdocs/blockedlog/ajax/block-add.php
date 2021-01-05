<?php
/* Copyright (C) 2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2017 ATM Consulting       <contact@atm-consulting.fr>
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
 *      \file       htdocs/blockedlog/ajax/block-add.php
 *      \ingroup    blockedlog
 *      \brief      block-add
 */


// This script is called with a POST method.
// Directory to scan (full path) is inside POST['dir'].

if (!defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', 1); // Disables token renewal
if (!defined('NOREQUIREMENU')) define('NOREQUIREMENU', '1');
if (!defined('NOREQUIREHTML')) define('NOREQUIREHTML', '1');

$res = require '../../main.inc.php';

$id = GETPOST('id', 'int');
$element = GETPOST('element', 'alpha');
$action = GETPOST('action', 'aZ09');

if ($element === 'facture') {
	require_once DOL_DOCUMENT_ROOT.'/blockedlog/class/blockedlog.class.php';
	require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';

	$facture = new Facture($db);
	if ($facture->fetch($id) > 0) {
		$facture->call_trigger($action, $user);
	}
}
