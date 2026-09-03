<?php
/* Copyright (C) 2017		Franck Moreau				<franck.moreau@theobald.com>
 * Copyright (C) 2018-2024	Alexandre Spangaro			<alexandre@inovea-conseil.com>
 * Copyright (C) 2020		Maxime DEMAREST				<maxime@indelog.fr>
 * Copyright (C) 2024-2026	MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024-2026  Frédéric France				<frederic.france@free.fr>
 * Copyright (C) 2026		Lenin Rivas					<lenin.rivas777@gmail.com>
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

// Load Dolibarr environment
require '../main.inc.php';
/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */
require_once DOL_DOCUMENT_ROOT.'/loan/class/loan.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/loan.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/loan/class/loanschedule.class.php';
require_once DOL_DOCUMENT_ROOT.'/loan/class/paymentloan.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
if (isModEnabled('project')) {
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
}

$loanid = GETPOSTINT('loanid');
$action = GETPOST('action', 'aZ09');

// Security check
$socid = 0;
if (GETPOSTISSET('socid')) {
	$socid = GETPOSTINT('socid');
}
if ($user->socid) {
	$socid = $user->socid;
}
if (!$user->hasRight('loan', 'calc')) {
	accessforbidden();
}

// Load translation files required by the page
$langs->loadLangs(array("compta", "bills", "loan"));

$object = new Loan($db);
$object->fetch($loanid);

$echeances = new LoanSchedule($db);
$echeances->fetchAll($object->id);

if ($object->paid > 0 && count($echeances->lines) == 0) {
	$pay_without_schedule = 1;
} else {
	$pay_without_schedule = 0;
}

$permissiontoadd = $user->hasRight('loan', 'write');


/*
 * Actions
 */

if ($action == 'createecheancier' && empty($pay_without_schedule) && $permissiontoadd) {
	$result = -1;
	$db->begin();
	$i = 1;
	while ($i < $object->nbterm + 1) {
		$date = GETPOSTINT('hi_date'.$i);
		$mens = price2num(GETPOST('mens'.$i));
		if (GETPOSTISSET('interets'.$i)) {
			$int = price2num(GETPOST('interets'.$i));
		} else {
			$int = price2num(GETPOST('hi_interets'.$i));
		}
		$insurance = price2num(GETPOST('hi_insurance'.$i));
		if (GETPOSTISSET('amort'.$i)) {
			$amort = price2num(GETPOST('amort'.$i));
		} else {
			$amort = (float) $mens - (float) $int;
		}

		$new_echeance = new LoanSchedule($db);

		$new_echeance->fk_loan = $object->id;
		$new_echeance->datec = dol_now();
		$new_echeance->tms = dol_now();
		$new_echeance->datep = $date;
		$new_echeance->amount_capital = $amort;
		$new_echeance->amount_insurance = $insurance;
		$new_echeance->amount_interest = $int;
		$new_echeance->fk_typepayment = 3;
		$new_echeance->fk_bank = 0;
		$new_echeance->fk_user_creat = $user->id;
		$new_echeance->fk_user_modif = $user->id;
		$result = $new_echeance->create($user);
		if ($result < 0) {
			setEventMessages($new_echeance->error, $new_echeance->errors, 'errors');
			$db->rollback();
			$echeances->lines = [];
			break;
		}
		$echeances->lines[] = $new_echeance;
		$i++;
	}
	if ($result > 0) {
		$db->commit();
	}
}

