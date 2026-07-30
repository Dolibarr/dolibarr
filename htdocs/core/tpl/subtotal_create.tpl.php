<?php
/* Copyright (C) 2014-2017  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2024-2026	MDW						<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024-2026  Frédéric France         <frederic.france@free.fr>
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
 * or see https://www.gnu.org/
 */

/**
 * @var CommonObject $this
 * @var Propal|Commande|Facture|FactureRec|Expedition|SupplierProposal|CommandeFournisseur|FactureFournisseur|Fichinter $object
 * @var PropaleLigne|ContratLigne|OrderLine|FactureLigne|ExpeditionLigne|DeliveryLine|CommandeFournisseurLigne|SupplierInvoiceLine|SupplierProposalLine|FichinterLigne $line
 * @var Form $form
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 *
 * @var array<int,string> $depth_array
 * @var array<string,string> $titles
 * @var string $type
 */

'
@phan-var-force Propal|Commande|Facture|FactureRec|Expedition|SupplierProposal|CommandeFournisseur|FactureFournisseur $this
@phan-var-force ?array<int,string> $depth_array
@phan-var-force ?array<string,string> $titles
@phan-var-force string $type
';

$depth_array = $depth_array ?? array();
$titles = $titles ?? array();

if ($type == 'subtotal' && empty($titles)) {
	setEventMessages("NoTitleError", null, 'errors');
	return;
}

$formquestion = array();

if ($type == 'title') {
	$formquestion = array();

	$predefinedtitles = $object->getPredefinedTitles();
	if (!empty($predefinedtitles)) {
		$formquestion[] = array(
			'type' => 'select',
			'name' => 'subtotalpredefinedtitle',
			'label' => $langs->trans("PredefinedTitle"),
			'values' => $predefinedtitles,
			'select_show_empty' => 1,
			'moreattr' => 'onchange="var v = jQuery(this).val(); if (v && v != \'-1\') { jQuery(\'#subtotallinedesc\').val(v); }"',
		);
	}

	$formquestion = array_merge($formquestion, array(
		array('type' => 'text', 'name' => 'subtotallinedesc', 'label' => $langs->trans("SubtotalLineDesc"), 'moreattr' => 'placeholder="' . $langs->trans("Description") . '"'),
		array('type' => 'select', 'name' => 'subtotallinelevel', 'label' => $langs->trans("SubtotalLineLevel"), 'values' => $depth_array, 'default' => 1, 'select_show_empty' => 0),
		array('type' => 'checkbox', 'value' => true, 'name' => 'titleshowuponpdf', 'label' => $langs->trans("ShowUPOnPDF")),
		array('type' => 'checkbox', 'value' => true, 'name' => 'titleshowtotalexludingvatonpdf', 'label' => $langs->trans("ShowTotalExludingVATOnPDF")),
		array('type' => 'checkbox', 'value' => false, 'name' => 'titleforcepagebreak', 'label' => $langs->trans("ForcePageBreak")),
	));
} elseif ($type == 'subtotal') {
	$formquestion = array(
		array('type' => 'select', 'name' => 'subtotaltitleline', 'label' => $langs->trans("CorrespondingTitleLine"), 'values' => $titles, 'select_show_empty' => 0),
		array('type' => 'checkbox', 'value' => true, 'name' => 'subtotalshowtotalexludingvatonpdf', 'label' => $langs->trans("ShowTotalExludingVATOnPDF")),
	);
} elseif ($type == 'text') {
	$formquestion = array();

	$predefinedtexts = $object->getPredefinedTexts();
	if (!empty($predefinedtexts)) {
		$predefinedtextvalues = array();
		$predefinedtextsmap = array();
		foreach ($predefinedtexts as $rowid => $text) {
			$predefinedtextvalues[$rowid] = $text['label'];
			$predefinedtextsmap[$rowid] = $text['content'];
		}
		print '<script>var subtotalPredefinedTextsMap = ' . json_encode($predefinedtextsmap, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . ';</script>';
		$formquestion[] = array(
			'type' => 'select',
			'name' => 'subtotalpredefinedtext',
			'label' => $langs->trans("PredefinedText"),
			'values' => $predefinedtextvalues,
			'select_show_empty' => 1,
			'moreattr' => 'onchange="var v = subtotalPredefinedTextsMap[jQuery(this).val()]; if (v !== undefined) { jQuery(\'#subtotaltextcontent\').val(v); }"',
		);
	}

	$formquestion[] = array('type' => 'textarea', 'name' => 'subtotaltextcontent', 'label' => $langs->trans("SubtotalTextContent"));
}

$page = $_SERVER["PHP_SELF"];

if ($object->element == 'facture') {
	$page .= '?facid=' . $object->id;
} elseif (in_array($object->element, $object::$ALLOWED_TYPES)) {  // @phan-suppress-current-line PhanUndeclaredStaticProperty
	$page .= '?id=' . $object->id;
}

$form_title = $type == 'title' ? $langs->trans('AddTitleLine') : $langs->trans('AddSubtotalLine');

print $form->formconfirm($page, $form_title, '', 'confirm_add' . $type . 'line', $formquestion, 'yes', 1);
