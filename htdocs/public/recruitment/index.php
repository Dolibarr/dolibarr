<?php
/* Copyright (C) 2020       Laurent Destailleur     <eldy@users.sourceforge.net>
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
 *       \file       htdocs/public/recruitment/view.php
 *       \ingroup    recruitment
 *       \brief      Public file to show on job
 */

if (!defined('NOLOGIN')) {
	define("NOLOGIN", 1); // This means this output page does not require to be logged.
}
if (!defined('NOCSRFCHECK')) {
	define("NOCSRFCHECK", 1); // We accept to go on this page from external web site.
}
if (!defined('NOIPCHECK')) {
	define('NOIPCHECK', '1'); // Do not check IP defined into conf $dolibarr_main_restrict_ip
}
if (!defined('NOBROWSERNOTIF')) {
	define('NOBROWSERNOTIF', '1');
}

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/recruitment/class/recruitmentjobposition.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/security.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';

// Load translation files required by the page
$langs->loadLangs(array("companies", "other", "recruitment"));

// Get parameters
$action   = GETPOST('action', 'aZ09');
$cancel   = GETPOST('cancel', 'alpha');
$email    = GETPOST('email', 'alpha');
$backtopage = '';

$ref = GETPOST('ref', 'alpha');

if (GETPOST('btn_view')) {
	unset($_SESSION['email_customer']);
}
if (isset($_SESSION['email_customer'])) {
	$email = $_SESSION['email_customer'];
}

$object = new RecruitmentJobPosition($db);

if (!$action) {
	if (!$ref) {
		print $langs->trans('ErrorBadParameters')." - ref missing";
		exit;
	} else {
		$object->fetch('', $ref);
	}
}

// Define $urlwithroot
//$urlwithouturlroot=preg_replace('/'.preg_quote(DOL_URL_ROOT,'/').'$/i','',trim($dolibarr_main_url_root));
//$urlwithroot=$urlwithouturlroot.DOL_URL_ROOT;		// This is to use external domain name found into config file
$urlwithroot = DOL_MAIN_URL_ROOT; // This is to use same domain name than current. For Paypal payment, we can use internal URL like localhost.

// Security check
if (empty($conf->recruitment->enabled)) {
	accessforbidden('', 0, 0, 1);
}


/*
 * Actions
 */

// None


/*
 * View
 */

$head = '';
if (!empty($conf->global->MAIN_RECRUITMENT_CSS_URL)) {
	$head = '<link rel="stylesheet" type="text/css" href="'.$conf->global->MAIN_RECRUITMENT_CSS_URL.'?lang='.$langs->defaultlang.'">'."\n";
}

$conf->dol_hide_topmenu = 1;
$conf->dol_hide_leftmenu = 1;

if (!$conf->global->RECRUITMENT_ENABLE_PUBLIC_INTERFACE) {
	$langs->load("errors");
	print '<div class="error">'.$langs->trans('ErrorPublicInterfaceNotEnabled').'</div>';
	$db->close();
	exit();
}

$arrayofjs = array();
$arrayofcss = array();

$replacemainarea = (empty($conf->dol_hide_leftmenu) ? '<div>' : '').'<div>';
llxHeader($head, $langs->trans("PositionToBeFilled"), '', '', 0, 0, '', '', '', 'onlinepaymentbody', $replacemainarea, 1);


print '<span id="dolpaymentspan"></span>'."\n";
print '<div class="center">'."\n";
print '<form id="dolpaymentform" class="center" name="paymentform" action="'.$_SERVER["PHP_SELF"].'" method="POST">'."\n";
print '<input type="hidden" name="token" value="'.newToken().'">'."\n";
print '<input type="hidden" name="action" value="dosign">'."\n";
print '<input type="hidden" name="tag" value="'.GETPOST("tag", 'alpha').'">'."\n";
print '<input type="hidden" name="suffix" value="'.GETPOST("suffix", 'alpha').'">'."\n";
print '<input type="hidden" name="securekey" value="'.$SECUREKEY.'">'."\n";
print '<input type="hidden" name="entity" value="'.$entity.'" />';
print "\n";
print '<!-- Form to view jobs -->'."\n";

// Show logo (search order: logo defined by ONLINE_SIGN_LOGO_suffix, then ONLINE_SIGN_LOGO_, then small company logo, large company logo, theme logo, common logo)
// Define logo and logosmall
$logosmall = $mysoc->logo_small;
$logo = $mysoc->logo;
$paramlogo = 'ONLINE_RECRUITMENT_LOGO_'.$suffix;
if (!empty($conf->global->$paramlogo)) {
	$logosmall = $conf->global->$paramlogo;
} elseif (!empty($conf->global->ONLINE_RECRUITMENT_LOGO)) {
	$logosmall = $conf->global->ONLINE_RECRUITMENT_LOGO_;
}
//print '<!-- Show logo (logosmall='.$logosmall.' logo='.$logo.') -->'."\n";
// Define urllogo
$urllogo = '';
$urllogofull = '';
if (!empty($logosmall) && is_readable($conf->mycompany->dir_output.'/logos/thumbs/'.$logosmall)) {
	$urllogo = DOL_URL_ROOT.'/viewimage.php?modulepart=mycompany&amp;entity='.$conf->entity.'&amp;file='.urlencode('logos/thumbs/'.$logosmall);
	$urllogofull = $dolibarr_main_url_root.'/viewimage.php?modulepart=mycompany&entity='.$conf->entity.'&file='.urlencode('logos/thumbs/'.$logosmall);
} elseif (!empty($logo) && is_readable($conf->mycompany->dir_output.'/logos/'.$logo)) {
	$urllogo = DOL_URL_ROOT.'/viewimage.php?modulepart=mycompany&amp;entity='.$conf->entity.'&amp;file='.urlencode('logos/'.$logo);
	$urllogofull = $dolibarr_main_url_root.'/viewimage.php?modulepart=mycompany&entity='.$conf->entity.'&file='.urlencode('logos/'.$logo);
}
// Output html code for logo
if ($urllogo) {
	print '<div class="backgreypublicpayment">';
	print '<div class="logopublicpayment">';
	print '<img id="dolpaymentlogo" src="'.$urllogo.'"';
	print '>';
	print '</div>';
	if (empty($conf->global->MAIN_HIDE_POWERED_BY)) {
		print '<div class="poweredbypublicpayment opacitymedium right"><a class="poweredbyhref" href="https://www.dolibarr.org?utm_medium=website&utm_source=poweredby" target="dolibarr" rel="noopener">'.$langs->trans("PoweredBy").'<br><img class="poweredbyimg" src="'.DOL_URL_ROOT.'/theme/dolibarr_logo.svg" width="80px"></a></div>';
	}
	print '</div>';
}

if (!empty($conf->global->RECRUITMENT_IMAGE_PUBLIC_INTERFACE)) {
	print '<div class="backimagepublicrecruitment">';
	print '<img id="idPROJECT_IMAGE_PUBLIC_SUGGEST_BOOTH" src="'.$conf->global->RECRUITMENT_IMAGE_PUBLIC_INTERFACE.'">';
	print '</div>';
}


// TODO List of jobs
print '<br>';
print $langs->trans("FeatureNotYetAvailable");


print '</form>'."\n";
print '</div>'."\n";
print '<br>';


htmlPrintOnlinePaymentFooter($mysoc, $langs);

llxFooter('', 'public');

$db->close();
