<?php
/* Copyright (C) 2002		David Tufts			<http://dave.imarc.net>
 * Copyright (C) 2014		Alexandre Spangaro	<alexandre.spangaro@gmail.com>
 * Copyright (C) 2015		Frederic France		<frederic.france@free.fr>
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

require '../main.inc.php';

$langs->load("loan");

/* --------------------------------------------------- *
 * Set Form DEFAULT values
 * --------------------------------------------------- */
$default_sale_price              = "150000";
$default_annual_interest_percent = 7.0;
$default_year_term               = 30;
$default_down_percent            = 10;
$default_show_progress           = TRUE;

/* --------------------------------------------------- *
 * Initialize Variables
 * --------------------------------------------------- */
$sale_price                      = 0;
$annual_interest_percent         = 0;
$year_term                       = 0;
$down_percent                    = 0;
$this_year_interest_paid         = 0;
$this_year_principal_paid        = 0;
$form_complete                   = false;
$show_progress                   = false;
$monthly_payment                 = false;
$show_progress                   = false;
$error                           = false;

/* --------------------------------------------------- *
 * Set the USER INPUT values
 * --------------------------------------------------- */
if (isset($_REQUEST['form_complete'])) {
    $sale_price                      = $_REQUEST['sale_price'];
    $annual_interest_percent         = $_REQUEST['annual_interest_percent'];
    $year_term                       = $_REQUEST['year_term'];
    $down_percent                    = $_REQUEST['down_percent'];
    $show_progress                   = (isset($_REQUEST['show_progress'])) ? $_REQUEST['show_progress'] : false;
    $form_complete                   = $_REQUEST['form_complete'];
}

// This function does the actual mortgage calculations
// by plotting a PVIFA (Present Value Interest Factor of Annuity)
// table...
function get_interest_factor($year_term, $monthly_interest_rate) {
    global $base_rate;

    $factor      = 0;
    $base_rate   = 1 + $monthly_interest_rate;
    $denominator = $base_rate;
    for ($i=0; $i < ($year_term * 12); $i++) {
        $factor += (1 / $denominator);
        $denominator *= $base_rate;
    }
    return $factor;
}

// If the form is complete, we'll start the math
if ($form_complete) {
    // We'll set all the numeric values to JUST
    // numbers - this will delete any dollars signs,
    // commas, spaces, and letters, without invalidating
    // the value of the number
    $sale_price              = preg_replace( "[^0-9.]", "", $sale_price);
    $annual_interest_percent = preg_replace( "[^0-9.]", "", $annual_interest_percent);
    $year_term               = preg_replace( "[^0-9.]", "", $year_term);
    $down_percent            = preg_replace( "[^0-9.]", "", $down_percent);

	if ((float) $year_term <= 0) {
		$errors[] = "You must enter a <b>Sale Price of Home</b>";
	}
	if ((float) $sale_price <= 0) {
		$errors[] = "You must enter a <b>Length of Mortgage</b>";
	}
	if ((float) $annual_interest_percent <= 0) {
		$errors[] = "You must enter an <b>Annual Interest Rate</b>";
	}
	if (!$errors) {
        $month_term              = $year_term * 12;
        $down_payment            = $sale_price * ($down_percent / 100);
        $annual_interest_rate    = $annual_interest_percent / 100;
        $monthly_interest_rate   = $annual_interest_rate / 12;
        $financing_price         = $sale_price - $down_payment;
        $monthly_factor          = get_interest_factor($year_term, $monthly_interest_rate);
        $monthly_payment         = $financing_price / $monthly_factor;
    }
} else {
    if (!$sale_price)              { $sale_price              = $default_sale_price;              }
    if (!$annual_interest_percent) { $annual_interest_percent = $default_annual_interest_percent; }
    if (!$year_term)               { $year_term               = $default_year_term;               }
    if (!$down_percent)            { $down_percent            = $default_down_percent;            }
    if (!$show_progress)           { $show_progress           = $default_show_progress;           }
}

if (! empty($errors)) {
    setEventMessages('', $errors, 'errors');
    $form_complete   = false;
}

/*
 *	View
 */

llxHeader();

print_fiche_titre($langs->trans("LoanCalc"));
print $langs->trans('LoanCalcDesc');

