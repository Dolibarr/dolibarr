<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2010      Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2010-2012 Juanjo Menent        <jmenent@2byte.es>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/compta/prelevement/create.php
 *  \ingroup    prelevement
 *	\brief      Prelevement creation page
 */

require('../../main.inc.php');
require_once DOL_DOCUMENT_ROOT.'/compta/prelevement/class/bonprelevement.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/prelevement.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

$langs->load("banks");
$langs->load("categories");
$langs->load("widthdrawals");
$langs->load("companies");
$langs->load("bills");

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'prelevement', '', '', 'bons');

// Get supervariables
$action = GETPOST('action','alpha');


/*
 * Actions
 */

// Change customer bank information to withdraw
if ($action == 'modify')
{
    for ($i = 1 ; $i < 9 ; $i++)
    {
        dolibarr_set_const($db, GETPOST("nom$i"), GETPOST("value$i"),'chaine',0,'',$conf->entity);
    }
}

if ($action == 'create')
{
    $bprev = new BonPrelevement($db);
    $result=$bprev->create($conf->global->PRELEVEMENT_CODE_BANQUE, $conf->global->PRELEVEMENT_CODE_GUICHET);
    if ($result < 0)
    {
        $mesg='<div class="error">'.$bprev->error.'</div>';
    }
    if ($result == 0)
    {
        $mesg='<div class="error">'.$langs->trans("NoInvoiceCouldBeWithdrawed").'</div>';
    }
}


/*
 * View
 */

$thirdpartystatic=new Societe($db);
$invoicestatic=new Facture($db);
$bprev = new BonPrelevement($db);

llxHeader('', $langs->trans("NewStandingOrder"));

if (prelevement_check_config() < 0)
{
	$langs->load("errors");
	print '<div class="error">';
	print $langs->trans("ErrorModuleSetupNotComplete");
	print '</div>';
}

$h=0;
$head[$h][0] = DOL_URL_ROOT.'/compta/prelevement/create.php';
$head[$h][1] = $langs->trans("NewStandingOrder");
$h++;

dol_fiche_head($head, $hselected, $langs->trans("StandingOrders"), 0, 'payment');


$nb=$bprev->NbFactureAPrelever();
$nb1=$bprev->NbFactureAPrelever(1);
$nb11=$bprev->NbFactureAPrelever(1,1);
$pricetowithdraw=$bprev->SommeAPrelever();
if ($nb < 0 || $nb1 < 0 || $nb11 < 0)
{
    dol_print_error($bprev->error);
}
print '<table class="border" width="100%">';

print '<tr><td>'.$langs->trans("NbOfInvoiceToWithdraw").'</td>';
print '<td align="right">';
print $nb;
print '</td></tr>';

print '<tr><td>'.$langs->trans("AmountToWithdraw").'</td>';
print '<td align="right">';
print price($pricetowithdraw);
print '</td>';
print '</tr>';

//print '<tr><td>'.$langs->trans("NbOfInvoiceToWithdraw").' + '.$langs->trans("ThirdPartyBankCode").'='.$conf->global->PRELEVEMENT_CODE_BANQUE.'</td><td align="right">';
//print $nb1;
//print '</td></tr>';

//print '<tr><td>'.$langs->trans("NbOfInvoiceToWithdrawWithInfo").'</td><td align="right">';
//print $nb11;
//print '</td></tr>';

print '</table>';
print '</div>';

if ($mesg) print $mesg;

print "<div class=\"tabsAction\">\n";

if ($nb)
{
    if ($pricetowithdraw) print '<a class="butAction" href="create.php?action=create">'.$langs->trans("CreateAll")."</a>\n";
    else print '<a class="butActionRefused" href="#">'.$langs->trans("CreateAll")."</a>\n";
}
else
{
    print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->transnoentitiesnoconv("NoInvoiceToWithdraw")).'">'.$langs->trans("CreateAll")."</a>\n";
}
    //if ($nb11) print '<a class="butAction" href="create.php?action=create&amp;banque=1">'.$langs->trans("CreateBanque")."</a>\n";
    //if ($nb1)  print '<a class="butAction" href="create.php?action=create&amp;banque=1&amp;guichet=1">'.$langs->trans("CreateGuichet")."</a>\n";

