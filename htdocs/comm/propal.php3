<?PHP
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
require("./pre.inc.php3");

$user->getrights('propale');
if (!$user->rights->propale->lire)
  accessforbidden();

require("../lib/CMailFile.class.php3");
/*
 *  Modules optionnels
 */
require("../project.class.php3");
require("../propal.class.php3");
require("../actioncomm.class.php3");
/*
 *
 */

/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $action = '';
  $socidp = $user->societe_id;
}

llxHeader();

/******************************************************************************/
/*                     Actions                                                */
/******************************************************************************/

if ($action == 'add') 
{
  $propal = new Propal($db, $socidp);

  $propal->remise = $HTTP_POST_VARS["remise"];
  $propal->datep = $db->idate(mktime(12, 1 , 1, 
				     $HTTP_POST_VARS["remonth"], 
				     $HTTP_POST_VARS["reday"], 
				     $HTTP_POST_VARS["reyear"]));

  $propal->contactid = $HTTP_POST_VARS["contactidp"];
  $propal->projetidp = $HTTP_POST_VARS["projetidp"];
  $propal->modelpdf = $HTTP_POST_VARS["modelpdf"];
  $propal->author = $user->id;
  $propal->note = $HTTP_POST_VARS["note"];

  $propal->ref = $ref;

  $propal->add_product($idprod1,$qty1);
  $propal->add_product($idprod2,$qty2);
  $propal->add_product($idprod3,$qty3);
  $propal->add_product($idprod4,$qty4);
  
  $id = $propal->create();
  
  /*
   *   Generation
   */
  if ($id) 
    {
      propale_pdf_create($db, $id, $HTTP_POST_VARS["modelpdf"]);
      $propalid = $id;
    }
}

if ($action == 'pdf')
{
  $propal = new Propal($db);
  $propal->fetch($propalid);
  propale_pdf_create($db, $propalid, $propal->modelpdf);
}

if ($action == 'setstatut' && $user->rights->propale->cloturer) 
{
  /*
   *  Cloture de la propale
   */
  $propal = new Propal($db);
  $propal->id = $propalid;
  $propal->cloture($user->id, $statut, $note);

} 

if ($action == 'modif' && $user->rights->propale->creer) 
{
  /*
   *  Repasse la propale en mode brouillon
   */
  $propal = new Propal($db);
  $propal->id = $propalid;
  $propal->reopen($user->id);

}

if ($HTTP_POST_VARS["action"] == 'addligne' && $user->rights->propale->creer) 
{
  /*
   *  Ajout d'une ligne produit dans la propale
   */
  if ($HTTP_POST_VARS["idprod"])
    {
      $propal = new Propal($db);
      $propal->id = $propalid;
      $propal->insert_product($HTTP_POST_VARS["idprod"], $HTTP_POST_VARS["qty"]);
    }
} 

if ($HTTP_POST_VARS["action"] == 'addproduct' && $user->rights->propale->creer) 
{
  /*
   *  Ajout d'une ligne produit dans la propale
   */
  $propal = new Propal($db);
  $propal->id = $propalid;
  $propal->insert_product_generic($HTTP_POST_VARS["np_desc"], 
				  $HTTP_POST_VARS["np_price"], 
				  $HTTP_POST_VARS["np_qty"],
				  $HTTP_POST_VARS["np_tva_tx"]);
} 


if ($action == 'del_ligne' && $user->rights->propale->creer) 
{
  /*
   *  Supprime une ligne produit dans la propale
   */
  $propal = new Propal($db);
  $propal->id = $propalid;
  $propal->delete_product($ligne);
  
}

if ( $action == 'delete' && $user->rights->propale->supprimer ) 
{
  $sql = "DELETE FROM llx_propal WHERE rowid = $propalid;";
  if ( $db->query($sql) ) 
    {

      $sql = "DELETE FROM llx_propaldet WHERE fk_propal = $propalid ;";
      if ( $db->query($sql) ) 
	{
	  print "<b><font color=\"red\">Propal supprimée</font></b>";
	}
      else
	{
	  print $db->error();
	  print "<p>$sql";
	} 
    }
  else
    {
      print $db->error();
      print "<p>$sql";
    }
  $propalid = 0;
  $brouillon = 1;
}


