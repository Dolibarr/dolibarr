<?php
/* Copyright (C) 2004-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2011      Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2013      Florian Henry		<florian.henry@open-concept.pro>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/compta/prelevement/index.php
 *  \ingroup    prelevement
 *	\brief      Home page for direct debit orders
 */


require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/prelevement/class/bonprelevement.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/prelevement.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

// Load translation files required by the page
$langs->loadLangs(array('banks', 'categories', 'withdrawals'));

// Security check
$socid = GETPOST('socid', 'int');
if ($user->socid) $socid = $user->socid;
$result = restrictedArea($user, 'prelevement', '', '');


/*
 * Actions
 */




/*
 * View
 */

llxHeader('', $langs->trans("CustomersStandingOrdersArea"));

if (prelevement_check_config() < 0)
{
	$langs->load("errors");
	setEventMessages($langs->trans("ErrorModuleSetupNotComplete", $langs->transnoentitiesnoconv("Withdraw")), null, 'errors');
}

print load_fiche_titre($langs->trans("CustomersStandingOrdersArea"));


print '<div class="fichecenter"><div class="fichethirdleft">';


$thirdpartystatic = new Societe($db);
$invoicestatic = new Facture($db);
$bprev = new BonPrelevement($db);

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><th colspan="2">'.$langs->trans("Statistics").'</th></tr>';

print '<tr class="oddeven"><td>'.$langs->trans("NbOfInvoiceToWithdraw").'</td>';
print '<td class="right">';
print '<a href="'.DOL_URL_ROOT.'/compta/prelevement/demandes.php?status=0">';
print $bprev->nbOfInvoiceToPay('direct-debit');
print '</a>';
print '</td></tr>';

print '<tr class="oddeven"><td>'.$langs->trans("AmountToWithdraw").'</td>';
print '<td class="right">';
print price($bprev->SommeAPrelever('direct-debit'), '', '', 1, -1, -1, 'auto');
print '</td></tr></table></div><br>';



/*
 * Invoices waiting for withdraw
 */
$sql = "SELECT f.ref, f.rowid, f.total_ttc, f.fk_statut, f.paye, f.type,";
$sql .= " pfd.date_demande, pfd.amount,";
$sql .= " s.nom as name, s.email, s.rowid as socid";
$sql .= " FROM ".MAIN_DB_PREFIX."facture as f,";
$sql .= " ".MAIN_DB_PREFIX."societe as s";
if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql .= " , ".MAIN_DB_PREFIX."prelevement_facture_demande as pfd";
$sql .= " WHERE s.rowid = f.fk_soc";
$sql .= " AND f.entity IN (".getEntity('invoice').")";
$sql .= " AND f.total_ttc > 0";
if (empty($conf->global->WITHDRAWAL_ALLOW_ANY_INVOICE_STATUS))
{
	$sql .= " AND f.fk_statut = ".Facture::STATUS_VALIDATED;
}
$sql .= " AND pfd.traite = 0";
$sql .= " AND pfd.ext_payment_id IS NULL";
$sql .= " AND pfd.fk_facture = f.rowid";
if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".$user->id;
if ($socid) $sql .= " AND f.fk_soc = ".$socid;

$resql = $db->query($sql);
if ($resql)
{
    $num = $db->num_rows($resql);
    $i = 0;

    print '<div class="div-table-responsive-no-min">';
    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre">';
    print '<th colspan="5">'.$langs->trans("InvoiceWaitingWithdraw").' ('.$num.')</th></tr>';
    if ($num)
    {
        while ($i < $num && $i < 20)
        {
            $obj = $db->fetch_object($resql);

            $invoicestatic->id = $obj->rowid;
            $invoicestatic->ref = $obj->ref;
            $invoicestatic->statut = $obj->fk_statut;
            $invoicestatic->paye = $obj->paye;
            $invoicestatic->type = $obj->type;
            $alreadypayed = $invoicestatic->getSommePaiement();

            $thirdpartystatic->id = $obj->socid;
            $thirdpartystatic->name = $obj->name;
            $thirdpartystatic->email = $obj->email;

            print '<tr class="oddeven"><td>';
            print $invoicestatic->getNomUrl(1, 'withdraw');
            print '</td>';

            print '<td>';
            print $thirdpartystatic->getNomUrl(1, 'customer');
            print '</td>';

            print '<td class="right">';
            print price($obj->amount);
            print '</td>';

            print '<td class="right">';
            print dol_print_date($db->jdate($obj->date_demande), 'day');
            print '</td>';

            print '<td class="right">';
            print $invoicestatic->getLibStatut(3, $alreadypayed);
            print '</td>';
            print '</tr>';
            $i++;
        }
    }
    else
    {
        print '<tr class="oddeven"><td colspan="5" class="opacitymedium">'.$langs->trans("NoInvoiceToWithdraw", $langs->transnoentitiesnoconv("StandingOrders")).'</td></tr>';
    }
    print "</table></div><br>";
}
else
{
    dol_print_error($db);
}


print '</div><div class="fichetwothirdright"><div class="ficheaddleft">';


/*
 * Direct debit orders
 */

$limit = 5;
$sql = "SELECT p.rowid, p.ref, p.amount, p.datec, p.statut";
$sql .= " FROM ".MAIN_DB_PREFIX."prelevement_bons as p";
$sql .= " WHERE p.type = 'debit-order'";
$sql .= " AND entity IN (" . getEntity('prelevement') . ")";
$sql .= " ORDER BY datec DESC";
$sql .= $db->plimit($limit);

$result = $db->query($sql);
if ($result)
{
    $num = $db->num_rows($result);
    $i = 0;

    print"\n<!-- debut table -->\n";
    print '<div class="div-table-responsive-no-min">';
    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre">';
    print '<th>'.$langs->trans("LastWithdrawalReceipt", $limit).'</th>';
    print '<th>'.$langs->trans("Date").'</th>';
    print '<th class="right">'.$langs->trans("Amount").'</th>';
    print '<th class="right">'.$langs->trans("Status").'</th>';
    print '</tr>';

    if ($num > 0) {
    	while ($i < min($num, $limit))
	    {
	        $obj = $db->fetch_object($result);


	        print '<tr class="oddeven">';

	        print "<td>";
	        $bprev->id = $obj->rowid;
	        $bprev->ref = $obj->ref;
	        $bprev->statut = $obj->statut;
	        print $bprev->getNomUrl(1);
	        print "</td>\n";
	        print '<td>'.dol_print_date($db->jdate($obj->datec), "dayhour")."</td>\n";
	        print '<td class="right">'.price($obj->amount)."</td>\n";
	        print '<td class="right">'.$bprev->getLibStatut(3)."</td>\n";

	        print "</tr>\n";
	        $i++;
	    }
    } else {
    	print '<tr><td class="opacitymedium" colspan="4">'.$langs->trans("None").'</td></tr>';
    }

    print "</table></div><br>";
    $db->free($result);
}
else
{
    dol_print_error($db);
}


print '</div></div></div>';

// End of page
llxFooter();
$db->close();
