<?php
/* Copyright (C) 2001-2002  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2001-2002  Jean-Louis Bergamo      <jlb@j1b.org>
 * Copyright (C) 2006-2013  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2012       Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2012       J. Fernando Lagrange    <fernando@demo-tic.org>
 * Copyright (C) 2018-2019  Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2018       Alexandre Spangaro      <aspangaro@open-dsi.fr>
 * Copyright (C) 2021       Waël Almoman            <info@almoman.com>
 * Copyright (C) 2022       Udo Tamm                <dev@dolibit.de>
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
 *	\file       htdocs/public/members/new.php
 *	\ingroup    member
 *	\brief      Example of form to add a new member
 *
 *  Note that you can add following constant to change behaviour of page
 *  MEMBER_NEWFORM_AMOUNT               Default amount for auto-subscribe form
 *  MEMBER_MIN_AMOUNT                   Minimum amount
 *  MEMBER_NEWFORM_PAYONLINE            Suggest payment with paypal, paybox or stripe
 *  MEMBER_NEWFORM_DOLIBARRTURNOVER     Show field turnover (specific for dolibarr foundation)
 *  MEMBER_URL_REDIRECT_SUBSCRIPTION    Url to redirect once registration form has been submitted (hidden option, by default we just show a message on same page or redirect to the payment page)
 *  MEMBER_NEWFORM_FORCETYPE            Force type of member
 *  MEMBER_NEWFORM_FORCEMORPHY          Force nature of member (mor/phy)
 *  MEMBER_NEWFORM_FORCECOUNTRYCODE     Force country
 */

if (!defined('NOLOGIN')) {
	define("NOLOGIN", 1); // This means this output page does not require to be logged.
}
if (!defined('NOCSRFCHECK')) {
	define("NOCSRFCHECK", 1); // We accept to go on this page from external web site.
}
if (!defined('NOBROWSERNOTIF')) {
	define('NOBROWSERNOTIF', '1');
}


// For MultiCompany module.
// Do not use GETPOST here, function is not defined and define must be done before including main.inc.php
$entity = (!empty($_GET['entity']) ? (int) $_GET['entity'] : (!empty($_POST['entity']) ? (int) $_POST['entity'] : 1));
if (is_numeric($entity)) {
	define("DOLENTITY", $entity);
}


// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent_type.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/cunits.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

// Init vars
$backtopage = GETPOST('backtopage', 'alpha');
$action = GETPOST('action', 'aZ09');

$errmsg = '';
$num = 0;
$error = 0;

// Load translation files
$langs->loadLangs(array("main", "members", "companies", "install", "other", "errors"));

// Security check
if (!isModEnabled('adherent')) {
	httponly_accessforbidden('Module Membership not enabled');
}

if (!getDolGlobalString('MEMBER_ENABLE_PUBLIC')) {
	httponly_accessforbidden("Auto subscription form for public visitors has not been enabled");
}

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('publicnewmembercard', 'globalcard'));

$extrafields = new ExtraFields($db);

$object = new Adherent($db);

$user->loadDefaultValues();


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
function llxHeaderVierge($title, $head = "", $disablejs = 0, $disablehead = 0, $arrayofjs = '', $arrayofcss = '')
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

	print '<header class="center">';

	// Output html code for logo
	if ($urllogo) {
		print '<div class="backgreypublicpayment">';
		print '<div class="logopublicpayment">';
		print '<img id="dolpaymentlogo" src="'.$urllogo.'">';
		print '</div>';
		if (empty($conf->global->MAIN_HIDE_POWERED_BY)) {
			print '<div class="poweredbypublicpayment opacitymedium right"><a class="poweredbyhref" href="https://www.dolibarr.org?utm_medium=website&utm_source=poweredby" target="dolibarr" rel="noopener">'.$langs->trans("PoweredBy").'<br><img class="poweredbyimg" src="'.DOL_URL_ROOT.'/theme/dolibarr_logo.svg" width="80px"></a></div>';
		}
		print '</div>';
	}

	if (!empty($conf->global->MEMBER_IMAGE_PUBLIC_REGISTRATION)) {
		print '<div class="backimagepublicregistration">';
		print '<img id="idEVENTORGANIZATION_IMAGE_PUBLIC_INTERFACE" src="'.$conf->global->MEMBER_IMAGE_PUBLIC_REGISTRATION.'">';
		print '</div>';
	}

	print '</header>';

	print '<div class="divmainbodylarge">';
}

/**
 * Show footer for new member
 *
 * @return	void
 */
function llxFooterVierge()
{
	global $conf, $langs;

	print '</div>';

	printCommonFooter('public');

	if (!empty($conf->use_javascript_ajax)) {
		print "\n".'<!-- Includes JS Footer of Dolibarr -->'."\n";
		print '<script src="'.DOL_URL_ROOT.'/core/js/lib_foot.js.php?lang='.$langs->defaultlang.(!empty($ext) ? '&'.$ext : '').'"></script>'."\n";
	}

	print "</body>\n";
	print "</html>\n";
}



/*
 * Actions
 */

$parameters = array();
// Note that $action and $object may have been modified by some hooks
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action);
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

