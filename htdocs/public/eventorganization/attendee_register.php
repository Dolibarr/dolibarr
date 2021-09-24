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
 *	\file       htdocs/public/eventorganization/attendee_register.php
 *	\ingroup    project
 *	\brief      Example of form to subscribe to an event
 *
 *  Note that you can add following constant to change behaviour of page
 *  MEMBER_NEWFORM_AMOUNT               Default amount for auto-subscribe form
 *  MEMBER_NEWFORM_EDITAMOUNT           0 or 1 = Amount can be edited
 *  MEMBER_NEWFORM_PAYONLINE            Suggest payment with paypal, paybox or stripe
 *  MEMBER_NEWFORM_DOLIBARRTURNOVER     Show field turnover (specific for dolibarr foundation)
 *  MEMBER_URL_REDIRECT_SUBSCRIPTION    Url to redirect once subscribe submitted
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
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/eventorganization/class/conferenceorbooth.class.php';
require_once DOL_DOCUMENT_ROOT.'/eventorganization/class/conferenceorboothattendee.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/paymentterm.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';

global $dolibarr_main_instance_unique_id;
global $dolibarr_main_url_root;

// Init vars
$errmsg = '';
$error = 0;
$backtopage = GETPOST('backtopage', 'alpha');
$action = GETPOST('action', 'aZ09');

$email = GETPOST("email");
$societe = GETPOST("societe");
$emailcompany = GETPOST("emailcompany");
$note_public = GETPOST('note_public', "nohtml");

// Getting id from Post and decoding it
$type = GETPOST('type', 'aZ09');
if ($type == 'conf') {
	$id = GETPOST('id', 'int');
} else {
	$id = GETPOST('fk_project', 'int') ? GETPOST('fk_project', 'int') : GETPOST('id', 'int');
}

$conference = new ConferenceOrBooth($db);
$project = new Project($db);

if ($type == 'conf') {
	$resultconf = $conference->fetch($id);
	if ($resultconf < 0) {
		print 'Bad value for parameter id';
		exit;
	}
	$resultproject = $project->fetch($conference->fk_project);
	if ($resultproject < 0) {
		$error++;
		$errmsg .= $project->error;
	}
}
if ($type == 'global') {
	$resultproject = $project->fetch($id);
	if ($resultproject < 0) {
		$error++;
		$errmsg .= $project->error;
	}
}


// Security check
$securekeyreceived = GETPOST('securekey', 'alpha');
$securekeytocompare = dol_hash($conf->global->EVENTORGANIZATION_SECUREKEY.'conferenceorbooth'.$id, 'md5');

// We check if the securekey collected is OK
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

