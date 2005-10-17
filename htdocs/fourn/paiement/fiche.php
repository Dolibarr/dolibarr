<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

/**	    \file       htdocs/compta/paiement/fiche.php
		\ingroup    facture
		\brief      Onglet paiement d'un paiement
		\version    $Revision$
*/


require("./pre.inc.php");

require(DOL_DOCUMENT_ROOT."/fourn/facture/paiementfourn.class.php");

$user->getrights('facture');

$langs->load("bills");
$langs->load("banks");
$langs->load("companies");


/*
 * Visualisation de la fiche
 *
 */

llxHeader();
$h=0;

$head[$h][0] = DOL_URL_ROOT.'/fourn/paiement/fiche.php?id='.$_GET["id"];
$head[$h][1] = $langs->trans("Card");
$hselected = $h;
$h++;      

$head[$h][0] = DOL_URL_ROOT.'/fourn/paiement/info.php?id='.$_GET["id"];
$head[$h][1] = $langs->trans("Info");
$h++;      

dolibarr_fiche_head($head, $hselected, $langs->trans("Payment").": ".$_GET["id"]);


$facture = new FactureFournisseur($db);
$paiement = new PaiementFourn($db);

if ($paiement->fetch($_GET["id"], $user) == 0)
{
  $html = new Form($db);

  $facture->fetch($paiement->facture_id);

  /*
   * Confirmation de la suppression du paiement
   *
   */
  if ($_GET["action"] == 'delete')
    {
      $html->form_confirm("fiche.php?id=$paiement->id","Supprimer le paiement","Etes-vous sûr de vouloir supprimer ce paiement ?","confirm_delete");
      print '<br>';
      
    }
  
  if ($_GET["action"] == 'valide')
    {
      $html->form_confirm("fiche.php?id=$paiement->id","Valider le paiement","Etes-vous sûr de vouloir valider ce paiment, auncune modification n'est possible une fois le paiement validé ?","confirm_valide");
      print '<br>';
      
    }
  
  print '<table class="noborder" width="100%">';
  
  print '<tr><td valign="top">';
  
  if ($paiement->bank_account) {
    // Si compte renseigné, on affiche libelle
    $bank=new Account($db);
    $bank->fetch($paiement->bank_account);
    print $langs->trans("BankAccount").' : '.$bank->label.'<br>';
  }
  print $langs->trans("Date").' : '.dolibarr_print_date($paiement->date)."<br>";
  print $langs->trans("Bill").' : <a href="../facture/fiche.php?facid='.$facture->id.'">'.$facture->ref."</a><br>";
  print $langs->trans("Type").' : '.$paiement->type_libelle."<br>";
  if ($paiement->numero) { print $langs->trans("Numero").' : '.$paiement->numero."<br>"; }
  print $langs->trans("Amount").' : '.$paiement->montant."&nbsp;".$conf->monnaie."<br>";
  print '</td></tr>';
  print "</table>";
  
  print nl2br($paiement->note);
  
  
  /*
   *
   *
   
print "<br></div>";

print '<div class="tabsAction">';

if ($user->societe_id == 0 && $paiement->statut == 0 && $_GET["action"] == '')
{
  print '<a class="tabAction" href="fiche.php?id='.$_GET["id"].'&amp;action=valide">'.$langs->trans("Valid").'</a>';
}


if ($user->societe_id == 0 && $allow_delete && $paiement->statut == 0 && $_GET["action"] == '')
{
  print '<a class="butDelete" href="fiche.php?id='.$_GET["id"].'&amp;action=delete">'.$langs->trans("Delete").'</a>';
  
}
print "</div>";      

*/

}
else
{
  print "Erreur de lecture";
}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
