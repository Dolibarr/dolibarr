<?php
/* Copyright (C) 2004      Laurent Destailleur  <eldy@users.sourceforge.net>
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

/*!	\file htdocs/compta/sociales/charges.php
		\ingroup    compta
		\brief      Fiche d'une charge sociale
		\version    $Revision$
*/

require("./pre.inc.php");

$langs->load("compta");
$langs->load("bills");


$user->getrights('compta');

if (!$user->admin && !$user->rights->compta->charges)
  accessforbidden();

require("../../chargesociales.class.php");
require("../bank/account.class.php");

llxHeader();


$chid=isset($_GET["id"])?$_GET["id"]:$_POST["id"];


/* *************************************************************************** */
/*                                                                             */
/* Action Classer Payé                                                         */
/*                                                                             */
/* *************************************************************************** */
if ($_GET["action"] == 'payed')
{
  $cha = new ChargeSociales($db);
  $result = $cha->set_payed($chid);
}
	


/* *************************************************************************** */
/*                                                                             */
/* Mode fiche                                                                  */
/*                                                                             */
/* *************************************************************************** */
if ($chid > 0)
    {      
      $html = new Form($db);

      $cha = new ChargeSociales($db);

      if ( $cha->fetch($chid) > 0)
	{	  
	  /*
	   *   Charge
	   */

	  //$head[0][0] = DOL_URL_ROOT.'/comm/propal.php?propalid='.$propal->id;
	  $head[0][1] = "Charge sociale : No $cha->id";
	  $h = 1;
	  $a = 0;

	  dolibarr_fiche_head($head, $a);
  	  
	  /*
	   * Confirmation de la suppression de la charge
	   *
	   */
	  if ($_GET["action"] == 'delete')
	    {
	      $html->form_confirm("index.php?id=$cha->id&amp;action=del","Supprimer la charge sociale","Etes-vous sûr de vouloir supprimer cette charge sociale ?","confirm_delete");
	    }

      print "<form action=\"charges.php?id=$cha->id&amp;action=update\" method=\"post\">";

	  print '<table class="border" width="100%">';

	  print "<tr><td>".$langs->trans("Type")."</td><td>$cha->type_libelle</td><td>".$langs->trans("Paiements")."</td></tr>";

	  print "<tr><td>".$langs->trans("Period")."</td><td>".dolibarr_print_date($cha->periode,"%Y")."</td>";
      print '<td rowspan="5" valign="top">';
	  	  
    	  /*
    	   * Paiements
    	   */
    	$sql = "SELECT ".$db->pdate("datep")." as dp, p.amount,";
    	$sql .= "c.libelle as paiement_type, p.num_paiement, p.rowid";
    	$sql .= " FROM ".MAIN_DB_PREFIX."paiementcharge as p, ".MAIN_DB_PREFIX."c_paiement as c ";
    	$sql .= " WHERE p.fk_charge = ".$chid." AND p.fk_typepaiement = c.id";
    	$sql .= " ORDER BY dp DESC";
    	
    	$result = $db->query($sql);
    	if ($result)
    	  {
    	    $num = $db->num_rows();
    	    $i = 0; $total = 0;
    	    echo '<table class="noborder" width="100%" cellspacing="0" cellpadding="3">';
    	    print '<tr class="liste_titre"><td>'.$langs->trans("Date").'</td><td>'.$langs->trans("Type").'</td>';
    	    print '<td align="right">'.$langs->trans("Amount").'</td><td>&nbsp;</td></tr>';
        
    	    $var=True;
    	    while ($i < $num)
    	      {
    		$objp = $db->fetch_object( $i);
    		$var=!$var;
    		print "<tr $bc[$var]><td>";
    		print dolibarr_print_date($objp->dp)."</td>\n";
    		print "<td>$objp->paiement_type $objp->num_paiement</td>\n";
    		print '<td align="right">'.price($objp->amount)."</td><td>".MAIN_MONNAIE."</td>\n";
    		print "</tr>";
    		$totalpaye += $objp->amount;
    		$i++;
    	      }
    
    	    if ($fac->paye == 0)
    	      {
    		print "<tr><td colspan=\"2\" align=\"right\">Total payé:</td><td align=\"right\"><b>".price($totalpaye)."</b></td><td>".MAIN_MONNAIE."</td></tr>\n";
    		print "<tr><td colspan=\"2\" align=\"right\">Réclamé :</td><td align=\"right\" bgcolor=\"#d0d0d0\">".price($cha->amount)."</td><td bgcolor=\"#d0d0d0\">".MAIN_MONNAIE."</td></tr>\n";
    		
    		$resteapayer = $cha->amount - $totalpaye;
    
    		print "<tr><td colspan=\"2\" align=\"right\">Reste à payer :</td>";
    		print "<td align=\"right\" bgcolor=\"#f0f0f0\"><b>".price($resteapayer)."</b></td><td bgcolor=\"#f0f0f0\">".MAIN_MONNAIE."</td></tr>\n";
    	      }
    	    print "</table>";
    	    $db->free();
    	  } else {
    	    dolibarr_print_error($db);
    	  }
    	  print "</td>";

	  print "</tr>";

      if ($cha->paye==0) {
          print '<tr><td>'.$langs->trans("Label").'</td><td><input type="text" name="desc" size="40" value="'.stripslashes($cha->lib).'"></td></tr>';
    	  print '<tr><td>'.$langs->trans("DateDue")."</td><td><input type=\"text\" name=\"amount\" value=\"".strftime("%Y%m%d",$cha->date_ech)."\"></td></tr>";
    	  print '<tr><td>'.$langs->trans("AmountTTC")."</td><td><b><input type=\"text\" name=\"amount\" value=\"$cha->amount\"></b></td></tr>";
        }
      else {
          print '<tr><td>'.$langs->trans("Label").'</td><td>'.$cha->lib.'</td></tr>';
    	  print "<tr><td>".$langs->trans("DateDue")."</td><td>".dolibarr_print_date($cha->date_ech)."</td></tr>";
    	  print '<tr><td>'.$langs->trans("AmountTTC").'</td><td><b>'.price($cha->amount).'</b></td></tr>';
      }


	  print '<tr><td>'.$langs->trans("Status").'</td><td>'.$cha->getLibStatut().'</td></tr>';
      print '</table>';
    
    
     print "</form>\n";

	print '<br></div>';

    if (! $_GET["action"]) {

	  /*
	   *   Boutons actions
	   */

	    print "<div class=\"tabsAction\">\n";

	    // Supprimer
	    if ($cha->paye == 0 && $totalpaye <=0 && $user->rights->compta->charges)
	      {
		print "<a class=\"tabAction\" href=\"charges.php?id=$cha->id&amp;action=delete\">".$langs->trans("Delete")."</a>";
	      } 

	    // Emettre paiement 
	    if ($cha->paye == 0 && round($resteapayer) > 0 && $user->rights->compta->charges)
	      {
		print "<a class=\"tabAction\" href=\"../paiement_charge.php?id=$cha->id&amp;action=create\">".$langs->trans("DoPaiement")."</a>";
	      }
	    
	    // Classer 'payé'
	    if ($cha->paye == 0 && round($resteapayer) <=0 && $user->rights->compta->charges)
	      {
		print "<a class=\"tabAction\" href=\"charges.php?id=$cha->id&amp;action=payed\">".$langs->trans("ClassifyPayed")."</a>";
	      }
	    
	    print "</div>";
    }

  }
  else
  {
  	/* Charge non trouvée */
	print "Charge inexistante ou accés refusé";
  }
}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
