<?php
/* Copyright (C) 2001-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2013      Cédric Salvador      <csalvador@gpcsolutions.fr>
 * Copyright (C) 2015      Jean-François Ferry  <jfefe@aternatik.fr>
 * Copyright (C) 2015      Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2017      Alexandre Spangaro   <aspangaro@open-dsi.fr>
 * Copyright (C) 2018      Ferran Marcet        <fmarcet@2byte.es>
 * Copyright (C) 2018      Charlene Benke       <charlie@patas-monkey.com>
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
 *	\file       htdocs/compta/paiement/list.php
 *  \ingroup    compta
 *  \brief      Payment page for customer invoices
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingjournal.class.php';

// Load translation files required by the page
$langs->loadLangs(array('bills', 'banks', 'compta', 'companies'));

$action		= GETPOST('action', 'alpha');
$confirm	= GETPOST('confirm', 'alpha');
$optioncss = GETPOST('optioncss', 'alpha');

$facid	= GETPOST('facid', 'int');
$socid	= GETPOST('socid', 'int');
$userid	= GETPOST('userid', 'int');
$day	= GETPOST('day', 'int');
$month	= GETPOST('month', 'int');
$year	= GETPOST('year', 'int');

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'facture', $facid, '');

$paymentstatic=new Paiement($db);
$accountstatic=new Account($db);
$companystatic=new Societe($db);

$search_ref=GETPOST("search_ref", "alpha");
$search_account=GETPOST("search_account", "int");
$search_paymenttype=GETPOST("search_paymenttype");
$search_amount=GETPOST("search_amount", 'alpha');    // alpha because we must be able to search on "< x"
$search_company=GETPOST("search_company", 'alpha');
$search_payment_num=GETPOST('search_payment_num', 'alpha');

$limit = GETPOST('limit', 'int')?GETPOST('limit', 'int'):$conf->liste_limit;
$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$page = GETPOST("page", 'int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) $sortorder="DESC";
if (! $sortfield) $sortfield="p.rowid";

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('paymentlist'));
$extrafields = new ExtraFields($db);

$arrayfields=array();


/*
 * Actions
 */

if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) // All tests are required to be compatible with all browsers
{
	$search_ref="";
	$search_account="";
	$search_amount="";
    $search_paymenttype="";
    $search_payment_num="";
	$search_company="";
    $day='';
    $year='';
    $month='';
    $search_array_options=array();
}


/*
 * 	View
 */

$form=new Form($db);
$formother=new FormOther($db);

llxHeader('', $langs->trans('ListPayment'));

