<?php
/* Copyright (C) 2003-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2008 Regis Houssin        <regis@dolibarr.fr>
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
 */

/**
        \file       htdocs/expedition/index.php
        \ingroup    expedition
        \brief      Page accueil du module expedition
        \version    $Id$
*/

require("./pre.inc.php");

$langs->load("sendings");


/*
*	View
*/

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
$clause = " WHERE ";
$sql = "SELECT e.rowid, e.ref";
$sql.= ", s.nom, s.rowid as socid";
$sql.= ", c.ref as commande_ref, c.rowid as commande_id";
$sql.= " FROM ".MAIN_DB_PREFIX."expedition as e";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."co_exp as ce ON e.rowid = ce.fk_expedition";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."commande as c ON ce.fk_commande = c.rowid"; 
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = e.fk_soc";
if (!$user->rights->societe->client->voir && !$socid)
{
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON e.fk_soc = sc.fk_soc";
	$sql.= $clause." sc.fk_user = " .$user->id;
	$clause = " AND ";
}
$sql.= $clause." e.fk_statut = 0";
if ($socid)
{
    $sql .= " AND c.fk_soc = ".$socid;
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
            print "<tr $bc[$var]><td nowrap=\"nowrap\"><a href=\"fiche.php?id=".$obj->rowid."\">".$obj->ref."</a></td>";
            print '<td><a href="'.DOL_URL_ROOT.'/comm/fiche.php?socid='.$obj->socid.'">'.$obj->nom.'</a></td>';
            print '<td><a href="'.DOL_URL_ROOT.'/commande/fiche.php?id='.$obj->commande_id.'">'.$obj->commande_ref.'</a></td></tr>';
            $i++;
        }
        print "</table><br>";
    }
}


/*
 * Commandes à traiter
 */
$sql = "SELECT c.rowid, c.ref, s.nom, s.rowid as socid";
if (!$user->rights->societe->client->voir && !$socid) $sql .= ", sc.fk_soc, sc.fk_user";
$sql.= " FROM ".MAIN_DB_PREFIX."commande as c, ".MAIN_DB_PREFIX."societe as s";
if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql.= " WHERE c.fk_soc = s.rowid AND c.fk_statut = 1";
if ($socid) $sql .= " AND c.fk_soc = ".$socid;
if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
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
	  print "<tr $bc[$var]>";
	  print '<td nowrap="nowrap">';
	  print "<a href=\"commande.php?id=".$obj->rowid."\">".img_object($langs->trans("ShowOrder"),"order").' '.$obj->ref.'</a></td>';
	  print '<td><a href="'.DOL_URL_ROOT.'/comm/fiche.php?socid='.$obj->socid.'">'.dolibarr_trunc($obj->nom,20).'</a></td></tr>';
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
$sql = "SELECT c.rowid, c.ref, s.nom, s.rowid as socid";
if (!$user->rights->societe->client->voir && !$socid) $sql .= ", sc.fk_soc, sc.fk_user";
$sql .= " FROM ".MAIN_DB_PREFIX."commande as c, ".MAIN_DB_PREFIX."societe as s";
if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql .= " WHERE c.fk_soc = s.rowid AND c.fk_statut = 2";
if ($socid) $sql .= " AND c.fk_soc = ".$socid;
if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
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
	  print "<tr $bc[$var]><td width=\"30%\"><a href=\"commande.php?id=".$obj->rowid."\">".img_object($langs->trans("ShowOrder"),"order").' ';
	  print $obj->ref.'</a></td>';
	  print '<td><a href="'.DOL_URL_ROOT.'/comm/fiche.php?socid='.$obj->socid.'">'.$obj->nom.'</a></td></tr>';
	  $i++;
	}
      print "</table><br>";
    }
}


/*
 * Expeditions à valider
 */
$clause = " WHERE ";
$sql = "SELECT e.rowid, e.ref";
$sql.= ", s.nom, s.rowid as socid";
$sql.= ", c.ref as commande_ref, c.rowid as commande_id";
$sql.= " FROM ".MAIN_DB_PREFIX."expedition as e";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."co_exp as ce ON e.rowid = ce.fk_expedition";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."commande as c ON ce.fk_commande = c.rowid"; 
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = e.fk_soc";
if (!$user->rights->societe->client->voir && !$socid)
{
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON e.fk_soc = sc.fk_soc";
	$sql.= $clause." sc.fk_user = " .$user->id;
	$clause = " AND ";
}
$sql.= $clause." e.fk_statut = 1";
if ($socid) $sql .= " AND c.fk_soc = ".$socid;
$sql.= " ORDER BY e.date_expedition DESC";
$sql.= $db->plimit(5, 0);

$resql = $db->query($sql);
if ($resql) 
{
  $num = $db->num_rows($resql);
  if ($num)
  {
  	$i = 0;
    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre">';
    print '<td colspan="3">'.$langs->trans("LastSendings",$num).'</td></tr>';
    $var = True;
    while ($i < $num)
    {
    	$var=!$var;
    	$obj = $db->fetch_object($resql);
    	print "<tr $bc[$var]><td width=\"20%\"><a href=\"fiche.php?id=$obj->rowid\">".img_object($langs->trans("ShowSending"),"sending").' ';
    	print $obj->ref.'</a></td>';
    	print '<td><a href="'.DOL_URL_ROOT.'/comm/fiche.php?socid='.$obj->socid.'">'.img_object($langs->trans("ShowCompany"),"company").' '.$obj->nom.'</a></td>';
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
