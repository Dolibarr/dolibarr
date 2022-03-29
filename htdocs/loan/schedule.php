<?php
/* Copyright (C) 2017      Franck Moreau        <franck.moreau@theobald.com>
 * Copyright (C) 2018      Alexandre Spangaro   <aspangaro@open-dsi.fr>
 * Copyright (C) 2020      Maxime DEMAREST      <maxime@indelog.fr>
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
 *  \file       htdocs/loan/schedule.php
 *  \ingroup    loan
 *  \brief      Schedule card
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/loan/class/loan.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/loan.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/loan/class/loanschedule.class.php';
require_once DOL_DOCUMENT_ROOT.'/loan/class/paymentloan.class.php';

$loanid = GETPOST('loanid', 'int');
$action = GETPOST('action', 'aZ09');

// Security check
$socid = 0;
if (GETPOSTISSET('socid')) $socid = GETPOST('socid', 'int');
if ($user->socid) $socid = $user->socid;
if (empty($user->rights->loan->calc)) accessforbidden();

// Load translation files required by the page
$langs->loadLangs(array("compta", "bills", "loan"));

$object = new Loan($db);
$object->fetch($loanid);

$echeances = new LoanSchedule($db);
$echeances->fetchAll($object->id);

if ($object->paid > 0 && count($echeances->lines) == 0) {
	$pay_without_schedule = 1;
}

/*
 * Actions
 */

if ($action == 'createecheancier' && empty($pay_without_schedule)) {
	$db->begin();
	$i = 1;
	while ($i < $object->nbterm + 1) {
		$date = GETPOST('hi_date'.$i, 'int');
		$mens = price2num(GETPOST('mens'.$i));
		$int = price2num(GETPOST('hi_interets'.$i));
		$insurance = price2num(GETPOST('hi_insurance'.$i));

		$new_echeance = new LoanSchedule($db);

		$new_echeance->fk_loan = $object->id;
		$new_echeance->datec = dol_now();
		$new_echeance->tms = dol_now();
		$new_echeance->datep = $date;
		$new_echeance->amount_capital = $mens - $int;
		$new_echeance->amount_insurance = $insurance;
		$new_echeance->amount_interest = $int;
		$new_echeance->fk_typepayment = 3;
		$new_echeance->fk_bank = 0;
		$new_echeance->fk_user_creat = $user->id;
		$new_echeance->fk_user_modif = $user->id;
		$result = $new_echeance->create($user);
		if ($result < 0) {
			setEventMessages($new_echeance->error, $echeance->errors, 'errors');
			$db->rollback();
			unset($echeances->lines);
			break;
		}
		$echeances->lines[] = $new_echeance;
		$i++;
	}
	if ($result > 0) {
		$db->commit();
	}
}

if ($action == 'updateecheancier' && empty($pay_without_schedule)) {
	$db->begin();
	$i = 1;
	while ($i < $object->nbterm + 1) {
		$mens = price2num(GETPOST('mens'.$i));
		$int = price2num(GETPOST('hi_interets'.$i));
		$id = GETPOST('hi_rowid'.$i);
		$insurance = price2num(GETPOST('hi_insurance'.$i));

		$new_echeance = new LoanSchedule($db);
		$new_echeance->fetch($id);
		$new_echeance->tms = dol_now();
		$new_echeance->amount_capital = $mens - $int;
		$new_echeance->amount_insurance = $insurance;
		$new_echeance->amount_interest = $int;
		$new_echeance->fk_user_modif = $user->id;
		$result = $new_echeance->update($user, 0);
		if ($result < 0) {
			setEventMessages(null, $new_echeance->errors, 'errors');
			$db->rollback();
			$echeances->fetchAll($object->id);
			break;
		}

		$echeances->lines[$i - 1] = $new_echeance;
		$i++;
	}
	if ($result > 0) $db->commit();
}

/*
 * View
 */

$title = $langs->trans("Loan").' - '.$langs->trans("Card");
$help_url = 'EN:Module_Loan|FR:Module_Emprunt';
llxHeader("", $title, $help_url);

$head = loan_prepare_head($object);
print dol_get_fiche_head($head, 'FinancialCommitment', $langs->trans("Loan"), -1, 'bill');

$linkback = '<a href="'.DOL_URL_ROOT.'/loan/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