if ($action == 'updateecheancier' && empty($pay_without_schedule) && $permissiontoadd) {
	$result = -1;
	$db->begin();
	$i = 1;
	while ($i < $object->nbterm + 1) {
		$mens = price2num(GETPOST('mens'.$i));
		if (GETPOSTISSET('interets'.$i)) {
			$int = price2num(GETPOST('interets'.$i));
		} else {
			$int = price2num(GETPOST('hi_interets'.$i));
		}
		$id = GETPOSTINT('hi_rowid'.$i);
		$insurance = price2num(GETPOST('hi_insurance'.$i));
		if (GETPOSTISSET('amort'.$i)) {
			$amort = price2num(GETPOST('amort'.$i));
		} else {
			$amort = (float) $mens - (float) $int;
		}

		$new_echeance = new LoanSchedule($db);
		$new_echeance->fetch($id);
		$new_echeance->tms = dol_now();
		$new_echeance->amount_capital = $amort;
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
	if ($result > 0) {
		$db->commit();
	}
}


/*
 * View
 */

$form = new Form($db);
$formproject = new FormProjets($db);

$title = $langs->trans("Loan").' - '.$langs->trans("FinancialCommitment");
$help_url = 'EN:Module_Loan|FR:Module_Emprunt';

llxHeader("", $title, $help_url, '', 0, 0, '', '', '', 'mod-loan page-card_schedule');

$head = loan_prepare_head($object);
print dol_get_fiche_head($head, 'FinancialCommitment', $langs->trans("Loan"), -1, 'money-bill-alt');

$linkback = '<a href="'.DOL_URL_ROOT.'/loan/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

$morehtmlref = '<div class="refidno">';
// Ref loan
$morehtmlref .= $form->editfieldkey("Label", 'label', $object->label, $object, 0, 'string', '', 0, 1);
$morehtmlref .= $form->editfieldval("Label", 'label', $object->label, $object, 0, 'string', '', null, null, '', 1);
// Project
if (isModEnabled('project')) {
	$langs->loadLangs(array("projects"));
	$morehtmlref .= '<br>'.$langs->trans('Project').' : ';
	if ($user->hasRight('loan', 'write')) {
		if ($action != 'classify') {
			//$morehtmlref .= '<a class="editfielda" href="'.dolBuildUrl($_SERVER['PHP_SELF'], ['action' => 'classify', 'id' => $object->id], true).'">'.img_edit($langs->transnoentitiesnoconv('SetProject')).'</a> : ';
			if ($action == 'classify') {
				//$morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'projectid', 0, 0, 1, 1);
				$morehtmlref .= '<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
				$morehtmlref .= '<input type="hidden" name="action" value="classin">';
				$morehtmlref .= '<input type="hidden" name="token" value="'.newToken().'">';
				$morehtmlref .= $formproject->select_projects(-1, (string) $object->fk_project, 'projectid', 16, 0, 1, 0, 1, 0, 0, '', 1);
				$morehtmlref .= '<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
				$morehtmlref .= '</form>';
			} else {
				$morehtmlref .= $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, -1, (string) $object->fk_project, 'none', 0, 0, 0, 1, '', 'maxwidth300');
			}
		}
	} else {
		if (!empty($object->fk_project)) {
			$proj = new Project($db);
			$proj->fetch($object->fk_project);
			$morehtmlref .= ' : '.$proj->getNomUrl(1);
			if ($proj->title) {
				$morehtmlref .= ' - '.$proj->title;
			}
		} else {
			$morehtmlref .= '';
		}
	}
}
$morehtmlref .= '</div>';

$morehtmlstatus = '';

dol_banner_tab($object, 'loanid', $linkback, 1, 'rowid', 'ref', $morehtmlref, '', 0, '', $morehtmlstatus);

