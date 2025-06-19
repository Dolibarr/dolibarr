<?php
/* Copyright (C) 2001-2002	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2006-2015	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2006-2012	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2011		Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2024		Alexandre Spangaro		<alexandre@inovea-conseil.com>
 * Copyright (C) 2024		Frédéric France			<frederic.france@free.fr>
 * Copyright (C) 2025		MDW						<mdeweerd@users.noreply.github.com>
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
 *     	\file       htdocs/don/admin/website.php
 *		\ingroup    don
 *		\brief      File of main public page for donation module
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/donation.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';
require_once DOL_DOCUMENT_ROOT.'/don/class/don.class.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 *
 * @var string $dolibarr_main_url_root
 */

// Load translation files required by the page
$langs->loadLangs(array("admin", "donations"));

$action = GETPOST('action', 'aZ09');

// Hook to be used by external payment modules (ie Payzen, ...)
$hookmanager = new HookManager($db);
$hookmanager->initHooks(array('newpayment'));

if (!$user->admin) {
	accessforbidden();
}

$error = 0;


/*
 * Actions
 */

if ($action == 'setDONATION_ENABLE_PUBLIC') {
	if (GETPOST('value')) {
		dolibarr_set_const($db, 'DONATION_ENABLE_PUBLIC', 1, 'chaine', 0, '', $conf->entity);
	} else {
		dolibarr_set_const($db, 'DONATION_ENABLE_PUBLIC', 0, 'chaine', 0, '', $conf->entity);
	}
}

if ($action == 'update') {
	$public = GETPOST('DONATION_ENABLE_PUBLIC');

	$minamount = GETPOST('DONATION_MIN_AMOUNT');
	$publiccounters = GETPOST('DONATION_COUNTERS_ARE_PUBLIC');
	$payonline = GETPOST('DONATION_NEWFORM_PAYONLINE');

	$res = dolibarr_set_const($db, "DONATION_ENABLE_PUBLIC", $public, 'chaine', 0, '', $conf->entity);
	$res = dolibarr_set_const($db, "DONATION_MIN_AMOUNT", $minamount, 'chaine', 0, '', $conf->entity);
	$res = dolibarr_set_const($db, "DONATION_COUNTERS_ARE_PUBLIC", $publiccounters, 'chaine', 0, '', $conf->entity);
	$res = dolibarr_set_const($db, "DONATION_NEWFORM_PAYONLINE", $payonline, 'chaine', 0, '', $conf->entity);

	if (!($res > 0)) {
		$error++;
	}

	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
}


/*
 * View
 */

$form = new Form($db);

$title = $langs->trans("DonationsSetup");
$help_url = 'EN:Module_Donation|FR:Module_Don';

llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-don page-admin_website');


$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($title, $linkback, 'title_setup');

$head = donation_admin_prepare_head();



print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="action" value="update">';
print '<input type="hidden" name="token" value="'.newToken().'">';

print dol_get_fiche_head($head, 'website', $langs->trans("Donations"), -1, 'user');

if ($conf->use_javascript_ajax) {
	print "\n".'<script type="text/javascript">';
	print 'jQuery(document).ready(function () {
                function initfields()
                {
					if (jQuery("#DONATION_ENABLE_PUBLIC").val()==\'0\') {
                        jQuery("#trforcetype, #tramount, #tredit, #trpayment").hide();
                    }
                    if (jQuery("#DONATION_ENABLE_PUBLIC").val()==\'1\') {
                        jQuery("#trforcetype, #tramount, #tredit, #trpayment").show();
					}
				}
				initfields();
                jQuery("#DONATION_ENABLE_PUBLIC").change(function() { initfields(); });
			})';
	print '</script>'."\n";
}


print '<span class="opacitymedium">'.$langs->trans("BlankDonationFormDesc").'</span><br><br>';

$param = '';