print "</div>\n";
print '<br>';


/*
 * Invoices waiting for withdraw
 */

$sql = "SELECT f.facnumber, f.rowid, f.total_ttc, s.nom, s.rowid as socid,";
$sql.= " pfd.date_demande";
$sql.= " FROM ".MAIN_DB_PREFIX."facture as f,";
$sql.= " ".MAIN_DB_PREFIX."societe as s,";
$sql.= " ".MAIN_DB_PREFIX."prelevement_facture_demande as pfd";
$sql.= " WHERE s.rowid = f.fk_soc";
$sql.= " AND f.entity = ".$conf->entity;
$sql.= " AND pfd.traite = 0";
$sql.= " AND pfd.fk_facture = f.rowid";
if ($socid) $sql.= " AND f.fk_soc = ".$socid;

$resql=$db->query($sql);
if ($resql)
{
    $num = $db->num_rows($resql);
    $i = 0;

    print_fiche_titre($langs->trans("InvoiceWaitingWithdraw").($num > 0?' ('.$num.')':''),'','');

    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre">';
    print '<td>'.$langs->trans("Invoice").'</td>';
    print '<td>'.$langs->trans("ThirdParty").'</td>';
    print '<td align="right">'.$langs->trans("AmountTTC").'</td>';
    print '<td align="right">'.$langs->trans("DateRequest").'</td>';
    print '</tr>';

    if ($num)
    {
        $var = True;
        while ($i < $num && $i < 20)
        {
            $obj = $db->fetch_object($resql);
            $var=!$var;
            print '<tr '.$bc[$var].'><td>';
            $invoicestatic->id=$obj->rowid;
            $invoicestatic->ref=$obj->facnumber;
            print $invoicestatic->getNomUrl(1,'withdraw');
            print '</td>';
            print '<td>';
            $thirdpartystatic->id=$obj->socid;
            $thirdpartystatic->nom=$obj->nom;
            print $thirdpartystatic->getNomUrl(1,'customer');
            print '</td>';
            print '<td align="right">';
            print price($obj->total_ttc).' '.$langs->trans("Currency".$conf->currency);
            print '</td>';
            // Date
            print '<td align="right">';
            print dol_print_date($db->jdate($obj->date_demande),'day');
            print '</td>';
            print '</tr>';
            $i++;
        }
    }
    else print '<tr><td colspan="4">'.$langs->trans("None").'</td></tr>';
    print "</table>";
    print "<br>\n";
}
else
{
    dol_print_error($db);
}


/*
 * List of last withdraws
 */
$limit=5;

print_fiche_titre($langs->trans("LastWithdrawalReceipts",$limit),'','');

$sql = "SELECT p.rowid, p.ref, p.amount, p.statut";
$sql.= ", p.datec";
$sql.= " FROM ".MAIN_DB_PREFIX."prelevement_bons as p";
$sql.= " WHERE p.entity = ".$conf->entity;
$sql.= " ORDER BY datec DESC";
$sql.=$db->plimit($limit);

$result = $db->query($sql);
if ($result)
{
    $num = $db->num_rows($result);
    $i = 0;

    print"\n<!-- debut table -->\n";
    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre"><td>'.$langs->trans("Ref").'</td>';
    print '<td align="center">'.$langs->trans("Date").'</td><td align="right">'.$langs->trans("Amount").'</td>';
    print '</tr>';

    $var=True;

    while ($i < min($num,$limit))
    {
        $obj = $db->fetch_object($result);
        $var=!$var;

        print "<tr $bc[$var]><td>";
        $bprev->id=$obj->rowid;
        $bprev->ref=$obj->ref;
        print $bprev->getNomUrl(1);
        print "</td>\n";
        print '<td align="center">'.dol_print_date($db->jdate($obj->datec),'day')."</td>\n";

        print '<td align="right">'.price($obj->amount).' '.$langs->trans("Currency".$conf->currency)."</td>\n";

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


$db->close();

llxFooter();
?>
