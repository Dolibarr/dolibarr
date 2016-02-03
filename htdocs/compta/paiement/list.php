<?php
/* Copyright (C) 2001-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2013      Cédric Salvador      <csalvador@gpcsolutions.fr>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
 * Copyright (C) 2015      Juanjo Menent        <jmenent@2byte.es>
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
 *  \brief      Page liste des paiements des factures clients
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

$langs->load("bills");
$langs->load("compta");

// Security check
$facid =GETPOST('facid','int');
$socid =GETPOST('socid','int');
$userid=GETPOST('userid','int');
$day	= GETPOST('day','int');
$month	= GETPOST('month','int');
$year	= GETPOST('year','int');

if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'facture',$facid,'');

$paymentstatic=new Paiement($db);
$accountstatic=new Account($db);
$companystatic=new Societe($db);

$search_ref=GETPOST("search_ref","int");
$search_account=GETPOST("search_account","int");
$search_paymenttype=GETPOST("search_paymenttype");
$search_amount=GETPOST("search_amount",'alpha');    // alpha because we must be able to search on "< x"
$search_company=GETPOST("search_company",'alpha');
$search_payment_num=GETPOST('search_payment_num','alpha');
$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$limit = GETPOST('limit')?GETPOST('limit','int'):$conf->liste_limit;
$page = GETPOST("page",'int');
if ($page == -1) { $page = 0; }
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) $sortorder="DESC";
if (! $sortfield) $sortfield="p.rowid";

if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter")) // Both test are required to be compatible with all browsers
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
}

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('paymentlist'));
$extrafields = new ExtraFields($db);



/*
 * 	View
 */

llxHeader('', $langs->trans('ListPayment'));

$form=new Form($db);
$formother=new FormOther($db);