if (GETPOST("orphelins", "alpha"))
{
    // Payments not linked to an invoice. Should not happend. For debug only.
    $sql = "SELECT p.rowid, p.ref, p.datep as dp, p.amount,";
    $sql.= " p.statut, p.num_paiement,";
    $sql.= " c.code as paiement_code";
	// Add fields for extrafields
	foreach ($extrafields->attribute_list as $key => $val) $sql.=",ef.".$key.' as options_'.$key;
	// Add fields from hooks
	$parameters=array();
	$reshook=$hookmanager->executeHooks('printFieldListSelect', $parameters);    // Note that $action and $object may have been modified by hook
	$sql.=$hookmanager->resPrint;
    $sql.= " FROM ".MAIN_DB_PREFIX."paiement as p LEFT JOIN ".MAIN_DB_PREFIX."c_paiement as c ON p.fk_paiement = c.id";
    $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."paiement_facture as pf ON p.rowid = pf.fk_paiement";
    $sql.= " WHERE p.entity IN (" . getEntity('invoice').")";
    $sql.= " AND pf.fk_facture IS NULL";
	// Add where from hooks
	$parameters=array();
	$reshook=$hookmanager->executeHooks('printFieldListWhere', $parameters);    // Note that $action and $object may have been modified by hook
	$sql.=$hookmanager->resPrint;
}
else
{
    $sql = "SELECT DISTINCT p.rowid, p.ref, p.datep as dp, p.amount,"; // DISTINCT is to avoid duplicate when there is a link to sales representatives
    $sql.= " p.statut, p.num_paiement,";
    $sql.= " c.code as paiement_code,";
    $sql.= " ba.rowid as bid, ba.ref as bref, ba.label as blabel, ba.number, ba.account_number as account_number, ba.fk_accountancy_journal as accountancy_journal,";
    $sql.= " s.rowid as socid, s.nom as name, s.email";
	// Add fields for extrafields
	foreach ($extrafields->attribute_list as $key => $val) $sql.=",ef.".$key.' as options_'.$key;
	// Add fields from hooks
	$parameters=array();
	$reshook=$hookmanager->executeHooks('printFieldListSelect', $parameters);    // Note that $action and $object may have been modified by hook
	$sql.=$hookmanager->resPrint;
    $sql.= " FROM ".MAIN_DB_PREFIX."paiement as p";
    $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_paiement as c ON p.fk_paiement = c.id";
    $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."bank as b ON p.fk_bank = b.rowid";
    $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."bank_account as ba ON b.fk_account = ba.rowid";
    $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."paiement_facture as pf ON p.rowid = pf.fk_paiement";
    $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."facture as f ON pf.fk_facture = f.rowid";
    $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON f.fk_soc = s.rowid";
    if (!$user->rights->societe->client->voir && !$socid)
    {
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON s.rowid = sc.fk_soc";
    }
    $sql.= " WHERE p.entity IN (" . getEntity('invoice') . ")";
    if (! $user->rights->societe->client->voir && ! $socid)
    {
        $sql.= " AND sc.fk_user = " .$user->id;
    }
    if ($socid > 0) $sql.= " AND f.fk_soc = ".$socid;
    if ($userid)
    {
        if ($userid == -1) $sql.= " AND f.fk_user_author IS NULL";
        else  $sql.= " AND f.fk_user_author = ".$userid;
    }
    // Search criteria
    $sql.= dolSqlDateFilter("p.datep", $day, $month, $year);
    if ($search_ref)       		    $sql .= natural_search('p.ref', $search_ref);
    if ($search_account > 0)      	$sql .=" AND b.fk_account=".$search_account;
    if ($search_paymenttype != "")  $sql .=" AND c.code='".$db->escape($search_paymenttype)."'";
    if ($search_payment_num != '')  $sql .= natural_search('p.num_paiement', $search_payment_num);
    if ($search_amount)      		$sql .= natural_search('p.amount', $search_amount, 1);
    if ($search_company)     		$sql .= natural_search('s.nom', $search_company);
	// Add where from hooks
	$parameters=array();
	$reshook=$hookmanager->executeHooks('printFieldListWhere', $parameters);    // Note that $action and $object may have been modified by hook
	$sql.=$hookmanager->resPrint;
}
$sql.= $db->order($sortfield, $sortorder);

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
//print "$sql";

