<?PHP
/* Copyright (C) 2004 Laurent Destailleur  <eldy@users.sourceforge.net>
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
include_once("../chargesociales.class.php");
include_once(DOL_DOCUMENT_ROOT."/compta/bank/account.class.php");
/*
 *
 */

$chid=isset($_GET["id"])?$_GET["id"]:$_POST["id"];


if ($_POST["action"] == 'add_paiement')
{
  if ($_POST["paiementtype"] > 0)
    {

      $datepaye = $db->idate(mktime(12, 0 , 0,
				    $_POST["remonth"], 
				    $_POST["reday"],
				    $_POST["reyear"]));

      $paiement_id = 0;
      $amounts = array();
      foreach ($_POST as $key => $value)
	{
	  if (substr($key,0,7) == 'amount_')
	    {
	      $other_chid = substr($key,7);
	  
	      $amounts[$other_chid] = $_POST[$key];
	    }
	}

      // TODO Mettre toute la chaine dans une même transaction

      // Creation de la ligne paiement
      $paiement = new PaiementCharge($db);
      $paiement->chid         = $chid;
      $paiement->datepaye     = $datepaye;
      $paiement->amounts      = $amounts;   // Tableau de montant
      $paiement->paiementtype = $_POST["paiementtype"];
      $paiement->num_paiement = $_POST["num_paiement"];
      $paiement->note         = $_POST["note"];
      $paiement_id = $paiement->create($user);

      if ($paiement_id > 0)
	{
        // On determine le montant total du paiement
        $total=0;
        foreach ($paiement->amounts as $key => $value)
        {
            $chid = $key;
            $value = trim($value);
            $amount = round(ereg_replace(",",".",$value), 2);
            if (is_numeric($amount))
              {
            $total += $amount;
              }
        }
        
        // Insertion dans llx_bank
        $label = "Règlement charge";
        $acc = new Account($db, $_POST["accountid"]);
        $bank_line_id = $acc->addline($paiement->datepaye, $paiement->paiementtype, $label, -abs($total), $paiement->num_paiement, '', $user);
	  
        // Mise a jour fk_bank dans llx_paiementcharge. On connait ainsi le paiement qui a généré l'écriture bancaire
        if ($bank_line_id) {
            $paiement->update_fk_bank($bank_line_id);
        }
	  
        // Mise a jour liens (pour chaque charge concernée par le paiement)
        //foreach ($paiement->amounts as $key => $value)
	    //{
        //    $chid = $key;
        //    $fac = new Facture($db);
        //    $fac->fetch($chid);
        //    $fac->fetch_client();
        //    $acc->add_url_line($bank_line_id, $paiement_id, DOL_URL_ROOT.'/compta/paiement/fiche.php?id=', "(paiement)");
        //    $acc->add_url_line($bank_line_id, $fac->client->id, DOL_URL_ROOT.'/compta/fiche.php?socid=', $fac->client->nom);
	    //}
	  
        $loc = DOL_URL_ROOT.'/compta/sociales/charges.php?id='.$chid;
        Header("Location: $loc");
	}
      else
	{
        // Il y a eu erreur
        $fiche_erreur_message = "Echec de la création du paiement: ".$db->error();
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

if ($fiche_erreur_message)
{
  print "<div class=\"error\">$fiche_erreur_message</div><br>";
}


/*
 * Formulaire de creation d'un paiement de charge
 */
if ($_GET["action"] == 'create') 
{

      $charge = new ChargeSociales($db);
      $charge->fetch($chid);

	  $total = $charge->amount;

	  print_titre("Emettre un paiement d'une charge");
      print "<br>\n";

	  print '<form action="paiement_charge.php" method="post">';
	  print "<input type=\"hidden\" name=\"id\" value=\"$charge->id\">";
	  print '<input type="hidden" name="action" value="add_paiement">';
	  print '<table cellspacing="0" class="border" width="100%" cellpadding="2">';

      print "<tr class=\"liste_titre\"><td colspan=\"3\">Charge</td>";

      print '<tr><td>Numéro :</td><td colspan="2">';
      print '<a href="charges.php?id='.$chid.'">'.$chid.'</a></td></tr>';
	  print "<tr><td>Type charge :</td><td colspan=\"2\">$charge->type_libelle</td></tr>\n";
	  print "<tr><td>Période :</td><td colspan=\"2\">$charge->periode</td></tr>\n";
	  print '<tr><td>'.$langs->trans("Label").' :</td><td colspan="2">'.$charge->lib."</td></tr>\n";
	  print "<tr><td>Date échéance :</td><td colspan=\"2\">".dolibarr_print_date($charge->date_ech)."</td></tr>\n";

      print "<tr><td>Montant TTC:</td><td colspan=\"2\">".price($charge->amount)." euros</td></tr>";

      $sql = "SELECT sum(p.amount) FROM ".MAIN_DB_PREFIX."paiementcharge as p WHERE p.fk_charge = $chid;";
      $result = $db->query($sql);
      if ($result) {
	    $sumpayed = $db->result(0,0);
	    $db->free();
      }
      print '<tr><td>Déjà payé TTC</td><td colspan="2"><b>'.price($sumpayed).'</b> euros</td></tr>';

      print "<tr class=\"liste_titre\"><td colspan=\"3\">Paiement</td>";

	  print "<input type=\"hidden\" name=\"chid\" value=\"$chid\">";
	  
	  print "<tr><td>Date :</td><td>";
	  print_date_select();
	  print "</td>";
	  print '<td>'.$langs->trans("Comments").'</td></tr>';
	  
	  print "<tr><td>Type du paiement :</td><td><select name=\"paiementtype\">\n";
	  
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

	  print '<td rowspan="4" valign="top"><textarea name="comment" wrap="soft" cols="40" rows="6"></textarea></td></tr>';	  

	  print "<tr><td>Numéro :</td><td><input name=\"num_paiement\" type=\"text\"><br><em>Numéro du chèque / virement</em></td></tr>\n";

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
		      print ' selected';
		    }
		  print '>'.$objopt->label.'</option>';
		  $i++;
		}
	    }
	  print "</select>";
	  print "</td></tr>\n";

      print "<tr><td valign=\"top\">Reste à payer :</td><td><b>".price($total - $sumpayed)."</b> euros TTC</td></tr>\n";
//      print "<tr><td valign=\"top\">Montant :</td><td><input name=\"amount\" type=\"text\"></td></tr>\n";
	  	  
	  /*
	   * Autres charges impayées
	   */
//	  $sql = "SELECT f.rowid as facid,f.facnumber,f.total_ttc,".$db->pdate("f.datef")." as df";
//	  $sql .= ", sum(pf.amount) as am";
//	  $sql .= " FROM ".MAIN_DB_PREFIX."facture as f";
//	  $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."paiement_facture as pf ON pf.fk_facture = f.rowid";
//	  $sql .= " WHERE f.fk_soc = ".$facture->socidp;
//	  $sql .= " AND f.paye = 0";
//	  $sql .= " AND f.fk_statut = 1";  // Statut=0 => non validée, Statut=2 => annulée
//	  $sql .= " GROUP BY f.facnumber";  
//
//	  if ($db->query($sql))
//	    {
//	      $num = $db->num_rows();
//	      
//	      if ($num > 0)
//		{
		  $num = 1;
		  $i = 0;
		  print '<tr><td colspan="3">';
		  print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
		  print '<tr class="liste_titre">';
		  print '<td>Charge</td><td align="center">Date échéance</td>';
		  print '<td align="right">Montant TTC</td>';	      
		  print '<td align="right">Déjà payé TTC</td>';
		  print '<td align="right">Reste à payer TTC</td>';
		  print '<td align="right">Montant</td>';
		  print "</tr>\n";
	      
		  $var=True;
		  $total=0;
		  $totalrecu=0;
		  
		  while ($i < $num)
		    {
		      //$objp = $db->fetch_object($i);
		      $objp = $charge;
		      
		      $var=!$var;
		      
		      print "<tr $bc[$var]>";
		  
		      print '<td><a href="sociales/charge.php?id='.$objp->id.'">' . $objp->id;
		      print "</a></td>\n";
		      
		      if ($objp->date_ech > 0 )
			{
			  print "<td align=\"center\">".dolibarr_print_date($objp->date_ech)."</td>\n";
			}
		      else
			{
			  print "<td align=\"center\"><b>!!!</b></td>\n";
			}
		      
		      print '<td align="right">'.price($objp->amount)."</td>";

		      print '<td align="right">'.price($sumpayed)."</td>";
		      
		      print '<td align="right">'.price($objp->amount-$sumpayed)."</td>";

		      print '<td align="right">';
		      if ($sumpayed < $objp->amount)
			{
			  $namef = "amount_".$objp->id;
			  print '<input type="text" size="8" name="'.$namef.'">';
			}
		      else
			{
			  print '-';
			}
		      print "</td>";

		      print "</tr>\n";
		      $total+=$objp->total;
		      $total_ttc+=$objp->total_ttc;
		      $totalrecu+=$objp->am;
		      $i++;
		    }
		  if ($i > 1)
		    {
		      // Print total
		      print "<tr ".$bc[!$var].">";
		      print '<td colspan="2" align="left">'.$langs->trans("TotalTTC").':</td>';
		      print "<td align=\"right\"><b>".price($total_ttc)."</b></td>";
		      print "<td align=\"right\"><b>".price($totalrecu)."</b></td>";
		      print "<td align=\"right\"><b>".price($total_ttc - $totalrecu)."</b></td>";
		      print '<td align="center">&nbsp;</td>';
		      print "</tr>\n";
		    }
		  print "</table></td></tr>\n";
//		}
//	      $db->free();	
//	    }
//	  else
//	    {
//	      print $sql ."<br>".$db->error();
//	    }
	  /*
	   *
	   */

	  print '<tr><td colspan="3" align="center"><input type="submit" value="'.$langs->trans("Save").'"></td></tr>';
	  print "</table>";
	  print "</form>\n";	  
//    }
} 


$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
