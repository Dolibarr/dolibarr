<?PHP
/* Copyright (C) 2003-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
if (!$user->rights->expedition->lire)
  accessforbidden();

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
if ($HTTP_POST_VARS["action"] == 'add') 
{
  $expedition = new Expedition($db);

  $expedition->date_expedition  = time();
  $expedition->note             = $HTTP_POST_VARS["note"];
  $expedition->commande_id      = $HTTP_POST_VARS["commande_id"];
  $expedition->entrepot_id      = $HTTP_POST_VARS["entrepot_id"];

  $commande = new Commande($db);
  $commande->fetch($expedition->commande_id);
  $commande->fetch_lignes();
  
  for ($i = 0 ; $i < sizeof($commande->lignes) ; $i++)
    {
      $qty = "qtyl".$i;
      $idl = "idl".$i;
      if ($HTTP_POST_VARS[$qty] > 0)
	{
	  $expedition->addline($HTTP_POST_VARS[$idl],$HTTP_POST_VARS[$qty]);
	}
    }

  $expedition->create($user);
  
  $id = $expedition->id;

  $action = '';  
}

/*
 *
 */


if ($HTTP_POST_VARS["action"] == 'confirm_valid' && $HTTP_POST_VARS["confirm"] == yes && $user->rights->expedition->valider)
{
  $expedition = new Expedition($db);
  $expedition->fetch($_GET["id"]);
  $result = $expedition->valid($user);
}

