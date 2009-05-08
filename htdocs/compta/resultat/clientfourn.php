<?php
/* Copyright (C) 2002-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
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
 */

/**
   \file        htdocs/compta/resultat/clientfourn.php
   \brief       Page reporting resultat
   \version     $Id$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/compta/tva/tva.class.php");
require_once(DOL_DOCUMENT_ROOT."/chargesociales.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/report.lib.php");


$langs->load("bills");

if (!$user->rights->facture->lire)
  accessforbidden();

$year=$_GET["year"];
if (! $year) { $year = strftime("%Y", time()); }
// Define modecompta ('CREANCES-DETTES' or 'RECETTES-DEPENSES')
$modecompta = $conf->compta->mode;
if ($_GET["modecompta"]) $modecompta=$_GET["modecompta"];



llxHeader();

$html=new Form($db);

// Affiche en-tete de rapport
if ($modecompta=="CREANCES-DETTES")
{
    $nom=$langs->trans("AnnualByCompaniesDueDebtMode");
    $nom.='<br>('.$langs->trans("SeeReportInInputOutputMode",'<a href="'.$_SERVER["PHP_SELF"].'?year='.$year.'&modecompta=RECETTES-DEPENSES">','</a>').')';
    $period=$langs->trans("Year")." ".$year;
    $periodlink='<a href="'.$_SERVER["PHP_SELF"].'?year='.($year-1).'&modecompta='.$modecompta.'">'.img_previous().'</a> <a href="'.$_SERVER["PHP_SELF"].'?year='.($year+1).'&modecompta='.$modecompta.'">'.img_next().'</a>';
    $description=$langs->trans("RulesResultDue");
    $builddate=time();
    $exportlink=$langs->trans("NotYetAvailable");
}
else {
    $nom=$langs->trans("AnnualByCompaniesInputOutputMode");
    $nom.='<br>('.$langs->trans("SeeReportInDueDebtMode",'<a href="'.$_SERVER["PHP_SELF"].'?year='.$year.'&modecompta=CREANCES-DETTES">','</a>').')';
    $period=$langs->trans("Year")." ".$year;
    $periodlink='<a href="'.$_SERVER["PHP_SELF"].'?year='.($year-1).'&modecompta='.$modecompta.'">'.img_previous().'</a> <a href="'.$_SERVER["PHP_SELF"].'?year='.($year+1).'&modecompta='.$modecompta.'">'.img_next().'</a>';
    $description=$langs->trans("RulesResultInOut");
    $builddate=time();
    $exportlink=$langs->trans("NotYetAvailable");
}
report_header($nom,$nomlink,$period,$periodlink,$description,$builddate,$exportlink);

// Affiche rapport
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td width="10%">&nbsp;</td><td>'.$langs->trans("Element").'</td>';
if ($modecompta == 'CREANCES-DETTES') print "<td align=\"right\">".$langs->trans("AmountHT")."</td>";
print "<td align=\"right\">".$langs->trans("AmountTTC")."</td>";
print "</tr>\n";
print '<tr><td colspan="4">&nbsp;</td></tr>';

/*
 * Factures clients
 */
print '<tr><td colspan="4">Facturation clients</td></tr>';

if ($modecompta == 'CREANCES-DETTES') {
    $sql = "SELECT s.nom, s.rowid as socid, sum(f.total) as amount_ht, sum(f.total_ttc) as amount_ttc";
    $sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
    $sql.= ", ".MAIN_DB_PREFIX."facture as f";
    $sql.= " WHERE f.fk_soc = s.rowid";
    $sql.= " AND f.fk_statut in (1,2)";
    if ($year) $sql.= " AND f.datef between '".$year."-01-01 00:00:00' and '".$year."-12-31 23:59:59'";
} else {
    /*
     * Liste des paiements (les anciens paiements ne sont pas vus par cette requete car, sur les
     * vieilles versions, ils n'etaient pas lies via paiement_facture. On les ajoute plus loin)
     */
	$sql = "SELECT s.nom as nom, s.rowid as socid, sum(pf.amount) as amount_ttc";
	$sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
	$sql.= ", ".MAIN_DB_PREFIX."facture as f";
	$sql.= ", ".MAIN_DB_PREFIX."paiement_facture as pf";
	$sql.= ", ".MAIN_DB_PREFIX."paiement as p";
  $sql.= " WHERE p.rowid = pf.fk_paiement";
  $sql.= " AND pf.fk_facture = f.rowid";
  $sql.= " AND f.fk_soc = s.rowid";
  if ($year) $sql.= " AND p.datep between '".$year."-01-01 00:00:00' and '".$year."-12-31 23:59:59'";
}
$sql.= " AND f.entity = ".$conf->entity;
if ($socid) $sql.= " AND f.fk_soc = ".$socid;
$sql.= " GROUP BY nom";
$sql.= " ORDER BY nom";

