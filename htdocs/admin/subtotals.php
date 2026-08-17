<?php
/* Copyright (C) 2004-2011	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2012-2013	Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2019		Christophe Battarel 	<christophe@altairis.fr>
 * Copyright (C) 2024-2026  Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2024		MDW						<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2025		Charlene Benke		<charlene@patas-monkey.com>
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
 *  \file       htdocs/admin/subtotals.php
 *  \ingroup    subtotals
 *  \brief      Activation page for the subtotals module in the other modules
 */

// Load Dolibarr environment
require '../main.inc.php';
/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var Form $form
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/doleditor.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formother.class.php';

// Load translation files required by the page
$langs->loadLangs(array('main', 'admin', 'subtotals', 'errors'));
$action = GETPOST('action', 'aZ09');

if (!$user->admin) {
	accessforbidden();
}

$formother = new FormOther($db);

// default color const
$default = 'ffffff';

// Constant and translation of the module description
$modules = [
	'PROPAL' => array('lang' => 'propal', 'key' => 'Proposal', 'old_pdf' => '(azur model)'),
	'COMMANDE' => array('lang' => 'orders', 'key' => 'CustomerOrder', 'old_pdf' => '(einstein model)'),
	'FICHINTER' => array('lang' => 'interventions', 'key' => 'Intervention', 'old_pdf' => '(soleil model)'),
	'FACTURE' => array('lang' => 'bills', 'key' => 'CustomerInvoice', 'old_pdf' => '(crabe model)'),
	'FACTUREREC' => array('lang' => 'bills', 'key' => 'RecurringInvoiceTemplate'),
	'SUPPLIER_PROPOSAL' => [
		'lang' => 'supplier_proposal',
		'key' => 'SupplierProposal',
		'old_pdf' => '(aurore model)',
	],
	'ORDER_SUPPLIER' => [
		'lang' => 'orders',
		'key' => 'SupplierOrder',
		'old_pdf' => '(muscadet model)',
	],
	'INVOICE_SUPPLIER' => [
		'lang' => 'bills',
		'key' => 'SupplierInvoice',
	],
];
// Conditions for the option to be offered
$conditions = [
	'PROPAL' => isModEnabled("propal"),
	'COMMANDE' => isModEnabled("order"),
	'FICHINTER' => (isModEnabled("intervention")),
	'FACTURE' => isModEnabled("invoice"),
	'FACTUREREC' => isModEnabled("invoice"),
	'SUPPLIER_PROPOSAL' => isModEnabled("supplier_proposal"),
	'ORDER_SUPPLIER' => isModEnabled("supplier_order"),
	'INVOICE_SUPPLIER' => isModEnabled("supplier_invoice"),
];

$max_depth = 0;

foreach ($modules as $const => $desc) {
	$const_depth = getDolGlobalString('SUBTOTAL_' . $const . '_MAX_DEPTH', 2);

	$constante_title = 'SUBTOTAL_TITLE_' . $const;
	$constante_subtotal = 'SUBTOTAL_' . $const;
	if (getDolGlobalString($constante_title) || getDolGlobalString($constante_subtotal)) {
		$max_depth = max($const_depth, $max_depth);
	}
}

// Background and text colors of the header (title line) and of the footer (subtotal line) of a block,
// for each level. A color not defined yet is shown with the color really used at the moment, so that
// saving the page without touching a picker changes nothing.
$colors = array();