print '<form method="GET" name="information" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="form_complete" value="1">';
print '<table cellpadding="2" cellspacing="0" border="0" width="100%">';
//print '<tr>';
//print '<td align="right"><img src="/images/clear.gif" width="225" height="1" border="0" alt=""></td>';
//print '<td align="smalltext" width="100%"><img src="/images/clear.gif" width="250" height="1" border="0" alt=""></td>';
//print '</tr>';
print '<tr bgcolor="#cccccc">';
print '<td align="center" colspan="2"><b>'.$langs->trans('PurchaseFinanceInfo').'</b></td>';
print '</tr>';
print '<tr bgcolor="#eeeeee">';
print '<td align="right">'.$langs->trans('SalePriceOfAsset').':</td>';
print '<td><input type="text" size="10" name="sale_price" value="'.$sale_price.'"> '.$langs->trans("Currency".$conf->currency).'</td>';print '</tr>';
print '<tr bgcolor="#eeeeee">';
print '<td align="right">'.$langs->trans('PercentageDown').':</td>';
print '<td><input type="text" size="5" name="down_percent" value="'.$down_percent.'">%</td>';
print '</tr>';
print '<tr bgcolor="#eeeeee">';
print '<td align="right">'.$langs->trans('LengthOfMortgage').':</td>';
print '<td><input type="text" size="3" name="year_term" value="'.$year_term.'">years</td>';
print '</tr>';
print '<tr bgcolor="#eeeeee">';
print '<td align="right">'.$langs->trans('AnnualInterestRate').':</td>';
print '<td><input type="text" size="5" name="annual_interest_percent" value="'.$annual_interest_percent.'">%</td>';
print '</tr>';
print '<tr bgcolor="#eeeeee">';
print '<td align="right">'.$langs->trans('ExplainCalculations').':</td>';

if (! empty($show_progress))
{
	print '<td><input type="checkbox" name="show_progress" value="1" checked>'.$langs->trans('ShowMeCalculationsAndAmortization').'</td>';
}
else
{
	print '<td><input type="checkbox" name="show_progress" value="1">'.$langs->trans('ShowMeCalculationsAndAmortization').'</td>';
}

print '</tr>';
print '</table>';

print '<br><center><input class="button" type="submit" value="'.$langs->trans("Calculate").'"> &nbsp; &nbsp; ';
print '<input class="button" type="submit" name="cancel" value="'.$langs->trans("Cancel").'"></center>';

