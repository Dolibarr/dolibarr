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
	    \file       htdocs/comm/prospect/index.php
        \ingroup    commercial
		\brief      Page acceuil de la zone prospection
		\version    $Revision$
*/

require("./pre.inc.php");

$langs->load("propal");

$user->getrights('propale');


if ($user->societe_id > 0)
{
  $socidp = $user->societe_id;
}


llxHeader();

function valeur($sql) 
{
  global $db;
  if ( $db->query($sql) ) 
    {
      if ( $db->num_rows() ) 
	{
	  $valeur = $db->result(0,0);
	}
      $db->free();
    }
  return $valeur;
}

/*
 *
 */

print_titre($langs->trans("ProspectionArea"));

print '<table border="0" width="100%" cellspacing="0" cellpadding="4">';

print '<tr><td valign="top" width="30%">';

if ($conf->propal->enabled) 
{
  $var=false;
  print '<table class="noborder" width="100%">';
  print '<form method="post" action="'.DOL_URL_ROOT.'/comm/propal.php">';
  print '<tr class="liste_titre"><td colspan="3">'.$langs->trans("SearchAProposal").'</td></tr>';
  print '<tr '.$bc[$var].'><td>';
  print $langs->trans("Ref").':</td><td><input type="text" class="flat" name="sf_ref" size="16"></td><td><input type="submit" value="'.$langs->trans("Search").'" class="button"></td></tr>';
  print "</form></table><br>\n";
}

/*
 * Prospects par status
 *
 */  

$sql = "SELECT count(*) as cc, st.libelle, st.id";
$sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."c_stcomm as st ";
$sql .= " WHERE s.fk_stcomm = st.id AND s.client=2";
$sql .= " GROUP BY st.id";
$sql .= " ORDER BY st.id";

$resql=$db->query($sql);
if ($resql)
{
  $num = $db->num_rows($resql);
  $i = 0;
  if ($num > 0 )
    {
      $var=true;

      print '<table class="noborder" width="100%">';
      print '<tr class="liste_titre">';
      print '<td colspan="2">'.$langs->trans("ProspectsByStatus").'</td></tr>';
      while ($i < $num)
	{
	  $obj = $db->fetch_object($resql);
	  $var=!$var;
	  print "<tr $bc[$var]><td><a href=\"prospects.php?page=0&amp;stcomm=".$obj->id."\">";
	  print img_action($langs->trans("Show"),$obj->id).' ';
	  print $langs->trans("StatusProspect".$obj->id);
	  print "</a></td><td>".$obj->cc."</td></tr>";
	  $i++;
	}
      print "</table><br>";
    }
}


if ($conf->propal->enabled) 
{
  $sql = "SELECT p.rowid, p.ref";
  $sql .= " FROM ".MAIN_DB_PREFIX."propal as p";
  $sql .= " WHERE p.fk_statut = 0";
  
  $resql=$db->query($sql);
  if ($resql)
    {
      $var=true;

      $num = $db->num_rows($resql);
      $i = 0;
      if ($num > 0 )
	{
	  print '<table class="noborder"" width="100%">';
	  print "<tr class=\"liste_titre\">";
	  print "<td colspan=\"2\">".$langs->trans("ProposalsDraft")."</td></tr>";
	  
	  while ($i < $num)
	    {
	      $obj = $db->fetch_object($resql);
	      $var=!$var;
	      print "<tr $bc[$var]><td>";
	      print "<a href=\"propal.php?propalid=".$obj->rowid."\">".img_object($langs->trans("ShowPropal"),"propal").' '.$obj->ref."</a>";
	      print "</td></tr>";
	      $i++;
	    }
	  print "</table><br>";
	}
      $db->free($resql);
    }
}

/*
 * Actions commerciales a faire
 *
 */
print '</td><td valign="top" width="70%">';

$sql = "SELECT a.id, ".$db->pdate("a.datea")." as da, c.code, c.libelle, a.fk_user_author, s.nom as sname, s.idp";
$sql .= " FROM ".MAIN_DB_PREFIX."actioncomm as a, ".MAIN_DB_PREFIX."c_actioncomm as c, ".MAIN_DB_PREFIX."societe as s";
$sql .= " WHERE c.id=a.fk_action AND a.percent < 100 AND s.idp = a.fk_soc AND a.fk_user_action = $user->id";
$sql .= " ORDER BY a.datea DESC";

