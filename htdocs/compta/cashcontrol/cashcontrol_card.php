<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2013 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2013      Charles-Fr BENKE     <charles.fr@benke.fr>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
 * Copyright (C) 2016      Marcos García        <marcosgdf@gmail.com>
 * Copyright (C) 2018      Andreu Bisquerra		<jove@bisquerra.com>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 *      \file       htdocs/compta/cashcontrol/cashcontrol_card.php
 *      \ingroup    cashdesk|takepos
 *      \brief      Page to show a cash fence
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/cashcontrol/class/cashcontrol.class.php';

$langs->loadLangs(array("install", "cashdesk", "admin", "banks"));

$id = GETPOSTINT('id');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$categid = GETPOST('categid');
$label = GETPOST("label");

$now = dol_now();
$syear = (GETPOSTISSET('closeyear') ? GETPOSTINT('closeyear') : dol_print_date($now, "%Y"));
$smonth = (GETPOSTISSET('closemonth') ? GETPOSTINT('closemonth') : dol_print_date($now, "%m"));
$sday = (GETPOSTISSET('closeday') ? GETPOSTINT('closeday') : dol_print_date($now, "%d"));

$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
if (empty($page) || $page == -1) {
	$page = 0;
}     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortfield) {
	$sortfield = 'rowid';
}
if (!$sortorder) {
	$sortorder = 'ASC';
}
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'thirdpartylist';

if ($contextpage == 'takepos') {
	$optioncss = 'print';
}

$arrayofpaymentmode = array('cash' => 'Cash', 'cheque' => 'Cheque', 'card' => 'CreditCard');

$arrayofposavailable = array();
if (isModEnabled('cashdesk')) {
	$arrayofposavailable['cashdesk'] = $langs->trans('CashDesk').' (cashdesk)';
}
if (isModEnabled('takepos')) {
	$arrayofposavailable['takepos'] = $langs->trans('TakePOS').' (takepos)';
}
// TODO Add hook here to allow other POS to add themself

$object = new CashControl($db);
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('cashcontrolcard', 'globalcard'));

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be 'include', not 'include_once'.

// Security check
if ($user->socid > 0) {	// Protection if external user
	//$socid = $user->socid;
	accessforbidden();
}
if (!$user->hasRight("cashdesk", "run") && !$user->hasRight("takepos", "run")) {
	accessforbidden();
}


/*
 * Actions
 */

$permissiontoadd = ($user->hasRight("cashdesk", "run") || $user->hasRight("takepos", "run"));
$permissiontodelete = ($user->hasRight("cashdesk", "run") || $user->hasRight("takepos", "run")) || ($permissiontoadd && $object->status == 0);
if (empty($backtopage)) {
	$backtopage = DOL_URL_ROOT.'/compta/cashcontrol/cashcontrol_card.php?id='.(!empty($id) && $id > 0 ? $id : '__ID__');
}
$backurlforlist = DOL_URL_ROOT.'/compta/cashcontrol/cashcontrol_list.php';
$triggermodname = 'CACHCONTROL_MODIFY'; // Name of trigger action code to execute when we modify record

if (!getDolGlobalString('CASHDESK_ID_BANKACCOUNT_CASH') && !getDolGlobalString('CASHDESK_ID_BANKACCOUNT_CASH1')) {
	setEventMessages($langs->trans("CashDesk")." - ".$langs->trans("NotConfigured"), null, 'errors');
}


if (GETPOST('cancel', 'alpha')) {
	if ($action == 'valid') {
		$action = 'view';
	} else {
		$action = 'create';
	}
}

if ($action == "reopen") {
	$result = $object->setStatut($object::STATUS_DRAFT, null, '', 'CASHFENCE_REOPEN');
	if ($result < 0) {
		setEventMessages($object->error, $object->errors, 'errors');
	}

	$action = 'view';
}

