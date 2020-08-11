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
 *      \file       htdocs/blockedlog/ajax/authority.php
 *      \ingroup    blockedlog
 *      \brief      authority
 */


// This script is called with a POST method.
// Directory to scan (full path) is inside POST['dir'].

if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', 1); // Disables token renewal
if (! defined('NOREQUIREMENU')) define('NOREQUIREMENU', '1');
if (! defined('NOREQUIREHTML')) define('NOREQUIREHTML', '1');

$res=require '../../master.inc.php';

require_once DOL_DOCUMENT_ROOT.'/blockedlog/class/blockedlog.class.php';
require_once DOL_DOCUMENT_ROOT.'/blockedlog/class/authority.class.php';

$user=new User($db);
$user->fetch(1); //TODO conf user authority

$auth = new BlockedLogAuthority($db);

$signature = GETPOST('s');
$newblock = GETPOST('b');
$hash = GETPOST('h');

if($auth->fetch(0, $signature)<=0) {
	$auth->signature = $signature;
	$auth->create($user);
}


if(!empty($hash)) {
	echo $auth->checkBlockchain($hash) ? 'hashisok' : 'hashisjunk';
}
elseif(!empty($newblock)){
	if($auth->checkBlock($newblock)) {
		$auth->addBlock($newblock);
		$auth->update($user);

		echo 'blockadded';
	}
	else{
		echo 'blockalreadyadded';
	}
}
else{
	echo 'idontunderstandwhatihavetodo';
}
