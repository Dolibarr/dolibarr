<?php
/* Copyright (C) 2014-2016	Alexandre Spangaro	<aspangaro@open-dsi.fr>
 * Copyright (C) 2015-2024  Frédéric France     <frederic.france@free.fr>
 * Copyright (C) 2020       Maxime DEMAREST     <maxime@indelog.fr>
 * Copyright (C) 2024-2025	MDW					<mdeweerd@users.noreply.github.com>
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
 *      \file       htdocs/core/lib/loan.lib.php
 *      \ingroup    loan
 *      \brief      Library for loan module
 */


/**
 * Prepare array with list of tabs
 *
 * @param   Loan	$object		Object related to tabs
 * @return	array<array{0:string,1:string,2:string}>	Array of tabs to show
 */
function loan_prepare_head($object)
{
	global $db, $langs, $conf;

	$tab = 0;
	$head = array();

	$head[$tab][0] = DOL_URL_ROOT.'/loan/card.php?id='.$object->id;
	$head[$tab][1] = $langs->trans('Card');
	$head[$tab][2] = 'card';
	$tab++;

	$head[$tab][0] = DOL_URL_ROOT.'/loan/schedule.php?loanid='.$object->id;
	$head[$tab][1] = $langs->trans('FinancialCommitment');
	$head[$tab][2] = 'FinancialCommitment';
	$tab++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname);   												to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $tab, 'loan', 'add', 'core');

	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
	$upload_dir = $conf->loan->dir_output."/".dol_sanitizeFileName($object->ref);
	$nbFiles = count(dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$'));
	$nbLinks = Link::count($db, $object->element, $object->id);
	$head[$tab][0] = DOL_URL_ROOT.'/loan/document.php?id='.$object->id;
	$head[$tab][1] = $langs->trans("Documents");
	if (($nbFiles + $nbLinks) > 0) {
		$head[$tab][1] .= '<span class="badge marginleftonlyshort">'.($nbFiles + $nbLinks).'</span>';
	}
	$head[$tab][2] = 'documents';
	$tab++;

	if (!getDolGlobalString('MAIN_DISABLE_NOTES_TAB')) {
		$nbNote = (empty($object->note_private) ? 0 : 1) + (empty($object->note_public) ? 0 : 1);
		$head[$tab][0] = DOL_URL_ROOT."/loan/note.php?id=".$object->id;
		$head[$tab][1] = $langs->trans("Notes");
		if ($nbNote > 0) {
			$head[$tab][1] .= '<span class="badge marginleftonlyshort">'.$nbNote.'</span>';
		}
		$head[$tab][2] = 'note';
		$tab++;
	}

	$head[$tab][0] = DOL_URL_ROOT.'/loan/info.php?id='.$object->id;
	$head[$tab][1] = $langs->trans("Info");
	$head[$tab][2] = 'info';
	$tab++;

	complete_head_from_modules($conf, $langs, $object, $head, $tab, 'loan', 'add', 'external');

	complete_head_from_modules($conf, $langs, $object, $head, $tab, 'loan', 'remove');

	return $head;
}

/**
 * Calculate remaining loan mensuality and interests
 *
 * @param   float   $mens				Value of this mensuality (interests include, set 0 if we don't paid interests for this mensuality)
 * @param   float   $capital    		Remaining capital for this mensuality
 * @param   float   $rate				Loan rate
 * @param   int     $numactualloadterm	Actual loan term
 * @param   int   	$nbterm  			Total number of term for this loan
 * @param   null|float|string $amort	Capital amortization for this term (optional)
 * @param   string  $source				Source of modification ('mens', 'amort' or 'interet')
 * @param   int     $grace_period		Number of terms with grace period (amortization = 0)
 * @param   null|float|string $int		Interest amount for this term (optional)
 * @param	int		$frequency				Number of payments per year (52, 26, 12, 4, 2 or 1)
 * @param	int		$interest_basis			0 = rate / number of payments per year, 1 = daily (rate x days in the period / 365)
 * @param	int		$datestart				Date of the first payment (needed for the daily basis)
 * @return array<array{cap_rest:float,cap_rest_str:string,interet:float,interet_str:string,amort:string,amort_num:float,mens:string,mens_num:float}>		Array with remaining capital, interest, amortization and mensuality for each remaining terms
 */
