<?php
/* Copyright (C) 2004-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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

/**
   \file       htdocs/fourn/commande/fiche.php
   \ingroup    commande
   \brief      Fiche commande
   \version    $Revision$

*/

require("./pre.inc.php");

$langs->load("orders");
$langs->load("suppliers");
$langs->load("companies");

$user->getrights('fournisseur');

if (!$user->rights->fournisseur->commande->lire) accessforbidden();

require_once DOL_DOCUMENT_ROOT."/project.class.php";
require_once DOL_DOCUMENT_ROOT."/propal.class.php";
require_once DOL_DOCUMENT_ROOT."/fournisseur.class.php";

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
if ($_GET["action"] == 'pdf') 
{
  $commande = new CommandeFournisseur($db);
  $commande->fetch($_GET["id"]);
  $commande->generate_pdf();
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
if ($_POST["action"] == 'addligne' && $user->rights->fournisseur->commande->creer) 
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

if ($_GET["action"] == 'deleteline' && $user->rights->fournisseur->commande->creer) 
{
  $comf = new CommandeFournisseur($db);
  $comf->fetch($_GET["id"]);
  $result = $comf->delete_line($_GET["lineid"]);
}

if ($_POST["action"] == 'confirm_valid' && $_POST["confirm"] == yes && $user->rights->fournisseur->commande->valider)
{
  $commande = new CommandeFournisseur($db);
  $commande->fetch($_GET["id"]);
  $soc = new Societe($db);
  $soc->fetch($commande->soc_id);
  $result = $commande->valid($user);
  Header("Location: fiche.php?id=".$_GET["id"]);
}

if ($_POST["action"] == 'confirm_approve' && $_POST["confirm"] == yes && $user->rights->fournisseur->commande->approuver)
{
  $commande = new CommandeFournisseur($db);
  $commande->fetch($_GET["id"]);
  $result = $commande->approve($user);
  Header("Location: fiche.php?id=".$_GET["id"]);
}

if ($_POST["action"] == 'confirm_refuse' && $_POST["confirm"] == yes && $user->rights->fournisseur->commande->approuver)
{
  $commande = new CommandeFournisseur($db);
  $commande->fetch($_GET["id"]);
  $result = $commande->refuse($user);
  Header("Location: fiche.php?id=".$_GET["id"]);
}

if ($_POST["action"] == 'confirm_commande' && $_POST["confirm"] == yes && $user->rights->fournisseur->commande->commander)
{
  $commande = new CommandeFournisseur($db);
  $commande->fetch($_GET["id"]);
  $result = $commande->commande($user, $_GET["datecommande"], $_GET["methode"]);
  Header("Location: fiche.php?id=".$_GET["id"]);
}


if ($_POST["action"] == 'confirm_delete' && $_POST["confirm"] == yes && $user->rights->fournisseur->commande->creer )
{
  $commande = new CommandeFournisseur($db);
  $commande->id = $_GET["id"];
  $commande->delete();
  Header("Location: index.php");
}

if ($_POST["action"] == 'livraison' && $user->rights->fournisseur->commande->receptionner)
{
  $commande = new CommandeFournisseur($db);
  $commande->fetch($_GET["id"]);

  $date_liv = mktime(0,0,0,$_POST["remonth"],$_POST["reday"],$_POST["reyear"]);

  $result = $commande->Livraison($user, $date_liv, $_POST["type"]);
  Header("Location: fiche.php?id=".$_GET["id"]);
}


/*
 * Créé une commande
 */
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
  
if ($_GET["id"] > 0)
{
  $commande = new CommandeFournisseur($db);
  if ( $commande->fetch($_GET["id"]) == 0)
    {	  
      $soc = new Societe($db);
      $soc->fetch($commande->soc_id);
      $author = new User($db);
      $author->id = $commande->user_author_id;
      $author->fetch();
      
      $h = 0;
      $head[$h][0] = DOL_URL_ROOT.'/fourn/commande/fiche.php?id='.$commande->id;
      $head[$h][1] = $langs->trans("Order").": $commande->ref";
      $a = $h;
      $h++;

      $head[$h][0] = DOL_URL_ROOT.'/fourn/commande/note.php?id='.$commande->id;
      $head[$h][1] = $langs->trans("Note");
      $h++;
      
      $head[$h][0] = DOL_URL_ROOT.'/fourn/commande/history.php?id='.$commande->id;
      $head[$h][1] = $langs->trans("History");
      $h++;

      dolibarr_fiche_head($head, $a, $soc->nom);	  
      
      /*
       * Confirmation de la suppression de la commande
       *
       */
      if ($_GET["action"] == 'delete')
	{
	  $html->form_confirm("fiche.php?id=$commande->id","Supprimer la commande","Etes-vous sûr de vouloir supprimer cette commande ?","confirm_delete");
	  print '<br />';
	}
	  
      /*
       * Confirmation de la validation
       *
       */
      if ($_GET["action"] == 'valid')
	{
	  $html->form_confirm("fiche.php?id=$commande->id","Valider la commande","Etes-vous sûr de vouloir valider cette commande ?","confirm_valid");
	  print '<br />';
	}
      /*
       * Confirmation de l'approbation
       *
       */
      if ($_GET["action"] == 'approve')
	{
	  $html->form_confirm("fiche.php?id=$commande->id","Approuver la commande","Etes-vous sûr de vouloir approuver cette commande ?","confirm_approve");
	  print '<br />';
	}
      /*
       * Confirmation de l'approbation
       *
       */
      if ($_GET["action"] == 'refuse')
	{
	  $html->form_confirm("fiche.php?id=$commande->id","Refuser la commande","Etes-vous sûr de vouloir refuser cette commande ?","confirm_refuse");
	  print '<br />';
	}
      /*
       * Confirmation de l'annulation
       *
       */
      if ($_GET["action"] == 'annuler')
	{
	  $html->form_confirm("fiche.php?id=$commande->id",$langs->trans("Cancel"),"Etes-vous sûr de vouloir annuler cette commande ?","confirm_cancel");
	  print '<br />';
	}
      /*
       * Confirmation de l'envoi de la commande
       *
       */
      if ($_GET["action"] == 'commande')
	{
	  $date_com = mktime(0,0,0,$_POST["remonth"],$_POST["reday"],$_POST["reyear"]);
	  $html->form_confirm("fiche.php?id=".$commande->id."&amp;datecommande=".$date_com."&amp;methode=".$_POST["methodecommande"],
			      "Envoi de la commande","Etes-vous sûr de vouloir confirmer cette commande en date du ".strftime("%d/%m/%Y",$date_com)." ?","confirm_commande");
	  print '<br />';
	}
      /*
       *   Commande
       */
      print '<table class="border" width="100%">';
      print "<tr><td>".$langs->trans("Supplier")."</td>";
      print '<td colspan="2">';
      print '<b><a href="'.DOL_URL_ROOT.'/fourn/fiche.php?socid='.$soc->id.'">'.$soc->nom.'</a></b></td>';
	  
      print '<td width="50%" colspan="3">';
      print '<img src="statut'.$commande->statut.'.png">&nbsp;';
      print $commande->statuts[$commande->statut];
      print "</td></tr>";
	  
      print '<tr><td>'.$langs->trans("Date").'</td>';
      print '<td colspan="2">';
      
      if ($commande->date_commande)
	{
	  print strftime("%A %d %B %Y",$commande->date_commande)."\n";
	}

      print '&nbsp;</td><td width="50%" colspan="3">';
      if ($commande->methode_commande)
	{
	  print "Méthode : " .$commande->methode_commande;
	}
      print "</td></tr>";
      print '<tr><td>'.$langs->trans("Author").'</td><td colspan="2">'.$author->fullname.'</td>';	
      print '<td colspan="3">';
      print "&nbsp;</td></tr>";
  
      // Ligne de 3 colonnes
      print '<tr><td>'.$langs->trans("AmountHT").'</td>';
      print '<td align="right"><b>'.price($commande->total_ht).'</b></td>';
      print '<td>'.$conf->monnaie.'</td>';
      print '<td>'.$langs->trans("VAT").'</td><td align="right">'.price($commande->total_tva).'</td>';
      print '<td>'.$conf->monnaie.'</td></tr>';

      print '<tr><td>'.$langs->trans("TotalTTC").'</td><td align="right">'.price($commande->total_ttc).'</td>';
      print '<td>'.$conf->monnaie.'</td><td colspan="3">&nbsp;</td></tr>';

      if ($commande->note)
	{
	  print '<tr><td>Note</td><td colspan="5">'.nl2br($commande->note)."</td></tr>";
	}
	  
      print "</table>";
	  
      /*
       * Lignes de commandes
       *
       */
      echo '<br><table class="noborder" width="100%">';	  

      $sql = "SELECT l.ref, l.fk_product, l.description, l.price, l.qty, l.rowid, l.tva_tx, l.remise_percent, l.subprice";
      $sql .= " FROM ".MAIN_DB_PREFIX."commande_fournisseurdet as l ";
      $sql .= " WHERE l.fk_commande = $commande->id ORDER BY l.rowid";
	  
      $result = $db->query($sql);
      if ($result)
	{
	  $num_lignes = $db->num_rows();
	  $i = 0; $total = 0;
	      
	  if ($num_lignes)
	    {
	      print '<tr class="liste_titre">';
	      print '<td width="8%" align="left">'.$langs->trans("Ref").'</td>';
	      print '<td width="46%">'.$langs->trans("Description").'</td>';
	      print '<td width="8%" align="center">'.$langs->trans("VAT").'</td>';
	      print '<td width="8%" align="center">'.$langs->trans("Qty").'</td>';
	      print '<td width="7%" align="right">'.$langs->trans("Discount").'</td>';
	      print '<td width="10%" align="right">'.$langs->trans("PriceU").'</td>';
	      print '<td width="5%">&nbsp;</td><td width="10%">&nbsp;</td>';
	      print "</tr>\n";
	    }
	  $var=True;
	  while ($i < $num_lignes)
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
	      if ($commande->statut == 0  && $user->rights->fournisseur->commande->creer && $_GET["action"] <> 'valid') 
		{
		  print '<td>&nbsp;</td><td align="right"><a href="fiche.php?id='.$commande->id.'&amp;action=deleteline&amp;lineid='.$objp->rowid.'">';
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
		  print "<form action=\"fiche.php?id=$commande->id\" method=\"post\">";
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
      if ($_GET["action"] <> 'valid' && $commande->statut == 0 && $user->rights->fournisseur->commande->creer) 
	{
	  $sql = "SELECT p.rowid,p.label,p.ref ";
	  $sql .=" , pf.price";
	  $sql .= " FROM ".MAIN_DB_PREFIX."product as p ";
	  $sql .= " , ".MAIN_DB_PREFIX."product_fournisseur_price as pf ";
	  $sql .= " WHERE p.rowid = pf.fk_product AND pf.fk_soc = ".$commande->fourn_id;
	  $sql .= " AND quantity = 1";
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

	  print '<form action="fiche.php?id='.$commande->id.'" method="post">';
	  print '<input type="hidden" name="action" value="addligne">';

	  print "<tr class=\"liste_titre\">";
	  print '<td colspan="2" width="54%">'.$langs->trans("Description").'</td>';
	  print '<td width="8%" align="center">'.$langs->trans("VAT").'</td>';
	  print '<td width="8%" align="center">'.$langs->trans("Qty").'</td>';
	  print '<td width="8%" align="right">'.$langs->trans("Discount").'</td>';
	  print '<td width="12%" align="right">'.$langs->trans("PriceU").'</td>';
	  print '<td>&nbsp;</td><td>&nbsp;</td>'."</tr>\n";

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

      if ($user->societe_id == 0 && $commande->statut < 3 && $_GET["action"] <> 'valid')
	{
	  print '<div class="tabsAction">';
	
	  if ($commande->statut == 0 && $num_lignes > 0) 
	    {
	      if ($user->rights->fournisseur->commande->valider)
		{
		  print '<a class="butAction" href="fiche.php?id='.$commande->id.'&amp;action=valid">'.$langs->trans("Valid").'</a>';
		}
	    }
	    
	  if ($commande->statut == 1) 
	    {
	      if ($user->rights->fournisseur->commande->approuver)
		{
		  print '<a class="butAction" href="fiche.php?id='.$commande->id.'&amp;action=approve">'.$langs->trans("ApproveOrder").'</a>';

		  print '<a class="butActionRefuse" href="fiche.php?id='.$commande->id.'&amp;action=refuse">'.$langs->trans("RefuseOrder").'</a>';
		}
	    }

	  if ($commande->statut == 2) 
	    {
	      if ($user->rights->fournisseur->commande->approuver)
		{
		  print '<a class="butActionRefuse" href="fiche.php?id='.$commande->id.'&amp;action=refuse">'.$langs->trans("RefuseOrder").'</a>';
		}
	    }

	  if ($commande->statut == 0) 
	    {
	      if ($user->rights->fournisseur->commande->creer)
		{
		  print '<a class="butActionDelete" href="fiche.php?id='.$commande->id.'&amp;action=delete">'.$langs->trans("Delete").'</a>';
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
	  dolibarr_print_error($db);
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
	  $num = $db->num_rows($result);
	  if ($num)
	    {
	      print_titre($langs->trans("Bills"));
	      $i = 0; $total = 0;
	      print '<table class="border" width="100%">';
	      print "<tr $bc[$var]><td>".$langs->trans("Bills")."</td><td>".$langs->trans("Date")."</td></tr>\n";
		
	      $var=True;
	      while ($i < $num)
		{
		  $objp = $db->fetch_object($result);
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
	  dolibarr_print_error($db);
	}
      print "&nbsp;</td></tr></table>";

      /*
       * Documents générés
       *
       */
      $file = $conf->commande->dir_output . "/" . $commande->ref . "/" . $commande->ref . ".pdf";
	  $relativepath=$commande->ref . "/" . $commande->ref . ".pdf";
	  
	  $var=true;
	  
      if (file_exists($file))
	{
	  print_titre($langs->trans("Documents"));
	  print '<table width="100%" class="border">';
	    
	  print "<tr $bc[$var]><td>".$langs->trans("Order")." PDF</td>";
	  print '<td><a href="'.DOL_URL_ROOT.'/document.php?modulepart=commande&file='.urlencode($relativepath).'">'.$commande->ref.'.pdf</a></td>';
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
      if ( $user->rights->fournisseur->commande->commander && $commande->statut == 2)
	{
	  /**
	   * Commander
	   */
	  $form = new Form($db);
	  
	  print '<form action="fiche.php?id='.$commande->id.'&amp;action=commande" method="post">';
	  print '<table class="noborder" cellpadding="4" cellspacing="0">';
	  print '<tr class="liste_titre"><td colspan="2">Commander</td></tr>';
	  print '<tr><td>Date commande</td><td>';
	  print $form->select_date();
	  print '</td></tr>';

	  $commande->get_methodes_commande();

	  print '<tr><td>Méthode de commande</td><td>';

	  print $form->select_array("methodecommande",$commande->methodes_commande);

	  print '</td></tr>';
	  print '<tr><td>Commentaire</td><td><input size="30" type="text" name="commentaire"></td></tr>';
	  print '<tr><td align="center" colspan="2"><input type="submit" name="Activer"></td></tr>';
	  print '</table>';
	  print '</form>';	  
	}
      /*
       *
       *
       */
      if ( $user->rights->fournisseur->commande->receptionner && ($commande->statut == 3 ||$commande->statut == 4 ))
	{
	  /**
	   * Réceptionner
	   */
	  $form = new Form($db);
	  
	  print '<form action="fiche.php?id='.$commande->id.'" method="post">';
	  print '<input type="hidden" name="action" value="livraison">';
	  print '<table class="noborder" cellpadding="4" cellspacing="0">';
	  print '<tr class="liste_titre"><td colspan="2">Réceptionner</td></tr>';
	  print '<tr><td>Date de livraison</td><td>';
	  print $form->select_date();
	  print "</td></tr>\n";

	  print "<tr><td>Livraison</td><td>\n";
	  $liv = array();
	  $liv['par'] = "Partielle";
	  $liv['tot'] = "Totale";

	  print $form->select_array("type",$liv);

	  print '</td></tr>';
	  print '<tr><td>Commentaire</td><td><input size="30" type="text" name="commentaire"></td></tr>';
	  print '<tr><td align="center" colspan="2"><input type="submit" name="Activer"></td></tr>';
	  print "</table>\n";
	  print "</form>\n";	  
	}
    }
  else
    {
      /* Commande non trouvée */
      print "Commande inexistante";
    }
}  


$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
