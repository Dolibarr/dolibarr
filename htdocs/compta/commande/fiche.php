<?php
/* Copyright (C) 2003-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
       \file       htdocs/compta/fiche.php
       \ingroup    commande
       \brief      Fiche commande
       \version    $Revision$
*/

require("./pre.inc.php");

$langs->load("orders");
$langs->load("sendings");
$langs->load("companies");
$langs->load("bills");
$langs->load("propal");

$user->getrights('facture');

if (! $user->rights->commande->lire) accessforbidden();

require_once DOL_DOCUMENT_ROOT."/project.class.php";
require_once DOL_DOCUMENT_ROOT."/propal.class.php";


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

if ($_GET["action"] == 'facturee') 
{
  $commande = new Commande($db);
  $commande->fetch($_GET["id"]);
  $commande->classer_facturee();
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
  $commande = New Commande($db);
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
    	  $hselected = $h;
    	  $h++;
        }
 
       dolibarr_fiche_head($head, $hselected, $soc->nom." / ".$langs->trans("Order")." : $commande->ref");
      
      /*
       *   Commande
       */
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
	}
      print '&nbsp;</td></tr>';

      print "<tr><td>".$langs->trans("Customer")."</td>";
      print '<td colspan="2">';
      print '<b><a href="'.DOL_URL_ROOT.'/compta/fiche.php?socid='.$soc->id.'">'.$soc->nom.'</a></b></td>';
      
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
      print '<td>'.$langs->trans("Currency".$conf->monnaie).'</td>';
      print '<td rowspan="4" valign="top">'.$langs->trans("Note").' :<br>'.nl2br($commande->note).'</td></tr>';
      
      print '<tr><td>'.$langs->trans("GlobalDiscount").'</td><td align="right">';
      print $commande->remise_percent.' %</td><td>&nbsp;';
      print '</td></tr>';
      
      print '<tr><td>'.$langs->trans("VAT").'</td><td align="right">'.price($commande->total_tva).'</td>';
      print '<td>'.$langs->trans("Currency".$conf->monnaie).'</td></tr>';
      print '<tr><td>'.$langs->trans("TotalTTC").'</td><td align="right">'.price($commande->total_ttc).'</td>';
      print '<td>'.$langs->trans("Currency".$conf->monnaie).'</td></tr>';      
      print "</table>\n";
      
      /*
       * Lignes de commandes
       *
       */
      echo '<br><table class="noborder" width="100%">';	  
      
      $sql = "SELECT l.fk_product, l.description, l.price, l.qty, l.rowid, l.tva_tx, l.remise_percent, l.subprice";
      $sql .= " FROM ".MAIN_DB_PREFIX."commandedet as l WHERE l.fk_commande =".$commande->id." ORDER BY l.rowid";
      
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
	      $var=!$var;
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
	      print '<td align="center">'.$objp->tva_tx.'%</td>';
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
	      print '<td>&nbsp;</td><td>&nbsp;</td>';
	      print "</tr>";	      

	      $i++;
	    }	      
	  $db->free($result);
	} 
      else
	{
	  dolibarr_print_error($db);
	}
      print '</table>';
      
      print '</div>';


        /*
         * Barre d'actions
         */
        
        if (! $user->societe_id && ! $commande->facturee)
        {
            print "<div class=\"tabsAction\">\n";
        
            if ($commande->statut > 0 && $user->rights->facture->creer)
            {
                print '<a class="butAction" href="'.DOL_URL_ROOT.'/compta/facture.php?action=create&amp;commandeid='.$commande->id.'&amp;socidp='.$commande->soc_id.'">'.$langs->trans("GenerateBill").'</a>';
            }
        
            if ($user->rights->commande->creer)
            {
                print '<a class="butAction" href="'.DOL_URL_ROOT.'/compta/commande/fiche.php?action=facturee&amp;id='.$commande->id.'">'.$langs->trans("ClassifyBilled").'</a>';
            }
            print '</div>';
        }
        

    print "<table width=\"100%\"><tr><td width=\"50%\" valign=\"top\">";


      /*
       * Documents générés
       *
       */
      $file = $conf->facture->dir_output . "/" . $commande->ref . "/" . $commande->ref . ".pdf";
      $relativepath = $commande->ref."/".$commande->ref.".pdf";

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

	}

	/*
	 * Liste des factures
	 */
	$sql = "SELECT f.rowid,f.facnumber, f.total_ttc, ".$db->pdate("f.datef")." as df";
	$sql .= " FROM ".MAIN_DB_PREFIX."facture as f, ".MAIN_DB_PREFIX."co_fa as cf";
	$sql .= " WHERE f.rowid = cf.fk_facture AND cf.fk_commande = ". $commande->id;
	    
	$result = $db->query($sql);
	if ($result)
	  {
	    $num = $db->num_rows($result);
	    if ($num)
	      {
        print '<br>';
		print_titre($langs->trans("RelatedBills"));
		$i = 0; $total = 0;
		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre"><td>'.$langs->trans("Ref")."</td>";
		print '<td align="center">'.$langs->trans("Date").'</td>';
		print '<td align="right">'.$langs->trans("Price").'</td>';
		print "</tr>\n";
		
		$var=True;
		while ($i < $num)
		  {
		    $objp = $db->fetch_object($result);
		    $var=!$var;
		    print "<tr $bc[$var]>";
		    print '<td><a href="../compta/facture.php?facid='.$objp->rowid.'">'.img_object($langs->trans("ShowBill"),"bill").' '.$objp->facnumber.'</a></td>';
		    print '<td align="center">'.dolibarr_print_date($objp->df).'</td>';
		    print '<td align="right">'.$objp->total_ttc.'</td></tr>';
		    $i++;
		  }
		print "</table>";
	      }
	  }
	else
	  {
	    dolibarr_print_error($db);
	  }

    print '</td><td valign="top" width="50%">';

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

	print "</td></tr></table>";   
	  
    }
  else
    {
      // Commande non trouvée
      print "Commande inexistante";
    }
}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