/******************************************************************************/
/*                   Fin des  Actions                                         */
/******************************************************************************/
/*
 *
 * Mode fiche
 *
 *
 */
if ($propalid)
{
  $propal = new Propal($db);
  $propal->fetch($propalid);


  if ($valid == 1 && $user->rights->propale->valider)
    {
      $propal->update_price($propalid);
      propale_pdf_create($db, $propalid);
      $propal->valid($user);
    }
  /*
   *
   */
  print "<table width=\"100%\">";
  print "<tr><td><div class=\"titre\">Proposition commerciale : $propal->ref</div></td>";
  print "</table>";
  /*
   *
   */
  $sql = "SELECT s.nom, s.idp, p.price, p.fk_projet,p.remise, p.tva, p.total, p.ref,".$db->pdate("p.datep")." as dp, c.id as statut, c.label as lst, p.note, x.firstname, x.name, x.fax, x.phone, x.email, p.fk_user_author, p.fk_user_valid, p.fk_user_cloture, p.datec, p.date_valid, p.date_cloture";
  $sql .= " FROM llx_societe as s, llx_propal as p, c_propalst as c, llx_socpeople as x";
  $sql .= " WHERE p.fk_soc = s.idp AND p.fk_statut = c.id AND x.idp = p.fk_soc_contact AND p.rowid = $propalid";

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

	  print "<table border=\"1\" cellspacing=\"0\" cellpadding=\"2\" width=\"100%\">";

	  print '<tr><td>Société</td><td><a href="fiche.php3?socid='.$obj->idp.'">'.$obj->nom.'</a></td>';
	  print '<td>Statut</td><td align="center"><b>'.$obj->lst.'</b></td></tr>';

	  print '<tr><td>Date</td><td>'.strftime("%A %d %B %Y",$obj->dp).'</td>';

	  print '<td>Auteur</td><td>';
	  $author = new User($db, $obj->fk_user_author);
	  $author->fetch('');
	  print $author->fullname.'</td></tr>';

	  print "<tr><td>Destinataire</td><td>$obj->firstname $obj->name &lt;$obj->email&gt;</td>";

	  print '<td valign="top" colspan="2" width="50%" rowspan="7">Note :<br>'. nl2br($obj->note)."</td></tr>";
	  
	  if ($obj->fk_projet) 
	    {
	      $projet = new Project($db);
	      $projet->fetch($obj->fk_projet); 
	      print '<tr><td>Projet</td><td colspan="1">';
	      print '<a href="projet/fiche.php3?id='.$projet->id.'">';
	      print $projet->title.'</a></td></tr>';
	    }

	  /*
	   *
	   */

	  print "<tr><td>Montant HT</td><td align=\"right\">".price($obj->price)." euros</td></tr>";
	  /*
	   *
	   */
	  
	  print "<tr><td>Remise</td><td align=\"right\">".price($obj->remise)." euros</td></tr>";
	  
	  /*
	   *
	   */
	  
	  $totalht = $propal->price - $propal->remise ;
	  
	  print "<tr><td>Total HT</td><td align=\"right\"><b>".price($totalht)."</b> euros</td></tr>";
	  /*
	   *
	   */

      
	  /*
	   *
	   */

	  print "</table>";

	  if ($action == 'statut') 
	    {
	      print "<form action=\"$PHP_SELF?propalid=$propalid\" method=\"post\">";
	      print "<input type=\"hidden\" name=\"action\" value=\"setstatut\">";
	      print "<select name=\"statut\">";
	      print "<option value=\"2\">Signée";
	      print "<option value=\"3\">Non Signée";
	      print '</select>';
	      print '<br><textarea cols="60" rows="6" wrap="soft" name="note">';
	      print $obj->note . "\n----------\n";
	      print '</textarea><br><input type="submit" value="Valider">';
	      print "</form>";
	    }



	  /*
	   * Produits
	   */
	  print_titre("Produits");

	  print '<TABLE border="0" width="100%" cellspacing="0" cellpadding="3">';
	  print "<TR class=\"liste_titre\">";
	  print "<td>Réf</td><td>Produit</td>";
	  print "<td align=\"right\">Prix</TD><td align=\"center\">Qté.</td>";
	  if ($obj->statut == 0)
	    {
	      print "<td>&nbsp;</td>";
	    }
	  print "</TR>\n";

	  $sql = "SELECT pt.rowid, p.label as product, p.ref, pt.price, pt.qty, p.rowid as prodid";
	  $sql .= " FROM llx_propaldet as pt, llx_product as p WHERE pt.fk_product = p.rowid AND pt.fk_propal = $propalid";
	  
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
		  print "<TD>[$objp->ref]</TD>\n";
		  print '<td><a href="'.DOL_URL_ROOT.'/product/fiche.php3?id='.$objp->prodid.'">'.$objp->product.'</td>';
		  print "<TD align=\"right\">".price($objp->price)."</TD><td align=\"center\">".$objp->qty."</td>\n";
		  if ($obj->statut == 0 && $user->rights->propale->creer)
		    {
		      print '<td align="center"><a href="propal.php3?propalid='.$propalid.'&ligne='.$objp->rowid.'&action=del_ligne">Supprimer</a></td>';
		    }
		  else
		    {
		      print '<td>-</td>';
		    }
		  print "</tr>";

		  $i++;
		}

	    }

	  $sql = "SELECT pt.rowid, pt.description, pt.price, pt.qty";
	  $sql .= " FROM llx_propaldet as pt WHERE pt.fk_propal = $propalid AND pt.fk_product = 0";
	  
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
		  print "<TR $bc[$var]><td>&nbsp;</td>\n";
		  print '<td>'.$objp->description.'</td>';
		  print "<TD align=\"right\">".price($objp->price)."</TD><td align=\"center\">".$objp->qty."</td>\n";
		  if ($obj->statut == 0 && $user->rights->propale->creer)
		    {
		      print '<td align="center"><a href="propal.php3?propalid='.$propalid.'&ligne='.$objp->rowid.'&action=del_ligne">Supprimer</a></td>';
		    }
		  else
		    {
		      print '<td>-</td>';
		    }
		  print "</tr>";
		  $i++;
		}
	    }

	  if ($obj->statut == 0 && $user->rights->propale->creer)
	    {

	      $sql = "SELECT p.rowid,p.label,p.ref,p.price FROM llx_product as p WHERE p.envente=1 ORDER BY p.nbvente DESC LIMIT 20";
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

	      /*
	       * Produits génériques
	       *
	       */
	      $var=!$var;
	      print '<form action="propal.php3?propalid='.$propalid.'" method="post">';
	      print '<input type="hidden" name="action" value="addproduct">';
	      print '<tr '.$bc[$var].'>';
	      print '<td>&nbsp;</td>';
	      print '<td><input type="text" size="28" name="np_desc"></td>';
	      print '<td align="right"><input type="text" size="6" name="np_price"></td>';
	      print '<td align="center"><input type="text" size="3" name="np_qty"></td>';
	      print '<td align="center"><input type="submit" value="Ajouter"></td>';
	      print '</tr></form>';

	      $var=!$var;
	      print '<form action="propal.php3?propalid='.$propalid.'" method="post">';
	      print '<input type="hidden" name="action" value="addligne">';
	      print "<tr $bc[$var]><td>&nbsp;</td><td colspan=\"2\"><select name=\"idprod\">$opt</select></td>";
	      print '<td align="center"><input type="text" size="3" name="qty" value="1"></td>';
	      print '<td align="center"><input type="submit" value="Ajouter"></td>';
	      print "</tr>\n";
	      print '</form>';


	    }

	  print "</table>";

	  /*
	   * Actions
	   */
	  if ($obj->statut < 2)
	    {
	      print "<p><TABLE border=\"1\" width=\"100%\" cellspacing=\"0\" cellpadding=\"4\"><tr>";
	  
	      if ($obj->statut == 0)
		{
		  if ($user->rights->propale->supprimer)
		    {
		      print "<td align=\"center\" width=\"25%\">[<a href=\"$PHP_SELF?propalid=$propalid&action=delete\">Supprimer</a>]</td>";
		    }
		  else
		    {
		      print "<td align=\"center\" width=\"25%\">-</td>";
		    }
		}
	      else
		{
		  if ($obj->statut == 1 && $user->rights->propale->cloturer)
		    {
		      print "<td align=center>[<a href=\"$PHP_SELF?propalid=$propalid&action=statut\">Cloturer</a>]</td>";
		    }
		  else
		    {
		      print "<td align=\"center\" width=\"25%\">-</td>";
		    }
		} 
	      /*
	       *
	       */
	      if ($obj->statut < 2 && $user->rights->propale->creer)
		{
		  print '<td align="center" width="25%">[<a href="'.$PHP_SELF."?propalid=$propalid&action=pdf\">Générer</a>]</td>";
		}
	      else
		{
		  print '<td align="center" width="25%">-</td>';
		}
	    
	      /*
	       *
	       */
	      if ($obj->statut == 1)
		{
		  $file = PROPALE_OUTPUTDIR. "/$obj->ref/$obj->ref.pdf";
		  if (file_exists($file))
		    {
		      if ($user->rights->propale->envoyer)
			{
			  print "<td align=\"center\" width=\"25%\">";
			  print "[<a href=\"$PHP_SELF?propalid=$propalid&action=presend\">Envoyer la proposition</a>]</td>";
			}
		      else
			{
			  print '<td align="center" width="25%">-</td>';
			}
		    }
		  else
		    {
		      print '<td bgcolor="#e0e0e0" align="center" width="25%">! Propale non generee !</td>';
		    }
		}
	      else
		{
		  print "<td align=\"center\" width=\"25%\">-</td>";
		}
	      /*
	       * 
	       */
	      if ($obj->statut == 0)
		{
		  if ($user->rights->propale->valider)
		    {
		      print "<td align=\"center\" width=\"25%\">[<a href=\"$PHP_SELF?propalid=$propalid&valid=1\">Valider</a>]</td>";
		    }
		  else
		    {
		      print '<td align="center" width="25%">-</td>';
		    }
		}
	      elseif ($obj->statut == 1)
		{
		  if ($user->rights->propale->creer)
		    {
		      print "<td align=\"center\" width=\"25%\">[<a href=\"$PHP_SELF?propalid=$propalid&action=modif\">Modifier</a>]</td>";
		    }
		  else
		    {
		      print '<td align="center" width="25%">-</td>';
		    }
		}
	      else
		{
		  print '<td align="center" width="25%">-</td>';
		}
	      print "</tr></table>";
	    }
	  /*
	   *
	   */
	  
	  /*
	   * Send
	   *
	   */
	  if ($action == 'send')
	    {
	      $file = PROPALE_OUTPUTDIR . "/$obj->ref/$obj->ref.pdf";
	      if (file_exists($file))
		{
	      
		  $subject = "Notre proposition commerciale $obj->ref";
		  $message = "Veuillez trouver ci-joint notre proposition commerciale $obj->ref\n\nCordialement\n\n";
		  $filepath = $file ;
		  $filename = "$obj->ref.pdf";
		  $mimetype = "application/pdf";
	      
		  $replyto = "$replytoname <$replytomail>";
	      
		  $mailfile = new CMailFile($subject,$sendto,$replyto,$message,$filepath,$mimetype, $filename);
	      
		  if ( $mailfile->sendfile() )
		    {
		
		      print "<p>envoy&eacute; &agrave; $sendto";
		      print "<p>envoy&eacute; par ".htmlentities($replyto);
		    }
		  else
		    {
		      print "<b>!! erreur d'envoi";
		    }
		}
	      /*
	       * Enregistre l'action
	       *
	       * Ne fonctionne pas, a corriger !
	       */

	      if ( $db->query($sql) )
		{
		  $sql = "INSERT INTO llx_actioncomm (datea,fk_action,fk_soc, propalrowid,note, fk_user_author) ";
		  $sql .= " VALUES (now(), 3, $obj->idp, $propalid, 'Envoyée à $sendto',$user->id);";
		  if (! $db->query($sql) ) {
		    print $db->error();
		    print "<p>$sql</p>";
		  }
		}
	      else
		{
		  print $db->error();
		}
	    }
	  /*
	   *
	   */
	  
	  print '<table width="100%" cellspacing=2><tr><td width="50%" valign="top">';
	  print_titre('<a href="propal/document.php?id='.$propal->id.'">Documents</a>');
	  print "<table width=\"100%\" cellspacing=0 border=1 cellpadding=3>";
	  
	  $file = PROPALE_OUTPUTDIR . "/$obj->ref/$obj->ref.pdf";
	  if (file_exists($file))
	    {
	      print "<tr $bc[0]><td>Propale PDF</a></td>";
	      print '<td><a href="'.PROPALE_OUTPUT_URL.'/'.$propal->ref.'/'.$propal->ref.'.pdf">'.$propal->ref.'.pdf</a></td>';
	      print '<td align="right">'.filesize($file). ' bytes</td>';
	      print '<td align="right">'.strftime("%d %b %Y %H:%M:%S",filemtime($file)).'</td></tr>';
	    }  
	  
	  print "</table>\n";
	  /*
	   *
	   */
	  print "</td><td valign=\"top\" width=\"50%\">";
	  print_titre("Propale envoyée");
	  /*
	   *
	   */
	  $sql = "SELECT ".$db->pdate("a.datea"). " as da, note, fk_user_author" ;
	  $sql .= " FROM llx_actioncomm as a WHERE a.fk_soc = $obj->idp AND a.propalrowid = $propalid ";
	  
	  if ( $db->query($sql) )
	    {
	      $num = $db->num_rows();
	      $i = 0; $total = 0;
	      print "<TABLE border=\"1\" width=\"100%\" cellspacing=\"0\" cellpadding=\"3\">";
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
	  /*
	   *
	   *
	   */
	  if ($action == 'presend')
	    {
	      $replytoname = $user->fullname;
	      $replytomail = $user->email;
	      
	      $from_name = $user->fullname ; //$conf->propal->fromtoname;
	      $from_mail = $user->email; //conf->propal->fromtomail;
	      
	      print "<form method=\"post\" action=\"$PHP_SELF?propalid=$propalid&action=send\">\n";
	      print "<input type=\"hidden\" name=\"replytoname\" value=\"$replytoname\">\n";
	      print "<input type=\"hidden\" name=\"replytomail\" value=\"$replytomail\">\n";

	      print_titre("Envoyer la propale par mail");
	      print "<table cellspacing=0 border=1 cellpadding=3>";
	      print "<tr><td>Destinataire</td><td colspan=\"5\">$obj->firstname $obj->name</td>";
	      print "<td><input size=\"30\" name=\"sendto\" value=\"$obj->email\"></td></tr>";
	      print "<tr><td>Expediteur</td><td colspan=\"5\">$from_name</td><td>$from_mail</td></tr>";
	      print "<tr><td>Reply-to</td><td colspan=\"5\">$replytoname</td>";
	      print "<td>$replytomail</td></tr>";
	      
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
      /*
       * Voir le suivi des actions
       *
       *
       *
       */
      if ($suivi)
	{
	  $validor = new User($db, $obj->fk_user_valid);
	  $validor->fetch('');
	  $cloturor = new User($db, $obj->fk_user_cloture);
	  $cloturor->fetch('');
	  
	  print 'Suivi des actions<br>';
	  print '<table cellspacing=0 border=1 cellpadding=3>';
	  print '<tr><td>&nbsp;</td><td>Nom</td><td>Date</td></tr>';
	  print '<tr><td>Création</td><td>'.$author->fullname.'</td>';
	  print '<td>'.$obj->datec.'</td></tr>';
	  
	  print '<tr><td>Validation</td><td>'.$validor->fullname.'&nbsp;</td>';
	  print '<td>'.$obj->date_valid.'&nbsp;</td></tr>';
	  
	  print '<tr><td>Cloture</td><td>'.$cloturor->fullname.'&nbsp;</td>';
	  print '<td>'.$obj->date_cloture.'&nbsp;</td></tr>';      
	  print '</table>';
	}
      else 
	{
	  print '<p><a href="'.$PHP_SELF.'?propalid='.$propal->id.'&suivi=1">Voir le suivi des actions </a>';
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
  /*
   *
   *
   * Mode Liste des propales
   *
   * 
   */

  if ($sortfield == "")
    {
      $sortfield="p.datep";
    }
  if ($sortorder == "")
    {
      $sortorder="DESC";
    }

  if ($page == -1) { $page = 0 ; }

  $limit = $conf->liste_limit;
  $offset = $limit * $page ;
  $pageprev = $page - 1;
  $pagenext = $page + 1;

  $sql = "SELECT s.nom, s.idp, p.rowid as propalid, p.price - p.remise as price, p.ref,".$db->pdate("p.datep")." as dp, c.label as statut, c.id as statutid";
  $sql .= " FROM llx_societe as s, llx_propal as p, c_propalst as c WHERE p.fk_soc = s.idp AND p.fk_statut = c.id";

  if ($socidp)
    { 
      $sql .= " AND s.idp = $socidp"; 
    }
  
  if ($viewstatut <> '')
    {
      $sql .= " AND c.id = $viewstatut"; 
    }
  
  if ($month > 0)
    {
      $sql .= " AND date_format(p.datep, '%Y-%m') = '$year-$month'";
    }
  if ($year > 0)
    {
      $sql .= " AND date_format(p.datep, '%Y') = $year";
    }
  
  $sql .= " ORDER BY $sortfield $sortorder";
  $sql .= $db->plimit($limit + 1,$offset);

  if ( $db->query($sql) )
    {
      $num = $db->num_rows();
      print_barre_liste("Propositions commerciales", $page, $PHP_SELF,"&socidp=$socidp",$sortfield,$sortorder,'',$num);


      $i = 0;
      print '<TABLE border="0" width="100%" cellspacing="0" cellpadding="4">';

      print '<TR class="liste_titre">';

      print_liste_field_titre_new ("Réf",$PHP_SELF,"p.ref","","&socidp=$socidp",'width="15%"',$sortfield);

      print_liste_field_titre_new ("Société",$PHP_SELF,"s.nom","","&socidp=$socidp",'width="30%"',$sortfield);

      print_liste_field_titre_new ("Date",$PHP_SELF,"p.datep","","&socidp=$socidp", 'width="25%" align="right" colspan="2"',$sortfield);
      print_liste_field_titre_new ("Prix",$PHP_SELF,"p.price","","&socidp=$socidp", ' width="20%" align="right"',$sortfield);

      print_liste_field_titre_new ("Statut",$PHP_SELF,"p.fk_statut","","&socidp=$socidp",'width="10%" align="center"',$sortfield);
      print "</tr>\n";
      $var=True;
      
      while ($i < min($num,$limit))
	{
	  $objp = $db->fetch_object( $i);
	  
	  $var=!$var;
	  print "<TR $bc[$var]>";
	  print "<TD><a href=\"$PHP_SELF?propalid=$objp->propalid\">$objp->ref</a></TD>\n";
	  print "<TD><a href=\"fiche.php3?socid=$objp->idp\">$objp->nom</a></TD>\n";      
	  
	  $now = time();
	  $lim = 3600 * 24 * 15 ;
	  
	  if ( ($now - $objp->dp) > $lim && $objp->statutid == 1 )
	    {
	      print "<td><b> &gt; 15 jours</b></td>";
	    }
	  else
	    {
	      print "<td>&nbsp;</td>";
	    }
	  
	  print "<TD align=\"right\">";
	  $y = strftime("%Y",$objp->dp);
	  $m = strftime("%m",$objp->dp);
	  
	  print strftime("%d",$objp->dp)."\n";
	  print " <a href=\"propal.php3?year=$y&month=$m\">";
	  print strftime("%B",$objp->dp)."</a>\n";
	  print " <a href=\"propal.php3?year=$y\">";
	  print strftime("%Y",$objp->dp)."</a></TD>\n";      
	  
	  print "<TD align=\"right\">".price($objp->price)."</TD>\n";
	  print "<TD align=\"center\">$objp->statut</TD>\n";
	  print "</TR>\n";
	  
	  $total = $total + $objp->price;
	  $subtotal = $subtotal + $objp->price;
	  
	  $i++;
	}
      
      
      print "</TABLE>";
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
