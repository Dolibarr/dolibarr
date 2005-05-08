<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 *
 */

/**
	    \file       htdocs/compta/tva/index.php
        \ingroup    compta
		\brief      Page des societes
		\version    $Revision$
*/

require("./pre.inc.php");
require("../../tva.class.php");

/*
 *
 */
function tva_coll($db, $y,$m) {
  $sql = "SELECT sum(f.tva) as amount"; 
  $sql .= " FROM ".MAIN_DB_PREFIX."facture as f WHERE f.paye = 1";
  $sql .= " AND date_format(f.datef,'%Y') = $y";
  $sql .= " AND date_format(f.datef,'%m') = $m";

  $result = $db->query($sql);
  if ($result) {
    $obj = $db->fetch_object($result);
    return $obj->amount;
  }
}

/*
 *
 */
function tva_paye($db, $y,$m)
{
  $sql = "SELECT sum(f.total_tva) as amount"; 
  $sql .= " FROM ".MAIN_DB_PREFIX."facture_fourn as f WHERE f.paye = 1";
  $sql .= " AND date_format(f.datef,'%Y') = $y";
  $sql .= " AND date_format(f.datef,'%m') = $m";

  $result = $db->query($sql);
  if ($result)
    {
      $obj = $db->fetch_object($result);
      return $obj->amount;
    }
}

function pt ($db, $sql, $date) {
    global $bc,$langs;

    $result = $db->query($sql);
    if ($result) {
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
        while ($i < $num) {
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
        } else {
            dolibar_print_error($db);
        }
    }


/*
 *
 */

llxHeader();

$tva = new Tva($db);

$year=$_GET["year"];
if ($year == 0 ) {
  $year_current = strftime("%Y",time());
  $year_start = $year_current;
} else {
  $year_current = $year;
  $year_start = $year;
}

$textprevyear="<a href=\"index.php?year=" . ($year_current-1) . "\">".img_previous()."</a>";
$textnextyear=" <a href=\"index.php?year=" . ($year_current+1) . "\">".img_next()."</a>";

print_fiche_titre($langs->trans("VAT"),"$textprevyear ".$langs->trans("Year")." $year_start $textnextyear");


echo '<table width="100%">';
echo '<tr><td>';
print_fiche_titre($langs->trans("VATSummary"));
echo '</td><td>';
print_fiche_titre($langs->trans("VATPayed"));
echo '</td></tr>';

for ($y = $year_current ; $y >= $year_start ; $y=$y-1 ) {

  echo '<tr><td width="50%" valign="top">';

  print "<table class=\"noborder\" width=\"100%\">";
  print "<tr class=\"liste_titre\">";
  print "<td width=\"30%\">".$langs->trans("Year")." $y</td>";
  print "<td align=\"right\">".$langs->trans("VATToPay")."</td>";
  print "<td align=\"right\">".$langs->trans("VATToCollect")."</td>";
  print "<td align=\"right\">".$langs->trans("VATToCollect")."</td>";
  print "</tr>\n";
  $var=True;
  $total = 0;  $subtotal = 0;
  $i=0;
  for ($m = 1 ; $m < 13 ; $m++ ) {
    $var=!$var;
    print "<tr $bc[$var]>";
    print '<td nowrap>'.strftime("%b %Y",mktime(0,0,0,$m,1,$y)).'</td>';
    
    $x_coll = tva_coll($db, $y, $m);
    print "<td nowrap align=\"right\">".price($x_coll)."</td>";
    
    $x_paye = tva_paye($db, $y, $m);
    print "<td nowrap align=\"right\">".price($x_paye)."</td>";
    
    $diff = $x_coll - $x_paye;
    $total = $total + $diff;
    $subtotal = $subtotal + $diff;
    
    print "<td nowrap align=\"right\">".price($diff)."</td>\n";
    print "</tr>\n";
    
    $i++;
    if ($i > 2) {
      print '<tr class="liste_total"><td align="right" colspan="3">'.$langs->trans("SubTotal").':</td><td nowrap align="right">'.price($subtotal).'</td></tr>';
      $i = 0;
      $subtotal = 0;
    }
  }
  print '<tr class="liste_total"><td align="right" colspan="3">'.$langs->trans("TotalToPay").':</td><td nowrap align="right"><b>'.price($total).'</b></td>';
  print "<td>&nbsp;</td>\n";
  print "</table>";


  echo '</td><td valign="top" width="50%">';


  /*
   * Réglée
   */

  $sql = "SELECT amount, date_format(f.datev,'%Y-%m') as dm";
  $sql .= " FROM ".MAIN_DB_PREFIX."tva as f WHERE f.datev >= '$y-01-01' AND f.datev <= '$y-12-31' ";
  $sql .= " GROUP BY dm DESC";
  
  pt($db, $sql,$langs->trans("Year")." $y");

  
  print "</td></tr></table>";

  echo '</td></tr>';
}


echo '</table>';



$db->close();

llxFooter('$Date$ - $Revision$');
?>