$morehtmlref = '<div class="refidno">';
// Ref loan
$morehtmlref .= $form->editfieldkey("Label", 'label', $object->label, $object, 0, 'string', '', 0, 1);
$morehtmlref .= $form->editfieldval("Label", 'label', $object->label, $object, 0, 'string', '', null, null, '', 1);
// Project
if (!empty($conf->projet->enabled))
{
	$langs->loadLangs(array("projects"));
	$morehtmlref .= '<br>'.$langs->trans('Project').' : ';
	if ($user->rights->loan->write)
	{
		if ($action != 'classify')
			//$morehtmlref .= '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?action=classify&id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetProject')).'</a> : ';
		if ($action == 'classify') {
			//$morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'projectid', 0, 0, 1, 1);
			$morehtmlref .= '<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
			$morehtmlref .= '<input type="hidden" name="action" value="classin">';
			$morehtmlref .= '<input type="hidden" name="token" value="'.newToken().'">';
			$morehtmlref .= $formproject->select_projects($object->socid, $object->fk_project, 'projectid', $maxlength, 0, 1, 0, 1, 0, 0, '', 1);
			$morehtmlref .= '<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
			$morehtmlref .= '</form>';
		} else {
			$morehtmlref .= $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, $object->socid, $object->fk_project, 'none', 0, 0, 0, 1);
		}
	} else {
		if (!empty($object->fk_project)) {
			$proj = new Project($db);
			$proj->fetch($object->fk_project);
			$morehtmlref .= '<a href="'.DOL_URL_ROOT.'/projet/card.php?id='.$object->fk_project.'" title="'.$langs->trans('ShowProject').'">';
			$morehtmlref .= $proj->ref;
			$morehtmlref .= '</a>';
		} else {
			$morehtmlref .= '';
		}
	}
}
$morehtmlref .= '</div>';

$morehtmlright = '';

dol_banner_tab($object, 'loanid', $linkback, 1, 'rowid', 'ref', $morehtmlref, '', 0, '', $morehtmlright);

?>
<script type="text/javascript" language="javascript">
$(document).ready(function() {
	$('[name^="mens"]').focusout(function() {
		var echeance=$(this).attr('ech');
		var mens=price2numjs($(this).val());
		var idcap=echeance-1;
		idcap = '#hi_capital'+idcap;
		var capital=price2numjs($(idcap).val());
		console.log("Change montly amount echeance="+echeance+" idcap="+idcap+" capital="+capital);
		$.ajax({
			  dataType: 'json',
			  url: 'calcmens.php',
			  data: { echeance: echeance, mens: mens, capital:capital, rate:<?php echo $object->rate / 100; ?> , nbterm : <?php echo $object->nbterm; ?>},
			  success: function(data) {
				$.each(data, function(index, element) {
					var idcap_res='#hi_capital'+index;
					var idcap_res_srt='#capital'+index;
					var interet_res='#hi_interets'+index;
					var interet_res_str='#interets'+index;
					var men_res='#mens'+index;
					$(idcap_res).val(element.cap_rest);
					$(idcap_res_srt).text(element.cap_rest_str);
					$(interet_res).val(element.interet);
					$(interet_res_str).text(element.interet_str);
					$(men_res).val(element.mens);
				});
			}
		});
	});
});
</script>
<?php

if ($pay_without_schedule == 1)
	print '<div class="warning">'.$langs->trans('CantUseScheduleWithLoanStartedToPaid').'</div>'."\n";

print '<form name="createecheancier" action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="loanid" value="'.$loanid.'">';
if (count($echeances->lines) > 0)
{
	print '<input type="hidden" name="action" value="updateecheancier">';
} else {
	print '<input type="hidden" name="action" value="createecheancier">';
}

print '<div class="div-table-responsive-no-min">';
print '<table class="border centpercent">';
print '<tr class="liste_titre">';
$colspan = 6;
if (count($echeances->lines) > 0) $colspan++;
print '<th class="center" colspan="'.$colspan.'">';
print $langs->trans("FinancialCommitment");
print '</th>';
print '</tr>';

print '<tr class="liste_titre">';
print '<th class="center">'.$langs->trans("Term").'</th>';
print '<th class="center">'.$langs->trans("Date").'</th>';
print '<th class="center">'.$langs->trans("Insurance");
print '<th class="center">'.$langs->trans("InterestAmount").'</th>';
print '<th class="center">'.$langs->trans("Amount").'</th>';
print '<th class="center">'.$langs->trans("CapitalRemain");
print '<br>('.price($object->capital, 0, '', 1, -1, -1, $conf->currency).')';
print '<input type="hidden" name="hi_capital0" id ="hi_capital0" value="'.$object->capital.'">';
print '</th>';
if (count($echeances->lines) > 0) print '<th class="center">'.$langs->trans('DoPayment').'</th>';
print '</tr>'."\n";

