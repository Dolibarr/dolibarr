<?PHP
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

llxHeader();

if ($sortfield == "") {
  $sortfield="datec";
}
if ($sortorder == "") {
  $sortorder="DESC";
}


if ($page == -1) { $page = 0 ; }
$limit = $conf->liste_limit;
$offset = $limit * $page ;

print_barre_liste("Liste des Newsletter", $page, "index.php");

$sql = "SELECT rowid, email_subject, email_from_name, email_from_email, email_replyto, email_body, target, sql_target, status, date_send_request, date_send_begin, date_send_end, nbsent";
$sql .= " FROM ".MAIN_DB_PREFIX."newsletter";
$sql .= " ORDER BY $sortfield $sortorder ";
$sql .= $db->plimit( $limit ,$offset);
 
if ( $db->query($sql) )
{
  $num = $db->num_rows();
  $i = 0;
  print "<table class=\"noborder\" width=\"100%\" cellspacing=\"0\" cellpadding=\"3\">";
  print "<tr class=\"liste_titre\"><td>";
  print_liste_field_titre("Sujet","index.php", "email_subject");
  print "</td>";
  print '<td align="center">'.$langs->trans("Status").'</td>';
  print '<td align="center">Nb envois</td>';
  print "</tr>\n";
  $var=True;
  while ($i < $num)
    {
      $objp = $db->fetch_object( $i);
      $var=!$var;
      print "<tr $bc[$var]>";

      print '<td><a href="fiche.php?id='.$objp->rowid.'"><img src="/theme/'.$conf->theme.'/img/filenew.png" border="0" alt="Fiche"></a>&nbsp;';

      print "<a href=\"fiche.php?id=$objp->rowid\">$objp->email_subject</a></td>\n";
      print '<td align="center">'.$objp->status.'</td>';
      print '<td align="center">'.$objp->nbsent.'</td>';
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
