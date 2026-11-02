<?php
/* Copyright (C) 2026 Pierre Ardoin <developpeur@lesmetiersdubatiment.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file    htdocs/accountancy/customerdeposits.php
 * \ingroup Accountancy
 * \brief   Read-only customer deposit control at a cutoff date
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/customerdepositreport.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var Translate $langs
 * @var User $user
 */

$langs->loadLangs(array('accountancy', 'bills', 'companies', 'orders', 'sendings'));

if (!isModEnabled('accounting') || $user->socid > 0 || !$user->hasRight('accounting', 'comptarapport', 'lire')) {
	accessforbidden();
}

$date_cutoff = GETPOSTDATE('date_cutoff', 'getpost', 'tzserver');
$search_ref = GETPOST('search_ref', 'alphanohtml');
$search_thirdparty = GETPOST('search_thirdparty', 'alphanohtml');
$search_status = GETPOST('search_status', 'aZ');
if (!in_array($search_status, array('outstanding', 'cleared', 'all'))) {
	$search_status = 'all';
}
$limit = GETPOSTINT('limit') > 0 ? GETPOSTINT('limit') : $conf->liste_limit;
$page = max(0, GETPOSTINT('page'));
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = dol_strtoupper(GETPOST('sortorder', 'aZ09comma')) === 'DESC' ? 'DESC' : 'ASC';
$contextpage = 'customerdepositcontrollist';
$action = GETPOST('action', 'aZ09');
$arrayfields = array(
	'f.ref' => array('label' => 'Invoice', 'checked' => '1', 'position' => 10),
	'f.datef' => array('label' => 'DateInvoice', 'checked' => '1', 'position' => 20),
	's.nom' => array('label' => 'ThirdParty', 'checked' => '1', 'position' => 30),
	'f.total_ttc' => array('label' => 'DepositControlInvoiced', 'checked' => '1', 'position' => 40),
	'received' => array('label' => 'DepositControlReceived', 'checked' => '1', 'position' => 50),
	'refunds' => array('label' => 'DepositControlRefunded', 'checked' => '0', 'position' => 60),
	'credited' => array('label' => 'DepositControlCredited', 'checked' => '0', 'position' => 70),
	'pay.last_payment' => array('label' => 'DepositControlLastPayment', 'checked' => '0', 'position' => 80),
	'applied' => array('label' => 'DepositControlApplied', 'checked' => '1', 'position' => 90),
	'remaining' => array('label' => 'DepositControlRemaining', 'checked' => '1', 'position' => 100),
	'applications' => array('label' => 'DepositControlApplications', 'checked' => '1', 'position' => 110),
	'orders' => array('label' => 'DepositControlOrders', 'checked' => '1', 'position' => 120, 'enabled' => $user->hasRight('commande', 'lire')),
	'status' => array('label' => 'Status', 'checked' => '1', 'position' => 130),
);
if (!$user->hasRight('commande', 'lire')) {
	unset($arrayfields['orders']);
}
if (!isset($arrayfields[$sortfield])) {
	$sortfield = 'f.datef';
}

/*
 * Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

$clearfilters = GETPOSTISSET('button_removefilter_x') || GETPOSTISSET('button_removefilter.x') || GETPOSTISSET('button_removefilter');
if (GETPOSTISSET('button_search_x') || GETPOSTISSET('button_search.x') || GETPOSTISSET('button_search') || $clearfilters) {
	$page = 0;
}
if ($clearfilters) {
	$search_ref = '';
	$search_thirdparty = '';
	$search_status = 'all';
}

if (empty($date_cutoff)) {
	$now = dol_now();
	$sql = 'SELECT date_end FROM '.$db->prefix().'accounting_fiscalyear';
	$sql .= ' WHERE entity IN ('.getEntity('accounting_fiscalyear').')';
	$sql .= " AND date_start <= '".$db->idate($now)."' AND date_end >= '".$db->idate($now)."'";
	$sql .= $db->order('date_end', 'ASC').$db->plimit(1);
	$resql = $db->query($sql);
	if ($resql) {
		$fiscalyear = $db->fetch_object($resql);
		if ($fiscalyear) {
			$date_cutoff = $db->jdate($fiscalyear->date_end);
		}
		$db->free($resql);
	}
	if (empty($date_cutoff)) {
		$date_cutoff = $now;
	}
}

$report = new CustomerDepositReport($db);
$filter = array('ref' => $search_ref, 'thirdparty' => $search_thirdparty, 'status' => $search_status, 'orders' => $user->hasRight('commande', 'lire'));
if (!$user->hasRight('societe', 'client', 'voir')) {
	$filter['sale'] = (int) $user->id;
}
$result = $report->fetchAll($date_cutoff, $filter, $limit, $page * $limit, $sortfield, $sortorder);
if ($result < 0) {
	setEventMessages($report->error, null, 'errors');
}

/*
 * Views
 */