// Action called when page is submitted
if (empty($reshook) && $action == 'add') {
	$error = 0;
	$urlback = '';

	$db->begin();

	// test if login already exists
	if (empty($conf->global->ADHERENT_LOGIN_NOT_REQUIRED)) {
		if (!GETPOST('login')) {
			$error++;
			$errmsg .= $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Login"))."<br>\n";
		}
		$sql = "SELECT login FROM ".MAIN_DB_PREFIX."adherent WHERE login = '".$db->escape(GETPOST('login'))."'";
		$result = $db->query($sql);
		if ($result) {
			$num = $db->num_rows($result);
		}
		if ($num != 0) {
			$error++;
			$langs->load("errors");
			$errmsg .= $langs->trans("ErrorLoginAlreadyExists")."<br>\n";
		}
		if (!GETPOSTISSET("pass1") || !GETPOSTISSET("pass2") || GETPOST("pass1", 'none') == '' || GETPOST("pass2", 'none') == '' || GETPOST("pass1", 'none') != GETPOST("pass2", 'none')) {
			$error++;
			$langs->load("errors");
			$errmsg .= $langs->trans("ErrorPasswordsMustMatch")."<br>\n";
		}
		if (!GETPOST('email')) {
			$error++;
			$errmsg .= $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("EMail"))."<br>\n";
		}
	}
	if (GETPOST('typeid') <= 0) {
		$error++;
		$errmsg .= $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Type"))."<br>\n";
	}
	if (!in_array(GETPOST('morphy'), array('mor', 'phy'))) {
		$error++;
		$errmsg .= $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv('Nature'))."<br>\n";
	}
	if (!GETPOST('lastname')) {
		$error++;
		$errmsg .= $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Lastname"))."<br>\n";
	}
	if (!GETPOST('firstname')) {
		$error++;
		$errmsg .= $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Firstname"))."<br>\n";
	}
	if (!empty($conf->global->ADHERENT_MAIL_REQUIRED) && empty(GETPOST('email'))) {
		$error++;
		$errmsg .= $langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Email'))."<br>\n";
	} elseif (GETPOST("email") && !isValidEmail(GETPOST("email"))) {
		$langs->load('errors');
		$error++;
		$errmsg .= $langs->trans("ErrorBadEMail", GETPOST("email"))."<br>\n";
	}
	$birthday = dol_mktime(GETPOST("birthhour", 'int'), GETPOST("birthmin", 'int'), GETPOST("birthsec", 'int'), GETPOST("birthmonth", 'int'), GETPOST("birthday", 'int'), GETPOST("birthyear", 'int'));
	if (GETPOST("birthmonth") && empty($birthday)) {
		$error++;
		$langs->load("errors");
		$errmsg .= $langs->trans("ErrorBadDateFormat")."<br>\n";
	}
	if (!empty($conf->global->MEMBER_NEWFORM_DOLIBARRTURNOVER)) {
		if (GETPOST("morphy") == 'mor' && GETPOST('budget') <= 0) {
			$error++;
			$errmsg .= $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("TurnoverOrBudget"))."<br>\n";
		}
	}

	// Check Captcha code if is enabled
	if (!empty($conf->global->MAIN_SECURITY_ENABLECAPTCHA)) {
		$sessionkey = 'dol_antispam_value';
		$ok = (array_key_exists($sessionkey, $_SESSION) === true && (strtolower($_SESSION[$sessionkey]) == strtolower($_POST['code'])));
		if (!$ok) {
			$error++;
			$errmsg .= $langs->trans("ErrorBadValueForCode")."<br>\n";
			$action = '';
		}
	}

	$public = GETPOSTISSET('public') ? 1 : 0;

	if (!$error) {
		// E-mail looks OK and login does not exist
		$adh = new Adherent($db);
		$adh->statut      = -1;
		$adh->public      = $public;
		$adh->firstname   = GETPOST('firstname');
		$adh->lastname    = GETPOST('lastname');
		$adh->gender      = GETPOST('gender');
		$adh->civility_id = GETPOST('civility_id');
		$adh->societe     = GETPOST('societe');
		$adh->address     = GETPOST('address');
		$adh->zip         = GETPOST('zipcode');
		$adh->town        = GETPOST('town');
		$adh->email       = GETPOST('email');
		if (empty($conf->global->ADHERENT_LOGIN_NOT_REQUIRED)) {
			$adh->login       = GETPOST('login');
			$adh->pass        = GETPOST('pass1');
		}
		$adh->photo       = GETPOST('photo');
		$adh->country_id  = getDolGlobalString("MEMBER_NEWFORM_FORCECOUNTRYCODE", GETPOST('country_id', 'int'));
		$adh->state_id    = GETPOST('state_id', 'int');
		$adh->typeid      = getDolGlobalString("MEMBER_NEWFORM_FORCETYPE", GETPOST('typeid', 'int'));
		$adh->note_private = GETPOST('note_private');
		$adh->morphy      = getDolGlobalString("MEMBER_NEWFORM_FORCEMORPHY", GETPOST('morphy'));
		$adh->birth       = $birthday;

		$adh->ip = getUserRemoteIP();

		$nb_post_max = getDolGlobalInt("MAIN_SECURITY_MAX_POST_ON_PUBLIC_PAGES_BY_IP_ADDRESS", 200);
		$now = dol_now();
		$minmonthpost = dol_time_plus_duree($now, -1, "m");
		// Calculate nb of post for IP
		$nb_post_ip = 0;
		if ($nb_post_max > 0) {	// Calculate only if there is a limit to check
			$sql = "SELECT COUNT(ref) as nb_adh";
			$sql .= " FROM ".MAIN_DB_PREFIX."adherent";
			$sql .= " WHERE ip = '".$db->escape($adh->ip)."'";
			$sql .= " AND datec > '".$db->idate($minmonthpost)."'";
			$resql = $db->query($sql);
			if ($resql) {
				$num = $db->num_rows($resql);
				$i = 0;
				while ($i < $num) {
					$i++;
					$obj = $db->fetch_object($resql);
					$nb_post_ip = $obj->nb_adh;
				}
			}
		}


		// Fill array 'array_options' with data from add form
		$extrafields->fetch_name_optionals_label($adh->table_element);
		$ret = $extrafields->setOptionalsFromPost(null, $adh);
		if ($ret < 0) {
			$error++;
			$errmsg .= $adh->error;
		}

		if ($nb_post_max > 0 && $nb_post_ip >= $nb_post_max) {
			$error++;
			$errmsg .= $langs->trans("AlreadyTooMuchPostOnThisIPAdress");
			array_push($adh->errors, $langs->trans("AlreadyTooMuchPostOnThisIPAdress"));
		}

		if (!$error) {
			$result = $adh->create($user);
			if ($result > 0) {
				require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
				$object = $adh;

				$adht = new AdherentType($db);
				$adht->fetch($object->typeid);

				if ($object->email) {
					$subject = '';
					$msg = '';

					// Send subscription email
					include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
					$formmail = new FormMail($db);
					// Set output language
					$outputlangs = new Translate('', $conf);
					$outputlangs->setDefaultLang(empty($object->thirdparty->default_lang) ? $mysoc->default_lang : $object->thirdparty->default_lang);
					// Load traductions files required by page
					$outputlangs->loadLangs(array("main", "members"));
					// Get email content from template
					$arraydefaultmessage = null;
					$labeltouse = $conf->global->ADHERENT_EMAIL_TEMPLATE_AUTOREGISTER;

					if (!empty($labeltouse)) {
						$arraydefaultmessage = $formmail->getEMailTemplate($db, 'member', $user, $outputlangs, 0, 1, $labeltouse);
					}

					if (!empty($labeltouse) && is_object($arraydefaultmessage) && $arraydefaultmessage->id > 0) {
						$subject = $arraydefaultmessage->topic;
						$msg     = $arraydefaultmessage->content;
					}

					$substitutionarray = getCommonSubstitutionArray($outputlangs, 0, null, $object);
					complete_substitutions_array($substitutionarray, $outputlangs, $object);
					$subjecttosend = make_substitutions($subject, $substitutionarray, $outputlangs);
					$texttosend = make_substitutions(dol_concatdesc($msg, $adht->getMailOnValid()), $substitutionarray, $outputlangs);

					if ($subjecttosend && $texttosend) {
						$moreinheader = 'X-Dolibarr-Info: send_an_email by public/members/new.php'."\r\n";

						$result = $object->sendEmail($texttosend, $subjecttosend, array(), array(), array(), "", "", 0, -1, '', $moreinheader);
					}
					/*if ($result < 0) {
						$error++;
						setEventMessages($object->error, $object->errors, 'errors');
					}*/
				}

				// Send email to the foundation to say a new member subscribed with autosubscribe form
				if (!empty($conf->global->MAIN_INFO_SOCIETE_MAIL) && !empty($conf->global->ADHERENT_AUTOREGISTER_NOTIF_MAIL_SUBJECT) &&
					!empty($conf->global->ADHERENT_AUTOREGISTER_NOTIF_MAIL)) {
					// Define link to login card
					$appli = constant('DOL_APPLICATION_TITLE');
					if (!empty($conf->global->MAIN_APPLICATION_TITLE)) {
						$appli = $conf->global->MAIN_APPLICATION_TITLE;
						if (preg_match('/\d\.\d/', $appli)) {
							if (!preg_match('/'.preg_quote(DOL_VERSION).'/', $appli)) {
								$appli .= " (".DOL_VERSION.")"; // If new title contains a version that is different than core
							}
						} else {
							$appli .= " ".DOL_VERSION;
						}
					} else {
						$appli .= " ".DOL_VERSION;
					}

					$to = $adh->makeSubstitution($conf->global->MAIN_INFO_SOCIETE_MAIL);
					$from = getDolGlobalString('ADHERENT_MAIL_FROM');
					$mailfile = new CMailFile(
						'['.$appli.'] '.$conf->global->ADHERENT_AUTOREGISTER_NOTIF_MAIL_SUBJECT,
						$to,
						$from,
						$adh->makeSubstitution($conf->global->ADHERENT_AUTOREGISTER_NOTIF_MAIL),
						array(),
						array(),
						array(),
						"",
						"",
						0,
						-1
					);

					if (!$mailfile->sendfile()) {
						dol_syslog($langs->trans("ErrorFailedToSendMail", $from, $to), LOG_ERR);
					}
				}

				// Auto-create thirdparty on member creation
				if (!empty($conf->global->ADHERENT_DEFAULT_CREATE_THIRDPARTY)) {
					$company = new Societe($db);
					$result = $company->create_from_member($adh);
					if ($result < 0) {
						$error++;
						$errmsg .= join('<br>', $company->errors);
					}
				}

				if (!empty($backtopage)) {
					$urlback = $backtopage;
				} elseif (!empty($conf->global->MEMBER_URL_REDIRECT_SUBSCRIPTION)) {
					$urlback = $conf->global->MEMBER_URL_REDIRECT_SUBSCRIPTION;
					// TODO Make replacement of __AMOUNT__, etc...
				} else {
					$urlback = $_SERVER["PHP_SELF"]."?action=added&token=".newToken();
				}

				if (!empty($conf->global->MEMBER_NEWFORM_PAYONLINE) && $conf->global->MEMBER_NEWFORM_PAYONLINE != '-1') {
					if (empty($adht->caneditamount)) {			// If edition of amount not allowed
						// TODO Check amount is same than the amount required for the type of member or if not defined as the defeault amount into $conf->global->MEMBER_NEWFORM_AMOUNT
						// It is not so important because a test is done on return of payment validation.
					}

					$urlback = getOnlinePaymentUrl(0, 'member', $adh->ref, price2num(GETPOST('amount', 'alpha'), 'MT'), '', 0);

					if (GETPOST('email')) {
						$urlback .= '&email='.urlencode(GETPOST('email'));
					}
					if ($conf->global->MEMBER_NEWFORM_PAYONLINE != '-1' && $conf->global->MEMBER_NEWFORM_PAYONLINE != 'all') {
						$urlback .= '&paymentmethod='.urlencode($conf->global->MEMBER_NEWFORM_PAYONLINE);
					}
				} else {
					if (!empty($entity)) {
						$urlback .= '&entity='.((int) $entity);
					}
				}
			} else {
				$error++;
				$errmsg .= join('<br>', $adh->errors);
			}
		}
	}

	if (!$error) {
		$db->commit();

		Header("Location: ".$urlback);
		exit;
	} else {
		$db->rollback();
		$action = "create";
	}
}