$result = $db->query($sql);
if ($result) {
    $num = $db->num_rows($result);
    $i = 0;
    $var=true;
    while ($i < $num)
    {
        $objp = $db->fetch_object($result);
        $var=!$var;

        print "<tr $bc[$var]><td>&nbsp;</td>";
        print "<td>".$langs->trans("Bills")." <a href=\"../facture.php?socid=".$objp->socid."\">$objp->nom</td>\n";

        if ($modecompta == 'CREANCES-DETTES') print "<td align=\"right\">".price($objp->amount_ht)."</td>\n";
        print "<td align=\"right\">".price($objp->amount_ttc)."</td>\n";

        $total_ht = $total_ht + $objp->amount_ht;
        $total_ttc = $total_ttc + $objp->amount_ttc;
        print "</tr>\n";
        $i++;
    }
    $db->free($result);
} else {
    dol_print_error($db);
}

// On ajoute les paiements clients anciennes version, non lie par paiement_facture
if ($modecompta != 'CREANCES-DETTES')
{
    $sql = "SELECT 'Autres' as nom, '0' as idp, sum(p.amount) as amount_ttc";
    $sql.= " FROM ".MAIN_DB_PREFIX."bank as b";
    $sql.= ", ".MAIN_DB_PREFIX."bank_account as ba";
    $sql.= ", ".MAIN_DB_PREFIX."paiement as p";
    $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."paiement_facture as pf ON p.rowid = pf.fk_paiement";
    $sql.= " WHERE pf.rowid IS NULL";
    $sql.= " AND p.fk_bank = b.rowid";
    $sql.= " AND b.fk_account = ba.rowid";
    $sql.= " AND ba.entity = ".$conf->entity;
    if ($year) $sql.= " AND p.datep between '".$year."-01-01 00:00:00' and '".$year."-12-31 23:59:59'";
    $sql.= " GROUP BY nom";
    $sql.= " ORDER BY nom";

    $result = $db->query($sql);
    if ($result) {
        $num = $db->num_rows($result);
        $i = 0;
        if ($num) {
            while ($i < $num)
            {
                $objp = $db->fetch_object($result);
                $var=!$var;

                print "<tr $bc[$var]><td>&nbsp;</td>";
                print "<td>".$langs->trans("Bills")." ".$langs->trans("Other")." (".$langs->trans("PaymentsNotLinkedToInvoice").")\n";

                if ($modecompta == 'CREANCES-DETTES') print "<td align=\"right\">".price($objp->amount_ht)."</td>\n";
                print "<td align=\"right\">".price($objp->amount_ttc)."</td>\n";

                $total_ht = $total_ht + $objp->amount_ht;
                $total_ttc = $total_ttc + $objp->amount_ttc;
                print "</tr>\n";
                $i++;
            }
        }
        $db->free($result);
    } else {
        dol_print_error($db);
    }
}

if ($total_ttc == 0)
{
    $var=!$var;
    print "<tr $bc[$var]><td>&nbsp;</td>";
    print '<td colspan="3">'.$langs->trans("None").'</td>';
    print '</tr>';
}

print '<tr class="liste_total">';
if ($modecompta == 'CREANCES-DETTES') print '<td colspan="3" align="right">'.price($total_ht).'</td>';
print '<td colspan="3" align="right">'.price($total_ttc).'</td>';
print '</tr>';


/*
 * Frais, factures fournisseurs.
 */
