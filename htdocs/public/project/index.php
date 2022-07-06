<?php
/* Copyright (C) 2021		Dorian Vabre			<dorian.vabre@gmail.com>
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
 *     	\file       htdocs/public/project/index.php
 *		\ingroup    core
 *		\brief      File to offer a way to suggest a conference or a booth for an event
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

// For MultiCompany module.
// Do not use GETPOST here, function is not defined and get of entity must be done before including main.inc.php
$entity = (!empty($_GET['entity']) ? (int) $_GET['entity'] : (!empty($_POST['entity']) ? (int) $_POST['entity'] : (!empty($_GET['e']) ? (int) $_GET['e'] : (!empty($_POST['e']) ? (int) $_POST['e'] : 1))));
if (is_numeric($entity)) {
	define("DOLENTITY", $entity);
}

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societeaccount.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
// Hook to be used by external payment modules (ie Payzen, ...)
include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
$hookmanager = new HookManager($db);
$hookmanager->initHooks(array('newpayment'));

// Load translation files
$langs->loadLangs(array("other", "dict", "bills", "companies", "errors", "paybox", "paypal", "stripe")); // File with generic data

// Security check
// No check on module enabled. Done later according to $validpaymentmethod

$action = GETPOST('action', 'aZ09');
$id = GETPOST('id', 'int');
$securekeyreceived = GETPOST("securekey", 'alpha');
$securekeytocompare = dol_hash($conf->global->EVENTORGANIZATION_SECUREKEY.'conferenceorbooth'.$id, 'md5');

if ($securekeytocompare != $securekeyreceived) {
	print $langs->trans('MissingOrBadSecureKey');
	exit;
}

// Define $urlwithroot
//$urlwithouturlroot=preg_replace('/'.preg_quote(DOL_URL_ROOT,'/').'$/i','',trim($dolibarr_main_url_root));
//$urlwithroot=$urlwithouturlroot.DOL_URL_ROOT;		// This is to use external domain name found into config file
$urlwithroot = DOL_MAIN_URL_ROOT; // This is to use same domain name than current. For Paypal payment, we can use internal URL like localhost.

$project = new Project($db);
$resultproject = $project->fetch($id);
if ($resultproject < 0) {
	$error++;
	$errmsg .= $project->error;
}

// Security check
if (empty($conf->project->enabled)) {
	accessforbidden('', 0, 0, 1);
}


/*
 * Actions
 */

if (GETPOST('suggestbooth')) {
	header("Location: ".dol_buildpath('/public/project/suggestbooth.php', 1).'?id='.$id."&securekey=".$securekeyreceived);
	exit;
}

if (GETPOST('suggestconference')) {
	header("Location: ".dol_buildpath('/public/project/suggestconference.php', 1).'?id='.$id."&securekey=".$securekeyreceived);
	exit;
}

if (GETPOST('viewandvote')) {
	header("Location: ".dol_buildpath('/public/project/viewandvote.php', 1).'?id='.$id."&securekey=".$securekeyreceived);
	exit;
}




/*
 * View
 */

$head = '';
if (!empty($conf->global->ONLINE_PAYMENT_CSS_URL)) {
	$head = '<link rel="stylesheet" type="text/css" href="'.$conf->global->ONLINE_PAYMENT_CSS_URL.'?lang='.$langs->defaultlang.'">'."\n";
}

$conf->dol_hide_topmenu = 1;
$conf->dol_hide_leftmenu = 1;

$replacemainarea = (empty($conf->dol_hide_leftmenu) ? '<div>' : '').'<div>';
llxHeader($head, $langs->trans("SuggestForm"), '', '', 0, 0, '', '', '', 'onlinepaymentbody', $replacemainarea);