function loanCalcMonthlyPayment($mens, $capital, $rate, $numactualloadterm, $nbterm, $amort = null, $source = 'mens', $grace_period = 0, $int = null, $frequency = 12, $interest_basis = 0, $datestart = 0)
{
	global $conf, $db;
	require_once DOL_DOCUMENT_ROOT.'/loan/class/loanschedule.class.php';
	$object = new LoanSchedule($db);
	$output = array();

	// Sanitize data in case of
	$mens = (float) price2num($mens);
	$capital = (float) price2num($capital);
	$rate = (float) price2num($rate);
	$numactualloadterm = ((int) $numactualloadterm);
	$nbterm = ((int) $nbterm);
	$grace_period = ((int) $grace_period);
	$amort = ($amort !== null && $amort !== '') ? (float) price2num($amort) : null;
	$int = ($int !== null && $int !== '') ? (float) price2num($int) : null;
	$frequency = ((int) $frequency > 0 ? (int) $frequency : 12);
	$interest_basis = (int) $interest_basis;
	$datestart = (int) $datestart;
	// Rate of the period that ends with a given term (rate / 12 for a monthly loan with interest per period)
	$periodrate = function ($term) use ($rate, $frequency, $interest_basis, $datestart) {
		return loanPeriodRate((float) $rate, $frequency, $interest_basis, ($interest_basis && $datestart ? loanPeriodDays($datestart, $term, $frequency) : 0));
	};

	if ($grace_period > 0 && $numactualloadterm <= $grace_period) {
		$amort = 0.0;
		$int = ($int !== null && $int > 0) ? $int : round((float) $capital * $periodrate($numactualloadterm), 2, PHP_ROUND_HALF_UP);
		$mens = $int;
		$cap_rest = $capital;
	} elseif ($source === 'amort') {
		$amort = ($amort !== null) ? $amort : 0.0;
		$int = ($int !== null && $int > 0) ? $int : round((float) $capital * $periodrate($numactualloadterm), 2, PHP_ROUND_HALF_UP);
		$mens = round((float) $amort + (float) $int, 2, PHP_ROUND_HALF_UP);
		$cap_rest = round((float) $capital - (float) $amort, 2, PHP_ROUND_HALF_UP);
	} elseif ($source === 'interet') {
		$int = ($int !== null) ? $int : round((float) $capital * $periodrate($numactualloadterm), 2, PHP_ROUND_HALF_UP);
		$amort = ($amort !== null) ? $amort : 0.0;
		$mens = round((float) $amort + (float) $int, 2, PHP_ROUND_HALF_UP);
		$cap_rest = round((float) $capital - (float) $amort, 2, PHP_ROUND_HALF_UP);
	} else {
		// source === 'mens'
		if ($amort !== null && $amort == 0) {
			// Zero capital amortization: payment is interest/fees and capital stays intact
			$amort = 0.0;
			$int = $mens;
			$cap_rest = $capital;
		} else {
			$int = ($int !== null && $int > 0) ? $int : round((float) $capital * $periodrate($numactualloadterm), 2, PHP_ROUND_HALF_UP);
			$amort = round((float) $mens - (float) $int, 2, PHP_ROUND_HALF_UP);
			if ($amort < 0) {
				$amort = 0.0;
				$int = $mens;
				$cap_rest = $capital;
			} else {
				$cap_rest = round((float) $capital - (float) $amort, 2, PHP_ROUND_HALF_UP);
			}
		}
	}

	$output[$numactualloadterm] = array(
		'cap_rest' => $cap_rest,
		'cap_rest_str' => price($cap_rest, 0, '', 1, -1, -1, $conf->currency),
		'interet' => $int,
		'interet_str' => price($int, 0, '', 1, -1, -1, $conf->currency),
		'amort' => price($amort),
		'amort_num' => (float) $amort,
		'mens' => price($mens),
		'mens_num' => (float) $mens,
	);

	$numactualloadterm++;
	$capital = $cap_rest;
	while ($numactualloadterm <= $nbterm) {
		if ($grace_period > 0 && $numactualloadterm <= $grace_period) {
			$amort = 0.0;
			$int = ((float) $capital * $periodrate($numactualloadterm));
			$int = round($int, 2, PHP_ROUND_HALF_UP);
			$mens = $int;
			$cap_rest = $capital;
		} else {
			$mens = round($object->calcMonthlyPayments($capital, (float) $rate, $nbterm - $numactualloadterm + 1, $frequency, $interest_basis), 2, PHP_ROUND_HALF_UP);

			$int = ($capital * $periodrate($numactualloadterm));
			$int = round($int, 2, PHP_ROUND_HALF_UP);
			$amort = round($mens - $int, 2, PHP_ROUND_HALF_UP);
			$cap_rest = round($capital - $amort, 2, PHP_ROUND_HALF_UP);

			// Adjust rounding difference on the last installment if small remainder (with daily interest,
			// periods are not all the same length, so the last installment always settles what is left)
			if ($numactualloadterm == $nbterm && ($interest_basis || abs($cap_rest) <= 0.05) && $capital > 0) {
				$amort = $capital;
				$cap_rest = 0.0;
				$mens = round($amort + $int, 2, PHP_ROUND_HALF_UP);
			}
		}

		$output[$numactualloadterm] = array(
			'cap_rest' => $cap_rest,
			'cap_rest_str' => price($cap_rest, 0, '', 1, -1, -1, $conf->currency),
			'interet' => $int,
			'interet_str' => price($int, 0, '', 1, -1, -1, $conf->currency),
			'amort' => price($amort),
			'amort_num' => (float) $amort,
			'mens' => price($mens),
			'mens_num' => (float) $mens,
		);
		$capital = $cap_rest;
		$numactualloadterm++;
	}

	return $output;
}


