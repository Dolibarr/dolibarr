<?php
/* Copyright (C) 2003-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
        \file       htdocs/commande/index.php
        \ingroup    compta
		\brief      Page acceuil zone comptabilité
		\version    $Revision$
*/

require("./pre.inc.php");

if (!$user->rights->commande->lire) accessforbidden();

$langs->load("orders");

llxHeader("",$langs->trans("Orders"),"Commande");

print_fiche_titre($langs->trans("OrdersArea"));

print '<table width="100%" class="notopnoleftnoright">';

print '<tr><td valign="top" width="30%" class="notopnoleft">';

/*
 * Zone recherche
 */
$var=false;
print '<table class="noborder" width="100%">';
print '<form method="post" action="liste.php">';
print '<tr class="liste_titre"><td colspan="3">'.$langs->trans("SearchOrder").'</td></tr>';
print '<tr '.$bc[$var].'><td>';
print $langs->trans("Ref").':</td><td><input type="text" class="flat" name="sf_ref" size=18></td><td rowspan="2"><input type="submit" value="'.$langs->trans("Search").'" class="button"></td></tr>';
print '<tr '.$bc[$var].'><td nowrap>'.$langs->trans("Other").':</td><td><input type="text" class="flat" name="sall" size="18"></td>';
print '</tr>';
print "</form></table><br>\n";


/*
 * Commandes à valider
 */
$sql = "SELECT c.rowid, c.ref, s.nom, s.idp FROM ".MAIN_DB_PREFIX."commande as c, ".MAIN_DB_PREFIX."societe as s";
$sql .= " WHERE c.fk_soc = s.idp AND c.fk_statut = 0";
if ($socidp)
{
  $sql .= " AND c.fk_soc = $socidp";
}

if ( $db->query($sql) )
{
    $langs->load("orders");
    $num = $db->num_rows();
    if ($num)
    {
        $i = 0;
        print '<table class="noborder" width="100%">';
        print '<tr class="liste_titre">';
        print '<td colspan="2">'.$langs->trans("DraftOrders").'</td></tr>';
        $var = True;
        while ($i < $num)
        {
            $var=!$var;
            $obj = $db->fetch_object();
            print "<tr $bc[$var]><td nowrap><a href=\"fiche.php?id=$obj->rowid\">".img_object($langs->trans("ShowOrder"),"order").' '.$obj->ref."</a></td>";
            print '<td><a href="'.DOL_URL_ROOT.'/comm/fiche.php?socid='.$obj->idp.'">'.img_object($langs->trans("ShowCompany"),"company").' '.dolibarr_trunc($obj->nom,24).'</a></td></tr>';
            $i++;
        }
        print "</table><br>";
    }
}

/*
 * Commandes à traiter
 */
$sql = "SELECT c.rowid, c.ref, s.nom, s.idp FROM ".MAIN_DB_PREFIX."commande as c, ".MAIN_DB_PREFIX."societe as s";
$sql .= " WHERE c.fk_soc = s.idp AND c.fk_statut = 1";
$sql .= " ORDER BY c.rowid DESC";

if ( $db->query($sql) )
{
  $num = $db->num_rows();
  if ($num)
    {
      $i = 0;
      print '<table class="noborder" width="100%">';
      print '<tr class="liste_titre">';
      print '<td colspan="2">'.$langs->trans("OrdersToProcess").'</td></tr>';
      $var = True;
      while ($i < $num)
	{
	  $var=!$var;
	  $obj = $db->fetch_object();
	  print "<tr $bc[$var]><td nowrap><a href=\"fiche.php?id=$obj->rowid\">".img_object($langs->trans("ShowOrder"),"order")." ".$obj->ref."</a></td>";
	  print '<td><a href="'.DOL_URL_ROOT.'/comm/fiche.php?socid='.$obj->idp.'">'.img_object($langs->trans("ShowCompany"),"company").' '.$obj->nom.'</a></td></tr>';
	  $i++;
	}
      print "</table><br>";
    }
}


print '</td><td valign="top" width="70%" class="notopnoleftnoright">';


/*
 * Commandes en cours
 */
$sql = "SELECT c.rowid, c.ref, s.nom, s.idp FROM ".MAIN_DB_PREFIX."commande as c, ".MAIN_DB_PREFIX."societe as s";
$sql .= " WHERE c.fk_soc = s.idp AND c.fk_statut = 2 ";
if ($socidp)
{
  $sql .= " AND c.fk_soc = $socidp";
}
$sql .= " ORDER BY c.rowid DESC";
if ( $db->query($sql) )
{
  $num = $db->num_rows();
  if ($num)
    {
      $i = 0;
      print '<table class="noborder" width="100%">';
      print '<tr class="liste_titre">';
      print '<td colspan="2">'.$langs->trans("OnProcessOrders").' ('.$num.')</td></tr>';
      $var = True;
      while ($i < $num)
	{
	  $var=!$var;
	  $obj = $db->fetch_object();
	  print "<tr $bc[$var]><td width=\"30%\"><a href=\"fiche.php?id=$obj->rowid\">".img_object($langs->trans("ShowOrder"),"order").' ';
	  print $obj->ref.'</a></td>';
	  print '<td><a href="'.DOL_URL_ROOT.'/comm/fiche.php?socid='.$obj->idp.'">'.$obj->nom.'</a></td></tr>';
	  $i++;
	}
      print "</table><br>";
    }
}

/*
 * Dernières commandes traitées
 */
$max=5;

$sql = "SELECT c.rowid, c.ref, s.nom, s.idp,";
$sql.= " ".$db->pdate("date_cloture")." as datec";
$sql.= " FROM ".MAIN_DB_PREFIX."commande as c, ".MAIN_DB_PREFIX."societe as s";
$sql.= " WHERE c.fk_soc = s.idp and c.fk_statut > 2";
if ($socidp) $sql .= " AND c.fk_soc = $socidp";
$sql.= " ORDER BY c.tms DESC";
$sql.= $db->plimit($max, 0);

$resql=$db->query($sql);
if ($resql)
{
  $num = $db->num_rows($resql);
  if ($num)
    {
      $i = 0;
      print '<table class="noborder" width="100%">';
      print '<tr class="liste_titre">';
      print '<td colspan="3">'.$langs->trans("LastClosedOrders",$max).'</td></tr>';
      $var = True;
      while ($i < $num)
	{
	  $var=!$var;
	  $obj = $db->fetch_object($resql);
	  print "<tr $bc[$var]><td><a href=\"fiche.php?id=$obj->rowid\">".img_object($langs->trans("ShowOrders"),"order").' ';
	  print $obj->ref.'</a></td>';
	  print '<td><a href="'.DOL_URL_ROOT.'/comm/fiche.php?socid='.$obj->idp.'">'.img_object($langs->trans("ShowCompany"),"company").' '.$obj->nom.'</a></td>';
	  print '<td>'.dolibarr_print_date($obj->datec).'</td>';
	  print '</tr>';
	  $i++;
	}
      print "</table><br>";
    }
}




print '</td></tr></table>';

$db->close();

llxFooter('$Date$ - $Revision$');

?>
