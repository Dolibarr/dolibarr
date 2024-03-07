<?php
/* Copyright (C) 2016-2020  Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2016-2023  Alexandre Spangaro		<aspangaro@easya.solutions>
 * Copyright (C) 2019-2021  Frédéric France			<frederic.france@netlogic.fr>
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
 * \file    htdocs/accountancy/index.php
 * \ingroup Accountancy (Double entries)
 * \brief   Home accounting module
 */


// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/accounting.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';

// Load translation files required by the page
$langs->loadLangs(array("compta", "bills", "other", "accountancy", "loans", "banks", "admin", "dict"));

// Initialize technical object to manage hooks. Note that conf->hooks_modules contains array of hooks
$hookmanager->initHooks(array('accountancyindex'));

// Security check
if ($user->socid > 0) {
	accessforbidden();
}
if (!isModEnabled('comptabilite') && !isModEnabled('accounting') && !isModEnabled('asset') && !isModEnabled('intracommreport')) {
	accessforbidden();
}
if (!$user->hasRight('compta', 'resultat', 'lire') && !$user->hasRight('accounting', 'comptarapport', 'lire') && !$user->hasRight('accounting', 'mouvements', 'lire') && !$user->hasRight('asset', 'read') && !$user->hasRight('intracommreport', 'read')) {
	accessforbidden();
}

$pcgver = getDolGlobalInt('CHARTOFACCOUNTS');


/*
 * Actions
 */

