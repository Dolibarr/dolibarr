<?PHP
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

/**
* Gestion d'une proposition commerciale
* @package propale
*/

require("./pre.inc.php");

$user->getrights('propale');
if (!$user->rights->propale->lire)
  accessforbidden();

/*
 *  Modules optionnels
 */
require("../project.class.php");
require("./propal_model_pdf.class.php");
require("../propal.class.php");
require("../actioncomm.class.php");
require("../lib/CMailFile.class.php");

/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $action = '';
  $socidp = $user->societe_id;
}

/******************************************************************************/
/*                     Actions                                                */
/******************************************************************************/

if ($_POST["action"] == 'confirm_delete' && $_POST["confirm"] == yes)
{
  if ($user->rights->propale->supprimer ) 
    {
      $propal = new Propal($db, 0, $_GET["propalid"]);
      $propal->delete();
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
      $_GET["propalid"] = $id;
    }
}

if ($action == 'pdf')
{
  $propal = new Propal($db);
  $propal->fetch($propalid);
  propale_pdf_create($db, $propalid, $propal->modelpdf);
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

if ($_POST["addligne"] == 'Ajouter' && $user->rights->propale->creer) 
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

if ($_POST["addproduct"] == 'Ajouter' && $user->rights->propale->creer) 
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

/******************************************************************************/
/*                   Fin des  Actions                                         */
/******************************************************************************/
/*
 *
 * Mode fiche
 *
 *
 */
if ($_GET["propalid"])
{
  $html = new Form($db);

  $propal = new Propal($db);
  $propal->fetch($_GET["propalid"]);

  $societe = new Societe($db);
  $societe->fetch($propal->soc_id);

  $head[0][0] = DOL_URL_ROOT.'/comm/propal.php?propalid='.$propal->id;
  $head[0][1] = "Proposition commerciale : $propal->ref";
  $h = 1;
  $a = 0;
  $head[$h][0] = DOL_URL_ROOT.'/comm/propal/note.php?propalid='.$propal->id;
  $head[$h][1] = "Note";
  $h++;
  $head[$h][0] = DOL_URL_ROOT.'/comm/propal/info.php?propalid='.$propal->id;
  $head[$h][1] = "Info";

  dolibarr_fiche_head($head, $a, $societe->nom);

  /*
   * Confirmation de la suppression de la propale
   *
   */
  if ($_GET["action"] == 'delete')
    {
      $html->form_confirm("propal.php?propalid=$propal->id","Supprimer la proposition","Etes-vous sûr de vouloir supprimer cette proposition ?","confirm_delete");
    }
  /*
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
      $obj = $db->fetch_object( 0 );
    
      if ($db->num_rows()) 
	{

	  $color1 = "#e0e0e0";


	  if ($propal->brouillon == 1 && $user->rights->propale->creer)
	    {
	      /* la form est ouverte avant la table pour respect des normes */
	      print '<form action="propal.php?propalid='.$propal->id.'" method="post">';
	      print '<input type="hidden" name="action" value="setremise">';
	    }

	  print "<table class=\"border\" cellspacing=\"0\" cellpadding=\"2\" width=\"100%\">";

	  print '<tr><td>Société</td><td colspan="3">';
	  if ($societe->client == 1)
	    {
	      $url ='fiche.php?socid='.$societe->id;
	    }
	  else
	    {
	      $url = DOL_URL_ROOT.'/comm/prospect/fiche.php?socid='.$societe->id;
	    }
	  print '<a href="'.$url.'">'.$societe->nom.'</a></td>';
	  print '<td>Statut</td><td align="center"><b>'.$obj->lst.'</b></td></tr>';

	  print '<tr><td>Date</td><td colspan="3">'.strftime("%A %d %B %Y",$propal->date);
	  if ($propal->fin_validite)
	    {
	      print " (".strftime("%d %B %Y",$propal->fin_validite).")";
	    }
	  print '</td>';

	  print '<td>Auteur</td><td>';
	  $author = new User($db, $obj->fk_user_author);
	  $author->fetch('');
	  print $author->fullname.'</td></tr>';

	  print "<tr><td>Destinataire</td><td colspan=\"3\">$obj->firstname $obj->name &lt;$obj->email&gt;</td>";

	  print '<td valign="top" colspan="2" width="50%" rowspan="5">Note :<br>'. nl2br($propal->note)."</td></tr>";
	  
	  if ($propal->projet_id) 
	    {
	      $projet = new Project($db);
	      $projet->fetch($propal->projet_id); 
	      print '<tr><td>Projet</td><td colspan="3">';
	      print '<a href="../projet/fiche.php?id='.$projet->id.'">';
	      print $projet->title.'</a></td></tr>';
	    }

	  /*
	   *
	   */
	  if ($propal->brouillon == 1 && $user->rights->propale->creer)
	    {
	      print '<tr><td>Remise <a href="propal/aideremise.php?propalid='.$propal->id.'">?</a></td>';
	      print '<td align="right"><input type="text" name="remise" size="3" value="'.$propal->remise_percent.'">%</td>';
	      print '<td><input type="submit" value="Appliquer"></td>';

	    }
	  else
	    {
	      print '<tr><td>Remise</td>';
	      print '<td align="right">'.$propal->remise_percent.' %</td><td>&nbsp;</td>';
	    }

	  print '<td align="right">'.price($propal->remise).' euros</td></tr>';
	  /*
	   *
	   */
	  print '<tr><td>Montant HT</td><td align="right">'.price($obj->price + $obj->remise).' euros</td>';
	  print '<td align="right">TVA</td><td align="right">'.price($propal->total_tva).' euros</td></tr>';
	  	  
	  /*
	   *
	   */
	  print '<tr><td>Total HT</td><td align="right"><b>'.price($obj->price).'</b> euros</td>';
	  print '<td align="right">Total TTC</td><td align="right"><b>'.price($propal->total_ttc).'</b> euros</td></tr>';
	  /*
	   *
	   */

	  print "</table>";

	  if ($propal->brouillon == 1 && $user->rights->propale->creer)
	    {
	      print "</form>";
	    }


	  if ($_GET["action"] == 'statut') 
	    {
	      print "<form action=\"propal.php?propalid=$propal->id\" method=\"post\">";
	      print '<br><table class="border" cellpadding="3" cellspacing="0">';
	      print '<tr><td>Clôturer comme : <input type="hidden" name="action" value="setstatut">';
	      print "<select name=\"statut\">";
	      print "<option value=\"2\">Signée";
	      print "<option value=\"3\">Non Signée";
	      print '</select>';
	      print '</td></tr><tr><td>Commentaires : <br><textarea cols="60" rows="6" wrap="soft" name="note">';
	      print $obj->note;
	      print '</textarea></td></tr><tr><td align="center"><input type="submit" value="Valider"></td>';
	      print "</tr></table></form>";
	    }
	  /*
	   *
	   *
	   */

	  /*
	   * Produits
	   */
	  print_titre("Produits");
	  print '<form action="propal.php?propalid='.$propal->id.'" method="post">';
	  print '<table border="0" width="100%" cellspacing="0" cellpadding="3">';
	  print "<TR class=\"liste_titre\">";
	  print "<td>Réf</td><td>Produit</td>";
	  print '<td align="center">Tva</td><td align="center">Qté.</td><td align="center">Remise</td><td align="right">P.U.</td>';
	  if ($propal->statut == 0)
	    {
	      print "<td>&nbsp;</td>";
	    }
	  print "</tr>\n";

	  $sql = "SELECT pt.rowid, p.label as product, p.ref, pt.price, pt.qty, p.rowid as prodid, pt.tva_tx, pt.remise_percent, pt.subprice";
	  $sql .= " FROM ".MAIN_DB_PREFIX."propaldet as pt, ".MAIN_DB_PREFIX."product as p WHERE pt.fk_product = p.rowid AND pt.fk_propal = $propal->id";
	  $sql .= " ORDER BY pt.rowid ASC";
	  $result = $db->query($sql);
	  if ($result) 
	    {
	      $num = $db->num_rows();
	      $i = 0;
	      
	      $var=True;

	      while ($i < $num) 
		{
		  $objp = $db->fetch_object( $i);
		  $var=!$var;
		  print "<TR $bc[$var]>";
		  print "<td>[$objp->ref]</td>\n";
		  print '<td><a href="'.DOL_URL_ROOT.'/product/fiche.php?id='.$objp->prodid.'">'.$objp->product.'</a></td>';
		  print '<td align="center">'.$objp->tva_tx.' %</td>';
		  print "<td align=\"center\">".$objp->qty."</td>\n";
		  print '<td align="center">'.$objp->remise_percent.' %</td>';
		  print '<td align="right">'.price($objp->subprice).'</td>';
		  if ($propal->statut == 0 && $user->rights->propale->creer)
		    {
		      print '<td align="center"><a href="propal.php?propalid='.$propal->id.'&amp;ligne='.$objp->rowid.'&amp;action=del_ligne">';
		      print img_delete();
		      print '</a></td>';
		    }
		  print "</tr>";

		  $i++;
		}
	    }

	  $sql = "SELECT pt.rowid, pt.description, pt.price, pt.qty, pt.tva_tx, pt.remise_percent, pt.subprice";
	  $sql .= " FROM ".MAIN_DB_PREFIX."propaldet as pt WHERE pt.fk_propal = $propal->id AND pt.fk_product = 0";
	  
	  if ($db->query($sql)) 
	    {
	      $num = $db->num_rows();
	      $i = 0;	     
	      while ($i < $num) 
		{
		  $objp = $db->fetch_object( $i);
		  $var=!$var;
		  print "<TR $bc[$var]><td>&nbsp;</td>\n";
		  print '<td>'.$objp->description.'</td>';
		  print '<td align="center">'.$objp->tva_tx.' %</td>';
		  print "<td align=\"center\">".$objp->qty."</td>\n";
		  print '<td align="center">'.$objp->remise_percent.' %</td>';
		  print "<TD align=\"right\">".price($objp->subprice)."</td>";
		  if ($propal->statut == 0 && $user->rights->propale->creer)
		    {
		      print '<td align="center"><a href="propal.php?propalid='.$propal->id.'&amp;ligne='.$objp->rowid.'&amp;action=del_ligne">';
		      print img_delete();
		      print '</a></td>';
		    }
		  else
		    {
		      print '<td>-</td>';
		    }
		  print "</tr>";
		  $i++;
		}
	    }

	  if ($propal->statut == 0 && $user->rights->propale->creer)
	    {
	      $sql = "SELECT p.rowid,p.label,p.ref,p.price FROM ".MAIN_DB_PREFIX."product as p WHERE p.envente=1 ORDER BY p.nbvente DESC LIMIT 20";
	      // RyXéo on a ORDER BY p.ref et pas de limit
	      if ( $db->query($sql) )
		{
		  $opt = "<option value=\"0\" SELECTED></option>";
		  if ($result)
		    {
		      $num = $db->num_rows();	$i = 0;	
		      while ($i < $num)
			{
			  $objp = $db->fetch_object( $i);
			  $opt .= "<option value=\"$objp->rowid\">[$objp->ref] ".substr($objp->label,0,40)."</option>\n";
			  $i++;
			}
		    }
		  $db->free();
		}
	      else
		{
		  print $db->error();
		}

	      /*
	       * Produits génériques
	       *
	       */
	      $var=!$var;


	      print '<tr '.$bc[$var].'>';
	      print '<td>&nbsp;</td>';
	      print '<td><input type="text" size="28" name="np_desc"></td>';
	      print '<td align="center">';
	      print $html->select_tva("np_tva_tx") . '</td>';
	      print '<td align="center"><input type="text" size="3" value="1" name="np_qty"></td>';
	      print '<td align="center"><input type="text" size="3" value="0" name="np_remise"> %</td>';
	      print '<td align="right"><input type="text" size="6" name="np_price"></td>';
	      print '<td align="center"><input type="submit" value="Ajouter" name="addproduct"></td>';
	      print '</tr>';

	      /*
	       * Produits
	       *
	       */
	      $var=!$var;
	      print "<tr $bc[$var]><td>&nbsp;</td><td colspan=\"2\"><select name=\"idprod\">$opt</select></td>";
	      print '<td align="center"><input type="text" size="3" name="qty" value="1"></td>';
	      print '<td align="center"><input type="text" size="3" name="remise" value="0"> %</td>';
	      print '<td>&nbsp;</td>';
	      print '<td align="center"><input type="submit" value="Ajouter" name="addligne"></td>';
	      print "</tr>\n";
	    }
	  print "</table>";
	  print '</form>';


	  /*
	   * Actions
	   */
	  print '</div>';
	  if ($propal->statut < 2)
	    {
	      print '<p><div class="tabsAction">';
	  
	      if ($propal->statut == 0)
		{
		  if ($user->rights->propale->supprimer)
		    {
		      print "<a class=\"tabAction\" href=\"propal.php?propalid=$propal->id&amp;action=delete\">Supprimer</a>";
		    }

		}
	      else
		{
		  if ($propal->statut == 1 && $user->rights->propale->cloturer)
		    {
		      print "<a class=\"tabAction\" href=\"propal.php?propalid=$propal->id&amp;action=statut\">Clôturer</a>";
		    }
		} 

	      /*
	       *
	       */
	      if ($propal->statut < 2 && $user->rights->propale->creer)
		{
		  print '<a class="tabAction" href="propal.php?propalid='.$propal->id.'&amp;action=pdf">Générer</a>';
		}	   
	      /*
	       *
	       */
	      if ($propal->statut == 1)
		{
		  $file = PROPALE_OUTPUTDIR. "/$obj->ref/$obj->ref.pdf";
		  if (file_exists($file))
		    {
		      if ($user->rights->propale->envoyer)
			{
			  print "<a class=\"tabAction\" href=\"propal.php?propalid=$propal->id&amp;action=presend\">Envoyer la proposition</a>";
			}

		    }
		}
	      /*
	       * 
	       */
	      if ($propal->statut == 0)
		{
		  if ($user->rights->propale->valider)
		    {
		      print "<a class=\"tabAction\" href=\"propal.php?propalid=$propal->id&amp;valid=1\">Valider</a>";
		    }

		}
	      elseif ($propal->statut == 1)
		{
		  if ($user->rights->propale->creer)
		    {
		      print "<a class=\"tabAction\" href=\"propal.php?propalid=$propal->id&amp;action=modif\">Modifier</a>";
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
	      $file = PROPALE_OUTPUTDIR . "/$propal->ref/$propal->ref.pdf";
	      if (file_exists($file))
		{
	      
		  $subject = "Notre proposition commerciale $propal->ref";
		  $filepath[0] = $file ;
		  $filename[0] = "$propal->ref.pdf";
		  $mimetype[0] = "application/pdf";
		  $filepath[1] = $_FILES['addedfile']['tmp_name'];
		  $filename[1] = $_FILES['addedfile']['name'];
		  $mimetype[1] = $_FILES['addedfile']['type'];
		  $replyto = $_POST["replytoname"]. " <".$_POST["replytomail"].">";
	      
		  $mailfile = new CMailFile($subject,$_POST["sendto"],$replyto,$_POST["message"],$filepath,$mimetype,$filename,$sendtocc);
	      
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
	  print '<table width="100%" cellspacing="2"><tr><td width="50%" valign="top">';
	  print_titre('<a href="propal/document.php?id='.$propal->id.'">Documents</a>');

	  print '<table class="border" width="100%" cellspacing="0" cellpadding="3">';
	  
	  /*
	   *
	   */

	  $file = PROPALE_OUTPUTDIR . "/$propal->ref/$propal->ref.pdf";
	  if (file_exists($file))
	    {
	      print "<tr $bc[0]><td>PDF</td>";

	      $encfile = urlencode($file);

	      print '<td><a href="'.DOL_URL_ROOT . '/document.php?file='.$encfile.'">'.$propal->ref.'.pdf</a></td>';

	      print '<td align="right">'.filesize($file). ' bytes</td>';
	      print '<td align="right">'.strftime("%d %B %Y %H:%M:%S",filemtime($file)).'</td></tr>';
	    }  
	  /*
	   *
	   *
	   */


	  if ($propal->brouillon == 1 && $user->rights->propale->creer)
	    {
	      print "<tr $bc[1]><td>Modèle</td><td align=\"right\">";
	      $html = new Form($db);
	      $modelpdf = new Propal_Model_pdf($db);
	      $html->select_array("modelpdf",$modelpdf->liste_array(),$propal->modelpdf);
	      print '</td><td colspan="2"><input type="submit" value="Changer">';
	      print '</td></tr>';
	    }
	  print "</table>\n";
	  /*
	   *
	   */
	  $nb_commande = sizeof($propal->commande_liste_array());
	  if ($nb_commande > 0)
	    {
	      $coms = $propal->commande_liste_array();
	      print '<br><table class="border" width="100%" cellspacing="0" cellpadding="3">';

	      if ($nb_commande == 1)

		{
		  print "<tr><td>Commande rattachée : ";
		  print '<a href="'.DOL_URL_ROOT.'/commande/fiche.php?id='.$coms[$i].'">';
		  print img_file();
		  print '</a>&nbsp;<a href="'.DOL_URL_ROOT.'/commande/fiche.php?id='.$coms[$i].'">'.$coms[$i]."</a>";
		  print "</td></tr>\n";
		}
	      else
		{
		  print "<tr><td>Commandes rattachées</td></tr>\n";
	      
		  for ($i = 0 ; $i < $nb_commande ; $i++)
		    {
		      print '<tr><td><a href="'.DOL_URL_ROOT.'/commande/fiche.php?id='.$coms[$i].'">'.$coms[$i]."</a></td>\n";
		      print "</tr>\n";
		    }
		}
	      print "</table>";
	    }




	  //	  print '<a href="propal.php?propalid=$propalid&amp;action=commande\">Générer</a>";
	  /*
	   *
	   */
	  print "</td><td valign=\"top\" width=\"50%\">";
	  /*
	   *
	   */
	  $sql = "SELECT ".$db->pdate("a.datea"). " as da, note, fk_user_author" ;
	  $sql .= " FROM ".MAIN_DB_PREFIX."actioncomm as a WHERE a.fk_soc = $obj->idp AND a.propalrowid = $propal->id ";
	  
	  if ( $db->query($sql) )
	    {
	      $num = $db->num_rows();
	      $i = 0; $total = 0;

	      if ($num > 0)
		{
		  print_titre("Propale envoyée");

		  print '<table class="border" width="100%" cellspacing="0" cellpadding="3">';
		  print "<tr><td>Date</td><td>Auteur</td></TR>\n";
	      

		  while ($i < $num)
		    {
		      $objp = $db->fetch_object( $i);
		      print "<TR><TD>".strftime("%d %B %Y %H:%M:%S",$objp->da)."</TD>\n";
		      $authoract = new User($db);
		      $authoract->id = $objp->fk_user_author;
		      $authoract->fetch('');
		      print "<TD>$authoract->code</TD></tr>\n";
		      print "<tr><td colspan=\"2\">$objp->note</td></tr>";
		      $i++;
		    }
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
	   */
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
	      print '<table cellspacing="0" border="1" cellpadding="3">';
	      print "<tr><td>Destinataire</td>";
	      print "<td  colspan=\"6\" align=\"right\"><input size=\"50\" name=\"sendto\" value=\"" . ucfirst(strtolower($obj->firstname)) . " " .  ucfirst(strtolower($obj->name)) . " <$obj->email>\"></td></tr>";
	      print '<tr><td>Copie à</td>';
	      print '<td colspan="6" align="right"><input size="50" name="sendtocc"></td></tr>';
	      print "<tr><td>Expediteur</td><td colspan=\"5\">$from_name</td><td>$from_mail</td></tr>";
	      print "<tr><td>Reply-to</td><td colspan=\"5\">$replytoname</td>";
	      print "<td>$replytomail</td></tr>";
	      print "<tr><td valign=\"top\">Joindre un fichier en plus de la propale<br>(conditions générales de ventes ...)</td><td colspan=\"6\"><input type=\"file\" name=\"addedfile\" size=\"40\" maxlength=\"80\"></td></tr>";
	      print "<tr><td valign=\"top\">Message</td><td colspan=\"6\"><textarea rows=\"5\" cols=\"40\" name=\"message\">$message</textarea></td></tr>";
	      
	      print "</table>";
	      print "<input type=\"submit\" value=\"Envoyer\">";
	      print "</form>";
	    }
	  
	}
      else
	{
	  print "Num rows = " . $db->num_rows();
	  print "<p><b>$sql";
	}      
    }
  else
    {
      print $db->error();
      print "<p><b>$sql";
    }  
  /*
   *
   *
   *
   */
} else {
  /****************************************************************************
   *                                                                          *
   *                                                                          *
   *                         Mode Liste des propales                          *
   *                                                                          *
   *                                                                          *
   ****************************************************************************/

  if ($sortfield == "")
    {
      $sortfield="p.datep";
    }
  if ($sortorder == "")
    {
      $sortorder="DESC";
    }

  $limit = $conf->liste_limit;
  $offset = $limit * $_GET["page"] ;
  $pageprev = $_GET["page"] - 1;
  $pagenext = $_GET["page"] + 1;

  $sql = "SELECT s.nom, s.idp, s.client, p.rowid as propalid, p.price, p.ref,".$db->pdate("p.datep")." as dp,".$db->pdate("p.fin_validite")." as dfv, c.label as statut, c.id as statutid";
  $sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."propal as p, ".MAIN_DB_PREFIX."c_propalst as c WHERE p.fk_soc = s.idp AND p.fk_statut = c.id";

  if ($socidp)
    { 
      $sql .= " AND s.idp = $socidp"; 
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
      print_barre_liste("Propositions commerciales", $_GET["page"],"propal.php","&amp;socidp=$socidp",$sortfield,$sortorder,'',$num);


      $i = 0;
      print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';

      print '<TR class="liste_titre">';

      print_liste_field_titre_new ("Réf","propal.php","p.ref","","&amp;socidp=$socidp",'width="15%"',$sortfield);

      print_liste_field_titre_new ("Société","propal.php","s.nom","","&amp;socidp=$socidp",'width="30%"',$sortfield);

      print_liste_field_titre_new ("Date","propal.php","p.datep","","&amp;socidp=$socidp", 'width="25%" align="right" colspan="2"',$sortfield);
      print_liste_field_titre_new ("Prix","propal.php","p.price","","&amp;socidp=$socidp", ' width="20%" align="right"',$sortfield);

      print_liste_field_titre_new ("Statut","propal.php","p.fk_statut","","&amp;socidp=$socidp",'width="10%" align="center"',$sortfield);
      print "</tr>\n";
      $var=True;
      
      while ($i < min($num,$limit))
	{
	  $objp = $db->fetch_object( $i);
	  $now = time();
	  $var=!$var;
	  print "<tr $bc[$var]>";
	  print '<td><a href="propal.php?propalid='.$objp->propalid.'">';
	  print img_file();
	  print "</a>&nbsp;<a href=\"propal.php?propalid=$objp->propalid\">$objp->ref</a></TD>\n";
	  if ($objp->client == 1)
	    {
	      $url ='fiche.php?socid='.$objp->idp;
	    }
	  else
	    {
	      $url = DOL_URL_ROOT.'/comm/prospect/fiche.php?socid='.$objp->idp;
	    }

	  print '<td><a href="'.$url.'">'.$objp->nom.'</a></td>';

	  if ( $now > $objp->dfv && $objp->dfv > 0 )
	    {
	      print "<td>".strftime("%d %b %Y",$objp->dfv)."</td>";
	    }
	  else
	    {
	      print "<td>&nbsp;</td>";
	    }
	  
	  print "<TD align=\"right\">";
	  $y = strftime("%Y",$objp->dp);
	  $m = strftime("%m",$objp->dp);
	  
	  print strftime("%d",$objp->dp)."\n";
	  print " <a href=\"propal.php?year=$y&amp;month=$m\">";
	  print strftime("%B",$objp->dp)."</a>\n";
	  print " <a href=\"propal.php?year=$y\">";
	  print strftime("%Y",$objp->dp)."</a></TD>\n";      
	  
	  print "<TD align=\"right\">".price($objp->price)."</TD>\n";
	  print "<TD align=\"center\">$objp->statut</TD>\n";
	  print "</TR>\n";
	  
	  $total = $total + $objp->price;
	  $subtotal = $subtotal + $objp->price;
	  
	  $i++;
	}
            
      print "</table>";
      $db->free();
    }
  else
    {
      print $db->error();
    }
}
$db->close();
llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
