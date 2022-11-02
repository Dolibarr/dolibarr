<?php
/* Copyright (C) 2022   Dolibarr
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Data class for holding installment values (helps with static analysis + IDE can help the developer with context).
 * Since it is just a data class with no specific methods, I didn't put it in a separate class file.
 * There is no CRUD (the CRUD part is handled by loanschedule.class.php which is analog but more complex)
 */
class Installment {
	/**
	 * @var int $p  Period number (⚠ starts with 1)
	 */
	public $p;
	/**
	 * @var double $pmt payment (= $ipmt + $ppmt)
	 */
	public $pmt;
	/**
	 * @var double $ipmt interest on payment  = intérêt
	 */
	public $ipmt;
	/**
	 * @var double $ppmt principal payment    = amortissement
	 */
	public $ppmt;
	/**
	 * @var double $pv present value        = solde initial
	 */
	public $pv;
	/**
	 * @var double $fv future value         = capital restant dû = solde
	 */
	public $fv;

	/**
	 * @param int    $p    Period number
	 * @param double $pmt  Payment amount (pmt = ppmt + ipmt)
	 * @param double $ppmt Principal component of the payment
	 * @param double $ipmt Interest component of the payment
	 * @param double $pv   Present value = balance of the loan capital before this installment is paid
	 * @param double $fv   Future  value = balance of the loan capital after this installment is paid
	 */
	public function __construct($p = 1, $pmt = 0.0, $ppmt = 0.0, $ipmt = 0.0, $pv = 0.0, $fv = 0.0) {
		$this->p    = (int) $p;
		$this->pmt  = (double) $pmt;
		$this->ppmt = (double) $ppmt;
		$this->ipmt = (double) $ipmt;
		$this->pv   = (double) $pv;
		$this->fv   = (double) $fv;
	}
}

/**
 * TODO: ça pourrait être une méthode de Loan
 * Converts a yearly proportional interest rate in percentage into a periodic proportional interest rate
 *
 * @param double $yearlyRatePercent  Yearly interest rate expressed as a percentage (ex: 5, meaning 5% annually)
 * @param double $periodicity        Number of months between two payments (assuming interest is calculated at the same
 *                                   dates as the payment). 1 = monthly; 3 = quarterly; 12 = annually; etc.
 * @return double  Interest rate expressed as the rate applied at each period (required for pmt, ipmt, ppmt and fv)
 */
function getPeriodicRate($yearlyRatePercent, $periodicity = 1) {
	$yearlyRate = (double) $yearlyRatePercent / 100.0;
	return $yearlyRate * $periodicity / 12;
}

/**
 * Returns the computed amortization schedule (échéancier) for a loan (or for a part of a loan).
 * The payments will all have the same value (TODO: save for rounding error correction)
 * But each payment (pmt) can be broken down into its principal (ppmt) and interest (ipmt).
 *   pmt == ppmt + ipmt
 * Typically, the interest part is high in the first installments and low in the last installments.
 * Conversely, the principal part is low in the first insttallments and high in the last installments.
 *
 * @param double $rate           Periodic proportional rate
 * @param int    $periods        Number of periods
 * @param double $present_value  Present value
 * @param double $future_value   Desired future value at the END of the loan schedule (≠ future value for one period)
 * @param bool   $beginning      True if payment in advance, false for payment in arrear
 * @return Installment[]
 */
function computeAmortizationSchedule($rate, $periods, $present_value, $future_value = 0.0, $beginning = false) {
	/** @var Installment[] $schedule */
	$schedule = [];
	$balance = $present_value;

	// TODO: effectuer l'arrondi ici et avoir un accumulateur d'erreur d'arrondi permettant de compenser cette erreur
	//       et d'en répartir la correction comme c'était le cas avant (?)

	for ($period = 1; $period <= $periods; $period++) {
		$installment = new Installment($period);
		$installment->pv = $balance;
		$iPeriod = $period;
		if ($beginning) {
			// TODO: comprendre pourquoi ce décalage est nécessaire
			// décalage si on est au terme à échoir
			$iPeriod = $period + 1;
		}
		$installment->pmt = pmt($rate, $periods, -$present_value, $future_value, $beginning);
		$installment->ipmt = ipmt($rate, $iPeriod, $periods, -$present_value, $future_value, $beginning);
		$installment->ppmt = ppmt($rate, $iPeriod, $periods, -$present_value, $future_value, $beginning);
		if ($beginning && $period === $periods) {
			// TODO: comprendre pourquoi ce décalage est nécessaire
			// décalage: on finit à 0
			$installment->ipmt = 0;
			$installment->ppmt = $installment->pmt;
		}
		$installment->fv = $installment->pv - $installment->ppmt;

		$balance = $installment->fv; // the pv of the next installment will be this installment's fv
		$schedule[] = $installment;
	}
	return $schedule;
}

