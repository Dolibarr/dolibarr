<?php
/* Copyright (C) 2020       Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2024		Frédéric France			<frederic.france@free.fr>
 * Copyright (C) 2024		MDW						<mdeweerd@users.noreply.github.com>
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

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/recruitment/class/recruitmentjobposition.class.php';
require_once DOL_DOCUMENT_ROOT.'/recruitment/class/recruitmentcandidature.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/security.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/public.lib.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Societe $mysoc
 * @var Translate $langs
 */

// Load translation files required by the page
$langs->loadLangs(array("companies", "other", "recruitment"));

// Get parameters
$action   = GETPOST('action', 'aZ09');
$cancel   = GETPOST('cancel', 'alpha');
$email    = GETPOST('email', 'alpha');
$firstname    = GETPOST('firstname', 'alpha');
$lastname    = GETPOST('lastname', 'alpha');
$birthday    = GETPOST('birthday', 'alpha');
$phone    	 = GETPOST('phone', 'alpha');
$message	 = GETPOST('message', 'alpha');
$requestedremuneration = GETPOST('requestedremuneration', 'alpha');

$ref = GETPOST('ref', 'alpha');

if (GETPOST('btn_view')) {
	unset($_SESSION['email_customer']);
}
if (isset($_SESSION['email_customer'])) {
	$email = $_SESSION['email_customer'];
}

$object = new RecruitmentJobPosition($db);

if (!$ref) {
	print $langs->trans('ErrorBadParameters')." - ref missing";
	exit;
}


// Define $urlwithroot
//$urlwithouturlroot=preg_replace('/'.preg_quote(DOL_URL_ROOT,'/').'$/i','',trim($dolibarr_main_url_root));
//$urlwithroot=$urlwithouturlroot.DOL_URL_ROOT;		// This is to use external domain name found into config file
$urlwithroot = DOL_MAIN_URL_ROOT; // This is to use same domain name than current. For Paypal payment, we can use internal URL like localhost.
$backtopage = $urlwithroot.'/public/recruitment/index.php';

// Security check
if (!isModEnabled("recruitment")) {
	httponly_accessforbidden('Module Recruitment not enabled');
}

$object->fetch(0, $ref);
$user->loadDefaultValues();
$errmsg = "";

/*
 * Actions
 */

if ($cancel) {
	if (!empty($backtopage)) {
		header("Location: ".$backtopage);
		exit;
	}
}

