<?php
/* Copyright (C) 2003-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

/*!	        \file       htdocs/commande/fiche.php
	        \ingroup    commande
	        \brief      Fiche commande
	        \version    $Revision$
*/

require("./pre.inc.php");

$langs->load("orders");
$langs->load("suppliers");
$langs->load("companies");

$user->getrights('commande');
$user->getrights('expedition');

if (!$user->rights->commande->lire)
  accessforbidden();

require_once "../../project.class.php";
require_once "../../propal.class.php";
require_once DOL_DOCUMENT_ROOT."/fournisseur.class.php";
require_once DOL_DOCUMENT_ROOT."/fournisseur.commande.class.php";

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
if ($_POST["action"] == 'classin') 
{
  $commande = new CommandeFournisseur($db);
  $commande->fetch($_GET["id"]);
  $commande->classin($_POST["projetid"]);
}

/*
 *
 */

if ($_POST["action"] == 'setremise' && $user->rights->commande->creer) 
{
  $commande = new CommandeFournisseur($db);
  $commande->fetch($id);

  $commande->set_remise($user, $_POST["remise"]);
} 
/*
 *
 */
if ($_POST["action"] == 'addligne' && $user->rights->commande->creer) 
{
  $comf = new CommandeFournisseur($db);
  $comf->fetch($_GET["id"]);

  if ($_POST["p_idprod"] > 0)
    {
      $result = $comf->addline("DESC",
			       $_POST["pu"],
			       $_POST["pqty"],
			       $_POST["tva_tx"],
			       $_POST["p_idprod"],
			       $_POST["premise"]);
    }
  else
    {
      $result = $comf->addline($_POST["desc"],
			       $_POST["pu"],
			       $_POST["qty"],
			       $_POST["tva_tx"],
			       0,
			       $_POST["remise_percent"]);
    }
  Header("Location: fiche.php?id=".$_GET["id"]);
}

if ($_POST["action"] == 'updateligne' && $user->rights->commande->creer) 
{
  $commande = new CommandeFournisseur($db,"",$_GET["id"]);
  if ($commande->fetch($_GET["id"]) )
    {
      $result = $commande->update_line($_POST["elrowid"],
				       $_POST["eldesc"],
				       $_POST["elprice"],
				       $_POST["elqty"],
				       $_POST["elremise_percent"]);
    }
  else
    {
      print "Erreur";
    }
}

if ($_GET["action"] == 'deleteline' && $user->rights->commande->creer) 
{
  $comf = new CommandeFournisseur($db);
  $comf->fetch($_GET["id"]);
  $result = $comf->delete_line($_GET["lineid"]);
}

if ($_POST["action"] == 'confirm_valid' && $_POST["confirm"] == yes && $user->rights->commande->valider)
{
  $commande = new CommandeFournisseur($db);
  $commande->fetch($_GET["id"]);
  $soc = new Societe($db);
  $soc->fetch($commande->soc_id);
  $result = $commande->valid($user);
}

if ($_POST["action"] == 'confirm_cancel' && $_POST["confirm"] == yes && $user->rights->commande->valider)
{
  $commande = new CommandeFournisseur($db);
  $commande->fetch($_GET["id"]);
  $result = $commande->cancel($user);
}

if ($_POST["action"] == 'confirm_delete' && $_POST["confirm"] == yes)
{
  if ($user->rights->commande->supprimer ) 
    {
      $commande = new CommandeFournisseur($db);
      $commande->id = $_GET["id"];
      $commande->delete();
      Header("Location: index.php");
    }
}

if ($_GET["action"] == 'pdf')
{
  /*
   * Generation de la commande
   * définit dans /includes/modules/commande/modules_commande.php
   */
  commande_pdf_create($db, $_GET["id"]);
} 

if ($_GET["action"] == 'create') 
{

  $fourn = new Fournisseur($db);
  $fourn->fetch($_GET["socid"]);

  if ($fourn->create_commande($user) == 0)
    {
      $idc = $fourn->single_open_commande;
      Header("Location:fiche.php?id=".$idc);
    }
}