$form = new Form($db);
$invoice = new Facture($db);
$thirdparty = new Societe($db);
$orderobject = new Commande($db);
$title = $langs->trans('CustomerDepositControl');
$dateparts = dol_getdate($date_cutoff, false, 'tzserver');
$param = '&mainmenu=accountancy&leftmenu=accountancy_accountancy_customerdeposits';
$param .= '&date_cutoffday='.$dateparts['mday'].'&date_cutoffmonth='.$dateparts['mon'].'&date_cutoffyear='.$dateparts['year'];
$param .= '&search_ref='.urlencode($search_ref).'&search_thirdparty='.urlencode($search_thirdparty).'&search_status='.urlencode($search_status);
if ($limit != $conf->liste_limit) {
	$param .= '&limit='.$limit;
}

llxHeader('', $title, '', '', 0, 0, '', '', '', 'mod-accountancy page-customerdeposits');

print '<form method="GET" action="'.dolPrintHTMLForAttributeUrl($_SERVER['PHP_SELF']).'">';
print '<input type="hidden" name="mainmenu" value="accountancy">';
print '<input type="hidden" name="leftmenu" value="accountancy_accountancy_customerdeposits">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="sortfield" value="'.dolPrintHTMLForAttribute($sortfield).'">';
print '<input type="hidden" name="sortorder" value="'.dolPrintHTMLForAttribute($sortorder).'">';
print_barre_liste($title, $page, $_SERVER['PHP_SELF'], $param, $sortfield, $sortorder, '', $result, $report->num_total, 'accountancy', 0, '', '', $limit);
print '<div class="opacitymedium marginbottomonly">'.$langs->trans('DepositControlDescription').'</div>';
print '<div class="warning">'.$langs->trans('DepositControlHistoryWarning').'</div>';
print '<div class="liste_titre liste_titre_bydiv centpercent">';
print '<div class="divsearchfield">'.$langs->trans('DepositControlCutoffDate').': ';
print $form->selectDate($date_cutoff, 'date_cutoff', 0, 0, 0, '', 1, 0, 0, '', '', '', '', 1, '', '', 'tzserver');
print '</div>';
print '<div class="divsearchfield"><label for="search_status">'.$langs->trans('Status').'</label> ';
print $form->selectarray('search_status', array('all' => $langs->trans('All'), 'outstanding' => $langs->trans('DepositControlOutstanding'), 'cleared' => $langs->trans('DepositControlCleared')), $search_status, 0, 0, 0, '', 0, 0, 0, '', 'maxwidth200');
print '</div>';
print '</div>';

$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $contextpage, $conf->main_checkbox_left_column);
$colspan = 1;
foreach ($arrayfields as $field) {
	if (!empty($field['checked'])) {
		$colspan++;
	}
}
print '<div class="div-table-responsive">';
print '<table class="liste listwithfilterbefore centpercent">';
print '<tr class="liste_titre_filter">';
if ($conf->main_checkbox_left_column) {
	print '<td class="liste_titre maxwidthsearch">'.$form->showFilterButtons('left').'</td>';
}
foreach ($arrayfields as $key => $field) {
	if (empty($field['checked'])) {
		continue;
	}
	print '<td class="liste_titre">';
	if ($key === 'f.ref') {
		print '<input class="flat maxwidth100" aria-label="'.dolPrintHTMLForAttribute($langs->transnoentities('Ref')).'" name="search_ref" value="'.dolPrintHTMLForAttribute($search_ref).'">';
	} elseif ($key === 's.nom') {
		print '<input class="flat maxwidth100" aria-label="'.dolPrintHTMLForAttribute($langs->transnoentities('ThirdParty')).'" name="search_thirdparty" value="'.dolPrintHTMLForAttribute($search_thirdparty).'">';
	}
	print '</td>';
}
if (!$conf->main_checkbox_left_column) {
	print '<td class="liste_titre maxwidthsearch">'.$form->showFilterButtons().'</td>';
}
print '</tr>';
print '<tr class="liste_titre">';
if ($conf->main_checkbox_left_column) {
	print getTitleFieldOfList($selectedfields, 0, $_SERVER['PHP_SELF'], '', '', '', '', $sortfield, $sortorder, 'center maxwidthsearch');
}
foreach ($arrayfields as $key => $field) {
	if (!empty($field['checked'])) {
		$sortkey = strpos($key, '.') !== false ? $key : '';
		print_liste_field_titre($field['label'], $_SERVER['PHP_SELF'], $sortkey, '', $param, '', $sortfield, $sortorder, in_array($key, array('f.total_ttc', 'received', 'refunds', 'credited', 'applied', 'remaining')) ? 'right' : '');
	}
}
if (!$conf->main_checkbox_left_column) {
	print getTitleFieldOfList($selectedfields, 0, $_SERVER['PHP_SELF'], '', '', '', '', $sortfield, $sortorder, 'center maxwidthsearch');
}
print '</tr>';

