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
if ($HTTP_POST_VARS["action"] == 'classin') 
{
  $commande = new Commande($db);
  $commande->fetch($_GET["id"]);
  $commande->classin($HTTP_POST_VARS["projetid"]);
}
/*
 *
 */	
if ($HTTP_POST_VARS["action"] == 'add') 
{
  $datecommande = mktime(12, 0 , 0, $HTTP_POST_VARS["remonth"], $HTTP_POST_VARS["reday"], $HTTP_POST_VARS["reyear"]); 

  $commande = new Commande($db);

  $commande->soc_id         = $HTTP_POST_VARS["soc_id"];
  $commande->date_commande  = $datecommande;      
  $commande->note           = $HTTP_POST_VARS["note"];
  $commande->source         = $HTTP_POST_VARS["source_id"];
  $commande->projetid       = $HTTP_POST_VARS["projetid"];
  $commande->remise_percent = $HTTP_POST_VARS["remise_percent"];
  
  $commande->add_product($HTTP_POST_VARS["idprod1"],$HTTP_POST_VARS["qty1"],$HTTP_POST_VARS["remise_percent1"]);
  $commande->add_product($HTTP_POST_VARS["idprod2"],$HTTP_POST_VARS["qty2"],$HTTP_POST_VARS["remise_percent2"]);
  $commande->add_product($HTTP_POST_VARS["idprod3"],$HTTP_POST_VARS["qty3"],$HTTP_POST_VARS["remise_percent3"]);
  $commande->add_product($HTTP_POST_VARS["idprod4"],$HTTP_POST_VARS["qty4"],$HTTP_POST_VARS["remise_percent4"]);
  
  $commande_id = $commande->create($user);
  
  $_GET["id"] = $commande->id;

  $action = '';  
}

/*
 *
 */


if ($HTTP_POST_VARS["action"] == 'setremise' && $user->rights->commande->creer) 
{
  $commande = new Commande($db);
  $commande->fetch($id);

  $commande->set_remise($user, $HTTP_POST_VARS["remise"]);
} 

if ($HTTP_POST_VARS["action"] == 'addligne' && $user->rights->commande->creer) 
{
  $commande = new Commande($db);
  $commande->fetch($_GET["id"]);

  if ($HTTP_POST_VARS["p_idprod"] > 0)
    {
      $result = $commande->addline("DESC",
				   $HTTP_POST_VARS["pu"],
				   $HTTP_POST_VARS["pqty"],
				   $HTTP_POST_VARS["tva_tx"],
				   $HTTP_POST_VARS["p_idprod"],
				   $HTTP_POST_VARS["premise"]);
    }
  else
    {
      $result = $commande->addline($HTTP_POST_VARS["desc"],
				   $HTTP_POST_VARS["pu"],
				   $HTTP_POST_VARS["qty"],
				   $HTTP_POST_VARS["tva_tx"],
				   0,
				   $HTTP_POST_VARS["remise_percent"]);
    }
}

if ($HTTP_POST_VARS["action"] == 'updateligne' && $user->rights->commande->creer) 
{
  $commande = new Commande($db,"",$_GET["id"]);
  if ($commande->fetch($_GET["id"]) )
    {
      $result = $commande->update_line($HTTP_POST_VARS["elrowid"],
				       $HTTP_POST_VARS["eldesc"],
				       $HTTP_POST_VARS["elprice"],
				       $HTTP_POST_VARS["elqty"],
				       $HTTP_POST_VARS["elremise_percent"]);
    }
  else
    {
      print "Erreur";
    }
}

if ($action == 'deleteline' && $user->rights->commande->creer) 
{
  $commande = new Commande($db);
  $commande->fetch($_GET["id"]);
  $result = $commande->delete_line($_GET["lineid"]);
}

if ($HTTP_POST_VARS["action"] == 'confirm_valid' && $HTTP_POST_VARS["confirm"] == yes && $user->rights->commande->valider)
{
  $commande = new Commande($db);
  $commande->fetch($_GET["id"]);
  $soc = new Societe($db);
  $soc->fetch($commande->soc_id);
  $result = $commande->valid($user);
}

