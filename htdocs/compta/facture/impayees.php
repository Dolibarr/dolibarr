<?php
/* Copyright (C) 2002-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Éric Seigne          <eric.seigne@ryxeo.com>
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
 */

/**
        \file       htdocs/compta/facture.php
        \ingroup    facture
        \brief      Page de création d'une facture
        \version    $Revision$
*/

require("./pre.inc.php");
require_once DOL_DOCUMENT_ROOT."/facture.class.php";
require_once DOL_DOCUMENT_ROOT."/paiement.class.php";

$user->getrights('facture');
$user->getrights('banque');

if (!$user->rights->facture->lire)
  accessforbidden();

$langs->load("main"); // BUG De chargement de traduction ne pas modifier cette ligne
$langs->load("bills");



if ($_GET["socidp"]) { $socidp=$_GET["socidp"]; }

// Sécurité accés client
if ($user->societe_id > 0)
{
  $action = '';
  $socidp = $user->societe_id;
}


llxHeader('',$langs->trans("BillsCustomersUnpayed"));


/***************************************************************************
*                                                                         *
*                      Mode Liste                                         *
*                                                                         *
***************************************************************************/
$page = $_GET["page"];
$sortfield=$_GET["sortfield"];
$sortorder=$_GET["sortorder"];
if (! $sortfield) $sortfield="f.date_lim_reglement";
if (! $sortorder) $sortorder="ASC";