if ($modecompta == 'CREANCES-DETTES')
{
	$sql = "SELECT s.nom, s.rowid as socid, sum(f.total_ht) as amount_ht, sum(f.total_ttc) as amount_ttc, date_format(f.datef,'%Y-%m') as dm";
  $sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
  $sql.= ", ".MAIN_DB_PREFIX."facture_fourn as f";
  $sql.= " WHERE f.fk_soc = s.rowid";
  $sql.= " AND f.fk_statut in (1,2)";
  if ($year) {
  	$sql.= " AND f.datef between '".$year."-01-01 00:00:00' and '".$year."-12-31 23:59:59'";
  }
} else {
	$sql = "SELECT s.nom, s.rowid as socid, date_format(p.datep,'%Y-%m') as dm, sum(pf.amount) as amount_ttc";
	$sql .= " FROM ".MAIN_DB_PREFIX."paiementfourn as p";
	$sql.= ", ".MAIN_DB_PREFIX."paiementfourn_facturefourn as pf";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."facture_fourn as f";
	$sql .= " ON pf.fk_facturefourn = f.rowid";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s";
	$sql .= " ON f.fk_soc = s.rowid";
  $sql .= " WHERE p.rowid = pf.fk_paiementfourn ";
  if ($year) {
  	$sql.= " AND p.datep between '".$year."-01-01 00:00:00' and '".$year."-12-31 23:59:59'";
  }
}
$sql.= " AND f.entity = ".$conf->entity;
if ($socid) $sql.= " AND f.fk_soc = ".$socid;
$sql .= " GROUP BY nom, s.rowid";
$sql .= " ORDER BY nom, s.rowid";

print '<tr><td colspan="4">Facturation fournisseurs</td></tr>';
$subtotal_ht = 0;
$subtotal_ttc = 0;
$result = $db->query($sql);
if ($result) {
  $num = $db->num_rows($result);
  $i = 0;
  $var=true;
  if ($num > 0) {
    while ($i < $num) {
      $objp = $db->fetch_object($result);
      $var=!$var;

      print "<tr $bc[$var]><td>&nbsp;</td>";
      print "<td>".$langs->trans("Bills")." <a href=\"".DOL_URL_ROOT."/fourn/facture/index.php?socid=".$objp->socid."\">".$objp->nom."</a></td>\n";

      if ($modecompta == 'CREANCES-DETTES') print "<td align=\"right\">".price(-$objp->amount_ht)."</td>\n";
      print "<td align=\"right\">".price(-$objp->amount_ttc)."</td>\n";

      $total_ht = $total_ht - $objp->amount_ht;
      $total_ttc = $total_ttc - $objp->amount_ttc;
      $subtotal_ht = $subtotal_ht + $objp->amount_ht;
      $subtotal_ttc = $subtotal_ttc + $objp->amount_ttc;
      print "</tr>\n";
      $i++;
    }
  }
  else {
    $var=!$var;
    print "<tr $bc[$var]><td>&nbsp;</td>";
    print '<td colspan="3">'.$langs->trans("None").'</td>';
    print '</tr>';
  }

  $db->free($result);
} else {
  dol_print_error($db);
}
print '<tr class="liste_total">';
if ($modecompta == 'CREANCES-DETTES') print '<td colspan="3" align="right">'.price(-$subtotal_ht).'</td>';
print '<td colspan="3" align="right">'.price(-$subtotal_ttc).'</td>';
print '</tr>';



/*
 * Charges sociales non deductibles
 */

print '<tr><td colspan="4">Prestations/Charges NON deductibles</td></tr>';

if ($modecompta == 'CREANCES-DETTES') {
    $sql = "SELECT c.libelle as nom, sum(s.amount) as amount";
    $sql.= " FROM ".MAIN_DB_PREFIX."c_chargesociales as c";
    $sql.= ", ".MAIN_DB_PREFIX."chargesociales as s";
    $sql.= " WHERE s.fk_type = c.id";
    $sql.= " AND c.deductible = 0";
    if ($year) {
    	$sql.= " AND s.date_ech between '".$year."-01-01 00:00:00' and '".$year."-12-31 23:59:59'";
    }
}
else {
    $sql = "SELECT c.libelle as nom, sum(p.amount) as amount";
    $sql.= " FROM ".MAIN_DB_PREFIX."c_chargesociales as c";
    $sql.= ", ".MAIN_DB_PREFIX."chargesociales as s";
    $sql.= ", ".MAIN_DB_PREFIX."paiementcharge as p";
    $sql.= " WHERE p.fk_charge = s.rowid";
    $sql.= " AND s.fk_type = c.id";
    $sql.= " AND c.deductible = 0";
    if ($year) {
    	$sql.= " AND p.datep between '".$year."-01-01 00:00:00' and '".$year."-12-31 23:59:59'";
    }
}
$sql.= " AND s.entity = ".$conf->entity;
$sql.= " GROUP BY c.libelle";

