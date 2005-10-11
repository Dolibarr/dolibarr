<?PHP
/* Copyright (C) 2004-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
require("./pre.inc.php");


$page = $_GET["page"];
$sortorder = $_GET["sortorder"];
$sortfield = $_GET["sortfield"];

llxHeader('','Telephonie - Clients - Contacts');
/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $action = '';
  $socidp = $user->societe_id;
}

if ($sortorder == "") {
  $sortorder="ASC";
}
if ($sortfield == "") {
  $sortfield="s.nom";
}

/*
 * Recherche
 *
 *
 */

if ($page == -1) { $page = 0 ; }

$offset = $conf->liste_limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;
/*
 * Mode Liste
 *
 */
$sql = "SELECT distinct cont.email, cont.idp, cont.name, cont.firstname, s.nom, s.idp as socidp";
$sql .= " FROM ".MAIN_DB_PREFIX."societe as s";

$sql .= ",".MAIN_DB_PREFIX."societe_perms as sp";
$sql .= ",".MAIN_DB_PREFIX."telephonie_contrat_contact_facture as cf";
$sql .= ",".MAIN_DB_PREFIX."socpeople as cont";

$sql .= " WHERE cont.fk_soc = s.idp ";
$sql .= " AND cf.fk_contact = cont.idp";

$sql .= " AND s.idp = sp.fk_soc";
$sql .= " AND sp.fk_user = ".$user->id." AND sp.pread = 1";

if ($_GET["search_client"])
{
  $sel = urldecode($_GET["search_client"]);
  $sql .= " AND s.nom LIKE '%".$sel."%'";
}

if ($_GET["search_email"])
{
  $sel = urldecode($_GET["search_email"]);
  $sql .= " AND cont.email LIKE '%".$sel."%'";
}

//$sql .= " GROUP BY s.idp";
$sql .= " ORDER BY $sortfield $sortorder " . $db->plimit($conf->liste_limit+1, $offset);

$result = $db->query($sql);
if ($result)
{
  $num = $db->num_rows();
  $i = 0;
  
  $urladd= "&amp;statut=".$_GET["statut"];

  print_barre_liste("Clients", $page, "liste.php", $urladd, $sortfield, $sortorder, '', $num);

  print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
  print '<tr class="liste_titre">';
  print_liste_field_titre("Client","contacts.php","s.nom","","",' width="50%"');
  print '<td width="25%">Prénom Nom</td>';

  print_liste_field_titre("Email","contacts.php","cont.email","","",' width="50%"');
  print "</tr>\n";

  print '<tr class="liste_titre">';
  print '<form action="contacts.php" method="GET">';
  print '<td><input type="text" name="search_client" value="'. $_GET["search_client"].'" size="12"></td>';  
  print '<td>&nbsp;</td>';
  print '<td><input type="text" name="search_email" value="'. $_GET["search_email"].'" size="12">';  
  print '&nbsp;<input type="submit" class="button" value="'.$langs->trans("Search").'"></td>';

  print '</form>';
  print '</tr>';


  $var=True;

  $ligne = new LigneTel($db);

  while ($i < min($num,$conf->liste_limit))
    {
      $obj = $db->fetch_object($i);	
      $var=!$var;

      print "<tr $bc[$var]><td>";

      print '<a href="'.DOL_URL_ROOT.'/telephonie/client/fiche.php?id='.$obj->socidp.'">';
      print img_file();      
      print '</a>&nbsp;';
      print '<a href="'.DOL_URL_ROOT.'/telephonie/client/fiche.php?id='.$obj->socidp.'">'.$obj->nom.'</a></td>';
      print '<td>'.$obj->firstname.' '.$obj->name."</td>\n";

      print '<td><a href="'.DOL_URL_ROOT.'/contact/fiche.php?id='.$obj->idp.'">';
      print $obj->email."</a></td>\n";

      print "</tr>\n";
      $i++;
    }
  print "</table>";
  $db->free();
}
else 
{
  print $db->error() . ' ' . $sql;
}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