?>
<script type="text/javascript">
$(document).ready(function() {
	var timeout = null;
	var delay = 750;   // 0.75 seconds

	function updateTotals() {
		var totInsu = 0;
		var totInt = 0;
		var totAmort = 0;
		var totMens = 0;

		$('[name^="hi_insurance"]').each(function() {
			totInsu += (price2numjs($(this).val()) || 0);
		});
		$('[name^="hi_interets"]').each(function() {
			totInt += (price2numjs($(this).val()) || 0);
		});
		$('[id^="hi_amort"]').each(function() {
			totAmort += (price2numjs($(this).val()) || 0);
		});
		$('[id^="hi_mens"]').each(function() {
			totMens += (price2numjs($(this).val()) || 0);
		});

		$('#total_insurance').text(pricejs(totInsu, 'MT', '<?php echo $conf->currency; ?>'));
		$('#total_interest').text(pricejs(totInt, 'MT', '<?php echo $conf->currency; ?>'));
		$('#total_amort').text(pricejs(totAmort, 'MT', '<?php echo $conf->currency; ?>'));
		$('#total_mens').text(pricejs(totMens, 'MT', '<?php echo $conf->currency; ?>'));
	}

	$('[name^="mens"]').on('keyup change', function() {
		clearTimeout(timeout);
		var $this = $(this);
		timeout = setTimeout(() => {
			var echeance = $this.attr('ech');
			var mens = $this.val();
			var amort = $('#amort' + echeance).val();
			var interet = $('#interets' + echeance).val();
			calculateMens(echeance, mens, amort, 'mens', 0, interet);
		}, delay);
	});

	$('[name^="amort"]').on('keyup change', function() {
		clearTimeout(timeout);
		var $this = $(this);
		timeout = setTimeout(() => {
			var echeance = $this.attr('ech');
			var amort = $this.val();
			var mens = $('#mens' + echeance).val();
			var interet = $('#interets' + echeance).val();
			calculateMens(echeance, mens, amort, 'amort', 0, interet);
		}, delay);
	});

	$('[name^="interets"]').on('keyup change', function() {
		clearTimeout(timeout);
		var $this = $(this);
		timeout = setTimeout(() => {
			var echeance = $this.attr('ech');
			var interet = $this.val();
			var amort = $('#amort' + echeance).val();
			var mens = $('#mens' + echeance).val();
			calculateMens(echeance, mens, amort, 'interet', 0, interet);
		}, delay);
	});

	$('#btn_apply_grace_period').on('click', function(e) {
		e.preventDefault();
		var months = parseInt($('#grace_period_months').val(), 10);
		if (isNaN(months) || months < 1) return;
		var nbterm = <?php echo (int) $object->nbterm; ?>;
		if (months >= nbterm) months = nbterm - 1;

		calculateMens(1, 0, 0, 'amort', months, 0);
	});

	function calculateMens(echeance, mens, amort, source, grace_period, interet) {
		var idcap = echeance - 1;
		idcap = '#hi_capital' + idcap;
		var capital = price2numjs($(idcap).val());
		console.log("calculateMens echeance=" + echeance + " idcap=" + idcap + " capital=" + capital + " source=" + source + " grace=" + grace_period);

		$.ajax({
			method: "GET",
			dataType: 'json',
			url: 'calcmens.php',
			data: {
				echeance: echeance,
				mens: price2numjs(mens),
				amort: price2numjs(amort),
				interet: price2numjs(interet),
				source: source,
				grace_period: grace_period,
				capital: capital,
				rate: <?php echo $object->rate / 100; ?>,
				nbterm: <?php echo $object->nbterm; ?>,
				token: '<?php echo currentToken(); ?>'
			},
			success: function(data) {
				$.each(data, function(index, element) {
					$('#hi_capital' + index).val(element.cap_rest);
					$('#capital' + index).text(element.cap_rest_str);

					if ($('#interets' + index).is('input')) {
						$('#interets' + index).val(element.interet);
					} else {
						$('#interets' + index).text(element.interet_str);
					}
					$('#hi_interets' + index).val(element.interet);

					if ($('#amort' + index).is('input')) {
						$('#amort' + index).val(element.amort);
					} else {
						$('#amort' + index).text(element.amort);
					}
					$('#hi_amort' + index).val(element.amort_num);

					if ($('#mens' + index).is('input')) {
						$('#mens' + index).val(element.mens);
					} else {
						$('#mens' + index).text(element.mens);
					}
					$('#hi_mens' + index).val(element.mens_num);
				});
				updateTotals();
			}
		});
	}

	updateTotals();
});
</script>
<?php

if ($pay_without_schedule == 1) {
	print '<div class="warning">'.$langs->trans('CantUseScheduleWithLoanStartedToPaid').'</div>'."\n";
}

print '<form name="createecheancier" action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="loanid" value="'.$loanid.'">';
if (count($echeances->lines) > 0) {
	print '<input type="hidden" name="action" value="updateecheancier">';
} else {
	print '<input type="hidden" name="action" value="createecheancier">';
}

if (empty($pay_without_schedule) && $permissiontoadd) {
	print '<div class="marginbottomonly inline-block valignmiddle">';
	print '<span class="opacitymedium">'.$langs->trans("GracePeriodMonths").': </span>';
	print '<input type="number" id="grace_period_months" min="1" max="'.max(1, $object->nbterm - 1).'" value="1" class="width50 right"> ';
	print '<input type="button" id="btn_apply_grace_period" class="button valignmiddle" value="'.$langs->trans("ApplyGracePeriod").'">';
	print '</div>';
	print '<br><br>';
}

print '<div class="div-table-responsive-no-min">';
print '<table class="border centpercent">';

$colspan = 7;
if (count($echeances->lines) > 0) {
	$colspan++;
}

print '<tr class="liste_titre">';
print '<th class="center">'.$langs->trans("Term").'</th>';
print '<th class="center">'.$langs->trans("Date").'</th>';
print '<th class="center">'.$langs->trans("Insurance").'</th>';
print '<th class="center">'.$langs->trans("InterestAmount").'</th>';
print '<th class="center">'.$langs->trans("CapitalAmortization").'</th>';
print '<th class="center">'.$langs->trans("Amount").'</th>';
print '<th class="center">'.$langs->trans("CapitalRemain");
print '<br>('.price($object->capital, 0, '', 1, -1, -1, $conf->currency).')';
print '<input type="hidden" name="hi_capital0" id ="hi_capital0" value="'.$object->capital.'">';
print '</th>';
if (count($echeances->lines) > 0) {
	print '<th class="center">'.$langs->trans('DoPayment').'</th>';
}
print '</tr>'."\n";

