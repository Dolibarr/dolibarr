<?php
/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo <jlb@j1b.org>
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

/*! \file htdocs/adherents/cotisations.php
        \ingroup    adherent
		\brief      Page de consultation et insertion d'une cotisation
		\version    $Revision$
*/

require("./pre.inc.php");
require(DOL_DOCUMENT_ROOT."/compta/bank/account.class.php");

llxHeader();

if ($action == 'add') {
  $datepaye = $db->idate(mktime(12, 0 , 0, $pmonth, $pday, $pyear));

  $paiement = new Paiement($db);

  $paiement->facid        = $facid;  
  $paiement->datepaye     = $datepaye;
  $paiement->amount       = $amount;
  $paiement->author       = $author;
  $paiement->paiementid   = $paiementid;
  $paiement->num_paiement = $num_paiement;
  $paiement->note         = $note;

  $paiement->create();

  $action = '';

}
// Insertion de la cotisation dans le compte banquaire
if ($_POST["action"] == '2bank' && $_POST["rowid"] !=''){
  if (defined("ADHERENT_BANK_USE") && ADHERENT_BANK_USE !=0){
    $dateop=strftime("%Y%m%d",time());
    $sql="SELECT cotisation FROM ".MAIN_DB_PREFIX."cotisation WHERE rowid=".$_POST["rowid"]." ";
    $result = $db->query($sql);
    if ($result) 
      {
	$num = $db->num_rows();
	if ($num>0)
	  {
	    $objp = $db->fetch_object($result);
	    $amount=$objp->cotisation;
	    $acct=new Account($db,ADHERENT_BANK_ACCOUNT);
	    $insertid=$acct->addline($dateop, $_POST["operation"], $_POST["label"], $amount, $_POST["num_chq"],ADHERENT_BANK_CATEGORIE,$user);
	    if ($insertid == '')
	      {
		   dolibarr_print_error($db);
	      }
	    else
	      {
		// met a jour la table cotisation
		$sql="UPDATE ".MAIN_DB_PREFIX."cotisation SET fk_bank=$insertid WHERE rowid=".$_POST["rowid"]." ";
		$result = $db->query($sql);
		if ($result) 
		  {
		    //Header("Location: cotisations.php");
		  }
		else
		  {
		   dolibarr_print_error($db);
		  }
	      }
	  }
	else
	  {
		   dolibarr_print_error($db);
	  }
      }
    else
      {
		   dolibarr_print_error($db);
      }
    
  }
}

if ($sortorder == "") {  $sortorder="DESC"; }
if ($sortfield == "") {  $sortfield="c.dateadh"; }

if ($page == -1) { $page = 0 ; }

$offset = $conf->liste_limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;

$sql = "SELECT c.cotisation, ".$db->pdate("c.dateadh")." as dateadh";
$sql .= " FROM ".MAIN_DB_PREFIX."adherent as d, ".MAIN_DB_PREFIX."cotisation as c";
$sql .= " WHERE d.rowid = c.fk_adherent";
if(isset($date_select) && $date_select != ''){
  $sql .= " AND dateadh LIKE '$date_select%'";
}
$result = $db->query($sql);
$Total=array();
$Number=array();
if ($result) 
{
  $num = $db->num_rows();
  $i = 0;
  while ($i < $num)
    {
      $objp = $db->fetch_object($result);
      $Total[strftime("%Y",$objp->dateadh)]+=price($objp->cotisation);
      $Number[strftime("%Y",$objp->dateadh)]+=1;
      $i++;
    }
}
$sql = "SELECT d.rowid, d.prenom, d.nom, d.societe, c.cotisation, ".$db->pdate("c.dateadh")." as dateadh, c.fk_bank as bank, c.rowid as crowid";
$sql .= " FROM ".MAIN_DB_PREFIX."adherent as d, ".MAIN_DB_PREFIX."cotisation as c";
$sql .= " WHERE d.rowid = c.fk_adherent";
if(isset($date_select) && $date_select != ''){
  $sql .= " AND dateadh LIKE '$date_select%'";
}
$sql .= " ORDER BY $sortfield $sortorder " . $db->plimit($conf->liste_limit, $offset);

