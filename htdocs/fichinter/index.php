<?php
/* Copyright (C) 2002-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *
 * $Id$
 * $Source$
 *
 */

/** 	\file       htdocs/ficheinter.php
		\brief      Fichier fiche intervention
		\ingroup    ficheinter
		\version    $Revision$
*/

require("./pre.inc.php");
require("../contact.class.php");

if ($user->societe_id > 0)
{
  $socid = $user->societe_id ;
}

llxHeader();


$sortorder=$_GET["sortorder"]?$_GET["sortorder"]:$_POST["sortorder"];
$sortfield=$_GET["sortfield"]?$_GET["sortfield"]:$_POST["sortfield"];

if (! $sortorder) $sortorder="DESC";
if (! $sortfield) $sortfield="f.datei";
if ($page == -1) { $page = 0 ; }

$limit = $conf->liste_limit;
$offset = $limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;

$sql = "SELECT s.nom,s.idp, f.ref,".$db->pdate("f.datei")." as dp, f.rowid as fichid, f.fk_statut, f.duree";
$sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."fichinter as f ";
$sql .= " WHERE f.fk_soc = s.idp ";


if ($socid > 0)
{
  $sql .= " AND s.idp = " . $socid;
}

$sql .= " ORDER BY $sortfield $sortorder " . $db->plimit( $limit + 1 ,$offset);

if ( $db->query($sql) )
{
  $num = $db->num_rows();
  print_barre_liste("Liste des fiches d'intervention", $page, "index.php","&amp;socid=$socid",$sortfield,$sortorder,'',$num);

  $i = 0;
  print '<table class="noborder" width="100%">';
  print "<tr class=\"liste_titre\">";
  print_liste_field_titre($langs->trans("Ref"),"index.php","f.ref","","&amp;socid=$socid",'width="15%"',$sortfield);
  print_liste_field_titre($langs->trans("Company"),"index.php","s.nom","","&amp;socid=$socid",'',$sortfield);
  print_liste_field_titre($langs->trans("Date"),"index.php","f.datei","","&amp;socid=$socid",'',$sortfield);
  print '<td align="center">'.$langs->trans("Duration").'</td>';
  print '<td align="center">'.$langs->trans("Status").'</td>';
  print "</tr>\n";
  $var=True;
  while ($i < $num)
    {
      $objp = $db->fetch_object();
      $var=!$var;
      print "<tr $bc[$var]>";
      print "<td><a href=\"fiche.php?id=$objp->fichid\">".img_object($langs->trans("Show"),"task").' '.$objp->ref."</a></td>\n";

      print '<td><a href="index.php?socid='.$objp->idp.'">'.img_object($langs->trans("ShowCompany"),"company").' '.$objp->nom."</a></td>\n";
      print "<td>".strftime("%d %B %Y",$objp->dp)."</TD>\n";
      print '<td align="center">'.sprintf("%.1f",$objp->duree).'</td>';
      print '<td align="center">'.$objp->fk_statut.'</td>';

      print "</tr>\n";
      
      $i++;
    }
  
  print "</table>";
  $db->free();
}
else
{
  dolibarr_print_error($db);
}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
