<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2010      Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2010	   Juanjo Menent        <jmenent@2byte.es>
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
 *	\file       htdocs/compta/prelevement/create.php
 *	\brief      Prelevement
 *	\version    $Id$
 */

require('../../main.inc.php');
require_once(DOL_DOCUMENT_ROOT."/compta/prelevement/class/bon-prelevement.class.php");
require_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");
require_once(DOL_DOCUMENT_ROOT."/societe/class/societe.class.php");

$langs->load("widthdrawals");
$langs->load("companies");
$langs->load("banks");
$langs->load("bills");

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'prelevement', '', '', 'bons');


/*
 * Actions
 */

if ($_GET["action"] == 'create')
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

$h=0;
$head[$h][0] = DOL_URL_ROOT.'/compta/prelevement/create.php';
$head[$h][1] = $langs->trans("NewStandingOrder");
$h++;

dol_fiche_head($head, $hselected, $langs->trans("StandingOrders"), 0, 'payment');


$nb=$bprev->NbFactureAPrelever();
$nb1=$bprev->NbFactureAPrelever(1);
$nb11=$bprev->NbFactureAPrelever(1,1);
if ($nb < 0 || $nb1 < 0 || $nb11 < 0)
{
    dol_print_error($bprev->error);
}
print '<table class="border" width="100%">';

print '<tr><td>'.$langs->trans("NbOfInvoiceToWithdraw").'</td>';
print '<td align="right">';
print $nb;
print '</td></tr>';

print '</table>';

print '<br>';

print_fiche_titre($langs->trans("PleaseSelectCustomerBankBANToWithdraw"),'','');

print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<table class="border" width="100%">';

print '<tr class="pair"><td>'.$langs->trans("NumeroNationalEmetter").'</td>';
print '<td align="left">';
print '<input type="hidden" name="nom1" value="PRELEVEMENT_NUMERO_NATIONAL_EMETTEUR">';
print '<input type="text"   name="value1" value="'.$conf->global->PRELEVEMENT_NUMERO_NATIONAL_EMETTEUR.'" size="9" ></td>';
print '</tr>';
print '<tr class="impair"><td>'.$langs->trans("Name").'</td>';
print '<td align="left">';
print '<input type="hidden" name="nom2" value="PRELEVEMENT_RAISON_SOCIALE">';
print '<input type="text"   name="value2" value="'.$conf->global->PRELEVEMENT_RAISON_SOCIALE.'" size="14" ></td>';
print '</tr>';
print '<tr class="pair"><td>'.$langs->trans("BankCode").'</td>';
print '<td align="left">';
print '<input type="hidden" name="nom3" value="PRELEVEMENT_CODE_BANQUE">';
print '<input type="text"   name="value3" value="'.$conf->global->PRELEVEMENT_CODE_BANQUE.'" size="6" ></td>';
print '</tr>';
print '<tr class="impair"><td>'.$langs->trans("DeskCode").'</td>';
print '<td align="left">';
print '<input type="hidden" name="nom4" value="PRELEVEMENT_CODE_GUICHET">';
print '<input type="text"   name="value4" value="'.$conf->global->PRELEVEMENT_CODE_GUICHET.'" size="6" ></td>';
print '</tr>';
print '<tr class="pair"><td>'.$langs->trans("AccountNumber").'</td>';
print '<td align="left">';
print '<input type="hidden" name="nom5" value="PRELEVEMENT_NUMERO_COMPTE">';
print '<input type="text"   name="value5" value="'.$conf->global->PRELEVEMENT_NUMERO_COMPTE.'" size="11" ></td>';
print '</tr>';
print '<tr class="pair"><td colspan="2" align="center">';
print '<input type="submit" class="button" name="modify" value="'.dol_escape_htmltag($langs->trans("Modify")).'">';
print '</td>';
print '</tr>';

print '</table>';
print '</form>';

print '<br>';

print '<table class="border" width="100%">';

print '<tr><td>'.$langs->trans("NbOfInvoiceToWithdraw").' + '.$langs->trans("ThirdPartyBankCode").'='.$conf->global->PRELEVEMENT_CODE_BANQUE.'</td><td align="right">';
print $nb1;
print '</td></tr>';
print '<tr><td>'.$langs->trans("NbOfInvoiceToWithdraw").' + '.$langs->trans("ThirdPartyBankCode").'='.$conf->global->PRELEVEMENT_CODE_BANQUE.' + '.$langs->trans("ThirdPartyDeskCode").'='.$conf->global->PRELEVEMENT_CODE_GUICHET.'</td><td align="right">';
print $nb11;
print '</td></tr>';

$pricetowithdraw=$bprev->SommeAPrelever();

print '<tr><td>'.$langs->trans("AmountToWithdraw").'</td>';
print '<td align="right">';
print price($pricetowithdraw);
print '</td>';
print '</tr>';

print '</table>';
print '</div>';

if ($mesg) print $mesg;

if ($nb)
{
    print "<div class=\"tabsAction\">\n";

    if ($nb)
    {
        if ($pricetowithdraw) print '<a class="butAction" href="create.php?action=create">'.$langs->trans("CreateAll")."</a>\n";
        else print '<a class="butActionRefused" href="#">'.$langs->trans("CreateAll")."</a>\n";
    }
    if ($nb11) print '<a class="butAction" href="create.php?action=create&amp;banque=1">'.$langs->trans("CreateBanque")."</a>\n";
    if ($nb1)  print '<a class="butAction" href="create.php?action=create&amp;banque=1&amp;guichet=1">'.$langs->trans("CreateGuichet")."</a>\n";

    print "</div>\n";
}
else
{
    print $langs->trans("NoInvoiceToWithdraw").'<br>';
}
print '<br>';



/*
 * Invoices waiting for withdraw
 */

$sql = "SELECT f.facnumber, f.rowid, f.total_ttc, s.nom, s.rowid as socid";
$sql .= " FROM ".MAIN_DB_PREFIX."facture as f";
$sql.= ", ".MAIN_DB_PREFIX."societe as s";
$sql.= ", ".MAIN_DB_PREFIX."prelevement_facture_demande as pfd";
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

    print_fiche_titre($langs->trans("InvoiceWaitingWithdraw").' ('.$num.')','','');

    if ($num)
    {
        print '<table class="noborder" width="100%">';
        print '<tr class="liste_titre">';
        print '<td>'.$langs->trans("Invoice").'</td>';
        print '<td>'.$langs->trans("ThirdParty").'</td>';
        print '<td align="right">'.$langs->trans("AmountTTC").'</td>';
        print '</tr>';
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
            print price($obj->total_ttc).' '.$langs->trans("Currency".$conf->monnaie)."</td>\n";
            print '</td>';
            print '</tr>';
            $i++;
        }

        print "</table><br>";

    }
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

        print '<td align="right">'.price($obj->amount).' '.$langs->trans("Currency".$conf->monnaie)."</td>\n";

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

llxFooter('$Date$ - $Revision$');
?>