/**
 * @param $lines
 * @return void
 */
function loadScheduleLinesToInstallmentObjs($lines) {

}

/**
 * @param int|double $val
 * @param string $name
 * @param bool $editable
 * @param array|string $format  Array of arguments (starting with 2nd arg) for calls to price(); 'int' or 'amount' for
 *                              predefined format styles ('int' will not display decimals for instance)
 * @return string
 */
function getNumberSpan($val, $name = '', $editable = false, $format = 'amount') {
	if ($format === 'amount') {
		$format = array(0, '', 1, -1, 2, '');
	} elseif ($format === 'int') {
		$format = array(0, '', 0, 0, 0, '');
	}
	// TODO: dol_htmlentities
	$localizedVal = price($val, ...$format);
	if ($editable) {
		return '<input class="number" name="' . $name . '" value="' . $localizedVal . '" />';
	} elseif ($name) {
		return '<input readonly="" class="number" name="' . $name . '" value="' . $localizedVal . '" />';
	}
	return '<span class="number">' . $localizedVal. '</span>';
}

/**
 * @param Loan $loan
 * @param Installment $installment
 * @return string
 */
function getInstallmentTableRow($loan, $installment) {
	$installmentDate = $loan->getDateOfPeriod($installment->p-1); // TODO: why -1?
	$dataAttrs = '';
	$tdArray = [];
	foreach ($installment as $attrName => $value) {
		$dataAttrs .= ' data-' . $attrName . '="' . dol_htmlentities($value) . '"';
		$format = 'amount';
		$editable = false;
		$name = 'installment[' . $installment->p . '][' . $attrName . ']';
		if ($attrName === 'p') { $format = 'int'; $name = ''; }
		if ($attrName === 'pmt') $editable = true;
		$tdArray[$attrName] = '<td class="' . $attrName . '">' . getNumberSpan($value, $name, $editable, $format) . '</td>';
	}
	$tdArray['date'] = '<td class="date">' . dol_print_date($installmentDate, 'day') . '</td>';
	$tdArray['insurance'] = '<td class="insurance">' . getNumberSpan($loan->insurance_amount / $loan->nbPeriods) . '</td>';
	$tr = '<tr class="installment" ' . $dataAttrs . '>'
		. $tdArray['p']
		. $tdArray['date']
		. $tdArray['insurance']
		. $tdArray['ppmt']
		. $tdArray['ipmt']
		. $tdArray['pmt']
		. $tdArray['fv']
		. '<td class="payment"></td>'
		. '</tr>';
	return $tr;
}


/// ————————————————————————— functions taken from MathPHP —————————————————————————
///  ##############################  MathPHP license: ##############################
//   MIT License
//
//   Copyright(c) 2016 Mark Rogoyski
//
//   Permission is hereby granted, free of charge, to any person obtaining a copy
//   of this software and associated documentation files(the 'Software'), to deal
//   in the Software without restriction, including without limitation the rights
//   to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
//   copies of the Software, and to permit persons to whom the Software is
//   furnished to do so, subject to the following conditions:
//
//   The above copyright notice and this permission notice shall be included in all
//   copies or substantial portions of the Software .
//
//   THE SOFTWARE IS PROVIDED 'AS IS', WITHOUT WARRANTY OF ANY KIND, EXPRESS or
//   IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
//   FITNESS for A PARTICULAR PURPOSE and NONINFRINGEMENT . IN NO EVENT SHALL THE
//   AUTHORS or COPYRIGHT HOLDERS BE LIABLE for ANY CLAIM, DAMAGES or OTHER
//   LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT or OTHERWISE, ARISING FROM,
//   OUT OF or IN CONNECTION WITH THE SOFTWARE or THE use or OTHER DEALINGS IN THE
//   SOFTWARE .

