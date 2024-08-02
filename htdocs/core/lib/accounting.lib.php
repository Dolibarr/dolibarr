<?php
/* Copyright (C) 2013-2014 Olivier Geffroy      <jeff@jeffinfo.com>
 * Copyright (C) 2013-2021 Alexandre Spangaro   <aspangaro@open-dsi.fr>
 * Copyright (C) 2014      Florian Henry        <florian.henry@open-concept.pro>
 * Copyright (C) 2019      Eric Seigne          <eric.seigne@cap-rel.fr>
 * Copyright (C) 2021-2024 Frédéric France      <frederic.france@netlogic.fr>
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
 * 	\file		htdocs/core/lib/accounting.lib.php
 * 	\ingroup	Accountancy (Double entries)
 * 	\brief		Library of accountancy functions
 */


/**
 *	Check if a value is empty with some options
 *
 * @author	Michael - https://www.php.net/manual/fr/function.empty.php#90767
 * @param	?mixed		$var			Value to test
 * @param	boolean     $allow_false 	Setting this to true will make the function consider a boolean value of false as NOT empty. This parameter is false by default.
 * @param	boolean     $allow_ws 		Setting this to true will make the function consider a string with nothing but white space as NOT empty. This parameter is false by default.
 * @return	boolean				  		True of False
 */
function is_empty($var, $allow_false = false, $allow_ws = false)
{
	if (is_null($var) || !isset($var) || ($allow_ws == false && trim($var) == "" && !is_bool($var)) || ($allow_false === false && $var === false) || (is_array($var) && empty($var))) {
		return true;
	}
	return false;
}

/**
 *	Prepare array with list of tabs
 *
 *	@param	AccountingAccount	$object		Accounting account
 *	@return	array				Array of tabs to show
 */
function accounting_prepare_head(AccountingAccount $object)
{
	global $langs, $conf;

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/accountancy/admin/card.php?id='.$object->id;
	$head[$h][1] = $langs->trans("AccountAccounting");
	$head[$h][2] = 'card';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__'); to add new tab
	// $this->tabs = array('entity:-tabname); to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'accounting_account');

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'accounting_account', 'remove');

	return $head;
}

/**
 * Return accounting account without zero on the right
 *
 * @param 	string	$account		Accounting account
 * @return	string          		String without zero on the right
 */
function clean_account($account)
{
	$account = rtrim($account, "0");

	return $account;
}

/**
 * Return General accounting account with defined length (used for product and miscellaneous)
 *
 * @param 	string	$account		General accounting account
 * @return	string          		String with defined length
 */
function length_accountg($account)
{
	global $conf;

	if ($account < 0 || is_empty($account)) {
		return '';
	}

	if (getDolGlobalString('ACCOUNTING_MANAGE_ZERO')) {
		return $account;
	}

	$g = getDolGlobalInt('ACCOUNTING_LENGTH_GACCOUNT');
	if (!is_empty($g)) {
		// Clean parameters
		$i = strlen($account);

		if ($i >= 1) {
			while ($i < $g) {
				$account .= '0';

				$i++;
			}

			return $account;
		} else {
			return $account;
		}
	} else {
		return $account;
	}
}

/**
 * Return Auxiliary accounting account of thirdparties with defined length
 *
 * @param 	string	$accounta		Auxiliary accounting account
 * @return	string          		String with defined length
 */
function length_accounta($accounta)
{
	global $conf;

	if ($accounta < 0 || is_empty($accounta)) {
		return '';
	}

	if (getDolGlobalString('ACCOUNTING_MANAGE_ZERO')) {
		return $accounta;
	}

	$a = getDolGlobalInt('ACCOUNTING_LENGTH_AACCOUNT');
	if (!is_empty($a)) {
		// Clean parameters
		$i = strlen($accounta);

		if ($i >= 1) {
			while ($i < $a) {
				$accounta .= '0';

				$i++;
			}

			return $accounta;
		} else {
			return $accounta;
		}
	} else {
		return $accounta;
	}
}



