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

$user->getrights('commande');
$user->getrights('expedition');
if (!$user->rights->commande->lire)
  accessforbidden();

require("../project.class.php");
require("../propal.class.php");
require("../product/stock/entrepot.class.php");
/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $action = '';
  $socidp = $user->societe_id;
}
/*
 *
 */	

if ($HTTP_POST_VARS["action"] == 'confirm_cloture' && $HTTP_POST_VARS["confirm"] == yes)
{
  $commande = new Commande($db);
  $commande->fetch($_GET["id"]);
  $result = $commande->cloture($user);
}

llxHeader('','Fiche commande','');

$html = new Form($db);

/* *************************************************************************** */
/*                                                                             */
/* Mode vue et edition                                                         */
/*                                                                             */
/* *************************************************************************** */

if ($id > 0)
{
  $commande = New Commande($db);
  if ( $commande->fetch($id) > 0)
    {	  
      $commande->livraison_array();
      
      $soc = new Societe($db);
      $soc->fetch($commande->soc_id);
      $author = new User($db);
      $author->id = $commande->user_author_id;
      $author->fetch();
      
      print_titre("Commande : ".$commande->ref);
      
      /*
       * Confirmation de la validation
       *
       */
      if ($_GET["action"] == 'cloture')
	{
	  $html->form_confirm("$PHP_SELF?id=$id","Cloturer la commande","Etes-vous sûr de cloturer cette commande ?","confirm_cloture");
	}
      /*
       *
       */

      print '<form method="post" action="fiche.php">';
      print '<input type="hidden" name="action" value="create">';
      print '<input type="hidden" name="commande_id" value="'.$commande->id.'">';
      print '<table class="border" cellspacing="0" cellpadding="2" width="100%">';
      print '<tr><td width="20%">Client</td>';
      print "<td colspan=\"2\">";
      print '<b><a href="'.DOL_URL_ROOT.'/comm/fiche.php?socid='.$soc->id.'">'.$soc->nom.'</a></b></td>';
      
      print '<td width="50%">';
      print $commande->statuts[$commande->statut];
      print "</td></tr>";
      
      print "<tr><td>Date</td>";
      print "<td colspan=\"2\">".strftime("%A %d %B %Y",$commande->date)."</td>\n";

      print '<td width="50%">Source : ' . $commande->sources[$commande->source] ;
      if ($commande->source == 0)
	{
	  /* Propale */
	  $propal = new Propal($db);
	  $propal->fetch($commande->propale_id);
	  print ' -> <a href="'.DOL_URL_ROOT.'/comm/propal.php?propalid='.$propal->id.'">'.$propal->ref.'</a>';
	}
      print "</td></tr>";
  
      if ($commande->note)
	{
	  print '<tr><td>Note</td></tr>';
	  print '<tr><td colspan="3">Note : '.nl2br($commande->note)."</td></tr>";
	}
	  
      print '<tr><td colspan="4">';
	  
      /*
       * Lignes de commandes
       *
       */
      echo '<table class="liste" width="100%" cellspacing="0" cellpadding="3">';	  

      $sql = "SELECT l.fk_product, l.description, l.price, l.qty, l.rowid, l.tva_tx, l.remise_percent, l.subprice";
      $sql .= " FROM llx_commandedet as l WHERE l.fk_commande = $id ORDER BY l.rowid";
	  
      $result = $db->query($sql);
      if ($result)
	{
	  $num = $db->num_rows();
	  $i = 0; $total = 0;
	      
	  if ($num)
	    {
	      print '<tr class="liste_titre">';
	      print '<td width="54%">Description</td>';
	      print '<td align="center">Quan. Commandée</td>';
	      print '<td align="center">Quan. livrée</td>';
	      print '<td align="center">Reste à livrer</td>';
	      if (defined("MAIN_MODULE_STOCK"))
		{
		  print '<td width="12%" align="center">Stock</td>';
		}
	      print "</tr>\n";
	    }
	  $var=True;
	  $reste_a_livrer = array();
	  while ($i < $num)
	    {
	      $objp = $db->fetch_object( $i);
	      print "<TR $bc[$var]>";
	      if ($objp->fk_product > 0)
		{

		  $product = new Product($db);
		  $product->fetch($objp->fk_product);

		  print '<td>';
		  print '<a href="'.DOL_URL_ROOT.'/product/fiche.php?id='.$objp->fk_product.'">'.stripslashes(nl2br($objp->description)).'</a></td>';
		}
	      else
		{
		  print "<td>".stripslashes(nl2br($objp->description))."</TD>\n";
		}

	      print '<td align="center">'.$objp->qty.'</td>';

	      print '<td align="center">';
	      $quantite_livree = $commande->livraisons[$objp->fk_product];
	      print $quantite_livree;
	      print '</td>';

	      $reste_a_livrer[$objp->fk_product] = $objp->qty - $quantite_livree;
	      $reste_a_livrer_x = $objp->qty - $quantite_livree;
	      $reste_a_livrer_total = $reste_a_livrer_total + $reste_a_livrer_x;
	      print '<td align="center">';
	      print $reste_a_livrer[$objp->fk_product];
	      print '</td>';

	      if (defined("MAIN_MODULE_STOCK"))
		{
		  if ($product->stock_reel < $reste_a_livrer_x)
		    {
		      print '<td align="center" class="alerte">'.$product->stock_reel.'</td>';
		    }
		  else
		    {
		      print '<td align="center">'.$product->stock_reel.'</td>';
		    }
		}
	      print "</tr>";
		  
	      $i++;
	      $var=!$var;
	    }	      
	  $db->free();
	  print "</table>";
	} 
      else
	{
	  print $db->error();
	}

      /*
       *
       *
       */
      if ($reste_a_livrer_total > 0)
	{
	  $entrepot = new Entrepot($db);

	  print '<tr><td width="20%">Entrepôt</td>';
	  print '<td colspan="3">';
	  $html->select_array("entrepot_id",$entrepot->list_array());
	  print '</td></tr>';

	  print '<tr><td width="20%">Méthode</td>';
	  print '<td colspan="3">';
	  $html->select_array("entrepot_id",$entrepot->list_array());
	  print '</td></tr>';

	  print '<tr><td colspan="4" align="center"><input type="submit" value="Créer"></td></tr>';
	}
      print "</table>";
      print "</form>\n";

      /*
       * Alerte de seuil
       *
       */
      if ($reste_a_livrer_total > 0 && defined("MAIN_MODULE_STOCK"))
	{
	  print '<br><table class="liste" cellpadding="3" width="100%"><tr>';
	  foreach ($reste_a_livrer as $key => $value)
	    {
	      if ($value > 0)
		{
		  $sql = "SELECT e.label as entrepot, ps.reel, p.label ";
		  $sql .= " FROM llx_entrepot as e, llx_product_stock as ps, llx_product as p";
		  $sql .= " WHERE e.rowid = ps.fk_entrepot AND ps.fk_product = p.rowid AND ps.fk_product = $key";
		  $sql .= " AND e.statut = 1 AND reel < $value";
		      
		  $result = $db->query($sql);
		  if ($result)
		    {
		      $num = $db->num_rows();
		      $i = 0;
		      
		      $var=True;
		      while ($i < $num)
			{
			  $obja = $db->fetch_object( $i);
			  print "<tr $bc[$var]>";
			  print '<td width="54%">'.$obja->label.'</td><td>'.$obja->entrepot.'</td><td><b>Stock : '.$obja->reel.'</b></td>';
			  print "</tr>\n";
			  $i++;
			}
		      $db->free();
		    }
		}
	    }
	  print "</table>";
	}
      /*
       *
       *
       */
      if ($user->societe_id == 0)
	{
	  print '<p><table id="actions" width="100%"><tr>';
	
	  print '<td align="center" width="20%">-</td>';
	  print '<td align="center" width="20%">-</td>';
	    
	  if ($user->rights->expedition->valider && $reste_a_livrer_total == 0 && $commande->statut < 3)
	    {
	      print '<td align="center" width="20%"><a href="commande.php?id='.$_GET["id"].'&amp;action=cloture">Clôturer</a></td>';
	    }
	  else
	    {
	      print '<td align="center" width="20%">-</td>';
	    }
	    
	  print '<td align="center" width="20%">-</td>';
	  print '<td align="center" width="20%">-</td>';
	  print "</tr></table>";
	}
      /*
       * Déjà livré
       *
       *
       */
      $sql = "SELECT cd.fk_product, cd.description, cd.rowid, cd.qty as qty_commande, ed.qty as qty_livre, e.ref, e.rowid as expedition_id";
      $sql .= " FROM llx_commandedet as cd , llx_expeditiondet as ed, llx_expedition as e";
      $sql .= " WHERE cd.fk_commande = $id AND cd.rowid = ed.fk_commande_ligne AND ed.fk_expedition = e.rowid";
      $sql .= " ORDER BY cd.fk_product";
      $result = $db->query($sql);
      if ($result)
	{
	  $num = $db->num_rows();
	  $i = 0; $total = 0;
	      
	  if ($num)
	    {
	      print '<br><table class="liste" cellpadding="3" width="100%"><tr>';
	      print '<tr class="liste_titre">';
	      print '<td width="54%">Description</td>';
	      print '<td align="center">Quan. livrée</td>';
	      print '<td align="center">Expédition</td>';

	      print "</tr>\n";
		
	      $var=True;
	      while ($i < $num)
		{
		  $objp = $db->fetch_object( $i);
		  print "<TR $bc[$var]>";
		  if ($objp->fk_product > 0)
		    {
		      print '<td>';
		      print '<a href="'.DOL_URL_ROOT.'/product/fiche.php?id='.$objp->fk_product.'">'.stripslashes(nl2br($objp->description)).'</a></td>';
		    }
		  else
		    {
		      print "<td>".stripslashes(nl2br($objp->description))."</TD>\n";
		    }
		  print '<td align="center">'.$objp->qty_livre.'</td>';
		  print '<td align="center"><a href="fiche.php?id='.$objp->expedition_id.'">'.$objp->ref.'</a></td>';
		  $i++;
		}

	      print '</table>';
	    }
	}	
    }
  else
    {
      /* Commande non trouvée */
      print "Commande inexistante ou accés refusé";
    }
}  


$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
