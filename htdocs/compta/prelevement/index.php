<?php
/* Copyright (C) 2004-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2011      Juanjo Menent		<jmenent@2byte.es>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/compta/prelevement/index.php
 *	\brief      Prelevement
 */

require("../bank/pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/compta/prelevement/class/bon-prelevement.class.php");
require_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");
require_once(DOL_DOCUMENT_ROOT."/societe/class/societe.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/prelevement.lib.php");

$langs->load("withdrawals");
$langs->load("categories");

// Security check
$socid = GETPOST('socid','int');
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'prelevement','','');

/*
 * Actions
 */




/*
 * View
 */

llxHeader('',$langs->trans("CustomersStandingOrdersArea"));

if (prelevement_check_config() < 0)
{
	$langs->load("errors");
	print '<div class="error">';
	print $langs->trans("ErrorModuleSetupNotComplete");
	print '</div>';
}

print_fiche_titre($langs->trans("CustomersStandingOrdersArea"));

print '<table border="0" width="100%" class="notopnoleftnoright">';

print '<tr><td valign="top" width="30%" class="notopnoleft">';

$thirdpartystatic=new Societe($db);
$invoicestatic=new Facture($db);
$bprev = new BonPrelevement($db);
$var=true;

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("Statistics").'</td></tr>';
$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("NbOfInvoiceToWithdraw").'</td>';
print '<td align="right">';
print '<a href="'.DOL_URL_ROOT.'/compta/prelevement/demandes.php?status=0">';
print $bprev->NbFactureAPrelever();
print '</a>';
print '</td></tr>';
$var=!$var;
print '<tr class="liste_total"><td>'.$langs->trans("AmountToWithdraw").'</td>';
print '<td align="right">';
print price($bprev->SommeAPrelever());
print '</td></tr></table><br>';

print '</td><td valign="top" width="70%">';



/*
 * Withdraw receipts
 */
$limit=5;
$sql = "SELECT p.rowid, p.ref, p.amount, p.datec";
$sql .= " ,p.statut ";
$sql .= " FROM ".MAIN_DB_PREFIX."prelevement_bons as p";
$sql .= " ORDER BY datec DESC LIMIT ".$limit;

$result = $db->query($sql);
if ($result)
{
    $num = $db->num_rows($result);
    $i = 0;
    $var=True;

    print"\n<!-- debut table -->\n";
    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre"><td>'.$langs->trans("LastWithdrawalReceipt",$limit).'</td>';
    print '<td>'.$langs->trans("Date").'</td>';
    print '<td align="right">'.$langs->trans("Amount").'</td>';
    print '</tr>';

    while ($i < min($num,$limit))
    {
        $obj = $db->fetch_object($result);
        $var=!$var;

        print "<tr $bc[$var]><td>";

        print '<img border="0" src="./img/statut'.$obj->statut.'.png"></a>&nbsp;';

        print '<a href="fiche.php?id='.$obj->rowid.'">'.$obj->ref."</a></td>\n";

        print '<td>'.dol_print_date($db->jdate($obj->datec),"dayhour")."</td>\n";

        print '<td align="right">'.price($obj->amount)."</td>\n";

        print "</tr>\n";
        $i++;
    }
    print "</table><br>";
    $db->free($result);
}
else
{
    dol_print_error($db);
}

/*
 * Invoices waiting for withdraw
 */
$sql = "SELECT f.facnumber, f.rowid, f.total_ttc, f.fk_statut, f.paye, f.type,";
$sql.= " pfd.date_demande,";
$sql.= " s.nom, s.rowid as socid";
$sql.= " FROM ".MAIN_DB_PREFIX."facture as f,";
$sql.= " ".MAIN_DB_PREFIX."societe as s";
if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql.= " , ".MAIN_DB_PREFIX."prelevement_facture_demande as pfd";
$sql.= " WHERE s.rowid = f.fk_soc";
$sql.= " AND f.entity = ".$conf->entity;
$sql.= " AND pfd.traite = 0 AND pfd.fk_facture = f.rowid";
if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
if ($socid) $sql.= " AND f.fk_soc = ".$socid;

$resql=$db->query($sql);
if ($resql)
{
    $num = $db->num_rows($resql);
    $i = 0;

    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre">';
    print '<td colspan="5">'.$langs->trans("InvoiceWaitingWithdraw").' ('.$num.')</td></tr>';
    if ($num)
    {
        $var = True;
        while ($i < $num && $i < 20)
        {
            $obj = $db->fetch_object($resql);

            $invoicestatic->id=$obj->rowid;
            $invoicestatic->ref=$obj->facnumber;
            $invoicestatic->statut=$obj->fk_statut;
            $invoicestatic->paye=$obj->paye;
            $invoicestatic->type=$obj->type;
            $alreadypayed=$invoicestatic->getSommePaiement();

            $var=!$var;
            print '<tr '.$bc[$var].'><td>';
            print $invoicestatic->getNomUrl(1,'withdraw');
            print '</td>';

            print '<td>';
            $thirdpartystatic->id=$obj->socid;
            $thirdpartystatic->nom=$obj->nom;
            print $thirdpartystatic->getNomUrl(1,'customer');
            print '</td>';

            print '<td align="right">';
            print price($obj->total_ttc);
            print '</td>';

            print '<td align="right">';
            print dol_print_date($db->jdate($obj->date_demande),'day');
            print '</td>';

            print '<td align="right">';
            print $invoicestatic->getLibStatut(3,$alreadypayed);
            print '</td>';
            print '</tr>';
            $i++;
        }
    }
    else
    {
        print '<tr><td colspan="2">'.$langs->trans("NoInvoiceToWithdraw").'</td></tr>';
    }
    print "</table><br>";
}
else
{
    dol_print_error($db);
}


print '</td></tr></table>';

llxFooter();
?>
