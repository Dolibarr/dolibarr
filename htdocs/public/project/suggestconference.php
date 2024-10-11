<?php
/* Copyright (C) 2021		Dorian Vabre			<dorian.vabre@gmail.com>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 *	\file       htdocs/public/project/suggestconference.php
 *	\ingroup    member
 *	\brief      Example of form to suggest a conference
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
// Do not use GETPOST here, function is not defined and define must be done before including main.inc.php
// Because 2 entities can have the same ref.
$entity = (!empty($_GET['entity']) ? (int) $_GET['entity'] : (!empty($_POST['entity']) ? (int) $_POST['entity'] : 1));
if (is_numeric($entity)) {
	define("DOLENTITY", $entity);
}

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/eventorganization/class/conferenceorbooth.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/paymentterm.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

global $dolibarr_main_url_root;

// Init vars
$errmsg = '';
$num = 0;
$error = 0;
$backtopage = GETPOST('backtopage', 'alpha');
$action = GETPOST('action', 'aZ09');

$eventtype = GETPOST("eventtype");
$email = GETPOST("email");
$societe = GETPOST("societe");
$label = GETPOST("label");
$note = GETPOST("note");
$datestart = dol_mktime(0, 0, 0, GETPOSTINT('datestartmonth'), GETPOSTINT('datestartday'), GETPOSTINT('datestartyear'));
$dateend = dol_mktime(23, 59, 59, GETPOSTINT('dateendmonth'), GETPOSTINT('dateendday'), GETPOSTINT('dateendyear'));

$id = GETPOST('id');

$project = new Project($db);
$resultproject = $project->fetch($id);
if ($resultproject < 0) {
	$error++;
	$errmsg .= $project->error;
}

// Security check
$securekeyreceived = GETPOST('securekey', 'alpha');
$securekeytocompare = dol_hash(getDolGlobalString('EVENTORGANIZATION_SECUREKEY') . 'conferenceorbooth'.$id, 'md5');

if ($securekeytocompare != $securekeyreceived) {
	print $langs->trans('MissingOrBadSecureKey');
	exit;
}

// Load translation files
$langs->loadLangs(array("main", "companies", "install", "other", "eventorganization"));

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('publicnewmembercard', 'globalcard'));

$extrafields = new ExtraFields($db);

$user->loadDefaultValues();

$cactioncomm = new CActionComm($db);
$arrayofconfboothtype = $cactioncomm->liste_array('', 'id', '', 0, "module='conference@eventorganization'");

// Security check
if (empty($conf->eventorganization->enabled)) {
	httponly_accessforbidden('Module Event organization not enabled');
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

	if (getDolGlobalString('PROJECT_IMAGE_PUBLIC_SUGGEST_CONFERENCE')) {
		print '<div class="backimagepublicsuggestconference">';
		print '<img id="idPROJECT_IMAGE_PUBLIC_SUGGEST_CONFERENCE" src="' . getDolGlobalString('PROJECT_IMAGE_PUBLIC_SUGGEST_CONFERENCE').'">';
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
$reshook = $hookmanager->executeHooks('doActions', $parameters, $project, $action);
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

// Action called when page is submitted
if (empty($reshook) && $action == 'add') {
	$error = 0;

	$urlback = '';

	$db->begin();

	if (!GETPOST("lastname")) {
		$error++;
		$errmsg .= $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Lastname"))."<br>\n";
	}
	if (!GETPOST("firstname")) {
		$error++;
		$errmsg .= $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Firstname"))."<br>\n";
	}
	if (!GETPOST("email")) {
		$error++;
		$errmsg .= $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Email"))."<br>\n";
	}
	if (!GETPOST("societe")) {
		$error++;
		$errmsg .= $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Societe"))."<br>\n";
	}
	if (!GETPOST("label")) {
		$error++;
		$errmsg .= $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Label"))."<br>\n";
	}
	if (!GETPOST("note")) {
		$error++;
		$errmsg .= $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Note"))."<br>\n";
	}
	if (GETPOST("email") && !isValidEmail(GETPOST("email"))) {
		$error++;
		$langs->load("errors");
		$errmsg .= $langs->trans("ErrorBadEMail", GETPOST("email"))."<br>\n";
	}

	if (!$error) {
		// Getting the thirdparty or creating it
		$thirdparty = new Societe($db);
		$resultfetchthirdparty = $thirdparty->fetch('', $societe);

		if ($resultfetchthirdparty < 0) {
			// If an error was found
			$error++;
			$errmsg .= $thirdparty->error;
			$errors = array_merge($errors, $thirdparty->errors);
		} elseif ($resultfetchthirdparty == 0) {	// No thirdparty found + a payment is expected
			// Creation of a new thirdparty
			$genericcompanyname = 'Unknown company';

			if (!empty($societe)) {
				$thirdparty->name     = $societe;
			} else {
				$thirdparty->name     = $genericcompanyname;
			}
			$thirdparty->address      = GETPOST("address");
			$thirdparty->zip          = GETPOST("zipcode");
			$thirdparty->town         = GETPOST("town");
			$thirdparty->client       = $thirdparty::PROSPECT;
			$thirdparty->fournisseur  = 0;
			$thirdparty->country_id   = GETPOSTINT("country_id");
			$thirdparty->state_id     = GETPOSTINT("state_id");
			$thirdparty->email        = ($emailcompany ? $emailcompany : $email);

			// Load object modCodeTiers
			$module = getDolGlobalString('SOCIETE_CODECLIENT_ADDON', 'mod_codeclient_leopard');
			if (substr($module, 0, 15) == 'mod_codeclient_' && substr($module, -3) == 'php') {
				$module = substr($module, 0, dol_strlen($module) - 4);
			}
			$dirsociete = array_merge(array('/core/modules/societe/'), $conf->modules_parts['societe']);
			foreach ($dirsociete as $dirroot) {
				$res = dol_include_once($dirroot.$module.'.php');
				if ($res) {
					break;
				}
			}
			$modCodeClient = new $module($db);

			if (empty($tmpcode) && !empty($modCodeClient->code_auto)) {
				$tmpcode = $modCodeClient->getNextValue($thirdparty, 0);
			}
			$thirdparty->code_client = $tmpcode;
			$readythirdparty = $thirdparty->create($user);
			if ($readythirdparty < 0) {
				$error++;
				$errmsg .= $thirdparty->error;
				$errors = array_merge($errors, $thirdparty->errors);
			} else {
				$thirdparty->country_code = getCountry($thirdparty->country_id, 2, $db, $langs);
				$thirdparty->country      = getCountry($thirdparty->country_code, 0, $db, $langs);
			}
		}
		// From there we have a thirdparty, now looking for the contact
		if (!$error) {
			$contact = new Contact($db);
			$resultcontact = $contact->fetch('', '', '', $email);
			if ($resultcontact <= 0) {
				// Need to create a contact
				$contact->socid = $thirdparty->id;
				$contact->lastname = (string) GETPOST("lastname", 'alpha');
				$contact->firstname = (string) GETPOST("firstname", 'alpha');
				$contact->address = (string) GETPOST("address", 'alpha');
				$contact->zip = (string) GETPOST("zipcode", 'alpha');
				$contact->town = (string) GETPOST("town", 'alpha');
				$contact->country_id = GETPOSTINT("country_id");
				$contact->state_id = GETPOSTINT("state_id");
				$contact->email = $email;
				$contact->statut = 1; //Default status to Actif
				$resultcreatecontact = $contact->create($user);
				if ($resultcreatecontact < 0) {
					$error++;
					$errmsg .= $contact->error;
				}
			}
		}

		if (!$error) {
			// Adding supplier tag and tag from setup to thirdparty
			$category = new Categorie($db);

			$resultcategory = $category->fetch(getDolGlobalString('EVENTORGANIZATION_CATEG_THIRDPARTY_CONF'));

			if ($resultcategory <= 0) {
				$error++;
				$errmsg .= $category->error;
			} else {
				$resultsetcategory = $thirdparty->setCategoriesCommon(array($category->id), Categorie::TYPE_CUSTOMER, false);
				if ($resultsetcategory < 0) {
					$error++;
					$errmsg .= $thirdparty->error;
				} else {
					$thirdparty->fournisseur = 1;

					// Load object modCodeFournisseur
					$module = getDolGlobalString('SOCIETE_CODECLIENT_ADDON', 'mod_codeclient_leopard');
					if (substr($module, 0, 15) == 'mod_codeclient_' && substr($module, -3) == 'php') {
						$module = substr($module, 0, dol_strlen($module) - 4);
					}
					$dirsociete = array_merge(array('/core/modules/societe/'), $conf->modules_parts['societe']);
					foreach ($dirsociete as $dirroot) {
						$res = dol_include_once($dirroot.$module.'.php');
						if ($res) {
							break;
						}
					}
					$modCodeFournisseur = new $module($db);
					if (empty($tmpcode) && !empty($modCodeFournisseur->code_auto)) {
						$tmpcode = $modCodeFournisseur->getNextValue($thirdparty, 1);
					}
					$thirdparty->code_fournisseur = $tmpcode;

					$res = $thirdparty->update(0, $user, 1, 1, 1);

					if ($res <= 0) {
						$error++;
					}
				}
			}
		}

		if (!$error) {
			// We have the contact and the thirdparty
			$conforbooth = new ConferenceOrBooth($db);
			$conforbooth->label = $label;
			$conforbooth->fk_soc = $thirdparty->id;
			$conforbooth->fk_project = $project->id;
			$conforbooth->note = $note;
			$conforbooth->fk_action = $eventtype;
			$conforbooth->datep = $datestart;
			$conforbooth->datep2 = $dateend;
			$conforbooth->datec = dol_now();
			$conforbooth->tms = dol_now();
			$conforbooth->firstname = $contact->firstname;
			$conforbooth->lastname = $contact->lastname;
			$conforbooth->ip = getUserRemoteIP();

			$nb_post_max = getDolGlobalInt("MAIN_SECURITY_MAX_POST_ON_PUBLIC_PAGES_BY_IP_ADDRESS", 200);
			$now = dol_now();
			$minmonthpost = dol_time_plus_duree($now, -1, "m");

			// Calculate nb of post for IP
			$nb_post_ip = 0;
			if ($nb_post_max > 0) {	// Calculate only if there is a limit to check
				$sql = "SELECT COUNT(ref) as nb_confs";
				$sql .= " FROM ".MAIN_DB_PREFIX."actioncomm";
				$sql .= " WHERE ip = '".$db->escape($conforbooth->ip)."'";
				$sql .= " AND datec > '".$db->idate($minmonthpost)."'";
				$resql = $db->query($sql);
				if ($resql) {
					$num = $db->num_rows($resql);
					$i = 0;
					while ($i < $num) {
						$i++;
						$obj = $db->fetch_object($resql);
						$nb_post_ip = $obj->nb_confs;
					}
				}
			}

			$resultconforbooth = 0;

			if ($nb_post_max > 0 && $nb_post_ip >= $nb_post_max) {
				$error++;
				$errmsg .= $langs->trans("AlreadyTooMuchPostOnThisIPAdress");
				array_push($conforbooth->errors, $langs->trans("AlreadyTooMuchPostOnThisIPAdress"));
				setEventMessage($errmsg, 'errors');
			} else {
				$resultconforbooth = $conforbooth->create($user);
			}
			if ($resultconforbooth <= 0) {
				$error++;
				$errmsg .= $conforbooth->error;
			} else {
				// Adding the contact to the project
				$resultaddcontact = $conforbooth->add_contact($contact->id, 'SPEAKER');
				if ($resultaddcontact < 0) {
					$error++;
					$errmsg .= $conforbooth->error;
				} else {
					$conforbooth->status = ConferenceOrBooth::STATUS_SUGGESTED;
					$conforbooth->update($user);

					// Sending mail
					require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
					include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
					$formmail = new FormMail($db);
					// Set output language
					$outputlangs = new Translate('', $conf);
					$outputlangs->setDefaultLang(empty($thirdparty->default_lang) ? $mysoc->default_lang : $thirdparty->default_lang);
					// Load traductions files required by page
					$outputlangs->loadLangs(array("main", "members", "eventorganization"));
					// Get email content from template
					$arraydefaultmessage = null;

					$labeltouse = getDolGlobalString('EVENTORGANIZATION_TEMPLATE_EMAIL_ASK_CONF');
					if (!empty($labeltouse)) {
						$arraydefaultmessage = $formmail->getEMailTemplate($db, 'conferenceorbooth', $user, $outputlangs, $labeltouse, 1, '');
					}

					if (!empty($labeltouse) && is_object($arraydefaultmessage) && $arraydefaultmessage->id > 0) {
						$subject = $arraydefaultmessage->topic;
						$msg     = $arraydefaultmessage->content;
					}

					$substitutionarray = getCommonSubstitutionArray($outputlangs, 0, null, $thirdparty);
					complete_substitutions_array($substitutionarray, $outputlangs, $project);

					$subjecttosend = make_substitutions($subject, $substitutionarray, $outputlangs);
					$texttosend = make_substitutions($msg, $substitutionarray, $outputlangs);

					$sendto = $thirdparty->email;
					$from = getDolGlobalString('MAILING_EMAIL_FROM');
					$urlback = $_SERVER["REQUEST_URI"];
					$trackid = 'proj'.$project->id;

					$ishtml = dol_textishtml($texttosend); // May contain urls

					$mailfile = new CMailFile($subjecttosend, $sendto, $from, $texttosend, array(), array(), array(), '', '', 0, $ishtml, '', '', $trackid);

					$result = $mailfile->sendfile();
					if ($result) {
						dol_syslog("EMail sent to ".$sendto, LOG_DEBUG, 0, '_payment');
					} else {
						dol_syslog("Failed to send EMail to ".$sendto, LOG_ERR, 0, '_payment');
					}
				}
			}
		}
	}

	if (!$error) {
		$db->commit();
		$securekeyurl = dol_hash(getDolGlobalString('EVENTORGANIZATION_SECUREKEY') . 'conferenceorbooth'.$id, 2);
		$redirection = $dolibarr_main_url_root.'/public/eventorganization/subscriptionok.php?id='.((int) $id).'&securekey='.urlencode($securekeyurl);
		header("Location: ".$redirection);
		exit;
	} else {
		$db->rollback();
	}
}


/*
 * View
 */

