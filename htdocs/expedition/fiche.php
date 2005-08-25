<?php
/* Copyright (C) 2003-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005      Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Simon TOSSER  <simon@kornog-computing.com>
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
        \file       htdocs/expedition/fiche.php
        \ingroup    expedition
        \brief      Fiche descriptive d'une expedition
        \version    $Revision$
*/

require("./pre.inc.php");

$langs->load("bills");

if (!$user->rights->expedition->lire)
  accessforbidden();

require_once(DOL_DOCUMENT_ROOT."/propal.class.php");
require_once(DOL_DOCUMENT_ROOT."/product/stock/entrepot.class.php");

// Sécurité accés client
if ($user->societe_id > 0) 
{
  $action = '';
  $socidp = $user->societe_id;
}


/*
 * Actions
 */

if ($_POST["action"] == 'add') 
{
    $expedition = new Expedition($db);
    
    $expedition->date_expedition  = time();
    $expedition->note             = $_POST["note"];
    $expedition->commande_id      = $_POST["commande_id"];
    $expedition->entrepot_id      = $_POST["entrepot_id"];
    
    $commande = new Commande($db);
    $commande->fetch($expedition->commande_id);
    $commande->fetch_lignes();
    
    $db->begin();
    
    for ($i = 0 ; $i < sizeof($commande->lignes) ; $i++)
    {
        $qty = "qtyl".$i;
        $idl = "idl".$i;
        if ($_POST[$qty] > 0)
        {
            $expedition->addline($_POST[$idl],$_POST[$qty]);
        }
    }
    
    $ret=$expedition->create($user);
    if ($ret > 0)
    {
        $db->commit();
        Header("Location: fiche.php?id=".$expedition->id);
        exit;
    }
    else
    {
        $db->rollback();
        $mesg='<div class="error">'.$expedition->error.'</div>';
        $_GET["commande_id"]=$_POST["commande_id"];
        $_GET["action"]='create';
    }
}

if ($_POST["action"] == 'confirm_valid' && $_POST["confirm"] == 'yes' && $user->rights->expedition->valider)
{
  $expedition = new Expedition($db);
  $expedition->fetch($_GET["id"]);
  $result = $expedition->valid($user);
  $expedition->PdfWrite();
}

if ($_POST["action"] == 'confirm_delete' && $_POST["confirm"] == 'yes')
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
 * Générer ou regénérer le PDF
 */

if ($_GET["action"] == 'pdf')
{
  $expedition = new Expedition($db);
  $expedition->fetch($_GET["id"]);
  $expedition->PdfWrite();
}


/*
 *
 */

$html = new Form($db);

/*********************************************************************
 *
 * Mode creation
 *
 *********************************************************************/