$resql=$db->query($sql);
if ($resql) 
{
  $num = $db->num_rows($resql);
  if ($num > 0)
    {
      $var=true;

      print '<table class="noborder" width="100%">';
      print '<tr class="liste_titre">';
      print '<td colspan="4">'.$langs->trans("ActionsToDo").'</td>';
      print "</tr>\n";
      
      $i = 0;
      while ($i < $num ) 
	{
	  $obj = $db->fetch_object($resql);
	  $var=!$var;
	  
	  print "<tr $bc[$var]><td>".dolibarr_print_date($obj->da)."</td>";

      $transcode=$langs->trans("Action".$obj->code);
      $libelle=($transcode!="Action".$obj->code?$transcode:$obj->libelle);
      print '<td><a href="'.DOL_URL_ROOT.'/comm/action/fiche.php?id='.$obj->id."\">".img_object($langs->trans("ShowAction"),"task").' '.$libelle.'</a></td>';

	  print '<td><a href="'.DOL_URL_ROOT.'/comm/prospect/fiche.php?id='.$obj->idp.'">'.img_object($langs->trans("ShowCompany"),"company").' '.$obj->sname.'</a></td>';
	  $i++;
	}
      print "</table><br>";
    }
  $db->free($resql);
} 
else
{
  dolibarr_print_error($db);
}


$sql = "SELECT s.nom, s.idp, p.rowid, p.price, p.ref,".$db->pdate("p.datep")." as dp, c.label as statut, c.id as statutid";
$sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."propal as p, ".MAIN_DB_PREFIX."c_propalst as c WHERE p.fk_soc = s.idp AND p.fk_statut = c.id AND p.fk_statut = 1";
if ($socidp)
{ 
  $sql .= " AND s.idp = $socidp"; 
}

$sql .= " ORDER BY p.rowid DESC";
$sql .= $db->plimit(5, 0);

if ( $db->query($sql) )
{
  $num = $db->num_rows();
  $i = 0;
  if ($num > 0 )
    {
      $var=true;

      print '<table class="noborder" width="100%">';
      print '<tr class="liste_titre"><td colspan="4">'.$langs->trans("ProposalsOpened").'</td></tr>';
      
      while ($i < $num)
	{
	  $obj = $db->fetch_object();
	  $var=!$var;
	  print "<tr $bc[$var]><td width=\"20%\"><a href=\"../propal.php?propalid=".$obj->rowid."\">";
	  print img_object($langs->trans("ShowPropal"),"propal").' '.$obj->ref.'</a></td>';

	  print "<td width=\"30%\"><a href=\"fiche.php?id=$obj->idp\">".img_object($langs->trans("ShowCompany"),"company").' '.$obj->nom."</a></td>\n";
	  print "<td align=\"right\">";
	  print dolibarr_print_date($obj->dp)."</td>\n";	  
	  print "<td align=\"right\">".price($obj->price)."</td></tr>\n";
	  $i++;
	}
      print "</table><br>";
    }
}

/*
 * Sociétés à contacter
 *
 */
$sql = "SELECT s.nom, s.idp";
$sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
$sql .= " WHERE s.fk_stcomm = 1";
$sql .= " ORDER BY s.tms ASC";
$sql .= $db->plimit(15, 0);

if ( $db->query($sql) )
{
  $num = $db->num_rows();
  $i = 0;
  if ($num > 0 )
    {
      $var=true;

      print '<table class="noborder" width="100%">';
      print '<tr class="liste_titre"><td colspan="4">'.$langs->trans("ProspectToContact").'</td></tr>';
      
      while ($i < $num)
	{
	  $obj = $db->fetch_object();
	  $var=!$var;
	  print "<tr $bc[$var]><td width=\"12%\"><a href=\"".DOL_URL_ROOT."/comm/prospect/fiche.php?id=".$obj->idp."\">";
	  print img_object($langs->trans("ShowCompany"),"company");
	  print ' '.$obj->nom.'</a></td></tr>';
	  $i++;
	}
      print "</table><br>";
    }
}


print '</td></tr>';
print '</table>';

$db->close();
 

llxFooter('$Date$ - $Revision$');
?>
