<?PHP
/* Copyright (C) 2002-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Laurent Destailleur  <eldy@users.sourceforge.net>
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

/*! \file htdocs/compta/facture/fiche-rec.php
        \ingroup    facture
		\brief      Page d'affichage d'une facture récurrent
		\version    $Revision$
*/


require("./pre.inc.php");
require("./facture-rec.class.php");

$user->getrights('facture');
if (!$user->rights->facture->lire)
  accessforbidden();

require("../../project.class.php");

llxHeader('','Facture récurrente','ch-facture.html#s-fac-facture-rec');

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
if ($_POST["action"] == 'add') 
{
  $facturerec = new FactureRec($db, $facid);

  $facturerec->titre = $_POST["titre"];
  
  if ($facturerec->create($user) > 0)
    {
      $facid = $facturerec->id;
      $action = '';
    }
  else
    {
      $action = "create";
    }
}
/*
 *
 */

if ($action == 'delete' && $user->rights->facture->supprimer) 
{
  $fac = new FactureRec($db);
  $fac->delete($facid);
  $facid = 0 ;
}

/*
 *
 */

$html = new Form($db);

/*********************************************************************
 *
 * Mode creation
 *
 *
 *
 ************************************************************************/
if ($_GET["action"] == 'create') 
{
  print_titre("Créer une facture récurrente");

  $facture = new Facture($db);

  if ($facture->fetch($_GET["facid"]) > 0) 
    {
       
      print '<form action="fiche-rec.php" method="post">';
      print '<input type="hidden" name="action" value="add">';
      print '<input type="hidden" name="facid" value="'.$facture->id.'">';
      
      print '<table class="border" cellspacing="0" cellpadding="3" width="100%">';
      
      $facture->fetch_client();

      print '<tr><td>'.$langs->trans("Customer").' :</td><td>'.$facture->client->nom.'</td>';
      print '<td>'.$langs->trans("Comment").'</td></tr>';
      
      print '<tr><td>'.$langs->trans("Title").' :</td><td><input type="text" name="titre" size="16"></td>';

      print '<td rowspan="4" valign="top">';
      print '<textarea name="note" wrap="soft" cols="60" rows="8"></textarea></td></tr>';	
      
      print "<tr><td>".$langs->trans("Author")." :</td><td>".$user->fullname."</td></tr>";
      print "<tr><td>Conditions de réglement :</td><td>";
      
      print $facture->cond_reglement;
      
      print "</td></tr>";
      
      print "<tr><td>Projet :</td><td>";
      if ($facture->projetid > 0)
	{
	  $proj = new Project($db);
	  $proj->fetch($facture->projetid);
	  print $proj->title;
	}
      print "</td></tr></table>";

      print_titre('Services/Produits');
	  
      print '<table cellspacing="0" cellpadding="3" border="0" width="100%">';
      /*
       * Lignes de factures
       *
       */
      print '<tr><td colspan="3">';
	
      $sql = "SELECT l.fk_product, l.description, l.price, l.qty, l.rowid, l.tva_taux, l.remise_percent, l.subprice";
      $sql .= " FROM ".MAIN_DB_PREFIX."facturedet as l WHERE l.fk_facture = $facture->id ORDER BY l.rowid";
	  
      $result = $db->query($sql);
      if ($result)
	{
	  $num = $db->num_rows();
	  $i = 0; $total = 0;
	  
	  echo '<table border="0" width="100%" cellspacing="0" cellpadding="3">';
	  if ($num)
	    {
	      print "<tr class=\"liste_titre\">";
	      print '<td width="54%">'.$langs->trans("Description").'</td>';
	      print '<td width="8%" align="center">Tva</td>';
	      print '<td width="8%" align="center">Quantité</td>';
	      print '<td width="8%" align="right">Remise</td>';
	      print '<td width="12%" align="right">P.U.</td>';
	      print '<td width="12%" align="right">N.P.</td>';
	      print "</tr>\n";
	    }
	  $var=True;
	  while ($i < $num)
	    {
	      $objp = $db->fetch_object( $i);

	      if ($objp->fk_product > 0)
		{
		  $product = New Product($db);
		  $product->fetch($objp->fk_product);
		}

	      $var=!$var;
	      print "<TR $bc[$var]>";
	      if ($objp->fk_product)
		{
		  print '<td><a href="'.DOL_URL_ROOT.'/product/fiche.php?id='.$objp->fk_product.'">'.stripslashes(nl2br($objp->description)).'</a></td>';
		}
	      else
		{
		  print "<td>".stripslashes(nl2br($objp->description))."</TD>\n";
		}
	      print '<TD align="center">'.$objp->tva_taux.' %</TD>';
	      print '<TD align="center">'.$objp->qty.'</TD>';
	      if ($objp->remise_percent > 0)
		{
		  print '<td align="right">'.$objp->remise_percent." %</td>\n";
		}
	      else
		{
		  print '<td>&nbsp;</td>';
		}

	      print '<TD align="right">'.price($objp->subprice)."</td>\n";

	      if ($objp->fk_product > 0 && $objp->subprice <> $product->price)
		{
		  print '<td align="right">'.price($product->price)."</td>\n";
		  $flag_different_price++;
		}
	      else
		{
		  print '<td>&nbsp;</td>';
		}
	      
	      print "</tr>";
	      
	      $i++;
	    }
	  
	  $db->free();
	  
	} 
      else
	{
	  print $db->error();
	}
      print "</table>";
      
      print '</td></tr>';
      if ($flag_different_price)
	{
	  print '<tr><td colspan="3" align="left">';
	  print '<select name="deal_price">';
	  if ($flag_different_price>1)
	    {
	      print '<option value="new">Prendre en compte les nouveaux prix</option>';
	      print '<option value="old">Utiliser les anciens prix</option>';
	    }
	  else
	    {
	      print '<option value="new">Prendre en compte le nouveau prix</option>';
	      print '<option value="old">Utiliser l\'ancien prix</option>';
	    }
	  print '</select>';
	  print '</td></tr>';
	}
      print '<tr><td colspan="3" align="center"><input type="submit" value="Créer"></td></tr>';
      print "</form>\n";
      print "</table>\n";
      
    } 
  else 
    {
      print "Erreur facture $facture->id inexistante";
    }
} 
else 
/* *************************************************************************** */
/*                                                                             */
/*                                                                             */
/*                                                                             */
/* *************************************************************************** */
{
  
  if ($facid > 0)
    {    
      $fac = New FactureRec($db,0);

      if ( $fac->fetch($facid, $user->societe_id) > 0)
	{	  
	  $soc = new Societe($db, $fac->socidp);
	  $soc->fetch($fac->socidp);
	  $author = new User($db);
	  $author->id = $fac->user_author;
	  $author->fetch();
	  
	  print_titre("Facture : ".$fac->titre);
	  
	  /*
	   *   Facture
	   */
	  print '<table class="border" cellspacing="0" cellpadding="2" width="100%">';
	  print "<tr><td>Client</td>";
	  print "<td colspan=\"3\">";
	  print '<b><a href="../fiche.php?socid='.$soc->id.'">'.$soc->nom.'</a></b></td>';
	  
	  print "<td>Conditions de réglement : " . $fac->cond_reglement ."</td></tr>";
	  
	  print "<tr><td>".$langs->trans("Author")."</td><td colspan=\"3\">$author->fullname</td>";
	  
	  if ($fac->remise_percent > 0)
	    {
	      print '<td rowspan="5" valign="top">';
	    }
	  else
	    {
	      print '<td rowspan="4" valign="top">';
	    }
	  
	print "</td></tr>";
	
	print '<tr><td>Montant</td>';
	print '<td align="right" colspan="2"><b>'.price($fac->total_ht).'</b></td>';
	print '<td>'.MAIN_MONNAIE.' HT</td></tr>';

	print '<tr><td>TVA</td><td align="right" colspan="2">'.price($fac->total_tva).'</td>';
	print '<td>'.MAIN_MONNAIE.'</td></tr>';
	print '<tr><td>'.$langs->trans("TotalTTC").'</td><td align="right" colspan="2">'.price($fac->total_ttc).'</td>';
	print '<td>'.MAIN_MONNAIE.'</td></tr>';
	if ($fac->note)
	  {
	    print '<tr><td colspan="5">Note : '.nl2br($fac->note)."</td></tr>";
	  }

	print "</table><br>";
	/*
	 * Lignes
	 *
	 */
	print_titre("Produits");
	      
	print '<table class="noborder" width="100%" cellspacing="0" cellpadding="3">';
	print '<tr class="liste_titre"><td>'.$langs->trans("Ref").'</td><td>Produit</td>';
	print '<td align="right">'.$langs->trans("Price").'</td><td align="center">Remise</td><td align="center">Qté.</td></tr>';
	
	$num = sizeof($fac->lignes);
	$i = 0;	
	$var=True;	
	while ($i < $num) 
	  {
	    $var=!$var;
	    if ($fac->lignes[$i]->produit_id > 0)
	      {
		$prod = New Product($db);
		$prod->fetch($fac->lignes[$i]->produit_id);
		print "<tr $bc[$var]><td>[".$prod->ref.']</td>';
		print '<td>'.$fac->lignes[$i]->desc.'</td>';
	      }
	    else
	      {
		print "<tr $bc[$var]><td>&nbsp;</td>";
		print '<td>'.$fac->lignes[$i]->desc.'</td>';
	      }
	    print "<td align=\"right\">".price($fac->lignes[$i]->price)."</TD>";
	    print '<td align="center">'.$fac->lignes[$i]->remise_percent.' %</td>';
	    print "<td align=\"center\">".$fac->lignes[$i]->qty."</td></tr>\n";
	    $i++;
	  }
	print '</table>';

	/*
	 * Actions
	 *
	 */
	if ($user->societe_id == 0 && $fac->paye == 0)
	  {
	    print '<p><table id="actions" width="100%" cellspacing="0" cellpadding="4"><tr>';
	
	    if ($fac->statut == 0 && $user->rights->facture->supprimer)
	      {
		print "<td align=\"center\" width=\"25%\">[<a href=\"fiche-rec.php?facid=$facid&action=delete\">Supprimer</a>]</td>";
	      } 
	    else
	      {
		print "<td align=\"center\" width=\"25%\">-</td>";
	      } 
	    

	    print "<td align=\"center\" width=\"25%\">-</td>";

	    

	    print '<td align="center" width="25%">-</td>';	    
	    print '<td align="center" width="25%">-</td>';

	    print "</tr></table>";
	  }
	print "<p>\n";

	/*
	 *
	 *
	 */       
      }
    else
      {
	/* Facture non trouvée */
	print "Facture inexistante ou accés refusé";
      }
  } else {
    /***************************************************************************
     *                                                                         *
     *                      Mode Liste                                         *
     *                                                                         * 
     *                                                                         *
     ***************************************************************************/
    if ($page == -1)
      {
	$page = 0 ;
      }

    if ($user->rights->facture->lire)
      {
	$limit = $conf->liste_limit;
	$offset = $limit * $page ;

	if ($sortorder == "")
	  $sortorder="DESC";

	if ($sortfield == "")
	  $sortfield="f.datef";

	$sql = "SELECT s.nom,s.idp,f.titre,f.total,f.rowid as facid";
	$sql .= " FROM ".MAIN_DB_PREFIX."societe as s,".MAIN_DB_PREFIX."facture_rec as f WHERE f.fk_soc = s.idp";
	
	if ($socidp)
	  $sql .= " AND s.idp = $socidp";
		 	
	//$sql .= " ORDER BY $sortfield $sortorder, rowid DESC ";
	//	$sql .= $db->plimit($limit + 1,$offset);
	
	$result = $db->query($sql);
      }
    if ($result)
      {
	$num = $db->num_rows();
	print_barre_liste("Factures",$page,"fiche-rec.php","&socidp=$socidp",$sortfield,$sortorder,'',$num);

	$i = 0;
	print "<TABLE border=\"0\" width=\"100%\" cellspacing=\"0\" cellpadding=\"4\">";
	print '<TR class="liste_titre">';
	print '<td>Titre</td>';
	print '<td>';
	print_liste_field_titre("Société","fiche-rec.php","s.nom","","&socidp=$socidp");

	print '</td><TD align="right">Montant</TD>';
	print '<td>&nbsp;</td>';
	print "</TR>\n";
      
	if ($num > 0) 
	  {
	    $var=True;
	    while ($i < min($num,$limit))
	      {
		$objp = $db->fetch_object($i);
		$var=!$var;

		print "<tr $bc[$var]>";

		$class = "normal";

		print '<td><a class="'.$class.'" href="fiche-rec.php?facid='.$objp->facid.'">' . $objp->titre;
		print "</a></TD>\n";
		print '<TD><a class="'.$class.'" href="../fiche.php?socid='.$objp->idp.'">'.$objp->nom.'</a></td>';
		
		print "<TD align=\"right\">".price($objp->total)."</TD>\n";
		
		if (! $objp->paye)
		  {
		    if ($objp->fk_statut == 0)
		      {
			print '<td align="center">brouillon</td>';
		      }
		    else
		      {
			print '<td align="center"><a href="facture.php?filtre=paye:0,fk_statut:1">impayée</a></td>';
		      }
		  }
		else
		  {
		    print '<td>&nbsp;</td>';
		  }
		
		print "</TR>\n";
		$i++;
	      }
	  }
	
	print "</table>";
	$db->free();
      }
    else
      {
	print $db->error() . "<br>" . $sql;
      }    
  }
  
}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