if ($HTTP_POST_VARS["action"] == 'confirm_cancel' && $HTTP_POST_VARS["confirm"] == yes && $user->rights->commande->valider)
{
  $commande = new Commande($db);
  $commande->fetch($_GET["id"]);
  $result = $commande->cancel($user);
}

if ($HTTP_POST_VARS["action"] == 'confirm_delete' && $HTTP_POST_VARS["confirm"] == yes)
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

llxHeader('','Fiche commande','ch-commande.html');



$html = new Form($db);

/*********************************************************************
 *
 * Mode creation
 *
 *
 *
 ************************************************************************/
if ($action == 'create') 
{
  print_titre("Créer une commande");

  $new_commande = new Commande($db);

  if ($propalid)
    {
      $sql = "SELECT s.nom, s.prefix_comm, s.idp, p.price, p.remise, p.remise_percent, p.tva, p.total, p.ref, ".$db->pdate("p.datep")." as dp, c.id as statut, c.label as lst";
      $sql .= " FROM llx_societe as s, llx_propal as p, c_propalst as c";
      $sql .= " WHERE p.fk_soc = s.idp AND p.fk_statut = c.id";      
      $sql .= " AND p.rowid = $propalid";
    }
  else
    {
      $sql = "SELECT s.nom, s.prefix_comm, s.idp ";
      $sql .= "FROM llx_societe as s ";
      $sql .= "WHERE s.idp = $socidp";      
    }

  if ( $db->query($sql) ) 
    {
      $num = $db->num_rows();
      if ($num)
	{
	  $obj = $db->fetch_object(0);

	  $soc = new Societe($db);
	  $soc->fetch($obj->idp);
       
	  print '<form action="'.$PHP_SELF.'" method="post">';
	  print '<input type="hidden" name="action" value="add">';
	  print '<input type="hidden" name="soc_id" value="'.$soc->id.'">' ."\n";
	  print '<input type="hidden" name="remise_percent" value="0">';

	  print '<table class="border" cellspacing="0" cellpadding="3" width="100%">';
	  
	  print '<tr><td>Client :</td><td>'.$obj->nom.'</td>';
	  print '<td class="border">Commentaire</td></tr>';

	  print "<tr><td>Auteur :</td><td>".$user->fullname."</td>";
	  
	  print '<td rowspan="5" valign="top">';
	  print '<textarea name="note" wrap="soft" cols="60" rows="8"></textarea></td></tr>';	
	  
	  print "<tr><td>Date :</td><td>";
	
	  print_date_select(time());

	  print "</td></tr>";
	  print "<tr><td>Numéro :</td><td>Provisoire</td></tr>";
	  print '<input name="facnumber" type="hidden" value="provisoire">';

	  print "<tr><td>Source :</td><td>";
	  $html->select_array("source_id",$new_commande->sources,2);
	  print "</td></tr>";

	  print "<tr><td>Projet :</td><td>";
	  $proj = new Project($db);
	  $html->select_array("projetid",$proj->liste_array($socidp),0,1);
	  print "</td></tr>";
	  
	  if ($propalid > 0)
	    {
	      $amount = ($obj->price);
	      print '<input type="hidden" name="amount"   value="'.$amount.'">'."\n";
	      print '<input type="hidden" name="total"    value="'.$obj->total.'">'."\n";
	      print '<input type="hidden" name="remise"   value="'.$obj->remise.'">'."\n";
	      print '<input type="hidden" name="remise_percent"   value="'.$obj->remise_percent.'">'."\n";
	      print '<input type="hidden" name="tva"      value="'.$obj->tva.'">'."\n";
	      print '<input type="hidden" name="propalid" value="'.$propalid.'">';
	      
	      print '<tr><td>Proposition</td><td colspan="2">'.$obj->ref.'</td></tr>';
	      print '<tr><td>Montant HT</td><td colspan="2">'.price($amount).'</td></tr>';
	      print '<tr><td>TVA</td><td colspan="2">'.price($obj->tva)."</td></tr>";
	      print '<tr><td>Total TTC</td><td colspan="2">'.price($obj->total)."</td></tr>";	  
	    }	  
	  else
	    {
	      print '<tr><td colspan="3">Services/Produits</td></tr>';
	      print '<tr><td colspan="3">';
	      /*
	       *
	       * Liste des elements
	       *
	       */
	      $sql = "SELECT p.rowid,p.label,p.ref,p.price FROM llx_product as p ";
	      $sql .= " WHERE envente = 1";
	      $sql .= " ORDER BY p.nbvente DESC LIMIT 20";
	      if ( $db->query($sql) )
		{
		  $opt = "<option value=\"0\" SELECTED></option>";
		  if ($result)
		    {
		      $num = $db->num_rows();	$i = 0;	
		      while ($i < $num)
			{
			  $objp = $db->fetch_object( $i);
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
	    }

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
	      
	      print '<TABLE border="0" width="100%" cellspacing="0" cellpadding="3">';
	      print '<tr class="liste_titre"><td>Réf</td><td>Produit</td>';
	      print '<td align="right">Prix</td><td align="center">Remise</td><td align="center">Qté.</td></tr>';
	      
	      $sql = "SELECT pt.rowid, p.label as product, p.ref, pt.price, pt.qty, p.rowid as prodid, pt.remise_percent";
	      $sql .= " FROM llx_propaldet as pt, llx_product as p WHERE pt.fk_product = p.rowid AND pt.fk_propal = $propalid";
	      $sql .= " ORDER BY pt.rowid ASC";
	      $result = $db->query($sql);
	      if ($result) 
		{
		  $num = $db->num_rows();
		  $i = 0;	
		  $var=True;	
		  while ($i < $num) 
		    {
		      $objp = $db->fetch_object($i);
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
	      $sql .= " FROM llx_propaldet as pt  WHERE  pt.fk_propal = $propalid AND pt.fk_product = 0";
	      $sql .= " ORDER BY pt.rowid ASC";
	      if ($db->query($sql)) 
		{
		  $num = $db->num_rows();
		  $i = 0;	
		  while ($i < $num) 
		    {
		      $objp = $db->fetch_object($i);
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
      $commande = New Commande($db);
      if ( $commande->fetch($id) > 0)
	{	  
	  $soc = new Societe($db);
	  $soc->fetch($commande->soc_id);
	  $author = new User($db);
	  $author->id = $commande->user_author_id;
	  $author->fetch();
	  
	  print_titre("Commande : ".$commande->ref);

	  /*
	   * Confirmation de la suppression de la commande
	   *
	   */
	  if ($_GET["action"] == 'delete')
	    {
	      $html->form_confirm("$PHP_SELF?id=$id","Supprimer la commande","Etes-vous sûr de vouloir supprimer cette commande ?","confirm_delete");
	    }
	  
	  /*
	   * Confirmation de la validation
	   *
	   */
	  if ($_GET["action"] == 'valid')
	    {
	      //$numfa = commande_get_num($soc);
	      $html->form_confirm("$PHP_SELF?id=$id","Valider la commande","Etes-vous sûr de vouloir valider cette commande ?","confirm_valid");
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
	  print "<tr><td>Client</td>";
	  print '<td colspan="2">';
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

	  print "<tr><td>Auteur</td><td colspan=\"2\">$author->fullname</td>";
	
	  print '<td>Projet : ';
	  if ($commande->projet_id > 0)
	    {
	      $projet = New Project($db);
	      $projet->fetch($commande->projet_id);
	      print '<a href="'.DOL_URL_ROOT.'/projet/fiche.php?id='.$commande->projet_id.'">'.$projet->title.'</a>';
	    }
	  else
	    {
	      print '<a href="fiche.php?id='.$id.'&amp;action=classer">Classer la commande</a>';
	    }
	  print "&nbsp;</td></tr>";
  
	  print '<tr><td>Montant</td>';
	  print '<td align="right"><b>'.price($commande->total_ht).'</b></td>';
	  print '<td>'.MAIN_MONNAIE.' HT</td>';
	  
	  print '<td>Note</td></tr>';

	  print '<tr><td>Remise globale</td><td align="right">';

	  if ($commande->brouillon == 1 && $user->rights->commande->creer) 
	    {
	      print '<input type="text" name="remise" size="3" value="'.$commande->remise_percent.'">%';
	      print '</td><td><input type="submit" value="Appliquer">';
	    }
	  else
	    {
	      print $commande->remise_percent.' %</td><td>&nbsp;';
	    }
	  print '</td></tr>';

	  print '<tr><td>TVA</td><td align="right">'.price($commande->total_tva).'</td>';
	  print '<td>'.MAIN_MONNAIE.'</td></tr>';
	  print '<tr><td>Total</td><td align="right">'.price($commande->total_ttc).'</td>';
	  print '<td>'.MAIN_MONNAIE.' TTC</td></tr>';
	  if ($commande->note)
	    {
	      print '<tr><td colspan="5">Note : '.nl2br($commande->note)."</td></tr>";
	    }
	  
	  print "</table>";
	  
	  if ($commande->brouillon == 1 && $user->rights->commande->creer) 
	    {
	      print '</form>';
	    }
	  
	  /*
	   * Lignes de commandes
	   *
	   */
	  echo '<br><table border="0" width="100%" cellspacing="0" cellpadding="3">';	  

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
		      print '<td align="right"><a href="'.$PHPSELF.'?id='.$id.'&amp;action=editline&amp;rowid='.$objp->rowid.'">';
		      print img_edit();
		      print '</a></td>';
		      print '<td align="right"><a href="'.$PHPSELF.'?id='.$id.'&amp;action=deleteline&amp;lineid='.$objp->rowid.'">';
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
		      print "<form action=\"$PHP_SELF?id=$id\" method=\"post\">";
		      print '<input type="hidden" name="action" value="updateligne">';
		      print '<input type="hidden" name="elrowid" value="'.$_GET["rowid"].'">';
		      print "<tr $bc[$var]>";
		      print '<td colspan="2"><textarea name="eldesc" cols="60" rows="2">'.stripslashes($objp->description).'</textarea></TD>';
		      print '<td align="center"><input size="4" type="text" name="elqty" value="'.$objp->qty.'"></TD>';
		      print '<td align="right"><input size="3" type="text" name="elremise_percent" value="'.$objp->remise_percent.'">&nbsp;%</td>';
		      print '<td align="right"><input size="8" type="text" name="elprice" value="'.price($objp->subprice).'"></td>';
		      print '<td align="right" colspan="2"><input type="submit" value="Enregistrer"></td>';
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
	      $sql = "SELECT p.rowid,p.label,p.ref,p.price FROM llx_product as p ";
	      $sql .= " WHERE envente = 1";
	      $sql .= " ORDER BY p.nbvente DESC LIMIT 20";
	      if ( $db->query($sql) )
		{
		  $opt = "<option value=\"0\" SELECTED></option>";
		  if ($result)
		    {
		      $num = $db->num_rows();	$i = 0;	
		      while ($i < $num)
			{
			  $objp = $db->fetch_object( $i);
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

	    print "<form action=\"$PHP_SELF?id=$id\" method=\"post\">";
	    print "<tr class=\"liste_titre\">";
	    print '<td width="54%">Description</td>';
	    print '<td width="8%" align="center">Tva</td>';
	    print '<td width="8%" align="center">Quantité</td>';
	    print '<td width="8%" align="right">Remise</td>';
	    print '<td width="12%" align="right">P.U.</TD>';
	    print '<td>&nbsp;</td><td>&nbsp;</td>'."</tr>\n";
	    print '<input type="hidden" name="action" value="addligne">';
	    print "<tr $bc[$var]>".'<td><textarea name="desc" cols="60" rows="1"></textarea></td>';
	    print '<td align="center">';
	    print $html->select_tva("tva_tx");
	    print '</td>';
	    print '<td align="center"><input type="text" name="qty" value="1" size="2"></td>';
	    print '<td align="right"><input type="text" name="remise_percent" size="4" value="0">&nbsp;%</td>';
	    print '<td align="right"><input type="text" name="pu" size="8"></td>';

	    print '<td align="center" colspan="3"><input type="submit" value="Ajouter"></td></tr>';

	    $var=!$var;
	    print "<tr $bc[$var]><td colspan=\"2\"><select name=\"p_idprod\">$opt</select></td>";
	    print '<td align="center"><input type="text" size="2" name="pqty" value="1"></td>';
	    print '<td align="right"><input type="text" size="4" name="premise" value="0"> %</td>';
	    print '<td>&nbsp;</td>';
	    print '<td align="center" colspan="3"><input type="submit" value="Ajouter"></td></tr>';
	    print "</tr>\n";

	    print "</form>";
	  }
	print "</table>";
	/*
	 * Fin Ajout ligne
	 *
	 */
	if ($user->societe_id == 0 && $commande->statut < 3)
	  {
	    print '<p><table id="actions" width="100%"><tr>';
	
	    if ($commande->statut == 0 && $user->rights->commande->supprimer)
	      {
		print "<td align=\"center\" width=\"20%\"><a href=\"$PHP_SELF?id=$id&amp;action=delete\">Supprimer</a></td>";
	      } 
	    elseif ($commande->statut == 1 && abs($resteapayer) > 0 && $user->rights->commande->envoyer) 
	      {
		print "<td align=\"center\" width=\"20%\"><a href=\"$PHP_SELF?id=$id&amp;action=presend\">Envoyer</a></td>";
	      }
	    else
	      {
		print '<td align="center" width="20%">-</td>';
	      } 
	    
	    if ($commande->statut > 0 && $commande->statut < 3 && $user->rights->expedition->creer)
	      {
		print '<td align="center" width="20%"><a href="'.DOL_URL_ROOT.'/expedition/commande.php?id='.$_GET["id"].'">Expédier</a></td>';
	      }
	    else
	      {
	    print '<td align="center" width="20%">-</td>';
	      }

	    
	    print '<td align="center" width="20%">-</td>';
	    print '<td align="center" width="20%">-</td>';
	    
	    if ($commande->statut == 0) 
	      {
		if ($user->rights->commande->valider)
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
		$nb_expedition = $commande->nb_expedition();
		if ($user->rights->commande->valider && $nb_expedition == 0)
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


	print '<table width="100%" cellspacing="2"><tr><td width="50%" valign="top">';
	/*
	 * Liste des expéditions
	 */
	$sql = "SELECT e.rowid,e.ref,".$db->pdate("e.date_expedition")." as de";
	$sql .= " FROM llx_expedition as e";
	$sql .= " WHERE e.fk_commande = ". $commande->id;
	    
	$result = $db->query($sql);
	if ($result)
	  {
	    $num = $db->num_rows();
	    if ($num)
	      {
		print_titre("Expéditions");
		$i = 0; $total = 0;
		print '<table border="1" cellspacing="0" cellpadding="4" width="100%">';
		print "<tr $bc[$var]><td>Expédition</td><td>Date</td></tr>\n";
		
		$var=True;
		while ($i < $num)
		  {
		    $objp = $db->fetch_object( $i);
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
	$sql .= " FROM llx_facture as f, llx_co_fa as cf";
	$sql .= " WHERE f.rowid = cf.fk_facture AND cf.fk_commande = ". $commande->id;
	    
	$result = $db->query($sql);
	if ($result)
	  {
	    $num = $db->num_rows();
	    if ($num)
	      {
		print_titre("Factures");
		$i = 0; $total = 0;
		print '<table border="1" cellspacing="0" cellpadding="4" width="100%">';
		print "<tr $bc[$var]><td>Facture</td><td>Date</td></tr>\n";
		
		$var=True;
		while ($i < $num)
		  {
		    $objp = $db->fetch_object( $i);
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

	    
	    /*
	     *
	     *
	     */

	  }
	/*
	 *
	 *
	 */
	if ($action == 'classer')
	  {	    
	    print "<p><form method=\"post\" action=\"$PHP_SELF?id=$id\">\n";
	    print '<input type="hidden" name="action" value="classin">';
	    print '<table cellspacing="0" class="border" cellpadding="3">';
	    print '<tr><td>Projet</td><td>';
	    
	    $proj = new Project($db);
	    $html->select_array("projetid",$proj->liste_array($commande->soc_id));
	    
	    print "</td></tr>";
	    print '<tr><td colspan="2" align="center"><input type="submit" value="Envoyer"></td></tr></table></form></p>';
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
}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