$enabledisablehtml = $langs->trans("EnablePublicDonationForm").' ';
if (!getDolGlobalString('DONATION_ENABLE_PUBLIC')) {
	// Button off, click to enable
	$enabledisablehtml .= '<a class="reposition valignmiddle" href="'.$_SERVER["PHP_SELF"].'?action=setDONATION_ENABLE_PUBLIC&token='.newToken().'&value=1'.$param.'">';
	$enabledisablehtml .= img_picto($langs->trans("Disabled"), 'switch_off');
	$enabledisablehtml .= '</a>';
} else {
	// Button on, click to disable
	$enabledisablehtml .= '<a class="reposition valignmiddle" href="'.$_SERVER["PHP_SELF"].'?action=setDONATION_ENABLE_PUBLIC&token='.newToken().'&value=0'.$param.'">';
	$enabledisablehtml .= img_picto($langs->trans("Activated"), 'switch_on');
	$enabledisablehtml .= '</a>';
}
print $enabledisablehtml;
print '<input type="hidden" id="DONATION_ENABLE_PUBLIC" name="DONATION_ENABLE_PUBLIC" value="'.(!getDolGlobalString('DONATION_ENABLE_PUBLIC') ? 0 : 1).'">';

print '<br><br>';


if (getDolGlobalString('DONATION_ENABLE_PUBLIC')) {
	print '<br>';
	print img_picto('', 'globe').' <span class="opacitymedium">'.$langs->trans('BlankDonationForm').'</span><br>';
	if (isModEnabled('multicompany')) {
		$entity_qr = '?entity='.((int) $conf->entity);
	} else {
		$entity_qr = '';
	}

	// Define $urlwithroot
	$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
	$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file

	print '<div class="urllink">';
	print '<input type="text" id="publicurldonation" class="quatrevingtpercentminusx" value="'.$urlwithroot.'/public/donations/new.php'.$entity_qr.'">';
	print '<a target="_blank" rel="noopener noreferrer" href="'.$urlwithroot.'/public/donations/new.php'.$entity_qr.'">'.img_picto('', 'globe', 'class="paddingleft"').'</a>';
	print '</div>';
	print ajax_autoselect('publicurldonation');

	print '<br><br>';

	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';

	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("Parameter").'</td>';
	print '<td>'.$langs->trans("Value").'</td>';
	print "</tr>\n";

	// Min amount
	print '<tr class="oddeven" id="tredit"><td>';
	print $langs->trans("MinimumAmountDonation");
	print '</td><td>';
	print '<input type="text" class="right width50" id="DONATION_MIN_AMOUNT" name="DONATION_MIN_AMOUNT" value="'.getDolGlobalString('DONATION_MIN_AMOUNT').'">';
	print "</td></tr>\n";

	// Show counter of validated donations publicly
	print '<tr class="oddeven" id="tredit"><td>';
	print $langs->trans("donationCountersArePublic");
	print '</td><td>';
	print $form->selectyesno("DONATION_COUNTERS_ARE_PUBLIC", getDolGlobalInt('DONATION_COUNTERS_ARE_PUBLIC'), 1, false, 0, 1);
	print "</td></tr>\n";

	// Jump to an online payment page
	print '<tr class="oddeven" id="trpayment"><td>';
	print $langs->trans("DONATION_NEWFORM_PAYONLINE");
	print '</td><td>';

	// Initialize $validpaymentmethod
	// The list can be complete by the hook 'doValidatePayment' executed inside getValidOnlinePaymentMethods()
	$validpaymentmethod = getValidOnlinePaymentMethods('', 1);

	// Define $listofval using the $validpaymentmethod
	$listofval = array();
	$listofval['-1'] = array('label' => $langs->trans('No'));
	$listofval['all'] = array('label' => $langs->trans('Yes').' ('.$langs->trans("VisitorCanChooseItsPaymentMode").')', 'data-html' => $langs->trans('Yes').' &nbsp; <span class="opacitymedium">('.$langs->trans("VisitorCanChooseItsPaymentMode").')</span>');
	foreach ($validpaymentmethod as $key => $val) {
		if (is_array($val)) {
			$listofval[$key] = $val;
		} else {
			$listofval[$key] = array('label' => $key, 'status' => 'valid');
		}
	}

	print $form->selectarray("DONATION_NEWFORM_PAYONLINE", $listofval, getDolGlobalString('DONATION_NEWFORM_PAYONLINE'), 0);
	print "</td></tr>\n";

	print '</table>';
	print '</div>';

	print '<div class="center">';
	print '<input type="submit" class="button button-edit" value="'.$langs->trans("Modify").'">';
	print '</div>';
}


print dol_get_fiche_end();

print '</form>';

// End of page
llxFooter();
$db->close();
