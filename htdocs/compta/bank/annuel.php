<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *		\file        htdocs/compta/bank/annuel.php
 *		\ingroup     banque
 *		\brief       Page reporting mensuel Entrées/Sorties d'un compte bancaire
 *		\version     $Id$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/bank.lib.php");

if (!$user->rights->banque->lire) accessforbidden();

$year_start=isset($_GET["year_start"])?$_GET["year_start"]:$_POST["year_start"];
$year_current = strftime("%Y",time());
if (! $year_start)
{
  $year_start = $year_current - 2;
  $year_end = $year_current;
}
else
{
  $year_end=$year_start+2;   
}

// Sécurité accés client
if ($user->societe_id > 0) 
{
  $socid = $user->societe_id;
}



llxHeader();

$form = new Form($db);

// Récupère info du compte
$acct = new Account($db);
if ($_GET["account"]) 
{
	$result=$acct->fetch($_GET["account"]);
}
if ($_GET["ref"]) 
{
	$result=$acct->fetch(0,$_GET["ref"]);
	$_GET["account"]=$acct->id;
}


# Ce rapport de trésorerie est basé sur llx_bank (car doit inclure les transactions sans facture)
# plutot que sur llx_paiement + llx_paiementfourn

$sql = "SELECT sum(f.amount), date_format(f.dateo,'%Y-%m') as dm";
$sql .= " FROM llx_bank as f";
$sql .= " WHERE f.amount >= 0";
if ($_GET["account"]) { $sql .= " AND fk_account = ".$_GET["account"]; }
$sql .= " GROUP BY dm";

$resql=$db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);
	$i = 0;
	while ($i < $num)
	{
		$row = $db->fetch_row($resql);
		$encaiss[$row[1]] = $row[0];
		$i++;
	}
}
else
{
	dolibarr_print_error($db);
}

$sql = "SELECT sum(f.amount), date_format(f.dateo,'%Y-%m') as dm";
$sql .= " FROM llx_bank as f";
$sql .= " WHERE f.amount <= 0";
if ($_GET["account"]) { $sql .= " AND fk_account = ".$_GET["account"]; }
$sql .= " GROUP BY dm";
$resql=$db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);
	$i = 0;
	while ($i < $num)
	{
		$row = $db->fetch_row($resql);
		$decaiss[$row[1]] = -$row[0];
		$i++;
	}
}
else
{
	dolibarr_print_error($db);
}


// Onglets
$head=bank_prepare_head($acct);
dolibarr_fiche_head($head,'annual',$langs->trans("FinancialAccount"),0);

$title=$langs->trans("FinancialAccount")." : ".$acct->label;
$lien=($year_start?"<a href='annuel.php?account=".$acct->id."&year_start=".($year_start-1)."'>".img_previous()."</a> ".$langs->trans("Year")." <a href='annuel.php?account=".$acct->id."&year_start=".($year_start+1)."'>".img_next()."</a>":"");

print '<table class="border" width="100%">';

// Ref
print '<tr><td valign="top" width="25%">'.$langs->trans("Ref").'</td>';
print '<td colspan="3">';
print $form->showrefnav($acct,'ref','',1,'ref');
print '</td></tr>';

// Label
print '<tr><td valign="top">'.$langs->trans("Label").'</td>';
print '<td colspan="3">'.$acct->label.'</td></tr>';

print '</table>';

print '<br>';

// Affiche tableau
print '<table class="noborder" width="100%">';
print '<tr><td colspan="'.(1+($year_end-$year_start+1)*2).'" align="right">'.$lien.'</td></tr>';
print '<tr class="liste_titre"><td rowspan=2>'.$langs->trans("Month").'</td>';

for ($annee = $year_start ; $annee <= $year_end ; $annee++)
{
  print '<td align="center" width="20%" colspan="2">'.$annee.'</td>';
}
print '</tr>';
print '<tr class="liste_titre">';
for ($annee = $year_start ; $annee <= $year_end ; $annee++)
{ 
  print '<td align="right">'.$langs->trans("Debit").'</td><td align="right">'.$langs->trans("Credit").'</td>';
}
print '</tr>';

$var=true;
for ($mois = 1 ; $mois < 13 ; $mois++)
{
    $var=!$var;
    print '<tr '.$bc[$var].'>';
    print "<td>".dolibarr_print_date(dolibarr_mktime(1,1,1,$mois,1,2000),"%B")."</td>";
    for ($annee = $year_start ; $annee <= $year_end ; $annee++)
    {
        $case = sprintf("%04s-%02s",$annee,$mois);
    	
        print '<td align="right" width="10%">&nbsp;';
        if ($decaiss[$case]>0)
        {
            print price($decaiss[$case]);
            $totsorties[$annee]+=$decaiss[$case];
        }
        print "</td>";

        print '<td align="right" width="10%">&nbsp;';
        if ($encaiss[$case]>0)
        {
            print price($encaiss[$case]);
            $totentrees[$annee]+=$encaiss[$case];
        }
        print "</td>";
    }
    print '</tr>';
}

// Total debit-credit
print '<tr class="liste_total"><td><b>'.$langs->trans("Total")."</b></td>";
for ($annee = $year_start ; $annee <= $year_end ; $annee++)
{
  print '<td align="right"><b>'.price($totsorties[$annee]).'</b></td><td align="right"><b>'.price($totentrees[$annee]).'</b></td>';
}
print "</tr>\n";

// Ligne vierge
print '<tr><td>&nbsp;</td>';
$nbcol=0;
for ($annee = $year_start ; $annee <= $year_end ; $annee++)
{
    $nbcol+=2;
}
print "</tr>\n";

// Solde actuel
$balance=0;
$sql = "SELECT sum(f.amount) as total";
$sql.= " FROM ".MAIN_DB_PREFIX."bank as f";
if ($_GET["account"]) { $sql .= " WHERE fk_account = ".$_GET["account"]; }
$resql=$db->query($sql);
if ($resql)
{
    $obj = $db->fetch_object($resql);
    if ($obj) $balance=$obj->total;
}
else {
    dolibarr_print_error($db);
}
print '<tr class="liste_total"><td><b>'.$langs->trans("CurrentBalance")."</b></td>";
print '<td colspan="'.($nbcol).'" align="right">'.price($balance).'</td>';
print "</tr>\n";

print "</table>";

print "\n</div>\n";

$db->close();

llxFooter('$Date$ - $Revision$');

?>
