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

if ($action == 'addga') {
  $client = new Client($db);

  $client->linkga($id, $ga);
}


if ($action == 'update' && !$cancel) {
  $client = new Client($db);

  $client->nom = $nom;

  $client->update($id, $user);
}

/*
 *
 *
 */

  if ($id)
    {

      $client = new Client($db);
      $result = $client->fetch($id);

      if ( $result )
	{ 

	  print '<div class="titre">Fiche Client : '.$client->name.'</div><br>';

	  print '<table border="1" width="100%" cellspacing="0" cellpadding="4">';
	  print "<tr>";
	  print '<td width="20%">Nom</td><td width="80%">'.$client->name.'</td></tr>';
	  print "</table>";


	  /*
	   * Commandes
	   *
	   */
	  $sql = "SELECT orders_id, customers_id,".$db->pdate("date_purchased")." as date_purchased";
	  $sql .= " FROM ".DB_NAME_OSC.".orders";
	  $sql .= " WHERE customers_id = " . $client->id;
	  
	  if ( $db->query($sql) )
	    {
	      $num = $db->num_rows();
	      $i = 0;
	      print '<p><TABLE border="0" width="50%" cellspacing="0" cellpadding="4">';
	      print "<TR class=\"liste_titre\"><td>Commandes</td>";
	      print "</tr>\n";
	      $var=True;
	      while ($i < $num) {
		$objp = $db->fetch_object( $i);
		$var=!$var;
		print "<TR $bc[$var]>";
		
		print '<td><a href="/boutique/commande/fiche.php?id='.$objp->orders_id.'"><img src="/theme/'.$conf->theme.'/img/filenew.png" border="0" alt="Fiche"></a>&nbsp;';
		
		print "<a href=\"/boutique/commande/fiche.php?id=$objp->orders_id\">".strftime("%d %B %Y",$objp->date_purchased)."</a></TD>\n";
		
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