// Security check
if (empty($conf->eventorganization->enabled)) {
	accessforbidden('', 0, 0, 1);
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
		print '<img id="dolpaymentlogo" src="'.$urllogo.'"';
		print '>';
		print '</div>';
		if (empty($conf->global->MAIN_HIDE_POWERED_BY)) {
			print '<div class="poweredbypublicpayment opacitymedium right"><a class="poweredbyhref" href="https://www.dolibarr.org?utm_medium=website&utm_source=poweredby" target="dolibarr" rel="noopener">'.$langs->trans("PoweredBy").'<br><img class="poweredbyimg" src="'.DOL_URL_ROOT.'/theme/dolibarr_logo.svg" width="80px"></a></div>';
		}
		print '</div>';
	}

	if (!empty($conf->global->EVENTORGANIZATION_IMAGE_PUBLIC_INTERFACE)) {
		print '<div class="backimagepubliceventorganizationsubscription">';
		print '<img id="idEVENTORGANIZATION_IMAGE_PUBLIC_INTERFACE" src="'.$conf->global->EVENTORGANIZATION_IMAGE_PUBLIC_INTERFACE.'">';
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
if (empty($reshook) && $action == 'add' && (!empty($conference->id) && $conference->status!=2  || !empty($project->id) && $project->status == Project::STATUS_VALIDATED)) {
	$error = 0;

	$urlback = '';

	$db->begin();

	if (!GETPOST("email")) {
		$error++;
		$errmsg .= $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Email"))."<br>\n";
	}
	// If the price has been set, name is required for the invoice
	if (!GETPOST("societe") && !empty(floatval($project->price_registration))) {
		$error++;
		$errmsg .= $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Company"))."<br>\n";
	}
	if (GETPOST("email") && !isValidEmail(GETPOST("email"))) {
		$error++;
		$langs->load("errors");
		$errmsg .= $langs->trans("ErrorBadEMail", GETPOST("email"))."<br>\n";
	}
	if (!GETPOST("country_id")) {
		$error++;
		$errmsg .= $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Country"))."<br>\n";
	}

	if (!$error) {
		// Check if attendee already exists (by email and for this event)
		$confattendee = new ConferenceOrBoothAttendee($db);

		if ($type == 'global') {
			$filter = array('t.fk_project'=>((int) $id), 'customsql'=>'t.email="'.$db->escape($email).'"');
		}
		if ($action == 'conf') {
			$filter = array('t.fk_actioncomm'=>((int) $id), 'customsql'=>'t.email="'.$db->escape($email).'"');
		}

		// Check if there is already an attendee into table eventorganization_conferenceorboothattendee for same event (or conference/booth)
		$resultfetchconfattendee = $confattendee->fetchAll('', '', 0, 0, $filter);

		if (is_array($resultfetchconfattendee) && count($resultfetchconfattendee) > 0) {
			// Found confattendee
			$confattendee = array_shift($resultfetchconfattendee);
		} else {
			// Need to create a confattendee
			$confattendee->date_creation = dol_now();
			$confattendee->email = $email;
			$confattendee->fk_project = $project->id;
			$confattendee->fk_actioncomm = $id;
			$confattendee->note_public = $note_public;
			$resultconfattendee = $confattendee->create($user);
			if ($resultconfattendee < 0) {
				$error++;
				$errmsg .= $confattendee->error;
			}
		}

		// At this point, we have an existing $confattendee. It may not be linked to a thirdparty.
		//var_dump($confattendee);

		// If the registration has already been paid for this attendee
		if (!empty($confattendee->date_subscription) && !empty($confattendee->amount)) {
			$securekeyurl = dol_hash($conf->global->EVENTORGANIZATION_SECUREKEY.'conferenceorbooth'.$id, 'master');
			$redirection = $dolibarr_main_url_root.'/public/eventorganization/subscriptionok.php?id='.((int) $id).'&securekey='.urlencode($securekeyurl);

			$mesg = $langs->trans("RegistrationAndPaymentWereAlreadyRecorder", $email);
			setEventMessages($mesg, null, 'mesgs');

			$db->commit();

			Header("Location: ".$redirection);
			exit;
		}

		$resultfetchthirdparty = 0;

		$genericcompanyname = $langs->trans('EventParticipant').' '.($emailcompany ? $emailcompany : $email);	// Keep this label simple so we can retreive same thirdparty for another event

		// Getting the thirdparty or creating it
		$thirdparty = new Societe($db);
		$contact = new Contact($db);
		// Fetch using fk_soc if the attendee was already found
		if (!empty($confattendee->fk_soc) && $confattendee->fk_soc > 0) {
			$resultfetchthirdparty = $thirdparty->fetch($confattendee->fk_soc);
		} else {
			if (empty($conf->global->EVENTORGANIZATION_DISABLE_RETREIVE_THIRDPARTY_FROM_NAME)) {
				// Fetch using the field input by end user if we have just created the attendee
				if ($resultfetchthirdparty <= 0 && !empty($societe) && !empty($emailcompany)) {
					$resultfetchthirdparty = $thirdparty->fetch('', $societe, '', '', '', '', '', '', '', '', $emailcompany);
					if ($resultfetchthirdparty > 0) {
						// We found a unique result with the name + emailcompany, so we set the fk_soc of attendee
						$confattendee->fk_soc = $thirdparty->id;
						$confattendee->update($user);
					} elseif ($resultfetchthirdparty == -2) {
						$thirdparty->error = $langs->trans("ErrorSeveralCompaniesWithNameContactUs", $mysoc->email);
					}
				}
				// Fetch using the field input by end user if we have just created the attendee
				if ($resultfetchthirdparty <= 0 && !empty($societe) && !empty($email) && $email != $emailcompany) {
					$resultfetchthirdparty = $thirdparty->fetch('', $societe, '', '', '', '', '', '', '', '', $email);
					if ($resultfetchthirdparty > 0) {
						// We found a unique result with the name + email, so we set the fk_soc of attendee
						$confattendee->fk_soc = $thirdparty->id;
						$confattendee->update($user);
					} elseif ($resultfetchthirdparty == -2) {
						$thirdparty->error = $langs->trans("ErrorSeveralCompaniesWithNameContactUs", $mysoc->email);
					}
				}
			}
			if ($resultfetchthirdparty <= 0 && !empty($emailcompany)) {
				// Try to find thirdparty from the email only
				$resultfetchthirdparty = $thirdparty->fetch('', '', '', '', '', '', '', '', '', '', $emailcompany);
				if ($resultfetchthirdparty > 0) {
					// We found a unique result with that email only, so we set the fk_soc of attendee
					$confattendee->fk_soc = $thirdparty->id;
					$confattendee->update($user);
				} elseif ($resultfetchthirdparty == -2) {
					$thirdparty->error = $langs->trans("ErrorSeveralCompaniesWithEmailContactUs", $mysoc->email);
				}
			}
			if ($resultfetchthirdparty <= 0 && !empty($email) && $email != $emailcompany) {
				// Try to find thirdparty from the email only
				$resultfetchthirdparty = $thirdparty->fetch('', '', '', '', '', '', '', '', '', '', $email);
				if ($resultfetchthirdparty > 0) {
					// We found a unique result with that email only, so we set the fk_soc of attendee
					$confattendee->fk_soc = $thirdparty->id;
					$confattendee->update($user);
				} elseif ($resultfetchthirdparty == -2) {
					$thirdparty->error = $langs->trans("ErrorSeveralCompaniesWithEmailContactUs", $mysoc->email);
				}
			}
			if ($resultfetchthirdparty <= 0 && !empty($genericcompanyname)) {
				// Try to find thirdparty from the generic mail only
				$resultfetchthirdparty = $thirdparty->fetch('', $genericcompanyname, '', '', '', '', '', '', '', '', '');
				if ($resultfetchthirdparty > 0) {
					// We found a unique result with that name + email, so we set the fk_soc of attendee
					$confattendee->fk_soc = $thirdparty->id;
					$confattendee->update($user);
				} elseif ($resultfetchthirdparty == -2) {
					$thirdparty->error = $langs->trans("ErrorSeveralCompaniesWithNameContactUs", $mysoc->email);
				}
			}

			// TODO Add more tests on a VAT number, profid or a name ?

			if ($resultfetchthirdparty <= 0 && !empty($email)) {
				// Try to find the thirdparty from the contact
				$resultfetchcontact = $contact->fetch('', null, '', $email);
				if ($resultfetchcontact > 0 && $contact->fk_soc > 0) {
					$thirdparty->fetch($contact->fk_soc);
					$confattendee->fk_soc = $thirdparty->id;
					$confattendee->update($user);
					$resultfetchthirdparty = 1;
				}
			}

			if ($resultfetchthirdparty <= 0 && !empty($societe)) {
				// Try to find thirdparty from the company name only
				$resultfetchthirdparty = $thirdparty->fetch('', $societe, '', '', '', '', '', '', '', '', '');
				if ($resultfetchthirdparty > 0) {
					// We found a unique result with that name only, so we set the fk_soc of attendee
					$confattendee->fk_soc = $thirdparty->id;
					$confattendee->update($user);
				} elseif ($resultfetchthirdparty == -2) {
					$thirdparty->error = "ErrorSeveralCompaniesWithNameContactUs";
				}
			}
		}

		// If price is empty, no need to create a thirdparty, so we force $resultfetchthirdparty as if we have already found thirdp party.
		if (empty(floatval($project->price_registration))) {
			$resultfetchthirdparty = 1;
		}

		if ($resultfetchthirdparty < 0) {
			// If an error was found
			$error++;
			$errmsg .= $thirdparty->error;
		} elseif ($resultfetchthirdparty == 0) {	// No thirdparty found + a payment is expected
			// Creation of a new thirdparty
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
			$thirdparty->country_id   = GETPOST("country_id", 'int');
			$thirdparty->state_id     = GETPOST("state_id", 'int');
			$thirdparty->email        = ($emailcompany ? $emailcompany : $email);

			// Load object modCodeTiers
			$module = (!empty($conf->global->SOCIETE_CODECLIENT_ADDON) ? $conf->global->SOCIETE_CODECLIENT_ADDON : 'mod_codeclient_leopard');
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
			} else {
				$thirdparty->country_code = getCountry($thirdparty->country_id, 2, $db, $langs);
				$thirdparty->country      = getCountry($thirdparty->country_code, 0, $db, $langs);

				// Update attendee country to match country of thirdparty
				$confattendee->fk_soc     = $thirdparty->id;
				$confattendee->update($user);
			}
		}
	}

	if (!$error) {
		// If the registration needs a payment
		if (!empty(floatval($project->price_registration))) {
			$outputlangs = $langs;

			// TODO Use default language of $thirdparty->default_lang to build $outputlang

			// Get product to use for invoice
			$productforinvoicerow = new Product($db);
			$productforinvoicerow->id = 0;

			$resultprod = 0;
			if ($conf->global->SERVICE_CONFERENCE_ATTENDEE_SUBSCRIPTION > 0) {
				$resultprod = $productforinvoicerow->fetch($conf->global->SERVICE_CONFERENCE_ATTENDEE_SUBSCRIPTION);
			}

			// Create invoice
			if ($resultprod < 0) {
				$error++;
				$errmsg .= $productforinvoicerow->error;
			} else {
				$facture = new Facture($db);
				if (empty($confattendee->fk_invoice)) {
					$facture->type = Facture::TYPE_STANDARD;
					$facture->socid = $thirdparty->id;
					$facture->paye = 0;
					$facture->date = dol_now();
					$facture->cond_reglement_id = $confattendee->cond_reglement_id;
					$facture->fk_project = $project->id;
					$facture->status = Facture::STATUS_DRAFT;

					if (empty($facture->cond_reglement_id)) {
						$paymenttermstatic = new PaymentTerm($confattendee->db);
						$facture->cond_reglement_id = $paymenttermstatic->getDefaultId();
						if (empty($facture->cond_reglement_id)) {
							$error++;
							$confattendee->error = 'ErrorNoPaymentTermRECEPFound';
							$confattendee->errors[] = $confattendee->error;
						}
					}
					$resultfacture = $facture->create($user);
					if ($resultfacture <= 0) {
						$confattendee->error = $facture->error;
						$confattendee->errors = $facture->errors;
						$error++;
					} else {
						$confattendee->fk_invoice = $resultfacture;
						$confattendee->update($user);
					}
				} else {
					$facture->fetch($confattendee->fk_invoice);
				}

				// Add link between invoice and the attendee registration
				/*if (!$error) {
					$facture->add_object_linked($confattendee->element, $confattendee->id);
				}*/
			}

			if (!$error) {
				// Add line to draft invoice
				$vattouse = get_default_tva($mysoc, $thirdparty, $productforinvoicerow->id);

				$labelforproduct = $outputlangs->trans("EventFee", $project->title);
				$date_start = $project->date_start;
				$date_end = $project->date_end;

				// If there is no lines yet, we add one
				if (empty($facture->lines)) {
					$result = $facture->addline($labelforproduct, floatval($project->price_registration), 1, $vattouse, 0, 0, $productforinvoicerow->id, 0, $date_start, $date_end, 0, 0, '', 'HT', 0, 1);
					if ($result <= 0) {
						$confattendee->error = $facture->error;
						$confattendee->errors = $facture->errors;
						$error++;
					}
				}
			}

			if (!$error) {
				$db->commit();

				// Registration was recorded and invoice was generated, but payment not yet done.
				// TODO
				// Send an email to says registration shas been received and that we are waiting for the payment.
				// Should send email template (EventOrganizationEmailRegistrationEvent) saved into conf EVENTORGANIZATION_TEMPLATE_EMAIL_REGISTRATION_EVENT.

				// Now we redirect to the payment page
				$sourcetouse = 'organizedeventregistration';
				$reftouse = $facture->id;
				$redirection = $dolibarr_main_url_root.'/public/payment/newpayment.php?source='.urlencode($sourcetouse).'&ref='.urlencode($reftouse);
				if (!empty($conf->global->PAYMENT_SECURITY_TOKEN)) {
					if (!empty($conf->global->PAYMENT_SECURITY_TOKEN_UNIQUE)) {
						$redirection .= '&securekey='.dol_hash($conf->global->PAYMENT_SECURITY_TOKEN . $sourcetouse . $reftouse, 2); // Use the source in the hash to avoid duplicates if the references are identical
					} else {
						$redirection .= '&securekey='.urlencode($conf->global->PAYMENT_SECURITY_TOKEN);
					}
				}

				Header("Location: ".$redirection);
				exit;
			} else {
				$db->rollback();
			}
		} else {
			$db->commit();

			// No price has been set
			// Validating the subscription
			$confattendee->setStatut(1);

			// Sending mail
			require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
			include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
			$formmail = new FormMail($db);
			// Set output language
			$outputlangs = new Translate('', $conf);
			$outputlangs->setDefaultLang(empty($thirdparty->default_lang) ? $mysoc->default_lang : $thirdparty->default_lang);
			// Load traductions files required by page
			$outputlangs->loadLangs(array("main", "members"));
			// Get email content from template
			$arraydefaultmessage = null;

			$labeltouse = $conf->global->EVENTORGANIZATION_TEMPLATE_EMAIL_AFT_SUBS_EVENT;
			if (!empty($labeltouse)) {
				$arraydefaultmessage = $formmail->getEMailTemplate($db, 'eventorganization_send', $user, $outputlangs, $labeltouse, 1, '');
			}

			if (!empty($labeltouse) && is_object($arraydefaultmessage) && $arraydefaultmessage->id > 0) {
				$subject = $arraydefaultmessage->topic;
				$msg     = $arraydefaultmessage->content;
			}

			$substitutionarray = getCommonSubstitutionArray($outputlangs, 0, null, $thirdparty);
			complete_substitutions_array($substitutionarray, $outputlangs, $object);

			$subjecttosend = make_substitutions($subject, $substitutionarray, $outputlangs);
			$texttosend = make_substitutions($msg, $substitutionarray, $outputlangs);

			$sendto = $thirdparty->email;
			$from = $conf->global->MAILING_EMAIL_FROM;
			$urlback = $_SERVER["REQUEST_URI"];

			$ishtml = dol_textishtml($texttosend); // May contain urls

			$mailfile = new CMailFile($subjecttosend, $sendto, $from, $texttosend, array(), array(), array(), '', '', 0, $ishtml);

			$result = $mailfile->sendfile();
			if ($result) {
				dol_syslog("EMail sent to ".$sendto, LOG_DEBUG, 0, '_payment');
			} else {
				dol_syslog("Failed to send EMail to ".$sendto, LOG_ERR, 0, '_payment');
			}

			$securekeyurl = dol_hash($conf->global->EVENTORGANIZATION_SECUREKEY.'conferenceorbooth'.$id, 2);
			$redirection = $dolibarr_main_url_root.'/public/eventorganization/subscriptionok.php?id='.((int) $id).'&securekey='.urlencode($securekeyurl);

			Header("Location: ".$redirection);
			exit;
		}
		//Header("Location: ".$urlback);
		//exit;
	} else {
		$db->rollback();
	}
}


