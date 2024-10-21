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
 *     	\file       htdocs/public/payment/newpayment.php
 *		\ingroup    core
 *		\brief      File to offer a way to make a payment for a particular Dolibarr object
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
require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';

// Hook to be used by external payment modules (ie Payzen, ...)
$hookmanager = new HookManager($db);

$hookmanager->initHooks(array('newpayment'));

// For encryption
global $dolibarr_main_url_root;

// Load translation files
$langs->loadLangs(array("main", "other", "dict", "bills", "companies", "errors", "paybox", "paypal", "stripe")); // File with generic data

// Security check
// No check on module enabled. Done later according to $validpaymentmethod

$errmsg = '';
$action = GETPOST('action', 'aZ09');
$id = GETPOST('id');
$securekeyreceived = GETPOST("securekey");
$securekeytocompare = dol_hash(getDolGlobalString('EVENTORGANIZATION_SECUREKEY') . 'conferenceorbooth'.$id, 'md5');

if ($securekeytocompare != $securekeyreceived) {
	print $langs->trans('MissingOrBadSecureKey');
	exit;
}

$listofvotes = explode(',', $_SESSION["savevotes"]);


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
if (empty($conf->eventorganization->enabled)) {
	httponly_accessforbidden('Module Event organization not enabled');
}


/*
 * Actions
 */

$tmpthirdparty = new Societe($db);

$listOfConferences = '<tr><td>'.$langs->trans('Label').'</td>';
$listOfConferences .= '<td>'.$langs->trans('Type').'</td>';
$listOfConferences .= '<td>'.$langs->trans('ThirdParty').'</td>';
$listOfConferences .= '<td>'.$langs->trans('Note').'</td></tr>';

$sql = "SELECT a.id, a.fk_action, a.datep, a.datep2, a.label, a.fk_soc, a.note, ca.libelle as label
		FROM ".MAIN_DB_PREFIX."actioncomm as a
		INNER JOIN ".MAIN_DB_PREFIX."c_actioncomm as ca ON (a.fk_action=ca.id)
		WHERE a.status<2";

$sqlforconf = $sql." AND ca.module='conference@eventorganization'";
//$sqlforbooth = $sql." AND ca.module='booth@eventorganization'";

// For conferences
$result = $db->query($sqlforconf);
$i = 0;
while ($i < $db->num_rows($result)) {
	$obj = $db->fetch_object($result);
	if (!empty($obj->fk_soc)) {
		$resultthirdparty = $tmpthirdparty->fetch($obj->fk_soc);
		if ($resultthirdparty) {
			$thirdpartyname = $tmpthirdparty->name;
		} else {
			$thirdpartyname = '';
		}
	} else {
		$thirdpartyname = '';
	}

	$listOfConferences .= '<tr><td>'.$obj->label.'</td><td>'.$obj->label.'</td><td>'.$thirdpartyname.'</td><td>'.$obj->note.'</td>';
	$listOfConferences .= '<td><button type="submit" name="vote" value="'.$obj->id.'" class="button">'.$langs->trans("Vote").'</button></td></tr>';
	$i++;
}

// For booths
/*
$result = $db->query($sqlforbooth);
$i = 0;
while ($i < $db->num_rows($result)) {
	$obj = $db->fetch_object($result);
	if (!empty($obj->fk_soc)) {
		$resultthirdparty = $tmpthirdparty->fetch($obj->fk_soc);
		if ($resultthirdparty) {
			$thirdpartyname = $tmpthirdparty->name;
		} else {
			$thirdpartyname = '';
		}
	} else {
		$thirdpartyname = '';
	}

	$listOfBooths .= '<tr><td>'.$obj->label.'</td><td>'.$obj->libelle.'</td><td>'.$obj->datep.'</td><td>'.$obj->datep2.'</td><td>'.$thirdpartyname.'</td><td>'.$obj->note.'</td>';
	$listOfBooths .= '<td><button type="submit" name="vote" value="'.$obj->id.'" class="button">'.$langs->trans("Vote").'</button></td></tr>';
	$i++;
}
*/

// Get vote result
$idvote = GETPOST("vote");
$hashedvote = dol_hash(getDolGlobalString('EVENTORGANIZATION_SECUREKEY') . 'vote'.$idvote);