const EPSILON = 1e-6;

    /**
     * Financial payment for a loan or annuity with compound interest.
     * Determines the periodic payment amount for a given interest rate,
     * principal, targeted payment goal, life of the annuity as number
     * of payments, and whether the payments are made at the start or end
     * of each payment period.
     *
     * Same as the =PMT() function in most spreadsheet software.
     *
     * The basic monthly payment formula derivation:
     * https://en.wikipedia.org/wiki/Mortgage_calculator#Monthly_payment_formula
     *
     *       rP(1+r)ᴺ
     * PMT = --------
     *       (1+r)ᴺ-1
     *
     * The formula is adjusted to allow targeting any future value rather than 0.
     * The 1/(1+r*when) factor adjusts the payment to the beginning or end
     * of the period. In the common case of a payment at the end of a period,
     * the factor is 1 and reduces to the formula above. Setting when=1 computes
     * an "annuity due" with an immediate payment.
     *
     * Examples:
     * The payment on a 30-year fixed mortgage note of $265000 at 3.5% interest
     * paid at the end of every month.
     *   pmt(0.035/12, 30*12, 265000, 0, false)
     *
     * The payment on a 30-year fixed mortgage note of $265000 at 3.5% interest
     * needed to half the principal in half in 5 years:
     *   pmt(0.035/12, 5*12, 265000, 265000/2, false)
     *
     * The weekly payment into a savings account with 1% interest rate and current
     * balance of $1500 needed to reach $10000 after 3 years:
     *   pmt(0.01/52, 3*52, -1500, 10000, false)
     * The present_value is negative indicating money put into the savings account,
     * whereas future_value is positive, indicating money that will be withdrawn from
     * the account. Similarly, the payment value is negative
     *
     * How much money can be withdrawn at the end of every quarter from an account
     * with $1000000 earning 4% so the money lasts 20 years:
     *  pmt(0.04/4, 20*4, 1000000, 0, false)
     *
     * @param  float $rate
     * @param  int   $periods
     * @param  float $present_value
     * @param  float $future_value
     * @param  bool  $beginning adjust the payment to the beginning or end of the period
     *
     * @return float
     */
function pmt(float $rate, int $periods, float $present_value, float $future_value = 0.0, bool $beginning = false): float
{
	$when = $beginning ? 1 : 0;

	if ($rate == 0) {
		return - ($future_value + $present_value) / $periods;
	}

	return - ($future_value + ($present_value * pow(1 + $rate, $periods)))
		/
		((1 + $rate * $when) / $rate * (pow(1 + $rate, $periods) - 1));
}

/**
 * Interest on a financial payment for a loan or annuity with compound interest.
 * Determines the interest payment at a particular period of the annuity. For
 * a typical loan paid down to zero, the amount of interest and principal paid
 * throughout the lifetime of the loan will change, with the interest portion
 * of the payment decreasing over time as the loan principal decreases.
 *
 * Same as the =IPMT() function in most spreadsheet software.
 *
 * See the PMT function for derivation of the formula. For IPMT, we have
 * the payment equal to the interest portion and principal portion of the payment:
 *
 * PMT = IPMT + PPMT
 *
 * The interest portion IPMT on a regular annuity can be calculated by computing
 * the future value of the annuity for the prior period and computing the compound
 * interest for one period:
 *
 * IPMT = FV(p=n-1) * rate
 *
 * For an "annuity due" where payment is at the start of the period, period=1 has
 * no interest portion of the payment because no time has elapsed for compounding.
 * To compute the interest portion of the payment, the future value of 2 periods
 * back needs to be computed, as the definition of a period is different, giving:
 *
 * IPMT = (FV(p=n-2) - PMT) * rate
 *
 * By thinking of the future value at period 0 instead of the present value, the
 * given formulas are computed.
 *
 * Example of regular annuity and annuity due for a loan of $10.00 paid back in 3 periods.
 * Although the principal payments are equal, the total payment and interest portion are
 * lower with the annuity due because a principal payment is made immediately.
 *
 *                       Regular Annuity |  Annuity Due
 * Period   FV       PMT    IPMT   PPMT  |   PMT    IPMT    PPMT
 *   0     -10.00                        |
 *   1      -6.83   -3.67  -0.50  -3.17  |  -3.50   0.00   -3.50
 *   2      -3.50   -3.67  -0.34  -3.33  |  -3.50  -0.33   -3.17
 *   3       0.00   -3.67  -0.17  -3.50  |  -3.50  -0.17   -3.33
 *                -----------------------|----------------------
 *             SUM -11.01  -1.01 -10.00  | -10.50  -0.50  -10.00
 *
 * Examples:
 * The interest on a payment on a 30-year fixed mortgage note of $265000 at 3.5% interest
 * paid at the end of every month, looking at the first payment:
 *   ipmt(0.035/12, 1, 30*12, 265000, 0, false)
 *
 * @param  float $rate
 * @param  int   $period
 * @param  int   $periods
 * @param  float $present_value
 * @param  float $future_value
 * @param  bool  $beginning adjust the payment to the beginning or end of the period
 *
 * @return float
 */
