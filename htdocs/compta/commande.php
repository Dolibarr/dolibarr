<?PHP
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004 Laurent Destailleur  <eldy@users.sourceforge.net>
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
$user->getrights('facture');
if (!$user->rights->commande->lire)
  accessforbidden();

require("../project.class.php");
require("../propal.class.php");
require("../commande/commande.class.php");

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


llxHeader();

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


      $head[0][0] = DOL_URL_ROOT.'/compta/commande.php?id='.$commande->id;
      $head[0][1] = "Commande : $commande->ref";
      $h = 1;
      $a = 0;

      dolibarr_fiche_head($head, $a);	 
      
      /*
       *   Commande
       */
      print '<table class="border" cellspacing="0" cellpadding="2" width="100%">';
      print "<tr><td>Client</td>";
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
      
      print "<tr><td>".$langs->trans("Author")."</td><td colspan=\"2\">$author->fullname</td>";
      
      print '<td>Projet : ';
      if ($commande->projet_id > 0)
	{
	  $projet = New Project($db);
	  $projet->fetch($commande->projet_id);
	  print '<a href="'.DOL_URL_ROOT.'/projet/fiche.php?id='.$commande->projet_id.'">'.$projet->title.'</a>';
	}
      print "&nbsp;</td></tr>";
      
      print '<tr><td>Montant</td>';
      print '<td align="right"><b>'.price($commande->total_ht).'</b></td>';
      print '<td>'.MAIN_MONNAIE.' HT</td>';
      
      print '<td>Note</td></tr>';
      
      print '<tr><td>Remise globale</td><td align="right">';
      
      print $commande->remise_percent.' %</td><td>&nbsp;';
      
      print '</td></tr>';
      
      print '<tr><td>TVA</td><td align="right">'.price($commande->total_tva).'</td>';
      print '<td>'.MAIN_MONNAIE.'</td></tr>';
      print '<tr><td>'.$langs->trans("TotalTTC").'</td><td align="right">'.price($commande->total_ttc).'</td>';
      print '<td>'.MAIN_MONNAIE.'</td></tr>';
      if ($commande->note)
	{
	  print '<tr><td colspan="5">Note : '.nl2br($commande->note)."</td></tr>";
	}
      
      print "</table>";
      
      /*
       * Lignes de commandes
       *
       */
      echo '<br><table border="0" width="100%" cellspacing="0" cellpadding="3">';	  
      
      $sql = "SELECT l.fk_product, l.description, l.price, l.qty, l.rowid, l.tva_tx, l.remise_percent, l.subprice";
      $sql .= " FROM ".MAIN_DB_PREFIX."commandedet as l WHERE l.fk_commande =".$commande->id." ORDER BY l.rowid";
      
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
	      $objp = $db->fetch_object( $i);
	      print "<tr $bc[$var]>";
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

	      print '<td>&nbsp;</td><td>&nbsp;</td>';

	      print "</tr>";
	      

	      $i++;
	      $var=!$var;
	    }	      
	  $db->free();
	} 
      else
	{
	  print $db->error();
	}
      print '</table>';
      
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
	  /*
	   *
	   *
	   */
	  print "</td></tr></table>";
	}
      /*
       * Factures associees
       */
      $sql = "SELECT f.facnumber, f.total,".$db->pdate("f.datef")." as df, f.rowid as facid, f.fk_user_author, f.paye";
      $sql .= " FROM ".MAIN_DB_PREFIX."facture as f, ".MAIN_DB_PREFIX."co_fa as fp WHERE fp.fk_facture = f.rowid AND fp.fk_commande = ".$commande->id;
      
      if ($db->query($sql) )
	{
	  $num_fac_asso = $db->num_rows();
	  $i = 0; $total = 0;

	  if ($num_fac_asso > 0)
	    {
	      print "<br>";
	      if ($num_fac_asso > 1)
		{
		  print_titre("Factures associées");
		}
	      else
		{
		  print_titre("Facture associée");
		}
	      print '<table class="border" width="100%" cellspacing="0" cellpadding="3">';
	      print '<tr><td>'.$langs->trans("Ref").'</td><td>'.$langs->trans("Date").'</td><td>'.$langs->trans("Author").'</td>';
	      print '<td align="right">'.$langs->trans("Price").'</td>';
	      print "</tr>\n";
	      
	      $var=True;
	      while ($i < $num_fac_asso)
		{
		  $objp = $db->fetch_object( $i);
		  $var=!$var;
		  print "<TR bgcolor=\"#e0e0e0\">";
		  print "<TD><a href=\"../compta/facture.php?facid=$objp->facid\">$objp->facnumber</a>";
		  if ($objp->paye)
		    { 
		      print " (<b>pay&eacute;e</b>)";
		    } 
		  print "</TD>\n";
		  print "<TD>".strftime("%d %B %Y",$objp->df)."</td>\n";
		  if ($objp->fk_user_author <> $user->id)
		    {
		      $fuser = new User($db, $objp->fk_user_author);
		      $fuser->fetch();
		      print "<td>".$fuser->fullname."</td>\n";
		    }
		  else
		    {
		      print "<td>".$user->fullname."</td>\n";
		    }
		  print '<TD align="right">'.price($objp->total).'</TD>';
		  print "</tr>";
		  $total = $total + $objp->total;
		  $i++;
		}
	      print "<tr><td align=\"right\" colspan=\"4\">".$langs->trans("TotalHT").": <b>$total</b> ".MAIN_MONNAIE."</td></tr>\n";
	      print "</table>";
	    }
	  $db->free();
	}
      else
	{
	  print $db->error();
	}
      /*
       *
       *
       */
      print '</div>';
      /*
       * Barre d'actions
       */
      
      if ($user->societe_id == 0 && !$commande->facturee)
	{
	  print "<br><div class=\"tabsAction\">\n";
	 

	  if ($user->rights->facture->creer)
	    print '<a class="tabAction" href="'.DOL_URL_ROOT.'/compta/facture.php?action=create&amp;commandeid='.$commande->id.'&amp;socidp='.$commande->soc_id.'">Facturer</a>';

	  if (!$commande->facturee && $num_fac_asso)
	    {
	      if ($user->rights->commande->creer)
		print '<a class="tabAction" href="'.DOL_URL_ROOT.'/compta/commande.php?action=facturee&amp;id='.$commande->id.'">Classer comme facturée</a>';

	    }
	  print '</div>';
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
