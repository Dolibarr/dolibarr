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

print_fiche_titre($langs->trans("SendingsArea"));

print '<table class="notopnoleftnoright" width="100%">';
print '<tr><td valign="top" width="30%" class="notopnoleft">';

$var=false;
print '<table class="noborder" width="100%">';
print '<form method="post" action="liste.php">';
print '<tr class="liste_titre"><td colspan="3">'.$langs->trans("SearchASending").'</td></tr>';
print '<tr '.$bc[$var].'><td>';
print $langs->trans("Ref").':</td><td><input type="text" class="flat" name="sf_ref" size="18"></td><td><input type="submit" value="'.$langs->trans("Search").'" class="button"></td></tr>';
print "</form></table><br />\n";

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
$resql=$db->query($sql);
if ($resql)
{
    $num = $db->num_rows($resql);
    if ($num)
    {
        print '<table class="noborder" width="100%">';
        print '<tr class="liste_titre">';
        print '<td colspan="3">'.$langs->trans("SendingsToValidate").'</td></tr>';
        $i = 0;
        $var = True;
        while ($i < $num)
        {
            $var=!$var;
            $obj = $db->fetch_object($resql);
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
	  print "<tr $bc[$var]><td width=\"33%\">";
	  print "<a href=\"commande.php?id=$obj->rowid\">".img_object($langs->trans("ShowOrder"),"order").' '.$obj->ref.'</a></td>';
	  print '<td><a href="'.DOL_URL_ROOT.'/comm/fiche.php?socid='.$obj->idp.'">'.dolibarr_trunc($obj->nom,20).'</a></td></tr>';
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
$sql .= " ORDER BY e.date_expedition DESC";
$sql .= $db->plimit(5, 0);

$resql = $db->query($sql);
if ($resql) 
{
  $num = $db->num_rows($resql);
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
	  $obj = $db->fetch_object($resql);
	  print "<tr $bc[$var]><td width=\"20%\"><a href=\"fiche.php?id=$obj->rowid\">".img_object($langs->trans("ShowSending"),"sending").' ';
	  print $obj->ref.'</a></td>';
	  print '<td><a href="'.DOL_URL_ROOT.'/comm/fiche.php?socid='.$obj->idp.'">'.img_object($langs->trans("ShowCompany"),"company").' '.$obj->nom.'</a></td>';
	  print '<td><a href="'.DOL_URL_ROOT.'/commande/fiche.php?id='.$obj->commande_id.'">'.img_object($langs->trans("ShowOrder"),"order").' '.$obj->commande_ref.'</a></td></tr>';
	  $i++;
	}
      print "</table><br>";
    }
  $db->free($resql);
}

print '</td></tr></table>';

$db->close();

llxFooter('$Date$ - $Revision$');

?>