// Action called after a submitted was send and member created successfully
// If MEMBER_URL_REDIRECT_SUBSCRIPTION is set to an url, we never go here because a redirect was done to this url. Same if we ask to redirect to the payment page.
// backtopage parameter with an url was set on member submit page, we never go here because a redirect was done to this url.

if (empty($reshook) && $action == 'added') {
	llxHeaderVierge($langs->trans("NewMemberForm"));

	// If we have not been redirected
	print '<br><br>';
	print '<div class="center">';
	print $langs->trans("NewMemberbyWeb");
	print '</div>';

	llxFooterVierge();
	exit;
}



/*
 * View
 */

$form = new Form($db);
$formcompany = new FormCompany($db);
$adht = new AdherentType($db);
$extrafields->fetch_name_optionals_label($object->table_element); // fetch optionals attributes and labels


llxHeaderVierge($langs->trans("NewSubscription"));

print '<br>';
print load_fiche_titre(img_picto('', 'member_nocolor', 'class="pictofixedwidth"').' &nbsp; '.$langs->trans("NewSubscription"), '', '', 0, 0, 'center');


print '<div align="center">';
print '<div id="divsubscribe">';

print '<div class="center subscriptionformhelptext opacitymedium justify">';
if (!empty($conf->global->MEMBER_NEWFORM_TEXT)) {
	print $langs->trans($conf->global->MEMBER_NEWFORM_TEXT)."<br>\n";
} else {
	print $langs->trans("NewSubscriptionDesc", getDolGlobalString("MAIN_INFO_SOCIETE_MAIL"))."<br>\n";
}
print '</div>';

