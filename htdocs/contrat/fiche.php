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

/*!	\file htdocs/commande/fiche.php
		\ingroup    commande
		\brief      Fiche commande
		\version    $Revision$
*/

require("./pre.inc.php");

$langs->load("contracts");
$langs->load("orders");
$langs->load("companies");

$user->getrights('contrat');

if (!$user->rights->contrat->lire)
  accessforbidden();

require("../project.class.php");
require("../propal.class.php");
require_once (DOL_DOCUMENT_ROOT."/contrat/contrat.class.php");

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
  $datecontrat = mktime(12, 0 , 0, $_POST["remonth"], $_POST["reday"], $_POST["reyear"]); 

  $contrat = new Contrat($db);

  $contrat->soc_id         = $_POST["soc_id"];
  $contrat->date_contrat   = $datecontrat;  
  $contrat->commercial_id  = $_POST["commercial"];
  $contrat->note           = $_POST["note"];
  $contrat->projetid       = $_POST["projetid"];
  $contrat->remise_percent = $_POST["remise_percent"];
  
  /*
  $contrat->add_product($_POST["idprod1"],$_POST["qty1"],$_POST["remise_percent1"]);
  $contrat->add_product($_POST["idprod2"],$_POST["qty2"],$_POST["remise_percent2"]);
  $contrat->add_product($_POST["idprod3"],$_POST["qty3"],$_POST["remise_percent3"]);
  $contrat->add_product($_POST["idprod4"],$_POST["qty4"],$_POST["remise_percent4"]);
  */
  $result = $contrat->create($user);
  if ($result == 0)
    {      
      Header("Location: fiche.php?id=".$contrat->id);
    }
  
  $_GET["id"] = $contrat->id;

  $action = '';  
}
/*
 *
 */	
if ($_POST["action"] == 'classin') 
{
  $commande = new Commande($db);
  $commande->fetch($_GET["id"]);
  $commande->classin($_POST["projetid"]);
}

/*
 *
 */

if ($_POST["action"] == 'addligne' && $user->rights->contrat->creer) 
{
  $result = 0;
  $contrat = new Contrat($db);
  $contrat->fetch($_GET["id"]);

  if ($_POST["p_idprod"] > 0)
    {
      $result = $contrat->addline($_POST["desc"],
				  $_POST["pu"],
				  $_POST["pqty"],
				  $_POST["tva_tx"],
				  $_POST["p_idprod"],
				  $_POST["premise"]);
    }
  
  if ($result == 0)
    {      
      Header("Location: fiche.php?id=".$contrat->id);
    }
}