$form = new Form($db);
$formcompany = new FormCompany($db);

llxHeaderVierge($langs->trans("NewSuggestionOfConference"));


print '<div align="center">';
print '<div id="divsubscribe">';

print '<br>';

// Sub banner
print '<div class="center subscriptionformbanner subbanner justify margintoponly paddingtop marginbottomonly padingbottom">';
print load_fiche_titre($langs->trans("NewSuggestionOfConference"), '', '', 0, 0, 'center');
// Welcome message
print '<span class="opacitymedium">'.$langs->trans("EvntOrgRegistrationWelcomeMessage").'</span>';
print '<br>';
// Title
print '<span class="eventlabel large">'.dol_escape_htmltag($project->title . ' '. $project->label).'</span><br>';
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
if ($project->note_public) {
	print '<br><!-- note public --><span class="opacitymedium">'.dol_htmlentitiesbr($project->note_public).'</span><br>';
}

print '</div>';

print '<br>';


dol_htmloutput_errors($errmsg, $errors);

// Print form
print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST" name="newmember">'."\n";
print '<input type="hidden" name="token" value="'.newToken().'" / >';
print '<input type="hidden" name="entity" value="'.$entity.'" />';
print '<input type="hidden" name="action" value="add" />';
print '<input type="hidden" name="id" value="'.$id.'" />';
print '<input type="hidden" name="securekey" value="'.$securekeyreceived.'" />';

