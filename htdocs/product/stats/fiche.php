<?PHP
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

/*!
	    \file       htdocs/product/stats/fiche.php
        \ingroup    product
		\brief      Page des stats produits
		\version    $Revision$
*/

require("./pre.inc.php");
require("../../propal.class.php");

$langs->load("products");

$types[0] = $langs->trans("Product");
$types[1] = $langs->trans("Service");


if ($user->societe_id > 0)
{
  $action = '';
  $socid = $user->societe_id;
}
else
{
  $socid = 0;
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
      $dir = DOL_DOCUMENT_ROOT."/documents/produit/".$product->id;
      if (! file_exists($dir))
	{
	  umask(0);
		rmdir(DOL_DOCUMENT_ROOT."/documents/produits");
	  //mkdir(DOL_DOCUMENT_ROOT."/documents");
	  mkdir(DOL_DOCUMENT_ROOT."/documents/produit");
	  if (! mkdir($dir, 0755))
	    {
	      $mesg = $langs->trans("ErrorCanNotCreateDir",$dir);
	    }
	}
      $img_propal_name = "propal12mois.png";
      $filenbpropal = $dir . "/" . $img_propal_name;
      $filenbvente  = $dir . "/vente12mois.png";
      $filenbpiece  = $dir . "/vendu12mois.png";
        

      $px = new BarGraph();
      $mesg = $px->isGraphKo();
      if (! $mesg)
	{
	  $graph_data = $product->get_num_vente($socid);
	  $px->draw($filenbvente, $graph_data);
	  
	  $px = new BarGraph();
	  $graph_data = $product->get_nb_vente($socid);
	  $px->draw($filenbpiece, $graph_data);
	  
	  $px = new BarGraph();
	  $graph_data = $product->get_num_propal($socid);
	  $px->draw($filenbpropal, $graph_data);

	  $mesg = $langs->trans("ChartGenerated");
	}

      // Zone recherche
      print '<div class="formsearch">';
      print '<form action="../liste.php" method="post">';
      print '<input type="hidden" name="type" value="'.$product->type.'">';
      print $langs->trans("Ref").': <input class="flat" type="text" size="10" name="sref">&nbsp;<input class="flat" type="submit" value="go"> &nbsp;';
      print $langs->trans("Label").': <input class="flat" type="text" size="20" name="snom">&nbsp;<input class="flat" type="submit" value="go">';
      print '</form></div>';

      
      $h=0;
      
      $head[$h][0] = DOL_URL_ROOT."/product/fiche.php?id=".$product->id;
      $head[$h][1] = $langs->trans("Card");
	  $h++;
	  
      $head[$h][0] = DOL_URL_ROOT."/product/price.php?id=".$product->id;
      $head[$h][1] = $langs->trans("Price");
	  $h++;
	  
      if($product->type == 0)
      {
        $head[$h][0] = DOL_URL_ROOT."/product/stock/product.php?id=".$product->id;
        $head[$h][1] = 'Stock';
        $h++;
      }

      $head[$h][0] = DOL_URL_ROOT."/product/stats/fiche.php?id=".$product->id;
      $head[$h][1] = $langs->trans("Statistics");
	  $hselected=$h;
	  $h++;
	  
	  
	  dolibarr_fiche_head($head, $hselected, $langs->trans("CardProduct".$product->type).' : '.$product->ref);

	      
      print '<table class="border" width="100%" cellspacing="0" cellpadding="3"><tr>';
      print '<td width="20%">'.$langs->trans("Ref").'</td><td width="40%"><a href="../fiche.php?id='.$product->id.'">'.$product->ref.'</a></td>';
      print '<td>'.$langs->trans("Statistics").'</td></tr>';
      print "<tr><td>".$langs->trans("Label")."</td><td>$product->libelle</td>";
      print '<td valign="top" rowspan="2">';
      print '<a href="propal.php?id='.$product->id.'">Propositions commerciales</a> : '.$product->count_propale($socid);
      print "<br>Proposé à <b>".$product->count_propale_client($socid)."</b> clients";
      print '<br><a href="facture.php?id='.$product->id.'">Factures</a> : '.$product->count_facture($socid);
      print '</td></tr>';
      print '<tr><td>Prix actuel</td><td>'.price($product->price).'</td></tr>';
      print "</table>";

      print '<br><table class="border" width="100%" cellspacing="0" cellpadding="3">';
      print '<tr class="liste_titre"><td width="50%" colspan="2" align="center">Nombre de ventes<br>sur les 12 derniers mois</td>';
      print '<td align="center" width="50%" colspan="2">Nombre de pièces vendues</td></tr>';
      print '<tr><td align="center" colspan="2">';

      print '<img src="'.DOL_URL_ROOT.'/documents/produit/'.$product->id.'/vente12mois.png" alt="Ventes sur les 12 derniers mois">';
      
      print '</td><td align="center" colspan="2">';
      print '<img src="'.DOL_URL_ROOT.'/documents/produit/'.$product->id.'/vendu12mois.png" alt="Ventes sur les 12 derniers mois">';
      
      print '</td></tr><tr>';
      if (file_exists($filenbvente) && filemtime($filenbvente))
	{
	  print '<td>Généré le '.dolibarr_print_date(filemtime($filenbvente),"%d %b %Y %H:%M:%S").'</td>';
	}
      else
	{
	  print '<td>'.$langs->trans("ChartNotGenerated").'</td>';
	}
      print '<td align="center">[<a href="fiche.php?id='.$product->id.'&amp;action=recalcul">Re-calculer</a>]</td>';
      if (file_exists($filenbpiece) && filemtime($filenbpiece))
	{
	  print '<td>Généré le '.dolibarr_print_date(filemtime($filenbpiece),"%d %b %Y %H:%M:%S").'</td>';
	}
      else
	{
	  print '<td>'.$langs->trans("ChartNotGenerated").'</td>';
	}
      print '<td align="center">[<a href="fiche.php?id='.$product->id.'&amp;action=recalcul">Re-calculer</a>]</td></tr>';
      print '<tr><td colspan="4">Statistiques effectuées sur les factures payées uniquement</td></tr>';
      
      print '<tr class="liste_titre"><td width="50%" colspan="2" align="center">Nombre de propositions commerciales<br>sur les 12 derniers mois</td>';
      print '<td align="center" width="50%" colspan="2">-</td></tr>';
      print '<tr><td align="center" colspan="2">';

      print '<img src="'.DOL_URL_ROOT.'/documents/produit/'.$product->id.'/'.$img_propal_name.'" alt="Propales sur les 12 derniers mois">';
      
      print '</td><td align="center" colspan="2">TODO AUTRE GRAPHIQUE';

      
      print '</td></tr><tr>';
      if (file_exists($filenbpropal) && filemtime($filenbpropal))
	{
	  print '<td>Généré le '.dolibarr_print_date(filemtime($filenbpropal),"%d %b %Y %H:%M:%S").'</td>';
	}
      else
	{
	  print '<td>'.$langs->trans("ChartNotGenerated").'</td>';
	}
      print '<td align="center">[<a href="fiche.php?id='.$product->id.'&amp;action=recalcul">Re-calculer</a>]</td>';
      if (file_exists($filenbpiece) && filemtime($filenbpiece33))
	{
	  print '<td>Généré le '.dolibarr_print_date(filemtime($filenbpiece),"%d %b %Y %H:%M:%S").'</td>';
	}
      else
	{
	  print '<td>'.$langs->trans("ChartNotGenerated").'</td>';
	}
      print '<td align="center">[<a href="fiche.php?id='.$product->id.'&amp;action=recalcul">Re-calculer</a>]</td></tr>';

      print '</table><br>';



    }
}
else
{
  dolibarr_print_error();
}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