if ($user->rights->facture->lire)
{
  $limit = $conf->liste_limit;
  $offset = $limit * $page ;
  
  $sql = "SELECT s.nom,s.idp,f.facnumber,f.increment,f.total,f.total_ttc,";
  $sql.= $db->pdate("f.datef")." as df, ".$db->pdate("f.date_lim_reglement")." as datelimite, ";
  $sql.= " f.paye as paye, f.rowid as facid, f.fk_statut, sum(pf.amount) as am";
  $sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
  $sql.= ",".MAIN_DB_PREFIX."facture as f";
  $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."paiement_facture as pf ON f.rowid=pf.fk_facture ";
  $sql.= " WHERE f.fk_soc = s.idp";
  $sql.= " AND f.paye = 0 AND f.fk_statut = 1";
  if ($socidp) $sql .= " AND s.idp = $socidp";
  
  if ($_GET["filtre"])
    {
      $filtrearr = split(",", $_GET["filtre"]);
      foreach ($filtrearr as $fil)
        {
	  $filt = split(":", $fil);
	  $sql .= " AND " . $filt[0] . " = " . $filt[1];
        }
    }
  
  if ($_GET["search_ref"])
    {
      $sql .= " AND f.facnumber like '%".$_GET["search_ref"]."%'";
    }
  
  if ($_GET["search_societe"])
    {
      $sql .= " AND s.nom like '%".$_GET["search_societe"]."%'";
    }
  
  if ($_GET["search_montant_ht"])
    {
      $sql .= " AND f.total = '".$_GET["search_montant_ht"]."'";
    }
  
  if ($_GET["search_montant_ttc"])
    {
      $sql .= " AND f.total_ttc = '".$_GET["search_montant_ttc"]."'";
    }
  
  if (strlen($_POST["sf_ref"]) > 0)
    {
      $sql .= " AND f.facnumber like '%".$_POST["sf_ref"] . "%'";
    }
    $sql.= " GROUP BY f.facnumber";

    $sql.= " ORDER BY ";
    $listfield=split(',',$sortfield);
    foreach ($listfield as $key => $value) $sql.=$listfield[$key]." ".$sortorder.",";
    $sql.= " f.fk_soc ASC";

    //$sql .= $db->plimit($limit+1,$offset);

    $result = $db->query($sql);

    if ($result)
    {
      $num = $db->num_rows($result);
      
      if ($socidp)
        {
	  $soc = new Societe($db);
	  $soc->fetch($socidp);
        }
      
      print_barre_liste($langs->trans("BillsCustomersUnpayed")." ".($socidp?" $soc->nom":""),$page,"impayees.php","&amp;socidp=$socidp",$sortfield,$sortorder,'',$num);
      $i = 0;
      print '<table class="liste" width="100%">';
      print '<tr class="liste_titre">';
      
      print_liste_field_titre($langs->trans("Ref"),$_SERVER["PHP_SELF"],"f.facnumber","","&amp;socidp=$socidp","",$sortfield);
      print_liste_field_titre($langs->trans("Date"),$_SERVER["PHP_SELF"],"f.datef","","&amp;socidp=$socidp",'align="center"',$sortfield);
      print_liste_field_titre($langs->trans("DateDue"),$_SERVER["PHP_SELF"],"f.date_lim_reglement","","&amp;socidp=$socidp",'align="center"',$sortfield);
      print_liste_field_titre($langs->trans("Company"),$_SERVER["PHP_SELF"],"s.nom","","&amp;socidp=$socidp","",$sortfield);
      print_liste_field_titre($langs->trans("AmountHT"),$_SERVER["PHP_SELF"],"f.total","","&amp;socidp=$socidp",'align="right"',$sortfield);
      print_liste_field_titre($langs->trans("AmountTTC"),$_SERVER["PHP_SELF"],"f.total_ttc","","&amp;socidp=$socidp",'align="right"',$sortfield);
      print_liste_field_titre($langs->trans("Received"),$_SERVER["PHP_SELF"],"am","","&amp;socidp=$socidp",'align="right"',$sortfield);
      print_liste_field_titre($langs->trans("Status"),$_SERVER["PHP_SELF"],"fk_statut,paye","","&amp;socidp=$socidp",'align="right"',$sortfield);
      print "</tr>\n";
      
      // Lignes des champs de filtre
      print '<form method="get" action="impayees.php">';
      print '<tr class="liste_titre">';
      print '<td class="liste_titre" valign="right">';
      print '<input class="flat" size="10" type="text" name="search_ref" value="'.$_GET["search_ref"].'"></td>';
      print '<td class="liste_titre">&nbsp;</td>';
      print '<td class="liste_titre">&nbsp;</td>';
      print '<td class="liste_titre" align="left">';
      print '<input class="flat" type="text" name="search_societe" value="'.$_GET["search_societe"].'">';
      print '</td><td class="liste_titre" align="right">';
      print '<input class="flat" type="text" size="10" name="search_montant_ht" value="'.$_GET["search_montant_ht"].'">';
      print '</td><td class="liste_titre" align="right">';
      print '<input class="flat" type="text" size="10" name="search_montant_ttc" value="'.$_GET["search_montant_ttc"].'">';
      print '</td><td class="liste_titre" colspan="2" align="right">';
      print '<input type="image" class="liste_titre" name="button_search" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" alt="'.$langs->trans("Search").'">';
      print '</td>';
      print "</tr>\n";
      print '</form>';
      
      if ($num > 0)
        {
	  $var=True;
	  $total=0;
	  $totalrecu=0;
	  
	  while ($i < $num)
            {
	      $objp = $db->fetch_object($result);
	      
                $var=!$var;
		
                print "<tr $bc[$var]>";
                $class = "impayee";
		
                print '<td nowrap><a href="'.DOL_URL_ROOT.'/compta/facture.php?facid='.$objp->facid.'">'.img_object($langs->trans("ShowBill"),"bill")."</a> ";
                print '<a href="'.DOL_URL_ROOT.'/compta/facture.php?facid='.$objp->facid.'">'.$objp->facnumber.'</a>'.$objp->increment;
                if ($objp->datelimite < (time() - $conf->facture->client->warning_delay) && ! $objp->paye && $objp->fk_statut == 1) print img_warning($langs->trans("Late"));
                print "</td>\n";

                print "<td nowrap align=\"center\">".dolibarr_print_date($objp->df)."</td>\n";
                print "<td nowrap align=\"center\">".dolibarr_print_date($objp->datelimite)."</td>\n";

                print '<td><a href="'.DOL_URL_ROOT.'/compta/fiche.php?socid='.$objp->idp.'">'.img_object($langs->trans("ShowCompany"),"company").' '.dolibarr_trunc($objp->nom,32).'</a></td>';

                print "<td align=\"right\">".price($objp->total)."</td>";
                print "<td align=\"right\">".price($objp->total_ttc)."</td>";
                print "<td align=\"right\">".price($objp->am)."</td>";

                // Affiche statut de la facture
                if (! $objp->paye)
                {
                    if ($objp->fk_statut == 0)
                    {
                        print '<td align="center">'.$langs->trans("BillShortStatusDraft").'</td>';
                    }
                    elseif ($objp->fk_statut == 3)
                    {
                        print '<td align="center">'.$langs->trans("BillShortStatusCanceled").'</td>';
                    }
                    else
                    {
                        print '<td align="center"><a class="'.$class.'" href="'.$_SERVER["PHP_SELF"].'?filtre=paye:0,fk_statut:1">'.($objp->am?$langs->trans("BillShortStatusStarted"):$langs->trans("BillShortStatusNotPayed")).'</a></td>';
                    }
                }
                else
                {
                    print '<td align="center">'.$langs->trans("BillShortStatusPayed").'</td>';
                }

                print "</tr>\n";
                $total+=$objp->total;
                $total_ttc+=$objp->total_ttc;
                $totalrecu+=$objp->am;

                $i++;
            }

            print '<tr class="liste_total">';
            print "<td colspan=\"4\" align=\"left\">".$langs->trans("Total").": </td>";
            print "<td align=\"right\"><b>".price($total)."</b></td>";
            print "<td align=\"right\"><b>".price($total_ttc)."</b></td>";
            print "<td align=\"right\"><b>".price($totalrecu)."</b></td>";
            print '<td align="center">&nbsp;</td>';
            print "</tr>\n";
        }

        print "</table>";
        $db->free();
    }
    else
    {
        dolibarr_print_error($db);
    }

}




$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
