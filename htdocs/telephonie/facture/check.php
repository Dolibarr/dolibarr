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


$page = $_GET["page"];
$sortorder = $_GET["sortorder"];
$sortfield = $_GET["sortfield"];

llxHeader();

/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $action = '';
  $socidp = $user->societe_id;
}

if ($sortorder == "") {
  $sortorder="DESC";
}
if ($sortfield == "") {
  $sortfield="f.date";
}

if ($page == -1) { $page = 0 ; }



/*
 * Mode Liste
 *
 *
 *
 */

$sql = "SELECT distinct(client), sum(montant) ";
$sql .= " FROM stragex";

if ($_GET["search_ligne"])
{
  $sql .= " WHERE f.ligne LIKE '%".$_GET["search_ligne"]."%'";
}
$sql .= " GROUP BY client";
$result = $db->query($sql);
if ($result)
{
  $num = $db->num_rows();
  $i = 0;
  
  print_barre_liste("Vérification avant facturation", $page, "check.php", "", $sortfield, $sortorder, '', $num);

  print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
  print '<tr class="liste_titre"><td valign="center">Ligne';
  print '</td><td align="center">Client</td><td align="center">Client facturé</td><td align="right">Montant</td><td align="center">Remise LMN</td>';
  print '<td align="center">RIB';
  print '</td><td align="center">Email</td>';
  print "</tr>\n";

  $var=True;
  $total = 0;
  while ($i < $num)
    {
      $row = $db->fetch_row($i);
      $var=!$var;
      $total = $total + $row[1];
      print "<tr $bc[$var]><td>";

      $ligne = new LigneTel($db);
      if ($ligne->fetch($row[0]) == 1)
	{

	  print '<img src="../ligne/graph'.$ligne->statut.'.png">&nbsp;';

	  print '<a href="'.DOL_URL_ROOT.'/telephonie/ligne/fiche.php?numero='.$row[0].'">';
	  print img_file();

	  print '</a>&nbsp;<a href="'.DOL_URL_ROOT.'/telephonie/ligne/fiche.php?numero='.$row[0].'">';

	  print dolibarr_print_phone($row[0])."</a></td>\n";
	  
	  print '<td align="center">';
	  
	  $client = new Societe($db);
	  	  
	  if ($client->fetch($ligne->client_id))
	    {
	      print $client->nom."</td>\n";
	    }
	  else
	    {
	      print "Erreur";	  
	    }

	  print '<td align="center">';

	  if ($ligne->facturable)
	    {

	      $clientf = new Societe($db);
	      if ($clientf->fetch($ligne->client_facture_id))
		{
		  print '<a href="'.DOL_URL_ROOT.'/comm/fiche.php?socid='.$ligne->client_facture_id.'">';
		  print $clientf->nom."</a></td>\n";

		  print '<td align="right">'.price($row[1])."</td>";;

		  /* Remise LMN */
		  print '<td align="center">'.$ligne->remise.' %</td>';
		  
		  print '<td align="center">';
		  
		  if ($ligne->mode_paiement == 'pre')
		    {
		      if ($clientf->verif_rib())
			{
			  print 'ok';
			}
		      else
			{
			  print "Alerte RIB";
			}
		    }
		  else
		    {
		      print 'Virement';
		    }
		  print "</td>\n";
		}
	      else
		{
		  print "Erreur</td><td>-</td>";
		}
	     
	      print '<td align="center">';
	      
	      $cfs = $ligne->get_contact_facture();
	      
	      if (sizeof($cfs))
		{
		  print $cfs[0];
		}
	      else
		{
		  print "Alerte Emails";
		}
	      print "</td>\n";
	    }
	  else
	    {
	      print 'Client non facturé</td><td colspan="4">&nbsp;</td>';
	    }
	  	 	  
	}
      else
	{
	  print dolibarr_print_phone($row[0]);
	  print '<td align="center">Ligne inconnue</td>';
	  print '<td colspan="5">&nbsp;</td>';
	}

      print "</tr>\n";
      $i++;
    }

  print '<tr class="liste_titre"><td colspan="2">&nbsp;</td><td align="center">Total HT</td><td align="right">'.price($total).'</td><td colspan="4">&nbsp;</td>';
  print "</tr>\n";

  print '<tr class="liste_titre"><td colspan="2">&nbsp;</td><td align="center">Total TTC</td><td align="right">'.price($total*1.196).'</td><td colspan="4">&nbsp;</td>';

  print "</table>";
  $db->free();
}
else 
{
  print $db->error() . ' ' . $sql;
}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
