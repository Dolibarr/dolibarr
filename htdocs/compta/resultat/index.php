<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
        \file        htdocs/compta/resultat/index.php
        \brief       Page reporting resultat
        \version     $Id$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/report.inc.php");


$year_start=isset($_GET["year_start"])?$_GET["year_start"]:$_POST["year_start"];
$year_current = strftime("%Y",time());
$nbofyear=4;
if (! $year_start) {
    $year_start = $year_current - ($nbofyear-1);
    $year_end = $year_current;
}
else {
    $year_end=$year_start + ($nbofyear-1);
}

/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $socid = $user->societe_id;
}

// Define modecompta ('CREANCES-DETTES' or 'RECETTES-DEPENSES')
$modecompta = $conf->compta->mode;
if ($_GET["modecompta"]) $modecompta=$_GET["modecompta"];


llxHeader();

$html=new Form($db);

// Affiche en-tête du rapport
if ($modecompta=="CREANCES-DETTES")
{
    $nom=$langs->trans("AnnualSummaryDueDebtMode");
    $nom.='<br>('.$langs->trans("SeeReportInInputOutputMode",'<a href="'.$_SERVER["PHP_SELF"].'?year_start='.$year_start.'&modecompta=RECETTES-DEPENSES">','</a>').')';
    $period="$year_start - $year_end";
    $periodlink=($year_start?"<a href='".$_SERVER["PHP_SELF"]."?year_start=".($year_start-1)."&modecompta=".$modecompta."'>".img_previous()."</a> <a href='".$_SERVER["PHP_SELF"]."?year_start=".($year_start+1)."&modecompta=".$modecompta."'>".img_next()."</a>":"");
    $description=$langs->trans("RulesResultDue");
    $builddate=time();
    $exportlink=$langs->trans("NotYetAvailable");
}
else {
    $nom=$langs->trans("AnnualSummaryInputOutputMode");
    $nom.='<br>('.$langs->trans("SeeReportInDueDebtMode",'<a href="'.$_SERVER["PHP_SELF"].'?year_start='.$year_start.'&modecompta=CREANCES-DETTES">','</a>').')';
    $period="$year_start - $year_end";
    $periodlink=($year_start?"<a href='".$_SERVER["PHP_SELF"]."?year_start=".($year_start-1)."&modecompta=".$modecompta."'>".img_previous()."</a> <a href='".$_SERVER["PHP_SELF"]."?year_start=".($year_start+1)."&modecompta=".$modecompta."'>".img_next()."</a>":"");
    $description=$langs->trans("RulesResultInOut");
    $builddate=time();
    $exportlink=$langs->trans("NotYetAvailable");
}
report_header($nom,$nomlink,$period,$periodlink,$description,$builddate,$exportlink);


/*
 * Factures clients
 */
$subtotal_ht = 0;
$subtotal_ttc = 0;
if ($modecompta == 'CREANCES-DETTES') { 
    $sql  = "SELECT sum(f.total) as amount_ht, sum(f.total_ttc) as amount_ttc, date_format(f.datef,'%Y-%m') as dm";
    $sql .= " FROM ".MAIN_DB_PREFIX."societe as s,".MAIN_DB_PREFIX."facture as f";
    $sql .= " WHERE f.fk_soc = s.rowid AND f.fk_statut in (1,2)";
} else {
    /*
     * Liste des paiements (les anciens paiements ne sont pas vus par cette requete car, sur les
     * vieilles versions, ils n'étaient pas liés via paiement_facture. On les ajoute plus loin)
     */
	$sql  = "SELECT sum(pf.amount) as amount_ttc, date_format(p.datep,'%Y-%m') as dm";
	$sql .= " FROM ".MAIN_DB_PREFIX."facture as f, ".MAIN_DB_PREFIX."paiement_facture as pf, ".MAIN_DB_PREFIX."paiement as p";
    $sql .= " WHERE p.rowid = pf.fk_paiement AND pf.fk_facture = f.rowid";
}
if ($socid) $sql .= " AND f.fk_soc = $socid";
$sql .= " GROUP BY dm";
$sql .= " ORDER BY dm";

