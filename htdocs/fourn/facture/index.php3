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
require("./pre.inc.php3");
require("../../contact.class.php3");


llxHeader();
$db = new Db();

if ($action == 'note') {
  $sql = "UPDATE societe SET note='$note' WHERE idp=$socid";
  $result = $db->query($sql);
}

if ($action == 'stcomm') {
  if ($stcommid <> 'null' && $stcommid <> $oldstcomm) {
    $sql = "INSERT INTO socstatutlog (datel, fk_soc, fk_statut, author) ";
    $sql .= " VALUES ('$dateaction',$socid,$stcommid,'" . $GLOBALS["REMOTE_USER"] . "')";
    $result = @$db->query($sql);

    if ($result) {
      $sql = "UPDATE societe SET fk_stcomm=$stcommid WHERE idp=$socid";
      $result = $db->query($sql);
    } else {
      $errmesg = "ERREUR DE DATE !";
    }
  }

  if ($actioncommid) {
    $sql = "INSERT INTO actioncomm (datea, fk_action, fk_soc, fk_user_author) VALUES ('$dateaction',$actioncommid,$socid,'" . $user->id . "')";
    $result = @$db->query($sql);

    if (!$result) {
      $errmesg = "ERREUR DE DATE !";
    }
  }
}


if ($action == 'delete') {
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
      $sql = "SELECT s.idp FROM societe as s ";
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
 *
 * Mode fiche
 *
 *
 */  
if ($socid > 0)
{

}
else
{
  /*
   * Mode Liste
   *
   *
   *
   */
  print_barre_liste("Liste des factures fournisseurs", $page, $PHP_SELF);

  if ($sortorder == "")
    {
      $sortorder="DESC";
    }
  if ($sortfield == "")
    {
      $sortfield="fac.paye ASC, fac.datef";
    }


  $sql = "SELECT s.idp as socid, s.nom, ".$db->pdate("s.datec")." as datec, ".$db->pdate("s.datea")." as datea,  s.prefix_comm, fac.total_ht, fac.paye, fac.libelle, ".$db->pdate("fac.datef")." as datef, fac.rowid as facid, fac.facnumber";
  $sql .= " FROM societe as s, llx_facture_fourn as fac ";
  $sql .= " WHERE fac.fk_soc = s.idp";

  $sql .= " ORDER BY $sortfield $sortorder " . $db->plimit( $limit, $offset);

  $result = $db->query($sql);

  if ($result) {
    $num = $db->num_rows();
    $i = 0;

    if ($sortorder == "DESC")
      {
	$sortorder="ASC";
      } else {
	$sortorder="DESC";
      }
    print "<p><TABLE border=\"0\" width=\"100%\" cellspacing=\"0\" cellpadding=\"4\">";
    print '<TR class="liste_titre">';
    print '<TD>Numéro</TD>';
    print '<TD>Libellé</TD><td>';
    print_liste_field_titre("Société",$PHP_SELF,"s.nom");
    print '</td><TD align="right">Montant</TD>';
    print '<td align="center">Payé</td>';
    print "</TR>\n";
    $var=True;
    while ($i < $num) {
      $obj = $db->fetch_object( $i);
      
      $var=!$var;

      print "<TR $bc[$var]>";
      print "<TD><a href=\"fiche.php3?facid=$obj->facid\">$obj->facnumber</A></td>\n";
      print "<TD><a href=\"fiche.php3?facid=$obj->facid\">$obj->libelle</A></td>\n";
      print "<TD><a href=\"../fiche.php3?socid=$obj->socid\">$obj->nom</A></td>\n";
      print '<TD align="right">'.price($obj->total_ht).'</TD>';

      print '<TD align="center">'.$yn[$obj->paye].'</TD>';


      print "</TR>\n";
      $i++;
    }
    print "</TABLE>";
    $db->free();
  } else {
    print $db->error();
  }
}
$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
