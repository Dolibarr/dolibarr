<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Éric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2004-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2006      Yannick Warnier      <ywarnier@beeznest.org>
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
	    \file       htdocs/compta/tva/clients.php
        \ingroup    compta
		\brief      Page des societes
		\version    $Revision$
*/

require("./pre.inc.php");
require("../../tva.class.php");

$year=$_GET["year"];
if ($year == 0 or $year!=intval(strval($year)))
{
  $year_current = strftime("%Y",time());
  $year_start = $year_current;
} else {
  $year_current = $year;
  $year_start = $year;
}

$min = $_GET["min"];
if($min == 0 or $min!=floatval(strval($min))){
	$min = 0.00;
}else{
	//keep min
}

/**
 * 	Look for collectable VAT clients in the chosen year
 *	@param		resource	Database handle
 *	@param		int			Year
 */
function tva_coll($db,$y)
{
	global $conf;
	
    if ($conf->compta->mode == "CREANCES-DETTES")
    {
        // Si on paye la tva sur les factures dues (non brouillon)
        $sql = "SELECT s.nom as nom, s.tva_intra as tva_intra, sum(f.total) as amount, sum(f.tva) as tva, s.tva_assuj as assuj, s.rowid as socid";
        $sql.= " FROM ".MAIN_DB_PREFIX."facture as f, ".MAIN_DB_PREFIX."societe as s";
        $sql.= " WHERE ";
        $sql.= " f.fk_statut in (1,2)";
        $sql.= " AND date_format(f.datef,'%Y') = ".$y;
        $sql.= " AND s.rowid = f.fk_soc ";
        $sql.= " GROUP BY s.rowid";
    }
    else
    {
        // Si on paye la tva sur les payments
    
        // \todo a ce jour on se sait pas la compter car le montant tva d'un payment
        // n'est pas stocké dans la table des payments.
        // Seul le module compta expert peut résoudre ce problème.
        // (Il faut quand un payment a lieu, stocker en plus du montant du paiement le
        // detail part tva et part ht).
        
/*
        // Tva sur factures payés
        $sql = "SELECT sum(f.tva) as amount";
        $sql.= " FROM ".MAIN_DB_PREFIX."facture as f";
        $sql.= " WHERE ";
        $sql.= " f.paye = 1";
        $sql.= " AND date_format(f.datef,'%Y') = ".$y;
        $sql.= " AND date_format(f.datef,'%m') = ".$m;
*/
    }

    $resql = $db->query($sql);

    if ($resql)
    {
    	$list = array();
    	while($assoc = $db->fetch_array($resql)){
        	$list[] = $assoc;
    	}
    	return $list;
    }
    else
    {
        dolibarr_print_error($db);
    }
}


/**
 * 	Get payable VAT
 *	@param		resource	Database handle
 *	@param		int			Year
 */
function tva_paye($db, $y)
{
	global $conf;

    if ($conf->compta->mode == "CREANCES-DETTES")
    {
        // Si on paye la tva sur les factures dues (non brouillon)
        $sql = "SELECT s.nom as nom, s.tva_intra as tva_intra, sum(f.total_ht) as amount, sum(f.tva) as tva, s.tva_assuj as assuj, s.rowid as socid";
        $sql.= " FROM ".MAIN_DB_PREFIX."facture_fourn as f, ".MAIN_DB_PREFIX."societe as s";
        $sql.= " WHERE ";
        $sql.= " f.fk_statut in (1,2)";
        $sql.= " AND date_format(f.datef,'%Y') = ".$y;
        $sql.= " AND s.rowid = f.fk_soc ";
        $sql.= " GROUP BY s.rowid";
    }
    else
    {
        // Si on paye la tva sur les payments
    
        // \todo a ce jour on se sait pas la compter car le montant tva d'un payment
        // n'est pas stocké dans la table des payments.
        // Seul le module compta expert peut résoudre ce problème.
        // (Il faut quand un payment a lieu, stocker en plus du montant du paiement le
        // detail part tva et part ht).
        
/*

        // \todo a ce jour on se sait pas la compter car le montant tva d'un payment
        // n'est pas stocké dans la table des payments.
        // Il faut quand un payment a lieu, stocker en plus du montant du paiement le
        // detail part tva et part ht.
        
        // Tva sur factures payés
        $sql = "SELECT sum(f.total_tva) as amount";
        $sql.= " FROM ".MAIN_DB_PREFIX."facture_fourn as f";
  //      $sql.= " WHERE ";
    $sql .= " WHERE f.fk_statut in (1,2)";
//        $sql.= " f.paye = 1";
        $sql.= " AND date_format(f.datef,'%Y') = $y";
        $sql.= " AND date_format(f.datef,'%m') = $m";
	//print "xx $sql";
*/
    }

    $resql = $db->query($sql);
    if ($resql)
    {
    	$list = array();
    	while($assoc = $db->fetch_array($resql)){
        	$list[] = $assoc;
    	}
    	return $list;
    }
    else
    {
        dolibarr_print_error($db);
    }
}

/**
 * Print VAT tables
 * @param	resource	Database handler
 * @param	string		SQL query
 * @param	string		Date
 */
