<?PHP
/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 *
 * $Id$
 * $Source$
 */
require("./pre.inc.php3");
require("./project.class.php3");


llxHeader("","../");
print '<table width="100%">';
print '<tr><td>Projets</td>';
if($socidp) {
  print '<td>[<a href="fiche.php3?socidp='.$socidp.'&action=create">Nouveau projet</a>]</td>';
}
print '</tr></table>';



$db = new Db();
/*
 * Traitements des actions
 *
 */
if ($action == 'create') {

  $pro = new Project($db);
  $pro->socidp = $socidp;
  $pro->ref = $ref;
  $pro->title = $title;

  $pro->create( $user->id);
}



/*
 *
 * Affichage
 *
 */
if ($sortfield == "") {
  $sortfield="lower(p.label)";
}
if ($sortorder == "") {
  $sortorder="ASC";
}

$yn["t"] = "oui";
$yn["f"] = "non";

if ($page == -1) { $page = 0 ; }
$limit = 26;
$offset = $limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;


/*
 *
 *
 * Liste des projets
 *
 * 
 */

print "<P>";
$sql = "SELECT s.nom, s.idp, p.rowid as projectid, p.ref, p.title,".$db->pdate("p.dateo")." as do";
$sql .= " FROM societe as s, llx_projet as p";
$sql .= " WHERE p.fk_soc = s.idp";

if ($socidp) { $sql .= " AND s.idp = $socidp"; }

if ( $db->query($sql) ) {
  $num = $db->num_rows();
  $i = 0;
  print "<p><TABLE border=\"0\" width=\"100%\" cellspacing=\"0\" cellpadding=\"4\">";
  
  $oldstatut = -1;
  $subtotal = 0;
  while ($i < $num) {
    $objp = $db->fetch_object( $i);
    
    if ($objp->statut <> $oldstatut ) {
      $oldstatut = $objp->statut;
      
      if ($i > 0) {
	print "<tr><td align=\"right\" colspan=\"6\">Total : <b>".price($subtotal)."</b></td>\n";
	print "<td align=\"left\">Euros HT</td></tr>\n";
      }
      $subtotal = 0;
      
      print "<TR bgcolor=\"#e0e0e0\">";
      print "<TD>[<a href=\"$PHP_SELF\">Tous</a>]</td>";
      print "<TD><a href=\"$PHP_SELF?sortfield=lower(p.label)&sortorder=ASC\">Societe</a></td>";
      print "<TD>Réf</TD><td>Titre</td>";
      print "<TD align=\"right\" colspan=\"2\">Date</TD>";
      print "<TD align=\"center\">Statut [<a href=\"$PHP_SELF?viewstatut=$objp->statutid\">Filtre</a>]</TD>";
      print "</TR>\n";
      $var=True;
    }
    
    $var=!$var;
    print "<TR $bc[$var]>";
    print "<TD>[<a href=\"$PHP_SELF?socidp=$objp->idp\">Filtre</a>]</TD>\n";
    print "<TD><a href=\"../fiche.php3?socid=$objp->idp\">$objp->nom</a></TD>\n";
    print "<TD><a href=\"fiche.php3?id=$objp->projectid\">$objp->ref</a></TD>\n";
    print "<TD><a href=\"fiche.php3?id=$objp->projectid\">$objp->title</a></TD>\n";
    print "<td>&nbsp;</td>";
    print "</TR>\n";
    
    $i++;
  }

  print "</TABLE>";
  $db->free();
} else {
  print $db->error();
}

$db->close();
llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
