<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2006 Regis Houssin        <regis@dolibarr.fr>
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
	    \file       htdocs/compta/deplacement/index.php
		\brief      Page liste des déplacements
		\version	$Id$
*/

require("./pre.inc.php");
require("../../tva.class.php");

$langs->load("companies");
$langs->load("users");
$langs->load("trips");

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'deplacement','','',1);


llxHeader();

$sortorder=$_GET["sortorder"];
$sortfield=$_GET["sortfield"];
$page=$_GET["page"];

if (! $sortorder) $sortorder="DESC";
if (! $sortfield) $sortfield="d.dated";


if ($page == -1) { $page = 0 ; }

$limit = $conf->liste_limit;
$offset = $limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;

// Sécurité accés client
$socid = $_GET["socid"];
if ($user->societe_id > 0)
{
    $action = '';
    $socid = $user->societe_id;
}

$sql = "SELECT s.nom, s.rowid as socid,";                       // Ou
$sql.= " d.rowid, ".$db->pdate("d.dated")." as dd, d.km, ";     // Comment
$sql.= " u.name, u.firstname";                                  // Qui
if (!$user->rights->commercial->client->voir && !$socid) $sql .= ", sc.fk_soc, sc.fk_user";
$sql.= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."deplacement as d, ".MAIN_DB_PREFIX."user as u";
if (!$user->rights->commercial->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql.= " WHERE d.fk_soc = s.rowid AND d.fk_user = u.rowid";
if (!$user->rights->commercial->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;

if ($socid)
{
  $sql .= " AND s.rowid = ".$socid;
}

$sql .= " ORDER BY $sortfield $sortorder " . $db->plimit( $limit + 1 ,$offset);

$resql=$db->query($sql);
if ($resql)
{
  $num = $db->num_rows($resql);

  print_barre_liste($langs->trans("ListOfTrips"), $page, "index.php","&socid=$socid",$sortfield,$sortorder,'',$num);

  $i = 0;
  print '<table class="noborder" width="100%">';
  print "<tr class=\"liste_titre\">";
  print_liste_field_titre($langs->trans("Ref"),"index.php","d.rowid","","&socid=$socid",'',$sortfield,$sortorder);
  print_liste_field_titre($langs->trans("Date"),"index.php","d.dated","","&socid=$socid",'',$sortfield,$sortorder);
  print_liste_field_titre($langs->trans("Company"),"index.php","s.nom","","&socid=$socid",'',$sortfield,$sortorder);
  print_liste_field_titre($langs->trans("Person"),"index.php","u.name","","&socid=$socid",'',$sortfield,$sortorder);
  print_liste_field_titre($langs->trans("Distance"),"index.php","d.km","","&socid=$socid",'align="right"',$sortfield,$sortorder);
  print "</tr>\n";

  $var=true;
  while ($i < $num)
    {
      $objp = $db->fetch_object($resql);
      $soc = new Societe($db);
      $soc->fetch($objp->socid);
      $var=!$var;
      print "<tr $bc[$var]>";
      print '<td><a href="fiche.php?id='.$objp->rowid.'">'.img_object($langs->trans("ShowTrip"),"trip").' '.$objp->rowid.'</a></td>';
      print '<td>'.dolibarr_print_date($objp->dd).'</td>';
      print '<td>'.$soc->getNomUrl(1).'</a></td>';
      print '<td align="left"><a href="'.DOL_URL_ROOT.'/user/fiche.php?id='.$objp->rowid.'">'.img_object($langs->trans("ShowUser"),"user").' '.$objp->firstname.' '.$objp->name.'</a></td>';
      print '<td align="right">'.$objp->km.'</td>';
      print "</tr>\n";
      
      $i++;
    }
  
  print "</table>";
  $db->free($resql);
}
else
{
  dolibarr_print_error($db);
}
$db->close();

llxFooter('$Date$ r&eacute;vision $Revision$');
?>