for ($i = 1; $i <= $max_depth; $i++) {
	$head_color = getDolGlobalString('SUBTOTAL_BACK_COLOR_LEVEL_' . $i, $default);
	$foot_color = getDolGlobalString('SUBTOTAL_FOOT_COLOR_LEVEL_' . $i);
	$foot_color = (empty($foot_color) ? $head_color : $foot_color);	// The footer uses the header color when not defined
	$head_text_color = getDolGlobalString('SUBTOTAL_TEXT_COLOR_LEVEL_' . $i);
	$foot_text_color = getDolGlobalString('SUBTOTAL_FOOT_TEXT_COLOR_LEVEL_' . $i);
	if (empty($foot_text_color)) {
		$foot_text_color = $head_text_color;
	}
	$colors[$i] = array(
		'SUBTOTAL_BACK_COLOR_LEVEL_' . $i => $head_color,
		// When no text color is set, the module computes one from the background color
		'SUBTOTAL_TEXT_COLOR_LEVEL_' . $i => (empty($head_text_color) ? (colorIsLight($head_color) == 1 ? '000000' : 'ffffff') : $head_text_color),
		'SUBTOTAL_FOOT_COLOR_LEVEL_' . $i => $foot_color,
		'SUBTOTAL_FOOT_TEXT_COLOR_LEVEL_' . $i => (empty($foot_text_color) ? (colorIsLight($foot_color) == 1 ? '000000' : 'ffffff') : $foot_text_color),
	);
}

/*
 *  Actions
 */

if (preg_match('/^SUBTOTAL_.*$/', $action)) {
	if (preg_match('/^.*_MAX_DEPTH$/', $action)) {
		dolibarr_set_const($db, $action, GETPOSTINT($action), 'int', 0, '', $conf->entity);
		header("Location: " . $_SERVER['PHP_SELF']);
		setEventMessages($langs->trans("SetupSaved"), null);
		exit;
	} else {
		$value = getDolGlobalInt($action, 0);
		$value == 0 ? $value = 1 : $value = 0;
		dolibarr_set_const($db, $action, $value, 'chaine', 0, '', $conf->entity);
		header("Location: " . $_SERVER['PHP_SELF']);
		setEventMessages($langs->trans("SetupSaved"), null);
		exit;
	}
}

if ($action == 'update_colors') {
	foreach ($colors as $level_colors) {
		foreach ($level_colors as $const => $color) {
			$color_to_update = GETPOST($const, 'aZ09');
			if ($color_to_update != $color) {
				dolibarr_set_const($db, $const, $color_to_update, 'chaine', 0, '', $conf->entity);
			}
		}
	}

	header("Location: " . $_SERVER["PHP_SELF"]);
	exit;
}


/*
 * View
 */

llxHeader('', '', '', '', 0, 0, '', '', '', 'mod-admin page-subtotals');

$linkback = '<a href="'.dolBuildUrl(DOL_URL_ROOT.'/admin/modules.php', ['restore_lastsearch_values' => 1]).'">'.img_picto($langs->trans("BackToModuleList"), 'back', 'class="pictofixedwidth"').'<span class="hideonsmartphone">'.$langs->trans("BackToModuleList").'</span></a>';

print load_fiche_titre($langs->trans("SubtotalSetup"), $linkback, 'title_setup');

