<?php
/* Copyright (C) 2015-2016 Marcos García de La Fuente	<hola@marcosgdf.com>
 * Copyright (C) 2020      Alexandre Spangaro			<aspangaro@open-dsi.fr>
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
 * or see https://www.gnu.org/
 */

define('NOTOKENRENEWAL', '1');
define('NOREQUIREMENU', '1');
define('NOREQUIREHTML', '1');
define('NOREQUIREAJAX', '1');
define('NOREQUIRESOC', '1');

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/multismtp.class.php';

$langs->load('errors');

try {
	// Defini si peux lire/modifier permisssions
	$canreaduser=($user->admin || $user->rights->user->user->lire);
	$id = GETPOST('userid', 'int');

	$result = restrictedArea($user, 'user', $id, '&user', $feature2);
	if ($user->id <> $id && !$canreaduser) {
		throw new Exception($langs->trans('ErrorForbidden'));
	}

	// Charge utilisateur edite
	$fuser = new User($db);

	if ($fuser->fetch($id) < 0) {
		throw new Exception($langs->trans('ErrorForbidden'));
	}

	$multismtp = new Multismtp($db, $conf);
	$multismtp->fetch($fuser);

	$imap_folders = array_keys($multismtp->getImapFolders());

	if (!in_array(GETPOST('folder'), $imap_folders)) {
		$langs->load('users');
		throw new Exception($langs->trans('ErrorFolderNotExist'));
	}

	$multismtp->imap_folder = GETPOST('folder');

	try {
		$multismtp->update();
	} catch (Exception $e) {
		throw new Exception($langs->trans('CoreErrorMessage').':'.$e->getMessage());
	}

	$res = array(
		'status' => 'ok'
	);
} catch (Exception $e) {
	$res = array(
		'status' => 'error',
		'msg' => $e->getMessage()
	);
}

header('Content-Type: application/json');
echo json_encode($res);