if ($object->nbterm > 0 && count($echeances->lines) == 0)
{
	$i = 1;
	$capital = $object->capital;
	$insurance = $object->insurance_amount / $object->nbterm;
	$insurance = price2num($insurance, 'MT');
	$regulInsurance = price2num($object->insurance_amount - ($insurance * $object->nbterm));
	while ($i < $object->nbterm + 1)
	{
		$mens = price2num($echeances->calcMonthlyPayments($capital, $object->rate / 100, $object->nbterm - $i + 1), 'MT');
		$int = ($capital * ($object->rate / 12)) / 100;
		$int = price2num($int, 'MT');
		$insu = ($insurance + (($i == 1) ? $regulInsurance : 0));
		$cap_rest = price2num($capital - ($mens - $int), 'MT');
		print '<tr>';
		print '<td class="center" id="n'.$i.'">'.$i.'</td>';
		print '<td class="center" id ="date'.$i.'"><input type="hidden" name="hi_date'.$i.'" id ="hi_date'.$i.'" value="'.dol_time_plus_duree($object->datestart, $i - 1, 'm').'">'.dol_print_date(dol_time_plus_duree($object->datestart, $i - 1, 'm'), 'day').'</td>';
		print '<td class="center" id="insurance'.$i.'">'.price($insurance + (($i == 1) ? $regulInsurance : 0), 0, '', 1, -1, -1, $conf->currency).'</td><input type="hidden" name="hi_insurance'.$i.'" id ="hi_insurance'.$i.'" value="'.($insurance + (($i == 1) ? $regulInsurance : 0)).'">';
		print '<td class="center" id="interets'.$i.'">'.price($int, 0, '', 1, -1, -1, $conf->currency).'</td><input type="hidden" name="hi_interets'.$i.'" id ="hi_interets'.$i.'" value="'.$int.'">';
		print '<td class="center"><input name="mens'.$i.'" id="mens'.$i.'" size="5" value="'.$mens.'" ech="'.$i.'"></td>';
		print '<td class="center" id="capital'.$i.'">'.price($cap_rest).'</td><input type="hidden" name="hi_capital'.$i.'" id ="hi_capital'.$i.'" value="'.$cap_rest.'">';
		print '</tr>'."\n";
		$i++;
		$capital = $cap_rest;
	}
} elseif (count($echeances->lines) > 0)
{
	$i = 1;
	$capital = $object->capital;
	$insurance = $object->insurance_amount / $object->nbterm;
	$insurance = price2num($insurance, 'MT');
	$regulInsurance = price2num($object->insurance_amount - ($insurance * $object->nbterm));
	$printed = false;
	foreach ($echeances->lines as $line) {
		$mens = $line->amount_capital + $line->amount_interest;
		$int = $line->amount_interest;
		$insu = ($insurance + (($i == 1) ? $regulInsurance : 0));
		$cap_rest = price2num($capital - ($mens - $int), 'MT');

		print '<tr>';
		print '<td class="center" id="n'.$i.'"><input type="hidden" name="hi_rowid'.$i.'" id ="hi_rowid'.$i.'" value="'.$line->id.'">'.$i.'</td>';
		print '<td class="center" id ="date'.$i.'"><input type="hidden" name="hi_date'.$i.'" id ="hi_date'.$i.'" value="'.$line->datep.'">'.dol_print_date($line->datep, 'day').'</td>';
		print '<td class="center" id="insurance'.$i.'">'.price($insu, 0, '', 1, -1, -1, $conf->currency).'</td><input type="hidden" name="hi_insurance'.$i.'" id ="hi_insurance'.$i.'" value="'.$insu.'">';
		print '<td class="center" id="interets'.$i.'">'.price($int, 0, '', 1, -1, -1, $conf->currency).'</td><input type="hidden" name="hi_interets'.$i.'" id ="hi_interets'.$i.'" value="'.$int.'">';
		if (empty($line->fk_bank)) {
			print '<td class="center"><input name="mens'.$i.'" id="mens'.$i.'" size="5" value="'.$mens.'" ech="'.$i.'"></td>';
		} else {
			print '<td class="center">'.price($mens, 0, '', 1, -1, -1, $conf->currency).'</td><input type="hidden" name="mens'.$i.'" id ="mens'.$i.'" value="'.$mens.'">';
		}

		print '<td class="center" id="capital'.$i.'">'.price($cap_rest, 0, '', 1, -1, -1, $conf->currency).'</td><input type="hidden" name="hi_capital'.$i.'" id ="hi_capital'.$i.'" value="'.$cap_rest.'">';
		print '<td class="center">';
		if (!empty($line->fk_bank))
		{
			print $langs->trans('Paid');
			if (!empty($line->fk_payment_loan))
				print '&nbsp;<a href="'.DOL_URL_ROOT.'/loan/payment/card.php?id='.$line->fk_payment_loan.'">('.img_object($langs->trans("Payment"), "payment").' '.$line->fk_payment_loan.')</a>';
		}
		elseif (!$printed)
		{
			print '<a class="butAction" href="'.DOL_URL_ROOT.'/loan/payment/payment.php?id='.$object->id.'&amp;action=create">'.$langs->trans('DoPayment').'</a>';
			$printed = true;
		}
		print '</td>';
		print '</tr>'."\n";
		$i++;
		$capital = $cap_rest;
	}
}

print '</table>';
print '</div>';

print '</br>';

if (count($echeances->lines) == 0) $label = $langs->trans("Create");
else $label = $langs->trans("Save");
print '<div class="center"><input class="button" type="submit" value="'.$label.'" '.(($pay_without_schedule == 1) ? 'disabled title="'.$langs->trans('CantUseScheduleWithLoanStartedToPaid').'"' : '').'title=""></div>';
print '</form>';

// End of page
llxFooter();
$db->close();
