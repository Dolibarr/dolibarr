<?PHP
/* Copyright (C) 2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

if ($user->societe_id > 0)
{
  $action = '';
  $socid = $user->societe_id;
}

llxHeader();

$mesg = '';

/*
 *
 *
 */

if ($_GET["id"])
{
  $product = new Product($db);
  $result = $product->fetch($_GET["id"]);
  
  if ( $result )
    { 
      print_fiche_titre('Fiche produit : '.$product->ref, $mesg);
      
      print '<table class="border" width="100%" cellspacing="0" cellpadding="4"><tr>';
      print '<td width="20%">Référence</td><td width="40%"><a href="../fiche.php?id='.$product->id.'">'.$product->ref.'</a></td>';
      print '<td><a href="fiche.php?id='.$product->id.'">Statistiques</a></td></tr>';
      print "<tr><td>Libellé</td><td>$product->libelle</td>";
      print '<td valign="top" rowspan="2">';
      print "Propositions commerciales : ".$product->count_propale($socid);
      print "<br>Proposé à <b>".$product->count_propale_client($socid)."</b> clients";
      print "<br>Factures : ".$product->count_facture($socid);
      print '</td></tr>';
      print '<tr><td>Prix de vente</td><td>'.price($product->price).'</td></tr>';
      print "</table>";

      if ($page == -1)
	{
	  $page = 0 ;
	}
      $limit = $conf->liste_limit;
      $offset = $limit * $page ;
      
      if ($sortorder == "")
	{
	  $sortorder="DESC";
	}
      if ($sortfield == "")
	{
	  $sortfield="p.datep";
	}
      
      print_barre_liste("Propositions commerciales",$page,"propal.php","&amp;id=$product->id",$sortfield,$sortorder);
      
      $sql = "SELECT distinct(p.rowid), s.nom,s.idp, p.ref,".$db->pdate("p.datep")." as df,p.rowid as facid";
      $sql .= " FROM ".MAIN_DB_PREFIX."societe as s,".MAIN_DB_PREFIX."propal as p, ".MAIN_DB_PREFIX."propaldet as d WHERE p.fk_soc = s.idp";
      $sql .= " AND d.fk_propal = p.rowid AND d.fk_product =".$product->id;
 
      if ($socid)
	{
	  $sql .= " AND p.fk_soc = $socid";
	}

     $sql .= " ORDER BY $sortfield $sortorder ";
      $sql .= $db->plimit( $limit ,$offset);

      $result = $db->query($sql);
      if ($result)
	{
	  $num = $db->num_rows();
    
	  $i = 0;
	  print "<TABLE border=\"0\" width=\"100%\" cellspacing=\"0\" cellpadding=\"4\">";
	  print '<TR class="liste_titre">';
	  print '<TD>Num&eacute;ro</TD>';
	  print '<td>';
	  print_liste_field_titre("Société","propal.php","s.nom","","&amp;id=$product->id&amp;socidp=$socidp");
	  print '</td><TD align="right">';
	  print_liste_field_titre("Date","propal.php","f.datef","","&amp;id=$product->id&amp;socidp=$socidp");
	  print '</td>';
	  print "</TR>\n";
	  
	  if ($num > 0)
	    {
	      $var=True;
	      while ($i < $num)
		{
		  $objp = $db->fetch_object( $i);
		  $var=!$var;
		  
		  print "<TR $bc[$var]>";
		  print '<td><a href="'.DOL_URL_ROOT.'/comm/propal.php?propalid='.$objp->facid.'">';
		  print img_file();
		  print '</a>&nbsp;<a href="'.DOL_URL_ROOT.'/comm/propal.php?propalid='.$objp->facid.'">';
		  print $objp->ref;
		  print "</a></td>\n";
		  print '<td><a href="'.DOL_URL_ROOT.'/comm/fiche.php?socid='.$objp->idp.'">'.$objp->nom.'</a></TD>';
		  print "<TD align=\"right\">";
		  print strftime("%d %B %Y",$objp->df)."</td>";
		  print "</tr>\n";
		  $i++;
		}
	    }
	}
      else
	{
	  print $db->error();
	}
      print "</table>";
      $db->free();
    }
}
else
{
  print "Error";
}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
