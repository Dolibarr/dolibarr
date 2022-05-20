<?php
/* Copyright (C) 2001-2002	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2006-2015	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2006-2012	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2011		Juanjo Menent			<jmenent@2byte.es>
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
 *     	\file       htdocs/partnership/admin/website.php
 *		\ingroup    partnership
 *		\brief      File of main public page for partnership module
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/member.lib.php';
require_once DOL_DOCUMENT_ROOT.'/partnership/lib/partnership.lib.php';
require_once DOL_DOCUMENT_ROOT.'/partnership/class/partnership.class.php';

// Load translation files required by the page
$langs->loadLangs(array("admin", "partnership"));

$action = GETPOST('action', 'aZ09');

if (!$user->admin) {
	accessforbidden();
}

$error = 0;


/*
 * Actions
 */

if ($action == 'setPARTNERSHIP_ENABLE_PUBLIC') {
	if (GETPOST('value')) {
		dolibarr_set_const($db, 'PARTNERSHIP_ENABLE_PUBLIC', 1, 'chaine', 0, '', $conf->entity);
	} else {
		dolibarr_set_const($db, 'PARTNERSHIP_ENABLE_PUBLIC', 0, 'chaine', 0, '', $conf->entity);
	}
}

if ($action == 'update') {
	$public = GETPOST('PARTNERSHIP_ENABLE_PUBLIC');

	$res = dolibarr_set_const($db, "PARTNERSHIP_ENABLE_PUBLIC", $public, 'chaine', 0, '', $conf->entity);

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

$title = $langs->trans('PartnershipSetup');
$help_url = '';
//$help_url = 'EN:Module_Partnership|FR:Module_Adh&eacute;rents|ES:M&oacute;dulo_Miembros';
llxHeader('', $title, $help_url);


$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($title, $linkback, 'title_setup');

$head = partnershipAdminPrepareHead();



print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="action" value="update">';
print '<input type="hidden" name="token" value="'.newToken().'">';

print dol_get_fiche_head($head, 'website', $langs->trans("Partnerships"), -1, 'partnership');

if ($conf->use_javascript_ajax) {
	print "\n".'<script type="text/javascript" language="javascript">';
	print 'jQuery(document).ready(function () {
                function initemail()
                {
                    if (jQuery("#PARTNERSHIP_NEWFORM_PAYONLINE").val()==\'-1\')
                    {
                        jQuery("#tremail").hide();
					}
					else
					{
                        jQuery("#tremail").show();
					}
				}
                function initfields()
                {
					if (jQuery("#PARTNERSHIP_ENABLE_PUBLIC").val()==\'0\')
                    {
                        jQuery("#trforcetype, #tramount, #tredit, #trpayment, #tremail").hide();
                    }
                    if (jQuery("#PARTNERSHIP_ENABLE_PUBLIC").val()==\'1\')
                    {
                        jQuery("#trforcetype, #tramount, #tredit, #trpayment").show();
                        if (jQuery("#PARTNERSHIP_NEWFORM_PAYONLINE").val()==\'-1\') jQuery("#tremail").hide();
                        else jQuery("#tremail").show();
					}
				}
				initfields();
                jQuery("#PARTNERSHIP_ENABLE_PUBLIC").change(function() { initfields(); });
                jQuery("#PARTNERSHIP_NEWFORM_PAYONLINE").change(function() { initemail(); });
			})';
	print '</script>'."\n";
}


print '<span class="opacitymedium">'.$langs->trans("PublicFormRegistrationPartnerDesc").'</span><br><br>';

$param = '';

$enabledisablehtml = $langs->trans("EnablePublicSubscriptionForm").' ';
if (empty($conf->global->PARTNERSHIP_ENABLE_PUBLIC)) {
	// Button off, click to enable
	$enabledisablehtml .= '<a class="reposition valignmiddle" href="'.$_SERVER["PHP_SELF"].'?action=setPARTNERSHIP_ENABLE_PUBLIC&token='.newToken().'&value=1'.$param.'">';
	$enabledisablehtml .= img_picto($langs->trans("Disabled"), 'switch_off');
	$enabledisablehtml .= '</a>';
} else {
	// Button on, click to disable
	$enabledisablehtml .= '<a class="reposition valignmiddle" href="'.$_SERVER["PHP_SELF"].'?action=setPARTNERSHIP_ENABLE_PUBLIC&token='.newToken().'&value=0'.$param.'">';
	$enabledisablehtml .= img_picto($langs->trans("Activated"), 'switch_on');
	$enabledisablehtml .= '</a>';
}
print $enabledisablehtml;
print '<input type="hidden" id="PARTNERSHIP_ENABLE_PUBLIC" name="PARTNERSHIP_ENABLE_PUBLIC" value="'.(empty($conf->global->PARTNERSHIP_ENABLE_PUBLIC) ? 0 : 1).'">';


print '<br>';


/*
if (!empty($conf->global->PARTNERSHIP_ENABLE_PUBLIC)) {
	print '<br>';

	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';

	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("Parameter").'</td>';
	print '<td class="right">'.$langs->trans("Value").'</td>';
	print "</tr>\n";

	// Amount
	print '<tr class="oddeven" id="tramount"><td>';
	print $langs->trans("DefaultAmount");
	print '</td><td class="right">';
	print '<input type="text" class="right width75" id="PARTNERSHIP_NEWFORM_AMOUNT" name="PARTNERSHIP_NEWFORM_AMOUNT" value="'.(!empty($conf->global->PARTNERSHIP_NEWFORM_AMOUNT) ? $conf->global->PARTNERSHIP_NEWFORM_AMOUNT : '').'">';
	print "</td></tr>\n";

	// Can edit
	print '<tr class="oddeven" id="tredit"><td>';
	print $langs->trans("CanEditAmount");
	print '</td><td class="right">';
	print $form->selectyesno("PARTNERSHIP_NEWFORM_EDITAMOUNT", (!empty($conf->global->PARTNERSHIP_NEWFORM_EDITAMOUNT) ? $conf->global->PARTNERSHIP_NEWFORM_EDITAMOUNT : 0), 1);
	print "</td></tr>\n";

	// Jump to an online payment page
	print '<tr class="oddeven" id="trpayment"><td>';
	print $langs->trans("PARTNERSHIP_NEWFORM_PAYONLINE");
	print '</td><td class="right">';
	$listofval = array();
	$listofval['-1'] = $langs->trans('No');
	$listofval['all'] = $langs->trans('Yes').' ('.$langs->trans("VisitorCanChooseItsPaymentMode").')';
	if (!empty($conf->paybox->enabled)) {
		$listofval['paybox'] = 'Paybox';
	}
	if (!empty($conf->paypal->enabled)) {
		$listofval['paypal'] = 'PayPal';
	}
	if (!empty($conf->stripe->enabled)) {
		$listofval['stripe'] = 'Stripe';
	}
	print $form->selectarray("PARTNERSHIP_NEWFORM_PAYONLINE", $listofval, (!empty($conf->global->PARTNERSHIP_NEWFORM_PAYONLINE) ? $conf->global->PARTNERSHIP_NEWFORM_PAYONLINE : ''), 0);
	print "</td></tr>\n";


	print '</table>';
	print '</div>';

	print '<div class="center">';
	print '<input type="submit" class="button button-edit" value="'.$langs->trans("Modify").'">';
	print '</div>';
}
*/


print dol_get_fiche_end();

print '</form>';


if (!empty($conf->global->PARTNERSHIP_ENABLE_PUBLIC)) {
	print '<br>';
	//print $langs->trans('FollowingLinksArePublic').'<br>';
	print img_picto('', 'globe').' <span class="opacitymedium">'.$langs->trans('BlankSubscriptionForm').'</span><br>';
	if (!empty($conf->multicompany->enabled)) {
		$entity_qr = '?entity='.$conf->entity;
	} else {
		$entity_qr = '';
	}

	// Define $urlwithroot
	$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
	$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
	//$urlwithroot=DOL_MAIN_URL_ROOT;					// This is to use same domain name than current

	print '<div class="urllink">';
	print '<input type="text" id="publicurlmember" class="quatrevingtpercentminusx" value="'.$urlwithroot.'/public/partnership/new.php'.$entity_qr.'">';
	print '<a target="_blank" rel="noopener noreferrer" href="'.$urlwithroot.'/public/partnership/new.php'.$entity_qr.'">'.img_picto('', 'globe', 'class="paddingleft"').'</a>';
	print '</div>';
	print ajax_autoselect('publicurlmember');
}

// End of page
llxFooter();
$db->close();