$result=$db->query($sql);
if ($result)
{
    $num = $db->num_rows($result);
    $i = 0; 
    while ($i < $num)
    {
        $row = $db->fetch_object($result);
        $encaiss[$row->dm] = $row->amount_ht;
        $encaiss_ttc[$row->dm] = $row->amount_ttc;
        $i++;
    }
    $db->free($result);
}
else {
	dolibarr_print_error($db);	
}

// On ajoute les paiements clients anciennes version, non liés par paiement_facture
if ($modecompta != 'CREANCES-DETTES') { 
    $sql = "SELECT sum(p.amount) as amount_ttc, date_format(p.datep,'%Y-%m') as dm";
    $sql .= " FROM ".MAIN_DB_PREFIX."paiement as p";
    $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."paiement_facture as pf ON p.rowid = pf.fk_paiement";
    $sql .= " WHERE pf.rowid IS NULL";
    $sql .= " GROUP BY dm";
    $sql .= " ORDER BY dm";

    $result = $db->query($sql);
    if ($result) {
        $num = $db->num_rows($result);
        $i = 0;
        while ($i < $num)
        {
            $row = $db->fetch_object($result);

            $encaiss[$row->dm] += $row->amount_ht;
            $encaiss_ttc[$row->dm] += $row->amount_ttc;

			// For DEBUG Only
			if (eregi('^2007',$row->dm))
			{
			$subtotal_ht = $subtotal_ht + $row->amount_ht;
			$subtotal_ttc = $subtotal_ttc + $row->amount_ttc;
			}

            $i++;
        }
    }
    else {
        dolibarr_print_error($db);
    }
}
/*
print "<br>Facture clients: subtotal_ht=".$subtotal_ht.' - subtotal_ttc='.$subtotal_ttc."<br>\n";
for ($mois = 1 ; $mois <= 12 ; $mois++)
{
	$annee = 2007;
	$case = strftime("%Y-%m",dolibarr_mktime(12,0,0,$mois,1,$annee));
	print 'Mois '.$mois.': '.$decaiss_ttc[$case].' ';
	print 'Mois '.$mois.': '.$encaiss_ttc[$case].' ';
}
*/

/*
 * Frais, factures fournisseurs.
 */
$subtotal_ht = 0;
$subtotal_ttc = 0;
if ($modecompta == 'CREANCES-DETTES') { 
    $sql  = "SELECT sum(f.total_ht) as amount_ht, sum(f.total_ttc) as amount_ttc, date_format(f.datef,'%Y-%m') as dm";
    $sql .= " FROM ".MAIN_DB_PREFIX."societe as s,".MAIN_DB_PREFIX."facture_fourn as f";
    $sql .= " WHERE f.fk_soc = s.rowid AND f.fk_statut in (1,2)";
} else {
	$sql = "SELECT sum(p.amount) as amount_ttc, date_format(p.datep,'%Y-%m') as dm";
	$sql .= " FROM ".MAIN_DB_PREFIX."paiementfourn as p";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."facture_fourn as f";
	$sql .= " ON f.rowid = p.fk_facture_fourn";
	$sql .= " WHERE 1=1";
}
if ($socid)
{
  $sql .= " AND f.fk_soc = $socid";
}
$sql .= " GROUP BY dm";

$result=$db->query($sql);
if ($result)
{
    $num = $db->num_rows($result);
    $i = 0; 
    while ($i < $num)
    {
        $row = $db->fetch_object($result);

        $decaiss[$row->dm] = $row->amount_ht;
        $decaiss_ttc[$row->dm] = $row->amount_ttc;

		// For DEBUG Only
		if (eregi('^2007',$row->dm))
		{
		$subtotal_ht = $subtotal_ht + $row->amount_ht;
		$subtotal_ttc = $subtotal_ttc + $row->amount_ttc;
		}

        $i++;
    }
    $db->free($result);
}
else {
	dolibarr_print_error($db);	
}
/*
print "<br>Facture fournisseurs: subtotal_ht=".$subtotal_ht.' - subtotal_ttc='.$subtotal_ttc."<br>\n";
for ($mois = 1 ; $mois <= 12 ; $mois++)
{
	$annee = 2007;
	$case = strftime("%Y-%m",dolibarr_mktime(12,0,0,$mois,1,$annee));
	print 'Mois '.$mois.': '.$decaiss_ttc[$case].' ';
	print 'Mois '.$mois.': '.$encaiss_ttc[$case].' ';
}
*/
		
