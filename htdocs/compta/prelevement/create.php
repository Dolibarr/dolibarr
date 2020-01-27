<?php
/* Copyright (C) 2005       Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2010-2015  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2010-2012  Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2018       Nicolas ZABOURI         <info@inovea-conseil.com>
 * Copyright (C) 2018       Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2019      Markus Welters       <markus@welters.de>
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
 *	\file       htdocs/compta/prelevement/create.php
 *  \ingroup    prelevement
 *	\brief      Prelevement creation page
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/prelevement/class/bonprelevement.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/bank.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/prelevement.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

// Load translation files required by the page
$langs->loadLangs(array('banks', 'categories', 'withdrawals', 'companies', 'bills'));

// Security check
if ($user->socid) $socid=$user->socid;
$result = restrictedArea($user, 'prelevement', '', '', 'bons');

// Get supervariables
$action = GETPOST('action', 'alpha');
$mode = GETPOST('mode', 'alpha')?GETPOST('mode', 'alpha'):'real';
$format = GETPOST('format', 'aZ09');
$limit = GETPOST('limit', 'int')?GETPOST('limit', 'int'):$conf->liste_limit;
$page = GETPOST("page", 'int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;

$hookmanager->initHooks(array('directdebitcreatecard','globalcard'));


/*
 * Actions
 */

$parameters = array('mode' => $mode, 'format' => $format, 'limit' => $limit, 'page' => $page, 'offset' => $offset);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
	// Change customer bank information to withdraw
	if ($action == 'modify')
	{
		for ($i = 1 ; $i < 9 ; $i++)
		{
			dolibarr_set_const($db, GETPOST("nom$i"), GETPOST("value$i"), 'chaine', 0, '', $conf->entity);
		}
	}
	if ($action == 'create')
	{
		// $conf->global->PRELEVEMENT_CODE_BANQUE and $conf->global->PRELEVEMENT_CODE_GUICHET should be empty
		$bprev = new BonPrelevement($db);
	    $executiondate = dol_mktime(0, 0, 0, GETPOST('remonth'), (GETPOST('reday')+$conf->global->PRELEVEMENT_ADDDAYS), GETPOST('reyear'));

	    $result = $bprev->create($conf->global->PRELEVEMENT_CODE_BANQUE, $conf->global->PRELEVEMENT_CODE_GUICHET, $mode, $format, $executiondate);
		if ($result < 0)
		{
			setEventMessages($bprev->error, $bprev->errors, 'errors');
		}
		elseif ($result == 0)
		{
			$mesg=$langs->trans("NoInvoiceCouldBeWithdrawed", $format);
			setEventMessages($mesg, null, 'errors');
			$mesg.='<br>'."\n";
			foreach($bprev->invoice_in_error as $key => $val)
			{
				$mesg.='<span class="warning">'.$val."</span><br>\n";
			}
		}
		else
		{
			setEventMessages($langs->trans("DirectDebitOrderCreated", $bprev->getNomUrl(1)), null);
		}
	}
}


/*
 * View
 */

$form = new Form($db);

$thirdpartystatic=new Societe($db);
$invoicestatic=new Facture($db);
$bprev = new BonPrelevement($db);

llxHeader('', $langs->trans("NewStandingOrder"));

if (prelevement_check_config() < 0)
{
	$langs->load("errors");
	setEventMessages($langs->trans("ErrorModuleSetupNotComplete", $langs->transnoentitiesnoconv("Withdraw")), null, 'errors');
}

/*$h=0;
$head[$h][0] = DOL_URL_ROOT.'/compta/prelevement/create.php';
$head[$h][1] = $langs->trans("NewStandingOrder");
$head[$h][2] = 'payment';
$hselected = 'payment';
$h++;

dol_fiche_head($head, $hselected, $langs->trans("StandingOrders"), 0, 'payment');
*/

print load_fiche_titre($langs->trans("NewStandingOrder"));

dol_fiche_head();