llxHeader('',$langs->trans("OrderCard"),"Commande");



$html = new Form($db);


/* *************************************************************************** */
/*                                                                             */
/* Mode vue et edition                                                         */
/*                                                                             */
/* *************************************************************************** */
  
$id = $_GET["id"];
if ($id > 0)
{
  $commande = new CommandeFournisseur($db);
  if ( $commande->fetch($id) == 0)
    {	  
      $soc = new Societe($db);
      $soc->fetch($commande->soc_id);
      $author = new User($db);
      $author->id = $commande->user_author_id;
      $author->fetch();
      
      
      $head[0][0] = DOL_URL_ROOT.'/fourn/commande/fiche.php?id='.$commande->id;
      $head[0][1] = $langs->trans("Order").": $commande->ref";
      $h = 1;
      $a = 0;
      
      dolibarr_fiche_head($head, $a, $soc->nom);	  
      
      /*
       * Confirmation de la suppression de la commande
       *
       */
      if ($_GET["action"] == 'delete')
	{
	  $html->form_confirm("fiche.php?id=$id","Supprimer la commande","Etes-vous sûr de vouloir supprimer cette commande ?","confirm_delete");
	}
	  
      /*
       * Confirmation de la validation
       *
       */
      if ($_GET["action"] == 'valid')
	{
	  //$numfa = commande_get_num($soc);
	  $html->form_confirm("fiche.php?id=$id","Valider la commande","Etes-vous sûr de vouloir valider cette commande ?","confirm_valid");
	}
      /*
       * Confirmation de l'annulation
       *
       */
      if ($_GET["action"] == 'annuler')
	{
	  $html->form_confirm("fiche.php?id=$id",$langs->trans("Cancel"),"Etes-vous sûr de vouloir annuler cette commande ?","confirm_cancel");
	}

      /*
       *   Commande
       */

      print '<table class="border" width="100%">';
      print "<tr><td>".$langs->trans("Supplier")."</td>";
      print '<td colspan="2">';
      print '<b><a href="'.DOL_URL_ROOT.'/comm/fiche.php?socid='.$soc->id.'">'.$soc->nom.'</a></b></td>';
	  
      print '<td width="50%">';
      print '<img src="statut'.$commande->statut.'.png">&nbsp;';
      print $commande->statuts[$commande->statut];
      print "</td></tr>";
	  
      print '<tr><td>'.$langs->trans("Date").'</td>';
      print "<td colspan=\"2\">".strftime("%A %d %B %Y",$commande->date)."</td>\n";

      print '<td width="50%">&nbsp;';
      print "</td></tr>";

      print '<tr><td>'.$langs->trans("Author").'</td><td colspan="2">'.$author->fullname.'</td>';
	
      print '<td>';
      print "&nbsp;</td></tr>";
  
      // Ligne de 3 colonnes
      print '<tr><td>'.$langs->trans("AmountHT").'</td>';
      print '<td align="right"><b>'.price($commande->total_ht).'</b></td>';
      print '<td>'.MAIN_MONNAIE.'</td>';
      print '<td rowspan="4" valign="top">'.$langs->trans("Note").' :</td></tr>';



      print '<tr><td>'.$langs->trans("VAT").'</td><td align="right">'.price($commande->total_tva).'</td>';
      print '<td>'.MAIN_MONNAIE.'</td></tr>';
      print '<tr><td>'.$langs->trans("TotalTTC").'</td><td align="right">'.price($commande->total_ttc).'</td>';
      print '<td>'.MAIN_MONNAIE.'</td></tr>';
      if ($commande->note)
	{
	  print '<tr><td colspan="3">Note : '.nl2br($commande->note)."</td></tr>";
	}
	  
      print "</table>";
	  
      /*
       * Lignes de commandes
       *
       */
      echo '<br><table class="noborder" width="100%">';	  

      $sql = "SELECT l.ref, l.fk_product, l.description, l.price, l.qty, l.rowid, l.tva_tx, l.remise_percent, l.subprice";
      $sql .= " FROM ".MAIN_DB_PREFIX."commande_fournisseurdet as l ";
      $sql .= " WHERE l.fk_commande = $id ORDER BY l.rowid";
	  
      $result = $db->query($sql);
      if ($result)
	{
	  $num = $db->num_rows();
	  $i = 0; $total = 0;
	      
	  if ($num)
	    {
	      print '<tr class="liste_titre">';
	      print '<td width="8%" align="left">'.$langs->trans("Ref").'</td>';
	      print '<td width="46%">'.$langs->trans("Description").'</td>';
	      print '<td width="8%" align="center">'.$langs->trans("VAT").'</td>';
	      print '<td width="8%" align="center">'.$langs->trans("Qty").'</td>';
	      print '<td width="7%" align="right">'.$langs->trans("Discount").'</td>';
	      print '<td width="10%" align="right">P.U.</td>';
	      print '<td width="5%">&nbsp;</td><td width="10%">&nbsp;</td>';
	      print "</tr>\n";
	    }
	  $var=True;
	  while ($i < $num)
	    {
	      $objp = $db->fetch_object();
	      print "<tr $bc[$var]>";
	      print "<td>".$objp->ref."</td>\n";
	      if ($objp->fk_product > 0)
		{
		  print '<td>';
		  print '<a href="'.DOL_URL_ROOT.'/product/fiche.php?id='.$objp->fk_product.'">'.stripslashes(nl2br($objp->description)).'</a></td>';
		}
	      else
		{
		  print "<td>".stripslashes(nl2br($objp->description))."</TD>\n";
		}
	      print '<td align="center">'.$objp->tva_tx.' %</TD>';
	      print '<td align="center">'.$objp->qty.'</TD>';
	      if ($objp->remise_percent > 0)
		{
		  print '<td align="right">'.$objp->remise_percent." %</td>\n";
		}
	      else
		{
		  print '<td>&nbsp;</td>';
		}
	      print '<td align="right">'.price($objp->subprice)."</td>\n";
	      if ($commande->statut == 0  && $user->rights->commande->creer) 
		{
		  print '<td align="right"><a href="fiche.php?id='.$id.'&amp;action=editline&amp;rowid='.$objp->rowid.'">';
		  print img_edit();
		  print '</a></td>';
		  print '<td align="right"><a href="fiche.php?id='.$id.'&amp;action=deleteline&amp;lineid='.$objp->rowid.'">';
		  print img_delete();
		  print '</a></td>';
		}
	      else
		{
		  print '<td>&nbsp;</td><td>&nbsp;</td>';
		}
	      print "</tr>";
		  
	      if ($_GET["action"] == 'editline' && $_GET["rowid"] == $objp->rowid)
		{
		  print "<form action=\"fiche.php?id=$id\" method=\"post\">";
		  print '<input type="hidden" name="action" value="updateligne">';
		  print '<input type="hidden" name="elrowid" value="'.$_GET["rowid"].'">';
		  print "<tr $bc[$var]>";
		  print '<td colspan="3"><textarea name="eldesc" cols="60" rows="2">'.stripslashes($objp->description).'</textarea></td>';
		  print '<td align="center"><input size="4" type="text" name="elqty" value="'.$objp->qty.'"></TD>';
		  print '<td align="right"><input size="3" type="text" name="elremise_percent" value="'.$objp->remise_percent.'">&nbsp;%</td>';
		  print '<td align="right"><input size="8" type="text" name="elprice" value="'.price($objp->subprice).'"></td>';
		  print '<td align="right" colspan="2"><input type="submit" value="'.$langs->trans("Save").'"></td>';
		  print '</tr>' . "\n";
		  print "</form>\n";
		}
	      $i++;
	      $var=!$var;
	    }	      
	  $db->free();
	} 
      else
	{
	  print $db->error();
	}
	
      /*
       * Ajouter une ligne
       *
       */
      if ($commande->statut == 0 && $user->rights->commande->creer) 
	{
	  $sql = "SELECT p.rowid,p.label,p.ref,p.price ";
	  $sql .= " FROM ".MAIN_DB_PREFIX."product as p ";
	  $sql .= " , ".MAIN_DB_PREFIX."product_fournisseur as pf ";
	  $sql .= " WHERE p.rowid = pf.fk_product AND pf.fk_soc = ".$commande->fourn_id;
	  $sql .= " ORDER BY p.ref ";
	  if ( $db->query($sql) )
	    {
	      $opt = "<option value=\"0\" SELECTED></option>";
	      if ($result)
		{
		  $num = $db->num_rows();	$i = 0;	
		  while ($i < $num)
		    {
		      $objp = $db->fetch_object();
		      $opt .= "<option value=\"$objp->rowid\">[$objp->ref] $objp->label : $objp->price</option>\n";
		      $i++;
		    }
		}
	      $db->free();
	    }
	  else
	    {
	      print $db->error();
	    }

	  print "<form action=\"fiche.php?id=$id\" method=\"post\">";
	  print '<input type="hidden" name="action" value="addligne">';

	  print "<tr class=\"liste_titre\">";
	  print '<td colspan="2" width="54%">'.$langs->trans("Description").'</td>';
	  print '<td width="8%" align="center">Tva</td>';
	  print '<td width="8%" align="center">Quantité</td>';
	  print '<td width="8%" align="right">Remise</td>';
	  print '<td width="12%" align="right">P.U.</TD>';
	  print '<td>&nbsp;</td><td>&nbsp;</td>'."</tr>\n";

	  print "<tr $bc[$var]>".'<td colspan="2"><textarea name="desc" cols="60" rows="1"></textarea></td>';
	  print '<td align="center">';
	  print $html->select_tva("tva_tx",$conf->defaulttx);
	  print '</td>';
	  print '<td align="center"><input type="text" name="qty" value="1" size="2"></td>';
	  print '<td align="right"><input type="text" name="remise_percent" size="4" value="0">&nbsp;%</td>';
	  print '<td align="right"><input type="text" name="pu" size="8"></td>';

	  print '<td align="center" colspan="3"><input type="submit" value="'.$langs->trans("Add").'"></td></tr>';

	  $var=!$var;
	  print "<tr $bc[$var]>".'<td colspan="3"><select name="p_idprod">'.$opt.'</select></td>';
	  print '<td align="center"><input type="text" size="2" name="pqty" value="1"></td>';
	  print '<td align="right"><input type="text" size="4" name="premise" value="0"> %</td>';
	  print '<td>&nbsp;</td>';
	  print '<td align="center" colspan="3"><input type="submit" value="'.$langs->trans("Add").'"></td></tr>';
	  print "</tr>\n";

	  print "</form>";
	}
      print "</table><br>";
      /*
       * Fin Ajout ligne
       *
       */

      print '</div>';

      if ($user->societe_id == 0 && $commande->statut < 3)
	{
	  print '<div class="tabsAction">';
	
	  if ($commande->statut == 0 && $user->rights->commande->supprimer)
	    {
	      print '<a class="tabAction" href="fiche.php?id='.$id.'&amp;action=delete">'.$langs->trans("Delete").'</a>';
	    } 
	    
	  if ($commande->statut > 0 && $commande->statut < 3 && $user->rights->expedition->creer)
	    {
	      print '<a class="tabAction" href="'.DOL_URL_ROOT.'/expedition/commande.php?id='.$_GET["id"].'">Expédier</a>';
	    }
	  
	    
	  if ($commande->statut == 0) 
	    {
	      if ($user->rights->commande->valider)
		{
		  print '<a class="tabAction" href="fiche.php?id='.$id.'&amp;action=valid">'.$langs->trans("Valid").'</a>';
		}
	    }
	    
	  if ($commande->statut == 1)
	    {
	      $nb_expedition = $commande->nb_expedition();
	      if ($user->rights->commande->valider && $nb_expedition == 0)
		{
		  print '<a class="tabAction" href="fiche.php?id='.$id.'&amp;action=annuler">'.$langs->trans("Cancel").'</a>';
		}
	    }

	  print "</div>";
	}
      print "<p>\n";


      print '<table width="100%" cellspacing="2"><tr><td width="50%" valign="top">';
      /*
       * Liste des expéditions
       */
      $sql = "SELECT e.rowid,e.ref,".$db->pdate("e.date_expedition")." as de";
      $sql .= " FROM ".MAIN_DB_PREFIX."expedition as e";
      $sql .= " WHERE e.fk_commande = ". $commande->id;
	    
      $result = $db->query($sql);
      if ($result)
	{
	  $num = $db->num_rows();
	  if ($num)
	    {
	      print_titre("Expéditions");
	      $i = 0; $total = 0;
	      print '<table class="border" width="100%">';
	      print "<tr $bc[$var]><td>Expédition</td><td>Date</td></tr>\n";
		
	      $var=True;
	      while ($i < $num)
		{
		  $objp = $db->fetch_object();
		  $var=!$var;
		  print "<tr $bc[$var]>";
		  print '<td><a href="../expedition/fiche.php?id='.$objp->rowid.'">'.stripslashes($objp->ref).'</a></td>';
		  print "<td>".strftime("%d %B %Y",$objp->de)."</td></tr>\n";
		  $i++;
		}
	      print "</table>";
	    }
	}
      else
	{
	  print $db->error();
	}
      print "&nbsp;</td><td>";
	
      /*
       * Liste des factures
       */
      $sql = "SELECT f.rowid,f.facnumber,".$db->pdate("f.datef")." as df";
      $sql .= " FROM ".MAIN_DB_PREFIX."facture as f, ".MAIN_DB_PREFIX."co_fa as cf";
      $sql .= " WHERE f.rowid = cf.fk_facture AND cf.fk_commande = ". $commande->id;
	    
      $result = $db->query($sql);
      if ($result)
	{
	  $num = $db->num_rows();
	  if ($num)
	    {
	      print_titre("Factures");
	      $i = 0; $total = 0;
	      print '<table class="border" width="100%">';
	      print "<tr $bc[$var]><td>Facture</td><td>".$langs->trans("Date")."</td></tr>\n";
		
	      $var=True;
	      while ($i < $num)
		{
		  $objp = $db->fetch_object();
		  $var=!$var;
		  print "<tr $bc[$var]>";
		  print '<td><a href="../compta/facture.php?facid='.$objp->rowid.'">'.stripslashes($objp->facnumber).'</a></td>';
		  print "<td>".strftime("%d %B %Y",$objp->df)."</td></tr>\n";
		  $i++;
		}
	      print "</table>";
	    }
	}
      else
	{
	  print $db->error();
	}
      print "&nbsp;</td></tr></table>";

      /*
       * Documents générés
       *
       */
      $file = FAC_OUTPUTDIR . "/" . $commande->ref . "/" . $commande->ref . ".pdf";
	
      if (file_exists($file))
	{

	  print_titre("Documents");
	  print '<table width="100%" class="border">';
	    
	  print "<tr $bc[0]><td>Commande PDF</td>";
	  print '<td><a href="'.FAC_OUTPUT_URL."/".$commande->ref."/".$commande->ref.'.pdf">'.$commande->ref.'.pdf</a></td>';
	  print '<td align="right">'.filesize($file). ' bytes</td>';
	  print '<td align="right">'.strftime("%d %b %Y %H:%M:%S",filemtime($file)).'</td>';
	  print '</tr>';
	           	
	  print "</table>\n";
	  print '</td><td valign="top" width="50%">';
	}
      /*
       *
       *
       */
      if ($_GET["action"] == 'classer')
	{	    
	  print '<p><form method="post" action="fiche.php?id='.$commande->id.'">';
	  print '<input type="hidden" name="action" value="classin">';
	  print '<table class="border">';
	  print '<tr><td>Projet</td><td>';
	    
	  $proj = new Project($db);
	  $html->select_array("projetid",$proj->liste_array($commande->soc_id));
	    
	  print "</td></tr>";
	  print '<tr><td colspan="2" align="center"><input type="submit" value="Envoyer"></td></tr></table></form>';
	}
      /*
       *
       *
       */
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