print '<br><span class="opacitymedium">'.$langs->trans("FieldsWithAreMandatory", '*').'</span><br>';
//print $langs->trans("FieldsWithIsForPublic",'**').'<br>';

print dol_get_fiche_head();

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

// Last Name
print '<tr><td><label for="lastname">'.$langs->trans("Lastname").'<span class="star">*</span></label></td>';
print '<td colspan="3"><input name="lastname" id="lastname" type="text" class="maxwidth100onsmartphone" maxlength="80" value="'.dol_escape_htmltag(GETPOST("lastname", 'alpha') ? GETPOST("lastname", 'alpha') : $object->lastname).'" autofocus="autofocus"></td>';
print '</tr>';
// First Name
print '<tr><td><label for="firstname">'.$langs->trans("Firstname").'<span class="star">*</span></label></td>';
print '<td colspan="3"><input name="firstname" id="firstname" type="text" class="maxwidth100onsmartphone" maxlength="80" value="'.dol_escape_htmltag(GETPOST("firstname", 'alpha') ? GETPOST("firstname", 'alpha') : $object->firstname).'" autofocus="autofocus"></td>';
print '</tr>';
// Email
print '<tr><td>'.$langs->trans("Email").'<span class="star">*</span></td><td><input type="text" name="email" maxlength="255" class="minwidth150" value="'.dol_escape_htmltag(GETPOST('email')).'"></td></tr>'."\n";
// Company
print '<tr id="trcompany" class="trcompany"><td>'.$langs->trans("Company").'<span class="star">*</span>';
print ' </td><td><input type="text" name="societe" class="minwidth150" value="'.dol_escape_htmltag(GETPOST('societe')).'"></td></tr>'."\n";
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
$country_id = GETPOST('country_id');
if (!$country_id && getDolGlobalString('MEMBER_NEWFORM_FORCECOUNTRYCODE')) {
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
if (!getDolGlobalString('SOCIETE_DISABLE_STATE')) {
	print '<tr><td>'.$langs->trans('State').'</td><td>';
	if ($country_code) {
		print $formcompany->select_state(GETPOST("state_id"), $country_code);
	} else {
		print '';
	}
	print '</td></tr>';
}
// Type of event
print '<tr><td>'.$langs->trans("Format").'<span class="star">*</span></td>'."\n";
print '<td>'.Form::selectarray('eventtype', $arrayofconfboothtype, $eventtype, 1).'</td>';
// Label
print '<tr><td>'.$langs->trans("LabelOfconference").'<span class="star">*</span></td>'."\n";
print '</td><td><input type="text" name="label" class="minwidth300" value="'.dol_escape_htmltag(GETPOST('label')).'"></td></tr>'."\n";
// Note
print '<tr><td>'.$langs->trans("Description").'<span class="star">*</span></td>'."\n";
print '<td><textarea name="note" id="note" wrap="soft" class="quatrevingtpercent" rows="'.ROWS_4.'">'.dol_escape_htmltag(GETPOST('note', 'restricthtml'), 0, 1).'</textarea></td></tr>'."\n";

print "</table>\n";

print dol_get_fiche_end();


// Show all action buttons
print '<div class="center">';
print '<br>';
print '<input type="submit" value="'.$langs->trans("SuggestConference").'" name="suggestconference"  id="suggestconference" class="button">';
print '<br><br>';




print "</form>\n";
print "<br>";
print '</div></div>';


llxFooterVierge();

$db->close();
