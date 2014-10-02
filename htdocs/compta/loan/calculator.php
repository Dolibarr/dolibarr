<?php
/* Copyright (C) 2014		Alexandre Spangaro	<alexandre.spangaro@gmail.com>
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

/**
 *   	\file       htdocs/compta/loan/calculator.php
 *		\ingroup    loan
 *		\brief      Calculator for loan
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/loan/class/loan.class.php';

$langs->load("loan");
$langs->load("compta");
$langs->load("banks");
$langs->load("bills");

// Security check
$socid = isset($GETPOST["socid"])?$GETPOST["socid"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'loan', '', '', '');

$action=GETPOST("action");

/* Defining Variables */
$periods = array(
				52 => $langs->trans("Weekly"),
				26 => $langs->trans("Bi-weekly"),
				12 => $langs->trans("Monthly"),
				6 =>  $langs->trans("Bi-monthly"),
				4 =>  $langs->trans("Quarterly"),
				2 =>  $langs->trans("Semi-annually"),
				1 =>  $langs->trans("Annually")
				);

$loan_capital    = isset($GETPOST("loan_capital"))   ? $GETPOST("loan_capital")    : 0;
$loan_length     = isset($GETPOST("loan_length"))    ? $GETPOST("loan_length")     : 0;
$loan_interest   = isset(GETPOST("loan_interest"))   ? $GETPOST("loan_interest")   : 0;
$pay_periodicity = isset(GETPOST("pay_periodicity")) ? $GETPOST("pay_periodicity") : 12;
$periodicity     = $periods[$pay_periodicity];
	
$pay_periods = '';
foreach($periods as $value => $name)
{
	$selected = ($pay_periodicity == $value) ? 'selected' : '';
}

if ($action == 'calculate')
{
	/* Checking Variables */
	$error = 0;
	
	if(!is_numeric($loan_capital) || $loan_capital <= 0) 
	{
		// $this->error="ErrorLoanCapital";
		return -1;
	}
	if(!is_numeric($loan_length) || $loan_length <= 0) 
	{
		// $this->error="ErrorLoanLength";
		return -1;
	}
	if(!is_numeric($loan_interest) or $loan_interest <= 0) 
	{
		// $this->error="ErrorLoanInterest";
		return -1;
	}

	/*
	 * Calculating
	 */
	if(isset($_GET['action']))
	{
		$c_balance         = $loan_capital;
		$total_periods     = $loan_length * $pay_periodicity;
		$interest_percent  = $loan_interest / 100;
		$period_interest   = $interest_percent / $pay_periodicity;
		$c_period_payment  = $loan_capital * ($period_interest / (1 - pow((1 + $period_interest), -($total_periods))));
		$total_paid        = number_format($c_period_payment * $total_periods, 2, '.', ' ');
		$total_interest    = number_format($c_period_payment * $total_periods - $loan_capital, 2, '.', ' ');
		$total_principal   = number_format($loan_capital, 2, '.', ' ');

		$loan_capital    = number_format($loan_capital, 2, '.', ' ');
		$loan_interest   = number_format($loan_interest, 2, '.', ' ');
		$period_payment  = number_format($c_period_payment, 2, '.', ' ');
			
		for($period = 1; $period <= $total_periods; $period++)
		{
			$c_interest  = $c_balance * $period_interest;
			$c_principal = $c_period_payment - $c_interest;
			$c_balance  -= $c_principal;
				
			$interest  = number_format($c_interest, 2, '.', ' ');
			$principal = number_format($c_principal, 2, '.', ' ');
			$balance   = number_format($c_balance, 2, '.', ' ');
				
			$evenrow_row_modifier = ($period % 2) ? '' : 'class=evenrow';
		}
	}
	else
	{
		$amortization_table = '';
		$loan_summary = '';
	}
}

/*
 * View
 */
 
$help_url='EN:Module_Loan|FR:Module_Emprunt';
llxHeader("",$langs->trans("Loan"),$help_url);

print_fiche_titre($langs->trans("CalculatorLoan"));

print '<table class="border" width="100%">';

print '<form name="calculate" action="' . $_SERVER["PHP_SELF"] . '" method="POST">' . "\n";
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="calculate">';
	
// Capital
print '<tr><td width="25%" class="fieldrequired">'.$langs->trans("Capital").'</td><td><input name="loan_capital" size="10" value="' . $loan_capital . '"></td></tr>';

// Length
print '<tr><td class="fieldrequired">'.$langs->trans("LengthByYears").'</td><td><input name="loan_length" size="2" value="' . $loan_length . '"></td></tr>';

// Interest
print '<tr><td class="fieldrequired">'.$langs->trans("Interest").'</td><td><input name="loan_interest" size="4" value="' . $loan_interest . '">&nbsp;%</td></tr>';

print '</table>';
	
print '<br><center><input class="button" type="submit" value="'.$langs->trans("Calculate").'"></center>';

print '</form>';

if ($action == 'calculate')
{
/*
	<!-- BEGIN amortization_table -->
	<table class=bordered cellpadding=5>
		<tr>
			<th>Period</th><th>Interest Paid</th><th>Principal Paid</th><th>Remaining Balance</th>
		</tr>
		{amortization_table_rows}
		<tr>
			<th>Totals:</th><th>{total_interest}$</th><th>{total_principal}$</th><th>&nbsp;</th>
		</tr>
	</table>
	<!-- END amortization_table -->

	<!-- BEGIN amortization_table_row -->
	<tr {evenrow_row_modifier}>
		<td align=center class=bordered>{period}</td>
		<td align=right class=bordered>{interest}$</td>
		<td align=right class=bordered>{principal}$</td>
		<td align=right class=bordered>{balance}$</td>
	</tr>
	<!-- END amortization_table_row -->

	<!-- BEGIN loan_summary -->
	<table cellpadding=5 width=100% class=bordered bgcolor=#EFEFEF style="margin-bottom: 10px">
		<tr>
			<th colspan=4>Loan Summary</th>
		</tr>
		<tr>
			<td class=label>Loan amount:</td>
			<td><b>{loan_amount}$</b></td>
		</tr>
		<tr>
			<td class=label>Loan length:</td>
			<td><b>{loan_length}&nbsp;years</b></td>
		</tr>
		<tr>
			<td class=label>Annual interest:</td>
			<td><b>{annual_interest}%</b></td>
		</tr>
		<tr>
			<td class=label>Pay periodicity:</td>
			<td><b>{periodicity}</b></td>
		</tr>
		<tr>
			<td class=label style="border-top: 1px solid #D6D6D6">{periodicity} payment:</td>
			<td style="border-top: 1px solid #D6D6D6"><b>{period_payment}$</b></td>
		</tr>
		<tr>
			<td class=label>Total paid:</td>
			<td><b>{total_paid}$</b></td>
		</tr>
		<tr>
			<td class=label>Total interest:</td>
			<td><b>{total_interest}$</b></td>
		</tr>
		<tr>
			<td class=label>Total periods:</td>
			<td><b>{total_periods}</b></td>
		</tr>
	</table>
	<!-- END loan_summary -->
	*/
}
else
{
	
}

$db->close();

llxFooter();
