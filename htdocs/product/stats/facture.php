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

$mesg = '';

/*
 *
 *
 */

if ($id)
{
  $product = new Product($db);
  $result = $product->fetch($id);
  
  if ( $result )
    { 
      print_fiche_titre('Fiche produit : '.$product->ref, $mesg);
      
      print '<table class="border" width="100%" cellspacing="0" cellpadding="4"><tr>';
      print '<td width="20%">Référence</td><td width="40%"><a href="../fiche.php?id='.$product->id.'">'.$product->ref.'</a></td>';
      print '<td><a href="fiche.php?id='.$id.'">Statistiques</a></td></tr>';
      print "<tr><td>Libellé</td><td>$product->libelle</td>";
      print '<td valign="top" rowspan="2">';
      print "Propositions commerciales : ".$product->count_propale();
      print "<br>Proposé à <b>".$product->count_propale_client()."</b> clients";
      print "<br>Factures : ".$product->count_facture();
      print '</td></tr>';
      print '<tr><td>Prix de vente</td><TD>'.price($product->price).'</td></tr>';
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
	  $sortfield="f.datef";
	}
      
      print_barre_liste("Factures",$page,$PHP_SELF,"&amp;id=$id",$sortfield,$sortorder);
      
      $sql = "SELECT distinct(f.rowid), s.nom,s.idp,f.facnumber,f.amount,".$db->pdate("f.datef")." as df,f.paye,f.rowid as facid";
      $sql .= " FROM llx_societe as s,llx_facture as f, llx_facturedet as d WHERE f.fk_soc = s.idp";
      $sql .= " AND d.fk_facture = f.rowid AND d.fk_product =".$id;
      $sql .= " ORDER BY $sortfield $sortorder ";
      $sql .= $db->plimit( $limit ,$offset);

      $result = $db->query($sql);
      if ($result) {
	$num = $db->num_rows();
    
	$i = 0;
	print "<TABLE border=\"0\" width=\"100%\" cellspacing=\"0\" cellpadding=\"4\">";
	print '<TR class="liste_titre">';
	print '<TD>Num&eacute;ro</TD>';
	print '<td>';
	print_liste_field_titre("Société",$PHP_SELF,"s.nom","","&amp;socidp=$socidp");
	print '</td><TD align="right">';
	print_liste_field_titre("Date",$PHP_SELF,"f.datef","","&amp;socidp=$socidp");
	print '</td><TD align="right">Montant</TD>';
	print '<td>&nbsp;</td>';
	print "</TR>\n";
	
	if ($num > 0)
	  {
	    $var=True;
	    while ($i < $num)
	      {
		$objp = $db->fetch_object( $i);
		$var=!$var;
		
		print "<TR $bc[$var]>";
		print '<td><a href="'.DOL_URL_ROOT.'/compta/facture.php?facid='.$objp->facid.'">';
		if ($objp->paye)
		  {
		    print $objp->facnumber;
		  }
		else
		  {
		    print '<b>'.$objp->facnumber.'</b>';
		  }
		print "</a></TD>\n";
		print '<td><a href="'.DOL_URL_ROOT.'/compta/fiche.php?socid='.$objp->idp.'">'.$objp->nom.'</a></TD>';
		
		if ($objp->df > 0 )
		  {
		    print "<TD align=\"right\">";
		    print strftime("%d %B %Y",$objp->df)."</td>";
		  }
		else
		  {
		    print "<TD align=\"right\"><b>!!!</b></TD>\n";
		  }
		
		print "<td align=\"right\">".price($objp->amount)."</TD>\n";
		
		if (! $objp->paye)
		  {
		    print '<td align="center">impayée</td>';
		  }
		else
		  {
		    print '<td>&nbsp;</td>';
		  }
		
		print "</tr>\n";
		$i++;
	      }
	  }
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
