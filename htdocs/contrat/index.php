<?PHP
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

$db = new Db();


llxHeader();

if ($page == -1) { 
  $page = 0 ; 
}

$limit = $conf->liste_limit;
$offset = $limit * $page ;

if ($sortfield == "")
{
  $sortfield="c.tms";
}

if ($sortorder == "")
{
  $sortorder="DESC";
}

$sql = "SELECT s.nom, c.rowid as cid, c.enservice, p.label, p.rowid, s.idp as sidp";
$sql .= " FROM llx_contrat as c, llx_societe as s, llx_product as p";
$sql .= " WHERE c.fk_soc = s.idp AND c.fk_product = p.rowid";
$sql .= " ORDER BY $sortfield $sortorder ";
$sql .= $db->plimit($limit + 1 ,$offset);

if ( $db->query($sql) )
{
  $num = $db->num_rows();
  $i = 0;


  print_barre_liste("Liste des contrats", $page, $PHP_SELF, "&sref=$sref&snom=$snom", $sortfield, $sortorder,'',$num);

  print '<TABLE border="0" width="100%" cellspacing="0" cellpadding="4">';

  print '<TR class="liste_titre"><td>';
  print_liste_field_titre("Libellé",$PHP_SELF, "p.label");
  print "</td><td>";
  print_liste_field_titre("Société",$PHP_SELF, "s.nom");
  print '</td><td align="center">Etat</td>';
  print "</TR>\n";
    
  $var=True;
  while ($i < min($num,$limit))
    {
      $obj = $db->fetch_object( $i);
      $var=!$var;
      print "<TR $bc[$var]>";
      print "<TD><a href=\"fiche.php?id=$obj->cid\">$obj->label</a></td>\n";
      print "<TD><a href=\"../comm/fiche.php3?socid=$obj->sidp\">$obj->nom</a></TD>\n";
      print '<td align="center">';
      if ($obj->enservice == 1)
	{
	  print "En service</td>";
	}
      elseif($obj->enservice == 2)
	{
	  print "En service</td>";
	}
      else
	{
	  print "A mettre en service</td>";
	}
      print "</TR>\n";
      $i++;
    }
  $db->free();

  print "</table>";

}
else
{
  print $db->error() . "<br>" .$sql;
}


$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
