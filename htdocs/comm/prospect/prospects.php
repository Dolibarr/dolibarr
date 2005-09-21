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
 */

/**
	    \file       htdocs/comm/prospect/prospects.php
        \ingroup    prospect
		\brief      Page de la liste des prospects
		\version    $Revision$
*/

require("./pre.inc.php");

$langs->load("propal");

$user->getrights('propale');
$user->getrights('fichinter');
$user->getrights('commande');
$user->getrights('projet');

if ($_GET["action"] == 'cstc')
{
  $sql = "UPDATE ".MAIN_DB_PREFIX."societe SET fk_stcomm = ".$_GET["pstcomm"];
  $sql .= " WHERE idp = ".$_GET["pid"];
  $db->query($sql);
}

// Sécurité accés client
if ($user->societe_id > 0) 
{
  $action = '';
  $socidp = $user->societe_id;
}

$socname=isset($_GET["socname"])?$_GET["socname"]:$_POST["socname"];
$sortfield = isset($_GET["sortfield"])?$_GET["sortfield"]:$_POST["sortfield"];
$sortorder = isset($_GET["sortorder"])?$_GET["sortorder"]:$_POST["sortorder"];
$page=isset($_GET["page"])?$_GET["page"]:$_POST["page"];

$page = $user->page_param["page"];
if ($page == -1) { $page = 0 ; }

$offset = $conf->liste_limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;

$sql = "SELECT s.idp, s.nom, s.ville, ".$db->pdate("s.datec")." as datec, ".$db->pdate("s.datea")." as datea,  st.libelle as stcomm, s.prefix_comm, s.fk_stcomm ";
$sql .= ", d.nom as departement";
$sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."c_stcomm as st";
$sql .= " LEFT join ".MAIN_DB_PREFIX."c_departements as d on d.rowid = s.fk_departement";
$sql .= " WHERE s.fk_stcomm = st.id AND s.client=2";

if ($_GET["stcomm"])
{
  $sql .= " AND s.fk_stcomm=".$_GET["stcomm"];
}

if ($user->societe_id)
{
  $sql .= " AND s.idp = " .$user->societe_id;
}

if ($_GET["search_nom"])
{
  $sql .= " AND lower(s.nom) like '%".strtolower($_GET["search_nom"])."%'";
}

if ($_GET["search_ville"])
{
  $sql .= " AND lower(s.ville) like '%".strtolower($_GET["search_ville"])."%'";
}

if ($socname)
{
  $sql .= " AND lower(s.nom) like '%".strtolower($socname)."%'";
  $sortfield = "lower(s.nom)";
  $sortorder = "ASC";
}

if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="s.nom";

$sql .= " ORDER BY $sortfield $sortorder, s.nom ASC ";
$sql .= $db->plimit($conf->liste_limit+1, $offset);

$resql = $db->query($sql);
if ($resql)
{
  $num = $db->num_rows($resql);

  if ($num == 1 && $socname)
    {
      $obj = $db->fetch_object($resql);
      Header("Location: fiche.php?socid=".$obj->idp);
    }
  else
    {
      llxHeader();
    }

  $urladd="page=$page&amp;stcomm=$stcomm";

  print_barre_liste($langs->trans("ListOfProspects"), $page, "prospects.php",'&amp;stcomm='.$_GET["stcomm"],"","",'',$num);

  $i = 0;
  
  print '<table class="liste" width="100%">';
  print '<tr class="liste_titre">';
  print_liste_field_titre($langs->trans("Company"),"prospects.php","s.nom","","","valign=\"center\"",$sortfield);
  print_liste_field_titre($langs->trans("Town"),"prospects.php","s.ville","","","",$sortfield);
  print_liste_field_titre($langs->trans("State"),"prospects.php","s.fk_departement","","","align=\"center\"",$sortfield);
  print_liste_field_titre($langs->trans("DateCreation"),"prospects.php","s.datec","","","align=\"center\"",$sortfield);
  print_liste_field_titre($langs->trans("Status"),"prospects.php","s.fk_stcomm","","","align=\"center\"",$sortfield);
  print '<td class="liste_titre" colspan="4">&nbsp;</td>';
  print "</tr>\n";

  print '<form method="get" action="prospects.php">';
  print '<tr class="liste_titre">';
  print '<td class="liste_titre" valign="right">';
  print '<input type="text" class="flat" name="search_nom" value="'.$_GET["search_nom"].'">';
  print '</td><td class="liste_titre">';
  print '<input type="text" class="flat" name="search_ville" size="12" value="'.$_GET["search_ville"].'">';
  print '</td>';
  print '<td class="liste_titre" colspan="7" align="right">';
  print '<input type="image" class="liste_titre" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" alt="'.$langs->trans("Search").'">';
  print '</td>';

  print "</tr>\n";

  $var=true;

  while ($i < min($num,$conf->liste_limit))
    {
      $obj = $db->fetch_object($resql);
      
      $var=!$var;

      print "<tr $bc[$var]>";
      print '<td><a href="'.DOL_URL_ROOT.'/comm/prospect/fiche.php?id='.$obj->idp.'">';
      print img_object($langs->trans("ShowProspect"),"company");
      print ' '.dolibarr_trunc($obj->nom,44).'</a></td>';
      print "<td>".$obj->ville."&nbsp;</td>";
      print "<td align=\"center\">$obj->departement</td>";
      // Date création
      print "<td align=\"center\">".dolibarr_print_date($obj->datec)."</td>";
      // Statut
      print "<td align=\"center\">";
      $transcode=$langs->trans("StatusProspect".$obj->fk_stcomm);
      $libelle=($transcode!="StatusProspect".$obj->fk_stcomm?$transcode:$obj->stcomm);
      print $libelle;
      print "</td>";

      $sts = array(-1,0,1,2,3);
      print '<td align="right" nowrap>';
      foreach ($sts as $key => $value)
	{
	  if ($value <> $obj->fk_stcomm)
	    {
	      print '<a href="prospects.php?pid='.$obj->idp.'&amp;pstcomm='.$value.'&amp;action=cstc&amp;'.$urladd.'">';
          print img_action(0,$value);
	      print '</a>&nbsp;';
	    }
	}
      print '</td>';

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

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
