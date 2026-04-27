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

require_once '../../main.inc.php';
/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */
require_once DOL_DOCUMENT_ROOT.'/margin/lib/margins.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php";

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

if ($action == 'update') {
	$error = 0;
	if (dolibarr_set_const($db, 'MARGIN_METHODE_FOR_DISCOUNT', GETPOST('MARGIN_METHODE_FOR_DISCOUNT'), 'chaine', 0, '', $conf->entity) <= 0) {
		dol_print_error($db);
		$error++;
	}

	if (dolibarr_set_const($db, 'MARGIN_TYPE', GETPOST('MARGIN_TYPE'), 'chaine', 0, '', $conf->entity) <= 0) {
		dol_print_error($db);
		$error++;
	}

	if (dolibarr_set_const($db, 'AGENT_CONTACT_TYPE', GETPOST('AGENT_CONTACT_TYPE'), 'chaine', 0, '', $conf->entity) <= 0) {
		dol_print_error($db);
		$error++;
	}

	if (!$error) {
		setEventMessages($langs->trans("RecordModifiedSuccessfully"), null, 'mesgs');
	}
}


/*
 * View
 */

$form = new Form($db);

llxHeader('', $langs->trans("margesSetup"), '', '', 0, 0, '', '', '', 'mod-margin page-admin_margin');


$linkback = '<a href="'.dolBuildUrl(DOL_URL_ROOT.'/admin/modules.php', ['restore_lastsearch_values' => 1]).'">'.img_picto($langs->trans("BackToModuleList"), 'back', 'class="pictofixedwidth"').'<span class="hideonsmartphone">'.$langs->trans("BackToModuleList").'</span></a>';
print load_fiche_titre($langs->trans("margesSetup"), $linkback, 'title_setup');


$head = marges_admin_prepare_head();

print dol_get_fiche_head($head, 'parameters', $langs->trans("Margins"), -1, 'margin');

print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="update">';

print '<div class="div-table-responsive">'; // You can use div-table-responsive-no-min if you don't need reserved height for your table
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td colspan="3">'.$langs->trans("MemberMainOptions").'</td>';
print '</tr>';

// GLOBAL DISCOUNT MANAGEMENT
print '<tr class="oddeven">';
print '<td>'.$langs->trans("MARGIN_TYPE").'</td>';
print '<td class="minwidth150">';
print ' <input type="radio" name="MARGIN_TYPE" id="MARGIN_TYPE1" value="1" ';
if (getDolGlobalString('MARGIN_TYPE') == '1') {
	print 'checked ';
}
print '/> ';
print '<label for="MARGIN_TYPE1">'.$langs->trans('MargeType1').'</label>';
print '<br>';
print ' <input type="radio" name="MARGIN_TYPE" id="MARGIN_TYPE2" value="pmp" ';
if (getDolGlobalString('MARGIN_TYPE') == 'pmp') {
	print 'checked ';
}
print '/> ';
print '<label for="MARGIN_TYPE2">'.$langs->trans('MargeType2').'</label>';
print '<br>';
print ' <input type="radio" name="MARGIN_TYPE" id="MARGIN_TYPE3" value="costprice" ';
if (getDolGlobalString('MARGIN_TYPE') == 'costprice') {
	print 'checked ';
}
print '/> ';
print '<label for="MARGIN_TYPE3">'.$langs->trans('MargeType3').'</label>';
print '</td>';
print '<td class="minwidth200"><span class="small">'.$langs->trans('MarginTypeDesc').'</span>';
print '</td>';
print '</tr>';

// DISPLAY MARGIN RATES
print '<tr class="oddeven">';
print '<td>'.$langs->trans("DisplayMarginRates").'</td>';
print '<td>';
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
print '<td><span class="small">'.$langs->trans('MarginRate').' = '.$langs->trans('Margin').' / '.$langs->trans('BuyingPrice').'</span></td>';
print '</tr>';

// DISPLAY MARK RATES
print '<tr class="oddeven">';
print '<td>'.$langs->trans("DisplayMarkRates").'</td>';
print '<td>';
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
print '<td><span class="small">'.$langs->trans('MarkRate').' = '.$langs->trans('Margin').' / '.$langs->trans('SellingPrice').'</span></td>';
print '</tr>';


print '<tr class="oddeven">';
print '<td>'.$langs->trans("ForceBuyingPriceIfNull").'</td>';
print '<td>';
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
print '<td><span class="small">'.$langs->trans('ForceBuyingPriceIfNullDetails').'</span></td>';
print '</tr>';

// GLOBAL DISCOUNT MANAGEMENT
$methods = array(
	1 => $langs->trans('UseDiscountAsProduct'),
	2 => $langs->trans('UseDiscountAsService'),
	3 => $langs->trans('UseDiscountOnTotal')
);


print '<tr class="oddeven">';
print '<td>'.$langs->trans("MARGIN_METHODE_FOR_DISCOUNT").'</td>';
print '<td class="left">';
print Form::selectarray('MARGIN_METHODE_FOR_DISCOUNT', $methods, getDolGlobalString('MARGIN_METHODE_FOR_DISCOUNT'));
print '</td>';
print '<td><span class="small">'.$langs->trans('MARGIN_METHODE_FOR_DISCOUNT_DETAILS').'</span></td>';
print '</tr>';

// INTERNAL CONTACT TYPE USED AS COMMERCIAL AGENT
print '<tr class="oddeven">';
print '<td>'.$langs->trans("AgentContactType").'</td>';
print '<td class="left">';
$formcompany = new FormCompany($db);
$facture = new Facture($db);
print $formcompany->selectTypeContact($facture, getDolGlobalString('AGENT_CONTACT_TYPE'), "AGENT_CONTACT_TYPE", "internal", "code", 1, "maxwidth250");
print '</td>';
print '<td><span class="small">'.$langs->trans('AgentContactTypeDetails').'</span></td>';
print '</tr>';

print '</table>';
print '</div>';

print '<center><input type="submit" class="button button-edit" value="'.$langs->trans("Modify").'" class="button"></center>';

print '</form>';

print dol_get_fiche_end();

print '<br>';

// End of page
llxFooter();
$db->close();
