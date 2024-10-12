<?php

namespace MathPHP;

use MathPHP\Exception\OutOfBoundsException;

/**
  * General references on financial functions and formulas:
  * - Open Document Format for Office Applications (OpenDocument) Version 1.2 Part 2:
  *   Recalculated Formula (OpenFormula) Format. 29 September 2011. OASIS Standard.
  *   http://docs.oasis-open.org/office/v1.2/os/OpenDocument-v1.2-os-part2.html#__RefHeading__1018228_715980110
  * - https://wiki.openoffice.org/wiki/Documentation/How_Tos/Calc:_Derivation_of_Financial_Formulas#Loans_and_Annuities
  */
class Finance
{
    /**
     * Floating-point range near zero to consider insignificant.
     */
    public const EPSILON = 1e-6;

    /**
     * Consider any floating-point value less than epsilon from zero as zero,
     * ie any value in the range [-epsilon < 0 < epsilon] is considered zero.
     * Also used to convert -0.0 to 0.0.
     *
     * @param float $value
     * @param float $epsilon
     *
     * @return float
     */
    private static function checkZero(float $value, float $epsilon = self::EPSILON): float
    {
        return \abs($value) < $epsilon ? 0.0 : $value;
    }

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
    public static function pmt(float $rate, int $periods, float $present_value, float $future_value = 0.0, bool $beginning = false): float
    {
        $when = $beginning ? 1 : 0;

        if ($rate == 0) {
            return - ($future_value + $present_value) / $periods;
        }

        return - ($future_value + ($present_value * \pow(1 + $rate, $periods)))
            /
            ((1 + $rate * $when) / $rate * (\pow(1 + $rate, $periods) - 1));
    }

    /**
     * Interest on a financial payment for a loan or annuity with compound interest.
     * Determines the interest payment at a particular period of the annuity. For
     * a typical loan paid down to zero, the amount of interest and principle paid
     * throughout the lifetime of the loan will change, with the interest portion
     * of the payment decreasing over time as the loan principle decreases.
     *
     * Same as the =IPMT() function in most spreadsheet software.
     *
     * See the PMT function for derivation of the formula. For IPMT, we have
     * the payment equal to the interest portion and principle portion of the payment:
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
     * Although the principle payments are equal, the total payment and interest portion are
     * lower with the annuity due because a principle payment is made immediately.
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
    public static function ipmt(float $rate, int $period, int $periods, float $present_value, float $future_value = 0.0, bool $beginning = false): float
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

        $payment = self::pmt($rate, $periods, $present_value, $future_value, $beginning);
        if ($beginning) {
            $interest = (self::fv($rate, $period - 2, $payment, $present_value, $beginning) - $payment) * $rate;
        } else {
            $interest = self::fv($rate, $period - 1, $payment, $present_value, $beginning) * $rate;
        }

        return self::checkZero($interest);
    }

    /**
     * Principle on a financial payment for a loan or annuity with compound interest.
     * Determines the principle payment at a particular period of the annuity. For
     * a typical loan paid down to zero, the amount of interest and principle paid
     * throughout the lifetime of the loan will change, with the principle portion
     * of the payment increasing over time as the loan principle decreases.
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
     * The principle on a payment on a 30-year fixed mortgage note of $265000 at 3.5% interest
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
    public static function ppmt(float $rate, int $period, int $periods, float $present_value, float $future_value = 0.0, bool $beginning = false): float
    {
        $payment = self::pmt($rate, $periods, $present_value, $future_value, $beginning);
        $ipmt    = self::ipmt($rate, $period, $periods, $present_value, $future_value, $beginning);

        return $payment - $ipmt;
    }

    /**
     * Number of payment periods of an annuity.
     * Solves for the number of periods in the annuity formula.
     *
     * Same as the =NPER() function in most spreadsheet software.
     *
     * Solving the basic annuity formula for number of periods:
     *        log(PMT - FV*r)
     *        ---------------
     *        log(PMT + PV*r)
     * n = --------------------
     *          log(1 + r)
     *
     * The (1+r*when) factor adjusts the payment to the beginning or end
     * of the period. In the common case of a payment at the end of a period,
     * the factor is 1 and reduces to the formula above. Setting when=1 computes
     * an "annuity due" with an immediate payment.
     *
     * Examples:
     * The number of periods of a $475000 mortgage with interest rate 3.5% and monthly
     * payment of $2132.96  paid in full:
     *   nper(0.035/12, -2132.96, 475000, 0)
     *
     * @param  float $rate
     * @param  float $payment
     * @param  float $present_value
     * @param  float $future_value
     * @param  bool  $beginning adjust the payment to the beginning or end of the period
     *
     * @return float
     */
    public static function periods(float $rate, float $payment, float $present_value, float $future_value, bool $beginning = false): float
    {
        $when = $beginning ? 1 : 0;

        if ($rate == 0) {
            return - ($present_value + $future_value) / $payment;
        }

        $initial = $payment * (1.0 + $rate * $when);
        return \log(($initial - $future_value * $rate) / ($initial + $present_value * $rate)) / \log(1.0 + $rate);
    }

