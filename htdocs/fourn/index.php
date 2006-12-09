<?php
/* Copyright (C) 2001-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2006 Regis Houssin        <regis.houssin@cap-networks.com>
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
   \file       htdocs/fourn/index.php
   \ingroup    fournisseur
   \brief      Page accueil de la zone fournisseurs
   \version    $Revision$
*/

require("./pre.inc.php");

if (!$user->rights->societe->lire)
  accessforbidden();

$langs->load("suppliers");
$langs->load("orders");
$langs->load("companies");

llxHeader();

// Sécurité accés client
$socid='';
if ($user->societe_id > 0) 
{
  $action = '';
  $socid = $user->societe_id;
}

print '<table border="0" width="100%" class="notopnoleftnoright">';
print '<tr><td valign="top" width="30%" class="notopnoleft">';

/*
 * Liste des categories
 *
 */
$sql = "SELECT rowid, label";
$sql.= " FROM ".MAIN_DB_PREFIX."fournisseur_categorie";
$sql .= " ORDER BY label ASC";

$resql = $db->query($sql);
if ($resql)
{
  $num = $db->num_rows($resql);
  $i = 0;
  
  print '<table class="liste" width="100%">';
  print '<tr class="liste_titre"><td>';
  print $langs->trans("Category");
  print "</td></tr>\n";

  $var=True;

  while ($obj = $db->fetch_object($resql))
    {
      $var=!$var;
      print "<tr $bc[$var]>\n";
      print '<td><a href="liste.php?cat='.$obj->rowid.'">'.stripslashes($obj->label).'</a>';
      print "</td>\n";
      print "</tr>\n";
    }
  print "</table>\n";

  $db->free($resql);
}
else 
{
  dolibarr_print_error($db);
}
print "</td>\n";
print '<td valign="top" width="70%" class="notopnoleft">';

/*
 * Liste des 10 derniers saisis
 *
 */
$sql = "SELECT s.idp, s.nom, s.ville,".$db->pdate("s.datec")." as datec, ".$db->pdate("s.datea")." as datea,  st.libelle as stcomm, s.prefix_comm";
$sql.= " , code_fournisseur, code_compta_fournisseur";
if (!$user->rights->commercial->client->voir && !$socid) $sql .= ", sc.fk_soc, sc.fk_user ";
$sql.= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."c_stcomm as st";
if (!$user->rights->commercial->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql.= " WHERE s.fk_stcomm = st.id AND s.fournisseur=1";
if (!$user->rights->commercial->client->voir && !$socid) $sql .= " AND s.idp = sc.fk_soc AND sc.fk_user = " .$user->id;
if ($socid) $sql .= " AND s.idp=".$socid;

$sql .= " ORDER BY s.datec DESC LIMIT 10; ";

$resql = $db->query($sql);
if ($resql)
{
  $num = $db->num_rows($resql);
  $i = 0;
  
  print '<table class="liste" width="100%">';
  print '<tr class="liste_titre">';
  print "<td>".$langs->trans("Company")."</td>\n";
  print "<td>".$langs->trans("SupplierCode")."</td>\n";
  print "<td>".$langs->trans("DateCreation")."</td>\n";
  print "</tr>\n";

  $var=True;

  while ($obj = $db->fetch_object($resql) )
    {
      $var=!$var;

      print "<tr $bc[$var]>";
      print '<td><a href="fiche.php?socid='.$obj->idp.'">'.img_object($langs->trans("ShowSupplier"),"company").'</a>';
      print "&nbsp;<a href=\"fiche.php?socid=$obj->idp\">$obj->nom</a></td>\n";
      print '<td align="left">'.$obj->code_fournisseur.'&nbsp;</td>';
      print '<td align="center">'.dolibarr_print_date($obj->datec).'</td>';
      print "</tr>\n";
    }
  print "</table>\n";

  $db->free($resql);
}
else 
{
  dolibarr_print_error($db);
}

print "</td></tr>\n";
print "</table>\n";

$db->close();

llxFooter('$Date$ - $Revision$');
?>