if ($action == "dosubmit") {	// Test on permission not required here (anonymous action protected by mitigation of /public/... urls)
	$error = 0;
	$db->begin();
	if (!strlen($ref)) {
		$error++;
		array_push($object->errors, $langs->trans("ErrorFieldRequired", $langs->transnoentities("Ref")));
		$action = 'view';
	}
	if (!strlen($email)) {
		$error++;
		array_push($object->errors, $langs->trans("ErrorFieldRequired", $langs->transnoentities("Email")));
		$action = 'view';
	} else {
		if (!isValidEmail($email)) {
			$error++;
			array_push($object->errors, $langs->trans("ErrorEmailInvalid"));
			$action = 'view';
		}
	}
	if (!strlen($lastname)) {
		$error++;
		array_push($object->errors, $langs->trans("ErrorFieldRequired", $langs->transnoentities("Lastname")));
		$action = 'view';
	}

	if (!$error) {
		$sql = "SELECT rrc.rowid FROM ".MAIN_DB_PREFIX."recruitment_recruitmentcandidature as rrc";
		$sql .= " WHERE rrc.email = '". $db->escape($email)."'";
		$sql .= " AND rrc.entity IN (". getEntity($object->element, 0).")";
		$resql = $db->query($sql);
		if ($resql) {
			$num = $db->num_rows($resql);
			if ($num > 0) {
				$error++;
				setEventMessages($langs->trans("ErrorRecruitmmentCandidatureAlreadyExists", $email), null, 'errors');
			}
		} else {
			dol_print_error($db);
			$error++;
		}
	}

	if (!$error) {	// Test on permission not required here (anonymous action protected by mitigation of /public/... urls)
		$candidature = new RecruitmentCandidature($db);

		$candidature->firstname = GETPOST('firstname', 'alpha');
		$candidature->lastname = GETPOST('lastname', 'alpha');
		$candidature->email = GETPOST('email', 'alpha');
		$candidature->phone = GETPOST('phone', 'alpha');
		$candidature->date_birth = GETPOST('birthday', 'alpha');
		$candidature->requestedremuneration = GETPOST('requestedremuneration', 'alpha');
		$candidature->description = GETPOST('message', 'alpha');
		$candidature->fk_recruitmentjobposition = $object->id;

		$candidature->ip = getUserRemoteIP();

		// Test MAIN_SECURITY_MAX_POST_ON_PUBLIC_PAGES_BY_IP_ADDRESS
		$nb_post_max = getDolGlobalInt("MAIN_SECURITY_MAX_POST_ON_PUBLIC_PAGES_BY_IP_ADDRESS", 200);

		if (checkNbPostsForASpeceificIp($candidature, $nb_post_max) <= 0) {
			$error++;
			$errmsg .= implode('<br>', $candidature->errors);
		}
		if (!$error) {
			$result = $candidature->create($user);
			if ($result <= 0) {
				$error++;
				$errmsg .= implode('<br>', $candidature->errors);
			}
		}
		if (!$error) {
			$candidature->validate($user);
			if ($result <= 0) {
				$error++;
				$errmsg .= implode('<br>', $candidature->errors);
			}
		}
	}

	if (!$error) {
		$db->commit();
		setEventMessages($langs->trans("RecruitmentCandidatureSaved"), null);
		header("Location: " . $backtopage);
		exit;
	} else {
		$db->rollback();
		$action = "view";
	}
}

// Actions to send emails (for ticket, we need to manage the addfile and removefile only)
$triggersendname = 'CANDIDATURE_SENTBYMAIL';
$paramname = 'id';
$autocopy = 'MAIN_MAIL_AUTOCOPY_CANDIDATURE_TO'; // used to know the automatic BCC to add
$trackid = 'recruitmentcandidature'.$object->id;
include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';



/*
 * View
 */

$form = new Form($db);
$now = dol_now();