if (strlen($idvote)) {
	if (in_array($hashedvote, $listofvotes)) {
		// Has already voted
		$votestatus = 'ko';
	} else {
		// Has not already voted
		$conforbooth = new ActionComm($db);
		$resultconforbooth = $conforbooth->fetch($idvote);
		if ($resultconforbooth <= 0) {
			$error++;
			$errmsg .= $conforbooth->error;
		} else {
			// Process to vote
			$conforbooth->num_vote++;
			$resupdate = $conforbooth->update($user);
			if ($resupdate) {
				$votestatus = 'ok';
				$_SESSION["savevotes"] = $hashedvote.','.(empty($_SESSION["savevotes"]) ? '' : $_SESSION["savevotes"]); // Save voter
			} else {
				//Error during update
				$votestatus = 'err';
			}
		}
	}
	if ($votestatus == "ok") {
		setEventMessage($langs->trans("VoteOk"), 'mesgs');
	} elseif ($votestatus == "ko") {
		setEventMessage($langs->trans("AlreadyVoted"), 'warnings');
	} elseif ($votestatus == "err") {
		setEventMessage($langs->trans("VoteError"), 'warnings');
	}
	header("Refresh:0;url=".dol_buildpath('/public/project/viewandvote.php?id='.$id.'&securekey=', 1).$securekeyreceived);
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
print '<input type="hidden" name="forcesandbox" value="'.GETPOSTINT('forcesandbox').'" />';
print "\n";


// Show logo (search order: logo defined by PAYMENT_LOGO_suffix, then PAYMENT_LOGO, then small company logo, large company logo, theme logo, common logo)
// Define logo and logosmall
$logosmall = $mysoc->logo_small;
$logo = $mysoc->logo;
$paramlogo = 'ONLINE_PAYMENT_LOGO_'.$suffix;
if (getDolGlobalString($paramlogo)) {
	$logosmall = getDolGlobalString($paramlogo);
} elseif (getDolGlobalString('ONLINE_PAYMENT_LOGO')) {
	$logosmall = getDolGlobalString('ONLINE_PAYMENT_LOGO');
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
	if (!getDolGlobalString('MAIN_HIDE_POWERED_BY')) {
		print '<div class="poweredbypublicpayment opacitymedium right"><a class="poweredbyhref" href="https://www.dolibarr.org?utm_medium=website&utm_source=poweredby" target="dolibarr" rel="noopener">'.$langs->trans("PoweredBy").'<br><img class="poweredbyimg" src="'.DOL_URL_ROOT.'/theme/dolibarr_logo.svg" width="80px"></a></div>';
	}
	print '</div>';
}

if (getDolGlobalString('PROJECT_IMAGE_PUBLIC_SUGGEST_BOOTH')) {
	print '<div class="backimagepublicsuggestbooth">';
	print '<img id="idPROJECT_IMAGE_PUBLIC_SUGGEST_BOOTH" src="' . getDolGlobalString('PROJECT_IMAGE_PUBLIC_SUGGEST_BOOTH').'">';
	print '</div>';
}

print '<table id="welcome" class="center">'."\n";
$text  = '<tr><td class="textpublicpayment"><br><strong>'.$langs->trans("EvntOrgRegistrationWelcomeMessage").'</strong></td></tr>'."\n";
$text .= '<tr><td class="textpublicpayment">'.$langs->trans("EvntOrgVoteHelpMessage").' : "'.dol_escape_htmltag($project->title).'".<br><br></td></tr>'."\n";
$text .= '<tr><td class="textpublicpayment">'.dol_htmlentitiesbr($project->note_public).'</td></tr>'."\n";
print $text;
print '</table>'."\n";


print '<table cellpadding="10" id="conferences" border="1" class="center">'."\n";
print '<th colspan="7">'.$langs->trans("ListOfSuggestedConferences").'</th>';
print $listOfConferences.'<br>';
print '</table>'."\n";

/*
print '<br>';

print '<table border=1  cellpadding="10" id="conferences" class="center">'."\n";
print '<th colspan="7">'.$langs->trans("ListOfSuggestedBooths").'</th>';
print $listOfBooths.'<br>';
print '</table>'."\n";
*/

$object = null;

htmlPrintOnlineFooter($mysoc, $langs, 1, $suffix, $object);

llxFooter('', 'public');

$db->close();
