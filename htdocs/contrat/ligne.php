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
if ($_POST["action"] == 'confirm_active' && $_POST["confirm"] == 'yes' && $user->rights->contrat->activer)
{
  $contrat = new Contrat($db);
  $contrat->fetch($_GET["id"]);

  $result = $contrat->active_line($user, $_GET["ligne"], $_GET["date"]);

  if ($result == 0)
    {
      Header("Location: fiche.php?id=".$contrat->id);
    }
      
}


llxHeader('',$langs->trans("Contract"),"Contrat");

$html = new Form($db);

/* *************************************************************************** */
/*                                                                             */
/* Mode vue et edition                                                         */
/*                                                                             */
/* *************************************************************************** */
  
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
	  $h++;

	  $head[$h][0] = DOL_URL_ROOT.'/contrat/ligne.php?id='.$contrat->id;
	  $head[$h][1] = $langs->trans("Edition de la ligne");
	  $hselected = $h;
	  
	  dolibarr_fiche_head($head, $hselected, $contrat->societe->nom);	  



	  /*
	   *   Contrat
	   */


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
	  

	  
	  /*
	   * Confirmation de la validation
	   *
	   */
	  if ($_GET["action"] == 'active' && $user->rights->contrat->activer)
	    {
	      print '<br />';
	      $dateact = mktime(12, 0 , 0, $_POST["remonth"], $_POST["reday"], $_POST["reyear"]); 
	      $html->form_confirm("ligne.php?id=".$contrat->id."&amp;ligne=".$_GET["ligne"]."&amp;date=".$dateact,"Activer le service","Etes-vous sûr de vouloir activer ce service en date du ".strftime("%A %d %B %Y", $dateact)." ?","confirm_active");
	    }


	  
	  /*
	   * Lignes de contrats
	   *
	   */
	  echo '<br><table border="0" width="100%" cellspacing="0" cellpadding="3">';	  

	  $sql = "SELECT l.statut, l.label, l.fk_product, l.description, l.price_ht, l.qty, l.rowid, l.tva_tx, l.remise_percent, l.subprice";
	  $sql .= " FROM ".MAIN_DB_PREFIX."contratdet as l";
	  $sql .= "  WHERE l.fk_contrat = ".$id;
	  $sql .= " AND rowid = ".$_GET["ligne"];
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
		      print '<img src="./statut'.$objp->statut.'.png" border="0" alt="statut">&nbsp;';
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

		  print '<td>&nbsp;</td><td>&nbsp;</td>';

		  print "</tr>\n";
		  

		  $i++;
		  $var=!$var;
		}	      
	      $db->free();
	    } 
	  else
	    {
	      print $db->error();
	    }
	

	print '</div>';


	print "<p>\n";



	if ( $user->rights->contrat->activer && $contrat->statut == 0 && $objp->statut <> 4)
	  {
	    /**
	     * Activer la ligne de contrat
	     */
	    $form = new Form($db);
	    
	    print '<table class="noborder" cellpadding="2" cellspacing="0" width="100%"><tr><td>';
	    
	    print '<form action="ligne.php?id='.$contrat->id.'&amp;ligne='.$_GET["ligne"].'&amp;action=active" method="post">';
	    print '<table class="noborder" cellpadding="2" cellspacing="0">';
	    print '<tr class="liste_titre"><td colspan="2">Activer le service</td><td>';
	    print '<tr><td>Date d\'activation</td><td>';

	    if ($_POST["remonth"])
	      {
		$dateact = mktime(12, 0 , 0, $_POST["remonth"], $_POST["reday"], $_POST["reyear"]); 
	      }
	    else
	      {
		$dateact = time();
	      }

	    print $form->select_date($dateact);
	    print '</td>';

	    print '<tr><td>Intervenant</td><td>'.$user->fullname.'</td></tr>';

	    print '<tr><td>Commentaire</td><td><input size="30" type="text" name="commentaire"></td></tr>';

	    print '<tr><td colspan="2" align="center"><input type="submit" name="Activer"></td></tr>';
	    print '</table>';
	    
	    print '</form></td><td>';
	    
	    print '&nbsp;</td></tr></table>';
	  }
      }
    else
      {
	/* Contrat non trouvée */
	print "Contrat inexistante ou accés refusé";
      }
  }  

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
