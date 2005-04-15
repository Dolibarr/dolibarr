<?php
/* Copyright (C) 2003-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
       \file       htdocs/commande/fiche.php
       \ingroup    commande
       \brief      Fiche commande
       \version    $Revision$
*/

require("./pre.inc.php");

$langs->load("sendings");
$langs->load("companies");
$langs->load("bills");

$user->getrights('commande');
$user->getrights('expedition');

if (!$user->rights->commande->lire) accessforbidden();

require_once DOL_DOCUMENT_ROOT."/project.class.php";
require_once DOL_DOCUMENT_ROOT."/propal.class.php";
require_once DOL_DOCUMENT_ROOT."/commande/commande.class.php";

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
if ($_POST["action"] == 'classin' && $user->rights->commande->creer)
{
  $commande = new Commande($db);
  $commande->fetch($_GET["id"]);
  $commande->classin($_POST["projetid"]);
}
/*
 *
 */	
if ($_POST["action"] == 'add' && $user->rights->commande->creer) 
{
  $datecommande = mktime(12, 0 , 0, $_POST["remonth"], $_POST["reday"], $_POST["reyear"]); 

  $commande = new Commande($db);

  $commande->soc_id         = $_POST["soc_id"];
  $commande->date_commande  = $datecommande;      
  $commande->note           = $_POST["note"];
  $commande->source         = $_POST["source_id"];
  $commande->projetid       = $_POST["projetid"];
  $commande->remise_percent = $_POST["remise_percent"];
  
  $commande->add_product($_POST["idprod1"],$_POST["qty1"],$_POST["remise_percent1"]);
  $commande->add_product($_POST["idprod2"],$_POST["qty2"],$_POST["remise_percent2"]);
  $commande->add_product($_POST["idprod3"],$_POST["qty3"],$_POST["remise_percent3"]);
  $commande->add_product($_POST["idprod4"],$_POST["qty4"],$_POST["remise_percent4"]);
  
  $commande_id = $commande->create($user);
  
  $_GET["id"] = $commande->id;

  $action = '';  
}

/*
 *
 */


if ($_POST["action"] == 'setremise' && $user->rights->commande->creer) 
{
  $commande = new Commande($db);
  $commande->fetch($id);

  $commande->set_remise($user, $_POST["remise"]);
} 

if ($_POST["action"] == 'addligne' && $user->rights->commande->creer) 
{
  $commande = new Commande($db);
  $commande->fetch($_GET["id"]);

  if ($_POST["p_idprod"] > 0)
    {
      $result = $commande->addline("DESC",
				   $_POST["pu"],
				   $_POST["pqty"],
				   $_POST["tva_tx"],
				   $_POST["p_idprod"],
				   $_POST["premise"]);
    }
  else
    {
      $result = $commande->addline($_POST["desc"],
				   $_POST["pu"],
				   $_POST["qty"],
				   $_POST["tva_tx"],
				   0,
				   $_POST["remise_percent"]);
    }
}

if ($_POST["action"] == 'updateligne' && $user->rights->commande->creer) 
{
  $commande = new Commande($db,"",$_GET["id"]);
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
  $commande = new Commande($db);
  $commande->fetch($_GET["id"]);
  $result = $commande->delete_line($_GET["lineid"]);
  Header("Location: fiche.php?id=".$_GET["id"]);
}

if ($_GET["action"] == 'facturee') 
{
  $commande = new Commande($db);
  $commande->fetch($_GET["id"]);
  $commande->classer_facturee();
}

if ($_POST["action"] == 'confirm_valid' && $_POST["confirm"] == yes && $user->rights->commande->valider)
{
  $commande = new Commande($db);
  $commande->fetch($_GET["id"]);
  $soc = new Societe($db);
  $soc->fetch($commande->soc_id);
  $result = $commande->valid($user);
}