$result=$db->query($sql);
$subtotal_ht = 0;
$subtotal_ttc = 0;
if ($result) {
    $num = $db->num_rows($result);
    $var=true;
    $i = 0;
    if ($num) {
      while ($i < $num) {
        $obj = $db->fetch_object($result);

        $total_ht = $total_ht - $obj->amount;
        $total_ttc = $total_ttc - $obj->amount;
        $subtotal_ht = $subtotal_ht + $obj->amount;
        $subtotal_ttc = $subtotal_ttc + $obj->amount;

        $var = !$var;
        print "<tr $bc[$var]><td>&nbsp;</td>";
        print '<td>'.$obj->nom.'</td>';
        if ($modecompta == 'CREANCES-DETTES') print '<td align="right">'.price(-$obj->amount).'</td>';
        print '<td align="right">'.price(-$obj->amount).'</td>';
        print '</tr>';
        $i++;
      }
    }
    else {
        $var = !$var;
    	print "<tr $bc[$var]><td>&nbsp;</td>";
        print '<td colspan="3">'.$langs->trans("None").'</td>';
        print '</tr>';
    }
} else {
  dol_print_error($db);
}
print '<tr class="liste_total">';
if ($modecompta == 'CREANCES-DETTES') print '<td colspan="3" align="right">'.price(-$subtotal_ht).'</td>';
print '<td colspan="3" align="right">'.price(-$subtotal_ttc).'</td>';
print '</tr>';


/*
 * Charges sociales deductibles
 */

print '<tr><td colspan="4">Prestations/Charges deductibles</td></tr>';

if ($modecompta == 'CREANCES-DETTES') {
    $sql = "SELECT c.libelle as nom, sum(s.amount) as amount";
    $sql.= " FROM ".MAIN_DB_PREFIX."c_chargesociales as c";
    $sql.= ", ".MAIN_DB_PREFIX."chargesociales as s";
    $sql.= " WHERE s.fk_type = c.id";
    $sql.= " AND c.deductible = 1";
    if ($year) {
    	$sql.= " AND s.date_ech between '".$year."-01-01 00:00:00' and '".$year."-12-31 23:59:59'";
    }
    $sql.= " AND s.entity = ".$conf->entity;
    $sql.= " GROUP BY c.libelle DESC";
}
else {
    $sql = "SELECT c.libelle as nom, sum(p.amount) as amount";
    $sql .= " FROM ".MAIN_DB_PREFIX."c_chargesociales as c";
    $sql.= ", ".MAIN_DB_PREFIX."chargesociales as s";
    $sql.= ", ".MAIN_DB_PREFIX."paiementcharge as p";
    $sql .= " WHERE p.fk_charge = s.rowid";
    $sql.= " AND s.fk_type = c.id";
    $sql.= " AND c.deductible = 1";
    if ($year) {
    	$sql .= " AND p.datep between '".$year."-01-01 00:00:00' and '".$year."-12-31 23:59:59'";
    }
    $sql.= " AND s.entity = ".$conf->entity;
    $sql.= " GROUP BY c.libelle";
}

$result=$db->query($sql);
$subtotal_ht = 0;
$subtotal_ttc = 0;
if ($result) {
    $num = $db->num_rows($result);
    $var=true;
    $i = 0;
    if ($num) {
        while ($i < $num) {
        $obj = $db->fetch_object($result);

        $total_ht = $total_ht - $obj->amount;
        $total_ttc = $total_ttc - $obj->amount;
        $subtotal_ht = $subtotal_ht + $obj->amount;
        $subtotal_ttc = $subtotal_ttc + $obj->amount;

        $var = !$var;
        print "<tr $bc[$var]><td>&nbsp;</td>";
        print '<td>'.$obj->nom.'</td>';
        if ($modecompta == 'CREANCES-DETTES') print '<td align="right">'.price(-$obj->amount).'</td>';
        print '<td align="right">'.price(-$obj->amount).'</td>';
        print '</tr>';
        $i++;
        }
    }
    else {
        $var = !$var;
    	print "<tr $bc[$var]><td>&nbsp;</td>";
        print '<td colspan="3">'.$langs->trans("None").'</td>';
        print '</tr>';
    }
} else {
  dol_print_error($db);
}
print '<tr class="liste_total">';
if ($modecompta == 'CREANCES-DETTES') print '<td colspan="3" align="right">'.price(-$subtotal_ht).'</td>';
print '<td colspan="3" align="right">'.price(-$subtotal_ttc).'</td>';
print '</tr>';