$nb=$bprev->NbFactureAPrelever();
$nb1=$bprev->NbFactureAPrelever(1);
$nb11=$bprev->NbFactureAPrelever(1, 1);
$pricetowithdraw=$bprev->SommeAPrelever();
if ($nb < 0 || $nb1 < 0 || $nb11 < 0)
{
	dol_print_error($bprev->error);
}
print '<table class="border centpercent tableforfield">';

print '<tr><td class="titlefield">'.$langs->trans("NbOfInvoiceToWithdraw").'</td>';
print '<td>';
print $nb;
print '</td></tr>';

print '<tr><td>'.$langs->trans("AmountToWithdraw").'</td>';
print '<td>';
print price($pricetowithdraw);
print '</td>';
print '</tr>';

print '</table>';
print '</div>';

if ($mesg) print $mesg;

print "<div class=\"tabsAction\">\n";

print '<form action="' . $_SERVER['PHP_SELF'] . '?action=create" method="POST">';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
if ($nb) {
    if ($pricetowithdraw) {
        print $langs->trans('ExecutionDate').' ';
        print $form->selectDate();
        if ($mysoc->isInEEC()) {
            print '<select name="format"><option value="FRST">'.$langs->trans('SEPAFRST').'</option><option value="RCUR">'.$langs->trans('SEPARCUR').'</option></select>';
            print '<input class="butAction" type="submit" value="' . $langs->trans("CreateForSepa") . '"/>';
        } else {
            print '<a class="butAction"  type="submit" href="create.php?action=create&format=ALL">' . $langs->trans("CreateAll") . "</a>\n";
		}
	}
	else
	{
		if ($mysoc->isInEEC())
		{
			print '<a class="butActionRefused classfortooltip" href="#">'.$langs->trans("CreateForSepaFRST")."</a>\n";
			print '<a class="butActionRefused classfortooltip" href="#">'.$langs->trans("CreateForSepaRCUR")."</a>\n";
		}
		else
		{
			print '<a class="butActionRefused classfortooltip" href="#">'.$langs->trans("CreateAll")."</a>\n";
		}
	}
}
else
{
    print '<a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->transnoentitiesnoconv("NoInvoiceToWithdraw", $langs->transnoentitiesnoconv("StandingOrders"))).'">'.$langs->trans("CreateAll")."</a>\n";
}

print "</form>\n";

print "</div>\n";
print '</form>';
print '<br>';


/*
 * Invoices waiting for withdraw
 */

$sql = "SELECT f.ref, f.rowid, f.total_ttc, s.nom as name, s.rowid as socid,";
$sql.= " pfd.date_demande, pfd.amount";
$sql.= " FROM ".MAIN_DB_PREFIX."facture as f,";
$sql.= " ".MAIN_DB_PREFIX."societe as s,";
$sql.= " ".MAIN_DB_PREFIX."prelevement_facture_demande as pfd";
$sql.= " WHERE s.rowid = f.fk_soc";
$sql.= " AND f.entity IN (".getEntity('invoice').")";
if (empty($conf->global->WITHDRAWAL_ALLOW_ANY_INVOICE_STATUS))
{
	$sql.= " AND f.fk_statut = ".Facture::STATUS_VALIDATED;
}
$sql.= " AND f.total_ttc > 0";
$sql.= " AND pfd.traite = 0";
$sql.= " AND pfd.fk_facture = f.rowid";
if ($socid > 0) $sql.= " AND f.fk_soc = ".$socid;

$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
	$result = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($result);
	if (($page * $limit) > $nbtotalofrecords)	// if total resultset is smaller then paging size (filtering), goto and load page 0
	{
		$page = 0;
		$offset = 0;
	}
}

$sql.= $db->plimit($limit+1, $offset);

