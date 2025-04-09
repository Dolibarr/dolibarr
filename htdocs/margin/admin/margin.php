<?php
/* Copyright (C) 2012	Christophe Battarel	<christophe.battarel@altairis.fr>
 * Copyright (C) 2016	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
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
 *      \file       /htdocs/margin/admin/margin.php
 *		\ingroup    margin
 *		\brief      Page to setup margin module
 */

include '../../main.inc.php';

require_once DOL_DOCUMENT_ROOT.'/margin/lib/margins.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php";

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

$langs->loadLangs(array("admin", "bills", "margins", "stocks"));

if (!$user->admin) {
	accessforbidden();
}

$action = GETPOST('action', 'aZ09');


/*
 * Action
 */

$reg = array();
if (preg_match('/set_([a-z0-9_\-]+)/i', $action, $reg)) {
	$code = $reg[1];
	if (dolibarr_set_const($db, $code, 1, 'yesno', 0, '', $conf->entity) > 0) {
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	} else {
		dol_print_error($db);
	}
}

if (preg_match('/del_([a-z0-9_\-]+)/i', $action, $reg)) {
	$code = $reg[1];
	if (dolibarr_del_const($db, $code, $conf->entity) > 0) {
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	} else {
		dol_print_error($db);
	}
}

if ($action == 'remises') {
	if (dolibarr_set_const($db, 'MARGIN_METHODE_FOR_DISCOUNT', GETPOST('MARGIN_METHODE_FOR_DISCOUNT'), 'chaine', 0, '', $conf->entity) > 0) {
		setEventMessages($langs->trans("RecordModifiedSuccessfully"), null, 'mesgs');
	} else {
		dol_print_error($db);
	}
}

if ($action == 'typemarges') {
	if (dolibarr_set_const($db, 'MARGIN_TYPE', GETPOST('MARGIN_TYPE'), 'chaine', 0, '', $conf->entity) > 0) {
		setEventMessages($langs->trans("RecordModifiedSuccessfully"), null, 'mesgs');
	} else {
		dol_print_error($db);
	}
}

if ($action == 'contact') {
	if (dolibarr_set_const($db, 'AGENT_CONTACT_TYPE', GETPOST('AGENT_CONTACT_TYPE'), 'chaine', 0, '', $conf->entity) > 0) {
		setEventMessages($langs->trans("RecordModifiedSuccessfully"), null, 'mesgs');
	} else {
		dol_print_error($db);
	}
}

/*
 * View
 */

$form = new Form($db);

llxHeader('', $langs->trans("margesSetup"), '', '', 0, 0, '', '', '', 'mod-margin page-admin_margin');


$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("margesSetup"), $linkback, 'title_setup');


$head = marges_admin_prepare_head();

print dol_get_fiche_head($head, 'parameters', $langs->trans("Margins"), -1, 'margin');

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td width=300>'.$langs->trans("MemberMainOptions").'</td>';
print '<td colspan="2">'.$langs->trans("Value").'</td>'."\n";
print '<td class="left">'.$langs->trans("Description").'</td>'."\n";
print '</tr>';

// GLOBAL DISCOUNT MANAGEMENT
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print "<input type=\"hidden\" name=\"action\" value=\"typemarges\">";
print '<tr class="oddeven">';
print '<td>'.$langs->trans("MARGIN_TYPE").'</td>';
print '<td>';
print ' <input type="radio" name="MARGIN_TYPE" value="1" ';
if (getDolGlobalString('MARGIN_TYPE') == '1') {
	print 'checked ';
}
print '/> ';
print $langs->trans('MargeType1');
print '<br>';
print ' <input type="radio" name="MARGIN_TYPE" value="pmp" ';
if (getDolGlobalString('MARGIN_TYPE') == 'pmp') {
	print 'checked ';
}
print '/> ';
print $langs->trans('MargeType2');
print '<br>';
print ' <input type="radio" name="MARGIN_TYPE" value="costprice" ';
if (getDolGlobalString('MARGIN_TYPE') == 'costprice') {
	print 'checked ';
}
print '/> ';
print $langs->trans('MargeType3');
print '</td>';
print '<td>';
print '<input type="submit" class="button button-edit" value="'.$langs->trans("Modify").'" class="button">';
print '</td>';
print '<td>'.$langs->trans('MarginTypeDesc');
print '</td>';
print '</tr>';
print '</form>';

