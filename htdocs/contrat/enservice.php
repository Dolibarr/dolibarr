<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Laurent Destailleur  <eldy@users.sourceforge.net>
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
	    \file       htdocs/contrat/enservice.php
        \ingroup    contrat
		\brief      Page liste des contrats en service
		\version    $Revision$
*/

require("./pre.inc.php");

$langs->load("products");
$langs->load("companies");

llxHeader();
$sortfield = isset($_GET["sortfield"])?$_GET["sortfield"]:$_POST["sortfield"];
$sortorder = isset($_GET["sortorder"])?$_GET["sortorder"]:$_POST["sortorder"];
$page = isset($_GET["page"])?$_GET["page"]:$_POST["page"];

$statut=isset($_GET["statut"])?$_GET["statut"]:1;
$socid=$_GET["socid"];


/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $action = '';
  $socid = $user->societe_id;
}


if ($page == -1) { $page = 0 ; }

$limit = $conf->liste_limit;
$offset = $limit * $page ;

if ($sortfield == "")
{
  $sortfield="cd.date_ouverture";
}

if ($sortorder == "")
{
  $sortorder="DESC";
}

$sql = "SELECT s.nom, c.rowid as cid, p.rowid as pid, s.idp as sidp, cd.label, cd.statut";
$sql .= " ,".$db->pdate("cd.date_ouverture")." as date_ouverture";
$sql .= " FROM ".MAIN_DB_PREFIX."contrat as c";
$sql .= " , ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."product as p";
$sql .= " , ".MAIN_DB_PREFIX."contratdet as cd";
$sql .= " WHERE c.fk_soc = s.idp AND cd.fk_product = p.rowid AND cd.statut = 4";
$sql .= " AND cd.fk_contrat = c.rowid";
if ($socid > 0)
{
  $sql .= " AND s.idp = $socid";
}
$sql .= " ORDER BY $sortfield $sortorder";
$sql .= $db->plimit($limit + 1 ,$offset);

if ( $db->query($sql) )
{
  $num = $db->num_rows();
  $i = 0;

  print_barre_liste("Liste des contrats en service", $page, "enservice.php", "&sref=$sref&snom=$snom", $sortfield, $sortorder,'',$num);

  print '<table class="noborder" width="100%">';

  print '<tr class="liste_titre">';
  print_liste_field_titre($langs->trans("Ref"),"enservice.php", "c.rowid","","","",$sortfield);
  print_liste_field_titre($langs->trans("Label"),"enservice.php", "p.label","","","",$sortfield);
  print_liste_field_titre($langs->trans("Company"),"enservice.php", "s.nom","","","",$sortfield);
  print '<td align="center">Date mise en service</td>';
  print "</tr>\n";

  $now=mktime();
  $var=True;
  while ($i < min($num,$limit))
    {
      $obj = $db->fetch_object();
      $var=!$var;
      print "<tr $bc[$var]>";
      print "<td><a href=\"fiche.php?id=$obj->cid\">";
      print '<img src="./statut'.$obj->statut.'.png" border="0" alt="statut">';
      print "</a>&nbsp;<a href=\"fiche.php?id=$obj->cid\">$obj->cid</a></td>\n";
      print "<td><a href=\"../product/fiche.php?id=$obj->pid\">$obj->label</a></td>\n";
      print "<td><a href=\"../comm/fiche.php?socid=$obj->sidp\">$obj->nom</a></td>\n";
      print '<td align="center">'.strftime("%d/%m/%y",$obj->date_ouverture)."</td>\n";

      print "</tr>\n";
      $i++;
    }
  $db->free();

  print "</table>";

}
else
{
  dolibarr_print_error($db);
}


$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