    /**
     * Annual Equivalent Rate (AER) of an annual percentage rate (APR).
     * The effective yearly rate of an annual percentage rate when the
     * annual percentage rate is compounded periodically within the year.
     *
     * Same as the =EFFECT() function in most spreadsheet software.
     *
     * The formula:
     * https://en.wikipedia.org/wiki/Effective_interest_rate
     *
     *        /     i \ ᴺ
     * AER = | 1 +  -  |  - 1
     *        \     n /
     *
     * Examples:
     * The AER of APR 3.5% interest compounded monthly.
     *   aer(0.035, 12)
     *
     * @param  float $nominal
     * @param  int $periods
     *
     * @return float
     */
    public static function aer(float $nominal, int $periods): float
    {
        if ($periods == 1) {
            return $nominal;
        }

        return \pow(1 + ($nominal / $periods), $periods) - 1;
    }

    /**
     * Annual Nominal Rate of an annual effective rate (AER).
     * The nominal yearly rate of an annual effective rate when the
     * annual effective rate is compounded periodically within the year.
     *
     * Same as the =NOMINAL() function in most spreadsheet software.
     *
     * See:
     * https://en.wikipedia.org/wiki/Nominal_interest_rate
     *
     *           /          1/N    \
     * NOMINAL = | (AER + 1)    -1 | * N
     *           \                 /
     *
     * Examples:
     * The nominal rate of AER 3.557% interest compounded monthly.
     *   nominal(0.03557, 12)
     *
     * @param  float $aer
     * @param  int $periods
     *
     * @return float
     */
    public static function nominal(float $aer, int $periods): float
    {
        if ($periods == 1) {
            return $aer;
        }

        return (\pow($aer + 1, 1 / $periods) - 1) * $periods;
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
     * principle would be outstanding:
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
    public static function fv(float $rate, int $periods, float $payment, float $present_value, bool $beginning = false): float
    {
        $when = $beginning ? 1 : 0;

        if ($rate == 0) {
            $fv = -($present_value + ($payment * $periods));
            return self::checkZero($fv);
        }

        $initial  = 1 + ($rate * $when);
        $compound = \pow(1 + $rate, $periods);
        $fv       = - (($present_value * $compound) + (($payment * $initial * ($compound - 1)) / $rate));

        return self::checkZero($fv);
    }

    /**
     * Present value for a loan or annuity with compound interest.
     *
     * Same as the =PV() function in most spreadsheet software.
     *
     * The basic present-value formula derivation:
     * https://en.wikipedia.org/wiki/Present_value
     *
     *            PMT*((1+r)ᴺ - 1)
     * PV = -FV - ----------------
     *                   r
     *      ---------------------
     *             (1 + r)ᴺ
     *
     * The (1+r*when) factor adjusts the payment to the beginning or end
     * of the period. In the common case of a payment at the end of a period,
     * the factor is 1 and reduces to the formula above. Setting when=1 computes
     * an "annuity due" with an immediate payment.
     *
     * Examples:
     * The present value of a bond's $1000 face value paid in 5 year's time
     * with a constant discount rate of 3.5% compounded monthly:
     *   pv(0.035/12, 5*12, 0, -1000, false)
     *
     * The present value of a $1000 5-year bond that pays a fixed 7% ($70)
     * coupon at the end of each year with a discount rate of 5%:
     *   pv(0.5, 5, -70, -1000, false)
     *
     * The payment and future_value is negative indicating money paid out.
     *
     * @param  float $rate
     * @param  int   $periods
     * @param  float $payment
     * @param  float $future_value
     * @param  bool  $beginning adjust the payment to the beginning or end of the period
     *
     * @return float
     */
    public static function pv(float $rate, int $periods, float $payment, float $future_value, bool $beginning = false): float
    {
        $when = $beginning ? 1 : 0;

        if ($rate == 0) {
            $pv = -$future_value - ($payment * $periods);
            return self::checkZero($pv);
        }

        $initial  = 1 + ($rate * $when);
        $compound = \pow(1 + $rate, $periods);
        $pv       = (-$future_value - (($payment * $initial * ($compound - 1)) / $rate)) / $compound;

        return self::checkZero($pv);
    }

    /**
     * Net present value of cash flows. Cash flows are periodic starting
     * from an initial time and with a uniform discount rate.
     *
     * Similar to the =NPV() function in most spreadsheet software, except
     * the initial (usually negative) cash flow at time 0 is given as the
     * first element of the array rather than subtracted. For example,
     *   spreadsheet: =NPV(0.01, 100, 200, 300, 400) - 1000
     * is done as
     *   MathPHP::npv(0.01, [-1000, 100, 200, 300, 400])
     *
     * The basic net-present-value formula derivation:
     * https://en.wikipedia.org/wiki/Net_present_value
     *
     *  n      Rt
     *  Σ   --------
     * t=0  (1 / r)ᵗ
     *
     * Examples:
     * The net present value of 5 yearly cash flows after an initial $1000
     * investment with a 3% discount rate:
     *  npv(0.03, [-1000, 100, 500, 300, 700, 700])
     *
     * @param  float $rate
     * @param  array<float> $values
     *
     * @return float
     */
    public static function npv(float $rate, array $values): float
    {
        $result = 0.0;

        for ($i = 0; $i < \count($values); ++$i) {
            $result += $values[$i] / (1 + $rate) ** $i;
        }

        return $result;
    }

    /**
     * Interest rate per period of an Annuity.
     *
     * Same as the =RATE() formula in most spreadsheet software.
     *
     * The basic rate formula derivation is to solve for the future value
     * taking into account the present value:
     * https://en.wikipedia.org/wiki/Future_value
     *
     *                        ((1+r)ᴺ - 1)
     * FV + PV*(1+r)ᴺ + PMT * ------------ = 0
     *                             r
     * The (1+r*when) factor adjusts the payment to the beginning or end
     * of the period. In the common case of a payment at the end of a period,
     * the factor is 1 and reduces to the formula above. Setting when=1 computes
     * an "annuity due" with an immediate payment.
     *
     * Not all solutions for the rate have real-value solutions or converge.
     * In these cases, NAN is returned.
     *
     * @param  float $periods
     * @param  float $payment
     * @param  float $present_value
     * @param  float $future_value
     * @param  bool  $beginning
     * @param  float $initial_guess
     *
     * @return float
     */
    public static function rate(float $periods, float $payment, float $present_value, float $future_value, bool $beginning = false, float $initial_guess = 0.1): float
    {
        $when = $beginning ? 1 : 0;

        $func = function ($x, $periods, $payment, $present_value, $future_value, $when) {
            return $future_value + $present_value * (1 + $x) ** $periods + $payment * (1 + $x * $when) / $x * ((1 + $x) ** $periods - 1);
        };

        return self::checkZero(NumericalAnalysis\RootFinding\NewtonsMethod::solve($func, [$initial_guess, $periods, $payment, $present_value, $future_value, $when], 0, self::EPSILON, 0));
    }

    /**
     * Internal rate of return.
     * Periodic rate of return that would provide a net-present value (NPV) of 0.
     *
     * Same as =IRR formula in most spreadsheet software.
     *
     * Reference:
     * https://en.wikipedia.org/wiki/Internal_rate_of_return
     *
     * Examples:
     * The rate of return of an initial investment of $100 with returns
     * of $50, $40, and $30:
     *  irr([-100, 50, 40, 30])
     *
     * Solves for NPV=0 using Newton's Method.
     * @param array<float> $values
     * @param float $initial_guess
     *
     * @return float
     *
     * @throws OutOfBoundsException
     *
     * @todo: Use eigenvalues to find the roots of a characteristic polynomial.
     * This will allow finding all solutions and eliminate the need of the initial_guess.
     */
    public static function irr(array $values, float $initial_guess = 0.1): float
    {
        $func = function ($x, $values) {
            return Finance::npv($x, $values);
        };

        if (\count($values) <= 1) {
            return \NAN;
        }

        $root = NumericalAnalysis\RootFinding\NewtonsMethod::solve($func, [$initial_guess, $values], 0, self::EPSILON, 0);
        if (!\is_nan($root)) {
            return self::CheckZero($root);
        }
        return self::checkZero(self::alternateIrr($values));
    }

    /**
     * Alternate IRR implementation.
     *
     * A more numerically stable implementation that converges to only one value.
     *
     * Based off of Better: https://github.com/better/irr
     *
     * @param  array<float> $values
     *
     * @return float
     */
    private static function alternateIrr(array $values): float
    {
        $rate = 0.0;
        for ($iter = 0; $iter < 100; $iter++) {
            $m = -1000;
            for ($i = 0; $i < \count($values); $i++) {
                $m = \max($m, -$rate * $i);
            }
            $f = [];
            for ($i = 0; $i < \count($values); $i++) {
                $f[$i] = \exp(-$rate * $i - $m);
            }
            $t = 0;
            for ($i = 0; $i < \count($values); $i++) {
                $t += $f[$i] * $values[$i];
            }
            if (\abs($t) < (self::EPSILON * \exp($m))) {
                break;
            }
            $u = 0;
            for ($i = 0; $i < \count($values); $i++) {
                $u += $f[$i] * $i * $values[$i];
            }
            if ($u == 0) {
                return \NAN;
            }
            $rate += $t / $u;
        }
        return \exp($rate) - 1;
    }

    /**
     * Modified internal rate of return.
     * Rate of return that discounts outflows (investments) at the financing rate,
     * and reinvests inflows with an expected rate of return.
     *
     * Same as =MIRR formula in most spreadsheet software.
     *
     * The formula derivation:
     * https://en.wikipedia.org/wiki/Modified_internal_rate_of_return
     *
     *       _____________________________
     *     n/ FV(re-invested cash inflows)
     *  -  /  ----------------------------  - 1.0
     *   \/   PV(discounted cash outflows)
     *
     * Examples:
     * The rate of return of an initial investment of $100 at 5% financing
     * with returns of $50, $40, and $30 reinvested at 10%:
     *  mirr([-100, 50, 40, 30], 0.05, 0.10)
     *
     * @param  array<float> $values
     * @param  float $finance_rate
     * @param  float $reinvestment_rate
     *
     * @return float
     */
    public static function mirr(array $values, float $finance_rate, float $reinvestment_rate): float
    {
        $inflows  = array();
        $outflows = array();

        for ($i = 0; $i < \count($values); $i++) {
            if ($values[$i] >= 0) {
                $inflows[]  = $values[$i];
                $outflows[] = 0;
            } else {
                $inflows[]  = 0;
                $outflows[] = $values[$i];
            }
        }

        $nonzero = function ($x) {
            return $x != 0;
        };

        if (\count(\array_filter($inflows, $nonzero)) == 0 || \count(\array_filter($outflows, $nonzero)) == 0) {
            return \NAN;
        }

        $root        = \count($values) - 1;
        $pv_inflows  = self::npv($reinvestment_rate, $inflows);
        $fv_inflows  = self::fv($reinvestment_rate, $root, 0, -$pv_inflows);
        $pv_outflows = self::npv($finance_rate, $outflows);

        return self::checkZero(\pow($fv_inflows / -$pv_outflows, 1 / $root) - 1);
    }

    /**
     * Discounted Payback of an investment.
     * The number of periods to recoup cash outlays of an investment.
     *
     * This is commonly used with discount rate=0 as simple payback period,
     * but it is not a real financial measurement when it doesn't consider the
     * discount rate. Even with a discount rate, it doesn't consider the cost
     * of capital or re-investment of returns.
     *
     * Avoid this when possible. Consider NPV, MIRR, IRR, and other financial
     * functions.
     *
     * Reference:
     * https://en.wikipedia.org/wiki/Payback_period
     *
     * The result is given assuming cash flows are continous throughout a period.
     * To compute payback in terms of whole periods, use ceil() on the result.
     *
     * An investment could reach its payback period before future cash outlays occur.
     * The payback period returned is defined to be the final point at which the
     * sum of returns becomes positive.
     *
     * Examples:
     * The payback period of an investment with a $1,000 investment and future returns
     * of $100, $200, $300, $400, $500:
     *  payback([-1000, 100, 200, 300, 400, 500])
     *
     * The discounted payback period of an investment with a $1,000 investment, future returns
     * of $100, $200, $300, $400, $500, and a discount rate of 0.10:
     *  payback([-1000, 100, 200, 300, 400, 500], 0.1)
     *
     * @param  array<float> $values
     * @param  float $rate
     *
     * @return float
     */
    public static function payback(array $values, float $rate = 0.0): float
    {
        $last_outflow = -1;
        for ($i = 0; $i < \count($values); $i++) {
            if ($values[$i] < 0) {
                $last_outflow = $i;
            }
        }

        if ($last_outflow < 0) {
            return 0.0;
        }

        $sum            = $values[0];
        $payback_period = -1;

        for ($i = 1; $i < \count($values); $i++) {
            $prevsum         = $sum;
            $discounted_flow = $values[$i] / (1 + $rate) ** $i;
            $sum            += $discounted_flow;
            if ($sum >= 0) {
                if ($i > $last_outflow) {
                    return ($i - 1) + (-$prevsum / $discounted_flow);
                }
                if ($payback_period == -1) {
                    $payback_period = ($i - 1) + (-$prevsum / $discounted_flow);
                }
            } else {
                $payback_period = -1;
            }
        }
        if ($sum >= 0) {
            return $payback_period;
        }

        return \NAN;
    }

    /**
     * Profitability Index.
     * The Profitability Index, also referred to as Profit Investment
     * Ratio (PIR) and Value Investment Ratio (VIR), is a comparison of
     * discounted cash inflows to discounted cash outflows. It can be
     * used as a decision criteria of an investment, using larger than 1
     * to choose an investment, and less than 1 to pass.
     *
     * The formula derivation:
     * https://en.wikipedia.org/wiki/Profitability_index
     *
     * PV(cash inflows)
     * ----------------
     * PV(cash outflows)
     *
     * The formula is usually stated in terms of the initial investmest,
     * but it is generalized here to discount all future outflows.
     *
     * Examples:
     * The profitability index of an initial $100 investment with future
     * returns of $50, $50, $50 with a 10% discount rate:
     *  profitabilityIndex([-100, 50, 50, 50], 0.10)
     *
     * @param  array<float> $values
     * @param  float $rate
     *
     * @return float
     */
    public static function profitabilityIndex(array $values, float $rate): float
    {
        $inflows  = array();
        $outflows = array();

        for ($i = 0; $i < \count($values); $i++) {
            if ($values[$i] >= 0) {
                $inflows[]  = $values[$i];
                $outflows[] = 0;
            } else {
                $inflows[]  = 0;
                $outflows[] = -$values[$i];
            }
        }

        $nonzero = function ($x) {
            return $x != 0;
        };

        if (\count(\array_filter($outflows, $nonzero)) == 0) {
            return \NAN;
        }

        $pv_inflows  = self::npv($rate, $inflows);
        $pv_outflows = self::npv($rate, $outflows);

        return $pv_inflows / $pv_outflows;
    }
}
