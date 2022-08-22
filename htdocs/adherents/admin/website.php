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
 *     	\file       htdocs/adherents/admin/website.php
 *		\ingroup    member
 *		\brief      File of main public page for member module
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/member.lib.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent_type.class.php';

// Load translation files required by the page
$langs->loadLangs(array("admin", "members"));

$action = GETPOST('action', 'aZ09');

if (!$user->admin) {
	accessforbidden();
}

$error = 0;


/*
 * Actions
 */

if ($action == 'setMEMBER_ENABLE_PUBLIC') {
	if (GETPOST('value')) {
		dolibarr_set_const($db, 'MEMBER_ENABLE_PUBLIC', 1, 'chaine', 0, '', $conf->entity);
	} else {
		dolibarr_set_const($db, 'MEMBER_ENABLE_PUBLIC', 0, 'chaine', 0, '', $conf->entity);
	}
}

if ($action == 'update') {
	$public = GETPOST('MEMBER_ENABLE_PUBLIC');
	$amount = price2num(GETPOST('MEMBER_NEWFORM_AMOUNT'), 'MT', 2);
	$editamount = GETPOST('MEMBER_NEWFORM_EDITAMOUNT');
	$payonline = GETPOST('MEMBER_NEWFORM_PAYONLINE');
	$forcetype = GETPOST('MEMBER_NEWFORM_FORCETYPE', 'int');
	$forcemorphy = GETPOST('MEMBER_NEWFORM_FORCEMORPHY', 'aZ09');

	$res = dolibarr_set_const($db, "MEMBER_ENABLE_PUBLIC", $public, 'chaine', 0, '', $conf->entity);
	$res = dolibarr_set_const($db, "MEMBER_NEWFORM_AMOUNT", $amount, 'chaine', 0, '', $conf->entity);
	$res = dolibarr_set_const($db, "MEMBER_NEWFORM_EDITAMOUNT", $editamount, 'chaine', 0, '', $conf->entity);
	$res = dolibarr_set_const($db, "MEMBER_NEWFORM_PAYONLINE", $payonline, 'chaine', 0, '', $conf->entity);
	if ($forcetype < 0) {
		$res = dolibarr_del_const($db, "MEMBER_NEWFORM_FORCETYPE", $conf->entity);
	} else {
		$res = dolibarr_set_const($db, "MEMBER_NEWFORM_FORCETYPE", $forcetype, 'chaine', 0, '', $conf->entity);
	}
	if ($forcemorphy == '-1') {
		$res = dolibarr_del_const($db, "MEMBER_NEWFORM_FORCEMORPHY", $conf->entity);
	} else {
		$res = dolibarr_set_const($db, "MEMBER_NEWFORM_FORCEMORPHY", $forcemorphy, 'chaine', 0, '', $conf->entity);
	}

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

$title = $langs->trans("MembersSetup");
$help_url = 'EN:Module_Foundations|FR:Module_Adh&eacute;rents|ES:M&oacute;dulo_Miembros';
llxHeader('', $title, $help_url);


$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($title, $linkback, 'title_setup');

$head = member_admin_prepare_head();



print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="action" value="update">';
print '<input type="hidden" name="token" value="'.newToken().'">';

print dol_get_fiche_head($head, 'website', $langs->trans("Members"), -1, 'user');

if ($conf->use_javascript_ajax) {
	print "\n".'<script type="text/javascript">';
	print 'jQuery(document).ready(function () {
                function initemail()
                {
                    if (jQuery("#MEMBER_NEWFORM_PAYONLINE").val()==\'-1\')
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
					if (jQuery("#MEMBER_ENABLE_PUBLIC").val()==\'0\')
                    {
                        jQuery("#trforcetype, #tramount, #tredit, #trpayment, #tremail").hide();
                    }
                    if (jQuery("#MEMBER_ENABLE_PUBLIC").val()==\'1\')
                    {
                        jQuery("#trforcetype, #tramount, #tredit, #trpayment").show();
                        if (jQuery("#MEMBER_NEWFORM_PAYONLINE").val()==\'-1\') jQuery("#tremail").hide();
                        else jQuery("#tremail").show();
					}
				}
				initfields();
                jQuery("#MEMBER_ENABLE_PUBLIC").change(function() { initfields(); });
                jQuery("#MEMBER_NEWFORM_PAYONLINE").change(function() { initemail(); });
			})';
	print '</script>'."\n";
}


print '<span class="opacitymedium">'.$langs->trans("BlankSubscriptionFormDesc").'</span><br><br>';

$param = '';

$enabledisablehtml = $langs->trans("EnablePublicSubscriptionForm").' ';
if (empty($conf->global->MEMBER_ENABLE_PUBLIC)) {
	// Button off, click to enable
	$enabledisablehtml .= '<a class="reposition valignmiddle" href="'.$_SERVER["PHP_SELF"].'?action=setMEMBER_ENABLE_PUBLIC&token='.newToken().'&value=1'.$param.'">';
	$enabledisablehtml .= img_picto($langs->trans("Disabled"), 'switch_off');
	$enabledisablehtml .= '</a>';
} else {
	// Button on, click to disable
	$enabledisablehtml .= '<a class="reposition valignmiddle" href="'.$_SERVER["PHP_SELF"].'?action=setMEMBER_ENABLE_PUBLIC&token='.newToken().'&value=0'.$param.'">';
	$enabledisablehtml .= img_picto($langs->trans("Activated"), 'switch_on');
	$enabledisablehtml .= '</a>';
}
print $enabledisablehtml;
print '<input type="hidden" id="MEMBER_ENABLE_PUBLIC" name="MEMBER_ENABLE_PUBLIC" value="'.(empty($conf->global->MEMBER_ENABLE_PUBLIC) ? 0 : 1).'">';


print '<br>';

if (!empty($conf->global->MEMBER_ENABLE_PUBLIC)) {
	print '<br>';

	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';

	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("Parameter").'</td>';
	print '<td>'.$langs->trans("Value").'</td>';
	print "</tr>\n";

	// Force Type
	$adht = new AdherentType($db);
	print '<tr class="oddeven drag" id="trforcetype"><td>';
	print $langs->trans("ForceMemberType");
	print '</td><td>';
	$listofval = array();
	$listofval += $adht->liste_array(1);
	$forcetype = empty($conf->global->MEMBER_NEWFORM_FORCETYPE) ? -1 : $conf->global->MEMBER_NEWFORM_FORCETYPE;
	print $form->selectarray("MEMBER_NEWFORM_FORCETYPE", $listofval, $forcetype, count($listofval) > 1 ? 1 : 0);
	print "</td></tr>\n";

	// Force nature of member (mor/phy)
	$morphys["phy"] = $langs->trans("Physical");
	$morphys["mor"] = $langs->trans("Moral");
	print '<tr class="oddeven drag" id="trforcenature"><td>';
	print $langs->trans("ForceMemberNature");
	print '</td><td>';
	$forcenature = empty($conf->global->MEMBER_NEWFORM_FORCEMORPHY) ? 0 : $conf->global->MEMBER_NEWFORM_FORCEMORPHY;
	print $form->selectarray("MEMBER_NEWFORM_FORCEMORPHY", $morphys, $forcenature, 1);
	print "</td></tr>\n";

	// Amount
	print '<tr class="oddeven" id="tramount"><td>';
	print $langs->trans("DefaultAmount");
	print '</td><td>';
	print '<input type="text" class="right width50" id="MEMBER_NEWFORM_AMOUNT" name="MEMBER_NEWFORM_AMOUNT" value="'.(!empty($conf->global->MEMBER_NEWFORM_AMOUNT) ? $conf->global->MEMBER_NEWFORM_AMOUNT : '').'">';
	print "</td></tr>\n";

	// Can edit
	print '<tr class="oddeven" id="tredit"><td>';
	print $langs->trans("CanEditAmount");
	print '</td><td>';
	print $form->selectyesno("MEMBER_NEWFORM_EDITAMOUNT", (!empty($conf->global->MEMBER_NEWFORM_EDITAMOUNT) ? $conf->global->MEMBER_NEWFORM_EDITAMOUNT : 0), 1);
	print "</td></tr>\n";

	// Jump to an online payment page
	print '<tr class="oddeven" id="trpayment"><td>';
	print $langs->trans("MEMBER_NEWFORM_PAYONLINE");
	print '</td><td>';
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
	print $form->selectarray("MEMBER_NEWFORM_PAYONLINE", $listofval, (!empty($conf->global->MEMBER_NEWFORM_PAYONLINE) ? $conf->global->MEMBER_NEWFORM_PAYONLINE : ''), 0);
	print "</td></tr>\n";

	print '</table>';
	print '</div>';

	print '<div class="center">';
	print '<input type="submit" class="button button-edit" value="'.$langs->trans("Modify").'">';
	print '</div>';
}


print dol_get_fiche_end();

print '</form>';


if (!empty($conf->global->MEMBER_ENABLE_PUBLIC)) {
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
	print '<input type="text" id="publicurlmember" class="quatrevingtpercentminusx" value="'.$urlwithroot.'/public/members/new.php'.$entity_qr.'">';
	print '<a target="_blank" rel="noopener noreferrer" href="'.$urlwithroot.'/public/members/new.php'.$entity_qr.'">'.img_picto('', 'globe', 'class="paddingleft"').'</a>';
	print '</div>';
	print ajax_autoselect('publicurlmember');
}

// End of page
llxFooter();
$db->close();