$resql=$db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);
	$i = 0;

    $param='';
	if ($limit > 0 && $limit != $conf->liste_limit) $param.='&limit='.urlencode($limit);
	if($socid) $param .= '&socid='.urlencode($socid);
    if($option) $param .= "&option=".urlencode($option);

    print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="page" value="'.$page.'">';
	if (! empty($limit)) {
		print '<input type="hidden" name="limit" value="' . $limit . '"/>';
	}

    print_barre_liste($langs->trans("InvoiceWaitingWithdraw"), $page, $_SERVER['PHP_SELF'], $param, '', '', '', $num, $nbtotalofrecords, 'invoicing', 0, '', '', $limit);

	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("Invoice").'</td>';
	print '<td>'.$langs->trans("ThirdParty").'</td>';
	print '<td>'.$langs->trans("RIB").'</td>';
	print '<td>'.$langs->trans("RUM").'</td>';
	print '<td class="right">'.$langs->trans("AmountTTC").'</td>';
	print '<td class="right">'.$langs->trans("DateRequest").'</td>';
	print '</tr>';

	if ($num)
	{
		require_once DOL_DOCUMENT_ROOT . '/societe/class/companybankaccount.class.php';
		$bac = new CompanyBankAccount($db);

		while ($i < $num && $i < $limit)
		{
			$obj = $db->fetch_object($resql);

			print '<tr class="oddeven">';
			print '<td>';
			$invoicestatic->id=$obj->rowid;
			$invoicestatic->ref=$obj->ref;
			print $invoicestatic->getNomUrl(1, 'withdraw');
			print '</td>';
			// Thirdparty
			print '<td>';
			$thirdpartystatic->fetch($obj->socid);
			print $thirdpartystatic->getNomUrl(1, 'ban');
			print '</td>';
			// RIB
			print '<td>';
			print $thirdpartystatic->display_rib();
			$bac->fetch(0, $obj->socid);
			if ($bac->verif() <= 0) print img_warning('Error on default bank number for IBAN : '.$bac->error_message);
			print '</td>';
			// RUM
			print '<td>';
			print $thirdpartystatic->display_rib('rum');
			$format = $thirdpartystatic->display_rib('format');
			if ($format) print ' ('.$format.')';
			print '</td>';
			// Amount
			print '<td class="right">';
			print price($obj->amount, 0, $langs, 0, 0, -1, $conf->currency);
			print '</td>';
			// Date
			print '<td class="right">';
			print dol_print_date($db->jdate($obj->date_demande), 'day');
			print '</td>';
			print '</tr>';
			$i++;
		}
	}
	else
	{
		print '<tr class="oddeven"><td colspan="6"><span class="opacitymedium">'.$langs->trans("None").'</span></td></tr>';
	}
	print "</table>";
	print "</form>";
	print "<br>\n";
}
else
{
	dol_print_error($db);
}


/*
 * List of latest withdraws
 */
/*
$limit=5;

print load_fiche_titre($langs->trans("LastWithdrawalReceipts",$limit),'','');

$sql = "SELECT p.rowid, p.ref, p.amount, p.statut";
$sql.= ", p.datec";
$sql.= " FROM ".MAIN_DB_PREFIX."prelevement_bons as p";
$sql.= " WHERE p.entity IN (".getEntity('invoice').")";
$sql.= " ORDER BY datec DESC";
$sql.=$db->plimit($limit);

$result = $db->query($sql);
if ($result)
{
    $num = $db->num_rows($result);
    $i = 0;

    print"\n<!-- debut table -->\n";
    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre"><td>'.$langs->trans("Ref").'</td>';
    print '<td class="center">'.$langs->trans("Date").'</td><td class="right">'.$langs->trans("Amount").'</td>';
    print '</tr>';

    while ($i < min($num,$limit))
    {
        $obj = $db->fetch_object($result);


        print '<tr class="oddeven">';

        print "<td>";
        $bprev->id=$obj->rowid;
        $bprev->ref=$obj->ref;
        print $bprev->getNomUrl(1);
        print "</td>\n";

        print '<td class="center">'.dol_print_date($db->jdate($obj->datec),'day')."</td>\n";

        print '<td class="right">'.price($obj->amount,0,$langs,0,0,-1,$conf->currency)."</td>\n";

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
*/

// End of page
llxFooter();
$db->close();
