<?PHP
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
  $sortfield="c.fin_validite";
}

if ($sortorder == "")
{
  $sortorder="ASC";
}

$sql = "SELECT s.nom, c.rowid as cid, c.enservice, p.label, p.rowid, s.idp as sidp";
$sql .= " ,".$db->pdate("c.fin_validite")." as date_fin_validite, c.fin_validite-sysdate() as delairestant ";
$sql .= " FROM ".MAIN_DB_PREFIX."contrat as c, ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."product as p";
$sql .= " WHERE c.fk_soc = s.idp AND c.fk_product = p.rowid AND c.enservice = 1";
if ($socid > 0)
{
  $sql .= " AND s.idp = $socid";
}
$sql .= " ORDER BY $sortfield $sortorder, delairestant ";
$sql .= $db->plimit($limit + 1 ,$offset);

if ( $db->query($sql) )
{
  $num = $db->num_rows();
  $i = 0;


  print_barre_liste("Liste des contrats en service", $page, "enservice.php", "&sref=$sref&snom=$snom", $sortfield, $sortorder,'',$num);

  print '<table class="noborder" width="100%" cellspacing="0" cellpadding="3">';

  print '<tr class="liste_titre"><td>';
  print_liste_field_titre($langs->trans("Label"),"enservice.php", "p.label");
  print "</td><td>";
  print_liste_field_titre("Société","enservice.php", "s.nom");
  print "</td>";
  print "<td align=\"center\">".$langs->trans("Status")."</td>";
  print "<td align=\"center\">";
  print_liste_field_titre("Date fin","enservice.php", "date_fin_validite");
  print '</td>';
  print "</tr>\n";

  $now=mktime();
  $var=True;
  while ($i < min($num,$limit))
    {
      $obj = $db->fetch_object( $i);
      $var=!$var;
      print "<tr $bc[$var]>";
      print "<td><a href=\"fiche.php?id=$obj->cid\">$obj->label</a></td>\n";
      print "<td><a href=\"../comm/fiche.php?socid=$obj->sidp\">$obj->nom</a></td>\n";

      // Affiche statut contrat
      if ($obj->enservice == 1)
	{
        if (! $obj->date_fin_validite || $obj->date_fin_validite >= $now) {
      	  $class = "normal";
    	  $statut="En service";
        }
        else {            
      	  $class = "error";
    	  $statut="<b>En service, expiré</b>";
        }
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
	print "</td>";

      print '<td align="center">'.dolibarr_print_date($obj->date_fin_validite).'</td>';

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
