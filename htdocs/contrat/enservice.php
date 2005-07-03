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

if (! $sortfield) $sortfield="cd.date_ouverture";
if (! $sortorder) $sortorder="DESC";


$sql = "SELECT s.nom, c.rowid as cid, s.idp as sidp, cd.rowid, cd.label, cd.statut, p.rowid as pid,";
$sql .= " ".$db->pdate("cd.date_ouverture")." as date_ouverture,";
$sql .= " ".$db->pdate("cd.date_fin_validite")." as date_fin_validite";
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

$resql=$db->query($sql);
if ($resql)
{
  $num = $db->num_rows($resql);
  $i = 0;

  print_barre_liste($langs->trans("ListOfRunningServices"), $page, "enservice.php", "&sref=$sref&snom=$snom", $sortfield, $sortorder,'',$num);

  print '<table class="noborder" width="100%">';

  print '<tr class="liste_titre">';
  print_liste_field_titre($langs->trans("Contract"),"enservice.php", "c.rowid","","","",$sortfield);
  print_liste_field_titre($langs->trans("Service"),"enservice.php", "p.label","","","",$sortfield);
  print_liste_field_titre($langs->trans("Company"),"enservice.php", "s.nom","","","",$sortfield);
  print_liste_field_titre($langs->trans("DateStartRealShort"),"enservice.php", "cd.date_ouverture",'','',' align="center"',$sortfield);
  print_liste_field_titre($langs->trans("DateEndPlannedShort"),"enservice.php", "cd.date_fin_validite",'','',' align="center"',$sortfield);
  print_liste_field_titre($langs->trans("Status"),"enservice.php", "cd.statut","","","",$sortfield);
  print "</tr>\n";

  $now=mktime();
  $var=True;
  while ($i < min($num,$limit))
    {
      $obj = $db->fetch_object($resql);
      $var=!$var;
      print "<tr $bc[$var]>";
      print '<td><a href="fiche.php?id='.$obj->cid.'">'.img_object($langs->trans("ShowContract"),"contract").' '.$obj->cid.'</a></td>';
      print '<td><a href="../product/fiche.php?id='.$obj->pid.'">'.img_object($langs->trans("ShowService"),"service").' '.dolibarr_trunc($obj->label,20).'</a></td>';
      print '<td><a href="../comm/fiche.php?socid='.$obj->sidp.'">'.img_object($langs->trans("ShowCompany"),"company").' '.dolibarr_trunc($obj->nom,44).'</a></td>';
      print '<td align="center">'.dolibarr_print_date($obj->date_ouverture).'</td>';
      print '<td align="center">'.($obj->date_fin_validite?dolibarr_print_date($obj->date_fin_validite):'&nbsp;');
      if ($obj->date_fin_validite < mktime()) print img_warning($langs->trans("Late"));
      else print '&nbsp;&nbsp;&nbsp;&nbsp;';
      print '</td>';
      print '<td align="center"><a href="'.DOL_URL_ROOT.'/contrat/ligne.php?id='.$obj->cid.'&ligne='.$obj->rowid.'"><img src="./statut'.$obj->statut.'.png" border="0" alt="statut"></a></td>';
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
