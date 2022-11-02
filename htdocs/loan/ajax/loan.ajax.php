<?php
/* TVI
 * Copyright (C) 2015	Florian HENRY 		<florian.henry@open-concept.pro>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/*
Back-end ajax pour répondre aux actions suivantes:
 - 'getAmortizationSchedule': recalculer les échéances en ajax depuis `schedule.php` lorsque
   le champ de saisie "montant" d'une échéance perd le focus (en gros quand on y saisit une
   nouvelle valeur - action js définie dans `loan.js`).
   `loan.ajax.php` reçoit 2 paramètres composés: 'loan' (informations de l'emprunt) et
   'installment (informations de l'échéance) et renvoie un échéancier partiel (qui démarre à
   l'échéance qui vient d'être modifiée en tenant compte de cette modification).
   TODO: vérifier la règle mathématique sur la répartition intérêt / amortissement de
         l'échéance entrée manuellement

 - (aucune autre action pour le moment)
*/

declare(strict_types=1);
if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', '1'); // Disables token renewal
if (! defined('NOREQUIREMENU')) define('NOREQUIREMENU', '1');
if (! defined('NOREQUIREAJAX')) define('NOREQUIREAJAX', '1');

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/loan/class/loanschedule.class.php';
require_once DOL_DOCUMENT_ROOT . '/loan/class/loan.class.php';
require_once DOL_DOCUMENT_ROOT . '/loan/lib/loan.lib.php';
/**
 * @var DoliDB $db
 * @var Conf $conf
 * @var Translate $langs
 * @var bool $dolibarr_main_prod;
 */


$ajaxResponse = array(
	'debug' => array(),
);
$errorsWarnings = '';
ob_start(); // inhibition of any uncontrolled output such as warnings / errors (which would not be valid JSON)

$action = GETPOST('action', 'aZ09');

switch($action) {
	case 'getAmortizationSchedule':
		// TODO: consistency checks (for instance if $manualPmt is greater than future value)

		/*
		Terminologie:
			term                                            = terme
			capital                                         = capital
			rate                                            = taux = Satz
			instal(l)ment amount / scheduled payment amount = montant d'échéance
			period                                          = période
			installment frequency                           = périodicité des paiements
			instalment (UK, CA, AU) / installment (US)      = échéance, paiement échelonné / périodique
			Periodicity                                     = périodicité
			compounded interest                             = intérêt composé
			in advance                                      = à échoir
			in arrear                                       = échu
			principal payment                               = amortissement
			balance                                         = solde (dans notre cas: = capital restant dû)
		 */

		// construct loan and installment objects from the query parameters
		$loanArray = GETPOST('loan', 'array');
		$loan = new Loan($db);
		foreach ($loanArray as $attrName => $value) {
			$loan->{$attrName} = $value;
		}
		$installmentArray = GETPOST('installment', 'array');
		$installment = new Installment(
			$installmentArray['p'],
			$installmentArray['pmt'],
			$installmentArray['ppmt'],
			$installmentArray['ipmt'],
			$installmentArray['pv'],
			$installmentArray['fv'],
		);

		$periodicRate = getPeriodicRate($loan->rate, $loan->periodicity);

		// we "pretend" that the loan starts after the installment that was provided
		$nbPeriods = $loan->nbPeriods - $installment->p;

		// the payment that was entered manually
		$manualPmt = (double) GETPOST('manualPmt', 'int');

		// TODO: check how we recompute the interest / principal breakdown of the manual payment
		if ($installment->pmt) $interestRatio = $installment->ipmt / $installment->pmt;
		else $interestRatio = 0;

		$installment->pmt = $manualPmt;
		$installment->ipmt = $interestRatio * $manualPmt;
		$installment->ppmt = $manualPmt - $installment->ipmt;

		$installment->fv = $installment->pv - $installment->ppmt;

		$pvNext = $installment->fv;

		// on calcul un nouvel échéancier partiel (qui démarre à l'échéance qui suit celle
		// dont on a modifié le montant)
		$recomputedInstallments = computeAmortizationSchedule(
			$periodicRate,
			$nbPeriods,
			$pvNext,
			(double) $loan->future_value,
			(int) $loan->calc_mode === Loan::IN_ADVANCE
		);

		// on renumérote les échéances de l'échéancier partiel pour pouvoir le réintégrer dans
		// l'échéancier d'origine
		array_walk($recomputedInstallments, function ($inst, $i) use ($installment) {
			$inst->p += $installment->p;
		});

		// on inclut l'échéance modifiée manuellement dans la réponse car le front-end devra
		// modifier aussi ses valeurs à lui
		$ajaxResponse['installments'] = array_merge(
			array($installment),
			$recomputedInstallments
		);

		top_httphead('application/json');

		$errorsWarnings = ob_get_clean(); // retrieval of any unplanned output for debug purposes

		break;
	default:
		$ajaxResponse['debug'][] = "Unhandled action: '$action'";
		break;
}

if ($errorsWarnings && $dolibarr_main_prod) {
	$ajaxResponse['debug'][] = 'Unplanned output retrieved: ' . $errorsWarnings;
}

echo json_encode($ajaxResponse);
exit;
