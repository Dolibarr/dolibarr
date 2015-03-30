<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Christophe Combelles <ccomb@free.fr>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2010-2011 Juanjo Menent        <jmenent@@2byte.es>
 * Copyright (C) 2012-2014 Marcos García        <marcosgdf@gmail.com>
 * Copyright (C) 2011-2015 Alexandre Spangaro   <alexandre.spangaro@gmail.com>
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
 *	    \file       htdocs/compta/bank/account.php
 *		\ingroup    banque
 *		\brief      List of details of bank transactions for an account
 */

require('../../main.inc.php');
require_once DOL_DOCUMENT_ROOT.'/core/lib/bank.lib.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/sociales/class/chargesociales.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/tva/class/tva.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/salaries/class/paymentsalary.class.php';
require_once DOL_DOCUMENT_ROOT.'/don/class/don.class.php';
require_once DOL_DOCUMENT_ROOT.'/loan/class/loan.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/paiementfourn.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

$langs->load("banks");
$langs->load("categories");
$langs->load("bills");
$langs->load("companies");
$langs->load("loan");
$langs->load("donations");

$id = (GETPOST('id','int') ? GETPOST('id','int') : GETPOST('account','int'));
$ref = GETPOST('ref','alpha');
$action=GETPOST('action','alpha');
$confirm=GETPOST('confirm','alpha');

// Security check
$fieldvalue = (! empty($id) ? $id : (! empty($ref) ? $ref :''));
$fieldtype = (! empty($ref) ? 'ref' :'rowid');
if ($user->societe_id) $socid=$user->societe_id;
$result=restrictedArea($user,'banque',$fieldvalue,'bank_account&bank_account','','',$fieldtype);

$paiementtype=GETPOST('paiementtype','alpha',3);
$req_nb=GETPOST("req_nb",'',3);
$thirdparty=GETPOST("thirdparty",'',3);
$req_desc=GETPOST("req_desc",'',3);
$req_debit=GETPOST("req_debit",'',3);
$req_credit=GETPOST("req_credit",'',3);

$vline=GETPOST("vline");
$page=GETPOST('page','int');
$negpage=GETPOST('negpage','int');
if ($negpage)
{
    $page=GETPOST("nbpage") - $negpage;
    if ($page > GETPOST("nbpage")) $page = GETPOST("nbpage");
}

$object = new Account($db);

/*
 * Action
 */
$dateop=-1;

