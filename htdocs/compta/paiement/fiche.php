<?php
/* Copyright (C) 2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004 Laurent Destailleur  <eldy@users.sourceforge.net>
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

/*!	\file htdocs/compta/paiement/fiche.php
		\ingroup    facture
		\brief      Onglet paiement d'un paiement
		\version    $Revision$
*/


require("./pre.inc.php");
require("../../paiement.class.php");

$user->getrights('facture');

$langs->load("bills");
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
/*
 * Visualisation de la fiche
 *
 */

$paiement = new Paiement($db);
$paiement->fetch($_GET["id"]);
$html = new Form($db);

/*
 * Confirmation de la suppression de la facture
 *
 */
 if ($_GET["action"] == 'delete')
   {
	 print '<br>';
     $html->form_confirm("fiche.php?id=$paiement->id","Supprimer le paiement","Etes-vous sûr de vouloir supprimer ce paiement ?","confirm_delete");
   }
 

print '<table class="noborder" width="100%">';

print '<tr><td valign="top">';

print $langs->trans("Numero").' : '.$paiement->numero."<br>";

print $langs->trans("Date").' : '.strftime("%d %B %Y",$paiement->date)."<br>";

print $langs->trans("Type").' : '.$paiement->type_libelle."<br>";

print $langs->trans("Amount").' : '.$paiement->montant."&nbsp;".MAIN_MONNAIE."<br>";

print '</td></tr>';
print "</table>";

print nl2br($paiement->note);

/*
 *
 *
 */
$allow_delete = 1 ;
$sql = "SELECT f.facnumber, f.total_ttc, pf.amount, f.rowid as facid, f.paye, s.nom, s.idp";
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
  print '<td align="right">'.$langs->trans("AmountTTC").'</td>';	      
  print "</tr>\n";
  
  if ($num > 0) 
    {
      $var=True;
      
      while ($i < $num)
	{
	  $objp = $db->fetch_object();
	  $var=!$var;
	  
	  print "<tr $bc[$var]>";
	  print '<td><a href="'.DOL_URL_ROOT.'/compta/facture.php?facid='.$objp->facid.'">'.img_file().'</a>';
	  print ' <a href="'.DOL_URL_ROOT.'/compta/facture.php?facid='.$objp->facid.'">' . $objp->facnumber;
	  print "</a></td>\n";
	  print '<td><a href="'.DOL_URL_ROOT.'/compta/fiche.php?socid='.$objp->idp.'">' . $objp->nom.'</a></td>';
	  print '<td align="right">'.price($objp->amount).'</td>';
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
  print "<tr $bc[$var]>";
  print '<td>&nbsp;</td>';
  print '<td align="right">Total</td>';
  print '<td align="right">'.price($total).'</td>';  
  print "</tr>\n";

  print "</table>\n";
  $db->free();	
}


/*
 *
 *
 */
  print "<br></div>";

  if ($user->societe_id == 0 && $allow_delete)
    {
      print '<div class="tabsAction">';

      if ($_GET["action"] != 'delete') { 
      	print '<a class="tabAction" href="fiche.php?id='.$_GET["id"].'&amp;action=delete">'.$langs->trans("Delete").'</a>';
      }
      
      print "</div>";      
    }

  $db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
