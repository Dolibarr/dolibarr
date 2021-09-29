<?php
/* Copyright (C) 2014-2016	Alexandre Spangaro	<aspangaro@open-dsi.fr>
 * Copyright (C) 2015-2020	Frederic France     <frederic.france@netlogic.fr>
 * Copyright (C) 2020       Maxime DEMAREST     <maxime@indelog.fr>
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
 *      \file       htdocs/core/lib/loan.lib.php
 *      \ingroup    loan
 *      \brief      Library for loan module
 */


/**
 * Prepare array with list of tabs
 *
 * @param   Object	$object		Object related to tabs
 * @return  array				Array of tabs to show
 */
function loan_prepare_head($object)
{
	global $db, $langs, $conf;

	$tab = 0;
	$head = array();

	$head[$tab][0] = DOL_URL_ROOT.'/loan/card.php?id='.$object->id;
	$head[$tab][1] = $langs->trans('Card');
	$head[$tab][2] = 'card';
	$tab++;

	$head[$tab][0] = DOL_URL_ROOT.'/loan/schedule.php?loanid='.$object->id;
	$head[$tab][1] = $langs->trans('FinancialCommitment');
	$head[$tab][2] = 'FinancialCommitment';
	$tab++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname);   												to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $tab, 'loan');

	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
	$upload_dir = $conf->loan->dir_output."/".dol_sanitizeFileName($object->ref);
	$nbFiles = count(dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$'));
	$nbLinks = Link::count($db, $object->element, $object->id);
	$head[$tab][0] = DOL_URL_ROOT.'/loan/document.php?id='.$object->id;
	$head[$tab][1] = $langs->trans("Documents");
	if (($nbFiles + $nbLinks) > 0) {
		$head[$tab][1] .= '<span class="badge marginleftonlyshort">'.($nbFiles + $nbLinks).'</span>';
	}
	$head[$tab][2] = 'documents';
	$tab++;

	if (empty($conf->global->MAIN_DISABLE_NOTES_TAB)) {
		$nbNote = (empty($object->note_private) ? 0 : 1) + (empty($object->note_public) ? 0 : 1);
		$head[$tab][0] = DOL_URL_ROOT."/loan/note.php?id=".$object->id;
		$head[$tab][1] = $langs->trans("Notes");
		if ($nbNote > 0) {
			$head[$tab][1] .= '<span class="badge marginleftonlyshort">'.$nbNote.'</span>';
		}
		$head[$tab][2] = 'note';
		$tab++;
	}

	$head[$tab][0] = DOL_URL_ROOT.'/loan/info.php?id='.$object->id;
	$head[$tab][1] = $langs->trans("Info");
	$head[$tab][2] = 'info';
	$tab++;

	complete_head_from_modules($conf, $langs, $object, $head, $tab, 'loan', 'remove');

	return $head;
}

/**
 * Calculate remaining loan mensuality and interests
 *
 * @param   float   $mens				Value of this mensuality (interests include, set 0 if we don't paid interests for this mensuality)
 * @param   float   $capital    		Remaining capital for this mensuality
 * @param   float   $rate				Loan rate
 * @param   int     $numactualloadterm	Actual loan term
 * @param   int   	$nbterm  			Total number of term for this loan
 * @return  array						Array with remaining capital, interest, and mensuality for each remaining terms
 */
function loanCalcMonthlyPayment($mens, $capital, $rate, $numactualloadterm, $nbterm)
{
	global $conf, $db;
	require_once DOL_DOCUMENT_ROOT.'/loan/class/loanschedule.class.php';
	$object = new LoanSchedule($db);
	$output = array();

	// Sanitize data in case of
	$mens = price2num($mens);
	$capital = price2num($capital);
	$rate = price2num($rate);
	$numactualloadterm = ((int) $numactualloadterm);
	$nbterm = ((int) $nbterm);

	// If mensuality is 0 we don't pay interests and remaining capital not modified
	if ($mens == 0) {
		$int = 0;
		$cap_rest = $capital;
	} else {
		$int = ($capital * ($rate / 12));
		$int = round($int, 2, PHP_ROUND_HALF_UP);
		$cap_rest = round($capital - ($mens - $int), 2, PHP_ROUND_HALF_UP);
	}
	$output[$numactualloadterm] = array('cap_rest'=>$cap_rest, 'cap_rest_str'=>price($cap_rest, 0, '', 1, -1, -1, $conf->currency), 'interet'=>$int, 'interet_str'=>price($int, 0, '', 1, -1, -1, $conf->currency), 'mens'=>$mens);

	$numactualloadterm++;
	$capital = $cap_rest;
	while ($numactualloadterm <= $nbterm) {
		$mens = round($object->calcMonthlyPayments($capital, $rate, $nbterm - $numactualloadterm + 1), 2, PHP_ROUND_HALF_UP);

		$int = ($capital * ($rate / 12));
		$int = round($int, 2, PHP_ROUND_HALF_UP);
		$cap_rest = round($capital - ($mens - $int), 2, PHP_ROUND_HALF_UP);

		$output[$numactualloadterm] = array(
			'cap_rest' => $cap_rest,
			'cap_rest_str' => price($cap_rest, 0, '', 1, -1, -1, $conf->currency),
			'interet' => $int,
			'interet_str' => price($int, 0, '', 1, -1, -1, $conf->currency),
			'mens' => $mens,
		);

		$capital = $cap_rest;
		$numactualloadterm++;
	}

	return $output;
}