$cap_rest = 0.0;
$total_insurance = 0.0;
$total_interest = 0.0;
$total_amort = 0.0;
$total_mens = 0.0;

if ($object->nbterm > 0 && count($echeances->lines) == 0) {
	$i = 1;
	$capital = $object->capital;
	$cap_rest = (float) $capital;
	$insurance = (float) $object->insurance_amount / $object->nbterm;
	$insurance = price2num($insurance, 'MT');
	$regulInsurance = price2num((float) $object->insurance_amount - ((float) $insurance * $object->nbterm));

	while ($i < $object->nbterm + 1) {
		$mens = price2num($echeances->calcMonthlyPayments($capital, $object->rate / 100, $object->nbterm - $i + 1), 'MT');
		$int = ($capital * ($object->rate / 12)) / 100;
		$int = price2num($int, 'MT');
		$amort = price2num((float) $mens - (float) $int, 'MT');
		$insu = ((float) $insurance + (($i == 1) ? (float) $regulInsurance : 0));

		// Adjust rounding difference on last term
		if ($i == $object->nbterm && abs($cap_rest) <= 0.05 && $capital > 0) {
			$amort = $capital;
			$cap_rest = 0.0;
			$mens = price2num((float) $amort + (float) $int, 'MT');
		}

		$total_insurance += $insu;
		$total_interest += $int;
		$total_amort += $amort;
		$total_mens += $mens;

		print '<tr>';
		print '<td class="center" id="n'.$i.'">'.$i.'</td>';
		print '<td class="center" id ="date'.$i.'"><input type="hidden" name="hi_date'.$i.'" id ="hi_date'.$i.'" value="'.dol_time_plus_duree($object->datestart, $i - 1, 'm').'">'.dol_print_date(dol_time_plus_duree($object->datestart, $i - 1, 'm'), 'day').'</td>';
		print '<td class="center amount" id="insurance'.$i.'">'.price($insu, 0, '', 1, -1, -1, $conf->currency).'</td><input type="hidden" name="hi_insurance'.$i.'" id ="hi_insurance'.$i.'" value="'.$insu.'">';
		print '<td class="center"><input class="width75 right" name="interets'.$i.'" id="interets'.$i.'" value="'.price($int).'" ech="'.$i.'"><input type="hidden" name="hi_interets'.$i.'" id="hi_interets'.$i.'" value="'.$int.'"></td>';
		print '<td class="center"><input class="width75 right" name="amort'.$i.'" id="amort'.$i.'" value="'.price($amort).'" ech="'.$i.'"><input type="hidden" name="hi_amort'.$i.'" id="hi_amort'.$i.'" value="'.$amort.'"></td>';
		print '<td class="center"><input class="width75 right" name="mens'.$i.'" id="mens'.$i.'" value="'.price($mens).'" ech="'.$i.'"><input type="hidden" name="hi_mens'.$i.'" id="hi_mens'.$i.'" value="'.$mens.'"></td>';
		print '<td class="center amount" id="capital'.$i.'">'.price($cap_rest, 0, '', 1, -1, -1, $conf->currency).'</td><input type="hidden" name="hi_capital'.$i.'" id ="hi_capital'.$i.'" value="'.$cap_rest.'">';
		print '</tr>'."\n";
		$i++;
		$capital = $cap_rest;
	}
} elseif (count($echeances->lines) > 0) {
	$i = 1;
	$capital = $object->capital;
	$cap_rest = (float) $capital;
	$insurance = (float) $object->insurance_amount / $object->nbterm;
	$insurance = price2num($insurance, 'MT');
	$regulInsurance = price2num((float) $object->insurance_amount - ((float) $insurance * $object->nbterm));
	$printed = false;

	foreach ($echeances->lines as $line) {
		$mens = $line->amount_capital + $line->amount_interest;
		$int = $line->amount_interest;
		$amort = $line->amount_capital;
		$insu = ((float) $insurance + (($i == 1) ? (float) $regulInsurance : 0));
		$cap_rest = price2num($capital - $amort, 'MT');

		$total_insurance += $insu;
		$total_interest += $int;
		$total_amort += $amort;
		$total_mens += $mens;

		print '<tr>';
		print '<td class="center" id="n'.$i.'"><input type="hidden" name="hi_rowid'.$i.'" id ="hi_rowid'.$i.'" value="'.$line->id.'">'.$i.'</td>';
		print '<td class="center" id ="date'.$i.'"><input type="hidden" name="hi_date'.$i.'" id ="hi_date'.$i.'" value="'.$line->datep.'">'.dol_print_date($line->datep, 'day').'</td>';
		print '<td class="center amount" id="insurance'.$i.'">'.price($insu, 0, '', 1, -1, -1, $conf->currency).'</td><input type="hidden" name="hi_insurance'.$i.'" id ="hi_insurance'.$i.'" value="'.$insu.'">';

		if (empty($line->fk_bank)) {
			print '<td class="center"><input class="right width75" name="interets'.$i.'" id="interets'.$i.'" value="'.price($int).'" ech="'.$i.'"><input type="hidden" name="hi_interets'.$i.'" id="hi_interets'.$i.'" value="'.$int.'"></td>';
			print '<td class="center"><input class="right width75" name="amort'.$i.'" id="amort'.$i.'" value="'.price($amort).'" ech="'.$i.'"><input type="hidden" name="hi_amort'.$i.'" id="hi_amort'.$i.'" value="'.$amort.'"></td>';
			print '<td class="center"><input class="right width75" name="mens'.$i.'" id="mens'.$i.'" value="'.price($mens).'" ech="'.$i.'"><input type="hidden" name="hi_mens'.$i.'" id="hi_mens'.$i.'" value="'.$mens.'"></td>';
		} else {
			print '<td class="center amount" id="interets'.$i.'">'.price($int, 0, '', 1, -1, -1, $conf->currency).'</td><input type="hidden" name="interets'.$i.'" id="interets'.$i.'" value="'.$int.'"><input type="hidden" name="hi_interets'.$i.'" id="hi_interets'.$i.'" value="'.$int.'">';
			print '<td class="center amount" id="amort'.$i.'">'.price($amort, 0, '', 1, -1, -1, $conf->currency).'</td><input type="hidden" name="amort'.$i.'" id ="amort'.$i.'" value="'.$amort.'"><input type="hidden" name="hi_amort'.$i.'" id="hi_amort'.$i.'" value="'.$amort.'">';
			print '<td class="center amount" id="mens'.$i.'">'.price($mens, 0, '', 1, -1, -1, $conf->currency).'</td><input type="hidden" name="mens'.$i.'" id ="mens'.$i.'" value="'.$mens.'"><input type="hidden" name="hi_mens'.$i.'" id="hi_mens'.$i.'" value="'.$mens.'">';
		}

		print '<td class="center amount" id="capital'.$i.'">'.price($cap_rest, 0, '', 1, -1, -1, $conf->currency).'</td><input type="hidden" name="hi_capital'.$i.'" id ="hi_capital'.$i.'" value="'.$cap_rest.'">';
		print '<td class="center">';
		if (!empty($line->fk_bank)) {
			print $langs->trans('Paid');
			if (!empty($line->fk_payment_loan)) {
				print '&nbsp;<a href="'.DOL_URL_ROOT.'/loan/payment/card.php?id='.$line->fk_payment_loan.'">('.img_object($langs->trans("Payment"), "payment").' '.$line->fk_payment_loan.')</a>';
			}
		} elseif (!$printed) {
			print '<a class="butAction smallpaddingimp" href="'.DOL_URL_ROOT.'/loan/payment/payment.php?id='.$object->id.'&action=create">'.$langs->trans('DoPayment').'</a>';
			$printed = true;
		}
		print '</td>';
		print '</tr>'."\n";
		$i++;
		$capital = $cap_rest;
	}
}

