<?PHP
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
require("./pre.inc.php");

$user->getrights('facture');
$user->getrights('compta');

if (!$user->admin && !$user->rights->compta->charges)
  accessforbidden();

require("../../chargesociales.class.php");
require("../../paiement.class.php");
require("../bank/account.class.php");


llxHeader();


/* *************************************************************************** */
/*                                                                             */
/* Mode fiche                                                                  */
/*                                                                             */
/* *************************************************************************** */
if ($_GET["id"] > 0)
    {      
      $html = new Form($db);

      $cha = New ChargeSociales($db);
      if ( $cha->fetch($_GET["id"]) > 0)
	{	  
	  /*
	   *   Charge
	   */
	  print_titre("Charge sociale : ".$cha->id);
      print "<br>";

	  /*
	   * Confirmation de la suppression de la facture
	   *
	   */
	  if ($_GET["action"] == 'delete')
	    {
	      $html->form_confirm("index.php?id=$cha->id&amp;action=del","Supprimer la charge sociale","Etes-vous sûr de vouloir supprimer cette charge sociale ?","confirm_delete");
	    }

      print "<form action=\"$PHP_SELF?id=$cha->id&amp;action=update\" method=\"post\">";

	  print '<table class="border" cellspacing="0" cellpadding="2" width="100%">';
	  print "<tr><td>Type</td><td colspan=\"3\">$cha->type_libelle</td></tr>";
	  print "<tr><td>Période</td><td colspan=\"3\">NA</td></tr>";
      if ($cha->paye==0) {
          print '<tr><td>Libellé</td><td colspan=\3\"><input type="text" name="desc" size="60" value='.stripslashes($cha->lib).'></td></tr>';
    	  print "<tr><td>Date d'échéance</td><td><input type=\"text\" name=\"amount\" value=\"".strftime("%Y%m%d",$cha->date_ech)."\"></td><td>Date de paiement</td><td>NA</td>";
    	  print "<tr><td>Montant</td><td><b><input type=\"text\" name=\"amount\" value=\"$cha->amount\"></b></td><td colspan=\"2\">&nbsp;</td></tr>";
        }
      else {
          print '<tr><td>Libellé</td><td colspan=\3\">'.stripslashes($cha->lib).'</td></tr>';
    	  print "<tr><td>Date d'échéance</td><td>".strftime("%Y%m%d",$cha->date_ech)."</td><td>Date de paiement</td><td>".strftime("%Y%m%d",$cha->date_pai)."</td>";
    	  print "<tr><td>Montant</td><td><b>$cha->amount</b></td><td colspan=\"2\">&nbsp;</td></tr>";
      }
	  print "<tr><td>Statut</td><td>".($cha->paye==0?"Non paye":"Payé")."</td><td colspan=\"2\">&nbsp;</td></tr>";
      print "</table>";
    
    
     print "</form>\n";

        

    if (! $_GET["action"]) {

	  /*
	   *   Boutons actions
	   */

	    print "<br><div class=\"tabsAction\">\n";

	    // Supprimer
	    if ($cha->paye == 0 && $user->rights->facture->supprimer)
	      {
		print "<a class=\"tabAction\" href=\"$PHP_SELF?id=$cha->id&amp;action=delete\">Supprimer</a>";
	      } 

	    // Emettre paiement 
	    if ($cha->paye == 0 && $user->rights->facture->paiement)
	      {
		print "<a class=\"tabAction\" href=\"$PHP_SELF?id=$cha->id&amp;action=create\">Emettre paiement</a>";
	      }
	    
	    // Classer 'payé'
	    if ($cha->paye == 0 && $user->rights->facture->paiement)
	      {
		print "<a class=\"tabAction\" href=\"$PHP_SELF?id=$cha->id&amp;action=payed\">Classer 'Payée'</a>";
	      }
	    
	    print "</div>";
    }

	if ($_GET["action"] == 'create')
	  {
    	print "Cette fonction n'a pas encore été implémentée";

	  }
	if ($_GET["action"] == 'payed')
	  {
    	print "Cette fonction n'a pas encore été implémentée";

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
