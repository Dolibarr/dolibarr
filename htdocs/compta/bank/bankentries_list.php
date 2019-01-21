<?php
/* Copyright (C) 2001-2002  Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2018  Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010  Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2012       Vinícius Nogueira    <viniciusvgn@gmail.com>
 * Copyright (C) 2014       Florian Henry        <florian.henry@open-cooncept.pro>
 * Copyright (C) 2015       Jean-François Ferry  <jfefe@aternatik.fr>
 * Copyright (C) 2016       Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2017       Alexandre Spangaro   <aspangaro@zendsi.com>
 * Copyright (C) 2018       Ferran Marcet        <fmarcet@2byte.es>
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
 *	\file       htdocs/compta/bank/bankentries_list.php
 *	\ingroup    banque
 *	\brief      List of bank transactions
 */

require('../../main.inc.php');
require_once DOL_DOCUMENT_ROOT.'/core/lib/bank.lib.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/bankcateg.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formaccounting.class.php';

require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/sociales/class/chargesociales.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/tva/class/tva.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/salaries/class/paymentsalary.class.php';
require_once DOL_DOCUMENT_ROOT.'/don/class/don.class.php';
require_once DOL_DOCUMENT_ROOT.'/expensereport/class/paymentexpensereport.class.php';
require_once DOL_DOCUMENT_ROOT.'/loan/class/loan.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/paiementfourn.class.php';

// Load translation files required by the page
$langs->loadLangs(array("banks","bills","categories","companies","margins","salaries","loan","donations","trips","members","compta","accountancy"));

$id = GETPOST('id','int');
$ref = GETPOST('ref','alpha');
$action=GETPOST('action','alpha');
$cancel=GETPOST('cancel','alpha');
$confirm=GETPOST('confirm','alpha');
$contextpage='banktransactionlist'.(empty($object->ref)?'':'-'.$object->id);

// Security check
$fieldvalue = (! empty($id) ? $id : (! empty($ref) ? $ref :''));
$fieldtype = (! empty($ref) ? 'ref' :'rowid');
if ($fielvalue)
{
	if ($user->societe_id) $socid=$user->societe_id;
	$result=restrictedArea($user,'banque',$fieldvalue,'bank_account&bank_account','','',$fieldtype);
}
else
{
	if ($user->societe_id) $socid=$user->societe_id;
	$result=restrictedArea($user,'banque');
}

$description=GETPOST("description",'alpha');
$dateop = dol_mktime(12,0,0,GETPOST("opmonth",'int'),GETPOST("opday",'int'),GETPOST("opyear",'int'));
$debit=GETPOST("debit",'alpha');
$credit=GETPOST("credit",'alpha');
$search_type=GETPOST("search_type",'alpha');
$search_account=GETPOST("search_account",'int')?GETPOST("search_account",'int'):GETPOST("account",'int');
$search_accountancy_code=GETPOST('search_accountancy_code', 'alpha')?GETPOST('search_accountancy_code', 'alpha'):GETPOST('accountancy_code', 'alpha');
$search_bid=GETPOST("search_bid","int")?GETPOST("search_bid","int"):GETPOST("bid","int");
$search_ref=GETPOST('search_ref','alpha');
$search_dt_start = dol_mktime(0, 0, 0, GETPOST('search_start_dtmonth', 'int'), GETPOST('search_start_dtday', 'int'), GETPOST('search_start_dtyear', 'int'));
$search_dt_end = dol_mktime(0, 0, 0, GETPOST('search_end_dtmonth', 'int'), GETPOST('search_end_dtday', 'int'), GETPOST('search_end_dtyear', 'int'));
$search_dv_start = dol_mktime(0, 0, 0, GETPOST('search_start_dvmonth', 'int'), GETPOST('search_start_dvday', 'int'), GETPOST('search_start_dvyear', 'int'));
$search_dv_end = dol_mktime(0, 0, 0, GETPOST('search_end_dvmonth', 'int'), GETPOST('search_end_dvday', 'int'), GETPOST('search_end_dvyear', 'int'));
$search_thirdparty=GETPOST("search_thirdparty",'alpha')?GETPOST("search_thirdparty",'alpha'):GETPOST("thirdparty",'alpha');
$search_req_nb=GETPOST("req_nb",'alpha');
$search_num_releve=GETPOST("search_num_releve",'alpha');
$search_conciliated=GETPOST("search_conciliated",'int');
$num_releve=GETPOST("num_releve","alpha");
$cat=GETPOST("cat");
if (empty($dateop)) $dateop=-1;

$limit = GETPOST('limit','int')?GETPOST('limit','int'):$conf->liste_limit;
$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
$pageplusone = GETPOST("pageplusone",'int');
if ($pageplusone) $page = $pageplusone - 1;
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) $sortorder='desc,desc,desc';
if (! $sortfield) $sortfield='b.datev,b.dateo,b.rowid';

$mode_balance_ok=false;
//if (($sortfield == 'b.datev' || $sortfield == 'b.datev,b.dateo,b.rowid'))    // TODO Manage balance when account not selected
if (($sortfield == 'b.datev' || $sortfield == 'b.datev,b.dateo,b.rowid'))
{
    $sortfield = 'b.datev,b.dateo,b.rowid';
    if ($id > 0 || ! empty($ref) || $search_account > 0) $mode_balance_ok = true;
}

$object = new Account($db);
if ($id > 0 || ! empty($ref))
{
    $result=$object->fetch($id, $ref);
    $search_account = $object->id;     // Force the search field on id of account
}


// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('banktransactionlist', $contextpage));
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels = $extrafields->fetch_name_optionals_label('banktransaction');
$search_array_options=$extrafields->getOptionalsFromPost($extralabels,'','search_');

$arrayfields=array(
    'b.rowid'=>array('label'=>$langs->trans("Ref"), 'checked'=>1),
    'description'=>array('label'=>$langs->trans("Description"), 'checked'=>1),
    'b.dateo'=>array('label'=>$langs->trans("DateOperationShort"), 'checked'=>1),
    'b.datev'=>array('label'=>$langs->trans("DateValueShort"), 'checked'=>1),
    'type'=>array('label'=>$langs->trans("Type"), 'checked'=>1),
    'b.num_chq'=>array('label'=>$langs->trans("Numero"), 'checked'=>1),
    'bu.label'=>array('label'=>$langs->trans("ThirdParty"), 'checked'=>1, 'position'=>500),
    'ba.ref'=>array('label'=>$langs->trans("BankAccount"), 'checked'=>(($id > 0 || ! empty($ref))?0:1), 'position'=>1000),
    'b.debit'=>array('label'=>$langs->trans("Debit"), 'checked'=>1, 'position'=>600),
    'b.credit'=>array('label'=>$langs->trans("Credit"), 'checked'=>1, 'position'=>605),
	'balancebefore'=>array('label'=>$langs->trans("BalanceBefore"), 'checked'=>0, 'position'=>1000),
	'balance'=>array('label'=>$langs->trans("Balance"), 'checked'=>1, 'position'=>1001),
	'b.num_releve'=>array('label'=>$langs->trans("AccountStatement"), 'checked'=>1, 'position'=>1010),
    'b.conciliated'=>array('label'=>$langs->trans("Conciliated"), 'enabled'=> $object->rappro, 'checked'=>($action == 'reconcile'?1:0), 'position'=>1020),
);
// Extra fields
if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label))
{
    foreach($extrafields->attribute_label as $key => $val)
    {
		if (! empty($extrafields->attribute_list[$key])) $arrayfields["ef.".$key]=array('label'=>$extrafields->attribute_label[$key], 'checked'=>(($extrafields->attribute_list[$key]<0)?0:1), 'position'=>$extrafields->attribute_pos[$key], 'enabled'=>(abs($extrafields->attribute_list[$key])!=3 && $extrafields->attribute_perms[$key]));
    }
}



/*
 * Actions
 */

if (GETPOST('cancel','alpha')) { $action='list'; $massaction=''; }
if (! GETPOST('confirmmassaction','alpha') && $massaction != 'presend' && $massaction != 'confirm_presend') { $massaction=''; }

$parameters=array();
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

if (GETPOST('button_removefilter_x','alpha') || GETPOST('button_removefilter.x','alpha') || GETPOST('button_removefilter','alpha')) // All tests are required to be compatible with all browsers
{
    $search_dt_start='';
    $search_dt_end='';
    $search_dv_start='';
    $search_dv_end='';
    $description="";
	$search_type="";
	$debit="";
	$credit="";
	$search_bid="";
	$search_ref="";
	$search_req_nb='';
	$search_thirdparty='';
	$search_num_releve='';
	$search_conciliated='';
	$thirdparty='';

	$search_account="";
	if ($id > 0 || ! empty($ref)) $search_account=$object->id;
}