if ($action == "start") {
	$error = 0;
	if (!GETPOST('posmodule', 'alpha') || GETPOST('posmodule', 'alpha') == '-1') {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Module")), null, 'errors');
		$action = 'create';
		$error++;
	}
	if (GETPOST('posnumber', 'alpha') == '') {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("CashDesk")), null, 'errors');
		$action = 'create';
		$error++;
	}
	if (!GETPOST('closeyear', 'alpha') || GETPOST('closeyear', 'alpha') == '-1') {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Year")), null, 'errors');
		$action = 'create';
		$error++;
	}
} elseif ($action == "add") {
	if (GETPOST('opening', 'alpha') == '') {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("InitialBankBalance")), null, 'errors');
		$action = 'start';
		$error++;
	}
	$error = 0;
	foreach ($arrayofpaymentmode as $key => $val) {
		$object->$key = price2num(GETPOST($key.'_amount', 'alpha'));
	}

	if (!$error) {
		$object->day_close = GETPOSTINT('closeday');
		$object->month_close = GETPOSTINT('closemonth');
		$object->year_close = GETPOSTINT('closeyear');

		$object->opening = price2num(GETPOST('opening', 'alpha'));
		$object->posmodule = GETPOST('posmodule', 'alpha');
		$object->posnumber = GETPOST('posnumber', 'alpha');

		$db->begin();

		$id = $object->create($user);

		if ($id > 0) {
			$db->commit();
			$action = "view";
		} else {
			$db->rollback();
			$action = "view";
		}
	}
	if ($contextpage == 'takepos') {
		print "
		<script>
		parent.location.href='../../takepos/index.php?place='+parent.place;
		</script>";
		exit;
	}
}

if ($action == "valid") {	// validate = close
	$object->fetch($id);

	$db->begin();

	/*
	$object->day_close = GETPOST('closeday', 'int');
	$object->month_close = GETPOST('closemonth', 'int');
	$object->year_close = GETPOST('closeyear', 'int');
	*/

	$object->cash = price2num(GETPOST('cash_amount', 'alpha'));
	$object->card = price2num(GETPOST('card_amount', 'alpha'));
	$object->cheque = price2num(GETPOST('cheque_amount', 'alpha'));

	$result = $object->update($user);

	$result = $object->valid($user);

	if ($result <= 0) {
		setEventMessages($object->error, $object->errors, 'errors');
		$db->rollback();
	} else {
		setEventMessages($langs->trans("CashFenceDone"), null);
		$db->commit();
	}

	if ($contextpage == 'takepos') {
		print "
		<script>
		parent.location.href='../../takepos/index.php?place='+parent.place;
		</script>";
		exit;
	}
	$action = "view";
}

// Action to delete
if ($action == 'confirm_delete' && !empty($permissiontodelete)) {
	$object->fetch($id);

	if (!($object->id > 0)) {
		dol_print_error(null, 'Error, object must be fetched before being deleted');
		exit;
	}

	$result = $object->delete($user);
	//var_dump($result);
	if ($result > 0) {
		// Delete OK
		setEventMessages("RecordDeleted", null, 'mesgs');
		header("Location: ".$backurlforlist);
		exit;
	} else {
		if (!empty($object->errors)) {
			setEventMessages(null, $object->errors, 'errors');
		} else {
			setEventMessages($object->error, null, 'errors');
		}
	}
}


/*
 * View
 */

$form = new Form($db);

$initialbalanceforterminal = array();
$theoricalamountforterminal = array();
$theoricalnbofinvoiceforterminal = array();


llxHeader('', $langs->trans("CashControl"));


