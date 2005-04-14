<?php
/* Copyright (C) 2003-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
        \file       htdocs/expedition/index.php
        \ingroup    expedition
        \brief      Page accueil du module expedition
        \version    $Revision$
*/

require("./pre.inc.php");

$langs->load("sendings");

llxHeader('',$langs->trans("Sendings"),'ch-expedition.html',$form_search);

print_titre($langs->trans("Sendings"));

print '<table class="noborder" width="100%">';
print '<tr><td valign="top" width="30%">';

print '<form method="post" action="liste.php">';
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("SearchASending").'</td></tr>';
print "<tr $bc[1]><td>";
print $langs->trans("Ref").' : <input type="text" class="flat" name="sf_ref" size="16"> <input type="submit" value="'.$langs->trans("Search").'" class="button"></td></tr>';
print "</table></form><br />\n";

/*
 * Expeditions à valider
 */
$sql = "SELECT e.rowid, e.ref, s.nom, s.idp, c.ref as commande_ref, c.rowid as commande_id";
$sql.= " FROM ".MAIN_DB_PREFIX."expedition as e, ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."commande as c";
$sql.= " WHERE e.fk_commande = c.rowid AND c.fk_soc = s.idp AND e.fk_statut = 0";
if ($socidp)
{
  $sql .= " AND c.fk_soc = $socidp";
}

if ( $db->query($sql) ) 
{
  $num = $db->num_rows();
  if ($num)
    {
      print '<table class="noborder" width="100%">';
      print '<tr class="liste_titre">';
      print '<td colspan="3">Expeditions à valider</td></tr>';
      $i = 0;
      $var = True;
      while ($i < $num)
	{
	  $var=!$var;
	  $obj = $db->fetch_object();
	  print "<tr $bc[$var]><td width=\"20%\"><a href=\"fiche.php?id=$obj->rowid\">$obj->ref</a></td>";
	  print '<td><a href="'.DOL_URL_ROOT.'/comm/fiche.php?socid='.$obj->idp.'">'.$obj->nom.'</a></td>';
	  print '<td><a href="'.DOL_URL_ROOT.'/commande/fiche.php?id='.$obj->commande_id.'">'.$obj->commande_ref.'</a></td></tr>';
	  $i++;
	}
      print "</table><br>";
    }
}


/*
 * Commandes à traiter
 */
$sql = "SELECT c.rowid, c.ref, s.nom, s.idp";
$sql.= " FROM ".MAIN_DB_PREFIX."commande as c, ".MAIN_DB_PREFIX."societe as s";
$sql.= " WHERE c.fk_soc = s.idp AND c.fk_statut = 1";
$sql.= " ORDER BY c.rowid ASC";

if ( $db->query($sql) ) 
{
  $num = $db->num_rows();
  if ($num)
    {
      $langs->load("orders");

      $i = 0;
      print '<table class="noborder" width="100%">';
      print '<tr class="liste_titre">';
      print '<td colspan="2">'.$langs->trans("OrdersToProcess").'</td></tr>';
      $var = True;
      while ($i < $num)
	{
	  $var=!$var;
	  $obj = $db->fetch_object();
	  print "<tr $bc[$var]><td width=\"30%\">";
	  print "<a href=\"commande.php?id=$obj->rowid\">".img_object($langs->trans("ShowOrder"),"order").' '.$obj->ref.'</a></td>';
	  print '<td><a href="'.DOL_URL_ROOT.'/comm/fiche.php?socid='.$obj->idp.'">'.$obj->nom.'</a></td></tr>';
	  $i++;
	}
      print "</table><br>";
    }
}


/*
 *
 */
print '</td><td valign="top" width="70%">';


/*
 * Commandes en traitement
 */
$sql = "SELECT c.rowid, c.ref, s.nom, s.idp FROM ".MAIN_DB_PREFIX."commande as c, ".MAIN_DB_PREFIX."societe as s";
$sql .= " WHERE c.fk_soc = s.idp AND c.fk_statut = 2";
$resql = $db->query($sql);
if ( $resql ) 
{
  $langs->load("orders");
  
  $num = $db->num_rows($resql);
  if ($num)
    {
      $i = 0;
      print '<table class="noborder" width="100%">';
      print '<tr class="liste_titre">';
      print '<td colspan="2">'.$langs->trans("OrdersInProcess").'</td></tr>';
      $var = True;
      while ($i < $num)
	{
	  $var=!$var;
	  $obj = $db->fetch_object($resql);
	  print "<tr $bc[$var]><td width=\"30%\"><a href=\"commande.php?id=$obj->rowid\">".img_object($langs->trans("ShowOrder"),"order").' ';
	  print $obj->ref.'</a></td>';
	  print '<td><a href="'.DOL_URL_ROOT.'/comm/fiche.php?socid='.$obj->idp.'">'.$obj->nom.'</a></td></tr>';
	  $i++;
	}
      print "</table><br>";
    }
}


/*
 * Expeditions à valider
 */
$sql = "SELECT e.rowid, e.ref, s.nom, s.idp, c.ref as commande_ref, c.rowid as commande_id";
$sql.= " FROM ".MAIN_DB_PREFIX."expedition as e, ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."commande as c";
$sql.= " WHERE e.fk_commande = c.rowid AND c.fk_soc = s.idp AND e.fk_statut = 1";
$sql .= $db->plimit(5, 0);

if ( $db->query($sql) ) 
{
  $num = $db->num_rows();
  if ($num)
    {
      $i = 0;
      print '<table class="noborder" width="100%">';
      print '<tr class="liste_titre">';
      print '<td colspan="3">'.$langs->trans("LastSendings",$max).'</td></tr>';
      $var = True;
      while ($i < $num)
	{
	  $var=!$var;
	  $obj = $db->fetch_object();
	  print "<tr $bc[$var]><td width=\"20%\"><a href=\"fiche.php?id=$obj->rowid\">".img_object($langs->trans("ShowSending"),"sending").' ';
	  print $obj->ref.'</a></td>';
	  print '<td><a href="'.DOL_URL_ROOT.'/comm/fiche.php?socid='.$obj->idp.'">'.img_object($langs->trans("ShowCompany"),"company").' '.$obj->nom.'</a></td>';
	  print '<td><a href="'.DOL_URL_ROOT.'/commande/fiche.php?id='.$obj->commande_id.'">'.img_object($langs->trans("ShowOrder"),"order").' '.$obj->commande_ref.'</a></td></tr>';
	  $i++;
	}
      print "</table><br>";
    }
}

print '</td></tr></table>';

$db->close();

llxFooter('$Date$ - $Revision$');

?>
