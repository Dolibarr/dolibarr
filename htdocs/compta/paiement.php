<?PHP
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
include_once("./pre.inc.php");
include_once("../paiement.class.php");
include_once(DOL_DOCUMENT_ROOT."/facture.class.php");
include_once(DOL_DOCUMENT_ROOT."/compta/bank/account.class.php");
/*
 *
 */

if ($HTTP_POST_VARS["action"] == 'add_paiement')
{

  if ($HTTP_POST_VARS["paiementid"] > 0)
    {

      $datepaye = $db->idate(mktime(12, 0 , 0,
				    $HTTP_POST_VARS["remonth"], 
				    $HTTP_POST_VARS["reday"],
				    $HTTP_POST_VARS["reyear"]));

      $paiement_id = 0;
      $amounts = array();
      foreach ($HTTP_POST_VARS as $key => $value)
	{
	  if (substr($key,0,7) == 'amount_')
	    {
	      $other_facid = substr($key,7);
	  
	      $amounts[$other_facid] = $HTTP_POST_VARS[$key];
	    }
	}

      $paiement = new Paiement($db);

      $paiement->datepaye     = $datepaye;
      $paiement->amounts      = $amounts;
      $paiement->author       = $HTTP_POST_VARS["author"];
      $paiement->paiementid   = $HTTP_POST_VARS["paiementid"];
      $paiement->num_paiement = $HTTP_POST_VARS["num_paiement"];
      $paiement->note         = $HTTP_POST_VARS["note"];
      $paiement_id = $paiement->create($user);

      if ($paiement_id > 0)
	{
	  $paiement = new Paiement($db);
	  if ( $paiement->fetch($paiement_id) )
	    {
	      $fac = new Facture($db);
	      $fac->fetch($HTTP_POST_VARS["facid"]);
	      $fac->fetch_client();
	  
	      $label = "Règlement facture";
	  
	      //On ajoute une ligne dans la table llx_bank pour qu'ensuite on puisse rapprocher le compte !
	      $acc = new Account($db, $HTTP_POST_VARS["accountid"]);
	      //paiementid est correct, il contient "CHQ ou VIR par exemple"
	      $bank_line_id = $acc->addline($datepaye, $HTTP_POST_VARS["paiementid"], $label, $paiement->montant, $num_paiement);
	  
	      // TODO
	      // Mise a jour fk_bank dans llx_paiement
	      // $acc->update_fk_bank($paiement_id,$bank_line_id);
	  
	      $acc->add_url_line($bank_line_id, $paiement_id, DOL_URL_ROOT.'/compta/paiement/fiche.php?id=', "(paiement)");
	      $acc->add_url_line($bank_line_id, $fac->client->id, DOL_URL_ROOT.'/compta/fiche.php?socid=', $fac->client->nom);	  

	      $loc = DOL_URL_ROOT.'/compta/paiement/fiche.php?id='.$paiement_id;
	      Header("Location: $loc");
	    }
	}
      else
	{
	  //      print "Pas d'entrée dans la banque";
	}
    }
  else
    {
      $fiche_erreur_message = "Vous devez sélectionner un mode de paiement";
    }
}

/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $action = '';
  $socidp = $user->societe_id;
}

/*
 * Affichage
 */

llxHeader();