function pt ($db, $sql, $date)
{
    global $conf, $bc,$langs;

    $result = $db->query($sql);
    if ($result)
    {
        $num = $db->num_rows($result);
        $i = 0;
        $total = 0;
        print "<table class=\"noborder\" width=\"100%\">";
        print "<tr class=\"liste_titre\">";
        print "<td nowrap width=\"60%\">$date</td>";
        print "<td align=\"right\">".$langs->trans("Amount")."</td>";
        print "<td>&nbsp;</td>\n";
        print "</tr>\n";
        $var=True;
        while ($i < $num)
        {
            $obj = $db->fetch_object($result);
            $var=!$var;
            print "<tr $bc[$var]>";
            print "<td nowrap>$obj->dm</td>\n";
            $total = $total + $obj->amount;

            print "<td nowrap align=\"right\">".price($obj->amount)."</td><td nowrap align=\"right\">".$total."</td>\n";
            print "</tr>\n";

            $i++;
        }
        print "<tr class=\"liste_total\"><td align=\"right\">".$langs->trans("Total")." :</td><td nowrap align=\"right\"><b>".price($total)."</b></td><td>&nbsp;</td></tr>";

        print "</table>";
        $db->free($result);
    }
    else {
        dolibar_print_error($db);
    }
}


/*
 * Code
 */

llxHeader();

$textprevyear="<a href=\"clients.php?year=" . ($year_current-1) . "\">".img_previous()."</a>";
$textnextyear=" <a href=\"clients.php?year=" . ($year_current+1) . "\">".img_next()."</a>";

print_fiche_titre($langs->trans("VAT"),"$textprevyear ".$langs->trans("Year")." $year_start $textnextyear");

echo '<form method="get" action="clients.php?year='.$year.'">';
echo '  <input type="hidden" name="year" value="'.$year.'">';
echo '  <label for="min">'.$langs->trans("Minimum").': </label>';
echo '  <input type="text" name="min" value="'.$min.'">';
echo '  <input type="submit" name="submit" value="'.$langs->trans("Chercher").'">';
echo '</form>';

echo '<table width="100%">';
echo '<tr><td>';
print_fiche_titre($langs->trans("VATSummary"));
//echo '</td><td>';
//print_fiche_titre($langs->trans("VATPayed"));
echo '</td></tr>';

//echo '<tr><td width="50%" valign="top">';
echo '<tr>';

print "<table class=\"noborder\" width=\"100%\">";
print "<tr class=\"liste_titre\">";
print "<td align=\"right\"></td>";
print "<td>".$langs->trans("Name")."</td>";
print "<td>".$langs->trans("VATIntra")."</td>";
print "<td align=\"right\">".$langs->trans("CA")."</td>";
print "<td align=\"right\">".$langs->trans("VATToPay")."</td>";
print "</tr>\n";

if ($conf->compta->mode == "CREANCES-DETTES")
{
	$y = $year_current ;
	
	
	$var=True;
	$total = 0;  $subtotal = 0;
	$var=!$var;
	$coll_list = tva_coll($db,$y);
	$i = 1;
	foreach($coll_list as $coll){
		if($min == 0 or ($min>0 and $coll[2]>$min)){
			$var=!$var;
			$intra = str_replace($find,$replace,$coll[1]);
			if(empty($intra)){
				if($coll[4] == '1'){
					$intra = $langs->trans('Unknown');
				}else{
					$intra = $langs->trans('NotRegistered');
				}
			}
			print "<tr $bc[$var]>";
			print "<td nowrap>".$i."</td>";		
			print '<td nowrap><a href="../../soc.php?socid='.$coll[5].'">'.$coll[0].'</td>';
			$find = array(' ','.');
			$replace = array('','');
			print "<td nowrap>".$intra."</td>";
			print "<td nowrap align=\"right\">".price($coll[2])."</td>";
			print "<td nowrap align=\"right\">".price($coll[3])."</td>";
			$total = $total + $coll[3];
			print "</tr>\n";
			$i++;
		}
	}

	print '<tr class="liste_total"><td align="right" colspan="4">'.$langs->trans("TotalToPay").':</td><td nowrap align="right"><b>'.price($total).'</b></td>';
	print '</tr>';

}
else
{
	print '<tr><td colspan="5">'.$langs->trans("FeatureNotYetAvailable").'</td></tr>';
	print '<tr><td colspan="5">'.$langs->trans("FeatureIsSupportedInInOutModeOnly").'</td></tr>';
}

print '</table>';
	
	
//echo '</td><td valign="top" width="50%">';
	
	
/*
* Réglée
*/

/*
$sql = "SELECT amount, date_format(f.datev,'%Y-%m') as dm";
$sql .= " FROM ".MAIN_DB_PREFIX."tva as f WHERE f.datev >= '$y-01-01' AND f.datev <= '$y-12-31' ";
$sql .= " GROUP BY dm DESC";

pt($db, $sql,$langs->trans("Year")." $y");


print "</td></tr></table>";
*/
echo '</td></tr>';
echo '</table>';


$db->close();

llxFooter('$Date$ - $Revision$');
?>