if ($_GET["action"] == 'create') 
{
  llxHeader('','Fiche expedition','ch-expedition.html',$form_search);

  print_titre($langs->trans("CreateASending"));

  if ($mesg)
  {
        print $mesg.'<br>';
  }
  
  $commande = new Commande($db);
  $commande->livraison_array();
  
  if ( $commande->fetch($_GET["commande_id"]))
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
      print '<input type="hidden" name="entrepot_id" value="'.$_GET["entrepot_id"].'">';
      print '<table class="border" width="100%">';
      print '<tr><td width="20%">'.$langs->trans("Customer").'</td>';
      print '<td width="30%"><b><a href="'.DOL_URL_ROOT.'/comm/fiche.php?socid='.$soc->id.'">'.$soc->nom.'</a></b></td>';
      
      print '<td width="50%" colspan="2">';

      print "</td></tr>";
      
      print "<tr><td>".$langs->trans("Date")."</td>";
      print "<td>".strftime("%A %d %B %Y",$commande->date)."</td>\n";
      
      print '<td colspan="2" width="50%">'.$langs->trans("Order").': ' . $commande->ref;
      print "</td></tr>\n";
      
      print '<tr><td>'.$langs->trans("Warehouse").'</td>';
      print '<td>';
      $ents = $entrepot->list_array();
      print $ents[$_GET["entrepot_id"]];
      print '</td>';
      print "<td>".$langs->trans("Author")."</td><td>$author->fullname</td>\n";
      
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
      
      $lignes = $commande->fetch_lignes(1);
      
      /* Lecture des livraisons déjà effectuées */
      $commande->livraison_array();
      
      $num = sizeof($commande->lignes);
      $i = 0;
      
      if ($num)
	{
	  print '<tr class="liste_titre">';
	  print '<td width="54%">'.$langs->trans("Description").'</td>';
	  print '<td align="center">Quan. commandée</td>';
	  print '<td align="center">Quan. livrée</td>';
	  print '<td align="center">Quan. à livrer</td>';
	  if ($conf->stock->enabled)
	    {
	      print '<td width="12%" align="center">'.$langs->trans("Stock").'</td>';
	    }
	  print "</tr>\n";
	}
      $var=true;
      while ($i < $num)
	{
	  $ligne = $commande->lignes[$i];
	  $var=!$var;
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
	      print "<td>".nl2br($ligne->description)."</td>\n";
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
	      
	  if ($conf->stock->enabled)
	    {
	      $stock = $product->stock_entrepot[$_POST["entrepot_id"]];

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

      print '<tr><td align="center" colspan="4"><br><input type="submit" value="'.$langs->trans("Create").'"></td></tr>';
      print "</table>";
      print '</form>';
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

  if ($_GET["id"] > 0)
    {
      $expedition = New Expedition($db);
      $result = $expedition->fetch($_GET["id"]);
      
      if ( $expedition->id > 0)
	{	  
	  $author = new User($db);
	  $author->id = $expedition->user_author_id;
	  $author->fetch();

	  llxHeader('','Fiche expedition','ch-expedition.html',$form_search,$author);
	 
	  $commande = New Commande($db);
	  $commande->fetch($expedition->commande_id);

	  $soc = new Societe($db);
	  $soc->fetch($commande->soc_id);

	  $h=0;          
	  $head[$h][0] = DOL_URL_ROOT."/expedition/fiche.php?id=".$expedition->id;
	  $head[$h][1] = $langs->trans("SendingCard");
	  $hselected = $h;
	  $h++;
	  	 	  
	  dolibarr_fiche_head($head, $hselected, $langs->trans("Sending")." : ".$expedition->ref);

	  /*
	   * Confirmation de la suppression
	   *
	   */
	  if ($_GET["action"] == 'delete')
	    {
	      $html->form_confirm("fiche.php?id=$expedition->id","Supprimer l'expedition","Etes-vous sûr de vouloir supprimer cette expedition ?","confirm_delete");
	      print '<br>';
	    }
	  
	  /*
	   * Confirmation de la validation
	   *
	   */
	  if ($_GET["action"] == 'valid')
	    {
	      $html->form_confirm("fiche.php?id=$expedition->id","Valider l'expédition","Etes-vous sûr de vouloir valider cette expédition ?","confirm_valid");
	      print '<br>';
	    }
	  /*
	   * Confirmation de l'annulation
	   *
	   */
	  if ($_GET["action"] == 'annuler')
	    {
	      $html->form_confirm("fiche.php?id=$expedition->id",$langs->trans("Cancel"),"Etes-vous sûr de vouloir annuler cette commande ?","confirm_cancel");
	      print '<br>';
	    }

	  /*
	   *   Commande
	   */
	  if ($commande->brouillon == 1 && $user->rights->commande->creer) 
	    {
	      print '<form action="fiche.php?id='.$expedition->id.'" method="post">';
	      print '<input type="hidden" name="action" value="setremise">';
	    }

	  print '<table class="border" width="100%">';
	  print '<tr><td width="20%">'.$langs->trans("Customer").'</td>';
	  print '<td width="30%">';
	  print '<b><a href="'.DOL_URL_ROOT.'/comm/fiche.php?socid='.$soc->id.'">'.$soc->nom.'</a></b></td>';
	  
	  print '<td width="20%">'.$langs->trans("Author").'</td><td width="30%">'.$author->fullname.'</td>';

	  print "</tr>";
	  
	  print '<tr><td>'.$langs->trans("Order").'</td>';
	  print '<td><a href="'.DOL_URL_ROOT.'/expedition/commande.php?id='.$commande->id.'">'.$commande->ref."</a></td>\n";
	  print '<td>&nbsp;</td><td>&nbsp;</td></tr>';

	  print '<tr><td>'.$langs->trans("Date").'</td>';
	  print "<td>".strftime("%A %d %B %Y",$expedition->date)."</td>\n";

	  $entrepot = new Entrepot($db);
	  $entrepot->fetch($expedition->entrepot_id);

	  print '<td width="20%">'.$langs->trans("Warehouse").'</td><td><a href="'.DOL_URL_ROOT.'/product/stock/fiche.php?id='.$entrepot->id.'">'.$entrepot->libelle.'</a></td></tr>';

	  print "</table>\n";
	  	  
	  /*
	   * Lignes 
	   *
	   */
	  echo '<br><table class="noborder" width="100%">';

	  $sql = "SELECT cd.fk_product, cd.description, cd.rowid, cd.qty as qty_commande";
	  $sql .= " , ed.qty as qty_livre";
	  $sql .= " FROM ".MAIN_DB_PREFIX."commandedet as cd , ".MAIN_DB_PREFIX."expeditiondet as ed";
	  $sql .= " WHERE ed.fk_expedition = $expedition->id AND cd.rowid = ed.fk_commande_ligne ";
	  
	  $resql = $db->query($sql);

	  if ($resql)
	    {
	      $num_prod = $db->num_rows($resql);
	      $i = 0;

	      print '<tr class="liste_titre">';
	      print '<td width="54%">'.$langs->trans("Products").'</td>';
	      print '<td align="center">Quan. commandée</td>';
	      print '<td align="center">Quan. livrée</td>';
	      print "</tr>\n";

	      $var=true;
	      while ($i < $num_prod)
		{
		  $objp = $db->fetch_object($resql);
		  
		  $var=!$var;
		  print "<tr $bc[$var]>";
		  if ($objp->fk_product > 0)
		    {
		      print '<td>';
		      print '<a href="'.DOL_URL_ROOT.'/product/fiche.php?id='.$objp->fk_product.'">'.stripslashes(nl2br($objp->description)).'</a></td>';
		    }
		  else
		    {
		      print "<td>".stripslashes(nl2br($objp->description))."</td>\n";
		    }
		  print '<td align="center">'.$objp->qty_commande.'</td>';
		  print '<td align="center">'.$objp->qty_livre.'</td>';
		  
		  print "</tr>";
		  
		  $i++;
		  $var=!$var;
		}	      
	      $db->free($resql);
	    } 
	  else
	    {
	      dolibarr_print_error($db);
	    }
	  	  
	  print "</table>\n";

	  print "\n</div>\n";


	  /*
	   *
	   */
	  if ($user->societe_id == 0)
	    {
	      print '<div class="tabsAction">';	
	    
	      if ($expedition->statut == 0 && $user->rights->expedition->valider && $num_prod > 0)
		{
		  print '<a class="butAction" href="fiche.php?id='.$expedition->id.'&amp;action=valid">'.$langs->trans("Validate").'</a>';
		}

	      print '<a class="butAction" href="fiche.php?id='.$expedition->id.'&amp;action=pdf">'.$langs->trans('BuildPDF').'</a>';
	    
	      if ($expedition->brouillon && $user->rights->expedition->supprimer)
		{
		  print '<a class="butActionDelete" href="fiche.php?id='.$expedition->id.'&amp;action=delete">'.$langs->trans("Delete").'</a>';
		} 

	      print '</div>';	      	   
	    }
	

        /*
         * Documents générés
         */
        $filename=sanitize_string($expedition->id);
        $filedir=$conf->expedition->dir_output . "/" .get_exdir($expedition->id);
        $urlsource=$_SERVER["PHP_SELF"]."?id=".$expedition->id;
        //$genallowed=$user->rights->expedition->creer;
        //$delallowed=$user->rights->expedition->supprimer;
        $genallowed=0;
        $delallowed=0;
        
        $var=true;
        
        print "<br>\n";
        $html->show_documents('expedition',$filename,$filedir,$urlsource,$genallowed,$delallowed,$propal->modelpdf);


	  /*
	   * Déjà livré
	   *
	   */
	  $sql = "SELECT cd.fk_product, cd.description, cd.rowid, cd.qty as qty_commande";
	  $sql .= " , ed.qty as qty_livre, e.ref";
	  $sql .= ",".$db->pdate("e.date_expedition")." as date_expedition";
	  $sql .= " FROM ".MAIN_DB_PREFIX."commandedet as cd";
	  $sql .= " , ".MAIN_DB_PREFIX."expeditiondet as ed, ".MAIN_DB_PREFIX."expedition as e";
	  $sql .= " WHERE cd.fk_commande = $expedition->commande_id";
	  $sql .= " AND e.rowid <> $expedition->id";
	  $sql .= " AND cd.rowid = ed.fk_commande_ligne";
	  $sql .= " AND ed.fk_expedition = e.rowid";
	  $sql .= " ORDER BY cd.fk_product";

	  $resql = $db->query($sql);
	  if ($resql)
	    {
	      $num = $db->num_rows($resql);
	      $i = 0;
	    
	      if ($num)
		{
		  print '<br><table class="liste" cellpadding="3" width="100%"><tr>';
		  print '<tr class="liste_titre">';
		  print '<td width="54%">'.$langs->trans("Description").'</td>';
		  print '<td align="center">Quan. livrée</td>';
		  print '<td align="center">Expédition</td>';
		  print '<td align="center">'.$langs->trans("Date").'</td>';
		
		  print "</tr>\n";
		
		  $var=True;
		  while ($i < $num)
		    {
		      $objp = $db->fetch_object($resql);
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
		      print '<td align="center">'.dolibarr_print_date($objp->date_expedition).'</td>';
		      $i++;
		    }
		
		  print '</table>';
		}
	      $db->free($resql);
	    }

	       
	  /*
	   * Documents générés
	   *
	   */
	  $file = $conf->commande->dir_output . "/" . $commande->ref . "/" . $commande->ref . ".pdf";
	  $relativepath = $commande->ref . "/" . $commande->ref . ".pdf";

	  $var=true;
    	
	  if (file_exists($file))
	    {
	      print "<table width=\"100%\" cellspacing=2><tr><td width=\"50%\" valign=\"top\">";
	      print_titre("Documents");
	      print '<table width="100%" class="border">';
	    
	      print "<tr $bc[$true]><td>".$langs->trans("Order")." PDF</td>";
	      print '<td><a href="'.DOL_URL_ROOT.'/document.php?modulepart=commande&file='.urlencode($relativepath).'">'.$commande->ref.'.pdf</a></td>';
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
	      $sql .= " FROM ".MAIN_DB_PREFIX."actioncomm as a";
	      $sql .= " WHERE a.fk_soc = $commande->socidp AND a.fk_action in (9,10)";
	      $sql .= " AND a.fk_commande = $expedition->id";
	    
	      $resql = $db->query($sql);
	      if ($resql)
		{
		  $num = $db->num_rows($resql);
		  if ($num)
		    {
		      $i = 0;
		      print '<table class="border" width="100%">';
		      print "<tr $bc[$var]><td>".$langs->trans("Date")."</td><td>".$langs->trans("Action")."</td></tr>\n";
		    
		      $var=True;
		      while ($i < $num)
			{
			  $objp = $db->fetch_object($resql);
			  $var=!$var;
			  print "<tr $bc[$var]>";
			  print "<td>".strftime("%d %B %Y",$objp->da)."</td>\n";
			  print '<td>'.stripslashes($objp->note).'</td>';
			  print "</tr>";
			  $i++;
			}
		      print "</table>";
		    }
		  $db->free($resql);
		}
	      else
		{
		  dolibarr_print_error($db);
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
	    
	      print "<form method=\"post\" action=\"fiche.php?id=$expedition->id&amp;action=send\">\n";
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
	  /* Expedition non trouvée */
	  llxHeader('','Fiche expedition','ch-expedition.html',$form_search);
	  print "Expedition inexistante ou accés refusé";
	}
    }
  else
    {
      /* Expedition non trouvée */
      llxHeader('','Fiche expedition','ch-expedition.html',$form_search);
      print "Expedition inexistante ou accés refusé";
    }
}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
