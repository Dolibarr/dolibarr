<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
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

/**     \file       htdocs/comm/propal.php
        \ingroup    propale
        \brief      Page liste des propales
*/

require("./pre.inc.php");

$langs->load("companies");
$langs->load("propal");
$langs->load("bills");

$user->getrights('propale');

if (!$user->rights->propale->lire)
  accessforbidden();

if ($conf->projet->enabled) {
  require_once "../project.class.php";
}
require("./propal_model_pdf.class.php");
require("../propal.class.php");
require("../actioncomm.class.php");
require("../lib/CMailFile.class.php");

/*
 *  Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $action = '';
  $socidp = $user->societe_id;
}

// Nombre de ligne pour choix de produit/service prédéfinis
$NBLINES=4;


/******************************************************************************/
/*                     Actions                                                */
/******************************************************************************/

if ($_POST["action"] == 'confirm_delete' && $_POST["confirm"] == 'yes')
{
  if ($user->rights->propale->supprimer) 
    {
      $propal = new Propal($db, 0, $_GET["propalid"]);
      $propal->delete($user);
      $propalid = 0;
      $brouillon = 1;
    }

  Header("Location: propal.php");
}


if ($_POST["action"] == 'add') 
{
  $propal = new Propal($db, $_GET["socidp"]);

  $propal->datep = mktime(12, 1 , 1, 
			  $_POST["remonth"], 
			  $_POST["reday"], 
			  $_POST["reyear"]);

  $propal->duree_validite = $_POST["duree_validite"];

  $propal->contactid = $_POST["contactidp"];
  $propal->projetidp = $_POST["projetidp"];
  $propal->modelpdf  = $_POST["modelpdf"];
  $propal->author    = $user->id;
  $propal->note      = $_POST["note"];

  $propal->ref = $_POST["ref"];

  for ($i = 1 ; $i <= PROPALE_NEW_FORM_NB_PRODUCT ; $i++)
    {
      $xid = "idprod".$i;
      $xqty = "qty".$i;
      $xremise = "remise".$i;

      $propal->add_product($_POST[$xid],$_POST[$xqty],$_POST[$xremise]);
    }
  
  $id = $propal->create();
  
  /*
   *   Generation
   */
  if ($id) 
    {
      propale_pdf_create($db, $id, $_POST["modelpdf"]);
      Header ("Location: propal.php?propalid=$id");
    }
}

if ($_GET["action"] == 'pdf')
{
    $propal = new Propal($db);
    $propal->fetch($_GET["propalid"]);
    propale_pdf_create($db, $_GET["propalid"], $propal->modelpdf);
}

if ($_POST["action"] == 'setstatut' && $user->rights->propale->cloturer) 
{
  /*
   *  Cloture de la propale
   */
  $propal = new Propal($db);
  $propal->fetch($_GET["propalid"]);
  $propal->cloture($user, $_POST["statut"], $_POST["note"]);
} 

if ($_GET["action"] == 'commande')
{
  /*
   *  Cloture de la propale
   */
  $propal = new Propal($db);
  $propal->fetch($propalid);
  $propal->create_commande($user);
} 

if ($_GET["action"] == 'modif' && $user->rights->propale->creer) 
{
  /*
   *  Repasse la propale en mode brouillon
   */
  $propal = new Propal($db);
  $propal->fetch($_GET["propalid"]);
  $propal->reopen($user->id);

}

if ($_POST["addligne"] == $langs->trans("Add") && $user->rights->propale->creer) 
{
  /*
   *  Ajout d'une ligne produit dans la propale
   */
  if ($_POST["idprod"])
    {
      $propal = new Propal($db);
      $propal->fetch($_GET["propalid"]);
      $propal->insert_product($_POST["idprod"], $_POST["qty"], $_POST["remise"]);
      propale_pdf_create($db, $_GET["propalid"], $propal->modelpdf);
    }
} 

if ($_POST["addproduct"] == $langs->trans("Add") && $user->rights->propale->creer) 
{
  /*
   *  Ajout d'une ligne produit dans la propale
   */
  if (strlen($_POST["np_desc"]) && strlen($_POST["np_price"]))
    {
      
      $propal = new Propal($db);
      $propal->fetch($_GET["propalid"]);
            
      $propal->insert_product_generic($_POST["np_desc"], 
				      $_POST["np_price"], 
				      $_POST["np_qty"],
				      $_POST["np_tva_tx"],
				      $_POST["np_remise"]);
    } 
}

