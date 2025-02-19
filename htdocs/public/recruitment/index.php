<?php
/* Copyright (C) 2020       Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 *       \file       htdocs/public/recruitment/index.php
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
require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/security.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';

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
$SECUREKEY = GETPOST("securekey");
$entity = GETPOSTINT('entity') ? GETPOSTINT('entity') : $conf->entity;
$backtopage = '';
$suffix = "";

// Load variable for pagination
$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
if (empty($page) || $page < 0 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
	$page = 0;
}     // If $page is not defined, or '' or -1 or if we click on clear filters
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

if (GETPOST('btn_view')) {
	unset($_SESSION['email_customer']);
}
if (isset($_SESSION['email_customer'])) {
	$email = $_SESSION['email_customer'];
}

$object = new RecruitmentJobPosition($db);

// Define $urlwithroot
//$urlwithouturlroot=preg_replace('/'.preg_quote(DOL_URL_ROOT,'/').'$/i','',trim($dolibarr_main_url_root));
//$urlwithroot=$urlwithouturlroot.DOL_URL_ROOT;		// This is to use external domain name found into config file
$urlwithroot = DOL_MAIN_URL_ROOT; // This is to use same domain name than current. For Paypal payment, we can use internal URL like localhost.

// Security check
if (empty($conf->recruitment->enabled)) {
	httponly_accessforbidden('Module Recruitment not enabled');
}


/*
 * Actions
 */

// None


/*
 * View
 */

$head = '';
if (getDolGlobalString('MAIN_RECRUITMENT_CSS_URL')) {
	$head = '<link rel="stylesheet" type="text/css" href="' . getDolGlobalString('MAIN_RECRUITMENT_CSS_URL').'?lang='.$langs->defaultlang.'">'."\n";
}

$conf->dol_hide_topmenu = 1;
$conf->dol_hide_leftmenu = 1;

if (!getDolGlobalString('RECRUITMENT_ENABLE_PUBLIC_INTERFACE')) {
	$langs->load("errors");
	print '<div class="error">'.$langs->trans('ErrorPublicInterfaceNotEnabled').'</div>';
	$db->close();
	exit();
}

$arrayofjs = array();
$arrayofcss = array();

$replacemainarea = (empty($conf->dol_hide_leftmenu) ? '<div>' : '').'<div>';
llxHeader($head, $langs->trans("PositionToBeFilled"), '', '', 0, 0, '', '', '', 'onlinepaymentbody', $replacemainarea, 1, 1);


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
if (getDolGlobalString($paramlogo)) {
	$logosmall = getDolGlobalString($paramlogo);
} elseif (getDolGlobalString('ONLINE_RECRUITMENT_LOGO')) {
	$logosmall = getDolGlobalString('ONLINE_RECRUITMENT_LOGO_');
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
	print '<img id="dolpaymentlogo" src="'.$urllogo.'">';
	print '</div>';
	if (!getDolGlobalString('MAIN_HIDE_POWERED_BY')) {
		print '<div class="poweredbypublicpayment opacitymedium right"><a class="poweredbyhref" href="https://www.dolibarr.org?utm_medium=website&utm_source=poweredby" target="dolibarr" rel="noopener">'.$langs->trans("PoweredBy").'<br><img class="poweredbyimg" src="'.DOL_URL_ROOT.'/theme/dolibarr_logo.svg" width="80px"></a></div>';
	}
	print '</div>';
}

if (getDolGlobalString('RECRUITMENT_IMAGE_PUBLIC_INTERFACE')) {
	print '<div class="backimagepublicrecruitment">';
	print '<img id="idPROJECT_IMAGE_PUBLIC_SUGGEST_BOOTH" src="' . getDolGlobalString('RECRUITMENT_IMAGE_PUBLIC_INTERFACE').'">';
	print '</div>';
}


$results = $object->fetchAll($sortorder, $sortfield, 0, 0, '(status:=:1)');
$now = dol_now();
$params = array();

if (is_array($results)) {
	if (empty($results)) {
		print '<br>';
		print $langs->trans("NoPositionOpen");
	} else {
		print '<br><br><br>';
		print '<span class="opacitymedium">'.$langs->trans("WeAreRecruiting").'</span>';
		print '<br><br><br>';
		print '<br class="hideonsmartphone">';

		foreach ($results as $job) {
			$object = $job;
			$arrayofpostulatebutton = array();

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
				$text .= '<tr><td class="textpublicpayment"><br>'.$langs->trans("JobOfferToBeFilled", $mysoc->name);
				$text .= ' &nbsp; - &nbsp; <strong>'.$mysoc->name.'</strong>';
				$text .= ' &nbsp; - &nbsp; <span class="nowraponall"><span class="fa fa-calendar secondary"></span> '.dol_print_date($object->date_creation).'</span>';
				$text .= '</td></tr>'."\n";
				$text .= '<tr><td class="textpublicpayment"><h1 class="paddingleft paddingright">'.$object->label.'</h1></td></tr>'."\n";
			}
			print $text;

			// Output payment summary form
			print '<tr><td class="left">';

			print '<div class="centpercent" id="tablepublicpayment">';
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
			print $tmpuser->getFullName($langs);
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

			$arrayofpostulatebutton[] = array(
				'url' => '/public/recruitment/view.php?ref='.$object->ref,
				'label' => $langs->trans('ApplyJobCandidature'),
				'lang' => 'recruitment',
				'perm' => true,
				'enabled' => true,
			);

			print '<div class="center">';
			print dolGetButtonAction('', $langs->trans("ApplyJobCandidature"), 'default', $arrayofpostulatebutton, 'applicate_'.$object->ref, true, $params);
			print '</div>';
			print '</div>'."\n";
			print "\n";


			if ($action != 'dosubmit') {
				if ($found && !$error) {
					// We are in a management option and no error
				} else {
					dol_print_error_email('ERRORSUBMITAPPLICATION');
				}
			} else {
				// Print
			}

			print '</td></tr>'."\n";

			print '</table>'."\n";

			print '<br><br class="hideonsmartphone"><br class="hideonsmartphone"><br class="hideonsmartphone">'."\n";
		}
	}
} else {
	dol_print_error($db, $object->error, $object->errors);
}

print '</form>'."\n";
print '</div>'."\n";
print '<br>';


htmlPrintOnlineFooter($mysoc, $langs);

llxFooter('', 'public');

$db->close();