/**
 *	Show header of a page used to transfer/dispatch data in accounting
 *
 *	@param	string				$nom            Name of report
 *	@param 	string				$variant        Link for alternate report
 *	@param 	string				$period         Period of report
 *	@param 	string				$periodlink     Link to switch period
 *	@param 	string				$description    Description
 *	@param 	integer	            $builddate      Date of generation
 *	@param 	string				$exportlink     Link for export or ''
 *	@param	array				$moreparam		Array with list of params to add into hidden fields of form
 *	@param	string				$calcmode		Calculation mode
 *  @param  string              $varlink        Add a variable into the address of the page
 *	@param	array				$moreoptions	Array with list of params to add to table
 *	@return	void
 */
function journalHead($nom, $variant, $period, $periodlink, $description, $builddate, $exportlink = '', $moreparam = array(), $calcmode = '', $varlink = '', $moreoptions = array())
{
	global $langs;

	print "\n\n<!-- start banner journal -->\n";

	if (!is_empty($varlink)) {
		$varlink = '?'.$varlink;
	}

	$head = array();
	$h = 0;
	$head[$h][0] = $_SERVER["PHP_SELF"].$varlink;
	$head[$h][1] = $langs->trans("Journalization");
	$head[$h][2] = 'journal';

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].$varlink.'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';

	print dol_get_fiche_head($head, 'journal');

	foreach ($moreparam as $key => $value) {
		print '<input type="hidden" name="'.$key.'" value="'.$value.'">';
	}
	print '<table class="border centpercent tableforfield">';

	// Ligne de titre
	print '<tr>';
	print '<td class="titlefieldcreate">'.$langs->trans("Name").'</td>';
	print '<td colspan="3">';
	print $nom;
	print '</td>';
	print '</tr>';

	// Calculation mode
	if ($calcmode) {
		print '<tr>';
		print '<td>'.$langs->trans("CalculationMode").'</td>';
		if (!$variant) {
			print '<td colspan="3">';
		} else {
			print '<td>';
		}
		print $calcmode;
		if ($variant) {
			print '</td><td colspan="2">'.$variant;
		}
		print '</td>';
		print '</tr>';
	}

	// Ligne de la periode d'analyse du rapport
	print '<tr>';
	print '<td>'.$langs->trans("ReportPeriod").'</td>';
	if (!$periodlink) {
		print '<td colspan="3">';
	} else {
		print '<td>';
	}
	if ($period) {
		print $period;
	}
	if ($periodlink) {
		print '</td><td colspan="2">'.$periodlink;
	}
	print '</td>';
	print '</tr>';

	// Ligne de description
	print '<tr>';
	print '<td>'.$langs->trans("ReportDescription").'</td>';
	print '<td colspan="3">'.$description.'</td>';
	print '</tr>';


	// more options
	foreach ($moreoptions as $key => $value) {
		print '<tr>';
		print '<td>'.$langs->trans($key).'</td>';
		print '<td colspan="3">'.$value.'</td>';
		print '</tr>';
	}

	print '</table>';

	print dol_get_fiche_end();

	print '<div class="center"><input type="submit" class="button" name="submit" value="'.$langs->trans("Refresh").'"></div>';

	print '</form>';

	print "\n<!-- end banner journal -->\n\n";
}

/**
 * Return Default dates for transfer based on periodicity option in accountancy setup
 *
 * @return	array		Dates of periodicity by default
 */
