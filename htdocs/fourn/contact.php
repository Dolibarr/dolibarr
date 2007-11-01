<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2006 Regis Houssin        <regis@dolibarr.fr>
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
	    \file       htdocs/fourn/contact.php
        \ingroup    fournisseur
		\brief      Liste des contacts fournisseurs
		\version    $Revision$
*/

require("./pre.inc.php");

$langs->load("companies");

llxHeader();

/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $action = '';
  $socid = $user->societe_id;
}

$page=$_GET["page"];
$sortorder=$_GET["sortorder"];
$sortfield=$_GET["sortfield"];

if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="p.name";
if ($page == -1) { $page = 0 ; }
$limit = $conf->liste_limit;
$offset = $limit * $page ;


/*
 * Mode liste
 *
 */

$sql = "SELECT s.rowid as socid, s.nom, st.libelle as stcomm, p.rowid as cidp, p.name, p.firstname, p.email, p.phone";
if (!$user->rights->commercial->client->voir && !$socid) $sql .= ", sc.fk_soc, sc.fk_user ";
$sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."socpeople as p, ".MAIN_DB_PREFIX."c_stcomm as st";
if (!$user->rights->commercial->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql .= " WHERE s.fk_stcomm = st.id AND s.fournisseur = 1 AND s.rowid = p.fk_soc";
if (!$user->rights->commercial->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;

if (strlen($stcomm)) {
  $sql .= " AND s.fk_stcomm=$stcomm";
}

if (strlen($begin)) {
  $sql .= " AND p.name like '$begin%'";
}

if ($contactname) {
  $sql .= " AND p.name like '%".strtolower($contactname)."%'";
  $sortfield = "p.name";
  $sortorder = "ASC";
}

if ($socid) {
  $sql .= " AND s.rowid = ".$socid;
}

$sql .= " ORDER BY $sortfield $sortorder " . $db->plimit( $limit, $offset);

$result = $db->query($sql);
if ($result) {
  $num = $db->num_rows($result);
  
  print_barre_liste($langs->trans("ListOfContacts")." (".$langs->trans("Suppliers").")",$page, "contact.php", "",$sortfield,$sortorder,"",$num);
    
  print '<table class="liste" width="100%">';
  print '<tr class="liste_titre">';
  print_liste_field_titre($langs->trans("Lastname"),"contact.php","p.name", $begin, "", "", $sortfield);
  print_liste_field_titre($langs->trans("Firstname"),"contact.php","p.firstname", $begin, "", "", $sortfield);
  print_liste_field_titre($langs->trans("Company"),"contact.php","s.nom", $begin, "", "", $sortfield);
  print '<td class="liste_titre">'.$langs->trans("Email").'</td>';
  print '<td class="liste_titre">'.$langs->trans("Phone").'</td>';
  print "</tr>\n";
  $var=True;
  $i = 0;
  while ($i < min($num,$limit))
    {
      $obj = $db->fetch_object($result);
    
      $var=!$var;

      print "<tr $bc[$var]>";

      print '<td><a href="'.DOL_URL_ROOT.'/contact/fiche.php?id='.$obj->cidp.'">'.img_object($langs->trans("ShowContact"),"contact").' '.$obj->name.'</a></td>';
      print '<td>'.$obj->firstname.'</td>';
      print '<td><a href="'.DOL_URL_ROOT.'/fourn/fiche.php?socid='.$obj->socid.'">'.img_object($langs->trans("ShowCompany"),"company").' '.$obj->nom.'</a></td>';
      print '<td>'.$obj->email.'</td>';
      print '<td>'.$obj->phone.'</td>';
      
      print "</tr>\n";
      $i++;
    }
  print "</table>";
  $db->free($result);

}
else
{
  dolibarr_print_error($db);
}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