if ($action == 'add' && $id && ! isset($_POST["cancel"]) && $user->rights->banque->modifier)
{
	$error = 0;

	if (price2num($_POST["credit"]) > 0)
	{
		$amount = price2num($_POST["credit"]);
	}
	else
	{
		$amount = - price2num($_POST["debit"]);
	}

	$dateop = dol_mktime(12,0,0,$_POST["opmonth"],$_POST["opday"],$_POST["opyear"]);
	$operation=$_POST["operation"];
	$num_chq=$_POST["num_chq"];
	$label=$_POST["label"];
	$cat1=$_POST["cat1"];

	if (! $dateop) {
		$error++;
		setEventMessage($langs->trans("ErrorFieldRequired",$langs->trans("Date")), 'errors');
	}
	if (! $operation) {
		$error++;
		setEventMessage($langs->trans("ErrorFieldRequired",$langs->trans("Type")), 'errors');
	}
	if (! $amount) {
		$error++;
		setEventMessage($langs->trans("ErrorFieldRequired",$langs->trans("Amount")), 'errors');
	}

	if (! $error)
	{
		$object->fetch($id);
		$insertid = $object->addline($dateop, $operation, $label, $amount, $num_chq, $cat1, $user);
		if ($insertid > 0)
		{
			setEventMessage($langs->trans("RecordSaved"));
			header("Location: ".$_SERVER['PHP_SELF']."?id=".$id."&action=addline");
			exit;
		}
		else
		{
			setEventMessage($object->error, 'errors');
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
	$result=$accline->delete();
}


/*
 * View
 */

llxHeader();

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
$bankstatic=new Account($db);
$banklinestatic=new AccountLine($db);

$form = new Form($db);

if ($id > 0 || ! empty($ref))
{
	if ($vline)
	{
		$viewline = $vline;
	}
	else
	{
		$viewline = empty($conf->global->MAIN_SIZE_LISTE_LIMIT)?20:$conf->global->MAIN_SIZE_LISTE_LIMIT;
	}

	$result=$object->fetch($id, $ref);

	// Chargement des categories bancaires dans $options
	$nbcategories=0;

	$sql = "SELECT rowid, label";
	$sql.= " FROM ".MAIN_DB_PREFIX."bank_categ";
	$sql.= " WHERE entity = ".$conf->entity;
	$sql.= " ORDER BY label";

	$result = $db->query($sql);
	if ($result)
	{
		$var=True;
		$num = $db->num_rows($result);
		$i = 0;
		$options = '<option value="0" selected="true">&nbsp;</option>';
		while ($i < $num)
		{
			$obj = $db->fetch_object($result);
			$options.= '<option value="'.$obj->rowid.'">'.$obj->label.'</option>'."\n";
			$nbcategories++;
			$i++;
		}
		$db->free($result);
	}

	// Definition de sql_rech et param
	$param='';
	$sql_rech='';
	$mode_search = 0;
	if ($req_nb)
	{
		$sql_rech.= " AND b.num_chq LIKE '%".$db->escape($req_nb)."%'";
		$param.='&amp;req_nb='.urlencode($req_nb);
		$mode_search = 1;
	}
	if ($req_desc)
	{
		$sql_rech.= " AND b.label LIKE '%".$db->escape($req_desc)."%'";
		$param.='&amp;req_desc='.urlencode($req_desc);
		$mode_search = 1;
	}
	if ($req_debit != '')
	{
		$sql_rech.=" AND b.amount = -".price2num($req_debit);
		$param.='&amp;req_debit='.urlencode($req_debit);
		$mode_search = 1;
	}
	if ($req_credit != '')
	{
		$sql_rech.=" AND b.amount = ".price2num($req_credit);
		$param.='&amp;req_credit='.urlencode($req_credit);
		$mode_search = 1;
	}
	if ($thirdparty)
	{
		$sql_rech.=" AND s.nom LIKE '%".$db->escape($thirdparty)."%'";
		$param.='&amp;thirdparty='.urlencode($thirdparty);
		$mode_search = 1;
	}
	if ($paiementtype)
	{
		$sql_rech.=" AND b.fk_type = '".$db->escape($paiementtype)."'";
		$param.='&amp;paiementtype='.urlencode($paiementtype);
		$mode_search = 1;
	}

	$sql = "SELECT count(*) as total";
	$sql.= " FROM ".MAIN_DB_PREFIX."bank_account as ba";
	$sql.= ", ".MAIN_DB_PREFIX."bank as b";
	if ($mode_search)
	{
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."bank_url as bu ON bu.fk_bank = b.rowid AND bu.type='company'";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON bu.url_id = s.rowid";
	}
	$sql.= " WHERE b.fk_account = ".$object->id;
	$sql.= " AND b.fk_account = ba.rowid";
	$sql.= " AND ba.entity = ".$conf->entity;
	$sql.= $sql_rech;

	dol_syslog("account.php count transactions -", LOG_DEBUG);
	$result=$db->query($sql);
	if ($result)
	{
		$obj = $db->fetch_object($result);
		$nbline = $obj->total;
		$total_lines = $nbline;

		$db->free($result);
	}
	else
	{
		dol_print_error($db);
	}

	//Total pages
	$totalPages = ceil($total_lines/$viewline);

	if ($totalPages == 0) {
		$page = 0;
	} else {

		if ($page > 0) {
			$limitsql = ($totalPages - $page) * $viewline;
			if ($limitsql < $viewline) {
				$limitsql = $viewline;
			}
			$nbline = $limitsql;
		} else {
			$page = 0;
			$limitsql = $nbline;
		}
	}

	//print $limitsql.'-'.$page.'-'.$viewline;

	// Onglets
	$head=bank_prepare_head($object);
	dol_fiche_head($head,'journal',$langs->trans("FinancialAccount"),0,'account');


	print '<table class="border" width="100%">';

	$linkback = '<a href="'.DOL_URL_ROOT.'/compta/bank/index.php">'.$langs->trans("BackToList").'</a>';

	// Ref
	print '<tr><td valign="top" width="25%">'.$langs->trans("Ref").'</td>';
	print '<td colspan="3">';
	print $form->showrefnav($object, 'ref', $linkback, 1, 'ref');
	print '</td></tr>';

	// Label
	print '<tr><td valign="top">'.$langs->trans("Label").'</td>';
	print '<td colspan="3">'.$object->label.'</td></tr>';

	print '</table>';

	print '<br>';

	/**
	 * Search form
	 */
	$param.='&amp;account='.$object->id.'&amp;vline='.$vline;

	// Confirmation delete
	if ($action == 'delete')
	{
		$text=$langs->trans('ConfirmDeleteTransaction');
		print $form->formconfirm($_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;rowid='.GETPOST("rowid"),$langs->trans('DeleteTransaction'),$text,'confirm_delete');

	}

	// Define transaction list navigation string
	print '<form action="'.$_SERVER["PHP_SELF"].'" name="newpage" method="POST">';
	print '<input type="hidden" name="token"        value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action"       value="add">';
	print '<input type="hidden" name="vline"        value="'.$vline.'">';
	print '<input type="hidden" name="paiementtype" value="'.$paiementtype.'">';
	print '<input type="hidden" name="req_nb"       value="'.$req_nb.'">';
	print '<input type="hidden" name="req_desc"     value="'.$req_desc.'">';
	print '<input type="hidden" name="req_debit"    value="'.$req_debit.'">';
	print '<input type="hidden" name="req_credit"   value="'.$req_credit.'">';
	print '<input type="hidden" name="thirdparty"   value="'.$thirdparty.'">';
	print '<input type="hidden" name="nbpage"       value="'.$totalPages.'">';
	print '<input type="hidden" name="id"           value="'.$object->id.'">';

	$navig ='<div data-role="fieldcontain">';
	if ($limitsql > $viewline) $navig.='<a href="account.php?'.$param.'&amp;page='.($page+1).'">'.img_previous().'</a>';
	$navig.= '<label for="negpage">'.$langs->trans("Page")."</label> "; // ' Page ';
	$navig.='<input type="text" name="negpage" id="negpage" size="1" class="flat" value="'.($totalPages-$page).'">';
	$navig.='/'.$totalPages.' ';
	if ($total_lines > $limitsql )
	{
		$navig.= '<a href="'.$_SERVER["PHP_SELF"].'?'.$param.'&amp;page='.($page-1).'">'.img_next().'</a>';
	}
	$navig.='</div>';

	//var_dump($navig);

	print '<table class="notopnoleftnoright" width="100%">';

	// Show title
	if ($action != 'addline' && $action != 'delete')
	{
		print '<tr><td colspan="10" align="right">'.$navig.'</td></tr>';
	}

	// Form to add a transaction with no invoice
	if ($user->rights->banque->modifier && $action == 'addline')
	{
		print '<tr>';
		print '<td align="left" colspan="10"><b>'.$langs->trans("AddBankRecordLong").'</b></td>';
		print '</tr>';

		print '<tr class="liste_titre">';
		print '<td>'.$langs->trans("Date").'</td>';
		print '<td>&nbsp;</td>';
		print '<td>'.$langs->trans("Type").'</td>';
		print '<td>'.$langs->trans("Numero").'</td>';
		print '<td colspan="2">'.$langs->trans("Description").'</td>';
		print '<td align=right>'.$langs->trans("Debit").'</td>';
		print '<td align=right>'.$langs->trans("Credit").'</td>';
		print '<td colspan="2" align="center">&nbsp;</td>';
		print '</tr>';

		print '<tr '.$bc[false].'>';
		print '<td class="nowrap" colspan="2">';
		$form->select_date($dateop,'op',0,0,0,'transaction');
		print '</td>';
		print '<td class="nowrap">';
		$form->select_types_paiements((GETPOST('operation')?GETPOST('operation'):($object->courant == 2 ? 'LIQ' : '')),'operation','1,2',2,1);
		print '</td><td>';
		print '<input name="num_chq" class="flat" type="text" size="4" value="'.GETPOST("num_chq").'"></td>';
		print '<td colspan="2">';
		print '<input name="label" class="flat" type="text" size="24"  value="'.GETPOST("label").'">';
		if ($nbcategories)
		{
			print '<br>'.$langs->trans("Category").': <select class="flat" name="cat1">'.$options.'</select>';
		}
		print '</td>';
		print '<td align=right><input name="debit" class="flat" type="text" size="4" value="'.GETPOST("debit").'"></td>';
		print '<td align=right><input name="credit" class="flat" type="text" size="4" value="'.GETPOST("credit").'"></td>';
		print '<td colspan="2" align="center">';
		print '<input type="submit" name="save" class="button" value="'.$langs->trans("Add").'"><br>';
		print '<input type="submit" name="cancel" class="button" value="'.$langs->trans("Cancel").'">';
		print '</td></tr>';
		print "</form>";

		print '<tr class="noborder"><td colspan="10">&nbsp;</td></tr>'."\n";
	}

	/*
	 * Affiche tableau des transactions bancaires
	 */

	// Ligne de titre tableau des ecritures
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("Date").'</td>';
	print '<td>'.$langs->trans("Value").'</td>';
	print '<td>'.$langs->trans("Type").'</td>';
	print '<td>'.$langs->trans("Numero").'</td>';
	print '<td>'.$langs->trans("Description").'</td>';
	print '<td>'.$langs->trans("ThirdParty").'</td>';
	print '<td align="right">'.$langs->trans("Debit").'</td>';
	print '<td align="right">'.$langs->trans("Credit").'</td>';
	print '<td align="right" width="80">'.$langs->trans("BankBalance").'</td>';
	print '<td align="center" width="60">';
	if ($object->type != 2 && $object->rappro) print $langs->trans("AccountStatementShort");
	else print '&nbsp;';
	print '</td></tr>';

	print '<form action="'.$_SERVER["PHP_SELF"].'?'.$param.'" name="search" method="POST">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="search">';
	print '<input type="hidden" name="id" value="'.$object->id.'">';

	print '<tr class="liste_titre">';
	print '<td>&nbsp;</td>';
	print '<td>&nbsp;</td>';
	print '<td>';
	//$filtertype=array('TIP'=>'TIP','PRE'=>'PRE',...)
	$filtertype='';
	$form->select_types_paiements($paiementtype,'paiementtype',$filtertype,2,1,1,8);
	print '</td>';
	print '<td><input type="text" class="flat" name="req_nb" value="'.$req_nb.'" size="2"></td>';
	print '<td><input type="text" class="flat" name="req_desc" value="'.$req_desc.'" size="24"></td>';
	print '<td><input type="text" class="flat" name="thirdparty" value="'.$thirdparty.'" size="14"></td>';
	print '<td align="right"><input type="text" class="flat" name="req_debit" value="'.$req_debit.'" size="4"></td>';
	print '<td align="right"><input type="text" class="flat" name="req_credit" value="'.$req_credit.'" size="4"></td>';
	print '<td align="center">&nbsp;</td>';
	print '<td align="center" width="40"><input type="image" class="liste_titre" src="'.img_picto($langs->trans("Search"),'search.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'"></td>';
	print "</tr>\n";


	/*
	 * Another solution
	 * create temporary table solde type=heap select amount from llx_bank limit 100 ;
	 * select sum(amount) from solde ;
     */

	$sql = "SELECT b.rowid, b.dateo as do, b.datev as dv,";
	$sql.= " b.amount, b.label, b.rappro, b.num_releve, b.num_chq, b.fk_type, b.fk_bordereau,";
	$sql.= " ba.rowid as bankid, ba.ref as bankref, ba.label as banklabel";
	if ($mode_search)
	{
		$sql.= ", s.rowid as socid, s.nom as thirdparty";
	}
	/*
	if ($mode_search && ! empty($conf->adherent->enabled))
	{

	}
	if ($mode_search && ! empty($conf->tax->enabled))
	{

	}
	*/
	$sql.= " FROM ".MAIN_DB_PREFIX."bank_account as ba";
	$sql.= ", ".MAIN_DB_PREFIX."bank as b";
	if ($mode_search)
	{
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."bank_url as bu1 ON bu1.fk_bank = b.rowid AND bu1.type='company'";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON bu1.url_id = s.rowid";
	}
	if ($mode_search && ! empty($conf->tax->enabled))
	{
		// VAT
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."bank_url as bu2 ON bu2.fk_bank = b.rowid AND bu2.type='payment_vat'";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."tva as t ON bu2.url_id = t.rowid";

		// Salary payment
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."bank_url as bu3 ON bu3.fk_bank = b.rowid AND bu3.type='payment_salary'";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."payment_salary as sal ON bu3.url_id = sal.rowid";
	}
	if ($mode_search && ! empty($conf->adherent->enabled))
	{
		// TODO Mettre jointure sur adherent pour recherche sur un adherent
		//$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."bank_url as bu3 ON bu3.fk_bank = b.rowid AND bu3.type='company'";
		//$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON bu3.url_id = s.rowid";
	}
	$sql.= " WHERE b.fk_account=".$object->id;
	$sql.= " AND b.fk_account = ba.rowid";
	$sql.= " AND ba.entity = ".$conf->entity;
	$sql.= $sql_rech;
	$sql.= $db->order("b.datev, b.datec", "ASC");  // We add date of creation to have correct order when everything is done the same day
	$sql.= $db->plimit($limitsql, 0);

	dol_syslog("account.php get transactions -", LOG_DEBUG);
	$result = $db->query($sql);
	if ($result)
	{
		$now=dol_now();
		$nows=dol_print_date($now,'%Y%m%d');

		//$form->load_cache_types_paiements();
		//$form->cache_types_paiements

		$var=true;

		$num = $db->num_rows($result);
		$i = 0; $total = 0; $sep = -1;

		while ($i < $num)
		{
			$objp = $db->fetch_object($result);
			$total = price2num($total + $objp->amount,'MT');
			if ($i >= ($viewline * (($totalPages-$page)-1)))
			{
				$var=!$var;

				// Is it a transaction in future ?
				$dos=dol_print_date($db->jdate($objp->do),'%Y%m%d');
				//print "dos=".$dos." nows=".$nows;
				if ($dos < $nows) $sep=0;		// 0 means there was at least one line before current date
				if ($dos > $nows && ! $sep)		// We have found a line in future and we already found on line before current date
				{
					$sep = 1 ;
					print '<tr class="liste_total"><td colspan="8">';
					print $langs->trans("CurrentBalance");
					print '</td>';
					print '<td align="right" class="nowrap"><b>'.price($total - $objp->amount).'</b></td>';
					print "<td>&nbsp;</td>";
					print '</tr>';
				}

				print '<tr '.$bc[$var].'>';

				print '<td class="nowrap">'.dol_print_date($db->jdate($objp->do),"day")."</td>\n";

				print '<td class="nowrap">'.dol_print_date($db->jdate($objp->dv),"day");
				print "</td>\n";

				// Payment type
				print '<td class="nowrap">';
				$label=($langs->trans("PaymentTypeShort".$objp->fk_type)!="PaymentTypeShort".$objp->fk_type)?$langs->trans("PaymentTypeShort".$objp->fk_type):$objp->fk_type;

				if ($objp->fk_type == 'SOLD') $label='&nbsp;';
				if ($objp->fk_type == 'CHQ' && $objp->fk_bordereau > 0) {
					dol_include_once('/compta/paiement/cheque/class/remisecheque.class.php');
					$bordereaustatic = new RemiseCheque($db);
					$bordereaustatic->id = $objp->fk_bordereau;
					$label .= ' '.$bordereaustatic->getNomUrl(2);
				}
				print $label;
				print "</td>\n";

				// Num
				print '<td class="nowrap">'.($objp->num_chq?$objp->num_chq:"")."</td>\n";

				// Description
				print '<td>';
				// Show generic description
				if (preg_match('/^\((.*)\)$/i',$objp->label,$reg))
				{
					// Generic description because between (). We show it after translating.
					print $langs->trans($reg[1]);
				}
				else
				{
					print dol_trunc($objp->label,60);
				}
				// Add links after description
				$links = $object->get_url($objp->rowid);
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
					elseif ($links[$key]['type']=='banktransfert')
					{
						// Do not show link to transfer since there is no transfer card (avoid confusion). Can already be accessed from transaction detail.
						if ($objp->amount > 0)
						{
							$banklinestatic->fetch($links[$key]['url_id']);
							$bankstatic->id=$banklinestatic->fk_account;
							$bankstatic->label=$banklinestatic->bank_account_label;
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
							$bankstatic->label=$banklinestatic->bank_account_label;
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

				// Add third party column
				print '<td>';
				foreach($links as $key=>$val)
				{
					if ($links[$key]['type']=='company')
					{
						$societestatic->id=$links[$key]['url_id'];
						$societestatic->name=$links[$key]['label'];
						print $societestatic->getNomUrl(1,'',16);
					}
					else if ($links[$key]['type']=='user')
					{
						$userstatic->id=$links[$key]['url_id'];
						$userstatic->lastname=$links[$key]['label'];
						print $userstatic->getNomUrl(1,'');
					}
					else if ($links[$key]['type']=='sc')
					{
						// sc=old value
						$chargestatic->id=$links[$key]['url_id'];
						if (preg_match('/^\((.*)\)$/i',$links[$key]['label'],$reg))
						{
							if ($reg[1]=='socialcontribution') $reg[1]='SocialContribution';
							$chargestatic->lib=$langs->trans($reg[1]);
						}
						else
						{
							$chargestatic->lib=$links[$key]['label'];
						}
						$chargestatic->ref=$chargestatic->lib;
						print $chargestatic->getNomUrl(1,16);
					}
					else if ($links[$key]['type']=='loan')
					{
						$loanstatic->id=$links[$key]['url_id'];
						if (preg_match('/^\((.*)\)$/i',$links[$key]['label'],$reg))
						{
							if ($reg[1]=='loan') $reg[1]='Loan';
							$loanstatic->label=$langs->trans($reg[1]);
						}
						else
						{
							$loanstatic->label=$links[$key]['label'];
						}
						$loanstatic->ref=$loanstatic->label;
						print $loanstatic->getLinkUrl(1,16);
					}
					else if ($links[$key]['type']=='member')
					{
						$memberstatic->id=$links[$key]['url_id'];
						$memberstatic->ref=$links[$key]['label'];
						print $memberstatic->getNomUrl(1,16,'card');
					}
				}
				print '</td>';

				// Amount
				if ($objp->amount < 0)
				{
					print '<td align="right" class="nowrap">'.price($objp->amount * -1).'</td><td>&nbsp;</td>'."\n";
				}
				else
				{
					print '<td>&nbsp;</td><td align="right" class="nowrap">&nbsp;'.price($objp->amount).'</td>'."\n";
				}

				// Balance
				if (! $mode_search)
				{
					if ($total >= 0)
					{
						print '<td align="right" nowrap>&nbsp;'.price($total).'</td>';
					}
					else
					{
						print '<td align="right" class="error" nowrap>&nbsp;'.price($total).'</td>';
					}
				}
				else
				{
					print '<td align="right">-</td>';
				}

				// Transaction reconciliated or edit link
				if ($objp->rappro && $object->canBeConciliated() > 0)  // If line not conciliated and account can be conciliated
				{
					print '<td align="center" nowrap>';
					print '<a href="'.DOL_URL_ROOT.'/compta/bank/ligne.php?rowid='.$objp->rowid.'&amp;account='.$object->id.'&amp;page='.$page.'">';
					print img_edit();
					print '</a>';
					print "&nbsp; ";
					print '<a href="releve.php?num='.$objp->num_releve.'&amp;account='.$object->id.'">'.$objp->num_releve.'</a>';
					print "</td>";
				}
				else
				{
					print '<td align="center">';
					if ($user->rights->banque->modifier || $user->rights->banque->consolidate)
					{
						print '<a href="'.DOL_URL_ROOT.'/compta/bank/ligne.php?rowid='.$objp->rowid.'&amp;account='.$object->id.'&amp;page='.$page.'">';
						print img_edit();
						print '</a>';
					}
					else
					{
						print '<a href="'.DOL_URL_ROOT.'/compta/bank/ligne.php?rowid='.$objp->rowid.'&amp;account='.$object->id.'&amp;page='.$page.'">';
						print img_view();
						print '</a>';
					}
					if ($object->canBeConciliated() > 0 && empty($objp->rappro))
					{
						if ($db->jdate($objp->dv) < ($now - $conf->bank->rappro->warning_delay))
						{
							print ' '.img_warning($langs->trans("Late"));
						}
					}
					print '&nbsp;';
					if ($user->rights->banque->modifier)
					{
						print '<a href="'.$_SERVER["PHP_SELF"].'?action=delete&amp;rowid='.$objp->rowid.'&amp;id='.$object->id.'&amp;page='.$page.'">';
						print img_delete();
						print '</a>';
					}
					print '</td>';
				}

				print "</tr>";
			}

			$i++;
		}

		// Show total
		if ($page == 0 && ! $mode_search)
		{
			print '<tr class="liste_total"><td align="left" colspan="8">';
			if ($sep > 0) print '&nbsp;';	// If we had at least one line in future
			else print $langs->trans("CurrentBalance");
			print ' '.$object->currency_code.'</td>';
			print '<td align="right" nowrap><b>'.price($total, 0, $langs, 0, 0, -1, $object->currency_code).'</b></td>';
			print '<td>&nbsp;</td>';
			print '</tr>';
		}
		$db->free($result);
	}
	else
	{
		dol_print_error($db);
	}

	print "</table>";

	print "</form>\n";

	dol_fiche_end();


	/*
	 * Boutons actions
	 */

	if ($action != 'delete')
	{
		print '<div class="tabsAction">';

		if ($object->type != 2 && $object->rappro)  // If not cash account and can be reconciliate
		{
			if ($user->rights->banque->consolidate)
			{
				print '<a class="butAction" href="'.DOL_URL_ROOT.'/compta/bank/rappro.php?account='.$object->id.($vline?'&amp;vline='.$vline:'').'">'.$langs->trans("Conciliate").'</a>';
			}
			else
			{
				print '<a class="butActionRefused" title="'.$langs->trans("NotEnoughPermissions").'" href="#">'.$langs->trans("Conciliate").'</a>';
			}
		}

		if ($action != 'addline')
		{
			if (empty($conf->global->BANK_DISABLE_DIRECT_INPUT))
			{
				if ($user->rights->banque->modifier)
				{
					print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=addline&amp;id='.$object->id.'&amp;page='.$page.($vline?'&amp;vline='.$vline:'').'">'.$langs->trans("AddBankRecord").'</a>';
				}
				else
				{
					print '<a class="butActionRefused" title="'.$langs->trans("NotEnoughPermissions").'" href="#">'.$langs->trans("AddBankRecord").'</a>';
				}
			}
			else
			{
				print '<a class="butActionRefused" title="'.$langs->trans("FeatureDisabled").'" href="#">'.$langs->trans("AddBankRecord").'</a>';
			}
		}

		print '</div>';
	}

	print '<br>';
}
else
{
	print $langs->trans("ErrorBankAccountNotFound");
}

llxFooter();

$db->close();