if ($mysoc->tva_assuj == 'franchise')	// Non assujeti
{
	// Total
	print '<tr>';
	print '<td colspan="4">&nbsp;</td>';
	print '</tr>';

	print '<tr class="liste_total"><td align="left" colspan="2">'.$langs->trans("Profit").'</td>';
	if ($modecompta == 'CREANCES-DETTES') print '<td class="border" align="right">'.price($total_ht).'</td>';
	print '<td class="border" align="right">'.price($total_ttc).'</td>';
	print '</tr>';

	print '<tr>';
	print '<td colspan="4">&nbsp;</td>';
	print '</tr>';
}


/*
 * VAT
 */
print '<tr><td colspan="4">'.$langs->trans("VAT").'</td></tr>';
$subtotal_ht = 0;
$subtotal_ttc = 0;

if ($modecompta == 'CREANCES-DETTES')
{
    // TVA a payer
    $amount=0;
    $sql = "SELECT sum(f.tva) as amount, date_format(f.datef,'%Y-%m') as dm";
    $sql.= " FROM ".MAIN_DB_PREFIX."facture as f";
    $sql.= " WHERE f.fk_statut in (1,2)";
    if ($year) {
    	$sql.= " AND f.datef between '".$year."-01-01 00:00:00' and '".$year."-12-31 23:59:59'";
    }
    $sql.= " AND f.entity = ".$conf->entity;
    $sql.= " GROUP BY dm";
    $sql.= " ORDER BY dm DESC";
    
    $result=$db->query($sql);
    if ($result) {
        $num = $db->num_rows($result);
        $var=false;
        $i = 0;
        if ($num) {
          while ($i < $num) {
            $obj = $db->fetch_object($result);

            $amount = $amount - $obj->amount;
            $total_ht = $total_ht - $obj->amount;
            $total_ttc = $total_ttc - $obj->amount;
            $subtotal_ht = $subtotal_ht - $obj->amount;
            $subtotal_ttc = $subtotal_ttc - $obj->amount;
            $i++;
          }
        }
    } else {
        dol_print_error($db);
    }
    print "<tr $bc[$var]><td>&nbsp;</td>";
    print "<td>".$langs->trans("VATToPay")."</td>\n";
    if ($modecompta == 'CREANCES-DETTES') print "<td align=\"right\">".price($amount)."</td>\n";
    print "<td align=\"right\">".price($amount)."</td>\n";
    print "</tr>\n";

    // TVA a recuperer
    $amount=0;
    $sql = "SELECT sum(f.total_tva) as amount, date_format(f.datef,'%Y-%m') as dm";
    $sql.= " FROM ".MAIN_DB_PREFIX."facture_fourn as f";
    $sql.= " WHERE f.fk_statut in (1,2)";
    if ($year) {
    	$sql.= " AND f.datef between '".$year."-01-01 00:00:00' and '".$year."-12-31 23:59:59'";
    }
    $sql.= " AND f.entity = ".$conf->entity;
    $sql.= " GROUP BY dm";
    $sql.= " ORDER BY dm DESC";

    $result=$db->query($sql);
    if ($result) {
        $num = $db->num_rows($result);
        $var=true;
        $i = 0;
        if ($num) {
          while ($i < $num) {
            $obj = $db->fetch_object($result);

            $amount = $amount + $obj->amount;
            $total_ht = $total_ht + $obj->amount;
            $total_ttc = $total_ttc + $obj->amount;
            $subtotal_ht = $subtotal_ht + $obj->amount;
            $subtotal_ttc = $subtotal_ttc + $obj->amount;

            $i++;
          }
        }
    } else {
        dol_print_error($db);
    }
    print "<tr $bc[$var]><td>&nbsp;</td>";
    print "<td>".$langs->trans("VATToCollect")."</td>\n";
    if ($modecompta == 'CREANCES-DETTES') print "<td align=\"right\">".price($amount)."</td>\n";
    print "<td align=\"right\">".price($amount)."</td>\n";
    print "</tr>\n";
}
else
{
    // TVA reellement deja payee
    $amount=0;
    $sql = "SELECT sum(t.amount) as amount, date_format(t.datev,'%Y-%m') as dm";
    $sql.= " FROM ".MAIN_DB_PREFIX."tva as t";
    $sql.= " WHERE amount > 0";
    if ($year) {
    	$sql.= " AND t.datev between '".$year."-01-01 00:00:00' and '".$year."-12-31 23:59:59'";
    }
    $sql.= " AND t.entity = ".$conf->entity;
    $sql.= " GROUP BY dm";
    $sql.= " ORDER BY dm DESC";
    
    $result=$db->query($sql);
    if ($result) {
        $num = $db->num_rows($result);
        $var=false;
        $i = 0;
        if ($num) {
          while ($i < $num) {
            $obj = $db->fetch_object($result);

            $amount = $amount - $obj->amount;
            $total_ht = $total_ht - $obj->amount;
            $total_ttc = $total_ttc - $obj->amount;
            $subtotal_ht = $subtotal_ht - $obj->amount;
            $subtotal_ttc = $subtotal_ttc - $obj->amount;

            $i++;
          }
        }
        $db->free($result);
    } else {
        dol_print_error($db);
    }
    print "<tr $bc[$var]><td>&nbsp;</td>";
    print "<td>".$langs->trans("VATPayed")."</td>\n";
    if ($modecompta == 'CREANCES-DETTES') print "<td align=\"right\">".price($amount)."</td>\n";
    print "<td align=\"right\">".price($amount)."</td>\n";
    print "</tr>\n";

    // TVA recuperee
    $amount=0;
    $sql = "SELECT sum(t.amount) as amount, date_format(t.datev,'%Y-%m') as dm";
    $sql.= " FROM ".MAIN_DB_PREFIX."tva as t";
    $sql.= " WHERE amount < 0";
    if ($year) {
    	$sql.= " AND t.datev between '".$year."-01-01 00:00:00' and '".$year."-12-31 23:59:59'";
    }
    $sql.= " AND t.entity = ".$conf->entity;
    $sql.= " GROUP BY dm";
    $sql.= " ORDER BY dm DESC";
    
    $result=$db->query($sql);
    if ($result) {
        $num = $db->num_rows($result);
        $var=true;
        $i = 0;
        if ($num) {
          while ($i < $num) {
            $obj = $db->fetch_object($result);

            $amount = $amount + $obj->amount;
            $total_ht = $total_ht + $obj->amount;
            $total_ttc = $total_ttc + $obj->amount;
            $subtotal_ht = $subtotal_ht + $obj->amount;
            $subtotal_ttc = $subtotal_ttc + $obj->amount;

            $i++;
          }
        }
        $db->free($result);
    } else {
        dol_print_error($db);
    }
    print "<tr $bc[$var]><td>&nbsp;</td>";
    print "<td>".$langs->trans("VATCollected")."</td>\n";
    if ($modecompta == 'CREANCES-DETTES') print "<td align=\"right\">".price($amount)."</td>\n";
    print "<td align=\"right\">".price($amount)."</td>\n";
    print "</tr>\n";
}


if ($mysoc->tva_assuj != 'franchise')	// Assujeti
{
	print '<tr class="liste_total">';
	if ($modecompta == 'CREANCES-DETTES') print '<td colspan="3" align="right">'.price($subtotal_ht).'</td>';
	print '<td colspan="3" align="right">'.price($subtotal_ttc).'</td>';
	print '</tr>';
}


if ($mysoc->tva_assuj != 'franchise')	// Assujeti
{
// Total
	print '<tr>';
	print '<td colspan="4">&nbsp;</td>';
	print '</tr>';

	print '<tr class="liste_total"><td align="left" colspan="2">'.$langs->trans("Profit").'</td>';
	if ($modecompta == 'CREANCES-DETTES') print '<td class="border" align="right">'.price($total_ht).'</td>';
	print '<td class="border" align="right">'.price($total_ttc).'</td>';
	print '</tr>';
}

print "</table>";
print '<br>';


$db->close();

llxFooter('$Date$ - $Revision$');
?>
