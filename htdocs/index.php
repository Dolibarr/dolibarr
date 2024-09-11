<?php
/* Copyright (C) 2001-2004	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2020	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2017	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2011-2012	Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2015		Marcos García			<marcosgdf@gmail.com>
 * Copyright (C) 2021		Frédéric France			<frederic.france@netlogic.fr>
 * Copyright (C) 2024		MDW						<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024		Alexandre Spangaro		<alexandre@inovea-conseil.com>
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
 *	\file       htdocs/index.php
 *	\brief      Dolibarr home page
 */


define('CSRFCHECK_WITH_TOKEN', 1); // We force need to use a token to login when making a POST

require 'main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';

// If not defined, we select menu "home"
$_GET['mainmenu'] = GETPOST('mainmenu', 'aZ09') ? GETPOST('mainmenu', 'aZ09') : 'home';	// Keep this ?
$action = GETPOST('action', 'aZ09');

$hookmanager->initHooks(array('index'));


/*
 * Actions
 */

// Define $nbmodulesnotautoenabled - TODO This code is at different places
$nbmodulesnotautoenabled = count($conf->modules);
$listofmodulesautoenabled = array('agenda', 'fckeditor', 'export', 'import');
foreach ($listofmodulesautoenabled as $moduleautoenable) {
	if (in_array($moduleautoenable, $conf->modules)) {
		$nbmodulesnotautoenabled--;
	}
}

// Check if company name is defined (first install)
if (!getDolGlobalString('MAIN_INFO_SOCIETE_NOM') || !getDolGlobalString('MAIN_INFO_SOCIETE_COUNTRY')) {
	header("Location: ".DOL_URL_ROOT."/admin/index.php?mainmenu=home&leftmenu=setup&mesg=setupnotcomplete");
	exit;
}
if ($nbmodulesnotautoenabled <= getDolGlobalInt('MAIN_MIN_NB_ENABLED_MODULE_FOR_WARNING', 1)) {	// If only autoenabled modules (property ->enabled_bydefault in modules) are activated
	header("Location: ".DOL_URL_ROOT."/admin/index.php?mainmenu=home&leftmenu=setup&mesg=setupnotcomplete");
	exit;
}
if (GETPOST('addbox')) {	// Add box (when submit is done from a form when ajax disabled)
	require_once DOL_DOCUMENT_ROOT.'/core/class/infobox.class.php';
	$zone = GETPOSTINT('areacode');
	$userid = GETPOSTINT('userid');
	$boxorder = GETPOST('boxorder', 'aZ09');
	$boxorder .= GETPOST('boxcombo', 'aZ09');

	$result = InfoBox::saveboxorder($db, $zone, $boxorder, $userid);
	if ($result > 0) {
		setEventMessages($langs->trans("BoxAdded"), null);
	}
}


/*
 * View
 */

if (!isset($form) || !is_object($form)) {
	$form = new Form($db);
}

// Title
$title = $langs->trans("HomeArea").' - Dolibarr '.DOL_VERSION;
if (getDolGlobalString('MAIN_APPLICATION_TITLE')) {
	$title = $langs->trans("HomeArea").' - ' . getDolGlobalString('MAIN_APPLICATION_TITLE');
}

llxHeader('', $title);


$resultboxes = FormOther::getBoxesArea($user, "0"); // Load $resultboxes (selectboxlist + boxactivated + boxlista + boxlistb)


if (getDolGlobalString('MAIN_MOTD')) {
	$conf->global->MAIN_MOTD = preg_replace('/<br(\s[\sa-zA-Z_="]*)?\/?>/i', '<br>', getDolGlobalString('MAIN_MOTD'));
	if (getDolGlobalString('MAIN_MOTD')) {
		$substitutionarray = getCommonSubstitutionArray($langs);
		complete_substitutions_array($substitutionarray, $langs);
		$texttoshow = make_substitutions(getDolGlobalString('MAIN_MOTD'), $substitutionarray, $langs);

		print "\n<!-- Start of welcome text -->\n";
		print '<table class="centpercent notopnoleftnoright"><tr><td>';
		print dol_htmlentitiesbr($texttoshow);
		print '</td></tr></table><br>';
		print "\n<!-- End of welcome text -->\n";
	}
}

/*
 * Show specific warnings
 */

// Specific warning to propose to upgrade invoice situation to progressive mode
if (getDolGlobalInt('INVOICE_USE_SITUATION') == 1) {
	$langs->loadLangs(array("admin"));
	print info_admin($langs->trans("WarningExperimentalFeatureInvoiceSituationNeedToUpgradeToProgressiveMode", 'https://partners.dolibarr.org'));
	//print "<br>";
}

/*
 * Show security warnings
 */