if ($_POST["action"] == 'confirm_cancel' && $_POST["confirm"] == yes && $user->rights->commande->valider)
{
  $commande = new Commande($db);
  $commande->fetch($_GET["id"]);
  $result = $commande->cancel($user);
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

if ($_GET["action"] == 'pdf')
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
 ************************************************************************/
if ($_GET["action"] == 'create' && $user->rights->commande->creer) 
{
  print_titre($langs->trans("CreateOrder"));

  $new_commande = new Commande($db);

  if ($propalid)
    {
      $sql = "SELECT s.nom, s.prefix_comm, s.idp, p.price, p.remise, p.remise_percent, p.tva, p.total, p.ref, ".$db->pdate("p.datep")." as dp, c.id as statut, c.label as lst";
      $sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."propal as p, ".MAIN_DB_PREFIX."c_propalst as c";
      $sql .= " WHERE p.fk_soc = s.idp AND p.fk_statut = c.id";      
      $sql .= " AND p.rowid = $propalid";
    }
  else
    {
      $sql = "SELECT s.nom, s.prefix_comm, s.idp ";
      $sql .= "FROM ".MAIN_DB_PREFIX."societe as s ";
      $sql .= "WHERE s.idp = ".$_GET["socidp"];      
    }

  $resql = $db->query($sql);

  if ( $resql ) 
    {
      $num = $db->num_rows($resql);
      if ($num)
	{
	  $obj = $db->fetch_object($resql);

	  $soc = new Societe($db);
	  $soc->fetch($obj->idp);
       
	  print '<form action="fiche.php" method="post">';
	  print '<input type="hidden" name="action" value="add">';
	  print '<input type="hidden" name="soc_id" value="'.$soc->id.'">' ."\n";
	  print '<input type="hidden" name="remise_percent" value="0">';
	  print '<input name="facnumber" type="hidden" value="provisoire">';

	  print '<table class="border" width="100%">';
	  
	  print '<tr><td>'.$langs->trans("Customer").' :</td><td>'.$soc->nom_url.'</td>';
	  print '<td>'.$langs->trans("Comments").' :</td></tr>';

	  print '<tr><td>'.$langs->trans("Author").' :</td><td>'.$user->fullname.'</td>';
	  
	  print '<td rowspan="5" valign="top">';
	  print '<textarea name="note" wrap="soft" cols="60" rows="6"></textarea></td></tr>';	
	  
	  print '<tr><td>'.$langs->trans("Date").' :</td><td>';
	  $html->select_date();
	  print "</td></tr>";

	  print '<tr><td>'.$langs->trans("Ref").' :</td><td>Provisoire</td></tr>';


	  print '<tr><td>'.$langs->trans("Source").' :</td><td>';
	  $html->select_array("source_id",$new_commande->sources,2);
	  print "</td></tr>";

	  print '<tr><td>'.$langs->trans("Project").' :</td><td>';
	  $proj = new Project($db);
	  $html->select_array("projetid",$proj->liste_array($soc->id),0,1);
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
	      
	      print '<tr><td>'.$langs->trans("Ref").'</td><td colspan="2">'.$obj->ref.'</td></tr>';
	      print '<tr><td>'.$langs->trans("TotalTTC").'</td><td colspan="2">'.price($amount).'</td></tr>';
	      print '<tr><td>'.$langs->trans("VAT").'</td><td colspan="2">'.price($obj->tva)."</td></tr>";
	      print '<tr><td>'.$langs->trans("TotalTTC").'</td><td colspan="2">'.price($obj->total)."</td></tr>";
	    }	  
	  else
	    {
	      print '<tr><td colspan="3">'.$langs->trans("Services").'/'.$langs->trans("Products").'</td></tr>';
	      print '<tr><td colspan="3">';
	      /*
	       *
	       * Liste des elements
	       *
	       */
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
		  dolibarr_print_error($db);
		}
	      	      
	      print '<table class="noborder">';
	      print '<tr><td>20 Produits les plus vendus</td><td>'.$langs->trans("Qty").'</td><td>'.$langs->trans("Discount").'</td></tr>';
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
	  print '<tr><td colspan="3" align="center"><input type="submit" value="'.$langs->trans("Create").'"></td></tr>';
	  print "</form>\n";
	  print "</table>\n";

	  if ($propalid)
	    {
	      /*
	       * Produits
	       */
	      print_titre($langs->trans("Products"));
	      
	      print '<table class="noborder" width="100%">';
	      print '<tr class="liste_titre"><td>'.$langs->trans("Ref").'</td><td>'.$langs->trans("Product").'</td>';
	      print '<td align="right">'.$langs->trans("Price").'</td><td align="center">'.$langs->trans("Discount").'</td><td align="center">'.$langs->trans("Qty").'</td></tr>';
	      
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
		      print '<td>'.img_object($langs->trans("ShowProduct"),"product").' '.$objp->product.'</td>';
		      print "<td align=\"right\">".price($objp->price)."</td>";
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
		      print '<td>'.img_object($langs->trans("ShowProduct"),"product").' '.$objp->product.'</td>';
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
      dolibarr_print_error($db);
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
      $commande = new Commande($db);
      if ( $commande->fetch($_GET["id"]) > 0)
	{	  
	  $soc = new Societe($db);
	  $soc->fetch($commande->soc_id);
	  $author = new User($db);
	  $author->id = $commande->user_author_id;
	  $author->fetch();

	  $h=0;

	  if ($conf->commande->enabled && $user->rights->commande->lire)
	    {
    	  $head[$h][0] = DOL_URL_ROOT.'/commande/fiche.php?id='.$commande->id;
    	  $head[$h][1] = $langs->trans("OrderCard");
    	  $hselected = $h;
    	  $h++;
        }
        	 
	  if ($conf->expedition->enabled && $user->rights->expedition->lire)
	    {
	      $head[$h][0] = DOL_URL_ROOT.'/expedition/commande.php?id='.$commande->id;
	      $head[$h][1] = $langs->trans("SendingCard");
	      $h++;
	    }

	  if ($conf->compta->enabled)
	    {
    	  $head[$h][0] = DOL_URL_ROOT.'/compta/commande/fiche.php?id='.$commande->id;
    	  $head[$h][1] = $langs->trans("ComptaCard");
    	  $h++;
        }
        
	  dolibarr_fiche_head($head, $hselected, $soc->nom." / ".$langs->trans("Order")." : $commande->ref");

	  /*
	   * Confirmation de la suppression de la commande
	   *
	   */
	  if ($_GET["action"] == 'delete')
	    {
	      $html->form_confirm("fiche.php?id=$id",$langs->trans("DeleteOrder"),$langs->trans("ConfirmDeleteOrder"),"confirm_delete");
	      print "<br />\n";
	    }
	  
	  /*
	   * Confirmation de la validation
	   *
	   */
	  if ($_GET["action"] == 'valid')
	    {
	      //$numfa = commande_get_num($soc);
	      $html->form_confirm("fiche.php?id=$id",$langs->trans("ValidateOrder"),$langs->trans("ConfirmValidateOrder"),"confirm_valid");
	      print "<br />\n";
	    }
	  /*
	   * Confirmation de l'annulation
	   *
	   */
	  if ($_GET["action"] == 'annuler')
	    {
	      $html->form_confirm("fiche.php?id=$id",$langs->trans("Cancel"),$langs->trans("ConfirmCancel"),"confirm_cancel");
	      print "<br />\n";
	    }

	  /*
	   *   Commande
	   */
	  if ($commande->brouillon == 1 && $user->rights->commande->creer) 
	    {
	      print '<form action="fiche.php?id='.$id.'" method="post">';
	      print '<input type="hidden" name="action" value="setremise">';
	    }

	  print '<table class="border" width="100%">';
	  print '<tr><td width="20%">'.$langs->trans("Order")."</td>";
	  print '<td width="15%">'.$commande->ref.'</td>';
	  print '<td width="15%" align="center">'.$commande->statuts[$commande->statut].'</td>';
	  print '<td width="50%">';
	  
	  if ($conf->projet->enabled) 
	    {
	      $langs->load("projects");
	      if ($commande->projet_id > 0)
		{
		  print $langs->trans("Project").' : ';
		  $projet = New Project($db);
		  $projet->fetch($commande->projet_id);
		  print '<a href="'.DOL_URL_ROOT.'/projet/fiche.php?id='.$commande->projet_id.'">'.$projet->title.'</a>';
		}
	      else
		{
		  print $langs->trans("Project").' : ';
		  print '<a href="fiche.php?id='.$id.'&amp;action=classer">Classer la commande</a>';
		}
	    }
	  print '&nbsp;</td></tr>';

	  print "<tr><td>".$langs->trans("Customer")."</td>";
	  print '<td colspan="2">';
	  print '<b><a href="'.DOL_URL_ROOT.'/comm/fiche.php?socid='.$soc->id.'">'.$soc->nom.'</a></b></td>';
	  
	  print '<td width="50%">'.$langs->trans("Source").' : ' . $commande->sources[$commande->source] ;
	  if ($commande->source == 0)
	    {
	      /* Propale */
	      $propal = new Propal($db);
	      $propal->fetch($commande->propale_id);
	      print ' -> <a href="'.DOL_URL_ROOT.'/comm/propal.php?propalid='.$propal->id.'">'.$propal->ref.'</a>';
	    }
	  print "</td></tr>";
	  
	  print '<tr><td>'.$langs->trans("Date").'</td>';
	  print "<td colspan=\"2\">".dolibarr_print_date($commande->date,"%A %d %B %Y")."</td>\n";

	  print '<td width="50%">';
	  print $langs->trans("Author").' : '.$author->fullname.'</td></tr>';
  
	  // Ligne de 3 colonnes
	  print '<tr><td>'.$langs->trans("AmountHT").'</td>';
	  print '<td align="right"><b>'.price($commande->total_ht).'</b></td>';
	  print '<td>'.$conf->monnaie.'</td>';
	  print '<td rowspan="4" valign="top">'.$langs->trans("Note").' :</td></tr>';

	  print '<tr><td>'.$langs->trans("GlobalDiscount").'</td><td align="right">';

	  if ($commande->brouillon == 1 && $user->rights->commande->creer) 
	    {
	      print '<input type="text" name="remise" size="3" value="'.$commande->remise_percent.'">%';
	      print '</td><td><input type="submit" value="'.$langs->trans("Save").'">';
	    }
	  else
	    {
	      print $commande->remise_percent.' %</td><td>&nbsp;';
	    }
	  print '</td></tr>';

	  print '<tr><td>'.$langs->trans("VAT").'</td><td align="right">'.price($commande->total_tva).'</td>';
	  print '<td>'.$conf->monnaie.'</td></tr>';
	  print '<tr><td>'.$langs->trans("TotalTTC").'</td><td align="right">'.price($commande->total_ttc).'</td>';
	  print '<td>'.$conf->monnaie.'</td></tr>';
	  if ($commande->note)
	    {
	      print '<tr><td colspan="5">'.$langs->trans("Note").' : '.nl2br($commande->note)."</td></tr>";
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
	  echo '<br><table class="noborder" width="100%">';	  

	  $sql = "SELECT l.fk_product, l.description, l.price, l.qty, l.rowid, l.tva_tx, l.remise_percent, l.subprice";
	  $sql .= " FROM ".MAIN_DB_PREFIX."commandedet as l";
	  $sql .= " WHERE l.fk_commande = ".$commande->id;
	  $sql .= " ORDER BY l.rowid";
	  
	  $result = $db->query($sql);
	  if ($result)
	    {
	      $num = $db->num_rows($result);
	      $i = 0; $total = 0;
	      
	      if ($num)
		{
		  print '<tr class="liste_titre">';
		  print '<td width="54%">'.$langs->trans("Description").'</td>';
		  print '<td width="8%" align="center">'.$langs->trans("VAT").'</td>';
		  print '<td width="8%" align="center">'.$langs->trans("Qty").'</td>';
		  print '<td width="8%" align="right">'.$langs->trans("Discount").'</td>';
		  print '<td width="12%" align="right">'.$langs->trans("PriceU").'</td>';
		  print '<td width="10%">&nbsp;</td><td width="10%">&nbsp;</td>';
		  print "</tr>\n";
		}
	      $var=True;
	      while ($i < $num)
		{
		  $objp = $db->fetch_object($result);
		  print "<tr $bc[$var]>";
		  if ($objp->fk_product > 0)
		    {
		      print '<td>';
		      print '<a href="'.DOL_URL_ROOT.'/product/fiche.php?id='.$objp->fk_product.'">'.img_object($langs->trans("ShowProduct"),"product").' '.stripslashes(nl2br($objp->description)).'</a></td>';
		    }
		  else
		    {
		      print "<td>".stripslashes(nl2br($objp->description))."</td>\n";
		    }
		  print '<td align="center">'.$objp->tva_tx.' %</td>';
		  print '<td align="center">'.$objp->qty.'</td>';
		  if ($objp->remise_percent > 0)
		    {
		      print '<td align="right">'.$objp->remise_percent."%</td>\n";
		    }
		  else
		    {
		      print '<td>&nbsp;</td>';
		    }
		  print '<td align="right">'.price($objp->subprice)."</td>\n";
		  if ($commande->statut == 0  && $user->rights->commande->creer && $_GET["action"] == '') 
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
		      print '<td colspan="2"><textarea name="eldesc" cols="60" rows="2">'.stripslashes($objp->description).'</textarea></td>';
		      print '<td align="center"><input size="4" type="text" name="elqty" value="'.$objp->qty.'"></td>';
		      print '<td align="right"><input size="3" type="text" name="elremise_percent" value="'.$objp->remise_percent.'">%</td>';
		      print '<td align="right"><input size="7" type="text" name="elprice" value="'.price($objp->subprice).'"></td>';
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
	    dolibarr_print_error($db);
	  }
	
	/*
	 * Ajouter une ligne
	 *
	 */
	if ($commande->statut == 0 && $user->rights->commande->creer && $_GET["action"] == '') 
	  {
	      $sql = "SELECT p.rowid,p.label,p.ref,p.price FROM ".MAIN_DB_PREFIX."product as p ";
	      $sql .= " WHERE envente = 1";
	      $sql .= " ORDER BY p.nbvente DESC";
	      $sql .= " LIMIT 20";
	      if ( $db->query($sql) )
		{
		  $opt = "<option value=\"0\" selected></option>";
		  if ($result)
		    {
		      $num = $db->num_rows();
		      $i = 0;	
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
		  dolibarr_print_error($db);
		}

	    print "<form action=\"fiche.php?id=$id\" method=\"post\">";
	    print "<tr class=\"liste_titre\">";
	    print '<td width="54%">'.$langs->trans("Description").'</td>';
	    print '<td width="8%" align="center">'.$langs->trans("VAT").'</td>';
	    print '<td width="8%" align="center">'.$langs->trans("Qty").'</td>';
	    print '<td width="8%" align="right">'.$langs->trans("Discount").'</td>';
	    print '<td width="12%" align="right">'.$langs->trans("PriceU").'</td>';
	    print '<td>&nbsp;</td><td>&nbsp;</td>'."</tr>\n";
	    print '<input type="hidden" name="action" value="addligne">';
	    print "<tr $bc[$var]>".'<td><textarea name="desc" cols="60" rows="1"></textarea></td>';
	    print '<td align="center">';
	    print $html->select_tva("tva_tx",$conf->defaulttx);
	    print '</td>';
	    print '<td align="center"><input type="text" name="qty" value="1" size="2"></td>';
	    print '<td align="right"><input type="text" name="remise_percent" size="3" value="0">%</td>';
	    print '<td align="right"><input type="text" name="pu" size="7"></td>';

	    print '<td align="center" colspan="3"><input type="submit" value="'.$langs->trans("Add").'"></td></tr>';

	    $var=!$var;
	    print "<tr $bc[$var]><td colspan=\"2\"><select name=\"p_idprod\">$opt</select></td>";
	    print '<td align="center"><input type="text" size="2" name="pqty" value="1"></td>';
	    print '<td align="right"><input type="text" size="3" name="premise" value="0">%</td>';
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

	if ($user->societe_id == 0 && $commande->statut < 3 && $_GET["action"] == '')
	  {
	    print '<div class="tabsAction">';
	
	    if ($conf->expedition->enabled && $commande->statut > 0 && $commande->statut < 3 && $user->rights->expedition->creer)
	      {
		print '<a class="butAction" href="'.DOL_URL_ROOT.'/expedition/commande.php?id='.$_GET["id"].'">'.$langs->trans("Send").'</a>';
	      }
	  
	    
	    if ($commande->statut == 0) 
	      {
		if ($user->rights->commande->valider)
		  {
		    print '<a class="butAction" href="fiche.php?id='.$id.'&amp;action=valid">'.$langs->trans("Valid").'</a>';
		  }
	      }
	    
	    if ($commande->statut == 0 && $user->rights->commande->supprimer)
	      {
		print '<a class="butActionDelete" href="fiche.php?id='.$id.'&amp;action=delete">'.$langs->trans("Delete").'</a>';
	      } 
	    
	    if ($commande->statut == 1)
	      {
    		$nb_expedition = $commande->nb_expedition();
    		if ($user->rights->commande->valider && $nb_expedition == 0)
    		  {
    		    print '<a class="butAction" href="fiche.php?id='.$id.'&amp;action=annuler">'.$langs->trans("Cancel").'</a>';
    		  }
	      }

	    print "</div>";
	  }
	print "<br>\n";


	print '<table width="100%"><tr><td width="50%" valign="top">';

	/*
	 * Liste des expéditions
	 */
	$sql = "SELECT e.rowid,e.ref,".$db->pdate("e.date_expedition")." as de";
	$sql .= " FROM ".MAIN_DB_PREFIX."expedition as e";
	$sql .= " WHERE e.fk_commande = ". $commande->id;
	    
	$result = $db->query($sql);
	if ($result)
	  {
	    $num = $db->num_rows($result);
	    if ($num)
	      {
		print_titre($langs->trans("Sendings"));
		$i = 0; $total = 0;
		print '<table class="border" width="100%">';
		print "<tr $bc[$var]><td>".$langs->trans("Sendings")."</td><td>".$langs->trans("Date")."</td></tr>\n";
		
		$var=True;
		while ($i < $num)
		  {
		    $objp = $db->fetch_object($result);
		    $var=!$var;
		    print "<tr $bc[$var]>";
		    print '<td><a href="../expedition/fiche.php?id='.$objp->rowid.'">'.img_object($langs->trans("ShowSending"),"sending").' '.$objp->ref.'</a></td>';
		    print "<td>".dolibarr_print_date($objp->de)."</td></tr>\n";
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
		print "<tr $bc[$var]><td>".$langs->trans("Bill")."</td><td>".$langs->trans("Date")."</td></tr>\n";
		
		$var=True;
		while ($i < $num)
		  {
		    $objp = $db->fetch_object($result);
		    $var=!$var;
		    print "<tr $bc[$var]>";
		    print '<td><a href="../compta/facture.php?facid='.$objp->rowid.'">'.img_object($langs->trans("ShowBill"),"bill").' '.$objp->facnumber.'</a></td>';
		    print "<td>".dolibarr_print_date($objp->df)."</td></tr>\n";
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
	$relativepath = $commande->ref."/".$commande->ref.".pdf";
    
	$var=true;
	
	if (file_exists($file))
	  {

	    print_titre($langs->trans("Documents"));
	    print '<table width="100%" class="border">';
	    
	    print "<tr $bc[$var]><td>".$langs->trans("Order")." PDF</td>";
        print '<td><a href="'.DOL_URL_ROOT . '/document.php?modulepart=commande&file='.urlencode($relativepath).'">'.$commande->ref.'.pdf</a></td>';
	    print '<td align="right">'.filesize($file). ' bytes</td>';
	    print '<td align="right">'.strftime("%d %b %Y %H:%M:%S",filemtime($file)).'</td>';
	    print '</tr>';
	           	
	    print "</table>\n";
	    print '</td><td valign="top" width="50%">';
	  }
	/*
	 * Classe la commande dans un projet
	 * TODO finir le look & feel très moche
	 */
	if ($_GET["action"] == 'classer')
	  {	    
	    print '<form method="post" action="fiche.php?id='.$commande->id.'">';
	    print '<input type="hidden" name="action" value="classin">';
	    print '<table class="border" width="100%">';
	    print '<tr><td>'.$langs->trans("Project").'</td><td>';
	    
	    $proj = new Project($db);
	    $html->select_array("projetid",$proj->liste_array($commande->soc_id));
	    
	    print "</td></tr>";
	    print '<tr><td colspan="2" align="center"><input type="submit" value="'.$langs->trans("Save").'"></td></tr></table></form>';
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
