<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

/**
	    \file       htdocs/contrat/liste.php
        \ingroup    contrat
		\brief      Page liste des contrats
		\version    $Revision$
*/

require("./pre.inc.php");
require_once (DOL_DOCUMENT_ROOT."/contrat/contrat.class.php");

$langs->load("contracts");
$langs->load("products");
$langs->load("companies");


llxHeader();

$sortfield = isset($_GET["sortfield"])?$_GET["sortfield"]:$_POST["sortfield"];
$sortorder = isset($_GET["sortorder"])?$_GET["sortorder"]:$_POST["sortorder"];
$page = isset($_GET["page"])?$_GET["page"]:$_POST["page"];
if ($page == -1) { $page = 0 ; }
$limit = $conf->liste_limit;
$offset = $limit * $page ;

$statut=isset($_GET["statut"])?$_GET["statut"]:1;
$socid=$_GET["socid"];

if (! $sortfield) $sortfield="c.rowid";
if (! $sortorder) $sortorder="DESC";


/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $action = '';
  $socid = $user->societe_id;
}



$sql = "SELECT count(cd.rowid) as nb, c.rowid as cid, c.datec, c.statut, s.nom, s.idp as sidp";
$sql.= " FROM ".MAIN_DB_PREFIX."contrat as c, ".MAIN_DB_PREFIX."societe as s";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."contratdet as cd ON c.rowid = cd.fk_contrat";
$sql.= " WHERE c.fk_soc = s.idp ";
if ($_POST["search_contract"]) {
    $sql .= " AND c.rowid = ".$_POST["search_contract"];
}
if ($socid > 0)
{
  $sql .= " AND s.idp = $socid";
}
$sql.= " GROUP BY c.rowid, c.datec, c.statut, s.nom, s.idp";
$sql.= " ORDER BY $sortfield $sortorder";
$sql.= $db->plimit($limit + 1 ,$offset);

$resql=$db->query($sql);
if ($resql)
{
  $num = $db->num_rows($resql);
  $i = 0;

  print_barre_liste($langs->trans("ListOfContracts"), $page, $_SERVER["PHP_SELF"], "&sref=$sref&snom=$snom", $sortfield, $sortorder,'',$num);

  print '<table class="noborder" width="100%">';

  print '<tr class="liste_titre">';
  print_liste_field_titre($langs->trans("Ref"), $_SERVER["PHP_SELF"], "c.rowid","","",'width="50"',$sortfield);
  print_liste_field_titre($langs->trans("NbOfServices"), $_SERVER["PHP_SELF"], "nb",'','','width="80"',$sortfield);
  print_liste_field_titre($langs->trans("Company"), $_SERVER["PHP_SELF"], "s.nom","","","",$sortfield);
  print_liste_field_titre($langs->trans("DateCreation"), $_SERVER["PHP_SELF"], "c.datec","","",'align="center"',$sortfield);
  print_liste_field_titre($langs->trans("Status"), $_SERVER["PHP_SELF"], "c.statut","","",'align="center"',$sortfield);
  print "</tr>\n";
    
  $contratstatic=new Contrat($db);
  
  $now=mktime();
  $var=True;
  while ($i < min($num,$limit))
    {
      $obj = $db->fetch_object($resql);
      $var=!$var;
      print "<tr $bc[$var]>";
      print "<td><a href=\"fiche.php?id=$obj->cid\">";
      print img_object($langs->trans("ShowContract"),"contract").' '.$obj->cid.'</a></td>';
      print '<td align="center">'.$obj->nb.'</td>';
      print '<td><a href="../comm/fiche.php?socid='.$obj->sidp.'">'.img_object($langs->trans("ShowCompany"),"company").' '.$obj->nom.'</a></td>';
      print '<td align="center">'.dolibarr_print_date($obj->datec).'</td>';
      print '<td align="center">'.$contratstatic->LibStatut($obj->statut).'</td>';

      print "</tr>\n";
      $i++;
    }
  $db->free($resql);

  print "</table>";

}
else
{
  dolibarr_print_error($db);
}


$db->close();

llxFooter('$Date$ - $Revision$');
?>