print '<tr class="liste_total">';
print '<td class="center">'.$langs->trans("Total").'</td>';
print '<td></td>';
print '<td class="center amount" id="total_insurance">'.price($total_insurance, 0, '', 1, -1, -1, $conf->currency).'</td>';
print '<td class="center amount" id="total_interest">'.price($total_interest, 0, '', 1, -1, -1, $conf->currency).'</td>';
print '<td class="center amount" id="total_amort">'.price($total_amort, 0, '', 1, -1, -1, $conf->currency).'</td>';
print '<td class="center amount" id="total_mens">'.price($total_mens, 0, '', 1, -1, -1, $conf->currency).'</td>';
print '<td class="center amount" id="total_capital">'.price(0, 0, '', 1, -1, -1, $conf->currency).'</td>';
if (count($echeances->lines) > 0) {
	print '<td></td>';
}
print '</tr>'."\n";

print '</table>';
print '</div>';

print '</br>';

if (count($echeances->lines) == 0) {
	$label = $langs->trans("Create");
} else {
	$label = $langs->trans("Save");
}
print '<div class="center"><input type="submit" class="button button-add" value="'.$label.'" '.(($pay_without_schedule == 1) ? 'disabled title="'.$langs->trans('CantUseScheduleWithLoanStartedToPaid').'"' : '').'title=""></div>';
print '</form>';

// End of page
llxFooter();
$db->close();
