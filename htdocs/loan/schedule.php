<?php
/* Copyright (C) 2017      Franck Moreau        <franck.moreau@theobald.com>
 * Copyright (C) 2018      Alexandre Spangaro   <aspangaro@open-dsi.fr>
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

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/loan/class/loan.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/loan.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/loan/class/loanschedule.class.php';
require_once DOL_DOCUMENT_ROOT.'/loan/class/paymentloan.class.php';

/**
 * @var DoliDB $db
 * @var Conf $conf
 * @var User $user
 * @var HookManager $hookmanager
 * @var Translate $langs
 */

$loanid = GETPOST('loanid', 'int');
$action = GETPOST('action', 'aZ09');

$object = new Loan($db);
$object->fetch($loanid);

// Load translation files required by the page
$langs->loadLangs(array('compta', 'bills', 'loan'));

$title = $langs->trans('Loan') . ' - ' . $langs->trans('Card');
$help_url = 'EN:Module_Loan|FR:Module_Emprunt';
$arrayofjs = array('/loan/js/loan.js');
$arrayofcss = array('/loan/css/loan.css');
llxHeader('', $title, $help_url, '', 0, 0, $arrayofjs, $arrayofcss);

/** @var Form $form  Defined globally during call to left_menu() in llxHeader() */

$head=loan_prepare_head($object);
dol_fiche_head($head, 'FinancialCommitment', $langs->trans('Loan'), -1, 'bill');

$linkback = '<a href="' . DOL_URL_ROOT . '/loan/list.php?restore_lastsearch_values=1">' . $langs->trans('BackToList') . '</a>';

$morehtmlref='<div class="refidno">';
// Ref loan
$morehtmlref.=$form->editfieldkey('Label', 'label', $object->label, $object, $user->rights->loan->write, 'string', '', 0, 1);
$morehtmlref.=$form->editfieldval('Label', 'label', $object->label, $object, $user->rights->loan->write, 'string', '', null, null, '', 1);
// Project
if (! empty($conf->projet->enabled))
{
	$langs->loadLangs(array('projects'));
	$morehtmlref.='<br>'.$langs->trans('Project') . ' ';
	if ($user->rights->loan->write)
	{
		if ($action != 'classify')
			$morehtmlref.='<a href="' . $_SERVER['PHP_SELF'] . '?action=classify&amp;id=' . $object->id . '">' . img_edit($langs->transnoentitiesnoconv('SetProject')) . '</a> : ';
		if ($action == 'classify') {
			require_once DOL_DOCUMENT_ROOT . '/core/class/html.formprojet.class.php';
			$formproject = new FormProjets($db);
			$maxlength = 16;
			//$morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'projectid', 0, 0, 1, 1);
			$morehtmlref.='<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
			$morehtmlref.='<input type="hidden" name="action" value="classin">';
			$morehtmlref.='<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			$morehtmlref.=$formproject->select_projects($object->socid, $object->fk_project, 'projectid', $maxlength, 0, 1, 0, 1, 0, 0, '', 1);
			$morehtmlref.='<input type="submit" class="button valignmiddle" value="'.$langs->trans('Modify').'">';
			$morehtmlref.='</form>';
		} else {
			$morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'none', 0, 0, 0, 1);
		}
	} else {
		if (! empty($object->fk_project)) {
			$proj = new Project($db);
			$proj->fetch($object->fk_project);
			$morehtmlref.='<a href="'.DOL_URL_ROOT.'/projet/card.php?id=' . $object->fk_project . '" title="' . $langs->trans('ShowProject') . '">';
			$morehtmlref.=$proj->ref;
			$morehtmlref.='</a>';
		} else {
			$morehtmlref.='';
		}
	}
}
$morehtmlref.='</div>';
$morehtmlright = '';
dol_banner_tab($object, 'id', $linkback, 1, 'rowid', 'ref', $morehtmlref, '', 0, '', $morehtmlright);

if ($action == 'save') {
    $i=1;
	$installmentsFromPost = GETPOST('installment', 'array');
	$nbEcheancierCreated = 0;
	$object->deleteSchedule();
	foreach ($installmentsFromPost as $p => $installmentFromPost) {
		if ($object->nbPeriods === 0) break; // should never be the case
		// make sure all values are properly typed
		$p = (int) $p;
		$installmentFromPost['ppmt'] = parseLocalizedNumberStr($installmentFromPost['ppmt']);
		$installmentFromPost['ipmt'] = parseLocalizedNumberStr($installmentFromPost['ipmt']);
		$installmentFromPost['fv']   = parseLocalizedNumberStr($installmentFromPost['fv']);

		$echeancier = new LoanSchedule($db);
        $echeancier->fk_loan = $object->id;
        $echeancier->datec = dol_now();
        $echeancier->tms = dol_now();
        $echeancier->datep = $object->getDateOfPeriod($p);
        $echeancier->amount_capital = $installmentFromPost['ppmt'];
		$echeancier->amount_interest = $installmentFromPost['ipmt'];
		$echeancier->amount_insurance = $object->insurance_amount / $object->nbPeriods;
        $echeancier->fk_typepayment = 3;
//        $echeancier->fk_bank = $object->fk_bank;
        $echeancier->fk_user_creat = $user->id;
        $echeancier->fk_user_modif = $user->id;
		$nbEcheancierCreated += ($echeancier->create($user) > 0);
	}
	if ($nbEcheancierCreated !== count($installmentsFromPost)) {
		setEventMessages($echeancier->error, $echeancier->errors, 'errors');
	} else {
		setEventMessage($langs->trans('ScheduleSaved'));
	}
}