if ($action == "create" || $action == "start" || $action == 'close') {
	if ($action == 'close') {
		$posmodule = $object->posmodule;
		$terminalid = $object->posnumber;
		$terminaltouse = $terminalid;

		$syear = $object->year_close;
		$smonth = $object->month_close;
		$sday = $object->day_close;
	} elseif (GETPOST('posnumber', 'alpha') != '' && GETPOST('posnumber', 'alpha') != '' && GETPOST('posnumber', 'alpha') != '-1') {
		$posmodule = GETPOST('posmodule', 'alpha');
		$terminalid = GETPOST('posnumber', 'alpha');
		$terminaltouse = $terminalid;

		if ($terminaltouse == '1' && $posmodule == 'cashdesk') {
			$terminaltouse = '';
		}

		if ($posmodule == 'cashdesk' && $terminaltouse != '' && $terminaltouse != '1') {
			$terminaltouse = '';
			setEventMessages($langs->trans("OnlyTerminal1IsAvailableForCashDeskModule"), null, 'errors');
			$error++;
		}
	}

	if (isset($terminalid) && $terminalid != '') {
		// Calculate $initialbalanceforterminal for terminal 0
		foreach ($arrayofpaymentmode as $key => $val) {
			if ($key != 'cash') {
				$initialbalanceforterminal[$terminalid][$key] = 0;
				continue;
			}

			// Get the bank account dedicated to this point of sale module/terminal
			$vartouse = 'CASHDESK_ID_BANKACCOUNT_CASH'.$terminaltouse;
			$bankid = getDolGlobalInt($vartouse);

			if ($bankid > 0) {
				$sql = "SELECT SUM(amount) as total FROM ".MAIN_DB_PREFIX."bank";
				$sql .= " WHERE fk_account = ".((int) $bankid);
				if ($syear && !$smonth) {
					$sql .= " AND dateo < '".$db->idate(dol_get_first_day($syear, 1))."'";
				} elseif ($syear && $smonth && !$sday) {
					$sql .= " AND dateo < '".$db->idate(dol_get_first_day($syear, $smonth))."'";
				} elseif ($syear && $smonth && $sday) {
					$sql .= " AND dateo < '".$db->idate(dol_mktime(0, 0, 0, $smonth, $sday, $syear))."'";
				} else {
					setEventMessages($langs->trans('YearNotDefined'), null, 'errors');
				}

				$resql = $db->query($sql);
				if ($resql) {
					$obj = $db->fetch_object($resql);
					if ($obj) {
						$initialbalanceforterminal[$terminalid][$key] = $obj->total;
					}
				} else {
					dol_print_error($db);
				}
			} else {
				setEventMessages($langs->trans("SetupOfTerminalNotComplete", $terminaltouse), null, 'errors');
				$error++;
			}
		}

		// Calculate $theoricalamountforterminal
		foreach ($arrayofpaymentmode as $key => $val) {
			$sql = "SELECT SUM(pf.amount) as total, COUNT(*) as nb";
			$sql .= " FROM ".MAIN_DB_PREFIX."paiement_facture as pf, ".MAIN_DB_PREFIX."facture as f, ".MAIN_DB_PREFIX."paiement as p, ".MAIN_DB_PREFIX."c_paiement as cp";
			$sql .= " WHERE pf.fk_facture = f.rowid AND p.rowid = pf.fk_paiement AND cp.id = p.fk_paiement";
			$sql .= " AND f.module_source = '".$db->escape($posmodule)."'";
			$sql .= " AND f.pos_source = '".$db->escape($terminalid)."'";
			$sql .= " AND f.paye = 1";
			$sql .= " AND p.entity IN (".getEntity('facture').")";
			if ($key == 'cash') {
				$sql .= " AND cp.code = 'LIQ'";
			} elseif ($key == 'cheque') {
				$sql .= " AND cp.code = 'CHQ'";
			} elseif ($key == 'card') {
				$sql .= " AND cp.code = 'CB'";
			} else {
				dol_print_error(null, 'Value for key = '.$key.' not supported');
				exit;
			}
			if ($syear && !$smonth) {
				$sql .= " AND datef BETWEEN '".$db->idate(dol_get_first_day($syear, 1))."' AND '".$db->idate(dol_get_last_day($syear, 12))."'";
			} elseif ($syear && $smonth && !$sday) {
				$sql .= " AND datef BETWEEN '".$db->idate(dol_get_first_day($syear, $smonth))."' AND '".$db->idate(dol_get_last_day($syear, $smonth))."'";
			} elseif ($syear && $smonth && $sday) {
				$sql .= " AND datef BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $smonth, $sday, $syear))."' AND '".$db->idate(dol_mktime(23, 59, 59, $smonth, $sday, $syear))."'";
			} else {
				setEventMessages($langs->trans('YearNotDefined'), null, 'errors');
			}

			$resql = $db->query($sql);
			if ($resql) {
				$theoricalamountforterminal[$terminalid][$key] = $initialbalanceforterminal[$terminalid][$key];

				$obj = $db->fetch_object($resql);
				if ($obj) {
					$theoricalamountforterminal[$terminalid][$key] = price2num($theoricalamountforterminal[$terminalid][$key] + $obj->total);
					$theoricalnbofinvoiceforterminal[$terminalid][$key] = $obj->nb;
				}
			} else {
				dol_print_error($db);
			}
		}
	}

	//var_dump($theoricalamountforterminal); var_dump($theoricalnbofinvoiceforterminal);
	if ($action != 'close') {
		print load_fiche_titre($langs->trans("CashControl")." - ".$langs->trans("New"), '', 'cash-register');

		print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		if ($contextpage == 'takepos') {
			print '<input type="hidden" name="contextpage" value="takepos">';
		}
		if ($action == 'start' && GETPOSTINT('posnumber') != '' && GETPOSTINT('posnumber') != '' && GETPOSTINT('posnumber') != '-1') {
			print '<input type="hidden" name="action" value="add">';
		} elseif ($action == 'close') {
			print '<input type="hidden" name="action" value="valid">';
			print '<input type="hidden" name="id" value="'.$id.'">';
		} else {
			print '<input type="hidden" name="action" value="start">';
		}

		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<td>'.$langs->trans("Module").'</td>';
		print '<td>'.$langs->trans("Terminal").'</td>';
		print '<td>'.$langs->trans("Year").'</td>';
		print '<td>'.$langs->trans("Month").'</td>';
		print '<td>'.$langs->trans("Day").'</td>';
		print '<td></td>';
		print "</tr>\n";

		$disabled = 0;
		$prefix = 'close';

		print '<tr class="oddeven">';
		print '<td>'.$form->selectarray('posmodule', $arrayofposavailable, GETPOST('posmodule', 'alpha'), (count($arrayofposavailable) > 1 ? 1 : 0)).'</td>';
		print '<td>';

		$arrayofpos = array();
		$numterminals = max(1, getDolGlobalString('TAKEPOS_NUM_TERMINALS'));
		for ($i = 1; $i <= $numterminals; $i++) {
			$nameofterminal = getDolGlobalString("TAKEPOS_TERMINAL_NAME_".$i);
			$arrayofpos[$i] = array('id' => $i, 'label' => (($nameofterminal != "TAKEPOS_TERMINAL_NAME_".$i) ? '#'.$i.' '.$nameofterminal : $i), 'data-html' => (($nameofterminal != "TAKEPOS_TERMINAL_NAME_".$i) ? '#'.$i.' - '.$nameofterminal : $i));
		}
		$selectedposnumber = 0;
		$showempty = 1;
		if (getDolGlobalString('TAKEPOS_NUM_TERMINALS') == '1') {
			$selectedposnumber = 1;
			$showempty = 0;
		}
		print $form->selectarray('posnumber', $arrayofpos, GETPOSTISSET('posnumber') ? GETPOSTINT('posnumber') : $selectedposnumber, $showempty);
		//print '<input name="posnumber" type="text" class="maxwidth50" value="'.(GETPOSTISSET('posnumber')?GETPOST('posnumber', 'alpha'):'0').'">';
		print '</td>';
		// Year
		print '<td>';
		$retstring = '<select'.($disabled ? ' disabled' : '').' class="flat valignmiddle maxwidth75imp" id="'.$prefix.'year" name="'.$prefix.'year">';
		for ($year = $syear - 10; $year < $syear + 10; $year++) {
			$retstring .= '<option value="'.$year.'"'.($year == $syear ? ' selected' : '').'>'.$year.'</option>';
		}
		$retstring .= "</select>\n";
		print $retstring;
		print '</td>';
		// Month
		print '<td>';
		$retstring = '<select'.($disabled ? ' disabled' : '').' class="flat valignmiddle maxwidth75imp" id="'.$prefix.'month" name="'.$prefix.'month">';
		$retstring .= '<option value="0"></option>';
		for ($month = 1; $month <= 12; $month++) {
			$retstring .= '<option value="'.$month.'"'.($month == $smonth ? ' selected' : '').'>';
			$retstring .= dol_print_date(mktime(12, 0, 0, $month, 1, 2000), "%b");
			$retstring .= "</option>";
		}
		$retstring .= "</select>";
		print $retstring;
		print '</td>';
		// Day
		print '<td>';
		$retstring = '<select'.($disabled ? ' disabled' : '').' class="flat valignmiddle maxwidth50imp" id="'.$prefix.'day" name="'.$prefix.'day">';
		$retstring .= '<option value="0" selected>&nbsp;</option>';
		for ($day = 1; $day <= 31; $day++) {
			$retstring .= '<option value="'.$day.'"'.($day == $sday ? ' selected' : '').'>'.$day.'</option>';
		}
		$retstring .= "</select>";
		print $retstring;
		print '</td>';
		// Button Start
		print '<td>';
		if ($action == 'start' && GETPOST('posnumber') != '' && GETPOST('posnumber') != '' && GETPOST('posnumber') != '-1') {
			print '';
		} else {
			print '<input type="submit" name="add" class="button" value="'.$langs->trans("Start").'">';
		}
		print '</td>';
		print '</table>';
		print '</div>';

		// Table to see/enter balance
		if (($action == 'start' && GETPOST('posnumber') != '' && GETPOST('posnumber') != '' && GETPOST('posnumber') != '-1') || $action == 'close') {
			$posmodule = GETPOST('posmodule', 'alpha');
			$terminalid = GETPOST('posnumber', 'alpha');

			print '<br>';

			print '<div class="div-table-responsive-no-min">';
			print '<table class="noborder centpercent">';

			print '<tr class="liste_titre">';
			print '<td></td>';
			print '<td class="center">'.$langs->trans("InitialBankBalance");
			//print '<br>'.$langs->trans("TheoricalAmount").'<br>'.$langs->trans("RealAmount");
			print '</td>';

			/*
			print '<td align="center" class="hide0" colspan="'.count($arrayofpaymentmode).'">';
			print $langs->trans("AmountAtEndOfPeriod");
			print '</td>';
			*/
			print '<td></td>';
			print '</tr>';

			print '<tr class="liste_titre">';
			print '<td></td>';
			print '<td class="center">'.$langs->trans("Cash");
			//print '<br>'.$langs->trans("TheoricalAmount").'<br>'.$langs->trans("RealAmount");
			print '</td>';
			/*
			$i = 0;
			foreach ($arrayofpaymentmode as $key => $val)
			{
				print '<td align="center"'.($i == 0 ? ' class="hide0"' : '').'>'.$langs->trans($val);
				//print '<br>'.$langs->trans("TheoricalAmount").'<br>'.$langs->trans("RealAmount");
				print '</td>';
				$i++;
			}*/
			print '<td></td>';
			print '</tr>';

			/*print '<tr>';
			// Initial amount
			print '<td>'.$langs->trans("NbOfInvoices").'</td>';
			print '<td class="center">';
			print '</td>';
			// Amount per payment type
			$i = 0;
			foreach ($arrayofpaymentmode as $key => $val)
			{
				print '<td align="center"'.($i == 0 ? ' class="hide0"' : '').'>';
				print $theoricalnbofinvoiceforterminal[$terminalid][$key];
				print '</td>';
				$i++;
			}
			// Save
			print '<td align="center"></td>';
			print '</tr>';
			*/

			print '<tr>';
			// Initial amount
			print '<td>'.$langs->trans("TheoricalAmount").'</td>';
			print '<td class="center">';
			print price($initialbalanceforterminal[$terminalid]['cash']).'<br>';
			print '</td>';
			// Amount per payment type
			/*$i = 0;
			foreach ($arrayofpaymentmode as $key => $val)
			{
				print '<td align="center"'.($i == 0 ? ' class="hide0"' : '').'>';
				print price($theoricalamountforterminal[$terminalid][$key]).'<br>';
				print '</td>';
				$i++;
			}*/
			// Save
			print '<td></td>';
			print '</tr>';

			print '<tr>';
			print '<td>'.$langs->trans("RealAmount").'</td>';
			// Initial amount
			print '<td class="center">';
			print '<input ';
			if ($action == 'close') {
				print 'disabled '; // To close cash user can't set opening cash
			}
			print 'name="opening" type="text" class="maxwidth100 center" value="';
			if ($action == 'close') {
				$object->fetch($id);
				print $object->opening;
			} else {
				print(GETPOSTISSET('opening') ? price2num(GETPOST('opening', 'alpha')) : price($initialbalanceforterminal[$terminalid]['cash']));
			}
			print '">';
			print '</td>';
			// Amount per payment type
			/*$i = 0;
			foreach ($arrayofpaymentmode as $key => $val)
			{
				print '<td align="center"'.($i == 0 ? ' class="hide0"' : '').'>';
				print '<input ';
				if ($action == 'start') print 'disabled '; // To start cash user only can set opening cash
				print 'name="'.$key.'_amount" type="text"'.($key == 'cash' ? ' autofocus' : '').' class="maxwidth100 center" value="'.GETPOST($key.'_amount', 'alpha').'">';
				print '</td>';
				$i++;
			}*/
			// Save
			print '<td class="center">';
			print '<input type="submit" name="cancel" class="button button-cancel" value="'.$langs->trans("Cancel").'">';
			if ($action == 'start') {
				print '<input type="submit" name="add" class="button button-save" value="'.$langs->trans("Save").'">';
			} elseif ($action == 'close') {
				print '<input type="submit" name="valid" class="button" value="'.$langs->trans("Validate").'">';
			}
			print '</td>';
			print '</tr>';

			print '</table>';
			print '</div>';
		}

		print '</form>';
	}
}