print '<span id="dolpaymentspan"></span>'."\n";
print '<div class="center">'."\n";
print '<form id="dolpaymentform" class="center" name="paymentform" action="'.$_SERVER["PHP_SELF"].'" method="POST">'."\n";
print '<input type="hidden" name="token" value="'.newToken().'">'."\n";
print '<input type="hidden" name="action" value="dopayment">'."\n";
print '<input type="hidden" name="tag" value="'.GETPOST("tag", 'alpha').'">'."\n";
//print '<input type="hidden" name="suffix" value="'.dol_escape_htmltag($suffix).'">'."\n";
print '<input type="hidden" name="id" value="'.dol_escape_htmltag($id).'">'."\n";
print '<input type="hidden" name="securekey" value="'.dol_escape_htmltag($securekeyreceived).'">'."\n";
print '<input type="hidden" name="e" value="'.$entity.'" />';
print '<input type="hidden" name="forcesandbox" value="'.GETPOST('forcesandbox', 'int').'" />';
print "\n";


// Show logo (search order: logo defined by PAYMENT_LOGO_suffix, then PAYMENT_LOGO, then small company logo, large company logo, theme logo, common logo)
// Define logo and logosmall
$logosmall = $mysoc->logo_small;
$logo = $mysoc->logo;
$paramlogo = 'ONLINE_PAYMENT_LOGO_'.$suffix;
if (!empty($conf->global->$paramlogo)) {
	$logosmall = $conf->global->$paramlogo;
} elseif (!empty($conf->global->ONLINE_PAYMENT_LOGO)) {
	$logosmall = $conf->global->ONLINE_PAYMENT_LOGO;
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

if (!empty($conf->global->PROJECT_IMAGE_PUBLIC_ORGANIZEDEVENT)) {
	print '<div class="backimagepublicorganizedevent">';
	print '<img id="idPROJECT_IMAGE_PUBLIC_ORGANIZEDEVENT" src="'.$conf->global->PROJECT_IMAGE_PUBLIC_ORGANIZEDEVENT.'">';
	print '</div>';
}

print '<br>';


// Event summary
print '<div class="center">';
print '<span class="large">'.$project->title.'</span><br>';
print img_picto('', 'calendar', 'class="pictofixedwidth"').$langs->trans("Date").': ';
print dol_print_date($project->date_start, 'daytext');
if ($project->date_end && $project->date_start != $project->date_end) {
	print ' - '.dol_print_date($project->date_end, 'daytext');
}
print '<br><br>'."\n";
print $langs->trans("EvntOrgRegistrationWelcomeMessage")."\n";
print $project->note_public."\n";
//print img_picto('', 'map-marker-alt').$langs->trans("Location").': xxxx';
print '</div>';


print '<br>';

print '<table id="dolpaymenttable" summary="Payment form" class="center">'."\n";

print $text;

// Output payment summary form
print '<tr><td align="center">';

$found = false;
$error = 0;
$var = false;

$object = null;

print "\n";


// Show all action buttons
print '<br>';

// Output introduction text
$foundaction = 0;
if ($project->accept_booth_suggestions) {
	$foundaction++;
	print '<input type="submit" value="'.$langs->trans("SuggestBooth").'" id="suggestbooth" name="suggestbooth" class="button width500">';
	print '<br><br>';
}
if ($project->accept_conference_suggestions == 1 || $project->accept_conference_suggestions == 2) {		// Can suggest conferences
	$foundaction++;
	print '<input type="submit" value="'.$langs->trans("SuggestConference").'" id="suggestconference" name="suggestconference" class="button width500">';
	print '<br><br>';
}
if ($project->accept_conference_suggestions == 2 || $project->accept_conference_suggestions == 3) {		// Can vote for conferences
	$foundaction++;
	print '<input type="submit" value="'.$langs->trans("ViewAndVote").'" id="viewandvote" name="viewandvote" class="button width500">';
}

if (! $foundaction) {
	print '<span class="opacitymedium">'.$langs->trans("NoPublicActionsAllowedForThisEvent").'</span>';
}

print '</td></tr>'."\n";

print '</table>'."\n";

print '</form>'."\n";
print '</div>'."\n";
print '<br>';


htmlPrintOnlinePaymentFooter($mysoc, $langs, 1, $suffix, $object);

llxFooter('', 'public');

$db->close();