/**
 * Payment frequencies of a loan.
 *
 * @return array<int,string>	Number of payments per year => translation key
 */
function loanFrequencies()
{
	return array(
		52 => 'LoanFrequencyWeekly',
		26 => 'LoanFrequencyFortnightly',
		12 => 'LoanFrequencyMonthly',
		4 => 'LoanFrequencyQuarterly',
		2 => 'LoanFrequencyHalfYearly',
		1 => 'LoanFrequencyYearly',
	);
}

/**
 * Date of a payment of a loan.
 *
 * @param	int		$datestart	Date of the first payment
 * @param	int		$index		0 for the first payment, 1 for the second, ...
 * @param	int		$frequency	Number of payments per year (52, 26, 12, 4, 2 or 1)
 * @return	int					Date of the payment
 */
function loanTermDate($datestart, $index, $frequency = 12)
{
	require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

	$frequency = (int) $frequency;
	if ($frequency == 52) {
		return dol_time_plus_duree($datestart, 7 * $index, 'd');
	}
	if ($frequency == 26) {
		return dol_time_plus_duree($datestart, 14 * $index, 'd');
	}
	$months = (($frequency == 4 || $frequency == 2 || $frequency == 1) ? (int) (12 / $frequency) : 1) * $index;
	$date = dol_time_plus_duree($datestart, $months, 'm');
	// A start on the 29th to 31st must not run into the next month (31 January + 1 month = 28 February)
	$day = (int) dol_print_date($datestart, '%d');
	if ((int) dol_print_date($date, '%d') != $day) {
		$date = dol_time_plus_duree($date, -(int) dol_print_date($date, '%d'), 'd');
	}
	return $date;
}

/**
 * Number of days of the period that ends with a payment (used by the daily interest basis).
 *
 * @param	int		$datestart	Date of the first payment
 * @param	int		$term		Payment number, 1 for the first payment
 * @param	int		$frequency	Number of payments per year
 * @return	int					Number of days
 */
function loanPeriodDays($datestart, $term, $frequency = 12)
{
	return (int) round((loanTermDate($datestart, $term - 1, $frequency) - loanTermDate($datestart, $term - 2, $frequency)) / 86400);
}

/**
 * Interest rate of one period of a loan.
 *
 * @param	float	$rate			Annual rate (as a fraction, or in percent: the result is in the same unit)
 * @param	int		$frequency		Number of payments per year
 * @param	int		$interest_basis	0 = rate / number of payments per year, 1 = daily (rate x days in the period / 365)
 * @param	int		$days			Days in the period, for the daily basis (0 = the average period: 7 days weekly, 14 days fortnightly, else 365 / number of payments)
 * @return	float					Rate of the period
 */
function loanPeriodRate($rate, $frequency = 12, $interest_basis = 0, $days = 0)
{
	$frequency = ((int) $frequency > 0 ? (int) $frequency : 12);
	if ($interest_basis) {
		if ($days <= 0) {
			$days = ($frequency == 52 ? 7 : ($frequency == 26 ? 14 : 365 / $frequency));
		}
		return $rate * $days / 365;
	}
	return $rate / $frequency;
}