if (GETPOST("orphelins"))
{
    // Paiements lies a aucune facture (pour aide au diagnostic)
    $sql = "SELECT p.rowid, p.ref, p.datep as dp, p.amount,";
    $sql.= " p.statut, p.num_paiement,";
    $sql.= " c.code as paiement_code";
	// Add fields for extrafields
	foreach ($extrafields->attribute_list as $key => $val) $sql.=",ef.".$key.' as options_'.$key;
	// Add fields from hooks
	$parameters=array();
	$reshook=$hookmanager->executeHooks('printFieldListSelect',$parameters);    // Note that $action and $object may have been modified by hook
	$sql.=$hookmanager->resPrint;
    $sql.= " FROM (".MAIN_DB_PREFIX."paiement as p,";
    $sql.= " ".MAIN_DB_PREFIX."c_paiement as c)";
    $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."paiement_facture as pf ON p.rowid = pf.fk_paiement";
    $sql.= " WHERE p.fk_paiement = c.id";
    $sql.= " AND p.entity = ".$conf->entity;
    $sql.= " AND pf.fk_facture IS NULL";
	// Add where from hooks
	$parameters=array();
	$reshook=$hookmanager->executeHooks('printFieldListWhere',$parameters);    // Note that $action and $object may have been modified by hook
	$sql.=$hookmanager->resPrint;
}
else
{
    $sql = "SELECT DISTINCT p.rowid, p.ref, p.datep as dp, p.amount,"; // DISTINCT is to avoid duplicate when there is a link to sales representatives
    $sql.= " p.statut, p.num_paiement,";
    $sql.= " c.code as paiement_code,";
    $sql.= " ba.rowid as bid, ba.label,";
    $sql.= " s.rowid as socid, s.nom as name";
	// Add fields for extrafields
	foreach ($extrafields->attribute_list as $key => $val) $sql.=",ef.".$key.' as options_'.$key;
	// Add fields from hooks
	$parameters=array();
	$reshook=$hookmanager->executeHooks('printFieldListSelect',$parameters);    // Note that $action and $object may have been modified by hook
	$sql.=$hookmanager->resPrint;
    $sql.= " FROM (".MAIN_DB_PREFIX."c_paiement as c, ".MAIN_DB_PREFIX."paiement as p)";
    $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."bank as b ON p.fk_bank = b.rowid";
    $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."bank_account as ba ON b.fk_account = ba.rowid";
    $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."paiement_facture as pf ON p.rowid = pf.fk_paiement";
    $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."facture as f ON pf.fk_facture = f.rowid";
    $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON f.fk_soc = s.rowid";
    if (!$user->rights->societe->client->voir && !$socid)
    {
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON s.rowid = sc.fk_soc";
    }
    $sql.= " WHERE p.fk_paiement = c.id";
    $sql.= " AND p.entity = ".$conf->entity;
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
    if ($month > 0)
    {
        if ($year > 0 && empty($day))
        $sql.= " AND p.datep BETWEEN '".$db->idate(dol_get_first_day($year,$month,false))."' AND '".$db->idate(dol_get_last_day($year,$month,false))."'";
        else if ($year > 0 && ! empty($day))
        $sql.= " AND p.datep BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $month, $day, $year))."' AND '".$db->idate(dol_mktime(23, 59, 59, $month, $day, $year))."'";
        else
        $sql.= " AND date_format(p.datep, '%m') = '".$month."'";
    }
    else if ($year > 0)
    {
        $sql.= " AND p.datep BETWEEN '".$db->idate(dol_get_first_day($year,1,false))."' AND '".$db->idate(dol_get_last_day($year,12,false))."'";
    }
    if ($search_ref)       		    $sql .=natural_search('p.ref', $search_ref);
    if ($search_account > 0)      	$sql .=" AND b.fk_account=".$search_account;
    if ($search_paymenttype != "")  $sql .=" AND c.code='".$db->escape($search_paymenttype)."'";
    if ($search_payment_num != '')  $sql .=" AND p.num_paiement = '".$db->escape($search_payment_num)."'";
    if ($search_amount)      		$sql .=" AND p.amount='".$db->escape(price2num($search_amount))."'";
    if ($search_company)     		$sql .= natural_search('s.nom', $search_company);
	// Add where from hooks
	$parameters=array();
	$reshook=$hookmanager->executeHooks('printFieldListWhere',$parameters);    // Note that $action and $object may have been modified by hook
	$sql.=$hookmanager->resPrint;
}
$sql.= $db->order($sortfield,$sortorder);
$sql.= $db->plimit($limit+1, $offset);
//print "$sql";

$resql = $db->query($sql);

