<?php
/* Copyright (C) 2017      Alexandre Spangaro   <aspangaro@open-dsi.fr>
 * Copyright (C) 2024		Frédéric France			<frederic.france@free.fr>
 * Copyright (C) 2024		MDW						<mdeweerd@users.noreply.github.com>
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
 *	\file			htdocs/stripe/lib/stripe.lib.php
 *	\ingroup		stripe
 *  \brief			Library for common stripe functions
 */

require_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';

/**
 *  Define head array for tabs of stripe tools setup pages
 *
 * @return	array<array{0:string,1:string,2:string}>	Array of tabs to show
 */
function stripeadmin_prepare_head()
{
	global $langs, $conf;

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT."/stripe/admin/stripe.php";
	$head[$h][1] = $langs->trans("Stripe");
	$head[$h][2] = 'stripeaccount';
	$h++;

	$object = new stdClass();

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname);   												to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'stripeadmin');

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'stripeadmin', 'remove');

	return $head;
}


/**
 * Show footer of company in HTML pages
 *
 * @param   Societe		$fromcompany	Third party
 * @param   Translate	$langs			Output language
 * @return	void
 */
function html_print_stripe_footer($fromcompany, $langs)
{
	global $conf;

	// Juridical status
	$line1 = "";
	if ($fromcompany->forme_juridique_code) {
		$line1 .= ($line1 ? " - " : "").getFormeJuridiqueLabel((string) $fromcompany->forme_juridique_code);
	}
	// Capital
	if ($fromcompany->capital) {
		$line1 .= ($line1 ? " - " : "").$langs->transnoentities("CapitalOf", $fromcompany->capital)." ".$langs->transnoentities("Currency".$conf->currency);
	}

	$reg = array();

	// Prof Id 1
	if ($fromcompany->idprof1 && ($fromcompany->country_code != 'FR' || !$fromcompany->idprof2)) {
		$field = $langs->transcountrynoentities("ProfId1", $fromcompany->country_code);
		if (preg_match('/\((.*)\)/i', $field, $reg)) {
			$field = $reg[1];
		}
		$line1 .= ($line1 ? " - " : "").$field.": ".$fromcompany->idprof1;
	}
	// Prof Id 2
	if ($fromcompany->idprof2) {
		$field = $langs->transcountrynoentities("ProfId2", $fromcompany->country_code);
		if (preg_match('/\((.*)\)/i', $field, $reg)) {
			$field = $reg[1];
		}
		$line1 .= ($line1 ? " - " : "").$field.": ".$fromcompany->idprof2;
	}

	// Second line of company infos
	$line2 = "";
	// Prof Id 3
	if ($fromcompany->idprof3) {
		$field = $langs->transcountrynoentities("ProfId3", $fromcompany->country_code);
		if (preg_match('/\((.*)\)/i', $field, $reg)) {
			$field = $reg[1];
		}
		$line2 .= ($line2 ? " - " : "").$field.": ".$fromcompany->idprof3;
	}
	// Prof Id 4
	if ($fromcompany->idprof4) {
		$field = $langs->transcountrynoentities("ProfId4", $fromcompany->country_code);
		if (preg_match('/\((.*)\)/i', $field, $reg)) {
			$field = $reg[1];
		}
		$line2 .= ($line2 ? " - " : "").$field.": ".$fromcompany->idprof4;
	}
	// IntraCommunautary VAT
	if ($fromcompany->tva_intra != '') {
		$line2 .= ($line2 ? " - " : "").$langs->transnoentities("VATIntraShort").": ".$fromcompany->tva_intra;
	}

	print '<br><br><hr>'."\n";
	print '<div class="center"><span style="font-size: 10px;">'."\n";
	print $fromcompany->name.'<br>';
	print $line1.'<br>';
	print $line2;
	print '</span></div>'."\n";
}