$resql = $db->query($sql);
if ($resql)
{
    $num = $db->num_rows($resql);

    $param='';
    if (! empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param.='&contextpage='.urlencode($contextpage);
    if ($limit > 0 && $limit != $conf->liste_limit) $param.='&limit='.urlencode($limit);
    $param.=(GETPOST("orphelins")?"&orphelins=1":"");
    $param.=($search_ref?"&search_ref=".urlencode($search_ref):"");
    $param.=($search_company?"&search_company=".urlencode($search_company):"");
    $param.=($search_amount?"&search_amount=".urlencode($search_amount):"");
    $param.=($search_payment_num?"&search_payment_num=".urlencode($search_payment_num):"");
    if ($optioncss != '') $param.='&optioncss='.urlencode($optioncss);

    print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
    if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="action" value="list">';
    print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
    print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
    print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
    print '<input type="hidden" name="page" value="'.$page.'">';
    print '<input type="hidden" name="viewstatut" value="'.$viewstatut.'">';

    print_barre_liste($langs->trans("ReceivedCustomersPayments"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'title_accountancy.png', 0, '', '', $limit);

    print '<div class="div-table-responsive">';
    print '<table class="tagtable liste'.($moreforfilter?" listwithfilterbefore":"").'">'."\n";

    // Lines for filters fields
    print '<tr class="liste_titre_filter">';
    print '<td class="liste_titre left">';
    print '<input class="flat" type="text" size="4" name="search_ref" value="'.dol_escape_htmltag($search_ref).'">';
    print '</td>';
    print '<td class="liste_titre center">';
    if (! empty($conf->global->MAIN_LIST_FILTER_ON_DAY)) print '<input class="flat width25 valignmiddle" type="text" maxlength="2" name="day" value="'.dol_escape_htmltag($day).'">';
    print '<input class="flat width25 valignmiddle" type="text" maxlength="2" name="month" value="'.dol_escape_htmltag($month).'">';
    $formother->select_year($year?$year:-1, 'year', 1, 20, 5);
    print '</td>';
    print '<td class="liste_titre">';
    print '<input class="flat" type="text" size="6" name="search_company" value="'.dol_escape_htmltag($search_company).'">';
    print '</td>';
    print '<td class="liste_titre">';
    $form->select_types_paiements($search_paymenttype, 'search_paymenttype', '', 2, 1, 1);
    print '</td>';
    print '<td class="liste_titre">';
    print '<input class="flat" type="text" size="4" name="search_payment_num" value="'.dol_escape_htmltag($search_payment_num).'">';
    print '</td>';
    if (! empty($conf->banque->enabled))
    {
	    print '<td class="liste_titre">';
	    $form->select_comptes($search_account, 'search_account', 0, '', 1);
	    print '</td>';
    }
    print '<td class="liste_titre right">';
    print '<input class="flat" type="text" size="4" name="search_amount" value="'.dol_escape_htmltag($search_amount).'">';
	print '</td>';
    print '<td class="liste_titre maxwidthsearch">';
    $searchpicto=$form->showFilterAndCheckAddButtons(0);
    print $searchpicto;
    print '</td>';
    if (! empty($conf->global->BILL_ADD_PAYMENT_VALIDATION))
    {
        print '<td class="liste_titre right">';
        print '</td>';
    }
    print "</tr>\n";

    print '<tr class="liste_titre">';
    print_liste_field_titre("RefPayment", $_SERVER["PHP_SELF"], "p.rowid", "", $param, "", $sortfield, $sortorder);
    print_liste_field_titre("Date", $_SERVER["PHP_SELF"], "dp", "", $param, '', $sortfield, $sortorder, 'center ');
    print_liste_field_titre("ThirdParty", $_SERVER["PHP_SELF"], "s.nom", "", $param, "", $sortfield, $sortorder);
    print_liste_field_titre("Type", $_SERVER["PHP_SELF"], "c.libelle", "", $param, "", $sortfield, $sortorder);
    print_liste_field_titre("Numero", $_SERVER["PHP_SELF"], "p.num_paiement", "", $param, "", $sortfield, $sortorder);
    if (! empty($conf->banque->enabled))
    {
        print_liste_field_titre("Account", $_SERVER["PHP_SELF"], "ba.label", "", $param, "", $sortfield, $sortorder);
    }
    print_liste_field_titre("Amount", $_SERVER["PHP_SELF"], "p.amount", "", $param, 'class="right"', $sortfield, $sortorder);
    //print_liste_field_titre("Invoices"),"","","",$param,'class="left"',$sortfield,$sortorder);

	$parameters=array('arrayfields'=>$arrayfields,'param'=>$param,'sortfield'=>$sortfield,'sortorder'=>$sortorder);
    $reshook=$hookmanager->executeHooks('printFieldListTitle', $parameters);    // Note that $action and $object may have been modified by hook
    print $hookmanager->resPrint;

    if (! empty($conf->global->BILL_ADD_PAYMENT_VALIDATION)) print_liste_field_titre("Status", $_SERVER["PHP_SELF"], "p.statut", "", $param, 'class="right"', $sortfield, $sortorder);
    print_liste_field_titre('', $_SERVER["PHP_SELF"], "", '', '', '', $sortfield, $sortorder, 'maxwidthsearch ');
    print "</tr>\n";

    $i = 0;
    $totalarray=array();
    while ($i < min($num, $limit))
    {
        $objp = $db->fetch_object($resql);

        $paymentstatic->id=$objp->rowid;
        $paymentstatic->ref=$objp->ref;

        $companystatic->id=$objp->socid;
        $companystatic->name=$objp->name;
        $companystatic->email=$objp->email;

        print '<tr class="oddeven">';

        print '<td>';
        print $paymentstatic->getNomUrl(1);
        print '</td>';
        if (! $i) $totalarray['nbfield']++;

        // Date
        $dateformatforpayment = 'day';
        if (! empty($conf->global->INVOICE_USE_HOURS_FOR_PAYMENT)) $dateformatforpayment='dayhour';
        print '<td align="center">'.dol_print_date($db->jdate($objp->dp), $dateformatforpayment).'</td>';
        if (! $i) $totalarray['nbfield']++;

        // Thirdparty
        print '<td>';
        if ($objp->socid > 0)
        {
            print $companystatic->getNomUrl(1, '', 24);
        }
        print '</td>';
        if (! $i) $totalarray['nbfield']++;

        // Type
        print '<td>'.$langs->trans("PaymentTypeShort".$objp->paiement_code).'</td>';
        if (! $i) $totalarray['nbfield']++;

        // Payment number
        print '<td>'.$objp->num_paiement.'</td>';
        if (! $i) $totalarray['nbfield']++;

        // Account
	    if (! empty($conf->banque->enabled))
	    {
	        print '<td>';
	        if ($objp->bid > 0)
	        {
	            $accountstatic->id=$objp->bid;
	            $accountstatic->ref=$objp->bref;
	            $accountstatic->label=$objp->blabel;
	            $accountstatic->number=$objp->number;
	            $accountstatic->account_number=$objp->account_number;

				$accountingjournal = new AccountingJournal($db);
				$accountingjournal->fetch($objp->accountancy_journal);
				$accountstatic->accountancy_journal = $accountingjournal->code;

	            print $accountstatic->getNomUrl(1);
	        }
	        print '</td>';
	        if (! $i) $totalarray['nbfield']++;
	    }

	    // Amount
        print '<td class="right">'.price($objp->amount).'</td>';
        if (! $i) $totalarray['nbfield']++;
        $totalarray['pos'][7]='amount';
        $totalarray['val']['amount'] += $objp->amount;

        if (! empty($conf->global->BILL_ADD_PAYMENT_VALIDATION))
        {
            print '<td class="right">';
            if ($objp->statut == 0) print '<a href="card.php?id='.$objp->rowid.'&amp;action=valide">';
            print $paymentstatic->LibStatut($objp->statut, 5);
            if ($objp->statut == 0) print '</a>';
            print '</td>';
            if (! $i) $totalarray['nbfield']++;
        }

		print '<td></td>';
		if (! $i) $totalarray['nbfield']++;

		print '</tr>';

        $i++;
    }

    // Show total line
    if (isset($totalarray['pos']))
    {
        print '<tr class="liste_total">';
        $i=0;
        while ($i < $totalarray['nbfield'])
        {
            $i++;
            if (! empty($totalarray['pos'][$i]))  print '<td class="right">'.price($totalarray['val'][$totalarray['pos'][$i]]).'</td>';
            else
            {
                if ($i == 1)
                {
                    if ($num < $limit) print '<td class="left">'.$langs->trans("Total").'</td>';
                    else print '<td class="left">'.$langs->trans("Totalforthispage").'</td>';
                }
                else print '<td></td>';
            }
        }
        print '</tr>';
    }

    print "</table>\n";
    print "</div>";
    print "</form>\n";
}
else
{
    dol_print_error($db);
}

// End of page
llxFooter();
$db->close();