if (empty($action) || $action == "view" || $action == "close") {
	$result = $object->fetch($id);

	if ($result <= 0) {
		print $langs->trans("ErrorRecordNotFound");
	} else {
		$head = array();
		$head[0][0] = DOL_URL_ROOT.'/compta/cashcontrol/cashcontrol_card.php?id='.$object->id;
		$head[0][1] = $langs->trans("CashControl");
		$head[0][2] = 'cashcontrol';

		print dol_get_fiche_head($head, 'cashcontrol', $langs->trans("CashControl"), -1, 'account');

		$linkback = '<a href="'.DOL_URL_ROOT.'/compta/cashcontrol/cashcontrol_list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

		$morehtmlref = '<div class="refidno">';
		$morehtmlref .= '</div>';


		dol_banner_tab($object, 'id', $linkback, 1, 'rowid', 'rowid', $morehtmlref);

		print '<div class="fichecenter">';
		print '<div class="fichehalfleft">';
		print '<div class="underbanner clearboth"></div>';
		print '<table class="border tableforfield" width="100%">';

		print '<tr><td class="titlefield nowrap">';
		print $langs->trans("Ref");
		print '</td><td>';
		print $id;
		print '</td></tr>';

		print '<tr><td valign="middle">'.$langs->trans("Module").'</td><td>';
		print $object->posmodule;
		print "</td></tr>";

		print '<tr><td valign="middle">'.$langs->trans("Terminal").'</td><td>';
		print $object->posnumber;
		print "</td></tr>";

		print '<tr><td class="nowrap">';
		print $langs->trans("Period");
		print '</td><td>';
		print $object->year_close;
		print($object->month_close ? "-" : "").$object->month_close;
		print($object->day_close ? "-" : "").$object->day_close;
		print '</td></tr>';

		print '</table>';
		print '</div>';

		print '<div class="fichehalfright">';
		print '<div class="underbanner clearboth"></div>';

		print '<table class="border tableforfield centpercent">';

		print '<tr><td class="titlefield nowrap">';
		print $langs->trans("DateCreationShort");
		print '</td><td>';
		print dol_print_date($object->date_creation, 'dayhour');
		print '</td></tr>';

		print '<tr><td valign="middle">'.$langs->trans("InitialBankBalance").' - '.$langs->trans("Cash").'</td><td>';
		print '<span class="amount">'.price($object->opening, 0, $langs, 1, -1, -1, $conf->currency).'</span>';
		print "</td></tr>";
		foreach ($arrayofpaymentmode as $key => $val) {
			print '<tr><td valign="middle">'.$langs->trans($val).'</td><td>';
			print '<span class="amount">'.price($object->$key, 0, $langs, 1, -1, -1, $conf->currency).'</span>';
			print "</td></tr>";
		}

		print "</table>\n";

		print '</div></div>';
		print '<div class="clearboth"></div>';

		print dol_get_fiche_end();

		if ($action != 'close') {
			print '<div class="tabsAction">';

			// Print ticket
			print '<div class="inline-block divButAction"><a target="_blank" rel="noopener noreferrer" class="butAction" href="report.php?id='.((int) $id).'">'.$langs->trans('PrintReport').'</a></div>';

			// Print ticket (no detail)
			print '<div class="inline-block divButAction"><a target="_blank" rel="noopener noreferrer" class="butAction" href="report.php?id='.((int) $id).'&summaryonly=1">'.$langs->trans('PrintReportNoDetail').'</a></div>';

			if ($object->status == CashControl::STATUS_DRAFT) {
				print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.((int) $id).'&action=close&token='.newToken().'&contextpage='.$contextpage.'">'.$langs->trans('Close').'</a></div>';

				print '<div class="inline-block divButAction"><a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?id='.((int) $id).'&action=confirm_delete&token='.newToken().'">'.$langs->trans('Delete').'</a></div>';
			} else {
				print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.((int) $id).'&action=reopen&token='.newToken().'">'.$langs->trans('ReOpen').'</a></div>';
			}

			print '</div>';

			if ($contextpage != 'takepos') {
				print '<center><iframe src="report.php?id='.$id.'" width="60%" height="800"></iframe></center>';
			}
		} else {
			print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'" name="formclose">';
			print '<input type="hidden" name="token" value="'.newToken().'">';
			if ($contextpage == 'takepos') {
				print '<input type="hidden" name="contextpage" value="takepos">';
			}
			if ($action == 'start' && GETPOSTINT('posnumber') != '' && GETPOSTINT('posnumber') != '' && GETPOSTINT('posnumber') != '-1') {
				print '<input type="hidden" name="action" value="add">';
			} elseif ($action == 'close') {
				print '<input type="hidden" name="action" value="valid">';
				print '<input type="hidden" name="id" value="'.$id.'">';
			} else {
				print '<input type="hidden" name="action" value="start">';
			}

			/*
			print '<div class="div-table-responsive-no-min">';
			print '<table class="noborder centpercent">';
			print '<tr class="liste_titre">';
			print '<td>'.$langs->trans("Module").'</td>';
			print '<td>'.$langs->trans("Terminal").'</td>';
			print '<td>'.$langs->trans("Year").'</td>';
			print '<td>'.$langs->trans("Month").'</td>';
			print '<td>'.$langs->trans("Day").'</td>';
			print '<td></td>';
			print "</tr>\n";

			$disabled = 1;
			$prefix = 'close';

			print '<tr class="oddeven">';
			print '<td>'.$form->selectarray('posmodulebis', $arrayofposavailable, $object->posmodule, (count($arrayofposavailable) > 1 ? 1 : 0), 0, 0, '', 0, 0, $disabled).'</td>';
			print '<input type="hidden" name="posmodule" value="'.$object->posmodule.'">';
			print '<td>';

			$array = array();
			$numterminals = max(1, $conf->global->TAKEPOS_NUM_TERMINALS);
			for($i = 1; $i <= $numterminals; $i++) {
				$array[$i] = $i;
			}
			$selectedposnumber = $object->posnumber; $showempty = 1;
			//print $form->selectarray('posnumber', $array, GETPOSTISSET('posnumber') ?GETPOST('posnumber', 'int') : $selectedposnumber, $showempty, 0, 0, '', 0, 0, $disabled);
			print '<input name="posnumberbis" disabled="disabled" type="text" class="maxwidth50" value="'.$object->posnumber.'">';
			print '<input type="hidden" name="posnumber" value="'.$object->posmodule.'">';
			print '</td>';
			// Year
			print '<td>';
			print '<input name="yearbis" disabled="disabled" type="text" class="maxwidth50" value="'.$object->year_close.'">';
			print '<input type="hidden" name="year_close" value="'.$object->year_close.'">';
			print '</td>';
			// Month
			print '<td>';
			print '<input name="monthbis" disabled="disabled" type="text" class="maxwidth50" value="'.$object->month_close.'">';
			print '<input type="hidden" name="month_close" value="'.$object->month_close.'">';
			print '</td>';
			// Day
			print '<td>';
			print '<input name="daybis" disabled="disabled" type="text" class="maxwidth50" value="'.$object->date_close.'">';
			print '<input type="hidden" name="day_close" value="'.$object->date_close.'">';
			print '</td>';

			print '<td></td>';
			print '</table>';
			print '</div>';
			*/

			// Table to see/enter balance
			if (($action == 'start' && GETPOST('posnumber') != '' && GETPOST('posnumber') != '' && GETPOST('posnumber') != '-1') || $action == 'close') {
				$posmodule = $object->posmodule;
				$terminalid = $object->posnumber;

				print '<br>';

				print '<div class="div-table-responsive-no-min">';
				print '<table class="noborder centpercent">';

				print '<tr class="liste_titre">';
				print '<td></td>';
				print '<td class="center">'.$langs->trans("InitialBankBalance");
				//print '<br>'.$langs->trans("TheoricalAmount").'<br>'.$langs->trans("RealAmount");
				print '</td>';

				print '<td align="center" class="hide0" colspan="'.count($arrayofpaymentmode).'">';
				print $langs->trans("AmountAtEndOfPeriod");
				print '</td>';
				print '<td></td>';
				print '</tr>';

				print '<tr class="liste_titre">';
				print '<td></td>';
				print '<td class="center">'.$langs->trans("Cash");
				//print '<br>'.$langs->trans("TheoricalAmount").'<br>'.$langs->trans("RealAmount");
				print '</td>';
				$i = 0;
				foreach ($arrayofpaymentmode as $key => $val) {
					print '<td align="center"'.($i == 0 ? ' class="hide0"' : '').'>'.$langs->trans($val);
					//print '<br>'.$langs->trans("TheoricalAmount").'<br>'.$langs->trans("RealAmount");
					print '</td>';
					$i++;
				}
				print '<td></td>';
				print '</tr>';

				print '<tr>';
				// Initial amount
				print '<td>'.$langs->trans("NbOfInvoices").'</td>';
				print '<td class="center">';
				print '</td>';
				// Amount per payment type
				$i = 0;
				foreach ($arrayofpaymentmode as $key => $val) {
					print '<td align="center"'.($i == 0 ? ' class="hide0"' : '').'>';
					print $theoricalnbofinvoiceforterminal[$terminalid][$key];
					print '</td>';
					$i++;
				}
				// Save
				print '<td align="center"></td>';
				print '</tr>';

				print '<tr>';
				// Initial amount
				print '<td>'.$langs->trans("TheoricalAmount").'</td>';
				print '<td class="center">';
				print price($initialbalanceforterminal[$terminalid]['cash']).'<br>';
				print '</td>';
				// Amount per payment type
				$i = 0;
				foreach ($arrayofpaymentmode as $key => $val) {
					print '<td align="center"'.($i == 0 ? ' class="hide0"' : '').'>';
					if ($key == 'cash') {
						$deltaforcash = ((float) $object->opening - $initialbalanceforterminal[$terminalid]['cash']);
						print price($theoricalamountforterminal[$terminalid][$key] + $deltaforcash).'<br>';
					} else {
						print price($theoricalamountforterminal[$terminalid][$key]).'<br>';
					}
					print '</td>';
					$i++;
				}
				// Save
				print '<td align="center"></td>';
				print '</tr>';

				print '<tr>';
				print '<td>'.$langs->trans("RealAmount").'</td>';
				// Initial amount
				print '<td class="center">';
				print '<input ';
				if ($action == 'close') {
					print 'disabled '; // To close cash user can't set opening cash
				}
				print 'name="opening" type="text" class="maxwidth100 center" value="';
				if ($action == 'close') {
					$object->fetch($id);
					print $object->opening;
				} else {
					print(GETPOSTISSET('opening') ? price2num(GETPOST('opening', 'alpha')) : price($initialbalanceforterminal[$terminalid]['cash']));
				}
				print '">';
				print '</td>';
				// Amount per payment type
				$i = 0;
				foreach ($arrayofpaymentmode as $key => $val) {
					print '<td align="center"'.($i == 0 ? ' class="hide0"' : '').'>';
					print '<input ';
					if ($action == 'start') {
						print 'disabled '; // To start cash user only can set opening cash
					}
					print 'name="'.$key.'_amount" type="text"'.($key == 'cash' ? ' autofocus' : '').' class="maxwidth100 center" value="'.GETPOST($key.'_amount', 'alpha').'">';
					print '</td>';
					$i++;
				}
				// Save
				print '<td class="center">';
				print '<input type="submit" name="cancel" class="button button-cancel" value="'.$langs->trans("Cancel").'">';
				if ($action == 'start') {
					print '<input type="submit" name="add" class="button button-save" value="'.$langs->trans("Save").'">';
				} elseif ($action == 'close') {
					print '<input type="submit" name="valid" class="button" value="'.$langs->trans("Close").'">';
				}
				print '</td>';
				print '</tr>';

				print '</table>';
				print '</div>';
			}

			print '</form>';
		}
	}
}

// End of page
llxFooter();
$db->close();