$head = '';
if (getDolGlobalString('MAIN_RECRUITMENT_CSS_URL')) {
	$head = '<link rel="stylesheet" type="text/css" href="' . getDolGlobalString('MAIN_RECRUITMENT_CSS_URL').'?lang='.$langs->defaultlang.'">'."\n";
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
llxHeader($head, $langs->trans("PositionToBeFilled"), '', '', 0, 0, '', '', '', 'onlinepaymentbody', $replacemainarea, 1, 1);
dol_htmloutput_errors($errmsg);

print '<span id="dolpaymentspan"></span>'."\n";
print '<div class="center">'."\n";
print '<form id="dolpaymentform" class="center" name="paymentform" action="'.$_SERVER["PHP_SELF"].'" method="POST">'."\n";
print '<input type="hidden" name="token" value="'.newToken().'">'."\n";
print '<input type="hidden" name="action" value="dosubmit">'."\n";
print '<input type="hidden" name="tag" value="'.GETPOST("tag", 'alpha').'">'."\n";
print '<input type="hidden" name="suffix" value="'.GETPOST("suffix", 'alpha').'">'."\n";
print '<input type="hidden" name="securekey" value="'.$SECUREKEY.'">'."\n";
print '<input type="hidden" name="entity" value="'.$entity.'" />';
print "\n";
print '<!-- Form to view job -->'."\n";

// Show logo (search order: logo defined by ONLINE_SIGN_LOGO_suffix, then ONLINE_SIGN_LOGO_, then small company logo, large company logo, theme logo, common logo)
// Define logo and logosmall
$logosmall = $mysoc->logo_small;
$logo = $mysoc->logo;
$paramlogo = 'ONLINE_RECRUITMENT_LOGO_'.$suffix;
if (getDolGlobalString($paramlogo)) {
	$logosmall = getDolGlobalString($paramlogo);
} elseif (getDolGlobalString('ONLINE_RECRUITMENT_LOGO')) {
	$logosmall = getDolGlobalString('ONLINE_RECRUITMENT_LOGO');
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
	if (!empty($mysoc->url)) {
		print '<a href="'.$mysoc->url.'" target="_blank" rel="noopener">';
	}
	print '<img id="dolpaymentlogo" src="'.$urllogofull.'">';
	if (!empty($mysoc->url)) {
		print '</a>';
	}
	print '</div>';
	if (!getDolGlobalString('MAIN_HIDE_POWERED_BY')) {
		print '<div class="poweredbypublicpayment opacitymedium right"><a class="poweredbyhref" href="https://www.dolibarr.org?utm_medium=website&utm_source=poweredby" target="dolibarr" rel="noopener">'.$langs->trans("PoweredBy").'<br><img class="poweredbyimg" src="'.DOL_URL_ROOT.'/theme/dolibarr_logo.svg" width="80px"></a></div>';
	}
	print '</div>';
}

if (getDolGlobalString('RECRUITMENT_IMAGE_PUBLIC_INTERFACE')) {
	print '<div class="backimagepublicrecruitment">';
	print '<img id="idRECRUITMENT_IMAGE_PUBLIC_INTERFACE" src="' . getDolGlobalString('RECRUITMENT_IMAGE_PUBLIC_INTERFACE').'">';
	print '</div>';
}


print '<table id="dolpaymenttable" summary="Job position offer" class="center">'."\n";

// Output introduction text
$text = '';
if (getDolGlobalString('RECRUITMENT_NEWFORM_TEXT')) {
	$reg = array();
	if (preg_match('/^\((.*)\)$/', $conf->global->RECRUITMENT_NEWFORM_TEXT, $reg)) {
		$text .= $langs->trans($reg[1])."<br>\n";
	} else {
		$text .= getDolGlobalString('RECRUITMENT_NEWFORM_TEXT') . "<br>\n";
	}
	$text = '<tr><td align="center"><br>'.$text.'<br></td></tr>'."\n";
}
if (empty($text)) {
	$text .= '<tr><td class="textpublicpayment" colspan=2><br>'.$langs->trans("JobOfferToBeFilled", $mysoc->name);
	$text .= ' &nbsp; - &nbsp; <strong>'.$mysoc->name.'</strong>';
	$text .= ' &nbsp; - &nbsp; <span class="nowraponall"><span class="fa fa-calendar secondary"></span> '.dol_print_date($object->date_creation).'</span>';
	$text .= '</td></tr>'."\n";
	$text .= '<tr><td class="textpublicpayment" colspan=2><h1 class="paddingleft paddingright">'.$object->label.'</h1><br></td></tr>'."\n";
}
print $text;

// Output payment summary form
print '<tr><td class="left" colspan=2>';

print '<div with="100%" id="tablepublicpayment">';
print '<div class="opacitymedium">'.$langs->trans("ThisIsInformationOnJobPosition").' :</div>'."\n";

$error = 0;
$found = true;

print '<br>';

// Label
print $langs->trans("Label").' : ';
print '<b>'.dol_escape_htmltag($object->label).'</b><br>';

// Date
print  $langs->trans("DateExpected").' : ';
print '<b>';
if ($object->date_planned > $now) {
	print dol_print_date($object->date_planned, 'day');
} else {
	print $langs->trans("ASAP");
}
print '</b><br>';

// Remuneration
print  $langs->trans("Remuneration").' : ';
print '<b>';
print dol_escape_htmltag($object->remuneration_suggested);
print '</b><br>';

// Contact
$tmpuser = new User($db);
$tmpuser->fetch($object->fk_user_recruiter);

print  $langs->trans("ContactForRecruitment").' : ';
$emailforcontact = $object->email_recruiter;
if (empty($emailforcontact)) {
	$emailforcontact = $tmpuser->email;
	if (empty($emailforcontact)) {
		$emailforcontact = $mysoc->email;
	}
}
print '<b class="wordbreak">';
print $tmpuser->getFullName(-1);
print ' &nbsp; '.dol_print_email($emailforcontact, 0, 0, 1, 0, 0, 'envelope');
print '</b>';
print '</b><br>';

if ($object->status == RecruitmentJobPosition::STATUS_RECRUITED) {
	print info_admin($langs->trans("JobClosedTextCandidateFound"), 0, 0, '0', 'warning');
}
if ($object->status == RecruitmentJobPosition::STATUS_CANCELED) {
	print info_admin($langs->trans("JobClosedTextCanceled"), 0, 0, '0', 'warning');
}

print '<br>';

// Description

$text = $object->description;
print $text;
print '<input type="hidden" name="ref" value="'.$object->ref.'">';

print '</div>'."\n";
print "\n";


if ($action != 'dosubmit') {
	if ($found && !$error) {
		// We are in a management option and no error
		print '</td></tr>'."\n";
		print '<tr><td class="titlefieldcreate fieldrequired left">'.$langs->trans("Lastname").'</td><td class="left">';
		print '<input type="text" class="flat minwidth400 --success" name="lastname" maxlength="128" value="'.$lastname.'">';
		print '</td></tr>'."\n";

		print '<tr><td class="titlefieldcreate left">'.$langs->trans("Firstname").'</td><td class="left">';
		print '<input type="text" class="flat minwidth400 --success" name="firstname" maxlength="128" value="'.$firstname.'">';
		print '</td></tr>'."\n";

		print '<tr><td class="titlefieldcreate fieldrequired left">'.$langs->trans("Email").'</td><td class="left">';
		print img_picto("", "email").'<input type="text" class="flat minwidth100 --success" name="email" value="'.$email.'">';
		print '</td></tr>'."\n";

		print '<tr><td class="titlefieldcreate left">'.$langs->trans("Phone").'</td><td class="left">';
		print img_picto("", "phone").'<input type="text" class="flat minwidth100 --success" name="phone" value="'.$phone.'">';
		print '</td></tr>'."\n";

		print '<tr><td class="titlefieldcreate left minwidth300">'.$langs->trans("DateOfBirth").'</td><td class="left">';
		print $form->selectDate($birthday, 'birthday', 0, 0, 1, "", 1, 0);
		print '</td></tr>'."\n";

		print '<tr><td class="titlefieldcreate left">'.$langs->trans("RequestedRemuneration").'</td><td class="left">';
		print '<input type="text" class="flat minwidth100 --success" name="requestedremuneration" value="'.$requestedremuneration.'">';
		print '</td></tr>'."\n";

		print '<tr><td class="titlefieldcreate left">'.$langs->trans("Message").'</td><td class="left">';
		print '<textarea class="flat quatrevingtpercent" rows="'.ROWS_5.'" name="message">'.$message.'</textarea>';
		print '</td></tr>'."\n";

		print '<tr><td colspan=2>';
		print $form->buttonsSaveCancel('Submit', 'Cancel');
		print '</td></tr>'."\n";
	} else {
		dol_print_error_email('ERRORSUBMITAPPLICATION');
	}
} else {
	// Print
}

print '</td></tr>'."\n";

print '</table>'."\n";

print '</form>'."\n";
print '</div>'."\n";
print '<br>';


htmlPrintOnlineFooter($mysoc, $langs);

llxFooter('', 'public');

$db->close();
