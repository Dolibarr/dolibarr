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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
		\file 		htdocs/adherents/htpasswd.php
        \ingroup    adherent
		\brief      Page d'export htpasswd du fichier des adherents
		\author     Rodolphe Quiedeville
		\version    $Id$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT.'/lib/security.lib.php');

llxHeader();

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



$sql = "SELECT d.login, d.pass, ".$db->pdate("d.datefin")." as datefin";
$sql .= " FROM ".MAIN_DB_PREFIX."adherent as d ";
$sql .= " WHERE d.statut = $statut ";
if ($cotis==1)
{
	$sql .= " AND datefin > now() ";
}
$sql.= " ORDER BY $sortfield $sortorder";
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
	dolibarr_print_error($db);
}


$db->close();

llxFooter('$Date$ - $Revision$');
?>
