<?PHP
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
require("./pre.inc.php");

llxHeader();

if ($page == -1) { 
  $page = 0 ; 
}
/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $action = '';
  $socid = $user->societe_id;
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

$sql = "SELECT c.rowid as cid, c.enservice, c.fin_validite, p.label, p.rowid as pid, s.nom, s.idp as sidp";
$sql .= " FROM ".MAIN_DB_PREFIX."contrat as c, ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."product as p";
$sql .= " WHERE c.fk_soc = s.idp AND c.fk_product = p.rowid";
if ($socid > 0)
{
  $sql .= " AND s.idp = $socid";
}
$sql .= " ORDER BY $sortfield $sortorder ";
$sql .= $db->plimit($limit + 1 ,$offset);

if ( $db->query($sql) )
{
  $num = $db->num_rows();
  $i = 0;


  print_barre_liste("Liste des contrats", $page, $PHP_SELF, "&sref=$sref&snom=$snom", $sortfield, $sortorder,'',$num);

  print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';

  print '<tr class="liste_titre">';
  print '<td>';
  print_liste_field_titre("Numéro",$PHP_SELF, "c.rowid");
  print "</td><td>";
  print_liste_field_titre("Libellé",$PHP_SELF, "p.label");
  print "</td><td>";
  print_liste_field_titre("Société",$PHP_SELF, "s.nom");
  print '</td><td align="center">';
  print_liste_field_titre("Statut",$PHP_SELF, "c.enservice");
  print '</td>';
  print '</td><td align="center">';
  print_liste_field_titre("Fin",$PHP_SELF, "c.fin_validite");
  print '</td>';
  print "</tr>\n";
    
  $var=True;
  while ($i < min($num,$limit))
    {
      $obj = $db->fetch_object( $i);
      $var=!$var;
      print "<tr $bc[$var]>";
      print "<td><a href=\"fiche.php?id=$obj->cid\">$obj->cid</a></td>\n";
      print "<td><a href=\"../product/fiche.php?id=$obj->pid\">$obj->label</a></td>\n";
      print "<td><a href=\"../comm/fiche.php?socid=$obj->sidp\">$obj->nom</a></td>\n";

      // Affiche statut contrat
      if ($obj->enservice == 1)
	{
  	  $class = "impayee";
	  $statut="En service";
	}
      elseif($obj->enservice == 2)
	{
   	  $class = "normal";
	  $statut= "Cloturé";
	}
      else
	{
  	  $class = "warning";
	  $statut= "A mettre en service";
	}
    print "<td align=\"center\" class=\"$class\">";
    print "$statut";
	print "</td><td>";

    if ($obj->enservice > 0) {
	    $time=strtotime($obj->fin_validite);
        print dolibarr_print_date($time);
    }
    else {
        print "&nbsp;";   
    }
    print "</td>\n";

    print "</tr>\n";
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