function getDefaultDatesForTransfer()
{
	global $db, $conf;

	$date_start = '';
	$date_end = '';
	$pastmonth = 0;
	$pastmonthyear = 0;

	// Period by default on transfer (0: previous month | 1: current month | 2: fiscal year)
	$periodbydefaultontransfer = getDolGlobalInt('ACCOUNTING_DEFAULT_PERIOD_ON_TRANSFER', 0);
	if ($periodbydefaultontransfer == 2) {	// fiscal year
		$sql = "SELECT date_start, date_end FROM ".MAIN_DB_PREFIX."accounting_fiscalyear";
		$sql .= " WHERE date_start < '".$db->idate(dol_now())."' AND date_end > '".$db->idate(dol_now())."'";
		$sql .= $db->plimit(1);
		$res = $db->query($sql);
		if ($res->num_rows > 0) {
			$obj = $db->fetch_object($res);

			$date_start = $db->jdate($obj->date_start);
			$date_end = $db->jdate($obj->date_end);
		} else {
			$month_start = getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1);
			$year_start = dol_print_date(dol_now(), '%Y');
			if ($month_start > dol_print_date(dol_now(), '%m')) {
				$year_start = $year_start - 1;
			}
			$year_end = $year_start + 1;
			$month_end = $month_start - 1;
			if ($month_end < 1) {
				$month_end = 12;
				$year_end--;
			}
			$date_start = dol_mktime(0, 0, 0, $month_start, 1, $year_start);
			$date_end = dol_get_last_day($year_end, $month_end);
		}
	} elseif ($periodbydefaultontransfer == 1) {	// current month
		$year_current = (int) dol_print_date(dol_now('gmt'), "%Y", 'gmt');
		$pastmonth = (int) dol_print_date(dol_now('gmt'), '%m', 'gmt');
		$pastmonthyear = $year_current;
		if ($pastmonth == 0) {
			$pastmonth = 12;
			$pastmonthyear--;
		}
	} else {	// previous month
		$year_current = (int) dol_print_date(dol_now('gmt'), "%Y", 'gmt');
		$pastmonth = (int) dol_print_date(dol_now('gmt'), '%m', 'gmt') - 1;
		$pastmonthyear = $year_current;
		if ($pastmonth == 0) {
			$pastmonth = 12;
			$pastmonthyear--;
		}
	}

	return array(
		'date_start' => $date_start,
		'date_end' => $date_end,
		'pastmonthyear' => $pastmonthyear,
		'pastmonth' => $pastmonth
	);
}

/**
 * Get current period of fiscal year
 *
 * @param 	DoliDB		$db				Database handler
 * @param 	stdClass	$conf			Config
 * @param 	int 		$from_time		[=null] Get current time or set time to find fiscal period
 * @return 	array		Period of fiscal year : [date_start, date_end]
 */
function getCurrentPeriodOfFiscalYear($db, $conf, $from_time = null)
{
	$now = dol_now();
	$now_arr = dol_getdate($now);
	if ($from_time === null) {
		$from_time = $now;
	}
	$from_db_time = $db->idate($from_time);

	$sql  = "SELECT date_start, date_end FROM ".$db->prefix()."accounting_fiscalyear";
	$sql .= " WHERE date_start <= '".$db->escape($from_db_time)."' AND date_end >= '".$db->escape($from_db_time)."'";
	$sql .= $db->order('date_start', 'DESC');
	$sql .= $db->plimit(1);
	$res = $db->query($sql);
	if ($db->num_rows($res) > 0) {
		$obj = $db->fetch_object($res);

		$date_start = $db->jdate($obj->date_start);
		$date_end = $db->jdate($obj->date_end);
	} else {
		$month_start = 1;
		$conf_fiscal_month_start = (int) $conf->global->SOCIETE_FISCAL_MONTH_START;
		if ($conf_fiscal_month_start >= 1 && $conf_fiscal_month_start <= 12) {
			$month_start = $conf_fiscal_month_start;
		}
		$year_start = $now_arr['year'];
		if ($conf_fiscal_month_start > $now_arr['mon']) {
			$year_start = $year_start - 1;
		}
		$year_end = $year_start + 1;
		$month_end = $month_start - 1;
		if ($month_end < 1) {
			$month_end = 12;
			$year_end--;
		}
		$date_start = dol_mktime(0, 0, 0, $month_start, 1, $year_start);
		$date_end = dol_get_last_day($year_end, $month_end);
	}

	return array(
		'date_start' => $date_start,
		'date_end' => $date_end,
	);
}
