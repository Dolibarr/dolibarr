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

$db = new Db();

/*
 *
 *
 */

if ($id)
{

  $commande = new Commande($db);
  $result = $commande->fetch($id);
  
  if ( $result )
    { 
	  
      print '<div class="titre">Fiche Commande : '.$commande->name.'</div><br>';
      
      print '<table border="1" width="100%" cellspacing="0" cellpadding="4">';
      print "<tr>";
      print '<td width="20%">Numéro</td><td width="80%">'.$commande->id.'</td></tr>';
      print '<td width="20%">Client</td><td width="80%"><a href="/boutique/client/fiche.php?id='.$commande->client_id.'">'.$commande->client_name.'</a></td></tr>';
      print "</table>";
      
      /*
       * Produits
       *
       */
      $sql = "SELECT orders_id, products_id, products_model, products_name, products_price, final_price, products_quantity";
      $sql .= " FROM ".DB_NAME_OSC.".orders_products";
      $sql .= " WHERE orders_id = " . $commande->id;
 
      if ( $db->query($sql) )
	{
	  $num = $db->num_rows();
	  $i = 0;
	  print "<p><TABLE border=\"0\" width=\"100%\" cellspacing=\"0\" cellpadding=\"4\">";
	  print "<TR class=\"liste_titre\"><td>Produit</td>";
	  print "<td>Nombre</td>";
	  print "<td>Prix</td>";
	  print '<td>Prix final</td>';
	  print "</TR>\n";
	  $var=True;
	  while ($i < $num) {
	    $objp = $db->fetch_object( $i);
	    $var=!$var;
	    print "<TR $bc[$var]>";
	    
	    print '<td><a href="fiche.php?id='.$objp->rowid.'"><img src="/theme/'.$conf->theme.'/img/filenew.png" border="0" alt="Fiche livre"></a>&nbsp;';
	    
	    print "<a href=\"fiche.php?id=$objp->rowid\">$objp->products_name</a></TD>\n";

	    print "<TD><a href=\"fiche.php?id=$objp->rowid\">$objp->products_quantity</a></TD>\n";
	    print "<TD><a href=\"fiche.php?id=$objp->rowid\">".price($objp->products_price)."</a></TD>\n";
	    print "<TD><a href=\"fiche.php?id=$objp->rowid\">".price($objp->final_price)."</a></TD>\n";
	    
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
      
      
      
    }
  else
    {
      print "Fetch failed";
    }
  
  
}
else
{
  print "Error";
}


/* ************************************************************************** */
/*                                                                            */ 
/* Barre d'action                                                             */ 
/*                                                                            */ 
/* ************************************************************************** */

print '<br><table width="100%" border="1" cellspacing="0" cellpadding="3">';
print '<td width="20%" align="center">-</td>';
print '<td width="20%" align="center">-</td>';
print '<td width="20%" align="center">-</td>';
print '<td width="20%" align="center">-</td>';    
print '<td width="20%" align="center">-</td>';    
print '</table><br>';



$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