dol_htmloutput_errors($errmsg);
dol_htmloutput_events();

// Print form
print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST" name="newmember">'."\n";
print '<input type="hidden" name="token" value="'.newToken().'" / >';
print '<input type="hidden" name="entity" value="'.$entity.'" />';

if (!empty($conf->global->MEMBER_SKIP_TABLE) || !empty($conf->global->MEMBER_NEWFORM_FORCETYPE) || $action == 'create') {
	print '<input type="hidden" name="action" value="add" />';
	print '<br>';

	$messagemandatory = '<span class="">'.$langs->trans("FieldsWithAreMandatory", '*').'</span>';
	//print '<br><span class="opacitymedium">'.$langs->trans("FieldsWithAreMandatory", '*').'</span><br>';
	//print $langs->trans("FieldsWithIsForPublic",'**').'<br>';

	print dol_get_fiche_head('');

	print '<script type="text/javascript">
	jQuery(document).ready(function () {
		jQuery(document).ready(function () {
			function initmorphy()
			{
				console.log("Call initmorphy");
				if (jQuery("#morphy").val() == \'phy\') {
					jQuery("#trcompany").hide();
				}
				if (jQuery("#morphy").val() == \'mor\') {
					jQuery("#trcompany").show();
				}
			}
			initmorphy();
			jQuery("#morphy").change(function() {
				initmorphy();
			});
			jQuery("#selectcountry_id").change(function() {
			document.newmember.action.value="create";
			document.newmember.submit();
			});
			jQuery("#typeid").change(function() {
			document.newmember.action.value="create";
			document.newmember.submit();
			});
		});
	});
	</script>';


	print '<table class="border" summary="form to subscribe" id="tablesubscribe">'."\n";

	// Type
	if (empty($conf->global->MEMBER_NEWFORM_FORCETYPE)) {
		$listoftype = $adht->liste_array();
		$tmp = array_keys($listoftype);
		$defaulttype = '';
		$isempty = 1;
		if (count($listoftype) == 1) {
			$defaulttype = $tmp[0];
			$isempty = 0;
		}
		print '<tr><td class="titlefield classfortooltip" title="'.dol_escape_htmltag($messagemandatory).'">'.$langs->trans("Type").' <span class="star">*</span></td><td>';
		print $form->selectarray("typeid", $adht->liste_array(1), GETPOST('typeid') ? GETPOST('typeid') : $defaulttype, $isempty);
		print '</td></tr>'."\n";
	} else {
		$adht->fetch($conf->global->MEMBER_NEWFORM_FORCETYPE);
		print '<input type="hidden" id="typeid" name="typeid" value="'.$conf->global->MEMBER_NEWFORM_FORCETYPE.'">';
	}

	// Moral/Physic attribute
	$morphys["phy"] = $langs->trans("Physical");
	$morphys["mor"] = $langs->trans("Moral");
	if (empty($conf->global->MEMBER_NEWFORM_FORCEMORPHY)) {
		print '<tr class="morphy"><td class="titlefield classfortooltip" title="'.dol_escape_htmltag($messagemandatory).'">'.$langs->trans('MemberNature').' <span class="star">*</span></td><td>'."\n";
		print $form->selectarray("morphy", $morphys, GETPOST('morphy'), 1);
		print '</td></tr>'."\n";
	} else {
		//print $morphys[$conf->global->MEMBER_NEWFORM_FORCEMORPHY];
		print '<input type="hidden" id="morphy" name="morphy" value="'.$conf->global->MEMBER_NEWFORM_FORCEMORPHY.'">';
	}

	// Company   // TODO : optional hide
	print '<tr id="trcompany" class="trcompany"><td>'.$langs->trans("Company").'</td><td>';
	print img_picto('', 'company', 'class="pictofixedwidth"');
	print '<input type="text" name="societe" class="minwidth150 widthcentpercentminusx" value="'.dol_escape_htmltag(GETPOST('societe')).'"></td></tr>'."\n";

	// Title
	print '<tr><td class="titlefield">'.$langs->trans('UserTitle').'</td><td>';
	print $formcompany->select_civility(GETPOST('civility_id'), 'civility_id').'</td></tr>'."\n";

	// Lastname
	print '<tr><td class="classfortooltip" title="'.dol_escape_htmltag($messagemandatory).'">'.$langs->trans("Lastname").' <span class="star">*</span></td><td><input type="text" name="lastname" class="minwidth150" value="'.dol_escape_htmltag(GETPOST('lastname')).'"></td></tr>'."\n";

	// Firstname
	print '<tr><td class="classfortooltip" title="'.dol_escape_htmltag($messagemandatory).'">'.$langs->trans("Firstname").' <span class="star">*</span></td><td><input type="text" name="firstname" class="minwidth150" value="'.dol_escape_htmltag(GETPOST('firstname')).'"></td></tr>'."\n";

	// EMail
	print '<tr><td class="'.(getDolGlobalString("ADHERENT_MAIL_REQUIRED") ? 'classfortooltip' : '').'" title="'.dol_escape_htmltag($messagemandatory).'">'.$langs->trans("Email").(getDolGlobalString("ADHERENT_MAIL_REQUIRED") ? ' <span class="star">*</span>' : '').'</td><td>';
	//print img_picto('', 'email', 'class="pictofixedwidth"');
	print '<input type="text" name="email" maxlength="255" class="minwidth200" value="'.dol_escape_htmltag(GETPOST('email')).'"></td></tr>'."\n";

	// Login
	if (empty($conf->global->ADHERENT_LOGIN_NOT_REQUIRED)) {
		print '<tr><td>'.$langs->trans("Login").' <span class="star">*</span></td><td><input type="text" name="login" maxlength="50" class="minwidth100"value="'.dol_escape_htmltag(GETPOST('login')).'"></td></tr>'."\n";
		print '<tr><td>'.$langs->trans("Password").' <span class="star">*</span></td><td><input type="password" maxlength="128" name="pass1" class="minwidth100" value="'.dol_escape_htmltag(GETPOST("pass1", "none", 2)).'"></td></tr>'."\n";
		print '<tr><td>'.$langs->trans("PasswordRetype").' <span class="star">*</span></td><td><input type="password" maxlength="128" name="pass2" class="minwidth100" value="'.dol_escape_htmltag(GETPOST("pass2", "none", 2)).'"></td></tr>'."\n";
	}

	// Gender
	print '<tr><td>'.$langs->trans("Gender").'</td>';
	print '<td>';
	$arraygender = array('man'=>$langs->trans("Genderman"), 'woman'=>$langs->trans("Genderwoman"), 'other'=>$langs->trans("Genderother"));
	print $form->selectarray('gender', $arraygender, GETPOST('gender', 'alphanohtml'), 1, 0, 0, '', 0, 0, 0, '', '', 1);
	print '</td></tr>';

	// Address
	print '<tr><td>'.$langs->trans("Address").'</td><td>'."\n";
	print '<textarea name="address" id="address" wrap="soft" class="quatrevingtpercent" rows="'.ROWS_3.'">'.dol_escape_htmltag(GETPOST('address', 'restricthtml'), 0, 1).'</textarea></td></tr>'."\n";

	// Zip / Town
	print '<tr><td>'.$langs->trans('Zip').' / '.$langs->trans('Town').'</td><td>';
	print $formcompany->select_ziptown(GETPOST('zipcode'), 'zipcode', array('town', 'selectcountry_id', 'state_id'), 0, 1, '', 'width75');
	print ' / ';
	print $formcompany->select_ziptown(GETPOST('town'), 'town', array('zipcode', 'selectcountry_id', 'state_id'), 0, 1);
	print '</td></tr>';

	// Country
	print '<tr><td>'.$langs->trans('Country').'</td><td>';
	print img_picto('', 'country', 'class="pictofixedwidth"');
	$country_id = GETPOST('country_id', 'int');
	if (!$country_id && !empty($conf->global->MEMBER_NEWFORM_FORCECOUNTRYCODE)) {
		$country_id = getCountry($conf->global->MEMBER_NEWFORM_FORCECOUNTRYCODE, 2, $db, $langs);
	}
	if (!$country_id && !empty($conf->geoipmaxmind->enabled)) {
		$country_code = dol_user_country();
		//print $country_code;
		if ($country_code) {
			$new_country_id = getCountry($country_code, 3, $db, $langs);
			//print 'xxx'.$country_code.' - '.$new_country_id;
			if ($new_country_id) {
				$country_id = $new_country_id;
			}
		}
	}
	$country_code = getCountry($country_id, 2, $db, $langs);
	print $form->select_country($country_id, 'country_id');
	print '</td></tr>';
	// State
	if (empty($conf->global->SOCIETE_DISABLE_STATE)) {
		print '<tr><td>'.$langs->trans('State').'</td><td>';
		if ($country_code) {
			print $formcompany->select_state(GETPOST("state_id"), $country_code);
		}
		print '</td></tr>';
	}

	// Birthday
	print '<tr id="trbirth" class="trbirth"><td>'.$langs->trans("DateOfBirth").'</td><td>';
	print $form->selectDate(!empty($birthday) ? $birthday : "", 'birth', 0, 0, 1, "newmember", 1, 0);
	print '</td></tr>'."\n";

	// Photo
	print '<tr><td>'.$langs->trans("URLPhoto").'</td><td><input type="text" name="photo" class="minwidth150" value="'.dol_escape_htmltag(GETPOST('photo')).'"></td></tr>'."\n";

	// Public
	$linkofpubliclist = DOL_MAIN_URL_ROOT.'/public/members/public_list.php'.((isModEnabled('multicompany')) ? '?entity='.$conf->entity : '');
	$publiclabel = $langs->trans("Public", getDolGlobalString('MAIN_INFO_SOCIETE_NOM'), $linkofpubliclist);
	print '<tr><td>'.$publiclabel.'</td><td><input type="checkbox" name="public"></td></tr>'."\n";

	// Other attributes
	$parameters['tpl_context']='public';	// define template context to public
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_add.tpl.php';

	// Comments
	print '<tr>';
	print '<td class="tdtop">'.$langs->trans("Comments").'</td>';
	print '<td class="tdtop"><textarea name="note_private" id="note_private" wrap="soft" class="quatrevingtpercent" rows="'.ROWS_3.'">'.dol_escape_htmltag(GETPOST('note_private', 'restricthtml'), 0, 1).'</textarea></td>';
	print '</tr>'."\n";

	// Add specific fields used by Dolibarr foundation for example
	// TODO Move this into generic feature.
	if (!empty($conf->global->MEMBER_NEWFORM_DOLIBARRTURNOVER)) {
		$arraybudget = array('50'=>'<= 100 000', '100'=>'<= 200 000', '200'=>'<= 500 000', '300'=>'<= 1 500 000', '600'=>'<= 3 000 000', '1000'=>'<= 5 000 000', '2000'=>'5 000 000+');
		print '<tr id="trbudget" class="trcompany"><td>'.$langs->trans("TurnoverOrBudget").' <span class="star">*</span></td><td>';
		print $form->selectarray('budget', $arraybudget, GETPOST('budget'), 1);
		print ' € or $';

		print '<script type="text/javascript">
		jQuery(document).ready(function () {
			initturnover();
			jQuery("#morphy").click(function() {
				initturnover();
			});
			jQuery("#budget").change(function() {
					if (jQuery("#budget").val() > 0) { jQuery(".amount").val(jQuery("#budget").val()); }
					else { jQuery("#budget").val(\'\'); }
			});
			/*jQuery("#typeid").change(function() {
				if (jQuery("#typeid").val()==1) { jQuery("#morphy").val(\'mor\'); }
				if (jQuery("#typeid").val()==2) { jQuery("#morphy").val(\'phy\'); }
				if (jQuery("#typeid").val()==3) { jQuery("#morphy").val(\'mor\'); }
				if (jQuery("#typeid").val()==4) { jQuery("#morphy").val(\'mor\'); }
				initturnover();
			});*/
			function initturnover() {
				if (jQuery("#morphy").val()==\'phy\') {
					jQuery(".amount").val(20);
					jQuery("#trbudget").hide();
					jQuery("#trcompany").hide();
				}
				if (jQuery("#morphy").val()==\'mor\') {
					jQuery(".amount").val(\'\');
					jQuery("#trcompany").show();
					jQuery("#trbirth").hide();
					jQuery("#trbudget").show();
					if (jQuery("#budget").val() > 0) { jQuery(".amount").val(jQuery("#budget").val()); }
					else { jQuery("#budget").val(\'\'); }
				}
			}
		});
		</script>';
		print '</td></tr>'."\n";
	}

	if (!empty($conf->global->MEMBER_NEWFORM_PAYONLINE)) {
		$typeid = $conf->global->MEMBER_NEWFORM_FORCETYPE ? $conf->global->MEMBER_NEWFORM_FORCETYPE : GETPOST('typeid', 'int');
		$adht = new AdherentType($db);
		$adht->fetch($typeid);
		$caneditamount = $adht->caneditamount;

		// Set amount for the subscription:
		// - First check the amount of the member type.
		$amountbytype = $adht->amountByType(1);		// Load the array of amount per type
		$amount = empty($amountbytype[$typeid]) ? (isset($amount) ? $amount : 0) : $amountbytype[$typeid];
		// - If not found, take the default amount only of the user is authorized to edit it
		if ($caneditamount && empty($amount) && !empty($conf->global->MEMBER_NEWFORM_AMOUNT)) {
			$amount = $conf->global->MEMBER_NEWFORM_AMOUNT;
		}
		// - If not set, we accept ot have amount defined as parameter (for backward compatibility).
		if (empty($amount)) {
			$amount = (GETPOST('amount') ? price2num(GETPOST('amount', 'alpha'), 'MT', 2) : '');
		}

		// Clean the amount
		$amount = price2num($amount);
		$showedamount = $amount>0? $amount: 0;
		// $conf->global->MEMBER_NEWFORM_PAYONLINE is 'paypal', 'paybox' or 'stripe'
		print '<tr><td>'.$langs->trans("Subscription");
		if (!empty($conf->global->MEMBER_EXT_URL_SUBSCRIPTION_INFO)) {
			print ' - <a href="'.$conf->global->MEMBER_EXT_URL_SUBSCRIPTION_INFO.'" rel="external" target="_blank" rel="noopener noreferrer">'.$langs->trans("SeeHere").'</a>';
		}
		print '</td><td class="nowrap">';

		if (empty($amount) && !empty($conf->global->MEMBER_NEWFORM_AMOUNT)) {
			$amount = $conf->global->MEMBER_NEWFORM_AMOUNT;
		}

		if ($caneditamount) {
			print '<input type="text" name="amount" id="amount" class="flat amount width50" value="'.$showedamount.'">';
			print ' '.$langs->trans("Currency".$conf->currency).'<span class="opacitymedium"> – ';
			print $amount > 0 ? $langs->trans("AnyAmountWithAdvisedAmount", price($amount, 0, $langs, 1, -1, -1, $conf->currency)): $langs->trans("AnyAmountWithoutAdvisedAmount");
			print '</span>';
		} else {
			print '<input type="hidden" name="amount" id="amount" class="flat amount" value="'.$showedamount.'">';
			print '<input type="text" name="amount" id="amounthidden" class="flat amount width50" disabled value="'.$showedamount.'">';
			print ' '.$langs->trans("Currency".$conf->currency);
		}
		print '</td></tr>';
	}

	// Display Captcha code if is enabled
	if (!empty($conf->global->MAIN_SECURITY_ENABLECAPTCHA)) {
		require_once DOL_DOCUMENT_ROOT.'/core/lib/security2.lib.php';
		print '<tr><td class="titlefield"><label for="email"><span class="fieldrequired">'.$langs->trans("SecurityCode").'</span></label></td><td>';
		print '<span class="span-icon-security inline-block">';
		print '<input id="securitycode" placeholder="'.$langs->trans("SecurityCode").'" class="flat input-icon-security width150" type="text" maxlength="5" name="code" tabindex="3" />';
		print '</span>';
		print '<span class="nowrap inline-block">';
		print '<img class="inline-block valignmiddle" src="'.DOL_URL_ROOT.'/core/antispamimage.php" border="0" width="80" height="32" id="img_securitycode" />';
		print '<a class="inline-block valignmiddle" href="'.$php_self.'" tabindex="4" data-role="button">'.img_picto($langs->trans("Refresh"), 'refresh', 'id="captcha_refresh_img"').'</a>';
		print '</span>';
		print '</td></tr>';
	}

	print "</table>\n";

	print dol_get_fiche_end();

	// Save / Submit
	print '<div class="center">';
	print '<input type="submit" value="'.$langs->trans("GetMembershipButtonLabel").'" id="submitsave" class="button">';
	if (!empty($backtopage)) {
		print ' &nbsp; &nbsp; <input type="submit" value="'.$langs->trans("Cancel").'" id="submitcancel" class="button button-cancel">';
	}
	print '</div>';


	print "</form>\n";
	print "<br>";
	print '</div></div>';
} else {  // Show the table of membership types
	// Get units
	$measuringUnits = new CUnits($db);
	$result = $measuringUnits->fetchAll('',	'',	0,	0, array('t.active' => 1));
	$units = array();
	foreach ($measuringUnits->records as $lines)
		$units[$lines->short_label] = $langs->trans(ucfirst($lines->label));

	$publiccounters = getDolGlobalString("MEMBER_COUNTERS_ARE_PUBLIC");
	$hidevoteallowed = getDolGlobalString("MEMBER_HIDE_VOTE_ALLOWED");

	$sql = "SELECT d.rowid, d.libelle as label, d.subscription, d.amount, d.caneditamount, d.vote, d.note, d.duration, d.statut as status, d.morphy,";
	$sql .= " COUNT(a.rowid) AS membercount";
	$sql .= " FROM ".MAIN_DB_PREFIX."adherent_type as d";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."adherent as a";
	$sql .= " ON d.rowid = a.fk_adherent_type AND a.statut > 0";
	$sql .= " WHERE d.entity IN (".getEntity('member_type').")";
	$sql .= " AND d.statut=1";
	$sql .= " GROUP BY d.rowid, d.libelle, d.subscription, d.amount, d.caneditamount, d.vote, d.note, d.duration, d.statut, d.morphy";

	$result = $db->query($sql);
	if ($result) {
		$num = $db->num_rows($result);

		print '<br><div class="div-table-responsive">';
		print '<table class="tagtable liste">'."\n";
		print '<input type="hidden" name="action" value="create">';

		print '<tr class="liste_titre">';
		print '<th>'.$langs->trans("Label").'</th>';
		print '<th class="center">'.$langs->trans("MembershipDuration").'</th>';
		print '<th class="center">'.$langs->trans("Amount").'</th>';
		print '<th class="center">'.$langs->trans("MembersNature").'</th>';
		if (empty($hidevoteallowed)) print '<th class="center">'.$langs->trans("VoteAllowed").'</th>';
		if ($publiccounters) print '<th class="center">'.$langs->trans("Members").'</th>';
		print '<th class="center">'.$langs->trans("NewSubscription").'</th>';
		print "</tr>\n";

		$i = 0;
		while ($i < $num) {
			$objp = $db->fetch_object($result);	// Load the member type and information on it

			print '<tr class="oddeven">';
			print '<td>'.dol_escape_htmltag($objp->label).'</td>';
			print '<td class="nowrap">';
			$unit = preg_replace("/[^a-zA-Z]+/", "", $objp->duration);
			print max(1, intval($objp->duration)).' '.$units[$unit];
			print '</td>';
			print '<td class="center"><span class="amount nowrap">';
			$displayedamount = max(intval($objp->amount), intval(getDolGlobalInt("MEMBER_MIN_AMOUNT")));
			$caneditamount = $objp->caneditamount;
			if ($objp->subscription) {
				if ($displayedamount > 0 || !$caneditamount) {
					print $displayedamount.' '.strtoupper($conf->currency);
				}
				if ($caneditamount && $displayedamount>0) {
					print $form->textwithpicto('', $langs->transnoentities("CanEditAmountShortForValues"), 1, 'help', '', 0, 3);
				} elseif ($caneditamount) {
					print $langs->transnoentities("CanEditAmountShort");
				}
			} else {
				print "–"; // No subscription required
			}
			print '</span></td>';
			print '<td class="center">';
			if ($objp->morphy == 'phy') {
				print $langs->trans("Physical");
			} elseif ($objp->morphy == 'mor') {
				print $langs->trans("Moral");
			} else {
				print $langs->trans("MorAndPhy");
			}
			print '</td>';
			if (empty($hidevoteallowed)) print '<td class="center">'.yn($objp->vote).'</td>';
			$membercount = $objp->membercount>0? $objp->membercount: "–";
			if ($publiccounters) print '<td class="center">'.$membercount.'</td>';
			print '<td class="center"><button class="button button-save reposition" name="typeid" type="submit" name="submit" value="'.$objp->rowid.'">'.$langs->trans("GetMembershipButtonLabel").'</button></td>';
			print "</tr>";
			$i++;
		}

		// If no record found
		if ($num == 0) {
			$colspan = 8;
			print '<tr><td colspan="'.$colspan.'" class="opacitymedium">'.$langs->trans("NoRecordFound").'</td></tr>';
		}

		print "</table>";
		print '</div>';

		print '</form>';
	} else {
		dol_print_error($db);
	}
}


llxFooterVierge();

$db->close();
