<?php
/* Copyright (C) 2021		Dorian Vabre			<dorian.vabre@gmail.com>
 * Copyright (C) 2024		Frédéric France			<frederic.france@free.fr>
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
// Because 2 entities can have the same ref.
$entity = (!empty($_GET['entity']) ? (int) $_GET['entity'] : (!empty($_POST['entity']) ? (int) $_POST['entity'] : (!empty($_GET['e']) ? (int) $_GET['e'] : (!empty($_POST['e']) ? (int) $_POST['e'] : 1))));
if (is_numeric($entity)) {
	define("DOLENTITY", $entity);
}

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societeaccount.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';

global $dolibarr_main_url_root;

// Load translation files
$langs->loadLangs(array("other", "dict", "bills", "companies", "errors", "paybox", "paypal", "stripe")); // File with generic data

// Security check
// No check on module enabled. Done later according to $validpaymentmethod

$errmsg = '';
$action = GETPOST('action', 'aZ09');
$id = GETPOSTINT('id');
$securekeyreceived = GETPOST("securekey", 'alpha');
$securekeytocompare = dol_hash(getDolGlobalString('EVENTORGANIZATION_SECUREKEY').'conferenceorbooth'.((int) $id), 'md5');

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

$hookmanager->initHooks(array('newpayment'));

$extrafields = new ExtraFields($db);

$user->loadDefaultValues();

// Security check
if (empty($conf->project->enabled)) {
	httponly_accessforbidden('Module Project not enabled');
}



/**
 * Show header for new member
 *
 * @param 	string		$title				Title
 * @param 	string		$head				Head array
 * @param 	int    		$disablejs			More content into html header
 * @param 	int    		$disablehead		More content into html header
 * @param 	array  		$arrayofjs			Array of complementary js files
 * @param 	array  		$arrayofcss			Array of complementary css files
 * @return	void
 */
function llxHeaderVierge($title, $head = "", $disablejs = 0, $disablehead = 0, $arrayofjs = [], $arrayofcss = [])
{
	global $user, $conf, $langs, $mysoc;

	top_htmlhead($head, $title, $disablejs, $disablehead, $arrayofjs, $arrayofcss); // Show html headers

	print '<body id="mainbody" class="publicnewmemberform">';

	// Define urllogo
	$urllogo = DOL_URL_ROOT.'/theme/common/login_logo.png';

	if (!empty($mysoc->logo_small) && is_readable($conf->mycompany->dir_output.'/logos/thumbs/'.$mysoc->logo_small)) {
		$urllogo = DOL_URL_ROOT.'/viewimage.php?cache=1&amp;modulepart=mycompany&amp;file='.urlencode('logos/thumbs/'.$mysoc->logo_small);
	} elseif (!empty($mysoc->logo) && is_readable($conf->mycompany->dir_output.'/logos/'.$mysoc->logo)) {
		$urllogo = DOL_URL_ROOT.'/viewimage.php?cache=1&amp;modulepart=mycompany&amp;file='.urlencode('logos/'.$mysoc->logo);
	} elseif (is_readable(DOL_DOCUMENT_ROOT.'/theme/dolibarr_logo.svg')) {
		$urllogo = DOL_URL_ROOT.'/theme/dolibarr_logo.svg';
	}

	print '<div class="center">';
	// Output html code for logo
	print '<div class="backgreypublicpayment">';
	print '<div class="logopublicpayment">';
	if ($urllogo) {
		print '<img id="dolpaymentlogo" src="'.$urllogo.'">';
	}
	if (empty($urllogo)) {
		print $mysoc->name;
	}
	print '</div>';
	if (!getDolGlobalString('MAIN_HIDE_POWERED_BY')) {
		print '<div class="poweredbypublicpayment opacitymedium right"><a class="poweredbyhref" href="https://www.dolibarr.org?utm_medium=website&utm_source=poweredby" target="dolibarr" rel="noopener">'.$langs->trans("PoweredBy").'<br><img class="poweredbyimg" src="'.DOL_URL_ROOT.'/theme/dolibarr_logo.svg" width="80px"></a></div>';
	}
	print '</div>';

	if (getDolGlobalString('PROJECT_IMAGE_PUBLIC_ORGANIZEDEVENT')) {
		print '<div class="backimagepubliceventorganizationsubscription">';
		print '<img id="idPROJECT_IMAGE_PUBLIC_ORGANIZEDEVENT" src="' . getDolGlobalString('PROJECT_IMAGE_PUBLIC_ORGANIZEDEVENT').'">';
		print '</div>';
	}

	print '</div>';

	print '<div class="divmainbodylarge">';
}

/**
 * Show footer for new member
 *
 * @return	void
 */
