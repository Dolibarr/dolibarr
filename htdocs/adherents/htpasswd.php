<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2006-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *  	\file 		htdocs/adherents/htpasswd.php
 *      \ingroup    member
 *      \brief      Page d'export htpasswd du fichier des adherents
 *      \author     Rodolphe Quiedeville
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/security2.lib.php';

// Security check
if (! $user->rights->adherent->export) accessforbidden();


/*
 * View
 */

llxHeader();

$now=dol_now();

if ($sortorder == "") {  $sortorder="ASC"; }
if ($sortfield == "") {  $sortfield="d.login"; }
if (! isset($statut))
{
  $statut = 1 ;
}

if (! isset($cotis))
{
  // par defaut les adherents doivent etre a jour de cotisation
  $cotis=1;
}


$sql = "SELECT d.login, d.pass, d.datefin";
$sql .= " FROM ".MAIN_DB_PREFIX."adherent as d ";
$sql .= " WHERE d.statut = $statut ";
if ($cotis==1)
{
	$sql .= " AND datefin > '".$db->idate($now)."'";
}
$sql.= $db->order($sortfield,$sortorder);
//$sql.=$db->plimit($conf->liste_limit, $offset);

$resql = $db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);
	$i = 0;

	print_barre_liste($langs->trans("HTPasswordExport"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder,'',0);

	print "<hr>\n";
	while ($i < $num)
	{
		$objp = $db->fetch_object($result);
		$htpass=crypt($objp->pass,makesalt());
		print $objp->login.":".$htpass."<br>\n";
		$i++;
	}
	print "<hr>\n";
}
else
{
	dol_print_error($db);
}


llxFooter();

$db->close();
?>