// DISPLAY MARGIN RATES
print '<tr class="oddeven">';
print '<td>'.$langs->trans("DisplayMarginRates").'</td>';
print '<td colspan="2">';
if (!empty($conf->use_javascript_ajax)) {
	print ajax_constantonoff('DISPLAY_MARGIN_RATES');
} else {
	if (!getDolGlobalString('DISPLAY_MARGIN_RATES')) {
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_DISPLAY_MARGIN_RATES&token='.newToken().'">'.img_picto($langs->trans("Disabled"), 'off').'</a>';
	} else {
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=del_DISPLAY_MARGIN_RATES&token='.newToken().'">'.img_picto($langs->trans("Enabled"), 'on').'</a>';
	}
}
print '</td>';
print '<td>'.$langs->trans('MarginRate').' = '.$langs->trans('Margin').' / '.$langs->trans('BuyingPrice').'</td>';
print '</tr>';

// DISPLAY MARK RATES
print '<tr class="oddeven">';
print '<td>'.$langs->trans("DisplayMarkRates").'</td>';
print '<td colspan="2">';
if (!empty($conf->use_javascript_ajax)) {
	print ajax_constantonoff('DISPLAY_MARK_RATES');
} else {
	if (!getDolGlobalString('DISPLAY_MARK_RATES')) {
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_DISPLAY_MARK_RATES&token='.newToken().'">'.img_picto($langs->trans("Disabled"), 'off').'</a>';
	} else {
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=del_DISPLAY_MARK_RATES&token='.newToken().'">'.img_picto($langs->trans("Enabled"), 'on').'</a>';
	}
}
print '</td>';
print '<td>'.$langs->trans('MarkRate').' = '.$langs->trans('Margin').' / '.$langs->trans('SellingPrice').'</td>';
print '</tr>';


print '<tr class="oddeven">';
print '<td>'.$langs->trans("ForceBuyingPriceIfNull").'</td>';
print '<td colspan="2">';
if (!empty($conf->use_javascript_ajax)) {
	print ajax_constantonoff('ForceBuyingPriceIfNull');
} else {
	if (!getDolGlobalString('ForceBuyingPriceIfNull')) {
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_ForceBuyingPriceIfNull&token='.newToken().'">'.img_picto($langs->trans("Disabled"), 'off').'</a>';
	} else {
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=del_ForceBuyingPriceIfNull&token='.newToken().'">'.img_picto($langs->trans("Enabled"), 'on').'</a>';
	}
}
print '</td>';
print '<td>'.$langs->trans('ForceBuyingPriceIfNullDetails').'</td>';
print '</tr>';

// GLOBAL DISCOUNT MANAGEMENT
$methods = array(
	1 => $langs->trans('UseDiscountAsProduct'),
	2 => $langs->trans('UseDiscountAsService'),
	3 => $langs->trans('UseDiscountOnTotal')
);


print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="remises">';
print '<tr class="oddeven">';
print '<td>'.$langs->trans("MARGIN_METHODE_FOR_DISCOUNT").'</td>';
print '<td class="left">';
print Form::selectarray('MARGIN_METHODE_FOR_DISCOUNT', $methods, getDolGlobalString('MARGIN_METHODE_FOR_DISCOUNT'));
print '</td>';
print '<td>';
print '<input type="submit" class="button button-edit" value="'.$langs->trans("Modify").'">';
print '</td>';
print '<td>'.$langs->trans('MARGIN_METHODE_FOR_DISCOUNT_DETAILS').'</td>';
print '</tr>';
print '</form>';

// INTERNAL CONTACT TYPE USED AS COMMERCIAL AGENT
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="contact">';
print '<tr class="oddeven">';
print '<td>'.$langs->trans("AgentContactType").'</td>';
print '<td class="left">';
$formcompany = new FormCompany($db);
$facture = new Facture($db);
print $formcompany->selectTypeContact($facture, getDolGlobalString('AGENT_CONTACT_TYPE'), "AGENT_CONTACT_TYPE", "internal", "code", 1, "maxwidth250");
print '</td>';
print '<td>';
print '<input type="submit" class="button button-edit" value="'.$langs->trans("Modify").'">';
print '</td>';
print '<td>'.$langs->trans('AgentContactTypeDetails').'</td>';
print '</tr>';
print '</form>';

print '</table>';

print dol_get_fiche_end();

print '<br>';

// End of page
llxFooter();
$db->close();