function llxFooterVierge()
{
	print '</div>';

	printCommonFooter('public');

	print "</body>\n";
	print "</html>\n";
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
if (getDolGlobalString('ONLINE_PAYMENT_CSS_URL')) {
	$head = '<link rel="stylesheet" type="text/css" href="' . getDolGlobalString('ONLINE_PAYMENT_CSS_URL').'?lang='.$langs->defaultlang.'">'."\n";
}

$conf->dol_hide_topmenu = 1;
$conf->dol_hide_leftmenu = 1;

$replacemainarea = (empty($conf->dol_hide_leftmenu) ? '<div>' : '').'<div>';

//llxHeader($head, $langs->trans("SuggestForm"), '', '', 0, 0, '', '', '', 'onlinepaymentbody', $replacemainarea);

llxHeaderVierge($langs->trans("SuggestForm"));



print '<span id="dolpaymentspan"></span>'."\n";
print '<div class="center">'."\n";

print '<form id="dolpaymentform" class="center" name="paymentform" action="'.$_SERVER["PHP_SELF"].'" method="POST">'."\n";
print '<input type="hidden" name="token" value="'.newToken().'">'."\n";
print '<input type="hidden" name="action" value="dopayment">'."\n";
print '<input type="hidden" name="tag" value="'.GETPOST("tag", 'alpha').'">'."\n";
//print '<input type="hidden" name="suffix" value="'.dol_escape_htmltag($suffix).'">'."\n";
print '<input type="hidden" name="id" value="'.dol_escape_htmltag((string) $id).'">'."\n";
print '<input type="hidden" name="securekey" value="'.dol_escape_htmltag($securekeyreceived).'">'."\n";
print '<input type="hidden" name="e" value="'.$entity.'" />';
print '<input type="hidden" name="forcesandbox" value="'.GETPOSTINT('forcesandbox').'" />';
print "\n";


print '<div align="center">';
print '<div id="divsubscribe">';


// Sub banner
print '<div class="center subscriptionformbanner subbanner justify margintoponly paddingtop marginbottomonly padingbottom">';
print load_fiche_titre($langs->trans("NewRegistration"), '', '', 0, '', 'center');
// Welcome message
print '<span class="opacitymedium">'.$langs->trans("EvntOrgRegistrationWelcomeMessage").'</span>';
print '<br>';
// Title
print '<span class="eventlabel large">'.dol_escape_htmltag($project->title . ' '. $conference->label).'</span><br>';
print '</div>';

// Help text
print '<div class="justify subscriptionformhelptext">';

if ($project->date_start_event || $project->date_end_event) {
	print '<br><span class="fa fa-calendar pictofixedwidth opacitymedium"></span>';
}
if ($project->date_start_event) {
	$format = 'day';
	$tmparray = dol_getdate($project->date_start_event, false, '');
	if ($tmparray['hours'] || $tmparray['minutes'] || $tmparray['minutes']) {
		$format = 'dayhour';
	}
	print dol_print_date($project->date_start_event, $format);
}
if ($project->date_start_event && $project->date_end_event) {
	print ' - ';
}
if ($project->date_end_event) {
	$format = 'day';
	$tmparray = dol_getdate($project->date_end_event, false, '');
	if ($tmparray['hours'] || $tmparray['minutes'] || $tmparray['minutes']) {
		$format = 'dayhour';
	}
	print dol_print_date($project->date_end_event, $format);
}
if ($project->date_start_event || $project->date_end_event) {
	print '<br>';
}
if ($project->location) {
	print '<span class="fa fa-map-marked-alt pictofixedwidth opacitymedium"></span>'.dol_escape_htmltag($project->location).'<br>';
}

print '</div>';


print '<br>';

print '<table id="dolsuggestboost" summary="Suggest a boost form" class="center">'."\n";

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
	print '<input type="submit" value="'.$langs->trans("SuggestBooth").'" id="suggestbooth" name="suggestbooth" class="button minwidth250">';
	print '<br><br>';
}
if ($project->accept_conference_suggestions == 1 || $project->accept_conference_suggestions == 2) {		// Can suggest conferences
	$foundaction++;
	print '<input type="submit" value="'.$langs->trans("SuggestConference").'" id="suggestconference" name="suggestconference" class="button minwidth250">';
	print '<br><br>';
}
if ($project->accept_conference_suggestions == 2 || $project->accept_conference_suggestions == 3) {		// Can vote for conferences
	$foundaction++;
	print '<input type="submit" value="'.$langs->trans("ViewAndVote").'" id="viewandvote" name="viewandvote" class="button minwidth250">';
}

if (! $foundaction) {
	print '<span class="opacitymedium">'.$langs->trans("NoPublicActionsAllowedForThisEvent").'</span>';
}

print '</td></tr>'."\n";

print '</table>'."\n";


print '</div></div>';


print '</form>'."\n";
print '</div>'."\n";
print '<br>';



htmlPrintOnlineFooter($mysoc, $langs, 1, $suffix, $object);

llxFooter('', 'public');

$db->close();