if (empty($conf->use_javascript_ajax)) {
	setEventMessages(null, array($langs->trans("NotAvailable"), $langs->trans("JavascriptDisabled")), 'errors');
} else {
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<td width="1100">' . $langs->trans("Settings") . '</td>';
	print '<td class="center">' . $langs->trans("Title") . '</td>';
	print '<td class="center">' . $langs->trans("Subtotal") . '</td>';
	print '<td class="center">' . $langs->trans("MaxSubtotalLevel") . '</td>';
	print "</tr>\n";

	// Modules
	foreach ($modules as $const => $desc) {
		// If this condition is not met, the option is not offered
		if (!$conditions[$const]) {
			continue;
		}

		$langs->load($desc['lang']);

		$constante_title = 'SUBTOTAL_TITLE_' . $const;
		$constante_subtotal = 'SUBTOTAL_' . $const;
		print '<!-- constant = ' . $constante_subtotal . ' -->' . "\n";
		print '<tr class="oddeven">';
		print '<td>';
		if (isset($desc['old_pdf'])) {
			print $form->textwithpicto($langs->trans($desc['key']), $langs->trans("NotSupportedByAllPDF", $desc['old_pdf']));
		} else {
			print $langs->trans($desc['key']);
		}
		print '</td>';

		print '<td class="center">';
		$value_title = getDolGlobalInt($constante_title, 0);
		print '<a class="reposition" href="' . $_SERVER['PHP_SELF'] . '?action=' . $constante_title . '&token=' . newToken() . '">';
		print $value_title == 0 ? img_picto($langs->trans("Disabled"), 'switch_off') : img_picto($langs->trans("Enabled"), 'switch_on') . '</a>';
		print '</td>';

		print '<td class="center">';
		$value_subtotal = getDolGlobalInt($constante_subtotal, 0);
		print '<a class="reposition" href="' . $_SERVER['PHP_SELF'] . '?action=' . $constante_subtotal . '&token=' . newToken() . '">';
		print $value_subtotal == 0 ? img_picto($langs->trans("Disabled"), 'switch_off') : img_picto($langs->trans("Enabled"), 'switch_on') . '</a>';
		print '</td>';

		print '<td class="center nowraponall">';
		$can_modify = !($value_subtotal == 0 && $value_title == 0);
		print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '" >';
		print '<input type="hidden" name="token" value="' . newToken() . '">';
		print '<input type="hidden" name="action" value="SUBTOTAL_' . $const . '_MAX_DEPTH">';
		print '<input size="3" type="text" class="center"';
		print $can_modify ? '' : ' disabled="disabled" ';
		print 'name="SUBTOTAL_' . $const . '_MAX_DEPTH" value="' . getDolGlobalString('SUBTOTAL_' . $const . '_MAX_DEPTH', $can_modify ? 2 : 0) . '">';
		print $can_modify ? '<input type="submit" class="button button-edit reposition smallpaddingimp" name="Button"value="' . $langs->trans("Modify") . '">' : '';
		print '</form>';
		print '</td>';

		print '</tr>';
	}

	print '</table>';

	// Other options

	print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
	print '<input type="hidden" name="token" value="' . newToken() . '">';
	print '<input type="hidden" name="action" value="update_colors">';

	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<td rowspan="2">' . $langs->trans("Other") . '</td>';
	print '<td class="center" colspan="2">' . $langs->trans("Title") . '</td>';
	print '<td class="center" colspan="2">' . $langs->trans("Subtotal") . '</td>';
	print "</tr>\n";
	print '<tr class="liste_titre">';
	print '<td class="center">' . $langs->trans("SubtotalBackColor") . '</td>';
	print '<td class="center">' . $langs->trans("SubtotalTextColor") . '</td>';
	print '<td class="center">' . $form->textwithpicto($langs->trans("SubtotalBackColor"), $langs->trans("SubtotalFootColorScreenOnly")) . '</td>';
	print '<td class="center">' . $form->textwithpicto($langs->trans("SubtotalTextColor"), $langs->trans("SubtotalFootColorScreenOnly")) . '</td>';
	print "</tr>\n";

	foreach ($colors as $level => $level_colors) {
		print '<tr class="oddeven">';
		print '<td>' . $langs->trans("SubtotalLevel", $level) . '</td>';
		foreach ($level_colors as $key => $color) {
			print '<td class="center width150">';
			print $formother->selectColor(colorArrayToHex(colorStringToArray($color, array()), $default), $key, '', 1, array(), '', '', $default);
			print $form->textwithpicto('', $langs->trans("NotSupportedByAllThemes") . ', ' . $langs->trans("PressF5AfterChangingThis"));
			print '</td>';
		}
		print '</tr>';
	}

	print '</table>' . "\n";
}

print '<div class="center">';
print '<input class="button button-save reposition buttonforacesave" type="submit" name="submit" value="' . $langs->trans("Save") . '">';
print '<input class="button button-cancel reposition" type="submit" name="cancel" value="' . $langs->trans("Cancel") . '">';
print '</div>';

// End of page
llxFooter();
$db->close();