// If the form has already been calculated, the $down_payment
// and $monthly_payment variables will be figured out, so we can show them in this table
if ($form_complete && $monthly_payment)
{
	print '<br>';
	print '<table cellpadding="2" cellspacing="0" border="0" width="100%">';
	print '<tr valign="top">';
	print '<td align="center" colspan="2" bgcolor="#000000"><font color="#ffffff"><b>'.$langs->trans('MortgagePaymentInformation').'</b></font></td>';
	print '</tr>';
	print '<tr valign="top" bgcolor="#eeeeee">';
	print '<td align="right">'.$langs->trans('DownPayment').':</td>';
	print '<td><b>' . number_format($down_payment, "2", ".", ",") . ' ' . $langs->trans("Currency".$conf->currency) . '</b></td>';
	print '</tr>';
	print '<tr valign="top" bgcolor="#eeeeee">';
	print '<td align="right">'.$langs->trans('AmountFinanced').':</td>';
	print '<td><b>' . number_format($financing_price, "2", ".", ",") . ' ' . $langs->trans("Currency".$conf->currency) . '</b></td>';
	print '</tr>';
	print '<tr valign="top" bgcolor="#cccccc">';
	print '<td align="right">'.$langs->trans('MonthlyPayment').':</td>';
	print '<td><b>' . number_format($monthly_payment, "2", ".", ",") . ' ' . $langs->trans("Currency".$conf->currency) . '</b><br><font>(Principal &amp; Interest ONLY)</font></td>';
	print '</tr>';

    if ($down_percent < 20)
	{
        $pmi_per_month = 55 * ($financing_price / 100000);

		print '<tr valign="top" bgcolor="#FFFFCC">';
		print '<td align="right">&nbsp;</td>';
		print '<td>';
		print '<br>';
        echo 'Since you are putting LESS than 20% down, you will need to pay PMI
                      (<a href="http://www.google.com/search?hl=en&q=private+mortgage+insurance">Private Mortgage Insurance</a>), which tends
                       to be about $55 per month for every $100,000 financed (until you have paid off 20% of your loan). This could add
                        '."\$" . number_format($pmi_per_month, "2", ".", ",").' to your monthly payment.';
		print '</td>';
		print '</tr>';
		print '<tr valign="top" bgcolor="#FFFF99">';
		print '<td align="right">'.$langs->trans('MonthlyPayment').':</td>';
		print '<td><b>' . number_format(($monthly_payment + $pmi_per_month), "2", ".", ",") . $langs->trans("Currency".$conf->currency) . '</b><br><font>';
		print '(Principal &amp; Interest, and PMI)</td>';
		print '</tr>';
	}

	print '<tr valign="top" bgcolor="#CCCCFF">';
	print '<td align="right">&nbsp;</td>';
	print '<td>';
	print '<br>';

	$assessed_price          = ($sale_price * .85);
	$residential_yearly_tax  = ($assessed_price / 1000) * 14;
	$residential_monthly_tax = $residential_yearly_tax / 12;

	if ($pmi_per_month)
	{
		$pmi_text = "PMI and ";
	}

	echo "Residential (or Property) Taxes are a little harder to figure out... In Massachusetts, the average resedential tax rate seems
          to be around $14 per year for every $1,000 of your property's assessed value.";

	print '<br><br>';
	print "Let's say that your property's <i>assessed value</i> is 85% of what you actually paid for it - ";
	print number_format($assessed_price, "2", ".", ",") . ' ' . $langs->trans("Currency".$conf->currency) . 'This would mean that your yearly residential taxes will be around';
	print number_format($residential_yearly_tax, "2", ".", ",") . ' ' . $langs->trans("Currency".$conf->currency);
	print 'This could add ' . number_format($residential_monthly_tax, "2", ".", ",") . ' ' . $langs->trans("Currency".$conf->currency) . 'to your monthly payment';
	print '</td>';
	print '</tr>';
	print '<tr valign="top" bgcolor="#9999FF">';
	print '<td align="right">TOTAL Monthly Payment:</td>';
	print '<td><b>' . number_format(($monthly_payment + $pmi_per_month + $residential_monthly_tax), "2", ".", ",") . ' ' . $langs->trans("Currency".$conf->currency) . '</b><br><font>';
	print '(including '.$pmi_text.' residential tax)</font></td>';
    print '</tr>';
}

print '</table>';
print '</form>';