$echeancier = new LoanSchedule($db);
$echeancier->fetchAll($object->id);

$var = false;
$var = ! $var;

$capital = $object->capital;
$futureValue = $object->capital;
$insurance = 0;
if ($object->nbPeriods) {
	$insurance = $object->insurance_amount / $object->nbPeriods;
}
$insurance = price2num($insurance, 'MT');
$regulInsurance = price2num($object->insurance_amount - ($insurance * $object->nbPeriods));
$periodicInterestRate = getPeriodicRate($object->rate, $object->periodicity);
$jsContext = array(
	'ajaxURL' => DOL_URL_ROOT . '/loan/ajax/loan.ajax.php',
	'loan' => $object->jsonSerialize(),
	'MAIN_LANG_DEFAULT' => $conf->global->MAIN_LANG_DEFAULT,
	'nbDecimals' => 2, // for rounding
);
$cssPaymentColumnVisibility = $object->hasEcheancier() ? 'table-cell' : 'none';
echo '<style>:root {--payment-column-visibility: ' . $cssPaymentColumnVisibility . '; }</style>';
echo '<link rel="stylesheet" type="text/css" href="' . DOL_URL_ROOT . '/loan/css/loan.css" />';
echo '<script type="application/javascript">LoanModule.initLoanSchedule(' . json_encode($jsContext) . ')</script>';

print '<form class="loanschedule" action="' . $_SERVER['PHP_SELF'] . '" method="POST">';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
print '<input type="hidden" name="loanid" value="' . $loanid . '">';

$columns = array('period', 'date', 'insurance', 'ppmt', 'ipmt', 'pmt', 'fv', 'payment');
$colspan = count($columns);
print '<table class="liste loanschedule">';
print '<colgroup>' . implode('', array_map(function ($colon) {
		return '<col class="' . $colon . '">';
	}, $columns)) . '</colgroup>';

print '<tr class="liste_titre"><th class="center" colspan="'.$colspan.'">'.$langs->trans('FinancialCommitment').'</th></tr>';

print '<tr class="liste_titre">'
	. '<th class="period">'    . $langs->trans('Period') . '</th>'
	. '<th class="date">'      . $langs->trans('Date') . '</th>'
	. '<th class="insurance">' . $langs->trans('Insurance') . '</th>'
	. '<th class="ppmt">'      . $langs->trans('Principal') . '</th>'
	. '<th class="ipmt">'      . $langs->trans('InterestAmount') . '</th>'
	. '<th class="pmt">'       . $langs->trans('Amount') . '</th>'
	. '<th class="fv">'        . $langs->trans('CapitalRemain')
	. '    <br>('.price($object->capital, 0, '', 1, -1, -1, $object->currency).')'
	. '    </th>'
	. '<th class="payment">'.$langs->trans('DoPayment').'</th>' // hidden by CSS as long as the loan schedule is not saved
	. '</tr>'."\n";

// Nouvel échéancier
if ($object->nbPeriods > 0)
{
	// FIXME: gestion des erreurs d'arrondis
	//        pour info, avant la réécriture, l'erreur d'arrondi sur l'assurance était calculée ainsi:
	//        $regulInsurance = price2num($object->insurance_amount - ($insurance * $object->nbPeriods))
	//        et elle était ajouté au montant d'assurance de la première échéance
	if ($object->hasEcheancier()) {
		$installments = loadScheduleLinesToInstallments($object, $echeancier->lines);
	} else {
		$installments = computeAmortizationSchedule(
			$periodicInterestRate,
			$object->nbPeriods,
			$object->capital,
			$object->future_value,
			$object->calc_mode === Loan::IN_ADVANCE
		);
	}

	$paymentBtnShown = false;
	foreach ($installments as $i => $installment) {

		// FIXME: le paiement devrait être intégré à Installment
		if ($object->hasEcheancier() && $line = $echeancier->lines[$i]) {
			if ($line->datep > dol_now() && empty($line->fk_bank)) {
				$isPaid = false;
			} else {
				$isPaid = true;
			}
		}
		$paymentCell = '';
		if (!$isPaid && $object->hasEcheancier() && !$paymentBtnShown) {
			$paymentCell = '<a class="butAction" href="'.DOL_URL_ROOT.'/loan/payment/payment.php?id='.$object->id.'&amp;action=create&line_id='.$line->id.'">'.$langs->trans('DoPayment').'</a>';
			$paymentBtnShown = true;
		} elseif ($isPaid) {
			$paymentCell = '☑'; // FIXME: afficher le montant du règlement à la place ? regarder comment c'était avant
		}
		$tr = getInstallmentTableRow($object, $installment, $isPaid);
		$tr = str_replace('__PAYMENT__', $paymentCell, $tr);
		echo $tr;
	}
}

print '</table>';
echo '&#8505;&#65039; ' . $langs->trans('AmountsAreInCurrency', $object->currency); // FIXME: utiliser méthode dolibarrienne pour afficher le symbole
print '</br>';
print '</br>';
if (count($echeancier->lines)==0) $label = $langs->trans('Create');
else $label = $langs->trans('Save');
print '<div class="center"><button name="action" value="save" class="button" type="submit">' . $label . '</div>';
print '</form>';


// End of page
llxFooter();
$db->close();