/*
 * TVA
 */
$subtotal_ht = 0;
$subtotal_ttc = 0;
if ($modecompta == 'CREANCES-DETTES') {
    // TVA à payer
    $sql = "SELECT sum(f.tva) as amount, date_format(f.datef,'%Y-%m') as dm"; 
    $sql .= " FROM ".MAIN_DB_PREFIX."facture as f";
    $sql .= " WHERE f.fk_statut in (1,2)";
    $sql .= " GROUP BY dm DESC";
    $result=$db->query($sql);
    if ($result) {
        $num = $db->num_rows($result);
        $var=false;
        $i = 0;
        if ($num) {    
          while ($i < $num) {
            $obj = $db->fetch_object($result);
        
            $decaiss[$obj->dm] += $obj->amount;
            $decaiss_ttc[$obj->dm] += $obj->amount;
        
            $i++;
          }
        }
    } else {
        dolibarr_print_error($db);
    }
    // TVA à récupérer
    $sql = "SELECT sum(f.total_tva) as amount, date_format(f.datef,'%Y-%m') as dm"; 
    $sql .= " FROM ".MAIN_DB_PREFIX."facture_fourn as f";
    $sql .= " WHERE f.fk_statut in (1,2)";
    $sql .= " GROUP BY dm";
    $result=$db->query($sql);
    if ($result) {
        $num = $db->num_rows($result);
        $var=false;
        $i = 0;
        if ($num) {    
          while ($i < $num) {
            $obj = $db->fetch_object($result);
        
            $encaiss[$obj->dm] += $obj->amount;
            $encaiss_ttc[$obj->dm] += $obj->amount;
        
            $i++;
          }
        }
    } else {
        dolibarr_print_error($db);
    }
}
else {
    // TVA réellement déja payée
    $sql = "SELECT sum(t.amount) as amount, date_format(t.datev,'%Y-%m') as dm"; 
    $sql .= " FROM ".MAIN_DB_PREFIX."tva as t";
    $sql .= " WHERE amount > 0";
    $sql .= " GROUP BY dm";
    $result=$db->query($sql);
    if ($result) {
        $num = $db->num_rows($result);
        $var=false;
        $i = 0;
        if ($num) {    
          while ($i < $num) {
            $obj = $db->fetch_object($result);
        
            $decaiss[$obj->dm] += $obj->amount;
            $decaiss_ttc[$obj->dm] += $obj->amount;
			// For DEBUG Only
			if (eregi('^2007',$obj->dm))
			{
			$subtotal_ht = $subtotal_ht + $obj->amount;
			$subtotal_ttc = $subtotal_ttc + $obj->amount;
			}
        
            $i++;
          }
        }
    } else {
        dolibarr_print_error($db);
    }
    // TVA récupérée
    $sql = "SELECT sum(t.amount) as amount, date_format(t.datev,'%Y-%m') as dm"; 
    $sql .= " FROM ".MAIN_DB_PREFIX."tva as t";
    $sql .= " WHERE amount < 0";
    $sql .= " GROUP BY dm";
    $result=$db->query($sql);
    if ($result) {
        $num = $db->num_rows($result);
        $var=false;
        $i = 0;
        if ($num) {    
          while ($i < $num) {
            $obj = $db->fetch_object($result);
        
            $encaiss[$obj->dm] += $obj->amount;
            $encaiss_ttc[$obj->dm] += $obj->amount;
			// For DEBUG Only
			if (eregi('^2007',$obj->dm))
			{
			$subtotal_ht = $subtotal_ht + $obj->amount;
			$subtotal_ttc = $subtotal_ttc + $obj->amount;
			}
        
            $i++;
          }
        }
    } else {
        dolibarr_print_error($db);
    }
}
/*
print "<br>TVA: subtotal_ht=".$subtotal_ht.' - subtotal_ttc='.$subtotal_ttc."<br>\n";
for ($mois = 1 ; $mois <= 12 ; $mois++)
{
	$annee = 2007;
	$case = strftime("%Y-%m",dolibarr_mktime(12,0,0,$mois,1,$annee));
	print 'Mois '.$mois.': '.$decaiss_ttc[$case].' ';
	print 'Mois '.$mois.': '.$encaiss_ttc[$case].' ';
}
*/

