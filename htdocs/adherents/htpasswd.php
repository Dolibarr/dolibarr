<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2006-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *  	\file 		htdocs/adherents/htpasswd.php
 *      \ingroup    member
 *      \brief      Export page htpasswd of the membership file
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/security2.lib.php';

$status = GETPOST('status', 'int');
$cotis = GETPOST('cotis', 'int');

$sortfield = GETPOST('sortfield', 'alphanohtml');
$sortorder = GETPOST('sortorder', 'aZ09');

// Security check
if (empty($conf->adherent->enabled)) {
	accessforbidden();
}
if (empty($user->rights->adherent->export)) {
	accessforbidden();
}


/*
 * View
 */

llxHeader();

$now = dol_now();

if (empty($sortorder)) {
	$sortorder = "ASC";
}
if (empty($sortfield)) {
	$sortfield = "d.login";
}

$sql = "SELECT d.login, d.pass, d.datefin";
$sql .= " FROM ".MAIN_DB_PREFIX."adherent as d ";
$sql .= " WHERE d.statut = ".((int) $status);
if ($cotis == 1) {
	$sql .= " AND datefin > '".$db->idate($now)."'";
}
$sql .= $db->order($sortfield, $sortorder);
//$sql.=$db->plimit($conf->liste_limit, $offset);

$resql = $db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);
	$i = 0;

	$param = '';
	print_barre_liste($langs->trans("HTPasswordExport"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', 0);

	print "<hr>\n";
	while ($i < $num) {
		$objp = $db->fetch_object($result);
		$htpass = crypt($objp->pass, makesalt());
		print $objp->login.":".$htpass."<br>\n";
		$i++;
	}
	print "<hr>\n";
} else {
	dol_print_error($db);
}

// End of page
llxFooter();
$db->close();
