<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

/*!
  \file       htdocs/projet/index.php
  \ingroup    projet
  \brief      Page d'accueil du module projet
  \version    $Revision$
*/

require("./pre.inc.php");

$langs->load("projects");

/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $action = '';
  $socidp = $user->societe_id;
}

llxHeader("",$langs->trans("Projects"),"Projet");

print_titre($langs->trans("Projects"));

$sortfield = isset($_GET["sortfield"])?$_GET["sortfield"]:$_POST["sortfield"];
$sortorder = isset($_GET["sortorder"])?$_GET["sortorder"]:$_POST["sortorder"];
$page=isset($_GET["page"])?$_GET["page"]:$_POST["page"];

if ($sortfield == "") $sortfield="p.ref";
if ($sortorder == "") $sortorder="ASC";

$offset = $limit * $page ;



/*
 *
 * Affichage de la liste des projets
 * 
 */
print '<br>';
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print_liste_field_titre($langs->trans("Company"),"index.php","s.nom","","","",$sortfield);
print '<td>Nb</td>';
print "</tr>\n";

$sql = "SELECT s.nom, s.idp, count(p.rowid)";
$sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."projet as p";
$sql .= " WHERE p.fk_soc = s.idp";

if ($socidp)
{ 
  $sql .= " AND s.idp = $socidp"; 
}
$sql .= " GROUP BY s.nom";
$sql .= " ORDER BY $sortfield $sortorder " . $db->plimit($conf->liste_limit, $offset);

$var=true;
$resql = $db->query($sql);
if ( $resql )
{
  $num = $db->num_rows($resql);
  $i = 0;

  while ($i < $num)
    {
      $row = $db->fetch_row( $resql);
      $var=!$var;
      print "<tr $bc[$var]>";
      print '<td><a href="'.DOL_URL_ROOT.'/projet/liste.php?socid='.$row[1].'">'.$row[0].'</a></td>';
      print '<td>'.$row[2].'</td>';
      print "</tr>\n";
    
      $i++;
    }
  
  $db->free($resql);
}
else
{
  dolibarr_print_error($db);
}

print "</table>";

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
