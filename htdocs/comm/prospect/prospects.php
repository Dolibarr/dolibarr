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
	    \file       htdocs/comm/prospect/prospect.php
        \ingroup    prospect
		\brief      Page de la liste des prospects
		\version    $Revision$
*/

require("./pre.inc.php");

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

dolibarr_user_page_param($db, $user, $_GET);

/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $action = '';
  $socidp = $user->societe_id;
}

$socname=$_GET["socname"];
$sortorder=$_GET["sortorder"];
$sortfield=$_GET["sortfield"];
$page=$_GET["page"];

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

if (strlen($stcomm))
{
  $sql .= " AND s.fk_stcomm=$stcomm";
}

if (strlen($user->page_param["begin"]))
{
  $sql .= " AND upper(s.nom) like '".$user->page_param["begin"]."%'";
}

if ($user->societe_id)
{
  $sql .= " AND s.idp = " .$user->societe_id;
}

if ($socname)
{
  $sql .= " AND lower(s.nom) like '%".strtolower($socname)."%'";
  $sortfield = "lower(s.nom)";
  $sortorder = "ASC";
}

if (! $sortorder)
{
  $sortorder="ASC";
}
if (! $sortfield)
{
  $sortfield="s.nom";
}

$sql .= " ORDER BY $sortfield $sortorder, s.nom ASC " . $db->plimit($conf->liste_limit, $offset);

$result = $db->query($sql);
if ($result)
{
  $num = $db->num_rows();

  if ($num == 1 && $socname)
    {
      $obj = $db->fetch_object($result);
      Header("Location: fiche.php?socid=".$obj->idp);
    }
  else
    {
      llxHeader();
    }

  $urladd="page=$page&amp;stcomm=$stcomm";

  print_barre_liste("Liste des prospects", $page, "prospects.php",'&amp;stcomm='.$_GET["stcomm"],"","",'',$num);

  print '<div align="center">';

  print "| <A href=\"prospects.php?page=$pageprev&amp;stcomm=$stcomm&amp;begin=%25\">*</A>\n| ";
  for ($ij = 65 ; $ij < 91; $ij++) {
    print "<A href=\"prospects.php?begin=" . chr($ij) . "&stcomm=$stcomm\" class=\"T3\">";
    
    if ($user->page_param["begin"] == chr($ij) )
      {
	print  "<b>&gt;" . chr($ij) . "&lt;</b>" ; 
      } 
    else
      {
	print  chr($ij);
      } 
    print "</A> | ";
  }
  print "</div>";

  $i = 0;
  
  print '<table class="noborder" width="100%">';
  print '<tr class="liste_titre">';
  print "<td valign=\"center\">";
  print_liste_field_titre($langs->trans("Company"),"prospects.php","s.nom");
  print "</td><td>";
  print_liste_field_titre($langs->trans("Town"),"prospects.php","s.ville");
  print "</td>";
  print "<td align=\"center\">";
  print_liste_field_titre("Département","prospects.php","s.fk_departement");
  print "</td><td>";
  print_liste_field_titre($langs->trans("Status"),"prospects.php","s.fk_stcomm");
  print "</td><td>";
  print_liste_field_titre("Insertion","prospects.php","s.datec");
  print '</td><td colspan="4">&nbsp;</td>';
  print "</tr>\n";
  $var=true;

  while ($i < min($num,$conf->liste_limit))
    {
      $obj = $db->fetch_object();
      
      $var=!$var;

      print "<tr $bc[$var]>";
      print '<td width="35%"><a href="fiche.php?id='.$obj->idp.'">';
      print img_file();
      print "</a>&nbsp;<a href=\"fiche.php?id=$obj->idp\">$obj->nom</A></td>\n";
      print "<td>".$obj->ville."&nbsp;</td>\n";
      print "<td align=\"center\">$obj->departement</td>\n";
      print "<td>".$obj->stcomm."</td>\n";

      if ($user->societe_id == 0)
	{
	  print "<td align=\"center\"><a href=\"addpropal.php?socidp=$obj->idp&action=create\">".strftime("%d/%b/%y",$obj->datec)."</A></td>\n";

	}
      else
	{
	  print "<td>&nbsp;</td>\n";
	}

      $sts = array(-1,0,1,2);
      foreach ($sts as $key => $value)
	{
	  if ($value <> $obj->fk_stcomm)
	    {
	      print '<td><a href="prospects.php?pid='.$obj->idp.'&amp;pstcomm='.$value.'&amp;action=cstc&amp;'.$urladd.'">';
	      print '<img align="absmiddle" src="'.DOL_URL_ROOT.'/theme/'.MAIN_THEME.'/img/stcomm'.$value.'.png" border="0" alt="'.$alt.'">';
	      print '</a></td>';
	    }
	  else
	    {
	      print '<td></td>';
	    }
	}

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