/*
 * Charges sociales non déductibles
 */
$subtotal_ht = 0;
$subtotal_ttc = 0;
if ($modecompta == 'CREANCES-DETTES') {
    $sql = "SELECT c.libelle as nom, date_format(s.date_ech,'%Y-%m') as dm, sum(s.amount) as amount_ht, sum(s.amount) as amount_ttc";
    $sql .= " FROM ".MAIN_DB_PREFIX."c_chargesociales as c, ".MAIN_DB_PREFIX."chargesociales as s";
    $sql .= " WHERE s.fk_type = c.id AND c.deductible=0";
    if ($year) {
    	$sql .= " AND s.date_ech between '$year-01-01 00:00:00' and '$year-12-31 23:59:59'";
    }
    $sql .= " GROUP BY c.libelle, dm";
}
else {
    $sql = "SELECT c.libelle as nom, date_format(p.datep,'%Y-%m') as dm, sum(p.amount) as amount_ht, sum(p.amount) as amount_ttc";
    $sql .= " FROM ".MAIN_DB_PREFIX."c_chargesociales as c, ".MAIN_DB_PREFIX."chargesociales as s, ".MAIN_DB_PREFIX."paiementcharge as p";
    $sql .= " WHERE p.fk_charge = s.rowid AND s.fk_type = c.id AND c.deductible=0";
    if ($year) {
    	$sql .= " AND p.datep between '$year-01-01 00:00:00' and '$year-12-31 23:59:59'";
    }
    $sql .= " GROUP BY c.libelle, dm";
}
$result=$db->query($sql);

if ($result) {
    $num = $db->num_rows($result);
    $var=false;
    $i = 0;
    if ($num) {    
      while ($i < $num) {
        $obj = $db->fetch_object($result);
    
        $decaiss[$obj->dm] += $obj->amount_ht;
        $decaiss_ttc[$obj->dm] += $obj->amount_ttc;

		// For DEBUG Only
		if (eregi('^2007',$obj->dm))
		{
		$subtotal_ht = $subtotal_ht + $obj->amount_ht;
		$subtotal_ttc = $subtotal_ttc + $obj->amount_ttc;
		}
   
        $i++;
      }
    }
} else {
  dolibarr_print_error($db);
}
/*
print "<br>Charges sociales non deduc: subtotal_ht=".$subtotal_ht.' - subtotal_ttc='.$subtotal_ttc."<br>\n";
for ($mois = 1 ; $mois <= 12 ; $mois++)
{
	$annee = 2007;
	$case = strftime("%Y-%m",dolibarr_mktime(12,0,0,$mois,1,$annee));
	print 'Mois '.$mois.': '.$decaiss_ttc[$case].' ';
	print 'Mois '.$mois.': '.$encaiss_ttc[$case].' ';
}
*/

/*
 * Charges sociales déductibles
 */
$subtotal_ht = 0;
$subtotal_ttc = 0;
if ($modecompta == 'CREANCES-DETTES')
{
    $sql = "SELECT c.libelle as nom, date_format(s.date_ech,'%Y-%m') as dm, sum(s.amount) as amount_ht, sum(s.amount) as amount_ttc";
    $sql .= " FROM ".MAIN_DB_PREFIX."c_chargesociales as c, ".MAIN_DB_PREFIX."chargesociales as s";
    $sql .= " WHERE s.fk_type = c.id AND c.deductible=1";
    if ($year) {
    	$sql .= " AND s.date_ech between '$year-01-01 00:00:00' and '$year-12-31 23:59:59'";
    }
    $sql .= " GROUP BY c.libelle, dm";
}
else
{
    $sql = "SELECT c.libelle as nom, date_format(p.datep,'%Y-%m') as dm, sum(p.amount) as amount_ht, sum(p.amount) as amount_ttc";
    $sql .= " FROM ".MAIN_DB_PREFIX."c_chargesociales as c, ".MAIN_DB_PREFIX."chargesociales as s, ".MAIN_DB_PREFIX."paiementcharge as p";
    $sql .= " WHERE p.fk_charge = s.rowid AND s.fk_type = c.id AND c.deductible=1";
    if ($year) {
    	$sql .= " AND p.datep between '$year-01-01 00:00:00' and '$year-12-31 23:59:59'";
    }
    $sql .= " GROUP BY c.libelle, dm";
}
$result=$db->query($sql);
if ($result) {
    $num = $db->num_rows($result);
    $var=false;
    $i = 0;
    if ($num) {
        while ($i < $num) {
        $obj = $db->fetch_object($result);
        
        $decaiss[$obj->dm] += $obj->amount_ht;
        $decaiss_ttc[$obj->dm] += $obj->amount_ttc;

		// For DEBUG Only
		if (eregi('^2007',$obj->dm))
		{
		$subtotal_ht = $subtotal_ht + $obj->amount_ht;
		$subtotal_ttc = $subtotal_ttc + $obj->amount_ttc;
		}
        
        $i++;
        }
    }
} else {
  dolibarr_print_error($db);
}
/*
print "<br>Charges sociales deduc: subtotal_ht=".$subtotal_ht.' - subtotal_ttc='.$subtotal_ttc."<br>\n";
for ($mois = 1 ; $mois <= 12 ; $mois++)
{
	$annee = 2007;
	$case = strftime("%Y-%m",dolibarr_mktime(12,0,0,$mois,1,$annee));
	print 'Mois '.$mois.': '.$decaiss_ttc[$case].' ';
	print 'Mois '.$mois.': '.$encaiss_ttc[$case].' ';
}
*/