if ($resql)
{
    $num = $db->num_rows($resql);
    $i = 0;

    $paramlist='';
    $paramlist.=(GETPOST("orphelins")?"&orphelins=1":"");
    $paramlist.=($search_ref?"&search_ref=".$search_ref:"");
    $paramlist.=($search_company?"&search_company=".$search_company:"");
    $paramlist.=($search_amount?"&search_amount=".$search_amount:"");

    print_barre_liste($langs->trans("ReceivedCustomersPayments"), $page, $_SERVER["PHP_SELF"],$paramlist,$sortfield,$sortorder,'',$num,'','title_accountancy.png');

    print '<form method="GET" action="'.$_SERVER["PHP_SELF"].'">';
    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre">';
    print_liste_field_titre($langs->trans("RefPayment"),$_SERVER["PHP_SELF"],"p.rowid","",$paramlist,"",$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Date"),$_SERVER["PHP_SELF"],"dp","",$paramlist,'align="center"',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("ThirdParty"),$_SERVER["PHP_SELF"],"s.nom","",$paramlist,"",$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Type"),$_SERVER["PHP_SELF"],"c.libelle","",$paramlist,"",$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Numero"),$_SERVER["PHP_SELF"],"p.num_paiement","",$paramlist,"",$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Account"),$_SERVER["PHP_SELF"],"ba.label","",$paramlist,"",$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Amount"),$_SERVER["PHP_SELF"],"p.amount","",$paramlist,'align="right"',$sortfield,$sortorder);
    //print_liste_field_titre($langs->trans("Invoices"),"","","",$paramlist,'align="left"',$sortfield,$sortorder);

	$parameters=array();
    $reshook=$hookmanager->executeHooks('printFieldListTitle',$parameters);    // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;

    if (! empty($conf->global->BILL_ADD_PAYMENT_VALIDATION)) print_liste_field_titre($langs->trans("Status"),$_SERVER["PHP_SELF"],"p.statut","",$paramlist,'align="right"',$sortfield,$sortorder);
    print_liste_field_titre('',$_SERVER["PHP_SELF"],"",'','','',$sortfield,$sortorder,'maxwidthsearch ');
	print "</tr>\n";

    // Lines for filters fields
    print '<tr class="liste_titre">';
    print '<td align="left">';
    print '<input class="flat" type="text" size="4" name="search_ref" value="'.$search_ref.'">';
    print '</td>';
    print '<td align="center">';
    if (! empty($conf->global->MAIN_LIST_FILTER_ON_DAY)) print '<input class="flat" type="text" size="1" maxlength="2" name="day" value="'.$day.'">';
    print '<input class="flat" type="text" size="1" maxlength="2" name="month" value="'.$month.'">';
    $formother->select_year($year?$year:-1,'year',1, 20, 5);
    print '</td>';
    print '<td align="left">';
    print '<input class="flat" type="text" size="6" name="search_company" value="'.$search_company.'">';
    print '</td>';
    print '<td>';
    $form->select_types_paiements($search_paymenttype,'search_paymenttype','',2,1,1);
    print '</td>';
    print '<td align="left">';
    print '<input class="flat" type="text" size="4" name="search_payment_num" value="'.$search_payment_num.'">';
    print '</td>';
    print '<td>';
    $form->select_comptes($search_account,'search_account',0,'',1);
    print '</td>';
    print '<td align="right">';
    print '<input class="flat" type="text" size="4" name="search_amount" value="'.$search_amount.'">';
	print '</td><td align="right">';
    print '<input type="image" class="liste_titre" name="button_search" src="'.img_picto($langs->trans("Search"),'search.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
	print '<input type="image" class="liste_titre" name="button_removefilter" src="'.img_picto($langs->trans("Search"),'searchclear.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'" title="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'">';
    print '</td>';
    if (! empty($conf->global->BILL_ADD_PAYMENT_VALIDATION))
    {
        print '<td align="right">';
        print '</td>';
    }
    print "</tr>\n";

    $var=true;
    while ($i < min($num,$limit))
    {
        $objp = $db->fetch_object($resql);
        $var=!$var;
        print "<tr ".$bc[$var].">";

        print '<td>';
        $paymentstatic->id=$objp->rowid;
        $paymentstatic->ref=$objp->ref;
        print $paymentstatic->getNomUrl(1);
        print '</td>';

        print '<td align="center">'.dol_print_date($db->jdate($objp->dp),'day').'</td>';

        // Company
        print '<td>';
        if ($objp->socid)
        {
            $companystatic->id=$objp->socid;
            $companystatic->name=$objp->name;
            print $companystatic->getNomUrl(1,'',24);
        }
        else print '&nbsp;';
        print '</td>';

        print '<td>'.$langs->trans("PaymentTypeShort".$objp->paiement_code).'</td><td>'.$objp->num_paiement.'</td>';
        print '<td>';
        if ($objp->bid)
        {
            $accountstatic->id=$objp->bid;
            $accountstatic->label=$objp->label;
            print $accountstatic->getNomUrl(1);
        }
        else print '&nbsp;';
        print '</td>';
        print '<td align="right">'.price($objp->amount).'</td>';

        if (! empty($conf->global->BILL_ADD_PAYMENT_VALIDATION))
        {
            print '<td align="right">';
            if ($objp->statut == 0) print '<a href="card.php?id='.$objp->rowid.'&amp;action=valide">';
            print $paymentstatic->LibStatut($objp->statut,5);
            if ($objp->statut == 0) print '</a>';
            print '</td>';
        }

		print '<td>&nbsp;</td>';
        print '</tr>';

        $i++;
    }
    print "</table>\n";
    print "</form>\n";
}
else
{
    dol_print_error($db);
}

llxFooter();
$db->close();
