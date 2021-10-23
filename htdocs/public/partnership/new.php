<?php
/* Copyright (C) 2001-2002  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2001-2002  Jean-Louis Bergamo      <jlb@j1b.org>
 * Copyright (C) 2006-2013  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2012       Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2012       J. Fernando Lagrange    <fernando@demo-tic.org>
 * Copyright (C) 2018-2019  Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2018       Alexandre Spangaro      <aspangaro@open-dsi.fr>
 * Copyright (C) 2021       Waël Almoman            <info@almoman.com>
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
 *	\file       htdocs/public/partnership/new.php
 *	\ingroup    member
 *	\brief      Example of form to add a new member
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
if (!defined('NOIPCHECK')) {
	define('NOIPCHECK', '1'); // Do not check IP defined into conf $dolibarr_main_restrict_ip
}

// For MultiCompany module.
// Do not use GETPOST here, function is not defined and define must be done before including main.inc.php
// TODO This should be useless. Because entity must be retrieve from object ref and not from url.
$entity = (!empty($_GET['entity']) ? (int) $_GET['entity'] : (!empty($_POST['entity']) ? (int) $_POST['entity'] : 1));
if (is_numeric($entity)) {
	define("DOLENTITY", $entity);
}

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/partnership/class/partnership.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';

// Init vars
$errmsg = '';
$num = 0;
$error = 0;
$backtopage = GETPOST('backtopage', 'alpha');
$action = GETPOST('action', 'aZ09');

// Load translation files
$langs->loadLangs(array("main", "members", "companies", "install", "other"));

// Security check
if (empty($conf->partnership->enabled)) {
	accessforbidden('', 0, 0, 1);
}

if (empty($conf->global->PARTNERSHIP_ENABLE_PUBLIC)) {
	print $langs->trans("Auto subscription form for public visitors has not been enabled");
	exit;
}

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('publicnewpartnershipcard', 'globalcard'));

$extrafields = new ExtraFields($db);

$object = new Partnership($db);

$user->loadDefaultValues();


/**
 * Show header for new partnership
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

	print '<div class="center">';

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

	if (!empty($conf->global->PARTNERSHIP_IMAGE_PUBLIC_REGISTRATION)) {
		print '<div class="backimagepublicregistration">';
		print '<img id="idPARTNERSHIP_IMAGE_PUBLIC_INTERFACE" src="'.$conf->global->PARTNERSHIP_IMAGE_PUBLIC_REGISTRATION.'">';
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

	/*if (GETPOST('typeid') <= 0) {
		$error++;
		$errmsg .= $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Type"))."<br>\n";
	}*/
	if (!GETPOST('lastname')) {
		$error++;
		$errmsg .= $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Lastname"))."<br>\n";
	}
	if (!GETPOST('firstname')) {
		$error++;
		$errmsg .= $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Firstname"))."<br>\n";
	}
	if (empty(GETPOST('email'))) {
		$error++;
		$errmsg .= $langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Email'))."<br>\n";
	} elseif (GETPOST("email") && !isValidEmail(GETPOST("email"))) {
		$langs->load('errors');
		$error++;
		$errmsg .= $langs->trans("ErrorBadEMail", GETPOST("email"))."<br>\n";
	}

	$public = GETPOSTISSET('public') ? 1 : 0;

	if (!$error) {
		$partnership = new Partnership($db);

		// We try to find the thirdparty or the member
		if (empty($conf->global->PARTNERSHIP_IS_MANAGED_FOR) || $conf->global->PARTNERSHIP_IS_MANAGED_FOR == 'thirdparty') {
			$partnership->fk_member = 0;
		} elseif ($conf->global->PARTNERSHIP_IS_MANAGED_FOR == 'member') {
			$partnership->fk_soc = 0;
		}

		$partnership->statut      = -1;
		$partnership->firstname   = GETPOST('firstname');
		$partnership->lastname    = GETPOST('lastname');
		$partnership->address     = GETPOST('address');
		$partnership->zip         = GETPOST('zipcode');
		$partnership->town        = GETPOST('town');
		$partnership->email       = GETPOST('email');
		$partnership->country_id  = GETPOST('country_id', 'int');
		$partnership->state_id    = GETPOST('state_id', 'int');
		//$partnership->typeid      = $conf->global->PARTNERSHIP_NEWFORM_FORCETYPE ? $conf->global->PARTNERSHIP_NEWFORM_FORCETYPE : GETPOST('typeid', 'int');
		$partnership->note_private = GETPOST('note_private');

		// Fill array 'array_options' with data from add form
		$extrafields->fetch_name_optionals_label($partnership->table_element);
		$ret = $extrafields->setOptionalsFromPost(null, $partnership);
		if ($ret < 0) {
			$error++;
		}

		$result = $partnership->create($user);
		if ($result > 0) {
			require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
			$object = $partnership;

			/*
			$partnershipt = new PartnershipType($db);
			$partnershipt->fetch($object->typeid);

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
				$labeltouse = $conf->global->PARTNERSHIP_EMAIL_TEMPLATE_AUTOREGISTER;

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
				$texttosend = make_substitutions(dol_concatdesc($msg, $partnershipt->getMailOnValid()), $substitutionarray, $outputlangs);

				if ($subjecttosend && $texttosend) {
					$moreinheader = 'X-Dolibarr-Info: send_an_email by public/members/new.php'."\r\n";

					$result = $object->send_an_email($texttosend, $subjecttosend, array(), array(), array(), "", "", 0, -1, '', $moreinheader);
				}
			}
			*/

			// Send email to the foundation to say a new member subscribed with autosubscribe form
			if (!empty($conf->global->MAIN_INFO_SOCIETE_MAIL) && !empty($conf->global->PARTNERSHIP_AUTOREGISTER_NOTIF_MAIL_SUBJECT) &&
				  !empty($conf->global->PARTNERSHIP_AUTOREGISTER_NOTIF_MAIL)) {
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

				$to = $partnership->makeSubstitution($conf->global->MAIN_INFO_SOCIETE_MAIL);
				$from = $conf->global->PARTNERSHIP_MAIL_FROM;
				$mailfile = new CMailFile(
					'['.$appli.'] '.$conf->global->PARTNERSHIP_AUTOREGISTER_NOTIF_MAIL_SUBJECT,
					$to,
					$from,
					$partnership->makeSubstitution($conf->global->PARTNERSHIP_AUTOREGISTER_NOTIF_MAIL),
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

			if (!empty($backtopage)) {
				$urlback = $backtopage;
			} elseif (!empty($conf->global->PARTNERSHIP_URL_REDIRECT_SUBSCRIPTION)) {
				$urlback = $conf->global->PARTNERSHIP_URL_REDIRECT_SUBSCRIPTION;
				// TODO Make replacement of __AMOUNT__, etc...
			} else {
				$urlback = $_SERVER["PHP_SELF"]."?action=added&token=".newToken();
			}

			if (!empty($conf->global->PARTNERSHIP_NEWFORM_PAYONLINE) && $conf->global->PARTNERSHIP_NEWFORM_PAYONLINE != '-1') {
				if ($conf->global->PARTNERSHIP_NEWFORM_PAYONLINE == 'all') {
					$urlback = DOL_MAIN_URL_ROOT.'/public/payment/newpayment.php?from=partnershipnewform&source=membersubscription&ref='.urlencode($partnership->ref);
					if (price2num(GETPOST('amount', 'alpha'))) {
						$urlback .= '&amount='.price2num(GETPOST('amount', 'alpha'));
					}
					if (GETPOST('email')) {
						$urlback .= '&email='.urlencode(GETPOST('email'));
					}
					if (!empty($conf->global->PAYMENT_SECURITY_TOKEN)) {
						if (!empty($conf->global->PAYMENT_SECURITY_TOKEN_UNIQUE)) {
							$urlback .= '&securekey='.urlencode(dol_hash($conf->global->PAYMENT_SECURITY_TOKEN.'membersubscription'.$partnership->ref, 2));
						} else {
							$urlback .= '&securekey='.urlencode($conf->global->PAYMENT_SECURITY_TOKEN);
						}
					}
				} elseif ($conf->global->PARTNERSHIP_NEWFORM_PAYONLINE == 'paybox') {
					$urlback = DOL_MAIN_URL_ROOT.'/public/paybox/newpayment.php?from=partnershipnewform&source=membersubscription&ref='.urlencode($partnership->ref);
					if (price2num(GETPOST('amount', 'alpha'))) {
						$urlback .= '&amount='.price2num(GETPOST('amount', 'alpha'));
					}
					if (GETPOST('email')) {
						$urlback .= '&email='.urlencode(GETPOST('email'));
					}
					if (!empty($conf->global->PAYMENT_SECURITY_TOKEN)) {
						if (!empty($conf->global->PAYMENT_SECURITY_TOKEN_UNIQUE)) {
							$urlback .= '&securekey='.urlencode(dol_hash($conf->global->PAYMENT_SECURITY_TOKEN.'membersubscription'.$partnership->ref, 2));
						} else {
							$urlback .= '&securekey='.urlencode($conf->global->PAYMENT_SECURITY_TOKEN);
						}
					}
				} elseif ($conf->global->PARTNERSHIP_NEWFORM_PAYONLINE == 'paypal') {
					$urlback = DOL_MAIN_URL_ROOT.'/public/paypal/newpayment.php?from=partnershipnewform&source=membersubscription&ref='.urlencode($partnership->ref);
					if (price2num(GETPOST('amount', 'alpha'))) {
						$urlback .= '&amount='.price2num(GETPOST('amount', 'alpha'));
					}
					if (GETPOST('email')) {
						$urlback .= '&email='.urlencode(GETPOST('email'));
					}
					if (!empty($conf->global->PAYMENT_SECURITY_TOKEN)) {
						if (!empty($conf->global->PAYMENT_SECURITY_TOKEN_UNIQUE)) {
							$urlback .= '&securekey='.urlencode(dol_hash($conf->global->PAYMENT_SECURITY_TOKEN.'membersubscription'.$partnership->ref, 2));
						} else {
							$urlback .= '&securekey='.urlencode($conf->global->PAYMENT_SECURITY_TOKEN);
						}
					}
				} elseif ($conf->global->PARTNERSHIP_NEWFORM_PAYONLINE == 'stripe') {
					$urlback = DOL_MAIN_URL_ROOT.'/public/stripe/newpayment.php?from=partnershipnewform&source=membersubscription&ref='.$partnership->ref;
					if (price2num(GETPOST('amount', 'alpha'))) {
						$urlback .= '&amount='.price2num(GETPOST('amount', 'alpha'));
					}
					if (GETPOST('email')) {
						$urlback .= '&email='.urlencode(GETPOST('email'));
					}
					if (!empty($conf->global->PAYMENT_SECURITY_TOKEN)) {
						if (!empty($conf->global->PAYMENT_SECURITY_TOKEN_UNIQUE)) {
							$urlback .= '&securekey='.urlencode(dol_hash($conf->global->PAYMENT_SECURITY_TOKEN.'membersubscription'.$partnership->ref, 2));
						} else {
							$urlback .= '&securekey='.urlencode($conf->global->PAYMENT_SECURITY_TOKEN);
						}
					}
				} else {
					dol_print_error('', "Autosubscribe form is setup to ask an online payment for a not managed online payment");
					exit;
				}
			}

			if (!empty($entity)) {
				$urlback .= '&entity='.$entity;
			}
			dol_syslog("partnership ".$partnership->ref." was created, we redirect to ".$urlback);
		} else {
			$error++;
			$errmsg .= join('<br>', $partnership->errors);
		}
	}

	if (!$error) {
		$db->commit();

		Header("Location: ".$urlback);
		exit;
	} else {
		$db->rollback();
	}
}

// Action called after a submitted was send and member created successfully
// If PARTNERSHIP_URL_REDIRECT_SUBSCRIPTION is set to url we never go here because a redirect was done to this url.
// backtopage parameter with an url was set on member submit page, we never go here because a redirect was done to this url.
if (empty($reshook) && $action == 'added') {
	llxHeaderVierge($langs->trans("NewPartnershipForm"));

	// Si on a pas ete redirige
	print '<br>';
	print '<div class="center">';
	print $langs->trans("NewPartnershipbyWeb");
	print '</div>';

	llxFooterVierge();
	exit;
}



/*
 * View
 */

$form = new Form($db);
$formcompany = new FormCompany($db);
$partnershipt = new AdherentType($db);
$extrafields->fetch_name_optionals_label('partnership'); // fetch optionals attributes and labels


llxHeaderVierge($langs->trans("NewSubscription"));


print load_fiche_titre($langs->trans("NewSubscription"), '', '', 0, 0, 'center');


print '<div align="center">';
print '<div id="divsubscribe">';

print '<div class="center subscriptionformhelptext justify">';
if (!empty($conf->global->PARTNERSHIP_NEWFORM_TEXT)) {
	print $langs->trans($conf->global->PARTNERSHIP_NEWFORM_TEXT)."<br>\n";
} else {
	print $langs->trans("NewSubscriptionDesc", $conf->global->MAIN_INFO_SOCIETE_MAIL)."<br>\n";
}
print '</div>';

dol_htmloutput_errors($errmsg);

// Print form
print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST" name="newmember">'."\n";
print '<input type="hidden" name="token" value="'.newToken().'" / >';
print '<input type="hidden" name="entity" value="'.$entity.'" />';
print '<input type="hidden" name="action" value="add" />';

print '<br>';

print '<br><span class="opacitymedium">'.$langs->trans("FieldsWithAreMandatory", '*').'</span><br>';
//print $langs->trans("FieldsWithIsForPublic",'**').'<br>';

print dol_get_fiche_head('');

print '<script type="text/javascript">
jQuery(document).ready(function () {
    jQuery(document).ready(function () {
        jQuery("#selectcountry_id").change(function() {
           document.newmember.action.value="create";
           document.newmember.submit();
        });
    });
});
</script>';


print '<table class="border" summary="form to subscribe" id="tablesubscribe">'."\n";

// Type
/*
if (empty($conf->global->PARTNERSHIP_NEWFORM_FORCETYPE)) {
	$listoftype = $partnershipt->liste_array();
	$tmp = array_keys($listoftype);
	$defaulttype = '';
	$isempty = 1;
	if (count($listoftype) == 1) {
		$defaulttype = $tmp[0];
		$isempty = 0;
	}
	print '<tr><td class="titlefield">'.$langs->trans("Type").' <span style="color: red">*</span></td><td>';
	print $form->selectarray("typeid", $partnershipt->liste_array(1), GETPOST('typeid') ? GETPOST('typeid') : $defaulttype, $isempty);
	print '</td></tr>'."\n";
} else {
	$partnershipt->fetch($conf->global->PARTNERSHIP_NEWFORM_FORCETYPE);
	print '<input type="hidden" id="typeid" name="typeid" value="'.$conf->global->PARTNERSHIP_NEWFORM_FORCETYPE.'">';
}
*/

// Moral/Physic attribute
$morphys["phy"] = $langs->trans("Physical");
$morphys["mor"] = $langs->trans("Moral");
if (empty($conf->global->PARTNERSHIP_NEWFORM_FORCEMORPHY)) {
	print '<tr class="morphy"><td class="titlefield">'.$langs->trans('MemberNature').' <span style="color: red">*</span></td><td>'."\n";
	print $form->selectarray("morphy", $morphys, GETPOST('morphy'), 1);
	print '</td></tr>'."\n";
} else {
	print $morphys[$conf->global->PARTNERSHIP_NEWFORM_FORCEMORPHY];
	print '<input type="hidden" id="morphy" name="morphy" value="'.$conf->global->PARTNERSHIP_NEWFORM_FORCEMORPHY.'">';
}

// Company
print '<tr id="trcompany" class="trcompany"><td>'.$langs->trans("Company").'</td><td>';
print img_picto('', 'company', 'class="pictofixedwidth"');
print '<input type="text" name="societe" class="minwidth150" value="'.dol_escape_htmltag(GETPOST('societe')).'"></td></tr>'."\n";
// Title
print '<tr><td class="titlefield">'.$langs->trans('UserTitle').'</td><td>';
print $formcompany->select_civility(GETPOST('civility_id'), 'civility_id').'</td></tr>'."\n";
// Lastname
print '<tr><td>'.$langs->trans("Lastname").' <span style="color: red">*</span></td><td><input type="text" name="lastname" class="minwidth150" value="'.dol_escape_htmltag(GETPOST('lastname')).'"></td></tr>'."\n";
// Firstname
print '<tr><td>'.$langs->trans("Firstname").' <span style="color: red">*</span></td><td><input type="text" name="firstname" class="minwidth150" value="'.dol_escape_htmltag(GETPOST('firstname')).'"></td></tr>'."\n";
// EMail
print '<tr><td>'.$langs->trans("Email").($conf->global->PARTNERSHIP_MAIL_REQUIRED ? ' <span style="color:red;">*</span>' : '').'</td><td>';
//print img_picto('', 'email', 'class="pictofixedwidth"');
print '<input type="text" name="email" maxlength="255" class="minwidth200" value="'.dol_escape_htmltag(GETPOST('email')).'"></td></tr>'."\n";
// Login
if (empty($conf->global->PARTNERSHIP_LOGIN_NOT_REQUIRED)) {
	print '<tr><td>'.$langs->trans("Login").' <span style="color: red">*</span></td><td><input type="text" name="login" maxlength="50" class="minwidth100"value="'.dol_escape_htmltag(GETPOST('login')).'"></td></tr>'."\n";
	print '<tr><td>'.$langs->trans("Password").' <span style="color: red">*</span></td><td><input type="password" maxlength="128" name="pass1" class="minwidth100" value="'.GETPOST("pass1", "nohtml").'"></td></tr>'."\n";
	print '<tr><td>'.$langs->trans("PasswordAgain").' <span style="color: red">*</span></td><td><input type="password" maxlength="128" name="pass2" class="minwidth100" value="'.GETPOST("pass2", "nohtml").'"></td></tr>'."\n";
}
// Gender
print '<tr><td>'.$langs->trans("Gender").'</td>';
print '<td>';
$arraygender = array('man'=>$langs->trans("Genderman"), 'woman'=>$langs->trans("Genderwoman"));
print $form->selectarray('gender', $arraygender, GETPOST('gender') ?GETPOST('gender') : $object->gender, 1);
print '</td></tr>';
// Address
print '<tr><td>'.$langs->trans("Address").'</td><td>'."\n";
print '<textarea name="address" id="address" wrap="soft" class="quatrevingtpercent" rows="'.ROWS_3.'">'.dol_escape_htmltag(GETPOST('address', 'restricthtml'), 0, 1).'</textarea></td></tr>'."\n";
// Zip / Town
print '<tr><td>'.$langs->trans('Zip').' / '.$langs->trans('Town').'</td><td>';
print $formcompany->select_ziptown(GETPOST('zipcode'), 'zipcode', array('town', 'selectcountry_id', 'state_id'), 6, 1);
print ' / ';
print $formcompany->select_ziptown(GETPOST('town'), 'town', array('zipcode', 'selectcountry_id', 'state_id'), 0, 1);
print '</td></tr>';
// Country
print '<tr><td>'.$langs->trans('Country').'</td><td>';
print img_picto('', 'country', 'class="pictofixedwidth"');
$country_id = GETPOST('country_id', 'int');
if (!$country_id && !empty($conf->global->PARTNERSHIP_NEWFORM_FORCECOUNTRYCODE)) {
	$country_id = getCountry($conf->global->PARTNERSHIP_NEWFORM_FORCECOUNTRYCODE, 2, $db, $langs);
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
// Logo
//print '<tr><td>'.$langs->trans("URLPhoto").'</td><td><input type="text" name="photo" class="minwidth150" value="'.dol_escape_htmltag(GETPOST('photo')).'"></td></tr>'."\n";
// Other attributes
$tpl_context = 'public'; // define template context to public
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_add.tpl.php';
// Comments
print '<tr>';
print '<td class="tdtop">'.$langs->trans("Comments").'</td>';
print '<td class="tdtop"><textarea name="note_private" id="note_private" wrap="soft" class="quatrevingtpercent" rows="'.ROWS_3.'">'.dol_escape_htmltag(GETPOST('note_private', 'restricthtml'), 0, 1).'</textarea></td>';
print '</tr>'."\n";

print "</table>\n";

print dol_get_fiche_end();

// Save
print '<div class="center">';
print '<input type="submit" value="'.$langs->trans("Submit").'" id="submitsave" class="button">';
if (!empty($backtopage)) {
	print ' &nbsp; &nbsp; <input type="submit" value="'.$langs->trans("Cancel").'" id="submitcancel" class="button button-cancel">';
}
print '</div>';


print "</form>\n";
print "<br>";
print '</div></div>';


llxFooterVierge();

$db->close();
