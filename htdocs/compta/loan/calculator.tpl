<!-- BEGIN header -->
<html>
<head>
	<title>Loan Calculator</title>
	<style>
		BODY, TABLE, TH, TD, TR, FORM {font: 11px Tahoma,Arial,Helvetica,sans-serif; vertical-align: top}
		FORM {margin: 0px; padding: 0px}
		TEXTAREA, INPUT, SELECT, LABEL, BUTTON {font: 11px Tahoma,Arial,Helvetica,sans-serif}
		TD.label { text-align: right; vertical-align: middle}
		TABLE {border-collapse: collapse}
		TH {font: bold; background: #FFF3CB; border: 1px solid #D6D6D6; padding: 5px; text-align: center}
		.bordered {border: 1px solid #D6D6D6}
		.evenrow {background-color: #EFEFEF}
	</style>
</head>
<body>
<!-- END header -->

<!-- BEGIN body -->
<table cellpadding=5 width=100% height=100% style="text-align: center; vertical-align: middle">
	<tr>
		<td height=100% style="text-align: center; vertical-align: middle">	
			{error}
			<table cellpadding=10>
				<tr>
					<td>
						{loan_summary}
						{loan_parameters_form}
					</td>
					<td>
						{amortization_table}
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
<!-- END body -->

<!-- BEGIN loan_parameters_form -->
<form>
<table cellpadding=5 width=100%>
	<tr>
		<th>Loan Parameters</th>
	</tr>
	<tr>
		<td class=bordered bgcolor=#EFEFEF>
			<table cellpadding=4 width=100%>
				<tr>
					<td class=label>Loan amount:</td>
					<td><input type="text" name="loan_amount" value="{loan_amount}" size=7>&nbsp;$</td>
				</tr>
				<tr>
					<td class=label>Loan length:</td>
					<td><input type="text" name="loan_length" value="{loan_length}" size=2>&nbsp;years</td>
				</tr>
				<tr>
					<td class=label>Annual interest:</td>
					<td><input type="text" name="annual_interest" value="{annual_interest}" size=2>&nbsp;%</td>
				</tr>
				<tr>
					<td class=label>Pay periodicity:</td>
					<td>
						<select name=pay_periodicity>
							{pay_periods}
						</select>
					</td>
				</tr>
				<tr>
					<td colspan=2 align=center><input name="action" type="submit" value="Calculate"></td>
				</tr>
			</table>
		</td>
	</tr>
	<tr>
		<!-- DO NOT REMOVE THIS LINE! Read Disclaimer in the loan-calculators.php file --><td align=center style="font: 9px; color: #AAAAAA">Powered by PC <a href="http://www.pc-calculators.com" style="font: 9px; color: #AAAAAA; text-decoration: none">Calculators</a></td>
	</tr>
</table>
</form>
<!-- END loan_parameters_form -->

<!-- BEGIN pay_period_option -->
<option value={value} {selected}>{name}</option>
<!-- END pay_period_option -->

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

<!-- BEGIN footer -->
</body>
</html>
<!-- END footer -->
