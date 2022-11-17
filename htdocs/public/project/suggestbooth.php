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
 *	\file       htdocs/public/project/suggestbooth.php
 *	\ingroup    member
 *	\brief      Example of form to suggest a booth
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
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/paymentterm.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';

global $dolibarr_main_instance_unique_id;
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
$datestart = dol_mktime(0, 0, 0, GETPOST('datestartmonth', 'int'), GETPOST('datestartday', 'int'), GETPOST('datestartyear', 'int'));
$dateend = dol_mktime(23, 59, 59, GETPOST('dateendmonth', 'int'), GETPOST('dateendday', 'int'), GETPOST('dateendyear', 'int'));
$id = GETPOST('id');

$project = new Project($db);
$resultproject = $project->fetch($id);
if ($resultproject < 0) {
	$error++;
	$errmsg .= $project->error;
}

// Security check
$securekeyreceived = GETPOST("securekey");
$securekeytocompare = dol_hash($conf->global->EVENTORGANIZATION_SECUREKEY.'conferenceorbooth'.$id, 'md5');

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
$arrayofeventtype = $cactioncomm->liste_array('', 'id', '', 0, "module='booth@eventorganization'");

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

	if (!empty($conf->global->PROJECT_IMAGE_PUBLIC_SUGGEST_BOOTH)) {
		print '<div class="backimagepublicsuggestbooth">';
		print '<img id="idPROJECT_IMAGE_PUBLIC_SUGGEST_BOOTH" src="'.$conf->global->PROJECT_IMAGE_PUBLIC_SUGGEST_BOOTH.'">';
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

	if (!GETPOST("email")) {
		$error++;
		$errmsg .= $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Email"))."<br>\n";
	}
	if (!GETPOST("label")) {
		$error++;
		$errmsg .= $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Label"))."<br>\n";
	}
	if (!GETPOST("note")) {
		$error++;
		$errmsg .= $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Note"))."<br>\n";
	}
	if (!GETPOST("email")) {
		$error++;
		$errmsg .= $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Email"))."<br>\n";
	}
	if (!GETPOST("lastname")) {
		$error++;
		$errmsg .= $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Name"))."<br>\n";
	}
	if (!GETPOST("societe")) {
		$error++;
		$errmsg .= $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Societe"))."<br>\n";
	}
	if (GETPOST("email") && !isValidEmail(GETPOST("email"))) {
		$error++;
		$langs->load("errors");
		$errmsg .= $langs->trans("ErrorBadEMail", GETPOST("email"))."<br>\n";
	}
	if (!GETPOST("country_id") && !empty(floatval($project->price_booth))) {
		$error++;
		$errmsg .= $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Country"))."<br>\n";
	}

	if (!$error) {
		// Getting the thirdparty or creating it
		$thirdparty = new Societe($db);
		$resultfetchthirdparty = $thirdparty->fetch('', $societe);

		if ($resultfetchthirdparty<=0) {
			// Need to create a new one (not found or multiple with the same name)
			$thirdparty->name     = $societe;
			$thirdparty->address      = GETPOST("address");
			$thirdparty->zip          = GETPOST("zipcode");
			$thirdparty->town         = GETPOST("town");
			$thirdparty->client       = 2;
			$thirdparty->fournisseur  = 0;
			$thirdparty->country_id   = GETPOST("country_id", 'int');
			$thirdparty->state_id     = GETPOST("state_id", 'int');
			$thirdparty->email        = $email;

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
			if ($readythirdparty <0) {
				$error++;
				$errmsg .= $thirdparty->error;
			} else {
				$thirdparty->country_code = getCountry($thirdparty->country_id, 2, $db, $langs);
				$thirdparty->country      = getCountry($thirdparty->country_code, 0, $db, $langs);
			}
		}
		// From there we have a thirdparty, now looking for the contact
		if (!$error) {
			$contact = new Contact($db);
			$resultcontact = $contact->fetch('', '', '', $email);
			if ($resultcontact<=0) {
				// Need to create a contact
				$contact->socid = $thirdparty->id;
				$contact->lastname = (string) GETPOST("lastname", 'alpha');
				$contact->firstname = (string) GETPOST("firstname", 'alpha');
				$contact->address = (string) GETPOST("address", 'alpha');
				$contact->zip = (string) GETPOST("zipcode", 'alpha');
				$contact->town = (string) GETPOST("town", 'alpha');
				$contact->country_id = (int) GETPOST("country_id", 'int');
				$contact->state_id = (int) GETPOST("state_id", 'int');
				$contact->email = $email;
				$contact->statut = 1; //Default status to Actif

				$resultcreatecontact = $contact->create($user);
				if ($resultcreatecontact<0) {
					$error++;
					$errmsg .= $contact->error;
				}
			}
		}

		if (!$error) {
			// Adding supplier tag and tag from setup to thirdparty
			$category = new Categorie($db);

			$resultcategory = $category->fetch($conf->global->EVENTORGANIZATION_CATEG_THIRDPARTY_BOOTH);

			if ($resultcategory<=0) {
				$error++;
				$errmsg .= $category->error;
			} else {
				$resultsetcategory = $thirdparty->setCategoriesCommon(array($category->id), CATEGORIE::TYPE_CUSTOMER, false);
				if ($resultsetcategory < 0) {
					$error++;
					$errmsg .= $thirdparty->error;
				} else {
					$thirdparty->fournisseur = 1;

					// Load object modCodeFournisseur
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
					$modCodeFournisseur = new $module;
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
			$resultconforbooth = $conforbooth->create($user);
			if ($resultconforbooth<=0) {
				$error++;
				$errmsg .= $conforbooth->error;
			} else {
				// Adding the contact to the project
				$resultaddcontact = $conforbooth->add_contact($contact->id, 'RESPONSIBLE');
				if ($resultaddcontact<0) {
					$error++;
					$errmsg .= $conforbooth->error;
				} else {
					// If this is a paying booth, we have to redirect to payment page and create an invoice
					if (!empty(floatval($project->price_booth))) {
						$productforinvoicerow = new Product($db);
						$resultprod = $productforinvoicerow->fetch($conf->global->SERVICE_BOOTH_LOCATION);
						if ($resultprod < 0) {
							$error++;
							$errmsg .= $productforinvoicerow->error;
						} else {
							$facture = new Facture($db);
							$facture->type = Facture::TYPE_STANDARD;
							$facture->socid = $thirdparty->id;
							$facture->paye = 0;
							$facture->date = dol_now();
							$facture->cond_reglement_id = $contact->cond_reglement_id;
							$facture->fk_project = $project->id;

							if (empty($facture->cond_reglement_id)) {
								$paymenttermstatic = new PaymentTerm($contact->db);
								$facture->cond_reglement_id = $paymenttermstatic->getDefaultId();
								if (empty($facture->cond_reglement_id)) {
									$error++;
									$contact->error = 'ErrorNoPaymentTermRECEPFound';
									$contact->errors[] = $contact->error;
								}
							}
							$resultfacture = $facture->create($user);
							if ($resultfacture <= 0) {
								$contact->error = $facture->error;
								$contact->errors = $facture->errors;
								$error++;
							} else {
								$db->commit();
								$facture->add_object_linked($conforbooth->element, $conforbooth->id);
							}
						}

						if (!$error) {
							// Add line to draft invoice
							$vattouse = get_default_tva($mysoc, $thirdparty, $productforinvoicerow->id);
							$result = $facture->addline($langs->trans("BoothLocationFee", $conforbooth->label, dol_print_date($conforbooth->datep, '%d/%m/%y %H:%M:%S'), dol_print_date($conforbooth->datep2, '%d/%m/%y %H:%M:%S')), floatval($project->price_booth), 1, $vattouse, 0, 0, $productforinvoicerow->id, 0, dol_now(), '', 0, 0, '', 'HT', 0, 1);
							if ($result <= 0) {
								$contact->error = $facture->error;
								$contact->errors = $facture->errors;
								$error++;
							}
							/*if (!$error) {
								$valid = true;
								$sourcetouse = 'boothlocation';
								$reftouse = $facture->id;
								$redirection = $dolibarr_main_url_root.'/public/payment/newpayment.php?source='.$sourcetouse.'&ref='.$reftouse.'&booth='.$conforbooth->id;
								if (!empty($conf->global->PAYMENT_SECURITY_TOKEN)) {
									if (!empty($conf->global->PAYMENT_SECURITY_TOKEN_UNIQUE)) {
										$redirection .= '&securekey='.dol_hash($conf->global->PAYMENT_SECURITY_TOKEN . $sourcetouse . $reftouse, 2); // Use the source in the hash to avoid duplicates if the references are identical
									} else {
										$redirection .= '&securekey='.$conf->global->PAYMENT_SECURITY_TOKEN;
									}
								}
								Header("Location: ".$redirection);
								exit;
							}*/
						}
					} else {
						// If no price has been set for the booth, we confirm it as suggested and we update
						$conforbooth->status = ConferenceOrBooth::STATUS_SUGGESTED;
						$conforbooth->update($user);
					}
				}
			}
		}
	}
	if (!$error) {
		$db->commit();

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

		$labeltouse = $conf->global->EVENTORGANIZATION_TEMPLATE_EMAIL_ASK_BOOTH;
		if (!empty($labeltouse)) {
			$arraydefaultmessage = $formmail->getEMailTemplate($db, 'conferenceorbooth', $user, $outputlangs, $labeltouse, 1, '');
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
		$redirection = $dolibarr_main_url_root.'/public/eventorganization/subscriptionok.php?id='.$id.'&securekey='.$securekeyurl;
		Header("Location: ".$redirection);
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

llxHeaderVierge($langs->trans("NewSuggestionOfBooth"));


print load_fiche_titre($langs->trans("NewSuggestionOfBooth"), '', '', 0, 0, 'center');


print '<div align="center">';
print '<div id="divsubscribe">';
print '<div class="center subscriptionformhelptext justify">';

// Welcome message
$text  = '<tr><td class="textpublicpayment"><strong>'.$langs->trans("EvntOrgRegistrationBoothWelcomeMessage").'</strong></td></tr></br>';
$text .= '<tr><td class="textpublicpayment">'.$langs->trans("EvntOrgRegistrationBoothHelpMessage").' '.$project->label.'.<br><br></td></tr>'."\n";
$text .= '<tr><td class="textpublicpayment">'.$project->note_public.'</td></tr>'."\n";;
print $text;
print '</div>';

dol_htmloutput_errors($errmsg);

// Print form
print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST" name="newmember">'."\n";
print '<input type="hidden" name="token" value="'.newToken().'" / >';
print '<input type="hidden" name="entity" value="'.$entity.'" />';
print '<input type="hidden" name="action" value="add" />';
print '<input type="hidden" name="id" value="'.$id.'" />';
print '<input type="hidden" name="securekey" value="'.$securekeyreceived.'" />';

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

// Name
print '<tr><td><label for="lastname">'.$langs->trans("Lastname").'<FONT COLOR="red">*</FONT></label></td>';
print '<td colspan="3"><input name="lastname" id="lastname" type="text" class="maxwidth100onsmartphone" maxlength="80" value="'.dol_escape_htmltag(GETPOST("lastname", 'alpha') ?GETPOST("lastname", 'alpha') : $object->lastname).'" autofocus="autofocus"></td>';
print '</tr>';
// Email
print '<tr><td>'.$langs->trans("Email").'<FONT COLOR="red">*</FONT></td><td><input type="text" name="email" maxlength="255" class="minwidth150" value="'.dol_escape_htmltag(GETPOST('email')).'"></td></tr>'."\n";
// Company
print '<tr id="trcompany" class="trcompany"><td>'.$langs->trans("Company").'<FONT COLOR="red">*</FONT>';
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
print '<tr><td>'.$langs->trans('Country');
print '<span style="color:red">*</span>';

print '</td><td>';
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
	print '<tr><td>'.$langs->trans('State').'</td><td>';
	if ($country_code) {
		print $formcompany->select_state(GETPOST("state_id"), $country_code);
	} else {
		print '';
	}
	print '</td></tr>';
}
// Type of event
print '<tr><td>'.$langs->trans("EventType").'<FONT COLOR="red">*</FONT></td>'."\n";
print '<td>'.FORM::selectarray('eventtype', $arrayofeventtype, $eventtype).'</td>';
// Label
print '<tr><td>'.$langs->trans("LabelOfBooth").'<FONT COLOR="red">*</FONT></td>'."\n";
print '</td><td><input type="text" name="label" class="minwidth150" value="'.dol_escape_htmltag(GETPOST('label')).'"></td></tr>'."\n";
// Note
print '<tr><td>'.$langs->trans("Description").'<FONT COLOR="red">*</FONT></td>'."\n";
print '<td><textarea name="note" id="note" wrap="soft" class="quatrevingtpercent" rows="'.ROWS_3.'">'.dol_escape_htmltag(GETPOST('note', 'restricthtml'), 0, 1).'</textarea></td></tr>'."\n";

print "</table>\n";

print dol_get_fiche_end();


// Show all action buttons
print '<div class="center">';
print '<br>';
print '<input type="submit" value="'.$langs->trans("SuggestBooth").'" name="suggestbooth" id="suggestbooth" class="button">';
print '</div>';
print '<br><br>';



print "</form>\n";
print "<br>";
print '</div></div>';


llxFooterVierge();

$db->close();
