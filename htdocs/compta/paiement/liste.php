<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
   \file       htdocs/compta/paiement/liste.php
   \ingroup    compta
   \brief      Page liste des paiements des factures clients
   \version    $Revision$
*/

require("./pre.inc.php");

$langs->load("bills");


// Sécurité accés client
if ($user->societe_id > 0) 
{
  $action = '';
  $socidp = $user->societe_id;
}


/*
 * Affichage
 */

llxHeader('',$langs->trans("ListPayment"));

$page=$_GET["page"];
$sortorder=$_GET["sortorder"];
$sortfield=$_GET["sortfield"];
 
$limit = $conf->liste_limit;
$offset = $limit * $page ;
if (! $sortorder) $sortorder="DESC";
if (! $sortfield) $sortfield="p.rowid";
  
$sql = "SELECT p.rowid,".$db->pdate("p.datep")." as dp, p.amount,";
$sql.= " p.statut, p.num_paiement,";
$sql.= " c.libelle as paiement_type";
$sql.= " FROM ".MAIN_DB_PREFIX."paiement as p,";
$sql.= " ".MAIN_DB_PREFIX."c_paiement as c";
$sql.= " WHERE p.fk_paiement = c.id";

if ($_GET["search_montant"])
{
  $sql .=" AND p.amount=".ereg_replace(",",".",$_GET["search_montant"]);
}


if ($_GET["orphelins"]) { // Options qui ne sert qu'au debuggage
    // Paiements liés à aucune facture (pour aide au diagnostique)
    $sql = "SELECT p.rowid,".$db->pdate("p.datep")." as dp, p.amount,";
    $sql.= " p.statut, p.num_paiement,";
    $sql.= " c.libelle as paiement_type";
    $sql.= " FROM ".MAIN_DB_PREFIX."paiement as p,";
    $sql.= " ".MAIN_DB_PREFIX."c_paiement as c";
    $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."paiement_facture as pf";
    $sql.= " ON p.rowid = pf.fk_paiement";
    $sql.= " WHERE p.fk_paiement = c.id AND pf.rowid IS NULL";
}
$sql .= " ORDER BY $sortfield $sortorder";
$sql .= $db->plimit( $limit+1 ,$offset);
//print "$sql";

$resql = $db->query($sql);

if ($resql)
{
  $num = $db->num_rows($resql);
  $i = 0; 
  $var=True;
  
  $paramlist=($_GET["orphelins"]?"&orphelins=1":"");
  print_barre_liste($langs->trans("ReceivedPayments"), $page, "liste.php",$paramlist,$sortfield,$sortorder,'',$num);
  
  print '<table class="noborder" width="100%">';
  print '<tr class="liste_titre">';
  print_liste_field_titre($langs->trans("Ref"),"liste.php","p.rowid","",$paramlist,"",$sortfield);
  print_liste_field_titre($langs->trans("Date"),"liste.php","dp","",$paramlist,"",$sortfield);
  print_liste_field_titre($langs->trans("Type"),"liste.php","c.libelle","",$paramlist,"",$sortfield);
  print_liste_field_titre($langs->trans("AmountTTC"),"liste.php","p.amount","",$paramlist,'align="right"',$sortfield);
  print_liste_field_titre($langs->trans("Status"),"liste.php","p.statut","",$paramlist,'align="center"',$sortfield);
  print "</tr>\n";
  

  // Lignes des champs de filtre
  print '<form method="get" action="liste.php">';
  print '<tr class="liste_titre">';
  print '<td colspan="3">&nbsp;</td>';

  print '<td align="right">';
  print '<input class="fat" type="text" size="10" name="search_montant" value="'.$_GET["search_montant"].'">';

  print '</td><td align="center">';
  print '<input type="submit" class="button" name="button_search" value="'.$langs->trans("Search").'">';
  print '</td>';
  print "</tr>\n";
  print '</form>';


  while ($i < min($num,$limit))
    {
      $objp = $db->fetch_object($resql);
      $var=!$var;
      print "<tr $bc[$var]>";
      print '<td>'.'<a href="'.DOL_URL_ROOT.'/compta/paiement/fiche.php?id='.$objp->rowid.'">'.img_object($langs->trans("ShowPayment"),"payment").' '.$objp->rowid.'</a></td>';
      print '<td>'.dolibarr_print_date($objp->dp)."</td>\n";
      print "<td>$objp->paiement_type $objp->num_paiement</td>\n";
      print '<td align="right">'.price($objp->amount).'</td>';
      print '<td align="center">';

      if ($objp->statut == 0)
	{
	  print '<a href="fiche.php?id='.$objp->rowid.'&amp;action=valide">A valider</a>';
	}
      else
	{
	  print img_tick();
	}

      print '</td></tr>';
      $i++;
    }
  print "</table>";
}
else
{
  dolibarr_print_error($db);
}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