// This prints the calculation progress and
// the instructions of HOW everything is figured
// out
if ($form_complete && $show_progress) {
    $step = 1;

	print '<br><br>';
	print '<table cellpadding="5" cellspacing="0" border="1" width="100%">';
	print '<tr valign="top">';
	print '<td><b>'. $step++ .'</b></td>';
	print '<td>';
	print $langs->trans('DownPaymentDesc').'<br><br>';
    print number_format($down_payment,"2",".",",") . ' ' . $langs->trans("Currency".$conf->currency) . ' = ';
    print number_format($sale_price,"2",".",",") . ' ' . $langs->trans("Currency".$conf->currency) . ' X (' . $down_percent . ' / 100)';
	print '</td>';
	print '</tr>';
	print '<tr valign="top">';
	print '<td><b>' . $step++ . '</b></td>';
	print '<td>';
	print $langs->trans('InterestRateDesc') . '<br><br>';
	print $annual_interest_rate . ' = ' . $annual_interest_percent . '% / 100';
	print '</td>';
	print '</tr>';
	print '<tr valign="top" bgcolor="#cccccc">';
	print '<td colspan="2">';
	print $langs->trans('MonthlyFactorDesc') . ':';
	print '</td>';
	print '</tr>';
	print '<tr valign="top">';
	print '<td><b>' . $step++ . '</b></td>';
	print '<td>';
	print $langs->trans('MonthlyInterestRateDesc') . '<br><br>';
	print $monthly_interest_rate . ' = ' . $annual_interest_rate . ' / 12';
	print '</td>';
	print '</tr>';
	print '<tr valign="top">';
	print '<td><b>' . $step++ . '</b></td>';
	print '<td>';
	print $langs->trans('MonthTermDesc') . '<br><br>';
	print $month_term . ' '. $langs->trans('Months') . ' = ' . $year_term . ' '. $langs->trans('Years') . ' X 12';
	print '</td>';
	print '</tr>';
	print '<tr valign="top">';
	print '<td><b>' . $step++ . '</b></td>';
	print '<td>';
	print $langs->trans('MonthlyPaymentDesc') . ':<br>';
	print $langs->trans('MonthlyPayment').' = ' . number_format($financing_price, "2", "", "") . ' * ';
	print '(1 - ((1 + ' . number_format($monthly_interest_rate, "4", "", "") . ')';
	print '<sup>-(' . $month_term . ')</sup>)))';
	print '<br><br>';
	print $langs->trans('AmortizationPaymentDesc');
	print '</td>';
	print '</tr>';
	print '</table>';
	print '<br>';


	// Set some base variables
	$principal     = $financing_price;
	$current_month = 1;
	$current_year  = 1;

	// This basically, re-figures out the monthly payment, again.
	$power = -($month_term);
	$denom = pow((1 + $monthly_interest_rate), $power);
	$monthly_payment = $principal * ($monthly_interest_rate / (1 - $denom));

	print '<br><br><a name="amortization"></a>'.$langs->trans('AmortizationMonthlyPaymentOverYears', number_format($monthly_payment, "2", ".", ","), $year_term)."<br>\n";

	print '<table class="noborder" width="100%">';

	// This LEGEND will get reprinted every 12 months
	$legend = '<tr class="liste_titre">';
	$legend.= '<td class="liste_titre" align="center">' . $langs->trans("Month") . '</td>';
	$legend.= '<td class="liste_titre" align="center">' . $langs->trans("Interest") . '</td>';
	$legend.= '<td class="liste_titre" align="center">' . $langs->trans("Capital") . '</td>';
	$legend.= '<td class="liste_titre" align="center">' . $langs->trans("Position") . '</td>';
	$legend.= '</tr>';

	print $legend;

	// Loop through and get the current month's payments for
	// the length of the loan
	while ($current_month <= $month_term)
	{
		$interest_paid     = $principal * $monthly_interest_rate;
		$principal_paid    = $monthly_payment - $interest_paid;
		$remaining_balance = $principal - $principal_paid;

		$this_year_interest_paid  = $this_year_interest_paid + $interest_paid;
		$this_year_principal_paid = $this_year_principal_paid + $principal_paid;

		$var = !$var;
		print "<tr ".$bc[$var].">";
		print '<td align="right">' . $current_month . '</td>';
		print '<td align="right">' . number_format($interest_paid, "2", ".", ",") . ' ' . $langs->trans("Currency".$conf->currency) . '</td>';
		print '<td align="right">' . number_format($principal_paid, "2", ".", ",") . ' ' . $langs->trans("Currency".$conf->currency) . '</td>';
		print '<td align="right">' . number_format($remaining_balance, "2", ".", ",") . ' ' . $langs->trans("Currency".$conf->currency) . '</td>';
		print '</tr>';

		($current_month % 12) ? $show_legend = FALSE : $show_legend = TRUE;

		if ($show_legend) {
			print '<tr>';
			print '<td colspan="4"><b>' . $langs->trans("Totalsforyear") . ' ' . $current_year . '</td>';
			print '</tr>';

			$total_spent_this_year = $this_year_interest_paid + $this_year_principal_paid;
			print '<tr>';
			print '<td>&nbsp;</td>';
			print '<td colspan="3">';
			print $langs->trans('YouWillSpend', number_format($total_spent_this_year, "2", ".", ",") . ' ' . $langs->trans("Currency".$conf->currency), $current_year) . '<br>';
			print $langs->trans('GoToInterest', number_format($this_year_interest_paid, "2", ".", ",") . ' ' . $langs->trans("Currency".$conf->currency)) . '<br>';
			print $langs->trans('GoToPrincipal', number_format($this_year_principal_paid, "2", ".", ",") . ' ' . $langs->trans("Currency".$conf->currency)) . '<br>';
			print '</td>';
			print '</tr>';

			print '<tr>';
			print '<td colspan="4">&nbsp;<br></td>';
			print '</tr>';

			$current_year++;
			$this_year_interest_paid  = 0;
			$this_year_principal_paid = 0;

			if (($current_month + 6) < $month_term)
			{
				echo $legend;
            }
        }
		$principal = $remaining_balance;
		$current_month++;
    }
	print "</table>\n";
}

llxFooter();

$db->close();