if ($_GET["action"] == 'create') 
{
  $facture = new Facture($db);
  $facture->fetch($_GET["facid"]);

  $sql = "SELECT s.nom,s.idp, f.amount, f.total_ttc as total, f.facnumber";
  $sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."facture as f WHERE f.fk_soc = s.idp";
  $sql .= " AND f.rowid = $facid";

  $result = $db->query($sql);
  if ($result)
    {
      $num = $db->num_rows();
      if ($num)
	{
	  $obj = $db->fetch_object( 0);

	  $total = $obj->total;

	  print_titre("Emettre un paiement");

	  print '<form action="paiement.php?action=create&facid='.$facid.'" method="post">';
	  print '<input type="hidden" name="action" value="add_paiement">';
	  print '<table cellspacing="0" class="border" width="100%" cellpadding="3">';
	  
	  print "<tr><td>Société :</td><td colspan=\"2\">$obj->nom</td></tr>\n";
	  
	  print "<input type=\"hidden\" name=\"facid\" value=\"$facid\">";
	  print "<input type=\"hidden\" name=\"facnumber\" value=\"$obj->facnumber\">";
	  print "<input type=\"hidden\" name=\"socid\" value=\"$obj->idp\">";
	  print "<input type=\"hidden\" name=\"societe\" value=\"$obj->nom\">";
	  
	  print "<tr><td>Date :</td><td>";
	  print_date_select();
	  print "</td>";
	  print "<td>Commentaires</td></tr>";
	  
	  print "<tr><td>Type :</td><td><select name=\"paiementid\">\n";
	  
	  $sql = "SELECT id, libelle FROM ".MAIN_DB_PREFIX."c_paiement ORDER BY id";
	  
	  $result = $db->query($sql);
	  if ($result)
	    {
	      $num = $db->num_rows();
	      $i = 0; 
	      while ($i < $num)
		{
		  $objopt = $db->fetch_object( $i);
		  print "<option value=\"$objopt->id\">$objopt->libelle</option>\n";
		  $i++;
		}
	    }
	  print "</select>";
	  print "</td>\n";
	  print '<td rowspan="3" valign="top">';
	  print '<textarea name="comment" wrap="soft" cols="40" rows="6"></textarea></td></tr>';	  
	  print "<tr><td>Compte à créditer :</td><td><select name=\"accountid\">\n";
	  
	  $sql = "SELECT rowid, label FROM ".MAIN_DB_PREFIX."bank_account ORDER BY rowid";
	  
	  $result = $db->query($sql);
	  if ($result)
	    {
	      $num = $db->num_rows();
	      $i = 0; 
	      while ($i < $num)
		{
		  $objopt = $db->fetch_object( $i);
		  print '<option value="'.$objopt->rowid.'"';
		  if (defined("FACTURE_RIB_NUMBER") && FACTURE_RIB_NUMBER == $objopt->rowid)
		    {
		      print ' SELECTED';
		    }
		  print '>'.$objopt->label.'</option>';
		  $i++;
		}
	    }
	  print "</select>";
	  print "</td></tr>\n";
	  	  
	  print "<tr><td>Numéro :</td><td><input name=\"num_paiement\" type=\"text\"><br><em>Numéro du chèque / virement</em></td></tr>\n";

	  /*
	   * Autres factures impayées
	   */

	  $sql = "SELECT f.rowid as facid,f.facnumber,f.total_ttc,".$db->pdate("f.datef")." as df";
	  $sql .= ", sum(pf.amount) as am";
	  $sql .= " FROM ".MAIN_DB_PREFIX."facture as f";
	  $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."paiement_facture as pf ON pf.fk_facture = f.rowid";
	  $sql .= " WHERE f.fk_soc = ".$facture->socidp;
	  $sql .= " AND f.paye = 0";
	  $sql .= " AND f.fk_statut > 0";// AND f.rowid <>".$_GET["facid"];
	  $sql .= " GROUP BY f.facnumber";  

	  if ($db->query($sql))
	    {
	      $num = $db->num_rows();
	      
	      if ($num > 0)
		{
		  $i = 0;
		  print '<tr><td colspan="3">';
		  print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
		  print '<tr class="liste_titre">';
		  print '<td>Facture</td><td align="center">Date</td>';
		  print '<td align="right">Montant TTC</td>';	      
		  print '<td align="right">Reçu</TD>';
		  print '<td align="right">Reste à payer</td>';
		  print '<td align="center">Montant</td>';
		  print "</TR>\n";
	      
		  $var=True;
		  $total=0;
		  $totalrecu=0;
		  
		  while ($i < $num)
		    {
		      $objp = $db->fetch_object($i);
		      $var=!$var;
		      
		      print "<tr $bc[$var]>";
		  
		      print '<td><a href="facture.php?facid='.$objp->facid.'">' . $objp->facnumber;
		      print "</a></TD>\n";
		      
		      if ($objp->df > 0 )
			{
			  print "<td align=\"center\">";
			  print strftime("%d %b %Y",$objp->df)."</td>\n";
			}
		      else
			{
			  print "<td align=\"center\"><b>!!!</b></TD>\n";
			}
		      
		      print '<td align="right">'.price($objp->total_ttc)."</TD>";
		      print '<td align="right">'.price($objp->am)."</td>";
		      print '<td align="right">'.price($objp->total_ttc - $objp->am)."</td>";
		      
		      print '<td align="center">';

		      if ($objp->total_ttc <> $objp->am)
			{
			  $namef = "amount_".$objp->facid;
			  print '<input type="text" size="8" name="'.$namef.'">';
			}
		      else
			{
			  print '-';
			}
		      print "</td></tr>\n";
		      $total+=$objp->total;
		      $total_ttc+=$objp->total_ttc;
		      $totalrecu+=$objp->am;
		      $i++;
		    }
		  if ($i > 1)
		    {
		      // Print total
		      print "<tr ".$bc[!$var].">";
		      print '<td colspan="2" align="left">Total :</td>';
		      print "<td align=\"right\"><b>".price($total_ttc)."</b></td>";
		      print "<td align=\"right\"><b>".price($totalrecu)."</b></td>";
		      print "<td align=\"right\"><b>".price($total_ttc - $totalrecu)."</b></td>";
		      print '<td align="center">&nbsp;</td>';
		      print "</tr>\n";
		    }
		  print "</table></td></tr>\n";
		}
	      $db->free();	
	    }
	  else
	    {
	      print $sql ."<br>".$db->error();
	    }
	  /*
	   *
	   */

	  if ($fiche_erreur_message)
	    {
	      print '<tr><td colspan="3" align="center">'.$fiche_erreur_message.'</td></tr>';
	    }

	  print '<tr><td colspan="3" align="center"><input type="submit" value="Enregistrer"></td></tr>';
	  print "</table>";
	  print "</form>\n";	  
	}
    }
} 

