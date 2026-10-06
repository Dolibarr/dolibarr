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

if ($action == 'recalculate' && $permissiontoadd) {
	$newrate = GETPOSTFLOAT('newrate');
	$keep = (GETPOST('keep', 'aZ09') === 'payment' ? 'payment' : 'term');
	$datefrom = dol_mktime(0, 0, 0, GETPOSTINT('recalcfrommonth'), GETPOSTINT('recalcfromday'), GETPOSTINT('recalcfromyear'));
	$oldrate = (float) $object->rate;
	if (GETPOST('newrate') === '' || $newrate < 0 || empty($datefrom)) {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("LoanNewRate")), null, 'errors');
	} else {
		$db->begin();
		$error = 0;
		if (count($echeances->lines) == 0) {
			// No schedule: only the rate of the loan changes
			$object->rate = $newrate;
			if ($object->update($user) < 0) {
				$error++;
				setEventMessages($object->error, $object->errors, 'errors');
			} elseif (loanRecordChange($db, $user, $object->id, 'rate', $datefrom, $oldrate, $newrate, '', 0, 0, $object->nbterm, $object->nbterm) < 0) {
				$error++;
				setEventMessages($db->lasterror(), null, 'errors');
			}
		} else {
			// First unpaid payment on or after the date; capital left before it
			$from = -1;
			$capital = (float) $object->capital;
			foreach ($echeances->lines as $k => $l) {
				if (empty($l->fk_bank) && $l->datep >= $datefrom) {
					$from = $k;
					break;
				}
				$capital -= (float) $l->amount_capital;
			}
			$nbtermold = (float) $object->nbterm;
			$res = loanRecalculateSchedule($db, $user, $object, $echeances->lines, $from, $capital, $newrate, $keep);
			if ($res['error']) {
				$error++;
				setEventMessages($langs->trans($res['error']), null, 'errors');
			} elseif (loanRecordChange($db, $user, $object->id, 'rate', (int) $echeances->lines[$from]->datep, $oldrate, $newrate, $keep, $res['payment_old'], $res['payment_new'], $nbtermold, $res['nbterm_new']) < 0) {
				$error++;
				setEventMessages($db->lasterror(), null, 'errors');
			}
		}
		if ($error) {
			$db->rollback();
		} else {
			$db->commit();
			setEventMessages($langs->trans("LoanRecalcDone"), null);
		}
		$object->fetch($object->id);
		$echeances = new LoanSchedule($db);
		$echeances->fetchAll($object->id);
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
			$proj->fetch((int) $object->fk_project);
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
				frequency: <?php echo (int) $object->frequency; ?>,
				interest_basis: <?php echo (int) $object->interest_basis; ?>,
				datestart: <?php echo (int) $object->datestart; ?>,
				balloon: <?php echo (float) $object->balloon_amount; ?>,
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
	print '<span class="opacitymedium">'.$langs->trans((int) $object->frequency == 12 ? "GracePeriodMonths" : "GracePeriodTerms").': </span>';
	print '<input type="number" id="grace_period_months" min="1" max="'.max(1, $object->nbterm - 1).'" value="1" class="width50 right"> ';
	print '<input type="button" id="btn_apply_grace_period" class="button valignmiddle" value="'.$langs->trans("Apply").'">';
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
print '<th class="center">'.loanChargeLabel($object->charge_type, $langs).'</th>';
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
	list($insurance, $regulInsurance) = loanChargePerPayment($object->insurance_amount, $object->charge_per_payment, $object->nbterm);

	while ($i < $object->nbterm + 1) {
		$mens = price2num($echeances->calcMonthlyPayments($capital, $object->rate / 100, $object->nbterm - $i + 1, $object->frequency, $object->interest_basis, $object->balloon_amount), 'MT');
		$int = ($capital * loanPeriodRate($object->rate, $object->frequency, $object->interest_basis, ($object->interest_basis ? loanPeriodDays($object->datestart, $i, $object->frequency) : 0))) / 100;
		$int = price2num($int, 'MT');
		$amort = price2num((float) $mens - (float) $int, 'MT');
		$insu = ((float) $insurance + (($i == 1) ? (float) $regulInsurance : 0));
		$cap_rest = (float) price2num((float) $capital - (float) $amort, 'MT');

		// Adjust rounding difference on last term (with daily interest, periods are not all the same
		// length, so the last term always settles what is left)
		// A balloon is paid with the last term, which then repays all the capital left.
		if ($i == $object->nbterm && ($object->interest_basis || (float) $object->balloon_amount > 0 || abs($cap_rest) <= 0.05) && $capital > 0) {
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
		print '<td class="center" id ="date'.$i.'"><input type="hidden" name="hi_date'.$i.'" id ="hi_date'.$i.'" value="'.loanTermDate($object->datestart, $i - 1, $object->frequency).'">'.dol_print_date(loanTermDate($object->datestart, $i - 1, $object->frequency), 'day').'</td>';
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
	list($insurance, $regulInsurance) = loanChargePerPayment($object->insurance_amount, $object->charge_per_payment, $object->nbterm);
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

if ((float) $object->balloon_amount > 0) {
	print '<div class="opacitymedium margintoponly">'.$langs->trans("LoanBalloonInLastPayment", price($object->balloon_amount, 0, '', 1, -1, -1, $conf->currency)).'</div>';
}

print '<br>';

if (count($echeances->lines) == 0) {
	$label = $langs->trans("Create");
} else {
	$label = $langs->trans("Save");
}
print '<div class="center"><input type="submit" class="button button-add" value="'.$label.'" '.(($pay_without_schedule == 1) ? 'disabled title="'.$langs->trans('CantUseScheduleWithLoanStartedToPaid').'"' : '').'title=""></div>';
print '</form>';

// Recalculate the unpaid payments from a date (rate change, or keep the repayment / the term)
if ($permissiontoadd && $object->paid != Loan::STATUS_PAID) {
	$firstunpaid = 0;
	foreach ($echeances->lines as $l) {
		if (empty($l->fk_bank)) {
			$firstunpaid = (int) $l->datep;
			break;
		}
	}
	print '<br>';
	print load_fiche_titre($langs->trans("LoanRecalculate"), '', '');
	print '<form name="recalculate" action="'.$_SERVER["PHP_SELF"].'" method="POST">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="loanid" value="'.$loanid.'">';
	print '<input type="hidden" name="action" value="recalculate">';
	print '<table class="border centpercent">';
	print '<tr><td class="titlefield">'.$langs->trans("LoanRecalcFrom").'</td><td>'.$form->selectDate($firstunpaid ? $firstunpaid : dol_now(), 'recalcfrom', 0, 0, 0, 'recalculate', 1, 0).'</td></tr>';
	print '<tr><td>'.$langs->trans("LoanNewRate").'</td><td><input name="newrate" size="5" value="'.dol_escape_htmltag((string) $object->rate).'"> %</td></tr>';
	if (count($echeances->lines) > 0) {
		print '<tr><td>'.$langs->trans("LoanRecalcKeep").'</td><td>'.$form->selectarray('keep', array('term' => $langs->trans("LoanRecalcKeepTerm"), 'payment' => $langs->trans("LoanRecalcKeepPayment")), 'term').'</td></tr>';
	}
	print '</table>';
	print '<div class="opacitymedium margintoponly">'.$langs->trans(count($echeances->lines) > 0 ? "LoanRecalcHelp" : "LoanRecalcHelpNoSchedule").'</div>';
	print '<div class="center"><input type="submit" class="button" value="'.$langs->trans("LoanRecalculateButton").'"></div>';
	print '</form>';
}

// History of the changes
$changes = loanFetchChanges($db, $object->id);
if (count($changes)) {
	print '<br>';
	print load_fiche_titre($langs->trans("LoanChangesHistory"), '', '');
	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre"><th>'.$langs->trans("Date").'</th><th>'.$langs->trans("LoanChangeReason").'</th><th>'.$langs->trans("LoanChangeFrom").'</th>';
	print '<th class="right">'.$langs->trans("Rate").'</th><th>'.$langs->trans("LoanRecalcKeep").'</th><th class="right">'.$langs->trans("Amount").'</th><th class="right">'.$langs->trans("Nbterms").'</th><th>'.$langs->trans("User").'</th></tr>';
	foreach ($changes as $c) {
		$u = new User($db);
		$u->fetch((int) $c->fk_user_author);
		print '<tr class="oddeven">';
		print '<td class="nowraponall">'.dol_print_date($c->datec, 'dayhour').'</td>';
		print '<td>'.$langs->trans($c->reason == 'payment' ? 'LoanChangeReasonPayment' : 'LoanChangeReasonRate');
		if (!empty($c->fk_payment_loan)) {
			print ' <a href="'.DOL_URL_ROOT.'/loan/payment/card.php?id='.((int) $c->fk_payment_loan).'">'.img_object($langs->trans("Payment"), "payment").' '.((int) $c->fk_payment_loan).'</a>';
		}
		print '</td>';
		print '<td class="nowraponall">'.dol_print_date($c->date_change, 'day').'</td>';
		print '<td class="right nowraponall">'.price($c->rate_old).'% &rarr; '.price($c->rate_new).'%</td>';
		print '<td>'.($c->keep_mode == 'payment' ? $langs->trans("LoanRecalcKeepPayment") : ($c->keep_mode == 'term' ? $langs->trans("LoanRecalcKeepTerm") : '')).'</td>';
		print '<td class="right nowraponall">'.($c->keep_mode ? price($c->payment_old, 0, $langs, 1, -1, -1, $conf->currency).' &rarr; '.price($c->payment_new, 0, $langs, 1, -1, -1, $conf->currency) : '').'</td>';
		print '<td class="right nowraponall">'.((float) $c->nbterm_old != (float) $c->nbterm_new ? ((float) $c->nbterm_old).' &rarr; '.((float) $c->nbterm_new) : ((float) $c->nbterm_new)).'</td>';
		print '<td>'.($u->id > 0 ? $u->getNomUrl(-1) : '').'</td>';
		print '</tr>';
	}
	print '</table>';
	print '</div>';
}

// End of page
llxFooter();
$db->close();