$result = $db->query($sql);
if ($result) 
{
  $num = $db->num_rows();
  $i = 0;
  
  print_barre_liste("Liste des cotisations", $page, "cotisations.php", "&statut=$statut&sortorder=$sortorder&sortfield=$sortfield");

  print "<table class=\"noborder\">\n";
  print '<tr class="liste_titre">';
  print "<td>Annee</td>";
  print '<td align="right">'.$langs->trans("Amount").'</td>';
  print '<td align="right">'.$langs->trans("Number").'</td>';
  print '<td align="right">'.$langs->trans("Average").'</td>';
  print "</tr>\n";

  foreach ($Total as $key=>$value){
    $var=!$var;
    print "<tr $bc[$var]><td><A HREF=\"cotisations.php?statut=$statut&date_select=$key\">$key</A></td><td align=\"right\">".price($value)."</td><td align=\"right\">".$Number[$key]."</td><td align=\"right\">".price($value/$Number[$key])."</td></tr>\n";
  }
  print "</table><br>\n";

  print "<table class=\"border\">";

  print '<tr class="liste_titre">';
  print_liste_field_titre("Date","cotisations.php","c.dateadh","&page=$page&statut=$statut");
  print_liste_field_titre("Montant","cotisations.php","c.cotisation","&page=$page&statut=$statut");
  print_liste_field_titre("Prenom Nom","cotisations.php","d.nom","&page=$page&statut=$statut");

  if (defined("ADHERENT_BANK_USE") && ADHERENT_BANK_USE !=0){
    print '<td>';
    //  print_liste_field_titre("Bank","cotisations.php","c.fk_bank","&page=$page&statut=$statut");
    print 'Bank<br>(Type,Numéro,Libelle)';
    print "</td>\n";
  }
  print "</tr>\n";
    
  $var=True;
  $total=0;
  while ($i < $num)
    {
      $objp = $db->fetch_object();
      $var=!$var;
      print "<tr $bc[$var]>";
      print "<td><a href=\"fiche.php?rowid=$objp->rowid&action=edit\">".strftime("%d %B %Y",$objp->dateadh)."</a></td>\n";
      print '<td align="right">'.price($objp->cotisation).'</td>';
      $total+=price($objp->cotisation);
      print "<td><a href=\"fiche.php?rowid=$objp->rowid&action=edit\">".stripslashes($objp->prenom)." ".stripslashes($objp->nom)."</a></td>\n";
      if (defined("ADHERENT_BANK_USE") && ADHERENT_BANK_USE !=0){
	if ($objp->bank !='' ){
	  print "<td>Deposé</td>";
	}else{
	  print "<td>";
	  print "<form method=\"post\" action=\"cotisations.php\">";
	  print '<input type="hidden" name="action" value="2bank">';
	  print '<input type="hidden" name="rowid" value="'.$objp->crowid.'">';
	  print '<select name="operation">';
	  print '<option value="CHQ" selected>CHQ';
	  print '<option value="CB">CB';
	  print '<option value="DEP">DEP';
	  print '<option value="TIP">TIP';
	  print '<option value="PRE">PRE';
	  print '<option value="VIR">VIR';
	  print '</select>';
	  print '<input name="num_chq" type="text" size="6">&nbsp;-&nbsp;';
	  print "<input name=\"label\" type=\"text\" size=20 value=\"Cotisation ".stripslashes($objp->prenom)." ".stripslashes($objp->nom)." ".strftime("%Y",$objp->dateadh)."\" >\n";
	  //	print "<td><input name=\"debit\" type=\"text\" size=8></td>";
	  //	print "<td><input name=\"credit\" type=\"text\" size=8></td>";
	  print '<input type="submit" value="Dépot">';
	  print "</form>\n";
	  print "</td>\n";
	}	  
      }
      print "</tr>";
      $i++;
    }
  $var=!$var;
  print "<tr $bc[$var]>";
  print "<td>".$langs->trans("Total")."</td>\n";
  print "<td align=\"right\">".price($total)."</td>\n";
  print "<td align=\"right\" colspan=\"2\">";
  print_fleche_navigation($page,"cotisations.php","&statut=$statut&sortorder=$sortorder&sortfield=$sortfield",1);
  print "</td>\n";

  print "</tr>\n";
  print "</table>";
  print "<br>\n";


}
else
{
  dolibarr_print_error($db);
}


$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
