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
 * @param	float	$balloon				Balloon / residual paid with the last payment (0 = none)
 * @return array<array{cap_rest:float,cap_rest_str:string,interet:float,interet_str:string,amort:string,amort_num:float,mens:string,mens_num:float}>		Array with remaining capital, interest, amortization and mensuality for each remaining terms
 */
function loanCalcMonthlyPayment($mens, $capital, $rate, $numactualloadterm, $nbterm, $amort = null, $source = 'mens', $grace_period = 0, $int = null, $frequency = 12, $interest_basis = 0, $datestart = 0, $balloon = 0)
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
	$periodrate = function (int $term) use ($rate, $frequency, $interest_basis, $datestart): float {
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
			$mens = round($object->calcMonthlyPayments($capital, (float) $rate, $nbterm - $numactualloadterm + 1, $frequency, $interest_basis, (float) $balloon), 2, PHP_ROUND_HALF_UP);

			$int = ($capital * $periodrate($numactualloadterm));
			$int = round($int, 2, PHP_ROUND_HALF_UP);
			$amort = round($mens - $int, 2, PHP_ROUND_HALF_UP);
			$cap_rest = round($capital - $amort, 2, PHP_ROUND_HALF_UP);

			// Adjust rounding difference on the last installment if small remainder (with daily interest,
			// periods are not all the same length, so the last installment always settles what is left)
			// A balloon is paid with the last installment, which then repays all the capital left.
			if ($numactualloadterm == $nbterm && ($interest_basis || (float) $balloon > 0 || abs($cap_rest) <= 0.05) && $capital > 0) {
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


/**
 * Kinds of charge the "insurance" amount of a loan can be.
 *
 * @return array<int,string>	charge_type => translation key
 */
function loanChargeTypes()
{
	return array(
		0 => 'Insurance',
		1 => 'LoanChargeAccountFee',
		2 => 'LoanChargeOtherFee',
	);
}

/**
 * Label of the charge of a loan (Insurance, Account-keeping fee, Other fee).
 *
 * @param	int			$charge_type	charge_type of the loan
 * @param	Translate	$outputlangs	Language
 * @return	string
 */
function loanChargeLabel($charge_type, $outputlangs)
{
	$types = loanChargeTypes();
	return $outputlangs->trans($types[(int) $charge_type] ?? $types[0]);
}

/**
 * Charge of each payment of a loan.
 *
 * @param	float	$amount				insurance_amount of the loan
 * @param	int		$per_payment		1 = the amount is due with every payment, 0 = it is a total spread over the payments
 * @param	float	$nbterm				Number of payments
 * @return	array{0:float,1:float}		Charge of each payment, rounding difference added to the first payment
 */
function loanChargePerPayment($amount, $per_payment, $nbterm)
{
	if ($per_payment) {
		return array((float) price2num($amount, 'MT'), 0.0);
	}
	if ((float) $nbterm <= 0) {
		return array(0.0, 0.0);
	}
	$each = (float) price2num((float) $amount / $nbterm, 'MT');
	return array($each, (float) price2num((float) $amount - ($each * $nbterm)));
}

/**
 * Recalculate the unpaid payments of a loan schedule, from one payment to the end, after a rate change or a
 * payment that differs from the schedule. Paid payments are never changed. The loan's number of payments
 * and end date are updated, and its rate when a new rate is given.
 *
 * @param	DoliDB			$db			Database handler
 * @param	User			$user		User making the change
 * @param	Loan			$loan		Loan (its rate is replaced by $rate)
 * @param	LoanSchedule[]	$lines		All the schedule lines of the loan, in date order
 * @param	int				$from		Index (0 = first line) of the first line to recalculate; it and all the following lines must be unpaid
 * @param	float			$capital	Capital left to repay before that line
 * @param	?float			$rate		Annual rate in percent for the recalculated lines (a rate change), or null to use, for each
 * 									payment, the rate in force on its date (see loanRateAt())
 * @param	string			$keep		'term' = keep the number of payments (the repayment changes), 'payment' = keep the repayment (the number of payments changes)
 * @param	float			$payment	With 'payment': repayment (capital + interest) for every recalculated payment, or 0 = each
 * 									payment keeps its current repayment (added payments: the last regular one)
 * @return	array{error:string,payment_old:float,payment_new:float,nbterm_old:int,nbterm_new:int}	'error' is a translation key, '' if OK
 */
function loanRecalculateSchedule($db, $user, $loan, $lines, $from, $capital, $rate, $keep, $payment = 0.0)
{
	require_once DOL_DOCUMENT_ROOT.'/loan/class/loanschedule.class.php';

	$lines = array_values($lines);
	$total = count($lines);
	$res = array('error' => '', 'payment_old' => 0.0, 'payment_new' => 0.0, 'nbterm_old' => $total, 'nbterm_new' => $total);
	if ($from < 0 || $from >= $total) {
		$res['error'] = 'LoanRecalcNothingToRecalculate';
		return $res;
	}
	for ($i = $from; $i < $total; $i++) {
		if (!empty($lines[$i]->fk_bank)) {
			$res['error'] = 'LoanRecalcLaterPaymentPaid';
			return $res;
		}
	}

	$frequency = ((int) $loan->frequency > 0 ? (int) $loan->frequency : 12);
	$interest_basis = (int) $loan->interest_basis;
	$balloon = (float) $loan->balloon_amount;
	$datestart = (int) $loan->datestart;
	$lastdate = (int) $lines[$total - 1]->datep;
	// Annual rate of a payment: the given one, or the one in force on the date of the payment
	$history = ($rate === null ? loanFetchChanges($db, $loan->id) : array());
	$rateof = function (int $index) use ($rate, $history, $loan, $lines, $total, $lastdate, $frequency): float {
		if ($rate !== null) {
			return (float) $rate;
		}
		$date = ($index < $total ? (int) $lines[$index]->datep : loanTermDate($lastdate, $index - ($total - 1), $frequency));
		return loanRateAt($history, (float) $loan->rate, $date);
	};
	// Rate of the period that ends with the payment of a given index (0 = first payment of the loan)
	$periodrate = function (int $index) use ($rateof, $frequency, $interest_basis, $datestart): float {
		return loanPeriodRate($rateof($index), $frequency, $interest_basis, ($interest_basis ? loanPeriodDays($datestart, $index + 1, $frequency) : 0)) / 100;
	};

	$res['payment_old'] = (float) price2num((float) $lines[$from]->amount_capital + (float) $lines[$from]->amount_interest, 'MT');
	$calc = new LoanSchedule($db);
	$new = array();	// index of the line => array(capital, interest)
	$cap = (float) $capital;

	if ($keep === 'payment') {
		// Repayment of each payment: the given one, else its current one; payments added after the last one get the
		// last regular repayment (the last payment itself is only what was left, or includes the balloon)
		$regular = (float) price2num((float) $lines[max(0, $total - 2)]->amount_capital + (float) $lines[max(0, $total - 2)]->amount_interest, 'MT');
		$i = $from;
		while ($cap > 0.005) {
			if ($payment > 0) {
				$pmt = (float) price2num($payment, 'MT');
			} elseif ($i < $total - 1) {
				$pmt = (float) price2num((float) $lines[$i]->amount_capital + (float) $lines[$i]->amount_interest, 'MT');
			} else {
				$pmt = $regular;
			}
			if ($i == $from) {
				$res['payment_new'] = $pmt;
			}
			if ($i - $from >= 1200) {
				$res['error'] = 'LoanRecalcPaymentTooSmall';
				return $res;
			}
			$int = (float) price2num($cap * $periodrate($i), 'MT');
			if ($pmt - $int <= 0) {
				$res['error'] = 'LoanRecalcPaymentTooSmall';
				return $res;
			}
			$amort = (float) price2num($pmt - $int, 'MT');
			if ($cap - $amort <= $balloon + 0.005) {
				$amort = $cap;	// last payment: all that is left (with the balloon if any)
			}
			$new[$i] = array($amort, $int);
			$cap = (float) price2num($cap - $amort, 'MT');
			$i++;
		}
	} else {
		$n = $total - $from;
		for ($k = 0; $k < $n; $k++) {
			$i = $from + $k;
			$mens = (float) price2num($calc->calcMonthlyPayments($cap, $rateof($i) / 100, $n - $k, $frequency, $interest_basis, $balloon), 'MT');
			$int = (float) price2num($cap * $periodrate($i), 'MT');
			$amort = (float) price2num($mens - $int, 'MT');
			if ($k == $n - 1) {
				$amort = $cap;	// last payment: all that is left (with the balloon if any)
			}
			$new[$i] = array($amort, $int);
			$cap = (float) price2num($cap - $amort, 'MT');
			if ($k == 0) {
				$res['payment_new'] = $mens;
			}
		}
	}

	// Write the lines: update existing ones, add the missing ones, delete the ones no longer needed
	list($charge, $chargeregul) = loanChargePerPayment($loan->insurance_amount, $loan->charge_per_payment, $loan->nbterm);
	$enddate = $lastdate;
	foreach ($new as $i => $amounts) {
		if ($i < $total) {
			$line = $lines[$i];
		} else {
			$line = new LoanSchedule($db);
			$line->fk_loan = $loan->id;
			$line->datec = dol_now();
			$line->datep = loanTermDate($lastdate, $i - ($total - 1), $frequency);
			$line->amount_insurance = $charge;
			$line->fk_typepayment = (int) $lines[$total - 1]->fk_typepayment;
			$line->fk_bank = 0;
			$line->fk_user_creat = $user->id;
		}
		$line->amount_capital = $amounts[0];
		$line->amount_interest = $amounts[1];
		$line->tms = dol_now();
		$line->fk_user_modif = $user->id;
		$result = ($i < $total ? $line->update($user, 0) : $line->create($user));
		if ($result < 0) {
			$res['error'] = ($line->error ? $line->error : 'Error');
			return $res;
		}
		$enddate = (int) $line->datep;
	}
	$nbnew = $from + count($new);
	for ($i = $nbnew; $i < $total; $i++) {
		if ($lines[$i]->delete($user) < 0) {
			$res['error'] = ($lines[$i]->error ? $lines[$i]->error : 'Error');
			return $res;
		}
	}

	if ($rate !== null) {
		$loan->rate = (float) $rate;
	}
	$loan->nbterm = $nbnew;
	$loan->dateend = $enddate;
	if ($loan->update($user) < 0) {
		$res['error'] = ($loan->error ? $loan->error : 'Error');
		return $res;
	}
	$res['nbterm_new'] = $nbnew;

	return $res;
}

/**
 * Record a change of a loan (rate change, or a payment that differs from the schedule) in its history.
 *
 * @param	DoliDB	$db				Database handler
 * @param	User	$user			User making the change
 * @param	int		$loanid			Loan id
 * @param	string	$reason			'rate' or 'payment'
 * @param	int		$datechange		Date from which the change applies
 * @param	float	$rate_old		Rate before (percent)
 * @param	float	$rate_new		Rate after (percent)
 * @param	string	$keep			'term', 'payment' or '' (no schedule recalculated)
 * @param	float	$payment_old	Repayment before
 * @param	float	$payment_new	Repayment after
 * @param	float	$nbterm_old		Number of payments before
 * @param	float	$nbterm_new		Number of payments after
 * @param	int		$fk_payment		Payment that caused the change (reason 'payment'), 0 if none
 * @return	int						<0 if KO, id of the record if OK
 */
function loanRecordChange($db, $user, $loanid, $reason, $datechange, $rate_old, $rate_new, $keep, $payment_old, $payment_new, $nbterm_old, $nbterm_new, $fk_payment = 0)
{
	global $conf;

	$sql = "INSERT INTO ".MAIN_DB_PREFIX."loan_change (entity, fk_loan, datec, date_change, reason, rate_old, rate_new, keep_mode, payment_old, payment_new, nbterm_old, nbterm_new, fk_payment_loan, fk_user_author)";
	$sql .= " VALUES (".((int) $conf->entity).", ".((int) $loanid).", '".$db->idate(dol_now())."', '".$db->idate($datechange)."',";
	$sql .= " '".$db->escape($reason)."', ".((float) $rate_old).", ".((float) $rate_new).", '".$db->escape($keep)."',";
	$sql .= " ".((float) price2num($payment_old, 'MT')).", ".((float) price2num($payment_new, 'MT')).", ".((float) $nbterm_old).", ".((float) $nbterm_new).",";
	$sql .= " ".($fk_payment > 0 ? ((int) $fk_payment) : "NULL").", ".((int) $user->id).")";
	if (!$db->query($sql)) {
		return -1;
	}
	return $db->last_insert_id(MAIN_DB_PREFIX."loan_change");
}

/**
 * History of the changes of a loan, most recent first.
 *
 * @param	DoliDB	$db		Database handler
 * @param	int		$loanid	Loan id
 * @return	array<int,stdClass>	Records of llx_loan_change (date_change and datec as timestamps)
 */
function loanFetchChanges($db, $loanid)
{
	$out = array();
	$sql = "SELECT rowid, datec, date_change, reason, rate_old, rate_new, keep_mode, payment_old, payment_new, nbterm_old, nbterm_new, fk_payment_loan, fk_user_author";
	$sql .= " FROM ".MAIN_DB_PREFIX."loan_change WHERE fk_loan = ".((int) $loanid)." ORDER BY datec DESC, rowid DESC";
	$resql = $db->query($sql);
	while ($resql && ($obj = $db->fetch_object($resql))) {
		$obj->datec = $db->jdate($obj->datec);
		$obj->date_change = $db->jdate($obj->date_change);
		$out[] = $obj;
	}
	return $out;
}

/**
 * Annual rate of a loan in force on a date, from the history of its rate changes: the rate of the most
 * recently recorded change that applies on or before the date; before the first change, the rate the
 * loan had before it; without any change, the current rate of the loan.
 *
 * @param	array<int,stdClass>	$history	From loanFetchChanges()
 * @param	float				$current	Current rate of the loan (percent)
 * @param	int					$date		Date
 * @return	float						Rate (percent)
 */
function loanRateAt($history, $current, $date)
{
	$found = null;
	$first = null;
	foreach ($history as $c) {	// most recent first
		if ($c->reason != 'rate') {
			continue;
		}
		$first = $c;
		if ($found === null && (int) $c->date_change <= (int) $date) {
			$found = $c;
		}
	}
	if ($found !== null) {
		return (float) $found->rate_new;
	}
	return ($first !== null ? (float) $first->rate_old : (float) $current);
}