if ($_POST["action"] == 'setremise' && $user->rights->propale->creer) 
{
  $propal = new Propal($db);
  $propal->fetch($_GET["propalid"]);
  $propal->set_remise($user, $_POST["remise"]);
  propale_pdf_create($db, $_GET["propalid"], $propal->modelpdf);
} 

if ($_POST["action"] == 'setpdfmodel' && $user->rights->propale->creer) 
{
  $propal = new Propal($db, 0, $_GET["propalid"]);
  $propal->set_pdf_model($user, $_POST["modelpdf"]);
  propale_pdf_create($db, $_GET["propalid"], $_POST["modelpdf"]);
} 


if ($_GET["action"] == 'del_ligne' && $user->rights->propale->creer) 
{
  /*
   *  Supprime une ligne produit dans la propale
   */
  $propal = new Propal($db);
  $propal->fetch($_GET["propalid"]);
  $propal->delete_product($_GET["ligne"]);
  propale_pdf_create($db, $_GET["propalid"], $propal->modelpdf);
}

if ($_GET["valid"] == 1 && $user->rights->propale->valider)
{
  $propal = new Propal($db);
  $propal->fetch($_GET["propalid"]);
  $propal->update_price($_GET["propalid"]);
  propale_pdf_create($db, $_GET["propalid"], $propal->modelpdf);
  $propal->valid($user);
}




llxHeader();


/*
 * Affichage fiche propal en mode visu
 *
 */