if (empty($reshook))
{
    $objectclass='Account';
    $objectlabel='BankTransaction';
    $permtoread = $user->rights->banque->lire;
    $permtodelete = $user->rights->banque->supprimer;
    $uploaddir = $conf->bank->dir_output;
    include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';
}

// Conciliation
if (GETPOST('confirm_reconcile') && $user->rights->banque->consolidate)
{
    $error=0;

    // Definition, nettoyage parametres
    $num_releve=trim(GETPOST("num_releve"));

    if ($num_releve)
    {
        $bankline=new AccountLine($db);
        if (isset($_POST['rowid']) && is_array($_POST['rowid']))
        {
            foreach($_POST['rowid'] as $row)
            {
                if ($row > 0)
                {
                    $result=$bankline->fetch($row);
                    $bankline->num_releve=$num_releve; //$_POST["num_releve"];
                    $result=$bankline->update_conciliation($user, GETPOST("cat"));
                    if ($result < 0)
                    {
                        setEventMessages($bankline->error, $bankline->errors, 'errors');
                        $error++;
                        break;
                    }
                }
            }
        }
        else
        {
            $error++;
            $langs->load("errors");
            setEventMessages($langs->trans("NoRecordSelected"), null, 'errors');
        }
    }
    else
    {
        $error++;
        $langs->load("errors");
        setEventMessages($langs->trans("ErrorPleaseTypeBankTransactionReportName"), null, 'errors');
    }

    if (! $error)
    {
        header('Location: '.$_SERVER["PHP_SELF"].'?id='.$id);	// To avoid to submit twice and allow back
        exit;
    }
}


if (GETPOST('save') && ! $cancel && $user->rights->banque->modifier)
{
    $error = 0;

    if (price2num($_POST["addcredit"]) > 0)
    {
        $amount = price2num($_POST["addcredit"]);
    }
    else
    {
        $amount = - price2num($_POST["adddebit"]);
    }

    $operation = GETPOST("operation",'alpha');
    $num_chq   = GETPOST("num_chq",'alpha');
    $label     = GETPOST("label",'alpha');
    $cat1      = GETPOST("cat1",'alpha');

    $bankaccountid = $id;
    if (GETPOST('add_account','int') > 0) $bankaccountid = GETPOST('add_account','int');

    if (! $dateop) {
        $error++;
        setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Date")), null, 'errors');
    }
    if (! $operation) {
        $error++;
        setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Type")), null, 'errors');
    }
    if (! $label) {
        $error++;
        setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Label")), null, 'errors');
    }
    if (! $amount) {
        $error++;
        setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Amount")), null, 'errors');
    }
    if (! $bankaccountid > 0)
    {
    	$error++;
    	setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("BankAccount")), null, 'errors');
    }
    /*if (! empty($conf->accounting->enabled) && (empty($search_accountancy_code) || $search_accountancy_code == '-1'))
    {
    	setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("AccountAccounting")), null, 'errors');
    	$error++;
    }*/

    if (! $error)
    {
    	$objecttmp = new Account($db);
    	$objecttmp->fetch($bankaccountid);
        $insertid = $objecttmp->addline($dateop, $operation, $label, $amount, $num_chq, ($cat1 > 0 ? $cat1 : 0), $user, '', '', $search_accountancy_code);
        if ($insertid > 0)
        {
            setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
            header("Location: ".$_SERVER['PHP_SELF'].($id ? "?id=".$id : ''));
            exit;
        }
        else
        {
            setEventMessages($object->error, $object->errors, 'errors');
        }
    }
    else
    {
        $action='addline';
    }
}

if ($action == 'confirm_delete' && $confirm == 'yes' && $user->rights->banque->modifier)
{
    $accline=new AccountLine($db);
    $result=$accline->fetch(GETPOST("rowid"));
    $result=$accline->delete($user);
}



/*
 * View
 */

$form = new Form($db);
$formother = new FormOther($db);
$formaccounting = new FormAccounting($db);

$companystatic=new Societe($db);
$bankaccountstatic=new Account($db);

$societestatic=new Societe($db);
$userstatic=new User($db);
$chargestatic=new ChargeSociales($db);
$loanstatic=new Loan($db);
$memberstatic=new Adherent($db);
$paymentstatic=new Paiement($db);
$paymentsupplierstatic=new PaiementFourn($db);
$paymentvatstatic=new TVA($db);
$paymentsalstatic=new PaymentSalary($db);
$donstatic=new Don($db);
$paymentexpensereportstatic=new PaymentExpenseReport($db);
$bankstatic=new Account($db);
$banklinestatic=new AccountLine($db);

$now = dol_now();


// Must be before button action
$param='';
if (! empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param.='&contextpage='.urlencode($contextpage);
if ($limit > 0 && $limit != $conf->liste_limit) $param.='&limit='.urlencode($limit);
if ($id > 0) $param.='&id='.urlencode($id);
if (!empty($ref)) $param.='&ref='.urlencode($ref);
if (!empty($search_ref)) $param.='&search_ref='.urlencode($search_ref);
if (!empty($description)) $param.='&description='.urlencode($description);
if (!empty($search_type)) $param.='&type='.urlencode($search_type);
if (!empty($search_thirdparty)) $param.='&search_thirdparty='.urlencode($search_thirdparty);
if (!empty($debit)) $param.='&debit='.urlencode($debit);
if (!empty($credit)) $param.='&credit='.urlencode($credit);
if (!empty($search_account)) $param.='&search_account='.urlencode($search_account);
if (!empty($search_num_releve)) $param.='&search_num_releve='.urlencode($search_num_releve);
if ($search_conciliated != '' && $search_conciliated != '-1')  $param.='&search_conciliated='.urlencode($search_conciliated);
if ($search_bid > 0)  $param.='&search_bid='.urlencode($search_bid);
if (dol_strlen($search_dt_start) > 0) $param .= '&search_start_dtmonth=' . GETPOST('search_start_dtmonth', 'int') . '&search_start_dtday=' . GETPOST('search_start_dtday', 'int') . '&search_start_dtyear=' . GETPOST('search_start_dtyear', 'int');
if (dol_strlen($search_dt_end) > 0)   $param .= '&search_end_dtmonth=' . GETPOST('search_end_dtmonth', 'int') . '&search_end_dtday=' . GETPOST('search_end_dtday', 'int') . '&search_end_dtyear=' . GETPOST('search_end_dtyear', 'int');
if (dol_strlen($search_dv_start) > 0) $param .= '&search_start_dvmonth=' . GETPOST('search_start_dvmonth', 'int') . '&search_start_dvday=' . GETPOST('search_start_dvday', 'int') . '&search_start_dvyear=' . GETPOST('search_start_dvyear', 'int');
if (dol_strlen($search_dv_end) > 0)   $param .= '&search_end_dvmonth=' . GETPOST('search_end_dvmonth', 'int') . '&search_end_dvday=' . GETPOST('search_end_dvday', 'int') . '&search_end_dvyear=' . GETPOST('search_end_dvyear', 'int');
if ($search_req_nb) $param.='&req_nb='.urlencode($search_req_nb);
if (GETPOST("search_thirdparty",'int')) $param.='&thirdparty='.urlencode(GETPOST("search_thirdparty",'int'));
if ($optioncss != '')       $param.='&optioncss='.urlencode($optioncss);
if ($action == 'reconcile') $param.='&action=reconcile';
// Add $param from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

$options = array();

$buttonreconcile = '';

if ($id > 0 || ! empty($ref))
{
	$title = $langs->trans("FinancialAccount").' - '.$langs->trans("Transactions");
	$helpurl = "";
	llxHeader('',$title,$helpurl);

    // Load bank groups
    require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/bankcateg.class.php';
    $bankcateg = new BankCateg($db);

    foreach ($bankcateg->fetchAll() as $bankcategory) {
        $options[$bankcategory->id] = $bankcategory->label;
    }

    // Bank card
    $head=bank_prepare_head($object);
    dol_fiche_head($head,'journal',$langs->trans("FinancialAccount"),0,'account');

    $linkback = '<a href="'.DOL_URL_ROOT.'/compta/bank/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

    dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref, '', 0, '', '', 1);

    dol_fiche_end();


    /*
     * Buttons actions
     */

    if ($action != 'reconcile')
    {
        if ($object->canBeConciliated() > 0)
        {
            // If not cash account and can be reconciliate
            if ($user->rights->banque->consolidate) {
            	$newparam = $param;
            	$newparam = preg_replace('/search_conciliated=\d+/i','',$newparam);
            	$buttonreconcile = '<a class="butActionNew" style="margin-bottom: 5px !important; margin-top: 5px !important" href="'.DOL_URL_ROOT.'/compta/bank/bankentries_list.php?action=reconcile&search_conciliated=0'.$newparam.'">'.$langs->trans("Conciliate").'</a>';
            } else {
            	$buttonreconcile = '<a class="butActionNewRefused" style="margin-bottom: 5px !important; margin-top: 5px !important" title="'.$langs->trans("NotEnoughPermissions").'" href="#">'.$langs->trans("Conciliate").'</a>';
            }
        }
    }
}
else
{
	llxHeader('', $langs->trans("BankTransactions"), '', '', 0, 0, array(), array(), $param);
}

