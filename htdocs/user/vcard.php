<?php
/* Copyright (C) 2004      	Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2023 	Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 	Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2020		Tobias Sekan		<tobias.sekan@startmail.com>
 * Copyright (C) 2021-2022 	Anthony Berton		<anthony.berton@bb2a.fr>
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
 *	    \file       htdocs/user/vcard.php
 *      \ingroup    user
 *		\brief      Page to return a user vcard
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/vcard.class.php';

$id = GETPOST('id', 'int');

// Security check
$socid = 0;
if ($user->socid > 0) {
	$socid = $user->socid;
}
$feature2 = 'user';
$result = restrictedArea($user, 'user', $id, 'user', $feature2);

$object = new User($db);
$result = $object->fetch($id);
if ($result <= 0) {
	dol_print_error($db, $object->error);
	exit;
}

// Data from linked company
$company = new Societe($db);
if ($object->socid > 0) {
	$result = $company->fetch($object->socid);
}


/*
 * View
 */

// We create VCard
$v = new vCard();
$output = $v->buildVCardString($object, $company, $langs);

$filename = trim(urldecode($v->getFileName())); // "Nom prenom.vcf"
$filenameurlencoded = dol_sanitizeFileName(urlencode($filename));
//$filename = dol_sanitizeFileName($filename);

top_httphead('text/x-vcard; name="'.$filename.'"');

header("Content-Disposition: attachment; filename=\"".$filename."\"");
header("Content-Length: ".dol_strlen($output));
header("Connection: close");

print $output;

$db->close();
