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
require("./pre.inc.php3");

require("../lib/CMailFile.class.php3");
/*
 *  Modules optionnels
 */
require("../project.class.php3");
require("../propal.class.php3");
require("../actioncomm.class.php3");
/*
 *
 */

llxHeader();

$db = new Db();

if ($action == 'setstatut')
{
  /*
   *  Classée la facture comme facturée
   */
  $propal = new Propal($db);
  $propal->id = $propalid;
  $propal->cloture($user->id, $statut, $note);

}

if ( $action == 'delete' )
{
  $sql = "DELETE FROM llx_propal WHERE rowid = $propalid;";
  if ( $db->query($sql) )
    {

      $sql = "DELETE FROM llx_propaldet WHERE fk_propal = $propalid ;";
      if ( $db->query($sql) )
	{
	  print "<b><font color=\"red\">Propal supprimée</font></b>";
	}
      else
	{
	  print $db->error();
	  print "<p>$sql";
	} 
    }
  else
    {
      print $db->error();
      print "<p>$sql";
    }
  $propalid = 0;
  $brouillon = 1;
}

/*
 *
 * Mode fiche
 *
 *
 */
if ($propalid)
{
  $propal = new Propal($db);
  $propal->fetch($propalid);

  if ($valid == 1)
    {
      $propal->valid($user->id);
    }
  /*
   *
   */
  print "<table width=\"100%\">";
  print "<tr><td><div class=\"titre\">Proposition commerciale : $propal->ref</div></td>";
  print "</table>";
  /*
   *
   */
  $sql = "SELECT s.nom, s.idp, p.price, p.fk_projet,p.remise, p.tva, p.total, p.ref,".$db->pdate("p.datep")." as dp, c.id as statut, c.label as lst, p.note, x.firstname, x.name, x.fax, x.phone, x.email, p.fk_user_author, p.fk_user_valid, p.fk_user_cloture, p.datec, p.date_valid, p.date_cloture";
  $sql .= " FROM llx_societe as s, llx_propal as p, c_propalst as c, llx_socpeople as x";
  $sql .= " WHERE p.fk_soc = s.idp AND p.fk_statut = c.id AND x.idp = p.fk_soc_contact AND p.rowid = $propalid";

  $result = $db->query($sql);

  if ( $result ) {
    $obj = $db->fetch_object( 0 );
    
    if ($db->num_rows()) {
            
      $color1 = "#e0e0e0";

      print '<table border="1" cellspacing="0" cellpadding="2" width="100%">';

      print '<tr><td>Société</td><td colspan="2"><a href="fiche.php3?socid='.$obj->idp.'">'.$obj->nom.'</a></td>';
      print "<td valign=\"top\" width=\"50%\" rowspan=\"8\">Note :<br>". nl2br($obj->note)."</td></tr>";
      //

      print '<tr><td>'.translate("Date").'</td><td colspan="2">'.strftime("%A %d %B %Y",$obj->dp).'</td></tr>';

      if ($obj->fk_projet) 
	{
	  $projet = new Project($db);
	  $projet->fetch($obj->fk_projet); 
	  print '<tr><td>Projet</td><td colspan="1">';
	  print '<a href="projet/fiche.php3?id='.$projet->id.'">';
	  print $projet->title.'</a></td></tr>';
	}
      print "<tr><td>Destinataire</td><td colspan=\"2\">$obj->firstname $obj->name &lt;$obj->email&gt;</td></tr>";
      /*
       *
       */

      print "<tr><td bgcolor=\"$color1\">Montant HT</td><td colspan=\"2\" bgcolor=\"$color1\" align=\"right\">".price($obj->price)." euros</td></tr>";
      /*
       *
       */

      print "<tr><td bgcolor=\"$color1\">Remise</td><td colspan=\"2\" bgcolor=\"$color1\" align=\"right\">".price($obj->remise)." euros</td></tr>";

      /*
       *
       */

      $totalht = $propal->price - $propal->remise ;

      print "<tr><td bgcolor=\"$color1\">Total HT</td><td colspan=\"2\" bgcolor=\"$color1\" align=\"right\"><b>".price($totalht)."</b> euros</td></tr>";
      /*
       *
       */
      print '<tr><td>Auteur</td><td colspan="2">';
      $author = new User($db, $obj->fk_user_author);
      $author->fetch('');
      print $author->fullname.'</td></tr>';
      /*
       *
       */
      print "<tr><td>PDF</a></td>";
      $file = PROPALE_OUTPUTDIR. "/$obj->ref/$obj->ref.pdf";
      if (file_exists($file)) {
	print '<td colspan="2"><a href="'.PROPALE_OUTPUT_URL.'/'.$obj->ref.'/'.$obj->ref.'.pdf">'.$obj->ref.'.pdf</a></td></tr>';
      }
      print '</tr>';
      /*
       *
       */
      print "<tr bgcolor=\"#f0f0f0\"><td>Statut :</td><td colspan=2 align=center><b>$obj->lst</b></td>";

      print '</tr>';


      print "</table>";

      if ($action == 'statut')
	{
	  print "<form action=\"$PHP_SELF?propalid=$propalid\" method=\"post\">";
	  print "<input type=\"hidden\" name=\"action\" value=\"setstatut\">";
	  print "<select name=\"statut\">";
	  print "<option value=\"2\">Signée";
	  print "<option value=\"3\">Non Signée";
	  print '</select>';
	  print '<br><textarea cols="60" rows="6" wrap="soft" name="note">';
	  print $obj->note . "\n----------\n";
	  print '</textarea><br><input type="submit" value="Valider">';
	  print "</form>";
	}
      

      print "<table width=\"100%\" cellspacing=2>";
      /*
       *
       */
      print "<td valign=\"top\" width=\"50%\">";
      /*
       * Factures associees
       */
      $sql = "SELECT f.facnumber, f.amount,".$db->pdate("f.datef")." as df, f.rowid as facid, f.fk_user_author, f.paye";
      $sql .= " FROM llx_facture as f, llx_fa_pr as fp WHERE fp.fk_facture = f.rowid AND fp.fk_propal = $propalid";

      $result = $db->query($sql);
      if ($result)
	{
	  $num = $db->num_rows();
	  $i = 0; $total = 0;
	  print "<br>";
	  if ($num > 1)
	    {
	      print_titre("Factures associées");
	    }
	  else
	    {
	      print_titre("Facture associée");
	    }
	  print '<table border="1" width="100%" cellspacing="0" cellpadding="3">';
	  print "<tr>";
	  print "<td>Num</td>";
	  print "<td>Date</td>";
	  print "<td>Auteur</td>";
	  print '<td align="right">Prix</td>';
	  print "</tr>\n";
	  
	  $var=True;
	  while ($i < $num)
	    {
	      $objp = $db->fetch_object( $i);
	      $var=!$var;
	      print "<TR bgcolor=\"#e0e0e0\">";
	      print "<TD><a href=\"../compta/facture.php3?facid=$objp->facid\">$objp->facnumber</a>";
	      if ($objp->paye)
		{ 
		  print " (<b>pay&eacute;e</b>)";
		} 
	      print "</TD>\n";
	      print "<TD>".strftime("%d %B %Y",$objp->df)."</td>\n";
	      if ($objp->fk_user_author <> $user->id)
		{
		  $fuser = new User($db, $objp->fk_user_author);
		  $fuser->fetch();
		  print "<td>".$fuser->fullname."</td>\n";
		}
	      else
		{
		  print "<td>".$user->fullname."</td>\n";
		}
	      print '<TD align="right">'.price($objp->amount).'</TD>';
	      print "</tr>";
	      $total = $total + $objp->amount;
	      $i++;
	    }
	  print "<tr><td align=\"right\" colspan=\"4\">Total : <b>$total</b> Euros HT</td></tr>\n";
	  print "</table>";
	  $db->free();
	}
      print "</table>";
      /*
       * Actions
       */
      print "<p><TABLE border=\"1\" width=\"100%\" cellspacing=\"0\" cellpadding=\"4\"><tr>";
  

      if ($obj->statut == 2)
	{
	  print '<td bgcolor="#e0e0e0" align="center" width="25%">';
	  print "<a href=\"facture.php3?propalid=$propalid&action=create\">Emettre une facture</td>";
	}
      else
	{
	  print '<td align="center" width="25%">-</td>';
	}
      
      print "<td align=\"center\" width=\"25%\">-</td>";
      print "<td align=\"center\" width=\"25%\">-</td>";


      if ($obj->statut == 2)
	{
	  print "<td bgcolor=\"#e0e0e0\" align=\"center\" width=\"25%\">[<a href=\"$PHP_SELF?propalid=$propalid&action=setstatut&statut=4\">Facturée</a>]</td>";
	}
      else	
	{
	  print "<td align=\"center\" width=\"25%\">-</td>";
	}
      print "</tr></table>";
      /*
       *
       */
      
    } else {
      print "Num rows = " . $db->num_rows();
      print "<p><b>$sql";
    }
    /*
     * Voir le suivi des actions
     *
     *
     *
     */
    if ($suivi) {
      $validor = new User($db, $obj->fk_user_valid);
      $validor->fetch('');
      $cloturor = new User($db, $obj->fk_user_cloture);
      $cloturor->fetch('');
      
      print '<p><a href="'.$PHP_SELF.'?propalid='.$propal->id.'">Cacher le suivi des actions </a>';
      print '<table cellspacing=0 border=1 cellpadding=3>';
      print '<tr><td>&nbsp;</td><td>Nom</td><td>Date</td></tr>';
      print '<tr><td>Création</td><td>'.$author->fullname.'</td>';
      print '<td>'.$obj->datec.'</td></tr>';

      print '<tr><td>Validation</td><td>'.$validor->fullname.'&nbsp;</td>';
      print '<td>'.$obj->date_valid.'&nbsp;</td></tr>';
      
      print '<tr><td>Cloture</td><td>'.$cloturor->fullname.'&nbsp;</td>';
      print '<td>'.$obj->date_cloture.'&nbsp;</td></tr>';      
      print '</table>';
    } else {
      print '<p><a href="'.$PHP_SELF.'?propalid='.$propal->id.'&suivi=1">Voir le suivi des actions </a>';
    }

  } else {
    print $db->error();
    print "<p><b>$sql";
  }


  /*
   *
   *
   *
   */
} else {
  /*
   *
   * Mode Liste des propales
   *
   */

  if ($sortfield == "")
    {
      $sortfield="p.datep";
    }
  if ($sortorder == "")
    {
      $sortorder="DESC";
    }

  if ($page == -1)
    {
      $page = 0 ;
    }

  $pageprev = $page - 1;
  $pagenext = $page + 1;
  $limit = $conf->liste_limit;
  $offset = $limit * $page ;

  $sql = "SELECT s.nom, s.idp, p.rowid as propalid, p.price - p.remise as price, p.ref,".$db->pdate("p.datep")." as dp, c.label as statut, c.id as statutid";
  $sql .= " FROM llx_societe as s, llx_propal as p, c_propalst as c ";
  $sql .= " WHERE p.fk_soc = s.idp AND p.fk_statut = c.id AND p.fk_statut in(2,4)";

  if ($socidp)
    { 
      $sql .= " AND s.idp = $socidp"; 
    }

  if ($viewstatut <> '')
    {
      $sql .= " AND c.id = $viewstatut"; 
    }

  if ($month > 0)
    {
      $sql .= " AND date_format(p.datep, '%Y-%m') = '$year-$month'";
    }
  
  if ($year > 0)
    {
      $sql .= " AND date_format(p.datep, '%Y') = $year";
    }
  
  $sql .= " ORDER BY $sortfield $sortorder, p.rowid DESC ";
  $sql .= $db->plimit($limit + 1,$offset);

  if ( $db->query($sql) )
    {
      $num = $db->num_rows();

      print_barre_liste("Propositions commerciales", $page, $PHP_SELF,"&socidp=$socidp",$sortfield,$sortorder,'',$num);

      $i = 0;
      print "<TABLE border=\"0\" width=\"100%\" cellspacing=\"0\" cellpadding=\"4\">";
      print '<TR class="liste_titre">';
      print_liste_field_titre_new ("Réf",$PHP_SELF,"p.ref","","&year=$year&viewstatut=$viewstatut",'',$sortfield);
      print_liste_field_titre_new ("Société",$PHP_SELF,"s.nom","&viewstatut=$viewstatut","",'',$sortfield);
      print_liste_field_titre_new ("Date",$PHP_SELF,"p.datep","&viewstatut=$viewstatut","",'align="right" colspan="2"',$sortfield);
      print_liste_field_titre_new ("Prix",$PHP_SELF,"p.price","&viewstatut=$viewstatut","",'align="right"',$sortfield);
      print_liste_field_titre_new ("Statut",$PHP_SELF,"p.fk_statut","&viewstatut=$viewstatut","",'align="center"',$sortfield);
      print "</tr>\n";

      while ($i < min($num, $limit))
	{
	  $objp = $db->fetch_object( $i);
	
	  $var=!$var;
	  print "<TR $bc[$var]>";
	  print "<TD><a href=\"$PHP_SELF?propalid=$objp->propalid\">$objp->ref</a></TD>\n";
	  print "<TD><a href=\"fiche.php3?socid=$objp->idp\">$objp->nom</a></TD>\n";      
	  
	  $now = time();
	  $lim = 3600 * 24 * 15 ;
	  
	  if ( ($now - $objp->dp) > $lim && $objp->statutid == 1 )
	    {
	      print "<td><b> &gt; 15 jours</b></td>";
	    }
	  else
	    {
	      print "<td>&nbsp;</td>";
	    }
	
	  print "<TD align=\"right\">";
	  $y = strftime("%Y",$objp->dp);
	  $m = strftime("%m",$objp->dp);
	  
	  print strftime("%d",$objp->dp)."\n";
	  print " <a href=\"propal.php3?year=$y&month=$m\">";
	  print strftime("%B",$objp->dp)."</a>\n";
	  print " <a href=\"propal.php3?year=$y\">";
	  print strftime("%Y",$objp->dp)."</a></TD>\n";      
	  
	  print "<TD align=\"right\">".price($objp->price)."</TD>\n";
	  print "<TD align=\"center\">$objp->statut</TD>\n";
	  print "</TR>\n";
	  
	  $i++;
	}
      
      print "</table>";
      $db->free();
    }
  else
    {
      print $db->error();
      print "<br>$sql";
    }
}
$db->close();
llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