if (GETPOST('addbox')) {
	// Add box (when submit is done from a form when ajax disabled)
	require_once DOL_DOCUMENT_ROOT.'/core/class/infobox.class.php';
	$zone = GETPOST('areacode', 'int');
	$userid = GETPOST('userid', 'int');
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

$help_url = 'EN:Module_Double_Entry_Accounting#Setup|FR:Module_Comptabilit&eacute;_en_Partie_Double#Configuration';

llxHeader('', $langs->trans("AccountancyArea"), $help_url);

if (isModEnabled('accounting')) {
	$step = 0;

	$resultboxes = FormOther::getBoxesArea($user, "27"); // Load $resultboxes (selectboxlist + boxactivated + boxlista + boxlistb)

	$helpisexpanded = empty($resultboxes['boxactivated']) || (empty($resultboxes['boxlista']) && empty($resultboxes['boxlistb'])); // If there is no widget, the tooltip help is expanded by default.
	$showtutorial = '';

	if (!$helpisexpanded) {
		$showtutorial  = '<div class="right"><a href="#" id="show_hide">';
		$showtutorial .= img_picto('', 'chevron-down');
		$showtutorial .= ' '.$langs->trans("ShowTutorial");
		$showtutorial .= '</a></div>';

		$showtutorial .= '<script type="text/javascript">
	    jQuery(document).ready(function() {
	        jQuery("#show_hide").click(function () {
	            jQuery( "#idfaq" ).toggle({
	                duration: 400,
	            });
	        });
	    });
	    </script>';
	}

	print load_fiche_titre($langs->trans("AccountancyArea"), $resultboxes['selectboxlist'], 'accountancy', 0, '', '', $showtutorial);

	if (getDolGlobalInt('INVOICE_USE_SITUATION') == 1) {
		print info_admin($langs->trans("SorryThisModuleIsNotCompatibleWithTheExperimentalFeatureOfSituationInvoices"));
		print "<br>";
	}

	print '<div class="'.($helpisexpanded ? '' : 'hideobject').'" id="idfaq">'; // hideobject is to start hidden
	print "<br>\n";
	print '<span class="opacitymedium">'.$langs->trans("AccountancyAreaDescIntro")."</span><br>\n";
	if ($user->hasRight('accounting', 'chartofaccount')) {
		print '<br>';
		print load_fiche_titre('<span class="fa fa-calendar"></span> '.$langs->trans("AccountancyAreaDescActionOnce"), '', '')."\n";
		print '<hr>';
		print "<br>\n";

		// STEPS
		$step++;
		$s = img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescJournalSetup", $step, '{s}');
		$s = str_replace('{s}', '<a href="'.DOL_URL_ROOT.'/accountancy/admin/journals_list.php?id=35" target="setupaccountancy"><strong>'.$langs->transnoentitiesnoconv("Setup").' - '.$langs->transnoentitiesnoconv("AccountingJournals").'</strong></a>', $s);
		print $s;
		print "<br>\n";
		$step++;
		$s = img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescChartModel", $step, '{s}');
		$s = str_replace('{s}', '<a href="'.DOL_URL_ROOT.'/accountancy/admin/accountmodel.php?search_country_id='.$mysoc->country_id.'" target="setupaccountancy"><strong>'.$langs->transnoentitiesnoconv("Setup").' - '.$langs->transnoentitiesnoconv("Pcg_version").'</strong></a>', $s);
		print $s;
		print "<br>\n";
		$step++;
		$s = img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescChart", $step, '{s}');
		$s = str_replace('{s}', '<a href="'.DOL_URL_ROOT.'/accountancy/admin/account.php" target="setupaccountancy"><strong>'.$langs->transnoentitiesnoconv("Setup").' - '.$langs->transnoentitiesnoconv("Chartofaccounts").'</strong></a>', $s);
		print $s;
		if ($pcgver > 0) {
			$pcgversion = '';
			$pcglabel = '';

			$sql = "SELECT a.rowid, a.pcg_version, a.label, a.active";
			$sql .= " FROM ".MAIN_DB_PREFIX."accounting_system as a";
			$sql .= " WHERE a.rowid = ".((int) $pcgver);

			$resqlchart = $db->query($sql);
			if ($resqlchart) {
				$obj = $db->fetch_object($resqlchart);
				if ($obj) {
					$pcgversion = $obj->pcg_version;
					$pcglabel = $obj->label;
				}
			} else {
				dol_print_error($db);
			}
			print ' <span class="opacitymedium">('.$langs->trans("CurrentChartOfAccount").': '.$pcgversion.')</span>';
		}
		print "<br>\n";

		print "<br>\n";
		print $langs->trans("AccountancyAreaDescActionOnceBis");
		print "<br>\n";
		print "<br>\n";

		if (getDolGlobalString('ACCOUNTANCY_FISCAL_PERIOD_MODE') != 'blockedonclosed') {
			$step++;
			$s = img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescFiscalPeriod", $step, '{s}');
			$s = str_replace('{s}', '<a href="'.DOL_URL_ROOT.'/accountancy/admin/fiscalyear.php" target="setupaccountancy"><strong>'.$langs->transnoentitiesnoconv("Setup").' - '.$langs->transnoentitiesnoconv("FiscalPeriod").'</strong></a>', $s);
			print $s;
			print "<br>\n";
		}

		$step++;
		$s = img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescDefault", $step, '{s}');
		$s = str_replace('{s}', '<a href="'.DOL_URL_ROOT.'/accountancy/admin/defaultaccounts.php" target="setupaccountancy"><strong>'.$langs->transnoentitiesnoconv("Setup").' - '.$langs->transnoentitiesnoconv("MenuDefaultAccounts").'</strong></a>', $s);
		print $s;
		print "<br>\n";

		$step++;
		$s = img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescBank", $step, '{s}')."\n";
		$s = str_replace('{s}', '<a href="'.DOL_URL_ROOT.'/compta/bank/list.php" target="setupaccountancy"><strong>'.$langs->transnoentitiesnoconv("Setup").' - '.$langs->transnoentitiesnoconv("MenuBankAccounts").'</strong></a>', $s);
		print $s;
		print "<br>\n";

		$step++;
		$textlink = '<a href="'.DOL_URL_ROOT.'/admin/dict.php?id=10&from=accountancy" target="setupaccountancy"><strong>'.$langs->transnoentitiesnoconv("Setup").' - '.$langs->transnoentitiesnoconv("MenuVatAccounts").'</strong></a>';
		$s = img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescVat", $step, '{s}');
		$s = str_replace('{s}', $textlink, $s);
		print $s;
		print "<br>\n";

		if (isModEnabled('tax')) {
			$textlink = '<a href="'.DOL_URL_ROOT.'/admin/dict.php?id=7&from=accountancy" target="setupaccountancy"><strong>'.$langs->transnoentitiesnoconv("Setup").' - '.$langs->transnoentitiesnoconv("MenuTaxAccounts").'</strong></a>';
			$step++;
			$s = img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescContrib", $step, '{s}');
			$s = str_replace('{s}', $textlink, $s);
			print $s;
			print "<br>\n";
		}
		if (isModEnabled('expensereport')) {  // TODO Move this in the default account page because this is only one accounting account per purpose, not several.
			$step++;
			$s = img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescExpenseReport", $step, '{s}');
			$s = str_replace('{s}', '<a href="'.DOL_URL_ROOT.'/admin/dict.php?id=17&from=accountancy"><strong>'.$langs->transnoentitiesnoconv("Setup").' - '.$langs->transnoentitiesnoconv("MenuExpenseReportAccounts").'</strong></a>', $s);
			print $s;
			print "<br>\n";
		}

		$step++;
		$s = img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescProd", $step, '{s}');
		$s = str_replace('{s}', '<a href="'.DOL_URL_ROOT.'/accountancy/admin/productaccount.php" target="setupaccountancy"><strong>'.$langs->transnoentitiesnoconv("Setup").' - '.$langs->transnoentitiesnoconv("ProductsBinding").'</strong></a>', $s);
		print $s;
		print "<br>\n";

		print '<br>';
	}

	// Step A - E

	print "<br>\n";
	print load_fiche_titre('<span class="fa fa-calendar"></span> '.$langs->trans("AccountancyAreaDescActionFreq"), '', '');
	print '<hr>';
	print "<br>\n";
	$step = 0;

	$langs->loadLangs(array('bills', 'trips'));

	$step++;
	$s = img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescBind", chr(64 + $step), $langs->transnoentitiesnoconv("BillsCustomers"), '{s}')."\n";
	$s = str_replace('{s}', '<a href="'.DOL_URL_ROOT.'/accountancy/customer/index.php" target="setupaccountancy"><strong>'.$langs->transnoentitiesnoconv("TransferInAccounting").' - '.$langs->transnoentitiesnoconv("CustomersVentilation").'</strong></a>', $s);
	print $s;
	print "<br>\n";

	$step++;
	$s = img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescBind", chr(64 + $step), $langs->transnoentitiesnoconv("BillsSuppliers"), '{s}')."\n";
	$s = str_replace('{s}', '<a href="'.DOL_URL_ROOT.'/accountancy/supplier/index.php" target="setupaccountancy"><strong>'.$langs->transnoentitiesnoconv("TransferInAccounting").' - '.$langs->transnoentitiesnoconv("SuppliersVentilation").'</strong></a>', $s);
	print $s;
	print "<br>\n";

	if (isModEnabled('expensereport') || isModEnabled('deplacement')) {
		$step++;
		$s = img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescBind", chr(64 + $step), $langs->transnoentitiesnoconv("ExpenseReports"), '{s}')."\n";
		$s = str_replace('{s}', '<a href="'.DOL_URL_ROOT.'/accountancy/expensereport/index.php" target="setupaccountancy"><strong>'.$langs->transnoentitiesnoconv("TransferInAccounting").' - '.$langs->transnoentitiesnoconv("ExpenseReportsVentilation").'</strong></a>', $s);
		print $s;
		print "<br>\n";
	}

	$step++;
	$s = img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescWriteRecords", chr(64 + $step), $langs->transnoentitiesnoconv("TransferInAccounting").' - '.$langs->transnoentitiesnoconv("RegistrationInAccounting"), $langs->transnoentitiesnoconv("WriteBookKeeping"))."\n";
	print $s;
	print "<br>\n";

	$step++;
	$s = img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescAnalyze", chr(64 + $step))."<br>\n";
	print $s;

	$step++;
	$s = img_picto('', 'puce').' '.$langs->trans("AccountancyAreaDescClosePeriod", chr(64 + $step))."<br>\n";
	print $s;

	print "<br>\n";


	print '<br>';

	print '</div>';

	print '<div class="clearboth"></div>';

	print '<div class="fichecenter fichecenterbis">';

	/*
	 * Show boxes
	 */
	$boxlist = '<div class="twocolumns">';

	$boxlist .= '<div class="firstcolumn fichehalfleft boxhalfleft" id="boxhalfleft">';

	$boxlist .= $resultboxes['boxlista'];

	$boxlist .= '</div>';

	$boxlist .= '<div class="secondcolumn fichehalfright boxhalfright" id="boxhalfright">';

	$boxlist .= $resultboxes['boxlistb'];

	$boxlist .= '</div>';
	$boxlist .= "\n";

	$boxlist .= '</div>';


	print $boxlist;

	print '</div>';
} elseif (isModEnabled('comptabilite')) {
	print load_fiche_titre($langs->trans("AccountancyArea"), '', 'accountancy');

	print '<span class="opacitymedium">'.$langs->trans("Module10Desc")."</span>\n";
	print "<br>";
} else {
	// This case can happen mode no accounting module is on but module "intracommreport" is on
	print load_fiche_titre($langs->trans("AccountancyArea"), '', 'accountancy');
}

// End of page
llxFooter();
$db->close();