/*
 * View
 */

$form = new Form($db);
$formcompany = new FormCompany($db);

llxHeaderVierge($langs->trans("NewRegistration"));

print '<br>';
print load_fiche_titre($langs->trans("NewRegistration"), '', '', 0, 0, 'center');


print '<div align="center">';
print '<div id="divsubscribe">';
print '<div class="center subscriptionformhelptext justify">';

// Welcome message

print $langs->trans("EvntOrgWelcomeMessage", $project->title . ' '. $conference->label);
print '<br>';
if ($conference->id) {
	print $langs->trans("Date").': ';
	print dol_print_date($conference->datep);
	if ($conference->date_end) {
		print ' - ';
		print dol_print_date($conference->datef);
	}
} else {
	print $langs->trans("Date").': ';
	print dol_print_date($project->date_start);
	if ($project->date_end) {
		print ' - ';
		print dol_print_date($project->date_end);
	}
}
print '</div>';

print '<br>';

dol_htmloutput_errors($errmsg);

if (!empty($conference->id) && $conference->status==ConferenceOrBooth::STATUS_CONFIRMED  || (!empty($project->id) && $project->status==Project::STATUS_VALIDATED)) {
	// Print form
	print '<form action="' . $_SERVER["PHP_SELF"] . '" method="POST" name="newmember">' . "\n";
	print '<input type="hidden" name="token" value="' . newToken() . '" / >';
	print '<input type="hidden" name="entity" value="' . $entity . '" />';
	print '<input type="hidden" name="action" value="add" />';
	print '<input type="hidden" name="type" value="' . $type . '" />';
	print '<input type="hidden" name="id" value="' . $conference->id . '" />';
	print '<input type="hidden" name="fk_project" value="' . $project->id . '" />';
	print '<input type="hidden" name="securekey" value="' . $securekeyreceived . '" />';

	print '<br>';

	print '<br><span class="opacitymedium">' . $langs->trans("FieldsWithAreMandatory", '*') . '</span><br>';
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

	print '<table class="border" summary="form to subscribe" id="tablesubscribe">' . "\n";

	// Email
	print '<tr><td>' . $langs->trans("EmailAttendee") . '<font color="red">*</font></td><td>';
	print img_picto('', 'email', 'class="pictofixedwidth"');
	print '<input type="text" name="email" maxlength="255" class="minwidth200" value="' . dol_escape_htmltag(GETPOST('email')) . '"></td></tr>' . "\n";

	// Company
	print '<tr id="trcompany" class="trcompany"><td>' . $langs->trans("Company");
	if (!empty(floatval($project->price_registration))) {
		print '<font color="red">*</font>';
	}
	print ' </td><td>';
	print img_picto('', 'company', 'class="pictofixedwidth"');
	print '<input type="text" name="societe" class="minwidth200" value="' . dol_escape_htmltag(GETPOST('societe')) . '"></td></tr>' . "\n";

	// Email company for invoice
	if ($project->price_registration) {
		print '<tr><td>' . $langs->trans("EmailCompanyForInvoice") . '</td><td>';
		print img_picto('', 'email', 'class="pictofixedwidth"');
		print '<input type="text" name="emailcompany" maxlength="255" class="minwidth200" value="' . dol_escape_htmltag(GETPOST('emailcompany')) . '"></td></tr>' . "\n";
	}

	// Address
	print '<tr><td>' . $langs->trans("Address") . '</td><td>' . "\n";
	print '<textarea name="address" id="address" wrap="soft" class="centpercent" rows="' . ROWS_2 . '">' . dol_escape_htmltag(GETPOST('address', 'restricthtml'), 0, 1) . '</textarea></td></tr>' . "\n";

	// Zip / Town
	print '<tr><td>' . $langs->trans('Zip') . ' / ' . $langs->trans('Town') . '</td><td>';
	print $formcompany->select_ziptown(GETPOST('zipcode'), 'zipcode', array('town', 'selectcountry_id', 'state_id'), 6, 1);
	print ' / ';
	print $formcompany->select_ziptown(GETPOST('town'), 'town', array('zipcode', 'selectcountry_id', 'state_id'), 0, 1);
	print '</td></tr>';

	// Country
	print '<tr><td>' . $langs->trans('Country') . '<font color="red">*</font></td><td>';
	print img_picto('', 'country', 'class="pictofixedwidth"');
	$country_id = GETPOST('country_id');
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
		print '<tr><td>' . $langs->trans('State') . '</td><td>';
		if ($country_code) {
			print $formcompany->select_state(GETPOST("state_id"), $country_code);
		} else {
			print '';
		}
		print '</td></tr>';
	}

	if ($project->price_registration) {
		print '<tr><td>' . $langs->trans('Price') . '</td><td>';
		print price($project->price_registration, 1, $langs, 1, -1, -1, $conf->currency);
		print '</td></tr>';
	}

	$notetoshow = $note_public;
	print '<tr><td>' . $langs->trans('Note') . '</td><td>';
	if (!empty($conf->global->EVENTORGANIZATION_DEFAULT_NOTE_ON_REGISTRATION)) {
		$notetoshow = str_replace('\n', "\n", $conf->global->EVENTORGANIZATION_DEFAULT_NOTE_ON_REGISTRATION);
	}
	print '<textarea name="note_public" class="centpercent" rows="'.ROWS_9.'">'.dol_escape_htmltag($notetoshow, 0, 1).'</textarea>';
	print '</td></tr>';

	print "</table>\n";

	print dol_get_fiche_end();

	// Save
	print '<div class="center">';
	print '<input type="submit" value="' . $langs->trans("Submit") . '" id="submitsave" class="button">';
	if (!empty($backtopage)) {
		print ' &nbsp; &nbsp; <input type="submit" value="' . $langs->trans("Cancel") . '" id="submitcancel" class="button button-cancel">';
	}
	print '</div>';


	print "</form>\n";
	print "<br>";
	print '</div></div>';
} else {
	print $langs->trans("ConferenceIsNotConfirmed");
}

llxFooterVierge();

$db->close();