if ($HTTP_POST_VARS["action"] == 'confirm_delete' && $HTTP_POST_VARS["confirm"] == yes)
{
  if ($user->rights->expedition->supprimer ) 
    {
      $expedition = new Expedition($db);
      $expedition->id = $_GET["id"];
      $expedition->delete();
      Header("Location: liste.php");
    }
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
if ($HTTP_POST_VARS["action"] == 'create') 
{
  llxHeader('','Fiche expedition','ch-expedition.html',$form_search);

  print_titre("Créer une expédition");

  $commande = new Commande($db);
  $commande->livraison_array();
  
  if ( $commande->fetch($HTTP_POST_VARS["commande_id"]))
    {
      $soc = new Societe($db);
      $soc->fetch($commande->soc_id);
      $author = new User($db);
      $author->id = $commande->user_author_id;
      $author->fetch();
      
      $entrepot = new Entrepot($db);
      /*
       *   Commande
       */
      print '<form action="fiche.php" method="post">';
      print '<input type="hidden" name="action" value="add">';
      print '<input type="hidden" name="commande_id" value="'.$commande->id.'">';
      print '<input type="hidden" name="entrepot_id" value="'.$HTTP_POST_VARS["entrepot_id"].'">';
      print '<table class="border" cellspacing="0" cellpadding="2" width="100%">';
      print '<tr><td width="20%">Client</td>';
      print '<td width="30%"><b><a href="'.DOL_URL_ROOT.'/comm/fiche.php?socid='.$soc->id.'">'.$soc->nom.'</a></b></td>';
      
      print '<td width="50%" colspan="2">';

      print "</td></tr>";
      
      print "<tr><td>Date</td>";
      print "<td>".strftime("%A %d %B %Y",$commande->date)."</td>\n";
      
      print '<td colspan="2" width="50%">Commande : ' . $commande->ref ;
      print "</td></tr>\n";
      
      print '<tr><td>Entrepôt</td>';
      print '<td>';
      $ents = $entrepot->list_array();
      print $ents[$HTTP_POST_VARS["entrepot_id"]];
      print '</td>';
      print "<td>Auteur</td><td>$author->fullname</td>\n";
      
      if ($commande->note)
	{
	  print '<tr><td colspan="3">Note : '.nl2br($commande->note)."</td></tr>";
	}
      print "</table>";
      
      /*
       * Lignes de commandes
       *
       */
      echo '<br><table border="0" width="100%" cellspacing="0" cellpadding="3">';	  
      
      $lignes = $commande->fetch_lignes(1);
      
      $num = sizeof($commande->lignes);
      $i = 0; $total = 0;
      
      if ($num)
	{
	  print '<tr class="liste_titre">';
	  print '<td width="54%">Description</td>';
	  print '<td align="center">Quan. commandée</td>';
	  print '<td align="center">Quan. livrée</td>';
	  print '<td align="center">Quan. à livrer</td>';
	  if (defined("MAIN_MODULE_STOCK"))
	    {
	      print '<td width="12%" align="center">Stock</td>';
	    }
	  print "</tr>\n";
	}
      $var=True;
      while ($i < $num)
	{
	  $ligne = $commande->lignes[$i];
	  print "<tr $bc[$var]>\n";
	  if ($ligne->product_id > 0)
	    {      
	      $product = new Product($db);
	      $product->fetch($ligne->product_id);
	      
	      print '<td>';
	      print '<a href="'.DOL_URL_ROOT.'/product/fiche.php?id='.$ligne->product_id.'">'.nl2br($ligne->description).'</a></td>';
	    }
	  else
	    {
	      print "<td>".nl2br($ligne->description)."</TD>\n";
	    }
	  
	  print '<td align="center">'.$ligne->qty.'</td>';
	  /*
	   *
	   */
	  print '<td align="center">';
	  $quantite_livree = $commande->livraisons[$ligne->product_id];
	  print $quantite_livree;;
	  print '</td>';
	  /*
	   *
	   */
	  print '<td align="center">';	  
	  print '<input name="idl'.$i.'" type="hidden" value="'.$ligne->id.'">';
	      
	  $quantite_commandee = $ligne->qty;
	  $quantite_a_livrer = $quantite_commandee - $quantite_livree;
	      
	  if (defined("MAIN_MODULE_STOCK"))
	    {
	      $stock = $product->stock_entrepot[$HTTP_POST_VARS["entrepot_id"]];

	      print '<input name="qtyl'.$i.'" type="text" size="6" value="'.min($quantite_a_livrer, $stock).'">';	      
	      print '</td>';
	      
	      if ($stock < $quantite_a_livrer)
		{
		  print '<td align="center" class="alerte">'.$stock.'</td>';
		}
	      else
		{
		  print '<td align="center">'.$stock.'</td>';
		}
	    }
	  else
	    {
	      print '<input name="qtyl'.$i.'" type="text" size="6" value="'.$quantite_a_livrer.'">';
	      print '</td>';
	    }
	  print "</tr>\n";
	  
	  $i++;
	  $var=!$var;
	}	      

      /*
       *
       */

      print '<tr><td align="center" colspan="3"><input type="submit" value="Créer"></td></tr>';
      print "</table>";
      print '</form>';
    } 
  else 
    {
      print $db->error() . "<br>$sql";;
    }
} 
else 
/* *************************************************************************** */
/*                                                                             */
/* Mode vue et edition                                                         */
/*                                                                             */
/* *************************************************************************** */
{  
  if ($id > 0)
    {
      $expedition = New Expedition($db);
      $result = $expedition->fetch($id);

      if ( $result > 0)
	{	  

	  $author = new User($db);
	  $author->id = $expedition->user_author_id;
	  $author->fetch();

	  llxHeader('','Fiche expedition','ch-expedition.html',$form_search,$author);
	 
	  $commande = New Commande($db);
	  $commande->fetch($expedition->commande_id);

	  $soc = new Societe($db);
	  $soc->fetch($commande->soc_id);

	  print_titre("Expedition : ".$expedition->ref);

	  /*
	   * Confirmation de la suppression
	   *
	   */
	  if ($action == 'delete')
	    {
	      $html->form_confirm("$PHP_SELF?id=$id","Supprimer l'expedition","Etes-vous sûr de vouloir supprimer cette expedition ?","confirm_delete");
	    }
	  
	  /*
	   * Confirmation de la validation
	   *
	   */
	  if ($_GET["action"] == 'valid')
	    {
	      $html->form_confirm("$PHP_SELF?id=$id","Valider l'expédition","Etes-vous sûr de vouloir valider cette expédition ?","confirm_valid");
	    }
	  /*
	   * Confirmation de l'annulation
	   *
	   */
	  if ($_GET["action"] == 'annuler')
	    {
	      $html->form_confirm("$PHP_SELF?id=$id","Annuler la commande","Etes-vous sûr de vouloir annuler cette commande ?","confirm_cancel");
	    }

	  /*
	   *   Commande
	   */
	  if ($commande->brouillon == 1 && $user->rights->commande->creer) 
	    {
	      print '<form action="fiche.php?id='.$id.'" method="post">';
	      print '<input type="hidden" name="action" value="setremise">';
	    }

	  print '<table class="border" cellspacing="0" cellpadding="2" width="100%">';
	  print '<tr><td width="20%">Client</td>';
	  print '<td width="30%">';
	  print '<b><a href="'.DOL_URL_ROOT.'/comm/fiche.php?socid='.$soc->id.'">'.$soc->nom.'</a></b></td>';
	  
	  print '<td width="20%">Auteur</td><td width="30%">'.$author->fullname.'</td>';

	  print "</tr>";
	  
	  print "<tr><td>Commande</td>";
	  print '<td><a href="'.DOL_URL_ROOT.'/commande/fiche.php?id='.$commande->id.'">'.$commande->ref."</a></td>\n";
	  print '<td>&nbsp;</td><td>&nbsp;</td></tr>';
	  print "<tr><td>Date</td>";
	  print "<td>".strftime("%A %d %B %Y",$expedition->date)."</td>\n";

	  $entrepot = new Entrepot($db);
	  $entrepot->fetch($expedition->entrepot_id);

	  print '<td width="20%">Entrepôt</td><td>'.$entrepot->libelle.'</td></tr>';

	  print "</table>\n";
	  	  
	  /*
	   * Lignes 
	   *
	   */
	  echo '<br><table border="0" width="100%" cellspacing="0" cellpadding="3">';	  

	  $sql = "SELECT cd.fk_product, cd.description, cd.rowid, cd.qty as qty_commande, ed.qty as qty_livre";
	  $sql .= " FROM ".MAIN_DB_PREFIX."commandedet as cd , ".MAIN_DB_PREFIX."expeditiondet as ed";
	  $sql .= " WHERE ed.fk_expedition = $id AND cd.rowid = ed.fk_commande_ligne ";
	  
	  $result = $db->query($sql);
	  if ($result)
	    {
	      $num = $db->num_rows();
	      $i = 0; $total = 0;
	      
	      if ($num)
		{
		  print '<tr class="liste_titre">';
		  print '<td width="54%">Description</td>';
		  print '<td align="center">Quan. commandée</td>';
		  print '<td align="center">Quan. livrée</td>';
		  print "</tr>\n";
		}
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
		  print '<td align="center">'.$objp->qty_commande.'</td>';
		  print '<td align="center">'.$objp->qty_livre.'</td>';

		  print "</tr>";
		  
		  $i++;
		  $var=!$var;
		}	      
	      $db->free();
	    } 
	else
	  {
	    print "$sql";
	    print $db->error();
	  }
	

	print "</table>";
	/*
	 * Fin Ajout ligne
	 *
	 */
	if ($user->societe_id == 0)
	  {
	    print '<p><table id="actions" width="100%"><tr>';
	
	    if ($expedition->brouillon && $user->rights->expedition->supprimer)
	      {
		print "<td align=\"center\" width=\"20%\"><a href=\"$PHP_SELF?id=$id&amp;action=delete\">Supprimer</a></td>";
	      } 
	    else
	      {
		print "<td align=\"center\" width=\"20%\">-</td>";
	      } 
	    

	    print "<td align=\"center\" width=\"20%\">-</td>";

	    

	    print '<td align="center" width="20%">-</td>';
	    print '<td align="center" width="20%">-</td>';
	    
	    if ($expedition->statut == 0) 
	      {
		if ($user->rights->expedition->valider)
		  {
		    print "<td align=\"center\" width=\"20%\"><a href=\"$PHP_SELF?id=$id&amp;action=valid\">Valider</a></td>";
		  }
		else
		  {
		    print '<td align="center" width="20%">-</td>';
		  }
	      }
	    elseif ($commande->statut == 1)
	      {
		if ($user->rights->commande->valider)
		  {
		    print "<td align=\"center\" width=\"20%\"><a href=\"fiche.php?id=$id&amp;action=annuler\">Annuler la commande</a></td>";
		  }
		else
		  {
		    print '<td align="center" width="20%">-</td>';
		  }
	      }
	    else
	      {
		print '<td align="center" width="20%">-</td>';
	      }

	    print "</tr></table>";
	  }
	print "<p>\n";

	/*
	 * Déjà livré
	 *
	 *
	 */
	$sql = "SELECT cd.fk_product, cd.description, cd.rowid, cd.qty as qty_commande, ed.qty as qty_livre, e.ref";
	$sql .= " FROM ".MAIN_DB_PREFIX."commandedet as cd , ".MAIN_DB_PREFIX."expeditiondet as ed, ".MAIN_DB_PREFIX."expedition as e";
	$sql .= " WHERE cd.fk_commande = $expedition->commande_id AND e.rowid <> $expedition->id AND cd.rowid = ed.fk_commande_ligne AND ed.fk_expedition = e.rowid";
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
		    print '<td align="center">'.$objp->ref.'</td>';
		    $i++;
		  }
		
		print '</table>';
	      }
	  }
	
	
	/*
	 * Documents générés
	 *
	 */
	$file = FAC_OUTPUTDIR . "/" . $commande->ref . "/" . $commande->ref . ".pdf";
	
	if (file_exists($file))
	  {
	    print "<table width=\"100%\" cellspacing=2><tr><td width=\"50%\" valign=\"top\">";
	    print_titre("Documents");
	    print '<table width="100%" cellspacing="0" class="border" cellpadding="3">';
	    
	    print "<tr $bc[0]><td>Commande PDF</td>";
	    print '<td><a href="'.FAC_OUTPUT_URL."/".$commande->ref."/".$commande->ref.'.pdf">'.$commande->ref.'.pdf</a></td>';
	    print '<td align="right">'.filesize($file). ' bytes</td>';
	    print '<td align="right">'.strftime("%d %b %Y %H:%M:%S",filemtime($file)).'</td>';
	    print '</tr>';
	           	
	    print "</table>\n";
	    print '</td><td valign="top" width="50%">';
	    print_titre("Actions");
	    /*
	     * Liste des actions
	     *
	     */
	    $sql = "SELECT ".$db->pdate("a.datea")." as da,  a.note";
	    $sql .= " FROM ".MAIN_DB_PREFIX."actioncomm as a WHERE a.fk_soc = $commande->socidp AND a.fk_action in (9,10) AND a.fk_commande = $id";
	    
	    $result = $db->query($sql);
	    if ($result)
	      {
		$num = $db->num_rows();
		if ($num)
		  {
		    $i = 0; $total = 0;
		    print '<table border="1" cellspacing="0" cellpadding="4" width="100%">';
		    print "<tr $bc[$var]><td>Date</td><td>Action</td></tr>\n";
		    
		    $var=True;
		    while ($i < $num)
		      {
			$objp = $db->fetch_object( $i);
			$var=!$var;
			print "<tr $bc[$var]>";
			print "<td>".strftime("%d %B %Y",$objp->da)."</TD>\n";
			print '<td>'.stripslashes($objp->note).'</TD>';
			print "</tr>";
			$i++;
		      }
		    print "</table>";
		  }
	      }
	    else
	      {
		print $db->error();
	      }
	    
	    /*
	     *
	     *
	     */
	    print "</td></tr></table>";
	  }
	/*
	 *
	 *
	 */

	if ($action == 'presend')
	  {
	    $replytoname = $user->fullname;
	    $from_name = $replytoname;

	    $replytomail = $user->email;
	    $from_mail = $replytomail;
	    
	    print "<form method=\"post\" action=\"$PHP_SELF?id=$id&amp;action=send\">\n";
	    print '<input type="hidden" name="replytoname" value="'.$replytoname.'">';
	    print '<input type="hidden" name="replytomail" value="'.$replytomail.'">';
	    
	    print "<p><b>Envoyer la commande par mail</b>";
	    print "<table cellspacing=0 border=1 cellpadding=3>";
	    print '<tr><td>Destinataire</td><td colspan="5">';
	    
	    $form = new Form($db);	    
	    $form->select_array("destinataire",$soc->contact_email_array());
	    
	    print "</td><td><input size=\"30\" name=\"sendto\" value=\"$commande->email\"></td></tr>";
	    print "<tr><td>Expéditeur</td><td colspan=\"5\">$from_name</td><td>$from_mail</td></tr>";
	    print "<tr><td>Reply-to</td><td colspan=\"5\">$replytoname</td>";
	    print "<td>$replytomail</td></tr></table>";
	    
	    print "<input type=\"submit\" value=\"Envoyer\"></form>";
	  }       
      }
    else
      {
	/* Commande non trouvée */
	print "Commande inexistante ou accés refusé";
      }
    }
}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
