<?PHP
/* Copyright (C) 2002-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
require("../../contact.class.php");

llxHeader();

/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $action = '';
  $socid = $user->societe_id;
}

if ($action == 'note')
{
  $sql = "UPDATE societe SET note='$note' WHERE idp=$socid";
  $result = $db->query($sql);
}

if ($action == 'delete')
{
  $fac = new FactureFourn($db);
  $fac->delete($facid);
  
  $facid = 0 ;
}


if ($page == -1) { $page = 0 ; }
$limit = $conf->liste_limit;
$offset = $limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;


/*
 * Recherche
 *
 *
 */
if ($mode == 'search')
{
  if ($mode-search == 'soc')
    {
      $sql = "SELECT s.idp FROM llx_societe as s ";
      $sql .= " WHERE lower(s.nom) like '%".strtolower($socname)."%'";
    }
      
  if ( $db->query($sql) )
    {
      if ( $db->num_rows() == 1)
	{
	  $obj = $db->fetch_object(0);
	  $socid = $obj->idp;
	}
      $db->free();
    }
}
  
/*
 * Mode Liste
 *
 */
print_barre_liste("Liste des factures fournisseurs", $page, $PHP_SELF);

if ($sortorder == "")
{
  $sortorder="DESC";
}
if ($sortfield == "")
{
  $sortfield="fac.datef";
}


$sql = "SELECT s.idp as socid, s.nom, ".$db->pdate("s.datec")." as datec, ".$db->pdate("s.datea")." as datea,  s.prefix_comm, fac.total_ht, fac.total_ttc, fac.paye, fac.libelle, ".$db->pdate("fac.datef")." as datef, fac.rowid as facid, fac.facnumber";
$sql .= " FROM llx_societe as s, llx_facture_fourn as fac ";
$sql .= " WHERE fac.fk_soc = s.idp";

if ($socid)
{
  $sql .= " AND s.idp = $socid";
}

$sql .= " ORDER BY $sortfield $sortorder " . $db->plimit( $limit, $offset);

$result = $db->query($sql);

if ($result)
{
  $num = $db->num_rows();
  $i = 0;
  
  if ($sortorder == "DESC")
    {
      $sortorder="ASC";
    }
  else
    {
      $sortorder="DESC";
    }
  print "<TABLE border=\"0\" width=\"100%\" cellspacing=\"0\" cellpadding=\"4\">";
  print '<TR class="liste_titre">';
  print '<TD>Numéro</TD>';
  print '<TD>';
  print_liste_field_titre("Date",$PHP_SELF,"fac.datef");
  print '</TD>';
  print '<TD>Libellé</TD>';
  print '<td>';
  print_liste_field_titre("Société",$PHP_SELF,"s.nom");
  print '</td>';
  print '<TD align="right">';
  print_liste_field_titre("Montant HT",$PHP_SELF,"fac.total_ht");
  print '</td>';
  print '<TD align="right">';
  print_liste_field_titre("Montant TTC",$PHP_SELF,"fac.total_ttc");
  print '</td>';
  print '<td align="center">Payé</td>';
  print "</TR>\n";
  $var=True;
  while ($i < $num)
    {
      $obj = $db->fetch_object( $i);
      
      $var=!$var;
      
      print "<TR $bc[$var]>";
      print "<TD><a href=\"fiche.php?facid=$obj->facid\">$obj->facnumber</A></td>\n";
      print "<TD>".strftime("%d %b %Y",$obj->datef)."</td>\n";
      print '<TD>'.stripslashes("$obj->libelle").'</td>';
      print "<TD><a href=\"../fiche.php?socid=$obj->socid\">$obj->nom</A></td>\n";
      print '<TD align="right">'.price($obj->total_ht).'</TD>';
	  print '<TD align="right">'.price($obj->total_ttc).'</TD>';
      print '<TD align="center">'.($obj->paye||$obj->total_ht==0?"":"<a class=\"impayee\" href=\"\">").($obj->total_ht==0?"brouillon":$yn[$obj->paye]).($obj->paye||$obj->total_ht==0?"":"</a>").'</TD>';


      print "</TR>\n";
      $i++;
    }
    print "</TABLE>";
    $db->free();
}
else
{
  print $db->error();
}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