// Security warning if install.lock file is missing or if conf file is writable
if (!getDolGlobalString('MAIN_REMOVE_INSTALL_WARNING')) {
	$message = '';

	// Check if install lock file is present
	$lockfile = DOL_DATA_ROOT.'/install.lock';
	if (!empty($lockfile) && !file_exists($lockfile) && is_dir(DOL_DOCUMENT_ROOT."/install")) {
		$langs->load("errors");
		//if (!empty($message)) $message.='<br>';
		$message .= info_admin($langs->transnoentities("WarningLockFileDoesNotExists", DOL_DATA_ROOT).' '.$langs->transnoentities("WarningUntilDirRemoved", DOL_DOCUMENT_ROOT."/install"), 0, 0, '1', 'clearboth');
	}

	// Conf files must be in read only mode
	if (is_writable($conffile)) {	// $conffile is defined into filefunc.inc.php
		$langs->load("errors");
		//$langs->load("other");
		//if (!empty($message)) $message.='<br>';
		$message .= info_admin($langs->transnoentities("WarningConfFileMustBeReadOnly").' '.$langs->transnoentities("WarningUntilDirRemoved", DOL_DOCUMENT_ROOT."/install"), 0, 0, '1', 'clearboth');
	}

	$object = new stdClass();
	$parameters = array();
	$reshook = $hookmanager->executeHooks('infoadmin', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
	if ($reshook == 0) {
		$message .= $hookmanager->resPrint;
	}
	if ($message) {	// $message is an HTML string.
		print '<!-- show security warning -->';
		print dol_string_onlythesehtmltags($message, 1, 0, 0, 0, array('div', 'span', 'b'));
		//print '<br>';
		//print info_admin($langs->trans("WarningUntilDirRemoved",DOL_DOCUMENT_ROOT."/install"));
	}
}

/*
 * Dashboard Dolibarr statistics
 * Hidden for external users
 */

print load_fiche_titre('&nbsp;', $resultboxes['selectboxlist'], '', 0, '', 'titleforhome');

// Load translation files required by page
$langs->loadLangs(array('commercial', 'bills', 'orders', 'contracts'));

// Dolibarr Working Board with weather
if (!getDolGlobalString('MAIN_DISABLE_GLOBAL_WORKBOARD') && getDolGlobalInt('MAIN_OPTIMIZEFORTEXTBROWSER') < 2) {
	$showweather = (!getDolGlobalString('MAIN_DISABLE_METEO') || getDolGlobalInt('MAIN_DISABLE_METEO') == 2) ? 1 : 0;

	//Array that contains all WorkboardResponse classes to process them
	$dashboardlines = array();

	// Do not include sections without management permission
	require_once DOL_DOCUMENT_ROOT.'/core/class/workboardresponse.class.php';

	// Number of actions to do (late)
	if (isModEnabled('agenda') && !getDolGlobalString('MAIN_DISABLE_BLOCK_AGENDA') && $user->hasRight('agenda', 'myactions', 'read')) {
		include_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
		$board = new ActionComm($db);
		$dashboardlines[$board->element] = $board->load_board($user);
	}

	// Number of project opened
	if (isModEnabled('project') && !getDolGlobalString('MAIN_DISABLE_BLOCK_PROJECT') && $user->hasRight('projet', 'lire')) {
		include_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
		$board = new Project($db);
		$dashboardlines[$board->element] = $board->load_board($user);
	}

	// Number of tasks to do (late)
	if (isModEnabled('project') && !getDolGlobalString('MAIN_DISABLE_BLOCK_PROJECT') && !getDolGlobalString('PROJECT_HIDE_TASKS') && $user->hasRight('projet', 'lire')) {
		include_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
		$board = new Task($db);
		$dashboardlines[$board->element] = $board->load_board($user);
	}

	// Number of commercial customer proposals open (expired)
	if (isModEnabled('propal') && !getDolGlobalString('MAIN_DISABLE_BLOCK_CUSTOMER') && $user->hasRight('propal', 'read')) {
		include_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
		$board = new Propal($db);
		$dashboardlines[$board->element.'_opened'] = $board->load_board($user, "opened");
		// Number of commercial proposals CLOSED signed (billed)
		$dashboardlines[$board->element.'_signed'] = $board->load_board($user, "signed");
	}

	// Number of supplier proposals open (expired)
	if (isModEnabled('supplier_proposal')  && !getDolGlobalString('MAIN_DISABLE_BLOCK_SUPPLIER') && $user->hasRight('supplier_proposal', 'lire')) {
		$langs->load("supplier_proposal");
		include_once DOL_DOCUMENT_ROOT.'/supplier_proposal/class/supplier_proposal.class.php';
		$board = new SupplierProposal($db);
		$dashboardlines[$board->element.'_opened'] = $board->load_board($user, "opened");
		// Number of commercial proposals CLOSED signed (billed)
		$dashboardlines[$board->element.'_signed'] = $board->load_board($user, "signed");
	}

	// Number of sales orders
	if (isModEnabled('order')  && !getDolGlobalString('MAIN_DISABLE_BLOCK_CUSTOMER') && $user->hasRight('commande', 'lire')) {
		include_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
		$board = new Commande($db);
		// Number of customer orders to be shipped (validated and in progress)
		$dashboardlines[$board->element.'_toship'] = $board->load_board($user, 'toship');
		// Number of customer orders to be billed (not visible by default, does not match a lot of organization).
		if (getDolGlobalInt('ORDER_BILL_AFTER_VALIDATION')) {
			$dashboardlines[$board->element.'_tobill'] = $board->load_board($user, 'tobill');
		}
		// Number of customer orders to be billed (delivered but not billed)
		$dashboardlines[$board->element.'_shippedtobill'] = $board->load_board($user, 'shippedtobill');
	}

	// Number of suppliers orders
	if (isModEnabled('supplier_order')  && !getDolGlobalString('MAIN_DISABLE_BLOCK_SUPPLIER') && $user->hasRight('fournisseur', 'commande', 'lire')) {
		include_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
		$board = new CommandeFournisseur($db);
		$dashboardlines[$board->element.'_opened'] = $board->load_board($user, "opened");
		$dashboardlines[$board->element.'_awaiting'] = $board->load_board($user, 'awaiting');
	}

	// Number of contract / services enabled (delayed)
	if (isModEnabled('contract')  && !getDolGlobalString('MAIN_DISABLE_BLOCK_CONTRACT') && $user->hasRight('contrat', 'lire')) {
		include_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';
		$board = new Contrat($db);
		$dashboardlines[$board->element.'_inactive'] = $board->load_board($user, "inactive");
		// Number of active services (expired)
		$dashboardlines[$board->element.'_active'] = $board->load_board($user, "active");
	}

	// Number of tickets open
	if (isModEnabled('ticket')  && !getDolGlobalString('MAIN_DISABLE_BLOCK_TICKET') && $user->hasRight('ticket', 'read')) {
		include_once DOL_DOCUMENT_ROOT.'/ticket/class/ticket.class.php';
		$board = new Ticket($db);
		$dashboardlines[$board->element.'_opened'] = $board->load_board($user, "opened");
		// Number of active services (expired)
		//$dashboardlines[$board->element.'_active'] = $board->load_board($user, "active");
	}

	// Number of invoices customers (paid)
	if (isModEnabled('invoice') && !getDolGlobalString('MAIN_DISABLE_BLOCK_CUSTOMER') && $user->hasRight('facture', 'lire')) {
		include_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
		$board = new Facture($db);
		$dashboardlines[$board->element] = $board->load_board($user);
	}

	// Number of supplier invoices (paid)
	if (isModEnabled('supplier_invoice') && !getDolGlobalString('MAIN_DISABLE_BLOCK_SUPPLIER') && $user->hasRight('fournisseur', 'facture', 'lire')) {
		include_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
		$board = new FactureFournisseur($db);
		$dashboardlines[$board->element] = $board->load_board($user);
	}

	// Number of transactions to conciliate
	if (isModEnabled('bank')  && !getDolGlobalString('MAIN_DISABLE_BLOCK_BANK') && $user->hasRight('banque', 'lire') && !$user->socid) {
		include_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
		$board = new Account($db);
		$nb = $board->countAccountToReconcile(); // Get nb of account to reconciliate
		if ($nb > 0) {
			$dashboardlines[$board->element] = $board->load_board($user);
		}
	}


	// Number of cheque to send
	if (isModEnabled('bank')  && !getDolGlobalString('MAIN_DISABLE_BLOCK_BANK') && $user->hasRight('banque', 'lire') && !$user->socid) {
		if (!getDolGlobalString('BANK_DISABLE_CHECK_DEPOSIT')) {
			include_once DOL_DOCUMENT_ROOT . '/compta/paiement/cheque/class/remisecheque.class.php';
			$board = new RemiseCheque($db);
			$dashboardlines[$board->element] = $board->load_board($user);
		}
		if (isModEnabled('prelevement')) {
			include_once DOL_DOCUMENT_ROOT.'/compta/prelevement/class/bonprelevement.class.php';
			$board = new BonPrelevement($db);
			$dashboardlines[$board->element . '_direct_debit'] = $board->load_board($user, 'direct_debit');
		}
		if (isModEnabled('paymentbybanktransfer')) {
			include_once DOL_DOCUMENT_ROOT.'/compta/prelevement/class/bonprelevement.class.php';
			$board = new BonPrelevement($db);
			$dashboardlines[$board->element . '_credit_transfer'] = $board->load_board($user, 'credit_transfer');
		}
	}

	// Number of foundation members
	if (isModEnabled('member')  && !getDolGlobalString('MAIN_DISABLE_BLOCK_ADHERENT') && $user->hasRight('adherent', 'lire') && !$user->socid) {
		include_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
		$board = new Adherent($db);
		$dashboardlines[$board->element.'_shift'] = $board->load_board($user, 'shift');
		$dashboardlines[$board->element.'_expired'] = $board->load_board($user, 'expired');
	}

	// Number of expense reports to approve
	if (isModEnabled('expensereport')  && !getDolGlobalString('MAIN_DISABLE_BLOCK_EXPENSEREPORT') && $user->hasRight('expensereport', 'approve')) {
		include_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport.class.php';
		$board = new ExpenseReport($db);
		$dashboardlines[$board->element.'_toapprove'] = $board->load_board($user, 'toapprove');
	}

	// Number of expense reports to pay
	if (isModEnabled('expensereport')  && !getDolGlobalString('MAIN_DISABLE_BLOCK_EXPENSEREPORT') && $user->hasRight('expensereport', 'to_paid')) {
		include_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport.class.php';
		$board = new ExpenseReport($db);
		$dashboardlines[$board->element.'_topay'] = $board->load_board($user, 'topay');
	}

	// Number of holidays to approve
	if (isModEnabled('holiday')  && !getDolGlobalString('MAIN_DISABLE_BLOCK_HOLIDAY') && $user->hasRight('holiday', 'approve')) {
		include_once DOL_DOCUMENT_ROOT.'/holiday/class/holiday.class.php';
		$board = new Holiday($db);
		$dashboardlines[$board->element] = $board->load_board($user);
	}

	$object = new stdClass();
	$parameters = array();
	$action = '';
	$reshook = $hookmanager->executeHooks(
		'addOpenElementsDashboardLine',
		$parameters,
		$object,
		$action
	); // Note that $action and $object may have been modified by some hooks
	if ($reshook == 0) {
		$dashboardlines = array_merge($dashboardlines, $hookmanager->resArray);
	}

	/* Open object dashboard */
	$dashboardgroup = array(
		'action' =>
			array(
				'groupName' => 'Agenda',
				'stats' => array('action'),
			),
		'project' =>
			array(
				'groupName' => 'Projects',
				'globalStatsKey' => 'projects',
				'stats' => array('project', 'project_task'),
			),
		'propal' =>
			array(
				'groupName' => 'Proposals',
				'globalStatsKey' => 'proposals',
				'stats' =>
					array('propal_opened', 'propal_signed'),
			),
		'commande' =>
			array(
				'groupName' => 'Orders',
				'globalStatsKey' => 'orders',
				'stats' =>
					array('commande_toship', 'commande_tobill', 'commande_shippedtobill'),
			),
		'facture' =>
			array(
				'groupName' => 'Invoices',
				'globalStatsKey' => 'invoices',
				'stats' =>
					array('facture'),
			),
		'supplier_proposal' =>
			array(
				'lang' => 'supplier_proposal',
				'groupName' => 'SupplierProposals',
				'globalStatsKey' => 'askprice',
				'stats' =>
					array('supplier_proposal_opened', 'supplier_proposal_signed'),
			),
		'order_supplier' =>
			array(
				'groupName' => 'SuppliersOrders',
				'globalStatsKey' => 'supplier_orders',
				'stats' =>
					array('order_supplier_opened', 'order_supplier_awaiting'),
			),
		'invoice_supplier' =>
			array(
				'groupName' => 'BillsSuppliers',
				'globalStatsKey' => 'supplier_invoices',
				'stats' =>
					array('invoice_supplier'),
			),
		'contrat' =>
			array(
				'groupName' => 'Contracts',
				'globalStatsKey' => 'Contracts',
				'stats' =>
				array('contrat_inactive', 'contrat_active'),
			),
		'ticket' =>
			array(
				'groupName' => 'Tickets',
				'globalStatsKey' => 'ticket',
				'stats' =>
					array('ticket_opened'),
			),
		'bank_account' =>
			array(
				'groupName' => 'BankAccount',
				'stats' =>
					array('bank_account', 'chequereceipt', 'widthdraw_direct_debit', 'widthdraw_credit_transfer'),
			),
		'member' =>
			array(
				'groupName' => 'Members',
				'globalStatsKey' => 'members',
				'stats' =>
					array('member_shift', 'member_expired'),
			),
		'expensereport' =>
			array(
				'groupName' => 'ExpenseReport',
				'globalStatsKey' => 'expensereports',
				'stats' =>
					array('expensereport_toapprove', 'expensereport_topay'),
			),
		'holiday' =>
			array(
				'groupName' => 'Holidays',
				'globalStatsKey' => 'holidays',
				'stats' =>
					array('holiday'),
			),
	);

	$object = new stdClass();
	$parameters = array(
		'dashboardgroup' => $dashboardgroup
	);
	$reshook = $hookmanager->executeHooks('addOpenElementsDashboardGroup', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
	if ($reshook == 0) {
		$dashboardgroup = array_merge($dashboardgroup, $hookmanager->resArray);
	}


	// Calculate total nb of late
	$totallate = $totaltodo = 0;

	//Remove any invalid response
	//load_board can return an integer if failed, or WorkboardResponse if OK
	$valid_dashboardlines = array();
	foreach ($dashboardlines as $workboardid => $tmp) {
		if ($tmp instanceof WorkboardResponse) {
			$tmp->id = $workboardid; // Complete the object to add its id into its name
			$valid_dashboardlines[$workboardid] = $tmp;
		}
	}

	// We calculate $totallate. Must be defined before start of next loop because it is show in first fetch on next loop
	foreach ($valid_dashboardlines as $board) {
		if (is_numeric($board->nbtodo) && is_numeric($board->nbtodolate) && $board->nbtodolate > 0) {
			$totaltodo += $board->nbtodo;
			$totallate += $board->nbtodolate;
		}
	}

	$openedDashBoardSize = 'info-box-sm'; // use sm by default
	foreach ($dashboardgroup as $dashbordelement) {
		if (is_array($dashbordelement['stats']) && count($dashbordelement['stats']) > 2) {
			$openedDashBoardSize = ''; // use default info box size : big
			break;
		}
	}

	$totalLateNumber = $totallate;
	$totallatePercentage = ((!empty($totaltodo)) ? round($totallate / $totaltodo * 100, 2) : 0);
	if (getDolGlobalString('MAIN_USE_METEO_WITH_PERCENTAGE')) {
		$totallate = $totallatePercentage;
	}

	// Fill the content to show the tasks to do as a widget box (old version). Now this is no more used. Tasks to do ar in dedicated thumbs.
	$boxwork = '';
	$boxwork .= '<div class="box">';
	$boxwork .= '<table summary="'.dol_escape_htmltag($langs->trans("WorkingBoard")).'" class="noborder boxtable boxtablenobottom boxworkingboard centpercent">'."\n";
	$boxwork .= '<tr class="liste_titre">';
	$boxwork .= '<th class="liste_titre"><div class="inline-block valignmiddle">'.$langs->trans("DolibarrWorkBoard").'</div>';
	if ($showweather) {
		if ($totallate > 0) {
			$text = $langs->transnoentitiesnoconv("WarningYouHaveAtLeastOneTaskLate").' ('.$langs->transnoentitiesnoconv(
				"NActionsLate",
				$totallate.(getDolGlobalString('MAIN_USE_METEO_WITH_PERCENTAGE') ? '%' : '')
			).')';
		} else {
			$text = $langs->transnoentitiesnoconv("NoItemLate");
		}
		$text .= '. '.$langs->transnoentitiesnoconv("LateDesc");
		//$text.=$form->textwithpicto('',$langs->trans("LateDesc"));
		$options = 'height="24px" style="float: right"';
		$boxwork .= showWeather($totallate, $text, $options, 'inline-block valignmiddle');
	}
	$boxwork .= '</th>';
	$boxwork .= '</tr>'."\n";

	// Show dashboard
	$nbworkboardempty = 0;
	$isIntopOpenedDashBoard = $globalStatInTopOpenedDashBoard = array();
	if (!empty($valid_dashboardlines)) {
		$openedDashBoard = '';

		$boxwork .= '<tr class="nobottom nohover"><td class="tdboxstats nohover flexcontainer centpercent"><div style="display: flex: flex-wrap: wrap">';

		foreach ($dashboardgroup as $groupKey => $groupElement) {
			$boards = array();

			// Scan $groupElement and save the one with 'stats' that must be used for the open objects dashboard
			if (!getDolGlobalString('MAIN_DISABLE_NEW_OPENED_DASH_BOARD')) {
				foreach ($groupElement['stats'] as $infoKey) {
					if (!empty($valid_dashboardlines[$infoKey])) {
						$boards[] = $valid_dashboardlines[$infoKey];
						$isIntopOpenedDashBoard[] = $infoKey;
					}
				}
			}

			if (!empty($boards)) {
				if (!empty($groupElement['lang'])) {
					$langs->load($groupElement['lang']);
				}
				// @phan-suppress-next-line PhanTypePossiblyInvalidDimOffset
				$groupName = $langs->trans($groupElement['groupName']);
				$groupKeyLowerCase = strtolower($groupKey);

				// global stats
				$globalStatsKey = false;
				if (!empty($groupElement['globalStatsKey']) && empty($groupElement['globalStats'])) { // can be filled by hook
					$globalStatsKey = $groupElement['globalStatsKey'];
					$groupElement['globalStats'] = array();
				}

				$openedDashBoard .= '<div class="box-flex-item"><div class="box-flex-item-with-margin">'."\n";
				$openedDashBoard .= '	<div class="info-box '.$openedDashBoardSize.'">'."\n";
				$openedDashBoard .= '		<span class="info-box-icon bg-infobox-'.$groupKeyLowerCase.'">'."\n";
				$openedDashBoard .= '		<i class="fa fa-dol-'.$groupKeyLowerCase.'"></i>'."\n";

				// Show the span for the total of record. TODO This seems not used.
				if (!empty($groupElement['globalStats'])) {
					$globalStatInTopOpenedDashBoard[] = $globalStatsKey;
					$openedDashBoard .= '<span class="info-box-icon-text" title="'.$groupElement['globalStats']['text'].'">'.$groupElement['globalStats']['nbTotal'].'</span>';
				}

				$openedDashBoard .= '</span>'."\n";
				$openedDashBoard .= '<div class="info-box-content">'."\n";

				$openedDashBoard .= '<div class="info-box-title" title="'.strip_tags($groupName).'">'.$groupName.'</div>'."\n";
				$openedDashBoard .= '<div class="info-box-lines">'."\n";

				foreach ($boards as $board) {
					$openedDashBoard .= '<div class="info-box-line spanoverflow nowrap">';

					if (!empty($board->labelShort)) {
						$infoName = '<div class="marginrightonly inline-block valignmiddle info-box-line-text" title="'.$board->label.'">'.$board->labelShort.'</div>';
					} else {
						$infoName = '<div class="marginrightonly inline-block valignmiddle info-box-line-text">'.$board->label.'</div>';
					}

					$textLateTitle = $langs->trans("NActionsLate", $board->nbtodolate);
					$textLateTitle .= ' ('.$langs->trans("Late").' = '.$langs->trans("DateReference").' > '.$langs->trans("DateToday").' '.(ceil(empty($board->warning_delay) ? 0 : $board->warning_delay) >= 0 ? '+' : '').ceil(empty($board->warning_delay) ? 0 : $board->warning_delay).' '.$langs->trans("days").')';

					if ($board->id == 'bank_account') {
						$textLateTitle .= '<br><span class="opacitymedium">'.$langs->trans("IfYouDontReconcileDisableProperty", $langs->transnoentitiesnoconv("Conciliable")).'</span>';
					}

					$textLate = '';
					if ($board->nbtodolate > 0) {
						$textLate .= '<span title="'.dol_escape_htmltag($textLateTitle).'" class="classfortooltip badge badge-warning">';
						$textLate .= '<i class="fa fa-exclamation-triangle"></i> '.$board->nbtodolate;
						$textLate .= '</span>';
					}

					$nbtodClass = '';
					if ($board->nbtodo > 0) {
						$nbtodClass = 'badge badge-info';
					} else {
						$nbtodClass = 'opacitymedium';
					}

					// Forge the line to show into the open object box
					$labeltoshow = $board->label.' ('.$board->nbtodo.')';
					if ($board->total > 0) {
						$labeltoshow .= ' - '.price($board->total, 0, $langs, 1, -1, -1, $conf->currency);
					}
					$openedDashBoard .= '<a href="'.$board->url.'" class="info-box-text info-box-text-a">';
					$openedDashBoard .= $infoName;
					$openedDashBoard .= '<div class="inline-block nowraponall">';
					$openedDashBoard .= '<span class="classfortooltip'.($nbtodClass ? ' '.$nbtodClass : '').'" title="'.$labeltoshow.'">';
					$openedDashBoard .= $board->nbtodo;
					if ($board->total > 0 && getDolGlobalString('MAIN_WORKBOARD_SHOW_TOTAL_WO_TAX')) {
						$openedDashBoard .= ' : '.price($board->total, 0, $langs, 1, -1, -1, $conf->currency);
					}
					$openedDashBoard .= '</span>';

					if ($textLate) {
						if ($board->url_late) {
							$openedDashBoard .= '</div></a>';
							$openedDashBoard .= ' <div class="inline-block"><a href="'.$board->url_late.'" class="info-box-text info-box-text-a paddingleft">';
						} else {
							$openedDashBoard .= ' ';
						}
						$openedDashBoard .= $textLate;
					}
					$openedDashBoard .= '</a>'."\n";
					$openedDashBoard .= '</div>';
					$openedDashBoard .= '</div>'."\n";
				}

				// TODO Add hook here to add more "info-box-line"

				$openedDashBoard .= '		</div><!-- /.info-box-lines --></div><!-- /.info-box-content -->'."\n";
				$openedDashBoard .= '	</div><!-- /.info-box -->'."\n";
				$openedDashBoard .= '</div><!-- /.box-flex-item-with-margin -->'."\n";
				$openedDashBoard .= '</div><!-- /.box-flex-item -->'."\n";
				$openedDashBoard .= "\n";
			}
		}

		if ($showweather && !empty($isIntopOpenedDashBoard)) {
			$appendClass = (getDolGlobalInt('MAIN_DISABLE_METEO') == 2 ? ' hideonsmartphone' : '');
			$weather = getWeatherStatus($totallate);

			$text = '';
			if ($totallate > 0) {
				$text = $langs->transnoentitiesnoconv("WarningYouHaveAtLeastOneTaskLate").' ('.$langs->transnoentitiesnoconv(
					"NActionsLate",
					$totallate.(getDolGlobalString('MAIN_USE_METEO_WITH_PERCENTAGE') ? '%' : '')
				).')';
			} else {
				$text = $langs->transnoentitiesnoconv("NoItemLate");
			}
			$text .= '. '.$langs->transnoentitiesnoconv("LateDesc");

			$weatherDashBoard = '<div class="box-flex-item '.$appendClass.'"><div class="box-flex-item-with-margin">'."\n";
			$weatherDashBoard .= '	<div class="info-box '.$openedDashBoardSize.' info-box-weather info-box-weather-level'.$weather->level.'">'."\n";
			$weatherDashBoard .= '		<span class="info-box-icon">';
			$weatherDashBoard .= img_weather('', $weather->level, '', 0, 'valignmiddle width50');
			$weatherDashBoard .= '       </span>'."\n";
			$weatherDashBoard .= '		<div class="info-box-content">'."\n";
			$weatherDashBoard .= '			<div class="info-box-title">'.$langs->trans('GlobalOpenedElemView').'</div>'."\n";

			if ($totallatePercentage > 0 && getDolGlobalString('MAIN_USE_METEO_WITH_PERCENTAGE')) {
				$weatherDashBoard .= '			<span class="info-box-number">'.$langs->transnoentitiesnoconv(
					"NActionsLate",
					price($totallatePercentage).'%'
				).'</span>'."\n";
				$weatherDashBoard .= '			<span class="progress-description">'.$langs->trans(
					'NActionsLate',
					$totalLateNumber
				).'</span>'."\n";
			} else {
				$weatherDashBoard .= '			<span class="info-box-number">'.$langs->transnoentitiesnoconv(
					"NActionsLate",
					$totalLateNumber
				).'</span>'."\n";
				if ($totallatePercentage > 0) {
					$weatherDashBoard .= '			<span class="progress-description">'.$langs->trans(
						'NActionsLate',
						price($totallatePercentage).'%'
					).'</span>'."\n";
				}
			}

			$weatherDashBoard .= '		</div><!-- /.info-box-content -->'."\n";
			$weatherDashBoard .= '	</div><!-- /.info-box -->'."\n";
			$weatherDashBoard .= '</div><!-- /.box-flex-item-with-margin -->'."\n";
			$weatherDashBoard .= '</div><!-- /.box-flex-item -->'."\n";
			$weatherDashBoard .= "\n";

			$openedDashBoard = $weatherDashBoard.$openedDashBoard;
		}

		if (!empty($isIntopOpenedDashBoard)) {
			for ($i = 1; $i <= 10; $i++) {
				$openedDashBoard .= '<div class="box-flex-item filler"></div>';
			}
		}

		$nbworkboardcount = 0;
		foreach ($valid_dashboardlines as $infoKey => $board) {
			if (in_array($infoKey, $isIntopOpenedDashBoard)) {
				// skip if info is present on top
				continue;
			}

			if (empty($board->nbtodo)) {
				$nbworkboardempty++;
			}
			$nbworkboardcount++;


			$textlate = $langs->trans("NActionsLate", $board->nbtodolate);
			$textlate .= ' ('.$langs->trans("Late").' = '.$langs->trans("DateReference").' > '.$langs->trans("DateToday").' '.(ceil($board->warning_delay) >= 0 ? '+' : '').ceil($board->warning_delay).' '.$langs->trans("days").')';


			$boxwork .= '<div class="boxstatsindicator thumbstat150 nobold nounderline"><div class="boxstats130 boxstatsborder">';
			$boxwork .= '<div class="boxstatscontent">';
			$boxwork .= '<span class="boxstatstext" title="'.dol_escape_htmltag($board->label).'">'.$board->img.' <span>'.$board->label.'</span></span><br>';
			$boxwork .= '<a class="valignmiddle dashboardlineindicator" href="'.$board->url.'"><span class="dashboardlineindicator'.(($board->nbtodo == 0) ? ' dashboardlineok' : '').'">'.$board->nbtodo.'</span></a>';
			if ($board->total > 0 && getDolGlobalString('MAIN_WORKBOARD_SHOW_TOTAL_WO_TAX')) {
				$boxwork .= '&nbsp;/&nbsp;<a class="valignmiddle dashboardlineindicator" href="'.$board->url.'"><span class="dashboardlineindicator'.(($board->nbtodo == 0) ? ' dashboardlineok' : '').'">'.price($board->total).'</span></a>';
			}
			$boxwork .= '</div>';
			if ($board->nbtodolate > 0) {
				$boxwork .= '<div class="dashboardlinelatecoin nowrap">';
				$boxwork .= '<a title="'.dol_escape_htmltag($textlate).'" class="valignmiddle dashboardlineindicatorlate'.($board->nbtodolate > 0 ? ' dashboardlineko' : ' dashboardlineok').'" href="'.((!$board->url_late) ? $board->url : $board->url_late).'">';
				//$boxwork .= img_picto($textlate, "warning_white", 'class="valigntextbottom"');
				$boxwork .= img_picto(
					$textlate,
					"warning_white",
					'class="inline-block hideonsmartphone valigntextbottom"'
				);
				$boxwork .= '<span class="dashboardlineindicatorlate'.($board->nbtodolate > 0 ? ' dashboardlineko' : ' dashboardlineok').'">';
				$boxwork .= $board->nbtodolate;
				$boxwork .= '</span>';
				$boxwork .= '</a>';
				$boxwork .= '</div>';
			}
			$boxwork .= '</div></div>';
			$boxwork .= "\n";
		}

		$boxwork .= '<div class="boxstatsindicator thumbstat150 nobold nounderline"><div class="boxstats150empty"></div></div>';
		$boxwork .= '<div class="boxstatsindicator thumbstat150 nobold nounderline"><div class="boxstats150empty"></div></div>';
		$boxwork .= '<div class="boxstatsindicator thumbstat150 nobold nounderline"><div class="boxstats150empty"></div></div>';
		$boxwork .= '<div class="boxstatsindicator thumbstat150 nobold nounderline"><div class="boxstats150empty"></div></div>';

		$boxwork .= '</div>';
		$boxwork .= '</td></tr>';
	} else {
		$boxwork .= '<tr class="nohover">';
		$boxwork .= '<td class="nohover valignmiddle opacitymedium">';
		$boxwork .= $langs->trans("NoOpenedElementToProcess");
		$boxwork .= '</td>';
		$boxwork .= '</tr>';
	}

	$boxwork .= '</td></tr>';

	$boxwork .= '</table>'; // End table array of working board
	$boxwork .= '</div>';

	if (!empty($isIntopOpenedDashBoard)) {
		print '<div class="fichecenter">';
		print '<div class="opened-dash-board-wrap"><div class="box-flex-container">'.$openedDashBoard.'</div></div>';
		print '</div>';
	}
}


print '<div class="clearboth"></div>';

print '<div class="fichecenter fichecenterbis">';


/*
 * Show widgets (boxes)
 */

$boxlist = '<div class="twocolumns">';

$boxlist .= '<div class="firstcolumn fichehalfleft boxhalfleft" id="boxhalfleft">';
if (!empty($nbworkboardcount)) {
	$boxlist .= $boxwork;
}

$boxlist .= $resultboxes['boxlista'];

$boxlist .= '</div>';

$boxlist .= '<div class="secondcolumn fichehalfright boxhalfright" id="boxhalfright">';

$boxlist .= $resultboxes['boxlistb'];

$boxlist .= '</div>';
$boxlist .= "\n";

$boxlist .= '</div>';


print $boxlist;

print '</div>';

//print 'mem='.memory_get_usage().' - '.memory_get_peak_usage();

// End of page
llxFooter();
$db->close();


/**
 *  Show weather logo. Logo to show depends on $totallate and values for
 *  conf 'MAIN_METEO_LEVELx'
 *
 *  @param      int     $totallate      Nb of element late
 *  @param      string  $text           Text to show on logo
 *  @param      string  $options        More parameters on img tag
 *  @param      string  $morecss        More CSS
 *  @return     string                  Return img tag of weather
 */
function showWeather($totallate, $text, $options, $morecss = '')
{
	global $conf;

	$weather = getWeatherStatus($totallate);
	return img_weather($text, $weather->picto, $options, 0, $morecss);
}


/**
 *  get weather status for conf 'MAIN_METEO_LEVELx'
 *
 *  @param      int     $totallate      Nb of element late
 *  @return     stdClass                Return img tag of weather
 */
function getWeatherStatus($totallate)
{
	$weather = new stdClass();
	$weather->picto = '';

	$offset = 0;
	$factor = 10; // By default

	$used_conf = (getDolGlobalString('MAIN_USE_METEO_WITH_PERCENTAGE') ? 'MAIN_METEO_PERCENTAGE_LEVEL' : 'MAIN_METEO_LEVEL');

	$weather->level = 0;
	$level0 = $offset;
	$level0 = getDolGlobalString($used_conf.'0', $level0);
	$level1 = $offset + $factor;
	$level1 = getDolGlobalString($used_conf.'1', $level1);
	$level2 = $offset + 2 * $factor;
	$level2 = getDolGlobalString($used_conf.'2', $level2);
	$level3 = $offset + 3 * $factor;
	$level3 = getDolGlobalString($used_conf.'3', $level3);

	if ($totallate <= $level0) {
		$weather->picto = 'weather-clear.png';
		$weather->level = 0;
	} elseif ($totallate <= $level1) {
		$weather->picto = 'weather-few-clouds.png';
		$weather->level = 1;
	} elseif ($totallate <= $level2) {
		$weather->picto = 'weather-clouds.png';
		$weather->level = 2;
	} elseif ($totallate <= $level3) {
		$weather->picto = 'weather-many-clouds.png';
		$weather->level = 3;
	} else {
		$weather->picto = 'weather-storm.png';
		$weather->level = 4;
	}

	return $weather;
}
