<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
	    \file       htdocs/compta/paiement/fiche.php
		\ingroup    facture
		\brief      Onglet paiement d'un paiement
		\version    $Revision$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/paiement.class.php");
require_once(DOL_DOCUMENT_ROOT."/facture.class.php");

$user->getrights('facture');

$langs->load("bills");
$langs->load("banks");
$langs->load("companies");



if ($_POST["action"] == 'confirm_delete' && $_POST["confirm"] == 'yes' && $user->rights->facture->creer)
{
  $paiement = new Paiement($db);
  $paiement->id = $_GET["id"];
  if ( $paiement->delete() )
    {
      Header("Location: liste.php");
    }
}

if ($_POST["action"] == 'confirm_valide' && $_POST["confirm"] == 'yes' && $user->rights->facture->creer)
{
  $paiement = new Paiement($db);
  $paiement->id = $_GET["id"];
  if ( $paiement->valide() == 0 )
    {
      Header("Location: fiche.php?id=".$paiement->id);
    }
}


/*
 *
 *
 */

llxHeader();


print '<div class="tabs">';
print '<a href="fiche.php?id='.$_GET["id"].'" id="active" class="tab">'.$langs->trans("Payment").'</a>';
print '<a class="tab" href="info.php?id='.$_GET["id"].'">'.$langs->trans("Info").'</a>';
print '</div>';

print '<div class="tabBar">';
print '<br>';


/*
 * Visualisation de la fiche
 *
 */

$paiement = new Paiement($db);
$paiement->fetch($_GET["id"]);
$html = new Form($db);

/*
 * Confirmation de la suppression du paiement
 */
if ($_GET["action"] == 'delete')
{
    $html->form_confirm("fiche.php?id=$paiement->id","Supprimer le paiement","Etes-vous sûr de vouloir supprimer ce paiement ?","confirm_delete");
    print '<br>';
}

/*
 * Confirmation de la validation du paiement
 */
if ($_GET["action"] == 'valide')
{
    $html->form_confirm("fiche.php?id=$paiement->id","Valider le paiement","Etes-vous sûr de vouloir valider ce paiment, auncune modification n'est possible une fois le paiement validé ?","confirm_valide");
    print '<br>';
}

print '<table class="border" width="100%">';

print '<tr><td valign="top" width="140">'.$langs->trans("Ref").'</td><td>'.$paiement->id.'</td></tr>';
if ($paiement->bank_account) {
    // Si compte renseigné, on affiche libelle
    print '<tr><td valign="top" width="140">';
    $bank=new Account($db);
    $bank->fetch($paiement->bank_account);
    print $langs->trans("BankAccount").'</td><td><a href="'.DOL_URL_ROOT.'/compta/bank/account.php?account='.$bank->id.'">'.$bank->label.'</a></td></tr>';
}
print '<tr><td valign="top" width="140">'.$langs->trans("Date").'</td><td>'.dolibarr_print_date($paiement->date).'</td></tr>';
print '<tr><td valign="top">'.$langs->trans("Type").'</td><td>'.$paiement->type_libelle.'</td></tr>';
if ($paiement->numero) { print '<tr><td valign="top">'.$langs->trans("Numero").'</td><td>'.$paiement->numero.'</td></tr>'; }
print '<tr><td valign="top">'.$langs->trans("Amount").'</td><td>'.$paiement->montant."&nbsp;".$langs->trans("Currency".$conf->monnaie).'</td></tr>';
print '<tr><td valign="top">'.$langs->trans("Note").'</td><td>'.nl2br($paiement->note).'</td></tr>';
print "</table>";


/*
 *
 *
 */
$allow_delete = 1 ;
$sql = "SELECT f.facnumber, f.total_ttc, pf.amount, f.rowid as facid, f.paye, f.fk_statut, s.nom, s.idp";
$sql .= " FROM ".MAIN_DB_PREFIX."paiement_facture as pf,".MAIN_DB_PREFIX."facture as f,".MAIN_DB_PREFIX."societe as s";
$sql .= " WHERE pf.fk_facture = f.rowid AND f.fk_soc = s.idp";
$sql .= " AND pf.fk_paiement = ".$paiement->id;
 			
if ($db->query($sql))
{
  $num = $db->num_rows();
  
  $i = 0;
  $total = 0;
  print '<br><table class="noborder" width="100%">';
  print '<tr class="liste_titre">';
  print '<td>'.$langs->trans("Bill").'</td><td>'.$langs->trans("Company").'</td>';
  print '<td align="right">'.$langs->trans("AmountTTC").'</td><td align="center">'.$langs->trans("Status").'</td>';
  print "</tr>\n";
  
  if ($num > 0) 
    {
      $var=True;
      
      while ($i < $num)
	{
	  $objp = $db->fetch_object();
	  $var=!$var;
	  
	  print "<tr $bc[$var]>";
	  print '<td><a href="'.DOL_URL_ROOT.'/compta/facture.php?facid='.$objp->facid.'">'.img_object($langs->trans("ShowBill"),"bill").' ';
	  print $objp->facnumber;
	  print "</a></td>\n";
	  print '<td><a href="'.DOL_URL_ROOT.'/compta/fiche.php?socid='.$objp->idp.'">'.img_object($langs->trans("ShowCompany"),"company").' '.$objp->nom.'</a></td>';
	  print '<td align="right">'.price($objp->amount).'</td>';
	  $fac=new Facture($db);
	  print '<td align="center">'.$fac->LibStatut($objp->paye,$objp->fk_statut).'</td>';
	  print "</tr>\n";
	  if ($objp->paye == 1)
	    {
	      $allow_delete = 0;
	    }
	  $total = $total + $objp->amount;
	  $i++;
	}		        
    }
  $var=!$var;

  print "</table>\n";
  $db->free();	
}
else {
    dolibarr_print_error($db);   
}

print "</div>";


/*
 * Boutons Actions
 */

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

$db->close();

llxFooter('$Date$ - $Revision$');
?>