function ipmt(float $rate, int $period, int $periods, float $present_value, float $future_value = 0.0, bool $beginning = false): float
{
	if ($period < 1 || $period > $periods) {
		return \NAN;
	}

	if ($rate == 0) {
		return 0;
	}

	if ($beginning && $period == 1) {
		return 0.0;
	}

	$payment = pmt($rate, $periods, $present_value, $future_value, $beginning);
	if ($beginning) {
		$interest = (fv($rate, $period - 2, $payment, $present_value, $beginning) - $payment) * $rate;
	} else {
		$interest = fv($rate, $period - 1, $payment, $present_value, $beginning) * $rate;
	}

	return checkZero($interest);
}

/**
 * Principal on a financial payment for a loan or annuity with compound interest.
 * Determines the principal payment at a particular period of the annuity. For
 * a typical loan paid down to zero, the amount of interest and principal paid
 * throughout the lifetime of the loan will change, with the principal portion
 * of the payment increasing over time as the loan principal decreases.
 *
 * Same as the =PPMT() function in most spreadsheet software.
 *
 * See the PMT function for derivation of the formula.
 * See the IPMT function for derivation and use of PMT, IPMT, and PPMT.
 *
 * With derivations for PMT and IPMT, we simply compute:
 *
 * PPMT = PMT - IPMT
 *
 * Examples:
 * The principal on a payment on a 30-year fixed mortgage note of $265000 at 3.5% interest
 * paid at the end of every month, looking at the first payment:
 *   ppmt(0.035/12, 1, 30*12, 265000, 0, false)
 *
 * @param  float $rate
 * @param  int   $period
 * @param  int   $periods
 * @param  float $present_value
 * @param  float $future_value
 * @param  bool  $beginning adjust the payment to the beginning or end of the period
 *
 * @return float
 */
function ppmt(float $rate, int $period, int $periods, float $present_value, float $future_value = 0.0, bool $beginning = false): float
{
	$payment = pmt($rate, $periods, $present_value, $future_value, $beginning);
	$ipmt    = ipmt($rate, $period, $periods, $present_value, $future_value, $beginning);

	return $payment - $ipmt;
}

/**
 * Future value for a loan or annuity with compound interest.
 *
 * Same as the =FV() function in most spreadsheet software.
 *
 * The basic future-value formula derivation:
 * https://en.wikipedia.org/wiki/Future_value
 *
 *                   PMT*((1+r)ᴺ - 1)
 * FV = -PV*(1+r)ᴺ - ----------------
 *                          r
 *
 * The (1+r*when) factor adjusts the payment to the beginning or end
 * of the period. In the common case of a payment at the end of a period,
 * the factor is 1 and reduces to the formula above. Setting when=1 computes
 * an "annuity due" with an immediate payment.
 *
 * Examples:
 * The future value in 5 years on a 30-year fixed mortgage note of $265000
 * at 3.5% interest paid at the end of every month. This is how much loan
 * principal would be outstanding:
 *   fv(0.035/12, 5*12, 1189.97, -265000, false)
 *
 * The present_value is negative indicating money borrowed for the mortgage,
 * whereas payment is positive, indicating money that will be paid to the
 * mortgage.
 *
 * @param  float $rate
 * @param  int   $periods
 * @param  float $payment
 * @param  float $present_value
 * @param  bool  $beginning adjust the payment to the beginning or end of the period
 *
 * @return float
 */
function fv(float $rate, int $periods, float $payment, float $present_value, bool $beginning = false): float
{
	$when = $beginning ? 1 : 0;

	if ($rate == 0) {
		$fv = -($present_value + ($payment * $periods));
		return checkZero($fv);
	}

	$initial  = 1 + ($rate * $when);
	$compound = pow(1 + $rate, $periods);
	$fv       = - (($present_value * $compound) + (($payment * $initial * ($compound - 1)) / $rate));

	return checkZero($fv);
}

function checkZero(float $value, float $epsilon = EPSILON): float
{
	return abs($value) < $epsilon ? 0.0 : $value;
}