if ($_GET["propalid"])
{
  $html = new Form($db);

  $propal = new Propal($db);
  $propal->fetch($_GET["propalid"]);

  $societe = new Societe($db);
  $societe->fetch($propal->soc_id);
  $h=0;

  $head[$h][0] = DOL_URL_ROOT.'/comm/propal.php?propalid='.$propal->id;
  $head[$h][1] = $langs->trans("Card");
  $hselected=$h;
  $h++;

  $head[$h][0] = DOL_URL_ROOT.'/comm/propal/note.php?propalid='.$propal->id;
  $head[$h][1] = $langs->trans("Note");
  $h++;

  $head[$h][0] = DOL_URL_ROOT.'/comm/propal/info.php?propalid='.$propal->id;
  $head[$h][1] = $langs->trans("Info");
  $h++;

  dolibarr_fiche_head($head, $hselected, $langs->trans("Proposal").": $propal->ref");

  /*
   * Confirmation de la suppression de la propale
   *
   */
  if ($_GET["action"] == 'delete')
    {
      $html->form_confirm("propal.php?propalid=$propal->id",$langs->trans("DeleteProp"),$langs->trans("ConfirmDeleteProp"),"confirm_delete");
      print '<br>';
    }


  /*
   * Fiche propal
   *
   */
  $sql = "SELECT s.nom, s.idp, p.price, p.fk_projet,p.remise, p.tva, p.total, p.ref,".$db->pdate("p.datep")." as dp, c.id as statut, c.label as lst, p.note, x.firstname, x.name, x.fax, x.phone, x.email, p.fk_user_author, p.fk_user_valid, p.fk_user_cloture, p.datec, p.date_valid, p.date_cloture";
  $sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."propal as p, ".MAIN_DB_PREFIX."c_propalst as c, ".MAIN_DB_PREFIX."socpeople as x";
  $sql .= " WHERE p.fk_soc = s.idp AND p.fk_statut = c.id AND x.idp = p.fk_soc_contact AND p.rowid = $propal->id";

  if ($socidp)
    { 
      $sql .= " AND s.idp = $socidp"; 
    }

  $result = $db->query($sql);
  if ( $result )
    {
   
      if ($db->num_rows($result)) 
	{

      $obj = $db->fetch_object($result);
	  $color1 = "#e0e0e0";

	  if ($propal->brouillon == 1 && $user->rights->propale->creer)
	    {
	      /* la form est ouverte avant la table pour respect des normes */
	      print '<form action="propal.php?propalid='.$propal->id.'" method="post">';
	      print '<input type="hidden" name="action" value="setremise">';
	    }

	  print "<table class=\"border\" width=\"100%\">";
      $rowspan=7;
        
	  print '<tr><td>'.$langs->trans("Company").'</td><td colspan="3">';
	  if ($societe->client == 1)
	    {
	      $url ='fiche.php?socid='.$societe->id;
	    }
	  else
	    {
	      $url = DOL_URL_ROOT.'/comm/prospect/fiche.php?socid='.$societe->id;
	    }
	  print '<a href="'.$url.'">'.$societe->nom.'</a></td>';
	  print '<td align="left" colspan="2">Conditions de réglement : </td></tr>';

	  print '<tr><td>'.$langs->trans("Date").'</td><td colspan="3">'.strftime("%A %d %B %Y",$propal->date);
	  if ($propal->fin_validite)
	    {
	      print " (".strftime("%d %B %Y",$propal->fin_validite).")";
	    }
	  print '</td>';

	  print '<td colspan="2">&nbsp;</td></tr>';

	  $langs->load("mails");
	  print "<tr><td>".$langs->trans("MailTo")."</td><td colspan=\"3\">$obj->firstname $obj->name".($obj->email?" &lt;$obj->email&gt;":"")."</td>";

	  if ($conf->projet->enabled && $propal->projet_id) $rowspan++;
                
	  print '<td valign="top" colspan="2" width="50%" rowspan="'.$rowspan.'">'.$langs->trans("Note").' :<br>'. nl2br($propal->note)."</td></tr>";
	  
	  if ($conf->projet->enabled && $propal->projet_id) 
	    {
          $langs->load("projects");
          
	      $projet = new Project($db);
	      $projet->fetch($propal->projet_id); 
	      print '<tr><td>'.$langs->trans("Projects").'</td><td colspan="3">';
	      print '<a href="../projet/fiche.php?id='.$projet->id.'">';
	      print $projet->title.'</a></td></tr>';
	    }

	  $author = new User($db, $obj->fk_user_author);
	  $author->fetch('');
	  print "<tr><td height=\"10\">".$langs->trans("Author")."</td><td colspan=\"3\">$author->fullname</td></tr>";

  	  print '<tr><td height=\"10\">'.$langs->trans("GlobalDiscount").'</td>';
	  if ($propal->brouillon == 1 && $user->rights->propale->creer)
	    {
	      print '<form action="propal.php?propid='.$fac->id.'" method="post">';
	      print '<td colspan="3"><input type="text" name="remise" size="3" value="'.$propal->remise_percent.'">% ';
	      print '<input type="submit" value="'.$langs->trans("Modify").'">';
	      print ' <a href="propal/aideremise.php?propalid='.$propal->id.'">?</a>';
          print '</td>';
	      print '</form>';
	    }
	  else
	    {
	      print '<td colspan="3">'.$propal->remise_percent.' %</td>';
	    }
      print '</tr>';

	  print '<tr><td height=\"10\">'.$langs->trans("AmountHT").'</td>';
	  print '<td align="right" colspan="2"><b>'.price($obj->price).'</b></td>';
	  print '<td>'.$conf->monnaie.'</td></tr>';

	  print '<tr><td height=\"10\">'.$langs->trans("VAT").'</td><td align="right" colspan="2">'.price($propal->total_tva).'</td>';
	  print '<td>'.$conf->monnaie.'</td></tr>';
	  print '<tr><td height=\"10\">'.$langs->trans("AmountTTC").'</td><td align="right" colspan="2">'.price($propal->total_ttc).'</td>';
	  print '<td>'.$conf->monnaie.'</td></tr>';

	  print '<tr><td height=\"10\">'.$langs->trans("Status").'</td><td align="left" colspan="3">'.$propal->getLibStatut().'</td></tr>';

	  print "</table><br>";

	  if ($propal->brouillon == 1 && $user->rights->propale->creer)
	    {
	      print "</form>";
	    }


	  if ($_GET["action"] == 'statut') 
	    {
	      print "<form action=\"propal.php?propalid=$propal->id\" method=\"post\">";
	      print '<br><table class="border">';
	      print '<tr><td>Clôturer comme : <input type="hidden" name="action" value="setstatut">';
	      print "<select name=\"statut\">";
	      print "<option value=\"2\">Signée";
	      print "<option value=\"3\">Non Signée";
	      print '</select>';
	      print '</td></tr><tr><td>'.$langs->trans("Comments").' : <br><textarea cols="60" rows="6" wrap="soft" name="note">';
	      print $obj->note;
	      print '</textarea></td></tr><tr><td align="center"><input type="submit" value="'.$langs->trans("Valid").'"></td>';
	      print "</tr></table></form>";
	    }

      /*
       * Lignes de propale
       *
       */
	  $sql = "SELECT pt.rowid, pt.description, pt.price, pt.fk_product, pt.qty, pt.tva_tx, pt.remise_percent, pt.subprice, p.label as product, p.ref, p.fk_product_type, p.rowid as prodid";
	  $sql .= " FROM ".MAIN_DB_PREFIX."propaldet as pt LEFT JOIN ".MAIN_DB_PREFIX."product as p ON pt.fk_product=p.rowid";
	  $sql .= " WHERE pt.fk_propal = ".$propal->id;
	  $sql .= " ORDER BY pt.rowid ASC";
	  $result = $db->query($sql);
	  if ($result) 
	    {
	      $num_lignes = $db->num_rows($result);
	      $i = 0; $total = 0;

    	  print '<table class="noborder" width="100%">';
          if ($num_lignes)
        {
    	  print "<tr class=\"liste_titre\">";
		  print '<td width="54%">'.$langs->trans("Description").'</td>';
		  print '<td width="8%" align="right">'.$langs->trans("VAT").'</td>';
		  print '<td width="12%" align="right">'.$langs->trans("PriceUHT").'</td>';
		  print '<td width="8%" align="right">'.$langs->trans("Qty").'</td>';
		  print '<td width="8%" align="right">'.$langs->trans("Discount").'</td>';
		  print '<td width="10%" align="right">'.$langs->trans("AmountHT").'</td>';
		  print '<td>&nbsp;</td><td>&nbsp;</td>';
    	  print "</tr>\n";
		}	
	      $var=True;
	      while ($i < $num_lignes)
		{
		  $objp = $db->fetch_object($result);
		  $var=!$var;
		  print "<tr $bc[$var]>";
		  if ($objp->fk_product > 0)
		    {
		      print '<td><a href="'.DOL_URL_ROOT.'/product/fiche.php?id='.$objp->fk_product.'">';
        	  if ($objp->fk_product_type) print img_object($langs->trans("ShowService"),"service");
        	  else print img_object($langs->trans("ShowProduct"),"product");
		      print ' '.stripslashes(nl2br($objp->description?$objp->description:$objp->product)).'</a>';
		      if ($objp->date_start && $objp->date_end) { print " (Du ".dolibarr_print_date($objp->date_start)." au ".dolibarr_print_date($objp->date_end).")"; }
		      if ($objp->date_start && ! $objp->date_end) { print " (A partir du ".dolibarr_print_date($objp->date_start).")"; }
		      if (! $objp->date_start && $objp->date_end) { print " (Jusqu'au ".dolibarr_print_date($objp->date_end).")"; }
		      print '</td>';
		    }
		  else
		    {
		      print "<td>".stripslashes(nl2br($objp->description));
		      if ($objp->date_start && $objp->date_end) { print " (Du ".dolibarr_print_date($objp->date_start)." au ".dolibarr_print_date($objp->date_end).")"; }
		      if ($objp->date_start && ! $objp->date_end) { print " (A partir du ".dolibarr_print_date($objp->date_start).")"; }
		      if (! $objp->date_start && $objp->date_end) { print " (Jusqu'au ".dolibarr_print_date($objp->date_end).")"; }
		      print "</td>\n";
		    }

		  print '<td align="right">'.$objp->tva_tx.' %</td>';
		  print '<td align="right">'.price($objp->subprice)."</td>\n";
		  print '<td align="right">'.$objp->qty.'</td>';
		  if ($objp->remise_percent > 0)
		    {
		      print '<td align="right">'.$objp->remise_percent." %</td>\n";
		    }
		  else
		    {
		      print '<td>&nbsp;</td>';
		    }
		  print '<td align="right">'.price($objp->subprice*$objp->qty*(100-$objp->remise_percent)/100)."</td>\n";

		  // Icone d'edition et suppression		  
		  if ($propal->statut == 0  && $user->rights->propale->creer) 
		    {
		      print '<td align="right"><a href="propal.php?propalid='.$propal->id.'&amp;action=editline&amp;ligne='.$objp->rowid.'">';
		      //print img_edit();
		      print '</a></td>';
		      print '<td align="right"><a href="propal.php?propalid='.$propal->id.'&amp;action=del_ligne&amp;ligne='.$objp->rowid.'">';
		      print img_delete();
		      print '</a></td>';
		    }
		  else
		    {
		      print '<td>&nbsp;</td><td>&nbsp;</td>';
		    }
		  print "</tr>";

		  // Update ligne de facture
          // \todo


		  $total = $total + ($objp->qty * $objp->price);
		  $i++;
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
	  if ($propal->statut == 0 && $user->rights->propale->creer)
	    {
    	  print '<form action="propal.php?propalid='.$propal->id.'" method="post">';
	      print "<tr class=\"liste_titre\">";
		  print '<td width="54%">'.$langs->trans("Description").'</td>';
		  print '<td width="8%" align="right">'.$langs->trans("VAT").'</td>';
		  print '<td width="12%" align="right">'.$langs->trans("PriceUHT").'</td>';
		  print '<td width="8%" align="right">'.$langs->trans("Qty").'</td>';
		  print '<td width="8%" align="right">'.$langs->trans("Discount").'</td>';
	      print '<td>&nbsp;</td>';
	      print '<td>&nbsp;</td>';
	      print '<td>&nbsp;</td>';
	      print "</tr>\n";
	      print '<input type="hidden" name="propid" value="'.$propal->id.'">';
	      print '<input type="hidden" name="action" value="addligne">';

          // Ajout produit produits/services personalisé
	      $var=!$var;

	      print "<tr ".$bc[$var].">\n";
	      print "  <td><textarea cols=\"50\" name=\"np_desc\"></textarea></td>\n";
	      print "  <td align=\"center\">";
	      print $html->select_tva("np_tva_tx",$conf->defaulttx) . "</td>\n";
	      print "  <td align=\"right\"><input type=\"text\" size=\"6\" name=\"np_price\"></td>\n";
	      print "  <td align=\"right\"><input type=\"text\" size=\"3\" value=\"1\" name=\"np_qty\"></td>\n";
	      print "  <td align=\"right\"><input type=\"text\" size=\"3\" value=\"".$societe->remise_client."\" name=\"np_remise\"> %</td>\n";
	      print "  <td align=\"center\" colspan=\"3\"><input type=\"submit\" value=\"".$langs->trans("Add")."\" name=\"addproduct\"></td>\n";
	      print "</tr>";

	      // Ajout de produits/services prédéfinis
	      $sql = "SELECT p.rowid,p.label,p.ref,p.price FROM ".MAIN_DB_PREFIX."product as p WHERE p.envente=1 ORDER BY p.nbvente DESC LIMIT 20";
	      $result = $db->query($sql);
		  if ($result)
		    {
    		  $opt = "<option value=\"0\" selected></option>";
		      $num = $db->num_rows();	$i = 0;	
		      while ($i < $num)
			    {
			  $objp = $db->fetch_object($result);
			  $opt .= "<option value=\"$objp->rowid\">[$objp->ref] ".substr($objp->label,0,40)."</option>\n";
			  $i++;
			    }
		      $db->free();
    		}
   	      else
    		{
    		  dolibarr_print_error($db);
    		}

	      $var=!$var;
	      print "<tr $bc[$var]>";
	      print "<td colspan=\"2\"><select name=\"idprod\">".$opt."</select></td>";
	      print '<td>&nbsp;</td>';
	      print '<td align="right"><input type="text" size="3" name="qty" value="1"></td>';
	      print '<td align="right"><input type="text" size="3" name="remise" value="'.$societe->remise_client.'"> %</td>';
	      print '<td align="center" colspan="3"><input type="submit" value="'.$langs->trans("Add").'" name="addligne"></td>';
	      print "</tr>\n";
	      print "</form>";
	    }

	  print "</table><br>\n";

      }

	  /*
	   * Fin Ajout ligne
	   *
	   */
	  print '</div>';



	  /*
	   * Barre d'actions
	   */
	  if ($propal->statut < 2)
	    {
	      print '<p><div class="tabsAction">';
	  
          // Valid
	      if ($propal->statut == 0)
		{
		  if ($user->rights->propale->valider)
		    {
		      print "<a class=\"tabAction\" href=\"propal.php?propalid=$propal->id&amp;valid=1\">".$langs->trans("Valid")."</a>";
		    }

		}

          // Save
	     if ($propal->statut == 1)
		{
		  if ($user->rights->propale->creer)
		    {
		      print "<a class=\"tabAction\" href=\"propal.php?propalid=$propal->id&amp;action=modif\">".$langs->trans("Edit")."</a>";
		    }
		}

          // Build PDF
	      if ($propal->statut < 2 && $user->rights->propale->creer)
		{
		  print '<a class="tabAction" href="propal.php?propalid='.$propal->id.'&amp;action=pdf">'.$langs->trans("BuildPDF").'</a>';
		}	   

          // Send
	      if ($propal->statut == 1)
		{
		      if ($user->rights->propale->envoyer)
			{
                $file = $conf->propal->dir_output . "/$obj->ref/$obj->ref.pdf";
                if (file_exists($file))
                {
                    print "<a class=\"tabAction\" href=\"propal.php?propalid=$propal->id&amp;action=presend\">".$langs->trans("Send")."</a>";
                }
		    }
		}

          // Delete
	      if ($propal->statut == 0)
		{
		  if ($user->rights->propale->supprimer)
		    {
		      print "<a class=\"butDelete\" href=\"propal.php?propalid=$propal->id&amp;action=delete\">".$langs->trans("Delete")."</a>";
		    }
		}

          // Close
	      if ($propal->statut != 0)
		{
		  if ($propal->statut == 1 && $user->rights->propale->cloturer)
		    {
		      print "<a class=\"tabAction\" href=\"propal.php?propalid=$propal->id&amp;action=statut\">".$langs->trans("Close")."</a>";
		    }
		} 

	      print "</div>";

	    }



	  /*
	   * Envoi de la propale par mail
	   *
	   */
	  if ($_GET["action"] == 'send')
	    {
	      $file = $conf->propal->dir_output . "/$propal->ref/$propal->ref.pdf";
	      if (file_exists($file))
		{
	      
		  $subject = "Notre proposition commerciale $propal->ref";
		  $filepath[0] = $file ;
		  $filename[0] = "$propal->ref.pdf";
		  $mimetype[0] = "application/pdf";
		  $filepath[1] = $_FILES['addedfile']['tmp_name'];
		  $filename[1] = $_FILES['addedfile']['name'];
		  $mimetype[1] = $_FILES['addedfile']['type'];
	      $from = $_POST["fromname"] . " <".$_POST["frommail"] .">";
	      $replyto = $_POST["replytoname"]. " <".$_POST["replytomail"].">";
	      
		  $mailfile = new CMailFile($subject,$_POST["sendto"],$from,$_POST["message"],$filepath,$mimetype,$filename,$sendtocc);
	      
		  if (! $mailfile->sendfile() )
		    {	       
		      print "<b>!! erreur d'envoi";
		    }
		}
	      /*
	       * Enregistre l'action
	       *
	       */
	      
	      $actioncomm = new ActionComm($db);
	      $actioncomm->priority    = 2;
	      $actioncomm->type        = 3;		  
	      $actioncomm->date        = $db->idate(time());
	      $actioncomm->percent     = 100;
	      $actioncomm->contact     = $propal->contactid;      
	      $actioncomm->user        = $user;	      
	      $actioncomm->societe     = $propal->socidp;
	      $actioncomm->propalrowid = $propal->id;
	      $actioncomm->note        = "Envoyée à ".$_POST["sendto"];
	      $actioncomm->add($user);
	    }

	  /*
	   *
	   */
	  if ($propal->brouillon == 1)
	    {
	      print '<form action="propal.php?propalid='.$propal->id.'" method="post">';
	      print '<input type="hidden" name="action" value="setpdfmodel">';
	    }	  
	  print '<table width="100%"><tr><td width="50%" valign="top">';
	  print_titre('<a href="propal/document.php?id='.$propal->id.'">'.$langs->trans("Document").'</a>');


	  /*
	   *
	   */

	  print '<table class="border" width="100%">';
	  
	  $file = $conf->propal->dir_output . "/$propal->ref/$propal->ref.pdf";
      $relativepath = "$propal->ref/$propal->ref.pdf";

      $var=true;
      
	  if (file_exists($file))
	    {
	      print "<tr $bc[$var]><td>".$langs->trans("Propal")." PDF</td>";

	      print '<td><a href="'.DOL_URL_ROOT . '/document.php?modulepart=propal&file='.urlencode($relativepath).'">'.$propal->ref.'.pdf</a></td>';
	      print '<td align="right">'.filesize($file). ' bytes</td>';
	      print '<td align="right">'.strftime("%d %B %Y %H:%M:%S",filemtime($file)).'</td></tr>';
	    }  

	  if ($propal->brouillon == 1 && $user->rights->propale->creer)
	    {
	      print "<tr $bc[$var]><td>Modèle</td><td align=\"right\">";
	      $html = new Form($db);
	      $modelpdf = new Propal_Model_pdf($db);
	      $html->select_array("modelpdf",$modelpdf->liste_array(),$propal->modelpdf);
	      print '</td><td colspan="2"><input type="submit" value="'.$langs->trans("Save").'">';
	      print '</td></tr>';
	    }
	  print "</table>\n";

	  /*
	   * Si le module commandes est activé ...
	   */
	  if($conf->commande->enabled) {
	    $nb_commande = sizeof($propal->commande_liste_array());
	    if ($nb_commande > 0)
	      {
		$coms = $propal->commande_liste_array();
		print '<br><table class="border" width="100%">';
		
		    print "<tr><td>Commande(s) rattachée(s)</td></tr>\n";
		    
		    for ($i = 0 ; $i < $nb_commande ; $i++)
		      {
			print '<tr><td><a href="'.DOL_URL_ROOT.'/commande/fiche.php?id='.$coms[$i].'">'.$coms[$i]."</a></td>\n";
			print "</tr>\n";
		      }

		print "</table>";
	      }
	  }

	  print "</td><td valign=\"top\" width=\"50%\">";
	
	  /*
	   * Liste des actions propres à la propal
	   */
	  $sql = "SELECT id, ".$db->pdate("a.datea"). " as da, note, fk_user_author" ;
	  $sql .= " FROM ".MAIN_DB_PREFIX."actioncomm as a";
	  $sql .= " WHERE a.fk_soc = $obj->idp AND a.propalrowid = $propal->id ";
	  
	  $result = $db->query($sql);
	  if ($result)
	    {
	      $num = $db->num_rows($result);
	      if ($num)
		{
		  print_titre($langs->trans("ActionsOnPropal"));

	      $i = 0; $total = 0;
		  print '<table class="border" width="100%">';
		  print '<tr '.$bc[$var].'><td>'.$langs->trans("Ref").'</td><td>'.$langs->trans("Date").'</td><td>'.$langs->trans("Action").'</td><td>'.$langs->trans("By").'</td></tr>';
          print "\n";
          
		  $var=True;
		  while ($i < $num)
		    {
		      $objp = $db->fetch_object($result);
		      $var=!$var;
		      print "<tr $bc[$var]>";
		      print '<td><a href="'.DOL_URL_ROOT.'/comm/action/fiche.php?id='.$objp->id.'">'.img_object($langs->trans("ShowTask"),"task").' '.$objp->id.'</a></td>';
		      print '<td>'.dolibarr_print_date($objp->da)."</td>\n";
		      print '<td>'.stripslashes($objp->note).'</td>';
		      $authoract = new User($db);
		      $authoract->id = $objp->fk_user_author;
		      $authoract->fetch('');
		      print '<td>'.$authoract->code.'</td>';
		      print "</tr>\n";
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

	  if ($propal->brouillon == 1)
	    {
	      print '</form>';
	    }

	  /*
	   *
	   *
	   */
	  if ($_GET["action"] == 'presend')
	    {
	      $replytoname = $user->fullname;
	      $replytomail = $user->email;
	      
	      $from_name = $user->fullname ; //$conf->propal->fromtoname;
	      $from_mail = $user->email; //conf->propal->fromtomail;
	      
	      $message = "Veuillez trouver ci-joint notre proposition commerciale $propal->ref\n\nCordialement\n\n";


	      print "<form method=\"post\" ENCTYPE=\"multipart/form-data\" action=\"propal.php?propalid=$propal->id&amp;action=send\">\n";
	      print '<input type="hidden" name="replytoname" value="'.$replytoname.'">';
	      print '<input type="hidden" name="replytomail" value="'.$replytomail.'">';
	      print '<input type="hidden" name="max_file_size" value="2000000">';

	      print_titre("Envoyer la propale par mail");

	      // Créé l'objet formulaire mail
	      include_once("../html.formmail.class.php");
	      $formmail = new FormMail($db);	    
	      $formmail->fromname = $user->fullname;
	      $formmail->frommail = $user->email;
          $formmail->withfrom=1;
          $formmail->withto=ucfirst(strtolower($obj->firstname)) . " " .  ucfirst(strtolower($obj->name)) . " <$obj->email>";
          $formmail->withcc=1;
          $formmail->withtopic=$langs->trans("SendPropalRef","__PROPREF__");
          $formmail->withfile=1;
	      $formmail->withbody=1;
          // Tableau des substitutions
          $formmail->substit["__PROPREF__"]=$propal->ref;
          // Tableau des paramètres complémentaires
          $formmail->param["action"]="send";
          $formmail->param["models"]="propal_send";
          $formmail->param["propalid"]=$propal->id;
          $formmail->param["returnurl"]=DOL_URL_ROOT."/comm/propal.php?propalid=$propal->id";

          $formmail->show_form();
	    }
	  
	}
      else
	{
	  dolibarr_print_error($db);
	}      

}
else
{
  /****************************************************************************
   *                                                                          *
   *                         Mode Liste des propales                          *
   *                                                                          *
   ****************************************************************************/

  $sortorder=$_GET["sortorder"];
  $sortfield=$_GET["sortfield"];
  $page=$_GET["page"];
  $viewstatut=$_GET["viewstatut"];

  if (! $sortfield) $sortfield="p.datep";
  if (! $sortorder) $sortorder="DESC";
  $limit = $conf->liste_limit;
  $offset = $limit * $page ;
  $pageprev = $page - 1;
  $pagenext = $page + 1;

  $sql = "SELECT s.nom, s.idp, s.client, p.rowid as propalid, p.price, p.ref,".$db->pdate("p.datep")." as dp,".$db->pdate("p.fin_validite")." as dfv, c.label as statut, c.id as statutid";
  $sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."propal as p, ".MAIN_DB_PREFIX."c_propalst as c";
  $sql .= " WHERE p.fk_soc = s.idp AND p.fk_statut = c.id";

  if ($_GET["socidp"])
    { 
      $sql .= " AND s.idp = ".$_GET["socidp"]; 
    }
  
  if ($_GET["viewstatut"] <> '')
    {
      $sql .= " AND c.id = ".$_GET["viewstatut"]; 
    }
  
  if ($month > 0)
    {
      $sql .= " AND date_format(p.datep, '%Y-%m') = '$year-$month'";
    }
  if ($year > 0)
    {
      $sql .= " AND date_format(p.datep, '%Y') = $year";
    }
  
  if (strlen($_POST["sf_ref"]) > 0)
    {
      $sql .= " AND p.ref like '%".$_POST["sf_ref"] . "%'";
    }

  $sql .= " ORDER BY $sortfield $sortorder";
  $sql .= $db->plimit($limit + 1,$offset);

  if ( $db->query($sql) )
    {
      $num = $db->num_rows();
      print_barre_liste($langs->trans("ListOfProposals"), $page,"propal.php","&amp;socidp=$socidp",$sortfield,$sortorder,'',$num);


      $i = 0;
      print '<table class="noborder" width="100%">';

      print '<tr class="liste_titre">';
      print_liste_field_titre($langs->trans("Ref"),"propal.php","p.ref","","&amp;socidp=$socidp&amp;viewstatut=$viewstatut",'width="15%"',$sortfield);
      print_liste_field_titre($langs->trans("Company"),"propal.php","s.nom","","&amp;socidp=$socidp&amp;viewstatut=$viewstatut",'width="30%"',$sortfield);
      print_liste_field_titre($langs->trans("Date"),"propal.php","p.datep","","&amp;socidp=$socidp&amp;viewstatut=$viewstatut", 'width="25%" align="right" colspan="2"',$sortfield);
      print_liste_field_titre($langs->trans("Price"),"propal.php","p.price","","&amp;socidp=$socidp&amp;viewstatut=$viewstatut", ' width="20%" align="right"',$sortfield);
      print_liste_field_titre($langs->trans("Status"),"propal.php","p.fk_statut","","&amp;socidp=$socidp&amp;viewstatut=$viewstatut",'width="10%" align="center"',$sortfield);
      print "</tr>\n";
      $var=true;
      
      while ($i < min($num,$limit))
	{
	  $objp = $db->fetch_object();
	  $now = time();
	  $var=!$var;
	  print "<tr $bc[$var]>";
	  print '<td><a href="propal.php?propalid='.$objp->propalid.'">'.img_object($langs->trans("ShowPropal"),"propal").' '.$objp->ref."</a></td>\n";
	  if ($objp->client == 1)
	    {
	      $url = DOL_URL_ROOT.'/comm/fiche.php?socid='.$objp->idp;
	    }
	  else
	    {
	      $url = DOL_URL_ROOT.'/comm/prospect/fiche.php?socid='.$objp->idp;
	    }

	  print '<td><a href="'.$url.'">'.img_object($langs->trans("ShowCompany"),"company").' '.$objp->nom.'</a></td>';

	  if ( $now > $objp->dfv && $objp->dfv > 0 )
	    {
	      print "<td>".strftime("%d %b %Y",$objp->dfv)."</td>";
	    }
	  else
	    {
	      print "<td>&nbsp;</td>";
	    }
	  
	  print "<td align=\"right\">";
	  $y = strftime("%Y",$objp->dp);
	  $m = strftime("%m",$objp->dp);
	  
	  print strftime("%d",$objp->dp)."\n";
	  print " <a href=\"propal.php?year=$y&amp;month=$m\">";
	  print strftime("%B",$objp->dp)."</a>\n";
	  print " <a href=\"propal.php?year=$y\">";
	  print strftime("%Y",$objp->dp)."</a></td>\n";      
	  
	  print "<td align=\"right\">".price($objp->price)."</td>\n";
	  print "<td align=\"center\">$objp->statut</td>\n";
	  print "</tr>\n";
	  
	  $total = $total + $objp->price;
	  $subtotal = $subtotal + $objp->price;
	  
	  $i++;
	}
            
      print "</table>";
      $db->free();
    }
  else
    {
      dolibarr_print_error($db);
    }
}
$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");

?>
