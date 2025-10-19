<?php
/* Copyright (C) 2014-2017  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2024		MDW						<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024-2025  Frédéric France         <frederic.france@free.fr>
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
 * @var CommonObject $object
 * @var CommonObjectLine $line
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
@phan-var-force CommonObject $this
';

$depth_array = $depth_array ?? array();
$titles = $titles ?? array();

if ($type == 'subtotal' && empty($titles)) {
	setEventMessages("NoTitleError", null, 'errors');
	return;
}

$formquestion = array();

if ($type == 'title') {
	$formquestion = array(
		array('type' => 'text', 'name' => 'subtotallinedesc', 'label' => $langs->trans("SubtotalLineDesc"), 'moreattr' => 'placeholder="' . $langs->trans("Description") . '"'),
		array('type' => 'select', 'name' => 'subtotallinelevel', 'label' => $langs->trans("SubtotalLineLevel"), 'values' => $depth_array, 'default' => 1, 'select_show_empty' => 0),
		array('type' => 'checkbox', 'value' => false, 'name' => 'titleshowuponpdf', 'label' => $langs->trans("ShowUPOnPDF")),
		array('type' => 'checkbox', 'value' => false, 'name' => 'titleshowtotalexludingvatonpdf', 'label' => $langs->trans("ShowTotalExludingVATOnPDF")),
		array('type' => 'checkbox', 'value' => false, 'name' => 'titleforcepagebreak', 'label' => $langs->trans("ForcePageBreak")),
	);
} elseif ($type == 'subtotal') {
	$formquestion = array(
		array('type' => 'select', 'name' => 'subtotaltitleline', 'label' => $langs->trans("CorrespondingTitleLine"), 'values' => $titles, 'select_show_empty' => 0),
		array('type' => 'checkbox', 'value' => false, 'name' => 'subtotalshowtotalexludingvatonpdf', 'label' => $langs->trans("ShowTotalExludingVATOnPDF")),
	);
}

$page = $_SERVER["PHP_SELF"];

if ($object->element == 'facture') {
	$page .= '?facid=' . $object->id;
} elseif (in_array($object->element, array('propal', 'commande', 'facturerec', 'shipping'))) {
	$page .= '?id=' . $object->id;
}

$form_title = $type == 'title' ? $langs->trans('AddTitleLine') : $langs->trans('AddSubtotalLine');

print $form->formconfirm($page, $form_title, '', 'confirm_add' . $type . 'line', $formquestion, 'yes', 1);