/*
 * Affiche tableau
 */
$totentrees=array();
$totsorties=array();

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre"><td rowspan=2>'.$langs->trans("Month").'</td>';

for ($annee = $year_start ; $annee <= $year_end ; $annee++)
{
  print '<td align="center" colspan="2"><a href="clientfourn.php?year='.$annee.'">'.$annee.'</a></td>';
}
print '</tr>';
print '<tr class="liste_titre">';
for ($annee = $year_start ; $annee <= $year_end ; $annee++)
{
  print '<td align="right">'.$langs->trans("Outcome").'</td>';
  print '<td align="right">'.$langs->trans("Income").'</td>';
}
print '</tr>';

$var=True;
for ($mois = 1 ; $mois <= 12 ; $mois++)
{
	$var=!$var;
	print '<tr '.$bc[$var].'>';
	print "<td>".strftime("%B",dolibarr_mktime(12,0,0,$mois,1,$annee))."</td>";
	for ($annee = $year_start ; $annee <= $year_end ; $annee++)
	{
		$case = strftime("%Y-%m",dolibarr_mktime(12,0,0,$mois,1,$annee));

		print '<td align="right">&nbsp;';
		if ($decaiss_ttc[$case] != 0)
		{
			print price($decaiss_ttc[$case]);
			$totsorties[$annee]+=$decaiss_ttc[$case];
		}
		print "</td>";

		print '<td align="right">&nbsp;';
		if ($encaiss_ttc[$case] != 0)
		{
			print price($encaiss_ttc[$case]);
			$totentrees[$annee]+=$encaiss_ttc[$case];
		}
		print "</td>";
	}

	print '</tr>';
}

// Total
$var=!$var;
$nbcols=0;
print '<tr class="liste_total"><td>'.$langs->trans("TotalTTC").'</td>';
for ($annee = $year_start ; $annee <= $year_end ; $annee++)
{
    $nbcols+=2;
    print '<td align="right">'.(isset($totsorties[$annee])?price($totsorties[$annee]):'&nbsp;').'</td>';
    print '<td align="right">'.(isset($totentrees[$annee])?price($totentrees[$annee]):'&nbsp;').'</td>';
}
print "</tr>\n";

// Ligne vierge
print '<tr><td>&nbsp;</td>';
print '<td colspan="'.$nbcols.'">&nbsp;</td>';
print "</tr>\n";

// Balance
$var=!$var;
print '<tr class="liste_total"><td>'.$langs->trans("Profit").'</td>';
for ($annee = $year_start ; $annee <= $year_end ; $annee++)
{
    print '<td align="right" colspan="2"> ';
    if (isset($totentrees[$annee]) || isset($totsorties[$annee])) {
        print price($totentrees[$annee]-$totsorties[$annee]).'</td>';
//  print '<td>&nbsp;</td>';
    }
}
print "</tr>\n";

print "</table>";

$db->close();

llxFooter('$Date$ - $Revision$');

?>