if ($_POST["action"] == 'updateligne' && $user->rights->contrat->creer) 
{
  $contrat = new Contrat($db,"",$_GET["id"]);
  if ($contrat->fetch($_GET["id"]) )
    {
      $result = $contrat->update_line($_POST["elrowid"],
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

if ($_GET["action"] == 'deleteline' && $user->rights->contrat->creer) 
{
  $contrat = new Contrat($db);
  $contrat->fetch($_GET["id"]);
  $result = $contrat->delete_line($_GET["lineid"]);

  if ($result == 0)
    {      
      Header("Location: fiche.php?id=".$contrat->id);
    }
}

if ($_POST["action"] == 'confirm_valid' && $_POST["confirm"] == yes && $user->rights->contrat->valider)
{
  $contrat = new Contrat($db);
  $contrat->fetch($_GET["id"]);
  $soc = new Societe($db);
  $soc->fetch($contrat->soc_id);
  $result = $contrat->valid($user);
}

if ($_POST["action"] == 'confirm_cancel' && $_POST["confirm"] == yes && $user->rights->contrat->valider)
{
  $contrat = new Contrat($db);
  $contrat->fetch($_GET["id"]);
  $result = $contrat->cancel($user);
}

if ($_POST["action"] == 'confirm_delete' && $_POST["confirm"] == yes)
{
  if ($user->rights->commande->supprimer ) 
    {
      $commande = new Commande($db);
      $commande->id = $_GET["id"];
      $commande->delete();
      Header("Location: index.php");
    }
}

/*
 *
 */
if ($action == 'pdf')
{
  /*
   * Generation de la commande
   * définit dans /includes/modules/commande/modules_commande.php
   */
  commande_pdf_create($db, $_GET["id"]);
} 

llxHeader('',$langs->trans("OrderCard"),"Commande");



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
  dolibarr_fiche_head($head, $a, "Création d'un nouveau contrat");	  

  $new_contrat = new Contrat($db);

  $sql = "SELECT s.nom, s.prefix_comm, s.idp ";
  $sql .= "FROM ".MAIN_DB_PREFIX."societe as s ";
  $sql .= "WHERE s.idp = ".$_GET["socid"];      
  

  if ( $db->query($sql) ) 
    {
      $num = $db->num_rows();
      if ($num)
	{
	  $obj = $db->fetch_object();

	  $soc = new Societe($db);
	  $soc->fetch($obj->idp);
       
	  print '<form action="fiche.php" method="post">';
	  print '<input type="hidden" name="action" value="add">';
	  print '<input type="hidden" name="soc_id" value="'.$soc->id.'">' ."\n";
	  print '<input type="hidden" name="remise_percent" value="0">';

	  print '<table class="border" cellspacing="0" cellpadding="3" width="100%">';
	  
	  print '<tr><td>'.$langs->trans("Customer").' :</td><td>'.$obj->nom.'</td></tr>';

	  print '<tr><td width="20%">'.$langs->trans("Commercial").'</td><td>';
	  print '<select name="commercial">';

	  $sql = "SELECT rowid, name, firstname FROM ".MAIN_DB_PREFIX."user ORDER BY name ";
	  if ( $db->query( $sql) )
	    {
	      $num = $db->num_rows();
	      if ( $num > 0 )
		{
		  $i = 0;
		  while ($i < $num)
		    {
		      $row = $db->fetch_row($i);
		      print '<option value="'.$row[0].'">'.$row[1] . " " . $row[2];
		      $i++;
		    }
		}
	      $db->free();
	      
	    }
	  print '</select></td></tr>';
	  	  
	  print "<tr><td>Date :</td><td>";
	
	  print_date_select(time());

	  print "</td></tr>";

	  print "<tr><td>Projet :</td><td>";
	  $proj = new Project($db);
	  $html->select_array("projetid",$proj->liste_array($soc->id),0,1);
	  print "</td></tr>";

	  /*	  
	   *
	   *
	   * Liste des elements
	   *
	   *
	  print '<tr><td colspan="3">'.$langs->trans("Services").'/'.$langs->trans("Products").'</td></tr>';
	  print '<tr><td colspan="3">';

	  $sql = "SELECT p.rowid,p.label,p.ref,p.price FROM ".MAIN_DB_PREFIX."product as p ";
	  $sql .= " WHERE envente = 1";
	  $sql .= " ORDER BY p.nbvente DESC LIMIT 20";
	  if ( $db->query($sql) )
	    {
	      $opt = "<option value=\"0\" selected></option>";
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
	  
	  print '<table class="noborder" cellspacing="0">';
	  print '<tr><td>20 Produits les plus vendus</td><td>Quan.</td><td>Remise</td></tr>';
	  for ($i = 1 ; $i < 5 ; $i++)
	    {
	      print '<tr><td><select name="idprod'.$i.'">'.$opt.'</select></td>';
	      print '<td><input type="text" size="3" name="qty'.$i.'" value="1"></td>';
	      print '<td><input type="text" size="4" name="remise_percent'.$i.'" value="0"> %</td></tr>';
	    }	      	      
	  
	  print '</table>';
	  print '</td></tr>';
	  */  
	  print '<tr><td>Commentaires</td><td valign="top">';
	  print '<textarea name="note" wrap="soft" cols="60" rows="4"></textarea></td></tr>';	

	  /*
	   *
	   */	  
	  print '<tr><td colspan="3" align="center"><input type="submit" value="Créer"></td></tr>';
	  print "</form>\n";
	  print "</table>\n";

	  if ($propalid)
	    {
	      /*
	       * Produits
	       */
	      print_titre("Produits");
	      
	      print '<table class="noborder" width="100%" cellspacing="0" cellpadding="3">';
	      print '<tr class="liste_titre"><td>'.$langs->trans("Ref").'</td><td>Produit</td>';
	      print '<td align="right">'.$langs->trans("Price").'</td><td align="center">Remise</td><td align="center">Qté.</td></tr>';
	      
	      $sql = "SELECT pt.rowid, p.label as product, p.ref, pt.price, pt.qty, p.rowid as prodid, pt.remise_percent";
	      $sql .= " FROM ".MAIN_DB_PREFIX."propaldet as pt, ".MAIN_DB_PREFIX."product as p WHERE pt.fk_product = p.rowid AND pt.fk_propal = $propalid";
	      $sql .= " ORDER BY pt.rowid ASC";
	      $result = $db->query($sql);
	      if ($result) 
		{
		  $num = $db->num_rows();
		  $i = 0;	
		  $var=True;	
		  while ($i < $num) 
		    {
		      $objp = $db->fetch_object();
		      $var=!$var;
		      print "<tr $bc[$var]><td>[$objp->ref]</td>\n";
		      print '<td>'.$objp->product.'</td>';
		      print "<td align=\"right\">".price($objp->price)."</TD>";
		      print '<td align="center">'.$objp->remise_percent.' %</td>';
		      print "<td align=\"center\">".$objp->qty."</td></tr>\n";
		      $i++;
		    }
		}
	      $sql = "SELECT pt.rowid, pt.description as product,  pt.price, pt.qty, pt.remise_percent";
	      $sql .= " FROM ".MAIN_DB_PREFIX."propaldet as pt  WHERE  pt.fk_propal = $propalid AND pt.fk_product = 0";
	      $sql .= " ORDER BY pt.rowid ASC";
	      if ($db->query($sql)) 
		{
		  $num = $db->num_rows();
		  $i = 0;	
		  while ($i < $num) 
		    {
		      $objp = $db->fetch_object();
		      $var=!$var;
		      print "<tr $bc[$var]><td>&nbsp;</td>\n";
		      print '<td>'.$objp->product.'</td>';
		      print '<td align="right">'.price($objp->price).'</td>';
		      print '<td align="center">'.$objp->remise_percent.' %</td>';
		      print "<td align=\"center\">".$objp->qty."</td></tr>\n";
		      $i++;
		    }
		}
	      else
		{
		  print $sql;
		}

	      print '</table>';
	    }	  
	}
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
  $id = $_GET["id"];
  if ($id > 0)
    {
      $contrat = New Contrat($db);
      if ( $contrat->fetch($id) > 0)
	{	  

	  $author = new User($db);
	  $author->id = $contrat->user_author_id;
	  $author->fetch();

	  $commercial_signature = new User($db);
	  $commercial_signature->id = $contrat->commercial_signature_id;
	  $commercial_signature->fetch();

	  $commercial_suivi = new User($db);
	  $commercial_suivi->id = $contrat->commercial_suivi_id;
	  $commercial_suivi->fetch();

	  $h = 0;
	  $head[$h][0] = DOL_URL_ROOT.'/contrat/fiche.php?id='.$contrat->id;
	  $head[$h][1] = $langs->trans("Contract");
	  $hselected = $h;
	  $h++;
	  


	  dolibarr_fiche_head($head, $hselected, $contrat->societe->nom);	  

	  /*
	   * Confirmation de la suppression de la contrat
	   *
	   */
	  if ($_GET["action"] == 'delete')
	    {
	      $html->form_confirm("fiche.php?id=$id","Supprimer la contrat","Etes-vous sûr de vouloir supprimer cette contrat ?","confirm_delete");
	    }
	  
	  /*
	   * Confirmation de la validation
	   *
	   */
	  if ($_GET["action"] == 'valid')
	    {
	      //$numfa = contrat_get_num($soc);
	      $html->form_confirm("fiche.php?id=$id","Valider la contrat","Etes-vous sûr de vouloir valider cette contrat ?","confirm_valid");
	    }
	  /*
	   * Confirmation de l'annulation
	   *
	   */
	  if ($_GET["action"] == 'annuler')
	    {
	      $html->form_confirm("fiche.php?id=$id",$langs->trans("Cancel"),"Etes-vous sûr de vouloir annuler cette contrat ?","confirm_cancel");
	    }

	  /*
	   *   Contrat
	   */
	  if ($contrat->brouillon == 1 && $user->rights->contrat->creer) 
	    {
	      print '<form action="fiche.php?id='.$id.'" method="post">';
	      print '<input type="hidden" name="action" value="setremise">';
	    }

	  print '<table class="border" cellspacing="0" cellpadding="2" width="100%">';
	  print "<tr><td>".$langs->trans("Customer")."</td>";
	  print '<td colspan="2">';
	  print '<b><a href="'.DOL_URL_ROOT.'/comm/fiche.php?socid='.$contrat->societe->id.'">'.$contrat->societe->nom.'</a></b></td>';
	  
	  print '<td width="50%" colspan="2">';
	  print $contrat->statuts[$contrat->statut];
	  print "</td></tr>";
	  
	  print '<tr><td>'.$langs->trans("Date").'</td>';
	  print "<td colspan=\"2\">".strftime("%A %d %B %Y",$contrat->date_contrat)."</td>\n";

	  print '<td>Projet</td><td>';
	  if ($contrat->projet_id > 0)
	    {
	      $projet = New Project($db);
	      $projet->fetch($contrat->projet_id);
	      print '<a href="'.DOL_URL_ROOT.'/projet/fiche.php?id='.$contrat->projet_id.'">'.$projet->title.'</a>';
	    }
	  else
	    {
	      print '<a href="fiche.php?id='.$id.'&amp;action=classer">Classer le contrat</a>';
	    }
	  print "&nbsp;</td></tr>";

	  print '<tr><td>'.$langs->trans("Commercial suivi").'</td><td colspan="2">'.$commercial_suivi->fullname.'</td>';
	  print '<td>'.$langs->trans("Commercial signature").'</td><td colspan="2">'.$commercial_signature->fullname.'</td></tr>';
	  print "</table>";
	  
	  if ($contrat->brouillon == 1 && $user->rights->contrat->creer) 
	    {
	      print '</form>';
	    }
	  
	  /*
	   * Lignes de contrats
	   *
	   */
	  echo '<br><table border="0" width="100%" cellspacing="0" cellpadding="3">';	  

	  $sql = "SELECT l.statut, l.label, l.fk_product, l.description, l.price_ht, l.qty, l.rowid, l.tva_tx, l.remise_percent, l.subprice";
	  $sql .= " FROM ".MAIN_DB_PREFIX."contratdet as l";
	  $sql .= "  WHERE l.fk_contrat = ".$id;
	  $sql .= " ORDER BY l.rowid";
	  
	  $result = $db->query($sql);

	  if ($result)
	    {
	      $num = $db->num_rows();
	      $i = 0; $total = 0;
	      
	      if ($num)
		{
		  print '<tr class="liste_titre">';
		  print '<td width="54%">'.$langs->trans("Description").'</td>';
		  print '<td width="8%" align="center">Tva</td>';
		  print '<td width="8%" align="center">Quantité</td>';
		  print '<td width="8%" align="right">Remise</td>';
		  print '<td width="12%" align="right">P.U.</td>';
		  print '<td width="10%">&nbsp;</td><td width="10%">&nbsp;</td>';
		  print "</tr>\n";
		}
	      $var=True;
	      while ($i < $num)
		{
		  $objp = $db->fetch_object();
		  print "<tr $bc[$var]>\n";
		  if ($objp->fk_product > 0)
		    {
		      print '<td>';
		      print '<a href="'.DOL_URL_ROOT.'/contrat/ligne.php?id='.$contrat->id.'&amp;ligne='.$objp->rowid.'">';;
		      print '<img src="./statut'.$objp->statut.'.png" border="0" alt="statut"></a>&nbsp;';
		      print '<a href="'.DOL_URL_ROOT.'/product/fiche.php?id='.$objp->fk_product.'">'.stripslashes(nl2br($objp->label)).'</a>';

		      if ($objp->description)
			{
			  
			  print '<br />'.stripslashes(nl2br($objp->description));
			}

		      print '</td>';
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
		  if ($contrat->statut == 0  && $objp->statut == 0 && $user->rights->contrat->creer) 
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
		      print '<td colspan="2">&nbsp;</td>';
		    }
		  print "</tr>\n";
		  
		  if ($_GET["action"] == 'editline' && $_GET["rowid"] == $objp->rowid)
		    {
		      print "<form action=\"fiche.php?id=$id\" method=\"post\">";
		      print '<input type="hidden" name="action" value="updateligne">';
		      print '<input type="hidden" name="elrowid" value="'.$_GET["rowid"].'">';
		      print "<tr $bc[$var]>";
		      print '<td colspan="2"><textarea name="eldesc" cols="60" rows="2">'.stripslashes($objp->description).'</textarea></TD>';
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
	 * Ajouter une ligne produit/service
	 *
	 */
	if ($user->rights->contrat->creer) 
	  {
	    $sql = "SELECT p.rowid,p.label,p.ref,p.price FROM ".MAIN_DB_PREFIX."product as p ";
	    $sql .= " WHERE p.envente = 1";
	    $sql .= " ORDER BY p.nbvente DESC LIMIT 20";

	    if ( $db->query($sql) )
	      {
		$opt = "<option value=\"0\" selected></option>";

		$num = $db->num_rows();	
		$i = 0;	
		while ($i < $num)
		  {
		    $objp = $db->fetch_object();
		    $opt .= "<option value=\"$objp->rowid\">[$objp->ref] $objp->label : $objp->price</option>\n";
		    $i++;
		  }
		
		$db->free();
	      }
	    else
	      {
		print $db->error();
	      }
	    
	    print '<form action="fiche.php?id='.$id.'" method="post">';
	    print '<input type="hidden" name="action" value="addligne">';

	    print "<tr class=\"liste_titre\">";
	    print '<td width="54%">'.$langs->trans("Description").'</td>';
	    print '<td width="8%" align="center">'.$langs->trans("VAT").'</td>';
	    print '<td width="8%" align="center">'.$langs->trans("Qty").'</td>';
	    print '<td width="8%" align="right">Remise</td>';
	    print '<td width="12%" align="right">P.U.</TD>';
	    print '<td>&nbsp;</td><td>&nbsp;</td>'."</tr>\n";


	    /*
	    print "<tr $bc[$var]>".'<td><textarea name="desc" cols="60" rows="1"></textarea></td>';
	    print '<td align="center">';
	    print $html->select_tva("tva_tx",$conf->defaulttx);
	    print '</td>';
	    print '<td align="center"><input type="text" name="qty" value="1" size="2"></td>';
	    print '<td align="right"><input type="text" name="remise_percent" size="4" value="0">&nbsp;%</td>';
	    print '<td align="right"><input type="text" name="pu" size="8"></td>';

	    print '<td align="center" colspan="3"><input type="submit" value="'.$langs->trans("Add").'"></td></tr>';
	    */

	    $var=!$var;
	    print "<tr $bc[$var]>";
	    print '<td colspan="2"><select name="p_idprod">'.$opt.'</select></td>';
	    print '<td align="center"><input type="text" size="2" name="pqty" value="1"></td>';
	    print '<td align="right"><input type="text" size="4" name="premise" value="0"> %</td>';
	    print '<td>&nbsp;</td>';
	    print '<td align="center" colspan="3"><input type="submit" value="'.$langs->trans("Add").'"></td></tr>';
	    print "</tr>\n";
	    print "<tr $bc[$var]>".'<td colspan="7"><textarea name="desc" cols="60" rows="1"></textarea></td></tr>';

	    print "</form>";
	  }
	print "</table><br>";
	/*
	 * Fin Ajout ligne
	 *
	 */

	print '</div>';

	if ($user->societe_id == 0 && $contrat->statut < 3)
	  {
	    print '<div class="tabsAction">';
	
	    if ($contrat->statut == 0 && $user->rights->contrat->supprimer)
	      {
		print '<a class="tabAction" href="fiche.php?id='.$id.'&amp;action=delete">'.$langs->trans("Delete").'</a>';
	      } 
	    
	    if ($contrat->statut > 0 && $contrat->statut < 3 && $user->rights->expedition->creer)
	      {
		print '<a class="tabAction" href="'.DOL_URL_ROOT.'/expedition/contrat.php?id='.$_GET["id"].'">Expédier</a>';
	      }
	  
	    
	    if ($contrat->statut == 0) 
	      {
		if ($user->rights->contrat->valider)
		  {
		    print '<a class="tabAction" href="fiche.php?id='.$id.'&amp;action=valid">'.$langs->trans("Valid").'</a>';
		  }
	      }
	    
	    if ($contrat->statut == 1)
	      {
		$nb_expedition = $contrat->nb_expedition();
		if ($user->rights->contrat->valider && $nb_expedition == 0)
		  {
		    print '<a class="tabAction" href="fiche.php?id='.$id.'&amp;action=annuler">'.$langs->trans("Cancel").'</a>';
		  }
	      }

	    print "</div>";
	  }
	print "<p>\n";


	/*
	 *
	 *
	 */
	if ($_GET["action"] == 'classer')
	  {	    
        $langs->load("project");
	    print '<p><form method="post" action="fiche.php?id='.$contrat->id.'">';
	    print '<input type="hidden" name="action" value="classin">';
	    print '<table class="border">';
	    print '<tr><td>'.$langs->trans("Project").'</td><td>';
	    
	    $proj = new Project($db);
	    $html->select_array("projetid",$proj->liste_array($contrat->soc_id));
	    
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
	/* Contrat non trouvée */
	print "Contrat inexistant ou accés refusé";
      }
  }  
}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