$sql = "SELECT b.rowid, b.dateo as do, b.datev as dv, b.amount, b.label, b.rappro as conciliated, b.num_releve, b.num_chq,";
$sql.= " b.fk_account, b.fk_type,";
$sql.= " ba.rowid as bankid, ba.ref as bankref,";
$sql.= " bu.url_id,";
$sql.= " s.nom, s.name_alias, s.client, s.fournisseur, s.email, s.code_client, s.code_fournisseur, s.code_compta, s.code_compta_fournisseur";
// Add fields from extrafields
foreach ($extrafields->attribute_label as $key => $val) $sql.=($extrafields->attribute_type[$key] != 'separate' ? ",ef.".$key.' as options_'.$key : '');
// Add fields from hooks
$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldListSelect',$parameters);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;
$sql.= " FROM ";
if ($search_bid>0) $sql.= MAIN_DB_PREFIX."bank_class as l,";
$sql.= " ".MAIN_DB_PREFIX."bank_account as ba,";
$sql.= " ".MAIN_DB_PREFIX."bank as b";
if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label)) $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."bank_extrafields as ef on (b.rowid = ef.fk_object)";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."bank_url as bu ON bu.fk_bank = b.rowid AND type = 'company'";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON bu.url_id = s.rowid";
$sql.= " WHERE b.fk_account = ba.rowid";
$sql.= " AND ba.entity IN (".getEntity('bank_account').")";
if ($search_account > 0) $sql.=" AND b.fk_account = ".$search_account;
// Search period criteria
if (dol_strlen($search_dt_start)>0) $sql .= " AND b.dateo >= '" . $db->idate($search_dt_start) . "'";
if (dol_strlen($search_dt_end)>0) $sql .= " AND b.dateo <= '" . $db->idate($search_dt_end) . "'";
// Search period criteria
if (dol_strlen($search_dv_start)>0) $sql .= " AND b.datev >= '" . $db->idate($search_dv_start) . "'";
if (dol_strlen($search_dv_end)>0) $sql .= " AND b.datev <= '" . $db->idate($search_dv_end) . "'";
if ($search_ref) $sql.=natural_search("b.rowid", $search_ref, 1);
if ($search_req_nb) $sql.= natural_search("b.num_chq", $search_req_nb);
if ($search_num_releve) $sql.= natural_search("b.num_releve", $search_num_releve);
if ($search_conciliated != '' && $search_conciliated != '-1') $sql.= " AND b.rappro = ".$search_conciliated;
if ($search_thirdparty) $sql.= natural_search("s.nom", $search_thirdparty);
if ($description) $sql.= natural_search("b.label", $description);       // Warning some text are just translation keys, not translated strings
if ($search_bid > 0) $sql.= " AND b.rowid=l.lineid AND l.fk_categ=".$search_bid;
if (! empty($search_type)) $sql.= " AND b.fk_type = '".$db->escape($search_type)."' ";
// Search criteria amount
$debit = price2num(str_replace('-','',$debit));
$credit = price2num(str_replace('-','',$credit));
if ($debit) $sql.= natural_search('- b.amount', $debit, 1);
if ($credit) $sql.= natural_search('b.amount', $credit, 1);
// Add where from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';

// Add where from hooks
$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldListWhere',$parameters);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;

$sql.= $db->order($sortfield,$sortorder);

$nbtotalofrecords = '';
$nbtotalofpages = 0;
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
    $result = $db->query($sql);
    $nbtotalofrecords = $db->num_rows($result);
    $nbtotalofpages = ceil($nbtotalofrecords/$limit);
}

if (($id > 0 || ! empty($ref)) && ((string) $page == ''))
{
    // We open a list of transaction of a dedicated account and no page was set by defaut
    // We force on last page.
    $page = ($nbtotalofpages - 1);
    $offset = $limit * $page;
    if ($page < 0) $page = 0;
}
if ($page >= $nbtotalofpages)
{
    // If we made a search and result has low page than the page number we were on
    $page = ($nbtotalofpages -1);
    $offset = $limit * $page;
    if ($page < 0) $page = 0;
}

// If not account defined $mode_balance_ok=false
if (empty($search_account)) $mode_balance_ok=false;
// If a search is done $mode_balance_ok=false
if (! empty($search_ref)) $mode_balance_ok=false;
if (! empty($req_nb)) $mode_balance_ok=false;
if (! empty($search_type)) $mode_balance_ok=false;
if (! empty($debit)) $mode_balance_ok=false;
if (! empty($credit)) $mode_balance_ok=false;
if (! empty($thirdparty)) $mode_balance_ok=false;