foreach ($report->lines as $line) {
	$invoice->id = (int) $line->rowid;
	$invoice->ref = $line->ref;
	$invoice->type = (int) $line->type;
	$thirdparty->id = (int) $line->fk_soc;
	$thirdparty->name = $line->thirdparty_name;
	print '<tr class="oddeven">';
	if ($conf->main_checkbox_left_column) {
		print '<td></td>';
	}
	foreach ($arrayfields as $key => $field) {
		if (empty($field['checked'])) {
			continue;
		}
		print '<td'.(in_array($key, array('f.total_ttc', 'received', 'refunds', 'credited', 'applied', 'remaining')) ? ' class="right nowraponall"' : '').'>';
		switch ($key) {
			case 'f.ref':
				print $user->hasRight('facture', 'lire') ? $invoice->getNomUrl(1, '', 0, 0, '', 1) : dolPrintLabel($line->ref);
				break;
			case 'f.datef':
				print dol_print_date($line->datef, 'day', 'tzserver');
				break;
			case 's.nom':
				print $user->hasRight('societe', 'lire') ? $thirdparty->getNomUrl(1, '', 0, 1, -1, 1) : dolPrintLabel($line->thirdparty_name);
				break;
			case 'f.total_ttc':
				print price($line->total_ttc);
				break;
			case 'received':
				print price($line->calculation['received']);
				break;
			case 'refunds':
			case 'credited':
			case 'applied':
				print price($line->{$key});
				break;
			case 'pay.last_payment':
				print dol_print_date($line->last_payment, 'day', 'tzserver');
				break;
			case 'remaining':
				print price($line->calculation['remaining']);
				break;
			case 'applications':
				foreach ($line->applications as $application) {
					$invoice->id = (int) $application->rowid;
					$invoice->ref = $application->ref;
					$invoice->type = (int) $application->type;
					print '<div class="nowraponall">';
					print $user->hasRight('facture', 'lire') ? $invoice->getNomUrl(1, '', 0, 0, '', 1) : dolPrintLabel($application->ref);
					print ' '.dol_print_date($application->datef, 'day', 'tzserver').' — '.price($application->amount);
					print ' '.dolGetBadge($langs->trans($application->at_cutoff ? 'DepositControlAppliedAtCutoff' : 'DepositControlNotAppliedAtCutoff'), '', $application->at_cutoff ? 'info' : 'warning');
					print '</div>';
				}
				if (!$line->applications) {
					print '<span class="opacitymedium">'.$langs->trans('None').'</span>';
				}
				break;
			case 'orders':
				foreach ($line->orders as $order) {
					$orderobject->id = (int) $order->rowid;
					$orderobject->ref = $order->ref;
					$orderobject->status = (int) $order->fk_statut;
					$orderobject->statut = (int) $order->fk_statut;
					print '<div class="nowraponall">'.$orderobject->getNomUrl(1, '', 0, 0, 1).' '.$orderobject->getLibStatut(5).'</div>';
					if ($order->date_livraison) {
						print '<div>'.$langs->trans('DateDeliveryPlanned').': '.dol_print_date($order->date_livraison, 'day', 'tzserver').'</div>';
					}
					if ($order->date_cloture) {
						print '<div>'.$langs->trans('DateClosing').': '.dol_print_date($order->date_cloture, 'day', 'tzserver').'</div>';
					}
				}
				if (!$line->orders) {
					print '<span class="opacitymedium">'.$langs->trans('DepositControlNoOrder').'</span>';
				}
				break;
			case 'status':
				print dolGetBadge($langs->trans($line->calculation['status']), '', $line->calculation['status'] === 'DepositControlCleared' ? 'secondary' : 'warning');
				break;
		}
		print '</td>';
	}
	if (!$conf->main_checkbox_left_column) {
		print '<td></td>';
	}
	print '</tr>';
}
if (!$report->lines) {
	print '<tr class="oddeven"><td colspan="'.$colspan.'" class="opacitymedium">'.$langs->trans('NoRecordFound').'</td></tr>';
} else {
	print '<tr class="liste_total"><td colspan="'.$colspan.'" class="right">'.$langs->trans('DepositControlPageTotal', price($report->totals['received']), price($report->totals['applied']), price($report->totals['remaining'])).'</td></tr>';
}
print '</table></div></form>';

llxFooter();
$db->close();
