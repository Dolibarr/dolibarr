<?PHP
/* Copyright (C) 2003-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

/*! \file htdocs/expedition/index.php
        \ingroup    expedition
		\brief      Page accueil du module expedition
		\version    $Revision$
*/


require("./pre.inc.php");

llxHeader('','Expéditions','ch-expedition.html',$form_search);

print_titre("Expeditions");

print '<table border="0" width="100%" cellspacing="0" cellpadding="4">';

print '<tr><td valign="top" width="30%">';

print '<form method="post" action="liste.php">';
print '<table class="noborder" cellspacing="0" cellpadding="3" width="100%">';
print '<tr class="liste_titre"><td colspan="2">Rechercher une expédition</td></tr>';
print "<tr $bc[1]><td>";
print $langs->trans("Ref").' : <input type="text" name="sf_ref"> <input type="submit" value="'.$langs->trans("Search").'" class="flat"></td></tr>';
print "</table></form>\n";

/*
 * Expeditions à valider
 */
$sql = "SELECT e.rowid, e.ref, s.nom, s.idp, c.ref as commande_ref, c.rowid as commande_id FROM ".MAIN_DB_PREFIX."expedition as e, ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."commande as c";
$sql .= " WHERE e.fk_commande = c.rowid AND c.fk_soc = s.idp AND e.fk_statut = 0";
if ($socidp)
{
  $sql .= " AND c.fk_soc = $socidp";
}

if ( $db->query($sql) ) 
{
  $num = $db->num_rows();
  if ($num)
    {
      print '<table class="noborder" cellspacing="0" cellpadding="3" width="100%">';
      print '<tr class="liste_titre">';
      print '<td colspan="3">Expeditions à valider</td></tr>';
      $i = 0;
      $var = True;
      while ($i < $num)
	{
	  $var=!$var;
	  $obj = $db->fetch_object($i);
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
$sql = "SELECT c.rowid, c.ref, s.nom, s.idp FROM ".MAIN_DB_PREFIX."commande as c, ".MAIN_DB_PREFIX."societe as s";
$sql .= " WHERE c.fk_soc = s.idp AND c.fk_statut = 1";
if ($socidp)
{
  $sql .= " AND c.fk_soc = $socidp";
}

if ( $db->query($sql) ) 
{
  $num = $db->num_rows();
  if ($num)
    {
      $langs->load("orders");

      $i = 0;
      print '<table class="noborder" cellspacing="0" cellpadding="3" width="100%">';
      print '<tr class="liste_titre">';
      print '<td colspan="2">'.$langs->trans("OrdersToProcess").'</td></tr>';
      $var = True;
      while ($i < $num)
	{
	  $var=!$var;
	  $obj = $db->fetch_object($i);
	  print "<tr $bc[$var]><td width=\"20%\"><a href=\"commande.php?id=$obj->rowid\">".img_file()."</a>&nbsp;";
	  print "<a href=\"commande.php?id=$obj->rowid\">$obj->ref</a></td>";
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
      print '<table class="noborder" cellspacing="0" cellpadding="3" width="100%">';
      print '<tr class="liste_titre">';
      print '<td colspan="2">'.$langs->trans("OrdersInProcess").'</td></tr>';
      $var = True;
      while ($i < $num)
	{
	  $var=!$var;
	  $obj = $db->fetch_object($i);
	  print "<tr $bc[$var]><td width=\"20%\"><a href=\"commande.php?id=$obj->rowid\">".img_file()."</a>&nbsp;";
	  print "<a href=\"commande.php?id=$obj->rowid\">$obj->ref</a></td>";
	  print '<td><a href="'.DOL_URL_ROOT.'/comm/fiche.php?socid='.$obj->idp.'">'.$obj->nom.'</a></td></tr>';
	  $i++;
	}
      print "</table><br>";
    }
}



/*
 * Expeditions à valider
 */
$sql = "SELECT e.rowid, e.ref, s.nom, s.idp, c.ref as commande_ref, c.rowid as commande_id FROM ".MAIN_DB_PREFIX."expedition as e, ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."commande as c";
$sql .= " WHERE e.fk_commande = c.rowid AND c.fk_soc = s.idp AND e.fk_statut = 1";
if ($socidp)
{
  $sql .= " AND c.fk_soc = $socidp";
}
$sql .= " ORDER BY c.rowid DESC";
$sql .= $db->plimit(5, 0);
if ( $db->query($sql) ) 
{
  $num = $db->num_rows();
  if ($num)
    {
      $i = 0;
      print '<table class="noborder" cellspacing="0" cellpadding="3" width="100%">';
      print '<tr class="liste_titre">';
      print '<td colspan="3">5 dernières expéditions</td></tr>';
      $var = True;
      while ($i < $num)
	{
	  $var=!$var;
	  $obj = $db->fetch_object($i);
	  print "<tr $bc[$var]><td width=\"20%\"><a href=\"fiche.php?id=$obj->rowid\">".img_file()."</a>&nbsp;";
	  print "<a href=\"fiche.php?id=$obj->rowid\">$obj->ref</a></td>";
	  print '<td><a href="'.DOL_URL_ROOT.'/comm/fiche.php?socid='.$obj->idp.'">'.$obj->nom.'</a></td>';
	  print '<td><a href="'.DOL_URL_ROOT.'/commande/fiche.php?id='.$obj->commande_id.'">'.$obj->commande_ref.'</a></td></tr>';
	  $i++;
	}
      print "</table><br>";
    }
}

print '</td></tr></table>';

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