$sql.= $db->plimit($limit+1, $offset);
//print $sql;
dol_syslog('compta/bank/bankentries_list.php', LOG_DEBUG);
$resql = $db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);

	$arrayofselected=is_array($toselect)?$toselect:array();

    // List of mass actions available
    $arrayofmassactions =  array(
        //'presend'=>$langs->trans("SendByMail"),
        //'builddoc'=>$langs->trans("PDFMerge"),
    );
    //if ($user->rights->bank->supprimer) $arrayofmassactions['predelete']=$langs->trans("Delete");
    if (in_array($massaction, array('presend','predelete'))) $arrayofmassactions=array();
    $massactionbutton=$form->selectMassAction('', $arrayofmassactions);

    // Confirmation delete
    if ($action == 'delete')
    {
        $text=$langs->trans('ConfirmDeleteTransaction');
        print $form->formconfirm($_SERVER['PHP_SELF'].'?id='.$object->id.'&rowid='.GETPOST("rowid"), $langs->trans('DeleteTransaction'), $text, 'confirm_delete', null, '', 1);
    }

    // Lines of title fields
	print '<form method="post" action="'.$_SERVER["PHP_SELF"].'" name="search_form">'."\n";
	if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="action" value="'.($action?$action:'search').'">';
	print '<input type="hidden" name="view" value="'.dol_escape_htmltag($view).'">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
    print '<input type="hidden" name="page" value="'.$page.'">';
	print '<input type="hidden" name="id" value="'.$id.'">';
	print '<input type="hidden" name="ref" value="'.$ref.'">';
	if (GETPOST('bid')) print '<input type="hidden" name="bid" value="'.GETPOST("bid").'">';

	// Form to reconcile
	if ($user->rights->banque->consolidate && $action == 'reconcile')
	{
	    print '<div class="valignmiddle inline-block" style="padding-right: 20px;">';
	    print '<strong>'.$langs->trans("InputReceiptNumber").'</strong>: ';
	    print '<input class="flat" id="num_releve" name="num_releve" type="text" value="'.(GETPOST('num_releve')?GETPOST('num_releve'):'').'" size="10">';  // The only default value is value we just entered
	    print '</div>';
	    if (is_array($options) && count($options))
	    {
	        print $langs->trans("EventualyAddCategory").': ';
	        print Form::selectarray('cat', $options, GETPOST('cat'), 1);
	    }
	    print '<br>'.$langs->trans("ThenCheckLinesAndConciliate").' ';
	    print '<input class="button" name="confirm_reconcile" type="submit" value="'.$langs->trans("Conciliate").'">';
	    print ' '.$langs->trans("or").' ';
	    print '<input type="submit" name="cancel" class="button" value="'.$langs->trans("Cancel").'">';

	    // Show last bank statements
	    $nbmax=15;      // We accept to show last 15 receipts (so we can have more than one year)
	    $liste="";
	    $sql = "SELECT DISTINCT num_releve FROM ".MAIN_DB_PREFIX."bank";
	    $sql.= " WHERE fk_account=".$object->id." AND num_releve IS NOT NULL";
	    $sql.= $db->order("num_releve","DESC");
	    $sql.= $db->plimit($nbmax+1);
	    print '<br><br>';
	    print $langs->trans("LastAccountStatements").' : ';
	    $resqlr=$db->query($sql);
	    if ($resqlr)
	    {
	        $numr=$db->num_rows($resqlr);
	        $i=0;
	        $last_ok=0;
	        while (($i < $numr) && ($i < $nbmax))
	        {
	            $objr = $db->fetch_object($resqlr);
	            if (! $last_ok) {
	                $last_releve = $objr->num_releve;
	                $last_ok=1;
	            }
	            $i++;
	            $liste='<a href="'.DOL_URL_ROOT.'/compta/bank/releve.php?account='.$id.'&amp;num='.$objr->num_releve.'">'.$objr->num_releve.'</a> &nbsp; '.$liste;
	        }
	        if ($numr >= $nbmax) $liste="... &nbsp; ".$liste;
	        print $liste;
	        if ($numr <= 0) print '<b>'.$langs->trans("None").'</b>';
	    }
	    else
	    {
	        dol_print_error($db);
	    }

		// Using BANK_REPORT_LAST_NUM_RELEVE to automatically report last num (or not)
		if ($conf->global->BANK_REPORT_LAST_NUM_RELEVE == 1)
		{
			print '
			    <script type="text/javascript">
			    	$("#num_releve").val("' . $last_releve . '");
			    </script>
			';
		}
		print '<br><br>';
	}

	// Form to add a transaction with no invoice
	if ($user->rights->banque->modifier && $action == 'addline')
	{
		print load_fiche_titre($langs->trans("AddBankRecordLong"),'','');

		print '<table class="noborder" width="100%">';

		print '<tr class="liste_titre">';
		print '<td>'.$langs->trans("Description").'</td>';
		print '<td>'.$langs->trans("Date").'</td>';
		print '<td>&nbsp;</td>';
		print '<td>'.$langs->trans("Type").'</td>';
		print '<td>'.$langs->trans("Numero").'</td>';
		//if (! $search_account > 0)
		//{
			print '<td align=right>'.$langs->trans("BankAccount").'</td>';
		//}
		print '<td align=right>'.$langs->trans("Debit").'</td>';
		print '<td align=right>'.$langs->trans("Credit").'</td>';
		/*if (! empty($conf->accounting->enabled))
		{
			print '<td align="center">';
			print $langs->trans("AccountAccounting");
			print '</td>';
		}*/
		print '<td align="center">&nbsp;</td>';
		print '</tr>';

		print '<tr>';
		print '<td>';
		print '<input name="label" class="flat minwidth200" type="text" value="'.GETPOST("label","alpha").'">';
		if (is_array($options) && count($options))
		{
			print '<br>'.$langs->trans("Rubrique").': ';
			print Form::selectarray('cat1', $options, GETPOST('cat1'), 1);
		}
		print '</td>';
		print '<td class="nowrap">';
		$form->select_date(empty($dateop)?-1:$dateop,'op',0,0,0,'transaction');
		print '</td>';
		print '<td>&nbsp;</td>';
		print '<td class="nowrap">';
		$form->select_types_paiements((GETPOST('operation')?GETPOST('operation'):($object->courant == Account::TYPE_CASH ? 'LIQ' : '')), 'operation', '1,2', 2, 1);
		print '</td>';
		print '<td>';
		print '<input name="num_chq" class="flat" type="text" size="4" value="'.GETPOST("num_chq","alpha").'">';
		print '</td>';
		//if (! $search_account > 0)
		//{
			print '<td align=right>';
			$form->select_comptes(GETPOST('add_account','int')?GETPOST('add_account','int'):$search_account,'add_account',0,'',1, ($id > 0 || ! empty($ref)?' disabled="disabled"':''));
			print '</td>';
		//}
		print '<td align="right"><input name="adddebit" class="flat" type="text" size="4" value="'.GETPOST("adddebit","alpha").'"></td>';
		print '<td align="right"><input name="addcredit" class="flat" type="text" size="4" value="'.GETPOST("addcredit","alpha").'"></td>';
		/*if (! empty($conf->accounting->enabled))
		{
			print '<td align="center">';
			print $formaccounting->select_account($search_accountancy_code, 'search_accountancy_code', 1, null, 1, 1, '');
			print '</td>';
		}*/
		print '<td align="center">';
		print '<input type="submit" name="save" class="button" value="'.$langs->trans("Add").'"><br>';
		print '<input type="submit" name="cancel" class="button" value="'.$langs->trans("Cancel").'">';
		print '</td></tr>';

		print '</table>';
		print '<br>';
	}

	/// ajax to adjust value date with plus and less picto
	print '
    <script type="text/javascript">
    $(function() {
    	$("a.ajax").each(function(){
    		var current = $(this);
    		current.click(function()
    		{
    			$.get("'.DOL_URL_ROOT.'/core/ajax/bankconciliate.php?"+current.attr("href").split("?")[1], function(data)
    			{
    			    console.log(data)
    				current.parent().prev().replaceWith(data);
    			});
    			return false;
    		});
    	});
    });
    </script>
    ';

	$i = 0;

	// Title
	$bankcateg=new BankCateg($db);

	$newcardbutton = '';
	if ($action != 'addline' && $action != 'reconcile')
	{
		if (empty($conf->global->BANK_DISABLE_DIRECT_INPUT))
		{
			if (! empty($conf->global->BANK_USE_VARIOUS_PAYMENT))	// If direct entries is done using miscellaneous payments
			{
				if ($user->rights->banque->modifier) {
					$newcardbutton = '<a class="butActionNew" href="'.DOL_URL_ROOT.'/compta/bank/various_payment/card.php?action=create&accountid='.$search_account.'&backtopage='.urlencode($_SERVER['PHP_SELF'].'?id='.urlencode($search_account)).'"><span class="valignmiddle">'.$langs->trans("AddBankRecord").'</span>';
					$newcardbutton.= '<span class="fa fa-plus-circle valignmiddle"></span>';
					$newcardbutton.= '</a>';
				} else {
					$newcardbutton = '<a class="butActionNewRefused" title="'.$langs->trans("NotEnoughPermissions").'" href="#">'.$langs->trans("AddBankRecord");
					$newcardbutton.= '<span class="fa fa-plus-circle valignmiddle"></span>';
					$newcardbutton.= '</a>';
				}
			}
			else													// If direct entries is not done using miscellaneous payments
			{
				if ($user->rights->banque->modifier) {
					$newcardbutton = '<a class="butActionNew" href="'.$_SERVER["PHP_SELF"].'?action=addline&page='.$page.$param.'">'.$langs->trans("AddBankRecord");
					$newcardbutton.= '<span class="fa fa-plus-circle valignmiddle"></span>';
					$newcardbutton.= '</a>';
				} else {
					$newcardbutton = '<a class="butActionNewRefused" title="'.$langs->trans("NotEnoughPermissions").'" href="#">'.$langs->trans("AddBankRecord");
					$newcardbutton.= '<span class="fa fa-plus-circle valignmiddle"></span>';
					$newcardbutton.= '</a>';
				}
			}
		}
		else
		{
			$newcardbutton = '<a class="butActionNewRefused" title="'.$langs->trans("FeatureDisabled").'" href="#">'.$langs->trans("AddBankRecord");
			$newcardbutton.= '<span class="fa fa-plus-circle valignmiddle"></span>';
			$newcardbutton.= '</a>';
		}
	}

	$morehtml='<div class="inline-block '.(($buttonreconcile || $newcardbutton)?'marginrightonly':'').'">';
	$morehtml.= '<label for="pageplusone">'.$langs->trans("Page")."</label> "; // ' Page ';
	$morehtml.='<input type="text" name="pageplusone" id="pageplusone" class="flat right width25" value="'.($page+1).'">';
	$morehtml.='/'.$nbtotalofpages.' ';
	$morehtml.='</div>';

	if ($action != 'addline' && $action != 'reconcile')
	{
		$morehtml.=$buttonreconcile;
	}

	$morehtml.=$newcardbutton;

	$picto='title_bank';
	if ($id > 0 || ! empty($ref)) $picto='';

	print_barre_liste($langs->trans("BankTransactions"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, $picto, 0, $morehtml, '', $limit);

	// We can add page now to param
	if ($page != '') $param.='&page='.urlencode($page);

	$moreforfilter = '';

	$moreforfilter.='<div class="divsearchfield">';
	$moreforfilter .= $langs->trans('DateOperationShort').' : ';
	$moreforfilter .= '<div class="nowrap'.($conf->browser->layout=='phone'?' centpercent':'').' inline-block">'.$langs->trans('From') . ' ';
	$moreforfilter .= $form->select_date($search_dt_start, 'search_start_dt', 0, 0, 1, "search_form", 1, 0, 1).'</div>';
	//$moreforfilter .= ' - ';
	$moreforfilter .= '<div class="nowrap'.($conf->browser->layout=='phone'?' centpercent':'').' inline-block">'.$langs->trans('to') . ' ' . $form->select_date($search_dt_end, 'search_end_dt', 0, 0, 1, "search_form", 1, 0, 1).'</div>';
	$moreforfilter .= '</div>';

	$moreforfilter.='<div class="divsearchfield">';
	$moreforfilter .= $langs->trans('DateValueShort').' : ';
	$moreforfilter .= '<div class="nowrap'.($conf->browser->layout=='phone'?' centpercent':'').' inline-block">'.$langs->trans('From') . ' ';
	$moreforfilter .= $form->select_date($search_dv_start, 'search_start_dv', 0, 0, 1, "search_form", 1, 0, 1).'</div>';
	//$moreforfilter .= ' - ';
	$moreforfilter .= '<div class="nowrap'.($conf->browser->layout=='phone'?' centpercent':'').' inline-block">'.$langs->trans('to') . ' ' . $form->select_date($search_dv_end, 'search_end_dv', 0, 0, 1, "search_form", 1, 0, 1).'</div>';
	$moreforfilter .= '</div>';

	if (! empty($conf->categorie->enabled))
	{
		// Categories
		if (! empty($conf->categorie->enabled)  && ! empty($user->rights->categorie->lire))
		{
			$langs->load('categories');

			// Bank line
			$moreforfilter.='<div class="divsearchfield">';
			$moreforfilter.=$langs->trans('RubriquesTransactions').' : ';
			$cate_arbo = $form->select_all_categories(Categorie::TYPE_BANK_LINE, $search_bid, 'parent', null, null, 1);
			$moreforfilter.=$form->selectarray('search_bid', $cate_arbo, $search_bid, 1);
			$moreforfilter.='</div>';
		}
	}

	$parameters=array();
	$reshook=$hookmanager->executeHooks('printFieldPreListTitle',$parameters);    // Note that $action and $object may have been modified by hook
	if (empty($reshook)) $moreforfilter .= $hookmanager->resPrint;
	else $moreforfilter = $hookmanager->resPrint;

	if ($moreforfilter)
	{
		print '<div class="liste_titre liste_titre_bydiv centpercent">';
		print $moreforfilter;
		print '</div>'."\n";
	}

    $varpage=empty($contextpage)?$_SERVER["PHP_SELF"]:$contextpage;
    $selectedfields=$form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage);	// This also change content of $arrayfields

    print '<div class="div-table-responsive">';
    print '<table class="tagtable liste'.($moreforfilter?" listwithfilterbefore":"").'">'."\n";


	print '<tr class="liste_titre_filter">';
	if (! empty($arrayfields['b.rowid']['checked']))
	{
	    print '<td class="liste_titre">';
    	print '<input type="text" class="flat" name="search_ref" size="2" value="'.dol_escape_htmltag($search_ref).'">';
	    print '</td>';
	}
	if (! empty($arrayfields['description']['checked']))
	{
	    print '<td class="liste_titre">';
    	//print '<input type="text" class="flat" name="description" size="10" value="'.dol_escape_htmltag($description).'">';
    	print '</td>';
	}
	if (! empty($arrayfields['b.dateo']['checked']))
	{
        print '<td class="liste_titre">&nbsp;</td>';
	}
	if (! empty($arrayfields['b.datev']['checked']))
	{
        print '<td class="liste_titre">&nbsp;</td>';
	}
	if (! empty($arrayfields['type']['checked']))
	{
        print '<td class="liste_titre" align="center">';
        $form->select_types_paiements(empty($search_type)?'':$search_type, 'search_type', '', 2, 1, 1, 0, 1, 'maxwidth100');
        print '</td>';
	}
	if (! empty($arrayfields['b.num_chq']['checked']))
	{
        // Numero
        print '<td class="liste_titre" align="center"><input type="text" class="flat" name="req_nb" value="'.dol_escape_htmltag($search_req_nb).'" size="2"></td>';
	}
	if (! empty($arrayfields['bu.label']['checked']))
	{
	    print '<td class="liste_titre"><input type="text" class="flat" name="search_thirdparty" value="'.dol_escape_htmltag($search_thirdparty).'" size="10"></td>';
	}
	if (! empty($arrayfields['ba.ref']['checked']))
	{
    	print '<td class="liste_titre" align="right">';
    	$form->select_comptes($search_account,'search_account',0,'',1, ($id > 0 || ! empty($ref)?' disabled="disabled"':''));
    	print '</td>';
	}
	if (! empty($arrayfields['b.debit']['checked']))
	{
    	print '<td class="liste_titre" align="right">';
    	print '<input type="text" class="flat" name="debit" size="4" value="'.dol_escape_htmltag($debit).'">';
    	print '</td>';
	}
	if (! empty($arrayfields['b.credit']['checked']))
	{
    	print '<td class="liste_titre" align="right">';
    	print '<input type="text" class="flat" name="credit" size="4" value="'.dol_escape_htmltag($credit).'">';
    	print '</td>';
	}
	if (! empty($arrayfields['balancebefore']['checked']))
	{
		print '<td class="liste_titre" align="right">';
		$htmltext=$langs->trans("BalanceVisibilityDependsOnSortAndFilters", $langs->transnoentitiesnoconv("DateValue"));
		print $form->textwithpicto('', $htmltext, 1);
		print '</td>';
	}
	if (! empty($arrayfields['balance']['checked']))
	{
		print '<td class="liste_titre" align="right">';
		$htmltext=$langs->trans("BalanceVisibilityDependsOnSortAndFilters", $langs->transnoentitiesnoconv("DateValue"));
		print $form->textwithpicto('', $htmltext, 1);
		print '</td>';
	}
	// Numero statement
	if (! empty($arrayfields['b.num_releve']['checked']))
	{
        print '<td class="liste_titre" align="center"><input type="text" class="flat" name="search_num_releve" value="'.dol_escape_htmltag($search_num_releve).'" size="3"></td>';
	}
	// Conciliated
	if (! empty($arrayfields['b.conciliated']['checked']))
	{
        print '<td class="liste_titre" align="center">';
        print $form->selectyesno('search_conciliated', $search_conciliated, 1, false, 1);
        print '</td>';
	}
	print '<td class="liste_titre" align="middle">';
	print '</td>';
	print '<td class="liste_titre" align="middle">';
	$searchpicto=$form->showFilterAndCheckAddButtons($massactionbutton?1:0, 'checkforselect', 1);
	print $searchpicto;
    print '</td>';
	print "</tr>\n";

	// Fields title
	print '<tr class="liste_titre">';
	if (! empty($arrayfields['b.rowid']['checked']))            print_liste_field_titre($arrayfields['b.rowid']['label'],$_SERVER['PHP_SELF'],'b.rowid','',$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['description']['checked']))        print_liste_field_titre($arrayfields['description']['label'],$_SERVER['PHP_SELF'],'','',$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['b.dateo']['checked']))            print_liste_field_titre($arrayfields['b.dateo']['label'],$_SERVER['PHP_SELF'],'b.dateo','',$param,'align="center"',$sortfield,$sortorder);
	if (! empty($arrayfields['b.datev']['checked']))            print_liste_field_titre($arrayfields['b.datev']['label'],$_SERVER['PHP_SELF'],'b.datev,b.dateo,b.rowid','',$param,'align="center"',$sortfield,$sortorder);
	if (! empty($arrayfields['type']['checked']))               print_liste_field_titre($arrayfields['type']['label'],$_SERVER['PHP_SELF'],'','',$param,'align="center"',$sortfield,$sortorder);
	if (! empty($arrayfields['b.num_chq']['checked']))          print_liste_field_titre($arrayfields['b.num_chq']['label'],$_SERVER['PHP_SELF'],'b.num_chq','',$param,'align="center"',$sortfield,$sortorder);
	if (! empty($arrayfields['bu.label']['checked']))           print_liste_field_titre($arrayfields['bu.label']['label'],$_SERVER['PHP_SELF'],'bu.label','',$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['ba.ref']['checked']))             print_liste_field_titre($arrayfields['ba.ref']['label'],$_SERVER['PHP_SELF'],'ba.ref','',$param,'align="right"',$sortfield,$sortorder);
	if (! empty($arrayfields['b.debit']['checked']))            print_liste_field_titre($arrayfields['b.debit']['label'],$_SERVER['PHP_SELF'],'b.amount','',$param,'align="right"',$sortfield,$sortorder);
	if (! empty($arrayfields['b.credit']['checked']))           print_liste_field_titre($arrayfields['b.credit']['label'],$_SERVER['PHP_SELF'],'b.amount','',$param,'align="right"',$sortfield,$sortorder);
	if (! empty($arrayfields['balancebefore']['checked']))      print_liste_field_titre($arrayfields['balancebefore']['label'],$_SERVER['PHP_SELF'],'','',$param,'align="right"',$sortfield,$sortorder);
	if (! empty($arrayfields['balance']['checked']))            print_liste_field_titre($arrayfields['balance']['label'],$_SERVER['PHP_SELF'],'','',$param,'align="right"',$sortfield,$sortorder);
	if (! empty($arrayfields['b.num_releve']['checked']))       print_liste_field_titre($arrayfields['b.num_releve']['label'],$_SERVER['PHP_SELF'],'b.num_releve','',$param,'align="center"',$sortfield,$sortorder);
	if (! empty($arrayfields['b.conciliated']['checked']))      print_liste_field_titre($arrayfields['b.conciliated']['label'],$_SERVER['PHP_SELF'],'b.rappro','',$param,'align="center"',$sortfield,$sortorder);
	// Extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';
	// Hook fields
	$parameters=array('arrayfields'=>$arrayfields,'param'=>$param,'sortfield'=>$sortfield,'sortorder'=>$sortorder);
	$reshook=$hookmanager->executeHooks('printFieldListTitle',$parameters);    // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	print_liste_field_titre('', $_SERVER["PHP_SELF"],"",'','','align="right"',$sortfield,$sortorder,'maxwidthsearch ');
	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"],"",'','','align="center"',$sortfield,$sortorder,'maxwidthsearch ');
	print "</tr>\n";

	$balance = 0;    // For balance
	$balancebefore = 0;    // For balance
	$balancecalculated = false;
	$posconciliatecol = 0;

	// Loop on each record
	$sign = 1;

    $totalarray=array();
    while ($i < min($num,$limit))
    {
        $objp = $db->fetch_object($resql);

        // If we are in a situation where we need/can show balance, we calculate the start of balance
        if (! $balancecalculated && (! empty($arrayfields['balancebefore']['checked']) || ! empty($arrayfields['balance']['checked'])) && $mode_balance_ok)
        {
            if (! $search_account)
            {
                dol_print_error('', 'account is not defined but $mode_balance_ok is true');
                exit;
            }

            // Loop on each record before
            $sign = 1;
            $i = 0;
            $sqlforbalance='SELECT SUM(b.amount) as previoustotal';
            $sqlforbalance.= " FROM ";
            $sqlforbalance.= " ".MAIN_DB_PREFIX."bank_account as ba,";
            $sqlforbalance.= " ".MAIN_DB_PREFIX."bank as b";
            $sqlforbalance.= " WHERE b.fk_account = ba.rowid";
            $sqlforbalance.= " AND ba.entity IN (".getEntity('bank_account').")";
            $sqlforbalance.= " AND b.fk_account = ".$search_account;
            $sqlforbalance.= " AND (b.datev < '" . $db->idate($db->jdate($objp->dv)) . "' OR (b.datev = '" . $db->idate($db->jdate($objp->dv)) . "' AND (b.dateo < '".$db->idate($db->jdate($objp->do))."' OR (b.dateo = '".$db->idate($db->jdate($objp->do))."' AND b.rowid < ".$objp->rowid."))))";
            $resqlforbalance = $db->query($sqlforbalance);
            //print $sqlforbalance;
            if ($resqlforbalance)
            {
                $objforbalance = $db->fetch_object($resqlforbalance);
                if ($objforbalance)
                {
                	// If sort is desc,desc,desc then total of previous date + amount is the balancebefore of the previous line before the line to show
                	if ($sortfield == 'b.datev,b.dateo,b.rowid' && $sortorder == 'desc,desc,desc')
                	{
                		$balancebefore = $objforbalance->previoustotal + ($sign * $objp->amount);
                	}
                	// If sort is asc,asc,asc then total of previous date is balance of line before the next line to show
                	else
                	{
                		$balance = $objforbalance->previoustotal;
                	}
                }
            }
            else dol_print_error($db);

            $balancecalculated=true;

            // Output a line with start balance
            if ($user->rights->banque->consolidate && $action == 'reconcile')
            {
            	$tmpnbfieldbeforebalance=0;
            	$tmpnbfieldafterbalance=0;
            	$balancefieldfound=0;
            	foreach($arrayfields as $key => $val)
            	{
            		if ($key == 'balancebefore' || $key == 'balance')
            		{
            			$balancefieldfound++;
            			continue;
            		}
           			if (! empty($arrayfields[$key]['checked']))
           			{
           				if (! $balancefieldfound) $tmpnbfieldbeforebalance++;
           				else $tmpnbfieldafterbalance++;
           			}
            	}
            	// Extra fields
            	if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label))
            	{
            		foreach($extrafields->attribute_label as $key => $val)
            		{
            			if (! empty($arrayfields["ef.".$key]['checked']))
            			{
		           			if (! empty($arrayfields[$key]['checked']))
		           			{
		           				if (! $balancefieldfound) $tmpnbfieldbeforebalance++;
		           				else $tmpnbfieldafterbalance++;
		           			}
            			}
            		}
            	}

            	print '<tr class="oddeven trforbreak">';
            	if ($tmpnbfieldbeforebalance)
            	{
            		print '<td colspan="'.$tmpnbfieldbeforebalance.'">';
            		print '</td>';
            	}

            	if (! empty($arrayfields['balancebefore']['checked']))
            	{
	            	print '<td align="right">';
	            	print price(price2num($balance, 'MT'), 1, $langs);
	            	print '</td>';
            	}
            	if (! empty($arrayfields['balance']['checked']))
            	{
            		print '<td align="right">';
					print price(price2num($balance, 'MT'), 1, $langs);
					print '</td>';
            	}

				print '<td align="center">';
				print '<input type="checkbox" id="selectAll" />';
				print ' <script type="text/javascript">
						$("input#selectAll").change(function() {
							$("input[type=checkbox][name^=rowid]").prop("checked", $(this).is(":checked"));
						});
						</script>';
				print '</td>';
				print '<td colspan="'.($tmpnbfieldafterbalance+2).'">';
				print '</td>';
            	print '</tr>';
            }
        }


        if ($sortfield == 'b.datev,b.dateo,b.rowid' && $sortorder == 'desc,desc,desc')
        {
        	$balance = price2num($balancebefore, 'MT');		// balance = balancebefore of previous line (sort is desc)
        	$balancebefore = price2num($balancebefore - ($sign * $objp->amount),'MT');
        }
		else
		{
			$balancebefore = price2num($balance, 'MT');		// balancebefore = balance of previous line (sort is asc)
			$balance = price2num($balance + ($sign * $objp->amount),'MT');
		}

        if (empty($cachebankaccount[$objp->bankid]))
        {
            $bankaccounttmp = new Account($db);
            $bankaccounttmp->fetch($objp->bankid);
            $cachebankaccount[$objp->bankid]=$bankaccounttmp;
            $bankaccount = $bankaccounttmp;
        }
        else
        {
            $bankaccount = $cachebankaccount[$objp->bankid];
        }

        print '<tr class="oddeven">';

        // Ref
    	if (! empty($arrayfields['b.rowid']['checked']))
    	{
                print '<td align="left" class="nowrap">';
                print "<a href=\"ligne.php?rowid=".$objp->rowid.'&save_lastsearch_values=1">'.img_object($langs->trans("ShowPayment").': '.$objp->rowid, 'account', 'class="classfortooltip"').' '.$objp->rowid."</a> &nbsp; ";
                print '</td>';
                if (! $i) $totalarray['nbfield']++;
    	}

    	// Description
    	if (! empty($arrayfields['description']['checked']))
    	{
    	    print "<td>";

    	    //print "<a href=\"ligne.php?rowid=".$objp->rowid."&amp;account=".$objp->fk_account."\">";
    	    $reg=array();
    	    preg_match('/\((.+)\)/i',$objp->label,$reg);	// Si texte entoure de parenthee on tente recherche de traduction
    	    if ($reg[1] && $langs->trans($reg[1])!=$reg[1]) print $langs->trans($reg[1]);
    	    else print dol_trunc($objp->label,40);
    	    //print "</a>&nbsp;";

    	    // Add links after description
    	    $links = $bankaccountstatic->get_url($objp->rowid);
    	    $cachebankaccount=array();
    	    foreach($links as $key=>$val)
    	    {
    	        if ($links[$key]['type']=='payment')
    	        {
    	            $paymentstatic->id=$links[$key]['url_id'];
    	            $paymentstatic->ref=$links[$key]['url_id'];
    	            print ' '.$paymentstatic->getNomUrl(2);
    	        }
    	        elseif ($links[$key]['type']=='payment_supplier')
    	        {
    	            $paymentsupplierstatic->id=$links[$key]['url_id'];
    	            $paymentsupplierstatic->ref=$links[$key]['url_id'];
    	            print ' '.$paymentsupplierstatic->getNomUrl(2);
    	        }
    	        elseif ($links[$key]['type']=='payment_sc')
    	        {
    	            print '<a href="'.DOL_URL_ROOT.'/compta/payment_sc/card.php?id='.$links[$key]['url_id'].'">';
    	            print ' '.img_object($langs->trans('ShowPayment'),'payment').' ';
    	            //print $langs->trans("SocialContributionPayment");
    	            print '</a>';
    	        }
    	        elseif ($links[$key]['type']=='payment_vat')
    	        {
    	            $paymentvatstatic->id=$links[$key]['url_id'];
    	            $paymentvatstatic->ref=$links[$key]['url_id'];
    	            print ' '.$paymentvatstatic->getNomUrl(2);
    	        }
    	        elseif ($links[$key]['type']=='payment_salary')
    	        {
    	            $paymentsalstatic->id=$links[$key]['url_id'];
    	            $paymentsalstatic->ref=$links[$key]['url_id'];
    	            print ' '.$paymentsalstatic->getNomUrl(2);
    	        }
    	        elseif ($links[$key]['type']=='payment_loan')
    	        {
    	            print '<a href="'.DOL_URL_ROOT.'/loan/payment/card.php?id='.$links[$key]['url_id'].'">';
    	            print ' '.img_object($langs->trans('ShowPayment'),'payment').' ';
    	            print '</a>';
    	        }
    	        elseif ($links[$key]['type']=='payment_donation')
    	        {
    	            print '<a href="'.DOL_URL_ROOT.'/don/payment/card.php?id='.$links[$key]['url_id'].'">';
    	            print ' '.img_object($langs->trans('ShowPayment'),'payment').' ';
    	            print '</a>';
    	        }
    	        elseif ($links[$key]['type']=='payment_expensereport')
    	        {
    	            $paymentexpensereportstatic->id=$links[$key]['url_id'];
    	            $paymentexpensereportstatic->ref=$links[$key]['url_id'];
    	            print ' '.$paymentexpensereportstatic->getNomUrl(2);
    	        }
    	        elseif ($links[$key]['type']=='banktransfert')
    	        {
    	            // Do not show link to transfer since there is no transfer card (avoid confusion). Can already be accessed from transaction detail.
    	            if ($objp->amount > 0)
    	            {
    	                $banklinestatic->fetch($links[$key]['url_id']);
    	                $bankstatic->id=$banklinestatic->fk_account;
    	                $bankstatic->label=$banklinestatic->bank_account_ref;
    	                print ' ('.$langs->trans("TransferFrom").' ';
    	                print $bankstatic->getNomUrl(1,'transactions');
    	                print ' '.$langs->trans("toward").' ';
    	                $bankstatic->id=$objp->bankid;
    	                $bankstatic->label=$objp->bankref;
    	                print $bankstatic->getNomUrl(1,'');
    	                print ')';
    	            }
    	            else
    	            {
    	                $bankstatic->id=$objp->bankid;
    	                $bankstatic->label=$objp->bankref;
    	                print ' ('.$langs->trans("TransferFrom").' ';
    	                print $bankstatic->getNomUrl(1,'');
    	                print ' '.$langs->trans("toward").' ';
    	                $banklinestatic->fetch($links[$key]['url_id']);
    	                $bankstatic->id=$banklinestatic->fk_account;
    	                $bankstatic->label=$banklinestatic->bank_account_ref;
    	                print $bankstatic->getNomUrl(1,'transactions');
    	                print ')';
    	            }
    	            //var_dump($links);
    	        }
    	        elseif ($links[$key]['type']=='company')
    	        {

    	        }
    	        elseif ($links[$key]['type']=='user')
    	        {

    	        }
    	        elseif ($links[$key]['type']=='member')
    	        {

    	        }
    	        elseif ($links[$key]['type']=='sc')
    	        {

    	        }
    	        else
    	        {
    	            // Show link with label $links[$key]['label']
    	            if (! empty($objp->label) && ! empty($links[$key]['label'])) print ' - ';
    	            print '<a href="'.$links[$key]['url'].$links[$key]['url_id'].'">';
    	            if (preg_match('/^\((.*)\)$/i',$links[$key]['label'],$reg))
    	            {
    	                // Label generique car entre parentheses. On l'affiche en le traduisant
    	                if ($reg[1]=='paiement') $reg[1]='Payment';
    	                print ' '.$langs->trans($reg[1]);
    	            }
    	            else
    	            {
    	                print ' '.$links[$key]['label'];
    	            }
    	            print '</a>';
    	        }
    	    }
    	    print '</td>';
    	    if (! $i) $totalarray['nbfield']++;
    	}

        // Date ope
    	if (! empty($arrayfields['b.dateo']['checked']))
    	{
    	   print '<td align="center" class="nowrap">';
    	   print '<span id="dateoperation_'.$objp->rowid.'">'.dol_print_date($db->jdate($objp->do),"day")."</span>";
    	   print '&nbsp;';
    	   print '<span class="inline-block">';
    	   print '<a class="ajax" href="'.$_SERVER['PHP_SELF'].'?action=doprev&amp;account='.$objp->bankid.'&amp;rowid='.$objp->rowid.'">';
    	   print img_edit_remove() . "</a> ";
    	   print '<a class="ajax" href="'.$_SERVER['PHP_SELF'].'?action=donext&amp;account='.$objp->bankid.'&amp;rowid='.$objp->rowid.'">';
    	   print img_edit_add() ."</a>";
    	   print '</span>';
    	   print "</td>\n";
                if (! $i) $totalarray['nbfield']++;
    	}

        // Date value
    	if (! empty($arrayfields['b.datev']['checked']))
    	{
    	   print '<td align="center" class="nowrap">';
    	   print '<span id="datevalue_'.$objp->rowid.'">'.dol_print_date($db->jdate($objp->dv),"day")."</span>";
    	   print '&nbsp;';
    	   print '<span class="inline-block">';
    	   print '<a class="ajax" href="'.$_SERVER['PHP_SELF'].'?action=dvprev&amp;account='.$objp->bankid.'&amp;rowid='.$objp->rowid.'">';
    	   print img_edit_remove() . "</a> ";
    	   print '<a class="ajax" href="'.$_SERVER['PHP_SELF'].'?action=dvnext&amp;account='.$objp->bankid.'&amp;rowid='.$objp->rowid.'">';
    	   print img_edit_add() ."</a>";
    	   print '</span>';
    	   print "</td>\n";
           if (! $i) $totalarray['nbfield']++;
    	}

        // Payment type
    	if (! empty($arrayfields['type']['checked']))
    	{
        	print '<td align="center" class="nowrap">';
	        $labeltype=($langs->trans("PaymentTypeShort".$objp->fk_type)!="PaymentTypeShort".$objp->fk_type)?$langs->trans("PaymentTypeShort".$objp->fk_type):$langs->getLabelFromKey($db,$objp->fk_type,'c_paiement','code','libelle','',1);
	        if ($labeltype == 'SOLD') print '&nbsp;'; //$langs->trans("InitialBankBalance");
	        else print $labeltype;
	        print "</td>\n";
                if (! $i) $totalarray['nbfield']++;
    	}

        // Num cheque
    	if (! empty($arrayfields['b.num_chq']['checked']))
    	{
    	    print '<td class="nowrap" align="center">'.($objp->num_chq?$objp->num_chq:"")."</td>\n";
    	    if (! $i) $totalarray['nbfield']++;
    	}

		// Third party
    	if (! empty($arrayfields['bu.label']['checked']))
    	{
        	print "<td>";
			if ($objp->url_id)
			{
				$companystatic->id=$objp->url_id;
				$companystatic->name=$objp->nom;
				$companystatic->name_alias=$objp->name_alias;
				$companystatic->client=$objp->client;
				$companystatic->email=$objp->email;
				$companystatic->fournisseur=$objp->fournisseur;
				$companystatic->code_client=$objp->code_client;
				$companystatic->code_fournisseur=$objp->code_fournisseur;
				$companystatic->code_compta=$objp->code_compta;
				$companystatic->code_compta_fournisseur=$objp->code_compta_fournisseur;
				print $companystatic->getNomUrl(1);
			}
			else
			{
				print '&nbsp;';
			}
			print '</td>';
            if (! $i) $totalarray['nbfield']++;
    	}

    	// Bank account
    	if (! empty($arrayfields['ba.ref']['checked']))
    	{
        	print '<td align="right" class="nowrap">';
			print $bankaccount->getNomUrl(1);
			print "</td>\n";
            if (! $i) $totalarray['nbfield']++;
    	}

    	// Debit
    	if (! empty($arrayfields['b.debit']['checked']))
    	{
    	    print '<td align="right">';
    	    if ($objp->amount < 0)
    	    {
    	    	print price($objp->amount * -1);
    	        $totalarray['totaldeb'] += $objp->amount;
    	    }
    	    print "</td>\n";
    	    if (! $i) $totalarray['nbfield']++;
    	    if (! $i) $totalarray['totaldebfield']=$totalarray['nbfield'];
    	}

    	// Credit
    	if (! empty($arrayfields['b.credit']['checked']))
    	{
    	    print '<td align="right">';
    	    if ($objp->amount > 0)
    	    {
				print price($objp->amount);
    	        $totalarray['totalcred'] += $objp->amount;
    	    }
    	    print "</td>\n";
    	    if (! $i) $totalarray['nbfield']++;
    	    if (! $i) $totalarray['totalcredfield']=$totalarray['nbfield'];
    	}

    	// Balance before
    	if (! empty($arrayfields['balancebefore']['checked']))
    	{
    		if ($mode_balance_ok)
    		{
    			if ($balancebefore >= 0)
    			{
    				print '<td align="right" class="nowrap">&nbsp;'.price($balancebefore).'</td>';
    			}
    			else
    			{
    				print '<td align="right" class="error nowrap">&nbsp;'.price($balancebefore).'</td>';
    			}
    		}
    		else
    		{
    			print '<td align="right">-</td>';
    		}
    		if (! $i) $totalarray['nbfield']++;
    	}
    	// Balance
    	if (! empty($arrayfields['balance']['checked']))
    	{
    		if ($mode_balance_ok)
    		{
    			if ($balance >= 0)
    			{
    				print '<td align="right" class="nowrap">&nbsp;'.price($balance).'</td>';
    			}
    			else
    			{
    				print '<td align="right" class="error nowrap">&nbsp;'.price($balance).'</td>';
    			}
    		}
    		else
    		{
    			print '<td align="right">-</td>';
    		}
    		if (! $i) $totalarray['nbfield']++;
    	}

    	if (! empty($arrayfields['b.num_releve']['checked']))
    	{
            print '<td class="nowrap" align="center">';
        	// Transaction reconciliated or edit link
        	if ($bankaccount->canBeConciliated() > 0)
        	{
            	if ($objp->conciliated)  // If line not conciliated and account can be conciliated
            	{
            	    print '<a href="releve.php?num='.$objp->num_releve.'&amp;account='.$objp->bankid.'">'.$objp->num_releve.'</a>';
            	}
            	else if ($action == 'reconcile')
            	{
            	    print '<input class="flat" name="rowid['.$objp->rowid.']" type="checkbox" value="'.$objp->rowid.'" size="1"'.(! empty($_POST['rowid'][$objp->rowid])?' checked':'').'>';
            	}
        	}
        	print '</td>';
            if (! $i)
            {
            	$totalarray['nbfield']++;
            	$posconciliatecol = $totalarray['nbfield'];
            }
    	}

        if (! empty($arrayfields['b.conciliated']['checked']))
    	{
            print '<td class="nowraponall" align="center">';
            print $objp->conciliated?$langs->trans("Yes"):$langs->trans("No");
        	print '</td>';
            if (! $i) $totalarray['nbfield']++;
    	}

    	// Action edit/delete
    	print '<td class="nowraponall" align="center">';
    	// Transaction reconciliated or edit link
    	if ($objp->conciliated && $bankaccount->canBeConciliated() > 0)  // If line not conciliated and account can be conciliated
    	{
    	    print '<a href="'.DOL_URL_ROOT.'/compta/bank/ligne.php?rowid='.$objp->rowid.'&amp;account='.$objp->bankid.'&amp;page='.$page.'">';
    	    print img_edit();
    	    print '</a>';
    	}
    	else
    	{
    	    if ($user->rights->banque->modifier || $user->rights->banque->consolidate)
    	    {
    	        print '<a href="'.DOL_URL_ROOT.'/compta/bank/ligne.php?rowid='.$objp->rowid.'&amp;account='.$objp->bankid.'&amp;page='.$page.'">';
    	        print img_edit();
    	        print '</a>';
    	    }
    	    else
    	    {
    	        print '<a href="'.DOL_URL_ROOT.'/compta/bank/ligne.php?rowid='.$objp->rowid.'&amp;account='.$objp->bankid.'&amp;page='.$page.'">';
    	        print img_view();
    	        print '</a>';
    	    }
    	    if ($bankaccount->canBeConciliated() > 0 && empty($objp->conciliated))
    	    {
    	        if ($db->jdate($objp->dv) < ($now - $conf->bank->rappro->warning_delay))
    	        {
    	            print ' '.img_warning($langs->trans("ReconciliationLate"));
    	        }
    	    }
    	    print '&nbsp;';
    	    if ($user->rights->banque->modifier)
    	    {
    	        print '<a href="'.$_SERVER["PHP_SELF"].'?action=delete&amp;rowid='.$objp->rowid.'&amp;id='.$objp->bankid.'&amp;page='.$page.'">';
    	        print img_delete();
    	        print '</a>';
    	    }
    	}
    	print '</td>';
    	if (! $i) $totalarray['nbfield']++;

    	// Action column
    	print '<td class="nowrap" align="center">';
    	if ($massactionbutton || $massaction)   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
    	{
    	    $selected=0;
    	    if (in_array($obj->rowid, $arrayofselected)) $selected=1;
    	    print '<input id="cb'.$obj->rowid.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$obj->rowid.'"'.($selected?' checked="checked"':'').'>';
    	}
    	print '</td>';
    	if (! $i) $totalarray['nbfield']++;

		print "</tr>";

		$i++;
	}

	// Show total line
	if (isset($totalarray['totaldebfield']) || isset($totalarray['totalcredfield']))
	{
	    print '<tr class="liste_total">';
	    $i=0;
	    while ($i < $totalarray['nbfield'])
	    {
	        $i++;
	        if ($i == 1)
	        {
	            if ($num < $limit && empty($offset)) print '<td align="left">'.$langs->trans("Total").'</td>';
	            else print '<td align="left">'.$langs->trans("Totalforthispage").'</td>';
	        }
	        elseif ($totalarray['totaldebfield'] == $i) print '<td align="right">'.price(-1 * $totalarray['totaldeb']).'</td>';
	        elseif ($totalarray['totalcredfield'] == $i) print '<td align="right">'.price($totalarray['totalcred']).'</td>';
	        elseif ($i == $posconciliatecol)
	        {
	        	print '<td class="center">';
	        	if ($user->rights->banque->consolidate && $action == 'reconcile') print '<input class="button" name="confirm_reconcile" type="submit" value="' . $langs->trans("Conciliate") . '">';
	        	print '</td>';
	        }
	        else print '<td></td>';
	    }
	    print '</tr>';
	}

	print "</table>";
	print "</div>";

    print '</form>';
	$db->free($resql);
}
else
{
	dol_print_error($db);
}

// If no data to display after a search
if ($_POST["action"] == "search" && ! $num)
{
	print '<div class="opacitymedium">'.$langs->trans("NoRecordFound").'</div>';
}

llxFooter();

$db->close();