if ($action == '') {
  
  if ($page == -1)
    $page = 0 ;
  
  $limit = $conf->liste_limit;
  $offset = $limit * $page ;

  if ($sortorder == "")
    $sortorder="DESC";
  
  if ($sortfield == "")
    $sortfield="p.datep";
  
  $sql = "SELECT ".$db->pdate("p.datep")." as dp, p.amount, f.amount as fa_amount, f.facnumber";
  $sql .=", f.rowid as facid, c.libelle as paiement_type, p.num_paiement";
  $sql .= " FROM ".MAIN_DB_PREFIX."paiement as p, ".MAIN_DB_PREFIX."facture as f, ".MAIN_DB_PREFIX."c_paiement as c";
  $sql .= " WHERE p.fk_facture = f.rowid AND p.fk_paiement = c.id";
  
  if ($socidp)
    {
      $sql .= " AND f.fk_soc = $socidp";
    }
  
  $sql .= " ORDER BY $sortfield $sortorder";
  $sql .= $db->plimit( $limit +1 ,$offset);
  $result = $db->query($sql);
  
  if ($result)
    {
      $num = $db->num_rows();
      $i = 0; 
      $var=True;
      
      print_barre_liste("Paiements", $page, $PHP_SELF,"",$sortfield,$sortorder,'',$num);

      print '<TABLE border="0" width="100%" cellspacing="0" cellpadding="4">';
      print '<TR class="liste_titre">';
      print "<td>Facture</td>";
      print "<td>Date</td>";
      print "<td>";
      print_liste_field_titre("Type",$PHP_SELF,"c.libelle","","");
      print '</td><td align="right">Montant</TD>';
      print "<td>&nbsp;</td>";
      print "</TR>\n";
      
      while ($i < min($num,$limit))
	{
	  $objp = $db->fetch_object( $i);
	  $var=!$var;
	  print "<TR $bc[$var]>";
	  print "<TD><a href=\"facture.php?facid=$objp->facid\">$objp->facnumber</a></TD>\n";
	  print "<TD>".strftime("%d %B %Y",$objp->dp)."</TD>\n";
	  print "<TD>$objp->paiement_type $objp->num_paiement</TD>\n";
	  print '<TD align="right">'.price($objp->amount).'</TD><td>&nbsp;</td>';	
	  print "</tr>";
	  $i++;
	}
      print "</table>";
    }
  print '<a href="paiement/index.php">Rapports</a>';
  
}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
