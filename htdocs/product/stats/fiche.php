<?PHP
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (c) 2004      Laurent Destailleur  <eldy@users.sourceforge.net>
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
require("../../propal.class.php");

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
      $dir = DOL_DOCUMENT_ROOT."/document/produit/".$product->id;
      if (! file_exists($dir))
	{
	  umask(0);
	  if (! mkdir($dir, 0755))
	    {
	      $mesg = "Impossible de créer $dir !";
	    }
	}

      $filenbpropal = $dir . "/propal12mois.png";
      $filenbvente  = $dir . "/vente12mois.png";
      $filenbpiece  = $dir . "/vendu12mois.png";
        
      if (! file_exists($filenbvente) or $_GET["action"] == 'recalcul')
        {
	  $px = new BarGraph();
	  $mesg = $px->isGraphKo();
	  if (! $mesg)
	    {
	      $graph_data = $product->get_num_vente();
	      $px->draw($filenbvente, $graph_data);

	      $px = new BarGraph();
	      $graph_data = $product->get_nb_vente();
	      $px->draw($filenbpiece, $graph_data);

	      $px = new BarGraph();
	      $graph_data = $product->get_num_propal();
	      $px->draw($filenbpropal, $graph_data);

	      $mesg = "Graphiques générés";
	    }
        }
        

      $head[0][0] = DOL_URL_ROOT."/product/fiche.php?id=".$product->id;
      $head[0][1] = 'Fiche';
	
      $head[1][0] = DOL_URL_ROOT."/product/price.php?id=".$product->id;
      $head[1][1] = 'Prix';
	
      $head[2][0] = DOL_URL_ROOT."/product/stats/fiche.php?id=".$product->id;
      $head[2][1] = 'Statistiques';
		
      dolibarr_fiche_head($head, 2, 'Fiche '.$types[$product->type].' : '.$product->ref);
	      
      print '<table class="border" width="100%" cellspacing="0" cellpadding="4"><tr>';
      print '<td width="20%">Référence</td><td width="40%"><a href="../fiche.php?id='.$product->id.'">'.$product->ref.'</a></td>';
      print '<td>Statistiques</td></tr>';
      print "<tr><td>Libellé</td><td>$product->libelle</td>";
      print '<td valign="top" rowspan="2">';
      print '<a href="propal.php?id='.$product->id.'">Propositions commerciales</a> : '.$product->count_propale();
      print "<br>Proposé à <b>".$product->count_propale_client()."</b> clients";
      print '<br><a href="facture.php?id='.$product->id.'">Factures</a> : '.$product->count_facture();
      print '</td></tr>';
      print '<tr><td>Prix actuel</td><td>'.price($product->price).'</td></tr>';
      print "</table>";

      print '<br><table class="border" width="100%" cellspacing="0" cellpadding="4">';
      print '<tr class="liste_titre"><td width="50%" colspan="2" align="center">Nombre de ventes<br>sur les 12 derniers mois</td>';
      print '<td align="center" width="50%" colspan="2">Nombre de pièces vendues</td></tr>';
      print '<tr><td align="center" colspan="2">';

      print '<img src="'.DOL_URL_ROOT.'/document/produit/'.$product->id.'/vente12mois.png" alt="Ventes sur les 12 derniers mois">';
      
      print '</td><td align="center" colspan="2">';
      print '<img src="'.DOL_URL_ROOT.'/document/produit/'.$product->id.'/vendu12mois.png" alt="Ventes sur les 12 derniers mois">';
      
      print '</td></tr><tr>';
      if (file_exists($filenbvente) && filemtime($filenbvente))
	{
	  print '<td>Généré le '.dolibarr_print_date(filemtime($filenbvente),"%d %b %Y %H:%M:%S").'</td>';
	}
      else
	{
	  print '<td>Graphique non généré</td>';
	}
      print '<td align="center">[<a href="fiche.php?id='.$product->id.'&amp;action=recalcul">Re-calculer</a>]</td>';
      if (file_exists($filenbpiece) && filemtime($filenbpiece))
	{
	  print '<td>Généré le '.dolibarr_print_date(filemtime($filenbpiece),"%d %b %Y %H:%M:%S").'</td>';
	}
      else
	{
	  print '<td>Graphique non généré</td>';
	}
      print '<td align="center">[<a href="fiche.php?id='.$product->id.'&amp;action=recalcul">Re-calculer</a>]</td></tr>';
      print '<tr><td colspan="4">Statistiques effectuées sur les factures payées uniquement</td></tr>';
      
      print '<tr class="liste_titre"><td width="50%" colspan="2" align="center">Nombre de propositions commerciales<br>sur les 12 derniers mois</td>';
      print '<td align="center" width="50%" colspan="2">-</td></tr>';
      print '<tr><td align="center" colspan="2">';

      print '<img src="'.DOL_URL_ROOT.'/document/produit/'.$product->id.'/propal12mois.png" alt="Propales sur les 12 derniers mois">';
      
      print '</td><td align="center" colspan="2">TODO AUTRE GRAPHIQUE';

      
      print '</td></tr><tr>';
      if (file_exists($filenbpropal) && filemtime($filenbpropal))
	{
	  print '<td>Généré le '.dolibarr_print_date(filemtime($filenbpropal),"%d %b %Y %H:%M:%S").'</td>';
	}
      else
	{
	  print '<td>Graphique non généré</td>';
	}
      print '<td align="center">[<a href="fiche.php?id='.$product->id.'&amp;action=recalcul">Re-calculer</a>]</td>';
      if (file_exists($filenbpiece) && filemtime($filenbpiece33))
	{
	  print '<td>Généré le '.dolibarr_print_date(filemtime($filenbpiece),"%d %b %Y %H:%M:%S").'</td>';
	}
      else
	{
	  print '<td>Graphique non généré</td>';
	}
      print '<td align="center">[<a href="fiche.php?id='.$product->id.'&amp;action=recalcul">Re-calculer</a>]</td></tr>';

      print '</table>';








    }
}
else
{
  print "Error";
}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
